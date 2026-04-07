import React, { useEffect, useRef, useState } from "react";
import { Helmet } from "react-helmet-async";
import { useNavigate } from "react-router-dom";
import { apiBaseUrl, apiPost } from "../lib/api";
import logo from "../assets/images/logo.webp";
import "./AskPage.css";

const ASK_TITLE = "Snoutiq - Is My Pet Okay? Free AI Pet Health Check";
const ASK_DESCRIPTION =
  "Free AI pet symptom checker for Indian pet parents. Get expert triage guidance in seconds. No signup needed.";
const ASK_CANONICAL = "https://snoutiq.com/ask";
const ASK_STORAGE_KEY = "snoutiq-ask-state-v1";
const ASK_DAILY_USAGE_KEY = "snoutiq-ask-daily-usage-v1";
const FREE_CHECK_LIMIT = 3;
const GAUGE_CIRCUMFERENCE = 201.1;

const QUICK_SYMPTOMS = [
  {
    key: "not-eating",
    emoji: "🍽",
    title: "Not Eating",
    subtitle: "Appetite loss, skipping meals",
    species: "dog",
    message: "My dog has not eaten for 2 days and is very lethargic",
  },
  {
    key: "vomiting",
    emoji: "🤢",
    title: "Vomiting",
    subtitle: "Throwing up, retching",
    species: "dog",
    message: "My dog has been vomiting repeatedly since this morning",
  },
  {
    key: "limping",
    emoji: "🦮",
    title: "Limping",
    subtitle: "Lameness, joint pain",
    species: "dog",
    message:
      "My dog is limping and putting no weight on one leg, leg is swollen",
  },
  {
    key: "diarrhea",
    emoji: "💧",
    title: "Diarrhea",
    subtitle: "Loose stools, stomach",
    species: "dog",
    message: "My pet has loose stools or diarrhea since yesterday",
  },
  {
    key: "skin-itching",
    emoji: "🐱",
    title: "Skin / Itching",
    subtitle: "Hair loss, scratching",
    species: "cat",
    message:
      "My cat has circular patches of hair loss and is scratching a lot",
  },
  {
    key: "lethargy",
    emoji: "😴",
    title: "Lethargy",
    subtitle: "Weak, dull, low energy",
    species: "dog",
    message:
      "My pet seems very tired, lethargic and not interested in anything",
  },
];

const SPECIES_OPTIONS = [
  { value: "dog", label: "Dog", emoji: "🐕" },
  { value: "cat", label: "Cat", emoji: "🐈" },
  { value: "rabbit", label: "Rabbit", emoji: "🐇" },
  { value: "bird", label: "Bird", emoji: "🐦" },
];

const CTA_ROUTE_MAP = {
  video_consult: "/20+vetsonline?start=details",
  clinic: "/vet-at-home-gurgaon/pet-details",
  vet_at_home: "/vet-at-home-gurgaon/pet-details",
  emergency: "/vet-at-home-gurgaon/pet-details",
  govt: "/vet-at-home-gurgaon/pet-details",
};

const DEEPLINK_ROUTE_MAP = {
  "snoutiq://video-consult": "/20+vetsonline?start=details",
  "snoutiq://vet-at-home": "/vet-at-home-gurgaon/pet-details",
  "snoutiq://clinic-booking": "/vet-at-home-gurgaon/pet-details",
  "snoutiq://find-clinic": "/vet-at-home-gurgaon/pet-details",
  "snoutiq://emergency": "/vet-at-home-gurgaon/pet-details",
  "snoutiq://govt-hospitals": "/vet-at-home-gurgaon/pet-details",
};

const getTodayKey = () => new Date().toISOString().slice(0, 10);

const getTimeLabel = (value = new Date()) =>
  new Intl.DateTimeFormat("en-IN", {
    hour: "numeric",
    minute: "2-digit",
  }).format(value);

const safeParse = (value, fallback) => {
  try {
    return JSON.parse(value);
  } catch {
    return fallback;
  }
};

const readDailyUsage = () => {
  if (typeof window === "undefined") return 0;
  const raw = safeParse(window.localStorage.getItem(ASK_DAILY_USAGE_KEY), null);
  if (!raw || raw.date !== getTodayKey()) return 0;
  return Math.max(0, Number(raw.count) || 0);
};

const writeDailyUsage = (count) => {
  if (typeof window === "undefined") return;
  window.localStorage.setItem(
    ASK_DAILY_USAGE_KEY,
    JSON.stringify({
      date: getTodayKey(),
      count: Math.max(0, Number(count) || 0),
    })
  );
};

const readStoredState = () => {
  if (typeof window === "undefined") return null;
  const raw = safeParse(window.localStorage.getItem(ASK_STORAGE_KEY), null);
  if (!raw || typeof raw !== "object") return null;
  return {
    species: typeof raw.species === "string" ? raw.species : "dog",
    sessionId: typeof raw.sessionId === "string" ? raw.sessionId : "",
    entries: Array.isArray(raw.entries) ? raw.entries : [],
  };
};

const writeStoredState = (state) => {
  if (typeof window === "undefined") return;
  window.localStorage.setItem(ASK_STORAGE_KEY, JSON.stringify(state));
};

const clearStoredState = () => {
  if (typeof window === "undefined") return;
  window.localStorage.removeItem(ASK_STORAGE_KEY);
};

const getSpeciesMeta = (species) => {
  const normalized = String(species || "")
    .trim()
    .toLowerCase();
  return (
    SPECIES_OPTIONS.find((option) => option.value === normalized) || {
      value: normalized || "pet",
      label: normalized ? normalized[0].toUpperCase() + normalized.slice(1) : "Pet",
      emoji: "🐾",
    }
  );
};

const getArcOffset = (value) => {
  const score = Math.max(0, Math.min(100, Number(value) || 0));
  return GAUGE_CIRCUMFERENCE - (score / 100) * GAUGE_CIRCUMFERENCE;
};

const extractErrorMessage = (error) => {
  const text = String(error?.message || "").trim();
  if (!text) {
    return "We could not complete the symptom check right now. Please try again.";
  }
  if (/failed to fetch/i.test(text)) {
    return "Network issue while contacting Snoutiq AI. Please check your connection and try again.";
  }
  return text;
};

const getThemeClass = (theme = "video") => `ask-theme-${theme}`;

const buildGoogleSearchUrl = (query) =>
  `https://www.google.com/search?q=${encodeURIComponent(query)}`;

const navigateToAskTarget = (navigate, target) => {
  const route = String(target || "").trim();
  if (!route) return;

  if (route.includes("start=details")) {
    window.location.assign(route);
    return;
  }

  const [pathname, query = ""] = route.split("?");
  navigate({
    pathname: pathname || "/",
    search: query ? `?${query}` : "",
  });
};

const buildAssessmentShareText = (assessment) =>
  assessment?.ui?.health_score?.share?.whatsapp_text ||
  "Check your pet on Snoutiq AI: https://snoutiq.com/ask";

const derivePossibleCauses = (assessment) => {
  const causes = assessment?.triage_detail?.possible_causes;
  if (Array.isArray(causes) && causes.length > 0) {
    return causes.filter(Boolean);
  }

  const summary = String(assessment?.response?.diagnosis_summary || "").trim();
  const match = summary.match(/possible causes include (.+?)\.?$/i);
  if (!match) return [];

  return match[1]
    .split(/,| or /i)
    .map((value) => value.trim())
    .filter(Boolean);
};

const buildAssessmentCopy = (assessment) => {
  const title = assessment?.ui?.banner?.title || "Snoutiq AI assessment";
  const score = assessment?.health_score || assessment?.ui?.health_score?.value;
  const label = assessment?.ui?.health_score?.label || "";
  const message = assessment?.response?.message || "";
  const doNow = assessment?.response?.do_now || "";
  const watch = Array.isArray(assessment?.response?.what_to_watch)
    ? assessment.response.what_to_watch
    : [];

  return [
    title,
    score ? `Pet Health Score: ${score}/100${label ? ` (${label})` : ""}` : "",
    message,
    doNow ? `Do now: ${doNow}` : "",
    watch.length ? `Watch for: ${watch.join(" | ")}` : "",
    "https://snoutiq.com/ask",
  ]
    .filter(Boolean)
    .join("\n\n");
};

const buildFallbackSessionEntries = (payload) => {
  const history = payload?.state?.history;
  const pet = payload?.state?.pet || {};
  if (!Array.isArray(history) || history.length === 0) {
    return [];
  }

  return history.flatMap((turn, index) => [
    {
      id: `history-user-${index}`,
      kind: "user",
      message: turn?.user || "",
      time: turn?.ts || getTimeLabel(),
      species: pet?.species || "dog",
    },
    {
      id: `history-assistant-${index}`,
      kind: "note",
      message: turn?.assistant || "",
      time: turn?.ts || getTimeLabel(),
      routing: turn?.routing || "video_consult",
    },
  ]);
};

async function apiGetJson(path) {
  const res = await fetch(`${apiBaseUrl()}${path}`, {
    headers: { Accept: "application/json" },
  });
  let data = null;
  try {
    data = await res.json();
  } catch {
    data = null;
  }
  if (!res.ok) {
    const message =
      (data && (data.message || data.error)) || `HTTP ${res.status}`;
    throw new Error(message);
  }
  return data ?? {};
}

async function apiPostJson(path, body = {}) {
  return apiPost(path, body);
}

function IdleScreen({ species, onSpeciesSelect, onQuickStart }) {
  return (
    <div className="ask-idle">
      <div className="ask-idle-hero">
        <div className="ask-idle-icon">🐾</div>
        <h1 className="ask-idle-title">
          What&apos;s worrying
          <br />
          <em>your pet</em> today?
        </h1>
        <p className="ask-idle-subtitle">
          Describe symptoms in plain words. Takes 30 seconds. Free for all pet
          parents.
        </p>
        <div className="ask-trust-row">
          <span>100% free</span>
          <span>India-trained AI</span>
          <span>Vet-reviewed</span>
          <span>No signup</span>
        </div>
      </div>

      <div className="ask-section-label">Common symptoms - tap to start</div>
      <div className="ask-quick-grid">
        {QUICK_SYMPTOMS.map((item) => (
          <button
            key={item.key}
            type="button"
            className="ask-quick-button"
            onClick={() => onQuickStart(item)}
          >
            <span className="ask-quick-icon">{item.emoji}</span>
            <span className="ask-quick-copy">
              <strong>{item.title}</strong>
              <span>{item.subtitle}</span>
            </span>
          </button>
        ))}
      </div>

      <div className="ask-section-label">My pet is a</div>
      <div className="ask-species-row">
        {SPECIES_OPTIONS.map((option) => (
          <button
            key={option.value}
            type="button"
            className={`ask-species-button${
              species === option.value ? " is-active" : ""
            }`}
            onClick={() => onSpeciesSelect(option.value)}
          >
            <span>{option.emoji}</span>
            <span>{option.label}</span>
          </button>
        ))}
      </div>
    </div>
  );
}

function UserMessage({ entry }) {
  const speciesMeta = getSpeciesMeta(entry?.species);

  return (
    <div className="ask-message-group">
      <div className="ask-message-row ask-message-row-user">
        <div>
          <div className="ask-pet-pill">
            {speciesMeta.emoji} {speciesMeta.label} · India
          </div>
          <div className="ask-message-bubble ask-message-bubble-user">
            {entry?.message}
          </div>
          <div className="ask-message-time">{entry?.time}</div>
        </div>
      </div>
    </div>
  );
}

function NoteMessage({ entry }) {
  return (
    <div className="ask-message-group">
      <div className="ask-message-row">
        <div className="ask-note-card">
          <div className="ask-note-eyebrow">
            Previous assessment · {String(entry?.routing || "video_consult").replace(/_/g, " ")}
          </div>
          <p>{entry?.message}</p>
        </div>
      </div>
    </div>
  );
}

function AssessmentCard({
  entry,
  onAction,
  onShare,
  onCopyAssessment,
  onCopyLink,
}) {
  const assessment = entry?.payload || {};
  const ui = assessment?.ui || {};
  const banner = ui?.banner || {};
  const healthScore = ui?.health_score || {};
  const buttons = assessment?.buttons || {};
  const serviceCards = Array.isArray(ui?.service_cards) ? ui.service_cards : [];
  const theme = ui?.theme || "video";
  const possibleCauses = derivePossibleCauses(assessment);
  const watchItems = Array.isArray(assessment?.response?.what_to_watch)
    ? assessment.response.what_to_watch
    : [];
  const redFlags = Array.isArray(assessment?.triage_detail?.red_flags_found)
    ? assessment.triage_detail.red_flags_found
    : [];

  return (
    <div className="ask-message-group">
      <div className="ask-message-row">
        <article className="ask-result-card">
          <div className={`ask-banner ${getThemeClass(theme)}`}>
            <div className="ask-banner-eyebrow">{banner?.eyebrow}</div>
            <h2 className="ask-banner-title">{banner?.title}</h2>
            <p className="ask-banner-subtitle">{banner?.subtitle}</p>
            {banner?.time_badge ? (
              <div className="ask-banner-badge">{banner.time_badge}</div>
            ) : null}
          </div>

          <div className="ask-health-block">
            <div className="ask-health-top">
              <div>
                <div className="ask-health-eyebrow">Pet Health Score</div>
                <div className="ask-health-score-row">
                  <div
                    className="ask-health-score"
                    style={{ color: healthScore?.color || "#1e88e5" }}
                  >
                    {healthScore?.value ?? assessment?.health_score ?? "--"}
                  </div>
                  <div className="ask-health-denom">/100</div>
                </div>
                <div
                  className="ask-health-label"
                  style={{ color: healthScore?.color || "#1e88e5" }}
                >
                  {healthScore?.label}
                </div>
                <div className="ask-health-subtitle">{healthScore?.subtitle}</div>
              </div>

              <div className="ask-gauge">
                <svg viewBox="0 0 80 80" aria-hidden="true">
                  <circle
                    cx="40"
                    cy="40"
                    r="32"
                    fill="none"
                    stroke="#f1f5f9"
                    strokeWidth="8"
                  />
                  <circle
                    cx="40"
                    cy="40"
                    r="32"
                    fill="none"
                    stroke={healthScore?.color || "#1e88e5"}
                    strokeWidth="8"
                    strokeDasharray={GAUGE_CIRCUMFERENCE}
                    strokeDashoffset={getArcOffset(healthScore?.value)}
                    strokeLinecap="round"
                    transform="rotate(-90 40 40)"
                  />
                  <text
                    x="40"
                    y="44"
                    textAnchor="middle"
                    fontSize="14"
                    fontWeight="900"
                    fill={healthScore?.color || "#1e88e5"}
                    fontFamily="Fraunces, serif"
                  >
                    {healthScore?.value ?? "--"}
                  </text>
                </svg>
              </div>
            </div>

            <div className="ask-share-row">
              <div className="ask-share-copy">
                <strong>{healthScore?.share?.title || "Share this score"}</strong>
                <span>
                  {healthScore?.share?.helper ||
                    "Help other pet parents find Snoutiq"}
                </span>
              </div>
              <button
                type="button"
                className="ask-whatsapp-button"
                onClick={() => onShare(assessment)}
              >
                Share on WhatsApp
              </button>
              <button
                type="button"
                className="ask-copy-link-button"
                onClick={onCopyLink}
              >
                Copy link
              </button>
            </div>
          </div>

          <div className="ask-cta-stack">
            {buttons?.primary ? (
              <button
                type="button"
                className="ask-primary-cta"
                style={{ backgroundColor: buttons.primary.color || "#1565C0" }}
                onClick={() => onAction(buttons.primary, assessment)}
              >
                {buttons.primary.label}
              </button>
            ) : null}
            {buttons?.secondary ? (
              <button
                type="button"
                className="ask-secondary-cta"
                onClick={() => onAction(buttons.secondary, assessment)}
              >
                {buttons.secondary.label}
              </button>
            ) : null}
          </div>

          {serviceCards.length > 0 ? (
            <div className="ask-service-list">
              {serviceCards.map((card, index) => (
                <article
                  key={`${card?.title || "card"}-${index}`}
                  className={`ask-service-card${
                    card?.featured ? " is-featured" : ""
                  }`}
                >
                  <div className="ask-service-header">
                    {card?.badge ? (
                      <div
                        className={`ask-service-badge ask-service-badge-${
                          card?.badge_variant || "default"
                        }`}
                      >
                        {card.badge}
                      </div>
                    ) : (
                      <span />
                    )}
                  </div>
                  <div className="ask-service-title">{card?.title}</div>
                  <div className="ask-service-price-row">
                    <div className={`ask-service-price ask-service-price-${card?.theme || "video"}`}>
                      {card?.price}
                    </div>
                    {card?.orig_price ? (
                      <div className="ask-service-orig-price">{card.orig_price}</div>
                    ) : null}
                  </div>
                  {card?.guarantee ? (
                    <div className="ask-service-guarantee">{card.guarantee}</div>
                  ) : null}
                  <div className="ask-service-bullets">
                    {(card?.bullets || []).map((bullet, bulletIndex) => (
                      <div
                        key={`${bullet}-${bulletIndex}`}
                        className="ask-service-bullet"
                      >
                        {bullet}
                      </div>
                    ))}
                  </div>
                  <button
                    type="button"
                    className={`ask-service-button ask-service-button-${card?.theme || "video"}`}
                    onClick={() => onAction(card?.cta || {}, assessment)}
                  >
                    {card?.cta?.label || "Continue"}
                  </button>
                </article>
              ))}
            </div>
          ) : null}

          <div className="ask-result-body">
            <section className="ask-body-section">
              <div className="ask-body-label">What we think is happening</div>
              <div className="ask-assessment-block">
                <div className="ask-assessment-icon">🩺</div>
                <p>{assessment?.response?.message}</p>
              </div>
            </section>

            {assessment?.response?.do_now ? (
              <section className="ask-body-section">
                <div className="ask-do-now">
                  <div className="ask-do-now-icon">⚡</div>
                  <div>
                    <div className="ask-body-label ask-body-label-orange">
                      Do this right now
                    </div>
                    <p>{assessment.response.do_now}</p>
                  </div>
                </div>
              </section>
            ) : null}

            {assessment?.triage_detail?.india_context ? (
              <section className="ask-body-section">
                <div className="ask-india-note">
                  <span>🇮🇳</span>
                  <span>{assessment.triage_detail.india_context}</span>
                </div>
              </section>
            ) : null}

            {assessment?.triage_detail?.image_observation ? (
              <section className="ask-body-section">
                <div className="ask-body-label">Image observation</div>
                <div className="ask-note-card">
                  <p>{assessment.triage_detail.image_observation}</p>
                </div>
              </section>
            ) : null}

            {watchItems.length > 0 ? (
              <section className="ask-body-section">
                <div className="ask-body-label">Watch for</div>
                <div className="ask-watch-list">
                  {watchItems.map((item, index) => (
                    <div
                      key={`${item}-${index}`}
                      className={`ask-watch-item${
                        index === watchItems.length - 1 &&
                        assessment?.routing === "emergency"
                          ? " is-danger"
                          : index === 0
                            ? " is-warning"
                            : ""
                      }`}
                    >
                      <span className="ask-watch-icon">
                        {index === watchItems.length - 1 &&
                        assessment?.routing === "emergency"
                          ? "🔴"
                          : index === 0
                            ? "🟡"
                            : "⚪"}
                      </span>
                      <p>{item}</p>
                    </div>
                  ))}
                </div>
              </section>
            ) : null}

            {possibleCauses.length > 0 ? (
              <section className="ask-body-section">
                <div className="ask-body-label">Most likely causes</div>
                <div className="ask-pill-row">
                  {possibleCauses.map((cause, index) => (
                    <span key={`${cause}-${index}`} className="ask-info-pill">
                      {cause}
                    </span>
                  ))}
                </div>
              </section>
            ) : null}

            {redFlags.length > 0 ? (
              <section className="ask-body-section">
                <div className="ask-body-label">Red flags found</div>
                <div className="ask-pill-row">
                  {redFlags.map((flag, index) => (
                    <span
                      key={`${flag}-${index}`}
                      className="ask-info-pill ask-info-pill-danger"
                    >
                      {flag}
                    </span>
                  ))}
                </div>
              </section>
            ) : null}

            {assessment?.vet_summary ? (
              <section className="ask-body-section">
                <div className="ask-vet-summary">
                  <div>
                    <div className="ask-body-label ask-body-label-blue">
                      Vet handover summary
                    </div>
                    <p>This is the summary a vet can skim quickly if you share it.</p>
                  </div>
                  <button
                    type="button"
                    className="ask-copy-summary-button"
                    onClick={() => onCopyAssessment(assessment)}
                  >
                    Copy summary
                  </button>
                </div>
              </section>
            ) : null}
          </div>

          <div className="ask-disclaimer">
            <div className="ask-disclaimer-icon">🤖</div>
            <div>
              <div className="ask-disclaimer-label">Snoutiq AI - triage only</div>
              <p>
                AI-generated guidance trained on veterinary cases across India,
                reviewed for clinical accuracy. Not a diagnosis. Always follow a
                licensed vet&apos;s advice.
              </p>
            </div>
          </div>
        </article>
      </div>
    </div>
  );
}

export default function AskPage() {
  const navigate = useNavigate();
  const bodyRef = useRef(null);
  const textareaRef = useRef(null);
  const hydratedRef = useRef(false);
  const [species, setSpecies] = useState("dog");
  const [sessionId, setSessionId] = useState("");
  const [entries, setEntries] = useState([]);
  const [inputValue, setInputValue] = useState("");
  const [loading, setLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState("");
  const [toastMessage, setToastMessage] = useState("");
  const [checksToday, setChecksToday] = useState(0);

  useEffect(() => {
    const storedState = readStoredState();
    if (storedState) {
      setSpecies(storedState.species || "dog");
      setSessionId(storedState.sessionId || "");
      setEntries(storedState.entries || []);
    }
    setChecksToday(readDailyUsage());
    hydratedRef.current = true;
  }, []);

  useEffect(() => {
    if (!hydratedRef.current) return;
    writeStoredState({ species, sessionId, entries });
  }, [entries, sessionId, species]);

  useEffect(() => {
    writeDailyUsage(checksToday);
  }, [checksToday]);

  useEffect(() => {
    if (!toastMessage) return undefined;
    const timer = window.setTimeout(() => setToastMessage(""), 2200);
    return () => window.clearTimeout(timer);
  }, [toastMessage]);

  useEffect(() => {
    if (!bodyRef.current) return;
    bodyRef.current.scrollTop = bodyRef.current.scrollHeight;
  }, [entries, loading]);

  useEffect(() => {
    if (!sessionId || entries.length > 0 || !hydratedRef.current) return;

    let active = true;

    apiGetJson(`/api/symptom-session/${encodeURIComponent(sessionId)}`)
      .then((payload) => {
        if (!active) return;
        const fallbackEntries = buildFallbackSessionEntries(payload);
        if (fallbackEntries.length > 0) {
          setEntries(fallbackEntries);
        }
      })
      .catch(() => {
        if (!active) return;
        setSessionId("");
        clearStoredState();
      });

    return () => {
      active = false;
    };
  }, [entries.length, sessionId]);

  const freeChecksLeft = Math.max(0, FREE_CHECK_LIMIT - checksToday);

  const resizeTextarea = () => {
    const node = textareaRef.current;
    if (!node) return;
    node.style.height = "auto";
    node.style.height = `${Math.min(node.scrollHeight, 100)}px`;
  };

  useEffect(() => {
    resizeTextarea();
  }, [inputValue]);

  const pushUserEntry = (messageText, nextSpecies) => ({
    id: `user-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
    kind: "user",
    message: messageText,
    time: getTimeLabel(),
    species: nextSpecies,
  });

  const pushAssessmentEntry = (payload) => ({
    id: `assessment-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
    kind: "assessment",
    payload,
    time: getTimeLabel(),
  });

  const handleSend = async ({ message, nextSpecies } = {}) => {
    const messageText = String(message ?? inputValue).trim();
    const speciesValue = String(nextSpecies || species || "dog").trim() || "dog";

    if (!messageText || loading) return;

    const isFollowup = Boolean(sessionId);
    setErrorMessage("");
    setToastMessage("");
    setEntries((current) => [...current, pushUserEntry(messageText, speciesValue)]);
    setInputValue("");
    setLoading(true);

    try {
      const payload = isFollowup
        ? await apiPostJson("/api/symptom-followup", {
            session_id: sessionId,
            message: messageText,
          })
        : await apiPostJson("/api/symptom-check", {
            message: messageText,
            species: speciesValue,
          });

      setSessionId(payload?.session_id || "");
      setEntries((current) => [...current, pushAssessmentEntry(payload)]);

      if (!isFollowup) {
        setChecksToday((count) => count + 1);
      }
    } catch (error) {
      setErrorMessage(extractErrorMessage(error));
    } finally {
      setLoading(false);
    }
  };

  const handleQuickStart = (item) => {
    setSpecies(item.species);
    handleSend({ message: item.message, nextSpecies: item.species });
  };

  const handleCopyLink = async () => {
    try {
      await navigator.clipboard.writeText(ASK_CANONICAL);
      setToastMessage("Link copied");
    } catch {
      setToastMessage("Copy failed");
    }
  };

  const handleCopyAssessment = async (assessment) => {
    try {
      await navigator.clipboard.writeText(buildAssessmentCopy(assessment));
      setToastMessage("Summary copied");
    } catch {
      setToastMessage("Copy failed");
    }
  };

  const handleShare = (assessment) => {
    const text = encodeURIComponent(buildAssessmentShareText(assessment));
    window.open(`https://wa.me/?text=${text}`, "_blank", "noopener,noreferrer");
  };

  const handleAction = (action, assessment) => {
    const type = String(action?.type || "").trim();
    const deeplink = String(action?.deeplink || "").trim();

    if (type === "info") {
      handleCopyAssessment(assessment);
      return;
    }

    const route = CTA_ROUTE_MAP[type];
    if (route) {
      navigateToAskTarget(navigate, route);
      return;
    }

    const deeplinkRoute = DEEPLINK_ROUTE_MAP[deeplink];
    if (deeplinkRoute) {
      navigateToAskTarget(navigate, deeplinkRoute);
      return;
    }

    if (deeplink.startsWith("snoutiq://")) {
      window.location.assign(deeplink);
    }
  };

  const handleReset = async () => {
    const confirmed = window.confirm(
      "Start a fresh symptom check? This will clear the current conversation."
    );
    if (!confirmed) return;

    if (sessionId) {
      try {
        await apiPostJson(
          `/api/symptom-session/${encodeURIComponent(sessionId)}/reset`
        );
      } catch {
        // Clear local state even if the backend reset call fails.
      }
    }

    setEntries([]);
    setSessionId("");
    setInputValue("");
    setErrorMessage("");
    clearStoredState();
    setToastMessage("Started fresh");
  };

  const navBadgeText =
    freeChecksLeft > 0
      ? `${freeChecksLeft} free checks left`
      : "Free checks used today";

  return (
    <div className="ask-root">
      <Helmet>
        <html lang="en" />
        <title>{ASK_TITLE}</title>
        <meta name="description" content={ASK_DESCRIPTION} />
        <meta property="og:type" content="website" />
        <meta property="og:url" content={ASK_CANONICAL} />
        <meta property="og:title" content={ASK_TITLE} />
        <meta property="og:description" content={ASK_DESCRIPTION} />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content={ASK_TITLE} />
        <meta name="twitter:description" content={ASK_DESCRIPTION} />
        <link rel="canonical" href={ASK_CANONICAL} />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
        <link
          href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,700;0,9..144,900;1,9..144,600&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap"
          rel="stylesheet"
        />
        <script type="application/ld+json">
          {JSON.stringify({
            "@context": "https://schema.org",
            "@type": "MedicalWebPage",
            name: "Snoutiq Ask - Free AI Pet Health Check",
            description: ASK_DESCRIPTION,
            url: ASK_CANONICAL,
            inLanguage: "en-IN",
            about: {
              "@type": "Thing",
              name: "Pet symptom checker",
            },
          })}
        </script>
      </Helmet>

      <div className="ask-announcement">
        🐾 Free AI Pet Health Check · snoutiq.com/ask · No signup needed
      </div>

      <div className="ask-nav">
        <div className="ask-logo">
          <img
            src={logo}
            alt="SnoutIQ"
            className="ask-logo-image"
            width={130}
            height={24}
            loading="eager"
            decoding="async"
          />
        </div>
        <div className="ask-nav-actions">
          <div className="ask-badge">{navBadgeText}</div>
          <button
            type="button"
            className="ask-nav-button"
            onClick={() =>
              navigateToAskTarget(navigate, "/20+vetsonline?start=details")
            }
          >
            Consult ₹499
          </button>
        </div>
      </div>

      <div className="ask-page-shell">
        <main className="ask-page">
          <div className="ask-chat-header">
            <div className="ask-avatar">🐾</div>
            <div className="ask-chat-meta">
              <h2>Snoutiq AI</h2>
              <p>
                <span className="ask-live-dot" />
                AI triage · Vet-reviewed · Free
              </p>
            </div>
            {sessionId ? (
              <button
                type="button"
                className="ask-reset-button"
                onClick={handleReset}
              >
                Start over
              </button>
            ) : null}
          </div>

          <div className="ask-chat-body" ref={bodyRef}>
            {entries.length === 0 ? (
              <IdleScreen
                species={species}
                onSpeciesSelect={setSpecies}
                onQuickStart={handleQuickStart}
              />
            ) : null}

            {entries.map((entry) => {
              if (entry.kind === "user") {
                return <UserMessage key={entry.id} entry={entry} />;
              }
              if (entry.kind === "note") {
                return <NoteMessage key={entry.id} entry={entry} />;
              }
              if (entry.kind === "assessment") {
                return (
                  <AssessmentCard
                    key={entry.id}
                    entry={entry}
                    onAction={handleAction}
                    onShare={handleShare}
                    onCopyAssessment={handleCopyAssessment}
                    onCopyLink={handleCopyLink}
                  />
                );
              }
              return null;
            })}

            {loading ? (
              <div className="ask-message-group">
                <div className="ask-message-row">
                  <div className="ask-typing-bubble">
                    <span />
                    <span />
                    <span />
                  </div>
                </div>
              </div>
            ) : null}

            {errorMessage ? (
              <div className="ask-error-card" role="status">
                {errorMessage}
              </div>
            ) : null}
          </div>

          <div className="ask-input-bar">
            <textarea
              ref={textareaRef}
              className="ask-input"
              rows={1}
              value={inputValue}
              onChange={(event) => setInputValue(event.target.value)}
              placeholder="Describe your pet's symptoms..."
              onKeyDown={(event) => {
                if (event.key === "Enter" && !event.shiftKey) {
                  event.preventDefault();
                  handleSend();
                }
              }}
            />
            <button
              type="button"
              className="ask-send-button"
              onClick={() => handleSend()}
              disabled={loading}
              aria-label="Send symptom message"
            >
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M2.01 21 23 12 2.01 3 2 10l15 2-15 2z" />
              </svg>
            </button>
          </div>
        </main>
      </div>

      {toastMessage ? <div className="ask-toast">{toastMessage}</div> : null}
    </div>
  );
}
