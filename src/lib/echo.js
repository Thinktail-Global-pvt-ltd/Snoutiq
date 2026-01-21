import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Static Reverb settings (hardcoded for production WS)
const REVERB_CONFIG = {
  key: 'base64:yT9RzP3vXl9lJ2pB2g==',
  host: 'snoutiq.com',
  port: 443,
  scheme: 'https',
  path: '/app', // default Reverb path
};

export function createEcho() {
  const forceTLS = true;

  // Log the exact target URL for debugging
  const debugUrl = `wss://${REVERB_CONFIG.host}${REVERB_CONFIG.path}/${REVERB_CONFIG.key}?protocol=7&client=js`;
  console.log('[echo] connecting to', debugUrl);

  const echo = new Echo({
    broadcaster: 'pusher',
    key: REVERB_CONFIG.key,
    // cluster intentionally omitted (not needed when host provided)
    wsHost: REVERB_CONFIG.host,
    wsPort: REVERB_CONFIG.port,
    wssPort: REVERB_CONFIG.port,
    wsPath: '', // let pusher-js build /app/{key}; do NOT prefix another /app
    forceTLS,
    encrypted: forceTLS,
    disableStats: true,
    enabledTransports: ['wss'],
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
  }

  return echo;
}

export { REVERB_CONFIG };
export default createEcho;
