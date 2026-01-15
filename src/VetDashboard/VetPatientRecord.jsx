import React, { useEffect, useMemo, useState } from "react";
import { useAuth } from "../auth/AuthContext";
import axios from "axios";

const API_BASE =
  import.meta.env.VITE_BACKEND_API || "https://snoutiq.com/backend/api";

const formatDateTime = (value) => {
  if (!value) return "-";
  const date = new Date(value);
  return isNaN(date.getTime()) ? value : date.toLocaleString();
};

const formatDate = (value) => {
  if (!value) return "-";
  const date = new Date(value);
  return isNaN(date.getTime()) ? value : date.toLocaleDateString();
};

const getPrimaryPet = (patient) => {
  const pets = Array.isArray(patient?.pets) ? patient.pets : [];
  return pets.length ? pets[0] : null;
};

const VetPatientRecord = () => {
  const { user } = useAuth();
  const [patients, setPatients] = useState([]);
  const [doctors, setDoctors] = useState([]);
  const [records, setRecords] = useState([]);
  const [selectedPatient, setSelectedPatient] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [loading, setLoading] = useState({ patients: false, records: false });
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [recordForm, setRecordForm] = useState({
    doctor_id: "",
    notes: "",
    file: null,
  });
  const selectedPet = getPrimaryPet(selectedPatient);
  const selectedPetName = selectedPet?.name || selectedPet?.pet_name || null;
  const selectedPetBreed = selectedPet?.breed || null;
  const selectedPetAge = selectedPet?.pet_age ?? selectedPet?.age;
  const selectedPetGender =
    selectedPet?.gender || selectedPet?.pet_gender || null;

  // Modal states
  const [isRecordModalOpen, setIsRecordModalOpen] = useState(false);
  const [isPatientModalOpen, setIsPatientModalOpen] = useState(false);
  const [selectedRecord, setSelectedRecord] = useState(null);

  // Get clinic and doctor IDs
  const { clinicId, defaultDoctorId } = useMemo(() => {
    const getFromStorage = (key) => {
      const raw = localStorage.getItem(key);
      const num = Number(raw);
      return Number.isFinite(num) && num > 0 ? num : null;
    };

    return {
      clinicId:
        user?.clinic_id ||
        user?.vet_registeration_id ||
        user?.vet_id ||
        getFromStorage("clinic_id") ||
        getFromStorage("vet_registeration_id") ||
        getFromStorage("vet_id"),
      defaultDoctorId:
        user?.doctor_id ||
        user?.id ||
        getFromStorage("doctor_id") ||
        getFromStorage("userId"),
    };
  }, [user]);

  // Load patients and doctors
  const loadInitialData = async () => {
    if (!clinicId) {
      setError("Clinic ID not found. Please sign in again.");
      return;
    }

    setLoading((prev) => ({ ...prev, patients: true }));
    setError("");

    try {
      const [patientsRes, doctorsRes] = await Promise.all([
        axios.get(`${API_BASE}/clinics/${clinicId}/patients`, {
          withCredentials: true,
        }),
        axios.get(`${API_BASE}/clinics/${clinicId}/doctors`, {
          withCredentials: true,
        }),
      ]);

      setPatients(patientsRes.data?.patients || []);
      setDoctors(doctorsRes.data?.doctors || []);

      // Set default doctor if available
      if (defaultDoctorId) {
        setRecordForm((prev) => ({
          ...prev,
          doctor_id: String(defaultDoctorId),
        }));
      }
    } catch (err) {
      setError(
        err.response?.data?.message || err.message || "Failed to load data"
      );
      setPatients([]);
      setDoctors([]);
    } finally {
      setLoading((prev) => ({ ...prev, patients: false }));
    }
  };

  // Load medical records for selected patient
  const loadRecords = async (patientId) => {
    if (!clinicId || !patientId) return;

    setLoading((prev) => ({ ...prev, records: true }));
    try {
      const res = await axios.get(
        `${API_BASE}/users/${patientId}/medical-records`,
        {
          params: { clinic_id: clinicId },
          withCredentials: true,
        }
      );

      const data = res.data;
      const recordsList = data?.data?.records || data?.records || [];
      setRecords(recordsList);
    } catch (err) {
      setError(
        err.response?.data?.message || err.message || "Failed to load records"
      );
      setRecords([]);
    } finally {
      setLoading((prev) => ({ ...prev, records: false }));
    }
  };

  // Handle patient selection
  const handleSelectPatient = (patient) => {
    setSelectedPatient(patient);
    setRecords([]);
    loadRecords(patient.id);
    setIsPatientModalOpen(true);
  };

  // Handle view record details
  const handleViewRecord = (record) => {
    setSelectedRecord(record);
    setIsRecordModalOpen(true);
  };

  // Handle file upload - UPDATED
  const handleUpload = async (e) => {
    e.preventDefault();

    if (!clinicId || !selectedPatient?.id) {
      setError("Clinic or patient information missing");
      return;
    }

    if (!recordForm.file) {
      setError("Please select a file to upload");
      return;
    }

    setUploading(true);
    setError("");
    setSuccess("");

    try {
      const formData = new FormData();
      formData.append("user_id", selectedPatient.id);
      formData.append("clinic_id", clinicId);
      formData.append("record_file", recordForm.file);

      if (recordForm.doctor_id)
        formData.append("doctor_id", recordForm.doctor_id);
      if (recordForm.notes) formData.append("notes", recordForm.notes);

      await axios.post(`${API_BASE}/medical-records`, formData, {
        withCredentials: true,
        headers: { "Content-Type": "multipart/form-data" },
      });

      // Success handling
      setSuccess("Medical record uploaded successfully!");
      
      // Reset form
      setRecordForm({
        doctor_id: String(defaultDoctorId || ""),
        notes: "",
        file: null,
      });
      
      // Reset file input
      const fileInput = document.querySelector('input[type="file"]');
      if (fileInput) fileInput.value = '';
      
      // Reload records without closing modal
      await loadRecords(selectedPatient.id);
      
      // Auto hide success message after 3 seconds
      setTimeout(() => setSuccess(""), 3000);
      
    } catch (err) {
      setError(err.response?.data?.message || err.message || "Upload failed");
      setSuccess("");
    } finally {
      setUploading(false);
    }
  };

  // File input handler - UPDATED
  const handleFileChange = (e) => {
    const file = e.target.files[0];
    setRecordForm((prev) => ({ ...prev, file }));
  };

  // Close modals - UPDATED
  const closePatientModal = () => {
    setIsPatientModalOpen(false);
    setSelectedPatient(null);
    setRecords([]);
    setSuccess("");
    // Reset form to default state
    setRecordForm({
      doctor_id: String(defaultDoctorId || ""),
      notes: "",
      file: null,
    });
  };

  const closeRecordModal = () => {
    setIsRecordModalOpen(false);
    setSelectedRecord(null);
  };

  // Initialize data
  useEffect(() => {
    if (clinicId) {
      loadInitialData();
    }
  }, [clinicId]);

  return (
    <div className="min-h-screen bg-gray-50 py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="mb-8">
          <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
              <h1 className="text-3xl font-bold text-gray-900">
                Patient Medical Records
              </h1>
              <p className="mt-2 text-sm text-gray-600">
                Manage patient records and medical documents for your clinic
              </p>
            </div>
            <div className="flex items-center gap-4">
              <div className="text-sm text-gray-700">
                Clinic:{" "}
                <span className="font-semibold">
                  {clinicId ? `#${clinicId}` : "Not available"}
                </span>
              </div>
              <button
                onClick={loadInitialData}
                disabled={loading.patients}
                className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
              >
                <svg
                  className={`-ml-1 mr-2 h-4 w-4 ${
                    loading.patients ? "animate-spin" : ""
                  }`}
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke="currentColor"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                  />
                </svg>
                {loading.patients ? "Loading..." : "Refresh"}
              </button>
            </div>
          </div>
        </div>

        {/* Error Alert */}
        {error && (
          <div className="mb-6 rounded-md bg-red-50 p-4">
            <div className="flex">
              <div className="flex-shrink-0">
                <svg
                  className="h-5 w-5 text-red-400"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                >
                  <path
                    fillRule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clipRule="evenodd"
                  />
                </svg>
              </div>
              <div className="ml-3">
                <h3 className="text-sm font-medium text-red-800">Error</h3>
                <div className="mt-1 text-sm text-red-700">{error}</div>
              </div>
            </div>
          </div>
        )}

        {/* Success Alert */}
        {success && (
          <div className="mb-6 rounded-md bg-green-50 p-4">
            <div className="flex">
              <div className="flex-shrink-0">
                <svg
                  className="h-5 w-5 text-green-400"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                >
                  <path
                    fillRule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clipRule="evenodd"
                  />
                </svg>
              </div>
              <div className="ml-3">
                <h3 className="text-sm font-medium text-green-800">Success</h3>
                <div className="mt-1 text-sm text-green-700">{success}</div>
              </div>
            </div>
          </div>
        )}

        {/* Patients List */}
        <div className="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
          <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 className="text-lg leading-6 font-medium text-gray-900">
              Clinic Patients
            </h3>
            <p className="mt-1 text-sm text-gray-500">
              {patients.length} patients found in your clinic
            </p>
          </div>

          {loading.patients ? (
            <div className="px-4 py-12 text-center">
              <div className="animate-pulse space-y-4">
                {[1, 2, 3].map((i) => (
                  <div key={i} className="flex space-x-4">
                    <div className="h-12 bg-gray-200 rounded flex-1"></div>
                  </div>
                ))}
              </div>
            </div>
          ) : patients.length === 0 ? (
            <div className="px-4 py-12 text-center">
              <svg
                className="mx-auto h-12 w-12 text-gray-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={1}
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                />
              </svg>
              <h3 className="mt-2 text-sm font-medium text-gray-900">
                No patients
              </h3>
              <p className="mt-1 text-sm text-gray-500">
                No patients found for this clinic.
              </p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th
                      scope="col"
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                    >
                      Patient Information
                    </th>
                    <th
                      scope="col"
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                    >
                      Pet Details
                    </th>
                    <th
                      scope="col"
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                    >
                      Medical Records
                    </th>
                    <th
                      scope="col"
                      className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                    >
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {patients.map((patient) => {
                    const primaryPet = getPrimaryPet(patient);
                    const hasPet = Boolean(primaryPet);
                    const petName =
                      primaryPet?.name ||
                      primaryPet?.pet_name ||
                      "No pets on file";
                    const petBreed = hasPet
                      ? primaryPet?.breed || "Unknown breed"
                      : "-";
                    const petAge = primaryPet?.pet_age ?? primaryPet?.age;
                    const petAgeLabel = hasPet
                      ? petAge || petAge === 0
                        ? `${petAge} years`
                        : "Age unknown"
                      : "-";
                    const petGender = hasPet
                      ? primaryPet?.gender ||
                        primaryPet?.pet_gender ||
                        "Unknown gender"
                      : "-";
                    return (
                      <tr key={patient.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex items-center">
                          <div className="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <span className="text-blue-600 font-medium text-sm">
                              {(patient.name || "U")[0].toUpperCase()}
                            </span>
                          </div>
                          <div className="ml-4">
                            <div className="text-sm font-semibold text-gray-900">
                              {patient.name || "Unnamed Patient"}
                              {patient.email && (
                                <span className="ml-2 text-xs text-gray-400 font-normal">
                                  {patient.email}
                                </span>
                              )}
                            </div>
                            <div className="text-sm text-gray-500">
                              {patient.phone || "No phone"}
                            </div>
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-gray-900">
                          {petName}
                        </div>
                        <div className="text-sm text-gray-500">
                          {petBreed}
                        </div>
                        <div className="text-sm text-gray-500">
                          {petAgeLabel} • {petGender}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex items-center">
                          <div
                            className={`h-3 w-3 rounded-full ${
                              (patient.records_count || 0) > 0
                                ? "bg-green-500"
                                : "bg-gray-300"
                            }`}
                          ></div>
                          <div className="ml-2">
                            <div className="text-sm font-medium text-gray-900">
                              {patient.records_count || 0} records
                            </div>
                            <div className="text-sm text-gray-500">
                              Last: {formatDateTime(patient.last_record_at)}
                            </div>
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button
                          onClick={() => handleSelectPatient(patient)}
                          className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                        >
                          <svg
                            className="w-4 h-4 mr-2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                          >
                            <path
                              strokeLinecap="round"
                              strokeLinejoin="round"
                              strokeWidth={2}
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                          </svg>
                          View Records
                        </button>
                      </td>
                    </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </div>

        {/* Patient Records Modal */}
        {isPatientModalOpen && selectedPatient && (
          <div className="fixed inset-0 overflow-y-auto z-50">
            <div className="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
              <div
                className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                onClick={closePatientModal}
              ></div>

              <div className="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">
                <div className="absolute top-0 right-0 pt-4 pr-4">
                  <button
                    type="button"
                    className="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    onClick={closePatientModal}
                  >
                    <span className="sr-only">Close</span>
                    <svg
                      className="h-6 w-6"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M6 18L18 6M6 6l12 12"
                      />
                    </svg>
                  </button>
                </div>

                <div className="sm:flex sm:items-start">
                  <div className="mt-3 text-center sm:mt-0 sm:text-left w-full">
                    <div className="flex items-center mb-6">
                      <div className="flex-shrink-0 h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <span className="text-blue-600 font-semibold text-lg">
                          {(selectedPatient.name || "U")[0].toUpperCase()}
                        </span>
                      </div>
                      <div className="ml-4">
                        <h3 className="text-2xl leading-6 font-bold text-gray-900">
                          {selectedPatient.name}
                        </h3>
                        <p className="mt-1 text-sm text-gray-500">
                          Patient ID: #{selectedPatient.id} •{" "}
                          {selectedPatient.email}
                        </p>
                      </div>
                    </div>

                    {/* Patient Details Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                      <div className="bg-gray-50 p-4 rounded-lg">
                        <h4 className="text-sm font-medium text-gray-500 mb-2">
                          Patient Information
                        </h4>
                        <div className="space-y-1">
                          <p className="text-sm">
                            <span className="font-medium">Name:</span>{" "}
                            {selectedPatient.name || "Not provided"}
                          </p>
                          <p className="text-sm">
                            <span className="font-medium">Email:</span>{" "}
                            {selectedPatient.email || "Not provided"}
                          </p>
                          <p className="text-sm">
                            <span className="font-medium">Phone:</span>{" "}
                            {selectedPatient.phone || "Not provided"}
                          </p>
                        </div>
                      </div>

                      <div className="bg-gray-50 p-4 rounded-lg">
                        <h4 className="text-sm font-medium text-gray-500 mb-2">
                          Pet Information
                        </h4>
                        <div className="space-y-1">
                          <p className="text-sm">
                            <span className="font-medium">Pet Name:</span>{" "}
                            {selectedPetName || "Not provided"}
                          </p>
                          <p className="text-sm">
                            <span className="font-medium">Breed:</span>{" "}
                            {selectedPetBreed || "Not provided"}
                          </p>
                          <p className="text-sm">
                            <span className="font-medium">Age:</span>{" "}
                            {selectedPetAge || selectedPetAge === 0
                              ? `${selectedPetAge} years`
                              : "Not provided"}
                          </p>
                          <p className="text-sm">
                            <span className="font-medium">Gender:</span>{" "}
                            {selectedPetGender || "Not provided"}
                          </p>
                        </div>
                      </div>
                    </div>

                    {/* Upload Form */}
                    <div className="border-t border-gray-200 pt-6 mb-8">
                      <h4 className="text-lg font-medium text-gray-900 mb-4">
                        Upload New Record
                      </h4>
                      <form onSubmit={handleUpload} className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                          <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                              Attending Doctor
                            </label>
                            <select
                              value={recordForm.doctor_id}
                              onChange={(e) =>
                                setRecordForm((prev) => ({
                                  ...prev,
                                  doctor_id: e.target.value,
                                }))
                              }
                              className="block w-full rounded-md border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                            >
                              <option value="">Select Doctor</option>
                              {doctors.map((doctor) => (
                                <option key={doctor.id} value={doctor.id}>
                                  {doctor.name ||
                                    doctor.doctor_name ||
                                    `Doctor #${doctor.id}`}
                                </option>
                              ))}
                            </select>
                          </div>

                          <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                              Clinical Notes
                            </label>
                            <input
                              type="text"
                              value={recordForm.notes}
                              onChange={(e) =>
                                setRecordForm((prev) => ({
                                  ...prev,
                                  notes: e.target.value,
                                }))
                              }
                              placeholder="Enter clinical notes..."
                              className="block w-full rounded-md border border-gray-300 py-2 px-3 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                            />
                          </div>

                          <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                              Medical File
                            </label>
                            <input
                              type="file"
                              onChange={handleFileChange}
                              className="
    block w-full cursor-pointer
    rounded-md border border-gray-300 bg-white
    text-sm text-gray-500 shadow-sm
    focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
    file:mr-4 file:py-2 file:px-4
    file:rounded-md file:border-0
    file:text-sm file:font-semibold
    file:bg-blue-50 file:text-blue-700
    hover:file:bg-blue-100
  "
                              required
                            />
                          </div>
                        </div>

                        <div className="flex justify-end">
                          <button
                            type="submit"
                            disabled={uploading}
                            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                          >
                            {uploading ? (
                              <>
                                <svg
                                  className="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                  fill="none"
                                  viewBox="0 0 24 24"
                                >
                                  <circle
                                    className="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    strokeWidth="4"
                                  ></circle>
                                  <path
                                    className="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                  ></path>
                                </svg>
                                Uploading...
                              </>
                            ) : (
                              "Upload Record"
                            )}
                          </button>
                        </div>
                      </form>
                    </div>

                    {/* Records List */}
                    <div>
                      <h4 className="text-lg font-medium text-gray-900 mb-4">
                        Medical Records ({records.length})
                      </h4>

                      {loading.records ? (
                        <div className="animate-pulse space-y-3">
                          {[1, 2, 3].map((i) => (
                            <div
                              key={i}
                              className="h-16 bg-gray-200 rounded"
                            ></div>
                          ))}
                        </div>
                      ) : records.length === 0 ? (
                        <div className="text-center py-8 border-2 border-dashed border-gray-300 rounded-lg">
                          <svg
                            className="mx-auto h-12 w-12 text-gray-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                          >
                            <path
                              strokeLinecap="round"
                              strokeLinejoin="round"
                              strokeWidth={1}
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                          </svg>
                          <h3 className="mt-2 text-sm font-medium text-gray-900">
                            No records
                          </h3>
                          <p className="mt-1 text-sm text-gray-500">
                            No medical records found for this patient.
                          </p>
                        </div>
                      ) : (
                        <div className="space-y-3 max-h-96 overflow-y-auto">
                          {records.map((record) => (
                            <div
                              key={record.id}
                              className="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                            >
                              <div className="flex-1 min-w-0">
                                <div className="flex items-center justify-between">
                                  <div className="flex items-center">
                                    <svg
                                      className="h-5 w-5 text-gray-400 mr-3"
                                      fill="none"
                                      viewBox="0 0 24 24"
                                      stroke="currentColor"
                                    >
                                      <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                      />
                                    </svg>
                                    <h5 className="text-sm font-medium text-gray-900 truncate">
                                      {record.file_name || "Medical Record"}
                                    </h5>
                                  </div>
                                  <div className="flex items-center space-x-2 ml-4">
                                    <button
                                      onClick={() => handleViewRecord(record)}
                                      className="inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                      View Details
                                    </button>
                                    {record.url && (
                                      <a
                                        href={record.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200"
                                      >
                                        Download
                                      </a>
                                    )}
                                  </div>
                                </div>
                                <p className="text-sm text-gray-500 mt-1">
                                  Uploaded {formatDateTime(record.uploaded_at)}
                                  {record.doctor_id &&
                                    ` by Doctor #${record.doctor_id}`}
                                </p>
                                {record.notes && (
                                  <p className="text-sm text-gray-700 mt-2 line-clamp-2">
                                    {record.notes}
                                  </p>
                                )}
                              </div>
                            </div>
                          ))}
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Record Details Modal */}
        {isRecordModalOpen && selectedRecord && (
          <div className="fixed inset-0 overflow-y-auto z-50">
            <div className="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
              <div
                className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                onClick={closeRecordModal}
              ></div>

              <div className="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div className="absolute top-0 right-0 pt-4 pr-4">
                  <button
                    type="button"
                    className="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    onClick={closeRecordModal}
                  >
                    <span className="sr-only">Close</span>
                    <svg
                      className="h-6 w-6"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M6 18L18 6M6 6l12 12"
                      />
                    </svg>
                  </button>
                </div>

                <div className="sm:flex sm:items-start">
                  <div className="mt-3 text-center sm:mt-0 sm:text-left w-full">
                    <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                      Record Details
                    </h3>

                    <div className="space-y-4">
                      <div>
                        <label className="block text-sm font-medium text-gray-500">
                          File Name
                        </label>
                        <p className="mt-1 text-sm text-gray-900">
                          {selectedRecord.file_name || "Medical Record"}
                        </p>
                      </div>

                      <div>
                        <label className="block text-sm font-medium text-gray-500">
                          Upload Date
                        </label>
                        <p className="mt-1 text-sm text-gray-900">
                          {formatDateTime(selectedRecord.uploaded_at)}
                        </p>
                      </div>

                      {selectedRecord.doctor_id && (
                        <div>
                          <label className="block text-sm font-medium text-gray-500">
                            Doctor
                          </label>
                          <p className="mt-1 text-sm text-gray-900">
                            Doctor #{selectedRecord.doctor_id}
                          </p>
                        </div>
                      )}

                      {selectedRecord.notes && (
                        <div>
                          <label className="block text-sm font-medium text-gray-500">
                            Clinical Notes
                          </label>
                          <p className="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                            {selectedRecord.notes}
                          </p>
                        </div>
                      )}

                      {selectedRecord.url && (
                        <div className="pt-4">
                          <a
                            href={selectedRecord.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                          >
                            <svg
                              className="w-4 h-4 mr-2"
                              fill="none"
                              stroke="currentColor"
                              viewBox="0 0 24 24"
                            >
                              <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                              />
                            </svg>
                            Download File
                          </a>
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default VetPatientRecord;
