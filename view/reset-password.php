<?php
session_start();
$token = $_GET['token'] ?? '';
if (!$token) {
    die('Token tidak valid');
}
// Messages from URL
$reset_error = isset($_GET['error']) ? urldecode($_GET['error']) : '';
$reset_success = isset($_GET['success']) ? urldecode($_GET['success']) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - HidroSmart</title>
    <link rel="stylesheet" href="../style/login-register.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .input-wrapper {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            transition: color 0.3s;
        }
        .toggle-password:hover {
            color: #4a89dc;
        }
        .input-wrapper input {
            padding-right: 40px !important;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-content">
            <div class="auth-card">
                <div class="auth-header"><h2>Reset Password</h2></div>

                <?php if ($reset_error): ?>
                    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><span><?php echo htmlspecialchars($reset_error); ?></span></div>
                <?php endif; ?>
                <?php if ($reset_success): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?php echo htmlspecialchars($reset_success); ?></span></div>
                <?php endif; ?>

                <?php if (!$reset_success): ?>
                <form method="POST" action="../logic/login-register/reset_password.php">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="form-group">
                        <label for="new-password">Password Baru</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="new-password" name="password" placeholder="Password baru" required>
                            <i class="fas fa-eye toggle-password" data-target="new-password"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm-password">Konfirmasi Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="confirm-password" name="confirm_password" placeholder="Konfirmasi password" required>
                            <i class="fas fa-eye toggle-password" data-target="confirm-password"></i>
                        </div>
                    </div>
                    <button type="submit" class="auth-btn"><span>Reset Password</span><i class="fas fa-sync"></i></button>
                </form>
                <?php endif; ?>

                <div class="auth-footer">
                    <a href="login-register.php">Kembali ke Login</a>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility
            const togglePassword = (button) => {
                const targetId = button.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle eye icon
                button.classList.toggle('fa-eye');
                button.classList.toggle('fa-eye-slash');
            };

            // Add click event to all toggle password buttons
            document.querySelectorAll('.toggle-password').forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    togglePassword(button);
                });
            });
        });
    </script>
</body>
</html>
