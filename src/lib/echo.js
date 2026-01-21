import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const isBrowser = typeof window !== 'undefined';
const isHttps = isBrowser ? window.location.protocol === 'https:' : false;

const REVERB_CONFIG = {
  // IMPORTANT: remove "base64:"
  key: 'yT9RzP3vXl9lJ2pB2g==',

  host: isBrowser ? window.location.hostname : 'localhost',
  scheme: isHttps ? 'https' : 'http',

  // IMPORTANT: keep wsPath aligned with backend prefix
  path: '/backend/app',

  // if you’re not using auth yet, remove authEndpoint entirely
  authEndpoint: '/backend/broadcasting/auth',
};

export function createEcho() {
  const forceTLS = isHttps;

  const echo = new Echo({
    broadcaster: 'reverb',
    key: REVERB_CONFIG.key,

    wsHost: REVERB_CONFIG.host,

    // IMPORTANT: on HTTPS, let it use default wss (443) without forcing port
    wsPort: forceTLS ? undefined : 80,
    wssPort: forceTLS ? undefined : undefined,

    wsPath: REVERB_CONFIG.path,
    forceTLS,
    enabledTransports: forceTLS ? ['wss'] : ['ws'],
    disableStats: true,

    // keep only if using private channels
    authEndpoint: REVERB_CONFIG.authEndpoint,
  });

  const conn = echo.connector?.pusher?.connection;
  if (conn?.bind) {
    conn.bind('error', (err) => console.error('[echo] error', err));
    conn.bind('state_change', (states) => console.log('[echo] state', states));
  }

  return echo;
}

export default createEcho;
