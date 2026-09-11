import React, { useEffect, useMemo, useState } from "react";
import { Link, useParams } from "react-router-dom";
import {
  AlertCircle,
  ArrowLeft,
  ArrowRight,
  Check,
  Clock,
  IndianRupee,
  Loader2,
  MapPin,
  Phone,
  Search,
  Stethoscope,
  Video,
  Wrench,
} from "lucide-react";

import axiosClient from "../axios";
import clinicFallbackImage from "../assets/images/clinic.png";
import { Navbar } from "../newflow/Navbar";
import { Footer } from "../newflow/NewFooter";
import { Button } from "../newflow/NewButton";

const CLINIC_FORM_API_URL = "https://snoutiq.com/backend/api/demo-website-form";
const DIRECT_CONSULT_PATH = "/20+vetsonline?start=details";

const valueOrDash = (value) => {
  const text = String(value ?? "").trim();
  return text || "-";
};

const plural = (count, label) => `${count} ${label}${count === 1 ? "" : "s"}`;

const formatMoney = (value) => {
  if (value === null || value === undefined || value === "") return null;
  const amount = Number(value);
  if (!Number.isFinite(amount) || amount <= 0) return null;
  return `Rs. ${amount.toLocaleString("en-IN")}`;
};

const extractClinics = (payload) => {
  const page = payload?.data?.data || payload?.data || payload;
  if (Array.isArray(page)) return page;
  if (Array.isArray(page?.data)) return page.data;
  return [];
};

const buildClinicStats = (entry) => {
  const doctors = entry?.doctors?.length || 0;
  const services = entry?.services?.length || 0;
  const packages = entry?.specialized_packages?.length || 0;
  const videoSchedules = entry?.video_schedules?.length || 0;

  return [
    plural(doctors, "doctor"),
    plural(services, "service"),
    plural(packages, "package"),
    plural(videoSchedules, "video schedule"),
  ];
};

const getClinicImage = (clinic) =>
  clinic?.clinic_image_url || clinic?.image || clinicFallbackImage;

const DAYS = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

const formatTime = (value) => {
  if (!value) return "-";
  const text = String(value);
  const [hour = "", minute = ""] = text.split(":");
  return hour && minute ? `${hour.padStart(2, "0")}:${minute}` : text;
};

const formatSlot = (slot) =>
  `${DAYS[Number(slot?.day_of_week)] || "Day"} ${formatTime(slot?.start_time)}-${formatTime(slot?.end_time)}`;

const formatLabel = (value) =>
  String(value || "")
    .replace(/_/g, " ")
    .replace(/\b\w/g, (char) => char.toUpperCase());

const parseList = (value) => {
  if (Array.isArray(value)) return value.filter(Boolean);
  if (!value) return [];

  try {
    const parsed = JSON.parse(value);
    if (Array.isArray(parsed)) return parsed.filter(Boolean);
  } catch {
    return String(value)
      .split(",")
      .map((item) => item.trim())
      .filter(Boolean);
  }

  return [];
};

const splitByMainService = (services = [], machinery = []) => {
  const machineRows = machinery.length
    ? machinery
    : services.filter((service) => service?.main_service === "machinery");

  const serviceRows = services.filter(
    (service) => service?.main_service !== "machinery"
  );

  return { serviceRows, machineRows };
};

const isNightSlot = (slot) => {
  const hour = Number(String(slot?.start_time || "").slice(0, 2));
  return Number.isFinite(hour) && hour >= 18;
};

const mapUrlForClinic = (clinic) => {
  if (clinic?.lat && clinic?.lng) {
    return `https://www.google.com/maps/search/?api=1&query=${clinic.lat},${clinic.lng}`;
  }

  const query = [clinic?.formatted_address, clinic?.address, clinic?.city]
    .filter(Boolean)
    .join(", ");

  return query
    ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`
    : null;
};

function DetailSection({ title, children }) {
  return (
    <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
      <h2 className="text-2xl font-bold text-slate-950">{title}</h2>
      <div className="mt-5">{children}</div>
    </section>
  );
}

function EmptyState({ children }) {
  return <p className="text-sm text-slate-600">{children}</p>;
}

function SlotList({ slots }) {
  if (!slots.length) return <EmptyState>No slots saved.</EmptyState>;

  return (
    <div className="grid gap-3 sm:grid-cols-2">
      {slots.map((slot) => (
        <div
          key={`${slot.doctor_id}-${slot.service_type}-${slot.day_of_week}-${slot.start_time}-${slot.end_time}`}
          className="rounded-lg border border-slate-200 bg-slate-50 p-4"
        >
          <p className="font-semibold text-slate-950">{formatSlot(slot)}</p>
          <p className="mt-1 text-sm text-slate-600">
            {formatLabel(slot.service_type || "in_clinic")}
            {slot.doctor_name ? ` · ${slot.doctor_name}` : ""}
          </p>
          {slot.break_start && slot.break_end ? (
            <p className="mt-1 text-xs text-slate-500">
              Break {formatTime(slot.break_start)}-{formatTime(slot.break_end)}
            </p>
          ) : null}
        </div>
      ))}
    </div>
  );
}

function ClinicLeadForm() {
  const [isSubmitted, setIsSubmitted] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState("");
  const [formData, setFormData] = useState({
    clinicName: "",
    contactName: "",
    mobile: "",
    city: "",
  });

  const handleChange = (e) => {
    const { id, value } = e.target;
    setFormData((prev) => ({ ...prev, [id]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitError("");
    setIsSubmitting(true);

    const payload = {
      name: formData.contactName.trim(),
      clinic_name: formData.clinicName.trim(),
      contact_name: formData.contactName.trim(),
      mobile: formData.mobile.trim(),
      city: formData.city.trim(),
      source: "newflow_clinics_page",
    };

    try {
      const response = await fetch(CLINIC_FORM_API_URL, {
        method: "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });

      const raw = await response.text();
      let data = null;
      try {
        data = raw ? JSON.parse(raw) : null;
      } catch {
        data = null;
      }

      if (!response.ok) {
        const message =
          data?.message ||
          (data?.errors && Object.values(data.errors).flat()[0]) ||
          "Unable to submit request right now. Please try again.";
        throw new Error(message);
      }

      setIsSubmitted(true);
      setFormData({ clinicName: "", contactName: "", mobile: "", city: "" });
    } catch (error) {
      setSubmitError(
        error?.message || "Unable to submit request right now. Please try again."
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <section id="clinic-onboarding-form" className="bg-slate-50 py-14 sm:py-16">
      <div className="mx-auto grid max-w-6xl gap-8 px-4 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:px-8">
        <div>
          <p className="text-sm font-semibold uppercase tracking-wide text-blue-700">
            For clinics
          </p>
          <h2 className="mt-3 text-3xl font-bold text-slate-950 sm:text-4xl">
            Bring your clinic into SnoutIQ.
          </h2>
          <p className="mt-4 text-lg text-slate-600">
            Add verified doctors, services, appointment hours, and video consult
            availability from the onboarding panel.
          </p>
        </div>

        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
          {isSubmitted ? (
            <div className="py-8 text-center">
              <div className="mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                <Check className="h-7 w-7" />
              </div>
              <h3 className="text-xl font-bold text-slate-950">
                Thank you. We will reach out shortly.
              </h3>
              <p className="mt-2 text-slate-600">
                Our team will contact you to discuss onboarding for your clinic.
              </p>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="space-y-5">
              {[
                ["clinicName", "Clinic Name", "e.g. City Pet Care Clinic"],
                ["contactName", "Contact Person", "e.g. Dr. Aditi Sharma"],
                ["mobile", "Mobile Number", "+91 98765 43210"],
                ["city", "City", "e.g. Bengaluru"],
              ].map(([id, label, placeholder]) => (
                <div key={id}>
                  <label
                    htmlFor={id}
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    {label}
                  </label>
                  <input
                    id={id}
                    required
                    type={id === "mobile" ? "tel" : "text"}
                    value={formData[id]}
                    onChange={handleChange}
                    className="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    placeholder={placeholder}
                  />
                </div>
              ))}

              {submitError ? (
                <p className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                  {submitError}
                </p>
              ) : null}

              <Button
                type="submit"
                size="lg"
                disabled={isSubmitting}
                className="w-full bg-blue-600 text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700"
              >
                {isSubmitting ? "Submitting..." : "Submit Request"}
              </Button>
            </form>
          )}
        </div>
      </div>
    </section>
  );
}

function ClinicDirectory() {
  const [clinics, setClinics] = useState([]);
  const [query, setQuery] = useState("");
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let cancelled = false;

    const loadClinics = async () => {
      setIsLoading(true);
      setError("");

      try {
        const { data } = await axiosClient.get("/clinic-pages", {
          params: { per_page: 100 },
        });

        if (!cancelled) {
          setClinics(extractClinics(data));
        }
      } catch (err) {
        if (!cancelled) {
          setError(
            err?.response?.data?.message ||
              "Unable to load clinics right now."
          );
        }
      } finally {
        if (!cancelled) setIsLoading(false);
      }
    };

    loadClinics();
    return () => {
      cancelled = true;
    };
  }, []);

  const filteredClinics = useMemo(() => {
    const term = query.trim().toLowerCase();
    if (!term) return clinics;

    return clinics.filter((entry) => {
      const clinic = entry?.clinic || {};
      return [clinic.name, clinic.city, clinic.address, clinic.slug]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(term));
    });
  }, [clinics, query]);

  return (
    <>
      <section className="border-b border-slate-200 bg-white py-10 sm:py-12">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="grid gap-8 lg:grid-cols-[0.9fr_0.55fr] lg:items-end">
            <div>
              <p className="text-sm font-semibold uppercase tracking-wide text-blue-700">
                SnoutIQ Clinics
              </p>
              <h1 className="mt-3 text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl">
                Verified clinic directory
              </h1>
              <p className="mt-4 max-w-3xl text-lg text-slate-600">
                Explore clinics added through the SnoutIQ onboarding workflow,
                with profile pages generated for each clinic.
              </p>
            </div>

            <label className="relative block">
              <Search className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
              <input
                value={query}
                onChange={(event) => setQuery(event.target.value)}
                className="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-slate-900 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100"
                placeholder="Search clinic, city, or slug"
              />
            </label>
          </div>
        </div>
      </section>

      <section className="bg-slate-50 py-10 sm:py-12">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          {isLoading ? (
            <div className="flex min-h-72 items-center justify-center rounded-2xl border border-slate-200 bg-white">
              <Loader2 className="h-8 w-8 animate-spin text-blue-600" />
            </div>
          ) : error ? (
            <div className="flex items-center gap-3 rounded-2xl border border-red-200 bg-red-50 p-5 text-red-700">
              <AlertCircle className="h-5 w-5" />
              <p>{error}</p>
            </div>
          ) : filteredClinics.length === 0 ? (
            <div className="rounded-2xl border border-slate-200 bg-white p-8 text-center">
              <h2 className="text-xl font-semibold text-slate-950">
                No clinics found
              </h2>
              <p className="mt-2 text-slate-600">
                Add a clinic name in full onboarding and the API will generate
                its slug.
              </p>
            </div>
          ) : (
            <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
              {filteredClinics.map((entry) => {
                const clinic = entry?.clinic || {};
                const stats = buildClinicStats(entry);
                const completion =
                  entry?.profile_completion_percentage ??
                  entry?.profile_completion?.percentage ??
                  0;

                return (
                  <Link
                    key={clinic.id || clinic.slug}
                    to={`/clinics/${clinic.slug || clinic.id}`}
                    className="group flex min-h-[340px] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md"
                  >
                    <div className="aspect-[16/9] overflow-hidden bg-slate-100">
                      <img
                        src={getClinicImage(clinic)}
                        alt={clinic.name || "Veterinary clinic"}
                        className="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                        loading="lazy"
                      />
                    </div>

                    <div className="flex flex-1 flex-col p-5">
                      <div className="flex items-start justify-between gap-4">
                        <div>
                          <h2 className="text-xl font-bold text-slate-950">
                            {valueOrDash(clinic.name)}
                          </h2>
                          <p className="mt-1 flex items-center gap-1.5 text-sm text-slate-500">
                            <MapPin className="h-4 w-4" />
                            {valueOrDash(clinic.city)}
                          </p>
                        </div>
                        <span className="rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700">
                          {completion}%
                        </span>
                      </div>

                      <p className="mt-4 line-clamp-2 text-sm text-slate-600">
                        {clinic.clinic_profile ||
                          clinic.hospital_profile ||
                          clinic.bio ||
                          clinic.address ||
                          "Clinic profile details are being completed."}
                      </p>

                      <div className="mt-5 grid grid-cols-2 gap-2 text-sm text-slate-600">
                        {stats.map((item) => (
                          <span
                            key={item}
                            className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2"
                          >
                            {item}
                          </span>
                        ))}
                      </div>

                      <span className="mt-auto inline-flex items-center gap-2 pt-5 font-semibold text-blue-700">
                        View clinic
                        <ArrowRight className="h-4 w-4" />
                      </span>
                    </div>
                  </Link>
                );
              })}
            </div>
          )}
        </div>
      </section>

      <ClinicLeadForm />
    </>
  );
}

function ClinicDetail() {
  const { clinicSlug } = useParams();
  const [entry, setEntry] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let cancelled = false;

    const loadClinic = async () => {
      setIsLoading(true);
      setError("");

      try {
        const { data } = await axiosClient.get(
          `/clinic-pages/${encodeURIComponent(clinicSlug)}`
        );

        if (!cancelled) setEntry(data?.data || null);
      } catch (err) {
        if (!cancelled) {
          setError(
            err?.response?.data?.message ||
              "Unable to load this clinic right now."
          );
        }
      } finally {
        if (!cancelled) setIsLoading(false);
      }
    };

    loadClinic();
    return () => {
      cancelled = true;
    };
  }, [clinicSlug]);

  if (isLoading) {
    return (
      <section className="flex min-h-[70vh] items-center justify-center bg-slate-50">
        <Loader2 className="h-9 w-9 animate-spin text-blue-600" />
      </section>
    );
  }

  if (error || !entry) {
    return (
      <section className="bg-slate-50 py-16">
        <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
          <Link
            to="/clinics"
            className="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-blue-700"
          >
            <ArrowLeft className="h-4 w-4" />
            Back to clinics
          </Link>
          <div className="rounded-2xl border border-red-200 bg-red-50 p-6 text-red-700">
            {error || "Clinic not found."}
          </div>
        </div>
      </section>
    );
  }

  const clinic = entry.clinic || {};
  const doctors = entry.doctors || [];
  const { serviceRows, machineRows } = splitByMainService(
    entry.services || [],
    entry.machinery || []
  );
  const packages = entry.specialized_packages || [];
  const vetAtHomeServices = entry.vet_at_home_services || [];
  const clinicAvailability = entry.clinic_availability || [];
  const videoSchedules = entry.video_schedules || [];
  const profile = entry.profile_completion || {};
  const missingFields = profile.missing_fields || [];
  const dayFee = formatMoney(clinic.clinic_day_fee);
  const nightFee = formatMoney(clinic.clinic_night_fee);
  const daySlots = clinicAvailability.filter((slot) => !isNightSlot(slot));
  const nightSlots = clinicAvailability.filter(isNightSlot);
  const locationUrl = mapUrlForClinic(clinic);
  const fullAddress =
    clinic.formatted_address || clinic.address || [clinic.city, clinic.pincode].filter(Boolean).join(" ");

  return (
    <>
      <section className="border-b border-slate-200 bg-white py-8">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <Link
            to="/clinics"
            className="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-blue-700"
          >
            <ArrowLeft className="h-4 w-4" />
            Back to clinics
          </Link>

          <div className="grid gap-8 lg:grid-cols-[1fr_0.82fr] lg:items-center">
            <div>
              <p className="text-sm font-semibold uppercase tracking-wide text-blue-700">
                /clinics/{clinic.slug}
              </p>
              <h1 className="mt-3 text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl">
                {valueOrDash(clinic.name)}
              </h1>
              <div className="mt-4 flex flex-wrap gap-3 text-sm text-slate-600">
                <span className="inline-flex items-center gap-1.5">
                  <MapPin className="h-4 w-4" />
                  {valueOrDash([clinic.city, clinic.pincode].filter(Boolean).join(" - "))}
                </span>
                <span className="inline-flex items-center gap-1.5">
                  <Phone className="h-4 w-4" />
                  {valueOrDash(clinic.mobile)}
                </span>
                <span className="inline-flex items-center gap-1.5">
                  <IndianRupee className="h-4 w-4" />
                  Day {dayFee || "-"} · Night {nightFee || "-"}
                </span>
              </div>
              {fullAddress ? (
                <p className="mt-3 max-w-3xl text-sm text-slate-500">
                  {fullAddress}
                </p>
              ) : null}
              <p className="mt-5 max-w-3xl text-lg text-slate-600">
                {clinic.clinic_profile ||
                  clinic.hospital_profile ||
                  clinic.bio ||
                  clinic.address ||
                  "This clinic profile is connected to SnoutIQ onboarding data."}
              </p>
            </div>

            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
              {clinic.clinic_video_url ? (
                <video
                  src={clinic.clinic_video_url}
                  className="aspect-[16/10] h-full w-full object-cover"
                  poster={getClinicImage(clinic)}
                  autoPlay
                  muted
                  loop
                  playsInline
                  controls
                />
              ) : (
                <img
                  src={getClinicImage(clinic)}
                  alt={clinic.name || "Veterinary clinic"}
                  className="aspect-[16/10] h-full w-full object-cover"
                />
              )}
            </div>
          </div>
        </div>
      </section>

      <section className="bg-slate-50 py-10 sm:py-12">
        <div className="mx-auto grid max-w-7xl gap-5 px-4 sm:px-6 lg:grid-cols-4 lg:px-8">
          {[
            [plural(doctors.length, "doctor"), Stethoscope],
            [plural(serviceRows.length, "service"), Check],
            [plural(machineRows.length, "machine"), Wrench],
            [plural(videoSchedules.length, "video schedule"), Video],
          ].map(([label, Icon]) => (
            <div
              key={label}
              className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
            >
              <Icon className="mb-4 h-6 w-6 text-blue-600" />
              <p className="text-2xl font-bold text-slate-950">{label}</p>
            </div>
          ))}
        </div>

        <div className="mx-auto mt-6 grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[1.25fr_0.75fr] lg:px-8">
          <div className="space-y-6">
            <DetailSection title="Clinic Fees">
              <div className="grid gap-3 sm:grid-cols-2">
                <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                  <p className="text-sm font-semibold text-slate-500">Day fee</p>
                  <p className="mt-2 text-2xl font-bold text-slate-950">
                    {dayFee || "-"}
                  </p>
                </div>
                <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                  <p className="text-sm font-semibold text-slate-500">Night fee</p>
                  <p className="mt-2 text-2xl font-bold text-slate-950">
                    {nightFee || "-"}
                  </p>
                </div>
              </div>
            </DetailSection>

            <DetailSection title="Clinic Day Slots">
              <SlotList slots={daySlots} />
            </DetailSection>

            <DetailSection title="Clinic Night Slots">
              <SlotList slots={nightSlots} />
            </DetailSection>

            <DetailSection title="Doctors">
              <div className="divide-y divide-slate-100">
                {doctors.length ? (
                  doctors.map((doctor) => (
                    <div key={doctor.id} className="py-4 first:pt-0 last:pb-0">
                      <h3 className="text-lg font-semibold text-slate-950">
                        {valueOrDash(doctor.doctor_name)}
                      </h3>
                      <p className="mt-1 text-sm text-slate-600">
                        {valueOrDash(doctor.degree)} ·{" "}
                        {valueOrDash(doctor.years_of_experience)} yrs
                      </p>
                      <div className="mt-3 flex flex-wrap gap-2">
                        {parseList(doctor.specialization_select_all_that_apply).map((item) => (
                          <span
                            key={item}
                            className="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700"
                          >
                            {item}
                          </span>
                        ))}
                      </div>
                      <div className="mt-3 grid gap-2 text-sm text-slate-600 sm:grid-cols-3">
                        <span>Clinic: {formatMoney(doctor.doctors_price) || "-"}</span>
                        <span>Video day: {formatMoney(doctor.video_day_rate) || "-"}</span>
                        <span>Video night: {formatMoney(doctor.video_night_rate) || "-"}</span>
                      </div>
                      {parseList(doctor.languages_spoken).length ? (
                        <p className="mt-2 text-sm text-slate-500">
                          Languages: {parseList(doctor.languages_spoken).join(", ")}
                        </p>
                      ) : null}
                    </div>
                  ))
                ) : (
                  <EmptyState>No doctors saved.</EmptyState>
                )}
              </div>
            </DetailSection>

            <DetailSection title="Services">
              <div className="grid gap-3 sm:grid-cols-2">
                {serviceRows.length ? (
                  serviceRows.map((service) => (
                    <div
                      key={service.id}
                      className="rounded-lg border border-slate-200 bg-slate-50 p-4"
                    >
                      <h3 className="font-semibold text-slate-950">
                        {valueOrDash(service.name)}
                      </h3>
                      <p className="mt-1 text-sm text-slate-600">
                        {service.description || service.pet_type || "Service details pending."}
                      </p>
                      <p className="mt-3 text-sm font-semibold text-blue-700">
                        {formatMoney(service.price) || "Price on request"}
                      </p>
                      {service.duration ? (
                        <p className="mt-1 text-xs text-slate-500">
                          Duration: {service.duration} mins
                        </p>
                      ) : null}
                    </div>
                  ))
                ) : (
                  <EmptyState>No services saved.</EmptyState>
                )}
              </div>
            </DetailSection>

            <DetailSection title="Machinery">
              <div className="grid gap-3 sm:grid-cols-2">
                {machineRows.length ? (
                  machineRows.map((machine) => (
                    <div
                      key={machine.id}
                      className="rounded-lg border border-slate-200 bg-slate-50 p-4"
                    >
                      <h3 className="font-semibold text-slate-950">
                        {valueOrDash(machine.name)}
                      </h3>
                      <p className="mt-1 text-sm text-slate-600">
                        {machine.description || machine.pet_type || "Machine details pending."}
                      </p>
                      <p className="mt-3 text-sm font-semibold text-blue-700">
                        {formatMoney(machine.price) || "Price on request"}
                      </p>
                    </div>
                  ))
                ) : (
                  <EmptyState>No machinery saved.</EmptyState>
                )}
              </div>
            </DetailSection>

            <DetailSection title="Packages">
              <div className="grid gap-3 sm:grid-cols-2">
                {packages.length ? (
                  packages.map((pack) => (
                    <div
                      key={pack.id}
                      className="rounded-lg border border-slate-200 bg-slate-50 p-4"
                    >
                      <p className="font-semibold text-slate-950">
                        {pack.doctor_name || "Specialized package"}
                      </p>
                      <div className="mt-3 space-y-1 text-sm text-slate-600">
                        {Object.entries(pack)
                          .filter(([key, value]) => key.endsWith("_price") && formatMoney(value))
                          .map(([key, value]) => (
                            <p key={key}>
                              {formatLabel(key.replace("_price", ""))}:{" "}
                              <span className="font-semibold text-slate-800">
                                {formatMoney(value)}
                              </span>
                            </p>
                          ))}
                      </div>
                    </div>
                  ))
                ) : (
                  <EmptyState>No packages saved.</EmptyState>
                )}
              </div>
            </DetailSection>
          </div>

          <aside className="space-y-6">
            <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
              <h2 className="text-xl font-bold text-slate-950">
                Profile completion
              </h2>
              <div className="mt-4 h-3 overflow-hidden rounded-full bg-slate-100">
                <div
                  className="h-full rounded-full bg-blue-600"
                  style={{ width: `${profile.percentage || 0}%` }}
                />
              </div>
              <p className="mt-3 text-sm font-semibold text-slate-700">
                {profile.completed_fields || 0} / {profile.total_fields || 0} fields
                completed
              </p>
              {missingFields.length ? (
                <p className="mt-2 text-sm text-slate-600">
                  Missing:{" "}
                  {missingFields
                    .slice(0, 5)
                    .map((field) => field.label || field)
                    .join(", ")}
                </p>
              ) : null}
            </section>

            <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
              <h2 className="text-xl font-bold text-slate-950">Location</h2>
              <p className="mt-3 text-sm text-slate-600">
                {fullAddress || "Location details pending."}
              </p>
              <div className="mt-4 space-y-2 text-sm text-slate-600">
                <p>City: {valueOrDash(clinic.city)}</p>
                <p>Pincode: {valueOrDash(clinic.pincode)}</p>
                {clinic.lat && clinic.lng ? (
                  <p>
                    Coordinates: {clinic.lat}, {clinic.lng}
                  </p>
                ) : null}
                {clinic.rating ? (
                  <p>
                    Rating: {clinic.rating}
                    {clinic.user_ratings_total
                      ? ` (${clinic.user_ratings_total} reviews)`
                      : ""}
                  </p>
                ) : null}
              </div>
              {locationUrl ? (
                <a
                  href={locationUrl}
                  target="_blank"
                  rel="noreferrer"
                  className="mt-5 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                >
                  <MapPin className="h-4 w-4" />
                  Open map
                </a>
              ) : null}
            </section>

            <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
              <h2 className="text-xl font-bold text-slate-950">Vet at home</h2>
              <div className="mt-4 space-y-3">
                {vetAtHomeServices.length ? (
                  vetAtHomeServices.map((service) => (
                    <div
                      key={service.id}
                      className="rounded-lg border border-slate-200 bg-slate-50 p-4"
                    >
                      <p className="font-semibold text-slate-950">
                        {service.doctor_name || "Home visit"}
                      </p>
                      <div className="mt-2 space-y-1 text-sm text-slate-600">
                        <p>Status: {service.is_enabled ? "Available" : "Not available"}</p>
                        <p>Hours: {valueOrDash(service.service_hours)}</p>
                        <p>Response: {valueOrDash(service.response_time)}</p>
                        <p>Base payout: {formatMoney(service.base_payout) || "-"}</p>
                        <p>Protocol: {valueOrDash(service.protocol_label)}</p>
                      </div>
                    </div>
                  ))
                ) : (
                  <EmptyState>No vet-at-home details saved.</EmptyState>
                )}
              </div>
            </section>

            <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
              <h2 className="text-xl font-bold text-slate-950">Video hours</h2>
              <div className="mt-4 space-y-3">
                {videoSchedules.length ? (
                  videoSchedules.map((schedule) => (
                    <div
                      key={schedule.doctor_id}
                      className="rounded-lg border border-slate-200 bg-slate-50 p-4"
                    >
                      <p className="font-semibold text-slate-950">
                        {valueOrDash(schedule.doctor_name)}
                      </p>
                      <div className="mt-2 flex flex-wrap gap-2 text-xs font-semibold text-blue-700">
                        <span className="rounded-full bg-blue-50 px-2.5 py-1">
                          Day {formatMoney(schedule.day_rate) || "-"}
                        </span>
                        <span className="rounded-full bg-blue-50 px-2.5 py-1">
                          Night {formatMoney(schedule.night_rate) || "-"}
                        </span>
                      </div>
                      <div className="mt-3 space-y-1 text-sm text-slate-600">
                        {(schedule.availability || []).length ? (
                          schedule.availability.map((slot) => (
                            <p
                              key={`${schedule.doctor_id}-${slot.day_of_week}-${slot.start_time}-${slot.end_time}`}
                              className="flex items-center gap-1.5"
                            >
                              <Clock className="h-4 w-4 text-slate-400" />
                              {formatSlot(slot)}
                            </p>
                          ))
                        ) : (
                          <EmptyState>No video slots saved.</EmptyState>
                        )}
                      </div>
                    </div>
                  ))
                ) : (
                  <EmptyState>No video hours saved.</EmptyState>
                )}
              </div>
            </section>
          </aside>
        </div>
      </section>
    </>
  );
}

export default function NewClinics() {
  const { clinicSlug } = useParams();

  return (
    <div className="flex min-h-screen flex-col bg-white">
      <Navbar consultPath={DIRECT_CONSULT_PATH} />
      <main className="flex-1">
        {clinicSlug ? <ClinicDetail /> : <ClinicDirectory />}
      </main>
      <Footer />
    </div>
  );
}
