'use client';

import { ArrowLeft, Search, Dog, Calendar, Phone } from "lucide-react";
import { useNavigate } from "react-router-dom";

const parents = [
  {
    name: "Rahul Sharma",
    pet: "Bruno",
    breed: "Labrador",
    last: "12 Apr 2026",
  },
  {
    name: "Amit Verma",
    pet: "Tommy",
    breed: "Pug",
    last: "10 Apr 2026",
  },
];

export default function NewDoctorSearchView() {
  const navigate = useNavigate();

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col">

      {/* HEADER */}
      <div className="flex items-center gap-3 px-4 py-4 bg-green-600 text-white">
        <button onClick={() => navigate(-1)}>
          <ArrowLeft size={20} />
        </button>
        <h1 className="text-base font-semibold">
          Search Pet Parent
        </h1>
      </div>

      {/* SEARCH BAR */}
      <div className="px-4 py-3 bg-white">
        <div className="flex items-center gap-2 bg-gray-100 px-3 py-2 rounded-lg">
          <Search size={18} className="text-gray-500" />
          <input
            type="text"
            placeholder="Search by name or number..."
            className="w-full bg-transparent outline-none text-sm"
          />
        </div>
      </div>

      {/* LIST */}
      <div className="flex-1 px-4 py-2">

        {parents.map((item, index) => (
          <div
            key={index}
            className="py-4 border-b border-gray-200"
          >
            <p className="font-semibold text-gray-900">
              {item.name}
            </p>

            <p className="text-xs text-gray-500 flex items-center gap-1 mt-1">
              <Dog size={12} />
              {item.pet} • {item.breed}
            </p>

            <p className="text-xs text-gray-400 mt-1 flex items-center gap-1">
              <Calendar size={12} />
              Last: {item.last}
            </p>
          </div>
        ))}

      </div>

      {/* EMPTY STATE (optional auto show) */}
      {parents.length === 0 && (
        <div className="flex-1 flex items-center justify-center text-gray-400 text-sm">
          No results found
        </div>
      )}

    </div>
  );
}