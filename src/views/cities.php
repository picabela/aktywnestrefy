<nav class="breadcrumbs" aria-label="Okruszki"><a href="/">Start</a> › <span>Miasta</span></nav>

<section class="page-head">
  <h1>Miejsca aktywności według miast</h1>
  <p class="lead">Wybierz miasto, aby zobaczyć siłownie plenerowe, parki kalisteniki, skateparki i pumptracki w okolicy.</p>
</section>

<section class="section">
  <?php if ($cities): ?>
  <div class="city-columns">
    <?php foreach ($cities as $c): ?>
      <a class="city-link" href="/silownie-plenerowe/<?= e($c['city_slug']) ?>"><?= e($c['city']) ?> <small>(<?= (int)$c['c'] ?>)</small></a>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <p class="muted">Baza jest pusta — uruchom <code>php bin/import.php</code>, aby zaimportować dane z OpenStreetMap.</p>
  <?php endif; ?>
</section>
