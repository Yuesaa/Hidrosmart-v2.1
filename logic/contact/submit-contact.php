<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['username']) && !isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location.href = '../../view/login-register.php';</script>";
    exit;
}

// Include database
require_once '../login-register/database.php';

try {
    // Ambil id_pengguna berdasarkan username di session
    $username = $_SESSION['username'] ?? '';
    $user_id = $_SESSION['user_id'] ?? null;
    
    if (!$user_id && $username) {
        $query = "SELECT id_pengguna FROM pengguna WHERE name = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        
        if (!$row) {
            throw new Exception("Pengguna tidak ditemukan.");
        }
        $user_id = $row['id_pengguna'];
    }

    // Ambil data dari POST
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validasi input
    if (empty($subject)) {
        throw new Exception("Subjek tidak boleh kosong.");
    }
    
    if (empty($message)) {
        throw new Exception("Pesan tidak boleh kosong.");
    }

    if (strlen($subject) < 5) {
        throw new Exception("Subjek minimal 5 karakter.");
    }

    if (strlen($message) < 10) {
        throw new Exception("Pesan minimal 10 karakter.");
    }

    // Simpan ke database
    $insert = "INSERT INTO contact (id_pengguna, subject, pesan, tanggal_submit) VALUES (?, ?, ?, NOW())";
    $stmt = $pdo->prepare($insert);
    
    if ($stmt->execute([$user_id, $subject, $message])) {
        echo "<script>alert('Terima kasih atas pesan Anda! Kami akan segera merespons.'); window.location.href='../../view/contact.php';</script>";
    } else {
        throw new Exception("Gagal menyimpan pesan.");
    }

} catch (Exception $e) {
    echo "<script>alert('" . $e->getMessage() . "'); window.history.back();</script>";
}
?>
