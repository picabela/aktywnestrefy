<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? APP_NAME) ?></title>
<meta name="description" content="<?= e($meta_description ?? '') ?>">
<?php if (!empty($canonical)): ?><link rel="canonical" href="<?= e($canonical) ?>"><?php endif; ?>
<?php if (!empty($robots)): ?><meta name="robots" content="<?= e($robots) ?>"><?php endif; ?>
<meta property="og:title" content="<?= e($title ?? APP_NAME) ?>">
<meta property="og:description" content="<?= e($meta_description ?? '') ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= APP_NAME ?>">
<?php if (!empty($canonical)): ?><meta property="og:url" content="<?= e($canonical) ?>"><?php endif; ?>
<link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
<link rel="stylesheet" href="/assets/vendor/leaflet/leaflet.css">
<link rel="stylesheet" href="/assets/css/style.css?v=1">
<script defer src="/assets/vendor/leaflet/leaflet.js"></script>
<script defer src="/assets/js/app.js?v=1"></script>
<?php if (!empty($jsonld)): ?>
<script type="application/ld+json"><?= json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <a href="/" class="logo" aria-label="<?= APP_NAME ?> — strona główna">
      <img src="/assets/img/logo.svg" alt="Aktywne Strefy" width="190" height="56">
    </a>
    <nav class="main-nav" id="mainNav">
      <?php foreach (CATEGORIES as $slug => $c): ?>
        <a href="/<?= e($slug) ?>"><?= e($c['name']) ?></a>
      <?php endforeach; ?>
      <a href="/mapa">Mapa</a>
      <a href="/miasta">Miasta</a>
    </nav>
    <div class="header-actions">
      <button id="themeToggle" class="icon-btn" title="Tryb ciemny/jasny" aria-label="Przełącz motyw">◐</button>
      <button id="navToggle" class="icon-btn nav-toggle" aria-label="Menu">☰</button>
    </div>
  </div>
</header>

<main class="<?= !empty($fullwidth) ? 'fullwidth' : 'container' ?>">
<?= $content ?? '' ?>
</main>

<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <strong>Aktywne Strefy</strong>
      <p>Katalog i wyszukiwarka miejsc do aktywności fizycznej na wolnym powietrzu w całej Polsce. Trenuj tam, gdzie jesteś.</p>
    </div>
    <div>
      <strong>Kategorie</strong>
      <?php foreach (CATEGORIES as $slug => $c): ?>
        <a href="/<?= e($slug) ?>"><?= e($c['name']) ?></a>
      <?php endforeach; ?>
    </div>
    <div>
      <strong>Serwis</strong>
      <a href="/mapa">Mapa Polski</a>
      <a href="/miasta">Wszystkie miasta</a>
      <a href="/o-serwisie">O serwisie i danych</a>
      <a href="/sitemap.xml">Mapa strony</a>
    </div>
    <div>
      <strong>Dane</strong>
      <p>Źródłem danych o obiektach jest <a href="https://www.openstreetmap.org" rel="noopener">OpenStreetMap</a> — © autorzy OSM, licencja <a href="https://opendatacommons.org/licenses/odbl/" rel="noopener">ODbL</a>.</p>
    </div>
  </div>
  <div class="container footer-bottom">
    © <?= date('Y') ?> <?= APP_DOMAIN ?> · Zrobione z 💪 dla aktywnych
  </div>
</footer>
</body>
</html>
