<?php
require_once __DIR__ . '/../php/config.php';

try {
    $pdo = getDBConnection();
    
    // Read and execute the meal history SQL
    $sql = file_get_contents(__DIR__ . '/meal_history.sql');
    
    // Split by semicolon and execute each statement
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "\n✅ Meal history tables created successfully!\n";
    echo "Tables created:\n";
    echo "- daily_meal_history (stores individual meal items)\n";
    echo "- daily_nutrition_totals (stores daily totals for faster queries)\n";
    
} catch (Exception $e) {
    echo "❌ Error creating tables: " . $e->getMessage() . "\n";
}
?>
