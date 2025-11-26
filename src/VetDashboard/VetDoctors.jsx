import React, { useContext, useEffect, useMemo, useState } from "react";
import { AuthContext } from "../auth/AuthContext";

const VetDoctors = () => {
  const { user } = useContext(AuthContext);

  const [doctorIdInput, setDoctorIdInput] = useState("");
  const [doctorIdReal, setDoctorIdReal] = useState("");
  const [doctors, setDoctors] = useState([]);
  const [clinic, setClinic] = useState(null);
  const [loading, setLoading] = useState(false);

  // (Optional) doctorId – agar kahi aur use karna ho
  const doctorId = useMemo(() => {
    return (
      Number(doctorIdInput) ||
      user?.doctor_id ||
      user?.id ||
      Number(localStorage.getItem("doctorId")) ||
      null
    );
  }, [doctorIdInput, user]);

  // ✅ sahi clinicId
  const clinicId = useMemo(() => {
    return (
      user?.clinic_id ||
      user?.vet_registeration_id ||
      Number(localStorage.getItem("clinic_id")) ||
      null
    );
  }, [user]);

  useEffect(() => {
    const getClinicDoctors = async () => {
      if (!clinicId) return;

      try {
        setLoading(true);
        const response = await fetch(
          `https://snoutiq.com/backend/api/clinics/${clinicId}/doctors`
        );
        const data = await response.json();

        const list = data?.doctors || [];
        setDoctors(list);
        setClinic(data?.clinic || null);

        if (list.length > 0) {
          setDoctorIdReal(String(list[0].id));
        }
      } catch (err) {
        console.error("Clinic doctor fetch failed", err);
      } finally {
        setLoading(false);
      }
    };

    getClinicDoctors();
  }, [clinicId]);

  console.log({ doctorId, clinicId, doctorIdReal, doctors });

  return (
    <section className="w-full px-6 py-6">
      <div className="mb-4 flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-semibold text-slate-900">Doctors</h2>
          {clinic && (
            <p className="mt-1 text-sm text-slate-500">
              Clinic: <span className="font-medium">{clinic.name}</span>
            </p>
          )}
        </div>

        {/* Optional: manual doctor/clinic id input for debugging */}
        {/* <div className="flex gap-2">
          <input
            type="number"
            value={doctorIdInput}
            onChange={(e) => setDoctorIdInput(e.target.value)}
            placeholder="Doctor ID (optional)"
            className="h-9 rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
          />
        </div> */}
      </div>

      {loading && (
        <div className="rounded-xl border border-slate-100 bg-white p-4 text-sm text-slate-500 shadow-sm">
          Loading doctors…
        </div>
      )}

      {!loading && doctors.length === 0 && (
        <div className="rounded-xl border border-slate-100 bg-white p-4 text-sm text-slate-500 shadow-sm">
          No doctors found for this clinic.
        </div>
      )}

      <div className="mt-4 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        {doctors.map((doc, i) => {
          // Dummy data – baad me API se bind kar sakte ho
          const nextSlot = ["13:00", "14:00", "15:30"][i % 3];
          const price = [800, 600, 750][i % 3];
          const isOnline = i % 2 === 1;
          const loadPercent = [40, 70, 55][i % 3];

          return (
            <div
              key={doc.id}
              className="relative flex h-full flex-col rounded-2xl border border-slate-100 bg-white p-5 shadow-[0_8px_30px_rgba(15,23,42,0.06)]"
            >
              {/* Left green line like screenshot */}
              <div className="absolute left-0 top-3 h-[85%] w-1 rounded-r-full bg-emerald-400" />

              {/* Header */}
              <div className="flex items-center gap-3">
                <div className="relative h-14 w-14 overflow-hidden rounded-full border border-slate-100 bg-sky-50">
                  <div className="flex h-full w-full items-center justify-center text-lg font-semibold text-sky-700">
                    {doc.name?.charAt(0) || "D"}
                  </div>
                  {/* Online dot */}
                  <span className="absolute bottom-1 right-1 h-3 w-3 rounded-full border-2 border-white bg-emerald-400" />
                </div>

                <div className="flex-1">
                  <h3 className="text-base font-semibold text-slate-900">
                    {doc.name}
                  </h3>
                  <p className="text-xs text-slate-500">
                    {/* Placeholder speciality */}
                    Senior Veterinary Surgeon
                  </p>
                </div>

                <button
                  type="button"
                  className="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-slate-400 transition hover:bg-slate-50"
                >
                  ★
                </button>
              </div>

              {/* Middle: Next slot + Load */}
              <div className="mt-5 flex items-center justify-between gap-4">
                {/* Next slot */}
                <div className="flex-1 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                  <div className="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                    Next Slot
                  </div>
                  <div className="mt-1 text-sm font-semibold text-slate-800">
                    {nextSlot}
                  </div>
                </div>

                {/* Load */}
                <div className="flex-1 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                  <div className="flex items-center justify-between text-[11px] font-medium uppercase tracking-wide text-slate-400">
                    <span>Load</span>
                    <span className="text-[10px] text-slate-500">
                      {loadPercent}%
                    </span>
                  </div>
                  <div className="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-200">
                    <div
                      className="h-full rounded-full bg-emerald-400"
                      style={{ width: `${loadPercent}%` }}
                    />
                  </div>
                </div>
              </div>

              {/* Footer */}
              <div className="mt-6 flex items-center justify-between">
                <div className="flex items-baseline gap-1">
                  <span className="text-lg font-semibold text-slate-900">
                    ₹{price}
                  </span>
                  <span className="text-xs text-slate-500">/ visit</span>
                </div>

                <button
                  type="button"
                  className={`rounded-full px-5 py-2 text-sm font-medium transition ${
                    isOnline
                      ? "bg-emerald-100 text-emerald-700 hover:bg-emerald-200"
                      : "bg-rose-100 text-rose-700 hover:bg-rose-200"
                  }`}
                >
                  {isOnline ? "Go Online" : "Go Offline"}
                </button>
              </div>
            </div>
          );
        })}
      </div>
    </section>
  );
};

export default VetDoctors;
