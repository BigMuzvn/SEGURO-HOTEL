<?php
/**
 * ════════════════════════════════════════════════════════
 * HOSPITOS — MODIFICATION DE CONFIGURATION D'HÔTEL
 * ════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/includes/HotelManager.php';

$id = $_GET['id'] ?? ($_POST['id'] ?? '');
$hotel = HotelManager::getHotel($id);

if (!$hotel) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hotelName = trim($_POST['name'] ?? '');
    if (empty($hotelName)) {
        $error = "Le nom de l'établissement ne peut pas être vide.";
    } else {
        $data = [
            'id'             => $hotel['id'],
            'name'           => $hotelName,
            'short_name'     => trim($_POST['short_name'] ?? $hotelName),
            'initials'       => strtoupper(trim($_POST['initials'] ?? 'HTL')),
            'tagline'        => trim($_POST['tagline'] ?? ''),
            'subtitle'       => trim($_POST['subtitle'] ?? ''),
            'description'    => trim($_POST['description'] ?? ''),
            'city'           => trim($_POST['city'] ?? 'Lomé'),
            'country'        => trim($_POST['country'] ?? 'Togo'),
            'location'       => trim($_POST['location'] ?? ''),
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
            'theme_preset'   => $_POST['theme_preset'] ?? ($hotel['theme_preset'] ?? 'custom'),
            'theme_primary'  => trim($_POST['theme_primary'] ?? ($hotel['theme_colors']['primary'] ?? '#143323')),
            'theme_accent'   => trim($_POST['theme_accent'] ?? ($hotel['theme_colors']['accent'] ?? '#c9a84c')),
            'theme_dark'     => trim($_POST['theme_dark'] ?? ($hotel['theme_colors']['dark'] ?? '#07130c')),
            'theme_light'    => trim($_POST['theme_light'] ?? ($hotel['theme_colors']['light'] ?? '#fbf9f4')),
            'set_active'     => !empty($_POST['set_active']) || !empty($hotel['is_active']),
            'notes'          => trim($_POST['notes'] ?? ''),
        ];

        HotelManager::saveHotel($data);
        header('Location: hotel-view.php?id=' . urlencode($hotel['id']) . '&updated=1');
        exit;
    }
}

$colors = $hotel['theme_colors'] ?? HotelManager::$THEME_PRESETS['emerald_gold'];
$primary = $colors['primary'] ?? '#143323';
$accent  = $colors['accent']  ?? '#c9a84c';
$dark    = $colors['dark']    ?? '#07130c';
$light   = $colors['light']   ?? '#fbf9f4';

$pageTitle = "Modifier — " . htmlspecialchars($hotel['name']);
require_once __DIR__ . '/includes/header.php';
?>

<style>
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

  .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
  .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
  .form-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
  @media(max-width:992px){ .form-grid-4 { grid-template-columns: 1fr 1fr; } }
  @media(max-width:768px){ .form-grid-2, .form-grid-3, .form-grid-4 { grid-template-columns: 1fr; } }

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
  }

  /* ── 4 COLOR PICKERS BOXES ── */
  .color-picker-card {
    background: rgba(0, 0, 0, 0.35);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    transition: all 0.25s ease;
  }
  .color-picker-card:hover {
    border-color: rgba(201, 168, 76, 0.5);
    transform: translateY(-2px);
  }
  .color-role-title {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-weight: 700;
    color: var(--gold-primary);
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .color-role-desc {
    font-size: 0.7rem;
    color: var(--text-muted);
    line-height: 1.35;
    min-height: 34px;
  }
  .color-input-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(10, 20, 15, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 6px;
    padding: 4px 8px;
  }
  .color-input-wrap input[type="color"] {
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    background: transparent;
    padding: 0;
  }
  .color-input-wrap input[type="text"] {
    flex: 1;
    background: transparent;
    border: none;
    color: #fff;
    font-family: monospace;
    font-size: 0.88rem;
    font-weight: 600;
    text-transform: uppercase;
    outline: none;
  }

  /* ── PRESETS INSPIRATION CARDS ── */
  .presets-strip {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
    margin-bottom: 24px;
  }
  .preset-pill-card {
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 8px;
    padding: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
  }
  .preset-pill-card:hover {
    border-color: var(--gold-primary);
    background: rgba(201, 168, 76, 0.08);
  }
  .preset-pill-name {
    font-size: 0.76rem;
    font-weight: 600;
    color: #fff;
    margin-bottom: 8px;
  }
  .preset-mini-palette {
    display: flex;
    height: 16px;
    border-radius: 4px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.15);
  }

  /* ── LIVE INTERACTIVE SIMULATOR ── */
  .live-simulator-container {
    margin-top: 24px;
    border: 1px solid rgba(201, 168, 76, 0.3);
    border-radius: 12px;
    overflow: hidden;
    background: #000;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
  }
  .simulator-bar {
    background: #0c1611;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    padding: 8px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.7rem;
    color: var(--gold-primary);
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
  }
  .sim-view {
    padding: 24px;
    transition: background-color 0.3s;
  }
</style>

<div style="margin-bottom:28px;">
  <a href="hotel-view.php?id=<?= urlencode($hotel['id']) ?>" style="color:var(--text-muted); text-decoration:none; font-size:0.8rem; display:inline-flex; align-items:center; gap:6px; margin-bottom:12px;">
    <i class="fas fa-arrow-left"></i> Retour à la fiche de l'hôtel
  </a>
  <h1 style="font-family:'Cormorant Garamond', serif; font-size:2.2rem; color:#fff; font-weight:600;">
    Modifier la Configuration — <?= htmlspecialchars($hotel['name']) ?>
  </h1>
  <p style="color:var(--text-muted); font-size:0.88rem;">
    Ajustez les paramètres d'identité, les 4 codes couleurs de la charte ou les coordonnées de l'hôtel.
  </p>
</div>

<?php if (!empty($error)): ?>
  <div style="background:rgba(239,68,68,0.15); border:1px solid #ef4444; color:#f87171; padding:14px 20px; border-radius:8px; margin-bottom:24px;">
    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<form action="edit.php" method="POST" id="hotelForm">
  <input type="hidden" name="id" value="<?= htmlspecialchars($hotel['id']) ?>">

  <!-- SECTION 1 : IDENTITÉ -->
  <div class="form-section">
    <div class="section-title"><i class="fas fa-crown"></i> 1. Identité de l'Établissement</div>
    <div class="section-desc">Nom, slogan et initiales qui personnalisent le monogramme du site.</div>

    <div class="form-grid-2">
      <div class="form-group">
        <label>Nom Complet de l'Hôtel <span class="req">*</span></label>
        <input type="text" name="name" id="input_name" class="form-control" value="<?= htmlspecialchars($hotel['name']) ?>" required oninput="updateSimHotelName()">
      </div>

      <div class="form-group">
        <label>Nom Court / Usuel</label>
        <input type="text" name="short_name" id="input_short_name" class="form-control" value="<?= htmlspecialchars($hotel['short_name']) ?>" oninput="updateSimHotelName()">
      </div>
    </div>

    <div class="form-grid-3">
      <div class="form-group">
        <label>Initiales du Blason <span class="req">*</span></label>
        <input type="text" name="initials" id="input_initials" class="form-control" value="<?= htmlspecialchars($hotel['initials']) ?>" maxlength="4" style="text-transform:uppercase; font-weight:700;" oninput="updateSimInitials()">
      </div>

      <div class="form-group">
        <label>Ville d'Implantation <span class="req">*</span></label>
        <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($hotel['city']) ?>" required>
      </div>

      <div class="form-group">
        <label>Pays</label>
        <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($hotel['country']) ?>">
      </div>
    </div>

    <div class="form-group">
      <label>Slogan / Devise</label>
      <input type="text" name="tagline" id="input_tagline" class="form-control" value="<?= htmlspecialchars($hotel['tagline']) ?>" oninput="updateSimTagline()">
    </div>

    <div class="form-group">
      <label>Histoire / Présentation Courte</label>
      <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($hotel['description'] ?? '') ?></textarea>
    </div>
  </div>

  <!-- SECTION 2 : 4 CODES COULEURS FONDAMENTAUX -->
  <div class="form-section">
    <div class="section-title"><i class="fas fa-palette"></i> 2. Charte Graphique — Les 4 Codes Couleurs Fondamentaux</div>
    <div class="section-desc">
      Personnalisez librement les 4 codes couleurs hexadécimaux (#RRGGBB). L'ensemble du site web, des textes, des arrière-plans et des boutons s'adaptera instantanément.
    </div>

    <!-- PALETTES D'INSPIRATION RAPIDE (1 CLIC) -->
    <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.1em; margin-bottom:10px; font-weight:600;">
      <i class="fas fa-magic"></i> Palettes de Prestige Prêtes à l'Emploi (cliquez pour pré-remplir les 4 codes) :
    </div>

    <div class="presets-strip">
      <?php foreach (HotelManager::$THEME_PRESETS as $key => $p): ?>
        <div class="preset-pill-card" onclick="applyPreset('<?= $key ?>', '<?= $p['primary'] ?>', '<?= $p['accent'] ?>', '<?= $p['dark'] ?>', '<?= $p['light'] ?>')">
          <div class="preset-pill-name"><?= htmlspecialchars($p['name']) ?></div>
          <div class="preset-mini-palette">
            <div style="flex:2; background:<?= $p['primary'] ?>;" title="Primaire"></div>
            <div style="flex:1.5; background:<?= $p['accent'] ?>;" title="Accent"></div>
            <div style="flex:1; background:<?= $p['dark'] ?>;" title="Sombre"></div>
            <div style="flex:1; background:<?= $p['light'] ?>;" title="Clair"></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <input type="hidden" name="theme_preset" id="theme_preset_input" value="<?= htmlspecialchars($hotel['theme_preset'] ?? 'custom') ?>">

    <!-- LES 4 SÉLECTEURS DE COULEURS LIBRES -->
    <div class="form-grid-4">
      
      <!-- 1. PRIMAIRE -->
      <div class="color-picker-card">
        <div class="color-role-title"><i class="fas fa-square" id="icon_primary"></i> 1. Primaire (Identité)</div>
        <div class="color-role-desc">En-têtes sombres, boutons principaux, barres de navigation et identité de marque.</div>
        <div class="color-input-wrap">
          <input type="color" id="picker_primary" value="<?= htmlspecialchars($primary) ?>" oninput="syncColorInput('primary', this.value)">
          <input type="text" name="theme_primary" id="text_primary" value="<?= htmlspecialchars($primary) ?>" maxlength="7" oninput="syncPickerInput('primary', this.value)">
        </div>
      </div>

      <!-- 2. ACCENTUATION -->
      <div class="color-picker-card">
        <div class="color-role-title"><i class="fas fa-gem" id="icon_accent"></i> 2. Accentuation & Métal</div>
        <div class="color-role-desc">Boutons de réservation, blason, étoiles, ornements & bordures (Or, Argent, Cuivre, etc.).</div>
        <div class="color-input-wrap">
          <input type="color" id="picker_accent" value="<?= htmlspecialchars($accent) ?>" oninput="syncColorInput('accent', this.value)">
          <input type="text" name="theme_accent" id="text_accent" value="<?= htmlspecialchars($accent) ?>" maxlength="7" oninput="syncPickerInput('accent', this.value)">
        </div>
      </div>

      <!-- 3. FOND SOMBRE -->
      <div class="color-picker-card">
        <div class="color-role-title"><i class="fas fa-moon" id="icon_dark"></i> 3. Fond Sombre (Nuit)</div>
        <div class="color-role-desc">Pied de page (footer), modales, arrière-plan du Hero et sections sombres.</div>
        <div class="color-input-wrap">
          <input type="color" id="picker_dark" value="<?= htmlspecialchars($dark) ?>" oninput="syncColorInput('dark', this.value)">
          <input type="text" name="theme_dark" id="text_dark" value="<?= htmlspecialchars($dark) ?>" maxlength="7" oninput="syncPickerInput('dark', this.value)">
        </div>
      </div>

      <!-- 4. FOND CLAIR -->
      <div class="color-picker-card">
        <div class="color-role-title"><i class="fas fa-sun" id="icon_light"></i> 4. Fond Clair (Surface)</div>
        <div class="color-role-desc">Arrière-plan global du site, cartes claires, zones de lecture (remplace le blanc pur).</div>
        <div class="color-input-wrap">
          <input type="color" id="picker_light" value="<?= htmlspecialchars($light) ?>" oninput="syncColorInput('light', this.value)">
          <input type="text" name="theme_light" id="text_light" value="<?= htmlspecialchars($light) ?>" maxlength="7" oninput="syncPickerInput('light', this.value)">
        </div>
      </div>

    </div>

    <!-- SIMULATEUR LIVE EN TEMPS RÉEL -->
    <div class="live-simulator-container">
      <div class="simulator-bar">
        <span><i class="fas fa-eye"></i> Aperçu Interactif en Direct de votre Palette</span>
        <span style="font-weight:400; color:rgba(255,255,255,0.6);">Réagit instantanément à vos 4 couleurs</span>
      </div>

      <div class="sim-view" id="sim_surface_light" style="background:<?= htmlspecialchars($light) ?>; color:#111;">
        
        <!-- Fausse Navbar -->
        <div id="sim_navbar" style="background:<?= htmlspecialchars($primary) ?>; padding:14px 20px; border-radius:8px; display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:2px solid <?= htmlspecialchars($accent) ?>;">
          <div style="display:flex; align-items:center; gap:12px;">
            <div id="sim_crest" style="width:40px; height:40px; border-radius:6px; border:1.5px solid <?= htmlspecialchars($accent) ?>; background:<?= htmlspecialchars($primary) ?>; color:<?= htmlspecialchars($accent) ?>; display:flex; flex-direction:column; align-items:center; justify-content:center; font-family:'Cormorant Garamond', serif; font-weight:700; font-size:1.1rem; line-height:1;">
              <span style="font-size:0.55rem;">👑</span>
              <span id="sim_crest_letters"><?= htmlspecialchars($hotel['initials']) ?></span>
            </div>
            <div>
              <div id="sim_brand_name" style="font-family:'Cormorant Garamond', serif; font-size:1.15rem; font-weight:700; color:#fff;"><?= htmlspecialchars($hotel['name']) ?></div>
              <div id="sim_tagline_text" style="font-size:0.65rem; color:<?= htmlspecialchars($accent) ?>; letter-spacing:0.1em;"><?= htmlspecialchars(strtoupper($hotel['tagline'])) ?></div>
            </div>
          </div>
          <button type="button" id="sim_btn_accent" style="background:<?= htmlspecialchars($accent) ?>; color:#111; border:none; padding:7px 16px; border-radius:4px; font-weight:600; font-size:0.75rem; cursor:pointer;">
            Réserver un Séjour
          </button>
        </div>

        <!-- Fausse Section Carte & Contenu -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px;">
          
          <!-- Carte Surface Claire -->
          <div id="sim_card" style="background:#ffffff; border:1px solid <?= htmlspecialchars($accent) ?>; border-radius:8px; padding:16px;">
            <div id="sim_badge" style="display:inline-block; background:rgba(201,168,76,0.15); color:<?= htmlspecialchars($accent) ?>; border:1px solid <?= htmlspecialchars($accent) ?>; padding:2px 8px; border-radius:20px; font-size:0.65rem; font-weight:600; text-transform:uppercase; margin-bottom:8px;">
              ⭐ Suite Présidentielle
            </div>
            <div id="sim_card_title" style="font-family:'Cormorant Garamond', serif; font-size:1.2rem; font-weight:700; color:<?= htmlspecialchars($primary) ?>; margin-bottom:6px;">
              Prestige & Vue Panoramique
            </div>
            <div style="font-size:0.75rem; color:#666; line-height:1.4; margin-bottom:12px;">
              Surface de détente avec jacuzzi privé, service majordome 24/7 et vue mer imprenable.
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <span id="sim_card_price" style="font-weight:700; font-size:0.95rem; color:<?= htmlspecialchars($primary) ?>;">150 000 <?= htmlspecialchars($hotel['currency']) ?></span>
              <button type="button" id="sim_btn_primary" style="background:<?= htmlspecialchars($primary) ?>; color:#fff; border:none; padding:5px 12px; border-radius:4px; font-size:0.72rem;">
                Découvrir
              </button>
            </div>
          </div>

          <!-- Carte Surface Sombre (Footer preview) -->
          <div id="sim_surface_dark" style="background:<?= htmlspecialchars($dark) ?>; border:1px solid rgba(255,255,255,0.1); border-radius:8px; padding:16px; color:#fff;">
            <div id="sim_footer_title" style="font-family:'Cormorant Garamond', serif; font-size:1.15rem; color:<?= htmlspecialchars($accent) ?>; margin-bottom:6px; font-weight:600;">
              Conciergerie & Réception 24h/24
            </div>
            <div style="font-size:0.72rem; color:rgba(255,255,255,0.7); line-height:1.4; margin-bottom:10px;">
              Une équipe dévouée pour sublimer votre séjour à chaque instant.
            </div>
            <div style="display:flex; gap:8px;">
              <span id="sim_pill_accent" style="background:<?= htmlspecialchars($accent) ?>; color:#111; font-size:0.65rem; font-weight:700; padding:3px 8px; border-radius:4px;">
                WhatsApp Direct
              </span>
              <span style="border:1px solid rgba(255,255,255,0.3); color:#fff; font-size:0.65rem; padding:3px 8px; border-radius:4px;">
                Room Service
              </span>
            </div>
          </div>

        </div>

      </div>
    </div>

  </div>

  <!-- SECTION 3 : COORDONNÉES -->
  <div class="form-section">
    <div class="section-title"><i class="fab fa-whatsapp" style="color:#25D366;"></i> 3. Coordonnées & Réception Directe</div>

    <div class="form-grid-2">
      <div class="form-group">
        <label>Numéro WhatsApp Réception <span class="req">*</span></label>
        <input type="text" name="whatsapp" class="form-control" value="<?= htmlspecialchars($hotel['whatsapp']) ?>" required>
      </div>

      <div class="form-group">
        <label>Téléphone Standard</label>
        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($hotel['phone']) ?>">
      </div>
    </div>

    <div class="form-grid-2">
      <div class="form-group">
        <label>Email Réservations</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($hotel['email']) ?>">
      </div>

      <div class="form-group">
        <label>Email Contact</label>
        <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($hotel['contact_email'] ?? '') ?>">
      </div>
    </div>

    <div class="form-group">
      <label>Adresse Physique & Quartier</label>
      <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($hotel['address'] ?? '') ?>">
    </div>
  </div>

  <!-- SECTION 4 : TARIFS & POLITIQUES -->
  <div class="form-section">
    <div class="section-title"><i class="fas fa-sliders-h"></i> 4. Politiques & Devise</div>

    <div class="form-grid-3">
      <div class="form-group">
        <label>Devise Principale</label>
        <select name="currency" class="form-control">
          <option value="FCFA" <?= ($hotel['currency'] ?? 'FCFA') === 'FCFA' ? 'selected' : '' ?>>FCFA (Franc CFA)</option>
          <option value="EUR" <?= ($hotel['currency'] ?? '') === 'EUR' ? 'selected' : '' ?>>EUR (€ Euro)</option>
          <option value="USD" <?= ($hotel['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD ($ Dollar)</option>
          <option value="GHS" <?= ($hotel['currency'] ?? '') === 'GHS' ? 'selected' : '' ?>>GHS (Cedi)</option>
        </select>
      </div>

      <div class="form-group">
        <label>Heure Check-In</label>
        <input type="text" name="checkin_time" class="form-control" value="<?= htmlspecialchars($hotel['checkin_time'] ?? '14:00') ?>">
      </div>

      <div class="form-group">
        <label>Heure Check-Out</label>
        <input type="text" name="checkout_time" class="form-control" value="<?= htmlspecialchars($hotel['checkout_time'] ?? '12:00') ?>">
      </div>
    </div>

    <div class="form-grid-2">
      <div class="form-group">
        <label>TVA (%)</label>
        <input type="number" name="tva_rate" class="form-control" value="<?= htmlspecialchars($hotel['tva_rate'] ?? '18') ?>" min="0" max="100" step="0.1">
      </div>

      <div class="form-group">
        <label>Taxe de Séjour (FCFA)</label>
        <input type="number" name="tourist_tax" class="form-control" value="<?= htmlspecialchars($hotel['tourist_tax'] ?? '1000') ?>" min="0">
      </div>
    </div>
  </div>

  <div style="display:flex; justify-content:flex-end; gap:16px; margin-bottom:60px;">
    <a href="hotel-view.php?id=<?= urlencode($hotel['id']) ?>" class="btn-master-secondary" style="padding:12px 24px;">Annuler</a>
    <button type="submit" class="btn-master-primary" style="padding:12px 32px; font-size:0.9rem;">
      <i class="fas fa-save"></i> Enregistrer les Modifications
    </button>
  </div>

</form>

<script>
function syncColorInput(type, val) {
  if (!val.startsWith('#')) val = '#' + val;
  document.getElementById('text_' + type).value = val.toLowerCase();
  refreshSimulator();
}

function syncPickerInput(type, val) {
  if (!val.startsWith('#')) val = '#' + val;
  if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
    document.getElementById('picker_' + type).value = val;
    refreshSimulator();
  }
}

function applyPreset(key, primary, accent, dark, light) {
  document.getElementById('theme_preset_input').value = key;
  
  document.getElementById('picker_primary').value = primary;
  document.getElementById('text_primary').value = primary.toLowerCase();

  document.getElementById('picker_accent').value = accent;
  document.getElementById('text_accent').value = accent.toLowerCase();

  document.getElementById('picker_dark').value = dark;
  document.getElementById('text_dark').value = dark.toLowerCase();

  document.getElementById('picker_light').value = light;
  document.getElementById('text_light').value = light.toLowerCase();

  refreshSimulator();
}

function refreshSimulator() {
  const primary = document.getElementById('text_primary').value || '#143323';
  const accent  = document.getElementById('text_accent').value  || '#c9a84c';
  const dark    = document.getElementById('text_dark').value    || '#07130c';
  const light   = document.getElementById('text_light').value   || '#fbf9f4';

  // Surface Claire
  const simSurfaceLight = document.getElementById('sim_surface_light');
  if (simSurfaceLight) simSurfaceLight.style.backgroundColor = light;

  // Navbar
  const simNavbar = document.getElementById('sim_navbar');
  if (simNavbar) {
    simNavbar.style.backgroundColor = primary;
    simNavbar.style.borderBottomColor = accent;
  }

  // Blason
  const simCrest = document.getElementById('sim_crest');
  if (simCrest) {
    simCrest.style.borderColor = accent;
    simCrest.style.backgroundColor = primary;
    simCrest.style.color = accent;
  }

  // Slogan & Titres
  const simTagline = document.getElementById('sim_tagline_text');
  if (simTagline) simTagline.style.color = accent;

  // Boutons Accent
  const simBtnAccent = document.getElementById('sim_btn_accent');
  if (simBtnAccent) simBtnAccent.style.backgroundColor = accent;

  const simPillAccent = document.getElementById('sim_pill_accent');
  if (simPillAccent) simPillAccent.style.backgroundColor = accent;

  // Carte
  const simCard = document.getElementById('sim_card');
  if (simCard) simCard.style.borderColor = accent;

  const simBadge = document.getElementById('sim_badge');
  if (simBadge) {
    simBadge.style.color = accent;
    simBadge.style.borderColor = accent;
  }

  const simCardTitle = document.getElementById('sim_card_title');
  if (simCardTitle) simCardTitle.style.color = primary;

  const simCardPrice = document.getElementById('sim_card_price');
  if (simCardPrice) simCardPrice.style.color = primary;

  const simBtnPrimary = document.getElementById('sim_btn_primary');
  if (simBtnPrimary) simBtnPrimary.style.backgroundColor = primary;

  // Surface Sombre
  const simSurfaceDark = document.getElementById('sim_surface_dark');
  if (simSurfaceDark) simSurfaceDark.style.backgroundColor = dark;

  const simFooterTitle = document.getElementById('sim_footer_title');
  if (simFooterTitle) simFooterTitle.style.color = accent;
}

function updateSimHotelName() {
  const name = document.getElementById('input_name').value || document.getElementById('input_short_name').value || 'Nom de l\'Hôtel';
  const target = document.getElementById('sim_brand_name');
  if (target) target.innerText = name;
}

function updateSimInitials() {
  const initials = document.getElementById('input_initials').value || 'HTL';
  const target = document.getElementById('sim_crest_letters');
  if (target) target.innerText = initials.toUpperCase();
}

function updateSimTagline() {
  const tagline = document.getElementById('input_tagline').value || 'L\'EXCELLENCE & LE CONFORT';
  const target = document.getElementById('sim_tagline_text');
  if (target) target.innerText = tagline.toUpperCase();
}

// Initialisation au chargement
document.addEventListener('DOMContentLoaded', () => {
  refreshSimulator();
  updateSimHotelName();
  updateSimInitials();
  updateSimTagline();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
