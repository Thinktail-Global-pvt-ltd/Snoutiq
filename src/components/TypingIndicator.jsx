import React from 'react';

const TypingIndicator = ({ username = 'User' }) => {
  return (
    <div className="flex items-center space-x-1 text-gray-600 font-medium text-8xl">
      <span></span>
      <span className="animate-bounce [animation-delay:0s]">.</span>
      <span className="animate-bounce [animation-delay:0.2s]">.</span>
      <span className="animate-bounce [animation-delay:0.4s]">.</span>
    </div>
  );
};

export default TypingIndicator;
