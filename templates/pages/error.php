<?php
/**
 * Error page — rendered by App::fail() with layout 'bare'.
 * Paper/ink document style, centered.
 * Data: $code (int), $message (string)
 */
?>
<div class="error-wrap">
  <span class="error-stamp">Not found</span>
  <h1><?= e((string) ($code ?? '')) ?></h1>
  <p>That page isn't here.</p>
  <a href="/" class="button ghost">&larr; Back home</a>
</div>
