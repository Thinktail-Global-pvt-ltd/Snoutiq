import React from "react";
import logo from "../assets/images/logo1.png"; 

const Footer = () => {
  return (
    <footer className="bg-gray-900 text-gray-300 py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col md:flex-row items-center justify-between space-y-6 md:space-y-0">
          
          {/* Brand / Logo */}
          <div className="flex items-center space-x-3">
            <img 
              src={logo} 
              alt="Snoutiq Logo"  
              className="h-8 cursor-pointer transition-transform hover:scale-105" 
            />
          </div>

          {/* Links */}
          <div className="flex flex-wrap justify-center md:justify-end gap-4 text-sm font-medium">
            <a href="/frontend/files" className="hover:text-white transition-colors">
              Home
            </a>
            <a href="/frontend/files/contact-us" className="hover:text-white transition-colors">
              Contact Us
            </a>
            <a href="/frontend/files/privacypolicy" className="hover:text-white transition-colors">
              Privacy Policy
            </a>
            <a href="/frontend/files/tearms" className="hover:text-white transition-colors">
              Terms & Conditions
            </a>
            <a href="/frontend/files/Cancellation_Refund_Policy" className="hover:text-white transition-colors">
              Cancellation & Refund
            </a>
            <a href="/frontend/files/shipping_policy" className="hover:text-white transition-colors">
              Shipping Policy
            </a>
            <a href="/frontend/files/MedicalDataConsent" className="hover:text-white transition-colors">
              Medical Data Consent
            </a>
            <a href="/frontend/files/Cookie_policy" className="hover:text-white transition-colors">
              Cookie Policy
            </a>
          </div>

          {/* Copyright */}
          <div className="text-sm text-gray-400 text-center md:text-right">
            © {new Date().getFullYear()} Snoutiq. All rights reserved.
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
