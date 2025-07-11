<?php
session_start();

// Cek autentikasi
if (!isset($_SESSION['user_id'])) {
    header('Location: login-register.php');
    exit();
}

// Include database
require_once '../logic/login-register/database.php';

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

// Cek pending order atau success
$has_pending_order = isset($_SESSION['pending_order']);
$payment_success = isset($_GET['success']) && $_GET['success'] == '1';
$payment_error = $_SESSION['payment_error'] ?? null;

// Clear error setelah ditampilkan
if ($payment_error) {
    unset($_SESSION['payment_error']);
}

// Jika tidak ada pending order dan bukan success, redirect ke order
if (!$has_pending_order && !$payment_success) {
    header('Location: order.php');
    exit();
}

// Get data
$order_data = $_SESSION['pending_order'] ?? null;
$success_data = $_SESSION['payment_success'] ?? null;
$user_info = [
    'username' => $_SESSION['username'] ?? 'User'
];

// Profil pengguna sudah diambil sebelumnya, jadi bagian ini dihapus untuk mencegah
// $user_profile tertimpa kembali dan menyebabkan avatar tidak ditampilkan.

// Clear success data setelah ditampilkan
if ($payment_success && $success_data) {
    unset($_SESSION['payment_success']);
}

// Get product image based on color
function getProductImage($color)
{
    $images = [
        'black' => '../images/hidrosmart-black.jpg',
        'white' => '../images/hidrosmart-white.jpg',
        'blue' => '../images/hidrosmart-blue.jpg',
        'gray' => '../images/hidrosmart-gray.jpg'
    ];
    return $images[$color] ?? '../images/hidrosmart-all.jpg';
}

// Get color name in Indonesian
function getColorName($color)
{
    $colors = [
        'black' => 'Hitam',
        'white' => 'Putih',
        'blue' => 'Biru',
        'gray' => 'Abu-abu'
    ];
    return $colors[$color] ?? ucfirst($color);
}

// Payment methods config
$payment_methods = [
    'bank_transfer' => [
        'name' => 'Transfer Bank',
        'icon' => 'fas fa-university',
        'accounts' => [
            'BCA' => '1234567890',
            'Mandiri' => '0987654321'
        ],
        'account_name' => 'PT HidroSmart Indonesia'
    ],
    'ewallet' => [
        'name' => 'E-Wallet (GoPay, OVO, DANA)',
        'icon' => 'fas fa-mobile-alt',
        'accounts' => [
            'GoPay' => '081234567890',
            'OVO' => '081234567890',
            'DANA' => '081234567890'
        ],
        'account_name' => 'PT HidroSmart Indonesia'
    ],
    'cod' => [
        'name' => 'Bayar di Tempat (COD)',
        'icon' => 'fas fa-money-bill-wave',
        'description' => 'Anda akan membayar saat produk tiba di lokasi. Pastikan Anda berada di alamat yang tercantum saat kurir datang.'
    ]
];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - HidroSmart</title>
    <meta name="description" content="Selesaikan pembayaran pesanan HidroSmart Anda">

    <!-- CSS -->
    <link rel="stylesheet" href="../style/payment.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Navigation Warning Modal -->
    <div id="navigationWarningModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Peringatan!</h3>
            </div>
            <div class="modal-body">
                <p>Anda memiliki pesanan yang belum selesai. Jika Anda meninggalkan halaman ini, pesanan akan dibatalkan.</p>
                <p><strong>Apakah Anda yakin ingin melanjutkan?</strong></p>
            </div>
            <div class="modal-actions">
                <button onclick="confirmNavigation()" class="btn-danger">
                    <i class="fas fa-times"></i>
                    Ya, Batalkan Pesanan
                </button>
                <button onclick="cancelNavigation()" class="btn-primary">
                    <i class="fas fa-arrow-left"></i>
                    Tetap di Halaman Ini
                </button>
            </div>
        </div>
    </div>

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
                    <li><a href="#" onclick="checkNavigation('home.php')" class="nav-link"><i class="ri-home-4-line"></i> Home</a></li>
                    <li><a href="#" onclick="checkNavigation('about.php')" class="nav-link"><i class="ri-information-2-line"></i> About Us</a></li>
                    <li><a href="#" onclick="checkNavigation('contact.php')" class="nav-link"><i class="ri-phone-line"></i> Contact Us</a></li>
                    <li><a href="#" onclick="checkNavigation('guarantee.php')" class="nav-link"><i class="ri-shield-line"></i> Guarantee Claim</a></li>
                    <li><a href="#" onclick="checkNavigation('order.php')" class="nav-link active"><i class="ri-shopping-cart-2-line"></i> Order</a></li>
                    <li><a href="#" onclick="checkNavigation('user.php')" class="nav-link"><i class="ri-user-3-line"></i> Dashboard</a></li>
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

    <!-- Main Content -->
    <main class="main-content">
        <!-- Payment Header -->
        <section class="payment-header">
            <div class="container">
                <div class="payment-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1 class="page-title">Pembayaran</h1>
                <p class="page-subtitle">Pilih metode pembayaran dan selesaikan transaksi Anda</p>
            </div>
        </section>

        <!-- Payment Content -->
        <section class="payment-section">
            <div class="container">
                <?php if ($payment_success && $success_data): ?>
                    <!-- Success Message with Popup -->
                    <div class="success-card">
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h2>Pembayaran Berhasil!</h2>
                        <p>Pesanan Anda dengan ID <strong><?php echo htmlspecialchars($success_data['order_id']); ?></strong> telah berhasil diproses.</p>
                        <div class="success-actions">
                            <button onclick="showSuccessPopup()" class="btn-primary">
                                <i class="fas fa-check"></i>
                                Selesai
                            </button>
                        </div>
                    </div>

                    <!-- Success Popup -->
                    <div id="successPopup" class="popup-overlay" style="display: none;">
                        <div class="popup-content">
                            <div class="popup-header">
                                <h3>Pemesanan Berhasil!</h3>
                                <button onclick="closePopup()" class="close-btn">&times;</button>
                            </div>
                            <div class="popup-body">
                                <p>Terima kasih! Pesanan Anda telah berhasil diproses.</p>
                                <p><strong>Order ID:</strong> <?php echo htmlspecialchars($success_data['order_id']); ?></p>
                                <p><strong>Status:</strong> <?php echo htmlspecialchars($success_data['status']); ?></p>
                            </div>
                            <div class="popup-actions">
                                <a href="user.php" class="btn-primary">
                                    <i class="fas fa-tachometer-alt"></i>
                                    Lihat Dashboard
                                </a>
                                <a href="order.php" class="btn-secondary">
                                    <i class="fas fa-shopping-cart"></i>
                                    Pesan Lagi
                                </a>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="payment-content">
                        <!-- Order Summary -->
                        <div class="summary-section">
                            <div class="summary-card">
                                <h2>Ringkasan Pesanan</h2>

                                <div class="order-details">
                                    <div class="product-info">
                                        <div class="product-image-section">
                                            <div class="product-image-container">
                                                <img src="<?php echo getProductImage($order_data['color'] ?? 'black'); ?>"
                                                    alt="HidroSmart Tumbler <?php echo getColorName($order_data['color'] ?? 'black'); ?>"
                                                    class="product-image">
                                                <div class="product-badge">
                                                    <span><?php echo getColorName($order_data['color'] ?? 'black'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="product-details">
                                            <h3>HidroSmart Tumbler</h3>
                                            <div class="product-specs">
                                                <span><i class="fas fa-palette"></i> Warna: <strong><?php echo getColorName($order_data['color'] ?? 'black'); ?></strong></span>
                                                <span><i class="fas fa-box"></i> Jumlah: <strong><?php echo htmlspecialchars($order_data['quantity'] ?? 0); ?> unit</strong></span>
                                                <span><i class="fas fa-map-marker-alt"></i> Pengiriman: <strong><?php echo htmlspecialchars(substr($order_data['address'] ?? '', 0, 50)) . (strlen($order_data['address'] ?? '') > 50 ? '...' : ''); ?></strong></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="price-breakdown">
                                    <div class="price-row">
                                        <span>Subtotal</span>
                                        <span>Rp <?php echo number_format($order_data['subtotal'] ?? 0, 0, ',', '.'); ?></span>
                                    </div>
                                    <div class="price-row">
                                        <span>Ongkos Kirim</span>
                                        <span>Rp 15.000</span>
                                    </div>
                                    <div class="price-row total">
                                        <span>Total Pembayaran</span>
                                        <span>Rp <?php echo number_format($order_data['total'] ?? 0, 0, ',', '.'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="payment-method-section">
                            <div class="payment-card">
                                <div class="payment-header-card">
                                    <i class="fas fa-credit-card"></i>
                                    <h2>Metode Pembayaran</h2>
                                </div>

                                <?php if ($payment_error): ?>
                                    <div class="alert alert-error">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <div>
                                            <strong>Terjadi kesalahan:</strong>
                                            <p><?php echo htmlspecialchars($payment_error); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <form class="payment-form" method="POST" action="../logic/payment/payment-controller.php" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label>Pilih Metode Pembayaran*</label>

                                        <div class="payment-options">
                                            <!-- Bank Transfer -->
                                            <div class="payment-option">
                                                <input type="radio" id="bank_transfer" name="payment_method" value="bank_transfer" required>
                                                <label for="bank_transfer" class="payment-option-label">
                                                    <i class="<?php echo $payment_methods['bank_transfer']['icon']; ?>"></i>
                                                    <span><?php echo $payment_methods['bank_transfer']['name']; ?></span>
                                                </label>
                                            </div>

                                            <!-- E-Wallet -->
                                            <div class="payment-option">
                                                <input type="radio" id="ewallet" name="payment_method" value="ewallet" required>
                                                <label for="ewallet" class="payment-option-label">
                                                    <i class="<?php echo $payment_methods['ewallet']['icon']; ?>"></i>
                                                    <span><?php echo $payment_methods['ewallet']['name']; ?></span>
                                                </label>
                                            </div>

                                            <!-- COD -->
                                            <div class="payment-option">
                                                <input type="radio" id="cod" name="payment_method" value="cod" required>
                                                <label for="cod" class="payment-option-label">
                                                    <i class="<?php echo $payment_methods['cod']['icon']; ?>"></i>
                                                    <span><?php echo $payment_methods['cod']['name']; ?></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bank Transfer Details -->
                                    <div id="bank-transfer-details" class="payment-details" style="display: none;">
                                        <div class="bank-info">
                                            <h4>Rekening Tujuan:</h4>
                                            <div class="bank-accounts">
                                                <?php foreach ($payment_methods['bank_transfer']['accounts'] as $bank => $account): ?>
                                                    <div class="bank-account">
                                                        <strong><?php echo $bank; ?>:</strong> <?php echo $account; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                                <div class="bank-account">
                                                    <strong>A.n:</strong> <?php echo $payment_methods['bank_transfer']['account_name']; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="transfer_proof_bank">Upload Bukti Transfer (Opsional)</label>
                                            <div class="file-upload-wrapper">
                                                <input type="file" id="transfer_proof_bank" name="transfer_proof_bank" accept="image/*,.pdf" class="file-input">
                                                <label for="transfer_proof_bank" class="file-upload-label">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                    <span>Pilih file atau drag & drop</span>
                                                    <small>JPG, PNG, PDF (Max 5MB)</small>
                                                </label>
                                            </div>
                                            <div class="file-preview" style="display: none;"></div>
                                        </div>
                                    </div>

                                    <!-- E-Wallet Details -->
                                    <div id="ewallet-details" class="payment-details" style="display: none;">
                                        <div class="ewallet-info">
                                            <h4>Akun Tujuan:</h4>
                                            <div class="bank-accounts">
                                                <?php foreach ($payment_methods['ewallet']['accounts'] as $ewallet => $account): ?>
                                                    <div class="bank-account">
                                                        <strong><?php echo $ewallet; ?>:</strong> <?php echo $account; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                                <div class="bank-account">
                                                    <strong>A.n:</strong> <?php echo $payment_methods['ewallet']['account_name']; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="transfer_proof_ewallet">Upload Bukti Transfer (Opsional)</label>
                                            <div class="file-upload-wrapper">
                                                <input type="file" id="transfer_proof_ewallet" name="transfer_proof_ewallet" accept="image/*,.pdf" class="file-input">
                                                <label for="transfer_proof_ewallet" class="file-upload-label">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                    <span>Pilih file atau drag & drop</span>
                                                    <small>JPG, PNG, PDF (Max 5MB)</small>
                                                </label>
                                            </div>
                                            <div class="file-preview" style="display: none;"></div>
                                        </div>
                                    </div>

                                    <!-- COD Details -->
                                    <div id="cod-details" class="payment-details" style="display: none;">
                                        <div class="cod-info">
                                            <h4>Bayar di Tempat (COD)</h4>
                                            <p><?php echo $payment_methods['cod']['description']; ?></p>
                                        </div>
                                    </div>

                                    <button type="submit" name="confirm_payment" class="btn-submit">
                                        <i class="fas fa-check"></i>
                                        Konfirmasi Pesanan
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- JavaScript -->
    <script>
        // data order untuk JS
        window.orderData = <?php echo json_encode($order_data); ?>;
    </script>
    <script src="../logic/payment/payment.js"></script>

    <script>
        // Navigation warning system
        let pendingNavigation = null;
        const hasPendingOrder = <?php echo $has_pending_order ? 'true' : 'false'; ?>;
        const paymentSuccess = <?php echo $payment_success ? 'true' : 'false'; ?>;

        function checkNavigation(url) {
            if (hasPendingOrder && !paymentSuccess) {
                pendingNavigation = url;
                document.getElementById('navigationWarningModal').style.display = 'flex';
            } else {
                window.location.href = url;
            }
        }

        function confirmNavigation() {
            if (pendingNavigation) {
                // Delete pending order
                fetch('../logic/payment/delete-pending-order.php', {
                    method: 'POST'
                }).then(() => {
                    window.location.href = pendingNavigation;
                });
            }
        }

        function cancelNavigation() {
            document.getElementById('navigationWarningModal').style.display = 'none';
            pendingNavigation = null;
        }

        // Browser back button handling (tanpa alert native)
        if (hasPendingOrder && !paymentSuccess) {
            // Hanya mencegah navigasi back dengan menampilkan modal kita sendiri
            window.addEventListener('popstate', function(e) {
                pendingNavigation = document.referrer || 'home.php';
                document.getElementById('navigationWarningModal').style.display = 'flex';
                history.pushState(null, null, window.location.href);
            });
        }

        // Show/hide payment details based on selected method
        document.addEventListener('DOMContentLoaded', function() {
            const paymentOptions = document.querySelectorAll('input[name="payment_method"]');
            const paymentDetails = document.querySelectorAll('.payment-details');

            // Function to show details for selected payment method
            function showPaymentDetails() {
                // Hide all details first
                paymentDetails.forEach(detail => {
                    detail.style.display = 'none';
                });

                // Show details for selected method
                const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
                if (selectedMethod) {
                    const detailsId = selectedMethod.value.replace('_', '-') + '-details';
                    const detailsElement = document.getElementById(detailsId);
                    if (detailsElement) {
                        detailsElement.style.display = 'block';
                    }
                }
            }

            // Add event listeners to payment options
            paymentOptions.forEach(option => {
                option.addEventListener('change', showPaymentDetails);
            });

            // Show details for initially selected option
            showPaymentDetails();

            // Enhanced file upload with close functionality
            const fileInputs = document.querySelectorAll('.file-input');
            fileInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const file = this.files[0];
                    const preview = this.parentElement.nextElementSibling;
                    const label = this.nextElementSibling;

                    if (file) {
                        // Validate file
                        if (file.size > 5 * 1024 * 1024) {
                            alert('Ukuran file terlalu besar. Maksimal 5MB.');
                            this.value = '';
                            return;
                        }

                        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                        if (!allowedTypes.includes(file.type)) {
                            alert('Tipe file tidak diizinkan. Gunakan JPG, PNG, atau PDF.');
                            this.value = '';
                            return;
                        }

                        // Update label
                        label.querySelector('span').textContent = file.name;
                        label.style.borderColor = '#10b981';
                        label.style.background = '#ecfdf5';

                        // Show preview with close button
                        preview.style.display = 'block';
                        preview.innerHTML = `
                            <div class="file-preview-item">
                                <div class="file-info">
                                    <i class="fas ${file.type.includes('pdf') ? 'fa-file-pdf' : 'fa-file-image'}"></i>
                                    <div class="file-details">
                                        <span class="file-name">${file.name}</span>
                                        <small class="file-size">(${(file.size/1024/1024).toFixed()} MB)</small>
                                    </div>
                                </div>
                                <button type="button" class="file-close-btn" onclick="removeFile(this)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        `;

                        // Show image preview if it's an image
                        if (file.type.startsWith('image/')) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                const imagePreview = document.createElement('div');
                                imagePreview.className = 'image-preview';
                                imagePreview.innerHTML = `
                                    <img src="${e.target.result}" alt="Preview" class="preview-image">
                                `;
                                preview.querySelector('.file-preview-item').appendChild(imagePreview);
                            };
                            reader.readAsDataURL(file);
                        }
                    } else {
                        // Reset if no file
                        label.querySelector('span').textContent = 'Pilih file atau drag & drop';
                        label.style.borderColor = '#d1d5db';
                        label.style.background = 'white';
                        preview.style.display = 'none';
                    }
                });
            });
        });

        // Remove file function
        function removeFile(button) {
            const preview = button.closest('.file-preview');
            const fileInput = preview.previousElementSibling.querySelector('.file-input');
            const label = preview.previousElementSibling.querySelector('.file-upload-label');

            // Clear file input
            fileInput.value = '';

            // Reset label
            label.querySelector('span').textContent = 'Pilih file atau drag & drop';
            label.style.borderColor = '#d1d5db';
            label.style.background = 'white';

            // Hide preview
            preview.style.display = 'none';
            preview.innerHTML = '';
        }

        // Success popup functions
        function showSuccessPopup() {
            document.getElementById('successPopup').style.display = 'flex';
        }

        function closePopup() {
            document.getElementById('successPopup').style.display = 'none';
        }

        // Auto show popup if success
        <?php if ($payment_success && $success_data): ?>
            setTimeout(showSuccessPopup, 500);
        <?php endif; ?>
    </script>

    <!-- Confirmation Popup -->
    <div id="confirmationPopup" class="popup-overlay" style="display:none;">
        <div class="popup-content" style="max-width:420px;">
            <div class="popup-header">
                <h3>Konfirmasi Pesanan Anda</h3>
                <button onclick="closeConfirmation()" class="close-btn">&times;</button>
            </div>
            <div class="popup-body" id="confirmationBody" style="max-height:60vh;overflow:auto;padding:1.5rem 1rem;">
                <!-- summary filled by JS -->
            </div>
            <div class="popup-actions" style="display:flex;gap:0.75rem;justify-content:center;padding-bottom:1rem;">
                <button id="confirmYesBtn" class="btn-primary"><i class="fas fa-check"></i> Ya, Pesan</button>
                <button id="confirmNoBtn" class="btn-secondary">Kembali</button>
            </div>
        </div>
    </div>

    <!-- Processing Popup -->
    <div id="processingPopup" class="popup-overlay" style="display:none;">
        <div class="popup-content" style="max-width:400px;text-align:center;">
            <div class="popup-header">
                <h3>Sedang Memproses Pesanan...</h3>
            </div>
            <div class="popup-body" style="padding:2rem 1rem;">
                <i class="fas fa-spinner fa-spin fa-3x" style="color:#3b82f6;"></i>
                <p style="margin-top:1rem; color:#64748b;">Mohon tunggu, kami sedang menyimpan pesanan Anda.</p>
            </div>
        </div>
    </div>

    <!-- Chatbot Widget -->
    <script src="../logic/chatbot/chatbot-widget.js"></script>
</body>

</html>