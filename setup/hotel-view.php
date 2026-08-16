<?php
/**
 * ════════════════════════════════════════════════════════
 * HOSPITOS — REVUE COMPLÈTE & FICHE D'ATTRIBUTION ("L'ŒUVRE")
 * ════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/includes/HotelManager.php';

$id = $_GET['id'] ?? '';
$hotel = HotelManager::getHotel($id);

if (!$hotel) {
    header('Location: index.php');
    exit;
}

$colors = $hotel['theme_colors'] ?? HotelManager::$THEME_PRESETS['emerald_gold'];
$isActive = !empty($hotel['is_active']);
$isJustCreated = !empty($_GET['created']);

$pageTitle = "Revue — " . htmlspecialchars($hotel['name']);
require_once __DIR__ . '/includes/header.php';
?>

<style>
  .hotel-view-hero {
    background: linear-gradient(135deg, rgba(16, 32, 24, 0.9) 0%, rgba(9, 18, 14, 0.95) 100%);
    border: 1.5px solid var(--border-master);
    border-radius: 16px;
    padding: 40px;
    margin-bottom: 36px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 32px;
    position: relative;
    overflow: hidden;
  }
  .hotel-view-hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(to right, <?= $colors['primary'] ?>, <?= $colors['accent'] ?>, <?= $colors['light'] ?>);
  }

  .view-crest-box {
    width: 86px; height: 86px;
    border-radius: 14px;
    border: 2px solid <?= $colors['accent'] ?>;
    background: <?= $colors['primary'] ?>;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-family: 'Cormorant Garamond', serif;
    font-weight: 700;
    font-size: 2rem;
    color: <?= $colors['accent'] ?>;
    box-shadow: 0 8px 30px rgba(0,0,0,0.5);
    flex-shrink: 0;
  }
  .view-crest-box span.crown { font-size: 0.8rem; margin-bottom: -3px; }

  .review-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 28px;
    margin-bottom: 36px;
  }
  @media(max-width:992px){
    .review-grid { grid-template-columns: 1fr; }
    .hotel-view-hero { flex-direction: column; align-items: flex-start; }
  }

  .review-box {
    background: var(--bg-surface);
    border: 1px solid var(--border-master);
    border-radius: 14px;
    padding: 28px;
    margin-bottom: 24px;
  }
  .review-box-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.45rem;
    color: var(--gold-primary);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    padding-bottom: 12px;
  }

  .info-table {
    width: 100%;
    border-collapse: collapse;
  }
  .info-table td {
    padding: 12px 8px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    font-size: 0.88rem;
  }
  .info-table td.label-col {
    width: 38%;
    color: var(--text-muted);
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
  }
  .info-table td.val-col {
    color: #fff;
    font-weight: 500;
  }

  /* ── SIMULATEUR UI EN DIRECT ── */
  .ui-simulation-frame {
    background: #080f0b;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    padding: 20px;
    margin-top: 14px;
  }
  .sim-button-primary {
    background: <?= $colors['accent'] ?>;
    color: #111;
    font-family: 'Jost', sans-serif;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    padding: 8px 18px;
    border-radius: 4px;
    display: inline-block;
    border: none;
  }
  .sim-card-sample {
    background: <?= $colors['light'] ?>;
    color: <?= $colors['dark'] ?>;
    border-radius: 8px;
    padding: 16px;
    margin-top: 14px;
  }

  .env-code-box {
    background: #050a08;
    border: 1px solid rgba(201,168,76,0.25);
    border-radius: 8px;
    padding: 16px;
    font-family: 'Courier New', monospace;
    font-size: 0.78rem;
    color: #34d399;
    line-height: 1.6;
    overflow-x: auto;
    max-height: 280px;
  }
</style>

<!-- NOTIFICATION SUCCÈS CRÉATION -->
<?php if ($isJustCreated): ?>
  <div style="background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#34d399; padding:16px 20px; border-radius:8px; margin-bottom:24px; display:flex; align-items:center; gap:12px;">
    <i class="fas fa-check-circle" style="font-size:1.3rem;"></i>
    <div>
      <strong>Nouvel Hôtel Déployé avec Succès !</strong><br>
      L'établissement a été enregistré dans votre catalogue. Consultez sa fiche d'attribution complète ci-dessous.
    </div>
  </div>
<?php endif; ?>

<a href="index.php" style="color:var(--text-muted); text-decoration:none; font-size:0.8rem; display:inline-flex; align-items:center; gap:6px; margin-bottom:16px;">
  <i class="fas fa-arrow-left"></i> Retour au Catalogue des Hôtels
</a>

<!-- HERO DE L'ŒUVRE / FICHE HÔTEL -->
<div class="hotel-view-hero">
  <div style="display:flex; align-items:center; gap:24px;">
    <div class="view-crest-box">
      <span class="crown"><i class="fas fa-crown"></i></span>
      <span><?= htmlspecialchars($hotel['initials'] ?? 'HTL') ?></span>
    </div>
    <div>
      <div style="font-size:0.75rem; color:var(--gold-primary); text-transform:uppercase; letter-spacing:0.2em; font-weight:600; margin-bottom:4px;">
        Fiche d'Attribution & Revue de l'Hôtel
      </div>
      <h1 style="font-family:'Cormorant Garamond', serif; font-size:2.2rem; color:#fff; font-weight:600; line-height:1.1; margin-bottom:6px;">
        <?= htmlspecialchars($hotel['name']) ?>
      </h1>
      <div style="font-size:0.85rem; color:var(--text-muted); font-style:italic;">
        "<?= htmlspecialchars($hotel['tagline'] ?? '') ?>"
      </div>
    </div>
  </div>

  <div style="display:flex; flex-direction:column; gap:10px; align-items:flex-end;">
    <?php if ($isActive): ?>
      <div style="background:rgba(16,185,129,0.18); color:#10b981; border:1px solid #10b981; padding:6px 14px; border-radius:20px; font-size:0.72rem; font-weight:600; display:flex; align-items:center; gap:6px;">
        <span style="width:7px; height:7px; border-radius:50%; background:#10b981;"></span> ACTUELLEMENT EN PRODUCTION
      </div>
      <a href="../index.php" target="_blank" class="btn-master-primary" style="font-size:0.78rem; padding:9px 18px;">
        <i class="fas fa-external-link-alt"></i> Voir le Site en Direct
      </a>
    <?php else: ?>
      <a href="index.php?action=activate&id=<?= urlencode($hotel['id']) ?>" class="btn-master-primary" style="font-size:0.78rem; padding:9px 18px;">
        <i class="fas fa-power-off"></i> Activer sur le Site Web
      </a>
    <?php endif; ?>
    <a href="edit.php?id=<?= urlencode($hotel['id']) ?>" class="btn-master-secondary" style="font-size:0.75rem; padding:7px 14px;">
      <i class="fas fa-edit"></i> Modifier cette configuration
    </a>
  </div>
</div>

<!-- GRILLE DE REVUE COMPLÈTE -->
<div class="review-grid">

  <!-- COLONNE GAUCHE (DÉTAILS COMPLETS) -->
  <div>

    <!-- 1. IDENTITÉ & STORYTELLING -->
    <div class="review-box">
      <div class="review-box-title"><i class="fas fa-fingerprint"></i> 1. Identité & Storytelling</div>
      <table class="info-table">
        <tr>
          <td class="label-col">Nom Officiel</td>
          <td class="val-col"><?= htmlspecialchars($hotel['name']) ?></td>
        </tr>
        <tr>
          <td class="label-col">Nom Court / Branding</td>
          <td class="val-col"><?= htmlspecialchars($hotel['short_name']) ?></td>
        </tr>
        <tr>
          <td class="label-col">Initiales Blason</td>
          <td class="val-col"><span style="font-family:'Cormorant Garamond', serif; font-size:1.2rem; font-weight:700; color:var(--gold-primary);"><?= htmlspecialchars($hotel['initials']) ?></span></td>
        </tr>
        <tr>
          <td class="label-col">Slogan Officiel</td>
          <td class="val-col"><?= htmlspecialchars($hotel['tagline']) ?></td>
        </tr>
        <tr>
          <td class="label-col">Description & Pitch</td>
          <td class="val-col" style="font-size:0.85rem; line-height:1.5; color:rgba(255,255,255,0.85);">
            <?= nl2br(htmlspecialchars($hotel['description'] ?? 'Non renseignée')) ?>
          </td>
        </tr>
      </table>
    </div>

    <!-- 2. COORDONNÉES & CONTACT DIRECT -->
    <div class="review-box">
      <div class="review-box-title"><i class="fab fa-whatsapp" style="color:#25D366;"></i> 2. Coordonnées & Réception Directe</div>
      <table class="info-table">
        <tr>
          <td class="label-col">WhatsApp Réception</td>
          <td class="val-col">
            <a href="https://wa.me/<?= htmlspecialchars($hotel['whatsapp']) ?>" target="_blank" style="color:#25D366; text-decoration:none; font-weight:600; display:inline-flex; align-items:center; gap:6px;">
              <i class="fab fa-whatsapp"></i> +<?= htmlspecialchars($hotel['whatsapp']) ?> (Tester le lien)
            </a>
          </td>
        </tr>
        <tr>
          <td class="label-col">Téléphone Standard</td>
          <td class="val-col"><?= htmlspecialchars($hotel['phone'] ?: 'Non renseigné') ?></td>
        </tr>
        <tr>
          <td class="label-col">Email Réservations</td>
          <td class="val-col"><?= htmlspecialchars($hotel['email'] ?: 'Non renseigné') ?></td>
        </tr>
        <tr>
          <td class="label-col">Email Contact</td>
          <td class="val-col"><?= htmlspecialchars($hotel['contact_email'] ?: 'Non renseigné') ?></td>
        </tr>
        <tr>
          <td class="label-col">Localisation Physique</td>
          <td class="val-col"><?= htmlspecialchars($hotel['location'] ?? '') ?>, <?= htmlspecialchars($hotel['city']) ?> (<?= htmlspecialchars($hotel['country']) ?>)</td>
        </tr>
      </table>
    </div>

    <!-- 3. PARAMÈTRES MÉTIER & FACTURATION -->
    <div class="review-box">
      <div class="review-box-title"><i class="fas fa-file-invoice-dollar"></i> 3. Paramètres Métier & Politiques Hôtelières</div>
      <table class="info-table">
        <tr>
          <td class="label-col">Devise Monétaire</td>
          <td class="val-col"><strong style="color:var(--gold-primary);"><?= htmlspecialchars($hotel['currency']) ?></strong></td>
        </tr>
        <tr>
          <td class="label-col">Taux de TVA</td>
          <td class="val-col"><?= htmlspecialchars($hotel['tva_rate'] ?? '18') ?> %</td>
        </tr>
        <tr>
          <td class="label-col">Taxe de Séjour</td>
          <td class="val-col"><?= htmlspecialchars($hotel['tourist_tax'] ?? '1000') ?> <?= htmlspecialchars($hotel['currency']) ?> / nuitée</td>
        </tr>
        <tr>
          <td class="label-col">Horaires Séjour</td>
          <td class="val-col">Check-in à partir de <strong><?= htmlspecialchars($hotel['checkin_time']) ?></strong> | Check-out jusqu'à <strong><?= htmlspecialchars($hotel['checkout_time']) ?></strong></td>
        </tr>
        <tr>
          <td class="label-col">Préfixes Système</td>
          <td class="val-col">Réservation : <code><?= htmlspecialchars($hotel['ref_prefix']) ?>-YYYY-XXXX</code> | Code Client : <code><?= htmlspecialchars($hotel['client_prefix']) ?>-XXXX</code></td>
        </tr>
      </table>
    </div>

  </div>

  <!-- COLONNE DROITE (CHARTE VISUELLE & PACKAGE .ENV) -->
  <div>

    <!-- CHARTE VISUELLE & SIMULATION -->
    <div class="review-box">
      <div class="review-box-title"><i class="fas fa-palette"></i> Charte Visuelle Attribuée</div>
      
      <div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:12px;">Échantillons de Couleurs :</div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:18px;">
        <div style="background:<?= $colors['primary'] ?>; padding:12px; border-radius:6px; font-size:0.7rem; color:#fff; border:1px solid rgba(255,255,255,0.15);">
          <div>Primaire</div>
          <strong><?= $colors['primary'] ?></strong>
        </div>
        <div style="background:<?= $colors['accent'] ?>; padding:12px; border-radius:6px; font-size:0.7rem; color:#111; font-weight:600;">
          <div>Accent Or</div>
          <strong><?= $colors['accent'] ?></strong>
        </div>
        <div style="background:<?= $colors['dark'] ?>; padding:12px; border-radius:6px; font-size:0.7rem; color:#fff; border:1px solid rgba(255,255,255,0.15);">
          <div>Fond Sombre</div>
          <strong><?= $colors['dark'] ?></strong>
        </div>
        <div style="background:<?= $colors['light'] ?>; padding:12px; border-radius:6px; font-size:0.7rem; color:#111;">
          <div>Fond Clair</div>
          <strong><?= $colors['light'] ?></strong>
        </div>
      </div>

      <div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:8px;">Aperçu des Composants UI :</div>
      <div class="ui-simulation-frame">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
          <div style="font-family:'Cormorant Garamond', serif; font-size:1.1rem; color:<?= $colors['accent'] ?>; font-weight:700;">
            <?= htmlspecialchars($hotel['short_name']) ?>
          </div>
          <span class="sim-button-primary">Réserver</span>
        </div>
        <div class="sim-card-sample">
          <div style="font-size:0.8rem; font-weight:600;">Suite Royale Vue Océan</div>
          <div style="font-size:0.7rem; color:#666; margin-top:2px;">150 000 <?= htmlspecialchars($hotel['currency']) ?> / nuit</div>
        </div>
      </div>
    </div>

    <!-- PACKAGE DE CONFIGURATION .ENV -->
    <div class="review-box">
      <div class="review-box-title"><i class="fas fa-code"></i> Fichier .env Généré</div>
      <div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:10px;">
        Configuration injectable sur le serveur ou cPanel :
      </div>
      <div class="env-code-box">
HOTEL_NAME="<?= addslashes($hotel['name']) ?>"
HOTEL_NAME_SHORT="<?= addslashes($hotel['short_name']) ?>"
HOTEL_INITIALS="<?= addslashes($hotel['initials']) ?>"
HOTEL_CITY="<?= addslashes($hotel['city']) ?>"
HOTEL_WHATSAPP="<?= addslashes($hotel['whatsapp']) ?>"
HOTEL_CURRENCY="<?= addslashes($hotel['currency']) ?>"
THEME_COLOR_PRIMARY="<?= $colors['primary'] ?>"
THEME_COLOR_ACCENT="<?= $colors['accent'] ?>"
      </div>
    </div>

    <!-- MODULES LOGICIELS CONNECTÉS -->
    <div class="review-box">
      <div class="review-box-title"><i class="fas fa-cubes"></i> Modules Opérationnels</div>
      <div style="display:flex; flex-direction:column; gap:8px; font-size:0.82rem;">
        <div style="display:flex; align-items:center; justify-content:space-between; color:#34d399;">
          <span><i class="fas fa-check-circle"></i> Réservations & Planning</span>
          <span style="font-size:0.68rem; background:rgba(16,185,129,0.15); padding:2px 8px; border-radius:10px;">Actif</span>
        </div>
        <div style="display:flex; align-items:center; justify-content:space-between; color:#34d399;">
          <span><i class="fas fa-check-circle"></i> Room Service Digital</span>
          <span style="font-size:0.68rem; background:rgba(16,185,129,0.15); padding:2px 8px; border-radius:10px;">Actif</span>
        </div>
        <div style="display:flex; align-items:center; justify-content:space-between; color:#34d399;">
          <span><i class="fas fa-check-circle"></i> Devis Événements & Congrès</span>
          <span style="font-size:0.68rem; background:rgba(16,185,129,0.15); padding:2px 8px; border-radius:10px;">Actif</span>
        </div>
        <div style="display:flex; align-items:center; justify-content:space-between; color:#34d399;">
          <span><i class="fas fa-check-circle"></i> Espace Client & Factures</span>
          <span style="font-size:0.68rem; background:rgba(16,185,129,0.15); padding:2px 8px; border-radius:10px;">Actif</span>
        </div>
      </div>
    </div>

  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
