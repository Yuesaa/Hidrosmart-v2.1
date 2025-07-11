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
// Check login status and profile completeness
$user_logged_in = isset($_SESSION['user_id']) || isset($_SESSION['username']);
$username = $user_logged_in ? ($_SESSION['username'] ?? 'User') : 'Masuk';
$profile_complete = false;
$user_profile = null;

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


// Check for notifications
$notification = $_SESSION['notification'] ?? null;
if ($notification) {
    unset($_SESSION['notification']); // Clear after reading
}

// Data statistik
$stats = [
    'active_users' => '1000+',
    'app_rating' => '4.8',
    'support' => '24/7'
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <!-- CSS -->
    <link rel="stylesheet" href="../style/home.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Notification Container -->
    <div id="notification-container"></div>

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
                        <li><a href="home.php" class="nav-link active"><i class="ri-home-4-line"></i> Home</a></li>
                        <li><a href="about.php" class="nav-link"><i class="ri-information-2-line"></i>About Us</a></li>
                        <li><a href="contact.php" class="nav-link"><i class="ri-phone-line"></i> Contact Us</a></li>
                        <li><a href="guarantee.php" class="nav-link"><i class="ri-shield-line"></i> Guarantee Claim</a>
                        <li><a href="order.php" class="nav-link"><i class="ri-shopping-cart-2-line"></i> Order</a></li>
                        <li><a href="user.php" class="nav-link"><i class="ri-user-3-line"></i>Dashboard</a></li>
                    </ul>
                <?php else: ?>
                    <ul class="nav-list">
                        <li><a href="home.php" class="nav-link active"><i class="ri-home-4-line"></i> Home</a></li>
                        <li><a href="about.php" class="nav-link"><i class="ri-information-2-line"></i>About Us</a></li>
                        <li><a href="contact.php" class="nav-link"><i class="ri-phone-line"></i> Contact Us</a></li>
                        <li><a href="guarantee.php" class="nav-link"><i class="ri-shield-line"></i> Guarantee Claim</a>
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
        <section class="hero-section">
            <div class="container">
                <div class="hero-content">
                    <div class="hero-left">
                        <h1 class="hero-title">
                            <span class="hashtag">#KNOW YOUR</span><br>
                            <span class="main-text">HEALTHY WITH US</span>
                        </h1>

                        <p class="hero-description">
                            Sistem monitoring kesehatan pintar dengan teknologi sensor tumbler
                            yang canggih. Pantau hidrasi dan kesehatan Anda secara real-time.
                        </p>

                        <div class="hero-features">
                            <div class="feature-item">
                                <i class="fas fa-microchip"></i>
                                <span class="span-feature">Smart Sensor</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-mobile-alt"></i>
                                <span class="span-feature">App Integration</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-bell"></i>
                                <span class="span-feature">Real-time Alert</span>
                            </div>
                        </div>

                        <div class="hero-buttons">
                            <a href="order.php" class="btn btn-primary">
                                Pesan Sekarang <i class="fas fa-arrow-right"></i>
                            </a>
                            <a href="#about" class="btn btn-secondary">
                                Pelajari Lebih Lanjut
                            </a>
                        </div>
                    </div>

                    <div class="hero-right">
                        <div class="tumbler-mockup">
                            <div class="tumbler-device">
                                <div class="device-screen">
                                    <div class="screen-header">
                                        <span class="brand-icon"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-droplets h-8 w-8 text-blue-600" data-lov-id="src/components/HomeComponents.tsx:25:12" data-lov-name="Droplets" data-component-path="src/components/HomeComponents.tsx" data-component-line="25" data-component-file="HomeComponents.tsx" data-component-name="Droplets" data-component-content="%7B%22className%22%3A%22h-8%20w-8%20text-blue-600%22%7D">
                                                <path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path>
                                                <path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path>
                                            </svg></span>
                                        <div class="screen-title">
                                            <h3>HIDROSMART</h3>
                                            <p>Smart Tumbler Sensor</p>
                                        </div>
                                    </div>
                                    <div class="screen-status">
                                        <span class="status-text">Status: Connected</span>
                                        <div class="water-level">
                                            <span class="level-text">750ml / 1000ml</span>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: 75%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="notification-badge">
                                <i class="fas fa-bell"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section id="about" class="about-section">
            <div class="container">
                <div class="about-content">
                    <div class="about-left">
                        <div class="tumbler-showcase">
                            <div class="showcase-card">
                                <span class="brand-icon"><svg xmlns="http://www.w3.org/2000/svg" width="150" height="150" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-droplets h-8 w-8 text-blue-600" data-lov-id="src/components/HomeComponents.tsx:25:12" data-lov-name="Droplets" data-component-path="src/components/HomeComponents.tsx" data-component-line="25" data-component-file="HomeComponents.tsx" data-component-name="Droplets" data-component-content="%7B%22className%22%3A%22h-8%20w-8%20text-blue-600%22%7D">
                                        <path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path>
                                        <path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path>
                                    </svg></span>
                                <h3>HidroSmart Tumbler</h3>
                                <p>Smart Sensor Technology</p>
                            </div>
                        </div>
                    </div>

                    <div class="about-right">
                        <h2 class="section-title">ABOUT US</h2>
                        <p class="about-description">
                            Kami hadir untuk memberikan solusi monitoring kesehatan yang inovatif. Dengan
                            teknologi sensor canggih, HidroSmart membantu Anda memantau kebutuhan
                            hidrasi dan menjaga gaya hidup sehat.
                        </p>

                        <h3 class="subsection-title"># TUMBLER SENSOR</h3>
                        <p class="subsection-description">
                            Dengan Adanya Produk ini Anda bisa mengecek dehidrasi anda dan sebuah notifikasi akan
                            muncul di hp anda lalu anda dapat minum dengan teratur.
                        </p>

                        <div class="feature-grid">
                            <div class="feature-item">
                                <i class="fas fa-microchip"></i>
                                <span>Sensor Canggih</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-mobile-alt"></i>
                                <span>Integrasi Mobile</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-shield-alt"></i>
                                <span>Aman Digunakan</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-battery-three-quarters"></i>
                                <span>Hemat Energi</span>
                            </div>
                        </div>

                        <a href="contact.php" class="btn btn-primary">Contact us</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Target Users Section -->
        <section class="target-users-section">
            <div class="container">
                <h2 class="section-title"># COCOK DIGUNAKAN UNTUK :</h2>

                <div class="users-grid">
                    <div class="user-card green">
                        <div class="card-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h3>PELAJAR & MAHASISWA</h3>
                        <p>Untuk mendukung aktivitas belajar dengan hidrasi yang optimal</p>
                    </div>

                    <div class="user-card blue">
                        <div class="card-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <h3>PEKERJA KANTORAN</h3>
                        <p>Membantu menjaga produktivitas dengan pengingat minum yang teratur</p>
                    </div>

                    <div class="user-card purple">
                        <div class="card-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h3>TRAVELER</h3>
                        <p>Pendamping perjalanan yang memastikan hidrasi tetap terjaga</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features-section">
            <div class="container">
                <h2 class="section-title">WE BUILD DIFFERENT</h2>
                <p class="section-subtitle">Fitur-fitur canggih yang membuat HidroSmart berbeda</p>

                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon blue">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3>Sensor & Notifikasi Langsung ke HP</h3>
                        <p>Terintegrasi dengan aplikasi mobile. 5 aplikasi, HidroSmart akan mengirimkan notifikasi saat anda perlu minum dengan teratur bernia untuk kenyamanan anda.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon red">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3>Aplikasi Pelacak Hidrasi</h3>
                        <p>Pantau asupan air harian, atur target hidrasi, dan lihat progress secara real-time melalui aplikasi yang user-friendly untuk semua kalangan.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon green">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3>Layar Sentuh Mini</h3>
                        <p>Tampilkan data air minum, indikator dehidrasi, serta progress harian langsung di layar sentuh kecil yang terletak pada tutup botol.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon purple">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <h3>Desain Eco-Friendly & Stylish</h3>
                        <p>Menggunakan bahan yang ramah lingkungan dengan desain modern yang cocok untuk aktivitas harian, membuat tampilan tetap stylish dalam kehidupan sehari-hari.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <div class="cta-content">
                    <h2 class="cta-title">TERTARIK DENGAN PRODUK KAMI ?</h2>
                    <h3 class="cta-subtitle">INGIN TAHU LEBIH JAUH LAGI ?</h3>
                    <p class="cta-description">
                        Bergabunglah dengan ribuan pengguna yang telah merasakan manfaat
                        HidroSmart. Mulai hidup lebih sehat hari ini!
                    </p>

                    <div class="app-showcase">
                        <div class="app-icon">
                            <span class="cta-brand-icon"><svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-droplets h-8 w-8 text-blue-600" data-lov-id="src/components/HomeComponents.tsx:25:12" data-lov-name="Droplets" data-component-path="src/components/HomeComponents.tsx" data-component-line="25" data-component-file="HomeComponents.tsx" data-component-name="Droplets" data-component-content="%7B%22className%22%3A%22h-8%20w-8%20text-blue-600%22%7D">
                                    <path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path>
                                    <path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path>
                                </svg></span>
                        </div>
                        <div class="app-info">
                            <h4>HIDROSMART</h4>
                            <p>Smart Health Monitoring</p>
                        </div>
                    </div>

                    <div class="cta-buttons">
                        <a href="contact.php" class="btn btn-primary">
                            Hubungi Kami <i class="fas fa-arrow-right"></i>
                        </a>
                        <a href="about.php" class="btn btn-secondary">
                            Pelajari Lebih Lanjut
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Section -->
            <!-- <div class="stats-section">
                <div class="stats-grid">
                    <div class="stat-item">
                        <h3 class="stat-number"><?php echo $stats['active_users']; ?></h3>
                        <p class="stat-label">Pengguna Aktif</p>
                    </div>
                    <div class="stat-item">
                        <h3 class="stat-number"><?php echo $stats['app_rating']; ?>★</h3>
                        <p class="stat-label">Rating Aplikasi</p>
                    </div>
                    <div class="stat-item">
                        <h3 class="stat-number"><?php echo $stats['support']; ?></h3>
                        <p class="stat-label">Customer Support</p>
                    </div>
                </div>
            </div> -->
        </section>

    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <div class="brand-logo">
                        <span class="brand-icon"><svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-droplets h-8 w-8 text-blue-600" data-lov-id="src/components/HomeComponents.tsx:25:12" data-lov-name="Droplets" data-component-path="src/components/HomeComponents.tsx" data-component-line="25" data-component-file="HomeComponents.tsx" data-component-name="Droplets" data-component-content="%7B%22className%22%3A%22h-8%20w-8%20text-blue-600%22%7D">
                                <path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path>
                                <path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path>
                            </svg></span>
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
                            <span>Yogyakarta, Indonesia</span>
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
    <script src="../logic/home/home.js"></script>
    <!-- Chatbot Widget -->
    <script src="../logic/chatbot/chatbot-widget.js"></script>

    <script>
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

            // Show notification
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);

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

        // Show notification if present
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($notification): ?>
                showNotification('<?php echo addslashes($notification['message']); ?>', '<?php echo $notification['type']; ?>');
            <?php endif; ?>
        });
    </script>

    <style>
        /* Notification Styles */
        #notification-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 10000;
        }

        .notification {
            background: #10b981;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transform: translateX(400px);
            transition: transform 0.3s ease;
            max-width: 400px;
            margin-bottom: 10px;
        }

        .notification.notification-success {
            background: #10b981;
        }

        .notification.notification-error {
            background: #ef4444;
        }

        .notification.notification-warning {
            background: #f59e0b;
        }

        .notification.notification-info {
            background: #3b82f6;
        }

        .notification.show {
            transform: translateX(0);
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
    </style>

</body>

</html>