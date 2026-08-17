<?php
/**
 * ════════════════════════════════════════════════════════
 * ROOM SERVICE & CARTE EN CHAMBRE
 * ════════════════════════════════════════════════════════
 */

session_start();
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Mail.php';

$database = new Database();
$db = $database->getConnection();

$succes = '';
$erreur = '';
$commande_ref = $_GET['suivi'] ?? ($_SESSION['last_rs_order'] ?? '');

$userId = $_SESSION['user_id'] ?? null;
$sejour_actif = null;
$reservation_attente = null;

if ($userId) {
    // 1. Chercher un séjour actif (Check-in effectué : statut = 'en_sejour')
    $stmtSejour = $db->prepare("
        SELECT r.*, c.nom as chambre_nom, c.type as chambre_type 
        FROM reservations r 
        JOIN chambres c ON r.chambre_id = c.id 
        WHERE r.user_id = ? AND r.statut = 'en_sejour' 
        ORDER BY r.date_arrivee DESC 
        LIMIT 1
    ");
    $stmtSejour->execute([$userId]);
    $sejour_actif = $stmtSejour->fetch(PDO::FETCH_ASSOC);

    // 2. Si pas de séjour actif, chercher une réservation en attente de Check-in
    if (!$sejour_actif) {
        $stmtAttente = $db->prepare("
            SELECT r.*, c.nom as chambre_nom 
            FROM reservations r 
            JOIN chambres c ON r.chambre_id = c.id 
            WHERE r.user_id = ? AND r.statut IN ('validee', 'en_cours', 'modifiee') 
            ORDER BY r.date_arrivee ASC 
            LIMIT 1
        ");
        $stmtAttente->execute([$userId]);
        $reservation_attente = $stmtAttente->fetch(PDO::FETCH_ASSOC);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_commande'])) {
    $client_nom        = trim($_POST['client_nom'] ?? '');
    $chambre_numero    = trim($_POST['chambre_numero'] ?? '');
    $client_telephone  = trim($_POST['client_telephone'] ?? '');
    $client_email      = trim($_POST['client_email'] ?? ($_SESSION['user_email'] ?? ''));
    $instructions      = trim($_POST['instructions'] ?? '');
    $panier_json       = $_POST['panier_json'] ?? '[]';
    $total_estime      = floatval($_POST['total_estime'] ?? 0);

    $items = json_decode($panier_json, true);

    // ── VÉRIFICATION STRICTE DU SÉJOUR ACTIF (STATUT 'en_sejour') ──
    $activeStayFound = null;

    if ($userId && $sejour_actif) {
        $activeStayFound = $sejour_actif;
    } else {
        // Pour un client non connecté, vérifier si la chambre ou référence fournie a un check-in actif
        if (!empty($chambre_numero)) {
            $stmtCheckGuest = $db->prepare("
                SELECT r.*, c.nom as chambre_nom, u.nom as u_nom, u.prenom as u_prenom, u.email as u_email 
                FROM reservations r 
                JOIN chambres c ON r.chambre_id = c.id 
                JOIN users u ON r.user_id = u.id 
                WHERE (r.reference = :ref_or_room OR c.nom = :ref_or_room) 
                  AND r.statut = 'en_sejour' 
                LIMIT 1
            ");
            $stmtCheckGuest->execute([':ref_or_room' => $chambre_numero]);
            $activeStayFound = $stmtCheckGuest->fetch(PDO::FETCH_ASSOC);
        }
    }

    if (!$activeStayFound) {
        if ($reservation_attente) {
            $statutLib = $reservation_attente['statut'] === 'validee' ? 'Validée (En attente de Check-in à la réception)' : 'En attente de validation';
            $erreur = "Accès restreint : Votre réservation n° {$reservation_attente['reference']} est actuellement au statut '{$statutLib}'. Le Room Service sera automatiquement activé dès votre arrivée à l'hôtel une fois le Check-in validé par la réception.";
        } else {
            $erreur = "Accès non autorisé : Le Room Service est exclusivement réservé aux clients séjournant actuellement à " . hotel_name() . " ayant effectué leur Check-in à la réception (Statut : En séjour).";
        }
    } elseif (empty($client_nom)) {
        $erreur = "Veuillez renseigner votre nom pour la livraison.";
    } elseif (empty($items) || count($items) === 0) {
        $erreur = "Votre plateau de commande est vide. Veuillez sélectionner au moins un article.";
    } else {
        try {
            $actualChambre = $activeStayFound['chambre_nom'] ?? $chambre_numero;
            $reference = 'RS-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
            $orderUserId = $userId ?: ($activeStayFound['user_id'] ?? null);

            $stmt = $db->prepare("
                INSERT INTO room_service_commandes 
                (reference, user_id, chambre_numero, client_nom, client_telephone, client_email, elements_commande, total_estime, instructions)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $reference, $orderUserId, $actualChambre, $client_nom,
                $client_telephone, $client_email, $panier_json, $total_estime, $instructions
            ]);

            $commande_ref = $reference;
            $_SESSION['last_rs_order'] = $reference;
            $succes = "Votre commande Room Service n° {$reference} pour la {$actualChambre} a été transmise en cuisine ! Livraison estimée sous 25 à 35 minutes.";

            // Notification Email si adresse email disponible
            if (!empty($client_email) && filter_var($client_email, FILTER_VALIDATE_EMAIL)) {
                try {
                    Mail::sendRoomServiceConfirmation($client_email, $client_nom, $reference, $actualChambre, $items, $total_estime, $instructions);
                } catch (Exception $mailErr) {
                    error_log("Room service confirmation mail error: " . $mailErr->getMessage());
                }
            }
        } catch (Exception $e) {
            error_log("Erreur commande room service: " . $e->getMessage());
            $erreur = "Erreur lors de la transmission de la commande. Veuillez contacter directement la réception.";
        }
    }
}

include(__DIR__ . '/../layouts/header.php');
?>

<style>
.rs-hero {
    background: linear-gradient(rgba(var(--vert-rgb),0.85), rgba(var(--noir-rgb),0.92)), 
                url('https://images.unsplash.com/photo-1544025162-d76694265947?w=1920&q=80') center/cover no-repeat;
    padding: 130px 24px 70px;
    text-align: center;
    color: #fff;
}

.rs-hero-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(2.4rem, 5vw, 3.8rem);
    font-weight: 300;
    margin-bottom: 14px;
    color: #fff;
}

.rs-hero-title em {
    font-style: italic;
    color: var(--or-pale);
}

.rs-hero-sub {
    font-family: 'Jost', sans-serif;
    font-size: 1rem;
    color: rgba(255,255,255,0.8);
    max-width: 620px;
    margin: 0 auto 24px;
}

* {
    box-sizing: border-box;
}

body {
    overflow-x: hidden;
}

.rs-container {
    max-width: 1140px;
    width: 94%;
    margin: 30px auto 80px;
    padding: 0;
    display: grid;
    grid-template-columns: minmax(0, 1fr) 330px;
    gap: 24px;
    align-items: flex-start;
    box-sizing: border-box;
}

.rs-menu-section {
    background: #fff;
    border-radius: 14px;
    padding: 24px 24px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.05);
    box-sizing: border-box;
    min-width: 0;
    width: 100%;
}

.rs-categories {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    border-bottom: 1.5px solid rgba(201,168,76,0.25);
    padding-bottom: 12px;
    overflow-x: auto;
}

.rs-cat-btn {
    background: transparent;
    border: 1px solid transparent;
    padding: 8px 16px;
    border-radius: 20px;
    font-family: 'Jost', sans-serif;
    font-size: 0.82rem;
    font-weight: 500;
    color: #555;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.3s;
}

.rs-cat-btn.active {
    background: var(--vert);
    color: var(--or);
    border-color: var(--vert);
    font-weight: 600;
}

.rs-items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    gap: 16px;
}

.rs-item-card {
    border: 1px solid #edf2f7;
    border-radius: 10px;
    padding: 16px;
    background: #faf8f3;
    transition: all 0.3s;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.rs-item-card:hover {
    border-color: var(--or);
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.05);
}

.rs-item-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--vert);
    margin-bottom: 6px;
}

.rs-item-desc {
    font-size: 0.82rem;
    color: #666;
    line-height: 1.5;
    margin-bottom: 14px;
}

.rs-item-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px dashed rgba(201,168,76,0.3);
    padding-top: 12px;
}

.rs-item-price {
    font-weight: 700;
    color: var(--or-texte);
    font-size: 1.05rem;
}

.btn-add-tray {
    background: var(--vert);
    color: var(--or);
    border: 1px solid var(--vert);
    padding: 8px 16px;
    font-family: 'Jost', sans-serif;
    font-size: 0.65rem;
    font-weight: 300;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.35s ease;
}

.btn-add-tray:hover {
    background: var(--vert-clair);
    color: #ffffff;
    border-color: var(--vert-clair);
    transform: translateY(-1px);
}

/* Plateau / Panier */
.rs-tray-box {
    background: #fff;
    border-radius: 14px;
    padding: 22px 18px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.05);
    border-top: 4px solid var(--or);
    position: sticky;
    top: 90px;
    box-sizing: border-box;
    width: 100%;
}

.rs-tray-box input,
.rs-tray-box textarea,
.rs-tray-box select {
    width: 100% !important;
    box-sizing: border-box !important;
}

.tray-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.6rem;
    color: var(--vert);
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.tray-items-list {
    min-height: 120px;
    max-height: 280px;
    overflow-y: auto;
    margin-bottom: 20px;
}

.tray-item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #edf2f7;
    font-size: 0.88rem;
}

.tray-total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 700;
    font-size: 1.2rem;
    color: var(--vert);
    padding: 16px 0;
    border-top: 2px solid var(--vert);
    margin-bottom: 20px;
}

@media (max-width: 900px) {
    .rs-container { grid-template-columns: 1fr; width: 95%; margin: 20px auto 60px; }
    .rs-tray-box { position: static; }
}
@media (max-width: 600px) {
    .rs-hero { padding: 50px 16px 36px; }
    .rs-hero-title { font-size: 1.9rem; }
    .rs-hero-sub { font-size: 0.92rem; }
    .rs-menu-section { padding: 18px 14px; }
    .rs-items-grid { grid-template-columns: 1fr; }
    .rs-tray-box { padding: 18px 14px; }
    .rs-categories { padding-bottom: 8px; }
}
</style>

<!-- HERO -->
<div class="rs-hero">
    <span style="text-transform:uppercase; letter-spacing:3px; color:var(--or); font-size:0.8rem; font-weight:600; display:block; margin-bottom:10px;">
        Service d'Étage 24h/24 &amp; Bien-être
    </span>
    <h1 class="rs-hero-title">Room Service &amp; <em>Saveurs en Chambre</em></h1>
    <p class="rs-hero-sub">
        Savourez l'excellence gastronomique de <?= htmlspecialchars(hotel_name()) ?> directement dans l'intimité de votre chambre, suite ou terrasse privative.
    </p>
    
    <!-- Barre de suivi rapide -->
    <div style="max-width:480px; margin:20px auto 0; display:flex; gap:8px; background:rgba(255,255,255,0.1); padding:6px; border-radius:30px; backdrop-filter:blur(5px); border:1px solid rgba(201,168,76,0.4);">
        <input type="text" id="trackRefInput" placeholder="Suivre ma commande (Ex: RS-2026-XXXX)" value="<?= htmlspecialchars($commande_ref) ?>" style="flex:1; background:transparent; border:none; color:#fff; padding:8px 16px; font-size:0.85rem; outline:none; font-family:'Jost',sans-serif;">
        <button type="button" onclick="startTracking(document.getElementById('trackRefInput').value)" style="background:var(--or); color:#111; border:none; border-radius:20px; padding:8px 18px; font-size:0.75rem; font-weight:600; cursor:pointer; text-transform:uppercase; letter-spacing:1px; transition:all 0.3s;">
            <i class="fas fa-search"></i> Suivre
        </button>
    </div>
</div>

<!-- CONTENU PRINCIPAL -->
<div class="rs-container">
    
    <!-- MENU CARTE -->
    <div class="rs-menu-section">
        
        <!-- CARTE LIVE TRACKER DE COMMANDE -->
        <div id="rsLiveTracker" style="display:none; background:#ffffff; border-radius:14px; padding:22px; box-shadow:0 8px 25px rgba(0,0,0,0.06); border-top:4px solid var(--or); margin-bottom:28px; border:1px solid rgba(201,168,76,0.25);">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; border-bottom:1px solid #f0ede6; padding-bottom:12px; margin-bottom:16px;">
                <div>
                    <span style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.18em; color:#888;">Suivi de votre commande en direct</span>
                    <h3 style="font-family:'Cormorant Garamond',serif; font-size:1.4rem; color:var(--vert); margin:2px 0 0 0;" id="trackerRefDisplay">Ref : ...</h3>
                </div>
                <div style="text-align:right;">
                    <span id="trackerStatusBadge" style="display:inline-block; padding:5px 12px; border-radius:20px; font-size:0.78rem; font-weight:600; background:#faf8f3; color:var(--or-texte); border:1px solid var(--or);">
                        <i class="fas fa-spinner fa-spin"></i> Recherche...
                    </span>
                    <div style="font-size:0.75rem; color:#666; margin-top:3px;" id="trackerDestination">Chambre : ...</div>
                </div>
            </div>

            <!-- Stepper visuel -->
            <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:8px; margin:18px 0; text-align:center;">
                <div class="tracker-step" id="step1">
                    <div class="step-circle" style="width:34px; height:34px; border-radius:50%; background:#e0dacb; color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:0.85rem; font-weight:bold; margin-bottom:4px; transition:all 0.3s;">1</div>
                    <div style="font-size:0.78rem; font-weight:600; color:var(--vert);">Reçue</div>
                    <div style="font-size:0.68rem; color:#888;">Réception</div>
                </div>
                <div class="tracker-step" id="step2">
                    <div class="step-circle" style="width:34px; height:34px; border-radius:50%; background:#e0dacb; color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:0.85rem; font-weight:bold; margin-bottom:4px; transition:all 0.3s;">2</div>
                    <div style="font-size:0.78rem; font-weight:600; color:var(--vert);">En Cuisine</div>
                    <div style="font-size:0.68rem; color:#888;">Préparation chef</div>
                </div>
                <div class="tracker-step" id="step3">
                    <div class="step-circle" style="width:34px; height:34px; border-radius:50%; background:#e0dacb; color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:0.85rem; font-weight:bold; margin-bottom:4px; transition:all 0.3s;">3</div>
                    <div style="font-size:0.78rem; font-weight:600; color:var(--vert);">Livrée</div>
                    <div style="font-size:0.68rem; color:#888;">En chambre</div>
                </div>
            </div>

            <div id="trackerMessage" style="background:#faf8f3; border-radius:8px; padding:10px 14px; font-size:0.82rem; color:#444; text-align:center; border:1px solid rgba(201,168,76,0.2);">
                Commande en cours de traitement...
            </div>
        </div>

        <?php if ($succes): ?>
            <div style="background:rgba(40,167,69,0.1); border-left:4px solid #28a745; padding:18px 22px; border-radius:8px; margin-bottom:24px; color:#155724;">
                <h4 style="margin:0 0 4px 0; font-family:'Cormorant Garamond',serif; font-size:1.3rem;"><i class="fas fa-concierge-bell"></i> Commande transmise avec succès !</h4>
                <p style="margin:0; font-size:0.9rem;"><?= htmlspecialchars($succes) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($erreur): ?>
            <div style="background:rgba(220,53,69,0.1); border-left:4px solid #dc3545; padding:14px 18px; border-radius:8px; margin-bottom:24px; color:#721c24;">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($erreur) ?>
            </div>
        <?php endif; ?>

        <!-- BANDEAU STATUT DU SÉJOUR CLIENT -->
        <?php if ($sejour_actif): ?>
            <div style="background:linear-gradient(135deg, var(--vert) 0%, var(--vert-clair) 100%); color:#fff; padding:20px 24px; border-radius:10px; margin-bottom:24px; border:1.5px solid var(--or); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; box-shadow:0 8px 24px rgba(var(--vert-rgb),0.2);">
                <div>
                    <span style="background:var(--or); color:#111; font-size:0.65rem; font-weight:700; padding:4px 12px; border-radius:20px; text-transform:uppercase; letter-spacing:0.15em; display:inline-block; margin-bottom:6px;">
                        <i class="fas fa-key"></i> Séjour Actif · Check-in Validé
                    </span>
                    <h4 style="font-family:'Cormorant Garamond',serif; font-size:1.4rem; margin:0; color:#fff;">
                        Service d'Étage pour : <strong><?= htmlspecialchars($sejour_actif['chambre_nom']) ?></strong>
                    </h4>
                    <div style="font-size:0.8rem; color:rgba(255,255,255,0.85); margin-top:2px;">
                        Réf séjour : <strong><?= htmlspecialchars($sejour_actif['reference']) ?></strong> · Du <?= date('d/m/Y', strtotime($sejour_actif['date_arrivee'])) ?> au <?= date('d/m/Y', strtotime($sejour_actif['date_depart'])) ?>
                    </div>
                </div>
                <div style="font-size:0.8rem; color:var(--or-pale); text-align:right;">
                    <i class="fas fa-concierge-bell"></i> Service d'étage prioritaire 24h/24
                </div>
            </div>
        <?php elseif ($reservation_attente): ?>
            <div style="background:rgba(var(--or-rgb),0.12); border-left:4px solid var(--or); padding:18px 22px; border-radius:0 8px 8px 0; margin-bottom:24px;">
                <div style="display:flex; align-items:flex-start; gap:12px;">
                    <i class="fas fa-clock" style="color:var(--or); font-size:1.3rem; margin-top:3px;"></i>
                    <div>
                        <strong style="color:var(--vert); font-size:1.05rem; display:block; margin-bottom:4px;">
                            Réservation n° <?= htmlspecialchars($reservation_attente['reference']) ?> · En attente de Check-in
                        </strong>
                        <p style="margin:0; font-size:0.88rem; color:#444; line-height:1.5;">
                            Votre réservation pour la <strong><?= htmlspecialchars($reservation_attente['chambre_nom']) ?></strong> est confirmée. Le Room Service et la commande en chambre seront <strong>automatiquement débloqués dès votre arrivée à l'hôtel</strong> une fois votre Check-in enregistré par notre réception.
                        </p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div style="background:rgba(var(--vert-rgb),0.06); border-left:4px solid var(--vert); padding:16px 20px; border-radius:0 8px 8px 0; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div>
                    <strong style="color:var(--vert); font-size:0.95rem;"><i class="fas fa-info-circle" style="color:var(--or);"></i> Consultation de la Carte Room Service</strong>
                    <div style="color:#555; font-size:0.85rem; margin-top:2px;">Le service d'étage est exclusivement réservé à nos hôtes résidant à l'hôtel ayant effectué leur Check-in.</div>
                </div>
                <?php if (!$userId): ?>
                    <a href="connexion-client.php" style="background:var(--vert); color:var(--or); border:1px solid var(--vert); padding:8px 18px; border-radius:6px; text-decoration:none; font-size:0.8rem; font-weight:600;">
                        <i class="fas fa-sign-in-alt"></i> Se Connecter
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Onglets Catégories -->
        <div class="rs-categories">
            <button type="button" class="rs-cat-btn active" onclick="filterMenu('all', this)">Tous les Plats &amp; Soins</button>
            <button type="button" class="rs-cat-btn" onclick="filterMenu('dejeuner', this)"><i class="fas fa-coffee" style="margin-right:6px; color:var(--or);"></i> Petits-déjeuners</button>
            <button type="button" class="rs-cat-btn" onclick="filterMenu('plats', this)"><i class="fas fa-utensils" style="margin-right:6px; color:var(--or);"></i> Plats Gastronomiques</button>
            <button type="button" class="rs-cat-btn" onclick="filterMenu('boissons', this)"><i class="fas fa-glass-martini-alt" style="margin-right:6px; color:var(--or);"></i> Cocktails &amp; Vins</button>
            <button type="button" class="rs-cat-btn" onclick="filterMenu('spa', this)"><i class="fas fa-spa" style="margin-right:6px; color:var(--or);"></i> Soins &amp; Massages</button>
        </div>

        <!-- Grille des Articles -->
        <div class="rs-items-grid" id="menuItemsGrid">
            
            <!-- Article 1 -->
            <div class="rs-item-card" data-cat="dejeuner">
                <div>
                    <h4 class="rs-item-title">Petit-déjeuner Continental VIP</h4>
                    <p class="rs-item-desc">Viennoiseries fraîches, baguette artisanale, confitures maison, œufs au choix, jus d'orange pressé, boisson chaude.</p>
                </div>
                <div class="rs-item-footer">
                    <span class="rs-item-price">6 500 F</span>
                    <button type="button" class="btn-add-tray" onclick="addToTray('Petit-déjeuner Continental VIP', 6500)">+ Ajouter</button>
                </div>
            </div>

            <!-- Article 2 -->
            <div class="rs-item-card" data-cat="dejeuner">
                <div>
                    <h4 class="rs-item-title">Petit-déjeuner Royal Signature</h4>
                    <p class="rs-item-desc">Assortiment continental complet, fruits tropicaux découpés, saumon fumé, coupe de champagne, pancakes au miel.</p>
                </div>
                <div class="rs-item-footer">
                    <span class="rs-item-price">12 000 F</span>
                    <button type="button" class="btn-add-tray" onclick="addToTray('Petit-déjeuner Royal Signature', 12000)">+ Ajouter</button>
                </div>
            </div>

            <!-- Article 3 -->
            <div class="rs-item-card" data-cat="plats">
                <div>
                    <h4 class="rs-item-title">Capitaine Braisé du Lac Togo</h4>
                    <p class="rs-item-desc">Pavé de capitaine sauvage grillé aux épices fines d'Agbodrafo, alloco croustillant et sauce tomate pimentée douce.</p>
                </div>
                <div class="rs-item-footer">
                    <span class="rs-item-price">14 500 F</span>
                    <button type="button" class="btn-add-tray" onclick="addToTray('Capitaine Braisé du Lac Togo', 14500)">+ Ajouter</button>
                </div>
            </div>

            <!-- Article 4 -->
            <div class="rs-item-card" data-cat="plats">
                <div>
                    <h4 class="rs-item-title">Filet de Bœuf Rossini</h4>
                    <p class="rs-item-desc">Cœur de filet de bœuf tendre, foie gras poêlé, jus réduit à la truffe et écrasé de pommes de terre à l'huile d'olive.</p>
                </div>
                <div class="rs-item-footer">
                    <span class="rs-item-price">18 500 F</span>
                    <button type="button" class="btn-add-tray" onclick="addToTray('Filet de Bœuf Rossini', 18500)">+ Ajouter</button>
                </div>
            </div>

            <!-- Article 5 -->
            <div class="rs-item-card" data-cat="boissons">
                <div>
                    <h4 class="rs-item-title">Cocktail Signature "Lagon Bleu"</h4>
                    <p class="rs-item-desc">Rhum blanc premium, curaçao bleu, purée de fruit de la passion fraîche, citron vert et feuille de menthe.</p>
                </div>
                <div class="rs-item-footer">
                    <span class="rs-item-price">5 000 F</span>
                    <button type="button" class="btn-add-tray" onclick="addToTray('Cocktail Signature Lagon Bleu', 5000)">+ Ajouter</button>
                </div>
            </div>

            <!-- Article 6 -->
            <div class="rs-item-card" data-cat="boissons">
                <div>
                    <h4 class="rs-item-title">Moët &amp; Chandon Brut Impérial (75cl)</h4>
                    <p class="rs-item-desc">Bouteille servie frappée dans son seau à champagne avec deux flûtes en cristal.</p>
                </div>
                <div class="rs-item-footer">
                    <span class="rs-item-price">65 000 F</span>
                    <button type="button" class="btn-add-tray" onclick="addToTray('Moët & Chandon Brut (75cl)', 65000)">+ Ajouter</button>
                </div>
            </div>

            <!-- Article 7 -->
            <div class="rs-item-card" data-cat="spa">
                <div>
                    <h4 class="rs-item-title">Massage Relaxant en Suite (60 min)</h4>
                    <p class="rs-item-desc">Rituel complet aux huiles essentielles bio de coco et baobab dispensé directement par notre praticienne en chambre.</p>
                </div>
                <div class="rs-item-footer">
                    <span class="rs-item-price">25 000 F</span>
                    <button type="button" class="btn-add-tray" onclick="addToTray('Massage Relaxant en Suite 60min', 25000)">+ Ajouter</button>
                </div>
            </div>

        </div>
    </div>

    <!-- PLATEAU / COMMANDE -->
    <div class="rs-tray-box">
        <h3 class="tray-title">
            <span><i class="fas fa-tray"></i> Mon Plateau</span>
            <span id="trayCountBadge" style="font-size:0.8rem; background:var(--vert); color:var(--or); padding:3px 10px; border-radius:12px;">0 article</span>
        </h3>

        <div class="tray-items-list" id="trayItemsList">
            <div style="text-align:center; color:#999; padding:40px 10px; font-size:0.85rem;">
                <i class="fas fa-utensils" style="font-size:2rem; color:#ccc; margin-bottom:8px; display:block;"></i>
                Votre plateau est vide.<br>Cliquez sur "+ Ajouter" pour composer votre commande.
            </div>
        </div>

        <div class="tray-total-row">
            <span>Total estimé :</span>
            <span id="trayTotalPrice" style="color:var(--or-texte);">0 FCFA</span>
        </div>

        <?php if ($sejour_actif): ?>
            <form method="post" id="rsOrderForm">
                <input type="hidden" name="action_commande" value="1">
                <input type="hidden" name="panier_json" id="f_panier_json" value="[]">
                <input type="hidden" name="total_estime" id="f_total_estime" value="0">

                <div style="margin-bottom:14px;">
                    <label style="font-size:0.8rem; font-weight:600; color:var(--vert); text-transform:uppercase;">Chambre de livraison</label>
                    <input type="text" name="chambre_numero" value="<?= htmlspecialchars($sejour_actif['chambre_nom']) ?>" readonly style="width:100%; padding:10px; border:1px solid var(--or); background:#faf8f3; border-radius:6px; font-size:0.9rem; font-weight:600; color:var(--vert);">
                </div>

                <div style="margin-bottom:14px;">
                    <label style="font-size:0.8rem; font-weight:600; color:var(--vert); text-transform:uppercase;">Votre Nom *</label>
                    <input type="text" name="client_nom" placeholder="Ex: M. Koffi" required value="<?= htmlspecialchars(trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''))) ?>" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; font-size:0.9rem;">
                </div>

                <div style="margin-bottom:14px;">
                    <label style="font-size:0.8rem; font-weight:600; color:var(--vert); text-transform:uppercase;">Téléphone / WhatsApp</label>
                    <input type="tel" name="client_telephone" placeholder="+228 90 00 00 00" value="<?= htmlspecialchars($_SESSION['user_telephone'] ?? '') ?>" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; font-size:0.9rem;">
                </div>

                <div style="margin-bottom:14px;">
                    <label style="font-size:0.8rem; font-weight:600; color:var(--vert); text-transform:uppercase;">Email pour reçu &amp; notifications</label>
                    <input type="email" name="client_email" placeholder="votre.email@exemple.com" value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; font-size:0.9rem;">
                </div>

                <div style="margin-bottom:20px;">
                    <label style="font-size:0.8rem; font-weight:600; color:var(--vert); text-transform:uppercase;">Instructions spéciales</label>
                    <textarea name="instructions" placeholder="Cuisson, heure souhaitée, allergies..." rows="2" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; font-size:0.85rem;"></textarea>
                </div>

                <button type="submit" style="width:100%; background:var(--vert); color:#ffffff !important; border:1px solid var(--vert); padding:15px; font-family:'Jost',sans-serif; font-weight:300; font-size:0.65rem; letter-spacing:0.32em; text-transform:uppercase; cursor:pointer; transition:all 0.4s ease;">
                    <i class="fas fa-concierge-bell" style="margin-right:8px; color:var(--or);"></i> Valider la commande
                </button>
            </form>
        <?php elseif ($reservation_attente): ?>
            <div style="background:#fdfbf7; border:1.5px dashed var(--or); border-radius:10px; padding:20px; text-align:center; margin-top:16px;">
                <i class="fas fa-lock" style="font-size:2rem; color:var(--or); margin-bottom:8px; display:block;"></i>
                <div style="font-size:0.9rem; font-weight:600; color:var(--vert);">Commande verrouillée</div>
                <div style="font-size:0.78rem; color:#666; margin:8px 0 16px; line-height:1.5;">
                    Votre réservation n° <strong><?= htmlspecialchars($reservation_attente['reference']) ?></strong> est enregistrée. Le Room Service sera disponible dès votre arrivée à l'hôtel une fois votre Check-in validé.
                </div>
                <a href="mon-compte.php#reservations" style="background:var(--vert); color:var(--or); padding:9px 18px; border-radius:6px; font-size:0.75rem; text-decoration:none; font-weight:600; display:inline-block;">
                    <i class="fas fa-calendar-check"></i> Voir ma réservation
                </a>
            </div>
        <?php elseif ($userId): ?>
            <div style="background:#fdfbf7; border:1.5px dashed #ccc; border-radius:10px; padding:20px; text-align:center; margin-top:16px;">
                <i class="fas fa-bed" style="font-size:2rem; color:#aaa; margin-bottom:8px; display:block;"></i>
                <div style="font-size:0.9rem; font-weight:600; color:var(--vert);">Aucun séjour actif</div>
                <div style="font-size:0.78rem; color:#666; margin:8px 0 16px; line-height:1.5;">
                    Le service d'étage est réservé aux clients séjournant actuellement dans notre établissement.
                </div>
                <a href="chambres.php" style="background:var(--vert); color:var(--or); padding:9px 18px; border-radius:6px; font-size:0.75rem; text-decoration:none; font-weight:600; display:inline-block;">
                    Réserver une Chambre
                </a>
            </div>
        <?php else: ?>
            <div style="background:#faf8f3; border:1.5px dashed var(--or); border-radius:10px; padding:20px; text-align:center; margin-top:16px;">
                <i class="fas fa-user-lock" style="font-size:2rem; color:var(--or); margin-bottom:8px; display:block;"></i>
                <div style="font-size:0.9rem; font-weight:600; color:var(--vert);">Espace Réservé aux Hôtes</div>
                <div style="font-size:0.78rem; color:#666; margin:8px 0 16px; line-height:1.5;">
                    Connectez-vous à votre compte client pour commander directement dans votre chambre.
                </div>
                <a href="connexion-client.php" style="background:var(--vert); color:var(--or); padding:9px 20px; border-radius:6px; font-size:0.75rem; text-decoration:none; font-weight:600; display:inline-block;">
                    <i class="fas fa-sign-in-alt"></i> Se Connecter
                </a>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
let tray = [];
let currentTrackingRef = "<?= htmlspecialchars($commande_ref) ?>";
let trackingTimer = null;

// Initialiser le suivi en direct si une référence est présente
document.addEventListener('DOMContentLoaded', () => {
    if (currentTrackingRef) {
        startTracking(currentTrackingRef);
    }
});

function startTracking(ref) {
    ref = (ref || '').trim();
    if (!ref) return;

    currentTrackingRef = ref;
    const trackerBox = document.getElementById('rsLiveTracker');
    if (trackerBox) trackerBox.style.display = 'block';

    pollOrderStatus(ref);

    if (trackingTimer) clearInterval(trackingTimer);
    trackingTimer = setInterval(() => {
        pollOrderStatus(currentTrackingRef);
    }, 6000); // Polling toutes les 6 secondes
}

async function pollOrderStatus(ref) {
    try {
        const res = await fetch(`../api/room-service-status.php?ref=${encodeURIComponent(ref)}`);
        const data = await res.json();

        if (!data.success) {
            document.getElementById('trackerStatusBadge').innerHTML = `<span style="color:#c62828;"><i class="fas fa-times-circle"></i> Introuvable</span>`;
            document.getElementById('trackerMessage').textContent = data.message || 'Commande introuvable.';
            return;
        }

        document.getElementById('trackerRefDisplay').textContent = `Ref : ${data.reference}`;
        document.getElementById('trackerDestination').textContent = `Chambre : ${data.chambre_numero} · Client : ${data.client_nom}`;
        document.getElementById('trackerMessage').textContent = data.statut_message;

        const badge = document.getElementById('trackerStatusBadge');
        badge.style.color = data.statut_color;
        badge.style.borderColor = data.statut_color;
        badge.innerHTML = `<i class="fas fa-circle" style="font-size:0.6rem; vertical-align:middle; margin-right:4px;"></i> ${data.statut_libelle}`;

        // Mise à jour des cercles du Stepper
        const step = data.statut_step; // 1, 2, 3
        updateStepVisual('step1', step >= 1, step === 1);
        updateStepVisual('step2', step >= 2, step === 2);
        updateStepVisual('step3', step >= 3, step === 3);

        if (data.statut === 'livree' || data.statut === 'annulee') {
            if (trackingTimer) clearInterval(trackingTimer);
        }
    } catch (e) {
        console.error("Tracking poll error:", e);
    }
}

function updateStepVisual(elementId, isCompleted, isCurrent) {
    const el = document.getElementById(elementId);
    if (!el) return;
    const circle = el.querySelector('.step-circle');
    if (!circle) return;

    if (isCompleted) {
        circle.style.background = isCurrent ? '#c9a84c' : '#28a745';
        circle.style.color = '#ffffff';
        circle.style.boxShadow = isCurrent ? '0 0 12px rgba(201,168,76,0.5)' : 'none';
    } else {
        circle.style.background = '#e0dacb';
        circle.style.color = '#ffffff';
        circle.style.boxShadow = 'none';
    }
}

function filterMenu(cat, btn) {
    document.querySelectorAll('.rs-cat-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const cards = document.querySelectorAll('.rs-item-card');
    cards.forEach(card => {
        if (cat === 'all' || card.getAttribute('data-cat') === cat) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

function addToTray(name, price) {
    const existing = tray.find(x => x.name === name);
    if (existing) {
        existing.qty += 1;
    } else {
        tray.push({ name: name, price: price, qty: 1 });
    }
    renderTray();
}

function removeFromTray(index) {
    tray.splice(index, 1);
    renderTray();
}

function updateQty(index, delta) {
    tray[index].qty += delta;
    if (tray[index].qty <= 0) {
        tray.splice(index, 1);
    }
    renderTray();
}

function renderTray() {
    const listEl = document.getElementById('trayItemsList');
    const badgeEl = document.getElementById('trayCountBadge');
    const totalEl = document.getElementById('trayTotalPrice');
    const fJson = document.getElementById('f_panier_json');
    const fTotal = document.getElementById('f_total_estime');

    let total = 0;
    let count = 0;

    if (tray.length === 0) {
        listEl.innerHTML = `
            <div style="text-align:center; color:#999; padding:40px 10px; font-size:0.85rem;">
                <i class="fas fa-utensils" style="font-size:2rem; color:#ccc; margin-bottom:8px; display:block;"></i>
                Votre plateau est vide.<br>Cliquez sur "+ Ajouter" pour composer votre commande.
            </div>
        `;
        badgeEl.textContent = '0 article';
        totalEl.textContent = '0 FCFA';
        fJson.value = '[]';
        fTotal.value = '0';
        return;
    }

    let html = '';
    tray.forEach((item, idx) => {
        const itemTotal = item.price * item.qty;
        total += itemTotal;
        count += item.qty;
        html += `
            <div class="tray-item-row">
                <div style="flex:1;">
                    <div style="font-weight:600; color:var(--vert);">${item.name}</div>
                    <div style="font-size:0.75rem; color:#777;">${item.price.toLocaleString('fr-FR')} F x ${item.qty}</div>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <button type="button" onclick="updateQty(${idx}, -1)" style="width:22px; height:22px; border:1px solid #ccc; background:#fff; border-radius:3px; cursor:pointer;">-</button>
                    <span style="font-weight:600;">${item.qty}</span>
                    <button type="button" onclick="updateQty(${idx}, 1)" style="width:22px; height:22px; border:1px solid #ccc; background:#fff; border-radius:3px; cursor:pointer;">+</button>
                    <button type="button" onclick="removeFromTray(${idx})" style="color:#e74c3c; border:none; background:transparent; cursor:pointer; margin-left:4px;"><i class="fas fa-trash-alt"></i></button>
                </div>
            </div>
        `;
    });

    listEl.innerHTML = html;
    badgeEl.textContent = count + ' article' + (count > 1 ? 's' : '');
    totalEl.textContent = total.toLocaleString('fr-FR') + ' FCFA';
    fJson.value = JSON.stringify(tray);
    fTotal.value = total;
}
</script>

<?php include(__DIR__ . '/../layouts/footer.php'); ?>
