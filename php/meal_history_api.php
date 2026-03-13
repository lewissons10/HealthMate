<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$action = $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];

try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

// Save daily meals
if ($action === 'save_daily_meals') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['meals']) || !isset($input['date'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }
    
    $date = $input['date'];
    $meals = $input['meals'];
    
    try {
        $pdo->beginTransaction();
        
        // Delete existing meals for this date
        $stmt = $pdo->prepare("DELETE FROM daily_meal_history WHERE user_id = ? AND date = ?");
        $stmt->execute([$userId, $date]);
        
        // Delete existing totals for this date
        $stmt = $pdo->prepare("DELETE FROM daily_nutrition_totals WHERE user_id = ? AND date = ?");
        $stmt->execute([$userId, $date]);
        
        $totalCalories = 0;
        $totalProtein = 0;
        $totalCarbs = 0;
        $totalFats = 0;
        $totalFiber = 0;
        $totalSugar = 0;
        
        // Insert new meals
        $stmt = $pdo->prepare("
            INSERT INTO daily_meal_history 
            (user_id, date, meal_type, food_name, calories, protein, carbs, fats, fiber, sugar, amount, unit)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($meals as $mealType => $foods) {
            foreach ($foods as $food) {
                $stmt->execute([
                    $userId,
                    $date,
                    $mealType,
                    $food['name'],
                    $food['calories'],
                    $food['protein'],
                    $food['carbs'],
                    $food['fats'],
                    $food['fiber'] ?? 0,
                    $food['sugar'] ?? 0,
                    $food['amount'] ?? 1,
                    $food['unit'] ?? 'serving'
                ]);
                
                $totalCalories += $food['calories'];
                $totalProtein += $food['protein'];
                $totalCarbs += $food['carbs'];
                $totalFats += $food['fats'];
                $totalFiber += $food['fiber'] ?? 0;
                $totalSugar += $food['sugar'] ?? 0;
            }
        }
        
        // Insert daily totals
        $stmt = $pdo->prepare("
            INSERT INTO daily_nutrition_totals 
            (user_id, date, total_calories, total_protein, total_carbs, total_fats, total_fiber, total_sugar)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $date,
            $totalCalories,
            $totalProtein,
            $totalCarbs,
            $totalFats,
            $totalFiber,
            $totalSugar
        ]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Daily meals saved successfully',
            'totals' => [
                'calories' => $totalCalories,
                'protein' => $totalProtein,
                'carbs' => $totalCarbs,
                'fats' => $totalFats
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to save meals: ' . $e->getMessage()]);
    }
    exit;
}

// Save user nutrition goals (BMR/TDEE/targets)
if ($action === 'save_goals' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Ensure table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_nutrition_goals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            bmr INT NULL,
            tdee INT NULL,
            goal_calories INT NULL,
            protein_goal INT NULL,
            carb_goal INT NULL,
            fat_goal INT NULL,
            fitness_goal VARCHAR(32) NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $bmr = isset($input['bmr']) ? (int)$input['bmr'] : null;
        $tdee = isset($input['tdee']) ? (int)$input['tdee'] : null;
        $goalCalories = isset($input['goalCalories']) ? (int)$input['goalCalories'] : null;
        $protein = isset($input['proteinGoal']) ? (int)$input['proteinGoal'] : null;
        $carbs = isset($input['carbGoal']) ? (int)$input['carbGoal'] : null;
        $fats = isset($input['fatGoal']) ? (int)$input['fatGoal'] : null;
        $fitnessGoal = isset($input['fitnessGoal']) ? substr((string)$input['fitnessGoal'], 0, 32) : null;

        // Upsert goals
        $stmt = $pdo->prepare("SELECT user_id FROM user_nutrition_goals WHERE user_id = ?");
        $stmt->execute([$userId]);
        if ($stmt->fetchColumn()) {
            $upd = $pdo->prepare("UPDATE user_nutrition_goals SET bmr = :bmr, tdee = :tdee, goal_calories = :gc, protein_goal = :p, carb_goal = :c, fat_goal = :f, fitness_goal = :fg WHERE user_id = :uid");
            $upd->execute([':bmr'=>$bmr, ':tdee'=>$tdee, ':gc'=>$goalCalories, ':p'=>$protein, ':c'=>$carbs, ':f'=>$fats, ':fg'=>$fitnessGoal, ':uid'=>$userId]);
        } else {
            $ins = $pdo->prepare("INSERT INTO user_nutrition_goals (user_id, bmr, tdee, goal_calories, protein_goal, carb_goal, fat_goal, fitness_goal) VALUES (:uid, :bmr, :tdee, :gc, :p, :c, :f, :fg)");
            $ins->execute([':uid'=>$userId, ':bmr'=>$bmr, ':tdee'=>$tdee, ':gc'=>$goalCalories, ':p'=>$protein, ':c'=>$carbs, ':f'=>$fats, ':fg'=>$fitnessGoal]);
        }

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save goals']);
    }
    exit;
}

// Get user nutrition goals
if ($action === 'get_goals') {
    try {
        $stmt = $pdo->prepare("SELECT bmr, tdee, goal_calories, protein_goal, carb_goal, fat_goal, fitness_goal FROM user_nutrition_goals WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            echo json_encode(['success' => true, 'goals' => null]);
            exit;
        }
        echo json_encode(['success' => true, 'goals' => $row]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to load goals']);
    }
    exit;
}

// Get historical meal data
if ($action === 'get_historical_data') {
    $period = $_GET['period'] ?? 'week';
    $startDate = '';
    $endDate = date('Y-m-d');
    
    switch ($period) {
        case 'week':
            $startDate = date('Y-m-d', strtotime('-6 days'));
            break;
        case 'month':
            $startDate = date('Y-m-d', strtotime('-3 weeks'));
            break;
        case 'year':
            $startDate = date('Y-m-d', strtotime('-11 months'));
            break;
    }
    
    try {
        // Get daily totals for the period
        $stmt = $pdo->prepare("
            SELECT date, total_calories, total_protein, total_carbs, total_fats
            FROM daily_nutrition_totals 
            WHERE user_id = ? AND date BETWEEN ? AND ?
            ORDER BY date ASC
        ");
        $stmt->execute([$userId, $startDate, $endDate]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fill in missing days with 0 values
        $data = [];
        $labels = [];
        
        if ($period === 'week') {
            // Last 7 days
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $labels[] = date('D', strtotime($date));
                
                $found = false;
                foreach ($results as $result) {
                    if ($result['date'] === $date) {
                        $data[] = (float)$result['total_calories'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $data[] = 0;
                }
            }
        } elseif ($period === 'month') {
            // Last 4 weeks
            for ($i = 3; $i >= 0; $i--) {
                $weekStart = date('Y-m-d', strtotime("-$i weeks"));
                $weekEnd = date('Y-m-d', strtotime("-$i weeks +6 days"));
                $labels[] = "Week " . (4 - $i);
                
                $weekTotal = 0;
                foreach ($results as $result) {
                    if ($result['date'] >= $weekStart && $result['date'] <= $weekEnd) {
                        $weekTotal += (float)$result['total_calories'];
                    }
                }
                $data[] = $weekTotal;
            }
        } elseif ($period === 'year') {
            // Last 12 months
            for ($i = 11; $i >= 0; $i--) {
                $monthStart = date('Y-m-01', strtotime("-$i months"));
                $monthEnd = date('Y-m-t', strtotime("-$i months"));
                $labels[] = date('M', strtotime($monthStart));
                
                $monthTotal = 0;
                foreach ($results as $result) {
                    if ($result['date'] >= $monthStart && $result['date'] <= $monthEnd) {
                        $monthTotal += (float)$result['total_calories'];
                    }
                }
                $data[] = $monthTotal;
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'labels' => $labels,
                'data' => $data,
                'period' => $period
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get historical data: ' . $e->getMessage()]);
    }
    exit;
}

// Get today's meals
if ($action === 'get_todays_meals') {
    $date = $_GET['date'] ?? date('Y-m-d');
    
    try {
        $stmt = $pdo->prepare("
            SELECT meal_type, food_name, calories, protein, carbs, fats, fiber, sugar, amount, unit
            FROM daily_meal_history 
            WHERE user_id = ? AND date = ?
            ORDER BY meal_type, id
        ");
        $stmt->execute([$userId, $date]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organize by meal type
        $meals = [
            'breakfast' => [],
            'lunch' => [],
            'dinner' => [],
            'snacks' => []
        ];
        
        foreach ($results as $row) {
            $meals[$row['meal_type']][] = [
                'name' => $row['food_name'],
                'calories' => (float)$row['calories'],
                'protein' => (float)$row['protein'],
                'carbs' => (float)$row['carbs'],
                'fats' => (float)$row['fats'],
                'fiber' => (float)$row['fiber'],
                'sugar' => (float)$row['sugar'],
                'amount' => (float)$row['amount'],
                'unit' => $row['unit']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'meals' => $meals
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get today\'s meals: ' . $e->getMessage()]);
    }
    exit;
}

// Get nutrition series for period (day/week/month)
if ($action === 'get_nutrition_series') {
    $period = $_GET['period'] ?? 'week';
    $days = 7;
    if ($period === 'day') { $days = 1; }
    if ($period === 'month') { $days = 30; }
    try {
        // Prefer summarized table
        $stmt = $pdo->prepare("SELECT date, total_calories, total_protein, total_carbs, total_fats FROM daily_nutrition_totals WHERE user_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) ORDER BY date ASC");
        $stmt->execute([$userId, $days - 1]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows || count($rows) === 0) {
            // Fallback aggregate from daily_meal_history
            $stmt = $pdo->prepare("SELECT DATE(date) AS date, COALESCE(SUM(calories),0) AS total_calories, COALESCE(SUM(protein),0) AS total_protein, COALESCE(SUM(carbs),0) AS total_carbs, COALESCE(SUM(fats),0) AS total_fats FROM daily_meal_history WHERE user_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) GROUP BY DATE(date) ORDER BY date ASC");
            $stmt->execute([$userId, $days - 1]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        echo json_encode(['success' => true, 'series' => $rows]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to load nutrition series']);
    }
    exit;
}

// Get today's macro totals
if ($action === 'get_today_macros') {
    $date = date('Y-m-d');
    try {
        $stmt = $pdo->prepare("SELECT total_calories, total_protein, total_carbs, total_fats FROM daily_nutrition_totals WHERE user_id = ? AND date = ?");
        $stmt->execute([$userId, $date]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(calories),0) AS total_calories, COALESCE(SUM(protein),0) AS total_protein, COALESCE(SUM(carbs),0) AS total_carbs, COALESCE(SUM(fats),0) AS total_fats FROM daily_meal_history WHERE user_id = ? AND date = ?");
            $stmt->execute([$userId, $date]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_calories'=>0,'total_protein'=>0,'total_carbs'=>0,'total_fats'=>0];
        }
        echo json_encode(['success' => true, 'macros' => $row]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to load today\'s macros']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
