<?php
$total = $counts[$catSlug] ?? 0;
$this_faq = $cat['faq'];
?>
<nav class="breadcrumbs" aria-label="Okruszki">
  <a href="/">Start</a> › <span><?= e($cat['name']) ?></span>
</nav>

<section class="page-head" style="--cat-color: <?= e($cat['color']) ?>">
  <h1><?= e($cat['name']) ?> w Polsce</h1>
  <p class="lead"><?= e($cat['lead']) ?> W katalogu: <strong><?= number_format($total, 0, ',', ' ') ?></strong> <?= polish_plural($total, 'obiekt', 'obiekty', 'obiektów') ?>.</p>
  <form class="search-box compact" action="/szukaj" method="get">
    <input type="hidden" name="kat" value="<?= e($catSlug) ?>">
    <div class="search-row">
      <input type="search" name="q" placeholder="Adres lub miasto…" aria-label="Adres lub miasto">
      <button type="button" class="btn btn-outline" data-geo-kat="<?= e($catSlug) ?>">📍 Blisko mnie</button>
      <button type="submit" class="btn btn-primary">Szukaj</button>
    </div>
  </form>
</section>

<section class="section">
  <div id="catMap" class="map map-lg" data-map="category" data-kat="<?= e($catSlug) ?>"></div>
</section>

<section class="section">
  <h2><?= e($cat['name']) ?> — wybierz miasto</h2>
  <?php if ($cities): ?>
  <div class="city-columns">
    <?php foreach ($cities as $c): ?>
      <a class="city-link" href="<?= e(city_url($catSlug, $c['city_slug'])) ?>">
        <?= e($c['city']) ?> <small>(<?= (int)$c['c'] ?>)</small>
      </a>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <p class="muted">Baza jest właśnie zasilana danymi — uruchom <code>php bin/import.php</code>, aby pobrać obiekty z OpenStreetMap.</p>
  <?php endif; ?>
</section>

<section class="section faq" itemscope itemtype="https://schema.org/FAQPage">
  <h2>Najczęstsze pytania</h2>
  <?php foreach ($this_faq as [$q, $a]): ?>
  <details itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
    <summary itemprop="name"><?= e($q) ?></summary>
    <div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
      <p itemprop="text"><?= e($a) ?></p>
    </div>
  </details>
  <?php endforeach; ?>
</section>
