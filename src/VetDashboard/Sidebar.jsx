// import React, { useState, useEffect, useContext, useRef } from "react";
// import { useNavigate, useLocation } from "react-router-dom";
// import { AuthContext } from "../auth/AuthContext";
// import RingtonePopup from '../pages/RingtonePopup';
// // Heroicons (outline set)
// import {
//   Bars3Icon,
//   XMarkIcon,
//   UserIcon,
//   ArrowRightOnRectangleIcon,
//   VideoCameraIcon,
//   HomeIcon,
//   CalendarIcon,
//   CogIcon,
//   HeartIcon,
//   UserGroupIcon,
//   ExclamationTriangleIcon,
//   StarIcon,
// } from "@heroicons/react/24/outline";
// import { socket } from '../pages/socket' 


// const HeaderWithSidebar = ({ children, doctorId = 501 }) => {
//   const [isDropdownOpen, setIsDropdownOpen] = useState(false);
//   const [isSidebarOpen, setIsSidebarOpen] = useState(false);
//   const [activeNavItem, setActiveNavItem] = useState("Dashboard");
//   const [isLive, setIsLive] = useState(false);

//   const navigate = useNavigate();
//   const location = useLocation();
//   const { user } = useContext(AuthContext);
//   const [incomingCall, setIncomingCall] = useState(null);
//   const hasListeners = useRef(false);

//  useEffect(() => {
//     const savedLive = localStorage.getItem("doctorLive");
//     if (savedLive === "true") goLive();
//   }, []);

//   // Connect doctor socket
//   const goLive = () => {
//     if (!socket.connected) socket.connect();
//     socket.emit("join-doctor", doctorId); // join doctor-specific room
//     setIsLive(true);
//     localStorage.setItem("doctorLive", "true");
//     console.log("🟢 Doctor is live");
//   };

//   const goOffline = () => {
//     socket.emit("leave-doctor", doctorId);
//     socket.disconnect();
//     setIsLive(false);
//     setIncomingCall(null);
//     localStorage.removeItem("doctorLive");
//     console.log("⚪ Doctor is offline");
//   };

//   // Toggle live status button
//   const handleToggle = () => (isLive ? goOffline() : goLive());

//   // Socket listeners (setup once)
//   useEffect(() => {
//     if (hasListeners.current) return;
//     hasListeners.current = true;

//     socket.on("connect", () => {
//       console.log("✅ Socket connected:", socket.id);
//       if (localStorage.getItem("doctorLive") === "true") {
//         socket.emit("join-doctor", doctorId);
//       }
//     });

//     socket.on("disconnect", () => console.log("❌ Socket disconnected"));

//     socket.on("call-requested", (callData) => {
//       console.log("📞 Incoming call data received:", callData);
//       setIncomingCall({
//         id: callData.callId,
//         patientId: callData.patientId,
//         channel: callData.channel,
//         timestamp: Date.now(),
//         doctorId,
//       });
//     });

//     return () => {
//       socket.off("connect");
//       socket.off("disconnect");
//       socket.off("call-requested");
//     };
//   }, [doctorId]);
  
//   // ✅ Navigation config with correct heroicons
//   const navConfig = {
//     common1: [
//       {
//         category: "Home",
//         items: [
//           {
//             text: "Home",
//             icon: <HomeIcon className="w-5 h-5" />,
//             path: "/dashboard",
//           },
//         ],
//       },
//     ],
//     super_admin: [
//       {
//         category: "Super Admin",
//         items: [
//           {
//             text: "Pet Owner",
//             icon: <StarIcon className="w-5 h-5" />,
//             path: "/user-dashboard/pet-owner",
//           },
//           {
//             text: "Vet Owner",
//             icon: <CogIcon className="w-5 h-5" />,
//             path: "/user-dashboard/vet-owner",
//           },
//         ],
//       },
//     ],
//     pet: [
//       {
//         category: "Pet Owner",
//         items: [
//           {
//             text: "My Pets",
//             icon: <HeartIcon className="w-5 h-5" />,
//             path: "/user-dashboard/pet-info",
//           },
//           {
//             text: "Health Records",
//             icon: <HeartIcon className="w-5 h-5" />,
//             path: "/user-dashboard/pet-health",
//           },
//           {
//             text: "Daily Care",
//             icon: <HeartIcon className="w-5 h-5" />,
//             path: "/user-dashboard/pet-daily-care",
//           },
//           {
//             text: "My Bookings",
//             icon: <CalendarIcon className="w-5 h-5" />,
//             path: "/user-dashboard/my-bookings",
//           },
//           {
//             text: "History",
//             icon: <UserGroupIcon className="w-5 h-5" />,
//             path: "/user-dashboard/history",
//           },
//           {
//             text: "Vaccinations",
//             icon: <ExclamationTriangleIcon className="w-5 h-5" />,
//             path: "/user-dashboard/vaccination-tracker",
//           },
//           {
//             text: "Medication Tracker",
//             icon: <ExclamationTriangleIcon className="w-5 h-5" />,
//             path: "/user-dashboard/medical-tracker",
//           },
//           {
//             text: "Weight Monitoring",
//             icon: <ExclamationTriangleIcon className="w-5 h-5" />,
//             path: "/user-dashboard/weight-monitoring",
//           },
//           {
//             text: "Vet Visits",
//             icon: <ExclamationTriangleIcon className="w-5 h-5" />,
//             path: "/user-dashboard/vet-visits",
//           },
//           {
//             text: "Photo Timeline",
//             icon: <ExclamationTriangleIcon className="w-5 h-5" />,
//             path: "/user-dashboard/photo-timeline",
//           },
//           {
//             text: "Emergency Contacts",
//             icon: <ExclamationTriangleIcon className="w-5 h-5" />,
//             path: "/user-dashboard/emergency-contacts",
//           },
//         ],
//       },
//     ],
//     vet: [
//       {
//         category: "Doctor",
//         items: [
//           {
//             text: "Booking Requests",
//             icon: <CalendarIcon className="w-5 h-5" />,
//             path: "/user-dashboard/bookings",
//           },
//           {
//             text: "Profile & Settings",
//             icon: <UserIcon className="w-5 h-5" />,
//             path: "/user-dashboard/vet-profile",
//           },
//           {
//             text: "My Document",
//             icon: <StarIcon className="w-5 h-5" />,
//             path: "/user-dashboard/vet-document",
//           },
//           {
//             text: "Payment",
//             icon: <ExclamationTriangleIcon className="w-5 h-5" />,
//             path: "/user-dashboard/vet-payment",
//           },
//         ],
//       },
//     ],
//     common: [
//       {
//         category: "Account",
//         items: [
//           {
//             text: "Ratings & Reviews",
//             icon: <StarIcon className="w-5 h-5" />,
//             path: "/user-dashboard/rating",
//           },
//           {
//             text: "Support",
//             icon: <CogIcon className="w-5 h-5" />,
//             path: "/user-dashboard/support",
//           },
//         ],
//       },
//     ],
//   };

//   const mainNavItems = [
//     ...(user?.role === "super_admin" || user?.role === "pet"
//       ? navConfig.common1 || []
//       : []),
//     ...(user?.role === "super_admin" ? navConfig.super_admin : []),
//     ...(user?.role === "pet" ? navConfig.pet : []),
//     ...(user?.role === "vet" ? navConfig.vet : []),
//     ...(navConfig.common || []),
//   ];

//   // set active nav item
//   useEffect(() => {
//     const currentPath = location.pathname;
//     for (const category of mainNavItems) {
//       const item = category.items.find((item) => item.path === currentPath);
//       if (item) {
//         setActiveNavItem(item.text);
//         break;
//       }
//     }
//   }, [location.pathname, mainNavItems]);

//   // toggle
//   const toggleDropdown = () => setIsDropdownOpen(!isDropdownOpen);
//   const toggleSidebar = () => setIsSidebarOpen(!isSidebarOpen);
//   const toggleLiveStatus = () => setIsLive(!isLive);

//   const handleNavItemClick = (path, text) => {
//     navigate(path);
//     setActiveNavItem(text);
//     if (window.innerWidth < 1024) setIsSidebarOpen(false);
//   };

//   const handleLogin = () => navigate("/login");
//   const handleRegister = () =>
//     navigate(
//       "/register?utm_source=facebook&utm_medium=paid_social&utm_campaign=pet_emergency_test1&utm_content=chat_conversion"
//     );
//   const handleLogout = () => {
//     localStorage.clear();
//     navigate("/");
//     window.location.reload();
//   };

//   return (
//     <div className="flex h-screen bg-gray-50">
//       {/* Desktop Sidebar */}
//       <div className="hidden lg:flex lg:flex-shrink-0">
//         <div className="w-64 flex flex-col bg-gradient-to-b from-indigo-800 to-purple-700 text-white">
//           <div className="flex items-center justify-between h-16 px-4 border-b border-indigo-700">
//             <div
//               className="text-xl font-bold cursor-pointer"
//               onClick={() => navigate(user ? "/dashboard" : "/")}
//             >
//               SnoutIQ
//             </div>
//           </div>

//           <nav className="flex-1 px-2 py-4 overflow-y-auto">
//             {mainNavItems.map((category, i) => (
//               <div key={i} className="mb-6">
//                 <h3 className="px-3 text-xs font-semibold text-indigo-300 uppercase tracking-wider mb-2">
//                   {category.category}
//                 </h3>
//                 <div className="space-y-1">
//                   {category.items.map((item) => {
//                     const isActive = location.pathname === item.path;
//                     return (
//                       <button
//                         key={item.text}
//                         onClick={() => handleNavItemClick(item.path, item.text)}
//                         className={`flex items-center w-full px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ${
//                           isActive
//                             ? "bg-white text-indigo-700 shadow-md"
//                             : "text-indigo-100 hover:bg-indigo-700 hover:text-white"
//                         }`}
//                       >
//                         <span className="mr-3">{item.icon}</span>
//                         {item.text}
//                       </button>
//                     );
//                   })}
//                 </div>
//               </div>
//             ))}
//           </nav>
//         </div>
//       </div>

//       {/* Main content with header */}
//       <div className="flex flex-col flex-1 overflow-hidden">
//         <header className="bg-white border-b border-gray-200 shadow-sm">
//           <div className="flex items-center justify-between px-4 py-3 h-16">
//             <div className="flex items-center">
//               <button
//                 type="button"
//                 className="px-3 py-2 rounded-md text-gray-500 hover:text-indigo-600 lg:hidden"
//                 onClick={toggleSidebar}
//               >
//                 <Bars3Icon className="h-6 w-6" />
//               </button>
//               <h1 className="ml-2 text-xl font-semibold text-gray-800 lg:ml-4">
//                 {activeNavItem}
//               </h1>
//             </div>

//             <div className="flex items-center space-x-4">
//               {user?.business_status && (
//                 <div className="flex items-center space-x-2">
//                   <span className="text-sm text-gray-600 hidden md:block">
//                     {isLive ? "Live" : "Offline"}
//                   </span>
//                   {/* <button
//                     onClick={toggleLiveStatus}
//                     className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 ${
//                       isLive ? "bg-green-500" : "bg-gray-300"
//                     }`}
//                   >
//                     <span
//                       className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
//                         isLive ? "translate-x-6" : "translate-x-1"
//                       }`}
//                     />
//                   </button> */}
//                   <button
//                     onClick={handleToggle}
//                     style={{
//                       background: isLive ? "green" : "gray",
//                       color: "white",
//                       padding: "8px 16px",
//                       borderRadius: "6px",
//                     }}
//                   >
//                     {isLive ? "🟢 Live" : "⚪ Offline"}
//                   </button>
//                   <VideoCameraIcon
//                     className={`h-5 w-5 ${
//                       isLive ? "text-green-500" : "text-gray-400"
//                     }`}
//                   />

//                 </div>
//               )}

//               {!user ? (
//                 <div className="flex space-x-2">
//                   <button
//                     onClick={handleRegister}
//                     className="px-4 py-2 text-sm font-medium text-indigo-600 border border-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors"
//                   >
//                     Register
//                   </button>
//                   <button
//                     onClick={handleLogin}
//                     className="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors"
//                   >
//                     Login
//                   </button>
//                 </div>
//               ) : (
//                 <div className="relative">
//                   <button
//                     className="flex items-center max-w-xs rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
//                     onClick={toggleDropdown}
//                   >
//                     <div className="flex items-center space-x-3">
//                       <div className="w-8 h-8 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full flex items-center justify-center">
//                         <UserIcon className="w-4 h-4 text-white" />
//                       </div>
//                       <div className="hidden md:flex flex-col items-start">
//                         <div className="text-sm font-medium text-gray-800">
//                           {user.name || user.business_status}
//                         </div>
//                         <div className="text-xs text-gray-500">
//                           {user.role || "Pet Owner"}
//                         </div>
//                       </div>
//                     </div>
//                   </button>

//                   {isDropdownOpen && (
//                     <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 z-50 border border-gray-200">
//                       <button
//                         onClick={handleLogout}
//                         className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
//                       >
//                         <ArrowRightOnRectangleIcon className="w-4 h-4 mr-2" />
//                         Logout
//                       </button>
//                     </div>
//                   )}
//                 </div>
//               )}
//             </div>
//           </div>
//         </header>

//         {/* <main className="flex-1 overflow-y-auto bg-gray-50 p-6">
//           {user && isLive && (
//             <div className="mb-6 flex items-center rounded-lg border border-green-200 bg-green-50 p-4">
//               <div className="relative flex-shrink-0">
//                 <span className="absolute inline-flex h-3 w-3 animate-ping rounded-full bg-green-500 opacity-75"></span>
//                 <span className="relative inline-flex h-3 w-3 rounded-full bg-green-500"></span>
//               </div>
//               <div className="ml-3">
//                 <p className="text-sm font-medium text-green-800">
//                   You are currently live
//                 </p>
//                 <p className="text-sm text-green-600">
//                   Video calls can be received. Toggle off when you're
//                   unavailable.
//                 </p>
//               </div>
//             </div>
//           )}

//           <div>
//             <div>{children}</div>
//           </div>
//         </main> */}
//          <main className="flex-1 overflow-y-auto bg-gray-50 p-6">
//           {incomingCall && (
//             <RingtonePopup
//               call={incomingCall}
//               doctorId={doctorId}
//               onClose={() => setIncomingCall(null)}
//             />
//           )}

//           {children}
//         </main>
//       </div>

//       {/* Mobile Sidebar */}
//       {isSidebarOpen && (
//         <div className="fixed inset-0 z-50 flex lg:hidden">
//           <div
//             className="fixed inset-0 bg-gray-900 bg-opacity-50"
//             onClick={() => setIsSidebarOpen(false)}
//           ></div>

//           <div className="relative flex flex-col w-64 max-w-xs bg-gradient-to-b from-indigo-800 to-purple-700 text-white z-50">
//             <div className="flex items-center justify-between h-16 px-4 border-b border-indigo-700">
//               <div className="text-xl font-bold">SnoutIQ</div>
//               <button
//                 onClick={() => setIsSidebarOpen(false)}
//                 className="text-white hover:text-gray-200"
//               >
//                 <XMarkIcon className="h-6 w-6" />
//               </button>
//             </div>

//             <nav className="flex-1 px-2 py-4 overflow-y-auto">
//               {mainNavItems.map((category, i) => (
//                 <div key={i} className="mb-6">
//                   <h3 className="px-3 text-xs font-semibold text-indigo-300 uppercase tracking-wider mb-2">
//                     {category.category}
//                   </h3>
//                   <div className="space-y-1">
//                     {category.items.map((item) => {
//                       const isActive = location.pathname === item.path;
//                       return (
//                         <button
//                           key={item.text}
//                           onClick={() =>
//                             handleNavItemClick(item.path, item.text)
//                           }
//                           className={`flex items-center w-full px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ${
//                             isActive
//                               ? "bg-white text-indigo-700 shadow-md"
//                               : "text-indigo-100 hover:bg-indigo-700 hover:text-white"
//                           }`}
//                         >
//                           <span className="mr-3">{item.icon}</span>
//                           {item.text}
//                         </button>
//                       );
//                     })}
//                   </div>
//                 </div>
//               ))}
//             </nav>
//           </div>
//         </div>
//       )}
//     </div>
//   );
// };

// export default HeaderWithSidebar;

import React, { useState, useEffect, useContext, useRef } from "react";
import { useNavigate, useLocation } from "react-router-dom";
import { AuthContext } from "../auth/AuthContext";
import RingtonePopup from '../pages/RingtonePopup';
// Heroicons (outline set)
import {
  Bars3Icon,
  XMarkIcon,
  UserIcon,
  ArrowRightOnRectangleIcon,
  VideoCameraIcon,
  HomeIcon,
  CalendarIcon,
  CogIcon,
  HeartIcon,
  UserGroupIcon,
  ExclamationTriangleIcon,
  StarIcon,
} from "@heroicons/react/24/outline";
import { socket } from '../pages/socket';

const HeaderWithSidebar = ({ children }) => {
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);
  const [activeNavItem, setActiveNavItem] = useState("Dashboard");
  const [isLive, setIsLive] = useState(false);
  const [isOnline, setIsOnline] = useState(false);
  const [connectionStatus, setConnectionStatus] = useState("connecting");
  const [debugLogs, setDebugLogs] = useState([]);
  const [incomingCall, setIncomingCall] = useState(null);
  const [incomingCalls, setIncomingCalls] = useState([]);

  const navigate = useNavigate();
  const location = useLocation();
  const { user } = useContext(AuthContext);
  const hasListeners = useRef(false);
  const hasSetUpListeners = useRef(false);
  // console.log(user,"ankit");
  
  const doctorId = user.id

  

  // Add debug log function
  const addDebugLog = (message) => {
    const timestamp = new Date().toLocaleTimeString();
    const logEntry = `${timestamp}: ${message}`;
    // console.log(logEntry);
    setDebugLogs(prev => [...prev.slice(-9), logEntry]); // Keep last 10 logs
  };

  // Join doctor room function
  const joinDoctorRoom = () => {
    // Prevent multiple join attempts if already online
    if (isOnline) {
      addDebugLog(`⚠️ Already online, skipping join-doctor`);
      return;
    }
    
    addDebugLog(`🏥 Emitting join-doctor event for ID: ${doctorId}`);
    setConnectionStatus("joining");
    socket.emit("join-doctor", doctorId);
    
    // Set a timeout to detect if we don't receive doctor-online event
    setTimeout(() => {
      if (!isOnline && socket.connected) {
        addDebugLog(`⚠️ TIMEOUT: No doctor-online event received after 3 seconds`);
        addDebugLog(`🔄 Retrying join-doctor...`);
        socket.emit("join-doctor", doctorId);
      }
    }, 3000);
  };

  useEffect(() => {
    const savedLive = localStorage.getItem("doctorLive");
    if (savedLive === "true") goLive();
  }, []);

  // Connect doctor socket - Enhanced version
  const goLive = () => {
    addDebugLog(`🟢 Going live - Doctor ID: ${doctorId}`);
    
    if (!socket.connected) {
      addDebugLog("🔌 Socket not connected, connecting...");
      socket.connect();
    }
    
    // Join doctor room
    joinDoctorRoom();
    
    setIsLive(true);
    localStorage.setItem("doctorLive", "true");
    addDebugLog("✅ Doctor is now live");
  };

  const goOffline = () => {
    addDebugLog(`⚪ Going offline - Doctor ID: ${doctorId}`);

    if (socket.connected) {
      socket.emit("leave-doctor", doctorId);
    }

    setIsLive(false);
    setIsOnline(false);
    setIncomingCall(null);
    setIncomingCalls([]);
    setConnectionStatus("manual-offline");
    localStorage.removeItem("doctorLive");
    addDebugLog("⚪ Doctor is offline but will continue receiving alerts");
  };

  // Toggle live status button
  const handleToggle = () => (isLive ? goOffline() : goLive());

  // Enhanced Socket listeners setup
  useEffect(() => {
    // Prevent duplicate listener setup
    if (hasSetUpListeners.current) {
      addDebugLog("⚠️ Listeners already set up, skipping");
      return;
    }
    
    hasSetUpListeners.current = true;
    addDebugLog(`🏥 Setting up socket listeners for doctorId: ${doctorId}`);

    // Check if socket is already connected
    if (socket.connected) {
      addDebugLog("✅ Socket already connected, joining doctor room");
      setConnectionStatus("connected");
      if (isLive) {
        joinDoctorRoom();
      }
    } else {
      addDebugLog("🔄 Socket not connected, waiting for connection");
      setConnectionStatus("connecting");
    }

    // Socket connection events
    const handleConnect = () => {
      addDebugLog("✅ Socket connected successfully");
      setConnectionStatus("connected");
      
      // Auto-rejoin if we were live before
      if (localStorage.getItem("doctorLive") === "true") {
        addDebugLog("🔄 Auto-rejoining doctor room after reconnection");
        joinDoctorRoom();
      }
    };

    const handleDisconnect = (reason) => {
      addDebugLog(`❌ Socket disconnected. Reason: ${reason}`);
      setConnectionStatus("disconnected");
      setIsOnline(false);
    };

    const handleConnectError = (error) => {
      addDebugLog(`❌ Socket connection error: ${error.message}`);
      setConnectionStatus("error");
    };

    // Doctor-specific events
    const handleDoctorOnline = (data) => {
      addDebugLog(`👨‍⚕️ Doctor online event received: ${JSON.stringify(data)}`);
      if (data.doctorId === doctorId) {
        setIsOnline(true);
        setConnectionStatus("online");
        addDebugLog(`✅ Doctor ${doctorId} is now ONLINE`);
      }
    };

    const handleDoctorOffline = (data) => {
      addDebugLog(`👨‍⚕️ Doctor offline event received: ${JSON.stringify(data)}`);
      if (data.doctorId === doctorId) {
        setIsOnline(false);
        setConnectionStatus("offline");
      }
    };

    // In HeaderWithSidebar component, update the handleCallRequested function:
const handleCallRequested = (callData) => {
  addDebugLog(`📞 Incoming call received: ${JSON.stringify(callData)}`);
  
  // Create call object for RingtonePopup - FIX patientId mapping
  const incomingCallData = {
    id: callData.callId,
    patientId: callData.patientId, // ✅ This was missing
    channel: callData.channel,
    timestamp: Date.now(),
    doctorId,
  };

  setIncomingCall(incomingCallData);
  
  setIncomingCalls(prev => {
    const exists = prev.some(call => call.id === callData.callId);
    if (exists) {
      addDebugLog(`⚠️ Duplicate call ignored: ${callData.callId}`);
      return prev;
    }
    
    return [...prev, { ...callData, id: callData.callId }];
  });
};

    // Error handling events
    const handleJoinError = (error) => {
      addDebugLog(`❌ Error joining doctor room: ${error.message}`);
      setConnectionStatus("error");
    };

    // Generic event listener to catch ALL events for debugging
    const handleAnyEvent = (eventName, ...args) => {
      if (eventName !== 'ping' && eventName !== 'pong') {
        addDebugLog(`📡 Event received: ${eventName} - ${JSON.stringify(args)}`);
      }
    };

    // Add event listeners
    socket.on("connect", handleConnect);
    socket.on("disconnect", handleDisconnect);
    socket.on("connect_error", handleConnectError);
    socket.on("doctor-online", handleDoctorOnline);
    socket.on("doctor-offline", handleDoctorOffline);
    socket.on("call-requested", handleCallRequested);
    socket.on("join-error", handleJoinError);

    // Listen to ALL socket events for debugging
    socket.onAny(handleAnyEvent);

    // Test server communication
    addDebugLog("🧪 Testing server communication...");
    socket.emit("get-server-status");
    socket.on("server-status", (status) => {
      addDebugLog(`📊 Server status received: ${JSON.stringify(status)}`);
    });

    // Cleanup function
    return () => {
      addDebugLog("🧹 Cleaning up socket listeners");
      
      socket.off("connect", handleConnect);
      socket.off("disconnect", handleDisconnect);
      socket.off("connect_error", handleConnectError);
      socket.off("doctor-online", handleDoctorOnline);
      socket.off("doctor-offline", handleDoctorOffline);
      socket.off("call-requested", handleCallRequested);
      socket.off("join-error", handleJoinError);
      socket.off("server-status");
      socket.offAny(handleAnyEvent);
      
      if (socket.connected && isLive) {
        addDebugLog(`🚪 Emitting leave-doctor for ID: ${doctorId}`);
        socket.emit("leave-doctor", doctorId);
      }
      
      setIsOnline(false);
      setConnectionStatus("disconnected");
      hasSetUpListeners.current = false;
    };
  }, [doctorId, isLive]); // Added isLive to dependencies

  // Handle call actions from RingtonePopup
  const handleCallClose = () => {
    addDebugLog("🔕 Closing incoming call popup");
    setIncomingCall(null);
    
    // Also remove from calls list
    if (incomingCall) {
      setIncomingCalls(prev => prev.filter(c => c.id !== incomingCall.id));
    }
  };

  // Manual rejoin function for debugging
  const manualRejoin = () => {
    addDebugLog("🔄 Manual rejoin triggered");
    setIsOnline(false);
    setConnectionStatus("rejoining");
    socket.emit("join-doctor", doctorId);
  };

  // Test server communication
  const testServerCommunication = () => {
    addDebugLog("🧪 Testing server communication manually");
    socket.emit("ping", { doctorId, timestamp: Date.now() });
    socket.once("pong", (data) => {
      addDebugLog(`🏓 Pong received: ${JSON.stringify(data)}`);
    });
  };

  // Get status color and text for UI
  const getStatusColor = () => {
    if (isOnline) return "#16a34a"; // green
    if (
      connectionStatus === "connecting" ||
      connectionStatus === "joining" ||
      connectionStatus === "rejoining"
    )
      return "#f59e0b"; // yellow
    return "#dc2626"; // red
  };

  const getStatusText = () => {
    if (isOnline) return "🟢 ONLINE";
    if (connectionStatus === "connecting") return "🟡 CONNECTING";
    if (connectionStatus === "joining") return "🟡 JOINING ROOM";
    if (connectionStatus === "rejoining") return "🟡 REJOINING";
    if (connectionStatus === "connected") return "🟡 CONNECTED (Not in room)";
    if (connectionStatus === "manual-offline") return "🔴 OFFLINE (Listening)";
    return "🔴 OFFLINE";
  };

  // ✅ Navigation config with correct heroicons
  const navConfig = {
    common1: [
      {
        category: "Home",
        items: [
          {
            text: "Home",
            icon: <HomeIcon className="w-5 h-5" />,
            path: "/dashboard",
          },
        ],
      },
    ],
    super_admin: [
      {
        category: "Super Admin",
        items: [
          {
            text: "Pet Owner",
            icon: <StarIcon className="w-5 h-5" />,
            path: "/user-dashboard/pet-owner",
          },
          {
            text: "Vet Owner",
            icon: <CogIcon className="w-5 h-5" />,
            path: "/user-dashboard/vet-owner",
          },
        ],
      },
    ],
    pet: [
      {
        category: "Pet Owner",
        items: [
          {
            text: "My Profile",
            icon: <HeartIcon className="w-5 h-5" />,
            path: "/user-dashboard/pet-info",
          },
          {
            text: "Health Records",
            icon: <HeartIcon className="w-5 h-5" />,
            path: "/user-dashboard/health-records",
          },
          // {
          //   text: "Daily Care",
          //   icon: <HeartIcon className="w-5 h-5" />,
          //   path: "/user-dashboard/pet-daily-care",
          // },
          // {
          //   text: "My Bookings",
          //   icon: <CalendarIcon className="w-5 h-5" />,
          //   path: "/user-dashboard/my-bookings",
          // },
          // {
          //   text: "History",
          //   icon: <UserGroupIcon className="w-5 h-5" />,
          //   path: "/user-dashboard/history",
          // },
          // {
          //   text: "Vaccinations",
          //   icon: <ExclamationTriangleIcon className="w-5 h-5" />,
          //   path: "/user-dashboard/vaccination-tracker",
          // },
          // {
          //   text: "Medication Tracker",
          //   icon: <ExclamationTriangleIcon className="w-5 h-5" />,
          //   path: "/user-dashboard/medical-tracker",
          // },
          // {
          //   text: "Weight Monitoring",
          //   icon: <ExclamationTriangleIcon className="w-5 h-5" />,
          //   path: "/user-dashboard/weight-monitoring",
          // },
          // {
          //   text: "Vet Visits",
          //   icon: <ExclamationTriangleIcon className="w-5 h-5" />,
          //   path: "/user-dashboard/vet-visits",
          // },
          // {
          //   text: "Photo Timeline",
          //   icon: <ExclamationTriangleIcon className="w-5 h-5" />,
          //   path: "/user-dashboard/photo-timeline",
          // },
          // {
          //   text: "Emergency Contacts",
          //   icon: <ExclamationTriangleIcon className="w-5 h-5" />,
          //   path: "/user-dashboard/emergency-contacts",
          // },
        ],
      },
    ],
    vet: [
      {
        category: "Doctor",
        items: [
          {
            text: "Booking Requests",
            icon: <CalendarIcon className="w-5 h-5" />,
            path: "/user-dashboard/bookings",
          },
          {
            text: "Profile & Settings",
            icon: <UserIcon className="w-5 h-5" />,
            path: "/user-dashboard/vet-profile",
          },
          {
            text: "My Document",
            icon: <StarIcon className="w-5 h-5" />,
            path: "/user-dashboard/vet-document",
          },
          {
            text: "Payment",
            icon: <ExclamationTriangleIcon className="w-5 h-5" />,
            path: "/user-dashboard/vet-payment",
          },
        ],
      },
    ],
    common: [
      {
        category: "Account",
        items: [
          // {
          //   text: "Ratings & Reviews",
          //   icon: <StarIcon className="w-5 h-5" />,
          //   path: "/user-dashboard/rating-page",
          // },
          {
            text: "Support",
            icon: <CogIcon className="w-5 h-5" />,
            path: "/user-dashboard/support",
          },
        ],
      },
    ],
  };

  const mainNavItems = [
    ...(user?.role === "super_admin" || user?.role === "pet"
      ? navConfig.common1 || []
      : []),
    ...(user?.role === "super_admin" ? navConfig.super_admin : []),
    ...(user?.role === "pet" ? navConfig.pet : []),
    ...(user?.role === "vet" ? navConfig.vet : []),
    ...(navConfig.common || []),
  ];

  // set active nav item
  useEffect(() => {
    const currentPath = location.pathname;
    for (const category of mainNavItems) {
      const item = category.items.find((item) => item.path === currentPath);
      if (item) {
        setActiveNavItem(item.text);
        break;
      }
    }
  }, [location.pathname, mainNavItems]);

  // toggle functions
  const toggleDropdown = () => setIsDropdownOpen(!isDropdownOpen);
  const toggleSidebar = () => setIsSidebarOpen(!isSidebarOpen);

  const handleNavItemClick = (path, text) => {
    navigate(path);
    setActiveNavItem(text);
    if (window.innerWidth < 1024) setIsSidebarOpen(false);
  };

  const handleLogin = () => navigate("/login");
  const handleRegister = () =>
    navigate(
      "/register?utm_source=facebook&utm_medium=paid_social&utm_campaign=pet_emergency_test1&utm_content=chat_conversion"
    );
  const handleLogout = () => {
    localStorage.clear();
    navigate("/");
    window.location.reload();
  };

  return (
    <div className="flex h-screen bg-gray-50">
      {/* Desktop Sidebar */}
      <div className="hidden lg:flex lg:flex-shrink-0">
        <div className="w-64 flex flex-col bg-gradient-to-b from-indigo-800 to-purple-700 text-white">
          <div className="flex items-center justify-between h-16 px-4 border-b border-indigo-700">
            <div
              className="text-xl font-bold cursor-pointer"
              onClick={() => navigate(user ? "/dashboard" : "/")}
            >
              SnoutIQ
            </div>
          </div>

          <nav className="flex-1 px-2 py-4 overflow-y-auto">
            {mainNavItems.map((category, i) => (
              <div key={i} className="mb-6">
                <h3 className="px-3 text-xs font-semibold text-indigo-300 uppercase tracking-wider mb-2">
                  {category.category}
                </h3>
                <div className="space-y-1">
                  {category.items.map((item) => {
                    const isActive = location.pathname === item.path;
                    return (
                      <button
                        key={item.text}
                        onClick={() => handleNavItemClick(item.path, item.text)}
                        className={`flex items-center w-full px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ${
                          isActive
                            ? "bg-white text-indigo-700 shadow-md"
                            : "text-indigo-100 hover:bg-indigo-700 hover:text-white"
                        }`}
                      >
                        <span className="mr-3">{item.icon}</span>
                        {item.text}
                      </button>
                    );
                  })}
                </div>
              </div>
            ))}
          </nav>
        </div>
      </div>

      {/* Main content with header */}
      <div className="flex flex-col flex-1 overflow-hidden">
        <header className="bg-white border-b border-gray-200 shadow-sm">
          <div className="flex items-center justify-between px-4 py-3 h-16">
            <div className="flex items-center">
              <button
                type="button"
                className="px-3 py-2 rounded-md text-gray-500 hover:text-indigo-600 lg:hidden"
                onClick={toggleSidebar}
              >
                <Bars3Icon className="h-6 w-6" />
              </button>
              <h1 className="ml-2 text-xl font-semibold text-gray-800 lg:ml-4">
                {activeNavItem}
              </h1>
            </div>

            <div className="flex items-center space-x-4">
              {user?.business_status && (
                <div className="flex items-center space-x-2">
                  <span className="text-sm text-gray-600 hidden md:block">
                    {getStatusText()}
                  </span>
                  <button
                    onClick={handleToggle}
                    style={{
                      background: isOnline ? "green" : "gray",
                      color: "white",
                      padding: "8px 16px",
                      borderRadius: "6px",
                      border: "none",
                      cursor: "pointer",
                      fontWeight: "bold"
                    }}
                  >
                    {isOnline ? "🟢 Online" : isLive ? "🟡 Connecting..." : "⚪ Offline"}
                  </button>
                  <VideoCameraIcon
                    className={`h-5 w-5 ${
                      isOnline ? "text-green-500" : "text-gray-400"
                    }`}
                  />
                  
                  {/* Show incoming calls count */}
                  {incomingCalls.length > 0 && (
                    <div className="bg-red-500 text-white text-xs rounded-full px-2 py-1 font-bold">
                      {incomingCalls.length} calls
                    </div>
                  )}
                </div>
              )}

              {/* Debug Panel Toggle for Doctors */}
              {user?.business_status && process.env.NODE_ENV === 'development' && (
                <div className="flex space-x-2">
                  <button 
                    onClick={manualRejoin}
                    className="px-2 py-1 text-xs bg-blue-500 text-white rounded"
                    title="Manual Rejoin"
                  >
                    🔄
                  </button>
                  <button 
                    onClick={testServerCommunication}
                    className="px-2 py-1 text-xs bg-green-500 text-white rounded"
                    title="Test Server"
                  >
                    🧪
                  </button>
                </div>
              )}

              {!user ? (
                <div className="flex space-x-2">
                  <button
                    onClick={handleRegister}
                    className="px-4 py-2 text-sm font-medium text-indigo-600 border border-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors"
                  >
                    Register
                  </button>
                  <button
                    onClick={handleLogin}
                    className="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors"
                  >
                    Login
                  </button>
                </div>
              ) : (
                <div className="relative">
                  <button
                    className="flex items-center max-w-xs rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    onClick={toggleDropdown}
                  >
                    <div className="flex items-center space-x-3">
                      <div className="w-8 h-8 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full flex items-center justify-center">
                        <UserIcon className="w-4 h-4 text-white" />
                      </div>
                      <div className="hidden md:flex flex-col items-start">
                        <div className="text-sm font-medium text-gray-800">
                          {user.name || user.business_status}
                        </div>
                        <div className="text-xs text-gray-500">
                          {user.role || "Pet Owner"}
                        </div>
                      </div>
                    </div>
                  </button>

                  {isDropdownOpen && (
                    <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 z-50 border border-gray-200">
                      <button
                        onClick={handleLogout}
                        className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
                      >
                        <ArrowRightOnRectangleIcon className="w-4 h-4 mr-2" />
                        Logout
                      </button>
                    </div>
                  )}
                </div>
              )}
            </div>
          </div>
        </header>

        <main className="flex-1 overflow-y-auto bg-gray-50 p-6">
          {user?.business_status && isLive && (
            <div className="mb-6 flex items-center rounded-lg border border-green-200 bg-green-50 p-4">
              <div className="relative flex-shrink-0">
                <span className="absolute inline-flex h-3 w-3 animate-ping rounded-full bg-green-500 opacity-75"></span>
                <span className="relative inline-flex h-3 w-3 rounded-full bg-green-500"></span>
              </div>
              <div className="ml-3 flex-1">
                <p className="text-sm font-medium text-green-800">
                  {getStatusText()} - Ready to receive video calls
                </p>
                <p className="text-sm text-green-600">
                  Socket ID: {socket.id || "Not connected"} | Status: {connectionStatus}
                </p>
              </div>
              {/* Show connection details */}
              <div className="text-xs text-green-600">
                Calls: {incomingCalls.length}
              </div>
            </div>
          )}

          {/* Render the RingtonePopup when there's an incoming call */}
          {incomingCall && (
            <RingtonePopup
              call={incomingCall}
              doctorId={doctorId}
              // onClose={handleCallClose}
      //  patientId={incomingCall.patientId}
      patientId={incomingCall?.patientId}
       onClose={() => setIncomingCall(null)}
            />
          )}

          {/* Debug Panel for Development */}
          {user?.business_status && process.env.NODE_ENV === 'development' && (
            <div className="mb-6 p-4 bg-gray-100 rounded-lg text-xs">
              <h4 className="font-bold mb-2">Debug Info:</h4>
              <div className="grid grid-cols-2 md:grid-cols-4 gap-2 mb-2">
                <div>Socket: {socket.connected ? "✅" : "❌"}</div>
                <div>Online: {isOnline ? "✅" : "❌"}</div>
                <div>Live: {isLive ? "✅" : "❌"}</div>
                <div>Calls: {incomingCalls.length}</div>
              </div>
              <div className="max-h-20 overflow-y-auto bg-black text-green-400 p-2 rounded font-mono">
                {debugLogs.slice(-3).map((log, i) => <div key={i}>{log}</div>)}
              </div>
            </div>
          )}

          <div>{children}</div>
        </main>
      </div>

      {/* Mobile Sidebar - keeping the original code */}
      {isSidebarOpen && (
        <div className="fixed inset-0 z-50 flex lg:hidden">
          <div
            className="fixed inset-0 bg-gray-900 bg-opacity-50"
            onClick={() => setIsSidebarOpen(false)}
          ></div>

          <div className="relative flex flex-col w-64 max-w-xs bg-gradient-to-b from-indigo-800 to-purple-700 text-white z-50">
            <div className="flex items-center justify-between h-16 px-4 border-b border-indigo-700">
              <div className="text-xl font-bold">SnoutIQ</div>
              <button
                onClick={() => setIsSidebarOpen(false)}
                className="text-white hover:text-gray-200"
              >
                <XMarkIcon className="h-6 w-6" />
              </button>
            </div>

            <nav className="flex-1 px-2 py-4 overflow-y-auto">
              {mainNavItems.map((category, i) => (
                <div key={i} className="mb-6">
                  <h3 className="px-3 text-xs font-semibold text-indigo-300 uppercase tracking-wider mb-2">
                    {category.category}
                  </h3>
                  <div className="space-y-1">
                    {category.items.map((item) => {
                      const isActive = location.pathname === item.path;
                      return (
                        <button
                          key={item.text}
                          onClick={() =>
                            handleNavItemClick(item.path, item.text)
                          }
                          className={`flex items-center w-full px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ${
                            isActive
                              ? "bg-white text-indigo-700 shadow-md"
                              : "text-indigo-100 hover:bg-indigo-700 hover:text-white"
                          }`}
                        >
                          <span className="mr-2">{item.icon}</span>
                          {item.text}
                        </button>
                      );
                    })}
                  </div>
                </div>
              ))}
            </nav>
          </div>
        </div>
      )}
    </div>
  );
};

export default HeaderWithSidebar;