<?php
/**
 * ════════════════════════════════════════════════════════
 * COMPOSANT SIDEBAR UNIQUE & UNIFIÉ — ADMINISTRATION
 * ════════════════════════════════════════════════════════
 */

if (!defined('BASE_URL')) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $baseUrl = rtrim(str_replace('\\', '/', dirname($scriptDir)), '/');
} else {
    $baseUrl = BASE_URL;
}

// Récupération dynamique des compteurs de notification pour les badges
$sidebar_counts = [
    'reservations_pending' => 0,
    'room_service_pending' => 0,
    'evenements_pending'   => 0,
    'avis_pending'         => 0,
];

if (isset($db) && $db instanceof PDO) {
    try {
        $stmtBadgeResa = $db->query("SELECT COUNT(*) FROM reservations WHERE statut IN ('en_cours', 'modifiee')");
        if ($stmtBadgeResa) $sidebar_counts['reservations_pending'] = (int)$stmtBadgeResa->fetchColumn();
    } catch (Exception $e) {}

    try {
        $stmtBadgeRS = $db->query("SELECT COUNT(*) FROM room_service_commandes WHERE statut IN ('en_attente', 'preparation')");
        if ($stmtBadgeRS) $sidebar_counts['room_service_pending'] = (int)$stmtBadgeRS->fetchColumn();
    } catch (Exception $e) {}

    try {
        $stmtBadgeDevis = $db->query("SELECT COUNT(*) FROM evenements_devis WHERE statut = 'demande'");
        if ($stmtBadgeDevis) $sidebar_counts['evenements_pending'] = (int)$stmtBadgeDevis->fetchColumn();
    } catch (Exception $e) {}

    try {
        $stmtBadgeAvis = $db->query("SELECT COUNT(*) FROM avis WHERE statut = 'en_attente'");
        if ($stmtBadgeAvis) $sidebar_counts['avis_pending'] = (int)$stmtBadgeAvis->fetchColumn();
    } catch (Exception $e) {}
}

$current_active = $active_page ?? '';

// Récupération des informations de l'administrateur connecté
$admin_nom = trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
if (empty($admin_nom)) $admin_nom = 'Administrateur';
$admin_initials = strtoupper(substr($_SESSION['user_prenom'] ?? 'A', 0, 1) . substr($_SESSION['user_nom'] ?? 'D', 0, 1));
$admin_role_label = ($_SESSION['user_role'] ?? '') === 'super_admin' ? 'Super Admin' : 'Admin';
?>

<!-- Styles unifiés et normalisés pour la Sidebar Admin -->
<style id="admin-unified-sidebar-styles">
    @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Jost:wght@200;300;400;500;600;700&display=swap');

    :root {
        --admin-sidebar-width: 260px;
        --vert: #1a3a2a;
        --vert-fonce: #0f2418;
        --vert-clair: #2d5c40;
        --or: #c9a84c;
        --or-clair: #e0c068;
        --or-pale: #f5e9c4;
        --blanc: #faf8f3;
        --gris: #f5f5f5;
        --gris-fonce: #666;
        --danger: #dc3545;
        --success: #28a745;
        --warning: #ffc107;
    }

    body {
        font-family: 'Jost', sans-serif !important;
        margin: 0;
        padding: 0;
        min-height: 100vh;
        background: #f4f6f8;
        color: #2c3e50;
        display: flex;
    }

    /* Sidebar Conteneur Fixe Sans Décalage */
    .sidebar {
        width: var(--admin-sidebar-width) !important;
        height: 100vh !important;
        background: linear-gradient(180deg, #132a1e 0%, #0c1b13 100%) !important;
        color: var(--blanc) !important;
        position: fixed !important;
        top: 0 !important;
        bottom: 0 !important;
        left: 0 !important;
        display: flex !important;
        flex-direction: column !important;
        z-index: 9999 !important;
        box-shadow: 4px 0 25px rgba(0, 0, 0, 0.15) !important;
        border-right: 1px solid rgba(201, 168, 76, 0.18) !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
    }

    /* En-tête Logo Sidebar */
    .sidebar-header {
        padding: 16px 20px !important;
        border-bottom: 1px solid rgba(201, 168, 76, 0.15) !important;
        background: rgba(10, 22, 16, 0.6) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        flex-shrink: 0 !important;
    }

    .sidebar-logo {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        text-decoration: none !important;
        color: var(--blanc) !important;
        width: 100% !important;
    }

    .sidebar-logo-crest {
        width: 38px !important;
        height: 38px !important;
        border: 1.5px solid var(--or) !important;
        background: rgba(201, 168, 76, 0.1) !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        flex-shrink: 0 !important;
        border-radius: 4px !important;
        transition: all 0.3s ease !important;
    }
    .sidebar-logo:hover .sidebar-logo-crest {
        background: var(--or) !important;
        transform: scale(1.04) !important;
    }
    .sidebar-logo:hover .sidebar-logo-crest i,
    .sidebar-logo:hover .sidebar-logo-crest span {
        color: #111 !important;
    }

    .sidebar-logo-crest i {
        font-size: 0.55rem !important;
        color: var(--or) !important;
        line-height: 1 !important;
        margin-bottom: 1px !important;
    }
    .sidebar-logo-crest span {
        font-family: 'Cormorant Garamond', serif !important;
        font-size: 1.1rem !important;
        font-weight: 700 !important;
        color: var(--or) !important;
        line-height: 1 !important;
        letter-spacing: 0.05em !important;
    }

    .sidebar-logo-text {
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
    }
    .sidebar-logo-title {
        font-family: 'Cormorant Garamond', serif !important;
        font-size: 1.15rem !important;
        font-weight: 600 !important;
        color: #ffffff !important;
        letter-spacing: 0.06em !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        line-height: 1.15 !important;
    }
    .sidebar-logo-badge {
        font-family: 'Jost', sans-serif !important;
        font-size: 0.52rem !important;
        font-weight: 500 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.22em !important;
        color: var(--or) !important;
        margin-top: 2px !important;
    }

    /* Navigation scrollable */
    .sidebar-nav {
        flex: 1 !important;
        padding: 8px 0 !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        scrollbar-width: thin !important;
        scrollbar-color: rgba(201, 168, 76, 0.2) transparent !important;
    }
    .sidebar-nav::-webkit-scrollbar {
        width: 4px !important;
    }
    .sidebar-nav::-webkit-scrollbar-thumb {
        background: rgba(201, 168, 76, 0.25) !important;
        border-radius: 4px !important;
    }

    /* Rubriques / Groupes */
    .nav-section {
        padding: 12px 20px 4px !important;
        font-family: 'Jost', sans-serif !important;
        font-size: 0.56rem !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.22em !important;
        color: rgba(201, 168, 76, 0.65) !important;
        user-select: none !important;
    }

    /* Liens d'élément de menu */
    .nav-item {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        padding: 8px 20px !important;
        color: rgba(250, 248, 243, 0.78) !important;
        text-decoration: none !important;
        font-family: 'Jost', sans-serif !important;
        font-size: 0.82rem !important;
        font-weight: 400 !important;
        letter-spacing: 0.03em !important;
        transition: all 0.22s ease !important;
        position: relative !important;
        border-left: 3px solid transparent !important;
    }

    .nav-item:hover {
        background: rgba(201, 168, 76, 0.08) !important;
        color: #ffffff !important;
        padding-left: 23px !important;
    }

    .nav-item.active {
        background: linear-gradient(90deg, rgba(201, 168, 76, 0.18) 0%, rgba(201, 168, 76, 0.04) 100%) !important;
        color: var(--or) !important;
        border-left: 3px solid var(--or) !important;
        font-weight: 500 !important;
    }

    .nav-item i {
        width: 18px !important;
        font-size: 0.92rem !important;
        text-align: center !important;
        color: rgba(250, 248, 243, 0.65) !important;
        transition: color 0.2s, transform 0.2s !important;
        flex-shrink: 0 !important;
    }

    .nav-item:hover i,
    .nav-item.active i {
        color: var(--or) !important;
        transform: scale(1.1) !important;
    }

    .nav-item span {
        flex: 1 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    /* Badges de notification */
    .nav-badge {
        font-family: 'Jost', sans-serif !important;
        font-size: 0.62rem !important;
        font-weight: 600 !important;
        padding: 2px 7px !important;
        border-radius: 10px !important;
        background: var(--or) !important;
        color: #111111 !important;
        line-height: 1.2 !important;
        margin-left: auto !important;
        flex-shrink: 0 !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.2) !important;
    }
    .nav-badge.danger {
        background: #e74c3c !important;
        color: #ffffff !important;
    }

    /* Lien vers le site public */
    .nav-item.public-link {
        margin: 10px 14px 4px !important;
        padding: 8px 12px !important;
        border: 1px dashed rgba(201, 168, 76, 0.3) !important;
        border-radius: 6px !important;
        background: rgba(201, 168, 76, 0.04) !important;
        font-size: 0.74rem !important;
        color: rgba(201, 168, 76, 0.9) !important;
    }
    .nav-item.public-link:hover {
        background: rgba(201, 168, 76, 0.14) !important;
        border-color: var(--or) !important;
        color: var(--or) !important;
        padding-left: 12px !important;
    }
    .nav-item.public-link i {
        color: var(--or) !important;
    }

    /* Pied de page Sidebar (Profil Admin & Déconnexion) */
    .sidebar-footer {
        padding: 14px 18px !important;
        border-top: 1px solid rgba(201, 168, 76, 0.15) !important;
        background: rgba(8, 18, 13, 0.6) !important;
        flex-shrink: 0 !important;
    }

    .admin-info {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        text-decoration: none !important;
        padding: 4px !important;
        border-radius: 6px !important;
        transition: background 0.2s !important;
    }
    .admin-info:hover {
        background: rgba(255, 255, 255, 0.05) !important;
    }

    .admin-avatar {
        width: 34px !important;
        height: 34px !important;
        border-radius: 50% !important;
        background: linear-gradient(135deg, var(--or) 0%, #a67c2e 100%) !important;
        color: #111111 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-family: 'Jost', sans-serif !important;
        font-weight: 700 !important;
        font-size: 0.78rem !important;
        border: 1.5px solid rgba(255, 255, 255, 0.2) !important;
        flex-shrink: 0 !important;
    }

    .admin-details {
        flex: 1 !important;
        overflow: hidden !important;
    }
    .admin-details h4 {
        font-family: 'Jost', sans-serif !important;
        font-size: 0.80rem !important;
        font-weight: 500 !important;
        color: #ffffff !important;
        margin: 0 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }
    .admin-details p {
        font-family: 'Jost', sans-serif !important;
        font-size: 0.62rem !important;
        color: var(--or) !important;
        margin: 2px 0 0 !important;
        white-space: nowrap !important;
        text-transform: uppercase !important;
        letter-spacing: 0.08em !important;
    }

    .sidebar-actions {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        margin-top: 10px !important;
        padding-top: 8px !important;
        border-top: 1px solid rgba(255, 255, 255, 0.06) !important;
    }
    .sidebar-action-btn {
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        color: rgba(250, 248, 243, 0.6) !important;
        text-decoration: none !important;
        font-family: 'Jost', sans-serif !important;
        font-size: 0.70rem !important;
        transition: color 0.2s !important;
    }
    .sidebar-action-btn:hover {
        color: var(--or) !important;
    }
    .sidebar-action-btn.logout:hover {
        color: #ff7675 !important;
    }

    /* Calage du contenu principal pour éviter tout chevauchement */
    .main-content {
        margin-left: var(--admin-sidebar-width) !important;
        flex: 1 !important;
        min-width: 0 !important;
        box-sizing: border-box !important;
    }

    /* Adaptation Responsive Mobile & Tablettes */
    @media (max-width: 991px) {
        :root {
            --admin-sidebar-width: 70px;
        }
        .sidebar {
            width: 70px !important;
        }
        .sidebar-header {
            padding: 14px 10px !important;
            justify-content: center !important;
        }
        .sidebar-logo-text,
        .nav-section,
        .nav-item span,
        .nav-badge,
        .admin-details,
        .sidebar-actions span {
            display: none !important;
        }
        .nav-item {
            justify-content: center !important;
            padding: 12px 0 !important;
        }
        .nav-item i {
            margin: 0 !important;
            font-size: 1.1rem !important;
        }
        .sidebar-footer {
            padding: 12px 8px !important;
        }
        .admin-info {
            justify-content: center !important;
        }
        .sidebar-actions {
            justify-content: center !important;
        }
        .main-content {
            margin-left: 70px !important;
        }
    }
</style>

<!-- ══════════════════════════════════════════
     SIDEBAR ADMIN UNIQUE & CENTRALISÉE
══════════════════════════════════════════ -->
<aside class="sidebar">
    <!-- Header Logo & Identité Hôtel -->
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-logo" title="<?= htmlspecialchars(hotel_name()) ?>">
            <div class="sidebar-logo-crest">
                <i class="fas fa-crown"></i>
                <span><?= htmlspecialchars(hotel_initials()) ?></span>
            </div>
            <div class="sidebar-logo-text">
                <span class="sidebar-logo-title"><?= htmlspecialchars(hotel_short_name()) ?></span>
                <span class="sidebar-logo-badge">Administration</span>
            </div>
        </a>
    </div>
    
    <!-- Menu de Navigation avec Contrôle RBAC -->
    <nav class="sidebar-nav">
        <!-- Section 1 : Vue Globale -->
        <div class="nav-section">Vue Globale</div>
        <?php if (AdminAuth::can('dashboard')): ?>
            <a href="dashboard.php" class="nav-item <?= $current_active === 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i>
                <span>Tableau de bord</span>
            </a>
        <?php endif; ?>
        <?php if (AdminAuth::can('calendrier')): ?>
            <a href="calendrier.php" class="nav-item <?= $current_active === 'calendrier' ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>Calendrier</span>
            </a>
        <?php endif; ?>
        
        <!-- Section 2 : Gestion Opérationnelle -->
        <div class="nav-section" style="margin-top: 10px;">Gestion Opérations</div>
        <?php if (AdminAuth::can('reservations')): ?>
            <a href="reservations.php" class="nav-item <?= $current_active === 'reservations' ? 'active' : '' ?>">
                <i class="fas fa-book-open"></i>
                <span>Réservations</span>
                <?php if ($sidebar_counts['reservations_pending'] > 0): ?>
                    <span class="nav-badge"><?= $sidebar_counts['reservations_pending'] ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>
        <?php if (AdminAuth::can('room_service')): ?>
            <a href="room-service.php" class="nav-item <?= $current_active === 'room_service' ? 'active' : '' ?>">
                <i class="fas fa-concierge-bell"></i>
                <span>Room Service</span>
                <?php if ($sidebar_counts['room_service_pending'] > 0): ?>
                    <span class="nav-badge"><?= $sidebar_counts['room_service_pending'] ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>
        <?php if (AdminAuth::can('evenements')): ?>
            <a href="evenements.php" class="nav-item <?= $current_active === 'evenements' ? 'active' : '' ?>">
                <i class="fas fa-glass-cheers"></i>
                <span>Devis Événements</span>
                <?php if ($sidebar_counts['evenements_pending'] > 0): ?>
                    <span class="nav-badge"><?= $sidebar_counts['evenements_pending'] ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>
        <?php if (AdminAuth::can('clients')): ?>
            <a href="clients.php" class="nav-item <?= $current_active === 'clients' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Fichier Clients</span>
            </a>
        <?php endif; ?>
        <?php if (AdminAuth::can('chambres')): ?>
            <a href="chambres.php" class="nav-item <?= $current_active === 'chambres' ? 'active' : '' ?>">
                <i class="fas fa-bed"></i>
                <span>Chambres &amp; Suites</span>
            </a>
        <?php endif; ?>

        <!-- Section 3 : Marketing & Configuration -->
        <div class="nav-section" style="margin-top: 10px;">Marketing &amp; Système</div>
        <?php if (AdminAuth::can('avis')): ?>
            <a href="avis.php" class="nav-item <?= $current_active === 'avis' ? 'active' : '' ?>">
                <i class="fas fa-star"></i>
                <span>Avis Clients</span>
                <?php if ($sidebar_counts['avis_pending'] > 0): ?>
                    <span class="nav-badge"><?= $sidebar_counts['avis_pending'] ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>
        <?php if (AdminAuth::can('codes_promo')): ?>
            <a href="codes-promo.php" class="nav-item <?= $current_active === 'codes_promo' ? 'active' : '' ?>">
                <i class="fas fa-tags"></i>
                <span>Codes Promo</span>
            </a>
        <?php endif; ?>
        <?php if (AdminAuth::can('profil')): ?>
            <a href="profil.php" class="nav-item <?= $current_active === 'profil' ? 'active' : '' ?>">
                <i class="fas fa-user-shield"></i>
                <span>Équipe &amp; Sécurité</span>
            </a>
        <?php endif; ?>

        <!-- Raccourci vers le site public -->
        <a href="<?= $baseUrl ?>/index.php" target="_blank" class="nav-item public-link" title="Ouvrir le site public">
            <i class="fas fa-external-link-alt"></i>
            <span>Voir le site public</span>
        </a>
    </nav>
    
    <!-- Footer Profil Admin Connecté -->
    <div class="sidebar-footer">
        <a href="profil.php" class="admin-info" title="Modifier mon profil admin">
            <div class="admin-avatar">
                <?= $admin_initials ?>
            </div>
            <div class="admin-details">
                <h4><?= htmlspecialchars($admin_nom) ?></h4>
                <p><?= htmlspecialchars($admin_role_label) ?></p>
            </div>
        </a>
        <div class="sidebar-actions">
            <a href="profil.php" class="sidebar-action-btn" title="Paramètres du compte">
                <i class="fas fa-cog"></i>
                <span>Profil</span>
            </a>
            <a href="../pages/deconnexion.php" class="sidebar-action-btn logout" title="Se déconnecter de l'administration">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </a>
        </div>
    </div>
</aside>
