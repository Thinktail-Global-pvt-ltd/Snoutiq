import React, { useState } from "react";
import { Link } from "react-router-dom";
import logo from "../assets/images/logo-dark.png";

const Header = () => {
  const [isOpen, setIsOpen] = useState(false);

  return (
    <nav className="bg-white shadow-md fixed top-0 left-0 right-0 z-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between h-16 items-center">
          {/* Logo / Brand */}
          <div className="cursor-pointer z-60">
            <img
              src={logo}
              className="h-8 cursor-pointer transition-transform hover:scale-105"
              alt="Logo"
            />
          </div>

          {/* Center Offers Slider */}
{/* Center Offers Slider */}
<div className="hidden md:flex justify-center mx-6 overflow-hidden relative w-[500px]">
  {/* Outer wrapper */}
  <div className="flex space-x-12 text-sm font-medium min-w-max animate-marquee">
    {/* First copy */}
    <span className="flex items-center space-x-2 text-blue-600">
      <span>🎁</span>
      <span>6 Months Premium FREE for Founders!</span>
    </span>
    <span className="flex items-center space-x-2 text-pink-600">
      <span>💎</span>
      <span>Lifetime Founder's Rate Locked</span>
    </span>
    <span className="flex items-center space-x-2 text-green-600">
      <span>🔥</span>
      <span>Exclusive Early Access to New Features</span>
    </span>
    <span className="flex items-center space-x-2 text-purple-600">
      <span>🚀</span>
      <span>Be Part of the Growth Journey</span>
    </span>

    {/* Duplicate copy for seamless loop */}
    <span className="flex items-center space-x-2 text-blue-600">
      <span>🎁</span>
      <span>6 Months Premium FREE for Founders!</span>
    </span>
    <span className="flex items-center space-x-2 text-pink-600">
      <span>💎</span>
      <span>Lifetime Founder's Rate Locked</span>
    </span>
    <span className="flex items-center space-x-2 text-green-600">
      <span>🔥</span>
      <span>Exclusive Early Access to New Features</span>
    </span>
    <span className="flex items-center space-x-2 text-purple-600">
      <span>🚀</span>
      <span>Be Part of the Growth Journey</span>
    </span>
  </div>
</div>



          {/* Desktop Menu */}
          <div className="hidden md:flex space-x-6">
            <Link
              to="/"
              className="text-gray-700 hover:text-blue-600 font-medium"
            >
              Home
            </Link>
            <Link
              to="/contact us"
              className="text-gray-700 hover:text-blue-600 font-medium"
            >
              Contact US
            </Link>
          </div>

          {/* Mobile Menu Button */}
          <div className="md:hidden">
            <button
              onClick={() => setIsOpen(!isOpen)}
              className="text-gray-700 focus:outline-none"
            >
              {/* Hamburger icon */}
              <svg
                className="w-6 h-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                {isOpen ? (
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M6 18L18 6M6 6l12 12"
                  />
                ) : (
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M4 6h16M4 12h16M4 18h16"
                  />
                )}
              </svg>
            </button>
          </div>
        </div>
      </div>

      {/* Mobile Drawer */}
      {isOpen && (
        <div className="md:hidden bg-white shadow-lg">
          <div className="flex flex-col px-4 py-3 space-y-3">
            <Link
              to="/"
              onClick={() => setIsOpen(false)}
              className="text-gray-700 hover:text-blue-600 font-medium"
            >
              Home
            </Link>
            <Link
              to="/contact us"
              className="text-gray-700 hover:text-blue-600 font-medium"
            >
              Contact US
            </Link>
          </div>
        </div>
      )}
    </nav>
  );
};

export default Header;
