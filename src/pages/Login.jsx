import React, { useState, useContext } from "react";
import { toast } from "react-hot-toast";
import { Link, Navigate, useNavigate } from "react-router-dom";
import axios from "../axios";
import Card from "../components/Card";
import logo from "../assets/images/logo.png";
import { AuthContext } from "../auth/AuthContext";
import Header from "../components/Header";
import { GoogleOAuthProvider, GoogleLogin } from "@react-oauth/google";

const Login = () => {
  const [formData, setFormData] = useState({
    email: "",
    password: "",
    role: "", // Add role to form data
  });
  const [errors, setErrors] = useState({});
  const [touched, setTouched] = useState({});
  const [isLoading, setIsLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [showRoleSelection, setShowRoleSelection] = useState(false);
  const navigate = useNavigate();

  const { user, login, updateChatRoomToken } = useContext(AuthContext);

  const handleInputChange = (field, value) => {
    setFormData((prev) => ({
      ...prev,
      [field]: value,
    }));
    // Clear error when user starts typing
    if (errors[field]) {
      setErrors((prev) => ({ ...prev, [field]: "" }));
    }
  };

  const handleBlur = (field) => {
    setTouched((prev) => ({ ...prev, [field]: true }));
    // Validate individual field on blur
    validateField(field, formData[field]);
  };

  const validateField = (field, value) => {
    let error = "";
    switch (field) {
      case "email":
        if (!value) {
          error = "Email is required";
        } else if (!/\S+@\S+\.\S+/.test(value)) {
          error = "Please enter a valid email address";
        }
        break;
      case "password":
        if (!value) {
          error = "Password is required";
        }
        break;
      case "role":
        if (!value) {
          error = "Please select a role";
        }
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
    if (!formData.role) {
      newErrors.role = "Please select a role";
    }
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    // First validate email and password only
    const emailPasswordValid = validateEmailPassword();
    if (!emailPasswordValid) return;

    // If email and password are valid but no role selected, show role selection
    if (!formData.role) {
      setShowRoleSelection(true);
      return;
    }

    // If all fields are filled, proceed with login
    if (!validateForm()) return;

    setIsLoading(true);
    try {
      const res = await axios.post(
        "https://snoutiq.com/backend/api/auth/login",
        {
          login: formData.email,
          password: formData.password,
          role: formData.role,
        }
      );

      const chatRoomToken = res.data.chat_room?.token || null;
      const { token, user } = res.data;

      if (token && user) {
        login(user, token, chatRoomToken);
        navigate("/dashboard");
        toast.success("Login successful!");
      } else {
        toast.error("Invalid response from server.");
      }
    } catch (error) {
      const errorMessage =
        error.response?.data?.message ||
        "Login failed. Please check your credentials and try again.";
      toast.error(errorMessage);
    } finally {
      setIsLoading(false);
    }
  };

  // Validate only email and password
  const validateEmailPassword = () => {
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

  const handleRoleSelect = async (role) => {
    // Update role in formData
    handleInputChange("role", role);
    setShowRoleSelection(false);

    // Validate email and password first
    if (!validateEmailPassword()) return;

    // Start login API call directly
    setIsLoading(true);
    try {
      const res = await axios.post(
        "https://snoutiq.com/backend/api/auth/login",
        {
          login: formData.email,
          password: formData.password,
          role: role, // use selected role
        }
      );

      const chatRoomToken = res.data.chat_room?.token || null;
      const { token, user } = res.data;

      if (token && user) {
        login(user, token, chatRoomToken);
        navigate("/dashboard");
        toast.success("Login successful!");
      } else {
        toast.error("Invalid response from server.");
      }
    } catch (error) {
      const errorMessage =
        error.response?.data?.message ||
        "Login failed. Please check your credentials and try again.";
      toast.error(errorMessage);
    } finally {
      setIsLoading(false);
    }
  };
  const handleGoogleSuccess = async (credentialResponse) => {
    setIsLoading(true);
    try {
      const base64Url = credentialResponse.credential.split(".")[1];
      const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
      const jsonPayload = decodeURIComponent(
        atob(base64)
          .split("")
          .map((c) => "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2))
          .join("")
      );
      const googleData = JSON.parse(jsonPayload);

      const uniqueUserId = googleData.sub; 
      const email = googleData.email || "";

      // Call your backend with the Google `sub`
      const res = await axios.post(
        "https://snoutiq.com/backend/api/google-login",
        {
          email: email, 
          google_token: uniqueUserId,
          // role: formData.role,        
        }
      );

      const chatRoomToken = res.data.chat_room?.token || null;
      const { token, user } = res.data;

      if (token && user) {
        login(user, token, chatRoomToken);
        navigate("/dashboard");
        toast.success("Login successful!");
      } else {
        toast.error("Invalid response from server.");
      }
    } catch (error) {
      const errorMessage =
        error.response?.data?.message ||
        "Login failed. Please check your credentials and try again.";
      toast.error(errorMessage);
    } finally {
      setIsLoading(false);
    }
  };

  if (user) return <Navigate to="/dashboard" replace />;

  return (
    <>
      <Header />
      <div className="min-h-screen bg-white bg-gradient-to-br from-blue-50 to-indigo-100 mt-12 flex items-center justify-center px-4 py-8">
        <div className="w-full max-w-sm sm:max-w-md">
          <Card className="text-center shadow-xl rounded-xl overflow-hidden p-6 sm:p-8">
            {/* Role Selection Modal */}
            {showRoleSelection && (
              <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div className="bg-white rounded-lg p-6 w-full max-w-md mx-4">
                  <h2 className="text-xl font-bold mb-4">Select Your Role</h2>
                  <p className="text-gray-600 mb-6">
                    Please choose how you'd like to use Snoutiq:
                  </p>

                  <div className="space-y-4">
                    <button
                      onClick={() => handleRoleSelect("pet_owner")}
                      className="w-full bg-blue-100 border border-blue-300 rounded-lg p-4 text-left hover:bg-blue-200 transition-colors"
                    >
                      <h3 className="font-semibold text-blue-800">Pet Owner</h3>
                      <p className="text-sm text-gray-600 mt-1">
                        I want to manage my pets and connect with veterinarians
                      </p>
                    </button>

                    <button
                      onClick={() => handleRoleSelect("veterinarian")}
                      className="w-full bg-green-100 border border-green-300 rounded-lg p-4 text-left hover:bg-green-200 transition-colors"
                    >
                      <h3 className="font-semibold text-green-800">
                        Veterinarian
                      </h3>
                      <p className="text-sm text-gray-600 mt-1">
                        I want to provide veterinary services and connect with
                        pet owners
                      </p>
                    </button>
                  </div>

                  <button
                    onClick={() => setShowRoleSelection(false)}
                    className="mt-6 w-full bg-gray-200 text-gray-800 font-medium py-2 px-4 rounded-lg hover:bg-gray-300 transition-colors"
                  >
                    Cancel
                  </button>
                </div>
              </div>
            )}

            {/* Selected Role Display */}
            {formData.role && (
              <div
                className={`mb-4 p-3 rounded-lg ${
                  formData.role === "pet_owner"
                    ? "bg-blue-100 text-blue-800"
                    : "bg-green-100 text-green-800"
                }`}
              >
                <span className="font-medium">Selected: </span>
                {formData.role === "pet_owner" ? "Pet Owner" : "Veterinarian"}
                <button
                  onClick={() => handleInputChange("role", "")}
                  className="ml-2 text-sm underline"
                >
                  Change
                </button>
              </div>
            )}

            {/* Logo */}
            <div className="mb-6">
              <img
                src={logo}
                alt="Snoutiq Logo"
                className="h-12 sm:h-14 mx-auto mb-3"
              />
            </div>

            {/* Welcome Message */}
            <div className="mb-6 sm:mb-8">
              <h1 className="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">
                Welcome Back!
              </h1>
              <p className="text-sm sm:text-base text-gray-600">
                Sign in to continue to your Snoutiq account
              </p>
            </div>

            {/* Divider */}
            <div className="relative flex items-center mb-6">
              <div className="flex-grow border-t border-gray-300"></div>
              <span className="flex-shrink mx-4 text-gray-500 text-sm">or</span>
              <div className="flex-grow border-t border-gray-300"></div>
            </div>

            {/* Form Fields */}
            <form onSubmit={handleSubmit} className="space-y-4 mb-6">
              {/* Email */}
              <div className="text-left">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Email Address
                </label>
                <div className="relative">
                  <div className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                    <svg
                      className="w-5 h-5"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                      />
                    </svg>
                  </div>
                  <input
                    type="email"
                    value={formData.email}
                    onChange={(e) => handleInputChange("email", e.target.value)}
                    onBlur={() => handleBlur("email")}
                    className={`w-full pl-10 pr-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                      errors.email && touched.email
                        ? "border-red-500"
                        : "border-gray-300"
                    }`}
                    placeholder="Enter your email address"
                  />
                  {errors.email && touched.email && (
                    <p className="text-red-500 text-xs mt-1">{errors.email}</p>
                  )}
                </div>
              </div>

              {/* Password */}
              <div className="text-left">
                <div className="flex justify-between items-center mb-2">
                  <label className="block text-sm font-medium text-gray-700">
                    Password
                  </label>
                  <Link
                    to="/forgot-password"
                    className="text-xs text-blue-600 hover:underline"
                  >
                    Forgot Password?
                  </Link>
                </div>
                <div className="relative">
                  <div className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                    <svg
                      className="w-5 h-5"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                      />
                    </svg>
                  </div>
                  <input
                    type={showPassword ? "text" : "password"}
                    value={formData.password}
                    onChange={(e) =>
                      handleInputChange("password", e.target.value)
                    }
                    onBlur={() => handleBlur("password")}
                    className={`w-full pl-10 pr-12 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                      errors.password && touched.password
                        ? "border-red-500"
                        : "border-gray-300"
                    }`}
                    placeholder="Enter your password"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                  >
                    {showPassword ? (
                      <svg
                        className="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          strokeWidth={2}
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          strokeWidth={2}
                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                        />
                      </svg>
                    ) : (
                      <svg
                        className="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          strokeWidth={2}
                          d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                        />
                      </svg>
                    )}
                  </button>
                  {errors.password && touched.password && (
                    <p className="text-red-500 text-xs mt-1">
                      {errors.password}
                    </p>
                  )}
                </div>
              </div>

              {/* Login Button */}
              <button
                type="submit"
                disabled={isLoading}
                className="w-full mt-6 bg-blue-600 text-white font-medium py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors disabled:bg-blue-400 disabled:cursor-not-allowed shadow-md"
              >
                {isLoading ? (
                  <span className="flex items-center justify-center">
                    <svg
                      className="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                      xmlns="http://www.w3.org/2000/svg"
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
                    Logging in...
                  </span>
                ) : (
                  "Login"
                )}
              </button>
            </form>
            <div className="flex justify-center">
              <GoogleOAuthProvider
                clientId={import.meta.env.VITE_GOOGLE_OAUTH_CLIENT_ID}
                onScriptLoadError={() =>
                  console.error("Google OAuth script failed to load")
                }
                onScriptLoadSuccess={() =>
                  console.log("Google OAuth script loaded")
                }
              >
                <GoogleLogin
                  onSuccess={handleGoogleSuccess}
                  onError={() => toast.error("Google login failed.")}
                  useOneTap
                  theme="filled_blue"
                  size="large"
                  text="continue_with"
                  shape="rectangular"
                />
              </GoogleOAuthProvider>
            </div>

            {/* Register Link */}
            <div className="mt-6 pt-4 border-t border-gray-200">
              <p className="text-gray-600 text-sm">
                Don't have an account?{" "}
                <Link
                  to="/register"
                  className="text-blue-600 hover:underline font-medium"
                >
                  Create an account
                </Link>
              </p>
            </div>
          </Card>
        </div>
      </div>
    </>
  );
};

export default Login;
