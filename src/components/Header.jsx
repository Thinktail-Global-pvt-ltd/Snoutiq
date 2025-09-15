import React, { useState, useEffect, useContext, Fragment, lazy } from "react";
import { useNavigate } from "react-router-dom";
import { Dialog, Transition } from "@headlessui/react";

// ✅ Tree-shaking optimized imports
import Bars3Icon from "@heroicons/react/24/outline/Bars3Icon";
import XMarkIcon from "@heroicons/react/24/outline/XMarkIcon";
import ChatBubbleLeftRightIcon from "@heroicons/react/24/outline/ChatBubbleLeftRightIcon";
import UserIcon from "@heroicons/react/24/outline/UserIcon";
import ArrowRightOnRectangleIcon from "@heroicons/react/24/outline/ArrowRightOnRectangleIcon";
import HomeIcon from "@heroicons/react/24/outline/HomeIcon";
import HeartIcon from "@heroicons/react/24/outline/HeartIcon";

const Sidebar = lazy(() => import("./Sidebar"));
const RightSidebar = lazy(() => import("./RightSidebar"));
import logo from "../assets/images/logo.webp";
import { AuthContext } from "../auth/AuthContext";

const Header = () => {
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  const [isLeftDrawerOpen, setIsLeftDrawerOpen] = useState(false);
  const [isRightDrawerOpen, setIsRightDrawerOpen] = useState(false);
  const [loading, setLoading] = useState(true);

  const navigate = useNavigate();
  const { user, setUser } = useContext(AuthContext);

  const [isUserLoaded, setIsUserLoaded] = useState(false);

  useEffect(() => {
    // Simulate checking localStorage or API call
    const storedUser = JSON.parse(localStorage.getItem("user"));
    if (storedUser) {
      setUser(storedUser);
    }
    setIsUserLoaded(true); // Now we know if user exists
  }, []);

  // ✅ Better logout (no full reload)
  const handleLogout = () => {
    localStorage.clear();
    setUser(null);
    navigate("/");
  };

  const handleLogin = () => navigate("/login");
  const handleRegister = () => navigate("/register");
  const toggleDropdown = () => setIsDropdownOpen((prev) => !prev);

  // ✅ Close dropdown on outside click
  useEffect(() => {
    const handleClickOutside = (e) => {
      if (!e.target.closest(".dropdown")) {
        setIsDropdownOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  // ✅ Mobile Drawers extracted inline
  const MobileDrawers = () => (
    <>
      {/* Left Drawer */}
      <Transition.Root show={isLeftDrawerOpen} as={Fragment}>
        <Dialog
          as="div"
          className="relative z-50"
          onClose={setIsLeftDrawerOpen}
        >
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
                    <div className="flex h-full flex-col bg-white shadow-xl">
                      <div className="flex items-center justify-between px-6 py-5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
                        <Dialog.Title className="text-lg font-semibold">
                          Chat History
                        </Dialog.Title>
                        <button
                          type="button"
                          aria-label="Close drawer"
                          className="rounded-md hover:text-gray-200 transition-colors"
                          onClick={() => setIsLeftDrawerOpen(false)}
                        >
                          <XMarkIcon className="w-6 h-6" />
                        </button>
                      </div>
                      <div className="flex-1 overflow-y-auto px-4 py-4">
                        <Sidebar
                          isMobile={true}
                          onItemClick={() => setIsLeftDrawerOpen(false)}
                        />
                      </div>
                    </div>
                  </Dialog.Panel>
                </Transition.Child>
              </div>
            </div>
          </div>
        </Dialog>
      </Transition.Root>

      {/* Right Drawer */}
      <Transition.Root show={isRightDrawerOpen} as={Fragment}>
        <Dialog
          as="div"
          className="relative z-50"
          onClose={setIsRightDrawerOpen}
        >
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
              <div className="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                <Transition.Child
                  as={Fragment}
                  enter="transform transition ease-in-out duration-300"
                  enterFrom="translate-x-full"
                  enterTo="translate-x-0"
                  leave="transform transition ease-in-out duration-300"
                  leaveFrom="translate-x-0"
                  leaveTo="translate-x-full"
                >
                  <Dialog.Panel className="pointer-events-auto w-[260px]">
                    <div className="flex h-full flex-col bg-white shadow-xl">
                      <div className="flex items-center justify-between px-6 py-5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
                        <Dialog.Title className="text-lg font-semibold">
                          Features & Offers
                        </Dialog.Title>
                        <button
                          type="button"
                          aria-label="Close drawer"
                          className="rounded-md hover:text-gray-200 transition-colors"
                          onClick={() => setIsRightDrawerOpen(false)}
                        >
                          <XMarkIcon className="w-6 h-6" />
                        </button>
                      </div>
                      <div className="flex-1 overflow-y-auto">
                        <RightSidebar
                          isMobile={true}
                          onItemClick={() => setIsRightDrawerOpen(false)}
                        />
                      </div>
                    </div>
                  </Dialog.Panel>
                </Transition.Child>
              </div>
            </div>
          </div>
        </Dialog>
      </Transition.Root>
    </>
  );

  return (
    <>
      <nav className="bg-white border-b border-gray-200 fixed top-0 left-0 right-0 z-50 shadow-sm">
        {/* Desktop Navbar */}
        <div className="hidden md:flex items-center justify-between px-6 py-4 h-[70px]">
          {/* Logo */}
          <div className="flex items-center space-x-3">
            <img
              src={logo}
              alt="SnoutIQ Logo"
              loading="lazy"
              className="h-5 cursor-pointer transition-transform hover:scale-105"
              onClick={() => navigate(user ? "/dashboard" : "/")}
            />
          </div>

          {/* User Actions */}
          <div className="flex items-center space-x-4 h-full">
            {!user ? (
              <>
                <button
                  onClick={handleRegister}
                  className="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-0.5"
                >
                  Register
                </button>
                <button
                  onClick={handleLogin}
                  className="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-0.5"
                >
                  Login
                </button>
              </>
            ) : (
              <div className="relative dropdown">
                <div
                  className="bg-white rounded-lg px-4 py-3 flex items-center space-x-3 shadow-sm hover:shadow-md border border-gray-200 cursor-pointer"
                  onClick={toggleDropdown}
                >
                  <div className="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg flex items-center justify-center">
                    <UserIcon className="w-4 h-4 text-white" />
                  </div>
                  <div className="flex flex-col">
                    <div className="text-sm font-semibold text-gray-800">
                      {user.name}
                    </div>
                    <div className="text-xs text-gray-500">Pet Owner</div>
                  </div>
                  <svg
                    className={`w-4 h-4 text-gray-400 transition-transform ${
                      isDropdownOpen ? "rotate-180" : ""
                    }`}
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M19 9l-7 7-7-7"
                    />
                  </svg>
                </div>

                {isDropdownOpen && (
                  <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 border border-gray-200 z-50">
                    <button
                      onClick={() => navigate("/dashboard")}
                      className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600"
                    >
                      <HomeIcon className="w-4 h-4 mr-2" />
                      Dashboard
                    </button>
                    <button
                      onClick={() => navigate("/pet-info")}
                      className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600"
                    >
                      <HeartIcon className="w-4 h-4 mr-2" />
                      My Pets
                    </button>
                    <button
                      onClick={handleLogout}
                      className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600"
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

        {/* Mobile Navbar */}
        <div className="md:hidden">
          <div className="flex items-center justify-between px-4 py-3 h-[60px]">
            {isUserLoaded && user && (
              <Suspense fallback={<div>Loading menu...</div>}>
                <button
                  aria-label="Open menu"
                  onClick={() => setIsLeftDrawerOpen(true)}
                  className="p-2 rounded-lg bg-gray-100 hover:bg-indigo-50 transition-colors"
                >
                  <Bars3Icon className="w-6 h-6 text-gray-700" />
                </button>
              </Suspense>
            )}

            <img
              src={logo}
              alt="SnoutIQ Logo"
              loading="lazy"
              className="h-5 cursor-pointer transition-transform hover:scale-105"
              onClick={() => navigate(user ? "/dashboard" : "/")}
            />
            <div className="flex items-center">
              {!user ? (
                <div className="flex gap-1 flex-shrink-0">
                  <button
                    onClick={handleRegister}
                    className="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 text-sm"
                  >
                    Register
                  </button>
                  <button
                    onClick={handleLogin}
                    className="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 text-sm"
                  >
                    Login
                  </button>
                </div>
              ) : (
                <div className="relative dropdown">
                  <div
                    className="bg-white rounded-lg p-2 flex items-center shadow-sm hover:shadow-md border border-gray-200 cursor-pointer"
                    onClick={toggleDropdown}
                  >
                    <div className="w-6 h-6 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg flex items-center justify-center">
                      <UserIcon className="w-3 h-3 text-white" />
                    </div>
                  </div>

                  {isDropdownOpen && (
                    <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 border border-gray-200 z-50">
                      <button
                        onClick={() => navigate("/dashboard")}
                        className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600"
                      >
                        <HomeIcon className="w-4 h-4 mr-2" />
                        Dashboard
                      </button>
                      <button
                        onClick={() => navigate("/pet-info")}
                        className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600"
                      >
                        <HeartIcon className="w-4 h-4 mr-2" />
                        My Pets
                      </button>
                      <button
                        onClick={handleLogout}
                        className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600"
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
        </div>
      </nav>

      {/* Mobile Drawers */}
      {isUserLoaded && user && (
        <Suspense fallback={<div>Loading menu...</div>}>
          <MobileDrawers />
        </Suspense>
      )}
    </>
  );
};

export default Header;
