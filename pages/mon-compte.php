<?php
/**
 * MON COMPTE — Espace client Hôtel SEGURO
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /ACATHON/pages/connexion-client.php');
    exit;
}

// Rediriger immédiatement les administrateurs vers le dashboard admin
if (in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin'])) {
    header('Location: /ACATHON/admin/dashboard.php');
    exit;
}

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Chambre.php';
require_once __DIR__ . '/../includes/Reservation.php';
require_once __DIR__ . '/../includes/Avis.php';
require_once __DIR__ . '/../includes/Mail.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$chambre = new Chambre($db);
$reservation = new Reservation($db);
$avisModel = new Avis($db);

$chambres_all = $chambre->getAllAvailable();
$user->getById($_SESSION['user_id']);
$reservations = $user->getReservations();
$fidelite = $user->getFideliteStatus();
$roomServiceOrders = $user->getRoomServiceOrders();

$erreur = '';
$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        $erreur = "Session expirée ou jeton de sécurité CSRF invalide. Veuillez recharger la page et réessayer.";
    } elseif (isset($_POST['action_modifier'])) {
        $reservation_id     = $_POST['reservation_id'] ?? '';
        $chambre_id         = $_POST['chambre_id'] ?? '';
        $date_arrivee       = $_POST['date_arrivee'] ?? '';
        $date_depart        = $_POST['date_depart'] ?? '';
        $nb_adultes         = intval($_POST['nb_adultes'] ?? 1);
        $nb_enfants         = intval($_POST['nb_enfants'] ?? 0);
        $demandes_speciales = trim($_POST['demandes_speciales'] ?? '');

        try {
            if ($reservation->getById($reservation_id) && $reservation->belongsTo($user->id)) {
                $targetChambreId = !empty($chambre_id) ? $chambre_id : $reservation->chambre_id;
                if ($reservation->modify($date_arrivee, $date_depart, $nb_adultes, $nb_enfants, $demandes_speciales, $targetChambreId)) {
                    $succes = 'Votre réservation a été modifiée avec succès. Notre équipe a été notifiée.';
                    $reservations = $user->getReservations();

                    // Alerte email envoyée aux administrateurs
                    try {
                        $adminList = $user->getAllAdmins();
                        $adminEmails = array_column($adminList, 'email');
                        $chInfo = $chambre->getById($targetChambreId);
                        $chNom = ($chInfo && isset($chInfo->nom)) ? $chInfo->nom : 'Chambre SEGURO';
                        $nomClient = $user->prenom . ' ' . $user->nom;
                        Mail::sendAdminReservationModificationNotification(
                            $adminEmails,
                            $reservation->reference,
                            $nomClient,
                            $user->email,
                            $chNom,
                            $date_arrivee,
                            $date_depart,
                            $reservation->getPrixTotalFormate(),
                            $demandes_speciales
                        );
                    } catch (Exception $mailErr) {
                        error_log("Mail admin modification alert error: " . $mailErr->getMessage());
                    }
                } else {
                    $erreur = $reservation->derniere_erreur ?: 'Erreur lors de la modification de la réservation.';
                }
            } else {
                $erreur = 'Réservation introuvable.';
            }
        } catch (Exception $e) {
            error_log("Reservation modify exception: " . $e->getMessage());
            $erreur = $e->getMessage();
        }
    }

    if (isset($_POST['action_annuler'])) {
        $reservation_id = $_POST['reservation_id'] ?? '';
        try {
            if ($reservation->getById($reservation_id) && $reservation->belongsTo($user->id)) {
                $ref = $reservation->reference;
                $arr = $reservation->date_arrivee;
                $dep = $reservation->date_depart;
                $chId = $reservation->chambre_id;
                if ($reservation->cancel()) {
                    $succes = 'Votre réservation a été annulée avec succès.';
                    $reservations = $user->getReservations();

                    // Alerte email envoyée aux administrateurs
                    try {
                        $adminList = $user->getAllAdmins();
                        $adminEmails = array_column($adminList, 'email');
                        $chInfo = $chambre->getById($chId);
                        $chNom = ($chInfo && isset($chInfo->nom)) ? $chInfo->nom : 'Chambre SEGURO';
                        $nomClient = $user->prenom . ' ' . $user->nom;
                        Mail::sendAdminReservationCancellationNotification(
                            $adminEmails,
                            $ref,
                            $nomClient,
                            $user->email,
                            $chNom,
                            $arr,
                            $dep
                        );
                    } catch (Exception $mailErr) {
                        error_log("Mail admin cancellation alert error: " . $mailErr->getMessage());
                    }
                } else {
                    $erreur = 'Erreur lors de l\'annulation.';
                }
            } else {
                $erreur = 'Réservation introuvable.';
            }
        } catch (Exception $e) {
            error_log("mon-compte annuler: " . $e->getMessage());
            $erreur = 'Erreur lors de l\'annulation de la réservation.';
        }
    }

    if (isset($_POST['action_avis'])) {
        $reservation_id = $_POST['reservation_id'] ?? '';
        $note = (int)($_POST['note'] ?? 5);
        $titre = trim($_POST['titre'] ?? '');
        $commentaire = trim($_POST['commentaire'] ?? '');

        if (empty($commentaire)) {
            $erreur = 'Veuillez rédiger un court commentaire sur votre séjour.';
        } else {
            try {
                if ($reservation->getById($reservation_id) && $reservation->belongsTo($user->id)) {
                    $ok = $avisModel->create($reservation_id, $user->id, $reservation->chambre_id, $note, $titre, $commentaire);
                    if ($ok) {
                        $succes = "Merci infiniment pour votre avis ! Votre témoignage est précieux.";
                    } else {
                        $erreur = "Vous avez déjà déposé un avis pour ce séjour.";
                    }
                }
            } catch (Exception $e) {
                error_log("mon-compte avis error: " . $e->getMessage());
                $erreur = "Erreur lors de l'enregistrement de votre avis.";
            }
        }
    }

    // ── NOUVELLE ACTION : Modifier le profil ──
    if (isset($_POST['action_modifier_profil'])) {
        $nom       = trim($_POST['nom'] ?? '');
        $prenom    = trim($_POST['prenom'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $pays      = trim($_POST['pays'] ?? '');
        $ville     = trim($_POST['ville'] ?? '');
        $adresse   = trim($_POST['adresse'] ?? '');

        if (empty($nom) || empty($prenom)) {
            $erreur = "Le nom et le prénom sont obligatoires.";
        } else {
            if ($user->updateProfile($user->id, $nom, $prenom, $telephone, $pays, $ville, $adresse)) {
                $succes = "Vos coordonnées personnelles ont été mises à jour avec succès.";
                $_SESSION['user_nom'] = $user->nom;
                $_SESSION['user_prenom'] = $user->prenom;
            } else {
                $erreur = "Erreur lors de la mise à jour de vos coordonnées.";
            }
        }
    }

    // ── NOUVELLE ACTION : Changer d'adresse email ──
    if (isset($_POST['action_changer_email'])) {
        $nouvel_email = trim($_POST['nouvel_email'] ?? '');
        $res = $user->changeEmail($user->id, $nouvel_email);
        if ($res['success']) {
            $succes = $res['message'];
            $_SESSION['user_email'] = $user->email;
        } else {
            $erreur = $res['message'];
        }
    }

    // ── NOUVELLE ACTION : Demander un nouveau Code Client ──
    if (isset($_POST['action_demander_code_client'])) {
        $res = $user->requestNewCodeClient($user->id);
        if ($res['success']) {
            $succes = $res['message'];
        } else {
            $erreur = $res['message'];
        }
    }

    // ── NOUVELLE ACTION : Confirmer et activer le nouveau Code Client ──
    if (isset($_POST['action_confirmer_code_client'])) {
        $nouveau_code_saisi = trim($_POST['nouveau_code_saisi'] ?? '');
        if (empty($nouveau_code_saisi)) {
            $erreur = "Veuillez saisir le nouveau Code Client reçu par email.";
        } else {
            $res = $user->confirmNewCodeClient($user->id, $nouveau_code_saisi);
            if ($res['success']) {
                $succes = $res['message'];
            } else {
                $erreur = $res['message'];
            }
        }
    }
}

include(__DIR__ . '/../layouts/header.php');
?>

<style>
  :root {
    --vert: #1a3a2a; --vert-clair: #2d5c40;
    --or: #c9a84c;   --or-pale: #f5e9c4;
    --noir: #111111;
  }

  .compte-container {
    max-width: 1100px;
    margin: 100px auto 80px;
    padding: 0 28px;
  }

  /* ── Header compte ── */
  .compte-header {
    background: linear-gradient(135deg, var(--vert), var(--vert-clair));
    border-radius: 4px;
    padding: 44px 48px;
    margin-bottom: 48px;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24px;
    flex-wrap: wrap;
  }
  .compte-header::before {
    content: '';
    position: absolute;
    top: -60px; right: -40px;
    width: 300px; height: 300px;
    background: rgba(255,255,255,0.04);
    border-radius: 50%;
    pointer-events: none;
  }
  .compte-info { position: relative; z-index: 1; }

  .compte-nom {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 300;
    font-size: 2.4rem;
    color: #fff;
    margin-bottom: 6px;
    letter-spacing: 0.04em;
  }
  .compte-email {
    font-family: 'Jost', sans-serif;
    font-weight: 200;
    font-size: 0.8rem;
    color: rgba(255,255,255,0.65);
    letter-spacing: 0.06em;
    margin-bottom: 12px;
  }
  .compte-code {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(201,168,76,0.3);
    border-radius: 4px;
    padding: 8px 18px;
    display: inline-block;
    font-family: 'Jost', sans-serif;
    font-weight: 300;
    font-size: 0.78rem;
    letter-spacing: 0.2em;
    color: var(--or-pale);
  }

  /* ── Bouton déconnexion ── */
  .btn-deconnexion {
    position: relative; z-index: 2;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: 'Jost', sans-serif;
    font-weight: 300;
    font-size: 0.6rem;
    letter-spacing: 0.35em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.65);
    border: 1px solid rgba(255,255,255,0.2);
    padding: 10px 22px;
    text-decoration: none;
    transition: all 0.3s;
    white-space: nowrap;
    align-self: flex-start;
    margin-top: 8px;
  }
  .btn-deconnexion:hover {
    background: rgba(220,53,69,0.2);
    border-color: rgba(220,53,69,0.5);
    color: #ff8080;
  }
  .btn-deconnexion-icon {
    font-size: 0.9rem;
    line-height: 1;
  }

  /* ── Titres de section ── */
  .section-title {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 300;
    font-size: 2rem;
    color: var(--vert);
    margin-bottom: 32px;
    padding-bottom: 16px;
    position: relative;
    letter-spacing: 0.04em;
  }
  .section-title::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0;
    width: 60px; height: 1px;
    background: var(--or);
  }

  /* ── Alertes ── */
  .alert {
    padding: 16px 20px;
    margin-bottom: 24px;
    font-family: 'Jost', sans-serif;
    font-weight: 200;
    font-size: 0.8rem;
    letter-spacing: 0.04em;
    border-left: 3px solid;
  }
  .alert-success { background: rgba(40,167,69,0.06); color: #1e7e34; border-left-color: #28a745; }
  .alert-error   { background: rgba(220,53,69,0.06); color: #c0392b; border-left-color: #dc3545; }

  /* ── Cartes réservation ── */
  .reservations-grid { display: grid; gap: 20px; }

  .reservation-card {
    background: #fff;
    border-left: 3px solid var(--or);
    padding: 32px 36px;
    box-shadow: 0 4px 20px rgba(26,58,42,0.06);
    transition: box-shadow 0.3s, transform 0.3s;
  }
  .reservation-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 36px rgba(26,58,42,0.1);
  }

  .reservation-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 14px;
  }
  .reservation-ref {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 400;
    font-size: 1.2rem;
    color: var(--vert);
    letter-spacing: 0.04em;
  }
  .reservation-date-creation {
    font-family: 'Jost', sans-serif;
    font-weight: 200;
    font-size: 0.62rem;
    color: #aaa;
    letter-spacing: 0.12em;
    margin-top: 4px;
  }

  /* Statuts — Badges Visibles et Raffinés */
  .reservation-statut {
    font-family: 'Jost', sans-serif;
    font-weight: 500;
    font-size: 0.78rem;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 8px 18px;
    border-radius: 30px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }
  .statut-validee   { background: #e8f5e9; color: #1b5e20; border: 1.5px solid #a5d6a7; font-weight: 600; box-shadow: 0 2px 10px rgba(46,125,50,0.15); }
  .statut-en_cours  { background: #fef5e7; color: #d35400; border: 1.5px solid #f8c471; font-weight: 600; }
  .statut-modifiee  { background: #ebf5fb; color: #2980b9; border: 1.5px solid #aed6f1; font-weight: 600; }
  .statut-annulee   { background: #fdedec; color: #c0392b; border: 1.5px solid #f5b7b1; font-weight: 600; }
  .statut-terminee  { background: #f4f6f7; color: #566573; border: 1.5px solid #d5dbdb; font-weight: 600; }

  /* Note Hotel / Conciergerie */
  .note-hotel-block {
    background: #fdfbf7;
    border-left: 4px solid var(--vert);
    border-radius: 0 8px 8px 0;
    padding: 16px 20px;
    margin-bottom: 20px;
  }
  .note-hotel-header {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--vert);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .note-hotel-body {
    font-size: 0.92rem;
    color: #444;
    line-height: 1.6;
  }

  .reservation-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
  }
  .detail-item { padding: 14px 16px; background: #f9f7f2; }
  .detail-label {
    font-family: 'Jost', sans-serif;
    font-weight: 200;
    font-size: 0.52rem;
    letter-spacing: 0.4em;
    text-transform: uppercase;
    color: #aaa;
    margin-bottom: 6px;
  }
  .detail-value {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 400;
    font-size: 1rem;
    color: var(--vert);
  }

  .demandes-block {
    background: rgba(201,168,76,0.04);
    border-left: 2px solid rgba(201,168,76,0.25);
    padding: 12px 16px;
    margin-bottom: 20px;
    font-family: 'Jost', sans-serif;
    font-weight: 200;
    font-size: 0.72rem;
    color: #777;
    font-style: italic;
    letter-spacing: 0.04em;
  }
  .demandes-block strong {
    display: block;
    font-style: normal;
    font-weight: 300;
    font-size: 0.52rem;
    letter-spacing: 0.35em;
    text-transform: uppercase;
    color: #bbb;
    margin-bottom: 4px;
  }

  .reservation-actions { display: flex; gap: 10px; flex-wrap: wrap; }

  .btn-action {
    font-family: 'Jost', sans-serif;
    font-weight: 300;
    font-size: 0.58rem;
    letter-spacing: 0.3em;
    text-transform: uppercase;
    padding: 9px 20px;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
  }
  .btn-modifier { background: var(--or); color: #fff; }
  .btn-modifier:hover { background: #b8941f; transform: translateY(-1px); color: #fff; }
  .btn-annuler  { background: #dc3545; color: #fff; }
  .btn-annuler:hover { background: #c82333; transform: translateY(-1px); }
  .btn-detail   { background: transparent; color: var(--vert); border: 1px solid rgba(26,58,42,0.3); }
  .btn-detail:hover { background: var(--vert); color: #fff; }

  /* ── Empty state ── */
  .empty-state { text-align: center; padding: 60px 20px; }
  .empty-state-icon { font-size: 3rem; color: rgba(201,168,76,0.25); margin-bottom: 20px; }
  .empty-state-title {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 300;
    font-size: 1.8rem;
    color: var(--vert);
    margin-bottom: 12px;
  }
  .empty-state p { font-family: 'Jost', sans-serif; font-weight: 200; font-size: 0.78rem; color: #aaa; margin-bottom: 28px; }

  /* ── Modales ── */
  .modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 2000;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  .modal-content {
    background: #fff;
    padding: 44px 48px;
    max-width: 620px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
  }
  .modal-close {
    position: absolute; top: 20px; right: 20px;
    width: 32px; height: 32px;
    border: 1px solid rgba(201,168,76,0.2);
    background: transparent;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; color: #aaa;
    transition: all 0.3s;
  }
  .modal-close:hover { background: #dc3545; color: #fff; border-color: #dc3545; }
  .modal-title {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 300;
    font-size: 1.8rem;
    color: var(--vert);
    margin-bottom: 32px;
    letter-spacing: 0.04em;
  }
  .modal-title em { font-style: italic; color: var(--or); }

  .form-group { margin-bottom: 24px; }
  .form-label {
    font-family: 'Jost', sans-serif;
    font-weight: 200;
    font-size: 0.52rem;
    letter-spacing: 0.45em;
    text-transform: uppercase;
    color: #aaa;
    display: block;
    margin-bottom: 10px;
  }
  .form-control {
    width: 100%;
    background: transparent;
    border: none;
    border-bottom: 1px solid rgba(201,168,76,0.25);
    color: #1a1a1a;
    font-family: 'Jost', sans-serif;
    font-weight: 300;
    font-size: 0.85rem;
    padding: 10px 0;
    outline: none;
    transition: border-color 0.3s;
  }
  .form-control:focus { border-bottom-color: var(--vert); }
  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }

  .modal-actions { display: flex; gap: 12px; margin-top: 32px; }

  @media (max-width: 768px) {
    .compte-container { margin: 80px auto 60px; padding: 0 16px; }
    .compte-header { padding: 32px 24px; flex-direction: column; align-items: flex-start; }
    .compte-nom { font-size: 1.8rem; }
    .reservation-card { padding: 24px 20px; }
    .form-row { grid-template-columns: 1fr; }
    .modal-content { padding: 32px 24px; }
    .btn-deconnexion { align-self: auto; width: 100%; justify-content: center; }
  }
</style>

<div class="compte-container">

  <!-- ── Header compte ── -->
  <div class="compte-header">
    <div class="compte-info">
      <div class="compte-nom">
        <?= htmlspecialchars($user->prenom) ?> <?= htmlspecialchars($user->nom) ?>
        <?php if ($user->email_verified == 1): ?>
          <span style="font-size:0.75rem; background:rgba(40,167,69,0.2); color:#28a745; padding:3px 10px; border-radius:12px; margin-left:8px; vertical-align:middle; font-weight:normal;">
            <i class="fas fa-check-circle"></i> Email vérifié
          </span>
        <?php endif; ?>
      </div>
      <div class="compte-email"><i class="fas fa-envelope" style="color:var(--or); margin-right:6px;"></i> <?= htmlspecialchars($user->email) ?></div>
      <div class="compte-code" style="display:inline-flex; align-items:center; gap:10px;">
        <span>Code Client : <strong><?= htmlspecialchars($user->code_client) ?></strong></span>
        <button type="button" onclick="copierCodeClient('<?= htmlspecialchars($user->code_client) ?>')" title="Copier mon code" style="background:rgba(201,168,76,0.25); border:1px solid var(--or); color:var(--or); border-radius:4px; padding:3px 10px; font-size:0.75rem; cursor:pointer; font-family:'Jost',sans-serif;">
          <i class="fas fa-copy"></i> Copier
        </button>
        <span id="copiedToast" style="display:none; font-size:0.75rem; color:var(--or); font-weight:600;">✓ Copié</span>
      </div>
    </div>
    <a href="../pages/deconnexion.php" class="btn-deconnexion">
      <span class="btn-deconnexion-icon"><i class="fas fa-sign-out-alt"></i></span>
      Déconnexion
    </a>
  </div>

  <!-- ── Carte Club Fidélité VIP ── -->
  <div style="background: linear-gradient(135deg, #1a3a2a 0%, #0e2218 100%); border: 1.5px solid #c9a84c; border-radius: 12px; padding: 26px 30px; margin-bottom: 30px; color: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.15); position: relative; overflow: hidden;">
    <div style="position: absolute; right: -20px; bottom: -20px; font-family:'Cormorant Garamond',serif; font-size: 8rem; color: rgba(201,168,76,0.06); font-weight: 700; pointer-events:none;">VIP</div>
    
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
      <div>
        <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 2px; color: var(--or); font-weight: 500;">Programme Privilège SEGURO</div>
        <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.8rem; margin: 4px 0 0 0; color: #fff; display: flex; align-items: center; gap: 10px;">
          <i class="fas fa-crown" style="color: <?= $fidelite['badge_color'] ?>;"></i>
          <?= htmlspecialchars($fidelite['grade_label']) ?>
        </h3>
      </div>
      <div style="text-align: right;">
        <span style="font-size: 0.8rem; background: rgba(201,168,76,0.2); border: 1px solid var(--or); color: var(--or); padding: 5px 14px; border-radius: 20px; font-weight: 500;">
          <?= $fidelite['remise_pourcentage'] > 0 ? ('-' . $fidelite['remise_pourcentage'] . '% automatique sur vos séjours') : 'Cumulez des nuits pour débloquer -5%' ?>
        </span>
      </div>
    </div>

    <!-- Stats compteurs -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; background: rgba(255,255,255,0.05); padding: 14px 18px; border-radius: 8px; margin-bottom: 18px;">
      <div>
        <div style="font-size: 0.75rem; color: rgba(255,255,255,0.6);">Séjours validés</div>
        <div style="font-size: 1.3rem; font-weight: 600; color: var(--or);"><?= $fidelite['nb_sejours'] ?></div>
      </div>
      <div>
        <div style="font-size: 0.75rem; color: rgba(255,255,255,0.6);">Nuits cumulées</div>
        <div style="font-size: 1.3rem; font-weight: 600; color: var(--or);"><?= $fidelite['nb_nuits'] ?> nuit(s)</div>
      </div>
      <div>
        <div style="font-size: 0.75rem; color: rgba(255,255,255,0.6);">Dépenses totales</div>
        <div style="font-size: 1.3rem; font-weight: 600; color: #fff;"><?= number_format($fidelite['total_depense'], 0, ',', ' ') ?> F</div>
      </div>
    </div>

    <!-- Avantages -->
    <div style="font-size: 0.85rem; color: rgba(255,255,255,0.85); line-height: 1.6; margin-bottom: 14px;">
      <strong style="color: var(--or); display: block; margin-bottom: 4px;">Vos privilèges actuels :</strong>
      <?php foreach ($fidelite['avantages'] as $av): ?>
        <div>✓ <?= htmlspecialchars($av) ?></div>
      <?php endforeach; ?>
    </div>

    <!-- Jauge progression -->
    <?php if (!empty($fidelite['prochain_grade'])): ?>
      <div style="margin-top: 14px;">
        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: rgba(255,255,255,0.7); margin-bottom: 4px;">
          <span>Progression vers <strong><?= htmlspecialchars($fidelite['prochain_grade']) ?></strong></span>
          <span>Encore <strong><?= $fidelite['nuits_restantes'] ?> nuit(s)</strong></span>
        </div>
        <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.15); border-radius: 4px; overflow: hidden;">
          <div style="width: <?= $fidelite['progression'] ?>%; height: 100%; background: var(--or); border-radius: 4px; transition: width 0.5s;"></div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($user->email_verified == 0): ?>
    <div style="background: rgba(201,168,76,0.12); border-left: 4px solid var(--or); padding: 16px 20px; border-radius: 0 8px 8px 0; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
      <div>
        <strong style="color: var(--vert);"><i class="fas fa-shield-alt" style="color:var(--or);"></i> Protégez votre compte :</strong>
        <span style="color: #555; font-size: .9rem;"> Votre adresse email n'est pas encore vérifiée.</span>
      </div>
      <a href="verifier-email.php" style="background: var(--vert); color: var(--or); padding: 8px 18px; border-radius: 6px; text-decoration: none; font-size: .85rem; font-weight: 500;">
        Vérifier mon email (OTP)
      </a>
    </div>
  <?php endif; ?>

  <?php if ($erreur): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i> <?= htmlspecialchars($erreur) ?></div>
  <?php endif; ?>

  <?php if ($succes): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle" style="margin-right:6px;"></i> <?= htmlspecialchars($succes) ?></div>
  <?php endif; ?>

  <!-- ── Navigation des Onglets Espace Client ── -->
  <div class="client-tabs-nav" style="display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 1.5px solid rgba(201,168,76,0.25); padding-bottom: 12px; flex-wrap: wrap;">
    <button type="button" class="tab-nav-btn active" onclick="switchTab('reservations')" id="tab_btn_reservations" style="background: rgba(201,168,76,0.15); border: 1px solid var(--or); font-family: 'Jost', sans-serif; font-size: 0.95rem; color: var(--vert); font-weight: 600; padding: 10px 22px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s;">
      <i class="fas fa-calendar-alt" style="color:var(--or);"></i> Mes Réservations (<?= count($reservations) ?>)
    </button>
    <button type="button" class="tab-nav-btn" onclick="switchTab('room_service')" id="tab_btn_room_service" style="background: transparent; border: 1px solid transparent; font-family: 'Jost', sans-serif; font-size: 0.95rem; color: #666; font-weight: 500; padding: 10px 22px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s;">
      <i class="fas fa-concierge-bell" style="color:var(--or);"></i> Room Service (<?= count($roomServiceOrders) ?>)
    </button>
    <button type="button" class="tab-nav-btn" onclick="switchTab('profil')" id="tab_btn_profil" style="background: transparent; border: 1px solid transparent; font-family: 'Jost', sans-serif; font-size: 0.95rem; color: #666; font-weight: 500; padding: 10px 22px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s;">
      <i class="fas fa-user-edit" style="color:var(--or);"></i> Mon Profil &amp; Coordonnées
    </button>
    <button type="button" class="tab-nav-btn" onclick="switchTab('securite')" id="tab_btn_securite" style="background: transparent; border: 1px solid transparent; font-family: 'Jost', sans-serif; font-size: 0.95rem; color: #666; font-weight: 500; padding: 10px 22px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s;">
      <i class="fas fa-key" style="color:var(--or);"></i> Sécurité &amp; Code Client
    </button>
  </div>

  <!-- ══════════════════════════════════════════════════════
       ONGLET 1 : MES RÉSERVATIONS
  ══════════════════════════════════════════════════════ -->
  <div id="tab_pane_reservations" class="tab-content-pane">
    <section>

      <?php 
        $sejour_actif = null;
        foreach ($reservations as $r) {
            if (($r['statut'] ?? '') === 'en_sejour') {
                $sejour_actif = $r;
                break;
            }
        }
      ?>

      <?php if ($sejour_actif): ?>
        <div style="background: linear-gradient(135deg, #1a3a2a 0%, #2d5c40 100%); color: #fff; padding: 24px 28px; border-radius: 10px; margin-bottom: 28px; border: 1.5px solid var(--or); box-shadow: 0 8px 24px rgba(26,58,42,0.25);">
          <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
              <span style="background: var(--or); color: #111; font-size: 0.65rem; font-weight: 600; padding: 4px 12px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.15em; display: inline-block; margin-bottom: 8px;">
                <i class="fas fa-key"></i> Séjour Actif · Bienvenue à l'Hôtel
              </span>
              <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.6rem; color: #fff; margin: 0 0 6px 0;">
                Vous séjournez actuellement dans la <?= htmlspecialchars($sejour_actif['chambre_nom'] ?? 'Chambre') ?>
              </h3>
              <p style="margin: 0; font-size: 0.9rem; color: rgba(255,255,255,0.85);">
                Réf: <strong><?= htmlspecialchars($sejour_actif['reference'] ?? '') ?></strong> · Du <?= date('d/m/Y', strtotime($sejour_actif['date_arrivee'])) ?> au <?= date('d/m/Y', strtotime($sejour_actif['date_depart'])) ?>.
              </p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
              <a href="room-service.php" style="background: var(--or); color: #111; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 0.8rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-concierge-bell"></i> Room Service 24h/24
              </a>
              <a href="facture.php?id=<?= $sejour_actif['id'] ?>" target="_blank" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.4); color: #fff; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-file-invoice"></i> Voir Facture
              </a>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <h2 class="section-title">Mes réservations</h2>

      <?php if (empty($reservations)): ?>
        <div class="empty-state">
          <div class="empty-state-icon">📅</div>
          <div class="empty-state-title">Aucune réservation</div>
          <p>Vous n'avez pas encore effectué de réservation à l'Hôtel SEGURO.</p>
          <a href="<?= $baseUrl ?>/pages/reservation-system.php" class="btn-action btn-modifier"
             style="padding:12px 32px;">
            Faire une réservation
          </a>
        </div>

    <?php else: ?>
      <div class="reservations-grid">
        <?php foreach ($reservations as $res): ?>
          <div class="reservation-card" style="<?= $res['statut']==='en_sejour' ? 'border-left: 6px solid #007bff; background:#fafcff;' : ($res['statut']==='validee' ? 'border-left: 6px solid #28a745;' : ($res['statut']==='en_cours' ? 'border-left: 6px solid #e67e22;' : ($res['statut']==='modifiee' ? 'border-left: 6px solid #2980b9;' : ($res['statut']==='annulee' ? 'border-left: 6px solid #c0392b; opacity:0.85;' : '')))) ?>">

            <div class="reservation-header">
              <div>
                <div class="reservation-ref"><?= htmlspecialchars($res['reference'] ?? '') ?></div>
                <div class="reservation-date-creation">
                  Créée le <?= date('d/m/Y', strtotime($res['created_at'])) ?>
                </div>
              </div>
              <span class="reservation-statut statut-<?= htmlspecialchars($res['statut'] ?? '') ?>">
                <?php if ($res['statut'] === 'en_sejour'): ?>
                  <span style="color:#007bff; font-weight:600;"><i class="fas fa-key"></i> En Séjour (Actif)</span>
                <?php elseif ($res['statut'] === 'validee'): ?>
                  <span style="color:#28a745; font-weight:600;"><i class="fas fa-check-circle"></i> Validée par l'Hôtel</span>
                <?php elseif ($res['statut'] === 'en_cours'): ?>
                  <span style="color:#e67e22; font-weight:600;"><i class="fas fa-clock"></i> En attente de validation</span>
                <?php elseif ($res['statut'] === 'modifiee'): ?>
                  <span style="color:#2980b9; font-weight:600;"><i class="fas fa-edit"></i> Modifiée (En attente)</span>
                <?php elseif ($res['statut'] === 'annulee'): ?>
                  <span style="color:#c0392b; font-weight:600;"><i class="fas fa-times-circle"></i> Séjour Annulé</span>
                <?php elseif ($res['statut'] === 'terminee'): ?>
                  <span style="color:#566573; font-weight:600;"><i class="fas fa-flag-checkered"></i> Séjour Terminé</span>
                <?php else: ?>
                  <?= htmlspecialchars((string)$reservation->getStatutLibelle($res['statut'] ?? '')) ?>
                <?php endif; ?>
              </span>
            </div>

            <div class="reservation-details">
              <div class="detail-item">
                <div class="detail-label">Chambre</div>
                <div class="detail-value"><?= htmlspecialchars($res['chambre_nom']) ?></div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Dates du séjour</div>
                <div class="detail-value">
                  <?= date('d/m/Y', strtotime($res['date_arrivee'])) ?>
                  &rarr;
                  <?= date('d/m/Y', strtotime($res['date_depart'])) ?>
                </div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Voyageurs</div>
                <div class="detail-value">
                  <?= $res['nb_adultes'] ?> adulte(s)
                  <?php if ($res['nb_enfants'] > 0): ?>
                    + <?= $res['nb_enfants'] ?> enfant(s)
                  <?php endif; ?>
                </div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Montant Total</div>
                <div class="detail-value" style="color:var(--or); font-weight:600;">
                  <?= number_format($res['prix_total'], 0, ',', ' ') ?> FCFA
                </div>
              </div>
            </div>

            <?php 
            $optsRes = $reservation->getOptions($res['id']);
            if (!empty($optsRes)): 
            ?>
              <div style="background:rgba(201,168,76,0.06); border-radius:6px; padding:12px 16px; margin-bottom:18px; border:1px solid rgba(201,168,76,0.2);">
                <div style="font-size:0.8rem; font-weight:600; color:var(--vert); margin-bottom:6px;">
                  <i class="fas fa-concierge-bell" style="color:var(--or);"></i> Services &amp; Options inclus :
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                  <?php foreach ($optsRes as $o): ?>
                    <span style="font-size:0.8rem; background:#fff; border:1px solid #e2dac9; padding:4px 12px; border-radius:15px; color:#333; font-weight:500;">
                      <i class="fas fa-check" style="color:var(--or); font-size:0.75rem;"></i> <?= htmlspecialchars($o['nom']) ?> (<?= number_format($o['prix_unitaire'], 0, ',', ' ') ?> FCFA <?= htmlspecialchars($o['unite'] ?? '') ?>)
                    </span>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($res['note_admin'])): ?>
              <div class="note-hotel-block">
                <div class="note-hotel-header">
                  <i class="fas fa-concierge-bell" style="color:var(--or);"></i> <strong>Message de la Réception / Conciergerie :</strong>
                </div>
                <div class="note-hotel-body">
                  <?= nl2br(htmlspecialchars($res['note_admin'])) ?>
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($res['demandes_speciales'])): ?>
              <div class="demandes-block">
                <strong>Vos demandes spéciales :</strong>
                <?= htmlspecialchars($res['demandes_speciales']) ?>
              </div>
            <?php endif; ?>

            <?php 
            $monAvis = $avisModel->getByReservationId($res['id']);
            if ($monAvis): 
            ?>
              <div style="background:rgba(201,168,76,0.08); border-left:4px solid var(--or); border-radius:0 8px 8px 0; padding:14px 18px; margin-bottom:18px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                  <div style="color:var(--or); font-size:1rem;">
                    <?php for($i=1;$i<=5;$i++): ?>
                      <i class="fas fa-star<?= $i <= $monAvis['note'] ? '' : '-o' ?>"></i>
                    <?php endfor; ?>
                    <strong style="color:var(--vert); font-size:0.95rem; margin-left:6px;"><?= htmlspecialchars($monAvis['titre'] ?? 'Avis vérifié') ?></strong>
                  </div>
                  <span style="font-size:0.75rem; color:#888;"><?= date('d/m/Y', strtotime($monAvis['created_at'])) ?></span>
                </div>
                <p style="margin:0; font-size:0.88rem; color:#444; font-style:italic;">
                  "<?= nl2br(htmlspecialchars($monAvis['commentaire'])) ?>"
                </p>
                <?php if (!empty($monAvis['reponse_hotel'])): ?>
                  <div style="margin-top:10px; padding-top:8px; border-top:1px dashed #d4c8b0; font-size:0.82rem; color:var(--vert);">
                    <strong><i class="fas fa-reply"></i> Réponse de l'Hôtel :</strong> <?= nl2br(htmlspecialchars($monAvis['reponse_hotel'])) ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <div class="reservation-actions">
              <a href="facture.php?id=<?= $res['id'] ?>" target="_blank" class="btn-action" style="background:rgba(201,168,76,0.12); color:#8c6d1f; border:1px solid rgba(201,168,76,0.4); text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                <i class="fas fa-file-invoice-dollar" style="color:var(--or);"></i> Facture / Reçu PDF
              </a>

              <?php if (in_array($res['statut'], ['validee', 'en_sejour'])): ?>
                <a href="room-service.php" class="btn-action" style="background:#faf8f3; color:var(--vert); border:1px solid var(--vert); text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                  <i class="fas fa-concierge-bell" style="color:var(--or);"></i> Room Service
                </a>
              <?php endif; ?>

              <?php if (!$monAvis && in_array($res['statut'], ['terminee', 'validee'])): ?>
                <button type="button" class="btn-action" style="background:var(--vert); color:var(--or); border:1px solid var(--or);" onclick="openModalAvis('<?= $res['id'] ?>', '<?= addslashes(htmlspecialchars($res['chambre_nom'])) ?>')">
                  <i class="fas fa-star" style="color:var(--or);"></i> Donner mon avis
                </button>
              <?php endif; ?>

              <?php if (in_array($res['statut'], ['en_cours', 'validee', 'modifiee'])): ?>
                <button class="btn-action btn-modifier"
                        onclick="openModalModifier('<?= $res['id'] ?>')">
                  Modifier
                </button>
              <?php endif; ?>

              <?php if (!in_array($res['statut'], ['terminee', 'annulee'])): ?>
                <form method="post" style="display:inline;"
                      onsubmit="return confirm('Confirmer l\'annulation de cette réservation ?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                  <button type="submit" name="action_annuler" class="btn-action btn-annuler">
                    Annuler
                  </button>
                </form>
              <?php endif; ?>

              <button class="btn-action btn-detail"
                      onclick="showDetails('<?= $res['id'] ?>')">
                Voir détails
              </button>
            </div>

          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    </section>
  </div>

  <!-- ══════════════════════════════════════════════════════
       ONGLET 2 : MON PROFIL & COORDONNÉES
  ══════════════════════════════════════════════════════ -->
  <div id="tab_pane_profil" class="tab-content-pane" style="display:none;">
    <div style="background:#fff; border-radius:12px; padding:36px; box-shadow:0 4px 24px rgba(26,58,42,0.06); border-left:4px solid var(--or); margin-bottom:30px;">
      <h2 class="section-title" style="margin-bottom:24px;">Mes informations personnelles</h2>
      <p style="color:#666; font-size:0.9rem; margin-bottom:28px;">
        Ces informations sont utilisées lors de la confirmation de vos séjours et pour vos factures officielles.
      </p>

      <form method="post" action="mon-compte.php#profil">
        <?= csrf_field() ?>
        <div class="form-row" style="margin-bottom:20px;">
          <div class="form-group">
            <label class="form-label" style="font-weight:600; color:var(--vert); font-size:0.85rem;">Prénom *</label>
            <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($user->prenom) ?>" required style="border-bottom:1.5px solid #ccc; padding:8px 0; font-size:0.95rem;">
          </div>
          <div class="form-group">
            <label class="form-label" style="font-weight:600; color:var(--vert); font-size:0.85rem;">Nom de famille *</label>
            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($user->nom) ?>" required style="border-bottom:1.5px solid #ccc; padding:8px 0; font-size:0.95rem;">
          </div>
        </div>

        <div class="form-row" style="margin-bottom:20px;">
          <div class="form-group">
            <label class="form-label" style="font-weight:600; color:var(--vert); font-size:0.85rem;">Numéro de Téléphone (WhatsApp)</label>
            <input type="tel" name="telephone" class="form-control" placeholder="+228 90 00 00 00" value="<?= htmlspecialchars($user->telephone ?? '') ?>" style="border-bottom:1.5px solid #ccc; padding:8px 0; font-size:0.95rem;">
          </div>
          <div class="form-group">
            <label class="form-label" style="font-weight:600; color:var(--vert); font-size:0.85rem;">Pays de résidence</label>
            <input type="text" name="pays" class="form-control" placeholder="Togo, France, Côte d'Ivoire, Bénin..." value="<?= htmlspecialchars($user->pays ?? 'Togo') ?>" style="border-bottom:1.5px solid #ccc; padding:8px 0; font-size:0.95rem;">
          </div>
        </div>

        <div class="form-row" style="margin-bottom:28px;">
          <div class="form-group">
            <label class="form-label" style="font-weight:600; color:var(--vert); font-size:0.85rem;">Ville</label>
            <input type="text" name="ville" class="form-control" placeholder="Lomé, Cotonou, Abidjan, Paris..." value="<?= htmlspecialchars($user->ville ?? '') ?>" style="border-bottom:1.5px solid #ccc; padding:8px 0; font-size:0.95rem;">
          </div>
          <div class="form-group">
            <label class="form-label" style="font-weight:600; color:var(--vert); font-size:0.85rem;">Adresse de résidence</label>
            <input type="text" name="adresse" class="form-control" placeholder="Quartier, Rue, N° de porte..." value="<?= htmlspecialchars($user->adresse ?? '') ?>" style="border-bottom:1.5px solid #ccc; padding:8px 0; font-size:0.95rem;">
          </div>
        </div>

        <div style="display:flex; justify-content:flex-end;">
          <button type="submit" name="action_modifier_profil" class="btn-action btn-modifier" style="background:var(--vert); color:var(--or); padding:14px 32px; font-size:0.9rem; cursor:pointer;">
            <i class="fas fa-save" style="margin-right:6px;"></i> Enregistrer mes coordonnées
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════════
       ONGLET 3 : SÉCURITÉ & CODE CLIENT
  ══════════════════════════════════════════════════════ -->
  <div id="tab_pane_securite" class="tab-content-pane" style="display:none;">
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(360px, 1fr)); gap:28px; margin-bottom:30px;">
      
      <!-- Carte 1 : Changer Email -->
      <div style="background:#fff; border-radius:12px; padding:32px; box-shadow:0 4px 24px rgba(26,58,42,0.06); border-left:4px solid var(--vert);">
        <h3 style="font-family:'Cormorant Garamond', serif; font-size:1.6rem; color:var(--vert); margin-bottom:8px;">
          <i class="fas fa-envelope-open-text" style="color:var(--or); margin-right:6px;"></i> Adresse Email
        </h3>
        <p style="color:#666; font-size:0.85rem; margin-bottom:20px; line-height:1.5;">
          Email actuel : <strong style="color:var(--vert);"><?= htmlspecialchars($user->email) ?></strong>
        </p>

        <form method="post" action="mon-compte.php#securite">
          <?= csrf_field() ?>
          <div class="form-group" style="margin-bottom:20px;">
            <label class="form-label" style="font-weight:600; color:var(--vert); font-size:0.85rem;">Nouvelle adresse email *</label>
            <input type="email" name="nouvel_email" class="form-control" placeholder="nouveau@email.com" required style="border-bottom:1.5px solid #ccc; padding:8px 0; font-size:0.95rem;">
            <small style="color:#777; font-size:0.8rem; margin-top:6px; display:block;">
              Un code de validation OTP vous sera envoyé sur cette nouvelle adresse.
            </small>
          </div>

          <button type="submit" name="action_changer_email" class="btn-action btn-modifier" style="background:var(--vert); color:var(--or); width:100%; padding:12px; justify-content:center;">
            <i class="fas fa-paper-plane" style="margin-right:6px;"></i> Mettre à jour mon email
          </button>
        </form>
      </div>

      <!-- Carte 2 : Renouveler Code Client -->
      <div style="background:#fff; border-radius:12px; padding:32px; box-shadow:0 4px 24px rgba(26,58,42,0.06); border-left:4px solid var(--or);">
        <h3 style="font-family:'Cormorant Garamond', serif; font-size:1.6rem; color:var(--vert); margin-bottom:8px;">
          <i class="fas fa-key" style="color:var(--or); margin-right:6px;"></i> Mon Code Client
        </h3>
        
        <div style="background:#faf8f3; border:1px solid rgba(201,168,76,0.3); border-radius:8px; padding:14px 18px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
          <div>
            <div style="font-size:0.75rem; color:#777; text-transform:uppercase; letter-spacing:0.05em;">Code Actuel</div>
            <div style="font-size:1.2rem; font-weight:700; color:var(--vert); letter-spacing:0.1em;"><?= htmlspecialchars($user->code_client) ?></div>
          </div>
          <button type="button" onclick="copierCodeClient('<?= htmlspecialchars($user->code_client) ?>')" style="background:var(--vert); color:var(--or); border:none; padding:6px 12px; border-radius:4px; font-size:0.8rem; cursor:pointer;">
            <i class="fas fa-copy"></i> Copier
          </button>
        </div>

        <p style="color:#666; font-size:0.85rem; margin-bottom:20px; line-height:1.5;">
          Pour des raisons de sécurité, vous pouvez renouveler votre Code Client à tout moment. Un nouveau code vous sera envoyé par email.
        </p>

        <!-- Étape 1 : Demander un nouveau code -->
        <form method="post" action="mon-compte.php#securite" style="margin-bottom:24px;">
          <?= csrf_field() ?>
          <button type="submit" name="action_demander_code_client" class="btn-action btn-detail" style="width:100%; padding:12px; justify-content:center; border-color:var(--vert); color:var(--vert); font-weight:600;">
            <i class="fas fa-paper-plane" style="margin-right:6px;"></i> Demander un nouveau Code Client par email
          </button>
        </form>

        <!-- Étape 2 : Confirmer le code reçu -->
        <form method="post" action="mon-compte.php#securite" style="border-top:1px dashed rgba(201,168,76,0.4); padding-top:20px;">
          <?= csrf_field() ?>
          <div class="form-group" style="margin-bottom:16px;">
            <label class="form-label" style="font-weight:600; color:var(--vert); font-size:0.85rem;">Entrez le nouveau Code Client reçu par email *</label>
            <input type="text" name="nouveau_code_saisi" class="form-control" placeholder="Ex: SEG-2026-XXXX" required style="border-bottom:1.5px solid #ccc; padding:8px 0; letter-spacing:0.08em; text-transform:uppercase; font-weight:600;">
            <small style="color:#777; font-size:0.8rem; margin-top:6px; display:block;">
              Saisissez le code reçu pour l'activer définitivement sur votre compte.
            </small>
          </div>

          <button type="submit" name="action_confirmer_code_client" class="btn-action btn-modifier" style="background:var(--vert); color:var(--or); width:100%; padding:12px; justify-content:center;">
            <i class="fas fa-check-circle" style="margin-right:6px;"></i> Confirmer et Activer mon Nouveau Code
          </button>
        </form>

      </div>

    </div>
  </div>

  <!-- ══════════════════════════════════════════════════════
       ONGLET 4 : MES COMMANDES ROOM SERVICE
  ══════════════════════════════════════════════════════ -->
  <div id="tab_pane_room_service" class="tab-content-pane" style="display:none;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:24px;">
      <div>
        <h3 style="font-family:'Cormorant Garamond', serif; font-size:1.8rem; color:var(--vert); margin:0;">
          Mes Commandes Room Service &amp; Carte en Chambre
        </h3>
        <p style="color:#666; font-size:0.85rem; margin:4px 0 0 0;">
          Suivez la préparation et la livraison de vos plateaux gastronomiques et soins en temps réel.
        </p>
      </div>
      <a href="room-service.php" style="background:var(--vert); color:var(--or); border:1px solid var(--vert); padding:10px 22px; border-radius:6px; text-decoration:none; font-size:0.8rem; font-weight:600; display:inline-flex; align-items:center; gap:8px;">
        <i class="fas fa-plus-circle"></i> Commander au Room Service
      </a>
    </div>

    <?php if (empty($roomServiceOrders)): ?>
      <div class="empty-state">
        <div class="empty-state-icon"><i class="fas fa-concierge-bell"></i></div>
        <h3 class="empty-state-title">Aucune commande Room Service</h3>
        <p>Vous n'avez pas encore passé de commande en chambre.</p>
        <a href="room-service.php" class="btn-action btn-modifier" style="background:var(--vert); color:var(--or); text-decoration:none; display:inline-block;">
          Découvrir la Carte Room Service
        </a>
      </div>
    <?php else: ?>
      <div style="display:grid; gap:20px;">
        <?php foreach ($roomServiceOrders as $ro): ?>
          <?php
            $rItems = json_decode($ro['elements_commande'], true) ?: [];
            $rStatut = $ro['statut'];
            $stBadge = '🟡 Reçue';
            $stBg = '#faf8f3';
            $stColor = '#c9a84c';
            $stStep = 1;

            if ($rStatut === 'en_preparation') {
              $stBadge = '🟠 En Cuisine / Préparation';
              $stBg = '#fff8e1';
              $stColor = '#e67e22';
              $stStep = 2;
            } elseif ($rStatut === 'livree') {
              $stBadge = '🟢 Livrée en Chambre';
              $stBg = '#e8f5e9';
              $stColor = '#28a745';
              $stStep = 3;
            } elseif ($rStatut === 'annulee') {
              $stBadge = '🔴 Annulée';
              $stBg = '#ffebee';
              $stColor = '#dc3545';
              $stStep = 0;
            }
          ?>
          <div style="background:#fff; border-radius:10px; padding:24px 28px; box-shadow:0 4px 18px rgba(0,0,0,0.05); border-top:3px solid var(--or); border-left:1px solid #eee; border-right:1px solid #eee; border-bottom:1px solid #eee;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:16px; border-bottom:1px solid #f0ede6; padding-bottom:14px;">
              <div>
                <span style="font-size:0.75rem; color:#888; text-transform:uppercase; letter-spacing:0.1em;">Commande Room Service</span>
                <h4 style="font-family:'Cormorant Garamond', serif; font-size:1.4rem; color:var(--vert); margin:2px 0 0 0;">
                  Ref : <?= htmlspecialchars($ro['reference']) ?>
                </h4>
                <div style="font-size:0.8rem; color:#666; margin-top:3px;">
                  <i class="fas fa-calendar-alt" style="color:var(--or);"></i> <?= date('d/m/Y à H:i', strtotime($ro['created_at'])) ?> · 
                  <i class="fas fa-door-closed" style="color:var(--or);"></i> <strong>Chambre : <?= htmlspecialchars($ro['chambre_numero']) ?></strong>
                </div>
              </div>
              <div style="text-align:right;">
                <span style="background:<?= $stBg ?>; color:<?= $stColor ?>; border:1px solid <?= $stColor ?>; padding:6px 14px; border-radius:20px; font-size:0.82rem; font-weight:600; display:inline-block;">
                  <?= $stBadge ?>
                </span>
                <div style="font-size:1.15rem; font-weight:700; color:var(--vert); margin-top:6px;">
                  <?= number_format($ro['total_estime'], 0, ',', ' ') ?> FCFA
                </div>
              </div>
            </div>

            <!-- Mini Stepper -->
            <?php if ($rStatut !== 'annulee'): ?>
              <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:8px; margin:16px 0 20px; text-align:center; background:#faf8f3; padding:12px 10px; border-radius:8px;">
                <div>
                  <div style="width:26px; height:26px; border-radius:50%; background:<?= $stStep >= 1 ? ($stStep === 1 ? '#c9a84c' : '#28a745') : '#ddd' ?>; color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem; font-weight:bold;">1</div>
                  <div style="font-size:0.72rem; font-weight:600; color:var(--vert); margin-top:2px;">Reçue</div>
                </div>
                <div>
                  <div style="width:26px; height:26px; border-radius:50%; background:<?= $stStep >= 2 ? ($stStep === 2 ? '#c9a84c' : '#28a745') : '#ddd' ?>; color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem; font-weight:bold;">2</div>
                  <div style="font-size:0.72rem; font-weight:600; color:var(--vert); margin-top:2px;">En Cuisine</div>
                </div>
                <div>
                  <div style="width:26px; height:26px; border-radius:50%; background:<?= $stStep >= 3 ? '#28a745' : '#ddd' ?>; color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem; font-weight:bold;">3</div>
                  <div style="font-size:0.72rem; font-weight:600; color:var(--vert); margin-top:2px;">Livrée</div>
                </div>
              </div>
            <?php endif; ?>

            <!-- Articles commandés -->
            <div style="margin-top:12px;">
              <div style="font-size:0.8rem; font-weight:600; color:#555; margin-bottom:8px;">Plats &amp; Soins commandés :</div>
              <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
                <?php foreach ($rItems as $it): ?>
                  <tr style="border-bottom:1px solid #f5f5f5;">
                    <td style="padding:6px 0; color:#333;"><strong><?= htmlspecialchars($it['name'] ?? $it['titre'] ?? 'Article') ?></strong> x <?= intval($it['qty'] ?? 1) ?></td>
                    <td style="padding:6px 0; text-align:right; font-weight:600; color:#1a3a2a;"><?= number_format(floatval($it['price'] ?? 0) * intval($it['qty'] ?? 1), 0, ',', ' ') ?> F</td>
                  </tr>
                <?php endforeach; ?>
              </table>
              <?php if (!empty($ro['instructions'])): ?>
                <div style="margin-top:10px; font-size:0.8rem; color:#666; font-style:italic;">
                  <strong>Instructions :</strong> "<?= htmlspecialchars($ro['instructions']) ?>"
                </div>
              <?php endif; ?>
            </div>

            <div style="margin-top:16px; text-align:right;">
              <a href="room-service.php?suivi=<?= urlencode($ro['reference']) ?>" style="background:transparent; border:1px solid var(--or); color:var(--vert); padding:6px 14px; border-radius:4px; text-decoration:none; font-size:0.75rem; font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                <i class="fas fa-radar"></i> Suivre en temps réel
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- ── Modal Modifier ── -->
<div id="modalModifier" class="modal">
  <div class="modal-content">
    <button class="modal-close" onclick="closeModal('modalModifier')">×</button>
    <h3 class="modal-title">Modifier ma <em>réservation</em></h3>

    <form method="post" id="formModifier">
      <?= csrf_field() ?>
      <input type="hidden" name="reservation_id" id="edit_reservation_id">

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Date d'arrivée</label>
          <input type="date" name="date_arrivee" id="edit_date_arrivee" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Date de départ</label>
          <input type="date" name="date_depart" id="edit_date_depart" class="form-control" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Adultes</label>
          <select name="nb_adultes" id="edit_nb_adultes" class="form-control" required>
            <?php for ($i = 1; $i <= 4; $i++): ?>
              <option value="<?= $i ?>"><?= $i ?> adulte<?= $i > 1 ? 's' : '' ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Enfants</label>
          <select name="nb_enfants" id="edit_nb_enfants" class="form-control">
            <?php for ($i = 0; $i <= 3; $i++): ?>
              <option value="<?= $i ?>"><?= $i ?> enfant<?= $i > 1 ? 's' : '' ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Demandes spéciales</label>
        <textarea name="demandes_speciales" id="edit_demandes" class="form-control" rows="3"></textarea>
      </div>

      <div class="modal-actions">
        <button type="submit" name="action_modifier" class="btn-action btn-modifier">
          Enregistrer
        </button>
        <button type="button" class="btn-action btn-detail"
                onclick="closeModal('modalModifier')">
          Annuler
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ── Modal Détails ── -->
<div id="modalDetails" class="modal">
  <div class="modal-content" style="max-width:680px;">
    <button class="modal-close" onclick="closeModal('modalDetails')">×</button>
    <h3 class="modal-title">Détails de la <em>réservation</em></h3>

    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="reservation_id" id="detail_reservation_id">

      <div style="background:#f9f7f2;padding:20px;margin-bottom:28px;border-left:2px solid var(--or);">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;">
          <div>
            <div class="detail-label">Référence</div>
            <div class="detail-value" id="detail_reference">—</div>
          </div>
          <div>
            <div class="detail-label">Statut</div>
            <div id="detail_statut">—</div>
          </div>
          <div>
            <div class="detail-label">Total</div>
            <div class="detail-value" style="color:var(--or);" id="detail_total">—</div>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Chambre</label>
        <select name="chambre_id" id="detail_chambre_select" class="form-control" required>
          <?php foreach ($chambres_all as $ch): ?>
            <option value="<?= $ch['id'] ?>">
              <?= htmlspecialchars($ch['nom']) ?> —
              <?= number_format($ch['prix_nuit'], 0, ',', ' ') ?> FCFA/nuit
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Arrivée</label>
          <input type="date" name="date_arrivee" id="detail_date_arrivee" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Départ</label>
          <input type="date" name="date_depart" id="detail_date_depart" class="form-control" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Adultes</label>
          <select name="nb_adultes" id="detail_nb_adultes" class="form-control" required>
            <?php for ($i = 1; $i <= 4; $i++): ?>
              <option value="<?= $i ?>"><?= $i ?> adulte<?= $i > 1 ? 's' : '' ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Enfants</label>
          <select name="nb_enfants" id="detail_nb_enfants" class="form-control">
            <?php for ($i = 0; $i <= 3; $i++): ?>
              <option value="<?= $i ?>"><?= $i ?> enfant<?= $i > 1 ? 's' : '' ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Demandes spéciales</label>
        <textarea name="demandes_speciales" id="detail_demandes" class="form-control" rows="3"></textarea>
      </div>

      <div id="detail_note_hotel_box" style="display:none; background:#fdfbf7; padding:16px 20px; margin-bottom:20px; border-left:4px solid var(--vert); border-radius:0 8px 8px 0;">
        <div style="font-size:0.8rem; font-weight:600; color:var(--vert); margin-bottom:4px;">
          <i class="fas fa-concierge-bell" style="color:var(--or);"></i> Message de l'Hôtel :
        </div>
        <div id="detail_note_hotel" style="font-size:0.9rem; color:#444; font-style:italic;"></div>
      </div>

      <div class="modal-actions">
        <button type="submit" name="action_modifier" class="btn-action btn-modifier">
          Enregistrer les modifications
        </button>
        <button type="button" class="btn-action btn-detail"
                onclick="closeModal('modalDetails')">
          Fermer
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const reservationsData = <?= json_encode($reservations) ?>;

function openModalModifier(id) {
  const r = reservationsData.find(x => x.id === id);
  if (!r) return;
  document.getElementById('edit_reservation_id').value = r.id;
  document.getElementById('edit_date_arrivee').value   = r.date_arrivee;
  document.getElementById('edit_date_depart').value    = r.date_depart;
  document.getElementById('edit_nb_adultes').value     = r.nb_adultes;
  document.getElementById('edit_nb_enfants').value     = r.nb_enfants;
  document.getElementById('edit_demandes').value       = r.demandes_speciales || '';
  document.getElementById('modalModifier').style.display = 'flex';
}

function showDetails(id) {
  const r = reservationsData.find(x => x.id === id);
  if (!r) return;
  document.getElementById('detail_reservation_id').value     = r.id;
  document.getElementById('detail_reference').textContent    = r.reference;
  document.getElementById('detail_statut').textContent       = r.statut;
  document.getElementById('detail_total').textContent        =
    new Intl.NumberFormat('fr-FR').format(r.prix_total) + ' FCFA';
  document.getElementById('detail_chambre_select').value     = r.chambre_id;
  document.getElementById('detail_date_arrivee').value       = r.date_arrivee;
  document.getElementById('detail_date_depart').value        = r.date_depart;
  document.getElementById('detail_nb_adultes').value         = r.nb_adultes;
  document.getElementById('detail_nb_enfants').value         = r.nb_enfants;
  document.getElementById('detail_demandes').value           = r.demandes_speciales || '';
  
  if (r.note_admin && r.note_admin.trim() !== '') {
    document.getElementById('detail_note_hotel_box').style.display = 'block';
    document.getElementById('detail_note_hotel').textContent = r.note_admin;
  } else {
    document.getElementById('detail_note_hotel_box').style.display = 'none';
  }

  document.getElementById('modalDetails').style.display      = 'flex';
}

function openModalAvis(resId, chambreNom) {
  document.getElementById('avis_reservation_id').value = resId;
  document.getElementById('avis_chambre_nom').textContent = chambreNom;
  document.getElementById('modalAvis').style.display = 'flex';
}

function switchTab(tabId) {
  document.querySelectorAll('.tab-content-pane').forEach(function(el) {
    el.style.display = 'none';
  });
  document.querySelectorAll('.tab-nav-btn').forEach(function(btn) {
    btn.style.color = '#666';
    btn.style.background = 'transparent';
    btn.style.borderColor = 'transparent';
    btn.classList.remove('active');
  });

  var pane = document.getElementById('tab_pane_' + tabId);
  var btn = document.getElementById('tab_btn_' + tabId);
  if (pane && btn) {
    pane.style.display = 'block';
    btn.style.color = 'var(--vert)';
    btn.style.background = 'rgba(201,168,76,0.15)';
    btn.style.borderColor = 'var(--or)';
    btn.classList.add('active');
    if (window.history && window.history.replaceState) {
      window.history.replaceState(null, null, '#' + tabId);
    }
  }
}

function copierCodeClient(code) {
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(code).then(function() {
      showCopiedToast();
    });
  } else {
    var temp = document.createElement('input');
    temp.value = code;
    document.body.appendChild(temp);
    temp.select();
    document.execCommand('copy');
    document.body.removeChild(temp);
    showCopiedToast();
  }
}

function showCopiedToast() {
  var t = document.getElementById('copiedToast');
  if (t) {
    t.style.display = 'inline';
    setTimeout(function() { t.style.display = 'none'; }, 2500);
  }
}

window.addEventListener('DOMContentLoaded', function() {
  var hash = window.location.hash.replace('#', '');
  if (hash && ['reservations', 'profil', 'securite', 'room_service'].indexOf(hash) !== -1) {
    switchTab(hash);
  } else {
    switchTab('reservations');
  }
});

document.querySelectorAll('.modal').forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) m.style.display = 'none'; });
});
</script>

<!-- ── MODAL AVIS CLIENT ── -->
<div id="modalAvis" class="modal">
  <div class="modal-content" style="max-width:520px;">
    <h2 style="font-family:'Cormorant Garamond', serif; font-size:1.8rem; color:var(--vert); margin-bottom:8px;">
      Votre avis sur votre séjour
    </h2>
    <p style="color:#666; font-size:0.9rem; margin-bottom:20px;">
      Hébergement : <strong id="avis_chambre_nom" style="color:var(--vert);"></strong>
    </p>

    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action_avis" value="1">
      <input type="hidden" name="reservation_id" id="avis_reservation_id">

      <div class="form-group" style="margin-bottom:18px;">
        <label class="form-label" style="font-weight:600;">Note globale *</label>
        <select name="note" class="form-control" style="font-size:1rem;" required>
          <option value="5">⭐⭐⭐⭐⭐ 5/5 — Exceptionnel</option>
          <option value="4">⭐⭐⭐⭐ 4/5 — Très bien</option>
          <option value="3">⭐⭐⭐ 3/5 — Bien</option>
          <option value="2">⭐⭐ 2/5 — Moyen</option>
          <option value="1">⭐ 1/5 — Décevant</option>
        </select>
      </div>

      <div class="form-group" style="margin-bottom:18px;">
        <label class="form-label" style="font-weight:600;">Titre de votre avis</label>
        <input type="text" name="titre" class="form-control" placeholder="Ex: Séjour magique et inoubliable !">
      </div>

      <div class="form-group" style="margin-bottom:24px;">
        <label class="form-label" style="font-weight:600;">Votre expérience détaillée *</label>
        <textarea name="commentaire" class="form-control" rows="4" placeholder="Ce que vous avez le plus apprécié (accueil, confort, repas, excursions...)" required></textarea>
      </div>

      <div class="modal-actions" style="display:flex; justify-content:flex-end; gap:12px;">
        <button type="button" class="btn-action btn-detail" onclick="closeModal('modalAvis')">Annuler</button>
        <button type="submit" class="btn-action btn-modifier" style="background:var(--vert); color:var(--or);">Publier mon avis</button>
      </div>
    </form>
  </div>
</div>

<?php include(__DIR__ . '/../layouts/footer.php'); ?>