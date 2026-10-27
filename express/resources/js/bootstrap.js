/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
import { withBase } from './utils/basePath';

/**
 * When the app is served under a sub-path (see utils/basePath.js), make
 * root-relative fetch() calls such as fetch("/api/...") hit this app instead
 * of the domain root.
 */
const nativeFetch = window.fetch.bind(window);
window.fetch = (input, init) =>
    nativeFetch(typeof input === 'string' ? withBase(input) : input, init);
import _ from 'lodash';
window.axios = axios;
window._ = _;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.baseURL = import.meta.env.VITE_APP_URL + "/api/";
window.axios.defaults.headers.post['Content-Type'] = 'application/json';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Sem servidor de WebSocket (o Pusher antigo saiu): Echo "mudo" com a mesma
// API, para as telas que assinam canais seguirem funcionando sem tempo real.
const silentChannel = new Proxy({}, { get: (_, prop) => (prop === 'then' ? undefined : () => silentChannel) });
const silentEcho = new Proxy({}, { get: (_, prop) => (prop === 'then' ? undefined : () => silentChannel) });

// Only initialize Echo if Pusher credentials are available
window.Echo = import.meta.env.VITE_PUSHER_APP_KEY
    ? new Echo({
          broadcaster: "pusher",
          wsHost: import.meta.env.VITE_PUSHER_HOST,
          wsPort: import.meta.env.VITE_PUSHER_PORT,
          wssPort: import.meta.env.VITE_PUSHER_PORT,
          key: import.meta.env.VITE_PUSHER_APP_KEY,
          cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
          forceTLS: false,
          encrypted: true,
          enableStats: false,
          enabledTransports: ['wss', 'ws'],
      })
    : silentEcho;

// if (import.meta.env.VITE_PUSHER_APP_KEY) {
//     try {
//         window.Echo = new Echo({
//             broadcaster: "pusher",
//             wsHost: import.meta.env.VITE_PUSHER_HOST,
//             wsPort: import.meta.env.VITE_PUSHER_PORT,
//             wssPort: import.meta.env.VITE_PUSHER_PORT,
//             key: import.meta.env.VITE_PUSHER_APP_KEY,
//             cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
//             forceTLS: false,
//             encrypted: true,
//             enableStats: false,
//             enabledTransports: ['wss', 'ws'],
//             authEndpoint: '/broadcasting/auth',
//             auth: {
//                 headers: {
//                     'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
//                     'X-Requested-With': 'XMLHttpRequest',
//                 },
//                 withCredentials: true,
//             },
//         });
//         console.log('Laravel Echo initialized successfully in fluent');
//     } catch (error) {
//         console.warn('Failed to initialize Laravel Echo:', error);
//         window.Echo = null;
//     }
// } else {
//     console.warn('Pusher credentials not found. WebSocket functionality will be disabled.');
//     window.Echo = null;
// }
