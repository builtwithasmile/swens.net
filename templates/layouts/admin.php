<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title ?? 'Admin') ?> — swens.net admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&family=Fragment+Mono&display=swap" rel="stylesheet">
<?php
  $cssFile = (defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 3)) . '/public/assets/css/site.css';
  $ver = is_file($cssFile) ? filemtime($cssFile) : 1;
?>
<link rel="stylesheet" href="/assets/css/site.css?v=<?= $ver ?>">
<style>
/* Admin chrome only — reuses DNA tokens from site.css, not a separate palette (2026-07-10 restyle) */
.admin-bar{display:flex;gap:1.5rem;align-items:center;padding:.875rem clamp(1rem,4vw,1.5rem);border-bottom:1px solid var(--line);background:var(--panel)}
.admin-bar strong{font-family:var(--font-mono);font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);font-weight:400}
.admin-bar a{font-family:var(--font-mono);font-size:13px;letter-spacing:.04em;color:var(--text)}
.admin-bar a:hover{color:var(--muted)}
.admin-bar form button{font-family:var(--font-mono);font-size:13px;background:none;border:none;color:var(--muted);cursor:pointer;padding:0}
.admin-bar form button:hover{color:var(--text)}
.admin-content{max-width:900px;margin:2.5rem auto;padding:0 clamp(1rem,4vw,1.5rem) 4rem}
.flash{border:1px solid var(--line);padding:.875rem 1rem;margin-bottom:1.5rem;font-size:.875rem}
.btn{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:0 1.1rem;border-radius:999px;border:1px solid var(--text);font:inherit;font-weight:700;font-size:.8125rem;cursor:pointer;text-decoration:none;color:var(--text);background:none}
.btn:hover{opacity:.7}
.btn-primary{background:var(--accent);color:var(--on-accent);border-color:var(--accent)}
.btn-danger{background:none;color:var(--clay);border-color:var(--clay)}
.btn-sm{min-height:32px;padding:0 .8rem;font-size:.75rem}
.form-group{margin-bottom:1.25rem}
label{display:block;margin-bottom:.4rem;font-family:var(--font-mono);font-size:.75rem;letter-spacing:.06em;text-transform:uppercase;color:var(--muted)}
input[type=text],input[type=email],select,textarea{width:100%;box-sizing:border-box;background:var(--panel);border:1px solid var(--line);color:var(--text);border-radius:0;padding:.6rem .75rem;font:inherit;font-size:.9375rem}
textarea{min-height:300px;font-family:var(--font-mono)}
.error{color:var(--clay);font-size:.8rem;margin-top:.3rem}
table{width:100%;border-collapse:collapse;font-size:.875rem}
th,td{text-align:left;padding:.7rem .75rem;border-bottom:1px solid var(--line)}
th{font-family:var(--font-mono);font-size:.6875rem;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);font-weight:400}
tr:hover td{background:var(--panel)}
</style>
</head>
<body class="interior">
<nav class="admin-bar">
  <strong>swens.net admin</strong>
  <a href="/admin">Posts</a>
  <a href="/admin/posts/new">+ New post</a>
  <a href="/admin/members">Members</a>
  <a href="/admin/audit">Audit</a>
  <span style="flex:1"></span>
  <form method="post" action="/admin/logout" style="margin:0">
    <?= csrf_field() ?>
    <button type="submit">Log out</button>
  </form>
</nav>
<div class="admin-content">
<?= $content ?? '' ?>
</div>
</body>
</html>
