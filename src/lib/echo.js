import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const REVERB_CONFIG = {
  key: 'base64:yT9RzP3vXl9lJ2pB2g==', // plain key
  host: 'snoutiq.com',
  scheme: 'https',
  port: 443,
  path: '/app',
  authEndpoint: 'https://snoutiq.com/backend/broadcasting/auth',
};

export function createEcho() {
  const forceTLS = true;
  const debugUrl = 'wss://snoutiq.com/app/base64:yT9RzP3vXl9lJ2pB2g==?protocol=7&client=js&version=8.4.0&flash=false';
  console.log('[echo] connecting to', debugUrl, {
    host: REVERB_CONFIG.host,
    path: REVERB_CONFIG.path,
    key: REVERB_CONFIG.key,
    authEndpoint: REVERB_CONFIG.authEndpoint,
    transports: ['ws', 'wss'],
  });
  try {
    const socket = new WebSocket(debugUrl);
    socket.onopen = () => {
      console.log('[echo] raw websocket opened', debugUrl);
    };
    socket.onmessage = (event) => {
      console.log('[echo] raw websocket message', event.data);
    };
    socket.onclose = (event) => {
      console.warn('[echo] raw websocket closed', { code: event.code, reason: event.reason, wasClean: event.wasClean });
    };
    socket.onerror = (event) => {
      console.error('[echo] raw websocket error', { event, debugUrl });
    };
  } catch (error) {
    console.error('[echo] failed to open raw websocket', { error, debugUrl });
  }

  const echo = new Echo({
    broadcaster: 'reverb',
    key: REVERB_CONFIG.key,
    wsHost: REVERB_CONFIG.host,
    wsPort: REVERB_CONFIG.port,
    wssPort: REVERB_CONFIG.port,
    // omit wsPath to let Echo/Pusher default to /app
    forceTLS,
    encrypted: forceTLS,
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    authEndpoint: REVERB_CONFIG.authEndpoint,
  });

  const conn = echo.connector?.pusher?.connection;
  if (conn?.bind) {
    conn.bind('error', (err) => {
      console.error('[echo] websocket error', {
        host: REVERB_CONFIG.host,
        key: REVERB_CONFIG.key,
        err,
      });
    });
    conn.bind('state_change', (states) => {
      console.log('[echo] state', states);
    });
    conn.bind('connected', () => console.log('[echo] connected'));
    conn.bind('disconnected', () => console.warn('[echo] disconnected'));
    conn.bind('connecting_in', (delay) => console.log('[echo] reconnecting in', delay));
    conn.bind('unavailable', () => console.error('[echo] unavailable'));
  }

  return echo;
}

export { REVERB_CONFIG };
export default createEcho;
