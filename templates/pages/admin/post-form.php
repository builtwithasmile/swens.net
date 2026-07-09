<?php
$isEdit  = isset($post['id']);
$action  = $isEdit ? '/admin/posts/' . (int)$post['id'] : '/admin/posts';
$v       = fn(string $k, string $d = '') => e($post[$k] ?? $d);
?>
<h1 style="font-size:1.25rem;margin-bottom:1.5rem"><?= $isEdit ? 'Edit Post' : 'New Post' ?></h1>
<?php if ($errors ?? []): ?>
<div class="flash" style="background:#3b1219;border-color:#7f1d1d">
<?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach ?>
</div>
<?php endif ?>
<form method="post" action="<?= e($action) ?>">
  <?= $csrf ?? '' ?>
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem">
    <div class="form-group">
      <label>Building</label>
      <select name="building">
        <?php foreach ($buildings ?? [] as $slug): ?>
        <option value="<?= e($slug) ?>" <?= ($post['building'] ?? '') === $slug ? 'selected' : '' ?>><?= e($slug) ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <div class="form-group">
      <label>Tier</label>
      <select name="tier">
        <option value="public"  <?= ($post['tier'] ?? 'public') === 'public'  ? 'selected' : '' ?>>public</option>
        <option value="keyed"   <?= ($post['tier'] ?? '') === 'keyed'  ? 'selected' : '' ?>>keyed</option>
      </select>
    </div>
    <div class="form-group">
      <label>Kind</label>
      <select name="kind">
        <?php foreach (['welcome','about','board','now','story'] as $k): ?>
        <option value="<?= $k ?>" <?= ($post['kind'] ?? 'welcome') === $k ? 'selected' : '' ?>><?= $k ?></option>
        <?php endforeach ?>
      </select>
    </div>
  </div>
  <div class="form-group">
    <label>Title</label>
    <input type="text" name="title" value="<?= $v('title') ?>" maxlength="160" required>
    <?php if (isset($errors['title'])): ?><div class="error"><?= e($errors['title']) ?></div><?php endif ?>
  </div>
  <div class="form-group">
    <label>Slug (lowercase, hyphens)</label>
    <input type="text" name="slug" value="<?= $v('slug') ?>" maxlength="160" pattern="[a-z0-9\-]+" required>
    <?php if (isset($errors['slug'])): ?><div class="error"><?= e($errors['slug']) ?></div><?php endif ?>
  </div>
  <div class="form-group">
    <label>Tags (comma-separated)</label>
    <input type="text" name="tags" value="<?= $v('tags') ?>" maxlength="255">
  </div>
  <div class="form-group">
    <label>Body (Markdown)</label>
    <textarea name="body_md"><?= $v('body_md') ?></textarea>
  </div>
  <div style="display:flex;gap:1rem;align-items:center">
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update' : 'Publish' ?></button>
    <a href="/admin" style="color:#64748b;font-size:.875rem">Cancel</a>
  </div>
</form>
