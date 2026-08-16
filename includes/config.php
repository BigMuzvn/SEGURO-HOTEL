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

    // ── Tokens de Design & Charte Graphique Dynamique ──
    define('THEME_COLOR_PRIMARY', getenv('THEME_COLOR_PRIMARY') ?: '#1a3a2a');
    define('THEME_COLOR_PRIMARY_LIGHT', getenv('THEME_COLOR_PRIMARY_LIGHT') ?: '#2d5c40');
    define('THEME_COLOR_ACCENT', getenv('THEME_COLOR_ACCENT') ?: '#b89035');
    define('THEME_COLOR_ACCENT_LIGHT', getenv('THEME_COLOR_ACCENT_LIGHT') ?: '#d4a948');
    define('THEME_COLOR_ACCENT_PALE', getenv('THEME_COLOR_ACCENT_PALE') ?: '#f5e9c4');
    define('THEME_COLOR_DARK', getenv('THEME_COLOR_DARK') ?: '#111111');
    define('THEME_COLOR_LIGHT', getenv('THEME_COLOR_LIGHT') ?: '#faf8f3');

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

    // ── Helper Functions Marque Blanche ──
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

    function hotel_theme_css(): string {
        return '<style id="hotel-theme-vars">
            :root {
                --vert: ' . THEME_COLOR_PRIMARY . ' !important;
                --vert-clair: ' . THEME_COLOR_PRIMARY_LIGHT . ' !important;
                --or: ' . THEME_COLOR_ACCENT . ' !important;
                --or-clair: ' . THEME_COLOR_ACCENT_LIGHT . ' !important;
                --or-pale: ' . THEME_COLOR_ACCENT_PALE . ' !important;
                --noir: ' . THEME_COLOR_DARK . ' !important;
                --blanc: ' . THEME_COLOR_LIGHT . ' !important;
                --bordure-form: rgba(184, 144, 53, 0.45);
            }
        </style>';
    }
}

