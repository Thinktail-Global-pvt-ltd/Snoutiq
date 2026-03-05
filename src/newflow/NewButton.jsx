import React from "react";
import { cn } from "../utils/cn";

export const Button = React.forwardRef(
  ({ className, variant = "primary", size = "md", ...props }, ref) => {
    const baseStyles =
      "inline-flex items-center justify-center rounded-full font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand disabled:pointer-events-none disabled:opacity-50";

    const variants = {
      primary: "bg-accent text-slate-900 hover:bg-accent-hover",
      brand: "bg-brand text-slate-900 hover:bg-brand-dark",
      secondary: "bg-slate-50 text-slate-900 hover:bg-slate-100",
      outline: "border border-brand text-brand hover:bg-brand-light",
      ghost: "hover:bg-slate-100 text-slate-900",
    };

    const sizes = {
      sm: "h-12 md:h-10 px-4 text-sm",
      md: "h-12 px-6 text-base",
      lg: "h-14 px-8 text-lg",
    };

    return (
      <button
        ref={ref}
        className={cn(baseStyles, variants[variant], sizes[size], className)}
        {...props}
      />
    );
  }
);

Button.displayName = "Button";

// Optional alias (agar kahin NewButton naam se use ho raha ho)
export const NewButton = Button;