import React, { useEffect, useMemo, useState } from "react";
import { Button } from "../components/Button";
import { Header, ProgressBar } from "../components/Sharedcomponents";
import { Clock, Star, Zap } from "lucide-react";

const API_URL = "https://snoutiq.com/backend/api/exported_from_excell_doctors";

const FALLBACK_AVATAR =
  "https://ui-avatars.com/api/?name=Vet&background=E5E7EB&color=111827&size=128";

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

const buildVetsFromApi = (apiData = []) => {
  const list = [];

  apiData.forEach((clinic) => {
    const clinicName = clinic?.name || "Clinic";

    (clinic?.doctors || []).forEach((doc) => {
      const specializationList = parseListField(
        doc?.specialization_select_all_that_apply
      );
      const breakTimes = parseListField(
        doc?.break_do_not_disturb_time_example_2_4_pm
      );

      list.push({
        id: doc?.id,
        clinicName,

        name: doc?.doctor_name || "Vet",
        qualification: doc?.degree || "Vet",
        experience: toNumber(doc?.years_of_experience, 0),

        image: doc?.doctor_image || FALLBACK_AVATAR,

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

const VetsScreen = ({ petDetails, onSelect, onBack }) => {
  const [vets, setVets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [errMsg, setErrMsg] = useState("");

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

  // ✅ No filters/sort UI — but we can still keep a stable order (lowest price first)
  const sortedVets = useMemo(() => {
    const base = [...vets];

    const specialtyScore = (v) =>
      petDetails?.type && v.specialties?.includes(petDetails.type) ? 1 : 0;

    // Specialist first, then lowest price (simple + clean)
    return base.sort((a, b) => {
      const aMatch = specialtyScore(a);
      const bMatch = specialtyScore(b);
      if (aMatch && !bMatch) return -1;
      if (!aMatch && bMatch) return 1;
      return (a.priceDay || 0) - (b.priceDay || 0);
    });
  }, [vets, petDetails]);

  return (
    <div className="min-h-screen bg-calm-bg flex flex-col animate-slide-up md:bg-gradient-to-b md:from-calm-bg md:to-white">
      <Header onBack={onBack} title="Available Vets" />

      <div className="w-full">
        <div className="flex-1 px-4 py-6 pb-20 overflow-y-auto no-scrollbar md:px-10 lg:px-16 md:py-12">
          <ProgressBar current={2} total={3} />

          {/* Context Header */}
          <div className="mb-4 flex flex-col gap-2 mt-6 md:mt-10">
            <div className="flex items-start md:items-end justify-between gap-4">
              <div className="min-w-0">
                <h2 className="text-lg font-bold text-stone-800 md:text-4xl md:leading-[1.15]">
                  {petDetails?.name
                    ? `Vets for ${petDetails.name}`
                    : "Available Vets"}
                </h2>
                <p className="hidden md:block text-base text-stone-500 mt-2 max-w-3xl">
                  Choose a vet based on specialty match and consult price.
                </p>
              </div>

              <div className="flex items-center gap-2 shrink-0">
                <div className="flex items-center gap-1 text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg md:px-3 md:py-2 md:text-sm md:rounded-xl">
                  <Zap size={12} fill="currentColor" className="md:hidden" />
                  <Zap size={16} fill="currentColor" className="hidden md:block" />
                  <span>Fast response</span>
                </div>
              </div>
            </div>

            <div className="flex items-center gap-2 text-sm text-stone-500 bg-stone-100 p-2 rounded-lg px-3 md:text-base md:px-4 md:py-3 md:rounded-2xl">
              <Clock size={14} className="md:hidden" />
              <Clock size={18} className="hidden md:block" />
              <span>
                Average response time: <strong>8 mins</strong>
              </span>
            </div>
          </div>

          {/* Loading / Error */}
          {loading ? (
            <div className="bg-white rounded-2xl p-4 shadow-sm border border-stone-100 md:p-8 md:rounded-3xl">
              <div className="text-stone-700 font-semibold">Loading vets...</div>
              <div className="text-stone-500 text-sm mt-1">Please wait a moment.</div>
            </div>
          ) : errMsg ? (
            <div className="bg-white rounded-2xl p-4 shadow-sm border border-stone-100 md:p-8 md:rounded-3xl">
              <div className="text-red-600 font-semibold">{errMsg}</div>
              <div className="text-stone-500 text-sm mt-2">Try again or check network.</div>
            </div>
          ) : sortedVets.length === 0 ? (
            <div className="bg-white rounded-2xl p-4 shadow-sm border border-stone-100 md:p-8 md:rounded-3xl">
              <div className="text-stone-700 font-semibold">No vets found.</div>
              <div className="text-stone-500 text-sm mt-1">Please try again later.</div>
            </div>
          ) : (
            <div className="space-y-4 md:space-y-0 md:grid md:grid-cols-2 md:gap-8 lg:grid-cols-3">
              {sortedVets.map((vet) => {
                const isSpecialist =
                  petDetails?.type && vet.specialties?.includes(petDetails.type);

                return (
                  <div
                    key={vet.id}
                    className="bg-white p-4 rounded-2xl shadow-sm flex flex-col gap-4 relative overflow-hidden transition-all border border-stone-100 hover:shadow-md md:p-7 md:rounded-3xl"
                  >
                    {/* Online Indicator (same as before) */}
                    <div className="absolute top-4 right-4 bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2 py-1 rounded-full flex items-center gap-1 md:top-6 md:right-6 md:text-xs md:px-3 md:py-1.5">
                      <div className="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                      ~15m
                    </div>

                    <div className="flex gap-4 mt-4 md:mt-10">
                      <img
                        src={vet.image}
                        alt={vet.name}
                        className="w-16 h-16 rounded-full object-cover border-2 border-stone-50 md:w-16 md:h-16 lg:w-18 lg:h-18"
                      />

                      <div className="flex-1 min-w-0">
                        <h3 className="font-bold text-stone-800 text-lg flex items-center gap-2 md:text-xl">
                          <span className="truncate">{vet.name}</span>

                          {isSpecialist && (
                            <span className="shrink-0 text-[10px] bg-stone-100 text-stone-500 px-1.5 py-0.5 rounded font-medium border border-stone-200 md:text-xs md:px-2 md:py-1 md:rounded-lg">
                              {petDetails?.type === "exotic"
                                ? "Exotic Specialist"
                                : `${petDetails?.type} Specialist`}
                            </span>
                          )}
                        </h3>

                        <p className="text-xs text-stone-500 mb-1 md:text-sm">
                          {vet.qualification} • {vet.experience} yrs exp
                        </p>

                        <div className="flex gap-2 items-center mb-2">
                          <div className="flex items-center gap-1 bg-amber-50 px-1.5 py-0.5 rounded text-amber-700 text-xs font-bold md:text-sm md:px-2 md:py-1 md:rounded-lg">
                            <Star size={10} fill="currentColor" className="md:hidden" />
                            <Star size={14} fill="currentColor" className="hidden md:block" />
                            {vet.rating}
                          </div>
                          <span className="text-xs text-stone-400 md:text-sm">
                            {vet.consultations}+ consults
                          </span>
                        </div>

                        {vet.specializationList?.length ? (
                          <div className="flex flex-wrap gap-1.5 mb-2">
                            {vet.specializationList.slice(0, 4).map((spec, idx) => (
                              <span
                                key={`${spec}-${idx}`}
                                className="text-[10px] md:text-xs bg-stone-100 text-stone-600 px-2 py-0.5 rounded-full border border-stone-200"
                              >
                                {spec}
                              </span>
                            ))}
                            {vet.specializationList.length > 4 ? (
                              <span className="text-[10px] md:text-xs text-stone-400">
                                +{vet.specializationList.length - 4}
                              </span>
                            ) : null}
                          </div>
                        ) : null}

                        {vet.responseDay || vet.responseNight || vet.breakTimes?.length ? (
                          <div className="space-y-1 text-[11px] text-stone-500 md:text-xs">
                            {vet.responseDay ? (
                              <div className="flex items-center gap-1">
                                <Clock size={12} />
                                <span>Day response: {vet.responseDay}</span>
                              </div>
                            ) : null}
                            {vet.responseNight ? (
                              <div className="flex items-center gap-1">
                                <Clock size={12} />
                                <span>Night response: {vet.responseNight}</span>
                              </div>
                            ) : null}
                            {vet.breakTimes?.length ? (
                              <div className="flex items-center gap-1">
                                <Clock size={12} />
                                <span>Break: {vet.breakTimes.join(", ")}</span>
                              </div>
                            ) : null}
                          </div>
                        ) : null}

                        <p className="hidden md:block text-xs text-stone-400">
                          {vet.clinicName}
                        </p>
                      </div>
                    </div>

                    <div className="border-t border-stone-100 pt-3 flex items-center justify-between md:pt-5">
                      <div className="flex flex-col">
                        <span className="text-lg font-bold text-stone-900 md:text-2xl">
                          ₹{vet.priceDay}
                        </span>
                        <span className="text-xs text-stone-400 md:text-sm">
                          day consult
                        </span>
                        {vet.priceNight > 0 ? (
                          <span className="text-xs text-stone-500 md:text-sm">
                            Night: Rs. {vet.priceNight}
                          </span>
                        ) : null}
                      </div>

                      <Button
                        onClick={() => onSelect(vet)}
                        className="py-2 px-6 text-sm md:py-3 md:px-8 md:text-base md:rounded-2xl bg-stone-800"
                      >
                        Consult Now
                      </Button>
                    </div>
                  </div>
                );
              })}
            </div>
          )}

          <div className="hidden md:block h-10" />
        </div>
      </div>
    </div>
  );
};

export default VetsScreen;
