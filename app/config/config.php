<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ydf-system');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application settings
define('BASE_URL', 'http://localhost/ydf-system/public');

// Security settings
define('ENCRYPTION_KEY', 'your-secure-key-here'); // Change this to a random string

// Helper function to get database connection
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
    }
    
    return $conn;
}

// CSRF protection functions
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Authentication check
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

function requireAuth() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "/login");
        exit();
    }
}

function requireAdmin() {
    requireAuth();
    if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        include APP_PATH . '/views/errors/403.php';
        exit();
    }
}