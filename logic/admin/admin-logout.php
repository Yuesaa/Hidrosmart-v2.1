<?php
// admin-logout.php - Simple redirect to regular logout
session_start();

// Clear admin flag
unset($_SESSION['is_admin']);

// Redirect to regular logout
header("Location: ../login-register/logout.php");
exit();
?>
