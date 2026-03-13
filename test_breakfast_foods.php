<?php
// Test specific breakfast food keys from dashboard
$breakfastFoods = [
    'eggs',
    'greek_yogurt', 
    'protein_powder',
    'cottage_cheese',
    'oatmeal',
    'whole_grain_bread',
    'banana',
    'quinoa',
    'avocado',
    'almonds',
    'chia_seeds',
    'peanut_butter'
];

echo "<h2>Testing Breakfast Food Keys</h2>";
echo "<p>Testing if these food keys exist in the database:</p>";

foreach ($breakfastFoods as $foodKey) {
    echo "<h3>Testing: $foodKey</h3>";
    
    $url = "http://localhost/fitness-app/php/food_api.php?action=search&q=" . urlencode($foodKey) . "&limit=1";
    echo "<p>URL: <a href='$url' target='_blank'>$url</a></p>";
    
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if ($data && isset($data['foods']) && count($data['foods']) > 0) {
        $food = $data['foods'][0];
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>✓ Found:</strong> " . $food['name'] . "<br>";
        echo "Calories/100g: " . $food['calories_per_100g'] . "<br>";
        echo "Protein: " . $food['protein'] . "g<br>";
        echo "Carbs: " . $food['carbs'] . "g<br>";
        echo "Fats: " . $food['fats'] . "g<br>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>❌ Not Found</strong><br>";
        if ($data && isset($data['error'])) {
            echo "Error: " . $data['error'];
        } else {
            echo "No foods found for this key";
        }
        echo "</div>";
    }
    
    echo "<hr>";
}

echo "<h3>All Foods in Database</h3>";
$allUrl = "http://localhost/fitness-app/php/food_api.php?action=get_all&limit=20";
echo "<p>URL: <a href='$allUrl' target='_blank'>$allUrl</a></p>";

$allResponse = file_get_contents($allUrl);
$allData = json_decode($allResponse, true);

if ($allData && isset($allData['foods'])) {
    echo "<div style='background: #d1ecf1; padding: 10px; border-radius: 5px;'>";
    echo "<strong>Available Foods:</strong><br>";
    foreach ($allData['foods'] as $food) {
        echo "• " . $food['name'] . " (" . $food['category'] . ")<br>";
    }
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
    echo "<strong>❌ Error loading all foods</strong>";
    if ($allData && isset($allData['error'])) {
        echo "<br>Error: " . $allData['error'];
    }
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; }
h3 { color: #666; margin-top: 30px; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
hr { margin: 20px 0; }
</style>
