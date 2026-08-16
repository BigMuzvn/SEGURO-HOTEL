<?php
/**
 * ════════════════════════════════════════════════════════
 * GESTION PROFIL & ÉQUIPE ADMIN (RBAC) — Hôtel SEGURO
 * ════════════════════════════════════════════════════════
 */

session_start();

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/AdminAuth.php';

AdminAuth::requireAccess('profil');

$database  = new Database();
$db        = $database->getConnection();
$userModel = new User($db);

$succes = '';
$erreur = '';

$allModules = AdminAuth::getAvailableModules();

// ── Traitement POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        $erreur = "Session de sécurité expirée ou jeton CSRF invalide. Veuillez recharger la page.";
    } else {
        $action = $_POST['action'] ?? '';

        // Modifier mon propre profil (Tout admin connecté)
        if ($action === 'mon_profil') {
            $nom       = trim($_POST['nom'] ?? '');
            $prenom    = trim($_POST['prenom'] ?? '');
            $email     = strtolower(trim($_POST['email'] ?? ''));
            $telephone = trim($_POST['telephone'] ?? '');
            $pays      = trim($_POST['pays'] ?? '');

            if (!$nom || !$prenom || !$email) {
                $erreur = 'Nom, prénom et email sont obligatoires.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erreur = 'Format d\'adresse email invalide.';
            } else {
                try {
                    $stmtCheck = $db->prepare("SELECT id, nom, prenom FROM users WHERE email = ? AND id != ? LIMIT 1");
                    $stmtCheck->execute([$email, $_SESSION['user_id']]);
                    $existingUser = $stmtCheck->fetch();

                    if ($existingUser) {
                        $erreur = "L'adresse email \"" . htmlspecialchars($email) . "\" est déjà associée au compte de " . htmlspecialchars($existingUser['prenom'] . ' ' . $existingUser['nom']) . ".";
                    } else {
                        $stmt = $db->prepare("UPDATE users SET nom=?, prenom=?, email=?, telephone=?, pays=? WHERE id=?");
                        $stmt->execute([$nom, $prenom, $email, $telephone, $pays, $_SESSION['user_id']]);
                        $_SESSION['user_nom'] = $nom;
                        $_SESSION['user_prenom'] = $prenom;
                        $_SESSION['user_email'] = $email;
                        $succes = 'Votre profil a été mis à jour avec succès.';
                    }
                } catch (PDOException $e) {
                    error_log("profil mon_profil error: " . $e->getMessage());
                    $erreur = 'Erreur technique lors de la mise à jour du profil : ' . $e->getMessage();
                }
            }
        }

        // Actions réservées EXCLUSIVEMENT au Super Administrateur
        if (in_array($action, ['ajouter_admin', 'modifier_permissions', 'supprimer_admin'])) {
            if (!AdminAuth::isSuperAdmin()) {
                $erreur = "Action refusée : Seul un Super Administrateur est autorisé à gérer l'équipe et les permissions d'accès.";
            } else {
                // Ajouter un membre d'équipe admin avec permissions spécifiques
                if ($action === 'ajouter_admin') {
                    $nom       = trim($_POST['nom'] ?? '');
                    $prenom    = trim($_POST['prenom'] ?? '');
                    $email     = trim($_POST['email'] ?? '');
                    $telephone = trim($_POST['telephone'] ?? '');
                    $role      = trim($_POST['role'] ?? 'admin');
                    $selectedPerms = $_POST['permissions'] ?? [];

                    if (!$nom || !$prenom || !$email) {
                        $erreur = 'Nom, prénom et email sont obligatoires.';
                    } else {
                        try {
                            $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = ?");
                            $stmtCheck->execute([strtolower($email)]);
                            if ($stmtCheck->fetch()) {
                                $erreur = 'Un compte existe déjà avec cette adresse email.';
                            } else {
                                $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                                    mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
                                    mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
                                    mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)
                                );
                                $code_client = 'ADM-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

                                $stmtUser = $db->prepare("INSERT INTO users (id, nom, prenom, email, code_client, telephone, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                $stmtUser->execute([$id, $nom, $prenom, strtolower($email), $code_client, $telephone, $role]);

                                // Si super_admin, donner toutes les permissions
                                if ($role === 'super_admin') {
                                    $selectedPerms = array_keys($allModules);
                                }

                                AdminAuth::savePermissions($id, $selectedPerms, $role);

                                $succes = "L'administrateur \"" . htmlspecialchars($prenom . ' ' . $nom) . "\" a été créé avec succès (Code : " . htmlspecialchars($code_client) . ").";
                            }
                        } catch (PDOException $e) {
                            error_log("profil ajouter_admin: " . $e->getMessage());
                            $erreur = 'Erreur lors de la création du compte administrateur.';
                        }
                    }
                }

                // Mettre à jour les permissions d'un administrateur existant
                if ($action === 'modifier_permissions') {
                    $targetUserId = trim($_POST['user_id'] ?? '');
                    $role = trim($_POST['role'] ?? 'admin');
                    $selectedPerms = $_POST['permissions'] ?? [];

                    if ($targetUserId) {
                        try {
                            if ($role === 'super_admin') {
                                $selectedPerms = array_keys($allModules);
                            }
                            $db->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$role, $targetUserId]);
                            AdminAuth::savePermissions($targetUserId, $selectedPerms, $role);
                            $succes = "Les permissions de l'administrateur ont été mises à jour avec succès.";
                        } catch (Exception $e) {
                            $erreur = "Erreur lors de la mise à jour des permissions : " . $e->getMessage();
                        }
                    }
                }

                // Supprimer un membre d'équipe admin
                if ($action === 'supprimer_admin') {
                    $adminId = trim($_POST['admin_id'] ?? '');
                    if ($adminId === $_SESSION['user_id']) {
                        $erreur = 'Vous ne pouvez pas supprimer votre propre compte administrateur.';
                    } else {
                        try {
                            $db->prepare("DELETE FROM admins WHERE user_id = ?")->execute([$adminId]);
                            $db->prepare("DELETE FROM users WHERE id = ? AND role IN ('admin', 'super_admin')")->execute([$adminId]);
                            $succes = 'Le compte administrateur a été supprimé avec succès.';
                        } catch (PDOException $e) {
                            error_log("profil supprimer_admin: " . $e->getMessage());
                            $erreur = 'Erreur lors de la suppression du compte administrateur.';
                        }
                    }
                }
            }
        }
    }
}

// Informations profil courant
$monProfil = $userModel->getById($_SESSION['user_id']);
// Liste des administrateurs
$equipe = $userModel->getAllAdmins();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Équipe &amp; Permissions RBAC — Admin <?= htmlspecialchars(hotel_name()) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <?= hotel_theme_css() ?>
    <style>
        :root {
            --vert:#1a3a2a; --vert-clair:#2d5c40; --or:#c9a84c; --or-clair:#dfc278;
            --blanc:#faf8f3; --gris:#f0ede6; --gris-fonce:#666;
            --success:#28a745; --danger:#dc3545;
        }
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Jost',sans-serif;background:var(--gris);color:var(--vert);display:flex;min-height:100vh;}
        
        /* SIDEBAR HARMONISÉE ET COMPACTE */
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

        .main-content{flex:1;margin-left:260px;padding:35px 40px;}
        .top-bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;padding-bottom:20px;border-bottom:1px solid #ddd;}
        .page-title h1{font-family:'Cormorant Garamond',serif;font-size:2rem;color:var(--vert);}
        .page-title p{font-size:.85rem;color:var(--gris-fonce);margin-top:4px;}

        .grid-2{display:grid;grid-template-columns:1fr 1.2fr;gap:24px;}
        .card{background:#fff;border-radius:10px;box-shadow:0 4px 18px rgba(0,0,0,.05);padding:26px;margin-bottom:24px;border-top:3px solid var(--or);}
        .card-title{font-family:'Cormorant Garamond',serif;font-size:1.45rem;color:var(--vert);margin-bottom:18px;font-weight:600;}
        
        .form-group{margin-bottom:14px;}
        .form-group label{display:block;font-size:.8rem;color:#555;font-weight:600;margin-bottom:4px;}
        .form-group input, .form-group select{width:100%;padding:9px 12px;border:1px solid #ddd;border-radius:6px;font-family:'Jost',sans-serif;font-size:.88rem;}
        .form-group input:focus, .form-group select:focus{outline:none;border-color:var(--or);}
        
        .permissions-box {
            background: #faf8f3;
            border: 1px solid rgba(201,168,76,0.3);
            border-radius: 8px;
            padding: 14px;
            margin-top: 10px;
        }
        .permissions-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 8px;
        }
        .perm-checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            color: #333;
            cursor: pointer;
        }
        .perm-checkbox-item input {
            width: 16px;
            height: 16px;
            accent-color: var(--vert);
            cursor: pointer;
        }

        .btn{display:inline-flex;align-items:center;gap:8px;padding:10px 22px;border-radius:6px;font-size:.82rem;cursor:pointer;border:none;transition:all .3s;font-family:'Jost',sans-serif;}
        .btn-primary{background:var(--vert);color:var(--or);}
        .btn-primary:hover{background:var(--vert-clair);}
        .btn-gold{background:var(--or);color:#111;font-weight:600;}
        .btn-gold:hover{background:var(--or-clair);}

        .alert{padding:14px 20px;border-radius:6px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:.9rem;}
        .alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb;}
        .alert-danger{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;}

        table{width:100%;border-collapse:collapse;margin-top:10px;}
        th{text-align:left;padding:12px 14px;font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--gris-fonce);background:#faf8f3;border-bottom:1px solid #eee;}
        td{padding:12px 14px;font-size:.85rem;border-bottom:1px solid #f2f2f2;}
        .badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:.7rem;font-weight:600;}
        .badge-super{background:#fff3cd;color:#856404;}
        .badge-admin{background:#d4edda;color:#155724;}

        .perm-badge-tag {
            display: inline-block;
            background: #eef2f5;
            color: #2c3e50;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 0.68rem;
            margin: 2px 1px;
        }

        /* Modal Permissions */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            width: 520px;
            max-width: 90vw;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            border-top: 4px solid var(--or);
        }

        @media(max-width:1100px){.grid-2{grid-template-columns:1fr;}}
        @media(max-width:768px){.sidebar{width:70px;}.sidebar-logo span,.nav-item span,.nav-section,.admin-details{display:none;}.main-content{margin-left:70px;padding:20px;}}
    </style>
</head>
<body>

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
            <a href="codes-promo.php" class="nav-item"><i class="fas fa-tags"></i><span>Codes Promo</span></a>
        <?php endif; ?>
        <?php if (AdminAuth::can('profil')): ?>
            <a href="profil.php" class="nav-item active"><i class="fas fa-user-shield"></i><span>Équipe &amp; Profil</span></a>
        <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-info">
            <div class="admin-avatar"><?= strtoupper(substr($_SESSION['user_prenom']??'A',0,1)) ?></div>
            <div class="admin-details">
                <h4><?= htmlspecialchars(($_SESSION['user_prenom']??'').' '.($_SESSION['user_nom']??'')) ?></h4>
                <p>Super Administrateur</p>
            </div>
        </div>
        <a href="../pages/deconnexion.php" style="display:flex; align-items:center; gap:8px; margin-top:10px; color:rgba(250,248,243,0.6); text-decoration:none; font-size:0.75rem;">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</aside>

<main class="main-content">
    <div class="top-bar">
        <div class="page-title">
            <h1>Équipe, Rôles &amp; Permissions RBAC</h1>
            <p>Gestion fine des accès et délégation des modules d'administration</p>
        </div>
    </div>

    <?php if ($succes): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($succes) ?></div><?php endif; ?>
    <?php if ($erreur): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($erreur) ?></div><?php endif; ?>

    <div class="grid-2">
        <!-- Carte : Mon Profil -->
        <div class="card">
            <h2 class="card-title"><i class="fas fa-user-circle" style="color:var(--or);margin-right:8px;"></i>Mon Profil Super Admin</h2>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="mon_profil">
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="nom" value="<?= htmlspecialchars($monProfil ? ($monProfil->nom ?? '') : ($_SESSION['user_nom'] ?? '')) ?>" required>
                </div>
                <div class="form-group">
                    <label>Prénom</label>
                    <input type="text" name="prenom" value="<?= htmlspecialchars($monProfil ? ($monProfil->prenom ?? '') : ($_SESSION['user_prenom'] ?? '')) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($monProfil ? ($monProfil->email ?? '') : ($_SESSION['user_email'] ?? '')) ?>" required>
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="text" name="telephone" value="<?= htmlspecialchars($monProfil->telephone ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Pays</label>
                    <input type="text" name="pays" value="<?= htmlspecialchars($monProfil->pays ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top:10px;">
                    <i class="fas fa-save"></i> Enregistrer mes informations
                </button>
            </form>
        </div>

        <!-- Carte : Ajouter un administrateur avec permissions -->
        <div class="card">
            <h2 class="card-title"><i class="fas fa-user-plus" style="color:var(--or);margin-right:8px;"></i>Ajouter un Collaborateur</h2>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="ajouter_admin">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label>Nom</label>
                        <input type="text" name="nom" required placeholder="ex: Koffi">
                    </div>
                    <div class="form-group">
                        <label>Prénom</label>
                        <input type="text" name="prenom" required placeholder="ex: Marc">
                    </div>
                </div>
                <div class="form-group">
                    <label>Email professionnel</label>
                    <input type="email" name="email" required placeholder="ex: contact@<?= parse_url(defined('BASE_URL') ? BASE_URL : 'hotel.com', PHP_URL_HOST) ?: 'hotel.com' ?>">
                </div>
                <div class="form-group">
                    <label>Téléphone / WhatsApp</label>
                    <input type="text" name="telephone" placeholder="+228 ...">
                </div>
                <div class="form-group">
                    <label>Niveau de rôle</label>
                    <select name="role" id="select_role" onchange="togglePermBox(this.value)">
                        <option value="admin">Administrateur Délégué (Accès personnalisés)</option>
                        <option value="super_admin">Super Administrateur (Accès Total)</option>
                    </select>
                </div>

                <div class="permissions-box" id="new_perms_box">
                    <label style="font-size:0.75rem; color:var(--vert); font-weight:700; text-transform:uppercase; letter-spacing:0.05em;">
                        <i class="fas fa-key" style="color:var(--or);"></i> Modules &amp; Accès autorisés :
                    </label>
                    <div class="permissions-grid">
                        <?php foreach ($allModules as $modKey => $modInfo): ?>
                            <?php if (empty($modInfo['super_admin_only'])): ?>
                                <label class="perm-checkbox-item">
                                    <input type="checkbox" name="permissions[]" value="<?= $modKey ?>" checked>
                                    <span><?= htmlspecialchars($modInfo['label']) ?></span>
                                </label>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-gold" style="margin-top:16px; width:100%;">
                    <i class="fas fa-user-check"></i> Créer le compte &amp; assigner les accès
                </button>
            </form>
        </div>
    </div>

    <!-- Tableau : Liste de l'Équipe Admin & Permissions -->
    <div class="card">
        <h2 class="card-title"><i class="fas fa-users-cog" style="color:var(--or);margin-right:8px;"></i>Gestion des Accès de l'Équipe</h2>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Nom &amp; Prénom</th>
                        <th>Email &amp; Contact</th>
                        <th>Identifiant</th>
                        <th>Rôle</th>
                        <th>Modules Autorisés</th>
                        <th>Création</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipe as $adm): ?>
                    <?php 
                        $userPerms = AdminAuth::getUserPermissions($adm['id']);
                        $isSuper = ($adm['role'] === 'super_admin');
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($adm['nom'] . ' ' . $adm['prenom']) ?></strong></td>
                        <td>
                            <div><?= htmlspecialchars($adm['email']) ?></div>
                            <div style="font-size:0.75rem; color:#888;"><?= htmlspecialchars($adm['telephone'] ?? '') ?></div>
                        </td>
                        <td><code style="background:#f4f1ea; padding:3px 8px; border-radius:4px; font-weight:600;"><?= htmlspecialchars($adm['code_client']) ?></code></td>
                        <td>
                            <span class="badge <?= $isSuper ? 'badge-super' : 'badge-admin' ?>">
                                <?= $isSuper ? 'Super Admin' : 'Admin Délégué' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($isSuper): ?>
                                <span style="font-size:0.75rem; color:var(--vert); font-weight:600;"><i class="fas fa-star" style="color:var(--or);"></i> Accès Total</span>
                            <?php else: ?>
                                <?php if (empty($userPerms)): ?>
                                    <span style="font-size:0.75rem; color:#888;">Tous les modules standards</span>
                                <?php else: ?>
                                    <?php foreach ($userPerms as $p): ?>
                                        <span class="perm-badge-tag"><?= htmlspecialchars($allModules[$p]['label'] ?? $p) ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y', strtotime($adm['created_at'])) ?></td>
                        <td style="text-align:right; white-space:nowrap;">
                            <?php if ($adm['id'] !== $_SESSION['user_id']): ?>
                                <button type="button" class="btn btn-primary" style="padding:6px 12px; font-size:0.75rem;" onclick='openEditPermsModal(<?= json_encode($adm) ?>, <?= json_encode($userPerms) ?>)'>
                                    <i class="fas fa-sliders-h"></i> Permissions
                                </button>
                                <form method="POST" style="display:inline; margin-left:6px;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet administrateur ?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="supprimer_admin">
                                    <input type="hidden" name="admin_id" value="<?= htmlspecialchars($adm['id']) ?>">
                                    <button type="submit" style="background:#dc3545; color:#fff; border:none; padding:6px 10px; border-radius:4px; font-size:.75rem; cursor:pointer;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="font-size:.75rem; color:#888;">(Vous-même)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- MODAL MODIFICATION PERMISSIONS -->
<div class="modal-overlay" id="editPermsModal">
    <div class="modal-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h3 style="font-family:'Cormorant Garamond',serif; font-size:1.5rem; color:var(--vert);">
                <i class="fas fa-user-lock" style="color:var(--or);"></i> Modifier les Accès
            </h3>
            <button type="button" onclick="closeEditPermsModal()" style="background:none; border:none; font-size:1.4rem; cursor:pointer; color:#888;">&times;</button>
        </div>
        
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="modifier_permissions">
            <input type="hidden" name="user_id" id="edit_user_id" value="">

            <p style="font-size:0.85rem; color:#555; margin-bottom:14px;">
                Administrateur : <strong id="edit_admin_name" style="color:var(--vert);"></strong>
            </p>

            <div class="form-group">
                <label>Rôle</label>
                <select name="role" id="edit_role" onchange="toggleModalPermBox(this.value)">
                    <option value="admin">Administrateur Délégué</option>
                    <option value="super_admin">Super Administrateur</option>
                </select>
            </div>

            <div class="permissions-box" id="edit_perms_box">
                <label style="font-size:0.75rem; color:var(--vert); font-weight:700; text-transform:uppercase;">
                    Modules autorisés :
                </label>
                <div class="permissions-grid">
                    <?php foreach ($allModules as $modKey => $modInfo): ?>
                        <?php if (empty($modInfo['super_admin_only'])): ?>
                            <label class="perm-checkbox-item">
                                <input type="checkbox" name="permissions[]" value="<?= $modKey ?>" class="edit-perm-check" id="perm_<?= $modKey ?>">
                                <span><?= htmlspecialchars($modInfo['label']) ?></span>
                            </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" onclick="closeEditPermsModal()" class="btn" style="background:#eee; color:#333;">Annuler</button>
                <button type="submit" class="btn btn-gold">Enregistrer les permissions</button>
            </div>
        </form>
    </div>
</div>

<script>
function togglePermBox(role) {
    const box = document.getElementById('new_perms_box');
    box.style.display = (role === 'super_admin') ? 'none' : 'block';
}

function toggleModalPermBox(role) {
    const box = document.getElementById('edit_perms_box');
    box.style.display = (role === 'super_admin') ? 'none' : 'block';
}

function openEditPermsModal(admin, perms) {
    document.getElementById('edit_user_id').value = admin.id;
    document.getElementById('edit_admin_name').innerText = admin.prenom + ' ' + admin.nom + ' (' + admin.email + ')';
    document.getElementById('edit_role').value = admin.role;
    
    toggleModalPermBox(admin.role);

    document.querySelectorAll('.edit-perm-check').forEach(chk => {
        if (perms && perms.length > 0) {
            chk.checked = perms.includes(chk.value);
        } else {
            chk.checked = true;
        }
    });

    document.getElementById('editPermsModal').style.display = 'flex';
}

function closeEditPermsModal() {
    document.getElementById('editPermsModal').style.display = 'none';
}
</script>

</body>
</html>
