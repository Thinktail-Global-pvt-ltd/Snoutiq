import React from 'react';

const GroomerList = ({ data, handleItemClick }) => {
  return (
    <div className="bg-white rounded-xl p-6 shadow-sm sm:max-w-full">
      <h3 className="text-lg font-semibold text-gray-800 mb-4">Available Groomers</h3>
      <div className="flex overflow-x-auto space-x-4 pb-4 scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
        {data.groomers.map((dd) => (
          <div
            key={dd.place_id}
            className="flex-none w-48 text-center bg-gray-50 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow"
          >
            <div className="w-16 h-16 rounded-xl mx-auto mb-3 overflow-hidden">
              <img
                src={dd.icon}
                alt={`${dd.name} icon`}
                className="w-full h-full object-cover"
              />
            </div>
            <h4 className="font-semibold text-sm text-gray-800 mb-1 truncate">
              {dd.name}
            </h4>
            <p className="text-xs text-gray-600 mb-3 line-clamp-2">
              {dd.vicinity}
            </p>
            <a
              className="inline-block bg-blue-600 text-white text-xs px-3 py-2 rounded-lg hover:bg-blue-700 font-medium transition-colors"
              onClick={handleItemClick}
              href={`https://www.google.com/maps?q=${dd.geometry.location.lat},${dd.geometry.location.lng}`}
              target="_blank"
              rel="noopener noreferrer"
            >
              Directions
            </a>
            {dd.business_status === "CLOSED_TEMPORARILY" && (
              <p className="text-xs text-red-500 mt-2">Temporarily Closed</p>
            )}
          </div>
        ))}
      </div>
    </div>
  );
};

export default GroomerList;