<?php
session_start();
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
require_once __DIR__ . '/../login-register/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../view/login-register.php');
    exit();
}

// Validasi input
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Metode request tidak valid';
    header('Location: ../../view/user.php#profil');
    exit();
}

$new_email = trim($_POST['email'] ?? '');

// Validasi email kosong
if (empty($new_email)) {
    $_SESSION['error'] = 'Email tidak boleh kosong';
    header('Location: ../../view/user.php#profil');
    exit();
}

// Validasi format email Google
if (!preg_match("/^[a-zA-Z0-9._%+-]+@gmail\\.com$/", $new_email)) {
    $_SESSION['error'] = 'Hanya email @gmail.com yang diperbolehkan';
    header('Location: ../../view/user.php#profil');
    exit();
}

try {
    $pdo = new Database();
    $db = $pdo->getPdo();
    
    // Periksa apakah email sudah digunakan
    $stmt = $db->prepare("SELECT id_pengguna FROM pengguna WHERE email = :email AND id_pengguna != :user_id");
    $stmt->execute([
        ':email' => $new_email,
        ':user_id' => $_SESSION['user_id']
    ]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = 'Email sudah digunakan oleh akun lain';
        header('Location: ../../view/user.php#profil');
        exit();
    }
    
    // Siapkan verifikasi email
    // Pastikan tabel verifikasi tersedia
    $db->exec("CREATE TABLE IF NOT EXISTS email_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_pengguna INT NOT NULL,
        new_email VARCHAR(255) NOT NULL,
        token VARCHAR(64) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        INDEX(token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Hasilkan token
    $token = bin2hex(random_bytes(32));
    $expires = (new DateTime('+1 day'))->format('Y-m-d H:i:s');

    // Simpan token
    $insert = $db->prepare('INSERT INTO email_verifications (id_pengguna, new_email, token, expires_at) VALUES (:id, :email, :token, :exp)');
    $insert->execute([
        ':id'    => $_SESSION['user_id'],
        ':email' => $new_email,
        ':token' => $token,
        ':exp'   => $expires
    ]);

    // Kirim email verifikasi
    require_once __DIR__ . '/../login-register/smtp_config.php';
    

    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_PORT == 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->SMTPDebug  = SMTP_DEBUG;
        $mail->Debugoutput = 'error_log';
        $mail->Timeout     = 20;            // batas 20 detik
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];

        //Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($new_email);

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Verifikasi Perubahan Email Anda';
        $verifyLink = APP_BASE_URL . '/logic/user/verify_email.php?token=' . $token;
        $mail->Body    = "Klik link berikut untuk mengkonfirmasi perubahan email Anda: <a href='$verifyLink'>$verifyLink</a><br>Link ini berlaku 24 jam.";

        $mail->send();
        $successMessage = 'Link verifikasi dikirim ke email baru Anda. Silakan cek inbox/spam.';
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $successMessage]);
            exit();
        }
        $_SESSION['success'] = $successMessage;
    } catch (Exception $e) {
        $errorMessage = 'Gagal mengirim email verifikasi: ' . $mail->ErrorInfo;
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $errorMessage]);
            exit();
        }
        $_SESSION['error'] = $errorMessage;
    }

    
} catch (PDOException $e) {
    $err = 'Terjadi kesalahan: ' . $e->getMessage();
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $err]);
        exit();
    }
    $_SESSION['error'] = $err;
}

header('Location: ../../view/user.php#profil');
exit();
