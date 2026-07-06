<nav class="breadcrumbs" aria-label="Okruszki"><a href="/">Start</a> › <span>Wyszukiwanie</span></nav>

<section class="page-head">
  <h1>Wyniki wyszukiwania</h1>
  <form class="search-box compact" action="/szukaj" method="get" id="searchForm">
    <div class="search-row">
      <input type="search" name="q" id="searchInput" value="<?= e($q) ?>" placeholder="Adres, miasto lub dzielnica…" aria-label="Adres lub miasto">
      <button type="button" class="btn btn-outline" id="geoBtn">📍 Blisko mnie</button>
      <button type="submit" class="btn btn-primary">Szukaj</button>
    </div>
    <div class="search-cats">
      <label class="chip"><input type="radio" name="kat" value="" <?= $kat === null ? 'checked' : '' ?>><span>Wszystko</span></label>
      <?php foreach (CATEGORIES as $slug => $c): ?>
        <label class="chip"><input type="radio" name="kat" value="<?= e($slug) ?>" <?= $kat === $slug ? 'checked' : '' ?>><span><?= e($c['name']) ?></span></label>
      <?php endforeach; ?>
    </div>
    <p class="geo-status" id="geoStatus" role="status"></p>
  </form>
</section>

<?php if ($mode === 'text' && $q !== '' && !$results): ?>
  <p class="muted">Nie znaleźliśmy obiektów pasujących do „<?= e($q) ?>". Wskazówka: kliknij <strong>📍 Blisko mnie</strong> albo wpisz adres — zamienimy go na współrzędne i poszukamy w promieniu 25 km.</p>
<?php endif; ?>

<?php if ($results): ?>
<div class="results-layout">
  <div id="resultsMap" class="map map-lg" data-map="static"
       data-places='<?= e(json_encode(array_map(fn($p) => [
            'name' => place_display_name($p),
            'lat' => (float)$p['lat'], 'lon' => (float)$p['lon'],
            'url' => place_url($p), 'category' => $p['category'],
       ], $results), JSON_UNESCAPED_UNICODE)) ?>'
       <?php if ($lat && $lng): ?>data-user-lat="<?= $lat ?>" data-user-lon="<?= $lng ?>"<?php endif; ?>></div>

  <div class="place-list">
    <?php foreach ($results as $p): ?>
      <?php $cc = CATEGORIES[$p['category']]; ?>
      <a class="place-row" href="<?= e(place_url($p)) ?>">
        <div>
          <span class="badge" style="--cat-color: <?= e($cc['color']) ?>"><?= e($cc['singular']) ?></span>
          <h3><?= e(place_display_name($p)) ?></h3>
          <p class="muted">
            <?= e($p['city'] ?: 'Polska') ?><?= $p['district'] ? ' · ' . e($p['district']) : '' ?>
            <?php if (isset($p['distance_km'])): ?> · 📍 <?= e(format_distance($p['distance_km'])) ?><?php endif; ?>
          </p>
        </div>
        <span class="arrow">→</span>
      </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
