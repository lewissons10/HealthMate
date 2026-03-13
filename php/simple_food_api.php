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
            handleSimpleFoodSearch($pdo);
            break;
        case 'get_all':
            handleSimpleGetAllFoods($pdo);
            break;
        case 'get_by_category':
            handleSimpleGetByCategory($pdo);
            break;
        case 'get_by_id':
            handleSimpleGetById($pdo);
            break;
        case 'get_high_protein':
            handleSimpleGetHighProtein($pdo);
            break;
        case 'get_by_calorie_range':
            handleSimpleGetByCalorieRange($pdo);
            break;
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleSimpleFoodSearch($pdo) {
    $query = $_GET['q'] ?? '';
    $limit = intval($_GET['limit'] ?? 20);
    
    if (strlen($query) < 2) {
        throw new Exception('Query must be at least 2 characters long');
    }
    
    $searchTerm = '%' . $query . '%';
    $stmt = $pdo->prepare("
        SELECT f.*
        FROM foods f
        WHERE f.name LIKE ? OR f.food_key LIKE ?
        ORDER BY f.name
        LIMIT ?
    ");
    
    $stmt->execute([$searchTerm, $searchTerm, $limit]);
    $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add empty vitamins and minerals for now
    foreach ($foods as &$food) {
        $food['vitamins'] = [];
        $food['minerals'] = [];
    }
    
    echo json_encode(['foods' => $foods]);
}

function handleSimpleGetAllFoods($pdo) {
    $limit = intval($_GET['limit'] ?? 100);
    $offset = intval($_GET['offset'] ?? 0);
    
    $stmt = $pdo->prepare("
        SELECT f.*
        FROM foods f
        ORDER BY f.name
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([$limit, $offset]);
    $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add empty vitamins and minerals for now
    foreach ($foods as &$food) {
        $food['vitamins'] = [];
        $food['minerals'] = [];
    }
    
    echo json_encode(['foods' => $foods]);
}

function handleSimpleGetByCategory($pdo) {
    $category = $_GET['category'] ?? '';
    
    if (empty($category)) {
        throw new Exception('Category parameter is required');
    }
    
    $stmt = $pdo->prepare("
        SELECT f.*
        FROM foods f
        WHERE f.category = ?
        ORDER BY f.name
    ");
    
    $stmt->execute([$category]);
    $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add empty vitamins and minerals for now
    foreach ($foods as &$food) {
        $food['vitamins'] = [];
        $food['minerals'] = [];
    }
    
    echo json_encode(['foods' => $foods]);
}

function handleSimpleGetById($pdo) {
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        throw new Exception('Valid ID is required');
    }
    
    $stmt = $pdo->prepare("
        SELECT f.*
        FROM foods f
        WHERE f.id = ?
    ");
    
    $stmt->execute([$id]);
    $food = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$food) {
        throw new Exception('Food not found');
    }
    
    // Add empty vitamins and minerals for now
    $food['vitamins'] = [];
    $food['minerals'] = [];
    
    echo json_encode(['food' => $food]);
}

function handleSimpleGetHighProtein($pdo) {
    $minProtein = floatval($_GET['min_protein'] ?? 20);
    
    $stmt = $pdo->prepare("
        SELECT f.*
        FROM foods f
        WHERE f.protein >= ?
        ORDER BY f.protein DESC
    ");
    
    $stmt->execute([$minProtein]);
    $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add empty vitamins and minerals for now
    foreach ($foods as &$food) {
        $food['vitamins'] = [];
        $food['minerals'] = [];
    }
    
    echo json_encode(['foods' => $foods]);
}

function handleSimpleGetByCalorieRange($pdo) {
    $minCalories = floatval($_GET['min_calories'] ?? 0);
    $maxCalories = floatval($_GET['max_calories'] ?? 1000);
    
    $stmt = $pdo->prepare("
        SELECT f.*
        FROM foods f
        WHERE f.calories_per_100g BETWEEN ? AND ?
        ORDER BY f.calories_per_100g
    ");
    
    $stmt->execute([$minCalories, $maxCalories]);
    $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add empty vitamins and minerals for now
    foreach ($foods as &$food) {
        $food['vitamins'] = [];
        $food['minerals'] = [];
    }
    
    echo json_encode(['foods' => $foods]);
}
?>
