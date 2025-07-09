<?php
// email-confirmation.php - Simple Email Confirmation System for Localhost
class EmailConfirmation {
    
    private $pdo;
    
    public function __construct($database) {
        $this->pdo = $database;
    }
    
    /**
     * Create email confirmation table if not exists
     */
    public function createConfirmationTable() {
        $sql = "CREATE TABLE IF NOT EXISTS email_confirmations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NOT NULL,
            confirmed BOOLEAN DEFAULT FALSE,
            INDEX(email),
            INDEX(token)
        )";
        
        $this->pdo->exec($sql);
    }
    
    /**
     * Generate confirmation token
     */
    public function generateConfirmationToken($email) {
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Delete old tokens for this email
        $stmt = $this->pdo->prepare("DELETE FROM email_confirmations WHERE email = ?");
        $stmt->execute([$email]);
        
        // Insert new token
        $stmt = $this->pdo->prepare("INSERT INTO email_confirmations (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expires_at]);
        
        return $token;
    }
    
    /**
     * Verify confirmation token
     */
    public function verifyToken($token) {
        $stmt = $this->pdo->prepare("
            SELECT email FROM email_confirmations 
            WHERE token = ? AND expires_at > NOW() AND confirmed = FALSE
        ");
        $stmt->execute([$token]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Mark as confirmed
            $stmt = $this->pdo->prepare("UPDATE email_confirmations SET confirmed = TRUE WHERE token = ?");
            $stmt->execute([$token]);
            
            return $result['email'];
        }
        
        return false;
    }
    
    /**
     * Send confirmation email (localhost simulation)
     */
    public function sendConfirmationEmail($email, $token) {
        // For localhost, we'll just log the confirmation link
        $confirmation_link = "http://localhost/hidrosmart/view/confirm-email.php?token=" . $token;
        
        // Log to file for localhost testing
        $log_message = "Email Confirmation for: $email\n";
        $log_message .= "Confirmation Link: $confirmation_link\n";
        $log_message .= "Time: " . date('Y-m-d H:i:s') . "\n\n";
        
        file_put_contents('email_confirmations.log', $log_message, FILE_APPEND);
        
        return true;
    }
}

// confirm-email.php - Email Confirmation Handler
if (isset($_GET['token'])) {
    require_once '../logic/login-register/database.php';
    require_once '../logic/login-register/email-confirmation.php';
    
    $emailConfirmation = new EmailConfirmation($pdo);
    $email = $emailConfirmation->verifyToken($_GET['token']);
    
    if ($email) {
        // Email confirmed successfully
        session_start();
        $_SESSION['notification'] = [
            'type' => 'success',
            'message' => 'Email berhasil dikonfirmasi! Silakan login dengan akun Anda.'
        ];
        header("Location: login-register.php?tab=login&login_email=" . urlencode($email));
    } else {
        // Invalid or expired token
        session_start();
        $_SESSION['notification'] = [
            'type' => 'error',
            'message' => 'Link konfirmasi tidak valid atau sudah kedaluwarsa.'
        ];
        header("Location: login-register.php");
    }
    exit();
}
?>