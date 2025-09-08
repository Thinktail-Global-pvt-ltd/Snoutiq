import React, { useState, useEffect, useContext } from "react";
import { toast } from "react-hot-toast";
import { Link, Navigate, useNavigate } from "react-router-dom";
import Card from "../components/Card";
import logo from "../assets/images/logo.png";
import axios from "../axios";
import { AuthContext } from "../auth/AuthContext";
import Header from "../components/Header";

const Register = () => {
  const [userType, setUserType] = useState("pet_owner"); // 'pet_owner' or 'veterinarian'
  const [formData, setFormData] = useState({
    fullName: "",
    email: "",
    mobileNumber: "",
    petName: "",
    petGender: "",
    petAge: "",
    petBread: "",
    petDoc1: null,
    petDoc2: null,
    emailOtp: "",
    password: "",
    confirmPassword: "",
  });

  const [errors, setErrors] = useState({});
  const [touched, setTouched] = useState({});
  const [otpSent, setOtpSent] = useState(false);
  const [isLoading, setIsLoading] = useState({ email: false, register: false });
  const [emailOtpCooldown, setEmailOtpCooldown] = useState(0);
  const [emailOtpToken, setEmailOtpToken] = useState("");
  const [activeStep, setActiveStep] = useState(0);
  const [otpStatus, setOtpStatus] = useState(null);
  const [locationAllowed, setLocationAllowed] = useState(null);
  const [coords, setCoords] = useState({ lat: null, lng: null });

  const navigate = useNavigate();
  const { user, login, updateChatRoomToken } = useContext(AuthContext);

  const steps = ["Basic Details", "Pet Details", "Verification & Password"];
  
  // If user is a veterinarian, show the professional registration form
  if (userType === "veterinarian") {
    return (
      navigate('/vet-register')
    );
  }

  // ✅ Location Permission Check
  const checkLocationPermission = async () => {
    if (!navigator.permissions) {
      console.log("Permissions API not supported in this browser");
      return null;
    }

    try {
      const result = await navigator.permissions.query({ name: "geolocation" });

      if (result.state === "granted") {
        console.log("✅ Location already allowed");
        return true;
      } else if (result.state === "prompt") {
        console.log("ℹ️ Location permission not asked yet");
        return null;
      } else if (result.state === "denied") {
        console.log("❌ Location blocked by user");
        return false;
      }
    } catch (error) {
      console.error("Error checking location permission:", error);
      return null;
    }
  };

  // ✅ Request Location Function
  const requestLocation = () => {
    return new Promise((resolve, reject) => {
      if (!navigator.geolocation) {
        toast.error("Geolocation not supported");
        reject(false);
        return;
      }
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          console.log("Location allowed ✅", pos.coords);
          setLocationAllowed(true);
          setCoords({
            lat: pos.coords.latitude,
            lng: pos.coords.longitude,
          });
          resolve(true);
        },
        (err) => {
          console.log("Location denied ❌", err);
          setLocationAllowed(false);
          toast.error("⚠️ Please allow location to continue");
          reject(false);
        }
      );
    });
  };

  const handleNext = async () => {
    if (activeStep === 0 && locationAllowed === null) {
      try {
        const granted = await requestLocation();
        if (!granted) return;
        setLocationAllowed(true);
        return;
      } catch {
        return;
      }
    }

    if (validateStep()) {
      setActiveStep((prev) => prev + 1);
    } else {
      const stepFields =
        activeStep === 0
          ? ["fullName", "email", "mobileNumber"]
          : activeStep === 1
          ? ["petName", "petGender", "petAge", "petBread"]
          : ["emailOtp", "password", "confirmPassword"];
      const touchedUpdate = {};
      stepFields.forEach((f) => (touchedUpdate[f] = true));
      setTouched((prev) => ({ ...prev, ...touchedUpdate }));
      const firstErrorKey = Object.keys(errors)[0];
      if (firstErrorKey) toast.error(errors[firstErrorKey]);
    }
  };

  // ✅ Run Once on Mount
  useEffect(() => {
    checkLocationPermission().then((status) => {
      if (status === true) {
        setLocationAllowed(true);
        requestLocation();
      } else if (status === false) {
        setLocationAllowed(false);
        toast.error("Please enable location access in browser settings");
      }
    });
  }, []);

  const handleInputChange = (field, value) => {
    setFormData((prev) => ({ ...prev, [field]: value }));

    if (errors[field]) {
      setErrors((prev) => ({ ...prev, [field]: "" }));
    }
  };

  const handleBlur = (field) => {
    setTouched((prev) => ({ ...prev, [field]: true }));
  };

  const handleSendEmailOtp = async () => {
    if (!formData.email.includes("@")) {
      setErrors({ ...errors, email: "Valid email is required" });
      toast.error("Valid email is required");
      return;
    }

    setIsLoading((prev) => ({ ...prev, email: true }));
    try {
      const res = await axios.post(
        "https://snoutiq.com/backend/api/auth/send-otp",
        {
          type: "email",
          value: formData.email,
          unique: "yes",
        }
      );

      if (res.data.message === "OTP sent successfully") {
        setEmailOtpToken(res.data.token);
        setOtpSent(true);
        setEmailOtpCooldown(15);
        toast.success(`Email OTP sent successfully`);
      } else {
        toast.error(res.data.message);
      }
    } catch (error) {
      toast.error(error.response?.data?.message || "Failed to send email OTP");
    } finally {
      setIsLoading((prev) => ({ ...prev, email: false }));
    }
  };

  useEffect(() => {
    if (emailOtpCooldown > 0) {
      const timer = setInterval(() => {
        setEmailOtpCooldown((prev) => prev - 1);
      }, 1000);
      return () => clearInterval(timer);
    }
  }, [emailOtpCooldown]);

  const verifyOtp = async () => {
    try {
      const res = await axios.post(
        "https://snoutiq.com/backend/api/auth/verify-otp",
        {
          token: emailOtpToken,
          otp: formData.emailOtp,
        }
      );

      if (res.data.message === "OTP verified successfully") {
        setOtpStatus("valid");
        toast.success("OTP verified successfully!");
      } else {
        setOtpStatus("invalid");
        toast.error(res.data.message || "Invalid OTP");
      }
    } catch (error) {
      setOtpStatus("invalid");
      toast.error(error.response?.data?.message || "Failed to verify OTP");
    }
  };

  useEffect(() => {
    if (formData.emailOtp.length === 4) {
      const timer = setTimeout(() => {
        verifyOtp();
      }, 3000);

      return () => clearTimeout(timer);
    }
  }, [formData.emailOtp]);

  const validateStep = (step = activeStep) => {
    const newErrors = {};

    if (step === 0) {
      if (!formData.fullName.trim()) newErrors.fullName = "Name is required";
      if (!formData.email.includes("@"))
        newErrors.email = "Valid email is required";
      if (!/^\d{10}$/.test(formData.mobileNumber))
        newErrors.mobileNumber = "Valid 10-digit mobile number required";
    }

    if (step === 1) {
      if (!formData.petName.trim()) newErrors.petName = "Pet name is required";
      if (!formData.petGender) newErrors.petGender = "Pet gender is required";
      if (!formData.petAge || formData.petAge <= 0)
        newErrors.petAge = "Valid pet age is required";
      if (!formData.petBread)
        newErrors.petBread = "Valid pet Breed is required";
    }

    if (step === 2) {
      if (!otpSent) newErrors.emailOtp = "Please send email OTP first";
      if (!formData.emailOtp.trim()) newErrors.emailOtp = "Enter email OTP";
      if (formData.password.length < 6)
        newErrors.password = "Password must be at least 6 characters";
      if (formData.password !== formData.confirmPassword) {
        newErrors.confirmPassword = "Passwords do not match";
      }
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleBack = () => {
    setActiveStep((prev) => prev - 1);
  };

  const compressImage = (file, maxSizeKB = 50, maxWidthOrHeight = 800) => {
    return new Promise((resolve) => {
      const reader = new FileReader();
      reader.readAsDataURL(file);

      reader.onload = (event) => {
        const img = new Image();
        img.src = event.target.result;

        img.onload = () => {
          const canvas = document.createElement("canvas");
          const ctx = canvas.getContext("2d");

          let width = img.width;
          let height = img.height;
          if (width > height && width > maxWidthOrHeight) {
            height = Math.round((height *= maxWidthOrHeight / width));
            width = maxWidthOrHeight;
          } else if (height > maxWidthOrHeight) {
            width = Math.round((width *= maxWidthOrHeight / height));
            height = maxWidthOrHeight;
          }

          canvas.width = width;
          canvas.height = height;
          ctx.drawImage(img, 0, 0, width, height);

          let quality = 0.7;
          const tryCompress = () => {
            canvas.toBlob(
              (blob) => {
                if (blob.size / 1024 > maxSizeKB && quality > 0.1) {
                  quality -= 0.1;
                  tryCompress();
                } else {
                  resolve(new File([blob], file.name, { type: "image/jpeg" }));
                }
              },
              "image/jpeg",
              quality
            );
          };
          tryCompress();
        };
      };
    });
  };

  const handleFileChange = async (field, files) => {
    if (files && files.length > 0) {
      const file = files[0];

      if (!file.type.startsWith("image/")) {
        handleInputChange(field, file);
        return;
      }

      const compressedFile = await compressImage(file, 50);
      handleInputChange(field, compressedFile);
    }
  };

  const handleSubmit = async () => {
    if (!validateStep()) return;

    if (!coords.lat || !coords.lng) {
      alert("⚠️ Please allow location to continue");
      return;
    }

    setIsLoading((prev) => ({ ...prev, register: true }));
    try {
      const submitData = new FormData();
      submitData.append("fullName", formData.fullName);
      submitData.append("email", formData.email);
      submitData.append("mobileNumber", formData.mobileNumber);
      submitData.append("password", formData.password);
      submitData.append("comfirmPassword", formData.confirmPassword);
      submitData.append("pet_name", formData.petName);
      submitData.append("pet_gender", formData.petGender);
      submitData.append("pet_age", formData.petAge);
      submitData.append("pet_bread", formData.petBread);

      submitData.append("latitude", coords.lat);
      submitData.append("longitude", coords.lng);

      if (formData.petDoc1) {
        submitData.append("pet_doc1", formData.petDoc1);
      }
      if (formData.petDoc2) {
        submitData.append("pet_doc2", formData.petDoc2);
      }

      await axios.post(
        "https://snoutiq.com/backend/api/auth/register",
        submitData,
        {
          headers: { "Content-Type": "multipart/form-data" },
        }
      );

      const res = await axios.post(
        "https://snoutiq.com/backend/api/auth/login",
        {
          login: formData.email,
          password: formData.password,
        }
      );
      const chatRoomToken = res.data.chat_room?.token || null;

      login(res.data.user, res.data.token, chatRoomToken);
      toast.success("Registration successful!");
      navigate("/dashboard");
    } catch (error) {
      toast.error(error.response?.data?.message || "Registration failed");
    } finally {
      setIsLoading((prev) => ({ ...prev, register: false }));
    }
  };

  if (user) return <Navigate to="/dashboard" replace />;

  return (
    <>
      <Header />
      <div className="min-h-screen bg-white bg-gradient-to-br from-blue-50 to-indigo-100 mt-12 flex items-center justify-center px-4 py-8">
        <div className="w-full max-w-sm sm:max-w-md">
          <Card className="text-center shadow-xl rounded-xl p-6 sm:p-8">
            {/* Logo */}
            <div className="mb-6">
              <img
                src={logo}
                alt="Snoutiq Logo"
                className="h-12 sm:h-14 mx-auto mb-3"
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

            {/* Stepper */}
            <div className="mb-6">
              <div className="flex justify-between items-start relative">
                <div className="absolute top-3 left-0 right-0 h-1 bg-gray-200 -z-10">
                  <div
                    className="h-1 bg-blue-600 transition-all duration-300"
                    style={{
                      width: `${(activeStep / (steps.length - 1)) * 100}%`,
                    }}
                  ></div>
                </div>

                {steps.map((label, index) => (
                  <div key={label} className="flex flex-col items-center z-10">
                    <div
                      className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium 
                    ${
                      activeStep >= index
                        ? "bg-blue-600 text-white"
                        : "bg-white border-2 border-gray-300 text-gray-500"
                    }`}
                    >
                      {activeStep > index ? (
                        <svg
                          className="w-4 h-4"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth="2"
                            d="M5 13l4 4L19 7"
                          ></path>
                        </svg>
                      ) : (
                        index + 1
                      )}
                    </div>
                    <p
                      className={`text-xs mt-2 font-medium max-w-20 ${
                        activeStep >= index ? "text-blue-600" : "text-gray-500"
                      }`}
                    >
                      {label}
                    </p>
                  </div>
                ))}
              </div>
            </div>

            {/* Form Fields */}
            <div className="space-y-4 mb-2">
              {activeStep === 0 && (
                <>
                  {/* Full Name */}
                  <div className="text-left">
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Full Name *
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
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                          />
                        </svg>
                      </div>
                      <input
                        type="text"
                        value={formData.fullName}
                        onChange={(e) =>
                          handleInputChange("fullName", e.target.value)
                        }
                        onBlur={() => handleBlur("fullName")}
                        className={`w-full pl-10 pr-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                          errors.fullName && touched.fullName
                            ? "border-red-500"
                            : "border-gray-300"
                        }`}
                        placeholder="Enter your full name"
                      />
                      {errors.fullName && touched.fullName && (
                        <p className="text-red-500 text-xs mt-1">
                          {errors.fullName}
                        </p>
                      )}
                    </div>
                  </div>

                  {/* Email */}
                  <div className="text-left">
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Email *
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
                        onChange={(e) =>
                          handleInputChange("email", e.target.value)
                        }
                        onBlur={() => handleBlur("email")}
                        className={`w-full pl-10 pr-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                          errors.email && touched.email
                            ? "border-red-500"
                            : "border-gray-300"
                        }`}
                        placeholder="Enter your email"
                      />
                      {errors.email && touched.email && (
                        <p className="text-red-500 text-xs mt-1">
                          {errors.email}
                        </p>
                      )}
                    </div>
                  </div>

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
                        <p className="text-red-500 text-xs mt-1">
                          {errors.mobileNumber}
                        </p>
                      )}
                    </div>
                  </div>
                </>
              )}

              {activeStep === 1 && (
                <>
                  <div className="text-left">
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Pet Name *
                    </label>
                    <input
                      type="text"
                      value={formData.petName}
                      onChange={(e) =>
                        handleInputChange("petName", e.target.value)
                      }
                      onBlur={() => handleBlur("petName")}
                      className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                        errors.petName && touched.petName
                          ? "border-red-500"
                          : "border-gray-300"
                      }`}
                      placeholder="Enter your pet's name"
                    />
                    {errors.petName && touched.petName && (
                      <p className="text-red-500 text-xs mt-1">
                        {errors.petName}
                      </p>
                    )}
                  </div>

                  <div className="text-left">
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Pet Gender *
                    </label>
                    <select
                      value={formData.petGender}
                      onChange={(e) =>
                        handleInputChange("petGender", e.target.value)
                      }
                      onBlur={() => handleBlur("petGender")}
                      className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                        errors.petGender && touched.petGender
                          ? "border-red-500"
                          : "border-gray-300"
                      }`}
                    >
                      <option value="">Select Gender</option>
                      <option value="Male">Male</option>
                      <option value="Female">Female</option>
                    </select>
                    {errors.petGender && touched.petGender && (
                      <p className="text-red-500 text-xs mt-1">
                        {errors.petGender}
                      </p>
                    )}
                  </div>

                  <div className="text-left">
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Pet Breed *
                    </label>
                    <input
                      value={formData.petBread}
                      onChange={(e) =>
                        handleInputChange("petBread", e.target.value)
                      }
                      onBlur={() => handleBlur("petBread")}
                      className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                        errors.petBread && touched.petBread
                          ? "border-red-500"
                          : "border-gray-300"
                      }`}
                      placeholder="Enter your pet's breed"
                    />
                    {errors.petBread && touched.petBread && (
                      <p className="text-red-500 text-xs mt-1">
                        {errors.petBread}
                      </p>
                    )}
                  </div>
                  <div className="text-left">
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Pet Age *
                    </label>
                    <input
                      type="number"
                      min="0"
                      value={formData.petAge}
                      onChange={(e) =>
                        handleInputChange("petAge", e.target.value)
                      }
                      onBlur={() => handleBlur("petAge")}
                      className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                        errors.petAge && touched.petAge
                          ? "border-red-500"
                          : "border-gray-300"
                      }`}
                      placeholder="Enter your pet's age"
                    />
                    {errors.petAge && touched.petAge && (
                      <p className="text-red-500 text-xs mt-1">
                        {errors.petAge}
                      </p>
                    )}
                  </div>

                  <div className="text-left">
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Prescription Document (Optional)
                    </label>
                    <div className="flex items-center justify-center w-full">
                      <label
                        className={`flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-lg cursor-pointer ${
                          formData.petDoc1
                            ? "border-blue-500 bg-blue-50"
                            : "border-gray-300 hover:border-gray-400"
                        }`}
                      >
                        <div className="flex flex-col items-center justify-center pt-5 pb-6">
                          <svg
                            className="w-8 h-8 mb-4 text-gray-500"
                            aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 20 16"
                          >
                            <path
                              stroke="currentColor"
                              strokeLinecap="round"
                              strokeLinejoin="round"
                              strokeWidth="2"
                              d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"
                            />
                          </svg>
                          <p className="mb-2 text-sm text-gray-500">
                            {formData.petDoc1 ? (
                              formData.petDoc1.name
                            ) : (
                              <span>Click to upload or drag and drop</span>
                            )}
                          </p>
                        </div>
                        <input
                          type="file"
                          className="hidden"
                          onChange={(e) =>
                            handleFileChange("petDoc1", e.target.files)
                          }
                        />
                      </label>
                    </div>
                  </div>

                  <div className="text-left">
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Medical Document (Optional)
                    </label>
                    <div className="flex items-center justify-center w-full">
                      <label
                        className={`flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-lg cursor-pointer ${
                          formData.petDoc2
                            ? "border-blue-500 bg-blue-50"
                            : "border-gray-300 hover:border-gray-400"
                        }`}
                      >
                        <div className="flex flex-col items-center justify-center pt-5 pb-6">
                          <svg
                            className="w-8 h-8 mb-4 text-gray-500"
                            aria-hidden="true"
                            xmlns="http://www.w3.org2000/svg"
                            fill="none"
                            viewBox="0 0 20 16"
                          >
                            <path
                              stroke="currentColor"
                              strokeLinecap="round"
                              strokeLinejoin="round"
                              strokeWidth="2"
                              d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"
                            />
                          </svg>
                          <p className="mb-2 text-sm text-gray-500">
                            {formData.petDoc2 ? (
                              formData.petDoc2.name
                            ) : (
                              <span>Click to upload or drag and drop</span>
                            )}
                          </p>
                        </div>
                        <input
                          type="file"
                          className="hidden"
                          onChange={(e) =>
                            handleFileChange("petDoc2", e.target.files)
                          }
                        />
                      </label>
                    </div>
                  </div>
                </>
              )}

              {activeStep === 2 && (
                <>
                  {/* Email OTP */}
                  <div className="text-left">
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Email OTP *
                    </label>
                    <div className="relative">
                      <input
                        type="text"
                        value={formData.emailOtp}
                        onChange={(e) =>
                          handleInputChange(
                            "emailOtp",
                            e.target.value.replace(/\D/g, "")
                          )
                        }
                        className="w-full pl-4 pr-28 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter email OTP"
                        maxLength="4"
                      />
                      {otpStatus === "valid" && (
                        <p className="text-green-600 text-sm mt-1">
                          ✅ Verified
                        </p>
                      )}
                      {otpStatus === "invalid" && (
                        <p className="text-red-600 text-sm mt-1">
                          ❌ Invalid OTP
                        </p>
                      )}
                      <button
                        onClick={handleSendEmailOtp}
                        disabled={isLoading.email || emailOtpCooldown > 0}
                        className={`absolute right-2 top-1/2 transform -translate-y-1/2 text-xs py-2 px-3 rounded ${
                          isLoading.email || emailOtpCooldown > 0
                            ? "bg-gray-200 text-gray-500 cursor-not-allowed"
                            : "bg-blue-100 text-blue-700 hover:bg-blue-200"
                        }`}
                      >
                        {isLoading.email
                          ? "Sending..."
                          : emailOtpCooldown > 0
                          ? `Resend (${emailOtpCooldown}s)`
                          : "Send OTP"}
                      </button>
                      {errors.emailOtp && touched.emailOtp && (
                        <p className="text-red-500 text-xs mt-1">
                          {errors.emailOtp}
                        </p>
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
                        onChange={(e) =>
                          handleInputChange("password", e.target.value)
                        }
                        onBlur={() => handleBlur("password")}
                        className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                          errors.password && touched.password
                            ? "border-red-500"
                            : "border-gray-300"
                        }`}
                        placeholder="Enter your password (min. 6 characters)"
                      />
                      {errors.password && touched.password && (
                        <p className="text-red-500 text-xs mt-1">
                          {errors.password}
                        </p>
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
                </>
              )}
            </div>

            {/* Navigation Buttons */}
            <div className="flex justify-between gap-3 mt-6">
              {activeStep > 0 ? (
                <button
                  onClick={handleBack}
                  className="flex-1 bg-gray-100 text-gray-800 font-medium py-3 px-6 rounded-lg hover:bg-gray-200 transition-colors"
                >
                  Back
                </button>
              ) : (
                <div className="flex-1"></div>
              )}

              {activeStep < steps.length - 1 ? (
                <button
                  onClick={handleNext}
                  className="flex-1 bg-blue-600 text-white font-medium py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors"
                >
                  Next
                </button>
              ) : (
                <button
                  onClick={handleSubmit}
                  disabled={isLoading.register}
                  className="flex-1 bg-blue-600 text-white font-medium py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors disabled:bg-blue-400 disabled:cursor-not-allowed"
                >
                  {isLoading.register ? (
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
                      Registering...
                    </span>
                  ) : (
                    "Register"
                  )}
                </button>
              )}
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

export default Register;