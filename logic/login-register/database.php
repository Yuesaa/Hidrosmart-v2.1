<?php
// Database Configuration
class Database
{
    private $host = 'localhost';
    private $db_name = 'hidrosmart';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    public $pdo;

    public function __construct()
    {
        $this->connect();
    }

    private function connect()
    {
        $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // Log error for debugging
            error_log("Database connection error: " . $e->getMessage());
            throw new PDOException("Koneksi database gagal. Pastikan MySQL server berjalan dan database 'hidrosmart' sudah dibuat.", (int)$e->getCode());
        }
    }

    public function getPdo()
    {
        return $this->pdo;
    }

    // Test connection method
    public function testConnection()
    {
        try {
            $stmt = $this->pdo->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            error_log("Database test failed: " . $e->getMessage());
            return false;
        }
    }
}

// Initialize database connection
try {
    $database = new Database();
    $pdo = $database->getPdo();

    // Test connection
    if (!$database->testConnection()) {
        throw new Exception("Database connection test failed");
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "<br><br>
         <strong>Pastikan:</strong><br>
         1. MySQL server sudah berjalan<br>
         2. Database 'hidrosmart' sudah dibuat<br>
         3. Tabel 'pengguna' sudah ada dengan kolom: id_pengguna, name, email, password, role");
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Utility class for common functions
class Utils
{
    public static function sanitize($data)
    {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function generateId($prefix = '', $length = 8)
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $id = $prefix;
        for ($i = 0; $i < $length; $i++) {
            $id .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $id;
    }

    public static function redirect($url)
    {
        header("Location: $url");
        exit();
    }

    public static function isLoggedIn()
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['session_token']);
    }

    public static function isAdmin()
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    public static function requireLogin()
    {
        if (!self::isLoggedIn()) {
            self::redirect('../view/login-register.php');
        }

        // Validasi token sesi tunggal
        try {
            global $pdo;
            $stmt = $pdo->prepare('SELECT session_token FROM pengguna WHERE id_pengguna = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $row = $stmt->fetch();
            if (!$row || $row['session_token'] !== session_id()) {
                // sesi tidak valid / telah digantikan di perangkat lain
                session_unset();
                session_destroy();
                self::redirect('../view/login-register.php?tab=login&login_error=' . urlencode('Akun Anda login di perangkat lain.'));
            }
        } catch (PDOException $e) {
            error_log('Session check failed: ' . $e->getMessage());
        }
    }

    public static function requireAdmin()
    {
        if (!self::isAdmin()) {
            self::redirect('../view/home.php');
        }
    }
}
