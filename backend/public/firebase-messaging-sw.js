// Firebase Messaging service worker (compat version) – replace config placeholders
/* global firebase */
importScripts('https://www.gstatic.com/firebasejs/10.12.2/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.2/firebase-messaging-compat.js');

// You must keep this in sync with the page config or hardcode your config here.
// For local dev you can paste the same config you used in fcm-test.html.
const firebaseConfig = {
  apiKey: 'AIzaSyDBTE0IA1xtFdtnMmM-EX-o0LWdNGV5F4g',
  authDomain: 'snoutiqapp.firebaseapp.com',
  databaseURL: 'https://snoutiqapp-default-rtdb.firebaseio.com',
  projectId: 'snoutiqapp',
  storageBucket: 'snoutiqapp.firebasestorage.app',
  messagingSenderId: '325007826401',
  appId: '1:325007826401:android:0f9d3cf46b8c32f21786a0',
};
const VAPID_KEY = 'BC4AxUdqZfC1OWofgs2e-NBlILpHdv4X0m-sd8Rwg8mDPTqdbCKW8MYpMpmUtKV1YG9tcfKpqwJiGFQPO2g1DDo';

try {
  firebase.initializeApp(firebaseConfig);
  const messaging = firebase.messaging();

  // Handle background messages (optional display)
  messaging.onBackgroundMessage((payload) => {
    const title = payload.notification?.title || 'Snoutiq';
    const body = payload.notification?.body || 'New message';
    self.registration.showNotification(title, { body, data: payload.data || {} });
  });
} catch (e) {
  // Intentionally silent: devs will see errors in DevTools
}
