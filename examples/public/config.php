<?php
// Copy this file to config.php and fill in your credentials
return [
    'providers' => [
        'google' => [
            'client_id' => 'GOOGLE_CLIENT_ID',
            'client_secret' => 'GOOGLE_CLIENT_SECRET',
            'redirect_uri' => 'http://localhost:8000/callback.php?provider=google',
            // Optional: 'scopes' => ['openid','email','profile'],
        ],
        'github' => [
            'client_id' => 'GITHUB_CLIENT_ID',
            'client_secret' => 'GITHUB_CLIENT_SECRET',
            'redirect_uri' => 'http://localhost:8000/callback.php?provider=github',
            // Optional: 'scopes' => ['read:user','user:email'],
        ],
    ],
];
