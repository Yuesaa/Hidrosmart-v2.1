<?php
session_start();

require_once '../logic/login-register/database.php';
require_once '../logic/security/user-session.php';

// Initialize user security if logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $userSecurity = new UserSecurity($pdo);

    // Only validate if user is logged in as regular user
    if ($_SESSION['role'] === 'user' || $_SESSION['role'] === 1) {
        if (!$userSecurity->validateUserSession()) {
            exit();
        }
        $userSecurity->preventAdminAccess();
    }
    // If admin is logged in, redirect to admin dashboard
    elseif ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 2) {
        header("Location: admin.php");
        exit();
    }
}

// Page configuration
$site_config = [
    'title' => 'Guarantee Claim - HidroSmart',
    'description' => 'Klaim garansi produk HidroSmart Anda dengan mudah dan cepat. Kami berkomitmen memberikan layanan terbaik untuk kepuasan Anda.',
    'current_page' => 'guarantee'
];

// Check login status and profile completeness
$user_logged_in = isset($_SESSION['user_id']) || isset($_SESSION['username']);
$username = $user_logged_in ? ($_SESSION['username'] ?? 'User') : 'Masuk';
$user_profile   = null;

try {
    if ($user_logged_in && isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE id_pengguna = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_profile = $stmt->fetch(PDO::FETCH_ASSOC);
        $profile_complete = !empty($user_profile['phone']);
    }
} catch (Exception $e) {
    $user_profile = null;
}

// Get user's completed orders for dropdown and check profile completeness
$completed_orders = [];
$profile_complete = false;
$existing_guarantee_orders = [];

if ($user_logged_in) {
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id && isset($_SESSION['username'])) {
        try {
            $stmt = $pdo->prepare("SELECT id_pengguna FROM pengguna WHERE name = ?");
            $stmt->execute([$_SESSION['username']]);
            $row = $stmt->fetch();
            if ($row) {
                $user_id = $row['id_pengguna'];
            }
        } catch (PDOException $e) {
            error_log("Error getting user ID: " . $e->getMessage());
        }
    }

    if ($user_id) {
        // Check profile completeness
        try {
            $stmt = $pdo->prepare("SELECT phone, alamat, avatar FROM pengguna WHERE id_pengguna = ?");
            $stmt->execute([$user_id]);
            $user_profile = $stmt->fetch();

            if ($user_profile && !empty($user_profile['phone']) && !empty($user_profile['alamat'])) {
                $profile_complete = true;
            }
        } catch (PDOException $e) {
            error_log("Error checking user profile: " . $e->getMessage());
        }

        // Get existing guarantee claims to exclude from dropdown
        try {
            $stmt = $pdo->prepare("SELECT id_order FROM guarantee WHERE id_pengguna = ?");
            $stmt->execute([$user_id]);
            $existing_guarantee_orders = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Error getting existing guarantees: " . $e->getMessage());
        }

        // Get completed orders only if profile is complete, excluding orders that already have guarantee claims
        if ($profile_complete) {
            try {
                $placeholders = '';
                $params = [$user_id];

                if (!empty($existing_guarantee_orders)) {
                    $placeholders = ' AND p.id_order NOT IN (' . str_repeat('?,', count($existing_guarantee_orders) - 1) . '?)';
                    $params = array_merge($params, $existing_guarantee_orders);
                }

                $stmt = $pdo->prepare("SELECT p.id_order, p.tanggal_transaksi, p.total_harga, p.color 
                                     FROM payment p 
                                     WHERE p.id_pengguna = ? AND p.status = 'Diterima Customer'" . $placeholders . " 
                                     ORDER BY p.tanggal_transaksi DESC");
                $stmt->execute($params);
                $completed_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Error getting completed orders: " . $e->getMessage());
            }
        }
    }
}

// Contact information
$warranty_terms = [
    'Produk masih dalam masa garansi',
    'Order sudah berstatus "Diterima Customer"',
    'Profil lengkap (nomor HP dan alamat)',
    'Kerusakan bukan karena kelalaian pengguna',
    'Melampirkan bukti kerusakan produk'
];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_config['title']; ?></title>
    <meta name="description" content="<?php echo $site_config['description']; ?>">

    <!-- CSS -->
    <link rel="stylesheet" href="../style/guarantee.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Header Navigation -->
    <header class="header">
        <div class="container-navbar">
            <div class="nav-brand">
                <span class="brand-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-droplets h-8 w-8 text-blue-600" data-lov-id="src/components/HomeComponents.tsx:25:12" data-lov-name="Droplets" data-component-path="src/components/HomeComponents.tsx" data-component-line="25" data-component-file="HomeComponents.tsx" data-component-name="Droplets" data-component-content="%7B%22className%22%3A%22h-8%20w-8%20text-blue-600%22%7D">
                        <path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path>
                        <path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path>
                    </svg>
                </span>
                <span class="brand-text">HIDROSMART</span>
            </div>

            <nav class="nav-menu">
                <?php if (isset($_SESSION['username'])): ?>
                    <ul class="nav-list">
                        <li><a href="home.php" class="nav-link"><i class="ri-home-4-line"></i> Home</a></li>
                        <li><a href="about.php" class="nav-link"><i class="ri-information-2-line"></i>About Us</a></li>
                        <li><a href="contact.php" class="nav-link "><i class="ri-phone-line"></i> Contact Us</a></li>
                        <li><a href="guarantee.php" class="nav-link active"><i class="ri-shield-line"></i> Guarantee Claim</a>
                        <li><a href="order.php" class="nav-link"><i class="ri-shopping-cart-2-line"></i> Order</a></li>
                        <li><a href="user.php" class="nav-link"><i class="ri-user-3-line"></i>Dashboard</a></li>
                    </ul>
                <?php else: ?>
                    <ul class="nav-list">
                        <li><a href="home.php" class="nav-link "><i class="ri-home-4-line"></i> Home</a></li>
                        <li><a href="about.php" class="nav-link"><i class="ri-information-2-line"></i>About Us</a></li>
                        <li><a href="contact.php" class="nav-link "><i class="ri-phone-line"></i> Contact Us</a></li>
                        <li><a href="guarantee.php" class="nav-link active"><i class="ri-shield-line"></i> Guarantee Claim</a>
                        <li><a href="order.php" class="nav-link"><i class="ri-shopping-cart-2-line"></i> Order</a></li>
                    </ul>
                <?php endif; ?>
            </nav>

            <div class="user-section">
                <?php if (isset($_SESSION['username'])): ?>
                    <div class="user-container">
                        <div class="user-avatar">
                            <?php if (!empty($user_profile['avatar'])): ?>
                                <img src="../logic/user/avatars/<?= htmlspecialchars($user_profile['avatar']) ?>" alt="Avatar" class="avatar-img">
                            <?php else: ?>
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="1">
                                    <circle cx="12" cy="12" r="11.5" />
                                    <circle cx="12" cy="9" r="3.5" />
                                    <path d="M19 21c0-3.9-3.1-7-7-7s-7 3.1-7 7" />
                                </svg>
                            <?php endif; ?>
                        </div>
                        <a class="user-greeting">Halo, <?= htmlspecialchars($_SESSION['username']) ?></a>
                    </div>
                    <a href="../logic/login-register/logout.php" class="nav-btn">Logout</a>
                <?php else: ?>
                    <a href="login-register.php" class="nav-btn"><i class="ri-user-line"></i> Masuk</a>
                    <a href="login-register.php" class="nav-btn-daf"><i class="ri-user-add-line"></i>Daftar</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Hero Section -->
        <section class="guarantee-hero">
            <div class="container">
                <div class="hero-content">
                    <div class="hero-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h1 class="hero-title">GUARANTEE CLAIM</h1>
                    <p class="hero-description">
                        Klaim garansi produk HidroSmart Anda dengan mudah dan cepat. Kami
                        berkomitmen memberikan layanan terbaik untuk kepuasan Anda.
                    </p>
                </div>
            </div>
        </section>

        <!-- Guarantee Form Section -->
        <section class="guarantee-form-section">
            <div class="container">
                <div class="guarantee-content">
                    <!-- Guarantee Form -->
                    <div class="form-section">
                        <div class="form-header">
                            <h2 class="form-title">Form Klaim Garansi</h2>
                            <p class="form-subtitle">
                                <?php if ($user_logged_in): ?>
                                    <?php if (!$profile_complete): ?>
                                        <span style="color: #f59e0b;">
                                            <i class="fas fa-info-circle"></i>
                                            Anda harus melengkapi nomor handphone dan alamat di <a href="user.php" style="color: #3b82f6; text-decoration: underline;">dashboard profil</a> terlebih dahulu.
                                        </span>
                                    <?php elseif (empty($completed_orders)): ?>
                                        <span style="color: #f59e0b;">
                                            <i class="fas fa-info-circle"></i>
                                            Anda belum memiliki pesanan yang berstatus "Diterima Customer" atau semua pesanan sudah diklaim garansinya.
                                        </span>
                                    <?php else: ?>
                                        Lengkapi form di bawah ini untuk mengajukan klaim garansi
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #f59e0b;">
                                        <i class="fas fa-info-circle"></i>
                                        Anda harus login terlebih dahulu untuk mengajukan klaim garansi
                                    </span>
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="form-input">
                            <form class="guarantee-form" method="POST" action="../logic/guarantee/guarantee-controller.php" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="id_order">Pilih Order*</label>
                                    <select
                                        id="id_order"
                                        name="id_order"
                                        required
                                        <?php echo (!$profile_complete || empty($completed_orders)) ? 'disabled' : ''; ?>>
                                        <option value="">-- Pilih Order yang Ingin Diklaim --</option>
                                        <?php foreach ($completed_orders as $order): ?>
                                            <option value="<?php echo htmlspecialchars($order['id_order']); ?>">
                                                <?php echo htmlspecialchars($order['id_order']); ?> -
                                                <?php echo ucfirst($order['color']); ?> -
                                                <?php echo date('d/m/Y', strtotime($order['tanggal_transaksi'])); ?> -
                                                Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-hint">Pilih order yang sudah diterima dan ingin diklaim garansinya</small>
                                </div>

                                <div class="form-group">
                                    <label for="deskripsi">Deskripsi Masalah*</label>
                                    <textarea
                                        id="deskripsi"
                                        name="deskripsi"
                                        rows="4"
                                        placeholder="Jelaskan masalah atau kerusakan yang terjadi pada produk HidroSmart Anda..."
                                        required
                                        <?php echo (!$profile_complete || empty($completed_orders)) ? 'disabled' : ''; ?>></textarea>
                                    <small class="form-hint">Berikan deskripsi detail tentang masalah yang dialami</small>
                                </div>

                                <div class="form-group">
                                    <label for="bukti_gambar">Upload Bukti Kerusakan*</label>
                                    <div class="file-upload-wrapper">
                                        <input type="file" id="bukti_gambar" name="bukti_gambar" accept="image/*,.pdf" class="file-input" required <?php echo (!$profile_complete || empty($completed_orders)) ? 'disabled' : ''; ?>>
                                        <label for="bukti_gambar" class="file-upload-label">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <span>Pilih file atau drag & drop</span>
                                            <small>JPG, PNG, PDF (Max 5MB)</small>
                                        </label>
                                    </div>
                                    <div class="file-preview" style="display: none;"></div>
                                </div>

                                <button type="submit" name="submit_guarantee" class="btn-submit" <?php echo (!$profile_complete || empty($completed_orders)) ? 'disabled' : ''; ?>>
                                    <i class="fas fa-paper-plane"></i> Submit Klaim Garansi
                                </button>
                            </form>
                            <div class="form-notice">
                                <p><strong>PESAN GARANSI AKAN KAMI KIRIM PALING LAMA 1×24 JAM</strong></p>
                            </div>
                        </div>
                    </div>

                    <!-- Information Section -->
                    <div class="info-section">
                        <!-- Process Time -->
                        <div class="info-card">
                            <div class="info-icon-clock">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h3 class="info-title">Waktu Proses</h3>
                            <p class="info-description">
                                Klaim garansi akan diproses maksimal 1×24 jam setelah pengajuan
                            </p>
                        </div>

                        <!-- Terms & Conditions -->
                        <div class="info-card">
                            <div class="info-icon-check">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3 class="info-title">Syarat & Ketentuan</h3>
                            <ul class="terms-list">
                                <?php foreach ($warranty_terms as $term): ?>
                                    <li>• <?php echo $term; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <!-- Contact Support -->
                        <div class="support-card">
                            <div class="support-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <h3 class="support-title">Butuh Bantuan?</h3>
                            <div class="support-contacts">
                                <a href="tel:+6281234567890" class="support-contact">
                                    <i class="fas fa-phone"></i>
                                    <span>+62 812-3456-7890</span>
                                </a>
                                <a href="mailto:support@hidrosmart.com" class="support-contact">
                                    <i class="fas fa-envelope"></i>
                                    <span>support@hidrosmart.com</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Additional Info Section -->
        <section class="additional-info-section">
            <div class="container">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-item-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h4>Masa Garansi</h4>
                        <p>Produk HidroSmart memiliki garansi resmi selama 2 tahun dari tanggal pembelian</p>
                    </div>

                    <div class="info-item">
                        <div class="info-item-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h4>Layanan Perbaikan</h4>
                        <p>Tim teknisi berpengalaman siap melakukan perbaikan atau penggantian produk</p>
                    </div>

                    <div class="info-item">
                        <div class="info-item-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <h4>Pengiriman Gratis</h4>
                        <p>Gratis biaya pengiriman untuk klaim garansi ke seluruh Indonesia</p>
                    </div>

                    <div class="info-item">
                        <div class="info-item-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h4>Support 24/7</h4>
                        <p>Tim customer service siap membantu Anda kapan saja melalui berbagai channel</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <div class="brand-logo">
                        <span class="brand-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-droplets h-8 w-8 text-blue-600">
                                <path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path>
                                <path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path>
                            </svg>
                        </span>
                        <span>HIDROSMART</span>
                    </div>
                    <p class="brand-description">
                        Solusi monitoring kesehatan pintar untuk
                        gaya hidup yang lebih sehat dan teratur.
                    </p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                    </div>
                </div>

                <div class="footer-contact">
                    <h4>Kontak</h4>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>info@hidrosmart.com</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span>+62 812-3456-7890</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Jakarta, Indonesia</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="footer-copyright">
                    <p>&copy; 2024 HidroSmart. All rights reserved.</p>
                </div>
                <div class="footer-legal">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="../logic/guarantee/guarantee.js"></script>
</body>

</html>