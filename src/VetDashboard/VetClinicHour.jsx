import React, { useContext, useEffect, useMemo, useState } from "react";
import { AuthContext } from "../auth/AuthContext";

const DAYS = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

const makeDayRow = (dow) => ({
  dow,
  active: true,
  start_time: "10:00",
  end_time: "18:00",
  break_start_time: "",
  break_end_time: "",
});

const VetClinicHour = () => {
  const [doctorIdInput, setDoctorIdInput] = useState("");
  const [doctorList, setDoctorList] = useState([]);
  const [loadingDoctors, setLoadingDoctors] = useState(false);
  const [doctor_id_new, setDoctor_Id_New] = useState("");
  const {user} = useContext(AuthContext)
  
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

  const [schedule, setSchedule] = useState(DAYS.map((_, i) => makeDayRow(i)));
  const [price, setPrice] = useState("");
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState("");

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
  }, [clinicId]);

  const notify = (text) => {
    setMessage(text);
    setTimeout(() => setMessage(""), 3200);
  };

  const normalizeDay = (entry) => ({
    dow: Number(entry.day_of_week ?? entry.dow ?? entry.day ?? 0),
    active: Boolean(entry.is_active ?? entry.active ?? false),
    start_time: entry.start_time || "",
    end_time: entry.end_time || "",
    break_start_time: entry.break_start || entry.break_start_time || "",
    break_end_time: entry.break_end || entry.break_end_time || "",
  });

  const fetchDoctors = async () => {
    if (!clinicId) return;
    setLoadingDoctors(true);
    try {
      const response = await fetch(`https://snoutiq.com/backend/api/clinics/${clinicId}/doctors`);
      const data = await response.json();
      const docs = data?.doctors || data?.data || [];
      setDoctorList(docs);
      if (docs.length === 1) {
        setDoctorIdInput(String(docs[0].id));
        localStorage.setItem("doctorId", String(docs[0].id));
      }
    } catch (err) {
      console.error("Failed to load clinic doctors", err);
    } finally {
      setLoadingDoctors(false);
    }
  };

  const fetchAvailability = async () => {
    if (!doctor_id_new) {
      notify("Doctor ID missing");
      return;
    }
    setLoading(true);
    try {
      const response = await fetch(
        `https://snoutiq.com/backend/api/doctors/${doctor_id_new}/availability?service_type=in_clinic`
      );
      
      const res = await response.json();

      const data = Array.isArray(res?.availability)
        ? res.availability
        : Array.isArray(res?.data?.availability)
        ? res.data.availability
        : Array.isArray(res?.data)
        ? res.data
        : res;

      const mapped = Array.isArray(data)
        ? data
            .map(normalizeDay)
            .filter((d) => Number.isFinite(d.dow))
            .reduce((acc, d) => {
              acc[d.dow] = d;
              return acc;
            }, {})
        : {};
      setSchedule((prev) =>
        (prev.length ? prev : DAYS.map((_, i) => makeDayRow(i))).map((row) =>
          mapped[row.dow] ? { ...row, ...mapped[row.dow] } : row
        )
      );
      notify("Schedule loaded");
    } catch (err) {
      console.error("Failed to load availability", err);
      notify("Unable to load schedule");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDoctors();
  }, [clinicId]);

  useEffect(() => {
    if (doctor_id_new) {
      fetchAvailability();
    }
  }, [doctor_id_new]);

  const updateDay = (dow, field, value) => {
    setSchedule((prev) =>
      prev.map((row) =>
        row.dow === dow ? { ...row, [field]: field === "active" ? Boolean(value) : value } : row
      )
    );
  };

  const handleSave = async () => {
    if (!doctor_id_new) return notify("Doctor ID missing");
    setSaving(true);
    try {
      const payload = schedule.map((row) => ({
        day_of_week: row.dow,
        start_time: row.start_time,
        end_time: row.end_time,
        break_start: row.break_start_time,
        break_end: row.break_end_time,
        is_active: row.active ? 1 : 0,
      }));
      
      await fetch(`https://snoutiq.com/backend/api/doctors/${doctor_id_new}/availability`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      
      notify("Schedule saved");
    } catch (err) {
      console.error("Save failed", err);
      notify("Failed to save schedule");
    } finally {
      setSaving(false);
    }
  };

  const handlePriceUpdate = async () => {
    if (!doctor_id_new) return notify("Doctor ID missing");
    const amount = Number(price);
    if (Number.isNaN(amount)) return notify("Enter a valid price");
    try {
      await fetch(`https://snoutiq.com/backend/api/doctors/${doctor_id_new}/price`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ price: amount })
      });
      notify("Price updated");
    } catch (err) {
      console.error("Price update failed", err);
      notify("Failed to update price");
    }
  };

  return (
    <div className="min-h-screen bg-slate-50">
      <div className="max-w-6xl mx-auto px-4 py-8 space-y-6">
        <header className="flex items-center justify-between">
          <div>
            <p className="text-sm text-slate-500">Clinic hours</p>
            <h1 className="text-2xl font-semibold text-slate-900">
              Weekly clinic schedule
            </h1>
          </div>
          {message && (
            <span className="text-sm bg-white border border-slate-200 px-3 py-1 rounded-lg text-slate-700">
              {message}
            </span>
          )}
        </header>

        <section className="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 space-y-4">
          <div className="grid gap-4 md:grid-cols-4">
            <div className="space-y-2">
              <label className="text-xs font-semibold text-slate-600 uppercase">
                Doctor
              </label>
              {doctorList.length > 0 ? (
                <select
                  value={doctorIdInput || ""}
                  onChange={(e) => {
                    setDoctorIdInput(e.target.value);
                    setDoctor_Id_New(e.target.value);
                    localStorage.setItem("doctorId", e.target.value);
                  }}
                  className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900"
                >
                  <option value="">Select doctor</option>
                  {doctorList.map((d) => (
                    <option key={d.id} value={d.id}>
                      {d.name || d.doctor_name || `Doctor #${d.id}`}
                    </option>
                  ))}
                </select>
              ) : (
                <input
                  value={doctorIdInput}
                  onChange={(e) => {
                    setDoctorIdInput(e.target.value);
                    setDoctor_Id_New(e.target.value);
                    localStorage.setItem("doctorId", e.target.value);
                  }}
                  placeholder={
                    loadingDoctors
                      ? "Loading doctors..."
                      : doctor_id_new
                      ? String(doctor_id_new)
                      : "Enter doctor ID"
                  }
                  className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900"
                />
              )}
              {clinicId && (
                <p className="text-[11px] text-slate-500">
                  Clinic ID: {clinicId} {doctor_id_new && `| Doctor ID: ${doctor_id_new}`}
                </p>
              )}
            </div>
            <div>
              <label className="text-xs font-semibold text-slate-600 uppercase">
                Consultation price (₹)
              </label>
              <div className="flex gap-2">
                <input
                  type="number"
                  value={price}
                  onChange={(e) => setPrice(e.target.value)}
                  className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900"
                  placeholder="e.g. 700"
                />
                <button
                  type="button"
                  onClick={handlePriceUpdate}
                  disabled={!hasDoctorId}
                  className="mt-1 px-3 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-black transition disabled:opacity-50"
                >
                  Update
                </button>
              </div>
            </div>
            <div className="md:col-span-2 flex items-end justify-end gap-2">
              <button
                type="button"
                onClick={fetchAvailability}
                disabled={!hasDoctorId || loading}
                className="px-4 py-2 rounded-xl border border-slate-200 text-slate-800 text-sm font-semibold hover:bg-slate-50 disabled:opacity-50"
              >
                {loading ? "Loading..." : "Refresh"}
              </button>
              <button
                type="button"
                onClick={handleSave}
                disabled={!hasDoctorId || saving}
                className="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition disabled:opacity-50"
              >
                {saving ? "Saving..." : "Save schedule"}
              </button>
            </div>
          </div>
        </section>

        <section className="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-lg font-semibold text-slate-900">
                Weekly clinic schedule
              </h2>
              <p className="text-sm text-slate-500">
                Set start/end times and optional breaks for each day.
              </p>
            </div>
          </div>

          <div className="space-y-3">
            {schedule.map((row) => (
              <div
                key={row.dow}
                className="border border-slate-200 rounded-xl p-4 bg-slate-50/70"
              >
                <div className="flex flex-wrap items-center justify-between gap-3 mb-3">
                  <div className="flex items-center gap-2">
                    <span className="text-base font-semibold text-slate-900">
                      {DAYS[row.dow]}
                    </span>
                    {!row.active && (
                      <span className="text-xs px-2 py-1 rounded-full bg-amber-100 text-amber-700 border border-amber-200">
                        Inactive
                      </span>
                    )}
                  </div>
                  <div className="flex items-center gap-2">
                    <label className="text-xs font-medium text-slate-600">
                      Active
                    </label>
                    <input
                      type="checkbox"
                      checked={row.active}
                      onChange={(e) => updateDay(row.dow, "active", e.target.checked)}
                      className="h-4 w-4 accent-emerald-600"
                    />
                  </div>
                </div>

                <div className="grid gap-3 md:grid-cols-4">
                  <div>
                    <label className="text-xs font-semibold text-slate-600 uppercase">
                      Start time
                    </label>
                    <input
                      type="time"
                      value={row.start_time}
                      onChange={(e) => updateDay(row.dow, "start_time", e.target.value)}
                      className="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 bg-white"
                    />
                  </div>
                  <div>
                    <label className="text-xs font-semibold text-slate-600 uppercase">
                      End time
                    </label>
                    <input
                      type="time"
                      value={row.end_time}
                      onChange={(e) => updateDay(row.dow, "end_time", e.target.value)}
                      className="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 bg-white"
                    />
                  </div>
                  <div>
                    <label className="text-xs font-semibold text-slate-600 uppercase">
                      Break start
                    </label>
                    <input
                      type="time"
                      value={row.break_start_time}
                      onChange={(e) => updateDay(row.dow, "break_start_time", e.target.value)}
                      className="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 bg-white"
                    />
                  </div>
                  <div>
                    <label className="text-xs font-semibold text-slate-600 uppercase">
                      Break end
                    </label>
                    <input
                      type="time"
                      value={row.break_end_time}
                      onChange={(e) => updateDay(row.dow, "break_end_time", e.target.value)}
                      className="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 bg-white"
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

export default VetClinicHour;
