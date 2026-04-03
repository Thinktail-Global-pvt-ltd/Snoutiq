import { useEffect, useRef, useState } from "react";
import {
  Send,
  User,
  AlertTriangle,
  Loader2,
  ChevronDown,
  Search,
} from "lucide-react";
import { apiBaseUrl, apiPost } from "../lib/api";

const EMPTY_PET_FORM = {
  owner_name: "",
  pet_name: "",
  species: "",
  breed: "",
  age: "",
  sex: "",
  weight: "",
  medical_history: "",
};

const SPECIES_OPTIONS = [
  { label: "Select species", value: "" },
  { label: "Dog", value: "Dog" },
  { label: "Cat", value: "Cat" },
  { label: "Other", value: "Other" },
];

const SEX_OPTIONS = [
  { label: "Select gender", value: "" },
  { label: "Male", value: "Male" },
  { label: "Female", value: "Female" },
  { label: "Unknown", value: "Unknown" },
];

const DOG_BREED_FALLBACK = [
  { label: "Mixed Breed", value: "mixed_breed" },
  { label: "Other", value: "other" },
];

const CAT_BREED_FALLBACK = [{ label: "Mixed / Other", value: "other" }];

function SnoutIQIcon({ className = "", alt = "SnoutIQ" }) {
  return (
    <img
      src="/favicon.png"
      alt={alt}
      className={`object-contain ${className}`.trim()}
    />
  );
}

function formatBreedName(breedKey, subBreed = null) {
  const cap = (input) =>
    String(input)
      .split(/[-_\s]/)
      .filter(Boolean)
      .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
      .join(" ");

  const base = cap(breedKey);
  if (!subBreed) return base;
  return `${cap(subBreed)} ${base}`;
}

async function fetchDogBreedOptions() {
  try {
    const response = await fetch(`${apiBaseUrl()}/api/dog-breeds/all`, {
      method: "GET",
      headers: {
        Accept: "application/json",
      },
    });
    const data = await response.json().catch(() => ({}));

    if (data?.status === "success" && data?.breeds) {
      const list = [];

      Object.keys(data.breeds).forEach((breedKey) => {
        const subBreeds = data.breeds[breedKey];
        if (!subBreeds || subBreeds.length === 0) {
          list.push({ label: formatBreedName(breedKey), value: breedKey });
          return;
        }

        list.push({ label: formatBreedName(breedKey), value: breedKey });
        subBreeds.forEach((subBreed) => {
          list.push({
            label: formatBreedName(breedKey, subBreed),
            value: `${breedKey}/${subBreed}`,
          });
        });
      });

      list.sort((a, b) => a.label.localeCompare(b.label));
      return {
        options: [...list, ...DOG_BREED_FALLBACK],
        error: "",
      };
    }

    return {
      options: DOG_BREED_FALLBACK,
      error: "Could not load dog breeds. Using basic options.",
    };
  } catch {
    return {
      options: DOG_BREED_FALLBACK,
      error: "Network error while loading dog breeds.",
    };
  }
}

async function fetchCatBreedOptions() {
  try {
    const response = await fetch(`${apiBaseUrl()}/api/cat-breeds/with-indian`, {
      method: "GET",
      headers: {
        Accept: "application/json",
      },
    });
    const data = await response.json().catch(() => ({}));

    if (data?.success && Array.isArray(data?.data)) {
      const list = data.data
        .map((breed) => ({
          label: breed?.name || breed?.id || "Unknown",
          value: breed?.name || breed?.id || "unknown",
        }))
        .filter((item) => item.label && item.value);

      list.sort((a, b) => a.label.localeCompare(b.label));

      return {
        options: [...list, ...CAT_BREED_FALLBACK],
        error: "",
      };
    }

    return {
      options: CAT_BREED_FALLBACK,
      error: "Could not load cat breeds. Using basic options.",
    };
  } catch {
    return {
      options: CAT_BREED_FALLBACK,
      error: "Network error while loading cat breeds.",
    };
  }
}

function toText(value) {
  if (value === null || value === undefined) return "";
  if (Array.isArray(value)) {
    return value
      .map((item) => toText(item))
      .filter(Boolean)
      .join(", ");
  }
  if (typeof value === "object") {
    return Object.entries(value)
      .map(([key, item]) => {
        const text = toText(item);
        return text ? `${formatLabel(key)}: ${text}` : "";
      })
      .filter(Boolean)
      .join(", ");
  }
  return String(value).trim();
}

function toList(value) {
  if (Array.isArray(value)) {
    return value
      .map((item) => toText(item))
      .filter(Boolean);
  }

  const text = toText(value);
  if (!text) return [];

  const splitByNewline = text
    .split(/\n+/)
    .map((item) => item.replace(/^[\s\-*]+/, "").trim())
    .filter(Boolean);

  if (splitByNewline.length > 1) return splitByNewline;
  return [text];
}

function formatLabel(value) {
  return String(value || "")
    .replace(/[_-]+/g, " ")
    .replace(/\s+/g, " ")
    .trim()
    .replace(/\b\w/g, (char) => char.toUpperCase());
}

function getUrgencyClasses(value) {
  const normalized = String(value || "").trim().toLowerCase();
  if (normalized === "emergency" || normalized === "high") {
    return "bg-red-100 text-red-700 border-red-200";
  }
  if (normalized === "medium") {
    return "bg-amber-100 text-amber-700 border-amber-200";
  }
  if (normalized === "low") {
    return "bg-emerald-100 text-emerald-700 border-emerald-200";
  }
  return "bg-slate-100 text-slate-700 border-slate-200";
}

function getConfidenceClasses(value) {
  const normalized = String(value || "").trim().toLowerCase();
  if (normalized === "high") {
    return "bg-emerald-100 text-emerald-700 border-emerald-200";
  }
  if (normalized === "medium") {
    return "bg-sky-100 text-sky-700 border-sky-200";
  }
  return "bg-slate-100 text-slate-700 border-slate-200";
}

function hasYesContent(data) {
  return (
    toText(data?.summary) ||
    toText(data?.service_recommendation) ||
    toText(data?.when_to_see_vet) ||
    toText(data?.what_we_found) ||
    toText(data?.additional_notes) ||
    toList(data?.immediate_steps).length > 0 ||
    toList(data?.home_care_tips).length > 0
  );
}

function hasNoContent(data) {
  return (
    toText(data?.diagnosis_summary) ||
    toText(data?.urgency) ||
    toList(data?.possible_causes).length > 0 ||
    toList(data?.recommended_next_steps).length > 0 ||
    toList(data?.follow_up_questions).length > 0
  );
}

function MessageBubble({ message }) {
  const isUser = message.role === "user";

  return (
    <div
      className={`flex min-w-0 gap-2.5 ${
        isUser ? "justify-end" : "justify-start"
      }`}
    >
      {!isUser && (
        <div className="mt-1 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-brand/20">
          <SnoutIQIcon className="h-4.5 w-4.5" alt="SnoutIQ assistant" />
        </div>
      )}

      <div
        className={`min-w-0 break-words rounded-2xl ${
          isUser
            ? "max-w-[84%] rounded-tr-none bg-brand px-3.5 py-3 text-slate-900 sm:max-w-[72%]"
            : "flex-1 rounded-tl-none border border-slate-200 bg-slate-100/95 px-3 py-2.5 text-slate-800"
        }`}
      >
        {isUser ? (
          <p className="whitespace-pre-wrap break-words text-[13px] leading-5">
            {message.text}
          </p>
        ) : (
          <AssistantMessage message={message} />
        )}
      </div>

      {isUser && (
        <div className="mt-1 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-brand">
          <User className="h-4.5 w-4.5 text-slate-900" />
        </div>
      )}
    </div>
  );
}

function AssistantMessage({ message }) {
  if (message.variant === "error") {
    return (
      <div className="space-y-2">
        <div className="inline-flex items-center gap-2 rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">
          <AlertTriangle className="h-3.5 w-3.5" />
          Request error
        </div>
        <p className="whitespace-pre-wrap break-words text-sm leading-relaxed text-slate-800">
          {message.text}
        </p>
      </div>
    );
  }

  if (message.variant === "yes") {
    return <YesResponseCard data={message.data} />;
  }

  if (message.variant === "no") {
    return <NoResponseCard data={message.data} />;
  }

  return (
    <p className="whitespace-pre-wrap break-words text-sm leading-relaxed">
      {message.text}
    </p>
  );
}

function InfoCard({ title, value, compact = false, className = "" }) {
  if (!value) return null;

  return (
    <div
      className={`rounded-xl border border-slate-200 bg-white/85 ${
        compact ? "p-2.5" : "p-3"
      } ${className}`.trim()}
    >
      <p className="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">
        {title}
      </p>
      <p
        className={`mt-1.5 break-words text-slate-800 ${
          compact ? "text-[12.5px] leading-5" : "text-sm leading-relaxed"
        }`}
      >
        {value}
      </p>
    </div>
  );
}

function CompactSummary({ title, text, className = "" }) {
  if (!text) return null;

  return (
    <div
      className={`rounded-xl border border-slate-200 bg-white/90 p-2.5 ${className}`.trim()}
    >
      <p className="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">
        {title}
      </p>
      <p className="mt-1 break-words text-[12.5px] leading-5 text-slate-800">
        {text}
      </p>
    </div>
  );
}

function YesResponseCard({ data }) {
  const immediateSteps = toList(data?.immediate_steps);
  const homeCareTips = toList(data?.home_care_tips);
  const summary = toText(data?.summary);
  const serviceRecommendation = toText(data?.service_recommendation);
  const whenToSeeVet = toText(data?.when_to_see_vet);
  const whatWeFound = toText(data?.what_we_found);
  const additionalNotes = toText(data?.additional_notes);

  if (!hasYesContent(data)) {
    return (
      <p className="text-sm leading-relaxed text-slate-700">
        I could not find structured symptom details in the response.
      </p>
    );
  }

  return (
    <div className="space-y-2.5 text-sm text-slate-800">
      <div className="flex flex-wrap items-center gap-1.5">
        <span className="inline-flex items-center rounded-full border border-brand/20 bg-brand/10 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-brand">
          Detailed review
        </span>
        {toText(data?.urgency_level) ? (
          <span
            className={`inline-flex items-center rounded-full border px-2 py-1 text-[10px] font-semibold uppercase tracking-wide ${getUrgencyClasses(
              data.urgency_level,
            )}`}
          >
            Urgency: {formatLabel(data.urgency_level)}
          </span>
        ) : null}
        {toText(data?.confidence) ? (
          <span
            className={`inline-flex items-center rounded-full border px-2 py-1 text-[10px] font-semibold uppercase tracking-wide ${getConfidenceClasses(
              data.confidence,
            )}`}
          >
            Confidence: {formatLabel(data.confidence)}
          </span>
        ) : null}
      </div>

      <div className="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
        <CompactSummary
          title="Summary"
          text={summary}
          className="sm:col-span-2 xl:col-span-2"
        />
        <InfoCard
          title="Service Recommendation"
          value={serviceRecommendation}
          compact
        />
        <InfoCard title="When To See Vet" value={whenToSeeVet} compact />
        <InfoCard
          title="What We Found"
          value={whatWeFound}
          compact
          className="xl:col-span-2"
        />
        <InfoCard
          title="Additional Notes"
          value={additionalNotes}
          compact
          className="xl:col-span-2"
        />
      </div>

      {immediateSteps.length > 0 ? (
        <ResponseList
          title="Immediate Steps"
          items={immediateSteps}
          compact
          columns={immediateSteps.length >= 6 ? 3 : immediateSteps.length >= 4 ? 2 : 1}
        />
      ) : null}

      {homeCareTips.length > 0 ? (
        <ResponseList
          title="Home Care Tips"
          items={homeCareTips}
          compact
          columns={homeCareTips.length >= 6 ? 3 : homeCareTips.length >= 4 ? 2 : 1}
        />
      ) : null}
    </div>
  );
}

function NoResponseCard({ data }) {
  const possibleCauses = toList(data?.possible_causes);
  const recommendedNextSteps = toList(data?.recommended_next_steps);
  const followUpQuestions = toList(data?.follow_up_questions);
  const diagnosisSummary = toText(data?.diagnosis_summary);

  if (!hasNoContent(data)) {
    return (
      <p className="text-sm leading-relaxed text-slate-700">
        I could not find structured diagnosis details in the response.
      </p>
    );
  }

  return (
    <div className="space-y-2.5 text-sm text-slate-800">
      <div className="flex flex-wrap items-center gap-1.5">
        <span className="inline-flex items-center rounded-full border border-slate-200 bg-white px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-700">
          Quick diagnosis
        </span>
        {toText(data?.urgency) ? (
          <span
            className={`inline-flex items-center rounded-full border px-2 py-1 text-[10px] font-semibold uppercase tracking-wide ${getUrgencyClasses(
              data.urgency,
            )}`}
          >
            Urgency: {formatLabel(data.urgency)}
          </span>
        ) : null}
      </div>

      <div className="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
        <CompactSummary
          title="Diagnosis Summary"
          text={diagnosisSummary}
          className="sm:col-span-2 xl:col-span-2"
        />
      </div>

      {possibleCauses.length > 0 ? (
        <ResponseList
          title="Possible Causes"
          items={possibleCauses}
          compact
          columns={possibleCauses.length >= 6 ? 3 : possibleCauses.length >= 4 ? 2 : 1}
        />
      ) : null}

      {recommendedNextSteps.length > 0 ? (
        <ResponseList
          title="Recommended Next Steps"
          items={recommendedNextSteps}
          compact
          columns={
            recommendedNextSteps.length >= 6
              ? 3
              : recommendedNextSteps.length >= 4
                ? 2
                : 1
          }
        />
      ) : null}

      {followUpQuestions.length > 0 ? (
        <ResponseList
          title="Follow-up Questions"
          items={followUpQuestions}
          compact
          columns={
            followUpQuestions.length >= 6 ? 3 : followUpQuestions.length >= 4 ? 2 : 1
          }
        />
      ) : null}
    </div>
  );
}

function ResponseList({ title, items, compact = false, columns = 1 }) {
  if (!items.length) return null;

  const layoutClass =
    columns >= 3
      ? "grid gap-x-4 gap-y-1.5 sm:grid-cols-2 xl:grid-cols-3"
      : columns === 2
        ? "grid gap-x-4 gap-y-1.5 sm:grid-cols-2"
        : "space-y-1.5";

  return (
    <div
      className={`rounded-xl border border-slate-200 bg-white/75 ${
        compact ? "p-2.5" : "p-3"
      }`}
    >
      <p className="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">
        {title}
      </p>
      <ul className={`mt-2 ${layoutClass}`}>
        {items.map((item, index) => (
          <li
            key={`${title}-${index}`}
            className="flex gap-2 text-[12.5px] leading-5 text-slate-800"
          >
            <span className="mt-[8px] h-1.5 w-1.5 flex-shrink-0 rounded-full bg-brand" />
            <span className="break-words">{item}</span>
          </li>
        ))}
      </ul>
    </div>
  );
}

function ModalShell({ children }) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-[2px]">
      {children}
    </div>
  );
}

function YesNoModal({ question, onYes, onNo, disabled }) {
  return (
    <ModalShell>
      <div className="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl">
        <p className="text-xs font-semibold uppercase tracking-wide text-brand">
          Symptom checker
        </p>
        <h4 className="mt-2 text-lg font-bold text-slate-900">
          Do you want to add pet details?
        </h4>
        <p className="mt-2 text-sm leading-relaxed text-slate-600">
          Adding pet details can improve the response quality.
        </p>
        <div className="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
          <p className="max-h-16 overflow-hidden break-words text-sm text-slate-700">
            {question}
          </p>
        </div>
        <div className="mt-5 grid grid-cols-2 gap-3">
          <button
            type="button"
            onClick={onNo}
            disabled={disabled}
            className="rounded-xl border border-slate-200 px-4 py-3 font-semibold text-slate-700 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
          >
            No
          </button>
          <button
            type="button"
            onClick={onYes}
            disabled={disabled}
            className="rounded-xl bg-brand px-4 py-3 font-semibold text-slate-900 transition-colors hover:bg-brand-hover disabled:cursor-not-allowed disabled:opacity-60"
          >
            Yes
          </button>
        </div>
      </div>
    </ModalShell>
  );
}

function PetDetailsModal({
  petForm,
  onChange,
  onBack,
  onSubmit,
  error,
  disabled,
  question,
  speciesOptions,
  sexOptions,
  breedOptions,
  breedLoading,
  breedError,
}) {
  const normalizedSpecies = petForm.species.trim().toLowerCase();
  const showBreedDropdown =
    normalizedSpecies === "dog" || normalizedSpecies === "cat";

  return (
    <ModalShell>
      <div className="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div className="border-b border-slate-200 px-5 py-4">
          <p className="text-xs font-semibold uppercase tracking-wide text-brand">
            Pet details
          </p>
          <h4 className="mt-1 text-lg font-bold text-slate-900">
            Add your pet&apos;s details
          </h4>
          <p className="mt-2 max-h-12 overflow-hidden break-words text-sm text-slate-600">
            {question}
          </p>
        </div>

        <form onSubmit={onSubmit} className="flex-1 overflow-y-auto p-5">
          <div className="grid gap-4 md:grid-cols-2">
            <Field
              label="Owner Name"
              name="owner_name"
              value={petForm.owner_name}
              onChange={onChange}
              placeholder="Rahul"
            />
            <Field
              label="Pet Name"
              name="pet_name"
              value={petForm.pet_name}
              onChange={onChange}
              required
              placeholder="Buddy"
            />
            <SelectField
              label="Species"
              name="species"
              value={petForm.species}
              onChange={onChange}
              options={speciesOptions}
            />
            {showBreedDropdown ? (
              <div>
                <SearchableSelectField
                  label="Breed"
                  name="breed"
                  value={petForm.breed}
                  onChange={onChange}
                  options={breedOptions}
                  disabled={breedLoading}
                  placeholder={
                    breedLoading ? "Loading breeds..." : "Select breed"
                  }
                  searchPlaceholder="Search breed..."
                  emptyMessage="No breeds found"
                />
                {breedError ? (
                  <p className="mt-2 text-xs text-amber-700">{breedError}</p>
                ) : (
                  <p className="mt-2 text-xs text-slate-500">
                    Breed list loads automatically for dogs and cats.
                  </p>
                )}
              </div>
            ) : (
              <Field
                label="Breed"
                name="breed"
                value={petForm.breed}
                onChange={onChange}
                placeholder="Mixed breed / other"
              />
            )}
            <Field
              label="Age"
              name="age"
              value={petForm.age}
              onChange={onChange}
              placeholder="3 years"
            />
            <SelectField
              label="Gender"
              name="sex"
              value={petForm.sex}
              onChange={onChange}
              options={sexOptions}
            />
            <Field
              label="Weight"
              name="weight"
              value={petForm.weight}
              onChange={onChange}
              placeholder="24 kg"
            />
          </div>

          <div className="mt-4">
            <label
              htmlFor="medical_history"
              className="mb-2 block text-sm font-semibold text-slate-700"
            >
              Medical History
            </label>
            <textarea
              id="medical_history"
              name="medical_history"
              value={petForm.medical_history}
              onChange={onChange}
              rows={4}
              placeholder="Any past illnesses, medications, allergies, or recent treatment"
              className="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 transition-colors placeholder:text-slate-400 focus:border-brand focus:outline-none"
            />
          </div>

          {error ? (
            <div className="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {error}
            </div>
          ) : null}

          <div className="mt-5 flex flex-col-reverse gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:justify-end">
            <button
              type="button"
              onClick={onBack}
              disabled={disabled}
              className="rounded-xl border border-slate-200 px-4 py-3 font-semibold text-slate-700 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
            >
              Back
            </button>
            <button
              type="submit"
              disabled={disabled}
              className="inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-4 py-3 font-semibold text-slate-900 transition-colors hover:bg-brand-hover disabled:cursor-not-allowed disabled:opacity-60"
            >
              {disabled ? (
                <>
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Submitting...
                </>
              ) : (
                "Continue"
              )}
            </button>
          </div>
        </form>
      </div>
    </ModalShell>
  );
}

function Field({
  label,
  name,
  value,
  onChange,
  placeholder,
  required = false,
}) {
  return (
    <div>
      <label
        htmlFor={name}
        className="mb-2 block text-sm font-semibold text-slate-700"
      >
        {label}
        {required ? <span className="text-red-500"> *</span> : null}
      </label>
      <input
        id={name}
        name={name}
        value={value}
        onChange={onChange}
        placeholder={placeholder}
        className="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 transition-colors placeholder:text-slate-400 focus:border-brand focus:outline-none"
      />
    </div>
  );
}

function SelectField({
  label,
  name,
  value,
  onChange,
  options,
  required = false,
  disabled = false,
}) {
  return (
    <div>
      <label
        htmlFor={name}
        className="mb-2 block text-sm font-semibold text-slate-700"
      >
        {label}
        {required ? <span className="text-red-500"> *</span> : null}
      </label>
      <select
        id={name}
        name={name}
        value={value}
        onChange={onChange}
        disabled={disabled}
        className="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 transition-colors focus:border-brand focus:outline-none disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500"
      >
        {options.map((option) => (
          <option key={`${name}-${option.value || "empty"}`} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
    </div>
  );
}

function SearchableSelectField({
  label,
  name,
  value,
  onChange,
  options,
  required = false,
  disabled = false,
  placeholder = "Select option",
  searchPlaceholder = "Search...",
  emptyMessage = "No options found",
}) {
  const [isOpen, setIsOpen] = useState(false);
  const [searchValue, setSearchValue] = useState("");
  const wrapperRef = useRef(null);

  const normalizedSearch = searchValue.trim().toLowerCase();
  const selectedOption = options.find((option) => option.value === value);
  const filteredOptions = normalizedSearch
    ? options.filter((option) =>
        option.label.toLowerCase().includes(normalizedSearch),
      )
    : options;

  useEffect(() => {
    if (!isOpen) return undefined;

    const handlePointerDown = (event) => {
      if (
        wrapperRef.current &&
        !wrapperRef.current.contains(event.target)
      ) {
        setIsOpen(false);
        setSearchValue("");
      }
    };

    document.addEventListener("mousedown", handlePointerDown);
    return () => {
      document.removeEventListener("mousedown", handlePointerDown);
    };
  }, [isOpen]);

  useEffect(() => {
    if (disabled) {
      setIsOpen(false);
      setSearchValue("");
    }
  }, [disabled]);

  const handleSelect = (optionValue) => {
    onChange({
      target: {
        name,
        value: optionValue,
      },
    });
    setIsOpen(false);
    setSearchValue("");
  };

  return (
    <div ref={wrapperRef} className="relative">
      <label
        htmlFor={`${name}-search`}
        className="mb-2 block text-sm font-semibold text-slate-700"
      >
        {label}
        {required ? <span className="text-red-500"> *</span> : null}
      </label>

      <button
        type="button"
        onClick={() => {
          if (disabled) return;
          setIsOpen((prev) => !prev);
          setSearchValue("");
        }}
        disabled={disabled}
        className="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm text-slate-900 transition-colors focus:border-brand focus:outline-none disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500"
      >
        <span className={selectedOption ? "text-slate-900" : "text-slate-400"}>
          {selectedOption?.label || placeholder}
        </span>
        <ChevronDown
          className={`h-4 w-4 flex-shrink-0 text-slate-500 transition-transform ${
            isOpen ? "rotate-180" : ""
          }`}
        />
      </button>

      {isOpen ? (
        <div className="absolute z-20 mt-2 w-full rounded-xl border border-slate-200 bg-white shadow-xl">
          <div className="border-b border-slate-100 p-3">
            <div className="relative">
              <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
              <input
                id={`${name}-search`}
                type="text"
                value={searchValue}
                onChange={(event) => setSearchValue(event.target.value)}
                placeholder={searchPlaceholder}
                autoFocus
                className="w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand focus:outline-none"
              />
            </div>
          </div>

          <div className="max-h-56 overflow-y-auto py-2">
            <button
              type="button"
              onClick={() => handleSelect("")}
              className={`block w-full px-4 py-2 text-left text-sm transition-colors hover:bg-slate-50 ${
                value === "" ? "bg-brand/10 font-medium text-brand" : "text-slate-700"
              }`}
            >
              {placeholder}
            </button>

            {filteredOptions.length > 0 ? (
              filteredOptions.map((option) => (
                <button
                  key={`${name}-${option.value}`}
                  type="button"
                  onClick={() => handleSelect(option.value)}
                  className={`block w-full px-4 py-2 text-left text-sm transition-colors hover:bg-slate-50 ${
                    value === option.value
                      ? "bg-brand/10 font-medium text-brand"
                      : "text-slate-700"
                  }`}
                >
                  {option.label}
                </button>
              ))
            ) : (
              <p className="px-4 py-3 text-sm text-slate-500">{emptyMessage}</p>
            )}
          </div>
        </div>
      ) : null}
    </div>
  );
}

export function SymptomCheckerChat() {
  const [messages, setMessages] = useState([]);
  const [inputValue, setInputValue] = useState("");
  const [pendingQuestion, setPendingQuestion] = useState("");
  const [yesNoModalOpen, setYesNoModalOpen] = useState(false);
  const [petDetailsModalOpen, setPetDetailsModalOpen] = useState(false);
  const [petForm, setPetForm] = useState(EMPTY_PET_FORM);
  const [loading, setLoading] = useState(false);
  const [requestType, setRequestType] = useState("");
  const [error, setError] = useState("");
  const [petFormError, setPetFormError] = useState("");
  const [dogBreedOptions, setDogBreedOptions] = useState([]);
  const [catBreedOptions, setCatBreedOptions] = useState([]);
  const [breedLoading, setBreedLoading] = useState(false);
  const [breedError, setBreedError] = useState("");
  const messagesEndRef = useRef(null);
  const messageIdRef = useRef(0);
  const normalizedSpecies = petForm.species.trim().toLowerCase();
  const breedOptions =
    normalizedSpecies === "dog"
      ? dogBreedOptions
      : normalizedSpecies === "cat"
        ? catBreedOptions
        : [];

  const isInteractionLocked =
    loading || yesNoModalOpen || petDetailsModalOpen || Boolean(pendingQuestion);

  const pushMessage = (message) => {
    setMessages((prev) => [
      ...prev,
      { id: `message-${messageIdRef.current++}`, ...message },
    ]);
  };

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages, loading]);

  useEffect(() => {
    if (!yesNoModalOpen && !petDetailsModalOpen) return undefined;
    if (typeof document === "undefined") return undefined;

    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";

    return () => {
      document.body.style.overflow = previousOverflow;
    };
  }, [yesNoModalOpen, petDetailsModalOpen]);

  useEffect(() => {
    let active = true;

    const loadBreeds = async () => {
      if (!petDetailsModalOpen) return;

      if (normalizedSpecies === "dog") {
        if (dogBreedOptions.length > 0) {
          setBreedError("");
          setBreedLoading(false);
          return;
        }

        setBreedLoading(true);
        const result = await fetchDogBreedOptions();
        if (!active) return;
        setDogBreedOptions(result.options);
        setBreedError(result.error);
        setBreedLoading(false);
        return;
      }

      if (normalizedSpecies === "cat") {
        if (catBreedOptions.length > 0) {
          setBreedError("");
          setBreedLoading(false);
          return;
        }

        setBreedLoading(true);
        const result = await fetchCatBreedOptions();
        if (!active) return;
        setCatBreedOptions(result.options);
        setBreedError(result.error);
        setBreedLoading(false);
        return;
      }

      setBreedError("");
      setBreedLoading(false);
    };

    void loadBreeds();

    return () => {
      active = false;
    };
  }, [
    catBreedOptions.length,
    dogBreedOptions.length,
    normalizedSpecies,
    petDetailsModalOpen,
  ]);

  const resetPendingFlow = () => {
    setPendingQuestion("");
    setYesNoModalOpen(false);
    setPetDetailsModalOpen(false);
    setRequestType("");
    setLoading(false);
  };

  const pushAssistantError = (message) => {
    pushMessage({
      role: "assistant",
      variant: "error",
      text:
        message ||
        "I am having trouble connecting right now. Please try again in a moment.",
    });
  };

  const handleSubmit = (event) => {
    event.preventDefault();

    const question = inputValue.trim();
    if (!question || isInteractionLocked) return;

    setError("");
    setPetFormError("");
    pushMessage({ role: "user", text: question });
    setInputValue("");
    setPendingQuestion(question);
    setYesNoModalOpen(true);
  };

  const handleYesChoice = () => {
    if (!pendingQuestion || loading) return;
    setYesNoModalOpen(false);
    setPetFormError("");
    setPetDetailsModalOpen(true);
  };

  const runNoFlow = async (question) => {
    setError("");
    setRequestType("no");
    setLoading(true);

    try {
      const response = await apiPost("/api/symptom-diagnosis", { question });
      pushMessage({
        role: "assistant",
        variant: "no",
        data: response?.data?.diagnosis ?? {},
      });
    } catch (requestError) {
      const message =
        requestError?.message ||
        "Unable to fetch a concise diagnosis right now. Please try again.";
      setError(message);
      pushAssistantError(message);
    } finally {
      resetPendingFlow();
    }
  };

  const handleNoChoice = () => {
    if (!pendingQuestion || loading) return;
    setYesNoModalOpen(false);
    void runNoFlow(pendingQuestion);
  };

  const handlePetFormChange = (event) => {
    const { name, value } = event.target;
    setPetForm((prev) => {
      if (name === "species") {
        return {
          ...prev,
          species: value,
          breed: prev.species === value ? prev.breed : "",
        };
      }

      return { ...prev, [name]: value };
    });
    if (petFormError) setPetFormError("");
  };

  const handlePetFormBack = () => {
    if (loading) return;
    setPetDetailsModalOpen(false);
    setPetFormError("");
    setYesNoModalOpen(true);
  };

  const runYesFlow = async (question, formValues) => {
    setError("");
    setRequestType("yes");
    setLoading(true);

    try {
      const response = await apiPost("/api/rag-snoutic-symptom-checker/page-data", {
        question,
        owner_name: formValues.owner_name.trim(),
        pet_name: formValues.pet_name.trim(),
        species: formValues.species.trim(),
        breed: formValues.breed.trim(),
        age: formValues.age.trim(),
        sex: formValues.sex.trim(),
        weight: formValues.weight.trim(),
        medical_history: formValues.medical_history.trim(),
      });

      pushMessage({
        role: "assistant",
        variant: "yes",
        data: response?.data?.symptom_data ?? {},
      });
    } catch (requestError) {
      const message =
        requestError?.message ||
        "Unable to fetch symptom details right now. Please try again.";
      setError(message);
      pushAssistantError(message);
    } finally {
      resetPendingFlow();
    }
  };

  const handlePetDetailsSubmit = (event) => {
    event.preventDefault();
    if (loading || !pendingQuestion) return;

    if (!petForm.pet_name.trim()) {
      setPetFormError("Pet name is required before continuing with pet details.");
      return;
    }

    setPetDetailsModalOpen(false);
    void runYesFlow(pendingQuestion, petForm);
  };

  return (
    <>
      <div className="flex h-full min-h-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-2xl">
        <div className="flex items-center justify-between border-b border-brand/20 bg-brand/10 px-3.5 py-2.5">
          <div className="flex items-center gap-3">
            <div className="rounded-full bg-brand p-1.5">
              <SnoutIQIcon className="h-4.5 w-4.5" alt="SnoutIQ" />
            </div>
            <div>
              <h3 className="text-[15px] font-bold leading-5 text-slate-900">
                Snoutiq AI Symptom Checker
              </h3>
              <p className="flex items-center gap-1 text-[11px] text-brand">
                <AlertTriangle className="h-3 w-3" />
                Triage only. Not a diagnosis.
              </p>
            </div>
          </div>
        </div>

        <div className="flex-1 min-h-0 space-y-2.5 overflow-y-auto overscroll-contain p-2.5">
          {messages.length === 0 ? (
            <div className="mt-4 text-center text-slate-600">
              <SnoutIQIcon
                className="mx-auto mb-2.5 h-9 w-9 opacity-80"
                alt="SnoutIQ"
              />
              <p className="mb-1 text-[15px] text-slate-900">
                Describe your pet&apos;s symptoms below.
              </p>
              <p className="mx-auto max-w-md text-[11px] leading-5">
                Example: &quot;My 3-year-old Golden Retriever has been vomiting
                yellow foam since morning.&quot;
              </p>
            </div>
          ) : null}

          {messages.map((message) => (
            <MessageBubble key={message.id} message={message} />
          ))}

          {loading ? (
            <div className="flex gap-2.5 justify-start">
              <div className="mt-1 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-brand/20">
                <SnoutIQIcon className="h-4.5 w-4.5" alt="SnoutIQ assistant" />
              </div>
              <div className="flex items-center gap-2 rounded-2xl rounded-tl-none border border-slate-200 bg-slate-100 px-3 py-2 text-slate-800">
                <Loader2 className="h-3.5 w-3.5 animate-spin text-brand" />
                <span className="text-[12.5px]">
                  {requestType === "yes"
                    ? "Reviewing symptoms with pet details..."
                    : "Preparing concise diagnosis..."}
                </span>
              </div>
            </div>
          ) : null}

          <div ref={messagesEndRef} />
        </div>

        <div className="shrink-0 border-t border-slate-200 bg-white p-2.5">
          <form onSubmit={handleSubmit} className="flex gap-2">
            <input
              type="text"
              value={inputValue}
              onChange={(event) => setInputValue(event.target.value)}
              placeholder="Describe the symptoms..."
              disabled={isInteractionLocked}
              className="flex-1 rounded-xl border border-slate-200 bg-slate-100 px-3 py-2.5 text-[13px] text-slate-900 transition-colors focus:border-brand focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
            />
            <button
              type="submit"
              disabled={isInteractionLocked || !inputValue.trim()}
              className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand text-slate-900 transition-colors hover:bg-brand-hover disabled:cursor-not-allowed disabled:opacity-50"
            >
              <Send className="h-4 w-4" />
            </button>
          </form>
          {error ? (
            <p className="sr-only" aria-live="polite">
              {error}
            </p>
          ) : null}
        </div>
      </div>

      {yesNoModalOpen ? (
        <YesNoModal
          question={pendingQuestion}
          onYes={handleYesChoice}
          onNo={handleNoChoice}
          disabled={loading}
        />
      ) : null}

      {petDetailsModalOpen ? (
        <PetDetailsModal
          petForm={petForm}
          onChange={handlePetFormChange}
          onBack={handlePetFormBack}
          onSubmit={handlePetDetailsSubmit}
          error={petFormError}
          disabled={loading}
          question={pendingQuestion}
          speciesOptions={SPECIES_OPTIONS}
          sexOptions={SEX_OPTIONS}
          breedOptions={breedOptions}
          breedLoading={breedLoading}
          breedError={breedError}
        />
      ) : null}
    </>
  );
}
