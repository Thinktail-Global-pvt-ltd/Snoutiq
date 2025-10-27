import React, { useCallback, useEffect, useMemo, useState } from "react";

const useResponsive = () => {
  const getScale = (size) => {
    if (typeof window === "undefined") return size;
    return (window.innerWidth / 375) * size;
  };

  const moderateScale = (size, factor = 0.5) => {
    const scaledSize = getScale(size);
    return size + (scaledSize - size) * factor;
  };

  return { moderateScale };
};

const LiveDoctorSelectionModal = React.memo(
  ({ visible, onClose, liveDoctors, onCallDoctor, loading }) => {
    const { moderateScale } = useResponsive();
    const [selectedDoctor, setSelectedDoctor] = useState(null);
    const [showProfile, setShowProfile] = useState(false);
    const [profileDoctor, setProfileDoctor] = useState(null);
    const [imageLoadErrors, setImageLoadErrors] = useState({});
    const [searchTerm, setSearchTerm] = useState("");
    const [sortBy, setSortBy] = useState("distance");

    // Filter and sort doctors
    const filteredDoctors = useMemo(() => {
      if (!liveDoctors) return [];
      
      let doctors = [...liveDoctors];
      
      // Filter by search term
      if (searchTerm) {
        doctors = doctors.filter(doctor => 
          doctor.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
          doctor.clinic_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
          doctor.specialization?.toLowerCase().includes(searchTerm.toLowerCase())
        );
      }
      
      // Sort doctors
      switch (sortBy) {
        case "rating":
          doctors.sort((a, b) => (b.rating || 0) - (a.rating || 0));
          break;
        case "experience":
          doctors.sort((a, b) => (b.experience || 0) - (a.experience || 0));
          break;
        case "distance":
        default:
          doctors.sort((a, b) => (a.distance || 0) - (b.distance || 0));
          break;
      }
      
      return doctors;
    }, [liveDoctors, searchTerm, sortBy]);

    useEffect(() => {
      if (!visible || !loading) {
        const timer = setTimeout(() => {
          setSelectedDoctor(null);
        }, 500);
        return () => clearTimeout(timer);
      }
    }, [visible, loading]);

    useEffect(() => {
      if (!visible) {
        setShowProfile(false);
        setProfileDoctor(null);
        setImageLoadErrors({});
        setSearchTerm("");
      }
    }, [visible]);

    const doctorCountText = useMemo(() => {
      const count = filteredDoctors.length;
      return `${count} doctor${count !== 1 ? 's' : ''} available`;
    }, [filteredDoctors.length]);

    const handleCallDoctor = useCallback(
      (doctor) => {
        if (loading) return;
        
        setSelectedDoctor(doctor.id);
        
        // Show professional loading state
        console.log(`📞 Calling Dr. ${doctor.name}...`);
        
        onCallDoctor(doctor);
      },
      [onCallDoctor, loading]
    );

    const handleViewProfile = useCallback((doctor) => {
      setProfileDoctor(doctor);
      setShowProfile(true);
    }, []);

    const handleCloseProfile = useCallback(() => {
      setShowProfile(false);
      setTimeout(() => {
        setProfileDoctor(null);
        setImageLoadErrors({});
      }, 300);
    }, []);

    const handleShareDoctor = useCallback(async (doctor) => {
      try {
        const shareUrl = `https://snoutiq.com/backend/vet/${doctor.slug}`;
        const message = `Check out Dr. ${doctor.business_status || doctor.name}\n${doctor.clinic_name || "Veterinary Clinic"}\n\n${shareUrl}`;

        if (navigator.share) {
          await navigator.share({
            title: `Dr. ${doctor.business_status || doctor.name}`,
            text: message,
            url: shareUrl,
          });
        } else {
          await navigator.clipboard.writeText(message);
          alert("Doctor information copied to clipboard!");
        }
      } catch (error) {
        console.error("Error sharing:", error);
      }
    }, []);

    const handleImageError = useCallback((imageId) => {
      setImageLoadErrors((prev) => ({ ...prev, [imageId]: true }));
    }, []);

    const renderDoctorItem = useCallback(
      (item) => {
        const isLoading = selectedDoctor === item.id && loading;
        const avatarError = imageLoadErrors[`avatar-${item.id}`];

        return (
          <div className="bg-white rounded-lg border border-gray-200 shadow-xs hover:shadow-sm transition-all duration-200 mb-3 overflow-hidden group">
            <div className="p-4 flex items-center gap-3">
              {/* Doctor Avatar */}
              <div className="relative flex-shrink-0">
                {item.profile_image && !avatarError ? (
                  <img
                    src={item.profile_image}
                    alt={`Dr. ${item.business_status || item.slug}`}
                    className="w-12 h-12 rounded-lg object-cover border border-gray-200"
                    onError={() => handleImageError(`avatar-${item.id}`)}
                  />
                ) : (
                  <div className="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                    <span className="text-white font-semibold text-sm">
                      {item.slug?.charAt(0) || "D"}
                    </span>
                  </div>
                )}
                <div className="absolute -bottom-1 -right-1 w-3 h-3 rounded-full bg-white border border-white shadow-xs flex items-center justify-center">
                  <div className="w-1.5 h-1.5 rounded-full bg-green-500"></div>
                </div>
              </div>

              {/* Doctor Info */}
              <div className="flex-1 min-w-0">
                <div className="flex items-center justify-between mb-1">
                  <div className="min-w-0">
                    <h3 className="text-sm font-semibold text-gray-900 truncate">
                      Dr. {item.slug || "Veterinarian"}
                    </h3>
                    <p className="text-gray-600 text-xs truncate">
                      {item.clinic_name || "Veterinary Clinic"}
                    </p>
                  </div>
                  <button
                    onClick={(e) => {
                      e.stopPropagation();
                      handleCallDoctor(item);
                    }}
                    disabled={isLoading}
                    className={`flex items-center gap-1 px-3 py-1.5 rounded-lg font-medium text-xs transition-all ${
                      isLoading
                        ? "bg-gray-100 text-gray-400 cursor-not-allowed"
                        : "bg-blue-50 text-blue-600 hover:bg-blue-100 border border-blue-200"
                    }`}
                  >
                    {isLoading ? (
                      <>
                        <div className="w-2 h-2 border border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                        Calling...
                      </>
                    ) : (
                      <>
                        <svg className="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                          <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                        </svg>
                        Call Now
                      </>
                    )}
                  </button>
                </div>

                {/* Stats */}
                <div className="flex items-center gap-2 mb-2">
                  {item.rating && (
                    <div className="flex items-center gap-1">
                      <div className="flex items-center gap-1 bg-yellow-50 px-1.5 py-0.5 rounded text-xs">
                        <svg className="w-2.5 h-2.5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <span className="text-xs font-medium text-gray-700">{item.rating}</span>
                      </div>
                    </div>
                  )}
                  {item.distance && (
                    <div className="flex items-center gap-1 bg-blue-50 px-1.5 py-0.5 rounded text-xs">
                      <svg className="w-2.5 h-2.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
                      </svg>
                      <span className="text-xs font-medium text-gray-700">{item.distance.toFixed(1)} km</span>
                    </div>
                  )}
                </div>

                {/* Actions */}
                <div className="flex items-center gap-1">
                  <button
                    onClick={(e) => {
                      e.stopPropagation();
                      handleViewProfile(item);
                    }}
                    className="flex items-center gap-1 px-2 py-1 text-gray-500 hover:text-gray-700 font-normal text-xs transition-colors"
                  >
                    <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Profile
                  </button>
                  <span className="text-gray-300 text-xs">•</span>
                  <button
                    onClick={(e) => {
                      e.stopPropagation();
                      handleShareDoctor(item);
                    }}
                    className="flex items-center gap-1 px-2 py-1 text-gray-500 hover:text-gray-700 font-normal text-xs transition-colors"
                  >
                    <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                    Share
                  </button>
                </div>
              </div>
            </div>
          </div>
        );
      },
      [handleViewProfile, handleCallDoctor, handleShareDoctor, selectedDoctor, loading, imageLoadErrors, handleImageError]
    );

    const emptyState = useMemo(
      () => (
        <div className="flex flex-col items-center justify-center py-8 px-4 text-center">
          <div className="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
            <svg className="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h3 className="text-sm font-semibold text-gray-900 mb-1">No doctors available</h3>
          <p className="text-gray-500 text-xs max-w-xs">
            {searchTerm 
              ? "No doctors match your search criteria."
              : "All veterinarians are currently busy."
            }
          </p>
          {searchTerm && (
            <button
              onClick={() => setSearchTerm("")}
              className="mt-2 px-3 py-1 text-blue-600 text-xs font-medium hover:text-blue-700 transition-colors"
            >
              Clear search
            </button>
          )}
        </div>
      ),
      [searchTerm]
    );

    const DoctorProfileView = useMemo(() => {
      if (!profileDoctor) return null;
      console.log(profileDoctor,"ankit");
      
    
      const profileAvatarError = imageLoadErrors[`profile-avatar-${profileDoctor.id}`];
      const isCallingThisDoctor = selectedDoctor === profileDoctor.id && loading;
    
      return (
        <div className={`fixed inset-0 z-50 ${showProfile ? "animate-in fade-in" : "animate-out fade-out"}`}>
          {/* Backdrop */}
          <div className="absolute inset-0 bg-black bg-opacity-50" onClick={handleCloseProfile} />
          
          {/* Modal Container */}
          <div className="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl max-h-[85vh] overflow-hidden animate-in slide-in-from-bottom">
            {/* Header - Compact */}
            <div className="flex items-center justify-between p-4 border-b border-gray-200 bg-white">
              <button
                onClick={handleCloseProfile}
                className="p-2 hover:bg-gray-50 rounded-lg transition-colors"
              >
                <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
              <h2 className="text-lg font-semibold text-gray-900">Doctor Details</h2>
              <button
                onClick={() => handleShareDoctor(profileDoctor)}
                className="p-2 hover:bg-gray-50 rounded-lg transition-colors"
              >
                <svg className="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                </svg>
              </button>
            </div>
    
            <div className="overflow-y-auto max-h-[calc(85vh-140px)]">
              {/* Profile Header - Compact */}
              <div className="p-4 border-b border-gray-100">
                <div className="flex items-center gap-3">
                  {/* Avatar */}
                  <div className="relative flex-shrink-0">
                    {profileDoctor.profile_image && !profileAvatarError ? (
                      <img
                        src={profileDoctor.profile_image}
                        alt={`Dr. ${profileDoctor.business_status || profileDoctor.name}`}
                        className="w-14 h-14 rounded-xl object-cover border border-gray-200"
                        onError={() => handleImageError(`profile-avatar-${profileDoctor.id}`)}
                      />
                    ) : (
                      <div className="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                        <span className="text-white font-bold text-lg">
                          {profileDoctor.business_status?.charAt(0) || profileDoctor.name?.charAt(0) || "D"}
                        </span>
                      </div>
                    )}
                    <div className="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-white border border-white shadow-xs flex items-center justify-center">
                      <div className="w-2 h-2 rounded-full bg-green-500"></div>
                    </div>
                  </div>
    
                  {/* Basic Info */}
                  <div className="flex-1 min-w-0">
                    <h1 className="text-lg font-bold text-gray-900 truncate">
                      Dr. {profileDoctor.business_status || profileDoctor.name || "Veterinarian"}
                    </h1>
                    <p className="text-gray-600 text-sm truncate">
                      {profileDoctor.clinic_name || "Veterinary Clinic"}
                    </p>
                    {profileDoctor.specialization && (
                      <p className="text-blue-600 text-sm font-medium mt-1">
                        {profileDoctor.specialization}
                      </p>
                    )}
                  </div>
                </div>
    
                {/* Quick Stats */}
                <div className="flex gap-4 mt-3 pt-3 border-t border-gray-100">
                  {profileDoctor.rating && (
                    <div className="flex flex-col items-center flex-1">
                      <div className="flex items-center gap-1 text-sm">
                        <svg className="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <span className="font-semibold text-gray-900">{profileDoctor.rating}</span>
                      </div>
                      <span className="text-gray-500 text-xs mt-1">Rating</span>
                    </div>
                  )}
                  
                  {profileDoctor.experience && (
                    <div className="flex flex-col items-center flex-1">
                      <div className="flex items-center gap-1 text-sm">
                        <svg className="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span className="font-semibold text-gray-900">{profileDoctor.experience}+</span>
                      </div>
                      <span className="text-gray-500 text-xs mt-1">Years Exp</span>
                    </div>
                  )}
                  
                  {profileDoctor.distance && (
                    <div className="flex flex-col items-center flex-1">
                      <div className="flex items-center gap-1 text-sm">
                        <svg className="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
                        </svg>
                        <span className="font-semibold text-gray-900">{profileDoctor.distance.toFixed(1)}</span>
                      </div>
                      <span className="text-gray-500 text-xs mt-1">Km Away</span>
                    </div>
                  )}
                </div>
              </div>
    
              {/* Contact & Details */}
              <div className="p-4 space-y-4">
                {/* Contact Info - Compact */}
                <div className="bg-gray-50 rounded-lg p-3">
                  <h3 className="text-sm font-semibold text-gray-900 mb-2">Contact Info</h3>
                  <div className="space-y-2">
                    <div className="flex justify-between items-center">
                      <span className="text-gray-600 text-sm">Phone:</span>
                      <span className="text-gray-900 font-medium text-sm">
                        {profileDoctor.mobile || "Not available"}
                      </span>
                    </div>
                    <div className="flex justify-between items-center">
                      <span className="text-gray-600 text-sm">Email:</span>
                      <span className="text-gray-900 font-medium text-sm truncate max-w-[140px]">
                        {profileDoctor.email || "Not available"}
                      </span>
                    </div>
                  </div>
                </div>
    
                {/* Consultation Fee - Prominent */}
                <div className="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
                  <div className="flex items-center justify-between">
                    <div>
                      <h3 className="text-sm font-semibold text-gray-900">Video Consultation</h3>
                      <p className="text-gray-600 text-xs mt-1">Instant connect • Secure payment</p>
                    </div>
                    <div className="text-right">
                      <p className="text-xl font-bold text-blue-600">
                        ₹{profileDoctor.chat_price || "500"}
                      </p>
                      <p className="text-gray-500 text-xs">per session</p>
                    </div>
                  </div>
                </div>
    
                {/* Bio - Conditional */}
                {profileDoctor.bio && profileDoctor.bio !== "null" && profileDoctor.bio.trim() !== "" && (
                  <div className="bg-gray-50 rounded-lg p-3">
                    <h3 className="text-sm font-semibold text-gray-900 mb-2">
                      About Dr. {profileDoctor.business_status || profileDoctor.name}
                    </h3>
                    <p className="text-gray-700 text-sm leading-relaxed line-clamp-4">
                      {profileDoctor.bio}
                    </p>
                  </div>
                )}
              </div>
            </div>
    
            {/* Fixed Action Button - Razorpay Style */}
            <div className="sticky bottom-0 left-0 right-0 p-4 bg-white border-t border-gray-200">
              {isCallingThisDoctor ? (
                <div className="flex items-center justify-center gap-3 py-3 bg-gray-400 rounded-lg">
                  <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                  <span className="text-white font-semibold text-sm">Connecting to Dr. {profileDoctor.business_status || profileDoctor.name}...</span>
                </div>
              ) : (
                <button
                  onClick={() => {
                    handleCloseProfile();
                    setTimeout(() => {
                      handleCallDoctor(profileDoctor);
                    }, 300);
                  }}
                  className="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-semibold text-sm flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md"
                >
                  <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                  </svg>
                  Start Video Call - ₹{profileDoctor.chat_price || "500"}
                </button>
              )}
            </div>
          </div>
        </div>
      );
    }, [profileDoctor, showProfile, handleCloseProfile, handleShareDoctor, handleCallDoctor, selectedDoctor, loading, imageLoadErrors, handleImageError]);

    if (!visible) return null;

    return (
      <>
        {/* Main Modal - Compact Razorpay Style */}
        <div className="fixed inset-0 z-40 flex items-end justify-center bg-black bg-opacity-50 animate-in fade-in">
          <div className="bg-white rounded-t-xl w-full max-w-md max-h-[70vh] overflow-hidden animate-in slide-in-from-bottom shadow-xl">
            {/* Header */}
            <div className="relative p-4 border-b border-gray-200 bg-white">
              <div className="flex items-center justify-between mb-3">
                <div>
                  <h2 className="text-lg font-bold text-gray-900">Available Veterinarians</h2>
                  <p className="text-gray-600 text-xs mt-1">{doctorCountText}</p>
                </div>
                <button
                  onClick={onClose}
                  className="p-1.5 hover:bg-gray-100 rounded-lg transition-colors"
                >
                  <svg className="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              {/* Search and Filter */}
              {/* <div className="flex gap-2">
                <div className="flex-1 relative">
                  <svg className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                  <input
                    type="text"
                    placeholder="Search doctors..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="w-full pl-9 pr-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all"
                  />
                </div>
                <select
                  value={sortBy}
                  onChange={(e) => setSortBy(e.target.value)}
                  className="px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all"
                >
                  <option value="distance">Nearest</option>
                  <option value="rating">Top Rated</option>
                  <option value="experience">Experienced</option>
                </select>
              </div> */}
            </div>

            {/* Doctors List */}
            <div className="p-4 overflow-y-auto max-h-[calc(70vh-120px)]">
              {filteredDoctors.length > 0 ? (
                <div className="space-y-2">
                  {filteredDoctors.map((doctor) => (
                    <div key={doctor.id}>
                      {renderDoctorItem(doctor)}
                    </div>
                  ))}
                </div>
              ) : (
                emptyState
              )}
            </div>
          </div>
        </div>

        {/* Doctor Profile Modal */}
        {DoctorProfileView}
      </>
    );
  }
);

export default LiveDoctorSelectionModal;