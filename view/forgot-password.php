<?php
session_start();
$error = isset($_GET['error']) ? urldecode($_GET['error']) : '';
$success = isset($_GET['success']) ? urldecode($_GET['success']) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - HidroSmart</title>
    <link rel="stylesheet" href="../style/login-register.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-content">
            <div class="auth-card">
                <div class="auth-header"><h2>Lupa Password</h2></div>
                <?php if ($error): ?>
                    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><span><?php echo htmlspecialchars($error); ?></span></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?php echo htmlspecialchars($success); ?></span></div>
                <?php endif; ?>

                <?php if (!$success): ?>
                <form method="POST" action="../logic/login-register/forgot_password.php">
                    <div class="form-group">
                        <label for="email">Masukkan Email Anda</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" id="email" name="email" placeholder="Email Anda" required>
                        </div>
                    </div>
                    <button type="submit" class="auth-btn"><span>Kirim Link Reset</span><i class="fas fa-paper-plane"></i></button>
                </form>
                <?php endif; ?>
                <div class="auth-footer"><a href="login-register.php">Kembali ke Login</a></div>
            </div>
        </div>
    </div>
</body>
</html>
