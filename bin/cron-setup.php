<?php
declare(strict_types=1);

/**
 * Generator wpisów CRON z automatycznie wykrytymi ścieżkami serwera.
 *
 * Uruchom na serwerze docelowym (przez SSH albo zadanie jednorazowe w panelu),
 * skopiuj wypisane linie i wklej je do konfiguracji crona hostingu:
 *
 *   php bin/cron-setup.php
 *
 * Skrypt sam ustala ścieżkę projektu i binarki PHP — nic nie trzeba
 * podmieniać ręcznie.
 */

require __DIR__ . '/../config.php';

$root    = ROOT_DIR;                          // katalog projektu (z config.php)
$php     = PHP_BINARY ?: 'php';               // ścieżka do interpretera PHP (CLI)
$import  = $root . '/bin/import.php';
$geocode = $root . '/bin/geocode.php';
$dataDir = $root . '/data';

// Na niektórych hostingach PHP_BINARY wskazuje na wersję CGI/FPM zamiast CLI.
$phpNote = '';
if (PHP_BINARY && !str_contains(strtolower(PHP_SAPI), 'cli')) {
    $phpNote = "  ⚠ Uwaga: skrypt uruchomiono przez SAPI \"" . PHP_SAPI . "\", nie CLI.\n"
             . "     Jeśli cron zgłasza błąd, użyj ścieżki do PHP CLI z panelu hostingu\n"
             . "     (często: /usr/bin/php, /usr/local/bin/php lub php83).\n\n";
}

// Kolory tylko gdy wyjście idzie na terminal
$isTty  = function_exists('posix_isatty') && @posix_isatty(STDOUT);
$bold   = $isTty ? "\033[1m" : '';
$green  = $isTty ? "\033[32m" : '';
$dim    = $isTty ? "\033[2m" : '';
$reset  = $isTty ? "\033[0m" : '';

$line1 = sprintf(
    '15 4 * * 1 %s %s >> %s/import.log 2>&1',
    $php, $import, $dataDir
);
$line2 = sprintf(
    '30 2 * * * %s %s >> %s/geocode.log 2>&1',
    $php, $geocode, $dataDir
);

// Ostrzeżenie, jeśli pliki nie istnieją pod wykrytą ścieżką
$warn = '';
foreach (['import.php' => $import, 'geocode.php' => $geocode] as $name => $path) {
    if (!is_file($path)) {
        $warn .= "  ⚠ Nie znaleziono pliku: $path\n";
    }
}
if (!is_writable($dataDir) && is_dir($dataDir)) {
    $warn .= "  ⚠ Katalog data/ nie jest zapisywalny — logi i baza mogą się nie utworzyć.\n";
    $warn .= "     Nadaj uprawnienia: chmod 775 $dataDir\n";
}

echo <<<TXT

{$bold}════════════════════════════════════════════════════════════════════
 AktywneStrefy.pl — wpisy CRON (ścieżki wykryte automatycznie)
════════════════════════════════════════════════════════════════════{$reset}

Wykryta ścieżka projektu: {$green}{$root}{$reset}
Wykryta binarka PHP:      {$green}{$php}{$reset}

{$phpNote}{$warn}
{$bold}Skopiuj poniższe linie do konfiguracji crona w panelu hostingu:{$reset}

{$dim}# 1) Import danych z OpenStreetMap — poniedziałek 4:15 (~2–5 min){$reset}
{$green}{$line1}{$reset}

{$dim}# 2) Uzupełnianie adresów obiektów — codziennie 2:30 (~11 min/porcję){$reset}
{$green}{$line2}{$reset}

{$dim}Podgląd logów:
  {$dataDir}/import.log    — przebieg importów z OpenStreetMap
  {$dataDir}/geocode.log   — przebieg uzupełniania adresów{$reset}

{$bold}════════════════════════════════════════════════════════════════════{$reset}

TXT;
