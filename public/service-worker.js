const CACHE_NAME = 'snoutiq-pwa-cache-v1';
const PRECACHE_URLS = ['/ringtone.mp3'];
const API_BASE_PATH = '/public/api';

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_URLS))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) =>
      Promise.all(
        cacheNames
          .filter((name) => name !== CACHE_NAME)
          .map((name) => caches.delete(name))
      )
    )
  );
  self.clients.claim();
});

self.addEventListener('push', (event) => {
  event.waitUntil(handlePushEvent());
});

async function handlePushEvent() {
  try {
    const registration = await self.registration.pushManager.getSubscription();
    if (!registration) {
      return;
    }

    const response = await fetch(`${API_BASE_PATH}/doctor/push/incoming-call`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ endpoint: registration.endpoint }),
    });

    if (!response.ok) {
      console.warn('Service worker push fetch failed', response.status);
      return;
    }

    const payload = await response.json();
    if (!payload?.call) {
      return;
    }

    const { call } = payload;

    const notificationOptions = {
      body: `${call.patient_name ?? 'Patient'} is calling`,
      icon: '/favicon-192.png',
      badge: '/favicon-192.png',
      vibrate: [200, 100, 200],
      requireInteraction: true,
      tag: `call-${call.session_id}`,
      data: {
        callUrl: call.doctor_join_url,
        sessionId: call.session_id,
        expiresAt: call.expires_at,
        ringtoneUrl: call.ringtone_url,
      },
      actions: [
        { action: 'answer', title: 'Answer' },
        { action: 'decline', title: 'Decline' },
      ],
    };

    await cacheRingtone(call.ringtone_url);
    await self.registration.showNotification('Incoming video consultation', notificationOptions);
  } catch (error) {
    console.error('Service worker push handling error', error);
  }
}

async function cacheRingtone(url) {
  if (!url) return;

  try {
    const cache = await caches.open(CACHE_NAME);
    const cached = await cache.match(url);
    if (!cached) {
      await cache.add(url);
    }
  } catch (error) {
    console.warn('Failed to cache ringtone', error);
  }
}

self.addEventListener('notificationclick', (event) => {
  const action = event.action;
  const { callUrl } = event.notification.data || {};
  event.notification.close();

  if (!callUrl) {
    return;
  }

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        const url = new URL(client.url);
        if (client.focus && callUrl && url.pathname === new URL(callUrl, self.location.origin).pathname) {
          return client.focus();
        }
      }

      const targetUrl = action === 'decline' ? `${callUrl}&action=decline` : callUrl;
      const absoluteUrl = new URL(targetUrl, self.location.origin).toString();
      return clients.openWindow(absoluteUrl);
    })
  );
});

self.addEventListener('pushsubscriptionchange', (event) => {
  event.waitUntil(
    (async () => {
      const applicationServerKey = await getApplicationServerKey();
      if (!applicationServerKey) {
        return;
      }

      const newSubscription = await self.registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey,
      });

      const clientList = await clients.matchAll({ includeUncontrolled: true, type: 'window' });
      for (const client of clientList) {
        client.postMessage({
          type: 'pushsubscriptionchange',
          subscription: newSubscription?.toJSON() ?? null,
        });
      }
    })()
  );
});

async function getApplicationServerKey() {
  try {
    const response = await fetch('/public/api/config/webpush');
    if (!response.ok) return null;
    const data = await response.json();
    if (!data?.publicKey) return null;
    const padding = '='.repeat((4 - (data.publicKey.length % 4)) % 4);
    const base64 = (data.publicKey + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
  } catch (error) {
    console.warn('Failed to refresh application server key', error);
    return null;
  }
}
