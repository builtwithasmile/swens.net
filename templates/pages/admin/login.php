<div style="max-width:380px;margin:4rem auto">
<?php if ($flash ?? ''): ?>
<div class="flash"><?= e($flash) ?></div>
<?php endif ?>
<h1 style="font-size:1.25rem;margin-bottom:1.5rem">Admin Login</h1>
<form method="post" action="/admin/login">
  <?= $csrf ?? '' ?>
  <div class="form-group">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required autofocus>
  </div>
  <button type="submit" class="btn btn-primary">Send login link</button>
</form>
</div>
