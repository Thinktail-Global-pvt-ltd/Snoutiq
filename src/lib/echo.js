import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const REVERB_CONFIG = {
  key: 'base64:yT9RzP3vXl9lJ2pB2g==', // plain key
  host: 'snoutiq.com',
  scheme: 'https',
  path: '/app',
  authEndpoint: '/backend/broadcasting/auth',
};

export function createEcho() {
  const forceTLS = true;
  const proto = 'wss';
  const debugUrl = `${proto}://${REVERB_CONFIG.host}${REVERB_CONFIG.path}/${REVERB_CONFIG.key}?protocol=7&client=js`;
  console.log('[echo] connecting to', debugUrl, {
    host: REVERB_CONFIG.host,
    path: REVERB_CONFIG.path,
    key: REVERB_CONFIG.key,
    authEndpoint: REVERB_CONFIG.authEndpoint,
    transports: ['wss'],
  });

  const echo = new Echo({
    broadcaster: 'reverb',
    key: REVERB_CONFIG.key,
    wsHost: REVERB_CONFIG.host,
    wsPath: REVERB_CONFIG.path, // /app
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
        port: REVERB_CONFIG.port,
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
