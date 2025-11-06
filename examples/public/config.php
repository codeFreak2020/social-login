<?php
// Example app config. This file will try to load project-root/.env (if present)
// so you can keep secrets out of version control.

use Dotenv\Dotenv;

$root = dirname(__DIR__, 2);

// Track how env was loaded for debugging
$__env_loader = 'none';

// Best effort: load .env via phpdotenv if available; otherwise use a tiny fallback parser
if (is_file($root . '/.env')) {
    if (class_exists(Dotenv::class)) {
        Dotenv::createImmutable($root)->safeLoad();
        $__env_loader = 'phpdotenv';
    } else {
        // Fallback minimal loader (does not support all phpdotenv features)
        $lines = @file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            if ($line === '' || str_starts_with(trim($line), '#')) {
                continue;
            }
            // Support KEY=VALUE with optional quotes
            if (!str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = array_map('trim', explode('=', $line, 2));
            // Strip possible UTF-8 BOM from the first key
            if (!isset($__bom_stripped)) {
                $k = preg_replace('/^\xEF\xBB\xBF/', '', $k);
                $__bom_stripped = true;
            }
            $v = trim($v, "\"' ");
            // very basic expansion for ${VAR}
            $v = preg_replace_callback('/\$\{([A-Z0-9_]+)\}/i', function ($m) {
                $ref = $m[1];
                return getenv($ref) ?: ($_ENV[$ref] ?? '');
            }, $v);
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
            if (function_exists('putenv')) {
                putenv($k.'='.$v);
            }
        }
        $__env_loader = 'fallback';
    }
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
    'meta' => [
        'env_loader' => $__env_loader,
        'env_path' => is_file($root . '/.env') ? ($root . '/.env') : null,
    ],
];
