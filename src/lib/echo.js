import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Hardcoded Reverb settings for local testing (no .env required)
const REVERB_CONFIG = {
  key: 'base64:yT9RzP3vXl9lJ2pB2g==',
  host: '127.0.0.1',
  port: 8080,
  scheme: 'http',
  path: '/app',
};

export function createEcho() {
  const forceTLS = REVERB_CONFIG.scheme === 'https';

  return new Echo({
    broadcaster: 'pusher', // Reverb uses Pusher protocol
    key: REVERB_CONFIG.key,
    cluster: 'mt1', // required by pusher-js even when overriding host
    wsHost: REVERB_CONFIG.host,
    wsPort: REVERB_CONFIG.port,
    wssPort: REVERB_CONFIG.port,
    // Leave empty so pusher-js builds /app/{key} (Reverb default). Setting '/app'
    // would prepend and produce /app/app/{key}, which breaks.
    wsPath: '',
    forceTLS,
    encrypted: forceTLS,
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
  });
}

export { REVERB_CONFIG };
export default createEcho;
