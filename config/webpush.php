<?php

return [
    /*
    |--------------------------------------------------------------------------
    | VAPID Keys
    |--------------------------------------------------------------------------
    | Generare con: npx web-push generate-vapid-keys
    | Oppure: php -r "echo 'Usa openssl per generare le chiavi VAPID';"
    */
    'public_key' => env('VAPID_PUBLIC_KEY', ''),
    'private_key' => env('VAPID_PRIVATE_KEY', ''),
    'subject' => env('VAPID_SUBJECT', 'mailto:it@graficanappa.com'),
];
