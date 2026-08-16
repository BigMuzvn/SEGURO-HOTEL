<?php
/**
 * GESTION DES DEVIS ÉVÉNEMENTS
 */

session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin'])) {
    header('Location: ../pages/connexion-client.php');
    exit;
}

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/AdminAuth.php';
require_once __DIR__ . '/../includes/Mail.php';

AdminAuth::requireAccess('evenements');

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
        $nouveau_statut = $_POST['statut'] ?? 'en_attente';
        $note_admin = trim($_POST['note_admin'] ?? '');
        $notifier_client = isset($_POST['notifier_client']) ? 1 : 0;

        if ($id > 0 && in_array($nouveau_statut, ['en_attente', 'traite', 'rejete'])) {
            $stmtDevis = $db->prepare("SELECT * FROM devis_evenements WHERE id = ? LIMIT 1");
            $stmtDevis->execute([$id]);
            $devis = $stmtDevis->fetch(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("UPDATE devis_evenements SET statut = :statut, note_admin = :note WHERE id = :id");
            if ($stmt->execute([':statut' => $nouveau_statut, ':note' => $note_admin, ':id' => $id])) {
                $statutLibelle = $nouveau_statut === 'traite' ? 'Traité / Devis envoyé' : ($nouveau_statut === 'rejete' ? 'Rejeté' : 'En attente');
                $message = "Le statut du devis #{$id} a été mis à jour en : " . $statutLibelle;

                if ($notifier_client && $devis && !empty($devis['email'])) {
                    try {
                        Mail::sendEventQuoteResponse(
                            $devis['email'],
                            $devis['nom_contact'],
                            $devis['reference'],
                            $devis['type_evenement'],
                            $nouveau_statut,
                            $note_admin,
                            $devis['budget_estime']
                        );
                        $message .= " — Notification email transmise au client ({$devis['email']}).";
                    } catch (Exception $mailErr) {
                        error_log("Event quote notification mail error: " . $mailErr->getMessage());
                    }
                }
            } else {
                $erreur = "Erreur lors de la mise à jour du devis.";
            }
        }
    }

    if ($action === 'envoyer_proposition') {
        $id = (int)($_POST['id'] ?? 0);
        $message_propos = trim($_POST['message_propos'] ?? '');
        $montant_chiffre = trim($_POST['montant_chiffre'] ?? '');

        if ($id > 0) {
            $stmtDevis = $db->prepare("SELECT * FROM devis_evenements WHERE id = ? LIMIT 1");
            $stmtDevis->execute([$id]);
            $devis = $stmtDevis->fetch(PDO::FETCH_ASSOC);

            if ($devis && !empty($devis['email'])) {
                $noteComplete = $message_propos . ($montant_chiffre ? "\n[Montant proposé : {$montant_chiffre}]" : '');
                $stmt = $db->prepare("UPDATE devis_evenements SET statut = 'traite', note_admin = :note, budget_estime = :budget WHERE id = :id");
                $stmt->execute([
                    ':note' => $noteComplete,
                    ':budget' => $montant_chiffre ?: $devis['budget_estime'],
                    ':id' => $id
                ]);

                try {
                    Mail::sendEventQuoteResponse(
                        $devis['email'],
                        $devis['nom_contact'],
                        $devis['reference'],
                        $devis['type_evenement'],
                        'traite',
                        $message_propos,
                        $montant_chiffre ?: $devis['budget_estime']
                    );
                    $message = "La proposition officielle a été envoyée par email à {$devis['email']} et le statut est passé à 'Traité'.";
                } catch (Exception $mailErr) {
                    error_log("Event quote send proposal error: " . $mailErr->getMessage());
                    $erreur = "Erreur lors de l'envoi de l'email : " . $mailErr->getMessage();
                }
            }
        }
    }

    if ($action === 'supprimer') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM devis_evenements WHERE id = :id");
            if ($stmt->execute([':id' => $id])) {
                $message = "Demande de devis supprimée avec succès.";
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

$sql = "SELECT * FROM devis_evenements WHERE 1=1";
$params = [];

if ($filtre_statut !== 'all' && in_array($filtre_statut, ['en_attente', 'traite', 'rejete'])) {
    $sql .= " AND statut = :statut";
    $params[':statut'] = $filtre_statut;
}

if (!empty($recherche)) {
    $sql .= " AND (nom_contact LIKE :q OR entreprise LIKE :q OR email LIKE :q OR telephone LIKE :q OR reference LIKE :q)";
    $params[':q'] = "%{$recherche}%";
}

$sql .= " ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$devis_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques globales
$stats_stmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
        SUM(CASE WHEN statut = 'traite' THEN 1 ELSE 0 END) as traite,
        SUM(CASE WHEN statut = 'rejete' THEN 1 ELSE 0 END) as rejete,
        SUM(nb_participants) as total_participants
    FROM devis_evenements
");
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devis Événements &amp; Séminaires — Admin <?= htmlspecialchars(hotel_name()) ?></title>
    
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
            font-size: 0.7rem;
            color: rgba(255,255,255,0.6);
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
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }

        .top-header h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2rem;
            color: var(--vert);
        }

        .top-header p {
            font-size: 0.9rem;
            color: var(--gris-fonce);
        }

        /* ── KPI STATS ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
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
        .stat-card.success { border-left-color: var(--success); }
        .stat-card.info { border-left-color: var(--info); }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--vert);
        }

        .stat-label {
            font-size: 0.75rem;
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
            padding: 16px 20px;
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
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.78rem;
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
            font-size: 0.85rem;
            font-family: 'Jost', sans-serif;
            width: 260px;
        }

        .btn-search {
            background: var(--or);
            color: #111;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
        }

        /* ── LISTE DES DEVIS ── */
        .devis-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .devis-card {
            background: #fff;
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border-top: 3px solid var(--or);
            display: grid;
            grid-template-columns: 1fr 1fr 300px;
            gap: 24px;
            align-items: start;
        }

        .devis-card.statut-en_attente { border-top-color: var(--warning); }
        .devis-card.statut-traite { border-top-color: var(--success); }
        .devis-card.statut-rejete { border-top-color: #999; opacity: 0.85; }

        .devis-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .devis-ref {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--vert);
        }

        .badge-statut {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-en_attente { background: #fff3cd; color: #856404; }
        .badge-traite { background: #d4edda; color: #155724; }
        .badge-rejete { background: #f8d7da; color: #721c24; }

        .prospect-info {
            font-size: 0.85rem;
            line-height: 1.6;
        }

        .prospect-info strong { color: var(--vert); font-size: 0.95rem; }
        .prospect-info a { color: var(--vert-clair); text-decoration: none; font-weight: 500; }
        .prospect-info a:hover { text-decoration: underline; color: var(--or); }

        .event-details {
            background: #faf8f3;
            padding: 16px;
            border-radius: 8px;
            font-size: 0.82rem;
            line-height: 1.6;
        }

        .event-details h5 {
            font-size: 0.9rem;
            color: var(--vert);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .service-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 8px;
        }

        .service-tag {
            background: rgba(201,168,76,0.15);
            color: #6b5212;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
        }

        .admin-action-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 16px;
        }

        .admin-action-box h5 {
            font-size: 0.85rem;
            color: var(--vert);
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-select-sm {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.82rem;
            margin-bottom: 10px;
            background: #fff;
        }

        .textarea-sm {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.8rem;
            font-family: 'Jost', sans-serif;
            margin-bottom: 10px;
            resize: vertical;
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
            letter-spacing: 0.1em;
            text-transform: uppercase;
            transition: background 0.2s;
        }

        .btn-update-statut:hover { background: var(--vert-clair); }

        .btn-quick-email {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            margin-top: 8px;
            padding: 8px;
            background: #fff;
            border: 1px solid var(--or);
            color: var(--vert);
            border-radius: 4px;
            font-size: 0.75rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-quick-email:hover {
            background: var(--or);
            color: #111;
        }

        .btn-del-devis {
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
            .devis-card { grid-template-columns: 1fr 1fr; }
            .admin-action-box { grid-column: span 2; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-logo span, .nav-item span, .nav-section, .admin-details { display: none; }
            .main-content { margin-left: 70px; padding: 20px; }
            .devis-card { grid-template-columns: 1fr; }
            .admin-action-box { grid-column: span 1; }
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
                <a href="room-service.php" class="nav-item">
                    <i class="fas fa-concierge-bell"></i>
                    <span>Room Service</span>
                </a>
            <?php endif; ?>
            <?php if (AdminAuth::can('evenements')): ?>
                <a href="evenements.php" class="nav-item active">
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
                    <p>Administrateur</p>
                </div>
            </div>
            <a href="../pages/deconnexion.php" style="display:flex; align-items:center; gap:8px; margin-top:12px; color:rgba(250,248,243,0.6); text-decoration:none; font-size:0.8rem;">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        
        <div class="top-header">
            <div>
                <h1>Demandes de Devis Événements &amp; Séminaires</h1>
                <p>Gestion des requêtes d'entreprises, mariages, conférences et salons VIP</p>
            </div>
            <a href="../pages/evenements.php" target="_blank" style="padding:10px 20px; border:1px solid var(--or); color:var(--vert); text-decoration:none; border-radius:4px; font-size:0.82rem; font-weight:500; display:inline-flex; align-items:center; gap:8px; background:#fff;">
                <i class="fas fa-external-link-alt"></i> Voir la page Événements
            </a>
        </div>

        <?php if ($message): ?>
            <div style="background:#d4edda; color:#155724; padding:14px 20px; border-radius:6px; margin-bottom:20px; border:1px solid #c3e6cb; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($erreur): ?>
            <div style="background:#f8d7da; color:#721c24; padding:14px 20px; border-radius:6px; margin-bottom:20px; border:1px solid #f5c6cb; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($erreur) ?>
            </div>
        <?php endif; ?>

        <!-- KPI STATS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div>
                    <div class="stat-number"><?= (int)($stats['total'] ?? 0) ?></div>
                    <div class="stat-label">Total Demandes</div>
                </div>
                <i class="fas fa-file-signature stat-icon"></i>
            </div>
            <div class="stat-card warning">
                <div>
                    <div class="stat-number"><?= (int)($stats['en_attente'] ?? 0) ?></div>
                    <div class="stat-label">En attente de réponse</div>
                </div>
                <i class="fas fa-clock stat-icon" style="color:rgba(230,126,34,0.3);"></i>
            </div>
            <div class="stat-card success">
                <div>
                    <div class="stat-number"><?= (int)($stats['traite'] ?? 0) ?></div>
                    <div class="stat-label">Devis Traités &amp; Validés</div>
                </div>
                <i class="fas fa-check-circle stat-icon" style="color:rgba(40,167,69,0.3);"></i>
            </div>
            <div class="stat-card info">
                <div>
                    <div class="stat-number"><?= (int)($stats['total_participants'] ?? 0) ?></div>
                    <div class="stat-label">Participants Cumulés</div>
                </div>
                <i class="fas fa-users stat-icon" style="color:rgba(23,162,184,0.3);"></i>
            </div>
        </div>

        <!-- FILTRES & RECHERCHE -->
        <div class="filter-bar">
            <div class="filter-tabs">
                <a href="evenements.php" class="filter-tab <?= $filtre_statut === 'all' ? 'active' : '' ?>">Tous (<?= (int)($stats['total'] ?? 0) ?>)</a>
                <a href="evenements.php?statut=en_attente" class="filter-tab <?= $filtre_statut === 'en_attente' ? 'active' : '' ?>">À Traiter (<?= (int)($stats['en_attente'] ?? 0) ?>)</a>
                <a href="evenements.php?statut=traite" class="filter-tab <?= $filtre_statut === 'traite' ? 'active' : '' ?>">Traités (<?= (int)($stats['traite'] ?? 0) ?>)</a>
                <a href="evenements.php?statut=rejete" class="filter-tab <?= $filtre_statut === 'rejete' ? 'active' : '' ?>">Rejetés (<?= (int)($stats['rejete'] ?? 0) ?>)</a>
            </div>

            <form method="GET" class="search-form">
                <?php if ($filtre_statut !== 'all'): ?>
                    <input type="hidden" name="statut" value="<?= htmlspecialchars($filtre_statut) ?>">
                <?php endif; ?>
                <input type="text" name="q" value="<?= htmlspecialchars($recherche) ?>" placeholder="Rechercher contact, entreprise, réf..." class="search-input">
                <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <!-- LISTE DES DEVIS -->
        <?php if (empty($devis_list)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>Aucune demande de devis trouvée</h3>
                <p style="margin-top:6px; font-size:0.9rem;">Les demandes soumises depuis la page Événements apparaîtront ici.</p>
            </div>
        <?php else: ?>
            <div class="devis-grid">
                <?php foreach ($devis_list as $d): ?>
                    <?php 
                        $services = !empty($d['services_souhaites']) ? json_decode($d['services_souhaites'], true) : [];
                        if (!is_array($services)) {
                            $services = array_filter(explode(',', (string)$d['services_souhaites']));
                        }
                    ?>
                    <div class="devis-card statut-<?= htmlspecialchars($d['statut']) ?>">
                        
                        <!-- Col 1 : Contact & Entreprise -->
                        <div>
                            <div class="devis-header">
                                <span class="devis-ref"><?= htmlspecialchars($d['reference']) ?></span>
                                <span class="badge-statut badge-<?= htmlspecialchars($d['statut']) ?>">
                                    <?= $d['statut'] === 'en_attente' ? 'En attente' : ($d['statut'] === 'traite' ? 'Traité' : 'Rejeté') ?>
                                </span>
                            </div>

                            <div class="prospect-info">
                                <div><strong><i class="fas fa-user"></i> <?= htmlspecialchars($d['nom_contact']) ?></strong></div>
                                <?php if (!empty($d['entreprise'])): ?>
                                    <div style="color:var(--gris-fonce);"><i class="fas fa-building"></i> Entreprise : <strong><?= htmlspecialchars($d['entreprise']) ?></strong></div>
                                <?php endif; ?>
                                <div style="margin-top:6px;">
                                    <i class="fas fa-envelope"></i> <a href="mailto:<?= htmlspecialchars($d['email']) ?>"><?= htmlspecialchars($d['email']) ?></a>
                                </div>
                                <div>
                                    <i class="fas fa-phone"></i> <a href="tel:<?= htmlspecialchars($d['telephone']) ?>"><?= htmlspecialchars($d['telephone']) ?></a>
                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $d['telephone']) ?>" target="_blank" style="margin-left:6px; color:#25D366;" title="Ouvrir WhatsApp"><i class="fab fa-whatsapp"></i></a>
                                </div>
                                <div style="font-size:0.75rem; color:#888; margin-top:8px;">
                                    <i class="fas fa-calendar-plus"></i> Reçu le <?= date('d/m/Y à H:i', strtotime($d['created_at'])) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Col 2 : Détails de l'événement -->
                        <div class="event-details">
                            <h5><i class="fas fa-glass-cheers" style="color:var(--or);"></i> <?= htmlspecialchars($d['type_evenement']) ?></h5>
                            <div><strong>Espace :</strong> <?= htmlspecialchars($d['espace_souhaite']) ?></div>
                            <div><strong>Date :</strong> <?= date('d/m/Y', strtotime($d['date_evenement'])) ?><?= !empty($d['date_fin']) ? ' au ' . date('d/m/Y', strtotime($d['date_fin'])) : '' ?></div>
                            <div><strong>Participants :</strong> <span style="font-weight:600; color:var(--vert);"><?= (int)$d['nb_participants'] ?> personnes</span></div>
                            
                            <?php if (!empty($d['budget_estime'])): ?>
                                <div><strong>Budget estimé :</strong> <?= htmlspecialchars($d['budget_estime']) ?></div>
                            <?php endif; ?>

                            <?php if (!empty($services)): ?>
                                <div style="margin-top:8px;">
                                    <strong style="font-size:0.75rem; color:#555;">Prestations souhaitées :</strong>
                                    <div class="service-tags">
                                        <?php foreach ($services as $srv): ?>
                                            <span class="service-tag"><?= htmlspecialchars($srv) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($d['message'])): ?>
                                <div style="margin-top:10px; padding-top:8px; border-top:1px dashed #ddd; font-style:italic; color:#555;">
                                    "<?= nl2br(htmlspecialchars($d['message'])) ?>"
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Col 3 : Traitement & Action Admin -->
                        <div class="admin-action-box">
                            <h5>Traitement Admin</h5>
                            
                            <form method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="changer_statut">
                                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                
                                <label style="font-size:0.75rem; color:#666; display:block; margin-bottom:4px;">Statut dossier :</label>
                                <select name="statut" class="form-select-sm">
                                    <option value="en_attente" <?= $d['statut'] === 'en_attente' ? 'selected' : '' ?>>🟡 En attente</option>
                                    <option value="traite" <?= $d['statut'] === 'traite' ? 'selected' : '' ?>>🟢 Devis Traité / Envoyé</option>
                                    <option value="rejete" <?= $d['statut'] === 'rejete' ? 'selected' : '' ?>>🔴 Rejeté / Indisponible</option>
                                </select>

                                <label style="font-size:0.75rem; color:#666; display:block; margin-bottom:4px;">Note / Commentaire :</label>
                                <textarea name="note_admin" rows="2" class="textarea-sm" placeholder="Ex: Devis chiffré à 850 000 F envoyé le..."><?= htmlspecialchars($d['note_admin'] ?? '') ?></textarea>

                                <label style="display:flex; align-items:center; gap:6px; font-size:0.72rem; color:#444; margin:6px 0 8px; cursor:pointer;">
                                    <input type="checkbox" name="notifier_client" value="1" checked style="accent-color:var(--vert);">
                                    <span>Notifier le client par email</span>
                                </label>

                                <button type="submit" class="btn-update-statut">Enregistrer &amp; Notifier</button>
                            </form>

                            <button type="button" class="btn-quick-email" onclick="openProposalModal(<?= htmlspecialchars(json_encode([
                                'id' => $d['id'],
                                'reference' => $d['reference'],
                                'nom_contact' => $d['nom_contact'],
                                'entreprise' => $d['entreprise'] ?? '',
                                'email' => $d['email'],
                                'type_evenement' => $d['type_evenement'],
                                'espace_souhaite' => $d['espace_souhaite'],
                                'date_evenement' => date('d/m/Y', strtotime($d['date_evenement'])),
                                'nb_participants' => $d['nb_participants'],
                                'budget_estime' => $d['budget_estime'] ?? ''
                            ]), ENT_QUOTES) ?>)">
                                <i class="fas fa-paper-plane" style="color:var(--or);"></i> Rédiger Proposition / Devis
                            </button>

                            <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement cette demande de devis ?');" style="text-align:right;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                <button type="submit" class="btn-del-devis">Supprimer la demande</button>
                            </form>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <!-- MODAL DE RÉDACTION DE PROPOSITION DEVIS -->
    <div id="proposalModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:2000; align-items:center; justify-content:center; padding:20px;">
        <div style="background:#fff; border-radius:12px; max-width:580px; width:100%; padding:28px; box-shadow:0 15px 45px rgba(0,0,0,0.3); position:relative; border-top:4px solid var(--or);">
            <button type="button" onclick="closeProposalModal()" style="position:absolute; top:16px; right:16px; border:none; background:transparent; font-size:1.2rem; cursor:pointer; color:#888;">&times;</button>
            
            <h3 style="font-family:'Cormorant Garamond',serif; font-size:1.6rem; color:var(--vert); margin:0 0 6px 0;">
                <i class="fas fa-paper-plane" style="color:var(--or);"></i> Envoyer Proposition de Devis
            </h3>
            <p style="font-size:0.82rem; color:#666; margin:0 0 18px 0;" id="m_propos_subtitle">Dossier : ...</p>

            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="envoyer_proposition">
                <input type="hidden" name="id" id="m_devis_id" value="0">

                <div style="margin-bottom:12px;">
                    <label style="font-size:0.8rem; font-weight:600; color:#444; display:block; margin-bottom:4px;">Destinataire :</label>
                    <input type="text" id="m_devis_dest" readonly style="width:100%; padding:8px 12px; background:#f9f9f9; border:1px solid #ddd; border-radius:6px; font-size:0.85rem; color:#555;">
                </div>

                <div style="margin-bottom:12px;">
                    <label style="font-size:0.8rem; font-weight:600; color:#444; display:block; margin-bottom:4px;">Montant chiffré estimatif (FCFA) :</label>
                    <input type="text" name="montant_chiffre" id="m_devis_montant" placeholder="Ex: 850 000 FCFA TTC" style="width:100%; padding:9px 12px; border:1px solid #ddd; border-radius:6px; font-size:0.88rem;">
                </div>

                <div style="margin-bottom:18px;">
                    <label style="font-size:0.8rem; font-weight:600; color:#444; display:block; margin-bottom:4px;">Message &amp; Détail de l'offre commerciale :</label>
                    <textarea name="message_propos" id="m_devis_msg" rows="5" required style="width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-family:'Jost',sans-serif; font-size:0.85rem;"></textarea>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" onclick="closeProposalModal()" style="padding:9px 18px; border:1px solid #ddd; background:#fff; border-radius:6px; font-size:0.82rem; cursor:pointer;">Annuler</button>
                    <button type="submit" style="padding:9px 22px; border:none; background:var(--vert); color:var(--or); border-radius:6px; font-size:0.82rem; font-weight:600; cursor:pointer;">
                        <i class="fas fa-envelope"></i> Expédier par Email
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openProposalModal(d) {
        document.getElementById('m_devis_id').value = d.id;
        document.getElementById('m_propos_subtitle').textContent = `Dossier ${d.reference} · ${d.type_evenement} (${d.date_evenement})`;
        document.getElementById('m_devis_dest').value = `${d.nom_contact} <${d.email}>` + (d.entreprise ? ` (${d.entreprise})` : '');
        document.getElementById('m_devis_montant').value = d.budget_estime || '';
        document.getElementById('m_devis_msg').value = `Bonjour ${d.nom_contact},\n\nNous avons le plaisir de vous transmettre notre proposition personnalisée pour votre événement "${d.type_evenement}" prévu le ${d.date_evenement} à <?= addslashes(hotel_name()) ?>.\n\nNotre formule comprend la mise à disposition de l'espace "${d.espace_souhaite}", l'accueil personnalisé de vos ${d.nb_participants} participants ainsi que les prestations de restauration et d'équipements techniques dédiées.\n\nRestant à votre écoute pour organiser une visite des lieux ou ajuster cette offre selon vos souhaits.`;
        
        const modal = document.getElementById('proposalModal');
        modal.style.display = 'flex';
    }

    function closeProposalModal() {
        document.getElementById('proposalModal').style.display = 'none';
    }
    </script>

</body>
</html>
