<?php

return [
    // Provider: 'groq' (gratuito) o 'claude' (a pagamento)
    'provider' => env('AI_PROVIDER', 'groq'),

    // API Key del provider scelto
    'api_key' => env('AI_API_KEY', ''),

    // Modello da usare
    // Groq: 'llama-3.3-70b-versatile', 'gemma2-9b-it'
    // Claude: 'claude-sonnet-4-20250514'
    'model' => env('AI_MODEL', 'llama-3.3-70b-versatile'),
];
