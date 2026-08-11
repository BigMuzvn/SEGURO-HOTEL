<?php
/**
 * ════════════════════════════════════════════════════════
 * CLASS RESERVATION — Cœur du système de réservation
 * ════════════════════════════════════════════════════════
 */

require_once 'database.php';

class Reservation {
    private $conn;
    private $table_name = "reservations";
    
    public $id;
    public $reference;
    public $user_id;
    public $chambre_id;
    public $date_arrivee;
    public $date_depart;
    public $nb_adultes;
    public $nb_enfants;
    public $prix_nuit;
    public $prix_options;
    public $code_promo_id;
    public $montant_reduction;
    public $prix_total;
    public $statut;
    public $demandes_speciales;
    public $note_admin;
    public $created_at;
    public $updated_at;
    public $valide_par;
    public $valide_at;
    public $checkin_at;
    public $checkout_at;
    public $statut_paiement;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Crée une nouvelle réservation (statut "en_cours" par défaut)
     */
    public function create($user_id, $chambre_id, $date_arrivee, $date_depart, $nb_adultes = 1, $nb_enfants = 0, $demandes_speciales = null, $selected_options = [], $code_promo_str = null) {
        try {
            // ── Validation des dates ──
            if (strtotime($date_depart) <= strtotime($date_arrivee)) {
                throw new Exception("La date de départ doit être postérieure à la date d'arrivée.");
            }
            if ($date_arrivee < date('Y-m-d')) {
                throw new Exception("La date d'arrivée ne peut pas être dans le passé.");
            }

            // ── Anti double-réservation ──
            $conflict = $this->conn->prepare("
                SELECT COUNT(*) FROM " . $this->table_name . "
                WHERE chambre_id = ?
                AND statut NOT IN ('annulee')
                AND date_arrivee < ? AND date_depart > ?
            ");
            $conflict->execute([$chambre_id, $date_depart, $date_arrivee]);
            if ($conflict->fetchColumn() > 0) {
                throw new Exception("Cette chambre est déjà réservée pour les dates sélectionnées.");
            }

            // Récupérer les infos de la chambre pour le prix
            $chambre_query = "SELECT prix_nuit FROM chambres WHERE id = ? AND disponible = 1 LIMIT 1";
            $stmt = $this->conn->prepare($chambre_query);
            $stmt->bindParam(1, $chambre_id);
            $stmt->execute();
            $chambre = $stmt->fetch();
            
            if (!$chambre) {
                throw new Exception("Chambre introuvable ou indisponible.");
            }
            
            // Calculer le nombre de nuits
            $nb_nuits = $this->calculerNuits($date_arrivee, $date_depart);
            $prix_nuit = $chambre['prix_nuit'];
            $prix_hebergement = $nb_nuits * $prix_nuit;

            // ── Calcul des options sélectionnées ──
            $prix_options = 0.0;
            $options_details = [];
            if (!empty($selected_options)) {
                $optionIds = array_keys($selected_options);
                $placeholders = implode(',', array_fill(0, count($optionIds), '?'));
                $optStmt = $this->conn->prepare("SELECT * FROM options WHERE id IN ({$placeholders}) AND actif = 1");
                $optStmt->execute(array_values($optionIds));
                $dbOpts = $optStmt->fetchAll(PDO::FETCH_ASSOC);

                $dbOptsIndexed = [];
                foreach ($dbOpts as $o) {
                    $dbOptsIndexed[$o['id']] = $o;
                }

                foreach ($selected_options as $optId => $qty) {
                    if (isset($dbOptsIndexed[$optId])) {
                        $opt = $dbOptsIndexed[$optId];
                        $unitPrice = (float) $opt['prix'];
                        $quantity = max(1, (int) $qty);
                        if ($opt['unite'] === 'par nuit') {
                            $optTotal = $unitPrice * $nb_nuits * $quantity;
                        } else {
                            $optTotal = $unitPrice * $quantity;
                        }
                        $prix_options += $optTotal;
                        $options_details[] = [
                            'option_id' => $optId,
                            'quantite' => $quantity,
                            'prix_unitaire' => $unitPrice
                        ];
                    }
                }
            }

            $sous_total = $prix_hebergement + $prix_options;
            $code_promo_id = null;
            $montant_reduction = 0.0;
            $prix_total = $sous_total;

            // ── Application du Code Promo ──
            if (!empty($code_promo_str)) {
                require_once __DIR__ . '/CodePromo.php';
                $cpModel = new CodePromo($this->conn);
                $resPromo = $cpModel->validateAndCalculate($code_promo_str, $sous_total);
                if ($resPromo['valid']) {
                    $code_promo_id = $resPromo['promo']['id'];
                    $montant_reduction = (float)$resPromo['reduction'];
                    $prix_total = (float)$resPromo['nouveau_total'];
                    $cpModel->incrementUsage($code_promo_id);
                }
            }
            
            // Générer un UUID et une référence unique
            $this->id = $this->generateUUID();
            $this->reference = $this->generateReference();
            
            $query = "INSERT INTO " . $this->table_name . "
                    (id, reference, user_id, chambre_id, date_arrivee, date_depart, nb_adultes, nb_enfants, prix_nuit, prix_options, code_promo_id, montant_reduction, prix_total, statut, demandes_speciales)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_cours', ?)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->id);
            $stmt->bindParam(2, $this->reference);
            $stmt->bindParam(3, $user_id);
            $stmt->bindParam(4, $chambre_id);
            $stmt->bindParam(5, $date_arrivee);
            $stmt->bindParam(6, $date_depart);
            $stmt->bindParam(7, $nb_adultes);
            $stmt->bindParam(8, $nb_enfants);
            $stmt->bindParam(9, $prix_nuit);
            $stmt->bindParam(10, $prix_options);
            $stmt->bindParam(11, $code_promo_id);
            $stmt->bindParam(12, $montant_reduction);
            $stmt->bindParam(13, $prix_total);
            $stmt->bindParam(14, $demandes_speciales);
            
            if ($stmt->execute()) {
                $this->user_id = $user_id;
                $this->chambre_id = $chambre_id;
                $this->date_arrivee = $date_arrivee;
                $this->date_depart = $date_depart;
                $this->nb_adultes = $nb_adultes;
                $this->nb_enfants = $nb_enfants;
                $this->prix_nuit = $prix_nuit;
                $this->prix_options = $prix_options;
                $this->code_promo_id = $code_promo_id;
                $this->montant_reduction = $montant_reduction;
                $this->prix_total = $prix_total;
                $this->statut = 'en_cours';
                $this->demandes_speciales = $demandes_speciales;

                // ── Enregistrement des options liées ──
                if (!empty($options_details)) {
                    $insertOptQuery = "INSERT INTO reservation_options (id, reservation_id, option_id, quantite, prix_unitaire) VALUES (UUID(), ?, ?, ?, ?)";
                    $optInsertStmt = $this->conn->prepare($insertOptQuery);
                    foreach ($options_details as $od) {
                        $optInsertStmt->execute([
                            $this->id,
                            $od['option_id'],
                            $od['quantite'],
                            $od['prix_unitaire']
                        ]);
                    }
                }
                
                // Logger la création
                $this->logAction('RESERVATION_CREEE', $user_id);
                
                return $this;
            }
        } catch(PDOException $exception) {
            error_log("Reservation::create PDO error: " . $exception->getMessage());
            $this->derniere_erreur = "Une erreur serveur est survenue lors de l'enregistrement de votre réservation. Veuillez réessayer.";
        } catch(Exception $exception) {
            error_log("Reservation::create error: " . $exception->getMessage());
            $this->derniere_erreur = $exception->getMessage();
        }
        return false;
    }

    /**
     * Retourne le dernier message d'erreur de create()
     */
    public $derniere_erreur = null;
    
    /**
     * Récupère les options associées à une réservation
     */
    public function getOptions($reservation_id = null) {
        $resId = $reservation_id ?: $this->id;
        if (!$resId) return [];
        $query = "SELECT ro.*, o.nom, o.description, o.unite 
                  FROM reservation_options ro
                  JOIN options o ON ro.option_id = o.id
                  WHERE ro.reservation_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$resId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Modifie une réservation (client uniquement)
     */
    public function modify($date_arrivee = null, $date_depart = null, $nb_adultes = null, $nb_enfants = null, $demandes_speciales = null, $chambre_id = null) {
        try {
            // Vérifier que la réservation peut être modifiée (en_cours, validee, ou deja modifiee)
            if (!in_array($this->statut, ['en_cours', 'validee', 'modifiee'])) {
                throw new Exception("Cette réservation ne peut plus être modifiée (statut actuel : " . $this->statut . ").");
            }
            
            $old_data = $this->getReservationData();
            
            $updates = [];
            $params = [];
            
            if ($date_arrivee) {
                $updates[] = "date_arrivee = ?";
                $params[] = $date_arrivee;
                $this->date_arrivee = $date_arrivee;
            }
            
            if ($date_depart) {
                $updates[] = "date_depart = ?";
                $params[] = $date_depart;
                $this->date_depart = $date_depart;
            }
            
            if ($nb_adultes !== null) {
                $updates[] = "nb_adultes = ?";
                $params[] = $nb_adultes;
                $this->nb_adultes = $nb_adultes;
            }
            
            if ($nb_enfants !== null) {
                $updates[] = "nb_enfants = ?";
                $params[] = $nb_enfants;
                $this->nb_enfants = $nb_enfants;
            }
            
            if ($demandes_speciales !== null) {
                $updates[] = "demandes_speciales = ?";
                $params[] = $demandes_speciales;
                $this->demandes_speciales = $demandes_speciales;
            }
            
            // Changement de chambre
            if ($chambre_id) {
                $updates[] = "chambre_id = ?";
                $params[] = $chambre_id;
                $this->chambre_id = $chambre_id;
                
                // Récupérer le nouveau prix de la chambre
                $chambre_query = "SELECT prix_nuit FROM chambres WHERE id = ? LIMIT 1";
                $stmt = $this->conn->prepare($chambre_query);
                $stmt->bindParam(1, $chambre_id);
                $stmt->execute();
                $chambre = $stmt->fetch();
                
                if ($chambre) {
                    $this->prix_nuit = $chambre['prix_nuit'];
                    $updates[] = "prix_nuit = ?";
                    $params[] = $this->prix_nuit;
                }
            }
            
            // Recalculer le prix si les dates ou la chambre changent
            if ($date_arrivee || $date_depart || $chambre_id) {
                $nb_nuits = $this->calculerNuits($this->date_arrivee, $this->date_depart);
                $new_total = $nb_nuits * $this->prix_nuit;
                $updates[] = "prix_total = ?";
                $params[] = $new_total;
                $this->prix_total = $new_total;
            }
            
            if (count($updates) > 0) {
                $updates[] = "statut = 'modifiee'";
                $params[] = $this->id;
                
                $query = "UPDATE " . $this->table_name . " SET " . implode(', ', $updates) . " WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                
                for ($i = 0; $i < count($params); $i++) {
                    $stmt->bindParam($i + 1, $params[$i]);
                }
                
                if ($stmt->execute()) {
                    $this->statut = 'modifiee';
                    
                    // Logger la modification
                    $new_data = $this->getReservationData();
                    $this->logAction('RESERVATION_MODIFIEE', $this->user_id, json_encode($old_data), json_encode($new_data));
                    
                    return true;
                }
            }
        } catch(PDOException $exception) {
            error_log("Reservation::modify error: " . $exception->getMessage());
        }
        return false;
    }
    
    /**
     * Annule une réservation
     */
    public function cancel($raison = null) {
        try {
            if ($this->statut === 'terminee') {
                throw new Exception("Impossible d'annuler une réservation terminée");
            }
            
            $old_statut = $this->statut;
            
            $query = "UPDATE " . $this->table_name . " SET statut = 'annulee', updated_at = NOW() WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->id);
            
            if ($stmt->execute()) {
                $this->statut = 'annulee';
                
                // Logger l'annulation
                $this->logAction('RESERVATION_ANNULEE', $this->user_id, $old_statut, 'annulee');
                
                return true;
            }
        } catch(PDOException $exception) {
            error_log("Reservation::cancel error: " . $exception->getMessage());
        }
        return false;
    }
    
    /**
     * Valide une réservation (admin uniquement)
     */
    public function validate($admin_id) {
        try {
            if ($this->statut !== 'en_cours' && $this->statut !== 'modifiee') {
                throw new Exception("Cette réservation ne peut pas être validée");
            }
            
            $query = "UPDATE " . $this->table_name . " 
                    SET statut = 'validee', valide_par = ?, valide_at = NOW(), updated_at = NOW() 
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $admin_id);
            $stmt->bindParam(2, $this->id);
            
            if ($stmt->execute()) {
                $this->statut = 'validee';
                $this->valide_par = $admin_id;
                $this->valide_at = date('Y-m-d H:i:s');
                
                // Logger la validation
                $this->logAction('RESERVATION_VALIDEE', $admin_id, 'en_cours', 'validee');
                
                return true;
            }
        } catch(PDOException $exception) {
            error_log("Reservation::validate error: " . $exception->getMessage());
        }
        return false;
    }

    /**
     * Effectue le Check-in (Client arrivé à l'hôtel)
     */
    public function checkIn($admin_id = null) {
        try {
            $adminVal = ($admin_id && strlen($admin_id) === 36) ? $admin_id : null;
            $query = "UPDATE " . $this->table_name . " 
                      SET statut = 'en_sejour', checkin_at = NOW(), valide_par = ?, updated_at = NOW() 
                      WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $adminVal);
            $stmt->bindParam(2, $this->id);
            if ($stmt->execute()) {
                $this->statut = 'en_sejour';
                $this->checkin_at = date('Y-m-d H:i:s');
                $this->logAction('CHECK_IN_EFFECTUE', $adminVal, 'validee', 'en_sejour');
                return true;
            }
        } catch (PDOException $e) {
            error_log("Reservation::checkIn error: " . $e->getMessage());
        }
        return false;
    }

    /**
     * Effectue le Check-out (Client quitte l'hôtel, libère la chambre et déclenche l'avis)
     */
    public function checkOut($admin_id = null) {
        try {
            $adminVal = ($admin_id && strlen($admin_id) === 36) ? $admin_id : null;
            $query = "UPDATE " . $this->table_name . " 
                      SET statut = 'terminee', checkout_at = NOW(), updated_at = NOW() 
                      WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->id);
            if ($stmt->execute()) {
                $this->statut = 'terminee';
                $this->checkout_at = date('Y-m-d H:i:s');

                // Basculer automatiquement la chambre libérée en statut 'a_nettoyer'
                if (!empty($this->chambre_id)) {
                    $stmtMenage = $this->conn->prepare("UPDATE chambres SET statut_menage = 'a_nettoyer' WHERE id = ?");
                    $stmtMenage->execute([$this->chambre_id]);
                }

                $this->logAction('CHECK_OUT_EFFECTUE', $adminVal, 'en_sejour', 'terminee');
                return true;
            }
        } catch (PDOException $e) {
            error_log("Reservation::checkOut error: " . $e->getMessage());
        }
        return false;
    }
    
    /**
     * Récupère une réservation par son ID
     */
    public function getById($id) {
        $query = "SELECT r.*, c.nom as chambre_nom, c.type as chambre_type,
                 u.nom as client_nom, u.prenom as client_prenom, u.email as client_email
                 FROM " . $this->table_name . " r
                 JOIN chambres c ON r.chambre_id = c.id
                 JOIN users u ON r.user_id = u.id
                 WHERE r.id = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        
        if ($row = $stmt->fetch()) {
            $this->loadFromRow($row);
            return $this;
        }
        return false;
    }
    
    /**
     * Récupère une réservation par sa référence
     */
    public function getByReference($reference) {
        $query = "SELECT r.*, c.nom as chambre_nom, c.type as chambre_type,
                 u.nom as client_nom, u.prenom as client_prenom, u.email as client_email
                 FROM " . $this->table_name . " r
                 JOIN chambres c ON r.chambre_id = c.id
                 JOIN users u ON r.user_id = u.id
                 WHERE r.reference = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $reference);
        $stmt->execute();
        
        if ($row = $stmt->fetch()) {
            $this->loadFromRow($row);
            return $this;
        }
        return false;
    }
    
    /**
     * Récupère toutes les réservations (admin)
     */
    public function getAll($statut = null, $limit = 50, $offset = 0) {
        $query = "SELECT r.*, c.nom as chambre_nom, c.type as chambre_type,
                 u.nom as user_nom, u.prenom as user_prenom, u.email as user_email
                 FROM " . $this->table_name . " r
                 JOIN chambres c ON r.chambre_id = c.id
                 JOIN users u ON r.user_id = u.id";
        
        $params = [];
        
        if ($statut) {
            if (is_array($statut)) {
                $placeholders = implode(',', array_fill(0, count($statut), '?'));
                $query .= " WHERE r.statut IN ({$placeholders})";
                $params = array_values($statut);
            } else {
                $query .= " WHERE r.statut = ?";
                $params[] = $statut;
            }
        }
        
        // Injecter directement les entiers pour LIMIT et OFFSET
        $limit = (int) $limit;
        $offset = (int) $offset;
        $query .= " ORDER BY r.created_at DESC LIMIT {$limit} OFFSET {$offset}";
        
        $stmt = $this->conn->prepare($query);
        
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindParam($i + 1, $params[$i]);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Compte le nombre de réservations par statut
     * @param string|array|null $statut Si spécifié, retourne le count pour ce(s) statut(s)
     */
    public function countByStatut($statut = null) {
        if ($statut !== null) {
            if (is_array($statut)) {
                $placeholders = implode(',', array_fill(0, count($statut), '?'));
                $query = "SELECT COUNT(*) as nb_reservations FROM " . $this->table_name . " WHERE statut IN ({$placeholders})";
                $stmt = $this->conn->prepare($query);
                $stmt->execute(array_values($statut));
            } else {
                $query = "SELECT COUNT(*) as nb_reservations FROM " . $this->table_name . " WHERE statut = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$statut]);
            }
            $result = $stmt->fetch();
            return (int) ($result['nb_reservations'] ?? 0);
        }
        
        $query = "SELECT statut, COUNT(*) as nb_reservations, SUM(prix_total) as ca_total
                 FROM " . $this->table_name . "
                 GROUP BY statut
                 ORDER BY nb_reservations DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Génère une référence de réservation unique
     */
    private function generateReference() {
        do {
            $reference = 'SEGURO-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $query = "SELECT id FROM " . $this->table_name . " WHERE reference = ? LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $reference);
            $stmt->execute();
        } while ($stmt->rowCount() > 0);
        
        return $reference;
    }

    /**
     * Génère un UUID v4
     */
    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
        );
    }
    
    /**
     * Calcule le nombre de nuits entre deux dates
     */
    private function calculerNuits($date_arrivee, $date_depart) {
        $arrivee = new DateTime($date_arrivee);
        $depart = new DateTime($date_depart);
        return $depart->diff($arrivee)->days;
    }
    
    /**
     * Charge les données depuis une ligne de résultat
     */
    private function loadFromRow($row) {
        $this->id = $row['id'];
        $this->reference = $row['reference'];
        $this->user_id = $row['user_id'];
        $this->chambre_id = $row['chambre_id'];
        $this->date_arrivee = $row['date_arrivee'];
        $this->date_depart = $row['date_depart'];
        $this->nb_adultes = $row['nb_adultes'];
        $this->nb_enfants = $row['nb_enfants'];
        $this->prix_nuit = $row['prix_nuit'];
        $this->prix_options = $row['prix_options'];
        $this->prix_total = $row['prix_total'];
        $this->statut = $row['statut'];
        $this->demandes_speciales = $row['demandes_speciales'];
        $this->note_admin = $row['note_admin'];
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'];
        $this->valide_par = $row['valide_par'];
        $this->valide_at = $row['valide_at'];
        $this->checkin_at = $row['checkin_at'] ?? null;
        $this->checkout_at = $row['checkout_at'] ?? null;
    }
    
    /**
     * Retourne les données de la réservation pour le logging
     */
    private function getReservationData() {
        return [
            'reference' => $this->reference,
            'chambre_id' => $this->chambre_id,
            'date_arrivee' => $this->date_arrivee,
            'date_depart' => $this->date_depart,
            'nb_adultes' => $this->nb_adultes,
            'nb_enfants' => $this->nb_enfants,
            'prix_total' => $this->prix_total,
            'statut' => $this->statut
        ];
    }
    
    /**
     * Enregistre une action dans les logs
     */
    private function logAction($action, $user_id, $avant = null, $apres = null) {
        try {
            $query = "INSERT INTO logs_actions (user_id, action, table_cible, cible_id, avant, apres, ip_address)
                     VALUES (?, ?, 'reservations', ?, ?, ?, ?)";

            $stmt = $this->conn->prepare($query);
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;

            // Permettre user_id null ou valider UUID
            if ($user_id !== null && strlen($user_id) === 36) {
                $stmt->bindValue(1, $user_id, PDO::PARAM_STR);
            } else {
                $stmt->bindValue(1, null, PDO::PARAM_NULL);
            }
            $stmt->bindParam(2, $action);
            $stmt->bindParam(3, $this->id);
            $stmt->bindParam(4, $avant);
            $stmt->bindParam(5, $apres);
            $stmt->bindParam(6, $ip);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur logAction Reservation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifie si une réservation appartient à un utilisateur
     */
    public function belongsTo($user_id) {
        return $this->user_id === $user_id;
    }
    
    /**
     * Formate le prix en FCFA
     */
    public function getPrixTotalFormate() {
        return number_format($this->prix_total, 0, ',', ' ') . ' FCFA';
    }
    
    /**
     * Retourne le libellé lisible du statut
     * @param string|null $statut
     * @return string
     */
    public function getStatutLibelle($statut = null) {
        $s = $statut ?? $this->statut ?? '';
        $statuts = [
            'en_cours' => 'En cours de validation',
            'validee' => 'Validée par l\'Hôtel',
            'modifiee' => 'Modifiée (En attente)',
            'en_sejour' => 'En séjour actif (Check-in effectué)',
            'annulee' => 'Séjour annulé',
            'terminee' => 'Séjour terminé (Check-out)'
        ];
        
        return $statuts[$s] ?? (!empty($s) ? ucfirst(str_replace('_', ' ', (string)$s)) : 'Statut inconnu');
    }
    
    /**
     * Compte le nombre total de réservations
     */
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        return (int) $result['total'];
    }
}
?>
