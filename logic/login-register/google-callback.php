<?php
// Handles OAuth callback from Google
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/database.php';

// Load environment variables from project root .env (same pattern as smtp_config.php)
try {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
    $dotenv->safeLoad();
    
} catch (Exception $e) {
    // If .env not present, continue; default values / fallbacks below will apply
}

session_start();

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID'] ?? 'YOUR_GOOGLE_CLIENT_ID');
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET'] ?? 'YOUR_GOOGLE_CLIENT_SECRET');
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI'] ?? 'http://localhost/Final%20Project/HidroSmart%20v2.1/logic/login-register/google-callback.php');

try {
    if (!isset($_GET['code'])) {
        throw new Exception('Kode otorisasi tidak ditemukan.');
    }

    // Tukar kode otorisasi dengan access token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) {
        throw new Exception('Gagal mendapatkan token akses: ' . $token['error']);
    }

    $client->setAccessToken($token['access_token']);

    // Ambil informasi user
    $oauth2 = new Google_Service_Oauth2($client);
    $googleUser = $oauth2->userinfo->get();

    $googleId = $googleUser->getId();
    $email    = $googleUser->getEmail();
    $name     = $googleUser->getName();
    $picture  = $googleUser->getPicture();

    // Determine mode (login/register) from state param set earlier
    $mode = $_GET['state'] ?? 'login';

    // Periksa apakah email sudah terdaftar di DB
    $stmt = $pdo->prepare('SELECT id_pengguna, role FROM pengguna WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user && $mode === 'register') {
        // Otomatis buat akun baru dengan role 'user'
        // Insert tanpa id_pengguna (AUTO_INCREMENT)
        $stmtInsert = $pdo->prepare('INSERT INTO pengguna (name, email, password, role, email_verified, verification_token) VALUES (:name, :email, NULL, 1, 1, NULL)');
        $stmtInsert->execute([
            'name'  => $name,
            'email' => $email
        ]);
        $newId = $pdo->lastInsertId();
        $user = [
            'id_pengguna' => $newId,
            'role' => 'user'
        ];
    } elseif (!$user && $mode === 'login') {
        $_SESSION['login_error'] = 'Email Google belum terdaftar. Silakan daftar terlebih dahulu.';
        header('Location: ../../view/login-register.php?tab=register');
        exit();
    }

    // Email terdaftar -> login sukses
    $_SESSION['user_id'] = $user['id_pengguna'];
    $_SESSION['username'] = $name;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $user['role'];
    $_SESSION['picture'] = $picture;

    // Redirect berdasarkan role
    $redirect = ($user['role'] === 'admin') ? '../../view/admin.php' : '../../view/home.php';
    header('Location: ' . $redirect);
    exit();

} catch (Exception $e) {
    // Tangani error
    error_log('Google OAuth error: ' . $e->getMessage());
    $_SESSION['login_error'] = 'Autentikasi Google gagal: ' . $e->getMessage();
    header('Location: ../../view/login-register.php');
    exit();
}
