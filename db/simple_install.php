<?php
require_once '../php/config.php';

echo "<h2>Simple Food Database Installation</h2>";
echo "<p>This script creates the food database tables step by step.</p>";

try {
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    // Step 1: Create foods table
    echo "<h3>Step 1: Creating foods table...</h3>";
    $createFoodsTable = "
    CREATE TABLE IF NOT EXISTS foods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        food_key VARCHAR(100) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL,
        calories_per_100g DECIMAL(8,2) NOT NULL,
        protein DECIMAL(6,2) NOT NULL,
        carbs DECIMAL(6,2) NOT NULL,
        fats DECIMAL(6,2) NOT NULL,
        fiber DECIMAL(6,2) DEFAULT 0,
        sugar DECIMAL(6,2) DEFAULT 0,
        unit VARCHAR(20) NOT NULL DEFAULT 'g',
        category VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_category (category),
        INDEX idx_name (name),
        INDEX idx_food_key (food_key),
        INDEX idx_calories (calories_per_100g),
        INDEX idx_protein (protein),
        INDEX idx_carbs (carbs),
        INDEX idx_fats (fats)
    )";
    
    $pdo->exec($createFoodsTable);
    echo "<p style='color: green;'>✓ Foods table created successfully!</p>";
    
    // Step 2: Create food_vitamins table
    echo "<h3>Step 2: Creating food_vitamins table...</h3>";
    $createVitaminsTable = "
    CREATE TABLE IF NOT EXISTS food_vitamins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        food_id INT NOT NULL,
        vitamin_name VARCHAR(100) NOT NULL,
        amount DECIMAL(8,2) NOT NULL,
        unit VARCHAR(20) DEFAULT 'mg',
        FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE,
        INDEX idx_food_id (food_id),
        INDEX idx_vitamin (vitamin_name)
    )";
    
    $pdo->exec($createVitaminsTable);
    echo "<p style='color: green;'>✓ Food vitamins table created successfully!</p>";
    
    // Step 3: Create food_minerals table
    echo "<h3>Step 3: Creating food_minerals table...</h3>";
    $createMineralsTable = "
    CREATE TABLE IF NOT EXISTS food_minerals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        food_id INT NOT NULL,
        mineral_name VARCHAR(100) NOT NULL,
        amount DECIMAL(8,2) NOT NULL,
        unit VARCHAR(20) DEFAULT 'mg',
        FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE,
        INDEX idx_food_id (food_id),
        INDEX idx_mineral (mineral_name)
    )";
    
    $pdo->exec($createMineralsTable);
    echo "<p style='color: green;'>✓ Food minerals table created successfully!</p>";
    
    // Step 4: Insert sample foods
    echo "<h3>Step 4: Inserting sample foods...</h3>";
    
    $sampleFoods = [
        ['chicken_breast', 'Chicken Breast', 165, 31, 0, 3.6, 0, 0, 'g', 'protein'],
        ['salmon', 'Salmon', 208, 25, 0, 12, 0, 0, 'g', 'protein'],
        ['eggs', 'Eggs', 155, 13, 1.1, 11, 0, 1.1, 'g', 'protein'],
        ['greek_yogurt', 'Greek Yogurt', 59, 10, 3.6, 0.4, 0, 3.6, 'g', 'dairy'],
        ['brown_rice', 'Brown Rice', 111, 2.6, 23, 0.9, 1.8, 0.4, 'g', 'grain'],
        ['sweet_potato', 'Sweet Potato', 86, 1.6, 20, 0.1, 3, 4.2, 'g', 'vegetable'],
        ['quinoa', 'Quinoa', 120, 4.4, 22, 1.9, 2.8, 0.9, 'g', 'grain'],
        ['oatmeal', 'Oatmeal', 389, 16.9, 66, 6.9, 10.6, 0.99, 'g', 'grain'],
        ['broccoli', 'Broccoli', 34, 2.8, 7, 0.4, 2.6, 1.5, 'g', 'vegetable'],
        ['spinach', 'Spinach', 23, 2.9, 3.6, 0.4, 2.2, 0.4, 'g', 'vegetable'],
        ['avocado', 'Avocado', 160, 2, 8.5, 14.7, 6.7, 0.7, 'g', 'fruit'],
        ['almonds', 'Almonds', 579, 21.2, 21.6, 49.9, 12.5, 4.4, 'g', 'nuts'],
        ['olive_oil', 'Olive Oil', 884, 0, 0, 100, 0, 0, 'ml', 'fat'],
        ['cheese', 'Cheddar Cheese', 403, 25, 1.3, 33, 0, 0.5, 'g', 'dairy'],
        ['apple', 'Apple', 52, 0.3, 14, 0.2, 2.4, 10.4, 'g', 'fruit']
    ];
    
    $insertFood = $pdo->prepare("
        INSERT IGNORE INTO foods (food_key, name, calories_per_100g, protein, carbs, fats, fiber, sugar, unit, category)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $foodCount = 0;
    foreach ($sampleFoods as $food) {
        $insertFood->execute($food);
        $foodCount++;
    }
    
    echo "<p style='color: green;'>✓ Inserted $foodCount sample foods!</p>";
    
    // Step 5: Insert sample vitamins
    echo "<h3>Step 5: Inserting sample vitamins...</h3>";
    
    $sampleVitamins = [
        ['chicken_breast', 'Niacin', 14.8, 'mg'],
        ['chicken_breast', 'Vitamin B6', 1.0, 'mg'],
        ['salmon', 'Vitamin D', 11, 'mcg'],
        ['salmon', 'Vitamin B12', 3.2, 'mcg'],
        ['eggs', 'Vitamin B12', 0.6, 'mcg'],
        ['eggs', 'Vitamin A', 140, 'mcg'],
        ['broccoli', 'Vitamin C', 89.2, 'mg'],
        ['broccoli', 'Vitamin K', 101.6, 'mcg'],
        ['spinach', 'Vitamin K', 483, 'mcg'],
        ['spinach', 'Vitamin A', 469, 'mcg'],
        ['avocado', 'Vitamin K', 21, 'mcg'],
        ['avocado', 'Folate', 81, 'mcg'],
        ['almonds', 'Vitamin E', 25.6, 'mg'],
        ['almonds', 'Riboflavin', 1.1, 'mg'],
        ['olive_oil', 'Vitamin E', 14.4, 'mg'],
        ['olive_oil', 'Vitamin K', 60.2, 'mcg']
    ];
    
    $insertVitamin = $pdo->prepare("
        INSERT IGNORE INTO food_vitamins (food_id, vitamin_name, amount, unit)
        VALUES ((SELECT id FROM foods WHERE food_key = ?), ?, ?, ?)
    ");
    
    $vitaminCount = 0;
    foreach ($sampleVitamins as $vitamin) {
        $insertVitamin->execute($vitamin);
        $vitaminCount++;
    }
    
    echo "<p style='color: green;'>✓ Inserted $vitaminCount vitamin entries!</p>";
    
    // Step 6: Insert sample minerals
    echo "<h3>Step 6: Inserting sample minerals...</h3>";
    
    $sampleMinerals = [
        ['chicken_breast', 'Selenium', 27.4, 'mcg'],
        ['chicken_breast', 'Phosphorus', 228, 'mg'],
        ['salmon', 'Selenium', 36.5, 'mcg'],
        ['salmon', 'Phosphorus', 252, 'mg'],
        ['eggs', 'Selenium', 30.7, 'mcg'],
        ['eggs', 'Phosphorus', 198, 'mg'],
        ['broccoli', 'Potassium', 316, 'mg'],
        ['broccoli', 'Manganese', 0.2, 'mg'],
        ['spinach', 'Iron', 2.7, 'mg'],
        ['spinach', 'Magnesium', 79, 'mg'],
        ['avocado', 'Potassium', 485, 'mg'],
        ['avocado', 'Manganese', 0.1, 'mg'],
        ['almonds', 'Magnesium', 270, 'mg'],
        ['almonds', 'Manganese', 2.3, 'mg'],
        ['olive_oil', 'Iron', 0.6, 'mg'],
        ['olive_oil', 'Sodium', 2, 'mg']
    ];
    
    $insertMineral = $pdo->prepare("
        INSERT IGNORE INTO food_minerals (food_id, mineral_name, amount, unit)
        VALUES ((SELECT id FROM foods WHERE food_key = ?), ?, ?, ?)
    ");
    
    $mineralCount = 0;
    foreach ($sampleMinerals as $mineral) {
        $insertMineral->execute($mineral);
        $mineralCount++;
    }
    
    echo "<p style='color: green;'>✓ Inserted $mineralCount mineral entries!</p>";
    
    // Step 7: Create search view
    echo "<h3>Step 7: Creating search view...</h3>";
    $createView = "
    CREATE OR REPLACE VIEW food_search_view AS
    SELECT 
        f.id,
        f.food_key,
        f.name,
        f.calories_per_100g,
        f.protein,
        f.carbs,
        f.fats,
        f.fiber,
        f.sugar,
        f.unit,
        f.category,
        GROUP_CONCAT(DISTINCT CONCAT(fv.vitamin_name, ':', fv.amount, fv.unit) SEPARATOR '|') as vitamins,
        GROUP_CONCAT(DISTINCT CONCAT(fm.mineral_name, ':', fm.amount, fm.unit) SEPARATOR '|') as minerals
    FROM foods f
    LEFT JOIN food_vitamins fv ON f.id = fv.food_id
    LEFT JOIN food_minerals fm ON f.id = fm.food_id
    GROUP BY f.id, f.food_key, f.name, f.calories_per_100g, f.protein, f.carbs, f.fats, f.fiber, f.sugar, f.unit, f.category
    ";
    
    $pdo->exec($createView);
    echo "<p style='color: green;'>✓ Search view created successfully!</p>";
    
    // Final verification
    echo "<h3>Installation Complete! 🎉</h3>";
    
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
    
    echo "<h4>API Endpoints Now Available:</h4>";
    echo "<ul>";
    echo "<li><code>GET /php/food_api.php?action=search&q=chicken</code> - Search foods</li>";
    echo "<li><code>GET /php/food_api.php?action=get_all</code> - Get all foods</li>";
    echo "<li><code>GET /php/food_api.php?action=get_by_category&category=protein</code> - Get foods by category</li>";
    echo "<li><code>GET /php/food_api.php?action=get_high_protein&min_protein=20</code> - Get high protein foods</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration in <code>php/config.php</code></p>";
}

echo "<hr>";
echo "<p><a href='../pages/dashboard.php'>← Back to Dashboard</a> | <a href='../test_integration.html'>Test Integration</a> | <a href='../test_food_api.php'>Test Food API</a></p>";
?>
