<?php
// user-session.php - User Session Security System

class UserSecurity {
    private $pdo;
    private $session_timeout = 3600; // 1 hour for users
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function validateUserSession() {
        // Check if user session exists
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            $this->redirectToLogin('Session tidak valid');
            return false;
        }
        
        // Check if user has regular user role (role = 1 or 'user')
        if ($_SESSION['role'] !== 'user' && $_SESSION['role'] !== 1) {
            $this->redirectToLogin('Access denied. Admin tidak dapat mengakses halaman user.');
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $this->session_timeout)) {
            $this->destroySession('Session expired');
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        // Check if user is accessing from multiple locations
        $this->checkMultipleAccess();
        
        return true;
    }
    
    private function checkMultipleAccess() {
        $user_id = $_SESSION['user_id'];
        $current_session = session_id();
        $current_ip = $_SERVER['REMOTE_ADDR'];
        $current_user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        // Create sessions table if not exists
        $this->createSessionsTable();
        
        // Clean old sessions
        $this->cleanOldSessions();
        
        // Check for existing active sessions
        $check_query = "SELECT * FROM user_sessions WHERE user_id = ? AND session_id != ?";
        $stmt = $this->pdo->prepare($check_query);
        $stmt->execute([$user_id, $current_session]);
        $existing_sessions = $stmt->fetchAll();
        
        if (!empty($existing_sessions)) {
            // Terminate other sessions
            $delete_query = "DELETE FROM user_sessions WHERE user_id = ? AND session_id != ?";
            $this->pdo->prepare($delete_query)->execute([$user_id, $current_session]);
        }
        
        // Update or insert current session
        $upsert_query = "INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, last_activity) 
                        VALUES (?, ?, ?, ?, NOW()) 
                        ON DUPLICATE KEY UPDATE 
                        ip_address = VALUES(ip_address), 
                        user_agent = VALUES(user_agent), 
                        last_activity = NOW()";
        $this->pdo->prepare($upsert_query)->execute([$user_id, $current_session, $current_ip, $current_user_agent]);
    }
    
    private function createSessionsTable() {
        $create_table = "CREATE TABLE IF NOT EXISTS user_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_id VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_session (user_id, session_id),
            FOREIGN KEY (user_id) REFERENCES pengguna(id_pengguna) ON DELETE CASCADE
        )";
        $this->pdo->exec($create_table);
    }
    
    private function cleanOldSessions() {
        $clean_query = "DELETE FROM user_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $this->pdo->exec($clean_query);
    }
    
    public function destroySession($message = '') {
        $user_id = $_SESSION['user_id'] ?? null;
        $session_id = session_id();
        
        if ($user_id) {
            // Remove from user_sessions table
            $delete_query = "DELETE FROM user_sessions WHERE user_id = ? AND session_id = ?";
            $this->pdo->prepare($delete_query)->execute([$user_id, $session_id]);
        }
        
        // Destroy PHP session
        session_destroy();
        $this->redirectToLogin($message);
    }
    
    private function redirectToLogin($message = '') {
        $redirect_url = "login-register.php";
        if ($message) {
            $redirect_url .= "?message=" . urlencode($message);
        }
        header("Location: $redirect_url");
        exit();
    }
    
    public function preventAdminAccess() {
        // Check if admin tries to access user pages
        $current_url = $_SERVER['REQUEST_URI'];
        $admin_pages = ['/view/admin.php'];
        
        foreach ($admin_pages as $page) {
            if (strpos($current_url, $page) !== false) {
                $this->redirectToLogin('Access denied. Please login as admin to access this page.');
            }
        }
    }
}
?>
