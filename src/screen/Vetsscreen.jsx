import React, { useEffect, useMemo, useState } from "react";
import { Button } from "../components/Button";
import { Header, ProgressBar } from "../components/Sharedcomponents";
import { Clock, Zap, ChevronRight } from "lucide-react";

const API_URL = "https://snoutiq.com/backend/api/exported_from_excell_doctors";

const normalizeImageUrl = (value) => {
  if (!value) return "";
  const trimmed = String(value).trim();
  if (!trimmed) return "";
  const lower = trimmed.toLowerCase();
  if (lower === "null" || lower === "undefined") return "";
  return trimmed;
};

const getInitial = (name = "") => {
  const trimmed = String(name).trim();
  return trimmed ? trimmed[0].toUpperCase() : "V";
};

const parseListField = (value) => {
  if (!value) return [];
  if (Array.isArray(value)) {
    return value.map((item) => String(item).trim()).filter(Boolean);
  }
  if (typeof value === "string") {
    const trimmed = value.trim();
    if (!trimmed) return [];
    if (trimmed.startsWith("[") && trimmed.endsWith("]")) {
      try {
        const parsed = JSON.parse(trimmed);
        if (Array.isArray(parsed)) {
          return parsed.map((item) => String(item).trim()).filter(Boolean);
        }
      } catch {
        // fall through to comma split
      }
    }
    return trimmed.split(",").map((item) => item.trim()).filter(Boolean);
  }
  return [String(value).trim()].filter(Boolean);
};

const normalizeBreakTimes = (value) => {
  const list = parseListField(value);
  return list.filter((item) => {
    const cleaned = String(item).toLowerCase().replace(/[^a-z0-9]/g, "");
    if (!cleaned) return false;
    if (
      ["no", "none", "nil", "na", "na0", "n/a", "noany", "notavailable"].includes(
        cleaned
      )
    ) {
      return false;
    }
    if (cleaned.startsWith("no")) return false;
    return true;
  });
};

const normalizeSpecialties = (specializationText = "") => {
  const raw = parseListField(specializationText)
    .map((s) => s.toLowerCase())
    .filter(Boolean);

  const mapped = new Set();
  raw.forEach((t) => {
    if (t.includes("dog")) mapped.add("dog");
    if (t.includes("cat")) mapped.add("cat");
    if (
      t.includes("exotic") ||
      t.includes("bird") ||
      t.includes("rabbit") ||
      t.includes("turtle")
    )
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
  if (!Number.isFinite(amount) || amount <= 0) return "Price on request";
  return `Rs. ${amount}`;
};

const formatList = (list = []) => {
  if (!Array.isArray(list) || list.length === 0) return "Not available";
  return list.join(", ");
};

const buildVetsFromApi = (apiData = []) => {
  const list = [];

  apiData.forEach((clinic) => {
    const clinicName = clinic?.name || "Clinic";

    (clinic?.doctors || []).forEach((doc) => {
      const specializationList = parseListField(
        doc?.specialization_select_all_that_apply
      );
      const breakTimes = normalizeBreakTimes(
        doc?.break_do_not_disturb_time_example_2_4_pm
      );

      list.push({
        id: doc?.id,
        clinicName,

        name: doc?.doctor_name || "Vet",
        qualification: doc?.degree || "Vet",
        experience: toNumber(doc?.years_of_experience, 0),

        image: normalizeImageUrl(doc?.doctor_image),

        priceDay: toNumber(doc?.video_day_rate, 0),
        priceNight: toNumber(doc?.video_night_rate, 0),

        // Not in API -> defaults
        rating: 4.6,
        consultations: 120,

        specialties: normalizeSpecialties(specializationList),
        specializationList,
        responseDay: doc?.response_time_for_online_consults_day || "",
        responseNight: doc?.response_time_for_online_consults_night || "",
        breakTimes,
        followUp:
          doc?.do_you_offer_a_free_follow_up_within_3_days_after_a_consulta ||
          "",

        raw: doc,
      });
    });
  });

  return list;
};

/** ---------- UI helpers (only styling) ---------- */

const SkeletonCard = () => (
  <div className="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div className="h-1 bg-gradient-to-r from-blue-600 to-blue-500" />
    <div className="p-5 md:p-7">
      <div className="flex items-start gap-4">
        <div className="h-16 w-16 rounded-full bg-slate-100 animate-pulse" />
        <div className="flex-1">
          <div className="h-4 w-2/3 bg-slate-100 rounded animate-pulse" />
          <div className="mt-2 h-3 w-1/2 bg-slate-100 rounded animate-pulse" />
          <div className="mt-3 h-3 w-3/4 bg-slate-100 rounded animate-pulse" />
        </div>
      </div>

      <div className="mt-6 rounded-2xl border border-slate-100 bg-slate-50 p-4 flex items-center justify-between">
        <div className="space-y-2">
          <div className="h-5 w-20 bg-slate-100 rounded animate-pulse" />
          <div className="h-3 w-28 bg-slate-100 rounded animate-pulse" />
        </div>
        <div className="h-10 w-32 bg-slate-100 rounded-2xl animate-pulse" />
      </div>
    </div>
  </div>
);

const Pill = ({ children }) => (
  <span className="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-600 md:text-xs">
    {children}
  </span>
);

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
        const res = await fetch(API_URL, { method: "GET" });
        const json = await res.json();

        if (!ignore) {
          if (json?.success && Array.isArray(json?.data)) {
            setVets(buildVetsFromApi(json.data));
          } else {
            setVets([]);
            setErrMsg("Could not load vets right now.");
          }
        }
      } catch (e) {
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

      {/* top spacing + container */}
      <div className="flex-1 px-4 py-6 pb-20 overflow-y-auto no-scrollbar md:px-10 lg:px-16 md:py-10">
        <ProgressBar current={2} total={3} />

        {/* Title block */}
        <div className="mt-6 md:mt-10">
          <div className="flex items-start justify-between gap-4">
            <div className="min-w-0">
              <h2 className="text-2xl md:text-4xl font-extrabold tracking-tight text-slate-900">
                {petDetails?.name ? `Vets for ${petDetails.name}` : "Available Vets"}
              </h2>
              <p className="mt-2 text-sm md:text-base text-slate-500 max-w-3xl">
                Choose a vet based on specialty match and consult price.
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

        {/* States */}
        {loading ? (
          <div className="mt-8 grid gap-4 md:grid-cols-2 md:gap-6 lg:grid-cols-3">
            {Array.from({ length: 6 }).map((_, i) => (
              <SkeletonCard key={i} />
            ))}
          </div>
        ) : errMsg ? (
          <div className="mt-8 rounded-3xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm">
            <div className="text-red-600 font-bold">{errMsg}</div>
            <div className="text-slate-500 text-sm mt-2">
              Try again or check network.
            </div>
          </div>
        ) : sortedVets.length === 0 ? (
          <div className="mt-8 rounded-3xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm">
            <div className="text-slate-900 font-bold">No vets found.</div>
            <div className="text-slate-500 text-sm mt-2">
              Please try again later.
            </div>
          </div>
        ) : (
          <div className="mt-8 grid gap-4 md:grid-cols-2 md:gap-6 lg:grid-cols-3">
            {sortedVets.map((vet) => {
              const isSpecialist =
                petDetails?.type && vet.specialties?.includes(petDetails.type);

              const showDayPrice = isDayTime();
              const priceLabel = showDayPrice
                ? "Day consult (8 AM - 8 PM)"
                : "Night consult (8 PM - 8 AM)";
              const priceValue = showDayPrice ? vet.priceDay : vet.priceNight;

              const showImage = Boolean(vet.image) && !brokenImages.has(vet.id);
              const initials = getInitial(vet.name);

              return (
                <div
                  key={vet.id}
                  className="relative bg-white rounded-3xl border border-slate-200 shadow-sm hover:shadow-lg transition-all overflow-hidden"
                >
                  <div className="h-[3px] bg-gradient-to-r from-blue-600 to-blue-500" />

                  <div className="p-5 md:p-6 flex flex-col gap-4 min-h-[220px]">
                    <div className="flex items-start gap-4">
                      {showImage ? (
                        <img
                          src={vet.image}
                          alt={vet.name}
                          onError={() => markImageBroken(vet.id)}
                          className="w-14 h-14 md:w-16 md:h-16 rounded-2xl object-cover border border-slate-200 bg-slate-50"
                        />
                      ) : (
                        <div className="w-14 h-14 md:w-16 md:h-16 rounded-2xl bg-gradient-to-br from-blue-600 to-blue-500 text-white flex items-center justify-center text-lg md:text-xl font-extrabold">
                          {initials}
                        </div>
                      )}

                      <div className="flex-1 min-w-0">
                        <div className="flex items-start justify-between gap-3">
                          <div className="min-w-0">
                            <h3 className="text-base md:text-lg font-extrabold text-slate-900 truncate">
                              {vet.name}
                            </h3>
                            <p className="mt-0.5 text-xs md:text-sm text-slate-600">
                              <span className="font-semibold">{vet.qualification}</span>
                              <span className="text-slate-300 mx-1">*</span>
                              {vet.experience} yrs exp
                            </p>
                          </div>

                          {isSpecialist ? (
                            <span className="shrink-0 whitespace-nowrap rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] md:text-xs font-semibold text-slate-700">
                              {petDetails?.type === "exotic"
                                ? "Exotic Specialist"
                                : `${petDetails?.type} Specialist`}
                            </span>
                          ) : null}
                        </div>

                        <div className="mt-2 flex items-start justify-between gap-3">
                          <p className="text-xs md:text-sm text-slate-500 leading-5 line-clamp-2">
                            {vet.clinicName}
                          </p>

                          <button
                            type="button"
                            onClick={() => setActiveBioVet(vet)}
                            className="shrink-0 whitespace-nowrap text-xs md:text-sm font-semibold text-blue-600 hover:text-blue-700 inline-flex items-center gap-1"
                          >
                            View bio <span className="text-blue-400">{">"}</span>
                          </button>
                        </div>
                      </div>
                    </div>

                    <div className="mt-auto">
                      <div className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 flex items-center justify-between gap-3">
                        <div className="min-w-0 flex-1">
                          <div className="text-xl md:text-2xl font-extrabold text-slate-900 leading-none">
                            {formatPrice(priceValue)}
                          </div>
                          <div className="mt-1 text-[11px] md:text-sm text-slate-500">
                            {priceLabel}
                          </div>
                        </div>

                        <Button
                          onClick={() => onSelect(vet)}
                          className="whitespace-nowrap shrink-0 h-9 md:h-10 px-4 md:px-5 text-xs md:text-sm rounded-xl bg-blue-600 hover:bg-blue-700 shadow-sm"
                        >
                          Consult Now
                        </Button>
                      </div>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        )}

        <div className="hidden md:block h-10" />
      </div>

      {/* Bio Modal */}
      {activeBioVet ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="w-full max-w-3xl overflow-hidden rounded-3xl bg-white shadow-2xl border border-slate-200">
            <div className="flex items-center justify-between border-b border-slate-200 px-6 py-4">
              <div>
                <p className="text-xs uppercase tracking-wide text-slate-400">
                  Doctor Bio
                </p>
                <h3 className="text-lg font-extrabold text-slate-900 md:text-xl">
                  {activeBioVet.name}
                </h3>
              </div>
              <button
                type="button"
                onClick={() => setActiveBioVet(null)}
                className="rounded-full bg-slate-100 px-4 py-2 text-xs md:text-sm font-semibold text-slate-700 hover:bg-slate-200"
              >
                Close
              </button>
            </div>

            <div className="max-h-[80vh] overflow-y-auto p-6 md:p-8">
              <div className="flex flex-col gap-6 md:flex-row md:items-start">
                {activeBioVet?.image && !brokenImages.has(activeBioVet.id) ? (
                  <img
                    src={activeBioVet.image}
                    alt={activeBioVet.name}
                    onError={() => markImageBroken(activeBioVet.id)}
                    className="h-24 w-24 rounded-2xl object-cover border border-slate-200 bg-slate-50"
                  />
                ) : (
                  <div className="h-24 w-24 rounded-2xl bg-gradient-to-br from-blue-600 to-blue-500 text-white flex items-center justify-center text-2xl font-extrabold">
                    {getInitial(activeBioVet?.name)}
                  </div>
                )}

                <div className="flex-1 space-y-4">
                  <div>
                    <div className="text-xs uppercase tracking-wide text-slate-400">
                      Clinic
                    </div>
                    <div className="text-sm font-bold text-slate-900">
                      {activeBioVet.clinicName || "Clinic"}
                    </div>
                  </div>

                  <div className="grid gap-4 sm:grid-cols-2">
                    <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                      <div className="text-xs uppercase tracking-wide text-slate-400">
                        Qualification
                      </div>
                      <div className="mt-1 text-sm text-slate-800 font-semibold">
                        {activeBioVet.qualification || "Not available"}
                      </div>
                    </div>

                    <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                      <div className="text-xs uppercase tracking-wide text-slate-400">
                        Experience
                      </div>
                      <div className="mt-1 text-sm text-slate-800 font-semibold">
                        {activeBioVet.experience || 0} years
                      </div>
                    </div>

                    <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                      <div className="text-xs uppercase tracking-wide text-slate-400">
                        License
                      </div>
                      <div className="mt-1 text-sm text-slate-700">
                        {activeBioVet.raw?.doctor_license || "Not available"}
                      </div>
                    </div>

                    <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                      <div className="text-xs uppercase tracking-wide text-slate-400">
                        Follow-up
                      </div>
                      <div className="mt-1 text-sm text-slate-700">
                        {activeBioVet.followUp || "Not available"}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div className="mt-6 grid gap-4 md:grid-cols-2">
                <div className="rounded-2xl border border-slate-200 bg-white p-5">
                  <div className="text-xs uppercase tracking-wide text-slate-400">
                    Specializations
                  </div>
                  <div className="mt-2 text-sm text-slate-700">
                    {formatList(activeBioVet.specializationList)}
                  </div>
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-5">
                  <div className="text-xs uppercase tracking-wide text-slate-400">
                    Availability
                  </div>
                  <div className="mt-2 space-y-1 text-sm text-slate-700">
                    <div>
                      Day response:{" "}
                      <span className="font-semibold text-slate-900">
                        {activeBioVet.responseDay || "Not available"}
                      </span>
                    </div>
                    <div>
                      Night response:{" "}
                      <span className="font-semibold text-slate-900">
                        {activeBioVet.responseNight || "Not available"}
                      </span>
                    </div>
                    <div>
                      Break:{" "}
                      <span className="font-semibold text-slate-900">
                        {activeBioVet.breakTimes?.length
                          ? activeBioVet.breakTimes.join(", ")
                          : "No break time"}
                      </span>
                    </div>
                  </div>
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-5 md:col-span-2">
                  <div className="text-xs uppercase tracking-wide text-slate-400">
                    Consult Fees
                  </div>
                  <div className="mt-2 grid gap-2 sm:grid-cols-2 text-sm text-slate-700">
                    <div className="rounded-xl bg-slate-50 border border-slate-200 p-4">
                      Day (8 AM - 8 PM):{" "}
                      <span className="font-extrabold text-slate-900">
                        {formatPrice(activeBioVet.priceDay)}
                      </span>
                    </div>
                    <div className="rounded-xl bg-slate-50 border border-slate-200 p-4">
                      Night (8 PM - 8 AM):{" "}
                      <span className="font-extrabold text-slate-900">
                        {formatPrice(activeBioVet.priceNight)}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <div className="mt-6 flex justify-end gap-3">
                <Button onClick={() => setActiveBioVet(null)} className="px-6">
                  Close
                </Button>
              </div>
            </div>
          </div>
        </div>
      ) : null}
    </div>
  );
};

export default VetsScreen;
