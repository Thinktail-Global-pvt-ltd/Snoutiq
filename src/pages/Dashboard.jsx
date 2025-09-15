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
import MessageBubble from "./MessageBubble";
import DetailedWeatherWidget from "./DetailedWeatherWidget";

const Dashboard = () => {
  const navigate = useNavigate();
  const {
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

  // Get chat_room_token from URL params
  const { chat_room_token } = useParams();
  const currentChatRoomToken = chat_room_token || chatRoomToken;

  const genId = () => Date.now() + Math.random();

  const fetchNearbyDoctors = async () => {
  if (!token) return;

  try {
    setIsLoading(true);
    const res = await axios.get(
      `https://snoutiq.com/backend/api/nearby-vets?user_id=${user.id}`,
      { headers: { Authorization: `Bearer ${token}` } }
    );

    console.log(res.data, "Nearby doctors response");

    if (res.data && Array.isArray(res.data.data)) {
      updateNearbyDoctors(res.data.data); // ✅ correct
      console.log("Doctors updated:", res.data.data);
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


  // Optimized scroll function with debouncing
  const scrollToBottom = useCallback((behavior = "smooth") => {
    if (isAutoScrolling.current) return;

    isAutoScrolling.current = true;
    requestAnimationFrame(() => {
      if (messagesEndRef.current) {
        messagesEndRef.current.scrollIntoView({
          behavior: behavior,
          block: "end",
        });
      }
      setTimeout(() => {
        isAutoScrolling.current = false;
      }, 100);
    });
  }, []);

  // Cleanup function for typing animations
  const cleanupTypingAnimation = useCallback((messageId) => {
    if (typingTimeouts.current.has(messageId)) {
      clearTimeout(typingTimeouts.current.get(messageId));
      typingTimeouts.current.delete(messageId);
    }
  }, []);

  // Cleanup all animations on unmount
  useEffect(() => {
    return () => {
      typingTimeouts.current.forEach((timeout) => clearTimeout(timeout));
      typingTimeouts.current.clear();
    };
  }, []);

  // Auto-scroll when new messages are added
  useEffect(() => {
    if (messages.length > 0) {
      scrollToBottom("smooth");
    }
  }, [messages, scrollToBottom]);

  const handleFeedback = async (feedback, timestamp) => {
    try {
      const consultationId = messages.find(
        (msg) => msg.timestamp.getTime() === timestamp.getTime()
      )?.consultationId;

      if (!consultationId) return;

      await axios.post("/api/feedback", {
        consultationId,
        feedback,
      });

      toast.success("Thanks for your feedback!");
    } catch (err) {
      toast.error("Failed to submit feedback");
    }
  };

  const fetchChatHistory = useCallback(
    async (specificChatRoomToken = null) => {
      const token = localStorage.getItem("token");
      if (!token || !user) return;

      try {
        setIsLoading(true);

        // ✅ सही API endpoint format
        let url;
        if (specificChatRoomToken) {
          url = `https://snoutiq.com/backend/api/chat-rooms/${specificChatRoomToken}/chats?user_id=${user.id}`;
        } else {
          url = `https://snoutiq.com/backend/api/chat/listRooms?user_id=${user.id}`;
        }

        const res = await axios.get(url, {
          headers: { Authorization: `Bearer ${token}` },
        });

        console.log("Chat history API response:", res.data);
        let messagesFromAPI = [];

        if (res.data && Array.isArray(res.data)) {
          const emergencyStatus = res.data.emergency_status || null;

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
                    emergency_status: emergencyStatus,
                  },
                ];
              })
              .flat() || [];
        } else if (res.data && res.data.chats) {
          messagesFromAPI = res.data.chats
            .filter((chat) => chat.question && chat.answer)
            .map((chat) => {
              const baseId = Number(new Date(chat.created_at)) + Math.random();
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
                },
              ];
            })
            .flat();
        }

        setMessages(messagesFromAPI);

        // Update context if it exists in response
        if (
          res.data.length > 0 &&
          res.data[res.data.length - 1].context_token
        ) {
          setContextToken(res.data[res.data.length - 1].context_token);
        }
      } catch (err) {
        console.error("Failed to fetch history", err);
        if (specificChatRoomToken) {
          toast.error("Failed to load chat history");
        }
      } finally {
        setIsLoading(false);
      }
    },
    [user]
  );

  useEffect(() => {
    if (currentChatRoomToken) {
      // Save latest chat room in localStorage
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

  // ✅ FIX: Add event listener for sidebar clicks
  useEffect(() => {
    const handleChatRoomChange = (event) => {
      const newChatRoomToken = event.detail;
      console.log("Received chat room change event:", newChatRoomToken);

      if (newChatRoomToken && updateChatRoomToken) {
        updateChatRoomToken(newChatRoomToken);
        navigate(`/chat/${newChatRoomToken}`);
      }
    };

    window.addEventListener("chatRoomChanged", handleChatRoomChange);

    return () => {
      window.removeEventListener("chatRoomChanged", handleChatRoomChange);
    };
  }, [navigate, updateChatRoomToken]);

  // Save messages to localStorage (debounced)
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

  // Load saved messages from localStorage on mount
  useEffect(() => {
    if (currentChatRoomToken) {
      const saved = localStorage.getItem(
        `chatMessages_${currentChatRoomToken}`
      );
      if (saved) {
        try {
          const parsedMessages = JSON.parse(saved);
          if (parsedMessages.length > 0) {
            setMessages(parsedMessages);
          }
        } catch (error) {
          console.error("Failed to parse saved messages:", error);
        }
      }
    }
  }, [currentChatRoomToken]);

  // Optimized typing animation with better performance
  const startTypingAnimation = useCallback(
    (messageId, fullText) => {
      cleanupTypingAnimation(messageId);

      let charIndex = 0;
      const typingSpeed = 15;
      const batchSize = 2;

      const typeNextBatch = () => {
        if (charIndex >= fullText.length) {
          cleanupTypingAnimation(messageId);
          requestAnimationFrame(() => scrollToBottom("smooth"));
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

        requestAnimationFrame(() => scrollToBottom("auto"));

        const timeoutId = setTimeout(typeNextBatch, typingSpeed);
        typingTimeouts.current.set(messageId, timeoutId);
      };

      const initialTimeout = setTimeout(typeNextBatch, 200);
      typingTimeouts.current.set(messageId, initialTimeout);
    },
    [cleanupTypingAnimation, scrollToBottom]
  );

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

      // FIXED: Add new messages to the end, not the beginning
      setMessages((prev) => [...prev, userMessage, loaderMessage]);

      try {
        const token = localStorage.getItem("token");
        if (!token) {
          toast.error("Please log in to continue");
          setMessages((prev) => prev.filter((m) => m.id !== loaderId));
          setSending(false);
          return;
        }

        const petData = {
          pet_name: user?.pet_name || "Unknown",
          pet_breed: "Unknown Breed",
          pet_age: user?.pet_age?.toString() || "Unknown",
          pet_location: "Unknown Location",
        };

        const payload = {
          user_id: user.id,
          question: inputMessage,
          context_token: contextToken || "",
          chat_room_token: currentChatRoomToken || "",
          ...petData,
        };

        const res = await axios.post(
          "https://snoutiq.com/backend/api/chat/send",
          payload,
          {
            headers: { Authorization: `Bearer ${token}` },
            timeout: 30000,
          }
        );

        // CORRECTED: Properly destructure the response
        const {
          context_token: newCtx,
          chat = {},
          emergency_status,
        } = res.data || {};

        if (newCtx) setContextToken(newCtx);

        const fullText = String(chat.answer || "");
        const aiId = genId();

        setMessages((prev) =>
          prev.map((m) =>
            m.id === loaderId
              ? {
                  id: aiId,
                  sender: "ai",
                  text: fullText,
                  displayedText: "",
                  timestamp: new Date(),
                  emergency_status: emergency_status,
                }
              : m
          )
        );

        startTypingAnimation(aiId, fullText);
      } catch (error) {
        console.error("Error sending chat:", error);
        toast.error("Something went wrong. Try again.");

        setMessages((prev) => [
          ...prev.filter((m) => m.id !== loaderId),
          {
            id: genId(),
            text: "⚠️ Sorry, I'm having trouble connecting right now.",
            sender: "ai",
            timestamp: new Date(),
            isError: true,
            displayedText: "⚠️ Sorry, I'm having trouble connecting right now.",
          },
        ]);
      } finally {
        setSending(false);
      }
    },
    [contextToken, currentChatRoomToken, user, sending, startTypingAnimation]
  );

  const clearChat = useCallback(() => {
    if (window.confirm("Are you sure you want to clear the chat history?")) {
      typingTimeouts.current.forEach((timeout) => clearTimeout(timeout));
      typingTimeouts.current.clear();

      setMessages([]);
      setContextToken("");

      // Clear from localStorage
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
    if (contextToken) {
      localStorage.setItem("contextToken", contextToken);
    }
  }, [contextToken]);

  useEffect(() => {
    const saved = localStorage.getItem("contextToken");
    if (saved) setContextToken(saved);
  }, []);

  // const fetchAvailableVet = async () => {
  //   try {
  //     setIsLoading(true);
  //     const response = await axios.get(
  //       `https://snoutiq.com/api/nearby-vets?user_id=1`
  //     );

  //     if (response.data.status === "success") {
  //       setWeather(response.data);
  //       setError(null);
  //     } else {
  //       setError("Unable to fetch weather data");
  //     }
  //   } catch (err) {
  //     console.error("Weather fetch error:", err);
  //     setError("Failed to load weather");
  //   } finally {
  //     setIsLoading(false);
  //   }
  // };

  // useEffect(() => {
  //   fetchAvailableVet();

  //   const interval = setInterval(fetchWeather, 30 * 60 * 1000);

  //   return () => clearInterval(interval);
  // }, []);

  const ChatContent = useMemo(() => {
    return (
      <div className="flex flex-col h-full">
        {/* Header */}
        <div className="bg-white border-b border-gray-200 px-6 py-4 shadow-sm flex justify-between items-center">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Snoutiq AI</h1>
            <p className="text-gray-600 mt-1 text-sm">
              {currentChatRoomToken
                ? `Ask questions about ${user?.pet_name || "your pet"}'s health`
                : ``}
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
        <div
          ref={chatContainerRef}
          className="flex-1 overflow-y-auto px-4 bg-gray-50 flex flex-col-reverse justify-between"
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
                    />
                  </div>
                ))}
                <div ref={messagesEndRef} />
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
  ]);

  return (
    <>
      <Navbar />
      <div className="hidden lg:flex h-[calc(100vh-64px)] mt-16 bg-gray-50">
        {/* Left Sidebar */}
        <div className="w-64 bg-white border-r border-gray-200 shadow-sm overflow-y-auto">
          <Sidebar />
        </div>

        {/* Center */}
        <div className="flex-1 flex flex-col overflow-hidden">
          {ChatContent}
        </div>

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
