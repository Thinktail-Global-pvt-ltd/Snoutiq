import React, { useEffect, useRef, useState, useMemo } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import {
  FileText,
  X,
  ChevronDown,
  Search,
  Stethoscope,
  Pill,
  Calendar,
  Upload,
  AlertCircle,
  CheckCircle2,
} from "lucide-react";
import { useNewDoctorAuth } from "./NewDoctorAuth";
import {
  clearDoctorPendingPrescription,
  setDoctorPendingPrescription,
} from "./doctorPendingPrescriptionService";
import { useDoctorPendingPrescription } from "./useDoctorPendingPrescription";

// ─── Constants ────────────────────────────────────────────────────────────────

const API_BASE_URL = "https://snoutiq.com/backend"; // TODO: move to env variable

const INPUT_BASE_CLASS =
  "w-full px-3.5 py-2.5 rounded-lg border border-gray-200 bg-white text-sm text-gray-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[#0B4D67]/20 focus:border-[#0B4D67] focus:bg-white placeholder:text-gray-400 hover:border-gray-300";
const PRESCRIPTION_TEXTAREA_MAX_LENGTH = 500;

const PRESCRIPTION_PROGNOSIS_OPTIONS = [
  { value: "good", label: "Good" },
  { value: "fair", label: "Fair" },
  { value: "poor", label: "Poor" },
  { value: "grave", label: "Grave" },
];

const PRESCRIPTION_CONSULT_MODE_OPTIONS = [
  { value: "video", label: "Video" },
  { value: "in_clinic", label: "In-Clinic" },
];

const PRESCRIPTION_FREQUENCY_OPTIONS = [
  "OD (Once daily)",
  "BD (Twice daily)",
  "TDS (3 times)",
  "QID (4 times)",
];

const PRESCRIPTION_TIMING_OPTIONS = [
  "Morning",
  "Afternoon",
  "Evening",
  "Night",
];

const PRESCRIPTION_FOOD_RELATION_OPTIONS = [
  "Before food (AC)",
  "After food (PC)",
  "With food",
  "Empty stomach",
];

const PRESCRIPTION_MUCOUS_MEMBRANE_OPTIONS = [
  { value: "normal_pink", label: "Normal pink" },
  { value: "cherry_red", label: "Cherry red" },
  { value: "yellow", label: "Yellow" },
  { value: "white", label: "White" },
];

const PRESCRIPTION_DEHYDRATION_LEVEL_OPTIONS = [
  { value: "no", label: "No dehydration" },
  { value: "mild", label: "Mild" },
  { value: "moderate", label: "Moderate" },
  { value: "severe", label: "Severe" },
];

const PRESCRIPTION_ABDOMINAL_PAIN_OPTIONS = [
  { value: "painful", label: "Painful" },
  { value: "no_pain", label: "No pain" },
];

const PRESCRIPTION_AUSCULTATION_OPTIONS = [
  { value: "normal", label: "Normal" },
  { value: "abnormal", label: "Abnormal" },
];

const FALLBACK_AFFECTED_SYSTEMS = [
  { id: 1, code: "integumentary", name: "Integumentary system (skin & nails)" },
  { id: 2, code: "gastrointestinal", name: "Gastrointestinal system" },
  { id: 3, code: "hepatobiliary", name: "Hepatobiliary system" },
  { id: 4, code: "urinary", name: "Urinary system" },
  { id: 5, code: "genital", name: "Genital system" },
  { id: 6, code: "nervous", name: "Nervous system" },
  { id: 7, code: "musculoskeletal", name: "Musculoskeletal system" },
  { id: 8, code: "endocrine", name: "Endocrine system" },
  { id: 9, code: "muscular", name: "Muscular system" },
  { id: 10, code: "respiratory", name: "Respiratory system" },
  { id: 11, code: "cardiovascular", name: "Cardiovascular system" },
  { id: 12, code: "visual", name: "Visual system (Eyes)" },
  {
    id: 13,
    code: "auditory_vestibular",
    name: "Auditory & vestibular system (Ear)",
  },
  { id: 14, code: "dental", name: "Dental system" },
];

// ─── Helpers ──────────────────────────────────────────────────────────────────

const normalizeOptionalText = (value) => {
  if (value == null) return "";
  if (typeof value === "number" && Number.isFinite(value)) return String(value);
  if (typeof value !== "string") return "";
  return value.replace(/\s+/g, " ").trim();
};

const getTransactionReportedSymptoms = (transaction) => {
  const candidates = [
    transaction?.pet?.reported_symptom,
    transaction?.pet?.disease,
    transaction?.reported_symptom,
    transaction?.metadata?.reported_symptom,
    transaction?.metadata?.symptom,
    transaction?.metadata?.notes?.reported_symptom,
  ];
  return candidates.map(normalizeOptionalText).find(Boolean) || "";
};

const getTransactionWeightInput = (transaction) => {
  const raw = normalizeOptionalText(
    transaction?.pet?.weight_kg ??
      transaction?.pet?.weight ??
      transaction?.weight,
  );
  if (!raw) return "";
  return raw.replace(/\s*kg$/i, "").trim();
};

const isInClinicConsultationCategory = (value) =>
  String(value || "").trim() === "General Consultation";

const normalizeVisitCategory = (value) => {
  switch (String(value || "").trim()) {
    case "Online Consultation":
      return "online_consultation";
    case "General Consultation":
      return "general_consultation";
    case "Follow-up":
      return "followup";
    case "Emergency":
      return "emergency";
    default:
      return String(value || "").trim();
  }
};

const getOptionLabel = (options, value, fallback = "Not specified") => {
  const match = options.find((o) => o.value === value);
  return match?.label || fallback;
};

const formatPetText = (value) => {
  if (!value) return "";
  return String(value)
    .replace(/_+/g, " ")
    .replace(/\s+/g, " ")
    .trim()
    .replace(/\b\w/g, (c) => c.toUpperCase());
};

const formatYesNoUnknown = (value) => {
  if (value == null || value === "") return "NA";
  if (typeof value === "boolean") return value ? "Yes" : "No";
  const n = String(value).trim().toLowerCase();
  if (["1", "true", "yes", "y"].includes(n)) return "Yes";
  if (["0", "false", "no", "n"].includes(n)) return "No";
  return "NA";
};

const formatWeightLabel = (value) => {
  if (value == null || value === "") return "Weight NA";
  const num = Number(value);
  if (Number.isFinite(num) && num > 0) {
    return `${Number.isInteger(num) ? String(num) : num.toFixed(2)} kg`;
  }
  const raw = String(value).trim();
  return raw ? (/kg/i.test(raw) ? raw : `${raw} kg`) : "Weight NA";
};

const getPetAgeLabel = (pet) => {
  const direct = Number(pet?.age ?? pet?.age_years);
  if (Number.isFinite(direct) && direct > 0) {
    const y = Math.floor(direct);
    return `${y} year${y > 1 ? "s" : ""}`;
  }
  const dobRaw = pet?.pet_dob || pet?.dob || pet?.date_of_birth;
  if (!dobRaw) return "Age NA";
  const dob = new Date(dobRaw);
  if (Number.isNaN(dob.getTime())) return "Age NA";
  const now = new Date();
  let years = now.getFullYear() - dob.getFullYear();
  let months = now.getMonth() - dob.getMonth();
  if (now.getDate() < dob.getDate()) months--;
  if (months < 0) {
    years--;
    months += 12;
  }
  if (years > 0) return `${years} year${years > 1 ? "s" : ""}`;
  if (months > 0) return `${months} month${months > 1 ? "s" : ""}`;
  const days = Math.max(0, Math.floor((now - dob) / 86400000));
  return days > 0 ? `${days} day${days > 1 ? "s" : ""}` : "Age NA";
};

const resolvePetName = (transaction) => {
  const name = transaction?.pet?.name?.trim();
  if (name) {
    const match = name.match(/^(.*)'s\s+pet$/i);
    if (match?.[1]) return formatPetText(match[1].trim()) || name;
    return name;
  }
  const metaName =
    transaction?.metadata?.notes?.pet_name ||
    transaction?.metadata?.pet_name ||
    "";
  return metaName
    ? String(metaName)
    : formatPetText(transaction?.pet?.breed || transaction?.pet?.pet_type) ||
        "Not available";
};

const getRemainingCharacters = (
  value = "",
  max = PRESCRIPTION_TEXTAREA_MAX_LENGTH,
) => Math.max(max - String(value || "").length, 0);

const autoResizeTextarea = (el) => {
  if (!el) return;
  el.style.height = "auto";
  el.style.height = `${el.scrollHeight}px`;
};

const buildValidationMessage = (data, fallback = "Request failed.") => {
  const primary = data?.message || data?.error || fallback;
  const rawErrors = data?.errors;
  if (!rawErrors || typeof rawErrors !== "object") return primary;
  const details = Object.values(rawErrors)
    .flat()
    .map((i) => String(i || "").trim())
    .filter(Boolean);
  if (!details.length) return primary;
  const deduped = Array.from(new Set(details.filter((i) => i !== primary)));
  return deduped.length ? [primary, ...deduped].join("\n") : primary;
};

const blockNumberInput = (e) => {
  if (["e", "E", "+", "-"].includes(e.key)) e.preventDefault();
};
const handleNumberWheel = (e) => e.currentTarget.blur();

const statusClass = (status) => {
  const k = (status || "").toLowerCase();
  if (k === "pending") return "bg-amber-50 text-amber-700 border-amber-200";
  if (["paid", "success", "captured", "completed"].includes(k))
    return "bg-emerald-50 text-emerald-700 border-emerald-200";
  if (["failed", "cancelled", "canceled", "refunded"].includes(k))
    return "bg-rose-50 text-rose-700 border-rose-200";
  return "bg-gray-100 text-gray-600 border-gray-200";
};

const statusLabel = (status) => {
  if (!status) return "unknown";
  const k = status.toLowerCase();
  if (k === "captured") return "paid";
  return status.replace(/_/g, " ");
};

const formatAmount = (value) => {
  const num = Number(value);
  return Number.isFinite(num) ? `₹${num.toLocaleString("en-IN")}` : "₹0";
};

const formatDate = (value) => {
  if (!value) return "N/A";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleString("en-IN", {
    day: "2-digit",
    month: "short",
    hour: "2-digit",
    minute: "2-digit",
  });
};

const formatPrescriptionDate = (value) => {
  if (!value) return "";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleDateString("en-IN", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
};

const normalizeId = (value) => {
  const num = Number(value);
  if (!Number.isFinite(num) || num <= 0) return "";
  return String(num);
};

const createMockPendingTransaction = ({
  pendingPrescription,
  doctorId,
  clinicId,
  doctorLicenseLabel,
  clinicNameLabel,
}) => {
  const patientData = pendingPrescription?.patientData || {};

  return {
    id: pendingPrescription?.consultationId || "",
    status: "paid",
    created_at: new Date().toISOString(),
    reference: pendingPrescription?.consultationId
      ? `Consultation #${pendingPrescription.consultationId}`
      : "Pending prescription",
    user: {
      id: pendingPrescription?.userId || "",
      name: patientData.parentName || "",
      phone: patientData.phone || "",
      city: "",
    },
    pet: {
      id: pendingPrescription?.petId || "",
      name: patientData.petName || "",
      pet_type: patientData.petType || "",
      breed: patientData.breed || "",
      pet_gender: patientData.gender || "",
      age: patientData.age || "",
      weight_kg: patientData.weight || "",
    },
    doctor: {
      id: doctorId || "",
      doctor_license: doctorLicenseLabel,
    },
    clinic: {
      id: clinicId || "",
      name: clinicNameLabel,
      city: "",
    },
    metadata: {
      user_id: pendingPrescription?.userId || "",
      pet_id: pendingPrescription?.petId || "",
      notes: {
        user_id: pendingPrescription?.userId || "",
        pet_id: pendingPrescription?.petId || "",
      },
    },
  };
};

const markPendingPrescriptionAsSubmitted = (doctorId, pendingPrescription) => {
  if (!doctorId || !pendingPrescription?.hasPending) {
    return;
  }

  setDoctorPendingPrescription(doctorId, {
    ...pendingPrescription,
    paymentStatus: "paid",
    prescriptionRequired: true,
    prescriptionStatus: "submitted",
    lockUntilSubmit: false,
    hasPending: true,
  });
};

// ─── Main Component ───────────────────────────────────────────────────────────

/**
 * Props:
 *  - transaction  {object}  – the active consultation transaction object
 *  - auth         {object}  – { token, doctor_id, clinic_id, doctor: { doctor_name, doctor_email, doctor_license, clinic_name } }
 *  - onClose      {fn}      – called when user closes / cancels
 *  - onSuccess    {fn}      – called after successful prescription submit
 */
const NewDoctorDigitalPrescription = ({
  transaction: providedTransaction = null,
  onClose = () => {},
  onSuccess = () => {},
}) => {
  const navigate = useNavigate();
  const location = useLocation();

  // ── Derived auth values ──
  const { auth } = useNewDoctorAuth();
  const authToken = auth?.token || auth?.access_token || "";
  const doctorId = normalizeId(
    auth?.doctor_id || auth?.doctor?.id || auth?.doctor?.doctor_id,
  );
  const clinicIdFromAuth = normalizeId(
    auth?.clinic_id ||
      auth?.doctor?.clinic_id ||
      auth?.doctor?.vet_registeration_id,
  );
  const doctorName =
    auth?.doctor?.doctor_name ||
    auth?.doctor_name ||
    auth?.doctor?.name ||
    "Doctor";
  const fallbackDoctorLicenseLabel =
    auth?.extras?.license_number ||
    auth?.doctor?.doctor_license ||
    "Not available";
  const fallbackClinicNameLabel =
    auth?.extras?.clinic_name || auth?.doctor?.clinic_name || "Not available";
  const assignedDoctorName =
    auth?.doctor?.doctor_name || doctorName || "Assigned Veterinarian";
  const { pendingPrescription, refresh } = useDoctorPendingPrescription({
    doctorId,
    enabled: true,
  });
  const isMockSubmitMode =
    !providedTransaction &&
    pendingPrescription.hasPending &&
    pendingPrescription.lockUntilSubmit;
  const activeTransaction = useMemo(() => {
    if (providedTransaction) return providedTransaction;
    if (!isMockSubmitMode) return null;

    return createMockPendingTransaction({
      pendingPrescription,
      doctorId,
      clinicId: clinicIdFromAuth,
      doctorLicenseLabel: fallbackDoctorLicenseLabel,
      clinicNameLabel: fallbackClinicNameLabel,
    });
  }, [
    clinicIdFromAuth,
    doctorId,
    fallbackClinicNameLabel,
    fallbackDoctorLicenseLabel,
    isMockSubmitMode,
    pendingPrescription,
    providedTransaction,
  ]);
  const doctorLicenseLabel =
    activeTransaction?.doctor?.doctor_license || fallbackDoctorLicenseLabel;
  const clinicNameLabel =
    activeTransaction?.clinic?.name || fallbackClinicNameLabel;

  // ── Resolve IDs from transaction ──
  const resolveIds = () => {
    const metadata = activeTransaction?.metadata || {};
    const notes = metadata?.notes || {};
    return {
      userId:
        activeTransaction?.user?.id ||
        metadata?.user_id ||
        notes?.user_id ||
        "",
      petId:
        activeTransaction?.pet?.id || metadata?.pet_id || notes?.pet_id || "",
      doctorId:
        doctorId ||
        normalizeId(
          activeTransaction?.doctor?.id ||
            metadata?.doctor_id ||
            notes?.doctor_id,
        ),
      clinicId:
        clinicIdFromAuth ||
        normalizeId(metadata?.clinic_id || notes?.clinic_id),
    };
  };

  const resolveChannelName = () =>
    normalizeOptionalText(
      activeTransaction?.channel_name ||
        activeTransaction?.metadata?.channel_name ||
        activeTransaction?.metadata?.call_id ||
        activeTransaction?.metadata?.notes?.call_session_id ||
        "",
    );

  // ── Derived pet labels ──
  const petAgeLabel = getPetAgeLabel(activeTransaction?.pet);
  const petWeightLabel = formatWeightLabel(
    activeTransaction?.pet?.weight_kg ??
      activeTransaction?.pet?.weight ??
      activeTransaction?.weight,
  );
  const vaccinationLabel = formatYesNoUnknown(
    activeTransaction?.pet?.is_vaccinated ??
      activeTransaction?.metadata?.is_vaccinated ??
      activeTransaction?.metadata?.notes?.is_vaccinated,
  );
  const neuterLabel = formatYesNoUnknown(
    activeTransaction?.pet?.is_neutered ??
      activeTransaction?.metadata?.is_neutered ??
      activeTransaction?.metadata?.notes?.is_neutered,
  );
  const dewormingLabel = formatYesNoUnknown(
    activeTransaction?.pet?.deworming_yes_no ??
      activeTransaction?.metadata?.deworming_yes_no ??
      activeTransaction?.metadata?.notes?.deworming_yes_no,
  );
  const petTypeLabel =
    formatPetText(activeTransaction?.pet?.pet_type) || "Not available";
  const petGenderLabel =
    formatPetText(
      activeTransaction?.pet?.pet_gender || activeTransaction?.gender,
    ) || "Not available";
  const consultationLocationLabel =
    activeTransaction?.user?.city ||
    activeTransaction?.clinic?.city ||
    "Not available";

  // ── Form state ──
  const createForm = () => ({
    visitCategory: "Online Consultation",
    consultationCategory: "Online Consultation",
    consultMode: "video",
    medicalStatus: "",
    caseSeverity: "general",
    prognosis: "fair",
    notes: getTransactionReportedSymptoms(activeTransaction),
    historySnapshot: getTransactionReportedSymptoms(activeTransaction),
    doctorTreatment: "",
    diagnosis: "",
    diagnosisStatus: "",
    treatmentPlan: "",
    homeCare: "",
    followUpRequired: "yes",
    followUpDate: "",
    followUpMode: "online",
    followUpNotes: "",
    systemAffectedId: "",
    temperature: "",
    weight: getTransactionWeightInput(activeTransaction),
    mucousMembrane: "",
    dehydrationLevel: "",
    abdominalPainReaction: "",
    auscultation: "",
    physicalExamOther: "",
    medications: [
      {
        name: "",
        dosage: "",
        frequency: "",
        duration: "",
        timing: [],
        foodRelation: "",
        instructions: "",
      },
    ],
    recordFile: null,
  });

  const [prescriptionForm, setPrescriptionForm] = useState(createForm);
  const [prescriptionView, setPrescriptionView] = useState("edit"); // "edit" | "preview"
  const [activeMedicationIndex, setActiveMedicationIndex] = useState(0);
  const [prescriptionSubmitting, setPrescriptionSubmitting] = useState(false);
  const [prescriptionError, setPrescriptionError] = useState("");
  const [showSuccessModal, setShowSuccessModal] = useState(false);

  const finalizePrescriptionSuccess = async () => {
    markPendingPrescriptionAsSubmitted(doctorId, pendingPrescription);
    clearDoctorPendingPrescription(doctorId);
    await refresh();
    setShowSuccessModal(true);
  };

  // affected systems
  const [affectedSystems, setAffectedSystems] = useState(
    FALLBACK_AFFECTED_SYSTEMS,
  );
  const [affectedSystemsLoading, setAffectedSystemsLoading] = useState(false);
  const [affectedSystemQuery, setAffectedSystemQuery] = useState("");
  const [isAffectedSystemMenuOpen, setIsAffectedSystemMenuOpen] =
    useState(false);
  const affectedSystemMenuRef = useRef(null);

  // textarea refs
  const historyTextareaRef = useRef(null);
  const diagnosisTextareaRef = useRef(null);
  const adviceTextareaRef = useRef(null);

  // ── Load affected systems ──
  useEffect(() => {
    let active = true;
    const controller = new AbortController();
    setAffectedSystemsLoading(true);
    fetch(`${API_BASE_URL}/api/affected-systems`, {
      signal: controller.signal,
      headers: { Accept: "application/json" },
    })
      .then((r) => r.json())
      .then((data) => {
        if (!active) return;
        const list = Array.isArray(data?.data)
          ? data.data
              .map((i) => ({
                id: i?.id,
                code: normalizeOptionalText(i?.code),
                name: normalizeOptionalText(i?.name),
              }))
              .filter((i) => i.id && i.name)
          : [];
        if (list.length) setAffectedSystems(list);
      })
      .catch(() => {})
      .finally(() => {
        if (active) setAffectedSystemsLoading(false);
      });
    return () => {
      active = false;
      controller.abort();
    };
  }, []);

  // ── Close affected system dropdown on outside click ──
  useEffect(() => {
    const handler = (e) => {
      if (!affectedSystemMenuRef.current?.contains(e.target))
        setIsAffectedSystemMenuOpen(false);
    };
    document.addEventListener("mousedown", handler);
    return () => document.removeEventListener("mousedown", handler);
  }, []);

  useEffect(() => {
    if (!isAffectedSystemMenuOpen) setAffectedSystemQuery("");
  }, [isAffectedSystemMenuOpen]);

  // ── Auto-resize textareas ──
  useEffect(() => {
    autoResizeTextarea(historyTextareaRef.current);
  }, [prescriptionForm.historySnapshot]);
  useEffect(() => {
    autoResizeTextarea(diagnosisTextareaRef.current);
  }, [prescriptionForm.diagnosis]);
  useEffect(() => {
    autoResizeTextarea(adviceTextareaRef.current);
  }, [prescriptionForm.homeCare]);

  // ── Derived ──
  const isGeneralConsultation =
    isInClinicConsultationCategory(prescriptionForm.consultationCategory) ||
    prescriptionForm.consultMode === "in_clinic";

  const selectedAffectedSystem =
    affectedSystems.find(
      (s) => String(s.id) === String(prescriptionForm.systemAffectedId),
    ) || null;

  const filteredAffectedSystems = useMemo(() => {
    const q = normalizeOptionalText(affectedSystemQuery).toLowerCase();
    if (!q) return affectedSystems;
    return affectedSystems.filter((s) =>
      [s.name, s.code].some((v) =>
        String(v || "")
          .toLowerCase()
          .includes(q),
      ),
    );
  }, [affectedSystemQuery, affectedSystems]);

  const followUpRequired = prescriptionForm.followUpRequired === "yes";
  const followUpDisplayLabel = !followUpRequired
    ? "Not required"
    : prescriptionForm.followUpDate
      ? formatPrescriptionDate(prescriptionForm.followUpDate)
      : "As needed";
  const followUpModeLabel =
    prescriptionForm.followUpMode === "in_clinic" ? "In-Clinic" : "Online";
  const prognosisLabel = getOptionLabel(
    PRESCRIPTION_PROGNOSIS_OPTIONS,
    prescriptionForm.prognosis,
  );
  const consultModeLabel = getOptionLabel(
    PRESCRIPTION_CONSULT_MODE_OPTIONS,
    prescriptionForm.consultMode,
    "Video",
  );
  const mucousMembraneLabel = getOptionLabel(
    PRESCRIPTION_MUCOUS_MEMBRANE_OPTIONS,
    prescriptionForm.mucousMembrane,
  );
  const dehydrationLevelLabel = getOptionLabel(
    PRESCRIPTION_DEHYDRATION_LEVEL_OPTIONS,
    prescriptionForm.dehydrationLevel,
  );
  const abdominalPainLabel = getOptionLabel(
    PRESCRIPTION_ABDOMINAL_PAIN_OPTIONS,
    prescriptionForm.abdominalPainReaction,
  );
  const auscultationLabel = getOptionLabel(
    PRESCRIPTION_AUSCULTATION_OPTIONS,
    prescriptionForm.auscultation,
  );
  const historySnapshotLabel =
    prescriptionForm.historySnapshot ||
    prescriptionForm.notes ||
    "No history added.";

  // ── Form helpers ──
  const updatePrescriptionField =
    (key, maxLength = null) =>
    (e) => {
      const val =
        maxLength != null ? e.target.value.slice(0, maxLength) : e.target.value;
      setPrescriptionForm((prev) => ({ ...prev, [key]: val }));
      if (maxLength != null && e.target?.tagName === "TEXTAREA")
        autoResizeTextarea(e.target);
    };

  const toggleChronicMedicalStatus = (e) =>
    setPrescriptionForm((prev) => ({
      ...prev,
      medicalStatus: e.target.checked ? "Chronic" : "",
    }));

  const updateMedication = (index, key) => (e) =>
    setPrescriptionForm((prev) => ({
      ...prev,
      medications: prev.medications.map((m, i) =>
        i === index ? { ...m, [key]: e.target.value } : m,
      ),
    }));

  const setMedicationField = (index, key, value) =>
    setPrescriptionForm((prev) => ({
      ...prev,
      medications: prev.medications.map((m, i) =>
        i === index ? { ...m, [key]: value } : m,
      ),
    }));

  const toggleMedicationTiming = (index, timing) => {
    const med = prescriptionForm.medications[index];
    if (!med) return;
    const current = Array.isArray(med.timing) ? med.timing : [];
    setMedicationField(
      index,
      "timing",
      current.includes(timing)
        ? current.filter((v) => v !== timing)
        : [...current, timing],
    );
  };

  const addMedication = () => {
    const nextIndex = prescriptionForm.medications.length;
    setPrescriptionForm((prev) => ({
      ...prev,
      medications: [
        ...prev.medications,
        {
          name: "",
          dosage: "",
          frequency: "",
          duration: "",
          timing: [],
          foodRelation: "",
          instructions: "",
        },
      ],
    }));
    setActiveMedicationIndex(nextIndex);
  };

  const removeMedication = (index) => {
    setPrescriptionForm((prev) => ({
      ...prev,
      medications:
        prev.medications.length > 1
          ? prev.medications.filter((_, i) => i !== index)
          : prev.medications,
    }));
    setActiveMedicationIndex((prev) => {
      if (index === prev) return Math.max(0, index - 1);
      if (index < prev) return prev - 1;
      return prev;
    });
  };

  const closeMedicationEditor = (index) => {
    if (index === activeMedicationIndex) setActiveMedicationIndex(-1);
  };

  const handleRecordFile = (e) => {
    const file = e.target.files?.[0] || null;
    setPrescriptionForm((prev) => ({ ...prev, recordFile: file }));
  };

  // ── Submit ──
  const handlePrescriptionSubmit = async (e) => {
    e.preventDefault();
    if (prescriptionSubmitting) return;

    // Validation
    const missingFields = [];
    if (!prescriptionForm.consultationCategory)
      missingFields.push("Consultation category");
    if (!prescriptionForm.diagnosis.trim())
      missingFields.push("Clinical notes / tentative diagnosis");
    if (!prescriptionForm.prognosis) missingFields.push("Prognosis");
    if (!prescriptionForm.homeCare.trim())
      missingFields.push("Vet advice / home care tips");
    if (!prescriptionForm.systemAffectedId)
      missingFields.push("System affected");
    if (followUpRequired && !prescriptionForm.followUpDate)
      missingFields.push("Follow-up date");
    if (isGeneralConsultation) {
      if (!String(prescriptionForm.temperature || "").trim())
        missingFields.push("Temperature");
      if (!String(prescriptionForm.weight || "").trim())
        missingFields.push("Weight");
      if (!prescriptionForm.mucousMembrane)
        missingFields.push("Mucous membrane");
      if (!prescriptionForm.dehydrationLevel)
        missingFields.push("Dehydration level");
      if (!prescriptionForm.abdominalPainReaction)
        missingFields.push("Abdominal pain reaction");
      if (!prescriptionForm.auscultation) missingFields.push("Auscultation");
      if (!prescriptionForm.physicalExamOther.trim())
        missingFields.push("Physical exam notes");
    }
    if (missingFields.length) {
      setPrescriptionError(
        `Please complete these required fields:\n• ${missingFields.join("\n• ")}`,
      );
      return;
    }

    setPrescriptionSubmitting(true);
    setPrescriptionError("");

    const {
      userId,
      petId,
      doctorId: resolvedDoctorId,
      clinicId,
    } = resolveIds();
    if (!isMockSubmitMode && (!userId || !clinicId)) {
      setPrescriptionError(
        "Missing patient or clinic data. Please refresh and try again.",
      );
      setPrescriptionSubmitting(false);
      return;
    }

    const medsPayload = prescriptionForm.medications
      .map((med) => ({
        timing: Array.isArray(med.timing) ? med.timing : [],
        food_relation: med.foodRelation || "",
        name: med.name.trim(),
        dose: med.dosage.trim(),
        frequency: med.frequency.trim(),
        duration: med.duration.trim(),
        instructions: [
          (med.instructions || "").trim(),
          Array.isArray(med.timing) && med.timing.length
            ? `Timing: ${med.timing.join(", ")}`
            : "",
          med.foodRelation ? `Food: ${med.foodRelation}` : "",
        ]
          .filter(Boolean)
          .join(" | "),
      }))
      .filter(
        (m) =>
          m.name ||
          m.dose ||
          m.frequency ||
          m.duration ||
          m.instructions ||
          (m.timing && m.timing.length) ||
          m.food_relation,
      );

    const fd = new FormData();
    const historySnapshotValue = (
      prescriptionForm.historySnapshot || prescriptionForm.notes
    ).trim();
    const channelName = resolveChannelName();

    const append = (key, val) => {
      if (val == null || val === "") return;
      const v = typeof val === "string" ? val.trim() : String(val).trim();
      if (!v) return;
      fd.append(key, v);
    };

    fd.append("user_id", String(userId));
    fd.append("clinic_id", String(clinicId));
    if (resolvedDoctorId) fd.append("doctor_id", String(resolvedDoctorId));
    if (petId) fd.append("pet_id", String(petId));
    fd.append("video_inclinic", prescriptionForm.consultMode);
    fd.append(
      "visit_category",
      normalizeVisitCategory(prescriptionForm.visitCategory),
    );
    fd.append("consultation_category", prescriptionForm.consultationCategory);
    append("medical_status", prescriptionForm.medicalStatus);
    fd.append("case_severity", prescriptionForm.caseSeverity);
    fd.append("prognosis", prescriptionForm.prognosis);
    fd.append("notes", prescriptionForm.notes.trim());
    if (channelName) fd.append("call_session", channelName);
    append("history_snapshot", historySnapshotValue);
    append("system_affected_id", prescriptionForm.systemAffectedId);
    append(
      "system_affected",
      selectedAffectedSystem?.code || selectedAffectedSystem?.name || "",
    );
    if (isGeneralConsultation) {
      append("temperature", prescriptionForm.temperature);
      append("weight", prescriptionForm.weight);
      append("mucous_membrane", prescriptionForm.mucousMembrane);
      append("dehydration_level", prescriptionForm.dehydrationLevel);
      append("abdominal_pain_reaction", prescriptionForm.abdominalPainReaction);
      append("auscultation", prescriptionForm.auscultation);
      append("physical_exam_other", prescriptionForm.physicalExamOther);
    }
    append("doctor_treatment", prescriptionForm.doctorTreatment);
    append("diagnosis", prescriptionForm.diagnosis);
    append("diagnosis_status", prescriptionForm.diagnosisStatus);
    append("treatment_plan", prescriptionForm.treatmentPlan);
    append("home_care", prescriptionForm.homeCare);
    if (followUpRequired && prescriptionForm.followUpDate)
      fd.append("follow_up_date", prescriptionForm.followUpDate);
    if (followUpRequired) {
      fd.append("follow_up_type", prescriptionForm.followUpMode);
      append("follow_up_notes", prescriptionForm.followUpNotes);
    }
    fd.append("follow_up_required", followUpRequired ? "1" : "0");
    fd.append("medications_json", JSON.stringify(medsPayload));
    if (prescriptionForm.recordFile)
      fd.append("record_file", prescriptionForm.recordFile);

    try {
      if (isMockSubmitMode) {
        await new Promise((resolve) => window.setTimeout(resolve, 350));
        await finalizePrescriptionSuccess();
        return;
      }

      const headers = authToken
        ? { Authorization: `Bearer ${authToken}`, Accept: "application/json" }
        : { Accept: "application/json" };
      const res = await fetch(`${API_BASE_URL}/api/medical-records`, {
        method: "POST",
        headers,
        body: fd,
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data?.success === false) {
        throw new Error(
          buildValidationMessage(data, "Failed to save prescription."),
        );
      }
      await finalizePrescriptionSuccess();
    } catch (err) {
      setPrescriptionError(err?.message || "Failed to save prescription.");
    } finally {
      setPrescriptionSubmitting(false);
    }
  };

  const handleSuccessClose = () => {
    setShowSuccessModal(false);
    onSuccess();
    onClose();
    if (location.pathname === "/counsltflow/digital-prescription") {
      navigate("/counsltflow/dashboard", { replace: true });
    }
  };

  // ══════════════════════════════════════════════════════════════════════════
  // RENDER
  // ══════════════════════════════════════════════════════════════════════════
  return (
    <div className="mx-auto w-full max-w-5xl rounded-2xl border border-gray-200 bg-[#FCFCFC] shadow-sm overflow-hidden lg:overflow-visible">
      {/* ── Header ── */}
      <div className="bg-white px-4 py-2.5 md:px-5 md:py-3 flex items-center justify-between border-b border-gray-200">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center">
            <FileText size={20} className="text-blue-600" />
          </div>
          <div>
            <p className="text-gray-500 text-[11px] font-semibold uppercase tracking-wide">
              Consultation Prescription
            </p>
            <h3 className="text-gray-900 font-semibold">Medical Record</h3>
          </div>
        </div>
        <div className="flex items-center gap-2">
          {prescriptionView === "edit" ? (
            <button
              type="button"
              onClick={() => setPrescriptionView("preview")}
              className="rounded-lg px-3 py-1.5 text-xs font-semibold transition border border-gray-200 bg-white text-gray-600 hover:bg-gray-50"
            >
              Preview
            </button>
          ) : (
            <button
              type="button"
              onClick={() => setPrescriptionView("edit")}
              className="rounded-lg px-3 py-1.5 text-xs font-semibold transition bg-blue-600 text-white hover:bg-blue-700"
            >
              Back to Edit
            </button>
          )}
        </div>
      </div>

      {/* ── Form ── */}
      <form onSubmit={handlePrescriptionSubmit} className="p-3.5 md:p-4">
        {prescriptionView === "edit" ? (
          // ════════ EDIT VIEW ════════
          <div className="grid grid-cols-1 gap-4">
            {/* LEFT: main fields */}
            <div className="space-y-4">
              {/* Patient Snapshot */}
              <div className="bg-gray-50 rounded-xl border border-gray-200 p-3 space-y-3">
                <div className="flex items-center justify-between">
                  <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    Patient Snapshot
                  </p>
                  <span
                    className={`px-3 py-1.5 rounded-full text-xs font-medium border ${statusClass(activeTransaction?.status)}`}
                  >
                    {statusLabel(activeTransaction?.status)}
                  </span>
                </div>
                <div className="grid gap-3 md:grid-cols-2">
                  <div className="rounded-xl border border-gray-200 bg-white p-3">
                    <p className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                      Pet Information
                    </p>
                    <p className="mt-1 text-sm font-semibold text-gray-800">
                      {resolvePetName(activeTransaction)} /{" "}
                      {activeTransaction?.pet?.breed || "Not available"} /{" "}
                      {petAgeLabel} / {petWeightLabel}
                    </p>
                    <div className="mt-2 grid grid-cols-2 gap-2 text-[11px] text-gray-600">
                      <p>
                        <span className="font-semibold text-gray-700">
                          Type:
                        </span>{" "}
                        {petTypeLabel}
                      </p>
                      <p>
                        <span className="font-semibold text-gray-700">
                          Gender:
                        </span>{" "}
                        {petGenderLabel}
                      </p>
                    </div>
                    {/* <div className="mt-2 flex flex-wrap gap-2">
                      {[["Vaccinated", vaccinationLabel], ["Neutered", neuterLabel], ["Dewormed", dewormingLabel]].map(([k, v]) => (
                        <span key={k} className="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">
                          {k}: {v}
                        </span>
                      ))}
                    </div> */}
                  </div>
                  <div className="space-y-3">
                    <div className="rounded-xl border border-gray-200 bg-white p-3">
                      <p className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                        Owner Name
                      </p>
                      <p className="mt-1 text-sm font-semibold text-gray-800">
                        {activeTransaction?.user?.name || "Pet Parent"}
                      </p>
                    </div>
                    <div className="rounded-xl border border-gray-200 bg-white p-3">
                      <p className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                        Location
                      </p>
                      <p className="mt-1 text-sm font-semibold text-gray-800">
                        {consultationLocationLabel}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              {/* Consultation Details */}
              <div className="inline-flex flex-col">
                <h4 className="font-medium text-gray-900 flex items-center gap-2">
                  <FileText size={16} className="text-[#0B4D67]" />
                  Consultation Details
                </h4>

                <div className="ml-6 mt-1 relative h-[12px] w-[92px] overflow-hidden">
                  <span className="absolute left-0 top-[1px] h-[8px] w-[72px] rounded-full border-b-[3px] border-[#c89211]" />
                  <span className="absolute left-[24px] top-[1px] h-[8px] w-[70px] rounded-full border-t-[3px] border-[#f3c84b]" />
                </div>
              </div>

              <div className="grid gap-3 sm:grid-cols-2">
                <div className="space-y-1">
                  <label className="block text-xs font-medium text-gray-500 mb-1">
                    Consultation Category{" "}
                    <span className="text-rose-500">*</span>
                  </label>
                  <select
                    value={prescriptionForm.consultationCategory}
                    onChange={(e) => {
                      const next = e.target.value;
                      setPrescriptionForm((prev) => ({
                        ...prev,
                        consultationCategory: next,
                        visitCategory: next,
                        consultMode: isInClinicConsultationCategory(next)
                          ? "in_clinic"
                          : "video",
                      }));
                    }}
                    className={INPUT_BASE_CLASS}
                  >
                    <option value="Online Consultation">
                      Video Call Consultation
                    </option>
                    <option value="General Consultation">
                      In-Clinic Consultation
                    </option>
                  </select>
                </div>
                <div className="space-y-1">
                  <label className="block text-xs font-medium text-gray-500 mb-1">
                    Tag this case as chronic
                  </label>
                  <label className="flex w-full min-h-[44px] items-center gap-3 rounded-lg border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700 transition hover:border-[#0B4D67]/30 hover:bg-white cursor-pointer">
                    <input
                      type="checkbox"
                      checked={prescriptionForm.medicalStatus === "Chronic"}
                      onChange={toggleChronicMedicalStatus}
                      className="h-4 w-4 rounded border-gray-300 text-[#0B4D67] focus:ring-[#0B4D67]/30"
                    />
                    <span>Chronic</span>
                  </label>
                </div>
              </div>

              {/* History */}
              <div className="rounded-xl border border-slate-200 bg-slate-50/80 p-3">
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  History
                </label>
                <textarea
                  ref={historyTextareaRef}
                  value={prescriptionForm.historySnapshot}
                  onChange={updatePrescriptionField(
                    "historySnapshot",
                    PRESCRIPTION_TEXTAREA_MAX_LENGTH,
                  )}
                  rows={3}
                  maxLength={PRESCRIPTION_TEXTAREA_MAX_LENGTH}
                  placeholder="Pet parent-reported history or concern."
                  className={`${INPUT_BASE_CLASS} min-h-[96px] resize-none overflow-hidden border-slate-200 bg-white text-xs leading-5`}
                />
                <div className="mt-1 flex items-center justify-between">
                  <p className="text-[11px] text-slate-500">
                    Prefilled from pet parent details, editable by vet.
                  </p>
                  <p className="text-[11px] text-slate-500">
                    {prescriptionForm.historySnapshot.length} /{" "}
                    {PRESCRIPTION_TEXTAREA_MAX_LENGTH}
                  </p>
                </div>
              </div>

              {/* Diagnosis */}
              <div className="rounded-xl border border-blue-200 bg-blue-50/50 p-3">
                <label className="block text-xs font-semibold text-blue-700 mb-1">
                  Clinical Notes / Tentative Diagnosis by Vet{" "}
                  <span className="text-rose-500">*</span>
                </label>
                <textarea
                  ref={diagnosisTextareaRef}
                  value={prescriptionForm.diagnosis}
                  onChange={updatePrescriptionField(
                    "diagnosis",
                    PRESCRIPTION_TEXTAREA_MAX_LENGTH,
                  )}
                  rows={3}
                  maxLength={PRESCRIPTION_TEXTAREA_MAX_LENGTH}
                  placeholder="Possible gastritis, dehydration..."
                  className={`${INPUT_BASE_CLASS} min-h-[96px] resize-none overflow-hidden border-blue-200 bg-white text-xs leading-5`}
                />
                <div className="mt-1 flex justify-end">
                  <p className="text-[11px] text-blue-700">
                    {getRemainingCharacters(prescriptionForm.diagnosis)}{" "}
                    characters left
                  </p>
                </div>
              </div>

              {/* Prognosis */}
              <div className="rounded-xl border border-blue-200 bg-blue-50/50 p-3">
                <label className="block text-xs font-medium text-gray-500 mb-1">
                  Prognosis <span className="text-rose-500">*</span>
                </label>
                <select
                  value={prescriptionForm.prognosis}
                  onChange={updatePrescriptionField("prognosis")}
                  className={INPUT_BASE_CLASS}
                >
                  {PRESCRIPTION_PROGNOSIS_OPTIONS.map((o) => (
                    <option key={o.value} value={o.value}>
                      {o.label}
                    </option>
                  ))}
                </select>
              </div>

              {/* Home Care */}
              <div className="rounded-xl border border-emerald-200 bg-emerald-50/50 p-3">
                <label className="block text-xs font-semibold text-emerald-700 mb-1">
                  Vet Advice / Home Care Tips{" "}
                  <span className="text-rose-500">*</span>
                </label>
                <textarea
                  ref={adviceTextareaRef}
                  value={prescriptionForm.homeCare}
                  onChange={updatePrescriptionField(
                    "homeCare",
                    PRESCRIPTION_TEXTAREA_MAX_LENGTH,
                  )}
                  rows={3}
                  maxLength={PRESCRIPTION_TEXTAREA_MAX_LENGTH}
                  placeholder="Feed small frequent meals, ensure hydration..."
                  className={`${INPUT_BASE_CLASS} min-h-[96px] resize-none overflow-hidden border-emerald-200 bg-white text-xs leading-5`}
                />
                <div className="mt-1 flex justify-end">
                  <p className="text-[11px] text-emerald-700">
                    {getRemainingCharacters(prescriptionForm.homeCare)}{" "}
                    characters left
                  </p>
                </div>
              </div>

              {/* Physical Examination (in-clinic only) */}
              {isGeneralConsultation && (
                <div className="bg-white border border-gray-200 rounded-xl p-3.5 space-y-3">
                  <h4 className="font-medium text-gray-900 flex items-center gap-2">
                    <Stethoscope size={16} className="text-[#0B4D67]" />
                    Physical Examination
                  </h4>
                  <div className="grid gap-3 sm:grid-cols-2">
                    <div>
                      <label className="block text-xs font-medium text-gray-500 mb-1">
                        Temperature (F) <span className="text-rose-500">*</span>
                      </label>
                      <input
                        type="number"
                        step="0.1"
                        value={prescriptionForm.temperature}
                        onChange={updatePrescriptionField("temperature")}
                        onKeyDown={blockNumberInput}
                        onWheel={handleNumberWheel}
                        placeholder="102.0"
                        className={INPUT_BASE_CLASS}
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-gray-500 mb-1">
                        Weight (kg) <span className="text-rose-500">*</span>
                      </label>
                      <input
                        type="number"
                        step="0.1"
                        value={prescriptionForm.weight}
                        onChange={updatePrescriptionField("weight")}
                        onKeyDown={blockNumberInput}
                        onWheel={handleNumberWheel}
                        placeholder="18.4"
                        className={INPUT_BASE_CLASS}
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-gray-500 mb-1">
                        Mucous Membrane <span className="text-rose-500">*</span>
                      </label>
                      <select
                        value={prescriptionForm.mucousMembrane}
                        onChange={updatePrescriptionField("mucousMembrane")}
                        className={INPUT_BASE_CLASS}
                      >
                        <option value="">Select mucous membrane</option>
                        {PRESCRIPTION_MUCOUS_MEMBRANE_OPTIONS.map((o) => (
                          <option key={o.value} value={o.value}>
                            {o.label}
                          </option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-gray-500 mb-1">
                        Dehydration Level{" "}
                        <span className="text-rose-500">*</span>
                      </label>
                      <select
                        value={prescriptionForm.dehydrationLevel}
                        onChange={updatePrescriptionField("dehydrationLevel")}
                        className={INPUT_BASE_CLASS}
                      >
                        <option value="">Select dehydration level</option>
                        {PRESCRIPTION_DEHYDRATION_LEVEL_OPTIONS.map((o) => (
                          <option key={o.value} value={o.value}>
                            {o.label}
                          </option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-gray-500 mb-1">
                        Abdominal Pain Reaction{" "}
                        <span className="text-rose-500">*</span>
                      </label>
                      <select
                        value={prescriptionForm.abdominalPainReaction}
                        onChange={updatePrescriptionField(
                          "abdominalPainReaction",
                        )}
                        className={INPUT_BASE_CLASS}
                      >
                        <option value="">Select pain response</option>
                        {PRESCRIPTION_ABDOMINAL_PAIN_OPTIONS.map((o) => (
                          <option key={o.value} value={o.value}>
                            {o.label}
                          </option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-gray-500 mb-1">
                        Auscultation <span className="text-rose-500">*</span>
                      </label>
                      <select
                        value={prescriptionForm.auscultation}
                        onChange={updatePrescriptionField("auscultation")}
                        className={INPUT_BASE_CLASS}
                      >
                        <option value="">Select auscultation result</option>
                        {PRESCRIPTION_AUSCULTATION_OPTIONS.map((o) => (
                          <option key={o.value} value={o.value}>
                            {o.label}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>
                  <div>
                    <label className="block text-xs font-medium text-gray-500 mb-1">
                      Physical exam notes{" "}
                      <span className="text-rose-500">*</span>
                    </label>
                    <textarea
                      value={prescriptionForm.physicalExamOther}
                      onChange={updatePrescriptionField("physicalExamOther")}
                      rows={2}
                      placeholder="Mild abdominal guarding, discomfort on palpation..."
                      className={`${INPUT_BASE_CLASS} resize-none text-xs`}
                    />
                  </div>
                </div>
              )}

              {/* Medications */}

              <div className="inline-flex flex-col">
                <h4 className="font-medium text-gray-900 flex items-center gap-2">
                  <Pill size={16} className="text-[#0B4D67]" />
                  Medications
                </h4>

                <div className="ml-6 mt-1 relative h-[12px] w-[92px] overflow-hidden">
                  <span className="absolute left-0 top-[1px] h-[8px] w-[52px] rounded-full border-b-[3px] border-[#c89211]" />
                  <span className="absolute left-[24px] top-[1px] h-[8px] w-[66px] rounded-full border-t-[3px] border-[#f3c84b]" />
                </div>
              </div>
              <div className="space-y-2.5">
                {prescriptionForm.medications.map((medication, index) => (
                  <div
                    key={index}
                    className="rounded-xl border border-gray-200 bg-gray-50 p-3"
                  >
                    <div className="flex items-center justify-between gap-2">
                      <button
                        type="button"
                        onClick={() =>
                          setActiveMedicationIndex((prev) =>
                            prev === index ? -1 : index,
                          )
                        }
                        className="text-left"
                      >
                        <p className="text-sm font-semibold text-gray-800">
                          Medication {index + 1}
                        </p>
                        <p className="text-xs text-gray-500">
                          {medication.name || "Add medicine details"}
                        </p>
                      </button>
                      <button
                        type="button"
                        onClick={() => removeMedication(index)}
                        className="rounded-full border border-stone-200 px-3 py-1.5 text-xs text-stone-500 hover:bg-stone-100 bg-white"
                      >
                        Remove
                      </button>
                    </div>

                    {activeMedicationIndex === index ? (
                      <div className="mt-3 space-y-3 rounded-xl border border-gray-200 bg-white p-3">
                        <div>
                          <label className="block text-xs font-semibold text-gray-700 mb-1.5">
                            Medicine Name
                          </label>
                          <input
                            type="text"
                            value={medication.name}
                            onChange={updateMedication(index, "name")}
                            placeholder="e.g., Amoxicillin"
                            className={INPUT_BASE_CLASS}
                          />
                        </div>
                        <div className="grid grid-cols-2 gap-2.5">
                          <div>
                            <label className="block text-xs font-semibold text-gray-700 mb-1.5">
                              Dosage
                            </label>
                            <input
                              type="text"
                              value={medication.dosage}
                              onChange={updateMedication(index, "dosage")}
                              placeholder="e.g., 1 tab"
                              className={INPUT_BASE_CLASS}
                            />
                          </div>
                          <div>
                            <label className="block text-xs font-semibold text-gray-700 mb-1.5">
                              Duration
                            </label>
                            <input
                              type="text"
                              value={medication.duration}
                              onChange={updateMedication(index, "duration")}
                              placeholder="e.g., 7 days"
                              className={INPUT_BASE_CLASS}
                            />
                          </div>
                        </div>

                        {[
                          {
                            label: "Frequency",
                            options: PRESCRIPTION_FREQUENCY_OPTIONS,
                            field: "frequency",
                            isMulti: false,
                          },
                          {
                            label: "Timing (select one or more)",
                            options: PRESCRIPTION_TIMING_OPTIONS,
                            field: "timing",
                            isMulti: true,
                          },
                          {
                            label: "Food Relation",
                            options: PRESCRIPTION_FOOD_RELATION_OPTIONS,
                            field: "foodRelation",
                            isMulti: false,
                          },
                        ].map(({ label, options, field, isMulti }) => (
                          <div key={field}>
                            <p className="mb-2 text-xs font-semibold text-gray-700">
                              {label}
                            </p>
                            <div className="flex flex-wrap gap-2">
                              {options.map((opt) => {
                                const isSelected = isMulti
                                  ? Array.isArray(medication.timing) &&
                                    medication.timing.includes(opt)
                                  : medication[field] === opt;
                                return (
                                  <button
                                    key={opt}
                                    type="button"
                                    onClick={() =>
                                      isMulti
                                        ? toggleMedicationTiming(index, opt)
                                        : setMedicationField(index, field, opt)
                                    }
                                    className={`rounded-full border px-3 py-1.5 text-xs font-semibold transition ${isSelected ? "border-blue-600 bg-blue-50 text-blue-700" : "border-gray-200 bg-white text-gray-600 hover:border-blue-300"}`}
                                  >
                                    {opt}
                                  </button>
                                );
                              })}
                            </div>
                          </div>
                        ))}

                        <div>
                          <label className="block text-xs font-semibold text-gray-700 mb-1.5">
                            Additional Instruction (optional)
                          </label>
                          <input
                            type="text"
                            value={medication.instructions || ""}
                            onChange={updateMedication(index, "instructions")}
                            placeholder="e.g., Give before sleep"
                            className={INPUT_BASE_CLASS}
                          />
                        </div>
                        <div className="flex justify-end gap-2">
                          <button
                            type="button"
                            onClick={() => closeMedicationEditor(index)}
                            className="inline-flex h-[42px] items-center justify-center rounded-xl border border-gray-200 bg-gray-100 px-4 text-xs font-semibold text-gray-600 hover:bg-gray-200"
                          >
                            Cancel
                          </button>
                          <button
                            type="button"
                            onClick={() => closeMedicationEditor(index)}
                            className="inline-flex h-[42px] items-center justify-center rounded-xl bg-blue-600 px-4 text-xs font-semibold text-white hover:bg-blue-700"
                          >
                            Save
                          </button>
                        </div>
                      </div>
                    ) : (
                      <div className="mt-3 flex flex-wrap gap-2 text-xs text-gray-600">
                        {[
                          medication.dosage || "Dosage N/A",
                          medication.duration || "Duration N/A",
                          medication.frequency || "Frequency N/A",
                        ].map((v) => (
                          <span
                            key={v}
                            className="rounded-full border border-gray-200 bg-white px-2.5 py-1"
                          >
                            {v}
                          </span>
                        ))}
                        {Array.isArray(medication.timing) &&
                          medication.timing.length > 0 && (
                            <span className="rounded-full border border-gray-200 bg-white px-2.5 py-1">
                              {medication.timing.join(", ")}
                            </span>
                          )}
                        {medication.foodRelation && (
                          <span className="rounded-full border border-gray-200 bg-white px-2.5 py-1">
                            {medication.foodRelation}
                          </span>
                        )}
                      </div>
                    )}
                  </div>
                ))}
              </div>
              <button
                type="button"
                onClick={addMedication}
                className="inline-flex h-[42px] w-full items-center justify-center rounded-full border border-stone-200 px-4 text-xs font-semibold text-blue-600 hover:bg-blue-50 sm:w-auto bg-white"
              >
                + Add Medicine
              </button>

              {/* Follow-up */}
              <div className="inline-flex flex-col">
                <h4 className="font-medium text-gray-900 flex items-center gap-2">
                  <Calendar size={16} className="text-[#0B4D67]" />
                  Follow-up
                </h4>

                <div className="ml-6 mt-1 relative h-[12px] w-[92px] overflow-hidden">
                  <span className="absolute left-0 top-[1px] h-[8px] w-[52px] rounded-full border-b-[3px] border-[#c89211]" />
                  <span className="absolute left-[24px] top-[1px] h-[8px] w-[48px] rounded-full border-t-[3px] border-[#f3c84b]" />
                </div>
              </div>

              <div className="grid sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-gray-500 mb-1">
                    Follow-up Required
                  </label>
                  <div className="flex gap-2">
                    {["yes", "no"].map((v) => (
                      <button
                        key={v}
                        type="button"
                        onClick={() =>
                          setPrescriptionForm((prev) => ({
                            ...prev,
                            followUpRequired: v,
                          }))
                        }
                        className={`inline-flex h-[42px] flex-1 items-center justify-center rounded-xl border px-3 text-xs font-semibold uppercase transition ${prescriptionForm.followUpRequired === v ? "border-blue-600 bg-blue-50 text-blue-700" : "border-gray-200 bg-white text-gray-600 hover:bg-gray-50"}`}
                      >
                        {v}
                      </button>
                    ))}
                  </div>
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-500 mb-1">
                    Follow-up Date{" "}
                    {followUpRequired && (
                      <span className="text-rose-500">*</span>
                    )}
                  </label>
                  <input
                    type="date"
                    value={prescriptionForm.followUpDate}
                    onChange={updatePrescriptionField("followUpDate")}
                    disabled={!followUpRequired}
                    className={`${INPUT_BASE_CLASS} ${!followUpRequired ? "cursor-not-allowed bg-gray-100 text-gray-400" : ""}`}
                  />
                </div>
              </div>
              <div>
                <label className="block text-xs font-medium text-gray-500 mb-1">
                  Follow-up Mode
                </label>
                <div className="grid grid-cols-2 gap-2">
                  {[
                    { value: "online", label: "Video Call" },
                    { value: "in_clinic", label: "In-Clinic" },
                  ].map((mode) => (
                    <button
                      key={mode.value}
                      type="button"
                      onClick={() =>
                        setPrescriptionForm((prev) => ({
                          ...prev,
                          followUpMode: mode.value,
                        }))
                      }
                      disabled={!followUpRequired}
                      className={`inline-flex h-[42px] items-center justify-center rounded-xl border px-3 text-xs font-semibold transition ${prescriptionForm.followUpMode === mode.value ? "border-blue-600 bg-blue-50 text-blue-700" : "border-gray-200 bg-white text-gray-600 hover:bg-gray-50"} ${!followUpRequired ? "cursor-not-allowed opacity-60" : ""}`}
                    >
                      {mode.label}
                    </button>
                  ))}
                </div>
              </div>

              {/* System Affected */}
              <div className="flex items-center gap-2 text-sm font-semibold text-stone-700">
                <FileText size={16} className="text-[#0B4D67]" />
                System Affected
              </div>
              <div className="relative" ref={affectedSystemMenuRef}>
                <label className="block text-xs font-medium text-gray-500 mb-1">
                  Select system affected{" "}
                  <span className="text-rose-500">*</span>
                </label>
                <button
                  type="button"
                  onClick={() => setIsAffectedSystemMenuOpen((prev) => !prev)}
                  className={`${INPUT_BASE_CLASS} flex items-center justify-between gap-3 text-left`}
                >
                  <span
                    className={
                      selectedAffectedSystem ? "text-gray-700" : "text-gray-400"
                    }
                  >
                    {selectedAffectedSystem?.name ||
                      (affectedSystemsLoading
                        ? "Loading systems..."
                        : "Select system affected")}
                  </span>
                  <ChevronDown
                    size={16}
                    className={`shrink-0 text-gray-400 transition-transform ${isAffectedSystemMenuOpen ? "rotate-180" : ""}`}
                  />
                </button>
                {isAffectedSystemMenuOpen && (
                  <div className="absolute left-0 right-0 top-full z-30 mt-2 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">
                    <div className="border-b border-gray-100 p-2">
                      <div className="relative">
                        <Search
                          size={14}
                          className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
                        />
                        <input
                          type="text"
                          value={affectedSystemQuery}
                          onChange={(e) =>
                            setAffectedSystemQuery(e.target.value)
                          }
                          placeholder="Search system affected..."
                          autoFocus
                          className="w-full rounded-lg border border-gray-200 bg-gray-50 py-2 pl-9 pr-3 text-sm text-gray-700 outline-none transition focus:border-[#0B4D67] focus:bg-white focus:ring-2 focus:ring-[#0B4D67]/20"
                        />
                      </div>
                    </div>
                    <div className="max-h-56 overflow-y-auto p-1.5">
                      <button
                        type="button"
                        onClick={() => {
                          setPrescriptionForm((prev) => ({
                            ...prev,
                            systemAffectedId: "",
                          }));
                          setIsAffectedSystemMenuOpen(false);
                        }}
                        className={`flex w-full items-start rounded-lg px-3 py-2 text-left text-sm transition ${!prescriptionForm.systemAffectedId ? "bg-blue-50 text-blue-700" : "text-gray-600 hover:bg-gray-50"}`}
                      >
                        Select system affected
                      </button>
                      {filteredAffectedSystems.length > 0 ? (
                        filteredAffectedSystems.map((system) => (
                          <button
                            key={system.id}
                            type="button"
                            onClick={() => {
                              setPrescriptionForm((prev) => ({
                                ...prev,
                                systemAffectedId: String(system.id),
                              }));
                              setIsAffectedSystemMenuOpen(false);
                            }}
                            className={`flex w-full items-start rounded-lg px-3 py-2 text-left text-sm transition ${String(prescriptionForm.systemAffectedId) === String(system.id) ? "bg-blue-50 text-blue-700" : "text-gray-700 hover:bg-gray-50"}`}
                          >
                            {system.name}
                          </button>
                        ))
                      ) : (
                        <div className="px-3 py-2 text-sm text-gray-500">
                          No matching systems
                        </div>
                      )}
                    </div>
                  </div>
                )}
              </div>

              {/* Attach Record */}

              <div className="flex items-center gap-2 text-sm font-semibold text-stone-700">
                <Upload size={16} /> Attach Record (optional)
              </div>
              <label className="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-stone-200 bg-white px-4 py-5 text-center text-xs text-stone-500 hover:border-[#3998de] hover:text-[#3998de]">
                <input
                  type="file"
                  accept=".pdf,.png,.jpg,.jpeg"
                  onChange={handleRecordFile}
                  className="hidden"
                />
                <span className="font-semibold">Upload report file</span>
                <span className="text-[10px] text-stone-400">
                  {prescriptionForm.recordFile?.name ||
                    "PDF, PNG, JPG supported"}
                </span>
              </label>
            </div>

            {/* RIGHT: sidebar */}
            <aside className="space-y-3">
              {/* Consult Summary */}
              {/* Final check */}
              <div className="rounded-xl border border-amber-200 bg-amber-50 p-3.5 shadow-sm">
                <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-amber-800">
                  <AlertCircle size={14} />
                  Final Check Before Sending
                </div>
                <p className="mt-2 text-xs leading-relaxed text-amber-800/90">
                  Please complete the highlighted diagnosis, home care, and
                  medications section for a complete digital prescription.
                </p>
              </div>

              {prescriptionError && (
                <div className="flex items-start gap-2 rounded-xl border border-red-100 bg-red-50 p-3 text-sm text-red-600">
                  <AlertCircle size={16} className="shrink-0 mt-0.5" />
                  <span className="whitespace-pre-line">
                    {prescriptionError}
                  </span>
                </div>
              )}

              <div className="flex flex-col gap-2">
                <button
                  type="button"
                  onClick={() => setPrescriptionView("preview")}
                  className="inline-flex h-[42px] items-center justify-center rounded-full border border-blue-200 bg-blue-50 px-5 text-sm font-semibold text-blue-700 hover:bg-blue-100"
                >
                  Preview
                </button>
                <button
                  type="submit"
                  disabled={prescriptionSubmitting}
                  className={`inline-flex h-[42px] items-center justify-center rounded-full bg-gradient-to-r from-orange-500 to-orange-400 px-6 text-sm font-semibold text-white shadow-sm hover:from-orange-600 hover:to-orange-500 ${prescriptionSubmitting ? "opacity-60 cursor-not-allowed" : ""}`}
                >
                  {prescriptionSubmitting ? "Sending..." : "Send Prescription"}
                </button>
              </div>
            </aside>
          </div>
        ) : (
          // ════════ PREVIEW VIEW ════════
          <div className="mx-auto max-w-5xl space-y-5">
            <div className="overflow-hidden rounded-[30px] border border-slate-200 bg-white shadow-[0_22px_55px_rgba(15,23,42,0.12)]">
              {/* Preview Header */}
              <div className="border-b border-slate-200 bg-gradient-to-r from-slate-50 via-white to-slate-50 px-5 py-5 md:px-7">
                <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                  <div className="flex items-center gap-3">
                    <div className="flex h-11 w-11 items-center justify-center rounded-xl shadow-sm">
                      <img
                        src="/favicon.png"
                        alt="SnoutIQ"
                        className="object-contain border-2 border-slate-200 rounded-xl bg-white p-1"
                      />
                    </div>
                    <div>
                      <p className="text-lg font-bold text-slate-900">
                        Snoutiq Digital Prescription
                      </p>
                      <p className="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">
                        Prepared by {assignedDoctorName}
                      </p>
                    </div>
                  </div>
                  <div className="rounded-xl border border-slate-200 bg-white px-3 py-2 text-right">
                    <p className="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">
                      Date
                    </p>
                    <p className="text-sm font-bold text-slate-900">
                      {new Date().toLocaleDateString("en-US")}
                    </p>
                    <p className="mt-0.5 text-[10px] text-slate-500">
                      Ref:{" "}
                      {activeTransaction?.reference ||
                        activeTransaction?.metadata?.order_id ||
                        "N/A"}
                    </p>
                  </div>
                </div>
                <div className="mt-4 flex flex-wrap gap-2">
                  <span className="rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-semibold text-slate-700">
                    {statusLabel(activeTransaction?.status)}
                  </span>
                  <span className="rounded-full border border-[#b7d3df] bg-[#e9f5fa] px-3 py-1 text-[11px] font-semibold text-[#0B4D67]">
                    {prescriptionForm.consultationCategory ||
                      "General Consultation"}
                  </span>
                  <span className="rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-600">
                    Powered by SnoutIQ
                  </span>
                </div>
              </div>

              <div className="space-y-6 p-5 md:p-7">
                {/* Patient + Clinical Summary */}
                <div className="grid gap-4 lg:grid-cols-2">
                  <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                      Patient Profile
                    </p>
                    <div className="mt-3 space-y-2 text-sm text-slate-700">
                      <p>
                        <span className="font-semibold text-slate-900">
                          Pet:
                        </span>{" "}
                        {resolvePetName(activeTransaction)} /{" "}
                        {activeTransaction?.pet?.breed || "Not available"} /{" "}
                        {petAgeLabel} / {petWeightLabel}
                      </p>
                      <p>
                        <span className="font-semibold text-slate-900">
                          Type / Gender:
                        </span>{" "}
                        {petTypeLabel} / {petGenderLabel}
                      </p>
                      <p>
                        <span className="font-semibold text-slate-900">
                          Owner:
                        </span>{" "}
                        {activeTransaction?.user?.name || "Pet Parent"}
                      </p>
                    </div>
                    {/* <div className="mt-3 flex flex-wrap gap-2">
                      {[["Vaccinated", vaccinationLabel], ["Neutered", neuterLabel], ["Dewormed", dewormingLabel]].map(([k, v]) => (
                        <span key={k} className="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-semibold text-emerald-700">{k}: {v}</span>
                      ))}
                    </div> */}
                  </div>
                  <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                      Clinical Summary
                    </p>
                    <div className="mt-3 grid grid-cols-2 gap-3 text-sm">
                      {prescriptionForm.medicalStatus === "Chronic" && (
                        <div>
                          <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                            Medical Status
                          </p>
                          <p className="mt-1 font-semibold text-slate-900">
                            {prescriptionForm.medicalStatus}
                          </p>
                        </div>
                      )}
                      {[
                        [
                          "Case Severity",
                          formatPetText(
                            prescriptionForm.caseSeverity || "General",
                          ),
                        ],
                        ["Prognosis", prognosisLabel],
                        ["Follow-Up", followUpDisplayLabel],
                        ["Consult Mode", consultModeLabel],
                      ].map(([k, v]) => (
                        <div key={k}>
                          <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                            {k}
                          </p>
                          <p className="mt-1 font-semibold text-slate-900">
                            {v}
                          </p>
                        </div>
                      ))}
                      {selectedAffectedSystem && (
                        <div>
                          <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                            System Affected
                          </p>
                          <p className="mt-1 font-semibold text-slate-900">
                            {selectedAffectedSystem.name}
                          </p>
                        </div>
                      )}
                    </div>
                    <div className="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                      <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                        History
                      </p>
                      <p className="mt-1 text-sm font-medium text-slate-800">
                        {historySnapshotLabel}
                      </p>
                    </div>
                  </div>
                </div>

                {/* Physical Exam (preview) */}
                {isGeneralConsultation && (
                  <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                      Physical Examination
                    </p>
                    <div className="mt-3 flex flex-wrap gap-2">
                      {[
                        prescriptionForm.temperature &&
                          `Temp: ${prescriptionForm.temperature} F`,
                        prescriptionForm.weight &&
                          `Weight: ${prescriptionForm.weight} kg`,
                        prescriptionForm.mucousMembrane &&
                          `Mucous membrane: ${mucousMembraneLabel}`,
                        prescriptionForm.dehydrationLevel &&
                          `Dehydration: ${dehydrationLevelLabel}`,
                        prescriptionForm.abdominalPainReaction &&
                          `Abdominal pain: ${abdominalPainLabel}`,
                        prescriptionForm.auscultation &&
                          `Auscultation: ${auscultationLabel}`,
                      ]
                        .filter(Boolean)
                        .map((v) => (
                          <span
                            key={v}
                            className="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-semibold text-slate-700"
                          >
                            {v}
                          </span>
                        ))}
                    </div>
                    <p className="mt-3 text-sm leading-relaxed text-slate-700">
                      {prescriptionForm.physicalExamOther ||
                        "No additional physical examination notes added."}
                    </p>
                  </div>
                )}

                {/* Medications (preview) */}
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                  <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                    Medications & Treatment
                  </p>
                  <div className="mt-3 space-y-3">
                    {prescriptionForm.medications
                      .filter(
                        (m) =>
                          m.name ||
                          m.dosage ||
                          m.frequency ||
                          m.duration ||
                          m.foodRelation ||
                          (Array.isArray(m.timing) && m.timing.length > 0) ||
                          m.instructions,
                      )
                      .map((m, i) => (
                        <div
                          key={i}
                          className="rounded-xl border border-slate-200 bg-slate-50 p-3"
                        >
                          <div className="flex items-start justify-between gap-3">
                            <div>
                              <p className="text-sm font-semibold text-slate-900">
                                {m.name || "Unnamed Medicine"}
                              </p>
                              <p className="text-xs text-slate-600">
                                {m.dosage || "-"} | {m.duration || "-"}
                              </p>
                            </div>
                            <span className="rounded-full bg-[#e9f5fa] px-2.5 py-1 text-[10px] font-semibold text-[#0B4D67]">
                              {m.frequency || "Frequency N/A"}
                            </span>
                          </div>
                          <div className="mt-2 flex flex-wrap gap-2">
                            {Array.isArray(m.timing) && m.timing.length > 0 && (
                              <span className="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[10px] text-slate-600">
                                {m.timing.join(", ")}
                              </span>
                            )}
                            {m.foodRelation && (
                              <span className="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[10px] text-slate-600">
                                {m.foodRelation}
                              </span>
                            )}
                          </div>
                          {m.instructions && (
                            <p className="mt-2 text-xs text-slate-600">
                              {m.instructions}
                            </p>
                          )}
                        </div>
                      ))}
                    {prescriptionForm.medications.every(
                      (m) =>
                        !m.name &&
                        !m.dosage &&
                        !m.frequency &&
                        !m.duration &&
                        !m.foodRelation &&
                        (!Array.isArray(m.timing) || m.timing.length === 0) &&
                        !m.instructions,
                    ) && (
                      <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                        No medications added.
                      </div>
                    )}
                  </div>
                </div>

                {/* Vet Advice */}
                <div className="rounded-2xl border border-[#b7d3df] bg-[#f0f9fc] p-4">
                  <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-[#0B4D67]">
                    Vet Advice
                  </p>
                  <p className="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-slate-700">
                    {prescriptionForm.homeCare || "Standard care recommended."}
                  </p>
                </div>

                {/* Footer */}
                <div className="grid gap-4 border-t border-slate-200 pt-5 md:grid-cols-2">
                  <div className="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p className="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">
                      Follow-Up Plan
                    </p>
                    <p className="mt-1 text-sm font-semibold text-slate-900">
                      {followUpDisplayLabel}
                      {followUpRequired && (
                        <span className="text-[#0B4D67]">
                          {" "}
                          ({followUpModeLabel})
                        </span>
                      )}
                    </p>
                  </div>
                  <div className="rounded-xl border border-slate-200 bg-white p-3 text-right">
                    <p className="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">
                      Attending Veterinarian
                    </p>
                    <p className="mt-1 text-sm font-semibold text-slate-900">
                      {assignedDoctorName}
                    </p>
                    <p className="text-xs text-slate-500">
                      License: {doctorLicenseLabel}
                    </p>
                    <p className="text-xs text-slate-500">
                      Clinic: {clinicNameLabel}
                    </p>
                    <p className="mt-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-400">
                      Powered by SnoutIQ
                    </p>
                  </div>
                </div>
              </div>
            </div>

            {prescriptionError && (
              <div className="flex items-start gap-2 rounded-xl border border-red-100 bg-red-50 p-3 text-sm text-red-600">
                <AlertCircle size={16} className="shrink-0 mt-0.5" />
                <span className="whitespace-pre-line">{prescriptionError}</span>
              </div>
            )}

            <div className="grid grid-cols-1 gap-3 md:grid-cols-[1fr_1.45fr]">
              <button
                type="button"
                onClick={() => setPrescriptionView("edit")}
                className="rounded-2xl border border-gray-200 bg-white px-6 py-3 text-base font-semibold text-gray-600 hover:bg-gray-50"
              >
                Edit Details
              </button>
              <button
                type="submit"
                disabled={prescriptionSubmitting}
                className={`rounded-2xl bg-gradient-to-r from-[#4f46e5] to-[#4338ca] px-6 py-3 text-base font-semibold text-white shadow-[0_10px_30px_rgba(79,70,229,0.35)] hover:from-[#4338ca] hover:to-[#3730a3] ${prescriptionSubmitting ? "opacity-60 cursor-not-allowed" : ""}`}
              >
                {prescriptionSubmitting ? "Sending..." : "Save & Share"}
              </button>
            </div>
            <p className="text-center text-[11px] text-gray-500">
              Save. (Upon save the prescription will be shared to the pet parent
              in app)
            </p>
          </div>
        )}
      </form>

      {/* ── Success Modal ── */}
      {showSuccessModal && (
        <div className="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4">
          <div className="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl">
            <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
              <CheckCircle2 size={28} />
            </div>
            <div className="text-lg font-bold text-stone-800">
              Prescription saved
            </div>
            <p className="mt-2 text-sm text-stone-500">
              Medical record has been sent successfully.
            </p>
            <button
              type="button"
              onClick={handleSuccessClose}
              className="mt-4 w-full rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 py-3 text-sm font-semibold text-white hover:from-blue-700 hover:to-blue-600 transition-all"
            >
              Done
            </button>
          </div>
        </div>
      )}
    </div>
  );
};

export default NewDoctorDigitalPrescription;
