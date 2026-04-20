import React, { useCallback, useEffect, useMemo, useState } from "react";
import axios from "axios";
import NavItem from "./NavItem.jsx";
import AiLoginForm from "./AiLoginForm.jsx";
import PetForn from "./PetForn.jsx";
import { readAiAuthState } from "./AiAuth";

const API_BASE_URL = "https://snoutiq.com/backend/api";

const normalizeBoolean = (value) => {
  if (typeof value === "boolean") return value;
  if (typeof value === "number") return value === 1;
  const normalized = String(value ?? "").trim().toLowerCase();
  return normalized === "1" || normalized === "true" || normalized === "yes";
};

const sanitizeOwnerName = (value) => {
  const normalized = String(value ?? "").trim();
  if (!normalized) return "";

  const digits = normalized.replace(/[^\d]/g, "");
  const letters = normalized.replace(/[^a-z]/gi, "");
  return digits.length >= 7 && letters.length === 0 ? "" : normalized;
};

const normalizePetType = (value) => {
  const normalized = String(value ?? "").trim().toLowerCase();
  return normalized === "cat" ? "cat" : "dog";
};

const normalizeDateValue = (value) => {
  const normalized = String(value ?? "").trim();
  return /^\d{4}-\d{2}-\d{2}$/.test(normalized) ? normalized : "";
};

const toNullableNumber = (value) => {
  const parsed = Number.parseFloat(value);
  return Number.isFinite(parsed) ? parsed : null;
};

const calculateAgeFromDob = (dobValue) => {
  const normalizedDob = normalizeDateValue(dobValue);
  if (!normalizedDob) return "";

  const dob = new Date(`${normalizedDob}T00:00:00`);
  if (Number.isNaN(dob.getTime())) return "";

  const today = new Date();
  let years = today.getFullYear() - dob.getFullYear();
  const monthDiff = today.getMonth() - dob.getMonth();
  const dayDiff = today.getDate() - dob.getDate();

  if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
    years -= 1;
  }

  return String(Math.max(years, 0));
};

const hasUsablePetProfile = (authState) => {
  const user = authState?.user || {};
  const primaryPet =
    user?.pet && typeof user.pet === "object"
      ? user.pet
      : Array.isArray(user?.pets) && user.pets.length > 0
      ? user.pets[0]
      : null;

  const registrationFlag =
    normalizeBoolean(authState?.registrationComplete) ||
    normalizeBoolean(user?.registrationComplete) ||
    normalizeBoolean(user?.registration_complete) ||
    normalizeBoolean(user?.profileCompleted);

  if (registrationFlag) return true;

  const petName = String(
    primaryPet?.name ?? primaryPet?.pet_name ?? user?.pet_name ?? ""
  ).trim();

  const ownerName = sanitizeOwnerName(
    user?.pet_owner_name ?? user?.owner_name ?? user?.name ?? ""
  );

  return Boolean(petName && ownerName);
};

const hasAuthSession = (authState) => {
  const user = authState?.user || {};
  return Boolean(authState?.token && (user?.id || user?.user_id));
};

export default function AiRoute() {
  const [authState, setAuthState] = useState(() => readAiAuthState());
  const [pendingQuestion, setPendingQuestion] = useState("");
  const [screen, setScreen] = useState(() => {
    const initialState = readAiAuthState();
    if (!hasAuthSession(initialState)) return "chat";
    return hasUsablePetProfile(initialState) ? "chat" : "profile";
  });

  const refreshAuthState = useCallback(() => {
    const nextState = readAiAuthState();
    setAuthState(nextState);
    return nextState;
  }, []);

  useEffect(() => {
    const handleStorage = () => {
      refreshAuthState();
    };

    window.addEventListener("storage", handleStorage);
    return () => window.removeEventListener("storage", handleStorage);
  }, [refreshAuthState]);

  const isLoggedIn = useMemo(() => hasAuthSession(authState), [authState]);
  const isProfileReady = useMemo(() => hasUsablePetProfile(authState), [authState]);

  const handleRequireAccessFlow = useCallback(
    (questionText) => {
      const normalizedQuestion = String(questionText ?? "").trim();
      if (!normalizedQuestion) return false;

      setPendingQuestion(normalizedQuestion);

      const latestState = refreshAuthState();

      if (!hasAuthSession(latestState)) {
        setScreen("login");
        return true;
      }

      if (!hasUsablePetProfile(latestState)) {
        setScreen("profile");
        return true;
      }

      return false;
    },
    [refreshAuthState]
  );

  const handleLoginSuccess = useCallback(() => {
    const latestState = refreshAuthState();
    if (hasUsablePetProfile(latestState)) {
      setScreen("chat");
    } else {
      setScreen("profile");
    }
  }, [refreshAuthState]);

  const submitIntake = useCallback(async (form) => {
    const latestState = readAiAuthState();
    const token = latestState?.token;
    const user = latestState?.user || {};

    const resolvedUserId = user?.user_id || user?.id;
    if (!token || !resolvedUserId) {
      throw new Error("Please login first.");
    }

    const primaryPet =
      user?.pet && typeof user.pet === "object"
        ? user.pet
        : Array.isArray(user?.pets) && user.pets.length > 0
        ? user.pets[0]
        : null;

    const formData = new FormData();
    formData.append("user_id", String(resolvedUserId));
    formData.append(
      "pet_owner_name",
      sanitizeOwnerName(
        form.owner_name || user?.pet_owner_name || user?.owner_name || user?.name || ""
      )
    );
    formData.append(
      "pet_type",
      normalizePetType(form.pet_type || primaryPet?.pet_type || user?.pet_type || "dog")
    );
    formData.append("pet_name", String(form.pet_name || "").trim());
    formData.append("pet_gender", String(form.sex || "male").trim());
    formData.append("role", "pet");

    const petDob = normalizeDateValue(form.pet_dob || primaryPet?.pet_dob || user?.pet_dob || "");
    if (petDob) {
      formData.append("pet_dob", petDob);
    }

    const petAge =
      calculateAgeFromDob(petDob) ||
      String(primaryPet?.pet_age || user?.pet_age || "")
        .replace(/[^\d]/g, "")
        .trim();
    if (petAge) {
      formData.append("pet_age", petAge);
    }

    formData.append("is_nuetered", form.neutered ? "1" : "0");
    formData.append("deworming_yes_no", form.dewormed ? "1" : "0");

    const lastDewormingDate = form.dewormed
      ? normalizeDateValue(
          form.last_deworming_date ||
            primaryPet?.last_deworming_date ||
            user?.last_deworming_date ||
            ""
        )
      : "";
    if (lastDewormingDate) {
      formData.append("last_deworming_date", lastDewormingDate);
    }

    const breed = String(form.breed || primaryPet?.breed || user?.breed || "").trim();
    if (breed) {
      formData.append("breed", breed);
    }

    const weightValue = String(
      primaryPet?.pet_weight ||
        primaryPet?.weight ||
        user?.pet_weight ||
        user?.weight ||
        ""
    ).trim();
    if (weightValue) {
      formData.append("pet_weight", weightValue);
      formData.append("weight", weightValue);
    }

    const latitude = toNullableNumber(form.latitude ?? user?.latitude);
    const longitude = toNullableNumber(form.longitude ?? user?.longitude);

    if (latitude != null) {
      formData.append("latitude", String(latitude));
    }
    if (longitude != null) {
      formData.append("longitude", String(longitude));
    }

    if (form.pet_doc2 instanceof File) {
      formData.append("pet_doc2", form.pet_doc2);
    }

    const response = await axios.post(`${API_BASE_URL}/auth/register`, formData, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    return response?.data || null;
  }, []);

  const handleProfileComplete = useCallback(() => {
    refreshAuthState();
    setScreen("chat");
  }, [refreshAuthState]);

  if (screen === "login") {
    return <AiLoginForm onLoginSuccess={handleLoginSuccess} />;
  }

  if (screen === "profile" && isLoggedIn && !isProfileReady) {
    return (
      <PetForn
        submitIntake={submitIntake}
        onComplete={handleProfileComplete}
      />
    );
  }

  return (
    <NavItem
      authState={authState}
      pendingQuestion={pendingQuestion}
      onRequireAccessFlow={handleRequireAccessFlow}
      onPendingQuestionConsumed={() => setPendingQuestion("")}
      onRequestLogin={() => setScreen("login")}
    />
  );
}
