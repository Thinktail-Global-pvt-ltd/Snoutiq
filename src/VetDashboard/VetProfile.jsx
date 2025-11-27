import React, { useEffect, useMemo, useState } from "react";
import axios from "axios";
import { useAuth } from "../auth/AuthContext";

const API_BASE =
  import.meta.env.VITE_BACKEND_API || "https://snoutiq.com/backend/api";

const QR_SERVICE_BASE = "https://api.qrserver.com/v1/create-qr-code/";

const formatCurrency = (value) => {
  const num = Number(value);
  if (!value && value !== 0) return "-";
  if (Number.isNaN(num)) return "-";
  return `Rs. ${num.toLocaleString("en-IN")}`;
};

const VetProfile = () => {
  const { user, doctor_id } = useAuth();

  const [loading, setLoading] = useState(false);
  const [clinic, setClinic] = useState(null);
  const [doctors, setDoctors] = useState([]);
  const [editable, setEditable] = useState({ clinic: false, doctor_ids: [] });
  const [clinicForm, setClinicForm] = useState({});
  const [doctorForms, setDoctorForms] = useState({});
  const [savingClinic, setSavingClinic] = useState(false);
  const [savingDoctorId, setSavingDoctorId] = useState(null);
  const [error, setError] = useState("");

  // ==== Resolve clinic id (like other vet screens) ====
  const resolvedClinicId = useMemo(() => {
    const fromStorage = (key) => {
      const raw = localStorage.getItem(key);
      const num = Number(raw);
      return Number.isFinite(num) && num > 0 ? num : null;
    };

    const id =
      user?.clinic_id ||
      user?.vet_registeration_id ||
      user?.vet_id ||
      fromStorage("clinic_id") ||
      fromStorage("vet_registeration_id") ||
      fromStorage("vet_id") ||
      null;

    const n = Number(id);
    return Number.isFinite(n) && n > 0 ? n : null;
  }, [user]);

  // ==== Resolve doctor id (for doctor role) ====
  const resolvedDoctorId = useMemo(() => {
    let id =
      doctor_id ||
      user?.doctor_id ||
      Number(localStorage.getItem("doctor_id")) ||
      null;

    // Sirf doctor role ke liye fallback user.id use karo
    if (!id && user?.role === "doctor") {
      id = user?.id || null;
    }

    const n = Number(id);
    return Number.isFinite(n) && n > 0 ? n : null;
  }, [doctor_id, user]);

  const loadProfile = async () => {
    setLoading(true);
    setError("");

    const params = {
      doctor_id: resolvedDoctorId || undefined,
      clinic_id: resolvedClinicId || undefined,
      role: user?.role || undefined,
    };

    console.log("VetProfile → request params", params);

    try {
      const res = await axios.get(`${API_BASE}/dashboard/profile`, {
        withCredentials: true,
        params,
        headers: { "Content-Type": "application/json" },
      });

      const data = res?.data || {};
      console.log("VetProfile → response data", data);

      if (data?.success === false) {
        throw new Error(data?.error || data?.message || "Unable to load profile");
      }

      setClinic(data.clinic || null);
      setDoctors(Array.isArray(data?.doctors) ? data.doctors : []);
      setEditable({
        clinic: Boolean(data?.editable?.clinic),
        doctor_ids: Array.isArray(data?.editable?.doctor_ids)
          ? data.editable.doctor_ids.map(Number)
          : [],
      });

      const clinicDefaults = data.clinic || {};
      setClinicForm({
        clinic_profile:
          clinicDefaults.clinic_profile || clinicDefaults.name || "",
        name: clinicDefaults.name || "",
        email: clinicDefaults.email || "",
        mobile: clinicDefaults.mobile || "",
        city: clinicDefaults.city || "",
        pincode: clinicDefaults.pincode || "",
        address: clinicDefaults.address || "",
        license_no: clinicDefaults.license_no || "",
        chat_price: clinicDefaults.chat_price || "",
        bio: clinicDefaults.bio || "",
      });

      const docForms = {};
      (data.doctors || []).forEach((doc) => {
        docForms[doc.id] = {
          doctor_name: doc.doctor_name || "",
          doctor_email: doc.doctor_email || "",
          doctor_mobile: doc.doctor_mobile || "",
          doctor_license: doc.doctor_license || "",
          doctors_price: doc.doctors_price || "",
        };
      });
      setDoctorForms(docForms);
    } catch (err) {
      console.error("VetProfile → loadProfile error", err);
      setError(err?.message || "Unable to load profile");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (!user) return;
    loadProfile();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [resolvedClinicId, resolvedDoctorId, user?.role]);

  const handleClinicChange = (field, value) => {
    setClinicForm((prev) => ({ ...prev, [field]: value }));
  };

  const handleDoctorChange = (id, field, value) => {
    setDoctorForms((prev) => ({
      ...prev,
      [id]: { ...(prev[id] || {}), [field]: value },
    }));
  };

  const onSubmitClinic = async (e) => {
    e.preventDefault();
    if (!editable.clinic) return;
    setSavingClinic(true);
    setError("");
    try {
      const payload = { ...clinicForm };
      const res = await axios.put(
        `${API_BASE}/dashboard/profile/clinic`,
        payload,
        {
          withCredentials: true,
          headers: { "Content-Type": "application/json" },
        }
      );
      const data = res?.data || {};
      if (data?.success === false) {
        throw new Error(data?.error || data?.message || "Update failed");
      }
      await loadProfile();
    } catch (err) {
      console.error("VetProfile → onSubmitClinic error", err);
      setError(err?.message || "Unable to save clinic");
    } finally {
      setSavingClinic(false);
    }
  };

  const onSubmitDoctor = async (id) => {
    if (!editable.doctor_ids.includes(Number(id))) return;
    setSavingDoctorId(id);
    setError("");
    try {
      const payload = { ...doctorForms[id] };
      const res = await axios.put(
        `${API_BASE}/dashboard/profile/doctor/${id}`,
        payload,
        {
          withCredentials: true,
          headers: { "Content-Type": "application/json" },
        }
      );
      const data = res?.data || {};
      if (data?.success === false) {
        throw new Error(data?.error || data?.message || "Update failed");
      }
      await loadProfile();
    } catch (err) {
      console.error("VetProfile → onSubmitDoctor error", err);
      setError(err?.message || "Unable to save doctor");
    } finally {
      setSavingDoctorId(null);
    }
  };

  const clinicPageUrl = useMemo(() => {
    const slug = clinic?.slug;
    if (!slug) return "";
    return `https://snoutiq.com/backend/vets/${encodeURIComponent(slug)}`;
  }, [clinic]);

  const qrSrc = clinicPageUrl
    ? `${QR_SERVICE_BASE}?size=220x220&margin=12&data=${encodeURIComponent(
        clinicPageUrl
      )}`
    : null;

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {/* Top summary card */}
        <div className="bg-white rounded-2xl shadow border border-gray-100 p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <p className="text-xs uppercase tracking-wide text-gray-400">
              Clinic
            </p>
            <h2 className="text-2xl font-semibold text-gray-900 mt-1">
              {clinic?.clinic_profile || clinic?.name || "Clinic"}
            </h2>
            <p className="text-sm text-gray-500 mt-1">
              ID: {clinic?.id ?? "N/A"}
            </p>
          </div>
          <div className="text-right">
            <p className="text-xs uppercase tracking-wide text-gray-400">
              Role
            </p>
            <p className="text-sm font-semibold text-indigo-600">
              {user?.role || "clinic_admin"}
            </p>
            {resolvedDoctorId && (
              <p className="text-xs text-gray-500 mt-1">
                Doctor ID: {resolvedDoctorId}
              </p>
            )}
          </div>
        </div>

        {/* Error banner */}
        {error && (
          <div className="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            {error}
          </div>
        )}

        {/* Clinic form + QR card */}
        <div className="grid gap-4 lg:grid-cols-3">
          {/* Clinic details */}
          <div className="bg-white rounded-2xl shadow border border-gray-100 p-6 lg:col-span-2">
            <div className="flex items-center justify-between mb-4">
              <div>
                <h3 className="text-lg font-semibold text-gray-900">
                  Clinic Details
                </h3>
                <p className="text-sm text-gray-500">
                  These details appear across SnoutIQ.
                </p>
              </div>
            </div>

            {loading ? (
              <div className="text-sm text-gray-600">Loading profile...</div>
            ) : (
              <form
                onSubmit={onSubmitClinic}
                className="grid gap-4 md:grid-cols-2"
              >
                {[
                  ["clinic_profile", "Clinic Profile Title"],
                  ["name", "Registered Name"],
                  ["email", "Email"],
                  ["mobile", "Phone"],
                  ["city", "City"],
                  ["pincode", "Pincode"],
                  ["license_no", "License Number"],
                  ["chat_price", "Consultation Price (Rs)"],
                ].map(([field, label]) => (
                  <div key={field}>
                    <label className="text-sm font-medium text-gray-700">
                      {label}
                    </label>
                    <input
                      value={clinicForm[field] ?? ""}
                      onChange={(e) =>
                        handleClinicChange(field, e.target.value)
                      }
                      disabled={!editable.clinic}
                      className="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-200 disabled:bg-gray-50"
                      type={field === "email" ? "email" : "text"}
                    />
                  </div>
                ))}

                <div className="md:col-span-2">
                  <label className="text-sm font-medium text-gray-700">
                    Address
                  </label>
                  <textarea
                    value={clinicForm.address ?? ""}
                    onChange={(e) =>
                      handleClinicChange("address", e.target.value)
                    }
                    disabled={!editable.clinic}
                    rows={2}
                    className="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-200 disabled:bg-gray-50"
                  />
                </div>

                <div className="md:col-span-2 flex justify-end gap-3">
                  <button
                    type="button"
                    onClick={() => loadProfile()}
                    className="px-4 py-2 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                  >
                    Reset
                  </button>
                  <button
                    type="submit"
                    disabled={!editable.clinic || savingClinic}
                    className="px-5 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 disabled:opacity-60"
                  >
                    {savingClinic ? "Saving..." : "Save changes"}
                  </button>
                </div>
              </form>
            )}
          </div>

          {/* QR card */}
          <div className="rounded-2xl p-6 shadow bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex flex-col gap-4">
            <div>
              <p className="text-xs uppercase tracking-wide text-white/70">
                Quick actions
              </p>
              <h3 className="text-xl font-semibold mt-1">Clinic QR</h3>
              <p className="text-sm text-white/80 mt-2">
                Scan to visit your SnoutIQ clinic profile.
              </p>
            </div>
            <div className="flex justify-center">
              {qrSrc ? (
                <img
                  src={qrSrc}
                  alt="Clinic QR code"
                  className="h-32 w-32 rounded-xl bg-white/20 object-cover"
                />
              ) : (
                <div className="h-32 w-32 rounded-xl bg-white/10 flex items-center justify-center text-sm">
                  QR not ready
                </div>
              )}
            </div>
            {clinicPageUrl ? (
              <a
                href={clinicPageUrl}
                target="_blank"
                rel="noreferrer"
                className="text-sm font-semibold text-white underline"
              >
                View clinic page
              </a>
            ) : (
              <p className="text-xs text-white/80">
                Clinic page not yet available.
              </p>
            )}
          </div>
        </div>

        {/* Doctors list */}
        <div className="bg-white rounded-2xl shadow border border-gray-100">
          <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between px-6 py-4 border-b border-gray-100">
            <div>
              <h3 className="text-lg font-semibold text-gray-900">
                Clinic Doctors
              </h3>
              <p className="text-sm text-gray-500">
                Keep doctor credentials accurate for compliance.
              </p>
            </div>
            <span className="text-sm text-gray-500">
              {doctors.length} doctor{doctors.length === 1 ? "" : "s"}
            </span>
          </div>

          <div className="p-6 grid gap-4 md:grid-cols-2">
            {loading ? (
              <div className="text-sm text-gray-600">Loading doctors...</div>
            ) : doctors.length === 0 ? (
              <div className="text-sm text-gray-600">No doctors found.</div>
            ) : (
              doctors.map((doc) => {
                const canEdit = editable.doctor_ids.includes(Number(doc.id));
                const form = doctorForms[doc.id] || {};
                return (
                  <div
                    key={doc.id}
                    className="border border-gray-100 rounded-2xl p-4 shadow-sm space-y-3"
                  >
                    <div className="flex items-start justify-between gap-3">
                      <div>
                        <p className="text-sm font-semibold text-gray-900">
                          {doc.doctor_name ||
                            doc.name ||
                            `Doctor #${doc.id}`}
                        </p>
                        <p className="text-xs text-gray-500">
                          {doc.doctor_email || "-"}
                        </p>
                      </div>
                      <span
                        className={`text-[11px] px-2 py-1 rounded-full ${
                          canEdit
                            ? "bg-emerald-50 text-emerald-700"
                            : "bg-slate-100 text-slate-600"
                        }`}
                      >
                        {canEdit ? "Editable" : "Read only"}
                      </span>
                    </div>

                    <div className="text-xs text-gray-600 space-y-1">
                      <p>
                        <span className="font-semibold text-gray-700">
                          Phone:
                        </span>{" "}
                        {doc.doctor_mobile || "-"}
                      </p>
                      <p>
                        <span className="font-semibold text-gray-700">
                          License:
                        </span>{" "}
                        {doc.doctor_license || "-"}
                      </p>
                      <p>
                        <span className="font-semibold text-gray-700">
                          Consultation:
                        </span>{" "}
                        {formatCurrency(doc.doctors_price)}
                      </p>
                    </div>

                    {canEdit && (
                      <div className="space-y-2 text-sm">
                        <input
                          className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                          placeholder="Doctor name"
                          value={form.doctor_name || ""}
                          onChange={(e) =>
                            handleDoctorChange(
                              doc.id,
                              "doctor_name",
                              e.target.value
                            )
                          }
                        />
                        <input
                          className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                          placeholder="Email"
                          value={form.doctor_email || ""}
                          onChange={(e) =>
                            handleDoctorChange(
                              doc.id,
                              "doctor_email",
                              e.target.value
                            )
                          }
                          type="email"
                        />
                        <div className="grid grid-cols-2 gap-2">
                          <input
                            className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                            placeholder="Phone"
                            value={form.doctor_mobile || ""}
                            onChange={(e) =>
                              handleDoctorChange(
                                doc.id,
                                "doctor_mobile",
                                e.target.value
                              )
                            }
                          />
                          <input
                            className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                            placeholder="Consultation Price"
                            value={form.doctors_price || ""}
                            onChange={(e) =>
                              handleDoctorChange(
                                doc.id,
                                "doctors_price",
                                e.target.value
                              )
                            }
                            type="number"
                            min="0"
                          />
                        </div>
                        <input
                          className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                          placeholder="License Number"
                          value={form.doctor_license || ""}
                          onChange={(e) =>
                            handleDoctorChange(
                              doc.id,
                              "doctor_license",
                              e.target.value
                            )
                          }
                        />
                        <div className="flex justify-end">
                          <button
                            type="button"
                            onClick={() => onSubmitDoctor(doc.id)}
                            disabled={savingDoctorId === doc.id}
                            className="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 disabled:opacity-60"
                          >
                            {savingDoctorId === doc.id
                              ? "Saving..."
                              : "Save doctor"}
                          </button>
                        </div>
                      </div>
                    )}
                  </div>
                );
              })
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default VetProfile;
