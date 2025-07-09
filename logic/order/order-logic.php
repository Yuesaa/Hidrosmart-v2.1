<?php
// Order Logic - Business Logic Layer
class OrderLogic {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Check if user is logged in
    public function checkUserLogin() {
        return isset($_SESSION['user_id']) || isset($_SESSION['username']);
    }
    
    // Get user ID from session
    public function getUserId() {
        $user_id = $_SESSION['user_id'] ?? null;
        
        if (!$user_id && isset($_SESSION['username'])) {
            try {
                $stmt = $this->pdo->prepare("SELECT id_pengguna FROM pengguna WHERE name = ?");
                $stmt->execute([$_SESSION['username']]);
                $row = $stmt->fetch();
                if ($row) {
                    $user_id = $row['id_pengguna'];
                    $_SESSION['user_id'] = $user_id; // Cache for future use
                }
            } catch (PDOException $e) {
                error_log("Error getting user ID: " . $e->getMessage());
            }
        }
        
        return $user_id;
    }
    
    // Check if user profile is complete
    public function checkProfileCompleteness($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT phone, alamat FROM pengguna WHERE id_pengguna = ?");
            $stmt->execute([$user_id]);
            $user_profile = $stmt->fetch();
            
            return $user_profile && !empty($user_profile['phone']) && !empty($user_profile['alamat']);
        } catch (PDOException $e) {
            error_log("Error checking profile completeness: " . $e->getMessage());
            return false;
        }
    }
    
    // Get user profile data
    public function getUserProfile($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM pengguna WHERE id_pengguna = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting user profile: " . $e->getMessage());
            return null;
        }
    }
    
    // Update user profile
    public function updateUserProfile($user_id, $phone, $address) {
        try {
            $stmt = $this->pdo->prepare("UPDATE pengguna SET phone = ?, alamat = ? WHERE id_pengguna = ?");
            return $stmt->execute([$phone, $address, $user_id]);
        } catch (PDOException $e) {
            error_log("Error updating user profile: " . $e->getMessage());
            return false;
        }
    }
    
    // Validate order data
    public function validateOrderData($data) {
        $errors = [];
        
        // Validate color
        $valid_colors = ['black', 'white', 'blue', 'gray'];
        if (empty($data['color']) || !in_array($data['color'], $valid_colors)) {
            $errors[] = "Warna produk tidak valid";
        }
        
        // Validate quantity
        if (empty($data['quantity']) || !is_numeric($data['quantity']) || $data['quantity'] < 1 || $data['quantity'] > 10) {
            $errors[] = "Jumlah produk harus antara 1-10 unit";
        }
        
        // Validate phone
        if (empty($data['phone']) || strlen(trim($data['phone'])) < 10) {
            $errors[] = "Nomor telepon minimal 10 karakter";
        }
        
        // Validate address
        if (empty($data['address']) || strlen(trim($data['address'])) < 10) {
            $errors[] = "Alamat pengiriman minimal 10 karakter";
        }
        
        return $errors;
    }
    
    // Calculate order pricing
    public function calculateOrderPricing($quantity) {
        $base_price = 299000; // Base price per unit
        $shipping_cost = 15000; // Fixed shipping cost
        
        $subtotal = $base_price * $quantity;
        $total = $subtotal + $shipping_cost;
        
        return [
            'base_price' => $base_price,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'shipping_cost' => $shipping_cost,
            'total' => $total
        ];
    }
    
    // Generate unique order ID with user ID
    public function generateOrderId($user_id) {
        $prefix = 'HISM';
        $user_suffix = str_pad($user_id, 2, '0', STR_PAD_LEFT);
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        return $prefix . $user_suffix . $random;
    }
    
    // Check if pending order exists and is valid
    public function checkPendingOrder() {
        if (!isset($_SESSION['pending_order'])) {
            return false;
        }
        
        // Check if order is not too old (30 minutes)
        $created_at = $_SESSION['pending_order']['created_at'] ?? 0;
        $max_age = 30 * 60; // 30 minutes
        
        if (time() - $created_at > $max_age) {
            unset($_SESSION['pending_order']);
            return false;
        }
        
        return true;
    }
    
    // Clear pending order
    public function clearPendingOrder() {
        unset($_SESSION['pending_order']);
    }
    
    // Get product information
    public function getProductInfo() {
        return [
            'name' => 'HidroSmart Tumbler',
            'base_price' => 299000,
            'description' => 'Smart tumbler dengan teknologi monitoring kesehatan',
            'colors' => [
                'black' => 'Hitam',
                'white' => 'Putih', 
                'blue' => 'Biru',
                'gray' => 'Abu-abu'
            ],
            'images' => [
                'black' => '../images/hidrosmart-black.jpg',
                'white' => '../images/hidrosmart-white.jpg',
                'blue' => '../images/hidrosmart-blue.jpg',
                'gray' => '../images/hidrosmart-gray.jpg'
            ]
        ];
    }
    
    // Get color name in Indonesian
    public function getColorName($color) {
        $colors = [
            'black' => 'Hitam',
            'white' => 'Putih',
            'blue' => 'Biru', 
            'gray' => 'Abu-abu'
        ];
        return $colors[$color] ?? ucfirst($color);
    }
    
    // Get product image path
    public function getProductImage($color) {
        $images = [
            'black' => '/images/tumbler-black.png',
            'white' => '/images/tumbler-white.png',
            'blue' => '/images/tumbler-blue.png',
            'gray' => '/images/tumbler-gray.png'
        ];
        return $images[$color] ?? '/images/tumbler-all.png';
    }
    
    // Log order activity (create table if needed)
    public function logOrderActivity($user_id, $action, $details = []) {
        try {
            // Create table if not exists
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS order_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    action VARCHAR(100) NOT NULL,
                    details TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES pengguna(id_pengguna)
                )
            ");
            
            $stmt = $this->pdo->prepare("
                INSERT INTO order_logs (user_id, action, details, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([
                $user_id,
                $action,
                json_encode($details)
            ]);
        } catch (PDOException $e) {
            error_log("Error logging order activity: " . $e->getMessage());
        }
    }
}
?>
