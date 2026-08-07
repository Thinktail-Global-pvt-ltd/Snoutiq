import React, { useState } from "react";
import { GoogleOAuthProvider, GoogleLogin } from "@react-oauth/google";
import axios from "axios";
import { buildAiUserData, persistAiAuthState } from "../ai/AiAuth";
import logo from '../assets/images/logo.png';

const API_CONFIG = {
  baseURL: "https://www.snoutiq.com/backend/api",
  endpoints: { googleLogin: "/google-store-user" },
  timeout: 15000,
};

const apiClient = axios.create({
  baseURL: API_CONFIG.baseURL,
  timeout: API_CONFIG.timeout,
  headers: { "Content-Type": "application/json" },
});

const decodeJwt = (token) => {
  try {
    const base64Url = token.split(".")[1];
    const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
    const jsonPayload = decodeURIComponent(
      atob(base64)
        .split("")
        .map((c) => "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2))
        .join("")
    );
    return JSON.parse(jsonPayload);
  } catch (error) {
    console.error("Failed to decode JWT token", error);
    return null;
  }
};

export default function GoogleAuthModal({ onLoginSuccess }) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const processGoogleLogin = async (googleToken) => {
    setLoading(true);
    setError("");
    try {
      const decoded = decodeJwt(googleToken) || {};
      const payload = {
        email: decoded.email || "",
        name: decoded.name || "",
        google_token: decoded.sub || googleToken,
      };

      const response = await apiClient.post(API_CONFIG.endpoints.googleLogin, payload);
      const responseData = response.data || {};
      console.log("🔑 Google Login API Response:", responseData);
      const userData = responseData.user || responseData.data?.user || null;
      const pets = responseData.pets || responseData.data?.pets || [];
      const primaryPet = Array.isArray(pets) && pets.length ? pets[0] : null;
      const latestChat = responseData.latest_chat || responseData.data?.latest_chat || null;
      const latestCallSession = responseData.latest_call_session || responseData.data?.latest_call_session || null;

      const rawUserId = responseData.user_id || responseData.data?.user_id || userData?.user_id || userData?.id || null;

      if (!userData && !rawUserId) {
        setError("Google Login failed. Please try again.");
        setLoading(false);
        return;
      }

      let authToken = responseData.token || responseData.jwt || responseData.access_token || responseData.data?.token || null;
      if (!authToken) {
        authToken = `user_google_${rawUserId}`;
      }

      const mergedPetData = primaryPet ? {
        pet_name: primaryPet.name || userData?.pet_name,
        pet_gender: primaryPet.pet_gender || userData?.pet_gender,
        breed: primaryPet.breed || userData?.breed,
        pet_age: primaryPet.pet_age ?? userData?.pet_age,
        pet_doc1: primaryPet.pet_doc1 || userData?.pet_doc1,
        pet_doc2: primaryPet.pet_doc2 || userData?.pet_doc2,
      } : {};

      const finalUserData = buildAiUserData({
        ...(userData || {}),
        ...mergedPetData,
        id: rawUserId,
        user_id: rawUserId,
        google_token: payload.google_token,
        email: payload.email || userData?.email || "",
        phone: userData?.phone || userData?.mobileNumber || "",
        pets,
        latest_chat: latestChat || userData?.latest_chat,
        latest_call_session: latestCallSession || userData?.latest_call_session || userData?.latestCallSession || null,
        chat_room_token: latestChat?.chat_room_token || latestChat?.context_token || userData?.chat_room_token,
      }, { latestChat, latestCallSession });

      persistAiAuthState({
        user: finalUserData,
        token: authToken,
        latestChat,
        latestCallSession,
      });

      console.log("👤 Saved User Data (Google):", finalUserData);
      if (typeof onLoginSuccess === "function") {
        await onLoginSuccess(finalUserData, authToken);
      }
    } catch (err) {
      setError(err?.response?.data?.message || err.message || "An unexpected error occurred");
    } finally {
      setLoading(false);
    }
  };

  const handleGoogleSuccess = async (credentialResponse) => {
    await processGoogleLogin(credentialResponse.credential);
  };

  return (
    <div className="flex flex-col items-center justify-center p-8 bg-white text-center rounded-2xl">
      <img src={logo} alt="SnoutIQ" className="h-5 mb-6 object-contain" />
      <h2 className="text-2xl font-bold text-slate-900 mb-2">Welcome to SnoutIQ</h2>
      <p className="text-slate-500 mb-8 text-sm">Sign in with Google to talk to our AI Vet Assistant</p>
      
      {error && (
        <div className="mb-4 text-red-500 text-sm bg-red-50 p-3 rounded-lg w-full">
          {error}
        </div>
      )}

      {loading ? (
        <div className="py-4">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-sm text-slate-500">Signing you in...</p>
        </div>
      ) : (
        <GoogleOAuthProvider clientId="325007826401-dhsrqhkpoeeei12gep3g1sneeg5880o7.apps.googleusercontent.com">
          <GoogleLogin
            onSuccess={handleGoogleSuccess}
            onError={() => setError("Google Login Failed")}
            useOneTap
            shape="rectangular"
            size="large"
            theme="outline"
          />
        </GoogleOAuthProvider>
      )}
      
      <p className="mt-8 text-xs text-slate-400">
        Secure & encrypted connection
      </p>
    </div>
  );
}
