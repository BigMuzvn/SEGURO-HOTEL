<?php
/**
 * ════════════════════════════════════════════════════════
 * MASTER HOTEL MANAGER — Moteur de Gestion Multi-Hôtels
 * ════════════════════════════════════════════════════════
 */

class HotelManager {
    private static string $dataFile = __DIR__ . '/../data/hotels.json';
    private static string $rootEnvFile = __DIR__ . '/../../.env';

    /**
     * Palettes de thèmes d'inspiration haut de gamme (1 clic)
     */
    public static array $THEME_PRESETS = [
        'emerald_gold' => [
            'id'             => 'emerald_gold',
            'name'           => 'Émeraude Impériale & Or Pur',
            'desc'           => 'Luxe intemporel, nature et prestige (Idéal Resort, Hôtel Spa & Palace)',
            'primary'        => '#143323',
            'primary_light'  => '#24523a',
            'primary_dark'   => '#0b1d14',
            'accent'         => '#c9a84c',
            'accent_light'   => '#dcbe68',
            'accent_dark'    => '#9c7b2c',
            'accent_pale'    => '#f5ecd1',
            'dark'           => '#07130c',
            'dark_surface'   => '#0f2418',
            'light'          => '#fbf9f4',
            'light_surface'  => '#ffffff',
        ],
        'sapphire_platinum' => [
            'id'             => 'sapphire_platinum',
            'name'           => 'Bleu Nuit Saphir & Argent Platine',
            'desc'           => 'Élégance moderne sans or, reflets miroir (Idéal Hôtel Business, Congrès & Design)',
            'primary'        => '#0b1f3a',
            'primary_light'  => '#18365f',
            'primary_dark'   => '#061120',
            'accent'         => '#c4cbd4',
            'accent_light'   => '#e2e7ed',
            'accent_dark'    => '#8a95a5',
            'accent_pale'    => '#e8eef5',
            'dark'           => '#040b15',
            'dark_surface'   => '#0c1a2d',
            'light'          => '#f0f4f8',
            'light_surface'  => '#ffffff',
        ],
        'bordeaux_champagne' => [
            'id'             => 'bordeaux_champagne',
            'name'           => 'Bordeaux Palace & Or Champagne',
            'desc'           => 'Atmosphère feutrée, velours et raffinement (Idéal Château, Relais & Boutique-Hôtel)',
            'primary'        => '#3d0c18',
            'primary_light'  => '#5e1528',
            'primary_dark'   => '#21050c',
            'accent'         => '#dfba73',
            'accent_light'   => '#eed296',
            'accent_dark'    => '#b08a3d',
            'accent_pale'    => '#faf2e1',
            'dark'           => '#170308',
            'dark_surface'   => '#270811',
            'light'          => '#fdf8f6',
            'light_surface'  => '#ffffff',
        ],
        'onyx_copper' => [
            'id'             => 'onyx_copper',
            'name'           => 'Onyx Absolu & Cuivre Flamboyant',
            'desc'           => 'Contraste architectural ultra-moderne (Idéal Suites Urbaines, Penthouse & Loft)',
            'primary'        => '#171717',
            'primary_light'  => '#2b2b2b',
            'primary_dark'   => '#0d0d0d',
            'accent'         => '#c87d55',
            'accent_light'   => '#df9a75',
            'accent_dark'    => '#9c542d',
            'accent_pale'    => '#f8ece4',
            'dark'           => '#080808',
            'dark_surface'   => '#141414',
            'light'          => '#f5eee9',
            'light_surface'  => '#ffffff',
        ],
        'forest_amber' => [
            'id'             => 'forest_amber',
            'name'           => 'Forêt Boréale & Ambre Ardent',
            'desc'           => 'Chaleur boisé et sérénité organique (Idéal Lodge, Écolodge & Retraite Bien-Être)',
            'primary'        => '#182c20',
            'primary_light'  => '#274433',
            'primary_dark'   => '#0c1810',
            'accent'         => '#e09f3e',
            'accent_light'   => '#ebb96b',
            'accent_dark'    => '#a86c18',
            'accent_pale'    => '#fcf2df',
            'dark'           => '#09120c',
            'dark_surface'   => '#122017',
            'light'          => '#f8f6f0',
            'light_surface'  => '#ffffff',
        ],
        'ocean_azure' => [
            'id'             => 'ocean_azure',
            'name'           => 'Bleu Pacifique & Azur Riviera',
            'desc'           => 'Fraîcheur balnéaire et horizon marin (Idéal Hôtel Bord de Mer & Villa Océane)',
            'primary'        => '#0c2340',
            'primary_light'  => '#153a66',
            'primary_dark'   => '#051221',
            'accent'         => '#0284c7',
            'accent_light'   => '#38bdf8',
            'accent_dark'    => '#0369a1',
            'accent_pale'    => '#e0f2fe',
            'dark'           => '#030c17',
            'dark_surface'   => '#091a2e',
            'light'          => '#f0f8ff',
            'light_surface'  => '#ffffff',
        ],
    ];

    /**
     * Nettoie et valide un code couleur Hexadécimal (#RRGGBB)
     */
    public static function sanitizeHex(string $hex, string $default = '#1a3a2a'): string {
        $hex = trim($hex);
        if (!str_starts_with($hex, '#')) {
            $hex = '#' . $hex;
        }
        if (preg_match('/^#[a-f0-9]{6}$/i', $hex)) {
            return strtolower($hex);
        }
        if (preg_match('/^#[a-f0-9]{3}$/i', $hex)) {
            return strtolower('#' . $hex[1].$hex[1].$hex[2].$hex[2].$hex[3].$hex[3]);
        }
        return strtolower($default);
    }

    /**
     * Ajuste la luminosité d'une couleur hexadécimale (-100 à +100)
     */
    public static function adjustBrightness(string $hex, int $percent): string {
        $hex = ltrim(self::sanitizeHex($hex), '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, (int)round($r + (255 * ($percent / 100)))));
        $g = max(0, min(255, (int)round($g + (255 * ($percent / 100)))));
        $b = max(0, min(255, (int)round($b + (255 * ($percent / 100)))));

        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }

    /**
     * Crée une teinte subtile mélangée entre couleur premier plan et arrière-plan
     */
    public static function tintColor(string $fgHex, string $bgHex, float $weight = 0.85): string {
        $fg = ltrim(self::sanitizeHex($fgHex), '#');
        $bg = ltrim(self::sanitizeHex($bgHex), '#');

        $r1 = hexdec(substr($fg, 0, 2)); $g1 = hexdec(substr($fg, 2, 2)); $b1 = hexdec(substr($fg, 4, 2));
        $r2 = hexdec(substr($bg, 0, 2)); $g2 = hexdec(substr($bg, 2, 2)); $b2 = hexdec(substr($bg, 4, 2));

        $r = (int)round($r1 * (1 - $weight) + $r2 * $weight);
        $g = (int)round($g1 * (1 - $weight) + $g2 * $weight);
        $b = (int)round($b1 * (1 - $weight) + $b2 * $weight);

        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }

    /**
     * Calcule l'ensemble complet des nuances du thème à partir des 4 codes fondamentaux
     */
    public static function computeThemeColors(string $primary, string $accent, string $dark, string $light): array {
        $primary = self::sanitizeHex($primary, '#143323');
        $accent  = self::sanitizeHex($accent,  '#c9a84c');
        $dark    = self::sanitizeHex($dark,    '#07130c');
        $light   = self::sanitizeHex($light,   '#fbf9f4');

        return [
            'primary'        => $primary,
            'primary_light'  => self::adjustBrightness($primary, 22),
            'primary_dark'   => self::adjustBrightness($primary, -22),
            'accent'         => $accent,
            'accent_light'   => self::adjustBrightness($accent, 18),
            'accent_dark'    => self::adjustBrightness($accent, -18),
            'accent_pale'    => self::tintColor($accent, $light, 0.85),
            'dark'           => $dark,
            'dark_surface'   => self::adjustBrightness($dark, 12),
            'light'          => $light,
            'light_surface'  => '#ffffff',
        ];
    }

    /**
     * Initialise le répertoire et charge les hôtels
     */
    private static function init(): void {
        $dir = dirname(self::$dataFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!file_exists(self::$dataFile)) {
            $default = [
                'hotels' => [
                    [
                        'id'             => 'hotel_default',
                        'code'           => 'GP-001',
                        'name'           => 'Hôtel Grand Prestige & Spa',
                        'short_name'     => 'Grand Prestige',
                        'initials'       => 'GP',
                        'tagline'        => 'L\'Excellence · Le Confort · L\'Hospitalité',
                        'subtitle'       => 'Rien n\'a été sacrifié sur l\'autel du confort et de l\'excellence.',
                        'description'    => 'Niché au cœur d\'un environnement privilégié à Lomé, l\'Hôtel Grand Prestige & Spa vous invite à découvrir l\'harmonie parfaite entre nature préservée et confort raffiné.',
                        'city'           => 'Lomé',
                        'country'        => 'Togo',
                        'location'       => 'Avenue Océane, Front de Mer',
                        'address'        => 'Boulevard de la Marina, BP 1245',
                        'maps_url'       => 'https://maps.google.com',
                        'phone'          => '+228 90 00 00 00',
                        'whatsapp'       => '22890000000',
                        'email'          => 'reservations@grandprestige-hotel.com',
                        'contact_email'  => 'contact@grandprestige-hotel.com',
                        'currency'       => 'FCFA',
                        'ref_prefix'     => 'HTL',
                        'client_prefix'  => 'CLI',
                        'checkin_time'   => '14:00',
                        'checkout_time'  => '12:00',
                        'tva_rate'       => '18',
                        'tourist_tax'    => '1000',
                        'theme_preset'   => 'emerald_gold',
                        'theme_colors'   => self::$THEME_PRESETS['emerald_gold'],
                        'hero_media'     => 'video',
                        'is_active'      => true,
                        'created_at'     => '2026-08-01 10:00:00',
                        'updated_at'     => date('Y-m-d H:i:s'),
                        'notes'          => 'Établissement vitrine de référence du système HospitOS.',
                    ]
                ],
                'active_hotel_id' => 'hotel_default',
                'system_version'  => '2.0.0'
            ];
            file_put_contents(self::$dataFile, json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * Récupère tous les profils d'hôtels
     */
    public static function getAllHotels(): array {
        self::init();
        $content = file_get_contents(self::$dataFile);
        $json = json_decode($content, true);
        return $json['hotels'] ?? [];
    }

    /**
     * Récupère un hôtel spécifique par son ID
     */
    public static function getHotel(string $id): ?array {
        self::init();
        $content = file_get_contents(self::$dataFile);
        $json = json_decode($content, true);
        foreach ($json['hotels'] as $h) {
            if ($h['id'] === $id) return $h;
        }
        return null;
    }

    /**
     * Récupère l'hôtel actuellement actif sur la plateforme
     */
    public static function getActiveHotel(): array {
        self::init();
        $content = file_get_contents(self::$dataFile);
        $json = json_decode($content, true);
        $activeId = $json['active_hotel_id'] ?? 'hotel_default';
        foreach ($json['hotels'] as $h) {
            if ($h['id'] === $activeId) return $h;
        }
        return $json['hotels'][0] ?? [];
    }

    /**
     * Sauvegarde ou met à jour un profil d'hôtel
     */
    public static function saveHotel(array $data): string {
        self::init();
        $content = file_get_contents(self::$dataFile);
        $json = json_decode($content, true);

        $isNew = empty($data['id']);
        $id = $isNew ? ('hotel_' . uniqid()) : $data['id'];

        // 1. Détermination des 4 couleurs fondamentales
        $presetKey = $data['theme_preset'] ?? 'custom';
        $preset = self::$THEME_PRESETS[$presetKey] ?? self::$THEME_PRESETS['emerald_gold'];

        $primary = !empty($data['theme_primary']) ? $data['theme_primary'] : ($preset['primary'] ?? '#143323');
        $accent  = !empty($data['theme_accent'])  ? $data['theme_accent']  : ($preset['accent']  ?? '#c9a84c');
        $dark    = !empty($data['theme_dark'])    ? $data['theme_dark']    : ($preset['dark']    ?? '#07130c');
        $light   = !empty($data['theme_light'])   ? $data['theme_light']   : ($preset['light']   ?? '#fbf9f4');

        $themeColors = self::computeThemeColors($primary, $accent, $dark, $light);

        $hotelEntry = [
            'id'             => $id,
            'code'           => $data['code'] ?? ('HTL-' . strtoupper(substr(uniqid(), -4))),
            'name'           => trim($data['name'] ?? 'Nouvel Hôtel'),
            'short_name'     => trim($data['short_name'] ?? ($data['name'] ?? 'Hôtel')),
            'initials'       => strtoupper(trim($data['initials'] ?? 'HTL')),
            'tagline'        => trim($data['tagline'] ?? 'L\'Excellence et le Confort'),
            'subtitle'       => trim($data['subtitle'] ?? ''),
            'description'    => trim($data['description'] ?? ''),
            'city'           => trim($data['city'] ?? 'Lomé'),
            'country'        => trim($data['country'] ?? 'Togo'),
            'location'       => trim($data['location'] ?? 'Centre-Ville'),
            'address'        => trim($data['address'] ?? ''),
            'maps_url'       => trim($data['maps_url'] ?? ''),
            'phone'          => trim($data['phone'] ?? ''),
            'whatsapp'       => preg_replace('/[^0-9]/', '', $data['whatsapp'] ?? ''),
            'email'          => trim($data['email'] ?? ''),
            'contact_email'  => trim($data['contact_email'] ?? ($data['email'] ?? '')),
            'currency'       => trim($data['currency'] ?? 'FCFA'),
            'ref_prefix'     => strtoupper(trim($data['ref_prefix'] ?? 'HTL')),
            'client_prefix'  => strtoupper(trim($data['client_prefix'] ?? 'CLI')),
            'checkin_time'   => trim($data['checkin_time'] ?? '14:00'),
            'checkout_time'  => trim($data['checkout_time'] ?? '12:00'),
            'tva_rate'       => (string)($data['tva_rate'] ?? '18'),
            'tourist_tax'    => (string)($data['tourist_tax'] ?? '1000'),
            'theme_preset'   => $presetKey,
            'theme_colors'   => $themeColors,
            'hero_media'     => $data['hero_media'] ?? 'video',
            'is_active'      => !empty($data['set_active']),
            'created_at'     => $data['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
            'notes'          => trim($data['notes'] ?? ''),
        ];

        $found = false;
        foreach ($json['hotels'] as &$h) {
            if ($h['id'] === $id) {
                $h = array_merge($h, $hotelEntry);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $json['hotels'][] = $hotelEntry;
        }

        // Si cet hôtel doit devenir l'hôtel actif
        if (!empty($data['set_active']) || count($json['hotels']) === 1) {
            $json['active_hotel_id'] = $id;
            foreach ($json['hotels'] as &$h) {
                $h['is_active'] = ($h['id'] === $id);
            }
            self::applyToEnv($hotelEntry);
        }

        file_put_contents(self::$dataFile, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $id;
    }

    /**
     * Active un hôtel sur la plateforme et génère le fichier .env
     */
    public static function setActive(string $id): bool {
        self::init();
        $content = file_get_contents(self::$dataFile);
        $json = json_decode($content, true);

        $selected = null;
        foreach ($json['hotels'] as &$h) {
            if ($h['id'] === $id) {
                $h['is_active'] = true;
                $selected = $h;
            } else {
                $h['is_active'] = false;
            }
        }

        if ($selected) {
            $json['active_hotel_id'] = $id;
            file_put_contents(self::$dataFile, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            self::applyToEnv($selected);
            return true;
        }
        return false;
    }

    /**
     * Supprime un hôtel (sauf si c'est le seul ou l'actif)
     */
    public static function deleteHotel(string $id): bool {
        self::init();
        $content = file_get_contents(self::$dataFile);
        $json = json_decode($content, true);

        if (count($json['hotels']) <= 1) return false;
        if ($json['active_hotel_id'] === $id) return false;

        $newHotels = [];
        foreach ($json['hotels'] as $h) {
            if ($h['id'] !== $id) {
                $newHotels[] = $h;
            }
        }
        $json['hotels'] = $newHotels;
        file_put_contents(self::$dataFile, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return true;
    }

    /**
     * Génère et écrit le fichier .env à la racine du projet
     */
    public static function applyToEnv(array $hotel): void {
        $colors = $hotel['theme_colors'] ?? self::$THEME_PRESETS['emerald_gold'];

        $primary = self::sanitizeHex($colors['primary'] ?? '#143323');
        $accent  = self::sanitizeHex($colors['accent']  ?? '#c9a84c');
        $dark    = self::sanitizeHex($colors['dark']    ?? '#07130c');
        $light   = self::sanitizeHex($colors['light']   ?? '#fbf9f4');

        $computed = self::computeThemeColors($primary, $accent, $dark, $light);

        $envContent = "# ═══════════════════════════════════════════════════════════\n";
        $envContent .= "# HOSPITOS — CONFIGURATION ACTIVE DE L'ÉTABLISSEMENT\n";
        $envContent .= "# Généré automatiquement par Master Hotel Setup Hub\n";
        $envContent .= "# Date: " . date('Y-m-d H:i:s') . "\n";
        $envContent .= "# ═══════════════════════════════════════════════════════════\n\n";

        $envContent .= "SYSTEM_NAME=\"HospitOS\"\n";
        $envContent .= "SYSTEM_VERSION=\"2.0.0\"\n\n";

        $envContent .= "# ── IDENTITÉ ÉTABLISSEMENT ──\n";
        $envContent .= "HOTEL_NAME=\"" . addslashes($hotel['name']) . "\"\n";
        $envContent .= "HOTEL_NAME_SHORT=\"" . addslashes($hotel['short_name']) . "\"\n";
        $envContent .= "HOTEL_INITIALS=\"" . addslashes($hotel['initials']) . "\"\n";
        $envContent .= "HOTEL_TAGLINE=\"" . addslashes($hotel['tagline']) . "\"\n";
        $envContent .= "HOTEL_LOCATION=\"" . addslashes($hotel['location']) . "\"\n";
        $envContent .= "HOTEL_CITY=\"" . addslashes($hotel['city']) . "\"\n";
        $envContent .= "HOTEL_COUNTRY=\"" . addslashes($hotel['country']) . "\"\n";
        $envContent .= "HOTEL_PHONE=\"" . addslashes($hotel['phone']) . "\"\n";
        $envContent .= "HOTEL_WHATSAPP=\"" . addslashes($hotel['whatsapp']) . "\"\n";
        $envContent .= "HOTEL_EMAIL=\"" . addslashes($hotel['email']) . "\"\n";
        $envContent .= "HOTEL_CONTACT_EMAIL=\"" . addslashes($hotel['contact_email']) . "\"\n";
        $envContent .= "HOTEL_CURRENCY=\"" . addslashes($hotel['currency']) . "\"\n";
        $envContent .= "HOTEL_REF_PREFIX=\"" . addslashes($hotel['ref_prefix']) . "\"\n";
        $envContent .= "HOTEL_CLIENT_PREFIX=\"" . addslashes($hotel['client_prefix']) . "\"\n\n";

        $envContent .= "# ── CHARTE GRAPHIQUE & 4 CODES COULEURS ──\n";
        $envContent .= "THEME_COLOR_PRIMARY=\"" . $computed['primary'] . "\"\n";
        $envContent .= "THEME_COLOR_PRIMARY_LIGHT=\"" . $computed['primary_light'] . "\"\n";
        $envContent .= "THEME_COLOR_PRIMARY_DARK=\"" . $computed['primary_dark'] . "\"\n";
        $envContent .= "THEME_COLOR_ACCENT=\"" . $computed['accent'] . "\"\n";
        $envContent .= "THEME_COLOR_ACCENT_LIGHT=\"" . $computed['accent_light'] . "\"\n";
        $envContent .= "THEME_COLOR_ACCENT_DARK=\"" . $computed['accent_dark'] . "\"\n";
        $envContent .= "THEME_COLOR_ACCENT_PALE=\"" . $computed['accent_pale'] . "\"\n";
        $envContent .= "THEME_COLOR_DARK=\"" . $computed['dark'] . "\"\n";
        $envContent .= "THEME_COLOR_DARK_SURFACE=\"" . $computed['dark_surface'] . "\"\n";
        $envContent .= "THEME_COLOR_LIGHT=\"" . $computed['light'] . "\"\n";
        $envContent .= "THEME_COLOR_LIGHT_SURFACE=\"" . $computed['light_surface'] . "\"\n\n";

        $envContent .= "# ── PARAMÈTRES MÉTIER ──\n";
        $envContent .= "CHECKIN_TIME=\"" . addslashes($hotel['checkin_time'] ?? '14:00') . "\"\n";
        $envContent .= "CHECKOUT_TIME=\"" . addslashes($hotel['checkout_time'] ?? '12:00') . "\"\n";
        $envContent .= "TVA_RATE=\"" . addslashes($hotel['tva_rate'] ?? '18') . "\"\n";
        $envContent .= "TOURIST_TAX=\"" . addslashes($hotel['tourist_tax'] ?? '1000') . "\"\n\n";

        $envContent .= "# ── BASE DE DONNÉES ──\n";
        $envContent .= "DB_HOST=\"localhost\"\n";
        $envContent .= "DB_NAME=\"seguro_hotel\"\n";
        $envContent .= "DB_USER=\"root\"\n";
        $envContent .= "DB_PASS=\"\"\n";
        $envContent .= "DB_CHARSET=\"utf8mb4\"\n\n";

        $envContent .= "# ── ENVIRONNEMENT ──\n";
        $envContent .= "APP_ENV=\"production\"\n";

        file_put_contents(self::$rootEnvFile, $envContent);
    }
}
