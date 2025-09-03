import { FaFacebookSquare, FaLinkedin, FaPinterestSquare, FaMobileAlt, FaPaperPlane, FaHeart } from "react-icons/fa";
import { FaSquareXTwitter } from "react-icons/fa6";
import { FaLocationDot } from "react-icons/fa6";
import { Link } from "react-router-dom";

export default function Footer() {
  return (
    <footer className="relative bg-gradient-to-br from-gray-900 via-blue-900 to-gray-800 text-white overflow-hidden w-full">
      {/* Background Pattern */}
      <div className="absolute inset-0 opacity-10">
        <div className="absolute top-0 left-0 w-72 h-72 bg-blue-400 rounded-full mix-blend-multiply filter blur-xl animate-pulse"></div>
        <div className="absolute top-0 right-0 w-72 h-72 bg-purple-400 rounded-full mix-blend-multiply filter blur-xl animate-pulse animation-delay-2000"></div>
        <div className="absolute bottom-0 left-1/2 w-72 h-72 bg-pink-400 rounded-full mix-blend-multiply filter blur-xl animate-pulse animation-delay-4000"></div>
      </div>

      <div className="relative z-10">
        {/* Main Footer Content */}
        <div className="container mx-auto px-6 py-16">
          <div className="grid grid-cols-1 lg:grid-cols-4 gap-12">
            
            {/* Company Info - Enhanced */}
            <div className="lg:col-span-2">
              <div className="mb-6">
                <img
                  src="https://snoutiq.com/wp-content/uploads/2025/06/dark-bg.png"
                  alt="Snoutiq" 
                  className="w-48 bg-white p-3 rounded-xl shadow-2xl transform hover:scale-105 transition-transform duration-300"
                />
              </div>
              <p className="text-lg leading-relaxed mb-6 text-gray-200 max-w-lg">
                We're Tanul and Nisha—passionate pet parents to Sherif (our resilient rescue dog) and three incredible cats: Shadow, Tokyo, and Tiger. Each of our pets came into our lives through unique, sometimes challenging circumstances.
              </p>
              
              {/* Enhanced Social Media */}
              <div className="flex space-x-4 mb-8">
                <a href="#" className="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white hover:from-blue-600 hover:to-blue-700 transform hover:scale-110 hover:shadow-lg transition-all duration-300">
                  <FaFacebookSquare className="text-xl" />
                </a>
                <a href="#" className="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl flex items-center justify-center text-white hover:from-purple-600 hover:to-purple-700 transform hover:scale-110 hover:shadow-lg transition-all duration-300">
                  <FaSquareXTwitter className="text-xl" />
                </a>
                <a href="#" className="w-12 h-12 bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl flex items-center justify-center text-white hover:from-blue-700 hover:to-blue-800 transform hover:scale-110 hover:shadow-lg transition-all duration-300">
                  <FaLinkedin className="text-xl" />
                </a>
                <a href="#" className="w-12 h-12 bg-gradient-to-r from-red-500 to-red-600 rounded-xl flex items-center justify-center text-white hover:from-red-600 hover:to-red-700 transform hover:scale-110 hover:shadow-lg transition-all duration-300">
                  <FaPinterestSquare className="text-xl" />
                </a>
              </div>

              {/* Call to Action */}
              <div className="bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl p-6 text-center">
                <h3 className="text-xl font-bold mb-2">Ready to give your pet the best care?</h3>
                <p className="text-blue-100 mb-4">Join thousands of happy pet parents using Snoutiq AI</p>
                <Link to={'/dashboard'} className="bg-white text-blue-600 px-8 py-3 rounded-xl font-semibold hover:bg-gray-100 transform hover:scale-105 transition-all duration-300 shadow-lg">
                  Get Started Now
                </Link>
              </div>
            </div>

            {/* Important Links - Redesigned */}
            <div>
              <h4 className="text-2xl font-bold mb-6 bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">
                Important Links
              </h4>
              <div className="space-y-3">
                {[
                  { name: "Terms & Conditions", url: "https://snoutiq.com/terms-conditions/" },
                  { name: "Privacy Policy", url: "https://snoutiq.com/privacy-policy/" },
                  { name: "Cancellation & Refund", url: "https://snoutiq.com/cancellation-refund-policy/" },
                  { name: "Shipping & Delivery", url: "https://snoutiq.com/shipping-delivery-policy/" },
                  { name: "Medical Disclaimer", url: "https://snoutiq.com/medical-disclaimer/" },
                  { name: "User Consent", url: "https://snoutiq.com/user-consent/" },
                  { name: "Vendor Agreement", url: "https://snoutiq.com/vendor-seller-agreement/" },
                  { name: "Cookie Policy", url: "https://snoutiq.com/cookie-policy/" }
                ].map((link, index) => (
                  <a 
                    key={index}
                    href={link.url} 
                    className="block text-gray-300 hover:text-white transform hover:translate-x-2 transition-all duration-300 text-sm border-l-2 border-transparent hover:border-blue-400 pl-3 py-1"
                  >
                    {link.name}
                  </a>
                ))}
              </div>
            </div>

            {/* Contact Info - Enhanced */}
            <div>
              <h4 className="text-2xl font-bold mb-6 bg-gradient-to-r from-green-400 to-blue-400 bg-clip-text text-transparent">
                Get In Touch
              </h4>
              <div className="space-y-6">
                <div className="flex items-start space-x-4 group">
                  <div className="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                    <FaLocationDot className="text-white text-lg" />
                  </div>
                  <div>
                    <h5 className="font-semibold text-blue-300 mb-1">Our Location</h5>
                    <p className="text-gray-300 text-sm leading-relaxed">Plot no 20, Block: H-1/A, Sector-63, Noida-201301</p>
                  </div>
                </div>

                <div className="flex items-start space-x-4 group">
                  <div className="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                    <FaMobileAlt className="text-white text-lg" />
                  </div>
                  <div>
                    <h5 className="font-semibold text-green-300 mb-1">Call Us</h5>
                    <a href="tel:+91-8447220079" className="text-gray-300 hover:text-white transition-colors duration-300 text-sm">
                      +91-8447220079
                    </a>
                  </div>
                </div>

                <div className="flex items-start space-x-4 group">
                  <div className="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                    <FaPaperPlane className="text-white text-lg" />
                  </div>
                  <div>
                    <h5 className="font-semibold text-purple-300 mb-1">Email Us</h5>
                    <a href="mailto:info@snoutiq.com" className="text-gray-300 hover:text-white transition-colors duration-300 text-sm">
                      info@snoutiq.com
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Bottom Bar */}
        <div className="border-t border-gray-700 bg-black bg-opacity-20">
          <div className="container mx-auto px-6 py-6">
            <div className="flex flex-col md:flex-row items-center justify-between">
              <div className="flex items-center space-x-2 text-gray-400 mb-4 md:mb-0">
                <span>Made with</span>
                <FaHeart className="text-red-500 animate-pulse" />
                <span>for pet lovers worldwide</span>
              </div>
              <div className="text-gray-400 text-sm">
                © 2024 Snoutiq. All rights reserved.
              </div>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
}
