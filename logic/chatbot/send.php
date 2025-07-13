<?php
// logic/chatbot/send.php
// HidroSmart Chatbot API endpoint with user authentication

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Create logs directory if it doesn't exist
$logsDir = __DIR__ . '/../../logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
}

ini_set('error_log', $logsDir . '/chatbot_error.log');
// Debug: log script start and enable display errors
file_put_contents($logsDir . '/chatbot_debug.log', date('c') . " SEND.PHP start - " . 
    (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'no-uri') . PHP_EOL,
    FILE_APPEND
);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'error' => 'unauthorized',
        'reply' => 'Silakan login terlebih dahulu untuk menggunakan chatbot HidroSmart.'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = isset($input['message']) ? trim($input['message']) : '';

// Log input for debugging
file_put_contents(
    $logsDir . '/chatbot_debug.log',
    date('c') . " USER_ID: " . $_SESSION['user_id'] . " INPUT: " . json_encode($input) . PHP_EOL,
    FILE_APPEND
);

// Initialize response
$response_data = [
    'error' => null,
    'reply' => null
];

if ($userMessage === '') {
    $response_data['error'] = 'empty_message';
    $response_data['reply'] = 'Mohon maaf, pesan tidak boleh kosong.';
    echo json_encode($response_data);
    exit();
}

// Load Dotenv and dependencies
require_once __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->safeLoad();

// Get API key from environment
$openaiApiKey = trim($_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY') ?? '');
file_put_contents(
    $logsDir . '/chatbot_debug.log',
    date('c') . " Using OPENAI_API_KEY from environment" . PHP_EOL,
    FILE_APPEND
);

// Validate API key format
if (!$openaiApiKey || !preg_match('/^sk-[a-zA-Z0-9\-_]{20,}$/', $openaiApiKey)) {
    error_log("HidroSmart Chatbot: API key not properly configured or invalid. Value: " . substr($openaiApiKey, 0, 8) . "***");
    echo json_encode([
        'error' => 'api_key_not_configured',
        'reply' => 'Halo! Sistem chatbot sedang tidak dapat digunakan karena masalah konfigurasi API key. Silakan hubungi admin.'
    ]);
    exit();
}

// Get user info for personalized responses
$userName = 'Pengguna';
if (isset($input['username']) && !empty($input['username'])) {
    $userName = $input['username'];
    file_put_contents($logsDir . '/chatbot_debug.log', date('c') . " USERNAME from input: $userName" . PHP_EOL, FILE_APPEND);
} elseif (isset($_SESSION['nama']) && !empty($_SESSION['nama'])) {
    $userName = $_SESSION['nama'];
    file_put_contents($logsDir . '/chatbot_debug.log', date('c') . " USERNAME from session: $userName" . PHP_EOL, FILE_APPEND);
} else {
    file_put_contents($logsDir . '/chatbot_debug.log', date('c') . " USERNAME fallback: Pengguna" . PHP_EOL, FILE_APPEND);
}

$systemPrompt = <<<PROMPT
Anda adalah Yuta, asisten virtual HidroSmart yang ramah dan profesional. Nama pengguna adalah '$userName'. Tugas Anda adalah memberikan informasi akurat, edukatif, dan membantu pengguna mengenai produk HidroSmart Tumbler dan website HidroSmart.

LATAR BELAKANG HIDROSMART:
Hidrosmart adalah tumbler pintar yang tidak hanya berfungsi sebagai tempat minum, tetapi juga dilengkapi sensor untuk memantau tingkat dehidrasi pengguna dan memberikan pemberitahuan melalui perangkat digital seperti smartphone. Hidrosmart hadir sebagai inovasi yang bertujuan membentuk kebiasaan minum air sehat dan teratur, terutama di kalangan pelajar, pekerja kantoran, dan traveler yang sering kali lupa minum karena aktivitas padat. Dengan Hidrosmart, pengguna tidak perlu lagi khawatir menghitung asupan cairan harian.

MASALAH YANG DISELESAIKAN HIDROSMART:
1. Banyak orang sulit mengingat untuk minum air secara teratur, terutama saat sibuk.
2. Tidak ada cara praktis untuk mengetahui tingkat dehidrasi tubuh secara real-time.

SOLUSI HIDROSMART:
1. HidroSmart sebagai tumbler pintar dengan sensor yang mendeteksi tingkat cairan tubuh pengguna.
2. Konektivitas Bluetooth ke aplikasi mobile yang memberikan notifikasi otomatis saat pengguna perlu minum.

FITUR UNGGULAN:
- Sensor Hidrasi: Mendeteksi tingkat dehidrasi tubuh secara real-time.
- Konektivitas Bluetooth: Sinkronisasi dengan aplikasi mobile (iOS & Android).
- Notifikasi pintar: Pengingat minum otomatis.
- Tracking konsumsi: Pantau riwayat minum harian/mingguan.
- Baterai tahan lama: 5-7 hari pemakaian.
- Display LED di tutup botol.
- Material premium: BPA-free, food grade stainless steel.
- Desain ergonomis, stylish, dan mudah dibawa.

SPESIFIKASI:
- Kapasitas: 500ml | Berat: 350g | Dimensi: 24cm x 7cm | Bluetooth 5.0 | Warna: Hitam, Putih, Biru, Merah

HARGA & PEMBELIAN:
- Harga: Rp 299.000
- Garansi: 2 tahun komponen utama, 1 tahun aksesori
- Pembelian: Melalui website/aplikasi HidroSmart
- Pengiriman: Seluruh Indonesia (gratis ongkir Jabodetabek)

SUPPORT:
- Customer Service: +62 896-5242-9620 | Email: support@hidrosmart.com | Jam operasional: 08:00-20:00 WIB

ATURAN INTERAKSI:
- Gunakan nama pengguna '$userName' untuk personalisasi.
- Jawab dalam Bahasa Indonesia yang ramah, sopan, dan edukatif.
- Fokus pada pertanyaan seputar HidroSmart, fitur, pembelian, garansi, dan support.
- Jika pertanyaan di luar topik, arahkan dengan sopan ke topik HidroSmart.
- Gunakan emoji sesekali: 😊 💧 🌟 ⚡
- Berikan solusi praktis dan informasi yang berguna.

PROMPT;

$payload = [
    'model' => 'gpt-4o-mini',
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userMessage]
    ],
    // 'max_tokens' => 350,
    // 'temperature' => 0.8,
];

// Log payload for debugging
file_put_contents(
    $logsDir . '/chatbot_debug.log',
    date('c') . " PAYLOAD: " . json_encode($payload) . PHP_EOL,
    FILE_APPEND
);

$ch = curl_init('https://api.openai.com/v1/chat/completions');

// Build headers
$headers = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $openaiApiKey,
];

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_USERAGENT => 'HidroSmart-Chatbot/1.0',
]);

file_put_contents($logsDir . '/chatbot_debug.log', date('c') . " CURL: about to exec" . PHP_EOL, FILE_APPEND);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
file_put_contents($logsDir . '/chatbot_debug.log', date('c') . " CURL: done exec HTTP $httpCode CURLERR: $curlError RESPONSE: " . substr($response,0,400) . PHP_EOL, FILE_APPEND);
curl_close($ch);

// Log response for debugging
file_put_contents($logsDir . '/chatbot_debug.log', date('c') . " RESPONSE CODE: $httpCode RESPONSE: $response" . PHP_EOL, FILE_APPEND);

// Tangani error 401 khusus (invalid API key)
if ($httpCode == 429) {
    error_log("HidroSmart Chatbot: OpenAI API returned 429 Insufficient Quota.");
    echo json_encode([
        'error' => 'insufficient_quota',
        'reply' => 'Mohon maaf, chatbot tidak dapat merespons karena kuota layanan kami telah habis. Silakan hubungi admin untuk informasi lebih lanjut.'
    ]);
    exit();
} elseif ($httpCode == 401) {
    error_log("HidroSmart Chatbot: OpenAI API returned 401 Unauthorized. Check API key validity and project access.");
    echo json_encode([
        'error' => 'invalid_api_key',
        'reply' => 'API key OpenAI yang digunakan tidak valid atau tidak memiliki akses. Silakan cek kembali API key Anda di dashboard OpenAI.'
    ]);
    exit();
}

// Log response for debugging
file_put_contents(
    $logsDir . '/chatbot_debug.log',
    date('c') . " RESPONSE CODE: $httpCode RESPONSE: " . substr($response, 0, 500) . PHP_EOL,
    FILE_APPEND
);

if ($response === false || !empty($curlError)) {
    error_log("CURL Error: " . $curlError);
    $response_data['error'] = 'connection_error';
    $response_data['reply'] = "Halo $userName! 😊 Saat ini koneksi sedang bermasalah. Untuk bantuan langsung, silakan hubungi customer service kami di +62 812-3456-7890";
} else if ($httpCode >= 400) {
    error_log("API Error: HTTP $httpCode - $response");
    $response_data['error'] = 'api_error';
    $response_data['reply'] = "Halo $userName! 😊 Sistem sedang mengalami gangguan. Silakan coba lagi atau hubungi customer service kami di +62 812-3456-7890";
} else {
    $data = json_decode($response, true);
    if (!$data || !isset($data['choices'][0]['message']['content'])) {
        error_log("Invalid response from OpenAI: " . $response);
        $response_data['error'] = 'invalid_response';
        $response_data['reply'] = "Halo $userName! 😊 Ada yang bisa saya bantu tentang HidroSmart Tumbler kami?";
    } else {
        $reply = trim($data['choices'][0]['message']['content']);
        if (empty($reply)) {
            $response_data['reply'] = "Halo $userName! 😊 Ada yang bisa saya bantu tentang HidroSmart Tumbler kami?";
        } else {
            $response_data['reply'] = $reply;
        }
    }
}

echo json_encode($response_data);
