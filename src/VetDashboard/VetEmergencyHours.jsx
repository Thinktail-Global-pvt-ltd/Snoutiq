import React, { useEffect, useMemo, useState } from "react";
import axios from "axios";
import { useAuth } from "../auth/AuthContext";


const API_BASE =
  import.meta.env.VITE_BACKEND_API ||"https://snoutiq.com/backend/api";

const SLOT_OPTIONS = [
  "18:00-20:00",
  "20:00-22:00",
  "22:00-00:00",
  "00:00-02:00",
  "02:00-04:00",
  "04:00-06:00",
];

const VetEmergencyHours = () => {
  const { user } = useAuth();
  const [doctors, setDoctors] = useState([]);
  const [selectedDoctors, setSelectedDoctors] = useState(new Set());
  const [selectedSlots, setSelectedSlots] = useState(new Set());
  const [price, setPrice] = useState("");
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");

  const fromStorage = (key) => {
    if (typeof window === "undefined") return null;
    const raw = localStorage.getItem(key);
    const num = Number(raw);
    return Number.isFinite(num) && num > 0 ? num : null;
  };

  const clinicId = useMemo(() => {
    return (
      user?.clinic_id ||
      user?.vet_registeration_id ||
      user?.vet_id ||
      fromStorage("clinic_id") ||
      fromStorage("vet_registeration_id") ||
      fromStorage("vet_id") ||
      null
    );
  }, [user]);

  const fetchDoctors = async () => {
    if (!clinicId) return;
    try {
      const res = await axios.get(`${API_BASE}/clinics/${clinicId}/doctors`, {
        withCredentials: true,
      });
      const list = Array.isArray(res?.data?.doctors) ? res.data.doctors : [];
      setDoctors(list);
    } catch (err) {
      console.warn("Failed to load doctors", err);
    }
  };

  const loadExisting = async () => {
    if (!clinicId) {
      setError("Clinic ID not found");
      return;
    }
    setLoading(true);
    setError("");
    try {
      const res = await axios.get(`${API_BASE}/clinic/emergency-hours`, {
        params: { clinic_id: clinicId },
        withCredentials: true,
      });
      const data = res?.data?.data || {};
      
      const docIds = new Set(
        (Array.isArray(data.doctor_ids) ? data.doctor_ids : []).map(Number)
      );
      const slots = new Set(
        Array.isArray(data.night_slots) ? data.night_slots : []
      );
      setSelectedDoctors(docIds);
      setSelectedSlots(slots);
      if (data.consultation_price !== undefined && data.consultation_price !== null) {
        setPrice(String(data.consultation_price));
      }
    } catch (err) {
      setError(err?.response?.data?.message || err?.message || "Failed to load emergency hours");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (clinicId) {
      fetchDoctors();
      loadExisting();
    }
  }, [clinicId]);

  const toggleDoctor = (id) => {
    setSelectedDoctors((prev) => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  };

  const toggleSlot = (slot) => {
    setSelectedSlots((prev) => {
      const next = new Set(prev);
      if (next.has(slot)) next.delete(slot);
      else next.add(slot);
      return next;
    });
  };

  const save = async () => {
    if (!clinicId) {
      setError("Clinic ID not found");
      return;
    }
    if (!selectedDoctors.size || !selectedSlots.size || !price) {
      setError("Select at least one doctor, one slot, and a price");
      return;
    }
    setSaving(true);
    setError("");
    try {
      await axios.post(
        `${API_BASE}/clinic/emergency-hours`,
        {
          clinic_id: clinicId,
          doctor_ids: Array.from(selectedDoctors),
          night_slots: Array.from(selectedSlots),
          consultation_price: Number(price),
        },
        {
          withCredentials: true,
          headers: { "Content-Type": "application/json" },
        }
      );
    } catch (err) {
      setError(err?.response?.data?.message || err?.message || "Failed to save");
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div className="bg-white shadow-sm ring-1 ring-gray-200/60 rounded-2xl p-6 space-y-4">
          <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div>
              <h2 className="text-xl font-semibold text-gray-900">
                Night Emergency Coverage
              </h2>
              <p className="text-sm text-gray-600 mt-1">
                Tag doctors and night slots for emergency routing.
              </p>
            </div>
            <div className="flex flex-wrap items-center gap-2 text-xs">
              <span className="px-2 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200">
                Emergency only
              </span>
              <span className="px-2 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                IST
              </span>
            </div>
          </div>

          {error && (
            <div className="rounded-md border border-rose-200 bg-rose-50 text-rose-700 px-4 py-3 text-sm">
              {error}
            </div>
          )}

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <section className="lg:col-span-2 space-y-5">
              <div>
                <div className="flex items-center justify-between mb-2">
                  <h3 className="text-sm font-semibold text-gray-800">
                    Doctors covering emergencies
                  </h3>
                  <span className="text-xs text-gray-500">
                    {selectedDoctors.size} selected
                  </span>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                  {doctors.map((doc) => {
                    const active = selectedDoctors.has(doc.id);
                    return (
                      <label
                        key={doc.id}
                        className={`flex items-center gap-3 p-3 rounded-xl border transition ${
                          active
                            ? "border-rose-500 bg-rose-50 text-rose-900 shadow-sm"
                            : "border-gray-200 bg-white text-gray-800 hover:border-rose-300"
                        }`}
                      >
                        <input
                          type="checkbox"
                          checked={active}
                          onChange={() => toggleDoctor(doc.id)}
                          className="h-4 w-4 text-rose-600 border-gray-300 rounded focus:ring-rose-500"
                        />
                        <span className="text-sm font-medium">
                          {doc.name || doc.doctor_name || `Doctor #${doc.id}`}
                        </span>
                      </label>
                    );
                  })}
                </div>
              </div>

              <div>
                <h3 className="text-sm font-semibold text-gray-800 mb-2">
                  Night emergency slots
                </h3>
                <p className="text-xs text-gray-500 mb-3">
                  Choose the time blocks you monitor (IST).
                </p>
                <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
                  {SLOT_OPTIONS.map((slot) => {
                    const active = selectedSlots.has(slot);
                    return (
                      <button
                        key={slot}
                        type="button"
                        onClick={() => toggleSlot(slot)}
                        className={`px-3 py-2 rounded-xl border text-sm font-medium transition ${
                          active
                            ? "border-rose-500 bg-rose-500 text-white shadow"
                            : "border-gray-200 bg-white text-gray-700 hover:border-rose-300 hover:text-rose-700"
                        }`}
                      >
                        {slot}
                      </button>
                    );
                  })}
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label
                    htmlFor="consultationPrice"
                    className="block text-sm font-medium text-gray-700"
                  >
                    Emergency consultation price (Rs)
                  </label>
                  <input
                    id="consultationPrice"
                    type="number"
                    min="0"
                    step="50"
                    value={price}
                    onChange={(e) => setPrice(e.target.value)}
                    className="mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="1200"
                  />
                </div>
              </div>
            </section>

            <aside className="space-y-4">
              <div className="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <div className="flex items-center justify-between">
                  <h3 className="text-sm font-semibold text-gray-800">
                    Saved configuration
                  </h3>
                  <span className="text-[11px] text-gray-500">
                    {loading ? "Loading..." : "Current"}
                  </span>
                </div>
                <dl className="mt-3 space-y-3 text-sm">
                  <div>
                    <dt className="text-xs uppercase tracking-wide text-gray-500">
                      Doctors
                    </dt>
                    <dd className="mt-0.5 text-gray-800">
                      {selectedDoctors.size
                        ? Array.from(selectedDoctors)
                            .map(
                              (id) =>
                                doctors.find((d) => d.id === id)?.name ||
                                doctors.find((d) => d.id === id)?.doctor_name ||
                                `Doctor #${id}`
                            )
                            .join(", ")
                        : "Not set"}
                    </dd>
                  </div>
                  <div>
                    <dt className="text-xs uppercase tracking-wide text-gray-500">
                      Night slots
                    </dt>
                    <dd className="mt-0.5 text-gray-800">
                      {selectedSlots.size
                        ? Array.from(selectedSlots).join(", ")
                        : "Not set"}
                    </dd>
                  </div>
                  <div>
                    <dt className="text-xs uppercase tracking-wide text-gray-500">
                      Consultation price
                    </dt>
                    <dd className="mt-0.5 text-gray-800">
                      {price ? `Rs. ${price}` : "Not set"}
                    </dd>
                  </div>
                </dl>
              </div>
            </aside>
          </div>

          <div className="flex items-center justify-between pt-4 border-t border-gray-200">
            <p className="text-xs text-gray-500">
              Changes are saved for the whole clinic. Team members tagged here
              receive night alerts.
            </p>
            <button
              onClick={save}
              disabled={saving}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold shadow disabled:opacity-60"
            >
              {saving ? "Saving..." : "Save emergency coverage"}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default VetEmergencyHours;
