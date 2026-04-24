import React, { useEffect, useMemo, useRef, useState } from "react";
import {
  Calendar,
  LogIn,
  LogOut,
  Menu,
  MessageSquare,
  PawPrint,
  Send,
  User,
  Video,
  X,
  Sparkles,
  ShieldCheck,
  Clock,
  ChevronDown,
  Loader2,
  MapPin,
  Phone,
  ExternalLink,
  AlertCircle,
  CheckCircle2,
  Stethoscope,
  Activity,
  Star,
} from "lucide-react";
import { useNavigate } from "react-router-dom";
import { clearAiAuthState } from "./AiAuth";
import snoutiq_app_icon from "../assets/snoutiq_app_icon.png";

const API_BASE = "https://snoutiq.com/backend/api";
const CHAT_CACHE_PREFIX = "snoutiq.ai.chatCache";

const normalizeBoolean = (value) => {
  if (typeof value === "boolean") return value;
  if (typeof value === "number") return value === 1;
  const normalized = String(value ?? "")
    .trim()
    .toLowerCase();
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

  const petName = String(
    primaryPet?.name ?? primaryPet?.pet_name ?? user?.pet_name ?? "",
  ).trim();

  const ownerName = String(
    user?.pet_owner_name ?? user?.owner_name ?? user?.name ?? "",
  ).trim();

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

const resolveSpecies = (currentPet, currentUser) =>
  String(
    currentPet?.species ??
      currentPet?.pet_type ??
      currentUser?.species ??
      currentUser?.pet_type ??
      "",
  )
    .trim()
    .toLowerCase();

const resolveLocation = (currentPet, currentUser) => {
  const possible = [
    currentPet?.location,
    currentPet?.pet_location,
    currentUser?.location,
    currentUser?.city,
    currentUser?.pet_location,
    currentUser?.current_location,
    currentUser?.address,
  ];

  const hit = possible.find((value) => String(value ?? "").trim());
  return String(hit ?? "").trim();
};

const createHeaders = (token, hasJsonBody = true) => {
  const headers = {
    Accept: "application/json",
  };

  if (hasJsonBody) {
    headers["Content-Type"] = "application/json";
  }

  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  return headers;
};

const getChatCacheKey = (userId) =>
  `${CHAT_CACHE_PREFIX}:${String(userId ?? "").trim()}`;

const readChatCache = (userId) => {
  if (typeof window === "undefined") return null;

  const key = getChatCacheKey(userId);
  if (!key) return null;

  try {
    const raw = window.localStorage.getItem(key);
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    return parsed && typeof parsed === "object" ? parsed : null;
  } catch {
    return null;
  }
};

const writeChatCache = (userId, payload) => {
  if (typeof window === "undefined") return;

  const key = getChatCacheKey(userId);
  if (!key) return;

  try {
    window.localStorage.setItem(key, JSON.stringify(payload));
  } catch {}
};

const clearChatCache = (userId) => {
  if (typeof window === "undefined") return;

  const key = getChatCacheKey(userId);
  if (!key) return;

  try {
    window.localStorage.removeItem(key);
  } catch {}
};

const formatTime = (value) => {
  if (!value) return "";
  const date = value instanceof Date ? value : new Date(value);
  if (Number.isNaN(date.getTime())) return "";

  return date.toLocaleTimeString([], {
    hour: "2-digit",
    minute: "2-digit",
  });
};

const normalizeCachedMessage = (message, index) => {
  if (!message || typeof message !== "object") return null;

  const sender = message?.sender === "user" ? "user" : "bot";
  const text = String(message?.text ?? "").trim();

  if (!text && sender === "user") return null;

  return {
    id:
      message?.id ??
      `${sender}-${index}-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`,
    text,
    sender,
    timestamp: message?.timestamp ?? new Date().toISOString(),
    queryType: message?.queryType ?? null,
    hasActions: Boolean(message?.hasActions),
    structuredData:
      message?.structuredData && typeof message.structuredData === "object"
        ? message.structuredData
        : null,
    richContent:
      message?.richContent && typeof message.richContent === "object"
        ? message.richContent
        : null,
    raw: message?.raw && typeof message.raw === "object" ? message.raw : null,
  };
};

const normalizeCachedConversation = (conversation, index) => {
  if (!conversation || typeof conversation !== "object") return null;

  const token = String(
    conversation?.token ??
      conversation?.chat_room_token ??
      conversation?.context_token ??
      conversation?.session_id ??
      "",
  ).trim();

  if (!token) return null;

  const messages = Array.isArray(conversation?.messages)
    ? conversation.messages
        .map((message, messageIndex) =>
          normalizeCachedMessage(message, messageIndex),
        )
        .filter(Boolean)
    : [];

  return {
    id: conversation?.id ?? token ?? `cached-room-${index}`,
    token,
    title:
      String(conversation?.title ?? conversation?.name ?? "New Chat").trim() ||
      "New Chat",
    summary: String(conversation?.summary ?? "").trim(),
    turns: Number(conversation?.turns ?? 0),
    createdAt: conversation?.createdAt ?? conversation?.created_at ?? null,
    updatedAt: conversation?.updatedAt ?? conversation?.updated_at ?? null,
    messages,
    loaded: Boolean(conversation?.loaded ?? messages.length > 0),
  };
};

const normalizeRoom = (room, existingRoom = null) => ({
  id: room?.id ?? room?.chat_room_id ?? room?.chat_room_token ?? Date.now(),
  token: room?.chat_room_token ?? room?.context_token ?? room?.session_id ?? "",
  title: String(room?.name ?? room?.title ?? "New Chat").trim() || "New Chat",
  summary: String(room?.summary ?? existingRoom?.summary ?? "").trim(),
  turns: Number(room?.turns ?? existingRoom?.turns ?? 0),
  createdAt: room?.created_at ?? existingRoom?.createdAt ?? null,
  updatedAt: room?.updated_at ?? existingRoom?.updatedAt ?? null,
  messages: existingRoom?.messages ?? [],
  loaded: existingRoom?.loaded ?? false,
});

const normalizeList = (items) =>
  Array.isArray(items)
    ? items
        .map((item) => String(item ?? "").trim())
        .filter(Boolean)
    : [];

const dedupeButtons = (items) => {
  const seen = new Set();
  return items.filter((item) => {
    const key = `${item?.label || ""}|${item?.deeplink || ""}|${item?.type || ""}`;
    if (!key.trim() || seen.has(key)) return false;
    seen.add(key);
    return true;
  });
};

const resolveActionDoctorId = (item) => {
  const possible = [
    item?.doctor_id,
    item?.doctorId,
    item?.doctor?.doctor_id,
    item?.doctor?.doctorId,
    item?.doctor?.id,
  ];

  const hit = possible.find((value) => String(value ?? "").trim());
  return String(hit ?? "").trim();
};

const resolveActionClinicId = (item) => {
  const possible = [
    item?.clinic_id,
    item?.clinicId,
    item?.vet_registeration_id,
    item?.vetRegistrationId,
    item?.clinic?.clinic_id,
    item?.clinic?.clinicId,
    item?.clinic?.id,
  ];

  const hit = possible.find((value) => String(value ?? "").trim());
  return String(hit ?? "").trim();
};

const resolveActionDoctorName = (item) => {
  const possible = [
    item?.doctor_name,
    item?.doctorName,
    item?.doctor?.doctor_name,
    item?.doctor?.doctorName,
    item?.doctor?.name,
    item?.name,
  ];

  const hit = possible.find((value) => String(value ?? "").trim());
  return String(hit ?? "").trim();
};

const resolveActionClinicName = (item) => {
  const possible = [
    item?.clinic_name,
    item?.clinicName,
    item?.place_name,
    item?.title,
    item?.name,
    item?.clinic?.clinic_name,
    item?.clinic?.clinicName,
    item?.clinic?.name,
  ];

  const hit = possible.find((value) => String(value ?? "").trim());
  return String(hit ?? "").trim();
};

const normalizeSuggestedClinic = (item) => {
  if (!item || typeof item !== "object") return null;

  return {
    name: resolveActionClinicName(item),
    address: String(
      item?.address ?? item?.place_address ?? item?.formatted_address ?? "",
    ).trim(),
    place_id: String(
      item?.place_id ?? item?.placeId ?? item?.external_place_id ?? "",
    ).trim(),
    maps_link: String(
      item?.maps_link ?? item?.mapsLink ?? item?.website ?? "",
    ).trim(),
    phone: String(item?.phone ?? item?.mobile ?? item?.clinic_mobile ?? "").trim(),
    latitude: item?.latitude ?? item?.lat ?? null,
    longitude: item?.longitude ?? item?.lng ?? null,
  };
};

const isVideoConsultAction = (item) => {
  const deeplink = String(item?.deeplink ?? "").trim().toLowerCase();
  const type = String(item?.type ?? "").trim().toLowerCase();

  return (
    type === "video_consult" ||
    type === "video" ||
    deeplink.includes("video-consult") ||
    deeplink.includes("videoconsult") ||
    deeplink.includes("video-call") ||
    deeplink.includes("videocall")
  );
};

const isClinicBookingAction = (item) => {
  const deeplink = String(item?.deeplink ?? "").trim().toLowerCase();
  const type = String(item?.type ?? "").trim().toLowerCase();

  return (
    type === "clinic" ||
    type === "clinic_booking" ||
    type === "in_clinic" ||
    deeplink.includes("find-clinic") ||
    deeplink.includes("clinic-booking") ||
    deeplink.includes("book-clinic") ||
    deeplink.includes("clinic")
  );
};

const isDoctorBookingAction = (item) => {
  if (isVideoConsultAction(item) || isClinicBookingAction(item)) {
    return false;
  }

  const deeplink = String(item?.deeplink ?? "").trim().toLowerCase();
  const type = String(item?.type ?? "").trim().toLowerCase();
  const label = String(item?.label ?? item?.title ?? "").trim().toLowerCase();

  return (
    type === "doctor" ||
    type === "doctor_consult" ||
    type === "consult" ||
    deeplink.includes("doctor") ||
    Boolean(resolveActionDoctorId(item)) ||
    ((label.includes("doctor") || label.includes("vet")) &&
      (label.includes("book") || label.includes("consult")))
  );
};

const getActionButtonLabel = (item) => {
  if (
    isClinicBookingAction(item) ||
    isVideoConsultAction(item) ||
    isDoctorBookingAction(item)
  ) {
    return "Book Consult";
  }

  return item?.label || item?.title || "Continue";
};

const extractRichContent = (payload) => {
  const response = payload?.response || {};
  const ui = payload?.ui || {};
  const banner = ui?.banner || {};
  const healthScore = ui?.health_score || {};
  const followUpQuestion = response?.follow_up_question || payload?.follow_up_question || null;
  const serviceCards = Array.isArray(ui?.service_cards) ? ui.service_cards : [];
  const safeToDoList = normalizeList(response?.safe_to_do_while_waiting);
  const watchList = normalizeList(response?.what_to_watch);

  const buttonItems = dedupeButtons(
    [
      payload?.buttons?.primary,
      payload?.buttons?.secondary,
      ...serviceCards.map((card) => ({
        ...card?.cta,
        title: card?.title,
        price: card?.price,
        orig_price: card?.orig_price,
        guarantee: card?.guarantee,
        bullets: Array.isArray(card?.bullets) ? card.bullets : [],
        featured: Boolean(card?.featured),
        badge: card?.badge,
        badge_variant: card?.badge_variant,
        theme: card?.theme,
      })),
    ].filter(Boolean),
  );

  return {
    intro:
      response?.what_we_think_is_happening ||
      response?.message ||
      payload?.message ||
      payload?.chat?.answer ||
      "",
    diagnosisSummary:
      response?.diagnosis_summary ||
      payload?.diagnosis_summary ||
      payload?.symptom_analysis?.diagnosis_summary ||
      "",
    doNow: response?.do_now || "",
    timeSensitivity: response?.time_sensitivity || banner?.time_badge || "",
    safeToDo:
      safeToDoList.length > 0
        ? safeToDoList
        : normalizeList(payload?.safe_to_do_while_waiting),
    whatToWatch:
      watchList.length > 0 ? watchList : normalizeList(payload?.what_to_watch),
    beReadyToTellVet:
      response?.be_ready_to_tell_vet || payload?.be_ready_to_tell_vet || "",
    followUpQuestion: followUpQuestion
      ? {
          label: String(followUpQuestion?.label ?? "").trim(),
          question: String(followUpQuestion?.question ?? "").trim(),
          options: normalizeList(followUpQuestion?.options),
        }
      : null,
    banner: {
      eyebrow: String(banner?.eyebrow ?? "").trim(),
      title: String(banner?.title ?? "").trim(),
      subtitle: String(banner?.subtitle ?? "").trim(),
    },
    healthScore: {
      value: healthScore?.value ?? payload?.health_score ?? null,
      label: String(healthScore?.label ?? "").trim(),
      subtitle: String(healthScore?.subtitle ?? "").trim(),
      color: String(healthScore?.color ?? "").trim(),
    },
    buttons: buttonItems,
    serviceCards,
    statusText: String(payload?.status_text ?? "").trim(),
    severity: String(payload?.severity ?? "").trim(),
    routing: String(payload?.routing ?? payload?.decision ?? "").trim(),
    vetSummary: String(payload?.vet_summary ?? "").trim(),
  };
};

const getResponseText = (payload) => {
  if (!payload || typeof payload !== "object") return "Something went wrong.";

  const rich = extractRichContent(payload);
  return (
    rich?.intro ||
    payload?.response?.message ||
    payload?.message ||
    payload?.chat?.answer ||
    "I received your request, but no reply text came from the server."
  );
};

const mapChatHistoryToMessages = (chats = []) => {
  const items = [];

  chats.forEach((chat, index) => {
    const baseId = chat?.id ?? `${chat?.chat_room_token || "chat"}-${index}`;

    if (String(chat?.question ?? "").trim()) {
      items.push({
        id: `${baseId}-user`,
        text: chat.question,
        sender: "user",
        timestamp: chat?.created_at ?? new Date().toISOString(),
      });
    }

    if (String(chat?.answer ?? "").trim()) {
      items.push({
        id: `${baseId}-bot`,
        text: chat.answer,
        sender: "bot",
        timestamp: chat?.updated_at ?? chat?.created_at ?? new Date().toISOString(),
        queryType: chat?.response_tag ?? null,
        hasActions:
          chat?.severity === "emergency" ||
          chat?.severity === "urgent" ||
          chat?.response_tag === "symptom_triage",
        richContent: null,
        raw: chat,
      });
    }
  });

  return items;
};

const buildBotMessageFromApi = (payload) => ({
  id: `bot-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
  text: getResponseText(payload),
  sender: "bot",
  timestamp: payload?.timestamp || new Date().toISOString(),
  queryType: payload?.decision || payload?.routing || null,
  hasActions: Boolean(
    payload?.buttons?.primary ||
      payload?.buttons?.secondary ||
      (Array.isArray(payload?.ui?.service_cards) && payload.ui.service_cards.length > 0),
  ),
  structuredData: payload?.structured_data ?? null,
  richContent: extractRichContent(payload),
  raw: payload,
});

const SectionList = ({ title, items, icon: Icon, tone = "slate" }) => {
  if (!Array.isArray(items) || items.length === 0) return null;

  const toneMap = {
    slate: "border-slate-200 bg-slate-50 text-slate-800",
    amber: "border-amber-200 bg-amber-50 text-amber-900",
    red: "border-red-200 bg-red-50 text-red-900",
    blue: "border-blue-200 bg-blue-50 text-blue-900",
    green: "border-emerald-200 bg-emerald-50 text-emerald-900",
  };

  return (
    <div className={`mt-4 rounded-2xl border p-3 ${toneMap[tone] || toneMap.slate}`}>
      <div className="mb-2 flex items-center gap-2 text-sm font-semibold">
        {Icon ? <Icon size={16} /> : null}
        {title}
      </div>
      <ul className="space-y-2">
        {items.map((item, index) => (
          <li key={`${title}-${index}`} className="flex items-start gap-2 text-sm leading-6">
            <span className="mt-[9px] h-1.5 w-1.5 shrink-0 rounded-full bg-current opacity-70" />
            <span>{item}</span>
          </li>
        ))}
      </ul>
    </div>
  );
};

export default function NavItem({
  authState,
  pendingQuestion,
  onRequireAccessFlow,
  onPendingQuestionConsumed,
  onRequestLogin,
}) {
  const navigate = useNavigate();
  const latestBotMessageRef = useRef(null);
  const shouldPinLatestResponseRef = useRef(false);
  const hasHydratedChatCacheRef = useRef(false);

  const [messages, setMessages] = useState([]);
  const [currentInput, setCurrentInput] = useState("");
  const [conversations, setConversations] = useState([]);
  const [activeConversation, setActiveConversation] = useState(null);
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);
  const [profileMenuOpen, setProfileMenuOpen] = useState(false);
  const [isSending, setIsSending] = useState(false);
  const [isRoomsLoading, setIsRoomsLoading] = useState(false);
  const [isHistoryLoading, setIsHistoryLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState("");

  const currentUser = authState?.user || {};
  const currentPet =
    currentUser?.pet && typeof currentUser.pet === "object"
      ? currentUser.pet
      : Array.isArray(currentUser?.pets) && currentUser.pets.length > 0
        ? currentUser.pets[0]
        : null;

  const token = authState?.token || "";

  const resolvedPetId =
    currentPet?.id ??
    currentPet?.pet_id ??
    currentUser?.pet_id ??
    currentUser?.pet?.id ??
    currentUser?.pet?.pet_id ??
    "";

  const resolvedUserId = currentUser?.id ?? currentUser?.user_id ?? "";

  const species = useMemo(
    () => resolveSpecies(currentPet, currentUser),
    [currentPet, currentUser],
  );

  const location = useMemo(
    () => resolveLocation(currentPet, currentUser),
    [currentPet, currentUser],
  );

  const hasAuth = useMemo(() => {
    const user = authState?.user || {};
    return Boolean(authState?.token && (user?.id || user?.user_id));
  }, [authState]);

  const profileReady = useMemo(() => {
    return hasUsablePetProfile(authState);
  }, [authState]);

  const petDisplayName = String(
    currentPet?.name ?? currentPet?.pet_name ?? currentUser?.pet_name ?? "",
  ).trim();

  const ownerDisplayName = String(
    currentUser?.pet_owner_name ?? currentUser?.owner_name ?? currentUser?.name ?? "",
  ).trim();

  const petBreed = String(
    currentPet?.breed ?? currentPet?.pet_breed ?? currentUser?.breed ?? "",
  ).trim();

  const petProfileImageUrl = resolvePetImageUrl(currentPet, currentUser?.pet, currentUser);
  const hasProfileIdentity = Boolean(
    ownerDisplayName || petDisplayName || petProfileImageUrl,
  );
  const canOpenProfileMenu = hasAuth && hasProfileIdentity;

  useEffect(() => {
    if (!hasAuth || !resolvedUserId) {
      hasHydratedChatCacheRef.current = false;
      setMessages([]);
      setConversations([]);
      setActiveConversation(null);
      return;
    }

    if (hasHydratedChatCacheRef.current) return;

    hasHydratedChatCacheRef.current = true;

    const cached = readChatCache(resolvedUserId);
    if (!cached) return;

    const cachedConversations = Array.isArray(cached?.conversations)
      ? cached.conversations
          .map((conversation, index) =>
            normalizeCachedConversation(conversation, index),
          )
          .filter(Boolean)
      : [];

    if (cachedConversations.length > 0) {
      setConversations(cachedConversations);
    }

    const cachedActiveToken = String(cached?.activeConversation ?? "").trim();
    if (!cachedActiveToken) return;

    setActiveConversation(cachedActiveToken);

    const cachedActiveRoom = cachedConversations.find(
      (conversation) => conversation.token === cachedActiveToken,
    );
    if (cachedActiveRoom?.messages?.length) {
      setMessages(cachedActiveRoom.messages);
    }
  }, [hasAuth, resolvedUserId]);

  useEffect(() => {
    if (!hasAuth || !resolvedUserId) return;
    if (!hasHydratedChatCacheRef.current) return;

    writeChatCache(resolvedUserId, {
      activeConversation,
      conversations,
      savedAt: Date.now(),
    });
  }, [activeConversation, conversations, hasAuth, resolvedUserId]);

  useEffect(() => {
    if (!shouldPinLatestResponseRef.current) return;

    shouldPinLatestResponseRef.current = false;
    latestBotMessageRef.current?.scrollIntoView({
      behavior: "smooth",
      block: "start",
    });
  }, [messages]);

  useEffect(() => {
    if (!isSidebarOpen) return undefined;

    const handleKeyDown = (event) => {
      if (event.key === "Escape") {
        setIsSidebarOpen(false);
      }
    };

    document.body.style.overflow = "hidden";
    window.addEventListener("keydown", handleKeyDown);

    return () => {
      document.body.style.overflow = "";
      window.removeEventListener("keydown", handleKeyDown);
    };
  }, [isSidebarOpen]);

  useEffect(() => {
    const mediaQuery = window.matchMedia("(min-width: 1024px)");
    const handleChange = (event) => {
      if (event.matches) {
        setIsSidebarOpen(false);
      }
    };

    if (mediaQuery.addEventListener) {
      mediaQuery.addEventListener("change", handleChange);
    } else {
      mediaQuery.addListener(handleChange);
    }

    return () => {
      if (mediaQuery.removeEventListener) {
        mediaQuery.removeEventListener("change", handleChange);
      } else {
        mediaQuery.removeListener(handleChange);
      }
    };
  }, []);

  useEffect(() => {
    const handleClickOutside = () => {
      setProfileMenuOpen(false);
    };

    if (profileMenuOpen) {
      window.addEventListener("click", handleClickOutside);
    }

    return () => {
      window.removeEventListener("click", handleClickOutside);
    };
  }, [profileMenuOpen]);

  useEffect(() => {
    if (!canOpenProfileMenu && profileMenuOpen) {
      setProfileMenuOpen(false);
    }
  }, [canOpenProfileMenu, profileMenuOpen]);

  const updateConversationInState = (roomToken, updater) => {
    setConversations((prev) =>
      prev.map((conv) =>
        conv.token === roomToken ? { ...conv, ...updater(conv) } : conv,
      ),
    );
  };

  const loadRooms = async (preferredToken = null) => {
    if (!hasAuth || !resolvedUserId) {
      setConversations([]);
      return;
    }

    setIsRoomsLoading(true);
    setErrorMessage("");

    try {
      const response = await fetch(
        `${API_BASE}/ask/chat/listRooms?user_id=${encodeURIComponent(resolvedUserId)}&limit=30`,
        {
          method: "GET",
          headers: createHeaders(token, false),
        },
      );

      const data = await response.json().catch(() => ({}));

      if (!response.ok || data?.status !== "success") {
        throw new Error(data?.message || "Unable to load conversations.");
      }

      setConversations((prev) => {
        const previousRooms = new Map(prev.map((item) => [item.token, item]));

        return Array.isArray(data?.rooms)
          ? data.rooms.map((room) =>
              normalizeRoom(
                room,
                previousRooms.get(
                  room?.chat_room_token ?? room?.context_token ?? room?.session_id ?? "",
                ),
              ),
            )
          : [];
      });

      const fetchedRooms = Array.isArray(data?.rooms) ? data.rooms : [];
      const preferred = preferredToken
        ? fetchedRooms.find(
            (room) =>
              (room?.chat_room_token ?? room?.context_token ?? room?.session_id ?? "") ===
              preferredToken,
          )
        : null;

      if (preferred) {
        setActiveConversation(
          preferred?.chat_room_token ?? preferred?.context_token ?? preferred?.session_id ?? null,
        );
        return;
      }

      if (!activeConversation && fetchedRooms.length > 0) {
        setActiveConversation(
          fetchedRooms[0]?.chat_room_token ??
            fetchedRooms[0]?.context_token ??
            fetchedRooms[0]?.session_id ??
            null,
        );
      }
    } catch (error) {
      setErrorMessage(error?.message || "Failed to load chat rooms.");
    } finally {
      setIsRoomsLoading(false);
    }
  };

  useEffect(() => {
    loadRooms();
  }, [hasAuth, resolvedUserId]);

  const loadRoomChats = async (roomToken, options = {}) => {
    const { shouldSetActive = true } = options;

    if (!roomToken || !hasAuth || !resolvedUserId) return;

    setIsHistoryLoading(true);
    setErrorMessage("");

    if (shouldSetActive) {
      setActiveConversation(roomToken);
      setIsSidebarOpen(false);
    }

    try {
      const response = await fetch(
        `${API_BASE}/ask/chat-rooms/${encodeURIComponent(roomToken)}/chats?user_id=${encodeURIComponent(resolvedUserId)}&sort=asc`,
        {
          method: "GET",
          headers: createHeaders(token, false),
        },
      );

      const data = await response.json().catch(() => ({}));

      if (!response.ok || data?.status !== "success") {
        throw new Error(data?.message || "Unable to load chat history.");
      }

      const roomMessages = mapChatHistoryToMessages(data?.chats || []);
      setMessages(roomMessages);

      updateConversationInState(roomToken, (conv) => ({
        messages: roomMessages,
        loaded: true,
        summary:
          conv.summary ||
          String(data?.room?.summary ?? roomMessages?.[roomMessages.length - 1]?.text ?? "").trim(),
      }));
    } catch (error) {
      setMessages([]);
      setErrorMessage(error?.message || "Failed to load room messages.");
    } finally {
      setIsHistoryLoading(false);
    }
  };

  useEffect(() => {
    if (!activeConversation || !hasAuth || !resolvedUserId) return;

    const activeRoom = conversations.find((room) => room.token === activeConversation);
    if (!activeRoom) return;

    if (activeRoom.loaded) {
      setMessages(activeRoom.messages || []);
      return;
    }

    loadRoomChats(activeConversation, { shouldSetActive: false });
  }, [activeConversation, conversations, hasAuth, resolvedUserId]);

  const createRoom = async (title = "New Chat") => {
    if (!hasAuth || !resolvedUserId) {
      throw new Error("Login required before creating a chat room.");
    }

    const payload = {
      user_id: resolvedUserId,
      title,
      pet_id: resolvedPetId || undefined,
      pet_name: petDisplayName || undefined,
      pet_breed: petBreed || undefined,
      pet_location: location || undefined,
      species: species || undefined,
    };

    const response = await fetch(`${API_BASE}/ask/chat-rooms/new`, {
      method: "POST",
      headers: createHeaders(token, true),
      body: JSON.stringify(payload),
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok || data?.status !== "success") {
      throw new Error(data?.message || "Unable to create a new chat room.");
    }

    const room = normalizeRoom({
      ...data,
      name: data?.name || title,
    });

    setConversations((prev) => {
      const alreadyExists = prev.some((item) => item.token === room.token);
      if (alreadyExists) return prev;
      return [room, ...prev];
    });

    setActiveConversation(room.token);
    setMessages([]);
    setIsSidebarOpen(false);

    return room;
  };

  const createNewConversation = async () => {
    try {
      setErrorMessage("");
      if (!hasAuth || !resolvedUserId) {
        handleLogin();
        return;
      }
      await createRoom("New Chat");
    } catch (error) {
      setErrorMessage(error?.message || "Unable to start new chat.");
    }
  };

  const selectConversation = async (roomToken) => {
    if (!roomToken) return;
    await loadRoomChats(roomToken, { shouldSetActive: true });
  };

  const getLatestUserMessageText = () => {
    for (let index = messages.length - 1; index >= 0; index -= 1) {
      const message = messages[index];
      if (message?.sender !== "user") continue;
      const text = String(message?.text ?? "").trim();
      if (text) return text;
    }
    return "";
  };

  const handleVideoCall = (button = null) => {
    navigate("/video-counsult", {
      state: {
        source: isDoctorBookingAction(button) ? "ai_doctor_suggestion" : "ai_video_consult",
        petId: resolvedPetId || null,
        userId: resolvedUserId || null,
        doctorId: resolveActionDoctorId(button) || null,
        clinicId: resolveActionClinicId(button) || null,
        doctorName: resolveActionDoctorName(button) || null,
        clinicName: resolveActionClinicName(button) || null,
        chat_room_token: activeConversation || null,
        context_token: activeConversation || null,
        symptomText: getLatestUserMessageText() || null,
        actionPayload: button || null,
      },
    });
  };

  const handleBookAppointment = (place = null) => {
    const normalizedPlace = normalizeSuggestedClinic(place);

    navigate("/inclinic-fast-booking", {
      state: {
        source: normalizedPlace ? "ai_nearby_place" : "ai_clinic_suggestion",
        petId: resolvedPetId || null,
        userId: resolvedUserId || null,
        doctorId: resolveActionDoctorId(place) || null,
        clinicId: resolveActionClinicId(place) || null,
        suggestedClinic: normalizedPlace,
        chat_room_token: activeConversation || null,
        context_token: activeConversation || null,
        symptomText: getLatestUserMessageText() || null,
        actionPayload: place || null,
      },
    });
  };

  const handleActionButton = (button) => {
    const deeplink = String(button?.deeplink ?? "").trim().toLowerCase();
    const type = String(button?.type ?? "").trim().toLowerCase();

    if (isVideoConsultAction(button) || isDoctorBookingAction(button)) {
      handleVideoCall(button);
      return;
    }

    if (isClinicBookingAction(button)) {
      handleBookAppointment(button);
      return;
    }

    if (deeplink.includes("vet-at-home") || type === "vet_at_home") {
      window.location.href = button.deeplink;
      return;
    }

    if (deeplink.startsWith("http")) {
      window.open(button.deeplink, "_blank", "noopener,noreferrer");
      return;
    }

    if (String(button?.deeplink ?? "").startsWith("snoutiq://")) {
      window.location.href = button.deeplink;
    }
  };

  const handleLogout = () => {
    clearChatCache(resolvedUserId);
    clearAiAuthState();
    setIsSidebarOpen(false);
    setProfileMenuOpen(false);
    setMessages([]);
    setConversations([]);
    setActiveConversation(null);
    navigate("/", { replace: true });
  };

  const handleLogin = () => {
    setIsSidebarOpen(false);

    if (typeof onRequestLogin === "function") {
      onRequestLogin();
      return;
    }

    navigate("/ai", { replace: true });
  };

  const viewTimeline = () => {
    if (!canOpenProfileMenu || !resolvedPetId) return;
    setIsSidebarOpen(false);
    navigate(`/pet-lifeline/${resolvedPetId}`);
  };

  const handleViewProfile = () => {
    setProfileMenuOpen(false);
    navigate("/profile");
  };

  const handleOpenAppointmentPage = () => {
    setProfileMenuOpen(false);
    if (!resolvedPetId) return;

    navigate("/appointment-page", {
      state: {
        petId: resolvedPetId,
        userId: resolvedUserId,
        source: "nav_profile_dropdown",
      },
    });
  };

  const handleOpenFollowupPage = () => {
    setProfileMenuOpen(false);
    if (!resolvedPetId) return;

    navigate("/followup-page", {
      state: {
        petId: resolvedPetId,
        userId: resolvedUserId,
        source: "nav_profile_dropdown",
      },
    });
  };

  const sendMessageToApi = async ({ roomToken, inputText }) => {
    const payload = {
      user_id: resolvedUserId,
      pet_id: resolvedPetId || undefined,
      question: inputText,
      message: inputText,
      chat_room_token: roomToken,
      context_token: roomToken,
      pet_name: petDisplayName || undefined,
      pet_breed: petBreed || undefined,
      species: species || undefined,
      location: location || undefined,
    };

    const lowered = inputText.toLowerCase();
    if (
      lowered.includes("clinic") ||
      lowered.includes("hospital") ||
      lowered.includes("nearby") ||
      lowered.includes("vet near")
    ) {
      payload.place_type = lowered.includes("hospital") ? "hospital" : "clinic";
    }

    const response = await fetch(`${API_BASE}/chat/send`, {
      method: "POST",
      headers: createHeaders(token, true),
      body: JSON.stringify(payload),
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(data?.message || "Unable to send message.");
    }

    return data;
  };

  const handleSendMessage = async (forcedText = null) => {
    const inputText = String(forcedText ?? currentInput).trim();
    if (!inputText || isSending) return;

    if (!hasAuth || !profileReady) {
      const shouldBlock =
        typeof onRequireAccessFlow === "function"
          ? onRequireAccessFlow(inputText)
          : false;

      if (shouldBlock) {
        setCurrentInput("");
        return;
      }
    }

    const userMessage = {
      id: `user-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
      text: inputText,
      sender: "user",
      timestamp: new Date().toISOString(),
    };

    setErrorMessage("");
    setIsSending(true);
    setCurrentInput("");

    let roomToken = activeConversation;

    try {
      if (!roomToken) {
        const room = await createRoom(inputText.slice(0, 40) || "New Chat");
        roomToken = room.token;
      }

      const optimisticMessages = [...messages, userMessage];
      setMessages(optimisticMessages);

      updateConversationInState(roomToken, () => ({
        messages: optimisticMessages,
        loaded: true,
        summary: inputText,
        title: inputText.slice(0, 30) + (inputText.length > 30 ? "..." : ""),
      }));

      const apiPayload = await sendMessageToApi({ roomToken, inputText });
      const botMessage = buildBotMessageFromApi(apiPayload);
      const finalMessages = [...optimisticMessages, botMessage];

      shouldPinLatestResponseRef.current = true;
      setMessages(finalMessages);
      setActiveConversation(roomToken);

      updateConversationInState(roomToken, (conv) => ({
        messages: finalMessages,
        loaded: true,
        title:
          conv.title === "New Chat"
            ? inputText.slice(0, 30) + (inputText.length > 30 ? "..." : "")
            : conv.title,
        summary: botMessage.text,
        updatedAt: new Date().toISOString(),
      }));

      await loadRooms(roomToken);
    } catch (error) {
      setMessages((prev) => prev.filter((item) => item.id !== userMessage.id));
      setErrorMessage(error?.message || "Failed to send message.");
    } finally {
      setIsSending(false);
    }
  };

  const handleKeyPress = (event) => {
    if (event.key === "Enter" && !event.shiftKey) {
      event.preventDefault();
      handleSendMessage();
    }
  };

  useEffect(() => {
    if (!pendingQuestion || !hasAuth || !profileReady || isSending) return;

    handleSendMessage(pendingQuestion);

    if (typeof onPendingQuestionConsumed === "function") {
      onPendingQuestionConsumed();
    }
  }, [pendingQuestion, hasAuth, profileReady]);

  const statusMeta = !hasAuth
    ? {
        label: "First question unlocks login",
        className: "border-amber-200 bg-amber-50 text-amber-700",
      }
    : profileReady
      ? {
          label: "Profile ready",
          className: "border-emerald-200 bg-emerald-50 text-emerald-700",
        }
      : {
          label: "Complete pet profile",
          className: "border-indigo-200 bg-indigo-50 text-indigo-700",
        };

  const quickPrompts = [
    { label: "Nearby Clinics", prompt: "Find nearby clinics" },
    { label: "Nutrition", prompt: "What should I feed my dog?" },
    { label: "Symptoms", prompt: "My pet is vomiting what should I do?" },
    { label: "Training", prompt: "How to potty train a puppy?" },
  ];

  const topicCards = [
    {
      title: "Symptom Guidance",
      description:
        "Get fast direction on vomiting, toxic ingestion, choking, and other everyday care concerns.",
      icon: PawPrint,
      tone: "from-rose-500/15 via-white to-white",
      iconTone: "bg-rose-100 text-rose-600",
    },
    {
      title: "Care & Behaviour",
      description:
        "Ask about food, exercise, scratching, routines, and behaviour changes you are seeing.",
      icon: ShieldCheck,
      tone: "from-indigo-500/15 via-white to-white",
      iconTone: "bg-indigo-100 text-indigo-600",
    },
    {
      title: "Nearby Vet Help",
      description:
        "Ask for nearby clinics or hospitals and continue the flow with real place results.",
      icon: MapPin,
      tone: "from-emerald-500/15 via-white to-white",
      iconTone: "bg-emerald-100 text-emerald-600",
    },
  ];

  const renderNearbyPlaces = (message) => {
    const places = message?.structuredData?.places;
    const lookupSuccess = message?.structuredData?.success;
    const placeTypeLabel = String(
      message?.structuredData?.label ?? message?.structuredData?.type ?? "Clinic",
    ).trim();

    if (!Array.isArray(places) || places.length === 0 || lookupSuccess === false) {
      return null;
    }

    return (
      <div className="mt-4">
        <div className="mb-3 flex items-center justify-between gap-3">
          <div>
            <div className="text-sm font-semibold text-slate-900">
              Nearby {placeTypeLabel} Options
            </div>
            <div className="text-xs text-slate-500">
              Swipe to compare places and open the action you need.
            </div>
          </div>
          <div className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600">
            {places.length} found
          </div>
        </div>

        <div className="-mx-1 overflow-x-auto pb-2">
          <div className="flex min-w-full gap-3 px-1">
            {places.map((place, index) => (
              <div
                key={place?.place_id || `${place?.name || "place"}-${index}`}
                className="flex min-h-[285px] min-w-[270px] max-w-[270px] flex-col rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm"
              >
                <div className="flex items-start justify-between gap-3">
                  <div className="min-w-0">
                    <h4 className="line-clamp-2 text-[19px] font-semibold leading-7 text-slate-900">
                      {place?.name || "Clinic"}
                    </h4>
                  </div>
                  {place?.distance_km ? (
                    <span className="shrink-0 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-medium text-slate-600">
                      {Number(place.distance_km).toFixed(1)} km
                    </span>
                  ) : null}
                </div>

                <p className="mt-3 line-clamp-5 min-h-[96px] text-[14px] leading-6 text-slate-600">
                  {place?.address || "Address not available"}
                </p>

                <div className="mt-4 space-y-3">
                  <div className="text-sm text-slate-700">
                    <span className="font-semibold text-slate-900">Type:</span>{" "}
                    {placeTypeLabel} visit available
                  </div>

                  {place?.rating ? (
                    <div className="flex items-center gap-2 text-sm font-semibold text-slate-900">
                      <Star size={15} className="fill-amber-400 text-amber-400" />
                      {place.rating}
                    </div>
                  ) : null}

                  {place?.active_hours_status ? (
                    <div className="text-xs font-medium text-emerald-700">
                      {place.active_hours_status}
                    </div>
                  ) : null}
                </div>

                {place?.phone ? (
                  <a
                    href={`tel:${place.phone}`}
                    className="mt-4 inline-flex items-center gap-2 text-xs font-semibold text-emerald-700 hover:text-emerald-800"
                  >
                    <Phone size={14} />
                    Call Clinic
                  </a>
                ) : null}

                <div className="mt-auto flex gap-2 pt-5">
                  {place?.maps_link ? (
                    <a
                      href={place.maps_link}
                      target="_blank"
                      rel="noreferrer"
                      className="inline-flex flex-1 items-center justify-center gap-2 rounded-2xl border border-emerald-600 bg-white px-3 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50"
                    >
                      <ExternalLink size={15} />
                      Maps
                    </a>
                  ) : null}

                  <button
                    type="button"
                    onClick={() => handleBookAppointment(place)}
                    className="inline-flex flex-1 items-center justify-center gap-2 rounded-2xl bg-emerald-700 px-3 py-3 text-sm font-semibold text-white transition hover:bg-emerald-800"
                  >
                    <Calendar size={15} />
                    Book Consult
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    );
  };

  const renderRichResponse = (message) => {
    const rich = message?.richContent;
    if (!rich) {
      return <p className="whitespace-pre-wrap text-sm leading-6">{message.text}</p>;
    }

    const serviceCards = Array.isArray(rich.serviceCards) ? rich.serviceCards : [];
    const actionButtons = Array.isArray(rich.buttons) ? rich.buttons : [];

    return (
      <div className="space-y-3">
        {rich.banner?.title || rich.banner?.subtitle ? (
          <div className="rounded-2xl border border-indigo-200 bg-indigo-50 p-3">
            {rich.banner?.eyebrow ? (
              <div className="text-[11px] font-semibold uppercase tracking-wide text-indigo-700">
                {rich.banner.eyebrow}
              </div>
            ) : null}
            {rich.banner?.title ? (
              <div className="mt-1 text-base font-semibold text-slate-900">
                {rich.banner.title}
              </div>
            ) : null}
            {rich.banner?.subtitle ? (
              <div className="mt-1 text-sm text-slate-700">{rich.banner.subtitle}</div>
            ) : null}
          </div>
        ) : null}

        {(rich.healthScore?.value !== null && rich.healthScore?.value !== undefined) ||
        rich.healthScore?.label ? (
          <div className="rounded-2xl border border-amber-200 bg-amber-50 p-3">
            <div className="flex items-center justify-between gap-3">
              <div>
                <div className="text-sm font-semibold text-slate-900">
                  Pet Health Score
                </div>
                {rich.healthScore?.label ? (
                  <div className="mt-1 text-sm text-amber-900">
                    {rich.healthScore.label}
                  </div>
                ) : null}
                {rich.healthScore?.subtitle ? (
                  <div className="mt-1 text-xs text-slate-600">
                    {rich.healthScore.subtitle}
                  </div>
                ) : null}
              </div>
              {rich.healthScore?.value !== null && rich.healthScore?.value !== undefined ? (
                <div className="rounded-2xl bg-white px-3 py-2 text-lg font-bold text-slate-900 shadow-sm">
                  {rich.healthScore.value}/100
                </div>
              ) : null}
            </div>
          </div>
        ) : null}

        {message.text ? (
          <p className="whitespace-pre-wrap text-sm leading-6">{message.text}</p>
        ) : null}

        {rich.timeSensitivity || rich.severity || rich.routing ? (
          <div className="flex flex-wrap gap-2">
            {rich.timeSensitivity ? (
              <span className="rounded-full border border-orange-200 bg-orange-50 px-3 py-1 text-xs font-medium text-orange-700">
                Time: {rich.timeSensitivity}
              </span>
            ) : null}
            {rich.severity ? (
              <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700 capitalize">
                Severity: {rich.severity}
              </span>
            ) : null}
            {rich.routing ? (
              <span className="rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700">
                Route: {rich.routing.replaceAll("_", " ")}
              </span>
            ) : null}
          </div>
        ) : null}

        {rich.doNow ? (
          <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-3">
            <div className="mb-1 flex items-center gap-2 text-sm font-semibold text-emerald-900">
              <CheckCircle2 size={16} />
              What to do now
            </div>
            <p className="text-sm leading-6 text-emerald-900">{rich.doNow}</p>
          </div>
        ) : null}

        {rich.diagnosisSummary ? (
          <div className="rounded-2xl border border-blue-200 bg-blue-50 p-3">
            <div className="mb-1 flex items-center gap-2 text-sm font-semibold text-blue-900">
              <Stethoscope size={16} />
              Summary
            </div>
            <p className="text-sm leading-6 text-blue-900">{rich.diagnosisSummary}</p>
          </div>
        ) : null}

        <SectionList
          title="Safe to do while waiting"
          items={rich.safeToDo}
          icon={ShieldCheck}
          tone="green"
        />

        <SectionList
          title="What to watch"
          items={rich.whatToWatch}
          icon={AlertCircle}
          tone="red"
        />

        {rich.beReadyToTellVet ? (
          <div className="rounded-2xl border border-slate-200 bg-slate-50 p-3">
            <div className="mb-1 flex items-center gap-2 text-sm font-semibold text-slate-900">
              <Activity size={16} />
              Be ready to tell the vet
            </div>
            <p className="text-sm leading-6 text-slate-700">{rich.beReadyToTellVet}</p>
          </div>
        ) : null}

        {message.sender === "bot" ? renderNearbyPlaces(message) : null}

        {rich.followUpQuestion?.question ? (
          <div className="rounded-2xl border border-violet-200 bg-violet-50 p-3">
            {rich.followUpQuestion?.label ? (
              <div className="text-sm font-semibold text-violet-900">
                {rich.followUpQuestion.label}
              </div>
            ) : null}
            <p className="mt-1 text-sm leading-6 text-violet-900">
              {rich.followUpQuestion.question}
            </p>
            {rich.followUpQuestion?.options?.length ? (
              <div className="mt-3 flex flex-wrap gap-2">
                {rich.followUpQuestion.options.map((option) => (
                  <button
                    key={option}
                    type="button"
                    onClick={() => handleSendMessage(option)}
                    className="rounded-full border border-violet-200 bg-white px-3 py-1.5 text-xs font-medium text-violet-700 transition hover:bg-violet-100"
                  >
                    {option}
                  </button>
                ))}
              </div>
            ) : null}
          </div>
        ) : null}

        {/* {actionButtons.length > 0 ? (
          <div className="mt-4 flex flex-wrap gap-2">
            {actionButtons.map((button, index) => (
              <button
                key={`${button?.label || "action"}-${index}`}
                type="button"
                onClick={() => handleActionButton(button)}
                className="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
              >
                {button?.type === "clinic" ? <Calendar size={16} /> : <Video size={16} />}
                {getActionButtonLabel(button)}
              </button>
            ))}
          </div>
        ) : null} */}

        {serviceCards.length > 0 ? (
          <div className="space-y-3">
            {serviceCards.map((card, index) => (
              <div
                key={`${card?.title || "service"}-${index}`}
                className="rounded-2xl border border-slate-200 bg-slate-50 p-3"
              >
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <div className="flex flex-wrap items-center gap-2">
                      <h4 className="text-sm font-semibold text-slate-900">
                        {card?.title || "Service"}
                      </h4>
                      {card?.badge ? (
                        <span className="rounded-full bg-white px-2 py-1 text-[11px] font-medium text-slate-600">
                          {card.badge}
                        </span>
                      ) : null}
                    </div>
                    {card?.guarantee ? (
                      <p className="mt-1 text-xs text-slate-600">{card.guarantee}</p>
                    ) : null}
                  </div>
                  <div className="text-right">
                    {card?.price ? (
                      <div className="text-sm font-semibold text-slate-900">{card.price}</div>
                    ) : null}
                    {card?.orig_price ? (
                      <div className="text-xs text-slate-400 line-through">
                        {card.orig_price}
                      </div>
                    ) : null}
                  </div>
                </div>

                {Array.isArray(card?.bullets) && card.bullets.length > 0 ? (
                  <ul className="mt-3 space-y-2">
                    {card.bullets.map((bullet, bulletIndex) => (
                      <li
                        key={`${card?.title || "service"}-bullet-${bulletIndex}`}
                        className="flex items-start gap-2 text-sm text-slate-700"
                      >
                        <span className="mt-[9px] h-1.5 w-1.5 shrink-0 rounded-full bg-slate-400" />
                        <span>{bullet}</span>
                      </li>
                    ))}
                  </ul>
                ) : null}

                {card?.cta?.label ? (
                  <button
                    type="button"
                    onClick={() => handleActionButton(card.cta)}
                    className="mt-3 inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50"
                  >
                    <ExternalLink size={14} />
                    {getActionButtonLabel(card.cta)}
                  </button>
                ) : null}
              </div>
            ))}
          </div>
        ) : null}

      </div>
    );
  };

  const getConversationPreview = (conversation) => {
    const previewText =
      conversation?.summary ||
      conversation?.messages?.[conversation.messages.length - 1]?.text ||
      "Start a new pet health conversation";

    return previewText.length > 70
      ? `${previewText.slice(0, 70)}...`
      : previewText;
  };

  return (
    <div className="flex h-screen overflow-hidden bg-gray-100">
      <div
        className={`fixed inset-0 z-30 bg-slate-900/40 transition-opacity duration-300 lg:hidden ${
          isSidebarOpen ? "opacity-100" : "pointer-events-none opacity-0"
        }`}
        onClick={() => setIsSidebarOpen(false)}
      />

      <div
        className={`fixed inset-y-0 left-0 z-40 flex w-72 max-w-[85vw] flex-col border-r border-gray-200 bg-white transition-transform duration-300 ease-out lg:static lg:z-auto lg:w-80 lg:max-w-none lg:translate-x-0 ${
          isSidebarOpen ? "translate-x-0 shadow-2xl" : "-translate-x-full"
        }`}
      >
        <div className="flex items-center justify-between border-b border-gray-200 px-4 py-4 lg:hidden">
          <div>
            <p className="text-sm font-semibold text-gray-800">Conversations</p>
            <p className="text-xs text-gray-500">Pick up where you left off</p>
          </div>
          <button
            type="button"
            onClick={() => setIsSidebarOpen(false)}
            className="rounded-full p-2 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-800"
            aria-label="Close sidebar"
          >
            <X size={18} />
          </button>
        </div>

        <div className="space-y-2 border-b border-gray-200 p-4">
          <button
            onClick={createNewConversation}
            className="flex w-full items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-white transition-colors hover:bg-indigo-700"
          >
            <MessageSquare size={20} />
            New Chat
          </button>

          {canOpenProfileMenu ? (
            <button
              onClick={viewTimeline}
              disabled={!resolvedPetId}
              className={`flex w-full items-center gap-2 rounded-lg px-4 py-2 transition-colors ${
                resolvedPetId
                  ? "bg-gray-100 text-gray-700 hover:bg-gray-200"
                  : "cursor-not-allowed bg-gray-100 text-gray-400"
              }`}
            >
              <Clock size={20} />
              View Pet Health line
            </button>
          ) : null}
        </div>

        <div className="flex-1 overflow-y-auto p-2">
          <h3 className="mb-2 px-2 text-sm font-semibold text-gray-600">
            Recent Conversations
          </h3>

          {isRoomsLoading ? (
            <div className="flex items-center gap-2 px-2 py-4 text-sm text-slate-500">
              <Loader2 size={16} className="animate-spin" />
              Loading conversations...
            </div>
          ) : conversations.length === 0 ? (
            <p className="px-2 text-sm text-gray-400">No conversations yet</p>
          ) : (
            conversations.map((conv) => (
              <div
                key={conv.token || conv.id}
                onClick={() => selectConversation(conv.token)}
                className={`m-1 cursor-pointer rounded-lg p-3 transition-colors ${
                  activeConversation === conv.token
                    ? "border border-indigo-300 bg-indigo-100"
                    : "hover:bg-gray-100"
                }`}
              >
                <div className="flex items-start justify-between gap-2">
                  <p className="truncate text-sm font-medium text-slate-800">
                    {conv.title}
                  </p>
                  {conv.turns ? (
                    <span className="rounded-full bg-white px-2 py-0.5 text-[10px] text-slate-500">
                      {conv.turns}
                    </span>
                  ) : null}
                </div>
                <p className="mt-1 text-xs leading-5 text-gray-500">
                  {getConversationPreview(conv)}
                </p>
                <p className="mt-1 text-[11px] text-gray-400">
                  {formatTime(conv.updatedAt || conv.createdAt)}
                </p>
              </div>
            ))
          )}
        </div>

        <div className="border-t border-gray-200 p-4">
          {hasAuth ? (
            <button
              type="button"
              onClick={handleLogout}
              className="flex w-full items-center justify-between rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-600 transition-colors hover:border-red-200 hover:bg-red-100"
            >
              <span className="flex items-center gap-2">
                <LogOut size={18} />
                Logout
              </span>
            </button>
          ) : (
            <button
              type="button"
              onClick={handleLogin}
              className="flex w-full items-center justify-between rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm font-medium text-indigo-600 transition-colors hover:border-indigo-200 hover:bg-indigo-100"
            >
              <span className="flex items-center gap-2">
                <LogIn size={18} />
                Login
              </span>
            </button>
          )}
        </div>
      </div>

      <div className="flex min-w-0 flex-1 flex-col">
        <div className="border-b border-gray-200 bg-white p-4 shadow-sm">
          <div className="flex items-center justify-between gap-3">
            <div className="flex min-w-0 items-center gap-3">
              <button
                type="button"
                onClick={() => setIsSidebarOpen(true)}
                className="rounded-xl border border-gray-200 p-2 text-gray-600 transition-colors hover:bg-gray-50 hover:text-gray-900 lg:hidden"
                aria-label="Open sidebar"
              >
                <Menu size={20} />
              </button>

              <img
                src={snoutiq_app_icon}
                alt="Snoutiq"
                className="h-10 w-10 rounded-xl object-cover"
              />

              <div className="min-w-0">
                <h1 className="truncate text-lg font-bold text-slate-900">
                  Snoutiq AI Assistant
                </h1>
                <p className="text-sm text-slate-500">Ask anything about your pet</p>
              </div>
            </div>

            {canOpenProfileMenu ? (
              <div className="relative">
                <button
                  type="button"
                  onClick={(event) => {
                    event.stopPropagation();
                    if (!canOpenProfileMenu) return;
                    setProfileMenuOpen((prev) => !prev);
                  }}
                  className="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-gray-50"
                >
                  {petProfileImageUrl ? (
                    <img
                      src={petProfileImageUrl}
                      alt={petDisplayName || ownerDisplayName || "Pet"}
                      className="h-9 w-9 rounded-full object-cover ring-2 ring-indigo-100"
                    />
                  ) : (
                    <div className="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                      <User size={18} />
                    </div>
                  )}
                  <div className="hidden text-left sm:block">
                    <div className="max-w-[120px] truncate text-sm font-semibold text-slate-900">
                      {ownerDisplayName || "Profile"}
                    </div>
                    <div className="max-w-[120px] truncate text-xs text-slate-500">
                      {petDisplayName || "Pet Parent"}
                    </div>
                  </div>
                  <ChevronDown size={16} className="text-slate-500" />
                </button>

                {profileMenuOpen ? (
                  <div
                    className="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg"
                    onClick={(event) => event.stopPropagation()}
                  >
                    <div className="border-b border-slate-100 px-4 py-3">
                      <div className="flex items-center gap-3">
                        {petProfileImageUrl ? (
                          <img
                            src={petProfileImageUrl}
                            alt={petDisplayName || ownerDisplayName || "Pet"}
                            className="h-11 w-11 rounded-full object-cover ring-2 ring-indigo-100"
                          />
                        ) : (
                          <div className="flex h-11 w-11 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                            <User size={18} />
                          </div>
                        )}
                        <div className="min-w-0">
                          <div className="truncate text-sm font-semibold text-slate-900">
                            {ownerDisplayName || "Profile"}
                          </div>
                          <div className="truncate text-xs text-slate-500">
                            {petDisplayName || "Pet Parent"}
                          </div>
                        </div>
                      </div>
                    </div>

                    <button
                      type="button"
                      onClick={handleViewProfile}
                      className="flex w-full items-center gap-2 px-4 py-3 text-sm text-slate-700 hover:bg-gray-50"
                    >
                      <User size={16} />
                      View Profile
                    </button>

                    <button
                      type="button"
                      onClick={handleOpenAppointmentPage}
                      disabled={!resolvedPetId}
                      className={`flex w-full items-center gap-2 px-4 py-3 text-sm hover:bg-gray-50 ${
                        resolvedPetId
                          ? "text-slate-700"
                          : "cursor-not-allowed text-slate-400"
                      }`}
                    >
                      <Calendar size={16} />
                      Appointment
                    </button>

                    <button
                      type="button"
                      onClick={handleOpenFollowupPage}
                      disabled={!resolvedPetId}
                      className={`flex w-full items-center gap-2 px-4 py-3 text-sm hover:bg-gray-50 ${
                        resolvedPetId
                          ? "text-slate-700"
                          : "cursor-not-allowed text-slate-400"
                      }`}
                    >
                      <MessageSquare size={16} />
                      Followup
                    </button>

                    <button
                      type="button"
                      onClick={handleLogout}
                      className="flex w-full items-center gap-2 border-t border-slate-100 px-4 py-3 text-sm text-red-600 hover:bg-red-50"
                    >
                      <LogOut size={16} />
                      Logout
                    </button>
                  </div>
                ) : null}
              </div>
            ) : null}
          </div>
        </div>

        <div className="flex-1 overflow-y-auto bg-white p-4">
          <div
            className={`mx-auto flex max-w-4xl flex-col gap-4 ${
              messages.length === 0 ? "min-h-full justify-center" : ""
            }`}
          >
            {messages.length === 0 ? (
              <div className="flex min-h-[60vh] flex-col items-center justify-center px-4 text-center">
                <div className="mb-4 inline-flex items-center gap-2 rounded-full border border-indigo-100 px-3 py-1.5 text-xs font-semibold text-indigo-700">
                  <Sparkles size={14} />
                  Smart pet care assistant
                </div>

                <div className="flex h-16 w-16 items-center justify-center rounded-3xl bg-indigo-50 text-indigo-700">
                  <PawPrint size={28} />
                </div>

                <h2 className="mt-5 text-3xl font-bold text-slate-900 sm:text-4xl">
                  Welcome to Snoutiq AI
                </h2>

                <p className="mt-3 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
                  Get instant guidance for symptoms, food, behaviour, exercise,
                  and nearby clinic help. Ask your first question to begin.
                </p>

                <div className="mt-6 flex flex-wrap items-center justify-center gap-2">
                  <div className="inline-flex items-center gap-2 rounded-full border border-emerald-100 px-3 py-1.5 text-xs font-medium text-emerald-700">
                    <ShieldCheck size={14} />
                    Safe pet guidance
                  </div>
                  <div className="inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700">
                    <User size={14} />
                    {ownerDisplayName || "Pet parent"}
                  </div>
                  <div className="inline-flex items-center gap-2 rounded-full border border-indigo-100 px-3 py-1.5 text-xs font-medium text-indigo-700">
                    <PawPrint size={14} />
                    {petDisplayName || "Your pet"}
                  </div>
                  <div
                    className={`inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-medium ${statusMeta.className}`}
                  >
                    {statusMeta.label}
                  </div>
                </div>

                <div className="mt-8 grid w-full max-w-4xl gap-4 md:grid-cols-3">
                  {topicCards.map((card) => {
                    const Icon = card.icon;
                    return (
                      <div
                        key={card.title}
                        className={`rounded-3xl border border-slate-200 bg-gradient-to-br ${card.tone} p-5 text-left shadow-sm`}
                      >
                        <div
                          className={`mb-4 inline-flex h-11 w-11 items-center justify-center rounded-2xl ${card.iconTone}`}
                        >
                          <Icon size={20} />
                        </div>
                        <h3 className="text-base font-semibold text-slate-900">
                          {card.title}
                        </h3>
                        <p className="mt-2 text-sm leading-6 text-slate-600">
                          {card.description}
                        </p>
                      </div>
                    );
                  })}
                </div>

                <div className="mt-8 flex w-full max-w-3xl flex-wrap justify-center gap-3">
                  {quickPrompts.map((item) => (
                    <button
                      key={item.label}
                      type="button"
                      onClick={() => handleSendMessage(item.prompt)}
                      className="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm transition hover:border-indigo-200 hover:text-indigo-700"
                    >
                      {item.label}
                    </button>
                  ))}
                </div>
              </div>
            ) : null}

            {errorMessage ? (
              <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {errorMessage}
              </div>
            ) : null}

            {isHistoryLoading ? (
              <div className="flex items-center gap-2 text-sm text-slate-500">
                <Loader2 size={16} className="animate-spin" />
                Loading messages...
              </div>
            ) : null}

            {messages.map((message, index) => {
              const isLatestBotMessage =
                message.sender === "bot" && index === messages.length - 1;

              return (
              <div
                key={message.id}
                ref={isLatestBotMessage ? latestBotMessageRef : null}
                className={`flex ${message.sender === "user" ? "justify-end" : "justify-start"}`}
              >
                <div
                  className={`max-w-[92%] rounded-3xl px-4 py-3 shadow-sm ${
                    message.sender === "user"
                      ? "bg-indigo-600 text-white"
                      : "border border-slate-200 bg-white text-slate-800"
                  }`}
                >
                  {message.sender === "bot" ? (
                    renderRichResponse(message)
                  ) : (
                    <p className="whitespace-pre-wrap text-sm leading-6">{message.text}</p>
                  )}

                  <div className="mt-2 text-[11px] opacity-60">
                    {formatTime(message.timestamp)}
                  </div>
                </div>
              </div>
              );
            })}

            {isSending ? (
              <div className="flex justify-start">
                <div className="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-500 shadow-sm">
                  <Loader2 size={16} className="animate-spin" />
                  Snoutiq is thinking...
                </div>
              </div>
            ) : null}
          </div>
        </div>

        <div className="border-t border-slate-200 bg-white p-4">
          <div className="mx-auto flex max-w-4xl items-end gap-3 rounded-3xl border border-slate-200 bg-slate-50 p-3 shadow-sm">
            <textarea
              rows={1}
              value={currentInput}
              onChange={(event) => setCurrentInput(event.target.value)}
              onKeyDown={handleKeyPress}
              placeholder="Ask anything about your pet..."
              className="max-h-40 min-h-[48px] flex-1 resize-none rounded-2xl border-0 bg-transparent px-3 py-2 text-sm text-slate-800 outline-none placeholder:text-slate-400"
            />

            <button
              type="button"
              onClick={() => handleSendMessage()}
              disabled={!currentInput.trim() || isSending}
              className="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600 text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {isSending ? <Loader2 size={18} className="animate-spin" /> : <Send size={18} />}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
