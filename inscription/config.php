<?php

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=seguro_hotel;charset=utf8mb4",
        "root",
        "",
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