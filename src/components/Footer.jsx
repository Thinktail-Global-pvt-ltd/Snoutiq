// import {
//   FaFacebookSquare,
//   FaLinkedin,
//   FaPinterestSquare,
//   FaMobileAlt,
//   FaPaperPlane,
//   FaHeart,
// } from "react-icons/fa";
// import { FaSquareXTwitter, FaLocationDot, FaSquareInstagram} from "react-icons/fa6";
// import { Link } from "react-router-dom";
// import logo from "../assets/images/logo.webp";
// import React from "react";

// export default function Footer() {
//   return (
//     <footer className="relative bg-blue-50 text-gray-800 overflow-hidden w-full border-t border-blue-200">
//       {/* Background Pattern */}
//       <div className="absolute inset-0 opacity-10">
//         <div className="absolute top-0 left-0 w-64 h-64 bg-blue-100 rounded-full mix-blend-multiply filter blur-xl"></div>
//         <div className="absolute top-0 right-0 w-64 h-64 bg-blue-200 rounded-full mix-blend-multiply filter blur-xl"></div>
//         <div className="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-64 h-64 bg-blue-100 rounded-full mix-blend-multiply filter blur-xl"></div>
//       </div>

//       <div className="relative z-10">
//         {/* Main Footer Content */}
//         <div className="max-w-7xl mx-auto px-6 py-10">
//           <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
//             {/* Company Info */}
//             <div className="lg:col-span-2">
//               <div className="mb-6">
//                 <img
//                   src={logo}
//                   alt="SnoutIQ Logo"
//                   loading="lazy"
//                   className="h-5 cursor-pointer transition-transform hover:scale-105"
//                 />
//               </div>
//               <p className="text-gray-700 leading-relaxed mb-6 max-w-md text-sm">
//                 We're Tanul and Nisha—passionate pet parents to Sherif (our
//                 resilient rescue dog) and three incredible cats: Shadow, Tokyo,
//                 and Tiger. Each of our pets came into our lives through unique,
//                 sometimes challenging circumstances.
//               </p>

//               {/* Social Media */}
//               <div className="flex space-x-3 mb-8">
//                 {[
//                   {
//                     icon: <FaFacebookSquare className="text-lg" />,
//                     url: "https://www.facebook.com/people/Snoutiq/61578226867078/",
//                     label: "Facebook",
//                   },
//                   {
//                     icon: <FaSquareInstagram className="text-lg" />,
//                     url: "https://www.instagram.com/snoutiq_marketplace/#",
//                     label: "Instagram",
//                   },
//                   {
//                     icon: <FaSquareXTwitter className="text-lg" />,
//                     url: "https://twitter.com/snoutiq",
//                     label: "Twitter",
//                   },
//                   {
//                     icon: <FaLinkedin className="text-lg" />,
//                     url: "https://linkedin.com/company/snoutiq",
//                     label: "LinkedIn",
//                   },
//                   {
//                     icon: <FaPinterestSquare className="text-lg" />,
//                     url: "https://pinterest.com/snoutiq",
//                     label: "Pinterest",
//                   },
//                 ].map((social, index) => (
//                   <a
//                     key={index}
//                     href={social.url}
//                     target="_blank"
//                     rel="noopener noreferrer"
//                     aria-label={`SnoutIQ on ${social.label}`}
//                     className="w-10 h-10 bg-blue-500 hover:bg-blue-600 text-white rounded-lg flex items-center justify-center transition-colors duration-300 shadow-sm hover:shadow-md"
//                   >
//                     {social.icon}
//                   </a>
//                 ))}
//               </div>

//               {/* Newsletter Signup */}
//               <div className="bg-white rounded-xl p-5 border border-blue-200 shadow-sm">
//                 <h3 className="text-lg font-semibold mb-3 text-blue-800">
//                   Stay Updated
//                 </h3>
//                 <p className="text-blue-600 text-sm mb-4">
//                   Get pet care tips and exclusive offers
//                 </p>
//                 <form
//                   onSubmit={(e) => {
//                     e.preventDefault();
//                     alert("Subscribed! 🚀");
//                   }}
//                   className="flex"
//                 >
//                   <input
//                     type="email"
//                     aria-label="Email address"
//                     placeholder="Your email address"
//                     required
//                     className="flex-1 bg-blue-50 text-gray-800 px-4 py-2 rounded-l-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 border border-blue-200"
//                   />
//                   <button
//                     type="submit"
//                     className="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-r-lg text-sm font-medium text-white transition-colors duration-300 shadow-sm hover:shadow-md"
//                   >
//                     Subscribe
//                   </button>
//                 </form>
//               </div>
//             </div>

//             {/* Policies */}
//             <div>
//               <h4 className="text-lg font-semibold mb-6 text-blue-800 border-b border-blue-200 pb-2">
//                 Policies
//               </h4>
//               <div className="space-y-3">
//                 {[
//                   { name: "Privacy Policy", url: "/privacy-policy" },
//                   { name: "Terms of Service", url: "/terms-of-service" },
//                   { name: "Refund Policy", url: "/cancellation-policy" },
//                   { name: "Shipping Policy", url: "/shipping-policy" },
//                   { name: "Cookie Policy", url: "/cookie-policy" },
//                   { name: "Medical Disclaimer", url: "/medical-data-consent" },
//                   { name: "User Consent", url: "/user-consent" },
//                   { name: "Vendor Agreement", url: "/vendor-seller-agreement" },
//                 ].map((link, index) => (
//                   <Link
//                     key={index}
//                     to={link.url}
//                     className="block text-gray-700 hover:text-blue-600 transition-colors duration-300 text-sm hover:translate-x-1"
//                   >
//                     {link.name}
//                   </Link>
//                 ))}
//               </div>
//             </div>

//             {/* Contact Info */}
//             <div>
//               <h4 className="text-lg font-semibold mb-6 text-blue-800 border-b border-blue-200 pb-2">
//                 Contact
//               </h4>
//               <div className="space-y-4">
//                 <div className="flex items-start space-x-3">
//                   <FaLocationDot className="text-blue-500 mt-1 flex-shrink-0" />
//                   <p className="text-gray-700 text-sm leading-relaxed">
//                     Plot no 20, Block: H-1/A, Sector-63, Noida-201301
//                   </p>
//                 </div>

//                 <div className="flex items-center space-x-3">
//                   <FaMobileAlt className="text-blue-500 flex-shrink-0" />
//                   <a
//                     href="tel:+918588007466"
//                     className="text-gray-700 hover:text-blue-600 transition-colors duration-300 text-sm"
//                   >
//                     +91 85880 07466
//                   </a>
//                 </div>

//                 <div className="flex items-center space-x-3">
//                   <FaPaperPlane className="text-blue-500 flex-shrink-0" />
//                   <a
//                     href="mailto:info@snoutiq.com"
//                     className="text-gray-700 hover:text-blue-600 transition-colors duration-300 text-sm"
//                   >
//                     info@snoutiq.com
//                   </a>
//                 </div>

//                 {/* Business Hours */}
//                 <div className="pt-4 border-t border-blue-200">
//                   <p className="text-blue-600 text-xs font-medium mb-1">
//                     Business Hours
//                   </p>
//                   <p className="text-gray-700 text-sm">
//                     <time dateTime="Mo-Fr 09:00-18:00">Mon-Fri: 9AM-6PM</time>
//                   </p>
//                   <p className="text-gray-700 text-sm">
//                     <time dateTime="Sa 10:00-16:00">Sat: 10AM-4PM</time>
//                   </p>
//                   <p className="text-gray-700 text-sm">
//                     <time dateTime="Su Closed">Sun: Closed</time>
//                   </p>
//                 </div>
//               </div>
//             </div>
//           </div>
//         </div>

//         {/* Bottom Bar */}
//         <div className="border-t border-blue-200 bg-blue-100">
//           <div className="max-w-7xl mx-auto px-6 py-6">
//             <div className="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0">
//               <div className="flex items-center space-x-2 text-blue-700 text-sm">
//                 <span>© 2024 SnoutIQ. All rights reserved.</span>
//               </div>

//               <div className="flex items-center space-x-2 text-blue-700 text-sm">
//                 <span>Made with</span>
//                 <FaHeart className="text-red-400" aria-label="love" />
//                 <span>for pets</span>
//               </div>
//             </div>
//           </div>
//         </div>
//       </div>
//     </footer>
//   );
// }

import {
  FaFacebookSquare,
  FaLinkedin,
  FaPinterestSquare,
  FaMobileAlt,
  FaPaperPlane,
  FaHeart,
} from "react-icons/fa";
import { FaSquareXTwitter, FaLocationDot, FaSquareInstagram} from "react-icons/fa6";
import { Link } from "react-router-dom";
import logo from "../assets/images/logo.webp";
import React from "react";
import "../pages/DiwaliHome.css";

export default function Footer() {
  return (
    <footer className="diwali-footer relative bg-gradient-to-b from-orange-50 to-yellow-50 text-gray-800 overflow-hidden w-full border-t border-orange-200">
      {/* Diwali Background Elements */}
      <div className="diwali-footer-bg">
        <div className="rangoli-pattern"></div>
        <div className="floating-sparkles">
          {[...Array(15)].map((_, i) => (
            <div key={i} className={`sparkle s-${i % 3}`}>✨</div>
          ))}
        </div>
      </div>

      <div className="relative z-10">
        {/* Main Footer Content */}
        <div className="max-w-7xl mx-auto px-6 py-12">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
            {/* Company Info */}
            <div className="lg:col-span-2">
              <div className="mb-6 flex items-center space-x-3">
                <div className="logo-container relative">
                  <img
                    src={logo}
                    alt="SnoutIQ Logo"
                    loading="lazy"
                    className="h-5 cursor-pointer transition-all duration-300 hover:scale-110 relative z-10"
                  />
                  <div className="logo-glow"></div>
                </div>
                <span className="diwali-badge bg-gradient-to-r from-orange-500 to-yellow-500 text-white px-3 py-1 rounded-full text-xs font-semibold">
                  Diwali Special 🪔
                </span>
              </div>
              <p className="text-gray-700 leading-relaxed mb-6 max-w-md text-sm bg-white/50 rounded-lg p-4 border border-orange-100">
                We're Tanul and Nisha—passionate pet parents to Sherif (our
                resilient rescue dog) and three incredible cats: Shadow, Tokyo,
                and Tiger. Each of our pets came into our lives through unique,
                sometimes challenging circumstances.
              </p>

              {/* Social Media */}
              <div className="flex space-x-3 mb-8">
                {[
                  {
                    icon: <FaFacebookSquare className="text-lg" />,
                    url: "https://www.facebook.com/people/Snoutiq/61578226867078/",
                    label: "Facebook",
                  },
                  {
                    icon: <FaSquareInstagram className="text-lg" />,
                    url: "https://www.instagram.com/snoutiq_marketplace/#",
                    label: "Instagram",
                  },
                  {
                    icon: <FaSquareXTwitter className="text-lg" />,
                    url: "https://twitter.com/snoutiq",
                    label: "Twitter",
                  },
                  {
                    icon: <FaLinkedin className="text-lg" />,
                    url: "https://linkedin.com/company/snoutiq",
                    label: "LinkedIn",
                  },
                  {
                    icon: <FaPinterestSquare className="text-lg" />,
                    url: "https://pinterest.com/snoutiq",
                    label: "Pinterest",
                  },
                ].map((social, index) => (
                  <a
                    key={index}
                    href={social.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label={`SnoutIQ on ${social.label}`}
                    className="diwali-social-btn w-10 h-10 bg-gradient-to-r from-orange-500 to-yellow-500 hover:from-orange-600 hover:to-yellow-600 text-white rounded-lg flex items-center justify-center transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-110"
                  >
                    {social.icon}
                  </a>
                ))}
              </div>

              {/* Newsletter Signup */}
              <div className="diwali-newsletter bg-white/80 rounded-xl p-5 border-2 border-orange-200 shadow-lg backdrop-blur-sm">
                <h3 className="text-lg font-semibold mb-3 text-orange-800 flex items-center">
                  🎇 Stay Updated This Diwali
                </h3>
                <p className="text-orange-600 text-sm mb-4">
                  Get festive pet care tips and exclusive Diwali offers
                </p>
                <form
                  onSubmit={(e) => {
                    e.preventDefault();
                    alert("Subscribed for Diwali Specials! 🪔");
                  }}
                  className="flex"
                >
                  <input
                    type="email"
                    aria-label="Email address"
                    placeholder="Your email address"
                    required
                    className="flex-1 bg-orange-50 text-gray-800 px-4 py-3 rounded-l-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 border border-orange-200"
                  />
                  <button
                    type="submit"
                    className="diwali-subscribe-btn bg-gradient-to-r from-orange-500 to-yellow-500 hover:from-orange-600 hover:to-yellow-600 px-4 py-3 rounded-r-lg text-sm font-medium text-white transition-all duration-300 shadow-lg hover:shadow-xl"
                  >
                    Subscribe
                  </button>
                </form>
              </div>
            </div>

            {/* Policies */}
            <div>
              <h4 className="diwali-section-title text-lg font-semibold mb-6 text-orange-800 border-b border-orange-200 pb-2">
                Policies
              </h4>
              <div className="space-y-3">
                {[
                  { name: "Privacy Policy", url: "/privacy-policy" },
                  { name: "Terms of Service", url: "/terms-of-service" },
                  { name: "Refund Policy", url: "/cancellation-policy" },
                  { name: "Shipping Policy", url: "/shipping-policy" },
                  { name: "Cookie Policy", url: "/cookie-policy" },
                  { name: "Medical Disclaimer", url: "/medical-data-consent" },
                  { name: "User Consent", url: "/user-consent" },
                  { name: "Vendor Agreement", url: "/vendor-seller-agreement" },
                ].map((link, index) => (
                  <Link
                    key={index}
                    to={link.url}
                    className="diwali-footer-link block text-gray-700 hover:text-orange-600 transition-all duration-300 text-sm hover:translate-x-2 hover:font-medium"
                  >
                    🪔 {link.name}
                  </Link>
                ))}
              </div>
            </div>

            {/* Contact Info */}
            <div>
              <h4 className="diwali-section-title text-lg font-semibold mb-6 text-orange-800 border-b border-orange-200 pb-2">
                Contact
              </h4>
              <div className="space-y-4">
                <div className="flex items-start space-x-3">
                  <FaLocationDot className="text-orange-500 mt-1 flex-shrink-0 text-lg" />
                  <p className="text-gray-700 text-sm leading-relaxed bg-white/50 rounded-lg p-3">
                    Plot no 20, Block: H-1/A, Sector-63, Noida-201301
                  </p>
                </div>

                <div className="flex items-center space-x-3">
                  <FaMobileAlt className="text-orange-500 flex-shrink-0 text-lg" />
                  <a
                    href="tel:+918588007466"
                    className="diwali-contact-link text-gray-700 hover:text-orange-600 transition-all duration-300 text-sm font-medium"
                  >
                    +91 85880 07466
                  </a>
                </div>

                <div className="flex items-center space-x-3">
                  <FaPaperPlane className="text-orange-500 flex-shrink-0 text-lg" />
                  <a
                    href="mailto:info@snoutiq.com"
                    className="diwali-contact-link text-gray-700 hover:text-orange-600 transition-all duration-300 text-sm font-medium"
                  >
                    info@snoutiq.com
                  </a>
                </div>

                {/* Business Hours */}
                <div className="pt-4 border-t border-orange-200">
                  <p className="text-orange-600 text-xs font-medium mb-2 flex items-center">
                    🕯️ Business Hours
                  </p>
                  <p className="text-gray-700 text-sm bg-white/50 rounded p-2">
                    <time dateTime="Mo-Fr 09:00-18:00">Mon-Fri: 9AM-6PM</time>
                  </p>
                  <p className="text-gray-700 text-sm bg-white/50 rounded p-2 mt-1">
                    <time dateTime="Sa 10:00-16:00">Sat: 10AM-4PM</time>
                  </p>
                  <p className="text-gray-700 text-sm bg-white/50 rounded p-2 mt-1">
                    <time dateTime="Su Closed">Sun: Closed</time>
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Bottom Bar */}
        <div className="border-t border-orange-200 bg-gradient-to-r from-orange-100 to-yellow-100 relative overflow-hidden">
          <div className="diya-border"></div>
          <div className="max-w-7xl mx-auto px-6 py-6">
            <div className="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0">
              <div className="flex items-center space-x-2 text-orange-700 text-sm font-medium">
                <span>© 2024 SnoutIQ. All rights reserved.</span>
              </div>

              <div className="flex items-center space-x-2 text-orange-700 text-sm font-medium">
                <span>Made with</span>
                <FaHeart className="text-red-500 animate-pulse" aria-label="love" />
                <span>for pets this Diwali</span>
                <div className="festive-emoji">🪔</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
}