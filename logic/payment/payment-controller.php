<?php
session_start();

// Error reporting untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database
require_once '../login-register/database.php';

// Cek autentikasi
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../view/login-register.php');
    exit();
}

// Cek pending order
if (!isset($_SESSION['pending_order'])) {
    header('Location: ../../view/order.php');
    exit();
}

// Cek method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../view/payment.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_data = $_SESSION['pending_order'];
$payment_method = $_POST['payment_method'] ?? '';

// Validasi metode pembayaran
$valid_methods = ['bank_transfer', 'ewallet', 'cod'];
if (!in_array($payment_method, $valid_methods)) {
    $_SESSION['payment_error'] = 'Metode pembayaran tidak valid';
    header('Location: ../../view/payment.php');
    exit();
}

// Handle file upload untuk bank_transfer dan ewallet
$bukti_transfer = null;
if (in_array($payment_method, ['bank_transfer', 'ewallet'])) {
    $file_field = ($payment_method === 'bank_transfer') ? 'transfer_proof_bank' : 'transfer_proof_ewallet';
    
    if (isset($_FILES[$file_field]) && $_FILES[$file_field]['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "uploads/";
        
        // Buat direktori jika belum ada
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $filename = basename($_FILES[$file_field]["name"]);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (in_array(strtolower($ext), $allowed_ext) && $_FILES[$file_field]['size'] <= 5 * 1024 * 1024) {
            $safe_filename = $order_data['order_id'] . '_' . time() . '.' . $ext;
            $target_file = $upload_dir . $safe_filename;

            if (move_uploaded_file($_FILES[$file_field]["tmp_name"], $target_file)) {
                $bukti_transfer = $safe_filename;
            } else {
                $_SESSION['payment_error'] = 'Gagal mengupload bukti transfer';
                header('Location: ../../view/payment.php');
                exit();
            }
        } else {
            $_SESSION['payment_error'] = 'File tidak valid. Gunakan JPG, PNG, atau PDF maksimal 5MB';
            header('Location: ../../view/payment.php');
            exit();
        }
    }
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Set status berdasarkan metode pembayaran
    $status = '';
    switch ($payment_method) {
        case 'cod':
            $status = 'Pesanan Dibuat';
            break;
        case 'bank_transfer':
            $status = $bukti_transfer ? 'Pembayaran Dikonfirmasi' : 'Menunggu Pembayaran';
            break;
        case 'ewallet':
            $status = $bukti_transfer ? 'Pembayaran Dikonfirmasi' : 'Menunggu Pembayaran';
            break;
        default:
            $status = 'Menunggu Konfirmasi';
    }
    
    // FIXED: Create payment record in database with correct column names
    $stmt = $pdo->prepare("
        INSERT INTO payment (id_order, id_pengguna, kuantitas, color, metode_pembayaran, bukti_transfer, ongkir, subtotal_harga, total_harga, status, tanggal_transaksi) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([
        $order_data['order_id'],
        $user_id,
        $order_data['quantity'],
        $order_data['color'],
        $payment_method, // FIXED: Now properly saving payment method
        $bukti_transfer, // FIXED: Now properly saving uploaded file
        $order_data['shipping_cost'],
        $order_data['subtotal'],
        $order_data['total'],
        $status
    ]);
    
    if (!$result) {
        throw new Exception("Gagal menyimpan data pembayaran");
    }
    
    // MOVED FROM ORDER CONTROLLER: Create initial order tracking entry
    $stmt_tracking = $pdo->prepare("
        INSERT INTO order_tracking (order_id, status, description, created_at) 
        VALUES (?, ?, ?, NOW())
    ");

    $tracking_description = '';
    switch ($payment_method) {
        case 'cod':
            $tracking_description = 'Pesanan Anda telah dikonfirmasi dengan metode COD (Cash on Delivery). Produk akan segera diproses dan dikirim ke alamat tujuan.';
            break;
        case 'bank_transfer':
            if ($bukti_transfer) {
                $tracking_description = 'Bukti transfer telah diterima dan sedang dalam proses verifikasi oleh tim kami. Kami akan mengkonfirmasi pembayaran dalam 1x24 jam.';
            } else {
                $tracking_description = 'Silakan lakukan transfer ke rekening yang telah disediakan dan upload bukti transfer untuk mempercepat proses verifikasi.';
            }
            break;
        case 'ewallet':
            if ($bukti_transfer) {
                $tracking_description = 'Bukti pembayaran e-wallet telah diterima dan sedang dalam proses verifikasi oleh tim kami. Kami akan mengkonfirmasi pembayaran dalam 1x24 jam.';
            } else {
                $tracking_description = 'Silakan lakukan pembayaran melalui e-wallet yang telah disediakan dan upload bukti pembayaran untuk mempercepat proses verifikasi.';
            }
            break;
        default:
            $tracking_description = 'Pesanan Anda telah berhasil dibuat dan sedang menunggu konfirmasi pembayaran. Tim kami akan segera memproses pesanan Anda.';
    }

    $tracking_result = $stmt_tracking->execute([
        $order_data['order_id'],
        $status,
        $tracking_description
    ]);

    if (!$tracking_result) {
        error_log("Warning: Failed to create order tracking entry for order: " . $order_data['order_id']);
        // Don't fail the entire process, just log the warning
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Set success data
    $_SESSION['payment_success'] = [
        'order_id' => $order_data['order_id'],
        'payment_method' => $payment_method,
        'total' => $order_data['total'],
        'status' => $status
    ];
    
    // Clear pending order
    unset($_SESSION['pending_order']);
    
    // Redirect ke success page
    header('Location: ../../view/payment.php?success=1');
    exit();
    
} catch (PDOException $e) {
    $pdo->rollback();
    error_log("Payment error: " . $e->getMessage());
    $_SESSION['payment_error'] = 'Terjadi kesalahan sistem: ' . $e->getMessage();
    header('Location: ../../view/payment.php');
    exit();
} catch (Exception $e) {
    $pdo->rollback();
    error_log("Payment error: " . $e->getMessage());
    $_SESSION['payment_error'] = 'Terjadi kesalahan: ' . $e->getMessage();
    header('Location: ../../view/payment.php');
    exit();
}
?>
