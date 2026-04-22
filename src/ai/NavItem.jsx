import React, { useEffect, useMemo, useRef, useState } from "react";
import {
  Calendar,
  Heart,
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
  ChevronRight,
  Clock,
  ChevronDown,
} from "lucide-react";
import { useNavigate } from "react-router-dom";
import { clearAiAuthState } from "./AiAuth";
import snoutiq_app_icon from "../assets/snoutiq_app_icon.png";

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

export default function NavItem({
  authState,
  pendingQuestion,
  onRequireAccessFlow,
  onPendingQuestionConsumed,
  onRequestLogin,
}) {
  const navigate = useNavigate();
  const [messages, setMessages] = useState([]);
  const [currentInput, setCurrentInput] = useState("");
  const [conversations, setConversations] = useState([]);
  const [activeConversation, setActiveConversation] = useState(null);
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);
  const [profileMenuOpen, setProfileMenuOpen] = useState(false);
  const messagesEndRef = useRef(null);

  const currentUser = authState?.user || {};
  const currentPet =
    currentUser?.pet && typeof currentUser.pet === "object"
      ? currentUser.pet
      : Array.isArray(currentUser?.pets) && currentUser.pets.length > 0
        ? currentUser.pets[0]
        : null;

  const resolvedPetId =
    currentPet?.id ??
    currentPet?.pet_id ??
    currentUser?.pet_id ??
    currentUser?.pet?.id ??
    currentUser?.pet?.pet_id ??
    "";

  const resolvedUserId =
    currentUser?.id ??
    currentUser?.user_id ??
    "";

  const hasAuth = useMemo(() => {
    const user = authState?.user || {};
    return Boolean(authState?.token && (user?.id || user?.user_id));
  }, [authState]);

  const profileReady = useMemo(() => {
    return hasUsablePetProfile(authState);
  }, [authState]);

  const questionDatabase = {
    "what should i feed my dog": {
      type: "health",
      answer:
        "Dogs need a balanced diet of protein, carbohydrates, fats, vitamins, and minerals. High-quality commercial dog food that meets AAFCO standards is recommended. The exact food depends on your dog's age, size, and health conditions.",
    },
    "how often should i take my cat to the vet": {
      type: "health",
      answer:
        "Kittens need visits every 3-4 weeks until 16 weeks old. Adult cats should have annual check-ups. Senior cats (7+ years) benefit from semi-annual visits. Always visit immediately if you notice any health concerns.",
    },
    "my pet is vomiting what should i do": {
      type: "emergency",
      answer:
        "If vomiting persists more than 24 hours, contains blood, or is accompanied by lethargy, diarrhea, or loss of appetite, contact your vet immediately. Withhold food for 12 hours but provide small amounts of water. Then introduce a bland diet.",
    },
    "how to potty train a puppy": {
      type: "training",
      answer:
        "Establish a routine with frequent bathroom breaks (after waking, eating, playing). Use positive reinforcement with treats and praise when they eliminate outside. Supervise indoors and watch for signs like sniffing or circling. Never punish accidents.",
    },
    "why is my cat scratching furniture": {
      type: "behavior",
      answer:
        "Cats scratch to mark territory, stretch muscles, and shed nail sheaths. Provide appropriate scratching posts near furniture they're targeting. Use deterrents like double-sided tape on furniture and attractants like catnip on posts.",
    },
    "how much exercise does my dog need": {
      type: "care",
      answer:
        "Exercise needs vary by breed, age, and health. Most dogs need 30 minutes to 2 hours of daily activity. High-energy breeds need more, while some breeds need less intense exercise.",
    },
    "should i get pet insurance": {
      type: "general",
      answer:
        "Pet insurance can help manage unexpected veterinary costs. Compare providers based on coverage, deductibles, reimbursement rates, and exclusions.",
    },
    "my pet ate something toxic": {
      type: "emergency",
      answer:
        "Contact your veterinarian or animal poison control immediately. Have information ready: what was ingested, how much, when, and your pet's weight. Do not induce vomiting unless instructed by a professional.",
    },
    "my dog is choking": {
      type: "emergency",
      answer:
        "If your dog is conscious but choking, carefully open their mouth and remove the obstruction if visible and easily reachable. Seek immediate veterinary care even if you dislodge the object.",
    },
  };

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  };

  useEffect(() => {
    scrollToBottom();
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

  const createNewConversation = () => {
    const newConv = {
      id: Date.now(),
      title: "New Chat",
      messages: [],
    };
    setConversations((prev) => [newConv, ...prev]);
    setActiveConversation(newConv.id);
    setIsSidebarOpen(false);
    return newConv.id;
  };

  const selectConversation = (convId) => {
    const conv = conversations.find((c) => c.id === convId);
    if (conv) {
      setActiveConversation(convId);
      setMessages(conv.messages);
      setIsSidebarOpen(false);
    }
  };

  const updateConversationTitle = (convId, newTitle) => {
    setConversations((convs) =>
      convs.map((conv) =>
        conv.id === convId
          ? {
              ...conv,
              title:
                newTitle.slice(0, 30) + (newTitle.length > 30 ? "..." : ""),
            }
          : conv,
      ),
    );
  };

  const calculateSimilarity = (str1, str2) => {
    const words1 = str1.split(" ");
    const words2 = str2.split(" ");
    const matchingWords = words1.filter((word) => words2.includes(word));
    return matchingWords.length / Math.max(words1.length, words2.length);
  };

  const findMatchingQuestion = (userInput) => {
    const input = userInput.toLowerCase().trim();

    for (const [question, data] of Object.entries(questionDatabase)) {
      if (
        input.includes(question.toLowerCase()) ||
        question.toLowerCase().includes(input) ||
        calculateSimilarity(input, question.toLowerCase()) > 0.7
      ) {
        return data;
      }
    }
    return null;
  };

  const buildBotResponse = (inputText) => {
    const matchedQuery = findMatchingQuestion(inputText);

    return {
      id: Date.now() + 1,
      text: matchedQuery
        ? matchedQuery.answer
        : "I'm sorry, I don't have information about that specific pet concern. Please try rephrasing your question or contact a veterinary professional for assistance.",
      sender: "bot",
      timestamp: new Date(),
      queryType: matchedQuery ? matchedQuery.type : null,
      hasActions: matchedQuery
        ? matchedQuery.type === "health" || matchedQuery.type === "emergency"
        : false,
    };
  };

  const appendConversationMessages = (newMessages, firstQuestionText) => {
    if (activeConversation) {
      setMessages(newMessages);
      setConversations((convs) =>
        convs.map((conv) =>
          conv.id === activeConversation
            ? { ...conv, messages: newMessages }
            : conv,
        ),
      );

      if (messages.length === 0) {
        updateConversationTitle(activeConversation, firstQuestionText);
      }
      return;
    }

    const newConvId = createNewConversation();

    setTimeout(() => {
      setActiveConversation(newConvId);
      setMessages(newMessages);
      setConversations((prev) =>
        prev.map((conv) =>
          conv.id === newConvId ? { ...conv, messages: newMessages } : conv,
        ),
      );
      updateConversationTitle(newConvId, firstQuestionText);
    }, 0);
  };

  const handleVideoCall = () => {
    alert("Connecting you with a veterinary professional...");
    window.open("/payment", "_blank");
  };

  const handleBookAppointment = () => {
    alert("Redirecting to appointment booking...");
    window.open("/Appointment", "_blank");
  };

  const handleLogout = () => {
  clearAiAuthState();
  setIsSidebarOpen(false);
  setProfileMenuOpen(false);
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

  const handleSendMessage = (forcedText = null) => {
    const inputText = String(forcedText ?? currentInput).trim();
    if (!inputText) return;

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
      id: Date.now(),
      text: inputText,
      sender: "user",
      timestamp: new Date(),
    };

    const botResponse = buildBotResponse(inputText);
    const newMessages = [...messages, userMessage, botResponse];

    appendConversationMessages(newMessages, inputText);
    setCurrentInput("");
  };

  const handleKeyPress = (e) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      handleSendMessage();
    }
  };

  useEffect(() => {
    if (!pendingQuestion || !hasAuth || !profileReady) return;

    handleSendMessage(pendingQuestion);

    if (typeof onPendingQuestionConsumed === "function") {
      onPendingQuestionConsumed();
    }
  }, [pendingQuestion, hasAuth, profileReady]);

  const petDisplayName = String(
    currentPet?.name ?? currentPet?.pet_name ?? currentUser?.pet_name ?? "",
  ).trim();
  const ownerDisplayName = String(
    currentUser?.pet_owner_name ??
      currentUser?.owner_name ??
      currentUser?.name ??
      "",
  ).trim();
  const petProfileImageUrl = resolvePetImageUrl(currentPet, currentUser?.pet, currentUser);
  const hasProfileIdentity = Boolean(
    ownerDisplayName || petDisplayName || petProfileImageUrl,
  );
  const canOpenProfileMenu = hasAuth && hasProfileIdentity;

  useEffect(() => {
    if (!canOpenProfileMenu && profileMenuOpen) {
      setProfileMenuOpen(false);
    }
  }, [canOpenProfileMenu, profileMenuOpen]);

  const quickPrompts = [
    { label: "Nutrition", prompt: "What should I feed my dog?" },
    { label: "Symptoms", prompt: "My pet is vomiting what should I do?" },
    { label: "Training", prompt: "How to potty train a puppy?" },
    { label: "Prevention", prompt: "Should I get pet insurance?" },
  ];

  const topicCards = [
    {
      title: "Symptom Guidance",
      description:
        "Get fast direction on vomiting, toxic ingestion, choking, and other everyday care concerns.",
      icon: Heart,
      tone: "from-rose-500/15 via-white to-white",
      iconTone: "bg-rose-100 text-rose-600",
    },
    {
      title: "Care & Behaviour",
      description:
        "Ask about food, exercise, scratching, routines, and behaviour changes you are seeing.",
      icon: PawPrint,
      tone: "from-indigo-500/15 via-white to-white",
      iconTone: "bg-indigo-100 text-indigo-600",
    },
    {
      title: "Vet Escalation",
      description:
        "Switch to video consult or appointment booking when a question needs expert follow-up.",
      icon: Video,
      tone: "from-emerald-500/15 via-white to-white",
      iconTone: "bg-emerald-100 text-emerald-600",
    },
  ];

  const getConversationPreview = (conversation) => {
    const previewText =
      conversation?.messages?.[conversation.messages.length - 1]?.text ||
      "Start a new pet health conversation";
    return previewText.length > 70
      ? `${previewText.slice(0, 70)}...`
      : previewText;
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

        <div className="p-4 border-b border-gray-200 space-y-2">
  <button
    onClick={createNewConversation}
    className="w-full flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
  >
    <MessageSquare size={20} />
    New Chat
  </button>

  {canOpenProfileMenu ? (
    <button
      onClick={viewTimeline}
      disabled={!resolvedPetId}
      className={`w-full flex items-center gap-2 px-4 py-2 rounded-lg transition-colors ${
        resolvedPetId
          ? "bg-gray-100 text-gray-700 hover:bg-gray-200"
          : "bg-gray-100 text-gray-400 cursor-not-allowed"
      }`}
    >
      <Clock size={20} />
      View Timeline
    </button>
  ) : null}
</div>

        <div className="flex-1 overflow-y-auto">
          <div className="p-2">
            <h3 className="text-sm font-semibold text-gray-600 mb-2 px-2">
              Recent Conversations
            </h3>
            {conversations.length === 0 ? (
              <p className="text-sm text-gray-400 px-2">No conversations yet</p>
            ) : (
              conversations.map((conv) => (
                <div
                  key={conv.id}
                  onClick={() => selectConversation(conv.id)}
                  className={`p-3 m-1 rounded-lg cursor-pointer transition-colors ${
                    activeConversation === conv.id
                      ? "bg-indigo-100 border border-indigo-300"
                      : "hover:bg-gray-100"
                  }`}
                >
                  <p className="text-sm font-medium truncate">{conv.title}</p>
                  <p className="text-xs text-gray-500">
                    {conv.messages.length} messages
                  </p>
                </div>
              ))
            )}
          </div>
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
        <div className="bg-white p-4 border-b border-gray-200 shadow-sm">
  <div className="flex items-center justify-between gap-3">
    <div className="flex items-center gap-3 min-w-0">
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
        <p className="text-sm text-slate-500">
          Ask anything about your pet
        </p>
      </div>
    </div>

    {canOpenProfileMenu ? (
    <div className="relative">
      <button
        type="button"
        onClick={(e) => {
          e.stopPropagation();
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
        <div className="hidden sm:block text-left">
          <div className="max-w-[120px] truncate text-sm font-semibold text-slate-900">
            {ownerDisplayName || "Profile"}
          </div>
          <div className="max-w-[120px] truncate text-xs text-slate-500">
            {petDisplayName || "Pet Parent"}
          </div>
        </div>
        <ChevronDown size={16} className="text-slate-500" />
      </button>

      {profileMenuOpen && canOpenProfileMenu ? (
        <div
          className="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg"
          onClick={(e) => e.stopPropagation()}
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
                  and emergency situations. Ask your first question to begin.
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
                </div>
              </div>
            ) : null}

            {messages.map((message) => (
              <div
                key={message.id}
                className={`flex ${
                  message.sender === "user" ? "justify-end" : "justify-start"
                }`}
              >
                <div
                  className={`max-w-[85%] rounded-3xl px-4 py-3 shadow-sm ${
                    message.sender === "user"
                      ? "bg-indigo-600 text-white"
                      : "bg-white border border-slate-200 text-slate-800"
                  }`}
                >
                  <p className="whitespace-pre-wrap text-sm leading-6">
                    {message.text}
                  </p>

                  {message.sender === "bot" && message.hasActions ? (
                    <div className="mt-4 flex flex-wrap gap-2">
                      <button
                        type="button"
                        onClick={handleVideoCall}
                        className="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                      >
                        <Video size={16} />
                        Start Video Call
                      </button>

                      <button
                        type="button"
                        onClick={handleBookAppointment}
                        className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                      >
                        <Calendar size={16} />
                        Book Appointment
                      </button>
                    </div>
                  ) : null}
                </div>
              </div>
            ))}

            <div ref={messagesEndRef} />
          </div>
        </div>

        <div className="border-t border-slate-200 bg-white p-4">
          <div className="mx-auto flex max-w-4xl items-end gap-3 rounded-3xl border border-slate-200 bg-slate-50 p-3 shadow-sm">
            <textarea
              rows={1}
              value={currentInput}
              onChange={(e) => setCurrentInput(e.target.value)}
              onKeyDown={handleKeyPress}
              placeholder="Ask anything about your pet..."
              className="max-h-40 min-h-[48px] flex-1 resize-none rounded-2xl border-0 bg-transparent px-3 py-2 text-sm text-slate-800 outline-none placeholder:text-slate-400"
            />

            <button
              type="button"
              onClick={() => handleSendMessage()}
              disabled={!currentInput.trim()}
              className="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600 text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
              <Send size={18} />
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
