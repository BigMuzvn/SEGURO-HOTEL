<button class="burger">☰</button>
<nav class="nav-menu">
    ...
</nav>


<?php 
if(session_status()===PHP_SESSION_NONE) session_start(); 
require_once __DIR__ . '/../includes/config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(hotel_name()) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <?= hotel_theme_css() ?>
</head>
<body>

<header class="site-header">
    <div class="header-container">
        
        <!-- Logo -->
        <div class="logo">
            <a href="../index.php">
                <h1><?= htmlspecialchars(hotel_short_name()) ?></h1>
            </a>
        </div>

        <!-- Bouton hamburger mobile -->
        <button class="burger" aria-label="Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- Navigation -->
        <nav class="nav-menu">
            <ul>
                <li><a href="../index.php">Accueil</a></li>
                <li><a href="../pages/chambres.php">Chambres</a></li>
                <li><a href="../pages/services.php">Services</a></li>
                <li><a href="../pages/galerie.php">Galerie</a></li>
                <li><a href="../pages/evenements.php">Événements</a></li>
                <li><a href="../pages/about.php">À propos</a></li>
                <li><a href="../pages/contact.php">Contact</a></li>
                <li>
                    <a href="../pages/reservation-system.php" class="btn-reserver">
                        Réserver
                    </a>
                </li>
            </ul>
        </nav>

    </div>
</header>