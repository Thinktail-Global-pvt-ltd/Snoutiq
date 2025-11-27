import React, { useCallback, useEffect, useMemo, useState } from "react";
import axios from "axios";
import { useLocation } from "react-router-dom";
import { toast } from "react-hot-toast";
import { useAuth } from "../auth/AuthContext";

const API_BASE =
  import.meta.env.VITE_BACKEND_API || "https://snoutiq.com/backend/api";
const DEFAULT_ROLES = ["doctor", "receptionist"];

const toNumber = (value) => {
  const num = Number(value);
  return Number.isFinite(num) && num > 0 ? num : null;
};

const formatRole = (role) => {
  if (role === "doctor") return "Doctor";
  if (role === "receptionist") return "Receptionist";
  if (role === "clinic_admin") return "Clinic Admin";
  return role ? role.replace(/_/g, " ") : "-";
};

const formatType = (type) => {
  if (type === "clinic") return "Clinic Admin";
  if (type === "doctor") return "Doctor";
  if (type === "receptionist") return "Receptionist";
  return type || "-";
};

const rolePillClass = (role) => {
  if (role === "doctor") return "bg-blue-50 text-blue-700 border border-blue-200";
  if (role === "receptionist") return "bg-green-50 text-green-700 border border-green-200";
  if (role === "clinic_admin") return "bg-purple-50 text-purple-700 border border-purple-200";
  return "bg-gray-100 text-gray-700 border border-gray-200";
};

const typeBadgeClass = (type) => {
  if (type === "clinic") return "bg-gradient-to-r from-purple-500 to-purple-600";
  if (type === "doctor") return "bg-gradient-to-r from-blue-500 to-blue-600";
  if (type === "receptionist") return "bg-gradient-to-r from-green-500 to-green-600";
  return "bg-gradient-to-r from-gray-500 to-gray-600";
};

const StaffManagement = () => {
  const { user, token } = useAuth();
  const location = useLocation();

  const searchParams = useMemo(
    () => new URLSearchParams(location.search),
    [location.search]
  );

  const queryClinicId = useMemo(() => {
    return (
      toNumber(searchParams.get("userId")) ||
      toNumber(searchParams.get("user_id")) ||
      toNumber(searchParams.get("doctorId")) ||
      null
    );
  }, [searchParams]);

  const querySlug = useMemo(() => {
    const raw =
      searchParams.get("vet_slug") || searchParams.get("clinic_slug");
    return raw && raw.trim() ? raw.trim() : null;
  }, [searchParams]);

  const clinicId = useMemo(() => {
    return (
      queryClinicId ||
      toNumber(user?.clinic_id) ||
      toNumber(user?.vet_registeration_id) ||
      toNumber(user?.vet_id) ||
      toNumber(user?.id) ||
      toNumber(localStorage.getItem("clinic_id")) ||
      toNumber(localStorage.getItem("vet_registeration_id")) ||
      toNumber(localStorage.getItem("vet_id")) ||
      toNumber(localStorage.getItem("user_id")) ||
      null
    );
  }, [queryClinicId, user]);

  const clinicSlug = useMemo(() => {
    const raw =
      querySlug ||
      user?.vet_slug ||
      user?.clinic_slug ||
      user?.slug ||
      localStorage.getItem("vet_slug") ||
      localStorage.getItem("clinic_slug");
    return raw && raw.trim() ? raw.trim() : null;
  }, [querySlug, user]);

  const authHeaders = useMemo(() => {
    const headers = { Accept: "application/json" };
    if (token) headers.Authorization = `Bearer ${token}`;
    if (clinicId) headers["X-User-Id"] = String(clinicId);
    if (clinicSlug) headers["X-Vet-Slug"] = clinicSlug;
    return headers;
  }, [token, clinicId, clinicSlug]);

  const [state, setState] = useState({
    loading: false,
    error: "",
    staff: [],
    roles: DEFAULT_ROLES,
  });
  const [searchTerm, setSearchTerm] = useState("");
  const [modalOpen, setModalOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [roleSaving, setRoleSaving] = useState("");
  const [form, setForm] = useState({
    name: "",
    email: "",
    phone: "",
    role: "receptionist",
  });

  const hasTarget = Boolean(clinicId || clinicSlug);

  const normalizePayload = useCallback((data) => {
    const list = [];
    if (data?.clinic_admin) {
      list.push({
        ...data.clinic_admin,
        editable: false,
        type: data.clinic_admin.type || "clinic",
        role: data.clinic_admin.role || "clinic_admin",
      });
    }
    (data?.doctors || []).forEach((doc) => {
      list.push({
        ...doc,
        editable: true,
        type: doc.type || "doctor",
        role: doc.role || "doctor",
      });
    });
    (data?.receptionists || []).forEach((rec) => {
      list.push({
        ...rec,
        editable: true,
        type: rec.type || "receptionist",
        role: rec.role || "receptionist",
      });
    });
    return list;
  }, []);

  const fetchStaff = useCallback(async () => {
    if (!hasTarget) {
      setState((prev) => ({
        ...prev,
        error:
          "Missing clinic id/slug. Add ?userId=<clinicId> or ?vet_slug=<slug> to the URL or log in again.",
        staff: [],
      }));
      return;
    }

    setState((prev) => ({ ...prev, loading: true, error: "" }));
    try {
      const res = await axios.get(`${API_BASE}/staff`, {
        params: {
          user_id: clinicId || undefined,
          vet_slug: clinicSlug || undefined,
        },
        headers: authHeaders,
        withCredentials: true,
      });

      const payload = res?.data?.data ?? res?.data ?? {};
      const roles = Array.isArray(payload?.editable_roles)
        ? payload.editable_roles
        : DEFAULT_ROLES;

      setState({
        loading: false,
        error: "",
        staff: normalizePayload(payload),
        roles,
      });
    } catch (err) {
      const message =
        err?.response?.data?.message ||
        err?.message ||
        "Unable to load staff.";
      setState((prev) => ({
        ...prev,
        loading: false,
        error: message,
        staff: [],
        roles: DEFAULT_ROLES,
      }));
    }
  }, [hasTarget, clinicId, clinicSlug, authHeaders, normalizePayload]);

  useEffect(() => {
    fetchStaff();
  }, [fetchStaff]);

  const filteredStaff = useMemo(() => {
    if (!searchTerm.trim()) return state.staff;
    const q = searchTerm.trim().toLowerCase();
    return state.staff.filter((item) => {
      return (
        (item.name || "").toLowerCase().includes(q) ||
        (item.email || "").toLowerCase().includes(q) ||
        (item.phone || "").toLowerCase().includes(q)
      );
    });
  }, [state.staff, searchTerm]);

  const appendTarget = (formData) => {
    if (clinicId) formData.append("user_id", String(clinicId));
    if (clinicSlug) formData.append("vet_slug", clinicSlug);
  };

  const handleAddStaff = async (event) => {
    event.preventDefault();
    if (!hasTarget) {
      toast.error("Clinic id/slug missing. Please open from dashboard.");
      return;
    }
    if (!form.name.trim()) {
      toast.error("Full name is required.");
      return;
    }

    setSaving(true);
    try {
      const payload = new FormData();
      payload.append("name", form.name.trim());
      if (form.email.trim()) payload.append("email", form.email.trim());
      if (form.phone.trim()) payload.append("phone", form.phone.trim());
      if (form.role) payload.append("role", form.role);
      appendTarget(payload);

      await axios.post(`${API_BASE}/staff/receptionists`, payload, {
        headers: authHeaders,
        withCredentials: true,
      });

      toast.success("Staff member added successfully!");
      setForm({ name: "", email: "", phone: "", role: "receptionist" });
      setModalOpen(false);
      fetchStaff();
    } catch (err) {
      const message =
        err?.response?.data?.message ||
        err?.message ||
        "Unable to add staff.";
      toast.error(message);
    } finally {
      setSaving(false);
    }
  };

  const handleRoleUpdate = async (item, newRole) => {
    if (!item?.id || !item?.type) return;
    if (item.role === newRole) return;
    if (!hasTarget) {
      toast.error("Clinic id/slug missing. Please open from dashboard.");
      return;
    }

    const key = `${item.type}-${item.id}`;
    setRoleSaving(key);
    try {
      const payload = new FormData();
      payload.append("role", newRole);
      appendTarget(payload);

      try {
        await axios.patch(
          `${API_BASE}/staff/${item.type}/${item.id}/role`,
          payload,
          {
            headers: authHeaders,
            withCredentials: true,
          }
        );
      } catch (patchErr) {
        await axios.post(
          `${API_BASE}/staff/${item.type}/${item.id}/role`,
          payload,
          {
            headers: {
              ...authHeaders,
              "X-HTTP-Method-Override": "PATCH",
            },
            withCredentials: true,
          }
        );
      }

      toast.success("Role updated successfully!");
      fetchStaff();
    } catch (err) {
      const message =
        err?.response?.data?.message ||
        err?.message ||
        "Unable to update role.";
      toast.error(message);
    } finally {
      setRoleSaving("");
    }
  };

  // Statistics for dashboard cards
  const staffStats = useMemo(() => {
    const total = state.staff.length;
    const doctors = state.staff.filter(s => s.role === 'doctor').length;
    const receptionists = state.staff.filter(s => s.role === 'receptionist').length;
    const admins = state.staff.filter(s => s.role === 'clinic_admin').length;
    
    return { total, doctors, receptionists, admins };
  }, [state.staff]);

  return (
    <div className="min-h-screen bg-gray-50/30 py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header Section */}
        <div className="mb-8">
          <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
              <h1 className="text-3xl font-bold text-gray-900">Staff Management</h1>
              <p className="mt-2 text-lg text-gray-600 max-w-3xl">
                Manage your clinic team members, roles, and permissions in one place.
              </p>
            </div>
            <div className="mt-4 lg:mt-0 flex items-center space-x-3">
              <button
                type="button"
                onClick={() => setModalOpen(true)}
                disabled={!hasTarget}
                className="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-xl text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-lg shadow-blue-500/25 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
              >
                <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add Staff Member
              </button>
            </div>
          </div>

          {!hasTarget && (
            <div className="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-xl">
              <div className="flex">
                <svg className="w-5 h-5 text-amber-400 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                </svg>
                <div>
                  <p className="text-sm font-medium text-amber-800">
                    Missing clinic context
                  </p>
                  <p className="text-sm text-amber-700 mt-1">
                    Add ?userId=&lt;clinicId&gt; or ?vet_slug=&lt;slug&gt; to the URL, or open this page from the dashboard.
                  </p>
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Statistics Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <div className="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                  <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                </div>
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Total Staff</p>
                <p className="text-2xl font-bold text-gray-900">{staffStats.total}</p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <div className="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center">
                  <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                  </svg>
                </div>
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Doctors</p>
                <p className="text-2xl font-bold text-gray-900">{staffStats.doctors}</p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <div className="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center">
                  <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                  </svg>
                </div>
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Receptionists</p>
                <p className="text-2xl font-bold text-gray-900">{staffStats.receptionists}</p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <div className="w-12 h-12 bg-gradient-to-br from-gray-500 to-gray-600 rounded-xl flex items-center justify-center">
                  <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                </div>
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Admins</p>
                <p className="text-2xl font-bold text-gray-900">{staffStats.admins}</p>
              </div>
            </div>
          </div>
        </div>

        {/* Staff Table Section */}
        <div className="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
          {/* Table Header with Search */}
          <div className="px-6 py-4 border-b border-gray-200 bg-gray-50/50">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <div className="flex-1 max-w-md">
                <div className="relative">
                  <svg className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                  <input
                    type="text"
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    placeholder="Search staff by name, email, or phone..."
                    className="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                  />
                </div>
              </div>
              <div className="flex items-center space-x-2 text-xs text-gray-500">
                <div className="w-2 h-2 rounded-full bg-green-500"></div>
                <span>Role changes allowed for doctors and receptionists</span>
              </div>
            </div>
          </div>

          {/* Table Content */}
          {state.error ? (
            <div className="px-6 py-8 text-center">
              <svg className="w-12 h-12 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <h3 className="text-lg font-medium text-gray-900 mb-2">Unable to load staff</h3>
              <p className="text-gray-600 max-w-md mx-auto">{state.error}</p>
              <button
                onClick={fetchStaff}
                className="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                Try Again
              </button>
            </div>
          ) : state.loading ? (
            <div className="px-6 py-12 text-center">
              <div className="inline-flex items-center justify-center space-x-2">
                <div className="w-4 h-4 bg-blue-600 rounded-full animate-bounce"></div>
                <div className="w-4 h-4 bg-blue-600 rounded-full animate-bounce" style={{ animationDelay: '0.1s' }}></div>
                <div className="w-4 h-4 bg-blue-600 rounded-full animate-bounce" style={{ animationDelay: '0.2s' }}></div>
              </div>
              <p className="mt-4 text-sm text-gray-600">Loading staff members...</p>
            </div>
          ) : filteredStaff.length === 0 ? (
            <div className="px-6 py-12 text-center">
              <svg className="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <h3 className="text-lg font-medium text-gray-900 mb-2">No staff members found</h3>
              <p className="text-gray-600 max-w-md mx-auto mb-6">
                {searchTerm ? 'Try adjusting your search terms.' : 'Get started by adding your first staff member.'}
              </p>
              {!searchTerm && (
                <button
                  onClick={() => setModalOpen(true)}
                  disabled={!hasTarget}
                  className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                >
                  <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                  </svg>
                  Add First Staff Member
                </button>
              )}
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Staff Member</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Contact</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-48">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200 bg-white">
                  {filteredStaff.map((item) => {
                    const key = `${item.type}-${item.id}`;
                    return (
                      <tr key={key} className="hover:bg-gray-50/50 transition-colors duration-150">
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="flex items-center">
                            <div className={`w-10 h-10 rounded-xl flex items-center justify-center text-white text-sm font-semibold ${typeBadgeClass(item.type)}`}>
                              {item.name ? item.name.charAt(0).toUpperCase() : 'U'}
                            </div>
                            <div className="ml-4">
                              <div className="text-sm font-semibold text-gray-900">{item.name || "-"}</div>
                              <div className="text-xs text-gray-500 capitalize">{formatType(item.type)}</div>
                            </div>
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="text-sm text-gray-900">{item.email || "-"}</div>
                          <div className="text-xs text-gray-500">{item.phone || "-"}</div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ${rolePillClass(item.role)}`}>
                            {formatRole(item.role)}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          {item.editable ? (
                            <select
                              className="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                              value={item.role}
                              disabled={roleSaving === key}
                              onChange={(e) => handleRoleUpdate(item, e.target.value)}
                            >
                              {(state.roles.length ? state.roles : DEFAULT_ROLES).map((role) => (
                                <option key={role} value={role}>
                                  {formatRole(role)}
                                </option>
                              ))}
                            </select>
                          ) : (
                            <span className="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-500 border border-gray-200">
                              <svg className="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clipRule="evenodd" />
                              </svg>
                              Locked
                            </span>
                          )}
                          {roleSaving === key && (
                            <div className="mt-1 text-xs text-blue-600">Updating...</div>
                          )}
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>

      {/* Add Staff Modal */}
      {modalOpen && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-fade-in">
          <div className="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 relative animate-scale-in">
            <button
              type="button"
              onClick={() => setModalOpen(false)}
              className="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors duration-200"
              aria-label="Close"
            >
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
            
            <div className="mb-6">
              <h2 className="text-xl font-bold text-gray-900">Add Staff Member</h2>
              <p className="mt-1 text-sm text-gray-600">Create a new staff profile for your clinic</p>
            </div>

            <form className="space-y-5" onSubmit={handleAddStaff}>
              <div>
                <label className="block text-sm font-semibold text-gray-900 mb-2">
                  Full Name <span className="text-red-500">*</span>
                </label>
                <input
                  name="name"
                  type="text"
                  value={form.name}
                  onChange={(e) => setForm((prev) => ({ ...prev, name: e.target.value }))}
                  required
                  className="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                  placeholder="Enter full name"
                />
              </div>

              <div className="grid grid-cols-1 gap-5">
                <div>
                  <label className="block text-sm font-semibold text-gray-900 mb-2">
                    Email Address
                  </label>
                  <input
                    name="email"
                    type="email"
                    value={form.email}
                    onChange={(e) => setForm((prev) => ({ ...prev, email: e.target.value }))}
                    className="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                    placeholder="optional@clinic.com"
                  />
                </div>
                <div>
                  <label className="block text-sm font-semibold text-gray-900 mb-2">
                    Phone Number
                  </label>
                  <input
                    name="phone"
                    type="text"
                    value={form.phone}
                    onChange={(e) => setForm((prev) => ({ ...prev, phone: e.target.value }))}
                    className="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                    placeholder="+91 99999 00000"
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-900 mb-2">
                  Role
                </label>
                <select
                  name="role"
                  value={form.role}
                  onChange={(e) => setForm((prev) => ({ ...prev, role: e.target.value }))}
                  className="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                >
                  <option value="receptionist">Receptionist</option>
                  <option value="doctor">Doctor</option>
                </select>
              </div>

              <div className="flex flex-col sm:flex-row gap-3 pt-2">
                <button
                  type="button"
                  onClick={() => setModalOpen(false)}
                  className="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-xl text-sm font-semibold hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={saving}
                  className="flex-1 px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl text-sm font-semibold hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg shadow-blue-500/25"
                >
                  {saving ? (
                    <span className="flex items-center justify-center">
                      <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>
                      Saving...
                    </span>
                  ) : (
                    "Save Staff Member"
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default StaffManagement;