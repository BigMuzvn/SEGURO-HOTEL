<?php

if (!function_exists('getPDO')) {
    function getPDO(): PDO {
        static $pdo = null;
        if ($pdo === null) {
            try {
                $pdo = new PDO(
                    "mysql:host=localhost;dbname=seguro_hotel;charset=utf8mb4",
                    "root",
                    "",
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
            } catch (PDOException $e) {
                error_log("connexion.php getPDO error: " . $e->getMessage());
                die("Une erreur de connexion à la base de données est survenue. Veuillez réessayer.");
            }
        }
        return $pdo;
    }
}

$pdo = getPDO();
?>