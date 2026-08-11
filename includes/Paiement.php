<?php
/**
 * ════════════════════════════════════════════════════════
 * CLASSE PAIEMENT — Gestion des paiements en ligne & acomptes
 * (Mobile Money T-Money / Flooz & Carte Bancaire)
 * ════════════════════════════════════════════════════════
 */

class Paiement {
    private $conn;
    private $table_name = "paiements";

    public $id;
    public $reservation_id;
    public $user_id;
    public $montant;
    public $moyen_paiement;
    public $type_paiement;
    public $statut;
    public $reference_paiement;
    public $telephone_paiement;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Enregistre une tentative de paiement et la valide
     */
    public function traiterPaiement($reservation_id, $user_id, $montant, $moyen_paiement, $type_paiement, $telephone = null): array {
        $id = $this->generateUUID();
        $refPaiement = "PAY-" . strtoupper(substr($moyen_paiement, 0, 3)) . "-" . date('YmdHis') . "-" . rand(100, 999);

        // Dans un environnement de paiement simulé/direct, on valide la transaction
        $statutPaiement = 'valide';

        $query = "INSERT INTO " . $this->table_name . "
                  (id, reservation_id, user_id, montant, moyen_paiement, type_paiement, statut, reference_paiement, telephone_paiement)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $ok = $stmt->execute([
            $id,
            $reservation_id,
            $user_id,
            $montant,
            $moyen_paiement,
            $type_paiement,
            $statutPaiement,
            $refPaiement,
            $telephone
        ]);

        if ($ok) {
            // Mise à jour de la réservation
            $nouveauStatutPaiement = ($type_paiement === 'total_100') ? 'totalement_paye' : (($type_paiement === 'acompte_30') ? 'acompte_paye' : 'non_paye');
            
            // Si acompte ou totalité payée, la réservation passe automatiquement en validée !
            $nouveauStatutResa = ($type_paiement !== 'sur_place') ? 'validee' : 'en_cours';

            $updateResa = $this->conn->prepare("UPDATE reservations SET statut_paiement = ?, statut = ? WHERE id = ?");
            $updateResa->execute([$nouveauStatutPaiement, $nouveauStatutResa, $reservation_id]);

            return [
                'success' => true,
                'paiement_id' => $id,
                'reference' => $refPaiement,
                'montant' => $montant,
                'moyen' => $moyen_paiement,
                'type' => $type_paiement,
                'statut_resa' => $nouveauStatutResa
            ];
        }

        return ['success' => false, 'message' => 'Erreur lors de l\'enregistrement du paiement.'];
    }

    /**
     * Récupère les paiements associés à une réservation
     */
    public function getPaiementsByReservation($reservation_id): array {
        $query = "SELECT * FROM " . $this->table_name . " WHERE reservation_id = ? ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$reservation_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
?>
