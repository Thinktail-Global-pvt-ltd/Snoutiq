// import React, { useState, useEffect, useContext, Fragment, lazy } from "react";
// import { useNavigate } from "react-router-dom";
// import { Dialog, Transition } from "@headlessui/react";

// // ✅ Tree-shaking optimized imports
// import Bars3Icon from "@heroicons/react/24/outline/Bars3Icon";
// import XMarkIcon from "@heroicons/react/24/outline/XMarkIcon";
// import ChatBubbleLeftRightIcon from "@heroicons/react/24/outline/ChatBubbleLeftRightIcon";
// import UserIcon from "@heroicons/react/24/outline/UserIcon";
// import ArrowRightOnRectangleIcon from "@heroicons/react/24/outline/ArrowRightOnRectangleIcon";
// import HomeIcon from "@heroicons/react/24/outline/HomeIcon";
// import HeartIcon from "@heroicons/react/24/outline/HeartIcon";

// const Sidebar = lazy(() => import("./Sidebar"));
// const RightSidebar = lazy(() => import("./RightSidebar"));
// import logo from "../assets/images/logo.webp";
// import { AuthContext } from "../auth/AuthContext";

// const Header = () => {
//   const [isDropdownOpen, setIsDropdownOpen] = useState(false);
//   const [isLeftDrawerOpen, setIsLeftDrawerOpen] = useState(false);
//   const [isRightDrawerOpen, setIsRightDrawerOpen] = useState(false);
//   const [loading, setLoading] = useState(true);

//   const navigate = useNavigate();
//   const { user } = useContext(AuthContext);

//   const [isUserLoaded, setIsUserLoaded] = useState(false);

//   // ✅ Better logout (no full reload)
//   const handleLogout = () => {
//     localStorage.clear();
//     navigate("/");
//   };

//   const handleLogin = () => navigate("/login");
//   const handleRegister = () => navigate("/register?utm_source=facebook&utm_medium=paid_social&utm_campaign=pet_emergency_test1&utm_content=chat_conversion");
//   const toggleDropdown = () => setIsDropdownOpen((prev) => !prev);

//   // ✅ Close dropdown on outside click
//   useEffect(() => {
//     const handleClickOutside = (e) => {
//       if (!e.target.closest(".dropdown")) {
//         setIsDropdownOpen(false);
//       }
//     };
//     document.addEventListener("mousedown", handleClickOutside);
//     return () => document.removeEventListener("mousedown", handleClickOutside);
//   }, []);

//   return (
//     <>
//       <nav className="bg-white border-b border-gray-200 fixed top-0 left-0 right-0 z-50 shadow-sm">
//         {/* Desktop Navbar */}
//         <div className="hidden md:flex items-center justify-between px-6 py-4 h-[70px]">
//           {/* Logo */}
//           <div className="flex items-center space-x-3">
//             <img
//               src={logo}
//               alt="SnoutIQ Logo"
//               loading="lazy"
//               className="h-5 cursor-pointer transition-transform hover:scale-105"
//               onClick={() => navigate(user ? "/dashboard" : "/")}
//             />
//           </div>

//           {/* User Actions */}
//           <div className="flex items-center space-x-4 h-full">
//             {!user ? (
//               <>
//                 <button
//                   onClick={handleRegister}
//                   className="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-0.5"
//                 >
//                   Register
//                 </button>
//                 <button
//                   onClick={handleLogin}
//                   className="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-0.5"
//                 >
//                   Login
//                 </button>
//               </>
//             ) : (
//               <div className="relative dropdown">
//                 <div
//                   className="bg-white rounded-lg px-4 py-3 flex items-center space-x-3 shadow-sm hover:shadow-md border border-gray-200 cursor-pointer"
//                   onClick={toggleDropdown}
//                 >
//                   <div className="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg flex items-center justify-center">
//                     <UserIcon className="w-4 h-4 text-white" />
//                   </div>
//                   <div className="flex flex-col">
//                     <div className="text-sm font-semibold text-gray-800">
//                       {user.name}
//                     </div>
//                     <div className="text-xs text-gray-500">Pet Owner</div>
//                   </div>
//                   <svg
//                     className={`w-4 h-4 text-gray-400 transition-transform ${
//                       isDropdownOpen ? "rotate-180" : ""
//                     }`}
//                     fill="none"
//                     stroke="currentColor"
//                     viewBox="0 0 24 24"
//                   >
//                     <path
//                       strokeLinecap="round"
//                       strokeLinejoin="round"
//                       strokeWidth={2}
//                       d="M19 9l-7 7-7-7"
//                     />
//                   </svg>
//                 </div>

//                 {isDropdownOpen && (
//                   <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 border border-gray-200 z-50">
//                     <button
//                       onClick={() => navigate("/dashboard")}
//                       className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600"
//                     >
//                       <HomeIcon className="w-4 h-4 mr-2" />
//                       Dashboard
//                     </button>
//                     <button
//                       onClick={() => navigate("/pet-info")}
//                       className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600"
//                     >
//                       <HeartIcon className="w-4 h-4 mr-2" />
//                       My Pets
//                     </button>
//                     <button
//                       onClick={handleLogout}
//                       className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600"
//                     >
//                       <ArrowRightOnRectangleIcon className="w-4 h-4 mr-2" />
//                       Logout
//                     </button>
//                   </div>
//                 )}
//               </div>
//             )}
//           </div>
//         </div>

//         {/* Mobile Navbar */}
//         <div className="md:hidden">
//           <div className="flex items-center justify-between px-4 py-3 h-[60px]">
//             {isUserLoaded && user && (
//               <Suspense fallback={<div>Loading menu...</div>}>
//                 <button
//                   aria-label="Open menu"
//                   onClick={() => setIsLeftDrawerOpen(true)}
//                   className="p-2 rounded-lg bg-gray-100 hover:bg-indigo-50 transition-colors"
//                 >
//                   <Bars3Icon className="w-6 h-6 text-gray-700" />
//                 </button>
//               </Suspense>
//             )}

//             <img
//               src={logo}
//               alt="SnoutIQ Logo"
//               loading="lazy"
//               className="h-5 cursor-pointer transition-transform hover:scale-105"
//               onClick={() => navigate(user ? "/dashboard" : "/")}
//             />
//             <div className="flex items-center">
//               {!user ? (
//                 <div className="flex gap-1 flex-shrink-0">
//                   <button
//                     onClick={handleRegister}
//                     className="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 text-sm"
//                   >
//                     Register
//                   </button>
//                   <button
//                     onClick={handleLogin}
//                     className="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 text-sm"
//                   >
//                     Login
//                   </button>
//                 </div>
//               ) : (
//                 <div className="relative dropdown">
//                   <div
//                     className="bg-white rounded-lg p-2 flex items-center shadow-sm hover:shadow-md border border-gray-200 cursor-pointer"
//                     onClick={toggleDropdown}
//                   >
//                     <div className="w-6 h-6 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg flex items-center justify-center">
//                       <UserIcon className="w-3 h-3 text-white" />
//                     </div>
//                   </div>

//                   {isDropdownOpen && (
//                     <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 border border-gray-200 z-50">
//                       <button
//                         onClick={() => navigate("/dashboard")}
//                         className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600"
//                       >
//                         <HomeIcon className="w-4 h-4 mr-2" />
//                         Dashboard
//                       </button>
//                       <button
//                         onClick={() => navigate("/pet-info")}
//                         className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600"
//                       >
//                         <HeartIcon className="w-4 h-4 mr-2" />
//                         My Pets
//                       </button>
//                       <button
//                         onClick={handleLogout}
//                         className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600"
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
//         </div>
//       </nav>

//     </>
//   );
// };

// export default Header;

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
import "../pages/DiwaliHome.css";

const Header = () => {
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  const [isLeftDrawerOpen, setIsLeftDrawerOpen] = useState(false);
  const [isRightDrawerOpen, setIsRightDrawerOpen] = useState(false);
  const [loading, setLoading] = useState(true);

  const navigate = useNavigate();
  const { user } = useContext(AuthContext);

  const [isUserLoaded, setIsUserLoaded] = useState(false);

  // ✅ Better logout (no full reload)
  const handleLogout = () => {
    localStorage.clear();
    navigate("/");
  };

  const handleLogin = () => navigate("/login");
  const handleRegister = () => navigate("/register?utm_source=facebook&utm_medium=paid_social&utm_campaign=pet_emergency_test1&utm_content=chat_conversion");
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

  // Diwali lights animation
  const DiwaliLights = () => (
    <div className="diwali-lights-container">
      {[...Array(20)].map((_, i) => (
        <div 
          key={i} 
          className={`diwali-light light-${i % 5}`}
          style={{ left: `${(i * 5)}%` }}
        />
      ))}
    </div>
  );

  // Floating diya particles
  const FloatingDiyas = () => (
    <div className="header-diyas">
      {[...Array(6)].map((_, i) => (
        <div key={i} className={`header-diya h-diya-${i + 1}`}>🪔</div>
      ))}
    </div>
  );

  return (
    <>
      <DiwaliLights />
      <FloatingDiyas />
      
      <nav className="diwali-header bg-white border-b border-orange-200 fixed top-0 left-0 right-0 z-50 shadow-lg">
        {/* Desktop Navbar */}
        <div className="hidden md:flex items-center justify-between px-6 py-4 h-[70px] relative">
          {/* Logo with Diwali Theme */}
          <div className="flex items-center space-x-3 relative">
            <div className="logo-container relative">
              <img
                src={logo}
                alt="SnoutIQ Logo"
                loading="lazy"
                className="h-5 cursor-pointer transition-all duration-300 hover:scale-110 relative z-10"
                onClick={() => navigate(user ? "/dashboard" : "/")}
              />
              <div className="logo-glow"></div>
              <div className="diya-sparkle">✨</div>
            </div>
            <span className="diwali-badge bg-gradient-to-r from-orange-500 to-yellow-500 text-white px-2 py-1 rounded-full text-xs font-semibold">
              Diwali Special
            </span>
          </div>

          {/* User Actions */}
          <div className="flex items-center space-x-4 h-full">
            {!user ? (
              <>
                <button
                  onClick={handleRegister}
                  className="diwali-btn bg-gradient-to-r from-orange-500 to-yellow-500 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-0.5 hover:scale-105"
                >
                  🪔 Register
                </button>
                <button
                  onClick={handleLogin}
                  className="diwali-btn-secondary border-2 border-orange-500 text-orange-600 px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-0.5 hover:bg-orange-50"
                >
                  Login
                </button>
              </>
            ) : (
              <div className="relative dropdown">
                <div
                  className="diwali-user-card bg-white rounded-lg px-4 py-3 flex items-center space-x-3 shadow-sm hover:shadow-md border border-orange-200 cursor-pointer transition-all duration-300 hover:border-orange-400"
                  onClick={toggleDropdown}
                >
                  <div className="w-8 h-8 bg-gradient-to-r from-orange-500 to-yellow-500 rounded-lg flex items-center justify-center relative">
                    <UserIcon className="w-4 h-4 text-white" />
                    <div className="user-glow"></div>
                  </div>
                  <div className="flex flex-col">
                    <div className="text-sm font-semibold text-gray-800">
                      {user.name}
                    </div>
                    <div className="text-xs text-orange-600 font-medium">Pet Owner</div>
                  </div>
                  <svg
                    className={`w-4 h-4 text-orange-500 transition-transform ${
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
                  <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl py-2 border border-orange-200 z-50 diwali-dropdown">
                    <div className="dropdown-sparkle">🎆</div>
                    <button
                      onClick={() => navigate("/dashboard")}
                      className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 transition-colors duration-200"
                    >
                      <HomeIcon className="w-4 h-4 mr-2" />
                      Dashboard
                    </button>
                    <button
                      onClick={() => navigate("/pet-info")}
                      className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 transition-colors duration-200"
                    >
                      <HeartIcon className="w-4 h-4 mr-2" />
                      My Pets
                    </button>
                    <button
                      onClick={handleLogout}
                      className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 transition-colors duration-200"
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
          <div className="flex items-center justify-between px-4 py-3 h-[60px] relative">
            {isUserLoaded && user && (
              <Suspense fallback={<div>Loading menu...</div>}>
                <button
                  aria-label="Open menu"
                  onClick={() => setIsLeftDrawerOpen(true)}
                  className="p-2 rounded-lg bg-orange-100 hover:bg-orange-200 transition-colors diwali-menu-btn"
                >
                  <Bars3Icon className="w-6 h-6 text-orange-700" />
                </button>
              </Suspense>
            )}

            <div className="logo-container relative">
              <img
                src={logo}
                alt="SnoutIQ Logo"
                loading="lazy"
                className="h-5 cursor-pointer transition-all duration-300 hover:scale-110 relative z-10"
                onClick={() => navigate(user ? "/dashboard" : "/")}
              />
              <div className="logo-glow"></div>
            </div>

            <div className="flex items-center">
              {!user ? (
                <div className="flex gap-1 flex-shrink-0">
                  <button
                    onClick={handleRegister}
                    className="diwali-btn bg-gradient-to-r from-orange-500 to-yellow-500 text-white px-3 py-2 rounded-lg hover:bg-orange-600 text-sm transition-all duration-300"
                  >
                    🪔
                  </button>
                  <button
                    onClick={handleLogin}
                    className="diwali-btn-secondary border border-orange-500 text-orange-600 px-3 py-2 rounded-lg hover:bg-orange-50 text-sm transition-all duration-300"
                  >
                    Login
                  </button>
                </div>
              ) : (
                <div className="relative dropdown">
                  <div
                    className="diwali-user-card bg-white rounded-lg p-2 flex items-center shadow-sm hover:shadow-md border border-orange-200 cursor-pointer transition-all duration-300"
                    onClick={toggleDropdown}
                  >
                    <div className="w-6 h-6 bg-gradient-to-r from-orange-500 to-yellow-500 rounded-lg flex items-center justify-center relative">
                      <UserIcon className="w-3 h-3 text-white" />
                      <div className="user-glow"></div>
                    </div>
                  </div>

                  {isDropdownOpen && (
                    <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl py-2 border border-orange-200 z-50 diwali-dropdown">
                      <div className="dropdown-sparkle">🎆</div>
                      <button
                        onClick={() => navigate("/dashboard")}
                        className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 transition-colors duration-200"
                      >
                        <HomeIcon className="w-4 h-4 mr-2" />
                        Dashboard
                      </button>
                      <button
                        onClick={() => navigate("/pet-info")}
                        className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 transition-colors duration-200"
                      >
                        <HeartIcon className="w-4 h-4 mr-2" />
                        My Pets
                      </button>
                      <button
                        onClick={handleLogout}
                        className="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 transition-colors duration-200"
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
    </>
  );
};

export default Header;