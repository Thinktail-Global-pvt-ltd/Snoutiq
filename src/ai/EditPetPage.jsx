import React, { useEffect, useMemo, useState } from "react";
import axios from "axios";
import { useLocation, useNavigate } from "react-router-dom";
import { updateAiUserData } from "./AiAuth";

const API_BASE_URL = "https://snoutiq.com/backend/api";

const styles = {
  page: {
    minHeight: "100vh",
    background: "linear-gradient(135deg, #e8f5e9 0%, #f3e5f5 100%)",
    padding: "24px 16px",
    boxSizing: "border-box",
  },
  wrap: {
    maxWidth: 760,
    margin: "0 auto",
  },
  headerBar: {
    display: "flex",
    alignItems: "center",
    gap: 12,
    marginBottom: 16,
  },
  backButton: {
    border: "1px solid #d0d7de",
    background: "#fff",
    borderRadius: 12,
    padding: "10px 14px",
    cursor: "pointer",
    fontWeight: 600,
  },
  heading: {
    fontSize: 28,
    fontWeight: 800,
    color: "#1f2937",
    margin: 0,
  },
  subheading: {
    fontSize: 14,
    color: "#6b7280",
    marginTop: 6,
    marginBottom: 0,
  },
  card: {
    background: "#fff",
    borderRadius: 20,
    boxShadow: "0 10px 30px rgba(0,0,0,0.08)",
    padding: 24,
  },
  sectionTitle: {
    fontSize: 12,
    fontWeight: 700,
    color: "#6b7280",
    textTransform: "uppercase",
    letterSpacing: "0.08em",
    margin: "0 0 14px",
  },
  row: {
    display: "grid",
    gridTemplateColumns: "repeat(auto-fit, minmax(220px, 1fr))",
    gap: 14,
  },
  field: {
    display: "flex",
    flexDirection: "column",
    marginBottom: 14,
  },
  label: {
    fontSize: 13,
    fontWeight: 600,
    color: "#374151",
    marginBottom: 6,
  },
  input: {
    width: "100%",
    boxSizing: "border-box",
    border: "1.5px solid #e5e7eb",
    borderRadius: 12,
    padding: "12px 14px",
    fontSize: 15,
    background: "#fafafa",
    outline: "none",
  },
  select: {
    width: "100%",
    boxSizing: "border-box",
    border: "1.5px solid #e5e7eb",
    borderRadius: 12,
    padding: "12px 14px",
    fontSize: 15,
    background: "#fafafa",
    outline: "none",
  },
  chipRow: {
    display: "flex",
    flexWrap: "wrap",
    gap: 10,
    marginBottom: 12,
  },
  chip: {
    border: "1px solid #d1d5db",
    borderRadius: 999,
    padding: "10px 14px",
    background: "#fff",
    cursor: "pointer",
    fontSize: 14,
    fontWeight: 600,
    color: "#374151",
  },
  chipActive: {
    border: "1px solid #2e7d32",
    background: "#ecfdf3",
    color: "#166534",
  },
  imageBox: {
    border: "1px dashed #cbd5e1",
    borderRadius: 16,
    padding: 16,
    background: "#f8fafc",
  },
  imagePreview: {
    width: 120,
    height: 120,
    borderRadius: 16,
    objectFit: "cover",
    border: "1px solid #e5e7eb",
    background: "#fff",
    display: "block",
    marginBottom: 12,
  },
  imagePlaceholder: {
    width: 120,
    height: 120,
    borderRadius: 16,
    border: "1px dashed #d1d5db",
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    color: "#6b7280",
    fontSize: 13,
    background: "#fff",
    marginBottom: 12,
  },
  helper: {
    marginTop: 6,
    fontSize: 12,
    color: "#6b7280",
    lineHeight: 1.5,
  },
  error: {
    background: "#fef2f2",
    border: "1px solid #fecaca",
    color: "#b91c1c",
    borderRadius: 12,
    padding: "12px 14px",
    marginBottom: 16,
    fontSize: 14,
  },
  success: {
    background: "#ecfdf3",
    border: "1px solid #a7f3d0",
    color: "#047857",
    borderRadius: 12,
    padding: "12px 14px",
    marginBottom: 16,
    fontSize: 14,
  },
  footer: {
    display: "flex",
    gap: 12,
    justifyContent: "flex-end",
    marginTop: 18,
    flexWrap: "wrap",
  },
  secondaryBtn: {
    border: "1px solid #d1d5db",
    background: "#fff",
    color: "#374151",
    borderRadius: 12,
    padding: "12px 18px",
    cursor: "pointer",
    fontSize: 15,
    fontWeight: 700,
  },
  primaryBtn: {
    border: "none",
    background: "#2e7d32",
    color: "#fff",
    borderRadius: 12,
    padding: "12px 18px",
    cursor: "pointer",
    fontSize: 15,
    fontWeight: 700,
    minWidth: 180,
  },
};

const normalizeGenderValue = (value) => {
  const normalized = String(value || "").trim().toLowerCase();
  if (normalized === "male" || normalized === "female") return normalized;
  return "";
};

const normalizeYesNoValue = (value, fallback = "") => {
  if (value === null || value === undefined || value === "") return fallback;
  if (value === true || value === 1) return "1";
  if (value === false || value === 0) return "0";
  const normalized = String(value).trim().toLowerCase();
  if (["1", "true", "yes", "y"].includes(normalized)) return "1";
  if (["0", "false", "no", "n"].includes(normalized)) return "0";
  return fallback;
};

const normalizePetTypeValue = (value) => {
  const normalized = String(value || "").trim().toLowerCase();
  if (normalized.includes("dog")) return "dog";
  if (normalized.includes("cat")) return "cat";
  if (normalized.includes("exotic")) return "exotic";
  return "";
};

const formatDateToIso = (date) => {
  if (!(date instanceof Date) || Number.isNaN(date.getTime())) return "";
  const year = date.getFullYear();
  const month = `${date.getMonth() + 1}`.padStart(2, "0");
  const day = `${date.getDate()}`.padStart(2, "0");
  return `${year}-${month}-${day}`;
};

const parseDobInput = (value) => {
  const raw = String(value || "").trim();
  if (!raw) return null;
  const date = new Date(`${raw}T00:00:00`);
  if (Number.isNaN(date.getTime())) return null;
  return date;
};

const deriveAgeMonthsFromDob = (dobDate) => {
  if (!(dobDate instanceof Date) || Number.isNaN(dobDate.getTime())) return null;
  const now = new Date();
  if (dobDate > now) return null;

  let months =
    (now.getFullYear() - dobDate.getFullYear()) * 12 +
    (now.getMonth() - dobDate.getMonth());

  if (now.getDate() < dobDate.getDate()) {
    months -= 1;
  }

  return Math.max(months, 0);
};

const normalizeNumberInput = (value) => {
  if (value === null || value === undefined) return null;
  const trimmed = String(value).trim();
  if (!trimmed) return null;
  const numeric = Number(trimmed);
  return Number.isFinite(numeric) ? numeric : null;
};

const buildPetImageUrl = (path) => {
  if (!path) return "";
  const trimmed = String(path).trim();
  if (!trimmed) return "";
  if (/^https?:\/\//i.test(trimmed) || /^blob:/i.test(trimmed) || /^data:/i.test(trimmed)) {
    return trimmed;
  }
  if (trimmed.startsWith("/")) {
    return `https://snoutiq.com${trimmed}`;
  }
  return `https://snoutiq.com/${trimmed.replace(/^\/+/, "")}`;
};

const resolvePetImageValue = (...sources) => {
  for (const source of sources) {
    if (!source || typeof source !== "object") continue;

    const resolved =
      source?.pet_doc1 ||
      source?.petDoc1 ||
      source?.pet_image_url ||
      source?.petImageUrl ||
      source?.avatar ||
      source?.photo ||
      source?.image ||
      source?.image_url ||
      source?.imageUrl ||
      source?.profile_image ||
      source?.profileImage ||
      source?.pet_photo ||
      source?.petPhoto ||
      "";

    if (String(resolved).trim()) {
      return resolved;
    }
  }

  return "";
};

const getPetFromApiPayload = (payload) => {
  if (!payload || typeof payload !== "object") return null;

  return (
    payload?.data?.pet ||
    payload?.pet ||
    (payload?.data && typeof payload.data === "object" ? payload.data : null)
  );
};

const getStoredUser = () => {
  try {
    const raw = localStorage.getItem("user_data");
    return raw ? JSON.parse(raw) : {};
  } catch {
    return {};
  }
};

const getPrimaryPetFromUser = (user) => {
  if (user?.pet && typeof user.pet === "object") return user.pet;
  if (Array.isArray(user?.pets) && user.pets.length > 0) return user.pets[0];
  return null;
};

const buildPetIdentityKey = (pet) => {
  const normalizedPetId = String(pet?.id ?? pet?.pet_id ?? "").trim();
  if (normalizedPetId) return `id:${normalizedPetId}`;

  const name = String(pet?.name ?? pet?.pet_name ?? "").trim().toLowerCase();
  const type = String(pet?.pet_type ?? pet?.species ?? pet?.type ?? "")
    .trim()
    .toLowerCase();
  const dob = String(pet?.pet_dob ?? pet?.dob ?? "").trim();

  if (!name) return "";
  return `fallback:${name}::${type}::${dob}`;
};

const mergeEditedPetIntoPets = (existingPets, updatedPet) => {
  const pets = Array.isArray(existingPets) ? existingPets : [];
  if (!updatedPet) return pets;
  if (!pets.length) return [updatedPet];

  const updatedPetKey = buildPetIdentityKey(updatedPet);
  let matched = false;

  const nextPets = pets.map((pet) => {
    if (!updatedPetKey || buildPetIdentityKey(pet) !== updatedPetKey) {
      return pet;
    }

    matched = true;
    return {
      ...pet,
      ...updatedPet,
    };
  });

  return matched ? nextPets : [...nextPets, updatedPet];
};

export default function EditPetPage() {
  const navigate = useNavigate();
  const location = useLocation();

  const authToken = localStorage.getItem("auth_token") || "";
  const storedUser = useMemo(() => getStoredUser(), []);
  const routePet = location.state?.pet || null;
  const fallbackPet = getPrimaryPetFromUser(storedUser);
  const selectedPet = routePet || fallbackPet || null;

  const [form, setForm] = useState(() => ({
    petOwnerName:
      selectedPet?.pet_owner_name ||
      selectedPet?.owner_name ||
      storedUser?.pet_owner_name ||
      storedUser?.owner_name ||
      storedUser?.name ||
      "",
    name: selectedPet?.name || selectedPet?.pet_name || storedUser?.pet_name || "",
    breed: selectedPet?.breed || storedUser?.breed || "",
    exoticType:
      normalizePetTypeValue(selectedPet?.pet_type || selectedPet?.petType) === "exotic"
        ? selectedPet?.exoticType || selectedPet?.exotic_type || selectedPet?.breed || ""
        : "",
    petDob: selectedPet?.pet_dob || selectedPet?.petDob || storedUser?.pet_dob || "",
    gender: normalizeGenderValue(
      selectedPet?.pet_gender || selectedPet?.gender || storedUser?.pet_gender || ""
    ),
    weight:
      selectedPet?.weight ??
      selectedPet?.pet_weight ??
      storedUser?.weight ??
      storedUser?.pet_weight ??
      "",
    petType: normalizePetTypeValue(
      selectedPet?.pet_type ||
        selectedPet?.petType ||
        selectedPet?.type ||
        storedUser?.pet_type ||
        "dog"
    ),
    isNuetered: normalizeYesNoValue(
      selectedPet?.is_nuetered ?? selectedPet?.is_neutered ?? storedUser?.is_nuetered,
      ""
    ),
    dewormingYesNo: normalizeYesNoValue(
      selectedPet?.deworming_yes_no ?? storedUser?.deworming_yes_no,
      ""
    ),
    lastDewormingDate:
      selectedPet?.last_deworming_date || storedUser?.last_deworming_date || "",
  }));

  const [petPhotoPreview, setPetPhotoPreview] = useState(
    buildPetImageUrl(resolvePetImageValue(selectedPet, storedUser?.pet, storedUser))
  );
  const [petPhotoFile, setPetPhotoFile] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  const petId = selectedPet?.id ?? selectedPet?.pet_id ?? storedUser?.pet_id ?? null;
  const userId = selectedPet?.user_id ?? storedUser?.user_id ?? storedUser?.id ?? null;

  const agePreview = useMemo(() => {
    const date = parseDobInput(form.petDob);
    if (!date) return "";
    const months = deriveAgeMonthsFromDob(date);
    if (months == null) return "";
    const years = Math.floor(months / 12);
    const remMonths = months % 12;

    if (years > 0 && remMonths > 0) {
      return `Calculated age: ${years} year(s) ${remMonths} month(s)`;
    }
    if (years > 0) return `Calculated age: ${years} year(s)`;
    return `Calculated age: ${remMonths} month(s)`;
  }, [form.petDob]);

  const setField = (key, value) => {
    setForm((prev) => ({
      ...prev,
      [key]: value,
    }));
  };

  const handleImageChange = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    setPetPhotoFile(file);
    const previewUrl = URL.createObjectURL(file);
    setPetPhotoPreview(previewUrl);
  };

  const handleSave = async () => {
    setError("");
    setSuccess("");

    if (!petId) {
      setError("Pet ID not found.");
      return;
    }

    const ownerNameValue = String(form.petOwnerName || "").trim();
    const nameValue = String(form.name || "").trim();
    const petTypeValue = normalizePetTypeValue(form.petType);
    const breedValue =
      petTypeValue === "exotic"
        ? String(form.exoticType || "").trim()
        : String(form.breed || "").trim();
    const genderValue = normalizeGenderValue(form.gender);
    const dobDate = parseDobInput(form.petDob);
    const dobIso = dobDate ? formatDateToIso(dobDate) : "";
    const ageMonthsValue = dobDate ? deriveAgeMonthsFromDob(dobDate) : null;
    const weightValue = normalizeNumberInput(form.weight);
    const neuteredValue = normalizeYesNoValue(form.isNuetered, "");
    const dewormingValue = normalizeYesNoValue(form.dewormingYesNo, "");
    const lastDewormingDateValue = String(form.lastDewormingDate || "").trim();

    if (!nameValue) {
      setError("Pet name is required.");
      return;
    }
    if (!petTypeValue) {
      setError("Pet type is required.");
      return;
    }
    if (!breedValue) {
      setError("Breed is required.");
      return;
    }
    if (!genderValue) {
      setError("Gender is required.");
      return;
    }
    if (!dobIso) {
      setError("Date of birth is required.");
      return;
    }
    if (ageMonthsValue === null) {
      setError("Date of birth must be in the past.");
      return;
    }
    if (neuteredValue === "") {
      setError("Neutering status is required.");
      return;
    }
    if (dewormingValue === "") {
      setError("Deworming status is required.");
      return;
    }
    if (dewormingValue === "1" && !lastDewormingDateValue) {
      setError("Last deworming date is required.");
      return;
    }

    setLoading(true);

    try {
      const payload = new FormData();
      payload.append("_method", "PUT");

      if (userId != null) {
        payload.append("user_id", String(userId));
      }
      if (ownerNameValue) {
        payload.append("pet_owner_name", ownerNameValue);
      }

      payload.append("pet_name", nameValue);
      payload.append("name", nameValue);
      payload.append("breed", breedValue);
      payload.append("pet_type", petTypeValue);
      payload.append("role", "pet");
      payload.append("pet_dob", dobIso);
      payload.append("pet_age", String(ageMonthsValue));
      payload.append("pet_gender", genderValue);

      if (neuteredValue !== "") {
        payload.append("is_nuetered", neuteredValue);
      }

      if (dewormingValue !== "") {
        payload.append("deworming_yes_no", dewormingValue);
        payload.append(
          "last_deworming_date",
          dewormingValue === "0" ? "" : lastDewormingDateValue || ""
        );
      }

      if (weightValue !== null) {
        payload.append("pet_weight", String(weightValue));
        payload.append("weight", String(weightValue));
      }

      if (petPhotoFile) {
        payload.append("pet_doc1", petPhotoFile);
      }

      for (const [key, value] of payload.entries()) {
        console.log("FORMDATA", key, value);
      }

      const response = await axios.post(
        `${API_BASE_URL}/pets/${petId}`,
        payload,
        {
          headers: {
            Authorization: `Bearer ${authToken}`,
            Accept: "application/json",
          },
        }
      );

      const responseData = response?.data || {};
      console.log("UPLOAD RESPONSE", responseData);

      let freshPet = null;
      try {
        const freshResponse = await axios.get(`${API_BASE_URL}/pets/${petId}`, {
          headers: {
            Authorization: `Bearer ${authToken}`,
            Accept: "application/json",
          },
        });
        const freshData = freshResponse?.data || {};
        freshPet = getPetFromApiPayload(freshData);
        console.log("FRESH PET RESPONSE", freshData);
      } catch (fetchError) {
        console.warn(
          "FRESH PET FETCH FAILED",
          fetchError?.response?.data || fetchError?.message || fetchError,
        );
      }

      const responsePet = freshPet || getPetFromApiPayload(responseData);
      const savedImagePath = resolvePetImageValue(responsePet);
      const savedImageUrl = buildPetImageUrl(savedImagePath);
      const existingImagePath = resolvePetImageValue(
        selectedPet,
        storedUser?.pet,
        storedUser,
      );
      const persistedImagePath = savedImagePath || existingImagePath;
      const resolvedAvatarUrl =
        buildPetImageUrl(
          responsePet?.pet_doc1 ||
            responsePet?.petDoc1 ||
            persistedImagePath,
        ) ||
        responsePet?.pet_image_url ||
        responsePet?.petImageUrl ||
        "";

      const updatedPet = {
        ...(selectedPet || {}),
        ...(responsePet || {}),
        id:
          responsePet?.id ??
          selectedPet?.id ??
          selectedPet?.pet_id ??
          petId,
        pet_id:
          responsePet?.id ??
          selectedPet?.pet_id ??
          selectedPet?.id ??
          petId,
        user_id: userId,
        pet_owner_name: ownerNameValue,
        name: nameValue,
        pet_name: nameValue,
        breed: breedValue,
        pet_type: petTypeValue,
        pet_dob: dobIso,
        pet_gender: genderValue === "male" ? "Male" : "Female",
        gender: genderValue === "male" ? "Male" : "Female",
        pet_age: String(ageMonthsValue),
        weight: weightValue != null ? String(weightValue) : "",
        pet_weight: weightValue != null ? String(weightValue) : "",
        is_nuetered: neuteredValue === "1",
        is_neutered: neuteredValue === "1",
        deworming_yes_no: dewormingValue === "1",
        last_deworming_date: dewormingValue === "1" ? lastDewormingDateValue : "",
        pet_doc1: persistedImagePath,
        pet_image_url:
          responsePet?.pet_image_url || responsePet?.petImageUrl || resolvedAvatarUrl,
        avatar: resolvedAvatarUrl,
        image: resolvedAvatarUrl,
        image_url: resolvedAvatarUrl,
        profile_image: resolvedAvatarUrl,
      };

      const latestStoredUser = getStoredUser();
      const normalizedUpdatedPet = {
        ...updatedPet,
        id: updatedPet?.id ?? updatedPet?.pet_id ?? petId,
        pet_id: updatedPet?.pet_id ?? updatedPet?.id ?? petId,
        pet_doc1: persistedImagePath || "",
        pet_image_url:
          responsePet?.pet_image_url || responsePet?.petImageUrl || resolvedAvatarUrl,
        avatar: resolvedAvatarUrl || "",
      };
      const nextPets = mergeEditedPetIntoPets(latestStoredUser?.pets, normalizedUpdatedPet);

      updateAiUserData({
        name: ownerNameValue || latestStoredUser?.name || "",
        owner_name:
          ownerNameValue || latestStoredUser?.owner_name || latestStoredUser?.name || "",
        pet_owner_name:
          ownerNameValue ||
          latestStoredUser?.pet_owner_name ||
          latestStoredUser?.owner_name ||
          latestStoredUser?.name ||
          "",
        pet_id: normalizedUpdatedPet.pet_id,
        pet_name:
          normalizedUpdatedPet.name || normalizedUpdatedPet.pet_name || "",
        breed: normalizedUpdatedPet.breed || "",
        pet_gender:
          normalizedUpdatedPet.pet_gender || normalizedUpdatedPet.gender || "",
        pet_type: normalizedUpdatedPet.pet_type || "",
        pet_dob: normalizedUpdatedPet.pet_dob || "",
        pet_age: normalizedUpdatedPet.pet_age || "",
        pet_doc1: normalizedUpdatedPet.pet_doc1 || "",
        pet_image_url:
          normalizedUpdatedPet.pet_image_url ||
          buildPetImageUrl(normalizedUpdatedPet.pet_doc1 || "") ||
          "",
        avatar:
          normalizedUpdatedPet.avatar ||
          buildPetImageUrl(normalizedUpdatedPet.pet_doc1 || "") ||
          "",
        image:
          normalizedUpdatedPet.image ||
          buildPetImageUrl(normalizedUpdatedPet.pet_doc1 || "") ||
          "",
        image_url:
          normalizedUpdatedPet.image_url ||
          buildPetImageUrl(normalizedUpdatedPet.pet_doc1 || "") ||
          "",
        profile_image:
          normalizedUpdatedPet.profile_image ||
          buildPetImageUrl(normalizedUpdatedPet.pet_doc1 || "") ||
          "",
        pet_weight:
          normalizedUpdatedPet.pet_weight || normalizedUpdatedPet.weight || "",
        weight:
          normalizedUpdatedPet.weight || normalizedUpdatedPet.pet_weight || "",
        is_nuetered: normalizedUpdatedPet.is_nuetered,
        deworming_yes_no: normalizedUpdatedPet.deworming_yes_no,
        last_deworming_date: normalizedUpdatedPet.last_deworming_date || "",
        pet: normalizedUpdatedPet,
        pets: nextPets,
      });

      if (savedImageUrl) {
        setPetPhotoPreview(savedImageUrl);
      }

      setPetPhotoFile(null);
      setSuccess(responseData?.message || "Pet updated successfully.");
    } catch (err) {
      setError(
        err?.response?.data?.message ||
          err?.response?.data?.error ||
          err?.message ||
          "Failed to update pet."
      );
    } finally {
      setLoading(false);
    }
  };

  if (!selectedPet) {
    return (
      <div style={styles.page}>
        <div style={styles.wrap}>
          <div style={styles.card}>
            <h1 style={styles.heading}>Edit Pet</h1>
            <p style={styles.subheading}>No pet data found to edit.</p>
            <div style={styles.footer}>
              <button style={styles.secondaryBtn} onClick={() => navigate(-1)}>
                Go Back
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div style={styles.page}>
      <div style={styles.wrap}>
        <div style={styles.headerBar}>
          <button style={styles.backButton} onClick={() => navigate(-1)}>
            ← Back
          </button>
          <div>
            <h1 style={styles.heading}>Edit Pet</h1>
            <p style={styles.subheading}>
              Update your pet details using the existing edit pet API.
            </p>
          </div>
        </div>

        <div style={styles.card}>
        
          <p style={styles.sectionTitle}>Pet Photo</p>
          <div style={styles.imageBox}>
            {petPhotoPreview ? (
              <img src={petPhotoPreview} alt="Pet" style={styles.imagePreview} />
            ) : (
              <div style={styles.imagePlaceholder}>No photo</div>
            )}

            <input type="file" accept="image/*" onChange={handleImageChange} />
            <div style={styles.helper}>
              Optional. If you choose a new image, it will be sent as <b>pet_doc1</b>.
            </div>
          </div>

          <p style={{ ...styles.sectionTitle, marginTop: 22 }}>About Your Pet</p>

          <div style={styles.row}>
            <div style={styles.field}>
              <label style={styles.label}>Pet Name *</label>
              <input
                style={styles.input}
                value={form.name}
                onChange={(e) => setField("name", e.target.value)}
                placeholder="e.g. Bruno"
              />
            </div>

            <div style={styles.field}>
              <label style={styles.label}>Pet Owner Name</label>
              <input
                style={styles.input}
                value={form.petOwnerName}
                onChange={(e) => setField("petOwnerName", e.target.value)}
                placeholder="Owner name"
              />
            </div>
          </div>

          <div style={styles.field}>
            <label style={styles.label}>Pet Type *</label>
            <div style={styles.chipRow}>
              {["dog", "cat", "exotic"].map((type) => {
                const isActive = form.petType === type;
                return (
                  <button
                    key={type}
                    type="button"
                    style={{
                      ...styles.chip,
                      ...(isActive ? styles.chipActive : {}),
                    }}
                    onClick={() => setField("petType", type)}
                  >
                    {type.charAt(0).toUpperCase() + type.slice(1)}
                  </button>
                );
              })}
            </div>
          </div>

          <div style={styles.row}>
            {form.petType === "exotic" ? (
              <div style={styles.field}>
                <label style={styles.label}>Exotic Type / Breed *</label>
                <input
                  style={styles.input}
                  value={form.exoticType}
                  onChange={(e) => setField("exoticType", e.target.value)}
                  placeholder="e.g. Rabbit, Parrot"
                />
              </div>
            ) : (
              <div style={styles.field}>
                <label style={styles.label}>Breed *</label>
                <input
                  style={styles.input}
                  value={form.breed}
                  onChange={(e) => setField("breed", e.target.value)}
                  placeholder="e.g. Labrador"
                />
              </div>
            )}

            <div style={styles.field}>
              <label style={styles.label}>Weight</label>
              <input
                style={styles.input}
                value={form.weight}
                onChange={(e) => setField("weight", e.target.value)}
                placeholder="e.g. 12.5"
              />
            </div>
          </div>

          <div style={styles.row}>
            <div style={styles.field}>
              <label style={styles.label}>Date of Birth *</label>
              <input
                style={styles.input}
                type="date"
                value={form.petDob}
                onChange={(e) => setField("petDob", e.target.value)}
                max={new Date().toISOString().split("T")[0]}
              />
              {agePreview ? <div style={styles.helper}>{agePreview}</div> : null}
            </div>

            <div style={styles.field}>
              <label style={styles.label}>Gender *</label>
              <select
                style={styles.select}
                value={form.gender}
                onChange={(e) => setField("gender", e.target.value)}
              >
                <option value="">Select gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
            </div>
          </div>

          <div style={styles.row}>
            <div style={styles.field}>
              <label style={styles.label}>Neutered / Spayed *</label>
              <select
                style={styles.select}
                value={form.isNuetered}
                onChange={(e) => setField("isNuetered", e.target.value)}
              >
                <option value="">Select</option>
                <option value="1">Yes</option>
                <option value="0">No</option>
              </select>
            </div>

            <div style={styles.field}>
              <label style={styles.label}>Deworming Done? *</label>
              <select
                style={styles.select}
                value={form.dewormingYesNo}
                onChange={(e) => setField("dewormingYesNo", e.target.value)}
              >
                <option value="">Select</option>
                <option value="1">Yes</option>
                <option value="0">No</option>
              </select>
            </div>
          </div>

          {form.dewormingYesNo === "1" ? (
            <div style={styles.field}>
              <label style={styles.label}>Last Deworming Date *</label>
              <input
                style={styles.input}
                type="date"
                value={form.lastDewormingDate}
                onChange={(e) => setField("lastDewormingDate", e.target.value)}
                max={new Date().toISOString().split("T")[0]}
              />
            </div>
          ) : null}
<div>
                 {error ? <div style={styles.error}>{error}</div> : null}
          {success ? <div style={styles.success}>{success}</div> : null}
</div>
          <div style={styles.footer}>
            <button
              type="button"
              style={styles.secondaryBtn}
              onClick={() => navigate(-1)}
              disabled={loading}
            >
              Cancel
            </button>

            <button
              type="button"
              style={styles.primaryBtn}
              onClick={handleSave}
              disabled={loading}
            >
              {loading ? "Saving..." : "Update Pet"}
            </button>
 

          </div>
        </div>
      </div>
    </div>
  );
}
