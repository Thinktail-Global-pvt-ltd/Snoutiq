import React, { useEffect, useMemo, useState } from "react";
import { toast } from "react-hot-toast";
import axios from "../axios";
import { useAuth } from "../auth/AuthContext";

const emptyForm = {
  serviceName: "",
  description: "",
  petType: "Dog",
  price: "",
  duration: "",
  main_service: "in_clinic",
  status: "active",
  serviceCategory: "",
};

const VetServices = () => {
  const { user } = useAuth();
  const [services, setServices] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [deletingId, setDeletingId] = useState(null);
  const [form, setForm] = useState(emptyForm);
  const [editingId, setEditingId] = useState(null);
  const [error, setError] = useState("");
  const [searchTerm, setSearchTerm] = useState("");
  const [filterType, setFilterType] = useState("all");
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [categories, setCategories] = useState([]);
  const [categoriesLoading, setCategoriesLoading] = useState(false);
  const [categoriesError, setCategoriesError] = useState("");

  const resolvedUserId = useMemo(() => {
    return (
      user?.vet_registeration_id ||
      user?.clinic_id ||
      user?.id ||
      Number(localStorage.getItem("userId")) ||
      null
    );
  }, [user]);

  const resolvedVetSlug = user?.slug || user?.vet_slug || null;

  const fetchServices = async () => {
    if (!resolvedUserId && !resolvedVetSlug) {
      setError("Missing clinic/user id to load services.");
      setLoading(false);
      return;
    }
    setLoading(true);
    setError("");
    try {
      const res = await axios.get("https://snoutiq.com/backend/api/groomer/services", {
        params: {
          user_id: resolvedUserId || undefined,
          vet_slug: resolvedVetSlug || undefined,
        },
        withCredentials: true,
      });
      const data = res?.data?.data;
      setServices(Array.isArray(data) ? data : []);
    } catch (err) {
      console.error("Failed to load services", err);
      setError("Unable to load services. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  const fetchCategories = async () => {
    if (!resolvedUserId && !resolvedVetSlug) {
      setCategories([]);
      setCategoriesError("Missing clinic/user id to load service categories.");
      return;
    }

    setCategoriesLoading(true);
    setCategoriesError("");
    try {
      const res = await axios.get("https://snoutiq.com/backend/api/groomer/service_categroy", {
        params: {
          user_id: resolvedUserId || undefined,
          vet_slug: resolvedVetSlug || undefined,
        },
        withCredentials: true,
      });
      const data = res?.data?.data;
      const nextCategories = Array.isArray(data) ? data : [];
      setCategories(nextCategories);

      if (nextCategories.length > 0) {
        setForm((prev) => {
          if (prev.serviceCategory) return prev;
          return { ...prev, serviceCategory: String(nextCategories[0].id) };
        });
      }
    } catch (err) {
      console.error("Failed to load service categories", err);
      setCategoriesError("Unable to load service categories.");
    } finally {
      setCategoriesLoading(false);
    }
  };

  useEffect(() => {
    fetchServices();
    fetchCategories();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [resolvedUserId, resolvedVetSlug]);

  const handleChange = (field, value) => {
    setForm((prev) => ({ ...prev, [field]: value }));
  };

  const resetForm = () => {
    setForm({ ...emptyForm, serviceCategory: categories[0]?.id ? String(categories[0].id) : "" });
    setEditingId(null);
    setIsModalOpen(false);
  };

  const openModal = () => {
    // Always start with a clean slate when opening for a new service
    setForm({ ...emptyForm, serviceCategory: categories[0]?.id ? String(categories[0].id) : "" });
    setEditingId(null);
    setIsModalOpen(true);
  };

  const closeModal = () => {
    resetForm();
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!resolvedUserId && !resolvedVetSlug) {
      toast.error("Missing clinic/user id");
      return;
    }

    const payload = {
      ...form,
      price: Number(form.price || 0),
      duration: Number(form.duration || 0),
      serviceCategory: form.serviceCategory ? Number(form.serviceCategory) : null,
      user_id: resolvedUserId,
      vet_slug: resolvedVetSlug,
    };

    if (!payload.serviceName || !payload.petType || !payload.price || !payload.duration || !payload.main_service) {
      toast.error("Please fill all required fields.");
      return;
    }

    if (!payload.serviceCategory) {
      toast.error("Please select a service category.");
      return;
    }

    setSaving(true);
    try {
      if (editingId) {
        await axios.post(`https://snoutiq.com/backend/api/groomer/service/${editingId}/update`, payload, {
          withCredentials: true,
        });
        toast.success("Service updated successfully");
      } else {
        await axios.post("https://snoutiq.com/backend/api/groomer/service", payload, { withCredentials: true });
        toast.success("Service added successfully");
      }
      resetForm();
      fetchServices();
    } catch (err) {
      console.error("Save failed", err);
      const msg =
        err?.response?.data?.message ||
        err?.response?.data?.errors?.[0] ||
        "Unable to save service";
      toast.error(msg);
    } finally {
      setSaving(false);
    }
  };

  const handleEdit = (svc) => {
    setEditingId(svc.id);
    setForm({
      serviceName: svc.name || "",
      description: svc.description || "",
      petType: svc.pet_type || "Dog",
      price: svc.price || "",
      duration: svc.duration || "",
      main_service: svc.main_service || "in_clinic",
      status: svc.status || "active",
      serviceCategory: svc.groomer_service_category_id ? String(svc.groomer_service_category_id) : "",
    });
    setIsModalOpen(true);
  };

  const handleDelete = async (id) => {
    if (!resolvedUserId && !resolvedVetSlug) {
      toast.error("Missing clinic/user id");
      return;
    }
    const confirm = window.confirm("Are you sure you want to delete this service? This action cannot be undone.");
    if (!confirm) return;

    setDeletingId(id);
    try {
      await axios.delete(`https://snoutiq.com/backend/api/groomer/service/${id}`, {
        params: { user_id: resolvedUserId, vet_slug: resolvedVetSlug },
        withCredentials: true,
      });
      setServices((prev) => prev.filter((s) => s.id !== id));
      toast.success("Service deleted successfully");
    } catch (err) {
      console.error("Delete failed", err);
      toast.error("Unable to delete service");
    } finally {
      setDeletingId(null);
    }
  };

  // Filter services based on search and filter
  const filteredServices = useMemo(() => {
    return services.filter(service => {
      const matchesSearch = service.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                           service.description?.toLowerCase().includes(searchTerm.toLowerCase());
      const matchesFilter = filterType === "all" || 
                           service.main_service === filterType ||
                           service.pet_type === filterType ||
                           service.status === filterType;
      return matchesSearch && matchesFilter;
    });
  }, [services, searchTerm, filterType]);

  const getServiceTypeIcon = (type) => {
    switch (type) {
      case 'in_clinic': return '🏥';
      case 'online': return '💻';
      case 'home_visit': return '🏠';
      default: return '🎯';
    }
  };

  const getPetTypeIcon = (type) => {
    switch (type) {
      case 'Dog': return '🐕';
      case 'Cat': return '🐈';
      default: return '🐾';
    }
  };

  const stats = {
    total: services.length,
    active: services.filter(s => s.status === 'active').length,
    inClinic: services.filter(s => s.main_service === 'in_clinic').length,
    online: services.filter(s => s.main_service === 'online').length,
  };

  return (
    <>
      <div className="min-h-screen bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          {/* Header */}
          <div className="mb-8">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
              <div>
                <h1 className="text-2xl font-bold text-gray-900">Service Management</h1>
                <p className="mt-1 text-sm text-gray-600">
                  Manage your veterinary services and offerings
                </p>
              </div>
              <div className="mt-4 sm:mt-0">
                <button
                  onClick={openModal}
                  className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                >
                  <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                  </svg>
                  Add New Service
                </button>
              </div>
            </div>
          </div>

          {/* Stats Cards */}
          <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <div className="bg-white overflow-hidden shadow rounded-lg border border-gray-200">
              <div className="px-4 py-5 sm:p-6">
                <div className="flex items-center">
                  <div className="flex-shrink-0 bg-blue-100 rounded-md p-3">
                    <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                  </div>
                  <div className="ml-5 w-0 flex-1">
                    <dl>
                      <dt className="text-sm font-medium text-gray-500 truncate">Total Services</dt>
                      <dd className="text-lg font-semibold text-gray-900">{stats.total}</dd>
                    </dl>
                  </div>
                </div>
              </div>
            </div>

            <div className="bg-white overflow-hidden shadow rounded-lg border border-gray-200">
              <div className="px-4 py-5 sm:p-6">
                <div className="flex items-center">
                  <div className="flex-shrink-0 bg-green-100 rounded-md p-3">
                    <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <div className="ml-5 w-0 flex-1">
                    <dl>
                      <dt className="text-sm font-medium text-gray-500 truncate">Active Services</dt>
                      <dd className="text-lg font-semibold text-gray-900">{stats.active}</dd>
                    </dl>
                  </div>
                </div>
              </div>
            </div>

            <div className="bg-white overflow-hidden shadow rounded-lg border border-gray-200">
              <div className="px-4 py-5 sm:p-6">
                <div className="flex items-center">
                  <div className="flex-shrink-0 bg-purple-100 rounded-md p-3">
                    <span className="text-lg">🏥</span>
                  </div>
                  <div className="ml-5 w-0 flex-1">
                    <dl>
                      <dt className="text-sm font-medium text-gray-500 truncate">In-Clinic</dt>
                      <dd className="text-lg font-semibold text-gray-900">{stats.inClinic}</dd>
                    </dl>
                  </div>
                </div>
              </div>
            </div>

            <div className="bg-white overflow-hidden shadow rounded-lg border border-gray-200">
              <div className="px-4 py-5 sm:p-6">
                <div className="flex items-center">
                  <div className="flex-shrink-0 bg-orange-100 rounded-md p-3">
                    <span className="text-lg">💻</span>
                  </div>
                  <div className="ml-5 w-0 flex-1">
                    <dl>
                      <dt className="text-sm font-medium text-gray-500 truncate">Online</dt>
                      <dd className="text-lg font-semibold text-gray-900">{stats.online}</dd>
                    </dl>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Main Content */}
          <div className="bg-white shadow-sm rounded-lg border border-gray-200">
            <div className="px-6 py-5 border-b border-gray-200">
              <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <h2 className="text-lg font-semibold text-gray-900">All Services</h2>
                  <p className="mt-1 text-sm text-gray-600">
                    Manage and organize your service offerings
                  </p>
                </div>
                <div className="mt-4 sm:mt-0 flex flex-col sm:flex-row gap-3">
                  <div className="relative">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <svg className="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                      </svg>
                    </div>
                    <input
                      type="text"
                      placeholder="Search services..."
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                      className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                  </div>
                  <select
                    value={filterType}
                    onChange={(e) => setFilterType(e.target.value)}
                    className="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md border"
                  >
                    <option value="all">All Services</option>
                    <option value="in_clinic">In Clinic</option>
                    <option value="online">Online</option>
                    <option value="home_visit">Home Visit</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                  </select>
                </div>
              </div>
            </div>

            <div className="p-6">
              {/* Error State */}
              {error && (
                <div className="mb-6 rounded-md bg-red-50 p-4 border border-red-200">
                  <div className="flex">
                    <div className="flex-shrink-0">
                      <svg className="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </div>
                    <div className="ml-3">
                      <h3 className="text-sm font-medium text-red-800">{error}</h3>
                    </div>
                  </div>
                </div>
              )}

              {/* Loading State */}
              {loading && (
                <div className="flex justify-center items-center py-12">
                  <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                </div>
              )}

              {/* Empty State */}
              {!loading && filteredServices.length === 0 && !error && (
                <div className="text-center py-12">
                  <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <h3 className="mt-2 text-sm font-medium text-gray-900">No services</h3>
                  <p className="mt-1 text-sm text-gray-500">
                    Get started by creating your first service.
                  </p>
                  <div className="mt-6">
                    <button
                      onClick={openModal}
                      className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                      <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                      </svg>
                      Add Service
                    </button>
                  </div>
                </div>
              )}

              {/* Services Grid */}
              {!loading && filteredServices.length > 0 && (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                  {filteredServices.map((svc) => (
                    <div key={svc.id} className="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200">
                      <div className="p-5">
                        <div className="flex items-start justify-between mb-3">
                          <div className="flex items-center space-x-3">
                            <span className="text-2xl">{getServiceTypeIcon(svc.main_service)}</span>
                            <div>
                              <h3 className="font-semibold text-gray-900">{svc.name || "Untitled Service"}</h3>
                              <p className="text-xs text-gray-500 capitalize">
                                {svc.main_service?.replace('_', ' ') || "Service"}
                              </p>
                            </div>
                          </div>
                          <span
                            className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                              svc.status === "active"
                                ? "bg-green-100 text-green-800"
                                : "bg-gray-100 text-gray-800"
                            }`}
                          >
                            {svc.status === "active" ? "Active" : "Inactive"}
                          </span>
                        </div>

                        <p className="text-sm text-gray-600 mb-4 line-clamp-2">
                          {svc.description || "No description provided."}
                        </p>

                        <div className="flex items-center justify-between mb-4">
                          <div className="flex items-center space-x-3 text-sm text-gray-600">
                            <span className="flex items-center">
                              {getPetTypeIcon(svc.pet_type)}
                              <span className="ml-1">{svc.pet_type || "N/A"}</span>
                            </span>
                            <span className="flex items-center">
                              <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                              </svg>
                              {svc.duration ? `${svc.duration} min` : "N/A"}
                            </span>
                          </div>
                          <div className="text-lg font-bold text-gray-900">
                            ${svc.price ?? 0}
                          </div>
                        </div>

                        <div className="flex items-center justify-between pt-4 border-t border-gray-100">
                          <button
                            onClick={() => handleEdit(svc)}
                            className="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors duration-200"
                          >
                            <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit
                          </button>
                          <button
                            onClick={() => handleDelete(svc.id)}
                            disabled={deletingId === svc.id}
                            className="inline-flex items-center text-sm font-medium text-red-600 hover:text-red-700 disabled:opacity-50 transition-colors duration-200"
                          >
                            {deletingId === svc.id ? (
                              <>
                                <svg className="animate-spin -ml-1 mr-1 h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24">
                                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Deleting...
                              </>
                            ) : (
                              <>
                                <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Delete
                              </>
                            )}
                          </button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Modal */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 overflow-y-auto">
          <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            {/* Background overlay */}
            <div 
              className="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
              onClick={closeModal}
            ></div>

            {/* Modal panel */}
            <div className="relative inline-block w-full max-w-2xl px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:p-6">
              {/* Header */}
              <div className="flex items-center justify-between mb-6">
                <div>
                  <h3 className="text-lg font-semibold text-gray-900">
                    {editingId ? "Edit Service" : "Add New Service"}
                  </h3>
                  <p className="mt-1 text-sm text-gray-600">
                    {editingId 
                      ? "Update your service details below."
                      : "Fill in the details to add a new service to your offerings."
                    }
                  </p>
                </div>
                <button
                  onClick={closeModal}
                  className="text-gray-400 hover:text-gray-600 transition-colors duration-200"
                >
                  <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              {/* Form */}
              <form onSubmit={handleSubmit} className="space-y-6">
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                  <div className="sm:col-span-2">
                    <label htmlFor="serviceName" className="block text-sm font-medium text-gray-700">
                      Service Name *
                    </label>
                    <input
                      type="text"
                      id="serviceName"
                      value={form.serviceName}
                      onChange={(e) => handleChange("serviceName", e.target.value)}
                      className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                      placeholder="e.g., General Health Checkup"
                    />
                  </div>

                  <div className="sm:col-span-2">
                    <label htmlFor="description" className="block text-sm font-medium text-gray-700">
                      Description
                    </label>
                    <textarea
                      id="description"
                      rows={3}
                      value={form.description}
                      onChange={(e) => handleChange("description", e.target.value)}
                      className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                      placeholder="Describe what this service includes..."
                    />
                  </div>

                  <div>
                    <label htmlFor="petType" className="block text-sm font-medium text-gray-700">
                      Pet Type *
                    </label>
                    <select
                      id="petType"
                      value={form.petType}
                      onChange={(e) => handleChange("petType", e.target.value)}
                      className="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                      <option value="Dog">Dog</option>
                      <option value="Cat">Cat</option>
                      <option value="Other">Other</option>
                    </select>
                  </div>

                  <div>
                    <label htmlFor="main_service" className="block text-sm font-medium text-gray-700">
                      Service Type *
                    </label>
                    <select
                      id="main_service"
                      value={form.main_service}
                      onChange={(e) => handleChange("main_service", e.target.value)}
                      className="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                      <option value="in_clinic">In Clinic</option>
                      <option value="online">Online</option>
                      <option value="home_visit">Home Visit</option>
                    </select>
                  </div>

                  <div>
                    <label htmlFor="serviceCategory" className="block text-sm font-medium text-gray-700">
                      Service Category *
                    </label>
                    <select
                      id="serviceCategory"
                      value={form.serviceCategory}
                      onChange={(e) => handleChange("serviceCategory", e.target.value)}
                      disabled={categoriesLoading || categories.length === 0}
                      className="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                      {categoriesLoading && <option value="">Loading categories...</option>}
                      {!categoriesLoading && categories.length === 0 && (
                        <option value="">No categories found</option>
                      )}
                      {categories.map((cat) => (
                        <option key={cat.id} value={cat.id}>
                          {cat.name}
                        </option>
                      ))}
                    </select>
                    {categoriesError && (
                      <p className="mt-2 text-sm text-red-600">{categoriesError}</p>
                    )}
                    {!categoriesLoading && categories.length === 0 && !categoriesError && (
                      <p className="mt-2 text-sm text-gray-500">
                        Create a category first to add services.
                      </p>
                    )}
                  </div>

                  <div>
                    <label htmlFor="price" className="block text-sm font-medium text-gray-700">
                      Price ($) *
                    </label>
                    <div className="mt-1 relative rounded-md shadow-sm">
                      <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span className="text-gray-500 sm:text-sm">$</span>
                      </div>
                      <input
                        type="number"
                        id="price"
                        min="0"
                        step="0.01"
                        value={form.price}
                        onChange={(e) => handleChange("price", e.target.value)}
                        className="block w-full pl-7 pr-12 border border-gray-300 rounded-md shadow-sm py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="0.00"
                      />
                    </div>
                  </div>

                  <div>
                    <label htmlFor="duration" className="block text-sm font-medium text-gray-700">
                      Duration (minutes) *
                    </label>
                    <input
                      type="number"
                      id="duration"
                      min="1"
                      value={form.duration}
                      onChange={(e) => handleChange("duration", e.target.value)}
                      className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                      placeholder="30"
                    />
                  </div>

                  <div className="sm:col-span-2">
                    <label htmlFor="status" className="block text-sm font-medium text-gray-700">
                      Status
                    </label>
                    <select
                      id="status"
                      value={form.status}
                      onChange={(e) => handleChange("status", e.target.value)}
                      className="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                      <option value="active">Active</option>
                      <option value="inactive">Inactive</option>
                    </select>
                  </div>
                </div>

                {/* Footer */}
                <div className="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                  <button
                    type="button"
                    onClick={closeModal}
                    className="px-6 py-3 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={saving}
                    className="inline-flex justify-center px-6 py-3 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 transition-colors duration-200"
                  >
                    {saving ? (
                      <>
                        <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                          <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                          <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Saving...
                      </>
                    ) : editingId ? (
                      "Update Service"
                    ) : (
                      "Add Service"
                    )}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}
    </>
  );
};

export default VetServices;
