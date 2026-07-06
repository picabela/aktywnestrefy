/* AktywneStrefy.pl — frontend */
(function () {
  'use strict';

  var CAT_COLORS = {
    'silownie-plenerowe': '#e0332c',
    'parki-kalisteniki': '#0d7a3f',
    'skateparki': '#1a1a1a',
    'pumptracki': '#e07b00'
  };

  /* ── Tryb ciemny ─────────────────────────────────────── */
  var root = document.documentElement;
  var stored = localStorage.getItem('theme');
  if (stored) {
    root.dataset.theme = stored;
  } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
    root.dataset.theme = 'dark';
  }
  document.addEventListener('click', function (e) {
    if (e.target.id === 'themeToggle') {
      var next = root.dataset.theme === 'dark' ? 'light' : 'dark';
      root.dataset.theme = next;
      localStorage.setItem('theme', next);
    }
    if (e.target.id === 'navToggle') {
      document.getElementById('mainNav').classList.toggle('open');
    }
  });

  /* ── Wyszukiwarka: geolokalizacja + geokodowanie ─────── */
  function setStatus(msg) {
    var el = document.getElementById('geoStatus');
    if (el) el.textContent = msg || '';
  }

  function selectedCat(form) {
    var checked = form.querySelector('input[name="kat"]:checked');
    return checked ? checked.value : '';
  }

  function goToCoords(lat, lng, kat, label) {
    var url = '/szukaj?lat=' + lat.toFixed(5) + '&lng=' + lng.toFixed(5);
    if (kat) url += '&kat=' + encodeURIComponent(kat);
    if (label) url += '&q=' + encodeURIComponent(label);
    window.location.href = url;
  }

  var geoBtn = document.getElementById('geoBtn');
  if (geoBtn) {
    geoBtn.addEventListener('click', function () {
      var form = geoBtn.closest('form');
      if (!navigator.geolocation) { setStatus('Twoja przeglądarka nie obsługuje geolokalizacji.'); return; }
      setStatus('Ustalam Twoją lokalizację…');
      navigator.geolocation.getCurrentPosition(
        function (pos) { goToCoords(pos.coords.latitude, pos.coords.longitude, selectedCat(form)); },
        function () { setStatus('Nie udało się pobrać lokalizacji. Sprawdź uprawnienia przeglądarki.'); },
        { enableHighAccuracy: true, timeout: 10000 }
      );
    });
  }

  // Przycisk „blisko mnie" na stronach kategorii
  document.querySelectorAll('[data-geo-kat]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!navigator.geolocation) return;
      navigator.geolocation.getCurrentPosition(function (pos) {
        goToCoords(pos.coords.latitude, pos.coords.longitude, btn.dataset.geoKat);
      });
    });
  });

  // Wysłanie formularza z adresem → geokodowanie Nominatim
  var searchForm = document.getElementById('searchForm');
  if (searchForm) {
    searchForm.addEventListener('submit', function (e) {
      var q = (document.getElementById('searchInput') || {}).value || '';
      q = q.trim();
      if (!q) return; // pusty — pozwól PHP obsłużyć
      e.preventDefault();
      setStatus('Szukam adresu „' + q + '"…');
      var url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&countrycodes=pl&limit=1&q=' + encodeURIComponent(q);
      fetch(url, { headers: { 'Accept-Language': 'pl' } })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res && res.length) {
            goToCoords(parseFloat(res[0].lat), parseFloat(res[0].lon), selectedCat(searchForm), q);
          } else {
            // Brak trafienia w geokoderze — spadamy na wyszukiwanie tekstowe
            searchForm.submit();
          }
        })
        .catch(function () { searchForm.submit(); });
    });
  }

  /* ── Mapy Leaflet ────────────────────────────────────── */
  function markerIcon(category) {
    var color = CAT_COLORS[category] || '#0d7a3f';
    return L.divIcon({
      className: '',
      html: '<div class="map-marker" style="background:' + color + '"></div>',
      iconSize: [26, 26],
      iconAnchor: [13, 24],
      popupAnchor: [0, -22]
    });
  }

  function baseMap(el, center, zoom) {
    var map = L.map(el, { scrollWheelZoom: el.dataset.map === 'full' }).setView(center, zoom);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);
    return map;
  }

  function popupHtml(p) {
    return '<strong>' + escapeHtml(p.name) + '</strong><br>' +
      (p.city ? escapeHtml(p.city) + '<br>' : '') +
      '<a href="' + p.url + '">Zobacz szczegóły →</a>';
  }

  function escapeHtml(s) {
    return String(s || '').replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function initMaps() {
    document.querySelectorAll('[data-map]').forEach(function (el) {
      var kind = el.dataset.map;

      if (kind === 'place') {
        var lat = parseFloat(el.dataset.lat), lon = parseFloat(el.dataset.lon);
        var map = baseMap(el, [lat, lon], 16);
        L.marker([lat, lon], { icon: markerIcon(el.dataset.kat) })
          .addTo(map).bindPopup('<strong>' + escapeHtml(el.dataset.name) + '</strong>');
      }

      if (kind === 'static') {
        var places = JSON.parse(el.dataset.places || '[]');
        if (!places.length) return;
        var map = baseMap(el, [52, 19], 6);
        var bounds = [];
        places.forEach(function (p) {
          L.marker([p.lat, p.lon], { icon: markerIcon(p.category) }).addTo(map).bindPopup(popupHtml(p));
          bounds.push([p.lat, p.lon]);
        });
        if (el.dataset.userLat) {
          var u = [parseFloat(el.dataset.userLat), parseFloat(el.dataset.userLon)];
          L.circleMarker(u, { radius: 8, color: '#1565c0', fillOpacity: .9 })
            .addTo(map).bindPopup('Twoja lokalizacja');
          bounds.push(u);
        }
        map.fitBounds(bounds, { padding: [30, 30], maxZoom: 14 });
      }

      if (kind === 'category' || kind === 'full') {
        var map = baseMap(el, [52.07, 19.48], 6);
        var layer = L.layerGroup().addTo(map);
        var kat = el.dataset.kat || '';

        function load() {
          if (map.getZoom() < 8) { layer.clearLayers(); return; }
          var b = map.getBounds();
          var url = '/api/bbox?bbox=' + [b.getSouth(), b.getWest(), b.getNorth(), b.getEast()].join(',');
          if (kat) url += '&kat=' + encodeURIComponent(kat);
          fetch(url).then(function (r) { return r.json(); }).then(function (data) {
            layer.clearLayers();
            (data.places || []).forEach(function (p) {
              L.marker([p.lat, p.lon], { icon: markerIcon(p.category) }).addTo(layer).bindPopup(popupHtml(p));
            });
          });
        }
        map.on('moveend', load);
        load();

        // Filtr kategorii na pełnej mapie
        document.querySelectorAll('input[name="mapkat"]').forEach(function (radio) {
          radio.addEventListener('change', function () { kat = radio.value; load(); });
        });

        if (kind === 'category') {
          // Wskocz do lokalizacji użytkownika, jeśli pozwoli
          if (navigator.geolocation && navigator.permissions) {
            navigator.permissions.query({ name: 'geolocation' }).then(function (st) {
              if (st.state === 'granted') {
                navigator.geolocation.getCurrentPosition(function (pos) {
                  map.setView([pos.coords.latitude, pos.coords.longitude], 12);
                });
              }
            });
          }
        }
      }
    });
  }

  /* ── Pogoda (Open-Meteo, bez klucza API) ─────────────── */
  var WMO = [
    [0, '☀️', 'bezchmurnie'], [1, '🌤', 'przeważnie słonecznie'], [2, '⛅', 'częściowe zachmurzenie'],
    [3, '☁️', 'pochmurno'], [45, '🌫', 'mgła'], [48, '🌫', 'mgła osadzająca'],
    [51, '🌦', 'mżawka'], [53, '🌦', 'mżawka'], [55, '🌧', 'gęsta mżawka'],
    [61, '🌧', 'słaby deszcz'], [63, '🌧', 'deszcz'], [65, '🌧', 'ulewa'],
    [71, '🌨', 'słaby śnieg'], [73, '🌨', 'śnieg'], [75, '❄️', 'intensywny śnieg'],
    [80, '🌦', 'przelotne opady'], [81, '🌧', 'przelotny deszcz'], [82, '⛈', 'silne opady'],
    [95, '⛈', 'burza'], [96, '⛈', 'burza z gradem'], [99, '⛈', 'burza z gradem']
  ];
  function wmoInfo(code) {
    var best = ['❓', ''];
    WMO.forEach(function (w) { if (code >= w[0]) best = [w[1], w[2]]; });
    return best;
  }

  function initWeather() {
    var el = document.querySelector('[data-weather]');
    if (!el) return;
    var url = 'https://api.open-meteo.com/v1/forecast?latitude=' + el.dataset.lat +
      '&longitude=' + el.dataset.lon +
      '&daily=weather_code,temperature_2m_max,precipitation_probability_max&timezone=Europe%2FWarsaw&forecast_days=4';
    fetch(url).then(function (r) { return r.json(); }).then(function (d) {
      if (!d.daily) { el.innerHTML = ''; return; }
      var labels = ['Dziś', 'Jutro', 'Pojutrze'];
      var html = '';
      for (var i = 0; i < Math.min(4, d.daily.time.length); i++) {
        var info = wmoInfo(d.daily.weather_code[i]);
        var day = labels[i] || new Date(d.daily.time[i]).toLocaleDateString('pl-PL', { weekday: 'short' });
        html += '<div class="weather-day"><div class="wd-label">' + day + '</div>' +
          '<div class="wd-icon" title="' + info[1] + '">' + info[0] + '</div>' +
          '<div class="wd-temp">' + Math.round(d.daily.temperature_2m_max[i]) + '°C</div>' +
          '<div class="wd-label">☔ ' + (d.daily.precipitation_probability_max[i] || 0) + '%</div></div>';
      }
      var today = d.daily;
      var good = (today.precipitation_probability_max[0] || 0) < 40 && today.temperature_2m_max[0] > 2;
      html += '<p class="weather-verdict">' + (good
        ? '💪 Dobre warunki — idealny dzień na trening na świeżym powietrzu!'
        : '🧥 Warunki wymagające — ubierz się odpowiednio albo sprawdź prognozę na jutro.') + '</p>';
      el.innerHTML = html;
    }).catch(function () { el.innerHTML = ''; });
  }

  /* ── Ulubione (localStorage) ─────────────────────────── */
  function getFavs() {
    try { return JSON.parse(localStorage.getItem('favs') || '[]'); } catch (e) { return []; }
  }
  function initFavs() {
    var btn = document.querySelector('[data-fav-slug]');
    if (btn) {
      var favs = getFavs();
      var isFav = favs.some(function (f) { return f.slug === btn.dataset.favSlug; });
      render(isFav);
      btn.addEventListener('click', function () {
        var favs = getFavs();
        var idx = favs.findIndex(function (f) { return f.slug === btn.dataset.favSlug; });
        if (idx >= 0) { favs.splice(idx, 1); render(false); }
        else {
          favs.push({ slug: btn.dataset.favSlug, name: btn.dataset.favName, url: btn.dataset.favUrl });
          render(true);
        }
        localStorage.setItem('favs', JSON.stringify(favs));
        renderFavList();
      });
    }
    renderFavList();
    function render(active) {
      btn.classList.toggle('active', active);
      btn.textContent = active ? '★ Zapisano' : '☆ Zapisz';
    }
  }
  function renderFavList() {
    var box = document.getElementById('favList');
    if (!box) return;
    var favs = getFavs();
    if (!favs.length) return;
    box.innerHTML = favs.map(function (f) {
      return '<a href="' + f.url + '">★ ' + escapeHtml(f.name) + '</a>';
    }).join('');
  }

  /* ── Udostępnianie ───────────────────────────────────── */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-share]');
    if (!btn) return;
    if (navigator.share) {
      navigator.share({ title: btn.dataset.shareTitle, url: location.href });
    } else {
      navigator.clipboard.writeText(location.href).then(function () {
        btn.textContent = '✓ Skopiowano link';
      });
    }
  });

  /* ── Filtr dzielnic na stronie miasta ────────────────── */
  document.querySelectorAll('[data-filter-district]').forEach(function (pill) {
    pill.addEventListener('click', function () {
      var slug = pill.dataset.filterDistrict;
      var active = pill.classList.toggle('active-filter');
      document.querySelectorAll('[data-filter-district]').forEach(function (p) {
        if (p !== pill) p.classList.remove('active-filter');
      });
      document.querySelectorAll('.place-row[data-district]').forEach(function (row) {
        row.classList.toggle('hidden', active && row.dataset.district !== slug);
      });
    });
  });

  /* ── Init ────────────────────────────────────────────── */
  function boot() {
    if (window.L) initMaps();
    initWeather();
    initFavs();
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
