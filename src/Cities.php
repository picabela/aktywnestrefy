<?php
declare(strict_types=1);

/**
 * Lista miast Polski używana do przypisywania obiektów OSM do stron miejskich
 * (programmatic SEO) na podstawie najbliższego miasta.
 */
final class Cities
{
    /** [nazwa, lat, lon] — większe miasta Polski */
    public const LIST = [
        ['Warszawa', 52.2297, 21.0122],
        ['Kraków', 50.0647, 19.9450],
        ['Łódź', 51.7592, 19.4560],
        ['Wrocław', 51.1079, 17.0385],
        ['Poznań', 52.4064, 16.9252],
        ['Gdańsk', 54.3520, 18.6466],
        ['Szczecin', 53.4285, 14.5528],
        ['Bydgoszcz', 53.1235, 18.0084],
        ['Lublin', 51.2465, 22.5684],
        ['Białystok', 53.1325, 23.1688],
        ['Katowice', 50.2649, 19.0238],
        ['Gdynia', 54.5189, 18.5305],
        ['Częstochowa', 50.8118, 19.1203],
        ['Radom', 51.4027, 21.1471],
        ['Toruń', 53.0138, 18.5984],
        ['Sosnowiec', 50.2863, 19.1041],
        ['Rzeszów', 50.0412, 21.9991],
        ['Kielce', 50.8661, 20.6286],
        ['Gliwice', 50.2945, 18.6714],
        ['Olsztyn', 53.7784, 20.4801],
        ['Zabrze', 50.3249, 18.7857],
        ['Bielsko-Biała', 49.8224, 19.0584],
        ['Bytom', 50.3483, 18.9157],
        ['Zielona Góra', 51.9356, 15.5062],
        ['Rybnik', 50.1022, 18.5463],
        ['Ruda Śląska', 50.2558, 18.8555],
        ['Opole', 50.6751, 17.9213],
        ['Tychy', 50.1372, 18.9640],
        ['Gorzów Wielkopolski', 52.7368, 15.2288],
        ['Elbląg', 54.1522, 19.4088],
        ['Płock', 52.5463, 19.7065],
        ['Dąbrowa Górnicza', 50.3216, 19.1948],
        ['Wałbrzych', 50.7714, 16.2843],
        ['Włocławek', 52.6483, 19.0678],
        ['Tarnów', 50.0121, 20.9858],
        ['Chorzów', 50.2974, 18.9546],
        ['Koszalin', 54.1943, 16.1722],
        ['Kalisz', 51.7611, 18.0910],
        ['Legnica', 51.2070, 16.1553],
        ['Grudziądz', 53.4837, 18.7536],
        ['Jaworzno', 50.2044, 19.2749],
        ['Słupsk', 54.4641, 17.0287],
        ['Jastrzębie-Zdrój', 49.9496, 18.5748],
        ['Nowy Sącz', 49.6249, 20.6915],
        ['Jelenia Góra', 50.9044, 15.7194],
        ['Siedlce', 52.1676, 22.2902],
        ['Mysłowice', 50.2078, 19.1668],
        ['Konin', 52.2230, 18.2512],
        ['Piła', 53.1516, 16.7378],
        ['Piotrków Trybunalski', 51.4046, 19.7031],
        ['Inowrocław', 52.7982, 18.2637],
        ['Lubin', 51.4008, 16.2013],
        ['Ostrów Wielkopolski', 51.6494, 17.8107],
        ['Suwałki', 54.1118, 22.9309],
        ['Stargard', 53.3364, 15.0503],
        ['Gniezno', 52.5348, 17.5826],
        ['Ostrowiec Świętokrzyski', 50.9294, 21.3852],
        ['Siemianowice Śląskie', 50.3277, 19.0290],
        ['Głogów', 51.6636, 16.0847],
        ['Pabianice', 51.6645, 19.3547],
        ['Leszno', 51.8404, 16.5750],
        ['Żory', 50.0450, 18.7006],
        ['Zamość', 50.7231, 23.2519],
        ['Pruszków', 52.1706, 20.8123],
        ['Łomża', 53.1782, 22.0592],
        ['Ełk', 53.8283, 22.3648],
        ['Tarnowskie Góry', 50.4454, 18.8618],
        ['Tomaszów Mazowiecki', 51.5313, 20.0088],
        ['Chełm', 51.1329, 23.4711],
        ['Mielec', 50.2871, 21.4239],
        ['Kędzierzyn-Koźle', 50.3499, 18.2261],
        ['Przemyśl', 49.7838, 22.7677],
        ['Stalowa Wola', 50.5709, 22.0534],
        ['Tczew', 54.0921, 18.7777],
        ['Biała Podlaska', 52.0325, 23.1149],
        ['Bełchatów', 51.3687, 19.3564],
        ['Świdnica', 50.8449, 16.4884],
        ['Będzin', 50.3278, 19.1300],
        ['Zgierz', 51.8560, 19.4062],
        ['Piaseczno', 52.0815, 21.0240],
        ['Racibórz', 50.0915, 18.2190],
        ['Legionowo', 52.4048, 20.9291],
        ['Ostrołęka', 53.0843, 21.5755],
        ['Świętochłowice', 50.2963, 18.9176],
        ['Wejherowo', 54.6060, 18.2350],
        ['Zawiercie', 50.4879, 19.4167],
        ['Skierniewice', 51.9546, 20.1416],
        ['Starachowice', 51.0374, 21.0703],
        ['Wodzisław Śląski', 50.0035, 18.4720],
        ['Starogard Gdański', 53.9634, 18.5264],
        ['Puławy', 51.4166, 21.9686],
        ['Tarnobrzeg', 50.5731, 21.6793],
        ['Kołobrzeg', 54.1757, 15.5833],
        ['Krosno', 49.6886, 21.7706],
        ['Radomsko', 51.0679, 19.4413],
        ['Otwock', 52.1058, 21.2613],
        ['Skarżysko-Kamienna', 51.1127, 20.8877],
        ['Ciechanów', 52.8815, 20.6188],
        ['Kutno', 52.2307, 19.3644],
        ['Zduńska Wola', 51.5993, 18.9398],
        ['Sieradz', 51.5955, 18.7300],
        ['Świnoujście', 53.9100, 14.2472],
        ['Żyrardów', 52.0491, 20.4461],
        ['Bolesławiec', 51.2632, 15.5697],
        ['Nowa Sól', 51.8019, 15.7175],
        ['Knurów', 50.2211, 18.6650],
        ['Oświęcim', 50.0345, 19.2098],
        ['Sanok', 49.5559, 22.2056],
        ['Jarosław', 50.0163, 22.6779],
        ['Malbork', 54.0362, 19.0264],
        ['Sopot', 54.4416, 18.5601],
        ['Zakopane', 49.2992, 19.9496],
        ['Augustów', 53.8433, 22.9797],
        ['Giżycko', 54.0380, 21.7647],
        ['Mrągowo', 53.8640, 21.3055],
        ['Kwidzyn', 53.7359, 18.9308],
        ['Nysa', 50.4739, 17.3325],
        ['Brzeg', 50.8607, 17.4676],
        ['Świecie', 53.4098, 18.4472],
        ['Chojnice', 53.6958, 17.5570],
        ['Lębork', 54.5392, 17.7503],
        ['Września', 52.3251, 17.5652],
        ['Krotoszyn', 51.6970, 17.4372],
        ['Śrem', 52.0887, 17.0158],
        ['Turek', 52.0155, 18.5010],
        ['Łuków', 51.9294, 22.3791],
        ['Dębica', 50.0515, 21.4117],
        ['Nowy Targ', 49.4775, 20.0326],
        ['Cieszyn', 49.7480, 18.6328],
        ['Żywiec', 49.6853, 19.1923],
        ['Myszków', 50.5750, 19.3247],
        ['Olkusz', 50.2811, 19.5650],
        ['Wieliczka', 49.9871, 20.0645],
        ['Bochnia', 49.9690, 20.4304],
        ['Gorlice', 49.6547, 21.1596],
        ['Jasło', 49.7450, 21.4716],
        ['Łańcut', 50.0686, 22.2296],
        ['Biłgoraj', 50.5411, 22.7220],
        ['Kraśnik', 50.9238, 22.2206],
        ['Świdnik', 51.2191, 22.6963],
        ['Hrubieszów', 50.8093, 23.8921],
        ['Sochaczew', 52.2297, 20.2376],
        ['Mińsk Mazowiecki', 52.1793, 21.5722],
        ['Wołomin', 52.3403, 21.2422],
        ['Marki', 52.3202, 21.1035],
        ['Ząbki', 52.2926, 21.1160],
        ['Grodzisk Mazowiecki', 52.1090, 20.6337],
        ['Nowy Dwór Mazowiecki', 52.4319, 20.7153],
        ['Wyszków', 52.5928, 21.4584],
        ['Mława', 53.1122, 20.3843],
        ['Działdowo', 53.2358, 20.1783],
        ['Iława', 53.5964, 19.5686],
        ['Ostróda', 53.6957, 19.9645],
        ['Szczytno', 53.5626, 20.9895],
        ['Kętrzyn', 54.0766, 21.3752],
        ['Bartoszyce', 54.2528, 20.8081],
        ['Braniewo', 54.3796, 19.8188],
        ['Sokółka', 53.4072, 23.5023],
        ['Bielsk Podlaski', 52.7654, 23.1866],
        ['Hajnówka', 52.7432, 23.5810],
        ['Zambrów', 52.9855, 22.2432],
        ['Grajewo', 53.6472, 22.4550],
    ];

    /**
     * Zwraca [nazwa, slug, dystans_km] najbliższego miasta,
     * lub null jeśli żadne miasto nie leży w promieniu $maxKm.
     */
    public static function nearest(float $lat, float $lon, float $maxKm = 30.0): ?array
    {
        $best = null;
        $bestDist = $maxKm;
        foreach (self::LIST as [$name, $cLat, $cLon]) {
            // Szybki pre-filtr po współrzędnych (ok. 30 km ≈ 0.45°)
            if (abs($cLat - $lat) > 0.5 || abs($cLon - $lon) > 0.75) {
                continue;
            }
            $d = haversine($lat, $lon, $cLat, $cLon);
            if ($d < $bestDist) {
                $bestDist = $d;
                $best = [$name, slugify($name), $d];
            }
        }
        return $best;
    }

    public static function coords(string $slug): ?array
    {
        foreach (self::LIST as [$name, $lat, $lon]) {
            if (slugify($name) === $slug) {
                return ['name' => $name, 'lat' => $lat, 'lon' => $lon];
            }
        }
        return null;
    }
}
