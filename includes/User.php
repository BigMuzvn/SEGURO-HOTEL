<?php
/**
 * ════════════════════════════════════════════════════════
 * CLASS USER — Gestion des clients et admins
 * ════════════════════════════════════════════════════════
 */

require_once 'database.php';

class User {
    private $conn;
    private $table_name = "users";
    
    public $id;
    public $nom;
    public $prenom;
    public $email;
    public $code_client;
    public $telephone;
    public $pays;
    public $ville;
    public $adresse;
    public $mot_de_passe;
    public $role;
    public $created_at;
    public $last_login;
    public $email_verified = 0;
    public $otp_code;
    public $otp_expires_at;
    public $otp_attempts = 0;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public $is_new_account = false;
    public $derniere_erreur = null;

    /**
     * Crée automatiquement un compte client lors de la première réservation
     */
    public function createAutoAccount($nom, $prenom, $email, $telephone = null, $pays = null) {
        $email = strtolower(trim($email));
        $this->derniere_erreur = null;
        $this->is_new_account = false;

        try {
            // Vérifier si l'email existe déjà
            if ($this->emailExists($email)) {
                $existingUser = $this->getByEmail($email);
                if ($existingUser) {
                    // Refuser impérativement les comptes administrateurs
                    if (in_array($existingUser->role, ['admin', 'super_admin'])) {
                        $this->derniere_erreur = "Cette adresse email est déjà utilisée. Veuillez utiliser une autre adresse email pour poursuivre votre réservation.";
                        return false;
                    }
                    // Client existant
                    $existingUser->is_new_account = false;
                    return $existingUser;
                }
            }
            
            // Générer un UUID et un code client unique
            $this->id = $this->generateUUID();
            $this->code_client = $this->generateCodeClient();
            
            $query = "INSERT INTO " . $this->table_name . "
                    (id, nom, prenom, email, code_client, telephone, pays, role)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'client')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->id);
            $stmt->bindParam(2, $nom);
            $stmt->bindParam(3, $prenom);
            $stmt->bindParam(4, $email);
            $stmt->bindParam(5, $this->code_client);
            $stmt->bindParam(6, $telephone);
            $stmt->bindParam(7, $pays);
            
            if ($stmt->execute()) {
                $this->nom = $nom;
                $this->prenom = $prenom;
                $this->email = $email;
                $this->role = 'client';
                $this->is_new_account = true;
                
                // Logger la création automatique (sans bloquer si ça échoue)
                try {
                    $this->logAction('COMPTE_AUTO_CREE', 'users', $this->id);
                } catch (Exception $logError) {
                    error_log("Erreur log création compte: " . $logError->getMessage());
                }
                
                return $this;
            } else {
                error_log("Erreur insertion user: " . implode(", ", $stmt->errorInfo()));
            }
        } catch(PDOException $exception) {
            error_log("User::createAutoAccount PDO error: " . $exception->getMessage());
            $this->derniere_erreur = "Erreur serveur lors de la création de votre compte.";
        } catch(Exception $exception) {
            error_log("User::createAutoAccount error: " . $exception->getMessage());
            $this->derniere_erreur = $exception->getMessage();
        }
        return false;
    }
    
    /**
     * Vérifie si un email existe déjà
     */
    public function emailExists($email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Récupère un utilisateur par email
     */
    public function getByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        
        if ($row = $stmt->fetch()) {
            $this->id = $row['id'];
            $this->nom = $row['nom'];
            $this->prenom = $row['prenom'];
            $this->email = $row['email'];
            $this->code_client = $row['code_client'];
            $this->telephone = $row['telephone'];
            $this->pays = $row['pays'];
            $this->ville = $row['ville'] ?? null;
            $this->adresse = $row['adresse'] ?? null;
            $this->mot_de_passe = $row['mot_de_passe'] ?? null;
            $this->role = $row['role'];
            $this->created_at = $row['created_at'];
            $this->last_login = $row['last_login'];
            $this->email_verified = intval($row['email_verified'] ?? 0);
            $this->otp_code = $row['otp_code'] ?? null;
            $this->otp_expires_at = $row['otp_expires_at'] ?? null;
            
            return $this;
        }
        return false;
    }
    
    /**
     * Récupère un utilisateur par ID
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        
        if ($row = $stmt->fetch()) {
            $this->id = $row['id'];
            $this->nom = $row['nom'];
            $this->prenom = $row['prenom'];
            $this->email = $row['email'];
            $this->code_client = $row['code_client'];
            $this->telephone = $row['telephone'];
            $this->pays = $row['pays'];
            $this->ville = $row['ville'] ?? null;
            $this->adresse = $row['adresse'] ?? null;
            $this->mot_de_passe = $row['mot_de_passe'] ?? null;
            $this->role = $row['role'];
            $this->created_at = $row['created_at'];
            $this->last_login = $row['last_login'];
            $this->email_verified = intval($row['email_verified'] ?? 0);
            $this->otp_code = $row['otp_code'] ?? null;
            $this->otp_expires_at = $row['otp_expires_at'] ?? null;
            
            return $this;
        }
        return false;
    }
    
    /**
     * Authentification par email et code client
     */
    public function login($email, $codeClient) {
        $email = strtolower(trim($email));
        $codeClient = trim($codeClient);

        $query = "SELECT * FROM " . $this->table_name . " WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$email]);
        
        if ($row = $stmt->fetch()) {
            if (!empty($row['code_client']) && strcasecmp($row['code_client'], $codeClient) === 0) {
                $this->id = $row['id'];
                $this->nom = $row['nom'];
                $this->prenom = $row['prenom'];
                $this->email = $row['email'];
                $this->code_client = $row['code_client'];
                $this->telephone = $row['telephone'];
                $this->pays = $row['pays'];
                $this->ville = $row['ville'] ?? null;
                $this->adresse = $row['adresse'] ?? null;
                $this->role = $row['role'];
                $this->created_at = $row['created_at'];
                $this->email_verified = intval($row['email_verified'] ?? 0);
                $this->otp_code = $row['otp_code'] ?? null;
                $this->otp_expires_at = $row['otp_expires_at'] ?? null;
                
                // Mettre à jour last_login
                $this->updateLastLogin();
                
                // Logger la connexion
                $this->logAction('CONNEXION', 'users', $this->id);
                
                return $this;
            }
        }
        return false;
    }
    
    /**
     * Met à jour les informations du profil client
     */
    public function updateProfile($id, $nom, $prenom, $telephone, $pays, $ville = null, $adresse = null): bool {
        try {
            $query = "UPDATE " . $this->table_name . " 
                      SET nom = ?, prenom = ?, telephone = ?, pays = ?, ville = ?, adresse = ?
                      WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            if ($stmt->execute([trim($nom), trim($prenom), trim($telephone), trim($pays), trim($ville ?: ''), trim($adresse ?: ''), $id])) {
                $this->nom = trim($nom);
                $this->prenom = trim($prenom);
                $this->telephone = trim($telephone);
                $this->pays = trim($pays);
                $this->ville = trim($ville ?: '');
                $this->adresse = trim($adresse ?: '');
                $this->logAction('PROFIL_MODIFIE', 'users', $id);
                return true;
            }
        } catch (PDOException $e) {
            error_log("User::updateProfile error: " . $e->getMessage());
        }
        return false;
    }

    /**
     * Modifie l'adresse email du client et déclenche l'envoi d'un nouveau code OTP
     */
    public function changeEmail($id, $newEmail): array {
        $newEmail = strtolower(trim($newEmail));
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Format d\'adresse email invalide.'];
        }

        $stmt = $this->conn->prepare("SELECT id FROM " . $this->table_name . " WHERE email = ? AND id != ? LIMIT 1");
        $stmt->execute([$newEmail, $id]);
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Cette adresse email est déjà associée à un autre compte.'];
        }

        $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $update = $this->conn->prepare("UPDATE " . $this->table_name . " SET email = ?, email_verified = 0, otp_code = ?, otp_expires_at = ? WHERE id = ?");
        if ($update->execute([$newEmail, $otp, $expires, $id])) {
            $this->email = $newEmail;
            $this->email_verified = 0;
            $this->otp_code = $otp;
            $this->otp_expires_at = $expires;

            try {
                require_once __DIR__ . '/Mail.php';
                Mail::sendOTPVerification($newEmail, ($this->prenom ?: 'Client'), $otp);
            } catch (Exception $e) {
                error_log("changeEmail mail error: " . $e->getMessage());
            }

            $this->logAction('EMAIL_MODIFIE', 'users', $id);
            return ['success' => true, 'message' => 'Votre adresse email a été mise à jour vers ' . htmlspecialchars($newEmail) . '. Un code de vérification OTP vous a été envoyé par email.'];
        }
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour de l\'email.'];
    }

    /**
     * Génère une demande de nouveau code client et l'envoie par email
     */
    public function requestNewCodeClient($userId): array {
        $user = $this->getById($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'Utilisateur introuvable.'];
        }

        $newCode = $this->generateCodeClient();
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $_SESSION['pending_new_code_client'] = $newCode;

        try {
            require_once __DIR__ . '/Mail.php';
            $sent = Mail::sendNewCodeClientRequest($user->email, ($user->prenom . ' ' . $user->nom), $newCode);
            if ($sent) {
                return [
                    'success' => true, 
                    'message' => 'Un nouveau Code Client a été généré et envoyé à votre adresse email (' . htmlspecialchars($user->email) . '). Veuillez le saisir ci-dessous pour confirmer et activer votre nouveau code.'
                ];
            }
        } catch (Exception $e) {
            error_log("requestNewCodeClient error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Une erreur est survenue lors de l\'envoi de l\'email. Veuillez réessayer.'];
    }

    /**
     * Confirme et applique le nouveau code client saisi par le client
     */
    public function confirmNewCodeClient($userId, $codeSaisi): array {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $pendingCode = $_SESSION['pending_new_code_client'] ?? '';
        $codeSaisi = strtoupper(trim($codeSaisi));

        if (empty($pendingCode)) {
            return ['success' => false, 'message' => 'Aucune demande de renouvellement en attente. Veuillez cliquer d\'abord sur "Demander un nouveau Code Client".'];
        }

        if (strcasecmp($pendingCode, $codeSaisi) !== 0) {
            return ['success' => false, 'message' => 'Le code saisi ne correspond pas au nouveau code client envoyé par email.'];
        }

        $stmt = $this->conn->prepare("UPDATE " . $this->table_name . " SET code_client = ? WHERE id = ?");
        if ($stmt->execute([$pendingCode, $userId])) {
            $this->code_client = $pendingCode;
            $_SESSION['user_code'] = $pendingCode;
            $_SESSION['user_code_client'] = $pendingCode;
            unset($_SESSION['pending_new_code_client']);

            $this->logAction('CODE_CLIENT_RENOUVELE', 'users', $userId, null, $pendingCode);
            return ['success' => true, 'message' => 'Félicitations ! Votre nouveau Code Client (' . $pendingCode . ') est désormais actif. Conservez-le pour vos prochaines connexions.'];
        }

        return ['success' => false, 'message' => 'Erreur lors de l\'enregistrement de votre nouveau code client.'];
    }

    /**
     * Récupère le code client et l'envoie par email
     */
    public function recoverClientCode($email): array {
        $email = strtolower(trim($email));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Veuillez saisir une adresse email valide.'];
        }

        $u = $this->getByEmail($email);
        if (!$u) {
            return ['success' => true, 'message' => 'Si un compte existe avec cette adresse email, votre Code Client vient de vous être envoyé par email.'];
        }

        try {
            require_once __DIR__ . '/Mail.php';
            $nomComplet = ($u->prenom . ' ' . $u->nom);
            Mail::sendClientCodeRecovery($u->email, $nomComplet, $u->code_client);
            return ['success' => true, 'message' => 'Votre Code Client a été envoyé avec succès à l\'adresse ' . htmlspecialchars($email) . '. Consultez votre boîte de réception.'];
        } catch (Exception $e) {
            error_log("recoverClientCode error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Une erreur est survenue lors de l\'envoi de l\'email. Veuillez réessayer.'];
    }

    /**
     * Met à jour la date de dernière connexion
     */
    public function updateLastLogin() {
        $query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        return $stmt->execute();
    }
    
    /**
     * Génère un code client unique
     */
    private function generateCodeClient() {
        $year  = date('Y');
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $suffix = '';
            for ($i = 0; $i < 4; $i++) {
                $suffix .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $code = "SEG-{$year}-{$suffix}";
            $query = "SELECT id FROM " . $this->table_name . " WHERE code_client = ? LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $code);
            $stmt->execute();
        } while ($stmt->rowCount() > 0);
        
        return $code;
    }
    
    /**
     * Vérifie si l'utilisateur est admin
     */
    public function isAdmin() {
        return in_array($this->role, ['admin', 'super_admin']);
    }
    
    /**
     * Enregistre une action dans les logs
     */
    private function logAction($action, $table_cible, $cible_id, $avant = null, $apres = null) {
        try {
            $query = "INSERT INTO logs_actions (user_id, action, table_cible, cible_id, avant, apres, ip_address)
                     VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->conn->prepare($query);
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;

            // Permettre user_id null (important pour les actions système)
            if ($this->id !== null) {
                $stmt->bindValue(1, $this->id, PDO::PARAM_STR);
            } else {
                $stmt->bindValue(1, null, PDO::PARAM_NULL);
            }
            $stmt->bindParam(2, $action);
            $stmt->bindParam(3, $table_cible);
            $stmt->bindParam(4, $cible_id);
            $stmt->bindParam(5, $avant);
            $stmt->bindParam(6, $apres);
            $stmt->bindParam(7, $ip);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur logAction: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère les réservations d'un client
     */
    public function getReservations() {
        $query = "SELECT r.*, c.nom as chambre_nom, c.type as chambre_type
                 FROM reservations r
                 JOIN chambres c ON r.chambre_id = c.id
                 WHERE r.user_id = ?
                 ORDER BY r.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Récupère tous les clients (pour l'admin)
     */
    public function getAllClients() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE role = 'client' ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Récupère tous les administrateurs
     */
    public function getAllAdmins() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE role IN ('admin', 'super_admin') ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Recherche des clients
     */
    public function searchClients($search) {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE role = 'client' 
                 AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ? OR code_client LIKE ?)
                 ORDER BY created_at DESC";
        
        $searchTerm = '%' . $search . '%';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $searchTerm);
        $stmt->bindParam(2, $searchTerm);
        $stmt->bindParam(3, $searchTerm);
        $stmt->bindParam(4, $searchTerm);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Génère un code OTP à 6 chiffres et l'enregistre en BDD (expiration 15 mins, réinitialise tentatives)
     */
    public function generateOTP() {
        if (!$this->id) return false;
        
        $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        $query = "UPDATE " . $this->table_name . " SET otp_code = ?, otp_expires_at = ?, otp_attempts = 0 WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute([$otp, $expires, $this->id])) {
            $this->otp_code = $otp;
            $this->otp_expires_at = $expires;
            $this->otp_attempts = 0;
            return $otp;
        }
        return false;
    }

    /**
     * Vérifie le code OTP saisi avec protection anti-force brute (max 5 tentatives) et timing-safe comparison
     */
    public function verifyOTP($codeSaisi) {
        if (!$this->id) return ['success' => false, 'message' => 'Utilisateur non identifié.'];
        
        $codeSaisi = trim($codeSaisi);
        if (empty($codeSaisi) || empty($this->otp_code)) {
            return ['success' => false, 'message' => 'Code de sécurité manquant ou expiré.'];
        }
        
        // Recharger le compteur d'essais depuis la BDD
        $stmtCheck = $this->conn->prepare("SELECT otp_code, otp_expires_at, otp_attempts FROM " . $this->table_name . " WHERE id = ?");
        $stmtCheck->execute([$this->id]);
        $row = $stmtCheck->fetch();
        
        if (!$row || empty($row['otp_code'])) {
            return ['success' => false, 'message' => 'Aucun code OTP actif. Veuillez en demander un nouveau.'];
        }

        $attempts = intval($row['otp_attempts'] ?? 0);
        if ($attempts >= 5) {
            // Invalider l'OTP après 5 tentatives infructueuses
            $this->conn->prepare("UPDATE " . $this->table_name . " SET otp_code = NULL, otp_expires_at = NULL, otp_attempts = 0 WHERE id = ?")->execute([$this->id]);
            return ['success' => false, 'message' => 'Trop de tentatives erronées (5/5). Ce code de sécurité a été annulé par précaution. Veuillez demander un nouveau code.'];
        }

        // Vérifier l'expiration temporelle
        if (strtotime($row['otp_expires_at']) < time()) {
            return ['success' => false, 'message' => 'Ce code de sécurité a expiré (validité 15 minutes). Veuillez demander un nouveau code.'];
        }
        
        // Comparaison temporelle sécurisée
        if (hash_equals((string)$row['otp_code'], (string)$codeSaisi)) {
            // Marquer l'email comme vérifié et réinitialiser l'OTP
            $query = "UPDATE " . $this->table_name . " SET email_verified = 1, otp_code = NULL, otp_expires_at = NULL, otp_attempts = 0 WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            if ($stmt->execute([$this->id])) {
                $this->email_verified = 1;
                $this->otp_code = null;
                $this->otp_expires_at = null;
                $this->otp_attempts = 0;
                $this->logAction('EMAIL_VERIFIE_OTP', 'users', $this->id);
                return ['success' => true, 'message' => 'Votre adresse email a été validée avec succès !'];
            }
        } else {
            // Incrémenter le nombre d'échecs
            $newAttempts = $attempts + 1;
            if ($newAttempts >= 5) {
                $this->conn->prepare("UPDATE " . $this->table_name . " SET otp_code = NULL, otp_expires_at = NULL, otp_attempts = 0 WHERE id = ?")->execute([$this->id]);
                $this->otp_code = null;
                return ['success' => false, 'message' => 'Trop de tentatives erronées (5/5). Ce code de sécurité a été révoqué par précaution. Veuillez demander un nouveau code.'];
            }
            $this->conn->prepare("UPDATE " . $this->table_name . " SET otp_attempts = ? WHERE id = ?")->execute([$newAttempts, $this->id]);
            $remaining = 5 - $newAttempts;
            return ['success' => false, 'message' => "Code incorrect. Plus que {$remaining} tentative(s) restante(s) avant annulation du code."];
        }
        return ['success' => false, 'message' => 'Code de sécurité invalide.'];
    }

    /**
     * Calcule le statut de fidélité et les avantages VIP du client
     */
    public function getFideliteStatus(): array {
        if (!$this->id) {
            return [
                'grade' => 'bronze',
                'grade_label' => 'Membre Club Bronze',
                'remise_pourcentage' => 0,
                'badge_color' => '#cd7f32',
                'nb_sejours' => 0,
                'nb_nuits' => 0,
                'total_depense' => 0,
                'avantages' => ['Accès aux offres exclusives membres', 'Wi-Fi haut débit offert'],
                'prochain_grade' => 'Silver Privilège',
                'nuits_restantes' => 4,
                'progression' => 0
            ];
        }

        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as nb_sejours, COALESCE(SUM(DATEDIFF(date_depart, date_arrivee)), 0) as nb_nuits, COALESCE(SUM(prix_total), 0) as total_depense
            FROM reservations
            WHERE user_id = ? AND statut IN ('validee', 'en_sejour', 'terminee')
        ");
        $stmt->execute([$this->id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['nb_sejours' => 0, 'nb_nuits' => 0, 'total_depense' => 0];

        $nbSejours = (int)$stats['nb_sejours'];
        $nbNuits = (int)$stats['nb_nuits'];
        $totalDepense = (float)$stats['total_depense'];

        if ($nbSejours >= 5 || $nbNuits >= 10) {
            $grade = 'gold';
            $gradeLabel = 'Membre VIP Gold (Or)';
            $remise = 10;
            $badgeColor = '#c9a84c';
            $avantages = [
                '10% de réduction automatique sur vos séjours',
                'Surclassement prioritaire selon disponibilité',
                'Accueil avec bouteille de Champagne offerte',
                'Départ tardif garanti jusqu\'à 14h00'
            ];
            $prochainGrade = null;
            $nuitsRestantes = 0;
            $progression = 100;
        } elseif ($nbSejours >= 2 || $nbNuits >= 4) {
            $grade = 'silver';
            $gradeLabel = 'Membre Privilège Silver (Argent)';
            $remise = 5;
            $badgeColor = '#a8b2bc';
            $avantages = [
                '5% de réduction automatique sur vos séjours',
                'Cocktail signature de bienvenue offert',
                'Accès prioritaire à la conciergerie VIP'
            ];
            $prochainGrade = 'Gold VIP (Or)';
            $nuitsRestantes = max(0, 10 - $nbNuits);
            $progression = min(99, round(($nbNuits / 10) * 100));
        } else {
            $grade = 'bronze';
            $gradeLabel = 'Membre Club Bronze';
            $remise = 0;
            $badgeColor = '#cd7f32';
            $avantages = [
                'Accès aux tarifs et offres exclusives membres',
                'Enregistrement accéléré à la réception',
                'Wi-Fi fibre haut débit illimité'
            ];
            $prochainGrade = 'Silver Privilège (Argent)';
            $nuitsRestantes = max(0, 4 - $nbNuits);
            $progression = min(99, round(($nbNuits / 4) * 100));
        }

        return [
            'grade' => $grade,
            'grade_label' => $gradeLabel,
            'remise_pourcentage' => $remise,
            'badge_color' => $badgeColor,
            'nb_sejours' => $nbSejours,
            'nb_nuits' => $nbNuits,
            'total_depense' => $totalDepense,
            'avantages' => $avantages,
            'prochain_grade' => $prochainGrade,
            'nuits_restantes' => $nuitsRestantes,
            'progression' => $progression
        ];
    }

    /**
     * Récupère l'historique des commandes Room Service du client
     */
    public function getRoomServiceOrders(): array {
        if (!$this->id) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM room_service_commandes 
                WHERE user_id = ? OR client_email = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$this->id, $this->email]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("User::getRoomServiceOrders error: " . $e->getMessage());
            return [];
        }
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
}
?>
