<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Get the request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    
    switch ($action) {
        case 'search':
            handleFoodSearch($pdo);
            break;
        case 'get_all':
            handleGetAllFoods($pdo);
            break;
        case 'get_by_category':
            handleGetByCategory($pdo);
            break;
        case 'get_by_id':
            handleGetById($pdo);
            break;
        case 'get_high_protein':
            handleGetHighProtein($pdo);
            break;
        case 'get_by_calorie_range':
            handleGetByCalorieRange($pdo);
            break;
        case 'add_food':
            handleAddFood($pdo);
            break;
        case 'update_food':
            handleUpdateFood($pdo);
            break;
        case 'delete_food':
            handleDeleteFood($pdo);
            break;
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleFoodSearch($pdo) {
    $query = $_GET['q'] ?? '';
    $limit = intval($_GET['limit'] ?? 20);
    
    if (strlen($query) < 2) {
        throw new Exception('Query must be at least 2 characters long');
    }
    
    $searchTerm = '%' . $query . '%';
    
    try {
        $stmt = $pdo->prepare("
            SELECT f.*, 
                   GROUP_CONCAT(DISTINCT CONCAT(fv.vitamin_name, ':', fv.amount, fv.unit) SEPARATOR '|') as vitamins,
                   GROUP_CONCAT(DISTINCT CONCAT(fm.mineral_name, ':', fm.amount, fm.unit) SEPARATOR '|') as minerals
            FROM foods f
            LEFT JOIN food_vitamins fv ON f.id = fv.food_id
            LEFT JOIN food_minerals fm ON f.id = fm.food_id
            WHERE f.name LIKE ? OR f.food_key LIKE ?
            GROUP BY f.id
            ORDER BY f.name
            LIMIT " . intval($limit)
        );
        
        $stmt->execute([$searchTerm, $searchTerm]);
        $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse vitamins and minerals
        foreach ($foods as &$food) {
            $food['vitamins'] = parseNutrients($food['vitamins']);
            $food['minerals'] = parseNutrients($food['minerals']);
        }
        
        echo json_encode(['foods' => $foods]);
        
    } catch (PDOException $e) {
        // Fallback to simple search if GROUP_CONCAT fails
        $stmt = $pdo->prepare("
            SELECT f.*
            FROM foods f
            WHERE f.name LIKE ? OR f.food_key LIKE ?
            ORDER BY f.name
            LIMIT " . intval($limit)
        );
        
        $stmt->execute([$searchTerm, $searchTerm]);
        $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add empty vitamins and minerals
        foreach ($foods as &$food) {
            $food['vitamins'] = [];
            $food['minerals'] = [];
        }
        
        echo json_encode(['foods' => $foods]);
    }
}

function handleGetAllFoods($pdo) {
    $limit = intval($_GET['limit'] ?? 100);
    $offset = intval($_GET['offset'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("
            SELECT f.*, 
                   GROUP_CONCAT(DISTINCT CONCAT(fv.vitamin_name, ':', fv.amount, fv.unit) SEPARATOR '|') as vitamins,
                   GROUP_CONCAT(DISTINCT CONCAT(fm.mineral_name, ':', fm.amount, fm.unit) SEPARATOR '|') as minerals
            FROM foods f
            LEFT JOIN food_vitamins fv ON f.id = fv.food_id
            LEFT JOIN food_minerals fm ON f.id = fm.food_id
            GROUP BY f.id
            ORDER BY f.name
            LIMIT " . intval($limit) . " OFFSET " . intval($offset)
        );
        
        $stmt->execute();
        $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse vitamins and minerals
        foreach ($foods as &$food) {
            $food['vitamins'] = parseNutrients($food['vitamins']);
            $food['minerals'] = parseNutrients($food['minerals']);
        }
        
        echo json_encode(['foods' => $foods]);
        
    } catch (PDOException $e) {
        // Fallback to simple query if GROUP_CONCAT fails
        $stmt = $pdo->prepare("
            SELECT f.*
            FROM foods f
            ORDER BY f.name
            LIMIT " . intval($limit) . " OFFSET " . intval($offset)
        );
        
        $stmt->execute();
        $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add empty vitamins and minerals
        foreach ($foods as &$food) {
            $food['vitamins'] = [];
            $food['minerals'] = [];
        }
        
        echo json_encode(['foods' => $foods]);
    }
}

function handleGetByCategory($pdo) {
    $category = $_GET['category'] ?? '';
    
    if (empty($category)) {
        throw new Exception('Category parameter is required');
    }
    
    $stmt = $pdo->prepare("
        SELECT f.*, 
               GROUP_CONCAT(DISTINCT CONCAT(fv.vitamin_name, ':', fv.amount, fv.unit) SEPARATOR '|') as vitamins,
               GROUP_CONCAT(DISTINCT CONCAT(fm.mineral_name, ':', fm.amount, fm.unit) SEPARATOR '|') as minerals
        FROM foods f
        LEFT JOIN food_vitamins fv ON f.id = fv.food_id
        LEFT JOIN food_minerals fm ON f.id = fm.food_id
        WHERE f.category = ?
        GROUP BY f.id
        ORDER BY f.name
    ");
    
    $stmt->execute([$category]);
    $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse vitamins and minerals
    foreach ($foods as &$food) {
        $food['vitamins'] = parseNutrients($food['vitamins']);
        $food['minerals'] = parseNutrients($food['minerals']);
    }
    
    echo json_encode(['foods' => $foods]);
}

function handleGetById($pdo) {
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        throw new Exception('Valid ID is required');
    }
    
    $stmt = $pdo->prepare("
        SELECT f.*, 
               GROUP_CONCAT(DISTINCT CONCAT(fv.vitamin_name, ':', fv.amount, fv.unit) SEPARATOR '|') as vitamins,
               GROUP_CONCAT(DISTINCT CONCAT(fm.mineral_name, ':', fm.amount, fm.unit) SEPARATOR '|') as minerals
        FROM foods f
        LEFT JOIN food_vitamins fv ON f.id = fv.food_id
        LEFT JOIN food_minerals fm ON f.id = fm.food_id
        WHERE f.id = ?
        GROUP BY f.id
    ");
    
    $stmt->execute([$id]);
    $food = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$food) {
        throw new Exception('Food not found');
    }
    
    // Parse vitamins and minerals
    $food['vitamins'] = parseNutrients($food['vitamins']);
    $food['minerals'] = parseNutrients($food['minerals']);
    
    echo json_encode(['food' => $food]);
}

function handleGetHighProtein($pdo) {
    $minProtein = floatval($_GET['min_protein'] ?? 20);
    
    $stmt = $pdo->prepare("
        SELECT f.*, 
               GROUP_CONCAT(DISTINCT CONCAT(fv.vitamin_name, ':', fv.amount, fv.unit) SEPARATOR '|') as vitamins,
               GROUP_CONCAT(DISTINCT CONCAT(fm.mineral_name, ':', fm.amount, fm.unit) SEPARATOR '|') as minerals
        FROM foods f
        LEFT JOIN food_vitamins fv ON f.id = fv.food_id
        LEFT JOIN food_minerals fm ON f.id = fm.food_id
        WHERE f.protein >= ?
        GROUP BY f.id
        ORDER BY f.protein DESC
    ");
    
    $stmt->execute([$minProtein]);
    $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse vitamins and minerals
    foreach ($foods as &$food) {
        $food['vitamins'] = parseNutrients($food['vitamins']);
        $food['minerals'] = parseNutrients($food['minerals']);
    }
    
    echo json_encode(['foods' => $foods]);
}

function handleGetByCalorieRange($pdo) {
    $minCalories = floatval($_GET['min_calories'] ?? 0);
    $maxCalories = floatval($_GET['max_calories'] ?? 1000);
    
    $stmt = $pdo->prepare("
        SELECT f.*, 
               GROUP_CONCAT(DISTINCT CONCAT(fv.vitamin_name, ':', fv.amount, fv.unit) SEPARATOR '|') as vitamins,
               GROUP_CONCAT(DISTINCT CONCAT(fm.mineral_name, ':', fm.amount, fm.unit) SEPARATOR '|') as minerals
        FROM foods f
        LEFT JOIN food_vitamins fv ON f.id = fv.food_id
        LEFT JOIN food_minerals fm ON f.id = fm.food_id
        WHERE f.calories_per_100g BETWEEN ? AND ?
        GROUP BY f.id
        ORDER BY f.calories_per_100g
    ");
    
    $stmt->execute([$minCalories, $maxCalories]);
    $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse vitamins and minerals
    foreach ($foods as &$food) {
        $food['vitamins'] = parseNutrients($food['vitamins']);
        $food['minerals'] = parseNutrients($food['minerals']);
    }
    
    echo json_encode(['foods' => $foods]);
}

function handleAddFood($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('POST method required');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['food_key', 'name', 'calories_per_100g', 'protein', 'carbs', 'fats', 'unit', 'category'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    $pdo->beginTransaction();
    
    try {
        // Insert food
        $stmt = $pdo->prepare("
            INSERT INTO foods (food_key, name, calories_per_100g, protein, carbs, fats, fiber, sugar, unit, category)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['food_key'],
            $input['name'],
            $input['calories_per_100g'],
            $input['protein'],
            $input['carbs'],
            $input['fats'],
            $input['fiber'] ?? 0,
            $input['sugar'] ?? 0,
            $input['unit'],
            $input['category']
        ]);
        
        $foodId = $pdo->lastInsertId();
        
        // Insert vitamins
        if (isset($input['vitamins']) && is_array($input['vitamins'])) {
            $vitaminStmt = $pdo->prepare("INSERT INTO food_vitamins (food_id, vitamin_name, amount, unit) VALUES (?, ?, ?, ?)");
            foreach ($input['vitamins'] as $vitamin) {
                $vitaminStmt->execute([$foodId, $vitamin['name'], $vitamin['amount'], $vitamin['unit'] ?? 'mg']);
            }
        }
        
        // Insert minerals
        if (isset($input['minerals']) && is_array($input['minerals'])) {
            $mineralStmt = $pdo->prepare("INSERT INTO food_minerals (food_id, mineral_name, amount, unit) VALUES (?, ?, ?, ?)");
            foreach ($input['minerals'] as $mineral) {
                $mineralStmt->execute([$foodId, $mineral['name'], $mineral['amount'], $mineral['unit'] ?? 'mg']);
            }
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'food_id' => $foodId]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function handleUpdateFood($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        throw new Exception('PUT method required');
    }
    
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('Valid ID is required');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $pdo->beginTransaction();
    
    try {
        // Update food
        $stmt = $pdo->prepare("
            UPDATE foods 
            SET name = ?, calories_per_100g = ?, protein = ?, carbs = ?, fats = ?, 
                fiber = ?, sugar = ?, unit = ?, category = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $stmt->execute([
            $input['name'],
            $input['calories_per_100g'],
            $input['protein'],
            $input['carbs'],
            $input['fats'],
            $input['fiber'] ?? 0,
            $input['sugar'] ?? 0,
            $input['unit'],
            $input['category'],
            $id
        ]);
        
        // Delete existing vitamins and minerals
        $pdo->prepare("DELETE FROM food_vitamins WHERE food_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM food_minerals WHERE food_id = ?")->execute([$id]);
        
        // Insert new vitamins
        if (isset($input['vitamins']) && is_array($input['vitamins'])) {
            $vitaminStmt = $pdo->prepare("INSERT INTO food_vitamins (food_id, vitamin_name, amount, unit) VALUES (?, ?, ?, ?)");
            foreach ($input['vitamins'] as $vitamin) {
                $vitaminStmt->execute([$id, $vitamin['name'], $vitamin['amount'], $vitamin['unit'] ?? 'mg']);
            }
        }
        
        // Insert new minerals
        if (isset($input['minerals']) && is_array($input['minerals'])) {
            $mineralStmt = $pdo->prepare("INSERT INTO food_minerals (food_id, mineral_name, amount, unit) VALUES (?, ?, ?, ?)");
            foreach ($input['minerals'] as $mineral) {
                $mineralStmt->execute([$id, $mineral['name'], $mineral['amount'], $mineral['unit'] ?? 'mg']);
            }
        }
        
        $pdo->commit();
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function handleDeleteFood($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        throw new Exception('DELETE method required');
    }
    
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('Valid ID is required');
    }
    
    $stmt = $pdo->prepare("DELETE FROM foods WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Food not found');
    }
    
    echo json_encode(['success' => true]);
}

function parseNutrients($nutrientString) {
    if (empty($nutrientString)) {
        return [];
    }
    
    $nutrients = [];
    $pairs = explode('|', $nutrientString);
    
    foreach ($pairs as $pair) {
        if (strpos($pair, ':') !== false) {
            list($name, $amountUnit) = explode(':', $pair, 2);
            $nutrients[$name] = $amountUnit;
        }
    }
    
    return $nutrients;
}
?>
