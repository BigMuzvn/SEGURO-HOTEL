<?php
/**
 * Layout Header — Master Hotel Setup Hub
 */
require_once __DIR__ . '/HotelManager.php';
$activeHotel = HotelManager::getActiveHotel();
$allHotels = HotelManager::getAllHotels();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? 'Master Hotel Studio' ?> — HospitOS Multi-Hôtels</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Jost:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <style>
    :root {
      --bg-master: #09120e;
      --bg-surface: #0f1f18;
      --bg-card: #142820;
      --bg-card-hover: #19342a;
      --border-master: rgba(201, 168, 76, 0.22);
      --border-active: #c9a84c;
      --text-white: #ffffff;
      --text-muted: rgba(255, 255, 255, 0.65);
      --text-gold: #d4af37;
      --gold-primary: #c9a84c;
      --gold-hover: #dfba73;
      --accent-emerald: #10b981;
      --accent-sapphire: #3b82f6;
      --accent-bordeaux: #e11d48;
      --accent-amber: #f59e0b;
      --danger: #ef4444;
      --success: #10b981;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background-color: var(--bg-master);
      background-image: 
        radial-gradient(circle at 10% 20%, rgba(201, 168, 76, 0.05) 0%, transparent 40%),
        radial-gradient(circle at 90% 80%, rgba(16, 185, 129, 0.04) 0%, transparent 40%);
      color: var(--text-white);
      font-family: 'Jost', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* ── BARRE DE NAVIGATION MASTER ── */
    .master-navbar {
      background: rgba(12, 24, 18, 0.92);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      border-bottom: 1px solid var(--border-master);
      padding: 14px 36px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .master-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      color: var(--text-white);
    }
    .master-logo-icon {
      width: 38px; height: 38px;
      background: linear-gradient(135deg, #c9a84c 0%, #8a6a24 100%);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #111;
      font-size: 1.1rem;
      box-shadow: 0 4px 15px rgba(201, 168, 76, 0.25);
    }
    .master-brand-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.4rem;
      font-weight: 700;
      letter-spacing: 0.12em;
      color: var(--gold-primary);
      text-transform: uppercase;
      line-height: 1.1;
    }
    .master-brand-subtitle {
      font-size: 0.65rem;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: var(--text-muted);
    }

    .master-nav-links {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .master-nav-link {
      padding: 8px 16px;
      border-radius: 6px;
      text-decoration: none;
      color: var(--text-muted);
      font-size: 0.82rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      font-weight: 500;
      transition: all 0.25s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .master-nav-link:hover {
      color: var(--gold-primary);
      background: rgba(201, 168, 76, 0.08);
    }
    .master-nav-link.active {
      color: #111;
      background: var(--gold-primary);
      font-weight: 600;
    }

    .master-active-badge {
      display: flex;
      align-items: center;
      gap: 12px;
      background: rgba(20, 40, 32, 0.8);
      border: 1px solid var(--border-master);
      padding: 6px 14px;
      border-radius: 30px;
    }
    .active-dot {
      width: 8px; height: 8px;
      border-radius: 50%;
      background: var(--success);
      box-shadow: 0 0 10px var(--success);
      animation: pulseDot 2s infinite;
    }
    @keyframes pulseDot {
      0%, 100% { transform: scale(1); opacity: 1; }
      50% { transform: scale(1.3); opacity: 0.7; }
    }
    .active-hotel-name {
      font-size: 0.78rem;
      font-weight: 600;
      color: var(--gold-primary);
      letter-spacing: 0.05em;
    }
    .btn-view-site {
      font-size: 0.72rem;
      text-decoration: none;
      color: var(--text-white);
      background: rgba(255, 255, 255, 0.1);
      padding: 4px 10px;
      border-radius: 20px;
      transition: all 0.2s;
    }
    .btn-view-site:hover {
      background: var(--gold-primary);
      color: #111;
    }

    /* ── CONTENEUR PRINCIPAL ── */
    .master-container {
      max-width: 1300px;
      width: 100%;
      margin: 0 auto;
      padding: 40px 24px 80px;
      flex: 1;
    }

    /* ── BOUTONS & COMPOSANTS ── */
    .btn-master-primary {
      background: linear-gradient(135deg, #c9a84c 0%, #9e7d30 100%);
      color: #111;
      font-family: 'Jost', sans-serif;
      font-weight: 600;
      font-size: 0.85rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      padding: 12px 24px;
      border-radius: 6px;
      border: none;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
      box-shadow: 0 4px 20px rgba(201, 168, 76, 0.3);
    }
    .btn-master-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(201, 168, 76, 0.45);
      background: linear-gradient(135deg, #dfba73 0%, #b89035 100%);
    }

    .btn-master-secondary {
      background: rgba(255, 255, 255, 0.05);
      color: var(--text-white);
      font-family: 'Jost', sans-serif;
      font-weight: 500;
      font-size: 0.82rem;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      padding: 11px 20px;
      border-radius: 6px;
      border: 1px solid rgba(255, 255, 255, 0.15);
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.25s;
    }
    .btn-master-secondary:hover {
      background: rgba(201, 168, 76, 0.15);
      border-color: var(--gold-primary);
      color: var(--gold-primary);
    }

    /* ── CARTES GLASSMORPHISM ── */
    .master-card {
      background: var(--bg-surface);
      border: 1px solid var(--border-master);
      border-radius: 12px;
      padding: 28px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
      position: relative;
      overflow: hidden;
    }
  </style>
</head>
<body>

  <!-- NAVIGATION MASTER -->
  <header class="master-navbar">
    <a href="index.php" class="master-brand">
      <div class="master-logo-icon">
        <i class="fas fa-crown"></i>
      </div>
      <div>
        <div class="master-brand-title">HospitOS Studio</div>
        <div class="master-brand-subtitle">Hub de Gestion Multi-Hôtels</div>
      </div>
    </a>

    <nav class="master-nav-links">
      <a href="index.php" class="master-nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
        <i class="fas fa-th-large"></i> Mes Hôtels (<?= count($allHotels) ?>)
      </a>
      <a href="create.php" class="master-nav-link <?= $currentPage === 'create.php' ? 'active' : '' ?>">
        <i class="fas fa-magic"></i> Déployer un Hôtel (Wizard)
      </a>
    </nav>

    <div class="master-active-badge">
      <span class="active-dot" title="Hôtel en production"></span>
      <div>
        <div style="font-size:0.6rem; color:var(--text-muted); text-transform:uppercase;">Hôtel Actif :</div>
        <div class="active-hotel-name"><?= htmlspecialchars($activeHotel['short_name'] ?? 'Grand Prestige') ?></div>
      </div>
      <a href="../index.php" target="_blank" class="btn-view-site" title="Voir le site en direct">
        <i class="fas fa-external-link-alt"></i> Ouvrir le site
      </a>
    </div>
  </header>

  <main class="master-container">
