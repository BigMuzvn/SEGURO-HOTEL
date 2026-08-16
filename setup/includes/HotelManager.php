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
     * Palettes de thèmes prédéfinies haut de gamme
     */
    public static array $THEME_PRESETS = [
        'emerald_gold' => [
            'id'             => 'emerald_gold',
            'name'           => 'Émeraude Royal & Or',
            'desc'           => 'Luxe intemporel, nature et prestige (Idéal Resort, Hôtel Spa & Palace)',
            'primary'        => '#1a3a2a',
            'primary_light'  => '#2d5c40',
            'accent'         => '#b89035',
            'accent_light'   => '#d4a948',
            'accent_pale'    => '#f5e9c4',
            'dark'           => '#111111',
            'light'          => '#faf8f3',
        ],
        'sapphire_gold' => [
            'id'             => 'sapphire_gold',
            'name'           => 'Bleu Nuit Saphir & Or',
            'desc'           => 'Élégance corporate et business (Idéal Hôtel d\'Affaires & Événements)',
            'primary'        => '#0d1b2a',
            'primary_light'  => '#1b263b',
            'accent'         => '#d4af37',
            'accent_light'   => '#e6c86e',
            'accent_pale'    => '#fbf5df',
            'dark'           => '#070d14',
            'light'          => '#f6f8fb',
        ],
        'bordeaux_bronze' => [
            'id'             => 'bordeaux_bronze',
            'name'           => 'Bordeaux Palace & Bronze',
            'desc'           => 'Ambiance feutrée et majestueuse (Idéal Boutique-Hôtel & Château)',
            'primary'        => '#2b0d14',
            'primary_light'  => '#4a1523',
            'accent'         => '#c59b4c',
            'accent_light'   => '#dfb56c',
            'accent_pale'    => '#fbf1e2',
            'dark'           => '#150408',
            'light'          => '#fcf8f7',
        ],
        'onyx_luxury' => [
            'id'             => 'onyx_luxury',
            'name'           => 'Onyx Black & Champagne',
            'desc'           => 'Minimalisme moderne et ultra-luxe (Idéal Suites Urbaines & Design Hôtel)',
            'primary'        => '#141414',
            'primary_light'  => '#262626',
            'accent'         => '#dfba73',
            'accent_light'   => '#eed095',
            'accent_pale'    => '#faf4e7',
            'dark'           => '#0a0a0a',
            'light'          => '#f7f7f7',
        ],
        'terracotta_sunset' => [
            'id'             => 'terracotta_sunset',
            'name'           => 'Terracotta Sunset & Cuivre',
            'desc'           => 'Chaleur balnéaire et authenticité (Idéal Lodge, Villa & Écolodge)',
            'primary'        => '#3a1d17',
            'primary_light'  => '#5c2d24',
            'accent'         => '#e09f3e',
            'accent_light'   => '#f3b865',
            'accent_pale'    => '#fdf3e2',
            'dark'           => '#1f0c08',
            'light'          => '#fbf7f4',
        ],
    ];

    /**
     * Initialise le répertoire et charge les hôtels
     */
    private static function init(): void {
        $dir = dirname(self::$dataFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!file_exists(self::$dataFile)) {
            // Hôtel par défaut (Hôtel #1 - Grand Prestige)
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
                        'hero_media'     => 'video', // video or image
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
     * Récupère un hôtel spécifique par ID
     */
    public static function getHotel(string $id): ?array {
        $hotels = self::getAllHotels();
        foreach ($hotels as $h) {
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

        // Détermination du thème
        $presetKey = $data['theme_preset'] ?? 'emerald_gold';
        $themeColors = self::$THEME_PRESETS[$presetKey] ?? self::$THEME_PRESETS['emerald_gold'];

        // Si couleurs personnalisées
        if (!empty($data['primary_color'])) $themeColors['primary'] = $data['primary_color'];
        if (!empty($data['accent_color']))  $themeColors['accent']  = $data['accent_color'];

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

        $envContent .= "# ── CHARTE GRAPHIQUE & THÈME VISUEL ──\n";
        $envContent .= "THEME_COLOR_PRIMARY=\"" . $colors['primary'] . "\"\n";
        $envContent .= "THEME_COLOR_PRIMARY_LIGHT=\"" . $colors['primary_light'] . "\"\n";
        $envContent .= "THEME_COLOR_ACCENT=\"" . $colors['accent'] . "\"\n";
        $envContent .= "THEME_COLOR_ACCENT_LIGHT=\"" . $colors['accent_light'] . "\"\n";
        $envContent .= "THEME_COLOR_ACCENT_PALE=\"" . $colors['accent_pale'] . "\"\n";
        $envContent .= "THEME_COLOR_DARK=\"" . $colors['dark'] . "\"\n";
        $envContent .= "THEME_COLOR_LIGHT=\"" . $colors['light'] . "\"\n\n";

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
