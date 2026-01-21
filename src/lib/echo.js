import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const REVERB_CONFIG = {
  key: 'base64:yT9RzP3vXl9lJ2pB2g==', // replace with your plain key if different
  host: typeof window !== 'undefined' ? window.location.hostname : 'localhost',
  port: typeof window !== 'undefined' && window.location.protocol === 'https:' ? 443 : 80,
  scheme: typeof window !== 'undefined' ? window.location.protocol.replace(':', '') : 'http',
  // Backend dev pages run under /backend, so keep wsPath aligned with Blade: meta base + /app
  path: '/backend',
  authEndpoint: '/backend/broadcasting/auth',
};

export function createEcho() {
  const forceTLS = REVERB_CONFIG.scheme === 'https';
  const proto = forceTLS ? 'wss' : 'ws';
  const debugUrl = `${proto}://${REVERB_CONFIG.host}${REVERB_CONFIG.port ? ':' + REVERB_CONFIG.port : ''}${REVERB_CONFIG.path}/${REVERB_CONFIG.key}?protocol=7&client=js`;
  console.log('[echo] connecting to', debugUrl, {
    host: REVERB_CONFIG.host,
    port: REVERB_CONFIG.port,
    path: REVERB_CONFIG.path,
    key: REVERB_CONFIG.key,
    authEndpoint: REVERB_CONFIG.authEndpoint,
    transports: forceTLS ? ['wss'] : ['ws', 'wss'],
  });

  const echo = new Echo({
    broadcaster: 'reverb',
    key: REVERB_CONFIG.key,
    wsHost: REVERB_CONFIG.host,
    wsPort: REVERB_CONFIG.port,
    wssPort: REVERB_CONFIG.port,
    wsPath: REVERB_CONFIG.path, // already includes /backend/app
    forceTLS,
    enabledTransports: forceTLS ? ['wss'] : ['ws', 'wss'],
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
