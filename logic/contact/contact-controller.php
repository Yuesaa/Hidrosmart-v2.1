<?php
// contact-controller.php
session_start();

// Load environment variables for WhatsApp
require_once __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

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

// Rate limit: 10 detik per user
$rateLimitSeconds = 10;
$now = time();
$lastSubmitKey = 'last_contact_submit_' . ($user_id ?? 'guest');
if (isset($_SESSION[$lastSubmitKey]) && ($now - $_SESSION[$lastSubmitKey] < $rateLimitSeconds)) {
    $sisa = $rateLimitSeconds - ($now - $_SESSION[$lastSubmitKey]);
    $response['message'] = "Anda harus menunggu $sisa detik sebelum mengirim pesan lagi.";
    $response['type'] = "rate_limit";
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
    error_log("Profile check error: " . $e->getMessage(), 3, __DIR__ . '/../../logs/error.log');
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
        // Simpan timestamp submit terakhir
        $_SESSION[$lastSubmitKey] = $now;
    } else {
        $response['message'] = "Gagal mengirim pesan.";
    }
} catch (PDOException $e) {
    error_log("Contact form error: " . $e->getMessage(), 3, __DIR__ . '/../../logs/error.log');
    $response['message'] = "Kesalahan server. Silakan coba lagi.";
}

// Send WhatsApp notification to support when message sent
if (isset($response['success']) && $response['success']) {
    try {
        // Kirim ke grup WhatsApp
        $adminPhone = 'GTn5pGgPUwv4ne5jDg5fUJ'; // ID grup dari link undangan
        $fonnteToken = trim($_ENV['FONNTE_TOKEN'] ?? getenv('FONNTE_TOKEN') ?? '');
        if ($fonnteToken) {
            $url = "https://api.fonnte.com/send";
            $text = "New contact message from {$user_profile['phone']}\nSubject: {$subject}\nMessage: {$message}";
            $payload = [
                'target' => $adminPhone,
                'message' => $text,
                // 'countryCode' => '62', // opsional jika nomor tanpa kode negara
            ];
            $headers = [
                "Authorization: $fonnteToken"
            ];
            error_log("Fonnte attempt: token=" . substr($fonnteToken,0,10) . "..., to={$adminPhone}" . PHP_EOL, 3, __DIR__ . '/../../logs/whatsapp_debug.log');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $res = curl_exec($ch);
            if (curl_errno($ch)) {
                error_log('Fonnte send error: ' . curl_error($ch) . PHP_EOL, 3, __DIR__ . '/../../logs/whatsapp_error.log');
            } else {
                error_log('Fonnte send response: ' . $res . PHP_EOL, 3, __DIR__ . '/../../logs/whatsapp_debug.log');
            }
            curl_close($ch);
            $response['wa_debug'] = [
                'token_preview' => substr($fonnteToken, 0, 10) . '...',
                'to' => $adminPhone,
                'response' => $res
            ];
        } else {
            error_log('Fonnte credentials not configured', 3, __DIR__ . '/../../logs/whatsapp_error.log');
$response['wa_debug'] = 'Fonnte credentials not configured';
        }
    } catch (Exception $e) {
        error_log('WhatsApp send exception: ' . $e->getMessage(), 3, __DIR__ . '/../../logs/whatsapp_error.log');
$response['wa_debug'] = $e->getMessage();
    }
}

echo json_encode($response);
?>
