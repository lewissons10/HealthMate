<?php
require_once '../php/config.php';

echo "<h2>Add Food Tables to HealthMate Database</h2>";
echo "<p>This script adds the food database tables to your existing HealthMate database.</p>";

try {
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    // Read and execute the food tables SQL
    $sqlFile = __DIR__ . '/add_food_tables.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Food tables SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Remove the summary SELECT statements at the end
    $sql = preg_replace('/-- Show summary.*$/s', '', $sql);
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $successCount++;
        } catch (PDOException $e) {
            $errorCount++;
            echo "<p style='color: red;'>✗ Error executing statement: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p style='color: gray;'>Statement: " . htmlspecialchars(substr($statement, 0, 100)) . "...</p>";
        }
    }
    
    echo "<h3>Installation Summary:</h3>";
    echo "<p style='color: green;'>✓ Successful statements: $successCount</p>";
    echo "<p style='color: red;'>✗ Failed statements: $errorCount</p>";
    
    if ($errorCount === 0) {
        echo "<h3 style='color: green;'>🎉 Food Tables Installation Complete!</h3>";
        
        // Test the installation
        echo "<h4>Testing Installation:</h4>";
        
        // Count foods
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM foods");
        $count = $stmt->fetch()['count'];
        echo "<p>✓ Total foods in database: $count</p>";
        
        // Count categories
        $stmt = $pdo->query("SELECT DISTINCT category FROM foods");
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>✓ Food categories: " . implode(', ', $categories) . "</p>";
        
        // Count vitamins
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM food_vitamins");
        $vitaminCount = $stmt->fetch()['count'];
        echo "<p>✓ Total vitamin entries: $vitaminCount</p>";
        
        // Count minerals
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM food_minerals");
        $mineralCount = $stmt->fetch()['count'];
        echo "<p>✓ Total mineral entries: $mineralCount</p>";
        
        echo "<h4>Sample Data:</h4>";
        $stmt = $pdo->query("SELECT name, category, calories_per_100g, protein FROM foods LIMIT 5");
        $sampleFoods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Name</th><th>Category</th><th>Calories/100g</th><th>Protein</th></tr>";
        foreach ($sampleFoods as $food) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($food['name']) . "</td>";
            echo "<td>" . htmlspecialchars($food['category']) . "</td>";
            echo "<td>" . htmlspecialchars($food['calories_per_100g']) . "</td>";
            echo "<td>" . htmlspecialchars($food['protein']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h4>Category Breakdown:</h4>";
        $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM foods GROUP BY category ORDER BY count DESC");
        $categoryBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Category</th><th>Count</th></tr>";
        foreach ($categoryBreakdown as $category) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($category['category']) . "</td>";
            echo "<td>" . htmlspecialchars($category['count']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h4>API Endpoints Now Available:</h4>";
        echo "<ul>";
        echo "<li><code>GET /php/food_api.php?action=search&q=chicken</code> - Search foods</li>";
        echo "<li><code>GET /php/food_api.php?action=get_all</code> - Get all foods</li>";
        echo "<li><code>GET /php/food_api.php?action=get_by_category&category=protein</code> - Get foods by category</li>";
        echo "<li><code>GET /php/food_api.php?action=get_high_protein&min_protein=20</code> - Get high protein foods</li>";
        echo "<li><code>GET /php/food_api.php?action=get_by_calorie_range&min_calories=100&max_calories=200</code> - Get foods by calorie range</li>";
        echo "</ul>";
        
    } else {
        echo "<h3 style='color: red;'>❌ Installation completed with errors. Please check the errors above.</h3>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration in <code>php/config.php</code></p>";
}

echo "<hr>";
echo "<p><a href='../pages/dashboard.php'>← Back to Dashboard</a> | <a href='../test_integration.html'>Test Integration</a> | <a href='../test_food_api.php'>Test Food API</a></p>";
?>
