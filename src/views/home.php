<section class="hero">
  <h1>Znajdź swoją <span class="accent-red">aktywną</span> <span class="accent-green">strefę</span></h1>
  <p class="hero-lead">Siłownie plenerowe, parki kalisteniki, skateparki i pumptracki — <strong><?= number_format($total, 0, ',', ' ') ?></strong> miejsc do darmowego treningu na świeżym powietrzu w całej Polsce.</p>

  <form class="search-box" action="/szukaj" method="get" id="searchForm">
    <div class="search-row">
      <input type="search" name="q" id="searchInput" placeholder="Wpisz adres, miasto lub dzielnicę…" autocomplete="off" aria-label="Adres lub miasto">
      <button type="button" class="btn btn-outline" id="geoBtn" title="Użyj mojej lokalizacji">📍 Blisko mnie</button>
      <button type="submit" class="btn btn-primary">Szukaj</button>
    </div>
    <div class="search-cats">
      <label class="chip"><input type="radio" name="kat" value="" checked><span>Wszystko</span></label>
      <?php foreach (CATEGORIES as $slug => $c): ?>
        <label class="chip"><input type="radio" name="kat" value="<?= e($slug) ?>"><span><?= e($c['name']) ?></span></label>
      <?php endforeach; ?>
    </div>
    <p class="geo-status" id="geoStatus" role="status"></p>
  </form>
</section>

<section class="cat-grid">
  <?php foreach (CATEGORIES as $slug => $c): ?>
  <a class="cat-card" href="/<?= e($slug) ?>" style="--cat-color: <?= e($c['color']) ?>">
    <span class="cat-icon" data-icon="<?= e($c['icon']) ?>"></span>
    <h2><?= e($c['name']) ?></h2>
    <p><?= e($c['lead']) ?></p>
    <span class="cat-count"><?= number_format($counts[$slug] ?? 0, 0, ',', ' ') ?> <?= polish_plural($counts[$slug] ?? 0, 'obiekt', 'obiekty', 'obiektów') ?> →</span>
  </a>
  <?php endforeach; ?>
</section>

<?php if ($topCities): ?>
<section class="section">
  <h2>Popularne miasta</h2>
  <div class="city-cloud">
    <?php foreach ($topCities as $c): ?>
      <a class="city-pill" href="/silownie-plenerowe/<?= e($c['city_slug']) ?>"><?= e($c['city']) ?> <small><?= (int)$c['c'] ?></small></a>
    <?php endforeach; ?>
  </div>
  <p><a href="/miasta">Zobacz wszystkie miasta →</a></p>
</section>
<?php endif; ?>

<section class="section how-it-works">
  <h2>Jak to działa?</h2>
  <div class="steps">
    <div class="step"><span class="step-no">1</span><h3>Wpisz adres lub kliknij „Blisko mnie"</h3><p>Wyszukiwarka znajdzie obiekty w promieniu do 25 km od wskazanego miejsca i posortuje je po odległości.</p></div>
    <div class="step"><span class="step-no">2</span><h3>Sprawdź szczegóły obiektu</h3><p>Lista sprzętu, nawierzchnia, oświetlenie, podgląd okolicy w Street View i wskazówki dojazdu.</p></div>
    <div class="step"><span class="step-no">3</span><h3>Trenuj i oceniaj</h3><p>Dodaj opinię i pomóż innym wybrać najlepsze miejsce do treningu w okolicy.</p></div>
  </div>
</section>

<?php if ($latest): ?>
<section class="section">
  <h2>Ostatnio zaktualizowane</h2>
  <div class="place-grid">
    <?php foreach ($latest as $p): ?>
      <?php $cc = CATEGORIES[$p['category']]; ?>
      <a class="place-card" href="<?= e(place_url($p)) ?>">
        <span class="badge" style="--cat-color: <?= e($cc['color']) ?>"><?= e($cc['singular']) ?></span>
        <h3><?= e(place_display_name($p)) ?></h3>
        <p class="muted"><?= e($p['city'] ?: 'Polska') ?><?= $p['district'] ? ' · ' . e($p['district']) : '' ?></p>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="section osm-note">
  <p>🌍 Dane pochodzą z <a href="https://www.openstreetmap.org" rel="noopener">OpenStreetMap</a> — największej otwartej mapy świata, tworzonej przez społeczność. Widzisz błąd? <a href="/o-serwisie">Dowiedz się, jak go poprawić</a> — pomożesz wszystkim użytkownikom.</p>
</section>
