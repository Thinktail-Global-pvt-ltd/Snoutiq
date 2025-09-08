import React, { useState, useEffect, useContext } from "react";
import { useNavigate, useLocation } from "react-router-dom";
import { Dialog, Transition } from "@headlessui/react";
import { Fragment } from "react";
import {
  Bars3Icon,
  XMarkIcon,
  UserIcon,
  ArrowRightOnRectangleIcon,
  HomeIcon,
  HeartIcon,
  BellIcon,
  ChatBubbleLeftIcon,
  VideoCameraIcon,
} from "@heroicons/react/24/outline";
import {
  HiOutlineViewGrid,
  HiOutlineCalendar,
  HiOutlineUser,
  HiOutlineChatAlt,
  HiOutlineExclamation,
  HiOutlineHeart,
  HiOutlineCog,
  HiOutlineStar,
  HiOutlineUserGroup,
} from "react-icons/hi";
import logo from "../assets/images/dark bg.png";
import axiosClient from "../axios";
import { AuthContext } from "../auth/AuthContext";

const HeaderWithSidebar = ({ children }) => {
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);
  const [activeNavItem, setActiveNavItem] = useState("");
  const [isLive, setIsLive] = useState(false);

  const navigate = useNavigate();
  const location = useLocation();
  const { user } = useContext(AuthContext);
  console.log(user.role,'fhg');
  

  useEffect(() => {
    (async () => {
      try {
        const res = await axiosClient.get("/ai-stats");
        setStats(res.data);
      } catch (error) {
        console.error("Error:", error);
      }
    })();
  }, []);

  useEffect(() => {
    // Set active nav item based on current path
    const currentItem = allNavItems.find(
      (item) => item.path === location.pathname
    );
    if (currentItem) {
      setActiveNavItem(currentItem.text);
    }
  }, [location.pathname]);

  // Check user's live status on component mount
  useEffect(() => {
    const checkLiveStatus = async () => {
      try {
        const response = await axiosClient.get("/user/live-status");
        setIsLive(response.data.isLive);
      } catch (error) {
        console.error("Error fetching live status:", error);
      }
    };

    if (user) {
      checkLiveStatus();
    }
  }, [user]);

  // Handle live status toggle
  const handleLiveToggle = async () => {
    // try {
    //   const newStatus = !isLive;
    //   const response = await axiosClient.post("/user/update-live-status", {
    //     isLive: newStatus,
    //   });

    //   if (response.data.success) {
    //     setIsLive(newStatus);

    //     // If user is going live, show success message
    //     if (newStatus) {
    //       // You can add a toast notification here
    //       console.log("You are now live! Video calls can be received.");
    //     }
    //   }
    // } catch (error) {
    //   console.error("Error updating live status:", error);
    // }
    setIsLive(!isLive);
  };

  const handleLogin = () => {
    navigate("/login");
  };
  const handleRegister = () => {
    navigate("/register");
  };
  const handleLogout = () => {
    // Update live status to false when logging out
    if (isLive) {
      axiosClient
        .post("/user/update-live-status", { isLive: false })
        .catch((error) =>
          console.error("Error updating live status on logout:", error)
        );
    }

    localStorage.clear();
    navigate("/");
    window.location.reload();
  };

  const toggleDropdown = () => {
    setIsDropdownOpen(!isDropdownOpen);
  };

  const handleNavItemClick = (path, text) => {
    navigate(path);
    setActiveNavItem(text);
    // Close sidebar on mobile after selection
    if (window.innerWidth < 1024) {
      setIsSidebarOpen(false);
    }
  };

  // Navigation items organized by category
  const mainNavItems = [
    {
      category: "Home",
      items: [
        {
          text: "Home",
          icon: <HiOutlineViewGrid />,
          path: "/dashboard",
        },
      ],
    },

    {
      category: "Pet Owner",
      items: [
          {
          text: "My Pets",
          icon: <HiOutlineHeart />,
          path: "/user-dashboard/pet-info",
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
      ],
    },

  
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
         icon: <ChatBubbleLeftIcon className="w-5 h-5" />,
          path: "/user-dashboard/vet-payment",
        },
      ],
    },
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
  ];

  // Flattened version for finding active item
  const allNavItems = mainNavItems.flatMap((category) => category.items);

  return (
    <div className="flex h-screen bg-gray-50">
      {/* Desktop Sidebar */}
      <div className="hidden lg:flex lg:flex-shrink-0">
        <div className="w-64 flex flex-col bg-gradient-to-b from-indigo-800 to-purple-700 text-white">
          {/* Logo */}
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

          {/* Navigation */}
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
                        className={`flex items-center w-full px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200
                          ${
                            isActive
                              ? "bg-white text-indigo-700 shadow-md"
                              : "text-indigo-100 hover:bg-indigo-700 hover:text-white"
                          } ${item.color || ""}`}
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

      <Transition.Root show={isSidebarOpen} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={setIsSidebarOpen}>
          <Transition.Child
            as={Fragment}
            enter="ease-in-out duration-300"
            enterFrom="opacity-0"
            enterTo="opacity-100"
            leave="ease-in-out duration-300"
            leaveFrom="opacity-100"
            leaveTo="opacity-0"
          >
            <div className="fixed inset-0 bg-gray-800 bg-opacity-75 transition-opacity" />
          </Transition.Child>

          <div className="fixed inset-0 overflow-hidden">
            <div className="absolute inset-0 overflow-hidden">
              <div className="pointer-events-none fixed inset-y-0 left-0 flex max-w-full pr-10">
                <Transition.Child
                  as={Fragment}
                  enter="transform transition ease-in-out duration-300"
                  enterFrom="-translate-x-full"
                  enterTo="translate-x-0"
                  leave="transform transition ease-in-out duration-300"
                  leaveFrom="translate-x-0"
                  leaveTo="-translate-x-full"
                >
                  <Dialog.Panel className="pointer-events-auto w-[260px]">
                    <div className="relative flex flex-col w-64 max-w-xs bg-gradient-to-b from-indigo-900 to-purple-800 text-white h-full">
                      <div className="flex items-center justify-between h-16 px-4 border-b border-indigo-700">
                        <div className="flex items-center">
                          <img
                            src={logo}
                            alt="Snoutiq Logo"
                            className="h-8 cursor-pointer"
                            onClick={() => {
                              navigate(user ? "/dashboard" : "/");
                              setIsSidebarOpen(false);
                            }}
                          />
                        </div>
                        <button
                          type="button"
                          className="rounded-md p-2 text-indigo-200 hover:text-white"
                          onClick={() => setIsSidebarOpen(false)}
                        >
                          <XMarkIcon className="h-6 w-6" />
                        </button>
                      </div>

                      <nav className="flex-1 px-2 py-4 overflow-y-auto">
                        {mainNavItems.map((category, categoryIndex) => (
                          <div key={categoryIndex} className="mb-6">
                            <h3 className="px-3 text-xs font-semibold text-indigo-300 uppercase tracking-wider mb-2">
                              {category.category}
                            </h3>
                            <div className="space-y-1">
                              {category.items.map((item) => {
                                const isActive =
                                  location.pathname === item.path;
                                return (
                                  <button
                                    key={item.text}
                                    onClick={() =>
                                      handleNavItemClick(item.path, item.text)
                                    }
                                    className={`flex items-center w-full px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200
                              ${
                                isActive
                                  ? "bg-white text-indigo-700 shadow-md"
                                  : "text-indigo-100 hover:bg-indigo-700 hover:text-white"
                              } ${item.color || ""}`}
                                  >
                                    <span
                                      className={`text-lg mr-3 ${
                                        isActive
                                          ? "text-indigo-600"
                                          : "text-indigo-300"
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
                  </Dialog.Panel>
                </Transition.Child>
              </div>
            </div>
          </div>
        </Dialog>
      </Transition.Root>

      {/* Main Content */}
      <div className="flex flex-col flex-1 overflow-hidden">
        {/* Header */}
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
              {/* Live Status Toggle */}
              {/* User Profile or Login Button */}
              {user && (
                <div className="flex items-center space-x-2">
                  {/* Live status text */}
                  <span className="text-sm text-gray-600 hidden md:block">
                    {isLive ? "Live" : "Offline"}
                  </span>

                  {/* Toggle switch */}
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

                  {/* Camera icon */}
                  <VideoCameraIcon
                    className={`h-5 w-5 ${
                      isLive ? "text-green-500" : "text-gray-400"
                    }`}
                  />
                </div>
              )}

              {/* User Profile or Login Button */}
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
                          {user.name}
                        </div>
                        <div className="text-xs text-gray-500">
                          {user.role || "Pet Owner"}
                        </div>
                      </div>
                    </div>
                  </button>

                  {isDropdownOpen && (
                    <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 z-50 border border-gray-200">
                      <div className="border-t border-gray-100 my-1"></div>
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

        {/* Main content area */}
        <main className="flex-1 bg-gray-50 overflow-y-auto">
          <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            {/* Page Header */}
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

            {/* Main Content */}
            <div>{children}</div>
          </div>
        </main>
      </div>
    </div>
  );
};

export default HeaderWithSidebar;
