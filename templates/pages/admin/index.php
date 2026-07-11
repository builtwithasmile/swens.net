<div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem">
  <h1 style="font-size:1.25rem;margin:0">Posts</h1>
  <a href="/admin/posts/new" class="btn btn-primary btn-sm">+ New</a>
</div>
<table>
<thead>
  <tr><th>Title</th><th>Building</th><th>Tier</th><th>Kind</th><th>Date</th><th></th></tr>
</thead>
<tbody>
<?php foreach ($posts ?? [] as $p): ?>
<tr>
  <td><?= e($p['title']) ?></td>
  <td><?= e($p['building']) ?></td>
  <td><?= e($p['tier']) ?></td>
  <td><?= e($p['kind']) ?></td>
  <td><?= e(substr($p['created_at'], 0, 10)) ?></td>
  <td style="display:flex;gap:.5rem">
    <a href="/admin/posts/<?= (int)$p['id'] ?>/edit" class="btn btn-sm">Edit</a>
    <form method="post" action="/admin/posts/<?= (int)$p['id'] ?>/delete" style="margin:0">
      <?= $csrf ?? '' ?>
      <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">Del</button>
    </form>
  </td>
</tr>
<?php endforeach ?>
<?php if (empty($posts)): ?>
<tr><td colspan="6" style="color:var(--muted);text-align:center;padding:2rem">No posts yet.</td></tr>
<?php endif ?>
</tbody>
</table>
