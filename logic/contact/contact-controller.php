<?php
// contact-controller.php
session_start();

require_once __DIR__ . '/../login-register/database.php';

// Jika ini bukan request AJAX, redirect ke halaman contact
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    header("Location: ../../view/contact.php");
    exit();
}

header("Content-Type: application/json");

$response = ["success" => false, "message" => "Terjadi kesalahan.", "type" => "general", "errors" => []];

// Cek apakah form di-submit
if (!isset($_POST['submit_contact']) && !isset($_POST['action'])) {
    $response['message'] = "Permintaan tidak valid.";
    echo json_encode($response);
    exit();
}

// Cek login status - gunakan session variable yang konsisten
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id_pengguna'])) {
    $response['message'] = "Anda harus login untuk mengirim pesan.";
    $response['type'] = "login_required";
    echo json_encode($response);
    exit();
}

// Ambil user ID dari session
$user_id = $_SESSION['user_id'] ?? $_SESSION['id_pengguna'] ?? null;

// ADDED: Check if user has completed phone number in profile
try {
    $stmt = $pdo->prepare("SELECT phone FROM pengguna WHERE id_pengguna = ?");
    $stmt->execute([$user_id]);
    $user_profile = $stmt->fetch();
    
    if (!$user_profile || empty($user_profile['phone'])) {
        $response['message'] = "Anda harus melengkapi nomor telepon di dashboard profil terlebih dahulu.";
        $response['type'] = "profile_incomplete";
        echo json_encode($response);
        exit();
    }
} catch (PDOException $e) {
    error_log("Profile check error: " . $e->getMessage());
    $response['message'] = "Kesalahan server saat memeriksa profil.";
    echo json_encode($response);
    exit();
}

$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
$errors = [];

// Validasi input
if (strlen($subject) < 5) {
    $errors[] = "Subjek minimal 5 karakter.";
} elseif (strlen($subject) > 255) {
    $errors[] = "Subjek maksimal 255 karakter.";
}

if (strlen($message) < 10) {
    $errors[] = "Pesan minimal 10 karakter.";
} elseif (strlen($message) > 2000) {
    $errors[] = "Pesan maksimal 2000 karakter.";
}

if (!empty($errors)) {
    $response['message'] = "Validasi gagal.";
    $response['errors'] = $errors;
    echo json_encode($response);
    exit();
}

try {
    // Gunakan nama tabel yang sesuai dengan database schema
    $stmt = $pdo->prepare("INSERT INTO contact (id_pengguna, subject, pesan, tanggal_submit) VALUES (?, ?, ?, NOW())");
    $success = $stmt->execute([
        $user_id,
        $subject,
        $message
    ]);

    if ($success) {
        $response['success'] = true;
        $response['message'] = "Pesan berhasil dikirim. Terima kasih atas masukan Anda!";
    } else {
        $response['message'] = "Gagal mengirim pesan.";
    }
} catch (PDOException $e) {
    error_log("Contact form error: " . $e->getMessage());
    $response['message'] = "Kesalahan server. Silakan coba lagi.";
}

echo json_encode($response);
?>
