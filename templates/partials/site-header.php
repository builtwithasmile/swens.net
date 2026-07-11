<?php
/**
 * Interior walk-back bar. Shown on every page except the home page
 * (the layout skips it when $active === 'home'). Left: back to swens.net.
 * Right: where you are. No menu, by design.
 */
$here = \App\Services\Buildings::ALL[$active ?? '']['name'] ?? 'swens.net';
?>
<header class="site-header">
  <nav aria-label="Wayfinding">
    <a class="walk-back" href="/">&larr; swens.net</a>
  </nav>
  <span class="site-here"><?= e($here) ?></span>
</header>
