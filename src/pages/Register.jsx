import React, { useState, useEffect, useContext } from "react";
import { toast } from "react-hot-toast";
import { Link, Navigate, useNavigate } from "react-router-dom";
import Card from "../components/Card";
import logo from "../assets/images/logo.png";
import axios from "../axios";
import { AuthContext } from "../auth/AuthContext";
import Header from "../components/Header";
import { GoogleOAuthProvider, GoogleLogin } from "@react-oauth/google";

const Register = () => {
  const [userType, setUserType] = useState("pet_owner");
  const [formData, setFormData] = useState({
    fullName: "",
    email: "",
    mobileNumber: "",
    petType: "",
    petName: "",
    petGender: "",
    petAge: "",
    petBreed: "",
    petDoc1: null,
    petDoc2: null,
    password: "",
    confirmPassword: "",
    google_token: "",
  });

  const [errors, setErrors] = useState({});
  const [touched, setTouched] = useState({});
  const [isLoading, setIsLoading] = useState({
    email: false,
    register: false,
    breedImage: false,
  });
  const [activeStep, setActiveStep] = useState(0);
  const [locationAllowed, setLocationAllowed] = useState(null);
  const [coords, setCoords] = useState({ lat: null, lng: null });
  const [breedOptions, setBreedOptions] = useState([]);
  const [loadingBreeds, setLoadingBreeds] = useState(false);
  const [breedImage, setBreedImage] = useState(null);
  const [showBreedModal, setShowBreedModal] = useState(false);
  const [debugMode, setDebugMode] = useState(false);
  const [debugLogs, setDebugLogs] = useState([]);
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const { user, login, updateChatRoomToken } = useContext(AuthContext);

  const steps = ["Basic Details", "Pet Details", "Verification & Password"];

  // Debug logging function
  const addDebugLog = (message, data = null) => {
    if (debugMode) {
      const timestamp = new Date().toISOString();
      const logEntry = { timestamp, message, data };
      setDebugLogs((prev) => [...prev, logEntry].slice(-50)); // Keep last 50 logs
      console.log(`[DEBUG] ${timestamp}: ${message}`, data || "");
    }
  };

  // If user is a veterinarian, redirect to veterinarian registration
  useEffect(() => {
    if (userType === "veterinarian") {
      navigate("/vet-register");
    }
  }, [userType, navigate]);

  // If user is already logged in, redirect to dashboard
  if (user) {
    if (user.role === "vet") {
      return <Navigate to="/user-dashboard/bookings" replace />;
    } else {
      return <Navigate to="/dashboard" replace />;
    }
  }

  // ✅ Location Permission Check
  const checkLocationPermission = async () => {
    if (!navigator.permissions) {
      const msg = "Permissions API not supported in this browser";
      addDebugLog(msg);
      return null;
    }

    try {
      const result = await navigator.permissions.query({ name: "geolocation" });
      addDebugLog("Location permission status", result.state);

      if (result.state === "granted") {
        return true;
      } else if (result.state === "prompt") {
        return null;
      } else if (result.state === "denied") {
        return false;
      }
    } catch (error) {
      addDebugLog("Error checking location permission", error);
      return null;
    }
  };

  // ✅ Request Location Function
  const requestLocation = () => {
    return new Promise((resolve, reject) => {
      if (!navigator.geolocation) {
        const msg = "Geolocation not supported";
        addDebugLog(msg);
        toast.error(msg);
        reject(false);
        return;
      }

      addDebugLog("Requesting location permission");
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          addDebugLog("Location allowed", pos.coords);
          setLocationAllowed(true);
          setCoords({
            lat: pos.coords.latitude,
            lng: pos.coords.longitude,
          });
          resolve(true);
        },
        (err) => {
          addDebugLog("Location denied", err);
          setLocationAllowed(false);
          toast.error("⚠️ Please allow location to continue");
          reject(false);
        },
        { timeout: 10000, enableHighAccuracy: true }
      );
    });
  };

  const handleGoogleSuccess = async (credentialResponse) => {
    try {
      setLoading(true);
      addDebugLog("Google OAuth success", credentialResponse);

      // Decode Google JWT
      const base64Url = credentialResponse.credential.split(".")[1];
      const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
      const jsonPayload = decodeURIComponent(
        atob(base64)
          .split("")
          .map(function (c) {
            return "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2);
          })
          .join("")
      );

      const googleData = JSON.parse(jsonPayload);
      addDebugLog("Google user data", googleData);

      // Unique ID for backend
      const uniqueUserId = googleData.sub;
      addDebugLog("Google unique ID", uniqueUserId);

      setFormData((prev) => ({
        ...prev,
        fullName: googleData.name || "",
        email: googleData.email || "",
        mobileNumber: "",
        google_token: uniqueUserId,
      }));

      // Move user to Step 1 (Pet Details)
      setActiveStep(1);
      addDebugLog("Moved to step 1 after Google login");

      toast.success("Google login successful. Continue with pet details!");
    } catch (error) {
      addDebugLog("Google login failed", error);
      console.error("Google login failed:", error);
      toast.error("Google login failed. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  const handleGoogleError = () => {
    addDebugLog("Google OAuth failed");
    toast.error("Google login failed. Please try again.");
  };

  const handleNext = async () => {
    addDebugLog("Next button clicked", { activeStep, locationAllowed });

    // Request location before moving to pet details step
    if (activeStep === 1 && locationAllowed === null) {
      try {
        const granted = await requestLocation();
        if (!granted) return; // stop here if denied
        setLocationAllowed(true);
      } catch {
        return;
      }
    }

    if (validateStep()) {
      setActiveStep((prev) => prev + 1);
      addDebugLog("Moved to next step", { newStep: activeStep + 1 });
    } else {
      const stepFields =
        activeStep === 0
          ? ["fullName", "email"]
          : activeStep === 1
          ? ["petType", "petName", "petGender", "petAge", "petBreed"]
          : ["mobileNumber", "password", "confirmPassword"];

      const touchedUpdate = {};
      stepFields.forEach((f) => (touchedUpdate[f] = true));
      setTouched((prev) => ({ ...prev, ...touchedUpdate }));

      const firstErrorKey = Object.keys(errors)[0];
      if (firstErrorKey) {
        addDebugLog("Validation error", {
          field: firstErrorKey,
          error: errors[firstErrorKey],
        });
        toast.error(errors[firstErrorKey]);
      }
    }
  };

  // ✅ Run Once on Mount
  useEffect(() => {
    addDebugLog("Component mounted");

    checkLocationPermission().then((status) => {
      addDebugLog("Location permission check completed", { status });

      if (status === true) {
        setLocationAllowed(true);
        requestLocation();
      } else if (status === false) {
        setLocationAllowed(false);
        toast.error("Please enable location access in browser settings");
      }
    });

    // Enable debug mode with triple click on logo
    const handleDebugActivation = () => {
      setDebugMode((prev) => {
        const newMode = !prev;
        toast(newMode ? "Debug mode enabled" : "Debug mode disabled");
        return newMode;
      });
    };

    let clickCount = 0;
    let lastClickTime = 0;

    const debugClickListener = (e) => {
      const currentTime = new Date().getTime();
      if (currentTime - lastClickTime > 300) {
        clickCount = 0;
      }

      clickCount++;
      lastClickTime = currentTime;

      if (clickCount >= 3) {
        handleDebugActivation();
        clickCount = 0;
      }
    };

    const logoElement = document.querySelector(".debug-logo");
    if (logoElement) {
      logoElement.addEventListener("click", debugClickListener);
    }

    return () => {
      if (logoElement) {
        logoElement.removeEventListener("click", debugClickListener);
      }
    };
  }, []);

  const handleInputChange = (field, value) => {
    addDebugLog("Input changed", { field, value });
    setFormData((prev) => ({ ...prev, [field]: value }));

    if (errors[field]) {
      setErrors((prev) => ({ ...prev, [field]: "" }));
    }
    if (field === "petBreed" && value && formData.petType === "Dog") {
      fetchBreedImage(value);
    } else {
      setBreedImage(null);
    }
  };

  const handleBlur = (field) => {
    addDebugLog("Input blurred", { field });
    setTouched((prev) => ({ ...prev, [field]: true }));
  };

  const toTitleCase = (str) =>
    str
      .split(" ")
      .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
      .join(" ");

  useEffect(() => {
    const fetchBreeds = async () => {
      if (formData.petType === "Dog") {
        setLoadingBreeds(true);
        addDebugLog("Fetching dog breeds");

        try {
          const res = await axios.get(
            "https://snoutiq.com/backend/api/dog-breeds/all"
          );

          addDebugLog("Dog breeds API response", res.data);

          if (res.data && res.data.breeds) {
            const breedsData = res.data.breeds;
            const breedList = [];

            Object.entries(breedsData).forEach(([breed, subBreeds]) => {
              if (Array.isArray(subBreeds) && subBreeds.length > 0) {
                subBreeds.forEach((sub) => {
                  breedList.push(`${sub} ${breed}`);
                });
              } else {
                breedList.push(breed);
              }
            });

            setBreedOptions(breedList.sort());
            addDebugLog("Processed breed options", breedList);
          } else {
            const msg = "Invalid response format for breeds";
            addDebugLog(msg, res.data);
            console.error(msg);
            setBreedOptions([]);
          }
        } catch (err) {
          addDebugLog("Failed to fetch breeds", err);
          console.error("Failed to fetch breeds", err);
          toast.error("Failed to load dog breeds");
          setBreedOptions([]);
        } finally {
          setLoadingBreeds(false);
        }
      } else if (formData.petType === "Cat") {
        addDebugLog("Setting cat breeds");
        // Static cat breeds
        setBreedOptions([
          "Siamese",
          "Persian",
          "Maine Coon",
          "Bengal",
          "Sphynx",
          "British Shorthair",
          "Ragdoll",
          "Abyssinian",
          "Scottish Fold",
        ]);
      } else {
        setBreedOptions([]);
      }
    };

    if (formData.petType) {
      fetchBreeds();
    }
  }, [formData.petType]);

  const validateStep = (step = activeStep) => {
    const newErrors = {};

    if (step === 0) {
      if (!formData.fullName.trim()) newErrors.fullName = "Name is required";
      if (!formData.email.includes("@"))
        newErrors.email = "Valid email is required";
    }

    if (step === 1) {
      if (!formData.petType) newErrors.petType = "Pet type is required";
      if (!formData.petName.trim()) newErrors.petName = "Pet name is required";
      if (!formData.petGender) newErrors.petGender = "Pet gender is required";
      if (!formData.petAge || formData.petAge <= 0)
        newErrors.petAge = "Valid pet age is required";
      if (!formData.petBreed) newErrors.petBreed = "Pet breed is required";
    }

    if (step === 2) {
      if (!/^\d{10}$/.test(formData.mobileNumber))
        newErrors.mobileNumber = "Valid 10-digit mobile number required";
      if (formData.password.length < 6)
        newErrors.password = "Password must be at least 6 characters";
      if (formData.password !== formData.confirmPassword) {
        newErrors.confirmPassword = "Passwords do not match";
      }
    }

    addDebugLog("Validation results", { step, errors: newErrors });
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleBack = () => {
    addDebugLog("Back button clicked", { fromStep: activeStep });
    setActiveStep((prev) => prev - 1);
  };

  const fetchBreedImage = async (breed) => {
    setIsLoading((prev) => ({ ...prev, breedImage: true }));
    addDebugLog("Fetching breed image", { breed });

    try {
      // Extract the main breed name (remove sub-breed if present)
      const breedName = breed.split(" ").reverse()[0].toLowerCase();

      const res = await axios.get(
        `https://snoutiq.com/backend/api/dog-breed/${breedName}`
      );

      addDebugLog("Breed image API response", res.data);

      if (res.data.status === "success" && res.data.image) {
        setBreedImage(res.data.image);
      } else {
        setBreedImage(null);
      }
    } catch (err) {
      addDebugLog("Failed to fetch breed image", err);
      console.error("Failed to fetch breed image", err);
      setBreedImage(null);
    } finally {
      setIsLoading((prev) => ({ ...prev, breedImage: false }));
    }
  };

  const compressImage = (file, maxSizeKB = 50, maxWidthOrHeight = 800) => {
    addDebugLog("Compressing image", { file: file.name, size: file.size });

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
                addDebugLog("Image compression iteration", {
                  quality,
                  size: blob.size / 1024,
                  target: maxSizeKB,
                });

                if (blob.size / 1024 > maxSizeKB && quality > 0.1) {
                  quality -= 0.1;
                  tryCompress();
                } else {
                  const compressedFile = new File([blob], file.name, {
                    type: "image/jpeg",
                  });
                  addDebugLog("Image compression complete", {
                    original: file.size / 1024,
                    compressed: compressedFile.size / 1024,
                  });
                  resolve(compressedFile);
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
    addDebugLog("File selected", { field, file: files[0]?.name });

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
    addDebugLog("Form submission started", formData);

    if (!validateStep()) return;

    if (!coords.lat || !coords.lng) {
      addDebugLog("Location not available", coords);
      toast.error("⚠️ Please allow location to continue");
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
      submitData.append("pet_type", formData.petType);
      submitData.append("pet_name", formData.petName);
      submitData.append("pet_gender", formData.petGender);
      submitData.append("pet_age", formData.petAge);
      submitData.append("breed", formData.petBreed);
      submitData.append("latitude", coords.lat);
      submitData.append("longitude", coords.lng);

      if (formData.google_token)
        submitData.append("google_token", formData.google_token);
      if (formData.petDoc1) submitData.append("pet_doc1", formData.petDoc1);
      if (formData.petDoc2) submitData.append("pet_doc2", formData.petDoc2);

      addDebugLog(
        "Sending registration request",
        Object.fromEntries(submitData)
      );

      const res = await axios.post(
        "https://snoutiq.com/backend/api/auth/register",
        submitData,
        { headers: { "Content-Type": "multipart/form-data" } }
      );

      addDebugLog("Registration successful, attempting login");

      const loginRes = await axios.post(
        "https://snoutiq.com/backend/api/auth/login",
        {
          login: formData.email,
          password: formData.password,
        }
      );

      const chatRoomToken = loginRes.data.chat_room?.token || null;
      addDebugLog("Login successful", {
        user: loginRes.data.user,
        token: loginRes.data.token,
      });

      login(loginRes.data.user, loginRes.data.token, chatRoomToken);
      toast.success("Registration successful!");
      navigate("/dashboard");
    } catch (error) {
      const message = error.response?.data?.message || "Registration failed";
      addDebugLog("Registration failed", message);

      if (message.includes("unique mobile or email")) {
        toast.error("⚠️ Email or mobile number already exists!");
      } else {
        toast.error(message);
      }
    } finally {
      setIsLoading((prev) => ({ ...prev, register: false }));
    }
  };

  return (
    <>
      <Header />
      <div className="min-h-screen bg-white bg-gradient-to-br from-blue-50 to-indigo-100 mt-12 flex items-center justify-center px-4 py-8">
        <div className="w-full max-w-sm sm:max-w-md">
          <Card className="text-center shadow-xl rounded-xl p-6 sm:p-8">
            {/* Logo with debug activation */}
            <div className="mb-6">
              <img
                src={logo}
                alt="Snoutiq Logo"
                className="h-12 sm:h-14 mx-auto mb-3 debug-logo cursor-pointer"
              />
            </div>

            {/* Debug Panel */}
            {debugMode && (
              <div className="mb-4 p-3 bg-gray-100 rounded-lg text-left max-h-40 overflow-y-auto">
                <h3 className="font-bold text-sm mb-2">Debug Logs</h3>
                {debugLogs.length === 0 ? (
                  <p className="text-xs">
                    No logs yet. Interactions will appear here.
                  </p>
                ) : (
                  debugLogs.map((log, index) => (
                    <div
                      key={index}
                      className="text-xs mb-1 border-b border-gray-200 pb-1"
                    >
                      <span className="text-gray-500">
                        [{log.timestamp.split("T")[1].split(".")[0]}]
                      </span>
                      <span className="font-medium"> {log.message}</span>
                      {log.data && (
                        <span className="text-gray-600">
                          :{" "}
                          {typeof log.data === "object"
                            ? JSON.stringify(log.data)
                            : log.data}
                        </span>
                      )}
                    </div>
                  ))
                )}
                <button
                  className="mt-2 text-xs text-blue-600"
                  onClick={() => setDebugLogs([])}
                >
                  Clear logs
                </button>
              </div>
            )}

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
                  <div className="flex justify-center">
                    <div className="w-full max-w-sm border rounded-xl shadow-md p-6 bg-white">
                      <GoogleOAuthProvider
                        clientId='325007826401-dhsrqhkpoeeei12gep3g1sneeg5880o7.apps.googleusercontent.com'
                        onScriptLoadError={() => {
                          addDebugLog("Google OAuth script failed to load");
                          console.error("Google OAuth script failed to load");
                        }}
                        onScriptLoadSuccess={() => {
                          addDebugLog("Google OAuth script loaded");
                          console.log("Google OAuth script loaded");
                        }}
                      >
                        <GoogleLogin
                          onSuccess={handleGoogleSuccess}
                          onError={handleGoogleError}
                          useOneTap
                          theme="filled_blue"
                          size="large"
                          text="continue_with"
                          shape="rectangular"
                        />
                      </GoogleOAuthProvider>
                    </div>
                  </div>
                </>
              )}

              {activeStep === 1 && (
                <>
                  {/* Pet Type */}
                  <div className="text-left">
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Pet Type *
                    </label>
                    <select
                      value={formData.petType}
                      onChange={(e) =>
                        handleInputChange("petType", e.target.value)
                      }
                      onBlur={() => handleBlur("petType")}
                      className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                        errors.petType && touched.petType
                          ? "border-red-500"
                          : "border-gray-300"
                      }`}
                    >
                      <option value="">Select Pet Type</option>
                      <option value="Dog">Dog</option>
                      <option value="Cat">Cat</option>
                      <option value="Other">Other</option>
                    </select>
                    {errors.petType && touched.petType && (
                      <p className="text-red-500 text-xs mt-1">
                        {errors.petType}
                      </p>
                    )}
                  </div>

                  <div className="text-left">
                    <label className="block text-sm font-medium text-gray-700 mb极狐">
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
                      Pet Breed *
                    </label>
                    {loadingBreeds ? (
                      <div className="flex items-center justify-center py-3">
                        <svg
                          className="animate-spin h-5 w-5 mr-2 text-blue-600"
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
                        <span>Loading breeds...</span>
                      </div>
                    ) : (
                      <select
                        value={formData.petBreed}
                        onChange={(e) =>
                          handleInputChange("petBreed", e.target.value)
                        }
                        onBlur={() => handleBlur("petBreed")}
                        className={`w-full px-极狐 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                          errors.petBreed && touched.petBreed
                            ? "border-red-500"
                            : "border-gray-300"
                        }`}
                        disabled={
                          !formData.petType || breedOptions.length === 0
                        }
                      >
                        <option value="">Select Breed</option>
                        {breedOptions.map((breed, index) => (
                          <option key={index} value={breed}>
                            {toTitleCase(breed)}
                          </option>
                        ))}
                      </select>
                    )}
                    {errors.petBreed && touched.petBreed && (
                      <p className="text-red-500 text-xs mt-1">
                        {errors.petBreed}
                      </p>
                    )}
                  </div>

                  {formData.petType === "Dog" && formData.petBreed && (
                    <div className="text-left">
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Breed Image
                      </label>
                      {isLoading.breedImage ? (
                        <div className="flex items-center justify-center py-3">
                          <svg
                            className="animate-spin h-5 w-5 mr-2 text-blue-600"
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
                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 极狐 7.938l3-2.647z"
                            ></path>
                          </svg>
                          <span>Loading image...</span>
                        </div>
                      ) : breedImage ? (
                        <div
                          className="cursor-pointer"
                          onClick={() => setShowBreedModal(true)}
                        >
                          <img
                            src={breedImage}
                            alt={formData.petBreed}
                            className="w-24 h-24 object-cover rounded-lg shadow-md"
                          />
                          <p className="text-xs text-gray-500 mt-1">
                            Click to view larger image
                          </p>
                        </div>
                      ) : (
                        <p className="text-sm text-gray-500">
                          No image available for this breed
                        </p>
                      )}
                    </div>
                  )}

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
                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21极狐2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
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
                  // disabled={isLoading.register}
                  disabled={isLoading.register || !coords.lat || !coords.lng}
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
