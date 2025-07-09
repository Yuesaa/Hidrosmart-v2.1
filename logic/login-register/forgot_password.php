<?php
// forgot_password.php - Handle forgot password request
ob_start(); // Start output buffering
session_start();

require_once 'database.php';
require_once __DIR__ . '/smtp_config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Redirect base
$base_url = APP_BASE_URL . '/view/forgot-password.php';

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $base_url");
    exit();
}

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = urlencode('Email tidak valid');
    header("Location: $base_url?error=$error");
    exit();
}

try {
    // Ensure password_resets table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(token),
        INDEX(email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Check user
    $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        $error = urlencode('Email tidak ditemukan');
        header("Location: $base_url?error=$error");
        exit();
    }

    // Generate token
    $token = bin2hex(random_bytes(32));
    $expires_at = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

    // Delete existing tokens for this email
    $pdo->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);

    // Insert new token
    $insert = $pdo->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?,?,?)');
    $insert->execute([$email, $token, $expires_at]);

    // Send email
    $reset_link = APP_BASE_URL . '/view/reset-password.php?token=' . urlencode($token);

    $log_file = __DIR__ . '/smtp_debug.log';
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    ini_set('error_log', $log_file);

    error_log("=== FORGOT PASSWORD REQUEST START ===");
    error_log("Time: " . date('Y-m-d H:i:s'));

    $mail = new PHPMailer(true);
    if (SMTP_DEBUG) $mail->SMTPDebug = 2;
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port = SMTP_PORT;

    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress($email, $user['name']);

    $mail->isHTML(true);
    $mail->Subject = 'Reset Password HidroSmart';
    $mail->Body = '<p>Halo ' . htmlspecialchars($user['name']) . ',</p>' .
                  '<p>Anda menerima email ini karena ada permintaan reset password untuk akun Anda.</p>' .
                  '<p>Silakan klik tautan berikut untuk mengatur ulang password (berlaku 1 jam):<br>' .
                  '<a href="' . $reset_link . '">' . $reset_link . '</a></p>' .
                  '<p>Jika Anda tidak meminta reset, abaikan email ini.</p>' .
                  '<p>Terima kasih,<br>Tim HidroSmart</p>';

    $mail->AltBody = "Kunjungi $reset_link untuk reset password. Link berlaku 1 jam.";

    $mail->send();
    ob_end_clean(); // Clean the output buffer
    $success = urlencode('Link reset password telah dikirim. Silakan cek email Anda.');
    header("Location: $base_url?success=$success");
    exit();

} catch (Exception $e) {
    ob_end_clean(); // Clean the output buffer
    error_log('Forgot password mail error: ' . $e->getMessage());
    $error = urlencode('Gagal mengirim email. Coba lagi nanti.');
    header("Location: $base_url?error=$error");
    exit();
} catch (Throwable $e) {
    ob_end_clean(); // Clean the output buffer
    error_log('Forgot password error: ' . $e->getMessage());
    $error = urlencode('Terjadi kesalahan sistem');
    header("Location: $base_url?error=$error");
    exit();
}
?>
