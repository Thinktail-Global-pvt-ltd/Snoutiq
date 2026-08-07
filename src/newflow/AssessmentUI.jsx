import React, { useEffect, useState } from "react";
import { Clock, AlertTriangle, ChevronRight, Activity, MapPin, Video, CheckCircle, Info } from "lucide-react";

export function BannerCard({ banner, theme }) {
  if (!banner) return null;
  
  let gradient = "from-blue-50 to-blue-100/50";
  let iconColor = "text-blue-600";
  
  if (theme === "in_clinic" || theme === "vet_at_home") {
    gradient = "from-purple-50 to-purple-100/50";
    iconColor = "text-purple-600";
  } else if (theme === "emergency") {
    gradient = "from-red-50 to-red-100/50";
    iconColor = "text-red-600";
  } else if (theme === "govt") {
    gradient = "from-orange-50 to-orange-100/50";
    iconColor = "text-orange-600";
  }

  return (
    <div className={`mb-6 rounded-2xl bg-gradient-to-br ${gradient} p-5 border border-white/50 shadow-sm`}>
      <div className="flex items-start justify-between">
        <div>
          {banner.eyebrow && <span className={`text-xs font-bold uppercase tracking-wider ${iconColor}`}>{banner.eyebrow}</span>}
          <h3 className="mt-1 text-lg font-bold text-slate-900">{banner.title}</h3>
          {banner.subtitle && <p className="mt-1 text-sm text-slate-700">{banner.subtitle}</p>}
        </div>
      </div>
      {banner.time_badge && (
        <div className="mt-4 inline-flex items-center gap-1.5 rounded-full bg-white/60 px-3 py-1.5 text-xs font-medium text-slate-800 shadow-sm backdrop-blur-sm">
          <Clock size={14} className={iconColor} />
          {banner.time_badge}
        </div>
      )}
    </div>
  );
}

export function HealthScore({ scoreData }) {
  if (!scoreData) return null;
  
  const circumference = 2 * Math.PI * 38;
  const strokeDashoffset = circumference - (scoreData.value / 100) * circumference;

  return (
    <div className="mb-6 flex flex-col items-center justify-center rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
      <div className="relative flex h-24 w-24 items-center justify-center">
        <svg className="h-full w-full -rotate-90 transform" viewBox="0 0 100 100">
          <circle cx="50" cy="50" r="38" fill="none" stroke="#f1f5f9" strokeWidth="8" />
          <circle
            cx="50" cy="50" r="38" fill="none"
            stroke={scoreData.color || "#3b82f6"}
            strokeWidth="8" strokeLinecap="round"
            strokeDasharray={circumference}
            strokeDashoffset={strokeDashoffset}
            className="transition-all duration-1000 ease-out"
          />
        </svg>
        <div className="absolute flex flex-col items-center">
          <span className="text-2xl font-bold text-slate-900">{scoreData.value}</span>
        </div>
      </div>
      <div className="mt-4 text-center">
        <h4 className="text-base font-bold text-slate-900" style={{ color: scoreData.color }}>
          {scoreData.label || "Health Score"}
        </h4>
        {scoreData.subtitle && <p className="text-sm text-slate-500">{scoreData.subtitle}</p>}
      </div>
      
      {scoreData.share?.whatsapp_text && (
        <a 
          href={`https://wa.me/?text=${encodeURIComponent(scoreData.share.whatsapp_text)}`}
          target="_blank" rel="noreferrer"
          className="mt-4 inline-flex items-center gap-2 rounded-full border border-green-200 bg-green-50 px-4 py-2 text-sm font-semibold text-green-700 hover:bg-green-100 transition-colors"
        >
          Share on WhatsApp
        </a>
      )}
    </div>
  );
}

export function DoNowCard({ text }) {
  if (!text) return null;
  return (
    <div className="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4">
      <div className="flex items-start gap-3">
        <AlertTriangle className="shrink-0 text-amber-600" size={20} />
        <div>
          <h4 className="text-sm font-bold text-amber-900">Do Now</h4>
          <p className="mt-1 text-sm text-amber-800">{text}</p>
        </div>
      </div>
    </div>
  );
}

export function ListSection({ title, items, icon: Icon }) {
  if (!items || !items.length) return null;
  return (
    <div className="mb-6 rounded-2xl border border-slate-100 bg-slate-50 p-4">
      <h4 className="flex items-center gap-2 text-sm font-bold text-slate-900 mb-3">
        {Icon && <Icon size={16} className="text-slate-500" />}
        {title}
      </h4>
      <ul className="space-y-2">
        {items.map((item, idx) => (
          <li key={idx} className="flex items-start gap-2 text-sm text-slate-700">
            <span className="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-400" />
            {item}
          </li>
        ))}
      </ul>
    </div>
  );
}

export function ServiceCard({ card, onAction }) {
  return (
    <div className="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-shadow hover:shadow-md">
      {card.badge && (
        <div className={`px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-white ${
          card.badge_variant === "success" ? "bg-emerald-500" : "bg-blue-600"
        }`}>
          {card.badge}
        </div>
      )}
      <div className="p-5">
        <div className="flex items-start justify-between">
          <h3 className="text-lg font-bold text-slate-900">{card.title}</h3>
          <div className="text-right">
            {card.orig_price && <div className="text-xs text-slate-400 line-through">{card.orig_price}</div>}
            <div className="text-lg font-bold text-slate-900">{card.price}</div>
          </div>
        </div>
        
        {card.bullets && card.bullets.length > 0 && (
          <ul className="mt-4 space-y-2">
            {card.bullets.map((b, i) => (
              <li key={i} className="flex items-center gap-2 text-sm text-slate-600">
                <CheckCircle size={14} className="text-emerald-500" />
                {b}
              </li>
            ))}
          </ul>
        )}
        
        {card.cta && (
          <button 
            onClick={() => onAction(card.cta)}
            className="mt-5 w-full rounded-xl bg-slate-900 py-3 text-sm font-semibold text-white transition-colors hover:bg-slate-800"
          >
            {String(card.cta.label).toLowerCase().includes("clinic") ? "Book Appointment" : card.cta.label}
          </button>
        )}
      </div>
    </div>
  );
}

export function FollowUpQuestion({ questionData, onAnswer }) {
  const [answered, setAnswered] = useState(false);

  if (!questionData || !questionData.question) return null;

  if (answered) {
    return (
      <div className="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-3 text-center text-sm font-medium text-slate-600">
        Assessment updated based on your answer
      </div>
    );
  }

  return (
    <div className="mb-6 rounded-2xl border border-blue-100 bg-blue-50/50 p-5">
      <div className="mb-3 text-xs font-bold uppercase tracking-wider text-blue-600">
        {questionData.label || "Help us narrow it down"}
      </div>
      <h3 className="mb-4 text-base font-semibold text-slate-900">{questionData.question}</h3>
      <div className="flex flex-col gap-2">
        {questionData.options?.map((opt, idx) => (
          <button
            key={idx}
            onClick={() => {
              setAnswered(true);
              onAnswer(questionData.question, opt);
            }}
            className="rounded-xl border border-slate-200 bg-white py-2.5 px-4 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 hover:text-slate-900 shadow-sm"
          >
            {opt}
          </button>
        ))}
      </div>
    </div>
  );
}
