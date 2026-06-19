<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'swens.net') ?></title>
  <meta name="description" content="<?= e($meta_desc ?? '') ?>">
  <meta name="robots" content="<?= e($robots ?? 'index,follow') ?>">
  <meta property="og:title" content="<?= e($title ?? 'swens.net') ?>">
  <meta property="og:description" content="<?= e($meta_desc ?? '') ?>">
  <meta property="og:type" content="website">
  <link rel="icon" type="image/svg+xml" href="/assets/swens-mark.svg">
  <?php
    $cssFile = (defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 3)) . '/public/assets/css/site.css';
    $ver = is_file($cssFile) ? filemtime($cssFile) : 1;
  ?>
  <link rel="stylesheet" href="/assets/css/site.css?v=<?= $ver ?>">
</head>
<body<?= ($active ?? '') !== 'map' ? ' class="interior"' : '' ?>>
<?php /* RT#3: the map IS the interface — no chrome on the map itself; interior pages get only the walk-back bar. Footer is absorbed into the map's deed line. */ ?>
<?php if (($active ?? '') !== 'map'): ?>
<?= partial('partials/site-header', ['active' => $active ?? '']) ?>
<?php endif; ?>
<main>
<?= $content ?? '' ?>
</main>
<?php /* Public business footer — omitted inside the keyed room. */ ?>
<?php if (($active ?? '') !== 'inside'): ?>
<?= partial('partials/site-footer') ?>
<?php endif; ?>
</body>
</html>
