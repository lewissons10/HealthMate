<?php
require_once '../php/config.php';

echo "<h2>Database Diagnostic Check</h2>";

try {
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    // Check if database exists
    echo "<h3>1. Database Check</h3>";
    $stmt = $pdo->query("SELECT DATABASE() as current_db");
    $db = $stmt->fetch();
    echo "<p>Current database: <strong>" . $db['current_db'] . "</strong></p>";
    
    // Check if tables exist
    echo "<h3>2. Table Check</h3>";
    $tables = ['foods', 'food_vitamins', 'food_minerals'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "<p style='color: green;'>✓ Table '$table' exists with $count records</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ Table '$table' does not exist</p>";
        }
    }
    
    // Check existing tables in database
    echo "<h3>3. All Tables in Database</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($existingTables)) {
        echo "<p style='color: red;'>No tables found in database!</p>";
    } else {
        echo "<p>Existing tables:</p><ul>";
        foreach ($existingTables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    }
    
    // Test API endpoint directly
    echo "<h3>4. API Test</h3>";
    echo "<p>Testing API endpoint...</p>";
    
    $testUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/php/food_api.php?action=get_all&limit=1';
    echo "<p>Test URL: <code>$testUrl</code></p>";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($testUrl, false, $context);
    
    if ($response === false) {
        echo "<p style='color: red;'>✗ API request failed</p>";
        $error = error_get_last();
        if ($error) {
            echo "<p style='color: red;'>Error: " . htmlspecialchars($error['message']) . "</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ API responded</p>";
        echo "<p>Response: <pre>" . htmlspecialchars($response) . "</pre></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ul>";
echo "<li><a href='simple_install.php'>Install Food Database</a> - If tables don't exist</li>";
echo "<li><a href='../test_food_api.php'>Test Food API</a> - After installation</li>";
echo "<li><a href='../test_integration.html'>Test Integration</a> - Test the full integration</li>";
echo "</ul>";
?>
