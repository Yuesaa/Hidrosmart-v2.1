<?php
session_start();
require_once __DIR__ . '/../login-register/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode tidak valid']);
    exit();
}

$currentPassword = trim($_POST['current_password'] ?? '');
$newPassword     = trim($_POST['new_password'] ?? '');

if (empty($currentPassword) || empty($newPassword)) {
    echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi']);
    exit();
}

if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password baru minimal 6 karakter']);
    exit();
}

try {
    $db  = (new Database())->getPdo();
    $id  = $_SESSION['user_id'];

    // ambil password lama
    $stmt = $db->prepare('SELECT password FROM pengguna WHERE id_pengguna = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($currentPassword, $row['password'])) {
        echo json_encode(['success' => false, 'message' => 'Password saat ini salah']);
        exit();
    }

    if (password_verify($newPassword, $row['password'])) {
        echo json_encode(['success' => false, 'message' => 'Password baru tidak boleh sama dengan password lama']);
        exit();
    }

    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $update = $db->prepare('UPDATE pengguna SET password = ? WHERE id_pengguna = ?');
    if ($update->execute([$hashed, $id])) {
        echo json_encode(['success' => true, 'message' => 'Password berhasil diubah']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan password']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: '.$e->getMessage()]);
}
?>
