import React, { useState, useEffect, useRef } from "react";
import { ChevronDown, Check, Plus, Dog, Cat } from "lucide-react";
import { readAiAuthState, persistAiAuthState } from "../ai/AiAuth";

export default function PetSelectorDropdown({ onAddNewPet }) {
  const [isOpen, setIsOpen] = useState(false);
  const [authState, setAuthState] = useState(() => readAiAuthState());
  const dropdownRef = useRef(null);

  const refreshState = () => {
    setAuthState(readAiAuthState());
  };

  useEffect(() => {
    window.addEventListener("snoutiq_pet_changed", refreshState);
    window.addEventListener("snoutiq_auth_changed", refreshState);
    window.addEventListener("storage", refreshState);
    return () => {
      window.removeEventListener("snoutiq_pet_changed", refreshState);
      window.removeEventListener("snoutiq_auth_changed", refreshState);
      window.removeEventListener("storage", refreshState);
    };
  }, []);

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const user = authState?.user || {};
  const activePetId = user.pet_id || user.pet?.id || (user.pets && user.pets[0] && user.pets[0].id);
  
  // Extract and normalize pets list from auth state
  const rawPets = Array.isArray(user.pets) && user.pets.length > 0 
    ? user.pets 
    : (user.pet ? [user.pet] : []);

  // Deduplicate pets by id or name
  const petsMap = new Map();
  rawPets.forEach(p => {
    if (!p) return;
    const key = p.id || p.pet_id || p.name || p.pet_name;
    if (key && !petsMap.has(key)) {
      petsMap.set(key, p);
    }
  });

  const petsList = Array.from(petsMap.values()).filter(p => p && (p.name || p.pet_name));

  // Determine active pet object
  const currentPet = petsList.find(p => String(p.id || p.pet_id) === String(activePetId)) 
    || petsList[0] 
    || user.pet 
    || null;

  const handleSelectPet = (pet) => {
    const nextUser = {
      ...user,
      pet: pet,
      pet_id: pet.id || pet.pet_id || user.pet_id,
      pet_name: pet.name || pet.pet_name || user.pet_name,
      breed: pet.breed || user.breed,
      pet_gender: pet.pet_gender || user.pet_gender,
      pet_age: pet.pet_age ?? user.pet_age,
      pet_type: pet.pet_type || pet.species || user.pet_type || "dog",
      pet_dob: pet.pet_dob || pet.dob || user.pet_dob,
    };

    persistAiAuthState({ user: nextUser, token: authState.token });
    window.dispatchEvent(new Event("snoutiq_pet_changed"));
    setIsOpen(false);
  };

  if (!authState?.token || petsList.length === 0) {
    return null;
  }

  const currentPetName = currentPet?.name || currentPet?.pet_name || "My Pet";
  const currentPetType = String(currentPet?.pet_type || currentPet?.species || "dog").toLowerCase();
  const isCat = currentPetType === "cat";

  return (
    <div className="relative inline-block text-left" ref={dropdownRef}>
      <button
        type="button"
        onClick={() => setIsOpen(!isOpen)}
        className="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-800 shadow-2xs hover:bg-slate-50 transition-all focus:outline-none"
      >
        <span className="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
          {isCat ? <Cat size={13} /> : <Dog size={13} />}
        </span>
        <span className="max-w-[110px] truncate">{currentPetName}</span>
        <ChevronDown size={14} className={`text-slate-400 transition-transform duration-200 ${isOpen ? "rotate-180" : ""}`} />
      </button>

      {isOpen && (
        <div className="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-100 bg-white p-1.5 shadow-xl ring-1 ring-black/5 z-[100] animate-[fadeIn_0.15s_ease-out]">
          <div className="px-3 py-2 text-[10px] font-bold tracking-wider text-slate-400 uppercase">
            Select Active Pet
          </div>
          <div className="max-h-60 overflow-y-auto space-y-0.5">
            {petsList.map((pet, idx) => {
              const pId = pet.id || pet.pet_id;
              const pName = pet.name || pet.pet_name || `Pet #${pId || idx + 1}`;
              const pBreed = pet.breed && pet.breed !== "Unknown" ? pet.breed : null;
              const pType = String(pet.pet_type || pet.species || "dog").toLowerCase();
              const isSelected = String(pId) === String(currentPet?.id || currentPet?.pet_id);

              return (
                <button
                  key={pId || idx}
                  onClick={() => handleSelectPet(pet)}
                  className={`w-full flex items-center justify-between rounded-xl px-3 py-2 text-xs font-medium transition-colors ${
                    isSelected ? "bg-emerald-50 text-emerald-900 font-bold" : "text-slate-700 hover:bg-slate-50"
                  }`}
                >
                  <div className="flex items-center gap-2 min-w-0">
                    <span className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-full ${isSelected ? "bg-emerald-200 text-emerald-800" : "bg-slate-100 text-slate-500"}`}>
                      {pType === "cat" ? <Cat size={13} /> : <Dog size={13} />}
                    </span>
                    <div className="text-left min-w-0">
                      <p className="font-bold truncate text-slate-800">{pName}</p>
                      {pBreed && <p className="text-[10px] text-slate-400 truncate">{pBreed}</p>}
                    </div>
                  </div>
                  {isSelected && <Check size={14} className="text-emerald-600 shrink-0 ml-2" />}
                </button>
              );
            })}
          </div>
        </div>
      )}
    </div>
  );
}
