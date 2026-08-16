<?php
/**
 * ════════════════════════════════════════════════════════
 * EXPORT DES RÉSERVATIONS EN CSV / EXCEL — Admin Hôtel SEGURO
 * ════════════════════════════════════════════════════════
 */

session_start();

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/Reservation.php';
require_once __DIR__ . '/../includes/AdminAuth.php';

// Contrôle strict d'accès RBAC
AdminAuth::requireAccess('reservations');

$database = new Database();
$db = $database->getConnection();
$resModel = new Reservation($db);

$statut_filter = $_GET['statut'] ?? null;
$date_debut = $_GET['debut'] ?? null;
$date_fin = $_GET['fin'] ?? null;

$query = "SELECT r.*, u.nom as client_nom, u.prenom as client_prenom, u.email as client_email, 
                 u.telephone as client_tel, u.code_client, c.nom as chambre_nom, c.type as chambre_type
          FROM reservations r
          JOIN users u ON r.user_id = u.id
          JOIN chambres c ON r.chambre_id = c.id
          WHERE 1=1";

$params = [];

if ($statut_filter) {
    $query .= " AND r.statut = ?";
    $params[] = $statut_filter;
}

if ($date_debut) {
    $query .= " AND r.date_arrivee >= ?";
    $params[] = $date_debut;
}

if ($date_fin) {
    $query .= " AND r.date_depart <= ?";
    $params[] = $date_fin;
}

$query .= " ORDER BY r.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * Assainit les champs texte contre l'injection de formules CSV / Excel (CWE-1236)
 */
function sanitize_csv_cell($value): string {
    if ($value === null) return '';
    $val = (string)$value;
    $firstChar = substr($val, 0, 1);
    if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"])) {
        return "'" . $val;
    }
    return $val;
}

// Headers pour téléchargement de fichier CSV avec BOM UTF-8
$filename = "reservations_export_" . date('Y-m-d_His') . ".csv";
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Insérer le BOM UTF-8 pour Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// En-têtes du CSV
fputcsv($output, [
    'Référence',
    'Date de création',
    'Code Client',
    'Client (Nom complet)',
    'Email Client',
    'Téléphone Client',
    'Chambre',
    'Type de Chambre',
    'Date Arrivée',
    'Date Départ',
    'Nombre de Nuits',
    'Adultes',
    'Enfants',
    'Prix Nuit (FCFA)',
    'Options (FCFA)',
    'Remise Promo (FCFA)',
    'Montant Total (FCFA)',
    'Statut',
    'Demandes Spéciales',
    'Note Interne Admin'
], ';');

foreach ($reservations as $r) {
    $nbNuits = max(1, (int)((strtotime($r['date_depart']) - strtotime($r['date_arrivee'])) / 86400));
    
    fputcsv($output, [
        sanitize_csv_cell($r['reference']),
        date('d/m/Y H:i', strtotime($r['created_at'])),
        sanitize_csv_cell($r['code_client'] ?? ''),
        sanitize_csv_cell(($r['client_prenom'] ?? '') . ' ' . ($r['client_nom'] ?? '')),
        sanitize_csv_cell($r['client_email'] ?? ''),
        sanitize_csv_cell($r['client_tel'] ?? ''),
        sanitize_csv_cell($r['chambre_nom'] ?? ''),
        sanitize_csv_cell(ucfirst($r['chambre_type'] ?? '')),
        date('d/m/Y', strtotime($r['date_arrivee'])),
        date('d/m/Y', strtotime($r['date_depart'])),
        $nbNuits,
        $r['nb_adultes'],
        $r['nb_enfants'],
        number_format($r['prix_nuit'], 0, ',', ''),
        number_format($r['prix_options'] ?? 0, 0, ',', ''),
        number_format($r['montant_reduction'] ?? 0, 0, ',', ''),
        number_format($r['prix_total'], 0, ',', ''),
        sanitize_csv_cell($resModel->getStatutLibelle($r['statut'])),
        sanitize_csv_cell($r['demandes_speciales'] ?? ''),
        sanitize_csv_cell($r['note_admin'] ?? '')
    ], ';');
}

fclose($output);
exit;

