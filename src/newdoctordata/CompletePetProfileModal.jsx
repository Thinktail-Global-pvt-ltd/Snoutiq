import React, { useEffect, useMemo, useRef, useState } from "react";
import {
  AlertCircle,
  LockKeyhole,
  User,
  PawPrint,
  Scale,
  Search,
  ChevronDown,
} from "lucide-react";
import {
  EMPTY_PENDING_PATIENT_DATA,
  PENDING_PRESCRIPTION_FIELD_LABELS,
  getPendingPrescriptionMissingFields,
} from "./doctorPendingPrescriptionService";

const DOG_BREEDS_URL = "https://snoutiq.com/backend/api/dog-breeds/all";
const CAT_BREEDS_URL = "https://snoutiq.com/backend/api/cat-breeds/with-indian";

const fieldBase =
  "h-[50px] w-full rounded-2xl border border-[#d7dee8] bg-white px-4 text-[15px] text-[#1e293b] outline-none placeholder:text-[#94a3b8] shadow-sm focus:border-[#16a34a] focus:bg-[#fcfffd] focus:ring-0";

const sectionLabel =
  "mb-3 text-[12px] font-bold uppercase tracking-[0.08em] text-[#475467]";

const formatBreedLabel = (value) =>
  String(value || "")
    .trim()
    .replace(/\s+/g, " ")
    .replace(/\b\w/g, (char) => char.toUpperCase());

function normalizeBreedOptions(payload) {
  const collected = [];

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

const normalizePetTypeValue = (value) => {
  const next = String(value || "").trim().toLowerCase();
  if (["dog", "cat", "bird", "other"].includes(next)) return next;
  return "";
};

const normalizeIncomingPatientData = (patientData) => ({
  ...EMPTY_PENDING_PATIENT_DATA,
  ...patientData,
  petType: normalizePetTypeValue(patientData?.petType),
});

const PET_PARENT_FIELD_KEYS = ["parentName", "phone"];
const PET_PROFILE_FIELD_KEYS = [
  "petName",
  "petType",
  "breed",
  "gender",
  "age",
  "weight",
];

export default function CompletePetProfileModal({
  isOpen,
  patientData = EMPTY_PENDING_PATIENT_DATA,
  missingFields = [],
  onSave,
}) {
  const breedMenuRef = useRef(null);

  const [form, setForm] = useState(EMPTY_PENDING_PATIENT_DATA);
  const [error, setError] = useState("");
  const [isSaving, setIsSaving] = useState(false);

  const [breedOptions, setBreedOptions] = useState([]);
  const [breedsLoading, setBreedsLoading] = useState(false);
  const [breedQuery, setBreedQuery] = useState("");
  const [isBreedMenuOpen, setIsBreedMenuOpen] = useState(false);

  const visibleFieldKeys = useMemo(() => {
    const normalizedPatientData = normalizeIncomingPatientData(patientData);
    const computedMissingFields =
      getPendingPrescriptionMissingFields(normalizedPatientData);
    const requestedFields = missingFields.length
      ? missingFields
      : computedMissingFields;

    return Array.from(
      new Set(
        requestedFields.filter((field) => computedMissingFields.includes(field)),
      ),
    );
  }, [missingFields, patientData]);

  useEffect(() => {
    if (!isOpen) return;

    setForm(normalizeIncomingPatientData(patientData));
    setError("");
    setBreedQuery("");
    setIsBreedMenuOpen(false);
  }, [isOpen, patientData]);

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
      if (!isOpen || !["dog", "cat"].includes(form.petType)) {
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
      } catch (fetchError) {
        if (fetchError?.name !== "AbortError" && active) {
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
  }, [form.petType, isOpen]);

  const highlightedFields = useMemo(
    () => new Set(visibleFieldKeys),
    [visibleFieldKeys],
  );

  const filteredBreedOptions = useMemo(() => {
    const query = breedQuery.trim().toLowerCase();
    if (!query) return breedOptions;

    return breedOptions.filter((item) =>
      item.label.toLowerCase().includes(query),
    );
  }, [breedOptions, breedQuery]);

  const updateField = (field, value) => {
    setForm((prev) => ({
      ...prev,
      [field]: value,
    }));
  };

  const handlePetTypeChange = (value) => {
    updateField("petType", value);
    updateField("breed", "");
    setBreedQuery("");
    setBreedOptions([]);
    setIsBreedMenuOpen(false);
  };

  const handleBreedSelect = (breedLabel) => {
    updateField("breed", breedLabel);
    setBreedQuery("");
    setIsBreedMenuOpen(false);
  };

  const handleSubmit = async (event) => {
    event.preventDefault();

    const nextMissingFields = getPendingPrescriptionMissingFields(form);

    if (nextMissingFields.length) {
      setError(
        `Please complete: ${nextMissingFields
          .map((field) => PENDING_PRESCRIPTION_FIELD_LABELS[field] || field)
          .join(", ")}`
      );
      return;
    }

    if (typeof onSave !== "function") return;

   try {
  setIsSaving(true);
  setError("");
  console.log("Submitting profile form:", form);
  await onSave(form);
  console.log("Profile save success");
} catch (saveError) {
  console.log("Profile save failed:", saveError);
  setError(saveError?.message || "Unable to save pet profile.");
} finally {
  setIsSaving(false);
}
  };

  if (!isOpen) return null;

  const getInputClassName = (field) =>
    `${fieldBase} ${
      highlightedFields.has(field) ? "!border-amber-400 !bg-amber-50/80" : ""
    }`;

  const getBreedTriggerClassName = (field) =>
    `${fieldBase} flex items-center justify-between text-left ${
      !form.petType
        ? "text-[#8a94a6]"
        : form.breed
          ? "text-slate-700"
          : "text-[#8a94a6]"
    } ${
      highlightedFields.has(field) ? "!border-amber-400 !bg-amber-50/80" : ""
    }`;

  const fieldIconClass =
    "absolute left-4 top-1/2 -translate-y-1/2 text-[#98a2b3]";

  const showPetParentSection = PET_PARENT_FIELD_KEYS.some((field) =>
    highlightedFields.has(field),
  );
  const showPetProfileSection = PET_PROFILE_FIELD_KEYS.some((field) =>
    highlightedFields.has(field),
  );

  if (!isOpen || !visibleFieldKeys.length) return null;

  return (
    <div className="fixed inset-0 z-[90] bg-[#0b1726]/50 backdrop-blur-[2px]">
      <div className="flex min-h-full items-end justify-center md:items-center md:p-4">
        <div
          className="doctor-panel doctor-panel-strong flex w-full max-w-[430px] flex-col overflow-hidden rounded-t-[28px] md:rounded-[28px]"
          style={{
            minHeight: "70dvh",
            maxHeight: "92dvh",
          }}
        >
          <div className="flex justify-center bg-transparent px-4 pt-3">
            <div className="h-1.5 w-12 rounded-full bg-[rgba(20,33,61,0.18)]" />
          </div>

          <div className="px-5 pb-3 pt-2">
            <div className="doctor-chip">
              <LockKeyhole size={14} />
              Complete Details
            </div>

            <h2 className="mt-3 text-[16px] font-extrabold leading-[1.35] text-[var(--doctor-ink)]">
              Add pet profile before prescription
            </h2>

            <p className="doctor-muted mt-1 text-[13px] leading-5">
              These details are required once and will be reused for future
              consultations.
            </p>
          </div>

          <form onSubmit={handleSubmit} className="flex min-h-0 flex-1 flex-col">
            <div className="flex-1 overflow-y-auto px-5 pb-4">
              <div className="space-y-4">
                {showPetParentSection ? (
                  <section className="doctor-panel rounded-[24px] p-4">
                    <p className={sectionLabel}>Pet Parent Details</p>

                    <div className="space-y-4">
                      {highlightedFields.has("parentName") ? (
                        <label className="block">
                          <span className="doctor-label">Parent name</span>
                          <div className="relative">
                            <User size={18} className={fieldIconClass} />
                            <input
                              value={form.parentName}
                              onChange={(event) =>
                                updateField("parentName", event.target.value)
                              }
                              className={`${getInputClassName("parentName")} pl-11`}
                              placeholder="Pet Parent Name"
                            />
                          </div>
                        </label>
                      ) : null}
                    </div>
                  </section>
                ) : null}

                {showPetProfileSection ? (
                  <section className="doctor-panel rounded-[24px] p-4">
                    <p className={sectionLabel}>Pet Details</p>

                    <div className="space-y-4">
                      {highlightedFields.has("petName") ? (
                        <label className="block">
                          <span className="doctor-label">Pet name</span>
                          <div className="relative">
                            <PawPrint size={18} className={fieldIconClass} />
                            <input
                              value={form.petName}
                              onChange={(event) =>
                                updateField("petName", event.target.value)
                              }
                              className={`${getInputClassName("petName")} pl-11`}
                              placeholder="Pet Name"
                            />
                          </div>
                        </label>
                      ) : null}

                      {highlightedFields.has("petType") ||
                      highlightedFields.has("breed") ? (
                        <div className="grid grid-cols-2 gap-3">
                          {highlightedFields.has("petType") ? (
                            <label className="block">
                              <span className="doctor-label">Pet type</span>
                              <div className="relative">
                                <select
                                  value={form.petType}
                                  onChange={(event) =>
                                    handlePetTypeChange(event.target.value)
                                  }
                                  className={`${getInputClassName("petType")} appearance-none pr-9`}
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
                            </label>
                          ) : (
                            <div />
                          )}

                          {highlightedFields.has("breed") ? (
                            <label className="block">
                              <span className="doctor-label">Breed</span>

                              {["dog", "cat"].includes(form.petType) ? (
                                <div className="relative" ref={breedMenuRef}>
                                  <button
                                    type="button"
                                    disabled={!form.petType}
                                    onClick={() => {
                                      if (!form.petType) return;
                                      setIsBreedMenuOpen((prev) => !prev);
                                    }}
                                    className={getBreedTriggerClassName("breed")}
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
                                            onChange={(event) =>
                                              setBreedQuery(event.target.value)
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
                              ) : (
                                <input
                                  value={form.breed}
                                  onChange={(event) =>
                                    updateField("breed", event.target.value)
                                  }
                                  className={getInputClassName("breed")}
                                  placeholder="Enter breed"
                                />
                              )}
                            </label>
                          ) : null}
                        </div>
                      ) : null}

                      {highlightedFields.has("gender") ||
                      highlightedFields.has("age") ? (
                        <div className="grid grid-cols-2 gap-3">
                          {highlightedFields.has("gender") ? (
                            <label className="block">
                              <span className="doctor-label">Gender</span>
                              <div className="relative">
                                <select
                                  value={form.gender}
                                  onChange={(event) =>
                                    updateField("gender", event.target.value)
                                  }
                                  className={`${getInputClassName("gender")} appearance-none pr-9`}
                                >
                                  <option value="">Select gender</option>
                                  <option value="Male">Male</option>
                                  <option value="Female">Female</option>
                                  <option value="Unknown">Unknown</option>
                                </select>

                                <ChevronDown
                                  size={16}
                                  className="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-slate-700"
                                />
                              </div>
                            </label>
                          ) : (
                            <div />
                          )}

                          {highlightedFields.has("age") ? (
                            <label className="block">
                              <span className="doctor-label">Age</span>
                              <input
                                value={form.age}
                                onChange={(event) =>
                                  updateField("age", event.target.value)
                                }
                                className={getInputClassName("age")}
                                placeholder="Age (e.g. 2 years)"
                              />
                            </label>
                          ) : null}
                        </div>
                      ) : null}

                      {highlightedFields.has("weight") ? (
                        <label className="block">
                          <span className="doctor-label">Weight</span>
                          <div className="relative">
                            <Scale size={18} className={fieldIconClass} />
                            <input
                              value={form.weight}
                              onChange={(event) =>
                                updateField("weight", event.target.value)
                              }
                              className={`${getInputClassName("weight")} pl-11`}
                              placeholder="Enter weight"
                            />
                          </div>
                        </label>
                      ) : null}
                    </div>
                  </section>
                ) : null}

                {error && (
                  <div className="rounded-[1rem] border border-red-200 bg-red-50 px-3.5 py-3">
                    <div className="flex items-start gap-2.5">
                      <AlertCircle
                        size={16}
                        className="mt-0.5 shrink-0 text-red-600"
                      />
                      <p className="text-[13px] leading-5 text-red-700">
                        {error}
                      </p>
                    </div>
                  </div>
                )}
              </div>
            </div>

            <div className="border-t border-[rgba(20,33,61,0.08)] bg-[rgba(255,255,255,0.92)] px-5 py-3">
              <button
                type="submit"
                disabled={isSaving}
                className="doctor-button text-[15px] disabled:cursor-not-allowed disabled:opacity-70"
              >
                {isSaving ? "Saving..." : "Save and continue"}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
