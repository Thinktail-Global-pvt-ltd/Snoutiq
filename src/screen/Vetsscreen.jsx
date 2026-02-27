// src/screens/VetsScreen.jsx
import React, { useCallback, useEffect, useMemo, useState } from "react";
import { Button } from "../components/Button";
import { Header, PET_FLOW_STEPS, ProgressBar } from "../components/Sharedcomponents";
import {
  Clock,
  Zap,
  ChevronRight,
  GraduationCap,
  Stethoscope,
  BadgeCheck,
  X,
} from "lucide-react";

const API_PATH = "/backend/api/exported_from_excell_doctors";
const DEFAULT_BACKEND_ORIGIN = "https://snoutiq.com";
const ROTATION_INTERVAL_MS = 20000;

const DUMMY_VETS_PAYLOAD = [
  {
    name: "Demo Pet Clinic",
    doctors: [
      {
        id: 999001,
        doctor_name: "Dr. Demo Vet",
        doctor_email: "demo@snoutiq.com",
        doctor_mobile: "9999999999",
        doctor_license: "DEMO-12345",
        doctor_image:
          "https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=640&q=80",
        degree: "BVSc",
        years_of_experience: "7",
        specialization_select_all_that_apply: '["Dogs","Cats"]',
        bio: "Demo profile shown when API is unavailable in debug mode.",
        response_time_for_online_consults_day: "0 to 15 mins",
        response_time_for_online_consults_night: "15 to 20 mins",
        break_do_not_disturb_time_example_2_4_pm: '["No"]',
        do_you_offer_a_free_follow_up_within_3_days_after_a_consulta: "Yes",
        video_day_rate: 499,
        video_night_rate: 649,
      },
    ],
  },
];

/* ---------------- helpers ---------------- */

const getSafeOrigin = () => {
  if (typeof window === "undefined") return DEFAULT_BACKEND_ORIGIN;
  const origin = window.location.origin;
  if (origin.includes("localhost") || origin.includes("127.0.0.1")) return DEFAULT_BACKEND_ORIGIN;
  return origin;
};

const BACKEND_BASE = `${getSafeOrigin()}/backend`;

const buildApiCandidates = () => {
  const origin = getSafeOrigin();
  return Array.from(
    new Set([
      `${origin}${API_PATH}`,
      `https://snoutiq.com${API_PATH}`,
      `https://www.snoutiq.com${API_PATH}`,
    ])
  );
};

const isTruthyFlag = (value) => {
  const normalized = String(value || "")
    .trim()
    .toLowerCase();
  return ["1", "true", "yes", "on"].includes(normalized);
};

const isDebugDummyEnabled = () => {
  if (typeof window === "undefined") return false;

  try {
    const params = new URLSearchParams(window.location.search || "");
    if (
      isTruthyFlag(params.get("debbing")) ||
      isTruthyFlag(params.get("debugging")) ||
      isTruthyFlag(params.get("debug"))
    ) {
      return true;
    }

    return (
      isTruthyFlag(window.localStorage.getItem("debbing")) ||
      isTruthyFlag(window.localStorage.getItem("debugging")) ||
      isTruthyFlag(window.localStorage.getItem("debug"))
    );
  } catch {
    return false;
  }
};

const fetchJsonStrict = async (url, { timeoutMs = 15000 } = {}) => {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);

  try {
    const res = await fetch(url, {
      method: "GET",
      signal: controller.signal,
      cache: "no-store",
      headers: { Accept: "application/json" },
    });

    const contentType = res.headers.get("content-type") || "";
    const text = await res.text();

    if (!res.ok) throw new Error(`HTTP ${res.status}: ${text.slice(0, 140)}`);
    if (!contentType.includes("application/json")) {
      throw new Error(`Non-JSON response (${contentType || "unknown"}): ${text.slice(0, 140)}`);
    }

    return JSON.parse(text);
  } catch (e) {
    if (e?.name === "AbortError") throw new Error("Request timed out. Please try again.");
    throw e;
  } finally {
    clearTimeout(timer);
  }
};

export const loadVetsWithFallback = async () => {
  const candidates = buildApiCandidates();
  const debugDummy = isDebugDummyEnabled();
  let lastErr = null;

  for (const url of candidates) {
    try {
      const json = await fetchJsonStrict(url);
      if (json?.success && Array.isArray(json?.data)) {
        if (json.data.length > 0 || !debugDummy) return json.data;
        console.warn("[VetsScreen] Empty API data. Showing dummy doctor because debbing=1.");
        return DUMMY_VETS_PAYLOAD;
      }
      lastErr = new Error("Invalid API response shape");
    } catch (e) {
      lastErr = e;
    }
  }

  if (debugDummy) {
    console.warn("[VetsScreen] API unavailable. Showing dummy doctor because debbing=1.");
    return DUMMY_VETS_PAYLOAD;
  }

  throw lastErr || new Error("Network error while loading vets.");
};

const normalizeImageUrl = (value) => {
  if (!value) return "";
  const trimmed = String(value).trim();
  if (!trimmed) return "";

  const lower = trimmed.toLowerCase();
  if (lower === "null" || lower === "undefined") return "";

  if (lower.includes("https://snoutiq.com/https://snoutiq.com/")) {
    return trimmed.replace(
      "https://snoutiq.com/https://snoutiq.com/",
      "https://snoutiq.com/"
    );
  }

  if (lower.startsWith("http://") || lower.startsWith("https://") || lower.startsWith("data:")) {
    return trimmed;
  }

  let cleaned = trimmed.replace(/^\/+/, "");
  if (cleaned.toLowerCase().startsWith("backend/")) {
    cleaned = cleaned.slice("backend/".length);
  }

  return `${BACKEND_BASE}/${cleaned}`;
};

const getDoctorImageSource = (doc) =>
  doc?.doctor_image_blob_url ||
  doc?.doctor_image_url ||
  doc?.doctor_image ||
  "";

const getInitials = (name = "") => {
  const s = String(name).trim();
  if (!s) return "V";
  const parts = s.split(" ").filter(Boolean);
  if (parts.length === 1) return parts[0][0]?.toUpperCase() || "V";
  return `${parts[0][0] || ""}${parts[1][0] || ""}`.toUpperCase();
};

const parseListField = (value) => {
  if (!value) return [];
  if (Array.isArray(value)) return value.map((x) => String(x).trim()).filter(Boolean);

  if (typeof value === "string") {
    const trimmed = value.trim();
    if (!trimmed) return [];
    if (trimmed.startsWith("[") && trimmed.endsWith("]")) {
      try {
        const parsed = JSON.parse(trimmed);
        if (Array.isArray(parsed)) return parsed.map((x) => String(x).trim()).filter(Boolean);
      } catch {
        // ignore
      }
    }
    return trimmed.split(",").map((x) => x.trim()).filter(Boolean);
  }
  return [String(value).trim()].filter(Boolean);
};

const normalizeText = (value) => {
  if (value === undefined || value === null) return "";
  const trimmed = String(value).trim();
  if (!trimmed) return "";
  const lower = trimmed.toLowerCase();
  if (lower === "null" || lower === "undefined" || lower === "[]") return "";
  return trimmed;
};

const hasDisplayValue = (value) => {
  if (value === undefined || value === null) return false;
  if (Array.isArray(value)) return value.length > 0;
  if (typeof value === "number") return Number.isFinite(value) && value > 0;
  const trimmed = String(value).trim();
  if (!trimmed) return false;
  const lower = trimmed.toLowerCase();
  if (lower === "null" || lower === "undefined" || lower === "[]") return false;
  if (["na", "n/a", "none"].includes(lower)) return false;
  return true;
};

const buildSpecializationData = (value) => {
  const list = parseListField(value);
  let text = list.length ? list.join(", ") : "";
  if (!text) {
    const raw = String(value || "").trim();
    const lower = raw.toLowerCase();
    if (raw && lower !== "null" && lower !== "undefined" && lower !== "[]") {
      if (raw.startsWith("[") && raw.endsWith("]")) {
        text = raw.slice(1, -1).replace(/["']/g, "").trim();
      } else {
        text = raw;
      }
    }
  }
  return { list, text };
};

const normalizeSpecializationKey = (value) =>
  String(value || "")
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, " ")
    .trim();

const isPetTypeSpecialization = (value) => {
  const key = normalizeSpecializationKey(value);
  if (!key) return false;
  return (
    key.includes("dog") ||
    key.includes("cat") ||
    key.includes("exotic") ||
    key.includes("livestock") ||
    key.includes("avian") ||
    key.includes("bird") ||
    key.includes("rabbit") ||
    key.includes("turtle")
  );
};

const buildDisplaySpecializationList = (list = []) => {
  const unique = [];
  const seen = new Set();
  list.forEach((item) => {
    const label = String(item || "").trim();
    if (!label) return;
    const key = normalizeSpecializationKey(label);
    if (!key || seen.has(key)) return;
    seen.add(key);
    unique.push(label);
  });

  if (!unique.length) return [];
  const focused = unique.filter((item) => !isPetTypeSpecialization(item));
  const petTypes = unique.filter((item) => isPetTypeSpecialization(item));
  return [...focused, ...petTypes];
};

const buildDegreeData = (value) => {
  const list = parseListField(value);
  let text = list.length ? list.join(", ") : "";
  if (!text) {
    const raw = String(value || "").trim();
    const lower = raw.toLowerCase();
    if (raw && lower !== "null" && lower !== "undefined" && lower !== "[]") {
      if (raw.startsWith("[") && raw.endsWith("]")) {
        text = raw.slice(1, -1).replace(/["']/g, "").trim();
      } else {
        text = raw;
      }
    }
  }
  return { list, text };
};

const normalizeBreakTimes = (value) => {
  const list = parseListField(value);
  return list.filter((item) => {
    const cleaned = String(item).toLowerCase().replace(/[^a-z0-9]/g, "");
    if (!cleaned) return false;
    if (["no", "none", "nil", "na", "n/a", "noany", "notavailable"].includes(cleaned)) return false;
    if (cleaned.startsWith("no")) return false;
    return true;
  });
};

const normalizeSpecialties = (specializationText = "") => {
  const raw = parseListField(specializationText)
    .map((s) => String(s).toLowerCase())
    .filter(Boolean);

  const mapped = new Set();
  raw.forEach((t) => {
    if (t.includes("dog")) mapped.add("dog");
    if (t.includes("cat")) mapped.add("cat");
    if (t.includes("exotic") || t.includes("bird") || t.includes("rabbit") || t.includes("turtle"))
      mapped.add("exotic");
  });
  return Array.from(mapped);
};

const toOptionalNumber = (value) => {
  const n = Number(value);
  return Number.isFinite(n) ? n : null;
};

const toNumber = (v, fallback = 0) => {
  const n = Number(v);
  return Number.isFinite(n) ? n : fallback;
};

const isDayTime = (date = new Date()) => {
  const hour = date.getHours();
  return hour >= 8 && hour < 20;
};

const clipText = (text, max = 160) => {
  const s = String(text || "").replace(/\s+/g, " ").trim();
  if (!s) return "";
  if (s.length <= max) return s;
  return `${s.slice(0, max).trim()}…`;
};

/**
 * ✅ IMPORTANT:
 * - HIDE DEMO/INVALID vets: if day OR night rate <= 2 => do not show
 * - Keep priceDay/priceNight numbers
 */
export const buildVetsFromApi = (apiData = []) => {
  const list = [];
  apiData.forEach((clinic) => {
    const clinicName = normalizeText(clinic?.name);

    (clinic?.doctors || []).forEach((doc) => {
      const specializationData = buildSpecializationData(
        doc?.specialization_select_all_that_apply
      );
      const specializationList = buildDisplaySpecializationList(specializationData.list);
      const specializationText = normalizeText(
        specializationList.length
          ? specializationList.join(", ")
          : specializationData.text
      );
      const degreeData = buildDegreeData(doc?.degree);
      const breakTimes = normalizeBreakTimes(doc?.break_do_not_disturb_time_example_2_4_pm);

      const priceDay = toNumber(doc?.video_day_rate, 0);
      const priceNight = toNumber(doc?.video_night_rate, 0);
      const apiReviews = Math.max(0, Math.round(toNumber(doc?.reviews_count, 0)));
      const apiRating = toOptionalNumber(doc?.average_review_points);

      if (priceDay <= 2 || priceNight <= 2) return;

      list.push({
        id: doc?.id,
        clinicName,

        name: normalizeText(doc?.doctor_name) || "Vet",
        qualification: normalizeText(degreeData.text),
        degreeList: degreeData.list,
        experience: toOptionalNumber(doc?.years_of_experience),

        image: normalizeImageUrl(getDoctorImageSource(doc)),

        priceDay,
        priceNight,

        rating:
          Number.isFinite(apiRating) && apiRating > 0
            ? Math.round(apiRating * 10) / 10
            : null,
        reviews: apiReviews,
        consultations: null,

        specialties: normalizeSpecialties(specializationList),
        specializationList,
        specializationText,

        responseDay: normalizeText(doc?.response_time_for_online_consults_day),
        responseNight: normalizeText(doc?.response_time_for_online_consults_night),

        breakTimes,
        followUp: normalizeText(doc?.do_you_offer_a_free_follow_up_within_3_days_after_a_consulta),
        bio: normalizeText(doc?.bio),
        raw: doc,
      });
    });
  });

  return list;
};

/* ---------------- UI bits ---------------- */

const SkeletonCard = () => (
  <div className="rounded-3xl border border-slate-200 bg-white shadow-sm p-5 animate-pulse">
    <div className="flex gap-4">
      <div className="h-16 w-16 rounded-2xl bg-slate-100" />
      <div className="flex-1 space-y-2">
        <div className="h-4 w-2/3 rounded bg-slate-100" />
        <div className="h-3 w-1/2 rounded bg-slate-100" />
        <div className="h-3 w-5/6 rounded bg-slate-100" />
      </div>
    </div>
    <div className="mt-4 h-12 rounded-2xl bg-slate-100" />
  </div>
);

const InfoRow = ({ icon: Icon, label, value, subValue }) => {
  const showValue = hasDisplayValue(value);
  const showSubValue = hasDisplayValue(subValue);
  if (!showValue && !showSubValue) return null;

  return (
    <div className="flex items-start gap-3">
      <div className="mt-[2px] inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-teal-50 text-teal-700 border border-teal-100">
        <Icon size={18} />
      </div>
      <div className="min-w-0 flex-1">
        <div className="text-[11px] font-semibold text-slate-500">{label}</div>
        <div className="text-sm font-semibold text-slate-900 leading-5 break-words line-clamp-2">
          {showValue ? value : null}
          {showValue && showSubValue ? (
            <span className="text-slate-400 font-semibold">{" - "}{subValue}</span>
          ) : null}
          {!showValue && showSubValue ? (
            <span className="text-slate-900 font-semibold">{subValue}</span>
          ) : null}
        </div>
      </div>
    </div>
  );
};

/* ---------------- Screen ---------------- */

const VetsScreen = ({ petDetails, onSelect, onBack }) => {
  const [vets, setVets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [errMsg, setErrMsg] = useState("");
  const [activeBioVet, setActiveBioVet] = useState(null);
  const [brokenImages, setBrokenImages] = useState(() => new Set());
  const [rotationIndex, setRotationIndex] = useState(0);

  const markImageBroken = useCallback((id) => {
    if (!id) return;
    setBrokenImages((prev) => {
      const next = new Set(prev);
      next.add(id);
      return next;
    });
  }, []);

  const fetchVets = useCallback(async () => {
    setLoading(true);
    setErrMsg("");

    try {
      const data = await loadVetsWithFallback();
      const list = buildVetsFromApi(data);
      setVets(list);
      if (!list.length) setErrMsg("");
    } catch (e) {
      console.error("[VetsScreen] load failed:", e);
      setVets([]);
      setErrMsg(e?.message || "Network error while loading vets.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    let ignore = false;
    const run = async () => {
      if (ignore) return;
      await fetchVets();
    };
    run();
    return () => {
      ignore = true;
    };
  }, [fetchVets]);

  const sortedVets = useMemo(() => {
    const base = [...vets];
    const specialtyScore = (v) =>
      petDetails?.type && v.specialties?.includes(petDetails.type) ? 1 : 0;

    return base.sort((a, b) => {
      const aMatch = specialtyScore(a);
      const bMatch = specialtyScore(b);
      if (aMatch && !bMatch) return -1;
      if (!aMatch && bMatch) return 1;

      const isDay = isDayTime();
      const aPrice = isDay ? a.priceDay : a.priceNight;
      const bPrice = isDay ? b.priceDay : b.priceNight;
      return (aPrice || 0) - (bPrice || 0);
    });
  }, [vets, petDetails]);

  useEffect(() => {
    if (sortedVets.length < 2) return undefined;
    const interval = setInterval(() => {
      setRotationIndex((prev) => (prev + 1) % sortedVets.length);
    }, ROTATION_INTERVAL_MS);
    return () => clearInterval(interval);
  }, [sortedVets.length]);

  const rotatedVets = useMemo(() => {
    if (sortedVets.length <= 1) return sortedVets;
    const shift = rotationIndex % sortedVets.length;
    if (shift === 0) return sortedVets;
    return [...sortedVets.slice(shift), ...sortedVets.slice(0, shift)];
  }, [sortedVets, rotationIndex]);

  return (
    <div className="min-h-screen bg-slate-50 flex flex-col">
      <Header onBack={onBack} title="Available Vets" />

      <div className="flex-1 px-4 py-6 pb-20 overflow-y-auto no-scrollbar md:px-10 lg:px-16 md:py-10">
        <ProgressBar current={1} steps={PET_FLOW_STEPS} />

        <div className="mt-6 md:mt-10">
          <div className="flex items-start justify-between gap-4">
            <div className="min-w-0 flex-1">
              <h2 className="text-2xl md:text-4xl font-extrabold tracking-tight text-slate-900">
                {petDetails?.name ? `Vets for ${petDetails.name}` : "Available Vets"}
              </h2>
              <p className="mt-2 text-sm md:text-base text-slate-500 max-w-3xl">
                Choose a vet based on experience, specialization, and response time.
              </p>
            </div>

            <div className="shrink-0 flex items-center gap-2">
              <div className="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-2 text-emerald-700 text-xs md:text-sm font-semibold border border-emerald-100">
                <Zap size={16} fill="currentColor" />
                <span>Fast response</span>
              </div>
            </div>
          </div>

          <div className="mt-4 inline-flex items-center gap-2 rounded-2xl bg-white px-4 py-3 border border-slate-200 shadow-sm text-slate-600">
            <Clock size={18} />
            <span className="text-sm md:text-base">
              Average response time: <strong className="text-slate-900">8 mins</strong>
            </span>
          </div>
        </div>

        {loading ? (
          <div className="mt-8 grid gap-4 md:grid-cols-2 md:gap-6 lg:grid-cols-3">
            {Array.from({ length: 6 }).map((_, i) => (
              <SkeletonCard key={i} />
            ))}
          </div>
        ) : errMsg ? (
          <div className="mt-8 rounded-3xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm">
            <div className="text-red-600 font-bold">{errMsg}</div>
            <div className="text-slate-500 text-sm mt-2">Try again or check network.</div>

            <div className="mt-4 flex gap-3">
              <button
                type="button"
                onClick={fetchVets}
                className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-100"
              >
                Try again
              </button>

              <button
                type="button"
                onClick={() => window.location.reload()}
                className="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50"
              >
                Reload page
              </button>
            </div>
          </div>
        ) : rotatedVets.length === 0 ? (
          <div className="mt-8 rounded-3xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm">
            <div className="text-slate-900 font-bold">No vets found.</div>
            <div className="text-slate-500 text-sm mt-2">Please try again later.</div>
          </div>
        ) : (
          <div className="mt-8 grid auto-rows-fr items-stretch gap-4 md:grid-cols-2 md:gap-6 lg:grid-cols-3">
            {rotatedVets.map((vet) => {
              const showImage = Boolean(vet.image) && !brokenImages.has(vet.id);
              const initials = getInitials(vet.name);
              const responseLabelRaw = isDayTime() ? vet.responseDay : vet.responseNight;
              const responseLabel = hasDisplayValue(responseLabelRaw)
                ? String(responseLabelRaw).trim()
                : "0 to 15 mins";
              const followUpLabel = hasDisplayValue(vet.followUp)
                ? clipText(vet.followUp, 44)
                : "Follow-up available";
              const experienceLabel = hasDisplayValue(vet.experience)
                ? `${vet.experience} years exp.`
                : "";
              const ratingValue = Number(vet.rating);
              const hasRating = Number.isFinite(ratingValue) && ratingValue > 0;

              const specializationValue = hasDisplayValue(vet.specializationText)
                ? vet.specializationText
                : Array.isArray(vet.specializationList) && vet.specializationList.length
                ? vet.specializationList.join(", ")
                : "";

              return (
                <div
                  key={vet.id}
                  className="rounded-3xl border border-slate-200 bg-white shadow-sm hover:shadow-lg transition-all overflow-hidden flex flex-col h-full"
                >
                  <div className="h-1 bg-gradient-to-r from-teal-500 to-emerald-500" />

                  <div className="p-5 flex flex-col h-full">
                    <div className="flex gap-4">
                      {showImage ? (
                        <img
                          src={vet.image}
                          alt={vet.name}
                          loading="lazy"
                          crossOrigin="anonymous"
                          onError={() => markImageBroken(vet.id)}
                          className="h-16 w-16 rounded-2xl object-cover border border-slate-200 bg-slate-50 shrink-0"
                        />
                      ) : (
                        <div className="h-16 w-16 rounded-2xl bg-amber-400 text-white flex items-center justify-center text-xl font-extrabold shadow-sm shrink-0">
                          {initials}
                        </div>
                      )}

                      <div className="flex-1 min-w-0">
                        <div className="flex items-start justify-between gap-2">
                          <div className="min-w-0">
                            <h3 className="truncate text-base md:text-lg font-extrabold text-slate-900">
                              {vet.name}
                            </h3>

                            {hasDisplayValue(vet.clinicName) ? (
                              <p className="mt-0.5 text-xs md:text-sm text-slate-500 truncate">
                                {vet.clinicName}
                              </p>
                            ) : null}
                          </div>

                          <div className="shrink-0 text-right">
                            <div className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 border border-emerald-100">
                              <span className="h-2 w-2 rounded-full bg-emerald-500" />
                              <span className="text-xs font-extrabold text-emerald-800">
                                Available
                              </span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div className="mt-4 space-y-3">
                      <InfoRow
                        icon={GraduationCap}
                        label="Education"
                        value={vet.qualification}
                        subValue={experienceLabel}
                      />

                      <InfoRow
                        icon={Stethoscope}
                        label="Specialization"
                        value={specializationValue}
                      />

                      <InfoRow icon={Clock} label="Response Time" value={responseLabel} />
                    </div>

                    <div className="mt-4 grid grid-cols-2 gap-2">
                      <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-2">
                        <div className="text-[10px] font-semibold uppercase tracking-wide text-emerald-700">
                          Follow-up
                        </div>
                        <div className="mt-0.5 inline-flex items-center gap-1 text-xs font-bold text-emerald-800">
                          <BadgeCheck size={13} />
                          {followUpLabel}
                        </div>
                      </div>

                      <div className="rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-right">
                        <div className="text-[10px] font-semibold uppercase tracking-wide text-amber-700">
                          Rating
                        </div>
                        <div className="mt-0.5 text-sm font-extrabold text-amber-900">
                          {hasRating ? `${ratingValue.toFixed(1)} / 5` : "New"}
                        </div>
                        <div className="text-[11px] font-medium text-amber-700">
                          {vet.reviews || 0} review{(vet.reviews || 0) === 1 ? "" : "s"}
                        </div>
                      </div>
                    </div>

                    <div className="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                      <button
                        type="button"
                        onClick={() => setActiveBioVet(vet)}
                        className="text-sm font-semibold text-teal-700 hover:text-teal-800 inline-flex items-center gap-1"
                      >
                        View full profile <ChevronRight size={16} />
                      </button>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        )}

        <div className="hidden md:block h-10" />
      </div>

      {activeBioVet ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
          <div className="w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl border border-slate-200">
            <div className="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white/90 backdrop-blur px-5 py-4 md:px-7">
              <div className="min-w-0 flex-1">
                <p className="text-[11px] uppercase tracking-wider text-slate-400">Vet Profile</p>
                <h3 className="truncate text-lg font-extrabold text-slate-900 md:text-xl">
                  {activeBioVet.name}
                </h3>
                {hasDisplayValue(activeBioVet.clinicName) ? (
                  <p className="mt-0.5 truncate text-xs text-slate-500">
                    {activeBioVet.clinicName}
                  </p>
                ) : null}
              </div>

              <button
                type="button"
                onClick={() => setActiveBioVet(null)}
                className="rounded-2xl border border-slate-200 bg-slate-50 p-2 text-slate-700 hover:bg-slate-100"
                aria-label="Close"
              >
                <X size={18} />
              </button>
            </div>

            <div className="max-h-[82vh] overflow-y-auto p-5 md:p-7">
              <div className="rounded-3xl border border-slate-200 bg-slate-50 p-5 md:p-7">
                <div className="flex flex-col gap-5 md:flex-row md:items-start md:gap-8">
                  <div className="shrink-0">
                    {activeBioVet?.image && !brokenImages.has(activeBioVet.id) ? (
                      <img
                        src={activeBioVet.image}
                        alt={activeBioVet.name}
                        loading="lazy"
                        crossOrigin="anonymous"
                        onError={() => markImageBroken(activeBioVet.id)}
                        className="h-36 w-36 md:h-44 md:w-44 rounded-3xl object-cover border border-slate-200 bg-white shadow-sm"
                      />
                    ) : (
                      <div className="h-36 w-36 md:h-44 md:w-44 rounded-3xl bg-amber-400 text-white flex items-center justify-center text-4xl font-extrabold shadow-sm">
                        {getInitials(activeBioVet?.name)}
                      </div>
                    )}
                  </div>

                  <div className="flex-1 min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                      {hasDisplayValue(activeBioVet.qualification) ? (
                        <span className="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 border border-slate-200">
                          {activeBioVet.qualification}
                        </span>
                      ) : null}
                      {hasDisplayValue(activeBioVet.experience) ? (
                        <span className="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 border border-slate-200">
                          {activeBioVet.experience} yrs exp
                        </span>
                      ) : null}
                      {hasDisplayValue(activeBioVet.raw?.doctor_license) ? (
                        <span className="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 border border-slate-200">
                          License: {activeBioVet.raw?.doctor_license}
                        </span>
                      ) : null}
                    </div>

                    {hasDisplayValue(activeBioVet.followUp) || activeBioVet.breakTimes?.length ? (
                      <div className="mt-4 grid gap-3 sm:grid-cols-2">
                        {hasDisplayValue(activeBioVet.followUp) ? (
                          <div className="rounded-2xl border border-slate-200 bg-white p-4">
                            <div className="text-[11px] uppercase tracking-wide text-slate-400">
                              Follow-up
                            </div>
                            <div className="mt-1 text-sm font-semibold text-slate-800">
                              {activeBioVet.followUp}
                            </div>
                          </div>
                        ) : null}

                        {activeBioVet.breakTimes?.length ? (
                          <div className="rounded-2xl border border-slate-200 bg-white p-4">
                            <div className="text-[11px] uppercase tracking-wide text-slate-400">
                              Break time
                            </div>
                            <div className="mt-1 text-sm font-semibold text-slate-800">
                              {activeBioVet.breakTimes.join(", ")}
                            </div>
                          </div>
                        ) : null}
                      </div>
                    ) : null}

                    <div className="mt-4 grid gap-3 sm:grid-cols-2">
                      <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <div className="text-[11px] uppercase tracking-wide text-slate-400">
                          Response time
                        </div>
                        <div className="mt-1 text-sm font-semibold text-slate-800">
                          Day: {hasDisplayValue(activeBioVet.responseDay) ? activeBioVet.responseDay : "0 to 15 mins"}
                        </div>
                        <div className="mt-0.5 text-sm font-semibold text-slate-800">
                          Night: {hasDisplayValue(activeBioVet.responseNight) ? activeBioVet.responseNight : "15 to 20 mins"}
                        </div>
                      </div>

                      <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <div className="text-[11px] uppercase tracking-wide text-slate-400">
                          Languages
                        </div>
                        <div className="mt-1 text-sm font-semibold text-slate-800">
                          {(() => {
                            const langs = parseListField(activeBioVet.raw?.languages_spoken);
                            return langs.length ? langs.join(", ") : "English / Hindi";
                          })()}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div className="mt-5 grid gap-4 md:grid-cols-5">
                  {hasDisplayValue(activeBioVet.bio) ? (
                    <div className="md:col-span-3 rounded-3xl border border-slate-200 bg-white p-5 md:p-6">
                      <div className="text-[11px] uppercase tracking-wider text-slate-400">
                        About
                      </div>
                      <div className="mt-1 text-base font-extrabold text-slate-900">
                        Doctor Bio
                      </div>

                      <div className="mt-4 text-sm leading-6 text-slate-700 whitespace-pre-line">
                        {activeBioVet.bio.trim()}
                      </div>
                    </div>
                  ) : null}

                  {(Array.isArray(activeBioVet.specializationList) &&
                    activeBioVet.specializationList.length > 0) ||
                  hasDisplayValue(activeBioVet.specializationText) ? (
                    <div className="md:col-span-2 rounded-3xl border border-slate-200 bg-white p-5 md:p-6">
                      <div className="text-[11px] uppercase tracking-wider text-slate-400">
                        Expertise
                      </div>
                      <div className="mt-1 text-base font-extrabold text-slate-900">
                        Specializations
                      </div>

                      <div className="mt-4 text-sm text-slate-700">
                        {Array.isArray(activeBioVet.specializationList) &&
                        activeBioVet.specializationList.length ? (
                          <div className="flex flex-wrap gap-2">
                            {activeBioVet.specializationList.map((s, idx) => (
                              <span
                                key={`${s}-${idx}`}
                                className="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700"
                              >
                                {String(s)}
                              </span>
                            ))}
                          </div>
                        ) : (
                          <div className="text-slate-700">{activeBioVet.specializationText}</div>
                        )}
                      </div>
                    </div>
                  ) : null}
                </div>

                <div className="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                  <Button onClick={() => setActiveBioVet(null)} className="px-6">
                    Close
                  </Button>

                  <Button
                    onClick={() => {
                      const v = activeBioVet;
                      setActiveBioVet(null);

                      const day = isDayTime();
                      const bookingPrice = day ? v.priceDay : v.priceNight;

                      onSelect?.({
                        ...v,
                        bookingRateType: day ? "day" : "night",
                        bookingPrice,
                      });
                    }}
                    className="px-6 bg-teal-600 hover:bg-teal-700 inline-flex items-center gap-2"
                  >
                    Proceed to Consult <ChevronRight size={18} />
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </div>
      ) : null}
    </div>
  );
};

export default VetsScreen;


