import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http';
const forceTLS = scheme === 'https';
const host = import.meta.env.VITE_REVERB_HOST;
const port = Number(import.meta.env.VITE_REVERB_PORT ?? (forceTLS ? 443 : 80));

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: host,
    wsPort: port,
    wssPort: port,
    forceTLS,
    encrypted: forceTLS,
    enabledTransports: forceTLS ? ['ws', 'wss'] : ['ws'],
    disableStats: true,
});
