<?php
// admin-controller.php - Enhanced Admin Dashboard Controller

class AdminController
{
    private $pdo;
    private $admin_id;
    private $admin_data;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->checkAuthentication();
        $this->initializeAdmin();
    }

    private function checkAuthentication()
    {
        // Check if user is logged in and is admin
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            header("Location: login-register.php");
            exit();
        }

        // Check if user has admin role
        if ($_SESSION['role'] !== 'admin') {
            header("Location: home.php");
            exit();
        }

        $this->admin_id = $_SESSION['user_id'];
    }

    // ---------------- EMAIL HELPER ----------------
    private function sendNotificationEmail($toEmail, $toName, $subject, $body)
    {
        require_once __DIR__ . '/../login-register/smtp_config.php';
        require_once __DIR__ . '/../../vendor/autoload.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_PORT == 465 ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->SMTPDebug  = SMTP_DEBUG;

            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);
            $mail->send();
        } catch (Exception $e) {
            error_log('Admin email send failed: ' . $mail->ErrorInfo);
        }
    }

    private function buildStatusEmailBody($name, $orderId, $status)
    {
        return "<p>Halo $name,</p>
                <p>Status pesanan <strong>$orderId</strong> Anda telah diperbarui menjadi: <strong>$status</strong>.</p>
                <p>Terima kasih telah berbelanja di HidroSmart.</p>";
    }

    private function buildDeleteEmailBody($name, $orderId, $method)
    {
        return "<p>Halo $name,</p>
                <p>Kami mohon maaf, pesanan <strong>$orderId</strong> telah dibatalkan karena bukti pembayaran untuk metode <strong>$method</strong> tidak valid.</p>
                <p>Silakan lakukan pemesanan ulang dengan metode pembayaran yang sesuai.</p>";
    }

    private function initializeAdmin()
    {
        // Get admin data from pengguna table
        $query = "SELECT * FROM pengguna WHERE id_pengguna = ? AND role = 2";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$this->admin_id]);
        $this->admin_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$this->admin_data) {
            session_destroy();
            header("Location: login-register.php?error=admin_not_found");
            exit();
        }
    }

    public function getAdminData()
    {
        return $this->admin_data;
    }

    public function getDashboardStats()
    {
        // Total Users
        $users_query = "SELECT COUNT(*) as total FROM pengguna WHERE role = 1";
        $users_stmt = $this->pdo->prepare($users_query);
        $users_stmt->execute();
        $total_users = $users_stmt->fetch()['total'];

        // Total Orders
        $orders_query = "SELECT COUNT(*) as total, SUM(total_harga) as revenue FROM payment";
        $orders_stmt = $this->pdo->prepare($orders_query);
        $orders_stmt->execute();
        $orders_data = $orders_stmt->fetch();

        // Orders today
        $today_orders_query = "SELECT COUNT(*) as total FROM payment WHERE DATE(tanggal_transaksi) = CURDATE()";
        $today_stmt = $this->pdo->prepare($today_orders_query);
        $today_stmt->execute();
        $today_orders = $today_stmt->fetch()['total'];

        // Pending orders (not delivered)
        $pending_query = "SELECT COUNT(*) as total FROM payment WHERE status != 'Diterima Customer'";
        $pending_stmt = $this->pdo->prepare($pending_query);
        $pending_stmt->execute();
        $pending_orders = $pending_stmt->fetch()['total'];

        // Initialize active users count
        $active_users = 0;

        // Active users (currently online)
        try {
            $check_table = $this->pdo->query("SHOW TABLES LIKE 'user_sessions'");
            if ($check_table && $check_table->rowCount() > 0) {
                // online within last 15 minutes
                // Count unique users active in the last 15 minutes
            $active_users_query = "SELECT COUNT(DISTINCT user_id) AS total FROM user_sessions WHERE last_activity >= (NOW() - INTERVAL 15 MINUTE)";
                $active_stmt = $this->pdo->prepare($active_users_query);
                $active_stmt->execute();
                $active_users = $active_stmt->fetch()['total'];
            } else {
                $active_users = 0;
            }
        } catch (Exception $e) {
            $active_users = 0;
        }

        // Guarantee claims
        $guarantee_query = "SELECT COUNT(*) as total FROM guarantee";
        $guarantee_stmt = $this->pdo->prepare($guarantee_query);
        $guarantee_stmt->execute();
        $guarantee_claims = $guarantee_stmt->fetch()['total'];

        // Contact messages
        $contact_query = "SELECT COUNT(*) as total FROM contact WHERE read_at IS NULL";
        $contact_stmt = $this->pdo->prepare($contact_query);
        $contact_stmt->execute();
        $unread_messages = $contact_stmt->fetch()['total'];

        // Suggestions / contact messages (total)
        $contact_query = "SELECT COUNT(*) as total FROM contact";
        $contact_stmt = $this->pdo->prepare($contact_query);
        $contact_stmt->execute();
        $total_suggestions = $contact_stmt->fetch()['total'];

        return [
            'total_users' => $total_users,
            'total_orders' => $orders_data['total'] ?? 0,
            'total_revenue' => $orders_data['revenue'] ?? 0,
            'today_orders' => $today_orders,
            'pending_orders' => $pending_orders,
            'active_users' => $active_users,
            'guarantee_claims' => $guarantee_claims,
            'total_suggestions' => $total_suggestions,
            'unread_messages' => $unread_messages
        ];
    }

    public function getRecentActivity()
    {
        $activities = [];

        // Get recent orders with user avatars
        $order_query = "
            SELECT 'order' as type, 
                   CONCAT('Pesanan baru #', p.id_order, ' dari ', u.name) as description,
                   p.tanggal_transaksi as created_at,
                   u.avatar,
                   u.name as user_name
            FROM payment p
            LEFT JOIN pengguna u ON p.id_pengguna = u.id_pengguna
            ORDER BY p.tanggal_transaksi DESC 
            LIMIT 3
        ";
        $stmt = $this->pdo->prepare($order_query);
        $stmt->execute();
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));

        // Get recent reviews with user avatars
        $review_query = "
            SELECT 'review' as type,
                   CONCAT('Ulasan baru dari ', u.name, ' untuk order #', pr.order_id) as description,
                   pr.created_at,
                   u.avatar,
                   u.name as user_name
            FROM product_reviews pr 
            LEFT JOIN pengguna u ON pr.user_id = u.id_pengguna
            ORDER BY pr.created_at DESC 
            LIMIT 2
        ";
        $stmt = $this->pdo->prepare($review_query);
        $stmt->execute();
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));

        // Get recent guarantee claims with user avatars
        $guarantee_query = "
            SELECT 'guarantee' as type,
                   CONCAT('Klaim garansi baru dari ', u.name, ' untuk order #', g.id_order) as description,
                   g.tanggal_klaim as created_at,
                   u.avatar,
                   u.name as user_name
            FROM guarantee g 
            LEFT JOIN pengguna u ON g.id_pengguna = u.id_pengguna
            ORDER BY g.tanggal_klaim DESC 
            LIMIT 2
        ";
        $stmt = $this->pdo->prepare($guarantee_query);
        $stmt->execute();
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));

        // Get recent contact messages with user avatars
        $contact_query = "
            SELECT 'contact' as type,
                   CONCAT('Pesan baru dari ', u.name, ': ', c.subject) as description,
                   c.tanggal_submit as created_at,
                   u.avatar,
                   u.name as user_name
            FROM contact c 
            LEFT JOIN pengguna u ON c.id_pengguna = u.id_pengguna
            ORDER BY c.tanggal_submit DESC 
            LIMIT 2
        ";
        $stmt = $this->pdo->prepare($contact_query);
        $stmt->execute();
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));

        // Sort by date and limit to 10
        usort($activities, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return array_slice($activities, 0, 10);
    }

    public function getAllUsers()
    {
        $query = "SELECT id_pengguna, name, email, phone, alamat, role, avatar,
                         (SELECT COUNT(*) FROM payment WHERE id_pengguna = pengguna.id_pengguna) as total_orders,
                         (SELECT MAX(tanggal_transaksi) FROM payment WHERE id_pengguna = pengguna.id_pengguna) as last_order
                  FROM pengguna 
                  WHERE role = 1 
                  ORDER BY id_pengguna DESC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserDetails($user_id)
    {
        try {
            // Get user basic info
            $user_query = "SELECT * FROM pengguna WHERE id_pengguna = ? AND role = 1";
            $user_stmt = $this->pdo->prepare($user_query);
            $user_stmt->execute([$user_id]);
            $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception('User tidak ditemukan');
            }

            // Get user orders with product images
            $orders_query = "SELECT p.*, 
                            CASE 
                                WHEN p.color = 'black' THEN '../images/hidrosmart-black.jpg'
                                WHEN p.color = 'white' THEN '../images/hidrosmart-white.jpg'
                                WHEN p.color = 'blue' THEN '../images/hidrosmart-blue.jpg'
                                WHEN p.color = 'gray' THEN '../images/hidrosmart-gray.jpg'
                                ELSE '/placeholder.svg?height=40&width=40'
                            END as product_image
                            FROM payment p 
                            WHERE p.id_pengguna = ? 
                            ORDER BY p.tanggal_transaksi DESC";
            $orders_stmt = $this->pdo->prepare($orders_query);
            $orders_stmt->execute([$user_id]);
            $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get user reviews
            $reviews_query = "SELECT pr.*, p.color FROM product_reviews pr 
                             LEFT JOIN payment p ON pr.order_id = p.id_order 
                             WHERE pr.user_id = ? ORDER BY pr.created_at DESC";
            $reviews_stmt = $this->pdo->prepare($reviews_query);
            $reviews_stmt->execute([$user_id]);
            $reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get user guarantee claims
            $guarantee_query = "SELECT * FROM guarantee WHERE id_pengguna = ? ORDER BY tanggal_klaim DESC";
            $guarantee_stmt = $this->pdo->prepare($guarantee_query);
            $guarantee_stmt->execute([$user_id]);
            $guarantees = $guarantee_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get user contact messages
            $contact_query = "SELECT * FROM contact WHERE id_pengguna = ? ORDER BY tanggal_submit DESC";
            $contact_stmt = $this->pdo->prepare($contact_query);
            $contact_stmt->execute([$user_id]);
            $contacts = $contact_stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'user' => $user,
                'orders' => $orders,
                'reviews' => $reviews,
                'guarantees' => $guarantees,
                'contacts' => $contacts,
                'stats' => [
                    'total_orders' => count($orders),
                    'total_spent' => array_sum(array_column($orders, 'total_harga')),
                    'total_reviews' => count($reviews),
                    'total_guarantees' => count($guarantees),
                    'total_contacts' => count($contacts)
                ]
            ];
        } catch (Exception $e) {
            throw new Exception('Gagal mengambil detail user: ' . $e->getMessage());
        }
    }

    public function getAllOrders()
    {
        $query = "SELECT p.*, u.name as customer_name, u.email as customer_email, u.phone,
                         p.metode_pembayaran, p.bukti_transfer AS bukti_pembayaran 
                  FROM payment p 
                  LEFT JOIN pengguna u ON p.id_pengguna = u.id_pengguna 
                  ORDER BY p.tanggal_transaksi DESC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ===================== NOTIFICATIONS =====================
    public function getUnreadNotifications()
    {
        $query = "SELECT * FROM notifikasi WHERE dibaca = 0 ORDER BY waktu DESC LIMIT 10";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markNotificationRead($notif_id)
    {
        $query = "UPDATE notifikasi SET dibaca = 1 WHERE id_notifikasi = ?";
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([$notif_id]);
    }

    public function getGuaranteeClaims()
    {
        $query = "SELECT g.*, u.name as customer_name, u.email as customer_email, u.phone, u.alamat as domisili,
                         p.color, p.kuantitas, p.total_harga,
                         CASE 
                            WHEN g.status_klaim IS NULL THEN 'menunggu'
                            ELSE g.status_klaim 
                         END as status_klaim
                  FROM guarantee g 
                  LEFT JOIN pengguna u ON g.id_pengguna = u.id_pengguna
                  LEFT JOIN payment p ON g.id_order = p.id_order
                  ORDER BY g.tanggal_klaim DESC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getContactMessages()
    {
        // FIXED: Include user avatar in contact messages query
        $query = "SELECT c.*, u.name, u.email, u.phone, u.avatar,
                         c.tanggal_submit as created_at,
                         c.id_saran as id_message,
                         c.subject,
                         c.pesan as message,
                         CASE 
                            WHEN c.read_at IS NULL THEN 'unread'
                            ELSE 'read'
                         END as status
                  FROM contact c 
                  LEFT JOIN pengguna u ON c.id_pengguna = u.id_pengguna
                  ORDER BY c.tanggal_submit DESC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductReviews()
    {
        $query = "SELECT pr.*, p.color, p.kuantitas, p.total_harga, pen.name as customer_name, pen.email as customer_email, pen.avatar,
                         pr.rating, pr.review_text as ulasan, pr.created_at, pr.order_id as id_order
                  FROM product_reviews pr 
                  LEFT JOIN payment p ON pr.order_id = p.id_order
                  LEFT JOIN pengguna pen ON pr.user_id = pen.id_pengguna
                  ORDER BY pr.created_at DESC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateOrderStatus($order_id, $new_status, $notes = '')
    {
        try {
            $this->pdo->beginTransaction();

            // Get current order info
            $order_query = "SELECT metode_pembayaran, status FROM payment WHERE id_order = ?";
            $order_stmt = $this->pdo->prepare($order_query);
            $order_stmt->execute([$order_id]);
            $order = $order_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                throw new Exception('Order tidak ditemukan');
            }

            // Validate status progression based on payment method
            $valid_progression = $this->validateStatusProgression($order['metode_pembayaran'], $order['status'], $new_status);
            
            if (!$valid_progression) {
                throw new Exception('Status progression tidak valid untuk metode pembayaran ini');
            }

            // Update payment status
            $update_query = "UPDATE payment SET status = ? WHERE id_order = ?";
            $update_stmt = $this->pdo->prepare($update_query);
            $update_stmt->execute([$new_status, $order_id]);

            // Add to order tracking if notes provided
            if (!empty($notes)) {
                $tracking_query = "INSERT INTO order_tracking (order_id, status, description, created_at) VALUES (?, ?, ?, NOW())";
                $tracking_stmt = $this->pdo->prepare($tracking_query);
                $tracking_stmt->execute([$order_id, $new_status, $notes]);
            }

            // Kirim email notifikasi ke user
            $userStmt = $this->pdo->prepare("SELECT pen.email, pen.name FROM pengguna pen JOIN payment pay ON pen.id_pengguna = pay.id_pengguna WHERE pay.id_order = ?");
            $userStmt->execute([$order_id]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $subject = "Update Status Pesanan #$order_id";
                $body    = $this->buildStatusEmailBody($user['name'], $order_id, $new_status);
                $this->sendNotificationEmail($user['email'], $user['name'], $subject, $body);
            }

            $this->pdo->commit();
            return ['success' => true, 'message' => 'Status pesanan berhasil diperbarui'];
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw new Exception('Gagal memperbarui status pesanan: ' . $e->getMessage());
        }
    }

    private function validateStatusProgression($payment_method, $current_status, $new_status)
    {
        // Define valid status progressions
        $cod_progression = [
            'Pesanan Dibuat' => ['Sedang Dikemas'],
            'Sedang Dikemas' => ['Sedang Dalam Perjalanan'],
            'Sedang Dalam Perjalanan' => ['Diterima Customer']
        ];

        $bank_ewallet_progression = [
            'Pembayaran Dikonfirmasi' => ['Pesanan Dibuat'],
            'Pesanan Dibuat' => ['Sedang Dikemas'],
            'Sedang Dikemas' => ['Sedang Dalam Perjalanan'],
            'Sedang Dalam Perjalanan' => ['Diterima Customer']
        ];

        $progression = ($payment_method === 'cod') ? $cod_progression : $bank_ewallet_progression;

        // Allow if it's the first status or if it's a valid next step
        if (empty($current_status) || !isset($progression[$current_status])) {
            return true; // Allow initial status setting
        }

        return in_array($new_status, $progression[$current_status]);
    }

    public function getValidNextStatuses($order_id)
    {
        $order_query = "SELECT metode_pembayaran, status FROM payment WHERE id_order = ?";
        $order_stmt = $this->pdo->prepare($order_query);
        $order_stmt->execute([$order_id]);
        $order = $order_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return [];
        }

        $cod_progression = [
            '' => ['Pesanan Dibuat'],
            'Pesanan Dibuat' => ['Sedang Dikemas'],
            'Sedang Dikemas' => ['Sedang Dalam Perjalanan'],
            'Sedang Dalam Perjalanan' => ['Diterima Customer']
        ];

        $bank_ewallet_progression = [
            '' => ['Pembayaran Dikonfirmasi'],
            'Pembayaran Dikonfirmasi' => ['Pesanan Dibuat'],
            'Pesanan Dibuat' => ['Sedang Dikemas'],
            'Sedang Dikemas' => ['Sedang Dalam Perjalanan'],
            'Sedang Dalam Perjalanan' => ['Diterima Customer']
        ];

        $progression = ($order['metode_pembayaran'] === 'cod') ? $cod_progression : $bank_ewallet_progression;
        
        return $progression[$order['status']] ?? [];
    }

    public function updateGuaranteeStatus($guarantee_id, $status, $admin_notes = '')
    {
        try {
            // FIXED: Update guarantee with proper timestamp and status
            $update_query = "UPDATE guarantee SET 
                            status_klaim = ?, 
                            catatan_admin = ?, 
                            tanggal_respon = NOW() 
                            WHERE id_guarantee = ?";
            $stmt = $this->pdo->prepare($update_query);
            $result = $stmt->execute([$status, $admin_notes, $guarantee_id]);

            if ($result) {
                // fetch user info
                $infoStmt = $this->pdo->prepare("SELECT g.id_guarantee, p.email, p.name FROM guarantee g JOIN pengguna p ON p.id_pengguna = g.id_pengguna WHERE g.id_guarantee = ?");
                $infoStmt->execute([$guarantee_id]);
                $info = $infoStmt->fetch(PDO::FETCH_ASSOC);
                if ($info) {
                    $subject = "Status Klaim Garansi #{$info['id_guarantee']}";
                    $body = $this->buildGuaranteeEmailBody($info['name'], $info['id_guarantee'], $status, $admin_notes);
                    $this->sendNotificationEmail($info['email'], $info['name'], $subject, $body);
                }
                return ['success' => true, 'message' => 'Status garansi berhasil diperbarui'];
            } else {
                throw new Exception('Gagal memperbarui status garansi');
            }
        } catch (Exception $e) {
            throw new Exception('Gagal memperbarui status garansi: ' . $e->getMessage());
        }
    }

    public function markMessageAsRead($message_id)
    {
        try {
            // Ensure read_at column exists
            $check_column = "SHOW COLUMNS FROM contact LIKE 'read_at'";
            $stmt = $this->pdo->prepare($check_column);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                // Add column if it doesn't exist
                $add_column = "ALTER TABLE contact ADD COLUMN read_at TIMESTAMP NULL DEFAULT NULL";
                $this->pdo->exec($add_column);
            }

            // Update the message as read
            $update_query = "UPDATE contact SET read_at = NOW() WHERE id_saran = ?";
            $stmt = $this->pdo->prepare($update_query);
            $result = $stmt->execute([$message_id]);

            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Pesan berhasil ditandai sebagai dibaca'];
            } else {
                throw new Exception('Pesan tidak ditemukan atau sudah dibaca');
            }
        } catch (Exception $e) {
            throw new Exception('Gagal menandai pesan sebagai dibaca: ' . $e->getMessage());
        }
    }

    public function deleteUser($user_id)
    {
        try {
            $this->pdo->beginTransaction();

            // Delete related records first
            $delete_orders = "DELETE FROM payment WHERE id_pengguna = ?";
            $this->pdo->prepare($delete_orders)->execute([$user_id]);

            $delete_reviews = "DELETE FROM product_reviews WHERE user_id = ?";
            $this->pdo->prepare($delete_reviews)->execute([$user_id]);

            $delete_guarantees = "DELETE FROM guarantee WHERE id_pengguna = ?";
            $this->pdo->prepare($delete_guarantees)->execute([$user_id]);

            $delete_contact = "DELETE FROM contact WHERE id_pengguna = ?";
            $this->pdo->prepare($delete_contact)->execute([$user_id]);

            $delete_order_logs = "DELETE FROM order_logs WHERE user_id = ?";
            $this->pdo->prepare($delete_order_logs)->execute([$user_id]);

            // Delete user avatar if exists
            $user_query = "SELECT avatar FROM pengguna WHERE id_pengguna = ?";
            $user_stmt = $this->pdo->prepare($user_query);
            $user_stmt->execute([$user_id]);
            $user_data = $user_stmt->fetch();

            if ($user_data && !empty($user_data['avatar'])) {
                $avatar_path = "../logic/user/avatars/" . $user_data['avatar'];
                if (file_exists($avatar_path)) {
                    unlink($avatar_path);
                }
            }

            // Delete user
            $delete_user = "DELETE FROM pengguna WHERE id_pengguna = ? AND role = 1";
            $this->pdo->prepare($delete_user)->execute([$user_id]);

            $this->pdo->commit();
            return ['success' => true, 'message' => 'User berhasil dihapus'];
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw new Exception('Gagal menghapus user: ' . $e->getMessage());
        }
    }

    public function deletePaymentProof($order_id)
    {
        if (empty($order_id)) {
            return ['success' => false, 'message' => 'Order ID kosong'];
        }
        try {
            // get current proof filename
            $stmt = $this->pdo->prepare("SELECT bukti_transfer FROM payment WHERE id_order = ?");
            $stmt->execute([$order_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['bukti_transfer'])) {
                $filePath = __DIR__ . '/../payment/uploads/' . $row['bukti_transfer'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            // update record
            $upd = $this->pdo->prepare("UPDATE payment SET bukti_transfer = NULL, status = 'menunggu' WHERE id_order = ?");
            $upd->execute([$order_id]);
            return ['success' => true, 'message' => 'Bukti pembayaran berhasil dihapus'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete entire order and related files
     */
    public function deleteOrder($order_id)
    {
        if (empty($order_id)) {
            return ['success' => false, 'message' => 'Order ID kosong'];
        }
        try {
            $this->pdo->beginTransaction();

            // remove payment proof file if exists
            $stmt = $this->pdo->prepare("SELECT bukti_transfer FROM payment WHERE id_order = ?");
            $stmt->execute([$order_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['bukti_transfer'])) {
                $filePath = __DIR__ . '/../payment/uploads/' . $row['bukti_transfer'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            // ambil info user dan metode sebelum delete
        $infoStmt = $this->pdo->prepare("SELECT pay.metode_pembayaran, pen.email, pen.name FROM payment pay JOIN pengguna pen ON pen.id_pengguna = pay.id_pengguna WHERE pay.id_order = ?");
        $infoStmt->execute([$order_id]);
        $info = $infoStmt->fetch(PDO::FETCH_ASSOC);

        // delete the order
            $del = $this->pdo->prepare("DELETE FROM payment WHERE id_order = ?");
            $del->execute([$order_id]);

            // delete related order logs (if table has order_id column)
            try {
                $this->pdo->prepare("DELETE FROM order_logs WHERE order_id = ?")->execute([$order_id]);
            } catch (Exception $e) {
                // column may not exist; ignore
            }

            // kirim email pemberitahuan pembatalan
            if ($info) {
                $subject = "Pembatalan Pesanan #$order_id";
                $body    = $this->buildDeleteEmailBody($info['name'], $order_id, $info['metode_pembayaran']);
                $this->sendNotificationEmail($info['email'], $info['name'], $subject, $body);
            }

            $this->pdo->commit();
            return ['success' => true, 'message' => 'Order berhasil dihapus'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function handleAjaxRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
            return false;
        }

        header('Content-Type: application/json');

        try {
            switch ($_POST['action']) {
                case 'update_order_status':
                    $result = $this->updateOrderStatus(
                        $_POST['order_id'] ?? '',
                        $_POST['status'] ?? '',
                        $_POST['notes'] ?? ''
                    );
                    echo json_encode($result);
                    break;

                case 'get_valid_statuses':
                    $statuses = $this->getValidNextStatuses($_POST['order_id'] ?? '');
                    echo json_encode(['success' => true, 'data' => $statuses]);
                    break;

                case 'update_guarantee_status':
                    $result = $this->updateGuaranteeStatus(
                        $_POST['guarantee_id'] ?? '',
                        $_POST['status'] ?? '',
                        $_POST['admin_notes'] ?? ''
                    );
                    echo json_encode($result);
                    break;

                case 'mark_message_read':
                    $result = $this->markMessageAsRead($_POST['message_id'] ?? '');
                    echo json_encode($result);
                    break;

                case 'get_unread_notifications':
                    $notifs = $this->getUnreadNotifications();
                    echo json_encode(['success' => true, 'data' => $notifs]);
                    break;

                case 'mark_notification_read':
                    $ok = $this->markNotificationRead($_POST['notif_id'] ?? 0);
                    echo json_encode(['success' => $ok]);
                    break;

                case 'delete_order':
                    $res = $this->deleteOrder($_POST['order_id'] ?? '');
                    echo json_encode($res);
                    break;

                case 'delete_payment_proof':
                    $res = $this->deletePaymentProof($_POST['order_id'] ?? '');
                    echo json_encode($res);
                    break;

                case 'delete_user':
                    $result = $this->deleteUser($_POST['user_id'] ?? '');
                    echo json_encode($result);
                    break;

                case 'get_user_details':
                    $user_details = $this->getUserDetails($_POST['user_id'] ?? '');
                    echo json_encode(['success' => true, 'data' => $user_details]);
                    break;

                                case 'reply_contact':
                    $res = $this->replyToContact($_POST['message_id'] ?? '', $_POST['reply_text'] ?? '');
                    echo json_encode($res);
                    break;

                default:
                    throw new Exception('Aksi tidak valid');
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

        exit;
    }
    // Generate email body for guarantee claim status updates
    private function buildGuaranteeEmailBody($name, $claimId, $status, $adminNotes = '')
    {
        // Normalize status to user-friendly Indonesian text
        $statusLower = strtolower($status);
        if (in_array($statusLower, ['approved', 'disetujui', 'diterima'])) {
            $statusText = 'disetujui';
        } elseif (in_array($statusLower, ['rejected', 'ditolak'])) {
            $statusText = 'ditolak';
        } else {
            $statusText = $status;
        }

        // Prepare notes section if provided
        $notesHtml = '';
        if (!empty($adminNotes)) {
            $safeNotes = nl2br(htmlspecialchars($adminNotes));
            $notesHtml = "<p><strong>Catatan Admin:</strong><br>{$safeNotes}</p>";
        }

        return "<p>Halo {$name},</p>\n"
             . "<p>Status klaim garansi dengan ID <strong>{$claimId}</strong> Anda telah <strong>{$statusText}</strong>.</p>\n"
             . $notesHtml
             . "<p>Terima kasih telah menggunakan HidroSmart.</p>";
    }

    /**
     * Reply to a contact message via WhatsApp.
     */
    public function replyToContact($messageId, $replyText)
    {
        // Load environment variables
        require_once __DIR__ . '/../../vendor/autoload.php';
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();

        // Fetch user's phone and name
        $query = "SELECT c.*, u.phone, u.name FROM contact c LEFT JOIN pengguna u ON c.id_pengguna = u.id_pengguna WHERE c.id_saran = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$messageId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            throw new Exception("Message not found");
        }
        $phone = $data['phone'];
        if (empty($phone)) {
            throw new Exception("User phone number not available");
        }

        // WhatsApp API credentials
        $whatsappToken = trim($_ENV['WHATSAPP_TOKEN'] ?? getenv('WHATSAPP_TOKEN') ?? '');
        $phoneNumberId = trim($_ENV['WHATSAPP_PHONE_NUMBER_ID'] ?? getenv('WHATSAPP_PHONE_NUMBER_ID') ?? '');
        if (empty($whatsappToken) || empty($phoneNumberId)) {
            throw new Exception("WhatsApp credentials not configured");
        }

        $url = "https://graph.facebook.com/v15.0/{$phoneNumberId}/messages";
        $messageBody = "Hi {$data['name']}, reply from HidroSmart support:\n{$replyText}";
        $payload = json_encode([
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'text',
            'text' => ['body' => $messageBody]
        ]);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $whatsappToken", "Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception("WhatsApp send error: " . curl_error($ch));
        }
        curl_close($ch);

        // Mark message as read in DB
        $update = $this->pdo->prepare("UPDATE contact SET read_at = NOW() WHERE id_saran = ?");
        $update->execute([$messageId]);
        return ['success' => true];
    }
}
?>
