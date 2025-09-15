// // import { FaFacebookSquare, FaLinkedin, FaPinterestSquare, FaMobileAlt, FaPaperPlane, FaHeart } from "react-icons/fa";
// // import { FaSquareXTwitter } from "react-icons/fa6";
// // import { FaLocationDot } from "react-icons/fa6";
// // import { Link } from "react-router-dom";

// // export default function Footer() {
// //   return (
// //     <footer className="relative bg-gradient-to-br from-gray-900 via-blue-900 to-gray-800 text-white overflow-hidden w-full">
// //       {/* Background Pattern */}
// //       <div className="absolute inset-0 opacity-10">
// //         <div className="absolute top-0 left-0 w-72 h-72 bg-blue-400 rounded-full mix-blend-multiply filter blur-xl animate-pulse"></div>
// //         <div className="absolute top-0 right-0 w-72 h-72 bg-purple-400 rounded-full mix-blend-multiply filter blur-xl animate-pulse animation-delay-2000"></div>
// //         <div className="absolute bottom-0 left-1/2 w-72 h-72 bg-pink-400 rounded-full mix-blend-multiply filter blur-xl animate-pulse animation-delay-4000"></div>
// //       </div>

// //       <div className="relative z-10">
// //         {/* Main Footer Content */}
// //         <div className="container mx-auto px-6 py-16">
// //           <div className="grid grid-cols-1 lg:grid-cols-4 gap-12">

// //             {/* Company Info - Enhanced */}
// //             <div className="lg:col-span-2">
// //               <div className="mb-6">
// //                 <img
// //                   src="https://snoutiq.com/wp-content/uploads/2025/06/dark-bg.png"
// //                   alt="Snoutiq" 
// //                   className="w-48 bg-white p-3 rounded-xl shadow-2xl transform hover:scale-105 transition-transform duration-300"
// //                 />
// //               </div>
// //               <p className="text-lg leading-relaxed mb-6 text-gray-200 max-w-lg">
// //                 We're Tanul and Nisha—passionate pet parents to Sherif (our resilient rescue dog) and three incredible cats: Shadow, Tokyo, and Tiger. Each of our pets came into our lives through unique, sometimes challenging circumstances.
// //               </p>

// //               {/* Enhanced Social Media */}
// //               <div className="flex space-x-4 mb-8">
// //                 <a href="#" className="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white hover:from-blue-600 hover:to-blue-700 transform hover:scale-110 hover:shadow-lg transition-all duration-300">
// //                   <FaFacebookSquare className="text-xl" />
// //                 </a>
// //                 <a href="#" className="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl flex items-center justify-center text-white hover:from-purple-600 hover:to-purple-700 transform hover:scale-110 hover:shadow-lg transition-all duration-300">
// //                   <FaSquareXTwitter className="text-xl" />
// //                 </a>
// //                 <a href="#" className="w-12 h-12 bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl flex items-center justify-center text-white hover:from-blue-700 hover:to-blue-800 transform hover:scale-110 hover:shadow-lg transition-all duration-300">
// //                   <FaLinkedin className="text-xl" />
// //                 </a>
// //                 <a href="#" className="w-12 h-12 bg-gradient-to-r from-red-500 to-red-600 rounded-xl flex items-center justify-center text-white hover:from-red-600 hover:to-red-700 transform hover:scale-110 hover:shadow-lg transition-all duration-300">
// //                   <FaPinterestSquare className="text-xl" />
// //                 </a>
// //               </div>

// //               {/* Call to Action */}
// //               <div className="bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl p-6 text-center">
// //                 <h3 className="text-xl font-bold mb-2">Ready to give your pet the best care?</h3>
// //                 <p className="text-blue-100 mb-4">Join thousands of happy pet parents using Snoutiq AI</p>
// //                 <Link to={'/dashboard'} className="bg-white text-blue-600 px-8 py-3 rounded-xl font-semibold hover:bg-gray-100 transform hover:scale-105 transition-all duration-300 shadow-lg">
// //                   Get Started Now
// //                 </Link>
// //               </div>
// //             </div>

// //             {/* Important Links - Redesigned */}
// //             <div>
// //               <h4 className="text-2xl font-bold mb-6 bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">
// //                 Important Links
// //               </h4>
// //               <div className="space-y-3">
// //                 {[
// //                   { name: "Terms & Conditions", url: "https://snoutiq.com/terms-conditions/" },
// //                   { name: "Privacy Policy", url: "https://snoutiq.com/privacy-policy/" },
// //                   { name: "Cancellation & Refund", url: "https://snoutiq.com/cancellation-refund-policy/" },
// //                   { name: "Shipping & Delivery", url: "https://snoutiq.com/shipping-delivery-policy/" },
// //                   { name: "Medical Disclaimer", url: "https://snoutiq.com/medical-disclaimer/" },
// //                   { name: "User Consent", url: "https://snoutiq.com/user-consent/" },
// //                   { name: "Vendor Agreement", url: "https://snoutiq.com/vendor-seller-agreement/" },
// //                   { name: "Cookie Policy", url: "https://snoutiq.com/cookie-policy/" }
// //                 ].map((link, index) => (
// //                   <a 
// //                     key={index}
// //                     href={link.url} 
// //                     className="block text-gray-300 hover:text-white transform hover:translate-x-2 transition-all duration-300 text-sm border-l-2 border-transparent hover:border-blue-400 pl-3 py-1"
// //                   >
// //                     {link.name}
// //                   </a>
// //                 ))}
// //               </div>
// //             </div>

// //             {/* Contact Info - Enhanced */}
// //             <div>
// //               <h4 className="text-2xl font-bold mb-6 bg-gradient-to-r from-green-400 to-blue-400 bg-clip-text text-transparent">
// //                 Get In Touch
// //               </h4>
// //               <div className="space-y-6">
// //                 <div className="flex items-start space-x-4 group">
// //                   <div className="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
// //                     <FaLocationDot className="text-white text-lg" />
// //                   </div>
// //                   <div>
// //                     <h5 className="font-semibold text-blue-300 mb-1">Our Location</h5>
// //                     <p className="text-gray-300 text-sm leading-relaxed">Plot no 20, Block: H-1/A, Sector-63, Noida-201301</p>
// //                   </div>
// //                 </div>

// //                 <div className="flex items-start space-x-4 group">
// //                   <div className="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
// //                     <FaMobileAlt className="text-white text-lg" />
// //                   </div>
// //                   <div>
// //                     <h5 className="font-semibold text-green-300 mb-1">Call Us</h5>
// //                     <a href="tel:+91-8588007466" className="text-gray-300 hover:text-white transition-colors duration-300 text-sm">
// //                       +91 85880 07466
// //                     </a>
// //                   </div>
// //                 </div>

// //                 <div className="flex items-start space-x-4 group">
// //                   <div className="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
// //                     <FaPaperPlane className="text-white text-lg" />
// //                   </div>
// //                   <div>
// //                     <h5 className="font-semibold text-purple-300 mb-1">Email Us</h5>
// //                     <a href="mailto:info@snoutiq.com" className="text-gray-300 hover:text-white transition-colors duration-300 text-sm">
// //                       info@snoutiq.com
// //                     </a>
// //                   </div>
// //                 </div>
// //               </div>
// //             </div>
// //           </div>
// //         </div>

// //         {/* Bottom Bar */}
// //         <div className="border-t border-gray-700 bg-black bg-opacity-20">
// //           <div className="container mx-auto px-6 py-6">
// //             <div className="flex flex-col md:flex-row items-center justify-between">
// //               <div className="flex items-center space-x-2 text-gray-400 mb-4 md:mb-0">
// //                 <span>Made with</span>
// //                 <FaHeart className="text-red-500 animate-pulse" />
// //                 <span>for pet lovers worldwide</span>
// //               </div>
// //               <div className="text-gray-400 text-sm">
// //                 © 2024 Snoutiq. All rights reserved.
// //               </div>
// //             </div>
// //           </div>
// //         </div>
// //       </div>
// //     </footer>
// //   );
// // }

// import { FaFacebookSquare, FaLinkedin, FaPinterestSquare, FaMobileAlt, FaPaperPlane, FaHeart } from "react-icons/fa";
// import { FaSquareXTwitter } from "react-icons/fa6";
// import { FaLocationDot } from "react-icons/fa6";
// import { Link } from "react-router-dom";
// import logo from '../assets/images/logo.webp';

// export default function Footer() {
//   return (
//     <footer className="relative bg-gray-900 text-white overflow-hidden w-full">
//       {/* Background Pattern */}
//       <div className="absolute inset-0 opacity-5">
//         <div className="absolute top-0 left-0 w-64 h-64 bg-blue-500 rounded-full mix-blend-multiply filter blur-xl"></div>
//         <div className="absolute top-0 right-0 w-64 h-64 bg-purple-500 rounded-full mix-blend-multiply filter blur-xl"></div>
//         <div className="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-64 h-64 bg-indigo-500 rounded-full mix-blend-multiply filter blur-xl"></div>
//       </div>

//       <div className="relative z-10">
//         {/* Main Footer Content */}
//         <div className="max-w-7xl mx-auto px-6 py-16">
//           <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">

//             {/* Company Info */}
//             <div className="lg:col-span-2">
//               <div className="mb-6">
//                 <img
//                   src={logo}
//                   alt="Snoutiq"
//                   className="w-40 bg-white p-3 rounded-lg shadow-md"
//                 />
//               </div>
//               <p className="text-gray-300 leading-relaxed mb-6 max-w-md text-sm">
//                 We're Tanul and Nisha—passionate pet parents to Sherif (our resilient rescue dog) and three incredible cats:
//                 Shadow, Tokyo, and Tiger. Each of our pets came into our lives through unique, sometimes challenging circumstances.
//               </p>

//               {/* Social Media */}
//               <div className="flex space-x-3 mb-8">
//                 {[
//                   { icon: <FaFacebookSquare className="text-lg" />, color: "bg-blue-600 hover:bg-blue-700" },
//                   { icon: <FaSquareXTwitter className="text-lg" />, color: "bg-gray-800 hover:bg-gray-900" },
//                   { icon: <FaLinkedin className="text-lg" />, color: "bg-blue-700 hover:bg-blue-800" },
//                   { icon: <FaPinterestSquare className="text-lg" />, color: "bg-red-600 hover:bg-red-700" }
//                 ].map((social, index) => (
//                   <a
//                     key={index}
//                     href="#"
//                     className={`w-10 h-10 ${social.color} rounded-lg flex items-center justify-center text-white transition-colors duration-300`}
//                   >
//                     {social.icon}
//                   </a>
//                 ))}
//               </div>

//               {/* Newsletter Signup */}
//               <div className="bg-gray-800 rounded-xl p-5">
//                 <h3 className="text-lg font-semibold mb-3">Stay Updated</h3>
//                 <p className="text-gray-400 text-sm mb-4">Get pet care tips and exclusive offers</p>
//                 <div className="flex">
//                   <input
//                     type="email"
//                     placeholder="Your email address"
//                     className="flex-1 bg-gray-700 text-white px-4 py-2 rounded-l-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
//                   />
//                   <button className="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-r-lg text-sm font-medium transition-colors duration-300">
//                     Subscribe
//                   </button>
//                 </div>
//               </div>
//             </div>

//             {/* Quick Links */}
//             {/* <div>
//               <h4 className="text-lg font-semibold mb-6 text-white border-b border-gray-700 pb-2">Company</h4>
//               <div className="space-y-3">
//                 {[
//                   { name: "About Us", url: "/about" },
//                   { name: "Our Team", url: "/team" },
//                   { name: "Careers", url: "/careers" },
//                   { name: "Press", url: "/press" },
//                   { name: "Blog", url: "/blog" },
//                   { name: "Contact", url: "/contact" }
//                 ].map((link, index) => (
//                   <a 
//                     key={index}
//                     href={link.url} 
//                     className="block text-gray-400 hover:text-white transition-colors duration-300 text-sm"
//                   >
//                     {link.name}
//                   </a>
//                 ))}
//               </div>
//             </div> */}

//             {/* Policies */}
//             <div>
//               <h4 className="text-lg font-semibold mb-6 text-white border-b border-gray-700 pb-2">Policies</h4>
//               <div className="space-y-3">
//                 {[

//                   { name: "Privacy Policy", url: "/privacy-policy'" },
//                   { name: "Terms of Service", url: "/terms-of-service" },
//                   { name: "Refund Policy", url: "/cancellation-policy" },
//                   { name: "Shipping Policy", url: "/shipping-policy" },
//                   { name: "Cookie Policy", url: "/cookie-policy" },
//                   { name: "Medical Disclaimer", url: "/medical-data-consent" },
//                   { name: "User Consent", url: "https://snoutiq.com/user-consent/" },
//                   { name: "Vendor Agreement", url: "https://snoutiq.com/vendor-seller-agreement/" },
//                 ].map((link, index) => (
//                   <a
//                     key={index}
//                     href={link.url}
//                     className="block text-gray-400 hover:text-white transition-colors duration-300 text-sm"
//                   >
//                     {link.name}
//                   </a>
//                 ))}
//               </div>
//             </div>

//             {/* Contact Info */}
//             <div>
//               <h4 className="text-lg font-semibold mb-6 text-white border-b border-gray-700 pb-2">Contact</h4>
//               <div className="space-y-4">
//                 <div className="flex items-start space-x-3">
//                   <FaLocationDot className="text-blue-400 mt-1 flex-shrink-0" />
//                   <div>
//                     <p className="text-gray-300 text-sm leading-relaxed">
//                       Plot no 20, Block: H-1/A, Sector-63, Noida-201301
//                     </p>
//                   </div>
//                 </div>

//                 <div className="flex items-center space-x-3">
//                   <FaMobileAlt className="text-green-400 flex-shrink-0" />
//                   <a href="tel:+91-8588007466" className="text-gray-300 hover:text-white transition-colors duration-300 text-sm">
//                     +91 85880 07466
//                   </a>
//                 </div>

//                 <div className="flex items-center space-x-3">
//                   <FaPaperPlane className="text-purple-400 flex-shrink-0" />
//                   <a href="mailto:info@snoutiq.com" className="text-gray-300 hover:text-white transition-colors duration-300 text-sm">
//                     info@snoutiq.com
//                   </a>
//                 </div>

//                 {/* Business Hours */}
//                 <div className="pt-4 border-t border-gray-800">
//                   <p className="text-gray-400 text-xs font-medium mb-1">Business Hours</p>
//                   <p className="text-gray-300 text-sm">Mon-Fri: 9AM-6PM</p>
//                   <p className="text-gray-300 text-sm">Sat: 10AM-4PM</p>
//                   <p className="text-gray-300 text-sm">Sun: Closed</p>
//                 </div>
//               </div>
//             </div>
//           </div>
//         </div>

//         {/* Bottom Bar */}
//         <div className="border-t border-gray-800 bg-gray-950">
//           <div className="max-w-7xl mx-auto px-6 py-6">
//             <div className="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0">
//               <div className="flex items-center space-x-2 text-gray-500 text-sm">
//                 <span>© 2024 Snoutiq. All rights reserved.</span>
//               </div>

//               {/* <div className="flex items-center space-x-6 text-gray-500 text-sm">
//                 <a href="#" className="hover:text-gray-300 transition-colors duration-300">Accessibility</a>
//                 <a href="#" className="hover:text-gray-300 transition-colors duration-300">Sitemap</a>
//                 <a href="#" className="hover:text-gray-300 transition-colors duration-300">Do Not Sell My Info</a>
//               </div> */}

//               <div className="flex items-center space-x-2 text-gray-500 text-sm">
//                 <span>Made with</span>
//                 <FaHeart className="text-red-500" />
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
import { FaSquareXTwitter, FaLocationDot } from "react-icons/fa6";
import { Link } from "react-router-dom";
import logo from "../assets/images/logo.webp";

export default function Footer() {
  return (
    <footer className="relative bg-blue-50 text-gray-800 overflow-hidden w-full border-t border-blue-200">
      {/* Background Pattern */}
      <div className="absolute inset-0 opacity-10">
        <div className="absolute top-0 left-0 w-64 h-64 bg-blue-100 rounded-full mix-blend-multiply filter blur-xl"></div>
        <div className="absolute top-0 right-0 w-64 h-64 bg-blue-200 rounded-full mix-blend-multiply filter blur-xl"></div>
        <div className="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-64 h-64 bg-blue-100 rounded-full mix-blend-multiply filter blur-xl"></div>
      </div>

      <div className="relative z-10">
        {/* Main Footer Content */}
        <div className="max-w-7xl mx-auto px-6 py-10">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
            {/* Company Info */}
            <div className="lg:col-span-2">
              <div className="mb-6">
                <img
                  src={logo}
                  alt="SnoutIQ Logo"
                  loading="lazy"
                  width="160"
                  height="60"
                  className="w-40 p-3 rounded-lg"
                />
              </div>
              <p className="text-gray-700 leading-relaxed mb-6 max-w-md text-sm">
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
                    url: "https://facebook.com/snoutiq",
                    label: "Facebook",
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
                    className="w-10 h-10 bg-blue-500 hover:bg-blue-600 text-white rounded-lg flex items-center justify-center transition-colors duration-300 shadow-sm hover:shadow-md"
                  >
                    {social.icon}
                  </a>
                ))}
              </div>

              {/* Newsletter Signup */}
              <div className="bg-white rounded-xl p-5 border border-blue-200 shadow-sm">
                <h3 className="text-lg font-semibold mb-3 text-blue-800">
                  Stay Updated
                </h3>
                <p className="text-blue-600 text-sm mb-4">
                  Get pet care tips and exclusive offers
                </p>
                <form
                  onSubmit={(e) => {
                    e.preventDefault();
                    alert("Subscribed! 🚀");
                  }}
                  className="flex"
                >
                  <input
                    type="email"
                    aria-label="Email address"
                    placeholder="Your email address"
                    required
                    className="flex-1 bg-blue-50 text-gray-800 px-4 py-2 rounded-l-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 border border-blue-200"
                  />
                  <button
                    type="submit"
                    className="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-r-lg text-sm font-medium text-white transition-colors duration-300 shadow-sm hover:shadow-md"
                  >
                    Subscribe
                  </button>
                </form>
              </div>
            </div>

            {/* Policies */}
            <div>
              <h4 className="text-lg font-semibold mb-6 text-blue-800 border-b border-blue-200 pb-2">
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
                    className="block text-gray-700 hover:text-blue-600 transition-colors duration-300 text-sm hover:translate-x-1"
                  >
                    {link.name}
                  </Link>
                ))}
              </div>
            </div>

            {/* Contact Info */}
            <div>
              <h4 className="text-lg font-semibold mb-6 text-blue-800 border-b border-blue-200 pb-2">
                Contact
              </h4>
              <div className="space-y-4">
                <div className="flex items-start space-x-3">
                  <FaLocationDot className="text-blue-500 mt-1 flex-shrink-0" />
                  <p className="text-gray-700 text-sm leading-relaxed">
                    Plot no 20, Block: H-1/A, Sector-63, Noida-201301
                  </p>
                </div>

                <div className="flex items-center space-x-3">
                  <FaMobileAlt className="text-blue-500 flex-shrink-0" />
                  <a
                    href="tel:+918588007466"
                    className="text-gray-700 hover:text-blue-600 transition-colors duration-300 text-sm"
                  >
                    +91 85880 07466
                  </a>
                </div>

                <div className="flex items-center space-x-3">
                  <FaPaperPlane className="text-blue-500 flex-shrink-0" />
                  <a
                    href="mailto:info@snoutiq.com"
                    className="text-gray-700 hover:text-blue-600 transition-colors duration-300 text-sm"
                  >
                    info@snoutiq.com
                  </a>
                </div>

                {/* Business Hours */}
                <div className="pt-4 border-t border-blue-200">
                  <p className="text-blue-600 text-xs font-medium mb-1">
                    Business Hours
                  </p>
                  <p className="text-gray-700 text-sm">
                    <time dateTime="Mo-Fr 09:00-18:00">Mon-Fri: 9AM-6PM</time>
                  </p>
                  <p className="text-gray-700 text-sm">
                    <time dateTime="Sa 10:00-16:00">Sat: 10AM-4PM</time>
                  </p>
                  <p className="text-gray-700 text-sm">
                    <time dateTime="Su Closed">Sun: Closed</time>
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Bottom Bar */}
        <div className="border-t border-blue-200 bg-blue-100">
          <div className="max-w-7xl mx-auto px-6 py-6">
            <div className="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0">
              <div className="flex items-center space-x-2 text-blue-700 text-sm">
                <span>© 2024 SnoutIQ. All rights reserved.</span>
              </div>

              <div className="flex items-center space-x-2 text-blue-700 text-sm">
                <span>Made with</span>
                <FaHeart className="text-red-400" aria-label="love" />
                <span>for pets</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
}
