import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Force Reverb to connect on the current host (production VPS) over TLS
const host = window.location.hostname;
const port = 443;
const forceTLS = true;

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: host,
    wsPort: port,
    wssPort: port,
    forceTLS,
    encrypted: forceTLS,
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
});
