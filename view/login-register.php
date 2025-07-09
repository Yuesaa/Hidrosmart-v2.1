<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id']) || isset($_SESSION['username'])) {
    $role = $_SESSION['role'] ?? 'user';
    $redirect_url = ($role === 'admin') ? 'admin.php' : 'home.php';
    header("Location: $redirect_url");
    exit();
}

// Get messages from URL parameters
$active_tab = isset($_GET['tab']) && $_GET['tab'] === 'register' ? 'register' : 'login';
$login_error = isset($_GET['login_error']) ? urldecode($_GET['login_error']) : '';
$register_error = isset($_GET['register_error']) ? urldecode($_GET['register_error']) : '';
$register_success = isset($_GET['success']) ? urldecode($_GET['success']) : '';

// Check for login required notification
$login_required = isset($_GET['login_required']) ? urldecode($_GET['login_required']) : '';

// Form data for repopulation
$form_data = [
    'login_email' => $_GET['login_email'] ?? '',
    'register_name' => $_GET['register_name'] ?? '',
    'register_email' => $_GET['register_email'] ?? ''
];

// If there's a success message, show login tab
if ($register_success) {
    $active_tab = 'login';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register - HidroSmart</title>
    <meta name="description" content="Masuk atau daftar untuk mengakses layanan HidroSmart">
    
    <!-- CSS -->
    <link rel="stylesheet" href="../style/login-register.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Notification Container -->
    <div id="notification-container"></div>

    <div class="auth-container">
        <div class="auth-background">
            <div class="background-pattern"></div>
            <div class="floating-elements">
                <div class="floating-element"></div>
                <div class="floating-element"></div>
                <div class="floating-element"></div>
                <div class="floating-element"></div>
                <div class="floating-element"></div>
            </div>
        </div>
        
        <div class="auth-content">
            <!-- Brand Header -->
            <div class="brand-header">
                <div class="brand-logo">
                    <span class="brand-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-droplets h-8 w-8 text-blue-600">
                            <path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path>
                            <path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path>
                        </svg>
                    </span>
                    <span>HIDROSMART</span>
                </div>
                <p class="brand-subtitle">Masuk atau daftar untuk melanjutkan</p>
            </div>
            
            <!-- Auth Card -->
            <div class="auth-card">
                <div class="auth-header">
                    <h2>Autentikasi</h2>
                </div>
                
                <!-- Tab Navigation -->
                <div class="tab-navigation">
                    <button class="tab-btn <?php echo $active_tab === 'login' ? 'active' : ''; ?>" 
                            onclick="switchTab('login')">Masuk</button>
                    <button class="tab-btn <?php echo $active_tab === 'register' ? 'active' : ''; ?>" 
                            onclick="switchTab('register')">Daftar</button>
                </div>
                
                <!-- Login Form -->
                   
                <div id="login-form" class="auth-form <?php echo $active_tab === 'login' ? 'active' : ''; ?>">
                    <?php if ($login_error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?php echo htmlspecialchars($login_error); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($register_success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <span><?php echo htmlspecialchars($register_success); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($login_required): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span><?php echo htmlspecialchars($login_required); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Form mengarah ke auth.php untuk login -->
                    <form method="POST" action="../logic/login-register/auth.php">
                        <input type="hidden" name="action" value="login">
                        
                        <div class="form-group">
                            <label for="login-email">Email</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope input-icon"></i>
                                <input type="email" id="login-email" name="email" 
                                       placeholder="Email Anda" required
                                       value="<?php echo htmlspecialchars($form_data['login_email']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="login-password">Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" id="login-password" name="password" 
                                       placeholder="Password Anda" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('login-password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" class="auth-btn">
                            <span>Masuk</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                        <!-- Google Login Button -->
                        <div class="social-login">
                            <a href="../logic/login-register/google-login.php?mode=login" class="google-btn">
                                <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google Logo"/>
                                <span>Masuk dengan Google</span>
                            </a>
                        </div>
                        <!-- End Google Login -->
                    </form>
                    
                    <div class="auth-footer">
                        <p><a href="forgot-password.php">Lupa password?</a></p>
                        <p>Belum punya akun? <a href="#" onclick="switchTab('register')">Daftar sekarang</a></p>
                        <a href="home.php" class="back-home">
                            <i class="fas fa-home"></i>
                            Kembali ke Beranda
                        </a>
                    </div>
                </div>
                
                <!-- Register Form -->
                <div id="register-form" class="auth-form <?php echo $active_tab === 'register' ? 'active' : ''; ?>">
                    <?php if ($register_error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?php echo htmlspecialchars($register_error); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Form mengarah ke auth.php untuk register -->
                    <form method="POST" action="../logic/login-register/auth.php">
                        <input type="hidden" name="action" value="register">
                        
                        <div class="form-group">
                            <label for="register-name">Nama Lengkap</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" id="register-name" name="name" 
                                       placeholder="Nama Lengkap" required
                                       value="<?php echo htmlspecialchars($form_data['register_name']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="register-email">Email</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope input-icon"></i>
                                <input type="email" id="register-email" name="email" 
                                       placeholder="Email Anda" required
                                       value="<?php echo htmlspecialchars($form_data['register_email']); ?>">
                            </div>
                            <small class="form-hint">Gunakan email yang terdaftar dan valid</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="register-password">Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" id="register-password" name="password" 
                                       placeholder="Password" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('register-password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="form-hint">Minimal 6 karakter</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="register-confirm-password">Konfirmasi Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" id="register-confirm-password" name="confirm_password" 
                                       placeholder="Konfirmasi Password" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('register-confirm-password')">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" class="auth-btn">
                            <span>Daftar</span>
                            <i class="fas fa-user-plus"></i>
                        </button>

                        <!-- Google Login Button -->
                        <div class="social-login">
                            <a href="../logic/login-register/google-login.php?mode=register" class="google-btn">
                                <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google Logo"/>
                                <span>Daftar dengan Google</span>
                            </a>
                        </div>
                        <!-- End Google Login -->
                    </form>
                    
                    <div class="auth-footer">
                        <p>Sudah punya akun? <a href="#" onclick="switchTab('login')">Masuk sekarang</a></p>
                        <a href="home.php" class="back-home">
                            <i class="fas fa-home"></i>
                            Kembali ke Beranda
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Demo Accounts Info -->
            <div class="demo-info">
                <h4>Demo Accounts:</h4>
                <div class="demo-accounts">
                    <div class="demo-account">
                        <strong>Admin:</strong> admin@hidrosmart.com / admin123
                    </div>
                    <div class="demo-account">
                        <strong>User:</strong> yuesa.saka@gmail.com / yuesa123
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="../logic/login-register/auth.js"></script>
    
    <script>
        // Set initial tab based on PHP variable
        document.addEventListener('DOMContentLoaded', function() {
            const activeTab = '<?php echo $active_tab; ?>';
            if (window.authPage) {
                window.authPage.currentTab = activeTab;
            }

            // Show login required notification if present
            <?php if ($login_required): ?>
            showNotification('<?php echo addslashes($login_required); ?>', 'warning');
            <?php endif; ?>
        });

        function togglePassword() {
            const pw = document.getElementById("password");
            pw.type = pw.type === "password" ? "text" : "password";
        }

        // Notification function
        function showNotification(message, type = 'info') {
            const container = document.getElementById('notification-container');
            
            // Remove existing notifications
            container.innerHTML = '';
            
            // Create notification
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            
            const iconMap = {
                'success': 'fa-check-circle',
                'error': 'fa-exclamation-circle',
                'info': 'fa-info-circle',
                'warning': 'fa-exclamation-triangle'
            };
            
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas ${iconMap[type]}"></i>
                    <span>${message}</span>
                    <button class="notification-close" onclick="closeNotification(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            container.appendChild(notification);
            
            // Auto close after 5 seconds
            setTimeout(() => {
                closeNotification(notification.querySelector('.notification-close'));
            }, 5000);
        }

        function closeNotification(closeBtn) {
            const notification = closeBtn.closest('.notification');
            notification.style.transform = 'translateX(400px)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    </script>

    <style>
        /* Notification Styles */
        #notification-container {
            position: fixed;
            top: 80px;
            right: 420px;
            z-index: 10000;
        }

        .notification {
            background: #10b981;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transform: translateX(400px);
            transition: transform 0.3s ease;
            max-width: 400px;
            margin-bottom: 10px;
        }

        .notification.notification-success { background: #10b981; }
        .notification.notification-error { background: #ef4444; }
        .notification.notification-warning { background: #f59e0b; }
        .notification.notification-info { background: #3b82f6; }

        /* Google Button */
        .social-login {
            text-align: center;
            margin-bottom: 1rem;
        }
        .google-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #ffffff;
            color: #444;
            border: 1px solid #ddd;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.2s;
        }
        .google-btn:hover {
            background: #f5f5f5;
        }
        .google-btn img {
            width: 20px;
            height: 20px;
        }

        .notification-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .notification-close {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: 0;
            margin-left: auto;
        }

        .notification-close:hover {
            opacity: 0.8;
        }

        /* Show notification */
        .notification.show {
            transform: translateX(0);
        }
    </style>
</body>
</html>