<?php
declare(strict_types=1);

/**
 * AktywneStrefy.pl — front controller.
 *
 * Routing:
 *   /                                  strona główna
 *   /szukaj?q=…|lat=…&lng=…[&kat=…]    wyniki wyszukiwania
 *   /mapa                              pełna mapa Polski
 *   /miasta                            indeks miast
 *   /o-serwisie                        o projekcie / dane OSM
 *   /{kategoria}                       strona kategorii
 *   /{kategoria}/{miasto}              lista w mieście
 *   /{kategoria}/{miasto}/{slug}       karta obiektu
 *   /api/nearby, /api/bbox             JSON dla map
 *   /sitemap.xml, /robots.txt
 */

// Serwer deweloperski (php -S): pliki statyczne serwuj bezpośrednio
if (PHP_SAPI === 'cli-server') {
    $staticFile = __DIR__ . (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    if ($staticFile !== __DIR__ . '/' && is_file($staticFile)) {
        return false;
    }
}

require dirname(__DIR__) . '/config.php';
require ROOT_DIR . '/src/helpers.php';
require ROOT_DIR . '/src/Database.php';
require ROOT_DIR . '/src/Cities.php';
require ROOT_DIR . '/src/Repo.php';
require ROOT_DIR . '/src/SeoText.php';

/** Schema.org BreadcrumbList z listy par [nazwa, url] */
function breadcrumb_schema(array $crumbs): array
{
    $items = [];
    foreach ($crumbs as $i => [$name, $url]) {
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'name'     => $name,
            'item'     => APP_URL . $url,
        ];
    }
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
}

$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$uri = rtrim($uri, '/') ?: '/';
$segments = $uri === '/' ? [] : explode('/', ltrim($uri, '/'));

/* ── API ─────────────────────────────────────────────────────── */

if (($segments[0] ?? '') === 'api') {
    $action = $segments[1] ?? '';
    $cat = isset($_GET['kat']) && isset(CATEGORIES[$_GET['kat']]) ? $_GET['kat'] : null;

    if ($action === 'nearby') {
        $lat = (float)($_GET['lat'] ?? 0);
        $lon = (float)($_GET['lng'] ?? 0);
        $radius = min(50.0, max(1.0, (float)($_GET['r'] ?? 15)));
        if (!$lat || !$lon) json_response(['error' => 'lat/lng wymagane'], 400);
        $rows = Repo::nearby($lat, $lon, $cat, $radius, 100);
        json_response(['places' => array_map('api_place', $rows)]);
    }

    if ($action === 'bbox') {
        $b = array_map('floatval', explode(',', $_GET['bbox'] ?? ''));
        if (count($b) !== 4) json_response(['error' => 'bbox=s,w,n,e wymagane'], 400);
        $rows = Repo::inBbox($b[0], $b[1], $b[2], $b[3], $cat);
        json_response(['places' => array_map('api_place', $rows)]);
    }

    json_response(['error' => 'nieznana akcja'], 404);
}

function api_place(array $p): array
{
    return [
        'name'     => place_display_name($p),
        'category' => $p['category'],
        'lat'      => (float)$p['lat'],
        'lon'      => (float)$p['lon'],
        'city'     => $p['city'],
        'url'      => place_url($p),
        'distance' => isset($p['distance_km']) ? round($p['distance_km'], 2) : null,
    ];
}

/* ── Sitemap / robots ────────────────────────────────────────── */

if ($uri === '/sitemap.xml') {
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    $static = ['/', '/mapa', '/miasta', '/o-serwisie'];
    foreach (array_keys(CATEGORIES) as $c) $static[] = '/' . $c;
    foreach ($static as $path) {
        echo "<url><loc>" . APP_URL . e($path) . "</loc><changefreq>daily</changefreq></url>\n";
    }
    $seenCity = [];
    foreach (Repo::allForSitemap() as $p) {
        if ($p['city_slug'] && !isset($seenCity[$p['category'] . '/' . $p['city_slug']])) {
            $seenCity[$p['category'] . '/' . $p['city_slug']] = true;
            echo "<url><loc>" . APP_URL . '/' . e($p['category']) . '/' . e($p['city_slug']) . "</loc><changefreq>weekly</changefreq></url>\n";
        }
        $lastmod = substr((string)$p['updated_at'], 0, 10);
        echo "<url><loc>" . APP_URL . '/' . e($p['category']) . '/' . e($p['city_slug'] ?: 'polska') . '/' . e($p['slug'])
            . "</loc><lastmod>$lastmod</lastmod></url>\n";
    }
    echo '</urlset>';
    exit;
}

/* ── Strony HTML ─────────────────────────────────────────────── */

// Strona główna
if ($uri === '/') {
    render_page('home', [
        'title'            => 'Aktywne Strefy — mapa siłowni plenerowych, skateparków i pumptracków w Polsce',
        'meta_description' => 'Znajdź miejsce do treningu na świeżym powietrzu: siłownie plenerowe, parki kalisteniki, skateparki i pumptracki w całej Polsce. Wpisz adres albo użyj swojej lokalizacji.',
        'canonical'        => APP_URL . '/',
        'counts'           => Repo::countByCategory(),
        'total'            => Repo::countAll(),
        'topCities'        => Repo::topCities(16),
        'latest'           => Repo::latest(6),
        'jsonld'           => [
            [
                '@context' => 'https://schema.org',
                '@type'    => 'WebSite',
                'name'     => 'Aktywne Strefy',
                'url'      => APP_URL . '/',
                'potentialAction' => [
                    '@type'       => 'SearchAction',
                    'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => APP_URL . '/szukaj?q={search_term_string}'],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
            [
                '@context' => 'https://schema.org',
                '@type'    => 'Organization',
                'name'     => 'Aktywne Strefy',
                'url'      => APP_URL . '/',
                'logo'     => APP_URL . '/assets/img/logo.svg',
                'email'    => APP_EMAIL,
            ],
        ],
    ]);
    exit;
}

// Wyszukiwarka
if ($uri === '/szukaj') {
    $q   = trim((string)($_GET['q'] ?? ''));
    $lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
    $lng = isset($_GET['lng']) ? (float)$_GET['lng'] : null;
    $kat = isset($_GET['kat']) && isset(CATEGORIES[$_GET['kat']]) ? $_GET['kat'] : null;

    $results = [];
    $mode = 'text';
    if ($lat && $lng) {
        $mode = 'geo';
        $results = Repo::nearby($lat, $lng, $kat, 25.0, 60);
    } elseif ($q !== '') {
        $results = Repo::searchText($q, $kat);
    }

    $label = $mode === 'geo' ? ($q !== '' ? $q : 'Twoja lokalizacja') : $q;
    render_page('search', [
        'title'            => ($label ? "Wyniki: $label — " : 'Szukaj — ') . APP_NAME,
        'meta_description' => 'Wyszukiwarka miejsc do aktywności na świeżym powietrzu.',
        'robots'           => 'noindex,follow',
        'results'          => $results,
        'q'                => $q,
        'lat'              => $lat,
        'lng'              => $lng,
        'kat'              => $kat,
        'mode'             => $mode,
    ]);
    exit;
}

// Pełna mapa
if ($uri === '/mapa') {
    render_page('map', [
        'title'            => 'Mapa miejsc aktywności w Polsce — ' . APP_NAME,
        'meta_description' => 'Interaktywna mapa wszystkich siłowni plenerowych, parków kalisteniki, skateparków i pumptracków w Polsce.',
        'canonical'        => APP_URL . '/mapa',
        'fullwidth'        => true,
    ]);
    exit;
}

// Indeks miast
if ($uri === '/miasta') {
    render_page('cities', [
        'title'            => 'Miasta — katalog miejsc aktywności — ' . APP_NAME,
        'meta_description' => 'Wybierz miasto i sprawdź siłownie plenerowe, parki kalisteniki, skateparki i pumptracki w Twojej okolicy.',
        'canonical'        => APP_URL . '/miasta',
        'cities'           => Repo::topCities(500),
    ]);
    exit;
}

// O serwisie
if ($uri === '/o-serwisie') {
    render_page('about', [
        'title'            => 'O serwisie — skąd mamy dane — ' . APP_NAME,
        'meta_description' => 'Aktywne Strefy to społecznościowy katalog miejsc do aktywności na wolnym powietrzu, oparty o otwarte dane OpenStreetMap.',
        'canonical'        => APP_URL . '/o-serwisie',
    ]);
    exit;
}

// Podgląd wpisów crona (chroniony sekretnym kluczem)
if ($uri === '/cron-setup') {
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');

    if (CRON_SETUP_KEY === '') {
        http_response_code(403);
        echo "Funkcja wyłączona.\n\n";
        echo "Aby włączyć podgląd wpisów crona przez przeglądarkę, ustaw stałą\n";
        echo "CRON_SETUP_KEY w pliku config.php na długi, losowy ciąg, a następnie\n";
        echo "otwórz: " . APP_URL . "/cron-setup?key=TWOJ_KLUCZ\n";
        exit;
    }
    // Porównanie odporne na timing; brak/zły klucz → 404 (funkcja niewidoczna)
    if (!hash_equals(CRON_SETUP_KEY, (string)($_GET['key'] ?? ''))) {
        not_found();
    }

    $info = cron_setup_info();
    echo "════════════════════════════════════════════════════════════════════\n";
    echo " AktywneStrefy.pl — wpisy CRON (ścieżki wykryte automatycznie)\n";
    echo "════════════════════════════════════════════════════════════════════\n\n";
    echo "Wykryta ścieżka projektu: {$info['root']}\n";
    echo "Wykryta binarka PHP:      {$info['php']}\n\n";
    foreach ($info['warnings'] as $w) {
        echo "  ⚠ $w\n";
    }
    if ($info['warnings']) echo "\n";
    echo "Skopiuj poniższe linie do konfiguracji crona w panelu hostingu:\n\n";
    echo "# 1) Import danych z OpenStreetMap — poniedziałek 4:15 (~2–5 min)\n";
    echo $info['line1'] . "\n\n";
    echo "# 2) Uzupełnianie adresów obiektów — codziennie 2:30 (~11 min/porcję)\n";
    echo $info['line2'] . "\n\n";
    echo "════════════════════════════════════════════════════════════════════\n";
    exit;
}

// Zgłoszenie błędu / opinia (POST)
if ($uri === '/zglos' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    handle_post();
    exit;
}

/* ── Kategorie / miasta / obiekty ───────────────────────────── */

$catSlug = $segments[0] ?? '';
if (isset(CATEGORIES[$catSlug])) {
    $cat = CATEGORIES[$catSlug];

    // /{kategoria}
    if (count($segments) === 1) {
        render_page('category', [
            'title'            => $cat['seo_title'] . ' — ' . APP_NAME,
            'meta_description' => $cat['seo_desc'],
            'canonical'        => APP_URL . '/' . $catSlug,
            'catSlug'          => $catSlug,
            'cat'              => $cat,
            'cities'           => Repo::citiesForCategory($catSlug),
            'counts'           => Repo::countByCategory(),
            'jsonld'           => [
                breadcrumb_schema([['Start', '/'], [$cat['name'], '/' . $catSlug]]),
                [
                    '@context'   => 'https://schema.org',
                    '@type'      => 'FAQPage',
                    'mainEntity' => array_map(fn($f) => [
                        '@type'          => 'Question',
                        'name'           => $f[0],
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
                    ], $cat['faq']),
                ],
            ],
        ]);
        exit;
    }

    $citySlug = $segments[1];

    // /{kategoria}/{miasto}
    if (count($segments) === 2) {
        $places = Repo::placesInCity($catSlug, $citySlug);
        if (!$places) not_found();
        $cityName = $places[0]['city'];
        $n = count($places);
        render_page('city', [
            'title'            => $cat['name'] . ' ' . $cityName . ' — mapa i lista (' . $n . ') — ' . APP_NAME,
            'meta_description' => $cat['name'] . ' w mieście ' . $cityName . ': ' . $n . ' ' .
                                  polish_plural($n, 'obiekt', 'obiekty', 'obiektów') .
                                  ' na mapie z opisem sprzętu, nawierzchni i oświetlenia. Dane z OpenStreetMap.',
            'canonical'        => APP_URL . '/' . $catSlug . '/' . $citySlug,
            'catSlug'          => $catSlug,
            'cat'              => $cat,
            'citySlug'         => $citySlug,
            'cityName'         => $cityName,
            'places'           => $places,
            'districts'        => Repo::districtsInCity($catSlug, $citySlug),
            'crossCats'        => Repo::categoriesInCity($citySlug, $catSlug),
            'jsonld'           => [
                breadcrumb_schema([
                    ['Start', '/'],
                    [$cat['name'], '/' . $catSlug],
                    [$cityName, city_url($catSlug, $citySlug)],
                ]),
                [
                    '@context'        => 'https://schema.org',
                    '@type'           => 'ItemList',
                    'name'            => $cat['name'] . ' — ' . $cityName,
                    'numberOfItems'   => $n,
                    'itemListElement' => array_map(fn($i, $p) => [
                        '@type'    => 'ListItem',
                        'position' => $i + 1,
                        'name'     => place_display_name($p),
                        'url'      => APP_URL . place_url($p),
                    ], array_keys(array_slice($places, 0, 50)), array_slice($places, 0, 50)),
                ],
            ],
        ]);
        exit;
    }

    // /{kategoria}/{miasto}/{slug}
    if (count($segments) === 3) {
        $place = Repo::bySlug($segments[2]);
        if (!$place || $place['category'] !== $catSlug) not_found();

        // Leniwe reverse-geokodowanie adresu: raz na obiekt, wynik trafia do bazy
        if ($place['address'] === null) {
            $addr = reverse_geocode((float)$place['lat'], (float)$place['lon']);
            if ($addr !== null) {
                Repo::setAddress((int)$place['id'], $addr);
                $place['address'] = $addr;
            }
        }

        $displayName = place_display_name($place);
        $equipment = place_equipment($place);
        $nearby = array_values(array_filter(
            Repo::nearby((float)$place['lat'], (float)$place['lon'], $catSlug, 10.0, 7),
            fn($p) => $p['id'] !== $place['id']
        ));
        $nearby = array_slice($nearby, 0, 6);
        $rating = Repo::ratingSummary((int)$place['id']);
        $faq = SeoText::faq($place, $equipment, $displayName);

        $crumbs = [['Start', '/'], [$cat['name'], '/' . $catSlug]];
        if ($place['city_slug']) {
            $crumbs[] = [$place['city'], city_url($catSlug, $place['city_slug'])];
        }
        $crumbs[] = [place_breadcrumb_label($place), place_url($place)];

        render_page('place', [
            'title'            => place_seo_title($place),
            'meta_description' => $cat['singular'] . ($place['city'] ? ' w mieście ' . $place['city'] : '') .
                                  ($place['district'] ? ', ' . $place['district'] : '') .
                                  ($place['address'] ? ' (' . $place['address'] . ')' : '') . '. ' .
                                  ($equipment ? 'Sprzęt: ' . implode(', ', array_slice($equipment, 0, 5)) . '. ' : '') .
                                  'Adres, mapa, dojazd i szczegóły obiektu.',
            'canonical'        => APP_URL . place_url($place),
            'head_extra'       => '<meta name="geo.region" content="PL">' . "\n" .
                                  '<meta name="geo.placename" content="' . e($place['city'] ?: 'Polska') . '">' . "\n" .
                                  '<meta name="geo.position" content="' . $place['lat'] . ';' . $place['lon'] . '">' . "\n" .
                                  '<meta name="ICBM" content="' . $place['lat'] . ', ' . $place['lon'] . '">',
            'catSlug'          => $catSlug,
            'cat'              => $cat,
            'place'            => $place,
            'displayName'      => $displayName,
            'equipment'        => $equipment,
            'nearby'           => $nearby,
            'reviews'          => Repo::reviews((int)$place['id']),
            'rating'           => $rating,
            'description'      => SeoText::description($place, $equipment, $nearby),
            'faq'              => $faq,
            'jsonld'           => [
                breadcrumb_schema($crumbs),
                [
                    '@context'   => 'https://schema.org',
                    '@type'      => 'FAQPage',
                    'mainEntity' => array_map(fn($f) => [
                        '@type'          => 'Question',
                        'name'           => $f[0],
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
                    ], $faq),
                ],
            ],
        ]);
        exit;
    }
}

not_found();

/* ── Obsługa formularzy POST ─────────────────────────────────── */

function handle_post(): void
{
    // Antyspam: honeypot + minimalny czas wypełniania
    if (!empty($_POST['website_url']) || (int)($_POST['ts'] ?? 0) > time() - 3) {
        header('Location: ' . ($_POST['back'] ?? '/'));
        return;
    }
    $back = (string)($_POST['back'] ?? '/');
    if (!str_starts_with($back, '/')) $back = '/';

    $type = $_POST['type'] ?? '';
    $placeId = isset($_POST['place_id']) ? (int)$_POST['place_id'] : null;

    if ($type === 'review' && $placeId) {
        $author = trim((string)($_POST['author'] ?? ''));
        $rating = (int)($_POST['rating'] ?? 0);
        $body = trim((string)($_POST['body'] ?? ''));
        if ($author !== '' && $rating >= 1 && $rating <= 5) {
            Repo::addReview($placeId, $author, $rating, $body);
            $back .= (str_contains($back, '?') ? '&' : '?') . 'dziekujemy=1#opinie';
        }
    } elseif ($type === 'report') {
        $body = trim((string)($_POST['body'] ?? ''));
        if ($body !== '') {
            Repo::addReport($placeId, (string)($_POST['kind'] ?? 'inne'), $body);
            $back .= (str_contains($back, '?') ? '&' : '?') . 'zgloszono=1';
        }
    }
    header('Location: ' . $back);
}
