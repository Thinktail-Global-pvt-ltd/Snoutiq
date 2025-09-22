// LoadingModal.js
import React from "react";
import { Dialog } from "@headlessui/react";

const LoadingModal = ({ open, onClose }) => {
  if (!open) return null;

  return (
    <Dialog open={open} onClose={onClose} className="fixed inset-0 z-50">
      {/* Background Overlay */}
      <div className="fixed inset-0 bg-black/40 backdrop-blur-sm" aria-hidden="true" />

      {/* Modal Content */}
      <div className="fixed inset-0 flex items-center justify-center p-4">
        <div className="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-sm flex flex-col items-center space-y-6 animate-fadeIn">
          
          {/* Doctor Avatar (Placeholder) */}
          <div className="w-20 h-20 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg animate-pulse">
            <svg className="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
              />
            </svg>
          </div>

          {/* Title */}
          <h3 className="text-lg font-semibold text-gray-800 text-center">
            Connecting you with a Doctor…
          </h3>

          {/* Subtitle */}
          <p className="text-gray-500 text-sm text-center">
            Please wait while we notify available doctors.
          </p>

          {/* Loader with dots (Rapido-style) */}
          <div className="flex space-x-2 mt-2">
            <span className="w-3 h-3 bg-blue-500 rounded-full animate-bounce"></span>
            <span className="w-3 h-3 bg-blue-500 rounded-full animate-bounce delay-150"></span>
            <span className="w-3 h-3 bg-blue-500 rounded-full animate-bounce delay-300"></span>
          </div>

          {/* Cancel Button */}
          <button
            onClick={onClose}
            className="mt-6 w-full bg-gradient-to-r from-red-500 to-red-600 text-white font-medium py-2 rounded-xl hover:from-red-600 hover:to-red-700 transition-all"
          >
            Cancel Request
          </button>
        </div>
      </div>
    </Dialog>
  );
};

export default LoadingModal;
