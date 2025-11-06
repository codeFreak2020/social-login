<?php
// Example app config. This file will try to load project-root/.env (if present)
// so you can keep secrets out of version control.

use Dotenv\Dotenv;

$root = dirname(__DIR__, 2);
if (is_file($root . '/.env') && class_exists(Dotenv::class)) {
    Dotenv::createImmutable($root)->safeLoad();
}

return [
    'providers' => [
        'google' => [
            'client_id' => getenv('SOCIAL_GOOGLE_CLIENT_ID') ?: 'GOOGLE_CLIENT_ID',
            'client_secret' => getenv('SOCIAL_GOOGLE_CLIENT_SECRET') ?: 'GOOGLE_CLIENT_SECRET',
            'redirect_uri' => getenv('SOCIAL_GOOGLE_REDIRECT_URI') ?: 'http://localhost:8000/callback.php?provider=google',
            // Optional: 'scopes' => ['openid','email','profile'],
        ],
        'github' => [
            'client_id' => getenv('SOCIAL_GITHUB_CLIENT_ID') ?: 'GITHUB_CLIENT_ID',
            'client_secret' => getenv('SOCIAL_GITHUB_CLIENT_SECRET') ?: 'GITHUB_CLIENT_SECRET',
            'redirect_uri' => getenv('SOCIAL_GITHUB_REDIRECT_URI') ?: 'http://localhost:8000/callback.php?provider=github',
            // Optional: 'scopes' => ['read:user','user:email'],
        ],
    ],
];
