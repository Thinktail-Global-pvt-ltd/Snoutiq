import React, { useState, useEffect, useContext, lazy } from "react";
import { useNavigate } from "react-router-dom";
import { Dialog, Transition } from "@headlessui/react";
import { Fragment } from "react";
import {
  Bars3Icon,
  XMarkIcon,
  ChatBubbleLeftRightIcon,
  UserIcon,
  ArrowRightOnRectangleIcon,
  HomeIcon,
  ChevronDownIcon,
} from "@heroicons/react/24/outline";
import {
  DocumentTextIcon,
  ShieldCheckIcon,
  Cog6ToothIcon,
  CurrencyDollarIcon,
  HeartIcon,
  TruckIcon,
} from "@heroicons/react/24/outline";
import Sidebar from "./Sidebar";
import RightSidebar from "./RightSidebar";
import OfferIcon from "../assets/images/offericon.webp";
import logo from "../assets/images/logo.webp";
import { AuthContext } from "../auth/AuthContext";

const Navbar = () => {
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  const [isPoliciesDropdownOpen, setIsPoliciesDropdownOpen] = useState(false);
  const [isLeftDrawerOpen, setIsLeftDrawerOpen] = useState(false);
  const [isRightDrawerOpen, setIsRightDrawerOpen] = useState(false);
  const navigate = useNavigate();
  const { user } = useContext(AuthContext);

  const handleLogin = () => {
    navigate("/login");
  };

  const handleLogout = () => {
    localStorage.clear();
    navigate("/");
    window.location.reload();
  };

  const toggleDropdown = () => {
    setIsDropdownOpen(!isDropdownOpen);
  };

  const togglePoliciesDropdown = () => {
    setIsPoliciesDropdownOpen(!isPoliciesDropdownOpen);
  };

  const closeLeftDrawer = () => setIsLeftDrawerOpen(false);
  const closeRightDrawer = () => setIsRightDrawerOpen(false);

  // Policies data
  const policies = [
    {
      title: "Privacy Policy",
      description:
        "How we collect, use, and protect your personal information.",
      href: "/privacy-policy",
      icon: <ShieldCheckIcon className="w-5 h-5 text-blue-500" />,
    },
    {
      title: "Terms of Service",
      description: "The rules and guidelines for using our platform.",
      href: "/terms-of-service",
      icon: <DocumentTextIcon className="w-5 h-5 text-green-500" />,
    },
    {
      title: "Cookie Policy",
      description: "Details on how we use cookies to enhance your experience.",
      href: "/cookie-policy",
      icon: <Cog6ToothIcon className="w-5 h-5 text-amber-500" />,
    },
    {
      title: "Refund & Cancellation Policy",
      description: "Learn about eligibility and process for refunds.",
      href: "/cancellation-policy",
      icon: <CurrencyDollarIcon className="w-5 h-5 text-purple-500" />,
    },
    {
      title: "Medical Data Consent",
      description: "How we handle and secure your medical-related data.",
      href: "/medical-data-consent",
      icon: <HeartIcon className="w-5 h-5 text-red-500" />,
    },
    {
      title: "Shipping Policy",
      description: "Information about shipping, delivery, and tracking.",
      href: "/shipping-policy",
      icon: <TruckIcon className="w-5 h-5 text-indigo-500" />,
    },
  ];

  // Mobile Layout Components
  const MobileFloatingButtons = () => (
    <div className="fixed right-4 bottom-32 z-40 flex flex-col gap-4">
      <button
        className="w-14 h-14 rounded-full bg-indigo-600 shadow-lg flex items-center justify-center hover:bg-indigo-700 transition-all duration-300 transform hover:scale-105"
        onClick={() => setIsRightDrawerOpen(true)}
      >
        <img src={OfferIcon} alt="Offers" className="w-7 h-7 filter invert" />
      </button>

      {/* <button
        className="w-14 h-14 rounded-full bg-emerald-500 shadow-lg flex items-center justify-center hover:bg-emerald-600 transition-all duration-300 transform hover:scale-105"
        onClick={() => setIsRightDrawerOpen(true)}
      >
        <img src={VetIcon} alt="Vet" className="w-7 h-7 filter invert" />
      </button>

      <button
        className="w-14 h-14 rounded-full bg-amber-500 shadow-lg flex items-center justify-center hover:bg-amber-600 transition-all duration-300 transform hover:scale-105"
        onClick={() => setIsRightDrawerOpen(true)}
      >
        <img src={groomericon} alt="Groomer" className="w-7 h-7 filter invert" />
      </button> */}
    </div>
  );

  const MobileDrawers = () => (
    <>
      {/* Left Drawer - Chat History */}
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
                          className="rounded-md text-white hover:text-gray-200 transition-colors"
                          onClick={() => setIsLeftDrawerOpen(false)}
                          aria-label="Close menu"
                        >
                          <XMarkIcon className="w-6 h-6" aria-hidden="true" />
                        </button>
                      </div>
                      <div className="flex-1 overflow-y-auto">
                        <div className="px-4 py-4">
                          <Sidebar
                            isMobile={true}
                            onItemClick={closeLeftDrawer}
                          />
                        </div>
                      </div>
                    </div>
                  </Dialog.Panel>
                </Transition.Child>
              </div>
            </div>
          </div>
        </Dialog>
      </Transition.Root>

      {/* Right Drawer - Additional Features */}
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
                          className="rounded-md text-white hover:text-gray-200 transition-colors"
                          onClick={() => setIsRightDrawerOpen(false)}
                          aria-label="Close menu"
                        >
                          <XMarkIcon className="w-6 h-6" aria-hidden="true" />
                        </button>
                      </div>
                      <div className="flex-1 overflow-y-auto">
                        <div className="w-full">
                          <RightSidebar
                            isMobile={true}
                            onItemClick={closeRightDrawer}
                          />
                        </div>
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
          {/* Logo and Brand */}
          <div className="flex items-center space-x-3">
            <img
              src={logo}
              alt="Snoutiq Logo"
              loading="lazy"
              className="h-6 cursor-pointer transition-transform hover:scale-105"
              onClick={() => navigate(user ? "/dashboard" : "/")}
            />
          </div>

          {/* Navigation Links */}
          <div className="flex items-center px-2 space-x-6">
            {/* Policies Dropdown */}
            <div className="relative">
              <button
                className="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 flex items-center justify-center h-12"
                onClick={togglePoliciesDropdown}
              >
                <span>Policies &nbsp; </span>
                <ChevronDownIcon
                  className={`w-4 h-4 transition-transform ${
                    isPoliciesDropdownOpen ? "rotate-180" : ""
                  }`}
                />
              </button>

              {isPoliciesDropdownOpen && (
                <div className="absolute top-full left-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-gray-200 py-3 z-50">
                  <div className="px-4 py-2 border-b border-gray-100">
                    <h3 className="font-semibold text-gray-800 text-sm">
                      Company Policies
                    </h3>
                    <p className="text-xs text-gray-500 mt-1">
                      Learn about our policies and terms
                    </p>
                  </div>
                  <div className="max-h-96 overflow-y-auto">
                    {policies.map((policy, index) => (
                      <a
                        key={index}
                        href={policy.href}
                        className="flex items-start px-4 py-3 hover:bg-gray-50 transition-colors group"
                      >
                        <div className="flex-shrink-0 mt-0.5 mr-3">
                          {policy.icon}
                        </div>
                        <div className="flex-1 min-w-0">
                          <p className="text-sm font-medium text-gray-900 group-hover:text-indigo-600 transition-colors">
                            {policy.title}
                          </p>
                          <p className="text-xs text-gray-500 mt-1 truncate">
                            {policy.description}
                          </p>
                        </div>
                      </a>
                    ))}
                  </div>
                </div>
              )}
            </div>
            {/* <div>
              <a
                href="https://snoutiq.com/blog"
                className="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 flex items-center justify-center h-12"
              >
                <div className="flex items-center space-x-2">
                  <ChatBubbleLeftRightIcon className="w-4 h-4" />
                  <span>Tail Talks</span>
                </div>
              </a>
            </div> */}
            {/* User Profile or Login Button */}
            <div className="flex items-center  h-full">
              {!user ? (
                <button
                  onClick={handleLogin}
                  className="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 flex items-center justify-center h-12"
                >
                  Login
                </button>
              ) : (
                <div className="relative">
                  <div
                    className="bg-white rounded-lg px-4 py-3 flex items-center space-x-3 shadow-sm hover:shadow-md transition-shadow cursor-pointer border border-gray-200"
                    onClick={toggleDropdown}
                  >
                    <div className="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg flex items-center justify-center">
                      <UserIcon className="w-4 h-4 text-white" />
                    </div>
                    <div className="flex flex-col">
                      <div className="text-sm font-semibold text-gray-800">
                        {user.name || user.business_status}
                      </div>
                      <div className="text-xs text-gray-500">
                        {user.role + " Owner"}
                      </div>
                    </div>
                    <svg
                      className="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform"
                      style={{
                        transform: isDropdownOpen
                          ? "rotate(180deg)"
                          : "rotate(0deg)",
                      }}
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
                    <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50 border border-gray-200">
                      <button
                        onClick={() => navigate("/user-dashboard")}
                        className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors"
                      >
                        <HomeIcon className="w-4 h-4 mr-2" />
                        Dashboard
                      </button>
                      <button
                        onClick={handleLogout}
                        className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors"
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

        {/* Mobile Navbar */}
        <div className="md:hidden">
          <div className="flex items-center justify-between px-4 py-3 h-[60px]">
            {/* Mobile Menu Button */}
            <button
              onClick={() => setIsLeftDrawerOpen(true)}
              className="p-2 rounded-lg bg-gray-100 hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
              aria-label="Open menu"
            >
              <Bars3Icon className="w-6 h-6 text-gray-700" aria-hidden="true" />
            </button>

            {/* Mobile Logo and Title */}
            <div className="flex items-center space-x-2">
              <img
                src={logo}
                alt="Snoutiq Logo"
                className="h-6 cursor-pointer"
                onClick={() => navigate(user ? "/dashboard" : "/")}
              />
            </div>
            <div className="flex space-x-3">
              {/* Policies Dropdown for Mobile */}
              <div className="relative">
                <button
                  onClick={togglePoliciesDropdown}
                  className="bg-white rounded-lg p-2 flex items-center shadow-sm hover:shadow-md transition-shadow cursor-pointer border border-gray-200"
                  aria-label="Open policies menu"
                  aria-expanded={isPoliciesDropdownOpen}
                >
                  <DocumentTextIcon
                    className="w-6 h-6 text-gray-700"
                    aria-hidden="true"
                  />
                </button>

                {isPoliciesDropdownOpen && (
                  <div className="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-lg border border-gray-200 py-2 z-50">
                    <div className="px-4 py-2 border-b border-gray-100">
                      <h3 className="font-semibold text-gray-800 text-sm">
                        Company Policies
                      </h3>
                    </div>
                    <div className="max-h-64 overflow-y-auto">
                      {policies.map((policy, index) => (
                        <a
                          key={index}
                          href={policy.href}
                          className="flex items-center px-4 py-3 hover:bg-gray-50 transition-colors"
                          onClick={() => setIsPoliciesDropdownOpen(false)}
                        >
                          <div className="flex-shrink-0 mr-3">
                            {policy.icon}
                          </div>
                          <span className="text-sm font-medium text-gray-900">
                            {policy.title}
                          </span>
                        </a>
                      ))}
                    </div>
                  </div>
                )}
              </div>

              {/* Mobile User Profile or Login Button */}
              <div className="flex items-center">
                {!user ? (
                  <div>
                    <button
                      onClick={handleLogin}
                      className="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm"
                    >
                      Login
                    </button>
                  </div>
                ) : (
                  <div className="relative">
                    <div
                      className="bg-white rounded-lg p-2 flex items-center shadow-sm hover:shadow-md transition-shadow cursor-pointer border border-gray-200"
                      onClick={toggleDropdown}
                    >
                      <div className="w-6 h-6 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg flex items-center justify-center">
                        <UserIcon className="w-3 h-3 text-white" />
                      </div>
                    </div>
                    {isDropdownOpen && (
                      <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50 border border-gray-200">
                        <button
                          onClick={() => navigate("/user-dashboard/*")}
                          className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors"
                        >
                          <HomeIcon className="w-4 h-4 mr-2" />
                          Dashboard
                        </button>
                        <button
                          onClick={handleLogout}
                          className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors"
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
        </div>
      </nav>

      {/* Mobile Components */}
      <div className="md:hidden">
        <MobileFloatingButtons />
      </div>
      <MobileDrawers />

      {/* Overlay for dropdowns */}
      {(isPoliciesDropdownOpen || isDropdownOpen) && (
        <div
          className="fixed inset-0 z-40 bg-black bg-opacity-10"
          onClick={() => {
            setIsPoliciesDropdownOpen(false);
            setIsDropdownOpen(false);
          }}
        />
      )}
    </>
  );
};

export default Navbar;
