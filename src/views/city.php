<?php $n = count($places); ?>
<nav class="breadcrumbs" aria-label="Okruszki">
  <a href="/">Start</a> › <a href="/<?= e($catSlug) ?>"><?= e($cat['name']) ?></a> › <span><?= e($cityName) ?></span>
</nav>

<section class="page-head" style="--cat-color: <?= e($cat['color']) ?>">
  <h1><?= e($cat['name']) ?> — <?= e($cityName) ?></h1>
  <p class="lead">W mieście <?= e($cityName) ?> i okolicy znajdziesz <strong><?= $n ?></strong> <?= polish_plural($n, 'obiekt tego typu', 'obiekty tego typu', 'obiektów tego typu') ?>. Kliknij znacznik na mapie albo wybierz z listy poniżej.</p>
</section>

<section class="section">
  <div id="cityMap" class="map map-lg" data-map="static"
       data-places='<?= e(json_encode(array_map(fn($p) => [
            'name' => place_display_name($p),
            'lat' => (float)$p['lat'], 'lon' => (float)$p['lon'],
            'url' => place_url($p), 'category' => $p['category'],
       ], $places), JSON_UNESCAPED_UNICODE)) ?>'></div>
</section>

<?php if ($districts): ?>
<section class="section">
  <h2>Dzielnice</h2>
  <div class="city-cloud">
    <?php foreach ($districts as $d): ?>
      <a class="city-pill" href="#lista" data-filter-district="<?= e($d['district_slug']) ?>"><?= e($d['district']) ?> <small><?= (int)$d['c'] ?></small></a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="section" id="lista">
  <h2>Lista obiektów</h2>
  <div class="place-list">
    <?php foreach ($places as $p): ?>
      <?php $eq = place_equipment($p); ?>
      <a class="place-row" href="<?= e(place_url($p)) ?>" data-district="<?= e($p['district_slug'] ?? '') ?>">
        <div>
          <h3><?= e(place_display_name($p)) ?></h3>
          <p class="muted">
            <?= $p['district'] ? e($p['district']) . ' · ' : '' ?>
            <?php if ($eq): ?><?= e(implode(', ', array_slice($eq, 0, 4))) ?><?= count($eq) > 4 ? '…' : '' ?><?php endif; ?>
            <?php if ($p['lit']): ?> · 💡 oświetlenie<?php endif; ?>
            <?php if (surface_label($p['surface'])): ?> · <?= e(surface_label($p['surface'])) ?><?php endif; ?>
          </p>
        </div>
        <span class="arrow">→</span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="section seo-text">
  <h2><?= e($cat['name']) ?> w mieście <?= e($cityName) ?> — co warto wiedzieć</h2>
  <p><?= e($cat['lead']) ?> Wszystkie obiekty w tym zestawieniu pochodzą z otwartej bazy OpenStreetMap i są ogólnodostępne. Przy każdym obiekcie znajdziesz dokładną lokalizację na mapie, wskazówki dojazdu oraz — jeśli społeczność je uzupełniła — listę sprzętu, typ nawierzchni i informację o oświetleniu, dzięki czemu zaplanujesz trening także po zmroku.</p>
  <p>Znasz miejsce w mieście <?= e($cityName) ?>, którego nie ma na liście? <a href="/o-serwisie">Dodaj je do OpenStreetMap</a> — pojawi się u nas po najbliższej aktualizacji danych.</p>
</section>
