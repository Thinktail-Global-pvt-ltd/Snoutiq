import React, { useEffect, useMemo, useState, lazy, Suspense } from "react";
import { useLocation, useNavigate } from "react-router-dom";

// Lazy screens (default exports)
const LandingScreen = lazy(() => import("../screen/Landingscreen"));
const PetDetailsScreen = lazy(() => import("../screen/Petdetailsscreen"));

// Payment has named exports
const PaymentScreen = lazy(() =>
  import("../screen/Paymentscreen").then((m) => ({ default: m.PaymentScreen }))
);
const ConfirmationScreen = lazy(() =>
  import("../screen/Paymentscreen").then((m) => ({
    default: m.ConfirmationScreen,
  }))
);

// Vet screens are named exports from ../components/VetScreens
const VetLoginScreen = lazy(() =>
  import("../components/VetScreens").then((m) => ({ default: m.VetLoginScreen }))
);
const VetRegisterScreen = lazy(() =>
  import("../components/VetScreens").then((m) => ({
    default: m.VetRegisterScreen,
  }))
);
const VetPendingScreen = lazy(() =>
  import("../components/VetScreens").then((m) => ({
    default: m.VetPendingScreen,
  }))
);
const VetDashboardScreen = lazy(() =>
  import("../components/VetScreens").then((m) => ({
    default: m.VetDashboardScreen,
  }))
);

// Small professional loader
const LoadingScreen = () => (
  <div className="min-h-screen w-full flex items-center justify-center bg-white">
    <div className="flex flex-col items-center gap-3">
      <div className="w-10 h-10 rounded-full border-4 border-stone-200 border-t-blue-600 animate-spin" />
      <p className="text-sm text-stone-500 font-medium">Loading...</p>
    </div>
  </div>
);

const CONSULT_VETS_API_PATH = "/backend/api/exported_from_excell_doctors";
const CONSULT_VETS_FALLBACK_ORIGIN = "https://snoutiq.com";
const DEFAULT_AUTO_ASSIGNED_VET = {
  id: 116,
  name: "Dr. Shashannk Goyal",
  doctor_name: "Dr Shashannk Goyal",
  qualification: "MVSc",
  experience: 10,
  specializationText:
    "Dogs, Cats, Exotic Pet, Surgery, Skin / Dermatology, General Practice, Endocrinology",
  specializationList: [
    "Dogs",
    "Cats",
    "Exotic Pet",
    "Surgery",
    "Skin / Dermatology",
    "General Practice",
    "Endocrinology",
  ],
  responseDay: "15 To 20 Mins",
  responseNight: "0 To 15 Mins",
  followUp: "Yes - free follow-up chat/call within 3 days",
  rating: 5,
  reviews: 0,
  priceDay: 499,
  priceNight: 649,
  bookingRateType: "day",
  bookingPrice: 499,
  isSnoutiqAssigned: true,
  autoAssigned: true,
  assignedBy: "snoutiq",
  raw: {
    id: 116,
    doctor_name: "Dr Shashannk Goyal",
    degree: "MVSc",
    years_of_experience: "10",
    video_day_rate: "499.00",
    video_night_rate: "649.00",
    specialization_select_all_that_apply:
      "Dogs, Cats, Exotic Pet, Surgery, Skin / Dermatology, General Practice, Endocrinology",
    response_time_for_online_consults_day: "15 To 20 Mins",
    response_time_for_online_consults_night: "0 To 15 Mins",
    do_you_offer_a_free_follow_up_within_3_days_after_a_consulta:
      "Yes - free follow-up chat/call within 3 days",
    average_review_points: 5,
    reviews_count: 0,
  },
};

const getDefaultAssignedVet = () => {
  const bookingRateType = isDayTime() ? "day" : "night";
  return {
    ...DEFAULT_AUTO_ASSIGNED_VET,
    bookingRateType,
    bookingPrice:
      bookingRateType === "day"
        ? DEFAULT_AUTO_ASSIGNED_VET.priceDay
        : DEFAULT_AUTO_ASSIGNED_VET.priceNight,
  };
};

const isDayTime = (date = new Date()) => {
  const hour = date.getHours();
  return hour >= 8 && hour < 20;
};

const sanitizeText = (value, fallback = "") => {
  if (value === undefined || value === null) return fallback;
  const text = String(value).trim();
  if (!text) return fallback;
  const lowered = text.toLowerCase();
  if (lowered === "null" || lowered === "undefined" || lowered === "[]") {
    return fallback;
  }
  return text;
};

const parseListField = (value) => {
  const text = sanitizeText(value);
  if (!text) return [];

  if (text.startsWith("[") && text.endsWith("]")) {
    try {
      const parsed = JSON.parse(text);
      if (Array.isArray(parsed)) {
        return parsed.map((item) => sanitizeText(item)).filter(Boolean);
      }
    } catch {
      // fall back to comma split
    }
  }

  return text
    .split(",")
    .map((item) => sanitizeText(item))
    .filter(Boolean);
};

const normalizeDoctorName = (value) => {
  const baseName = sanitizeText(value, "Selected Vet").replace(/\s+/g, " ");
  if (/^dr\.?\s/i.test(baseName)) {
    return baseName.replace(/^dr\.?\s*/i, "Dr. ");
  }
  return `Dr. ${baseName}`;
};

const normalizeImageUrl = (value) => {
  const raw = sanitizeText(value);
  if (!raw) return "";

  if (raw.includes("https://snoutiq.com/https://snoutiq.com/")) {
    return raw.replace(
      "https://snoutiq.com/https://snoutiq.com/",
      "https://snoutiq.com/"
    );
  }

  if (/^(https?:)?\/\//i.test(raw) || raw.startsWith("data:")) {
    return raw;
  }

  return `${CONSULT_VETS_FALLBACK_ORIGIN}/${raw.replace(/^\/+/, "")}`;
};

const getSafeOrigin = () => {
  if (typeof window === "undefined") return CONSULT_VETS_FALLBACK_ORIGIN;
  const origin = window.location.origin;
  if (origin.includes("localhost") || origin.includes("127.0.0.1")) {
    return CONSULT_VETS_FALLBACK_ORIGIN;
  }
  return origin;
};

const buildApiCandidates = () => {
  const origin = getSafeOrigin();
  return Array.from(
    new Set([
      `${origin}${CONSULT_VETS_API_PATH}`,
      `${CONSULT_VETS_FALLBACK_ORIGIN}${CONSULT_VETS_API_PATH}`,
      `https://www.snoutiq.com${CONSULT_VETS_API_PATH}`,
    ])
  );
};

const fetchJsonStrict = async (url, { timeoutMs = 15000 } = {}) => {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);

  try {
    const response = await fetch(url, {
      method: "GET",
      signal: controller.signal,
      cache: "no-store",
      headers: { Accept: "application/json" },
    });
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    return await response.json();
  } finally {
    clearTimeout(timer);
  }
};

const pickBestMatchVetFromApi = async () => {
  const candidates = buildApiCandidates();

  for (const url of candidates) {
    try {
      const payload = await fetchJsonStrict(url);
      if (!payload?.success || !Array.isArray(payload?.data)) continue;

      const vets = [];
      payload.data.forEach((clinic) => {
        (clinic?.doctors || []).forEach((doc) => {
          const priceDay = Number(doc?.video_day_rate);
          const priceNight = Number(doc?.video_night_rate);
          if (
            !Number.isFinite(priceDay) ||
            !Number.isFinite(priceNight) ||
            priceDay <= 2 ||
            priceNight <= 2
          ) {
            return;
          }

          const sourceImage =
            doc?.doctor_image_blob_url ||
            doc?.doctor_image_url ||
            doc?.doctor_image ||
            "";
          const specializationList = parseListField(
            doc?.specialization_select_all_that_apply
          );

          vets.push({
            id: doc?.id,
            name: normalizeDoctorName(doc?.doctor_name),
            doctor_name: sanitizeText(doc?.doctor_name),
            qualification: sanitizeText(doc?.degree),
            experience: Number(doc?.years_of_experience) || 0,
            image: normalizeImageUrl(sourceImage),
            priceDay,
            priceNight,
            responseDay: sanitizeText(doc?.response_time_for_online_consults_day),
            responseNight: sanitizeText(doc?.response_time_for_online_consults_night),
            followUp: sanitizeText(
              doc?.do_you_offer_a_free_follow_up_within_3_days_after_a_consulta
            ),
            rating:
              Number.isFinite(Number(doc?.average_review_points)) &&
              Number(doc?.average_review_points) > 0
                ? Number(doc?.average_review_points)
                : null,
            reviews: Number(doc?.reviews_count) || 0,
            specializationList,
            specializationText: specializationList.length
              ? specializationList.join(", ")
              : sanitizeText(doc?.specialization_select_all_that_apply),
            clinicName: sanitizeText(clinic?.name),
            isSnoutiqAssigned: true,
            autoAssigned: true,
            assignedBy: "snoutiq",
            raw: doc,
          });
        });
      });

      if (!vets.length) continue;

      const bestMatch =
        vets.find(
          (vet) =>
            /shash/i.test(String(vet?.name || "")) &&
            /goyal/i.test(String(vet?.name || ""))
        ) || vets[0];

      const bookingRateType = isDayTime() ? "day" : "night";
      const bookingPrice =
        bookingRateType === "day" ? bestMatch.priceDay : bestMatch.priceNight;

      return {
        ...bestMatch,
        bookingRateType,
        bookingPrice,
      };
    } catch {
      // Try next API URL.
    }
  }

  return {
    ...getDefaultAssignedVet(),
  };
};

const Home = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const shouldStartAtDetails = useMemo(() => {
    const params = new URLSearchParams(location.search || "");
    const fromQuery = String(params.get("start") || "").toLowerCase() === "details";
    const fromRoute = location.pathname === "/20+vetsonline";
    return fromQuery || fromRoute;
  }, [location.pathname, location.search]);

  const [screen, setScreen] = useState(() =>
    shouldStartAtDetails ? "details" : "landing"
  );
  const [petDetails, setPetDetails] = useState(null);
  const [selectedVet, setSelectedVet] = useState(() =>
    shouldStartAtDetails ? getDefaultAssignedVet() : null
  );

  useEffect(() => {
    window.scrollTo(0, 0);
  }, [screen]);

  // One goBack map instead of many ifs
  const backMap = useMemo(
    () => ({
      details: "landing",
      payment: "details",
      confirmation: "payment",

      "vet-login": "landing",
      "vet-register": "vet-login",
      "vet-pending": "vet-register",
      "vet-dashboard": "vet-login",
    }),
    []
  );

  const goBack = () => {
    const prev = backMap[screen];
    if (prev) setScreen(prev);
  };

  // (Optional) cleanup state when jumping back
  useEffect(() => {
    if (screen === "landing") {
      setPetDetails(null);
      setSelectedVet(null);
    }
  }, [screen]);

  useEffect(() => {
    if (!shouldStartAtDetails) return;
    let ignore = false;
    const bootstrap = async () => {
      setScreen("details");
      const assignedVet = await pickBestMatchVetFromApi();
      if (ignore) return;
      setSelectedVet(assignedVet);
    };

    bootstrap();

    return () => {
      ignore = true;
    };
  }, [shouldStartAtDetails]);

  // Render routes in a clean map
  const content = useMemo(() => {
    switch (screen) {
      case "landing":
        return (
          <LandingScreen
            onVetAccess={() => navigate("/auth")}
            onSelectVet={(vet) => {
              setSelectedVet(vet);
              setScreen("details");
            }}
          />
        );

      case "details":
        return (
          <PetDetailsScreen
            vet={selectedVet}
            onSubmit={(details) => {
              setPetDetails(details);
              setScreen("payment");
            }}
          />
        );

      case "payment":
        return selectedVet ? (
          <PaymentScreen
            vet={selectedVet}
            petDetails={petDetails}
            onBack={goBack}
            onPay={() => {
              setTimeout(() => {
                navigate("/consultation-booked", { state: { vet: selectedVet } });
              }, 1200);
            }}
          />
        ) : (
          <LoadingScreen />
        );

      case "confirmation":
        return selectedVet ? (
          <ConfirmationScreen vet={selectedVet} />
        ) : (
          <LoadingScreen />
        );

      // Vet flows
      case "vet-login":
        return (
          <VetLoginScreen
            onLogin={(payload) => {
              setScreen("vet-dashboard");
              navigate("/vet-dashboard", { state: { auth: payload } });
            }}
            onRegisterClick={() => setScreen("vet-register")}
            onBack={goBack}
          />
        );

      case "vet-register":
        return (
          <VetRegisterScreen onSubmit={() => setScreen("vet-login")} onBack={goBack} />
        );

      case "vet-pending":
        return <VetPendingScreen onHome={() => setScreen("landing")} />;

      case "vet-dashboard":
        return <VetDashboardScreen onLogout={() => setScreen("landing")} />;

      default:
        return (
          <LandingScreen
            onVetAccess={() => navigate("/auth")}
            onSelectVet={(vet) => {
              setSelectedVet(vet);
              setScreen("details");
            }}
          />
        );
    }
  }, [screen, petDetails, selectedVet, backMap, navigate]); // backMap safe (memo)

  return (
    <div className="min-h-screen bg-stone-100 font-sans">
      <div className="min-h-screen md:bg-gradient-to-b md:from-stone-100 md:to-stone-200">
        <div className="min-h-screen">
          <div
            className={[
              "bg-white min-h-screen relative overflow-x-hidden",
              "w-full",
              "md:w-full md:rounded-none md:shadow-none md:border-0",
            ].join(" ")}
          >
            {/* Lazy loading boundary */}
            <Suspense fallback={<LoadingScreen />}>{content}</Suspense>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Home;
