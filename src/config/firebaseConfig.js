import { getApp, getApps, initializeApp } from "firebase/app";

const fallbackConfig = {
  apiKey: "AIzaSyDBTE0IA1xtFdtnMmM-EX-o0LWdNGV5F4g",
  authDomain: "snoutiqapp.firebaseapp.com",
  databaseURL: "https://snoutiqapp-default-rtdb.firebaseio.com",
  projectId: "snoutiqapp",
  storageBucket: "snoutiqapp.firebasestorage.app",
  messagingSenderId: "325007826401",
  appId: "1:325007826401:android:0f9d3cf46b8c32f21786a0",
  vapidKey:
    "BC4AxUdqZfC1OWofgs2e-NBlILpHdv4X0m-sd8Rwg8mDPTqdbCKW8MYpMpmUtKV1YG9tcfKpqwJiGFQPO2g1DDo",
};

const firebaseConfig = {
  apiKey: import.meta?.env?.VITE_FIREBASE_API_KEY ?? fallbackConfig.apiKey,
  authDomain:
    import.meta?.env?.VITE_FIREBASE_AUTH_DOMAIN ?? fallbackConfig.authDomain,
  databaseURL:
    import.meta?.env?.VITE_FIREBASE_DB_URL ?? fallbackConfig.databaseURL,
  projectId:
    import.meta?.env?.VITE_FIREBASE_PROJECT_ID ?? fallbackConfig.projectId,
  storageBucket:
    import.meta?.env?.VITE_FIREBASE_STORAGE_BUCKET ??
    fallbackConfig.storageBucket,
  messagingSenderId:
    import.meta?.env?.VITE_FIREBASE_MESSAGING_SENDER_ID ??
    fallbackConfig.messagingSenderId,
  appId: import.meta?.env?.VITE_FIREBASE_APP_ID ?? fallbackConfig.appId,
  vapidKey:
    import.meta?.env?.VITE_FIREBASE_VAPID_KEY ?? fallbackConfig.vapidKey,
};

let firebaseApp;

export const getFirebaseApp = () => {
  if (firebaseApp) {
    return firebaseApp;
  }

  firebaseApp = getApps().length ? getApp() : initializeApp(firebaseConfig);
  return firebaseApp;
};

export { firebaseConfig };
