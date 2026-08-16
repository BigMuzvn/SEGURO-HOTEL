<?php
/**
 * ════════════════════════════════════════════════════════
 * GESTION DES CODES PROMO — Administration Hôtel SEGURO
 * ════════════════════════════════════════════════════════
 */

session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin'])) {
    header('Location: ../pages/connexion-client.php');
    exit;
}

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/CodePromo.php';
require_once __DIR__ . '/../includes/AdminAuth.php';

AdminAuth::requireAccess('codes_promo');

$database = new Database();
$db = $database->getConnection();
$promoModel = new CodePromo($db);

$message = '';
$erreur = '';

// Actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        $erreur = "Session de sécurité expirée ou jeton CSRF invalide. Veuillez recharger la page.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'creer') {
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $type = $_POST['type_reduction'] ?? 'pourcentage';
            $valeur = (float)($_POST['valeur'] ?? 0);
            $date_expiration = !empty($_POST['date_expiration']) ? $_POST['date_expiration'] : null;
            $utilisations_max = !empty($_POST['utilisations_max']) ? (int)$_POST['utilisations_max'] : null;

            if (empty($code) || $valeur <= 0) {
                $erreur = 'Le code et la valeur de réduction sont obligatoires et doivent être valides.';
            } else {
                $ok = $promoModel->create([
                    'code' => $code,
                    'type_reduction' => $type,
                    'valeur' => $valeur,
                    'date_expiration' => $date_expiration,
                    'utilisations_max' => $utilisations_max,
                    'actif' => 1
                ]);
                if ($ok) {
                    $message = "Code promotionnel \"{$code}\" créé avec succès !";
                } else {
                    $erreur = "Erreur lors de la création du code (le code existe peut-être déjà).";
                }
            }
        }

        if ($action === 'toggle') {
            $id = $_POST['id'] ?? '';
            if ($promoModel->toggleActif($id)) {
                $message = "Statut du code promo modifié avec succès.";
            }
        }

        if ($action === 'supprimer') {
            $id = $_POST['id'] ?? '';
            if ($promoModel->delete($id)) {
                $message = "Code promotionnel supprimé avec succès.";
            }
        }
    }
}

$codes = $promoModel->getAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Codes Promo — Administration <?= htmlspecialchars(hotel_name()) ?></title>
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
            max-width: 520px;
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
                <a href="avis.php" class="nav-item"><i class="fas fa-star"></i><span>Avis Clients</span></a>
            <?php endif; ?>
            <?php if (AdminAuth::can('codes_promo')): ?>
                <a href="codes-promo.php" class="nav-item active"><i class="fas fa-tags"></i><span>Codes Promo</span></a>
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
                <h1 class="page-title">Gestion des Codes Promotionnels</h1>
                <p style="color:var(--gris-fonce); font-size:0.9rem;">Créez et suivez l'utilisation de vos offres et réductions</p>
            </div>
            <button class="btn-primary" onclick="ouvrirModalCreer()">
                <i class="fas fa-plus"></i> Nouveau Code Promo
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($erreur): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3 style="font-family:'Cormorant Garamond', serif; font-size:1.4rem; margin-bottom:16px;">
                Liste des codes disponibles (<?= count($codes) ?>)
            </h3>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Réduction</th>
                            <th>Expiration</th>
                            <th>Utilisations</th>
                            <th>Statut</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($codes)): ?>
                            <tr><td colspan="6" style="text-align:center; color:#888; padding:30px;">Aucun code promo créé.</td></tr>
                        <?php else: ?>
                            <?php foreach ($codes as $c): ?>
                            <tr>
                                <td>
                                    <strong style="color:var(--vert); font-size:1rem; letter-spacing:0.05em; background:rgba(201,168,76,0.1); padding:4px 10px; border-radius:4px; border:1px dashed var(--or);">
                                        <?= htmlspecialchars($c['code']) ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php if ($c['type_reduction'] === 'pourcentage'): ?>
                                        <strong style="color:var(--or); font-size:1.05rem;">-<?= (float)$c['valeur'] ?>%</strong>
                                    <?php else: ?>
                                        <strong style="color:var(--or); font-size:1.05rem;">-<?= number_format($c['valeur'], 0, ',', ' ') ?> FCFA</strong>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($c['date_expiration'])): ?>
                                        <?= date('d/m/Y', strtotime($c['date_expiration'])) ?>
                                        <?php if (strtotime($c['date_expiration']) < strtotime(date('Y-m-d'))): ?>
                                            <span style="color:#c62828; font-size:0.75rem; font-weight:600;">(Expiré)</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:#888;">Illimitée</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= $c['utilisations_actuel'] ?></strong>
                                    / <?= $c['utilisations_max'] !== null ? $c['utilisations_max'] : '∞' ?>
                                </td>
                                <td>
                                    <?php if ($c['actif'] == 1): ?>
                                        <span class="badge badge-success">Actif</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Désactivé</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;">
                                    <form method="post" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($c['id']) ?>">
                                        <button type="submit" class="btn-primary btn-sm" style="background:#f0f0f0; color:#333; margin-right:4px;">
                                            <?= $c['actif'] == 1 ? 'Désactiver' : 'Activer' ?>
                                        </button>
                                    </form>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer ce code promotionnel ?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="supprimer">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($c['id']) ?>">
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

    <!-- MODAL CRÉATION -->
    <div id="modalCreer" class="modal">
        <div class="modal-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
                <h2 style="font-family:'Cormorant Garamond', serif; font-size:1.8rem; color:var(--vert);">
                    Nouveau Code Promotionnel
                </h2>
                <button type="button" onclick="fermerModalCreer()" style="background:none; border:none; font-size:1.4rem; cursor:pointer; color:#888;">&times;</button>
            </div>
            <form method="post" action="codes-promo.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="creer">

                <div class="form-group">
                    <label class="form-label">Code promotionnel (sans espaces) *</label>
                    <input type="text" name="code" class="form-control" placeholder="Ex: ETE2026" required style="text-transform:uppercase; font-weight:600;">
                </div>

                <div class="form-group">
                    <label class="form-label">Type de réduction *</label>
                    <select name="type_reduction" class="form-control" required>
                        <option value="pourcentage">Pourcentage (%)</option>
                        <option value="montant_fixe">Montant fixe (FCFA)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Valeur de la réduction *</label>
                    <input type="number" step="0.01" min="1" name="valeur" class="form-control" placeholder="Ex: 10 pour 10% ou 25000" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Date d'expiration (facultatif)</label>
                    <input type="date" name="date_expiration" class="form-control" min="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Nombre maximum d'utilisations (facultatif)</label>
                    <input type="number" name="utilisations_max" class="form-control" placeholder="Illimité si vide">
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px;">
                    <button type="button" class="btn-primary" style="background:#eee; color:#333;" onclick="fermerModalCreer()">Annuler</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-check"></i> Créer le code</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function ouvrirModalCreer() {
        document.getElementById('modalCreer').style.display = 'flex';
    }
    function fermerModalCreer() {
        document.getElementById('modalCreer').style.display = 'none';
    }
    window.onclick = function(event) {
        var modal = document.getElementById('modalCreer');
        if (event.target === modal) fermerModalCreer();
    };
    </script>
</body>
</html>
