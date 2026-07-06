<?php
declare(strict_types=1);

/**
 * Importer danych z OpenStreetMap (Overpass API).
 *
 * Użycie:
 *   php bin/import.php                    # cała Polska (domyślnie)
 *   php bin/import.php --bbox=49.9,19.7,50.2,20.2   # tylko prostokąt (S,W,N,E)
 *   php bin/import.php --endpoint=https://overpass.kumi.systems/api/interpreter
 *
 * Pobiera i kategoryzuje:
 *  - siłownie plenerowe   leisure=fitness_station
 *  - parki kalisteniki    sport~calisthenics (lub fitness_station z drążkami/poręczami)
 *  - skateparki           sport~skateboard
 *  - pumptracki           sport~bmx / name~pumptrack
 */

require __DIR__ . '/../config.php';
require ROOT_DIR . '/src/helpers.php';
require ROOT_DIR . '/src/Database.php';
require ROOT_DIR . '/src/Cities.php';

$options = getopt('', ['bbox::', 'endpoint::']);
$endpoint = $options['endpoint'] ?? 'https://overpass-api.de/api/interpreter';
$bbox = $options['bbox'] ?? null;

if ($bbox !== null) {
    $areaFilter = '(' . $bbox . ')';
    $areaDef = '';
} else {
    $areaDef = 'area["ISO3166-1"="PL"][admin_level=2]->.pl;';
    $areaFilter = '(area.pl)';
}

$query = <<<QL
[out:json][timeout:600];
$areaDef
(
  nwr["leisure"="fitness_station"]$areaFilter;
  nwr["sport"~"calisthenics"]$areaFilter;
  nwr["sport"~"skateboard"]$areaFilter;
  nwr["leisure"~"^(pitch|track)$"]["sport"~"bmx"]$areaFilter;
  nwr["name"~"[Pp]ump ?[Tt]rack"]$areaFilter;
);
out center tags;
QL;

echo "→ Pobieram dane z Overpass API ($endpoint)…\n";
$json = overpass_fetch($endpoint, $query);
$elements = $json['elements'] ?? [];
echo '→ Otrzymano ' . count($elements) . " elementów.\n";

$pdo = Database::get();
$pdo->beginTransaction();

$upsert = $pdo->prepare(<<<SQL
INSERT INTO places (osm_type, osm_id, category, name, lat, lon, city, city_slug,
                    district, district_slug, slug, equipment, surface, lit, covered,
                    access, opening_hours, website, tags, updated_at)
VALUES (:osm_type, :osm_id, :category, :name, :lat, :lon, :city, :city_slug,
        :district, :district_slug, :slug, :equipment, :surface, :lit, :covered,
        :access, :opening_hours, :website, :tags, datetime('now'))
ON CONFLICT (osm_type, osm_id) DO UPDATE SET
    category = excluded.category, name = excluded.name,
    lat = excluded.lat, lon = excluded.lon,
    city = excluded.city, city_slug = excluded.city_slug,
    district = excluded.district, district_slug = excluded.district_slug,
    equipment = excluded.equipment, surface = excluded.surface,
    lit = excluded.lit, covered = excluded.covered, access = excluded.access,
    opening_hours = excluded.opening_hours, website = excluded.website,
    tags = excluded.tags, updated_at = datetime('now')
SQL);

$imported = 0;
$skipped = 0;
$counts = [];

foreach ($elements as $el) {
    $tags = $el['tags'] ?? [];
    if (!$tags) { $skipped++; continue; }

    // Pomijamy obiekty prywatne / kryte hale
    $access = $tags['access'] ?? null;
    if (in_array($access, ['private', 'no', 'customers'], true)) { $skipped++; continue; }

    $lat = $el['lat'] ?? $el['center']['lat'] ?? null;
    $lon = $el['lon'] ?? $el['center']['lon'] ?? null;
    if ($lat === null || $lon === null) { $skipped++; continue; }

    $category = categorize($tags);
    if ($category === null) { $skipped++; continue; }

    // Miasto: addr:city z OSM, inaczej najbliższe duże miasto (≤30 km)
    $city = $tags['addr:city'] ?? null;
    if ($city === null) {
        $nearest = Cities::nearest((float)$lat, (float)$lon);
        $city = $nearest[0] ?? null;
    }
    $citySlug = $city !== null ? slugify($city) : null;

    $district = $tags['addr:suburb'] ?? $tags['addr:district'] ?? null;
    $districtSlug = $district !== null ? slugify($district) : null;

    $name = trim($tags['name'] ?? '');
    $name = $name !== '' ? $name : null;

    $slugBase = $name ?? (CATEGORIES[$category]['singular'] . ' ' . ($district ?? $city ?? ''));
    $slug = slugify($slugBase) . '-' . substr($el['type'], 0, 1) . $el['id'];

    $equipment = extract_equipment($tags);

    $upsert->execute([
        ':osm_type'      => $el['type'],
        ':osm_id'        => $el['id'],
        ':category'      => $category,
        ':name'          => $name,
        ':lat'           => $lat,
        ':lon'           => $lon,
        ':city'          => $city,
        ':city_slug'     => $citySlug,
        ':district'      => $district,
        ':district_slug' => $districtSlug,
        ':slug'          => $slug,
        ':equipment'     => json_encode($equipment, JSON_UNESCAPED_UNICODE),
        ':surface'       => $tags['surface'] ?? null,
        ':lit'           => isset($tags['lit']) ? (int)($tags['lit'] !== 'no') : null,
        ':covered'       => isset($tags['covered']) ? (int)($tags['covered'] !== 'no') : null,
        ':access'        => $access,
        ':opening_hours' => $tags['opening_hours'] ?? null,
        ':website'       => $tags['website'] ?? $tags['contact:website'] ?? null,
        ':tags'          => json_encode($tags, JSON_UNESCAPED_UNICODE),
    ]);
    $imported++;
    $counts[$category] = ($counts[$category] ?? 0) + 1;
}

$pdo->commit();

echo "✓ Zaimportowano/odświeżono: $imported (pominięto: $skipped)\n";
foreach ($counts as $cat => $n) {
    echo "   • " . CATEGORIES[$cat]['name'] . ": $n\n";
}

/* ── Funkcje pomocnicze ──────────────────────────────────────── */

function categorize(array $tags): ?string
{
    $sport = $tags['sport'] ?? '';
    $name = mb_strtolower($tags['name'] ?? '');
    $leisure = $tags['leisure'] ?? '';

    if (str_contains($sport, 'bmx') || preg_match('/pump\s?track/u', $name)) {
        return 'pumptracki';
    }
    if (str_contains($sport, 'skateboard') || str_contains($name, 'skatepark') || str_contains($name, 'skate park')) {
        return 'skateparki';
    }
    if (str_contains($sport, 'calisthenics') || str_contains($name, 'street workout') || str_contains($name, 'kalistenik')) {
        return 'parki-kalisteniki';
    }
    if ($leisure === 'fitness_station') {
        // Sama infrastruktura drążkowa bez urządzeń = de facto park kalisteniki
        $bars = 0;
        $machines = 0;
        foreach ($tags as $k => $v) {
            if (!str_starts_with($k, 'fitness_station:') || $v === 'no') continue;
            $key = substr($k, 16);
            if (in_array($key, ['horizontal_bar', 'parallel_bars', 'rings', 'monkey_bars', 'horizontal_ladder', 'wall_bars', 'push-up'], true)) {
                $bars++;
            } else {
                $machines++;
            }
        }
        return ($bars >= 2 && $machines === 0) ? 'parki-kalisteniki' : 'silownie-plenerowe';
    }
    return null;
}

function extract_equipment(array $tags): array
{
    $out = [];
    foreach ($tags as $k => $v) {
        if (str_starts_with($k, 'fitness_station:') && $v !== 'no') {
            $out[] = substr($k, 16);
        }
    }
    if (($tags['fitness_station'] ?? '') !== '' && ($tags['fitness_station'] ?? '') !== 'yes') {
        foreach (explode(';', $tags['fitness_station']) as $v) {
            $out[] = trim($v);
        }
    }
    return array_values(array_unique(array_filter($out)));
}

function overpass_fetch(string $endpoint, string $query): array
{
    $attempts = 0;
    while (true) {
        $attempts++;
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => 'data=' . urlencode($query),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 660,
            CURLOPT_USERAGENT      => APP_NAME . ' importer (+' . APP_URL . '; ' . APP_EMAIL . ')',
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($body !== false && $status === 200) {
            $json = json_decode($body, true);
            if (is_array($json)) {
                return $json;
            }
            $err = 'niepoprawny JSON';
        }
        if ($attempts >= 4) {
            fwrite(STDERR, "✗ Błąd Overpass (HTTP $status): $err\n");
            exit(1);
        }
        $wait = 2 ** $attempts * 5;
        echo "… próba $attempts nieudana (HTTP $status $err), czekam {$wait}s\n";
        sleep($wait);
    }
}
