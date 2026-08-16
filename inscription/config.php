<?php
require_once __DIR__ . '/../includes/config.php';

try {
    $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
    $dbName = defined('DB_NAME') ? DB_NAME : 'seguro_hotel';
    $dbUser = defined('DB_USER') ? DB_USER : 'root';
    $dbPass = defined('DB_PASS') ? DB_PASS : '';

    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    error_log("inscription/config.php DB error: " . $e->getMessage());
    die("Erreur de connexion. Veuillez réessayer.");
}
?>