// pages/RegisterPassword.js
import React, { useState, useEffect, useContext } from "react";
import { toast } from "react-hot-toast";
import { useLocation, useNavigate } from "react-router-dom";
import Card from "../components/Card";
import Header from "../components/Header";
import logo from "../assets/images/logo.webp";
import { useRegistration } from "../auth/RegistrationContext";
import { AuthContext } from "../auth/AuthContext";
import axios from "../axios";


const RegisterPetPassword = () => {
  const location = useLocation();
  const { userId } = location.state || {};
  // console.log(userId,"userID");
  
  const navigate = useNavigate();
  const { login } = useContext(AuthContext);
  const {
    formData,
    errors,
    touched,
    coords,
    updateFormData,
    setFieldTouched,
    validatePasswordDetails,
    clearRegistrationData,
  } = useRegistration();

  const [isLoading, setIsLoading] = useState(false);

  // Redirect if previous steps not completed
  useEffect(() => {
    if (!formData.fullName || !formData.email) {
      toast.error("Please complete basic details first");
      navigate("/register");
      return;
    }
    
    if (!formData.petName || !formData.petType || !formData.petBreed) {
      toast.error("Please complete pet details first");
      navigate("/register-pet-details");
      return;
    }
  }, [formData, navigate]);

  const handleInputChange = (field, value) => {
    updateFormData(field, value);
  };

  const handleBlur = (field) => {
    setFieldTouched(field);
  };

  const handleBack = () => {
    navigate("/register-pet-details");
  };

  const handleSubmit = async () => {
    if (!validatePasswordDetails()) {
      // Mark password fields as touched to show errors
      setFieldTouched("mobileNumber");
      setFieldTouched("password");
      setFieldTouched("confirmPassword");
      
      // Show first error
      const firstError = errors.mobileNumber || errors.password || errors.confirmPassword;
      if (firstError) {
        toast.error(firstError);
      }
      return;
    }

    // Check if location is available
    if (!coords.lat || !coords.lng) {
      toast.error("Please allow location to continue");
      return;
    }

    setIsLoading(true);

    try {
      // Prepare form data for submission
      const submitData = new FormData();
      submitData.append("user_id", userId);
      submitData.append("fullName", formData.fullName);
      submitData.append("email", formData.email);
      submitData.append("mobileNumber", formData.mobileNumber);
      submitData.append("password", formData.password);
      submitData.append("comfirmPassword", formData.confirmPassword);
      submitData.append("pet_type", formData.petType);
      submitData.append("pet_name", formData.petName);
      submitData.append("pet_gender", formData.petGender);
      submitData.append("pet_age", formData.petAge);
      submitData.append("breed", formData.petBreed);
      submitData.append("latitude", coords.lat);
      submitData.append("longitude", coords.lng);

      // Add optional fields
      if (formData.google_token) {
        submitData.append("google_token", formData.google_token);
      }
      if (formData.petDoc1) {
        submitData.append("pet_doc1", formData.petDoc1);
      }
      if (formData.petDoc2) {
        submitData.append("pet_doc2", formData.petDoc2);
      }

      // Submit registration
      const registerResponse = await axios.post(
        "https://snoutiq.com/backend/api/auth/register",
        submitData,
        { headers: { "Content-Type": "multipart/form-data" } }
      );

      // console.log("Registration successful:", registerResponse.data);

      // Auto login after successful registration
      try {
        const loginResponse = await axios.post(
          "https://snoutiq.com/backend/api/auth/login",
          {
            login: formData.email,
            password: formData.password,
            role: 'pet'
          }
        );

        const chatRoomToken = loginResponse.data.chat_room?.token || null;
        
        // Login user
        login(loginResponse.data.user, loginResponse.data.token, chatRoomToken);
        
        // Clear registration data
        clearRegistrationData();
        
        toast.success("Registration successful! Welcome to Snoutiq!");
        navigate("/dashboard");

      } catch (loginError) {
        console.error("Auto-login failed:", loginError);
        toast.success("Registration successful! Please login to continue.");
        navigate("/login");
      }

    } catch (error) {
      console.error("Registration failed:", error);
      
      const errorMessage = error.response?.data?.message || "Registration failed";
      
      if (errorMessage.includes("unique mobile or email")) {
        toast.error("Email or mobile number already exists!");
      } else {
        toast.error(errorMessage);
      }
    } finally {
      setIsLoading(false);
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
                className="h-6 mx-auto mb-3"
              />
            </div>

            {/* Progress Indicator */}
            <div className="mb-6">
              <div className="flex justify-between text-xs text-gray-500 mb-2">
                <span>Step 3 of 3</span>
                <span>Password & Mobile</span>
              </div>
              <div className="w-full bg-gray-200 rounded-full h-2">
                <div className="bg-blue-600 h-2 rounded-full" style={{ width: '100%' }}></div>
              </div>
            </div>

            {/* Summary */}
            <div className="mb-6 p-4 bg-gray-50 rounded-lg text-left">
              <h3 className="font-semibold text-gray-800 mb-2">Registration Summary</h3>
              <div className="text-sm text-gray-600 space-y-1">
                <p><span className="font-medium">Owner:</span> {formData.fullName}</p>
                <p><span className="font-medium">Email:</span> {formData.email}</p>
                <p><span className="font-medium">Pet:</span> {formData.petName} ({formData.petType})</p>
                <p><span className="font-medium">Breed:</span> {formData.petBreed}</p>
              </div>
            </div>

            {/* Form Fields */}
            <div className="space-y-4 mb-6">
              {/* Mobile Number */}
              <div className="text-left">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Mobile Number *
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
                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                      />
                    </svg>
                  </div>
                  <input
                    type="tel"
                    value={formData.mobileNumber}
                    onChange={(e) =>
                      handleInputChange(
                        "mobileNumber",
                        e.target.value.replace(/\D/g, "").slice(0, 10)
                      )
                    }
                    onBlur={() => handleBlur("mobileNumber")}
                    className={`w-full pl-10 pr-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                      errors.mobileNumber && touched.mobileNumber
                        ? "border-red-500"
                        : "border-gray-300"
                    }`}
                    placeholder="Enter your 10-digit mobile number"
                  />
                  {errors.mobileNumber && touched.mobileNumber && (
                    <p className="text-red-500 text-xs mt-1">{errors.mobileNumber}</p>
                  )}
                </div>
              </div>

              {/* Password */}
              <div className="text-left">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Password *
                </label>
                <div className="relative">
                  <input
                    type="password"
                    value={formData.password}
                    onChange={(e) => handleInputChange("password", e.target.value)}
                    onBlur={() => handleBlur("password")}
                    className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                      errors.password && touched.password
                        ? "border-red-500"
                        : "border-gray-300"
                    }`}
                    placeholder="Enter your password (min. 6 characters)"
                  />
                  {errors.password && touched.password && (
                    <p className="text-red-500 text-xs mt-1">{errors.password}</p>
                  )}
                </div>
              </div>

              {/* Confirm Password */}
              <div className="text-left">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Confirm Password *
                </label>
                <div className="relative">
                  <input
                    type="password"
                    value={formData.confirmPassword}
                    onChange={(e) =>
                      handleInputChange("confirmPassword", e.target.value)
                    }
                    onBlur={() => handleBlur("confirmPassword")}
                    className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                      errors.confirmPassword && touched.confirmPassword
                        ? "border-red-500"
                        : "border-gray-300"
                    }`}
                    placeholder="Confirm your password"
                  />
                  {errors.confirmPassword && touched.confirmPassword && (
                    <p className="text-red-500 text-xs mt-1">
                      {errors.confirmPassword}
                    </p>
                  )}
                </div>
              </div>
            </div>

            {/* Location Status */}
            <div className="mb-6">
              {coords.lat && coords.lng ? (
                <div className="flex items-center justify-center text-green-600 text-sm">
                  <svg className="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd"></path>
                  </svg>
                  Location permission granted
                </div>
              ) : (
                <div className="flex items-center justify-center text-yellow-600 text-sm">
                  <svg className="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd"></path>
                  </svg>
                  Please enable location access
                </div>
              )}
            </div>

            {/* Navigation Buttons */}
            <div className="flex justify-between gap-3">
              <button
                onClick={handleBack}
                className="flex-1 bg-gray-100 text-gray-800 font-medium py-3 px-6 rounded-lg hover:bg-gray-200 transition-colors"
                disabled={isLoading}
              >
                Back
              </button>
              <button
                onClick={handleSubmit}
                disabled={isLoading || !coords.lat || !coords.lng}
                className="flex-1 bg-blue-600 text-white font-medium py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors disabled:bg-blue-400 disabled:cursor-not-allowed"
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
                    Creating Account...
                  </span>
                ) : (
                  "Complete Registration"
                )}
              </button>
            </div>

            {/* Terms */}
            <div className="mt-4 text-xs text-gray-500">
              By completing registration, you agree to our{" "}
              <a href="/terms" className="text-blue-600 hover:underline">
                Terms of Service
              </a>{" "}
              and{" "}
              <a href="/privacy" className="text-blue-600 hover:underline">
                Privacy Policy
              </a>
            </div>
          </Card>
        </div>
      </div>
    </>
  );
};

export default RegisterPetPassword;