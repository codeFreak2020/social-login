<?php
declare(strict_types=1);

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Social Login Demo</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 2rem; }
    a.btn { display:inline-block; padding:.6rem 1rem; border-radius:6px; background:#111827; color:#fff; text-decoration:none; margin-right:.5rem; }
    .note { color:#6b7280; margin-top:1rem; }
  </style>
</head>
<body>
  <h1>Social Login Demo</h1>
  <p>
    <a class="btn" href="auth.php?provider=google">Continue with Google</a>
    <a class="btn" href="auth.php?provider=github">Continue with GitHub</a>
  </p>
  <p class="note">Copy <code>config.sample.php</code> to <code>config.php</code> and set your OAuth credentials before trying.</p>
  <?php if (isset($_GET['debug'])): ?>
    <?php
      $cfgFile = __DIR__ . '/config.php';
      $cfg = is_file($cfgFile) ? (require $cfgFile) : ['providers'=>[], 'meta'=>[]];
      $g = $cfg['providers']['google'] ?? [];
      $meta = $cfg['meta'] ?? [];
      $mask = fn($s) => $s ? substr($s, 0, 6) . str_repeat('*', max(0, strlen($s)-10)) . substr($s, -4) : '';
    ?>
    <div style="margin-top:1.5rem;padding:1rem;border:1px solid #e5e7eb;border-radius:8px;background:#f9fafb;">
      <strong>Debug: Loaded config (sanitized)</strong>
      <pre style="white-space:pre-wrap;">
Google client_id: <?php echo htmlspecialchars($mask($g['client_id'] ?? '')); ?>
Google secret:    <?php echo htmlspecialchars($mask($g['client_secret'] ?? '')); ?>
Google redirect:  <?php echo htmlspecialchars((string)($g['redirect_uri'] ?? '')); ?>
APP_URL:          <?php echo htmlspecialchars(getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? '')); ?>
ENV loader:       <?php echo htmlspecialchars((string)($meta['env_loader'] ?? 'unknown')); ?>
ENV path:         <?php echo htmlspecialchars((string)($meta['env_path'] ?? 'n/a')); ?>
      </pre>
      <div class="note">If values look empty or wrong, ensure <code>.env</code> exists at project root and run <code>composer install</code> to load dev dependencies.</div>
    </div>
  <?php endif; ?>
</body>
</html>
