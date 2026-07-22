<?php
/**
 * /mycryptowatch — personal top-10 crypto watch list.
 * Not a site page: no header/footer chrome (bare layout, noindex). Every
 * request re-fetches CoinGecko's public markets endpoint — no cache, no
 * accounts, just whatever the price was when the page loaded.
 */
$coins = $coins ?? [];
?>
<style>
  .cw-table-wrap { overflow-x: auto; margin-top: 28px; }
  .cw-table { width: 100%; border-collapse: collapse; font-size: 15px; }
  .cw-table th { text-align: left; font-family: var(--font-mono); font-size: 11px; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); font-weight: 400; padding: 0 12px 10px; border-bottom: 1px solid var(--line); white-space: nowrap; }
  .cw-table td { padding: 14px 12px; border-bottom: 1px solid var(--line); vertical-align: middle; white-space: nowrap; }
  .cw-table th:not(:first-child), .cw-table td:not(:first-child) { text-align: right; }
  .cw-rank { color: var(--muted); font-family: var(--font-mono); font-size: 13px; }
  .cw-coin { display: flex; align-items: center; gap: 10px; }
  .cw-coin img { width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0; }
  .cw-symbol { color: var(--muted); text-transform: uppercase; font-size: 12px; }
  .cw-change { font-family: var(--font-mono); font-size: 13px; }
  .cw-refresh { color: var(--muted); font-size: 13px; text-decoration: underline; text-underline-offset: 3px; }
  @media (max-width: 480px) {
    .cw-table { font-size: 13px; }
    .cw-table th, .cw-table td { padding: 10px 8px; }
    .cw-symbol { display: none; }
  }
</style>

<section class="section-pad narrow">
  <p class="eyebrow">Crypto Watch</p>
  <h1 class="h2-display">Top 10 by market cap</h1>
  <p class="lead">
    Updated <?= e($updated) ?> ·
    <a class="cw-refresh" href="/mycryptowatch">↻ refresh</a>
  </p>

  <?php if ($error): ?>
    <p class="lead"><?= e($error) ?></p>
  <?php else: ?>
  <div class="cw-table-wrap">
  <table class="cw-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Coin</th>
        <th>Price (CAD)</th>
        <th>24h</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($coins as $i => $c): ?>
      <tr>
        <td class="cw-rank"><?= (int) ($i + 1) ?></td>
        <td>
          <div class="cw-coin">
            <?php if (!empty($c['image'])): ?><img src="<?= e($c['image']) ?>" alt=""><?php endif; ?>
            <span><?= e($c['name'] ?? '') ?></span>
            <span class="cw-symbol"><?= e($c['symbol'] ?? '') ?></span>
          </div>
        </td>
        <?php
          $price = (float) ($c['current_price'] ?? 0);
          // Sub-dollar coins (DOGE, ADA, ...) need more precision or they all read as $0.00.
          $priceStr = '$' . number_format($price, $price >= 1 ? 2 : ($price >= 0.01 ? 4 : 6));
          $chg = (float) ($c['price_change_percentage_24h'] ?? 0);
        ?>
        <td><?= e($priceStr) ?></td>
        <td class="cw-change"><?= $chg >= 0 ? '▲' : '▼' ?> <?= e(number_format(abs($chg), 2)) ?>%</td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</section>
