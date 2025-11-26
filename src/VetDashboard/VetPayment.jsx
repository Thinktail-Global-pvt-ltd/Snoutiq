import React, { useEffect, useMemo, useState, useCallback } from "react";
import axios from "axios";
import { useAuth } from "../auth/AuthContext";

// Constants
const API_BASE = import.meta.env.VITE_BACKEND_API || "https://snoutiq.com/backend/api";
const SUCCESS_STATUSES = new Set(["completed", "captured", "paid", "success", "successful", "settled"]);
const STATUS_OPTIONS = [
  { value: "all", label: "All" },
  { value: "completed", label: "Completed" },
  { value: "captured", label: "Captured" },
  { value: "paid", label: "Paid" },
  { value: "success", label: "Success" },
  { value: "failed", label: "Failed" },
];

// Utility functions
const formatRupees = (paise) => {
  const amount = Number(paise || 0) / 100;
  return new Intl.NumberFormat("en-IN", {
    style: "currency",
    currency: "INR",
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount);
};

const formatDate = (isoString) => {
  if (!isoString) return "-";
  try {
    return new Date(isoString).toLocaleString("en-IN", {
      timeZone: "Asia/Kolkata",
      hour12: true,
      day: "2-digit",
      month: "short",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  } catch {
    return isoString;
  }
};

const getFromStorage = (key) => {
  if (typeof window === "undefined") return null;
  const raw = localStorage.getItem(key);
  const num = Number(raw);
  return Number.isFinite(num) && num > 0 ? num : null;
};

// Custom hook for payment data
const usePayments = (clinicId, vetId, statusFilter) => {
  const [state, setState] = useState({
    loading: false,
    error: "",
    transactions: [],
    summary: null,
  });

  useEffect(() => {
    const fetchPayments = async () => {
      if (!clinicId && !vetId) {
        setState(prev => ({ ...prev, error: "Missing clinic_id or vet_id. Please log in again.", transactions: [], summary: null }));
        return;
      }

      setState(prev => ({ ...prev, loading: true, error: "" }));
      
      try {
        const params = {
          clinic_id: clinicId || undefined,
          vet_id: vetId || undefined,
          status: statusFilter === "all" ? undefined : statusFilter,
        };

        const { data } = await axios.get(`${API_BASE}/clinics/payments`, {
          params,
          withCredentials: true,
        });

        if (!data?.success) {
          throw new Error(data?.message || "Unable to load payments right now.");
        }

        setState({
          loading: false,
          error: "",
          transactions: Array.isArray(data.transactions) ? data.transactions : [],
          summary: {
            totalPayments: data.payments ?? 0,
            totalRupees: data.total_rupees ?? 0,
            status: data.status_filter ?? "all",
            clinicId: data.clinic_id ?? clinicId ?? null,
            vetId: data.vet_id ?? vetId ?? null,
          },
        });
      } catch (err) {
        console.error("Failed to load clinic payments", err);
        const message = err?.response?.data?.message || err?.message || "Unable to load payments.";
        setState(prev => ({ ...prev, loading: false, error: message, transactions: [], summary: null }));
      }
    };

    fetchPayments();
  }, [clinicId, vetId, statusFilter]);

  return state;
};

// Table Row Component
const TransactionRow = React.memo(({ transaction }) => {
  const paymentId = transaction.reference || `TXN#${transaction.id}`;
  const orderId = transaction.metadata?.order_id || transaction.metadata?.razorpay_order_id || "-";
  const amountLabel = formatRupees(transaction.amount_paise || 0);
  const statusKey = String(transaction.status || "").toLowerCase();
  const isSuccess = SUCCESS_STATUSES.has(statusKey);
  const methodLabel = transaction.payment_method || transaction.type || transaction.gateway || "-";
  const userName = transaction.metadata?.user_name || transaction.user?.name || transaction.user_id || "-";
  const doctorLabel = transaction.metadata?.doctor_name || transaction.doctor?.doctor_name || transaction.doctor_id || "-";

  return (
    <tr className="hover:bg-gray-50 transition-colors duration-150">
      <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
        {formatDate(transaction.created_at)}
      </td>
      <td className="px-4 py-3 whitespace-nowrap font-mono text-sm text-gray-900">
        {paymentId}
      </td>
      <td className="px-4 py-3 whitespace-nowrap font-mono text-sm text-gray-900">
        {orderId}
      </td>
      <td className="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
        {amountLabel}
      </td>
      <td className="px-4 py-3 whitespace-nowrap">
        <span
          className={`inline-flex px-2 py-1 text-xs font-medium rounded-full ${
            isSuccess
              ? "bg-green-100 text-green-800"
              : "bg-gray-100 text-gray-800"
          }`}
        >
          {transaction.status || "-"}
        </span>
      </td>
      <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
        {methodLabel}
      </td>
      <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
        {userName}
      </td>
      <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
        {doctorLabel}
      </td>
    </tr>
  );
});

TransactionRow.displayName = 'TransactionRow';

// Summary Cards Component
const SummaryCards = React.memo(({ summary, resolvedClinicId, lastPaymentAt }) => (
  <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
    <div className="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
      <dt className="text-sm font-medium text-gray-500 truncate">Clinic ID</dt>
      <dd className="mt-1 text-2xl font-semibold text-gray-900">
        {summary?.clinicId || resolvedClinicId || "-"}
      </dd>
    </div>
    <div className="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
      <dt className="text-sm font-medium text-gray-500 truncate">Total Payments</dt>
      <dd className="mt-1 text-2xl font-semibold text-gray-900">
        {summary?.totalPayments ?? 0}
      </dd>
    </div>
    <div className="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
      <dt className="text-sm font-medium text-gray-500 truncate">Amount Collected</dt>
      <dd className="mt-1 text-2xl font-semibold text-gray-900">
        {formatRupees((summary?.totalRupees ?? 0) * 100)}
      </dd>
    </div>
    <div className="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
      <dt className="text-sm font-medium text-gray-500 truncate">Last Payment</dt>
      <dd className="mt-1 text-lg font-semibold text-gray-900">{lastPaymentAt}</dd>
    </div>
  </div>
));

SummaryCards.displayName = 'SummaryCards';

// Loading Skeleton
const LoadingSkeleton = () => (
  <div className="animate-pulse space-y-4">
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      {[...Array(4)].map((_, i) => (
        <div key={i} className="bg-gray-200 rounded-lg h-24"></div>
      ))}
    </div>
    <div className="bg-gray-200 rounded-lg h-64"></div>
  </div>
);

// Main Component
const VetPayment = () => {
  const { user } = useAuth();
  const [statusFilter, setStatusFilter] = useState("all");

  const resolvedIds = useMemo(() => ({
    clinicId: user?.clinic_id || user?.vet_registeration_id || user?.vet_id || getFromStorage("clinic_id") || getFromStorage("vet_registeration_id") || null,
    vetId: user?.vet_registeration_id || user?.vet_id || getFromStorage("vet_id") || getFromStorage("userId") || null,
  }), [user]);

  const { loading, error, transactions, summary } = usePayments(
    resolvedIds.clinicId,
    resolvedIds.vetId,
    statusFilter
  );

  const lastPaymentAt = useMemo(() => {
    if (!transactions.length) return "-";
    return formatDate(transactions[0]?.created_at);
  }, [transactions]);

  const handleStatusFilterChange = useCallback((e) => {
    setStatusFilter(e.target.value);
  }, []);

  return (
    <div className="min-h-screen bg-gray-50 py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="mb-8">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h1 className="text-3xl font-bold text-gray-900">Clinic Payments</h1>
              <p className="mt-1 text-sm text-gray-600">
                Manage and track your clinic payment transactions
              </p>
            </div>
            <div className="flex items-center gap-3">
              <label htmlFor="status-filter" className="text-sm font-medium text-gray-700">
                Filter by Status
              </label>
              <select
                id="status-filter"
                value={statusFilter}
                onChange={handleStatusFilterChange}
                className="block w-40 rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                {STATUS_OPTIONS.map(option => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </div>
          </div>
        </div>

        {/* Error Alert */}
        {error && (
          <div className="mb-6 rounded-md bg-red-50 p-4 border border-red-200">
            <div className="flex">
              <div className="flex-shrink-0">
                <svg className="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clipRule="evenodd" />
                </svg>
              </div>
              <div className="ml-3">
                <h3 className="text-sm font-medium text-red-800">Error</h3>
                <div className="mt-1 text-sm text-red-700">{error}</div>
              </div>
            </div>
          </div>
        )}

        {/* Content */}
        {loading ? (
          <LoadingSkeleton />
        ) : (
          <>
            <SummaryCards 
              summary={summary} 
              resolvedClinicId={resolvedIds.clinicId}
              lastPaymentAt={lastPaymentAt}
            />

            {/* Transactions Table */}
            <div className="mt-8 bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
              <div className="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div className="flex items-center justify-between">
                  <h2 className="text-lg font-semibold text-gray-900">Recent Transactions</h2>
                  <span className="text-sm text-gray-500">
                    Showing {transactions.length} transactions
                  </span>
                </div>
              </div>

              {transactions.length === 0 ? (
                <div className="text-center py-12">
                  <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                  </svg>
                  <h3 className="mt-2 text-sm font-medium text-gray-900">No transactions</h3>
                  <p className="mt-1 text-sm text-gray-500">No payment transactions found for the selected criteria.</p>
                </div>
              ) : (
                <div className="overflow-x-auto">
                  <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                      <tr>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Date & Time
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Payment ID
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Order ID
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Amount
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Status
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Method
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          User
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Doctor
                        </th>
                      </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                      {transactions.map((transaction) => (
                        <TransactionRow key={transaction.id} transaction={transaction} />
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          </>
        )}
      </div>
    </div>
  );
};

export default React.memo(VetPayment);