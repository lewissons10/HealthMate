<?php
echo "<h2>Food API Debug</h2>";

// Test the API endpoint directly
$testUrl = 'http://localhost/fitness-app/php/food_api.php?action=get_all&limit=1';

echo "<p><strong>Testing URL:</strong> <code>$testUrl</code></p>";

// Use cURL for better error handling
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h3>Results:</h3>";
echo "<p><strong>HTTP Code:</strong> $httpCode</p>";

if ($error) {
    echo "<p style='color: red;'><strong>cURL Error:</strong> $error</p>";
}

if ($response === false) {
    echo "<p style='color: red;'>No response received</p>";
} else {
    echo "<p><strong>Response:</strong></p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars($response);
    echo "</pre>";
    
    // Try to decode JSON
    $data = json_decode($response, true);
    if ($data === null) {
        echo "<p style='color: red;'>Response is not valid JSON</p>";
    } else {
        echo "<p style='color: green;'>Response is valid JSON</p>";
        echo "<pre style='background: #e8f5e8; padding: 10px; border: 1px solid #4caf50;'>";
        print_r($data);
        echo "</pre>";
    }
}

// Test database connection directly
echo "<h3>Database Connection Test:</h3>";
try {
    require_once 'php/config.php';
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Check if foods table exists
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM foods");
        $count = $stmt->fetch()['count'];
        echo "<p style='color: green;'>✓ Foods table exists with $count records</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ Foods table does not exist: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='db/check_database.php'>Check Database Status</a> | <a href='db/simple_install.php'>Install Food Database</a></p>";
?>
