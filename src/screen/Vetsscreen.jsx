import React, { useEffect, useMemo, useState } from "react";
import { Button } from "../components/Button";
import { Header, ProgressBar } from "../components/Sharedcomponents";
import { Clock, Zap } from "lucide-react";

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
  const [activeBioVet, setActiveBioVet] = useState(null);

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
                const showDayPrice = isDayTime();
                const priceLabel = showDayPrice
                  ? "Day consult (8 AM - 8 PM)"
                  : "Night consult (8 PM - 8 AM)";
                const priceValue = showDayPrice ? vet.priceDay : vet.priceNight;

                return (
                  <div
                    key={vet.id}
                    className="bg-white p-4 rounded-2xl shadow-sm flex flex-col gap-4 relative overflow-hidden transition-all border border-stone-100 hover:shadow-md md:p-7 md:rounded-3xl"
                  >
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

                        <div className="flex items-center justify-between">
                          <p className="text-xs text-stone-400 md:text-sm">
                            {vet.clinicName}
                          </p>
                          <button
                            type="button"
                            onClick={() => setActiveBioVet(vet)}
                            className="text-xs font-semibold text-[#3998de] hover:underline md:text-sm"
                          >
                            View bio
                          </button>
                        </div>
                      </div>
                    </div>

                    <div className="border-t border-stone-100 pt-3 flex items-center justify-between md:pt-5">
                      <div className="flex flex-col">
                        <span className="text-lg font-bold text-stone-900 md:text-2xl">
                          {formatPrice(priceValue)}
                        </span>
                        <span className="text-xs text-stone-400 md:text-sm">
                          {priceLabel}
                        </span>
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

      {activeBioVet ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="w-full max-w-3xl overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div className="flex items-center justify-between border-b border-stone-100 px-6 py-4">
              <div>
                <p className="text-xs uppercase text-stone-400">Doctor Bio</p>
                <h3 className="text-lg font-bold text-stone-800 md:text-xl">
                  {activeBioVet.name}
                </h3>
              </div>
              <button
                type="button"
                onClick={() => setActiveBioVet(null)}
                className="rounded-full bg-stone-100 px-4 py-2 text-xs font-semibold text-stone-600 hover:bg-stone-200 md:text-sm"
              >
                Close
              </button>
            </div>

            <div className="max-h-[80vh] overflow-y-auto p-6 md:p-8">
              <div className="flex flex-col gap-6 md:flex-row md:items-start">
                <img
                  src={activeBioVet.image}
                  alt={activeBioVet.name}
                  className="h-24 w-24 rounded-2xl object-cover border border-stone-100"
                />
                <div className="flex-1 space-y-3">
                  <div>
                    <div className="text-xs uppercase text-stone-400">Clinic</div>
                    <div className="text-sm font-semibold text-stone-800">
                      {activeBioVet.clinicName || "Clinic"}
                    </div>
                  </div>
                  <div className="grid gap-3 sm:grid-cols-2">
                    <div>
                      <div className="text-xs uppercase text-stone-400">
                        Qualification
                      </div>
                      <div className="text-sm text-stone-700">
                        {activeBioVet.qualification || "Not available"}
                      </div>
                    </div>
                    <div>
                      <div className="text-xs uppercase text-stone-400">
                        Experience
                      </div>
                      <div className="text-sm text-stone-700">
                        {activeBioVet.experience || 0} years
                      </div>
                    </div>
                    <div>
                      <div className="text-xs uppercase text-stone-400">License</div>
                      <div className="text-sm text-stone-700">
                        {activeBioVet.raw?.doctor_license || "Not available"}
                      </div>
                    </div>
                    <div>
                      <div className="text-xs uppercase text-stone-400">
                        Follow-up
                      </div>
                      <div className="text-sm text-stone-700">
                        {activeBioVet.followUp || "Not available"}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div className="mt-6 grid gap-4 md:grid-cols-2">
                <div className="rounded-2xl border border-stone-100 bg-stone-50 p-4">
                  <div className="text-xs uppercase text-stone-400">
                    Specializations
                  </div>
                  <div className="mt-2 text-sm text-stone-700">
                    {formatList(activeBioVet.specializationList)}
                  </div>
                </div>
                <div className="rounded-2xl border border-stone-100 bg-stone-50 p-4">
                  <div className="text-xs uppercase text-stone-400">Availability</div>
                  <div className="mt-2 space-y-1 text-sm text-stone-700">
                    <div>Day response: {activeBioVet.responseDay || "Not available"}</div>
                    <div>
                      Night response: {activeBioVet.responseNight || "Not available"}
                    </div>
                    <div>
                      Break:{" "}
                      {activeBioVet.breakTimes?.length
                        ? activeBioVet.breakTimes.join(", ")
                        : "No break time"}
                    </div>
                  </div>
                </div>
                <div className="rounded-2xl border border-stone-100 bg-stone-50 p-4">
                  <div className="text-xs uppercase text-stone-400">Consult Fees</div>
                  <div className="mt-2 space-y-1 text-sm text-stone-700">
                    <div>Day (8 AM - 8 PM): {formatPrice(activeBioVet.priceDay)}</div>
                    <div>
                      Night (8 PM - 8 AM): {formatPrice(activeBioVet.priceNight)}
                    </div>
                  </div>
                </div>
                {/* <div className="rounded-2xl border border-stone-100 bg-stone-50 p-4">
                  <div className="text-xs uppercase text-stone-400">Contact</div>
                  <div className="mt-2 space-y-1 text-sm text-stone-700">
                    <div>Email: {activeBioVet.raw?.doctor_email || "Not available"}</div>
                    <div>Phone: {activeBioVet.raw?.doctor_mobile || "Not available"}</div>
                  </div>
                </div> */}
              </div>

              <div className="mt-6 flex justify-end">
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
