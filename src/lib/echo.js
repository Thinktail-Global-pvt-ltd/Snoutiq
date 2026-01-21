import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const REVERB_CONFIG = {
  key: 'base64:yT9RzP3vXl9lJ2pB2g==', // plain key
  host: 'snoutiq.com',
  path: '/app',
  authEndpoint: '/backend/broadcasting/auth',
};

export function createEcho() {
  const forceTLS = true;
  const debugUrl = 'wss://snoutiq.com/app/base64:yT9RzP3vXl9lJ2pB2g==?protocol=7&client=js&version=8.4.0&flash=false';
  console.log('[echo] connecting to', debugUrl, {
    host: REVERB_CONFIG.host,
    path: REVERB_CONFIG.path,
    key: REVERB_CONFIG.key,
    authEndpoint: REVERB_CONFIG.authEndpoint,
    transports: ['wss'],
  });
  try {
    const socket = new WebSocket(debugUrl);
    socket.onopen = () => {
      console.log('[echo] raw websocket opened', debugUrl);
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
    wsHost: 'snoutiq.com',
    wsPath: REVERB_CONFIG.path,
    forceTLS,
    enabledTransports: ['wss'],
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
  }

  return echo;
}

export { REVERB_CONFIG };
export default createEcho;
