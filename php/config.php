<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'healthmate_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Email configuration
define('ADMIN_EMAIL', 'ansonlewis2003@gmail.com');
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'ansonlewis2003@gmail.com');
define('SMTP_PASSWORD', 'mdxdnxrbymlkwqnj'); // Gmail App Password for HealthMate (16 characters)
define('SMTP_SECURE', 'tls');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection function
function getDBConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Helper function to sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>
