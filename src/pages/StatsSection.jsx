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
      className="mb-20"
      style={{
        // Reserve exact space to prevent any shifts
        minHeight: '200px', // Ensures space is reserved before content loads
        containIntrinsicSize: '100% 200px' // Modern CSS containment
      }}
    >
      {/* Mobile-first grid with explicit sizing */}
      <div 
        className="grid gap-4 sm:gap-6"
        style={{
          gridTemplateColumns: 'repeat(2, 1fr)', // Mobile: 2 columns
          '@media (min-width: 768px)': {
            gridTemplateColumns: 'repeat(4, 1fr)' // Desktop: 4 columns
          }
        }}
      >
        {stats.map((stat, i) => (
          <div
            key={i}
            className="text-center bg-[#DBEAFE] rounded-xl shadow-sm border border-gray-100 flex flex-col justify-center items-center"
            style={{
              // Explicit sizing to prevent CLS on mobile
              minWidth: '140px',
              minHeight: '140px', // Reduced for mobile
              maxHeight: '140px',
              width: '100%',
              padding: '16px 12px', // Smaller padding for mobile
              // Ensure consistent aspect ratio across devices
              aspectRatio: '1 / 1'
            }}
          >
            {/* Value container with fixed dimensions */}
            <div 
              className="font-bold text-blue-600 flex items-center justify-center"
              style={{
                height: '32px', // Fixed height prevents shifts
                width: '100%',
                fontSize: '1.5rem', // 24px - consistent across devices
                lineHeight: '1',
                fontWeight: '700',
                textAlign: 'center',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center'
              }}
            >
              {stat.value}
            </div>
            
            {/* Label container with fixed dimensions */}
            <div 
              className="text-gray-600 flex items-center justify-center text-center"
              style={{
                height: '20px', // Fixed height prevents shifts
                width: '100%',
                fontSize: '0.75rem', // 12px for mobile
                lineHeight: '1.2',
                fontWeight: '400',
                marginTop: '8px',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center'
              }}
            >
              {stat.label}
            </div>
          </div>
        ))}
      </div>
      
      {/* CSS-in-JS for responsive grid */}
      <style jsx>{`
        @media (min-width: 768px) {
          .grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
          }
          .grid > div {
            min-height: 160px !important;
            max-height: 160px !important;
            padding: 24px 16px !important;
          }
          .grid > div > div:first-child {
            height: 40px !important;
            font-size: 1.875rem !important; /* 30px for desktop */
          }
          .grid > div > div:last-child {
            height: 24px !important;
            font-size: 0.875rem !important; /* 14px for desktop */
          }
        }
      `}</style>
    </section>
  );
};

export default StatsSection;