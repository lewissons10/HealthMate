<?php
// Debug why specific foods are failing to add

echo "<h2>Debugging Failed Foods</h2>";
echo "<p>Let's investigate why these 4 specific foods are failing with 400 Bad Request.</p>";

// Test each failed food individually with detailed debugging
$failedFoods = [
    'turkey_breast' => [
        'name' => 'Turkey Breast',
        'category' => 'protein',
        'calories_per_100g' => 135,
        'protein' => 30,
        'carbs' => 0,
        'fats' => 1,
        'fiber' => 0,
        'sugar' => 0,
        'unit' => 'g'
    ],
    'lean_beef' => [
        'name' => 'Lean Beef',
        'category' => 'protein',
        'calories_per_100g' => 250,
        'protein' => 26,
        'carbs' => 0,
        'fats' => 17,
        'fiber' => 0,
        'sugar' => 0,
        'unit' => 'g'
    ],
    'white_fish' => [
        'name' => 'White Fish',
        'category' => 'protein',
        'calories_per_100g' => 144,
        'protein' => 26,
        'carbs' => 0,
        'fats' => 3.2,
        'fiber' => 0,
        'sugar' => 0,
        'unit' => 'g'
    ],
    'chicken_thigh' => [
        'name' => 'Chicken Thigh',
        'category' => 'protein',
        'calories_per_100g' => 209,
        'protein' => 18,
        'carbs' => 0,
        'fats' => 15,
        'fiber' => 0,
        'sugar' => 0,
        'unit' => 'g'
    ]
];

foreach ($failedFoods as $foodKey => $foodData) {
    echo "<h3>Testing: {$foodData['name']}</h3>";
    
    // Show the data being sent
    echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Data being sent:</strong><br>";
    echo "<pre>" . json_encode($foodData, JSON_PRETTY_PRINT) . "</pre>";
    echo "</div>";
    
    // Test with cURL for better error handling
    $url = 'http://localhost/fitness-app/php/food_api.php?action=add_food';
    
    $postData = [
        'food_key' => $foodKey,
        'name' => $foodData['name'],
        'category' => $foodData['category'],
        'calories_per_100g' => $foodData['calories_per_100g'],
        'protein' => $foodData['protein'],
        'carbs' => $foodData['carbs'],
        'fats' => $foodData['fats'],
        'fiber' => $foodData['fiber'],
        'sugar' => $foodData['sugar'],
        'unit' => $foodData['unit']
    ];
    
    echo "<div style='background: #e2e3e5; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Full POST data:</strong><br>";
    echo "<pre>" . json_encode($postData, JSON_PRETTY_PRINT) . "</pre>";
    echo "</div>";
    
    // Use cURL for better error handling
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen(json_encode($postData))
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "<div style='background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>cURL Response:</strong><br>";
    echo "HTTP Code: $httpCode<br>";
    if ($error) {
        echo "cURL Error: $error<br>";
    }
    echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";
    echo "</div>";
    
    // Try a simpler approach - direct database insertion
    echo "<h4>Attempting Direct Database Insertion:</h4>";
    
    try {
        require_once 'php/config.php';
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO foods (food_key, name, calories_per_100g, protein, carbs, fats, fiber, sugar, unit, category)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $foodKey,
            $foodData['name'],
            $foodData['calories_per_100g'],
            $foodData['protein'],
            $foodData['carbs'],
            $foodData['fats'],
            $foodData['fiber'],
            $foodData['sugar'],
            $foodData['unit'],
            $foodData['category']
        ]);
        
        if ($result) {
            echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>✅ Direct DB Insert Success!</strong> {$foodData['name']} added directly to database.";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>❌ Direct DB Insert Failed</strong>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>❌ Database Error:</strong> " . $e->getMessage();
        echo "</div>";
    }
    
    echo "<hr>";
}

echo "<h3>Summary</h3>";
echo "<p>This debug script shows:</p>";
echo "<ul>";
echo "<li>The exact data being sent to the API</li>";
echo "<li>cURL response with HTTP codes</li>";
echo "<li>Direct database insertion attempt</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<p>1. <a href='test_breakfast_foods.php'>Test Breakfast Foods</a> - Check if foods are now in database</p>";
echo "<p>2. <a href='pages/dashboard.php'>Go to Dashboard</a> - Test breakfast food cards</p>";
echo "<p>3. <a href='add_all_foods_api.php'>Run Full Script Again</a> - Try adding all foods again</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; }
h3 { color: #666; margin-top: 30px; }
h4 { color: #888; margin-top: 20px; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
hr { margin: 20px 0; }
pre { background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 0.9em; overflow-x: auto; }
</style>
