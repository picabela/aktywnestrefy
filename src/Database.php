<?php
declare(strict_types=1);

/**
 * Lekka warstwa dostępu do SQLite (PDO).
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function get(): PDO
    {
        if (self::$pdo === null) {
            $dir = dirname(DB_FILE);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            // Pierwsze uruchomienie: jeśli nie ma żywej bazy, a jest plik-ziarno
            // (data/seed.sqlite z repozytorium) — kopiujemy go jako bazę startową.
            // Dzięki temu żywa baza (z opiniami i adresami z crona) nigdy nie jest
            // w repozytorium i nie zostanie nadpisana przy kolejnych wgraniach.
            $seed = $dir . '/seed.sqlite';
            if (!file_exists(DB_FILE) && is_file($seed)) {
                copy($seed, DB_FILE);
            }
            self::$pdo = new PDO('sqlite:' . DB_FILE, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            self::$pdo->exec('PRAGMA journal_mode = WAL');
            self::$pdo->exec('PRAGMA foreign_keys = ON');
            self::migrate(self::$pdo);
        }
        return self::$pdo;
    }

    private static function migrate(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS places (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    osm_type      TEXT NOT NULL,
    osm_id        INTEGER NOT NULL,
    category      TEXT NOT NULL,
    name          TEXT,
    lat           REAL NOT NULL,
    lon           REAL NOT NULL,
    city          TEXT,
    city_slug     TEXT,
    district      TEXT,
    district_slug TEXT,
    slug          TEXT NOT NULL UNIQUE,
    equipment     TEXT DEFAULT '[]',
    surface       TEXT,
    lit           INTEGER,
    covered       INTEGER,
    access        TEXT,
    opening_hours TEXT,
    website       TEXT,
    tags          TEXT DEFAULT '{}',
    updated_at    TEXT DEFAULT (datetime('now')),
    UNIQUE (osm_type, osm_id)
);

CREATE INDEX IF NOT EXISTS idx_places_category  ON places (category);
CREATE INDEX IF NOT EXISTS idx_places_city      ON places (city_slug, category);
CREATE INDEX IF NOT EXISTS idx_places_latlon    ON places (lat, lon);

CREATE TABLE IF NOT EXISTS reviews (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    place_id   INTEGER NOT NULL REFERENCES places(id) ON DELETE CASCADE,
    author     TEXT NOT NULL,
    rating     INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
    body       TEXT,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_reviews_place ON reviews (place_id);
SQL);

        // Migracja: kolumna adresu (ulica + numer) dla starszych baz
        $cols = $pdo->query("PRAGMA table_info(places)")->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('address', $cols, true)) {
            $pdo->exec('ALTER TABLE places ADD COLUMN address TEXT');
        }

        $pdo->exec(<<<SQL

CREATE TABLE IF NOT EXISTS reports (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    place_id   INTEGER REFERENCES places(id) ON DELETE SET NULL,
    kind       TEXT NOT NULL,
    body       TEXT NOT NULL,
    created_at TEXT DEFAULT (datetime('now'))
);
SQL);
    }
}
