<?php
/**
 * ════════════════════════════════════════════════════════
 * HOSPITOS SETUP WIZARD — Assistant de Déploiement Nouvel Hôtel
 * ════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/includes/HotelManager.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hotelName = trim($_POST['name'] ?? '');
    if (empty($hotelName)) {
        $error = "Veuillez renseigner au moins le nom complet de l'établissement.";
    } else {
        $data = [
            'name'           => $hotelName,
            'short_name'     => trim($_POST['short_name'] ?? $hotelName),
            'initials'       => strtoupper(trim($_POST['initials'] ?? 'HTL')),
            'tagline'        => trim($_POST['tagline'] ?? 'L\'Excellence et le Confort'),
            'subtitle'       => trim($_POST['subtitle'] ?? ''),
            'description'    => trim($_POST['description'] ?? ''),
            'city'           => trim($_POST['city'] ?? 'Lomé'),
            'country'        => trim($_POST['country'] ?? 'Togo'),
            'location'       => trim($_POST['location'] ?? 'Centre-Ville'),
            'address'        => trim($_POST['address'] ?? ''),
            'phone'          => trim($_POST['phone'] ?? ''),
            'whatsapp'       => preg_replace('/[^0-9]/', '', $_POST['whatsapp'] ?? ''),
            'email'          => trim($_POST['email'] ?? ''),
            'contact_email'  => trim($_POST['contact_email'] ?? ($_POST['email'] ?? '')),
            'currency'       => trim($_POST['currency'] ?? 'FCFA'),
            'ref_prefix'     => strtoupper(trim($_POST['ref_prefix'] ?? 'HTL')),
            'client_prefix'  => strtoupper(trim($_POST['client_prefix'] ?? 'CLI')),
            'checkin_time'   => trim($_POST['checkin_time'] ?? '14:00'),
            'checkout_time'  => trim($_POST['checkout_time'] ?? '12:00'),
            'tva_rate'       => trim($_POST['tva_rate'] ?? '18'),
            'tourist_tax'    => trim($_POST['tourist_tax'] ?? '1000'),
            'theme_preset'   => $_POST['theme_preset'] ?? 'emerald_gold',
            'set_active'     => !empty($_POST['set_active']),
            'notes'          => trim($_POST['notes'] ?? ''),
        ];

        $newId = HotelManager::saveHotel($data);
        header('Location: hotel-view.php?id=' . urlencode($newId) . '&created=1');
        exit;
    }
}

$pageTitle = "Déployer un Nouvel Hôtel — Setup Wizard";
require_once __DIR__ . '/includes/header.php';
?>

<style>
  .wizard-header {
    text-align: center;
    margin-bottom: 36px;
  }
  .wizard-step-bar {
    display: flex;
    justify-content: center;
    gap: 16px;
    margin-bottom: 36px;
    flex-wrap: wrap;
  }
  .step-pill {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--bg-surface);
    border: 1px solid var(--border-master);
    padding: 8px 18px;
    border-radius: 30px;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--text-muted);
  }
  .step-pill.active {
    background: rgba(201, 168, 76, 0.15);
    border-color: var(--gold-primary);
    color: var(--gold-primary);
  }
  .step-num {
    width: 22px; height: 22px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 700;
  }
  .step-pill.active .step-num {
    background: var(--gold-primary);
    color: #111;
  }

  .form-section {
    background: var(--bg-surface);
    border: 1px solid var(--border-master);
    border-radius: 14px;
    padding: 32px;
    margin-bottom: 28px;
  }
  .section-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.5rem;
    color: var(--gold-primary);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .section-desc {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-bottom: 24px;
  }

  .form-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
  }
  .form-grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
  }
  @media(max-width:768px){
    .form-grid-2, .form-grid-3 { grid-template-columns: 1fr; }
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;
  }
  .form-group label {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--text-muted);
    font-weight: 500;
  }
  .form-group label span.req { color: var(--gold-primary); }

  .form-control {
    background: rgba(8, 16, 12, 0.7);
    border: 1px solid rgba(201, 168, 76, 0.3);
    border-radius: 6px;
    padding: 11px 14px;
    color: #fff;
    font-family: 'Jost', sans-serif;
    font-size: 0.9rem;
    transition: all 0.2s;
  }
  .form-control:focus {
    outline: none;
    border-color: var(--gold-primary);
    box-shadow: 0 0 0 3px rgba(201, 168, 76, 0.15);
    background: rgba(12, 24, 18, 0.95);
  }

  /* ── THEME PRESETS SELECTOR ── */
  .theme-presets-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 16px;
    margin-top: 14px;
  }
  .theme-option-card {
    background: rgba(0, 0, 0, 0.3);
    border: 1.5px solid rgba(255, 255, 255, 0.12);
    border-radius: 10px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.25s ease;
    position: relative;
  }
  .theme-option-card:hover {
    border-color: rgba(201, 168, 76, 0.6);
    transform: translateY(-2px);
  }
  .theme-option-card input[type="radio"] {
    position: absolute;
    top: 14px; right: 14px;
    accent-color: var(--gold-primary);
    width: 18px; height: 18px;
  }
  .theme-option-card.selected {
    border-color: var(--gold-primary);
    background: rgba(201, 168, 76, 0.1);
    box-shadow: 0 4px 15px rgba(201, 168, 76, 0.15);
  }
  .theme-palette-bar {
    display: flex;
    height: 28px;
    border-radius: 6px;
    overflow: hidden;
    margin: 10px 0 12px;
    border: 1px solid rgba(255,255,255,0.2);
  }
</style>

<div class="wizard-header">
  <a href="index.php" style="color:var(--text-muted); text-decoration:none; font-size:0.8rem; display:inline-flex; align-items:center; gap:6px; margin-bottom:12px;">
    <i class="fas fa-arrow-left"></i> Retour au Hub Multi-Hôtels
  </a>
  <h1 style="font-family:'Cormorant Garamond', serif; font-size:2.3rem; color:#fff; font-weight:600;">
    Assistant de Déploiement Nouvel Hôtel
  </h1>
  <p style="color:var(--text-muted); font-size:0.9rem;">
    Configurez un nouvel établissement en quelques instants pour l'intégrer à votre suite HospitOS.
  </p>
</div>

<!-- BARRE D'ÉTAPES -->
<div class="wizard-step-bar">
  <div class="step-pill active"><span class="step-num">1</span> Identité & Marque</div>
  <div class="step-pill active"><span class="step-num">2</span> Charte Graphique</div>
  <div class="step-pill active"><span class="step-num">3</span> Coordonnées & WhatsApp</div>
  <div class="step-pill active"><span class="step-num">4</span> Politiques & Tarifs</div>
</div>

<?php if (!empty($error)): ?>
  <div style="background:rgba(239,68,68,0.15); border:1px solid #ef4444; color:#f87171; padding:14px 20px; border-radius:8px; margin-bottom:24px;">
    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<form action="create.php" method="POST">

  <!-- ══════════════════════════════════════════
       SECTION 1 : IDENTITÉ & MARQUE
  ══════════════════════════════════════════ -->
  <div class="form-section">
    <div class="section-title"><i class="fas fa-crown"></i> 1. Identité de l'Établissement & Monogramme</div>
    <div class="section-desc">Définissez le nom, le slogan et les initiales qui s'afficheront sur le blason et dans toute l'interface.</div>

    <div class="form-grid-2">
      <div class="form-group">
        <label>Nom Complet de l'Hôtel <span class="req">*</span></label>
        <input type="text" name="name" class="form-control" placeholder="ex: Grand Hôtel du Golfe & Spa" required>
      </div>

      <div class="form-group">
        <label>Nom Court / Usuel</label>
        <input type="text" name="short_name" class="form-control" placeholder="ex: Grand Hôtel du Golfe">
      </div>
    </div>

    <div class="form-grid-3">
      <div class="form-group">
        <label>Initiales du Blason (2 ou 3 lettres) <span class="req">*</span></label>
        <input type="text" name="initials" class="form-control" placeholder="ex: GHG" maxlength="4" style="text-transform:uppercase; font-weight:700;">
      </div>

      <div class="form-group">
        <label>Ville</label>
        <input type="text" name="city" class="form-control" placeholder="ex: Lomé" value="Lomé">
      </div>

      <div class="form-group">
        <label>Pays</label>
        <input type="text" name="country" class="form-control" placeholder="ex: Togo" value="Togo">
      </div>
    </div>

    <div class="form-group">
      <label>Slogan / Devise de l'Hôtel</label>
      <input type="text" name="tagline" class="form-control" placeholder="ex: L'Élégance au Cœur de la Cité">
    </div>

    <div class="form-group">
      <label>Histoire / Présentation Courte (Page d'accueil & À propos)</label>
      <textarea name="description" class="form-control" rows="3" placeholder="Une invitation au voyage et au raffinement au cœur de..."></textarea>
    </div>
  </div>

  <!-- ══════════════════════════════════════════
       SECTION 2 : CHARTE GRAPHIQUE & THÈME
  ══════════════════════════════════════════ -->
  <div class="form-section">
    <div class="section-title"><i class="fas fa-palette"></i> 2. Charte Graphique & Palette de Couleurs</div>
    <div class="section-desc">Choisissez l'univers visuel qui correspond le mieux au positionnement de cet hôtel.</div>

    <div class="theme-presets-grid">
      <?php foreach (HotelManager::$THEME_PRESETS as $key => $preset): ?>
        <label class="theme-option-card <?= $key === 'emerald_gold' ? 'selected' : '' ?>">
          <input type="radio" name="theme_preset" value="<?= $key ?>" <?= $key === 'emerald_gold' ? 'checked' : '' ?> onchange="updateThemeSelection(this)">
          <div style="font-weight:600; color:#fff; font-size:0.95rem;"><?= htmlspecialchars($preset['name']) ?></div>
          <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;"><?= htmlspecialchars($preset['desc']) ?></div>
          
          <div class="theme-palette-bar">
            <div style="flex:2; background:<?= $preset['primary'] ?>;" title="Primaire"></div>
            <div style="flex:1; background:<?= $preset['accent'] ?>;" title="Or / Accent"></div>
            <div style="flex:1; background:<?= $preset['dark'] ?>;" title="Sombre"></div>
            <div style="flex:1; background:<?= $preset['light'] ?>;" title="Clair"></div>
          </div>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ══════════════════════════════════════════
       SECTION 3 : COORDONNÉES & WHATSAPP
  ══════════════════════════════════════════ -->
  <div class="form-section">
    <div class="section-title"><i class="fab fa-whatsapp" style="color:#25D366;"></i> 3. Coordonnées & WhatsApp Réception Direct</div>
    <div class="section-desc">Les coordonnées utilisées pour les réservations, le Room Service et les confirmations clients.</div>

    <div class="form-grid-2">
      <div class="form-group">
        <label>Numéro WhatsApp de la Réception (avec indicatif sans le +) <span class="req">*</span></label>
        <input type="text" name="whatsapp" class="form-control" placeholder="ex: 22890112233" required>
        <small style="color:var(--text-muted); font-size:0.7rem;">Exemple : 22890000000 pour le Togo, 22507000000 pour la Côte d'Ivoire.</small>
      </div>

      <div class="form-group">
        <label>Téléphone Standard Réception</label>
        <input type="text" name="phone" class="form-control" placeholder="ex: +228 22 20 00 00">
      </div>
    </div>

    <div class="form-grid-2">
      <div class="form-group">
        <label>Email des Réservations</label>
        <input type="email" name="email" class="form-control" placeholder="ex: reservation@mon-hotel.com">
      </div>

      <div class="form-group">
        <label>Email Général de Contact</label>
        <input type="email" name="contact_email" class="form-control" placeholder="ex: contact@mon-hotel.com">
      </div>
    </div>

    <div class="form-group">
      <label>Adresse Physique & Quartier</label>
      <input type="text" name="address" class="form-control" placeholder="ex: Boulevard de la Marina, Face à l'Océan">
    </div>
  </div>

  <!-- ══════════════════════════════════════════
       SECTION 4 : POLITIQUES & TARIFS
  ══════════════════════════════════════════ -->
  <div class="form-section">
    <div class="section-title"><i class="fas fa-sliders-h"></i> 4. Politiques du Séjour, Devise & Fiscalité</div>
    <div class="section-desc">Configurez la devise monétaire et les horaires de fonctionnement de l'établissement.</div>

    <div class="form-grid-3">
      <div class="form-group">
        <label>Devise Principale</label>
        <select name="currency" class="form-control">
          <option value="FCFA" selected>FCFA (Franc CFA)</option>
          <option value="EUR">EUR (€ Euro)</option>
          <option value="USD">USD ($ Dollar)</option>
          <option value="GHS">GHS (Cedi Ghanéen)</option>
        </select>
      </div>

      <div class="form-group">
        <label>Heure de Check-In</label>
        <input type="text" name="checkin_time" class="form-control" value="14:00" placeholder="14:00">
      </div>

      <div class="form-group">
        <label>Heure de Check-Out</label>
        <input type="text" name="checkout_time" class="form-control" value="12:00" placeholder="12:00">
      </div>
    </div>

    <div class="form-grid-2">
      <div class="form-group">
        <label>Taux de TVA (%)</label>
        <input type="number" name="tva_rate" class="form-control" value="18" min="0" max="100">
      </div>

      <div class="form-group">
        <label>Taxe de Séjour forfaitaire par nuitée</label>
        <input type="number" name="tourist_tax" class="form-control" value="1000" min="0">
      </div>
    </div>

    <div class="form-grid-2">
      <div class="form-group">
        <label>Préfixe Références Réservation</label>
        <input type="text" name="ref_prefix" class="form-control" placeholder="ex: HTL" maxlength="6" style="text-transform:uppercase;">
      </div>

      <div class="form-group">
        <label>Préfixe Codes Clients</label>
        <input type="text" name="client_prefix" class="form-control" placeholder="ex: CLI" maxlength="6" style="text-transform:uppercase;">
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════
       SECTION 5 : ACTIVATION & FINALISATION
  ══════════════════════════════════════════ -->
  <div class="form-section" style="background:rgba(20, 44, 34, 0.85); border-color:var(--gold-primary);">
    <div class="section-title"><i class="fas fa-rocket"></i> 5. Activation & Déploiement Immédiat</div>
    
    <label style="display:flex; align-items:center; gap:12px; cursor:pointer; padding:14px; background:rgba(0,0,0,0.3); border-radius:8px; margin-bottom:16px;">
      <input type="checkbox" name="set_active" value="1" checked style="width:20px; height:20px; accent-color:var(--gold-primary);">
      <div>
        <strong style="color:#fff; font-size:0.95rem;">Activer cet hôtel immédiatement en production sur le site web</strong>
        <div style="font-size:0.75rem; color:var(--text-muted);">Le fichier de configuration .env sera synchronisé dès la validation du formulaire.</div>
      </div>
    </label>

    <div class="form-group">
      <label>Notes & Commentaires internes pour ce client / hôtel</label>
      <input type="text" name="notes" class="form-control" placeholder="ex: Contact gérant : M. Koffi / Contrat signé le 15 août">
    </div>

    <div style="display:flex; justify-content:flex-end; gap:16px; margin-top:24px;">
      <a href="index.php" class="btn-master-secondary">Annuler</a>
      <button type="submit" class="btn-master-primary">
        <i class="fas fa-check-circle"></i> Enregistrer & Déployer l'Hôtel
      </button>
    </div>
  </div>

</form>

<script>
  function updateThemeSelection(radio) {
    document.querySelectorAll('.theme-option-card').forEach(c => c.classList.remove('selected'));
    radio.closest('.theme-option-card').classList.add('selected');
  }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
