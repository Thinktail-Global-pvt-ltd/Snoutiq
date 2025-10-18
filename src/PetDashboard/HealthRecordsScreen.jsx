import { useCallback, useEffect, useState } from "react";
import { useAuth } from "../auth/AuthContext";
import toast from "react-hot-toast";

// Cache implementation
const CACHE_KEYS = {
  APPOINTMENTS: "appointments_cache",
  PRESCRIPTIONS: "prescriptions_cache",
  TIMESTAMP: "cache_timestamp",
};

const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes

const cacheData = (key, data) => {
  try {
    const cacheItem = {
      data,
      timestamp: Date.now(),
    };
    // Using in-memory storage instead of localStorage
    window._appCache = window._appCache || {};
    window._appCache[key] = cacheItem;
  } catch (error) {
    console.error("Cache save error:", error);
  }
};

const getCachedData = (key) => {
  try {
    if (!window._appCache) return null;
    const cached = window._appCache[key];
    if (cached) {
      const { data, timestamp } = cached;
      if (Date.now() - timestamp < CACHE_DURATION) {
        return data;
      }
    }
  } catch (error) {
    console.error("Cache read error:", error);
  }
  return null;
};

const HealthRecordsScreen = ({ navigation }) => {
  const { user } = useAuth();
  const [expandedId, setExpandedId] = useState(null);
  const [expandedType, setExpandedType] = useState(null);
  const [appointments, setAppointments] = useState([]);
  const [prescriptions, setPrescriptions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [activeTab, setActiveTab] = useState("appointments");
  const [downloadingImages, setDownloadingImages] = useState({});

  // Fetch appointments with cache
  const fetchAppointments = useCallback(
    async (useCache = true) => {
      if (!user?.id) return;

      try {
        let appointmentsData = null;

        if (useCache) {
          appointmentsData = getCachedData(CACHE_KEYS.APPOINTMENTS);
          if (appointmentsData) {
            setAppointments(appointmentsData);
            return appointmentsData;
          }
        }

        const response = await fetch(
          `https://snoutiq.com/backend/api/users/${user.id}/orders`,
          {
            headers: {
              "Content-Type": "application/json",
              Accept: "application/json",
            },
          }
        );

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const text = await response.text();
        const cleanText = text.trim().replace(/^\uFEFF/, "");

        let result;
        try {
          result = JSON.parse(cleanText);
        } catch (e) {
          console.error("JSON parse failed:", e, "Raw text:", cleanText);
          setAppointments([]);
          return;
        }

        const finalAppointments = Array.isArray(result.orders)
          ? result.orders
          : [];
        setAppointments(finalAppointments);

        cacheData(CACHE_KEYS.APPOINTMENTS, finalAppointments);

        return finalAppointments;
      } catch (error) {
        console.error("Error fetching appointments:", error);

        const cachedData = getCachedData(CACHE_KEYS.APPOINTMENTS);
        if (cachedData) {
          setAppointments(cachedData);
        } else {
          setAppointments([]);
        }
      }
    },
    [user?.id]
  );

  // Fetch prescriptions with cache
  const fetchPrescriptions = useCallback(
    async (useCache = true) => {
      if (!user?.id) return;

      try {
        // Try cache first (if allowed)
        if (useCache) {
          const cached = getCachedData(CACHE_KEYS.PRESCRIPTIONS);
          if (cached?.length) {
            setPrescriptions(cached);
            console.log("📦 Loaded prescriptions from cache");
            return cached;
          }
        }

        console.log("🌐 Fetching prescriptions from API...");

        const response = await fetch(
          `https://snoutiq.com/backend/api/prescriptions?user_id=${user.id}`,
          {
            headers: {
              "Content-Type": "application/json",
              Accept: "application/json",
            },
          }
        );

        const text = await response.text();
        const cleanText = text.trim().replace(/^\uFEFF/, "");

        let json;
        try {
          json = JSON.parse(cleanText);
        } catch (err) {
          console.error("❌ JSON parse failed:", err, "Raw text:", cleanText);
          setPrescriptions([]);
          return [];
        }

        const freshPrescriptions = Array.isArray(json.data) ? json.data : [];

        // Sort newest first
        freshPrescriptions.sort(
          (a, b) => new Date(b.created_at) - new Date(a.created_at)
        );

        setPrescriptions(freshPrescriptions);
        cacheData(CACHE_KEYS.PRESCRIPTIONS, freshPrescriptions);

        console.log("✅ Updated prescriptions:", freshPrescriptions);
        return freshPrescriptions;
      } catch (error) {
        console.error("⚠️ Error fetching prescriptions:", error);

        const cached = getCachedData(CACHE_KEYS.PRESCRIPTIONS);
        if (cached) {
          console.log("📦 Fallback to cached prescriptions");
          setPrescriptions(cached);
          return cached;
        } else {
          setPrescriptions([]);
          return [];
        }
      }
    },
    [user?.id]
  );

  // Fetch all data
  const fetchAllData = useCallback(
    async (useCache = true) => {
      setLoading(true);

      await Promise.all([
        fetchAppointments(useCache),
        fetchPrescriptions(useCache),
      ]);

      setLoading(false);
    },
    [fetchAppointments, fetchPrescriptions]
  );

  useEffect(() => {
    fetchAllData(true); // Use cache on initial load
  }, [fetchAllData]);

  const clearCache = () => {
    window._appCache = {};
  };

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    clearCache();
    await fetchAllData(false);
    setRefreshing(false);
    toast.success("Records refreshed! 🐾");
  }, [fetchAllData]);

  const toggleExpand = (id, type) => {
    if (expandedId === id && expandedType === type) {
      setExpandedId(null);
      setExpandedType(null);
    } else {
      setExpandedId(id);
      setExpandedType(type);
    }
  };

  // Download prescription image function
  const downloadPrescriptionImage = (prescription) => {
    if (!prescription.image_path) {
      toast.error("No prescription image available");
      return;
    }

    const fullImageUrl = `https://snoutiq.com/backend/${prescription.image_path}`;

    setDownloadingImages((prev) => ({ ...prev, [prescription.id]: true }));

    try {
      // Create a temporary link element
      const link = document.createElement("a");
      link.href = fullImageUrl;
      
      // Extract filename from image_path or create a meaningful one
      const pathParts = prescription.image_path.split('/');
      const originalFileName = pathParts[pathParts.length - 1];
      const extension = originalFileName.split('.').pop();
      const fileName = `prescription-${prescription.id}-${
        new Date().toISOString().split("T")[0]
      }.${extension}`;
      
      link.download = fileName;
      link.target = "_blank";
      link.rel = "noopener noreferrer";

      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      toast.success(`Prescription download started! 📄`, {
        duration: 3000,
        icon: "✅",
      });
    } catch (error) {
      console.error("Error downloading image:", error);
      toast.error("Failed to download prescription image. Please try again.");
    } finally {
      // Reset downloading state after a short delay
      setTimeout(() => {
        setDownloadingImages((prev) => ({ ...prev, [prescription.id]: false }));
      }, 1000);
    }
  };

  // Download all prescription images
  const downloadAllPrescriptionImages = () => {
    const prescriptionsWithImages = prescriptions.filter((p) => p.image_path);

    if (prescriptionsWithImages.length === 0) {
      toast.error("No prescription images available to download");
      return;
    }

    toast.success(
      `Starting download of ${prescriptionsWithImages.length} prescription images...`,
      {
        duration: 3000,
      }
    );

    // Download each prescription with a small delay between them
    prescriptionsWithImages.forEach((prescription, index) => {
      setTimeout(() => {
        downloadPrescriptionImage(prescription);
      }, index * 500); // 500ms delay between each download
    });
  };

  // Shimmer loading component
  const ShimmerLoader = () => (
    <div className="space-y-4">
      {[1, 2, 3].map((item) => (
        <div
          key={item}
          className="bg-white rounded-2xl p-5 shadow-lg border border-gray-100 animate-pulse"
        >
          <div className="flex items-start mb-4">
            <div className="w-12 h-12 bg-gray-200 rounded-xl mr-4"></div>
            <div className="flex-1">
              <div className="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
              <div className="h-3 bg-gray-200 rounded w-1/2"></div>
            </div>
          </div>
          <div className="space-y-2">
            <div className="h-3 bg-gray-200 rounded w-full"></div>
            <div className="h-3 bg-gray-200 rounded w-full"></div>
            <div className="h-3 bg-gray-200 rounded w-2/3"></div>
          </div>
        </div>
      ))}
    </div>
  );

  const getStatusColor = (status) => {
    const colors = {
      confirmed: {
        bg: "bg-green-100",
        text: "text-green-800",
        border: "border-green-200",
      },
      routing: {
        bg: "bg-blue-100",
        text: "text-blue-800",
        border: "border-blue-200",
      },
      pending: {
        bg: "bg-yellow-100",
        text: "text-yellow-800",
        border: "border-yellow-200",
      },
      cancelled: {
        bg: "bg-red-100",
        text: "text-red-800",
        border: "border-red-200",
      },
      completed: {
        bg: "bg-gray-100",
        text: "text-gray-800",
        border: "border-gray-200",
      },
    };
    return colors[status] || colors.pending;
  };

  const getStatusIcon = (status) => {
    const icons = {
      confirmed: "✅",
      routing: "⏳",
      pending: "⚠️",
      cancelled: "❌",
      completed: "✅✅",
    };
    return icons[status] || "⚠️";
  };

  const getUrgencyBadge = (urgency) => {
    const badges = {
      high: {
        bg: "bg-red-100",
        text: "text-red-800",
        border: "border-red-200",
      },
      medium: {
        bg: "bg-yellow-100",
        text: "text-yellow-800",
        border: "border-yellow-200",
      },
      low: {
        bg: "bg-green-100",
        text: "text-green-800",
        border: "border-green-200",
      },
    };
    return badges[urgency] || badges.medium;
  };

  const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
    });
  };

  const formatTime = (timeStr) => {
    const [hours, minutes] = timeStr.split(":");
    const hour = parseInt(hours, 10);
    const ampm = hour >= 12 ? "PM" : "AM";
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
  };

  const stripHtml = (html) => {
    return (
      html
        ?.replace(/<[^>]*>/g, "")
        .replace(/&nbsp;/g, " ")
        .trim() || ""
    );
  };

  const renderAppointmentCard = (appointment) => {
    const isExpanded =
      expandedId === appointment.id && expandedType === "appointment";
    const statusColors = getStatusColor(appointment.status);
    const urgencyColors = getUrgencyBadge(appointment.urgency);

    return (
      <div
        key={appointment.id}
        className="bg-white rounded-2xl p-5 shadow-lg border border-gray-100 mb-4"
      >
        <button
          onClick={() => toggleExpand(appointment.id, "appointment")}
          className="w-full text-left focus:outline-none"
        >
          <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between mb-4">
            <div className="flex items-start flex-1">
              <div className="bg-gradient-to-br from-purple-100 to-pink-100 w-12 h-12 rounded-xl flex items-center justify-center mr-4 shadow-sm">
                <span className="text-lg">
                  {appointment.service_type === "video" ? "📹" : "🏥"}
                </span>
              </div>
              <div className="flex-1 min-w-0">
                <div className="flex flex-col sm:flex-row sm:items-center mb-2">
                  <h3 className="font-bold text-gray-900 text-lg truncate flex-1">
                    {appointment.doctor_name}
                  </h3>
                  <div
                    className={`px-3 py-1 rounded-full text-xs font-bold border ${urgencyColors.bg} ${urgencyColors.text} ${urgencyColors.border} sm:ml-2 mt-1 sm:mt-0 inline-flex`}
                  >
                    {appointment.urgency}
                  </div>
                </div>
                <p className="text-gray-600 font-medium text-sm truncate">
                  {appointment.clinic_name}
                </p>
              </div>
            </div>
            <div className="flex items-center justify-between sm:justify-end mt-2 sm:mt-0 sm:ml-4">
              <div
                className={`px-3 py-1 rounded-full text-xs font-bold border flex items-center gap-1 ${statusColors.bg} ${statusColors.text} ${statusColors.border} mr-2`}
              >
                <span>{getStatusIcon(appointment.status)}</span>
                <span>{appointment.status}</span>
              </div>
              <span className="text-gray-400 text-lg">
                {isExpanded ? "▲" : "▼"}
              </span>
            </div>
          </div>

          <div className="bg-gray-50 rounded-xl p-4 flex flex-col sm:flex-row gap-4">
            <div className="flex items-center gap-2 flex-1">
              <span className="text-purple-600">📅</span>
              <span className="font-semibold text-gray-700 text-sm">
                {formatDate(appointment.scheduled_date)}
              </span>
            </div>
            <div className="flex items-center gap-2 flex-1">
              <span className="text-purple-600">⏰</span>
              <span className="font-semibold text-gray-700 text-sm">
                {formatTime(appointment.scheduled_time)}
              </span>
            </div>
          </div>
        </button>

        {isExpanded && (
          <div className="mt-4 pt-4 border-t border-gray-100">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="flex items-start gap-4">
                <div className="bg-purple-100 w-10 h-10 rounded-lg flex items-center justify-center shadow-sm">
                  <span className="text-purple-600 text-sm">👤</span>
                </div>
                <div>
                  <p className="text-xs text-gray-500 font-semibold uppercase tracking-wide mb-1">
                    Doctor
                  </p>
                  <p className="font-bold text-gray-900 capitalize">
                    {appointment.doctor_name}
                  </p>
                </div>
              </div>

              <div className="flex items-start gap-4">
                <div className="bg-green-100 w-10 h-10 rounded-lg flex items-center justify-center shadow-sm">
                  <span className="text-green-600 text-sm">🏢</span>
                </div>
                <div>
                  <p className="text-xs text-gray-500 font-semibold uppercase tracking-wide mb-1">
                    Clinic
                  </p>
                  <p className="font-bold text-gray-900">
                    {appointment.clinic_name}
                  </p>
                </div>
              </div>

              <div className="flex items-start gap-4">
                <div className="bg-blue-100 w-10 h-10 rounded-lg flex items-center justify-center shadow-sm">
                  <span className="text-blue-600 text-sm">📄</span>
                </div>
                <div>
                  <p className="text-xs text-gray-500 font-semibold uppercase tracking-wide mb-1">
                    Service Type
                  </p>
                  <p className="font-bold text-gray-900 capitalize">
                    {appointment.service_type === "video"
                      ? "Video Consultation"
                      : "Clinic Consultation"}
                  </p>
                </div>
              </div>

              <div className="flex items-start gap-4">
                <div className="bg-yellow-100 w-10 h-10 rounded-lg flex items-center justify-center shadow-sm">
                  <span className="text-red-600 text-sm">💳</span>
                </div>
                <div>
                  <p className="text-xs text-gray-500 font-semibold uppercase tracking-wide mb-1">
                    Payment Status
                  </p>
                  <p className="font-bold text-gray-900 capitalize">
                    {appointment.payment_status}
                  </p>
                </div>
              </div>

              <div className="flex items-start gap-4">
                <div className="bg-green-50 w-10 h-10 rounded-lg flex items-center justify-center shadow-sm">
                  <span className="text-green-700 text-sm">💰</span>
                </div>
                <div>
                  <p className="text-xs text-gray-500 font-semibold uppercase tracking-wide mb-1">
                    Final Price
                  </p>
                  <p className="font-bold text-gray-900">
                    {appointment.final_price
                      ? `₹${appointment.final_price}`
                      : "Not Set"}
                  </p>
                </div>
              </div>

              <div className="flex items-start gap-4">
                <div className="bg-purple-50 w-10 h-10 rounded-lg flex items-center justify-center shadow-sm">
                  <span className="text-purple-600 text-sm">⏰</span>
                </div>
                <div>
                  <p className="text-xs text-gray-500 font-semibold uppercase tracking-wide mb-1">
                    Scheduled For
                  </p>
                  <p className="font-bold text-gray-900">
                    {formatDate(appointment.scheduled_for)} at{" "}
                    {formatTime(appointment.scheduled_time)}
                  </p>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    );
  };

  const renderPrescriptionCard = (prescription) => {
    const isExpanded =
      expandedId === prescription.id && expandedType === "prescription";
    const isDownloading = downloadingImages[prescription.id];
    let fullImageUrl = null;
    if (
      prescription.image_path &&
      typeof prescription.image_path === "string"
    ) {
      fullImageUrl = `https://snoutiq.com/backend/${prescription.image_path}`;
    }

    return (
      <div
        key={prescription.id}
        className="bg-white rounded-2xl p-5 shadow-lg border border-gray-100 mb-4"
      >
        <button
          onClick={() => toggleExpand(prescription.id, "prescription")}
          className="w-full text-left focus:outline-none"
        >
          <div className="flex items-start justify-between mb-4">
            <div className="flex items-start flex-1">
              <div className="bg-gradient-to-br from-blue-100 to-cyan-100 w-12 h-12 rounded-xl flex items-center justify-center mr-4 shadow-sm">
                <span className="text-lg">📋</span>
              </div>
              <div className="flex-1">
                <h3 className="font-bold text-gray-900 text-lg">
                  Prescription #{prescription.id}
                </h3>
                <p className="text-gray-600 font-medium text-sm">
                  Issued: {formatDate(prescription.created_at)}
                </p>
              </div>
            </div>
            <span className="text-gray-400 text-lg">
              {isExpanded ? "▲" : "▼"}
            </span>
          </div>

          <div className="bg-gray-50 rounded-xl p-4">
            <p className="text-gray-700 line-clamp-3 font-medium">
              {stripHtml(prescription.content_html) ||
                "No prescription details available"}
            </p>
          </div>
        </button>

        {isExpanded && (
          <div className="mt-4 pt-4 border-t border-gray-100 space-y-4">
            <div className="bg-white rounded-xl border border-gray-200 p-4">
              <h4 className="font-bold text-gray-800 mb-2 text-sm">
                Prescription Details
              </h4>
              <p className="text-gray-700 font-medium leading-relaxed">
                {stripHtml(prescription.content_html) ||
                  "No prescription details available"}
              </p>
            </div>

            {fullImageUrl && (
              <div className="bg-white rounded-xl border border-gray-200 p-4">
                <div className="flex items-center justify-between mb-3">
                  <div className="flex items-center gap-2">
                    <span className="text-gray-500">📷</span>
                    <h4 className="font-bold text-gray-800 text-sm">
                      Prescription Image
                    </h4>
                  </div>
                  <button
                    onClick={(e) => {
                      e.stopPropagation();
                      downloadPrescriptionImage(prescription);
                    }}
                    disabled={isDownloading}
                    className="flex items-center gap-2 px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {isDownloading ? (
                      <>
                        <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        <span>Downloading...</span>
                      </>
                    ) : (
                      <>
                        <span>⬇️</span>
                        <span>Download</span>
                      </>
                    )}
                  </button>
                </div>
                <div className="bg-gray-50 rounded-lg p-4 flex justify-center">
                  <img
                    src={fullImageUrl}
                    alt="Prescription"
                    className="max-w-full h-auto max-h-64 rounded-lg cursor-pointer hover:opacity-90 transition-opacity"
                    onClick={() => window.open(fullImageUrl, "_blank")}
                    onError={(e) => {
                      console.log("Image loading error");
                      e.target.style.display = "none";
                    }}
                  />
                </div>
              </div>
            )}
          </div>
        )}
      </div>
    );
  };

  const prescriptionsWithImages = prescriptions.filter((p) => p.image_path);

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-purple-50">
      {/* Header */}
      <div className="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-4 py-6 sm:px-6 lg:px-8">
        <div className="max-w-7xl mx-auto">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold">Health Records</h1>
              <p className="text-purple-100 mt-1">
                Track appointments and prescriptions
              </p>
            </div>
            <div>
              <button
                className="bg-white border border-gray-200 rounded-xl px-6 py-3 font-semibold text-purple-600 flex items-center gap-2 mx-auto shadow-sm hover:bg-gray-50 transition-colors"
                onClick={onRefresh}
              >
                <span>🔄</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Tab Buttons */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6">
        <div className="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden flex">
          <button
            className={`flex-1 py-3 px-4 text-center font-semibold transition-colors ${
              activeTab === "appointments"
                ? "bg-purple-600 text-white"
                : "bg-white text-gray-600 hover:bg-gray-50"
            }`}
            onClick={() => setActiveTab("appointments")}
          >
            Appointments ({appointments.length})
          </button>
          <button
            className={`flex-1 py-3 px-4 text-center font-semibold transition-colors ${
              activeTab === "prescriptions"
                ? "bg-purple-600 text-white"
                : "bg-white text-gray-600 hover:bg-gray-50"
            }`}
            onClick={() => setActiveTab("prescriptions")}
          >
            Prescriptions ({prescriptions.length})
          </button>
        </div>
      </div>

      {/* Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        {/* Bulk Download Button for Prescriptions */}
        {!loading &&
          activeTab === "prescriptions" &&
          prescriptionsWithImages.length > 0 && (
            <div className="mb-6 flex justify-end">
              <button
                onClick={downloadAllPrescriptionImages}
                disabled={Object.values(downloadingImages).some(
                  (status) => status
                )}
                className="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span>📦</span>
                <span>Download All ({prescriptionsWithImages.length})</span>
              </button>
            </div>
          )}

        {/* Loading State */}
        {loading && !refreshing && <ShimmerLoader />}

        {/* Appointments Section */}
        {!loading &&
          activeTab === "appointments" &&
          appointments.length > 0 && (
            <div className="mb-8">
              <div className="flex items-center mb-6">
                <div className="bg-gradient-to-r from-purple-600 to-purple-700 w-9 h-9 rounded-lg flex items-center justify-center mr-3 shadow-sm">
                  <span className="text-white text-sm">📅</span>
                </div>
                <h2 className="text-2xl font-bold text-gray-900 flex-1">
                  Appointments
                </h2>
                <div className="bg-gray-100 px-3 py-1 rounded-full">
                  <span className="text-xs font-bold text-gray-700">
                    {appointments.length}
                  </span>
                </div>
              </div>
              <div className="space-y-4">
                {appointments.map(renderAppointmentCard)}
              </div>
            </div>
          )}

        {/* Prescriptions Section */}
        {!loading &&
          activeTab === "prescriptions" &&
          prescriptions.length > 0 && (
            <div className="mb-8">
              <div className="flex items-center justify-between mb-6">
                <div className="flex items-center">
                  <div className="bg-gradient-to-r from-blue-600 to-blue-700 w-9 h-9 rounded-lg flex items-center justify-center mr-3 shadow-sm">
                    <span className="text-white text-sm">📋</span>
                  </div>
                  <h2 className="text-2xl font-bold text-gray-900">
                    Prescriptions
                  </h2>
                </div>
                <div className="flex items-center gap-2">
                  {prescriptionsWithImages.length > 0 && (
                    <span className="text-sm text-gray-600">
                      {prescriptionsWithImages.length} with images
                    </span>
                  )}
                  <div className="bg-gray-100 px-3 py-1 rounded-full">
                    <span className="text-xs font-bold text-gray-700">
                      {prescriptions.length}
                    </span>
                  </div>
                </div>
              </div>
              <div className="space-y-4">
                {prescriptions.map(renderPrescriptionCard)}
              </div>
            </div>
          )}

        {/* Empty State */}
        {!loading &&
          ((activeTab === "appointments" && appointments.length === 0) ||
            (activeTab === "prescriptions" && prescriptions.length === 0)) && (
            <div className="text-center py-16">
              <div className="bg-gradient-to-br from-gray-50 to-gray-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                <span className="text-4xl text-gray-400">📁</span>
              </div>
              <h3 className="text-2xl font-bold text-gray-900 mb-2">
                No Records Found
              </h3>
              <p className="text-gray-600 mb-6 max-w-md mx-auto">
                Your{" "}
                {activeTab === "appointments"
                  ? "appointments"
                  : "prescriptions"}{" "}
                will appear here once available
              </p>
              <button
                className="bg-white border border-gray-200 rounded-xl px-6 py-3 font-semibold text-purple-600 flex items-center gap-2 mx-auto shadow-sm hover:bg-gray-50 transition-colors"
                onClick={onRefresh}
              >
                <span>🔄</span>
                Refresh
              </button>
            </div>
          )}

        {/* Refresh indicator */}
        {refreshing && (
          <div className="fixed bottom-4 right-4 bg-purple-600 text-white px-4 py-2 rounded-full shadow-lg flex items-center gap-2">
            <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
            <span className="text-sm font-medium">Refreshing...</span>
          </div>
        )}
      </div>
    </div>
  );
};

export default HealthRecordsScreen;