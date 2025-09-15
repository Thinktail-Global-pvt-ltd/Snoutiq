import React from "react";

const StatsSection = () => {
  const stats = [
    { value: "10K+", label: "Happy Pets" },
    { value: "24/7", label: "Support" },
    { value: "98%", label: "Accuracy" },
    { value: "500+", label: "Pet Experts" },
  ];

  return (
    <section
      id="stats-anchor"
      className="grid grid-cols-2 md:grid-cols-4 gap-6 mb-20"
    >
      {stats.map((stat, i) => (
        <div
          key={i}
          className="text-center p-6 bg-[#DBEAFE] rounded-xl shadow-sm border border-gray-100 flex flex-col justify-center min-w-[120px]"
          style={{ minHeight: "160px" }}
        >
          <div className="text-3xl font-bold text-blue-600 mb-2 h-8 flex items-center justify-center">
            {stat.value}
          </div>
          <div className="text-gray-600 h-6 flex items-center justify-center">
            {stat.label}
          </div>
        </div>
      ))}
    </section>
  );
};

export default StatsSection;
