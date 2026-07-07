<?php
declare(strict_types=1);

/** Escapowanie HTML */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Slugifikacja z obsługą polskich znaków */
function slugify(string $text): string
{
    $map = [
        'ą'=>'a','ć'=>'c','ę'=>'e','ł'=>'l','ń'=>'n','ó'=>'o','ś'=>'s','ź'=>'z','ż'=>'z',
        'Ą'=>'a','Ć'=>'c','Ę'=>'e','Ł'=>'l','Ń'=>'n','Ó'=>'o','Ś'=>'s','Ź'=>'z','Ż'=>'z',
    ];
    $text = strtr($text, $map);
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

/** Dystans haversine w kilometrach */
function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $r = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    return $r * 2 * asin(min(1.0, sqrt($a)));
}

/** Ładny format dystansu */
function format_distance(float $km): string
{
    return $km < 1 ? round($km * 1000) . ' m' : number_format($km, 1, ',', ' ') . ' km';
}

/** Odmiana liczebników: 1 obiekt, 2 obiekty, 5 obiektów */
function polish_plural(int $n, string $one, string $few, string $many): string
{
    if ($n === 1) return $one;
    $mod10 = $n % 10;
    $mod100 = $n % 100;
    if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) return $few;
    return $many;
}

/** URL kanoniczny podstrony obiektu */
function place_url(array $p): string
{
    return '/' . $p['category'] . '/' . ($p['city_slug'] ?: 'polska') . '/' . $p['slug'];
}

/** URL strony miasta w kategorii */
function city_url(string $category, string $citySlug): string
{
    return '/' . $category . '/' . $citySlug;
}

/** Ulica z adresu (bez kodu pocztowego), np. "Malborska 96" */
function place_street(array $p): ?string
{
    if (empty($p['address'])) return null;
    $street = trim(explode(',', $p['address'])[0]);
    return $street !== '' ? $street : null;
}

/**
 * Nazwa wyświetlana obiektu. Gdy OSM nie podaje nazwy, budujemy unikalną
 * z kategorii + ulicy (lub dzielnicy/miasta), np. "Skatepark – Malborska 96".
 */
function place_display_name(array $p): string
{
    if (!empty($p['name'])) return $p['name'];
    $cat = CATEGORIES[$p['category']]['singular'] ?? 'Obiekt';
    $loc = place_street($p) ?: ($p['district'] ?: ($p['city'] ?: null));
    return $loc ? "$cat – $loc" : $cat;
}

/**
 * Tytuł SEO karty obiektu — bez dublowania kategorii i miasta.
 * Nazwane:    "Vert Ramp Płaszów — skatepark, Kraków | AktywneStrefy.pl"
 * Bez nazwy:  "Skatepark Malborska 96, Kraków — mapa i dojazd | AktywneStrefy.pl"
 */
function place_seo_title(array $p): string
{
    $cat = CATEGORIES[$p['category']];
    // Słowo kluczowe kategorii (pierwszy wyraz nazwy pojedynczej): siłownia/park/skatepark/pumptrack
    $keyword = explode(' ', mb_strtolower($cat['singular']))[0];

    if (!empty($p['name'])) {
        $title = $p['name'];
        if (mb_stripos($title, $keyword) === false) {
            $title .= ' — ' . mb_strtolower($cat['singular']);
        }
        if ($p['city'] && mb_stripos($title, $p['city']) === false) {
            $title .= (str_contains($title, ' — ') ? ', ' : ' — ') . $p['city'];
        }
        return $title . ' | ' . APP_NAME;
    }

    $loc = place_street($p) ?: $p['district'];
    $title = $cat['singular']
        . ($loc ? ' ' . $loc : '')
        . ($p['city'] && mb_stripos((string)$loc, (string)$p['city']) === false ? ', ' . $p['city'] : '');
    return $title . ' — mapa i dojazd | ' . APP_NAME;
}

/** Krótka etykieta obiektu do breadcrumbów (miasto jest już we wcześniejszym okruszku) */
function place_breadcrumb_label(array $p): string
{
    if (!empty($p['name'])) return $p['name'];
    $cat = CATEGORIES[$p['category']]['singular'] ?? 'Obiekt';
    $loc = place_street($p) ?: $p['district'];
    return $loc ? "$cat – $loc" : $cat;
}

/** Lista sprzętu (JSON → tablica polskich etykiet) */
function place_equipment(array $p): array
{
    $eq = json_decode($p['equipment'] ?? '[]', true) ?: [];
    $out = [];
    foreach ($eq as $key) {
        $out[] = EQUIPMENT_LABELS[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }
    return array_values(array_unique($out));
}

/** Etykieta nawierzchni po polsku */
function surface_label(?string $surface): ?string
{
    if (!$surface) return null;
    return SURFACE_LABELS[$surface] ?? str_replace('_', ' ', $surface);
}

/**
 * Reverse-geokodowanie Nominatim (adres z współrzędnych).
 * Wywoływane leniwie — raz na obiekt, wynik zapisywany w bazie.
 * Zwraca adres, pusty string (brak adresu w okolicy) lub null (błąd sieci — spróbujemy ponownie).
 */
function reverse_geocode(float $lat, float $lon): ?string
{
    $url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=17'
         . '&lat=' . $lat . '&lon=' . $lon . '&accept-language=pl';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 3,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_USERAGENT      => APP_NAME . ' (+' . APP_URL . '; ' . APP_EMAIL . ')',
    ]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($body === false || $status !== 200) {
        return null;
    }
    $a = json_decode($body, true)['address'] ?? [];
    $street = $a['road'] ?? $a['pedestrian'] ?? $a['footway'] ?? null;
    if ($street === null) {
        return '';
    }
    $addr = $street . (isset($a['house_number']) ? ' ' . $a['house_number'] : '');
    if (isset($a['postcode'])) {
        $addr .= ', ' . $a['postcode'];
    }
    return $addr;
}

/**
 * Wykrywa ścieżkę do binarki PHP CLI.
 * W CLI używa dokładnego PHP_BINARY; w kontekście web (FPM/Apache) PHP_BINARY
 * wskazuje na zły plik, więc sondujemy typowe lokalizacje interpretera CLI.
 */
function detect_php_cli(): string
{
    if (PHP_SAPI === 'cli' && PHP_BINARY !== '') {
        return PHP_BINARY;
    }
    $v = PHP_MAJOR_VERSION . PHP_MINOR_VERSION; // np. "84"
    $candidates = [
        "/usr/local/bin/php{$v}", "/usr/bin/php{$v}",
        "/usr/local/bin/php" . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
        "/usr/bin/php" . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
        '/usr/local/bin/php', '/usr/bin/php', '/opt/php/bin/php',
    ];
    foreach ($candidates as $path) {
        if (@is_executable($path)) {
            return $path;
        }
    }
    return 'php';
}

/**
 * Zbiera dane potrzebne do skonfigurowania crona (wspólne dla CLI i web).
 * @return array{root:string,php:string,dataDir:string,line1:string,line2:string,warnings:string[]}
 */
function cron_setup_info(): array
{
    $root    = ROOT_DIR;
    $php     = detect_php_cli();
    $dataDir = $root . '/data';
    $import  = $root . '/bin/import.php';
    $geocode = $root . '/bin/geocode.php';

    $warnings = [];
    foreach (['import.php' => $import, 'geocode.php' => $geocode] as $name => $path) {
        if (!is_file($path)) {
            $warnings[] = "Nie znaleziono pliku: $path";
        }
    }
    if (is_dir($dataDir) && !is_writable($dataDir)) {
        $warnings[] = "Katalog data/ nie jest zapisywalny — nadaj uprawnienia: chmod 775 $dataDir";
    }
    if ($php === 'php') {
        $warnings[] = 'Nie udało się wykryć pełnej ścieżki PHP CLI — użyto ogólnego „php". Jeśli cron zgłosi błąd, wpisz pełną ścieżkę z panelu hostingu.';
    }

    return [
        'root'     => $root,
        'php'      => $php,
        'dataDir'  => $dataDir,
        'line1'    => sprintf('15 4 * * 1 %s %s >> %s/import.log 2>&1', $php, $import, $dataDir),
        'line2'    => sprintf('30 2 * * * %s %s >> %s/geocode.log 2>&1', $php, $geocode, $dataDir),
        'warnings' => $warnings,
    ];
}

/** Render szablonu widoku */
function view(string $template, array $data = []): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    require ROOT_DIR . '/src/views/' . $template . '.php';
    return (string)ob_get_clean();
}

/** Pełna strona w layoucie */
function render_page(string $template, array $data = []): void
{
    $data['content'] = view($template, $data);
    echo view('layout', $data);
}

/** Odpowiedź JSON */
function json_response(mixed $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: public, max-age=300');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** 404 */
function not_found(): void
{
    http_response_code(404);
    render_page('404', ['title' => 'Nie znaleziono strony — ' . APP_NAME, 'meta_description' => '']);
    exit;
}
