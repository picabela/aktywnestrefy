<?php
/**
 * AktywneStrefy.pl — konfiguracja globalna
 */

declare(strict_types=1);

const APP_NAME   = 'AktywneStrefy.pl';
const APP_DOMAIN = 'aktywnestrefy.pl';
const APP_URL    = 'https://aktywnestrefy.pl';
const APP_EMAIL  = 'kontakt@aktywnestrefy.pl';

/**
 * Sekretny klucz do podglądu wpisów crona przez przeglądarkę:
 *   https://aktywnestrefy.pl/cron-setup?key=TWOJ_KLUCZ
 * Puste = funkcja wyłączona (bezpieczne domyślnie). Ustaw długi, losowy
 * ciąg, żeby zobaczyć gotowe polecenia bez logowania się przez SSH.
 */
const CRON_SETUP_KEY = '';

define('ROOT_DIR', __DIR__);
define('DB_FILE', ROOT_DIR . '/data/aktywnestrefy.sqlite');

/**
 * Kategorie obiektów — klucz = slug w URL.
 * Każda kategoria ma własne long-tailowe szablony SEO.
 */
const CATEGORIES = [
    'silownie-plenerowe' => [
        'name'       => 'Siłownie plenerowe',
        'singular'   => 'Siłownia plenerowa',
        'locative'   => 'siłowni plenerowych',   // "lista siłowni plenerowych"
        'icon'       => 'dumbbell',
        'color'      => '#e0332c',
        'lead'       => 'Darmowe siłownie zewnętrzne pod chmurką — orbitreki, wioślarze, wyciskanie i więcej. Trenuj za darmo, na świeżym powietrzu.',
        'seo_title'  => 'Siłownie plenerowe — mapa i katalog siłowni zewnętrznych w Polsce',
        'seo_desc'   => 'Znajdź siłownię plenerową blisko siebie. Mapa, lista sprzętu, nawierzchnia i oświetlenie. Ponad tysiąc darmowych siłowni zewnętrznych w całej Polsce.',
        'faq'        => [
            ['Czy korzystanie z siłowni plenerowej jest darmowe?', 'Tak — siłownie plenerowe to ogólnodostępne obiekty publiczne. Korzystanie z nich jest bezpłatne i nie wymaga zapisów ani karnetu.'],
            ['Jakie urządzenia znajdę na siłowni plenerowej?', 'Najczęściej: orbitrek, wioślarz, wyciskanie siedząc, wyciąg górny, biegacz, twister i rowerek. Na podstronie każdego obiektu znajdziesz listę sprzętu z danych OpenStreetMap.'],
            ['Czy na siłowni plenerowej można trenować zimą?', 'Tak, urządzenia są odporne na warunki atmosferyczne. Warto założyć rękawiczki treningowe — metalowe uchwyty bywają zimne — i uważać na oblodzenie.'],
        ],
    ],
    'parki-kalisteniki' => [
        'name'       => 'Parki kalisteniki',
        'singular'   => 'Park kalisteniki',
        'locative'   => 'parków kalisteniki',
        'icon'       => 'bars',
        'color'      => '#0d7a3f',
        'lead'       => 'Drążki do podciągania, poręcze, drabinki i rurki — street workout parki do treningu z masą własnego ciała.',
        'seo_title'  => 'Parki kalisteniki i street workout — mapa drążków do podciągania w Polsce',
        'seo_desc'   => 'Katalog parków kalisteniki i street workout. Znajdź drążki do podciągania, poręcze i drabinki blisko siebie — z mapą i listą sprzętu.',
        'faq'        => [
            ['Czym różni się park kalisteniki od siłowni plenerowej?', 'Park kalisteniki (street workout) to zestaw drążków, poręczy i drabinek do ćwiczeń z masą własnego ciała. Siłownia plenerowa ma urządzenia prowadzone (orbitrek, wioślarz itd.). Wiele lokalizacji łączy oba typy.'],
            ['Jak zacząć trening kalisteniki?', 'Podstawa to podciąganie na drążku, pompki na poręczach i przysiady. Zacznij od wersji ułatwionych (australijskie podciąganie, negatywy) i trenuj 2–3 razy w tygodniu.'],
            ['Czego szukać w dobrym parku do street workoutu?', 'Drążki na różnych wysokościach, poręcze równoległe, bezpieczna nawierzchnia (poliuretan lub piasek) i oświetlenie, jeśli trenujesz po zmroku. Te informacje znajdziesz przy każdym obiekcie w naszym katalogu.'],
        ],
    ],
    'skateparki' => [
        'name'       => 'Skateparki',
        'singular'   => 'Skatepark',
        'locative'   => 'skateparków',
        'icon'       => 'skate',
        'color'      => '#1a1a1a',
        'lead'       => 'Betonowe i modułowe skateparki — bowle, rampy, raile i schody. Dla deskorolki, BMX-a, hulajnogi i rolek.',
        'seo_title'  => 'Skateparki w Polsce — mapa i katalog betonowych i modułowych skateparków',
        'seo_desc'   => 'Wszystkie skateparki w Polsce na jednej mapie. Sprawdź nawierzchnię, przeszkody i oświetlenie zanim pojedziesz. Deskorolka, BMX, hulajnoga, rolki.',
        'faq'        => [
            ['Skatepark betonowy czy modułowy — jaka różnica?', 'Betonowe skateparki (monolityczne) są cichsze, trwalsze i płynniejsze w jeździe. Modułowe (sklejka/metal na asfalcie) są tańsze i łatwiejsze do przebudowy, ale głośniejsze. Nawierzchnię obiektu podajemy tam, gdzie jest w danych OSM.'],
            ['Czy na skatepark można wejść z hulajnogą lub BMX-em?', 'Zdecydowana większość polskich skateparków jest otwarta dla deskorolek, BMX-ów, hulajnóg i rolek. Zasady pierwszeństwa reguluje zwykle regulamin obiektu.'],
            ['Czy wstęp na skatepark jest płatny?', 'Publiczne skateparki miejskie są bezpłatne i ogólnodostępne. Płatne bywają jedynie prywatne hale i skateparki kryte.'],
        ],
    ],
    'pumptracki' => [
        'name'       => 'Pumptracki',
        'singular'   => 'Pumptrack',
        'locative'   => 'pumptracków',
        'icon'       => 'wave',
        'color'      => '#e07b00',
        'lead'       => 'Asfaltowe i ziemne tory pumptrack — muldy i bandy do jazdy „na pompowanie" rowerem, hulajnogą i rolkami.',
        'seo_title'  => 'Pumptracki w Polsce — mapa asfaltowych i ziemnych torów pumptrack',
        'seo_desc'   => 'Katalog pumptracków w całej Polsce. Znajdź asfaltowy lub ziemny tor rowerowy blisko siebie — mapa, dojazd, zdjęcia okolicy.',
        'faq'        => [
            ['Co to jest pumptrack?', 'Pumptrack to zamknięty tor z muld i profilowanych zakrętów (band), po którym jedzie się bez pedałowania — prędkość generuje się „pompując" ciałem. Świetny trening dla rowerzystów w każdym wieku.'],
            ['Pumptrack asfaltowy czy ziemny?', 'Asfaltowe są uniwersalne — pojeździsz rowerem, hulajnogą, rolkami i deskorolką, niezależnie od pogody. Ziemne (dirtowe) są bardziej wymagające i lubiane przez zaawansowanych riderów MTB.'],
            ['Jaki rower na pumptrack?', 'Najlepiej sprawdzają się rowery dirt/street i MTB hardtail, ale na większości torów pojeździsz każdym sprawnym rowerem. Kask jest obowiązkowy na większości obiektów.'],
        ],
    ],
];

/**
 * Mapowanie tagów OSM fitness_station:* → polskie nazwy sprzętu.
 */
const EQUIPMENT_LABELS = [
    'horizontal_bar'    => 'Drążek do podciągania',
    'parallel_bars'     => 'Poręcze równoległe',
    'push-up'           => 'Stanowisko do pompek',
    'sit-up'            => 'Ławka do brzuszków',
    'rings'             => 'Kółka gimnastyczne',
    'wall_bars'         => 'Drabinka',
    'horizontal_ladder' => 'Drabinka pozioma',
    'monkey_bars'       => 'Małpi gaj (rurki)',
    'elliptical_trainer'=> 'Orbitrek',
    'air_walker'        => 'Biegacz (air walker)',
    'exercise_bike'     => 'Rowerek',
    'rower'             => 'Wioślarz',
    'chest_press'       => 'Wyciskanie siedząc',
    'lat_pulldown'      => 'Wyciąg górny',
    'leg_press'         => 'Wypychanie nogami',
    'twister'           => 'Twister',
    'ski_trainer'       => 'Narciarz',
    'surfboard'         => 'Surfer',
    'balance'           => 'Równoważnia',
    'slalom'            => 'Slalom',
    'stretch_bars'      => 'Drążki do rozciągania',
    'sign'              => 'Tablica z instrukcją',
    'battling_ropes'    => 'Liny treningowe',
    'box'               => 'Skrzynia plyo',
    'slackline'         => 'Slackline',
    'climbing'          => 'Ścianka wspinaczkowa',
    'hyperextension'    => 'Ławka rzymska',
    'bench'             => 'Ławka treningowa',
    'captains_chair'    => 'Krzesło kapitańskie',
    'stepper'           => 'Stepper',
    'pendulum'          => 'Wahadło',
    'body_lift'         => 'Podciąg ciała',
    'pull_handles'      => 'Uchwyty do przyciągania',
];

const SURFACE_LABELS = [
    'concrete'       => 'beton',
    'asphalt'        => 'asfalt',
    'paving_stones'  => 'kostka brukowa',
    'grass'          => 'trawa',
    'sand'           => 'piasek',
    'dirt'           => 'nawierzchnia ziemna',
    'earth'          => 'nawierzchnia ziemna',
    'ground'         => 'nawierzchnia gruntowa',
    'gravel'         => 'żwir',
    'fine_gravel'    => 'drobny żwir',
    'tartan'         => 'tartan',
    'rubber'         => 'guma (bezpieczna)',
    'rubbercrumb'    => 'guma (bezpieczna)',
    'wood'           => 'drewno',
    'metal'          => 'metal',
    'compacted'      => 'nawierzchnia utwardzona',
    'woodchips'      => 'zrębki drewniane',
    'artificial_turf'=> 'sztuczna trawa',
    'metal_grid'     => 'kratownica metalowa',
];
