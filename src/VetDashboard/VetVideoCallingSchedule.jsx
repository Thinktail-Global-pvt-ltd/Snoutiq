import React, { useEffect, useMemo, useState,useContext } from "react";
import axios from "../axios";
import { AuthContext } from "../auth/AuthContext";

const DAYS = [
  "Sunday",
  "Monday",
  "Tuesday",
  "Wednesday",
  "Thursday",
  "Friday",
  "Saturday",
];

const makeDayRow = (dow) => ({
  dow,
  active: false,
  start_time: "",
  end_time: "",
  break_start_time: "",
  break_end_time: "",
});

const VetVideoCallingSchedule = () => {

  const [doctorIdInput, setDoctorIdInput] = useState("");
 const {user} = useContext(AuthContext)
   const [doctor_id_new, setDoctor_Id_New] = useState("");
  
  // Mock user data - replace with actual useAuth
//   const user = {
//     doctor_id: null,
//     id: null,
//     clinic_id: 123,
//     vet_registeration_id: null
//   };

  const doctorId = useMemo(() => {
    return (
      Number(doctorIdInput) ||
      user?.doctor_id ||
      user?.id ||
      Number(localStorage.getItem("doctorId")) ||
      null
    );
  }, [doctorIdInput, user]);

  const clinicId = useMemo(() => {
    return (
      user?.clinic_id ||
      user?.vet_registeration_id ||
      Number(localStorage.getItem("clinic_id")) ||
      null
    );
  }, [user]);

  


  // ===========================================================
  // FETCH REAL DOCTOR ID FROM CLINIC
  // clinicId = doctorId (from login screen)
  // API → /clinics/{clinicId}/doctors → returns real doctorIds
  // ===========================================================
  const hasDoctorId = !!doctor_id_new;

  // Fetch real doctor ID from clinic
  useEffect(() => {
    const getRealDoctorId = async () => {
      if (!doctorId) return;

      try {
        const response = await fetch(
          `https://snoutiq.com/backend/api/clinics/${doctorId}/doctors`
        );
        const data = await response.json();
        const list = data?.doctors || [];

        // If clinic has at least 1 doctor
        if (list.length > 0) {
          const real = list[0].id;
          setDoctor_Id_New(String(real));
        }
      } catch (err) {
        console.error("Clinic doctor fetch failed", err);
      }
    };

    getRealDoctorId();
  }, [doctorId]);

  const [avgMinutes, setAvgMinutes] = useState(20);
  const [maxPerHour, setMaxPerHour] = useState(3);
  const [is247, setIs247] = useState(false);
  const [days, setDays] = useState(DAYS.map((_, i) => makeDayRow(i)));
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);

  const [message, setMessage] = useState("");

  const notify = (text) => {
    setMessage(text);
    setTimeout(() => setMessage(""), 3000);
  };


  const normalizeDay = (entry) => ({
    dow: Number(entry.dow ?? entry.day_of_week ?? entry.day ?? 0),
    active: Boolean(entry.active ?? entry.is_active ?? false),
    start_time: entry.start_time || "",
    end_time: entry.end_time || "",
    break_start_time:
      entry.break_start_time || entry.break_start || "",
    break_end_time:
      entry.break_end_time || entry.break_end || "",
  });

  // ===========================================================
  // LOAD SCHEDULE
  // GET /api/video/schedule/{doctorId}
  // ===========================================================
  const fetchSchedule = async () => {
    const activeDoctorId = doctor_id_new ;
    if (!activeDoctorId) return notify("Doctor ID missing");

    setLoading(true);
    try {
      const res = await axios.get(
        `https://snoutiq.com/backend/api/video/schedule/${activeDoctorId}`
      );
      const data = res?.data || {};
      console.log(res,"ankitwrew");
      

      setAvgMinutes(Number(data.avg_consult_minutes ?? 20));
      setMaxPerHour(Number(data.max_bookings_per_hour ?? 3));
      setIs247(Boolean(data.is_247));

      const mapped = Array.isArray(data.days)
        ? data.days.map(normalizeDay)
        : [];

      const merged = DAYS.map((_, i) => {
        const found = mapped.find((d) => d.dow === i);
        return found ? { ...makeDayRow(i), ...found } : makeDayRow(i);
      });

      setDays(merged);
      notify("Schedule loaded");
    } catch (err) {
      console.error("Load failed", err);
      notify("Failed to load schedule");
    } finally {
      setLoading(false);
    }
  };

  // fetch instantly when doctorId becomes ready
  useEffect(() => {
    if (doctor_id_new || doctorId) fetchSchedule();
  }, [doctor_id_new, doctorId]);

  // ===========================================================
  // Day update
  // ===========================================================
  const updateDay = (dow, field, val) => {
    setDays((prev) =>
      prev.map((row) =>
        row.dow === dow
          ? { ...row, [field]: field === "active" ? Boolean(val) : val }
          : row
      )
    );
  };

  // ===========================================================
  // SAVE SCHEDULE
  // POST /api/video/schedule/{doctorId}
  // ===========================================================
  const handleSave = async () => {
    const activeDoctorId = doctor_id_new ;
    if (!activeDoctorId) return notify("Doctor ID missing");

    setSaving(true);

    try {
      const payload = {
        avg_consult_minutes: Number(avgMinutes) || 20,
        max_bookings_per_hour: Number(maxPerHour) || 3,
        is_247: Boolean(is247),
        days: days.map((row) => ({
          dow: row.dow,
          active: is247 ? true : row.active,
          start_time: is247 ? "00:00" : row.start_time,
          end_time: is247 ? "23:59" : row.end_time,
          break_start_time: is247 ? null : row.break_start_time || null,
          break_end_time: is247 ? null : row.break_end_time || null,
        })),
      };

      await axios.post(
        `https://snoutiq.com/backend/api/video/schedule/${activeDoctorId}`,
        payload
      );

      notify("Schedule saved");
    } catch (err) {
      console.error("Save failed", err);
      notify("Failed to save schedule");
    } finally {
      setSaving(false);
    }
  };

  // ===========================================================
  // TOGGLE 24×7
  // /video/schedule/{doctorId}/toggle-247
  // ===========================================================
  const handleToggle247 = async () => {
    const activeDoctorId = doctor_id_new ;
    if (!activeDoctorId) return notify("Doctor ID missing");

    try {
      const res = await axios.post(
        `https://snoutiq.com/backend/api/video/schedule/${activeDoctorId}/toggle-247`
      );

      setIs247(Boolean(res?.data?.is_247));
      notify("24×7 toggled");

      fetchSchedule();
    } catch (err) {
      console.error("Toggle failed", err);
      notify("Failed to toggle 24×7");
    }
  };

  return (
    <div className="min-h-screen bg-slate-50">
      <div className="max-w-6xl mx-auto px-4 py-8 space-y-6">
        <header className="flex items-center justify-between">
          <div>
            <p className="text-sm text-slate-500">Video Calling Schedule</p>
            <h1 className="text-2xl font-semibold text-slate-900">
              Manage weekly video slots
            </h1>
          </div>

          {message && (
            <span className="text-sm bg-white border border-slate-200 px-3 py-1 rounded-lg text-slate-700">
              {message}
            </span>
          )}
        </header>

        {/* ============================= Doctor Settings ============================= */}
        <section className="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 space-y-4">
          <div className="grid gap-4 md:grid-cols-4">
            <div>
              <label className="text-xs font-semibold text-slate-600 uppercase">
                Doctor ID
              </label>
              <input
                value={doctorIdInput}
                onChange={(e) => setDoctorIdInput(e.target.value)}
                placeholder="Enter doctor ID"
                className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
              />
            </div>

            <div>
              <label className="text-xs font-semibold text-slate-600 uppercase">
                Avg consult (min)
              </label>
              <input
                type="number"
                value={avgMinutes}
                onChange={(e) => setAvgMinutes(e.target.value)}
                className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
              />
            </div>

            <div>
              <label className="text-xs font-semibold text-slate-600 uppercase">
                Max bookings/hr
              </label>
              <input
                type="number"
                value={maxPerHour}
                onChange={(e) => setMaxPerHour(e.target.value)}
                className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
              />
            </div>

            <div className="flex items-end">
              <button
                onClick={handleToggle247}
                className="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700"
              >
                {is247 ? "Disable 24×7" : "Enable 24×7"}
              </button>
            </div>
          </div>

          <div className="flex justify-end gap-2">
            <button
              onClick={fetchSchedule}
              disabled={loading}
              className="px-4 py-2 rounded-xl border border-slate-200 text-slate-800 text-sm"
            >
              {loading ? "Loading..." : "Refresh"}
            </button>

            <button
              onClick={handleSave}
              disabled={saving}
              className="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700"
            >
              {saving ? "Saving..." : "Save schedule"}
            </button>
          </div>
        </section>

        {/* ============================= Weekly Grid ============================= */}
        <section className="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 space-y-4">
          <h2 className="text-lg font-semibold text-slate-900">
            Weekly video schedule
          </h2>

          <div className="space-y-3">
            {days.map((row) => (
              <div
                key={row.dow}
                className="border border-slate-200 rounded-xl p-4 bg-slate-50/70"
              >
                <div className="flex items-center justify-between mb-3">
                  <div className="flex items-center gap-2">
                    <span className="font-semibold">{DAYS[row.dow]}</span>
                    {is247 && (
                      <span className="text-xs px-2 py-1 bg-blue-100 rounded-full border border-blue-300">
                        24×7
                      </span>
                    )}
                  </div>

                  <div className="flex items-center gap-2">
                    <label className="text-xs">Active</label>
                    <input
                      type="checkbox"
                      checked={is247 ? true : row.active}
                      disabled={is247}
                      onChange={(e) =>
                        updateDay(row.dow, "active", e.target.checked)
                      }
                    />
                  </div>
                </div>

                <div className="grid gap-3 md:grid-cols-4">
                  {/* START */}
                  <div>
                    <label className="text-xs">Start time</label>
                    <input
                      type="time"
                      disabled={is247}
                      value={row.start_time}
                      onChange={(e) =>
                        updateDay(row.dow, "start_time", e.target.value)
                      }
                      className="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                    />
                  </div>

                  {/* END */}
                  <div>
                    <label className="text-xs">End time</label>
                    <input
                      type="time"
                      disabled={is247}
                      value={row.end_time}
                      onChange={(e) =>
                        updateDay(row.dow, "end_time", e.target.value)
                      }
                      className="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                    />
                  </div>

                  {/* BREAK START */}
                  <div>
                    <label className="text-xs">Break start</label>
                    <input
                      type="time"
                      disabled={is247}
                      value={row.break_start_time}
                      onChange={(e) =>
                        updateDay(
                          row.dow,
                          "break_start_time",
                          e.target.value
                        )
                      }
                      className="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                    />
                  </div>

                  {/* BREAK END */}
                  <div>
                    <label className="text-xs">Break end</label>
                    <input
                      type="time"
                      disabled={is247}
                      value={row.break_end_time}
                      onChange={(e) =>
                        updateDay(
                          row.dow,
                          "break_end_time",
                          e.target.value
                        )
                      }
                      className="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                    />
                  </div>
                </div>
              </div>
            ))}
          </div>
        </section>
      </div>
    </div>
  );
};

export default VetVideoCallingSchedule;
