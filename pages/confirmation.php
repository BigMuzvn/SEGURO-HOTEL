<?php
/**
 * CONFIRMATION DE RÉSERVATION
 */
session_start();

// Si pas d'info de réservation en session, rediriger
if (!isset($_SESSION['reservation_id'])) {
    header('Location: /ACATHON/pages/reservation-system.php');
    exit;
}

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/Reservation.php';

$database    = new Database();
$db          = $database->getConnection();
$reservationModel = new Reservation($db);

$resa = $reservationModel->getById($_SESSION['reservation_id']);
$userInfo = $_SESSION['user_info'] ?? [];

// Nettoyer les clés de session après affichage
unset($_SESSION['reservation_id'], $_SESSION['user_info']);

include(__DIR__ . '/../layouts/header.php');
?>

<style>
:root { --vert:#1a3a2a; --or:#c9a84c; --gris:#f8f8f6; }

.confirm-container {
    max-width: 680px;
    margin: 80px auto 100px;
    padding: 0 20px;
    font-family: 'Jost', sans-serif;
}

.confirm-hero {
    text-align: center;
    margin-bottom: 40px;
    animation: fadeInUp .6s ease;
}

.confirm-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #1a3a2a, #2d5c40);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    box-shadow: 0 8px 24px rgba(26,58,42,.25);
}

.confirm-icon i {
    font-size: 2rem;
    color: var(--or);
}

.confirm-hero h1 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2.4rem;
    color: var(--vert);
    margin-bottom: 8px;
}

.confirm-hero p {
    color: #666;
    font-size: .95rem;
}

.confirm-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0,0,0,.07);
    overflow: hidden;
    margin-bottom: 24px;
}

.confirm-card-header {
    background: var(--vert);
    padding: 18px 28px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.confirm-card-header i { color: var(--or); font-size: 1.1rem; }
.confirm-card-header span { color: #fff; font-size: .95rem; font-weight: 500; letter-spacing: .04em; text-transform: uppercase; }

.confirm-card-body { padding: 24px 28px; }

.confirm-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f0ede8;
    font-size: .9rem;
}

.confirm-row:last-child { border-bottom: none; }
.confirm-label { color: #888; }
.confirm-value { font-weight: 500; color: var(--vert); }

.ref-badge {
    display: inline-block;
    background: rgba(201,168,76,.15);
    color: #7a5c1e;
    padding: 6px 16px;
    border-radius: 20px;
    font-family: 'Courier New', monospace;
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: .05em;
}

.code-box {
    background: var(--gris);
    border: 2px dashed rgba(201,168,76,.4);
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    margin: 16px 0;
}

.code-box .label { font-size: .8rem; color: #888; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 8px; }
.code-box .code  { font-family: 'Courier New', monospace; font-size: 1.5rem; font-weight: 700; color: var(--vert); }

.confirm-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 30px;
}

.btn-primary-confirm {
    flex: 1;
    background: var(--vert);
    color: var(--or);
    padding: 14px 24px;
    border-radius: 8px;
    text-decoration: none;
    text-align: center;
    font-size: .9rem;
    font-weight: 500;
    transition: .3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-primary-confirm:hover { background: #2d5c40; }

.btn-outline-confirm {
    flex: 1;
    border: 2px solid var(--vert);
    color: var(--vert);
    padding: 14px 24px;
    border-radius: 8px;
    text-decoration: none;
    text-align: center;
    font-size: .9rem;
    font-weight: 500;
    transition: .3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-outline-confirm:hover { background: var(--vert); color: #fff; }

.notice-box {
    background: rgba(201,168,76,.08);
    border-left: 4px solid var(--or);
    padding: 16px 20px;
    border-radius: 0 8px 8px 0;
    font-size: .88rem;
    color: #555;
    line-height: 1.6;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<div class="confirm-container">

    <div class="confirm-hero">
        <div class="confirm-icon"><i class="fas fa-check"></i></div>
        <h1>Réservation enregistrée !</h1>
        <p>Votre demande a bien été reçue et sera confirmée sous 24h par notre équipe.</p>
    </div>

    <?php if ($resa): ?>
    <!-- Détails réservation -->
    <div class="confirm-card">
        <div class="confirm-card-header">
            <i class="fas fa-file-alt"></i>
            <span>Récapitulatif de votre réservation</span>
        </div>
        <div class="confirm-card-body">
            <div style="text-align:center;margin-bottom:16px;">
                <div class="ref-badge"><?= htmlspecialchars($resa->reference ?? '') ?></div>
            </div>
            <div class="confirm-row">
                <span class="confirm-label">Chambre</span>
                <span class="confirm-value"><?= htmlspecialchars($resa->chambre_nom ?? '—') ?></span>
            </div>
            <div class="confirm-row">
                <span class="confirm-label">Arrivée</span>
                <span class="confirm-value"><?= $resa->date_arrivee ? date('d/m/Y', strtotime($resa->date_arrivee)) : '—' ?></span>
            </div>
            <div class="confirm-row">
                <span class="confirm-label">Départ</span>
                <span class="confirm-value"><?= $resa->date_depart ? date('d/m/Y', strtotime($resa->date_depart)) : '—' ?></span>
            </div>
            <div class="confirm-row">
                <span class="confirm-label">Voyageurs</span>
                <span class="confirm-value">
                    <?= intval($resa->nb_adultes ?? 1) ?> adulte<?= ($resa->nb_adultes ?? 1) > 1 ? 's' : '' ?>
                    <?= ($resa->nb_enfants ?? 0) > 0 ? ', ' . intval($resa->nb_enfants) . ' enfant(s)' : '' ?>
                </span>
            </div>
            <div class="confirm-row">
                <span class="confirm-label">Total</span>
                <span class="confirm-value" style="font-size:1.1rem;color:var(--or);">
                    <?= number_format($resa->prix_total ?? 0, 0, ',', ' ') ?> <?= hotel_currency() ?>
                </span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Code client -->
    <?php if (!empty($userInfo['is_new']) && !empty($userInfo['code_client'])): ?>
    <div class="confirm-card">
        <div class="confirm-card-header">
            <i class="fas fa-id-card"></i>
            <span>Votre code d'accès client</span>
        </div>
        <div class="confirm-card-body">
            <div class="code-box">
                <div class="label">Conservez ce code précieusement</div>
                <div class="code"><?= htmlspecialchars($userInfo['code_client']) ?></div>
            </div>
            <p style="font-size:.85rem;color:#888;text-align:center;">
                Conservez ce code — vous l'utiliserez avec votre email (<strong><?= htmlspecialchars($userInfo['email'] ?? '') ?></strong>) pour vous connecter à votre espace client.
            </p>
        </div>
    </div>
    <?php elseif (!empty($userInfo['email']) && empty($userInfo['is_new'])): ?>
    <div class="confirm-card">
        <div class="confirm-card-header">
            <i class="fas fa-user-check"></i>
            <span>Compte client existant</span>
        </div>
        <div class="confirm-card-body" style="text-align:center;">
            <p style="font-size:.9rem;color:#444;line-height:1.5;">
                Cette réservation a été rattachée à votre compte client existant (<strong><?= htmlspecialchars($userInfo['email']) ?></strong>).<br>
                Vous pouvez vous connecter à votre espace avec votre code client habituel.
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Notice -->
    <div class="notice-box">
        <i class="fas fa-info-circle" style="color:var(--or);margin-right:8px;"></i>
        Notre équipe validera votre réservation sous <strong>24 heures</strong>.
        Vous recevrez un email de confirmation. Pour toute question :
        <strong><?= htmlspecialchars(hotel_email()) ?></strong> ou <strong><?= htmlspecialchars(hotel_phone()) ?></strong>.
    </div>

    <!-- Actions -->
    <div class="confirm-actions">
        <a href="mon-compte.php" class="btn-primary-confirm">
            <i class="fas fa-user"></i> Accéder à mon espace
        </a>
        <a href="../index.php" class="btn-outline-confirm">
            <i class="fas fa-home"></i> Retour à l'accueil
        </a>
    </div>

</div>

<?php include(__DIR__ . '/../layouts/footer.php'); ?>