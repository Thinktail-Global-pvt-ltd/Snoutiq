import React, { useEffect, useMemo, useRef, useState } from "react";
import axios from "axios";
import { buildAiUserData, persistAiAuthState } from "./AiAuth";
import logo from '../assets/images/logo.png';
import pet_image from '../assets/pet_image.png';

const OTP_CELL_COUNT = 4;
const PET_AUTH_ROLE = "pet";

const API_CONFIG = {
  baseURL: "https://snoutiq.com/backend/api",
  endpoints: {
    sendOtp: "/send-otp",
    verifyOtp: "/verify-otp",
  },
  timeout: 15000,
};

const apiClient = axios.create({
  baseURL: API_CONFIG.baseURL,
  timeout: API_CONFIG.timeout,
  headers: {
    "Content-Type": "application/json",
  },
});

const colors = {
  primary: "#2555F5",
  primaryDark: "#173ED7",
  white: "#FFFFFF",
  black: "#243045",
  darkGray: "#455166",
  mediumGray: "#7C879D",
  textGray: "#97A1B5",
  lightGray: "#F3F6FB",
  surface: "#F8FAFE",
  borderGray: "#E4EAF3",
  success: "#10B981",
  error: "#EF4444",
  pageTop: "#DCEAFF",
  pageMiddle: "#F0F6FF",
  pageBottom: "#FFFFFF",
  whatsappGreen: "#25D366",
  whatsappMuted: "#D9F7DE",
  whatsappBorder: "#C7EFCF",
  decorBlue: "#7FD3F7",
  decorPink: "#D9E5FF",
};

const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

const buildWavePath = (width, height) =>
  [
    `M 0 ${height * 0.62}`,
    `C ${width * 0.12} ${height * 0.1}, ${width * 0.25} 0, ${width * 0.41} ${height * 0.42}`,
    `C ${width * 0.54} ${height * 0.82}, ${width * 0.69} ${height}, ${width * 0.83} ${height * 0.64}`,
    `C ${width * 0.92} ${height * 0.38}, ${width * 0.97} ${height * 0.3}, ${width} ${height * 0.48}`,
    `L ${width} ${height}`,
    `L 0 ${height}`,
    "Z",
  ].join(" ");

function ShieldIcon({ size = 14, color = colors.success }) {
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <path
        d="M12 3l7 3v5c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-3z"
        stroke={color}
        strokeWidth="1.8"
        fill="none"
      />
      <path
        d="M9.5 12.5l1.6 1.6 3.6-4.1"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </svg>
  );
}

function AlertIcon({ size = 14, color = colors.error }) {
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <path
        d="M12 8v5m0 3h.01M10.3 3.8L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.8a2 2 0 0 0-3.4 0z"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </svg>
  );
}

function ChevronDownIcon({ size = 14, color = colors.darkGray }) {
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <path
        d="M6 9l6 6 6-6"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </svg>
  );
}

function WhatsAppIcon({ size = 18, color = colors.whatsappGreen }) {
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <path
        d="M20 11.5c0 4.9-3.9 8.8-8.8 8.8-1.5 0-3-.4-4.3-1.1L3 20l.9-3.7A8.7 8.7 0 0 1 2.4 11.5a8.8 8.8 0 0 1 17.6 0z"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
      <path
        d="M9 8.7c.3-.7.6-.7.9-.7h.7c.2 0 .5.1.6.4l.7 1.7c.1.3 0 .6-.1.8l-.5.6c-.1.1-.1.4 0 .5.4.7 1 1.3 1.7 1.7.1.1.4.1.5 0l.6-.5c.2-.2.5-.2.8-.1l1.7.7c.3.1.4.4.4.6v.7c0 .3 0 .6-.7.9-.6.2-1.3.4-2 .2-1.1-.2-2.6-1.1-4-2.5-1.4-1.4-2.3-2.9-2.5-4-.2-.7 0-1.4.2-2z"
        fill={color}
      />
    </svg>
  );
}

function LocationIcon({ size = 26, color = colors.primary }) {
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <path
        d="M12 21s7-5.7 7-11a7 7 0 1 0-14 0c0 5.3 7 11 7 11z"
        stroke={color}
        strokeWidth="1.8"
      />
      <circle cx="12" cy="10" r="2.6" stroke={color} strokeWidth="1.8" />
    </svg>
  );
}

function Spinner({ color = "#fff", size = 18 }) {
  return (
    <div
      style={{
        width: size,
        height: size,
        borderRadius: "50%",
        border: `2px solid ${color}55`,
        borderTopColor: color,
        animation: "spin 0.75s linear infinite",
      }}
    />
  );
}

export default function AiLoginForm({ onLoginSuccess }) {
  const [phone, setPhone] = useState("");
  const [otp, setOtp] = useState("");
  const [step, setStep] = useState("phone");
  const [token, setToken] = useState(null);
  const [loading, setLoading] = useState(false);
  const [checkingData, setCheckingData] = useState(false);
  const [errors, setErrors] = useState({});
  const [countdown, setCountdown] = useState(0);
  const [loginLocation, setLoginLocation] = useState(null);
  const [showLocationModal, setShowLocationModal] = useState(false);
  const [locationModalState, setLocationModalState] = useState("idle");
  const [locationModalError, setLocationModalError] = useState("");
  const [windowSize, setWindowSize] = useState({
    width: typeof window !== "undefined" ? window.innerWidth : 390,
    height: typeof window !== "undefined" ? window.innerHeight : 844,
  });

  const otpInputRef = useRef(null);
  const pendingVerifyRef = useRef(false);

  useEffect(() => {
    const handleResize = () => {
      setWindowSize({
        width: window.innerWidth,
        height: window.innerHeight,
      });
    };
    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  const layout = useMemo(() => {
    const screenWidth = clamp(windowSize.width, 320, 430);
    const screenHeight = clamp(windowSize.height, 560, 980);
    const widthRatio = screenWidth / 390;
    const isCompactHeight = screenHeight <= 760;
    const isVeryCompactHeight = screenHeight <= 680;
    const needsTightLayout = isCompactHeight || (step === "otp" && screenHeight <= 820);
    const horizontalPadding = clamp(
      Math.round((isVeryCompactHeight ? 14 : 18) * widthRatio),
      12,
      24
    );
    const topPadding = clamp(
      Math.round((needsTightLayout ? 8 : 12) * widthRatio),
      6,
      18
    );
    const bottomPadding = clamp(
      Math.round((needsTightLayout ? 10 : 18) * widthRatio),
      8,
      24
    );
    const logoWidth = clamp(Math.round(116 * widthRatio), 102, 124);
    const logoHeight = clamp(Math.round(28 * widthRatio), 24, 30);
    const heroWidthTarget = screenWidth * (isVeryCompactHeight ? 0.46 : needsTightLayout ? 0.52 : 0.64);
    const heroHeightTarget = screenHeight * (isVeryCompactHeight ? 0.2 : needsTightLayout ? 0.24 : 0.3);
    const heroSize = clamp(
      Math.round(Math.min(heroWidthTarget, heroHeightTarget)),
      isVeryCompactHeight ? 128 : 152,
      248
    );
    const waveHeight = clamp(
      Math.round((needsTightLayout ? 30 : 40) * widthRatio),
      24,
      46
    );
    const waveOverlap = clamp(Math.round(heroSize * 0.08), 8, 18);
    const formPaddingX = clamp(Math.round(24 * widthRatio), 18, 26);
    const formPaddingTop = clamp(
      Math.round((needsTightLayout ? 14 : 18) * widthRatio),
      12,
      22
    );
    const formPaddingBottom = clamp(needsTightLayout ? 20 : screenHeight < 720 ? 28 : 34, 18, 34);
    const sliderWidth = screenWidth - formPaddingX * 2;
    const titleSize = clamp(
      Math.round((needsTightLayout ? 24 : 28) * widthRatio),
      21,
      30
    );
    const titleLineHeight = Math.round(titleSize * 1.15);
    const subtitleSize = clamp(
      Math.round((needsTightLayout ? 11.5 : 12.5) * widthRatio),
      10,
      14
    );
    const subtitleLineHeight = Math.round(subtitleSize * 1.45);
    const eyebrowSize = clamp(Math.round(12 * widthRatio), 10, 13);
    const inputFontSize = clamp(Math.round((needsTightLayout ? 14.5 : 15.5) * widthRatio), 13, 16);
    const inputHeight = clamp(Math.round((needsTightLayout ? 44 : 48) * widthRatio), 40, 52);
    const buttonHeight = clamp(Math.round((needsTightLayout ? 44 : 48) * widthRatio), 40, 52);
    const sectionGap = clamp(Math.round((needsTightLayout ? 10 : 16) * widthRatio), 8, 18);
    const otpGap = clamp(Math.round((needsTightLayout ? 10 : 12) * widthRatio), 8, 14);
    const otpCellSize = clamp(
      Math.floor((sliderWidth - otpGap * (OTP_CELL_COUNT - 1) - 12) / OTP_CELL_COUNT),
      needsTightLayout ? 42 : 46,
      58
    );
    const formMinHeight = needsTightLayout
      ? 0
      : clamp(
          Math.round(
            screenHeight -
              (topPadding + logoHeight + heroSize - waveOverlap + bottomPadding + 36)
          ),
          250,
          520
        );
    const heroTopMargin = needsTightLayout ? 6 : 12;
    const securityMarginTop = needsTightLayout ? 10 : 14;

    return {
      screenWidth,
      screenHeight,
      isCompactHeight,
      isVeryCompactHeight,
      needsTightLayout,
      horizontalPadding,
      topPadding,
      bottomPadding,
      logoWidth,
      logoHeight,
      heroSize,
      waveHeight,
      waveOverlap,
      formPaddingX,
      formPaddingTop,
      formPaddingBottom,
      sliderWidth,
      titleSize,
      titleLineHeight,
      subtitleSize,
      subtitleLineHeight,
      eyebrowSize,
      inputFontSize,
      inputHeight,
      buttonHeight,
      sectionGap,
      otpGap,
      otpCellSize,
      formMinHeight,
      heroTopMargin,
      securityMarginTop,
    };
  }, [step, windowSize]);

  useEffect(() => {
    const savedPhone = localStorage.getItem("phone_number");
    if (savedPhone) {
      const digits = savedPhone.replace(/^\+?91/, "").replace(/[^\d]/g, "");
      if (digits.length === 10) setPhone(digits);
    }
    const lat = parseFloat(localStorage.getItem("userLatitude"));
    const lng = parseFloat(localStorage.getItem("userLongitude"));
    if (Number.isFinite(lat) && Number.isFinite(lng)) {
      setLoginLocation({ latitude: lat, longitude: lng });
    }
  }, []);

  useEffect(() => {
    if (countdown <= 0) return;
    const timer = setInterval(() => setCountdown((prev) => prev - 1), 1000);
    return () => clearInterval(timer);
  }, [countdown]);

  useEffect(() => {
    if (step === "otp") {
      const timer = setTimeout(() => otpInputRef.current?.focus(), 180);
      return () => clearTimeout(timer);
    }
  }, [step]);

  const phoneDigits = phone.replace(/[^\d]/g, "");
  const isValidPhone = phoneDigits.length === 10;
  const isValidOtp = otp.length === OTP_CELL_COUNT;
  const formattedPhone =
    phoneDigits.length === 10
      ? `+91 ${phoneDigits.slice(0, 5)} ${phoneDigits.slice(5)}`
      : "+91";

  const handleApiError = (error) => {
    let errorMessage = error?.message || "An unexpected error occurred";
    if (error.response) {
      const status = error.response.status;
      const message = error.response.data?.message || `Server error (${status})`;
      if (status === 401 || status === 403) errorMessage = "Unauthorized";
      else if (status === 404) errorMessage = "Not found";
      else if (status >= 500) errorMessage = "Server error";
      else errorMessage = message;
    } else if (error.request) {
      errorMessage = "Network error. Please check your internet.";
    }
    return errorMessage;
  };

  const hasValidLocation = (loc) =>
    Number.isFinite(loc?.latitude) && Number.isFinite(loc?.longitude);

  const resolveStoredLocation = () => {
    const latitude = parseFloat(localStorage.getItem("userLatitude"));
    const longitude = parseFloat(localStorage.getItem("userLongitude"));
    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return null;
    const stored = { latitude, longitude };
    setLoginLocation(stored);
    return stored;
  };

  const openLocationModal = (state, message = "") => {
    setLocationModalState(state);
    setLocationModalError(message);
    setShowLocationModal(true);
  };

  const closeLocationModal = () => {
    setShowLocationModal(false);
    setLocationModalState("idle");
    setLocationModalError("");
    pendingVerifyRef.current = false;
  };

  const getBrowserPermissionState = async () => {
    try {
      if (!navigator.permissions?.query) return null;
      const result = await navigator.permissions.query({ name: "geolocation" });
      return result.state;
    } catch {
      return null;
    }
  };

  const requestLocation = () =>
    new Promise((resolve) => {
      if (!navigator.geolocation) {
        resolve(null);
        return;
      }

      navigator.geolocation.getCurrentPosition(
        (position) => {
          const latitude = position?.coords?.latitude;
          const longitude = position?.coords?.longitude;
          if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
            resolve(null);
            return;
          }

          const payload = { latitude, longitude };
          localStorage.setItem("userLatitude", String(latitude));
          localStorage.setItem("userLongitude", String(longitude));
          setLoginLocation(payload);
          resolve(payload);
        },
        () => resolve(null),
        {
          enableHighAccuracy: false,
          timeout: 15000,
          maximumAge: 300000,
        }
      );
    });

  const ensureLocationForVerify = async () => {
    if (hasValidLocation(loginLocation)) return loginLocation;

    const storedLocation = resolveStoredLocation();
    if (hasValidLocation(storedLocation)) return storedLocation;

    const permissionState = await getBrowserPermissionState();

    if (permissionState === "denied") {
      openLocationModal("blocked");
      return null;
    }

    const currentLocation = await requestLocation();
    if (hasValidLocation(currentLocation)) return currentLocation;

    if (permissionState === "prompt" || permissionState === null) {
      openLocationModal("needs_permission");
    } else {
      openLocationModal(
        "unavailable",
        "Location is unavailable. Please turn on GPS/location and try again."
      );
    }
    return null;
  };

  const handleLocationPrimaryAction = async () => {
    if (locationModalState === "requesting") return;

    setLocationModalState("requesting");
    setLocationModalError("");

    const currentLocation = await requestLocation();

    if (!hasValidLocation(currentLocation)) {
      const permissionState = await getBrowserPermissionState();
      if (permissionState === "denied") setLocationModalState("blocked");
      else {
        setLocationModalState("unavailable");
        setLocationModalError("Location is unavailable. Please try again.");
      }
      return;
    }

    setShowLocationModal(false);
    setLocationModalState("idle");
    setLocationModalError("");

    if (pendingVerifyRef.current) {
      pendingVerifyRef.current = false;
      verifyOtp(currentLocation);
    }
  };

  const handlePhoneChange = (e) => {
    const digits = e.target.value.replace(/[^\d]/g, "").slice(0, 10);
    setPhone(digits);
    if (errors.phone) setErrors((prev) => ({ ...prev, phone: null }));
  };

  const handleOtpChange = (e) => {
    const digits = e.target.value.replace(/[^\d]/g, "").slice(0, OTP_CELL_COUNT);
    setOtp(digits);
    if (errors.otp) setErrors((prev) => ({ ...prev, otp: null }));
  };

  const sendOtp = async () => {
    setErrors({});
    if (loading) return;

    if (!isValidPhone) {
      setErrors({ phone: "Please enter a valid 10-digit mobile number" });
      return;
    }

    setLoading(true);
    try {
      const response = await apiClient.post(API_CONFIG.endpoints.sendOtp, {
        value: `91${phoneDigits}`,
        role: PET_AUTH_ROLE,
      });

      if (response.data?.token) {
        setToken(response.data.token);
        setStep("otp");
        setCountdown(60);
      } else {
        throw new Error("Token not received");
      }
    } catch (error) {
      setErrors({ phone: handleApiError(error) });
    } finally {
      setLoading(false);
    }
  };

  const verifyOtp = async (overrideLocation = null) => {
    setErrors({});
    if (loading) return;

    if (!isValidOtp) {
      setErrors({ otp: "Please enter a valid OTP" });
      return;
    }

    if (!token) {
      setErrors({ otp: "Please request OTP first" });
      return;
    }

    let locationPayload = overrideLocation;
    if (!hasValidLocation(locationPayload)) {
      locationPayload = await ensureLocationForVerify();
      if (!locationPayload) {
        pendingVerifyRef.current = true;
        return;
      }
    }

    pendingVerifyRef.current = false;
    setLoading(true);

    try {
      const phoneNumber = `91${phoneDigits}`;
      const payload = {
        token: String(token).trim(),
        otp: otp.trim(),
        phone: phoneNumber,
        role: PET_AUTH_ROLE,
      };

      if (locationPayload?.latitude != null && locationPayload?.longitude != null) {
        payload.latitude = locationPayload.latitude;
        payload.longitude = locationPayload.longitude;
      }

      const response = await apiClient.post(API_CONFIG.endpoints.verifyOtp, payload);
      const responseData = response.data || {};
      const userData = responseData.user || responseData.data?.user || null;
      const pets = responseData.pets || responseData.data?.pets || [];
      const primaryPet = Array.isArray(pets) && pets.length ? pets[0] : null;
      const latestChat = responseData.latest_chat || responseData.data?.latest_chat || null;
      const latestCallSession =
        responseData.latest_call_session || responseData.data?.latest_call_session || null;

      const rawUserId =
        responseData.user_id ||
        responseData.data?.user_id ||
        userData?.user_id ||
        userData?.id ||
        null;

      if (!userData && !rawUserId) {
        setErrors({ otp: "OTP verification failed. Please try again." });
        setLoading(false);
        return;
      }

      let authToken =
        responseData.token ||
        responseData.jwt ||
        responseData.access_token ||
        responseData.data?.token ||
        null;

      if (!authToken) {
        authToken = `user_${phoneNumber}`;
      }

      const mergedPetData = primaryPet
        ? {
            pet_name: primaryPet.name || userData?.pet_name,
            pet_gender: primaryPet.pet_gender || userData?.pet_gender,
            breed: primaryPet.breed || userData?.breed,
            pet_age: primaryPet.pet_age ?? userData?.pet_age,
            pet_doc1: primaryPet.pet_doc1 || userData?.pet_doc1,
            pet_doc2: primaryPet.pet_doc2 || userData?.pet_doc2,
          }
        : {};

      const finalUserData = buildAiUserData(
        {
          ...(userData || {}),
          ...mergedPetData,
          ...(locationPayload
            ? {
                latitude: locationPayload.latitude,
                longitude: locationPayload.longitude,
              }
            : {}),
          id: rawUserId,
          user_id: rawUserId,
          phone: phoneNumber,
          mobileNumber: phoneNumber,
          pets,
          latest_chat: latestChat || userData?.latest_chat,
          latest_call_session:
            latestCallSession ||
            userData?.latest_call_session ||
            userData?.latestCallSession ||
            null,
          chat_room_token:
            latestChat?.chat_room_token ||
            latestChat?.context_token ||
            userData?.chat_room_token,
        },
        {
          latestChat,
          latestCallSession,
        },
      );

      setCheckingData(true);

      persistAiAuthState({
        user: finalUserData,
        token: authToken,
        latestChat,
        latestCallSession,
      });

      if (typeof onLoginSuccess === "function") {
        await onLoginSuccess(finalUserData, authToken);
      }

      setCheckingData(false);
    } catch (error) {
      setErrors({ otp: handleApiError(error) });
      setCheckingData(false);
    } finally {
      setLoading(false);
    }
  };

  const resendOtp = async () => {
    if (countdown > 0) return;
    await sendOtp();
  };

  const resetToPhone = () => {
    setStep("phone");
    setOtp("");
    setToken(null);
    setErrors({});
    setCountdown(0);
  };

  const otpSlots = Array.from({ length: OTP_CELL_COUNT }, (_, index) => otp[index] || "");

  const locationModalContent = (() => {
    switch (locationModalState) {
      case "blocked":
        return {
          title: "Allow location in browser settings",
          message: "Location access is blocked. Please allow it in browser/site settings and try again.",
          primaryLabel: "Try Again",
          secondaryLabel: "Not now",
        };
      case "unavailable":
        return {
          title: "Location unavailable",
          message: "Turn on location services and try again.",
          primaryLabel: "Try Again",
          secondaryLabel: "Not now",
        };
      case "requesting":
        return {
          title: "Enabling location",
          message: "Please wait while we access your location.",
          primaryLabel: "Enabling...",
          secondaryLabel: "Not now",
        };
      case "needs_permission":
      default:
        return {
          title: "Enable location to continue",
          message: "We use your location to find nearby vets and complete sign-in.",
          primaryLabel: "Enable Location",
          secondaryLabel: "Not now",
        };
    }
  })();

  if (checkingData) {
    return (
      <div style={styles.loaderWrap}>
        <style>{globalCss}</style>
        <Spinner color={colors.primary} size={34} />
        <div style={styles.loaderText}>Please wait...</div>
      </div>
    );
  }

  return (
    <>
      <style>{globalCss}</style>
      <div style={styles.page}>
        <div
          style={{
            ...styles.decorCircle,
            ...styles.decorTop,
            width: layout.heroSize * 1.1,
            height: layout.heroSize * 1.1,
            top: -layout.heroSize * 0.52,
            right: -layout.heroSize * 0.3,
          }}
        />
        <div
          style={{
            ...styles.decorCircle,
            ...styles.decorBottom,
            width: layout.heroSize * 0.95,
            height: layout.heroSize * 0.95,
            bottom: -layout.heroSize * 0.42,
            left: -layout.heroSize * 0.32,
          }}
        />

        <div
          style={{
            ...styles.scrollArea,
            padding: `${layout.topPadding}px ${layout.horizontalPadding}px ${layout.bottomPadding}px`,
          }}
        >
          <div style={styles.contentWrap}>
            <div style={styles.logoRow}>
              <div style={styles.logoPill}>
                <img
                  src={logo}
                  alt="SnoutIQ"
                  style={{
                    width: layout.logoWidth,
                    height: layout.logoHeight,
                    objectFit: "contain",
                    display: "block",
                  }}
                />
              </div>
            </div>

            <div style={{ ...styles.heroSection, marginTop: layout.heroTopMargin }}>
              <img
                src={pet_image}
                alt="Pet"
                style={{
                  width: layout.heroSize,
                  height: layout.heroSize,
                  objectFit: "contain",
                  display: "block",
                }}
              />

              <div
                style={{
                  width: layout.screenWidth,
                  marginTop: -layout.waveOverlap,
                  marginLeft: -layout.horizontalPadding,
                  marginRight: -layout.horizontalPadding,
                }}
              >
                <div style={{ width: "100%", marginBottom: -1, height: layout.waveHeight }}>
                  <svg
                    width={layout.screenWidth}
                    height={layout.waveHeight}
                    viewBox={`0 0 ${layout.screenWidth} ${layout.waveHeight}`}
                    style={{ display: "block", width: "100%", height: "100%" }}
                  >
                    <path d={buildWavePath(layout.screenWidth, layout.waveHeight)} fill="#fff" />
                  </svg>
                </div>

                <div
                  style={{
                    ...styles.formCard,
                    minHeight: layout.formMinHeight,
                    padding: `${layout.formPaddingTop}px ${layout.formPaddingX}px ${layout.formPaddingBottom}px`,
                  }}
                >
                  <div style={{ overflow: "hidden", width: "100%" }}>
                    <div
                      style={{
                        display: "flex",
                        width: layout.sliderWidth * 2,
                        transform: `translateX(${step === "otp" ? -layout.sliderWidth : 0}px)`,
                        transition: "transform 0.35s ease",
                      }}
                    >
                      <div style={{ width: layout.sliderWidth }}>
                        <div
                          style={{
                            ...styles.welcomeText,
                            fontSize: layout.titleSize,
                            lineHeight: `${layout.titleLineHeight}px`,
                          }}
                        >
                          Welcome Back!
                        </div>

                        <div
                          style={{
                            ...styles.subtitleText,
                            fontSize: layout.subtitleSize,
                            lineHeight: `${layout.subtitleLineHeight}px`,
                          }}
                        >
                          Enter your details to continue
                        </div>

                        <div style={{ ...styles.metaRow, marginTop: layout.sectionGap - 8 }}>
                          <div style={styles.metaLine} />
                          <div
                            style={{
                              ...styles.metaText,
                              padding: "0 12px",
                              fontSize: layout.eyebrowSize,
                            }}
                          >
                            Log in or sign up
                          </div>
                          <div style={styles.metaLine} />
                        </div>

                        <div style={{ marginTop: layout.sectionGap + 2 }}>
                          <div
                            style={{
                              ...styles.phoneField,
                              minHeight: layout.inputHeight,
                              borderColor: errors.phone ? colors.error : colors.borderGray,
                            }}
                          >
                            <div style={styles.countryChip}>
                              <span style={{ ...styles.countryChipText }}>IN</span>
                              <ChevronDownIcon />
                            </div>
                            <div style={styles.inputDivider} />
                            <div style={{ ...styles.callingCodeText, fontSize: layout.inputFontSize }}>
                              +91
                            </div>
                            <input
                              value={phone}
                              onChange={handlePhoneChange}
                              placeholder="Enter mobile number"
                              maxLength={10}
                              inputMode="numeric"
                              autoComplete="tel"
                              style={{
                                ...styles.phoneInput,
                                fontSize: layout.inputFontSize,
                              }}
                            />
                          </div>

                          {errors.phone ? (
                            <div style={styles.errorContainer}>
                              <AlertIcon />
                              <div style={styles.errorText}>{errors.phone}</div>
                            </div>
                          ) : null}
                        </div>

                        <button
                          type="button"
                          onClick={sendOtp}
                          disabled={!isValidPhone || loading}
                          style={{
                            ...styles.primaryButton,
                            minHeight: layout.buttonHeight,
                            marginTop: layout.sectionGap + 2,
                            opacity: !isValidPhone || loading ? 0.5 : 1,
                          }}
                        >
                          {loading ? <Spinner color="#fff" /> : "Get OTP"}
                        </button>

                        <div
                          style={{
                            ...styles.securityBadge,
                            marginTop: layout.securityMarginTop,
                          }}
                        >
                          <ShieldIcon />
                          <div style={styles.securityText}>Secure & encrypted connection</div>
                        </div>
                      </div>

                      <div style={{ width: layout.sliderWidth }}>
                        <div
                          style={{
                            ...styles.welcomeText,
                            fontSize: layout.titleSize,
                            lineHeight: `${layout.titleLineHeight}px`,
                          }}
                        >
                          Welcome Back!
                        </div>

                        <div
                          style={{
                            ...styles.subtitleText,
                            fontSize: layout.subtitleSize,
                            lineHeight: `${layout.subtitleLineHeight}px`,
                            marginTop: 2,
                          }}
                        >
                          We&apos;ve sent a verification code to
                        </div>

                        <div
                          style={{
                            ...styles.phonePreviewText,
                            fontSize: layout.inputFontSize + 1,
                          }}
                        >
                          {formattedPhone}
                        </div>

                        <button
                          type="button"
                          onClick={resetToPhone}
                          disabled={loading}
                          style={styles.changeNumberButton}
                        >
                          Change number
                        </button>

                        <div style={{ ...styles.whatsappBadge, marginTop: layout.sectionGap }}>
                          <WhatsAppIcon />
                          <div style={styles.whatsappBadgeText}>Check WhatsApp for OTP</div>
                        </div>

                        <div
                          onClick={() => otpInputRef.current?.focus()}
                          style={{
                            ...styles.otpRow,
                            marginTop: layout.sectionGap,
                            gap: layout.otpGap,
                          }}
                        >
                          {otpSlots.map((digit, index) => {
                            const isFocused = index === Math.min(otp.length, OTP_CELL_COUNT - 1);
                            return (
                              <div
                                key={index}
                                style={{
                                  ...styles.otpCell,
                                  width: layout.otpCellSize,
                                  height: layout.otpCellSize,
                                  background: digit ? "#EEF3FF" : "#fff",
                                  borderColor: errors.otp
                                    ? colors.error
                                    : isFocused
                                    ? colors.primary
                                    : digit
                                    ? "#C8D8FF"
                                    : colors.borderGray,
                                }}
                              >
                                <span
                                  style={{
                                    ...styles.otpDigit,
                                    fontSize: layout.inputFontSize + 5,
                                  }}
                                >
                                  {digit}
                                </span>
                              </div>
                            );
                          })}

                          <input
                            ref={otpInputRef}
                            value={otp}
                            onChange={handleOtpChange}
                            maxLength={OTP_CELL_COUNT}
                            inputMode="numeric"
                            autoComplete="one-time-code"
                            style={styles.hiddenInput}
                          />
                        </div>

                        {errors.otp ? (
                          <div style={styles.errorContainer}>
                            <AlertIcon />
                            <div style={styles.errorText}>{errors.otp}</div>
                          </div>
                        ) : null}

                        <div style={{ ...styles.resendWrap, marginTop: layout.sectionGap - 2 }}>
                          <span style={styles.resendText}>Didn&apos;t receive OTP?</span>
                          <button
                            type="button"
                            onClick={resendOtp}
                            disabled={countdown > 0 || loading}
                            style={{
                              ...styles.resendButton,
                              color: countdown > 0 ? colors.textGray : colors.primary,
                            }}
                          >
                            {countdown > 0 ? `Resend in ${countdown}s` : "Resend"}
                          </button>
                        </div>

                        <button
                          type="button"
                          onClick={() => verifyOtp()}
                          disabled={!isValidOtp || loading}
                          style={{
                            ...styles.primaryButton,
                            minHeight: layout.buttonHeight,
                            marginTop: layout.sectionGap + 2,
                            opacity: !isValidOtp || loading ? 0.5 : 1,
                          }}
                        >
                          {loading ? <Spinner color="#fff" /> : "Verify & Continue"}
                        </button>

                        <div
                          style={{
                            ...styles.securityBadge,
                            marginTop: layout.securityMarginTop,
                          }}
                        >
                          <ShieldIcon />
                          <div style={styles.securityText}>Secure & encrypted connection</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {showLocationModal ? (
          <div style={styles.modalOverlay}>
            <div style={styles.modalCard}>
              <div style={styles.locationIconWrap}>
                <LocationIcon />
              </div>
              <div style={styles.modalTitle}>{locationModalContent.title}</div>
              <div style={styles.modalMessage}>{locationModalContent.message}</div>

              {locationModalError ? (
                <div style={styles.modalError}>{locationModalError}</div>
              ) : null}

              <div style={{ marginTop: 16, display: "grid", gap: 10 }}>
                <button
                  type="button"
                  onClick={handleLocationPrimaryAction}
                  disabled={locationModalState === "requesting"}
                  style={{
                    ...styles.modalPrimaryBtn,
                    opacity: locationModalState === "requesting" ? 0.7 : 1,
                  }}
                >
                  {locationModalState === "requesting" ? (
                    <Spinner color="#fff" />
                  ) : (
                    locationModalContent.primaryLabel
                  )}
                </button>

                <button
                  type="button"
                  onClick={closeLocationModal}
                  disabled={locationModalState === "requesting"}
                  style={styles.modalSecondaryBtn}
                >
                  {locationModalContent.secondaryLabel}
                </button>
              </div>
            </div>
          </div>
        ) : null}
      </div>
    </>
  );
}

const styles = {
  page: {
    minHeight: "100dvh",
    width: "100%",
    background: `linear-gradient(180deg, ${colors.pageTop} 0%, ${colors.pageMiddle} 45%, ${colors.pageBottom} 100%)`,
    position: "relative",
    overflow: "hidden",
    fontFamily:
      'Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
  },
  scrollArea: {
    minHeight: "100dvh",
    display: "flex",
    justifyContent: "center",
    boxSizing: "border-box",
    position: "relative",
    zIndex: 2,
  },
  contentWrap: {
    width: "100%",
    maxWidth: 430,
    display: "flex",
    flexDirection: "column",
  },
  logoRow: {
    width: "100%",
    display: "flex",
    justifyContent: "flex-start",
  },
  logoPill: {
    background: "rgba(255,255,255,0.5)",
    borderRadius: 18,
    padding: "8px 12px",
    display: "inline-flex",
  },
  heroSection: {
    width: "100%",
    display: "flex",
    flexDirection: "column",
    alignItems: "center",
  },
  formCard: {
    background: "#fff",
    width: "100%",
    boxSizing: "border-box",
  },
  welcomeText: {
    color: colors.black,
    textAlign: "center",
    fontWeight: 800,
    letterSpacing: -0.4,
  },
  subtitleText: {
    color: colors.mediumGray,
    textAlign: "center",
    fontWeight: 500,
  },
  metaRow: {
    display: "flex",
    alignItems: "center",
  },
  metaLine: {
    flex: 1,
    height: 1,
    background: colors.borderGray,
  },
  metaText: {
    color: colors.textGray,
    textAlign: "center",
    fontWeight: 600,
  },
  phoneField: {
    display: "flex",
    alignItems: "center",
    background: "#fff",
    borderRadius: 10,
    borderWidth: 1,
    borderStyle: "solid",
    padding: "0 10px",
    boxSizing: "border-box",
  },
  countryChip: {
    display: "flex",
    alignItems: "center",
    gap: 4,
    padding: "9px 10px",
    background: colors.surface,
    borderRadius: 9,
  },
  countryChipText: {
    color: colors.darkGray,
    fontWeight: 700,
    fontSize: 12,
  },
  inputDivider: {
    width: 1,
    height: "58%",
    background: colors.borderGray,
    margin: "0 10px",
  },
  callingCodeText: {
    color: colors.darkGray,
    fontWeight: 700,
  },
  phoneInput: {
    flex: 1,
    border: "none",
    outline: "none",
    color: colors.black,
    fontWeight: 600,
    padding: "0 10px",
    background: "transparent",
  },
  errorContainer: {
    display: "flex",
    alignItems: "center",
    gap: 6,
    marginTop: 6,
  },
  errorText: {
    fontSize: 12,
    color: colors.error,
    fontWeight: 500,
  },
  primaryButton: {
    width: "100%",
    border: "none",
    borderRadius: 8,
    background: colors.primary,
    color: "#fff",
    fontWeight: 800,
    fontSize: 15,
    letterSpacing: 0.2,
    cursor: "pointer",
    boxShadow: "0 8px 16px rgba(37,85,245,0.18)",
  },
  securityBadge: {
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    gap: 6,
    marginTop: 14,
  },
  securityText: {
    fontSize: 10,
    color: colors.mediumGray,
    fontWeight: 600,
  },
  phonePreviewText: {
    textAlign: "center",
    color: colors.darkGray,
    fontWeight: 700,
    marginTop: 6,
  },
  changeNumberButton: {
    alignSelf: "center",
    marginTop: 6,
    border: "none",
    background: "transparent",
    color: colors.primary,
    fontSize: 12,
    fontWeight: 700,
    cursor: "pointer",
  },
  whatsappBadge: {
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    background: colors.whatsappMuted,
    borderRadius: 8,
    border: `1px solid ${colors.whatsappBorder}`,
    padding: "10px 14px",
    gap: 8,
  },
  whatsappBadgeText: {
    flex: 1,
    textAlign: "center",
    color: "#12753E",
    fontSize: 13,
    fontWeight: 700,
  },
  otpRow: {
    display: "flex",
    justifyContent: "center",
    alignItems: "center",
    position: "relative",
    cursor: "text",
  },
  otpCell: {
    borderRadius: 10,
    borderWidth: 1,
    borderStyle: "solid",
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    boxSizing: "border-box",
  },
  otpDigit: {
    color: colors.black,
    fontWeight: 700,
  },
  hiddenInput: {
    position: "absolute",
    opacity: 0.001,
    pointerEvents: "none",
    width: 1,
    height: 1,
  },
  resendWrap: {
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    gap: 6,
  },
  resendText: {
    fontSize: 12,
    color: colors.mediumGray,
  },
  resendButton: {
    fontSize: 12,
    fontWeight: 700,
    border: "none",
    background: "transparent",
    cursor: "pointer",
  },
  decorCircle: {
    position: "absolute",
    borderRadius: 999,
    opacity: 0.14,
    zIndex: 1,
  },
  decorTop: {
    background: colors.decorBlue,
  },
  decorBottom: {
    background: colors.decorPink,
  },
  modalOverlay: {
    position: "fixed",
    inset: 0,
    background: "rgba(17,24,39,0.55)",
    display: "flex",
    justifyContent: "center",
    alignItems: "center",
    padding: 16,
    zIndex: 99,
  },
  modalCard: {
    width: "100%",
    maxWidth: 390,
    background: "#fff",
    borderRadius: 20,
    padding: 20,
    boxShadow: "0 10px 30px rgba(37,99,235,0.12)",
  },
  locationIconWrap: {
    width: 56,
    height: 56,
    borderRadius: 28,
    background: colors.lightGray,
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    margin: "0 auto 12px",
  },
  modalTitle: {
    fontSize: 16,
    fontWeight: 700,
    color: colors.black,
    textAlign: "center",
    marginBottom: 8,
  },
  modalMessage: {
    fontSize: 12,
    color: colors.mediumGray,
    textAlign: "center",
    lineHeight: 1.5,
  },
  modalError: {
    fontSize: 10,
    color: colors.error,
    textAlign: "center",
    marginTop: 8,
  },
  modalPrimaryBtn: {
    width: "100%",
    border: "none",
    background: colors.primary,
    color: "#fff",
    borderRadius: 12,
    padding: "12px 14px",
    fontSize: 14,
    fontWeight: 700,
    cursor: "pointer",
  },
  modalSecondaryBtn: {
    width: "100%",
    border: `1px solid ${colors.borderGray}`,
    background: colors.lightGray,
    color: colors.mediumGray,
    borderRadius: 12,
    padding: "11px 14px",
    fontSize: 14,
    fontWeight: 700,
    cursor: "pointer",
  },
  loaderWrap: {
    minHeight: "100dvh",
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    flexDirection: "column",
    gap: 12,
    background: "#fff",
  },
  loaderText: {
    fontSize: 14,
    color: colors.darkGray,
    fontWeight: 600,
  },
};

const globalCss = `
  * { box-sizing: border-box; }
  html, body, #root { margin: 0; padding: 0; min-height: 100%; }
  button, input { font-family: inherit; }
  button:disabled { cursor: not-allowed; }
  @keyframes spin { to { transform: rotate(360deg); } }
`;
