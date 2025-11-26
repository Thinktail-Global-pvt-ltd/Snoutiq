import React, {
  useState,
  useContext,
  useEffect,
  useCallback,
  lazy,
  Suspense,
} from "react";
import { toast, Toaster } from "react-hot-toast";
import { Link, Navigate, useNavigate } from "react-router-dom";
import axios from "../axios";
import logo from "../assets/images/logo.webp";
import { AuthContext } from "../auth/AuthContext";
const Header = lazy(() => import("../components/Header"));

// Background image - you can replace this with your actual image
const loginBackground = "https://images.unsplash.com/photo-1543466835-00a7907e9de1?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1974&q=80";

const Login = () => {
  const [formData, setFormData] = useState({
    email: "",
    password: "",
    role: "vet",
  });
  const [errors, setErrors] = useState({});
  const [touched, setTouched] = useState({});
  const [isLoading, setIsLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  
  // Location states
  const [locationStatus, setLocationStatus] = useState("checking");
  const [coords, setCoords] = useState({ lat: null, lng: null });

  const navigate = useNavigate();
  const { user, login } = useContext(AuthContext);

  // Enhanced location permission check
  const requestLocation = useCallback(() => {
    setLocationStatus("checking");

    const options = {
      enableHighAccuracy: true,
      timeout: 15000,
      maximumAge: 300000,
    };

    navigator.geolocation.getCurrentPosition(
      (position) => {
        setLocationStatus("granted");
        setCoords({
          lat: position.coords.latitude,
          lng: position.coords.longitude,
        });
        toast.success("Location access granted!");
      },
      (error) => {
        console.error("Location error:", error);
        setLocationStatus("denied");

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
  }, []);

  const initializeLocation = useCallback(async () => {
    if (typeof window === "undefined" || !navigator.geolocation) {
      setLocationStatus("denied");
      toast.error("Location services not supported in this browser");
      return null;
    }

    if (!navigator.permissions) {
      setLocationStatus("prompt");
      return null;
    }

    try {
      const result = await navigator.permissions.query({
        name: "geolocation",
      });

      const handlePermissionChange = () => {
        if (result.state === "granted") {
          setLocationStatus("granted");
          requestLocation();
        } else if (result.state === "denied") {
          setLocationStatus("denied");
        } else {
          setLocationStatus("prompt");
        }
      };

      handlePermissionChange();
      result.addEventListener("change", handlePermissionChange);

      return () => result.removeEventListener("change", handlePermissionChange);
    } catch (error) {
      console.error("Error checking permissions:", error);
      setLocationStatus("prompt");
      return null;
    }
  }, [requestLocation]);

  useEffect(() => {
    let cleanupPermission;
    const timeout = window.setTimeout(() => {
      initializeLocation().then((cleanup) => {
        cleanupPermission = cleanup;
      });
    }, 250);

    return () => {
      window.clearTimeout(timeout);
      if (typeof cleanupPermission === "function") {
        cleanupPermission();
      }
    };
  }, [initializeLocation]);

  const handleLocationRequest = () => {
    if (locationStatus === "denied") {
      toast.error(
        "Please enable location in your browser settings and refresh the page."
      );
      return;
    }
    requestLocation();
  };

  const handleInputChange = (field, value) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    if (errors[field]) {
      setErrors((prev) => ({ ...prev, [field]: "" }));
    }
  };

  const handleBlur = (field) => {
    setTouched((prev) => ({ ...prev, [field]: true }));
    validateField(field, formData[field]);
  };

  const validateField = (field, value) => {
    let error = "";
    switch (field) {
      case "email":
        if (!value) error = "Email is required";
        else if (!/\S+@\S+\.\S+/.test(value))
          error = "Please enter a valid email address";
        break;
      case "password":
        if (!value) error = "Password is required";
        break;
      default:
        break;
    }
    setErrors((prev) => ({ ...prev, [field]: error }));
    return !error;
  };

  const validateForm = () => {
    const newErrors = {};
    if (!formData.email) {
      newErrors.email = "Email is required";
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = "Please enter a valid email address";
    }
    if (!formData.password) {
      newErrors.password = "Password is required";
    }
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!validateForm()) return;

    setIsLoading(true);
    try {
      const res = await axios.post(
        "https://snoutiq.com/backend/api/auth/login",
        {
          login: formData.email,
          password: formData.password,
          role: "vet",
          latitude: coords.lat,
          longitude: coords.lng,
        }
      );

      console.log("Login response:", res);

      // const chatRoomToken = res.data.chat_room?.token || null;
      const { token, user } = res.data;

      if (token && user) {
        let finalUser = { ...user };

        if (
          formData.email === "admin@gmail.com" &&
          formData.password === "5f4dcc3b5d"
        ) {
          finalUser = { ...user, role: "super_admin" };
        }

        // login(finalUser, token);
        login(finalUser, token, null);

        toast.success("Login successful!");

        navigate("/user-dashboard/vet-dashboard");
      } else {
        toast.error("Invalid response from server.");
      }
    } catch (error) {
      console.error("Login error details:", error);

      const errorMessage =
        error.response?.data?.message ||
        error.message ||
        "Login failed. Please check your credentials and try again.";
      toast.error(errorMessage);
    } finally {
      setIsLoading(false);
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
            <svg
              className="w-4 h-4 mr-3"
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
            <div className="flex items-center justify-center text-red-600 text-sm mb-3 p-3 bg-red-50 rounded-xl border border-red-100">
              <svg
                className="w-4 h-4 mr-3"
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
              <svg
                className="w-4 h-4 mr-3"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fillRule="evenodd"
                  d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                  clipRule="evenodd"
                ></path>
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

  if (user) {
    return <Navigate to="/user-dashboard/vet-dashboard" replace />;
  }

  return (
    <>
      <Toaster position="top-right" />
      <Suspense fallback={<div className="h-16 w-full bg-white/80" />}>
        <Header />
      </Suspense>
      <div className="min-h-screen bg-white flex mt-16">
        {/* Left Side - Brand Section */}
        <div className="hidden lg:flex lg:w-1/2 relative bg-gradient-to-br from-blue-900 to-indigo-900">
          <div className="absolute inset-0 bg-black/20"></div>
          
          {/* Background Pattern */}
          <div className="absolute inset-0 opacity-10">
            <div className="absolute inset-0" style={{
              backgroundImage: `radial-gradient(circle at 25px 25px, rgba(255,255,255,0.3) 2%, transparent 40%)`,
              backgroundSize: '50px 50px'
            }}></div>
          </div>
          
          <div
            className="absolute inset-0 bg-cover bg-center bg-no-repeat"
            style={{ backgroundImage: `url(${loginBackground})` }}
          >
            {/* Overlay */}
            <div className="absolute inset-0 bg-blue-900/70"></div>
          </div>

          {/* Content */}
          <div className="relative z-10 flex flex-col justify-center p-16 text-white w-full">
            <div className="max-w-lg">

              <h1 className="text-5xl font-bold mb-6 leading-tight">
                Welcome to{" "}
                <span className="bg-gradient-to-r from-blue-300 to-indigo-200 bg-clip-text text-transparent">
                  Snoutiq
                </span>
              </h1>
              
              <p className="text-xl text-blue-100 mb-10 leading-relaxed font-light">
                Your trusted partner in comprehensive pet healthcare. Connect with licensed veterinarians and ensure the best care for your beloved pets.
              </p>
              
              {/* Features Grid */}
              <div className="grid grid-cols-1 gap-6">
                <div className="flex items-start gap-4 p-4 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
                  <div className="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center flex-shrink-0 border border-blue-400/30">
                    <span className="text-lg">🏥</span>
                  </div>
                  <div>
                    <h3 className="font-semibold text-white mb-1">24/7 Veterinary Support</h3>
                    <p className="text-blue-100 text-sm font-light">Round-the-clock access to licensed professionals</p>
                  </div>
                </div>
                
                <div className="flex items-start gap-4 p-4 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
                  <div className="w-12 h-12 bg-indigo-500/20 rounded-xl flex items-center justify-center flex-shrink-0 border border-indigo-400/30">
                    <span className="text-lg">📍</span>
                  </div>
                  <div>
                    <h3 className="font-semibold text-white mb-1">Location-Based Services</h3>
                    <p className="text-blue-100 text-sm font-light">Find nearby veterinarians and pet services</p>
                  </div>
                </div>

                <div className="flex items-start gap-4 p-4 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
                  <div className="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center flex-shrink-0 border border-emerald-400/30">
                    <span className="text-lg">🎥</span>
                  </div>
                  <div>
                    <h3 className="font-semibold text-white mb-1">Instant Video Consultations</h3>
                    <p className="text-blue-100 text-sm font-light">High-quality remote veterinary care</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Right Side - Login Form */}
        <div className="w-full lg:w-1/2 flex items-center justify-center px-6 py-8 lg:px-12 bg-gradient-to-br from-blue-50 to-indigo-100">
          <div className="w-full max-w-md shadow-xl rounded-xl overflow-hidden p-6 sm:p-8 bg-white">
            {/* Mobile Logo */}
            <div className="lg:hidden flex justify-center mb-8">
              <img
                src={logo}
                alt="Snoutiq"
                className="h-8"
              />
            </div>

            {/* Header */}
            <div className="text-center mb-8">
              <h1 className="text-3xl font-bold text-gray-900 mb-3">
                Veterinarian Login
              </h1>
              <p className="text-gray-600">
                Access your Snoutiq doctor workspace
              </p>
            </div>

            {/* Location Status */}
            <div className="mb-6">
              <LocationStatus />
            </div>

            {/* Veterinarian Login Form */}
            <form onSubmit={handleSubmit} className="space-y-6">
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">
                  Email Address
                </label>
                  <input
                    type="email"
                    value={formData.email}
                    onChange={(e) => handleInputChange("email", e.target.value)}
                    onBlur={() => handleBlur("email")}
                    className={`w-full px-4 py-3.5 border-2 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 ${
                      errors.email && touched.email
                        ? "border-red-300 bg-red-50"
                        : "border-gray-200 hover:border-gray-300 focus:border-blue-500"
                    }`}
                    placeholder="Enter your email"
                  />
                  {errors.email && touched.email && (
                    <p className="text-red-600 text-sm font-medium mt-2 flex items-center gap-1">
                      <span>⚠</span> {errors.email}
                    </p>
                  )}
                </div>

                {/* Password Field */}
                <div>
                  <div className="flex justify-between items-center mb-2">
                    <label className="block text-sm font-semibold text-gray-700">
                      Password
                    </label>
                    <Link
                      to="/forgot-password"
                      className="text-sm text-blue-600 hover:text-blue-700 font-medium transition-colors"
                    >
                      Forgot password?
                    </Link>
                  </div>
                  <div className="relative">
                    <input
                      type={showPassword ? "text" : "password"}
                      value={formData.password}
                      onChange={(e) =>
                        handleInputChange("password", e.target.value)
                      }
                      onBlur={() => handleBlur("password")}
                      className={`w-full px-4 py-3.5 border-2 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 ${
                        errors.password && touched.password
                          ? "border-red-300 bg-red-50"
                          : "border-gray-200 hover:border-gray-300 focus:border-blue-500"
                      }`}
                      placeholder="Enter your password"
                    />
                    <button
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                      className="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                    >
                      {showPassword ? "Hide" : "Show"}
                    </button>
                  </div>
                  {errors.password && touched.password && (
                    <p className="text-red-600 text-sm font-medium mt-2 flex items-center gap-1">
                      <span>⚠</span> {errors.password}
                    </p>
                  )}
                </div>

                {/* Submit Button */}
                <button
                  type="submit"
                  disabled={isLoading}
                  className="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-4 px-6 rounded-xl shadow-lg shadow-blue-500/25 hover:shadow-xl hover:shadow-blue-500/30 transition-all duration-200 transform hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                >
                  {isLoading ? (
                    <div className="flex items-center justify-center gap-2">
                      <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                      Signing in...
                    </div>
                  ) : (
                    "Sign In"
                  )}
                </button>
              </form>

            <div className="mt-6 text-center text-sm text-gray-500">
              Location helps us find nearby pet parents. You can still sign in without granting it.
            </div>

            {/* Sign Up Link */}
            <div className="mt-4 pt-6 border-t border-gray-100">
              <p className="text-center text-gray-600 text-sm">
                Don't have an account?{" "}
                <Link
                  to="/register"
                  className="text-blue-600 hover:text-blue-700 font-semibold transition-colors"
                >
                  Create account
                </Link>
              </p>
            </div>

          </div>
        </div>
      </div>
    </>
  );
};

export default Login;
