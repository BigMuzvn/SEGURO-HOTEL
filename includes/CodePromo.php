<?php
/**
 * ════════════════════════════════════════════════════════
 * CLASSE CODEPROMO — Gestion des codes de réduction
 * ════════════════════════════════════════════════════════
 */

class CodePromo {
    private $conn;
    private $table_name = "codes_promo";

    public $id;
    public $code;
    public $type_reduction;
    public $valeur;
    public $date_expiration;
    public $utilisations_max;
    public $utilisations_actuel;
    public $actif;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Valide et calcule la réduction pour un code promo donné et un sous-total
     */
    public function validateAndCalculate(string $code, float $sousTotal): array {
        $code = strtoupper(trim($code));
        if (empty($code)) {
            return ['valid' => false, 'message' => 'Veuillez saisir un code promotionnel.'];
        }

        $query = "SELECT * FROM " . $this->table_name . " WHERE UPPER(code) = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$code]);
        $promo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$promo) {
            return ['valid' => false, 'message' => "Le code promo \"{$code}\" n'existe pas."];
        }

        if ((int)$promo['actif'] !== 1) {
            return ['valid' => false, 'message' => "Ce code promo a été désactivé."];
        }

        if (!empty($promo['date_expiration']) && strtotime($promo['date_expiration']) < strtotime(date('Y-m-d'))) {
            return ['valid' => false, 'message' => "Ce code promo a expiré le " . date('d/m/Y', strtotime($promo['date_expiration'])) . "."];
        }

        if ($promo['utilisations_max'] !== null && (int)$promo['utilisations_actuel'] >= (int)$promo['utilisations_max']) {
            return ['valid' => false, 'message' => "Le quota d'utilisation maximal de ce code promo a été atteint."];
        }

        // Calcul de la remise
        $remise = 0.0;
        $valeur = (float) $promo['valeur'];

        if ($promo['type_reduction'] === 'pourcentage') {
            $remise = ($sousTotal * $valeur) / 100.0;
        } else {
            $remise = min($valeur, $sousTotal);
        }

        $remise = round($remise, 2);
        $nouveauTotal = max(0.0, $sousTotal - $remise);

        return [
            'valid' => true,
            'promo' => $promo,
            'reduction' => $remise,
            'nouveau_total' => $nouveauTotal,
            'message' => "Code promo \"{$promo['code']}\" appliqué avec succès !"
        ];
    }

    /**
     * Incrémente le compteur d'utilisation
     */
    public function incrementUsage($id): bool {
        $query = "UPDATE " . $this->table_name . " SET utilisations_actuel = utilisations_actuel + 1 WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    /**
     * Récupère tous les codes promo (pour l'admin)
     */
    public function getAll(): array {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crée un nouveau code promo
     */
    public function create(array $data): bool {
        $id = $this->generateUUID();
        $code = strtoupper(trim($data['code'] ?? ''));
        $type = $data['type_reduction'] ?? 'pourcentage';
        $valeur = (float)($data['valeur'] ?? 0);
        $expiration = !empty($data['date_expiration']) ? $data['date_expiration'] : null;
        $max = !empty($data['utilisations_max']) ? (int)$data['utilisations_max'] : null;
        $actif = isset($data['actif']) ? (int)$data['actif'] : 1;

        $query = "INSERT INTO " . $this->table_name . " 
                 (id, code, type_reduction, valeur, date_expiration, utilisations_max, actif)
                 VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id, $code, $type, $valeur, $expiration, $max, $actif]);
    }

    /**
     * Active ou désactive un code promo
     */
    public function toggleActif($id): bool {
        $query = "UPDATE " . $this->table_name . " SET actif = 1 - actif WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    /**
     * Supprime un code promo
     */
    public function delete($id): bool {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
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
