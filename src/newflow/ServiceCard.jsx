import React from "react";
import { Link } from "react-router-dom";
import { Button } from "./NewButton";

export function ServiceCard({
  title,
  description,
  icon: Icon,
  features,
  price,
  badge,
  href,
  ctaText = "Book Now",
}) {
  return (
    <div className="group flex flex-col rounded-2xl border border-slate-200 bg-slate-50 p-6 transition-all hover:border-brand/50 hover:shadow-lg hover:shadow-brand/5">
      <div className="mb-4 flex items-center justify-between">
        <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-brand/10 text-brand">
          {Icon ? <Icon className="h-6 w-6" /> : null}
        </div>

        {badge ? (
          <span className="rounded-full bg-brand/20 px-3 py-1 text-xs font-medium text-brand">
            {badge}
          </span>
        ) : null}
      </div>

      <h3 className="mb-2 font-display text-xl font-semibold text-slate-900">
        {title}
      </h3>
      <p className="mb-6 text-sm text-slate-600 flex-grow">{description}</p>

      <ul className="mb-6 space-y-2 text-sm text-slate-700">
        {(features || []).map((feature, i) => (
          <li key={`${feature}-${i}`} className="flex items-start gap-2">
            <span className="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-brand/20 text-[10px] text-brand">
              ✓
            </span>
            <span>{feature}</span>
          </li>
        ))}
      </ul>

      {price ? (
        <div className="mb-6 border-t border-slate-200 pt-4">
          <p className="text-sm text-slate-600">Starting from</p>
          <p className="font-display text-2xl font-bold text-slate-900">
            {price}
          </p>
        </div>
      ) : null}

      {/* Internal route */}
      <Link to={href} className="mt-auto">
        <Button
          variant="outline"
          className="w-full transition-colors group-hover:bg-brand group-hover:text-slate-900"
        >
          {ctaText}
        </Button>
      </Link>
    </div>
  );
}