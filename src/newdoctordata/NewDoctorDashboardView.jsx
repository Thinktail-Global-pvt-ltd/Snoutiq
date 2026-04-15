import logo from "../assets/images/logo.png";
import {
  Bell,
  IndianRupee,
  Plus,
  Search,
  Calendar,
  Upload,
} from "lucide-react";
import { useNavigate } from "react-router-dom";
import { useNewDoctorAuth } from "./NewDoctorAuth";

export default function NewDoctorDashboardView() {
  const navigate = useNavigate();
  const { auth } = useNewDoctorAuth();

  const authFollowUps =
    auth?.follow_ups ||
    auth?.followUps ||
    auth?.doctor?.follow_ups ||
    auth?.raw_profile?.follow_ups ||
    [];

  const followUps = Array.isArray(authFollowUps) ? authFollowUps : [];

  return (
    <div className="min-h-screen w-full bg-[#FCFCFC]">
      {/* FULL WIDTH HEADER */}
      <div className="sticky top-0 z-20 w-full bg-white border-b border-gray-100">
        <div className="flex items-center justify-between px-4 py-4">
          <img src={logo} alt="logo" className="h-4 w-auto object-contain" />

          <button
            className="relative flex h-10 w-10 items-center justify-center rounded-full active:scale-95 transition"
            onClick={() => navigate("/counsltflow/notifications")}
            type="button"
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
              {auth?.revenue || auth?.dashboard?.revenue || "0"}
            </h2>
          </div>

          <div className="rounded-2xl bg-blue-100 p-4">
            <p className="text-xs font-medium text-gray-600">Follow-ups</p>
            <h2 className="mt-2 text-xl font-bold text-blue-700">
              {followUps.length} Active
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

        {/* CSV UPLOAD */}
        {/* <div className="mb-5">
          <h2 className="mb-3 text-sm font-semibold text-gray-700">
            Import Data
          </h2>

          <div className="flex items-center justify-between rounded-2xl border border-dashed border-gray-200 bg-white p-4">
            <div className="flex items-center gap-3">
              <div className="rounded-xl bg-gray-100 p-2">
                <Upload size={18} className="text-gray-600" />
              </div>

              <div>
                <p className="text-sm font-semibold text-gray-800">
                  Upload CSV
                </p>
                <p className="text-xs text-gray-500">
                  Import clients & history
                </p>
              </div>
            </div>

            <button
              className="text-sm font-semibold text-green-600"
              type="button"
            >
              Choose File
            </button>
          </div>
        </div> */}

        {/* FOLLOW UPS */}
        <div>
          <h2 className="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-700">
            <Calendar size={16} />
            Upcoming Follow-ups
          </h2>

          {followUps.length > 0 ? (
            <div className="space-y-3">
              {followUps.map((item, index) => (
                <div
                  key={item.id || index}
                  className="rounded-2xl border border-gray-100 bg-white p-4"
                >
                  <p className="font-semibold text-gray-900">
                    {item.pet_name || item.petName || "Pet"}
                  </p>
                  <p className="mt-1 text-xs text-gray-500">
                    {item.parent_name ||
                      item.parentName ||
                      item.owner_name ||
                      "Pet Parent"}
                  </p>
                  <p className="mt-2 text-sm text-gray-600">
                    {item.schedule_label ||
                      item.follow_up_at ||
                      item.date_time ||
                      item.date ||
                      "Upcoming"}
                  </p>
                </div>
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
    </div>
  );
}