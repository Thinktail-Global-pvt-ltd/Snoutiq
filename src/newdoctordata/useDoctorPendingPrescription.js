import { useCallback, useEffect, useState } from "react";
import {
  EMPTY_PENDING_PRESCRIPTION,
  getDoctorPendingPrescription,
  subscribeToDoctorPendingPrescription,
} from "./doctorPendingPrescriptionService";

const DEFAULT_POLL_INTERVAL = 3000;

export function useDoctorPendingPrescription({
  doctorId,
  enabled = true,
  pollInterval = DEFAULT_POLL_INTERVAL,
} = {}) {
  const [pendingPrescription, setPendingPrescription] = useState(
    EMPTY_PENDING_PRESCRIPTION,
  );

  const refresh = useCallback(() => {
    if (!enabled) {
      setPendingPrescription(EMPTY_PENDING_PRESCRIPTION);
      return;
    }

    setPendingPrescription(getDoctorPendingPrescription(doctorId));
  }, [doctorId, enabled]);

  useEffect(() => {
    refresh();
  }, [refresh]);

  useEffect(() => {
    if (!enabled) {
      return undefined;
    }

    refresh();

    const intervalId = window.setInterval(refresh, pollInterval);
    const unsubscribe = subscribeToDoctorPendingPrescription(
      doctorId,
      refresh,
    );

    return () => {
      window.clearInterval(intervalId);
      unsubscribe();
    };
  }, [doctorId, enabled, pollInterval, refresh]);

  return {
    pendingPrescription,
    refresh,
  };
}

export default useDoctorPendingPrescription;
