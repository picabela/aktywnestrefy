<?php
declare(strict_types=1);

/**
 * AktywneStrefy.pl — samodzielny aktualizator strony.
 *
 * Pobiera najnowszą wersję kodu z GitHuba i podmienia pliki na serwerze,
 * NIE RUSZAJĄC danych produkcyjnych:
 *   • data/aktywnestrefy.sqlite (żywa baza: opinie, oceny, adresy z crona)
 *   • data/*.log, data/*.lock, kopie zapasowe data/backup-*
 *   • wartości CRON_SETUP_KEY w config.php (przenoszona do nowej wersji)
 * Przed podmianą robi kopię zapasową żywej bazy (trzyma 5 ostatnich).
 *
 * UŻYCIE PRZEZ PRZEGLĄDARKĘ (wymaga klucza — patrz niżej):
 *   1. W config.php ustaw CRON_SETUP_KEY na długi losowy ciąg (raz).
 *   2. Otwórz: https://aktywnestrefy.pl/update.php?key=TWOJ_KLUCZ
 *      — zobaczysz plan aktualizacji (nic się jeszcze nie zmienia).
 *   3. Kliknij / otwórz ten sam adres z dopiskiem &run=1 — wykona aktualizację.
 *
 * UŻYCIE PRZEZ SSH:
 *   php public/update.php          # pokazuje plan
 *   php public/update.php --run    # wykonuje aktualizację
 *
 * Ten plik jest częścią repozytorium, więc przy każdej aktualizacji
 * sam też się aktualizuje — kolejne wersje wgrywasz tym samym adresem.
 */

/* ── Konfiguracja źródła aktualizacji ─────────────────────────── */
const GH_OWNER  = 'picabela';
const GH_REPO   = 'aktywnestrefy';
const GH_BRANCH = 'claude/aktywne-strefy-php-site-ark761';
const GH_TOKEN  = '';   // wymagany tylko, gdy repozytorium jest prywatne

/* ── Ustalenie katalogu projektu ──────────────────────────────── */
// Plik leży w public/, więc projekt to katalog wyżej. Gdy ktoś wgra go
// do katalogu głównego projektu — też zadziała (szukamy config.php).
$root = is_file(dirname(__DIR__) . '/config.php') ? dirname(__DIR__)
      : (is_file(__DIR__ . '/config.php') ? __DIR__ : null);

$isCli = PHP_SAPI === 'cli';
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
}
set_time_limit(600);

function out(string $s): void { echo $s . "\n"; @ob_flush(); @flush(); }
function fail(string $s): never { out(''); out('✗ BŁĄD: ' . $s); exit(1); }

if ($root === null) {
    fail('Nie znaleziono config.php — wgraj update.php do katalogu public/ projektu.');
}

/* ── Autoryzacja ──────────────────────────────────────────────── */
$configRaw = (string)file_get_contents($root . '/config.php');
preg_match("/const\s+CRON_SETUP_KEY\s*=\s*'([^']*)'/", $configRaw, $m);
$secret = $m[1] ?? '';

if (!$isCli) {
    if ($secret === '') {
        http_response_code(403);
        fail("Aktualizator wyłączony.\n\nUstaw CRON_SETUP_KEY w config.php na długi losowy ciąg, a następnie\notwórz: /update.php?key=TWOJ_KLUCZ\n\n(Starsza wersja strony bez tej stałej? Dodaj w config.php linię:\n  const CRON_SETUP_KEY = 'twoj-losowy-ciag';\nzaraz pod const APP_EMAIL.)");
    }
    if (!hash_equals($secret, (string)($_GET['key'] ?? ''))) {
        http_response_code(404);
        fail('Nie znaleziono strony.');
    }
}

$doRun = $isCli ? in_array('--run', $_SERVER['argv'] ?? [], true) : isset($_GET['run']);
// --force / &force=1 — aktualizuj nawet gdy wersja jest już aktualna
$force = $isCli ? in_array('--force', $_SERVER['argv'] ?? [], true) : isset($_GET['force']);

// Lokalny plik zip zamiast pobierania — wyłącznie z CLI (do testów)
$localZip = null;
if ($isCli) {
    foreach ($_SERVER['argv'] ?? [] as $arg) {
        if (str_starts_with($arg, '--zip=')) {
            $localZip = substr($arg, 6);
        }
    }
}

/* ── Sprawdzenie wersji: lokalny SHA vs najnowszy commit na GitHubie ── */
$versionFile = $root . '/data/installed-version.txt';
$installedSha = is_file($versionFile) ? trim((string)file_get_contents($versionFile)) : '';
$remoteSha = $localZip !== null ? null : github_latest_sha();
$upToDate  = $remoteSha !== null && $installedSha !== '' && $installedSha === $remoteSha;

/* ── Plan / nagłówek ──────────────────────────────────────────── */
out('════════════════════════════════════════════════════════════════');
out(' AktywneStrefy.pl — aktualizator');
out('════════════════════════════════════════════════════════════════');
out('');
out('Katalog projektu: ' . $root);
out('Źródło: github.com/' . GH_OWNER . '/' . GH_REPO . ' (gałąź: ' . GH_BRANCH . ')');
out('');

// Status wersji
if ($localZip !== null) {
    out('Wersja: pomijam sprawdzanie (lokalna paczka).');
} elseif ($remoteSha === null) {
    out('Wersja: nie udało się sprawdzić najnowszej wersji na GitHubie');
    out('        (brak sieci lub limit zapytań) — aktualizacja i tak zadziała.');
} else {
    out('Zainstalowana wersja: ' . ($installedSha !== '' ? substr($installedSha, 0, 7) : 'nieznana (pierwsza aktualizacja)'));
    out('Najnowsza na GitHubie: ' . substr($remoteSha, 0, 7));
    out($upToDate
        ? 'Status: ✓ masz już najnowszą wersję.'
        : 'Status: ⟳ dostępna jest nowsza wersja.');
}
out('');
out('Chronione (nigdy nie nadpisywane):');
out('  • data/aktywnestrefy.sqlite — żywa baza (opinie, oceny, adresy)');
out('  • data/*.log, data/*.lock — dotychczasowe logi');
out('  • data/backup-* — kopie zapasowe');
out('  • CRON_SETUP_KEY — Twój klucz zostaje przeniesiony do nowej wersji');
out('');

if (!extension_loaded('zip')) {
    fail('Brak rozszerzenia PHP "zip" (ZipArchive) — włącz je w panelu hostingu.');
}
if (!is_writable($root)) {
    fail('Katalog projektu nie jest zapisywalny przez PHP: ' . $root);
}

if (!$doRun) {
    out('To był tylko podgląd — nic nie zostało zmienione.');
    out('');
    if ($upToDate && !$force) {
        out('Nie ma nic do zaktualizowania. Gdybyś mimo to chciał wymusić ponowne');
        out($isCli
            ? 'wgranie plików, użyj:  php public/update.php --run --force'
            : 'wgranie plików, dodaj do adresu:  &run=1&force=1');
    } else {
        out($isCli
            ? 'Aby wykonać aktualizację, uruchom:  php public/update.php --run'
            : 'Aby wykonać aktualizację, otwórz ten sam adres z dopiskiem &run=1');
    }
    exit(0);
}

// Wykonanie wstrzymane, gdy wersja jest już aktualna (chyba że --force)
if ($upToDate && !$force) {
    out('✓ Masz już najnowszą wersję — nie ma nic do zaktualizowania.');
    out('  (Aby mimo to wymusić ponowne wgranie plików, dodaj --force / &force=1.)');
    exit(0);
}

/* ── 1. Pobranie paczki ───────────────────────────────────────── */
$dataDir = $root . '/data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0775, true);
}
$zipPath = $dataDir . '/update-download.zip';

if ($localZip !== null) {
    out('→ Używam lokalnej paczki: ' . $localZip);
    if (!is_file($localZip) || !copy($localZip, $zipPath)) {
        fail('Nie można odczytać wskazanej paczki zip.');
    }
} else {
    $url = 'https://codeload.github.com/' . GH_OWNER . '/' . GH_REPO
         . '/zip/refs/heads/' . str_replace('%2F', '/', rawurlencode(GH_BRANCH));
    out('→ Pobieram najnowszą wersję z GitHuba…');
    $fh = fopen($zipPath, 'wb');
    $ch = curl_init($url);
    $headers = ['User-Agent: AktywneStrefy-Updater'];
    if (GH_TOKEN !== '') {
        $headers[] = 'Authorization: Bearer ' . GH_TOKEN;
    }
    curl_setopt_array($ch, [
        CURLOPT_FILE           => $fh,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 300,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    $ok = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    fclose($fh);
    if ($ok === false || $status !== 200) {
        @unlink($zipPath);
        fail("Pobieranie nie powiodło się (HTTP $status $err)."
           . ($status === 404 ? "\nJeśli repozytorium jest prywatne, ustaw GH_TOKEN na początku pliku update.php." : ''));
    }
    out('   pobrano ' . number_format((float)filesize($zipPath) / 1048576, 1) . ' MB');
}

/* ── 2. Rozpakowanie ──────────────────────────────────────────── */
$tmpDir = $dataDir . '/update-tmp';
rrmdir($tmpDir);
mkdir($tmpDir, 0775, true);

out('→ Rozpakowuję…');
$zip = new ZipArchive();
if ($zip->open($zipPath) !== true) {
    fail('Uszkodzona paczka zip.');
}
$zip->extractTo($tmpDir);
$zip->close();
@unlink($zipPath);

// Zip z GitHuba ma jeden katalog nadrzędny (repo-gałąź) — znajdź go
$entries = array_values(array_diff(scandir($tmpDir) ?: [], ['.', '..']));
$srcRoot = count($entries) === 1 && is_dir($tmpDir . '/' . $entries[0])
    ? $tmpDir . '/' . $entries[0]
    : $tmpDir;

// Walidacja: czy to na pewno paczka AktywneStrefy?
if (!is_file($srcRoot . '/config.php') || !str_contains((string)file_get_contents($srcRoot . '/config.php'), 'AktywneStrefy')) {
    rrmdir($tmpDir);
    fail('Paczka nie wygląda na projekt AktywneStrefy — przerwano dla bezpieczeństwa.');
}

/* ── 3. Kopia zapasowa żywej bazy ─────────────────────────────── */
$liveDb = $dataDir . '/aktywnestrefy.sqlite';
if (is_file($liveDb)) {
    $backup = $dataDir . '/backup-' . date('Ymd-His') . '.sqlite';
    out('→ Kopia zapasowa bazy: data/' . basename($backup));
    if (!copy($liveDb, $backup)) {
        rrmdir($tmpDir);
        fail('Nie udało się utworzyć kopii zapasowej bazy — przerwano.');
    }
    // Rotacja: zostaw 5 najnowszych kopii
    $backups = glob($dataDir . '/backup-*.sqlite') ?: [];
    rsort($backups);
    foreach (array_slice($backups, 5) as $old) {
        @unlink($old);
    }
}

/* ── 4. Podmiana plików (z listą chronionych) ─────────────────── */
out('→ Podmieniam pliki…');
$stats = ['copied' => 0, 'skipped' => 0];
sync_dir($srcRoot, $root, '', $stats);
out('   zaktualizowano plików: ' . $stats['copied'] . ', pominięto (chronione): ' . $stats['skipped']);

/* ── 5. Przeniesienie klucza do nowego config.php ─────────────── */
if ($secret !== '') {
    $newConfig = (string)file_get_contents($root . '/config.php');
    $patched = preg_replace(
        "/const\s+CRON_SETUP_KEY\s*=\s*'[^']*'/",
        "const CRON_SETUP_KEY = '" . $secret . "'",
        $newConfig, 1
    );
    if ($patched !== null && $patched !== $newConfig) {
        file_put_contents($root . '/config.php', $patched);
        out('→ CRON_SETUP_KEY przeniesiony do nowej wersji config.php');
    }
}

/* ── 6. Zapis wersji, sprzątanie i wpis do logu ───────────────── */
// Zapamiętaj wgrany SHA, żeby następnym razem wiedzieć, czy jest coś nowego
if ($remoteSha !== null) {
    file_put_contents($versionFile, $remoteSha . "\n");
    out('→ Zapisano wersję: ' . substr($remoteSha, 0, 7));
}
rrmdir($tmpDir);
file_put_contents(
    $dataDir . '/update.log',
    '[' . date('Y-m-d H:i:s') . '] aktualizacja OK — plików: ' . $stats['copied']
        . ($remoteSha !== null ? ' — wersja ' . substr($remoteSha, 0, 7) : '') . "\n",
    FILE_APPEND
);

out('');
out('✓ AKTUALIZACJA ZAKOŃCZONA POMYŚLNIE');
out('  Żywa baza, logi i klucz pozostały nietknięte.');
out('  Historia aktualizacji: data/update.log');
out('════════════════════════════════════════════════════════════════');

/* ── Funkcje pomocnicze ───────────────────────────────────────── */

/**
 * Najnowszy SHA commita gałęzi z GitHub API (identyfikuje wersję kodu).
 * Zwraca 40-znakowy hash albo null, gdy nie udało się sprawdzić.
 */
function github_latest_sha(): ?string
{
    $url = 'https://api.github.com/repos/' . GH_OWNER . '/' . GH_REPO
         . '/commits/' . rawurlencode(GH_BRANCH);
    $headers = ['User-Agent: AktywneStrefy-Updater', 'Accept: application/vnd.github+json'];
    if (GH_TOKEN !== '') {
        $headers[] = 'Authorization: Bearer ' . GH_TOKEN;
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($body === false || $status !== 200) {
        return null;
    }
    $sha = json_decode((string)$body, true)['sha'] ?? null;
    return (is_string($sha) && preg_match('/^[0-9a-f]{40}$/', $sha)) ? $sha : null;
}

/** Czy ścieżka względna (od katalogu projektu) jest chroniona przed nadpisaniem? */
function is_protected(string $rel): bool
{
    if ($rel === 'data/aktywnestrefy.sqlite') return true;
    if (preg_match('#^data/.*\.(log|lock|sqlite-wal|sqlite-shm|sqlite-journal)$#', $rel)) return true;
    if (str_starts_with($rel, 'data/backup-')) return true;
    return false;
}

/** Rekurencyjna kopia nowej wersji na starą z pomijaniem chronionych plików */
function sync_dir(string $src, string $dst, string $rel, array &$stats): void
{
    foreach (array_diff(scandir($src) ?: [], ['.', '..']) as $item) {
        $srcPath = $src . '/' . $item;
        $dstPath = $dst . '/' . $item;
        $relPath = ltrim($rel . '/' . $item, '/');

        if (is_dir($srcPath)) {
            if (!is_dir($dstPath)) {
                mkdir($dstPath, 0775, true);
            }
            sync_dir($srcPath, $dstPath, $relPath, $stats);
        } else {
            if (is_protected($relPath)) {
                $stats['skipped']++;
                continue;
            }
            if (!copy($srcPath, $dstPath)) {
                fail('Nie można zapisać pliku: ' . $relPath . ' — sprawdź uprawnienia.');
            }
            $stats['copied']++;
        }
    }
}

/** Rekurencyjne usunięcie katalogu */
function rrmdir(string $dir): void
{
    if (!is_dir($dir)) return;
    foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $item) {
        $path = $dir . '/' . $item;
        is_dir($path) ? rrmdir($path) : @unlink($path);
    }
    @rmdir($dir);
}
