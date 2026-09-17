<?php

return [

    'client_url' => env('CLIENT_URL', 'http://localhost:5173'),

    'jwt' => [
        'secret' => env('JWT_SECRET', 'fallback-secret'),
        'expiry' => env('JWT_EXPIRY', '4h'),
    ],

    'face' => [
        // Base URL of the externally supplied face-verification service.
        'service_url' => env('FACE_SERVICE_URL', 'http://localhost:5001'),
        // "embedding" (service returns a comparable vector, stored encrypted) or
        // "reference-image" (service compares images itself; we just keep the capture).
        'mode' => env('FACE_SERVICE_MODE', 'embedding'),
        'encryption_key' => env('FACE_ENCRYPTION_KEY', ''),
        // Euclidean distance threshold for embedding-mode verification. Verified = distance <= threshold.
        'verification_threshold' => (function () {
            $v = filter_var(env('FACE_VERIFICATION_THRESHOLD'), FILTER_VALIDATE_FLOAT);
            return $v !== false ? $v : 0.6;
        })(),
        'monitoring_interval' => (int) env('FACE_MONITORING_INTERVAL', 60),
        'max_warnings' => (int) env('FACE_MAX_WARNINGS', 5),
        // Informational only in "reference-image" mode — the external service does its
        // own matching and returns a `matched` boolean; this is just echoed in responses.
        'reference_threshold' => (float) env('FACE_REFERENCE_SIMILARITY_THRESHOLD', 90),
        'liveness_threshold' => (float) env('FACE_LIVENESS_THRESHOLD', 75),
    ],

    'audio' => [
        'noise_threshold' => (function () {
            $v = filter_var(env('AUDIO_NOISE_THRESHOLD'), FILTER_VALIDATE_FLOAT);
            return $v !== false ? $v : -50;
        })(),
        'max_warnings' => (int) env('AUDIO_MAX_WARNINGS', 2),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
        'chat_id' => env('TELEGRAM_CHAT_ID', ''),
    ],

];
