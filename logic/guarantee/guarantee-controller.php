<?php
session_start();

require_once __DIR__ . '/../login-register/database.php';

$response = ["success" => false, "message" => "Terjadi kesalahan.", "type" => "general", "errors" => []];

// Jika ini bukan request AJAX, redirect ke halaman guarantee
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    header("Location: ../../view/guarantee.php");
    exit();
}

header("Content-Type: application/json");

// Cek apakah form di-submit
if (!isset($_POST['submit_guarantee'])) {
    $response['message'] = "Permintaan tidak valid.";
    echo json_encode($response);
    exit();
}

// 1. Cek login status
if (!isset($_SESSION['user_id']) && !isset($_SESSION['username'])) {
    $response['message'] = "Anda harus login terlebih dahulu untuk mengajukan klaim garansi.";
    $response['type'] = "login_required";
    echo json_encode($response);
    exit();
}

// Ambil user ID dari session
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id && isset($_SESSION['username'])) {
    try {
        $stmt = $pdo->prepare("SELECT id_pengguna FROM pengguna WHERE name = ?");
        $stmt->execute([$_SESSION['username']]);
        $row = $stmt->fetch();
        if ($row) {
            $user_id = $row['id_pengguna'];
        }
    } catch (PDOException $e) {
        error_log("Error getting user ID: " . $e->getMessage());
    }
}

if (!$user_id) {
    $response['message'] = "Data pengguna tidak ditemukan.";
    echo json_encode($response);
    exit();
}

// 2. Cek kelengkapan profil user (phone dan alamat)
try {
    $stmt = $pdo->prepare("SELECT phone, alamat FROM pengguna WHERE id_pengguna = ?");
    $stmt->execute([$user_id]);
    $user_profile = $stmt->fetch();
    
    if (!$user_profile) {
        $response['message'] = "Data pengguna tidak ditemukan.";
        echo json_encode($response);
        exit();
    }
    
    // Cek apakah phone dan alamat sudah diisi
    if (empty($user_profile['phone']) || empty($user_profile['alamat'])) {
        $response['message'] = "Anda harus melengkapi nomor handphone dan alamat di dashboard profil terlebih dahulu sebelum dapat mengajukan klaim garansi.";
        $response['type'] = "profile_incomplete";
        echo json_encode($response);
        exit();
    }
} catch (PDOException $e) {
    error_log("Error checking user profile: " . $e->getMessage());
    $response['message'] = "Kesalahan sistem. Silakan coba lagi.";
    echo json_encode($response);
    exit();
}

// 3. Cek apakah user sudah pernah order
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as order_count FROM payment WHERE id_pengguna = ?");
    $stmt->execute([$user_id]);
    $order_result = $stmt->fetch();
    
    if ($order_result['order_count'] == 0) {
        $response['message'] = "Anda harus melakukan pemesanan terlebih dahulu sebelum dapat mengajukan klaim garansi.";
        $response['type'] = "no_order";
        echo json_encode($response);
        exit();
    }
} catch (PDOException $e) {
    error_log("Error checking user orders: " . $e->getMessage());
    $response['message'] = "Kesalahan sistem. Silakan coba lagi.";
    echo json_encode($response);
    exit();
}

// 4. Cek apakah ada order dengan status 'Diterima Customer'
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as completed_orders FROM payment WHERE id_pengguna = ? AND status = 'Diterima Customer'");
    $stmt->execute([$user_id]);
    $completed_result = $stmt->fetch();
    
    if ($completed_result['completed_orders'] == 0) {
        $response['message'] = "Klaim garansi hanya dapat diajukan setelah pesanan Anda berstatus 'Diterima Customer'. Silakan tunggu hingga pesanan selesai.";
        $response['type'] = "order_not_completed";
        echo json_encode($response);
        exit();
    }
} catch (PDOException $e) {
    error_log("Error checking completed orders: " . $e->getMessage());
    $response['message'] = "Kesalahan sistem. Silakan coba lagi.";
    echo json_encode($response);
    exit();
}

// Ambil dan validasi input
$id_order = trim($_POST['id_order'] ?? '');
$deskripsi = trim($_POST['deskripsi'] ?? '');
$errors = [];

// Validasi ID Order
if (empty($id_order)) {
    $errors[] = "Order harus dipilih.";
} else {
    // Cek apakah order ID valid dan milik user yang login
    try {
        $stmt = $pdo->prepare("SELECT id_order FROM payment WHERE id_order = ? AND id_pengguna = ? AND status = 'Diterima Customer'");
        $stmt->execute([$id_order, $user_id]);
        if (!$stmt->fetch()) {
            $errors[] = "Order tidak valid atau belum berstatus 'Diterima Customer'.";
        }
    } catch (PDOException $e) {
        error_log("Error validating order: " . $e->getMessage());
        $errors[] = "Kesalahan sistem saat validasi order.";
    }
}

// Validasi deskripsi
if (empty($deskripsi)) {
    $errors[] = "Deskripsi masalah harus diisi.";
} elseif (strlen($deskripsi) < 10) {
    $errors[] = "Deskripsi masalah minimal 10 karakter.";
} elseif (strlen($deskripsi) > 1000) {
    $errors[] = "Deskripsi masalah maksimal 1000 karakter.";
}

// Cek apakah order sudah pernah diklaim
if (!empty($id_order)) {
    try {
        $stmt = $pdo->prepare("SELECT id_guarantee FROM guarantee WHERE id_order = ?");
        $stmt->execute([$id_order]);
        if ($stmt->fetch()) {
            $errors[] = "Order ini sudah pernah diklaim garansinya.";
        }
    } catch (PDOException $e) {
        error_log("Error checking existing guarantee claim: " . $e->getMessage());
        $errors[] = "Kesalahan sistem saat validasi klaim garansi.";
    }
}

// Handle file upload
$bukti_gambar_filename = null;
if (isset($_FILES['bukti_gambar']) && $_FILES['bukti_gambar']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['bukti_gambar'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    // Validasi tipe file
    if (!in_array($file['type'], $allowed_types)) {
        $errors[] = "Tipe file tidak diizinkan. Gunakan JPG, PNG, atau PDF.";
    }
    
    // Validasi ukuran file
    if ($file['size'] > $max_size) {
        $errors[] = "Ukuran file terlalu besar. Maksimal 5MB.";
    }
    
    if (empty($errors)) {
        // Generate nama file dengan format: id_order_timestamp.extension
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $bukti_gambar_filename = $id_order . '_' . time() . '.' . $file_extension;
        
        // Tentukan direktori upload
        $upload_dir = 'bukti_kerusakan/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $upload_path = $upload_dir . $bukti_gambar_filename;
        
        // Upload file
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            $errors[] = "Gagal mengupload file bukti kerusakan.";
            $bukti_gambar_filename = null;
        }
    }
} else {
    $errors[] = "Bukti kerusakan harus diupload.";
}

// Jika ada error, return error response
if (!empty($errors)) {
    $response['message'] = "Validasi gagal.";
    $response['errors'] = $errors;
    echo json_encode($response);
    exit();
}

// Simpan ke database
try {
    // Generate ID Guarantee dengan format: GR + YYYYMMDD + random 4 digit
    $guarantee_id = 'GR' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Cek apakah ID sudah ada (untuk memastikan unique)
    $stmt = $pdo->prepare("SELECT id_guarantee FROM guarantee WHERE id_guarantee = ?");
    $stmt->execute([$guarantee_id]);
    
    // Jika ID sudah ada, generate ulang
    while ($stmt->fetch()) {
        $guarantee_id = 'GR' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT id_guarantee FROM guarantee WHERE id_guarantee = ?");
        $stmt->execute([$guarantee_id]);
    }
    
    $stmt = $pdo->prepare("INSERT INTO guarantee (id_guarantee, id_pengguna, id_order, deskripsi, bukti_gambar, tanggal_klaim) VALUES (?, ?, ?, ?, ?, NOW())");
    $success = $stmt->execute([
        $guarantee_id,
        $user_id,
        $id_order,
        $deskripsi,
        $bukti_gambar_filename
    ]);

    if ($success) {
        $response['success'] = true;
        $response['message'] = "Klaim garansi berhasil diajukan!";
        $response['claim_id'] = $guarantee_id;
        $response['order_id'] = $id_order;
    } else {
        $response['message'] = "Gagal menyimpan klaim garansi.";
    }
} catch (PDOException $e) {
    error_log("Error saving guarantee claim: " . $e->getMessage());
    $response['message'] = "Kesalahan server. Silakan coba lagi.";
}

echo json_encode($response);
?>
