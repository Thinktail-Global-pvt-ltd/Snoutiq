import React from "react";
import { Navigate, useLocation } from "react-router-dom";
import { readAiAuthState } from "./AiAuth";

const normalizeText = (value) => String(value ?? "").trim();

function hasPetContext(user, routeState = {}) {
  if (normalizeText(routeState.petId || routeState.pet_id)) return true;
  if (!user || typeof user !== "object") return false;
  if (normalizeText(user.pet_id)) return true;
  if (user.pet && typeof user.pet === "object") {
    return Boolean(normalizeText(user.pet.id || user.pet.pet_id || user.pet.name || user.pet.pet_name));
  }
  return Array.isArray(user.pets) && user.pets.length > 0;
}

export default function ProtectedBookingRoute({ children }) {
  const location = useLocation();
  const authState = readAiAuthState();
  const user = authState?.user || {};
  const routeState = location.state || {};
  const userId = normalizeText(user.id || user.user_id);
  const token = normalizeText(authState?.token);

  if (!token || !userId || !hasPetContext(user, routeState)) {
    return (
      <Navigate
        replace
        to="/ai"
        state={{
          returnTo: `${location.pathname}${location.search || ""}${location.hash || ""}`,
          bookingAccessRequired: true,
        }}
      />
    );
  }

  return children;
}
