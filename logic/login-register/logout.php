<?php
session_start();

// Get username before destroying session
$username = $_SESSION['username'] ?? 'User';

// Hapus token sesi di database
require_once 'database.php';
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare('UPDATE pengguna SET session_token = NULL WHERE id_pengguna = ?');
        $stmt->execute([$_SESSION['user_id']]);
    } catch (PDOException $e) {
        error_log('Clear session token failed: ' . $e->getMessage());
    }
}

// Destroy all session data
session_unset();
session_destroy();

// Start new session for notification
session_start();

// Set logout notification
$_SESSION['notification'] = [
    'type' => 'success',
    'message' => 'Berhasil logout. Sampai jumpa, ' . $username . '!'
];

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Output page to clear chat history and redirect to home
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Logout</title>
  <script>
    // Clear chatbot history on logout
    localStorage.removeItem('hidrosmart_chat_last_user');
    Object.keys(localStorage).forEach(function(k){
      if (k.startsWith('hidrosmart_chat_history_')) {
        localStorage.removeItem(k);
      }
    });
    // Redirect to home page
    window.location.href = '../../view/home.php';
  </script>
</head>
<body>
  <noscript>
    <meta http-equiv="refresh" content="0;url=../../view/home.php">
  </noscript>
</body>
</html>
<?php
exit();
?>