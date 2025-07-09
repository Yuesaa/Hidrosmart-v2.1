<?php
// Enhanced Dashboard Controller with improved features
require_once '../logic/login-register/database.php';

class DashboardController {
    private $pdo;
    private $user_id;
    
    public function __construct($database) {
        $this->pdo = $database;
        $this->user_id = $_SESSION['user_id'] ?? null;
        
        if (!$this->user_id) {
            $this->redirectToLogin();
        }
    }
    
    private function redirectToLogin() {
        $login_required = urlencode('Harus login terlebih dahulu');
        header("Location: login-register.php?tab=login&login_required=$login_required");
        exit();
    }
    
    // Get user dashboard statistics
    public function getDashboardStats() {
        $stats = [
            'total_orders' => 0,
            'completed_orders' => 0,
            'pending_orders' => 0,
            'total_spent' => 0
        ];
        
        try {
            // Get order statistics
            $query = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(CASE WHEN status = 'Diterima Customer' THEN 1 ELSE 0 END) as completed_orders,
                        SUM(total_harga) as total_spent
                      FROM payment 
                      WHERE id_pengguna = ?";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$this->user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $stats['total_orders'] = (int)$result['total_orders'];
                $stats['completed_orders'] = (int)$result['completed_orders'];
                $stats['pending_orders'] = $stats['total_orders'] - $stats['completed_orders'];
                $stats['total_spent'] = (float)($result['total_spent'] ?? 0);
            }
            
        } catch (PDOException $e) {
            error_log("Dashboard stats error: " . $e->getMessage());
        }
        
        return $stats;
    }
    
    // Get user orders with enhanced payment method info
    public function getUserOrders($status_filter = 'all') {
        try {
            $query = "SELECT p.*, 
                            (SELECT description FROM order_tracking ot 
                             WHERE ot.order_id = p.id_order 
                             ORDER BY ot.created_at DESC LIMIT 1) as latest_tracking
                      FROM payment p 
                      WHERE p.id_pengguna = ?";
            
            $params = [$this->user_id];
            
            if ($status_filter !== 'all') {
                $query .= " AND p.status = ?";
                $params[] = $status_filter;
            }
            
            $query .= " ORDER BY p.tanggal_transaksi DESC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get orders error: " . $e->getMessage());
            return [];
        }
    }
    
    // Enhanced order tracking with improved timeline logic
    public function getOrderTracking($order_id) {
        try {
            // Verify order belongs to user
            $verify_query = "SELECT id_order, metode_pembayaran FROM payment WHERE id_order = ? AND id_pengguna = ?";
            $verify_stmt = $this->pdo->prepare($verify_query);
            $verify_stmt->execute([$order_id, $this->user_id]);
            $order_info = $verify_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order_info) {
                return [];
            }
            
            // Get tracking data
            $query = "SELECT * FROM order_tracking 
                      WHERE order_id = ? 
                      ORDER BY created_at ASC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$order_id]);
            $tracking = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no tracking data, create default based on order status
            if (empty($tracking)) {
                $order_query = "SELECT status, tanggal_transaksi, metode_pembayaran FROM payment WHERE id_order = ?";
                $order_stmt = $this->pdo->prepare($order_query);
                $order_stmt->execute([$order_id]);
                $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($order) {
                    $tracking = $this->generateDefaultTracking($order_id, $order['status'], $order['tanggal_transaksi'], $order['metode_pembayaran']);
                }
            }
            
            return [
                'tracking' => $tracking,
                'payment_method' => $order_info['metode_pembayaran']
            ];
            
        } catch (PDOException $e) {
            error_log("Get tracking error: " . $e->getMessage());
            return [];
        }
    }
    
    // Generate default tracking timeline with improved logic
    private function generateDefaultTracking($order_id, $status, $order_date, $payment_method = null) {
        $timeline = [];
        
        // Define timeline based on payment method
        if ($payment_method === 'cod') {
            // COD: 4 stages (no payment confirmation needed)
            $statuses = [
                'Pesanan Dibuat' => 'Pesanan Anda telah berhasil dibuat dengan metode COD dan akan segera diproses.',
                'Sedang Dikemas' => 'Pesanan Anda sedang dikemas dengan hati-hati oleh tim kami.',
                'Sedang Dalam Perjalanan' => 'Pesanan Anda sedang dalam perjalanan menuju alamat tujuan.',
                'Diterima Customer' => 'Pesanan telah berhasil diterima. Terima kasih telah berbelanja!'
            ];
        } else {
            // Bank Transfer / E-wallet: 5 stages (includes payment confirmation)
            $statuses = [
                'Pembayaran Dikonfirmasi' => 'Pembayaran telah dikonfirmasi. Pesanan Anda akan segera diproses.',
                'Pesanan Dibuat' => 'Pesanan Anda berhasil dicatat dan akan segera dikemas.',
                'Sedang Dikemas' => 'Pesanan Anda sedang dikemas dengan hati-hati oleh tim kami.',
                'Sedang Dalam Perjalanan' => 'Pesanan Anda sedang dalam perjalanan menuju alamat tujuan.',
                'Diterima Customer' => 'Pesanan telah berhasil diterima. Terima kasih telah berbelanja!'
            ];
        }
        
        $current_reached = false;
        foreach ($statuses as $timeline_status => $description) {
            if ($timeline_status === $status) {
                $current_reached = true;
            }
            
            if (!$current_reached || $timeline_status === $status) {
                $timeline[] = [
                    'status' => $timeline_status,
                    'description' => $description,
                    'created_at' => $order_date
                ];
            }
            
            if ($timeline_status === $status) {
                break;
            }
        }
        
        return $timeline;
    }
    
    // Get user contact messages with read status
    public function getUserContactMessages() {
        try {
            $query = "SELECT c.*, 
                            CASE 
                                WHEN c.read_at IS NOT NULL THEN 'read'
                                ELSE 'unread'
                            END as read_status
                      FROM contact c 
                      WHERE c.id_pengguna = ?
                      ORDER BY c.tanggal_submit DESC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$this->user_id]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get contact messages error: " . $e->getMessage());
            return [];
        }
    }
    
    // Get user reviews
    public function getUserReviews() {
        try {
            $query = "SELECT pr.*, p.color, p.kuantitas, p.tanggal_transaksi
                      FROM product_reviews pr
                      JOIN payment p ON pr.order_id = p.id_order
                      WHERE pr.user_id = ?
                      ORDER BY pr.created_at DESC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$this->user_id]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get reviews error: " . $e->getMessage());
            return [];
        }
    }
    
    // Get orders eligible for review
    public function getReviewableOrders() {
        try {
            $query = "SELECT p.* FROM payment p
                      WHERE p.id_pengguna = ? 
                      AND p.status = 'Diterima Customer'
                      AND p.id_order NOT IN (
                          SELECT order_id FROM product_reviews WHERE user_id = ?
                      )
                      ORDER BY p.tanggal_transaksi DESC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$this->user_id, $this->user_id]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get reviewable orders error: " . $e->getMessage());
            return [];
        }
    }
    
    // Submit product review
    public function submitReview($order_id, $rating, $review_text) {
        try {
            // Verify order belongs to user and is completed
            $verify_query = "SELECT id_order FROM payment 
                            WHERE id_order = ? AND id_pengguna = ? AND status = 'Diterima Customer'";
            $verify_stmt = $this->pdo->prepare($verify_query);
            $verify_stmt->execute([$order_id, $this->user_id]);
            
            if (!$verify_stmt->fetch()) {
                throw new Exception('Pesanan tidak valid atau belum selesai');
            }
            
            // Check if review already exists
            $check_query = "SELECT id FROM product_reviews WHERE user_id = ? AND order_id = ?";
            $check_stmt = $this->pdo->prepare($check_query);
            $check_stmt->execute([$this->user_id, $order_id]);
            
            if ($check_stmt->fetch()) {
                throw new Exception('Ulasan untuk pesanan ini sudah ada');
            }
            
            // Validate rating
            if ($rating < 1 || $rating > 5) {
                throw new Exception('Rating harus antara 1-5');
            }
            
            // Validate review text
            if (strlen(trim($review_text)) < 10) {
                throw new Exception('Ulasan minimal 10 karakter');
            }
            
            // Insert review
            $insert_query = "INSERT INTO product_reviews (user_id, order_id, rating, review_text) 
                            VALUES (?, ?, ?, ?)";
            $insert_stmt = $this->pdo->prepare($insert_query);
            
            if ($insert_stmt->execute([$this->user_id, $order_id, $rating, $review_text])) {
                return ['success' => true, 'message' => 'Ulasan berhasil dikirim'];
            } else {
                throw new Exception('Gagal mengirim ulasan');
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Get warranty claims
    public function getWarrantyClaims() {
        try {
            $query = "SELECT g.*, p.color, p.kuantitas, p.tanggal_transaksi
                      FROM guarantee g
                      JOIN payment p ON g.id_order = p.id_order
                      WHERE g.id_pengguna = ?
                      ORDER BY g.tanggal_klaim DESC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$this->user_id]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get warranty claims error: " . $e->getMessage());
            return [];
        }
    }
    
    // Get user profile
    public function getUserProfile() {
        try {
            $query = "SELECT * FROM pengguna WHERE id_pengguna = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$this->user_id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get profile error: " . $e->getMessage());
            return null;
        }
    }
    
    // Enhanced profile update with better validations
    public function updateProfile($name, $email, $phone, $alamat, $current_password = '', $new_password = '') {
        try {
            // Validate required fields
            if (empty($name) || empty($email)) {
                throw new Exception('Nama dan email harus diisi');
            }
            
            // Validate name
            if (strlen($name) < 2) {
                throw new Exception('Nama minimal 2 karakter');
            }
            
            // Check email uniqueness
            $email_check = "SELECT id_pengguna FROM pengguna WHERE email = ? AND id_pengguna != ?";
            $email_stmt = $this->pdo->prepare($email_check);
            $email_stmt->execute([$email, $this->user_id]);
            
            if ($email_stmt->fetch()) {
                throw new Exception('Email sudah digunakan oleh pengguna lain');
            }
            
            // Validate phone number
            if (!empty($phone)) {
                $phone = preg_replace('/[\s\-]/', '', $phone);
                
                if (!preg_match('/^\+62/', $phone)) {
                    throw new Exception('Nomor telepon harus dimulai dengan +62');
                }
                
                $phone_digits = substr($phone, 3);
                if (!preg_match('/^\d{10,13}$/', $phone_digits)) {
                    throw new Exception('Nomor telepon harus 10-13 digit setelah +62');
                }
                
                if (!ctype_digit($phone_digits)) {
                    throw new Exception('Nomor telepon hanya boleh berisi angka');
                }
            }
            
            // Validate address length
            if (!empty($alamat) && strlen($alamat) > 500) {
                throw new Exception('Alamat maksimal 500 karakter');
            }
            
            // Handle password update
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    throw new Exception('Password lama harus diisi untuk mengubah password');
                }
                
                // Verify current password
                $pass_query = "SELECT password FROM pengguna WHERE id_pengguna = ?";
                $pass_stmt = $this->pdo->prepare($pass_query);
                $pass_stmt->execute([$this->user_id]);
                $user_pass = $pass_stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!password_verify($current_password, $user_pass['password'])) {
                    throw new Exception('Password lama tidak benar');
                }
                
                if (password_verify($new_password, $user_pass['password'])) {
                    throw new Exception('Password baru harus berbeda dengan password lama');
                }
                
                if (strlen($new_password) < 6) {
                    throw new Exception('Password baru minimal 6 karakter');
                }
                
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE pengguna SET name = ?, email = ?, phone = ?, alamat = ?, password = ? WHERE id_pengguna = ?";
                $params = [$name, $email, $phone, $alamat, $hashed_password, $this->user_id];
            } else {
                $update_query = "UPDATE pengguna SET name = ?, email = ?, phone = ?, alamat = ? WHERE id_pengguna = ?";
                $params = [$name, $email, $phone, $alamat, $this->user_id];
            }
            
            $stmt = $this->pdo->prepare($update_query);
            
            if ($stmt->execute($params)) {
                $_SESSION['username'] = $name;
                return ['success' => true, 'message' => 'Profil berhasil diperbarui'];
            } else {
                throw new Exception('Gagal memperbarui profil');
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Enhanced avatar upload
    public function uploadAvatar($file) {
        try {
            if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('File tidak valid atau terjadi kesalahan upload');
            }
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $file_type = finfo_file($file_info, $file['tmp_name']);
            finfo_close($file_info);
            
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('Format file tidak didukung. Gunakan JPG, PNG, atau GIF');
            }
            
            // Validate file size (max 2MB)
            if ($file['size'] > 2 * 1024 * 1024) {
                throw new Exception('Ukuran file terlalu besar. Maksimal 2MB');
            }
            
            // Create upload directory if not exists
            $upload_dir = "../logic/user/avatars/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $this->user_id . '_' . time() . '.' . $extension;
            $target_path = $upload_dir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                // Delete old avatar if exists
                $old_avatar_query = "SELECT avatar FROM pengguna WHERE id_pengguna = ?";
                $old_stmt = $this->pdo->prepare($old_avatar_query);
                $old_stmt->execute([$this->user_id]);
                $old_avatar = $old_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($old_avatar && !empty($old_avatar['avatar'])) {
                    $old_file = $upload_dir . $old_avatar['avatar'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                
                // Update database
                $update_query = "UPDATE pengguna SET avatar = ? WHERE id_pengguna = ?";
                $stmt = $this->pdo->prepare($update_query);
                
                if ($stmt->execute([$filename, $this->user_id])) {
                    return ['success' => true, 'message' => 'Avatar berhasil diupload', 'filename' => $filename];
                } else {
                    unlink($target_path);
                    throw new Exception('Gagal menyimpan avatar ke database');
                }
            } else {
                throw new Exception('Gagal mengupload file');
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Check if user profile is complete
    public function isProfileComplete() {
        $profile = $this->getUserProfile();
        return !empty($profile['phone']) && !empty($profile['alamat']);
    }
    
    // Get dashboard insights and recommendations
    public function getDashboardInsights() {
        try {
            $insights = [];
            
            // Check profile completion
            if (!$this->isProfileComplete()) {
                $insights[] = [
                    'type' => 'warning',
                    'title' => 'Lengkapi Profil',
                    'message' => 'Lengkapi nomor telepon dan alamat untuk dapat melakukan pemesanan',
                    'action' => 'Lengkapi Sekarang',
                    'action_tab' => 'profil'
                ];
            }
            
            // Check for pending reviews
            $reviewable = $this->getReviewableOrders();
            if (!empty($reviewable)) {
                $insights[] = [
                    'type' => 'info',
                    'title' => 'Berikan Ulasan',
                    'message' => 'Anda memiliki ' . count($reviewable) . ' pesanan yang dapat diulas',
                    'action' => 'Beri Ulasan',
                    'action_tab' => 'ulasan'
                ];
            }
            
            // Check for unread contact responses
            $contact_messages = $this->getUserContactMessages();
            $unread_count = count(array_filter($contact_messages, function($msg) {
                return $msg['read_status'] === 'unread';
            }));
            
            if ($unread_count > 0) {
                $insights[] = [
                    'type' => 'info',
                    'title' => 'Pesan Belum Dibaca',
                    'message' => 'Anda memiliki ' . $unread_count . ' pesan yang belum dibaca admin',
                    'action' => 'Lihat Pesan',
                    'action_tab' => 'suggestion'
                ];
            }
            
            // Check for orders in transit
            $orders = $this->getUserOrders();
            $in_transit = array_filter($orders, function($order) {
                return in_array($order['status'], ['Sedang Dikemas', 'Sedang Dalam Perjalanan']);
            });
            
            if (!empty($in_transit)) {
                $insights[] = [
                    'type' => 'success',
                    'title' => 'Pesanan Dalam Perjalanan',
                    'message' => 'Anda memiliki ' . count($in_transit) . ' pesanan yang sedang dalam perjalanan',
                    'action' => 'Lacak Pesanan',
                    'action_tab' => 'status-pesanan'
                ];
            }
            
            return $insights;
            
        } catch (Exception $e) {
            error_log("Get insights error: " . $e->getMessage());
            return [];
        }
    }
}
?>
