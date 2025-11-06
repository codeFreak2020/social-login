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
</body>
</html>
