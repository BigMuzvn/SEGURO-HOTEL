<?php
/**
 * ════════════════════════════════════════════════════════
 * FACTURE & REÇU OFFICIEL — Hôtel SEGURO
 * ════════════════════════════════════════════════════════
 */

session_start();

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/Reservation.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Chambre.php';
require_once __DIR__ . '/../includes/Option.php';

$resId = $_GET['id'] ?? '';
if (empty($resId)) {
    die("Identifiant de réservation manquant.");
}

$database = new Database();
$db = $database->getConnection();
$resModel = new Reservation($db);
$userModel = new User($db);
$chambreModel = new Chambre($db);

$resa = $resModel->getById($resId);
if (!$resa) {
    die("Réservation introuvable.");
}

// Vérification des droits d'accès (propriétaire du compte OU administrateur)
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin']);
$isOwner = $isLoggedIn && ($_SESSION['user_id'] === $resa->user_id);

if (!$isAdmin && !$isOwner) {
    die("Accès refusé. Veuillez vous connecter à votre compte pour consulter cette facture.");
}

$client = $userModel->getById($resa->user_id);
$chambre = $chambreModel->getById($resa->chambre_id);
$options = $resModel->getOptions($resa->id);

$nbNuits = max(1, (int)((strtotime($resa->date_depart) - strtotime($resa->date_arrivee)) / 86400));
$prixHebergement = (float)$resa->prix_nuit * $nbNuits;
$prixOptions = (float)($resa->prix_options ?? 0);
$montantReduction = (float)($resa->montant_reduction ?? 0);
$prixTotal = (float)$resa->prix_total;
$numFacture = "FACT-" . date('Y', strtotime($resa->created_at)) . "-" . substr(str_replace('-', '', $resa->id), 0, 8);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture <?= htmlspecialchars($resa->reference) ?> — Hôtel SEGURO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --vert: #1a3a2a;
            --vert-fonce: #0e2218;
            --or: #c9a84c;
            --or-fonce: #9c7b28;
            --gris-fond: #f8f6f0;
            --texte: #2b2b2b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Jost', sans-serif;
            background: #e9e6df;
            color: var(--texte);
            padding: 40px 20px;
        }
        .facture-container {
            max-width: 850px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.12);
            padding: 50px 60px;
            position: relative;
            border-top: 8px solid var(--vert);
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-family: 'Cormorant Garamond', serif;
            font-size: 7rem;
            color: rgba(201,168,76,0.04);
            font-weight: 700;
            letter-spacing: 15px;
            pointer-events: none;
            text-transform: uppercase;
        }
        .header-grid {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #f0ecdf;
            padding-bottom: 30px;
            margin-bottom: 30px;
        }
        .logo-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.2rem;
            color: var(--vert);
            letter-spacing: 2px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .logo-sub {
            color: var(--or);
            font-size: 0.85rem;
            letter-spacing: 3px;
            text-transform: uppercase;
            font-weight: 500;
        }
        .hotel-coords {
            font-size: 0.85rem;
            color: #666;
            margin-top: 10px;
            line-height: 1.5;
        }
        .facture-meta {
            text-align: right;
        }
        .facture-badge {
            display: inline-block;
            background: var(--vert);
            color: var(--or);
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        .meta-item {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 4px;
        }
        .meta-item strong {
            color: var(--vert);
        }
        .addresses-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 35px;
        }
        .addr-box {
            background: var(--gris-fond);
            border-radius: 8px;
            padding: 20px 24px;
            border: 1px solid #eae5d7;
        }
        .addr-title {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--or-fonce);
            font-weight: 600;
            margin-bottom: 10px;
        }
        .addr-name {
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--vert);
            margin-bottom: 4px;
        }
        .addr-text {
            font-size: 0.88rem;
            color: #555;
            line-height: 1.5;
        }
        .sejour-details {
            background: #fff;
            border: 1.5px solid #eae5d7;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
        }
        .sejour-col {
            font-size: 0.85rem;
        }
        .sejour-col strong {
            display: block;
            color: var(--vert);
            font-size: 0.95rem;
            margin-top: 2px;
        }
        table.facture-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table.facture-table th {
            background: var(--vert);
            color: #fff;
            padding: 12px 16px;
            text-align: left;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        table.facture-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f0ecdf;
            font-size: 0.9rem;
        }
        table.facture-table tr:last-child td {
            border-bottom: 2px solid var(--vert);
        }
        .totaux-grid {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 35px;
        }
        .totaux-box {
            width: 340px;
        }
        .totaux-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 0.95rem;
            color: #555;
        }
        .totaux-row.total-final {
            border-top: 2px solid var(--vert);
            margin-top: 8px;
            padding-top: 14px;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--vert);
        }
        .totaux-row.total-final .valeur {
            color: var(--or);
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.5rem;
        }
        .totaux-row.remise {
            color: #28a745;
            font-weight: 600;
        }
        .facture-footer {
            border-top: 1px solid #f0ecdf;
            padding-top: 24px;
            text-align: center;
            font-size: 0.8rem;
            color: #888;
            line-height: 1.6;
        }
        .actions-bar {
            max-width: 850px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-print {
            background: var(--vert);
            color: var(--or);
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        .btn-print:hover {
            background: var(--vert-fonce);
            transform: translateY(-2px);
        }
        .btn-back {
            color: var(--vert);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        @media print {
            body { background: white; padding: 0; }
            .actions-bar { display: none; }
            .facture-container {
                box-shadow: none;
                border-radius: 0;
                padding: 30px;
                max-width: 100%;
                border-top: none;
            }
        }
    </style>
</head>
<body>

    <div class="actions-bar">
        <a href="<?= $isAdmin ? '../admin/reservations.php' : 'mon-compte.php' ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
        <button onclick="window.print()" class="btn-print">
            <i class="fas fa-print"></i> Imprimer / Enregistrer en PDF
        </button>
    </div>

    <div class="facture-container">
        <div class="watermark">SEGURO</div>

        <!-- HEADER -->
        <div class="header-grid">
            <div>
                <div class="logo-title">HÔTEL SEGURO</div>
                <div class="logo-sub">Resort &amp; Nature de Luxe</div>
                <div class="hotel-coords">
                    Boulevard de la Marina, Lomé — Togo<br>
                    Tél : +228 90 00 00 00 | Email : contact@hotel-seguro.com<br>
                    NIF : 1000123456 | RCCM : TG-LOM-2024-B-999
                </div>
            </div>
            <div class="facture-meta">
                <div class="facture-badge">
                    <?= in_array($resa->statut, ['validee', 'terminee']) ? 'FACTURE OFFICIELLE' : 'REÇU PROVISOIRE' ?>
                </div>
                <div class="meta-item">N° Facture : <strong><?= htmlspecialchars($numFacture) ?></strong></div>
                <div class="meta-item">Réf. Réservation : <strong><?= htmlspecialchars($resa->reference) ?></strong></div>
                <div class="meta-item">Date d'émission : <strong><?= date('d/m/Y', strtotime($resa->created_at)) ?></strong></div>
                <div class="meta-item">Statut : 
                    <strong style="color:<?= $resa->statut==='validee' ? '#28a745' : ($resa->statut==='en_cours' ? '#e67e22' : '#2b2b2b') ?>;">
                        <?= strtoupper($resModel->getStatutLibelle($resa->statut)) ?>
                    </strong>
                </div>
            </div>
        </div>

        <!-- CLIENT & DATES -->
        <div class="addresses-grid">
            <div class="addr-box">
                <div class="addr-title">Facturé à :</div>
                <div class="addr-name"><?= htmlspecialchars(($client ? $client->prenom . ' ' . $client->nom : 'Client SEGURO')) ?></div>
                <div class="addr-text">
                    Code Client : <strong><?= htmlspecialchars($client ? $client->code_client : 'N/A') ?></strong><br>
                    Email : <?= htmlspecialchars($client ? $client->email : '') ?><br>
                    Tél : <?= htmlspecialchars($client ? ($client->telephone ?: 'Non renseigné') : '') ?><br>
                    Pays : <?= htmlspecialchars($client ? ($client->pays ?: 'Togo') : 'Togo') ?>
                </div>
            </div>
            <div class="addr-box">
                <div class="addr-title">Établissement émetteur :</div>
                <div class="addr-name">Hôtel SEGURO SARL</div>
                <div class="addr-text">
                    Service Facturation &amp; Conciergerie<br>
                    Boulevard de la Marina, Lomé, Togo<br>
                    Réception 24h/24 : +228 90 00 00 01<br>
                    Banque : Ecobank Togo | IBAN : TG54 TG05 4010 0100 1234 5678 9012
                </div>
            </div>
        </div>

        <!-- RÉCAPITULATIF SÉJOUR -->
        <div class="sejour-details">
            <div class="sejour-col">
                Hébergement :
                <strong><?= htmlspecialchars($chambre ? $chambre->nom : 'Chambre SEGURO') ?> (<?= ucfirst($chambre ? $chambre->type : '') ?>)</strong>
            </div>
            <div class="sejour-col">
                Période de séjour :
                <strong><?= date('d/m/Y', strtotime($resa->date_arrivee)) ?> &rarr; <?= date('d/m/Y', strtotime($resa->date_depart)) ?></strong>
            </div>
            <div class="sejour-col">
                Durée :
                <strong><?= $nbNuits ?> nuit<?= $nbNuits > 1 ? 's' : '' ?></strong>
            </div>
            <div class="sejour-col">
                Occupants :
                <strong><?= $resa->nb_adultes ?> adulte(s) <?= $resa->nb_enfants > 0 ? '+ ' . $resa->nb_enfants . ' enfant(s)' : '' ?></strong>
            </div>
        </div>

        <!-- TABLEAU DES PRESTATIONS -->
        <table class="facture-table">
            <thead>
                <tr>
                    <th>Désignation de la prestation</th>
                    <th style="text-align:center;">Unité / Période</th>
                    <th style="text-align:center;">Quantité</th>
                    <th style="text-align:right;">Prix Unitaire</th>
                    <th style="text-align:right;">Montant Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>Hébergement en <?= htmlspecialchars($chambre ? $chambre->nom : 'Chambre') ?></strong><br>
                        <span style="font-size:0.8rem; color:#777;">Du <?= date('d/m/Y', strtotime($resa->date_arrivee)) ?> au <?= date('d/m/Y', strtotime($resa->date_depart)) ?></span>
                    </td>
                    <td style="text-align:center;">par nuit</td>
                    <td style="text-align:center;"><?= $nbNuits ?></td>
                    <td style="text-align:right;"><?= number_format($resa->prix_nuit, 0, ',', ' ') ?> FCFA</td>
                    <td style="text-align:right; font-weight:600;"><?= number_format($prixHebergement, 0, ',', ' ') ?> FCFA</td>
                </tr>

                <?php if (!empty($options)): ?>
                    <?php foreach ($options as $opt): 
                        $optTotal = ($opt['unite'] === 'par nuit') ? ((float)$opt['prix_unitaire'] * $nbNuits * (int)$opt['quantite']) : ((float)$opt['prix_unitaire'] * (int)$opt['quantite']);
                    ?>
                    <tr>
                        <td>
                            <strong>Option : <?= htmlspecialchars($opt['nom']) ?></strong><br>
                            <span style="font-size:0.8rem; color:#777;"><?= htmlspecialchars($opt['description'] ?? '') ?></span>
                        </td>
                        <td style="text-align:center;"><?= htmlspecialchars($opt['unite']) ?></td>
                        <td style="text-align:center;"><?= $opt['quantite'] ?></td>
                        <td style="text-align:right;"><?= number_format($opt['prix_unitaire'], 0, ',', ' ') ?> FCFA</td>
                        <td style="text-align:right; font-weight:600;"><?= number_format($optTotal, 0, ',', ' ') ?> FCFA</td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- TOTAUX -->
        <div class="totaux-grid">
            <div class="totaux-box">
                <div class="totaux-row">
                    <span>Sous-total Hébergement :</span>
                    <strong><?= number_format($prixHebergement, 0, ',', ' ') ?> FCFA</strong>
                </div>

                <?php if ($prixOptions > 0): ?>
                <div class="totaux-row">
                    <span>Sous-total Options &amp; Services :</span>
                    <strong><?= number_format($prixOptions, 0, ',', ' ') ?> FCFA</strong>
                </div>
                <?php endif; ?>

                <?php if ($montantReduction > 0): ?>
                <div class="totaux-row remise">
                    <span><i class="fas fa-tag"></i> Remise Code Promotionnel :</span>
                    <span>-<?= number_format($montantReduction, 0, ',', ' ') ?> FCFA</span>
                </div>
                <?php endif; ?>

                <div class="totaux-row">
                    <span>Taxes de séjour &amp; TVA (incluses) :</span>
                    <span>0 FCFA</span>
                </div>

                <div class="totaux-row total-final">
                    <span>Total Net à Régler :</span>
                    <span class="valeur"><?= number_format($prixTotal, 0, ',', ' ') ?> FCFA</span>
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="facture-footer">
            <p><strong>Conditions de règlement :</strong> Règlement à la réception lors du check-in ou par Mobile Money / Virement bancaire.</p>
            <p>Pour toute question relative à cette facture, veuillez contacter notre conciergerie à <em>facturation@hotel-seguro.com</em> en rappelant la référence <strong><?= htmlspecialchars($resa->reference) ?></strong>.</p>
            <p style="margin-top:10px; color:#aaa;">Hôtel SEGURO — L'art de l'hospitalité d'exception.</p>
        </div>
    </div>

</body>
</html>
