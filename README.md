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
3. Ustaw crona odświeżającego dane raz dziennie/tygodniowo:
   ```
   15 4 * * 1 php /sciezka/do/bin/import.php >> /sciezka/do/data/import.log 2>&1
   ```
4. Zgłoś `https://aktywnestrefy.pl/sitemap.xml` w Google Search Console.

## Struktura

```
config.php          konfiguracja, kategorie, słowniki tagów OSM → PL
bin/import.php      importer Overpass API (upsert do SQLite)
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
