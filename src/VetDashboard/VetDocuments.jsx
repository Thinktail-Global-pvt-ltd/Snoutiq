import React, { useEffect, useMemo, useState } from "react";
import { useAuth } from "../auth/AuthContext";
import axios from "axios";

const API_BASE = import.meta.env.VITE_BACKEND_API || "https://snoutiq.com/backend/api";

const VetDocuments = () => {
  const { user,doctor_id } = useAuth();
  const [clinic, setClinic] = useState(null);
  const [doctors, setDoctors] = useState([]);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState({ clinic: false, doctors: {} });
  const [error, setError] = useState("");
  const [clinicForm, setClinicForm] = useState({
    license_no: "",
    license_document: null,
  });
  const [doctorForms, setDoctorForms] = useState({});

  // Get clinic ID
  const clinicId = useMemo(() => {
    const getFromStorage = (key) => {
      const raw = localStorage.getItem(key);
      const num = Number(raw);
      return Number.isFinite(num) && num > 0 ? num : null;
    };

    return (
      user?.clinic_id ||
      user?.vet_registeration_id ||
      user?.vet_id ||
      getFromStorage("clinic_id") ||
      getFromStorage("vet_registeration_id") ||
      getFromStorage("vet_id")
    );
  }, [user]);

  // Load clinic and doctors data
  const loadData = async () => {
    if (!clinicId) {
      setError("Clinic ID not found. Please sign in again.");
      return;
    }

    setLoading(true);
    setError("");

    try {
      const [doctorsRes, clinicRes] = await Promise.all([
        axios.get(`${API_BASE}/clinics/${clinicId}/doctors`, {
          withCredentials: true,
        }),
        axios.get(`${API_BASE}/clinics/${doctor_id}`, {
          withCredentials: true,
        }).catch(() => ({ data: { clinic: null } })) // Ignore if clinic endpoint fails
      ]);

      // Set doctors data
      const doctorsList = doctorsRes.data?.doctors || [];
      setDoctors(doctorsList);
      
      // Initialize doctor forms
      setDoctorForms(
        doctorsList.reduce((acc, doctor) => {
          acc[doctor.id] = {
            doctor_license: doctor.doctor_license || "",
            doctor_document: null,
          };
          return acc;
        }, {})
      );

      // Set clinic data if available
      if (clinicRes.data?.clinic) {
        const clinicData = clinicRes.data.clinic;
        setClinic(clinicData);
        setClinicForm(prev => ({
          ...prev,
          license_no: clinicData.license_no || "",
        }));
      }
    } catch (err) {
      setError(err.response?.data?.message || err.message || "Failed to load data");
      setDoctors([]);
      setClinic(null);
    } finally {
      setLoading(false);
    }
  };

  // Handle clinic file upload
  const handleClinicFile = (e) => {
    setClinicForm(prev => ({
      ...prev,
      license_document: e.target.files[0] || null
    }));
  };

  // Handle doctor file upload
  const handleDoctorFile = (doctorId, file) => {
    setDoctorForms(prev => ({
      ...prev,
      [doctorId]: { 
        ...prev[doctorId], 
        doctor_document: file || null 
      }
    }));
  };

  // Handle doctor license input
  const handleDoctorLicense = (doctorId, value) => {
    setDoctorForms(prev => ({
      ...prev,
      [doctorId]: { 
        ...prev[doctorId], 
        doctor_license: value 
      }
    }));
  };

  // Submit clinic documents
  const submitClinic = async (e) => {
    e.preventDefault();
    
    if (!doctor_id) {
      setError("Clinic ID missing");
      return;
    }

    setSaving(prev => ({ ...prev, clinic: true }));
    setError("");

    try {
      const formData = new FormData();
      formData.append("license_no", clinicForm.license_no);
      if (clinicForm.license_document) {
        formData.append("license_document", clinicForm.license_document);
      }

      await axios.post(`${API_BASE}/doctor/documents`, formData, {
        withCredentials: true,
        headers: { "Content-Type": "multipart/form-data" },
      });

      await loadData(); // Reload data
    } catch (err) {
      setError(err.response?.data?.message || err.message || "Failed to save clinic documents");
    } finally {
      setSaving(prev => ({ ...prev, clinic: false }));
    }
  };

  // Submit doctor documents
  const submitDoctor = async (doctorId) => {
    if (!clinicId || !doctorId) return;

    const form = doctorForms[doctorId];
    if (!form) return;

    setSaving(prev => ({ 
      ...prev, 
      doctors: { ...prev.doctors, [doctorId]: true } 
    }));
    setError("");

    try {
      const formData = new FormData();
      formData.append("doctor_license", form.doctor_license || "");
      if (form.doctor_document) {
        formData.append("doctor_document", form.doctor_document);
      }

      await axios.post(`${API_BASE}/doctor/documents/doctors/${doctorId}`, formData, {
        withCredentials: true,
        headers: { "Content-Type": "multipart/form-data" },
      });

      await loadData(); // Reload data
    } catch (err) {
      setError(err.response?.data?.message || err.message || "Failed to save doctor document");
    } finally {
      setSaving(prev => ({ 
        ...prev, 
        doctors: { ...prev.doctors, [doctorId]: false } 
      }));
    }
  };

  // Get file URL for display
  const getFileUrl = (filePath) => {
    if (!filePath) return null;
    if (filePath.startsWith('http')) return filePath;
    return `${window.location.origin}${filePath.startsWith('/') ? '' : '/'}${filePath}`;
  };

  // Initialize data
  useEffect(() => {
    if (clinicId) {
      loadData();
    }
  }, [clinicId]);

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {/* Header */}
        <div className="bg-white rounded-xl shadow border border-gray-200 p-5">
          <h1 className="text-2xl font-bold text-gray-900">
            Documents & Compliance
          </h1>
          <p className="text-sm text-gray-600 mt-1">
            Clinic documents and doctor credentials.
          </p>
          <div className="text-sm text-gray-700 mt-2">
            Clinic ID:{" "}
            <span className="font-semibold">
              {clinicId ? `#${clinicId}` : "Not detected"}
            </span>
          </div>
        </div>

        {/* Error Message */}
        {error && (
          <div className="rounded-md bg-red-50 p-4">
            <div className="flex">
              <div className="flex-shrink-0">
                <svg className="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                </svg>
              </div>
              <div className="ml-3">
                <h3 className="text-sm font-medium text-red-800">Error</h3>
                <div className="mt-1 text-sm text-red-700">{error}</div>
              </div>
            </div>
          </div>
        )}

        {/* Clinic Section */}
        <div className="bg-white rounded-xl shadow border border-gray-200 p-5 space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-xl font-semibold text-gray-900">
                Clinic License & Documents
              </h2>
              <p className="text-sm text-gray-600 mt-1">
                Upload business registration proof and registration number.
              </p>
            </div>
            {clinic?.license_document && (
              <a
                href={getFileUrl(clinic.license_document)}
                target="_blank"
                rel="noreferrer"
                className="text-sm font-semibold text-indigo-600 hover:text-indigo-700"
              >
                View Current File
              </a>
            )}
          </div>

          <form onSubmit={submitClinic} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Business Registration Number *
              </label>
              <input
                type="text"
                required
                value={clinicForm.license_no}
                onChange={(e) => setClinicForm(prev => ({ ...prev, license_no: e.target.value }))}
                className="block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                placeholder="Enter registration number"
              />
            </div>
            
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Upload Registration Proof (PDF/JPG/PNG, max 5MB)
              </label>
              <input
                type="file"
                accept=".pdf,.jpg,.jpeg,.png"
                onChange={handleClinicFile}
                className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
              />
            </div>
            
            <div className="flex justify-end">
              <button
                type="submit"
                disabled={saving.clinic}
                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
              >
                {saving.clinic ? (
                  <>
                    <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Saving...
                  </>
                ) : (
                  "Save Clinic Documents"
                )}
              </button>
            </div>
          </form>
        </div>

        {/* Doctors Section */}
        <div className="bg-white rounded-xl shadow border border-gray-200 p-5 space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-xl font-semibold text-gray-900">
                Doctor Credentials
              </h2>
              <p className="text-sm text-gray-600 mt-1">
                Upload certifications or renewed licenses for each doctor.
              </p>
            </div>
            <span className="text-sm text-gray-500">
              {doctors.length} doctor{doctors.length === 1 ? "" : "s"}
            </span>
          </div>

          {loading ? (
            <div className="text-center py-8">
              <div className="animate-pulse space-y-4">
                {[1, 2, 3].map(i => (
                  <div key={i} className="h-20 bg-gray-200 rounded-lg"></div>
                ))}
              </div>
            </div>
          ) : doctors.length === 0 ? (
            <div className="text-center py-8 border-2 border-dashed border-gray-300 rounded-lg">
              <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <h3 className="mt-2 text-sm font-medium text-gray-900">No doctors</h3>
              <p className="mt-1 text-sm text-gray-500">No doctors found for this clinic.</p>
            </div>
          ) : (
            <div className="space-y-4">
              {doctors.map((doctor) => {
                const form = doctorForms[doctor.id] || { doctor_license: "", doctor_document: null };
                const isSaving = saving.doctors[doctor.id];

                return (
                  <div key={doctor.id} className="border border-gray-200 rounded-lg p-4 space-y-4">
                    <div className="flex items-center justify-between">
                      <div>
                        <div className="font-semibold text-gray-900">
                          {doctor.doctor_name || doctor.name || `Doctor #${doctor.id}`}
                        </div>
                        <div className="text-sm text-gray-500">ID: {doctor.id}</div>
                      </div>
                      {doctor.doctor_document && (
                        <a
                          href={getFileUrl(doctor.doctor_document)}
                          target="_blank"
                          rel="noreferrer"
                          className="text-sm font-semibold text-indigo-600 hover:text-indigo-700"
                        >
                          View Current File
                        </a>
                      )}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                          License Number
                        </label>
                        <input
                          type="text"
                          value={form.doctor_license}
                          onChange={(e) => handleDoctorLicense(doctor.id, e.target.value)}
                          className="block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                          placeholder="License number"
                        />
                      </div>
                      
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                          Upload Document (PDF/JPG/PNG, max 5MB)
                        </label>
                        <input
                          type="file"
                          accept=".pdf,.jpg,.jpeg,.png"
                          onChange={(e) => handleDoctorFile(doctor.id, e.target.files[0])}
                          className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                        />
                      </div>
                      
                      <div className="flex justify-end">
                        <button
                          type="button"
                          onClick={() => submitDoctor(doctor.id)}
                          disabled={isSaving}
                          className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                        >
                          {isSaving ? (
                            <>
                              <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                              </svg>
                              Saving...
                            </>
                          ) : (
                            "Save Doctor"
                          )}
                        </button>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default VetDocuments;