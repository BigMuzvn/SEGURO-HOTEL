<?php
/**
 * ════════════════════════════════════════════════════════
 * AdminAuth.php — Gestionnaire RBAC des Rôles & Permissions
 * HospitOS — Administration Modulaire
 * ════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/database.php';

class AdminAuth {

    public static function getAvailableModules(): array {
        return [
            'dashboard'    => ['label' => 'Dashboard & Statistiques', 'icon' => 'fas fa-chart-line', 'file' => 'dashboard.php'],
            'calendrier'   => ['label' => 'Calendrier des Séjours', 'icon' => 'fas fa-calendar-alt', 'file' => 'calendrier.php'],
            'reservations' => ['label' => 'Gestion des Réservations', 'icon' => 'fas fa-book', 'file' => 'reservations.php'],
            'room_service' => ['label' => 'Commandes Room Service', 'icon' => 'fas fa-concierge-bell', 'file' => 'room-service.php'],
            'evenements'   => ['label' => 'Devis Événements & Séminaires', 'icon' => 'fas fa-glass-cheers', 'file' => 'evenements.php'],
            'clients'      => ['label' => 'Clients & Historique', 'icon' => 'fas fa-users', 'file' => 'clients.php'],
            'chambres'     => ['label' => 'Chambres & Housekeeping', 'icon' => 'fas fa-bed', 'file' => 'chambres.php'],
            'avis'         => ['label' => 'Avis Clients & Modération', 'icon' => 'fas fa-star', 'file' => 'avis.php'],
            'codes_promo'  => ['label' => 'Codes Promotionnels', 'icon' => 'fas fa-tags', 'file' => 'codes-promo.php'],
            'profil'       => ['label' => 'Équipe & Profil (Super Admin)', 'icon' => 'fas fa-user-shield', 'file' => 'profil.php', 'super_admin_only' => true],
        ];
    }

    /**
     * Vérifie si l'utilisateur actuellement connecté ou l'utilisateur spécifié a accès à un module
     */
    public static function can(string $module, ?string $userId = null): bool {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $currentUserId = $userId ?? ($_SESSION['user_id'] ?? null);

        if (!$currentUserId) {
            return false;
        }

        // Déterminer le rôle
        $role = '';
        if ($userId === null || $userId === ($_SESSION['user_id'] ?? '')) {
            $role = $_SESSION['user_role'] ?? '';
        }

        if (empty($role)) {
            try {
                $db = (new Database())->getConnection();
                $stmt = $db->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
                $stmt->execute([$currentUserId]);
                $role = (string)$stmt->fetchColumn();
            } catch (Exception $e) {
                $role = 'admin';
            }
        }

        // Le Super Admin a accès absolu à tous les modules
        if ($role === 'super_admin') {
            return true;
        }

        // Le module 'profil' (gestion d'équipe) est réservé exclusivement au Super Admin
        if ($module === 'profil') {
            return ($role === 'super_admin');
        }

        // Si c'est un administrateur standard, vérifier ses permissions stockées
        $perms = self::getUserPermissions($currentUserId);
        
        return in_array($module, $perms);
    }

    /**
     * Vérifie si l'utilisateur spécifié ou connecté est super_admin
     */
    public static function isSuperAdmin(?string $userId = null): bool {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $currentUserId = $userId ?? ($_SESSION['user_id'] ?? null);
        if (!$currentUserId) return false;

        $role = '';
        if ($userId === null || $userId === ($_SESSION['user_id'] ?? '')) {
            $role = $_SESSION['user_role'] ?? '';
        }
        if (empty($role)) {
            try {
                $db = (new Database())->getConnection();
                $stmt = $db->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
                $stmt->execute([$currentUserId]);
                $role = (string)$stmt->fetchColumn();
            } catch (Exception $e) {
                return false;
            }
        }
        return ($role === 'super_admin');
    }

    /**
     * Protège une page admin : redirige si l'accès n'est pas autorisé
     */
    public static function requireAccess(string $module): void {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin'])) {
            header('Location: ../pages/connexion-client.php');
            exit;
        }

        if (!self::can($module)) {
            // Trouver la première page autorisée pour cet admin
            $firstAllowed = self::getFirstAllowedPage($_SESSION['user_id']);
            header("Location: {$firstAllowed}?acces_refuse=1");
            exit;
        }
    }

    /**
     * Récupère la liste des modules autorisés pour un utilisateur
     */
    public static function getUserPermissions(string $userId): array {
        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT permissions, niveau FROM admins WHERE user_id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && !empty($row['permissions'])) {
                $decoded = json_decode($row['permissions'], true);
                if (is_array($decoded)) {
                    // Si le format est associatif ['module'=>true], extraire les clés valides
                    if (array_values($decoded) !== $decoded) {
                        $keys = [];
                        foreach ($decoded as $k => $v) {
                            if ($v) $keys[] = $k;
                        }
                        return $keys;
                    }
                    return $decoded;
                }
            }
        } catch (Exception $e) {
            error_log("AdminAuth::getUserPermissions error: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Enregistre les permissions d'un administrateur
     */
    public static function savePermissions(string $userId, array $permissions, string $niveau = 'admin'): bool {
        try {
            $db = (new Database())->getConnection();
            
            // Vérifier si l'enregistrement existe dans admins
            $stmt = $db->prepare("SELECT id FROM admins WHERE user_id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            $permsJson = json_encode(array_values(array_unique($permissions)));

            if ($existing) {
                $stmtUpdate = $db->prepare("UPDATE admins SET niveau = ?, permissions = ? WHERE user_id = ?");
                return $stmtUpdate->execute([$niveau, $permsJson, $userId]);
            } else {
                $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
                    mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
                    mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)
                );
                $stmtInsert = $db->prepare("INSERT INTO admins (id, user_id, niveau, permissions) VALUES (?, ?, ?, ?)");
                return $stmtInsert->execute([$uuid, $userId, $niveau, $permsJson]);
            }
        } catch (Exception $e) {
            error_log("AdminAuth::savePermissions error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retourne la première page autorisée pour un admin
     */
    public static function getFirstAllowedPage(string $userId): string {
        $modules = self::getAvailableModules();
        foreach ($modules as $modKey => $modInfo) {
            if (self::can($modKey, $userId)) {
                return $modInfo['file'];
            }
        }
        return 'dashboard.php';
    }

    /**
     * Synchronise les comptes admin existants de users vers admins s'ils n'y figurent pas
     */
    public static function syncAdmins(): void {
        try {
            $db = (new Database())->getConnection();
            $stmt = $db->query("SELECT id, role FROM users WHERE role IN ('admin', 'super_admin')");
            $adminsUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($adminsUsers as $u) {
                $chk = $db->prepare("SELECT id FROM admins WHERE user_id = ?");
                $chk->execute([$u['id']]);
                if (!$chk->fetch()) {
                    $defaultPerms = array_keys(self::getAvailableModules());
                    self::savePermissions($u['id'], $defaultPerms, $u['role']);
                }
            }
        } catch (Exception $e) {
            error_log("AdminAuth::syncAdmins error: " . $e->getMessage());
        }
    }
}
