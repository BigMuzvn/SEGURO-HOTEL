<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header('Location: ../pages/connexion-client.php');
    exit;
}

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/Reservation.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Mail.php';
require_once __DIR__ . '/../includes/AdminAuth.php';

AdminAuth::requireAccess('reservations');

$database = new Database();
$db       = $database->getConnection();
$reservation = new Reservation($db);

$erreur = '';
$succes = '';

// ════════════════════════════════════════════════════════
// TRAITEMENT POST
// ════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        if (isset($_POST['action']) && in_array($_POST['action'], ['note', 'changer_statut'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Jeton CSRF invalide ou session expirée.']);
            exit;
        }
        $erreur = "Session de sécurité expirée ou jeton CSRF invalide. Veuillez recharger la page.";
    } else {
        // AJAX : note admin
    if (($_POST['action'] ?? '') === 'note') {
        header('Content-Type: application/json');
        $id   = trim($_POST['id']   ?? '');
        $note = trim($_POST['note'] ?? '');
        try {
            $db->prepare("UPDATE reservations SET note_admin=? WHERE id=?")->execute([$note, $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            error_log("note admin error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
        }
        exit;
    }

    // AJAX : changer statut depuis le modal
    if (($_POST['action'] ?? '') === 'changer_statut') {
        header('Content-Type: application/json');
        $id     = trim($_POST['id']     ?? '');
        $statut = trim($_POST['statut'] ?? '');
        $allowed = ['validee', 'en_sejour', 'terminee', 'annulee', 'en_cours'];
        if (!$id || !in_array($statut, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Paramètres invalides.']);
            exit;
        }
        try {
            if ($reservation->getById($id)) {
                $u = new User($db);
                $clientExiste = $u->getById($reservation->user_id);
                $ch = $db->prepare("SELECT nom FROM chambres WHERE id = ?");
                $ch->execute([$reservation->chambre_id]);
                $chNom = $ch->fetchColumn() ?: 'Chambre SEGURO';

                if ($statut === 'en_sejour') {
                    $reservation->checkIn($_SESSION['user_id'] ?? null);
                } elseif ($statut === 'terminee') {
                    $reservation->checkOut($_SESSION['user_id'] ?? null);
                    // Déclencher l'invitation d'avis 5 étoiles par email
                    if ($clientExiste) {
                        try {
                            Mail::sendReviewInvitation($u->email, $u->prenom . ' ' . $u->nom, $reservation->reference, $chNom);
                        } catch (Exception $e) {
                            error_log("Review invitation mail error: " . $e->getMessage());
                        }
                    }
                } elseif ($statut === 'validee') {
                    $reservation->validate($_SESSION['user_id'] ?? null);
                    if ($clientExiste) {
                        try {
                            Mail::sendStatusUpdate($u->email, $u->prenom . ' ' . $u->nom, $reservation->reference, 'validee', $chNom, $reservation->note_admin);
                        } catch (Exception $e) {}
                    }
                } elseif ($statut === 'annulee') {
                    $reservation->cancel();
                    if ($clientExiste) {
                        try {
                            Mail::sendStatusUpdate($u->email, $u->prenom . ' ' . $u->nom, $reservation->reference, 'annulee', $chNom, $reservation->note_admin);
                        } catch (Exception $e) {}
                    }
                } else {
                    $db->prepare("UPDATE reservations SET statut=?, updated_at=NOW() WHERE id=?")->execute([$statut, $id]);
                }

                echo json_encode(['success' => true, 'statut' => $statut]);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Réservation introuvable.']);
                exit;
            }
        } catch (Exception $e) {
            error_log("changer_statut error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
            exit;
        }
    }

    // Formulaire classique (tableau)
    $rid = trim($_POST['reservation_id'] ?? '');
    if ($rid) {
        if (isset($_POST['action_valider'])) {
            try {
                $s = $db->prepare("UPDATE reservations SET statut='validee', valide_par=?, valide_at=NOW(), updated_at=NOW() WHERE id=? AND statut IN ('en_cours','modifiee')");
                $s->execute([$_SESSION['user_id'] ?? null, $rid]);
                $succes = $s->rowCount() > 0 ? 'Réservation validée.' : 'Déjà traitée ou introuvable.';

                if ($s->rowCount() > 0 && $reservation->getById($rid)) {
                    $u = new User($db);
                    if ($u->getById($reservation->user_id)) {
                        $ch = $db->prepare("SELECT nom FROM chambres WHERE id = ?");
                        $ch->execute([$reservation->chambre_id]);
                        $chNom = $ch->fetchColumn() ?: 'Chambre SEGURO';
                        Mail::sendStatusUpdate($u->email, $u->prenom . ' ' . $u->nom, $reservation->reference, 'validee', $chNom, $reservation->note_admin);
                    }
                }
            } catch (PDOException $e) { error_log("valider resa: " . $e->getMessage()); $erreur = 'Erreur serveur.'; }
        }
        if (isset($_POST['action_annuler'])) {
            try {
                $s = $db->prepare("UPDATE reservations SET statut='annulee', updated_at=NOW() WHERE id=? AND statut NOT IN ('terminee','annulee')");
                $s->execute([$rid]);
                $succes = $s->rowCount() > 0 ? 'Réservation annulée.' : 'Déjà annulée ou introuvable.';

                if ($s->rowCount() > 0 && $reservation->getById($rid)) {
                    $u = new User($db);
                    if ($u->getById($reservation->user_id)) {
                        $ch = $db->prepare("SELECT nom FROM chambres WHERE id = ?");
                        $ch->execute([$reservation->chambre_id]);
                        $chNom = $ch->fetchColumn() ?: 'Chambre SEGURO';
                        Mail::sendStatusUpdate($u->email, $u->prenom . ' ' . $u->nom, $reservation->reference, 'annulee', $chNom, $reservation->note_admin);
                    }
                }
            } catch (PDOException $e) { error_log("annuler resa: " . $e->getMessage()); $erreur = 'Erreur serveur.'; }
        }
    }
}
}

// ════════════════════════════════════════════════════════
// DONNÉES
// ════════════════════════════════════════════════════════
$statut_filter      = $_GET['statut'] ?? '';
$reservations       = $reservation->getAll($statut_filter ?: null, 500);
$page               = max(1, intval($_GET['page'] ?? 1));
$per_page           = 20;
$total_reservations = count($reservations);
$total_pages        = ceil($total_reservations / $per_page);
$reservations       = array_slice($reservations, ($page - 1) * $per_page, $per_page);

// Enrichissement avec les options, promo et paiements
foreach ($reservations as &$r) {
    $r['options_details'] = $reservation->getOptions($r['id']);
    if (!empty($r['code_promo_id'])) {
        $cpStmt = $db->prepare("SELECT code, type_reduction, valeur FROM codes_promo WHERE id = ?");
        $cpStmt->execute([$r['code_promo_id']]);
        $r['promo_info'] = $cpStmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $r['promo_info'] = null;
    }
    $payStmt = $db->prepare("SELECT * FROM paiements WHERE reservation_id = ? ORDER BY created_at DESC");
    $payStmt->execute([$r['id']]);
    $r['paiements'] = $payStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($r);

$stats = [
    'total'     => $reservation->count(),
    'en_cours'  => $reservation->countByStatut('en_cours'),
    'validee'   => $reservation->countByStatut('validee'),
    'en_sejour' => $reservation->countByStatut('en_sejour'),
    'modifiee'  => $reservation->countByStatut('modifiee'),
    'annulee'   => $reservation->countByStatut('annulee'),
    'terminee'  => $reservation->countByStatut('terminee'),
];

$statuts_labels = [
    'en_cours'  => 'En cours', 
    'validee'   => 'Validée',
    'en_sejour' => 'En séjour 🏨',
    'modifiee'  => 'Modifiée', 
    'annulee'   => 'Annulée', 
    'terminee'  => 'Terminée ✓',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservations — Hôtel Seguro</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root{--vert:#1a3a2a;--vert-clair:#2d5c40;--or:#c9a84c;--blanc:#faf8f3;--gris:#f5f5f5;--gris-fonce:#666;--danger:#dc3545;--success:#28a745;}
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Jost',sans-serif;background:var(--gris);color:var(--vert);display:flex;min-height:100vh;}
        /* ── Sidebar ── */
        .sidebar {
            width: 260px;
            background: var(--vert);
            color: var(--blanc);
            position: fixed;
            top: 0; bottom: 0; left: 0;
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
            padding: 6px 18px 2px;
            font-size: 0.58rem;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: rgba(250,248,243,0.4);
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
            background: rgba(201,168,76,0.1);
            color: var(--or);
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
        .logout-btn{display:flex;align-items:center;gap:8px;margin-top:12px;padding:8px 0;color:rgba(250,248,243,.6);text-decoration:none;font-size:.8rem;transition:color .3s;}
        .logout-btn:hover{color:var(--or);}
        .main-content{flex:1;margin-left:260px;padding:30px;}
        .top-bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;padding-bottom:20px;border-bottom:1px solid #e0e0e0;}
        .page-title h1{font-family:'Cormorant Garamond',serif;font-size:1.8rem;font-weight:400;}
        .page-title p{font-size:.85rem;color:var(--gris-fonce);margin-top:4px;}
        .filter-select{padding:10px 16px;border:1px solid #e0e0e0;border-radius:4px;font-size:.9rem;background:#fff;cursor:pointer;}
        .alert{padding:14px 20px;border-radius:6px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:.9rem;}
        .alert-success{background:rgba(40,167,69,.1);color:var(--success);border:1px solid rgba(40,167,69,.2);}
        .alert-danger{background:rgba(220,53,69,.1);color:var(--danger);border:1px solid rgba(220,53,69,.2);}
        .stats-bar{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;}
        .stat-filter{background:#fff;padding:16px 20px;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.06);text-align:center;cursor:pointer;transition:all .3s;border:2px solid transparent;}
        .stat-filter:hover,.stat-filter.active{border-color:var(--or);transform:translateY(-2px);}
        .stat-filter-value{font-size:1.5rem;font-weight:600;color:var(--vert);}
        .stat-filter-label{font-size:.8rem;color:var(--gris-fonce);margin-top:4px;}
        .card{background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;}
        .card-header{padding:20px 24px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;}
        .card-title{font-family:'Cormorant Garamond',serif;font-size:1.2rem;font-weight:600;color:var(--vert);}
        table{width:100%;border-collapse:collapse;}
        th{text-align:left;padding:14px 20px;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:var(--gris-fonce);font-weight:500;background:#fafafa;border-bottom:1px solid #f0f0f0;}
        td{padding:14px 20px;font-size:.88rem;border-bottom:1px solid #f8f8f8;vertical-align:middle;}
        tr:hover td{background:#fafafa;}
        .badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:.75rem;font-weight:500;}
        .badge-warning{background:rgba(255,193,7,.15);color:#856404;}
        .badge-success{background:rgba(40,167,69,.15);color:#155724;}
        .badge-danger{background:rgba(220,53,69,.15);color:#721c24;}
        .badge-info{background:rgba(23,162,184,.15);color:#0c5460;}
        .badge-modif{background:rgba(102,16,242,.12);color:#4a0080;}
        .actions{display:flex;gap:6px;flex-wrap:wrap;}
        .btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:4px;font-size:.82rem;transition:all .25s;cursor:pointer;border:none;font-family:'Jost',sans-serif;}
        .btn-primary{background:var(--or);color:var(--vert);}
        .btn-primary:hover{background:#b8962a;}
        .btn-success{background:var(--success);color:#fff;}
        .btn-success:hover{background:#218838;}
        .btn-danger{background:var(--danger);color:#fff;}
        .btn-danger:hover{background:#c82333;}
        .btn-sm{padding:5px 10px;font-size:.75rem;}
        .pagination{display:flex;justify-content:center;gap:8px;padding:20px;}
        .pagination a{padding:8px 16px;border:1px solid #e0e0e0;border-radius:4px;text-decoration:none;color:var(--vert);transition:all .3s;}
        .pagination a:hover,.pagination a.active{background:var(--or);color:var(--vert);border-color:var(--or);}
        /* Modal */
        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9000;align-items:center;justify-content:center;}
        .modal-overlay.open{display:flex;}
        .modal-box{background:#fff;border-radius:16px;padding:36px;width:600px;max-width:95vw;position:relative;box-shadow:0 20px 60px rgba(0,0,0,.25);max-height:90vh;overflow-y:auto;}
        .modal-close{position:absolute;top:16px;right:16px;background:none;border:none;font-size:22px;cursor:pointer;color:#aaa;}
        .modal-close:hover{color:#333;}
        .modal-title{font-family:'Cormorant Garamond',serif;color:var(--vert);font-size:1.6rem;font-weight:600;margin-bottom:4px;}
        .modal-ref{font-size:.85rem;color:var(--or);font-weight:600;margin-bottom:6px;letter-spacing:.05em;}
        .modal-statut-badge{margin-bottom:24px;}
        .section-title{font-size:.72rem;text-transform:uppercase;letter-spacing:.12em;color:#bbb;font-weight:600;margin:20px 0 10px;}
        .detail-row{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid #f4f4f4;font-size:.9rem;}
        .detail-row:last-child{border-bottom:none;}
        .detail-label{color:#999;}
        .detail-val{font-weight:500;color:#222;}
        .note-area{width:100%;min-height:72px;padding:10px 12px;border:1px solid #ddd;border-radius:6px;font-family:'Jost',sans-serif;font-size:.88rem;resize:vertical;margin-top:8px;}
        .note-area:focus{outline:none;border-color:var(--or);}
        .modal-footer{display:flex;gap:10px;align-items:center;justify-content:space-between;margin-top:24px;padding-top:20px;border-top:1px solid #f0f0f0;}
        .modal-footer-left{display:flex;gap:8px;}
        .note-ok{font-size:.8rem;color:var(--success);display:none;}
        @media(max-width:768px){.sidebar{width:70px;}.sidebar-logo span,.nav-item span,.nav-section,.admin-details{display:none;}.main-content{margin-left:70px;}.stats-bar{grid-template-columns:repeat(2,1fr);}}
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-logo"><i class="fas fa-crown"></i><span>Hôtel Seguro</span></a>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Principal</div>
        <?php if (AdminAuth::can('dashboard')): ?>
            <a href="dashboard.php" class="nav-item"><i class="fas fa-chart-line"></i><span>Dashboard</span></a>
        <?php endif; ?>
        <?php if (AdminAuth::can('calendrier')): ?>
            <a href="calendrier.php" class="nav-item"><i class="fas fa-calendar-alt"></i><span>Calendrier</span></a>
        <?php endif; ?>
        
        <div class="nav-section" style="margin-top:14px;">Gestion</div>
        <?php if (AdminAuth::can('reservations')): ?>
            <a href="reservations.php" class="nav-item active"><i class="fas fa-book"></i><span>Réservations</span></a>
        <?php endif; ?>
        <?php if (AdminAuth::can('room_service')): ?>
            <a href="room-service.php" class="nav-item"><i class="fas fa-concierge-bell"></i><span>Room Service</span></a>
        <?php endif; ?>
        <?php if (AdminAuth::can('evenements')): ?>
            <a href="evenements.php" class="nav-item"><i class="fas fa-glass-cheers"></i><span>Devis Événements</span></a>
        <?php endif; ?>
        <?php if (AdminAuth::can('clients')): ?>
            <a href="clients.php" class="nav-item"><i class="fas fa-users"></i><span>Clients</span></a>
        <?php endif; ?>
        <?php if (AdminAuth::can('chambres')): ?>
            <a href="chambres.php" class="nav-item"><i class="fas fa-bed"></i><span>Chambres</span></a>
        <?php endif; ?>
        <?php if (AdminAuth::can('avis')): ?>
            <a href="avis.php" class="nav-item"><i class="fas fa-star"></i><span>Avis Clients</span></a>
        <?php endif; ?>
        <?php if (AdminAuth::can('codes_promo')): ?>
            <a href="codes-promo.php" class="nav-item"><i class="fas fa-tags"></i><span>Codes Promo</span></a>
        <?php endif; ?>
        <?php if (AdminAuth::can('profil')): ?>
            <a href="profil.php" class="nav-item"><i class="fas fa-user-shield"></i><span>Équipe &amp; Profil</span></a>
        <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-info">
            <div class="admin-avatar"><?php echo strtoupper(substr($_SESSION['user_prenom'] ?? 'A', 0, 1)); ?></div>
            <div class="admin-details">
                <h4><?php echo htmlspecialchars(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? '')); ?></h4>
                <p><?= ($_SESSION['user_role'] ?? '') === 'super_admin' ? 'Super Administrateur' : 'Administrateur' ?></p>
            </div>
        </div>
        <a href="../pages/deconnexion.php" class="logout-btn" style="display:flex; align-items:center; gap:8px; margin-top:10px; color:rgba(250,248,243,0.6); text-decoration:none; font-size:0.75rem;">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</aside>

<main class="main-content">
    <div class="top-bar">
        <div class="page-title"><h1>Réservations</h1><p>Gestion de toutes les réservations</p></div>
        <div style="display:flex; gap:10px; align-items:center;">
            <a href="export-reservations.php<?= $statut_filter ? '?statut='.urlencode($statut_filter) : '' ?>" class="btn btn-primary" style="text-decoration:none; background:var(--vert); color:var(--or); border:1px solid var(--or); display:inline-flex; align-items:center; gap:8px;">
                <i class="fas fa-file-excel"></i> Exporter en CSV
            </a>
            <form method="GET">
                <select name="statut" class="filter-select" onchange="this.form.submit()">
                    <option value="">Tous les statuts</option>
                    <option value="en_cours" <?= $statut_filter==='en_cours'?'selected':'' ?>>En cours</option>
                    <option value="validee"  <?= $statut_filter==='validee' ?'selected':'' ?>>Validées</option>
                    <option value="modifiee" <?= $statut_filter==='modifiee'?'selected':'' ?>>Modifiées</option>
                    <option value="annulee"  <?= $statut_filter==='annulee' ?'selected':'' ?>>Annulées</option>
                    <option value="terminee" <?= $statut_filter==='terminee'?'selected':'' ?>>Terminées</option>
                </select>
            </form>
        </div>
    </div>

    <?php if ($succes): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i><?= $succes ?></div><?php endif; ?>
    <?php if ($erreur): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><?= $erreur ?></div><?php endif; ?>

    <div class="stats-bar">
        <?php foreach ([
            [''         , $stats['total'],    'Total'],
            ['en_cours' , $stats['en_cours'], 'En cours'],
            ['validee'  , $stats['validee'],  'Validées'],
            ['modifiee' , $stats['modifiee'], 'Modifiées'],
            ['annulee'  , $stats['annulee'],  'Annulées'],
            ['terminee' , $stats['terminee'], 'Terminées'],
        ] as [$val, $nb, $lbl]): ?>
        <div class="stat-filter <?= $statut_filter===$val?'active':'' ?>"
             onclick="location.href='reservations.php<?= $val ? '?statut='.$val : '' ?>'">
            <div class="stat-filter-value"><?= $nb ?></div>
            <div class="stat-filter-label"><?= $lbl ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-book" style="color:var(--or);margin-right:8px;"></i>Liste des réservations</h3>
            <span class="badge badge-info"><?= $total_reservations ?> réservations</span>
        </div>
        <div style="overflow-x:auto;">
            <table>
                <thead><tr>
                    <th>Référence</th><th>Client</th><th>Chambre</th>
                    <th>Dates</th><th>Montant</th><th>Statut</th><th>Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($reservations as $res):
                    $bc = match($res['statut']) {
                        'en_cours'=>'badge-warning','validee'=>'badge-success',
                        'annulee'=>'badge-danger','modifiee'=>'badge-modif',default=>'badge-info'
                    };
                ?>
                <tr id="row-<?= $res['id'] ?>">
                    <td><strong><?= htmlspecialchars($res['reference']) ?></strong></td>
                    <td><?= htmlspecialchars(($res['user_nom']??'').' '.($res['user_prenom']??'')) ?></td>
                    <td><?= htmlspecialchars($res['chambre_nom']??'—') ?></td>
                    <td><?= date('d/m/Y',strtotime($res['date_arrivee'])).' → '.date('d/m/Y',strtotime($res['date_depart'])) ?></td>
                    <td style="color:var(--or);font-weight:500;"><?= number_format($res['prix_total'],0,',',' ') ?> FCFA</td>
                    <td><span class="badge <?= $bc ?>" id="badge-<?= $res['id'] ?>"><?= $statuts_labels[$res['statut']]??$res['statut'] ?></span></td>
                    <td>
                        <div class="actions">
                            <button type="button" class="btn btn-primary btn-sm" title="Voir / Agir"
                                onclick='ouvrirModal(<?= json_encode($res, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                                <i class="fas fa-eye"></i> Voir
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i=1;$i<=$total_pages;$i++): ?>
            <a href="?page=<?= $i ?><?= $statut_filter?'&statut='.urlencode($statut_filter):'' ?>"
               class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- ══ MODAL ══════════════════════════════════════════════ -->
<div id="modalVoir" class="modal-overlay">
  <div class="modal-box">
    <button class="modal-close" onclick="fermerModal()">✕</button>

    <div class="modal-title" id="mRef"></div>
    <div class="modal-ref"   id="mRefCode"></div>
    <div class="modal-statut-badge"><span class="badge" id="mBadge"></span></div>

    <div class="section-title">🏨 Séjour</div>
    <div class="detail-row"><span class="detail-label">Chambre</span><span class="detail-val" id="mChambre"></span></div>
    <div class="detail-row"><span class="detail-label">Arrivée</span><span class="detail-val" id="mArrivee"></span></div>
    <div class="detail-row"><span class="detail-label">Départ</span><span class="detail-val" id="mDepart"></span></div>
    <div class="detail-row"><span class="detail-label">Adultes / Enfants</span><span class="detail-val" id="mPers"></span></div>

    <div class="section-title">✨ Options & Services de séjour</div>
    <div id="mOptionsList" style="background:#faf8f3; border:1px solid #e8e3d6; border-radius:6px; padding:10px 14px; margin-bottom:12px; font-size:0.88rem; line-height:1.6;"></div>

    <div class="section-title">💰 Tarifs & Règlement</div>
    <div class="detail-row"><span class="detail-label">Prix / nuit</span><span class="detail-val" id="mNuit"></span></div>
    <div class="detail-row"><span class="detail-label">Sous-total options</span><span class="detail-val" id="mOpts"></span></div>
    <div class="detail-row" id="mRowPromo" style="display:none;"><span class="detail-label" style="color:#28a745;">Code Promo</span><span class="detail-val" id="mPromo" style="color:#28a745; font-weight:600;"></span></div>
    <div class="detail-row"><span class="detail-label">Total Net</span><span class="detail-val" id="mTotal" style="color:var(--or);font-weight:700;font-size:1.1rem;"></span></div>
    <div class="detail-row"><span class="detail-label">Paiement</span><span class="detail-val" id="mStatutPaiement"></span></div>

    <div class="section-title">👤 Client</div>
    <div class="detail-row"><span class="detail-label">Nom</span><span class="detail-val" id="mClient"></span></div>
    <div class="detail-row"><span class="detail-label">Email</span><span class="detail-val" id="mEmail"></span></div>

    <div class="section-title">📝 Demandes spéciales</div>
    <div id="mDemandes" style="font-size:.88rem;color:#666;padding:4px 0 8px;"></div>

    <div class="section-title">🔒 Note interne admin</div>
    <textarea id="mNote" class="note-area" placeholder="Ajouter une note…"></textarea>
    <div style="display:flex;justify-content:flex-end;margin-top:6px;gap:10px;align-items:center;">
        <span id="noteOk" class="note-ok">✓ Enregistré</span>
        <button class="btn btn-primary btn-sm" onclick="sauvegarderNote()">Enregistrer la note</button>
    </div>

    <!-- ACTIONS — toujours visibles, adaptées au statut -->
    <div class="modal-footer">
        <div class="modal-footer-left" id="mBtns"></div>
        <button class="btn" style="border:1px solid #ddd;" onclick="fermerModal()">Fermer</button>
    </div>
  </div>
</div>

<script>
var RES = null;
var STATUTS = {en_cours:'En cours',validee:'Validée',modifiee:'Modifiée',annulee:'Annulée',terminee:'Terminée'};
var BADGE_CSS = {en_cours:'badge-warning',validee:'badge-success',annulee:'badge-danger',modifiee:'badge-modif',terminee:'badge-info'};

function fmt(n){ return parseFloat(n||0).toLocaleString('fr-FR')+' FCFA'; }
function fdate(s){ if(!s)return'—'; var p=s.substring(0,10).split('-'); return p[2]+'/'+p[1]+'/'+p[0]; }

function ouvrirModal(r) {
    RES = r;
    document.getElementById('mRef').textContent     = 'Réservation';
    document.getElementById('mRefCode').textContent = r.reference || '';
    var badge = document.getElementById('mBadge');
    badge.textContent = STATUTS[r.statut] || r.statut;
    badge.className   = 'badge ' + (BADGE_CSS[r.statut]||'badge-info');

    document.getElementById('mChambre').textContent = r.chambre_nom || '—';
    document.getElementById('mArrivee').textContent = fdate(r.date_arrivee);
    document.getElementById('mDepart').textContent  = fdate(r.date_depart);
    document.getElementById('mPers').textContent    = (r.nb_adultes||0)+' adulte(s) / '+(r.nb_enfants||0)+' enfant(s)';
    document.getElementById('mNuit').textContent    = fmt(r.prix_nuit);
    document.getElementById('mOpts').textContent    = fmt(r.prix_options);
    document.getElementById('mTotal').textContent   = fmt(r.prix_total);
    document.getElementById('mClient').textContent  = (r.user_nom||'') + ' ' + (r.user_prenom||'');
    document.getElementById('mEmail').textContent   = r.user_email || '—';
    document.getElementById('mDemandes').textContent = r.demandes_speciales || 'Aucune';
    document.getElementById('mNote').value          = r.note_admin || '';
    document.getElementById('noteOk').style.display = 'none';

    // Rendu des options de séjour
    var optsDiv = document.getElementById('mOptionsList');
    if (r.options_details && r.options_details.length > 0) {
        var htmlOpts = '';
        r.options_details.forEach(function(opt) {
            htmlOpts += '<div>• <strong>' + (opt.nom||'') + '</strong> : ' + fmt(opt.prix_unitaire) + ' (' + (opt.unite||'') + ', qté: ' + opt.quantite + ')</div>';
        });
        optsDiv.innerHTML = htmlOpts;
        optsDiv.style.display = 'block';
    } else {
        optsDiv.innerHTML = '<span style="color:#888;">Aucune option de séjour sélectionnée</span>';
        optsDiv.style.display = 'block';
    }

    // Rendu Code Promo
    var rowPromo = document.getElementById('mRowPromo');
    var spanPromo = document.getElementById('mPromo');
    if (r.montant_reduction && parseFloat(r.montant_reduction) > 0) {
        var promoCodeNom = (r.promo_info && r.promo_info.code) ? r.promo_info.code : 'Appliqué';
        spanPromo.textContent = promoCodeNom + ' (-' + fmt(r.montant_reduction) + ')';
        rowPromo.style.display = 'flex';
    } else {
        rowPromo.style.display = 'none';
    }

    // Rendu Paiement
    var spEl = document.getElementById('mStatutPaiement');
    var pStatut = r.statut_paiement || 'non_paye';
    if (pStatut === 'acompte_paye') {
        var pDetail = (r.paiements && r.paiements[0]) ? (' (' + fmt(r.paiements[0].montant) + ' via ' + r.paiements[0].moyen_paiement.toUpperCase() + ')') : '';
        spEl.innerHTML = '<span style="color:#28a745; font-weight:600;">✓ Acompte 30% réglé' + pDetail + '</span>';
    } else if (pStatut === 'totalement_paye') {
        var pDetail = (r.paiements && r.paiements[0]) ? (' (' + fmt(r.paiements[0].montant) + ' via ' + r.paiements[0].moyen_paiement.toUpperCase() + ')') : '';
        spEl.innerHTML = '<span style="color:#28a745; font-weight:600;">✓ Totalité 100% réglée' + pDetail + '</span>';
    } else {
        spEl.innerHTML = '<span style="color:#e67e22; font-weight:500;">Paiement à l\'arrivée (sur place)</span>';
    }

    // ── Boutons d'action selon statut ──────────────────
    var btns = document.getElementById('mBtns');
    btns.innerHTML = '<a href="../pages/facture.php?id=' + r.id + '" target="_blank" class="btn" style="background:#f4efe6; color:var(--vert); border:1px solid var(--or); text-decoration:none;"><i class="fas fa-file-invoice"></i> Voir Facture</a>';

    if (r.statut === 'en_cours' || r.statut === 'modifiee') {
        btns.innerHTML +=
            '<button class="btn btn-success" onclick="changerStatut(\'validee\')">'
          + '<i class="fas fa-check"></i> Valider</button>'
          + '<button class="btn btn-danger" onclick="changerStatut(\'annulee\')">'
          + '<i class="fas fa-times"></i> Annuler</button>';
    }
    if (r.statut === 'validee') {
        btns.innerHTML +=
            '<button class="btn" style="background:#1a3a2a; color:#c9a84c; border:1px solid #c9a84c;" onclick="changerStatut(\'en_sejour\')">'
          + '<i class="fas fa-key"></i> 🛎️ Check-in (Client Arrivé)</button>'
          + '<button class="btn btn-danger" onclick="changerStatut(\'annulee\')">'
          + '<i class="fas fa-times"></i> Annuler</button>';
    }
    if (r.statut === 'en_sejour') {
        btns.innerHTML +=
            '<button class="btn btn-success" onclick="changerStatut(\'terminee\')">'
          + '<i class="fas fa-door-open"></i> 🚪 Check-out (Départ & Inviter Avis)</button>';
    }
    if (r.statut === 'annulee') {
        btns.innerHTML +=
            '<button class="btn btn-success" onclick="changerStatut(\'validee\')">'
          + '<i class="fas fa-undo"></i> Remettre en valide</button>';
    }

    document.getElementById('modalVoir').classList.add('open');
}

function fermerModal() {
    document.getElementById('modalVoir').classList.remove('open');
    RES = null;
}

document.getElementById('modalVoir').addEventListener('click', function(e){
    if(e.target===this) fermerModal();
});

function changerStatut(nouveauStatut) {
    if (!RES) return;
    var msg = nouveauStatut === 'validee' ? 'Valider' : (nouveauStatut === 'annulee' ? 'Annuler' : 'Modifier');
    if (!confirm(msg + ' cette réservation ?')) return;

    var data = new FormData();
    data.append('action', 'changer_statut');
    data.append('csrf_token', '<?= csrf_token() ?>');
    data.append('id',     RES.id);
    data.append('statut', nouveauStatut);

    fetch(window.location.pathname, {method:'POST', body:data})
        .then(function(r){ return r.json(); })
        .then(function(res) {
            if (res.success) {
                // Mettre à jour le badge dans le tableau
                var b = document.getElementById('badge-' + RES.id);
                if (b) {
                    b.textContent = {en_cours:'En cours',validee:'Validée',annulee:'Annulée'}[nouveauStatut] || nouveauStatut;
                    b.className = 'badge ' + ({en_cours:'badge-warning',validee:'badge-success',annulee:'badge-danger'}[nouveauStatut]||'badge-info');
                }
                // Mettre à jour le modal
                RES.statut = nouveauStatut;
                ouvrirModal(RES);
            } else {
                alert('Erreur : ' + (res.message || 'Inconnue'));
            }
        })
        .catch(function(){ alert('Erreur réseau.'); });
}

function sauvegarderNote() {
    if (!RES) return;
    var data = new FormData();
    data.append('action', 'note');
    data.append('csrf_token', '<?= csrf_token() ?>');
    data.append('id',     RES.id);
    data.append('note',   document.getElementById('mNote').value);

    fetch(window.location.pathname, {method:'POST', body:data})
        .then(function(r){ return r.json(); })
        .then(function(res){
            if (res.success) {
                var ok = document.getElementById('noteOk');
                ok.style.display = 'inline';
                setTimeout(function(){ ok.style.display='none'; }, 2500);
            }
        });
}
</script>

</body>
</html>