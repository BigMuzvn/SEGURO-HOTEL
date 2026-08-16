<?php
/**
 * ════════════════════════════════════════════════════════
 * HOSPITOS MASTER STUDIO — Tableau de Bord Multi-Hôtels
 * ════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/includes/HotelManager.php';

// Traitement des actions (Activation, Duplication, Démo)
$message = '';
$msgType = 'success';

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'activate' && !empty($_GET['id'])) {
        if (HotelManager::setActive($_GET['id'])) {
            $message = "L'établissement a été activé avec succès ! Le site web et l'administration ont été synchronisés instantanément.";
        } else {
            $message = "Erreur lors de l'activation de l'établissement.";
            $msgType = 'error';
        }
    } elseif ($_GET['action'] === 'delete' && !empty($_GET['id'])) {
        if (HotelManager::deleteHotel($_GET['id'])) {
            $message = "L'établissement a été supprimé du registre.";
        } else {
            $message = "Impossible de supprimer l'établissement actif ou le dernier établissement restant.";
            $msgType = 'error';
        }
    } elseif ($_GET['action'] === 'load_demo') {
        // Crée 3 profils de démonstration haut de gamme
        HotelManager::saveHotel([
            'name'           => 'Hôtel Saphir Palace & Business',
            'short_name'     => 'Saphir Palace',
            'initials'       => 'SP',
            'tagline'        => 'L\'Art du Séjour d\'Affaires & de Réception',
            'subtitle'       => 'Des espaces de travail et de détente conçus pour l\'élite.',
            'description'    => 'Situé en plein quartier des affaires à Lomé, le Saphir Palace conjugue technologie de pointe et hospitalité raffinée pour vos congrès et séjours exécutifs.',
            'city'           => 'Lomé',
            'country'        => 'Togo',
            'location'       => 'Boulevard Circulaire, Centre d\'Affaires',
            'address'        => 'Quartier Administratif, BP 4589',
            'phone'          => '+228 22 21 00 00',
            'whatsapp'       => '22891223344',
            'email'          => 'contact@saphirpalace.com',
            'currency'       => 'FCFA',
            'ref_prefix'     => 'SPH',
            'client_prefix'  => 'CSP',
            'checkin_time'   => '13:00',
            'checkout_time'  => '12:00',
            'tva_rate'       => '18',
            'tourist_tax'    => '1500',
            'theme_preset'   => 'sapphire_gold',
            'notes'          => 'Profil type hôtel d\'affaires avec centre de congrès et séminaires.',
        ]);

        HotelManager::saveHotel([
            'name'           => 'Villa Océane Luxury Resort',
            'short_name'     => 'Villa Océane',
            'initials'       => 'VO',
            'tagline'        => 'Refuge Éco-Luxe au Bord de l\'Atlantique',
            'subtitle'       => 'L\'authenticité africaine sublimée par le grand luxe balnéaire.',
            'description'    => 'Villas privatives les pieds dans le sable, gastronomie métissée et couchers de soleil inoubliables.',
            'city'           => 'Aného',
            'country'        => 'Togo',
            'location'       => 'Plage des Cocotiers',
            'address'        => 'Route Internationale d\'Aného, PK 32',
            'phone'          => '+228 92 55 66 77',
            'whatsapp'       => '22892556677',
            'email'          => 'reservations@villa-oceane.com',
            'currency'       => 'FCFA',
            'ref_prefix'     => 'VOC',
            'client_prefix'  => 'CVO',
            'checkin_time'   => '15:00',
            'checkout_time'  => '11:00',
            'tva_rate'       => '18',
            'tourist_tax'    => '1000',
            'theme_preset'   => 'terracotta_sunset',
            'notes'          => 'Profil type Resort de charme & Écolodge pieds dans l\'eau.',
        ]);

        $message = "3 profils d'hôtels de démonstration ont été générés avec succès !";
    }
}

$hotels = HotelManager::getAllHotels();
$activeHotel = HotelManager::getActiveHotel();
$pageTitle = "Master Hub — Gestion Multi-Hôtels";
require_once __DIR__ . '/includes/header.php';
?>

<style>
  .hub-hero {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 32px;
    background: linear-gradient(135deg, rgba(20, 42, 32, 0.7) 0%, rgba(10, 22, 16, 0.9) 100%);
    border: 1px solid var(--border-master);
    border-radius: 16px;
    padding: 36px 40px;
    margin-bottom: 40px;
    position: relative;
    overflow: hidden;
  }
  .hub-hero::after {
    content: '';
    position: absolute;
    top: -50px; right: -50px;
    width: 250px; height: 250px;
    background: radial-gradient(circle, rgba(201, 168, 76, 0.15) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
  }

  .hub-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 36px;
  }
  .stat-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-master);
    border-radius: 10px;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: transform 0.25s ease;
  }
  .stat-card:hover {
    transform: translateY(-2px);
    border-color: var(--gold-primary);
  }
  .stat-icon {
    width: 46px; height: 46px;
    border-radius: 10px;
    background: rgba(201, 168, 76, 0.12);
    color: var(--gold-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
  }
  .stat-value {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.8rem;
    font-weight: 700;
    color: #fff;
    line-height: 1;
    margin-bottom: 4px;
  }
  .stat-label {
    font-size: 0.72rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.1em;
  }

  /* ── GRILLE DES HÔTELS (LES CARTES LUXURY) ── */
  .hotels-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 28px;
    margin-top: 24px;
  }
  .hotel-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-master);
    border-radius: 14px;
    padding: 28px;
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 8px 25px rgba(0,0,0,0.35);
  }
  .hotel-card:hover {
    transform: translateY(-4px);
    border-color: rgba(201, 168, 76, 0.55);
    box-shadow: 0 16px 35px rgba(0,0,0,0.5);
  }
  .hotel-card.is-active-card {
    border: 1.5px solid var(--gold-primary);
    background: linear-gradient(180deg, rgba(20, 44, 34, 0.9) 0%, rgba(13, 26, 20, 0.95) 100%);
  }

  .card-top-badge {
    position: absolute;
    top: 18px; right: 18px;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.65rem;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    padding: 4px 10px;
    border-radius: 20px;
  }
  .badge-live {
    background: rgba(16, 185, 129, 0.18);
    color: #10b981;
    border: 1px solid #10b981;
  }
  .badge-ready {
    background: rgba(201, 168, 76, 0.12);
    color: var(--gold-primary);
    border: 1px solid rgba(201, 168, 76, 0.35);
  }

  .hotel-crest-wrap {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
  }
  .hotel-crest-box {
    width: 54px; height: 54px;
    border-radius: 10px;
    border: 1.5px solid var(--gold-primary);
    background: rgba(201, 168, 76, 0.1);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-family: 'Cormorant Garamond', serif;
    font-weight: 700;
    font-size: 1.35rem;
    color: var(--gold-primary);
    flex-shrink: 0;
  }
  .hotel-crest-box span.crown {
    font-size: 0.55rem;
    margin-bottom: -2px;
  }

  .hotel-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.45rem;
    font-weight: 600;
    color: #fff;
    line-height: 1.2;
  }
  .hotel-location-tag {
    font-size: 0.75rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 4px;
  }

  .hotel-palette-preview {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    background: rgba(0, 0, 0, 0.25);
    border-radius: 8px;
    margin: 14px 0 18px;
  }
  .color-dot {
    width: 22px; height: 22px;
    border-radius: 50%;
    border: 1.5px solid rgba(255,255,255,0.25);
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
  }

  .hotel-meta-list {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    font-size: 0.76rem;
    margin-bottom: 22px;
    border-top: 1px solid rgba(255,255,255,0.08);
    padding-top: 14px;
  }
  .meta-item {
    color: var(--text-muted);
  }
  .meta-item strong {
    color: #fff;
    display: block;
    font-size: 0.8rem;
  }

  .card-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    border-top: 1px solid rgba(255,255,255,0.08);
    padding-top: 18px;
  }
  .btn-card-action {
    flex: 1;
    text-align: center;
    padding: 9px 12px;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
  }
  .btn-action-activate {
    background: var(--gold-primary);
    color: #111;
  }
  .btn-action-activate:hover {
    background: var(--gold-hover);
  }
  .btn-action-view {
    background: rgba(255,255,255,0.08);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.15);
  }
  .btn-action-view:hover {
    background: rgba(201, 168, 76, 0.15);
    border-color: var(--gold-primary);
    color: var(--gold-primary);
  }
  .btn-action-icon {
    width: 36px; height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.12);
    color: var(--text-muted);
    text-decoration: none;
    transition: all 0.2s;
  }
  .btn-action-icon:hover {
    color: var(--gold-primary);
    border-color: var(--gold-primary);
  }
  .btn-action-icon.danger:hover {
    color: var(--danger);
    border-color: var(--danger);
    background: rgba(239, 68, 68, 0.12);
  }
</style>

<!-- ALERTES MESSAGES -->
<?php if (!empty($message)): ?>
  <div style="margin-bottom:24px; padding:16px 20px; border-radius:8px; display:flex; align-items:center; gap:12px; font-size:0.88rem; <?= $msgType === 'success' ? 'background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#34d399;' : 'background:rgba(239,68,68,0.15); border:1px solid #ef4444; color:#f87171;' ?>">
    <i class="fas <?= $msgType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>" style="font-size:1.2rem;"></i>
    <div><?= htmlspecialchars($message) ?></div>
  </div>
<?php endif; ?>

<!-- HUB HERO BANNER -->
<section class="hub-hero">
  <div>
    <div style="font-size:0.75rem; letter-spacing:0.25em; text-transform:uppercase; color:var(--gold-primary); font-weight:600; margin-bottom:8px;">
      <i class="fas fa-layer-group"></i> Master Multi-Hotel Suite
    </div>
    <h1 style="font-family:'Cormorant Garamond', serif; font-size:2.4rem; font-weight:600; color:#fff; line-height:1.2; margin-bottom:12px;">
      Catalogue & Studio de Déploiement
    </h1>
    <p style="color:var(--text-muted); font-size:0.95rem; max-width:680px; line-height:1.6;">
      Gérez, personnalisez et basculez instantanément entre les profils de vos <strong>5 hôtels partenaires</strong>. Chaque établissement dispose de son identité, de sa charte graphique, de ses coordonnées WhatsApp directes et de sa configuration dédiée.
    </p>
  </div>
  <div style="display:flex; flex-direction:column; gap:12px; align-items:flex-end; flex-shrink:0;">
    <a href="create.php" class="btn-master-primary">
      <i class="fas fa-plus-circle"></i> Déployer un Nouvel Hôtel
    </a>
    <?php if (count($hotels) === 1): ?>
      <a href="index.php?action=load_demo" class="btn-master-secondary" style="font-size:0.75rem;">
        <i class="fas fa-magic"></i> Charger 2 Hôtels Démo
      </a>
    <?php endif; ?>
  </div>
</section>

<!-- STATISTIQUES GLOBALES DU HUB -->
<section class="hub-stats-grid">
  <div class="stat-card">
    <div class="stat-icon"><i class="fas fa-hotel"></i></div>
    <div>
      <div class="stat-value"><?= count($hotels) ?> / 5</div>
      <div class="stat-label">Hôtels Enregistrés</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="color:#10b981; background:rgba(16,185,129,0.12);"><i class="fas fa-broadcast-tower"></i></div>
    <div>
      <div class="stat-value" style="font-size:1.3rem; color:var(--gold-primary);"><?= htmlspecialchars($activeHotel['short_name'] ?? 'Aucun') ?></div>
      <div class="stat-label">Hôtel Actif en Ligne</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="color:#3b82f6; background:rgba(59,130,246,0.12);"><i class="fas fa-palette"></i></div>
    <div>
      <div class="stat-value">5 Thèmes</div>
      <div class="stat-label">Chartes Pré-calibrées</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="color:#f59e0b; background:rgba(245,158,11,0.12);"><i class="fas fa-shield-alt"></i></div>
    <div>
      <div class="stat-value">100% Sync</div>
      <div class="stat-label">Statut .env & Moteur</div>
    </div>
  </div>
</section>

<!-- EN-TÊTE DE LA GRILLE -->
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
  <div>
    <h2 style="font-family:'Cormorant Garamond', serif; font-size:1.7rem; font-weight:600; color:#fff;">
      Vos Établissements Hôteliers (<?= count($hotels) ?>)
    </h2>
    <p style="font-size:0.8rem; color:var(--text-muted);">
      Cliquez sur <strong>"Activer"</strong> pour basculer la plateforme sur un hôtel en 1 seconde.
    </p>
  </div>
</div>

<!-- GRILLE DES HÔTELS -->
<div class="hotels-grid">
  <?php foreach ($hotels as $hotel): ?>
    <?php 
      $isActive = !empty($hotel['is_active']);
      $colors = $hotel['theme_colors'] ?? HotelManager::$THEME_PRESETS['emerald_gold'];
    ?>
    <div class="hotel-card <?= $isActive ? 'is-active-card' : '' ?>">
      
      <!-- BADGE STATUT -->
      <div class="card-top-badge <?= $isActive ? 'badge-live' : 'badge-ready' ?>">
        <?php if ($isActive): ?>
          <span style="width:6px; height:6px; border-radius:50%; background:#10b981;"></span> EN PRODUCTION
        <?php else: ?>
          <span style="width:6px; height:6px; border-radius:50%; background:var(--gold-primary);"></span> PRÊT À DÉPLOYER
        <?php endif; ?>
      </div>

      <!-- ENTÊTE & BLASON -->
      <div>
        <div class="hotel-crest-wrap">
          <div class="hotel-crest-box" style="border-color:<?= $colors['accent'] ?>; color:<?= $colors['accent'] ?>; background:<?= $colors['primary'] ?>;">
            <span class="crown"><i class="fas fa-crown"></i></span>
            <span><?= htmlspecialchars($hotel['initials'] ?? 'HTL') ?></span>
          </div>
          <div>
            <h3 class="hotel-title"><?= htmlspecialchars($hotel['name']) ?></h3>
            <div class="hotel-location-tag">
              <i class="fas fa-map-marker-alt" style="color:var(--gold-primary);"></i>
              <?= htmlspecialchars($hotel['city'] ?? 'Lomé') ?>, <?= htmlspecialchars($hotel['country'] ?? 'Togo') ?>
            </div>
          </div>
        </div>

        <!-- APERÇU PALETTE DE COULEURS -->
        <div class="hotel-palette-preview">
          <span style="font-size:0.68rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.08em; margin-right:auto;">Charte :</span>
          <div class="color-dot" style="background:<?= $colors['primary'] ?>;" title="Primaire : <?= $colors['primary'] ?>"></div>
          <div class="color-dot" style="background:<?= $colors['accent'] ?>;" title="Accent Or : <?= $colors['accent'] ?>"></div>
          <div class="color-dot" style="background:<?= $colors['dark'] ?>;" title="Sombre : <?= $colors['dark'] ?>"></div>
          <div class="color-dot" style="background:<?= $colors['light'] ?>;" title="Fond Clair : <?= $colors['light'] ?>"></div>
        </div>

        <!-- MÉTADONNÉES ATTRIBUÉES -->
        <div class="hotel-meta-list">
          <div class="meta-item">
            WhatsApp Réception :
            <strong><i class="fab fa-whatsapp" style="color:#25D366;"></i> +<?= htmlspecialchars($hotel['whatsapp'] ?? 'Non configuré') ?></strong>
          </div>
          <div class="meta-item">
            Devise :
            <strong><?= htmlspecialchars($hotel['currency'] ?? 'FCFA') ?> (TVA <?= htmlspecialchars($hotel['tva_rate'] ?? '18') ?>%)</strong>
          </div>
          <div class="meta-item">
            Code Préfixe :
            <strong><?= htmlspecialchars($hotel['ref_prefix'] ?? 'HTL') ?>- / <?= htmlspecialchars($hotel['client_prefix'] ?? 'CLI') ?>-</strong>
          </div>
          <div class="meta-item">
            Horaires Séjour :
            <strong>In: <?= htmlspecialchars($hotel['checkin_time'] ?? '14:00') ?> | Out: <?= htmlspecialchars($hotel['checkout_time'] ?? '12:00') ?></strong>
          </div>
        </div>
      </div>

      <!-- BOUTONS D'ACTIONS -->
      <div class="card-actions">
        <?php if (!$isActive): ?>
          <a href="index.php?action=activate&id=<?= urlencode($hotel['id']) ?>" class="btn-card-action btn-action-activate">
            <i class="fas fa-power-off"></i> Activer
          </a>
        <?php else: ?>
          <div class="btn-card-action" style="background:rgba(16,185,129,0.15); color:#10b981; border:1px solid rgba(16,185,129,0.4); cursor:default;">
            <i class="fas fa-check-circle"></i> Actif en Ligne
          </div>
        <?php endif; ?>

        <a href="hotel-view.php?id=<?= urlencode($hotel['id']) ?>" class="btn-card-action btn-action-view" title="Consulter la fiche détaillée et l'œuvre complète">
          <i class="fas fa-eye"></i> Revue
        </a>

        <a href="edit.php?id=<?= urlencode($hotel['id']) ?>" class="btn-action-icon" title="Modifier cet hôtel">
          <i class="fas fa-edit"></i>
        </a>

        <?php if (!$isActive && count($hotels) > 1): ?>
          <a href="index.php?action=delete&id=<?= urlencode($hotel['id']) ?>" 
             onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet hôtel du registre ?');" 
             class="btn-action-icon danger" 
             title="Supprimer cet hôtel">
            <i class="fas fa-trash-alt"></i>
          </a>
        <?php endif; ?>
      </div>

    </div>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
