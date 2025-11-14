/* global firebase */
importScripts("https://www.gstatic.com/firebasejs/11.0.2/firebase-app-compat.js");
importScripts(
  "https://www.gstatic.com/firebasejs/11.0.2/firebase-messaging-compat.js"
);

const firebaseConfig = {
  apiKey: "AIzaSyDBTE0IA1xtFdtnMmM-EX-o0LWdNGV5F4g",
  authDomain: "snoutiqapp.firebaseapp.com",
  databaseURL: "https://snoutiqapp-default-rtdb.firebaseio.com",
  projectId: "snoutiqapp",
  storageBucket: "snoutiqapp.firebasestorage.app",
  messagingSenderId: "325007826401",
  appId: "1:325007826401:android:0f9d3cf46b8c32f21786a0",
};

try {
  firebase.initializeApp(firebaseConfig);
  const messaging = firebase.messaging();

  messaging.onBackgroundMessage((payload) => {
    const title = payload.notification?.title || "Snoutiq";
    const body = payload.notification?.body || "You have a new alert";

    const notificationOptions = {
      body,
      icon: payload.notification?.icon || "/favicon-192.png",
      data: payload.data || {},
      badge: "/favicon-192.png",
    };

    self.registration.showNotification(title, notificationOptions);
  });
} catch (error) {
  console.error("firebase-messaging-sw init failed", error);
}

self.addEventListener("notificationclick", (event) => {
  event.notification.close();
  const deepLink = event.notification?.data?.deepLink;

  if (deepLink) {
    event.waitUntil(clients.openWindow(deepLink));
    return;
  }

  event.waitUntil(clients.openWindow("/doctor-dashboard"));
});
