<?php
declare(strict_types=1);

/**
 * Generator wpisów CRON z automatycznie wykrytymi ścieżkami serwera (CLI).
 *
 * Uruchom na serwerze docelowym (przez SSH albo zadanie jednorazowe w panelu):
 *   php bin/cron-setup.php
 *
 * Alternatywnie te same wpisy pokaże przeglądarka pod adresem
 * /cron-setup?key=... (po ustawieniu CRON_SETUP_KEY w config.php).
 */

require __DIR__ . '/../config.php';
require ROOT_DIR . '/src/helpers.php';

$info = cron_setup_info();

$isTty = function_exists('posix_isatty') && @posix_isatty(STDOUT);
$b = $isTty ? "\033[1m" : '';
$g = $isTty ? "\033[32m" : '';
$d = $isTty ? "\033[2m" : '';
$r = $isTty ? "\033[0m" : '';

echo "\n{$b}════════════════════════════════════════════════════════════════════\n";
echo " AktywneStrefy.pl — wpisy CRON (ścieżki wykryte automatycznie)\n";
echo "════════════════════════════════════════════════════════════════════{$r}\n\n";
echo "Wykryta ścieżka projektu: {$g}{$info['root']}{$r}\n";
echo "Wykryta binarka PHP:      {$g}{$info['php']}{$r}\n\n";

foreach ($info['warnings'] as $w) {
    echo "  ⚠ $w\n";
}
if ($info['warnings']) echo "\n";

echo "{$b}Skopiuj poniższe linie do konfiguracji crona w panelu hostingu:{$r}\n\n";
echo "{$d}# 1) Import danych z OpenStreetMap — poniedziałek 4:15 (~2–5 min){$r}\n";
echo "{$g}{$info['line1']}{$r}\n\n";
echo "{$d}# 2) Uzupełnianie adresów obiektów — codziennie 2:30 (~11 min/porcję){$r}\n";
echo "{$g}{$info['line2']}{$r}\n\n";
echo "{$d}Podgląd logów:\n";
echo "  {$info['dataDir']}/import.log    — przebieg importów z OpenStreetMap\n";
echo "  {$info['dataDir']}/geocode.log   — przebieg uzupełniania adresów{$r}\n";
echo "{$b}════════════════════════════════════════════════════════════════════{$r}\n\n";
