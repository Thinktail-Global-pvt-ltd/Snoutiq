import React from "react";
import { HiMail, HiPhone, HiLocationMarker } from "react-icons/hi";
import { FaFacebookF, FaTwitter, FaLinkedinIn } from "react-icons/fa";

const Support = () => {
  return (
    <div className="p-6 max-w-4xl mx-auto">
      <h1 className="text-3xl font-extrabold mb-8 text-gray-800">
        Support & Contact
      </h1>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        {/* Contact Details Card */}
        <div className="bg-gradient-to-r from-blue-50 to-indigo-50 shadow-lg rounded-xl p-6 space-y-6 border-l-4 border-blue-500">
          <h2 className="text-xl font-semibold text-gray-700">Get in Touch</h2>
          <p className="text-gray-600">
            We are here to help you. Contact us through any of the channels below.
          </p>

          {/* Address */}
          {/* <div className="flex items-start gap-4">
            <HiLocationMarker className="h-6 w-6 text-blue-600 mt-1" />
            <div>
              <h3 className="font-medium text-gray-700">Address</h3>
              <p className="text-gray-600 text-sm">
                337, 3rd Floor, Udyog Vihar, Phase 2, Gurgaon, Haryana, 122016
              </p>
            </div>
          </div> */}

          {/* Phone */}
          <div className="flex items-start gap-4">
            <HiPhone className="h-6 w-6 text-green-600 mt-1" />
            <div>
              <h3 className="font-medium text-gray-700">Phone</h3>
              <p className="text-gray-600 text-sm">+91 85880 07466</p>
            </div>
          </div>

          {/* Email */}
          <div className="flex items-start gap-4">
            <HiMail className="h-6 w-6 text-red-600 mt-1" />
            <div>
              <h3 className="font-medium text-gray-700">Email</h3>
              <p className="text-gray-600 text-sm">support@snoutiq.com</p>
            </div>
          </div>

          {/* Working Hours */}
          <div className="mt-4">
            <h3 className="font-medium text-gray-700">Working Hours</h3>
            <p className="text-gray-600 text-sm">
              Monday - Friday: 9:00 AM - 6:00 PM <br />
              Saturday: 10:00 AM - 3:00 PM <br />
              Sunday: Closed
            </p>
          </div>

          {/* Social Media */}
          <div className="mt-4 flex gap-4">
            <a href="#" className="text-blue-600 hover:text-blue-800">
              <FaFacebookF />
            </a>
            <a href="#" className="text-blue-400 hover:text-blue-600">
              <FaTwitter />
            </a>
            <a href="#" className="text-blue-700 hover:text-blue-900">
              <FaLinkedinIn />
            </a>
          </div>
        </div>

        {/* FAQ / Support Info Card */}
        <div className="bg-white shadow-lg rounded-xl p-6 space-y-6 border-l-4 border-indigo-500">
          <h2 className="text-xl font-semibold text-gray-700">Need Help?</h2>
          <p className="text-gray-600">
            If you have any questions or face any issues while using Snoutiq, our support team is here to assist you.
          </p>

          <div className="space-y-4">
            <div>
              <h3 className="font-medium text-gray-700">How to book an appointment?</h3>
              <p className="text-gray-600 text-sm">
                Navigate to the "Appointments" section and select your preferred veterinarian and time slot.
              </p>
            </div>
            <div>
              <h3 className="font-medium text-gray-700">How to manage pets?</h3>
              <p className="text-gray-600 text-sm">
                Go to "My Pets" to add, edit, or remove your pets. You can also track their health records.
              </p>
            </div>
            <div>
              <h3 className="font-medium text-gray-700">Technical Support</h3>
              <p className="text-gray-600 text-sm">
                For technical issues, email us at <span className="text-blue-600">support@snoutiq.com</span>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Support;
