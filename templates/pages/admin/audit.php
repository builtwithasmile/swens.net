<?php
/** Owner-only: last 200 privileged admin actions, newest first. */
?>
<h1 style="font-size:1.25rem;margin:0 0 1.5rem">Audit trail</h1>

<table>
<thead>
  <tr><th>When (UTC)</th><th>Action</th><th>Subject</th><th>IP</th></tr>
</thead>
<tbody>
<?php foreach ($log ?? [] as $row): ?>
<tr>
  <td style="color:#94a3b8"><?= e($row['created_at']) ?></td>
  <td><?= e($row['action']) ?></td>
  <td style="color:#94a3b8"><?= e($row['subject'] ?? '') ?></td>
  <td style="color:#94a3b8"><?= e($row['ip'] ?? '') ?></td>
</tr>
<?php endforeach ?>
<?php if (empty($log)): ?>
<tr><td colspan="4" style="color:#64748b;text-align:center;padding:2rem">No activity recorded yet.</td></tr>
<?php endif ?>
</tbody>
</table>
