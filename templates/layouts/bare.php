<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'swens.net') ?></title>
  <meta name="robots" content="noindex">
  <link rel="icon" type="image/svg+xml" href="/assets/swens-mark.svg">
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
