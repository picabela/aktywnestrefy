<?php
declare(strict_types=1);

/**
 * Generator unikalnych treści na podstrony obiektów (programmatic SEO).
 * Warianty zdań wybierane deterministycznie z sluga, żeby każda podstrona
 * miała stabilną, ale inną od sąsiednich treść.
 */
final class SeoText
{
    /** Deterministyczny wybór wariantu zdania dla danego obiektu */
    private static function pick(array $p, string $salt, array $variants): string
    {
        return $variants[crc32($p['slug'] . $salt) % count($variants)];
    }

    /** Akapity opisu obiektu */
    public static function description(array $p, array $equipment, array $nearby): array
    {
        $cat = CATEGORIES[$p['category']];
        $catName = mb_strtolower($cat['singular']);
        $where = $p['city']
            ? ($p['district'] ? "w dzielnicy {$p['district']} ({$p['city']})" : "w mieście {$p['city']}")
            : 'w Polsce';
        $paragraphs = [];

        // Akapit 1 — lokalizacja i charakter miejsca
        $paragraphs[] = self::pick($p, 'intro', [
            "To ogólnodostępny obiekt typu $catName $where. Wstęp jest bezpłatny i nie wymaga zapisów — przychodzisz i trenujesz.",
            "Ten $catName znajduje się $where. Jak wszystkie obiekty w naszym katalogu jest publiczny i darmowy — wystarczy przyjść.",
            "Miejsce do aktywności na świeżym powietrzu $where. Obiekt jest ogólnodostępny przez cały rok, bez opłat i karnetów.",
        ]);

        // Akapit 2 — sprzęt / infrastruktura
        if ($equipment) {
            $list = implode(', ', array_map('mb_strtolower', array_slice($equipment, 0, 6)));
            $more = count($equipment) > 6 ? ' i inne' : '';
            $paragraphs[] = self::pick($p, 'equip', [
                "Na miejscu znajdziesz: $list$more. Pełną listę wyposażenia — pochodzącą z danych OpenStreetMap — znajdziesz w sekcji „Sprzęt i wyposażenie” powyżej.",
                "Wyposażenie obiektu obejmuje: $list$more. Dane o sprzęcie pochodzą od społeczności OpenStreetMap i są regularnie aktualizowane.",
            ]);
        } else {
            $paragraphs[] = self::pick($p, 'equip0', [
                "Społeczność OpenStreetMap nie uzupełniła jeszcze listy wyposażenia tego obiektu. Byłeś tu? Możesz dodać te informacje w OSM — pomożesz kolejnym trenującym.",
                "Szczegółowa lista sprzętu nie została jeszcze opisana w danych OpenStreetMap. Jeśli znasz to miejsce, uzupełnij dane w OSM — zmiany pojawią się u nas po najbliższej aktualizacji.",
            ]);
        }

        // Akapit 3 — warunki treningowe (nawierzchnia / oświetlenie)
        $cond = [];
        if ($s = surface_label($p['surface'])) {
            $cond[] = "nawierzchnia to $s";
        }
        if ($p['lit'] !== null) {
            $cond[] = $p['lit']
                ? 'obiekt jest oświetlony, więc potrenujesz też po zmroku'
                : 'obiekt nie ma oświetlenia — zaplanuj trening za dnia';
        }
        if ($cond) {
            $paragraphs[] = mb_strtoupper(mb_substr(implode(', a ', $cond), 0, 1)) . mb_substr(implode(', a ', $cond), 1) . '.';
        }

        // Akapit 4 — okolica
        if ($nearby) {
            $n = count($nearby);
            $first = $nearby[array_key_first($nearby)];
            $dist = format_distance($first['distance_km']);
            $paragraphs[] = self::pick($p, 'near', [
                "W promieniu 10 km " . ($n === 1 ? "znajduje się jeszcze 1 podobny obiekt" : "znajdują się jeszcze co najmniej $n podobne obiekty") . " — najbliższy zaledwie $dist stąd. Listę znajdziesz w sekcji „W pobliżu”.",
                "Jeśli to miejsce jest zajęte, najbliższa alternatywa jest $dist stąd — sprawdź sekcję „W pobliżu”, gdzie zebraliśmy okoliczne obiekty tego typu.",
            ]);
        }

        // Akapit 5 — porada kategorii
        $tips = [
            'silownie-plenerowe' => [
                'Urządzenia na siłowniach plenerowych pracują z oporem własnego ciała — to bezpieczny sposób na trening ogólnorozwojowy niezależnie od wieku i poziomu zaawansowania.',
                'Trening na świeżym powietrzu poprawia nie tylko siłę, ale i odporność. Zacznij od 2–3 serii na każdym urządzeniu i stopniowo zwiększaj obciążenie.',
            ],
            'parki-kalisteniki' => [
                'Kalistenika to trening z masą własnego ciała: podciąganie, dipy, przysiady. Systematyczność na drążku daje efekty szybciej, niż myślisz — zacznij od negatywów i australijskiego podciągania.',
                'Do treningu street workout nie potrzebujesz nic poza drążkiem i konsekwencją. Rękawiczki treningowe albo magnezja przydadzą się przy dłuższych sesjach.',
            ],
            'skateparki' => [
                'Przed pierwszą wizytą sprawdź godziny mniejszego ruchu — rano i przed południem skateparki są zwykle luźniejsze, co ułatwia naukę nowych trików.',
                'Kask i ochraniacze to standard, szczególnie na betonie. Szanuj kolejność najazdów — pierwszeństwo ma osoba, która pierwsza ruszyła na przeszkodę.',
            ],
            'pumptracki' => [
                'Na pumptracku jeździ się bez pedałowania — prędkość generujesz „pompując” ciałem na muldach. Kask jest obowiązkowy, a rower sprawdź przed jazdą, zwłaszcza hamulce.',
                'Pumptrack to świetny trening kondycji i techniki jazdy dla całej rodziny. Początkujący powinni zaczynać od wolnych przejazdów i pustego toru.',
            ],
        ];
        $paragraphs[] = self::pick($p, 'tip', $tips[$p['category']]);

        return $paragraphs;
    }

    /** FAQ obiektu: pary [pytanie, odpowiedź] — także do JSON-LD FAQPage */
    public static function faq(array $p, array $equipment, string $displayName): array
    {
        $cat = CATEGORIES[$p['category']];
        $catName = mb_strtolower($cat['singular']);
        $faq = [];

        $faq[] = [
            "Czy wstęp na ten obiekt jest płatny?",
            "Nie — to publiczny, ogólnodostępny $catName. Korzystanie jest bezpłatne i nie wymaga rezerwacji ani karnetu.",
        ];

        if ($p['lit'] !== null) {
            $faq[] = [
                'Czy obiekt jest oświetlony po zmroku?',
                $p['lit']
                    ? 'Tak, według danych OpenStreetMap obiekt ma oświetlenie — możesz trenować także wieczorem.'
                    : 'Według danych OpenStreetMap obiekt nie ma oświetlenia. Zimą zaplanuj trening w godzinach dziennych.',
            ];
        }

        if ($equipment) {
            $faq[] = [
                'Jaki sprzęt jest dostępny na miejscu?',
                'Na wyposażeniu: ' . implode(', ', array_map('mb_strtolower', $equipment)) . '. Lista pochodzi z danych OpenStreetMap.',
            ];
        }

        $dojazd = $p['address']
            ? "Obiekt znajduje się przy: {$p['address']}" . ($p['city'] ? " w mieście {$p['city']}" : '') . '.'
            : ($p['city'] ? "Obiekt znajduje się " . ($p['district'] ? "w dzielnicy {$p['district']} w mieście {$p['city']}" : "w mieście {$p['city']}") . '.' : 'Dokładna lokalizacja jest zaznaczona na mapie powyżej.');
        $faq[] = [
            "Jak dojechać do tego miejsca?",
            $dojazd . ' Kliknij przycisk „Nawiguj”, aby otworzyć trasę w Google Maps z Twojej aktualnej lokalizacji.',
        ];

        $catFaq = [
            'silownie-plenerowe' => ['Czy siłownia plenerowa nadaje się dla seniorów?', 'Tak — urządzenia prowadzone (orbitrek, wioślarz, twister) są bezpieczne i często projektowane właśnie z myślą o seniorach. Ćwicz w swoim tempie i zaczynaj od krótkich serii.'],
            'parki-kalisteniki'  => ['Czy to miejsce nadaje się dla początkujących?', 'Tak — kalistenika skaluje się do każdego poziomu. Zamiast pełnych podciągnięć zacznij od zwisów, negatywów i podciągania australijskiego na niskim drążku.'],
            'skateparki'         => ['Czy na skatepark można wejść z hulajnogą, BMX-em lub rolkami?', 'Zdecydowana większość publicznych skateparków w Polsce dopuszcza deskorolki, hulajnogi, BMX-y i rolki. Szczegóły reguluje regulamin obiektu, zwykle wywieszony przy wejściu.'],
            'pumptracki'         => ['Czy na pumptracku obowiązuje kask?', 'Na większości polskich pumptracków kask jest obowiązkowy — wymaga tego regulamin obiektu. Zalecamy też ochraniacze, szczególnie dla dzieci i początkujących.'],
        ];
        $faq[] = $catFaq[$p['category']];

        return $faq;
    }
}
