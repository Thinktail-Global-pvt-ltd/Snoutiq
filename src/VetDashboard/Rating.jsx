import React, { useState, useEffect } from "react";
import { useParams, useNavigate } from "react-router-dom";
import axios from "../axios";

const Ratings = ({ visible = true, onClose }) => {
  const { doctorId, patientId } = useParams();
  const [rating, setRating] = useState(0);
  const [hover, setHover] = useState(0);
  const [comment, setComment] = useState("");
  const [loading, setLoading] = useState(false);
  const [feedback, setFeedback] = useState(null);
  const navigate = useNavigate();

  // Handle Escape key press to close modal
  useEffect(() => {
    const handleEscape = (event) => {
      if (event.keyCode === 27 && visible) {
        handleClose();
      }
    };

    document.addEventListener("keydown", handleEscape);
    return () => {
      document.removeEventListener("keydown", handleEscape);
    };
  }, [visible]);

  // Prevent body scroll when modal is open
  useEffect(() => {
    if (visible) {
      document.body.style.overflow = "hidden";
    } else {
      document.body.style.overflow = "unset";
    }

    return () => {
      document.body.style.overflow = "unset";
    };
  }, [visible]);

  const handleRatingClick = (value) => setRating(value);

  const handleClose = () => {
    if (loading) return; // Prevent closing while submitting
    
    // Reset form state
    setRating(0);
    setHover(0);
    setComment("");
    setFeedback(null);
    
    // Call the onClose prop
    if (onClose) {
      onClose();
    } else {
      // Fallback navigation if no onClose provided
      navigate("/dashboard");
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (rating === 0) {
      setFeedback({ type: "error", msg: "Please select a rating before submitting." });
      return;
    }

    setLoading(true);
    setFeedback(null);

    try {
      const payload = {
        user_id: patientId,
        doctor_id: doctorId,
        points: rating,
        comment: comment || "",
      };

      const res = await axios.post("https://snoutiq.com/backend/api/reviews", payload);
      console.log("API Response:", res.data);

      setFeedback({ type: "success", msg: "⭐ Rating submitted successfully!" });

      // ✅ Navigate after short delay
      setTimeout(() => {
        handleClose();
        navigate("/dashboard"); // ✅ redirect to dashboard
      }, 2000);
    } catch (err) {
      console.error("Failed to submit rating:", err);
      setFeedback({
        type: "error",
        msg: err.response?.data?.message || "❌ Failed to submit rating. Please try again.",
      });
    } finally {
      setLoading(false);
    }
  };

  // Star Icon Component
  const StarIcon = ({ filled, className = "" }) => (
    <svg 
      className={`w-8 h-8 ${className}`}
      fill={filled ? "currentColor" : "none"}
      stroke="currentColor" 
      viewBox="0 0 24 24"
    >
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        strokeWidth={2}
        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
      />
    </svg>
  );

  if (!visible) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
      {/* Backdrop Click Handler */}
      <div 
        className="absolute inset-0" 
        onClick={handleClose}
      />
      
      {/* Modal Container */}
      <div className="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-all duration-300 scale-100 relative z-10">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-100">
          <div>
            <h2 className="text-xl font-bold text-gray-900">
              Rate Your Experience
            </h2>
            <p className="text-gray-500 text-sm mt-1">
              How was your consultation with the doctor?
            </p>
          </div>
          <button
            onClick={handleClose}
            disabled={loading}
            className={`
              text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full p-2 transition-colors
              ${loading ? "opacity-50 cursor-not-allowed" : ""}
            `}
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div className="p-6">
          {/* Star Rating */}
          <div className="text-center mb-6">
            <div className="flex justify-center items-center space-x-1 mb-3">
              {[1, 2, 3, 4, 5].map((star) => (
                <button
                  key={star}
                  type="button"
                  onClick={() => handleRatingClick(star)}
                  onMouseEnter={() => setHover(star)}
                  onMouseLeave={() => setHover(0)}
                  disabled={loading}
                  className={`
                    p-1 transition-transform hover:scale-110 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded-full
                    ${loading ? "cursor-not-allowed opacity-60" : ""}
                  `}
                >
                  <StarIcon
                    filled={star <= (hover || rating)}
                    className={`
                      ${star <= (hover || rating) 
                        ? "text-yellow-400" 
                        : "text-gray-300"
                      } 
                      transition-colors duration-200
                    `}
                  />
                </button>
              ))}
            </div>
            <p className="text-sm text-gray-600">
              {rating ? `You rated ${rating} out of 5` : "Tap a star to rate"}
            </p>
          </div>

          {/* Comment Box */}
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label htmlFor="comment" className="block text-sm font-medium text-gray-700 mb-2">
                Additional Feedback (Optional)
              </label>
              <textarea
                id="comment"
                value={comment}
                onChange={(e) => setComment(e.target.value)}
                placeholder="Share your experience with the doctor..."
                rows="3"
                disabled={loading}
                className={`
                  w-full px-3 py-2 border border-gray-300 rounded-lg resize-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors
                  ${loading ? "bg-gray-100 cursor-not-allowed opacity-60" : ""}
                `}
              />
            </div>

            {/* Feedback Message */}
            {feedback && (
              <div
                className={`p-3 rounded-lg text-sm font-medium ${
                  feedback.type === "success" 
                    ? "bg-green-50 text-green-800 border border-green-200" 
                    : "bg-red-50 text-red-800 border border-red-200"
                }`}
              >
                {feedback.msg}
              </div>
            )}

            {/* Action Buttons */}
            <div className="flex gap-3">
              {/* Close Button */}
              <button
                type="button"
                onClick={handleClose}
                disabled={loading}
                className={`
                  flex-1 py-3 px-4 rounded-lg font-semibold transition-all duration-200 border
                  ${loading 
                    ? "bg-gray-100 text-gray-400 border-gray-300 cursor-not-allowed" 
                    : "bg-white text-gray-700 border-gray-300 hover:bg-gray-50 hover:border-gray-400"
                  }
                `}
              >
                Close
              </button>

              {/* Submit Button */}
              <button
                type="submit"
                disabled={loading || rating === 0}
                className={`
                  flex-1 py-3 px-4 rounded-lg font-semibold transition-all duration-200
                  ${loading || rating === 0
                    ? "bg-gray-300 text-gray-500 cursor-not-allowed" 
                    : "bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg hover:shadow-xl transform hover:scale-105"
                  }
                `}
              >
                {loading ? (
                  <div className="flex items-center justify-center">
                    <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>
                    Submitting...
                  </div>
                ) : (
                  "Submit Rating"
                )}
              </button>
            </div>
          </form>

          {/* Footer Note */}
          <p className="text-xs text-gray-500 text-center mt-4">
            Your feedback helps us improve our services
          </p>
        </div>
      </div>
    </div>
  );
};

export default Ratings;