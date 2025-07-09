<?php
// Autoload Composer (PHPMailer, vlucas/phpdotenv, etc.)
require_once __DIR__ . '/../../vendor/autoload.php';

// Load environment variables from project root .env
try {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
    $dotenv->safeLoad(); // safeLoad() to avoid exception if .env missing in some environments
} catch (Exception $e) {
    // If .env not found, continue; constants below may use defaults
}
// SMTP configuration for PHPMailer
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_PORT', (int)($_ENV['SMTP_PORT'] ?? 587));            // 465 untuk SSL, 587 untuk TLS
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? 'your_email@example.com');
$pw = $_ENV['SMTP_PASSWORD'] ?? 'secret';
// Google App Passwords sometimes stored with spaces for readability
$pw = trim(str_replace(['"','\'"',' '], '', $pw));
define('SMTP_PASSWORD', $pw);
// Determine encryption type by port
if (SMTP_PORT == 465) {
    define('SMTP_SECURE', PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS);
} else {
    define('SMTP_SECURE', PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS);
}

define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM_EMAIL'] ?? SMTP_USERNAME);
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? 'HidroSmart');

// Nonaktifkan output debug langsung
// 0 = off (for production use)
// 1 = client messages
// 2 = client and server messages
define('SMTP_DEBUG', 0);

// Base URL aplikasi
define('APP_BASE_URL', $_ENV['APP_BASE_URL'] ?? 'http://localhost/Final%20Project/HidroSmart%20v2.1');

// Log file untuk debug
$log_file = __DIR__ . '/smtp_debug.log';
ini_set('display_errors', 0);  // Matikan display_errors untuk produksi
error_reporting(E_ALL);
ini_set('error_log', $log_file);
ini_set('log_errors', 1);
?>
