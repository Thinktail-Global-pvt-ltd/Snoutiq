import React from "react";

const stats = [
  { value: "10K+", label: "Happy Pets" },
  { value: "24/7", label: "Support" },
  { value: "98%", label: "Accuracy" },
  { value: "500+", label: "Pet Experts" },
];

const StatsSection = () => {
  return (
    <section className="mb-20 px-4">
      <div className="max-w-6xl mx-auto">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
          {stats.map((stat, i) => (
            <div
              key={i}
              className="text-center bg-blue-50 rounded-xl border border-gray-100 flex flex-col justify-center items-center p-4 min-h-[120px] md:min-h-[140px]"
            >
              <div className="font-bold text-blue-600 text-2xl md:text-3xl mb-2">
                {stat.value}
              </div>
              <div className="text-gray-600 text-xs md:text-sm">
                {stat.label}
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

export default StatsSection;