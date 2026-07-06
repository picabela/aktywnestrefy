<nav class="breadcrumbs" aria-label="Okruszki"><a href="/">Start</a> › <span>O serwisie</span></nav>

<section class="page-head">
  <h1>O serwisie Aktywne Strefy</h1>
  <p class="lead">Pomagamy znaleźć darmowe miejsca do treningu na świeżym powietrzu — w Twojej dzielnicy i w całej Polsce.</p>
</section>

<section class="section prose">
  <h2>Skąd mamy dane?</h2>
  <p>Wszystkie obiekty w katalogu pochodzą z <a href="https://www.openstreetmap.org" rel="noopener">OpenStreetMap</a> (OSM) — otwartej mapy świata tworzonej przez miliony wolontariuszy. Pobieramy je przez <a href="https://overpass-api.de" rel="noopener">Overpass API</a> i regularnie odświeżamy. Dane OSM udostępniane są na licencji <a href="https://opendatacommons.org/licenses/odbl/" rel="noopener">Open Database License (ODbL)</a>, © autorzy OpenStreetMap.</p>

  <h2>Czego szukamy w OSM?</h2>
  <ul>
    <li><strong>Siłownie plenerowe</strong> — obiekty z tagiem <code>leisure=fitness_station</code>, wraz z listą urządzeń (<code>fitness_station:*</code>).</li>
    <li><strong>Parki kalisteniki / street workout</strong> — obiekty z <code>sport=calisthenics</code> oraz stacje fitness złożone wyłącznie z drążków i poręczy.</li>
    <li><strong>Skateparki</strong> — obiekty z <code>sport=skateboard</code>.</li>
    <li><strong>Pumptracki</strong> — tory z <code>sport=bmx</code> lub nazwą zawierającą „pumptrack".</li>
  </ul>

  <h2>Brakuje obiektu? Dodaj go — to proste</h2>
  <ol>
    <li>Wejdź na <a href="https://www.openstreetmap.org" rel="noopener">openstreetmap.org</a> i załóż darmowe konto.</li>
    <li>Znajdź miejsce na mapie i kliknij „Edytuj".</li>
    <li>Dodaj punkt i nadaj mu odpowiedni tag (np. <code>leisure=fitness_station</code>).</li>
    <li>Zapisz zmiany — obiekt pojawi się u nas po najbliższej aktualizacji, a przy okazji trafi do wszystkich map korzystających z OSM na całym świecie.</li>
  </ol>

  <h2>Zdjęcia okolicy</h2>
  <p>Przy każdym obiekcie znajdziesz linki do <strong>Google Street View</strong> oraz <strong>Mapillary</strong> (otwartej, społecznościowej bazy zdjęć ulic) — możesz obejrzeć miejsce przed treningiem.</p>

  <h2>Kontakt</h2>
  <p>Masz pomysł, uwagę albo chcesz nawiązać współpracę? Napisz: <a href="mailto:<?= APP_EMAIL ?>"><?= APP_EMAIL ?></a></p>
</section>
