<?php
// logic/chatbot/send.php
// Proxy endpoint to forward user messages to the OpenAI Chat Completion API.
// Returns JSON: { reply: string }

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/chatbot_error.log'); // log file

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = isset($input['message']) ? trim($input['message']) : '';

// Log input for debugging
file_put_contents(__DIR__ . '/../../logs/chatbot_debug.log', date('c') . " INPUT: " . json_encode($input) . PHP_EOL, FILE_APPEND);

if ($userMessage === '') {
    error_log("Empty user message received.");
    echo json_encode(['error' => 'empty_message', 'reply' => 'Mohon maaf, pesan tidak boleh kosong.']);
    exit();
}

// Load .env if exists (using vlucas/phpdotenv)
$dotenvPath = __DIR__ . '/../../';
if (file_exists($dotenvPath . '.env')) {
    require_once $dotenvPath . 'vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable($dotenvPath);
    $dotenv->load();
}

// Ambil API key dari environment (.env)
$openaiApiKey = getenv('OPENAI_API_KEY');

// Fallback ke konstanta dari config jika belum di-set
require_once __DIR__ . '/../../config/openai.php';
if (!$openaiApiKey) {
    $openaiApiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;
}

// Check if API key is configured
if (!$openaiApiKey || $openaiApiKey === 'YOUR_OPENAI_KEY' || $openaiApiKey === 'sk-your-actual-openai-api-key-here') {
    error_log("OpenAI API key not configured.");
    echo json_encode([
        'error' => 'api_key_not_configured',
        'reply' => 'Halo! Saya asisten virtual HidroSmart. Saat ini sistem sedang dalam pemeliharaan. Silakan hubungi customer service kami di +62 812-3456-7890 untuk bantuan langsung.'
    ]);
    exit();
}

$systemPrompt = "Anda adalah asisten virtual dari HidroSmart yang ramah dan membantu. Tugas Anda adalah memberikan informasi yang akurat dan membantu pengguna terkait produk tumbler pintar HidroSmart. Jawab setiap pertanyaan dengan ramah, sopan, dan ringkas dalam Bahasa Indonesia.

Gunakan informasi di bawah ini sebagai sumber kebenaran Anda:

Informasi Produk HidroSmart:
- Nama Produk: HidroSmart Tumbler
- Kategori: Tumbler Pintar (Smart Tumbler) dengan teknologi sensor
- Fungsi Utama: Tempat minum pintar yang memantau tingkat dehidrasi dan memberikan notifikasi
- Fitur Unggulan:
  1. Sensor Dehidrasi: Mendeteksi tingkat cairan tubuh pengguna
  2. Konektivitas Bluetooth: Terhubung ke aplikasi mobile
  3. Notifikasi Cerdas: Pengingat minum otomatis
  4. Riwayat Konsumsi: Pantau histori minum harian
  5. Daya Tahan Baterai: Hingga 5 hari
  6. Layar Sentuh Mini: Tampilan data di tutup botol
  7. Desain Eco-Friendly: Ramah lingkungan dan stylish
- Target Pengguna: Pelajar, mahasiswa, pekerja kantoran, traveler
- Harga: Rp 450.000 (tersedia berbagai warna)
- Garansi: 2 tahun untuk komponen utama, 1 tahun untuk aksesori
- Kontak Support: +62 812-3456-7890, support@hidrosmart.com

Aturan Interaksi:
- Selalu awali dengan sapaan ramah
- Prioritaskan pertanyaan tentang HidroSmart
- Jika pertanyaan tidak relevan, arahkan kembali ke produk dengan sopan
- Berikan informasi yang akurat sesuai data di atas
- Gunakan emoticon sesekali untuk kesan ramah: 😊 🌟 💧
- Jika diminta fitur yang tidak ada, jelaskan dengan sopan apa yang bisa dilakukan produk";

$payload = [
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userMessage]
    ],
    'max_tokens' => 300,
    'temperature' => 0.7,
];

// Log payload for debugging
file_put_contents(__DIR__ . '/../../logs/chatbot_debug.log', date('c') . " PAYLOAD: " . json_encode($payload) . PHP_EOL, FILE_APPEND);

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $openaiApiKey,
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false, // For development only
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Log response for debugging
file_put_contents(__DIR__ . '/../../logs/chatbot_debug.log', date('c') . " RESPONSE: " . $response . " | HTTP: $httpCode | CURL: $curlError" . PHP_EOL, FILE_APPEND);

if ($response === false || !empty($curlError)) {
    error_log("CURL Error: " . $curlError);
    echo json_encode([
        'error' => 'connection_error',
        'reply' => 'Halo! Saya asisten HidroSmart 😊 Saat ini koneksi sedang bermasalah. Untuk bantuan langsung, silakan hubungi customer service kami di +62 812-3456-7890 atau email support@hidrosmart.com'
    ]);
    exit();
}

if ($httpCode >= 400) {
    error_log("API Error: HTTP $httpCode - $response");
    echo json_encode([
        'error' => 'api_error',
        'reply' => 'Halo! Saya asisten HidroSmart 😊 Sistem sedang mengalami gangguan. Silakan coba lagi dalam beberapa saat atau hubungi customer service kami di +62 812-3456-7890'
    ]);
    exit();
}

$data = json_decode($response, true);
if (!$data || !isset($data['choices'][0]['message']['content'])) {
    error_log("Invalid response from OpenAI: " . $response);
    echo json_encode([
        'error' => 'invalid_response',
        'reply' => 'Halo! Saya asisten HidroSmart 😊 Ada yang bisa saya bantu tentang produk HidroSmart Tumbler kami?'
    ]);
    exit();
}

$reply = trim($data['choices'][0]['message']['content']);
if (empty($reply)) {
    $reply = 'Halo! Saya asisten HidroSmart 😊 Ada yang bisa saya bantu tentang produk HidroSmart Tumbler kami?';
}

echo json_encode(['reply' => $reply]);
