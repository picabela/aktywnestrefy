<div class="fullmap-wrap">
  <div class="fullmap-bar">
    <strong>Mapa Polski</strong>
    <div class="search-cats">
      <label class="chip"><input type="radio" name="mapkat" value="" checked><span>Wszystko</span></label>
      <?php foreach (CATEGORIES as $slug => $c): ?>
        <label class="chip"><input type="radio" name="mapkat" value="<?= e($slug) ?>"><span><?= e($c['name']) ?></span></label>
      <?php endforeach; ?>
    </div>
  </div>
  <div id="fullMap" class="map fullmap" data-map="full"></div>
</div>
