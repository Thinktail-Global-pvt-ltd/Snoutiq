import React from 'react';

const FeatureCard = ({ icon, title, description }) => {
  return (
    <div className="bg-white rounded-xl p-3 sm:p-4 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
      <div className="flex items-start space-x-3 sm:space-x-4">
        {/* Icon on the left */}
        <div className="w-10 h-10 sm:w-12 sm:h-12 bg-[#2761E8] rounded-lg flex items-center justify-center flex-shrink-0">
          {icon}
        </div>
        
        {/* Content on the right */}
        <div className="flex-1">
          <h3 className="text-base sm:text-lg font-semibold text-gray-800 mb-1 sm:mb-2">
            {title}
          </h3>
          <p className="text-gray-600 text-xs sm:text-sm leading-relaxed">
            {description}
          </p>
        </div>
      </div>
    </div>
  );
};

export default FeatureCard; 