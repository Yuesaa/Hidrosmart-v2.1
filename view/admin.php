<?php
session_start();

// Include security system
require_once '../logic/security/admin-session.php';
require_once '../logic/login-register/database.php';

// Initialize security
$security = new AdminSecurity($pdo);

// Validate admin session and prevent user access
if (!$security->validateAdminSession()) {
    exit();
}
$security->preventUserAccess();

// Include controller
require_once '../logic/admin/admin-controller.php';

// Initialize controller
$admin = new AdminController($pdo);

// Handle AJAX requests
$admin->handleAjaxRequest();

// Get data untuk display
$admin_data = $admin->getAdminData();
$stats = $admin->getDashboardStats();
$recent_activity = $admin->getRecentActivity();
$users = $admin->getAllUsers();
$orders = $admin->getAllOrders();
$guarantees = $admin->getGuaranteeClaims();
$messages = $admin->getContactMessages();
$reviews = $admin->getProductReviews();

// Get admin name from regular session
$admin_name = $_SESSION['username'];

// Get current page
$current_page = $_GET['page'] ?? 'dashboard';

// ================= Notifications =================
$notifications = $admin->getUnreadNotifications();
$unread_count = count($notifications);

// Define color images array for use throughout the page
$color_images = [
    'black' => '../images/hidrosmart-black.jpg',
    'white' => '../images/hidrosmart-white.jpg',
    'blue' => '../images/hidrosmart-blue.jpg',
    'gray' => '../images/hidrosmart-gray.jpg'
];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HidroSmart</title>
    <link rel="stylesheet" href="../style/admin.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightbox2@2/dist/css/lightbox.min.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Header Navigation -->
    <header class="header">
        <div class="container-navbar">
            <div class="nav-brand">
                <span class="brand-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path>
                        <path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path>
                    </svg>
                </span>
                <span class="brand-text">HIDROSMART</span>
            </div>

            <nav class="nav-menu">
                <ul class="nav-list">
                    <li><a href="?page=dashboard" class="nav-link <?= $current_page === 'dashboard' ? 'active' : '' ?>"><i class="ri-dashboard-3-line"></i> Dashboard</a></li>
                    <li><a href="?page=users" class="nav-link <?= $current_page === 'users' ? 'active' : '' ?>"><i class="ri-user-community-line"></i>Users</a></li>
                    <li><a href="?page=orders" class="nav-link <?= $current_page === 'orders' ? 'active' : '' ?>"><i class="ri-shopping-bag-4-line"></i>Orders</a></li>
                    <li><a href="?page=reviews" class="nav-link <?= $current_page === 'reviews' ? 'active' : '' ?>"><i class="ri-star-line"></i>Reviews</a></li>
                    <li><a href="?page=guarantees" class="nav-link <?= $current_page === 'guarantees' ? 'active' : '' ?>"><i class="ri-shield-line"></i>Guarantees</a></li>
                    <li><a href="?page=suggestions" class="nav-link <?= $current_page === 'suggestions' ? 'active' : '' ?>"><i class="ri-chat-3-line"></i>Suggestions</a></li>
                </ul>
            </nav>

            <div class="user-section">
                <span class="user-greeting">Halo, <?= htmlspecialchars($admin_name) ?> (Admin)</span>
                <a href="../logic/login-register/logout.php" class="nav-btn">Logout</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <h1 class="hero-title">
                    <span class="hashtag"># HIDROSMART</span><br>
                    <span class="main-text">ADMIN DASHBOARD</span>
                </h1>
                <p class="hero-description">Kelola sistem HidroSmart dengan mudah dan efisien</p>
            </div>
        </section>

        <div class="container">
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card stat-card-1">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Orders Hari Ini</div>
                            <div class="stat-number"><?= $stats['today_orders'] ?></div>
                        </div>
                    </div>
                </div>

                
                <div class="stat-card stat-card-2">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Total Revenue</div>
                            <div class="stat-number">Rp <?= number_format($stats['total_revenue'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>

                <div class="stat-card stat-card-3">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Active Users</div>
                            <div class="stat-number"><?= $stats['active_users'] ?></div>
                        </div>
                    </div>
                </div>

                <div class="stat-card stat-card-4">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Pending Orders</div>
                            <div class="stat-number"><?= $stats['pending_orders'] ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Secondary Stats -->
            <div class="secondary-stats">
                <div class="stat-item">
                    <div class="stat-icon-secondary primary">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <div class="stat-info-secondary">
                        <div class="stat-label-secondary">Total Users</div>
                        <div class="stat-value-secondary"><?= $stats['total_users'] ?></div>
                    </div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon-secondary success">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-info-secondary">
                        <div class="stat-label-secondary">Total Orders</div>
                        <div class="stat-value-secondary"><?= $stats['total_orders'] ?></div>
                    </div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon-secondary warning">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="stat-info-secondary">
                        <div class="stat-label-secondary">Guarantee Claims</div>
                        <div class="stat-value-secondary"><?= $stats['guarantee_claims'] ?></div>
                    </div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon-secondary info">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-info-secondary">
                        <div class="stat-label-secondary">Unread Message</div>
                        <div class="stat-value-secondary"><?= $stats['unread_messages'] ?></div>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <div class="page-content">
                <?php if ($current_page === 'dashboard'): ?>
                    <!-- Dashboard Content -->
                    <div class="content-section">
                        <h2 class="section-title">
                            <i class="fas fa-clock"></i>
                            Recent Activity
                        </h2>
                        <div class="activity-list">
                            <?php if (!empty($recent_activity)): ?>
                                <?php foreach ($recent_activity as $activity): ?>
                                    <div class="activity-item">
                                        <div class="user-avatar-table">
                                            <?php if (!empty($activity['avatar'])): ?>
                                                <img src="../logic/user/avatars/<?= htmlspecialchars($activity['avatar']) ?>" alt="Avatar" class="avatar-img-table">
                                            <?php else: ?>
                                                <i class="fas fa-user-circle"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="activity-content">
                                            <p><?= htmlspecialchars($activity['description']) ?></p>
                                            <div class="activity-meta">
                                                <span class="activity-time">
                                                    <i class="fas fa-clock"></i>
                                                    <?= date('H:i', strtotime($activity['created_at'])) ?>
                                                </span>
                                                <span class="activity-date">
                                                    <i class="fas fa-calendar"></i>
                                                    <?= date('d/m/Y', strtotime($activity['created_at'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-clock"></i>
                                    <p>Belum ada aktivitas terbaru</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($current_page === 'users'): ?>
                    <!-- Users Management -->
                    <div class="content-section">
                        <h2 class="section-title">
                            <i class="fas fa-users"></i>
                            User Management
                        </h2>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Avatar</th>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Total Orders</th>
                                        <th>Last Order</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="user-avatar-table">
                                                    <?php if (!empty($user['avatar'])): ?>
                                                        <img src="../logic/user/avatars/<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar" class="avatar-img-table">
                                                    <?php else: ?>
                                                        <i class="fas fa-user-circle"></i>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?= $user['id_pengguna'] ?></td>
                                            <td><?= htmlspecialchars($user['name']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><?= htmlspecialchars($user['phone'] ?? 'Not set') ?></td>
                                            <td>
                                                <span class="badge badge-info"><?= $user['total_orders'] ?></span>
                                            </td>
                                            <td><?= $user['last_order'] ? date('d/m/Y', strtotime($user['last_order'])) : 'Never' ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-primary" onclick="viewUser(<?= $user['id_pengguna'] ?>)">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(<?= $user['id_pengguna'] ?>)">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php elseif ($current_page === 'orders'): ?>
                    <!-- Orders Management -->
                    <div class="content-section">
                        <h2 class="section-title">
                            <i class="fas fa-shopping-cart"></i>
                            Order Management
                        </h2>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Phone</th>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Method</th>
                                        <th>Proof</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>
                                                <span class="order-id">#<?= $order['id_order'] ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($order['customer_name'] ?? 'Unknown') ?></td>
                                            <td><?= htmlspecialchars($order['phone']) ?></td>
                                            <td>
                                                <div class="product-info-table">
                                                    <?php
                                                    $image_src = $color_images[$order['color']] ?? '/placeholder.svg?height=40&width=40';
                                                    ?>
                                                    <img src="<?= $image_src ?>" alt="HidroSmart" class="product-image-table">
                                                    <span><?= ucfirst($order['color']) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary"><?= $order['kuantitas'] ?></span>
                                            </td>
                                            <td>
                                                <span class="price">Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></span>
                                            </td>
                                            <td><?= ucfirst(str_replace('_', ' ', $order['metode_pembayaran'])) ?></td>
                                            <td>
                                                <?php if (!empty($order['bukti_pembayaran'])): ?>
                                                    <a href="../logic/payment/uploads/<?= $order['bukti_pembayaran'] ?>" data-lightbox="bukti-<?= $order['id_order'] ?>">
                                                        <img src="../logic/payment/uploads/<?= $order['bukti_pembayaran'] ?>" alt="Bukti" class="proof-thumb">
                                                    </a>

                                                <?php else: ?>
                                                    NULL
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?= str_replace(' ', '-', strtolower($order['status'])) ?>">
                                                    <?= ucfirst($order['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($order['tanggal_transaksi'])) ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($order['status'] !== 'Diterima Customer'): ?>
                                                        <button class="btn btn-sm btn-primary" onclick="updateOrderStatus('<?= $order['id_order'] ?>')">
                                                            <i class="fas fa-edit"></i> Update
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteOrder('<?= $order['id_order'] ?>')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php elseif ($current_page === 'reviews'): ?>
                    <!-- Product Reviews -->
                    <div class="content-section">
                        <h2 class="section-title">
                            <i class="fas fa-star"></i>
                            Product Reviews
                        </h2>
                        <div class="reviews-container">
                            <?php if (!empty($reviews)): ?>
                                <?php foreach ($reviews as $review): ?>
                                    <div class="review-card">
                                        <div class="review-header">
                                            <div class="customer-info">
                                                <div class="customer-avatar">
                                                    <?php if (!empty($review['avatar'])): ?>
                                                        <img src="../logic/user/avatars/<?= htmlspecialchars($review['avatar']) ?>" alt="Avatar" class="avatar-img-table">
                                                    <?php else: ?>
                                                        <i class="fas fa-user-circle"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="customer-details">
                                                    <h4><?= htmlspecialchars($review['customer_name']) ?></h4>
                                                    <p><?= htmlspecialchars($review['customer_email']) ?></p>
                                                    <small>Order #<?= $review['id_order'] ?></small>
                                                </div>
                                            </div>
                                            <div class="review-meta">
                                                <div class="review-rating">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <span class="star <?= $i <= $review['rating'] ? 'filled' : '' ?>">★</span>
                                                    <?php endfor; ?>
                                                </div>
                                                <div class="review-date">
                                                    <i class="fas fa-calendar"></i>
                                                    <?= date('d/m/Y H:i', strtotime($review['created_at'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="product-info-review">
                                            <?php
                                            $image_src = $color_images[$review['color']] ?? '/placeholder.svg?height=60&width=60';
                                            ?>
                                            <img src="<?= $image_src ?>" alt="HidroSmart Tumbler" class="product-image-review">
                                            <div class="product-details-review">
                                                <h5>HidroSmart Tumbler</h5>
                                                <p>Color: <?= ucfirst($review['color'] ?? 'N/A') ?></p>
                                                <p>ID Product: HST-<?= $review['id_order'] ?></p>
                                            </div>
                                        </div>
                                        <div class="review-content">
                                            <p><?= nl2br(htmlspecialchars($review['ulasan'])) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-star"></i>
                                    <p>Belum ada ulasan produk</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($current_page === 'guarantees'): ?>
                    <!-- Guarantees Management -->
                    <div class="content-section">
                        <h2 class="section-title">
                            <i class="fas fa-shield-alt"></i>
                            Guarantee Claims
                        </h2>
                        <div class="guarantee-grid">
                            <?php if (!empty($guarantees)): ?>
                                <?php foreach ($guarantees as $guarantee): ?>
                                    <div class="guarantee-card guarantee-status-<?= $guarantee['status_klaim'] ?>">
                                        <div class="guarantee-header">
                                            <h4>
                                                <i class="fas fa-shield-alt"></i>
                                                Claim #<?= $guarantee['id_guarantee'] ?>
                                            </h4>
                                            <div class="guarantee-status-info">
                                                <span class="guarantee-date">
                                                    <i class="fas fa-calendar"></i>
                                                    <?= date('d/m/Y', strtotime($guarantee['tanggal_klaim'])) ?>
                                                </span>
                                                <span class="status-badge status-<?= $guarantee['status_klaim'] ?>">
                                                    <?= ucfirst($guarantee['status_klaim']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="guarantee-content">
                                            <div class="product-info-guarantee">
                                                <?php
                                                // Try to show damage evidence image first, fallback to product image
                                                $damage_image_path = '../logic/guarantee/bukti_kerusakan/' . $guarantee['bukti_gambar'];
                                                $color_images = [
                                                    'black' => '../images/hidrosmart-black.jpg',
                                                    'white' => '../images/hidrosmart-white.jpg',
                                                    'blue' => '../images/hidrosmart-blue.jpg',
                                                    'gray' => '../images/hidrosmart-gray.jpg'
                                                ];

                                                if (!empty($guarantee['bukti_gambar']) && file_exists($damage_image_path)) {
                                                    $image_src = $damage_image_path;
                                                    $image_alt = "Bukti Kerusakan";
                                                } else {
                                                    $image_src = $color_images[$guarantee['color']] ?? '/placeholder.svg?height=80&width=80';
                                                    $image_alt = "HidroSmart Tumbler";
                                                }
                                                ?>
                                                <a href="<?= $image_src ?>" data-lightbox="guarantee-image" data-title="Bukti Kerusakan">
                                                    <img src="<?= $image_src ?>" alt="<?= $image_alt ?>" class="product-image-guarantee">
                                                </a>
                                                <div class="product-details-guarantee">
                                                    <h5>HidroSmart Tumbler</h5>
                                                    <p>Order: #<?= $guarantee['id_order'] ?></p>
                                                    <p>Color: <?= ucfirst($guarantee['color'] ?? 'N/A') ?></p>
                                                </div>
                                            </div>
                                            <div class="customer-info-guarantee">
                                                <p><strong>Customer:</strong> <?= htmlspecialchars($guarantee['customer_name']) ?></p>
                                                <p><strong>Phone:</strong> <?= htmlspecialchars($guarantee['phone']) ?></p>
                                                <p><strong>Location:</strong> <?= htmlspecialchars($guarantee['domisili']) ?></p>
                                                <?php if (!empty($guarantee['deskripsi'])): ?>
                                                    <p><strong>Issue:</strong> <?= htmlspecialchars($guarantee['deskripsi']) ?></p>
                                                <?php endif; ?>
                                                <?php if (!empty($guarantee['catatan_admin'])): ?>
                                                    <p><strong>Admin Notes:</strong> <?= htmlspecialchars($guarantee['catatan_admin']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($guarantee['status_klaim'] === 'menunggu'): ?>
                                                <div class="guarantee-actions">
                                                    <button class="btn btn-sm btn-success" onclick="updateGuaranteeStatus('<?= $guarantee['id_guarantee'] ?>', 'disetujui')">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="updateGuaranteeStatus('<?= $guarantee['id_guarantee'] ?>', 'ditolak')">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                    
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-shield-alt"></i>
                                    <p>Belum ada klaim garansi</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($current_page === 'suggestions'): ?>
                    <!-- Contact Messages/Suggestions -->
                    <div class="content-section">
                        <h2 class="section-title">
                            <i class="fas fa-comments"></i>
                            User Suggestions & Contact Messages
                        </h2>
                        <div class="messages-container">
                            <?php if (!empty($messages)): ?>
                                <?php foreach ($messages as $message): ?>
                                    <div class="message-card <?= $message['status'] === 'unread' ? 'unread' : 'read' ?>" data-message-id="<?= $message['id_message'] ?>" data-phone="<?= htmlspecialchars($message['phone']) ?>">
                                        <div class="message-header">
                                            <div class="sender-info">
                                                <div class="customer-avatar">
                                                    <?php if (!empty($message['avatar'])): ?>
                                                        <img src="../logic/user/avatars/<?= htmlspecialchars($message['avatar']) ?>" alt="Avatar" class="avatar-img-table">
                                                    <?php else: ?>
                                                        <i class="fas fa-user-circle"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="sender-details">
                                                    <h4><?= htmlspecialchars($message['name']) ?></h4>
                                                    <p><?= htmlspecialchars($message['email']) ?></p>
                                                    <?php if (!empty($message['phone'])): ?>
                                                        <p><i class="fas fa-phone"></i> <?= htmlspecialchars($message['phone']) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="message-meta">
                                                <span class="message-date">
                                                    <i class="fas fa-clock"></i>
                                                    <?= date('d/m/Y H:i', strtotime($message['created_at'])) ?>
                                                </span>
                                                <span class="status-badge status-<?= $message['status'] ?>">
                                                    <?= ucfirst($message['status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="message-content">
                                            <h5>
                                                <i class="fas fa-envelope"></i>
                                                <?= htmlspecialchars($message['subject']) ?>
                                            </h5>
                                            <p><?= nl2br(htmlspecialchars($message['message'])) ?></p>
                                        </div>
                                        <div class="reply-area" id="reply-area-<?= $message['id_message'] ?>" style="margin:10px 0; display:none;">
                                            <textarea id="reply-text-<?= $message['id_message'] ?>" class="form-control" placeholder="Tulis balasan..."></textarea>
                                            <button class="btn btn-sm btn-primary" onclick="sendInlineReply(<?= $message['id_message'] ?>)"><i class="fas fa-paper-plane"></i> Kirim Balasan</button>
                                        </div>
                                        <div class="message-actions">
                                            <?php if ($message['status'] === 'unread'): ?>
                                                <button class="btn btn-sm btn-primary" onclick="markAsRead(<?= $message['id_message'] ?>)">
                                                    <i class="fas fa-check"></i> Mark as Read
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-success" onclick="toggleReplyArea(<?= $message['id_message'] ?>)">
                                                <i class="fas fa-reply"></i> Balas
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-comments"></i>
                                    <p>Belum ada pesan dari user</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Update Order Status Modal -->
    <div id="updateStatusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Update Order Status</h3>
                <span class="close" onclick="closeModal('updateStatusModal')">&times;</span>
            </div>
            <form id="updateStatusForm">
                <input type="hidden" id="update_order_id" name="order_id">
                <div class="form-group">
                    <label for="new_status">New Status</label>
                    <select id="new_status" name="status" required>
                        <option value="Pesanan Dibuat">Pesanan Dibuat</option>
                        <option value="Pembayaran Dikonfirmasi">Pembayaran Dikonfirmasi</option>
                        <option value="Sedang Dikemas">Sedang Dikemas</option>
                        <option value="Sedang Dalam Perjalanan">Sedang Dalam Perjalanan</option>
                        <option value="Diterima Customer">Diterima Customer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="admin_notes">Timeline Description</label>
                    <textarea id="admin_notes" name="notes" rows="3" placeholder="Deskripsi yang akan ditampilkan di timeline user..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Status
                </button>
            </form>
        </div>
    </div>

    <!-- User Detail Modal -->
    <div id="userDetailModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3><i class="fas fa-user"></i> User Details</h3>
                <span class="close" onclick="closeModal('userDetailModal')">&times;</span>
            </div>
            <div id="userDetailContent" class="modal-body">
                <!-- User details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Enhanced Guarantee Note Modal -->
    <div id="guaranteeStatusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="guaranteeStatusModalTitle"><i class="fas fa-info-circle"></i> Update Guarantee Status</h3>
                <span class="close" onclick="closeModal('guaranteeStatusModal')">&times;</span>
            </div>
            <form id="guaranteeStatusForm">
                <input type="hidden" id="guarantee_status_id" name="guarantee_id">
                    <input type="hidden" id="guarantee_status_action" name="status">
                <div class="form-group">
                    <label for="admin_status_note">Catatan</label>
                    <textarea id="admin_status_note" name="admin_notes" rows="4" placeholder="Masukkan catatan admin untuk user..." required></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('guaranteeStatusModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Note
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Processing...</p>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notification-container"></div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../logic/admin/admin.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lightbox2@2/dist/js/lightbox-plus-jquery.min.js"></script>
</body>

</html>