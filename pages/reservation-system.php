<?php
/**
 * ════════════════════════════════════════════════════════
 * SYSTÈME DE RÉSERVATION EN LIGNE
 * ════════════════════════════════════════════════════════
 */

session_start();
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Chambre.php';
require_once __DIR__ . '/../includes/Reservation.php';
require_once __DIR__ . '/../includes/Option.php';
require_once __DIR__ . '/../includes/CodePromo.php';
require_once __DIR__ . '/../includes/Paiement.php';
require_once __DIR__ . '/../includes/Mail.php';

// Initialisation
$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$chambre = new Chambre($db);
$reservation = new Reservation($db);
$optionModel = new Option($db);
$options_disponibles = $optionModel->getAllActive();
$codePromoModel = new CodePromo($db);
$paiementModel = new Paiement($db);

// Traitement AJAX : Vérification Code Promo
if (isset($_POST['action_verifier_promo'])) {
    header('Content-Type: application/json');
    $code = trim($_POST['code_promo'] ?? '');
    $sousTotal = (float)($_POST['sous_total'] ?? 0);
    $res = $codePromoModel->validateAndCalculate($code, $sousTotal);
    echo json_encode($res);
    exit;
}

// Variables pour la vue
$chambres_disponibles = [];
$chambre_selectionnee = null;
$dates_selectionnees = [
    'arrivee' => $_GET['arrivee'] ?? '',
    'depart' => $_GET['depart'] ?? ''
];
$etape = 1; // 1: recherche, 2: sélection, 3: formulaire, 4: confirmation
$erreur = '';
$succes = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Étape 1: Recherche de disponibilités
    if (isset($_POST['action_recherche'])) {
        $date_arrivee = $_POST['date_arrivee'] ?? '';
        $date_depart = $_POST['date_depart'] ?? '';
        $type_chambre = $_POST['type_chambre'] ?? 'all';
        
        if (empty($date_arrivee) || empty($date_depart)) {
            $erreur = 'Veuillez sélectionner une date d\'arrivée et de départ';
        } elseif (strtotime($date_arrivee) >= strtotime($date_depart)) {
            $erreur = 'La date de départ doit être postérieure à la date d\'arrivée';
        } elseif (strtotime($date_arrivee) < strtotime(date('Y-m-d'))) {
            $erreur = 'La date d\'arrivée ne peut être dans le passé';
        } else {
            $chambres_disponibles = $chambre->getAvailableForDates($date_arrivee, $date_depart, $type_chambre);
            $dates_selectionnees = ['arrivee' => $date_arrivee, 'depart' => $date_depart];
            
            if (empty($chambres_disponibles)) {
                $erreur = 'Aucune chambre disponible pour ces dates. Veuillez essayer d\'autres dates.';
            } else {
                $etape = 2;
            }
        }
    }
    
    // Étape 2: Sélection d'une chambre
    if (isset($_POST['action_selection'])) {
        $chambre_id = $_POST['chambre_id'] ?? '';
        $date_arrivee = $_POST['date_arrivee'] ?? '';
        $date_depart = $_POST['date_depart'] ?? '';
        
        if ($chambre_selectionnee = $chambre->getById($chambre_id)) {
            $dates_selectionnees = ['arrivee' => $date_arrivee, 'depart' => $date_depart];
            $etape = 3;
        } else {
            $erreur = 'Chambre non trouvée';
        }
    }
    
    // Étape 3: Création de la réservation
    if (isset($_POST['action_reservation'])) {
        $chambre_id = $_POST['chambre_id'] ?? '';
        $date_arrivee = $_POST['date_arrivee'] ?? '';
        $date_depart = $_POST['date_depart'] ?? '';
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $pays = trim($_POST['pays'] ?? '');
        $nb_adultes = intval($_POST['nb_adultes'] ?? 1);
        $nb_enfants = intval($_POST['nb_enfants'] ?? 0);
        $demandes_speciales = trim($_POST['demandes_speciales'] ?? '');
        $selected_options = $_POST['options'] ?? [];
        $code_promo = trim($_POST['code_promo'] ?? '');
        $mode_reglement = $_POST['mode_reglement'] ?? 'sur_place';
        $moyen_paiement = $_POST['moyen_paiement'] ?? 'especes_hotel';
        $telephone_paiement = trim($_POST['telephone_paiement'] ?? $telephone);
        
        // Validation
        if (empty($nom) || empty($prenom) || empty($email)) {
            $erreur = 'Les champs nom, prénom et email sont obligatoires';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreur = 'Adresse email invalide';
        } elseif ($nb_adultes < 1) {
            $erreur = 'Au moins un adulte est requis';
        } else {
            try {
                // Créer ou récupérer le compte client automatiquement
                $client = $user->createAutoAccount($nom, $prenom, $email, $telephone, $pays);
                
                if ($client) {
                    // Créer la réservation avec les options et le code promo
                    $new_reservation = $reservation->create(
                        $client->id,
                        $chambre_id,
                        $date_arrivee,
                        $date_depart,
                        $nb_adultes,
                        $nb_enfants,
                        $demandes_speciales,
                        $selected_options,
                        $code_promo
                    );
                    
                    if ($new_reservation) {
                        // Traitement du Paiement en Ligne (Mobile Money / Carte)
                        $paiementInfo = null;
                        if ($mode_reglement !== 'sur_place') {
                            $montantAPayer = ($mode_reglement === 'acompte_30') ? round($new_reservation->prix_total * 0.3, 2) : $new_reservation->prix_total;
                            $paiementInfo = $paiementModel->traiterPaiement(
                                $new_reservation->id,
                                $client->id,
                                $montantAPayer,
                                $moyen_paiement,
                                $mode_reglement,
                                $telephone_paiement
                            );
                        }

                        // Stocker les infos en session pour la confirmation
                        $_SESSION['reservation_id'] = $new_reservation->id;
                        $_SESSION['paiement_info'] = $paiementInfo;
                        $_SESSION['user_info'] = [
                            'nom' => $client->nom,
                            'prenom' => $client->prenom,
                            'email' => $client->email,
                            'code_client' => (!empty($client->is_new_account)) ? $client->code_client : null,
                            'is_new' => !empty($client->is_new_account)
                        ];
                        
                        $etape = 4;
                        $succes = 'Votre réservation a été enregistrée avec succès !';

                        // ── ENVOI DES EMAILS ──
                        try {
                            $nomComplet = $client->prenom . ' ' . $client->nom;
                            
                            // 1. Email de bienvenue avec Code Client (si nouveau compte)
                            if (!empty($client->is_new_account)) {
                                Mail::sendWelcomeClientCode($client->email, $nomComplet, $client->code_client);
                            }

                            // Récupérer la liste des options et le nom de la chambre
                            $chambreInfo = $chambre->getById($chambre_id);
                            $chambreNom  = ($chambreInfo && isset($chambreInfo->nom)) ? $chambreInfo->nom : ('Hébergement ' . hotel_short_name());
                            $optionsChoisies = $new_reservation->getOptions();
                            $promoTexte = !empty($code_promo) ? ($code_promo . ' (-' . number_format($new_reservation->montant_reduction, 0, ',', ' ') . ' FCFA)') : null;
                            $paiementTexte = ($mode_reglement === 'acompte_30') 
                                ? 'Acompte de 30% réglé en ligne (' . number_format($new_reservation->prix_total * 0.3, 0, ',', ' ') . ' FCFA) via ' . strtoupper($moyen_paiement)
                                : (($mode_reglement === 'total_100') ? 'Totalité (100%) réglée en ligne via ' . strtoupper($moyen_paiement) : 'Paiement à l\'arrivée à l\'hôtel');

                            // 2. Email de confirmation de réservation au client
                            Mail::sendReservationConfirmation(
                                $client->email,
                                $nomComplet,
                                $new_reservation->reference,
                                $chambreNom,
                                $date_arrivee,
                                $date_depart,
                                $new_reservation->getPrixTotalFormate(),
                                $optionsChoisies,
                                $promoTexte,
                                $paiementTexte,
                                $demandes_speciales
                            );

                            // 3. Email de notification aux Administrateurs
                            $adminList = $user->getAllAdmins();
                            $adminEmails = array_column($adminList, 'email');
                            Mail::sendAdminNewReservationNotification(
                                $adminEmails,
                                $new_reservation->reference,
                                $nomComplet,
                                $client->email,
                                $chambreNom,
                                $date_arrivee,
                                $date_depart,
                                $new_reservation->getPrixTotalFormate(),
                                $optionsChoisies,
                                $promoTexte,
                                $paiementTexte,
                                $demandes_speciales
                            );
                        } catch (Exception $mailError) {
                            error_log("Email sending error in reservation-system: " . $mailError->getMessage());
                        }
                    } else {
                        // Récupérer le message d'erreur (ex: double réservation)
                        $erreur = $reservation->derniere_erreur
                            ?: 'Erreur lors de la création de la réservation. Veuillez réessayer.';
                    }
                } else {
                    $erreur = $user->derniere_erreur ?: 'Erreur lors de la création de votre compte.';
                }
            } catch (Exception $e) {
                error_log("reservation-system create: " . $e->getMessage());
                $erreur = 'Une erreur est survenue. Veuillez réessayer.';
            }
        }
    }
}

// Récupérer les chambres pour les filtres
$types_chambres = $chambre->countByType();
$chambres_all = $chambre->getAllAvailable();

include(__DIR__ . '/../layouts/header.php');
?>

<style>
/* Variables CSS */
:root {
    --vert: #1a3a2a;
    --vert-clair: #2d5f47;
    --or: #c9a84c;
    --or-pale: #d4b873;
    --noir: #000000;
}

/* ════════════════════════════════════════════════════════
   SYSTÈME DE RÉSERVATION — Styles
═══════════════════════════════════════════════════════ */
.reservation-system {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

.etape-indicator {
    display: flex;
    justify-content: center;
    margin-bottom: 60px;
    position: relative;
}

.etape {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 30px;
    position: relative;
}

.etape:not(:last-child)::after {
    content: '';
    position: absolute;
    right: -15px;
    top: 20px;
    width: 30px;
    height: 2px;
    background: rgba(201,168,76,0.2);
}

.etape.active .etape-number {
    background: var(--or);
    color: white;
}

.etape.completed .etape-number {
    background: var(--vert);
    color: white;
}

.etape-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    transition: all 0.3s;
    font-family: 'Jost', sans-serif;
    font-size: 0.9rem;
}

.etape-text {
    font-family: 'Jost', sans-serif;
    font-size: 0.8rem;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.etape.active .etape-text,
.etape.completed .etape-text {
    color: var(--vert);
}

.form-section {
    background: white;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.05);
    margin-bottom: 40px;
}

.form-group {
    margin-bottom: 24px;
}

.form-label {
    font-family: 'Jost', sans-serif;
    font-weight: 500;
    font-size: 0.9rem;
    color: var(--vert);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 8px;
    display: block;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid rgba(201,168,76,0.2);
    border-radius: 8px;
    font-family: 'Jost', sans-serif;
    font-size: 0.95rem;
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: var(--or);
    box-shadow: 0 0 0 3px rgba(201,168,76,0.1);
}

.btn-primary {
    background: var(--vert);
    color: white;
    border: none;
    padding: 14px 40px;
    border-radius: 8px;
    font-family: 'Jost', sans-serif;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-primary:hover {
    background: var(--vert-clair);
    transform: translateY(-2px);
}

/* Style identique au bouton Réserver du header */
.btn-reserver {
    font-family: 'Jost', sans-serif;
    font-weight: 300;
    font-size: 0.62rem;
    letter-spacing: 0.3em;
    text-transform: uppercase;
    padding: 12px 28px;
    text-decoration: none;
    border: 1px solid var(--or);
    color: var(--or);
    background: transparent;
    display: inline-block;
    transition: background 0.3s, color 0.3s;
    cursor: pointer;
}
.btn-reserver:hover {
    background: var(--or);
    color: #fff;
}
.btn-reserver.plein {
    background: var(--vert);
    border-color: var(--vert);
    color: var(--or);
}
.btn-reserver.plein:hover {
    background: var(--vert-clair);
    border-color: var(--vert-clair);
}

.chambre-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 24px;
    margin-top: 32px;
}

.chambre-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s;
    cursor: pointer;
}

.chambre-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.12);
}

.chambre-card.selected {
    border: 2px solid var(--or);
}

.chambre-image {
    height: 200px;
    background: linear-gradient(45deg, var(--vert-clair), var(--or-pale));
    position: relative;
    overflow: hidden;
}

.chambre-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.chambre-info {
    padding: 24px;
}

.chambre-nom {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.4rem;
    color: var(--vert);
    margin-bottom: 8px;
}

.chambre-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 16px;
}

.chambre-prix {
    font-size: 1.2rem;
    color: var(--or);
    font-weight: 600;
}

.alert {
    padding: 16px 24px;
    border-radius: 8px;
    margin-bottom: 24px;
    font-family: 'Jost', sans-serif;
}

.alert-success {
    background: rgba(26,58,42,0.1);
    color: var(--vert);
    border: 1px solid rgba(26,58,42,0.2);
}

.alert-error {
    background: rgba(220,53,69,0.1);
    color: #dc3545;
    border: 1px solid rgba(220,53,69,0.2);
}

.confirmation-box {
    background: linear-gradient(135deg, rgba(26,58,42,0.05), rgba(201,168,76,0.05));
    border-radius: 12px;
    padding: 40px;
    text-align: center;
}

.confirmation-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2rem;
    color: var(--vert);
    margin-bottom: 16px;
}

.confirmation-ref {
    font-size: 1.1rem;
    color: var(--or);
    font-weight: 600;
    margin-bottom: 24px;
}

.confirmation-code {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin: 24px 0;
    border: 1px solid rgba(201,168,76,0.2);
}

.code-label {
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 8px;
}

.code-value {
    font-size: 1.4rem;
    color: var(--vert);
    font-weight: 600;
    letter-spacing: 0.1em;
}

@media (max-width: 768px) {
    .reservation-system {
        padding: 20px 14px;
        margin-top: 80px !important;
    }
    
    .form-section {
        padding: 20px 16px;
    }
    
    .etape-indicator {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        gap: 6px;
        margin-bottom: 30px;
    }
    
    .etape {
        padding: 0 4px;
        flex-direction: column;
        gap: 4px;
        text-align: center;
    }
    
    .etape-number {
        width: 30px;
        height: 30px;
        font-size: 0.8rem;
    }
    
    .etape-text {
        font-size: 0.65rem;
    }
    
    .etape:not(:last-child)::after {
        display: none;
    }
    
    .chambre-grid {
        grid-template-columns: 1fr;
    }
    
    .form-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<div class="reservation-system " style="margin-top: 100px;">
    
    <!-- Indicateur d'étapes -->
    <div class="etape-indicator">
        <div class="etape <?= $etape >= 1 ? 'active' : '' ?>">
            <div class="etape-number">1</div>
            <div class="etape-text">Dates</div>
        </div>
        <div class="etape <?= $etape >= 2 ? 'active' : '' ?>">
            <div class="etape-number">2</div>
            <div class="etape-text">Chambre</div>
        </div>
        <div class="etape <?= $etape >= 3 ? 'active' : '' ?>">
            <div class="etape-number">3</div>
            <div class="etape-text">Informations</div>
        </div>
        <div class="etape <?= $etape >= 4 ? 'active' : '' ?>">
            <div class="etape-number">4</div>
            <div class="etape-text">Confirmation</div>
        </div>
    </div>

    <?php if ($erreur): ?>
        <div class="alert alert-error"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <?php if ($succes): ?>
        <div class="alert alert-success"><?= htmlspecialchars($succes) ?></div>
    <?php endif; ?>

    <!-- ÉTAPE 1: RECHERCHE DES DATES -->
    <?php if ($etape === 1): ?>
        <div class="form-section">
            <h2 style="font-family: 'Cormorant Garamond', serif; font-size: 2rem; color: var(--vert); margin-bottom: 32px; text-align: center;">
                Quand souhaitez-vous séjourner ?
            </h2>
            
            <form method="post">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Date d'arrivée</label>
                            <input type="date" name="date_arrivee" class="form-control" 
                                   value="<?= htmlspecialchars($dates_selectionnees['arrivee']) ?>"
                                   min="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Date de départ</label>
                            <input type="date" name="date_depart" class="form-control" 
                                   value="<?= htmlspecialchars($dates_selectionnees['depart']) ?>"
                                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Type de chambre</label>
                    <select name="type_chambre" class="form-control">
                        <option value="all">Tous les types</option>
                        <?php foreach ($types_chambres as $type): ?>
                            <option value="<?= $type['type'] ?>">
                                <?= ucfirst($type['type']) ?> (<?= $type['nb_chambres'] ?> chambres)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="text-align: center; margin-top: 32px;">
                    <button type="submit" name="action_recherche" class="btn-primary">
                        Vérifier la disponibilité
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- ÉTAPE 2: SÉLECTION DE LA CHAMBRE -->
    <?php if ($etape === 2): ?>
        <div class="form-section">
            <h2 style="font-family: 'Cormorant Garamond', serif; font-size: 2rem; color: var(--vert); margin-bottom: 16px; text-align: center;">
                Chambres disponibles
            </h2>
            <p style="text-align: center; color: #666; margin-bottom: 32px;">
                Du <?= date('d/m/Y', strtotime($dates_selectionnees['arrivee'])) ?> 
                au <?= date('d/m/Y', strtotime($dates_selectionnees['depart'])) ?>
            </p>
            
            <form method="post">
                <input type="hidden" name="date_arrivee" value="<?= htmlspecialchars($dates_selectionnees['arrivee']) ?>">
                <input type="hidden" name="date_depart" value="<?= htmlspecialchars($dates_selectionnees['depart']) ?>">
                
                <div class="chambre-grid">
                    <?php foreach ($chambres_disponibles as $ch): ?>
                        <div class="chambre-card" onclick="selectChambre('<?= $ch['id'] ?>', this)">
                            <div class="chambre-image">
                                <?php if ($ch['image_principale']): ?>
                                    <img src="<?= htmlspecialchars($ch['image_principale']) ?>" alt="<?= htmlspecialchars($ch['nom']) ?>">
                                <?php else: ?>
                                    <img src="https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800&q=80" alt="<?= htmlspecialchars($ch['nom']) ?>">
                                <?php endif; ?>
                            </div>
                            <div class="chambre-info">
                                <div class="chambre-nom"><?= htmlspecialchars($ch['nom']) ?></div>
                                <div style="color: #666; font-size: 0.9rem; margin-bottom: 8px;">
                                    <?= ucfirst($ch['type']) ?> • <?= $ch['superficie_m2'] ?> m² • 
                                    Max <?= $ch['capacite_max'] ?> personnes
                                </div>
                                <div class="chambre-details">
                                    <div class="chambre-prix"><?= number_format($ch['prix_nuit'], 0, ',', ' ') ?> FCFA/nuit</div>
                                    <button type="button" class="btn-primary" style="padding: 8px 20px; font-size: 0.8rem;"
                                            onclick="selectChambre('<?= $ch['id'] ?>', this)">
                                        Choisir
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <input type="hidden" name="chambre_id" id="chambre_id_selected">
                <div style="text-align: center; margin-top: 32px;">
                    <button type="submit" name="action_selection" class="btn-primary" id="btn_continue" disabled>
                        Continuer
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- ÉTAPE 3: FORMULAIRE CLIENT -->
    <?php if ($etape === 3): ?>
        <div class="form-section">
            <h2 style="font-family: 'Cormorant Garamond', serif; font-size: 2rem; color: var(--vert); margin-bottom: 32px; text-align: center;">
                Vos informations
            </h2>
            
            <form method="post">
                <input type="hidden" name="chambre_id" value="<?= htmlspecialchars($chambre_selectionnee->id) ?>">
                <input type="hidden" name="date_arrivee" value="<?= htmlspecialchars($dates_selectionnees['arrivee']) ?>">
                <input type="hidden" name="date_depart" value="<?= htmlspecialchars($dates_selectionnees['depart']) ?>">
                
                <!-- Récapitulatif de la réservation -->
                <div style="background: rgba(201,168,76,0.05); border-radius: 8px; padding: 20px; margin-bottom: 32px;">
                    <h4 style="color: var(--vert); margin-bottom: 12px;">Récapitulatif de votre séjour</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; font-size: 0.9rem;">
                        <div><strong>Chambre:</strong> <?= htmlspecialchars($chambre_selectionnee->nom) ?></div>
                        <div><strong>Dates:</strong> Du <?= date('d/m/Y', strtotime($dates_selectionnees['arrivee'])) ?> au <?= date('d/m/Y', strtotime($dates_selectionnees['depart'])) ?></div>
                        <div><strong>Prix:</strong> <?= number_format($chambre_selectionnee->prix_nuit, 0, ',', ' ') ?> FCFA/nuit</div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Nom *</label>
                            <input type="text" name="nom" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Prénom *</label>
                            <input type="text" name="prenom" class="form-control" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" required>
                    <small style="color: #666;">Un compte sera créé automatiquement avec ces informations</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" name="telephone" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Pays</label>
                    <input type="text" name="pays" class="form-control">
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Nombre d'adultes *</label>
                            <select name="nb_adultes" class="form-control" required>
                                <?php for ($i = 1; $i <= $chambre_selectionnee->capacite_max; $i++): ?>
                                    <option value="<?= $i ?>" <?= $i == 1 ? 'selected' : '' ?>><?= $i ?> <?= $i > 1 ? 'adultes' : 'adulte' ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Nombre d'enfants</label>
                            <select name="nb_enfants" class="form-control">
                                <?php for ($i = 0; $i <= $chambre_selectionnee->capacite_enfants; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?> <?= $i > 1 ? 'enfants' : 'enfant' ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Demandes spéciales</label>
                    <textarea name="demandes_speciales" class="form-control" rows="3" 
                              placeholder="Allergies, préférences d'étage, besoins particuliers..."></textarea>
                </div>

                <!-- ── OPTIONS & SERVICES DE SÉJOUR ── -->
                <?php 
                $nbNuits = max(1, (int)((strtotime($dates_selectionnees['depart']) - strtotime($dates_selectionnees['arrivee'])) / 86400));
                $prixBaseChambre = (float) $chambre_selectionnee->prix_nuit * $nbNuits;
                ?>
                <div style="margin: 36px 0 28px;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:18px;">
                        <span style="font-size:1.4rem;">✨</span>
                        <h3 style="font-family:'Cormorant Garamond', serif; font-size:1.6rem; color:var(--vert); margin:0;">
                            Personnalisez votre séjour avec nos options exclusives
                        </h3>
                    </div>

                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:16px;">
                        <?php 
                        $optionIcons = [
                            'Petit-déjeuner'     => 'fa-utensils',
                            'Transfert aéroport' => 'fa-car-side',
                            'Soin Spa 60 min'    => 'fa-spa',
                            'Dîner romantique'   => 'fa-wine-glass-alt',
                            'Excursion Ganvié'   => 'fa-water',
                            'Accueil Champagne'  => 'fa-glass-cheers',
                            'Session Jet Ski'    => 'fa-ship',
                            'Croisière Yacht'    => 'fa-anchor'
                        ];
                        foreach ($options_disponibles as $opt): 
                            $icon = $optionIcons[$opt['nom']] ?? 'fa-concierge-bell';
                            $optPrice = (float) $opt['prix'];
                        ?>
                        <label class="option-card" style="display:flex; align-items:flex-start; gap:14px; background:#fff; border:1.5px solid #e8e3d9; border-radius:8px; padding:16px; cursor:pointer; transition:all 0.3s; position:relative;">
                            <input type="checkbox" name="options[<?= $opt['id'] ?>]" value="1" 
                                   data-prix="<?= $optPrice ?>" 
                                   data-unite="<?= htmlspecialchars($opt['unite']) ?>"
                                   data-nom="<?= htmlspecialchars($opt['nom']) ?>"
                                   style="margin-top:4px; width:18px; height:18px; accent-color:var(--vert);" 
                                   onchange="recalculerTotal()">
                            <div style="flex:1;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                                    <strong style="color:var(--vert); font-size:0.95rem;">
                                        <i class="fas <?= $icon ?>" style="color:var(--or); margin-right:6px;"></i>
                                        <?= htmlspecialchars($opt['nom']) ?>
                                    </strong>
                                    <span style="font-weight:600; color:var(--or); font-size:0.9rem;">
                                        +<?= number_format($optPrice, 0, ',', ' ') ?> FCFA
                                    </span>
                                </div>
                                <p style="margin:0 0 6px 0; font-size:0.82rem; color:#666; line-height:1.4;">
                                    <?= htmlspecialchars($opt['description']) ?>
                                </p>
                                <span style="font-size:0.75rem; background:rgba(201,168,76,0.12); color:#8c6d1f; padding:2px 8px; border-radius:12px; font-weight:500;">
                                    <?= htmlspecialchars($opt['unite']) ?>
                                </span>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ── CODE PROMO & RÉDUCTIONS ── -->
                <div style="margin: 24px 0; background: #ffffff; border: 1.5px dashed #c9a84c; border-radius: 8px; padding: 18px 22px;">
                    <label style="font-weight: 600; color: var(--vert); font-size: 0.95rem; display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <i class="fas fa-tag" style="color: var(--or);"></i> Vous avez un code promotionnel ?
                    </label>
                    <div style="display: flex; gap: 10px; max-width: 450px; flex-wrap: wrap;">
                        <input type="text" name="code_promo" id="code_promo_input" placeholder="Ex: WELCOME10" class="form-control" style="text-transform: uppercase; font-weight: 600; letter-spacing: 0.06em; flex: 1; min-width: 160px;">
                        <button type="button" class="btn-primary" style="white-space: nowrap; padding: 10px 22px; font-size: 0.85rem;" onclick="appliquerCodePromo()">Appliquer</button>
                    </div>
                    <div id="promo_feedback" style="margin-top: 8px; font-size: 0.88rem; display: none;"></div>
                </div>

                <!-- ── RÉCAPITULATIF FINANCIER DYNAMIQUE ── -->
                <div style="background:linear-gradient(135deg, #fdfbf7, #f7f3e8); border:1.5px solid rgba(201,168,76,0.3); border-radius:10px; padding:24px 28px; margin:32px 0;">
                    <h4 style="font-family:'Cormorant Garamond', serif; font-size:1.3rem; color:var(--vert); margin:0 0 16px 0;">
                        Détail du tarif de votre séjour
                    </h4>
                    
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:0.95rem; color:#555;">
                        <span>Hébergement (<?= $nbNuits ?> nuit<?= $nbNuits > 1 ? 's' : '' ?> × <?= number_format($chambre_selectionnee->prix_nuit, 0, ',', ' ') ?> FCFA) :</span>
                        <strong style="color:var(--vert);" id="prix_hebergement_txt"><?= number_format($prixBaseChambre, 0, ',', ' ') ?> FCFA</strong>
                    </div>

                    <div style="display:flex; justify-content:space-between; margin-bottom:12px; font-size:0.95rem; color:#555;">
                        <span>Options et services sélectionnés :</span>
                        <strong style="color:var(--vert);" id="prix_options_txt">0 FCFA</strong>
                    </div>

                    <div id="options_detail_list" style="font-size:0.85rem; color:#777; margin-bottom:12px; padding-left:12px; border-left:2px solid var(--or); display:none;"></div>

                    <div id="promo_row" style="display:none; justify-content:space-between; margin-bottom:12px; font-size:0.95rem; color:#28a745; font-weight:600;">
                        <span><i class="fas fa-tag"></i> Remise code promo (<span id="promo_code_label"></span>) :</span>
                        <span id="prix_reduction_txt">-0 FCFA</span>
                    </div>

                    <hr style="border:none; border-top:1px dashed #d4c8b0; margin:14px 0;">

                    <div style="display:flex; justify-content:space-between; align-items:baseline;">
                        <span style="font-size:1.1rem; font-weight:600; color:var(--vert);">Total du séjour :</span>
                        <span style="font-family:'Cormorant Garamond', serif; font-size:2rem; font-weight:700; color:var(--or);" id="prix_total_txt">
                            <?= number_format($prixBaseChambre, 0, ',', ' ') ?> FCFA
                        </span>
                    </div>
                </div>

                <!-- ── CHOIX DU MODE DE RÈGLEMENT & PAIEMENT ── -->
                <div style="background:#ffffff; border:1.5px solid rgba(26,58,42,0.15); border-radius:10px; padding:24px 28px; margin:32px 0;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:18px;">
                        <span style="font-size:1.4rem;">💳</span>
                        <h3 style="font-family:'Cormorant Garamond', serif; font-size:1.5rem; color:var(--vert); margin:0;">
                            Mode de règlement de votre séjour
                        </h3>
                    </div>

                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:16px; margin-bottom:20px;">
                        <label class="option-card" style="border:1.5px solid #c9a84c; background:#fdfcf9; border-radius:8px; padding:16px; cursor:pointer; display:block;">
                            <input type="radio" name="mode_reglement" value="acompte_30" checked onchange="majModePaiement()" style="accent-color:var(--vert);">
                            <div style="margin-top:6px;">
                                <strong style="color:var(--vert); font-size:0.95rem;">Acompte de garantie (30%)</strong>
                                <p style="font-size:0.8rem; color:#666; margin:4px 0;">Réglez <span id="acompte_montant_txt" style="color:var(--or); font-weight:600;">... FCFA</span> en ligne pour valider instantanément votre chambre.</p>
                                <span style="font-size:0.75rem; background:#e8f5e9; color:#2e7d32; padding:2px 8px; border-radius:10px; font-weight:500;">Validation immédiate</span>
                            </div>
                        </label>

                        <label class="option-card" style="border:1.5px solid #e0dacb; background:#fff; border-radius:8px; padding:16px; cursor:pointer; display:block;">
                            <input type="radio" name="mode_reglement" value="total_100" onchange="majModePaiement()" style="accent-color:var(--vert);">
                            <div style="margin-top:6px;">
                                <strong style="color:var(--vert); font-size:0.95rem;">Totalité du séjour (100%)</strong>
                                <p style="font-size:0.8rem; color:#666; margin:4px 0;">Payez l'intégralité en ligne et voyagez en toute tranquillité.</p>
                                <span style="font-size:0.75rem; background:rgba(201,168,76,0.15); color:#8c6d1f; padding:2px 8px; border-radius:10px; font-weight:500;">Paiement sécurisé</span>
                            </div>
                        </label>

                        <label class="option-card" style="border:1.5px solid #e0dacb; background:#fff; border-radius:8px; padding:16px; cursor:pointer; display:block;">
                            <input type="radio" name="mode_reglement" value="sur_place" onchange="majModePaiement()" style="accent-color:var(--vert);">
                            <div style="margin-top:6px;">
                                <strong style="color:var(--vert); font-size:0.95rem;">Règlement à l'arrivée</strong>
                                <p style="font-size:0.8rem; color:#666; margin:4px 0;">Paiement complet à la réception lors de votre check-in.</p>
                                <span style="font-size:0.75rem; background:#f0f0f0; color:#666; padding:2px 8px; border-radius:10px; font-weight:500;">Validation sous 24h</span>
                            </div>
                        </label>
                    </div>

                    <!-- Options de passerelle -->
                    <div id="bloc_moyens_paiement" style="background:#faf8f3; border:1px solid #e8e3d6; border-radius:8px; padding:18px; margin-top:14px;">
                        <label style="font-weight:600; color:var(--vert); font-size:0.9rem; display:block; margin-bottom:10px;">
                            Sélectionnez votre moyen de paiement sécurisé :
                        </label>
                        <div style="display:flex; gap:16px; flex-wrap:wrap; margin-bottom:14px;">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; background:#fff; border:1.5px solid #dcd5c5; padding:10px 18px; border-radius:6px;">
                                <input type="radio" name="moyen_paiement" value="mobile_money" checked style="accent-color:var(--vert); width:18px; height:18px;">
                                <span style="font-size:0.95rem;">📱 <strong>Paiement Mobile Money</strong> <small style="color:#666;">(Moov, MTN, Orange, Wave, T-Money...)</small></span>
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; background:#fff; border:1.5px solid #dcd5c5; padding:10px 18px; border-radius:6px;">
                                <input type="radio" name="moyen_paiement" value="carte_bancaire" style="accent-color:var(--vert); width:18px; height:18px;">
                                <span style="font-size:0.95rem;">💳 <strong>Carte Bancaire</strong> <small style="color:#666;">(Visa, Mastercard)</small></span>
                            </label>
                        </div>
                        <div class="form-group" style="max-width:380px; margin-bottom:0;">
                            <label class="form-label" style="font-size:0.85rem;">Numéro de téléphone Mobile Money ou référence bancaire</label>
                            <input type="text" name="telephone_paiement" class="form-control" placeholder="Ex: +228 90 00 00 00 / +229 ... / +225 ...">
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 32px;">
                    <button type="submit" name="action_reservation" class="btn-primary" style="padding:14px 44px; font-size:1rem;">
                        Confirmer et Réserver
                    </button>
                </div>
            </form>
        </div>

        <script>
        const nbNuits = <?= (int)$nbNuits ?>;
        const prixBaseHebergement = <?= (float)$prixBaseChambre ?>;
        let remiseActive = 0;
        let codePromoValide = '';

        function majModePaiement() {
            const mode = document.querySelector('input[name="mode_reglement"]:checked').value;
            const bloc = document.getElementById('bloc_moyens_paiement');
            if (mode === 'sur_place') {
                bloc.style.display = 'none';
            } else {
                bloc.style.display = 'block';
            }
        }

        function getSousTotalActuel() {
            let totalOptions = 0;
            const checkedOpts = document.querySelectorAll('input[name^="options["]:checked');
            checkedOpts.forEach(cb => {
                const prix = parseFloat(cb.getAttribute('data-prix')) || 0;
                const unite = cb.getAttribute('data-unite');
                let optTotal = (unite === 'par nuit') ? (prix * nbNuits) : prix;
                totalOptions += optTotal;
            });
            return prixBaseHebergement + totalOptions;
        }

        function recalculerTotal() {
            let totalOptions = 0;
            const checkedOpts = document.querySelectorAll('input[name^="options["]:checked');
            const detailsDiv = document.getElementById('options_detail_list');
            let detailsHtml = '';

            checkedOpts.forEach(cb => {
                const prix = parseFloat(cb.getAttribute('data-prix')) || 0;
                const unite = cb.getAttribute('data-unite');
                const nom = cb.getAttribute('data-nom');
                let optTotal = (unite === 'par nuit') ? (prix * nbNuits) : prix;
                totalOptions += optTotal;

                detailsHtml += '<div>• ' + nom + ' : +' + optTotal.toLocaleString('fr-FR') + ' FCFA (' + unite + ')</div>';
            });

            if (checkedOpts.length > 0) {
                detailsDiv.innerHTML = detailsHtml;
                detailsDiv.style.display = 'block';
            } else {
                detailsDiv.style.display = 'none';
            }

            const sousTotal = prixBaseHebergement + totalOptions;
            document.getElementById('prix_options_txt').textContent = totalOptions.toLocaleString('fr-FR') + ' FCFA';

            if (codePromoValide) {
                // Re-vérifier la remise avec le nouveau sous-total
                verifierPromoBackend(codePromoValide, sousTotal, false);
            } else {
                const totalFinal = Math.max(0, sousTotal - remiseActive);
                document.getElementById('prix_total_txt').textContent = totalFinal.toLocaleString('fr-FR') + ' FCFA';
                const acompte = Math.round(totalFinal * 0.3);
                const acEl = document.getElementById('acompte_montant_txt');
                if (acEl) acEl.textContent = acompte.toLocaleString('fr-FR') + ' FCFA';
            }
        }

        // Init acompte au chargement
        document.addEventListener('DOMContentLoaded', () => {
            const acEl = document.getElementById('acompte_montant_txt');
            if (acEl) acEl.textContent = Math.round(prixBaseHebergement * 0.3).toLocaleString('fr-FR') + ' FCFA';
        });

        function appliquerCodePromo() {
            const code = document.getElementById('code_promo_input').value.trim();
            const sousTotal = getSousTotalActuel();
            verifierPromoBackend(code, sousTotal, true);
        }

        function verifierPromoBackend(code, sousTotal, showFeedback) {
            const fb = document.getElementById('promo_feedback');
            if (!code) {
                if (showFeedback) {
                    fb.innerHTML = '<span style="color:#dc3545;"><i class="fas fa-exclamation-circle"></i> Veuillez entrer un code promo.</span>';
                    fb.style.display = 'block';
                }
                return;
            }

            const data = new FormData();
            data.append('action_verifier_promo', '1');
            data.append('code_promo', code);
            data.append('sous_total', sousTotal);

            fetch(window.location.href, { method: 'POST', body: data })
                .then(r => r.json())
                .then(res => {
                    if (res.valid) {
                        codePromoValide = res.promo.code;
                        remiseActive = res.reduction;
                        document.getElementById('promo_row').style.display = 'flex';
                        document.getElementById('promo_code_label').textContent = res.promo.code;
                        document.getElementById('prix_reduction_txt').textContent = '-' + res.reduction.toLocaleString('fr-FR') + ' FCFA';
                        document.getElementById('prix_total_txt').textContent = res.nouveau_total.toLocaleString('fr-FR') + ' FCFA';

                        if (showFeedback) {
                            fb.innerHTML = '<span style="color:#28a745; font-weight:600;"><i class="fas fa-check-circle"></i> ' + res.message + ' (-' + res.reduction.toLocaleString('fr-FR') + ' FCFA)</span>';
                            fb.style.display = 'block';
                        }
                    } else {
                        codePromoValide = '';
                        remiseActive = 0;
                        document.getElementById('promo_row').style.display = 'none';
                        document.getElementById('prix_total_txt').textContent = sousTotal.toLocaleString('fr-FR') + ' FCFA';

                        if (showFeedback) {
                            fb.innerHTML = '<span style="color:#dc3545;"><i class="fas fa-times-circle"></i> ' + res.message + '</span>';
                            fb.style.display = 'block';
                        }
                    }
                })
                .catch(() => {
                    if (showFeedback) {
                        fb.innerHTML = '<span style="color:#dc3545;">Erreur lors de la vérification du code promo.</span>';
                        fb.style.display = 'block';
                    }
                });
        }
        </script>
    <?php endif; ?>

    <!-- ÉTAPE 4: CONFIRMATION -->
    <?php if ($etape === 4): ?>
        <div class="confirmation-box">
            <div class="confirmation-title">Réservation confirmée !</div>
            <div class="confirmation-ref">Référence: <?= htmlspecialchars($reservation->reference ?? '') ?></div>
            
            <p style="color: #666; margin-bottom: 24px;">
                Votre réservation a été enregistrée et est en attente de validation par notre équipe.
                Vous recevrez un email de confirmation shortly.
            </p>
            
            <?php if (!empty($_SESSION['user_info']['is_new']) && !empty($_SESSION['user_info']['code_client'])): ?>
                <div class="confirmation-code">
                    <div class="code-label">VOS IDENTIFIANTS DE CONNEXION</div>
                    <div class="code-value"><?= htmlspecialchars($_SESSION['user_info']['code_client'] ?? '') ?></div>
                    <div style="font-size: 0.9rem; color: #666; margin-top: 8px;">
                        Conservez ce code précieusement pour vous connecter et gérer votre réservation
                    </div>
                </div>
            <?php elseif (!empty($_SESSION['user_info']['email'])): ?>
                <div class="confirmation-code" style="background: rgba(201,168,76,0.1); border-color: var(--or);">
                    <div class="code-label" style="color: var(--vert);">COMPTE CLIENT EXISTANT</div>
                    <div style="font-size: 0.95rem; color: #444; margin-top: 8px; line-height: 1.5;">
                        Cette réservation a été rattachée à votre compte (<strong><?= htmlspecialchars($_SESSION['user_info']['email'] ?? '') ?></strong>).<br>
                        Vous pouvez vous connecter avec votre code client habituel.
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($_SESSION['paiement_info'])): 
                $pInfo = $_SESSION['paiement_info'];
            ?>
                <div style="background:rgba(40,167,69,0.08); border:1.5px solid #28a745; border-radius:8px; padding:20px; margin:24px 0; text-align:left;">
                    <div style="display:flex; align-items:center; gap:10px; color:#2e7d32; font-weight:600; font-size:1.1rem; margin-bottom:8px;">
                        <i class="fas fa-check-circle" style="font-size:1.3rem;"></i> Paiement en Ligne Validé avec Succès !
                    </div>
                    <div style="font-size:0.9rem; color:#444; line-height:1.6;">
                        Montant réglé : <strong style="color:var(--vert);"><?= number_format($pInfo['montant'], 0, ',', ' ') ?> FCFA</strong> (<?= $pInfo['type'] === 'acompte_30' ? 'Acompte 30%' : 'Totalité 100%' ?>)<br>
                        Moyen : <strong><?= strtoupper(str_replace('_', ' ', $pInfo['moyen'])) ?></strong> | Transaction Réf : <code><?= htmlspecialchars($pInfo['reference']) ?></code><br>
                        <span style="color:#28a745; font-weight:500;">✓ Votre réservation est automatiquement <strong>VALIDÉE</strong> par le système hôtelier.</span>
                    </div>
                </div>
            <?php endif; ?>

            <div style="background: white; border-radius: 8px; padding: 20px; margin: 24px 0; text-align: left;">
                <h4 style="color: var(--vert); margin-bottom: 12px;">Prochaines étapes:</h4>
                <ol style="color: #666; line-height: 1.8;">
                    <li>Notre équipe va valider votre réservation sous 24h</li>
                    <li>Vous recevrez un email de confirmation avec les détails</li>
                    <li>Vous pourrez vous connecter avec votre email et votre code client pour modifier votre réservation</li>
                    <li>Le paiement se fera directement à l'hôtel lors de votre arrivée</li>
                </ol>
            </div>
            
            <div style="margin-top: 32px;">
                <a href="connexion-client.php" class="btn-reserver plein" style="margin-right: 16px;">
                    Me connecter
                </a>
                <a href="chambres.php" class="btn-reserver plein">
                    Autres réservations
                </a>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
function selectChambre(chambreId, el) {
    // Désélectionner toutes les cartes
    document.querySelectorAll('.chambre-card').forEach(function(card) {
        card.classList.remove('selected');
    });

    // Remonter jusqu'à la carte parente
    var card = el.closest('.chambre-card') || el;
    card.classList.add('selected');

    // Mettre à jour le champ hidden
    document.getElementById('chambre_id_selected').value = chambreId;

    // Activer le bouton continuer
    document.getElementById('btn_continue').disabled = false;
}
</script>

<?php include(__DIR__ . '/../layouts/footer.php'); ?>