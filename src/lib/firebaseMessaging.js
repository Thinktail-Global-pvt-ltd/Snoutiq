import { getMessaging, getToken, isSupported, onMessage } from "firebase/messaging";
import { getFirebaseApp, firebaseConfig } from "../../firebaseConfig";

let messagingSetupPromise = null;

const registerMessagingServiceWorker = async () => {
  if (typeof window === "undefined" || !("serviceWorker" in navigator)) {
    return null;
  }

  try {
    return await navigator.serviceWorker.register("/firebase-messaging-sw.js");
  } catch (error) {
    console.error("[FCM] Service worker registration failed:", error);
    return null;
  }
};

const ensureMessagingReady = async () => {
  if (typeof window === "undefined") {
    return null;
  }

  const supported = await isSupported().catch(() => false);
  if (!supported) {
    console.warn("[FCM] Messaging is not supported in this environment");
    return null;
  }

  if (!messagingSetupPromise) {
    messagingSetupPromise = (async () => {
      const app = getFirebaseApp();
      const messaging = getMessaging(app);
      const registration = await registerMessagingServiceWorker();
      return { messaging, registration };
    })();
  }

  return messagingSetupPromise;
};

const ensureNotificationPermission = async () => {
  if (typeof window === "undefined" || !("Notification" in window)) {
    throw new Error("Notifications are not supported in this browser");
  }

  if (Notification.permission === "granted") {
    return true;
  }

  if (Notification.permission === "denied") {
    throw new Error(
      "Notifications are blocked. Enable them in your browser settings to receive alerts."
    );
  }

  const permission = await Notification.requestPermission();
  if (permission !== "granted") {
    throw new Error("Notification permission was not granted");
  }

  return true;
};

export const requestFcmToken = async () => {
  await ensureNotificationPermission();

  const setup = await ensureMessagingReady();
  if (!setup) {
    throw new Error("Firebase messaging is not supported on this device");
  }

  const { messaging, registration } = setup;

  const token = await getToken(messaging, {
    vapidKey: firebaseConfig.vapidKey,
    serviceWorkerRegistration: registration ?? undefined,
  });

  if (!token) {
    throw new Error("Firebase did not return a registration token");
  }

  return token;
};

export const subscribeToForegroundMessages = async (callback) => {
  const setup = await ensureMessagingReady();
  if (!setup) {
    return () => {};
  }

  return onMessage(setup.messaging, callback);
};
