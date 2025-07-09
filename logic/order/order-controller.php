<?php
// Order Controller - Handle Form Submissions (FIXED VERSION)
session_start();
require_once '../login-register/database.php';
require_once 'order-logic.php';

// Initialize order logic
$orderLogic = new OrderLogic($pdo);

// Check if user is logged in
if (!$orderLogic->checkUserLogin()) {
    header('Location: ../../view/login-register.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order'])) {
    try {
        // Get user ID
        $user_id = $orderLogic->getUserId();
        
        if (!$user_id) {
            $_SESSION['order_error'] = "Sesi login tidak valid. Silakan login kembali.";
            header('Location: ../../view/order.php');
            exit();
        }
        
        // Check profile completeness
        if (!$orderLogic->checkProfileCompleteness($user_id)) {
            $_SESSION['order_error'] = "Anda harus melengkapi nomor handphone dan alamat di dashboard profil terlebih dahulu.";
            header('Location: ../../view/order.php');
            exit();
        }
        
        // Get and validate form data
        $order_data = [
            'color' => $_POST['color'] ?? '',
            'quantity' => $_POST['quantity'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'address' => $_POST['address'] ?? ''
        ];
        
        // Validate order data
        $validation_errors = $orderLogic->validateOrderData($order_data);
        
        if (!empty($validation_errors)) {
            $_SESSION['order_error'] = implode(', ', $validation_errors);
            header('Location: ../../view/order.php');
            exit();
        }
        
        // Update user profile if needed
        $orderLogic->updateUserProfile($user_id, $order_data['phone'], $order_data['address']);
        
        // Calculate pricing
        $pricing = $orderLogic->calculateOrderPricing($order_data['quantity']);
        
        // Generate unique order ID
        $order_id = $orderLogic->generateOrderId($user_id);
        
        // REMOVED: Database insertion - moved to payment controller
        // Only create pending order session for payment page
        $_SESSION['pending_order'] = [
            'order_id' => $order_id,
            'user_id' => $user_id,
            'color' => $order_data['color'],
            'quantity' => (int)$order_data['quantity'],
            'phone' => $order_data['phone'],
            'address' => $order_data['address'],
            'subtotal' => $pricing['subtotal'],
            'shipping_cost' => $pricing['shipping_cost'],
            'total' => $pricing['total'],
            'created_at' => time()
        ];
        
        // Log order creation attempt
        $orderLogic->logOrderActivity($user_id, 'order_initiated', [
            'order_id' => $order_id,
            'color' => $order_data['color'],
            'quantity' => $order_data['quantity'],
            'total' => $pricing['total']
        ]);
        
        // Clear any previous errors
        unset($_SESSION['order_error']);
        
        // Redirect to payment page
        header('Location: ../../view/payment.php');
        exit();
        
    } catch (Exception $e) {
        error_log("Order submission error: " . $e->getMessage());
        $_SESSION['order_error'] = "Terjadi kesalahan sistem: " . $e->getMessage();
        header('Location: ../../view/order.php');
        exit();
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        $user_id = $orderLogic->getUserId();
        
        switch ($_POST['action']) {
            case 'check_profile':
                $profile_complete = $orderLogic->checkProfileCompleteness($user_id);
                $user_profile = $orderLogic->getUserProfile($user_id);
                
                echo json_encode([
                    'success' => true,
                    'profile_complete' => $profile_complete,
                    'profile' => $user_profile
                ]);
                break;
                
            case 'calculate_pricing':
                $quantity = (int)($_POST['quantity'] ?? 1);
                if ($quantity < 1 || $quantity > 10) {
                    throw new Exception("Jumlah tidak valid");
                }
                
                $pricing = $orderLogic->calculateOrderPricing($quantity);
                echo json_encode([
                    'success' => true,
                    'pricing' => $pricing
                ]);
                break;
                
            case 'validate_address':
                $address = trim($_POST['address'] ?? '');
                $is_valid = strlen($address) >= 10;
                
                echo json_encode([
                    'success' => true,
                    'valid' => $is_valid,
                    'message' => $is_valid ? 'Alamat valid' : 'Alamat minimal 10 karakter'
                ]);
                break;
                
            default:
                throw new Exception("Action tidak valid");
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit();
}

// If no valid action, redirect to order page
header('Location: ../../view/order.php');
exit();
?>
