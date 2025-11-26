import React, { useEffect, useMemo, useState } from "react";
import { useParams, Link } from "react-router-dom";
import axios from "axios";


const API_BASE =
  import.meta.env.VITE_BACKEND_API || "https://snoutiq.com/backend/api";

const formatDateTime = (value) => {
  if (!value) return "-";
  const d = new Date(String(value).replace(" ", "T"));
  return Number.isNaN(d.getTime()) ? value : d.toLocaleString();
};

const VetOrderDetails = () => {
  const { id } = useParams();
  const bookingId = useMemo(() => Number(id) || null, [id]);
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  

  useEffect(() => {
    if (!bookingId) {
      setError("Invalid booking ID");
      return;
    }
    const load = async () => {
      setLoading(true);
      setError("");
      try {
        const res = await axios.get(`${API_BASE}/bookings/details/${bookingId}`, {
          withCredentials: true,
        });
        
        setData(res?.data || null);
      } catch (err) {
        setError(err?.response?.data?.message || err?.message || "Failed to load booking");
        setData(null);
      } finally {
        setLoading(false);
      }
    };
    load();
  }, [bookingId]);

  const booking = data?.booking;
  const pet = data?.pet || (data?.pets || [])[0];
  console.log(pet,"ankit");
  

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-bold text-gray-900">
            Order #{bookingId || "-"}
          </h1>
          <Link
            to="/user-dashboard/order-history"
            className="text-sm text-indigo-600 hover:underline"
          >
            Back to orders
          </Link>
        </div>

        {error && (
          <div className="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            {error}
          </div>
        )}

        {loading ? (
          <div className="bg-white rounded-xl shadow p-6 text-sm text-gray-600">
            Loading booking...
          </div>
        ) : !booking ? (
          <div className="bg-white rounded-xl shadow p-6 text-sm text-gray-600">
            No booking found.
          </div>
        ) : (
          <>
            <div className="bg-white rounded-xl shadow p-6 space-y-4">
              <div className="flex flex-wrap gap-3 text-sm text-gray-700">
                <span className="px-3 py-1 rounded-full bg-slate-100 text-slate-700">
                  {booking.service_type || "-"}
                </span>
                <span className="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700">
                  {booking.status || "-"}
                </span>
                <span className="px-3 py-1 rounded-full bg-sky-100 text-sky-700 capitalize">
                  {booking.urgency || "-"}
                </span>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                  <p className="text-xs text-gray-500">Scheduled</p>
                  <p className="font-semibold text-gray-900">
                    {formatDateTime(booking.scheduled_for || booking.booking_created_at)}
                  </p>
                </div>
                <div>
                  <p className="text-xs text-gray-500">Clinic ID</p>
                  <p className="font-semibold text-gray-900">
                    {booking.clinic_id ?? "-"}
                  </p>
                </div>
                <div>
                  <p className="text-xs text-gray-500">Assigned Doctor</p>
                  <p className="font-semibold text-gray-900">
                    {booking.assigned_doctor_id ?? booking.doctor_name ?? "-"}
                  </p>
                </div>
                <div>
                  <p className="text-xs text-gray-500">Price</p>
                  <p className="font-semibold text-gray-900">
                    Rs. {booking.final_price || booking.quoted_price || "0"}
                  </p>
                </div>
              </div>
              <div>
                <p className="text-xs text-gray-500 mb-1">AI Summary</p>
                <div className="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-800 whitespace-pre-wrap">
                  {booking.ai_summary || "No AI summary"}
                </div>
              </div>
            </div>

            {/* <div className="bg-white rounded-xl shadow p-6 space-y-3">
              <h2 className="text-lg font-semibold text-gray-900">Pet Details</h2>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                  <p className="text-xs text-gray-500">Pet</p>
                  <p className="font-semibold text-gray-900">
                    {pet?.name || booking.pet_name || "-"}
                  </p>
                </div>
                <div>
                  <p className="text-xs text-gray-500">Breed</p>
                  <p className="font-semibold text-gray-900">
                    {pet?.breed || "-"}
                  </p>
                </div>
                <div>
                  <p className="text-xs text-gray-500">Age</p>
                  <p className="font-semibold text-gray-900">
                    {pet?.age || "-"}
                  </p>
                </div>
                <div>
                  <p className="text-xs text-gray-500">Gender</p>
                  <p className="font-semibold text-gray-900">
                    {pet?.gender || "-"}
                  </p>
                </div>
              </div>
            </div> */}
          </>
        )}
      </div>
    </div>
  );
};

export default VetOrderDetails;
