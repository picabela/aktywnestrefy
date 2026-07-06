<?php
declare(strict_types=1);

/**
 * Repozytorium zapytań — cała logika odczytu/zapisu danych katalogu.
 */
final class Repo
{
    public static function countAll(): int
    {
        return (int)Database::get()->query('SELECT COUNT(*) FROM places')->fetchColumn();
    }

    /** @return array<string,int> liczba obiektów per kategoria */
    public static function countByCategory(): array
    {
        $rows = Database::get()->query('SELECT category, COUNT(*) c FROM places GROUP BY category')->fetchAll();
        $out = [];
        foreach ($rows as $r) {
            $out[$r['category']] = (int)$r['c'];
        }
        return $out;
    }

    public static function citiesForCategory(string $category): array
    {
        $st = Database::get()->prepare(
            'SELECT city, city_slug, COUNT(*) c FROM places
             WHERE category = ? AND city IS NOT NULL
             GROUP BY city_slug ORDER BY c DESC, city'
        );
        $st->execute([$category]);
        return $st->fetchAll();
    }

    public static function topCities(int $limit = 16): array
    {
        $st = Database::get()->prepare(
            'SELECT city, city_slug, COUNT(*) c FROM places
             WHERE city IS NOT NULL
             GROUP BY city_slug ORDER BY c DESC LIMIT ?'
        );
        $st->execute([$limit]);
        return $st->fetchAll();
    }

    public static function placesInCity(string $category, string $citySlug): array
    {
        $st = Database::get()->prepare(
            'SELECT * FROM places WHERE category = ? AND city_slug = ? ORDER BY name IS NULL, name'
        );
        $st->execute([$category, $citySlug]);
        return $st->fetchAll();
    }

    public static function districtsInCity(string $category, string $citySlug): array
    {
        $st = Database::get()->prepare(
            'SELECT district, district_slug, COUNT(*) c FROM places
             WHERE category = ? AND city_slug = ? AND district IS NOT NULL
             GROUP BY district_slug ORDER BY c DESC'
        );
        $st->execute([$category, $citySlug]);
        return $st->fetchAll();
    }

    public static function bySlug(string $slug): ?array
    {
        $st = Database::get()->prepare('SELECT * FROM places WHERE slug = ?');
        $st->execute([$slug]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /**
     * Wyszukiwanie po współrzędnych — najbliższe obiekty.
     * Pre-filtr prostokątny + dokładny haversine w PHP.
     */
    public static function nearby(float $lat, float $lon, ?string $category = null, float $radiusKm = 15.0, int $limit = 60): array
    {
        $dLat = $radiusKm / 111.0;
        $dLon = $radiusKm / (111.0 * max(0.2, cos(deg2rad($lat))));
        $sql = 'SELECT * FROM places WHERE lat BETWEEN :latMin AND :latMax AND lon BETWEEN :lonMin AND :lonMax';
        $params = [
            ':latMin' => $lat - $dLat, ':latMax' => $lat + $dLat,
            ':lonMin' => $lon - $dLon, ':lonMax' => $lon + $dLon,
        ];
        if ($category !== null) {
            $sql .= ' AND category = :cat';
            $params[':cat'] = $category;
        }
        $st = Database::get()->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();

        foreach ($rows as &$row) {
            $row['distance_km'] = haversine($lat, $lon, (float)$row['lat'], (float)$row['lon']);
        }
        unset($row);
        $rows = array_filter($rows, fn($r) => $r['distance_km'] <= $radiusKm);
        usort($rows, fn($a, $b) => $a['distance_km'] <=> $b['distance_km']);
        return array_slice($rows, 0, $limit);
    }

    /** Wyszukiwanie tekstowe po nazwie/mieście/dzielnicy */
    public static function searchText(string $q, ?string $category = null, int $limit = 60): array
    {
        $like = '%' . str_replace(['%', '_'], ' ', trim($q)) . '%';
        $sql = 'SELECT * FROM places WHERE (name LIKE :q OR city LIKE :q OR district LIKE :q)';
        $params = [':q' => $like];
        if ($category !== null) {
            $sql .= ' AND category = :cat';
            $params[':cat'] = $category;
        }
        $sql .= ' ORDER BY name IS NULL, name LIMIT :lim';
        $st = Database::get()->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    /** Obiekty w prostokącie mapy (dla API mapy) */
    public static function inBbox(float $south, float $west, float $north, float $east, ?string $category = null, int $limit = 500): array
    {
        $sql = 'SELECT id, category, name, lat, lon, city, city_slug, district, slug FROM places
                WHERE lat BETWEEN :s AND :n AND lon BETWEEN :w AND :e';
        $params = [':s' => $south, ':n' => $north, ':w' => $west, ':e' => $east];
        if ($category !== null) {
            $sql .= ' AND category = :cat';
            $params[':cat'] = $category;
        }
        $sql .= ' LIMIT :lim';
        $st = Database::get()->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function latest(int $limit = 8): array
    {
        $st = Database::get()->prepare('SELECT * FROM places ORDER BY updated_at DESC, id DESC LIMIT ?');
        $st->execute([$limit]);
        return $st->fetchAll();
    }

    /* ── Opinie ─────────────────────────────────────────── */

    public static function reviews(int $placeId): array
    {
        $st = Database::get()->prepare('SELECT * FROM reviews WHERE place_id = ? ORDER BY created_at DESC LIMIT 50');
        $st->execute([$placeId]);
        return $st->fetchAll();
    }

    public static function ratingSummary(int $placeId): array
    {
        $st = Database::get()->prepare('SELECT COUNT(*) c, AVG(rating) avg FROM reviews WHERE place_id = ?');
        $st->execute([$placeId]);
        $r = $st->fetch();
        return ['count' => (int)$r['c'], 'avg' => $r['avg'] !== null ? round((float)$r['avg'], 1) : null];
    }

    public static function addReview(int $placeId, string $author, int $rating, string $body): void
    {
        $st = Database::get()->prepare('INSERT INTO reviews (place_id, author, rating, body) VALUES (?,?,?,?)');
        $st->execute([$placeId, mb_substr($author, 0, 60), $rating, mb_substr($body, 0, 2000)]);
    }

    public static function addReport(?int $placeId, string $kind, string $body): void
    {
        $st = Database::get()->prepare('INSERT INTO reports (place_id, kind, body) VALUES (?,?,?)');
        $st->execute([$placeId, mb_substr($kind, 0, 40), mb_substr($body, 0, 2000)]);
    }

    /** Wszystkie URL-e do sitemap.xml */
    public static function allForSitemap(): array
    {
        return Database::get()->query(
            'SELECT category, city_slug, slug, updated_at FROM places ORDER BY id'
        )->fetchAll();
    }
}
