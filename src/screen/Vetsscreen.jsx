import React, { useEffect, useMemo, useState } from "react";
import { Button } from "../components/Button";
import { Header, PET_FLOW_STEPS, ProgressBar } from "../components/Sharedcomponents";
import {
  Clock,
  Zap,
  ChevronRight,
  GraduationCap,
  Stethoscope,
  BadgeCheck,
  Star,
  X,
} from "lucide-react";

const API_URL = "https://snoutiq.com/backend/api/exported_from_excell_doctors";
const BACKEND_BASE = "https://snoutiq.com/backend";

/* ---------------- helpers ---------------- */

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

  // ✅ already absolute (http/https/data)
  if (
    lower.startsWith("http://") ||
    lower.startsWith("https://") ||
    lower.startsWith("data:")
  ) {
    return trimmed;
  }

  // ✅ handle "/photo/..." or "photo/..."
  let cleaned = trimmed.replace(/^\/+/, "");

  // ✅ avoid backend/backend if API gives "backend/photo/.."
  if (cleaned.toLowerCase().startsWith("backend/")) {
    cleaned = cleaned.slice("backend/".length);
  }

  // ✅ Ensure proper path construction
  return `${BACKEND_BASE}/${cleaned}`;
};

const getDoctorImageSource = (doc) =>
  doc?.doctor_image_blob_url || doc?.doctor_image_url || doc?.doctor_image || "";

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
  const raw = parseListField(specializationText).map((s) => String(s).toLowerCase()).filter(Boolean);
  const mapped = new Set();
  raw.forEach((t) => {
    if (t.includes("dog")) mapped.add("dog");
    if (t.includes("cat")) mapped.add("cat");
    if (t.includes("exotic") || t.includes("bird") || t.includes("rabbit") || t.includes("turtle"))
      mapped.add("exotic");
  });
  return Array.from(mapped);
};

const toNumber = (v, fallback = 0) => {
  const n = Number(v);
  return Number.isFinite(n) ? n : fallback;
};

const isDayTime = (date = new Date()) => {
  const hour = date.getHours();
  return hour >= 8 && hour < 20;
};

const formatPrice = (value) => {
  const amount = Number(value);
  if (!Number.isFinite(amount) || amount <= 0) return "";
  return `₹${amount}`;
};

const clipText = (text, max = 160) => {
  const s = String(text || "").replace(/\s+/g, " ").trim();
  if (!s) return "";
  if (s.length <= max) return s;
  return `${s.slice(0, max).trim()}…`;
};

const buildVetsFromApi = (apiData = []) => {
  const list = [];
  apiData.forEach((clinic) => {
    const clinicName = clinic?.name || "Clinic";
    (clinic?.doctors || []).forEach((doc) => {
      const specializationData = buildSpecializationData(
        doc?.specialization_select_all_that_apply
      );
      const degreeData = buildDegreeData(doc?.degree);
      const breakTimes = normalizeBreakTimes(doc?.break_do_not_disturb_time_example_2_4_pm);

      list.push({
        id: doc?.id,
        clinicName,

        name: doc?.doctor_name || "Vet",
        qualification: degreeData.text || "",
        degreeList: degreeData.list,
        experience: toNumber(doc?.years_of_experience, 0),

        // ✅ use blob endpoint when available, fallback to other URL fields
        image: normalizeImageUrl(getDoctorImageSource(doc)),

        priceDay: toNumber(doc?.video_day_rate, 0),
        priceNight: toNumber(doc?.video_night_rate, 0),

        rating: 4.6,
        reviews: 178,
        consultations: 120,

        specialties: normalizeSpecialties(specializationData.list),
        specializationList: specializationData.list,
        specializationText: specializationData.text,
        responseDay: doc?.response_time_for_online_consults_day || "",
        responseNight: doc?.response_time_for_online_consults_night || "",
        breakTimes,
        followUp: doc?.do_you_offer_a_free_follow_up_within_3_days_after_a_consulta || "",
        bio: doc?.bio || "",
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

const InfoRow = ({ icon: Icon, label, value, subValue }) => (
  <div className="flex items-start gap-3">
    <div className="mt-[2px] inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-teal-50 text-teal-700 border border-teal-100">
      <Icon size={18} />
    </div>
    <div className="min-w-0">
      <div className="text-[11px] font-semibold text-slate-500">{label}</div>
      <div className="text-sm font-semibold text-slate-900 leading-5 break-words">
        {value || "Not available"}
        {subValue ? (
          <span className="text-slate-400 font-semibold"> {" • "} {subValue}</span>
        ) : null}
      </div>
    </div>
  </div>
);

/* ---------------- Screen ---------------- */

const VetsScreen = ({ petDetails, onSelect, onBack }) => {
  const [vets, setVets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [errMsg, setErrMsg] = useState("");
  const [activeBioVet, setActiveBioVet] = useState(null);
  const [brokenImages, setBrokenImages] = useState(() => new Set());

  const markImageBroken = (id) => {
    if (!id) return;
    setBrokenImages((prev) => {
      const next = new Set(prev);
      next.add(id);
      return next;
    });
  };

  useEffect(() => {
    let ignore = false;

    const load = async () => {
      setLoading(true);
      setErrMsg("");
      try {
        const res = await fetch(API_URL);
        const json = await res.json();
        if (!ignore) {
          if (json?.success && Array.isArray(json?.data)) {
            setVets(buildVetsFromApi(json.data));
          } else {
            setVets([]);
            setErrMsg("Could not load vets right now.");
          }
        }
      } catch {
        if (!ignore) {
          setVets([]);
          setErrMsg("Network error while loading vets.");
        }
      } finally {
        if (!ignore) setLoading(false);
      }
    };

    load();
    return () => {
      ignore = true;
    };
  }, []);

  const sortedVets = useMemo(() => {
    const base = [...vets];
    const specialtyScore = (v) =>
      petDetails?.type && v.specialties?.includes(petDetails.type) ? 1 : 0;

    return base.sort((a, b) => {
      const aMatch = specialtyScore(a);
      const bMatch = specialtyScore(b);
      if (aMatch && !bMatch) return -1;
      if (!aMatch && bMatch) return 1;
      return (a.priceDay || 0) - (b.priceDay || 0);
    });
  }, [vets, petDetails]);

  return (
    <div className="min-h-screen bg-slate-50 flex flex-col">
      <Header onBack={onBack} title="Available Vets" />

      <div className="flex-1 px-4 py-6 pb-20 overflow-y-auto no-scrollbar md:px-10 lg:px-16 md:py-10">
        <ProgressBar current={2} steps={PET_FLOW_STEPS} />

        <div className="mt-6 md:mt-10">
          <div className="flex items-start justify-between gap-4">
            <div className="min-w-0">
              <h2 className="text-2xl md:text-4xl font-extrabold tracking-tight text-slate-900">
                {petDetails?.name ? `Vets for ${petDetails.name}` : "Available Vets"}
              </h2>
              <p className="mt-2 text-sm md:text-base text-slate-500 max-w-3xl">
                Choose a vet based on your pet and consult price.
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
          </div>
        ) : sortedVets.length === 0 ? (
          <div className="mt-8 rounded-3xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm">
            <div className="text-slate-900 font-bold">No vets found.</div>
            <div className="text-slate-500 text-sm mt-2">Please try again later.</div>
          </div>
        ) : (
          <div className="mt-8 grid gap-4 md:grid-cols-2 md:gap-6 lg:grid-cols-3">
            {sortedVets.map((vet) => {
              const isSpecialist =
                petDetails?.type && vet.specialties?.includes(petDetails.type);

              const showDayPrice = isDayTime();
              const priceValue = showDayPrice ? vet.priceDay : vet.priceNight;
              const price = formatPrice(priceValue);

              // ✅ IMPORTANT: image will be like https://snoutiq.com/backend/photo/...
              const showImage = Boolean(vet.image) && !brokenImages.has(vet.id);
              const initials = getInitials(vet.name);

              const bioPreview = clipText(vet.bio, 170);

              return (
                <div
                  key={vet.id}
                  className="rounded-3xl border border-slate-200 bg-white shadow-sm hover:shadow-lg transition-all overflow-hidden"
                >
                  <div className="h-1 bg-gradient-to-r from-teal-500 to-emerald-500" />

                  <div className="p-5">
                    <div className="flex gap-4">
                      {showImage ? (
                        <img
                          src={vet.image}
                          alt={vet.name}
                          loading="lazy"
                          crossOrigin="anonymous"
                          onError={() => markImageBroken(vet.id)}
                          className="h-16 w-16 rounded-2xl object-cover border border-slate-200 bg-slate-50"
                        />
                      ) : (
                        <div className="h-16 w-16 rounded-2xl bg-amber-400 text-white flex items-center justify-center text-xl font-extrabold shadow-sm">
                          {initials}
                        </div>
                      )}

                      <div className="flex-1 min-w-0">
                        <div className="flex items-start justify-between gap-2">
                          <div className="min-w-0">
                            <h3 className="truncate text-base md:text-lg font-extrabold text-slate-900">
                              {vet.name}
                            </h3>

                            <p className="mt-0.5 text-xs md:text-sm text-slate-500 truncate">
                              {vet.clinicName}
                            </p>

                            {isSpecialist ? (
                              <p className="mt-1 text-[12px] font-semibold text-teal-600">
                                {petDetails?.type === "exotic"
                                  ? "Exotic care"
                                  : `${petDetails?.type} care`}
                              </p>
                            ) : (
                              <p className="mt-1 text-[12px] font-semibold text-slate-400">
                                Online consultation
                              </p>
                            )}
                          </div>

                          <div className="shrink-0 text-right">
                            <div className="inline-flex items-center gap-1 text-slate-700">
                              <Star size={16} className="text-amber-500" />
                              <span className="text-sm font-extrabold">
                                {Number(vet.rating).toFixed(1)}
                              </span>
                            </div>
                            <div className="text-[11px] text-slate-500">
                              ({vet.reviews} reviews)
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
                        subValue={`${vet.experience || 0} years exp.`}
                      />

                      <InfoRow
                        icon={Stethoscope}
                        label="Specialization"
                        value={vet.specializationText || "Not available"}
                      />

                      <InfoRow
                        icon={BadgeCheck}
                        label="Successful consultations"
                        value={`${vet.consultations}+`}
                      />
                    </div>

                    {bioPreview ? (
                      <div className="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div className="text-[11px] uppercase tracking-wide text-slate-400 font-semibold">
                          About
                        </div>
                        <p className="mt-1 text-sm text-slate-700 leading-6 line-clamp-3">
                          {bioPreview}
                        </p>
                      </div>
                    ) : null}

                    <div className="mt-4 flex items-center justify-between gap-3">
                      <button
                        type="button"
                        onClick={() => setActiveBioVet(vet)}
                        className="text-sm font-semibold text-teal-700 hover:text-teal-800 inline-flex items-center gap-1"
                      >
                        View full profile <ChevronRight size={16} />
                      </button>

                      <Button
                        onClick={() => onSelect(vet)}
                        className="h-11 px-5 rounded-2xl bg-teal-600 hover:bg-teal-700 shadow-sm text-sm inline-flex items-center gap-3"
                      >
                        <span className="font-semibold">Consult Now</span>
                        {price ? (
                          <span className="ml-1 rounded-xl bg-white/15 px-3 py-1 text-[13px] font-extrabold">
                            {price}
                          </span>
                        ) : (
                          <span className="ml-1 rounded-xl bg-white/15 px-3 py-1 text-[12px] font-bold">
                            Ask
                          </span>
                        )}
                      </Button>
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
              <div className="min-w-0">
                <p className="text-[11px] uppercase tracking-wider text-slate-400">
                  Vet Profile
                </p>
                <h3 className="truncate text-lg font-extrabold text-slate-900 md:text-xl">
                  {activeBioVet.name}
                </h3>
                <p className="mt-0.5 truncate text-xs text-slate-500">
                  {activeBioVet.clinicName || "Clinic"}
                </p>
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
                      <span className="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 border border-slate-200">
                        {activeBioVet.qualification || "Not available"}
                      </span>
                      <span className="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 border border-slate-200">
                        {activeBioVet.experience || 0} yrs exp
                      </span>
                      <span className="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 border border-slate-200">
                        License: {activeBioVet.raw?.doctor_license || "N/A"}
                      </span>
                    </div>

                    <div className="mt-4 grid gap-3 sm:grid-cols-2">
                      <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <div className="text-[11px] uppercase tracking-wide text-slate-400">
                          Follow-up
                        </div>
                        <div className="mt-1 text-sm font-semibold text-slate-800">
                          {activeBioVet.followUp || "Not available"}
                        </div>
                      </div>

                      <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <div className="text-[11px] uppercase tracking-wide text-slate-400">
                          Break time
                        </div>
                        <div className="mt-1 text-sm font-semibold text-slate-800">
                          {activeBioVet.breakTimes?.length
                            ? activeBioVet.breakTimes.join(", ")
                            : "No break time"}
                        </div>
                      </div>
                    </div>

                    <div className="mt-4 grid gap-3 sm:grid-cols-2">
                      <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <div className="text-[11px] uppercase tracking-wide text-slate-400">
                          Day consult (8 AM - 8 PM)
                        </div>
                        <div className="mt-1 text-lg font-extrabold text-slate-900">
                          {formatPrice(activeBioVet.priceDay) || "Price on request"}
                        </div>
                        <div className="mt-1 text-xs text-slate-500">
                          Response:{" "}
                          <span className="font-semibold text-slate-800">
                            {activeBioVet.responseDay || "Not available"}
                          </span>
                        </div>
                      </div>

                      <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <div className="text-[11px] uppercase tracking-wide text-slate-400">
                          Night consult (8 PM - 8 AM)
                        </div>
                        <div className="mt-1 text-lg font-extrabold text-slate-900">
                          {formatPrice(activeBioVet.priceNight) || "Price on request"}
                        </div>
                        <div className="mt-1 text-xs text-slate-500">
                          Response:{" "}
                          <span className="font-semibold text-slate-800">
                            {activeBioVet.responseNight || "Not available"}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div className="mt-5 grid gap-4 md:grid-cols-5">
                  <div className="md:col-span-3 rounded-3xl border border-slate-200 bg-white p-5 md:p-6">
                    <div className="text-[11px] uppercase tracking-wider text-slate-400">
                      About
                    </div>
                    <div className="mt-1 text-base font-extrabold text-slate-900">
                      Doctor Bio
                    </div>

                    <div className="mt-4 text-sm leading-6 text-slate-700 whitespace-pre-line">
                      {activeBioVet.bio?.trim()
                        ? activeBioVet.bio.trim()
                        : "Bio not available yet."}
                    </div>
                  </div>

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
                      ) : activeBioVet.specializationText ? (
                        <div className="text-slate-700">
                          {activeBioVet.specializationText}
                        </div>
                      ) : (
                        <div className="text-slate-500">Not available</div>
                      )}
                    </div>
                  </div>
                </div>

                <div className="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                  <Button onClick={() => setActiveBioVet(null)} className="px-6">
                    Close
                  </Button>

                  <Button
                    onClick={() => {
                      const v = activeBioVet;
                      setActiveBioVet(null);
                      onSelect?.(v);
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
