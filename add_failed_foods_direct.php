<?php
// Add the failed foods directly to the database, bypassing the API

echo "<h2>Adding Failed Foods Directly to Database</h2>";
echo "<p>This script adds the 4 failed foods directly to the database, bypassing the API.</p>";

// The 4 foods that failed
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

try {
    require_once 'php/config.php';
    $pdo = getDBConnection();
    
    $addedCount = 0;
    $skippedCount = 0;
    $errorCount = 0;
    
    foreach ($failedFoods as $foodKey => $foodData) {
        echo "<h3>Processing: {$foodData['name']}</h3>";
        
        // Check if food already exists
        $checkStmt = $pdo->prepare("SELECT id FROM foods WHERE food_key = ? OR name = ?");
        $checkStmt->execute([$foodKey, $foodData['name']]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>⚠️ Already exists:</strong> {$foodData['name']}";
            echo "</div>";
            $skippedCount++;
            continue;
        }
        
        // Insert food directly
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
            echo "<strong>✅ Added successfully:</strong> {$foodData['name']}<br>";
            echo "Calories/100g: {$foodData['calories_per_100g']}, Protein: {$foodData['protein']}g, Carbs: {$foodData['carbs']}g, Fats: {$foodData['fats']}g";
            echo "</div>";
            $addedCount++;
        } else {
            echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>❌ Error adding:</strong> {$foodData['name']}";
            echo "</div>";
            $errorCount++;
        }
        
        echo "<hr>";
    }
    
    echo "<h2>Summary</h2>";
    echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px;'>";
    echo "<strong>Failed Foods Processed:</strong> " . count($failedFoods) . "<br>";
    echo "<strong>Successfully Added:</strong> $addedCount<br>";
    echo "<strong>Already Existed:</strong> $skippedCount<br>";
    echo "<strong>Errors:</strong> $errorCount<br>";
    echo "</div>";
    
    if ($errorCount == 0) {
        echo "<h3>🎉 All Failed Foods Successfully Added!</h3>";
        echo "<p>Now all 51 foods should be in the database. You can test the breakfast food cards!</p>";
    } else {
        echo "<h3>⚠️ Some Foods Still Failed</h3>";
        echo "<p>There might be a database constraint issue.</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<strong>❌ Database Connection Error:</strong><br>";
    echo $e->getMessage();
    echo "</div>";
}

echo "<h3>Next Steps:</h3>";
echo "<p>1. <a href='test_breakfast_foods.php'>Test Breakfast Foods</a> - Verify all foods are in database</p>";
echo "<p>2. <a href='pages/dashboard.php'>Go to Dashboard</a> - Test breakfast food cards</p>";
echo "<p>3. <a href='debug_failed_foods.php'>Debug Failed Foods</a> - Investigate API issues</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; }
h3 { color: #666; margin-top: 30px; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
hr { margin: 20px 0; }
</style>
