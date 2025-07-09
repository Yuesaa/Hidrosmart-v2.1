<?php
session_start();
require_once __DIR__ . '/../login-register/database.php';

$token = $_GET['token'] ?? '';
if (!$token) {
    die('Token tidak valid');
}

try {
    $pdo = new Database();
    $db  = $pdo->getPdo();

    // Pastikan tabel verifikasi ada
    $db->exec("CREATE TABLE IF NOT EXISTS email_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_pengguna INT NOT NULL,
        new_email VARCHAR(255) NOT NULL,
        token VARCHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        INDEX(token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Cari token
    $stmt = $db->prepare('SELECT * FROM email_verifications WHERE token = :token LIMIT 1');
    $stmt->execute([':token' => $token]);
    $row = $stmt->fetch();

    if (!$row) {
        die('Token tidak ditemukan atau sudah digunakan.');
    }

    // Cek kedaluwarsa
    if (new DateTime($row['expires_at']) < new DateTime()) {
        // Hapus token
        $db->prepare('DELETE FROM email_verifications WHERE token = :token')->execute([':token' => $token]);
        die('Token telah kedaluwarsa.');
    }

    // Update email pengguna
    $update = $db->prepare('UPDATE pengguna SET email = :email WHERE id_pengguna = :id');
    $update->execute([
        ':email' => $row['new_email'],
        ':id'    => $row['id_pengguna']
    ]);

    // Hapus token
    $db->prepare('DELETE FROM email_verifications WHERE token = :token')->execute([':token' => $token]);

    $_SESSION['success'] = 'Email berhasil diverifikasi.';
    header('Location: ../../view/user.php#profil');
    exit();

} catch (PDOException $e) {
    die('Terjadi kesalahan: ' . $e->getMessage());
}
