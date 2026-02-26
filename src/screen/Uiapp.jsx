import { useState } from "react";

// ══════════════════════════════════════════════════════════════════════════════
// THEME
// ══════════════════════════════════════════════════════════════════════════════

const THEMES = {
  dark: {
    shell:       "#0D0D0D",
    wallpaper:   "linear-gradient(135deg, #0f0c29 0%, #1a1a2e 50%, #16213e 100%)",
    card:        "rgba(255,255,255,0.04)",
    cardBorder:  "rgba(255,255,255,0.07)",
    cardStrong:  "linear-gradient(135deg, #1C1C1E, #2C2C2E)",
    text:        "#FFFFFF",
    sub:         "rgba(255,255,255,0.45)",
    faint:       "rgba(255,255,255,0.25)",
    ghost:       "rgba(255,255,255,0.07)",
    label:       "rgba(255,255,255,0.33)",
    navBg:       "rgba(13,13,13,0.97)",
    navBorder:   "rgba(255,255,255,0.06)",
    inputBg:     "rgba(255,255,255,0.06)",
    inputBorder: "rgba(255,255,255,0.12)",
    aiBubble:    "rgba(255,255,255,0.08)",
    row:         "rgba(255,255,255,0.03)",
    dashed:      "rgba(255,255,255,0.11)",
    status:      "rgba(255,255,255,0.45)",
    pillBorder:  "rgba(255,255,255,0.1)",
    pillText:    "rgba(255,255,255,0.55)",
    divider:     "rgba(255,255,255,0.06)",
    toggleOff:   "rgba(255,255,255,0.1)",
    taskDone:    "rgba(46,196,182,0.06)",
    taskDoneBd:  "rgba(46,196,182,0.15)",
  },
  light: {
    shell:       "#F5F4F0",
    wallpaper:   "linear-gradient(135deg, #E8E6FF 0%, #F0EEF8 50%, #E6EEF8 100%)",
    card:        "#FFFFFF",
    cardBorder:  "rgba(0,0,0,0.07)",
    cardStrong:  "linear-gradient(135deg, #FFFFFF, #F2F1ED)",
    text:        "#111111",
    sub:         "rgba(0,0,0,0.5)",
    faint:       "rgba(0,0,0,0.28)",
    ghost:       "rgba(0,0,0,0.05)",
    label:       "rgba(0,0,0,0.38)",
    navBg:       "rgba(245,244,240,0.97)",
    navBorder:   "rgba(0,0,0,0.08)",
    inputBg:     "rgba(0,0,0,0.05)",
    inputBorder: "rgba(0,0,0,0.1)",
    aiBubble:    "rgba(0,0,0,0.06)",
    row:         "rgba(0,0,0,0.025)",
    dashed:      "rgba(0,0,0,0.1)",
    status:      "rgba(0,0,0,0.38)",
    pillBorder:  "rgba(0,0,0,0.1)",
    pillText:    "rgba(0,0,0,0.5)",
    divider:     "rgba(0,0,0,0.07)",
    toggleOff:   "rgba(0,0,0,0.1)",
    taskDone:    "rgba(46,196,182,0.07)",
    taskDoneBd:  "rgba(46,196,182,0.2)",
  },
};

// ══════════════════════════════════════════════════════════════════════════════
// DATA
// ══════════════════════════════════════════════════════════════════════════════

const PETS = [
  { id: 1, name: "Bruno",  breed: "Golden Retriever", age: "8 months", gender: "Male",   weight: "14 kg", emoji: "🐶", color: "#FF6B35", status: "Healthy", neutered: false },
  { id: 2, name: "Mochi",  breed: "Persian Cat",      age: "2 years",  gender: "Female", weight: "4 kg",  emoji: "🐱", color: "#9B5DE5", status: "Healthy", neutered: true  },
  { id: 3, name: "Pepper", breed: "Indie Dog",         age: "4 months", gender: "Male",   weight: "5 kg",  emoji: "🐕", color: "#2EC4B6", status: "On meds", neutered: false },
];

// Deworming schedule — NOT daily, shown as upcoming reminders only
const DEWORMING_SCHEDULE = {
  lastDone:    "Nov 24, 2024",
  lastProduct: "Milbemax",
  nextDue:     "Feb 24, 2025",           // quarterly for 8-month dog
  status:      "overdue",               // "ok" | "due_soon" | "overdue"
  daysOverdue: 2,
  schedule: "Quarterly (every 3 months) — adult dog schedule",
  log: [
    { date: "Aug 24, 2024",  product: "Milbemax",    done: true  },
    { date: "Nov 24, 2024",  product: "Milbemax",    done: true  },
    { date: "Feb 24, 2025",  product: "Due now",     done: false, overdue: true },
    { date: "May 24, 2025",  product: "Upcoming",    done: false },
  ],
};

const MEDICATIONS = [
  { id: "m1", name: "Meloxicam 1mg",   reason: "Joint inflammation", dose: "1 tablet", times: ["8:00 AM", "8:00 PM"], daysLeft: 4,  color: "#F72585" },
  { id: "m2", name: "Probiotic paste", reason: "Gut health",         dose: "2 ml",     times: ["6:00 PM"],             daysLeft: 12, color: "#2EC4B6" },
];

const MED_LOG_DATA = [
  { date: "Feb 22", day: "Today",    entries: [{ name: "Meloxicam AM", done: true }, { name: "Probiotic", done: true }, { name: "Meloxicam PM", done: false }] },
  { date: "Feb 21", day: "Yesterday",entries: [{ name: "Meloxicam AM", done: true }, { name: "Probiotic", done: true }, { name: "Meloxicam PM", done: true  }] },
  { date: "Feb 20", day: "Thu",      entries: [{ name: "Meloxicam AM", done: true }, { name: "Probiotic", done: false}, { name: "Meloxicam PM", done: true  }] },
  { date: "Feb 19", day: "Wed",      entries: [{ name: "Meloxicam AM", done: true }, { name: "Probiotic", done: true }, { name: "Meloxicam PM", done: true  }] },
  { date: "Feb 18", day: "Tue",      entries: [{ name: "Meloxicam AM", done: true }, { name: "Probiotic", done: true }, { name: "Meloxicam PM", done: true  }] },
];

const VACCINATIONS = [
  { id: "v1", name: "DHPPiL (6-in-1)",           due: "Oct 2024",     done: true,  notes: "Core vaccine" },
  { id: "v2", name: "DHPPiL Booster 1",           due: "Nov 2024",     done: true,  notes: "4 weeks after first" },
  { id: "v3", name: "DHPPiL Booster 2",           due: "Dec 2024",     done: true,  notes: "4 weeks after booster 1" },
  { id: "v4", name: "Anti-Rabies",                due: "Jan 2025",     done: true,  notes: "Mandatory by law in India" },
  { id: "v5", name: "Kennel Cough (Bordetella)",  due: "Mar 8, 2025",  done: false, notes: "Due in 14 days", urgent: true },
  { id: "v6", name: "Annual Booster + Rabies",    due: "Oct 2025",     done: false, notes: "Annual renewal" },
];

const UPCOMING_APPOINTMENT = {
  type:    "clinic",
  clinic:  "Paws & Care Clinic",
  vet:     "Dr. Priya Sharma",
  reason:  "Kennel Cough vaccine",
  date:    "Mar 8, 2025",
  day:     "Saturday",
  time:    "11:30 AM",
  daysAway: 14,
};

const UPCOMING_CALL = {
  type:    "video",
  vet:     "Dr. Anjali Mehta",
  reason:  "Deworming follow-up",
  date:    "Feb 26, 2025",
  day:     "Wednesday",
  time:    "7:00 PM",
  daysAway: 4,
};

const ONLINE_VETS = [
  { id: 1, name: "Dr. Anjali Mehta", spec: "Small Animals",    rating: 4.9, reviews: 312, wait: "~3 min",  fee: 149, online: true,  avatar: "👩‍⚕️" },
  { id: 2, name: "Dr. Rohan Kapoor", spec: "Canine Specialist", rating: 4.8, reviews: 204, wait: "~8 min",  fee: 199, online: true,  avatar: "👨‍⚕️" },
  { id: 3, name: "Dr. Sneha Iyer",   spec: "Nutrition & Diet", rating: 4.7, reviews: 98,  wait: "~15 min", fee: 129, online: false, avatar: "👩‍⚕️" },
];

const CLINIC_SLOTS = [
  { day: "Today", date: "22 Feb", slots: ["11:30 AM", "1:00 PM",  "3:30 PM"] },
  { day: "Mon",   date: "23 Feb", slots: ["9:00 AM",  "10:30 AM", "4:00 PM"] },
  { day: "Tue",   date: "24 Feb", slots: ["11:00 AM", "2:00 PM"]             },
];

const AI_CHAT_INIT = [
  { role: "ai", text: "Hey! Tell me what's going on with Bruno. Describe what you've noticed — when it started, how he's been behaving, anything that changed. The more you share, the better I can help." },
];

const CONSULT_HISTORY = [
  { id: "c1", vet: "Dr. Anjali Mehta", date: "Feb 10, 2025", reason: "Ear scratching & head shaking",  duration: "18 min", fee: 149, prescription: true,  avatar: "👩‍⚕️" },
  { id: "c2", vet: "Dr. Rohan Kapoor", date: "Jan 28, 2025", reason: "Diet & weight review",           duration: "12 min", fee: 199, prescription: false, avatar: "👨‍⚕️" },
  { id: "c3", vet: "Dr. Sneha Iyer",   date: "Dec 5, 2024",  reason: "Post-vaccination follow-up",    duration: "9 min",  fee: 129, prescription: false, avatar: "👩‍⚕️" },
];

const APPT_HISTORY = [
  { id: "a1", clinic: "Paws & Care Clinic", date: "Feb 10, 2025", reason: "Ear infection treatment",    vet: "Dr. Priya Sharma", outcome: "Prescribed Meloxicam, ear drops",     done: true  },
  { id: "a2", clinic: "Paws & Care Clinic", date: "Jan 15, 2025", reason: "Routine checkup + weight",  vet: "Dr. Priya Sharma", outcome: "All clear. Weight up 1 kg",            done: true  },
  { id: "a3", clinic: "VetCare Saket",       date: "Dec 3, 2024",  reason: "Anti-Rabies vaccination",   vet: "Dr. Arun Nair",    outcome: "Vaccination done, cert issued",        done: true  },
  { id: "a4", clinic: "Paws & Care Clinic", date: "Mar 8, 2025",  reason: "Kennel Cough vaccine",      vet: "Dr. Priya Sharma", outcome: "Upcoming",                             done: false },
];

const DOCUMENTS = [
  { id: "d1", type: "Prescription",     label: "Ear Infection Rx",           date: "Feb 10, 2025", icon: "💊", color: "#F72585" },
  { id: "d2", type: "Blood Report",     label: "Routine Blood Panel",         date: "Jan 15, 2025", icon: "🩸", color: "#FF6B35" },
  { id: "d3", type: "Vaccination Card", label: "Vaccination Certificate",     date: "Jan 2025",     icon: "💉", color: "#2EC4B6" },
  { id: "d4", type: "Health Cert",      label: "Health Certificate",          date: "Dec 2024",     icon: "🏥", color: "#9B5DE5" },
  { id: "d5", type: "Travel Cert",      label: "Fit to Fly — Delhi to Goa",  date: "Dec 2024",     icon: "✈️", color: "#FFD60A" },
];

// Daily tasks — NO deworming here
const ROUTINE_TASKS_INIT = [
  { id: 1, icon: "🍗", label: "Morning feed",    time: "8:00 AM",  done: true  },
  { id: 2, icon: "🦮", label: "Walk (20 min)",   time: "9:00 AM",  done: true  },
  { id: 4, icon: "🍗", label: "Evening feed",    time: "6:00 PM",  done: false },
  { id: 5, icon: "🪥", label: "Brush teeth",     time: "8:00 PM",  done: false },
];

const toMin = s => {
  const [h, rest] = s.split(":");
  const [m, ap]   = rest.split(" ");
  return (parseInt(h) % 12 + (ap === "PM" ? 12 : 0)) * 60 + parseInt(m);
};

const MED_TASKS_INIT = MEDICATIONS.flatMap((med, mi) =>
  med.times.map((t, ti) => ({
    id:       `med_${mi}_${ti}`,
    icon:     "💊",
    label:    `${med.name} — ${med.dose}`,
    time:     t,
    done:     false,
    isMed:    true,
    medColor: med.color,
  }))
);

const ALL_TASKS_INIT = [...ROUTINE_TASKS_INIT, ...MED_TASKS_INIT]
  .sort((a, b) => toMin(a.time) - toMin(b.time));

const VITALS = [
  { label: "Last vet visit", value: "12 days ago", icon: "🏥", ok: true  },
  { label: "Next vaccine",   value: "Mar 8",        icon: "💉", ok: true  },
  { label: "Weight check",   value: "Overdue",      icon: "⚖️", ok: false },
];

// ══════════════════════════════════════════════════════════════════════════════
// SHARED UI
// ══════════════════════════════════════════════════════════════════════════════

function SL({ children, th, style = {} }) {
  return <p style={{ color: th.label, fontSize: "11px", fontWeight: 700, textTransform: "uppercase", letterSpacing: "1.4px", margin: "0 0 12px", ...style }}>{children}</p>;
}

function Card({ children, th, style = {} }) {
  return <div style={{ background: th.card, border: `1px solid ${th.cardBorder}`, borderRadius: "20px", padding: "18px", ...style }}>{children}</div>;
}

function Badge({ color, children }) {
  return <span style={{ background: `${color}22`, color, fontSize: "10px", fontWeight: 700, padding: "3px 8px", borderRadius: "20px", border: `1px solid ${color}44` }}>{children}</span>;
}

function Pill({ label, active, onClick, activeColor = "#FF6B35", th }) {
  return (
    <div onClick={onClick} style={{ padding: "7px 14px", borderRadius: "20px", cursor: "pointer", border: `1px solid ${active ? `${activeColor}55` : th.pillBorder}`, background: active ? `${activeColor}18` : th.ghost, color: active ? activeColor : th.pillText, fontSize: "12px", fontWeight: 600, transition: "all 0.15s", whiteSpace: "nowrap" }}>
      {label}
    </div>
  );
}

function TabBar({ tabs, active, onChange, th }) {
  return (
    <div style={{ display: "flex", background: th.ghost, borderRadius: "16px", padding: 4, gap: 4 }}>
      {tabs.map(t => (
        <button key={t.id} onClick={() => onChange(t.id)} style={{ flex: 1, padding: "9px 4px", borderRadius: "12px", border: "none", cursor: "pointer", fontFamily: "'DM Sans', sans-serif", fontSize: "12px", fontWeight: 700, transition: "all 0.2s", background: active === t.id ? "linear-gradient(135deg, #FF6B35, #F72585)" : "transparent", color: active === t.id ? "#fff" : th.faint }}>
          {t.label}
        </button>
      ))}
    </div>
  );
}

// ══════════════════════════════════════════════════════════════════════════════
// MODALS
// ══════════════════════════════════════════════════════════════════════════════

function VaxModal({ onClose, th }) {
  return (
    <div style={{ position: "fixed", inset: 0, background: "rgba(0,0,0,0.7)", zIndex: 200, display: "flex", alignItems: "flex-end", justifyContent: "center" }} onClick={onClose}>
      <div style={{ width: 390, background: th.shell, borderRadius: "32px 32px 0 0", padding: "24px 24px 44px", border: `1px solid ${th.cardBorder}`, maxHeight: "82vh", display: "flex", flexDirection: "column" }} onClick={e => e.stopPropagation()}>
        <div style={{ width: 40, height: 4, background: th.ghost, borderRadius: 4, margin: "0 auto 20px", flexShrink: 0 }} />
        <h2 style={{ fontFamily: "'Syne', sans-serif", fontSize: "20px", fontWeight: 800, color: th.text, margin: "0 0 4px", flexShrink: 0 }}>Vaccination Schedule 💉</h2>
        <p style={{ color: th.sub, fontSize: "12px", margin: "0 0 20px", flexShrink: 0 }}>Bruno · Golden Retriever · India schedule</p>
        <div style={{ overflowY: "auto", scrollbarWidth: "none", flex: 1 }}>
          {VACCINATIONS.map((v, i) => (
            <div key={v.id} style={{ display: "flex", gap: 12, alignItems: "flex-start" }}>
              <div style={{ display: "flex", flexDirection: "column", alignItems: "center", paddingTop: 4 }}>
                <div style={{ width: 24, height: 24, borderRadius: "50%", background: v.done ? "linear-gradient(135deg, #2EC4B6, #0BD3C5)" : v.urgent ? "rgba(247,37,133,0.15)" : th.ghost, border: v.urgent && !v.done ? "1px solid rgba(247,37,133,0.4)" : "none", display: "flex", alignItems: "center", justifyContent: "center", fontSize: "11px", color: v.done ? "#fff" : v.urgent ? "#F72585" : th.faint, fontWeight: 700, flexShrink: 0 }}>
                  {v.done ? "✓" : v.urgent ? "!" : i + 1}
                </div>
                {i < VACCINATIONS.length - 1 && <div style={{ width: 2, height: 28, background: th.divider, margin: "4px 0" }} />}
              </div>
              <div style={{ flex: 1, paddingBottom: 8 }}>
                <div style={{ display: "flex", gap: 8, alignItems: "center", flexWrap: "wrap" }}>
                  <p style={{ margin: 0, fontSize: "13px", fontWeight: 600, color: v.done ? th.sub : th.text }}>{v.name}</p>
                  {v.urgent && <Badge color="#F72585">Due soon</Badge>}
                </div>
                <p style={{ margin: "2px 0 0", fontSize: "11px", color: th.faint }}>{v.due} · {v.notes}</p>
              </div>
            </div>
          ))}

          {/* Deworming schedule section */}
          <div style={{ marginTop: 20, borderTop: `1px solid ${th.divider}`, paddingTop: 20 }}>
            <SL th={th}>Deworming Schedule 🪱</SL>
            <div style={{ background: "rgba(255,107,53,0.08)", border: "1px solid rgba(255,107,53,0.2)", borderRadius: "14px", padding: "12px 14px", marginBottom: 14 }}>
              <p style={{ margin: 0, fontSize: "12px", fontWeight: 700, color: "#FF6B35" }}>Bruno's schedule · 8 months (adult)</p>
              <p style={{ margin: "4px 0 0", fontSize: "12px", color: th.sub, lineHeight: 1.6 }}>
                Every 3 months (quarterly) — standard adult dog schedule.<br />
                If he eats raw food or goes outdoors a lot, your vet may recommend more often.
              </p>
            </div>
            {DEWORMING_SCHEDULE.log.map((d, i) => (
              <div key={i} style={{ display: "flex", justifyContent: "space-between", alignItems: "center", padding: "10px 0", borderBottom: `1px solid ${th.divider}` }}>
                <span style={{ fontSize: "12px", color: th.sub }}>{d.date}</span>
                <span style={{ fontSize: "12px", fontWeight: 600, color: d.overdue ? "#F72585" : d.done ? "#2EC4B6" : th.faint }}>{d.product}</span>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

function MedLogModal({ onClose, th }) {
  const [activeTab, setActiveTab] = useState("log");
  const allDone  = day => day.entries.filter(e => e.done).length;
  const allTotal = day => day.entries.length;

  return (
    <div style={{ position: "fixed", inset: 0, background: "rgba(0,0,0,0.7)", zIndex: 200, display: "flex", alignItems: "flex-end", justifyContent: "center" }} onClick={onClose}>
      <div style={{ width: 390, background: th.shell, borderRadius: "32px 32px 0 0", padding: "24px 24px 44px", border: `1px solid ${th.cardBorder}`, maxHeight: "85vh", display: "flex", flexDirection: "column" }} onClick={e => e.stopPropagation()}>
        <div style={{ width: 40, height: 4, background: th.ghost, borderRadius: 4, margin: "0 auto 20px", flexShrink: 0 }} />
        <h2 style={{ fontFamily: "'Syne', sans-serif", fontSize: "20px", fontWeight: 800, color: th.text, margin: "0 0 4px", flexShrink: 0 }}>Medication Logs 💊</h2>
        <p style={{ color: th.sub, fontSize: "12px", margin: "0 0 16px", flexShrink: 0 }}>Bruno · Active & history</p>

        {/* Mini tabs */}
        <div style={{ display: "flex", gap: 8, marginBottom: 18, flexShrink: 0 }}>
          {["log", "active"].map(t => (
            <button key={t} onClick={() => setActiveTab(t)} style={{ flex: 1, padding: "8px", borderRadius: "12px", border: "none", cursor: "pointer", fontFamily: "'DM Sans', sans-serif", fontSize: "12px", fontWeight: 700, background: activeTab === t ? "linear-gradient(135deg, #FF6B35, #F72585)" : th.ghost, color: activeTab === t ? "#fff" : th.faint }}>
              {t === "log" ? "Compliance Log" : "Active Meds"}
            </button>
          ))}
        </div>

        <div style={{ overflowY: "auto", scrollbarWidth: "none", flex: 1 }}>
          {activeTab === "active" ? (
            <div style={{ display: "flex", flexDirection: "column", gap: 10 }}>
              {MEDICATIONS.map(m => (
                <div key={m.id} style={{ background: `${m.color}12`, border: `1px solid ${m.color}30`, borderRadius: "16px", padding: "16px" }}>
                  <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start" }}>
                    <div>
                      <p style={{ margin: 0, fontSize: "14px", fontWeight: 700, color: th.text }}>{m.name}</p>
                      <p style={{ margin: "3px 0 6px", fontSize: "12px", color: th.sub }}>{m.reason}</p>
                      <p style={{ margin: 0, fontSize: "12px", color: th.sub }}>{m.dose} · {m.times.join(", ")}</p>
                    </div>
                    <div style={{ textAlign: "right", flexShrink: 0, paddingLeft: 12 }}>
                      <p style={{ margin: 0, fontSize: "16px", fontWeight: 800, color: m.color }}>{m.daysLeft}</p>
                      <p style={{ margin: 0, fontSize: "10px", color: th.faint, fontWeight: 600 }}>days left</p>
                    </div>
                  </div>
                  {/* Progress bar */}
                  <div style={{ marginTop: 12, height: 4, background: th.ghost, borderRadius: 4, overflow: "hidden" }}>
                    <div style={{ height: "100%", width: `${(m.daysLeft / 14) * 100}%`, background: m.color, borderRadius: 4 }} />
                  </div>
                </div>
              ))}
              <div style={{ background: th.row, borderRadius: "14px", padding: "14px 16px" }}>
                <p style={{ margin: 0, fontSize: "12px", color: th.sub, lineHeight: 1.6 }}>💡 Tip: Never stop medications early without checking with your vet, even if Bruno seems better.</p>
              </div>
            </div>
          ) : (
            <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
              {/* Week compliance summary */}
              <div style={{ display: "flex", gap: 8 }}>
                {MED_LOG_DATA.slice(0, 5).reverse().map(day => {
                  const pct = allDone(day) / allTotal(day);
                  return (
                    <div key={day.date} style={{ flex: 1, display: "flex", flexDirection: "column", alignItems: "center", gap: 6 }}>
                      <div style={{ width: "100%", height: 48, borderRadius: "10px", background: th.ghost, overflow: "hidden", display: "flex", alignItems: "flex-end" }}>
                        <div style={{ width: "100%", height: `${pct * 100}%`, background: pct === 1 ? "linear-gradient(180deg, #2EC4B6, #0BD3C5)" : pct >= 0.6 ? "linear-gradient(180deg, #FF6B35, #FFB347)" : "linear-gradient(180deg, #F72585, #FF6B35)", borderRadius: "8px 8px 0 0" }} />
                      </div>
                      <p style={{ margin: 0, fontSize: "10px", color: th.faint, fontWeight: 600 }}>{day.day.slice(0,3)}</p>
                    </div>
                  );
                })}
              </div>

              {/* Day-by-day breakdown */}
              {MED_LOG_DATA.map(day => (
                <div key={day.date} style={{ background: th.card, border: `1px solid ${th.cardBorder}`, borderRadius: "16px", padding: "14px 16px" }}>
                  <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 10 }}>
                    <div>
                      <p style={{ margin: 0, fontSize: "13px", fontWeight: 700, color: th.text }}>{day.day}</p>
                      <p style={{ margin: "1px 0 0", fontSize: "11px", color: th.faint }}>{day.date}</p>
                    </div>
                    <span style={{ fontSize: "12px", fontWeight: 700, color: allDone(day) === allTotal(day) ? "#2EC4B6" : "#FF6B35" }}>
                      {allDone(day)}/{allTotal(day)} doses
                    </span>
                  </div>
                  <div style={{ display: "flex", flexDirection: "column", gap: 6 }}>
                    {day.entries.map(e => (
                      <div key={e.name} style={{ display: "flex", alignItems: "center", gap: 10 }}>
                        <div style={{ width: 18, height: 18, borderRadius: "50%", background: e.done ? "linear-gradient(135deg, #2EC4B6, #0BD3C5)" : "rgba(255,59,48,0.15)", border: e.done ? "none" : "1px solid rgba(255,59,48,0.3)", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0 }}>
                          <span style={{ fontSize: "9px", color: e.done ? "#fff" : "#FF3B30", fontWeight: 800 }}>{e.done ? "✓" : "✗"}</span>
                        </div>
                        <p style={{ margin: 0, fontSize: "12px", color: e.done ? th.sub : "#FF3B30", fontWeight: e.done ? 400 : 600 }}>{e.name}</p>
                      </div>
                    ))}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

// ══════════════════════════════════════════════════════════════════════════════
// HOME SCREEN
// ══════════════════════════════════════════════════════════════════════════════

function TaskRow({ task, onToggle, th }) {
  const s = task.done
    ? { bg: th.taskDone,           bd: th.taskDoneBd }
    : task.isMed
    ? { bg: `${task.medColor}09`,  bd: `${task.medColor}30` }
    : { bg: th.row,                bd: th.cardBorder };

  return (
    <div onClick={() => onToggle(task.id)} style={{ display: "flex", alignItems: "center", gap: 12, background: s.bg, border: `1px solid ${s.bd}`, borderRadius: "14px", padding: "11px 14px", cursor: "pointer", transition: "all 0.2s" }}>
      <span style={{ fontSize: "18px" }}>{task.icon}</span>
      <div style={{ flex: 1 }}>
        <p style={{ margin: 0, fontSize: "13px", fontWeight: 600, color: task.done ? th.faint : th.text, textDecoration: task.done ? "line-through" : "none" }}>{task.label}</p>
        <p style={{ margin: "1px 0 0", fontSize: "11px", color: th.faint }}>{task.time}</p>
      </div>
      <div style={{ width: 22, height: 22, borderRadius: "50%", background: task.done ? "linear-gradient(135deg, #2EC4B6, #0BD3C5)" : "transparent", border: task.done ? "none" : `2px solid ${th.pillBorder}`, display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0 }}>
        {task.done && <span style={{ fontSize: "11px", color: "#fff" }}>✓</span>}
      </div>
    </div>
  );
}

// ─── Scenario definitions ────────────────────────────────────────────────────
const SCENARIOS = [
  { id: "normal",      label: "Normal",      emoji: "🐾" },
  { id: "meds",        label: "On Meds",     emoji: "💊" },
  { id: "vet_waiting", label: "Vet Soon",    emoji: "📹" },
];

// Vet-waiting countdown timer helper
function useCountdown(startSeconds) {
  const [secs, setSecs] = useState(startSeconds);
  useState(() => {
    const id = setInterval(() => setSecs(s => s > 0 ? s - 1 : 0), 1000);
    return () => clearInterval(id);
  });
  const m = String(Math.floor(secs / 60)).padStart(2, "0");
  const s = String(secs % 60).padStart(2, "0");
  return `${m}:${s}`;
}

// Pulsing dot component
function PulseDot({ color }) {
  return (
    <div style={{ position: "relative", width: 12, height: 12, flexShrink: 0 }}>
      <div style={{ position: "absolute", inset: 0, borderRadius: "50%", background: color, opacity: 0.3, animation: "pulse 1.6s ease-in-out infinite" }} />
      <div style={{ position: "absolute", inset: 2, borderRadius: "50%", background: color }} />
      <style>{`@keyframes pulse { 0%,100%{transform:scale(1);opacity:0.3} 50%{transform:scale(2.2);opacity:0} }`}</style>
    </div>
  );
}

function HomeScreen({ setActiveNav, activePet, th }) {
  const [scenario,   setScenario]   = useState("normal");
  const [tasks,      setTasks]      = useState(ALL_TASKS_INIT);
  const [showVax,    setShowVax]    = useState(false);
  const [showMedLog, setShowMedLog] = useState(false);

  const toggle    = id => setTasks(tasks.map(t => t.id === id ? { ...t, done: !t.done } : t));

  // Task list varies by scenario
  const visibleTasks = scenario === "meds"
    ? tasks                                       // all tasks incl. meds
    : tasks.filter(t => !t.isMed);               // routine only

  const done     = visibleTasks.filter(t => t.done).length;
  const progress = (done / visibleTasks.length) * 100;
  const medTasks = visibleTasks.filter(t => t.isMed);
  const routTasks = visibleTasks.filter(t => !t.isMed);

  // Countdown for vet-waiting scenario (starts at 28:47)
  const [waitSecs, setWaitSecs] = useState(28 * 60 + 47);
  useState(() => {
    if (scenario !== "vet_waiting") return;
    const id = setInterval(() => setWaitSecs(s => s > 0 ? s - 1 : 0), 1000);
    return () => clearInterval(id);
  });
  const waitMM = String(Math.floor(waitSecs / 60)).padStart(2, "0");
  const waitSS = String(waitSecs % 60).padStart(2, "0");

  // Pet status badge varies by scenario
  const petStatusColor = scenario === "meds" ? "#FF6B35" : scenario === "vet_waiting" ? "#F72585" : "#2EC4B6";
  const petStatusLabel = scenario === "meds" ? "On Meds" : scenario === "vet_waiting" ? "Awaiting Vet" : activePet.status;

  return (
    <div style={{ flex: 1, overflowY: "auto", scrollbarWidth: "none" }}>

      {/* ── Header ────────────────────────────────────────────────────── */}
      <div style={{ padding: "16px 24px 0", display: "flex", justifyContent: "space-between", alignItems: "center" }}>
        <div>
          <p style={{ color: th.sub, fontSize: "13px", margin: 0 }}>Sunday, Feb 22</p>
          <h1 style={{ margin: "2px 0 0", fontFamily: "'Syne', sans-serif", fontSize: "26px", fontWeight: 800, color: th.text, lineHeight: 1.1 }}>Hey Priya 👋</h1>
        </div>
        <div style={{ position: "relative" }}>
          <div style={{ width: 44, height: 44, borderRadius: "50%", background: "linear-gradient(135deg, #FF6B35, #F72585)", display: "flex", alignItems: "center", justifyContent: "center", fontSize: "20px", cursor: "pointer" }}>🔔</div>
          <div style={{ position: "absolute", top: 1, right: 1, width: 10, height: 10, background: "#FF3B30", borderRadius: "50%", border: `2px solid ${th.shell}` }} />
        </div>
      </div>

      {/* ── Scenario switcher ─────────────────────────────────────────── */}
      <div style={{ padding: "14px 24px 0" }}>
        <p style={{ margin: "0 0 8px", fontSize: "10px", fontWeight: 700, color: th.faint, textTransform: "uppercase", letterSpacing: "1.2px" }}>Preview home state</p>
        <div style={{ display: "flex", gap: 8 }}>
          {SCENARIOS.map(sc => {
            const active = scenario === sc.id;
            const accentMap = { normal: "#2EC4B6", meds: "#FF6B35", vet_waiting: "#F72585" };
            const accent = accentMap[sc.id];
            return (
              <div
                key={sc.id}
                onClick={() => setScenario(sc.id)}
                style={{
                  flex: 1,
                  padding: "10px 6px",
                  borderRadius: "14px",
                  textAlign: "center",
                  cursor: "pointer",
                  background: active ? `${accent}18` : th.ghost,
                  border: `1.5px solid ${active ? accent + "55" : th.cardBorder}`,
                  transition: "all 0.2s",
                }}
              >
                <p style={{ margin: 0, fontSize: "18px" }}>{sc.emoji}</p>
                <p style={{ margin: "4px 0 0", fontSize: "11px", fontWeight: 700, color: active ? accent : th.faint }}>{sc.label}</p>
              </div>
            );
          })}
        </div>
      </div>

      {/* ── Pet card ──────────────────────────────────────────────────── */}
      <div style={{ padding: "14px 24px 0" }}>
        <div style={{ background: th.cardStrong, borderRadius: "24px", padding: "20px", border: `1px solid ${th.cardBorder}`, position: "relative", overflow: "hidden" }}>
          <div style={{ position: "absolute", top: -30, right: -30, width: 130, height: 130, background: `radial-gradient(circle, ${activePet.color}35 0%, transparent 70%)`, borderRadius: "50%" }} />
          <div style={{ display: "flex", alignItems: "center", gap: 16 }}>
            <div style={{ position: "relative" }}>
              <div style={{ width: 72, height: 72, borderRadius: "20px", background: `linear-gradient(135deg, ${activePet.color}, #F72585)`, display: "flex", alignItems: "center", justifyContent: "center", fontSize: "38px", flexShrink: 0, boxShadow: `0 8px 24px ${activePet.color}55` }}>{activePet.emoji}</div>
              {/* Live pulse ring for vet-waiting */}
              {scenario === "vet_waiting" && (
                <div style={{ position: "absolute", inset: -4, borderRadius: "24px", border: "2px solid #F72585", animation: "ringPulse 1.8s ease-in-out infinite", opacity: 0.6 }}>
                  <style>{`@keyframes ringPulse{0%,100%{opacity:0.6;transform:scale(1)}50%{opacity:0;transform:scale(1.12)}}`}</style>
                </div>
              )}
            </div>
            <div style={{ flex: 1 }}>
              <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
                <h2 style={{ fontFamily: "'Syne', sans-serif", fontSize: "22px", fontWeight: 800, color: th.text, margin: 0 }}>{activePet.name}</h2>
                <Badge color={petStatusColor}>{petStatusLabel}</Badge>
              </div>
              <p style={{ color: th.sub, fontSize: "13px", margin: "3px 0 10px" }}>{activePet.breed} · {activePet.age} · {activePet.gender}</p>
              <div style={{ display: "flex", gap: 8, flexWrap: "wrap" }}>
                {[["🎂", activePet.age], ["⚖️", activePet.weight], ["📍", "Delhi"]].map(([icon, val]) => (
                  <div key={val} style={{ display: "flex", alignItems: "center", gap: 4, background: th.ghost, padding: "4px 8px", borderRadius: "8px" }}>
                    <span style={{ fontSize: "11px" }}>{icon}</span>
                    <span style={{ color: th.sub, fontSize: "12px", fontWeight: 500 }}>{val}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* ══════════════════════════════════════════════════════════════════
          SCENARIO: VET WAITING — big live card + simplified view below
      ══════════════════════════════════════════════════════════════════ */}
      {scenario === "vet_waiting" && (
        <>
          {/* Live waiting card */}
          <div style={{ padding: "14px 24px 0" }}>
            <div style={{
              background: "linear-gradient(135deg, rgba(247,37,133,0.1), rgba(155,93,229,0.08))",
              border: "1.5px solid rgba(247,37,133,0.3)",
              borderRadius: "22px",
              padding: "20px",
              position: "relative",
              overflow: "hidden",
            }}>
              {/* Glow */}
              <div style={{ position: "absolute", top: -20, right: -20, width: 100, height: 100, background: "radial-gradient(circle, rgba(247,37,133,0.2) 0%, transparent 70%)", borderRadius: "50%" }} />

              {/* Header row */}
              <div style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 16 }}>
                <PulseDot color="#F72585" />
                <p style={{ margin: 0, fontSize: "13px", fontWeight: 700, color: "#F72585" }}>Vet is reviewing your case</p>
              </div>

              {/* Vet info */}
              <div style={{ display: "flex", gap: 14, alignItems: "center", marginBottom: 16 }}>
                <div style={{ width: 52, height: 52, borderRadius: "16px", background: "rgba(255,255,255,0.08)", display: "flex", alignItems: "center", justifyContent: "center", fontSize: "26px", flexShrink: 0, border: "1px solid rgba(247,37,133,0.25)" }}>👩‍⚕️</div>
                <div>
                  <p style={{ margin: 0, fontSize: "15px", fontWeight: 700, color: th.text }}>Dr. Anjali Mehta</p>
                  <p style={{ margin: "2px 0 0", fontSize: "12px", color: th.sub }}>Small Animals · Ear scratching & head shaking</p>
                </div>
              </div>

              {/* Countdown */}
              <div style={{ display: "flex", alignItems: "center", gap: 12, marginBottom: 16 }}>
                <div style={{ flex: 1, background: th.ghost, borderRadius: "14px", padding: "14px 16px", textAlign: "center" }}>
                  <p style={{ margin: 0, fontSize: "11px", color: th.sub, fontWeight: 600 }}>Est. response in</p>
                  <p style={{ margin: "4px 0 0", fontFamily: "'Syne', sans-serif", fontSize: "28px", fontWeight: 800, color: "#F72585", letterSpacing: "2px" }}>{waitMM}:{waitSS}</p>
                </div>
                <div style={{ flex: 1, background: th.ghost, borderRadius: "14px", padding: "14px 16px", textAlign: "center" }}>
                  <p style={{ margin: 0, fontSize: "11px", color: th.sub, fontWeight: 600 }}>Consult fee</p>
                  <p style={{ margin: "4px 0 0", fontFamily: "'Syne', sans-serif", fontSize: "28px", fontWeight: 800, color: th.text }}>₹149</p>
                </div>
              </div>

              {/* Status steps */}
              <div style={{ display: "flex", alignItems: "center", gap: 0, marginBottom: 16 }}>
                {[
                  { label: "Sent",     done: true  },
                  { label: "Reading",  done: true  },
                  { label: "Typing",   done: false, active: true },
                  { label: "Response", done: false },
                ].map((step, i, arr) => (
                  <div key={step.label} style={{ display: "flex", alignItems: "center", flex: i < arr.length - 1 ? 1 : "none" }}>
                    <div style={{ display: "flex", flexDirection: "column", alignItems: "center", gap: 4 }}>
                      <div style={{
                        width: 24, height: 24, borderRadius: "50%",
                        background: step.done ? "linear-gradient(135deg, #2EC4B6, #0BD3C5)" : step.active ? "rgba(247,37,133,0.15)" : th.ghost,
                        border: step.active ? "1.5px solid #F72585" : "none",
                        display: "flex", alignItems: "center", justifyContent: "center",
                        fontSize: "10px", color: step.done ? "#fff" : step.active ? "#F72585" : th.faint, fontWeight: 800,
                      }}>
                        {step.done ? "✓" : step.active ? "●" : "○"}
                      </div>
                      <p style={{ margin: 0, fontSize: "9px", color: step.done ? "#2EC4B6" : step.active ? "#F72585" : th.faint, fontWeight: 700, whiteSpace: "nowrap" }}>{step.label}</p>
                    </div>
                    {i < arr.length - 1 && (
                      <div style={{ flex: 1, height: 2, background: step.done ? "#2EC4B6" : th.ghost, margin: "0 4px 14px" }} />
                    )}
                  </div>
                ))}
              </div>

              {/* Actions */}
              <div style={{ display: "flex", gap: 8 }}>
                <button style={{ flex: 1, padding: "12px", borderRadius: "14px", border: "none", background: "linear-gradient(135deg, #F72585, #9B5DE5)", color: "#fff", fontFamily: "'DM Sans', sans-serif", fontSize: "13px", fontWeight: 700, cursor: "pointer" }}>
                  💬 Open Chat
                </button>
                <button style={{ flex: 1, padding: "12px", borderRadius: "14px", border: "1px solid rgba(255,59,48,0.3)", background: "rgba(255,59,48,0.07)", color: "#FF3B30", fontFamily: "'DM Sans', sans-serif", fontSize: "13px", fontWeight: 700, cursor: "pointer" }}>
                  Cancel
                </button>
              </div>
            </div>
          </div>

          {/* Calm reminder — still show today's routine */}
          <div style={{ padding: "14px 24px 0" }}>
            <div style={{ background: th.ghost, border: `1px solid ${th.cardBorder}`, borderRadius: "14px", padding: "12px 16px", display: "flex", gap: 10, alignItems: "center" }}>
              <span style={{ fontSize: "16px" }}>💡</span>
              <p style={{ margin: 0, fontSize: "12px", color: th.sub, lineHeight: 1.5 }}>While you wait — Bruno still needs his evening feed and walk. Keep his day normal.</p>
            </div>
          </div>
        </>
      )}

      {/* ══════════════════════════════════════════════════════════════════
          SCENARIO: MEDS — show medication alert banner
      ══════════════════════════════════════════════════════════════════ */}
      {scenario === "meds" && (
        <div style={{ padding: "14px 24px 0" }}>
          <div style={{ background: "rgba(255,107,53,0.07)", border: "1px solid rgba(255,107,53,0.22)", borderRadius: "18px", padding: "16px" }}>
            <div style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 12 }}>
              <span style={{ fontSize: "20px" }}>💊</span>
              <p style={{ margin: 0, fontSize: "14px", fontWeight: 700, color: "#FF6B35" }}>Active medication course</p>
            </div>
            {MEDICATIONS.map(m => (
              <div key={m.id} style={{ display: "flex", alignItems: "center", justifyContent: "space-between", padding: "10px 12px", background: `${m.color}10`, border: `1px solid ${m.color}28`, borderRadius: "12px", marginBottom: 8 }}>
                <div>
                  <p style={{ margin: 0, fontSize: "13px", fontWeight: 700, color: th.text }}>{m.name}</p>
                  <p style={{ margin: "2px 0 0", fontSize: "11px", color: th.sub }}>{m.dose} · {m.times.join(" & ")}</p>
                </div>
                <div style={{ textAlign: "right" }}>
                  <p style={{ margin: 0, fontSize: "13px", fontWeight: 800, color: m.color }}>{m.daysLeft}d</p>
                  <p style={{ margin: 0, fontSize: "10px", color: th.faint }}>left</p>
                </div>
              </div>
            ))}
            <div onClick={() => setShowMedLog(true)} style={{ display: "flex", alignItems: "center", justifyContent: "center", gap: 6, padding: "10px", borderRadius: "12px", border: `1px solid ${th.cardBorder}`, background: th.ghost, cursor: "pointer", marginTop: 4 }}>
              <span style={{ fontSize: "14px" }}>📋</span>
              <p style={{ margin: 0, fontSize: "12px", fontWeight: 700, color: th.sub }}>View compliance log →</p>
            </div>
          </div>
        </div>
      )}

      {/* ══════════════════════════════════════════════════════════════════
          SCENARIO: NORMAL — show upcoming call + appointment reminders
      ══════════════════════════════════════════════════════════════════ */}
      {scenario === "normal" && (
        <>
          {/* Upcoming video call reminder */}
          <div style={{ padding: "14px 24px 0" }}>
            <div style={{ background: "rgba(46,196,182,0.07)", border: "1px solid rgba(46,196,182,0.2)", borderRadius: "16px", padding: "14px 16px", display: "flex", gap: 12, alignItems: "center" }}>
              <div style={{ width: 40, height: 40, borderRadius: "12px", background: "rgba(46,196,182,0.15)", display: "flex", alignItems: "center", justifyContent: "center", fontSize: "20px", flexShrink: 0 }}>📹</div>
              <div style={{ flex: 1 }}>
                <p style={{ margin: 0, fontSize: "13px", fontWeight: 700, color: "#2EC4B6" }}>Video call in {UPCOMING_CALL.daysAway} days</p>
                <p style={{ margin: "2px 0 0", fontSize: "11px", color: th.sub }}>{UPCOMING_CALL.vet} · {UPCOMING_CALL.day}, {UPCOMING_CALL.time}</p>
                <p style={{ margin: "1px 0 0", fontSize: "11px", color: th.faint }}>{UPCOMING_CALL.reason}</p>
              </div>
              <button style={{ padding: "7px 12px", borderRadius: "10px", border: "1px solid rgba(46,196,182,0.3)", background: "rgba(46,196,182,0.12)", color: "#2EC4B6", fontFamily: "'DM Sans', sans-serif", fontSize: "11px", fontWeight: 700, cursor: "pointer", whiteSpace: "nowrap" }}>Join</button>
            </div>
          </div>

          {/* Upcoming clinic appointment */}
          <div style={{ padding: "8px 24px 0" }}>
            <div style={{ background: "rgba(155,93,229,0.07)", border: "1px solid rgba(155,93,229,0.2)", borderRadius: "16px", padding: "14px 16px", display: "flex", gap: 12, alignItems: "center" }}>
              <div style={{ width: 40, height: 40, borderRadius: "12px", background: "rgba(155,93,229,0.15)", display: "flex", alignItems: "center", justifyContent: "center", fontSize: "20px", flexShrink: 0 }}>🏥</div>
              <div style={{ flex: 1 }}>
                <p style={{ margin: 0, fontSize: "13px", fontWeight: 700, color: "#9B5DE5" }}>Clinic visit in {UPCOMING_APPOINTMENT.daysAway} days</p>
                <p style={{ margin: "2px 0 0", fontSize: "11px", color: th.sub }}>{UPCOMING_APPOINTMENT.clinic} · {UPCOMING_APPOINTMENT.day}, {UPCOMING_APPOINTMENT.time}</p>
                <p style={{ margin: "1px 0 0", fontSize: "11px", color: th.faint }}>{UPCOMING_APPOINTMENT.reason}</p>
              </div>
              <span style={{ color: "#9B5DE5", fontSize: "16px" }}>→</span>
            </div>
          </div>
        </>
      )}

      {/* ── Deworming overdue (all scenarios) ─────────────────────────── */}
      <div style={{ padding: "8px 24px 0" }}>
        <div style={{ background: "rgba(247,37,133,0.06)", border: "1px solid rgba(247,37,133,0.2)", borderRadius: "16px", padding: "13px 16px", display: "flex", alignItems: "center", gap: 12 }}>
          <span style={{ fontSize: "18px" }}>🪱</span>
          <div style={{ flex: 1 }}>
            <p style={{ margin: 0, fontSize: "13px", fontWeight: 700, color: "#F72585" }}>Deworming overdue by {DEWORMING_SCHEDULE.daysOverdue} days</p>
            <p style={{ margin: "2px 0 0", fontSize: "11px", color: th.sub }}>Next dose was due {DEWORMING_SCHEDULE.nextDue} · {DEWORMING_SCHEDULE.schedule}</p>
          </div>
          <div onClick={() => setShowVax(true)} style={{ fontSize: "14px", color: "#F72585", cursor: "pointer", fontWeight: 700 }}>Info →</div>
        </div>
      </div>

      {/* ── Neutering nudge (all scenarios) ───────────────────────────── */}
      {!activePet.neutered && (
        <div style={{ padding: "8px 24px 0" }}>
          <div style={{ background: "rgba(155,93,229,0.06)", border: "1px solid rgba(155,93,229,0.18)", borderRadius: "16px", padding: "13px 16px", display: "flex", gap: 12 }}>
            <span style={{ fontSize: "20px" }}>✂️</span>
            <div style={{ flex: 1 }}>
              <p style={{ margin: 0, fontSize: "13px", fontWeight: 700, color: "#9B5DE5" }}>Neutering — plan ahead</p>
              <p style={{ margin: "4px 0 0", fontSize: "12px", color: th.sub, lineHeight: 1.5 }}>
                Recommended 12–18 months for {activePet.breed}s. {activePet.name} is {activePet.age}. Good time to discuss with your vet.
              </p>
            </div>
          </div>
        </div>
      )}

      {/* ── Vaccine nudge (all scenarios) ─────────────────────────────── */}
      <div style={{ padding: "8px 24px 0" }}>
        <div onClick={() => setShowVax(true)} style={{ background: "rgba(46,196,182,0.06)", border: "1px solid rgba(46,196,182,0.18)", borderRadius: "16px", padding: "12px 16px", display: "flex", alignItems: "center", gap: 12, cursor: "pointer" }}>
          <span>💉</span>
          <div style={{ flex: 1 }}>
            <p style={{ margin: 0, fontSize: "13px", fontWeight: 700, color: "#2EC4B6" }}>Kennel Cough vaccine due Mar 8</p>
            <p style={{ margin: "2px 0 0", fontSize: "11px", color: th.faint }}>Tap to view full vaccination schedule →</p>
          </div>
        </div>
      </div>

      {/* ── Quick actions ──────────────────────────────────────────────── */}
      <div style={{ padding: "16px 24px 0" }}>
        <SL th={th}>Quick Actions</SL>
        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr 1fr", gap: 10 }}>
          {[
            { icon: "🩺", label: "AI Health\nCheck", color: "#FF6B35", onClick: () => setActiveNav("health") },
            { icon: "📞", label: "Talk to\nVet",     color: "#2EC4B6", onClick: () => setActiveNav("health") },
            { icon: "📋", label: "Med\nLogs",        color: "#9B5DE5", onClick: () => setShowMedLog(true)    },
            { icon: "💉", label: "Vaccines",         color: "#F72585", onClick: () => setShowVax(true)       },
          ].map(({ icon, label, color, onClick }) => (
            <div key={label} onClick={onClick} style={{ background: th.card, border: `1px solid ${th.cardBorder}`, borderRadius: "18px", padding: "14px 8px", display: "flex", flexDirection: "column", alignItems: "center", gap: 8, cursor: "pointer" }}>
              <div style={{ width: 44, height: 44, borderRadius: "14px", background: `${color}20`, border: `1px solid ${color}44`, display: "flex", alignItems: "center", justifyContent: "center", fontSize: "20px" }}>{icon}</div>
              <span style={{ color: th.sub, fontSize: "10px", fontWeight: 600, textAlign: "center", lineHeight: 1.3, whiteSpace: "pre-line" }}>{label}</span>
            </div>
          ))}
        </div>
      </div>

      {/* ── Today's care ───────────────────────────────────────────────── */}
      <div style={{ padding: "20px 24px 0" }}>
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 10 }}>
          <SL th={th} style={{ margin: 0 }}>Today's Care</SL>
          <span style={{ color: th.faint, fontSize: "12px" }}>{done}/{visibleTasks.length} done</span>
        </div>
        <div style={{ height: 4, background: th.ghost, borderRadius: "4px", margin: "10px 0 14px", overflow: "hidden" }}>
          <div style={{ height: "100%", width: `${progress}%`, background: "linear-gradient(90deg, #FF6B35, #F72585)", borderRadius: "4px", transition: "width 0.4s" }} />
        </div>

        {medTasks.length > 0 && (
          <>
            <p style={{ color: th.faint, fontSize: "10px", fontWeight: 700, letterSpacing: "1px", textTransform: "uppercase", margin: "0 0 8px" }}>💊 Medications</p>
            <div style={{ display: "flex", flexDirection: "column", gap: 7, marginBottom: 14 }}>
              {medTasks.map(t => <TaskRow key={t.id} task={t} onToggle={toggle} th={th} />)}
            </div>
            <p style={{ color: th.faint, fontSize: "10px", fontWeight: 700, letterSpacing: "1px", textTransform: "uppercase", margin: "0 0 8px" }}>🐾 Routine</p>
          </>
        )}
        <div style={{ display: "flex", flexDirection: "column", gap: 7 }}>
          {routTasks.map(t => <TaskRow key={t.id} task={t} onToggle={toggle} th={th} />)}
        </div>
      </div>

      {/* ── Health snapshot ────────────────────────────────────────────── */}
      <div style={{ padding: "20px 24px 28px" }}>
        <SL th={th}>Health Snapshot</SL>
        <div style={{ display: "flex", flexDirection: "column", gap: 8 }}>
          {VITALS.map(({ label, value, icon, ok }) => (
            <div key={label} style={{ display: "flex", alignItems: "center", gap: 14, background: th.row, border: `1px solid ${th.cardBorder}`, borderRadius: "14px", padding: "13px 16px" }}>
              <span style={{ fontSize: "18px" }}>{icon}</span>
              <div style={{ flex: 1 }}>
                <p style={{ margin: 0, fontSize: "13px", color: th.sub }}>{label}</p>
                <p style={{ margin: "2px 0 0", fontSize: "14px", fontWeight: 600, color: ok ? th.text : "#FF6B35" }}>{value}</p>
              </div>
              <div style={{ width: 8, height: 8, borderRadius: "50%", background: ok ? "#2EC4B6" : "#FF6B35", boxShadow: ok ? "0 0 8px rgba(46,196,182,0.5)" : "0 0 8px rgba(255,107,53,0.5)" }} />
            </div>
          ))}
        </div>
      </div>

      {showVax    && <VaxModal    onClose={() => setShowVax(false)}    th={th} />}
      {showMedLog && <MedLogModal onClose={() => setShowMedLog(false)} th={th} />}
    </div>
  );
}

// ══════════════════════════════════════════════════════════════════════════════
// HEALTH SCREEN
// ══════════════════════════════════════════════════════════════════════════════

function AiTriage({ th }) {
  const [messages, setMessages] = useState(AI_CHAT_INIT);
  const [input,    setInput]    = useState("");
  const [typing,   setTyping]   = useState(false);

  const autoReplies = [
    "Got it. How long has this been going on? And has anything changed recently — food, environment, new products at home?",
    "Thanks for sharing that. Is he eating and drinking normally otherwise?",
    "Understood. On a scale of 1–10, how much is this affecting his normal behaviour — playing, energy, mood?",
    "Based on what you've shared, this sounds like it could need attention within 24–48 hours. I'd recommend connecting with a vet today. Want me to find you one now?",
  ];
  const replyIndex = { current: 0 };

  const sendText = () => {
    if (!input.trim()) return;
    const val = input;
    setInput("");
    setMessages(m => [...m, { role: "user", text: val }]);
    setTyping(true);
    const idx = Math.min(messages.filter(m => m.role === "ai").length, autoReplies.length - 1);
    setTimeout(() => {
      setMessages(m => [...m, { role: "ai", text: autoReplies[idx] }]);
      setTyping(false);
    }, 1100);
  };

  return (
    <div style={{ display: "flex", flexDirection: "column", gap: 16 }}>
      {/* Disclaimer */}
      <div style={{ background: "rgba(255,107,53,0.08)", border: "1px solid rgba(255,107,53,0.22)", borderRadius: "16px", padding: "14px 16px", display: "flex", gap: 12 }}>
        <span style={{ fontSize: "22px" }}>🤖</span>
        <div>
          <p style={{ margin: 0, fontSize: "13px", fontWeight: 700, color: th.text }}>AI Triage — Not a diagnosis</p>
          <p style={{ margin: "3px 0 0", fontSize: "12px", color: th.sub, lineHeight: 1.5 }}>Describe what you've noticed in your own words. I'll help figure out how urgent it is.</p>
        </div>
      </div>

      {/* Chat */}
      <Card th={th} style={{ padding: "16px" }}>
        <div style={{ display: "flex", flexDirection: "column", gap: 10, maxHeight: 320, minHeight: 200, overflowY: "auto", scrollbarWidth: "none" }}>
          {messages.map((m, i) => (
            <div key={i} style={{ display: "flex", justifyContent: m.role === "user" ? "flex-end" : "flex-start" }}>
              {m.role === "ai" && (
                <div style={{ width: 28, height: 28, borderRadius: "50%", background: "linear-gradient(135deg, #FF6B35, #F72585)", display: "flex", alignItems: "center", justifyContent: "center", fontSize: "14px", flexShrink: 0, marginRight: 8, alignSelf: "flex-end" }}>🤖</div>
              )}
              <div style={{ maxWidth: "78%", padding: "11px 14px", borderRadius: m.role === "user" ? "18px 18px 4px 18px" : "18px 18px 18px 4px", background: m.role === "user" ? "linear-gradient(135deg, #FF6B35, #F72585)" : th.aiBubble, fontSize: "13px", color: m.role === "user" ? "#fff" : th.text, lineHeight: 1.55 }}>
                {m.text}
              </div>
            </div>
          ))}
          {typing && (
            <div style={{ display: "flex" }}>
              <div style={{ width: 28, height: 28, borderRadius: "50%", background: "linear-gradient(135deg, #FF6B35, #F72585)", display: "flex", alignItems: "center", justifyContent: "center", fontSize: "14px", marginRight: 8 }}>🤖</div>
              <div style={{ background: th.aiBubble, padding: "11px 14px", borderRadius: "18px 18px 18px 4px" }}>
                <span style={{ color: th.faint, fontSize: "14px", letterSpacing: 2 }}>●●●</span>
              </div>
            </div>
          )}
        </div>

        {/* Input area */}
        <div style={{ marginTop: 14, borderTop: `1px solid ${th.divider}`, paddingTop: 14 }}>
          <textarea
            value={input}
            onChange={e => setInput(e.target.value)}
            onKeyDown={e => { if (e.key === "Enter" && !e.shiftKey) { e.preventDefault(); sendText(); } }}
            placeholder="Describe what you noticed — when it started, what he's doing, any changes..."
            rows={3}
            style={{ width: "100%", background: th.inputBg, border: `1px solid ${th.inputBorder}`, borderRadius: "14px", padding: "12px 14px", color: th.text, fontSize: "13px", fontFamily: "'DM Sans', sans-serif", outline: "none", resize: "none", boxSizing: "border-box", lineHeight: 1.5 }}
          />
          <button onClick={sendText} style={{ marginTop: 8, width: "100%", padding: "12px", borderRadius: "14px", border: "none", background: input.trim() ? "linear-gradient(135deg, #FF6B35, #F72585)" : th.ghost, color: input.trim() ? "#fff" : th.faint, fontFamily: "'DM Sans', sans-serif", fontSize: "14px", fontWeight: 700, cursor: input.trim() ? "pointer" : "default", transition: "all 0.2s" }}>
            Send to AI Triage →
          </button>
        </div>
      </Card>

      {/* CTA buttons */}
      <div style={{ display: "flex", gap: 10 }}>
        <div style={{ flex: 1, background: "rgba(46,196,182,0.08)", border: "1px solid rgba(46,196,182,0.2)", borderRadius: "14px", padding: "14px", textAlign: "center", cursor: "pointer" }}>
          <p style={{ margin: 0, fontSize: "18px" }}>📞</p>
          <p style={{ margin: "4px 0 0", fontSize: "12px", fontWeight: 700, color: "#2EC4B6" }}>Talk to Vet</p>
          <p style={{ margin: "2px 0 0", fontSize: "10px", color: th.faint }}>~3 min wait</p>
        </div>
        <div style={{ flex: 1, background: "rgba(155,93,229,0.08)", border: "1px solid rgba(155,93,229,0.2)", borderRadius: "14px", padding: "14px", textAlign: "center", cursor: "pointer" }}>
          <p style={{ margin: 0, fontSize: "18px" }}>🏥</p>
          <p style={{ margin: "4px 0 0", fontSize: "12px", fontWeight: 700, color: "#9B5DE5" }}>Book Visit</p>
          <p style={{ margin: "2px 0 0", fontSize: "10px", color: th.faint }}>Today 11:30 AM</p>
        </div>
      </div>
    </div>
  );
}

function Telemedicine({ th }) {
  const [calling, setCalling] = useState(null);
  return (
    <div style={{ display: "flex", flexDirection: "column", gap: 14 }}>
      <div style={{ background: "rgba(46,196,182,0.07)", border: "1px solid rgba(46,196,182,0.2)", borderRadius: "16px", padding: "14px 18px", display: "flex", alignItems: "center", justifyContent: "space-between" }}>
        <div><p style={{ margin: 0, fontSize: "14px", fontWeight: 700, color: th.text }}>Vets available now</p><p style={{ margin: "2px 0 0", fontSize: "12px", color: th.sub }}>Avg connect time under 5 minutes</p></div>
        <div style={{ display: "flex", alignItems: "center", gap: 6, background: "rgba(46,196,182,0.14)", padding: "6px 12px", borderRadius: "20px" }}>
          <div style={{ width: 8, height: 8, borderRadius: "50%", background: "#2EC4B6", boxShadow: "0 0 6px #2EC4B6" }} />
          <span style={{ color: "#2EC4B6", fontSize: "12px", fontWeight: 700 }}>24 / 7</span>
        </div>
      </div>
      <SL th={th}>Available Vets</SL>
      {ONLINE_VETS.map(vet => (
        <Card key={vet.id} th={th} style={{ position: "relative", overflow: "hidden", padding: "16px" }}>
          {vet.online && <div style={{ position: "absolute", top: 14, right: 14, display: "flex", alignItems: "center", gap: 5, background: "rgba(46,196,182,0.12)", padding: "4px 10px", borderRadius: "20px", border: "1px solid rgba(46,196,182,0.25)" }}><div style={{ width: 6, height: 6, borderRadius: "50%", background: "#2EC4B6", boxShadow: "0 0 5px #2EC4B6" }} /><span style={{ color: "#2EC4B6", fontSize: "10px", fontWeight: 700 }}>Online</span></div>}
          <div style={{ display: "flex", gap: 14 }}>
            <div style={{ width: 50, height: 50, borderRadius: "16px", background: th.ghost, display: "flex", alignItems: "center", justifyContent: "center", fontSize: "26px", flexShrink: 0 }}>{vet.avatar}</div>
            <div style={{ flex: 1 }}>
              <p style={{ margin: 0, fontSize: "15px", fontWeight: 700, color: th.text }}>{vet.name}</p>
              <p style={{ margin: "2px 0 6px", fontSize: "12px", color: th.sub }}>{vet.spec}</p>
              <div style={{ display: "flex", gap: 12 }}><span style={{ fontSize: "12px", color: th.sub }}>⭐ {vet.rating} ({vet.reviews})</span><span style={{ fontSize: "12px", color: vet.online ? "#2EC4B6" : th.faint }}>{vet.online ? `⏱ ${vet.wait}` : "Offline"}</span></div>
            </div>
          </div>
          <div style={{ display: "flex", gap: 8, marginTop: 14 }}>
            <div style={{ flex: 1, background: th.ghost, borderRadius: "12px", padding: "8px 0", textAlign: "center" }}><p style={{ margin: 0, fontSize: "10px", color: th.faint }}>Fee</p><p style={{ margin: 0, fontSize: "15px", fontWeight: 800, color: th.text }}>₹{vet.fee}</p></div>
            {vet.online
              ? <button onClick={() => setCalling(vet.id)} style={{ flex: 2, borderRadius: "12px", border: "none", background: calling === vet.id ? "rgba(46,196,182,0.2)" : "linear-gradient(135deg, #2EC4B6, #0BD3C5)", color: "#fff", fontFamily: "'DM Sans', sans-serif", fontSize: "13px", fontWeight: 700, cursor: "pointer", padding: "10px" }}>{calling === vet.id ? "⏳ Connecting…" : "📞 Talk Now"}</button>
              : <button style={{ flex: 2, borderRadius: "12px", border: `1px solid ${th.cardBorder}`, background: "transparent", color: th.faint, fontFamily: "'DM Sans', sans-serif", fontSize: "13px", fontWeight: 600, cursor: "not-allowed", padding: "10px" }}>Notify when online</button>
            }
          </div>
        </Card>
      ))}
    </div>
  );
}

function BookVisit({ th }) {
  const [day, setDay]     = useState(0);
  const [slot, setSlot]   = useState(null);
  const [booked, setBooked] = useState(false);
  const [reason, setReason] = useState("");
  const reasons = ["Routine checkup", "Vaccination", "Skin / coat issue", "Ear problem", "Digestive issue", "Other"];

  if (booked) return (
    <div style={{ display: "flex", flexDirection: "column", alignItems: "center", gap: 16, padding: "20px 0" }}>
      <div style={{ width: 80, height: 80, borderRadius: "50%", background: "rgba(46,196,182,0.1)", border: "2px solid rgba(46,196,182,0.4)", display: "flex", alignItems: "center", justifyContent: "center", fontSize: "36px" }}>✅</div>
      <div style={{ textAlign: "center" }}><p style={{ margin: 0, fontSize: "18px", fontWeight: 800, color: th.text, fontFamily: "'Syne', sans-serif" }}>Appointment Booked!</p><p style={{ margin: "6px 0 0", fontSize: "13px", color: th.sub, lineHeight: 1.5 }}>{CLINIC_SLOTS[day].day}, {CLINIC_SLOTS[day].date} · {slot}<br />Paws & Care Clinic, Saket</p></div>
      <Card th={th} style={{ width: "100%", padding: "16px" }}>
        {[["📍","Paws & Care Clinic"],["🧭","2.4 km · 12 min drive"],["🩺","Dr. Priya Sharma"],["📋", reason || "Routine checkup"]].map(([i, t]) => (
          <div key={t} style={{ display: "flex", gap: 10, padding: "6px 0" }}><span>{i}</span><span style={{ color: th.sub, fontSize: "13px" }}>{t}</span></div>
        ))}
      </Card>
      <button onClick={() => { setBooked(false); setSlot(null); }} style={{ width: "100%", padding: "14px", borderRadius: "16px", border: `1px solid ${th.cardBorder}`, background: "transparent", color: th.faint, fontFamily: "'DM Sans', sans-serif", fontSize: "14px", fontWeight: 600, cursor: "pointer" }}>Book another</button>
    </div>
  );

  return (
    <div style={{ display: "flex", flexDirection: "column", gap: 16 }}>
      <Card th={th} style={{ position: "relative", overflow: "hidden", padding: "16px" }}>
        <div style={{ position: "absolute", top: -20, right: -20, width: 80, height: 80, background: "radial-gradient(circle, rgba(155,93,229,0.18) 0%, transparent 70%)", borderRadius: "50%" }} />
        <div style={{ display: "flex", gap: 14 }}>
          <div style={{ width: 50, height: 50, borderRadius: "14px", background: "rgba(155,93,229,0.1)", border: "1px solid rgba(155,93,229,0.2)", display: "flex", alignItems: "center", justifyContent: "center", fontSize: "24px", flexShrink: 0 }}>🏥</div>
          <div><p style={{ margin: 0, fontSize: "15px", fontWeight: 700, color: th.text }}>Paws & Care Clinic</p><p style={{ margin: "2px 0 0", fontSize: "12px", color: th.sub }}>Saket, New Delhi · 2.4 km</p><div style={{ display: "flex", gap: 10, marginTop: 6 }}><span style={{ color: th.sub, fontSize: "12px" }}>⭐ 4.8</span><span style={{ color: "#2EC4B6", fontSize: "12px" }}>● Open until 9 PM</span></div></div>
        </div>
      </Card>
      <div><SL th={th}>Visit reason</SL><div style={{ display: "flex", flexWrap: "wrap", gap: 8 }}>{reasons.map(r => <Pill key={r} label={r} active={reason === r} onClick={() => setReason(r)} activeColor="#9B5DE5" th={th} />)}</div></div>
      <div>
        <SL th={th}>Pick a day</SL>
        <div style={{ display: "flex", gap: 8 }}>
          {CLINIC_SLOTS.map((d, i) => (
            <div key={d.day} onClick={() => { setDay(i); setSlot(null); }} style={{ flex: 1, padding: "10px", borderRadius: "14px", textAlign: "center", cursor: "pointer", background: day === i ? "linear-gradient(135deg, rgba(155,93,229,0.18), rgba(247,37,133,0.12))" : th.card, border: `1px solid ${day === i ? "rgba(155,93,229,0.4)" : th.cardBorder}` }}>
              <p style={{ margin: 0, fontSize: "11px", color: day === i ? "#9B5DE5" : th.faint, fontWeight: 700 }}>{d.day}</p>
              <p style={{ margin: "3px 0 0", fontSize: "13px", color: day === i ? th.text : th.sub, fontWeight: 700 }}>{d.date}</p>
            </div>
          ))}
        </div>
      </div>
      <div><SL th={th}>Available slots</SL><div style={{ display: "flex", gap: 8, flexWrap: "wrap" }}>{CLINIC_SLOTS[day].slots.map(s => <div key={s} onClick={() => setSlot(s)} style={{ padding: "10px 18px", borderRadius: "12px", cursor: "pointer", border: `1px solid ${slot === s ? "rgba(247,37,133,0.5)" : th.pillBorder}`, background: slot === s ? "rgba(247,37,133,0.1)" : th.card, color: slot === s ? "#F72585" : th.sub, fontSize: "13px", fontWeight: 700 }}>{s}</div>)}</div></div>
      <button onClick={() => setBooked(true)} disabled={!slot} style={{ width: "100%", padding: "16px", borderRadius: "18px", border: "none", background: slot ? "linear-gradient(135deg, #9B5DE5, #F72585)" : th.ghost, color: slot ? "#fff" : th.faint, fontFamily: "'DM Sans', sans-serif", fontSize: "15px", fontWeight: 700, cursor: slot ? "pointer" : "not-allowed" }}>
        {slot ? `Confirm · ${CLINIC_SLOTS[day].day} ${slot}` : "Select a slot to continue"}
      </button>
    </div>
  );
}

function HealthScreen({ th }) {
  const [tab, setTab] = useState("triage");
  const tabs = [{ id: "triage", label: "AI Triage" }, { id: "tele", label: "Talk to Vet" }, { id: "book", label: "Book Visit" }];
  return (
    <>
      <div style={{ padding: "16px 24px 0", display: "flex", justifyContent: "space-between", alignItems: "center" }}>
        <div><p style={{ margin: 0, color: th.sub, fontSize: 12 }}>Bruno · 8 months</p><h1 style={{ margin: "3px 0 0", fontFamily: "'Syne', sans-serif", fontSize: 26, fontWeight: 800, color: th.text }}>Health Care ❤️‍🩹</h1></div>
        <div style={{ width: 44, height: 44, borderRadius: "50%", background: "linear-gradient(135deg, #FF6B35, #F72585)", display: "flex", alignItems: "center", justifyContent: "center", fontSize: 20 }}>🐶</div>
      </div>
      <div style={{ padding: "12px 24px 0" }}>
        <div style={{ background: "rgba(247,37,133,0.07)", border: "1px solid rgba(247,37,133,0.22)", borderRadius: 16, padding: "12px 16px", display: "flex", alignItems: "center", gap: 10 }}>
          <span>🪱</span>
          <div style={{ flex: 1 }}><p style={{ margin: 0, fontSize: 12, fontWeight: 700, color: "#F72585" }}>Deworming overdue by 2 days</p><p style={{ margin: "2px 0 0", fontSize: 11, color: th.sub }}>Ask a vet or book a visit to confirm the right product</p></div>
          <span style={{ color: "#F72585" }}>→</span>
        </div>
      </div>
      <div style={{ padding: "14px 24px 0" }}><TabBar tabs={tabs} active={tab} onChange={setTab} th={th} /></div>
      <div style={{ flex: 1, overflowY: "auto", scrollbarWidth: "none", padding: "16px 24px 24px" }}>
        {tab === "triage" && <AiTriage th={th} />}
        {tab === "tele"   && <Telemedicine th={th} />}
        {tab === "book"   && <BookVisit th={th} />}
      </div>
    </>
  );
}

// ══════════════════════════════════════════════════════════════════════════════
// RECORDS SCREEN
// ══════════════════════════════════════════════════════════════════════════════

function ConsultHistory({ th }) {
  const [expanded, setExpanded] = useState(null);
  return (
    <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
      <div style={{ background: "rgba(46,196,182,0.07)", border: "1px solid rgba(46,196,182,0.15)", borderRadius: "14px", padding: "12px 16px", display: "flex", gap: 12, alignItems: "center" }}>
        <span>📹</span>
        <div><p style={{ margin: 0, fontSize: "13px", fontWeight: 700, color: "#2EC4B6" }}>3 video consults · ₹477 total</p><p style={{ margin: "2px 0 0", fontSize: "11px", color: th.sub }}>Dec 2024 – Feb 2025</p></div>
      </div>
      {CONSULT_HISTORY.map(c => (
        <div key={c.id} onClick={() => setExpanded(expanded === c.id ? null : c.id)} style={{ background: th.card, border: `1px solid ${th.cardBorder}`, borderRadius: "18px", padding: "16px", cursor: "pointer" }}>
          <div style={{ display: "flex", gap: 12, alignItems: "flex-start" }}>
            <div style={{ width: 44, height: 44, borderRadius: "14px", background: th.ghost, display: "flex", alignItems: "center", justifyContent: "center", fontSize: "22px", flexShrink: 0 }}>{c.avatar}</div>
            <div style={{ flex: 1 }}>
              <div style={{ display: "flex", justifyContent: "space-between" }}>
                <p style={{ margin: 0, fontSize: "14px", fontWeight: 700, color: th.text }}>{c.vet}</p>
                <span style={{ color: th.faint, fontSize: "11px" }}>{c.date}</span>
              </div>
              <p style={{ margin: "3px 0 8px", fontSize: "12px", color: th.sub }}>{c.reason}</p>
              <div style={{ display: "flex", gap: 6, flexWrap: "wrap" }}>
                <Badge color="#2EC4B6">📹 Video</Badge>
                <Badge color="#9B5DE5">{c.duration}</Badge>
                <Badge color="#FF6B35">₹{c.fee}</Badge>
                {c.prescription && <Badge color="#F72585">Rx issued</Badge>}
              </div>
            </div>
          </div>
          {expanded === c.id && (
            <div style={{ marginTop: 14, paddingTop: 14, borderTop: `1px solid ${th.divider}` }}>
              <p style={{ margin: "0 0 10px", fontSize: "13px", color: th.sub, lineHeight: 1.6 }}>{c.reason}. Vet advised monitoring for 48 hours and prescribed treatment if symptoms persist.</p>
              {c.prescription && <button style={{ width: "100%", padding: "10px", borderRadius: "12px", border: "1px solid rgba(247,37,133,0.3)", background: "rgba(247,37,133,0.07)", color: "#F72585", fontFamily: "'DM Sans', sans-serif", fontSize: "13px", fontWeight: 700, cursor: "pointer" }}>📄 View Prescription</button>}
            </div>
          )}
        </div>
      ))}
    </div>
  );
}

function VisitHistory({ th }) {
  return (
    <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
      {APPT_HISTORY.map(a => (
        <div key={a.id} style={{ background: th.card, border: `1px solid ${a.done ? th.cardBorder : "rgba(155,93,229,0.25)"}`, borderRadius: "18px", padding: "16px" }}>
          <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 6 }}>
            <div style={{ display: "flex", gap: 8, alignItems: "center" }}>
              <p style={{ margin: 0, fontSize: "14px", fontWeight: 700, color: th.text }}>{a.clinic}</p>
              {!a.done && <Badge color="#9B5DE5">Upcoming</Badge>}
            </div>
            <span style={{ color: th.faint, fontSize: "11px" }}>{a.date}</span>
          </div>
          <p style={{ margin: "0 0 4px", fontSize: "12px", color: th.sub }}>🩺 {a.vet}</p>
          <p style={{ margin: "0 0 10px", fontSize: "12px", color: th.text }}>{a.reason}</p>
          <div style={{ padding: "9px 12px", background: a.done ? "rgba(46,196,182,0.07)" : "rgba(155,93,229,0.07)", border: `1px solid ${a.done ? "rgba(46,196,182,0.18)" : "rgba(155,93,229,0.2)"}`, borderRadius: "10px" }}>
            <p style={{ margin: 0, fontSize: "11px", color: a.done ? "#2EC4B6" : "#9B5DE5", fontWeight: 600 }}>{a.outcome}</p>
          </div>
        </div>
      ))}
    </div>
  );
}

function DocumentsTab({ th }) {
  const [filter, setFilter] = useState("All");
  const types    = ["All", "Prescription", "Blood Report", "Vaccination Card", "Health Cert", "Travel Cert"];
  const filtered = filter === "All" ? DOCUMENTS : DOCUMENTS.filter(d => d.type === filter);
  return (
    <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
      <div style={{ display: "flex", gap: 8, overflowX: "auto", scrollbarWidth: "none", paddingBottom: 4 }}>
        {types.map(t => <Pill key={t} label={t} active={filter === t} onClick={() => setFilter(t)} th={th} />)}
      </div>
      {filtered.map(doc => (
        <div key={doc.id} style={{ display: "flex", alignItems: "center", gap: 14, background: th.card, border: `1px solid ${th.cardBorder}`, borderRadius: "16px", padding: "14px 16px" }}>
          <div style={{ width: 46, height: 46, borderRadius: "14px", background: `${doc.color}18`, border: `1px solid ${doc.color}30`, display: "flex", alignItems: "center", justifyContent: "center", fontSize: "22px", flexShrink: 0 }}>{doc.icon}</div>
          <div style={{ flex: 1 }}>
            <p style={{ margin: 0, fontSize: "14px", fontWeight: 600, color: th.text }}>{doc.label}</p>
            <p style={{ margin: "2px 0 0", fontSize: "11px", color: th.sub }}>{doc.type} · {doc.date}</p>
          </div>
          <button style={{ padding: "7px 14px", borderRadius: "10px", border: `1px solid ${doc.color}40`, background: `${doc.color}12`, color: doc.color, fontFamily: "'DM Sans', sans-serif", fontSize: "12px", fontWeight: 700, cursor: "pointer" }}>View</button>
        </div>
      ))}
      <div style={{ border: `1.5px dashed ${th.dashed}`, borderRadius: "16px", padding: "20px", textAlign: "center", cursor: "pointer" }}>
        <p style={{ margin: "0 0 4px", fontSize: "24px" }}>📤</p>
        <p style={{ margin: "0 0 4px", fontSize: "13px", fontWeight: 700, color: th.sub }}>Upload a document</p>
        <p style={{ margin: 0, fontSize: "11px", color: th.faint }}>Prescriptions, blood reports, certificates</p>
      </div>
    </div>
  );
}

function RecordsScreen({ th }) {
  const [tab, setTab] = useState("consults");
  const tabs = [{ id: "consults", label: "Consults" }, { id: "visits", label: "Visits" }, { id: "documents", label: "Docs" }];
  return (
    <>
      <div style={{ padding: "16px 24px 0", display: "flex", justifyContent: "space-between", alignItems: "center" }}>
        <div><p style={{ margin: 0, color: th.sub, fontSize: 12 }}>Bruno · Full history</p><h1 style={{ margin: "3px 0 0", fontFamily: "'Syne', sans-serif", fontSize: 26, fontWeight: 800, color: th.text }}>Records 📋</h1></div>
        <div style={{ width: 44, height: 44, borderRadius: "12px", background: th.ghost, border: `1px solid ${th.cardBorder}`, display: "flex", alignItems: "center", justifyContent: "center", fontSize: 18, cursor: "pointer" }}>🔍</div>
      </div>
      <div style={{ padding: "16px 24px 0" }}><TabBar tabs={tabs} active={tab} onChange={setTab} th={th} /></div>
      <div style={{ flex: 1, overflowY: "auto", scrollbarWidth: "none", padding: "16px 24px 24px" }}>
        {tab === "consults"  && <ConsultHistory th={th} />}
        {tab === "visits"    && <VisitHistory th={th} />}
        {tab === "documents" && <DocumentsTab th={th} />}
      </div>
    </>
  );
}

// ══════════════════════════════════════════════════════════════════════════════
// PROFILE SCREEN
// ══════════════════════════════════════════════════════════════════════════════

function PetsTab({ activePet, setActivePetId, th }) {
  return (
    <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
      <p style={{ margin: "0 0 4px", fontSize: "12px", color: th.sub }}>Tap a pet to switch the active profile on Home.</p>
      {PETS.map(pet => (
        <div key={pet.id} onClick={() => setActivePetId(pet.id)} style={{ background: activePet.id === pet.id ? `${pet.color}10` : th.card, border: `1.5px solid ${activePet.id === pet.id ? pet.color + "55" : th.cardBorder}`, borderRadius: "20px", padding: "16px", cursor: "pointer", transition: "all 0.2s" }}>
          <div style={{ display: "flex", gap: 14, alignItems: "center" }}>
            <div style={{ width: 58, height: 58, borderRadius: "18px", background: `linear-gradient(135deg, ${pet.color}, #F72585)`, display: "flex", alignItems: "center", justifyContent: "center", fontSize: "30px", flexShrink: 0, boxShadow: activePet.id === pet.id ? `0 6px 20px ${pet.color}44` : "none" }}>{pet.emoji}</div>
            <div style={{ flex: 1 }}>
              <div style={{ display: "flex", gap: 8, alignItems: "center", marginBottom: 4, flexWrap: "wrap" }}>
                <p style={{ margin: 0, fontSize: "16px", fontWeight: 800, color: th.text, fontFamily: "'Syne', sans-serif" }}>{pet.name}</p>
                {activePet.id === pet.id && <Badge color={pet.color}>Active</Badge>}
                {pet.neutered && <Badge color="#2EC4B6">Neutered ✓</Badge>}
              </div>
              <p style={{ margin: "0 0 6px", fontSize: "12px", color: th.sub }}>{pet.breed} · {pet.age} · {pet.gender} · {pet.weight}</p>
              <Badge color={pet.status === "Healthy" ? "#2EC4B6" : "#FF6B35"}>{pet.status}</Badge>
            </div>
            <span style={{ color: th.faint, fontSize: "20px" }}>›</span>
          </div>
        </div>
      ))}
      <div style={{ border: `1.5px dashed ${th.dashed}`, borderRadius: "20px", padding: "20px", textAlign: "center", cursor: "pointer" }}>
        <p style={{ margin: "0 0 4px", fontSize: "26px" }}>➕</p>
        <p style={{ margin: 0, fontSize: "13px", fontWeight: 700, color: th.faint }}>Add a new pet</p>
      </div>
    </div>
  );
}

function CertsTab({ th }) {
  const certs = [
    { icon: "🏥", label: "Health Certificate",          sub: "Issued Dec 2024 · Paws & Care Clinic",   color: "#9B5DE5", valid: true,  expires: "Dec 2025" },
    { icon: "✈️", label: "Fit to Fly — Delhi to Goa",   sub: "Issued Dec 12, 2024 · Dr. Priya Sharma", color: "#FFD60A", valid: true,  expires: "Mar 2025" },
    { icon: "💉", label: "Vaccination Certificate",      sub: "Updated Jan 2025 · DHPPiL + Rabies",     color: "#2EC4B6", valid: true,  expires: "Jan 2026" },
    { icon: "📋", label: "Travel Permit — Maharashtra",  sub: "Application pending",                    color: "#FF6B35", valid: false, expires: null       },
  ];
  return (
    <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
      <div style={{ background: th.row, borderRadius: "14px", padding: "12px 16px" }}>
        <p style={{ margin: 0, fontSize: "12px", color: th.sub, lineHeight: 1.6 }}>Certificates are issued by your vet. Share as PDF or show QR at checkpoints.</p>
      </div>
      {certs.map(cert => (
        <div key={cert.label} style={{ background: th.card, border: `1px solid ${cert.valid ? th.cardBorder : "rgba(255,107,53,0.2)"}`, borderRadius: "18px", padding: "16px" }}>
          <div style={{ display: "flex", gap: 14, alignItems: "center" }}>
            <div style={{ width: 50, height: 50, borderRadius: "14px", background: `${cert.color}18`, border: `1px solid ${cert.color}30`, display: "flex", alignItems: "center", justifyContent: "center", fontSize: "24px", flexShrink: 0 }}>{cert.icon}</div>
            <div style={{ flex: 1 }}>
              <p style={{ margin: 0, fontSize: "14px", fontWeight: 700, color: th.text }}>{cert.label}</p>
              <p style={{ margin: "2px 0 6px", fontSize: "11px", color: th.sub }}>{cert.sub}</p>
              {cert.valid ? <Badge color="#2EC4B6">Valid until {cert.expires}</Badge> : <Badge color="#FF6B35">Pending</Badge>}
            </div>
          </div>
          {cert.valid && (
            <div style={{ display: "flex", gap: 8, marginTop: 12 }}>
              <button style={{ flex: 1, padding: "9px", borderRadius: "12px", border: `1px solid ${cert.color}40`, background: `${cert.color}12`, color: cert.color, fontFamily: "'DM Sans', sans-serif", fontSize: "12px", fontWeight: 700, cursor: "pointer" }}>📄 Download PDF</button>
              <button style={{ flex: 1, padding: "9px", borderRadius: "12px", border: `1px solid ${th.cardBorder}`, background: th.ghost, color: th.sub, fontFamily: "'DM Sans', sans-serif", fontSize: "12px", fontWeight: 700, cursor: "pointer" }}>QR Code</button>
            </div>
          )}
        </div>
      ))}
      <button style={{ width: "100%", padding: "14px", borderRadius: "16px", border: "1px solid rgba(155,93,229,0.3)", background: "rgba(155,93,229,0.08)", color: "#9B5DE5", fontFamily: "'DM Sans', sans-serif", fontSize: "14px", fontWeight: 700, cursor: "pointer" }}>
        + Request New Certificate from Vet
      </button>
    </div>
  );
}

function SettingsTab({ isDark, toggleTheme, th }) {
  const [notifs,  setNotifs]  = useState(true);
  const [summary, setSummary] = useState(true);
  const [share,   setShare]   = useState(false);

  const Toggle = ({ label, sub, val, set }) => (
    <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", padding: "13px 16px", background: th.row, borderRadius: "14px" }}>
      <div><p style={{ margin: 0, fontSize: "13px", fontWeight: 600, color: th.text }}>{label}</p>{sub && <p style={{ margin: "2px 0 0", fontSize: "11px", color: th.faint }}>{sub}</p>}</div>
      <div onClick={() => set(!val)} style={{ width: 44, height: 26, borderRadius: "13px", background: val ? "linear-gradient(135deg, #FF6B35, #F72585)" : th.toggleOff, padding: "3px", display: "flex", alignItems: "center", justifyContent: val ? "flex-end" : "flex-start", cursor: "pointer", transition: "all 0.2s" }}>
        <div style={{ width: 20, height: 20, borderRadius: "50%", background: "#fff", boxShadow: "0 1px 4px rgba(0,0,0,0.2)" }} />
      </div>
    </div>
  );

  return (
    <div style={{ display: "flex", flexDirection: "column", gap: 10 }}>
      {/* Profile */}
      <div style={{ background: th.card, border: `1px solid ${th.cardBorder}`, borderRadius: "20px", padding: "16px", display: "flex", gap: 14, alignItems: "center" }}>
        <div style={{ width: 56, height: 56, borderRadius: "18px", background: "linear-gradient(135deg, #FF6B35, #F72585)", display: "flex", alignItems: "center", justifyContent: "center", fontSize: "28px" }}>👩</div>
        <div style={{ flex: 1 }}>
          <p style={{ margin: 0, fontSize: "16px", fontWeight: 800, color: th.text, fontFamily: "'Syne', sans-serif" }}>Priya Sharma</p>
          <p style={{ margin: "2px 0 0", fontSize: "12px", color: th.sub }}>priya@email.com · Delhi</p>
        </div>
        <button style={{ padding: "7px 14px", borderRadius: "10px", border: `1px solid ${th.cardBorder}`, background: "transparent", color: th.sub, fontFamily: "'DM Sans', sans-serif", fontSize: "12px", fontWeight: 600, cursor: "pointer" }}>Edit</button>
      </div>

      {/* Theme toggle — PROMINENT */}
      <div style={{ background: th.card, border: `1px solid ${th.cardBorder}`, borderRadius: "16px", padding: "16px", display: "flex", alignItems: "center", justifyContent: "space-between" }}>
        <div style={{ display: "flex", alignItems: "center", gap: 12 }}>
          <span style={{ fontSize: "20px" }}>{isDark ? "🌙" : "☀️"}</span>
          <div>
            <p style={{ margin: 0, fontSize: "13px", fontWeight: 700, color: th.text }}>{isDark ? "Dark mode" : "Light mode"}</p>
            <p style={{ margin: "2px 0 0", fontSize: "11px", color: th.faint }}>Tap to switch</p>
          </div>
        </div>
        <div onClick={toggleTheme} style={{ width: 44, height: 26, borderRadius: "13px", background: isDark ? "linear-gradient(135deg, #FF6B35, #F72585)" : th.toggleOff, padding: "3px", display: "flex", alignItems: "center", justifyContent: isDark ? "flex-end" : "flex-start", cursor: "pointer", transition: "all 0.3s" }}>
          <div style={{ width: 20, height: 20, borderRadius: "50%", background: "#fff", boxShadow: "0 1px 4px rgba(0,0,0,0.2)", transition: "all 0.3s" }} />
        </div>
      </div>

      <SL th={th} style={{ marginTop: 4 }}>Notifications</SL>
      <Toggle label="Care reminders"    sub="Meds, walks, feeding alerts"      val={notifs}  set={setNotifs}  />
      <Toggle label="Daily summary"     sub="Morning digest of Bruno's tasks"  val={summary} set={setSummary} />
      <Toggle label="Share health data" sub="Anonymous, improves AI triage"    val={share}   set={setShare}   />

      <SL th={th} style={{ marginTop: 4 }}>Account</SL>
      {[["🔒","Change password"],["📍","Manage locations"],["💳","Subscription · Pro Plan"],["📞","Contact support"],["📄","Privacy policy"]].map(([icon, label]) => (
        <div key={label} style={{ display: "flex", alignItems: "center", gap: 14, padding: "13px 16px", background: th.row, borderRadius: "14px", cursor: "pointer" }}>
          <span>{icon}</span><p style={{ margin: 0, fontSize: "13px", fontWeight: 600, color: th.text, flex: 1 }}>{label}</p><span style={{ color: th.faint, fontSize: "16px" }}>›</span>
        </div>
      ))}
      <div style={{ display: "flex", alignItems: "center", gap: 14, padding: "13px 16px", background: "rgba(255,59,48,0.06)", border: "1px solid rgba(255,59,48,0.15)", borderRadius: "14px", cursor: "pointer" }}>
        <span>🚪</span><p style={{ margin: 0, fontSize: "13px", fontWeight: 700, color: "#FF3B30", flex: 1 }}>Log out</p>
      </div>
    </div>
  );
}

function ProfileScreen({ activePet, setActivePetId, isDark, toggleTheme, th }) {
  const [tab, setTab] = useState("pets");
  const tabs = [{ id: "pets", label: "My Pets" }, { id: "certs", label: "Certs" }, { id: "settings", label: "Account" }];
  return (
    <>
      <div style={{ padding: "16px 24px 0" }}>
        <p style={{ margin: 0, color: th.sub, fontSize: 12 }}>Priya Sharma</p>
        <h1 style={{ margin: "3px 0 0", fontFamily: "'Syne', sans-serif", fontSize: 26, fontWeight: 800, color: th.text }}>Profile 🐾</h1>
      </div>
      <div style={{ padding: "16px 24px 0" }}><TabBar tabs={tabs} active={tab} onChange={setTab} th={th} /></div>
      <div style={{ flex: 1, overflowY: "auto", scrollbarWidth: "none", padding: "16px 24px 24px" }}>
        {tab === "pets"     && <PetsTab activePet={activePet} setActivePetId={setActivePetId} th={th} />}
        {tab === "certs"    && <CertsTab th={th} />}
        {tab === "settings" && <SettingsTab isDark={isDark} toggleTheme={toggleTheme} th={th} />}
      </div>
    </>
  );
}

// ══════════════════════════════════════════════════════════════════════════════
// ROOT
// ══════════════════════════════════════════════════════════════════════════════

export default function Uiapp() {
  const [activeNav,   setActiveNav]   = useState("home");
  const [activePetId, setActivePetId] = useState(1);
  const [isDark,      setIsDark]      = useState(true);

  const th         = isDark ? THEMES.dark : THEMES.light;
  const activePet  = PETS.find(p => p.id === activePetId);
  const toggleTheme = () => setIsDark(d => !d);

  const nav = [
    { id: "home",    icon: "🏠",  label: "Home"   },
    { id: "health",  icon: "❤️‍🩹", label: "Health" },
    { id: "records", icon: "📋",  label: "Records" },
    { id: "profile", icon: "🐾",  label: "Profile" },
  ];

  return (
    <div style={{ display: "flex", justifyContent: "center", alignItems: "center", minHeight: "100vh", background: th.wallpaper, fontFamily: "'DM Sans', sans-serif", padding: 20, transition: "background 0.4s" }}>
      <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet" />

      {/* Theme toggle floating pill — quick access outside phone */}
      <div onClick={toggleTheme} style={{ position: "fixed", top: 24, right: 24, background: isDark ? "rgba(255,255,255,0.1)" : "rgba(0,0,0,0.1)", backdropFilter: "blur(12px)", border: isDark ? "1px solid rgba(255,255,255,0.15)" : "1px solid rgba(0,0,0,0.12)", borderRadius: "20px", padding: "8px 16px", display: "flex", alignItems: "center", gap: 8, cursor: "pointer", zIndex: 10 }}>
        <span style={{ fontSize: "16px" }}>{isDark ? "☀️" : "🌙"}</span>
        <span style={{ fontSize: "12px", fontWeight: 700, color: isDark ? "rgba(255,255,255,0.7)" : "rgba(0,0,0,0.6)" }}>{isDark ? "Light mode" : "Dark mode"}</span>
      </div>

      <div style={{ width: 390, height: 844, background: th.shell, borderRadius: 52, overflow: "hidden", boxShadow: isDark ? "0 0 0 12px #1a1a1a, 0 0 0 14px #333, 0 60px 120px rgba(0,0,0,0.8)" : "0 0 0 12px #e0e0e0, 0 0 0 14px #ccc, 0 40px 80px rgba(0,0,0,0.2)", display: "flex", flexDirection: "column", transition: "all 0.3s" }}>

        {/* Status bar */}
        <div style={{ padding: "14px 28px 0", display: "flex", justifyContent: "space-between", alignItems: "center", color: th.status, fontSize: 12, fontWeight: 500, flexShrink: 0, position: "relative" }}>
          <span>9:41</span>
          <div style={{ width: 120, height: 30, background: th.shell, borderRadius: 20, position: "absolute", top: 0, left: "50%", transform: "translateX(-50%)" }} />
          <div style={{ display: "flex", gap: 5 }}><span>●●●</span><span>📶</span><span>🔋</span></div>
        </div>

        {/* Screen */}
        <div style={{ flex: 1, display: "flex", flexDirection: "column", overflow: "hidden" }}>
          {activeNav === "home"    && <HomeScreen    setActiveNav={setActiveNav} activePet={activePet} th={th} />}
          {activeNav === "health"  && <HealthScreen  th={th} />}
          {activeNav === "records" && <RecordsScreen th={th} />}
          {activeNav === "profile" && <ProfileScreen activePet={activePet} setActivePetId={setActivePetId} isDark={isDark} toggleTheme={toggleTheme} th={th} />}
        </div>

        {/* Bottom nav */}
        <div style={{ padding: "12px 24px 28px", background: th.navBg, backdropFilter: "blur(20px)", borderTop: `1px solid ${th.navBorder}`, display: "flex", justifyContent: "space-around", alignItems: "center", flexShrink: 0 }}>
          {nav.map(t => (
            <div key={t.id} onClick={() => setActiveNav(t.id)} style={{ display: "flex", flexDirection: "column", alignItems: "center", gap: 4, cursor: "pointer", padding: "8px 14px", borderRadius: 14, background: activeNav === t.id ? "rgba(255,107,53,0.1)" : "transparent", transition: "all 0.2s" }}>
              <span style={{ fontSize: 22, filter: activeNav === t.id ? "none" : "grayscale(1) opacity(0.4)" }}>{t.icon}</span>
              <span style={{ fontSize: 10, fontWeight: 700, color: activeNav === t.id ? "#FF6B35" : th.faint }}>{t.label}</span>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
