import React, { useState } from "react";
import { Helmet } from "react-helmet-async";
import SymptomCheckerSidebar from "./SymptomCheckerSidebar";
import SymptomCheckerFlow from "./SymptomCheckerFlow";
import { Menu, PanelLeft } from "lucide-react";
import snoutiq_app_icon from "../assets/snoutiq_app_icon.png";
import snoutiq_app_icon1 from "../assets/images/logo.png";

import { GoogleOAuthProvider } from "@react-oauth/google";
import PetSelectorDropdown from "./PetSelectorDropdown";

export default function SymptomCheckerApp() {
  const [isSidebarOpen, setIsSidebarOpen] = useState(false); // mobile drawer
  const [isDesktopSidebarOpen, setIsDesktopSidebarOpen] = useState(true); // desktop collapse
  const [activeChatRoomToken, setActiveChatRoomToken] = useState(
    () => sessionStorage.getItem("snoutiq_active_chat_token") || null
  );
  const [historyRefreshKey, setHistoryRefreshKey] = useState(0);

  // Persist active chat token across refreshes
  const handleSetActiveChatRoomToken = (token) => {
    if (token) {
      sessionStorage.setItem("snoutiq_active_chat_token", token);
    } else {
      sessionStorage.removeItem("snoutiq_active_chat_token");
    }
    setActiveChatRoomToken(token);
  };

  const handleNewChat = () => {
    sessionStorage.removeItem("snoutiq_active_chat_token");
    setActiveChatRoomToken(null);
    setIsSidebarOpen(false);
  };

  const handleOpenPetModal = () => {
    window.dispatchEvent(new Event("snoutiq_open_pet_modal"));
  };

  return (
    <GoogleOAuthProvider clientId="325007826401-dhsrqhkpoeeei12gep3g1sneeg5880o7.apps.googleusercontent.com">
      <Helmet>
        <meta name="robots" content="index,follow" />
        <link rel="canonical" href="https://snoutiq.com/" />
      </Helmet>
      <div className="flex h-screen w-full overflow-hidden bg-slate-50">
      <SymptomCheckerSidebar 
        isOpen={isSidebarOpen} 
        setIsOpen={setIsSidebarOpen}
        isDesktopOpen={isDesktopSidebarOpen}
        setIsDesktopOpen={setIsDesktopSidebarOpen}
        activeChatRoomToken={activeChatRoomToken}
        onSelectChat={(token) => {
          handleSetActiveChatRoomToken(token);
          setIsSidebarOpen(false);
        }}
        onNewChat={handleNewChat} 
        historyRefreshKey={historyRefreshKey}
      />
      
      <div className="flex flex-1 flex-col relative w-full h-full min-w-0">
        {/* Mobile Header */}
        <div className="md:hidden flex h-14 items-center justify-between border-b border-slate-200 bg-white px-4">
          <div className="flex items-center">
            <button 
              onClick={() => setIsSidebarOpen(true)}
              className="text-slate-500 hover:text-slate-800"
            >
              <Menu size={24} />
            </button>
            <img src={snoutiq_app_icon1} alt="Snoutiq" className="h-4 ml-3" />
            {/* <span className="ml-2 font-bold text-slate-900">AI Chat</span> */}
          </div>

          <PetSelectorDropdown onAddNewPet={handleOpenPetModal} />
        </div>

        {/* Desktop Header */}
        <div className="hidden md:flex h-14 items-center justify-between border-b border-slate-200 bg-white px-6">
          <div className="flex items-center gap-2">
            {/* Expand button — only visible when the desktop sidebar is collapsed */}
            {!isDesktopSidebarOpen && (
              <button
                onClick={() => setIsDesktopSidebarOpen(true)}
                className="text-slate-500 hover:text-slate-800 p-1.5 rounded-md hover:bg-slate-100 transition-colors mr-1"
                title="Expand sidebar"
              >
                <PanelLeft size={20} />
              </button>
            )}
            <img src={snoutiq_app_icon1} alt="Snoutiq" className="h-4 ml-2" />
            {/* <span className="font-semibold text-slate-700">AI Chat</span> */}
          </div>

          <PetSelectorDropdown onAddNewPet={handleOpenPetModal} />
        </div>

        <div className="flex-1 overflow-y-auto bg-slate-50">
          <SymptomCheckerFlow 
            activeChatRoomToken={activeChatRoomToken}
            setActiveChatRoomToken={handleSetActiveChatRoomToken}
            onMessageSent={() => setHistoryRefreshKey(prev => prev + 1)}
            isDesktopSidebarOpen={isDesktopSidebarOpen}
          />
        </div>
      </div>
      
      {/* Mobile Overlay */}
      {isSidebarOpen && (
        <div 
          className="fixed inset-0 z-30 bg-slate-900/20 backdrop-blur-sm md:hidden"
          onClick={() => setIsSidebarOpen(false)}
        />
      )}
    </div>
    </GoogleOAuthProvider>
  );
}