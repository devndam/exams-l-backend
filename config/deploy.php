<?php

return [
    // Shared secret the CI pipeline presents (as a Bearer token) to trigger
    // post-deploy tasks on hosts with no SSH access. Generate with
    // `php artisan tinker --execute="echo Str::random(40);"`.
    'token' => env('DEPLOY_TOKEN'),
];
