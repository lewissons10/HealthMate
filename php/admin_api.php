<?php
require_once 'admin_config.php';
requireAnsonAccess(); // Restrict access to Anson only

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    $pdo = getAdminDBConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    switch ($action) {
        case 'get_stats':
            getStats($pdo);
            break;
        case 'get_users':
            getUsers($pdo);
            break;
        case 'get_recent_activity':
            getRecentActivity($pdo);
            break;
        case 'get_user_details':
            getUserDetails($pdo);
            break;
        case 'get_user_meals':
            getUserMeals($pdo);
            break;
        case 'get_user_progress':
            getUserProgress($pdo);
            break;
        case 'get_detailed_stats':
            getDetailedStats($pdo);
            break;
            case 'export_data':
                exportData($pdo);
                break;
            case 'bulk_delete_users':
                bulkDeleteUsers($pdo);
                break;
            case 'add_user':
                addUser($pdo);
                break;
            case 'update_user':
                updateUser($pdo);
                break;
            case 'delete_user':
                deleteUser($pdo);
                break;
            default:
                throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function getStats($pdo) {
	try {
		// Compute live stats directly from tables
		$totalUsers = 0;
		$totalMeals = 0;
		$totalCalories = 0;
		$activeUsers = 0;

		try {
			$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
		} catch (Exception $e) {}

		try {
			$totalMeals = (int)$pdo->query("SELECT COUNT(*) FROM daily_meal_history")->fetchColumn();
			$totalCalories = (int)$pdo->query("SELECT COALESCE(SUM(calories), 0) FROM daily_meal_history")->fetchColumn();
		} catch (Exception $e) {}

		try {
			$activeUsers = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM user_sessions WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND is_active = 1")->fetchColumn();
		} catch (Exception $e) {}

		echo json_encode([
			'success' => true,
			'stats' => [
				'total_users' => $totalUsers,
				'total_meals' => $totalMeals,
				'total_calories' => $totalCalories,
				'active_users' => $activeUsers
			]
		]);
	} catch (Exception $e) {
		echo json_encode([
			'success' => false,
			'error' => $e->getMessage()
		]);
	}
}

function getUsers($pdo) {
    try {
        // Base user rows (avoid risky subqueries); if table missing, return empty
        try {
            $stmt = $pdo->query("\n                SELECT \n                    id, username, email, first_name, last_name, age, weight, height, phone, date_of_birth, gender, bio,\n                    target_weight, workouts_per_week, experience_level, level, fitness_goal, points, created_at, updated_at\n                FROM users\n                ORDER BY created_at DESC\n            ");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo json_encode(['success' => true, 'users' => []]);
            return;
        }

        // Optional sessions
        $userIdToSession = [];
        try {
            $stmt = $pdo->query("SELECT user_id, last_activity, ip_address FROM user_sessions WHERE is_active = 1");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $userIdToSession[$row['user_id']] = $row;
            }
        } catch (Exception $e) {}

        foreach ($users as &$user) {
            $userId = (int)$user['id'];

            $lastLogin = $userIdToSession[$userId]['last_activity'] ?? null;
            $user['last_login'] = $lastLogin ? date('Y-m-d H:i:s', strtotime($lastLogin)) : 'Never';
            $user['ip_address'] = $userIdToSession[$userId]['ip_address'] ?? null;

            // Totals from daily_meal_history
            $totalMeals = 0; $totalCalories = 0;
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM daily_meal_history WHERE user_id = ?");
                $stmt->execute([$userId]);
                $totalMeals = (int)$stmt->fetchColumn();

                $stmt = $pdo->prepare("SELECT COALESCE(SUM(calories), 0) FROM daily_meal_history WHERE user_id = ?");
                $stmt->execute([$userId]);
                $totalCalories = (int)$stmt->fetchColumn();
            } catch (Exception $e) {}

            // Activities
            $totalActivities = 0;
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_activity_logs WHERE user_id = ?");
                $stmt->execute([$userId]);
                $totalActivities = (int)$stmt->fetchColumn();
            } catch (Exception $e) {}

            // Derived
            $user['status'] = $lastLogin
                ? (strtotime($lastLogin) > strtotime('-7 days') ? 'active' : (strtotime($lastLogin) > strtotime('-30 days') ? 'recent' : 'inactive'))
                : 'inactive';
            $user['full_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            $user['bmi'] = ($user['weight'] && $user['height']) ? round($user['weight'] / pow($user['height']/100, 2), 1) : null;
            $user['age_display'] = $user['age'] ? $user['age'] . ' years' : 'Not specified';
            $user['weight_display'] = $user['weight'] ? $user['weight'] . ' kg' : 'Not specified';
            $user['height_display'] = $user['height'] ? $user['height'] . ' cm' : 'Not specified';
            $user['target_weight_display'] = $user['target_weight'] ? $user['target_weight'] . ' kg' : 'Not set';
            $user['total_meals'] = $totalMeals;
            $user['total_calories'] = $totalCalories;
            $user['total_activities'] = $totalActivities;
        }

        echo json_encode([
            'success' => true,
            'users' => $users
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function getRecentActivity($pdo) {
	try {
		// Pull recent activity from real tables (workouts and meals)
		$rows = [];

		try {
			$stmt = $pdo->query("
				SELECT u.username,
				       CONCAT('Workout: ', COALESCE(ual.activity_type, 'Activity')) AS activity,
				       ual.created_at AS ts
				FROM user_activity_logs ual
				JOIN users u ON u.id = ual.user_id
				ORDER BY ual.created_at DESC
				LIMIT 20
			");
			$rows = array_merge($rows, $stmt->fetchAll(PDO::FETCH_ASSOC));
		} catch (Exception $e) {}

		try {
			$stmt = $pdo->query("
				SELECT u.username,
				       CONCAT('Logged meal: ', COALESCE(dmh.meal_type, ''), CASE WHEN dmh.food_name IS NOT NULL AND dmh.food_name <> '' THEN CONCAT(' - ', dmh.food_name) ELSE '' END) AS activity,
				       dmh.created_at AS ts
				FROM daily_meal_history dmh
				JOIN users u ON u.id = dmh.user_id
				ORDER BY dmh.created_at DESC
				LIMIT 20
			");
			$rows = array_merge($rows, $stmt->fetchAll(PDO::FETCH_ASSOC));
		} catch (Exception $e) {}

		// Sort combined and take latest 20
		usort($rows, function($a, $b) {
			return strtotime($b['ts']) <=> strtotime($a['ts']);
		});
		$rows = array_slice($rows, 0, 20);

		$activities = array_map(function($r) {
			$timeStr = date('Y-m-d H:i:s', strtotime($r['ts']));
			$status = (strtotime($r['ts']) >= strtotime('-7 days')) ? 'active' : ((strtotime($r['ts']) >= strtotime('-30 days')) ? 'recent' : 'inactive');
			return [
				'username' => $r['username'] ?? 'Unknown',
				'activity' => $r['activity'] ?? 'Activity',
				'time' => $timeStr,
				'status' => $status,
			];
		}, $rows);

		echo json_encode([
			'success' => true,
			'activities' => $activities
		]);
	} catch (Exception $e) {
		echo json_encode([
			'success' => false,
			'error' => $e->getMessage()
		]);
	}
}

function getUserDetails($pdo) {
    $userId = $_GET['user_id'] ?? '';
    
    if (!$userId) {
        throw new Exception('User ID required');
    }
    
    try {
        // Get base user only (no joins to optional tables)
        $stmt = $pdo->prepare("\n            SELECT \n                u.*\n            FROM users u\n            WHERE u.id = ?\n        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception('User not found');
        }
        
        // Try to enrich with session info if table exists
        $lastLogin = null; $ipAddress = null;
        try {
            $stmt = $pdo->prepare("SELECT last_activity, ip_address FROM user_sessions WHERE user_id = ? AND is_active = 1 ORDER BY last_activity DESC LIMIT 1");
            $stmt->execute([$userId]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $lastLogin = $row['last_activity'];
                $ipAddress = $row['ip_address'];
            }
        } catch (Exception $e) {}
        $user['last_login'] = $lastLogin ? date('Y-m-d H:i:s', strtotime($lastLogin)) : 'Never';
        $user['ip_address'] = $ipAddress;
        
        // Defensive totals
        $totalMeals = 0; $totalCalories = 0; $totalActivities = 0;
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM daily_meal_history WHERE user_id = ?");
            $stmt->execute([$userId]);
            $totalMeals = (int)$stmt->fetchColumn();
        } catch (Exception $e) {}
        try {
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(calories), 0) FROM daily_meal_history WHERE user_id = ?");
            $stmt->execute([$userId]);
            $totalCalories = (int)$stmt->fetchColumn();
        } catch (Exception $e) {}
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_activity_logs WHERE user_id = ?");
            $stmt->execute([$userId]);
            $totalActivities = (int)$stmt->fetchColumn();
        } catch (Exception $e) {}

        $user['total_meals'] = $totalMeals;
        $user['total_calories'] = $totalCalories;
        $user['total_activities'] = $totalActivities;

        // Derived fields
        $user['bmi'] = $user['weight'] && $user['height'] ? round($user['weight'] / pow($user['height']/100, 2), 1) : null;
        $user['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
        
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function getUserMeals($pdo) {
    $userId = $_GET['user_id'] ?? '';
    $limit = $_GET['limit'] ?? 50;
    
    if (!$userId) {
        throw new Exception('User ID required');
    }
    
    try {
        // Ensure limit is a safe integer to avoid SQL quoting issues
        $limit = (int)$limit;
        if ($limit <= 0) { $limit = 50; }
        if ($limit > 500) { $limit = 500; }
        
        $sql = "\n            SELECT \n                dmh.*,\n                DATE(dmh.date) as meal_date,\n                TIME(dmh.created_at) as meal_time\n            FROM daily_meal_history dmh\n            WHERE dmh.user_id = ?\n            ORDER BY dmh.date DESC, dmh.created_at DESC\n            LIMIT $limit\n        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $meals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'meals' => $meals
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function getUserProgress($pdo) {
    $userId = $_GET['user_id'] ?? '';
    $days = $_GET['days'] ?? 30;
    
    if (!$userId) {
        throw new Exception('User ID required');
    }
    
    try {
        // Primary nutrition source: daily_nutrition_totals
        $nutrition = [];
        try {
            $stmt = $pdo->prepare("\n                SELECT \n                    date,\n                    total_calories,\n                    total_protein,\n                    total_carbs,\n                    total_fats\n                FROM daily_nutrition_totals\n                WHERE user_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)\n                ORDER BY date DESC\n            ");
            $stmt->execute([$userId, $days]);
            $nutrition = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        // Fallback nutrition: aggregate from daily_meal_history
        if (empty($nutrition)) {
            // Try with macros if columns exist
            try {
                $stmt = $pdo->prepare("\n                    SELECT \n                        DATE(dmh.date) AS date,\n                        COALESCE(SUM(dmh.calories), 0) AS total_calories,\n                        COALESCE(SUM(dmh.protein), 0) AS total_protein,\n                        COALESCE(SUM(dmh.carbs), 0) AS total_carbs,\n                        COALESCE(SUM(dmh.fats), 0) AS total_fats\n                    FROM daily_meal_history dmh\n                    WHERE dmh.user_id = ? AND dmh.date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)\n                    GROUP BY DATE(dmh.date)\n                    ORDER BY date DESC\n                ");
                $stmt->execute([$userId, $days]);
                $nutrition = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // Minimal fallback: calories only
                try {
                    $stmt = $pdo->prepare("\n                        SELECT \n                            DATE(dmh.date) AS date,\n                            COALESCE(SUM(dmh.calories), 0) AS total_calories\n                        FROM daily_meal_history dmh\n                        WHERE dmh.user_id = ? AND dmh.date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)\n                        GROUP BY DATE(dmh.date)\n                        ORDER BY date DESC\n                    ");
                    $stmt->execute([$userId, $days]);
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($rows as $r) {
                        $nutrition[] = [
                            'date' => $r['date'],
                            'total_calories' => (int)($r['total_calories'] ?? 0),
                            'total_protein' => null,
                            'total_carbs' => null,
                            'total_fats' => null,
                        ];
                    }
                } catch (Exception $e2) {}
            }
        }

        // Primary progress source: user_progress
        $progress = [];
        try {
            $stmt = $pdo->prepare("\n                SELECT \n                    date,\n                    weight,\n                    calories_consumed,\n                    calories_burned,\n                    workout_duration_minutes\n                FROM user_progress\n                WHERE user_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)\n                ORDER BY date DESC\n            ");
            $stmt->execute([$userId, $days]);
            $progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        // Fallback progress: aggregate from workout_logs and combine with nutrition calories
        if (empty($progress)) {
            try {
                $stmt = $pdo->prepare("\n                    SELECT \n                        DATE(created_at) AS date,\n                        COALESCE(SUM(calories_burned), 0) AS calories_burned,\n                        COALESCE(SUM(duration_minutes), 0) AS workout_duration_minutes\n                    FROM workout_logs\n                    WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)\n                    GROUP BY DATE(created_at)\n                    ORDER BY date DESC\n                ");
                $stmt->execute([$userId, $days]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $calsByDate = [];
                foreach ($nutrition as $n) {
                    if (isset($n['date'])) {
                        $calsByDate[$n['date']] = $n['total_calories'] ?? 0;
                    }
                }

                foreach ($rows as $r) {
                    $date = $r['date'];
                    $progress[] = [
                        'date' => $date,
                        'weight' => null,
                        'calories_consumed' => isset($calsByDate[$date]) ? (int)$calsByDate[$date] : null,
                        'calories_burned' => (int)($r['calories_burned'] ?? 0),
                        'workout_duration_minutes' => (int)($r['workout_duration_minutes'] ?? 0),
                    ];
                }
            } catch (Exception $e) {}
        }
        
        echo json_encode([
            'success' => true,
            'nutrition' => $nutrition,
            'progress' => $progress
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function getDetailedStats($pdo) {
    try {
        // Get comprehensive system statistics
        $stats = [];
        
        // User statistics
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_users,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_users_week,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_users_month,
                AVG(age) as avg_age,
                AVG(weight) as avg_weight,
                AVG(height) as avg_height
            FROM users
        ");
        $userStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['users'] = $userStats;
        
        // Meal statistics
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_meals,
                COUNT(CASE WHEN date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as meals_week,
                COUNT(CASE WHEN date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as meals_month,
                AVG(calories) as avg_calories_per_meal,
                SUM(calories) as total_calories
            FROM daily_meal_history
        ");
        $mealStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['meals'] = $mealStats;
        
        // Activity statistics
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_activities,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as activities_week,
                activity_type,
                COUNT(*) as count
            FROM user_activity_logs
            GROUP BY activity_type
            ORDER BY count DESC
        ");
        $activityStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stats['activities'] = $activityStats;
        
        // Gender distribution
        $stmt = $pdo->query("
            SELECT 
                gender,
                COUNT(*) as count
            FROM users
            WHERE gender IS NOT NULL
            GROUP BY gender
        ");
        $genderStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stats['gender_distribution'] = $genderStats;
        
        // Fitness goal distribution
        $stmt = $pdo->query("
            SELECT 
                fitness_goal,
                COUNT(*) as count
            FROM users
            WHERE fitness_goal IS NOT NULL
            GROUP BY fitness_goal
        ");
        $goalStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stats['fitness_goals'] = $goalStats;
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function exportData($pdo) {
    try {
        // Export user data to CSV or JSON
        $users = getUsers($pdo);
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="healthmate_users_export.json"');
        
        echo json_encode($users);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

// Helper functions
function getTotalUsers($pdo) {
    // Count users from localStorage files or users table
    $localStorageDir = 'localStorage/';
    if (is_dir($localStorageDir)) {
        $files = glob($localStorageDir . '*.json');
        return count($files);
    }
    return 2; // Default demo count
}

function getTotalMeals($pdo) {
    // Count total meals from meal history
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM daily_meal_history");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}

function getTotalCalories($pdo) {
    // Sum total calories from meal history
    try {
        $stmt = $pdo->query("SELECT SUM(calories) as total FROM daily_meal_history");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}

function getActiveUsers($pdo) {
    // Count users with recent activity
    try {
        $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as count FROM user_sessions WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND is_active = 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        return 1; // Default demo count
    }
}

function updateSystemStats($pdo) {
    try {
        // Update total users
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $totalUsers = $stmt->fetchColumn();
        $pdo->prepare("UPDATE system_stats SET stat_value = ? WHERE stat_name = 'total_users'")->execute([$totalUsers]);
        
        // Update total meals
        $stmt = $pdo->query("SELECT COUNT(*) FROM daily_meal_history");
        $totalMeals = $stmt->fetchColumn();
        $pdo->prepare("UPDATE system_stats SET stat_value = ? WHERE stat_name = 'total_meals'")->execute([$totalMeals]);
        
        // Update total calories
        $stmt = $pdo->query("SELECT COALESCE(SUM(calories), 0) FROM daily_meal_history");
        $totalCalories = $stmt->fetchColumn();
        $pdo->prepare("UPDATE system_stats SET stat_value = ? WHERE stat_name = 'total_calories'")->execute([$totalCalories]);
        
        // Update active users
        $activeUsers = getActiveUsers($pdo);
        $pdo->prepare("UPDATE system_stats SET stat_value = ? WHERE stat_name = 'active_users'")->execute([$activeUsers]);
        
    } catch (Exception $e) {
        // If tables don't exist, use default values
        $defaults = [
            'total_users' => 2,
            'total_meals' => 0,
            'total_calories' => 0,
            'active_users' => 1
        ];
        
        foreach ($defaults as $stat => $value) {
            try {
                $pdo->prepare("UPDATE system_stats SET stat_value = ? WHERE stat_name = ?")->execute([$value, $stat]);
            } catch (Exception $e2) {
                // Ignore if table doesn't exist
            }
        }
    }
}

function addUser($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['username', 'email', 'password', 'first_name', 'last_name'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$input['username'], $input['email']]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception('Username or email already exists');
        }
        
        // Hash password
        $password_hash = password_hash($input['password'], PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users (
                username, email, password_hash, first_name, last_name, 
                age, weight, height, phone, date_of_birth, gender, bio,
                target_weight, workouts_per_week, experience_level, 
                level, fitness_goal, points
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['username'],
            $input['email'],
            $password_hash,
            $input['first_name'],
            $input['last_name'],
            $input['age'] ?? null,
            $input['weight'] ?? null,
            $input['height'] ?? null,
            $input['phone'] ?? null,
            $input['date_of_birth'] ?? null,
            $input['gender'] ?? null,
            $input['bio'] ?? null,
            $input['target_weight'] ?? null,
            $input['workouts_per_week'] ?? null,
            $input['experience_level'] ?? 'beginner',
            $input['level'] ?? 1,
            $input['fitness_goal'] ?? 'general_fitness',
            $input['points'] ?? 0
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'User added successfully',
            'user_id' => $pdo->lastInsertId()
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function updateUser($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['user_id'] ?? null;
        
        if (!$userId) {
            throw new Exception('User ID is required');
        }
        
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        if ($stmt->rowCount() == 0) {
            throw new Exception('User not found');
        }
        
        // Build update query dynamically
        $updateFields = [];
        $values = [];
        
        $allowedFields = [
            'first_name', 'last_name', 'email', 'age', 'weight', 'height',
            'phone', 'date_of_birth', 'gender', 'bio', 'target_weight',
            'workouts_per_week', 'experience_level', 'level', 'fitness_goal', 'points'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                $values[] = $input[$field];
            }
        }
        
        if (empty($updateFields)) {
            throw new Exception('No fields to update');
        }
        
        $values[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        
        echo json_encode([
            'success' => true,
            'message' => 'User updated successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function deleteUser($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['user_id'] ?? null;
        
        if (!$userId) {
            throw new Exception('User ID is required');
        }
        
        // Check if user exists
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception('User not found');
        }
        
        // Delete user (cascade will handle related records)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        echo json_encode([
            'success' => true,
            'message' => "User '{$user['username']}' deleted successfully"
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function bulkDeleteUsers($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $userIds = $input['user_ids'] ?? [];
        if (!is_array($userIds) || count($userIds) === 0) {
            throw new Exception('No user IDs provided');
        }

        // Ensure all are integers
        $ids = array_map('intval', $userIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // Optionally fetch usernames for message
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $usernames = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Delete users
        $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)");
        $stmt->execute($ids);

        echo json_encode([
            'success' => true,
            'message' => 'Deleted ' . count($ids) . ' users',
            'usernames' => $usernames
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>
