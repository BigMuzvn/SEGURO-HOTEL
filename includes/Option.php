<?php
/**
 * ════════════════════════════════════════════════════════
 * CLASSE OPTION — Gestion des options et services de séjour
 * ════════════════════════════════════════════════════════
 */

class Option {
    private $conn;
    private $table_name = "options";

    public $id;
    public $nom;
    public $description;
    public $prix;
    public $unite;
    public $actif;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Récupère toutes les options actives
     */
    public function getAllActive() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE actif = 1 ORDER BY prix ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère une option par son ID
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère plusieurs options par leurs IDs
     */
    public function getByIds(array $ids) {
        if (empty($ids)) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "SELECT * FROM " . $this->table_name . " WHERE id IN ({$placeholders}) AND actif = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(array_values($ids));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calcule le montant total d'une liste d'options choisies en fonction du nombre de nuits
     */
    public function calculateTotalOptions(array $selectedOptions, int $nbNuits = 1): float {
        if (empty($selectedOptions)) return 0.0;

        $optionIds = array_keys($selectedOptions);
        $dbOptions = $this->getByIds($optionIds);
        $dbOptionsIndexed = [];
        foreach ($dbOptions as $opt) {
            $dbOptionsIndexed[$opt['id']] = $opt;
        }

        $total = 0.0;
        foreach ($selectedOptions as $optId => $qty) {
            if (isset($dbOptionsIndexed[$optId])) {
                $opt = $dbOptionsIndexed[$optId];
                $prixUnit = (float) $opt['prix'];
                $quantity = max(1, (int) $qty);

                if ($opt['unite'] === 'par nuit') {
                    $total += $prixUnit * $nbNuits * $quantity;
                } else {
                    $total += $prixUnit * $quantity;
                }
            }
        }
        return $total;
    }
}
?>
