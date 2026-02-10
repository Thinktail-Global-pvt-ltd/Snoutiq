import React from "react";

export const Button = ({
  children,
  variant = "primary", // 'primary' | 'secondary' | 'ghost'
  fullWidth = false,
  className = "",
  ...props
}) => {
  // Mobile stays same. Desktop polish remains.
  const baseStyles =
    "py-3.5 px-6 rounded-xl font-semibold transition-all duration-200 active:scale-95 flex items-center justify-center gap-2 " +
    "focus:outline-none focus-visible:ring-2 focus-visible:ring-[#3998de]/30 focus-visible:ring-offset-2 focus-visible:ring-offset-white " +
    "disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100";

  // ✅ Detect custom background/gradient in className
  const hasCustomBg =
    className.includes("bg-") ||
    className.includes("bg-gradient") ||
    className.includes("from-") ||
    className.includes("to-");

  const variants = {
    // ✅ If custom bg is provided, don't force the default color
    primary: hasCustomBg
      ? "text-white shadow-lg shadow-[#3998de]/30 hover:shadow-xl md:hover:-translate-y-[1px] md:active:translate-y-0"
      : "bg-[#3998de] text-white shadow-lg shadow-[#3998de]/30 hover:brightness-95 hover:shadow-xl md:hover:-translate-y-[1px] md:active:translate-y-0",

    secondary:
      "bg-white text-[#3998de] border border-[#3998de]/30 hover:bg-[#3998de]/10 shadow-sm hover:shadow-md",

    ghost:
      "bg-transparent text-calm-subtext hover:text-[#3998de] hover:bg-[#3998de]/10",
  };

  return (
    <button
      className={[
        baseStyles,
        variants[variant],
        fullWidth ? "w-full" : "",
        className, // ✅ Keep last so custom styles apply
      ].join(" ")}
      {...props}
    >
      {children}
    </button>
  );
};
