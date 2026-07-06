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

/** Nazwa wyświetlana obiektu (fallback gdy brak nazwy w OSM) */
function place_display_name(array $p): string
{
    if (!empty($p['name'])) return $p['name'];
    $cat = CATEGORIES[$p['category']]['singular'] ?? 'Obiekt';
    $where = $p['district'] ? ($p['district'] . ', ' . $p['city']) : $p['city'];
    return $where ? "$cat — $where" : $cat;
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
