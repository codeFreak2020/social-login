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
