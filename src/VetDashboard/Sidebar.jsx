import React, { useState, useEffect, useContext } from "react";
import { useNavigate, useLocation } from "react-router-dom";

import {
  Bars3Icon,
  UserIcon,
  ArrowRightOnRectangleIcon,
  VideoCameraIcon,
} from "@heroicons/react/24/outline";
import {
  HiOutlineViewGrid,
  HiOutlineCalendar,
  HiOutlineUser,
  HiOutlineExclamation,
  HiOutlineHeart,
  HiOutlineCog,
  HiOutlineStar,
  HiOutlineUserGroup,
} from "react-icons/hi";
import logo from "../assets/images/dark bg.webp";
import { AuthContext } from "../auth/AuthContext";
import axios from "axios";
import RingtonePopup from "../pages/RingtonePopup";

const HeaderWithSidebar = ({ children }) => {
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);
  const [activeNavItem, setActiveNavItem] = useState("");
  const [isLive, setIsLive] = useState(false);

  const navigate = useNavigate();
  const location = useLocation();
  const { user } = useContext(AuthContext);

  // Other existing functions remain the same...
  useEffect(() => {
    const currentItem = allNavItems.find(
      (item) => item.path === location.pathname
    );
    if (currentItem) {
      setActiveNavItem(currentItem.text);
    }
  }, [location.pathname]);

  useEffect(() => {
    const checkLiveStatus = async () => {
      try {
        const response = await axios.get("/user/live-status");
        setIsLive(response.data.isLive);
      } catch (error) {
        console.error("Error fetching live status:", error);
      }
    };

    if (user) {
      checkLiveStatus();
    }
  }, [user]);

  const handleLiveToggle = async () => {
    setIsLive(!isLive);
  };

  const handleLogin = () => navigate("/login");
  const handleRegister = () => navigate("/register");

  const handleLogout = () => {
    if (isLive) {
      axios
        .post("/user/update-live-status", { isLive: false })
        .catch((error) =>
          console.error("Error updating live status on logout:", error)
        );
    }
    localStorage.clear();
    navigate("/");
    window.location.reload();
  };

  const toggleDropdown = () => setIsDropdownOpen(!isDropdownOpen);

  const handleNavItemClick = (path, text) => {
    navigate(path);
    setActiveNavItem(text);
    if (window.innerWidth < 1024) {
      setIsSidebarOpen(false);
    }
  };

  // Navigation config (keeping same as original)
  const navConfig = {
    common1: [
      {
        category: "Home",
        items: [
          { text: "Home", icon: <HiOutlineViewGrid />, path: "/dashboard" },
        ],
      },
    ],
    super_admin: [
      {
        category: "Super Admin",
        items: [
          {
            text: "Pet Owner",
            icon: <HiOutlineStar />,
            path: "/user-dashboard/pet-owner",
          },
          {
            text: "Vet Owner",
            icon: <HiOutlineCog />,
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
            text: "My Pets",
            icon: <HiOutlineHeart />,
            path: "/user-dashboard/pet-info",
          },
          {
            text: "Health Records",
            icon: <HiOutlineHeart />,
            path: "/user-dashboard/pet-health",
          },
          {
            text: "Daily Care",
            icon: <HiOutlineHeart />,
            path: "/user-dashboard/pet-daily-care",
          },
          {
            text: "My Bookings",
            icon: <HiOutlineCalendar />,
            path: "/user-dashboard/my-bookings",
          },
          {
            text: "History",
            icon: <HiOutlineUserGroup />,
            path: "/user-dashboard/history",
          },
          {
            text: "Vaccinations",
            icon: <HiOutlineExclamation />,
            path: "/user-dashboard/vaccination-tracker",
          },
          {
            text: "Medication Tracker",
            icon: <HiOutlineExclamation />,
            path: "/user-dashboard/medical-tracker",
          },
          {
            text: "Weight Monitoring",
            icon: <HiOutlineExclamation />,
            path: "/user-dashboard/weight-monitoring",
          },
          {
            text: "Vet Visits",
            icon: <HiOutlineExclamation />,
            path: "/user-dashboard/vet-visits",
          },
          {
            text: "Photo Timeline",
            icon: <HiOutlineExclamation />,
            path: "/user-dashboard/photo-timeline",
          },
          {
            text: "Emergency Contacts",
            icon: <HiOutlineExclamation />,
            path: "/user-dashboard/emergency-contacts",
          },
        ],
      },
    ],
    vet: [
      {
        category: "Doctor",
        items: [
          {
            text: "Booking Requests",
            icon: <HiOutlineCalendar />,
            path: "/user-dashboard/bookings",
          },
          {
            text: "Profile & Settings",
            icon: <HiOutlineUser />,
            path: "/user-dashboard/vet-profile",
          },
          {
            text: "My Document",
            icon: <HiOutlineStar />,
            path: "/user-dashboard/vet-document",
          },
          {
            text: "Payment",
            icon: <HiOutlineExclamation />,
            path: "/user-dashboard/vet-payment",
          },
        ],
      },
    ],
    common: [
      {
        category: "Account",
        items: [
          {
            text: "Ratings & Reviews",
            icon: <HiOutlineStar />,
            path: "/user-dashboard/rating",
          },
          {
            text: "Support",
            icon: <HiOutlineCog />,
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

  const allNavItems = mainNavItems.flatMap((category) => category.items);

  return (
    <div className="flex h-screen bg-gray-50">
      {/* Incoming Call Dialog */}

      {user?.role === "vet" && <RingtonePopup />}

      {/* Rest of your component remains the same... */}
      {/* Desktop Sidebar */}
      <div className="hidden lg:flex lg:flex-shrink-0">
        <div className="w-64 flex flex-col bg-gradient-to-b from-indigo-800 to-purple-700 text-white">
          <div className="flex items-center justify-between h-16 px-4 border-b border-indigo-700">
            <div className="flex items-center">
              <img
                src={logo}
                alt="Snoutiq Logo"
                className="h-8 cursor-pointer"
                onClick={() => navigate(user ? "/dashboard" : "/")}
              />
            </div>
          </div>

          <nav className="flex-1 px-2 py-4 overflow-y-auto">
            {mainNavItems.map((category, categoryIndex) => (
              <div key={categoryIndex} className="mb-6">
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
                        <span
                          className={`text-lg mr-3 ${
                            isActive ? "text-indigo-600" : "text-indigo-300"
                          }`}
                        >
                          {item.icon}
                        </span>
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

      {/* Main Content */}
      <div className="flex flex-col flex-1 overflow-hidden">
        <header className="bg-white border-b border-gray-200 shadow-sm">
          <div className="flex items-center justify-between px-4 py-3 h-16">
            <div className="flex items-center">
              <button
                type="button"
                className="px-3 py-2 rounded-md text-gray-500 hover:text-indigo-600 lg:hidden"
                onClick={() => setIsSidebarOpen(true)}
              >
                <Bars3Icon className="h-6 w-6" />
              </button>
              <h1 className="ml-2 text-xl font-semibold text-gray-800 lg:ml-4">
                {activeNavItem || "Dashboard"}
              </h1>
            </div>

            <div className="flex items-center space-x-4">
              {user.business_status && (
                <div className="flex items-center space-x-2">
                  <span className="text-sm text-gray-600 hidden md:block">
                    {isLive ? "Live" : "Offline"}
                  </span>
                  <button
                    onClick={handleLiveToggle}
                    className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 ${
                      isLive ? "bg-green-500" : "bg-gray-300"
                    }`}
                  >
                    <span
                      className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                        isLive ? "translate-x-6" : "translate-x-1"
                      }`}
                    />
                  </button>
                  <VideoCameraIcon
                    className={`h-5 w-5 ${
                      isLive ? "text-green-500" : "text-gray-400"
                    }`}
                  />
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

        <main className="flex-1 bg-gray-50 overflow-y-auto">
          <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            {user && isLive && (
              <div className="mb-6 flex items-center rounded-lg border border-green-200 bg-green-50 p-4">
                <div className="relative flex-shrink-0">
                  <span className="absolute inline-flex h-3 w-3 animate-ping rounded-full bg-green-500"></span>
                  <span className="relative inline-flex h-3 w-3 rounded-full bg-green-500"></span>
                </div>
                <div className="ml-3">
                  <p className="text-sm font-medium text-green-800">
                    You are currently live
                  </p>
                  <p className="text-sm text-green-600">
                    Video calls can be received. Toggle off when you're
                    unavailable.
                  </p>
                </div>
              </div>
            )}
            <div>{children}</div>
          </div>
        </main>
      </div>
    </div>
  );
};

export default HeaderWithSidebar;
