import { useCallback, useEffect, useRef, useState } from "react";
import {
  EMPTY_PENDING_PRESCRIPTION,
  clearDoctorPendingPrescription,
  getDoctorPendingPrescription,
  setDoctorPendingPrescription,
  subscribeToDoctorPendingPrescription,
} from "./doctorPendingPrescriptionService";
import { useNewDoctorAuth } from "./NewDoctorAuth";

const PENDING_PRESCRIPTION_STATUS_URL =
  "https://snoutiq.com/backend/api/doctor/pending-prescription";

const normalizeStatusText = (value) => String(value ?? "").trim().toLowerCase();

const isTruthyFlag = (value) => {
  if (typeof value === "boolean") return value;
  if (typeof value === "number") return value === 1;

  const normalized = normalizeStatusText(value);
  return normalized === "1" || normalized === "true" || normalized === "yes";
};

export function useDoctorPendingPrescription({
  doctorId,
  enabled = true,
} = {}) {
  const { auth } = useNewDoctorAuth();
  const [pendingPrescription, setPendingPrescription] = useState(
    EMPTY_PENDING_PRESCRIPTION,
  );
  const canTrackPendingPrescription = enabled && Boolean(doctorId);
  const authToken = auth?.token || auth?.access_token || "";
  const refreshSequenceRef = useRef(0);
  const isMountedRef = useRef(true);

  useEffect(() => {
    return () => {
      isMountedRef.current = false;
      refreshSequenceRef.current += 1;
    };
  }, []);

  const refresh = useCallback(async () => {
    const refreshSequence = refreshSequenceRef.current + 1;
    refreshSequenceRef.current = refreshSequence;

    if (!canTrackPendingPrescription) {
      if (isMountedRef.current) {
        setPendingPrescription(EMPTY_PENDING_PRESCRIPTION);
      }
      return EMPTY_PENDING_PRESCRIPTION;
    }

    const localPendingPrescription = getDoctorPendingPrescription(doctorId);

    if (
      !localPendingPrescription.hasPending ||
      !localPendingPrescription.userId
    ) {
      if (
        isMountedRef.current &&
        refreshSequence === refreshSequenceRef.current
      ) {
        setPendingPrescription(localPendingPrescription);
      }
      return localPendingPrescription;
    }

    if (
      isMountedRef.current &&
      refreshSequence === refreshSequenceRef.current
    ) {
      setPendingPrescription(localPendingPrescription);
    }

    try {
      const url = new URL(PENDING_PRESCRIPTION_STATUS_URL);
      url.searchParams.set("user_id", localPendingPrescription.userId);

      const response = await fetch(url.toString(), {
        headers: {
          Accept: "application/json",
          ...(authToken
            ? {
                Authorization: `Bearer ${authToken}`,
              }
            : {}),
        },
      });

      if (!response.ok) {
        throw new Error("Failed to fetch pending prescription status.");
      }

      const responseData = await response.json().catch(() => ({}));
      const statusPayload =
        responseData?.data && typeof responseData.data === "object"
          ? responseData.data
          : responseData;
      const hasStatusPayload =
        statusPayload &&
        typeof statusPayload === "object" &&
        ("payment_status" in statusPayload ||
          "prescription_status" in statusPayload ||
          "prescription_required" in statusPayload ||
          "lock_until_submit" in statusPayload);

      if (
        refreshSequence !== refreshSequenceRef.current ||
        !isMountedRef.current
      ) {
        return localPendingPrescription;
      }

      if (!hasStatusPayload) {
        setPendingPrescription(localPendingPrescription);
        return localPendingPrescription;
      }

      const latestLocalPendingPrescription =
        getDoctorPendingPrescription(doctorId);
      if (
        !latestLocalPendingPrescription.hasPending ||
        latestLocalPendingPrescription.userId !== localPendingPrescription.userId
      ) {
        setPendingPrescription(latestLocalPendingPrescription);
        return latestLocalPendingPrescription;
      }

      const paymentStatus = String(statusPayload.payment_status ?? "").trim();
      const prescriptionStatus = String(
        statusPayload.prescription_status ?? "",
      ).trim();
      const prescriptionRequired = isTruthyFlag(
        statusPayload.prescription_required,
      );
      const lockUntilSubmit = isTruthyFlag(statusPayload.lock_until_submit);
      const isLocked =
        normalizeStatusText(paymentStatus) === "paid" &&
        prescriptionRequired &&
        normalizeStatusText(prescriptionStatus) === "pending" &&
        lockUntilSubmit;
      const isSubmittedAndUnlocked =
        normalizeStatusText(paymentStatus) === "paid" &&
        prescriptionRequired &&
        normalizeStatusText(prescriptionStatus) === "submitted" &&
        !lockUntilSubmit;

      if (isLocked) {
        const nextPendingPrescription = setDoctorPendingPrescription(doctorId, {
          ...latestLocalPendingPrescription,
          paymentStatus,
          prescriptionRequired,
          prescriptionStatus,
          lockUntilSubmit: true,
          hasPending: true,
        });

        if (
          isMountedRef.current &&
          refreshSequence === refreshSequenceRef.current
        ) {
          setPendingPrescription(nextPendingPrescription);
        }

        return nextPendingPrescription;
      }

      if (isSubmittedAndUnlocked) {
        clearDoctorPendingPrescription(doctorId);

        if (
          isMountedRef.current &&
          refreshSequence === refreshSequenceRef.current
        ) {
          setPendingPrescription(EMPTY_PENDING_PRESCRIPTION);
        }

        return EMPTY_PENDING_PRESCRIPTION;
      }

      const nextPendingPrescription = setDoctorPendingPrescription(doctorId, {
        ...latestLocalPendingPrescription,
        paymentStatus,
        prescriptionRequired,
        prescriptionStatus,
        lockUntilSubmit: false,
        hasPending: true,
      });

      if (
        isMountedRef.current &&
        refreshSequence === refreshSequenceRef.current
      ) {
        setPendingPrescription(nextPendingPrescription);
      }

      return nextPendingPrescription;
    } catch {
      if (
        isMountedRef.current &&
        refreshSequence === refreshSequenceRef.current
      ) {
        setPendingPrescription(localPendingPrescription);
      }

      return localPendingPrescription;
    }
  }, [authToken, canTrackPendingPrescription, doctorId]);

  useEffect(() => {
    refresh();
    if (!canTrackPendingPrescription) {
      return undefined;
    }

    const unsubscribe = subscribeToDoctorPendingPrescription(doctorId, refresh);

    return () => {
      unsubscribe();
    };
  }, [canTrackPendingPrescription, doctorId, refresh]);

  return {
    pendingPrescription,
    refresh,
  };
}

export default useDoctorPendingPrescription;
