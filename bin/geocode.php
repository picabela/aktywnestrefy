<?php
declare(strict_types=1);

/**
 * Uzupełnianie adresów lokalizacji obiektów (reverse-geokodowanie Nominatim).
 *
 * Przetwarza obiekty bez adresu (places.address IS NULL) z szacunkiem dla
 * limitu Nominatim (maks. 1 zapytanie/sek.). Wyniki zapisuje w bazie na stałe,
 * więc każdorazowe uruchomienie dogęszcza kolejną porcję rekordów.
 *
 * Użycie:
 *   php bin/geocode.php                 # domyślnie do 600 obiektów (~11 minut)
 *   php bin/geocode.php --limit=100     # mniejsza porcja
 *   php bin/geocode.php --limit=0       # bez limitu (całość, może trwać godziny)
 *
 * Cron (co noc o 2:30 dogęszcza kolejne 600 adresów, aż braknie zaległości):
 *   30 2 * * * php /sciezka/do/bin/geocode.php >> /sciezka/do/data/geocode.log 2>&1
 */

require __DIR__ . '/../config.php';
require ROOT_DIR . '/src/helpers.php';
require ROOT_DIR . '/src/Database.php';

$options = getopt('', ['limit::']);
$limit = isset($options['limit']) ? max(0, (int)$options['limit']) : 600;

// Blokada przed nakładaniem się uruchomień z crona
$lockFile = fopen(ROOT_DIR . '/data/geocode.lock', 'c');
if ($lockFile === false || !flock($lockFile, LOCK_EX | LOCK_NB)) {
    echo "Inna instancja geocode.php już działa — kończę.\n";
    exit(0);
}

$pdo = Database::get();

$total = (int)$pdo->query('SELECT COUNT(*) FROM places WHERE address IS NULL')->fetchColumn();
if ($total === 0) {
    echo "✓ Wszystkie obiekty mają już adres (lub oznaczenie „brak ulicy w okolicy”).\n";
    exit(0);
}

$batch = $limit > 0 ? min($limit, $total) : $total;
echo '→ Obiektów bez adresu: ' . $total . '; w tym przebiegu przetworzę: ' . $batch . "\n";

$st = $pdo->prepare('SELECT id, lat, lon FROM places WHERE address IS NULL ORDER BY id LIMIT ?');
$st->execute([$batch]);
$rows = $st->fetchAll();

$update = $pdo->prepare('UPDATE places SET address = ? WHERE id = ?');

$done = 0;
$found = 0;
$errors = 0;

foreach ($rows as $row) {
    $addr = reverse_geocode((float)$row['lat'], (float)$row['lon']);

    if ($addr === null) {
        // Błąd sieci/HTTP — nie zapisujemy, spróbujemy w kolejnym przebiegu
        $errors++;
        if ($errors >= 5) {
            fwrite(STDERR, "✗ 5 błędów z rzędu — przerywam (Nominatim niedostępny?).\n");
            break;
        }
        sleep(5);
        continue;
    }

    $errors = 0;
    // Pusty string = "w okolicy nie ma nazwanej ulicy" — zapisany, żeby nie pytać ponownie
    $update->execute([$addr, $row['id']]);
    $done++;
    if ($addr !== '') {
        $found++;
    }

    if ($done % 50 === 0) {
        echo "   … $done/$batch (adresów: $found)\n";
    }

    // Limit Nominatim: maks. 1 zapytanie na sekundę
    usleep(1_100_000);
}

$left = (int)$pdo->query('SELECT COUNT(*) FROM places WHERE address IS NULL')->fetchColumn();
echo "✓ Przetworzono: $done, znalezionych adresów: $found, pozostało w kolejce: $left\n";
