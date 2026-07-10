<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title ?? 'Admin') ?></title>
<style>
body{font-family:system-ui,sans-serif;background:#0f1117;color:#e8edf4;margin:0;padding:0}
.admin-bar{background:#1a1f2e;border-bottom:1px solid #2a3040;padding:.75rem 1.5rem;display:flex;gap:1rem;align-items:center}
.admin-bar a{color:#94a3b8;text-decoration:none;font-size:.875rem}
.admin-bar a:hover{color:#e8edf4}
.admin-content{max-width:900px;margin:2rem auto;padding:0 1.5rem}
.flash{background:#1e3a5f;border:1px solid #2d5a8e;border-radius:4px;padding:.75rem 1rem;margin-bottom:1.5rem;font-size:.875rem}
.btn{display:inline-block;padding:.5rem 1rem;border-radius:4px;border:none;cursor:pointer;font-size:.875rem;text-decoration:none}
.btn-primary{background:#2563eb;color:#fff}.btn-primary:hover{background:#1d4ed8}
.btn-danger{background:#dc2626;color:#fff}.btn-danger:hover{background:#b91c1c}
.btn-sm{padding:.25rem .6rem;font-size:.8rem}
.form-group{margin-bottom:1.25rem}
label{display:block;margin-bottom:.35rem;font-size:.875rem;color:#94a3b8}
input[type=text],input[type=email],select,textarea{width:100%;box-sizing:border-box;background:#1a1f2e;border:1px solid #2a3040;color:#e8edf4;border-radius:4px;padding:.5rem .75rem;font-size:.875rem}
textarea{min-height:300px;font-family:monospace}
.error{color:#f87171;font-size:.8rem;margin-top:.25rem}
table{width:100%;border-collapse:collapse;font-size:.875rem}
th,td{text-align:left;padding:.6rem .75rem;border-bottom:1px solid #1a1f2e}
th{color:#94a3b8;font-weight:500}
tr:hover td{background:#1a1f2e}
</style>
</head>
<body>
<nav class="admin-bar">
  <strong style="color:#e8edf4">swens.net admin</strong>
  <a href="/admin">Posts</a>
  <a href="/admin/posts/new">+ New Post</a>
  <a href="/admin/members">Members</a>
  <a href="/admin/audit">Audit</a>
  <span style="flex:1"></span>
  <form method="post" action="/admin/logout" style="margin:0">
    <?= csrf_field() ?>
    <button type="submit" style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:.875rem">Log out</button>
  </form>
</nav>
<div class="admin-content">
<?= $content ?? '' ?>
</div>
</body>
</html>
