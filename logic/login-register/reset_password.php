<?php

session_start();

// Dapatkan base URL secara dinamis
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$base_path = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/\\');
$base_url = $protocol . $host . $base_path . '/view/reset-password.php';

// Include file yang diperlukan
// reset_password.php - process new password using token
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/smtp_config.php';

// Include autoloader jika diperlukan
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . dirname(dirname($base_url)) . '/view/login-register.php');
    exit();
}

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (!$token) {
    header('Location: ' . $base_url . '?error=' . urlencode('Token tidak valid'));
    exit();
}
if (strlen($password) < 6) {
    header("Location: $base_url?token=" . urlencode($token) . '&error=' . urlencode('Password minimal 6 karakter'));
    exit();
}
if ($password !== $confirm) {
    header("Location: $base_url?token=" . urlencode($token) . '&error=' . urlencode('Konfirmasi password tidak cocok'));
    exit();
}

try {
    // Fetch reset row
    $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token = ?');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if (!$row) {
        header("Location: $base_url?error=" . urlencode('Token tidak ditemukan atau sudah digunakan'));
        exit();
    }
    // Check expiry
    if (new DateTime() > new DateTime($row['expires_at'])) {
        // delete token
        $pdo->prepare('DELETE FROM password_resets WHERE token = ?')->execute([$token]);
        header("Location: $base_url?error=" . urlencode('Token kadaluarsa')); exit();
    }
    // Update user password
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $pdo->prepare('UPDATE pengguna SET password = ? WHERE email = ?')->execute([$hashed, $row['email']]);
    // delete token
    $pdo->prepare('DELETE FROM password_resets WHERE token = ?')->execute([$token]);

    $success = urlencode('Password berhasil direset. Silakan login.');
    header('Location: ' . APP_BASE_URL . '/view/login-register.php?success=' . $success);
    exit();

} catch (Exception $e) {
    error_log('Reset password error: ' . $e->getMessage());
    header("Location: $base_url?token=" . urlencode($token) . '&error=' . urlencode('Terjadi kesalahan. Coba lagi')); exit();
}
?>
