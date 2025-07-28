<?php
session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    $login_required = urlencode('Harus login terlebih dahulu');
    header("Location: login-register.php?tab=login&login_required=$login_required");
    exit();
}

require_once '../logic/login-register/database.php';
require_once '../logic/user/user-controller.php';

// Include configuration
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

$dashboard = new DashboardController($pdo);


// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'submit_review':
                $result = $dashboard->submitReview(
                    $_POST['order_id'],
                    (int)$_POST['rating'],
                    trim($_POST['review_text'])
                );
                echo json_encode($result);
                break;

            case 'submit_warranty':
                $order_id = $_POST['order_id'];
                echo json_encode(['success' => true, 'redirect' => "guarantee.php?order_id=$order_id"]);
                break;

            case 'update_profile':
                $result = $dashboard->updateProfile(
                    trim($_POST['name']),
                    trim($_POST['email']),
                    trim($_POST['phone']),
                    trim($_POST['alamat']),
                    $_POST['current_password'] ?? '',
                    $_POST['new_password'] ?? ''
                );
                echo json_encode($result);
                break;

            case 'upload_avatar':
                $result = $dashboard->uploadAvatar($_FILES['avatar']);
                echo json_encode($result);
                break;

            case 'get_order_tracking':
                $tracking_data = $dashboard->getOrderTracking($_POST['order_id']);
                echo json_encode([
                    'success' => true,
                    'tracking' => $tracking_data['tracking'] ?? [],
                    'payment_method' => $tracking_data['payment_method'] ?? 'cod'
                ]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Get dashboard data
$stats = $dashboard->getDashboardStats();
$orders = $dashboard->getUserOrders();
$reviews = $dashboard->getUserReviews();
$reviewable_orders = $dashboard->getReviewableOrders();
$warranty_claims = $dashboard->getWarrantyClaims();
$user_profile = $dashboard->getUserProfile();
$insights = $dashboard->getDashboardInsights();
$contact_messages = $dashboard->getUserContactMessages();


// Check for notifications
$notification = $_SESSION['notification'] ?? null;
if ($notification) {
    unset($_SESSION['notification']);
}

// Helper function to get payment method display info
function getPaymentMethodInfo($method)
{
    $methods = [
        'cod' => [
            'name' => 'Cash on Delivery (COD)',
            'icon' => 'fas fa-money-bill-wave',
            'color' => '#10b981',
            'bg_color' => '#d1fae5'
        ],
        'bank_transfer' => [
            'name' => 'Transfer Bank',
            'icon' => 'fas fa-university',
            'color' => '#3b82f6',
            'bg_color' => '#dbeafe'
        ],
        'ewallet' => [
            'name' => 'E-Wallet',
            'icon' => 'fas fa-mobile-alt',
            'color' => '#8b5cf6',
            'bg_color' => '#ede9fe'
        ]
    ];

    return $methods[$method] ?? $methods['cod'];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - HidroSmart</title>
    <meta name="description" content="Dashboard pengguna HidroSmart untuk mengelola pesanan, ulasan, dan profil">

    <!-- CSS -->
    <link rel="stylesheet" href="../style/user.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightbox2@2/dist/css/lightbox.min.css" />
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-droplets h-8 w-8 text-blue-600">
                        <path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path>
                        <path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path>
                    </svg>
                </span>
                <span class="brand-text">HIDROSMART</span>
            </div>

            <nav class="nav-menu">
                <ul class="nav-list">
                    <li><a href="home.php" class="nav-link"><i class="ri-home-4-line"></i> Home</a></li>
                    <li><a href="about.php" class="nav-link"><i class="ri-information-2-line"></i>About Us</a></li>
                    <li><a href="contact.php" class="nav-link "><i class="ri-phone-line"></i> Contact Us</a></li>
                    <li><a href="guarantee.php" class="nav-link"><i class="ri-shield-line"></i> Guarantee Claim</a>
                    <li><a href="order.php" class="nav-link"><i class="ri-shopping-cart-2-line"></i> Order</a></li>
                    <li><a href="user.php" class="nav-link active"><i class="ri-user-3-line"></i>Dashboard</a></li>
                </ul>
            </nav>

            <div class="user-section">
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
            </div>
        </div>
    </header>

    <!-- Main Dashboard -->
    <main class="dashboard-main">
        <div class="container">
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <h1>Dashboard</h1>
                <p>Selamat datang, <?= htmlspecialchars($_SESSION['username']) ?>!</p>
            </div>

            <!-- Insights Cards -->
            <?php if (!empty($insights)): ?>
                <div class="insights-section">
                    <h3>Rekomendasi untuk Anda</h3>
                    <div class="insights-grid">
                        <?php foreach ($insights as $insight): ?>
                            <div class="insight-card insight-<?= $insight['type'] ?>">
                                <div class="insight-content">
                                    <h4><?= htmlspecialchars($insight['title']) ?></h4>
                                    <p><?= htmlspecialchars($insight['message']) ?></p>
                                </div>
                                <button class="insight-action" onclick="switchToTab('<?= $insight['action_tab'] ?>')">
                                    <?= htmlspecialchars($insight['action']) ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card stat-card-1">
                    <div class="stat-content">
                        <div class="stat-label">Total Pesanan</div>
                        <div class="stat-number"><?= $stats['total_orders'] ?></div>
                    </div>
                    <div class="stat-icon-1">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>

                <div class="stat-card stat-card-2">
                    <div class="stat-content">
                        <div class="stat-label">Pesanan Selesai</div>
                        <div class="stat-number"><?= $stats['completed_orders'] ?></div>
                    </div>
                    <div class="stat-icon-2">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>

                <div class="stat-card stat-card-3">
                    <div class="stat-content">
                        <div class="stat-label">Pesanan Pending</div>
                        <div class="stat-number"><?= $stats['pending_orders'] ?></div>
                    </div>
                    <div class="stat-icon-3">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>

                <div class="stat-card stat-card-4">
                    <div class="stat-content">
                        <div class="stat-label">Total Belanja</div>
                        <div class="stat-number">Rp <?= number_format($stats['total_spent'], 0, ',', '.') ?></div>
                    </div>
                    <div class="stat-icon-4">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="dashboard-tabs">
                <button class="tab-btn active" data-tab="status-pesanan">
                    <i class="fas fa-truck"></i>
                    Status Pesanan
                </button>
                <button class="tab-btn" data-tab="ulasan">
                    <i class="fas fa-star"></i>
                    Ulasan
                </button>
                <button class="tab-btn" data-tab="garansi">
                    <i class="fas fa-shield-alt"></i>
                    Garansi
                </button>
                <button class="tab-btn" data-tab="suggestion">
                    <i class="fas fa-comments"></i>
                    Pesan Contact
                </button>
                <button class="tab-btn" data-tab="profil">
                    <i class="fas fa-user"></i>
                    Profil
                </button>
            </div>

            <!-- Tab Contents -->
            <div class="tab-contents">
                <!-- Status Pesanan Tab -->
                <div id="status-pesanan" class="tab-content active">
                    <h2>Riwayat Pesanan</h2>

                    <?php if (!empty($orders)): ?>
                        <div class="orders-container">
                            <?php foreach ($orders as $order): ?>
                                <div class="order-card">
                                    <div class="order-header">
                                        <div class="order-id">
                                            <strong>Order #<?= htmlspecialchars($order['id_order']) ?></strong>
                                            <span class="order-date"><?= date('d/m/Y H:i:s', strtotime($order['tanggal_transaksi'])) ?></span>
                                        </div>
                                        <div class="order-status status-<?= str_replace(' ', '-', strtolower($order['status'])) ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </div>
                                    </div>

                                    <div class="order-details">
                                        <div class="product-info">
                                            <div class="product-image-container">
                                                <?php
                                                $color_images = [
                                                    'black' => '../images/hidrosmart-black.jpg',
                                                    'white' => '../images/hidrosmart-white.jpg',
                                                    'blue' => '../images/hidrosmart-blue.jpg',
                                                    'gray' => '../images/hidrosmart-gray.jpg'
                                                ];
                                                $image_src = $color_images[$order['color']] ?? '/placeholder.svg?height=80&width=80';
                                                ?>
                                                <img src="<?= $image_src ?>" alt="HidroSmart Tumbler" class="product-image">
                                            </div>
                                            <div class="product-details">
                                                <h4>HidroSmart Tumbler</h4>
                                                <p>Warna: <?= ucfirst($order['color']) ?> | Qty: <?= $order['kuantitas'] ?></p>
                                                <span class="order-total">Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></span>

                                                <!-- Enhanced Payment Method Display -->
                                                <?php $payment_info = getPaymentMethodInfo($order['metode_pembayaran']); ?>
                                                <div class="payment-method-display" style="background-color: <?= $payment_info['bg_color'] ?>;">
                                                    <i class="<?= $payment_info['icon'] ?>" style="color: <?= $payment_info['color'] ?>; font-size: 1rem;"></i>
                                                    <span style="color: <?= $payment_info['color'] ?>; font-weight: 500; font-size: 0.875rem;">
                                                        <?= $payment_info['name'] ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="order-actions">
                                            <button class="btn btn-secondary" onclick="showOrderTracking('<?= $order['id_order'] ?>')">
                                                <i class="fas fa-truck"></i>
                                                Lacak Pesanan
                                            </button>

                                            <!-- Show Payment Proof Button for Bank Transfer and E-wallet -->
                                            <?php if (in_array($order['metode_pembayaran'], ['bank_transfer', 'ewallet']) && !empty($order['bukti_transfer'])): ?>
                                                <button class="btn btn-info" onclick="showPaymentProof('<?= $order['id_order'] ?>', '<?= $order['bukti_transfer'] ?>')">
                                                    <i class="fas fa-receipt"></i>
                                                    Lihat Bukti
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($order['status'] === 'Diterima Customer'): ?>
                                                <?php if (!in_array($order['id_order'], array_column($reviews, 'order_id'))): ?>
                                                    <button class="btn btn-primary" onclick="openReviewModal('<?= $order['id_order'] ?>')">
                                                        <i class="fas fa-star"></i>
                                                        Beri Ulasan
                                                    </button>
                                                <?php endif; ?>

                                                <?php if (!in_array($order['id_order'], array_column($warranty_claims, 'id_order'))): ?>
                                                    <a href="guarantee.php?order_id=<?= $order['id_order'] ?>" class="btn btn-warning">
                                                        <i class="fas fa-shield-alt"></i>
                                                        Klaim Garansi
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-cart"></i>
                            <p>Belum ada pesanan</p>
                            <a href="order.php" class="btn-primary-pesanan">
                                <i class="fa-solid fa-plus"></i>
                                <span>Buat Pesanan Pertama</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Ulasan Tab -->
                <div id="ulasan" class="tab-content">
                    <h2>Ulasan Saya</h2>

                    <!-- Pending Reviews -->
                    <?php if (!empty($reviewable_orders)): ?>
                        <div class="pending-reviews">
                            <h3>Pesanan yang Belum Diulas</h3>
                            <?php foreach ($reviewable_orders as $order): ?>
                                <div class="review-pending-card">
                                    <div class="product-info">
                                        <?php
                                        $image_src = $color_images[$order['color']] ?? '/placeholder.svg?height=60&width=60';
                                        ?>
                                        <img src="<?= $image_src ?>" alt="HidroSmart Tumbler" class="product-image">
                                        <div class="product-details">
                                            <h4>HidroSmart Tumbler</h4>
                                            <p>Order #<?= $order['id_order'] ?> | <?= ucfirst($order['color']) ?></p>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary" onclick="openReviewModal('<?= $order['id_order'] ?>')">
                                        <i class="fas fa-star"></i>
                                        Beri Ulasan
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Review History -->
                    <?php if (!empty($reviews)): ?>
                        <div class="reviews-history">
                            <h3>Riwayat Ulasan</h3>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-card">
                                    <div class="review-header">
                                        <div class="product-info">
                                            <?php
                                            $image_src = $color_images[$review['color']] ?? '/placeholder.svg?height=60&width=60';
                                            ?>
                                            <img src="<?= $image_src ?>" alt="HidroSmart Tumbler" class="product-image">
                                            <div class="product-details">
                                                <h4>HidroSmart Tumbler</h4>
                                                <p>Order #<?= $review['order_id'] ?></p>
                                            </div>
                                        </div>
                                        <div class="review-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="star <?= $i <= $review['rating'] ? 'filled' : '' ?>">★</span>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="review-content">
                                        <p><?= htmlspecialchars($review['review_text']) ?></p>
                                        <small><?= date('d/m/Y H:i', strtotime($review['created_at'])) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-star"></i>
                            <p>Belum ada ulasan</p>
                            <small>Buat ulasan setelah menerima produk</small>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Garansi Tab -->
                <div id="garansi" class="tab-content">
                    <h2>Riwayat Garansi</h2>

                    <?php if (!empty($warranty_claims)): ?>
                        <div class="warranty-container">
                            <?php foreach ($warranty_claims as $claim): ?>
                                <div class="warranty-card">
                                    <div class="warranty-header">
                                        <div class="warranty-product-info">
                                            <div class="warranty-product-image">
                                                <?php
                                                $damage_image_path = '../logic/guarantee/bukti_kerusakan/' . $claim['bukti_gambar'];
                                                if (!empty($claim['bukti_gambar']) && file_exists($damage_image_path)) {
                                                    $image_src = $damage_image_path;
                                                    $image_alt = 'Bukti Kerusakan';
                                                } else {
                                                    $image_src = $color_images[$claim['color']] ?? '/placeholder.svg?height=100%&width=100%';
                                                    $image_alt = 'HidroSmart Tumbler';
                                                }
                                                ?>
                                                <a href="<?= $image_src ?>" data-lightbox="warranty-claim-<?= $claim['id_order'] ?>" data-title="Klaim Garansi - Order #<?= $claim['id_order'] ?>">
                                                    <img src="<?= $image_src ?>" alt="<?= $image_alt ?>" class="product-image">
                                                </a>
                                            </div>
                                            <div class="warranty-product-details">
                                                <h4>Klaim Garansi - Order #<?= $claim['id_order'] ?></h4>
                                                <p><strong>Produk:</strong> HidroSmart Tumbler (<?= ucfirst($claim['color']) ?>)</p>
                                            </div>
                                        </div>
                                        <span class="warranty-status status-<?= $claim['status_klaim'] ?>">
                                            <?= ucfirst($claim['status_klaim']) ?>
                                        </span>
                                    </div>
                                    <div class="warranty-details">
                                        <p><strong>Deskripsi:</strong> <?= htmlspecialchars($claim['deskripsi']) ?></p>
                                        <p><strong>Tanggal Klaim:</strong> <?= date('d/m/Y H:i', strtotime($claim['tanggal_klaim'])) ?></p>
                                        <?php if (!empty($claim['catatan_admin'])): ?>
                                            <p><strong>Catatan Admin:</strong> <?= htmlspecialchars($claim['catatan_admin']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-shield-alt"></i>
                            <p>Belum ada klaim garansi</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- NEW: Suggestion Tab for Contact Messages -->
                <div id="suggestion" class="tab-content">
                    <h2>Pesan Contact Saya</h2>
                    <p style="color: var(--color-gray-600); margin-bottom: 1.5rem; font-size: 0.875rem;">
                        Lihat status pesan yang telah Anda kirim melalui halaman Contact Us
                    </p>

                    <?php if (!empty($contact_messages)): ?>
                        <div class="contact-messages-container">
                            <?php foreach ($contact_messages as $message): ?>
                                <div class="contact-message-card <?= $message['read_status'] === 'read' ? 'message-read' : 'message-unread' ?>">
                                    <div class="message-header">
                                        <div class="message-info">
                                            <!-- <div class="message-avatar">
                                                <?= strtoupper(substr($user_profile['name'], 0, 1)) ?>
                                            </div> -->
                                            <div class="message-details">
                                                <h4><?= htmlspecialchars($message['subject']) ?></h4>
                                                <div class="message-meta">
                                                    <span class="message-date">
                                                        <i class="fas fa-calendar-alt"></i>
                                                        <?= date('d/m/Y H:i', strtotime($message['tanggal_submit'])) ?>
                                                    </span>
                                                    <span class="message-subject">
                                                        <i class="fas fa-envelope"></i>
                                                        Pesan Contact
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="message-status">
                                            <?php if ($message['read_status'] === 'read'): ?>
                                                <span class="status-badge status-read">
                                                    <i class="fas fa-check-double"></i>
                                                    READ
                                                </span>
                                                <div class="read-timestamp">
                                                    Dibaca: <?= date('d/m/Y H:i', strtotime($message['read_at'])) ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="status-badge status-unread">
                                                    <i class="fas fa-clock"></i>
                                                    UNREAD
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="message-content">
                                        <p><?= htmlspecialchars($message['pesan']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-comments"></i>
                            <p>Belum ada pesan contact</p>
                            <a href="contact.php" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-plus"></i>
                                Kirim Pesan Pertama
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Profil Tab -->
                <div id="profil" class="tab-content">
                    <div class="profile-header">
                        <h2>Profil Saya</h2>
                        <p>Kelola informasi profil Anda untuk mengontrol, melindungi dan mengamankan akun</p>
                    </div>


                    <div class="profile-container">
                        <!-- Informasi Profil -->
                        <div class="profile-card">
                            <div class="profile-section">
                            <div class="profile-avatar-section">
                                <div class="profile-avatar-large">
                                    <?php if (!empty($user_profile['avatar'])): ?>
                                        <img src="../logic/user/avatars/<?= htmlspecialchars($user_profile['avatar']) ?>" alt="Avatar" class="avatar-img-large">
                                    <?php else: ?>
                                        <i class="fas fa-user-circle"></i>
                                    <?php endif; ?>
                                    <div class="avatar-upload-overlay">
                                        <i class="fas fa-camera"></i>
                                        <span>Ubah Foto</span>
                                    </div>
                                </div>
                                <input type="file" id="avatarInput" accept="image/*" style="display: none;">
                                <div class="profile-details">
                                    <h3><?= htmlspecialchars($user_profile['name']) ?></h3>
                                    <p><?= htmlspecialchars($user_profile['email']) ?></p>
                                    <span class="member-badge">Member HidroSmart</span>
                                </div>
                            </div>

                            <div class="profile-form">
                                <h3 class="form-section-title">Informasi Pribadi</h3>
                                <form id="profileForm">
                                        <input type="hidden" name="email" value="<?= htmlspecialchars($user_profile['email']) ?>">
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="profile_name">Nama Lengkap</label>
                                            <input type="text" id="profile_name" name="name" value="<?= htmlspecialchars($user_profile['name']) ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="profile_phone">Nomor Telepon</label>
                                            <div class="input-with-prefix">
                                                <span class="input-prefix">+62</span>
                                                <input type="tel" id="profile_phone" name="phone" 
                                                       value="<?= !empty($user_profile['phone']) ? htmlspecialchars(preg_replace('/^\+62/', '', $user_profile['phone'])) : '' ?>" 
                                                       placeholder="8123456789" maxlength="15" inputmode="numeric"  oninput="this.value=this.value.replace(/\D/g,'').slice(0,15)">
                                            </div>
                                            <small class="form-help">Contoh: 8123456789 (9-13 digit)</small>
                                        </div>

                                        <div class="form-group full-width">
                                            <label for="profile_alamat">Alamat Lengkap</label>
                                            <textarea id="profile_alamat" name="alamat" rows="3" maxlength="500" 
                                                      placeholder="Masukkan alamat lengkap Anda"><?= htmlspecialchars($user_profile['alamat'] ?? '') ?></textarea>
                                            <small class="form-help">Maksimal 500 karakter</small>
                                        </div>

                                        <div class="form-actions">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i>
                                                Simpan Perubahan
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            </div>
                        </div>

                        <!-- Keamanan Akun -->
                        <div class="security-card">
                            <h3 class="form-section-title">Keamanan Akun</h3>
                            
                            <!-- Ubah Password -->
                            <div class="security-section">
                                <div class="security-info">
                                    <i class="fas fa-lock"></i>
                                    <div>
                                        <h4>Password</h4>
                                        <p>Terakhir diubah: <?= !empty($user_profile['password_updated_at']) ? date('d M Y', strtotime($user_profile['password_updated_at'])) : 'Belum pernah diubah' ?></p>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline" id="changePasswordBtn">
                                    <i class="fas fa-pencil-alt"></i> Ubah
                                </button>
                            </div>

                            <!-- Ubah Email -->
                            <div class="security-section">
                                <div class="security-info">
                                    <i class="fas fa-envelope"></i>
                                    <div>
                                        <h4>Alamat Email</h4>
                                        <p><?= htmlspecialchars($user_profile['email']) ?></p>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline" id="changeEmailBtn">
                                    <i class="fas fa-pencil-alt"></i> Ubah
                                </button>
                            </div>
                        </div>
                    </div>

                    </div>

                    <!-- Modal Ubah Password -->
                    <div id="passwordModal" class="modal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3>Ubah Password</h3>
                                <span class="modal-close" onclick="closeModal('passwordModal')">&times;</span>
                            </div>
                            <div class="modal-body">
                                <form id="passwordForm" class="modal-form">
                                    <div id="passwordNotification" style="display: none;" class="notification">
                                        <i class="fas"></i>
                                        <span></span>
                                    </div>
                                    <div class="form-group">
                                        <label for="current_password">Password Saat Ini</label>
                                        <div class="password-input">
                                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                                            <i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility(this)"></i>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="new_password">Password Baru</label>
                                        <div class="password-input">
                                            <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6">
                                            <i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility(this)"></i>
                                        </div>
                                        <small class="form-help">Minimal 6 karakter, gunakan kombinasi huruf dan angka</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="confirm_password">Konfirmasi Password Baru</label>
                                        <div class="password-input">
                                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6">
                                            <i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility(this)"></i>
                                        </div>
                                    </div>
                                    <div class="form-actions">
                                        <button type="button" class="btn btn-outline" onclick="closeModal('passwordModal')">Batal</button>
                                        <button type="submit" class="btn btn-primary">Simpan Password</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Ubah Email -->
                    <div id="emailModal" class="modal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3>Ubah Alamat Email</h3>
                                <span class="close" onclick="closeModal('emailModal')">&times;</span>
                            </div>
                            <div class="modal-body">
                                <?php if (isset($_SESSION['error'])): ?>
                                    <div class="notification error">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <span><?= htmlspecialchars($_SESSION['error']) ?></span>
                                    </div>
                                    <?php unset($_SESSION['error']); ?>
                                <?php endif; ?>
                                <?php if (isset($_SESSION['success'])): ?>
                                    <div class="notification success">
                                        <i class="fas fa-check-circle"></i>
                                        <span><?= htmlspecialchars($_SESSION['success']) ?></span>
                                    </div>
                                    <?php unset($_SESSION['success']); ?>
                                <?php endif; ?>
                                <form action="../logic/user/update_email.php" method="POST" id="changeEmailForm">
                                    <div class="form-group">
                                        <label for="new_email">Email Baru</label>
                                        <input type="email" id="new_email" name="email" required 
                                               pattern="[a-z0-9._%+-]+@gmail\.com$" 
                                               title="Hanya email @gmail.com yang diperbolehkan"
                                               placeholder="contoh@gmail.com">
                                        <small class="form-help">Hanya email @gmail.com yang diperbolehkan</small>
                                    </div>
                                    <div class="form-actions">
                                        <button type="button" class="btn btn-outline" onclick="closeModal('emailModal')">Batal</button>
                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Enhanced Review Modal -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Beri Ulasan Produk</h3>
                <span class="close" onclick="closeModal('reviewModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div class="product-preview">
                    <img src="../images/hidrosmart-blue.jpg" alt="HidroSmart Tumbler" class="product-preview-img">
                    <div class="product-preview-info">
                        <h4>HidroSmart Tumbler</h4>
                        <p>Bagikan pengalaman Anda dengan produk ini</p>
                    </div>
                </div>
                <form id="reviewForm">
                    <input type="hidden" id="review_order_id" name="order_id">
                    <div class="form-group">
                        <label>Rating Produk</label>
                        <div class="rating-input" id="reviewRating">
                            <span class="star" data-rating="1">★</span>
                            <span class="star" data-rating="2">★</span>
                            <span class="star" data-rating="3">★</span>
                            <span class="star" data-rating="4">★</span>
                            <span class="star" data-rating="5">★</span>
                        </div>
                        <input type="hidden" id="rating" name="rating" required>
                        <small class="rating-text">Pilih rating Anda</small>
                    </div>
                    <div class="form-group">
                        <label for="review_text">Ulasan Anda</label>
                        <textarea id="review_text" name="review_text" rows="4" placeholder="Ceritakan pengalaman Anda menggunakan HidroSmart Tumbler..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="fas fa-star"></i>
                        Kirim Ulasan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Compact Order Tracking Modal -->
    <div id="trackingModal" class="modal">
        <div class="modal-content modal-compact">
            <div class="modal-header">
                <h3>Lacak Pesanan</h3>
                <span class="close" onclick="closeModal('trackingModal')">&times;</span>
            </div>
            <div id="trackingContent" class="modal-body">
                <!-- Tracking timeline will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Memproses...</p>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../logic/user/user.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lightbox2@2/dist/js/lightbox-plus-jquery.min.js"></script>

    <script>
        // Show notification if present
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($notification)): ?>
                showNotification('<?php echo addslashes($notification['message']); ?>', '<?php echo $notification['type']; ?>');
            <?php endif; ?>

            // Validasi form ganti email
            const changeEmailForm = document.getElementById('changeEmailForm');
            if (changeEmailForm) {
                changeEmailForm.addEventListener('submit', function(e) {
                    const emailInput = document.getElementById('new_email');
                    const email = emailInput.value.trim();
                    
                    // Validasi format email Google
                    const emailPattern = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
                    if (!emailPattern.test(email)) {
                        e.preventDefault();
                        showNotification('Hanya email @gmail.com yang diperbolehkan', 'error');
                        emailInput.focus();
                        return false;
                    }
                    
                    // Konfirmasi sebelum mengubah email
                    if (!confirm('Apakah Anda yakin ingin mengganti email?')) {
                        e.preventDefault();
                        return false;
                    }
                    
                    return true;
                });
            }
        });

        // Global function to switch tabs (called from insights)
        function switchToTab(tabId) {
            if (window.dashboardManager) {
                window.dashboardManager.switchTab(tabId);
            }
        }
    </script>
</body>

</html>