<?php
// Fix the remaining 4 foods that failed to add

$remainingFoods = [
    'turkey_breast' => [
        'name' => 'Turkey Breast',
        'category' => 'protein',
        'calories_per_100g' => 135,
        'protein' => 30,
        'carbs' => 0,
        'fats' => 1,
        'fiber' => 0,
        'sugar' => 0,
        'vitamins' => ['Niacin' => 10.4, 'Vitamin B6' => 0.8],
        'minerals' => ['Selenium' => 24.3, 'Phosphorus' => 223]
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
        'vitamins' => ['Vitamin B12' => 2.4, 'Niacin' => 4.5],
        'minerals' => ['Iron' => 2.6, 'Zinc' => 4.3]
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
        'vitamins' => ['Vitamin B12' => 2.1],
        'minerals' => ['Selenium' => 36.5, 'Phosphorus' => 256]
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
        'vitamins' => ['Niacin' => 6.3, 'Vitamin B6' => 0.4],
        'minerals' => ['Selenium' => 18.2, 'Phosphorus' => 158]
    ]
];

// Function to add food to database using API
function addFoodToDatabase($foodKey, $foodData) {
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
        'unit' => 'g'
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($postData)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === false) {
        return ['error' => 'Failed to connect to API'];
    }
    
    $decoded = json_decode($result, true);
    if ($decoded === null) {
        return ['error' => 'Invalid JSON response: ' . $result];
    }
    
    return $decoded;
}

echo "<h2>Fixing Remaining 4 Foods</h2>";
echo "<p>These foods failed to add in the previous run. Let's try to add them individually.</p>";

$addedCount = 0;
$errorCount = 0;

foreach ($remainingFoods as $foodKey => $foodData) {
    echo "<h3>Processing: {$foodData['name']}</h3>";
    
    // Add food to database
    $result = addFoodToDatabase($foodKey, $foodData);
    
    if ($result && !isset($result['error'])) {
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>✅ Added successfully:</strong> {$foodData['name']}<br>";
        echo "Calories/100g: {$foodData['calories_per_100g']}, Protein: {$foodData['protein']}g, Carbs: {$foodData['carbs']}g, Fats: {$foodData['fats']}g";
        echo "</div>";
        $addedCount++;
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>❌ Error adding:</strong> {$foodData['name']}<br>";
        if ($result && isset($result['error'])) {
            echo "Error: {$result['error']}<br>";
            echo "Raw result: " . print_r($result, true);
        } else {
            echo "Unknown error occurred<br>";
            echo "Raw result: " . print_r($result, true);
        }
        echo "</div>";
        $errorCount++;
    }
    
    echo "<hr>";
}

echo "<h2>Summary</h2>";
echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px;'>";
echo "<strong>Remaining Foods Processed:</strong> " . count($remainingFoods) . "<br>";
echo "<strong>Successfully Added:</strong> $addedCount<br>";
echo "<strong>Errors:</strong> $errorCount<br>";
echo "</div>";

if ($errorCount == 0) {
    echo "<h3>🎉 All Foods Successfully Added!</h3>";
    echo "<p>Now you can test the breakfast food cards in the dashboard.</p>";
} else {
    echo "<h3>⚠️ Some Foods Still Failed</h3>";
    echo "<p>There might be an issue with the API or database. Let's investigate further.</p>";
}

echo "<h3>Next Steps:</h3>";
echo "<p>1. <a href='test_breakfast_foods.php'>Test Breakfast Foods</a> - Verify all foods are in database</p>";
echo "<p>2. <a href='pages/dashboard.php'>Go to Dashboard</a> - Test breakfast food cards</p>";
echo "<p>3. <a href='debug_breakfast_api.html'>Debug API Integration</a> - Test API calls</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; }
h3 { color: #666; margin-top: 30px; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
hr { margin: 20px 0; }
</style>
