<?php
session_start();

// Include database
require_once '../login-register/database.php';

// Check if user has pending order
if (isset($_SESSION['pending_order'])) {
    $order_data = $_SESSION['pending_order'];
    
    try {
        // Delete the pending order from database
        $stmt = $pdo->prepare("DELETE FROM payment WHERE id_order = ? AND status = 'Pesanan Dibuat'");
        $stmt->execute([$order_data['order_id']]);
        
        // Clear session
        unset($_SESSION['pending_order']);
        
        // Return success response
        echo json_encode(['success' => true, 'message' => 'Pending order deleted']);
    } catch (PDOException $e) {
        error_log("Error deleting pending order: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No pending order found']);
}
?>
