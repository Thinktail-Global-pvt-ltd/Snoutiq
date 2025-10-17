import React, { useCallback, useEffect, useMemo, useState } from "react";
// import { Share } from "./share-utils"; 

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

// Font sizes and spacing constants
const FONT_SIZES = {
  tiny: "text-xs",
  small: "text-sm",
  medium: "text-base",
  large: "text-lg",
  xlarge: "text-xl",
  xxlarge: "text-2xl",
  xxxlarge: "text-3xl",
};

const SPACING = {
  xs: "1",
  sm: "2",
  md: "3",
  lg: "4",
  xl: "5",
  xxl: "6",
  xxxl: "8",
};

const LiveDoctorSelectionModal = React.memo(
  ({ visible, onClose, liveDoctors, onCallDoctor, loading }) => {
    const { moderateScale } = useResponsive();
    const [selectedDoctor, setSelectedDoctor] = useState(null);
    const [showProfile, setShowProfile] = useState(false);
    const [profileDoctor, setProfileDoctor] = useState(null);
    const [imageLoadErrors, setImageLoadErrors] = useState({});
    console.log(visible, onClose, liveDoctors, onCallDoctor, loading ,"ankit");
    

    // Cleanup selected doctor when modal closes or call completes
    useEffect(() => {
      if (!visible || !loading) {
        const timer = setTimeout(() => {
          setSelectedDoctor(null);
        }, 500);
        return () => clearTimeout(timer);
      }
    }, [visible, loading]);

    // Reset state when modal closes
    useEffect(() => {
      if (!visible) {
        setShowProfile(false);
        setProfileDoctor(null);
        setImageLoadErrors({});
      }
    }, [visible]);

    // Memoized doctor count
    const doctorCountText = useMemo(() => {
      const count = liveDoctors?.length || 0;
      return `${count} doctor${count !== 1 ? "s" : ""} available now`;
    }, [liveDoctors?.length]);

    // Memoized handle functions with useCallback
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
        const message = `Check out Dr. ${
          doctor.business_status || doctor.name
        }\n${doctor.clinic_name || "Veterinary Clinic"}\n\n${shareUrl}`;

        // Web share API
        if (navigator.share) {
          await navigator.share({
            title: `Dr. ${doctor.business_status || doctor.name}`,
            text: message,
            url: shareUrl,
          });
        } else {
          // Fallback: copy to clipboard
          await navigator.clipboard.writeText(message);
          alert("Doctor information copied to clipboard!");
        }
      } catch (error) {
        console.error("Error sharing:", error);
      }
    }, []);

    const handleImageError = useCallback((imageId) => {
      setImageLoadErrors((prev) => {
        if (prev[imageId]) return prev;
        return { ...prev, [imageId]: true };
      });
    }, []);

    // Memoized doctor item renderer
    const renderDoctorItem = useCallback(
      (item) => {
        const isLoading = selectedDoctor === item.id && loading;
        const avatarError = imageLoadErrors[`avatar-${item.id}`];

        return (
          <div
            className="bg-white rounded-lg shadow-lg mb-3 overflow-hidden cursor-pointer transition-transform hover:scale-[1.02]"
            onClick={() => handleViewProfile(item)}
          >
            <div className="bg-gradient-to-r from-white to-gray-50 p-4 flex items-center">
              {/* Doctor Avatar */}
              <div className="relative mr-4">
                {item.profile_image && !avatarError ? (
                  <img
                    src={item.profile_image}
                    alt={`Dr. ${item.business_status || item.name}`}
                    className="w-16 h-16 rounded-full object-cover"
                    onError={() => handleImageError(`avatar-${item.id}`)}
                  />
                ) : (
                  <div className="w-16 h-16 rounded-full bg-purple-600 flex items-center justify-center">
                    <span className="text-white font-bold text-xl">
                      {item.business_status?.substring(0, 2).toUpperCase() || "DR"}
                    </span>
                  </div>
                )}
                {/* Live Indicator */}
                <div className="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-white flex items-center justify-center border-2 border-white">
                  <div className="w-3 h-3 rounded-full bg-blue-500"></div>
                </div>
              </div>

              {/* Doctor Info */}
              <div className="flex-1">
                <h3 className="text-lg font-bold text-gray-900 truncate">
                  Dr. {item.business_status || item.name || "Veterinarian"}
                </h3>
                <p className="text-gray-600 text-base mb-2 truncate">
                  {item.clinic_name || item.name || "Veterinary Clinic"}
                </p>

                {/* Distance & Rating */}
                <div className="flex gap-4 mb-3">
                  <div className="flex items-center gap-1">
                    <svg className="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
                    </svg>
                    <span className="text-sm text-gray-600 font-medium">
                      {item.distance ? `${item.distance.toFixed(1)} km` : "Nearby"}
                    </span>
                  </div>
                  {item.rating && (
                    <div className="flex items-center gap-1">
                      <svg className="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                      </svg>
                      <span className="text-sm text-gray-600 font-medium">{item.rating} ★</span>
                    </div>
                  )}
                </div>

                {/* Quick Actions */}
                <div className="flex gap-2">
                  <button
                    className="flex items-center gap-1 px-3 py-1 bg-gray-100 rounded-lg text-purple-700 font-semibold text-sm hover:bg-gray-200 transition-colors"
                    onClick={(e) => {
                      e.stopPropagation();
                      handleViewProfile(item);
                    }}
                    disabled={isLoading}
                  >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    View Profile
                  </button>
                </div>
              </div>

              {/* Quick Call Button */}
              <button
                className={`w-12 h-12 rounded-full flex items-center justify-center ml-2 transition-colors ${
                  isLoading ? "bg-gray-400" : "bg-green-100 hover:bg-green-200"
                }`}
                onClick={(e) => {
                  e.stopPropagation();
                  handleCallDoctor(item);
                }}
                disabled={isLoading}
              >
                {isLoading ? (
                  <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                ) : (
                  <svg className="w-6 h-6 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                  </svg>
                )}
              </button>
            </div>
          </div>
        );
      },
      [
        handleViewProfile,
        handleCallDoctor,
        selectedDoctor,
        loading,
        imageLoadErrors,
        handleImageError,
      ]
    );

    // Memoized empty state
    const emptyState = useMemo(
      () => (
        <div className="flex flex-col items-center justify-center py-16">
          <svg className="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p className="text-xl font-semibold text-gray-600 mb-2">No doctors available</p>
          <p className="text-gray-500">Please try again in a few moments</p>
        </div>
      ),
      []
    );

    // Doctor Profile Modal Component
    const DoctorProfileView = useMemo(() => {
      if (!profileDoctor) return null;

      const profileAvatarError = imageLoadErrors[`profile-avatar-${profileDoctor.id}`];
      const isCallingThisDoctor = selectedDoctor === profileDoctor.id && loading;

      return (
        <div className={`fixed inset-0 z-50 ${showProfile ? "block" : "hidden"}`}>
          <div className="flex flex-col h-full bg-gray-50">
            {/* Header */}
            <div className="flex justify-between items-center p-4 bg-white border-b border-gray-200">
              <button
                onClick={handleCloseProfile}
                className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
              >
                <svg className="w-6 h-6 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                </svg>
              </button>
              <h2 className="text-xl font-bold text-gray-900">Doctor Profile</h2>
              <button
                onClick={() => handleShareDoctor(profileDoctor)}
                className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
              >
                <svg className="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                </svg>
              </button>
            </div>

            <div className="flex-1 overflow-y-auto">
              {/* Doctor Card */}
              <div className="bg-gradient-to-r from-blue-500 to-purple-600 p-8 text-white">
                <div className="flex flex-col items-center">
                  <div className="relative mb-4">
                    {profileDoctor.profile_image && !profileAvatarError ? (
                      <img
                        src={profileDoctor.profile_image}
                        alt={`Dr. ${profileDoctor.business_status || profileDoctor.name}`}
                        className="w-24 h-24 rounded-full border-4 border-white object-cover"
                        onError={() => handleImageError(`profile-avatar-${profileDoctor.id}`)}
                      />
                    ) : (
                      <div className="w-24 h-24 rounded-full bg-white bg-opacity-30 border-4 border-white flex items-center justify-center">
                        <span className="text-white font-bold text-3xl">
                          {profileDoctor.business_status?.substring(0, 2).toUpperCase() || "DR"}
                        </span>
                      </div>
                    )}
                    <div className="absolute -bottom-2 left-1/2 transform -translate-x-1/2 flex items-center bg-white px-3 py-1 rounded-full gap-2">
                      <div className="w-2 h-2 rounded-full bg-green-500"></div>
                      <span className="text-green-600 font-semibold text-sm">Live Now</span>
                    </div>
                  </div>

                  <h1 className="text-3xl font-bold text-center mb-2">
                    Dr. {profileDoctor.business_status || profileDoctor.name || "Veterinarian"}
                  </h1>
                  <p className="text-xl text-white text-opacity-90 text-center mb-4">
                    {profileDoctor.clinic_name || profileDoctor.name || "Veterinary Clinic"}
                  </p>

                  {/* Stats Row */}
                  <div className="flex gap-8 flex-wrap justify-center">
                    {profileDoctor.rating && (
                      <div className="flex items-center gap-2">
                        <svg className="w-5 h-5 text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <span className="font-semibold">{profileDoctor.rating}</span>
                      </div>
                    )}
                    {profileDoctor.user_ratings_total && (
                      <div className="flex items-center gap-2">
                        <svg className="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                          <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                        </svg>
                        <span className="font-semibold">{profileDoctor.user_ratings_total} reviews</span>
                      </div>
                    )}
                    {profileDoctor.distance && (
                      <div className="flex items-center gap-2">
                        <svg className="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
                        </svg>
                        <span className="font-semibold">{profileDoctor.distance.toFixed(1)} km</span>
                      </div>
                    )}
                  </div>
                </div>
              </div>

              {/* Details Section */}
              <div className="p-4 space-y-4">
                {/* Contact Info */}
                <div className="bg-white rounded-lg p-4 shadow-sm">
                  <div className="flex items-center gap-3 mb-3">
                    <svg className="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <h3 className="text-lg font-semibold text-gray-900">Contact Information</h3>
                  </div>
                  <div className="space-y-2">
                    <div className="flex justify-between items-center">
                      <span className="text-gray-600 font-medium">Phone:</span>
                      <span className="text-gray-900 font-semibold">
                        {profileDoctor.mobile || "Not available"}
                      </span>
                    </div>
                    <div className="flex justify-between items-center">
                      <span className="text-gray-600 font-medium">Email:</span>
                      <span className="text-gray-900 font-semibold truncate max-w-[200px]">
                        {profileDoctor.email || "Not available"}
                      </span>
                    </div>
                  </div>
                </div>

                {/* Address */}
                <div className="bg-white rounded-lg p-4 shadow-sm">
                  <div className="flex items-center gap-3 mb-3">
                    <svg className="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <h3 className="text-lg font-semibold text-gray-900">Address</h3>
                  </div>
                  <div>
                    <p className="text-gray-700 leading-relaxed">
                      {profileDoctor.address || profileDoctor.formatted_address || "Address not available"}
                    </p>
                    {(profileDoctor.city || profileDoctor.pincode) && (
                      <p className="text-gray-600 mt-1">
                        {profileDoctor.city}
                        {profileDoctor.city && profileDoctor.pincode ? ", " : ""}
                        {profileDoctor.pincode}
                      </p>
                    )}
                  </div>
                </div>

                {/* Consultation Fee */}
                <div className="bg-white rounded-lg p-4 shadow-sm">
                  <div className="flex items-center gap-3 mb-3">
                    <svg className="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                    </svg>
                    <h3 className="text-lg font-semibold text-gray-900">Consultation Fee</h3>
                  </div>
                  <p className="text-2xl font-bold text-green-600">
                    ₹{profileDoctor.chat_price || "500"}/session
                  </p>
                </div>

                {/* Bio */}
                {profileDoctor.bio && profileDoctor.bio !== "null" && profileDoctor.bio.trim() !== "" && (
                  <div className="bg-white rounded-lg p-4 shadow-sm">
                    <div className="flex items-center gap-3 mb-3">
                      <svg className="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <h3 className="text-lg font-semibold text-gray-900">About</h3>
                    </div>
                    <p className="text-gray-700 leading-relaxed">{profileDoctor.bio}</p>
                  </div>
                )}

                {/* Clinic Photos */}
                {profileDoctor.photos &&
                  (() => {
                    try {
                      const photos = JSON.parse(profileDoctor.photos);

                      const getPhotoUrl = (photoReference) => {
                        const match = photoReference.match(/1s([A-Za-z0-9_-]+)/);
                        if (match && match[1]) {
                          const actualPhotoRef = match[1];
                          return `https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=${actualPhotoRef}&key=AIzaSyDSiWYPatUTt_CCokGa9ZW1rsQhP5THCpA`;
                        }

                        if (photoReference.length < 100) {
                          return `https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=${photoReference}&key=AIzaSyDSiWYPatUTt_CCokGa9ZW1rsQhP5THCpA`;
                        }

                        return null;
                      };

                      return (
                        photos &&
                        photos.length > 0 && (
                          <div className="bg-white rounded-lg p-4 shadow-sm">
                            <div className="flex items-center gap-3 mb-3">
                              <svg className="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                              </svg>
                              <h3 className="text-lg font-semibold text-gray-900">Clinic Photos</h3>
                            </div>
                            <div className="flex gap-3 overflow-x-auto pb-2">
                              {photos.slice(0, 5).map((photo, index) => {
                                const photoUrl = getPhotoUrl(photo.photo_reference);
                                return photoUrl ? (
                                  <div key={index} className="flex-shrink-0">
                                    <img
                                      src={photoUrl}
                                      alt={`Clinic photo ${index + 1}`}
                                      className="w-32 h-32 rounded-lg object-cover bg-gray-100"
                                      onError={(e) => {
                                        console.log("Image load error:", e);
                                        console.log("Failed URL:", photoUrl);
                                      }}
                                    />
                                  </div>
                                ) : null;
                              })}
                            </div>
                          </div>
                        )
                      );
                    } catch (e) {
                      console.log("Photo parse error:", e);
                      return null;
                    }
                  })()}
              </div>
            </div>

            {/* Fixed Call Button */}
            <div className="sticky bottom-0 left-0 right-0 p-4 bg-white border-t border-gray-200 shadow-lg">
              {isCallingThisDoctor ? (
                <div className="flex items-center justify-center gap-3 py-4 bg-gray-400 rounded-lg">
                  <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                  <span className="text-white font-bold text-lg">Calling...</span>
                </div>
              ) : (
                <button
                  onClick={() => {
                    handleCloseProfile();
                    setTimeout(() => {
                      handleCallDoctor(profileDoctor);
                    }, 300);
                  }}
                  className="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white py-4 rounded-lg font-bold text-lg flex items-center justify-center gap-3 transition-all transform hover:scale-[1.02]"
                >
                  <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                  </svg>
                  Start Video Call
                </button>
              )}
            </div>
          </div>
        </div>
      );
    }, [
      profileDoctor,
      showProfile,
      handleCloseProfile,
      handleShareDoctor,
      handleCallDoctor,
      selectedDoctor,
      loading,
      imageLoadErrors,
      handleImageError,
    ]);

    if (!visible) return null;

    return (
      <>
        {/* Main Modal */}
        <div className="fixed inset-0 z-40 flex items-end justify-center bg-black bg-opacity-50">
          <div className="bg-white rounded-t-2xl w-full max-w-2xl max-h-[85vh] overflow-hidden">
            {/* Header */}
            <div className="flex justify-between items-center p-6 border-b border-gray-200">
              <div>
                <h2 className="text-2xl font-bold text-gray-900">Live Veterinarians</h2>
                <p className="text-gray-600 mt-1">{doctorCountText}</p>
              </div>
              <button
                onClick={onClose}
                className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
              >
                <svg className="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            {/* Doctors List */}
            <div className="p-6 overflow-y-auto max-h-[calc(85vh-80px)]">
              {liveDoctors?.length > 0 ? (
                <div className="space-y-4">
                  {liveDoctors.map((doctor) => (
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