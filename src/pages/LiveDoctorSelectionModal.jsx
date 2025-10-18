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
        setSelectedDoctor(doctor.id);
        onCallDoctor(doctor);
      },
      [onCallDoctor]
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
          <div className="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 mb-4 overflow-hidden group">
            <div className="p-6 flex items-start gap-4">
              {/* Doctor Avatar */}
              <div className="relative flex-shrink-0">
                {item.profile_image && !avatarError ? (
                  <img
                    src={item.profile_image}
                    alt={`Dr. ${item.business_status || item.name}`}
                    className="w-20 h-20 rounded-2xl object-cover border-2 border-white shadow-md"
                    onError={() => handleImageError(`avatar-${item.id}`)}
                  />
                ) : (
                  <div className="w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-md">
                    <span className="text-white font-bold text-lg">
                      {item.business_status?.charAt(0) || item.name?.charAt(0) || "D"}
                    </span>
                  </div>
                )}
                <div className="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-white border-2 border-white shadow-sm flex items-center justify-center">
                  <div className="w-3 h-3 rounded-full bg-green-500 animate-pulse"></div>
                </div>
              </div>

              {/* Doctor Info */}
              <div className="flex-1 min-w-0">
                <div className="flex items-start justify-between mb-2">
                  <div className="min-w-0">
                    <h3 className="text-lg font-semibold text-gray-900 truncate">
                      Dr. {item.business_status || item.name || "Veterinarian"}
                    </h3>
                    <p className="text-gray-600 text-sm font-medium truncate">
                      {item.clinic_name || "Veterinary Clinic"}
                    </p>
                    {item.specialization && (
                      <p className="text-blue-600 text-sm font-medium mt-1">
                        {item.specialization}
                      </p>
                    )}
                  </div>
                  <button
                    onClick={(e) => {
                      e.stopPropagation();
                      handleCallDoctor(item);
                    }}
                    disabled={isLoading}
                    className={`flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-sm transition-all ${
                      isLoading
                        ? "bg-gray-100 text-gray-400 cursor-not-allowed"
                        : "bg-blue-50 text-blue-600 hover:bg-blue-100 hover:scale-105"
                    }`}
                  >
                    {isLoading ? (
                      <>
                        <div className="w-3 h-3 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                        Calling...
                      </>
                    ) : (
                      <>
                        <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                          <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                        </svg>
                        Call Now
                      </>
                    )}
                  </button>
                </div>

                {/* Stats */}
                <div className="flex items-center gap-4 mb-3">
                  {item.rating && (
                    <div className="flex items-center gap-1">
                      <div className="flex items-center gap-1 bg-yellow-50 px-2 py-1 rounded-full">
                        <svg className="w-3 h-3 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <span className="text-xs font-semibold text-gray-700">{item.rating}</span>
                      </div>
                    </div>
                  )}
                  {item.distance && (
                    <div className="flex items-center gap-1 bg-blue-50 px-2 py-1 rounded-full">
                      <svg className="w-3 h-3 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
                      </svg>
                      <span className="text-xs font-semibold text-gray-700">{item.distance.toFixed(1)} km</span>
                    </div>
                  )}
                  {item.experience && (
                    <div className="flex items-center gap-1 bg-green-50 px-2 py-1 rounded-full">
                      <svg className="w-3 h-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <span className="text-xs font-semibold text-gray-700">{item.experience}+ years</span>
                    </div>
                  )}
                </div>

                {/* Actions */}
                <div className="flex items-center gap-2">
                  <button
                    onClick={(e) => {
                      e.stopPropagation();
                      handleViewProfile(item);
                    }}
                    className="flex items-center gap-2 px-3 py-2 text-gray-600 hover:text-gray-900 font-medium text-sm transition-colors"
                  >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    View Profile
                  </button>
                  <span className="text-gray-300">•</span>
                  <button
                    onClick={(e) => {
                      e.stopPropagation();
                      handleShareDoctor(item);
                    }}
                    className="flex items-center gap-2 px-3 py-2 text-gray-600 hover:text-gray-900 font-medium text-sm transition-colors"
                  >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        <div className="flex flex-col items-center justify-center py-16 px-4 text-center">
          <div className="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-6">
            <svg className="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h3 className="text-xl font-semibold text-gray-900 mb-2">No doctors available</h3>
          <p className="text-gray-500 max-w-sm">
            {searchTerm 
              ? "No doctors match your search criteria. Try adjusting your filters."
              : "All our veterinarians are currently busy. Please try again in a few minutes."
            }
          </p>
          {searchTerm && (
            <button
              onClick={() => setSearchTerm("")}
              className="mt-4 px-4 py-2 text-blue-600 font-medium hover:text-blue-700 transition-colors"
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

      const profileAvatarError = imageLoadErrors[`profile-avatar-${profileDoctor.id}`];
      const isCallingThisDoctor = selectedDoctor === profileDoctor.id && loading;

      return (
        <div className={`fixed inset-0 z-50 ${showProfile ? "animate-in fade-in" : "animate-out fade-out"}`}>
          <div className="flex flex-col h-full bg-white">
            {/* Header */}
            <div className="flex items-center justify-between p-6 border-b border-gray-100 bg-white">
              <button
                onClick={handleCloseProfile}
                className="p-2 hover:bg-gray-50 rounded-xl transition-colors"
              >
                <svg className="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                </svg>
              </button>
              <h2 className="text-xl font-bold text-gray-900">Doctor Profile</h2>
              <button
                onClick={() => handleShareDoctor(profileDoctor)}
                className="p-2 hover:bg-gray-50 rounded-xl transition-colors"
              >
                <svg className="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                </svg>
              </button>
            </div>

            <div className="flex-1 overflow-y-auto">
              {/* Hero Section */}
              <div className="bg-gradient-to-br from-blue-500 via-blue-600 to-purple-700 px-6 py-8 text-white">
                <div className="flex flex-col items-center text-center">
                  <div className="relative mb-6">
                    {profileDoctor.profile_image && !profileAvatarError ? (
                      <img
                        src={profileDoctor.profile_image}
                        alt={`Dr. ${profileDoctor.business_status || profileDoctor.name}`}
                        className="w-28 h-28 rounded-2xl border-4 border-white border-opacity-20 object-cover shadow-xl"
                        onError={() => handleImageError(`profile-avatar-${profileDoctor.id}`)}
                      />
                    ) : (
                      <div className="w-28 h-28 rounded-2xl bg-white bg-opacity-20 border-4 border-white border-opacity-20 flex items-center justify-center shadow-xl">
                        <span className="text-white font-bold text-3xl">
                          {profileDoctor.business_status?.charAt(0) || profileDoctor.name?.charAt(0) || "D"}
                        </span>
                      </div>
                    )}
                    <div className="absolute -bottom-2 left-1/2 transform -translate-x-1/2 flex items-center bg-white px-4 py-2 rounded-full shadow-lg gap-2">
                      <div className="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                      <span className="text-green-600 font-semibold text-sm">Available Now</span>
                    </div>
                  </div>

                  <h1 className="text-2xl font-bold mb-2">
                    Dr. {profileDoctor.business_status || profileDoctor.name || "Veterinarian"}
                  </h1>
                  <p className="text-blue-100 text-lg mb-4">
                    {profileDoctor.clinic_name || "Veterinary Clinic"}
                  </p>
                  {profileDoctor.specialization && (
                    <p className="text-blue-200 font-medium mb-6">
                      {profileDoctor.specialization}
                    </p>
                  )}

                  {/* Stats */}
                  <div className="flex gap-6 flex-wrap justify-center">
                    {profileDoctor.rating && (
                      <div className="flex flex-col items-center">
                        <span className="text-2xl font-bold">{profileDoctor.rating}</span>
                        <span className="text-blue-200 text-sm">Rating</span>
                      </div>
                    )}
                    {profileDoctor.experience && (
                      <div className="flex flex-col items-center">
                        <span className="text-2xl font-bold">{profileDoctor.experience}+</span>
                        <span className="text-blue-200 text-sm">Years Exp</span>
                      </div>
                    )}
                    {profileDoctor.distance && (
                      <div className="flex flex-col items-center">
                        <span className="text-2xl font-bold">{profileDoctor.distance.toFixed(1)}</span>
                        <span className="text-blue-200 text-sm">Km Away</span>
                      </div>
                    )}
                  </div>
                </div>
              </div>

              {/* Details */}
              <div className="p-6 space-y-6">
                {/* Contact Info */}
                <div className="bg-gray-50 rounded-2xl p-6">
                  <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-3">
                    <div className="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                      <svg className="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                      </svg>
                    </div>
                    Contact Information
                  </h3>
                  <div className="space-y-3">
                    <div className="flex justify-between items-center py-2 border-b border-gray-100">
                      <span className="text-gray-600 font-medium">Phone:</span>
                      <span className="text-gray-900 font-semibold">
                        {profileDoctor.mobile || "Not available"}
                      </span>
                    </div>
                    <div className="flex justify-between items-center py-2">
                      <span className="text-gray-600 font-medium">Email:</span>
                      <span className="text-gray-900 font-semibold truncate max-w-[200px]">
                        {profileDoctor.email || "Not available"}
                      </span>
                    </div>
                  </div>
                </div>

                {/* Consultation Fee */}
                <div className="bg-gradient-to-r from-green-50 to-emerald-50 rounded-2xl p-6 border border-green-100">
                  <div className="flex items-center justify-between">
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900 mb-1">Video Consultation</h3>
                      <p className="text-gray-600">Instant connect • Secure payment</p>
                    </div>
                    <div className="text-right">
                      <p className="text-2xl font-bold text-green-600">
                        ₹{profileDoctor.chat_price || "500"}
                      </p>
                      <p className="text-gray-500 text-sm">per session</p>
                    </div>
                  </div>
                </div>

                {/* Bio */}
                {profileDoctor.bio && profileDoctor.bio !== "null" && profileDoctor.bio.trim() !== "" && (
                  <div className="bg-gray-50 rounded-2xl p-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-3">About Dr. {profileDoctor.business_status || profileDoctor.name}</h3>
                    <p className="text-gray-700 leading-relaxed">{profileDoctor.bio}</p>
                  </div>
                )}
              </div>
            </div>

            {/* Fixed Call Button */}
            <div className="sticky bottom-0 left-0 right-0 p-6 bg-white border-t border-gray-100">
              {isCallingThisDoctor ? (
                <div className="flex items-center justify-center gap-3 py-4 bg-gray-400 rounded-xl">
                  <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                  <span className="text-white font-bold text-lg">Connecting...</span>
                </div>
              ) : (
                <button
                  onClick={() => {
                    handleCloseProfile();
                    setTimeout(() => {
                      handleCallDoctor(profileDoctor);
                    }, 300);
                  }}
                  className="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white py-4 rounded-xl font-bold text-lg flex items-center justify-center gap-3 transition-all transform hover:scale-[1.02] shadow-lg shadow-blue-500/25"
                >
                  <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
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
        {/* Main Modal */}
        <div className="fixed inset-0 z-40 flex items-end justify-center bg-black bg-opacity-50 animate-in fade-in">
          <div className="bg-white rounded-t-3xl w-full max-w-4xl max-h-[90vh] overflow-hidden animate-in slide-in-from-bottom">
            {/* Header */}
            <div className="relative p-8 border-b border-gray-100 bg-gradient-to-r from-white to-gray-50">
              <div className="flex items-center justify-between mb-6">
                <div>
                  <h2 className="text-3xl font-bold text-gray-900">Available Veterinarians</h2>
                  <p className="text-gray-600 mt-2 text-lg">{doctorCountText}</p>
                </div>
                <button
                  onClick={onClose}
                  className="p-3 hover:bg-gray-100 rounded-xl transition-colors"
                >
                  <svg className="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              {/* Search and Filter */}
              <div className="flex gap-4">
                <div className="flex-1 relative">
                  <svg className="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                  <input
                    type="text"
                    placeholder="Search by name, clinic, or specialization..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="w-full pl-12 pr-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                  />
                </div>
                <select
                  value={sortBy}
                  onChange={(e) => setSortBy(e.target.value)}
                  className="px-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                >
                  <option value="distance">Nearest First</option>
                  <option value="rating">Highest Rated</option>
                  <option value="experience">Most Experienced</option>
                </select>
              </div>
            </div>

            {/* Doctors List */}
            <div className="p-8 overflow-y-auto max-h-[calc(90vh-200px)]">
              {filteredDoctors.length > 0 ? (
                <div className="space-y-4">
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