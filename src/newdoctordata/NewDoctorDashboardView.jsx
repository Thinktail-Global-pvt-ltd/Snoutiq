import logo from "../assets/images/logo.png";
import {
  Bell,
  IndianRupee,
  Plus,
  Search,
  Calendar,
  X,
  Phone,
  Mail,
  MapPin,
  PawPrint,
  Power,
} from "lucide-react";
import Swal from "sweetalert2";
import { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import { useNewDoctorAuth } from "./NewDoctorAuth";

const FOLLOW_UP_USERS_URL =
  "https://snoutiq.com/backend/api/doctor/follow-up-users";

const normalizeId = (value) => {
  const next = String(value ?? "").trim();
  return next && next !== "null" && next !== "undefined" ? next : "";
};

const formatValue = (value, fallback = "Not available") => {
  const next = String(value ?? "").trim();
  return next ? next : fallback;
};

const formatFollowUpDate = (item) => {
  const raw =
    item?.follow_up_prescription?.follow_up_date ||
    item?.follow_up_at ||
    item?.date_time ||
    item?.date ||
    "";

  if (!raw) return "Upcoming";

  const parsed = new Date(raw);
  if (Number.isNaN(parsed.getTime())) return raw;

  return parsed.toLocaleDateString("en-IN", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
};

const formatFollowUpType = (item) => {
  const raw = String(item?.follow_up_prescription?.follow_up_type || "")
    .trim()
    .toLowerCase();

  if (!raw) return "Not available";
  if (raw === "clinic" || raw === "in_clinic") return "In-Clinic";
  if (raw === "online") return "Online";
  return raw.replace(/_/g, " ").replace(/\b\w/g, (char) => char.toUpperCase());
};

const formatAgeGender = (item) => {
  const values = [item?.pet_age, item?.pet_gender]
    .map((value) => String(value ?? "").trim())
    .filter(Boolean);

  return values.length ? values.join(" / ") : "Not available";
};

function FollowUpDetailsModal({ item, onClose }) {
  if (!item) return null;

  const prescription = item.follow_up_prescription || {};

  return (
    <div className="fixed inset-0 z-[120] bg-black/50 backdrop-blur-[2px]">
      <div className="flex min-h-full items-end justify-center md:items-center md:p-4">
        <div className="w-full max-w-[430px] overflow-hidden rounded-t-[28px] bg-[#FCFCFC] shadow-[0_-10px_40px_rgba(15,23,42,0.18)] md:rounded-[28px] md:shadow-[0_24px_70px_rgba(15,23,42,0.22)]">
          <div className="flex justify-center pt-3">
            <div className="h-1.5 w-12 rounded-full bg-[rgba(20,33,61,0.18)]" />
          </div>

          <div className="relative border-b border-gray-100 px-5 pb-4 pt-3">
            <button
              type="button"
              onClick={onClose}
              className="absolute right-4 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-[#f3f4f6] text-[#667085] active:scale-95"
            >
              <X size={18} />
            </button>

            <p className="text-[11px] font-bold uppercase tracking-[0.16em] text-[#16a34a]">
              Follow-up details
            </p>

            <h2 className="mt-2 text-[22px] font-bold leading-tight text-[#0f172a]">
              {formatValue(item.pet_name, "Pet")}
            </h2>

            <p className="mt-1 text-[14px] text-[#667085]">
              Review pet parent and prescription follow-up details
            </p>
          </div>

          <div className="max-h-[70vh] overflow-y-auto px-5 py-4 space-y-4">
            <div className="rounded-[20px] bg-[#f8fafc] p-4">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <p className="text-[10px] text-[#98a2b3]">Follow-up date</p>
                  <p className="mt-1 text-[14px] font-semibold text-[#0f172a]">
                    {formatFollowUpDate(item)}
                  </p>
                </div>

                <div>
                  <p className="text-[10px] text-[#98a2b3]">Follow-up type</p>
                  <p className="mt-1 text-[14px] font-semibold text-[#0f172a]">
                    {formatFollowUpType(item)}
                  </p>
                </div>
              </div>
            </div>

            <div className="rounded-[20px] border border-[#e5e7eb] bg-white p-4">
              <p className="text-[11px] font-bold uppercase tracking-[0.12em] text-[#98a2b3]">
                Pet Parent
              </p>

              <div className="mt-3 space-y-3">
                <div className="flex items-start gap-3">
                  <div className="mt-0.5 rounded-full bg-[#eefbf2] p-2 text-[#22c55e]">
                    <PawPrint size={14} />
                  </div>
                  <div>
                    <p className="text-[15px] font-semibold text-[#0f172a]">
                      {formatValue(item.name, "Pet Parent")}
                    </p>
                    <p className="mt-1 text-[13px] text-[#667085]">
                      Parent name
                    </p>
                  </div>
                </div>

                <div className="flex items-start gap-3">
                  <div className="mt-0.5 rounded-full bg-[#f8fafc] p-2 text-[#667085]">
                    <Phone size={14} />
                  </div>
                  <div>
                    <p className="text-[14px] font-medium text-[#0f172a]">
                      {formatValue(item.phone)}
                    </p>
                    <p className="mt-1 text-[13px] text-[#667085]">Phone</p>
                  </div>
                </div>

                <div className="flex items-start gap-3">
                  <div className="mt-0.5 rounded-full bg-[#f8fafc] p-2 text-[#667085]">
                    <Mail size={14} />
                  </div>
                  <div>
                    <p className="text-[14px] font-medium text-[#0f172a] break-all">
                      {formatValue(item.email)}
                    </p>
                    <p className="mt-1 text-[13px] text-[#667085]">Email</p>
                  </div>
                </div>

              </div>
            </div>

            <div className="rounded-[20px] border border-[#e5e7eb] bg-white p-4">
              <p className="text-[11px] font-bold uppercase tracking-[0.12em] text-[#98a2b3]">
                Pet Details
              </p>

              <div className="mt-3 grid grid-cols-2 gap-3">
                <div className="rounded-[16px] bg-[#f8fafc] px-3 py-3">
                  <p className="text-[11px] text-[#98a2b3]">Pet name</p>
                  <p className="mt-1 text-[14px] font-semibold text-[#0f172a]">
                    {formatValue(item.pet_name)}
                  </p>
                </div>

                <div className="rounded-[16px] bg-[#f8fafc] px-3 py-3">
                  <p className="text-[11px] text-[#98a2b3]">Breed</p>
                  <p className="mt-1 text-[14px] font-semibold text-[#0f172a]">
                    {formatValue(item.breed)}
                  </p>
                </div>

                <div className="rounded-[16px] bg-[#f8fafc] px-3 py-3">
                  <p className="text-[11px] text-[#98a2b3]">Age / Gender</p>
                  <p className="mt-1 text-[14px] font-semibold text-[#0f172a]">
                    {formatAgeGender(item)}
                  </p>
                </div>
              </div>
            </div>

            <div className="rounded-[20px] border border-[#e5e7eb] bg-white p-4">
              <p className="text-[11px] font-bold uppercase tracking-[0.12em] text-[#98a2b3]">
                Follow-up Prescription
              </p>

              <div className="mt-3 rounded-[16px] bg-[#f8fafc] px-3 py-3">
                <p className="text-[11px] text-[#98a2b3]">Follow-up notes</p>
                <p className="mt-1 text-[14px] font-semibold text-[#0f172a]">
                  {formatValue(prescription.follow_up_notes)}
                </p>
              </div>
            </div>
          </div>

          <div className="border-t border-gray-100 px-5 py-4">
            <button
              type="button"
              onClick={onClose}
              className="flex h-[50px] w-full items-center justify-center rounded-[16px] bg-[#16a34a] px-4 text-[15px] font-bold text-white shadow-[0_10px_22px_rgba(22,163,74,0.22)] active:scale-[0.99] transition-transform"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

export default function NewDoctorDashboardView() {
  const navigate = useNavigate();
  const { auth, clearAuth } = useNewDoctorAuth();

  const doctorId = normalizeId(
    auth?.doctor_id || auth?.doctor?.id || auth?.doctor?.doctor_id,
  );

  const [followUps, setFollowUps] = useState([]);
  const [followUpsCount, setFollowUpsCount] = useState(0);
  const [followUpsRevenue, setFollowUpsRevenue] = useState(0);
  const [isLoadingFollowUps, setIsLoadingFollowUps] = useState(false);
  const [followUpsError, setFollowUpsError] = useState("");
  const [selectedFollowUp, setSelectedFollowUp] = useState(null);

  useEffect(() => {
    let active = true;
    const controller = new AbortController();

    const fetchFollowUps = async () => {
      if (!doctorId) {
        if (active) {
          setFollowUps([]);
          setFollowUpsCount(0);
          setFollowUpsRevenue(0);
        }
        return;
      }

      try {
        setIsLoadingFollowUps(true);
        setFollowUpsError("");

        const url = new URL(FOLLOW_UP_USERS_URL);
        url.searchParams.set("doctor_id", doctorId);

        const headers = {
          Accept: "application/json",
        };

        const authToken = auth?.token || auth?.access_token;
        if (authToken) {
          headers.Authorization = `Bearer ${authToken}`;
        }

        const response = await fetch(url.toString(), {
          method: "GET",
          headers,
          signal: controller.signal,
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok || payload?.success === false) {
          throw new Error(
            payload?.message || "Failed to fetch follow-up users.",
          );
        }

        if (!active) return;

        const nextData = Array.isArray(payload?.data) ? payload.data : [];
        setFollowUps(nextData);
        setFollowUpsCount(
          Number.isFinite(Number(payload?.count))
            ? Number(payload.count)
            : nextData.length,
        );
        setFollowUpsRevenue(
          Number.isFinite(Number(payload?.total_earnings_sum))
            ? Number(payload.total_earnings_sum)
            : 0,
        );
      } catch (error) {
        if (error?.name === "AbortError") return;
        if (!active) return;

        setFollowUps([]);
        setFollowUpsCount(0);
        setFollowUpsRevenue(0);
        setFollowUpsError(
          error?.message || "Unable to load follow-up users right now.",
        );
      } finally {
        if (active) {
          setIsLoadingFollowUps(false);
        }
      }
    };

    fetchFollowUps();

    return () => {
      active = false;
      controller.abort();
    };
  }, [auth?.access_token, auth?.token, doctorId]);

  const revenueLabel = useMemo(() => {
    if (Number.isFinite(followUpsRevenue) && followUpsRevenue > 0) {
      return String(followUpsRevenue);
    }
    return String(auth?.revenue || auth?.dashboard?.revenue || "0");
  }, [auth?.dashboard?.revenue, auth?.revenue, followUpsRevenue]);

  const handleLogout = async () => {
    const result = await Swal.fire({
      title: "Logout?",
      text: "Are you sure you want to logout?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Logout",
      cancelButtonText: "Cancel",
      confirmButtonColor: "#dc2626",
      cancelButtonColor: "#16a34a",
      reverseButtons: true,
    });

    if (!result.isConfirmed) return;

    clearAuth();
    navigate("/counsltflow/login", { replace: true });
  };

  return (
    <div className="min-h-screen w-full bg-[#FCFCFC]">
      {/* FULL WIDTH HEADER */}
      <div className="flex items-center justify-between px-4 py-4">
        <img src={logo} alt="logo" className="h-4 w-auto object-contain" />

        <div className="flex items-center gap-2">
          <button
            className="flex h-10 w-10 items-center justify-center rounded-full bg-red-50 active:scale-95 transition"
            onClick={handleLogout}
            type="button"
            aria-label="Logout"
            title="Logout"
          >
            <Power size={20} className="text-red-600" />
          </button>

          <button
            className="relative flex h-10 w-10 items-center justify-center rounded-full active:scale-95 transition"
            onClick={() => navigate("/counsltflow/notifications")}
            type="button"
            aria-label="Notifications"
            title="Notifications"
          >
            <Bell size={20} className="text-gray-700" />
            <span className="absolute top-2.5 right-2.5 w-2 h-2 bg-red-500 rounded-full"></span>
          </button>
        </div>
      </div>
      {/* FULL SCREEN CONTENT */}
      <div className="w-full px-4 pt-4 pb-6">
        {/* WELCOME */}
        <div className="mb-5">
          <h1 className="text-base font-semibold text-gray-600">
            Welcome Back 👋
          </h1>
          <p className="mt-1 text-2xl font-bold text-gray-900 leading-tight">
            {auth?.doctor?.doctor_name || auth?.extras?.full_name || "Doctor"}
          </p>
        </div>

        {/* STATS */}
        <div className="grid grid-cols-2 gap-3 mb-5">
          <div className="rounded-2xl bg-green-100 p-4">
            <p className="text-xs font-medium text-gray-600">Revenue</p>
            <h2 className="mt-2 flex items-center gap-1 text-xl font-bold text-green-700">
              <IndianRupee size={16} />
              {revenueLabel}
            </h2>
          </div>

          <div className="rounded-2xl bg-blue-100 p-4">
            <p className="text-xs font-medium text-gray-600">Follow-ups</p>
            <h2 className="mt-2 text-xl font-bold text-blue-700">
              {followUpsCount} Active
            </h2>
          </div>
        </div>

        {/* QUICK ACTIONS */}
        <div className="grid grid-cols-2 gap-3 mb-5">
          <button
            className="rounded-2xl bg-green-500 p-5 text-white flex flex-col items-center justify-center text-center active:scale-[0.98] transition"
            onClick={() => navigate("/counsltflow/new-request")}
            type="button"
          >
            <Plus size={22} />
            <p className="mt-2 text-sm font-semibold">New Request</p>
          </button>

          <button
            className="rounded-2xl bg-white p-5 flex flex-col items-center justify-center text-center border border-gray-100 shadow-sm active:scale-[0.98] transition"
            onClick={() => navigate("/counsltflow/search")}
            type="button"
          >
            <Search size={22} className="text-gray-700" />
            <p className="mt-2 text-sm font-semibold text-gray-700">
              Search Parent
            </p>
          </button>
        </div>

        {/* FOLLOW UPS */}
        <div>
          <h2 className="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-700">
            <Calendar size={16} />
            Upcoming Follow-ups
          </h2>

          {isLoadingFollowUps ? (
            <div className="rounded-2xl border border-gray-100 bg-white p-6 text-center">
              <p className="text-sm font-semibold text-gray-800">
                Loading follow-ups...
              </p>
            </div>
          ) : followUpsError ? (
            <div className="rounded-2xl border border-red-100 bg-red-50 p-6 text-center">
              <p className="text-sm font-semibold text-red-700">
                {followUpsError}
              </p>
            </div>
          ) : followUps.length > 0 ? (
            <div className="space-y-3">
              {followUps.map((item, index) => (
                <button
                  key={item.id || item.follow_up_prescription?.id || index}
                  type="button"
                  onClick={() => setSelectedFollowUp(item)}
                  className="w-full rounded-[22px] border border-[#e5e7eb] bg-white px-4 py-4 text-left shadow-[0_8px_24px_rgba(15,23,42,0.06)] active:scale-[0.99] transition"
                >
                  <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                      <p className="truncate text-[15px] font-bold text-[#0f172a]">
                        {formatValue(item.pet_name, "Pet")}
                      </p>
                      <p className="mt-1 text-[12px] text-[#667085]">
                        {formatValue(item.name, "Pet Parent")}
                      </p>
                    </div>

                    <div className="shrink-0 rounded-full bg-[#eefbf2] px-2.5 py-1 text-[11px] font-semibold text-[#16a34a]">
                      {formatFollowUpType(item)}
                    </div>
                  </div>

                  <div className="mt-3 grid grid-cols-2 gap-2">
                    <div className="rounded-[14px] bg-[#f8fafc] px-3 py-2.5">
                      <p className="text-[10px] text-[#98a2b3]">Follow-up date</p>
                      <p className="mt-1 text-[13px] font-semibold text-[#0f172a]">
                        {formatFollowUpDate(item)}
                      </p>
                    </div>

                    <div className="rounded-[14px] bg-[#f8fafc] px-3 py-2.5">
                      <p className="text-[10px] text-[#98a2b3]">Breed</p>
                      <p className="mt-1 truncate text-[13px] font-semibold text-[#0f172a]">
                        {formatValue(item.breed)}
                      </p>
                    </div>

                    <div className="rounded-[14px] bg-[#f8fafc] px-3 py-2.5">
                      <p className="text-[10px] text-[#98a2b3]">Phone</p>
                      <p className="mt-1 text-[13px] font-semibold text-[#0f172a]">
                        {formatValue(item.phone)}
                      </p>
                    </div>

                    <div className="rounded-[14px] bg-[#f8fafc] px-3 py-2.5">
                      <p className="text-[10px] text-[#98a2b3]">Age / Gender</p>
                      <p className="mt-1 truncate text-[13px] font-semibold text-[#0f172a]">
                        {formatAgeGender(item)}
                      </p>
                    </div>
                  </div>

                  <p className="mt-3 text-[11px] font-semibold text-[#16a34a]">
                    Tap to view full details
                  </p>
                </button>
              ))}
            </div>
          ) : (
            <div className="rounded-2xl border border-gray-100 bg-white p-6 text-center">
              <p className="text-sm font-semibold text-gray-800">
                No follow-ups
              </p>
              <p className="mt-1 text-xs text-gray-500">
                No upcoming follow-ups available right now.
              </p>
            </div>
          )}
        </div>

        <div className="h-16"></div>
      </div>

      <FollowUpDetailsModal
        item={selectedFollowUp}
        onClose={() => setSelectedFollowUp(null)}
      />
    </div>
  );
}
