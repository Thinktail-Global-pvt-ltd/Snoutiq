import React from 'react';

const VetList = ({ data, handleItemClick }) => {
  return (
    <div className="bg-white rounded-lg p-4 shadow-sm max-w-full">
      <h3 className="text-base font-semibold text-gray-800 mb-3">
        Available Veterinarians
      </h3>
      <div className="flex overflow-x-auto space-x-3 pb-3 scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
        {data.vets.map((dd) => (
          <div
            key={dd.place_id}
            className="flex-none w-40 text-center bg-gray-50 rounded-md p-3 shadow-sm hover:shadow-md transition-shadow"
          >
            <h4 className="font-medium text-sm text-gray-800 mb-1 truncate">
              {dd.name}
            </h4>
            <p className="text-xs text-gray-600 mb-2 line-clamp-2">
              {dd.vicinity}
            </p>
            <a
              className="inline-block bg-blue-600 text-white text-xs px-2 py-1 rounded-md hover:bg-blue-700 font-medium transition-colors"
              onClick={handleItemClick}
              href={`https://www.google.com/maps?q=${dd.geometry.location.lat},${dd.geometry.location.lng}`}
              target="_blank"
              rel="noopener noreferrer"
            >
              Directions
            </a>
            {dd.business_status === "CLOSED_TEMPORARILY" && (
              <p className="text-xs text-red-500 mt-1">Closed</p>
            )}
          </div>
        ))}
      </div>
    </div>
  );
};

export default VetList;
