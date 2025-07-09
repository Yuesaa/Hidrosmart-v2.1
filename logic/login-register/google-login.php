<?php
// Google OAuth 2.0 login entry point
// Redirects user to Google's consent screen.

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/database.php';

session_start();

// Mode can be 'login' or 'register' based on which button the user clicked
$mode = ($_GET['mode'] ?? 'login') === 'register' ? 'register' : 'login';

// ------------------ IMPORTANT ------------------
// Ganti CLIENT_ID dan CLIENT_SECRET di bawah ini dengan
// kredensial OAuth 2.0 milik Anda dari Google Cloud Console.
// ----------------------------------------------

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID'] ?? 'YOUR_GOOGLE_CLIENT_ID');
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET'] ?? 'YOUR_GOOGLE_CLIENT_SECRET');

// Sesuaikan URL di bawah dengan path absolut ke google-callback.php
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI'] ?? 'http://localhost/Final%20Project/HidroSmart%20v2.1/logic/login-register/google-callback.php');

$client->setScopes(['email', 'profile']);
$client->setAccessType('offline');

// Attach state so we know if it was login or register
$client->setState($mode);
$authUrl = $client->createAuthUrl();

header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
exit();
