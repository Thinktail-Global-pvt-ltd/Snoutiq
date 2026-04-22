import React, { useCallback, useEffect, useMemo, useState } from "react";
import {
  ArrowLeft,
  Building2,
  Calendar,
  Clock3,
  MessageSquare,
  RefreshCw,
  UserRound,
  Video,
} from "lucide-react";
import { useNavigate } from "react-router-dom";
import { readAiAuthState } from "./AiAuth";
import { fetchPetOverview } from "./petOverviewService";

const normalizeText = (value) => String(value ?? "").trim();

const normalizeBoolean = (value) => {
  if (typeof value === "boolean") return value;
  if (typeof value === "number") return value === 1;
  const normalized = String(value ?? "").trim().toLowerCase();
  return normalized === "1" || normalized === "true" || normalized === "yes";
};

const hasUsablePetProfile = (authState) => {
  const user = authState?.user || {};
  const primaryPet =
    user?.pet && typeof user.pet === "object"
      ? user.pet
      : Array.isArray(user?.pets) && user.pets.length > 0
        ? user.pets[0]
        : null;

  const registrationFlag =
    normalizeBoolean(authState?.registrationComplete) ||
    normalizeBoolean(user?.registrationComplete) ||
    normalizeBoolean(user?.registration_complete) ||
    normalizeBoolean(user?.profileCompleted);

  if (registrationFlag) return true;

  const petName = String(primaryPet?.name ?? primaryPet?.pet_name ?? user?.pet_name ?? "").trim();
  const ownerName = String(user?.pet_owner_name ?? user?.owner_name ?? user?.name ?? "").trim();

  return Boolean(petName && ownerName);
};

const resolvePetImageUrl = (...sources) => {
  for (const source of sources) {
    if (!source || typeof source !== "object") continue;

    const raw =
      source?.pet_doc1 ??
      source?.petDoc1 ??
      source?.pet_image_url ??
      source?.petImageUrl ??
      source?.avatar ??
      source?.photo ??
      source?.image ??
      source?.image_url ??
      source?.imageUrl ??
      source?.profile_image ??
      source?.profileImage ??
      source?.pet_photo ??
      source?.petPhoto ??
      "";

    const value = String(raw || "").trim();
    if (!value) continue;

    if (
      value.startsWith("http://") ||
      value.startsWith("https://") ||
      value.startsWith("blob:") ||
      value.startsWith("data:")
    ) {
      return value;
    }

    if (value.startsWith("/")) {
      return `https://snoutiq.com${value}`;
    }

    return `https://snoutiq.com/${value.replace(/^\/+/, "")}`;
  }

  return "";
};

const sanitizeDisplayText = (value) => {
  if (value == null) return "";
  if (typeof value === "string") return value.trim();
  if (typeof value === "number") return Number.isFinite(value) ? String(value) : "";
  return "";
};

const parseDateValue = (value) => {
  if (value == null || value === "") return null;
  if (value instanceof Date && !Number.isNaN(value.getTime())) return value;
  if (typeof value === "number" && Number.isFinite(value)) {
    const fromNumber = new Date(value);
    return Number.isNaN(fromNumber.getTime()) ? null : fromNumber;
  }
  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime()) ? null : parsed;
};

const formatPrettyDate = (value) => {
  const parsed = parseDateValue(value);
  if (!parsed) return "Date pending";
  return parsed.toLocaleDateString("en-IN", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
};

const formatPrettyDayTime = (value) => {
  const parsed = parseDateValue(value);
  if (!parsed) return "Date pending";
  const day = parsed.toLocaleDateString("en-IN", { weekday: "long" });
  const time = parsed.toLocaleTimeString("en-IN", {
    hour: "numeric",
    minute: "2-digit",
  });
  return `${day}, ${time}`;
};

const normalizeDoctorDisplayName = (value) => {
  const text = normalizeText(value);
  if (!text) return "";
  return /^dr\.?\s/i.test(text) ? text : `Dr. ${text}`;
};

const buildDateTimeCandidate = (dateValue, timeValue) => {
  const dateText = sanitizeDisplayText(dateValue);
  const timeText = sanitizeDisplayText(timeValue);
  if (!dateText) return null;
  return timeText ? `${dateText} ${timeText}` : dateText;
};

const getAppointmentDateTime = (appointment) => {
  if (!appointment || typeof appointment !== "object") return null;

  const directCandidates = [
    appointment?.appointment_at,
    appointment?.appointmentAt,
    appointment?.scheduled_at,
    appointment?.scheduledAt,
    appointment?.start_at,
    appointment?.startAt,
    appointment?.starts_at,
    appointment?.startsAt,
    appointment?.visit_at,
    appointment?.visitAt,
    appointment?.consult_at,
    appointment?.consultAt,
    appointment?.datetime,
    appointment?.date_time,
    appointment?.dateTime,
    appointment?.booking_at,
    appointment?.bookingAt,
  ];

  for (const candidate of directCandidates) {
    const parsed = parseDateValue(candidate);
    if (parsed) return parsed;
  }

  const dateCandidate =
    appointment?.appointment_date ??
    appointment?.appointmentDate ??
    appointment?.scheduled_date ??
    appointment?.scheduledDate ??
    appointment?.visit_date ??
    appointment?.visitDate ??
    appointment?.date ??
    null;

  const timeCandidate =
    appointment?.appointment_time ??
    appointment?.appointmentTime ??
    appointment?.time_slot ??
    appointment?.timeSlot ??
    appointment?.slot_time ??
    appointment?.slotTime ??
    appointment?.start_time ??
    appointment?.startTime ??
    appointment?.time ??
    appointment?.slot ??
    null;

  return parseDateValue(buildDateTimeCandidate(dateCandidate, timeCandidate));
};

const getAppointmentStatusValue = (appointment) =>
  appointment?.status ??
  appointment?.appointment_status ??
  appointment?.appointmentStatus ??
  appointment?.state ??
  appointment?.consult_status ??
  "";

const getAppointmentDoctorName = (appointment) =>
  sanitizeDisplayText(
    appointment?.doctor_name ||
      appointment?.doctorName ||
      appointment?.doctor?.name ||
      appointment?.doctor?.full_name
  );

const getAppointmentClinicName = (appointment) =>
  sanitizeDisplayText(
    appointment?.clinic_name ||
      appointment?.clinicName ||
      appointment?.clinic?.name ||
      appointment?.hospital_name ||
      appointment?.hospitalName
  );

const resolveDoctorIdFromRecord = (record) => {
  if (!record || typeof record !== "object") return null;
  const candidates = [
    record?.doctor_id,
    record?.doctorId,
    record?.doctor?.id,
    record?.doctor?.doctor_id,
    record?.doctor?.doctorId,
    record?.doctor_user_id,
    record?.doctorUserId,
    record?.doctor?.user_id,
    record?.doctor?.userId,
  ];
  for (const candidate of candidates) {
    const normalized = normalizeText(candidate);
    if (normalized) return normalized;
  }
  return null;
};

const resolveClinicIdFromRecord = (record) => {
  if (!record || typeof record !== "object") return null;
  const candidates = [
    record?.clinic_id,
    record?.clinicId,
    record?.clinic?.id,
    record?.clinic?.clinic_id,
    record?.clinic?.clinicId,
    record?.vet_registeration_id,
    record?.vetRegisterationId,
    record?.vet_id,
    record?.vetId,
    record?.hospital_id,
    record?.hospitalId,
  ];
  for (const candidate of candidates) {
    const normalized = normalizeText(candidate);
    if (normalized && normalized !== "0") return normalized;
  }
  return null;
};

const resolvePetIdFromRecord = (record) => {
  if (!record || typeof record !== "object") return null;
  const candidates = [record?.pet_id, record?.petId, record?.pet?.id, record?.pet?.pet_id, record?.pet?.petId];
  for (const candidate of candidates) {
    const normalized = normalizeText(candidate);
    if (normalized) return normalized;
  }
  return null;
};

const resolveDoctorImageUrl = (...records) => {
  for (const record of records) {
    if (!record || typeof record !== "object") continue;

    const candidates = [
      record?.doctor_image_blob_url,
      record?.doctorImageBlobUrl,
      record?.doctor_image_url,
      record?.doctorImageUrl,
      record?.doctor_image,
      record?.profile_image,
      record?.profileImage,
      record?.profile_photo,
      record?.profilePhoto,
      record?.image,
      record?.image_url,
      record?.imageUrl,
      record?.avatar,
      record?.avatar_url,
      record?.avatarUrl,
      record?.photo,
      record?.doctor?.doctor_image_blob_url,
      record?.doctor?.doctorImageBlobUrl,
      record?.doctor?.doctor_image_url,
      record?.doctor?.doctorImageUrl,
      record?.doctor?.doctor_image,
      record?.doctor?.profile_image,
      record?.doctor?.profileImage,
      record?.doctor?.image,
      record?.doctor?.image_url,
      record?.doctor?.avatar,
      record?.doctor?.photo,
    ];

    for (const candidate of candidates) {
      const normalized = sanitizeDisplayText(candidate);
      if (!normalized) continue;
      if (/^(?:https?:|file:|content:|data:)/i.test(normalized)) {
        return normalized;
      }
      return normalized.startsWith("/")
        ? `https://snoutiq.com${normalized}`
        : `https://snoutiq.com/${normalized.replace(/^\/+/, "")}`;
    }
  }

  return "";
};

const stripHtmlToText = (value) => {
  const raw = String(value || "").trim();
  if (!raw) return "";
  const withBreaks = raw
    .replace(/<br\s*\/?>/gi, "\n")
    .replace(/<\/p>/gi, "\n")
    .replace(/<\/div>/gi, "\n")
    .replace(/<li>/gi, "- ")
    .replace(/<\/li>/gi, "\n");
  const withoutTags = withBreaks.replace(/<[^>]*>/g, " ");
  return withoutTags.replace(/\s+/g, " ").trim();
};

const truncateText = (value, maxLength = 96) => {
  const normalized = normalizeText(value);
  if (!normalized) return "";
  if (normalized.length <= maxLength) return normalized;
  return `${normalized.slice(0, Math.max(0, maxLength - 1)).trimEnd()}…`;
};

const resolveLatestPrescription = (overviewData) => {
  const list = Array.isArray(overviewData?.prescriptions) ? overviewData.prescriptions : [];
  if (!list.length) return null;
  return [...list]
    .map((item, index) => ({ item, index, createdAtMs: parseDateValue(item?.created_at || item?.createdAt || item?.updated_at || item?.updatedAt)?.getTime() || 0 }))
    .sort((left, right) => {
      if (right.createdAtMs !== left.createdAtMs) return right.createdAtMs - left.createdAtMs;
      return left.index - right.index;
    })[0]?.item || null;
};

const resolvePrescriptionMode = (prescription) => {
  if (!prescription || typeof prescription !== "object") return "unknown";
  if (prescription?.in_clinic_appointment_id) return "in_clinic";
  if (prescription?.video_appointment_id) return "video";

  const visitMode = normalizeText(prescription?.video_inclinic ?? prescription?.videoInclinic ?? "").toLowerCase();
  if (visitMode === "video") return "video";
  if (visitMode === "in_clinic") return "in_clinic";

  const visitCategory = normalizeText(prescription?.visit_category ?? prescription?.visitCategory ?? "").toLowerCase();
  if (visitCategory.includes("video")) return "video";
  if (visitCategory.includes("clinic") || visitCategory.includes("in clinic")) return "in_clinic";
  return "unknown";
};

const buildPrescriptionSymptomPrefill = (prescription) => {
  if (!prescription || typeof prescription !== "object") return "";

  const baseText =
    normalizeText(
      prescription?.follow_up_notes ??
        prescription?.follow_up_note ??
        prescription?.followUpNotes ??
        prescription?.followUpNote ??
        ""
    ) ||
    normalizeText(prescription?.diagnosis ?? "") ||
    normalizeText(prescription?.disease_name ?? prescription?.diseaseName ?? "") ||
    normalizeText(prescription?.visit_notes ?? prescription?.visitNotes ?? "") ||
    stripHtmlToText(prescription?.content_html ?? prescription?.contentHtml ?? "");

  const homeCareText = truncateText(stripHtmlToText(prescription?.home_care ?? prescription?.homeCare ?? ""), 120);

  if (!baseText) return homeCareText ? `Home care: ${homeCareText}` : "";
  if (!homeCareText) return baseText;
  if (baseText.toLowerCase().includes(homeCareText.toLowerCase())) return baseText;
  return `${baseText}\n\nHome care: ${homeCareText}`;
};

const buildLatestPrescriptionFollowup = ({ overviewData, selectedPetId }) => {
  const latestPrescription = resolveLatestPrescription(overviewData);
  if (!latestPrescription) return null;

  const mode = resolvePrescriptionMode(latestPrescription);
  const latestAppointments =
    overviewData?.latest_appointments && typeof overviewData.latest_appointments === "object"
      ? overviewData.latest_appointments
      : null;

  const videoAppointment =
    overviewData?.video_call_appointment || latestAppointments?.video_call || latestAppointments?.videoCall || null;
  const inClinicAppointment =
    overviewData?.in_clinic_appointment ||
    latestAppointments?.in_clinic ||
    latestAppointments?.inClinic ||
    latestAppointments?.clinic_visit ||
    latestAppointments?.clinicVisit ||
    null;

  const appointment = mode === "video" ? videoAppointment : inClinicAppointment;
  const doctorId = resolveDoctorIdFromRecord(latestPrescription) || resolveDoctorIdFromRecord(appointment) || null;
  const clinicId = resolveClinicIdFromRecord(latestPrescription) || resolveClinicIdFromRecord(appointment) || null;
  const petId = resolvePetIdFromRecord(latestPrescription) || resolvePetIdFromRecord(overviewData?.pet) || normalizeText(selectedPetId) || null;
  const doctorName =
    sanitizeDisplayText(
      latestPrescription?.doctor_name ||
        latestPrescription?.doctorName ||
        latestPrescription?.doctor?.name ||
        appointment?.doctor_name ||
        appointment?.doctorName ||
        appointment?.doctor?.name
    ) || "";
  const clinicName =
    sanitizeDisplayText(
      latestPrescription?.clinic_name || latestPrescription?.clinicName || latestPrescription?.clinic?.name || appointment?.clinic_name || appointment?.clinicName || appointment?.clinic?.name
    ) || "";

  const diagnosis = normalizeText(latestPrescription?.diagnosis ?? "");
  const diseaseName = normalizeText(latestPrescription?.disease_name ?? latestPrescription?.diseaseName ?? "");
  const visitNotes = normalizeText(latestPrescription?.visit_notes ?? latestPrescription?.visitNotes ?? "");
  const followUpDate =
    latestPrescription?.follow_up_date ??
    latestPrescription?.follow_up_at ??
    latestPrescription?.followUpDate ??
    latestPrescription?.followUpAt ??
    null;

  const symptomText = buildPrescriptionSymptomPrefill(latestPrescription);
  const issueText =
    truncateText(diagnosis || diseaseName || visitNotes || symptomText || "Follow-up recommended.", 82) ||
    "Follow-up recommended.";

  return {
    kind: "followup",
    mode,
    doctorId,
    clinicId,
    petId,
    doctorName,
    clinicName,
    doctorImageUrl: resolveDoctorImageUrl(latestPrescription, appointment),
    title: mode === "video" ? "Video Follow-Up" : mode === "in_clinic" ? "Clinic Follow-Up" : "Latest Follow-Up",
    subtitle:
      mode === "video"
        ? [normalizeDoctorDisplayName(doctorName), "Video Consultation"].filter(Boolean).join(" • ")
        : [clinicName, normalizeDoctorDisplayName(doctorName)].filter(Boolean).join(" • "),
    description: `${issueText}${followUpDate ? `\nFollow-up date: ${formatPrettyDate(followUpDate)}` : ""}`.trim(),
    dateText: followUpDate ? formatPrettyDate(followUpDate) : "Date pending",
    ctaLabel: mode === "video" ? "Continue" : mode === "in_clinic" ? "Continue" : "Open",
    createdAt: latestPrescription?.created_at ?? latestPrescription?.createdAt ?? latestPrescription?.updated_at ?? latestPrescription?.updatedAt ?? null,
    followUpDate,
    symptomText,
    raw: latestPrescription,
  };
};

const getUpcomingClinicAppointment = (overviewData, nowMs = Date.now()) => {
  const rawCandidates = [
    overviewData?.in_clinic_appointment,
    overviewData?.latest_appointments?.in_clinic,
    overviewData?.latest_appointments?.inClinic,
    overviewData?.latest_appointments?.clinic_visit,
    overviewData?.latest_appointments?.clinicVisit,
  ].filter((item) => item && typeof item === "object");

  const upcomingEntries = rawCandidates
    .map((appointment) => {
      const scheduledAt = getAppointmentDateTime(appointment);
      const scheduledAtMs = scheduledAt?.getTime() ?? null;
      if (!scheduledAtMs || scheduledAtMs <= nowMs) return null;

      const doctorName = getAppointmentDoctorName(appointment);
      const clinicName = getAppointmentClinicName(appointment);

      return {
        kind: "appointment",
        raw: appointment,
        scheduledAt,
        title: "Clinic Appointment",
        doctorId: resolveDoctorIdFromRecord(appointment),
        doctorName,
        clinicName,
        doctorImageUrl: resolveDoctorImageUrl(appointment),
        dateText: formatPrettyDayTime(scheduledAt),
        subtitle: [doctorName ? `With ${normalizeDoctorDisplayName(doctorName)}` : "", clinicName].filter(Boolean).join(" • "),
        description: formatPrettyDayTime(scheduledAt),
        ctaLabel: "Open",
      };
    })
    .filter(Boolean)
    .sort((left, right) => left.scheduledAt.getTime() - right.scheduledAt.getTime());

  return upcomingEntries[0] || null;
};

const getActiveVideoCallCard = (overviewData) => {
  const appointment =
    overviewData?.video_call_appointment ||
    overviewData?.latest_appointments?.video_call ||
    overviewData?.latest_appointments?.videoCall ||
    null;

  if (!appointment || typeof appointment !== "object") return null;

  const doctorName = getAppointmentDoctorName(appointment);
  const scheduledAt = getAppointmentDateTime(appointment);

  return {
    kind: "video",
    raw: appointment,
    title: "Video Consultation",
    subtitle: [normalizeDoctorDisplayName(doctorName), getAppointmentStatusValue(appointment) || "Scheduled"].filter(Boolean).join(" • "),
    description: scheduledAt ? formatPrettyDayTime(scheduledAt) : "Consultation scheduled",
    dateText: scheduledAt ? formatPrettyDayTime(scheduledAt) : "Time pending",
    doctorId: resolveDoctorIdFromRecord(appointment),
    doctorName,
    doctorImageUrl: resolveDoctorImageUrl(appointment),
    ctaLabel: "Open",
  };
};

const resolveCurrentPet = (authState) => {
  const currentUser = authState?.user || {};
  return currentUser?.pet && typeof currentUser.pet === "object"
    ? currentUser.pet
    : Array.isArray(currentUser?.pets) && currentUser.pets.length > 0
      ? currentUser.pets[0]
      : null;
};

const resolvePetIdFromAuth = (authState) => {
  const currentUser = authState?.user || {};
  const currentPet = resolveCurrentPet(authState);
  return (
    currentPet?.id ??
    currentPet?.pet_id ??
    currentUser?.pet_id ??
    currentUser?.pet?.id ??
    currentUser?.pet?.pet_id ??
    ""
  );
};

const AppHeader = ({ title, onBack }) => (
  <div className="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div className="mx-auto flex max-w-3xl items-center gap-3 px-4 py-4 sm:px-6">
      <button
        type="button"
        onClick={onBack}
        className="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
        aria-label="Go back"
      >
        <ArrowLeft size={18} />
      </button>
      <h1 className="flex-1 text-center text-lg font-semibold text-slate-900">
        {title}
      </h1>
      <div className="h-10 w-10 shrink-0" aria-hidden="true" />
    </div>
  </div>
);

const PetNameBar = ({ petName, petImageUrl }) => (
  <div className="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm">
    <div className="flex items-center gap-3">
      {petImageUrl ? (
        <img src={petImageUrl} alt={petName || "Pet"} className="h-12 w-12 rounded-2xl object-cover" />
      ) : (
        <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
          <UserRound size={18} />
        </div>
      )}
      <div>
        <p className="text-xs font-medium uppercase tracking-[0.18em] text-slate-400">Pet</p>
        <p className="text-base font-semibold text-slate-900">{petName || "Your Pet"}</p>
      </div>
    </div>
  </div>
);

const ActionCard = ({ title, subtitle, description, dateText, icon, imageUrl, actionLabel, onAction }) => (
  <div className="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm">
    <div className="flex items-start gap-4">
      {imageUrl ? (
        <img src={imageUrl} alt={title} className="h-14 w-14 rounded-2xl object-cover" />
      ) : (
        <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
          {icon}
        </div>
      )}
      <div className="min-w-0 flex-1">
        <h2 className="text-base font-semibold text-slate-900">{title}</h2>
        {subtitle ? <p className="mt-1 text-sm text-slate-500">{subtitle}</p> : null}
        {dateText ? <p className="mt-2 text-sm font-medium text-slate-700">{dateText}</p> : null}
      </div>
    </div>

    {description ? <p className="mt-4 whitespace-pre-wrap text-sm leading-6 text-slate-600">{description}</p> : null}

    {actionLabel ? (
      <button
        type="button"
        onClick={onAction}
        className="mt-4 inline-flex items-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
      >
        {actionLabel}
      </button>
    ) : null}
  </div>
);

function EmptyState({ message }) {
  return (
    <div className="rounded-[24px] border border-slate-200 bg-white p-8 text-center shadow-sm">
      <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
        <Calendar size={20} />
      </div>
      <p className="mt-4 text-sm font-medium text-slate-600">{message}</p>
    </div>
  );
}

function ErrorState({ error, onRetry }) {
  return (
    <div className="rounded-[24px] border border-red-200 bg-red-50 p-5 text-red-700 shadow-sm">
      <p className="text-sm font-medium">{error}</p>
      <button
        type="button"
        onClick={onRetry}
        className="mt-4 inline-flex items-center gap-2 rounded-2xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700"
      >
        <RefreshCw size={16} /> Refresh
      </button>
    </div>
  );
}

function LoadingState() {
  return (
    <div className="space-y-4">
      {Array.from({ length: 2 }).map((_, index) => (
        <div key={index} className="animate-pulse rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm">
          <div className="flex items-start gap-4">
            <div className="h-14 w-14 rounded-2xl bg-slate-200" />
            <div className="flex-1">
              <div className="h-4 w-40 rounded bg-slate-200" />
              <div className="mt-3 h-3 w-52 rounded bg-slate-100" />
              <div className="mt-3 h-3 w-32 rounded bg-slate-100" />
            </div>
          </div>
          <div className="mt-5 h-12 rounded-2xl bg-slate-100" />
        </div>
      ))}
    </div>
  );
}

function FollowupDetails({ followupCard }) {
  if (!followupCard) return null;

  return (
    <div className="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm">
      <h3 className="text-base font-semibold text-slate-900">Details</h3>

      <div className="mt-4 space-y-3">
        <div className="flex items-center gap-3 rounded-2xl bg-slate-50 p-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-slate-600">
            <UserRound size={18} />
          </div>
          <div>
            <p className="text-xs font-medium uppercase tracking-[0.16em] text-slate-400">Doctor</p>
            <p className="text-sm font-medium text-slate-900">
              {followupCard.doctorName ? normalizeDoctorDisplayName(followupCard.doctorName) : "Not available"}
            </p>
          </div>
        </div>

        <div className="flex items-center gap-3 rounded-2xl bg-slate-50 p-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-slate-600">
            <Building2 size={18} />
          </div>
          <div>
            <p className="text-xs font-medium uppercase tracking-[0.16em] text-slate-400">Clinic</p>
            <p className="text-sm font-medium text-slate-900">{followupCard.clinicName || "Not available"}</p>
          </div>
        </div>

        <div className="flex items-center gap-3 rounded-2xl bg-slate-50 p-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-slate-600">
            <Clock3 size={18} />
          </div>
          <div>
            <p className="text-xs font-medium uppercase tracking-[0.16em] text-slate-400">Date</p>
            <p className="text-sm font-medium text-slate-900">{followupCard.followUpDate ? formatPrettyDate(followupCard.followUpDate) : "Pending"}</p>
          </div>
        </div>
      </div>

      {followupCard.symptomText ? (
        <div className="mt-4 rounded-2xl bg-slate-50 p-4">
          <p className="text-xs font-medium uppercase tracking-[0.16em] text-slate-400">Summary</p>
          <p className="mt-2 whitespace-pre-wrap text-sm leading-6 text-slate-700">{followupCard.symptomText}</p>
        </div>
      ) : null}
    </div>
  );
}

function usePetOverviewCards(authState = readAiAuthState()) {
  const currentUser = authState?.user || {};
  const currentPet = useMemo(() => resolveCurrentPet(authState), [authState]);
  const resolvedPetId = useMemo(() => resolvePetIdFromAuth(authState), [authState]);
  const petDisplayName = String(currentPet?.name ?? currentPet?.pet_name ?? currentUser?.pet_name ?? "").trim();
  const petProfileImageUrl = resolvePetImageUrl(currentPet, currentUser?.pet, currentUser);

  const [overview, setOverview] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const loadOverview = useCallback(
    async (forceRefresh = false) => {
      if (!resolvedPetId) {
        setOverview(null);
        setLoading(false);
        setError("Pet details are unavailable.");
        return;
      }

      try {
        setLoading(true);
        setError("");
        const data = await fetchPetOverview(resolvedPetId, { forceRefresh });
        setOverview(data || null);
      } catch (err) {
        setError(err?.message || "Unable to load this page.");
      } finally {
        setLoading(false);
      }
    },
    [resolvedPetId]
  );

  useEffect(() => {
    loadOverview(false);
  }, [loadOverview]);

  const videoCard = useMemo(() => getActiveVideoCallCard(overview), [overview]);
  const appointmentCard = useMemo(() => getUpcomingClinicAppointment(overview), [overview]);
  const followupCard = useMemo(() => buildLatestPrescriptionFollowup({ overviewData: overview, selectedPetId: resolvedPetId }), [overview, resolvedPetId]);

  return {
    resolvedPetId,
    petDisplayName,
    petProfileImageUrl,
    overview,
    loading,
    error,
    reload: loadOverview,
    videoCard,
    appointmentCard,
    followupCard,
  };
}

export function AppointmentPage({ authState = readAiAuthState() }) {
  const navigate = useNavigate();
  useMemo(() => hasUsablePetProfile(authState), [authState]);
  const {
    resolvedPetId,
    petDisplayName,
    petProfileImageUrl,
    loading,
    error,
    reload,
    videoCard,
    appointmentCard,
  } = usePetOverviewCards(authState);

  const cards = [videoCard, appointmentCard].filter(Boolean);

  const handleCardAction = (card) => {
    if (!card) return;
    if (card.kind === "video") {
      navigate("/video-counsult", { state: { petId: resolvedPetId, doctorId: card.doctorId, source: "appointment_page" } });
      return;
    }

    if (card.kind === "appointment") {
      navigate("/inclinic-fast-booking", { state: { petId: resolvedPetId, doctorId: card.doctorId, source: "appointment_page" } });
    }
  };

  return (
    <div className="min-h-screen bg-slate-50">
      <AppHeader title="Appointment" onBack={() => navigate(-1)} />

      <div className="mx-auto max-w-3xl space-y-4 px-4 py-4 sm:px-6">
        <PetNameBar petName={petDisplayName} petImageUrl={petProfileImageUrl} />

        <div className="flex justify-end">
          <button
            type="button"
            onClick={() => reload(true)}
            className="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
          >
            <RefreshCw size={16} /> Refresh
          </button>
        </div>

        {loading ? (
          <LoadingState />
        ) : error ? (
          <ErrorState error={error} onRetry={() => reload(true)} />
        ) : cards.length === 0 ? (
          <EmptyState message="No Data Found" />
        ) : (
          <div className="space-y-4">
            {cards.map((card, index) => (
              <ActionCard
                key={`${card.kind}-${index}`}
                title={card.title}
                subtitle={card.subtitle}
                description={card.description}
                dateText={card.dateText}
                imageUrl={card.doctorImageUrl}
                actionLabel={card.ctaLabel}
                onAction={() => handleCardAction(card)}
                icon={card.kind === "video" ? <Video size={22} /> : <Calendar size={22} />}
              />
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

export function FollowupPage({ authState = readAiAuthState() }) {
  const navigate = useNavigate();
  useMemo(() => hasUsablePetProfile(authState), [authState]);
  const {
    resolvedPetId,
    petDisplayName,
    petProfileImageUrl,
    loading,
    error,
    reload,
    followupCard,
  } = usePetOverviewCards(authState);

  const handleFollowupAction = () => {
    if (!followupCard) return;

    if (followupCard.mode === "video") {
      navigate("/video-counsult", {
        state: {
          petId: resolvedPetId,
          doctorId: followupCard.doctorId,
          prescriptionPrefill: {
            mode: "video",
            petId: followupCard.petId,
            doctorId: followupCard.doctorId,
            clinicId: followupCard.clinicId,
            doctorName: followupCard.doctorName,
            createdAt: followupCard.createdAt,
            followUpDate: followupCard.followUpDate,
            symptomText: followupCard.symptomText,
          },
        },
      });
      return;
    }

    if (followupCard.mode === "in_clinic") {
      navigate("/inclinic-fast-booking", {
        state: {
          petId: resolvedPetId,
          doctorId: followupCard.doctorId,
          prescriptionPrefill: {
            mode: "in_clinic",
            petId: followupCard.petId,
            doctorId: followupCard.doctorId,
            clinicId: followupCard.clinicId,
            doctorName: followupCard.doctorName,
            createdAt: followupCard.createdAt,
            followUpDate: followupCard.followUpDate,
            symptomText: followupCard.symptomText,
          },
        },
      });
      return;
    }

    navigate("/profile", { state: { petId: resolvedPetId } });
  };

  return (
    <div className="min-h-screen bg-slate-50">
      <AppHeader title="Follow Up" onBack={() => navigate(-1)} />

      <div className="mx-auto max-w-3xl space-y-4 px-4 py-4 sm:px-6">
        <PetNameBar petName={petDisplayName} petImageUrl={petProfileImageUrl} />

        <div className="flex justify-end">
          <button
            type="button"
            onClick={() => reload(true)}
            className="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
          >
            <RefreshCw size={16} /> Refresh
          </button>
        </div>

        {loading ? (
          <LoadingState />
        ) : error ? (
          <ErrorState error={error} onRetry={() => reload(true)} />
        ) : !followupCard ? (
          <EmptyState message="No Data Found" />
        ) : (
          <div className="grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
            <ActionCard
              title={followupCard.title}
              subtitle={followupCard.subtitle}
              description={followupCard.description}
              dateText={followupCard.dateText}
              imageUrl={followupCard.doctorImageUrl}
              actionLabel={followupCard.ctaLabel}
              onAction={handleFollowupAction}
              icon={<MessageSquare size={22} />}
            />

            <FollowupDetails followupCard={followupCard} />
          </div>
        )}
      </div>
    </div>
  );
}
