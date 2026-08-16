<?php
/**
 * ════════════════════════════════════════════════════════
 * MODÉRATION DES AVIS CLIENTS
 * ════════════════════════════════════════════════════════
 */

session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin'])) {
    header('Location: ../pages/connexion-client.php');
    exit;
}

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/Avis.php';
require_once __DIR__ . '/../includes/AdminAuth.php';

AdminAuth::requireAccess('avis');

$database = new Database();
$db = $database->getConnection();
$avisModel = new Avis($db);

$message = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        $erreur = "Session de sécurité expirée ou jeton CSRF invalide. Veuillez recharger la page.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'moderer') {
            $id = trim($_POST['id'] ?? '');
            $statut = trim($_POST['statut'] ?? 'publie');
            $reponse = trim($_POST['reponse_hotel'] ?? '');

            if ($id && $avisModel->moderer($id, $statut, $reponse ?: null)) {
                $message = "Avis et réponse enregistrés avec succès !";
            } else {
                $erreur = "Erreur lors de la modération de l'avis.";
            }
        }

        if ($action === 'supprimer') {
            $id = trim($_POST['id'] ?? '');
            if ($id && $avisModel->delete($id)) {
                $message = "Avis supprimé avec succès.";
            } else {
                $erreur = "Erreur lors de la suppression de l'avis.";
            }
        }
    }
}

$tousAvis = $avisModel->getAllForAdmin();
$statut_filtre = $_GET['statut'] ?? '';
if ($statut_filtre) {
    $tousAvis = array_filter($tousAvis, function($a) use ($statut_filtre) {
        return $a['statut'] === $statut_filtre;
    });
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avis &amp; Évaluations — Administration <?= htmlspecialchars(hotel_name()) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <?= hotel_theme_css() ?>
    <style>
        :root {
            --vert: #1a3a2a;
            --vert-clair: #2d5c40;
            --vert-fonce: #0f2418;
            --or: #c9a84c;
            --or-clair: #e0c068;
            --blanc: #faf8f3;
            --gris: #f5f5f5;
            --gris-fonce: #666;
            --danger: #dc3545;
            --success: #28a745;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Jost', sans-serif;
            background: var(--gris);
            color: var(--vert);
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar Standard ── */
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

        /* ── Main Content ── */
        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 30px 40px;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .page-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2rem;
            color: var(--vert);
        }
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            padding: 24px;
            margin-bottom: 24px;
        }
        .btn-primary {
            background: var(--or);
            color: var(--vert-fonce);
            padding: 10px 22px;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
            font-family: 'Jost', sans-serif;
        }
        .btn-primary:hover { background: var(--or-clair); }
        .btn-sm { padding: 6px 14px; font-size: 0.85rem; }
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
        }
        .badge-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .badge-warning { background: #fff8e1; color: #f57f17; border: 1px solid #ffe082; }
        .badge-danger { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #eee; font-size: 0.92rem; }
        th { background: #faf8f3; color: var(--vert); font-weight: 600; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; }
        
        /* ── Modal ── */
        .modal {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            padding: 20px;
        }
        .modal-content {
            background: white;
            padding: 32px;
            border-radius: 12px;
            width: 100%;
            max-width: 580px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.25);
            position: relative;
        }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-weight: 500; margin-bottom: 6px; font-size: 0.9rem; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit; font-size: 0.9rem; }
        .form-control:focus { outline: none; border-color: var(--or); }
        .alert { padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }

        @media(max-width:768px) {
            .sidebar { width: 70px; }
            .sidebar-logo span, .nav-item span, .nav-section, .admin-details { display: none; }
            .main-content { margin-left: 70px; padding: 20px; }
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-logo"><i class="fas fa-crown"></i><span><?= htmlspecialchars(hotel_short_name()) ?></span></a>
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
                <a href="reservations.php" class="nav-item"><i class="fas fa-book"></i><span>Réservations</span></a>
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
                <a href="avis.php" class="nav-item active"><i class="fas fa-star"></i><span>Avis Clients</span></a>
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
                <div class="admin-avatar"><?= strtoupper(substr($_SESSION['user_prenom'] ?? 'A', 0, 1)) ?></div>
                <div class="admin-details">
                    <h4><?= htmlspecialchars(($_SESSION['user_prenom'] ?? 'Admin') . ' ' . ($_SESSION['user_nom'] ?? '')) ?></h4>
                    <p><?= ($_SESSION['user_role'] ?? '') === 'super_admin' ? 'Super Administrateur' : 'Administrateur' ?></p>
                </div>
            </div>
            <a href="../pages/deconnexion.php" class="logout-btn" style="display:flex; align-items:center; gap:8px; margin-top:10px; color:rgba(250,248,243,0.6); text-decoration:none; font-size:0.75rem;">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="topbar">
            <div>
                <h1 class="page-title">Modération des Avis Clients</h1>
                <p style="color:var(--gris-fonce); font-size:0.9rem;">Gérez les notes, témoignages et réponses officielles de l'hôtel</p>
            </div>
            <div style="display:flex; gap:8px;">
                <a href="?statut=" class="btn-primary btn-sm" style="<?= empty($statut_filtre)?'':'background:#eee;color:#333;' ?>">Tous</a>
                <a href="?statut=publie" class="btn-primary btn-sm" style="<?= $statut_filtre==='publie'?'background:#2e7d32;color:#fff;':'background:#e8f5e9;color:#2e7d32;' ?>">Publiés</a>
                <a href="?statut=en_attente" class="btn-primary btn-sm" style="<?= $statut_filtre==='en_attente'?'background:#f57f17;color:#fff;':'background:#fff8e1;color:#f57f17;' ?>">En attente</a>
                <a href="?statut=refuse" class="btn-primary btn-sm" style="<?= $statut_filtre==='refuse'?'background:#c62828;color:#fff;':'background:#ffebee;color:#c62828;' ?>">Refusés</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($erreur): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3 style="font-family:'Cormorant Garamond', serif; font-size:1.4rem; margin-bottom:16px;">
                Témoignages enregistrés (<?= count($tousAvis) ?>)
            </h3>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date / Réf</th>
                            <th>Client &amp; Chambre</th>
                            <th>Note</th>
                            <th>Commentaire</th>
                            <th>Statut</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tousAvis)): ?>
                            <tr><td colspan="6" style="text-align:center; color:#888; padding:30px;">Aucun avis enregistré.</td></tr>
                        <?php else: ?>
                            <?php foreach ($tousAvis as $a): ?>
                            <tr>
                                <td>
                                    <strong><?= date('d/m/Y', strtotime($a['created_at'])) ?></strong><br>
                                    <span style="font-size:0.75rem; color:#888;"><?= htmlspecialchars($a['reservation_ref']) ?></span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($a['client_prenom'] . ' ' . $a['client_nom']) ?></strong><br>
                                    <span style="font-size:0.8rem; color:var(--vert);"><?= htmlspecialchars($a['chambre_nom']) ?></span>
                                </td>
                                <td>
                                    <div style="color:var(--or); font-size:0.95rem;">
                                        <?php for($i=1;$i<=5;$i++): ?>
                                            <i class="fas fa-star<?= $i <= (int)$a['note'] ? '' : '-o' ?>"></i>
                                        <?php endfor; ?>
                                        <span style="color:#333; font-weight:600; margin-left:4px;"><?= $a['note'] ?>/5</span>
                                    </div>
                                </td>
                                <td style="max-width:320px;">
                                    <?php if (!empty($a['titre'])): ?>
                                        <strong style="color:var(--vert); display:block; margin-bottom:3px;"><?= htmlspecialchars($a['titre']) ?></strong>
                                    <?php endif; ?>
                                    <span style="color:#555; font-size:0.85rem; font-style:italic;">
                                        "<?= htmlspecialchars($a['commentaire']) ?>"
                                    </span>
                                    <?php if (!empty($a['reponse_hotel'])): ?>
                                        <div style="margin-top:8px; font-size:0.8rem; color:var(--vert); background:#f7f5ee; padding:6px 10px; border-radius:4px; border-left:3px solid var(--or);">
                                            <strong><i class="fas fa-reply" style="color:var(--or);"></i> Réponse de l'hôtel :</strong> <?= htmlspecialchars($a['reponse_hotel']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($a['statut'] === 'publie'): ?>
                                        <span class="badge badge-success">Publié</span>
                                    <?php elseif ($a['statut'] === 'en_attente'): ?>
                                        <span class="badge badge-warning">En attente</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Refusé</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right; white-space:nowrap;">
                                    <button type="button" class="btn-primary btn-sm"
                                        data-id="<?= htmlspecialchars($a['id'], ENT_QUOTES) ?>"
                                        data-statut="<?= htmlspecialchars($a['statut'], ENT_QUOTES) ?>"
                                        data-client="<?= htmlspecialchars($a['client_prenom'] . ' ' . $a['client_nom'], ENT_QUOTES) ?>"
                                        data-note="<?= (int)$a['note'] ?>"
                                        data-commentaire="<?= htmlspecialchars($a['commentaire'], ENT_QUOTES) ?>"
                                        data-reponse="<?= htmlspecialchars($a['reponse_hotel'] ?? '', ENT_QUOTES) ?>"
                                        onclick="ouvrirModalReponse(this)">
                                        <i class="fas fa-reply"></i> <?= empty($a['reponse_hotel']) ? 'Répondre' : 'Modifier réponse' ?>
                                    </button>
                                    <form method="post" style="display:inline; margin-left:4px;" onsubmit="return confirm('Supprimer définitivement cet avis client ?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="supprimer">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($a['id']) ?>">
                                        <button type="submit" class="btn-primary btn-sm" style="background:#ffebee; color:#c62828;" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- MODAL RÉPONSE & MODÉRATION -->
    <div id="modalReponse" class="modal">
        <div class="modal-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                <h2 style="font-family:'Cormorant Garamond', serif; font-size:1.8rem; color:var(--vert);">
                    Modération &amp; Réponse Officielle
                </h2>
                <button type="button" onclick="fermerModal()" style="background:none; border:none; font-size:1.4rem; cursor:pointer; color:#888;">&times;</button>
            </div>
            
            <div id="avisDetailsBox" style="background:#faf8f3; border:1px solid #e5dfd0; border-radius:8px; padding:14px; margin-bottom:18px; font-size:0.88rem;"></div>

            <form method="post" action="avis.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="moderer">
                <input type="hidden" name="id" id="mod_id">

                <div class="form-group">
                    <label class="form-label">Statut de publication *</label>
                    <select name="statut" id="mod_statut" class="form-control" required>
                        <option value="publie">Publié (visible publiquement sur le site)</option>
                        <option value="en_attente">En attente de validation</option>
                        <option value="refuse">Refusé (non conforme)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Réponse officielle de l'établissement (affichée sous l'avis)</label>
                    <textarea name="reponse_hotel" id="mod_reponse" class="form-control" rows="5" placeholder="Ex: Cher(e) client(e), toute l'équipe de <?= htmlspecialchars(hotel_name()) ?> vous remercie chaleureusement pour votre retour élogieux..."></textarea>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px;">
                    <button type="button" class="btn-primary" style="background:#eee; color:#333;" onclick="fermerModal()">Annuler</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-paper-plane"></i> Enregistrer &amp; Publier la réponse</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function ouvrirModalReponse(btn) {
        var d = btn.dataset;
        document.getElementById('mod_id').value = d.id;
        document.getElementById('mod_statut').value = d.statut;
        document.getElementById('mod_reponse').value = d.reponse || '';
        
        var noteInt = parseInt(d.note) || 5;
        var starsHtml = '';
        for (var i = 1; i <= 5; i++) {
            starsHtml += '<i class="fas fa-star" style="color:' + (i <= noteInt ? '#c9a84c' : '#ddd') + '; font-size:12px;"></i>';
        }

        document.getElementById('avisDetailsBox').innerHTML = 
            '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">' +
                '<strong>' + escapeHtml(d.client) + '</strong>' +
                '<span>' + starsHtml + ' (' + noteInt + '/5)</span>' +
            '</div>' +
            '<div style="font-style:italic; color:#555; line-height:1.4;">"' + escapeHtml(d.commentaire) + '"</div>';
            
        document.getElementById('modalReponse').style.display = 'flex';
    }

    function fermerModal() {
        document.getElementById('modalReponse').style.display = 'none';
    }

    window.onclick = function(event) {
        var modal = document.getElementById('modalReponse');
        if (event.target === modal) fermerModal();
    };
    </script>
</body>
</html>
