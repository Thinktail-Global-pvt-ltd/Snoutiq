import React, { useContext, useEffect, useMemo, useState } from "react";
import axios from "axios";
import { AuthContext } from "../auth/AuthContext";

const API_BASE = import.meta.env.VITE_BACKEND_API || "https://snoutiq.com/backend/api";

const formatDate = (value) => {
  if (!value) return "-";
  const d = new Date(String(value).replace(" ", "T"));
  return Number.isNaN(d.getTime()) ? value : d.toLocaleDateString();
};

const formatDateTime = (value) => {
  if (!value) return "-";
  const d = new Date(String(value).replace(" ", "T"));
  return Number.isNaN(d.getTime()) ? value : d.toLocaleString();
};

const VetBookingPayments = () => {
  const { user } = useContext(AuthContext);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [bookings, setBookings] = useState([]);
  const [sinceDate] = useState(new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10));
  const [serviceFilter, setServiceFilter] = useState("");
  

  // Get clinic ID from user or localStorage
  const clinicId = useMemo(() => {
    const fromStorage = (key) => {
      const raw = localStorage.getItem(key);
      const num = Number(raw);
      return Number.isFinite(num) && num > 0 ? num : null;
    };

    return user?.clinic_id || user?.vet_registeration_id || user?.vet_id || 
           fromStorage("clinic_id") || fromStorage("vet_registeration_id") || fromStorage("vet_id");
  }, [user]);

  // Load all payments data
  const loadPayments = async () => {
    if (!clinicId) {
      setError("Clinic ID not found. Please log in again.");
      return;
    }

    setLoading(true);
    setError("");

    try {
      // Get doctors for this clinic
      const doctorsRes = await axios.get(`${API_BASE}/clinics/${clinicId}/doctors`, {
        withCredentials: true
      });
      
      const doctors = doctorsRes?.data?.doctors || [];

      // Fetch bookings for all doctors in parallel
      const bookingPromises = doctors.map(async (doctor) => {
        try {
          const res = await axios.get(`${API_BASE}/doctors/${doctor.id}/bookings`, {
            params: { since: sinceDate },
            withCredentials: true,
          });
          
          const bookingsList = res?.data?.bookings || [];
          return bookingsList.map(booking => ({
            ...booking,
            doctorName: doctor.name || doctor.doctor_name || `Doctor #${doctor.id}`,
            doctorId: doctor.id
          }));
        } catch (error) {
          console.warn(`Failed to load bookings for doctor ${doctor.id}`, error);
          return [];
        }
      });

      const allBookings = (await Promise.all(bookingPromises)).flat();

      // Filter paid bookings
      const paidBookings = allBookings
        .filter(booking => String(booking.payment_status || "").toLowerCase() === "paid")
        .filter(booking => !serviceFilter || booking.service_type === serviceFilter)
        .sort((a, b) => {
          const dateA = new Date(a.payment_verified_at || a.scheduled_for || a.booking_created_at || 0);
          const dateB = new Date(b.payment_verified_at || b.scheduled_for || b.booking_created_at || 0);
          return dateB - dateA;
        });

      setBookings(paidBookings);
    } catch (err) {
      console.error("Failed to load booking payments", err);
      setError(err?.response?.data?.message || err?.message || "Unable to load booking payments.");
      setBookings([]);
    } finally {
      setLoading(false);
    }
  };

  // Calculate summary from bookings
  const summary = useMemo(() => {
    const totalInr = bookings.reduce((sum, booking) => sum + (Number(booking.final_price) || 0), 0);
    
    const methods = bookings.reduce((acc, booking) => {
      const method = (booking.payment_method || "-").toUpperCase();
      acc[method] = (acc[method] || 0) + 1;
      return acc;
    }, {});

    return {
      totalInr,
      count: bookings.length,
      methods: Object.entries(methods)
    };
  }, [bookings]);

  // Load payments when component mounts or clinicId changes
  useEffect(() => {
    loadPayments();
  }, [clinicId]);

  return (
    <div className="min-h-screen bg-gray-50 p-4">
      <div className="max-w-7xl mx-auto space-y-6">
        {/* Header */}
        <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Booking Payments</h1>
            <p className="text-gray-600">Clinic ID: {clinicId ? `#${clinicId}` : "Not available"}</p>
          </div>
          
          <div className="flex gap-4 items-center">
            <select 
              value={serviceFilter}
              onChange={(e) => setServiceFilter(e.target.value)}
              className="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">All Services</option>
              <option value="video">Video</option>
              <option value="in_clinic">In Clinic</option>
              <option value="home_visit">Home Visit</option>
            </select>
            
            <button
              onClick={loadPayments}
              disabled={loading}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              {loading ? "Loading..." : "Refresh"}
            </button>
          </div>
        </div>

        {/* Error Message */}
        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            {error}
          </div>
        )}

        {/* Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="bg-white p-6 rounded-lg shadow-sm border">
            <p className="text-gray-600 text-sm">Total Collected</p>
            <p className="text-2xl font-bold text-gray-900">
              ₹{summary.totalInr.toLocaleString('en-IN', { minimumFractionDigits: 2 })}
            </p>
          </div>
          
          <div className="bg-white p-6 rounded-lg shadow-sm border">
            <p className="text-gray-600 text-sm">Paid Bookings</p>
            <p className="text-2xl font-bold text-gray-900">{summary.count}</p>
          </div>
          
          <div className="bg-white p-6 rounded-lg shadow-sm border">
            <p className="text-gray-600 text-sm">Payment Methods</p>
            <p className="text-gray-900">
              {summary.methods.length > 0 
                ? summary.methods.map(([method, count]) => `${method}: ${count}`).join(", ")
                : "No payments"
              }
            </p>
          </div>
        </div>

        {/* Bookings Table */}
        <div className="bg-white rounded-lg shadow-sm border overflow-hidden">
          <div className="px-6 py-4 border-b">
            <h2 className="text-lg font-semibold">Paid Bookings ({summary.count})</h2>
          </div>

          {loading ? (
            <div className="p-8 text-center text-gray-600">Loading payments...</div>
          ) : bookings.length === 0 ? (
            <div className="p-8 text-center text-gray-600">No paid bookings found</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Booking ID</th>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Verified At</th>
                  </tr>
                </thead>
                <tbody className="divide-y">
                  {bookings.map((booking) => (
                    <tr key={booking.id} className="hover:bg-gray-50">
                      <td className="px-4 py-3 text-sm">
                        {formatDate(booking.payment_verified_at || booking.scheduled_for || booking.booking_created_at)}
                      </td>
                      <td className="px-4 py-3 text-sm font-medium">#{booking.id}</td>
                      <td className="px-4 py-3 text-sm">{booking.doctorName}</td>
                      <td className="px-4 py-3 text-sm capitalize">{booking.service_type || "-"}</td>
                      <td className="px-4 py-3 text-sm font-medium">
                        ₹{(Number(booking.final_price) || 0).toFixed(2)}
                      </td>
                      <td className="px-4 py-3 text-sm uppercase">{booking.payment_method || "-"}</td>
                      <td className="px-4 py-3 text-sm">
                        {formatDateTime(booking.payment_verified_at)}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default VetBookingPayments;