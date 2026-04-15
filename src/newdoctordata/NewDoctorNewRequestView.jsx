"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { useNavigate } from "react-router-dom";
import {
  ArrowLeft,
  Check,
  ChevronDown,
  CreditCard,
  IndianRupee,
  Phone,
  User,
  PawPrint,
  Search,
} from "lucide-react";

const TOTAL_STEPS = 2;
const DOG_BREEDS_URL = "https://snoutiq.com/backend/api/dog-breeds/all";
const CAT_BREEDS_URL = "https://snoutiq.com/backend/api/cat-breeds/with-indian";

const fieldBase =
  "h-[50px] w-full rounded-2xl border border-[#d7dee8] bg-white px-4 text-[15px] text-[#1e293b] outline-none placeholder:text-[#94a3b8] shadow-sm focus:border-[#16a34a] focus:bg-[#fcfffd] focus:ring-0";

const sectionLabel =
  "mb-3 text-[12px] font-bold uppercase tracking-[0.08em] text-[#475467]";

const primaryBtn =
  "flex h-[54px] w-full items-center justify-center gap-2 rounded-2xl bg-[#2fd161] px-4 text-[17px] font-bold text-white shadow-[0_10px_22px_rgba(47,209,97,0.24)] active:scale-[0.99] transition-transform";

const formatBreedLabel = (value) =>
  String(value || "")
    .trim()
    .replace(/\s+/g, " ")
    .replace(/\b\w/g, (char) => char.toUpperCase());

function normalizeBreedOptions(payload) {
  const collected = [];

  // DOG API special shape: { status: "success", breeds: { "affenpinscher": [], ... } }
  if (
    payload?.breeds &&
    typeof payload.breeds === "object" &&
    !Array.isArray(payload.breeds)
  ) {
    Object.keys(payload.breeds).forEach((breedKey) => {
      const label = formatBreedLabel(breedKey);
      if (label) {
        collected.push({
          id: breedKey,
          label,
        });
      }
    });
  }

  // Generic walker for other possible shapes, including cat API
  const walk = (node) => {
    if (!node) return;

    if (Array.isArray(node)) {
      node.forEach(walk);
      return;
    }

    if (typeof node === "string") {
      const label = formatBreedLabel(node);
      if (label) {
        collected.push({ id: label, label });
      }
      return;
    }

    if (typeof node !== "object") return;

    const directLabel =
      node.name ||
      node.breed_name ||
      node.breed ||
      node.title ||
      node.label ||
      node.pet_breed ||
      "";

    if (typeof directLabel === "string" && directLabel.trim()) {
      const label = formatBreedLabel(directLabel);
      const id = String(node.id ?? node.value ?? label);
      collected.push({ id, label });
    }

    Object.values(node).forEach((value) => {
      if (Array.isArray(value) || (value && typeof value === "object")) {
        walk(value);
      }
    });
  };

  walk(payload);

  const uniqueMap = new Map();
  collected.forEach((item) => {
    const key = item.label.toLowerCase();
    if (!uniqueMap.has(key)) {
      uniqueMap.set(key, item);
    }
  });

  return Array.from(uniqueMap.values()).sort((a, b) =>
    a.label.localeCompare(b.label),
  );
}

export default function NewDoctorNewRequestView() {
  const navigate = useNavigate();
  const breedMenuRef = useRef(null);

  const [step, setStep] = useState(0);
  const [form, setForm] = useState({
    phone: "",
    amount: "499",
    parentName: "",
    petName: "",
    petType: "",
    breed: "",
    gender: "Male",
    age: "",
  });

  const [breedOptions, setBreedOptions] = useState([]);
  const [breedsLoading, setBreedsLoading] = useState(false);
  const [breedQuery, setBreedQuery] = useState("");
  const [isBreedMenuOpen, setIsBreedMenuOpen] = useState(false);

  const parentPhonePreview = useMemo(() => {
    return form.phone?.trim() || "2342342342";
  }, [form.phone]);

  const filteredBreedOptions = useMemo(() => {
    const query = breedQuery.trim().toLowerCase();
    if (!query) return breedOptions;

    return breedOptions.filter((item) =>
      item.label.toLowerCase().includes(query),
    );
  }, [breedOptions, breedQuery]);

  const goNext = () => setStep((prev) => Math.min(prev + 1, TOTAL_STEPS - 1));

  const handleBack = () => {
    if (step > 0) {
      setStep((prev) => Math.max(prev - 1, 0));
      return;
    }

    if (window.history.length > 1) {
      navigate(-1);
    } else {
      navigate("/counsltflow/dashboard", { replace: true });
    }
  };

  const updateField = (key, value) =>
    setForm((prev) => ({ ...prev, [key]: value }));

  const handlePetTypeChange = (value) => {
    setForm((prev) => ({
      ...prev,
      petType: value,
      breed: "",
    }));
    setBreedQuery("");
    setBreedOptions([]);
    setIsBreedMenuOpen(false);
  };

  const handleBreedSelect = (breedLabel) => {
    setForm((prev) => ({
      ...prev,
      breed: breedLabel,
    }));
    setBreedQuery("");
    setIsBreedMenuOpen(false);
  };

  const handlePaymentReceived = () => {
    navigate("/counsltflow/digital-prescription", {
      state: {
        consultationId: null,
        paymentCompleted: true,
        lockUntilSubmit: true,
        fromNewRequest: true,
        patientData: {
          phone: form.phone,
          amount: form.amount,
          parentName: form.parentName,
          petName: form.petName,
          petType: form.petType,
          breed: form.breed,
          gender: form.gender,
          age: form.age,
        },
      },
    });
  };

  useEffect(() => {
    const handleOutsideClick = (event) => {
      if (!breedMenuRef.current?.contains(event.target)) {
        setIsBreedMenuOpen(false);
      }
    };

    document.addEventListener("mousedown", handleOutsideClick);
    return () => document.removeEventListener("mousedown", handleOutsideClick);
  }, []);

  useEffect(() => {
    let active = true;
    const controller = new AbortController();

    const fetchBreeds = async () => {
      if (!form.petType) {
        setBreedOptions([]);
        setBreedsLoading(false);
        return;
      }

      try {
        setBreedsLoading(true);

        const url = form.petType === "dog" ? DOG_BREEDS_URL : CAT_BREEDS_URL;

        const response = await fetch(url, {
          signal: controller.signal,
          headers: {
            Accept: "application/json",
          },
        });

        const data = await response.json().catch(() => ({}));
        if (!active) return;

        const normalized = normalizeBreedOptions(data);
        setBreedOptions(normalized);
      } catch (error) {
        if (error?.name !== "AbortError" && active) {
          setBreedOptions([]);
        }
      } finally {
        if (active) setBreedsLoading(false);
      }
    };

    fetchBreeds();

    return () => {
      active = false;
      controller.abort();
    };
  }, [form.petType]);

  return (
    <div className="min-h-screen bg-[#F8F8F8] flex flex-col">
      <div className="mx-auto w-full min-h-screen bg-[#FCFCFC]">
        {step === 0 && (
          <div className="flex flex-col min-h-screen">
            <Header title="New Consultation" onBack={handleBack} />

            <div className="flex-1 px-5 pt-6 pb-7">
              <div className="space-y-5">
                <div>
                  <p className={sectionLabel}>Mandatory Info</p>

                  <div className="space-y-4">
                    <div className="relative">
                      <Phone
                        size={18}
                        className="absolute left-4 top-1/2 -translate-y-1/2 text-[#98a2b3]"
                      />
                      <input
                        type="tel"
                        value={form.phone}
                        onChange={(e) => updateField("phone", e.target.value)}
                        placeholder="Parent WhatsApp Number"
                        className={`${fieldBase} pl-11`}
                      />
                    </div>

                    <div className="relative">
                      <IndianRupee
                        size={18}
                        className="absolute left-4 top-1/2 -translate-y-1/2 text-[#98a2b3]"
                      />
                      <input
                        type="number"
                        value={form.amount}
                        onChange={(e) => updateField("amount", e.target.value)}
                        placeholder="499"
                        className={`${fieldBase} pl-11`}
                      />
                    </div>
                  </div>
                </div>

                <div>
                  <p className={sectionLabel}>Pet & Parent (Optional)</p>

                  <div className="space-y-4">
                    <div className="grid grid-cols-2 gap-3">
                      <div className="relative">
                        <User
                          size={18}
                          className="absolute left-4 top-1/2 -translate-y-1/2 text-[#98a2b3]"
                        />
                        <input
                          type="text"
                          value={form.parentName}
                          onChange={(e) =>
                            updateField("parentName", e.target.value)
                          }
                          placeholder="Pet Parent Name"
                          className={`${fieldBase} pl-11`}
                        />
                      </div>

                      <div className="relative">
                        <PawPrint
                          size={18}
                          className="absolute left-4 top-1/2 -translate-y-1/2 text-[#98a2b3]"
                        />
                        <input
                          type="text"
                          value={form.petName}
                          onChange={(e) =>
                            updateField("petName", e.target.value)
                          }
                          placeholder="Pet Name"
                          className={`${fieldBase} pl-11`}
                        />
                      </div>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                      {/* Pet Type full width for better UX */}
                      <div className="relative">
                        <select
                          value={form.petType}
                          onChange={(e) => handlePetTypeChange(e.target.value)}
                          className={`${fieldBase} appearance-none pr-9`}
                        >
                          <option value="">Select Pet Type</option>
                          <option value="dog">Dog</option>
                          <option value="cat">Cat</option>
                        </select>

                        <ChevronDown
                          size={16}
                          className="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-slate-700"
                        />
                      </div>

                      {/* Breed full width for cleaner dropdown UX */}
                      <div className="relative" ref={breedMenuRef}>
                        <button
                          type="button"
                          disabled={!form.petType}
                          onClick={() => {
                            if (!form.petType) return;
                            setIsBreedMenuOpen((prev) => !prev);
                          }}
                          className={`${fieldBase} flex items-center justify-between text-left ${
                            !form.petType ? "text-[#8a94a6]" : "text-slate-700"
                          }`}
                        >
                          <span className="truncate">
                            {form.breed
                              ? form.breed
                              : !form.petType
                                ? "Select Pet Type First"
                                : breedsLoading
                                  ? "Loading breeds..."
                                  : "Select Breed"}
                          </span>

                          <ChevronDown
                            size={16}
                            className={`shrink-0 text-slate-700 transition-transform ${
                              isBreedMenuOpen ? "rotate-180" : ""
                            }`}
                          />
                        </button>

                        {isBreedMenuOpen && form.petType ? (
                          <div className="absolute left-0 right-0 top-[calc(100%+10px)] z-30 overflow-hidden rounded-2xl border border-[#e8eaee] bg-white shadow-[0_18px_40px_rgba(15,23,42,0.12)]">
                            <div className="border-b border-slate-100 p-3">
                              <div className="relative">
                                <Search
                                  size={16}
                                  className="absolute left-3 top-1/2 -translate-y-1/2 text-[#98a2b3]"
                                />
                                <input
                                  type="text"
                                  value={breedQuery}
                                  onChange={(e) =>
                                    setBreedQuery(e.target.value)
                                  }
                                  placeholder="Search breed"
                                  autoFocus
                                  className="h-[42px] w-full rounded-xl border border-[#e8eaee] bg-[#f4f5f7] pl-10 pr-3 text-[14px] text-slate-700 outline-none"
                                />
                              </div>
                            </div>

                            <div className="max-h-52 overflow-y-auto p-2">
                              {filteredBreedOptions.length > 0 ? (
                                filteredBreedOptions.map((item) => (
                                  <button
                                    key={item.id}
                                    type="button"
                                    onClick={() =>
                                      handleBreedSelect(item.label)
                                    }
                                    className={`flex w-full items-center rounded-xl px-3 py-2.5 text-left text-[14px] transition ${
                                      form.breed === item.label
                                        ? "bg-green-50 text-green-700"
                                        : "text-slate-700 hover:bg-slate-50"
                                    }`}
                                  >
                                    {item.label}
                                  </button>
                                ))
                              ) : (
                                <div className="px-3 py-3 text-sm text-[#98a2b3]">
                                  {breedsLoading
                                    ? "Loading breeds..."
                                    : "No breeds found"}
                                </div>
                              )}
                            </div>
                          </div>
                        ) : null}
                      </div>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                      <div className="relative">
                        <select
                          value={form.gender}
                          onChange={(e) =>
                            updateField("gender", e.target.value)
                          }
                          className={`${fieldBase} appearance-none pr-9`}
                        >
                          <option>Male</option>
                          <option>Female</option>
                        </select>

                        <ChevronDown
                          size={16}
                          className="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-slate-700"
                        />
                      </div>

                      <input
                        type="text"
                        value={form.age}
                        onChange={(e) => updateField("age", e.target.value)}
                        placeholder="Age (e.g. 2 years)"
                        className={fieldBase}
                      />
                    </div>
                  </div>
                </div>
              </div>

              <div className="mt-auto pt-[5rem]">
                <button type="button" onClick={goNext} className={primaryBtn}>
                  Send Payment Link
                  <CreditCard size={18} />
                </button>
              </div>
            </div>
          </div>
        )}

        {step === 1 && (
          <div className="flex flex-col min-h-screen">
            <Header title="Payment Status" onBack={handleBack} />

            <div className="flex-1 flex flex-col items-center justify-center px-6 text-center">
              <div className="relative mb-7">
                <div className="w-[96px] h-[96px] rounded-full border-[4px] border-[#f4c691] flex items-center justify-center bg-transparent">
                  <IndianRupee size={38} className="text-[#f97316]" />
                </div>
                <div className="absolute inset-0 rounded-full border-[4px] border-transparent border-t-[#f97316] rotate-[20deg]" />
              </div>

              <h2 className="text-[20px] font-bold text-[#0f2749] mb-3">
                Awaiting Payment
              </h2>

              <p className="text-[15px] leading-7 text-[#667085] max-w-[290px]">
                Payment link sent to {parentPhonePreview}.
                <br />
                Waiting for confirmation...
              </p>

              <button
                type="button"
                onClick={handlePaymentReceived}
                className="mt-12 h-[50px] px-8 rounded-full bg-[#2fd161] text-white text-[15px] font-bold flex items-center gap-2 shadow-[0_10px_22px_rgba(47,209,97,0.24)] active:scale-[0.98] transition-transform"
              >
                Simulate Payment Received
                <span className="flex h-5 w-5 items-center justify-center rounded-full border border-white/70">
                  <Check size={12} />
                </span>
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

function Header({ title, onBack }) {
  return (
    <div className="flex items-center gap-3 px-5 h-[68px] bg-[#16a34a] text-white shadow-[0_2px_12px_rgba(0,0,0,0.08)]">
      <button
        type="button"
        onClick={onBack}
        className="flex h-9 w-9 items-center justify-center rounded-full active:scale-95 transition"
      >
        <ArrowLeft size={22} />
      </button>

      <h1 className="text-[18px] font-bold">{title}</h1>
    </div>
  );
}
