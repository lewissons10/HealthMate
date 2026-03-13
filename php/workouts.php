<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

// Ensure session tables exist (idempotent)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS workout_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        workout_name VARCHAR(255) NOT NULL,
        duration_minutes INT NOT NULL,
        calories_burned INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS daily_calories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        day DATE NOT NULL,
        burned INT NOT NULL DEFAULT 0,
        UNIQUE KEY uniq_user_day (user_id, day)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS workout_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        workout_name VARCHAR(255) NOT NULL,
        status ENUM('in_progress','paused','completed') NOT NULL DEFAULT 'paused',
        elapsed_seconds INT NOT NULL DEFAULT 0,
        target_seconds INT NOT NULL DEFAULT 2700,
        total_calories INT NOT NULL DEFAULT 400,
        started_at DATETIME NULL,
        last_started_at DATETIME NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_workout (user_id, workout_name)
    ) ENGINE=InnoDB");
} catch (Exception $e) {
    // ignore table create errors
}

if ($action === 'cbt_get') {
    $userId = $_SESSION['user_id'];
    $name = 'Complete Body Transformation';
    $stmt = $pdo->prepare("SELECT status, elapsed_seconds, target_seconds, total_calories, last_started_at FROM workout_sessions WHERE user_id=? AND workout_name=?");
    $stmt->execute([$userId, $name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $elapsed = (int)$row['elapsed_seconds'];
        if ($row['status'] === 'in_progress' && !empty($row['last_started_at'])) {
            $delta = time() - strtotime($row['last_started_at']);
            if ($delta > 0) { $elapsed += $delta; }
        }
        $elapsed = min($elapsed, (int)$row['target_seconds']);
        $cal = (int)round(($elapsed / max(1,(int)$row['target_seconds'])) * (int)$row['total_calories']);
        echo json_encode(['success'=>true,'status'=>$row['status'],'elapsed_seconds'=>$elapsed,'target_seconds'=>(int)$row['target_seconds'],'calories_burned'=>$cal,'total_calories'=>(int)$row['total_calories']]);
        exit;
    }
    echo json_encode(['success'=>true,'status'=>'paused','elapsed_seconds'=>0,'target_seconds'=>2700,'calories_burned'=>0,'total_calories'=>400]);
    exit;
}

if ($action === 'cbt_start') {
    $userId = $_SESSION['user_id'];
    $name = 'Complete Body Transformation';
    $target = 2700; $totalCal = 400;
    $stmt = $pdo->prepare("INSERT INTO workout_sessions (user_id, workout_name, status, elapsed_seconds, target_seconds, total_calories, started_at, last_started_at)
        VALUES (?,?,?,?,?,?,NOW(),NOW())
        ON DUPLICATE KEY UPDATE status='in_progress', elapsed_seconds=0, target_seconds=VALUES(target_seconds), total_calories=VALUES(total_calories), started_at=IF(started_at IS NULL, NOW(), started_at), last_started_at=NOW()");
    $stmt->execute([$userId, $name, 'in_progress', 0, $target, $totalCal]);
    echo json_encode(['success'=>true]);
    exit;
}

if ($action === 'cbt_pause') {
    $userId = $_SESSION['user_id'];
    $name = 'Complete Body Transformation';
    // add elapsed delta since last_started_at
    $pdo->beginTransaction();
    $row = $pdo->query("SELECT id, elapsed_seconds, last_started_at, target_seconds FROM workout_sessions WHERE user_id=".$pdo->quote($userId)." AND workout_name=".$pdo->quote($name)." FOR UPDATE")->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $elapsed = (int)$row['elapsed_seconds'];
        if (!empty($row['last_started_at'])) {
            $delta = time() - strtotime($row['last_started_at']);
            if ($delta > 0) { $elapsed += $delta; }
        }
        $elapsed = min($elapsed, (int)$row['target_seconds']);
        $stmt = $pdo->prepare("UPDATE workout_sessions SET status='paused', elapsed_seconds=?, last_started_at=NULL WHERE id=?");
        $stmt->execute([$elapsed, (int)$row['id']]);
        $pdo->commit();
        echo json_encode(['success'=>true]);
    } else { $pdo->rollBack(); echo json_encode(['success'=>false]); }
    exit;
}

if ($action === 'cbt_resume') {
    $userId = $_SESSION['user_id'];
    $name = 'Complete Body Transformation';
    $stmt = $pdo->prepare("UPDATE workout_sessions SET status='in_progress', last_started_at=NOW() WHERE user_id=? AND workout_name=?");
    $stmt->execute([$userId, $name]);
    echo json_encode(['success'=>true]);
    exit;
}

if ($action === 'cbt_end') {
    $userId = $_SESSION['user_id'];
    $name = 'Complete Body Transformation';
    $pdo->beginTransaction();
    $row = $pdo->query("SELECT id, elapsed_seconds, last_started_at, target_seconds, total_calories FROM workout_sessions WHERE user_id=".$pdo->quote($userId)." AND workout_name=".$pdo->quote($name)." FOR UPDATE")->fetch(PDO::FETCH_ASSOC);
    $elapsed = 0; $target=2700; $totalCal=400;
    if ($row) {
        $elapsed = (int)$row['elapsed_seconds'];
        $target = (int)$row['target_seconds'];
        $totalCal = (int)$row['total_calories'];
        if (!empty($row['last_started_at'])) {
            $delta = time() - strtotime($row['last_started_at']);
            if ($delta > 0) { $elapsed += $delta; }
        }
        $elapsed = min($elapsed, $target);
        $stmt = $pdo->prepare("UPDATE workout_sessions SET status='completed', elapsed_seconds=?, last_started_at=NULL WHERE id=?");
        $stmt->execute([$elapsed, (int)$row['id']]);
    }
    $pdo->commit();

    $durationMinutes = (int)round($elapsed/60);
    $caloriesBurned = (int)round(($elapsed / max(1,$target)) * $totalCal);

    // Log workout and update daily calories
    try {
        $stmt = $pdo->prepare("INSERT INTO workout_logs (user_id, workout_name, duration_minutes, calories_burned) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $name, $durationMinutes, $caloriesBurned]);
    } catch (Exception $e) { /* ignore duplicate */ }

    $today = date('Y-m-d');
    $stmt = $pdo->prepare("INSERT INTO daily_calories (user_id, day, burned) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE burned = burned + VALUES(burned)");
    $stmt->execute([$userId, $today, $caloriesBurned]);

    $stmt = $pdo->prepare("SELECT burned FROM daily_calories WHERE user_id = ? AND day = ?");
    $stmt->execute([$userId, $today]);
    $todayTotal = (int)($stmt->fetchColumn() ?: 0);

    echo json_encode(['success'=>true,'message'=>'Workout completed','today_total_calories'=>$todayTotal,'calories_burned'=>$caloriesBurned,'duration_minutes'=>$durationMinutes]);
    exit;
}

if ($action === 'get_workout_count') {
    $userId = $_SESSION['user_id'];
    // Count only workouts completed today for this user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM workout_logs WHERE user_id = ? AND DATE(created_at) = CURDATE()");
    $stmt->execute([$userId]);
    $count = (int)$stmt->fetchColumn();
    echo json_encode(['success' => true, 'count' => $count]);
    exit;
}

if ($action === 'log_workout') {
    $userId = $_SESSION['user_id'];
    $workoutName = sanitizeInput($_POST['workout_name'] ?? 'Workout');
    $durationMinutes = max(0, intval($_POST['duration_minutes'] ?? 0));
    $caloriesBurned = max(0, intval($_POST['calories_burned'] ?? 0));

    try {
        // Log the workout
        $stmt = $pdo->prepare("INSERT INTO workout_logs (user_id, workout_name, duration_minutes, calories_burned) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $workoutName, $durationMinutes, $caloriesBurned]);

        // Update daily calories
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("INSERT INTO daily_calories (user_id, day, burned) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE burned = burned + VALUES(burned)");
        $stmt->execute([$userId, $today, $caloriesBurned]);

        // Award points using the points system
        require_once 'points_system.php';
        $pointsSystem = new PointsSystem($userId);
        $pointsEarned = $pointsSystem->awardWorkoutPoints($workoutName, $durationMinutes, $caloriesBurned);
        
        // Check for new achievements
        $achievements = $pointsSystem->checkStreakAchievements();
        foreach ($achievements as $achievement) {
            $pointsSystem->awardAchievementPoints($achievement['name'], $achievement['points']);
        }

        // Fetch updated totals
        $stmt = $pdo->prepare("SELECT burned FROM daily_calories WHERE user_id = ? AND day = ?");
        $stmt->execute([$userId, $today]);
        $todayTotal = (int)($stmt->fetchColumn() ?: 0);
        
        $totalPoints = $pointsSystem->getTotalPoints();

        echo json_encode([
            'success' => true,
            'message' => 'Workout logged successfully!',
            'points_earned' => $pointsEarned,
            'total_points' => $totalPoints,
            'today_total_calories' => $todayTotal,
            'achievements' => $achievements
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to log workout: ' . $e->getMessage()]);
        exit;
    }
}

// Aggregate achievement stats and badge unlocks
if ($action === 'achievement_stats') {
    $userId = $_SESSION['user_id'];

    // Basic totals
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total, MIN(created_at) AS first_ts FROM workout_logs WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'first_ts' => null];
    $totalWorkouts = (int)($row['total'] ?? 0);
    $firstTs = $row['first_ts'] ? date('Y-m-d', strtotime($row['first_ts'])) : null;

    // Per-day presence for streaks
    $stmt = $pdo->prepare("SELECT DATE(created_at) AS day, COUNT(*) cnt FROM workout_logs WHERE user_id = ? GROUP BY DATE(created_at) ORDER BY day ASC");
    $stmt->execute([$userId]);
    $days = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $currentStreak = 0; $longestStreak = 0;
    if (!empty($days)) {
        $dateSet = [];
        foreach ($days as $d) { $dateSet[$d['day']] = true; }
        $today = new DateTime('today');
        $cursor = clone $today;
        // If no workout today, start from yesterday
        if (!isset($dateSet[$today->format('Y-m-d')])) {
            $cursor->modify('-1 day');
        }
        // Walk backwards while consecutive
        while (isset($dateSet[$cursor->format('Y-m-d')])) {
            $currentStreak++;
            $cursor->modify('-1 day');
        }

        // Longest streak: iterate sorted days
        $prev = null; $tmp = 0;
        foreach ($dateSet as $dayStr => $_v) {
            // ensure chronological
        }
        $dayKeys = array_keys($dateSet);
        sort($dayKeys);
        foreach ($dayKeys as $dayStr) {
            if ($prev === null) {
                $tmp = 1; $longestStreak = max($longestStreak, $tmp); $prev = $dayStr; continue;
            }
            $prevDate = new DateTime($prev);
            $thisDate = new DateTime($dayStr);
            $prevDate->modify('+1 day');
            if ($prevDate->format('Y-m-d') === $thisDate->format('Y-m-d')) {
                $tmp++;
            } else {
                $tmp = 1;
            }
            $longestStreak = max($longestStreak, $tmp);
            $prev = $dayStr;
        }
    }

    // Time-of-day and weekend stats
    $stmt = $pdo->prepare("SELECT created_at FROM workout_logs WHERE user_id = ? ORDER BY created_at ASC");
    $stmt->execute([$userId]);
    $timestamps = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $morningCount = 0; $nightCount = 0; $weekendCount = 0;
    foreach ($timestamps as $ts) {
        $dt = new DateTime($ts);
        $hour = (int)$dt->format('H');
        // Morning Hustler: before 7 AM
        if ($hour < 7) { $morningCount++; }
        // Night Owl: 21:00 or later
        if ($hour >= 21) { $nightCount++; }
        // Weekend: Sat(6) or Sun(0)
        $dow = (int)$dt->format('w');
        if ($dow === 0 || $dow === 6) { $weekendCount++; }
    }

    // Category-based: naive keyword mapping from workout_name
    $stmt = $pdo->prepare("SELECT workout_name FROM workout_logs WHERE user_id = ?");
    $stmt->execute([$userId]);
    $names = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $counts = [
        'cardio' => 0,
        'strength' => 0,
        'core' => 0,
        'legs' => 0,
        'fullbody' => 0,
    ];
    foreach ($names as $name) {
        $n = strtolower($name);
        if (preg_match('/cardio|run|treadmill|endurance|hiit|bike|cycling|row/', $n)) { $counts['cardio']++; }
        if (preg_match('/strength|weight|bench|press|deadlift|squat|curl|dumbbell|barbell/', $n)) { $counts['strength']++; }
        if (preg_match('/core|ab|abs|plank|crunch|sit[- ]?up|hollow/', $n)) { $counts['core']++; }
        if (preg_match('/leg|squat|lunge|calf|hamstring|quad|glute/', $n)) { $counts['legs']++; }
        if (preg_match('/full[- ]?body|total|complete body/', $n)) { $counts['fullbody']++; }
    }

    // Comeback Kid: any gap >= 30 days between consecutive workout days
    $comeback = false;
    if (!empty($days)) {
        $prev = null;
        foreach ($days as $d) {
            $cur = new DateTime($d['day']);
            if ($prev) {
                $gap = $prev->diff($cur)->days;
                if ($gap >= 30) { $comeback = true; break; }
            }
            $prev = $cur;
        }
    }

    // Build badge unlocks - keeping only relevant and logical ones
    $badges = [];
    
    // Workout completion badges (simplified milestones)
    $milestones = [
        1 => 'Starter', 
        5 => 'Consistency Rookie', 
        10 => 'Sweat Beginner', 
        25 => 'Fitness Explorer', 
        50 => 'Dedicated Beast', 
        100 => 'Century Crusher'
    ];
    foreach ($milestones as $threshold => $title) {
        $badges[] = ['group' => 'workouts', 'title' => $title, 'threshold' => $threshold, 'progress' => $totalWorkouts, 'earned' => $totalWorkouts >= $threshold];
    }

    // Streak-based (realistic streaks)
    $streaks = [
        3 => '3-Day Warrior', 
        7 => 'Weekly Grinder', 
        14 => 'Two Week Champion', 
        30 => 'Monthly Machine'
    ];
    foreach ($streaks as $threshold => $title) {
        $badges[] = ['group' => 'streaks', 'title' => $title, 'threshold' => $threshold, 'progress' => $longestStreak, 'earned' => $longestStreak >= $threshold];
    }

    // Category-based (simplified categories)
    $badges[] = ['group' => 'category', 'title' => 'Cardio King/Queen', 'threshold' => 20, 'progress' => $counts['cardio'], 'earned' => $counts['cardio'] >= 20];
    $badges[] = ['group' => 'category', 'title' => 'Strength Seeker', 'threshold' => 20, 'progress' => $counts['strength'], 'earned' => $counts['strength'] >= 20];
    $badges[] = ['group' => 'category', 'title' => 'Core Crusher', 'threshold' => 10, 'progress' => $counts['core'], 'earned' => $counts['core'] >= 10];

    // Special milestones (achievable and meaningful)
    $badges[] = ['group' => 'special', 'title' => 'First Blood', 'threshold' => 1, 'progress' => $totalWorkouts, 'earned' => $totalWorkouts >= 1];
    $badges[] = ['group' => 'special', 'title' => 'Comeback Kid', 'threshold' => 1, 'progress' => $comeback ? 1 : 0, 'earned' => $comeback];
    $badges[] = ['group' => 'special', 'title' => 'Morning Hustler', 'threshold' => 10, 'progress' => $morningCount, 'earned' => $morningCount >= 10];
    $badges[] = ['group' => 'special', 'title' => 'Weekend Warrior', 'threshold' => 10, 'progress' => $weekendCount, 'earned' => $weekendCount >= 10];

    echo json_encode([
        'success' => true,
        'data' => [
            'totals' => [
                'workouts' => $totalWorkouts,
                'current_streak' => $currentStreak,
                'longest_streak' => $longestStreak,
                'first_workout_date' => $firstTs,
                'morning_count' => $morningCount,
                'night_count' => $nightCount,
                'weekend_count' => $weekendCount,
                'category_counts' => $counts,
            ],
            'badges' => $badges,
        ]
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>


