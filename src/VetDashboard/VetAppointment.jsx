import React, { useContext, useEffect, useMemo, useState } from "react";
import { AuthContext } from "../auth/AuthContext";
import axios from "../axios";

const API_BASE = import.meta.env.VITE_BACKEND_API || "https://snoutiq.com/backend/api";

const VetAppointment = () => {
  const { user } = useContext(AuthContext);

  // ================== CLINIC ID RESOLVE ==================
  const clinicId = useMemo(() => {
    const fromStorage = (key) => {
      const raw = localStorage.getItem(key);
      const num = Number(raw);
      return Number.isFinite(num) && num > 0 ? num : null;
    };

    return (
      user?.clinic_id ||
      user?.vet_registeration_id ||
      user?.vet_id ||
      fromStorage("clinic_id") ||
      fromStorage("vet_registeration_id") ||
      fromStorage("vet_id")
    );
  }, [user]);

  // ================== STATE ==================
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [doctorAppointments, setDoctorAppointments] = useState([]);
  const [selectedDoctorId, setSelectedDoctorId] = useState("all");

  // ================== LOAD APPOINTMENTS ==================
  const loadAppointments = async () => {
    if (!clinicId) {
      setError("Clinic ID not found. Please log in again.");
      return;
    }

    setLoading(true);
    setError("");

    try {
      const doctorsRes = await axios.get(
        `${API_BASE}/clinics/${clinicId}/doctors`,
        { withCredentials: true }
      );

      const doctors = doctorsRes?.data?.doctors || [];

      if (!doctors.length) {
        setDoctorAppointments([]);
        return;
      }

      const results = await Promise.allSettled(
        doctors.map(async (doctor) => {
          const res = await axios.get(
            `${API_BASE}/appointments/by-doctor/${doctor.id}`,
            { withCredentials: true }
          );

          const payload = res?.data?.data || {};
          return {
            doctorId: doctor.id,
            doctorName: doctor.name || payload?.doctor?.name || "Unknown Doctor",
            count: payload?.count || 0,
            appointments: payload?.appointments || [],
          };
        })
      );

      const successfulResults = results
        .filter(result => result.status === 'fulfilled')
        .map(result => result.value);

      setDoctorAppointments(successfulResults);
    } catch (err) {
      console.error("Error loading clinic doctors/appointments:", err);
      setError("Failed to load appointments. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  // ================== AUTO LOAD ==================
  useEffect(() => {
    if (clinicId) {
      loadAppointments();
    }
  }, [clinicId]);

  // ================== FILTERED LIST ==================
  const filteredDoctorAppointments = useMemo(() => {
    if (selectedDoctorId === "all") return doctorAppointments;
    return doctorAppointments.filter(
      (doc) => String(doc.doctorId) === String(selectedDoctorId)
    );
  }, [doctorAppointments, selectedDoctorId]);

  // ================== STATS CALCULATION ==================
  const stats = useMemo(() => {
    const totalAppointments = doctorAppointments.reduce((sum, doc) => sum + doc.count, 0);
    const totalDoctors = doctorAppointments.length;
    const confirmedAppointments = doctorAppointments.reduce((sum, doc) => 
      sum + doc.appointments.filter(appt => appt.status === 'confirmed').length, 0
    );

    return { totalAppointments, totalDoctors, confirmedAppointments };
  }, [doctorAppointments]);

  // ================== RENDER LOADING SKELETON ==================
  const renderLoadingSkeleton = () => (
    <div className="space-y-6">
      {[1, 2, 3].map((item) => (
        <div key={item} className="border rounded-lg overflow-hidden shadow-sm animate-pulse">
          <div className="flex items-center justify-between px-6 py-4 bg-gray-50">
            <div className="space-y-2">
              <div className="h-4 bg-gray-200 rounded w-24"></div>
              <div className="h-6 bg-gray-200 rounded w-48"></div>
            </div>
            <div className="text-right space-y-2">
              <div className="h-3 bg-gray-200 rounded w-20"></div>
              <div className="h-6 bg-gray-200 rounded w-12"></div>
            </div>
          </div>
          <div className="p-6 space-y-3">
            {[1, 2, 3].map((row) => (
              <div key={row} className="flex space-x-4">
                <div className="h-4 bg-gray-200 rounded flex-1"></div>
                <div className="h-4 bg-gray-200 rounded flex-1"></div>
                <div className="h-4 bg-gray-200 rounded flex-1"></div>
              </div>
            ))}
          </div>
        </div>
      ))}
    </div>
  );

  // ================== RENDER APPOINTMENT STATUS ==================
  const renderStatusBadge = (status) => {
    const statusConfig = {
      confirmed: { color: "green", label: "Confirmed" },
      rescheduled: { color: "yellow", label: "Rescheduled" },
      cancelled: { color: "red", label: "Cancelled" },
      completed: { color: "blue", label: "Completed" },
      pending: { color: "gray", label: "Pending" }
    };

    const config = statusConfig[status] || statusConfig.pending;

    return (
      <span
        className={`inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-${config.color}-100 text-${config.color}-800`}
      >
        {config.label}
      </span>
    );
  };

  return (
    <div className="min-h-screen bg-gray-50 py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header Section */}
        <div className="mb-8">
          <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
              <h1 className="text-3xl font-bold text-gray-900">Clinic Appointments</h1>
              <p className="mt-2 text-sm text-gray-600">
                Manage and view appointments across all doctors in your clinic
              </p>
            </div>
            
            <div className="flex flex-col sm:flex-row gap-4">
              {/* Doctor Filter */}
              <div className="flex items-center gap-3">
                <label className="text-sm font-medium text-gray-700">
                  Filter by Doctor:
                </label>
                <select
                  className="block w-48 rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  value={selectedDoctorId}
                  onChange={(e) => setSelectedDoctorId(e.target.value)}
                >
                  <option value="all">All Doctors ({doctorAppointments.length})</option>
                  {doctorAppointments.map((doc) => (
                    <option key={doc.doctorId} value={doc.doctorId}>
                      {doc.doctorName} ({doc.count} appointments)
                    </option>
                  ))}
                </select>
              </div>

              {/* Refresh Button */}
              <button
                onClick={loadAppointments}
                disabled={loading}
                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg className={`-ml-1 mr-2 h-4 w-4 ${loading ? 'animate-spin' : ''}`} fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                {loading ? "Loading..." : "Refresh"}
              </button>
            </div>
          </div>
        </div>

        {/* Stats Cards */}
        {!loading && doctorAppointments.length > 0 && (
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div className="bg-white overflow-hidden shadow rounded-lg">
              <div className="px-4 py-5 sm:p-6">
                <dt className="text-sm font-medium text-gray-500 truncate">Total Doctors</dt>
                <dd className="mt-1 text-3xl font-semibold text-gray-900">{stats.totalDoctors}</dd>
              </div>
            </div>
            <div className="bg-white overflow-hidden shadow rounded-lg">
              <div className="px-4 py-5 sm:p-6">
                <dt className="text-sm font-medium text-gray-500 truncate">Total Appointments</dt>
                <dd className="mt-1 text-3xl font-semibold text-gray-900">{stats.totalAppointments}</dd>
              </div>
            </div>
            <div className="bg-white overflow-hidden shadow rounded-lg">
              <div className="px-4 py-5 sm:p-6">
                <dt className="text-sm font-medium text-gray-500 truncate">Confirmed</dt>
                <dd className="mt-1 text-3xl font-semibold text-green-600">{stats.confirmedAppointments}</dd>
              </div>
            </div>
          </div>
        )}

        {/* Error Messages */}
        {!clinicId && (
          <div className="rounded-md bg-red-50 p-4 mb-6">
            <div className="flex">
              <div className="flex-shrink-0">
                <svg className="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                </svg>
              </div>
              <div className="ml-3">
                <h3 className="text-sm font-medium text-red-800">Clinic ID Required</h3>
                <div className="mt-1 text-sm text-red-700">Clinic ID not found. Please log in again.</div>
              </div>
            </div>
          </div>
        )}

        {error && (
          <div className="rounded-md bg-red-50 p-4 mb-6">
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

        {/* Loading State */}
        {loading && renderLoadingSkeleton()}

        {/* Empty State */}
        {!loading && !error && doctorAppointments.length === 0 && (
          <div className="text-center py-12">
            <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <h3 className="mt-2 text-sm font-medium text-gray-900">No appointments</h3>
            <p className="mt-1 text-sm text-gray-500">No doctors or appointments found for this clinic.</p>
          </div>
        )}

        {/* Doctor-wise Appointments */}
        {!loading && filteredDoctorAppointments.length > 0 && (
          <div className="space-y-6">
            {filteredDoctorAppointments.map((doc) => (
              <div key={doc.doctorId} className="bg-white shadow overflow-hidden sm:rounded-lg">
                {/* Doctor Header */}
                <div className="px-6 py-5 border-b border-gray-200 sm:flex sm:items-center sm:justify-between">
                  <div className="flex-1">
                    <h3 className="text-lg leading-6 font-medium text-gray-900">
                      {doc.doctorName}
                    </h3>
                    <p className="mt-1 text-sm text-gray-500">
                      Doctor ID: {doc.doctorId}
                    </p>
                  </div>
                  <div className="mt-4 sm:mt-0 sm:ml-4 sm:flex-shrink-0">
                    <div className="text-right">
                      <p className="text-sm text-gray-500">Total Appointments</p>
                      <p className="text-2xl font-bold text-gray-900">{doc.count}</p>
                    </div>
                  </div>
                </div>

                {/* Appointments Table */}
                {doc.appointments.length > 0 ? (
                  <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                      <thead className="bg-gray-50">
                        <tr>
                          <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Appointment
                          </th>
                          <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Patient
                          </th>
                          <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Contact
                          </th>
                          <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Schedule
                          </th>
                          <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                          </th>
                          <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Amount
                          </th>
                        </tr>
                      </thead>
                      <tbody className="bg-white divide-y divide-gray-200">
                        {doc.appointments.map((appt) => (
                          <tr key={appt.id} className="hover:bg-gray-50">
                            <td className="px-6 py-4 whitespace-nowrap">
                              <div className="text-sm font-medium text-gray-900">#{appt.id}</div>
                              <div className="text-sm text-gray-500">{appt.clinic?.name}</div>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap">
                              <div className="text-sm font-medium text-gray-900">
                                {appt.patient?.name || 'Unknown Patient'}
                              </div>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap">
                              <div className="text-sm text-gray-900">{appt.patient?.phone || '-'}</div>
                              <div className="text-sm text-gray-500">{appt.patient?.email || '-'}</div>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap">
                              <div className="text-sm text-gray-900">{appt.date}</div>
                              <div className="text-sm text-gray-500">{appt.time_slot}</div>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap">
                              {renderStatusBadge(appt.status)}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                              {appt.amount != null 
                                ? `₹${appt.amount} ${appt.currency || ''}`.trim()
                                : '-'
                              }
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                ) : (
                  <div className="px-6 py-8 text-center">
                    <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <h3 className="mt-2 text-sm font-medium text-gray-900">No appointments</h3>
                    <p className="mt-1 text-sm text-gray-500">No appointments scheduled for this doctor.</p>
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default VetAppointment;