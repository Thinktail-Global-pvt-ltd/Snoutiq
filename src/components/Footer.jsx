import {
  FaFacebookSquare,
  FaLinkedin,
  FaPinterestSquare,
  FaMobileAlt,
  FaPaperPlane,
  FaHeart,
} from "react-icons/fa";
import {
  FaSquareXTwitter,
  FaLocationDot,
  FaSquareInstagram,
} from "react-icons/fa6";
import { Link } from "react-router-dom";
import logo from "../assets/images/logo.webp";
import React from "react";

const policyLinks = [
  { name: "Privacy Policy", url: "/privacy-policy" },
  { name: "Terms of Service", url: "/terms-of-service" },
  { name: "Refund Policy", url: "/cancellation-policy" },
  { name: "Shipping Policy", url: "/shipping-policy" },
  { name: "Cookie Policy", url: "/cookie-policy" },
  { name: "Medical Disclaimer", url: "/medical-data-consent" },
  { name: "User Consent", url: "/user-consent" },
  { name: "Vendor Agreement", url: "/vendor-seller-agreement" },
];

const socialLinks = [
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
];

export default function Footer() {
  return (
    <footer
      className="relative bg-blue-50 text-gray-800 overflow-hidden w-full border-t border-blue-200"
      aria-labelledby="site-footer-heading"
    >
      <div className="absolute inset-0 opacity-10 pointer-events-none" aria-hidden="true">
        <div className="absolute top-0 left-0 w-64 h-64 bg-blue-100 rounded-full mix-blend-multiply filter blur-xl"></div>
        <div className="absolute top-0 right-0 w-64 h-64 bg-blue-200 rounded-full mix-blend-multiply filter blur-xl"></div>
        <div className="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-64 h-64 bg-blue-100 rounded-full mix-blend-multiply filter blur-xl"></div>
      </div>

      <div className="relative z-10">
        <div className="max-w-7xl mx-auto px-6 py-10">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
            <div className="lg:col-span-2">
              <div className="mb-6">
                <img
                  src={logo}
                  alt="SnoutIQ logo"
                  loading="lazy"
                  className="h-6 w-auto"
                />
              </div>
              <h2 id="site-footer-heading" className="sr-only">
                SnoutIQ footer
              </h2>
              <p className="text-gray-700 leading-relaxed mb-6 max-w-md text-sm">
                Built by pet parents and clinicians, SnoutIQ delivers expert guidance, immediate access to care, and peace of mind for every family. We exist so no pet waits for answers.
              </p>

              <div className="flex flex-wrap gap-3 mb-8" aria-label="Social media">
                {socialLinks.map((social) => (
                  <a
                    key={social.label}
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

              <div className="bg-white rounded-xl p-5 border border-blue-200 shadow-sm max-w-md">
                <h3 className="text-lg font-semibold mb-3 text-blue-800">
                  Stay updated
                </h3>
                <p className="text-blue-600 text-sm mb-4">
                  Weekly product releases and pet health insights straight to your inbox.
                </p>
                <form
                  onSubmit={(e) => {
                    e.preventDefault();
                    alert("Thanks for subscribing to SnoutIQ!");
                  }}
                  className="flex"
                >
                  <input
                    type="email"
                    aria-label="Email address"
                    placeholder="you@clinic.com"
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

            <div>
              <h3 className="text-lg font-semibold mb-4 text-blue-800 border-b border-blue-200 pb-2">
                Policies
              </h3>
              <nav aria-label="Legal"
                className="space-y-3"
              >
                {policyLinks.map((link) => (
                  <Link
                    key={link.name}
                    to={link.url}
                    className="block text-gray-700 hover:text-blue-600 transition-colors duration-300 text-sm hover:translate-x-1"
                  >
                    {link.name}
                  </Link>
                ))}
              </nav>
            </div>

            <div>
              <h3 className="text-lg font-semibold mb-4 text-blue-800 border-b border-blue-200 pb-2">
                Contact
              </h3>
              <ul className="space-y-4 text-sm">
                <li className="flex items-start space-x-3">
                  <FaLocationDot className="text-blue-500 mt-1 flex-shrink-0" />
                  <p className="text-gray-700 leading-relaxed">
                    Plot no 20, Block H-1/A, Sector-63, Noida-201301
                  </p>
                </li>
                <li className="flex items-center space-x-3">
                  <FaMobileAlt className="text-blue-500 flex-shrink-0" />
                  <a
                    href="tel:+918588007466"
                    className="text-gray-700 hover:text-blue-600 transition-colors duration-300"
                  >
                    +91 85880 07466
                  </a>
                </li>
                <li className="flex items-center space-x-3">
                  <FaPaperPlane className="text-blue-500 flex-shrink-0" />
                  <a
                    href="mailto:info@snoutiq.com"
                    className="text-gray-700 hover:text-blue-600 transition-colors duration-300"
                  >
                    info@snoutiq.com
                  </a>
                </li>
                <li className="pt-4 border-t border-blue-200">
                  <p className="text-blue-600 text-xs font-medium mb-1">Business hours</p>
                  <p className="text-gray-700">
                    <time dateTime="Mo-Fr 09:00-18:00">Mon-Fri: 9am – 6pm IST</time>
                  </p>
                  <p className="text-gray-700">
                    <time dateTime="Sa 10:00-16:00">Sat: 10am – 4pm</time>
                  </p>
                  <p className="text-gray-700">
                    <time dateTime="Su Closed">Sun: Closed</time>
                  </p>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <div className="border-t border-blue-200 bg-blue-100">
          <div className="max-w-7xl mx-auto px-6 py-5">
            <div className="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0">
              <p className="text-blue-700 text-sm">
                &copy; {new Date().getFullYear()} SnoutIQ. All rights reserved.
              </p>

              <div className="flex items-center space-x-2 text-blue-700 text-sm">
                <span>Made with</span>
                <FaHeart className="text-red-400" aria-label="love" />
                <span>for every pet + parent</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
}
