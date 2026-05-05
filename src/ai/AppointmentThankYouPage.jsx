import React, { useMemo } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";

function readStoredSuccess() {
  try {
    const raw = window.localStorage.getItem("snoutiq.lastBookingSuccess");
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
}

function formatService(type) {
  if (type === "video_consult") return "Video consult";
  if (type === "home_service") return "Vet at home";
  if (type === "in_clinic") return "Clinic visit";
  return "Appointment";
}

function getBookingRoute(type) {
  if (type === "video_consult") return "/video-consult";
  if (type === "home_service") return "/vet-at-home-booking";
  if (type === "in_clinic") return "/inclinic-fast-booking";
  return "/ai";
}

function getSuccessCopy(type) {
  if (type === "home_service") {
    return {
      title: "Vet at home booked successfully",
      subtitle: "SnoutIQ team will assign a vet and confirm your visit.",
    };
  }

  if (type === "video_consult") {
    return {
      title: "Video consult booked successfully",
      subtitle: "Doctor will connect with you shortly.",
    };
  }

  if (type === "in_clinic") {
    return {
      title: "Clinic visit booked successfully",
      subtitle: "Your appointment is confirmed.",
    };
  }

  return {
    title: "Appointment booked successfully",
    subtitle: "Your booking is confirmed.",
  };
}

export default function AppointmentThankYouPage() {
  const location = useLocation();
  const navigate = useNavigate();
  const success = useMemo(
    () => (location.state && Object.keys(location.state).length ? location.state : readStoredSuccess()),
    [location.state],
  );

  if (!success) {
    return (
      <div className="min-h-screen bg-slate-50 px-4 py-10 text-slate-900">
        <div className="mx-auto max-w-xl rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm">
          <h1 className="text-2xl font-bold">No recent booking found</h1>
          <button onClick={() => navigate("/ai")} className="mt-5 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white">
            Back to AI
          </button>
        </div>
      </div>
    );
  }

  const petId = success.petId || success.pet_id;
  const copy = getSuccessCopy(success.bookingType);
  const dateTime = [success.date, success.time].filter(Boolean).join(" - ");

  return (
    <div className="min-h-screen bg-slate-50 px-4 py-8 text-slate-900">
      <div className="mx-auto max-w-2xl rounded-2xl border border-emerald-200 bg-white p-6 shadow-sm">
        <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-base font-bold text-emerald-700">
          OK
        </div>
        <h1 className="mt-4 text-center text-2xl font-bold">{copy.title}</h1>
        <p className="mt-2 text-center text-sm text-slate-500">{copy.subtitle}</p>
        <div className="mt-6 divide-y divide-slate-100 rounded-2xl border border-slate-200 p-4 text-sm">
          {success.paymentId || success.payment_id ? <Row label="Payment ID" value={success.paymentId || success.payment_id} /> : null}
          {success.appointmentId || success.bookingId || success.id ? <Row label="Booking ID" value={success.appointmentId || success.bookingId || success.id} /> : null}
          <Row label="Service" value={formatService(success.bookingType)} />
          {success.petName ? <Row label="Pet" value={success.petName} /> : null}
          {success.bookingType !== "home_service" && success.doctorName ? <Row label="Doctor" value={success.doctorName} /> : null}
          {success.bookingType !== "home_service" && success.clinicName ? <Row label="Clinic" value={success.clinicName} /> : null}
          {dateTime ? <Row label="Date / Time" value={dateTime} /> : null}
          {success.amount ? <Row label="Amount" value={`Rs ${success.amount}`} /> : null}
        </div>
        <div className="mt-6 grid gap-3 sm:grid-cols-3">
          <Link to="/ai" className="rounded-2xl border border-slate-200 px-4 py-3 text-center text-sm font-semibold text-slate-700">
            Back to AI
          </Link>
          {petId ? (
            <Link to={`/pet-lifeline/${petId}`} className="rounded-2xl border border-slate-200 px-4 py-3 text-center text-sm font-semibold text-slate-700">
              View Pet Timeline
            </Link>
          ) : null}
          <Link to={getBookingRoute(success.bookingType)} className="rounded-2xl bg-blue-600 px-4 py-3 text-center text-sm font-semibold text-white">
            Book Another Appointment
          </Link>
        </div>
      </div>
    </div>
  );
}

function Row({ label, value }) {
  return (
    <div className="flex items-center justify-between gap-4 py-3">
      <span className="text-slate-500">{label}</span>
      <span className="text-right font-semibold text-slate-900">{value}</span>
    </div>
  );
}
