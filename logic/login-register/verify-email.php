<?php
session_start();
require_once 'database.php';
require_once 'smtp_config.php';

$token = $_GET['token'] ?? '';
$base_url = '../../view/login-register.php';

if (empty($token)) {
    $_SESSION['login_error'] = 'Token verifikasi tidak valid.';
    header("Location: $base_url?tab=login");
    exit();
}

try {
    // Cari pengguna dengan token
    $stmt = $pdo->prepare('SELECT id_pengguna, email_verified FROM pengguna WHERE verification_token = ?');
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['login_error'] = 'Token verifikasi tidak ditemukan atau sudah kadaluarsa.';
        header("Location: $base_url?tab=login");
        exit();
    }

    if ($user['email_verified']) {
        $_SESSION['login_error'] = 'Email sudah diverifikasi. Silakan login.';
        header("Location: $base_url?tab=login");
        exit();
    }

    // Update status verifikasi
    $stmtUp = $pdo->prepare('UPDATE pengguna SET email_verified = 1, verification_token = NULL WHERE id_pengguna = ?');
    $stmtUp->execute([$user['id_pengguna']]);

    $_SESSION['success'] = 'Verifikasi berhasil! Silakan login.';
    header("Location: $base_url?tab=login");
    exit();

} catch (PDOException $e) {
    error_log('Verify email error: ' . $e->getMessage());
    $_SESSION['login_error'] = 'Terjadi kesalahan. Coba lagi.';
    header("Location: $base_url?tab=login");
    exit();
}
?>
