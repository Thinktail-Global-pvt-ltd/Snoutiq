import React, { createContext, useContext, useEffect, useMemo, useState } from "react";
import {
  BOOKING_FLOW_STORAGE_KEY,
  DEFAULT_BOOKING_STATE,
} from "./bookingFlowData";

const VetNearMeBookingContext = createContext(null);

const mergeBookingState = (storedState = {}) => {
  const mergedBooking = {
    ...DEFAULT_BOOKING_STATE.booking,
    ...(storedState.booking || {}),
  };
  const mergedProgress = {
    ...DEFAULT_BOOKING_STATE.progress,
    ...(storedState.progress || {}),
  };
  const latestCompletedStep = Number(mergedBooking.latestCompletedStep || 0);

  return {
    lead: {
      ...DEFAULT_BOOKING_STATE.lead,
      ...(storedState.lead || {}),
    },
    pet: {
      ...DEFAULT_BOOKING_STATE.pet,
      ...(storedState.pet || {}),
      symptoms: Array.isArray(storedState.pet?.symptoms)
        ? storedState.pet.symptoms
        : DEFAULT_BOOKING_STATE.pet.symptoms,
    },
    booking: mergedBooking,
    progress: {
      ...mergedProgress,
      leadSubmitted:
        mergedProgress.leadSubmitted ||
        Boolean(mergedBooking.bookingId) ||
        latestCompletedStep >= 1,
      petDetailsSubmitted:
        mergedProgress.petDetailsSubmitted || latestCompletedStep >= 2,
      paymentCompleted:
        mergedProgress.paymentCompleted || latestCompletedStep >= 3,
    },
  };
};

const readStoredBookingState = () => {
  if (typeof window === "undefined") return DEFAULT_BOOKING_STATE;

  try {
    const rawState = window.sessionStorage.getItem(BOOKING_FLOW_STORAGE_KEY);
    if (!rawState) return DEFAULT_BOOKING_STATE;
    return mergeBookingState(JSON.parse(rawState));
  } catch {
    return DEFAULT_BOOKING_STATE;
  }
};

export function VetNearMeBookingProvider({ children }) {
  const [bookingState, setBookingState] = useState(readStoredBookingState);

  useEffect(() => {
    if (typeof window === "undefined") return;

    window.sessionStorage.setItem(
      BOOKING_FLOW_STORAGE_KEY,
      JSON.stringify(bookingState)
    );
  }, [bookingState]);

  const updateLead = (updates) => {
    setBookingState((currentState) => ({
      ...currentState,
      lead: {
        ...currentState.lead,
        ...updates,
      },
    }));
  };

  const updatePet = (updates) => {
    setBookingState((currentState) => ({
      ...currentState,
      pet: {
        ...currentState.pet,
        ...updates,
      },
    }));
  };

  const toggleSymptom = (symptomValue) => {
    setBookingState((currentState) => {
      const selectedSymptoms = currentState.pet.symptoms.includes(symptomValue)
        ? currentState.pet.symptoms.filter((value) => value !== symptomValue)
        : [...currentState.pet.symptoms, symptomValue];

      return {
        ...currentState,
        pet: {
          ...currentState.pet,
          symptoms: selectedSymptoms,
        },
      };
    });
  };

  const updateProgress = (updates) => {
    setBookingState((currentState) => ({
      ...currentState,
      progress: {
        ...currentState.progress,
        ...updates,
      },
    }));
  };

  const updateBooking = (updates) => {
    setBookingState((currentState) => ({
      ...currentState,
      booking: {
        ...currentState.booking,
        ...updates,
      },
    }));
  };

  const resetBookingFlow = () => {
    setBookingState(DEFAULT_BOOKING_STATE);
  };

  const value = useMemo(
    () => ({
      bookingState,
      updateLead,
      updatePet,
      toggleSymptom,
      updateProgress,
      updateBooking,
      resetBookingFlow,
    }),
    [bookingState]
  );

  return (
    <VetNearMeBookingContext.Provider value={value}>
      {children}
    </VetNearMeBookingContext.Provider>
  );
}

export function useVetNearMeBooking() {
  const context = useContext(VetNearMeBookingContext);

  if (!context) {
    throw new Error(
      "useVetNearMeBooking must be used inside VetNearMeBookingProvider."
    );
  }

  return context;
}
