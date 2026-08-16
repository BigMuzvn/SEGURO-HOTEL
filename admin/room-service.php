<?php
/**
 * ════════════════════════════════════════════════════════
 * GESTION DU ROOM SERVICE — Administration Hôtel SEGURO
 * ════════════════════════════════════════════════════════
 */

session_start();

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/AdminAuth.php';
require_once __DIR__ . '/../includes/Mail.php';

AdminAuth::requireAccess('room_service');

$database = new Database();
$db = $database->getConnection();

$message = '';
$erreur = '';

// Actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        $erreur = "Session de sécurité expirée ou jeton CSRF invalide. Veuillez recharger la page.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'changer_statut') {
            $id = (int)($_POST['id'] ?? 0);
            $nouveau_statut = $_POST['statut'] ?? 'recue';

            if ($id > 0 && in_array($nouveau_statut, ['recue', 'en_preparation', 'livree', 'annulee'])) {
                // Récupérer les infos de la commande pour l'email
                $stmtInfo = $db->prepare("SELECT * FROM room_service_commandes WHERE id = ? LIMIT 1");
                $stmtInfo->execute([$id]);
                $orderInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);

                $userEmail = '';
                if (!empty($orderInfo['user_id'])) {
                    $stmtUser = $db->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
                    $stmtUser->execute([$orderInfo['user_id']]);
                    $userEmail = $stmtUser->fetchColumn() ?: '';
                }

                $stmt = $db->prepare("UPDATE room_service_commandes SET statut = :statut WHERE id = :id");
                if ($stmt->execute([':statut' => $nouveau_statut, ':id' => $id])) {
                    $libelleStatut = ucfirst(str_replace('_', ' ', $nouveau_statut));
                    $message = "Le statut de la commande #{$id} a été mis à jour : " . $libelleStatut;

                    // Envoi de notification email au client
                    $targetEmail = !empty($orderInfo['client_email']) ? $orderInfo['client_email'] : $userEmail;
                    if (!empty($targetEmail) && filter_var($targetEmail, FILTER_VALIDATE_EMAIL)) {
                        try {
                            Mail::sendRoomServiceStatusUpdate(
                                $targetEmail,
                                $orderInfo['client_nom'],
                                $orderInfo['reference'],
                                $orderInfo['chambre_numero'],
                                $nouveau_statut
                            );
                            $message .= " — Notification email envoyée à {$targetEmail}.";
                        } catch (Exception $mailErr) {
                            error_log("Room service status mail error: " . $mailErr->getMessage());
                        }
                    }
                } else {
                    $erreur = "Erreur lors de la mise à jour de la commande.";
                }
            }
        }

        if ($action === 'supprimer') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $db->prepare("DELETE FROM room_service_commandes WHERE id = :id");
                if ($stmt->execute([':id' => $id])) {
                    $message = "Commande supprimée avec succès.";
                } else {
                    $erreur = "Erreur lors de la suppression.";
                }
            }
        }
    }
}

// Filtre et recherche
$filtre_statut = $_GET['statut'] ?? 'all';
$recherche = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM room_service_commandes WHERE 1=1";
$params = [];

if ($filtre_statut !== 'all' && in_array($filtre_statut, ['recue', 'en_preparation', 'livree', 'annulee'])) {
    $sql .= " AND statut = :statut";
    $params[':statut'] = $filtre_statut;
}

if (!empty($recherche)) {
    $sql .= " AND (client_nom LIKE :q OR chambre_numero LIKE :q OR client_telephone LIKE :q OR reference LIKE :q)";
    $params[':q'] = "%{$recherche}%";
}

$sql .= " ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$stats_stmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'recue' THEN 1 ELSE 0 END) as recues,
        SUM(CASE WHEN statut = 'en_preparation' THEN 1 ELSE 0 END) as en_preparation,
        SUM(CASE WHEN statut = 'livree' THEN 1 ELSE 0 END) as livrees,
        SUM(CASE WHEN statut != 'annulee' THEN total_estime ELSE 0 END) as ca_total
    FROM room_service_commandes
");
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Service &amp; Restauration — Admin <?= htmlspecialchars(hotel_name()) ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Jost:wght@200;300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?= hotel_theme_css() ?>
    
    <style>
        :root {
            --vert: #1a3a2a;
            --vert-clair: #2d5c40;
            --or: #c9a84c;
            --or-clair: #dfc278;
            --noir: #0d1a12;
            --blanc: #faf8f3;
            --gris-clair: #f4f1ea;
            --gris-moyen: #e2ddd5;
            --gris-fonce: #6b7c72;
            --success: #28a745;
            --warning: #e67e22;
            --danger: #dc3545;
            --info: #17a2b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Jost', sans-serif;
            background: #f0ede6;
            color: #2c3e50;
            display: flex;
            min-height: 100vh;
        }

        /* ── SIDEBAR FIXE ET STATIQUE ── */
        .sidebar {
            width: 260px;
            background: var(--vert);
            color: var(--blanc);
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            overflow: hidden;
        }

        .sidebar-header {
            padding: 14px 18px;
            border-bottom: 1px solid rgba(250,248,243,0.1);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--or);
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.3rem;
            text-decoration: none;
        }

        .sidebar-nav {
            flex: 1;
            padding: 6px 0;
            overflow-y: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .sidebar-nav::-webkit-scrollbar {
            display: none;
            width: 0;
            height: 0;
        }

        .nav-section {
            font-size: 0.58rem;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: rgba(250,248,243,0.4);
            padding: 6px 18px 2px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 18px;
            color: rgba(250,248,243,0.8);
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.2s;
        }

        .nav-item:hover, .nav-item.active {
            color: var(--or);
            background: rgba(201,168,76,0.1);
            border-left: 3px solid var(--or);
        }

        .nav-item i {
            width: 16px;
            font-size: 0.85rem;
            text-align: center;
        }

        .sidebar-footer {
            padding: 10px 18px;
            border-top: 1px solid rgba(250,248,243,0.1);
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .admin-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--or);
            color: var(--vert);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .admin-details h4 {
            font-size: 0.78rem;
            color: #fff;
            line-height: 1.2;
        }

        .admin-details p {
            font-size: 0.62rem;
            color: rgba(255,255,255,0.6);
            line-height: 1.2;
        }

        /* ── MAIN CONTENT ── */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 35px 40px;
        }

        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 18px;
            border-bottom: 1px solid #ddd;
        }

        .top-header h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2rem;
            color: var(--vert);
        }

        .top-header p {
            font-size: 0.88rem;
            color: var(--gris-fonce);
        }

        /* ── KPI STATS ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.04);
            border-left: 4px solid var(--or);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-card.warning { border-left-color: var(--warning); }
        .stat-card.info { border-left-color: #2980b9; }
        .stat-card.success { border-left-color: var(--success); }

        .stat-number {
            font-size: 1.7rem;
            font-weight: 600;
            color: var(--vert);
        }

        .stat-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--gris-fonce);
        }

        .stat-icon {
            font-size: 2rem;
            color: rgba(201,168,76,0.3);
        }

        /* ── FILTRES & RECHERCHE ── */
        .filter-bar {
            background: #fff;
            padding: 14px 18px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-tabs {
            display: flex;
            gap: 8px;
        }

        .filter-tab {
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 0.76rem;
            text-decoration: none;
            color: #555;
            background: #f0ede6;
            font-weight: 500;
            transition: all 0.2s;
        }

        .filter-tab.active, .filter-tab:hover {
            background: var(--vert);
            color: #fff;
        }

        .search-form {
            display: flex;
            gap: 8px;
        }

        .search-input {
            padding: 8px 14px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 0.82rem;
            font-family: 'Jost', sans-serif;
            width: 250px;
        }

        .btn-search {
            background: var(--or);
            color: #111;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.82rem;
        }

        /* ── GRILLE COMMANDES ── */
        .orders-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .order-card {
            background: #fff;
            border-radius: 10px;
            padding: 22px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border-top: 3.5px solid var(--or);
            display: grid;
            grid-template-columns: 280px 1fr 260px;
            gap: 24px;
            align-items: start;
        }

        .order-card.statut-recue { border-top-color: var(--warning); background: #fffcf5; }
        .order-card.statut-en_preparation { border-top-color: #2980b9; }
        .order-card.statut-livree { border-top-color: var(--success); opacity: 0.92; }
        .order-card.statut-annulee { border-top-color: var(--danger); opacity: 0.75; }

        .order-ref {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--vert);
        }

        .badge-statut {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: inline-block;
            margin-top: 4px;
        }

        .badge-recue { background: #fff3cd; color: #856404; animation: pulseBadge 2s infinite; }
        .badge-en_preparation { background: #d1ecf1; color: #0c5460; }
        .badge-livree { background: #d4edda; color: #155724; }
        .badge-annulee { background: #f8d7da; color: #721c24; }

        @keyframes pulseBadge {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        .order-info {
            font-size: 0.85rem;
            line-height: 1.6;
            margin-top: 10px;
        }

        .order-items-box {
            background: #faf8f3;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid rgba(201,168,76,0.2);
        }

        .order-items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }

        .order-items-table th {
            text-align: left;
            padding: 6px 0;
            color: var(--gris-fonce);
            border-bottom: 1px solid #ddd;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.7rem;
        }

        .order-items-table td {
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
        }

        .order-total-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 700;
            font-size: 1rem;
            color: var(--vert);
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1.5px solid var(--vert);
        }

        .order-actions-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 16px;
        }

        .order-actions-box h5 {
            font-size: 0.8rem;
            color: var(--vert);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-select-sm {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-bottom: 10px;
            background: #fff;
        }

        .btn-update-statut {
            width: 100%;
            background: var(--vert);
            color: #fff;
            border: none;
            padding: 9px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            transition: background 0.2s;
        }

        .btn-update-statut:hover { background: var(--vert-clair); }

        .btn-whatsapp {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            margin-top: 8px;
            padding: 8px;
            background: #25D366;
            color: #fff;
            border-radius: 4px;
            font-size: 0.75rem;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.2s;
        }

        .btn-whatsapp:hover { opacity: 0.9; }

        .btn-del-order {
            background: transparent;
            border: none;
            color: #dc3545;
            font-size: 0.72rem;
            cursor: pointer;
            margin-top: 10px;
            display: inline-block;
            text-decoration: underline;
        }

        .empty-state {
            background: #fff;
            padding: 60px 20px;
            text-align: center;
            border-radius: 8px;
            color: #777;
        }

        .empty-state i {
            font-size: 3rem;
            color: rgba(201,168,76,0.3);
            margin-bottom: 14px;
            display: block;
        }

        @media (max-width: 1200px) {
            .order-card { grid-template-columns: 1fr 1fr; }
            .order-actions-box { grid-column: span 2; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-logo span, .nav-item span, .nav-section, .admin-details { display: none; }
            .main-content { margin-left: 70px; padding: 20px; }
            .order-card { grid-template-columns: 1fr; }
            .order-actions-box { grid-column: span 1; }
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-logo">
                <i class="fas fa-crown"></i>
                <span><?= htmlspecialchars(hotel_short_name()) ?></span>
            </a>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">Principal</div>
            <?php if (AdminAuth::can('dashboard')): ?>
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            <?php endif; ?>
            <?php if (AdminAuth::can('calendrier')): ?>
                <a href="calendrier.php" class="nav-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Calendrier</span>
                </a>
            <?php endif; ?>
            
            <div class="nav-section" style="margin-top: 14px;">Gestion</div>
            <?php if (AdminAuth::can('reservations')): ?>
                <a href="reservations.php" class="nav-item">
                    <i class="fas fa-book"></i>
                    <span>Réservations</span>
                </a>
            <?php endif; ?>
            <?php if (AdminAuth::can('room_service')): ?>
                <a href="room-service.php" class="nav-item active">
                    <i class="fas fa-concierge-bell"></i>
                    <span>Room Service</span>
                </a>
            <?php endif; ?>
            <?php if (AdminAuth::can('evenements')): ?>
                <a href="evenements.php" class="nav-item">
                    <i class="fas fa-glass-cheers"></i>
                    <span>Devis Événements</span>
                </a>
            <?php endif; ?>
            <?php if (AdminAuth::can('clients')): ?>
                <a href="clients.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Clients</span>
                </a>
            <?php endif; ?>
            <?php if (AdminAuth::can('chambres')): ?>
                <a href="chambres.php" class="nav-item">
                    <i class="fas fa-bed"></i>
                    <span>Chambres</span>
                </a>
            <?php endif; ?>
            <?php if (AdminAuth::can('avis')): ?>
                <a href="avis.php" class="nav-item">
                    <i class="fas fa-star"></i>
                    <span>Avis Clients</span>
                </a>
            <?php endif; ?>
            <?php if (AdminAuth::can('codes_promo')): ?>
                <a href="codes-promo.php" class="nav-item">
                    <i class="fas fa-tags"></i>
                    <span>Codes Promo</span>
                </a>
            <?php endif; ?>
            <?php if (AdminAuth::can('profil')): ?>
                <a href="profil.php" class="nav-item">
                    <i class="fas fa-user-shield"></i>
                    <span>Équipe &amp; Profil</span>
                </a>
            <?php endif; ?>
        </nav>
        
        <div class="sidebar-footer">
            <div class="admin-info">
                <div class="admin-avatar">
                    <?= strtoupper(substr($_SESSION['user_prenom'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="admin-details">
                    <h4><?= htmlspecialchars(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? '')) ?></h4>
                    <p><?= ($_SESSION['user_role'] ?? '') === 'super_admin' ? 'Super Administrateur' : 'Administrateur' ?></p>
                </div>
            </div>
            <a href="../pages/deconnexion.php" style="display:flex; align-items:center; gap:8px; margin-top:10px; color:rgba(250,248,243,0.6); text-decoration:none; font-size:0.75rem;">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        
        <div class="top-header">
            <div>
                <h1>Commandes Room Service &amp; Restauration</h1>
                <p>Commandes passées en chambre par les résidents en temps réel</p>
            </div>
            <a href="../pages/room-service.php" target="_blank" style="padding:9px 18px; border:1px solid var(--or); color:var(--vert); text-decoration:none; border-radius:4px; font-size:0.8rem; font-weight:500; display:inline-flex; align-items:center; gap:8px; background:#fff;">
                <i class="fas fa-external-link-alt"></i> Voir le menu Room Service
            </a>
        </div>

        <?php if ($message): ?>
            <div style="background:#d4edda; color:#155724; padding:12px 18px; border-radius:6px; margin-bottom:20px; border:1px solid #c3e6cb; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($erreur): ?>
            <div style="background:#f8d7da; color:#721c24; padding:12px 18px; border-radius:6px; margin-bottom:20px; border:1px solid #f5c6cb; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($erreur) ?>
            </div>
        <?php endif; ?>

        <!-- KPI STATS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div>
                    <div class="stat-number"><?= (int)($stats['total'] ?? 0) ?></div>
                    <div class="stat-label">Total Commandes</div>
                </div>
                <i class="fas fa-utensils stat-icon"></i>
            </div>
            <div class="stat-card warning">
                <div>
                    <div class="stat-number"><?= (int)($stats['recues'] ?? 0) ?></div>
                    <div class="stat-label">Nouvelles / En attente</div>
                </div>
                <i class="fas fa-bell stat-icon" style="color:rgba(230,126,34,0.3);"></i>
            </div>
            <div class="stat-card info">
                <div>
                    <div class="stat-number"><?= (int)($stats['en_preparation'] ?? 0) ?></div>
                    <div class="stat-label">En Cuisine / Préparation</div>
                </div>
                <i class="fas fa-fire stat-icon" style="color:rgba(41,128,185,0.3);"></i>
            </div>
            <div class="stat-card success">
                <div>
                    <div class="stat-number"><?= number_format((float)($stats['ca_total'] ?? 0), 0, ',', ' ') ?> F</div>
                    <div class="stat-label">Chiffre d'Affaires</div>
                </div>
                <i class="fas fa-coins stat-icon" style="color:rgba(40,167,69,0.3);"></i>
            </div>
        </div>

        <!-- FILTRES & RECHERCHE -->
        <div class="filter-bar">
            <div class="filter-tabs">
                <a href="room-service.php" class="filter-tab <?= $filtre_statut === 'all' ? 'active' : '' ?>">Toutes (<?= (int)($stats['total'] ?? 0) ?>)</a>
                <a href="room-service.php?statut=recue" class="filter-tab <?= $filtre_statut === 'recue' ? 'active' : '' ?>">À Traiter (<?= (int)($stats['recues'] ?? 0) ?>)</a>
                <a href="room-service.php?statut=en_preparation" class="filter-tab <?= $filtre_statut === 'en_preparation' ? 'active' : '' ?>">En Préparation (<?= (int)($stats['en_preparation'] ?? 0) ?>)</a>
                <a href="room-service.php?statut=livree" class="filter-tab <?= $filtre_statut === 'livree' ? 'active' : '' ?>">Livrées (<?= (int)($stats['livrees'] ?? 0) ?>)</a>
                <a href="room-service.php?statut=annulee" class="filter-tab <?= $filtre_statut === 'annulee' ? 'active' : '' ?>">Annulées</a>
            </div>

            <form method="GET" class="search-form">
                <?php if ($filtre_statut !== 'all'): ?>
                    <input type="hidden" name="statut" value="<?= htmlspecialchars($filtre_statut) ?>">
                <?php endif; ?>
                <input type="text" name="q" value="<?= htmlspecialchars($recherche) ?>" placeholder="Rechercher chambre, client, tél..." class="search-input">
                <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <!-- LISTE DES COMMANDES -->
        <?php if (empty($commandes)): ?>
            <div class="empty-state">
                <i class="fas fa-concierge-bell"></i>
                <h3>Aucune commande Room Service</h3>
                <p style="margin-top:6px; font-size:0.9rem;">Les commandes passées depuis la page Room Service ou les chambres apparaîtront ici.</p>
            </div>
        <?php else: ?>
            <div class="orders-grid">
                <?php foreach ($commandes as $cmd): ?>
                    <?php 
                        $items = json_decode($cmd['elements_commande'], true) ?: [];
                        $cleanTel = preg_replace('/[^0-9]/', '', $cmd['client_telephone'] ?? '');
                    ?>
                    <div class="order-card statut-<?= htmlspecialchars($cmd['statut']) ?>">
                        
                        <!-- Col 1 : Infos Chambre & Client -->
                        <div>
                            <div class="order-ref"><?= htmlspecialchars($cmd['reference']) ?></div>
                            <span class="badge-statut badge-<?= htmlspecialchars($cmd['statut']) ?>">
                                <?php if ($cmd['statut'] === 'recue'): ?>
                                    <i class="fas fa-bell"></i> Nouvelle Commande
                                <?php elseif ($cmd['statut'] === 'en_preparation'): ?>
                                    <i class="fas fa-utensils"></i> En Préparation
                                <?php elseif ($cmd['statut'] === 'livree'): ?>
                                    <i class="fas fa-check"></i> Livrée en chambre
                                <?php else: ?>
                                    <i class="fas fa-times"></i> Annulée
                                <?php endif; ?>
                            </span>

                            <div class="order-info">
                                <div style="font-size:1.05rem; font-weight:700; color:var(--vert); margin-top:8px;">
                                    <i class="fas fa-door-closed" style="color:var(--or);"></i> <?= htmlspecialchars($cmd['chambre_numero']) ?>
                                </div>
                                <div style="font-weight:600; color:#333; margin-top:4px;">
                                    <i class="fas fa-user"></i> <?= htmlspecialchars($cmd['client_nom']) ?>
                                </div>
                                <?php if (!empty($cmd['client_telephone'])): ?>
                                    <div style="margin-top:4px;">
                                        <i class="fas fa-phone"></i> <a href="tel:<?= htmlspecialchars($cmd['client_telephone']) ?>" style="color:var(--vert); text-decoration:none;"><?= htmlspecialchars($cmd['client_telephone']) ?></a>
                                    </div>
                                <?php endif; ?>
                                <div style="font-size:0.75rem; color:#888; margin-top:8px;">
                                    <i class="fas fa-clock"></i> Reçue le <?= date('d/m/Y à H:i', strtotime($cmd['created_at'])) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Col 2 : Détail du Plateau & Plats -->
                        <div class="order-items-box">
                            <h5 style="font-size:0.85rem; color:var(--vert); margin-bottom:8px; text-transform:uppercase; letter-spacing:0.05em;">
                                <i class="fas fa-concierge-bell" style="color:var(--or);"></i> Composition du Plateau (<?= count($items) ?> article(s))
                            </h5>
                            
                            <table class="order-items-table">
                                <thead>
                                    <tr>
                                        <th>Article</th>
                                        <th style="text-align:center; width:60px;">Qté</th>
                                        <th style="text-align:right; width:90px;">P.U.</th>
                                        <th style="text-align:right; width:100px;">Sous-total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $it): ?>
                                        <?php 
                                            $qte = (int)($it['qty'] ?? 1);
                                            $prix = (float)($it['price'] ?? 0);
                                            $stotal = $qte * $prix;
                                        ?>
                                        <tr>
                                            <td style="font-weight:500; color:#222;"><?= htmlspecialchars($it['name'] ?? 'Article') ?></td>
                                            <td style="text-align:center; font-weight:600; color:var(--vert);">x<?= $qte ?></td>
                                            <td style="text-align:right; color:#777;"><?= number_format($prix, 0, ',', ' ') ?> F</td>
                                            <td style="text-align:right; font-weight:600; color:var(--vert);"><?= number_format($stotal, 0, ',', ' ') ?> F</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <div class="order-total-bar">
                                <span>TOTAL PLATEAU :</span>
                                <span style="font-size:1.15rem; color:var(--or-texte);"><?= number_format((float)$cmd['total_estime'], 0, ',', ' ') ?> FCFA</span>
                            </div>

                            <?php if (!empty($cmd['instructions'])): ?>
                                <div style="margin-top:10px; padding:8px 12px; background:#fff; border-left:3px solid var(--or); border-radius:4px; font-size:0.8rem; color:#555;">
                                    <strong>Instructions client :</strong> "<?= htmlspecialchars($cmd['instructions']) ?>"
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Col 3 : Actions Personnel & Cuisine -->
                        <div class="order-actions-box">
                            <h5>Gestion Commande</h5>
                            
                            <form method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="changer_statut">
                                <input type="hidden" name="id" value="<?= $cmd['id'] ?>">
                                
                                <label style="font-size:0.72rem; color:#666; display:block; margin-bottom:4px;">Statut actuel :</label>
                                <select name="statut" class="form-select-sm">
                                    <option value="recue" <?= $cmd['statut'] === 'recue' ? 'selected' : '' ?>>🟡 Reçue (En attente)</option>
                                    <option value="en_preparation" <?= $cmd['statut'] === 'en_preparation' ? 'selected' : '' ?>>🔵 En Cuisine / Préparation</option>
                                    <option value="livree" <?= $cmd['statut'] === 'livree' ? 'selected' : '' ?>>🟢 Livrée en chambre</option>
                                    <option value="annulee" <?= $cmd['statut'] === 'annulee' ? 'selected' : '' ?>>🔴 Annulée</option>
                                </select>

                                <button type="submit" class="btn-update-statut">Mettre à jour</button>
                            </form>

                            <?php if (!empty($cleanTel)): ?>
                                <?php
                                    $msgWa = rawurlencode("Bonjour {$cmd['client_nom']}, votre commande Room Service [{$cmd['reference']}] pour la {$cmd['chambre_numero']} est bien prise en charge par notre conciergerie " . hotel_short_name() . ".");
                                ?>
                                <a href="https://wa.me/<?= $cleanTel ?>?text=<?= $msgWa ?>" target="_blank" class="btn-whatsapp">
                                    <i class="fab fa-whatsapp"></i> Notifier via WhatsApp
                                </a>
                            <?php endif; ?>

                            <form method="POST" onsubmit="return confirm('Supprimer définitivement cette commande ?');" style="text-align:right;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="id" value="<?= $cmd['id'] ?>">
                                <button type="submit" class="btn-del-order">Supprimer la commande</button>
                            </form>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

</body>
</html>
