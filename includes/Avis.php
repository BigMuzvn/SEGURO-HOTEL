<?php
/**
 * ════════════════════════════════════════════════════════
 * CLASSE AVIS — Gestion des avis et notes vérifiés
 * ════════════════════════════════════════════════════════
 */

class Avis {
    private $conn;
    private $table_name = "avis";

    public $id;
    public $reservation_id;
    public $user_id;
    public $chambre_id;
    public $note;
    public $titre;
    public $commentaire;
    public $statut;
    public $reponse_hotel;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Crée un nouvel avis client
     */
    public function create($reservation_id, $user_id, $chambre_id, $note, $titre, $commentaire): bool {
        $id = $this->generateUUID();
        $note = max(1, min(5, (int)$note));
        $titre = trim($titre);
        $commentaire = trim($commentaire);

        $query = "INSERT INTO " . $this->table_name . "
                  (id, reservation_id, user_id, chambre_id, note, titre, commentaire, statut)
                  VALUES (?, ?, ?, ?, ?, ?, ?, 'publie')";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id, $reservation_id, $user_id, $chambre_id, $note, $titre, $commentaire]);
    }

    /**
     * Récupère l'avis lié à une réservation
     */
    public function getByReservationId($reservation_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE reservation_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$reservation_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère tous les avis publiés pour une chambre avec informations auteur
     */
    public function getByChambreId($chambre_id): array {
        $query = "SELECT a.*, u.prenom, u.nom, u.pays, r.date_arrivee, r.date_depart
                  FROM " . $this->table_name . " a
                  JOIN users u ON a.user_id = u.id
                  JOIN reservations r ON a.reservation_id = r.id
                  WHERE a.chambre_id = ? AND a.statut = 'publie'
                  ORDER BY a.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$chambre_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calcule la note moyenne et le nombre d'avis pour une chambre
     */
    public function getStatsChambre($chambre_id): array {
        $query = "SELECT COUNT(*) as nb_avis, AVG(note) as moyenne
                  FROM " . $this->table_name . "
                  WHERE chambre_id = ? AND statut = 'publie'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$chambre_id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'nb_avis' => (int)($res['nb_avis'] ?? 0),
            'moyenne' => $res['moyenne'] !== null ? round((float)$res['moyenne'], 1) : 5.0
        ];
    }

    /**
     * Récupère tous les avis pour l'administration
     */
    public function getAllForAdmin(): array {
        $query = "SELECT a.*, u.prenom as client_prenom, u.nom as client_nom, u.email as client_email,
                         c.nom as chambre_nom, r.reference as reservation_ref
                  FROM " . $this->table_name . " a
                  JOIN users u ON a.user_id = u.id
                  JOIN chambres c ON a.chambre_id = c.id
                  JOIN reservations r ON a.reservation_id = r.id
                  ORDER BY a.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Modère un avis (statut + réponse officielle de l'hôtel)
     */
    public function moderer($id, $statut, $reponse_hotel = null): bool {
        $query = "UPDATE " . $this->table_name . " SET statut = ?, reponse_hotel = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$statut, $reponse_hotel, $id]);
    }

    /**
     * Supprime un avis
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
