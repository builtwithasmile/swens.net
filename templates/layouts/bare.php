<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'swens.net') ?></title>
  <meta name="robots" content="noindex">
  <link rel="icon" href="/favicon.ico" sizes="any">
  <link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
  <link rel="icon" href="/assets/favicon-32.png" type="image/png" sizes="32x32">
  <link rel="apple-touch-icon" href="/assets/apple-touch-icon.png" sizes="180x180">
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#16202a">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&family=Fragment+Mono&display=swap" rel="stylesheet">
  <?php
    $cssFile = (defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 3)) . '/public/assets/css/site.css';
    $ver = is_file($cssFile) ? filemtime($cssFile) : 1;
  ?>
  <link rel="stylesheet" href="/assets/css/site.css?v=<?= $ver ?>">
</head>
<body class="interior">
<?= $content ?? '' ?>
</body>
</html>
