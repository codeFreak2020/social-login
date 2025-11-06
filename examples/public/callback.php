<?php
declare(strict_types=1);

use SocialLogin\Manager;

require __DIR__ . '/../../vendor/autoload.php';

session_start();

$provider = $_GET['provider'] ?? ($_SESSION['oauth2provider'] ?? '');
$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    echo 'Missing config.php. Copy config.sample.php to config.php and fill credentials.';
    exit;
}
$config = require $configFile;

$state = $_GET['state'] ?? '';
if (empty($_SESSION['oauth2state']) || $state !== $_SESSION['oauth2state']) {
    unset($_SESSION['oauth2state']);
    http_response_code(400);
    echo 'Invalid state';
    exit;
}

$code = $_GET['code'] ?? '';
if (!$code) {
    http_response_code(400);
    echo 'Missing authorization code';
    exit;
}

try {
    $manager = new Manager($config);
    $driver = $manager->driver($provider);
    $token = $driver->fetchAccessToken($code);
    $user = $driver->fetchUser($token['access_token']);
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Callback error: ' . htmlspecialchars($e->getMessage());
    exit;
}

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login Success</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 2rem; }
    img { border-radius: 50%; width: 80px; height: 80px; }
    pre { background: #f3f4f6; padding: 1rem; border-radius: 8px; overflow:auto; }
  </style>
</head>
<body>
  <h1>Login Success (<?php echo htmlspecialchars($provider); ?>)</h1>
  <?php if ($user->avatar): ?>
    <p><img src="<?php echo htmlspecialchars($user->avatar); ?>" alt="avatar" /></p>
  <?php endif; ?>
  <ul>
    <li><strong>ID:</strong> <?php echo htmlspecialchars($user->id); ?></li>
    <li><strong>Name:</strong> <?php echo htmlspecialchars($user->name ?? ''); ?></li>
    <li><strong>Email:</strong> <?php echo htmlspecialchars($user->email ?? ''); ?></li>
  </ul>
  <h3>Raw profile</h3>
  <pre><?php echo htmlspecialchars(json_encode($user->raw, JSON_PRETTY_PRINT)); ?></pre>
</body>
</html>
