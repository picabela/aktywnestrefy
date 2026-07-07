# AktywneStrefy.pl

Katalog i wyszukiwarka miejsc do aktywności fizycznej na wolnym powietrzu w Polsce,
oparty na otwartych danych **OpenStreetMap** (Overpass API). Zaprojektowany pod
**programmatic SEO** — tysiące podstron long-tail typu
„siłownia plenerowa Kraków Bronowice" czy „skatepark betonowy Gdańsk".

## Kategorie obiektów

| Kategoria | Tagi OSM |
|---|---|
| Siłownie plenerowe | `leisure=fitness_station` (+ `fitness_station:*` jako lista sprzętu) |
| Parki kalisteniki | `sport=calisthenics`, stacje fitness złożone z drążków/poręczy |
| Skateparki | `sport=skateboard` |
| Pumptracki | `sport=bmx`, nazwy zawierające „pumptrack" |

## Stack

- **PHP 8.1+** — bez frameworka, jeden front controller (`public/index.php`)
- **SQLite** (PDO) — lekka baza plikowa, zero konfiguracji serwera
- **Leaflet + OpenStreetMap** — mapy bez kluczy API
- **Nominatim** — geokodowanie adresu wpisanego w wyszukiwarkę (client-side)
- **Open-Meteo** — prognoza pogody na stronie obiektu (bez klucza API)

## Uruchomienie lokalne

```bash
# 1. Import danych z Overpass API (cała Polska, ok. 2–5 minut)
php bin/import.php

#    …lub tylko wybrany obszar (S,W,N,E):
php bin/import.php --bbox=49.9,19.7,50.2,20.2

# 2. Serwer deweloperski
php -S localhost:8080 -t public public/index.php
```

Baza tworzy się automatycznie w `data/aktywnestrefy.sqlite`.

## Wdrożenie na hosting (np. współdzielony z Apache)

1. Wgraj repozytorium na serwer; **document root skieruj na katalog `public/`**
   (reguły mod_rewrite są w `public/.htaccess`).
2. Upewnij się, że PHP ma rozszerzenia `pdo_sqlite` i `curl`, a katalog `data/`
   jest zapisywalny przez PHP.
3. Ustaw crony. Gotowe wpisy z **automatycznie wykrytymi ścieżkami** serwera
   pokaże skrypt — na dwa sposoby:
   - **przez SSH:** `php bin/cron-setup.php`
   - **przez przeglądarkę:** ustaw `CRON_SETUP_KEY` w `config.php` na losowy
     ciąg i otwórz `https://aktywnestrefy.pl/cron-setup?key=TWOJ_KLUCZ`
     (bez klucza strona jest niedostępna — chroni ścieżki serwera; po
     skopiowaniu wpisów możesz z powrotem wyczyścić klucz)
   Wypisze m.in.:
   ```
   # odświeżanie danych z OpenStreetMap (poniedziałek 4:15)
   15 4 * * 1 /usr/bin/php /home/…/bin/import.php >> /home/…/data/import.log 2>&1
   # dogęszczanie adresów (co noc porcja 600 obiektów, ~11 min)
   30 2 * * * /usr/bin/php /home/…/bin/geocode.php >> /home/…/data/geocode.log 2>&1
   ```
4. Zgłoś `https://aktywnestrefy.pl/sitemap.xml` w Google Search Console.

### Baza danych a aktualizacje kodu (ważne!)

W repozytorium jest tylko **`data/seed.sqlite`** — baza-ziarno z gotowym
kompletem obiektów. Przy pierwszym uruchomieniu na serwerze aplikacja kopiuje
ją automatycznie do **`data/aktywnestrefy.sqlite`** (żywej bazy). Żywa baza
jest w `.gitignore`, więc:

- **kolejne wgrania plików z GitHuba NIE nadpisują żywej bazy** — Twoje opinie
  użytkowników, zgłoszenia i adresy uzupełnione przez cron są bezpieczne;
- `data/seed.sqlite` to tylko punkt startowy — po wdrożeniu żyje własnym życiem
  na serwerze i aktualizuje ją `bin/import.php` (obiekty) oraz cron (adresy).

### Aktualizacja strony jednym kliknięciem — `update.php`

Do aktualizacji służy **`public/update.php`** — samodzielny aktualizator,
który sam pobiera najnowszą wersję z GitHuba i podmienia pliki, chroniąc
dane produkcyjne (żywą bazę, logi, klucz `CRON_SETUP_KEY`) i robiąc przed
podmianą kopię zapasową bazy (trzyma 5 ostatnich w `data/backup-*.sqlite`).

- **przez przeglądarkę:** ustaw `CRON_SETUP_KEY` w `config.php`, otwórz
  `https://aktywnestrefy.pl/update.php?key=TWOJ_KLUCZ` (podgląd planu),
  potem to samo z dopiskiem `&run=1` (wykonanie);
- **przez SSH:** `php public/update.php --run`.

Masz na serwerze starszą wersję sprzed aktualizatora? Wgraj przez FTP sam
plik `public/update.php` do katalogu `public/`, dopisz `CRON_SETUP_KEY`
w `config.php` i uruchom przez przeglądarkę — reszta zaktualizuje się sama.
Aktualizator **sam sprawdza, czy jest coś nowego**: pobiera z GitHub API
identyfikator najnowszego commita (SHA) i porównuje go z zapisanym w
`data/installed-version.txt`. Jeśli wersje są równe, w trybie podglądu
napisze „masz już najnowszą wersję", a `--run` nic nie zrobi (chyba że
wymusisz `--force` / `&force=1`). Gdy API jest nieosiągalne, aktualizacja
i tak zadziała (bezpieczny fallback). Historia trafia do `data/update.log`.
Jeśli repozytorium na GitHubie jest prywatne, wpisz token w stałej
`GH_TOKEN` na początku `update.php`.

## Struktura

```
config.php          konfiguracja, kategorie, słowniki tagów OSM → PL
bin/import.php      importer Overpass API (upsert do SQLite)
bin/geocode.php     dogęszczanie adresów obiektów (Nominatim, 1 zapyt./sek.)
bin/cron-setup.php  wypisuje wpisy crona z automatycznie wykrytymi ścieżkami
data/seed.sqlite    baza-ziarno (w repo); kopiowana do żywej bazy przy 1. starcie
src/
  Database.php      połączenie + migracje SQLite
  Repo.php          zapytania (nearby, bbox, miasta, opinie…)
  Cities.php        ~160 miast PL do przypisywania obiektów (programmatic SEO)
  helpers.php       slugify, haversine, renderowanie widoków
  views/            szablony PHP (layout, home, kategoria, miasto, obiekt…)
public/
  index.php         routing + kontrolery + API JSON + sitemap.xml
  assets/           CSS, JS, logo
data/               baza SQLite (tworzona automatycznie)
```

## Funkcje

- Wyszukiwanie po **adresie** (geokodowanie Nominatim) lub **geolokalizacji**
  („📍 Blisko mnie"), wyniki sortowane po odległości
- Strony **kategoria → miasto → obiekt** z czystymi URL-ami i unikalnymi meta tagami
- Karta obiektu: mapa, **adres** (z tagów OSM lub reverse-geokodowania Nominatim,
  cache'owany w bazie), **lista sprzętu po polsku**, nawierzchnia, oświetlenie,
  **Street View / Mapillary**, nawigacja Google Maps, **prognoza pogody na trening**,
  auto-generowany opis i FAQ (programmatic SEO)
- **Opinie i oceny** użytkowników (SQLite, honeypot antyspamowy)
- **Ulubione miejsca** w localStorage (bez kont)
- Zgłaszanie błędów w danych + instrukcja poprawiania OSM
- `sitemap.xml` generowany dynamicznie, dane strukturalne
  (schema.org `SportsActivityLocation`, `FAQPage`, `AggregateRating`,
  `BreadcrumbList`, `ItemList`, `WebSite`+`SearchAction`, `Organization`),
  geo meta tagi, Open Graph + Twitter Cards, `llms.txt` dla wyszukiwarek AI
- Wymuszone HTTPS i domena bez www (301 w `.htaccess`)
- Tryb ciemny, RWD, pełnoekranowa mapa Polski z filtrem kategorii

## Licencja danych

Dane o obiektach: © autorzy [OpenStreetMap](https://www.openstreetmap.org),
licencja [ODbL](https://opendatacommons.org/licenses/odbl/).
