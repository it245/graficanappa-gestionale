{{-- Laravel Echo via CDN (Reverb WebSocket) --}}
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.3.0/dist/web/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
<script>
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: '{{ config("reverb.apps.0.key", env("REVERB_APP_KEY", "")) }}',
    wsHost: '{{ config("reverb.servers.0.host", env("REVERB_HOST", "localhost")) }}',
    wsPort: {{ config("reverb.servers.0.port", env("REVERB_PORT", 8080)) }},
    wssPort: {{ config("reverb.servers.0.port", env("REVERB_PORT", 8080)) }},
    forceTLS: {{ config("reverb.servers.0.scheme", "http") === 'https' ? 'true' : 'false' }},
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
});

// Flag globale: true se Echo è connesso
window.echoConnected = false;
window.Echo.connector.pusher.connection.bind('connected', function() {
    window.echoConnected = true;
    console.log('Echo connesso a Reverb');
});
window.Echo.connector.pusher.connection.bind('disconnected', function() {
    window.echoConnected = false;
    console.log('Echo disconnesso — fallback a polling');
});

/**
 * Helper: ascolta canale con fallback a polling.
 * Se Reverb non è disponibile dopo 5s, attiva il polling.
 */
window.listenOrPoll = function(channelName, eventName, callback, pollFn, pollInterval) {
    // Ascolta via WebSocket
    window.Echo.channel(channelName).listen('.' + eventName, function(data) {
        callback(data);
    });

    // Fallback: se non connesso dopo 5s, avvia polling
    setTimeout(function() {
        if (!window.echoConnected && pollFn) {
            console.log('Reverb non disponibile, fallback polling per ' + channelName);
            setInterval(pollFn, pollInterval || 15000);
            pollFn(); // prima esecuzione immediata
        }
    }, 5000);
};
</script>
