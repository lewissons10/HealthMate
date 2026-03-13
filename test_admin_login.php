<?php
// Test file to verify admin login functionality
require_once 'php/admin_config.php';

echo "<h2>Admin Login Test</h2>";

// Test 1: Check if functions exist
echo "<h3>Function Tests:</h3>";
echo "startAdminSession exists: " . (function_exists('startAdminSession') ? 'YES' : 'NO') . "<br>";
echo "isAdminLoggedIn exists: " . (function_exists('isAdminLoggedIn') ? 'YES' : 'NO') . "<br>";
echo "loginAdmin exists: " . (function_exists('loginAdmin') ? 'YES' : 'NO') . "<br>";

// Test 2: Check constants
echo "<h3>Constant Tests:</h3>";
echo "ADMIN_USERNAME: " . (defined('ADMIN_USERNAME') ? ADMIN_USERNAME : 'NOT DEFINED') . "<br>";
echo "ADMIN_PASSWORD: " . (defined('ADMIN_PASSWORD') ? ADMIN_PASSWORD : 'NOT DEFINED') . "<br>";

// Test 3: Test login function
echo "<h3>Login Test:</h3>";
$test_result = loginAdmin('anson', 'ansonlewis');
echo "Login test (anson/ansonlewis): " . ($test_result ? 'SUCCESS' : 'FAILED') . "<br>";

$test_result2 = loginAdmin('wrong', 'wrong');
echo "Login test (wrong/wrong): " . ($test_result2 ? 'SUCCESS' : 'FAILED') . "<br>";

// Test 4: Check session
echo "<h3>Session Test:</h3>";
echo "Is admin logged in: " . (isAdminLoggedIn() ? 'YES' : 'NO') . "<br>";
echo "Admin username in session: " . ($_SESSION['admin_username'] ?? 'NOT SET') . "<br>";

// Test 5: Database connection
echo "<h3>Database Test:</h3>";
$pdo = getAdminDBConnection();
echo "Database connection: " . ($pdo ? 'SUCCESS' : 'FAILED') . "<br>";

echo "<hr>";
echo "<a href='admin_login.php'>Go to Admin Login</a><br>";
echo "<a href='admin_dashboard.php'>Go to Admin Dashboard</a>";
?>
