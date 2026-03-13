<?php
// Admin Configuration - Restricted to Anson only
define('ADMIN_USERNAME', 'anson');
define('ADMIN_PASSWORD', 'ansonlewis'); // Anson's secure password
define('ADMIN_SESSION_KEY', 'admin_logged_in');

// Admin session management
function startAdminSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

function isAdminLoggedIn() {
    startAdminSession();
    return isset($_SESSION[ADMIN_SESSION_KEY]) && $_SESSION[ADMIN_SESSION_KEY] === true;
}

function loginAdmin($username, $password) {
    startAdminSession();
    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION[ADMIN_SESSION_KEY] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_login_time'] = time();
        return true;
    }
    return false;
}

function logoutAdmin() {
    startAdminSession();
    unset($_SESSION[ADMIN_SESSION_KEY]);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_login_time']);
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: admin_login.php');
        exit();
    }
}

function requireAnsonAccess() {
    startAdminSession();
    if (!isAdminLoggedIn() || $_SESSION['admin_username'] !== 'anson') {
        header('Location: admin_login.php');
        exit();
    }
}

// Database connection for admin functions
function getAdminDBConnection() {
    // Use the same database configuration as the main app
    require_once 'config.php';
    return getDBConnection();
}
?>
