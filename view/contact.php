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
    'title' => 'Contact Us - HidroSmart',
    'description' => 'Hubungi tim HidroSmart untuk pertanyaan, saran, atau dukungan teknis. Kami siap membantu Anda.',
    'current_page' => 'contact'
];

// Check login status and profile completeness
$user_logged_in = isset($_SESSION['user_id']) || isset($_SESSION['username']);
$username = $user_logged_in ? ($_SESSION['username'] ?? 'User') : 'Masuk';
$profile_complete = false;
$user_profile = null;

try {
    if ($user_logged_in) {
        // Check if user profile is complete (phone number required)
        $stmt = $pdo->prepare("SELECT phone, name, avatar FROM pengguna WHERE id_pengguna = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_profile = $stmt->fetch();

        if ($user_profile && !empty($user_profile['phone'])) {
            $profile_complete = true;
        }
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}

// Contact information
$contact_info = [
    [
        'icon' => 'fas fa-phone',
        'title' => 'Telepon',
        'main' => '+62 812-3456-7890',
        'sub' => 'Senin - Jumat, 08:00 - 17:00',
        'background' => 'linear-gradient(150deg, #1976d2 50%, #26c6da 100%);'
    ],
    [
        'icon' => 'fas fa-envelope',
        'title' => 'Email',
        'main' => 'info@hidrosmart.com',
        'sub' => 'Respon dalam 24 jam',
        'background' => 'linear-gradient(150deg, #1976d2 50%, #26c6da 100%);'
    ],
    [
        'icon' => 'fas fa-map-marker-alt',
        'title' => 'Alamat',
        'main' => 'Jakarta, Indonesia',
        'sub' => 'Kantor pusat HidroSmart',
        'background' => 'linear-gradient(150deg, #1976d2 50%, #26c6da 100%);'
    ],
    [
        'icon' => 'fas fa-clock',
        'title' => 'Jam Operasional',
        'main' => '24/7 Support',
        'sub' => 'Customer service siap membantu',
        'background' => 'linear-gradient(150deg, #1976d2 50%, #26c6da 100%);'
    ]
];

// Enhanced FAQ data - 8 questions as shown in images
$faqs = [
    'left' => [
        [
            'icon' => 'fa-solid fa-droplet',
            'question' => 'Apa itu HIDROSMART dan bagaimana cara kerjanya?',
            'answer' => 'HIDROSMART adalah sistem filter air canggih yang menggunakan teknologi reverse osmosis untuk menghasilkan air minum berkualitas tinggi. Sistem ini bekerja dengan menyaring air melalui beberapa tahap filtrasi untuk menghilangkan kontaminan, bakteri, dan zat berbahaya lainnya.',
            'background' => '#1d4ed8'
        ],
        [
            'icon' => 'ri-shield-check-line',
            'background' => '#00E676',
            'question' => 'Berapa lama garansi produk HIDROSMART?',
            'answer' => 'Semua produk HIDROSMART dilengkapi dengan garansi resmi selama 2 tahun untuk komponen utama dan 1 tahun untuk aksesori. Garansi mencakup kerusakan manufaktur dan layanan perbaikan gratis selama masa garansi berlaku.'
        ],
        [
            'icon' => 'fas fa-sync-alt',
            'background' => '#F57C00',
            'question' => 'Seberapa sering filter perlu diganti?',
            'answer' => 'Filter pre-treatment perlu diganti setiap 6-12 bulan, sedangkan membran RO perlu diganti setiap 18-24 bulan. Waktu penggantian dapat bervariasi tergantung kualitas air baku dan intensitas penggunaan. Kami akan mengingatkan Anda saat saatnya penggantian.'
        ],
        [
            'icon' => 'fas fa-bolt',
            'background' => '#FDD835',
            'question' => 'Berapa konsumsi listrik sistem HIDROSMART?',
            'answer' => 'Sistem HIDROSMART sangat hemat energi dengan konsumsi listrik rata-rata hanya 24 watt (setara dengan 1 lampu LED). Biaya operasional bulanan sangat minimal, sekitar Rp 15.000-25.000 per bulan tergantung tarif listrik daerah Anda.'
        ]
    ],
    'right' => [
        [
            'icon' => 'ri-award-line',
            'background' => '#8E24AA',
            'question' => 'Apakah HIDROSMART memiliki sertifikat resmi?',
            'answer' => 'Ya, semua produk HIDROSMART telah tersertifikasi oleh Kementerian Kesehatan RI, memiliki sertifikat ISO 9001:2015, dan telah lulus uji laboratorium terakreditasi. Kami juga memiliki sertifikat halal dari MUI untuk memastikan produk aman dikonsumsi.'
        ],
        [
            'icon' => 'fas fa-users',
            'background' => '#5E35B1',
            'question' => 'Bagaimana cara perawatan sistem HIDROSMART?',
            'answer' => 'Perawatan sangat mudah: bersihkan housing filter setiap 3 bulan, ganti filter sesuai jadwal, dan lakukan pembilasan sistem setiap minggu. Tim teknisi kami juga menyediakan layanan perawatan berkala untuk memastikan sistem selalu dalam kondisi optimal.'
        ],
        [
            'icon' => 'fas fa-headset',
            'background' => '#E53935',
            'question' => 'Apakah tersedia layanan purna jual?',
            'answer' => 'Tentu! Kami menyediakan layanan purna jual lengkap meliputi: instalasi gratis, pelatihan penggunaan, layanan konsultasi 24/7, maintenance berkala, dan ketersediaan spare part. Tim customer service kami siap membantu Anda kapan saja.'
        ],
        [
            'icon' => 'fas fa-star',
            'background' => '#FFB300',
            'question' => 'Apa keunggulan HIDROSMART dibanding produk lain?',
            'answer' => 'HIDROSMART unggul dalam hal: teknologi terdepan dengan efisiensi tinggi, desain compact yang hemat tempat, sistem monitoring otomatis, garansi lengkap, layanan purna jual terbaik, dan harga yang kompetitif. Kepuasan pelanggan adalah prioritas utama kami.'
        ]
    ]
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
    <link rel="stylesheet" href="../style/contact.css">
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-droplets h-8 w-8 text-blue-600">
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
                        <li><a href="contact.php" class="nav-link active"><i class="ri-phone-line"></i> Contact Us</a></li>
                        <li><a href="guarantee.php" class="nav-link"><i class="ri-shield-line"></i> Guarantee Claim</a>
                        <li><a href="order.php" class="nav-link"><i class="ri-shopping-cart-2-line"></i> Order</a></li>
                        <li><a href="user.php" class="nav-link"><i class="ri-user-3-line"></i>Dashboard</a></li>
                    </ul>
                <?php else: ?>
                    <ul class="nav-list">
                        <li><a href="home.php" class="nav-link "><i class="ri-home-4-line"></i> Home</a></li>
                        <li><a href="about.php" class="nav-link"><i class="ri-information-2-line"></i>About Us</a></li>
                        <li><a href="contact.php" class="nav-link active"><i class="ri-phone-line"></i> Contact Us</a></li>
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
        <section class="contact-hero">
            <div class="container">
                <div class="hero-content">
                    <h1 class="hero-title">CONTACT US</h1>
                    <p class="hero-description">
                        Kami siap membantu Anda dengan pertanyaan, saran, atau dukungan teknis. Jangan
                        ragu untuk menghubungi tim profesional kami.
                    </p>
                </div>
            </div>
        </section>

        <!-- Contact Info Section -->
        <section class="contact-info-section">
            <div class="container">
                <div class="contact-info-grid">
                    <?php foreach ($contact_info as $info): ?>
                        <div class="contact-info-card animate-element">
                            <div class="info-icon" style="background: <?php echo $info['background']; ?>">
                                <i class="<?php echo $info['icon']; ?>"></i>
                            </div>
                            <h3 class="info-title"><?php echo $info['title']; ?></h3>
                            <p class="info-main"><?php echo $info['main']; ?></p>
                            <p class="info-sub"><?php echo $info['sub']; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Contact Form & Support Section -->
        <section class="contact-main-section">
            <div class="container">
                <div class="contact-main-content">
                    <!-- Contact Form -->
                    <div class="form-section animate-element">
                        <div class="form-header">
                            <h2 class="form-title">Kirim Pesan</h2>
                            <p class="form-subtitle">
                                <?php if ($user_logged_in): ?>
                                    <?php if (!$profile_complete): ?>
                                        <span style="color: #f59e0b;">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Anda harus melengkapi nomor telepon di <a href="user.php" style="color: #3b82f6; text-decoration: underline;">dashboard profil</a> terlebih dahulu untuk dapat mengirim pesan.
                                        </span>
                                    <?php else: ?>
                                        Lengkapi form di bawah ini dan kami akan segera menghubungi Anda
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #f59e0b;">
                                        <i class="fas fa-info-circle"></i>
                                        Anda harus <a href="login-register.php" style="color: #3b82f6; text-decoration: underline;">login</a> terlebih dahulu untuk mengirim pesan
                                    </span>
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="form-input">
                            <form class="contact-form" method="POST" action="../logic/contact/contact-controller.php">
                                <div class="form-group">
                                    <label for="subject">Subjek*</label>
                                    <input type="text" id="subject" name="subject" placeholder="Subjek pesan Anda"
                                        <?php echo (!$user_logged_in || !$profile_complete) ? 'disabled' : ''; ?> required>
                                </div>

                                <div class="form-group">
                                    <label for="message">Pesan*</label>
                                    <textarea id="message" name="message" rows="6" placeholder="Tulis pesan Anda di sini..."
                                        <?php echo (!$user_logged_in || !$profile_complete) ? 'disabled' : ''; ?> required></textarea>
                                </div>

                                <button type="submit" name="submit_contact" class="btn-submit"
                                    <?php echo (!$user_logged_in || !$profile_complete) ? 'disabled' : ''; ?>>
                                    <i class="fas fa-paper-plane"></i>
                                    <?php
                                    if (!$user_logged_in) {
                                        echo 'Login Dulu';
                                    } elseif (!$profile_complete) {
                                        echo 'Lengkapi Profil Dulu';
                                    } else {
                                        echo 'Kirim Pesan';
                                    }
                                    ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Support Section -->
                    <div class="support-section animate-element">
                        <div class="container-support">
                            <h2 class="support-title">Layanan Support</h2>

                            <div class="support-services">
                                <div class="support-item animate-child">
                                    <div class="support-icon">
                                        <i class="fas fa-comments"></i>
                                    </div>
                                    <div class="support-content">
                                        <h3>Live Chat</h3>
                                        <p>Chat langsung dengan tim support kami untuk bantuan cepat</p>
                                    </div>
                                </div>

                                <div class="support-item animate-child">
                                    <div class="support-icon">
                                        <i class="fas fa-headset"></i>
                                    </div>
                                    <div class="support-content">
                                        <h3>Phone Support</h3>
                                        <p>Hubungi hotline kami untuk konsultasi produk dan layanan</p>
                                    </div>
                                </div>

                                <div class="support-item animate-child">
                                    <div class="support-icon">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div class="support-content">
                                        <h3>Email Support</h3>
                                        <p>Kirim pertanyaan detail melalui email untuk respon lengkap</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Help Card -->
                        <div class="quick-help-card">
                            <h3>Butuh Bantuan Cepat?</h3>
                            <p>Tim customer service kami siap membantu Anda 24/7</p>
                            <a href="tel:+6281234567890" class="btn-help">
                                <i class="fas fa-phone"></i> Hubungi Sekarang
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Enhanced FAQ Section -->
        <section class="faq-main-section">
            <div class="container">
                <div class="faq-header">
                    <h2 class="faq-main-title">Pertanyaan yang Sering Diajukan</h2>
                    <p class="faq-subtitle">Temukan jawaban atas pertanyaan-pertanyaan umum tentang produk dan layanan HIDROSMART</p>
                </div>

                <div class="faq-grid">
                    <!-- Left Column -->
                    <div class="faq-column faq-left">
                        <?php foreach ($faqs['left'] as $index => $faq): ?>
                            <div class="faq-item" data-column="left" data-index="<?php echo $index; ?>">
                                <div class="faq-question-wrapper">
                                    <div class="faq-icon" style="background: <?php echo $faq['background']; ?>">
                                        <i class="<?php echo $faq['icon']; ?>"></i>
                                    </div>
                                    <h3 class="faq-question"><?php echo $faq['question']; ?></h3>
                                    <div class="faq-toggle">
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                </div>
                                <div class="faq-answer">
                                    <p><?php echo $faq['answer']; ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Right Column -->
                    <div class="faq-column faq-right">
                        <?php foreach ($faqs['right'] as $index => $faq): ?>
                            <div class="faq-item" data-column="right" data-index="<?php echo $index; ?>">
                                <div class="faq-question-wrapper">
                                    <div class="faq-icon" style="background: <?php echo $faq['background']; ?>">
                                        <i class="<?php echo $faq['icon']; ?>"></i>
                                    </div>
                                    <h3 class="faq-question"><?php echo $faq['question']; ?></h3>
                                    <div class="faq-toggle">
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                </div>
                                <div class="faq-answer">
                                    <p><?php echo $faq['answer']; ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
    <script>
        // Pass PHP data to JavaScript
        const userLoggedIn = <?php echo $user_logged_in ? 'true' : 'false'; ?>;
        const profileComplete = <?php echo $profile_complete ? 'true' : 'false'; ?>;
    </script>
    <script src="../logic/contact/contact.js"></script>
    <!-- Chatbot Widget -->
    <script src="../logic/chatbot/chatbot-widget.js"></script>

</body>

</html>