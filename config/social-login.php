<?php

return [
    // Example configuration. You can override via env or config files in your Laravel app.
    'providers' => [
        'google' => [
            'client_id' => env('SOCIAL_GOOGLE_CLIENT_ID', ''),
            'client_secret' => env('SOCIAL_GOOGLE_CLIENT_SECRET', ''),
            'redirect_uri' => env('SOCIAL_GOOGLE_REDIRECT_URI', ''),
            // 'scopes' => ['openid', 'email', 'profile'],
        ],
        'github' => [
            'client_id' => env('SOCIAL_GITHUB_CLIENT_ID', ''),
            'client_secret' => env('SOCIAL_GITHUB_CLIENT_SECRET', ''),
            'redirect_uri' => env('SOCIAL_GITHUB_REDIRECT_URI', ''),
            // 'scopes' => ['read:user', 'user:email'],
        ],
    ],
];
