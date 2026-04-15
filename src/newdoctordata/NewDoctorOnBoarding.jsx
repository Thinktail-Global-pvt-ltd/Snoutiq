'use client';

import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { Loader2 } from "lucide-react";
import logo from "../assets/images/logo.png";
import { useNewDoctorAuth } from "./NewDoctorAuth";

const ONBOARD_URL = "https://snoutiq.com/backend/api/excell-export/import-lite";

export default function NewDoctorOnBoarding() {
  const navigate = useNavigate();
  const { auth, hydrated, mergeAuth } = useNewDoctorAuth();

  const [form, setForm] = useState({
    full_name: "",
    clinic_name: "",
    license_number: "",
    response_time: "",
    payout_preference: "weekly",
    google_review_url: "",
    upi_id: "",
  });

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    if (!hydrated) return;

    if (!auth.phone_verified) {
      navigate("/counsltflow/login", { replace: true });
      return;
    }

    if (auth.onboarding_completed) {
      navigate("/counsltflow/dashboard", { replace: true });
    }
  }, [auth, hydrated, navigate]);

  const handleChange = (e) => {
    const { name, value } = e.target;

    if (name === "full_name") {
      const cleanedValue = value.replace(/^dr\.?\s*/i, "");
      setForm((prev) => ({ ...prev, [name]: cleanedValue }));
      return;
    }

    setForm((prev) => ({ ...prev, [name]: value }));
  };

  const setPayoutPreference = (value) => {
    setForm((prev) => ({ ...prev, payout_preference: value }));
  };

  const getDoctorFullName = () => {
    const trimmedName = form.full_name.trim().replace(/^dr\.?\s*/i, "");
    return trimmedName ? `Dr. ${trimmedName}` : "";
  };

  const handleSubmit = async () => {
    if (!form.full_name.trim()) {
      setError("Please enter full name");
      return;
    }
    if (!form.clinic_name.trim()) {
      setError("Please enter clinic name");
      return;
    }
    if (!form.response_time) {
      setError("Please select response time");
      return;
    }
    if (!form.upi_id.trim()) {
      setError("Please enter UPI ID");
      return;
    }

    setLoading(true);
    setError("");

    try {
      const doctorFullName = getDoctorFullName();

      const payload = {
        full_name: doctorFullName,
        clinic_name: form.clinic_name.trim(),
        doctor_mobile: auth.phone,
        response_time: form.response_time,
        payout_preference: form.payout_preference,
        google_review_url: form.google_review_url.trim(),
        upi_id: form.upi_id.trim(),
      };

      const response = await fetch(ONBOARD_URL, {
        method: "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });

      const data = await response.json();
      console.log("doctor onboarding response:", data);

      if (!response.ok || !data?.success) {
        throw new Error(data?.message || "Onboarding failed");
      }

      mergeAuth({
        onboarding_completed: true,
        phone_verified: true,
        phone_exists: true,
        vet: data?.data?.vet || null,
        doctor: data?.data?.doctor || null,
        raw_profile: data?.data || null,
        extras: {
          license_number: form.license_number.trim(),
          full_name: doctorFullName,
          clinic_name: form.clinic_name.trim(),
          response_time: form.response_time,
          payout_preference: form.payout_preference,
          google_review_url: form.google_review_url.trim(),
          upi_id: form.upi_id.trim(),
        },
      });

      navigate("/counsltflow/dashboard", { replace: true });
    } catch (err) {
      console.error("doctor onboarding error:", err);
      setError(err.message || "Unable to complete setup");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen px-6 py-6 bg-gray-50">
      <div className="mb-8">
        <div className="flex items-center gap-3">
          <img src={logo} alt="logo" className="w-16 h-6 object-contain" />
        </div>

        <div className="text-center mt-6">
          <h1 className="text-2xl font-bold text-gray-900">ConsultFlow</h1>
          <p className="text-sm text-gray-500">Complete your profile</p>
        </div>
      </div>

      <div className="space-y-5">
        <div>
          <label className="text-sm text-gray-600">Full Name</label>

          <div className="w-full mt-1 flex items-center border rounded-lg bg-white focus-within:ring-2 focus-within:ring-green-400">
            <span className="pl-3 pr-1 text-gray-700 font-medium whitespace-nowrap">
              Dr.
            </span>
            <input
              type="text"
              name="full_name"
              value={form.full_name}
              onChange={handleChange}
              placeholder="Enter your full name"
              className="w-full p-3 pl-1 rounded-lg outline-none bg-transparent"
            />
          </div>
        </div>

        <div>
          <label className="text-sm text-gray-600">Clinic Name</label>
          <input
            type="text"
            name="clinic_name"
            value={form.clinic_name}
            onChange={handleChange}
            placeholder="Enter clinic name"
            className="w-full mt-1 p-3 border rounded-lg outline-none focus:ring-2 focus:ring-green-400"
          />
        </div>

        <div>
          <label className="text-sm text-gray-600">License Number</label>
          <input
            type="text"
            name="license_number"
            value={form.license_number}
            onChange={handleChange}
            placeholder="Enter license number"
            className="w-full mt-1 p-3 border rounded-lg outline-none focus:ring-2 focus:ring-green-400"
          />
        </div>

        <div>
          <label className="text-sm text-gray-600">Response Time</label>
          <select
            name="response_time"
            value={form.response_time}
            onChange={handleChange}
            className="w-full mt-1 p-3 border rounded-lg outline-none focus:ring-2 focus:ring-green-400"
          >
            <option value="">Select response time</option>
            <option value="within 15 mins">10 - 15 mins</option>
            <option value="within 30 mins">20 - 30 mins</option>
            <option value="within 40 mins">30 - 40 mins</option>
            <option value="within 60 mins">40 - 60 mins</option>
          </select>
        </div>

        <div>
          <label className="text-sm text-gray-600">Payout</label>
          <div className="flex gap-3 mt-2">
            <button
              type="button"
              onClick={() => setPayoutPreference("weekly")}
              className={`flex-1 py-3 border rounded-lg font-semibold ${
                form.payout_preference === "weekly"
                  ? "bg-green-50 text-green-600 border-green-200"
                  : "text-gray-600 bg-white"
              }`}
            >
              Weekly
            </button>
            <button
              type="button"
              onClick={() => setPayoutPreference("monthly")}
              className={`flex-1 py-3 border rounded-lg font-semibold ${
                form.payout_preference === "monthly"
                  ? "bg-green-50 text-green-600 border-green-200"
                  : "text-gray-600 bg-white"
              }`}
            >
              Monthly
            </button>
          </div>
        </div>

        <div>
          <label className="text-sm text-gray-600">Google Review URL</label>
          <input
            type="url"
            name="google_review_url"
            value={form.google_review_url}
            onChange={handleChange}
            placeholder="Enter Google review link (optional)"
            className="w-full mt-1 p-3 border rounded-lg outline-none focus:ring-2 focus:ring-green-400"
          />
        </div>

        <div>
          <label className="text-sm text-gray-600">UPI ID</label>
          <input
            type="text"
            name="upi_id"
            value={form.upi_id}
            onChange={handleChange}
            placeholder="Enter UPI ID"
            className="w-full mt-1 p-3 border rounded-lg outline-none focus:ring-2 focus:ring-green-400"
          />
        </div>

        {auth.phone ? (
          <div className="rounded-lg border bg-white p-3 text-sm text-gray-600">
            Registered Number: <span className="font-semibold">+91 {auth.phone}</span>
          </div>
        ) : null}

        {error ? <p className="text-sm text-red-500">{error}</p> : null}
      </div>

      <div className="mt-8">
        <button
          onClick={handleSubmit}
          disabled={loading}
          className="w-full py-3 bg-green-500 text-white rounded-lg font-semibold flex items-center justify-center gap-2 disabled:opacity-70"
        >
          {loading ? <Loader2 size={18} className="animate-spin" /> : null}
          Complete Setup
        </button>
      </div>
    </div>
  );
}