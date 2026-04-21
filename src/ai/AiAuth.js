import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from "react";

export const AI_AUTH_STORAGE_KEYS = {
  token: "auth_token",
  user: "user_data",
  registrationComplete: "registration_complete",
  currentUserId: "current_user_id",
  userId: "user_id",
  chatRoomToken: "chat_room_token",
  phoneNumber: "phone_number",
  otpVerified: "otp_verified",
  userIdentifier: "user_identifier",
  latestChat: "latest_chat",
  latestCallSession: "latest_call_session",
  symptomDescription: "symptom_description",
  fcmToken: "fcm_token",
  lastVetId: "last_vet_id",
  referralClinicId: "referral_clinic_id",
  clinicDoctors: "clinic_doctors",
};

export const AI_PET_FORM_DRAFT_KEY = "ai_pet_form_draft";

const AiAuthContext = createContext(null);

const getStorage = () =>
  typeof window !== "undefined" ? window.localStorage : null;

const normalizeText = (value) => String(value ?? "").trim();

const normalizeId = (value) => {
  const next = normalizeText(value);
  return next && next !== "null" && next !== "undefined" ? next : "";
};

const normalizeBooleanFlag = (value) => {
  if (typeof value === "boolean") return value;
  if (typeof value === "number") return value === 1;

  const next = normalizeText(value).toLowerCase();
  if (!next) return null;
  if (["1", "true", "yes"].includes(next)) return true;
  if (["0", "false", "no"].includes(next)) return false;
  return null;
};

const safeParseJson = (value, fallback = null) => {
  if (!value) return fallback;

  try {
    return JSON.parse(value);
  } catch {
    return fallback;
  }
};

const readStorageValue = (key) => {
  try {
    return getStorage()?.getItem(key) ?? null;
  } catch {
    return null;
  }
};

const writeStorageValue = (key, value) => {
  try {
    const storage = getStorage();
    if (!storage) return;

    if (value === null || value === undefined || value === "") {
      storage.removeItem(key);
      return;
    }

    storage.setItem(key, String(value));
  } catch {}
};

const writeStorageJson = (key, value) => {
  try {
    const storage = getStorage();
    if (!storage) return;

    if (value === null || value === undefined) {
      storage.removeItem(key);
      return;
    }

    storage.setItem(key, JSON.stringify(value));
  } catch {}
};

const removeStorageValue = (key) => {
  try {
    getStorage()?.removeItem(key);
  } catch {}
};

const resolvePetImageValue = (...sources) => {
  for (const source of sources) {
    if (!source || typeof source !== "object") continue;

    const resolved =
      pickFirstValue(
        source.pet_doc1,
        source.petDoc1,
        source.pet_image_url,
        source.petImageUrl,
        source.avatar,
        source.photo,
        source.image,
        source.image_url,
        source.imageUrl,
        source.profile_image,
        source.profileImage,
        source.pet_photo,
        source.petPhoto,
      ) ?? "";

    if (normalizeText(resolved)) {
      return resolved;
    }
  }

  return "";
};

const buildPetDedupeKey = (pet) => {
  const normalizedPetId = normalizeId(pet?.id ?? pet?.pet_id);
  if (normalizedPetId) {
    return `id:${normalizedPetId}`;
  }

  const petName = normalizeText(pet?.name ?? pet?.pet_name).toLowerCase();
  const petType = normalizeText(pet?.pet_type ?? pet?.species ?? pet?.type).toLowerCase();
  const petDob = normalizeText(pet?.pet_dob ?? pet?.dob);

  if (!petName) return "";
  return `fallback:${petName}::${petType}::${petDob}`;
};

const normalizePetRecord = (pet) => {
  if (!pet || typeof pet !== "object") return null;

  const normalizedPetId = normalizeId(pet?.id ?? pet?.pet_id);
  const normalizedPetImage = resolvePetImageValue(pet);
  if (!normalizedPetId) {
    return {
      ...pet,
      pet_doc1: normalizeText(pet?.pet_doc1 ?? normalizedPetImage) || "",
      pet_image_url:
        normalizeText(
          pet?.pet_image_url ?? pet?.petImageUrl ?? normalizedPetImage,
        ) || "",
      avatar: normalizeText(pet?.avatar ?? normalizedPetImage) || "",
      image: normalizeText(pet?.image ?? normalizedPetImage) || "",
      image_url:
        normalizeText(pet?.image_url ?? pet?.imageUrl ?? normalizedPetImage) || "",
      profile_image:
        normalizeText(
          pet?.profile_image ?? pet?.profileImage ?? normalizedPetImage,
        ) || "",
    };
  }

  return {
    ...pet,
    id: normalizedPetId,
    pet_id: normalizedPetId,
    pet_doc1: normalizeText(pet?.pet_doc1 ?? normalizedPetImage) || "",
    pet_image_url:
      normalizeText(
        pet?.pet_image_url ?? pet?.petImageUrl ?? normalizedPetImage,
      ) || "",
    avatar: normalizeText(pet?.avatar ?? normalizedPetImage) || "",
    image: normalizeText(pet?.image ?? normalizedPetImage) || "",
    image_url:
      normalizeText(pet?.image_url ?? pet?.imageUrl ?? normalizedPetImage) || "",
    profile_image:
      normalizeText(
        pet?.profile_image ?? pet?.profileImage ?? normalizedPetImage,
      ) || "",
  };
};

const normalizePets = (pets) => {
  if (!Array.isArray(pets)) return [];

  const seen = new Set();
  const normalizedPets = [];

  pets.forEach((item) => {
    const normalizedPet = normalizePetRecord(item);
    if (!normalizedPet) return;

    const dedupeKey = buildPetDedupeKey(normalizedPet);
    if (dedupeKey && seen.has(dedupeKey)) {
      return;
    }

    if (dedupeKey) {
      seen.add(dedupeKey);
    }

    normalizedPets.push(normalizedPet);
  });

  return normalizedPets;
};

const getPrimaryPet = (user = {}) => {
  if (user?.pet && typeof user.pet === "object") {
    return user.pet;
  }

  const pets = normalizePets(user?.pets);
  return pets[0] || null;
};

const pickFirstValue = (...values) => {
  for (const value of values) {
    if (value === 0 || value === false) return value;

    const next = normalizeText(value);
    if (next) return value;
  }

  return null;
};

const buildPhoneNumber = (value, fallback = "") => {
  const digits = normalizeText(value).replace(/[^\d]/g, "");

  if (digits.length === 10) return `91${digits}`;
  if (digits.length > 10) return digits;
  return normalizeText(fallback);
};

export const buildAiUserData = (user = {}, options = {}) => {
  const sourceUser = user && typeof user === "object" ? user : {};
  const mergedUser = { ...sourceUser, ...options };
  const pets = normalizePets(options.pets ?? mergedUser.pets);
  const primaryPet =
    (options.primaryPet && typeof options.primaryPet === "object"
      ? options.primaryPet
      : null) || getPrimaryPet({ ...mergedUser, pets });

  const normalizedId =
    normalizeId(options.id ?? mergedUser.id ?? mergedUser.user_id) ||
    normalizeId(mergedUser.id ?? mergedUser.user_id);

  const latestChat =
    options.latestChat ?? mergedUser.latest_chat ?? mergedUser.latestChat ?? null;
  const latestCallSession =
    options.latestCallSession ??
    mergedUser.latest_call_session ??
    mergedUser.latestCallSession ??
    null;

  const locationLatitude = pickFirstValue(
    options.latitude,
    mergedUser.latitude,
    mergedUser.userLatitude,
  );
  const locationLongitude = pickFirstValue(
    options.longitude,
    mergedUser.longitude,
    mergedUser.userLongitude,
  );

  const phone = buildPhoneNumber(
    options.phone ?? mergedUser.phone ?? mergedUser.mobileNumber,
    mergedUser.phone ?? mergedUser.mobileNumber,
  );

  const petWeight =
    pickFirstValue(
      options.pet_weight,
      mergedUser.pet_weight,
      primaryPet?.weight,
      primaryPet?.pet_weight,
      primaryPet?.weight_kg,
      mergedUser.weight,
    ) ?? "";

  const resolvedPetImage =
    resolvePetImageValue(options, mergedUser, primaryPet) || "";

  const resolvedPetId =
    normalizeId(
      pickFirstValue(
        options.pet_id,
        mergedUser.pet_id,
        primaryPet?.id,
        primaryPet?.pet_id,
      ),
    ) || null;

  const resolvedPet = primaryPet
    ? {
        ...primaryPet,
        id: resolvedPetId,
        pet_id: resolvedPetId,
        name:
          pickFirstValue(
            options.pet_name,
            mergedUser.pet_name,
            primaryPet?.name,
            primaryPet?.pet_name,
          ) ?? "",
        pet_name:
          pickFirstValue(
            options.pet_name,
            mergedUser.pet_name,
            primaryPet?.pet_name,
            primaryPet?.name,
          ) ?? "",
        breed:
          pickFirstValue(options.breed, mergedUser.breed, primaryPet?.breed) ?? "",
        pet_gender:
          pickFirstValue(
            options.pet_gender,
            mergedUser.pet_gender,
            primaryPet?.pet_gender,
            primaryPet?.gender,
          ) ?? "",
        pet_age:
          pickFirstValue(
            options.pet_age,
            mergedUser.pet_age,
            primaryPet?.pet_age,
            primaryPet?.pet_age_months,
          ) ?? "",
        pet_type:
          pickFirstValue(
            options.pet_type,
            mergedUser.pet_type,
            primaryPet?.pet_type,
            primaryPet?.species,
            primaryPet?.type,
          ) ?? "",
        pet_dob:
          pickFirstValue(
            options.pet_dob,
            mergedUser.pet_dob,
            primaryPet?.pet_dob,
            primaryPet?.dob,
          ) ?? "",
        pet_doc1:
          pickFirstValue(
            options.pet_doc1,
            mergedUser.pet_doc1,
            primaryPet?.pet_doc1,
            resolvedPetImage,
          ) ?? "",
        pet_image_url:
          pickFirstValue(
            options.pet_image_url,
            options.petImageUrl,
            mergedUser.pet_image_url,
            mergedUser.petImageUrl,
            primaryPet?.pet_image_url,
            primaryPet?.petImageUrl,
            resolvedPetImage,
          ) ?? "",
        avatar:
          pickFirstValue(
            options.avatar,
            mergedUser.avatar,
            primaryPet?.avatar,
            resolvedPetImage,
          ) ?? "",
        image:
          pickFirstValue(
            options.image,
            mergedUser.image,
            primaryPet?.image,
            resolvedPetImage,
          ) ?? "",
        image_url:
          pickFirstValue(
            options.image_url,
            options.imageUrl,
            mergedUser.image_url,
            mergedUser.imageUrl,
            primaryPet?.image_url,
            primaryPet?.imageUrl,
            resolvedPetImage,
          ) ?? "",
        profile_image:
          pickFirstValue(
            options.profile_image,
            options.profileImage,
            mergedUser.profile_image,
            mergedUser.profileImage,
            primaryPet?.profile_image,
            primaryPet?.profileImage,
            resolvedPetImage,
          ) ?? "",
        pet_doc2:
          pickFirstValue(
            options.pet_doc2,
            mergedUser.pet_doc2,
            primaryPet?.pet_doc2,
          ) ?? "",
        weight: petWeight,
        pet_weight: petWeight,
        is_nuetered:
          normalizeBooleanFlag(
            pickFirstValue(
              options.is_nuetered,
              mergedUser.is_nuetered,
              primaryPet?.is_nuetered,
              primaryPet?.is_neutered,
            ),
          ) ?? null,
        is_neutered:
          normalizeBooleanFlag(
            pickFirstValue(
              options.is_nuetered,
              mergedUser.is_nuetered,
              primaryPet?.is_neutered,
              primaryPet?.is_nuetered,
            ),
          ) ?? null,
        deworming_yes_no:
          normalizeBooleanFlag(
            pickFirstValue(
              options.deworming_yes_no,
              mergedUser.deworming_yes_no,
              primaryPet?.deworming_yes_no,
            ),
          ) ?? null,
      }
    : null;

  const resolvedPets = normalizePets(
    resolvedPet ? [resolvedPet, ...pets] : pets,
  );

  const registrationComplete =
    normalizeBooleanFlag(
      pickFirstValue(
        options.registrationComplete,
        options.registration_complete,
        options.profileCompleted,
        mergedUser.registrationComplete,
        mergedUser.registration_complete,
        mergedUser.profileCompleted,
      ),
    ) ?? false;

  return {
    ...mergedUser,
    ...(resolvedPet ? { pet: resolvedPet } : {}),
    id: normalizedId || mergedUser.id || mergedUser.user_id || "",
    user_id: normalizedId || mergedUser.user_id || mergedUser.id || "",
    pet_id:
      normalizeId(
        pickFirstValue(
          options.pet_id,
          mergedUser.pet_id,
          resolvedPet?.id,
          resolvedPet?.pet_id,
        ),
      ) || "",
    phone,
    mobileNumber:
      buildPhoneNumber(
        options.mobileNumber ?? mergedUser.mobileNumber ?? phone,
        phone,
      ) || phone,
    latitude: locationLatitude ?? mergedUser.latitude ?? null,
    longitude: locationLongitude ?? mergedUser.longitude ?? null,
    pets: resolvedPets,
    pet_owner_name:
      pickFirstValue(
        options.pet_owner_name,
        mergedUser.pet_owner_name,
        options.owner_name,
        mergedUser.owner_name,
        mergedUser.name,
      ) ?? "",
    owner_name:
      pickFirstValue(
        options.owner_name,
        mergedUser.owner_name,
        options.pet_owner_name,
        mergedUser.pet_owner_name,
        mergedUser.name,
      ) ?? "",
    pet_name:
      pickFirstValue(
        options.pet_name,
        mergedUser.pet_name,
        resolvedPet?.name,
        resolvedPet?.pet_name,
      ) ?? "",
    pet_gender:
      pickFirstValue(
        options.pet_gender,
        mergedUser.pet_gender,
        resolvedPet?.pet_gender,
      ) ?? "",
    breed:
      pickFirstValue(options.breed, mergedUser.breed, resolvedPet?.breed) ?? "",
    pet_age:
      pickFirstValue(options.pet_age, mergedUser.pet_age, resolvedPet?.pet_age) ??
      "",
    pet_type:
      pickFirstValue(
        options.pet_type,
        mergedUser.pet_type,
        resolvedPet?.pet_type,
      ) ?? "",
    pet_dob:
      pickFirstValue(options.pet_dob, mergedUser.pet_dob, resolvedPet?.pet_dob) ??
      "",
    pet_doc1:
      pickFirstValue(
        options.pet_doc1,
        mergedUser.pet_doc1,
        resolvedPet?.pet_doc1,
        resolvedPetImage,
      ) ?? "",
    pet_image_url:
      pickFirstValue(
        options.pet_image_url,
        options.petImageUrl,
        mergedUser.pet_image_url,
        mergedUser.petImageUrl,
        resolvedPet?.pet_image_url,
        resolvedPetImage,
      ) ?? "",
    avatar:
      pickFirstValue(
        options.avatar,
        mergedUser.avatar,
        resolvedPet?.avatar,
        resolvedPetImage,
      ) ?? "",
    image:
      pickFirstValue(
        options.image,
        mergedUser.image,
        resolvedPet?.image,
        resolvedPetImage,
      ) ?? "",
    image_url:
      pickFirstValue(
        options.image_url,
        options.imageUrl,
        mergedUser.image_url,
        mergedUser.imageUrl,
        resolvedPet?.image_url,
        resolvedPetImage,
      ) ?? "",
    profile_image:
      pickFirstValue(
        options.profile_image,
        options.profileImage,
        mergedUser.profile_image,
        mergedUser.profileImage,
        resolvedPet?.profile_image,
        resolvedPetImage,
      ) ?? "",
    pet_doc2:
      pickFirstValue(
        options.pet_doc2,
        mergedUser.pet_doc2,
        resolvedPet?.pet_doc2,
      ) ?? "",
    pet_weight: petWeight,
    weight:
      pickFirstValue(options.weight, mergedUser.weight, petWeight, resolvedPet?.weight) ??
      "",
    is_nuetered:
      normalizeBooleanFlag(
        pickFirstValue(
          options.is_nuetered,
          mergedUser.is_nuetered,
          resolvedPet?.is_nuetered,
          resolvedPet?.is_neutered,
        ),
      ) ?? null,
    deworming_yes_no:
      normalizeBooleanFlag(
        pickFirstValue(
          options.deworming_yes_no,
          mergedUser.deworming_yes_no,
          resolvedPet?.deworming_yes_no,
        ),
      ) ?? null,
    latest_chat: latestChat,
    latest_call_session: latestCallSession,
    chat_room_token:
      normalizeText(
        options.chat_room_token ??
          options.chatRoomToken ??
          mergedUser.chat_room_token ??
          latestChat?.chat_room_token ??
          latestChat?.context_token,
      ) || "",
    registrationComplete,
    registration_complete: registrationComplete,
    profileCompleted:
      normalizeBooleanFlag(
        pickFirstValue(options.profileCompleted, mergedUser.profileCompleted),
      ) ?? registrationComplete,
  };
};

export const readAiAuthState = () => {
  const token = normalizeText(readStorageValue(AI_AUTH_STORAGE_KEYS.token));
  const storedUser = safeParseJson(readStorageValue(AI_AUTH_STORAGE_KEYS.user), {});
  const latestChat = safeParseJson(
    readStorageValue(AI_AUTH_STORAGE_KEYS.latestChat),
    null,
  );
  const latestCallSession = safeParseJson(
    readStorageValue(AI_AUTH_STORAGE_KEYS.latestCallSession),
    null,
  );
  const fcmToken = normalizeText(readStorageValue(AI_AUTH_STORAGE_KEYS.fcmToken));
  const clinicDoctors = safeParseJson(
    readStorageValue(AI_AUTH_STORAGE_KEYS.clinicDoctors),
    null,
  );
  const chatRoomToken = normalizeText(
    readStorageValue(AI_AUTH_STORAGE_KEYS.chatRoomToken),
  );

  const user = buildAiUserData(storedUser, {
    latestChat,
    latestCallSession,
    chat_room_token:
      chatRoomToken || storedUser?.chat_room_token || latestChat?.chat_room_token,
    registrationComplete:
      normalizeBooleanFlag(
        readStorageValue(AI_AUTH_STORAGE_KEYS.registrationComplete),
      ) ??
      storedUser?.registrationComplete ??
      storedUser?.registration_complete,
  });

  return {
    user,
    token,
    chatRoomToken: user.chat_room_token || chatRoomToken || "",
    latestChat,
    latestCallSession,
    registrationComplete: Boolean(
      user.registrationComplete || user.registration_complete,
    ),
    fcmToken,
    clinicDoctors,
  };
};

export const persistAiAuthState = ({
  user,
  token,
  latestChat,
  latestCallSession,
  fcmToken,
  clinicDoctors,
} = {}) => {
  const currentState = readAiAuthState();
  const nextUser = buildAiUserData(
    {
      ...(currentState.user || {}),
      ...(user && typeof user === "object" ? user : {}),
    },
    {
      latestChat:
        latestChat ??
        user?.latest_chat ??
        currentState.latestChat ??
        currentState.user?.latest_chat,
      latestCallSession:
        latestCallSession ??
        user?.latest_call_session ??
        currentState.latestCallSession ??
        currentState.user?.latest_call_session,
      chat_room_token:
        user?.chat_room_token ??
        currentState.chatRoomToken ??
        currentState.user?.chat_room_token,
      registrationComplete:
        user?.registrationComplete ??
        user?.registration_complete ??
        user?.profileCompleted ??
        currentState.registrationComplete,
    },
  );

  const nextToken =
    normalizeText(token) || currentState.token || normalizeText(user?.token);
  const nextChatRoomToken =
    normalizeText(nextUser.chat_room_token) || currentState.chatRoomToken;
  const nextLatestChat =
    latestChat ?? nextUser.latest_chat ?? currentState.latestChat ?? null;
  const nextLatestCallSession =
    latestCallSession ??
    nextUser.latest_call_session ??
    currentState.latestCallSession ??
    null;
  const nextRegistrationComplete = Boolean(
    nextUser.registrationComplete ||
      nextUser.registration_complete ||
      nextUser.profileCompleted,
  );

  writeStorageJson(AI_AUTH_STORAGE_KEYS.user, nextUser);
  writeStorageValue(AI_AUTH_STORAGE_KEYS.token, nextToken || null);
  writeStorageValue(
    AI_AUTH_STORAGE_KEYS.registrationComplete,
    nextRegistrationComplete ? "true" : "false",
  );
  writeStorageValue(
    AI_AUTH_STORAGE_KEYS.currentUserId,
    normalizeId(nextUser.id || nextUser.user_id) || null,
  );
  writeStorageValue(
    AI_AUTH_STORAGE_KEYS.userId,
    normalizeId(nextUser.user_id || nextUser.id) || null,
  );
  writeStorageValue(
    AI_AUTH_STORAGE_KEYS.phoneNumber,
    nextUser.phone || nextUser.mobileNumber || null,
  );
  writeStorageValue(
    AI_AUTH_STORAGE_KEYS.otpVerified,
    nextToken || nextUser.id ? "true" : null,
  );
  writeStorageValue(
    AI_AUTH_STORAGE_KEYS.userIdentifier,
    nextUser.phone ? `user_${nextUser.phone}` : null,
  );
  writeStorageValue(
    AI_AUTH_STORAGE_KEYS.chatRoomToken,
    nextChatRoomToken || null,
  );
  writeStorageJson(
    AI_AUTH_STORAGE_KEYS.latestChat,
    nextLatestChat && typeof nextLatestChat === "object" ? nextLatestChat : null,
  );
  writeStorageJson(
    AI_AUTH_STORAGE_KEYS.latestCallSession,
    nextLatestCallSession && typeof nextLatestCallSession === "object"
      ? nextLatestCallSession
      : null,
  );

  if (nextLatestChat?.question) {
    writeStorageValue(
      AI_AUTH_STORAGE_KEYS.symptomDescription,
      nextLatestChat.question,
    );
  }

  writeStorageValue(
    AI_AUTH_STORAGE_KEYS.lastVetId,
    normalizeId(nextUser.last_vet_id) || null,
  );
  writeStorageValue(
    AI_AUTH_STORAGE_KEYS.referralClinicId,
    normalizeId(nextUser.referral_clinic_id) || null,
  );
  writeStorageValue(
    AI_AUTH_STORAGE_KEYS.fcmToken,
    normalizeText(fcmToken || currentState.fcmToken) || null,
  );
  writeStorageJson(
    AI_AUTH_STORAGE_KEYS.clinicDoctors,
    clinicDoctors ?? currentState.clinicDoctors ?? null,
  );

  return {
    user: nextUser,
    token: nextToken,
    chatRoomToken: nextChatRoomToken,
    latestChat: nextLatestChat,
    latestCallSession: nextLatestCallSession,
    registrationComplete: nextRegistrationComplete,
    fcmToken: normalizeText(fcmToken || currentState.fcmToken),
    clinicDoctors: clinicDoctors ?? currentState.clinicDoctors ?? null,
  };
};

export const updateAiUserData = (patch = {}, options = {}) =>
  persistAiAuthState({
    user: patch,
    latestChat: options.latestChat,
    latestCallSession: options.latestCallSession,
    token: options.token,
  });

export const clearAiAuthState = () => {
  Object.values(AI_AUTH_STORAGE_KEYS).forEach((key) => removeStorageValue(key));
  removeStorageValue(AI_PET_FORM_DRAFT_KEY);
};

export const readAiPetFormDraft = () =>
  safeParseJson(readStorageValue(AI_PET_FORM_DRAFT_KEY), null);

export const writeAiPetFormDraft = (draft) => {
  writeStorageJson(AI_PET_FORM_DRAFT_KEY, draft);
};

export const clearAiPetFormDraft = () => {
  removeStorageValue(AI_PET_FORM_DRAFT_KEY);
};

export function AiAuthProvider({ children }) {
  const [authState, setAuthState] = useState(() => readAiAuthState());

  const login = useCallback((user, token, extras = {}) => {
    const nextState = persistAiAuthState({
      user,
      token,
      latestChat: extras.latestChat,
      latestCallSession: extras.latestCallSession,
      fcmToken: extras.fcmToken,
      clinicDoctors: extras.clinicDoctors,
    });
    setAuthState(nextState);
    return nextState;
  }, []);

  const updateUser = useCallback((patch, extras = {}) => {
    const nextState = updateAiUserData(patch, extras);
    setAuthState(nextState);
    return nextState;
  }, []);

  const logout = useCallback(() => {
    clearAiAuthState();
    const emptyState = {
      user: null,
      token: "",
      chatRoomToken: "",
      latestChat: null,
      latestCallSession: null,
      registrationComplete: false,
      fcmToken: "",
      clinicDoctors: null,
    };
    setAuthState(emptyState);
    return emptyState;
  }, []);

  useEffect(() => {
    const handleStorage = () => {
      setAuthState(readAiAuthState());
    };

    window.addEventListener("storage", handleStorage);
    return () => window.removeEventListener("storage", handleStorage);
  }, []);

  const value = useMemo(
    () => ({
      ...authState,
      login,
      updateUser,
      logout,
      refresh: () => setAuthState(readAiAuthState()),
    }),
    [authState, login, logout, updateUser],
  );

  return React.createElement(
    AiAuthContext.Provider,
    { value },
    children,
  );
}

export function useAiAuth() {
  const context = useContext(AiAuthContext);
  if (!context) {
    throw new Error("useAiAuth must be used inside AiAuthProvider");
  }
  return context;
}
