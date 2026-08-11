<?php
/**
 * ════════════════════════════════════════════════════════
 * CONNEXION BASE DE DONNÉES — Hôtel SEGURO
 * ════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/config.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    
    public $conn;
    
    public function __construct() {
        $this->host = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: 'localhost');
        $this->db_name = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'seguro_hotel');
        $this->username = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'root');
        $this->password = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
        $this->charset = defined('DB_CHARSET') ? DB_CHARSET : (getenv('DB_CHARSET') ?: 'utf8mb4');
    }
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            error_log("DB connection error: " . $exception->getMessage());
            die("Une erreur de connexion est survenue. Veuillez réessayer.");
        }
        
        return $this->conn;
    }
    
    /**
     * Exécute le script SQL de création de la base de données
     */
    public function createDatabase() {
        try {
            $conn = $this->getConnection();
            
            // Lire et exécuter le script SQL
            $sql_file = __DIR__ . '/../database/seguro_hotel.sql';
            if (file_exists($sql_file)) {
                $sql = file_get_contents($sql_file);
                $conn->exec($sql);
                return true;
            }
        } catch(PDOException $exception) {
            error_log("DB createDatabase error: " . $exception->getMessage());
        }
        return false;
    }
}
?>
