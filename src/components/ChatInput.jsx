import React, { useEffect, useState, useCallback, useRef } from 'react';

const ChatInput = ({ onSendMessage, isLoading = false }) => {
  const [message, setMessage] = useState("");
  const hasLoadedSavedMessage = useRef(false);
  
  // Load saved message only once when component mounts
  useEffect(() => {
    if (!hasLoadedSavedMessage.current) {
      const savedMessage = localStorage.getItem('messageIntended');
      if (savedMessage) {
        setMessage(savedMessage);
        console.log('Loaded saved message:', savedMessage);
      }
      hasLoadedSavedMessage.current = true;
    }
  }, []); // Empty dependency array ensures this runs only once

  // Optimized submit handler with useCallback
  const handleSubmit = useCallback((e) => {
    e.preventDefault();
    if (message.trim() && !isLoading) {
      localStorage.removeItem('messageIntended');
      onSendMessage(message);
      setMessage('');
    }
  }, [message, isLoading, onSendMessage]);

  // Optimized change handler with useCallback
  const handleChange = useCallback((e) => {
    setMessage(e.target.value);
  }, []);

  // Handle Enter key press
  const handleKeyPress = useCallback((e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSubmit(e);
    }
  }, [handleSubmit]);

  return (
    <form onSubmit={handleSubmit} className="w-full max-w-2xl mx-auto">
      <div className="relative flex items-center">
        {/* Microphone Icon */}
        <div className="absolute left-3 sm:left-4 text-gray-400 pointer-events-none">
          <svg 
            className="w-4 h-4 sm:w-5 sm:h-5" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path 
              strokeLinecap="round" 
              strokeLinejoin="round" 
              strokeWidth={2} 
              d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" 
            />
          </svg>
        </div>

        {/* Input Field */}
        <input
          type="text"
          value={message}
          onChange={handleChange}
          onKeyPress={handleKeyPress}
          placeholder="Describe the issue your pet is facing"
          disabled={isLoading}
          className="w-full pl-10 sm:pl-12 pr-14 sm:pr-16 py-3 sm:py-4 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2761E8] focus:border-transparent text-gray-800 placeholder-gray-400 text-sm sm:text-base disabled:opacity-70 disabled:cursor-not-allowed transition-all"
          autoComplete="off"
          spellCheck="false"
        />

        {/* Send Button */}
        <button
          type="submit"
          disabled={isLoading || !message.trim()}
          className="absolute right-2 w-8 h-8 sm:w-10 sm:h-10 bg-[#2761E8] text-white rounded-full flex items-center justify-center hover:bg-[#1e4bd8] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          aria-label={isLoading ? "Sending..." : "Send message"}
        >
          {isLoading ? (
            <svg
              className="animate-spin w-4 h-4 sm:w-5 sm:h-5 text-white"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              aria-hidden="true"
            >
              <circle
                className="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                strokeWidth="4"
              ></circle>
              <path
                className="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
              ></path>
            </svg>
          ) : (
            <svg
              className="w-4 h-4 sm:w-5 sm:h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              aria-hidden="true"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
              />
            </svg>
          )}
        </button>
      </div>
    </form>
  );
};

export default React.memo(ChatInput); 
