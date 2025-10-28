import React, {
  useEffect,
  useState,
  useContext,
  useRef,
  useCallback,
  useMemo,
} from "react";
import ChatInput from "../components/ChatInput";
import RightSidebar from "../components/RightSidebar";
import axios from "../axios";
import toast from "react-hot-toast";
import { useNavigate } from "react-router-dom";
import Sidebar from "../components/Sidebar";
import { AuthContext } from "../auth/AuthContext";
import Navbar from "../components/Navbar";
import { useParams } from "react-router-dom";
import {MessageBubble} from "./MessageBubble"
import DetailedWeatherWidget from "./DetailedWeatherWidget";
import PetDetailsModal from "./RegisterPetOwner";

const deriveDecision = (decision) => {
  const normalized = String(decision ?? "").toUpperCase().trim();
  if (!normalized) {
    return null;
  }
  if (
    normalized.includes("EMERGENCY") ||
    normalized.includes("VIDEO_CONSULT") ||
    normalized.includes("IN_CLINIC")
  ) {
    return normalized;
  }
  return null;
};

const Dashboard = () => {
  const navigate = useNavigate();
  const {
    updateUser,
    user,
    token,
    chatRoomToken,
    updateChatRoomToken,
    updateNearbyDoctors,
  } = useContext(AuthContext);

  const [isLoading, setIsLoading] = useState(false);
  const [messages, setMessages] = useState([]);
  const [sending, setSending] = useState(false);
  const messagesEndRef = useRef(null);
  const chatContainerRef = useRef(null);
  const [contextToken, setContextToken] = useState("");
  const typingTimeouts = useRef(new Map());
  const isAutoScrolling = useRef(false);
  const [nearbyDoctors, setNearbyDoctors] = useState([]);

  // NEW: last decision/score shown in the Pet Profile bar
  const [lastDecision, setLastDecision] = useState(null);
  const [lastScore, setLastScore] = useState(null);

  // ===== NEW: Editable Pet Profile state =====
  const deriveInitialPetProfile = (u) => {
    if (!u) {
      return {
        pet_name: "Unknown",
        pet_breed: "Unknown Breed",
        pet_age: "Unknown",
        pet_weight: "",
        pet_location: "Unknown Location",
      };
    }
    const lat = u.latitude ? String(u.latitude).trim() : "";
    const lon = u.longitude ? String(u.longitude).trim() : "";
    const loc = lat && lon ? `${lat},${lon}` : "Unknown Location";

    return {
      pet_name: u.pet_name || "Unknown",
      pet_breed: u.breed || u.pet_breed || "Unknown Breed",
      pet_age: u.pet_age != null ? String(u.pet_age) : "Unknown",
      pet_weight: u.pet_weight ? String(u.pet_weight) : "",
      pet_location: loc,
    };
  };

  const [petProfile, setPetProfile] = useState(deriveInitialPetProfile(user));

  const onPetChange = (key) => (e) => {
    const v = e?.target?.value ?? "";
    setPetProfile((prev) => ({ ...prev, [key]: v }));
  };

  const resetFromUser = () => {
    setPetProfile(deriveInitialPetProfile(user));
  };

  const canEditPetProfile = Boolean(
    user?.email &&
      ["admin@gmail.com", "ankitkumarjha306@gmail.com"].includes(user.email)
  );
  // ===========================================

  // Get chat_room_token from URL params
  const { chat_room_token } = useParams();
  const currentChatRoomToken = chat_room_token || chatRoomToken;

  const [showPetModal, setShowPetModal] = useState(false);

  useEffect(() => {
    if (user) {
      const hasPetData =
        user.pet_name && user.pet_gender && user.breed && user.pet_age;
      setShowPetModal(!hasPetData);
      setPetProfile(deriveInitialPetProfile(user)); // keep in sync
    } else {
      setShowPetModal(false);
    }
  }, [user]);

  const genId = () => Date.now() + Math.random();

  const fetchNearbyDoctors = async () => {
    if (!token || !user?.id) return;

    try {
      setIsLoading(true);
      const res = await axios.get(
        `https://snoutiq.com/backend/api/nearby-vets?user_id=${user.id}`,
        { headers: { Authorization: `Bearer ${token}` } }
      );

      if (res.data && Array.isArray(res.data.data)) {
        updateNearbyDoctors(res.data.data);
        setNearbyDoctors(res.data.data);
      }
    } catch (err) {
      console.error("Failed to fetch nearby doctors", err);
      toast.error("Failed to fetch doctors");
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchNearbyDoctors();
    const interval = setInterval(fetchNearbyDoctors, 5 * 60 * 1000);
    return () => clearInterval(interval);
  }, []);

  // Improved scrollToBottom
  const scrollToBottom = useCallback((behavior = "smooth") => {
    if (isAutoScrolling.current) return;

    isAutoScrolling.current = true;
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        if (messagesEndRef.current) {
          messagesEndRef.current.scrollIntoView({
            behavior,
            block: "end",
            inline: "nearest",
          });
        }
        setTimeout(() => {
          isAutoScrolling.current = false;
        }, behavior === "smooth" ? 500 : 100);
      });
    });
  }, []);

  // Cleanup typing animations
  const cleanupTypingAnimation = useCallback((messageId) => {
    if (typingTimeouts.current.has(messageId)) {
      clearTimeout(typingTimeouts.current.get(messageId));
      typingTimeouts.current.delete(messageId);
    }
  }, []);

  useEffect(() => {
    return () => {
      typingTimeouts.current.forEach((t) => clearTimeout(t));
      typingTimeouts.current.clear();
    };
  }, []);

  // Auto-scroll when length changes
  useEffect(() => {
    if (messages.length > 0) {
      const timeoutId = setTimeout(() => scrollToBottom("smooth"), 50);
      return () => clearTimeout(timeoutId);
    }
  }, [messages.length, scrollToBottom]);

  const handleFeedback = async (feedback, timestamp) => {
    try {
      const consultationId = messages.find(
        (m) => m.timestamp.getTime() === timestamp.getTime()
      )?.consultationId;

      if (!consultationId) return;

      await axios.post("/api/feedback", { consultationId, feedback });
      toast.success("Thanks for your feedback!");
    } catch {
      toast.error("Failed to submit feedback");
    }
  };

  const fetchChatHistory = useCallback(
    async (specificChatRoomToken = null) => {
      const tk = localStorage.getItem("token");
      if (!tk || !user) return;

      try {
        setIsLoading(true);

        let url;
        if (specificChatRoomToken) {
          url = `https://snoutiq.com/backend/api/chat-rooms/${specificChatRoomToken}/chats?user_id=${user.id}`;
        } else {
          url = `https://snoutiq.com/backend/api/chat/listRooms?user_id=${user.id}`;
        }

        const res = await axios.get(url, {
          headers: { Authorization: `Bearer ${tk}` },
        });

        let messagesFromAPI = [];

        if (res.data && Array.isArray(res.data)) {
          const decision = deriveDecision(res.data.decision);
          console.log(decision,"ankit217");


          messagesFromAPI =
            res.data.chats
              ?.map((chat) => {
                const baseId =
                  Number(new Date(chat.created_at)) + Math.random();
                return [
                  {
                    id: baseId + 1,
                    sender: "user",
                    text: chat.question,
                    timestamp: new Date(chat.created_at),
                  },
                  {
                    id: baseId + 2,
                    sender: "ai",
                    text: chat.answer,
                    displayedText: chat.answer,
                    timestamp: new Date(chat.created_at),
                    decision,
                  },
                ];
              })
              .flat() || [];
        } else if (res.data && res.data.chats) {
          messagesFromAPI = res.data.chats
            .filter((chat) => chat.question && chat.answer)
            .map((chat) => {
              const baseId = Number(new Date(chat.created_at)) + Math.random();
              const chatDecision = deriveDecision(chat.decision);
              return [
                {
                  id: baseId + 1,
                  sender: "user",
                  text: chat.question,
                  timestamp: new Date(chat.created_at),
                },
                {
                  id: baseId + 2,
                  sender: "ai",
                  text: chat.answer,
                  displayedText: chat.answer,
                  timestamp: new Date(chat.created_at),
                  decision: chatDecision,
                },
              ];
            })
            .flat();
        }

        setMessages(messagesFromAPI);

        if (res.data.length > 0 && res.data[res.data.length - 1].context_token)
          setContextToken(res.data[res.data.length - 1].context_token);
      } catch (err) {
        console.error("Failed to fetch history", err);
        if (specificChatRoomToken) toast.error("Failed to load chat history");
      } finally {
        setIsLoading(false);
      }
    },
    [user]
  );

  useEffect(() => {
    if (currentChatRoomToken) {
      localStorage.setItem("lastChatRoomToken", currentChatRoomToken);
      if (updateChatRoomToken && chat_room_token) {
        updateChatRoomToken(chat_room_token);
      }
      setMessages([]);
      setContextToken("");
      fetchChatHistory(currentChatRoomToken);
    } else {
      fetchChatHistory();
    }
  }, [currentChatRoomToken]);

  // On mount, load last opened chat
  useEffect(() => {
    const savedRoom = localStorage.getItem("lastChatRoomToken");
    if (savedRoom && !chat_room_token) {
      navigate(`/chat/${savedRoom}`);
    }
  }, []);

  // Sidebar events
  useEffect(() => {
    const handleChatRoomChange = (event) => {
      const newChatRoomToken = event.detail;
      if (newChatRoomToken && updateChatRoomToken) {
        updateChatRoomToken(newChatRoomToken);
        navigate(`/chat/${newChatRoomToken}`);
      }
    };
    window.addEventListener("chatRoomChanged", handleChatRoomChange);
    return () =>
      window.removeEventListener("chatRoomChanged", handleChatRoomChange);
  }, [navigate, updateChatRoomToken]);

  // Save to localStorage
  useEffect(() => {
    const timeoutId = setTimeout(() => {
      if (messages.length > 0) {
        const storageKey = currentChatRoomToken
          ? `chatMessages_${currentChatRoomToken}`
          : "chatMessages";
        localStorage.setItem(storageKey, JSON.stringify(messages));
      }
    }, 500);
    return () => clearTimeout(timeoutId);
  }, [messages, currentChatRoomToken]);

  // Load saved messages for this room
  useEffect(() => {
    if (currentChatRoomToken) {
      const saved = localStorage.getItem(
        `chatMessages_${currentChatRoomToken}`
      );
      if (saved) {
        try {
          const parsed = JSON.parse(saved);
          if (parsed.length > 0) setMessages(parsed);
        } catch (e) {
          console.error("Failed to parse saved messages:", e);
        }
      }
    }
  }, [currentChatRoomToken]);

  // Typing animation
  const startTypingAnimation = useCallback(
    (messageId, fullText) => {
      cleanupTypingAnimation(messageId);
      let charIndex = 0;
      const typingSpeed = 25;
      const batchSize = 3;

      const typeNextBatch = () => {
        if (charIndex >= fullText.length) {
          cleanupTypingAnimation(messageId);
          setTimeout(() => scrollToBottom("smooth"), 200);
          return;
        }
        const nextIndex = Math.min(charIndex + batchSize, fullText.length);
        setMessages((prev) =>
          prev.map((m) =>
            m.id === messageId
              ? { ...m, displayedText: fullText.slice(0, nextIndex) }
              : m
          )
        );
        charIndex = nextIndex;
        setTimeout(() => scrollToBottom("auto"), 10);
        const timeoutId = setTimeout(typeNextBatch, typingSpeed);
        typingTimeouts.current.set(messageId, timeoutId);
      };

      const initialTimeout = setTimeout(typeNextBatch, 300);
      typingTimeouts.current.set(messageId, initialTimeout);
    },
    [cleanupTypingAnimation, scrollToBottom]
  );

  // Send Message
  const handleSendMessage = useCallback(
    async (inputMessage) => {
      if (inputMessage.trim() === "" || sending) return;

      setSending(true);

      const userMsgId = genId();
      const loaderId = "__loader__";

      const userMessage = {
        id: userMsgId,
        text: inputMessage,
        sender: "user",
        timestamp: new Date(),
      };

      const loaderMessage = {
        id: loaderId,
        type: "loading",
        sender: "ai",
        text: "",
        timestamp: new Date(),
      };

      setMessages((prev) => {
        const newMessages = [...prev, userMessage, loaderMessage];
        setTimeout(() => scrollToBottom("smooth"), 100);
        return newMessages;
      });

      try {
        const tk = localStorage.getItem("token");
        if (!tk) {
          toast.error("Please log in to continue");
          setMessages((prev) => prev.filter((m) => m.id !== loaderId));
          setSending(false);
          return;
        }

        // use editable petProfile for payload
        const petData = {
          pet_name: (petProfile.pet_name || "Unknown").trim(),
          pet_breed: (petProfile.pet_breed || "Unknown Breed").trim(),
          pet_age:
            petProfile.pet_age != null && String(petProfile.pet_age).length
              ? String(petProfile.pet_age).trim()
              : "Unknown",
          pet_location:
            (petProfile.pet_location || "Unknown Location").trim(),
        };

        const payload = {
          user_id: user?.id,
          question: inputMessage,
          context_token: contextToken || "",
          chat_room_token: currentChatRoomToken || "",
          ...petData,
        };

        const res = await axios.post(
          "https://snoutiq.com/backend/api/chat/send",
          payload,
          {
            headers: { Authorization: `Bearer ${tk}` },
            timeout: 30000,
          }
        );

        const {
          context_token: newCtx,
          chat = {},
          emergency_status,
          decision,
          score,
        } = res.data || {};

        const finalDecision = deriveDecision(decision);

        if (newCtx) setContextToken(newCtx);

        // 🔴 update decision/score chips shown in the bar
        setLastDecision(finalDecision ?? null);
        setLastScore(
          typeof score === "number" ? score : score ? Number(score) : null
        );

        const fullText = String(chat.answer || "");
        const aiId = genId();

        setMessages((prev) => {
          const newMessages = prev.map((m) =>
            m.id === loaderId
              ? {
                  id: aiId,
                  sender: "ai",
                  text: fullText,
                  displayedText: "",
                  timestamp: new Date(),
                  emergency_status: emergency_status,
                  // (optional) keep per-message decision/score if you want later
                  decision: finalDecision ?? null,
                  score:
                    typeof score === "number"
                      ? score
                      : score
                      ? Number(score)
                      : null,
                }
              : m
          );
          setTimeout(() => scrollToBottom("smooth"), 100);
          return newMessages;
        });

        startTypingAnimation(aiId, fullText);
      } catch (error) {
        toast.error("Something went wrong. Try again.");

        setMessages((prev) => {
          const filtered = prev.filter((m) => m.id !== loaderId);
          const errorMessage = {
            id: genId(),
            text: "⚠️ Sorry, I'm having trouble connecting right now.",
            sender: "ai",
            timestamp: new Date(),
            isError: true,
            displayedText: "⚠️ Sorry, I'm having trouble connecting right now.",
          };
          return [...filtered, errorMessage];
        });
      } finally {
        setSending(false);
      }
    },
    [
      contextToken,
      currentChatRoomToken,
      user?.id,
      sending,
      startTypingAnimation,
      scrollToBottom,
      petProfile,
    ]
  );

  const clearChat = useCallback(() => {
    if (window.confirm("Are you sure you want to clear the chat history?")) {
      typingTimeouts.current.forEach((t) => clearTimeout(t));
      typingTimeouts.current.clear();
      setMessages([]);
      setContextToken("");
      setLastDecision(null);
      setLastScore(null);

      const storageKey = currentChatRoomToken
        ? `chatMessages_${currentChatRoomToken}`
        : "chatMessages";
      localStorage.removeItem(storageKey);
      localStorage.removeItem("contextToken");

      toast.success("Chat cleared");
    }
  }, [currentChatRoomToken]);

  // Context token persistence
  useEffect(() => {
    if (contextToken) localStorage.setItem("contextToken", contextToken);
  }, [contextToken]);

  useEffect(() => {
    const saved = localStorage.getItem("contextToken");
    if (saved) setContextToken(saved);
  }, []);

  const ChatContent = useMemo(() => {
    return (
      <div className="flex flex-col h-full">
        {/* Header */}
        <div className="bg-white border-b border-gray-200 px-6 py-2 shadow-sm flex justify-between items-center">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">SnoutIQ Symptom Checker</h1>
            <p className="text-gray-600 mt-1 text-sm">
              {/* {currentChatRoomToken
                ? `Ask questions about ${user?.pet_name || "your pet"}'s health`
                : ``} */}
                Check symptoms, get instant advice, and connect to a vet faster.
            </p>
          </div>
          <DetailedWeatherWidget />
          <div className="flex gap-2">
            {messages.length > 0 && (
              <button
                onClick={clearChat}
                className="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded-md transition-colors"
                disabled={sending}
              >
                Clear Chat
              </button>
            )}
          </div>
        </div>

        {/* Chat Container */}
        <div
          ref={chatContainerRef}
          className="flex-1 overflow-y-auto px-4 bg-gray-50 custom-scroll "
        >
          <div className="max-w-4xl mx-auto py-4">
            {isLoading ? (
              <div className="flex justify-center items-center h-40">
                <div className="text-center">
                  <div className="animate-spin h-8 w-8 border-2 border-blue-500 border-t-transparent rounded-full mb-4 mx-auto"></div>
                  <p className="text-gray-600">Loading chat history...</p>
                </div>
              </div>
            ) : messages.length === 0 ? (
              <div className="flex flex-col items-center justify-center h-full text-center py-8">
                <div className="bg-white rounded-xl shadow-md p-6 lg:p-8 max-w-md">
                  <div className="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center mb-4 mx-auto">
                    <svg
                      className="w-7 h-7 text-blue-600"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
                      />
                    </svg>
                  </div>
                  <h2 className="text-lg lg:text-xl font-semibold text-gray-900 mb-2">
                    {currentChatRoomToken
                      ? "No messages in this chat"
                      : "Welcome to Pet Health Assistant"}
                  </h2>
                  <p className="text-gray-600 mb-4 text-sm lg:text-base">
                    {currentChatRoomToken
                      ? "Start the conversation by asking a question about your pet."
                      : "Start a conversation about your pet's health. Describe symptoms, ask questions, or seek advice about pet care."}
                  </p>
                  <div className="bg-blue-50 border border-blue-200 rounded-lg p-3 text-left">
                    <h3 className="font-medium text-blue-800 mb-2 text-sm">
                      Try asking:
                    </h3>
                    <ul className="text-xs lg:text-sm text-blue-700 space-y-1">
                      <li>
                        • "My dog is scratching constantly, what could it be?"
                      </li>
                      <li>• "What's the best diet for a senior cat?"</li>
                      <li>• "How often should I groom my golden retriever?"</li>
                    </ul>
                  </div>
                </div>
              </div>
            ) : (
              <div>
                {messages.map((msg, index) => (
                  <div
                    key={msg.id || `msg-${index}`}
                    className="mb-4 last:mb-0"
                  >
                    <MessageBubble
                      msg={msg}
                      index={index}
                      onFeedback={(value, timestamp) =>
                        handleFeedback(value, timestamp)
                      }
                      nearbyDoctors={nearbyDoctors}
                    />
                  </div>
                ))}
                <div ref={messagesEndRef} className="h-1" />
              </div>
            )}
          </div>
        </div>

        {/* Chat Input */}
        <div className="border-t border-gray-200 bg-white p-4 shadow-lg">
          <div className="max-w-4xl mx-auto px-4">

         
            <ChatInput onSendMessage={handleSendMessage} isLoading={sending} />
            <div className="mt-2 text-xs text-gray-500 text-center">
              ⚠️ AI-generated advice. Consult a licensed veterinarian for
              serious health concerns.
            </div>
          </div>
        </div>
      </div>
    );
  }, [
    messages,
    user,
    sending,
    clearChat,
    handleSendMessage,
    isLoading,
    currentChatRoomToken,
    canEditPetProfile,
    petProfile,
    lastDecision,
    lastScore,
  ]);

  return (
    <>
      <Navbar />
      {showPetModal && (
        <PetDetailsModal
          onComplete={() => setShowPetModal(false)}
          updateUser={updateUser}
          token={token}
          user={user}
        />
      )}

      <div className="hidden lg:flex h-[calc(100vh-64px)] mt-16 bg-gray-50">
        {/* Left Sidebar */}
        <div className="w-64 bg-white border-r border-gray-200 shadow-sm overflow-y-auto">
          <Sidebar />
        </div>

        {/* Center */}
        <div className="flex-1 flex flex-col overflow-hidden">{ChatContent}</div>

        {/* Right Sidebar */}
        <div className="w-64 bg-white border-l border-gray-200 shadow-sm overflow-y-auto custom-scroll">
          <RightSidebar />
        </div>
      </div>

      {/* Mobile Layout */}
      <div className="lg:hidden h-[calc(100vh-64px)] mt-16">{ChatContent}</div>
    </>
  );
};

export default Dashboard;
