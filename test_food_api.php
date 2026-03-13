<?php
echo "<h2>HealthMate Food API Test</h2>";

// Test API endpoints
$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/php/food_api.php';

$tests = [
    'Search Foods' => $baseUrl . '?action=search&q=chicken',
    'Get All Foods' => $baseUrl . '?action=get_all&limit=5',
    'Get Protein Foods' => $baseUrl . '?action=get_by_category&category=protein',
    'Get High Protein Foods' => $baseUrl . '?action=get_high_protein&min_protein=20',
    'Get Foods by Calorie Range' => $baseUrl . '?action=get_by_calorie_range&min_calories=100&max_calories=200'
];

foreach ($tests as $testName => $url) {
    echo "<h3>$testName</h3>";
    echo "<p><strong>URL:</strong> <code>$url</code></p>";
    
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if ($data === null) {
        echo "<p style='color: red;'>❌ Invalid JSON response</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    } else {
        echo "<p style='color: green;'>✓ Valid JSON response</p>";
        
        if (isset($data['error'])) {
            echo "<p style='color: red;'>❌ API Error: " . htmlspecialchars($data['error']) . "</p>";
        } else {
            echo "<p style='color: green;'>✓ API Success</p>";
            
            if (isset($data['foods'])) {
                $count = count($data['foods']);
                echo "<p>Found $count foods:</p>";
                
                if ($count > 0) {
                    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
                    echo "<tr><th>Name</th><th>Category</th><th>Calories/100g</th><th>Protein</th><th>Carbs</th><th>Fats</th></tr>";
                    
                    foreach (array_slice($data['foods'], 0, 5) as $food) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($food['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($food['category']) . "</td>";
                        echo "<td>" . htmlspecialchars($food['calories_per_100g']) . "</td>";
                        echo "<td>" . htmlspecialchars($food['protein']) . "</td>";
                        echo "<td>" . htmlspecialchars($food['carbs']) . "</td>";
                        echo "<td>" . htmlspecialchars($food['fats']) . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                    
                    if ($count > 5) {
                        echo "<p>... and " . ($count - 5) . " more foods</p>";
                    }
                }
            } elseif (isset($data['food'])) {
                $food = $data['food'];
                echo "<p>Single food result:</p>";
                echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
                echo "<tr><th>Name</th><th>Category</th><th>Calories/100g</th><th>Protein</th><th>Carbs</th><th>Fats</th></tr>";
                echo "<tr>";
                echo "<td>" . htmlspecialchars($food['name']) . "</td>";
                echo "<td>" . htmlspecialchars($food['category']) . "</td>";
                echo "<td>" . htmlspecialchars($food['calories_per_100g']) . "</td>";
                echo "<td>" . htmlspecialchars($food['protein']) . "</td>";
                echo "<td>" . htmlspecialchars($food['carbs']) . "</td>";
                echo "<td>" . htmlspecialchars($food['fats']) . "</td>";
                echo "</tr>";
                echo "</table>";
            }
        }
    }
    
    echo "<hr>";
}

echo "<h3>API Usage Examples</h3>";
echo "<h4>JavaScript AJAX Example:</h4>";
echo "<pre>";
echo htmlspecialchars('
// Search for foods
fetch("php/food_api.php?action=search&q=chicken")
    .then(response => response.json())
    .then(data => {
        if (data.foods) {
            console.log("Found foods:", data.foods);
        } else {
            console.error("Error:", data.error);
        }
    });

// Get high protein foods
fetch("php/food_api.php?action=get_high_protein&min_protein=25")
    .then(response => response.json())
    .then(data => {
        console.log("High protein foods:", data.foods);
    });
');
echo "</pre>";

echo "<h4>PHP cURL Example:</h4>";
echo "<pre>";
echo htmlspecialchars('
$url = "http://localhost/fitness-app/php/food_api.php?action=search&q=salmon";
$response = file_get_contents($url);
$data = json_decode($response, true);

if ($data && isset($data["foods"])) {
    foreach ($data["foods"] as $food) {
        echo $food["name"] . " - " . $food["calories_per_100g"] . " cal/100g\n";
    }
}
');
echo "</pre>";

echo "<hr>";
echo "<p><a href='pages/dashboard.php'>← Back to Dashboard</a> | <a href='db/install_food_db.php'>Install Food Database</a></p>";
?>
