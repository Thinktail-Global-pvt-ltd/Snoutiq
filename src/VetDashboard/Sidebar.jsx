import React, { useState, useEffect, useContext, useRef } from "react";
import { useNavigate, useLocation } from "react-router-dom";
import { AuthContext } from "../auth/AuthContext";
import RingtonePopup from "../pages/RingtonePopup";
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
  StarIcon,
} from "@heroicons/react/24/outline";
import logo from "../assets/images/dark bg.webp";
import {
  LayoutDashboard,
  UserCircle,
  Receipt,
  Star,
  Calendar,
  AlertTriangle,
  Users,
  FileStack,
  Video,
  Bot,
  CreditCard,
  BadgeCheck,
  QrCode,
  UserCog,
  History,
  ClipboardList,
  Stethoscope,
  FileText,
  HeartPulse,
} from "lucide-react";

import { socket } from "../pages/socket";

/* =======================
   Clinic Info Card (Sidebar Top)
   ======================= */

const ClinicInfoCard = ({ user, onManageProfile }) => {
  if (!user) return null;

  const displayName = user.name || "Demo Clinic";
  const email = user.email || "test@gmail.com";
  const idLabel = user.clinic_id || user.id || "--";
  const roleLabel = (user.role || "clinic_admin")
    .replace("_", " ")
    .toUpperCase();

  return (
    <div className="mb-6 px-2">
      <div className="rounded-2xl bg-gradient-to-r from-indigo-600 to-purple-600 p-4 shadow-lg border border-indigo-300/40">
        <div className="text-sm font-semibold text-white truncate">
          {displayName}
        </div>
        <div className="text-[11px] text-indigo-100 truncate">{email}</div>

        <div className="mt-3 flex items-center justify-between text-[11px] text-indigo-100/90">
          <span className="px-2 py-0.5 rounded-full bg-indigo-800/80 border border-indigo-300/60">
            ID {idLabel}
          </span>
          <span className="uppercase tracking-wide">ROLE · {roleLabel}</span>
        </div>

        <button
          onClick={onManageProfile}
          className="mt-4 w-full rounded-xl bg-white/95 text-indigo-700 text-xs font-semibold py-2 hover:bg-white transition-colors"
        >
          Manage Profile
        </button>
      </div>
    </div>
  );
};

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
  const hasSetUpListeners = useRef(false);

  const doctorId = user?.id;

  const addDebugLog = (message) => {
    const timestamp = new Date().toLocaleTimeString();
    const logEntry = `${timestamp}: ${message}`;
    setDebugLogs((prev) => [...prev.slice(-9), logEntry]);
  };

  const joinDoctorRoom = () => {
    if (isOnline) {
      addDebugLog(`⚠️ Already online, skipping join-doctor`);
      return;
    }

    addDebugLog(`🏥 Emitting join-doctor event for ID: ${doctorId}`);
    setConnectionStatus("joining");
    socket.emit("join-doctor", doctorId);

    setTimeout(() => {
      if (!isOnline && socket.connected) {
        addDebugLog(
          `⚠️ TIMEOUT: No doctor-online event received after 3 seconds`
        );
        addDebugLog(`🔄 Retrying join-doctor...`);
        socket.emit("join-doctor", doctorId);
      }
    }, 3000);
  };

  useEffect(() => {
    const savedLive = localStorage.getItem("doctorLive");
    if (savedLive === "true") goLive();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const goLive = () => {
    addDebugLog(`🟢 Going live - Doctor ID: ${doctorId}`);

    if (!socket.connected) {
      addDebugLog("🔌 Socket not connected, connecting...");
      socket.connect();
    }

    joinDoctorRoom();

    setIsLive(true);
    localStorage.setItem("doctorLive", "true");
    addDebugLog("✅ Doctor is now live");
  };

  const goOffline = () => {
    addDebugLog(`⚪ Going offline - Doctor ID: ${doctorId}`);

    socket.emit("leave-doctor", doctorId);

    if (socket.connected) {
      socket.disconnect();
    }

    setIsLive(false);
    setIsOnline(false);
    setIncomingCall(null);
    setIncomingCalls([]);
    setConnectionStatus("offline");
    localStorage.removeItem("doctorLive");
    addDebugLog("⚪ Doctor is offline");
  };

  const handleToggle = () => (isLive ? goOffline() : goLive());

  useEffect(() => {
    if (hasSetUpListeners.current) {
      addDebugLog("⚠️ Listeners already set up, skipping");
      return;
    }

    hasSetUpListeners.current = true;
    addDebugLog(`🏥 Setting up socket listeners for doctorId: ${doctorId}`);

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

    const handleConnect = () => {
      addDebugLog("✅ Socket connected successfully");
      setConnectionStatus("connected");

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

    const handleCallRequested = (callData) => {
      addDebugLog(`📞 Incoming call received: ${JSON.stringify(callData)}`);

      const incomingCallData = {
        id: callData.callId,
        patientId: callData.patientId,
        channel: callData.channel,
        timestamp: Date.now(),
        doctorId,
      };

      setIncomingCall(incomingCallData);

      setIncomingCalls((prev) => {
        const exists = prev.some((call) => call.id === callData.callId);
        if (exists) {
          addDebugLog(`⚠️ Duplicate call ignored: ${callData.callId}`);
          return prev;
        }

        return [...prev, { ...callData, id: callData.callId }];
      });
    };

    const handleJoinError = (error) => {
      addDebugLog(`❌ Error joining doctor room: ${error.message}`);
      setConnectionStatus("error");
    };

    const handleAnyEvent = (eventName, ...args) => {
      if (eventName !== "ping" && eventName !== "pong") {
        addDebugLog(
          `📡 Event received: ${eventName} - ${JSON.stringify(args)}`
        );
      }
    };

    socket.on("connect", handleConnect);
    socket.on("disconnect", handleDisconnect);
    socket.on("connect_error", handleConnectError);
    socket.on("doctor-online", handleDoctorOnline);
    socket.on("doctor-offline", handleDoctorOffline);
    socket.on("call-requested", handleCallRequested);
    socket.on("join-error", handleJoinError);
    socket.onAny(handleAnyEvent);

    addDebugLog("🧪 Testing server communication...");
    socket.emit("get-server-status");
    socket.on("server-status", (status) => {
      addDebugLog(`📊 Server status received: ${JSON.stringify(status)}`);
    });

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
  }, [doctorId, isLive]); // eslint-disable-line react-hooks/exhaustive-deps

  const handleCallClose = () => {
    addDebugLog("🔕 Closing incoming call popup");
    setIncomingCall(null);

    if (incomingCall) {
      setIncomingCalls((prev) => prev.filter((c) => c.id !== incomingCall.id));
    }
  };

  const manualRejoin = () => {
    addDebugLog("🔄 Manual rejoin triggered");
    setIsOnline(false);
    setConnectionStatus("rejoining");
    socket.emit("join-doctor", doctorId);
  };

  const testServerCommunication = () => {
    addDebugLog("🧪 Testing server communication manually");
    socket.emit("ping", { doctorId, timestamp: Date.now() });
    socket.once("pong", (data) => {
      addDebugLog(`🏓 Pong received: ${JSON.stringify(data)}`);
    });
  };

  const getStatusText = () => {
    if (isOnline) return "🟢 ONLINE";
    if (connectionStatus === "connecting") return "🟡 CONNECTING";
    if (connectionStatus === "joining") return "🟡 JOINING ROOM";
    if (connectionStatus === "rejoining") return "🟡 REJOINING";
    if (connectionStatus === "connected") return "🟡 CONNECTED (Not in room)";
    return "🔴 OFFLINE";
  };

  // ========== NAV CONFIG ==========

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
        ],
      },
    ],
    vet: [
      {
        category: "Overview",
        items: [
          {
            text: "Dashboard",
            icon: <LayoutDashboard className="w-5 h-5" />,
            path: "/user-dashboard/vet-dashboard",
          },
          {
            text: "Profile",
            icon: <UserCircle className="w-5 h-5" />,
            path: "/user-dashboard/Doctor-Profile",
          },
          {
            text: "Order History",
            icon: <History className="w-5 h-5" />,
            path: "/user-dashboard/order-history",
          },
        ],
      },
      {
        category: "Clinic Management",
        items: [
          {
            text: "Services",
            icon: <Star className="w-5 h-5" />,
            path: "/user-dashboard/vet-services",
          },
          {
            text: "Clinic Hours",
            icon: <Calendar className="w-5 h-5" />,
            path: "/user-dashboard/vet-clinic-hours",
          },
          {
            text: "Emergency Hours",
            icon: <AlertTriangle className="w-5 h-5" />,
            path: "/user-dashboard/doctor-emergency-hours",
          },
          {
            text: "Doctors",
            icon: <Users className="w-5 h-5" />,
            path: "/user-dashboard/Total-Doctors",
          },
          {
            text: "Doctor Documents",
            icon: <FileStack className="w-5 h-5" />,
            path: "/user-dashboard/Doctor-Documents",
          },
        ],
      },
      {
        category: "Appointments & Records",
        items: [
          {
            text: "Booking Requests",
            icon: <ClipboardList className="w-5 h-5" />,
            path: "/user-dashboard/booking-payments",
          },
          {
            text: "Appointments",
            icon: <Stethoscope className="w-5 h-5" />,
            path: "/user-dashboard/appointment",
          },
          {
            text: "Follow Up",
            icon: <FileText className="w-5 h-5" />,
            path: "/user-dashboard/doctor-follow-up",
          },
          {
            text: "Patient Records",
            icon: <HeartPulse className="w-5 h-5" />,
            path: "/user-dashboard/patient-records",
          },
        ],
      },
      {
        category: "Communication",
        items: [
          {
            text: "Video Calling Schedule",
            icon: <Video className="w-5 h-5" />,
            path: "/user-dashboard/vet-videocalling-schedule",
          },
          {
            text: "AI Assistant",
            icon: <Bot className="w-5 h-5" />,
            path: "/user-dashboard/AIAssistantView",
          },
        ],
      },
      {
        category: "Finance",
        items: [
          {
            text: "Payment",
            icon: <CreditCard className="w-5 h-5" />,
            path: "/user-dashboard/vet-payment",
          },
          {
            text: "Clinic KYC",
            icon: <BadgeCheck className="w-5 h-5" />,
            path: "/user-dashboard/clinic-kyc",
          },
          {
            text: "QR Code Branding",
            icon: <QrCode className="w-5 h-5" />,
            path: "/user-dashboard/Qr-Code-Branding",
          },
          {
            text: "Staff Management",
            icon: <UserCog className="w-5 h-5" />,
            path: "/user-dashboard/staff-management",
          },
        ],
      },
    ],
    common: [
      {
        category: "Account",
        items: [
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
    ...(user?.role === "clinic_admin" ? navConfig.vet : []),
    ...(navConfig.common || []),
  ];

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

  // card ka button
  const handleManageProfile = () => {
    if (!user) return;
    if (user.role === "clinic_admin") {
      navigate("/user-dashboard/Doctor-Profile");
    } else if (user.role === "pet") {
      navigate("/user-dashboard/pet-info");
    } else {
      navigate("/dashboard");
    }
    setIsSidebarOpen(false);
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
              <img src={logo} className="h-10 w-auto" />
            </div>
          </div>

          <nav className="flex-1 px-2 py-4 overflow-y-auto">
            {/* ⭐ Clinic card at top of sidebar */}
            {user?.role === "clinic_admin" && (
              <ClinicInfoCard
                user={user}
                onManageProfile={handleManageProfile}
              />
            )}

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
                      fontWeight: "bold",
                    }}
                  >
                    {isOnline
                      ? "🟢 Online"
                      : isLive
                      ? "🟡 Connecting..."
                      : "⚪ Offline"}
                  </button>
                  <VideoCameraIcon
                    className={`h-5 w-5 ${
                      isOnline ? "text-green-500" : "text-gray-400"
                    }`}
                  />

                  {incomingCalls.length > 0 && (
                    <div className="bg-red-500 text-white text-xs rounded-full px-2 py-1 font-bold">
                      {incomingCalls.length} calls
                    </div>
                  )}
                </div>
              )}

              {user?.business_status &&
                process.env.NODE_ENV === "development" && (
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
                  Socket ID: {socket.id || "Not connected"} | Status:{" "}
                  {connectionStatus}
                </p>
              </div>
              <div className="text-xs text-green-600">
                Calls: {incomingCalls.length}
              </div>
            </div>
          )}

          {incomingCall && (
            <RingtonePopup
              call={incomingCall}
              doctorId={doctorId}
              patientId={incomingCall?.patientId}
              onClose={handleCallClose}
            />
          )}

          <div>{children}</div>
        </main>
      </div>

      {/* Mobile Sidebar */}
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
              {/* ⭐ Mobile sidebar top card */}
              {user?.role === "clinic_admin" && (
                <ClinicInfoCard
                  user={user}
                  onManageProfile={handleManageProfile}
                />
              )}

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
