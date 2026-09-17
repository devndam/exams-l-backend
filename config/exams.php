<?php

return [

    'client_url' => env('CLIENT_URL', 'http://localhost:5173'),

    'jwt' => [
        'secret' => env('JWT_SECRET', 'fallback-secret'),
        'expiry' => env('JWT_EXPIRY', '4h'),
    ],

    'face' => [
        // Base URL of the externally supplied service — used only for the AWS Rekognition
        // liveness flow and its reference-image comparison; enrollment/verification/
        // monitoring face-matching is computed client-side (see FaceService).
        'service_url' => env('FACE_SERVICE_URL', 'http://localhost:5001'),
        'encryption_key' => env('FACE_ENCRYPTION_KEY', ''),
        // Euclidean distance threshold for client-side embedding verification. Matched = distance <= threshold.
        'verification_threshold' => (function () {
            $v = filter_var(env('FACE_VERIFICATION_THRESHOLD'), FILTER_VALIDATE_FLOAT);
            return $v !== false ? $v : 0.6;
        })(),
        'monitoring_interval' => (int) env('FACE_MONITORING_INTERVAL', 60),
        'max_warnings' => (int) env('FACE_MAX_WARNINGS', 5),
        // Threshold for the liveness-flow's reference-image comparison (completeLivenessSession).
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
