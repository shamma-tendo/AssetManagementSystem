<?php
/** @var string $title */
/** @var string $body */
/** @var mixed $flashOk */
/** @var mixed $flashErr */
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= h($title) ?> · Asset Management (PHP)</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:wght@600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/app.css" />
</head>
<body>
  <div class="shell">
    <header class="hero">
      <div>
        <p class="eyebrow">PHP + SQLite</p>
        <h1>Asset Management</h1>
        <p class="lede">Stored on this PC under <code>asset-management-php/data/</code></p>
      </div>
    </header>

    <?php if (!empty($flashOk)): ?>
      <div class="banner ok" role="status"><?= h((string) $flashOk) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashErr)): ?>
      <div class="banner err" role="alert"><?= h((string) $flashErr) ?></div>
    <?php endif; ?>

    <?= $body ?>
  </div>
</body>
</html>
