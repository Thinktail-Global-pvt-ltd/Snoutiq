import { useCallback, useEffect, useRef } from "react";
import { socket } from "../pages/socket";

const HEARTBEAT_EVENT = "doctor-heartbeat";
const HEARTBEAT_INTERVAL_MS = 25_000;
const HEARTBEAT_GRACE_MS = 5 * 60 * 1000;

const isDocumentHidden = () => {
  if (typeof document === "undefined") {
    return false;
  }
  return document.hidden;
};

export default function useDoctorHeartbeat(doctorId, options = {}) {
  const latestDoctorIdRef = useRef(doctorId);
  const heartbeatTimerRef = useRef(null);
  const visibilityRef = useRef(!isDocumentHidden());

  const clearHeartbeat = useCallback(() => {
    if (heartbeatTimerRef.current) {
      clearInterval(heartbeatTimerRef.current);
      heartbeatTimerRef.current = null;
    }
  }, []);

  const emitHeartbeat = useCallback(
    (immediate = false) => {
      const activeDoctorId = Number(latestDoctorIdRef.current);
      if (!activeDoctorId || Number.isNaN(activeDoctorId)) {
        return;
      }
      if (!socket || !socket.connected) {
        return;
      }

      try {
        socket.emit(HEARTBEAT_EVENT, {
          doctorId: activeDoctorId,
          at: Date.now(),
          immediate: immediate ? 1 : 0,
          visible: visibilityRef.current ? 1 : 0,
          page: options.pageTag || options.source || "doctor-dashboard",
          graceMs: HEARTBEAT_GRACE_MS,
        });
      } catch (error) {
        if (process.env.NODE_ENV !== "production") {
          console.warn("[useDoctorHeartbeat] failed to send heartbeat", error);
        }
        options.onError?.(error);
      }
    },
    [options]
  );

  const startHeartbeat = useCallback(() => {
    if (heartbeatTimerRef.current) {
      return;
    }
    emitHeartbeat(true);
    heartbeatTimerRef.current = setInterval(() => {
      emitHeartbeat(false);
    }, HEARTBEAT_INTERVAL_MS);
  }, [emitHeartbeat]);

  useEffect(() => {
    latestDoctorIdRef.current = doctorId;
    if (!doctorId) {
      clearHeartbeat();
    }
  }, [doctorId, clearHeartbeat]);

  useEffect(() => {
    if (!socket) {
      return undefined;
    }

    const handleConnect = () => {
      if (latestDoctorIdRef.current) {
        startHeartbeat();
      }
    };

    const handleDisconnect = () => {
      clearHeartbeat();
    };

    if (socket.connected && latestDoctorIdRef.current) {
      startHeartbeat();
    }

    socket.on("connect", handleConnect);
    socket.on("disconnect", handleDisconnect);

    return () => {
      socket.off("connect", handleConnect);
      socket.off("disconnect", handleDisconnect);
      clearHeartbeat();
    };
  }, [clearHeartbeat, startHeartbeat]);

  useEffect(() => {
    if (typeof document === "undefined") {
      return undefined;
    }

    const handleVisibilityChange = () => {
      visibilityRef.current = !isDocumentHidden();
      emitHeartbeat(true);
    };

    document.addEventListener("visibilitychange", handleVisibilityChange);
    return () => {
      document.removeEventListener("visibilitychange", handleVisibilityChange);
    };
  }, [emitHeartbeat]);
}
