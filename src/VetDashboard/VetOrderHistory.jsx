import React, { useEffect, useMemo, useState } from "react";
import axios from "axios";
import { useAuth } from "../auth/AuthContext";

const API_BASE =
  import.meta.env.VITE_BACKEND_API || "https://snoutiq.com/backend/api";

const formatDateTime = (value) => {
  if (!value) return "-";
  const d = new Date(String(value).replace(" ", "T"));
  return Number.isNaN(d.getTime()) ? value : d.toLocaleString();
};

const VetOrderHistory = () => {
  const { user } = useAuth();
  const [doctors, setDoctors] = useState([]);
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [sinceDate, setSinceDate] = useState(
    new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10)
  );
  const [statusFilter, setStatusFilter] = useState("");

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

  const loadOrders = async () => {
    if (!clinicId) {
      setError("Clinic ID not found. Please log in again.");
      return;
    }
    setLoading(true);
    setError("");
    try {
      // 1) Get doctors for clinic
      const dres = await axios.get(`${API_BASE}/clinics/${clinicId}/doctors`, {
        withCredentials: true,
      });
      const docList = Array.isArray(dres?.data?.doctors)
        ? dres.data.doctors
        : [];
      setDoctors(docList);

      // 2) Fetch bookings per doctor
      const all = [];
      await Promise.all(
        docList.map(async (doc) => {
          try {
            const res = await axios.get(
              `${API_BASE}/doctors/${doc.id}/bookings`,
              {
                params: { since: sinceDate },
                withCredentials: true,
              }
            );
            const bookings = Array.isArray(res?.data?.bookings)
              ? res.data.bookings
              : [];
            bookings.forEach((b) =>
              all.push({
                ...b,
                __doctor_id: doc.id,
                __doctor_name:
                  doc.name || doc.doctor_name || `Doctor #${doc.id}`,
              })
            );
          } catch (err) {
            // ignore per-doctor failure
          }
        })
      );

      // 3) Filter status
      let rows = all;
      if (statusFilter) {
        const key = statusFilter.toLowerCase();
        rows = rows.filter(
          (x) => String(x.status || "").toLowerCase() === key
        );
      }

      // 4) Sort
      rows.sort((a, b) => {
        const ta =
          Date.parse(
            String(a.scheduled_for || a.booking_created_at || "").replace(
              " ",
              "T"
            )
          ) || 0;
        const tb =
          Date.parse(
            String(b.scheduled_for || b.booking_created_at || "").replace(
              " ",
              "T"
            )
          ) || 0;
        return tb - ta;
      });

      setOrders(rows);
    } catch (err) {
      setError(err?.response?.data?.message || err?.message || "Load failed");
      setOrders([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (clinicId) {
      loadOrders();
    } else {
      setError("Clinic ID not found. Please log in again.");
    }
  }, [clinicId]);

  const lastByDoctor = useMemo(() => {
    const map = new Map();
    orders.forEach((o) => {
      if (!map.has(o.__doctor_id)) {
        map.set(o.__doctor_id, []);
      }
      map.get(o.__doctor_id).push(o);
    });
    return map;
  }, [orders]);

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div className="bg-white rounded-xl shadow p-4">
          <div className="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
            <div className="grid grid-cols-1 md:grid-cols-5 gap-4 flex-1">
              <div className="md:col-span-2">
                <label className="block text-sm font-medium text-gray-700">
                  Clinic
                </label>
                <input
                  type="text"
                  value={clinicId ? `#${clinicId}` : "Not detected"}
                  className="mt-1 w-full rounded border-gray-300 bg-gray-50"
                  disabled
                />
                <div className="text-xs text-gray-500 mt-1">
                  Loaded from session/local storage.
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">
                  Since
                </label>
                <input
                  type="date"
                  value={sinceDate}
                  onChange={(e) => setSinceDate(e.target.value)}
                  className="mt-1 w-full rounded border-gray-300"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">
                  Filter Status
                </label>
                <select
                  value={statusFilter}
                  onChange={(e) => setStatusFilter(e.target.value)}
                  className="mt-1 w-full rounded border-gray-300"
                >
                  <option value="">All</option>
                  <option value="completed">Completed</option>
                  <option value="accepted">Accepted</option>
                  <option value="pending">Pending</option>
                  <option value="routing">Routing</option>
                  <option value="in_progress">In Progress</option>
                  <option value="cancelled">Cancelled</option>
                  <option value="failed">Failed</option>
                </select>
              </div>
              <div className="flex items-end">
                <button
                  onClick={loadOrders}
                  className="px-4 py-2 rounded bg-indigo-600 text-white"
                  disabled={loading}
                >
                  {loading ? "Loading..." : "Load Orders"}
                </button>
              </div>
            </div>
          </div>
        </div>

        {error && (
          <div className="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            {error}
          </div>
        )}

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {doctors.map((doc) => {
            const list = lastByDoctor.get(doc.id) || [];
            const last = list[0] || null;
            return (
              <div key={doc.id} className="bg-white border rounded-lg p-4">
                <div className="text-sm text-gray-500">Doctor</div>
                <div className="font-semibold">
                  {doc.name || doc.doctor_name || `Doctor #${doc.id}`}
                </div>
                <div className="mt-2 text-xs text-gray-500">Last booking</div>
                {last ? (
                  <>
                    <div className="mt-1 text-sm">
                      #{last.id} · {last.service_type || ""} ·{" "}
                      <span className="uppercase">{last.status || ""}</span>
                    </div>
                    <div className="text-xs text-gray-500">
                      {formatDateTime(
                        last.scheduled_for || last.booking_created_at
                      )}
                    </div>
                    <div className="mt-2">
                      <a
                        href={`/user-dashboard/order/${last.id}`}
                        className="text-indigo-600 hover:underline text-sm"
                      >
                        View details
                      </a>
                    </div>
                  </>
                ) : (
                  <div className="mt-1 text-sm text-gray-500">No bookings</div>
                )}
              </div>
            );
          })}
        </div>

        <div className="bg-white rounded-xl shadow">
          <div className="p-4 border-b">
            <div className="flex items-center justify-between">
              <div className="text-base font-semibold">Order History</div>
              <div className="text-sm text-gray-500">
                {orders.length} orders
              </div>
            </div>
          </div>
          <div className="overflow-auto">
            {loading ? (
              <div className="p-6 text-center text-sm text-gray-600">
                Loading…
              </div>
            ) : orders.length === 0 ? (
              <div className="p-6 text-center text-sm text-gray-600">
                No orders found for the selected range.
              </div>
            ) : (
              <table className="min-w-full text-sm">
                <thead className="bg-gray-50">
                  <tr className="text-left text-gray-700">
                    <th className="px-4 py-2">Order #</th>
                    <th className="px-4 py-2">Doctor</th>
                    <th className="px-4 py-2">Pet</th>
                    <th className="px-4 py-2">Service</th>
                    <th className="px-4 py-2">Urgency</th>
                    <th className="px-4 py-2">Scheduled</th>
                    <th className="px-4 py-2">Status</th>
                    <th className="px-4 py-2">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y">
                  {orders.map((b) => (
                    <tr key={b.id}>
                      <td className="px-4 py-2 font-medium">#{b.id}</td>
                      <td className="px-4 py-2">{b.__doctor_name || "-"}</td>
                      <td className="px-4 py-2">{b.pet_name || "-"}</td>
                      <td className="px-4 py-2">
                        {(b.service_type || "-").replace("_", " ")}
                      </td>
                      <td className="px-4 py-2 capitalize">
                        {b.urgency || "-"}
                      </td>
                      <td className="px-4 py-2">
                        {formatDateTime(
                          b.scheduled_for || b.booking_created_at
                        )}
                      </td>
                      <td className="px-4 py-2">
                        <span
                          className={`px-2 py-0.5 rounded-full text-xs ${
                            b.status === "completed"
                              ? "bg-emerald-100 text-emerald-700"
                              : "bg-gray-200 text-gray-700"
                          }`}
                        >
                          {b.status || "-"}
                        </span>
                      </td>
                      <td className="px-4 py-2">
                        <a
                          href={`/user-dashboard/order/${b.id}`}
                          className="text-indigo-600 hover:underline"
                        >
                          View
                        </a>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default VetOrderHistory;
