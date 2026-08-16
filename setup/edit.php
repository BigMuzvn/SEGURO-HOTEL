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
            'theme_preset'   => $_POST['theme_preset'] ?? ($hotel['theme_preset'] ?? 'emerald_gold'),
            'set_active'     => !empty($_POST['set_active']) || !empty($hotel['is_active']),
            'notes'          => trim($_POST['notes'] ?? ''),
        ];

        HotelManager::saveHotel($data);
        header('Location: hotel-view.php?id=' . urlencode($hotel['id']) . '&updated=1');
        exit;
    }
}

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
  @media(max-width:768px){ .form-grid-2, .form-grid-3 { grid-template-columns: 1fr; } }

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
  .theme-option-card.selected {
    border-color: var(--gold-primary);
    background: rgba(201, 168, 76, 0.1);
  }
  .theme-option-card input[type="radio"] {
    position: absolute;
    top: 14px; right: 14px;
    accent-color: var(--gold-primary);
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

<div style="margin-bottom:28px;">
  <a href="hotel-view.php?id=<?= urlencode($hotel['id']) ?>" style="color:var(--text-muted); text-decoration:none; font-size:0.8rem; display:inline-flex; align-items:center; gap:6px; margin-bottom:12px;">
    <i class="fas fa-arrow-left"></i> Retour à la Fiche de l'Hôtel
  </a>
  <h1 style="font-family:'Cormorant Garamond', serif; font-size:2.2rem; color:#fff; font-weight:600;">
    Modifier la Configuration — <?= htmlspecialchars($hotel['name']) ?>
  </h1>
</div>

<?php if (!empty($error)): ?>
  <div style="background:rgba(239,68,68,0.15); border:1px solid #ef4444; color:#f87171; padding:14px 20px; border-radius:8px; margin-bottom:24px;">
    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<form action="edit.php?id=<?= urlencode($hotel['id']) ?>" method="POST">
  <input type="hidden" name="id" value="<?= htmlspecialchars($hotel['id']) ?>">

  <!-- 1. IDENTITÉ -->
  <div class="form-section">
    <div class="section-title"><i class="fas fa-crown"></i> 1. Identité de l'Établissement</div>
    
    <div class="form-grid-2">
      <div class="form-group">
        <label>Nom Complet de l'Hôtel <span class="req">*</span></label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($hotel['name']) ?>" required>
      </div>

      <div class="form-group">
        <label>Nom Court / Usuel</label>
        <input type="text" name="short_name" class="form-control" value="<?= htmlspecialchars($hotel['short_name']) ?>">
      </div>
    </div>

    <div class="form-grid-3">
      <div class="form-group">
        <label>Initiales du Blason <span class="req">*</span></label>
        <input type="text" name="initials" class="form-control" value="<?= htmlspecialchars($hotel['initials']) ?>" maxlength="4" style="text-transform:uppercase; font-weight:700;">
      </div>

      <div class="form-group">
        <label>Ville</label>
        <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($hotel['city']) ?>">
      </div>

      <div class="form-group">
        <label>Pays</label>
        <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($hotel['country']) ?>">
      </div>
    </div>

    <div class="form-group">
      <label>Slogan Officiel</label>
      <input type="text" name="tagline" class="form-control" value="<?= htmlspecialchars($hotel['tagline']) ?>">
    </div>

    <div class="form-group">
      <label>Description & Storytelling</label>
      <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($hotel['description'] ?? '') ?></textarea>
    </div>
  </div>

  <!-- 2. CHARTE VISUELLE -->
  <div class="form-section">
    <div class="section-title"><i class="fas fa-palette"></i> 2. Charte Graphique & Thème</div>
    
    <div class="theme-presets-grid">
      <?php foreach (HotelManager::$THEME_PRESETS as $key => $preset): ?>
        <?php $isSelected = ($hotel['theme_preset'] ?? 'emerald_gold') === $key; ?>
        <label class="theme-option-card <?= $isSelected ? 'selected' : '' ?>">
          <input type="radio" name="theme_preset" value="<?= $key ?>" <?= $isSelected ? 'checked' : '' ?> onchange="updateThemeSelection(this)">
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

  <!-- 3. COORDONNÉES & WHATSAPP -->
  <div class="form-section">
    <div class="section-title"><i class="fab fa-whatsapp" style="color:#25D366;"></i> 3. Coordonnées & WhatsApp Réception</div>
    
    <div class="form-grid-2">
      <div class="form-group">
        <label>WhatsApp Réception (sans le +) <span class="req">*</span></label>
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
        <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($hotel['contact_email']) ?>">
      </div>
    </div>

    <div class="form-group">
      <label>Adresse Physique</label>
      <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($hotel['address'] ?? '') ?>">
    </div>
  </div>

  <!-- 4. POLITIQUES & DEVISE -->
  <div class="form-section">
    <div class="section-title"><i class="fas fa-sliders-h"></i> 4. Politiques, Devise & Fiscalité</div>
    
    <div class="form-grid-3">
      <div class="form-group">
        <label>Devise Principale</label>
        <select name="currency" class="form-control">
          <option value="FCFA" <?= ($hotel['currency'] ?? '') === 'FCFA' ? 'selected' : '' ?>>FCFA</option>
          <option value="EUR" <?= ($hotel['currency'] ?? '') === 'EUR' ? 'selected' : '' ?>>EUR (€)</option>
          <option value="USD" <?= ($hotel['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD ($)</option>
          <option value="GHS" <?= ($hotel['currency'] ?? '') === 'GHS' ? 'selected' : '' ?>>GHS</option>
        </select>
      </div>

      <div class="form-group">
        <label>Heure de Check-In</label>
        <input type="text" name="checkin_time" class="form-control" value="<?= htmlspecialchars($hotel['checkin_time']) ?>">
      </div>

      <div class="form-group">
        <label>Heure de Check-Out</label>
        <input type="text" name="checkout_time" class="form-control" value="<?= htmlspecialchars($hotel['checkout_time']) ?>">
      </div>
    </div>

    <div class="form-grid-2">
      <div class="form-group">
        <label>Taux TVA (%)</label>
        <input type="number" name="tva_rate" class="form-control" value="<?= htmlspecialchars($hotel['tva_rate'] ?? '18') ?>">
      </div>

      <div class="form-group">
        <label>Taxe de Séjour par nuitée</label>
        <input type="number" name="tourist_tax" class="form-control" value="<?= htmlspecialchars($hotel['tourist_tax'] ?? '1000') ?>">
      </div>
    </div>

    <div class="form-grid-2">
      <div class="form-group">
        <label>Préfixe Référence Réservation</label>
        <input type="text" name="ref_prefix" class="form-control" value="<?= htmlspecialchars($hotel['ref_prefix'] ?? 'HTL') ?>">
      </div>

      <div class="form-group">
        <label>Préfixe Code Client</label>
        <input type="text" name="client_prefix" class="form-control" value="<?= htmlspecialchars($hotel['client_prefix'] ?? 'CLI') ?>">
      </div>
    </div>
  </div>

  <!-- 5. ENREGISTREMENT -->
  <div class="form-section" style="background:rgba(20, 44, 34, 0.85); border-color:var(--gold-primary);">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
      <div style="font-size:0.85rem; color:var(--text-muted);">
        Enregistrer les modifications appliquera directement les nouveaux paramètres.
      </div>
      <div style="display:flex; gap:14px;">
        <a href="hotel-view.php?id=<?= urlencode($hotel['id']) ?>" class="btn-master-secondary">Annuler</a>
        <button type="submit" class="btn-master-primary">
          <i class="fas fa-save"></i> Mettre à Jour la Configuration
        </button>
      </div>
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
