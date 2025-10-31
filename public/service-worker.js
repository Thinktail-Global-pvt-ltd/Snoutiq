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
        callId: call.session_id || call.call_id || call.callId || null,
        call,
      },
      actions: [
        { action: 'answer', title: 'Answer' },
        { action: 'decline', title: 'Decline' },
      ],
    };

    await cacheRingtone(call.ringtone_url);
    await broadcastIncomingCall(call);
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
  const data = event.notification.data || {};
  const rawAction = event.action || 'default';
  const action = normaliseAction(rawAction);
  event.notification.close();

  event.waitUntil(
    (async () => {
      const message = {
        type: 'snoutiq-notification',
        action,
        call: data.call || null,
        url: resolveAbsoluteUrl(data.callUrl || data.url || null),
        callId: data.callId || data.sessionId || data.call?.call_id || data.call?.callId || null,
      };

      const windows = await clients.matchAll({ type: 'window', includeUncontrolled: true });
      let delivered = false;

      for (const client of windows) {
        try {
          client.postMessage(message);
          if (action !== 'dismiss' && typeof client.focus === 'function') {
            await client.focus();
          }
          delivered = true;
        } catch (error) {
          console.warn('Service worker notification postMessage failed', error);
        }
      }

      if (!delivered && message.url && action !== 'dismiss') {
        try {
          await clients.openWindow(message.url);
        } catch (error) {
          console.warn('Service worker notification openWindow failed', error);
        }
      }
    })()
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

async function broadcastIncomingCall(call) {
  try {
    const message = {
      type: 'snoutiq-incoming-call',
      call: call || null,
      sentAt: new Date().toISOString(),
      ringtoneUrl: call?.ringtone_url || null,
    };
    const windows = await clients.matchAll({ type: 'window', includeUncontrolled: true });
    for (const client of windows) {
      try {
        client.postMessage(message);
      } catch (error) {
        console.warn('Service worker push broadcast failed', error);
      }
    }
  } catch (error) {
    console.warn('Service worker push broadcast error', error);
  }
}

function normaliseAction(action) {
  switch (action) {
    case 'answer':
      return 'accept';
    case 'decline':
      return 'dismiss';
    case 'default':
    default:
      return action || 'default';
  }
}

function resolveAbsoluteUrl(url) {
  if (!url) return null;
  try {
    return new URL(url, self.location.origin).toString();
  } catch (_error) {
    return null;
  }
}
