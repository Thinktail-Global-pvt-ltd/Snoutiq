import React, { useState } from "react";
import { XMarkIcon, StarIcon } from "@heroicons/react/24/outline";

const RatingModal = ({ visible, onClose, doctorId, userId }) => {
  const [rating, setRating] = useState(0);
  const [comment, setComment] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [hoverRating, setHoverRating] = useState(0);

  const closeModal = () => {
    setRating(0);
    setComment("");
    setHoverRating(0);
    onClose();
  };

  const submitRating = async () => {
    if (rating === 0) {
      alert("Rating Required", "Please select a star rating.");
      return;
    }

    setSubmitting(true);
    try {
      const response = await fetch("https://snoutiq.com/backend/api/reviews", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          user_id: userId,
          doctor_id: doctorId,
          points: rating,
          comment: comment,
        }),
      });

      const data = await response.json();

      if (response.ok) {
        alert("Thank You!", "Your feedback has been submitted.");
        closeModal();
      } else {
        alert("Error", data.message || "Failed to submit rating.");
      }
    } catch (error) {
      alert("Error", "Something went wrong.");
    } finally {
      setSubmitting(false);
    }
  };

  const renderStars = () => {
    return Array.from({ length: 5 }, (_, i) => {
      const starValue = i + 1;
      const isFilled = starValue <= (hoverRating || rating);
      
      return (
        <button
          key={starValue}
          onClick={() => setRating(starValue)}
          onMouseEnter={() => setHoverRating(starValue)}
          onMouseLeave={() => setHoverRating(0)}
          className="p-1 transition-transform hover:scale-110 focus:outline-none"
          type="button"
        >
          <StarIcon
            className={`w-10 h-10 ${
              isFilled 
                ? "text-yellow-400 fill-yellow-400" 
                : "text-gray-400"
            } transition-colors duration-200`}
          />
        </button>
      );
    });
  };

  if (!visible) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60 backdrop-blur-sm">
      <div className="relative w-full max-w-md bg-gray-900 rounded-2xl shadow-2xl border border-gray-700">
        {/* Close Button */}
        <button
          onClick={closeModal}
          className="absolute right-4 top-4 p-2 text-gray-400 hover:text-white transition-colors rounded-full hover:bg-gray-800 z-10"
          type="button"
        >
          <XMarkIcon className="w-6 h-6" />
        </button>

        <div className="p-6 text-center">
          <h2 className="text-2xl font-bold text-white mb-2">
            Rate Your Doctor
          </h2>
          <p className="text-gray-400 mb-6">Tap a star to rate</p>

          {/* Stars Container */}
          <div className="flex justify-center items-center mb-6 space-x-2">
            {renderStars()}
          </div>

          {/* Comment Input */}
          <textarea
            placeholder="Leave a comment (optional)"
            value={comment}
            onChange={(e) => setComment(e.target.value)}
            className="w-full h-24 px-4 py-3 bg-gray-800 border border-gray-700 rounded-xl text-white placeholder-gray-500 resize-none focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
            rows={4}
          />

          {/* Submit Button */}
          <button
            onClick={submitRating}
            disabled={submitting}
            className={`w-full py-3 px-6 mt-6 rounded-xl font-bold transition-all duration-200 ${
              submitting
                ? "bg-gray-600 cursor-not-allowed"
                : "bg-yellow-500 hover:bg-yellow-600 transform hover:scale-105"
            }`}
            type="button"
          >
            {submitting ? (
              <div className="flex items-center justify-center">
                <div className="w-5 h-5 border-2 border-black border-t-transparent rounded-full animate-spin mr-2"></div>
                Submitting...
              </div>
            ) : (
              "Submit Feedback"
            )}
          </button>
        </div>
      </div>
    </div>
  );
};

export default RatingModal;