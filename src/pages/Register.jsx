import React, { useState, useContext, useEffect } from "react";
import { toast } from "react-hot-toast";
import { Link, useNavigate, Navigate } from "react-router-dom";
import Card from "../components/Card";
import Header from "../components/Header";
import logo from "../assets/images/logo.webp";
import { AuthContext } from "../auth/AuthContext";
import { GoogleOAuthProvider, GoogleLogin } from "@react-oauth/google";
import axios from "axios";

// Background image for the right side
const registerBackground = "https://images.unsplash.com/photo-1450778869180-41d0601e046e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1974&q=80";

const RegisterBasicDetails = () => {
  const navigate = useNavigate();
  const { user, login } = useContext(AuthContext);

  const [userType, setUserType] = useState("pet_owner");
  const [loading, setLoading] = useState(false);
  const [locationStatus, setLocationStatus] = useState("checking");
  const [locationAllowed, setLocationAllowed] = useState(false);
  const [coords, setCoords] = useState({ lat: null, lng: null });
  const [googleMessage, setGoogleMessage] = useState("");
  const [formData, setFormData] = useState({
    fullName: "",
    email: "",
    google_token: "",
  });
  const [errors, setErrors] = useState({});
  const [touched, setTouched] = useState({});

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
      if (!navigator.geolocation) {
        setLocationStatus("denied");
        setLocationAllowed(false);
        toast.error("Location services not supported in this browser");
        return;
      }

      if (navigator.permissions) {
        try {
          const result = await navigator.permissions.query({
            name: "geolocation",
          });

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
            setLocationStatus("prompt");
          }

          result.addEventListener("change", () => {
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
        setLocationStatus("prompt");
      }
    };

    initializeLocation();
  }, []);

  const requestLocation = () => {
    setLocationStatus("checking");

    const options = {
      enableHighAccuracy: true,
      timeout: 15000,
      maximumAge: 300000,
    };

    navigator.geolocation.getCurrentPosition(
      (position) => {
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

  const handleLocationRequest = () => {
    if (locationStatus === "denied") {
      toast.error(
        "Please enable location in your browser settings and refresh the page."
      );
      return;
    }
    requestLocation();
  };

  const handleGoogleSuccess = async (credentialResponse) => {
    const frontendRole = "pet"; 

    try {
      setLoading(true);
      setGoogleMessage("");

      if (locationStatus !== "granted" || !coords.lat || !coords.lng) {
        setGoogleMessage(
          "❌ Please allow location access before Google login."
        );
        return;
      }

      const base64Url = credentialResponse.credential.split(".")[1];
      const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
      const jsonPayload = decodeURIComponent(
        atob(base64)
          .split("")
          .map((c) => "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2))
          .join("")
      );
      const googleData = JSON.parse(jsonPayload);
      const email = googleData.email;
      const googleToken = googleData.sub;

      let loginRes;
      try {
        loginRes = await axios.post(
          "https://snoutiq.com/backend/api/google-login",
          { email, google_token: googleToken }
        );

        if (loginRes.data.status === "success") {
          const chatRoomToken = loginRes.data.chat_room?.token || null;
          const { token, user } = loginRes.data;

          login({ ...user, role: frontendRole }, token, chatRoomToken);
          toast.success("Login successful!");
          navigate("/dashboard");
          return;
        }
      } catch (err) {
        console.warn("Login failed, will try register:", err.response?.data);
      }

      const registerRes = await fetch(
        "https://snoutiq.com/backend/api/auth/initial-register",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            fullName: googleData.name,
            email,
            google_token: googleToken,
            latitude: coords.lat,
            longitude: coords.lng,
          }),
        }
      );

      const registerData = await registerRes.json();

      if (registerData.status === "error") {
        setGoogleMessage(registerData.message || "Something went wrong.");
        return;
      }

      const finalLoginRes = await axios.post(
        "https://snoutiq.com/backend/api/google-login",
        { email, google_token: googleToken, role: frontendRole }
      );

      const chatRoomToken = finalLoginRes.data.chat_room?.token || null;
      const { token, user } = finalLoginRes.data;

      if (token && user) {
        const userWithRole = { ...user, role: frontendRole };
        login(userWithRole, token, chatRoomToken);

        localStorage.setItem("user", JSON.stringify(userWithRole));
        
        toast.success("Login successful!");
        navigate("/dashboard");
      } else {
        toast.error("Invalid response from server.");
      }
    } catch (error) {
      console.error("Google login failed:", error);
      const errorMessage =
        error.response?.data?.message || "Google login failed.";
      toast.error(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  const handleGoogleError = () => {
    toast.error("Google login failed. Please try again.");
  };

  const handleInputChange = (field, value) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    if (errors[field]) {
      setErrors((prev) => ({ ...prev, [field]: null }));
    }
  };

  const handleBlur = (field) => {
    setTouched((prev) => ({ ...prev, [field]: true }));
  };

  const validateBasicDetails = () => {
    const newErrors = {};

    if (!formData.fullName.trim()) {
      newErrors.fullName = "Full name is required";
    }

    if (!formData.email.trim()) {
      newErrors.email = "Email is required";
    } else if (!/^\S+@\S+\.\S+$/.test(formData.email)) {
      newErrors.email = "Invalid email format";
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleNext = async () => {
    if (!validateBasicDetails()) {
      setTouched({
        fullName: true,
        email: true,
      });

      const firstError = errors.fullName || errors.email;
      if (firstError) {
        toast.error(firstError);
      }
      return;
    }

    if (locationStatus === "prompt" || locationStatus === "checking") {
      toast.info("Please allow location access to continue...");
      requestLocation();

      setTimeout(() => {
        if (coords.lat && coords.lng) {
          navigate("/dashboard");
        }
      }, 2000);
    } else if (locationStatus === "granted" && coords.lat && coords.lng) {
      navigate("/dashboard");
    } else {
      toast.error(
        "Location access is required to proceed. Please allow location access."
      );
    }
  };

  // Enhanced Location Status Component
  const LocationStatus = () => {
    switch (locationStatus) {
      case "checking":
        return (
          <div className="flex items-center justify-center text-blue-600 text-sm mb-4 p-3 bg-blue-50 rounded-xl border border-blue-100">
            <div className="w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mr-3"></div>
            Requesting location access...
          </div>
        );
      case "granted":
        return (
          <div className="flex items-center justify-center text-green-600 text-sm mb-4 p-3 bg-green-50 rounded-xl border border-green-100">
            <svg className="w-4 h-4 mr-3" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd"></path>
            </svg>
            Location access granted
          </div>
        );
      case "denied":
        return (
          <div className="mb-4">
            <div className="flex items-center justify-center text-red-600 text-sm mb-3 p-3 bg-red-50 rounded-xl border border-red-100">
              <svg className="w-4 h-4 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd"></path>
              </svg>
              Location access denied
            </div>
            <button
              onClick={handleLocationRequest}
              className="w-full text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors"
            >
              Try again or check browser settings
            </button>
          </div>
        );
      case "prompt":
        return (
          <div className="mb-4">
            <div className="flex items-center justify-center text-yellow-600 text-sm mb-3 p-3 bg-yellow-50 rounded-xl border border-yellow-100">
              <svg className="w-4 h-4 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd"></path>
              </svg>
              Location access needed for better service
            </div>
            <button
              onClick={handleLocationRequest}
              className="w-full text-sm bg-blue-100 text-blue-700 py-3 px-4 rounded-xl hover:bg-blue-200 transition-colors font-medium"
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
      <div className="min-h-screen bg-white flex mt-12">
        {/* Left Side - Registration Form */}
        <div className="w-full lg:w-1/2 flex items-center justify-center px-6 py-8 lg:px-16 xl:px-24 bg-gradient-to-br from-slate-50 to-blue-50/30 ">
          <div className="w-full max-w-md shadow-xl rounded-xl overflow-hidden p-6 sm:p-8 bg-white">
            {/* Mobile Logo */}

            {/* Header */}
            <div className="text-center mb-10">
              <h1 className="text-3xl lg:text-4xl font-bold text-gray-900 mb-3 bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
                Join Snoutiq
              </h1>
              <p className="text-gray-600 text-lg font-light">
                Create your account to get started
              </p>
            </div>

            {/* Role Selector */}
            <div className="mb-10">
              <label className="block text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wide ">
                I am a
              </label>
              <div className="flex bg-gray-100 rounded-2xl p-2 border border-gray-200 shadow-sm">
                <button
                  onClick={() => setUserType("pet_owner")}
                  className={`flex-1 py-4 px-6 rounded-xl text-base font-semibold transition-all duration-300 ${
                    userType === "pet_owner"
                      ? "bg-white text-blue-700 shadow-lg shadow-blue-100 border border-blue-100 transform scale-105"
                      : "text-gray-600 hover:text-gray-800 hover:bg-gray-50/50"
                  }`}
                >
                  <div className="flex items-center justify-center gap-3">
                    <span className="text-lg">🐾</span>
                    <span>Pet Owner</span>
                  </div>
                </button>
                <button
                  onClick={() => setUserType("veterinarian")}
                  className={`flex-1 py-4 px-6 rounded-xl text-base font-semibold transition-all duration-300 ${
                    userType === "veterinarian"
                      ? "bg-white text-blue-700 shadow-lg shadow-blue-100 border border-blue-100 transform scale-105"
                      : "text-gray-600 hover:text-gray-800 hover:bg-gray-50/50"
                  }`}
                >
                  <div className="flex items-center justify-center gap-3">
                    <span className="text-lg">🩺</span>
                    <span>Veterinarian</span>
                  </div>
                </button>
              </div>
            </div>

            {/* Location Status */}
            <div className="mb-8">
              <LocationStatus />
            </div>

            {/* Google Sign Up */}
            <div className="space-y-6">
              {/* Divider */}
              <div className="relative">
                <div className="absolute inset-0 flex items-center">
                  <div className="w-full border-t border-gray-200"></div>
                </div>
                <div className="relative flex justify-center text-sm">
                  <span className="px-4 bg-transparent text-gray-500 font-medium text-sm uppercase tracking-wide">
                    Quick Sign Up
                  </span>
                </div>
              </div>

              {/* Google Login */}
              <div className="w-full">
                <GoogleOAuthProvider
                  clientId="325007826401-dhsrqhkpoeeei12gep3g1sneeg5880o7.apps.googleusercontent.com"
                >
                  <div className="flex justify-center">
                    <GoogleLogin
                      onSuccess={handleGoogleSuccess}
                      onError={handleGoogleError}
                      useOneTap
                      theme="filled_blue"
                      size="large"
                      text="signup_with"
                      shape="rectangular"
                      width="100%"
                      locale="en"
                      disabled={loading}
                    />
                  </div>
                </GoogleOAuthProvider>

                {/* Message below button */}
                {googleMessage && (
                  <p className={`mt-4 text-sm text-center p-3 rounded-xl ${
                    googleMessage.includes("successful")
                      ? "text-green-700 bg-green-50 border border-green-100"
                      : "text-red-700 bg-red-50 border border-red-100"
                  }`}>
                    {googleMessage}
                  </p>
                )}
              </div>

              {/* Loading State */}
              {loading && (
                <div className="flex items-center justify-center gap-3 text-blue-600 text-sm p-4 bg-blue-50 rounded-xl border border-blue-100">
                  <div className="w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                  Creating your account...
                </div>
              )}

              {/* Security Note */}
              <div className="text-center p-4 bg-blue-50 rounded-xl border border-blue-100">
                <p className="text-blue-700 text-sm font-medium">
                  🔒 Secure & encrypted registration
                </p>
                <p className="text-blue-600 text-xs mt-1">
                  Your privacy and security are our top priority
                </p>
              </div>
            </div>

            {/* Login Link */}
            <div className="mt-12 pt-8 border-t border-gray-100">
              <p className="text-center text-gray-600 text-base">
                Already have an account?{" "}
                <Link
                  to="/login"
                  className="text-blue-600 hover:text-blue-700 font-semibold transition-colors hover:underline"
                >
                  Sign in here
                </Link>
              </p>
            </div>
          </div>
        </div>

        {/* Right Side - Brand Section */}
        <div className="hidden lg:flex lg:w-1/2 relative bg-gradient-to-br from-blue-900 to-indigo-900">
          <div 
            className="absolute inset-0 bg-cover bg-center bg-no-repeat"
            style={{ backgroundImage: `url(${registerBackground})` }}
          >
            {/* Overlay */}
            <div className="absolute inset-0 bg-blue-900/70"></div>
          </div>
          
          {/* Background Pattern */}
          <div className="absolute inset-0 opacity-10">
            <div className="absolute inset-0" style={{
              backgroundImage: `radial-gradient(circle at 25px 25px, rgba(255,255,255,0.3) 2%, transparent 40%)`,
              backgroundSize: '50px 50px'
            }}></div>
          </div>
          
          {/* Content */}
          <div className="relative z-10 flex flex-col justify-center p-16 text-white w-full">
            <div className="max-w-lg">

              <h1 className="text-5xl font-bold mb-6 leading-tight">
                Start Your{" "}
                <span className="bg-gradient-to-r from-blue-300 to-indigo-200 bg-clip-text text-transparent">
                  Pet Care Journey
                </span>
              </h1>
              
              <p className="text-xl text-blue-100 mb-10 leading-relaxed font-light">
                Join thousands of pet owners and veterinarians who trust Snoutiq for comprehensive pet healthcare solutions.
              </p>

              
              {/* Benefits Grid */}
              <div className="grid grid-cols-1 gap-6">
                <div className="flex items-start gap-4 p-4 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
                  <div className="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center flex-shrink-0 border border-blue-400/30">
                    <span className="text-lg">🚀</span>
                  </div>
                  <div>
                    <h3 className="font-semibold text-white mb-1">Quick Setup</h3>
                    <p className="text-blue-100 text-sm font-light">Get started in seconds with Google Sign Up</p>
                  </div>
                </div>
                
                <div className="flex items-start gap-4 p-4 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
                  <div className="w-12 h-12 bg-indigo-500/20 rounded-xl flex items-center justify-center flex-shrink-0 border border-indigo-400/30">
                    <span className="text-lg">📍</span>
                  </div>
                  <div>
                    <h3 className="font-semibold text-white mb-1">Location-Based Services</h3>
                    <p className="text-blue-100 text-sm font-light">Find nearby veterinarians and services</p>
                  </div>
                </div>

                <div className="flex items-start gap-4 p-4 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
                  <div className="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center flex-shrink-0 border border-emerald-400/30">
                    <span className="text-lg">💫</span>
                  </div>
                  <div>
                    <h3 className="font-semibold text-white mb-1">Personalized Experience</h3>
                    <p className="text-blue-100 text-sm font-light">Tailored recommendations for your pets</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

export default RegisterBasicDetails;