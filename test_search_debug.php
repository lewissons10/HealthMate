<?php
require_once 'php/config.php';

echo "<h2>Search Function Debug</h2>";

try {
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    // Test 1: Simple search without GROUP_CONCAT
    echo "<h3>Test 1: Simple Search</h3>";
    $query = 'chicken';
    $searchTerm = '%' . $query . '%';
    
    $stmt = $pdo->prepare("SELECT * FROM foods WHERE name LIKE ? OR food_key LIKE ? LIMIT 5");
    $stmt->execute([$searchTerm, $searchTerm]);
    $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($foods) . " foods:</p>";
    foreach ($foods as $food) {
        echo "<p>- " . $food['name'] . " (" . $food['food_key'] . ")</p>";
    }
    
    // Test 2: Search with GROUP_CONCAT
    echo "<h3>Test 2: Search with Vitamins/Minerals</h3>";
    $stmt = $pdo->prepare("
        SELECT f.*, 
               GROUP_CONCAT(DISTINCT CONCAT(fv.vitamin_name, ':', fv.amount, fv.unit) SEPARATOR '|') as vitamins,
               GROUP_CONCAT(DISTINCT CONCAT(fm.mineral_name, ':', fm.amount, fm.unit) SEPARATOR '|') as minerals
        FROM foods f
        LEFT JOIN food_vitamins fv ON f.id = fv.food_id
        LEFT JOIN food_minerals fm ON f.id = fm.food_id
        WHERE f.name LIKE ? OR f.food_key LIKE ?
        GROUP BY f.id
        ORDER BY f.name
        LIMIT 5
    ");
    
    $stmt->execute([$searchTerm, $searchTerm]);
    $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($foods) . " foods with vitamins/minerals:</p>";
    foreach ($foods as $food) {
        echo "<p>- " . $food['name'] . " (Vitamins: " . ($food['vitamins'] ?: 'None') . ")</p>";
    }
    
    // Test 3: Direct API call
    echo "<h3>Test 3: Direct API Call</h3>";
    $apiUrl = 'http://localhost/fitness-app/php/food_api.php?action=search&q=' . urlencode($query);
    echo "<p>API URL: <code>$apiUrl</code></p>";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response === false) {
        echo "<p style='color: red;'>✗ API call failed</p>";
        $error = error_get_last();
        if ($error) {
            echo "<p style='color: red;'>Error: " . htmlspecialchars($error['message']) . "</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ API call successful</p>";
        echo "<p>Response:</p>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
        echo htmlspecialchars($response);
        echo "</pre>";
        
        $data = json_decode($response, true);
        if ($data && isset($data['foods'])) {
            echo "<p>Found " . count($data['foods']) . " foods via API:</p>";
            foreach ($data['foods'] as $food) {
                echo "<p>- " . $food['name'] . " (" . $food['category'] . ")</p>";
            }
        } else {
            echo "<p style='color: red;'>API returned error: " . ($data['error'] ?? 'Unknown error') . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='test_food_api.php'>← Back to API Test</a></p>";
?>
