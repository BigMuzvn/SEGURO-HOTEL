<?php
/**
 * ════════════════════════════════════════════════════════
 * CONFIGURATION CENTRALE & SÉCURITÉ — Hôtel SEGURO
 * ════════════════════════════════════════════════════════
 */

if (!defined('HOTEL_CONFIG_LOADED')) {
    define('HOTEL_CONFIG_LOADED', true);

    // ── Chargement automatique du fichier .env s'il existe ──
    $envFile = dirname(__DIR__) . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) continue;
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }

    // ── Moteur Logiciel (SaaS / White-Label) ──
    define('SYSTEM_NAME', getenv('SYSTEM_NAME') ?: 'HospitOS');
    define('SYSTEM_VERSION', getenv('SYSTEM_VERSION') ?: '2.0.0');

    // ── Identité Établissement (Personnalisable par hôtel) ──
    define('HOTEL_NAME', getenv('HOTEL_NAME') ?: 'Hôtel Grand Prestige & Spa');
    define('HOTEL_NAME_SHORT', getenv('HOTEL_NAME_SHORT') ?: 'Grand Prestige');
    define('HOTEL_INITIALS', getenv('HOTEL_INITIALS') ?: 'GP');
    define('HOTEL_TAGLINE', getenv('HOTEL_TAGLINE') ?: 'L\'Excellence · Le Confort · L\'Hospitalité');
    define('HOTEL_LOCATION', getenv('HOTEL_LOCATION') ?: 'Avenue Océane, Front de Mer');
    define('HOTEL_CITY', getenv('HOTEL_CITY') ?: 'Lomé');
    define('HOTEL_COUNTRY', getenv('HOTEL_COUNTRY') ?: 'Togo');
    define('HOTEL_PHONE', getenv('HOTEL_PHONE') ?: '+228 90 00 00 00');
    define('HOTEL_WHATSAPP', getenv('HOTEL_WHATSAPP') ?: '22890000000');
    define('HOTEL_EMAIL', getenv('HOTEL_EMAIL') ?: 'reservations@grandprestige-hotel.com');
    define('HOTEL_CONTACT_EMAIL', getenv('HOTEL_CONTACT_EMAIL') ?: 'contact@grandprestige-hotel.com');
    define('HOTEL_CURRENCY', getenv('HOTEL_CURRENCY') ?: 'FCFA');
    define('HOTEL_REF_PREFIX', getenv('HOTEL_REF_PREFIX') ?: 'HTL');
    define('HOTEL_CLIENT_PREFIX', getenv('HOTEL_CLIENT_PREFIX') ?: 'CLI');

    // ── Tokens de Design & Charte Graphique Dynamique (4 Codes Couleurs) ──
    define('THEME_COLOR_PRIMARY', getenv('THEME_COLOR_PRIMARY') ?: '#143323');
    define('THEME_COLOR_PRIMARY_LIGHT', getenv('THEME_COLOR_PRIMARY_LIGHT') ?: '#24523a');
    define('THEME_COLOR_PRIMARY_DARK', getenv('THEME_COLOR_PRIMARY_DARK') ?: '#0b1d14');
    define('THEME_COLOR_ACCENT', getenv('THEME_COLOR_ACCENT') ?: '#c9a84c');
    define('THEME_COLOR_ACCENT_LIGHT', getenv('THEME_COLOR_ACCENT_LIGHT') ?: '#dcbe68');
    define('THEME_COLOR_ACCENT_DARK', getenv('THEME_COLOR_ACCENT_DARK') ?: '#9c7b2c');
    define('THEME_COLOR_ACCENT_PALE', getenv('THEME_COLOR_ACCENT_PALE') ?: '#f5ecd1');
    define('THEME_COLOR_DARK', getenv('THEME_COLOR_DARK') ?: '#07130c');
    define('THEME_COLOR_DARK_SURFACE', getenv('THEME_COLOR_DARK_SURFACE') ?: '#0f2418');
    define('THEME_COLOR_LIGHT', getenv('THEME_COLOR_LIGHT') ?: '#fbf9f4');
    define('THEME_COLOR_LIGHT_SURFACE', getenv('THEME_COLOR_LIGHT_SURFACE') ?: '#ffffff');

    // Base de données
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_NAME', getenv('DB_NAME') ?: 'seguro_hotel');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
    define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

    // Brevo API Mail
    define('BREVO_API_KEY', getenv('BREVO_API_KEY') ?: '');
    define('BREVO_SENDER_EMAIL', getenv('BREVO_SENDER_EMAIL') ?: HOTEL_EMAIL);
    define('BREVO_SENDER_NAME', getenv('BREVO_SENDER_NAME') ?: (HOTEL_NAME . ' — Conciergerie'));

    // Environnement
    define('APP_ENV', getenv('APP_ENV') ?: 'production');

    // Configuration sécurisée des sessions
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => $cookieParams['path'] ?? '/',
            'domain'   => $cookieParams['domain'] ?? '',
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }

    // En-têtes HTTP de Sécurité
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /**
     * Convertit un code Hexadécimal en triplet RGB (ex: "201, 168, 76")
     */
    function color_hex_to_rgb(string $hex): string {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6) return '0, 0, 0';
        return hexdec(substr($hex, 0, 2)) . ', ' . hexdec(substr($hex, 2, 2)) . ', ' . hexdec(substr($hex, 4, 2));
    }

    /**
     * Génère un jeton CSRF et le stocke en session
     */
    function csrf_token(): string {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Génère un champ input hidden pour formulaire
     */
    function csrf_field(): string {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
    }

    /**
     * Valide un jeton CSRF reçu par requête POST
     */
    function verify_csrf_token(?string $token): bool {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    // ── Helper Functions Marque Blanche & Thème ──
    function hotel_name(): string { return HOTEL_NAME; }
    function hotel_short_name(): string { return HOTEL_NAME_SHORT; }
    function hotel_initials(): string {
        if (defined('HOTEL_INITIALS') && !empty(HOTEL_INITIALS)) {
            return HOTEL_INITIALS;
        }
        $words = explode(' ', trim(hotel_short_name()));
        $initials = '';
        foreach ($words as $w) {
            if (!empty($w)) $initials .= mb_substr($w, 0, 1);
        }
        return strtoupper(substr($initials, 0, 3)) ?: 'HTL';
    }
    function hotel_tagline(): string { return HOTEL_TAGLINE; }
    function hotel_location(): string { return HOTEL_LOCATION; }
    function hotel_city(): string { return HOTEL_CITY; }
    function hotel_country(): string { return HOTEL_COUNTRY; }
    function hotel_phone(): string { return HOTEL_PHONE; }
    function hotel_whatsapp(): string { return HOTEL_WHATSAPP; }
    function hotel_email(): string { return HOTEL_EMAIL; }
    function hotel_currency(): string { return HOTEL_CURRENCY; }
    function hotel_ref_prefix(): string { return HOTEL_REF_PREFIX; }
    function hotel_client_prefix(): string { return HOTEL_CLIENT_PREFIX; }
    function hotel_theme_primary(): string { return THEME_COLOR_PRIMARY; }
    function hotel_theme_accent(): string { return THEME_COLOR_ACCENT; }
    function hotel_theme_dark(): string { return THEME_COLOR_DARK; }
    function hotel_theme_light(): string { return THEME_COLOR_LIGHT; }

    function hotel_theme_css(): string {
        $primaryRgb = color_hex_to_rgb(THEME_COLOR_PRIMARY);
        $accentRgb  = color_hex_to_rgb(THEME_COLOR_ACCENT);
        $darkRgb    = color_hex_to_rgb(THEME_COLOR_DARK);
        $lightRgb   = color_hex_to_rgb(THEME_COLOR_LIGHT);

        return '<style id="hotel-theme-vars">
            :root {
                --color-primary: ' . THEME_COLOR_PRIMARY . ';
                --color-primary-rgb: ' . $primaryRgb . ';
                --color-primary-light: ' . THEME_COLOR_PRIMARY_LIGHT . ';
                --color-primary-dark: ' . THEME_COLOR_PRIMARY_DARK . ';
                
                --color-accent: ' . THEME_COLOR_ACCENT . ';
                --color-accent-rgb: ' . $accentRgb . ';
                --color-accent-light: ' . THEME_COLOR_ACCENT_LIGHT . ';
                --color-accent-dark: ' . THEME_COLOR_ACCENT_DARK . ';
                --color-accent-pale: ' . THEME_COLOR_ACCENT_PALE . ';

                --color-dark: ' . THEME_COLOR_DARK . ';
                --color-dark-rgb: ' . $darkRgb . ';
                --color-dark-surface: ' . THEME_COLOR_DARK_SURFACE . ';

                --color-light: ' . THEME_COLOR_LIGHT . ';
                --color-light-rgb: ' . $lightRgb . ';
                --color-light-surface: ' . THEME_COLOR_LIGHT_SURFACE . ';

                /* Aliases Rétrocompatibles */
                --vert: var(--color-primary) !important;
                --vert-clair: var(--color-primary-light) !important;
                --vert-sombre: var(--color-primary-dark) !important;
                --or: var(--color-accent) !important;
                --or-clair: var(--color-accent-light) !important;
                --or-pale: var(--color-accent-pale) !important;
                --or-texte: var(--color-accent-dark) !important;
                --noir: var(--color-dark) !important;
                --noir-surface: var(--color-dark-surface) !important;
                --blanc: var(--color-light) !important;
                --blanc-surface: var(--color-light-surface) !important;
                --bordure-form: rgba(' . $accentRgb . ', 0.45) !important;
            }

            /* ── Application Globale sur tout le site ── */
            body {
                background-color: var(--color-light) !important;
                color: var(--color-dark) !important;
            }

            /* Titres, textes & accents */
            .text-or, .gold-text, .accent-text, .section-subtitle, .badge-or {
                color: var(--color-accent) !important;
            }
            .text-vert, .primary-text {
                color: var(--color-primary) !important;
            }

            /* SVG Hero Frame & Ornements */
            .hero-corner svg path, .hero-corner svg rect, .hero-corner svg line, .hero-corner svg circle {
                stroke: var(--color-accent) !important;
            }

            /* Blasons Monogramme & Couronnes */
            .logo-crest, .brand-crest, .crest-box {
                border-color: var(--color-accent) !important;
                background: var(--color-primary) !important;
                color: var(--color-accent) !important;
            }
            .crest-crown, .crest-letters {
                color: var(--color-accent) !important;
            }

            /* Boutons d\'Action */
            .btn-or, .btn-gold, a.btn-or, button.btn-or, .btn-accent {
                background: var(--color-accent) !important;
                background-color: var(--color-accent) !important;
                border-color: var(--color-accent) !important;
                color: #111111 !important;
            }
            .btn-or:hover, a.btn-or:hover, button.btn-or:hover, .btn-accent:hover {
                background: var(--color-accent-light) !important;
                border-color: var(--color-accent-light) !important;
                color: #000000 !important;
                box-shadow: 0 6px 20px rgba(' . $accentRgb . ', 0.35) !important;
            }
            .btn-vert, .btn-primary, a.btn-vert, button.btn-vert, .btn-primary-hotel {
                background: var(--color-primary) !important;
                background-color: var(--color-primary) !important;
                border-color: var(--color-primary) !important;
                color: #ffffff !important;
            }
            .btn-vert:hover, a.btn-vert:hover, button.btn-vert:hover {
                background: var(--color-primary-light) !important;
                border-color: var(--color-primary-light) !important;
                color: #ffffff !important;
                box-shadow: 0 6px 20px rgba(' . $primaryRgb . ', 0.35) !important;
            }
            .btn-outline-or {
                border-color: var(--color-accent) !important;
                color: var(--color-accent) !important;
                background: transparent !important;
            }
            .btn-outline-or:hover {
                background: var(--color-accent) !important;
                color: #111111 !important;
            }

            /* Sections & Arrière-plans */
            section, .section-light {
                background-color: var(--color-light) !important;
            }
            section.section-dark, .section-primary-dark, footer#footer, .footer-section {
                background-color: var(--color-dark) !important;
            }
            .footer-title, .footer-heading {
                color: var(--color-accent) !important;
            }

            /* Cartes & Surfaces */
            .card, .room-card, .service-card, .menu-card, .testimonial-card, .feature-box, .stat-card {
                background-color: var(--color-light-surface) !important;
                border-color: rgba(' . $accentRgb . ', 0.2) !important;
            }
            .card:hover, .room-card:hover {
                border-color: var(--color-accent) !important;
            }

            /* Étoiles & Badges */
            .fa-star, .stars, .star-rating, .rating-badge {
                color: var(--color-accent) !important;
            }
            .badge-gold, .tag-accent {
                background: rgba(' . $accentRgb . ', 0.15) !important;
                color: var(--color-accent-dark) !important;
                border: 1px solid rgba(' . $accentRgb . ', 0.35) !important;
            }

            /* Formulaires & Champs */
            .form-control:focus, input:focus, select:focus, textarea:focus {
                border-color: var(--color-accent) !important;
                box-shadow: 0 0 0 3px rgba(' . $accentRgb . ', 0.18) !important;
            }
        </style>';
    }
}


