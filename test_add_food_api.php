<?php
// Test the add_food API endpoint

echo "<h2>Testing Add Food API</h2>";

// Test data
$testFood = [
    'food_key' => 'test_food',
    'name' => 'Test Food',
    'category' => 'test',
    'calories_per_100g' => 100,
    'protein' => 10,
    'carbs' => 20,
    'fats' => 5,
    'fiber' => 2,
    'sugar' => 1,
    'unit' => 'g'
];

echo "<h3>Test Data:</h3>";
echo "<pre>" . json_encode($testFood, JSON_PRETTY_PRINT) . "</pre>";

// Test API call
$url = 'http://localhost/fitness-app/php/food_api.php?action=add_food';

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($testFood)
    ]
];

echo "<h3>API Call:</h3>";
echo "<p>URL: <a href='$url' target='_blank'>$url</a></p>";
echo "<p>Method: POST</p>";
echo "<p>Content-Type: application/json</p>";
echo "<p>Body: " . json_encode($testFood) . "</p>";

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "<h3>Result:</h3>";
if ($result === false) {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
    echo "<strong>❌ Failed to connect to API</strong>";
    echo "</div>";
} else {
    echo "<div style='background: #d1ecf1; padding: 10px; border-radius: 5px;'>";
    echo "<strong>Raw Response:</strong><br>";
    echo "<pre>" . htmlspecialchars($result) . "</pre>";
    echo "</div>";
    
    $decoded = json_decode($result, true);
    if ($decoded === null) {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
        echo "<strong>❌ Invalid JSON response</strong>";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
        echo "<strong>✅ Valid JSON Response:</strong><br>";
        echo "<pre>" . json_encode($decoded, JSON_PRETTY_PRINT) . "</pre>";
        echo "</div>";
        
        if (isset($decoded['error'])) {
            echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
            echo "<strong>❌ API Error:</strong> " . $decoded['error'];
            echo "</div>";
        } else {
            echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
            echo "<strong>✅ Success!</strong> Food added successfully.";
            echo "</div>";
        }
    }
}

echo "<h3>Next Steps:</h3>";
echo "<p>1. <a href='add_all_foods_api.php'>Run Add All Foods Script</a></p>";
echo "<p>2. <a href='test_breakfast_foods.php'>Test Breakfast Foods</a></p>";
echo "<p>3. <a href='pages/dashboard.php'>Go to Dashboard</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; }
h3 { color: #666; margin-top: 30px; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
pre { background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 0.9em; }
</style>
