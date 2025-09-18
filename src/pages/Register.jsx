import React, { useState, useContext, useEffect } from "react";
import { toast } from "react-hot-toast";
import { Link, useNavigate, Navigate } from "react-router-dom";
import Card from "../components/Card";
import Header from "../components/Header";
import logo from "../assets/images/logo.webp";
import { AuthContext } from "../auth/AuthContext";
import { useRegistration } from "../auth/RegistrationContext";
import { GoogleOAuthProvider, GoogleLogin } from "@react-oauth/google";

const RegisterBasicDetails = () => {
  const navigate = useNavigate();
  const { user } = useContext(AuthContext);
  const {
    formData,
    errors,
    touched,
    updateFormData,
    updateMultipleFields,
    setFieldTouched,
    validateBasicDetails,
    locationAllowed,
    setLocationAllowed,
    setCoords,
    coords,
  } = useRegistration();

  const [userType, setUserType] = useState("pet_owner");
  const [loading, setLoading] = useState(false);
  const [locationStatus, setLocationStatus] = useState("checking");

  // Redirect if already logged in
  if (user) {
    if (user.role === "vet") {
      return <Navigate to="/user-dashboard/bookings" replace />;
    } else {
      return <Navigate to="/dashboard" replace />;
    }
  }

  useEffect(() => {
    if (userType === "veterinarian") {
      navigate("/vet-register");
    }
  }, [userType, navigate]);

  // Enhanced location permission check
  useEffect(() => {
    const initializeLocation = async () => {
      // console.log("Initializing location...");

      // First check if geolocation is supported
      if (!navigator.geolocation) {
        // console.log("Geolocation not supported");
        setLocationStatus("denied");
        setLocationAllowed(false);
        toast.error("Location services not supported in this browser");
        return;
      }

      // Try to check permissions API if available
      if (navigator.permissions) {
        try {
          const result = await navigator.permissions.query({
            name: "geolocation",
          });
          // console.log("Permission status:", result.state);

          if (result.state === "granted") {
            setLocationStatus("granted");
            requestLocation();
          } else if (result.state === "denied") {
            setLocationStatus("denied");
            setLocationAllowed(false);
            toast.error(
              "Location access denied. Please enable in browser settings."
            );
          } else {
            // State is 'prompt' - we'll request when needed
            setLocationStatus("prompt");
          }

          // Listen for permission changes
          result.addEventListener("change", () => {
            // console.log("Permission changed:", result.state);
            if (result.state === "granted") {
              requestLocation();
            } else if (result.state === "denied") {
              setLocationStatus("denied");
              setLocationAllowed(false);
            }
          });
        } catch (error) {
          console.error("Error checking permissions:", error);
          setLocationStatus("prompt");
        }
      } else {
        // Permissions API not supported - try direct request
        console.log("Permissions API not supported, will prompt when needed");
        setLocationStatus("prompt");
      }
    };

    initializeLocation();
  }, []);

  const requestLocation = () => {
    // console.log("Requesting location...");
    setLocationStatus("checking");

    const options = {
      enableHighAccuracy: true,
      timeout: 15000,
      maximumAge: 300000,
    };

    navigator.geolocation.getCurrentPosition(
      (position) => {
        // console.log("Location success:", position.coords);
        setLocationStatus("granted");
        setLocationAllowed(true);
        setCoords({
          lat: position.coords.latitude,
          lng: position.coords.longitude,
        });
        toast.success("Location access granted!");
      },
      (error) => {
        console.error("Location error:", error);
        setLocationStatus("denied");
        setLocationAllowed(false);

        let errorMessage = "Location access denied.";
        switch (error.code) {
          case error.PERMISSION_DENIED:
            errorMessage =
              "Location access denied. Please allow location access and refresh the page.";
            break;
          case error.POSITION_UNAVAILABLE:
            errorMessage =
              "Location information unavailable. Please try again.";
            break;
          case error.TIMEOUT:
            errorMessage = "Location request timed out. Please try again.";
            break;
        }
        toast.error(errorMessage);
      },
      options
    );
  };

  // Manual location request function
  const handleLocationRequest = () => {
    if (locationStatus === "denied") {
      toast.error(
        "Please enable location in your browser settings and refresh the page."
      );
      return;
    }
    requestLocation();
  };

  //   const handleGoogleSuccess = async (credentialResponse) => {
  //     try {
  //       setLoading(true);

  //       // Decode Google JWT
  //       const base64Url = credentialResponse.credential.split(".")[1];
  //       const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
  //       const jsonPayload = decodeURIComponent(
  //         atob(base64)
  //           .split("")
  //           .map(function (c) {
  //             return "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2);
  //           })
  //           .join("")
  //       );

  //       const googleData = JSON.parse(jsonPayload);
  //        console.log("Google User Data:", googleData);

  //     // Send data to backend
  //     await sendGoogleDataToBackend(googleData);

  //       // Update form data with Google info
  //       updateMultipleFields({
  //         fullName: googleData.name || "",
  //         email: googleData.email || "",
  //         google_token: googleData.sub,
  //       });

  //       toast.success("Google login successful! Continue with pet details.");

  //       // Check location before proceeding
  //       if (locationStatus === 'prompt' || locationStatus === 'checking') {
  //         requestLocation();
  //       }

  //       navigate("/register-pet-details");
  //     } catch (error) {
  //       console.error("Google login failed:", error);
  //       toast.error("Google login failed. Please try again.");
  //     } finally {
  //       setLoading(false);
  //     }
  //   };

  // const sendGoogleDataToBackend = async (userData) => {
  //   try {
  //     const payload = {
  //       fullName: userData.name,
  //       email: userData.email,
  //       google_id: userData.sub,
  //       firstName: userData.given_name,
  //       lastName: userData.family_name,
  //       picture: userData.picture,
  //       locale: userData.locale,
  //     };

  //     const res = await fetch("https://snoutiq.com/backend/auth/initial-register", {
  //       method: "POST",
  //       headers: { "Content-Type": "application/json" },
  //       body: JSON.stringify(payload),
  //     });

  //     const data = await res.json();
  //     console.log("Backend response:", data);
  //   } catch (error) {
  //     console.error("Error sending Google data to backend:", error);
  //     toast.error("Failed to send data to server. Try again.");
  //   }
  // };

  // const handleGoogleSuccess = async (credentialResponse) => {
  //   try {
  //     setLoading(true);

  //     // Decode Google JWT
  //     const base64Url = credentialResponse.credential.split(".")[1];
  //     const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
  //     const jsonPayload = decodeURIComponent(
  //       atob(base64)
  //         .split("")
  //         .map((c) => "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2))
  //         .join("")
  //     );

  //     const googleData = JSON.parse(jsonPayload);
  //     // console.log("Google User Data:", googleData);

  //     // Call backend
  //     const res = await fetch(
  //       "https://snoutiq.com/backend/api/auth/initial-register",
  //       {
  //         method: "POST",
  //         headers: { "Content-Type": "application/json" },
  //         body: JSON.stringify({
  //           fullName: googleData.name,
  //           email: googleData.email,
  //           google_token: googleData.sub,
  //         }),
  //       }
  //     );

  //     const data = await res.json();
  //     // console.log("Backend response:", data);
  //     // console.log("Backend response:", res);

  //     // Check if user already exists
  //     if (data.status === "error") {
  //       toast.error(data.message);
  //       return;
  //     }
  //     const userId = data.user_id;

  //     // If user doesn't exist, update form data
  //     updateMultipleFields({
  //       fullName: googleData.name || "",
  //       email: googleData.email || "",
  //       google_token: googleData.sub,
  //       user: userId,
  //     });

  //     toast.success("Google login successful! Continue with pet details.");

  //     // Request location if needed
  //     if (locationStatus === "prompt" || locationStatus === "checking") {
  //       requestLocation();
  //     }

  //     // navigate("/register-pet-details");
  //     navigate("/register-pet-details", { state: { userId } });
  //   } catch (error) {
  //     console.error("Google login failed:", error);
  //     toast.error("Google login failed. Please try again.");
  //   } finally {
  //     setLoading(false);
  //   }
  // };
  const [googleMessage, setGoogleMessage] = useState(""); // 👈 new state

  // const handleGoogleSuccess = async (credentialResponse) => {
  //   try {
  //     setLoading(true);
  //     setGoogleMessage(""); // reset old messages

  //     // Decode Google JWT
  //     const base64Url = credentialResponse.credential.split(".")[1];
  //     const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
  //     const jsonPayload = decodeURIComponent(
  //       atob(base64)
  //         .split("")
  //         .map((c) => "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2))
  //         .join("")
  //     );

  //     const googleData = JSON.parse(jsonPayload);

  //     // Call backend
  //     const res = await fetch(
  //       "https://snoutiq.com/backend/api/auth/initial-register",
  //       {
  //         method: "POST",
  //         headers: { "Content-Type": "application/json" },
  //         body: JSON.stringify({
  //           fullName: googleData.name,
  //           email: googleData.email,
  //           google_token: googleData.sub,
  //         }),
  //       }
  //     );

  //     const data = await res.json();
  //     console.log("Backend response:", data);

  //     // 👇 Show message below button instead of only toast
  //     if (data.status === "exists") {
  //       setGoogleMessage("⚠️ User already exists. Please login.");
  //       return;
  //     }

  //     if (data.status === "error") {
  //       setGoogleMessage(data.message || "Something went wrong.");
  //       return;
  //     }

  //     // ✅ New User → proceed
  //     const userId = data.user_id;

  //     updateMultipleFields({
  //       fullName: googleData.name || "",
  //       email: googleData.email || "",
  //       google_token: googleData.sub,
  //       user: userId,
  //     });

  //     setGoogleMessage(
  //       "✅ Google login successful! Continue with pet details."
  //     );

  //     if (locationStatus === "prompt" || locationStatus === "checking") {
  //       requestLocation();
  //     }

  //     navigate("/register-pet-details", { state: { userId } });
  //   } catch (error) {
  //     console.error("Google login failed:", error);
  //     setGoogleMessage("❌ Google login failed. Please try again.");
  //   } finally {
  //     setLoading(false);
  //   }
  // };

  const handleGoogleSuccess = async (credentialResponse) => {
  try {
    setLoading(true);
    setGoogleMessage(""); // reset

    // ⛔ Agar location allowed nahi hai to API mat call karo
    if (locationStatus !== "granted" || !coords.lat || !coords.lng) {
      setGoogleMessage("❌ Please allow location access before Google login.");
      setLoading(false);
      return;
    }

    // ✅ Location mil gayi → ab decode JWT
    const base64Url = credentialResponse.credential.split(".")[1];
    const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
    const jsonPayload = decodeURIComponent(
      atob(base64)
        .split("")
        .map((c) => "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2))
        .join("")
    );

    const googleData = JSON.parse(jsonPayload);

    // ✅ Backend call
    const res = await fetch(
      "https://snoutiq.com/backend/api/auth/initial-register",
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          fullName: googleData.name,
          email: googleData.email,
          google_token: googleData.sub,
          location: coords, // 👈 lat/lng bhi bhejo
        }),
      }
    );

    const data = await res.json();

    if (data.status === "exists") {
      setGoogleMessage("⚠️ User already exists. Please login.");
      return;
    }

    if (data.status === "error") {
      setGoogleMessage(data.message || "Something went wrong.");
      return;
    }

    const userId = data.user_id;

    updateMultipleFields({
      fullName: googleData.name || "",
      email: googleData.email || "",
      google_token: googleData.sub,
      user: userId,
    });

    setGoogleMessage("✅ Google login successful! Continue with pet details.");
    navigate("/register-pet-details", { state: { userId } });
  } catch (error) {
    console.error("Google login failed:", error);
    setGoogleMessage("❌ Google login failed. Please try again.");
  } finally {
    setLoading(false);
  }
};

  const handleGoogleError = () => {
    toast.error("Google login failed. Please try again.");
  };

  const handleInputChange = (field, value) => {
    updateFormData(field, value);
  };

  const handleBlur = (field) => {
    setFieldTouched(field);
  };

  const handleNext = async () => {
    if (!validateBasicDetails()) {
      // Mark fields as touched to show errors
      setFieldTouched("fullName");
      setFieldTouched("email");

      // Show first error
      const firstError = errors.fullName || errors.email;
      if (firstError) {
        toast.error(firstError);
      }
      return;
    }

    // Check and request location if needed
    if (locationStatus === "prompt" || locationStatus === "checking") {
      toast.info("Please allow location access to continue...");
      requestLocation();

      // Wait for location response
      setTimeout(() => {
        if (coords.lat && coords.lng) {
          navigate("/register-pet-details");
        }
      }, 2000);
    } else if (locationStatus === "granted" && coords.lat && coords.lng) {
      navigate("/register-pet-details");
    } else {
      toast.error(
        "Location access is required to proceed. Please allow location access."
      );
    }
  };

  // Location status component
  const LocationStatus = () => {
    switch (locationStatus) {
      case "checking":
        return (
          <div className="flex items-center justify-center text-blue-600 text-sm mb-4 p-2 bg-blue-50 rounded-lg">
            <svg
              className="animate-spin w-4 h-4 mr-2"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                className="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                strokeWidth="4"
              ></circle>
              <path
                className="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              ></path>
            </svg>
            Requesting location access...
          </div>
        );
      case "granted":
        return (
          <div className="flex items-center justify-center text-green-600 text-sm mb-4 p-2 bg-green-50 rounded-lg">
            <svg
              className="w-4 h-4 mr-2"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fillRule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clipRule="evenodd"
              ></path>
            </svg>
            Location access granted
          </div>
        );
      case "denied":
        return (
          <div className="mb-4">
            <div className="flex items-center justify-center text-red-600 text-sm mb-2 p-2 bg-red-50 rounded-lg">
              <svg
                className="w-4 h-4 mr-2"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fillRule="evenodd"
                  d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                  clipRule="evenodd"
                ></path>
              </svg>
              Location access denied
            </div>
            <button
              onClick={handleLocationRequest}
              className="w-full text-sm text-blue-600 hover:text-blue-800 underline"
            >
              Try again or check browser settings
            </button>
          </div>
        );
      case "prompt":
        return (
          <div className="mb-4">
            <div className="flex items-center justify-center text-yellow-600 text-sm mb-2 p-2 bg-yellow-50 rounded-lg">
              <svg
                className="w-4 h-4 mr-2"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fillRule="evenodd"
                  d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                  clipRule="evenodd"
                ></path>
              </svg>
              Location access needed
            </div>
            <button
              onClick={handleLocationRequest}
              className="w-full text-sm bg-blue-100 text-blue-700 py-2 px-4 rounded-lg hover:bg-blue-200 transition-colors"
            >
              Enable Location Access
            </button>
          </div>
        );
      default:
        return null;
    }
  };

  return (
    <>
      <Header />
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 mt-12 flex items-center justify-center px-4 py-8">
        <div className="w-full max-w-sm sm:max-w-md">
          <Card className="text-center shadow-xl rounded-xl p-6 sm:p-8">
            {/* Logo */}
            <div className="mb-6">
              <img
                src={logo}
                alt="Snoutiq Logo"
                className="h-6 mx-auto mb-3 cursor-pointer"
              />
            </div>

            {/* Welcome Message */}
            <div className="mb-4 sm:mb-6">
              <h1 className="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">
                Welcome to Snoutiq!
              </h1>
              <p className="text-sm sm:text-base text-gray-600">
                Let's start by getting to know you
              </p>
            </div>

            {/* User Type Selection */}
            <div className="mb-6">
              <div className="flex bg-gray-100 rounded-lg p-1">
                <button
                  onClick={() => setUserType("pet_owner")}
                  className={`flex-1 py-2 px-4 rounded-md text-sm font-medium transition-colors ${
                    userType === "pet_owner"
                      ? "bg-white text-blue-600 shadow-sm"
                      : "text-gray-600 hover:text-gray-800"
                  }`}
                >
                  Pet Owner
                </button>
                <button
                  onClick={() => setUserType("veterinarian")}
                  className={`flex-1 py-2 px-4 rounded-md text-sm font-medium transition-colors ${
                    userType === "veterinarian"
                      ? "bg-white text-blue-600 shadow-sm"
                      : "text-gray-600 hover:text-gray-800"
                  }`}
                >
                  Veterinarian
                </button>
              </div>
            </div>

            {/* Progress Indicator */}
            <div className="mb-6">
              <div className="flex justify-between text-xs text-gray-500 mb-2">
                <span>Step 1 of 3</span>
                <span>Basic Details</span>
              </div>
              <div className="w-full bg-gray-200 rounded-full h-2">
                <div
                  className="bg-blue-600 h-2 rounded-full"
                  style={{ width: "33.33%" }}
                ></div>
              </div>
            </div>

            {/* Location Status */}
            <LocationStatus />

            {/* Google Sign In */}
            {/* <div className="mb-6">
              <div className="flex justify-center">
                <div className="w-full max-w-sm border rounded-xl shadow-md p-4 bg-white">
                  <GoogleOAuthProvider
                    clientId="325007826401-dhsrqhkpoeeei12gep3g1sneeg5880o7.apps.googleusercontent.com"
                  >
                    <GoogleLogin
                      onSuccess={handleGoogleSuccess}
                      onError={handleGoogleError}
                      theme="filled_blue"
                      size="large"
                      text="continue_with"
                      shape="rectangular"
                      disabled={loading}
                    />
                  </GoogleOAuthProvider>
                </div>
      
              </div>
            </div> */}
            {/* Google Sign In */}
            <div className="mb-6">
              <div className="flex justify-center">
                <div className="w-full max-w-sm border rounded-xl shadow-md p-4 bg-white">
                  <GoogleOAuthProvider
                    // clientId="YOUR_CLIENT_ID"
                    clientId="325007826401-dhsrqhkpoeeei12gep3g1sneeg5880o7.apps.googleusercontent.com"
                  >
                    <GoogleLogin
                      onSuccess={handleGoogleSuccess}
                      onError={() =>
                        setGoogleMessage(
                          "❌ Google login failed. Please try again."
                        )
                      }
                      theme="filled_blue"
                      size="large"
                      text="continue_with"
                      shape="rectangular"
                      disabled={loading}
                    />
                  </GoogleOAuthProvider>

                  {/* 👇 message below button */}
                  {googleMessage && (
                    <p
                      className={`mt-3 text-sm text-center ${
                        googleMessage.includes("successful")
                          ? "text-green-600"
                          : "text-red-600"
                      }`}
                    >
                      {googleMessage}
                    </p>
                  )}
                </div>
              </div>
            </div>

            <div className="mt-6 pt-4 border-t border-gray-200">
              <p className="text-gray-600 text-sm">
                Already have an account?{" "}
                <Link
                  to="/login"
                  className="text-blue-600 hover:underline font-medium"
                >
                  Login here
                </Link>
              </p>
            </div>
          </Card>
        </div>
      </div>
    </>
  );
};

export default RegisterBasicDetails;
