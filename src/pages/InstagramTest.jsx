import React from 'react';
import InstagramFeed from '../components/InstagramFeed';

/**
 * InstagramTest Page Component
 */
export default function InstagramTest() {
  return (
    <div className="min-h-screen bg-gray-50 p-6 md:p-10 font-sans">
      <div className="max-w-6xl mx-auto space-y-6">
        {/* Page Heading */}
        <h1 className="text-2xl md:text-3xl font-bold text-gray-900 tracking-tight">
          Instagram Feed Test
        </h1>

        {/* Custom Instagram Graph API Feed Component */}
        <InstagramFeed title="Instagram Graph API Feed" />
      </div>
    </div>
  );
}
