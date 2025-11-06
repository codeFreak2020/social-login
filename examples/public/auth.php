<?php
declare(strict_types=1);

use SocialLogin\Manager;

require __DIR__ . '/../../vendor/autoload.php';

session_start();

$provider = $_GET['provider'] ?? '';
$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    echo 'Missing config.php. Copy config.sample.php to config.php and fill credentials.';
    exit;
}
$config = require $configFile;

try {
    if (isset($_GET['debug'])) {
        header('Content-Type: text/plain');
        $cfg = $config['providers'][$provider] ?? [];
        $meta = $config['meta'] ?? [];
        $mask = function ($s) {
            $s = (string) $s;
            if ($s === '') return '';
            return substr($s, 0, 6) . str_repeat('*', max(0, strlen($s) - 10)) . substr($s, -4);
        };
        echo "Provider: $provider\n";
        echo "client_id:  ".$mask($cfg['client_id'] ?? '')."\n";
        echo "secret:     ".$mask($cfg['client_secret'] ?? '')."\n";
        echo "redirect:   ".($cfg['redirect_uri'] ?? '')."\n";
        echo "APP_URL:    ".(getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? ''))."\n";
        echo "ENV loader: ".($meta['env_loader'] ?? 'unknown')."\n";
        echo "ENV path:   ".($meta['env_path'] ?? 'n/a')."\n";
        exit;
    }
    $manager = new Manager($config);
    $driver = $manager->driver($provider);
    $authUrl = $driver->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $driver->getState();
    $_SESSION['oauth2provider'] = $provider;
    header('Location: ' . $authUrl);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Auth error: ' . htmlspecialchars($e->getMessage());
    exit;
}
