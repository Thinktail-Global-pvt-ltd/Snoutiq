import React from "react";

const stats = [
  { value: "10K+", label: "Happy Pets" },
  { value: "24/7", label: "Support" },
  { value: "98%", label: "Accuracy" },
  { value: "500+", label: "Pet Experts" },
];

const StatsSection = () => {
  return (
    <section
      id="stats-anchor"
      className="grid grid-cols-2 md:grid-cols-4 gap-6 mb-20"
    >
      {stats.map((stat, i) => (
        <div
          key={i}
          className="text-center p-6 bg-[#DBEAFE] rounded-xl shadow-sm border border-gray-100 flex flex-col justify-center"
          style={{
            // Fixed dimensions to prevent CLS
            minWidth: '140px',
            minHeight: '160px',
            width: '100%',
            aspectRatio: '1 / 1.1' // Consistent aspect ratio
          }}
        >
          {/* Fixed height containers to prevent text shifting */}
          <div 
            className="text-blue-600 font-bold mb-2 flex items-center justify-center leading-none"
            style={{
              height: '40px', // Fixed height for value
              fontSize: 'clamp(1.5rem, 4vw, 2rem)', // Responsive but consistent sizing
              lineHeight: '1'
            }}
          >
            {stat.value}
          </div>
          <div 
            className="text-gray-600 flex items-center justify-center text-center leading-tight"
            style={{
              height: '24px', // Fixed height for label
              fontSize: '0.875rem', // 14px equivalent
              lineHeight: '1.2'
            }}
          >
            {stat.label}
          </div>
        </div>
      ))}
    </section>
  );
};

export default StatsSection;