import React, { useCallback, useEffect, useMemo, useState } from "react";
import {
  CalendarDays,
  ChevronLeft,
  ChevronRight,
  Download,
  Eye,
  FileText,
  FlaskConical,
  Loader2,
  Share2,
  ShieldCheck,
  Stethoscope,
  Video,
  X,
} from "lucide-react";
import { useNavigate, useParams } from "react-router-dom";

const DEFAULT_PET_ID = 935;
const API_BASE = "https://snoutiq.com/backend/api";
const LIFELINE_ENDPOINT = `${API_BASE}/pets/lifeline-timeline`;
const PRESCRIPTION_PDF_ENDPOINT = `${API_BASE}/consultation/prescription/pdf`;

const SCREEN_BACKGROUND = "#F6F5F2";
const SURFACE_COLOR = "#FFFFFF";
const BORDER_COLOR = "rgba(0,0,0,0.08)";
const TEXT_PRIMARY = "#1E1D1A";
const TEXT_SECONDARY = "#4B4A46";
const TEXT_MUTED = "#6B6964";
const TEXT_SUBTLE = "#7E7B76";
const FALLBACK_BACKGROUND = "#F3F2EF";
const FALLBACK_TEXT = "#5F5E5A";
const ACTIVE_BACKGROUND = "#FFF4DB";
const ACTIVE_TEXT = "#7A4B00";
const ACTIVE_BORDER = "#C98A22";

const RECORD_THEMES = {
  vaccination: {
    accentBackground: "#E1F5EE",
    accentText: "#0F6E56",
    icon: ShieldCheck,
  },
  deworming: {
    accentBackground: "#EEEDFE",
    accentText: "#534AB7",
    icon: FlaskConical,
  },
  video_consultation: {
    accentBackground: "#E6F1FB",
    accentText: "#185FA5",
    icon: Video,
  },
  consultation: {
    accentBackground: "#E6F1FB",
    accentText: "#185FA5",
    icon: Stethoscope,
  },
  prescription: {
    accentBackground: "#F5EDFF",
    accentText: "#7C3AED",
    icon: FileText,
  },
  unknown: {
    accentBackground: "#F3F2EF",
    accentText: "#5F5E5A",
    icon: FileText,
  },
};

const toDisplayText = (value) => {
  const text =
    typeof value === "string" ? value.trim() : String(value ?? "").trim();
  if (!text) return "";
  return text
    .replace(/_/g, " ")
    .split(/\s+/)
    .filter(Boolean)
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1).toLowerCase())
    .join(" ");
};

const toTrimmedString = (value) =>
  typeof value === "string" ? value.trim() : String(value ?? "").trim();

const getTimestamp = (value) => {
  if (!value) return 0;
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? 0 : date.getTime();
};

const formatDate = (value) => {
  if (!value) return "Date unavailable";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return String(value);
  return date.toLocaleDateString("en-IN", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
};

const sanitizeFileName = (value) =>
  String(value || "").replace(/[^a-zA-Z0-9._-]/g, "_");

const getRecordTheme = (recordType) =>
  RECORD_THEMES[recordType] || RECORD_THEMES.unknown;

const resolvePetImageUrl = (pet) => {
  const raw =
    pet?.pet_doc1 ??
    pet?.petDoc1 ??
    pet?.avatar ??
    pet?.photo ??
    pet?.image ??
    pet?.image_url ??
    pet?.imageUrl ??
    pet?.profile_image ??
    pet?.profileImage ??
    pet?.pet_photo ??
    pet?.petPhoto ??
    null;

  const value = String(raw || "").trim();
  if (!value) return "";

  if (value.startsWith("http://") || value.startsWith("https://")) {
    return value;
  }

  if (value.startsWith("/")) {
    return `https://snoutiq.com${value}`;
  }

  return `https://snoutiq.com/${value.replace(/^\/+/, "")}`;
};

const getPetMetaLine = (pet) =>
  [
    toDisplayText(pet?.breed) || "Breed not available",
    toDisplayText(pet?.pet_type) || "Pet",
    toDisplayText(pet?.pet_gender) || "Unknown",
  ]
    .filter(Boolean)
    .join(" | ");

const getPetAvatarLabel = (pet) => {
  const name = typeof pet?.name === "string" ? pet.name.trim() : "";
  return name ? name.charAt(0).toUpperCase() : "P";
};

const looksLikePrescriptionRecord = (value) =>
  Boolean(
    value &&
      typeof value === "object" &&
      (value?.medical_record_id != null ||
        value?.medications_json ||
        value?.treatment_plan ||
        value?.content_html ||
        value?.diagnosis ||
        value?.disease_name ||
        value?.home_care ||
        value?.visit_notes)
  );

const getNestedPrescriptionCandidate = (value) => {
  if (!value || typeof value !== "object") return null;

  const nestedCandidates = [
    value?.prescription,
    value?.latest_prescription,
    Array.isArray(value?.prescriptions) ? value.prescriptions[0] : null,
    Array.isArray(value?.prescriptions_full) ? value.prescriptions_full[0] : null,
  ];

  return (
    nestedCandidates.find((candidate) => looksLikePrescriptionRecord(candidate)) ||
    null
  );
};

const resolveEventPrescriptionId = (event) => {
  const raw = event?.raw && typeof event.raw === "object" ? event.raw : null;
  const record = raw?.record && typeof raw.record === "object" ? raw.record : null;

  const directCandidates = [
    event?.prescription_id,
    event?.prescriptionId,
    raw?.prescription_id,
    raw?.prescriptionId,
    record?.prescription_id,
    record?.prescriptionId,
  ];

  for (const candidate of directCandidates) {
    const normalized = toTrimmedString(candidate);
    if (normalized) return normalized;
  }

  const nestedCandidates = [
    getNestedPrescriptionCandidate(event),
    getNestedPrescriptionCandidate(raw),
    getNestedPrescriptionCandidate(record),
    looksLikePrescriptionRecord(record) ? record : null,
    looksLikePrescriptionRecord(raw) ? raw : null,
  ];

  for (const candidate of nestedCandidates) {
    const normalized = toTrimmedString(
      candidate?.id ?? candidate?.prescription_id ?? candidate?.prescriptionId
    );
    if (normalized) return normalized;
  }

  return "";
};

const inferRecordType = (item) => {
  const rawType = String(
    item?.recordType ??
      item?.record_type ??
      item?.typeLabel ??
      item?.type ??
      item?.category ??
      item?.source ??
      ""
  )
    .trim()
    .toLowerCase();

  if (rawType.includes("vacc")) return "vaccination";
  if (rawType.includes("deworm")) return "deworming";
  if (rawType.includes("video")) return "video_consultation";
  if (rawType.includes("consult")) return "consultation";
  if (rawType.includes("prescription")) return "prescription";

  if (resolveEventPrescriptionId(item)) return "prescription";

  return "unknown";
};

const normalizeTimelineItem = (item, index) => {
  const recordType = inferRecordType(item);
  const occurredAt =
    item?.occurredAt ||
    item?.occurred_at ||
    item?.created_at ||
    item?.date ||
    item?.recorded_at ||
    item?.event_date ||
    null;

  const nextDueAt =
    item?.nextDueAt ||
    item?.next_due_at ||
    item?.next_due ||
    item?.nextDueDate ||
    item?.next_due_date ||
    null;

  const yearValue = getTimestamp(occurredAt || nextDueAt);
  const title =
    item?.title ||
    item?.name ||
    item?.label ||
    item?.record_name ||
    item?.vaccine_name ||
    item?.disease_name ||
    item?.typeLabel ||
    "Medical record";

  const metaLabel =
    item?.metaLabel ||
    item?.meta_label ||
    item?.status_label ||
    item?.status ||
    item?.doctor_name ||
    null;

  const subtitleLabel =
    item?.subtitleLabel ||
    item?.subtitle_label ||
    (nextDueAt ? `Next due: ${formatDate(nextDueAt)}` : null);

  const detailRows = Array.isArray(item?.detailRows)
    ? item.detailRows
    : [
        { label: "Type", value: toDisplayText(recordType) || "Unknown" },
        { label: "Date", value: formatDate(occurredAt || nextDueAt) },
        metaLabel ? { label: "Info", value: String(metaLabel) } : null,
        subtitleLabel ? { label: "Next", value: String(subtitleLabel) } : null,
      ].filter(Boolean);

  return {
    id: String(item?.id ?? `${recordType}-${index}`),
    recordType,
    typeLabel: item?.typeLabel || toDisplayText(recordType) || "Unknown",
    title: String(title),
    dateLabel: formatDate(occurredAt || nextDueAt),
    metaLabel: metaLabel ? String(metaLabel) : "",
    subtitleLabel: subtitleLabel ? String(subtitleLabel) : "",
    note:
      item?.note ||
      item?.description ||
      item?.content_html ||
      item?.visit_notes ||
      "No additional note available.",
    detailRows,
    occurredAt,
    nextDueAt,
    isActive: Boolean(item?.isActive || item?.is_active || item?.pending),
    raw: item,
    year: yearValue
      ? String(new Date(yearValue).getFullYear())
      : "Upcoming",
    prescription_id: item?.prescription_id ?? item?.prescriptionId ?? "",
  };
};

const groupByYear = (events) => {
  const groups = events.reduce((acc, event) => {
    const key = event.year || "Upcoming";
    if (!acc[key]) acc[key] = [];
    acc[key].push(event);
    return acc;
  }, {});

  return Object.keys(groups)
    .sort((a, b) => {
      if (a === "Upcoming") return -1;
      if (b === "Upcoming") return 1;
      return Number(b) - Number(a);
    })
    .map((year) => ({
      year,
      events: groups[year].sort(
        (left, right) =>
          getTimestamp(right.occurredAt || right.nextDueAt) -
          getTimestamp(left.occurredAt || left.nextDueAt)
      ),
    }));
};

const buildVaccinationReminderEvents = (vaccinations = []) => {
  if (!Array.isArray(vaccinations)) return [];

  return vaccinations
    .map((record, index) => {
      const nextDueAt =
        record?.nextDueDate ||
        record?.next_due_date ||
        record?.next_due ||
        null;

      if (!nextDueAt) return null;

      const vaccineName = String(record?.label || record?.name || "Vaccination").trim();

      return {
        id: `vaccination-reminder-${record?.key || index}`,
        recordType: "vaccination",
        typeLabel: "Vaccination",
        title: `${vaccineName} vaccine`,
        dateLabel: formatDate(record?.lastDate || record?.last_date || nextDueAt),
        metaLabel: `Status: ${toDisplayText(record?.status) || "Due"}`,
        subtitleLabel: `Next due: ${formatDate(nextDueAt)}`,
        note:
          record?.note ||
          `The next due date for ${vaccineName} is ${formatDate(nextDueAt)}.`,
        detailRows: [
          { label: "Source", value: "Vaccination" },
          { label: "Vaccine", value: vaccineName },
          { label: "Last date", value: formatDate(record?.lastDate || record?.last_date) },
          { label: "Next due", value: formatDate(nextDueAt) },
        ],
        occurredAt: record?.lastDate || record?.last_date || nextDueAt,
        nextDueAt,
        isActive: Boolean(record?.showInHero),
        raw: { derivedVaccinationRecord: record },
        year: String(new Date(nextDueAt).getFullYear()),
      };
    })
    .filter(Boolean);
};

const buildTimelineEvents = (payload) => {
  const timeline = Array.isArray(payload?.data?.timeline) ? payload.data.timeline : [];
  const vaccinations = Array.isArray(payload?.data?.vaccinations)
    ? payload.data.vaccinations
    : [];

  const baseEvents = timeline.map((item, index) => normalizeTimelineItem(item, index));
  const reminderEvents = buildVaccinationReminderEvents(vaccinations);

  const merged = [...baseEvents];

  reminderEvents.forEach((reminder) => {
    const exists = merged.some(
      (event) =>
        String(event.title).toLowerCase() === String(reminder.title).toLowerCase() &&
        String(event.subtitleLabel).toLowerCase() ===
          String(reminder.subtitleLabel).toLowerCase()
    );
    if (!exists) merged.push(reminder);
  });

  return merged.sort(
    (left, right) =>
      getTimestamp(right.occurredAt || right.nextDueAt) -
      getTimestamp(left.occurredAt || left.nextDueAt)
  );
};

function Spinner({ className = "h-4 w-4" }) {
  return <Loader2 className={`${className} animate-spin`} />;
}

function LoadingSkeleton() {
  return (
    <div className="space-y-4">
      <div className="rounded-2xl border border-black/10 bg-white p-4">
        <div className="flex items-center gap-3">
          <div className="h-12 w-12 animate-pulse rounded-full bg-neutral-200" />
          <div className="flex-1 space-y-2">
            <div className="h-4 w-2/5 animate-pulse rounded bg-neutral-200" />
            <div className="h-3 w-3/5 animate-pulse rounded bg-neutral-200" />
            <div className="flex gap-2 pt-1">
              <div className="h-6 w-20 animate-pulse rounded-full bg-neutral-200" />
              <div className="h-6 w-20 animate-pulse rounded-full bg-neutral-200" />
              <div className="h-6 w-24 animate-pulse rounded-full bg-neutral-200" />
            </div>
          </div>
        </div>
      </div>

      <div className="h-24 animate-pulse rounded-2xl bg-neutral-200" />

      {Array.from({ length: 3 }).map((_, index) => (
        <div key={index} className="flex gap-3">
          <div className="flex w-10 flex-col items-center">
            <div className="h-10 w-10 animate-pulse rounded-full bg-neutral-200" />
            {index < 2 ? <div className="mt-2 h-20 w-px bg-neutral-200" /> : null}
          </div>
          <div className="flex-1 rounded-2xl border border-black/10 bg-white p-4">
            <div className="mb-3 h-6 w-28 animate-pulse rounded-full bg-neutral-200" />
            <div className="h-4 w-1/2 animate-pulse rounded bg-neutral-200" />
            <div className="mt-2 h-3 w-1/3 animate-pulse rounded bg-neutral-200" />
            <div className="mt-2 h-3 w-2/3 animate-pulse rounded bg-neutral-200" />
          </div>
        </div>
      ))}
    </div>
  );
}

function EmptyState({ title, subtitle, actionLabel, onAction }) {
  return (
    <div className="mt-4 rounded-2xl border border-black/10 bg-white p-8 text-center">
      <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-stone-100">
        <CalendarDays className="h-5 w-5 text-stone-500" />
      </div>
      <h3 className="mt-4 text-lg font-bold text-stone-900">{title}</h3>
      <p className="mt-2 text-sm leading-6 text-stone-600">{subtitle}</p>
      {actionLabel && onAction ? (
        <button
          type="button"
          onClick={onAction}
          className="mt-5 rounded-xl border border-black/10 bg-white px-4 py-2 text-sm font-semibold text-stone-900 transition hover:bg-stone-50"
        >
          {actionLabel}
        </button>
      ) : null}
    </div>
  );
}

function PetCard({ pet, recordCount }) {
  const petImageUrl = resolvePetImageUrl(pet);
  const petAvatarLabel = getPetAvatarLabel(pet);
  const petMetaLine = getPetMetaLine(pet);

  return (
    <div className="flex items-center gap-3 rounded-2xl border border-black/10 bg-white p-4">
      <div className="flex h-12 w-12 items-center justify-center overflow-hidden rounded-full border border-black/10 bg-sky-50">
        {petImageUrl ? (
          <img
            src={petImageUrl}
            alt={pet?.name || "Pet"}
            className="h-full w-full object-cover"
          />
        ) : (
          <span className="text-base font-bold text-stone-900">{petAvatarLabel}</span>
        )}
      </div>

      <div className="min-w-0 flex-1">
        <h2 className="truncate text-lg font-bold text-stone-900">
          {pet?.name || "Pet"}
        </h2>
        <p className="mt-1 text-xs text-stone-600">{petMetaLine}</p>

        <div className="mt-3 flex flex-wrap gap-2">
          <span className="rounded-full border border-black/10 bg-stone-100 px-3 py-1 text-xs font-semibold text-stone-600">
            {toDisplayText(pet?.pet_gender) || "Unknown"}
          </span>
          <span className="rounded-full border border-black/10 bg-stone-100 px-3 py-1 text-xs font-semibold text-stone-600">
            {toDisplayText(pet?.pet_type) || "Pet"}
          </span>
          <span className="rounded-full border border-black/10 bg-stone-100 px-3 py-1 text-xs font-semibold text-stone-600">
            {recordCount} records
          </span>
        </div>
      </div>
    </div>
  );
}

function NextDueBanner({ event, onPress }) {
  const theme = getRecordTheme(event?.recordType || "unknown");
  const Icon = theme.icon;

  return (
    <button
      type="button"
      onClick={onPress}
      className="mt-4 flex w-full items-center gap-3 rounded-2xl border p-4 text-left transition hover:opacity-95"
      style={{
        backgroundColor: theme.accentBackground,
        borderColor: theme.accentText,
      }}
    >
      <div className="flex h-10 w-10 items-center justify-center rounded-full border border-black/10 bg-white">
        <Icon className="h-5 w-5" style={{ color: theme.accentText }} />
      </div>

      <div className="min-w-0 flex-1">
        <div
          className="text-xs font-semibold"
          style={{ color: theme.accentText }}
        >
          Next due
        </div>
        <div className="mt-1 text-base font-bold text-stone-900">
          {event ? event.title : "No upcoming due item"}
        </div>
        <div className="mt-1 text-xs text-stone-600">
          {event?.subtitleLabel || "Keep the timeline updated for better reminders."}
        </div>
      </div>

      <ChevronRight className="h-4 w-4" style={{ color: theme.accentText }} />
    </button>
  );
}

function TimelineEvent({ event, isLast, onPress }) {
  const theme = getRecordTheme(event.recordType);
  const Icon = theme.icon;

  return (
    <button type="button" onClick={() => onPress(event)} className="flex w-full gap-3 text-left">
      <div className="flex w-10 flex-col items-center">
        <div
          className="flex h-10 w-10 items-center justify-center rounded-full border"
          style={{
            backgroundColor: theme.accentBackground,
            borderColor: theme.accentBackground,
          }}
        >
          <Icon className="h-4 w-4" style={{ color: theme.accentText }} />
        </div>
        {!isLast ? <div className="mt-2 h-full min-h-12 w-px bg-black/10" /> : null}
      </div>

      <div
        className="mb-4 flex-1 rounded-2xl border p-4"
        style={{
          backgroundColor: event.isActive ? ACTIVE_BACKGROUND : SURFACE_COLOR,
          borderColor: event.isActive ? ACTIVE_BORDER : BORDER_COLOR,
        }}
      >
        <div className="mb-3 flex flex-wrap gap-2">
          <span
            className="rounded-full px-3 py-1 text-xs font-semibold"
            style={{
              backgroundColor: theme.accentBackground,
              color: theme.accentText,
            }}
          >
            {event.typeLabel}
          </span>

          {event.isActive ? (
            <span
              className="rounded-full border px-3 py-1 text-xs font-semibold"
              style={{
                backgroundColor: ACTIVE_BACKGROUND,
                borderColor: ACTIVE_BORDER,
                color: ACTIVE_TEXT,
              }}
            >
              Pending
            </span>
          ) : null}
        </div>

        <div className="text-base font-bold text-stone-900">{event.title}</div>
        <div className="mt-1 text-xs text-stone-600">{event.dateLabel}</div>
        {event.metaLabel ? (
          <div className="mt-1 text-xs text-stone-600">{event.metaLabel}</div>
        ) : null}
        {event.subtitleLabel ? (
          <div className="mt-1 text-xs text-stone-600">{event.subtitleLabel}</div>
        ) : null}

        <div className="mt-2 text-xs text-stone-500">Tap to view details</div>
      </div>
    </button>
  );
}

function EventDetailModal({
  event,
  open,
  onClose,
  onOpenVaccination,
  onPreviewPrescription,
  previewingPrescription,
  showPrescriptionActions,
  showVaccinationAction,
}) {
  if (!open || !event) return null;

  const theme = getRecordTheme(event.recordType);
  const Icon = theme.icon;

  return (
    <div className="fixed inset-0 z-50 flex items-end bg-black/35 md:items-center md:justify-center">
      <div
        className="absolute inset-0"
        onClick={onClose}
        aria-hidden="true"
      />
      <div className="relative z-10 w-full rounded-t-3xl bg-white md:max-w-2xl md:rounded-3xl">
        <div className="mx-auto mt-3 h-1.5 w-10 rounded-full bg-black/10 md:hidden" />

        <div className="flex items-start gap-3 border-b border-black/10 px-4 py-4">
          <div
            className="flex h-10 w-10 items-center justify-center rounded-full"
            style={{ backgroundColor: theme.accentBackground }}
          >
            <Icon className="h-5 w-5" style={{ color: theme.accentText }} />
          </div>

          <div className="min-w-0 flex-1">
            <div className="text-lg font-bold text-stone-900">{event.title}</div>
            <div className="mt-1 text-xs text-stone-500">
              {event.typeLabel} - {event.dateLabel}
            </div>
          </div>

          <button
            type="button"
            onClick={onClose}
            className="flex h-10 w-10 items-center justify-center rounded-full border border-black/10 bg-stone-100"
          >
            <X className="h-4 w-4 text-stone-600" />
          </button>
        </div>

        <div className="max-h-[75vh] overflow-y-auto px-4 py-4">
          {event.detailRows.map((row) => (
            <div key={`${event.id}-${row.label}`} className="mb-4">
              <div className="mb-1 text-xs font-semibold uppercase tracking-wide text-stone-500">
                {row.label}
              </div>
              <div className="text-sm leading-6 text-stone-900">{row.value}</div>
            </div>
          ))}

          <div className="my-4 h-px bg-black/10" />

          <div className="mb-4">
            <div className="mb-1 text-xs font-semibold uppercase tracking-wide text-stone-500">
              What happened
            </div>
            <div className="rounded-2xl border border-black/10 bg-stone-100 p-3 text-sm leading-6 text-stone-700">
              {event.note}
            </div>
          </div>

          {showPrescriptionActions ? (
            <div className="mt-4 flex flex-wrap gap-3">
              <button
                type="button"
                onClick={onPreviewPrescription}
                disabled={previewingPrescription}
                className="inline-flex items-center gap-2 rounded-xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white disabled:opacity-60"
              >
                {previewingPrescription ? (
                  <Spinner className="h-4 w-4" />
                ) : (
                  <Eye className="h-4 w-4" />
                )}
                {previewingPrescription ? "Opening..." : "Preview"}
              </button>
            </div>
          ) : null}

          {showVaccinationAction ? (
            <div className="mt-3 flex flex-wrap gap-3">
              <button
                type="button"
                onClick={onOpenVaccination}
                className="inline-flex items-center gap-2 rounded-xl border border-black/10 bg-white px-4 py-3 text-sm font-semibold text-stone-900"
              >
                <ShieldCheck className="h-4 w-4" />
                Open Vaccinations
              </button>
            </div>
          ) : null}

          <button
            type="button"
            onClick={onClose}
            className="mt-5 w-full rounded-xl border border-black/10 bg-white px-4 py-3 text-sm font-semibold text-stone-900 transition hover:bg-stone-50"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  );
}

export default function PetLifelinePage() {
  const navigate = useNavigate();
  const params = useParams();
  const petId = params?.petId || DEFAULT_PET_ID;
  const authToken = localStorage.getItem("auth_token") || "";

  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState("");
  const [lifelineData, setLifelineData] = useState(null);
  const [selectedEvent, setSelectedEvent] = useState(null);
  const [activePdfAction, setActivePdfAction] = useState(null);
  const [previewingPrescriptionId, setPreviewingPrescriptionId] = useState(null);

  const fetchTimeline = useCallback(async (signal) => {
    try {
      setIsLoading(true);
      setError("");

      const response = await fetch(
        `${LIFELINE_ENDPOINT}?pet_id=${encodeURIComponent(String(petId))}`,
        {
          signal,
          headers: {
            Accept: "application/json",
            ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
          },
        }
      );

      const payload = await response.json();

      if (!response.ok || !payload?.success) {
        throw new Error(payload?.message || "Unable to fetch timeline.");
      }

      setLifelineData(payload);
    } catch (fetchError) {
      if (signal?.aborted) return;
      setError(
        fetchError instanceof Error
          ? fetchError.message
          : "Something went wrong while loading the lifeline."
      );
    } finally {
      if (!signal?.aborted) {
        setIsLoading(false);
      }
    }
  }, [petId, authToken]);

  useEffect(() => {
    const controller = new AbortController();
    void fetchTimeline(controller.signal);
    return () => controller.abort();
  }, [fetchTimeline]);

  const pet = lifelineData?.data?.pet || null;
  const events = useMemo(() => buildTimelineEvents(lifelineData), [lifelineData]);
  const groupedEvents = useMemo(() => groupByYear(events), [events]);
  const recordCount = Number(lifelineData?.counts?.timeline) || events.length;
  const activeEvent = useMemo(
    () => events.find((event) => event.isActive) || null,
    [events]
  );

  const nextDueEvent = useMemo(() => {
    const dueCandidates = events
      .filter((event) => Boolean(event.nextDueAt))
      .sort(
        (left, right) => getTimestamp(left.nextDueAt) - getTimestamp(right.nextDueAt)
      );

    const now = Date.now();

    return (
      dueCandidates.find((event) => getTimestamp(event.nextDueAt) >= now) ||
      dueCandidates[0] ||
      null
    );
  }, [events]);

  const screenTitle = useMemo(() => {
    const petName = typeof pet?.name === "string" ? pet.name.trim() : "";
    return petName ? `${petName}'s Health Line` : "Pet Health line";
  }, [pet?.name]);

  const petDisplayName = useMemo(() => {
    const petName = typeof pet?.name === "string" ? pet.name.trim() : "";
    return petName || "Your pet";
  }, [pet?.name]);

  const selectedPrescriptionId = useMemo(
    () => resolveEventPrescriptionId(selectedEvent),
    [selectedEvent]
  );

  const downloadBlob = async (url, fileName) => {
    const response = await fetch(url, {
      headers: {
        Accept: "application/pdf",
        ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
      },
    });

    if (!response.ok) {
      throw new Error(`Server returned ${response.status}`);
    }

    const blob = await response.blob();
    const blobUrl = window.URL.createObjectURL(blob);

    return {
      blob,
      blobUrl,
      fileName,
    };
  };

  const handleDownloadPdf = useCallback(async () => {
    if (activePdfAction) return;

    setActivePdfAction("download");
    try {
      const fileName = sanitizeFileName(
        `${petDisplayName.replace(/\s+/g, "_")}_lifeline.pdf`
      );
      const url = `${LIFELINE_ENDPOINT}/pdf?pet_id=${encodeURIComponent(
        String(petId)
      )}&download=1`;

      const { blobUrl } = await downloadBlob(url, fileName);

      const anchor = document.createElement("a");
      anchor.href = blobUrl;
      anchor.download = fileName;
      document.body.appendChild(anchor);
      anchor.click();
      anchor.remove();

      setTimeout(() => window.URL.revokeObjectURL(blobUrl), 5000);
    } catch (downloadError) {
      alert(downloadError?.message || "Could not download the PDF. Please try again.");
    } finally {
      setActivePdfAction(null);
    }
  }, [activePdfAction, petDisplayName, petId, authToken]);

  const handleSharePdf = useCallback(async () => {
    if (activePdfAction) return;

    setActivePdfAction("share");
    try {
      const fileName = sanitizeFileName(
        `${petDisplayName.replace(/\s+/g, "_")}_lifeline.pdf`
      );
      const url = `${LIFELINE_ENDPOINT}/pdf?pet_id=${encodeURIComponent(
        String(petId)
      )}&download=1`;

      const { blob, blobUrl } = await downloadBlob(url, fileName);

      if (navigator.share && navigator.canShare) {
        const file = new File([blob], fileName, { type: "application/pdf" });
        const canShareFile = navigator.canShare({ files: [file] });

        if (canShareFile) {
          await navigator.share({
            title: `${petDisplayName} Lifeline PDF`,
            files: [file],
          });
          window.URL.revokeObjectURL(blobUrl);
          return;
        }
      }

      window.open(blobUrl, "_blank", "noopener,noreferrer");
      setTimeout(() => window.URL.revokeObjectURL(blobUrl), 5000);
    } catch (shareError) {
      alert(shareError?.message || "Could not share the PDF. Please try again.");
    } finally {
      setActivePdfAction(null);
    }
  }, [activePdfAction, petDisplayName, petId, authToken]);

  const handlePreviewPrescription = useCallback(async () => {
    if (!selectedPrescriptionId || previewingPrescriptionId) return;

    setPreviewingPrescriptionId(selectedPrescriptionId);

    try {
      const fileName = sanitizeFileName(
        `prescription_${selectedPrescriptionId}_${Date.now()}.pdf`
      );
      const url = `${PRESCRIPTION_PDF_ENDPOINT}?prescription_id=${encodeURIComponent(
        selectedPrescriptionId
      )}`;

      const { blobUrl } = await downloadBlob(url, fileName);
      window.open(blobUrl, "_blank", "noopener,noreferrer");
      setTimeout(() => window.URL.revokeObjectURL(blobUrl), 10000);
    } catch (previewError) {
      alert(
        previewError?.message ||
          "Could not open the prescription preview. Please try again."
      );
    } finally {
      setPreviewingPrescriptionId((current) =>
        current === selectedPrescriptionId ? null : current
      );
    }
  }, [selectedPrescriptionId, previewingPrescriptionId, authToken]);

  const handleOpenVaccination = useCallback(() => {
    navigate(`/vaccination-tracker/${petId}`);
  }, [navigate, petId]);

  const handleRetry = useCallback(() => {
    const controller = new AbortController();
    void fetchTimeline(controller.signal);
  }, [fetchTimeline]);

  return (
    <div
      className="min-h-screen"
      style={{ backgroundColor: SCREEN_BACKGROUND, color: TEXT_PRIMARY }}
    >
      <div className="mx-auto max-w-4xl px-4 pb-8 pt-4">
        <header className="mb-4 flex items-center rounded-2xl border border-black/10 bg-white px-4 py-3">
          <button
            type="button"
            onClick={() => navigate(-1)}
            className="flex h-10 w-10 items-center justify-center rounded-full"
          >
            <ChevronLeft className="h-5 w-5 text-stone-900" />
          </button>

          <h1 className="flex-1 text-center text-lg font-bold">{screenTitle}</h1>
          <div className="h-10 w-10" />
        </header>

        {isLoading ? (
          <LoadingSkeleton />
        ) : (
          <>
            <PetCard pet={pet} recordCount={recordCount} />

            <div className="mt-4 grid gap-3 sm:grid-cols-2">
              <button
                type="button"
                onClick={handleDownloadPdf}
                disabled={Boolean(activePdfAction)}
                className="inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl border px-4 py-3 text-sm font-semibold text-white disabled:opacity-60"
                style={{
                  backgroundColor: TEXT_PRIMARY,
                  borderColor: TEXT_PRIMARY,
                }}
              >
                {activePdfAction === "download" ? (
                  <Spinner />
                ) : (
                  <Download className="h-4 w-4" />
                )}
                {activePdfAction === "download" ? "Downloading..." : "Download PDF"}
              </button>

              <button
                type="button"
                onClick={handleSharePdf}
                disabled={Boolean(activePdfAction)}
                className="inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl border px-4 py-3 text-sm font-semibold disabled:opacity-60"
                style={{
                  backgroundColor: SURFACE_COLOR,
                  borderColor: BORDER_COLOR,
                  color: TEXT_PRIMARY,
                }}
              >
                {activePdfAction === "share" ? (
                  <Spinner className="h-4 w-4 text-stone-900" />
                ) : (
                  <Share2 className="h-4 w-4" />
                )}
                {activePdfAction === "share" ? "Sharing..." : "Share PDF"}
              </button>
            </div>

            <NextDueBanner
              event={nextDueEvent}
              onPress={() => {
                if (nextDueEvent) {
                  setSelectedEvent(nextDueEvent);
                } else {
                  alert("Keep the timeline updated to receive better reminders.");
                }
              }}
            />

            {activeEvent ? (
              <div
                className="mt-4 flex items-center gap-3 rounded-2xl border p-4"
                style={{
                  backgroundColor: ACTIVE_BACKGROUND,
                  borderColor: ACTIVE_BORDER,
                }}
              >
                <div
                  className="h-2 w-2 rounded-full"
                  style={{ backgroundColor: ACTIVE_TEXT }}
                />
                <div className="flex-1">
                  <div
                    className="text-xs font-semibold"
                    style={{ color: ACTIVE_TEXT }}
                  >
                    Ongoing item
                  </div>
                  <div className="mt-1 text-sm font-bold text-stone-900">
                    {activeEvent.title}
                  </div>
                </div>
              </div>
            ) : null}

            {error ? (
              <EmptyState
                title="Unable to load the lifeline"
                subtitle={error}
                actionLabel="Try again"
                onAction={handleRetry}
              />
            ) : events.length === 0 ? (
              <EmptyState
                title="No records yet"
                subtitle="Once vaccinations, deworming, or consultations are added, they will appear here."
                actionLabel="Refresh"
                onAction={handleRetry}
              />
            ) : (
              <>
                <div
                  className="mt-5 text-xs font-semibold uppercase tracking-wide"
                  style={{ color: TEXT_SUBTLE }}
                >
                  Full medical history
                </div>

                <div className="mt-3">
                  {groupedEvents.map((group) => (
                    <div key={group.year} className="mb-5">
                      <div className="mb-3 inline-flex rounded-full border border-black/10 bg-white px-3 py-1 text-xs font-bold text-stone-900">
                        {group.year}
                      </div>

                      {group.events.map((event, index) => (
                        <TimelineEvent
                          key={event.id}
                          event={event}
                          isLast={index === group.events.length - 1}
                          onPress={setSelectedEvent}
                        />
                      ))}
                    </div>
                  ))}
                </div>
              </>
            )}
          </>
        )}
      </div>

      <EventDetailModal
        event={selectedEvent}
        open={Boolean(selectedEvent)}
        onClose={() => setSelectedEvent(null)}
        onOpenVaccination={handleOpenVaccination}
        onPreviewPrescription={handlePreviewPrescription}
        previewingPrescription={
          Boolean(selectedPrescriptionId) &&
          previewingPrescriptionId === selectedPrescriptionId
        }
        showPrescriptionActions={Boolean(selectedPrescriptionId)}
        showVaccinationAction={selectedEvent?.recordType === "vaccination"}
      />
    </div>
  );
}