<?php
// Admin logout handler
require_once 'php/admin_config.php';

// Log the logout activity
if (isset($_SESSION['admin_username'])) {
    error_log("Admin logout: " . $_SESSION['admin_username']);
}

// Clear admin session
logoutAdmin();

// Redirect to login page
header('Location: admin_login.php');
exit();
?>