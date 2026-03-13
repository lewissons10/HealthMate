<?php
require_once 'php/config.php';

echo "<h2>Simple API Test</h2>";

try {
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    // Test 1: Simple foods query
    echo "<h3>Test 1: Simple Foods Query</h3>";
    $stmt = $pdo->query("SELECT * FROM foods LIMIT 3");
    $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Found " . count($foods) . " foods:</p>";
    foreach ($foods as $food) {
        echo "<p>- " . $food['name'] . " (" . $food['category'] . ")</p>";
    }
    
    // Test 2: GROUP_CONCAT test
    echo "<h3>Test 2: GROUP_CONCAT Test</h3>";
    try {
        $stmt = $pdo->query("
            SELECT f.name, 
                   GROUP_CONCAT(DISTINCT CONCAT(fv.vitamin_name, ':', fv.amount, fv.unit) SEPARATOR '|') as vitamins
            FROM foods f
            LEFT JOIN food_vitamins fv ON f.id = fv.food_id
            WHERE f.id = 1
            GROUP BY f.id
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✓ GROUP_CONCAT works: " . ($result['vitamins'] ?: 'No vitamins') . "</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ GROUP_CONCAT failed: " . $e->getMessage() . "</p>";
    }
    
    // Test 3: Direct API call with cURL
    echo "<h3>Test 3: Direct API Call</h3>";
    
    $testUrl = 'http://localhost/fitness-app/php/food_api.php?action=get_all&limit=2';
    echo "<p>Testing: <code>$testUrl</code></p>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $testUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
    
    if ($error) {
        echo "<p style='color: red;'><strong>cURL Error:</strong> $error</p>";
    }
    
    if ($response === false) {
        echo "<p style='color: red;'>No response received</p>";
    } else {
        echo "<p><strong>Response:</strong></p>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow-y: auto;'>";
        echo htmlspecialchars($response);
        echo "</pre>";
        
        $data = json_decode($response, true);
        if ($data === null) {
            echo "<p style='color: red;'>Response is not valid JSON</p>";
        } else {
            echo "<p style='color: green;'>Response is valid JSON</p>";
            if (isset($data['foods'])) {
                echo "<p>Found " . count($data['foods']) . " foods via API</p>";
            } else {
                echo "<p style='color: red;'>API returned error: " . ($data['error'] ?? 'Unknown error') . "</p>";
            }
        }
    }
    
    // Test 4: Search API
    echo "<h3>Test 4: Search API</h3>";
    
    $searchUrl = 'http://localhost/fitness-app/php/food_api.php?action=search&q=chicken';
    echo "<p>Testing: <code>$searchUrl</code></p>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $searchUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
    
    if ($error) {
        echo "<p style='color: red;'><strong>cURL Error:</strong> $error</p>";
    }
    
    if ($response === false) {
        echo "<p style='color: red;'>No response received</p>";
    } else {
        echo "<p><strong>Response:</strong></p>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow-y: auto;'>";
        echo htmlspecialchars($response);
        echo "</pre>";
        
        $data = json_decode($response, true);
        if ($data === null) {
            echo "<p style='color: red;'>Response is not valid JSON</p>";
        } else {
            echo "<p style='color: green;'>Response is valid JSON</p>";
            if (isset($data['foods'])) {
                echo "<p>Found " . count($data['foods']) . " foods via search</p>";
            } else {
                echo "<p style='color: red;'>Search returned error: " . ($data['error'] ?? 'Unknown error') . "</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='test_food_api.php'>← Back to API Test</a></p>";
?>
