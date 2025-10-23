// import React, { useState, useContext } from "react";
// import { toast,Toaster } from "react-hot-toast";
// import { Link, Navigate, useNavigate } from "react-router-dom";
// import axios from "../axios";
// import Card from "../components/Card";
// import logo from "../assets/images/logo.webp";
// import { AuthContext } from "../auth/AuthContext";
// import Header from "../components/Header";
// import { GoogleOAuthProvider, GoogleLogin } from "@react-oauth/google";


// const Login = () => {
//   const [formData, setFormData] = useState({
//     email: "",
//     password: "",
//     role: "",
//   });
//   const [errors, setErrors] = useState({});
//   const [touched, setTouched] = useState({});
//   const [isLoading, setIsLoading] = useState(false);
//   const [showPassword, setShowPassword] = useState(false);
//   const [userType, setUserType] = useState("pet");

//   const navigate = useNavigate();
//   const { user, login } = useContext(AuthContext);

//   // map frontend userType → backend role
//   const getBackendRole = (type) => {
//     if (type === "pet") return "pet";
//     if (type === "vet") return "vet";
//     return "";
//   };

//   // handle input
//   const handleInputChange = (field, value) => {
//     setFormData((prev) => ({ ...prev, [field]: value }));
//     if (errors[field]) {
//       setErrors((prev) => ({ ...prev, [field]: "" }));
//     }
//   };

//   const handleBlur = (field) => {
//     setTouched((prev) => ({ ...prev, [field]: true }));
//     validateField(field, formData[field]);
//   };

//   // field validation
//   const validateField = (field, value) => {
//     let error = "";
//     switch (field) {
//       case "email":
//         if (!value) error = "Email is required";
//         else if (!/\S+@\S+\.\S+/.test(value))
//           error = "Please enter a valid email address";
//         break;
//       case "password":
//         if (!value) error = "Password is required";
//         break;
//       default:
//         break;
//     }
//     setErrors((prev) => ({ ...prev, [field]: error }));
//     return !error;
//   };

//   // full validation
//   const validateForm = () => {
//     const newErrors = {};
//     if (!formData.email) {
//       newErrors.email = "Email is required";
//     } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
//       newErrors.email = "Please enter a valid email address";
//     }
//     if (!formData.password) {
//       newErrors.password = "Password is required";
//     }
//     const backendRole = getBackendRole(userType);
//     if (!backendRole) {
//       newErrors.role = "Please select a role";
//     }
//     setErrors(newErrors);
//     return Object.keys(newErrors).length === 0;
//   };

// //   const handleSubmit = async (e) => {
// //   e.preventDefault();
// //   if (!validateForm()) return;

// //   setIsLoading(true);
// //   try {
// //     const res = await axios.post(
// //       "https://snoutiq.com/backend/api/auth/login",
// //       {
// //         login: formData.email,
// //         password: formData.password,
// //         role: getBackendRole(userType),
// //       }
// //     );

// //     const chatRoomToken = res.data.chat_room?.token || null;
// //     let { token, user } = res.data;

// //     if (token && user) {
// //       if (
// //         formData.email === "admin@gmail.com" && 
// //         formData.password === "5f4dcc3b5d"
// //       ) {
// //         user = { ...user, role: "super_admin" }; 
// //       }

// //       login(user, token, chatRoomToken);
// //       console.log("Login Response:", res);

// //       toast.success("Login successful!");
// //       // navigate("/dashboard");
// //          if (user.role === "vet") {
// //         navigate("/user-dashboard/bookings");
// //       } else {
// //         navigate("/dashboard"); 
// //         toast.info(`Welcome ${user.role}, dashboard is only for vets.`);
// //       }

// //     } else {
// //       toast.error("Invalid response from server.");
// //     }
// //   } catch (error) {
// //     const errorMessage =
// //       error.response?.data?.message ||
// //       "Login failed. Please check your credentials and try again.";
// //     toast.error(errorMessage);
// //   } finally {
// //     setIsLoading(false);
// //   }
// // };

//   // google login
  
//   const handleSubmit = async (e) => {
//   e.preventDefault();
//   if (!validateForm()) return;

//   setIsLoading(true);
//   try {
//     const res = await axios.post(
//       "https://snoutiq.com/backend/api/auth/login",
//       {
//         login: formData.email,
//         password: formData.password,
//         role: getBackendRole(userType),
//       }
//     );

//     console.log("✅ Login Response:", res);

//     const chatRoomToken = res.data.chat_room?.token || null;
//     const { token, user } = res.data;

//     if (token && user) {
//       let finalUser = { ...user };

//       // super admin override
//       if (
//         formData.email === "admin@gmail.com" &&
//         formData.password === "5f4dcc3b5d"
//       ) {
//         finalUser = { ...user, role: "super_admin" };
//       } 

//       // save session
//       login(finalUser, token, chatRoomToken);

//       toast.success("Login successful!");

//       // navigate according to role
//       if (finalUser.role === "vet") {
//         navigate("/user-dashboard/bookings");
//       } else {
//         navigate("/dashboard");
//        toast(`Welcome ${finalUser.role}, dashboard is only for vets.`);

//       }
//     } else {
//       toast.error("Invalid response from server.");
//     }
//   } catch (error) {
//     console.error("❌ Login Error Details:", error);

//     const errorMessage =
//       error.response?.data?.message ||
//       error.message ||
//       "Login failed. Please check your credentials and try again.";
//     toast.error(errorMessage);
//   } finally {
//     setIsLoading(false);
//   }
// };

//   const handleGoogleSuccess = async (credentialResponse) => {
//     setIsLoading(true);
//     try {
//       const base64Url = credentialResponse.credential.split(".")[1];
//       const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
//       const jsonPayload = decodeURIComponent(
//         atob(base64)
//           .split("")
//           .map((c) => "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2))
//           .join("")
//       );
//       const googleData = JSON.parse(jsonPayload);

//       const uniqueUserId = googleData.sub;
//       const email = googleData.email || "";

//       const res = await axios.post(
//         "https://snoutiq.com/backend/api/google-login",
//         {
//           email,
//           google_token: uniqueUserId,
//           role: 'pet',
//         }
//       );

//       const chatRoomToken = res.data.chat_room?.token || null;
//       const { token, user } = res.data;

//       if (token && user) {
//         login(user, token, chatRoomToken);
//         toast.success("Login successful!");
//         navigate("/dashboard");
//       } else {
//         toast.error("Invalid response from server.");
//       }
//     } catch (error) {
//       const errorMessage =
//         error.response?.data?.message || "Google login failed.";
//       toast.error(errorMessage);
//     } finally {
//       setIsLoading(false);
//     }
//   };

//   const handleUserTypeChange = (type) => {
//     setUserType(type);
//     handleInputChange("role", getBackendRole(type));
//   };

//   if (user) {
//     if (user.role === "vet") {
//       return <Navigate to="/user-dashboard/bookings" replace />;
//     } else {
//       return <Navigate to="/dashboard" replace />;
//     }
//   }

//   return (
//     <>
//       <Header />
//       <div className="min-h-screen bg-white bg-gradient-to-br from-blue-50 to-indigo-100 mt-12 flex items-center justify-center px-4 py-8">
//         <div className="w-full max-w-sm sm:max-w-md">
//           <Card className="text-center shadow-xl rounded-xl overflow-hidden p-6 sm:p-8">
//             {/* Logo */}
//             <div className="mb-6">
//               <img
//                 src={logo}
//                 alt="Snoutiq Logo"
//                 className="h-6 mx-auto mb-3"
//               />
//             </div>

//             {/* Welcome */}
//             <div className="mb-6 sm:mb-8">
//               <h1 className="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">
//                 Welcome Back!
//               </h1>
//               <p className="text-sm sm:text-base text-gray-600">
//                 Sign in to continue to your Snoutiq account
//               </p>
//             </div>

//             {/* Role Switch */}
//             <div className="mb-6">
//               <div className="flex bg-gray-100 rounded-lg p-1">
//                 <button
//                   onClick={() => handleUserTypeChange("pet")}
//                   className={`flex-1 py-2 px-4 rounded-md text-sm font-medium transition-colors ${
//                     userType === "pet"
//                       ? "bg-white text-blue-600 shadow-sm"
//                       : "text-gray-600 hover:text-gray-800"
//                   }`}
//                 >
//                   Pet Owner
//                 </button>
//                 <button
//                   onClick={() => handleUserTypeChange("vet")}
//                   className={`flex-1 py-2 px-4 rounded-md text-sm font-medium transition-colors ${
//                     userType === "vet"
//                       ? "bg-white text-blue-600 shadow-sm"
//                       : "text-gray-600 hover:text-gray-800"
//                   }`}
//                 >
//                   Veterinarian
//                 </button>
//               </div>
//             </div>

//             {/* Form */}
//                  {userType === "vet" && (
//             <form onSubmit={handleSubmit} className="space-y-4 mb-6">
//               {/* Email */}
//               <div className="text-left">
//                 <label className="block text-sm font-medium text-gray-700 mb-2">
//                   Email Address
//                 </label>
//                 <input
//                   type="email"
//                   value={formData.email}
//                   onChange={(e) => handleInputChange("email", e.target.value)}
//                   onBlur={() => handleBlur("email")}
//                   className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 ${
//                     errors.email && touched.email
//                       ? "border-red-500"
//                       : "border-gray-300"
//                   }`}
//                   placeholder="Enter your email address"
//                 />
//                 {errors.email && touched.email && (
//                   <p className="text-red-500 text-xs mt-1">{errors.email}</p>
//                 )}
//               </div>

//               {/* Password */}
//               <div className="text-left">
//                 <div className="flex justify-between items-center mb-2">
//                   <label className="block text-sm font-medium text-gray-700">
//                     Password
//                   </label>
//                   <Link
//                     to="/forgot-password"
//                     className="text-xs text-blue-600 hover:underline"
//                   >
//                     Forgot Password?
//                   </Link>
//                 </div>
//                 <div className="relative">
//                   <input
//                     type={showPassword ? "text" : "password"}
//                     value={formData.password}
//                     onChange={(e) =>
//                       handleInputChange("password", e.target.value)
//                     }
//                     onBlur={() => handleBlur("password")}
//                     className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 ${
//                       errors.password && touched.password
//                         ? "border-red-500"
//                         : "border-gray-300"
//                     }`}
//                     placeholder="Enter your password"
//                   />
//                   <button
//                     type="button"
//                     onClick={() => setShowPassword(!showPassword)}
//                     className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"
//                   >
//                     {showPassword ? "🙈" : "👁️"}
//                   </button>
//                 </div>
//                 {errors.password && touched.password && (
//                   <p className="text-red-500 text-xs mt-1">{errors.password}</p>
//                 )}
//               </div>

//               {/* Button */}
//               <button
//                 type="submit"
//                 disabled={isLoading}
//                 className="w-full mt-6 bg-blue-600 text-white font-medium py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors disabled:bg-blue-400"
//               >
//                 {isLoading ? "Logging in..." : "Login"}
//               </button>
//             </form>
//                  )}

//             {/* Google login */}
//             {userType === "pet" && (
//               <div className="w-full max-w-sm border rounded-xl shadow-md p-4 bg-white">
//                 <GoogleOAuthProvider
//                   clientId="325007826401-dhsrqhkpoeeei12gep3g1sneeg5880o7.apps.googleusercontent.com"
//                   onScriptLoadError={() =>
//                     console.error("Google OAuth script failed to load")
//                   }
//                   onScriptLoadSuccess={() =>
//                     console.log("Google OAuth script loaded")
//                   }
//                 >
//                   <GoogleLogin
//                     onSuccess={handleGoogleSuccess}
//                     onError={() => toast.error("Google login failed.")}
//                     useOneTap
//                     theme="filled_blue"
//                     size="large"
//                     text="continue_with"
//                     shape="rectangular"
//                   />
//                 </GoogleOAuthProvider>
//               </div>
//             )}

//             {/* Register */}
//             <div className="mt-6 pt-4 border-t border-gray-200">
//               <p className="text-gray-600 text-sm">
//                 Don't have an account?{" "}
//                 <Link
//                   to="/register"
//                   className="text-blue-600 hover:underline font-medium"
//                 >
//                   Create an account
//                 </Link>
//               </p>
//             </div>
//           </Card>
//         </div>
//       </div>
//     </>
//   );
// };

// export default Login;
import React, { useState, useContext } from "react";
import { toast, Toaster } from "react-hot-toast";
import { Link, Navigate, useNavigate } from "react-router-dom";
import axios from "../axios";
import logo from "../assets/images/logo.webp";
import { AuthContext } from "../auth/AuthContext";
import Header from "../components/Header";
import { GoogleOAuthProvider, GoogleLogin } from "@react-oauth/google";

// Background image - you can replace this with your actual image
const loginBackground = "https://images.unsplash.com/photo-1543466835-00a7907e9de1?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1974&q=80";

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

  const getBackendRole = (type) => {
    if (type === "pet") return "pet";
    if (type === "vet") return "vet";
    return "";
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

      console.log("✅ Login Response:", res);

      const chatRoomToken = res.data.chat_room?.token || null;
      const { token, user } = res.data;

      if (token && user) {
        let finalUser = { ...user };

        if (
          formData.email === "admin@gmail.com" &&
          formData.password === "5f4dcc3b5d"
        ) {
          finalUser = { ...user, role: "super_admin" };
        }

        login(finalUser, token, chatRoomToken);
        toast.success("Login successful!");

        if (finalUser.role === "vet") {
          navigate("/user-dashboard/bookings");
        } else {
          navigate("/dashboard");
          toast(`Welcome ${finalUser.role}, dashboard is only for vets.`);
        }
      } else {
        toast.error("Invalid response from server.");
      }
    } catch (error) {
      console.error("❌ Login Error Details:", error);

      const errorMessage =
        error.response?.data?.message ||
        error.message ||
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
      <div className="min-h-screen bg-white flex mt-12">
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
        <div className="w-full lg:w-1/2 flex items-center justify-center px-6 py-8 lg:px-12  bg-gradient-to-br from-blue-50 to-indigo-100 ">
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
                Welcome Back
              </h1>
              <p className="text-gray-600">
                Sign in to your Snoutiq account
              </p>
            </div>

            {/* Role Selector */}
            <div className="mb-8">
              <div className="flex bg-gray-100 rounded-xl p-1.5 border border-gray-200">
                <button
                  onClick={() => handleUserTypeChange("pet")}
                  className={`flex-1 py-3 px-4 rounded-lg text-sm font-semibold transition-all duration-200 ${
                    userType === "pet"
                      ? "bg-white text-blue-600 shadow-sm shadow-blue-100 border border-blue-100"
                      : "text-gray-600 hover:text-gray-800 hover:bg-gray-50"
                  }`}
                >
                  🐾 Pet Owner
                </button>
                <button
                  onClick={() => handleUserTypeChange("vet")}
                  className={`flex-1 py-3 px-4 rounded-lg text-sm font-semibold transition-all duration-200 ${
                    userType === "vet"
                      ? "bg-white text-blue-600 shadow-sm shadow-blue-100 border border-blue-100"
                      : "text-gray-600 hover:text-gray-800 hover:bg-gray-50"
                  }`}
                >
                  🩺 Veterinarian
                </button>
              </div>
            </div>

            {/* Veterinarian Login Form */}
            {userType === "vet" && (
              <form onSubmit={handleSubmit} className="space-y-6">
                {/* Email Field */}
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
                      {showPassword ? "🔒" : "👁️"}
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
            )}

            {/* Pet Owner Google Login */}
            {userType === "pet" && (
              <div className="space-y-6">
                {/* Divider */}
                <div className="relative">
                  <div className="absolute inset-0 flex items-center">
                    <div className="w-full border-t border-gray-200"></div>
                  </div>
                  <div className="relative flex justify-center text-sm">
                    <span className="px-4 bg-white text-gray-500 font-medium">
                      Continue with
                    </span>
                  </div>
                </div>

                {/* Google Login */}
                <div className="w-full">
                  <GoogleOAuthProvider
                    clientId="325007826401-dhsrqhkpoeeei12gep3g1sneeg5880o7.apps.googleusercontent.com"
                    onScriptLoadError={() =>
                      console.error("Google OAuth script failed to load")
                    }
                    onScriptLoadSuccess={() =>
                      console.log("Google OAuth script loaded")
                    }
                  >
                    <div className="flex justify-center">
                      <GoogleLogin
                        onSuccess={handleGoogleSuccess}
                        onError={() => toast.error("Google login failed")}
                        useOneTap
                        theme="filled_blue"
                        size="large"
                        text="continue_with"
                        shape="rectangular"
                        width="100%"
                        locale="en"
                      />
                    </div>
                  </GoogleOAuthProvider>
                </div>

                {/* Additional Info */}
                <div className="text-center">
                  <p className="text-gray-500 text-sm">
                    Secure login with Google
                  </p>
                </div>
              </div>
            )}

            {/* Sign Up Link */}
            <div className="mt-8 pt-6 border-t border-gray-100">
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