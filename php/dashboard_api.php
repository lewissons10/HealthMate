<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

$userId = $_SESSION['user_id'];

// Get dashboard statistics
if ($action === 'stats') {
    // Get user info
    $stmt = $pdo->prepare("SELECT first_name, last_name, points FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Workouts this week
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM workout_logs 
        WHERE user_id = ? 
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
    ");
    $stmt->execute([$userId]);
    $workoutsThisWeek = (int)$stmt->fetchColumn();
    
    // Calories burned this week
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(calories_burned), 0) as total 
        FROM workout_logs 
        WHERE user_id = ? 
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
    ");
    $stmt->execute([$userId]);
    $caloriesBurned = (int)$stmt->fetchColumn();
    
    // Current streak
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as workout_date 
        FROM workout_logs 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $workoutDates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $currentStreak = 0;
    if (!empty($workoutDates)) {
        $today = new DateTime();
        $currentDate = clone $today;
        
        // Check if there's a workout today
        if (in_array($today->format('Y-m-d'), $workoutDates)) {
            $currentStreak = 1;
            $currentDate->modify('-1 day');
        } else {
            $currentDate->modify('-1 day');
        }
        
        // Count consecutive days backwards
        while (in_array($currentDate->format('Y-m-d'), $workoutDates)) {
            $currentStreak++;
            $currentDate->modify('-1 day');
        }
    }
    
    // Goal progress (simplified - based on weekly workout goal of 5)
    $goalProgress = min(100, ($workoutsThisWeek / 5) * 100);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'user' => $user,
            'workouts_this_week' => $workoutsThisWeek,
            'calories_burned' => $caloriesBurned,
            'current_streak' => $currentStreak,
            'goal_progress' => round($goalProgress)
        ]
    ]);
    exit;
}

// Get progress chart data
if ($action === 'progress_chart') {
    $period = $_GET['period'] ?? 'week';
    
    if ($period === 'week') {
        // Last 7 days
        $stmt = $pdo->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as workouts
            FROM workout_logs 
            WHERE user_id = ? 
            AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stmt->execute([$userId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fill in missing days with 0
        $chartData = [];
        $labels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('M j', strtotime($date));
            $workouts = 0;
            foreach ($data as $row) {
                if ($row['date'] === $date) {
                    $workouts = (int)$row['workouts'];
                    break;
                }
            }
            $chartData[] = $workouts;
        }
    } elseif ($period === 'month') {
        // Last 30 days
        $stmt = $pdo->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as workouts
            FROM workout_logs 
            WHERE user_id = ? 
            AND created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stmt->execute([$userId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fill in missing days with 0
        $chartData = [];
        $labels = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('M j', strtotime($date));
            $workouts = 0;
            foreach ($data as $row) {
                if ($row['date'] === $date) {
                    $workouts = (int)$row['workouts'];
                    break;
                }
            }
            $chartData[] = $workouts;
        }
    } else {
        // Year - monthly data
        $stmt = $pdo->prepare("
            SELECT 
                YEAR(created_at) as year,
                MONTH(created_at) as month,
                COUNT(*) as workouts
            FROM workout_logs 
            WHERE user_id = ? 
            AND created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
            GROUP BY YEAR(created_at), MONTH(created_at)
            ORDER BY year ASC, month ASC
        ");
        $stmt->execute([$userId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $chartData = [];
        $labels = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = date('Y-m-01', strtotime("-$i months"));
            $labels[] = date('M Y', strtotime($date));
            $workouts = 0;
            foreach ($data as $row) {
                if ($row['year'] == date('Y', strtotime($date)) && $row['month'] == date('n', strtotime($date))) {
                    $workouts = (int)$row['workouts'];
                    break;
                }
            }
            $chartData[] = $workouts;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'labels' => $labels,
            'workouts' => $chartData
        ]
    ]);
    exit;
}

// Get calories chart data
if ($action === 'calories_chart') {
    // Last 7 days calories burned
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            SUM(calories_burned) as calories
        FROM workout_logs 
        WHERE user_id = ? 
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$userId]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fill in missing days with 0
    $chartData = [];
    $labels = [];
    $colors = ['#8B5CF6', '#10B981', '#F59E0B', '#EF4444', '#3B82F6', '#8B5CF6', '#EC4899'];
    
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('D', strtotime($date));
        $calories = 0;
        foreach ($data as $row) {
            if ($row['date'] === $date) {
                $calories = (int)$row['calories'];
                break;
            }
        }
        $chartData[] = $calories;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'labels' => $labels,
            'calories' => $chartData,
            'colors' => $colors
        ]
    ]);
    exit;
}

// Get recent activity
if ($action === 'recent_activity') {
    $stmt = $pdo->prepare("
        SELECT 
            workout_name,
            duration_minutes,
            calories_burned,
            created_at
        FROM workout_logs 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formattedActivities = [];
    foreach ($activities as $activity) {
        $formattedActivities[] = [
            'title' => $activity['workout_name'],
            'details' => $activity['duration_minutes'] . ' min • ' . $activity['calories_burned'] . ' cal',
            'ts' => $activity['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formattedActivities
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
