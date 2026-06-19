<?php
/**
 * Owner-only: the keyed circle. Issue a key (= approve), revoke, rotate, and
 * read the board. Key URLs are shown because the page is already behind auth.
 */
$statusColor = ['approved' => '#16a34a', 'revoked' => '#dc2626', 'pending' => '#d97706'];
?>
<?php if (!empty($flash)): ?>
<div class="flash"><?= e($flash) ?></div>
<?php endif ?>

<h1 style="font-size:1.25rem;margin:0 0 1.5rem">Members</h1>

<form method="post" action="/admin/members" style="background:#1a1f2e;border:1px solid #2a3040;border-radius:6px;padding:1.25rem;margin-bottom:2rem">
  <?= $csrf ?? '' ?>
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem">
    <div class="form-group" style="margin:0">
      <label>Email</label>
      <input type="email" name="email" required maxlength="160">
    </div>
    <div class="form-group" style="margin:0">
      <label>Display name</label>
      <input type="text" name="display_name" required maxlength="80">
    </div>
    <div class="form-group" style="margin:0">
      <label>Relationship (optional)</label>
      <input type="text" name="relationship" maxlength="80" placeholder="family, HotSync era, film days…">
    </div>
  </div>
  <div style="margin-top:1rem"><button type="submit" class="btn btn-primary">Issue key</button></div>
</form>

<table>
<thead>
  <tr><th>Name</th><th>Email</th><th>Relationship</th><th>Status</th><th>Last seen</th><th>Key link</th><th></th></tr>
</thead>
<tbody>
<?php foreach ($members ?? [] as $m): ?>
<tr>
  <td><?= e($m['display_name']) ?></td>
  <td style="color:#94a3b8"><?= e($m['email']) ?></td>
  <td style="color:#94a3b8"><?= e($m['relationship'] ?? '') ?></td>
  <td><span style="color:<?= e($statusColor[$m['status']] ?? '#94a3b8') ?>">&#9679;</span> <?= e($m['status']) ?></td>
  <td style="color:#94a3b8"><?= e($m['last_seen_at'] ? substr($m['last_seen_at'], 0, 10) : '—') ?></td>
  <td><input type="text" readonly value="<?= e($m['key_url']) ?>" onclick="this.select()" style="width:13rem;font-size:.72rem;background:#0f1117;border:1px solid #2a3040;color:#94a3b8;border-radius:4px;padding:.3rem .4rem"></td>
  <td style="display:flex;gap:.4rem">
    <?php if ($m['status'] === 'approved'): ?>
    <form method="post" action="/admin/members/<?= (int)$m['id'] ?>/revoke" style="margin:0"><?= $csrf ?? '' ?><button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Revoke this key?')">Revoke</button></form>
    <?php else: ?>
    <form method="post" action="/admin/members/<?= (int)$m['id'] ?>/approve" style="margin:0"><?= $csrf ?? '' ?><button type="submit" class="btn btn-sm" style="background:#16a34a;color:#fff">Approve</button></form>
    <?php endif ?>
    <form method="post" action="/admin/members/<?= (int)$m['id'] ?>/rotate" style="margin:0"><?= $csrf ?? '' ?><button type="submit" class="btn btn-sm" style="background:#1a1f2e;color:#e8edf4" onclick="return confirm('Rotate key? The old link dies.')">Rotate</button></form>
  </td>
</tr>
<?php endforeach ?>
<?php if (empty($members)): ?>
<tr><td colspan="7" style="color:#64748b;text-align:center;padding:2rem">No members yet. Issue the first key above.</td></tr>
<?php endif ?>
</tbody>
</table>

<h2 style="font-size:1.05rem;margin:2.5rem 0 1rem">The board — recent check-ins</h2>
<?php if (empty($checkins)): ?>
<p style="color:#64748b">No check-ins yet.</p>
<?php else: ?>
<div style="display:grid;gap:1rem">
  <?php foreach ($checkins as $c): ?>
  <div style="border-bottom:1px solid #1a1f2e;padding-bottom:.85rem">
    <p style="margin:0;font-size:.8rem;color:#94a3b8"><strong style="color:#e8edf4"><?= e($c['display_name']) ?></strong> · <?= e(substr($c['created_at'], 0, 16)) ?><?php if (!empty($c['mood'])): ?> · <em><?= e($c['mood']) ?></em><?php endif ?></p>
    <p style="margin:.3rem 0 0;font-size:.9rem"><?= nl2br(e($c['body'])) ?></p>
  </div>
  <?php endforeach ?>
</div>
<?php endif ?>
