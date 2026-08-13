import React, { useEffect, useState } from "react";
import { NavLink } from "react-router-dom";
import { 
  Stethoscope, 
  MapPin, 
  BookOpen, 
  Info,
  MessageSquare,
  Plus,
  User,
  X,
  LogOut,
  Trash2,
  ChevronLeft
} from "lucide-react";
import { apiBaseUrl } from "../lib/api";
import { readAiAuthState } from "../ai/AiAuth";
// import snoutiq_app_icon from "../assets/images/logo.png";
import snoutiq_app_icon from "../assets/snoutiq_app_icon.png";

const PAGES = [
  { name: "Register for Vet", path: "/vets", icon: Stethoscope },
  { name: "Register for Clinics", path: "/clinics", icon: MapPin },
  { name: "Pet Care Guides", path: "/blog", icon: BookOpen },
  { name: "About Us", path: "/about", icon: Info },
];

export default function SymptomCheckerSidebar({
  isOpen,
  setIsOpen,
  isDesktopOpen = true,
  setIsDesktopOpen,
  activeChatRoomToken,
  onSelectChat,
  onNewChat,
  historyRefreshKey,
}) {
  const [history, setHistory] = useState([]);
  const [loading, setLoading] = useState(false);
  
  const authState = readAiAuthState();
  const userId = authState?.user?.id || authState?.user?.user_id;
  const token = authState?.token;

  useEffect(() => {
    if (!userId) return;
    
    const fetchHistory = async () => {
      setLoading(true);
      try {
        const response = await fetch(`${apiBaseUrl()}/api/ask/chat/listRooms?user_id=${userId}&limit=30`, {
          headers: {
            "Authorization": `Bearer ${token}`,
            "Content-Type": "application/json"
          }
        });
        if (response.ok) {
          const data = await response.json();
          let rooms = data.rooms || data.data || (Array.isArray(data) ? data : []);
          
          const normalized = rooms.map(room => ({
            ...room,
            chat_room_token: room.chat_room_token || room.session_id || room.context_token || room.id,
            name: room.title || room.name || room.chat_room_title || "Untitled Chat",
            summary: room.summary || ""
          })).filter(room => room.chat_room_token);
          
          setHistory(normalized);
        }
      } catch (error) {
        console.error("Failed to load history:", error);
      } finally {
        setLoading(false);
      }
    };
    
    if (token && userId) {
      fetchHistory();
    }
  }, [token, userId, historyRefreshKey]);

  const handleDeleteChat = async (e, roomToken) => {
    e.stopPropagation();
    try {
      const response = await fetch(`${apiBaseUrl()}/api/ask/chat-rooms/${encodeURIComponent(roomToken)}`, {
        method: "DELETE",
        headers: {
          "Authorization": `Bearer ${token}`,
          "Accept": "application/json"
        }
      });
      if (response.ok) {
        setHistory(prev => prev.filter(c => c.chat_room_token !== roomToken));
        if (activeChatRoomToken === roomToken) {
          onNewChat();
        }
      }
    } catch (err) {
      console.error("Failed to delete chat", err);
    }
  };

  return (
    <div
      className={`fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 flex-col overflow-hidden border-r border-slate-200 bg-white transition-all duration-300 md:relative md:translate-x-0 ${
        isOpen ? "translate-x-0" : "-translate-x-full"
      } ${isDesktopOpen ? "md:w-64" : "md:w-16"}`}
    >
      {/* Inner fixed-width wrapper so content doesn't squish while width animates */}
      <div className={`flex h-full w-64 flex-col ${isDesktopOpen ? "md:w-64" : "md:w-16"}`}>
        {/* Header */}
        <div className={`flex h-14 items-center border-b border-slate-100 ${isDesktopOpen ? "justify-between px-4" : "md:justify-center md:px-0 justify-between px-4"}`}>
          <div className="flex items-center gap-2">
            <img src={snoutiq_app_icon} alt="Snoutiq" className="h-8 w-8 rounded-full" />
          </div>
          <div className="flex items-center gap-1">
            {/* Desktop collapse/expand toggle */}
            <button
              onClick={() => setIsDesktopOpen && setIsDesktopOpen(!isDesktopOpen)}
              className={`hidden md:inline-flex text-slate-400 hover:text-slate-800 p-1.5 rounded-md hover:bg-slate-100 transition-colors ${!isDesktopOpen ? "md:hidden" : ""}`}
              title="Collapse sidebar"
            >
              <ChevronLeft size={18} />
            </button>
            {/* Mobile close button */}
            <button onClick={() => setIsOpen(false)} className="md:hidden text-slate-500 hover:text-slate-800">
              <X size={20} />
            </button>
          </div>
        </div>

        <div className="p-3">
          {/* New Chat Button */}
          <button 
            onClick={onNewChat}
            title="New chat"
            className={`flex w-full items-center gap-2 rounded-xl border border-slate-200 bg-white py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 shadow-sm transition-all ${isDesktopOpen ? "px-3" : "md:justify-center md:px-0 px-3"}`}
          >
            <Plus size={16} />
            <span className={!isDesktopOpen ? "md:hidden" : ""}>New chat</span>
          </button>
        </div>

        {/* Desktop icon-rail: quick access to Pages when collapsed */}
        {!isDesktopOpen && (
          <div className="hidden md:flex flex-col items-center gap-1 px-2 pb-3">
            {PAGES.map((page) => {
              const Icon = page.icon;
              return (
                <NavLink
                  key={page.name}
                  to={page.path}
                  title={page.name}
                  className={({ isActive }) =>
                    `flex h-10 w-10 items-center justify-center rounded-lg transition-colors ${
                      isActive ? "bg-slate-100 text-slate-900" : "text-slate-500 hover:bg-slate-100 hover:text-slate-900"
                    }`
                  }
                >
                  <Icon size={18} />
                </NavLink>
              );
            })}
          </div>
        )}

        <div className={`flex-1 overflow-y-auto px-3 pb-3 ${!isDesktopOpen ? "md:hidden" : ""}`}>
          {/* History Section */}
          {userId && (
            <div>
              <p className="px-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Recent</p>
              {loading ? (
                <div className="px-2 text-xs text-slate-500">Loading history...</div>
              ) : history.length === 0 ? (
                <div className="px-2 text-xs text-slate-500">No recent chats</div>
              ) : (
                  <div className="space-y-1">
                    {history.map((chat) => (
                      <div key={chat.chat_room_token} className="group flex w-full items-stretch min-w-0">
                        <button 
                          onClick={() => onSelectChat(chat.chat_room_token)}
                          className={`flex-1 flex items-start gap-2 rounded-l-lg px-2 py-2 text-left text-sm transition-colors overflow-hidden ${
                            activeChatRoomToken === chat.chat_room_token 
                              ? "bg-slate-100 text-slate-900 font-medium" 
                              : "text-slate-600 hover:bg-slate-100"
                          }`}
                        >
                          <MessageSquare size={14} className="mt-0.5 shrink-0 text-slate-400" />
                          <div className="flex-1 overflow-hidden min-w-0">
                            <div className="truncate w-full">{chat.name || "Untitled Chat"}</div>
                            {chat.summary && <div className="truncate w-full text-xs text-slate-400">{chat.summary}</div>}
                          </div>
                        </button>
                        <button 
                          onClick={(e) => handleDeleteChat(e, chat.chat_room_token)}
                          className={`flex items-center px-2 text-slate-300 hover:text-red-500 rounded-r-lg transition-colors shrink-0 ${
                            activeChatRoomToken === chat.chat_room_token ? "bg-slate-100" : "hover:bg-slate-100"
                          }`}
                          title="Delete chat"
                        >
                          <Trash2 size={14} />
                        </button>
                      </div>
                    ))}
                  </div>
              )}
            </div>
          )}
        </div>

        {/* Pages Section (Fixed at bottom) */}
        <div className={`p-3 border-t border-slate-100 shrink-0 ${!isDesktopOpen ? "md:hidden" : ""}`}>
          <p className="px-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Pages</p>
          <div className="space-y-0.5">
            {PAGES.map((page) => {
              const Icon = page.icon;
              return (
                <NavLink
                  key={page.name}
                  to={page.path}
                  className={({ isActive }) => 
                    `flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                      isActive 
                        ? "bg-slate-100 text-slate-900" 
                        : "text-slate-600 hover:bg-slate-50 hover:text-slate-900"
                    }`
                  }
                >
                  <Icon size={18} />
                  {page.name}
                </NavLink>
              );
            })}
          </div>
        </div>

        {/* User / Guest Session */}
        <div className="border-t border-slate-100 p-4 mt-auto">
          <div className={`flex items-center ${isDesktopOpen ? "justify-between" : "md:justify-center justify-between"}`}>
            <div className="flex items-center gap-3 text-sm font-medium text-slate-700">
              <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100">
                {userId ? <User size={20} className="text-slate-600" /> : <span className="text-xs">TR</span>}
              </div>
              <span className={`truncate ${!isDesktopOpen ? "md:hidden" : ""}`}>{userId ? (authState?.user?.name || "User") : "Guest User"}</span>
            </div>
            {userId && (
              <button 
                onClick={() => { localStorage.clear(); window.location.reload(); }} 
                className={`text-slate-400 hover:text-slate-700 transition-colors p-2 ${!isDesktopOpen ? "md:hidden" : ""}`}
                title="Logout"
              >
                <LogOut size={18} />
              </button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}