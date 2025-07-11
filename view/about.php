<?php
session_start();

// Include configuration
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
    'title' => 'About Us - HidroSmart',
    'description' => 'Mengenal lebih dekat HidroSmart dan visi kami untuk menghadirkan teknologi kesehatan yang mudah diakses',
    'current_page' => 'about'
];

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

// Data statistik pencapaian
$achievements = [
    [
        'number' => '10,000+',
        'label' => 'Pengguna Aktif',
        'color' => '#3b82f6'
    ],
    [
        'number' => '4.9★',
        'label' => 'Rating Kepuasan',
        'color' => '#3b82f6'
    ],
    [
        'number' => '24/7',
        'label' => 'Customer Support',
        'color' => '#3b82f6'
    ]
];

// Data tim
$team_members = [
    [
        'name' => 'Dr. Nadia Puji Saputri',
        'position' => 'Chief Health Officer',
        'description' => 'Ahli kesehatan dengan pengalaman 15+ tahun dalam teknologi medis',
        'avatar' => 'assets/images/team/sarah.jpg'
    ],
    [
        'name' => 'Daniel Dwi Putra Gunawan',
        'position' => 'Lead Engineer',
        'description' => 'Expert dalam IoT dan sensor technology dengan berbagai inovasi',
        'avatar' => 'assets/images/team/michael.jpg'
    ],
    [
        'name' => 'Muhammad Hilmi Rasyid Imaduddin',
        'position' => 'Product Manager',
        'description' => 'Berpengalaman dalam product development dan user experience',
        'avatar' => 'assets/images/team/lisa.jpg'
    ],
    [
        'name' => 'Muhammad Naufal Yazid Akbar',
        'position' => 'Data Scientist',
        'description' => 'Spesialis AI dan analitik data untuk solusi hidrasi yang cerdas',
        'avatar' => 'assets/images/team/yusuf.jpg'
    ]
];

// Data nilai-nilai perusahaan
$company_values = [
    [
        'icon' => 'fas fa-heart',
        'title' => 'Kesehatan Pertama',
        'description' => 'Kami percaya kesehatan adalah prioritas utama dalam hidup setiap orang',
        'color' => '#ef4444'
    ],
    [
        'icon' => 'fas fa-shield-alt',
        'title' => 'Kualitas Terjamin',
        'description' => 'Produk berkualitas tinggi dengan standar internasional dan garansi resmi',
        'color' => '#10b981'
    ],
    [
        'icon' => 'fas fa-users',
        'title' => 'Customer First',
        'description' => 'Kepuasan pelanggan adalah tujuan utama dalam setiap layanan yang kami berikan',
        'color' => '#8b5cf6'
    ],
    [
        'icon' => 'fas fa-bullseye',
        'title' => 'Inovasi Berkelanjutan',
        'description' => 'Terus berinovasi menghadirkan teknologi terdepan untuk kesehatan Anda',
        'color' => '#f59e0b'
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
    <link rel="stylesheet" href="../style/about.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Header Navigation -->
    <header class="header">
        <div class="container-navbar">
            <div class="nav-brand">
                <span class="brand-icon"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-droplets h-8 w-8 text-blue-600" data-lov-id="src/components/HomeComponents.tsx:25:12" data-lov-name="Droplets" data-component-path="src/components/HomeComponents.tsx" data-component-line="25" data-component-file="HomeComponents.tsx" data-component-name="Droplets" data-component-content="%7B%22className%22%3A%22h-8%20w-8%20text-blue-600%22%7D">
                        <path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path>
                        <path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path>
                    </svg></span>
                <span class="brand-text">HIDROSMART</span>
            </div>

            <nav class="nav-menu">
                <?php if (isset($_SESSION['username'])): ?>
                    <ul class="nav-list">
                        <li><a href="home.php" class="nav-link"><i class="ri-home-4-line"></i> Home</a></li>
                        <li><a href="about.php" class="nav-link active"><i class="ri-information-2-line"></i>About Us</a></li>
                        <li><a href="contact.php" class="nav-link"><i class="ri-phone-line"></i> Contact Us</a></li>
                        <li><a href="guarantee.php" class="nav-link"><i class="ri-shield-line"></i> Guarantee Claim</a>
                        <li><a href="order.php" class="nav-link"><i class="ri-shopping-cart-2-line"></i> Order</a></li>
                        <li><a href="user.php" class="nav-link"><i class="ri-user-3-line"></i>Dashboard</a></li>
                    </ul>
                <?php else: ?>
                    <ul class="nav-list">
                        <li><a href="home.php" class="nav-link "><i class="ri-home-4-line"></i> Home</a></li>
                        <li><a href="about.php" class="nav-link active"><i class="ri-information-2-line"></i>About Us</a></li>
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
        <section class="about-hero">
            <div class="container">
                <div class="hero-content">
                    <h1 class="hero-title">ABOUT US</h1>
                    <p class="hero-description">
                        HidroSmart lahir dari visi untuk menghadirkan teknologi kesehatan yang mudah
                        diakses dan dapat diandalkan oleh setiap orang dalam kehidupan sehari-hari.
                    </p>
                </div>
            </div>
        </section>

        <!-- Our Story Section -->
        <section class="our-story-section">
            <div class="container">
                <div class="story-content">
                    <div class="story-left">
                        <h2 class="section-title">Cerita Kami</h2>
                        <div class="story-text">
                            <p>
                                Berawal dari keprihatian akan minimnya kesadaran masyarakat terhadap
                                pentingnya hidrasi yang cukup, kami mengembangkan HidroSmart sebagai solusi
                                inovatif yang menggabungkan teknologi sensor canggih dengan kemudahan
                                penggunaan.
                            </p>
                            <p>
                                Dengan dukungan tim ahli kesehatan dan teknologi, kami berkomitmen
                                menghadirkan produk yang tidak hanya canggih, tetapi juga mudah digunakan
                                oleh siapa saja, kapan saja, dan di mana saja.
                            </p>
                        </div>
                        <a href="contact.php" class="btn btn-primary">
                            <i class="fas fa-phone"></i> Hubungi Kami
                        </a>
                    </div>

                    <div class="story-right">
                        <div class="innovation-card">
                            <div class="innovation-icon">
                                <span class="brand-icon"><svg xmlns="http://www.w3.org/2000/svg" width="150" height="150" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-droplets h-8 w-8 text-blue-600" data-lov-id="src/components/HomeComponents.tsx:25:12" data-lov-name="Droplets" data-component-path="src/components/HomeComponents.tsx" data-component-line="25" data-component-file="HomeComponents.tsx" data-component-name="Droplets" data-component-content="%7B%22className%22%3A%22h-8%20w-8%20text-blue-600%22%7D">
                                        <path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path>
                                        <path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path>
                                    </svg></span>
                            </div>
                            <div class="innovation-content">
                                <h3>HidroSmart Innovation</h3>
                                <p>Technology for Better Health</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Company Values Section -->
        <section class="values-section">
            <div class="container">
                <div class="values-header">
                    <h2 class="section-title">Nilai-Nilai Kami</h2>
                    <p class="section-subtitle">Prinsip yang memandu setiap langkah perjalanan HidroSmart</p>
                </div>

                <div class="values-grid">
                    <?php foreach ($company_values as $value): ?>
                        <div class="value-card">
                            <div class="value-icon" style="background-color: <?php echo $value['color']; ?>">
                                <i class="<?php echo $value['icon']; ?>"></i>
                            </div>
                            <h3 class="value-title"><?php echo $value['title']; ?></h3>
                            <p class="value-description"><?php echo $value['description']; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Team Section -->
        <section class="team-section">
            <div class="container">
                <div class="team-header">
                    <h2 class="section-title">Tim Kami</h2>
                    <p class="section-subtitle">Ahli terbaik yang berdedikasi untuk kesehatan Anda</p>
                </div>

                <div class="team-grid">
                    <?php foreach ($team_members as $member): ?>
                        <div class="container-team">
                            <div class="team-card">
                                <div class="team-avatar">
                                    <div class="avatar-placeholder">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users h-10 w-10 text-white" data-lov-id="src/pages/About.tsx:187:20" data-lov-name="Users" data-component-path="src/pages/About.tsx" data-component-line="187" data-component-file="About.tsx" data-component-name="Users" data-component-content="%7B%22className%22%3A%22h-10%20w-10%20text-white%22%7D">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="9" cy="7" r="4"></circle>
                                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="team-info">
                                    <h3 class="team-name"><?php echo $member['name']; ?></h3>
                                    <p class="team-position"><?php echo $member['position']; ?></p>
                                    <p class="team-description"><?php echo $member['description']; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <div class="cta-content">
                    <h2 class="cta-title">Siap Memulai Hidup Sehat Bersama Kami?</h2>
                    <p class="cta-description">
                        Bergabunglah dengan ribuan pengguna yang telah merasakan manfaat HidroSmart
                    </p>

                    <div class="cta-buttons">
                        <a href="order.php" class="btn-cta btn-shooping">
                            <i class="fas fa-shopping-cart"></i> Pesan Sekarang
                        </a>
                        <a href="contact.php" class="btn btn-secondary">
                            <i class="fas fa-info-circle"></i> Pelajari Lebih Lanjut
                        </a>
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
    <script src="../logic/about/about.js"></script>
    <!-- Chatbot Widget -->
    <script src="../logic/chatbot/chatbot-widget.js"></script>
</body>

</html>