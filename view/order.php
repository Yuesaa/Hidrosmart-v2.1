<?php
session_start();

// Include dependencies
require_once '../logic/login-register/database.php';
require_once '../logic/order/order-logic.php';
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

// Initialize variables
$form_errors = [];
$form_data = [];
$user_info = [];
$user_profile = [];
$profile_complete = false;
$product_info = [];
$user_id = null;

// Check login status and profile completeness
$user_logged_in = isset($_SESSION['user_id']) || isset($_SESSION['username']);
$username = $user_logged_in ? ($_SESSION['username'] ?? 'User') : 'Masuk';
$profile_complete = false;
$user_profile = null;

try {
    // Initialize order logic
    $orderLogic = new OrderLogic($pdo);

    // Check if user is logged in
    if (!$orderLogic->checkUserLogin()) {
        $login_required_message = urlencode('Harus login terlebih dahulu untuk melakukan pemesanan');
        header("Location: login-register.php?tab=login&login_required=$login_required_message");
        exit();
    }

    // Get user ID
    $user_id = $orderLogic->getUserId();
    if (!$user_id) {
        throw new Exception('User ID not found');
    }

    // Get user profile
    $user_profile = $orderLogic->getUserProfile($user_id);
    if (!$user_profile) {
        throw new Exception('User profile not found');
    }

    // Check profile completeness
    $profile_complete = $orderLogic->checkProfileCompleteness($user_id);

    // Get product information
    $product_info = $orderLogic->getProductInfo();

    // Set user info
    $user_info = [
        'user_id' => $user_id,
        'username' => $user_profile['name'] ?? 'User'
    ];

    // Get form data (either from POST or defaults)
    $form_data = [
        'color' => $_POST['color'] ?? '',
        'quantity' => max(1, min(10, (int)($_POST['quantity'] ?? 1))),
        'phone' => $_POST['phone'] ?? ($user_profile['phone'] ?? ''),
        'address' => $_POST['address'] ?? ($user_profile['alamat'] ?? '')
    ];

    // Handle error messages from session
    if (isset($_SESSION['order_error'])) {
        $form_errors[] = $_SESSION['order_error'];
        unset($_SESSION['order_error']);
    }

    // Handle error messages from URL parameters
    if (isset($_GET['error'])) {
        $form_errors[] = urldecode($_GET['error']);
    }
} catch (Exception $e) {
    // Handle authentication errors
    if (strpos($e->getMessage(), 'not authenticated') !== false || strpos($e->getMessage(), 'User ID not found') !== false) {
        $login_required_message = urlencode('Harus login terlebih dahulu untuk melakukan pemesanan');
        header("Location: login-register.php?tab=login&login_required=$login_required_message");
        exit();
    } else {
        // Log error and show generic message
        error_log("Order page error: " . $e->getMessage());
        $form_errors[] = 'Terjadi kesalahan sistem, silakan muat ulang halaman';
    }
}

// Calculate pricing
$pricing = $orderLogic->calculateOrderPricing($form_data['quantity']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order - HidroSmart</title>
    <meta name="description" content="Pesan HidroSmart Tumbler dengan teknologi monitoring kesehatan terdepan">

    <!-- CSS -->
    <link rel="stylesheet" href="../style/order.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Order Confirmation Modal -->
    <div id="orderConfirmationModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-shopping-cart modal-icon"></i>
                <h3>Konfirmasi Pesanan</h3>
            </div>
            <div class="modal-body">
                <p><strong>Apakah Anda yakin dengan pesanan ini?</strong></p>
                <div class="order-details">
                    <div class="detail-row">
                        <span>Produk:</span>
                        <span><?php echo htmlspecialchars($product_info['name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Warna:</span>
                        <span id="confirm-color">-</span>
                    </div>
                    <div class="detail-row">
                        <span>Jumlah:</span>
                        <span id="confirm-quantity">-</span>
                    </div>
                    <div class="detail-row">
                        <span>Total:</span>
                        <span id="confirm-total">-</span>
                    </div>
                </div>
                <p><small>Setelah konfirmasi, Anda akan diarahkan ke halaman pembayaran.</small></p>
            </div>
            <div class="modal-actions">
                <button onclick="confirmOrder()" class="btn-primary" id="confirmBtn">
                    <i class="fas fa-check"></i>
                    Ya, Lanjutkan
                </button>
                <button onclick="cancelOrder()" class="btn-secondary">
                    <i class="fas fa-times"></i>
                    Batal
                </button>
            </div>
        </div>
    </div>

    <!-- Profile Change Confirmation Modal -->
    <div id="profileChangeModal" class="profile-modal-overlay" style="display: none;">
        <div class="profile-modal">
            <div class="profile-modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Konfirmasi Perubahan Profil</h3>
            </div>
            <div class="profile-modal-body">
                <p>Anda akan mengubah informasi profil yang tersimpan:</p>
                <div id="profileChanges"></div>
                <p><strong>Apakah Anda yakin ingin melanjutkan?</strong></p>
                <small>Perubahan ini akan memperbarui profil dashboard Anda.</small>
            </div>
            <div class="profile-modal-actions">
                <button onclick="confirmProfileChange()" class="btn-primary">
                    <i class="fas fa-check"></i>
                    Ya, Lanjutkan
                </button>
                <button onclick="cancelProfileChange()" class="btn-secondary">
                    <i class="fas fa-times"></i>
                    Batal
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
                    <li><a href="home.php" class="nav-link"><i class="ri-home-4-line"></i> Home</a></li>
                    <li><a href="about.php" class="nav-link"><i class="ri-information-2-line"></i>About Us</a></li>
                    <li><a href="contact.php" class="nav-link "><i class="ri-phone-line"></i> Contact Us</a></li>
                    <li><a href="guarantee.php" class="nav-link"><i class="ri-shield-line"></i> Guarantee Claim</a>
                    <li><a href="order.php" class="nav-link active"><i class="ri-shopping-cart-2-line"></i> Order</a></li>
                    <li><a href="user.php" class="nav-link"><i class="ri-user-3-line"></i>Dashboard</a></li>
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
        <!-- Order Header -->
        <section class="order-header">
            <div class="container">
                <h1 class="page-title">Pesan HidroSmart</h1>
                <p class="page-subtitle">Lengkapi form pemesanan di bawah ini</p>
            </div>
        </section>

        <!-- Order Content -->
        <section class="order-section">
            <div class="container">
                <div class="order-content">
                    <!-- Product Section -->
                    <div class="product-section">
                        <div class="product-card">
                            <div class="product-header">
                                <span class="product-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-droplets h-8 w-8 text-blue-600">
                                        <path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path>
                                        <path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path>
                                    </svg>
                                </span>
                                <h2><?php echo htmlspecialchars($product_info['name']); ?></h2>
                            </div>

                            <div class="product-showcase">
                                <div class="product-image">
                                    <div class="product-image-container">
                                        <img src="../images/hidrosmart-all.jpg" alt="HidroSmart Tumbler" class="product-main-image">
                                        <div class="product-overlay">
                                            <div class="product-logo">
                                                <span class="product-icon-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-droplets h-8 w-8 text-blue-600">
                                                        <path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path>
                                                        <path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path>
                                                    </svg>
                                                </span>
                                                <span>HIDROSMART</span>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="product-tagline"><?php echo htmlspecialchars($product_info['description']); ?></p>
                                    <div class="product-price">
                                        Rp <?php echo number_format($product_info['base_price'], 0, ',', '.'); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="product-features">
                                <h3>Fitur Utama:</h3>
                                <ul class="features-list">
                                    <li>• Smart sensor untuk monitoring hidrasi</li>
                                    <li>• Aplikasi mobile terintegrasi</li>
                                    <li>• Real-time health tracking</li>
                                    <li>• Battery life hingga 7 hari</li>
                                    <li>• Material BPA-free dan food grade</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Order Form Section -->
                    <div class="form-section">
                        <div class="form-card">
                            <div class="form-header">
                                <i class="fas fa-shopping-cart form-icon"></i>
                                <h2>Form Pemesanan</h2>
                            </div>

                            <!-- Display errors if any -->
                            <?php if (!empty($form_errors)): ?>
                                <div class="alert alert-error">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <div>
                                        <strong>Terjadi kesalahan:</strong>
                                        <ul>
                                            <?php foreach ($form_errors as $error): ?>
                                                <li><?php echo htmlspecialchars($error); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Profile completeness warning -->
                            <?php if (!$profile_complete): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <div>
                                        <strong>Profil Belum Lengkap!</strong>
                                        <p>Anda harus melengkapi nomor telepon dan alamat di <a href="user.php">dashboard profil</a> terlebih dahulu sebelum dapat melakukan pemesanan.</p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Hidden form for actual submission -->
                            <form id="hiddenOrderForm" method="POST" action="../logic/order/order-controller.php" style="display: none;">
                                <input type="hidden" name="submit_order" value="1">
                                <input type="hidden" name="color" id="hidden-color">
                                <input type="hidden" name="quantity" id="hidden-quantity">
                                <input type="hidden" name="phone" id="hidden-phone">
                                <input type="hidden" name="address" id="hidden-address">
                            </form>

                            <!-- Visible form for user interaction -->
                            <form class="order-form" id="orderForm">
                                <div class="form-group">
                                    <label for="color">Pilih Warna*</label>
                                    <select id="color" name="color" required <?php echo !$profile_complete ? 'disabled' : ''; ?>>
                                        <option value="">Pilih warna HidroSmart</option>
                                        <?php foreach ($product_info['colors'] as $value => $label): ?>
                                            <option value="<?php echo htmlspecialchars($value); ?>"
                                                <?php echo ($form_data['color'] === $value) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="quantity">Jumlah*</label>
                                    <div class="quantity-control">
                                        <button type="button" class="qty-btn minus" onclick="changeQuantity(-1)" <?php echo !$profile_complete ? 'disabled' : ''; ?>>
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" id="quantity" name="quantity"
                                            value="<?php echo htmlspecialchars($form_data['quantity']); ?>"
                                            min="1" max="10" required readonly>
                                        <button type="button" class="qty-btn plus" onclick="changeQuantity(1)" <?php echo !$profile_complete ? 'disabled' : ''; ?>>
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="phone">Nomor Telepon*</label>
                                    <div class="input-wrapper">
                                        <input type="tel" id="phone" name="phone"
                                            placeholder="Nomor telepon Anda"
                                            value="<?php echo htmlspecialchars($form_data['phone']); ?>"
                                            <?php echo $profile_complete ? 'readonly' : ''; ?>
                                            required>
                                        <?php if ($profile_complete): ?>
                                            <button type="button" class="edit-btn" onclick="enablePhoneEdit()">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!$profile_complete): ?>
                                        <small class="form-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Nomor telepon akan disimpan ke profil Anda
                                        </small>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="address">Alamat Pengiriman*</label>
                                    <div class="input-wrapper">
                                        <textarea id="address" name="address"
                                            placeholder="Alamat lengkap untuk pengiriman"
                                            <?php echo $profile_complete ? 'readonly' : ''; ?>
                                            required><?php echo htmlspecialchars($form_data['address']); ?></textarea>
                                        <?php if ($profile_complete): ?>
                                            <button type="button" class="edit-btn" onclick="enableAddressEdit()">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!$profile_complete): ?>
                                        <small class="form-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Alamat akan disimpan ke profil Anda
                                        </small>
                                    <?php endif; ?>
                                </div>

                                <!-- Order Summary -->
                                <div class="order-summary">
                                    <h3>Ringkasan Pesanan</h3>
                                    <div class="summary-row">
                                        <span>Subtotal (<span id="item-count"><?php echo $form_data['quantity']; ?></span> item)</span>
                                        <span id="subtotal">Rp <?php echo number_format($pricing['subtotal'], 0, ',', '.'); ?></span>
                                    </div>
                                    <div class="summary-row">
                                        <span>Ongkos Kirim</span>
                                        <span>Rp <?php echo number_format($pricing['shipping_cost'], 0, ',', '.'); ?></span>
                                    </div>
                                    <div class="summary-row total">
                                        <span>Total</span>
                                        <span id="total">Rp <?php echo number_format($pricing['total'], 0, ',', '.'); ?></span>
                                    </div>
                                </div>

                                <button type="button" onclick="handleOrderSubmit()" class="btn-submit" id="submitBtn" <?php echo !$profile_complete ? 'disabled' : ''; ?>>
                                    <i class="fas fa-shopping-cart"></i>
                                    <?php echo $profile_complete ? 'Lanjut ke Pembayaran' : 'Lengkapi Profil Dulu'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- JavaScript -->
    <!-- <script src="../logic/order/order.js"></script> -->

    <script>
        // Pass PHP data to JavaScript
        const productPrice = <?php echo $product_info['base_price']; ?>;
        const shippingCost = <?php echo $pricing['shipping_cost']; ?>;
        const profileComplete = <?php echo $profile_complete ? 'true' : 'false'; ?>;
        const originalPhone = "<?php echo htmlspecialchars($user_profile['phone'] ?? ''); ?>";
        const originalAddress = "<?php echo htmlspecialchars($user_profile['alamat'] ?? ''); ?>";
        const productColors = <?php echo json_encode($product_info['colors']); ?>;

        let isSubmitting = false;

        // Quantity change function
        function changeQuantity(change) {
            if (!profileComplete) return;

            const quantityInput = document.getElementById('quantity');
            let currentQuantity = parseInt(quantityInput.value);
            let newQuantity = currentQuantity + change;

            if (newQuantity < 1) newQuantity = 1;
            if (newQuantity > 10) newQuantity = 10;

            quantityInput.value = newQuantity;
            updateOrderSummary(newQuantity);
        }

        function updateOrderSummary(quantity) {
            const subtotal = productPrice * quantity;
            const total = subtotal + shippingCost;

            document.getElementById('item-count').textContent = quantity;
            document.getElementById('subtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
            document.getElementById('total').textContent = 'Rp ' + total.toLocaleString('id-ID');
        }

        // Enable phone editing
        function enablePhoneEdit() {
            const phoneInput = document.getElementById('phone');
            phoneInput.removeAttribute('readonly');
            phoneInput.focus();
            phoneInput.style.borderColor = '#f59e0b';
            phoneInput.style.background = '#fffbeb';
        }

        // Enable address editing
        function enableAddressEdit() {
            const addressInput = document.getElementById('address');
            addressInput.removeAttribute('readonly');
            addressInput.focus();
            addressInput.style.borderColor = '#f59e0b';
            addressInput.style.background = '#fffbeb';
        }

        // Handle order submit button click
        function handleOrderSubmit() {
            if (!profileComplete) {
                alert('Lengkapi profil di dashboard terlebih dahulu');
                return;
            }

            if (isSubmitting) {
                return;
            }

            // Validate form
            const color = document.getElementById('color').value;
            const phone = document.getElementById('phone').value.trim();
            const address = document.getElementById('address').value.trim();

            if (!color || !phone || !address) {
                alert('Mohon lengkapi semua field yang wajib diisi');
                return;
            }

            if (address.length < 10) {
                alert('Alamat terlalu singkat (minimal 10 karakter)');
                return;
            }

            // Check for profile changes first
            if (checkProfileChanges()) {
                // No changes, show order confirmation
                showOrderConfirmation();
            }
        }

        // Show order confirmation modal
        function showOrderConfirmation() {
            const color = document.getElementById('color').value;
            const quantity = document.getElementById('quantity').value;
            const colorName = productColors[color] || color;
            const total = (productPrice * quantity) + shippingCost;

            document.getElementById('confirm-color').textContent = colorName;
            document.getElementById('confirm-quantity').textContent = quantity + ' unit';
            document.getElementById('confirm-total').textContent = 'Rp ' + total.toLocaleString('id-ID');

            document.getElementById('orderConfirmationModal').style.display = 'flex';
        }

        // FIXED: Confirm order submission using hidden form
        function confirmOrder() {
            if (isSubmitting) return;

            isSubmitting = true;

            // Update button states
            const confirmBtn = document.getElementById('confirmBtn');
            const submitBtn = document.getElementById('submitBtn');

            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

            // Copy data to hidden form
            document.getElementById('hidden-color').value = document.getElementById('color').value;
            document.getElementById('hidden-quantity').value = document.getElementById('quantity').value;
            document.getElementById('hidden-phone').value = document.getElementById('phone').value;
            document.getElementById('hidden-address').value = document.getElementById('address').value;

            // Hide modal
            document.getElementById('orderConfirmationModal').style.display = 'none';

            // Submit hidden form
            setTimeout(() => {
                document.getElementById('hiddenOrderForm').submit();
            }, 100);
        }

        // Cancel order
        function cancelOrder() {
            document.getElementById('orderConfirmationModal').style.display = 'none';
        }

        // Check for profile changes before form submission
        function checkProfileChanges() {
            if (!profileComplete) return true;

            const currentPhone = document.getElementById('phone').value.trim();
            const currentAddress = document.getElementById('address').value.trim();

            const phoneChanged = currentPhone !== originalPhone;
            const addressChanged = currentAddress !== originalAddress;

            if (phoneChanged || addressChanged) {
                let changes = [];
                if (phoneChanged) {
                    changes.push(`<div class="change-item"><strong>Nomor Telepon:</strong><br>Dari: ${originalPhone}<br>Ke: ${currentPhone}</div>`);
                }
                if (addressChanged) {
                    changes.push(`<div class="change-item"><strong>Alamat:</strong><br>Dari: ${originalAddress.substring(0, 50)}...<br>Ke: ${currentAddress.substring(0, 50)}...</div>`);
                }

                document.getElementById('profileChanges').innerHTML = changes.join('');
                document.getElementById('profileChangeModal').style.display = 'flex';
                return false;
            }

            return true;
        }

        function confirmProfileChange() {
            document.getElementById('profileChangeModal').style.display = 'none';
            showOrderConfirmation();
        }

        function cancelProfileChange() {
            document.getElementById('profileChangeModal').style.display = 'none';
            // Reset fields
            document.getElementById('phone').value = originalPhone;
            document.getElementById('address').value = originalAddress;

            // Reset styles
            const phoneInput = document.getElementById('phone');
            const addressInput = document.getElementById('address');

            phoneInput.setAttribute('readonly', true);
            addressInput.setAttribute('readonly', true);

            phoneInput.style.borderColor = '';
            phoneInput.style.background = '';
            addressInput.style.borderColor = '';
            addressInput.style.background = '';
        }

        // Handle page unload warning
        document.addEventListener('DOMContentLoaded', function() {
            let formDirty = false;
            const formInputs = document.querySelectorAll('#orderForm input, #orderForm select, #orderForm textarea');

            formInputs.forEach(input => {
                input.addEventListener('change', () => {
                    formDirty = true;
                });
            });

            window.addEventListener('beforeunload', function(e) {
                if (formDirty && !isSubmitting) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
        });
    </script>
    <!-- Chatbot Widget -->
    <script src="../logic/chatbot/chatbot-widget.js"></script>
</body>

</html>