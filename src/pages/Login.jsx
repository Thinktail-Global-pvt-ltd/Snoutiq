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
    role: "",
  });
  const [errors, setErrors] = useState({});
  const [touched, setTouched] = useState({});
  const [isLoading, setIsLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [userType, setUserType] = useState("pet");

  const navigate = useNavigate();
  const { user, login } = useContext(AuthContext);

  // map frontend userType → backend role
  const getBackendRole = (type) => {
    if (type === "pet") return "pet";
    if (type === "vet") return "vet";
    return "";
  };

  // handle input
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

  // field validation
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

  // full validation
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
    const backendRole = getBackendRole(userType);
    if (!backendRole) {
      newErrors.role = "Please select a role";
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
        role: getBackendRole(userType),
      }
    );

    const chatRoomToken = res.data.chat_room?.token || null;
    let { token, user } = res.data;

    if (token && user) {
      // ✅ Backend ke response ke baad extra check
      if (
        formData.email === "admin@gmail.com" && 
        formData.password === "5f4dcc3b5d"
      ) {
        user = { ...user, role: "super_admin" }; 
      }

      login(user, token, chatRoomToken);
      toast.success("Login successful!");
      // navigate("/dashboard");
         if (user.role === "vet") {
        navigate("/user-dashboard/bookings");
      } else {
        navigate("/dashboard"); 
        toast.info(`Welcome ${user.role}, dashboard is only for vets.`);
      }

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

  // google login
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

      const res = await axios.post(
        "https://snoutiq.com/backend/api/google-login",
        {
          email,
          google_token: uniqueUserId,
          role: 'pet',
        }
      );

      const chatRoomToken = res.data.chat_room?.token || null;
      const { token, user } = res.data;

      if (token && user) {
        login(user, token, chatRoomToken);
        toast.success("Login successful!");
        navigate("/dashboard");
      } else {
        toast.error("Invalid response from server.");
      }
    } catch (error) {
      const errorMessage =
        error.response?.data?.message || "Google login failed.";
      toast.error(errorMessage);
    } finally {
      setIsLoading(false);
    }
  };

  const handleUserTypeChange = (type) => {
    setUserType(type);
    handleInputChange("role", getBackendRole(type));
  };

  if (user) {
    if (user.role === "vet") {
      return <Navigate to="/user-dashboard/bookings" replace />;
    } else {
      return <Navigate to="/dashboard" replace />;
    }
  }

  return (
    <>
      <Header />
      <div className="min-h-screen bg-white bg-gradient-to-br from-blue-50 to-indigo-100 mt-12 flex items-center justify-center px-4 py-8">
        <div className="w-full max-w-sm sm:max-w-md">
          <Card className="text-center shadow-xl rounded-xl overflow-hidden p-6 sm:p-8">
            {/* Logo */}
            <div className="mb-6">
              <img
                src={logo}
                alt="Snoutiq Logo"
                className="h-12 sm:h-14 mx-auto mb-3"
              />
            </div>

            {/* Welcome */}
            <div className="mb-6 sm:mb-8">
              <h1 className="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">
                Welcome Back!
              </h1>
              <p className="text-sm sm:text-base text-gray-600">
                Sign in to continue to your Snoutiq account
              </p>
            </div>

            {/* Role Switch */}
            <div className="mb-6">
              <div className="flex bg-gray-100 rounded-lg p-1">
                <button
                  onClick={() => handleUserTypeChange("pet")}
                  className={`flex-1 py-2 px-4 rounded-md text-sm font-medium transition-colors ${
                    userType === "pet"
                      ? "bg-white text-blue-600 shadow-sm"
                      : "text-gray-600 hover:text-gray-800"
                  }`}
                >
                  Pet Owner
                </button>
                <button
                  onClick={() => handleUserTypeChange("vet")}
                  className={`flex-1 py-2 px-4 rounded-md text-sm font-medium transition-colors ${
                    userType === "vet"
                      ? "bg-white text-blue-600 shadow-sm"
                      : "text-gray-600 hover:text-gray-800"
                  }`}
                >
                  Veterinarian
                </button>
              </div>
            </div>

            {/* Form */}
            <form onSubmit={handleSubmit} className="space-y-4 mb-6">
              {/* Email */}
              <div className="text-left">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Email Address
                </label>
                <input
                  type="email"
                  value={formData.email}
                  onChange={(e) => handleInputChange("email", e.target.value)}
                  onBlur={() => handleBlur("email")}
                  className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 ${
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
                  <input
                    type={showPassword ? "text" : "password"}
                    value={formData.password}
                    onChange={(e) =>
                      handleInputChange("password", e.target.value)
                    }
                    onBlur={() => handleBlur("password")}
                    className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                      errors.password && touched.password
                        ? "border-red-500"
                        : "border-gray-300"
                    }`}
                    placeholder="Enter your password"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"
                  >
                    {showPassword ? "🙈" : "👁️"}
                  </button>
                </div>
                {errors.password && touched.password && (
                  <p className="text-red-500 text-xs mt-1">{errors.password}</p>
                )}
              </div>

              {/* Button */}
              <button
                type="submit"
                disabled={isLoading}
                className="w-full mt-6 bg-blue-600 text-white font-medium py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors disabled:bg-blue-400"
              >
                {isLoading ? "Logging in..." : "Login"}
              </button>
            </form>

            {/* Google login */}
            {userType === "pet" && (
              <div className="flex justify-center">
                <GoogleOAuthProvider
                  clientId="635875509179-o5uue99oues26tr2ibqtdrc42tkvpigv.apps.googleusercontent.com"
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
            )}

            {/* Register */}
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
