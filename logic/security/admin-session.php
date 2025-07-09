<?php
// admin-session.php - Enhanced Admin Security System

class AdminSecurity {
    private $pdo;
    private $session_timeout = 1800; // 30 minutes
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function validateAdminSession() {
        // Check if admin session exists
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $this->redirectToLogin('Session tidak valid');
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $this->session_timeout)) {
            $this->destroySession('Session expired');
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        // Check if admin is accessing from multiple locations
        $this->checkMultipleAccess();
        
        return true;
    }
    
    private function checkMultipleAccess() {
        $admin_id = $_SESSION['user_id'];
        $current_session = session_id();
        $current_ip = $_SERVER['REMOTE_ADDR'];
        $current_user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        // Create sessions table if not exists
        $this->createSessionsTable();
        
        // Clean old sessions
        $this->cleanOldSessions();
        
        // Check for existing active sessions
        $check_query = "SELECT * FROM admin_sessions WHERE admin_id = ? AND session_id != ?";
        $stmt = $this->pdo->prepare($check_query);
        $stmt->execute([$admin_id, $current_session]);
        $existing_sessions = $stmt->fetchAll();
        
        if (!empty($existing_sessions)) {
            // Terminate other sessions
            $delete_query = "DELETE FROM admin_sessions WHERE admin_id = ? AND session_id != ?";
            $this->pdo->prepare($delete_query)->execute([$admin_id, $current_session]);
        }
        
        // Update or insert current session
        $upsert_query = "INSERT INTO admin_sessions (admin_id, session_id, ip_address, user_agent, last_activity) 
                        VALUES (?, ?, ?, ?, NOW()) 
                        ON DUPLICATE KEY UPDATE 
                        ip_address = VALUES(ip_address), 
                        user_agent = VALUES(user_agent), 
                        last_activity = NOW()";
        $this->pdo->prepare($upsert_query)->execute([$admin_id, $current_session, $current_ip, $current_user_agent]);
    }
    
    private function createSessionsTable() {
        $create_table = "CREATE TABLE IF NOT EXISTS admin_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            session_id VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_session (admin_id, session_id),
            FOREIGN KEY (admin_id) REFERENCES pengguna(id_pengguna) ON DELETE CASCADE
        )";
        $this->pdo->exec($create_table);
    }
    
    private function cleanOldSessions() {
        $clean_query = "DELETE FROM admin_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 MINUTE)";
        $this->pdo->exec($clean_query);
    }
    
    public function destroySession($message = '') {
        $admin_id = $_SESSION['user_id'] ?? null;
        $session_id = session_id();
        
        if ($admin_id) {
            // Remove from admin_sessions table
            $delete_query = "DELETE FROM admin_sessions WHERE admin_id = ? AND session_id = ?";
            $this->pdo->prepare($delete_query)->execute([$admin_id, $session_id]);
        }
        
        // Destroy PHP session
        session_destroy();
        $this->redirectToLogin($message);
    }
    
    private function redirectToLogin($message = '') {
        $redirect_url = "../view/login-register.php";
        if ($message) {
            $redirect_url .= "?message=" . urlencode($message);
        }
        header("Location: $redirect_url");
        exit();
    }
    
    public function preventUserAccess() {
        // Check if user tries to access user pages while logged in as admin
        $current_url = $_SERVER['REQUEST_URI'];
        $user_pages = ['/view/home.php', '/view/user.php', '/view/order.php', '/view/about.php', '/view/contact.php', '/view/guarantee.php'];
        
        foreach ($user_pages as $page) {
            if (strpos($current_url, $page) !== false) {
                $this->redirectToLogin('Access denied. Please login as regular user to access this page.');
            }
        }
    }
}
?>
