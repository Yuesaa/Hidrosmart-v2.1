<?php
// auth.php - Enhanced Authentication dengan Advanced Email Validation
session_start();

// Include database connection and email validator
require_once 'database.php';
require_once 'email-validator.php';
require_once __DIR__ . '/smtp_config.php';
// Autoload PHPMailer
require_once __DIR__ . '/../../vendor/autoload.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../view/login-register.php");
    exit();
}

// Get action from form
$action = $_POST['action'] ?? '';

// Base redirect URL
$base_url = "../../view/login-register.php";

if ($action === 'login') {
    handleLogin();
} elseif ($action === 'register') {
    handleRegister();
} else {
    // Invalid action
    header("Location: $base_url");
    exit();
}

// Function to handle login process (unchanged)
function handleLogin() {
    global $pdo, $base_url;
    
    // Get form data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($email) || empty($password)) {
        $error = urlencode('Email dan password harus diisi');
        $redirect_url = "$base_url?tab=login&login_error=$error&login_email=" . urlencode($email);
        header("Location: $redirect_url");
        exit();
    }
    
    try {
        // Check user in database
        $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        // Jika akun dibuat melalui Google (password null)
        if ($user && empty($user['password'])) {
            $error = urlencode('Akun ini dibuat melalui Google. Silakan klik "Masuk dengan Google" atau atur ulang password.');
            $redirect_url = "$base_url?tab=login&login_error=$error&login_email=" . urlencode($email);
            header("Location: $redirect_url");
            exit();
        }

        // Cek verifikasi email (kecuali admin)
        if ($user && !$user['email_verified'] && $user['role'] != 2) {
            $error = urlencode('Email belum diverifikasi. Silakan cek inbox Anda.');
            $redirect_url = "$base_url?tab=login&login_error=$error&login_email=" . urlencode($email);
            header("Location: $redirect_url");
            exit();
        }

        if ($user && password_verify($password, $user['password'])) {
            // Login successful - enforce single session
            session_regenerate_id(true);
            $sessionToken = session_id();
            // Pastikan kolom session_token ada
            try {
                $pdo->exec("ALTER TABLE pengguna ADD COLUMN IF NOT EXISTS session_token VARCHAR(128) NULL");
            } catch (PDOException $e) {
                // ignore if exists
            }
            try {
                $upd = $pdo->prepare('UPDATE pengguna SET session_token = ? WHERE id_pengguna = ?');
                $upd->execute([$sessionToken, $user['id_pengguna']]);
            } catch (PDOException $e) {
                error_log('Save session token failed: ' . $e->getMessage());
            }
            
            // Set session data
            $_SESSION['user_id']     = $user['id_pengguna'];
            $_SESSION['username']    = $user['name'];
            $_SESSION['email']       = $user['email'];
            $_SESSION['role']        = ($user['role'] == 2) ? 'admin' : 'user';
            $_SESSION['session_token'] = $sessionToken;
            
            // Set login success notification
            $_SESSION['notification'] = [
                'type' => 'success',
                'message' => 'Login berhasil! Selamat datang, ' . $user['name']
            ];
            
            // Redirect based on role
            if ($_SESSION['role'] === 'admin') {
                header("Location: ../../view/admin.php");
            } else {
                header("Location: ../../view/home.php");
            }
            exit();
        } else {
            // Login failed
            $error = urlencode('Email atau password salah');
            $redirect_url = "$base_url?tab=login&login_error=$error&login_email=" . urlencode($email);
            header("Location: $redirect_url");
            exit();
        }
        
    } catch (PDOException $e) {
        // Database error
        error_log("Login error: " . $e->getMessage());
        $error = urlencode('Terjadi kesalahan sistem');
        $redirect_url = "$base_url?tab=login&login_error=$error&login_email=" . urlencode($email);
        header("Location: $redirect_url");
        exit();
    }
}

// Function to handle register process with enhanced email validation
function handleRegister() {
    global $pdo, $base_url;
    
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // For form repopulation on error
    $form_params = "register_name=" . urlencode($name) . "&register_email=" . urlencode($email);
    
    // Basic validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = urlencode('Semua field harus diisi');
        $redirect_url = "$base_url?tab=register&register_error=$error&$form_params";
        header("Location: $redirect_url");
        exit();
    }
    
    // ENHANCED EMAIL VALIDATION - FITUR UTAMA
    $emailValidator = new EmailValidator();
    $email_validation = $emailValidator->validateEmail($email);
    
    if (!$email_validation['valid']) {
        $error = urlencode($email_validation['reason']);
        $redirect_url = "$base_url?tab=register&register_error=$error&$form_params";
        header("Location: $redirect_url");
        exit();
    }
    
    // Password validation
    if (strlen($password) < 6) {
        $error = urlencode('Password minimal 6 karakter');
        $redirect_url = "$base_url?tab=register&register_error=$error&$form_params";
        header("Location: $redirect_url");
        exit();
    }
    
    if ($password !== $confirm_password) {
        $error = urlencode('Password dan konfirmasi password tidak cocok');
        $redirect_url = "$base_url?tab=register&register_error=$error&$form_params";
        header("Location: $redirect_url");
        exit();
    }
    
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id_pengguna FROM pengguna WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = urlencode('Email sudah terdaftar');
            $redirect_url = "$base_url?tab=register&register_error=$error&$form_params";
            header("Location: $redirect_url");
            exit();
        }
        
        // Check if name already exists
        $stmt = $pdo->prepare("SELECT id_pengguna FROM pengguna WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            $error = urlencode('Nama sudah digunakan');
            $redirect_url = "$base_url?tab=register&register_error=$error&$form_params";
            header("Location: $redirect_url");
            exit();
        }
        
        // Insert new user (belum terverifikasi)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO pengguna (name, email, password, role, email_verified) VALUES (?, ?, ?, 1, 0)");
        
        if ($stmt->execute([$name, $email, $hashed_password])) {
                $newUserId = $pdo->lastInsertId();

                // Generate verification token
                $token = bin2hex(random_bytes(32));
                $stmtTok = $pdo->prepare("UPDATE pengguna SET verification_token = ? WHERE id_pengguna = ?");
                $stmtTok->execute([$token, $newUserId]);

                // Send verification email
                sendVerificationEmail($email, $name, $token);

            // Registration successful
            $success = urlencode('Registrasi berhasil! Kami telah mengirim email verifikasi. Silakan cek inbox/spam untuk mengaktifkan akun.');
            $redirect_url = "$base_url?tab=login&success=$success&login_email=" . urlencode($email);
            header("Location: $redirect_url");
            exit();
        } else {
            $error = urlencode('Gagal mendaftarkan akun');
            $redirect_url = "$base_url?tab=register&register_error=$error&$form_params";
            header("Location: $redirect_url");
            exit();
        }
        
    } catch (PDOException $e) {
        // Database error
        error_log("Register error: " . $e->getMessage());
        $error = urlencode('Terjadi kesalahan sistem');
        $redirect_url = "$base_url?tab=register&register_error=$error&$form_params";
        header("Location: $redirect_url");
        exit();
    }
}
// ---------------- HELPER: Kirim Email Verifikasi ----------------
function sendVerificationEmail($email, $name, $token) {
    // PHPMailer setup
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        //Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email, $name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Confirm your email – HidroSmart';

        $verifyLink = APP_BASE_URL . '/logic/login-register/verify-email.php?token=' . $token;
        $mail->Body    = "<p>Hi $name,</p>
                           <p>Thanks for signing up to HidroSmart.</p>
                           <p>Please confirm your email address by clicking the button below:</p>
                           <p><a href='$verifyLink' style='display:inline-block;padding:10px 20px;background:#2563eb;color:#fff;text-decoration:none;border-radius:5px;'>Confirm your email</a></p>
                           <p>If the button doesn’t work, copy and paste this URL into your browser:</p>
                           <p>$verifyLink</p>
                           <p>Salam,<br>Tim HidroSmart</p>";
        $mail->AltBody = "Confirm your email via this link: $verifyLink";

        $mail->send();
    } catch (Exception $e) {
        error_log('Email verification send failed: ' . $mail->ErrorInfo);
        // we don't block registration if email fails
    }
}
?>