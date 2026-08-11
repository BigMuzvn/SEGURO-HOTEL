<?php
/**
 * ════════════════════════════════════════════════════════
 * API — Statut en Temps Réel d'une Commande Room Service
 * ════════════════════════════════════════════════════════
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../includes/database.php';

$ref = trim($_GET['ref'] ?? '');

if (empty($ref)) {
    echo json_encode([
        'success' => false,
        'message' => 'Référence de commande requise.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("
        SELECT id, reference, chambre_numero, client_nom, client_telephone, client_email, 
               elements_commande, total_estime, instructions, statut, created_at 
        FROM room_service_commandes 
        WHERE reference = ? 
        LIMIT 1
    ");
    $stmt->execute([$ref]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode([
            'success' => false,
            'message' => 'Commande introuvable avec la référence fournie.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $statut = $order['statut'];
    $step = 1;
    $libelle = 'Commande Reçue';
    $message = 'Votre commande a été enregistrée à la réception et transmise en cuisine.';
    $color = '#c9a84c';

    if ($statut === 'en_preparation') {
        $step = 2;
        $libelle = 'En Cuisine / Préparation';
        $message = 'Le chef et son équipe préparent actuellement votre plateau avec soin.';
        $color = '#e67e22';
    } elseif ($statut === 'livree') {
        $step = 3;
        $libelle = 'Livrée en Chambre';
        $message = 'Votre commande a été livrée avec succès à votre chambre. Bon appétit !';
        $color = '#28a745';
    } elseif ($statut === 'annulee') {
        $step = 0;
        $libelle = 'Commande Annulée';
        $message = 'Cette commande a été annulée. Contactez la conciergerie pour toute assistance.';
        $color = '#dc3545';
    }

    $items = json_decode($order['elements_commande'], true) ?: [];

    echo json_encode([
        'success'         => true,
        'reference'       => $order['reference'],
        'chambre_numero'  => $order['chambre_numero'],
        'client_nom'      => $order['client_nom'],
        'total_estime'    => floatval($order['total_estime']),
        'total_formate'   => number_format($order['total_estime'], 0, ',', ' ') . ' FCFA',
        'statut'          => $statut,
        'statut_step'     => $step,
        'statut_libelle'  => $libelle,
        'statut_message'  => $message,
        'statut_color'    => $color,
        'items'           => $items,
        'instructions'    => $order['instructions'] ?? '',
        'created_at_fmt'  => date('d/m/Y à H:i', strtotime($order['created_at']))
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
