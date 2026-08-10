import React, { useEffect, useRef, useState } from "react";
import { Send, ImagePlus, X, Loader2 } from "lucide-react";
import { Dialog } from "@headlessui/react";
import { apiBaseUrl } from "../lib/api";
import { readAiAuthState, clearAiAuthState } from "../ai/AiAuth";
import GoogleAuthModal from "./GoogleAuthModal";
import PetForn from "../ai/PetForn";
import { useNavigate } from "react-router-dom";
import { hasUsablePetProfile, submitIntakeForm } from "./authHelpers";
import ModernDoctorBooking from "./ModernDoctorBooking";
import snoutiq_app_icon from "../assets/snoutiq_app_icon.png";
import { 
  BannerCard, 
  HealthScore, 
  DoNowCard, 
  ListSection, 
  ServiceCard, 
  FollowUpQuestion 
} from "./AssessmentUI";

function ModalShell({ children }) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-[2px] animate-[fadeIn_0.2s_ease-out]">
      <style>{`
        @keyframes fadeIn {
          from { opacity: 0; }
          to { opacity: 1; }
        }
        @keyframes scaleIn {
          from { transform: scale(0.97); opacity: 0; }
          to { transform: scale(1); opacity: 1; }
        }
      `}</style>
      <div className="animate-[scaleIn_0.2s_ease-out] w-full flex items-center justify-center">
        {children}
      </div>
    </div>
  );
}

function ImageUploadModal({ onClose, onUpload }) {
  const fileInputRef = useRef(null);

  const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onloadend = () => {
        const base64String = reader.result;
        onUpload(base64String, file.type);
        onClose();
      };
      reader.readAsDataURL(file);
    }
  };

  return (
    <ModalShell>
      <div className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl relative">
        <button onClick={onClose} className="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
          <X size={20} />
        </button>
        <h3 className="text-xl font-bold text-slate-900 mb-4">Upload Image</h3>
        <div 
          onClick={() => fileInputRef.current?.click()}
          className="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center bg-slate-50 hover:bg-slate-100 transition-colors cursor-pointer"
        >
          <ImagePlus className="mx-auto h-12 w-12 text-slate-400 mb-4" />
          <p className="text-sm font-medium text-slate-700">Click to upload image</p>
          <p className="text-xs text-slate-500 mt-2">Will be attached to your next message</p>
          <input 
            type="file" 
            accept="image/*" 
            className="hidden" 
            ref={fileInputRef} 
            onChange={handleFileChange}
          />
        </div>
      </div>
    </ModalShell>
  );
}

export default function SymptomCheckerFlow({ activeChatRoomToken, setActiveChatRoomToken, onMessageSent }) {
  const navigate = useNavigate();
  const [messages, setMessages] = useState([]);
  const [inputValue, setInputValue] = useState("");
  const [loading, setLoading] = useState(false);
  const [showAuthGate, setShowAuthGate] = useState(false);
  const [showPetModal, setShowPetModal] = useState(false);
  const [showDoctorsModal, setShowDoctorsModal] = useState(false);
  const [bookingOrderType, setBookingOrderType] = useState("video_consult");
  const [showImageModal, setShowImageModal] = useState(false);
  const [attachedImage, setAttachedImage] = useState(null);
  const [pendingSubmit, setPendingSubmit] = useState(false);
  const [petFormPart, setPetFormPart] = useState(1);
  const [pendingFollowUp, setPendingFollowUp] = useState(null);
  
  const messagesEndRef = useRef(null);
  
  const authState = readAiAuthState();
  const token = authState?.token;
  const user = authState?.user || {};
  const pet = user.pet || (user.pets ? user.pets[0] : null) || {};

  useEffect(() => {
    const lastMsg = messages[messages.length - 1];
    if (lastMsg && lastMsg.role === "user") {
      messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
    }
  }, [messages]);

  useEffect(() => {
    if (pendingSubmit && token && hasUsablePetProfile(authState)) {
      setPendingSubmit(false);
      if (inputValue.trim() || attachedImage) {
        handleSubmit(null, inputValue);
      }
    }
  }, [pendingSubmit, token, authState, inputValue, attachedImage]);

  useEffect(() => {
    if (activeChatRoomToken && token) {
      loadChatHistory(activeChatRoomToken);
    } else {
      setMessages([]);
    }
  }, [activeChatRoomToken, token]);

  const pushMessage = (msg) => setMessages((prev) => [...prev, { id: Date.now() + Math.random(), ...msg }]);
  const replaceLastMessage = (msg) => setMessages((prev) => [...prev.slice(0, -1), { id: Date.now() + Math.random(), ...msg }]);

  const loadChatHistory = async (roomToken) => {
    setLoading(true);
    try {
      const res = await fetch(`${apiBaseUrl()}/api/ask/chat-rooms/${roomToken}/chats?user_id=${user.id || user.user_id}&sort=asc`, {
        headers: { "Authorization": `Bearer ${token}` }
      });
      const data = await res.json();
      if (data.chats && Array.isArray(data.chats)) {
        const historyMessages = [];
        data.chats.forEach(chat => {
          if (chat.question) historyMessages.push({ id: `q_${chat.id || Math.random()}`, role: "user", text: chat.question });
          if (chat.answer || chat.response) {
            let uiData = {};
            try {
              uiData = chat.ui ? (typeof chat.ui === 'string' ? JSON.parse(chat.ui) : chat.ui) : {};
            } catch(e) {}
            
            historyMessages.push({
              id: `a_${chat.id || Math.random()}`,
              role: "assistant",
              text: chat.answer || "Analyzed",
              raw_response: { ...chat, ui: uiData }
            });
          }
        });
        setMessages(historyMessages);
      }
    } catch (err) {
      console.error("Failed to load history", err);
    } finally {
      setLoading(false);
    }
  };

  const createChatRoom = async () => {
    const res = await fetch(`${apiBaseUrl()}/api/ask/chat-rooms/new`, {
      method: "POST",
      headers: { "Content-Type": "application/json", "Authorization": `Bearer ${token}` },
      body: JSON.stringify({
        user_id: user.id || user.user_id,
        title: `Chat for ${pet.name || pet.pet_name}`,
        pet_id: pet.id || pet.pet_id,
        pet_name: pet.name || pet.pet_name,
        pet_breed: pet.breed,
        species: pet.pet_type || "dog"
      })
    });
    const data = await res.json();
    return data.chat_room_token || data.session_id;
  };

  const callLegacyFallback = async (question, roomToken) => {
    const res = await fetch(`${apiBaseUrl()}/api/chat/send`, {
      method: "POST",
      headers: { "Content-Type": "application/json", "Authorization": `Bearer ${token}` },
      body: JSON.stringify({
        user_id: user.id || user.user_id,
        question: question,
        chat_room_token: roomToken,
        pet_id: pet.id || pet.pet_id,
        pet_name: pet.name || pet.pet_name,
        pet_type: pet.pet_type || "dog",
        species: pet.pet_type || "dog",
        pet_breed: pet.breed,
        breed: pet.breed,
        pet_age: pet.pet_age,
        location: user.location
      })
    });
    return await res.json();
  };

  const resolveActionType = (cta = {}) => {
    const type = String(cta?.type || "").toLowerCase().replace(/[\s-]+/g, "_");
    const label = String(cta?.label || "").toLowerCase();
    const deeplink = String(cta?.deeplink || "").toLowerCase();

    // Both video_consult and vet_at_home use the same API / booking modal flow
    if (
      type === "video_consult" ||
      type === "video" ||
      type === "vet_at_home" ||
      type.includes("video") ||
      type.includes("home") ||
      label.includes("video") ||
      label.includes("talk to vet") ||
      label.includes("consult") ||
      label.includes("home") ||
      deeplink.includes("video-consult") ||
      deeplink.includes("video_consult") ||
      deeplink.includes("vet-at-home") ||
      deeplink.includes("vet_at_home")
    ) {
      return "video_consult";
    }

    if (type === "emergency" || label.includes("emergency")) {
      return "emergency";
    }

    if (
      type === "clinic" || 
      type.includes("clinic") || 
      deeplink.includes("clinic") || 
      label.includes("clinic")
    ) {
      return "clinic";
    }

    return "video_consult";
  };

  const handleAction = (cta) => {
    if (!token) {
      setShowAuthGate(true);
      return;
    }
    
    const resolvedType = typeof cta === "string" 
      ? resolveActionType({ type: cta }) 
      : resolveActionType(cta);
      
    console.log("🔍 Resolved action type:", resolvedType, "from raw:", cta);

    if (resolvedType === "video_consult" || resolvedType === "clinic") {
      setBookingOrderType(resolvedType === "clinic" ? "appointment" : "video_consult");
      setShowDoctorsModal(true);
    } else {
      navigate("/clinics");
    }
  };

  const getUserSymptomText = () => {
    const userMsg = messages.find(m => m.role === "user");
    if (userMsg && userMsg.text) {
      return userMsg.text;
    }
    const stored = localStorage.getItem("symptom_description");
    if (stored) return stored;
    return bookingOrderType === "appointment" ? "Clinic Visit Booking" : "Video Consult Booking";
  };

  const getProcessedServiceCards = (raw) => {
    let cards = raw?.ui?.service_cards;
    if (!Array.isArray(cards)) return cards;
    
    if (raw?.buttons?.secondary?.type === "clinic" && cards.length > 1) {
      return cards.map((card, idx) => {
        if (idx === 1) {
          return {
            ...card,
            badge: "In-person Care",
            badge_variant: "success",
            title: "Clinic Visit",
            guarantee: "Book appointment at nearby clinics",
            bullets: [
              "Skip the long queues",
              "Consult with verified local vets",
              "Physical checkup & diagnostics"
            ],
            cta: {
              ...raw.buttons.secondary,
              label: "Book Appointment",
              type: "clinic"
            }
          };
        }
        return card;
      });
    }
    return cards;
  };

  const handleSubmit = async (e, forcedQuestion = null) => {
    if (e) e.preventDefault();
    const textToSubmit = forcedQuestion || inputValue.trim();
    if (!textToSubmit && !attachedImage) return;

    if (!token) {
      setShowAuthGate(true);
      return;
    }
    if (!hasUsablePetProfile(authState)) {
      setPetFormPart(1);
      setShowPetModal(true);
      return;
    }

    const hasHealthProfile = localStorage.getItem("snoutiq_health_profile_completed") === "true";
    const user = authState?.user || {};
    const primaryPet = user?.pet || (user?.pets ? user.pets[0] : null) || {};
    const alreadyHasHealthDetails = primaryPet && (primaryPet.is_nuetered !== undefined || primaryPet.vaccenated_yes_no !== undefined || primaryPet.deworming_yes_no !== undefined);

    if (messages.length >= 2 && !hasHealthProfile && !alreadyHasHealthDetails) {
      setPetFormPart(2);
      setShowPetModal(true);
      return;
    }

    pushMessage({ role: "user", text: textToSubmit, image: attachedImage?.base64 });
    setInputValue("");
    const imgData = attachedImage;
    setAttachedImage(null);
    setLoading(true);

    try {
      let currentSessionId = activeChatRoomToken;
      if (!currentSessionId) {
        currentSessionId = await createChatRoom();
        setActiveChatRoomToken(currentSessionId);
        if (typeof onMessageSent === "function") {
          onMessageSent();
        }
      }

function stripBase64Prefix(dataUrl) {
  if (!dataUrl) return dataUrl;
  const commaIndex = dataUrl.indexOf(",");
  return dataUrl.startsWith("data:") && commaIndex !== -1
    ? dataUrl.slice(commaIndex + 1)
    : dataUrl;
}

      const isFirstMessage = messages.length === 0;
      const endpoint = isFirstMessage ? "/symptom-check" : "/symptom-followup";
      
      const payload = {
        session_id: currentSessionId,
        message: textToSubmit,
        image_base64: imgData?.base64 ? stripBase64Prefix(imgData.base64) : undefined,
        image_mime: imgData?.mime || undefined,
      };

      if (isFirstMessage) {
        Object.assign(payload, {
          species: String(pet.pet_type || "dog"),
          type: String(pet.pet_type || "dog"),
          owner_name: String(user.name || user.owner_name || "Owner"),
          pet_name: String(pet.name || pet.pet_name || "Pet"),
          breed: String(pet.breed || "Unknown"),
          dob: String(pet.pet_dob || pet.dob || "2023-01-01").substring(0, 10), // Ensure Y-m-d format
          location: String(user.location || "Unknown"),
          lat: user.lat ? Number(user.lat) : undefined,
          long: (user.long || user.lng) ? Number(user.long || user.lng) : undefined,
          user_id: String(user.id || user.user_id || "1"),
          pet_id: String(pet.id || pet.pet_id || "1"),
          user: user,
          pets: pet,
        });
      }

      let res = await fetch(`${apiBaseUrl()}/api${endpoint}`, {
        method: "POST",
        headers: { "Content-Type": "application/json", "Authorization": `Bearer ${token}` },
        body: JSON.stringify(payload)
      });

      if (!res.ok) {
        if (res.status === 422) {
          const errData = await res.json();
          console.error("422 Validation Error:", errData);
          const errMessage = errData.message || JSON.stringify(errData.errors || errData);
          pushMessage({ role: "assistant", text: `Validation Error: ${errMessage}` });
          return;
        }
        // Fallback
        const fallbackData = await callLegacyFallback(textToSubmit, currentSessionId);
        console.log("💬 Legacy fallback chat response:", fallbackData);
        pushMessage({
          role: "assistant",
          text: fallbackData?.data?.answer || "We received your query via fallback.",
          raw_response: fallbackData
        });
        if (typeof onMessageSent === "function") {
          onMessageSent();
        }
        return;
      }

      const data = await res.json();
      console.log("💬 Chat response:", data);
      pushMessage({
        role: "assistant",
        text: data?.response?.what_we_think_is_happening || data?.vet_summary || "Analyzed",
        raw_response: data
      });

      if (typeof onMessageSent === "function") {
        onMessageSent();
      }

    } catch (err) {
      console.error(err);
      pushMessage({ role: "assistant", text: "There was an error communicating with the server." });
    } finally {
      setLoading(false);
    }
  };

  const handleAuthSuccess = () => {
    setShowAuthGate(false);
    const freshAuth = readAiAuthState();
    if (!hasUsablePetProfile(freshAuth)) {
      setShowPetModal(true);
    } else {
      setPendingSubmit(true);
    }
  };

  const handlePetFormComplete = () => {
    setShowPetModal(false);
    if (petFormPart === 1) {
      setPendingSubmit(true);
    } else {
      localStorage.setItem("snoutiq_health_profile_completed", "true");
      if (pendingFollowUp) {
        const { questionText, answerText } = pendingFollowUp;
        setPendingFollowUp(null);
        handleFollowUpAnswer(questionText, answerText);
      } else {
        setPendingSubmit(true);
      }
    }
  };

  const handleFollowUpAnswer = async (questionText, answerText) => {
    const hasHealthProfile = localStorage.getItem("snoutiq_health_profile_completed") === "true";
    const user = authState?.user || {};
    const primaryPet = user?.pet || (user?.pets ? user.pets[0] : null) || {};
    const alreadyHasHealthDetails = primaryPet && (primaryPet.is_nuetered !== undefined || primaryPet.vaccenated_yes_no !== undefined || primaryPet.deworming_yes_no !== undefined);

    if (!hasHealthProfile && !alreadyHasHealthDetails) {
      setPetFormPart(2);
      setPendingFollowUp({ questionText, answerText });
      setShowPetModal(true);
      return;
    }

    setLoading(true);
    try {
      const payload = {
        session_id: activeChatRoomToken,
        question: questionText,
        answer: answerText
      };
      const res = await fetch(`${apiBaseUrl()}/api/symptom-answer`, {
        method: "POST",
        headers: { "Content-Type": "application/json", "Authorization": `Bearer ${token}` },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      console.log("💬 Follow-up chat response:", data);
      
      // Update the last assistant message with the revised assessment
      replaceLastMessage({
        role: "assistant",
        text: data?.response?.what_we_think_is_happening || "Revised assessment.",
        raw_response: data
      });
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <div className="flex h-full min-h-[100vh] flex-col bg-white">
        
        {messages.length === 0 ? (
          <div className="flex-1 flex flex-col items-center justify-center p-6 text-center">
            <h1 className="text-4xl md:text-5xl font-bold text-slate-900 mb-4 tracking-tight">Smarter care for your pet's health.</h1>
            <p className="text-lg text-slate-500 mb-8 max-w-2xl">
              Describe a symptom or upload a photo to get instant guidance from AI trained on vet insights.
            </p>
            <div className="w-full max-w-3xl">
              {attachedImage && (
                <div className="mb-4 inline-flex items-center gap-2 rounded-full bg-blue-50 px-4 py-2 text-sm text-blue-700 border border-blue-200">
                  <ImagePlus size={16} /> Image attached
                  <button onClick={() => setAttachedImage(null)} className="ml-2 hover:text-blue-900"><X size={16} /></button>
                </div>
              )}
              <form onSubmit={handleSubmit} className="relative flex items-center shadow-lg border border-slate-200 rounded-full bg-white p-2 mb-8">
                <button
                  type="button"
                  onClick={() => setShowImageModal(true)}
                  className="flex h-10 w-10 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors ml-2"
                >
                  <ImagePlus size={20} />
                </button>
                <input
                  type="text"
                  value={inputValue}
                  onChange={(e) => setInputValue(e.target.value)}
                  placeholder="Describe your pet's symptoms..."
                  className="flex-1 bg-transparent px-2 py-3 text-lg outline-none"
                />
                <button
                  type="submit"
                  disabled={(!inputValue.trim() && !attachedImage)}
                  className="flex h-12 w-12 items-center justify-center rounded-full bg-slate-900 text-white hover:bg-slate-800 disabled:opacity-50 transition-colors"
                >
                  <Send size={20} />
                </button>
              </form>
              <div className="flex flex-wrap justify-center gap-3">
                <button onClick={() => handleSubmit(null, "Vomiting or stomach upset")} className="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 shadow-sm">🐶 Vomiting or stomach upset</button>
                <button onClick={() => handleSubmit(null, "Lethargic & not eating")} className="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 shadow-sm">🐱 Lethargic & not eating</button>
              </div>
            </div>
          </div>
        ) : (
          <div className="flex-1 flex flex-col max-w-4xl mx-auto w-full p-4">
             {pet?.name && (
              <div className="mx-auto mb-6 flex items-center gap-3 rounded-full border border-slate-200 bg-white px-4 py-2 shadow-sm">
                <div className="text-xl">🐾</div>
                <div className="text-sm">
                  <span className="font-semibold text-slate-900">Active profile: {pet.name}</span>
                </div>
              </div>
            )}
            
            <div className="flex-1 overflow-y-auto space-y-6 pb-24">
              {messages.map((msg) => (
                <div key={msg.id} className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                  {!msg.role.includes('user') && (
                     <div className="mt-1 mr-3 flex h-8 w-8 items-center justify-center">
                        <img src={snoutiq_app_icon} alt="AI" className="h-8 w-8 rounded-full" />
                     </div>
                  )}
                  
                  <div className={`max-w-[85%] ${msg.role === 'user' ? 'rounded-2xl bg-slate-900 text-white rounded-tr-none px-5 py-4' : 'w-full'}`}>
                    
                    {msg.role === 'user' && msg.image && (
                      <div className="mb-3 rounded-lg overflow-hidden border border-slate-700">
                        <img src={msg.image} alt="uploaded" className="max-h-48 object-cover" />
                      </div>
                    )}
                    
                    {msg.role === 'user' ? (
                      <p className="whitespace-pre-wrap">{msg.text}</p>
                    ) : (
                      <div className="w-full">
                        {/* AI Rich Response Rendering */}
                        {msg.raw_response?.ui?.banner && (
                          <BannerCard banner={msg.raw_response.ui.banner} theme={msg.raw_response.ui.theme} />
                        )}
                        
                        {msg.raw_response?.ui?.health_score?.value != null && (
                          <HealthScore scoreData={msg.raw_response.ui.health_score} />
                        )}
                        
                        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm mb-6">
                          <p className="whitespace-pre-wrap text-slate-800 leading-relaxed mb-4">{msg.raw_response?.response?.what_we_think_is_happening || msg.text}</p>
                          
                          {msg.raw_response?.response?.do_now && (
                            <DoNowCard text={msg.raw_response.response.do_now} />
                          )}
                          
                          <ListSection title="What to watch" items={msg.raw_response?.response?.what_to_watch} />
                          <ListSection title="Safe to do while waiting" items={msg.raw_response?.response?.safe_to_do_while_waiting} />
                        </div>

                        {msg.raw_response?.follow_up_question && (
                          <FollowUpQuestion 
                            questionData={msg.raw_response.follow_up_question} 
                            onAnswer={handleFollowUpAnswer} 
                          />
                        )}

                        {getProcessedServiceCards(msg.raw_response)?.map((card, idx) => (
                          <ServiceCard key={idx} card={card} onAction={handleAction} />
                        ))}
                        
                        {/* Fallback buttons if no service cards but buttons exist */}
                        {(!msg.raw_response?.ui?.service_cards || msg.raw_response.ui.service_cards.length === 0) && msg.raw_response?.buttons && (
                           <div className="flex gap-3">
                              {msg.raw_response.buttons.primary && (
                                <button onClick={() => handleAction(msg.raw_response.buttons.primary)} className="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 shadow-sm transition-colors">
                                  {String(msg.raw_response.buttons.primary.label).toLowerCase().includes("clinic") ? "Book Appointment" : msg.raw_response.buttons.primary.label}
                                </button>
                              )}
                              {msg.raw_response.buttons.secondary && (
                                <button onClick={() => handleAction(msg.raw_response.buttons.secondary)} className="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition-colors">
                                  {String(msg.raw_response.buttons.secondary.label).toLowerCase().includes("clinic") ? "Book Appointment" : msg.raw_response.buttons.secondary.label}
                                </button>
                              )}
                           </div>
                        )}
                      </div>
                    )}
                  </div>
                </div>
              ))}
              {loading && (
                 <div className="flex justify-start">
                    <div className="mt-1 mr-3 flex h-8 w-8 items-center justify-center rounded-full">
                        <img src={snoutiq_app_icon} alt="AI" className="h-8 w-8 rounded-full" />
                     </div>
                    <div className="rounded-2xl bg-white border border-slate-200 shadow-sm rounded-tl-none px-5 py-4 text-slate-500 flex items-center gap-2">
                       <Loader2 className="w-4 h-4 animate-spin" /> Analyzing symptoms...
                    </div>
                 </div>
              )}
              <div ref={messagesEndRef} />
            </div>

            <div className="fixed bottom-0 left-0 right-0 md:left-64 bg-white/80 backdrop-blur-md border-t border-slate-200 p-4">
              {attachedImage && (
                <div className="mx-auto max-w-4xl mb-2 flex">
                  <div className="inline-flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 border border-blue-200">
                    <ImagePlus size={14} /> Image attached
                    <button onClick={() => setAttachedImage(null)} className="ml-1 hover:text-blue-900"><X size={14} /></button>
                  </div>
                </div>
              )}
              <form onSubmit={handleSubmit} className="mx-auto max-w-4xl relative flex items-center border border-slate-200 rounded-full bg-white p-1.5 shadow-sm focus-within:border-slate-400 focus-within:ring-1 focus-within:ring-slate-400">
                 <button
                  type="button"
                  onClick={() => setShowImageModal(true)}
                  className="flex h-10 w-10 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors ml-1 mr-1 shrink-0"
                >
                  <ImagePlus size={20} />
                </button>
                 <input
                  type="text"
                  value={inputValue}
                  onChange={(e) => setInputValue(e.target.value)}
                  placeholder="Describe your pet's symptoms..."
                  disabled={loading}
                  className="flex-1 bg-transparent px-4 py-2 outline-none disabled:opacity-50 text-slate-900 min-w-0"
                />
                <button
                  type="submit"
                  disabled={(!inputValue.trim() && !attachedImage) || loading}
                  className="flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-white hover:bg-slate-800 disabled:opacity-50 transition-colors shrink-0"
                >
                  <Send size={18} />
                </button>
              </form>
            </div>
          </div>
        )}

        {showAuthGate && (
          <ModalShell>
            <div className="relative w-full max-w-md rounded-2xl overflow-hidden bg-white shadow-2xl">
              <button onClick={() => setShowAuthGate(false)} className="absolute top-4 right-4 z-50 p-2 bg-slate-100 rounded-full shadow-sm text-slate-500 hover:bg-slate-200 hover:text-slate-900">
                <X size={20} />
              </button>
              <GoogleAuthModal onLoginSuccess={handleAuthSuccess} />
            </div>
          </ModalShell>
        )}
        
        {showPetModal && (
          <ModalShell>
            <div className="relative w-full max-w-xl rounded-2xl overflow-hidden bg-white shadow-2xl max-h-[90vh] flex flex-col">
              <button onClick={() => setShowPetModal(false)} className="absolute top-4 right-4 z-50 p-2 bg-slate-100 hover:bg-slate-200 rounded-full text-slate-500 hover:text-slate-900 transition-colors">
                <X size={20} />
              </button>
              <div className="w-full overflow-y-auto">
                <PetForn 
                  submitIntake={submitIntakeForm} 
                  onComplete={handlePetFormComplete} 
                  isModal={true} 
                  part={petFormPart}
                />
              </div>
            </div>
          </ModalShell>
        )}
        
        {showImageModal && (
          <ImageUploadModal 
            onClose={() => setShowImageModal(false)} 
            onUpload={(base64, mime) => setAttachedImage({ base64, mime })} 
          />
        )}
        
        {showDoctorsModal && (
          <ModernDoctorBooking 
            onClose={() => setShowDoctorsModal(false)} 
            symptomText={getUserSymptomText()}
            preSelectedPet={pet}
            orderType={bookingOrderType}
          />
        )}
      </div>
    </>
  );
}
