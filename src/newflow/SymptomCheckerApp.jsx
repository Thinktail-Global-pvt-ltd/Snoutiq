import React, { useState } from "react";
import SymptomCheckerSidebar from "./SymptomCheckerSidebar";
import SymptomCheckerFlow from "./SymptomCheckerFlow";
import { Menu } from "lucide-react";
import snoutiq_app_icon from "../assets/snoutiq_app_icon.png";
import snoutiq_app_icon1 from "../assets/images/logo.png";

import { GoogleOAuthProvider } from "@react-oauth/google";
import PetSelectorDropdown from "./PetSelectorDropdown";

export default function SymptomCheckerApp() {
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);
  const [activeChatRoomToken, setActiveChatRoomToken] = useState(null);
  const [historyRefreshKey, setHistoryRefreshKey] = useState(0);

  const handleNewChat = () => {
    setActiveChatRoomToken(null);
    setIsSidebarOpen(false);
  };

  const handleOpenPetModal = () => {
    window.dispatchEvent(new Event("snoutiq_open_pet_modal"));
  };

  return (
    <GoogleOAuthProvider clientId="325007826401-dhsrqhkpoeeei12gep3g1sneeg5880o7.apps.googleusercontent.com">
      <div className="flex h-screen w-full overflow-hidden bg-slate-50">
      <SymptomCheckerSidebar 
        isOpen={isSidebarOpen} 
        setIsOpen={setIsSidebarOpen}
        activeChatRoomToken={activeChatRoomToken}
        onSelectChat={(token) => {
          setActiveChatRoomToken(token);
          setIsSidebarOpen(false);
        }}
        onNewChat={handleNewChat} 
        historyRefreshKey={historyRefreshKey}
      />
      
      <div className="flex flex-1 flex-col relative w-full h-full">
        {/* Mobile Header */}
        <div className="md:hidden flex h-14 items-center justify-between border-b border-slate-200 bg-white px-4">
          <div className="flex items-center">
            <button 
              onClick={() => setIsSidebarOpen(true)}
              className="text-slate-500 hover:text-slate-800"
            >
              <Menu size={24} />
            </button>
            <img src={snoutiq_app_icon1} alt="Snoutiq" className="h-6 w-6 rounded-xl ml-3" />
            <span className="ml-2 font-bold text-slate-900">AI Chat</span>
          </div>

          <PetSelectorDropdown onAddNewPet={handleOpenPetModal} />
        </div>

        {/* Desktop Header */}
        <div className="hidden md:flex h-14 items-center justify-between border-b border-slate-200 bg-white px-6">
          <div className="flex items-center gap-2">
            <img src={snoutiq_app_icon1} alt="Snoutiq" className="h-4 ml-2" />
            {/* <span className="font-semibold text-slate-700">AI Chat</span> */}
          </div>

          <PetSelectorDropdown onAddNewPet={handleOpenPetModal} />
        </div>

        <div className="flex-1 overflow-y-auto bg-slate-50">
          <SymptomCheckerFlow 
            activeChatRoomToken={activeChatRoomToken}
            setActiveChatRoomToken={setActiveChatRoomToken}
            onMessageSent={() => setHistoryRefreshKey(prev => prev + 1)}
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
