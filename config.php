<?php
// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'book_platform');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// إعدادات الموقع
define('SITE_URL', 'http://localhost/german-books');
define('SITE_NAME', 'منصة الكتب الرقمية');

// مسارات الملفات
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('BOOKS_DIR', UPLOAD_DIR . 'books/');
define('AUDIO_DIR', UPLOAD_DIR . 'audio/');
define('COVERS_DIR', UPLOAD_DIR . 'covers/');
define('QR_DIR', UPLOAD_DIR . 'qr/');

// إنشاء المجلدات إذا لم تكن موجودة
if (!file_exists(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
if (!file_exists(BOOKS_DIR)) mkdir(BOOKS_DIR, 0755, true);
if (!file_exists(AUDIO_DIR)) mkdir(AUDIO_DIR, 0755, true);
if (!file_exists(COVERS_DIR)) mkdir(COVERS_DIR, 0755, true);
if (!file_exists(QR_DIR)) mkdir(QR_DIR, 0755, true);

// إعدادات الرفع
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_BOOK_TYPES', ['pdf']);
define('ALLOWED_AUDIO_TYPES', ['mp3', 'wav', 'ogg', 'm4a']);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// إعدادات الأمان
define('SESSION_LIFETIME', 3600 * 24); // 24 hours
define('HASH_ALGO', 'sha256');

// بدء الجلسة
session_start();

// الاتصال بقاعدة البيانات
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// دالة التحقق من تسجيل الدخول
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// دالة التحقق من صلاحيات الأدمن
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// دالة إعادة التوجيه
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// دالة توليد اسم ملف فريد
function generateUniqueFilename($extension) {
    return bin2hex(random_bytes(16)) . '_' . time() . '.' . $extension;
}

// دالة تنظيف المدخلات
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// دالة توليد QR Code
function generateQRCode($data, $filename) {
    require_once 'vendor/phpqrcode/qrlib.php';
    $filepath = QR_DIR . $filename;
    QRcode::png($data, $filepath, QR_ECLEVEL_L, 10);
    return $filepath;
}

// تعيين المنطقة الزمنية
date_default_timezone_set('Africa/Cairo');

// معالجة الأخطاء
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
?>
