<?php
$lat = (float)$place['lat'];
$lon = (float)$place['lon'];
$gmapsDir  = "https://www.google.com/maps/dir/?api=1&destination={$lat},{$lon}";
$streetView = "https://www.google.com/maps/@?api=1&map_action=pano&viewpoint={$lat},{$lon}";
$mapillary = "https://www.mapillary.com/app/?lat={$lat}&lng={$lon}&z=17";
$osmEdit   = "https://www.openstreetmap.org/edit?" . e($place['osm_type']) . "=" . (int)$place['osm_id'];
$osmView   = "https://www.openstreetmap.org/" . e($place['osm_type']) . "/" . (int)$place['osm_id'];

$jsonld = [
    '@context' => 'https://schema.org',
    '@type'    => 'SportsActivityLocation',
    'name'     => $displayName,
    'url'      => APP_URL . place_url($place),
    'geo'      => ['@type' => 'GeoCoordinates', 'latitude' => $lat, 'longitude' => $lon],
    'isAccessibleForFree' => true,
];
if ($place['city'] || $place['address']) {
    $jsonld['address'] = array_filter([
        '@type'           => 'PostalAddress',
        'streetAddress'   => $place['address'] ?: null,
        'addressLocality' => $place['city'] ?: null,
        'addressCountry'  => 'PL',
    ]);
}
if ($rating['count'] > 0) {
    $jsonld['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => $rating['avg'], 'reviewCount' => $rating['count']];
}
?>
<script type="application/ld+json"><?= json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

<nav class="breadcrumbs" aria-label="Okruszki">
  <a href="/">Start</a> ›
  <a href="/<?= e($catSlug) ?>"><?= e($cat['name']) ?></a> ›
  <?php if ($place['city_slug']): ?><a href="<?= e(city_url($catSlug, $place['city_slug'])) ?>"><?= e($place['city']) ?></a> ›<?php endif; ?>
  <span><?= e($displayName) ?></span>
</nav>

<section class="page-head" style="--cat-color: <?= e($cat['color']) ?>">
  <span class="badge" style="--cat-color: <?= e($cat['color']) ?>"><?= e($cat['singular']) ?></span>
  <h1><?= e($displayName) ?></h1>
  <p class="lead">
    <?php if ($place['address']): ?>📍 <?= e($place['address']) ?>, <?php endif; ?><?= $place['district'] ? e($place['district']) . ', ' : '' ?><?= e($place['city'] ?: 'Polska') ?>
    <?php if ($rating['count'] > 0): ?>
      · ⭐ <?= number_format($rating['avg'], 1, ',', '') ?>/5 (<?= $rating['count'] ?> <?= polish_plural($rating['count'], 'opinia', 'opinie', 'opinii') ?>)
    <?php endif; ?>
    · <button type="button" class="fav-btn" data-fav-slug="<?= e($place['slug']) ?>" data-fav-name="<?= e($displayName) ?>" data-fav-url="<?= e(place_url($place)) ?>">☆ Zapisz</button>
  </p>
</section>

<?php if (isset($_GET['dziekujemy'])): ?><p class="flash">✅ Dziękujemy za opinię!</p><?php endif; ?>
<?php if (isset($_GET['zgloszono'])): ?><p class="flash">✅ Zgłoszenie przyjęte — dzięki za pomoc!</p><?php endif; ?>

<div class="place-layout">
  <div class="place-main">
    <div id="placeMap" class="map map-lg" data-map="place" data-lat="<?= $lat ?>" data-lon="<?= $lon ?>" data-name="<?= e($displayName) ?>" data-kat="<?= e($catSlug) ?>"></div>

    <div class="action-row">
      <a class="btn btn-primary" href="<?= $gmapsDir ?>" rel="noopener" target="_blank">🧭 Nawiguj</a>
      <a class="btn btn-outline" href="<?= $streetView ?>" rel="noopener" target="_blank">👁 Street View</a>
      <a class="btn btn-outline" href="<?= $mapillary ?>" rel="noopener" target="_blank">📷 Mapillary</a>
      <button type="button" class="btn btn-outline" data-share data-share-title="<?= e($displayName) ?>">↗ Udostępnij</button>
    </div>

    <section class="section place-desc">
      <h2>O tym miejscu</h2>
      <?php foreach ($description as $par): ?>
        <p><?= e($par) ?></p>
      <?php endforeach; ?>
    </section>

    <?php if ($equipment): ?>
    <section class="section">
      <h2>Sprzęt i wyposażenie</h2>
      <ul class="equipment-list">
        <?php foreach ($equipment as $item): ?><li><?= e($item) ?></li><?php endforeach; ?>
      </ul>
    </section>
    <?php endif; ?>

    <section class="section">
      <h2>Informacje</h2>
      <dl class="info-grid">
        <?php if ($place['address']): ?><dt>Adres</dt><dd><?= e($place['address']) ?><?= $place['city'] ? ', ' . e($place['city']) : '' ?></dd>
        <?php elseif ($place['city']): ?><dt>Lokalizacja</dt><dd><?= $place['district'] ? e($place['district']) . ', ' : '' ?><?= e($place['city']) ?></dd><?php endif; ?>
        <?php if (surface_label($place['surface'])): ?><dt>Nawierzchnia</dt><dd><?= e(surface_label($place['surface'])) ?></dd><?php endif; ?>
        <?php if ($place['lit'] !== null): ?><dt>Oświetlenie</dt><dd><?= $place['lit'] ? '💡 tak — potrenujesz po zmroku' : 'brak' ?></dd><?php endif; ?>
        <?php if ($place['covered'] !== null): ?><dt>Zadaszenie</dt><dd><?= $place['covered'] ? 'tak' : 'nie' ?></dd><?php endif; ?>
        <?php if ($place['opening_hours']): ?><dt>Godziny otwarcia</dt><dd><?= e($place['opening_hours']) ?></dd><?php endif; ?>
        <?php if ($place['website']): ?><dt>Strona WWW</dt><dd><a href="<?= e($place['website']) ?>" rel="nofollow noopener" target="_blank"><?= e(parse_url($place['website'], PHP_URL_HOST) ?: $place['website']) ?></a></dd><?php endif; ?>
        <dt>Współrzędne</dt><dd><code><?= number_format($lat, 5, '.', '') ?>, <?= number_format($lon, 5, '.', '') ?></code></dd>
        <dt>Dostęp</dt><dd>obiekt ogólnodostępny, bezpłatny</dd>
      </dl>
    </section>

    <section class="section" id="pogoda">
      <h2>Pogoda na trening</h2>
      <div class="weather-widget" data-weather data-lat="<?= $lat ?>" data-lon="<?= $lon ?>">
        <p class="muted">Ładowanie prognozy…</p>
      </div>
    </section>

    <section class="section faq">
      <h2>Pytania i odpowiedzi</h2>
      <?php foreach ($faq as [$q, $a]): ?>
      <details>
        <summary><?= e($q) ?></summary>
        <p><?= e($a) ?></p>
      </details>
      <?php endforeach; ?>
    </section>

    <section class="section" id="opinie">
      <h2>Opinie trenujących</h2>
      <?php if ($reviews): ?>
        <div class="reviews">
        <?php foreach ($reviews as $r): ?>
          <article class="review">
            <header><strong><?= e($r['author']) ?></strong> <span class="stars" aria-label="<?= (int)$r['rating'] ?> na 5"><?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?></span> <time class="muted"><?= e(substr($r['created_at'], 0, 10)) ?></time></header>
            <?php if ($r['body']): ?><p><?= nl2br(e($r['body'])) ?></p><?php endif; ?>
          </article>
        <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="muted">Nikt jeszcze nie ocenił tego miejsca. Trenowałeś tu? Podziel się wrażeniami!</p>
      <?php endif; ?>

      <form class="review-form" method="post" action="/zglos">
        <h3>Dodaj opinię</h3>
        <input type="hidden" name="type" value="review">
        <input type="hidden" name="place_id" value="<?= (int)$place['id'] ?>">
        <input type="hidden" name="back" value="<?= e(place_url($place)) ?>">
        <input type="hidden" name="ts" value="<?= time() ?>">
        <input type="text" name="website_url" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
        <div class="form-row">
          <label>Imię / nick <input type="text" name="author" required maxlength="60"></label>
          <label>Ocena
            <select name="rating" required>
              <option value="5">★★★★★ — świetne</option>
              <option value="4">★★★★☆ — dobre</option>
              <option value="3">★★★☆☆ — w porządku</option>
              <option value="2">★★☆☆☆ — słabe</option>
              <option value="1">★☆☆☆☆ — do remontu</option>
            </select>
          </label>
        </div>
        <label>Komentarz (opcjonalnie) <textarea name="body" rows="3" maxlength="2000" placeholder="Stan sprzętu, tłok, atmosfera…"></textarea></label>
        <button type="submit" class="btn btn-primary">Wyślij opinię</button>
      </form>
    </section>

    <details class="report-box">
      <summary>⚠ Zgłoś błąd w danych tego obiektu</summary>
      <form method="post" action="/zglos">
        <input type="hidden" name="type" value="report">
        <input type="hidden" name="place_id" value="<?= (int)$place['id'] ?>">
        <input type="hidden" name="back" value="<?= e(place_url($place)) ?>">
        <input type="hidden" name="ts" value="<?= time() ?>">
        <input type="text" name="website_url" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
        <label>Rodzaj problemu
          <select name="kind">
            <option value="nie-istnieje">Obiekt nie istnieje</option>
            <option value="zla-lokalizacja">Zła lokalizacja</option>
            <option value="zly-sprzet">Błędna lista sprzętu</option>
            <option value="inne">Inne</option>
          </select>
        </label>
        <label>Opis <textarea name="body" rows="2" required maxlength="2000"></textarea></label>
        <button type="submit" class="btn btn-outline">Wyślij zgłoszenie</button>
      </form>
      <p class="muted">Możesz też <a href="<?= $osmEdit ?>" rel="noopener" target="_blank">poprawić obiekt bezpośrednio w OpenStreetMap</a> — zmiana trafi do wszystkich map na świecie. <a href="<?= $osmView ?>" rel="noopener" target="_blank">Zobacz obiekt w OSM</a>.</p>
    </details>
  </div>

  <aside class="place-side">
    <?php if ($nearby): ?>
    <section class="side-box">
      <h2>W pobliżu</h2>
      <?php foreach ($nearby as $np): ?>
        <a class="nearby-item" href="<?= e(place_url($np)) ?>">
          <span><?= e(place_display_name($np)) ?></span>
          <small class="muted"><?= e(format_distance($np['distance_km'])) ?></small>
        </a>
      <?php endforeach; ?>
    </section>
    <?php endif; ?>
    <section class="side-box">
      <h2>Ulubione</h2>
      <div id="favList"><p class="muted">Zapisane miejsca trzymamy w Twojej przeglądarce — bez zakładania konta.</p></div>
    </section>
  </aside>
</div>
