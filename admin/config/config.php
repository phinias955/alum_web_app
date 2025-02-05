<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Database configurations
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'alumn_db');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

// Base paths and URLs
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/alumn');
    define('ADMIN_URL', BASE_URL . '/admin');
    define('ASSETS_URL', BASE_URL . '/assets');
    define('ADMIN_PATH', dirname(__DIR__));
    define('UPLOAD_PATH', ADMIN_PATH . '/uploads');
}

// Security configurations
if (!defined('SECURE_SESSION')) {
    define('SECURE_SESSION', true);
    define('SESSION_LIFETIME', 3600); // 1 hour
    define('CSRF_TOKEN_SECRET', bin2hex(random_bytes(32)));
    define('PASSWORD_PEPPER', 'phini@1234'); // Using the password as pepper for consistent hashing
    define('MAX_LOGIN_ATTEMPTS', 5);
    define('LOCKOUT_TIME', 900); // 15 minutes
    define('RATE_LIMIT_REQUESTS', 100);
    define('RATE_LIMIT_WINDOW', 60); // 1 minute
}

// File upload settings
if (!defined('MAX_FILE_SIZE')) {
    define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
    define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
}

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0777, true);
}

// Include required files
require_once __DIR__ . '/database.php';
require_once ADMIN_PATH . '/includes/Security.php';

// Set timezone
date_default_timezone_set('UTC');

// Global functions
function redirect($url) {
    header("Location: $url");
    exit;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

// Security functions
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

// Initialize database connection
$db = Database::getInstance();

// Initialize required directories
$directories = [
    UPLOAD_PATH
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}
?>
