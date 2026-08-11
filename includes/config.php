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

    // Identité Établissement
    define('HOTEL_NAME', getenv('HOTEL_NAME') ?: 'Hôtel SEGURO');
    define('HOTEL_TAGLINE', getenv('HOTEL_TAGLINE') ?: 'La Sérénité · La Qualité · La Confiance');
    define('HOTEL_LOCATION', getenv('HOTEL_LOCATION') ?: 'Agbodrafo, entrée de Aného, Togo');
    define('HOTEL_PHONE', getenv('HOTEL_PHONE') ?: '+228 90 00 00 00');
    define('HOTEL_WHATSAPP', getenv('HOTEL_WHATSAPP') ?: '22890000000');
    define('HOTEL_EMAIL', getenv('HOTEL_EMAIL') ?: 'reservations@hotelseguro.com');
    define('HOTEL_CONTACT_EMAIL', getenv('HOTEL_CONTACT_EMAIL') ?: 'contact@hotelseguro.com');

    // Base de données
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_NAME', getenv('DB_NAME') ?: 'seguro_hotel');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
    define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

    // Brevo API Mail
    define('BREVO_API_KEY', getenv('BREVO_API_KEY') ?: '');
    define('BREVO_SENDER_EMAIL', getenv('BREVO_SENDER_EMAIL') ?: 'reservations@hotelseguro.com');
    define('BREVO_SENDER_NAME', getenv('BREVO_SENDER_NAME') ?: 'Hôtel SEGURO — Conciergerie');

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
}

