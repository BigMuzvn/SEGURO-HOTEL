<?php
/**
 * ════════════════════════════════════════════════════════
 * ÉVÉNEMENTS & SÉMINAIRES B2B
 * ════════════════════════════════════════════════════════
 */

session_start();
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Mail.php';

$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);

$succes = '';
$erreur = '';
$reference_generee = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_devis'])) {
    $nom_contact        = trim($_POST['nom_contact'] ?? '');
    $entreprise         = trim($_POST['entreprise'] ?? '');
    $email              = trim($_POST['email'] ?? '');
    $telephone          = trim($_POST['telephone'] ?? '');
    $type_evenement     = trim($_POST['type_evenement'] ?? '');
    $espace_souhaite    = trim($_POST['espace_souhaite'] ?? '');
    $date_evenement     = trim($_POST['date_evenement'] ?? '');
    $date_fin           = !empty($_POST['date_fin']) ? trim($_POST['date_fin']) : null;
    $nb_participants    = intval($_POST['nb_participants'] ?? 10);
    $services           = $_POST['services'] ?? [];
    $budget_estime      = trim($_POST['budget_estime'] ?? '');
    $message            = trim($_POST['message'] ?? '');

    if (empty($nom_contact) || empty($email) || empty($telephone) || empty($type_evenement) || empty($date_evenement)) {
        $erreur = "Veuillez remplir tous les champs obligatoires (*).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Format d'adresse email invalide.";
    } else {
        try {
            $reference = 'DEV-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
            $services_json = json_encode($services, JSON_UNESCAPED_UNICODE);

            $stmt = $db->prepare("
                INSERT INTO devis_evenements 
                (reference, nom_contact, entreprise, email, telephone, type_evenement, espace_souhaite, date_evenement, date_fin, nb_participants, services_souhaites, budget_estime, message)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $reference, $nom_contact, $entreprise, $email, $telephone,
                $type_evenement, $espace_souhaite, $date_evenement, $date_fin,
                $nb_participants, $services_json, $budget_estime, $message
            ]);

            $reference_generee = $reference;
            $succes = "Votre demande de devis n° {$reference} a été enregistrée avec succès ! Notre équipe commerciale vous répondra sous 24h ouvrées.";

            // 1. Email de confirmation au prospect
            try {
                Mail::sendEventQuoteConfirmation($email, $nom_contact, $reference, $type_evenement, date('d/m/Y', strtotime($date_evenement)));
            } catch (Exception $mailErr) {
                error_log("Event quote client mail error: " . $mailErr->getMessage());
            }

            // 2. Alerte aux administrateurs
            try {
                $admins = $userModel->getAllAdmins();
                $adminEmails = array_column($admins, 'email');
                Mail::sendAdminNewEventQuoteNotification(
                    $adminEmails,
                    $reference,
                    $nom_contact,
                    $entreprise,
                    $email,
                    $telephone,
                    $type_evenement,
                    $espace_souhaite,
                    date('d/m/Y', strtotime($date_evenement)),
                    $nb_participants
                );
            } catch (Exception $adminMailErr) {
                error_log("Event quote admin mail error: " . $adminMailErr->getMessage());
            }

        } catch (Exception $e) {
            error_log("Erreur enregistrement devis: " . $e->getMessage());
            $erreur = "Une erreur est survenue lors de l'envoi de votre demande. Veuillez réessayer.";
        }
    }
}

include(__DIR__ . '/../layouts/header.php');
?>

<style>
.event-hero {
    background: linear-gradient(rgba(26,58,42,0.82), rgba(13,26,18,0.92)), 
                url('https://images.unsplash.com/photo-1511578314322-379afb476865?w=1920&q=80') center/cover no-repeat;
    padding: 140px 24px 80px;
    text-align: center;
    color: #fff;
    position: relative;
}

.event-hero-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(2.5rem, 5vw, 4rem);
    font-weight: 300;
    margin-bottom: 16px;
    color: #fff;
}

.event-hero-title em {
    font-style: italic;
    color: var(--or-pale);
}

.event-hero-sub {
    font-family: 'Jost', sans-serif;
    font-size: 1.05rem;
    color: rgba(255,255,255,0.85);
    max-width: 680px;
    margin: 0 auto 32px;
    line-height: 1.6;
}

.event-spaces-section {
    max-width: 1200px;
    margin: -40px auto 80px;
    padding: 0 24px;
    position: relative;
    z-index: 10;
}

.spaces-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
    gap: 30px;
}

.space-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    transition: transform 0.3s, box-shadow 0.3s;
    border-bottom: 3px solid var(--or);
}

.space-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 40px rgba(26,58,42,0.15);
}

.space-img {
    height: 220px;
    width: 100%;
    object-fit: cover;
}

.space-body {
    padding: 28px;
}

.space-capa {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--or-texte);
    font-weight: 600;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.space-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.8rem;
    color: var(--vert);
    margin-bottom: 12px;
}

.space-desc {
    color: #555;
    font-size: 0.9rem;
    line-height: 1.6;
    margin-bottom: 18px;
}

.space-features {
    list-style: none;
    padding: 0;
    margin: 0;
    font-size: 0.85rem;
    color: #444;
}

.space-features li {
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.space-features li i {
    color: var(--or);
}

.devis-section {
    max-width: 900px;
    margin: 0 auto 100px;
    padding: 0 24px;
}

.devis-box {
    background: #fff;
    border-radius: 16px;
    padding: 50px 48px;
    box-shadow: 0 15px 50px rgba(0,0,0,0.06);
    border-left: 5px solid var(--vert);
}

.devis-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2.2rem;
    color: var(--vert);
    margin-bottom: 8px;
    text-align: center;
}

.devis-sub {
    text-align: center;
    color: #555;
    font-size: 0.95rem;
    margin-bottom: 40px;
}

.form-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

.form-group-devis {
    margin-bottom: 20px;
}

.form-group-devis label {
    display: block;
    font-family: 'Jost', sans-serif;
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--vert);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.form-control-devis {
    width: 100%;
    padding: 12px 16px;
    border: 1.5px solid rgba(201,168,76,0.35);
    border-radius: 8px;
    font-family: 'Jost', sans-serif;
    font-size: 0.95rem;
    color: #2d3748;
    background: #faf8f3;
    transition: all 0.3s;
    box-sizing: border-box;
}

.form-control-devis:focus {
    outline: none;
    border-color: var(--vert);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(26,58,42,0.1);
}

.services-checkboxes {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 12px;
    background: #faf8f3;
    padding: 18px 20px;
    border-radius: 8px;
    border: 1px solid rgba(201,168,76,0.25);
    margin-bottom: 24px;
}

.service-check-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.88rem;
    color: #333;
    cursor: pointer;
}

.service-check-item input {
    width: 18px;
    height: 18px;
    accent-color: var(--vert);
    cursor: pointer;
}

.btn-event-cta {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    background: var(--or) !important;
    color: var(--noir) !important;
    font-family: 'Jost', sans-serif;
    font-weight: 300;
    font-size: 0.65rem;
    letter-spacing: 0.35em;
    text-transform: uppercase;
    padding: 15px 42px;
    border: 1px solid var(--or);
    text-decoration: none;
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.btn-event-cta:hover {
    background: var(--or-clair) !important;
    border-color: var(--or-clair) !important;
    color: var(--noir) !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(201, 168, 76, 0.25);
}

.btn-submit-devis {
    background: var(--vert) !important;
    color: #ffffff !important;
    border: 1px solid var(--vert);
    padding: 15px 42px;
    font-family: 'Jost', sans-serif;
    font-weight: 300;
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.35em;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    display: block;
    width: 100%;
}

.btn-submit-devis:hover {
    background: var(--vert-clair) !important;
    color: #ffffff !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(26,58,42,0.25);
}

@media (max-width: 768px) {
    .form-grid-2 { grid-template-columns: 1fr; gap: 0; }
    .devis-box { padding: 32px 20px; }
}
</style>

<!-- HERO SECTION -->
<div class="event-hero">
    <span style="text-transform:uppercase; letter-spacing:3px; color:var(--or); font-size:0.8rem; font-weight:600; display:block; margin-bottom:12px;">
        <?= htmlspecialchars(hotel_name()) ?> · Espaces Professionnels &amp; Réceptions
    </span>
    <h1 class="event-hero-title">Événements d'Exception &amp; <em>Séminaires</em></h1>
    <p class="event-hero-sub">
        Des espaces modulables haut de gamme, une technologie audiovisuelle de pointe et une gastronomie raffinée pour faire de vos événements d'affaires et célébrations privées un moment inoubliable.
    </p>
    <a href="#formulaire-devis" class="btn-event-cta">
        <i class="fas fa-file-signature"></i> Demander un devis personnalisé
    </a>
</div>

<!-- SECTION ESPACES & SALONS -->
<div class="event-spaces-section">
    <div class="spaces-grid">
        
        <!-- Espace 1 : Salon Baobab VIP -->
        <div class="space-card">
            <img src="https://images.unsplash.com/photo-1517502884422-41eaead166d4?w=800&q=80" alt="Salon Baobab VIP" class="space-img">
            <div class="space-body">
                <div class="space-capa"><i class="fas fa-users"></i> 10 à 35 Personnes</div>
                <h3 class="space-title">Salon Baobab VIP</h3>
                <p class="space-desc">
                    Idéal pour les conseils d'administration, réunions stratégiques et comités de direction en toute confidentialité.
                </p>
                <ul class="space-features">
                    <li><i class="fas fa-check"></i> Écran 4K 85" &amp; Visioconférence HD</li>
                    <li><i class="fas fa-check"></i> Sièges ergonomiques en cuir &amp; Climatisation</li>
                    <li><i class="fas fa-check"></i> Espace pause-café privatif</li>
                </ul>
            </div>
        </div>

        <!-- Espace 2 : Grand Palais -->
        <div class="space-card">
            <img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800&q=80" alt="Salle Grand Palais" class="space-img">
            <div class="space-body">
                <div class="space-capa"><i class="fas fa-users"></i> Jusqu'à 180 Personnes</div>
                <h3 class="space-title">Salle Grand Palais</h3>
                <p class="space-desc">
                    Plénières, congrès, conférences et banquets de gala avec scène modulable et régie technique intégrée.
                </p>
                <ul class="space-features">
                    <li><i class="fas fa-check"></i> Double vidéoprojection &amp; Sonorisation Bose</li>
                    <li><i class="fas fa-check"></i> Éclairage d'ambiance LED personnalisable</li>
                    <li><i class="fas fa-check"></i> Configuration Théâtre, U ou Banquet</li>
                </ul>
            </div>
        </div>

        <!-- Espace 3 : Terrasse & Jardins -->
        <div class="space-card">
            <img src="https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=800&q=80" alt="Terrasse & Piscine" class="space-img">
            <div class="space-body">
                <div class="space-capa"><i class="fas fa-users"></i> Jusqu'à 250 Personnes</div>
                <h3 class="space-title">Terrasse &amp; Espace Piscine</h3>
                <p class="space-desc">
                    Un cadre féerique au bord de l'eau pour vos cocktails d'entreprise, mariages d'exception et soirées de gala.
                </p>
                <ul class="space-features">
                    <li><i class="fas fa-check"></i> Vue panoramique sur le lac Togo</li>
                    <li><i class="fas fa-check"></i> Bar à cocktails extérieur dédié</li>
                    <li><i class="fas fa-check"></i> Service traiteur gastronomique 5 étoiles</li>
                </ul>
            </div>
        </div>

    </div>
</div>

<!-- FORMULAIRE DE DEVIS -->
<div class="devis-section" id="formulaire-devis">
    <div class="devis-box">
        <h2 class="devis-title">Demande de Devis Express</h2>
        <p class="devis-sub">
            Remplissez ce formulaire sans engagement. Notre équipe Événements vous transmettra une proposition chiffrée détaillée sous 24h ouvrées.
        </p>

        <?php if ($succes): ?>
            <div style="background:rgba(40,167,69,0.1); border-left:4px solid #28a745; padding:20px 24px; border-radius:8px; margin-bottom:30px; color:#155724;">
                <h4 style="margin:0 0 6px 0; font-family:'Cormorant Garamond',serif; font-size:1.4rem;">
                    <i class="fas fa-check-circle"></i> Demande transmise avec succès !
                </h4>
                <p style="margin:0; font-size:0.95rem; line-height:1.5;">
                    <?= htmlspecialchars($succes) ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if ($erreur): ?>
            <div style="background:rgba(220,53,69,0.1); border-left:4px solid #dc3545; padding:16px 20px; border-radius:8px; margin-bottom:24px; color:#721c24;">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($erreur) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="evenements.php#formulaire-devis">
            <input type="hidden" name="action_devis" value="1">

            <div class="form-grid-2">
                <div class="form-group-devis">
                    <label>Votre Nom &amp; Prénom *</label>
                    <input type="text" name="nom_contact" class="form-control-devis" placeholder="Ex: Jean Dupont" required value="<?= htmlspecialchars($_POST['nom_contact'] ?? '') ?>">
                </div>
                <div class="form-group-devis">
                    <label>Entreprise / Organisation</label>
                    <input type="text" name="entreprise" class="form-control-devis" placeholder="Ex: Groupe Afrique Holding" value="<?= htmlspecialchars($_POST['entreprise'] ?? '') ?>">
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group-devis">
                    <label>Adresse Email professionnelle *</label>
                    <input type="email" name="email" class="form-control-devis" placeholder="contact@entreprise.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group-devis">
                    <label>Téléphone / WhatsApp *</label>
                    <input type="tel" name="telephone" class="form-control-devis" placeholder="+228 90 00 00 00" required value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>">
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group-devis">
                    <label>Type d'événement *</label>
                    <select name="type_evenement" class="form-control-devis" required>
                        <option value="">— Sélectionner —</option>
                        <option value="Séminaire d'entreprise">Séminaire d'entreprise</option>
                        <option value="Conférence / Congrès">Conférence / Congrès international</option>
                        <option value="Conseil d'Administration">Conseil d'Administration VIP</option>
                        <option value="Mariage & Célébration">Mariage &amp; Réception privée</option>
                        <option value="Cocktail / Dîner de Gala">Cocktail &amp; Dîner de Gala</option>
                        <option value="Lancement de produit">Lancement de produit / Marque</option>
                        <option value="Autre événement">Autre type d'événement</option>
                    </select>
                </div>
                <div class="form-group-devis">
                    <label>Espace souhaité *</label>
                    <select name="espace_souhaite" class="form-control-devis" required>
                        <option value="Salon Baobab VIP">Salon Baobab VIP (10 - 35 pers.)</option>
                        <option value="Salle Grand Palais">Salle Grand Palais (30 - 180 pers.)</option>
                        <option value="Terrasse & Piscine">Terrasse &amp; Espace Piscine (50 - 250 pers.)</option>
                        <option value="Privatisation Totale">Privatisation Complète de l'Hôtel</option>
                    </select>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group-devis">
                    <label>Date de l'événement *</label>
                    <input type="date" name="date_evenement" class="form-control-devis" required min="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group-devis">
                    <label>Nombre estimé de participants *</label>
                    <input type="number" name="nb_participants" class="form-control-devis" min="5" max="500" value="25" required>
                </div>
            </div>

            <div class="form-group-devis">
                <label>Prestations souhaitées</label>
                <div class="services-checkboxes">
                    <label class="service-check-item">
                        <input type="checkbox" name="services[]" value="Vidéoprojection & Micros sans fil">
                        <span>Vidéoprojection &amp; Micros</span>
                    </label>
                    <label class="service-check-item">
                        <input type="checkbox" name="services[]" value="Pause-café & Viennoiseries" checked>
                        <span>Pause-café d'accueil</span>
                    </label>
                    <label class="service-check-item">
                        <input type="checkbox" name="services[]" value="Déjeuner Buffet Gastronomique" checked>
                        <span>Déjeuner Buffet VIP</span>
                    </label>
                    <label class="service-check-item">
                        <input type="checkbox" name="services[]" value="Hébergement des participants">
                        <span>Hébergement (Chambres)</span>
                    </label>
                    <label class="service-check-item">
                        <input type="checkbox" name="services[]" value="Navette aéroport VIP">
                        <span>Navettes aéroport VIP</span>
                    </label>
                    <label class="service-check-item">
                        <input type="checkbox" name="services[]" value="Animation musicale & DJ">
                        <span>Animation &amp; Sonorisation DJ</span>
                    </label>
                </div>
            </div>

            <div class="form-group-devis">
                <label>Précisions &amp; Besoins spécifiques</label>
                <textarea name="message" class="form-control-devis" rows="4" placeholder="Indiquez ici toute précision sur le déroulement, le timing, les régimes alimentaires particuliers ou vos attentes..."></textarea>
            </div>

            <button type="submit" class="btn-submit-devis">
                <i class="fas fa-paper-plane" style="margin-right:8px;"></i> Envoyer ma demande de devis
            </button>
        </form>
    </div>
</div>

<?php include(__DIR__ . '/../layouts/footer.php'); ?>
