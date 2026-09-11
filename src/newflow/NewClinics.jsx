import React, { useEffect, useMemo, useState } from "react";
import { Link, useParams } from "react-router-dom";
import {
  AlertCircle,
  ArrowLeft,
  ArrowRight,
  Check,
  Clock,
  CalendarDays,
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

const hasValue = (value) => String(value ?? "").trim() !== "";

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

const getClinicImage = (clinic) =>
  clinic?.clinic_image_url || clinic?.image || clinicFallbackImage;

const getClinicSummary = (clinic) =>
  clinic?.website_subtitle ||
  clinic?.clinic_profile ||
  clinic?.hospital_profile ||
  clinic?.bio ||
  clinic?.address ||
  "";

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

function SlotList({ slots }) {
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
      <section className="border-b border-slate-200 bg-white py-8 sm:py-10">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="grid gap-6 lg:grid-cols-[0.95fr_0.5fr] lg:items-end">
            <div>
              <p className="inline-flex rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700">
                {plural(clinics.length, "clinic")} listed
              </p>
              <h1 className="mt-4 text-3xl font-bold tracking-tight text-slate-950 sm:text-5xl">
                Find a SnoutIQ clinic near you
              </h1>
              <p className="mt-3 max-w-3xl text-base leading-7 text-slate-600 sm:text-lg">
                Browse clinics with doctors, consultation fees, services,
                machinery, location, and appointment hours from verified
                onboarding data.
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

      <section className="bg-slate-50 py-8 sm:py-10">
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
            <div className="grid gap-5 lg:grid-cols-2">
              {filteredClinics.map((entry) => {
                const clinic = entry?.clinic || {};
                const { serviceRows, machineRows } = splitByMainService(
                  entry?.services || [],
                  entry?.machinery || []
                );
                const doctors = entry?.doctors?.length || 0;
                const packages = entry?.specialized_packages?.length || 0;
                const videoSchedules = entry?.video_schedules?.length || 0;
                const dayFee = formatMoney(clinic.clinic_day_fee);
                const nightFee = formatMoney(clinic.clinic_night_fee);
                const summary = getClinicSummary(clinic);
                const quickStats = [
                  [doctors, plural(doctors, "doctor"), Stethoscope],
                  [serviceRows.length, plural(serviceRows.length, "service"), Check],
                  [machineRows.length, plural(machineRows.length, "machine"), Wrench],
                  [packages, plural(packages, "package"), IndianRupee],
                  [videoSchedules, plural(videoSchedules, "video slot"), Video],
                ].filter(([count]) => count > 0);
                const hasFees = dayFee || nightFee;

                return (
                  <Link
                    key={clinic.id || clinic.slug}
                    to={`/clinics/${clinic.slug || clinic.id}`}
                    className="group grid overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md sm:grid-cols-[240px_1fr]"
                  >
                    <div className="relative min-h-[220px] overflow-hidden bg-slate-100 sm:min-h-full">
                      <img
                        src={getClinicImage(clinic)}
                        alt={clinic.name || "Veterinary clinic"}
                        className="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                        loading="lazy"
                        onError={(event) => {
                          event.currentTarget.onerror = null;
                          event.currentTarget.src = clinicFallbackImage;
                        }}
                      />
                      {clinic.city ? (
                        <span className="absolute left-4 top-4 rounded-full bg-white/95 px-3 py-1 text-sm font-bold text-blue-700 shadow-sm">
                          {clinic.city}
                        </span>
                      ) : null}
                    </div>

                    <div className="flex min-h-[300px] flex-col p-5 sm:p-6">
                      <div className="flex items-start justify-between gap-4">
                        <div className="min-w-0">
                          <h2 className="text-2xl font-bold leading-tight text-slate-950">
                            {valueOrDash(clinic.name)}
                          </h2>
                          <p className="mt-2 flex items-center gap-1.5 text-sm font-medium text-slate-500">
                            <MapPin className="h-4 w-4" />
                            {valueOrDash([clinic.city, clinic.pincode].filter(Boolean).join(" - "))}
                          </p>
                        </div>
                        <span className="shrink-0 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                          Verified
                        </span>
                      </div>

                      {summary ? (
                        <p className="mt-4 line-clamp-2 text-sm leading-6 text-slate-600">
                          {summary}
                        </p>
                      ) : null}

                      {clinic.mobile || hasFees ? (
                        <div className="mt-4 grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
                          {clinic.mobile ? (
                            <span className="inline-flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2">
                              <Phone className="h-4 w-4 text-blue-600" />
                              {clinic.mobile}
                            </span>
                          ) : null}
                          {hasFees ? (
                            <span className="inline-flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2">
                              <CalendarDays className="h-4 w-4 text-blue-600" />
                              {dayFee ? `Day ${dayFee}` : ""}
                              {dayFee && nightFee ? " · " : ""}
                              {nightFee ? `Night ${nightFee}` : ""}
                            </span>
                          ) : null}
                        </div>
                      ) : null}

                      {quickStats.length ? (
                        <div className="mt-4 flex flex-wrap gap-2">
                          {quickStats.map(([, item, Icon]) => (
                          <span
                            key={item}
                            className="inline-flex items-center gap-1.5 rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600"
                          >
                            <Icon className="h-3.5 w-3.5 text-blue-600" />
                            {item}
                          </span>
                          ))}
                        </div>
                      ) : null}

                      <span className="mt-auto inline-flex items-center gap-2 pt-5 font-bold text-blue-700">
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
  const dayFee = formatMoney(clinic.clinic_day_fee);
  const nightFee = formatMoney(clinic.clinic_night_fee);
  const daySlots = clinicAvailability.filter((slot) => !isNightSlot(slot));
  const nightSlots = clinicAvailability.filter(isNightSlot);
  const locationUrl = mapUrlForClinic(clinic);
  const fullAddress =
    clinic.formatted_address || clinic.address || [clinic.city, clinic.pincode].filter(Boolean).join(" ");
  const hasClinicImage = Boolean(clinic.clinic_image_url || clinic.image);
  const hasClinicVideo = Boolean(clinic.clinic_video_url);
  const detailSummary = getClinicSummary(clinic);
  const displayPackages = packages
    .map((pack) => ({
      ...pack,
      packagePrices: Object.entries(pack).filter(
        ([key, value]) => key.endsWith("_price") && formatMoney(value)
      ),
    }))
    .filter((pack) => pack.packagePrices.length > 0);
  const summaryCards = [
    [doctors.length, plural(doctors.length, "doctor"), Stethoscope],
    [serviceRows.length, plural(serviceRows.length, "service"), Check],
    [machineRows.length, plural(machineRows.length, "machine"), Wrench],
    [displayPackages.length, plural(displayPackages.length, "package"), IndianRupee],
    [videoSchedules.length, plural(videoSchedules.length, "video schedule"), Video],
  ].filter(([count]) => count > 0);

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
                {clinic.city || clinic.pincode ? (
                  <span className="inline-flex items-center gap-1.5">
                    <MapPin className="h-4 w-4" />
                    {valueOrDash([clinic.city, clinic.pincode].filter(Boolean).join(" - "))}
                  </span>
                ) : null}
                {clinic.mobile ? (
                  <span className="inline-flex items-center gap-1.5">
                    <Phone className="h-4 w-4" />
                    {clinic.mobile}
                  </span>
                ) : null}
                {dayFee || nightFee ? (
                  <span className="inline-flex items-center gap-1.5">
                    <IndianRupee className="h-4 w-4" />
                    {dayFee ? `Day ${dayFee}` : ""}
                    {dayFee && nightFee ? " · " : ""}
                    {nightFee ? `Night ${nightFee}` : ""}
                  </span>
                ) : null}
              </div>
              {fullAddress ? (
                <p className="mt-3 max-w-3xl text-sm text-slate-500">
                  {fullAddress}
                </p>
              ) : null}
              {detailSummary ? (
                <p className="mt-5 max-w-3xl text-lg text-slate-600">
                  {detailSummary}
                </p>
              ) : null}
            </div>

            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
              {hasClinicImage ? (
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                  <img
                    src={getClinicImage(clinic)}
                    alt={clinic.name || "Veterinary clinic"}
                    className="aspect-[16/10] h-full w-full object-cover"
                    onError={(event) => {
                      event.currentTarget.onerror = null;
                      event.currentTarget.src = clinicFallbackImage;
                    }}
                  />
                </div>
              ) : null}
              {hasClinicVideo ? (
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
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
                </div>
              ) : null}
              {!hasClinicImage && !hasClinicVideo ? (
                <img
                  src={getClinicImage(clinic)}
                  alt={clinic.name || "Veterinary clinic"}
                  className="aspect-[16/10] rounded-2xl border border-slate-200 object-cover"
                />
              ) : null}
            </div>
          </div>
        </div>
      </section>

      <section className="bg-slate-50 py-10 sm:py-12">
        {summaryCards.length ? (
        <div className="mx-auto grid max-w-7xl gap-5 px-4 sm:px-6 lg:grid-cols-4 lg:px-8">
          {summaryCards.map(([, label, Icon]) => (
            <div
              key={label}
              className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
            >
              <Icon className="mb-4 h-6 w-6 text-blue-600" />
              <p className="text-2xl font-bold text-slate-950">{label}</p>
            </div>
          ))}
        </div>
        ) : null}

        <div className="mx-auto mt-6 grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[1.25fr_0.75fr] lg:px-8">
          <div className="space-y-6">
            {dayFee || nightFee ? (
            <DetailSection title="Clinic Fees">
              <div className="grid gap-3 sm:grid-cols-2">
                {dayFee ? (
                <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                  <p className="text-sm font-semibold text-slate-500">Day fee</p>
                  <p className="mt-2 text-2xl font-bold text-slate-950">
                    {dayFee}
                  </p>
                </div>
                ) : null}
                {nightFee ? (
                <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                  <p className="text-sm font-semibold text-slate-500">Night fee</p>
                  <p className="mt-2 text-2xl font-bold text-slate-950">
                    {nightFee}
                  </p>
                </div>
                ) : null}
              </div>
            </DetailSection>
            ) : null}

            {daySlots.length ? (
            <DetailSection title="Clinic Day Slots">
              <SlotList slots={daySlots} />
            </DetailSection>
            ) : null}

            {nightSlots.length ? (
            <DetailSection title="Clinic Night Slots">
              <SlotList slots={nightSlots} />
            </DetailSection>
            ) : null}

            {doctors.length ? (
            <DetailSection title="Doctors">
              <div className="divide-y divide-slate-100">
                {doctors.map((doctor) => {
                    const specializations = parseList(
                      doctor.specialization_select_all_that_apply
                    );
                    const languages = parseList(doctor.languages_spoken);
                    const doctorFees = [
                      ["Clinic", formatMoney(doctor.doctors_price)],
                      ["Video day", formatMoney(doctor.video_day_rate)],
                      ["Video night", formatMoney(doctor.video_night_rate)],
                    ].filter(([, value]) => value);

                    return (
                    <div key={doctor.id} className="py-4 first:pt-0 last:pb-0">
                      <h3 className="text-lg font-semibold text-slate-950">
                        {valueOrDash(doctor.doctor_name)}
                      </h3>
                      {doctor.degree || doctor.years_of_experience ? (
                        <p className="mt-1 text-sm text-slate-600">
                          {doctor.degree || ""}
                          {doctor.degree && doctor.years_of_experience ? " · " : ""}
                          {doctor.years_of_experience
                            ? `${doctor.years_of_experience} yrs`
                            : ""}
                        </p>
                      ) : null}
                      {specializations.length ? (
                        <div className="mt-3 flex flex-wrap gap-2">
                          {specializations.map((item) => (
                          <span
                            key={item}
                            className="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700"
                          >
                            {item}
                          </span>
                          ))}
                        </div>
                      ) : null}
                      {doctorFees.length ? (
                        <div className="mt-3 grid gap-2 text-sm text-slate-600 sm:grid-cols-3">
                          {doctorFees.map(([label, value]) => (
                            <span key={label}>
                              {label}: {value}
                            </span>
                          ))}
                        </div>
                      ) : null}
                      {languages.length ? (
                        <p className="mt-2 text-sm text-slate-500">
                          Languages: {languages.join(", ")}
                        </p>
                      ) : null}
                    </div>
                    );
                  })}
              </div>
            </DetailSection>
            ) : null}

            {serviceRows.length ? (
            <DetailSection title="Services">
              <div className="grid gap-3 sm:grid-cols-2">
                {serviceRows.map((service) => (
                    <div
                      key={service.id}
                      className="rounded-lg border border-slate-200 bg-slate-50 p-4"
                    >
                      <h3 className="font-semibold text-slate-950">
                        {valueOrDash(service.name)}
                      </h3>
                      {service.description || service.pet_type ? (
                        <p className="mt-1 text-sm text-slate-600">
                          {service.description || service.pet_type}
                        </p>
                      ) : null}
                      {formatMoney(service.price) ? (
                        <p className="mt-3 text-sm font-semibold text-blue-700">
                          {formatMoney(service.price)}
                        </p>
                      ) : null}
                      {service.duration ? (
                        <p className="mt-1 text-xs text-slate-500">
                          Duration: {service.duration} mins
                        </p>
                      ) : null}
                    </div>
                  ))}
              </div>
            </DetailSection>
            ) : null}

            {machineRows.length ? (
            <DetailSection title="Machinery">
              <div className="grid gap-3 sm:grid-cols-2">
                {machineRows.map((machine) => (
                    <div
                      key={machine.id}
                      className="rounded-lg border border-slate-200 bg-slate-50 p-4"
                    >
                      <h3 className="font-semibold text-slate-950">
                        {valueOrDash(machine.name)}
                      </h3>
                      {machine.description || machine.pet_type ? (
                        <p className="mt-1 text-sm text-slate-600">
                          {machine.description || machine.pet_type}
                        </p>
                      ) : null}
                      {formatMoney(machine.price) ? (
                        <p className="mt-3 text-sm font-semibold text-blue-700">
                          {formatMoney(machine.price)}
                        </p>
                      ) : null}
                    </div>
                  ))}
              </div>
            </DetailSection>
            ) : null}

            {displayPackages.length ? (
            <DetailSection title="Packages">
              <div className="grid gap-3 sm:grid-cols-2">
                {displayPackages.map((pack) => (
                    <div
                      key={pack.id}
                      className="rounded-lg border border-slate-200 bg-slate-50 p-4"
                    >
                      <p className="font-semibold text-slate-950">
                        {pack.doctor_name || "Specialized package"}
                      </p>
                      <div className="mt-3 space-y-1 text-sm text-slate-600">
                        {pack.packagePrices.map(([key, value]) => (
                            <p key={key}>
                              {formatLabel(key.replace("_price", ""))}:{" "}
                              <span className="font-semibold text-slate-800">
                                {formatMoney(value)}
                              </span>
                            </p>
                          ))}
                      </div>
                    </div>
                  ))}
              </div>
            </DetailSection>
            ) : null}
          </div>

          <aside className="space-y-6">
            {fullAddress || clinic.city || clinic.pincode || clinic.lat || clinic.rating ? (
            <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
              <h2 className="text-xl font-bold text-slate-950">Location</h2>
              {fullAddress ? (
                <p className="mt-3 text-sm text-slate-600">{fullAddress}</p>
              ) : null}
              <div className="mt-4 space-y-2 text-sm text-slate-600">
                {clinic.city ? <p>City: {clinic.city}</p> : null}
                {clinic.pincode ? <p>Pincode: {clinic.pincode}</p> : null}
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
            ) : null}

            {vetAtHomeServices.length ? (
            <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
              <h2 className="text-xl font-bold text-slate-950">Vet at home</h2>
              <div className="mt-4 space-y-3">
                {vetAtHomeServices.map((service) => (
                    <div
                      key={service.id}
                      className="rounded-lg border border-slate-200 bg-slate-50 p-4"
                    >
                      <p className="font-semibold text-slate-950">
                        {service.doctor_name || "Home visit"}
                      </p>
                      <div className="mt-2 space-y-1 text-sm text-slate-600">
                        {service.is_enabled !== null ? (
                          <p>Status: {service.is_enabled ? "Available" : "Not available"}</p>
                        ) : null}
                        {service.service_hours ? <p>Hours: {service.service_hours}</p> : null}
                        {service.response_time ? <p>Response: {service.response_time}</p> : null}
                        {formatMoney(service.base_payout) ? (
                          <p>Base payout: {formatMoney(service.base_payout)}</p>
                        ) : null}
                        {service.protocol_label ? <p>Protocol: {service.protocol_label}</p> : null}
                      </div>
                    </div>
                  ))}
              </div>
            </section>
            ) : null}

            {videoSchedules.length ? (
            <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
              <h2 className="text-xl font-bold text-slate-950">Video hours</h2>
              <div className="mt-4 space-y-3">
                {videoSchedules.map((schedule) => {
                    const availability = schedule.availability || [];
                    const hasVideoRates =
                      formatMoney(schedule.day_rate) || formatMoney(schedule.night_rate);

                    return (
                    <div
                      key={schedule.doctor_id}
                      className="rounded-lg border border-slate-200 bg-slate-50 p-4"
                    >
                      <p className="font-semibold text-slate-950">
                        {valueOrDash(schedule.doctor_name)}
                      </p>
                      {hasVideoRates ? (
                        <div className="mt-2 flex flex-wrap gap-2 text-xs font-semibold text-blue-700">
                          {formatMoney(schedule.day_rate) ? (
                            <span className="rounded-full bg-blue-50 px-2.5 py-1">
                              Day {formatMoney(schedule.day_rate)}
                            </span>
                          ) : null}
                          {formatMoney(schedule.night_rate) ? (
                            <span className="rounded-full bg-blue-50 px-2.5 py-1">
                              Night {formatMoney(schedule.night_rate)}
                            </span>
                          ) : null}
                        </div>
                      ) : null}
                      {availability.length ? (
                        <div className="mt-3 space-y-1 text-sm text-slate-600">
                          {availability.map((slot) => (
                            <p
                              key={`${schedule.doctor_id}-${slot.day_of_week}-${slot.start_time}-${slot.end_time}`}
                              className="flex items-center gap-1.5"
                            >
                              <Clock className="h-4 w-4 text-slate-400" />
                              {formatSlot(slot)}
                            </p>
                          ))}
                        </div>
                      ) : null}
                    </div>
                    );
                  })}
              </div>
            </section>
            ) : null}
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
