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
    "focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-200 focus-visible:ring-offset-2 focus-visible:ring-offset-white " +
    "disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100";

  // ✅ Detect custom background/gradient in className
  const hasCustomBg =
    className.includes("bg-") ||
    className.includes("bg-gradient") ||
    className.includes("from-") ||
    className.includes("to-");

  const variants = {
    // ✅ If custom bg is provided, don't force bg-brand-600
    primary: hasCustomBg
      ? "text-white shadow-lg shadow-brand-200 hover:shadow-xl md:hover:-translate-y-[1px] md:active:translate-y-0"
      : "bg-brand-600 text-white shadow-lg shadow-brand-200 hover:bg-brand-700 hover:shadow-xl md:hover:-translate-y-[1px] md:active:translate-y-0",

    secondary:
      "bg-white text-brand-700 border border-brand-200 hover:bg-brand-50 shadow-sm hover:shadow-md",

    ghost:
      "bg-transparent text-calm-subtext hover:text-brand-600 hover:bg-brand-50/50",
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
