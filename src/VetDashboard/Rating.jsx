import React, { useEffect, useState } from "react";
import axios from "../axios";
import { HiStar } from "react-icons/hi";

const Ratings = () => {
  const [doctors, setDoctors] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchRatings = async () => {
      try {
        const res = await axios.get("/api/doctors/ratings"); // API jo doctor ratings return kare
        setDoctors(res.data);
      } catch (err) {
        console.error("Failed to fetch ratings:", err);
      } finally {
        setLoading(false);
      }
    };
    fetchRatings();
  }, []);

  if (loading) return <div className="text-center mt-20">Loading...</div>;

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-6">Doctor Ratings & Reviews</h1>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {doctors.map((doc) => (
          <div
            key={doc.id}
            className="bg-white rounded-lg shadow-md p-4 flex flex-col justify-between"
          >
            <div>
              <h2 className="text-lg font-semibold">{doc.name}</h2>
              <p className="text-gray-500 text-sm">{doc.specialization}</p>
            </div>
            <div className="mt-3 flex items-center gap-1">
              {Array.from({ length: 5 }, (_, i) => (
                <HiStar
                  key={i}
                  className={`h-5 w-5 ${
                    i < Math.round(doc.rating)
                      ? "text-yellow-400"
                      : "text-gray-300"
                  }`}
                />
              ))}
              <span className="ml-2 text-gray-600 text-sm">
                {doc.rating.toFixed(1)} / 5
              </span>
            </div>
            {doc.reviews && doc.reviews.length > 0 && (
              <div className="mt-3 text-sm text-gray-700">
                <strong>Latest Review:</strong> {doc.reviews[0].comment}
              </div>
            )}
          </div>
        ))}
      </div>
    </div>
  );
};

export default Ratings;
