<?php
require_once '../php/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../index.html');
}

// Get user data
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get dashboard statistics
$userId = $_SESSION['user_id'];

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

// Enhanced progress calculation
$weeklyGoal = 5; // Target workouts per week
$goalProgress = min(100, ($workoutsThisWeek / $weeklyGoal) * 100);

// Get additional progress metrics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_workouts,
        SUM(calories_burned) as total_calories,
        SUM(duration_minutes) as total_duration,
        AVG(calories_burned) as avg_calories_per_workout,
        AVG(duration_minutes) as avg_duration_per_workout
    FROM workout_logs 
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$progressMetrics = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate weekly averages
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as weekly_workouts,
        SUM(calories_burned) as weekly_calories,
        SUM(duration_minutes) as weekly_duration
    FROM workout_logs 
    WHERE user_id = ? 
    AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
");
$stmt->execute([$userId]);
$weeklyMetrics = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate monthly progress
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as monthly_workouts,
        SUM(calories_burned) as monthly_calories
    FROM workout_logs 
    WHERE user_id = ? 
    AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$stmt->execute([$userId]);
$monthlyMetrics = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate fitness level based on consistency
$fitnessLevel = 'Beginner';
if ($currentStreak >= 30) {
    $fitnessLevel = 'Elite';
} elseif ($currentStreak >= 14) {
    $fitnessLevel = 'Advanced';
} elseif ($currentStreak >= 7) {
    $fitnessLevel = 'Intermediate';
} elseif ($workoutsThisWeek >= 3) {
    $fitnessLevel = 'Getting Started';
}

// Get progress chart data (last 7 days)
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
$progressData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fill in missing days with 0
$progressChartData = [];
$progressLabels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $progressLabels[] = date('M j', strtotime($date));
    $workouts = 0;
    foreach ($progressData as $row) {
        if ($row['date'] === $date) {
            $workouts = (int)$row['workouts'];
            break;
        }
    }
    $progressChartData[] = $workouts;
}

// Get calories chart data (last 7 days)
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
$caloriesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fill in missing days with 0
$caloriesChartData = [];
$caloriesLabels = [];
$caloriesColors = ['#8B5CF6', '#10B981', '#F59E0B', '#EF4444', '#3B82F6', '#8B5CF6', '#EC4899'];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $caloriesLabels[] = date('D', strtotime($date));
    $calories = 0;
    foreach ($caloriesData as $row) {
        if ($row['date'] === $date) {
            $calories = (int)$row['calories'];
            break;
        }
    }
    $caloriesChartData[] = $calories;
}

// Get recent activity
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
$recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - HealthMate</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/text-brightness.css">
    <style>
        .progress-item {
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .progress-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
        }
        
        .progress-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
        }
        
        .fitness-level-indicator {
            margin-top: 5px;
        }
        
        .level-bar {
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            overflow: hidden;
        }
        
        .level-fill {
            height: 100%;
            background: linear-gradient(90deg, #10B981, #3B82F6, #8B5CF6);
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        
        .points-display {
            display: flex;
            align-items: center;
            margin-top: 5px;
        }
        
        .points-text {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .progress {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .progress-bar {
            transition: width 0.6s ease;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../index.html">
                <i class="fas fa-heartbeat me-2"></i>HealthMate
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="workouts.php">
                            <i class="fas fa-dumbbell me-1"></i>Workouts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="leaderboard.php">
                            <i class="fas fa-trophy me-1"></i>Leaderboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="achievements.php">
                            <i class="fas fa-medal me-1"></i>Achievements
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <div class="avatar me-2">
                                <?php 
                                $gender = $user['gender'] ?? '';
                                $firstName = strtolower($user['first_name'] ?? '');
                                if ($firstName === 'ashish' || $gender === 'male') {
                                    echo '<i class="fas fa-mars fa-lg" style="color: #2196f3;"></i>';
                                } elseif ($gender === 'female') {
                                    echo '<i class="fas fa-venus fa-lg" style="color: #e91e63;"></i>';
                                } else {
                                    echo '<i class="fas fa-user-circle fa-lg"></i>';
                                }
                                ?>
                            </div>
                            <span><?php echo htmlspecialchars($user['first_name']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="dropdown-header d-flex align-items-center">
                                <span class="me-2">
                                    <?php 
                                    $gender = $user['gender'] ?? '';
                                    $firstName = strtolower($user['first_name'] ?? '');
                                    if ($firstName === 'ashish' || $gender === 'male') {
                                        echo '<i class="fas fa-mars" style="color: #2196f3;"></i>';
                                    } elseif ($gender === 'female') {
                                        echo '<i class="fas fa-venus" style="color: #e91e63;"></i>';
                                    } else {
                                        echo '<i class="fas fa-user"></i>';
                                    }
                                    ?>
                                </span>
                                <span class="text-capitalize"><?php echo ucfirst($gender ?: 'User'); ?></span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-warning" href="../admin_login.php" target="_blank"><i class="fas fa-shield-alt me-2"></i>Admin Panel</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" id="logoutBtn"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="dashboard-container">
        <div class="container-fluid">
        <!-- Welcome Section -->
        <div class="dashboard-header animate-fade-in-up">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="dashboard-title">
                        Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>! 
                        <?php 
                        $gender = $user['gender'] ?? '';
                        $firstName = strtolower($user['first_name'] ?? '');
                        if ($firstName === 'ashish' || $gender === 'male') {
                            echo '<i class="fas fa-mars ms-2" style="color: #2196f3; font-size: 0.8em;"></i>';
                        } elseif ($gender === 'female') {
                            echo '<i class="fas fa-venus ms-2" style="color: #e91e63; font-size: 0.8em;"></i>';
                        } else {
                            echo '👋';
                        }
                        ?>
                    </h1>
                    <p class="dashboard-subtitle">Ready to crush your fitness goals today? Let's make today count!</p>
                    
                    <!-- Animated SVG Background -->
                    <div class="animated-svg-background">
                        <svg width="100%" height="100" viewBox="0 0 1200 100" class="welcome-svg">
                            <defs>
                                <linearGradient id="waveGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" style="stop-color:#2196f3;stop-opacity:0.3" />
                                    <stop offset="50%" style="stop-color:#00bcd4;stop-opacity:0.2" />
                                    <stop offset="100%" style="stop-color:#4caf50;stop-opacity:0.3" />
                                </linearGradient>
                            </defs>
                            <path d="M0,50 Q300,20 600,50 T1200,50 L1200,100 L0,100 Z" fill="url(#waveGradient)" class="wave-path">
                                <animateTransform attributeName="transform" type="translate" values="0,0; -50,0; 0,0" dur="8s" repeatCount="indefinite"/>
                            </path>
                            <circle cx="200" cy="30" r="3" fill="#2196f3" opacity="0.6" class="floating-dot">
                                <animate attributeName="cy" values="30;20;30" dur="3s" repeatCount="indefinite"/>
                                <animate attributeName="opacity" values="0.6;1;0.6" dur="3s" repeatCount="indefinite"/>
                            </circle>
                            <circle cx="800" cy="40" r="2" fill="#00bcd4" opacity="0.7" class="floating-dot">
                                <animate attributeName="cy" values="40;25;40" dur="4s" repeatCount="indefinite"/>
                                <animate attributeName="opacity" values="0.7;1;0.7" dur="4s" repeatCount="indefinite"/>
                            </circle>
                            <circle cx="1000" cy="35" r="2.5" fill="#4caf50" opacity="0.5" class="floating-dot">
                                <animate attributeName="cy" values="35;22;35" dur="5s" repeatCount="indefinite"/>
                                <animate attributeName="opacity" values="0.5;1;0.5" dur="5s" repeatCount="indefinite"/>
                            </circle>
                        </svg>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="d-flex align-items-center justify-content-lg-end">
                        <div class="me-3 text-center">
                            <div class="stat-number counter" data-target="<?php echo $user['points']; ?>">0</div>
                            <div class="stat-label">Total Points</div>
                        </div>
                        <div class="stat-icon">
                            <!-- Animated Trophy SVG -->
                            <svg width="60" height="60" viewBox="0 0 100 100" class="animated-trophy">
                                <defs>
                                    <linearGradient id="trophyGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" style="stop-color:#ffd700;stop-opacity:1" />
                                        <stop offset="50%" style="stop-color:#ffc107;stop-opacity:1" />
                                        <stop offset="100%" style="stop-color:#ff9800;stop-opacity:1" />
                                    </linearGradient>
                                </defs>
                                <!-- Trophy Base -->
                                <rect x="35" y="70" width="30" height="8" rx="4" fill="url(#trophyGradient)">
                                    <animate attributeName="opacity" values="0.8;1;0.8" dur="2s" repeatCount="indefinite"/>
                                </rect>
                                <!-- Trophy Body -->
                                <rect x="40" y="30" width="20" height="40" rx="10" fill="url(#trophyGradient)">
                                    <animateTransform attributeName="transform" type="scale" values="1;1.05;1" dur="3s" repeatCount="indefinite"/>
                                </rect>
                                <!-- Trophy Handles -->
                                <path d="M40,40 Q30,35 35,50 Q40,45 40,40" fill="none" stroke="url(#trophyGradient)" stroke-width="3">
                                    <animate attributeName="stroke-opacity" values="0.7;1;0.7" dur="2.5s" repeatCount="indefinite"/>
                                </path>
                                <path d="M60,40 Q70,35 65,50 Q60,45 60,40" fill="none" stroke="url(#trophyGradient)" stroke-width="3">
                                    <animate attributeName="stroke-opacity" values="0.7;1;0.7" dur="2.5s" repeatCount="indefinite"/>
                                </path>
                                <!-- Trophy Top -->
                                <rect x="45" y="25" width="10" height="8" rx="5" fill="url(#trophyGradient)">
                                    <animate attributeName="y" values="25;23;25" dur="1.5s" repeatCount="indefinite"/>
                                </rect>
                                <!-- Sparkles -->
                                <circle cx="25" cy="20" r="2" fill="#ffd700" opacity="0.8">
                                    <animate attributeName="opacity" values="0;1;0" dur="2s" repeatCount="indefinite"/>
                                    <animateTransform attributeName="transform" type="scale" values="0.5;1.2;0.5" dur="2s" repeatCount="indefinite"/>
                                </circle>
                                <circle cx="75" cy="25" r="1.5" fill="#ffc107" opacity="0.6">
                                    <animate attributeName="opacity" values="0;1;0" dur="2.5s" repeatCount="indefinite"/>
                                    <animateTransform attributeName="transform" type="scale" values="0.3;1;0.3" dur="2.5s" repeatCount="indefinite"/>
                                </circle>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="dashboard-stats animate-fade-in-up">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <!-- Animated Fire SVG -->
                            <svg width="50" height="50" viewBox="0 0 100 100" class="animated-fire">
                                <defs>
                                    <linearGradient id="fireGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" style="stop-color:#ff5722;stop-opacity:1" />
                                        <stop offset="50%" style="stop-color:#ff9800;stop-opacity:1" />
                                        <stop offset="100%" style="stop-color:#ffc107;stop-opacity:1" />
                                    </linearGradient>
                                </defs>
                                <path d="M50,80 Q30,60 40,40 Q50,20 60,40 Q70,60 50,80 Z" fill="url(#fireGradient)">
                                    <animateTransform attributeName="transform" type="scale" values="1;1.1;1" dur="2s" repeatCount="indefinite"/>
                                </path>
                                <path d="M50,70 Q35,55 42,45 Q50,35 58,45 Q65,55 50,70 Z" fill="#ff9800" opacity="0.8">
                                    <animate attributeName="opacity" values="0.8;1;0.8" dur="1.5s" repeatCount="indefinite"/>
                                </path>
                                <circle cx="45" cy="50" r="3" fill="#ffc107" opacity="0.9">
                                    <animate attributeName="cy" values="50;45;50" dur="1s" repeatCount="indefinite"/>
                                </circle>
                                <circle cx="55" cy="48" r="2" fill="#ffeb3b" opacity="0.7">
                                    <animate attributeName="cy" values="48;43;48" dur="1.2s" repeatCount="indefinite"/>
                                </circle>
                            </svg>
                        </div>
                        <div class="stat-number counter" data-target="<?php echo $workoutsThisWeek; ?>">0</div>
                        <div class="stat-label">Workouts This Week</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <!-- Animated Burn/Flame SVG -->
                            <svg width="50" height="50" viewBox="0 0 100 100" class="animated-burn">
                                <defs>
                                    <linearGradient id="burnGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" style="stop-color:#f44336;stop-opacity:1" />
                                        <stop offset="50%" style="stop-color:#ff5722;stop-opacity:1" />
                                        <stop offset="100%" style="stop-color:#ff9800;stop-opacity:1" />
                                    </linearGradient>
                                </defs>
                                <path d="M50,85 Q25,70 35,50 Q50,30 65,50 Q75,70 50,85 Z" fill="url(#burnGradient)">
                                    <animateTransform attributeName="transform" type="scale" values="1;1.05;1" dur="2.5s" repeatCount="indefinite"/>
                                </path>
                                <path d="M50,75 Q30,65 40,50 Q50,35 60,50 Q70,65 50,75 Z" fill="#ff5722" opacity="0.9">
                                    <animate attributeName="opacity" values="0.9;1;0.9" dur="1.8s" repeatCount="indefinite"/>
                                </path>
                                <path d="M50,65 Q35,55 45,45 Q50,35 55,45 Q65,55 50,65 Z" fill="#ff9800" opacity="0.8">
                                    <animate attributeName="opacity" values="0.8;1;0.8" dur="1.3s" repeatCount="indefinite"/>
                                </path>
                                <!-- Flame particles -->
                                <circle cx="40" cy="60" r="2" fill="#ffeb3b" opacity="0.8">
                                    <animate attributeName="cy" values="60;55;60" dur="1.5s" repeatCount="indefinite"/>
                                    <animate attributeName="opacity" values="0.8;1;0.8" dur="1.5s" repeatCount="indefinite"/>
                                </circle>
                                <circle cx="60" cy="58" r="1.5" fill="#ffc107" opacity="0.7">
                                    <animate attributeName="cy" values="58;53;58" dur="1.7s" repeatCount="indefinite"/>
                                    <animate attributeName="opacity" values="0.7;1;0.7" dur="1.7s" repeatCount="indefinite"/>
                                </circle>
                            </svg>
                        </div>
                        <div id="statCaloriesBurned" class="stat-number counter" data-target="<?php echo $caloriesBurned; ?>">0</div>
                        <div class="stat-label">Calories Burned</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div id="statDayStreak" class="stat-number counter" data-target="<?php echo $currentStreak; ?>">0</div>
                        <div class="stat-label">Day Streak</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-target"></i>
                        </div>
                        <div id="statGoalProgress" class="stat-number counter" data-target="<?php echo round($goalProgress); ?>">0</div>
                        <div class="stat-label">Goal Progress %</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Summary Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card progress-summary-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Progress Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="progress-item">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="progress-label">Weekly Goal</span>
                                        <span class="progress-value"><?php echo $workoutsThisWeek; ?>/<?php echo $weeklyGoal; ?></span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $goalProgress; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="progress-item">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="progress-label">Fitness Level</span>
                                        <span class="progress-value"><?php echo $fitnessLevel; ?></span>
                                    </div>
                                    <div class="fitness-level-indicator">
                                        <div class="level-bar">
                                            <div class="level-fill" style="width: <?php echo min(100, ($currentStreak / 30) * 100); ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="progress-item">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="progress-label">Avg Workout</span>
                                        <span class="progress-value"><?php echo round($progressMetrics['avg_duration_per_workout'] ?? 0); ?> min</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo min(100, (($progressMetrics['avg_duration_per_workout'] ?? 0) / 60) * 100); ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="progress-item">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="progress-label">Total Points</span>
                                        <span class="progress-value"><?php echo $user['points']; ?></span>
                                    </div>
                                    <div class="points-display">
                                        <i class="fas fa-star text-warning me-1"></i>
                                        <span class="points-text"><?php echo $user['points']; ?> points earned</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row mb-4">
            <div class="col-lg-8 mb-4">
                <div class="chart-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="chart-title mb-0">Progress Overview</h5>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary btn-sm active">Week</button>
                            <button type="button" class="btn btn-outline-primary btn-sm">Month</button>
                            <button type="button" class="btn btn-outline-primary btn-sm">Year</button>
                        </div>
                    </div>
                    <div style="height: 300px; position: relative;">
                        <canvas id="progressChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="chart-container">
                    <h5 class="chart-title">Calories Burned</h5>
                    <div style="height: 300px; position: relative;">
                        <canvas id="caloriesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Recent Activity -->
        <div class="row mb-4">
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="quick-action-card" onclick="startQuickWorkout()">
                                    <div class="quick-action-icon">
                                        <i class="fas fa-play"></i>
                                    </div>
                                    <div class="quick-action-content">
                                        <h6>Start Workout</h6>
                                        <p class="text-muted mb-0">Begin your daily routine</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="quick-action-card" onclick="logProgress()">
                                    <div class="quick-action-icon">
                                        <i class="fas fa-plus"></i>
                                    </div>
                                    <div class="quick-action-content">
                                        <h6>Log Progress</h6>
                                        <p class="text-muted mb-0">Update your metrics</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="quick-action-card" onclick="viewWorkouts()">
                                    <div class="quick-action-icon">
                                        <i class="fas fa-dumbbell"></i>
                                    </div>
                                    <div class="quick-action-content">
                                        <h6>Browse Workouts</h6>
                                        <p class="text-muted mb-0">Find new routines</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="quick-action-card" onclick="viewAchievements()">
                                    <div class="quick-action-icon">
                                        <i class="fas fa-medal"></i>
                                    </div>
                                    <div class="quick-action-content">
                                        <h6>View Achievements</h6>
                                        <p class="text-muted mb-0">See your badges</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Recent Activity card removed as requested -->
        </div>

        <!-- Enhanced Navigation Tabs -->
        <ul class="nav nav-tabs animate-fade-in-up" id="mainTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="trackerTab" data-bs-toggle="tab" data-bs-target="#trackerContent" type="button" role="tab">
                    <i class="fas fa-calculator me-2"></i>Nutrition Tracker
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="bmrTab" data-bs-toggle="tab" data-bs-target="#bmrContent" type="button" role="tab">
                    <i class="fas fa-heartbeat me-2"></i>BMR & Plans
                </button>
            </li>
            
        </ul>

        <!-- Tab Content -->
        <div class="tab-content animate-fade-in-up" id="mainTabContent">
            <!-- Tracker Tab -->
            <div class="tab-pane fade show active" id="trackerContent" role="tabpanel">
                            <div class="row">
                                <div class="col-lg-8">
                                    <div class="calories-calculator">
                                        <!-- Meal Selection -->
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <label class="form-label">Select Meal</label>
                                                <div class="btn-group w-100" role="group">
                                                    <input type="radio" class="btn-check" name="mealType" id="breakfast" value="breakfast" checked>
                                                    <label class="btn btn-outline-primary" for="breakfast">
                                                        <i class="fas fa-sun me-1"></i>Breakfast
                                                    </label>
                                                    <input type="radio" class="btn-check" name="mealType" id="lunch" value="lunch">
                                                    <label class="btn btn-outline-primary" for="lunch">
                                                        <i class="fas fa-sun me-1"></i>Lunch
                                                    </label>
                                                    <input type="radio" class="btn-check" name="mealType" id="dinner" value="dinner">
                                                    <label class="btn btn-outline-primary" for="dinner">
                                                        <i class="fas fa-moon me-1"></i>Dinner
                                                    </label>
                                                    <input type="radio" class="btn-check" name="mealType" id="snacks" value="snacks">
                                                    <label class="btn btn-outline-primary" for="snacks">
                                                        <i class="fas fa-cookie-bite me-1"></i>Snacks
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Quick Breakfast Options -->
                                        <div class="breakfast-options mb-4" id="breakfastOptions">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6><i class="fas fa-coffee me-2"></i>Quick Breakfast Options</h6>
                                                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#breakfastCollapse" aria-expanded="true">
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                            </div>
                                            <div class="collapse show" id="breakfastCollapse">
                                                <div class="breakfast-categories">
                                                    <!-- Protein Options -->
                                                    <div class="breakfast-category mb-3">
                                                        <h6 class="category-title">
                                                            <i class="fas fa-dumbbell me-2 text-primary"></i>Protein Power
                                                            <small class="text-muted">High protein breakfast options</small>
                                                        </h6>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 breakfast-btn" data-food="eggs" data-amount="2" data-unit="large">
                                                                    <div class="breakfast-item">
                                                                        <i class="fas fa-egg"></i>
                                                                        <span>2 Large Eggs</span>
                                                                        <small>140 cal, 12g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 breakfast-btn" data-food="greek_yogurt" data-amount="200" data-unit="g">
                                                                    <div class="breakfast-item">
                                                                        <i class="fas fa-blender"></i>
                                                                        <span>Greek Yogurt</span>
                                                                        <small>200g, 120 cal, 20g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 breakfast-btn" data-food="protein_powder" data-amount="1" data-unit="scoop">
                                                                    <div class="breakfast-item">
                                                                        <i class="fas fa-flask"></i>
                                                                        <span>Protein Shake</span>
                                                                        <small>1 scoop, 120 cal, 25g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 breakfast-btn" data-food="cottage_cheese" data-amount="150" data-unit="g">
                                                                    <div class="breakfast-item">
                                                                        <i class="fas fa-cheese"></i>
                                                                        <span>Cottage Cheese</span>
                                                                        <small>150g, 120 cal, 18g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Carb Options -->
                                                    <div class="breakfast-category mb-3">
                                                        <h6 class="category-title">
                                                            <i class="fas fa-seedling me-2 text-success"></i>Energy Carbs
                                                            <small class="text-muted">Healthy carbohydrate sources</small>
                                                        </h6>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 breakfast-btn" data-food="oatmeal" data-amount="50" data-unit="g">
                                                                    <div class="breakfast-item">
                                                                        <i class="fas fa-bowl-food"></i>
                                                                        <span>Oatmeal</span>
                                                                        <small>50g dry, 190 cal, 35g carbs</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 breakfast-btn" data-food="whole_grain_bread" data-amount="2" data-unit="slice">
                                                                    <div class="breakfast-item">
                                                                        <i class="fas fa-bread-slice"></i>
                                                                        <span>Whole Grain Bread</span>
                                                                        <small>2 slices, 160 cal, 30g carbs</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 breakfast-btn" data-food="banana" data-amount="1" data-unit="medium">
                                                                    <div class="breakfast-item">
                                                                        <i class="fas fa-banana"></i>
                                                                        <span>Banana</span>
                                                                        <small>1 medium, 105 cal, 27g carbs</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 breakfast-btn" data-food="quinoa" data-amount="80" data-unit="g">
                                                                    <div class="breakfast-item">
                                                                        <i class="fas fa-seedling"></i>
                                                                        <span>Quinoa Bowl</span>
                                                                        <small>80g dry, 280 cal, 50g carbs</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Healthy Fats -->
                                                    <div class="breakfast-category mb-3">
                                                        <h6 class="category-title">
                                                            <i class="fas fa-heart me-2 text-warning"></i>Healthy Fats
                                                            <small class="text-muted">Essential fatty acids</small>
                                                        </h6>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-warning w-100 breakfast-btn" data-food="avocado" data-amount="0.5" data-unit="medium">
                                                                    <div class="breakfast-item">
                                                                        <i class="fas fa-leaf"></i>
                                                                        <span>Avocado</span>
                                                                        <small>1/2 medium, 120 cal, 11g fat</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-warning w-100 breakfast-btn" data-food="almonds" data-amount="30" data-unit="g">
                                                                    <div class="breakfast-item">
                                                                        <i class="fas fa-nut"></i>
                                                                        <span>Almonds</span>
                                                                        <small>30g, 170 cal, 15g fat</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-warning w-100 breakfast-btn" data-food="chia_seeds" data-amount="15" data-unit="g">
                                                                    <div class="breakfast-item">
                                                                        <i class="fas fa-seedling"></i>
                                                                        <span>Chia Seeds</span>
                                                                        <small>15g, 70 cal, 5g fat</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-warning w-100 breakfast-btn" data-food="peanut_butter" data-amount="1" data-unit="tbsp">
                                                                    <div class="breakfast-item">
                                                                        <i class="fas fa-jar"></i>
                                                                        <span>Peanut Butter</span>
                                                                        <small>1 tbsp, 95 cal, 8g fat</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Complete Breakfast Combos -->
                                                    <div class="breakfast-category mb-3">
                                                        <h6 class="category-title">
                                                            <i class="fas fa-star me-2 text-info"></i>Complete Combos
                                                            <small class="text-muted">Balanced breakfast meals</small>
                                                        </h6>
                                                        <div class="row">
                                                            <div class="col-lg-4 col-md-6 mb-2">
                                                                <button class="btn btn-outline-info w-100 breakfast-combo-btn" data-combo="protein_power">
                                                                    <div class="breakfast-combo">
                                                                        <i class="fas fa-dumbbell"></i>
                                                                        <span>Protein Power</span>
                                                                        <small>2 eggs + Greek yogurt + berries</small>
                                                                        <div class="combo-calories">~350 cal, 35g protein</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-4 col-md-6 mb-2">
                                                                <button class="btn btn-outline-info w-100 breakfast-combo-btn" data-combo="energy_boost">
                                                                    <div class="breakfast-combo">
                                                                        <i class="fas fa-bolt"></i>
                                                                        <span>Energy Boost</span>
                                                                        <small>Oatmeal + banana + almonds</small>
                                                                        <div class="combo-calories">~450 cal, 15g protein</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-4 col-md-6 mb-2">
                                                                <button class="btn btn-outline-info w-100 breakfast-combo-btn" data-combo="healthy_start">
                                                                    <div class="breakfast-combo">
                                                                        <i class="fas fa-heart"></i>
                                                                        <span>Healthy Start</span>
                                                                        <small>Avocado toast + eggs + spinach</small>
                                                                        <div class="combo-calories">~400 cal, 20g protein</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Quick Lunch Options -->
                                        <div class="lunch-options mb-4" id="lunchOptions" style="display: none;">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6><i class="fas fa-sun me-2"></i>Quick Lunch Options</h6>
                                                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#lunchCollapse" aria-expanded="true">
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                            </div>
                                            <div class="collapse show" id="lunchCollapse">
                                                <div class="lunch-categories">
                                                    <!-- Protein Options -->
                                                    <div class="lunch-category mb-3">
                                                        <h6 class="category-title">
                                                            <i class="fas fa-drumstick-bite me-2 text-primary"></i>Protein Power
                                                            <small class="text-muted">High protein lunch options</small>
                                                        </h6>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 lunch-btn" data-food="chicken_breast" data-amount="150" data-unit="g">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-drumstick-bite"></i>
                                                                        <span>Grilled Chicken</span>
                                                                        <small>150g, 250 cal, 46g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 lunch-btn" data-food="salmon" data-amount="120" data-unit="g">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-fish"></i>
                                                                        <span>Salmon Fillet</span>
                                                                        <small>120g, 200 cal, 25g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 lunch-btn" data-food="turkey_breast" data-amount="100" data-unit="g">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-turkey"></i>
                                                                        <span>Turkey Breast</span>
                                                                        <small>100g, 160 cal, 30g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 lunch-btn" data-food="tofu" data-amount="150" data-unit="g">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-leaf"></i>
                                                                        <span>Tofu</span>
                                                                        <small>150g, 120 cal, 15g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Carb Options -->
                                                    <div class="lunch-category mb-3">
                                                        <h6 class="category-title">
                                                            <i class="fas fa-bread-slice me-2 text-success"></i>Energy Carbs
                                                            <small class="text-muted">Healthy carbohydrate sources</small>
                                                        </h6>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 lunch-btn" data-food="brown_rice" data-amount="100" data-unit="g">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-seedling"></i>
                                                                        <span>Brown Rice</span>
                                                                        <small>100g cooked, 110 cal, 23g carbs</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 lunch-btn" data-food="sweet_potato" data-amount="1" data-unit="medium">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-carrot"></i>
                                                                        <span>Sweet Potato</span>
                                                                        <small>1 medium, 130 cal, 30g carbs</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 lunch-btn" data-food="quinoa" data-amount="80" data-unit="g">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-seedling"></i>
                                                                        <span>Quinoa</span>
                                                                        <small>80g cooked, 120 cal, 22g carbs</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 lunch-btn" data-food="whole_grain_pasta" data-amount="80" data-unit="g">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-utensils"></i>
                                                                        <span>Whole Grain Pasta</span>
                                                                        <small>80g dry, 280 cal, 56g carbs</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Vegetable Options -->
                                                    <div class="lunch-category mb-3">
                                                        <h6 class="category-title">
                                                            <i class="fas fa-leaf me-2 text-success"></i>Fresh Vegetables
                                                            <small class="text-muted">Nutrient-dense vegetables</small>
                                                        </h6>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 lunch-btn" data-food="broccoli" data-amount="100" data-unit="g">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-seedling"></i>
                                                                        <span>Broccoli</span>
                                                                        <small>100g, 35 cal, 3g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 lunch-btn" data-food="spinach" data-amount="50" data-unit="g">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-leaf"></i>
                                                                        <span>Spinach</span>
                                                                        <small>50g, 12 cal, 1.5g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 lunch-btn" data-food="bell_peppers" data-amount="100" data-unit="g">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-pepper-hot"></i>
                                                                        <span>Bell Peppers</span>
                                                                        <small>100g, 25 cal, 1g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 lunch-btn" data-food="tomatoes" data-amount="100" data-unit="g">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-apple-alt"></i>
                                                                        <span>Tomatoes</span>
                                                                        <small>100g, 18 cal, 1g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Healthy Fats -->
                                                    <div class="lunch-category mb-3">
                                                        <h6 class="category-title">
                                                            <i class="fas fa-heart me-2 text-warning"></i>Healthy Fats
                                                            <small class="text-muted">Essential fatty acids for lunch</small>
                                                        </h6>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-warning w-100 lunch-btn" data-food="avocado" data-amount="0.5" data-unit="medium">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-leaf"></i>
                                                                        <span>Avocado</span>
                                                                        <small>1/2 medium, 120 cal, 11g fat</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-warning w-100 lunch-btn" data-food="olive_oil" data-amount="1" data-unit="tbsp">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-tint"></i>
                                                                        <span>Olive Oil</span>
                                                                        <small>1 tbsp, 120 cal, 14g fat</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-warning w-100 lunch-btn" data-food="almonds" data-amount="25" data-unit="g">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-nut"></i>
                                                                        <span>Almonds</span>
                                                                        <small>25g, 150 cal, 13g fat</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-warning w-100 lunch-btn" data-food="walnuts" data-amount="25" data-unit="g">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-nut"></i>
                                                                        <span>Walnuts</span>
                                                                        <small>25g, 165 cal, 16g fat</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Lunch Salads -->
                                                    <div class="lunch-category mb-3">
                                                        <h6 class="category-title">
                                                            <i class="fas fa-leaf me-2 text-success"></i>Fresh Salads
                                                            <small class="text-muted">Nutrient-packed lunch salads</small>
                                                        </h6>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 lunch-btn" data-food="mixed_greens" data-amount="100" data-unit="g">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-seedling"></i>
                                                                        <span>Mixed Greens</span>
                                                                        <small>100g, 20 cal, 2g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 lunch-btn" data-food="arugula" data-amount="50" data-unit="g">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-leaf"></i>
                                                                        <span>Arugula</span>
                                                                        <small>50g, 12 cal, 1.5g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 lunch-btn" data-food="kale" data-amount="50" data-unit="g">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-seedling"></i>
                                                                        <span>Kale</span>
                                                                        <small>50g, 25 cal, 2g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 lunch-btn" data-food="cucumber" data-amount="100" data-unit="g">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-seedling"></i>
                                                                        <span>Cucumber</span>
                                                                        <small>100g, 16 cal, 1g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Lunch Wraps & Sandwiches -->
                                                    <div class="lunch-category mb-3">
                                                        <h6 class="category-title">
                                                            <i class="fas fa-bread-slice me-2 text-primary"></i>Wraps & Sandwiches
                                                            <small class="text-muted">Convenient lunch options</small>
                                                        </h6>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 lunch-btn" data-food="whole_grain_tortilla" data-amount="1" data-unit="large">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-bread-slice"></i>
                                                                        <span>Whole Grain Tortilla</span>
                                                                        <small>1 large, 150 cal, 4g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 lunch-btn" data-food="whole_grain_bread" data-amount="2" data-unit="slice">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-bread-slice"></i>
                                                                        <span>Whole Grain Bread</span>
                                                                        <small>2 slices, 160 cal, 8g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 lunch-btn" data-food="lettuce_wrap" data-amount="2" data-unit="large">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-leaf"></i>
                                                                        <span>Lettuce Wrap</span>
                                                                        <small>2 large leaves, 10 cal, 1g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 lunch-btn" data-food="pita_bread" data-amount="1" data-unit="medium">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-bread-slice"></i>
                                                                        <span>Whole Wheat Pita</span>
                                                                        <small>1 medium, 140 cal, 5g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Lunch Proteins Extended -->
                                                    <div class="lunch-category mb-3">
                                                        <h6 class="category-title">
                                                            <i class="fas fa-drumstick-bite me-2 text-primary"></i>More Proteins
                                                            <small class="text-muted">Additional protein sources</small>
                                                        </h6>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 lunch-btn" data-food="lean_beef" data-amount="100" data-unit="g">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-hamburger"></i>
                                                                        <span>Lean Beef</span>
                                                                        <small>100g, 170 cal, 25g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 lunch-btn" data-food="white_fish" data-amount="120" data-unit="g">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-fish"></i>
                                                                        <span>White Fish</span>
                                                                        <small>120g, 120 cal, 28g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 lunch-btn" data-food="lentils" data-amount="100" data-unit="g">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-seedling"></i>
                                                                        <span>Lentils</span>
                                                                        <small>100g cooked, 116 cal, 9g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 lunch-btn" data-food="black_beans" data-amount="100" data-unit="g">
                                                                    <div class="lunch-item">
                                                                        <i class="fas fa-seedling"></i>
                                                                        <span>Black Beans</span>
                                                                        <small>100g cooked, 132 cal, 9g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Complete Lunch Combos -->
                                                    <div class="lunch-category mb-3">
                                                        <h6 class="category-title">
                                                            <i class="fas fa-star me-2 text-info"></i>Complete Combos
                                                            <small class="text-muted">Balanced lunch meals</small>
                                                        </h6>
                                                        <div class="row">
                                                            <div class="col-lg-4 col-md-6 mb-2">
                                                                <button class="btn btn-outline-info w-100 lunch-combo-btn" data-combo="protein_bowl">
                                                                    <div class="lunch-combo">
                                                                        <i class="fas fa-bowl-food"></i>
                                                                        <span>Protein Bowl</span>
                                                                        <small>Chicken + rice + vegetables</small>
                                                                        <div class="combo-calories">~450 cal, 35g protein</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-4 col-md-6 mb-2">
                                                                <button class="btn btn-outline-info w-100 lunch-combo-btn" data-combo="salmon_meal">
                                                                    <div class="lunch-combo">
                                                                        <i class="fas fa-fish"></i>
                                                                        <span>Salmon Meal</span>
                                                                        <small>Salmon + quinoa + greens</small>
                                                                        <div class="combo-calories">~400 cal, 30g protein</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-4 col-md-6 mb-2">
                                                                <button class="btn btn-outline-info w-100 lunch-combo-btn" data-combo="veggie_power">
                                                                    <div class="lunch-combo">
                                                                        <i class="fas fa-leaf"></i>
                                                                        <span>Veggie Power</span>
                                                                        <small>Tofu + vegetables + rice</small>
                                                                        <div class="combo-calories">~350 cal, 20g protein</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-4 col-md-6 mb-2">
                                                                <button class="btn btn-outline-info w-100 lunch-combo-btn" data-combo="mediterranean_lunch">
                                                                    <div class="lunch-combo">
                                                                        <i class="fas fa-leaf"></i>
                                                                        <span>Mediterranean Lunch</span>
                                                                        <small>Fish + olive oil + vegetables</small>
                                                                        <div class="combo-calories">~380 cal, 25g protein</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-4 col-md-6 mb-2">
                                                                <button class="btn btn-outline-info w-100 lunch-combo-btn" data-combo="power_salad">
                                                                    <div class="lunch-combo">
                                                                        <i class="fas fa-seedling"></i>
                                                                        <span>Power Salad</span>
                                                                        <small>Mixed greens + protein + nuts</small>
                                                                        <div class="combo-calories">~320 cal, 22g protein</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-4 col-md-6 mb-2">
                                                                <button class="btn btn-outline-info w-100 lunch-combo-btn" data-combo="wrap_lunch">
                                                                    <div class="lunch-combo">
                                                                        <i class="fas fa-bread-slice"></i>
                                                                        <span>Wrap Lunch</span>
                                                                        <small>Tortilla + protein + vegetables</small>
                                                                        <div class="combo-calories">~400 cal, 28g protein</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Detailed Lunch Meal Plans -->
                                                    <div class="lunch-category mb-3">
                                                        <h6 class="category-title">
                                                            <i class="fas fa-utensils me-2 text-success"></i>Detailed Lunch Meal Plans
                                                            <small class="text-muted">Complete meals with specific portions and macros</small>
                                                        </h6>
                                                        <div class="row">
                                                            <div class="col-lg-6 col-md-6 mb-3">
                                                                <button class="btn btn-outline-success w-100 lunch-meal-btn" data-meal="grilled_chicken_rice_bowl">
                                                                    <div class="lunch-meal">
                                                                        <i class="fas fa-bowl-food"></i>
                                                                        <span>Grilled Chicken & Brown Rice Bowl</span>
                                                                        <small>200g chicken + 150g brown rice + broccoli & carrots</small>
                                                                        <div class="meal-macros">600 kcal | 55g protein | 65g carbs | 15g fat</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-6 col-md-6 mb-3">
                                                                <button class="btn btn-outline-success w-100 lunch-meal-btn" data-meal="egg_sweet_potato_bowl">
                                                                    <div class="lunch-meal">
                                                                        <i class="fas fa-egg"></i>
                                                                        <span>Egg & Sweet Potato Power Bowl</span>
                                                                        <small>4 eggs + 200g sweet potato + spinach & peppers</small>
                                                                        <div class="meal-macros">550 kcal | 35g protein | 50g carbs | 20g fat</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-6 col-md-6 mb-3">
                                                                <button class="btn btn-outline-success w-100 lunch-meal-btn" data-meal="salmon_quinoa_plate">
                                                                    <div class="lunch-meal">
                                                                        <i class="fas fa-fish"></i>
                                                                        <span>Salmon & Quinoa Plate</span>
                                                                        <small>180g salmon + 120g quinoa + asparagus & zucchini</small>
                                                                        <div class="meal-macros">650 kcal | 45g protein | 50g carbs | 25g fat</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-6 col-md-6 mb-3">
                                                                <button class="btn btn-outline-success w-100 lunch-meal-btn" data-meal="chicken_wraps">
                                                                    <div class="lunch-meal">
                                                                        <i class="fas fa-bread-slice"></i>
                                                                        <span>Chicken Wraps</span>
                                                                        <small>150g chicken + 2 tortillas + lettuce, tomato, onion</small>
                                                                        <div class="meal-macros">500 kcal | 40g protein | 55g carbs | 12g fat</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-6 col-md-6 mb-3">
                                                                <button class="btn btn-outline-success w-100 lunch-meal-btn" data-meal="lean_beef_rice_bowl">
                                                                    <div class="lunch-meal">
                                                                        <i class="fas fa-hamburger"></i>
                                                                        <span>Lean Beef Rice Bowl</span>
                                                                        <small>200g lean beef + 150g white rice + green beans & mushrooms</small>
                                                                        <div class="meal-macros">700 kcal | 55g protein | 65g carbs | 18g fat</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-6 col-md-6 mb-3">
                                                                <button class="btn btn-outline-success w-100 lunch-meal-btn" data-meal="paneer_veggie_stirfry">
                                                                    <div class="lunch-meal">
                                                                        <i class="fas fa-leaf"></i>
                                                                        <span>Paneer & Veggie Stir-Fry</span>
                                                                        <small>150g paneer + 100g brown rice + mixed peppers & broccoli</small>
                                                                        <div class="meal-macros">600 kcal | 35g protein | 45g carbs | 20g fat</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-6 col-md-6 mb-3">
                                                                <button class="btn btn-outline-success w-100 lunch-meal-btn" data-meal="tuna_pasta_salad">
                                                                    <div class="lunch-meal">
                                                                        <i class="fas fa-fish"></i>
                                                                        <span>Tuna Pasta Salad</span>
                                                                        <small>120g tuna + 100g whole wheat pasta + cucumber & tomato</small>
                                                                        <div class="meal-macros">550 kcal | 42g protein | 60g carbs | 14g fat</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-6 col-md-6 mb-3">
                                                                <button class="btn btn-outline-success w-100 lunch-meal-btn" data-meal="chicken_avocado_salad">
                                                                    <div class="lunch-meal">
                                                                        <i class="fas fa-seedling"></i>
                                                                        <span>Chicken & Avocado Salad</span>
                                                                        <small>200g chicken + lettuce, cucumber, tomatoes + ½ avocado</small>
                                                                        <div class="meal-macros">520 kcal | 50g protein | 20g carbs | 22g fat</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-6 col-md-6 mb-3">
                                                                <button class="btn btn-outline-success w-100 lunch-meal-btn" data-meal="egg_fried_rice">
                                                                    <div class="lunch-meal">
                                                                        <i class="fas fa-egg"></i>
                                                                        <span>Egg Fried Rice (Healthy)</span>
                                                                        <small>3 eggs + 3 egg whites + 150g basmati rice + peas & carrots</small>
                                                                        <div class="meal-macros">580 kcal | 40g protein | 60g carbs | 15g fat</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-6 col-md-6 mb-3">
                                                                <button class="btn btn-outline-success w-100 lunch-meal-btn" data-meal="grilled_turkey_potato">
                                                                    <div class="lunch-meal">
                                                                        <i class="fas fa-drumstick-bite"></i>
                                                                        <span>Grilled Turkey & Potato Plate</span>
                                                                        <small>200g turkey + 200g boiled potatoes + green beans & broccoli</small>
                                                                        <div class="meal-macros">590 kcal | 52g protein | 50g carbs | 14g fat</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Quick Dinner Options -->
                                        <div class="dinner-options mb-4" id="dinnerOptions" style="display: none;">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6><i class="fas fa-moon me-2"></i>Quick Dinner Options</h6>
                                                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#dinnerCollapse" aria-expanded="true">
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                            </div>
                                            <div class="collapse show" id="dinnerCollapse">
                                                <div class="dinner-categories">
                                                    <!-- Protein Options -->
                                                    <div class="dinner-category mb-3">
                                                        <h6 class="category-title">
                                                            <i class="fas fa-drumstick-bite me-2 text-primary"></i>Protein Power
                                                            <small class="text-muted">High protein dinner options</small>
                                                        </h6>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 dinner-btn" data-food="lean_beef" data-amount="120" data-unit="g">
                                                                    <div class="dinner-item">
                                                                        <i class="fas fa-hamburger"></i>
                                                                        <span>Lean Beef</span>
                                                                        <small>120g, 200 cal, 30g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 dinner-btn" data-food="chicken_thigh" data-amount="100" data-unit="g">
                                                                    <div class="dinner-item">
                                                                        <i class="fas fa-drumstick-bite"></i>
                                                                        <span>Chicken Thigh</span>
                                                                        <small>100g, 180 cal, 20g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 dinner-btn" data-food="white_fish" data-amount="150" data-unit="g">
                                                                    <div class="dinner-item">
                                                                        <i class="fas fa-fish"></i>
                                                                        <span>White Fish</span>
                                                                        <small>150g, 150 cal, 35g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 dinner-btn" data-food="lentils" data-amount="100" data-unit="g">
                                                                    <div class="dinner-item">
                                                                        <i class="fas fa-seedling"></i>
                                                                        <span>Lentils</span>
                                                                        <small>100g cooked, 116 cal, 9g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Carb Options -->
                                                    <div class="dinner-category mb-3">
                                                        <h6 class="category-title">
                                                            <i class="fas fa-bread-slice me-2 text-success"></i>Energy Carbs
                                                            <small class="text-muted">Healthy carbohydrate sources</small>
                                                        </h6>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 dinner-btn" data-food="wild_rice" data-amount="100" data-unit="g">
                                                                    <div class="dinner-item">
                                                                        <i class="fas fa-seedling"></i>
                                                                        <span>Wild Rice</span>
                                                                        <small>100g cooked, 100 cal, 4g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 dinner-btn" data-food="roasted_potatoes" data-amount="150" data-unit="g">
                                                                    <div class="dinner-item">
                                                                        <i class="fas fa-carrot"></i>
                                                                        <span>Roasted Potatoes</span>
                                                                        <small>150g, 120 cal, 3g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 dinner-btn" data-food="whole_grain_bread" data-amount="2" data-unit="slice">
                                                                    <div class="dinner-item">
                                                                        <i class="fas fa-bread-slice"></i>
                                                                        <span>Whole Grain Bread</span>
                                                                        <small>2 slices, 160 cal, 8g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 dinner-btn" data-food="buckwheat" data-amount="80" data-unit="g">
                                                                    <div class="dinner-item">
                                                                        <i class="fas fa-seedling"></i>
                                                                        <span>Buckwheat</span>
                                                                        <small>80g cooked, 100 cal, 4g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Healthy Fats -->
                                                    <div class="dinner-category mb-3">
                                                        <h6 class="category-title">
                                                            <i class="fas fa-heart me-2 text-warning"></i>Healthy Fats
                                                            <small class="text-muted">Essential fatty acids</small>
                                                        </h6>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-warning w-100 dinner-btn" data-food="olive_oil" data-amount="1" data-unit="tbsp">
                                                                    <div class="dinner-item">
                                                                        <i class="fas fa-tint"></i>
                                                                        <span>Olive Oil</span>
                                                                        <small>1 tbsp, 120 cal, 14g fat</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-warning w-100 dinner-btn" data-food="walnuts" data-amount="30" data-unit="g">
                                                                    <div class="dinner-item">
                                                                        <i class="fas fa-nut"></i>
                                                                        <span>Walnuts</span>
                                                                        <small>30g, 200 cal, 20g fat</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-warning w-100 dinner-btn" data-food="avocado" data-amount="0.5" data-unit="medium">
                                                                    <div class="dinner-item">
                                                                        <i class="fas fa-leaf"></i>
                                                                        <span>Avocado</span>
                                                                        <small>1/2 medium, 120 cal, 11g fat</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-warning w-100 dinner-btn" data-food="flax_seeds" data-amount="15" data-unit="g">
                                                                    <div class="dinner-item">
                                                                        <i class="fas fa-seedling"></i>
                                                                        <span>Flax Seeds</span>
                                                                        <small>15g, 75 cal, 6g fat</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Complete Dinner Combos -->
                                                    <div class="dinner-category mb-3">
                                                        <h6 class="category-title">
                                                            <i class="fas fa-star me-2 text-info"></i>Complete Combos
                                                            <small class="text-muted">Balanced dinner meals</small>
                                                        </h6>
                                                        <div class="row">
                                                            <div class="col-lg-4 col-md-6 mb-2">
                                                                <button class="btn btn-outline-info w-100 dinner-combo-btn" data-combo="beef_dinner">
                                                                    <div class="dinner-combo">
                                                                        <i class="fas fa-hamburger"></i>
                                                                        <span>Beef Dinner</span>
                                                                        <small>Beef + rice + vegetables</small>
                                                                        <div class="combo-calories">~500 cal, 35g protein</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-4 col-md-6 mb-2">
                                                                <button class="btn btn-outline-info w-100 dinner-combo-btn" data-combo="fish_meal">
                                                                    <div class="dinner-combo">
                                                                        <i class="fas fa-fish"></i>
                                                                        <span>Fish Meal</span>
                                                                        <small>Fish + potatoes + greens</small>
                                                                        <div class="combo-calories">~450 cal, 30g protein</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-4 col-md-6 mb-2">
                                                                <button class="btn btn-outline-info w-100 dinner-combo-btn" data-combo="vegetarian_feast">
                                                                    <div class="dinner-combo">
                                                                        <i class="fas fa-leaf"></i>
                                                                        <span>Vegetarian Feast</span>
                                                                        <small>Lentils + rice + vegetables</small>
                                                                        <div class="combo-calories">~400 cal, 20g protein</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Quick Snacks Options -->
                                        <div class="snacks-options mb-4" id="snacksOptions" style="display: none;">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6><i class="fas fa-cookie-bite me-2"></i>Quick Snacks Options</h6>
                                                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#snacksCollapse" aria-expanded="true">
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                            </div>
                                            <div class="collapse show" id="snacksCollapse">
                                                <div class="snacks-categories">
                                                    <!-- Protein Snacks -->
                                                    <div class="snacks-category mb-3">
                                                        <h6 class="category-title">
                                                            <i class="fas fa-dumbbell me-2 text-primary"></i>Protein Snacks
                                                            <small class="text-muted">High protein snack options</small>
                                                        </h6>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 snacks-btn" data-food="greek_yogurt" data-amount="150" data-unit="g">
                                                                    <div class="snacks-item">
                                                                        <i class="fas fa-blender"></i>
                                                                        <span>Greek Yogurt</span>
                                                                        <small>150g, 90 cal, 15g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 snacks-btn" data-food="cottage_cheese" data-amount="100" data-unit="g">
                                                                    <div class="snacks-item">
                                                                        <i class="fas fa-cheese"></i>
                                                                        <span>Cottage Cheese</span>
                                                                        <small>100g, 80 cal, 12g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 snacks-btn" data-food="protein_bar" data-amount="1" data-unit="bar">
                                                                    <div class="snacks-item">
                                                                        <i class="fas fa-candy-cane"></i>
                                                                        <span>Protein Bar</span>
                                                                        <small>1 bar, 200 cal, 20g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-primary w-100 snacks-btn" data-food="hard_boiled_eggs" data-amount="2" data-unit="large">
                                                                    <div class="snacks-item">
                                                                        <i class="fas fa-egg"></i>
                                                                        <span>Hard Boiled Eggs</span>
                                                                        <small>2 eggs, 140 cal, 12g protein</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Fruit Snacks -->
                                                    <div class="snacks-category mb-3">
                                                        <h6 class="category-title">
                                                            <i class="fas fa-apple-alt me-2 text-success"></i>Fresh Fruits
                                                            <small class="text-muted">Natural fruit snacks</small>
                                                        </h6>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 snacks-btn" data-food="apple" data-amount="1" data-unit="medium">
                                                                    <div class="snacks-item">
                                                                        <i class="fas fa-apple-alt"></i>
                                                                        <span>Apple</span>
                                                                        <small>1 medium, 95 cal, 25g carbs</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 snacks-btn" data-food="banana" data-amount="1" data-unit="medium">
                                                                    <div class="snacks-item">
                                                                        <i class="fas fa-banana"></i>
                                                                        <span>Banana</span>
                                                                        <small>1 medium, 105 cal, 27g carbs</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 snacks-btn" data-food="berries" data-amount="100" data-unit="g">
                                                                    <div class="snacks-item">
                                                                        <i class="fas fa-seedling"></i>
                                                                        <span>Mixed Berries</span>
                                                                        <small>100g, 50 cal, 12g carbs</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-success w-100 snacks-btn" data-food="orange" data-amount="1" data-unit="medium">
                                                                    <div class="snacks-item">
                                                                        <i class="fas fa-lemon"></i>
                                                                        <span>Orange</span>
                                                                        <small>1 medium, 60 cal, 15g carbs</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Nut Snacks -->
                                                    <div class="snacks-category mb-3">
                                                        <h6 class="category-title">
                                                            <i class="fas fa-nut me-2 text-warning"></i>Nut Snacks
                                                            <small class="text-muted">Healthy fat snacks</small>
                                                        </h6>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-warning w-100 snacks-btn" data-food="almonds" data-amount="25" data-unit="g">
                                                                    <div class="snacks-item">
                                                                        <i class="fas fa-nut"></i>
                                                                        <span>Almonds</span>
                                                                        <small>25g, 150 cal, 13g fat</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-warning w-100 snacks-btn" data-food="walnuts" data-amount="25" data-unit="g">
                                                                    <div class="snacks-item">
                                                                        <i class="fas fa-nut"></i>
                                                                        <span>Walnuts</span>
                                                                        <small>25g, 165 cal, 16g fat</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-warning w-100 snacks-btn" data-food="cashews" data-amount="25" data-unit="g">
                                                                    <div class="snacks-item">
                                                                        <i class="fas fa-nut"></i>
                                                                        <span>Cashews</span>
                                                                        <small>25g, 140 cal, 11g fat</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <button class="btn btn-outline-warning w-100 snacks-btn" data-food="pistachios" data-amount="25" data-unit="g">
                                                                    <div class="snacks-item">
                                                                        <i class="fas fa-nut"></i>
                                                                        <span>Pistachios</span>
                                                                        <small>25g, 160 cal, 13g fat</small>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Complete Snack Combos -->
                                                    <div class="snacks-category mb-3">
                                                        <h6 class="category-title">
                                                            <i class="fas fa-star me-2 text-info"></i>Complete Combos
                                                            <small class="text-muted">Balanced snack combinations</small>
                                                        </h6>
                                                        <div class="row">
                                                            <div class="col-lg-4 col-md-6 mb-2">
                                                                <button class="btn btn-outline-info w-100 snacks-combo-btn" data-combo="protein_snack">
                                                                    <div class="snacks-combo">
                                                                        <i class="fas fa-dumbbell"></i>
                                                                        <span>Protein Snack</span>
                                                                        <small>Greek yogurt + berries</small>
                                                                        <div class="combo-calories">~150 cal, 18g protein</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-4 col-md-6 mb-2">
                                                                <button class="btn btn-outline-info w-100 snacks-combo-btn" data-combo="energy_snack">
                                                                    <div class="snacks-combo">
                                                                        <i class="fas fa-bolt"></i>
                                                                        <span>Energy Snack</span>
                                                                        <small>Apple + almonds</small>
                                                                        <div class="combo-calories">~250 cal, 6g protein</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                            <div class="col-lg-4 col-md-6 mb-2">
                                                                <button class="btn btn-outline-info w-100 snacks-combo-btn" data-combo="healthy_snack">
                                                                    <div class="snacks-combo">
                                                                        <i class="fas fa-heart"></i>
                                                                        <span>Healthy Snack</span>
                                                                        <small>Banana + walnuts</small>
                                                                        <div class="combo-calories">~270 cal, 6g protein</div>
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        
                                        
                                        <!-- Current Meal Foods -->
                                        <div id="currentMealFoods" class="meal-foods mb-3">
                                            <h6 class="mb-2"><i class="fas fa-utensils me-1"></i>Current Meal</h6>
                                            <div id="foodList" class="food-list">
                                                <!-- Added foods will appear here -->
                                            </div>
                                        </div>
                                        
                                        <!-- Daily Summary -->
                                        <div class="daily-summary">
                                            <h6 class="mb-3"><i class="fas fa-chart-pie me-1"></i>Daily Summary</h6>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="summary-card">
                                                        <div class="summary-icon bg-primary">
                                                            <i class="fas fa-fire"></i>
                                                        </div>
                                                        <div class="summary-content">
                                                            <h4 id="totalCalories">0</h4>
                                                            <small>Calories</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="summary-card">
                                                        <div class="summary-icon bg-success">
                                                            <i class="fas fa-drumstick-bite"></i>
                                                        </div>
                                                        <div class="summary-content">
                                                            <h4 id="totalProtein">0g</h4>
                                                            <small>Protein</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="summary-card">
                                                        <div class="summary-icon bg-warning">
                                                            <i class="fas fa-bread-slice"></i>
                                                        </div>
                                                        <div class="summary-content">
                                                            <h4 id="totalCarbs">0g</h4>
                                                            <small>Carbs</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="summary-card">
                                                        <div class="summary-icon bg-info">
                                                            <i class="fas fa-oil-can"></i>
                                                        </div>
                                                        <div class="summary-content">
                                                            <h4 id="totalFats">0g</h4>
                                                            <small>Fats</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-lg-4">
                                    <!-- Quick Foods -->
                                    <div class="calorie-info-card mb-4">
                                        <h6 class="mb-3"><i class="fas fa-bolt me-2"></i>Quick Add</h6>
                                        <div class="quick-foods">
                                            <button class="btn btn-outline-primary btn-sm mb-2 me-2 quick-food-btn" data-food="apple">🍎 Apple</button>
                                            <button class="btn btn-outline-primary btn-sm mb-2 me-2 quick-food-btn" data-food="banana">🍌 Banana</button>
                                            <button class="btn btn-outline-primary btn-sm mb-2 me-2 quick-food-btn" data-food="chicken breast">🍗 Chicken</button>
                                            <button class="btn btn-outline-primary btn-sm mb-2 me-2 quick-food-btn" data-food="rice">🍚 Rice</button>
                                            <button class="btn btn-outline-primary btn-sm mb-2 me-2 quick-food-btn" data-food="eggs">🥚 Eggs</button>
                                            <button class="btn btn-outline-primary btn-sm mb-2 me-2 quick-food-btn" data-food="bread">🍞 Bread</button>
                                            <button class="btn btn-outline-primary btn-sm mb-2 me-2 quick-food-btn" data-food="milk">🥛 Milk</button>
                                            <button class="btn btn-outline-primary btn-sm mb-2 me-2 quick-food-btn" data-food="yogurt">🥛 Yogurt</button>
                                        </div>
                                    </div>

                                    <!-- Daily Goals -->
                                    <div class="calorie-info-card mb-4">
                                        <h6 class="mb-3"><i class="fas fa-target me-2"></i>Daily Goals</h6>
                                        <div class="goal-progress">
                                            <div class="d-flex justify-content-between mb-1">
                                                <small>Calories</small>
                                                <small id="consumedCalories">0 / 2000</small>
                                            </div>
                                            <div class="progress mb-2" style="height: 8px;">
                                                <div class="progress-bar bg-primary" role="progressbar" id="calorieProgress" style="width: 0%"></div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between mb-1">
                                                <small>Protein</small>
                                                <small id="consumedProtein">0 / 150g</small>
                                            </div>
                                            <div class="progress mb-2" style="height: 6px;">
                                                <div class="progress-bar bg-success" role="progressbar" id="proteinProgress" style="width: 0%"></div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between mb-1">
                                                <small>Carbs</small>
                                                <small id="consumedCarbs">0 / 250g</small>
                                            </div>
                                            <div class="progress mb-2" style="height: 6px;">
                                                <div class="progress-bar bg-warning" role="progressbar" id="carbsProgress" style="width: 0%"></div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between mb-1">
                                                <small>Fats</small>
                                                <small id="consumedFats">0 / 65g</small>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-info" role="progressbar" id="fatsProgress" style="width: 0%"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Meal Breakdown -->
                                    <div class="calorie-info-card">
                                        <h6 class="mb-3"><i class="fas fa-utensils me-2"></i>Meal Breakdown</h6>
                                        <div class="meal-breakdown">
                                            <div class="meal-item">
                                                <div class="d-flex justify-content-between">
                                                    <span>Breakfast</span>
                                                    <span id="breakfastCalories">0 cal</span>
                                                </div>
                                                <div class="progress mt-1" style="height: 4px;">
                                                    <div class="progress-bar bg-warning" role="progressbar" id="breakfastProgress" style="width: 0%"></div>
                                                </div>
                                            </div>
                                            <div class="meal-item">
                                                <div class="d-flex justify-content-between">
                                                    <span>Lunch</span>
                                                    <span id="lunchCalories">0 cal</span>
                                                </div>
                                                <div class="progress mt-1" style="height: 4px;">
                                                    <div class="progress-bar bg-info" role="progressbar" id="lunchProgress" style="width: 0%"></div>
                                                </div>
                                            </div>
                                            <div class="meal-item">
                                                <div class="d-flex justify-content-between">
                                                    <span>Dinner</span>
                                                    <span id="dinnerCalories">0 cal</span>
                                                </div>
                                                <div class="progress mt-1" style="height: 4px;">
                                                    <div class="progress-bar bg-primary" role="progressbar" id="dinnerProgress" style="width: 0%"></div>
                                                </div>
                                            </div>
                                            <div class="meal-item">
                                                <div class="d-flex justify-content-between">
                                                    <span>Snacks</span>
                                                    <span id="snacksCalories">0 cal</span>
                                                </div>
                                                <div class="progress mt-1" style="height: 4px;">
                                                    <div class="progress-bar bg-secondary" role="progressbar" id="snacksProgress" style="width: 0%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BMR & Meal Plans Tab -->
                        <style>
                            /* Lighten BMR calculator cards */
                            #bmrContent .chart-container {
                                background: #ffffff !important;
                                border: 1px solid rgba(0,0,0,0.06) !important;
                                box-shadow: 0 6px 20px rgba(0,0,0,0.04) !important;
                            }
                            #bmrContent .chart-title {
                                color: #374151 !important;
                            }
                            #bmrContent .bmr-explanation {
                                background: #f8fafc !important;
                                border: 1px solid #eef2f7 !important;
                                color: #4b5563 !important;
                                border-radius: 10px;
                                padding: 0.75rem 1rem;
                            }
                            #bmrContent .bmr-card,
                            #bmrContent .bmr-report-card,
                            #bmrContent .bmr-insights-card,
                            #bmrContent .bmr-chart-card {
                                background: #ffffff !important;
                                border: 1px solid #eef2f7 !important;
                                box-shadow: 0 4px 14px rgba(0,0,0,0.03) !important;
                            }
                            #bmrContent .bmr-icon,
                            #bmrContent .report-icon {
                                filter: brightness(1.05) saturate(1.02);
                            }
                            #bmrContent .bmr-placeholder {
                                background: #f9fbfd !important;
                                border: 1px dashed #e5edf6 !important;
                                border-radius: 12px;
                                padding: 2rem 1rem;
                            }
                            
                            /* Professional Metabolic Profile Styles */
                            .profile-badge .badge {
                                font-size: 0.75rem;
                                font-weight: 600;
                                letter-spacing: 0.5px;
                                text-transform: uppercase;
                                padding: 0.4rem 0.8rem;
                                border-radius: 20px;
                            }
                            
                            .metric-card {
                                background: #fff;
                                border-radius: 16px;
                                padding: 1.5rem 1rem;
                                text-align: center;
                                box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                                border: 1px solid #f0f4f8;
                                transition: all 0.3s ease;
                                position: relative;
                                overflow: hidden;
                            }
                            
                            .metric-card::before {
                                content: '';
                                position: absolute;
                                top: 0;
                                left: 0;
                                right: 0;
                                height: 4px;
                                background: linear-gradient(90deg, var(--metric-color), var(--metric-color-light));
                            }
                            
                            .metric-card.primary {
                                --metric-color: #4f46e5;
                                --metric-color-light: #818cf8;
                            }
                            
                            .metric-card.success {
                                --metric-color: #059669;
                                --metric-color-light: #10b981;
                            }
                            
                            .metric-card.warning {
                                --metric-color: #d97706;
                                --metric-color-light: #f59e0b;
                            }
                            
                            .metric-card:hover {
                                transform: translateY(-2px);
                                box-shadow: 0 8px 30px rgba(0,0,0,0.12);
                            }
                            
                            .metric-icon {
                                width: 50px;
                                height: 50px;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                margin: 0 auto 1rem;
                                font-size: 1.2rem;
                                color: white;
                                background: linear-gradient(135deg, var(--metric-color), var(--metric-color-light));
                            }
                            
                            .metric-content h3 {
                                font-size: 1.8rem;
                                font-weight: 700;
                                margin-bottom: 0.25rem;
                                color: #1f2937;
                            }
                            
                            .metric-content p {
                                font-size: 0.9rem;
                                font-weight: 600;
                                margin-bottom: 0.25rem;
                                color: #374151;
                            }
                            
                            .metric-content small {
                                font-size: 0.75rem;
                                color: #6b7280;
                                font-weight: 500;
                            }
                            
                            .goal-description-card {
                                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                                border: 1px solid #e2e8f0;
                                border-radius: 12px;
                                padding: 1.25rem;
                            }
                            
                            .goal-header {
                                display: flex;
                                align-items: center;
                                margin-bottom: 0.75rem;
                                color: #475569;
                                font-size: 0.9rem;
                            }
                            
                            .goal-text {
                                color: #64748b;
                                font-size: 0.9rem;
                                line-height: 1.5;
                            }
                            
                            .macro-targets-card {
                                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                                border: 1px solid #e2e8f0;
                                border-radius: 12px;
                                padding: 1.25rem;
                                box-shadow: 0 2px 8px rgba(148, 163, 184, 0.1);
                            }
                            
                            .macro-header {
                                display: flex;
                                align-items: center;
                                margin-bottom: 1rem;
                                color: #64748b;
                                font-size: 0.9rem;
                            }
                            
                            .macro-item {
                                display: flex;
                                align-items: center;
                                padding: 0.75rem;
                                border-radius: 8px;
                                background: rgba(255, 255, 255, 0.7);
                                border: 1px solid rgba(226, 232, 240, 0.5);
                                backdrop-filter: blur(10px);
                                transition: all 0.3s ease;
                            }
                            
                            .macro-item:hover {
                                background: rgba(255, 255, 255, 0.9);
                                transform: translateY(-1px);
                                box-shadow: 0 4px 12px rgba(148, 163, 184, 0.15);
                            }
                            
                            .macro-item.protein {
                                border-left: 3px solid #fca5a5;
                            }
                            
                            .macro-item.carbs {
                                border-left: 3px solid #93c5fd;
                            }
                            
                            .macro-item.fats {
                                border-left: 3px solid #fcd34d;
                            }
                            
                            .macro-icon {
                                width: 32px;
                                height: 32px;
                                border-radius: 6px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                margin-right: 0.75rem;
                                font-size: 0.9rem;
                            }
                            
                            .macro-item.protein .macro-icon {
                                background: rgba(255, 255, 255, 0.9);
                                color: #f87171;
                            }
                            
                            .macro-item.carbs .macro-icon {
                                background: rgba(255, 255, 255, 0.9);
                                color: #60a5fa;
                            }
                            
                            .macro-item.fats .macro-icon {
                                background: rgba(255, 255, 255, 0.9);
                                color: #fbbf24;
                            }
                            
                            .macro-info {
                                display: flex;
                                flex-direction: column;
                            }
                            
                            .macro-value {
                                font-weight: 600;
                                font-size: 0.9rem;
                                color: #475569;
                            }
                            
                            .macro-label {
                                font-size: 0.75rem;
                                color: #94a3b8;
                                font-weight: 500;
                            }
                            
                            /* BMR Analysis Cards */
                            .bmr-analysis-card {
                                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                                border: 1px solid #e2e8f0;
                                border-radius: 12px;
                                padding: 1.5rem;
                                box-shadow: 0 2px 8px rgba(148, 163, 184, 0.1);
                                transition: all 0.3s ease;
                            }
                            
                            .bmr-analysis-card:hover {
                                transform: translateY(-2px);
                                box-shadow: 0 4px 16px rgba(148, 163, 184, 0.15);
                            }
                            
                            .bmr-analysis-card h6 {
                                color: #475569;
                                font-weight: 600;
                                margin-bottom: 1.25rem;
                                font-size: 1rem;
                                display: flex;
                                align-items: center;
                            }
                            
                            .bmr-analysis-card h6 i {
                                color: #64748b;
                                margin-right: 0.5rem;
                            }
                            
                            .analysis-content {
                                display: flex;
                                flex-direction: column;
                                gap: 0.75rem;
                                min-height: 200px;
                                opacity: 1;
                                transition: opacity 0.3s ease;
                            }
                            
                            .analysis-content.loading {
                                opacity: 0.6;
                            }
                            
                            .analysis-content.loaded {
                                opacity: 1;
                            }
                            
                            .analysis-item {
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                padding: 0.75rem;
                                background: rgba(255, 255, 255, 0.6);
                                border-radius: 8px;
                                border: 1px solid rgba(226, 232, 240, 0.5);
                                transition: all 0.2s ease;
                                min-height: 48px;
                                opacity: 1;
                            }
                            
                            .analysis-item.loading {
                                opacity: 0.5;
                                background: rgba(248, 250, 252, 0.8);
                            }
                            
                            .analysis-item.loaded {
                                opacity: 1;
                                background: rgba(255, 255, 255, 0.6);
                            }
                            
                            .analysis-item:hover {
                                background: rgba(255, 255, 255, 0.8);
                                border-color: rgba(148, 163, 184, 0.3);
                            }
                            
                            .analysis-label {
                                font-size: 0.875rem;
                                color: #64748b;
                                font-weight: 500;
                            }
                            
                            .analysis-value {
                                font-size: 0.875rem;
                                color: #334155;
                                font-weight: 600;
                                text-align: right;
                                max-width: 60%;
                                word-wrap: break-word;
                            }
                            
                            /* Color coding for different analysis values */
                            .analysis-value.text-success {
                                color: #059669 !important;
                            }
                            
                            .analysis-value.text-warning {
                                color: #d97706 !important;
                            }
                            
                            .analysis-value.text-danger {
                                color: #dc2626 !important;
                            }
                            
                            .analysis-value.text-info {
                                color: #0891b2 !important;
                            }
                            
                            /* Enhanced Placeholder Styles */
                            .placeholder-content {
                                text-align: center;
                                padding: 2rem 1rem;
                            }
                            
                            .placeholder-icon {
                                width: 80px;
                                height: 80px;
                                border-radius: 50%;
                                background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                margin: 0 auto 1.5rem;
                                font-size: 2rem;
                                color: #4f46e5;
                            }
                            
                            .placeholder-content h6 {
                                font-weight: 600;
                                color: #374151;
                                margin-bottom: 0.75rem;
                            }
                            
                            .placeholder-features {
                                margin-top: 1.5rem;
                                text-align: left;
                                max-width: 280px;
                                margin-left: auto;
                                margin-right: auto;
                            }
                            
                            .feature-item {
                                display: flex;
                                align-items: center;
                                margin-bottom: 0.5rem;
                                font-size: 0.85rem;
                                color: #4b5563;
                            }
                            
                            .feature-item i {
                                margin-right: 0.5rem;
                                font-size: 0.8rem;
                            }
                            
                            /* Professional Form and Alert Styles */
                            .bmr-explanation .alert {
                                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                                border-left: 4px solid #3b82f6;
                                border-radius: 8px;
                                padding: 1rem 1.25rem;
                            }
                            
                            .bmr-explanation .alert strong {
                                color: #1e40af;
                                font-weight: 600;
                            }
                            
                            #bmrForm .form-label {
                                font-weight: 600;
                                color: #374151;
                                margin-bottom: 0.5rem;
                            }
                            
                            #bmrForm .form-control {
                                border: 1px solid #d1d5db;
                                border-radius: 8px;
                                padding: 0.75rem 1rem;
                                font-size: 0.9rem;
                                transition: all 0.2s ease;
                            }
                            
                            #bmrForm .form-control:focus {
                                border-color: #3b82f6;
                                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
                            }
                            
                            #bmrForm .form-select {
                                border: 1px solid #d1d5db;
                                border-radius: 8px;
                                padding: 0.75rem 1rem;
                                font-size: 0.9rem;
                                transition: all 0.2s ease;
                            }
                            
                            #bmrForm .form-select:focus {
                                border-color: #3b82f6;
                                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
                            }
                            
                            #bmrForm .btn-primary {
                                background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
                                border: none;
                                border-radius: 8px;
                                padding: 0.75rem 2rem;
                                font-weight: 600;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                                font-size: 0.85rem;
                                transition: all 0.3s ease;
                            }
                            
                            #bmrForm .btn-primary:hover {
                                transform: translateY(-1px);
                                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
                            }
                            
                            /* Light Theme for Personalized Recommendations */
                            .bmr-recommendations-card {
                                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                                border: 1px solid #e2e8f0;
                                border-radius: 16px;
                                padding: 2rem;
                                box-shadow: 0 4px 20px rgba(0,0,0,0.05);
                            }
                            
                            .bmr-recommendations-card h6 {
                                color: #475569;
                                font-weight: 600;
                                font-size: 1.1rem;
                                margin-bottom: 1.5rem;
                                display: flex;
                                align-items: center;
                            }
                            
                            .bmr-recommendations-card h6 i {
                                color: #f59e0b;
                                margin-right: 0.5rem;
                            }
                            
                            .recommendations-content {
                                display: grid;
                                gap: 1.5rem;
                            }
                            
                            .recommendation-category {
                                background: #ffffff;
                                border: 1px solid #f1f5f9;
                                border-radius: 12px;
                                padding: 1.5rem;
                                box-shadow: 0 2px 8px rgba(0,0,0,0.04);
                                transition: all 0.3s ease;
                            }
                            
                            .recommendation-category:hover {
                                transform: translateY(-2px);
                                box-shadow: 0 4px 16px rgba(0,0,0,0.08);
                                border-color: #e2e8f0;
                            }
                            
                            .recommendation-category .category-title {
                                color: #334155;
                                font-weight: 600;
                                font-size: 1rem;
                                margin-bottom: 1rem;
                                padding-bottom: 0.75rem;
                                border-bottom: 2px solid #f1f5f9;
                                display: flex;
                                align-items: center;
                            }
                            
                            .recommendation-category .category-title::before {
                                content: '';
                                width: 4px;
                                height: 20px;
                                background: linear-gradient(135deg, #3b82f6, #8b5cf6);
                                border-radius: 2px;
                                margin-right: 0.75rem;
                            }
                            
                            .recommendation-list {
                                list-style: none;
                                padding: 0;
                                margin: 0;
                            }
                            
                            .recommendation-list li {
                                color: #64748b;
                                font-size: 0.9rem;
                                line-height: 1.6;
                                padding: 0.5rem 0;
                                padding-left: 1.5rem;
                                position: relative;
                                border-bottom: 1px solid #f8fafc;
                            }
                            
                            .recommendation-list li:last-child {
                                border-bottom: none;
                            }
                            
                            .recommendation-list li::before {
                                content: '✓';
                                position: absolute;
                                left: 0;
                                top: 0.5rem;
                                width: 16px;
                                height: 16px;
                                background: linear-gradient(135deg, #10b981, #059669);
                                color: white;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-size: 0.7rem;
                                font-weight: bold;
                            }
                            
                            .recommendation-list li:hover {
                                color: #475569;
                                transform: translateX(4px);
                                transition: all 0.2s ease;
                            }
                            
                            /* Light Theme for Insights Section */
                            .bmr-insights-card {
                                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                                border: 1px solid #e2e8f0;
                                border-radius: 16px;
                                padding: 2rem;
                                box-shadow: 0 4px 20px rgba(0,0,0,0.05);
                            }
                            
                            .bmr-insights-card h6 {
                                color: #475569;
                                font-weight: 600;
                                font-size: 1.1rem;
                                margin-bottom: 1.5rem;
                                display: flex;
                                align-items: center;
                            }
                            
                            .bmr-insights-card h6 i {
                                color: #3b82f6;
                                margin-right: 0.5rem;
                            }
                            
                            .insights-content {
                                display: flex;
                                flex-direction: column;
                                gap: 1rem;
                            }
                            
                            .insight-item {
                                background: #ffffff;
                                border: 1px solid #f1f5f9;
                                border-radius: 12px;
                                padding: 1.25rem;
                                display: flex;
                                align-items: flex-start;
                                gap: 1rem;
                                box-shadow: 0 2px 8px rgba(0,0,0,0.04);
                                transition: all 0.3s ease;
                            }
                            
                            .insight-item:hover {
                                transform: translateY(-2px);
                                box-shadow: 0 4px 16px rgba(0,0,0,0.08);
                                border-color: #e2e8f0;
                            }
                            
                            .insight-icon {
                                width: 40px;
                                height: 40px;
                                border-radius: 10px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-size: 1rem;
                                flex-shrink: 0;
                            }
                            
                            .insight-item:nth-child(1) .insight-icon {
                                background: linear-gradient(135deg, #fef3c7, #fde68a);
                                color: #d97706;
                            }
                            
                            .insight-item:nth-child(2) .insight-icon {
                                background: linear-gradient(135deg, #d1fae5, #a7f3d0);
                                color: #059669;
                            }
                            
                            .insight-item:nth-child(3) .insight-icon {
                                background: linear-gradient(135deg, #dbeafe, #bfdbfe);
                                color: #2563eb;
                            }
                            
                            .insight-text {
                                flex: 1;
                            }
                            
                            .insight-text strong {
                                color: #334155;
                                font-weight: 600;
                                font-size: 0.95rem;
                                display: block;
                                margin-bottom: 0.5rem;
                            }
                            
                            .insight-text p {
                                color: #64748b;
                                font-size: 0.85rem;
                                line-height: 1.5;
                                margin: 0;
                            }
                            
                            /* Override any conflicting insight styles with light theme */
                            #bmrContent .insight-item {
                                background: #ffffff !important;
                                border: 1px solid #f1f5f9 !important;
                                border-radius: 12px !important;
                                padding: 1.25rem !important;
                                display: flex !important;
                                align-items: flex-start !important;
                                gap: 1rem !important;
                                box-shadow: 0 2px 8px rgba(0,0,0,0.04) !important;
                                transition: all 0.3s ease !important;
                                margin-bottom: 0 !important;
                            }
                            
                            #bmrContent .insight-item:hover {
                                transform: translateY(-2px) !important;
                                box-shadow: 0 4px 16px rgba(0,0,0,0.08) !important;
                                border-color: #e2e8f0 !important;
                                background: #ffffff !important;
                            }
                            
                            #bmrContent .insight-item:last-child {
                                margin-bottom: 0 !important;
                            }
                        </style>
                        <div id="bmrContent" class="tab-content" style="display: none;">
                            <!-- BMR Calculator Section -->
                            <div class="row mb-4">
                                <div class="col-lg-6 mb-4">
                                    <div class="chart-container">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h5 class="chart-title mb-1" id="bmr">BMR Calculator</h5>
                                                <p class="text-muted small mb-0">
                                                    <i class="fas fa-calculator me-1"></i>
                                                    Calculate your Basal Metabolic Rate using the scientifically validated Mifflin-St Jeor equation
                                                </p>
                                            </div>
                                            <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#bmrInfoModal">
                                                <i class="fas fa-info-circle me-1"></i>What is BMR?
                                            </button>
                                        </div>
                                        <div class="bmr-explanation mb-3">
                                            <div class="alert alert-light border-0 mb-0">
                                                <div class="d-flex align-items-start">
                                                    <i class="fas fa-lightbulb text-warning me-2 mt-1"></i>
                                                    <div>
                                                        <strong>Professional Calculation:</strong> BMR (Basal Metabolic Rate) represents the minimum calories your body needs for basic functions at rest. Our calculator uses the gold-standard Mifflin-St Jeor equation, considered the most accurate method for BMR calculation.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <form id="bmrForm">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="age" class="form-label">Age</label>
                                                    <input type="number" class="form-control" id="age" min="15" max="100" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="gender" class="form-label">Gender</label>
                                                    <select class="form-select" id="gender" required>
                                                        <option value="">Select Gender</option>
                                                        <option value="male">Male</option>
                                                        <option value="female">Female</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="weight" class="form-label">Weight (kg)</label>
                                                    <input type="number" class="form-control" id="weight" step="0.1" min="30" max="300" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="height" class="form-label">Height (cm)</label>
                                                    <input type="number" class="form-control" id="height" min="100" max="250" required>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="activityLevel" class="form-label">Activity Level</label>
                                                <select class="form-select" id="activityLevel" required>
                                                    <option value="">Select Activity Level</option>
                                                    <option value="1.2">Sedentary (little/no exercise)</option>
                                                    <option value="1.375">Lightly Active (light exercise 1-3 days/week)</option>
                                                    <option value="1.55">Moderately Active (moderate exercise 3-5 days/week)</option>
                                                    <option value="1.725">Very Active (hard exercise 6-7 days/week)</option>
                                                    <option value="1.9">Extra Active (very hard exercise & physical job)</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="fitnessGoal" class="form-label">Fitness Goal</label>
                                                <select class="form-select" id="fitnessGoal" required>
                                                    <option value="">Select Goal</option>
                                                    <option value="cutting">Cutting (Weight Loss) - 500 cal deficit</option>
                                                    <option value="maintenance">Maintenance - Maintain weight</option>
                                                    <option value="bulking">Bulking (Weight Gain) - 300 cal surplus</option>
                                                </select>
                                                <div class="form-text">
                                                    <small class="text-muted">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        Cutting: 1lb/week loss | Bulking: Lean muscle gain | Maintenance: Current weight
                                                    </small>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="fas fa-calculator me-2"></i>Calculate BMR & TDEE
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="chart-container">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="chart-title mb-0">Metabolic Profile</h5>
                                            <div class="profile-badge">
                                                <span class="badge bg-gradient-primary">Professional Analysis</span>
                                            </div>
                                        </div>
                                        <div class="bmr-results" id="bmrResults" style="display: none;">
                                            <!-- Primary Metrics Row -->
                                            <div class="row g-3 mb-4">
                                                <div class="col-4">
                                                    <div class="metric-card primary">
                                                        <div class="metric-icon">
                                                            <i class="fas fa-fire"></i>
                                                        </div>
                                                        <div class="metric-content">
                                                            <h3 id="bmrValue">0</h3>
                                                            <p>BMR</p>
                                                            <small>Basal Metabolic Rate</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="metric-card success">
                                                        <div class="metric-icon">
                                                            <i class="fas fa-running"></i>
                                                        </div>
                                                        <div class="metric-content">
                                                            <h3 id="tdeeValue">0</h3>
                                                            <p>TDEE</p>
                                                            <small>Total Daily Energy Expenditure</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="metric-card warning">
                                                        <div class="metric-icon">
                                                            <i class="fas fa-target"></i>
                                                        </div>
                                                        <div class="metric-content">
                                                            <h3 id="goalCalories">0</h3>
                                                            <p>Goal</p>
                                                            <small>Target Calories</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Goal Description -->
                                            <div class="goal-description-card mb-3">
                                                <div class="goal-header">
                                                    <i class="fas fa-bullseye me-2"></i>
                                                    <strong>Fitness Goal</strong>
                                                </div>
                                                <p id="goalDescription" class="goal-text mb-0">Calculate your BMR to see your personalized goal</p>
                                            </div>
                                            
                                            <!-- Macro Targets -->
                                            <div class="macro-targets-card">
                                                <div class="macro-header">
                                                    <i class="fas fa-chart-pie me-2"></i>
                                                    <strong>Macro Targets</strong>
                                                </div>
                                                <div class="row g-2">
                                                    <div class="col-4">
                                                        <div class="macro-item protein">
                                                            <div class="macro-icon">
                                                                <i class="fas fa-dumbbell"></i>
                                                            </div>
                                                            <div class="macro-info">
                                                                <span class="macro-value" id="targetProtein">0g</span>
                                                                <span class="macro-label">Protein</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="macro-item carbs">
                                                            <div class="macro-icon">
                                                                <i class="fas fa-bread-slice"></i>
                                                            </div>
                                                            <div class="macro-info">
                                                                <span class="macro-value" id="targetCarbs">0g</span>
                                                                <span class="macro-label">Carbs</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="macro-item fats">
                                                            <div class="macro-icon">
                                                                <i class="fas fa-seedling"></i>
                                                            </div>
                                                            <div class="macro-info">
                                                                <span class="macro-value" id="targetFats">0g</span>
                                                                <span class="macro-label">Fats</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="bmr-placeholder" id="bmrPlaceholder">
                                            <div class="placeholder-content">
                                                <div class="placeholder-icon">
                                                    <i class="fas fa-chart-line"></i>
                                                </div>
                                                <h6>Professional Metabolic Analysis</h6>
                                                <p class="text-muted">Complete the BMR calculator to unlock your personalized metabolic profile with detailed insights and recommendations.</p>
                                                <div class="placeholder-features">
                                                    <div class="feature-item">
                                                        <i class="fas fa-check text-success"></i>
                                                        <span>Precise BMR & TDEE calculations</span>
                                                    </div>
                                                    <div class="feature-item">
                                                        <i class="fas fa-check text-success"></i>
                                                        <span>Personalized macro targets</span>
                                                    </div>
                                                    <div class="feature-item">
                                                        <i class="fas fa-check text-success"></i>
                                                        <span>Professional recommendations</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- BMR Report Section -->
                            <div class="row mb-4" id="bmrReportSection" style="display: none;">
                                <div class="col-12">
                                    <div class="chart-container">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <div>
                                                <h5 class="chart-title mb-1">Comprehensive Metabolic Analysis</h5>
                                                <p class="text-muted small mb-0">
                                                    <i class="fas fa-chart-line me-1"></i>
                                                    Professional assessment of your metabolic profile and nutritional requirements
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <!-- BMR Overview -->
                                        <div class="row mb-4">
                                            <div class="col-lg-3 col-md-6 mb-3">
                                                <div class="bmr-report-card">
                                                    <div class="report-icon bg-primary">
                                                        <i class="fas fa-fire"></i>
                                                    </div>
                                                    <div class="report-content">
                                                        <h4 id="reportBmrValue">0</h4>
                                                        <p>BMR (Calories)</p>
                                                        <small class="text-muted">Basal Metabolic Rate</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 mb-3">
                                                <div class="bmr-report-card">
                                                    <div class="report-icon bg-success">
                                                        <i class="fas fa-running"></i>
                                                    </div>
                                                    <div class="report-content">
                                                        <h4 id="reportTdeeValue">0</h4>
                                                        <p>TDEE (Calories)</p>
                                                        <small class="text-muted">Total Daily Energy Expenditure</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 mb-3">
                                                <div class="bmr-report-card">
                                                    <div class="report-icon bg-warning">
                                                        <i class="fas fa-bullseye"></i>
                                                    </div>
                                                    <div class="report-content">
                                                        <h4 id="reportGoalCalories">0</h4>
                                                        <p>Goal Calories</p>
                                                        <small class="text-muted" id="reportGoalType">Maintenance</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 mb-3">
                                                <div class="bmr-report-card">
                                                    <div class="report-icon bg-info">
                                                        <i class="fas fa-chart-line"></i>
                                                    </div>
                                                    <div class="report-content">
                                                        <h4 id="reportCalorieDifference">0</h4>
                                                        <p>Calorie Difference</p>
                                                        <small class="text-muted" id="reportDifferenceType">vs TDEE</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Detailed Analysis -->
                                        <div class="row mb-4">
                                            <div class="col-lg-6 mb-4">
                                                <div class="bmr-analysis-card">
                                                    <h6><i class="fas fa-chart-pie me-2"></i>Metabolic Analysis</h6>
                                                    <div class="analysis-content">
                                                        <div class="analysis-item">
                                                            <span class="analysis-label">Metabolic Category:</span>
                                                            <span class="analysis-value" id="metabolicCategory">-</span>
                                                        </div>
                                                        <div class="analysis-item">
                                                            <span class="analysis-label">Activity Level:</span>
                                                            <span class="analysis-value" id="activityLevelText">-</span>
                                                        </div>
                                                        <div class="analysis-item">
                                                            <span class="analysis-label">BMI Status:</span>
                                                            <span class="analysis-value" id="bmiStatus">-</span>
                                                        </div>
                                                        <div class="analysis-item">
                                                            <span class="analysis-label">Body Composition:</span>
                                                            <span class="analysis-value" id="bodyComposition">-</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 mb-4">
                                                <div class="bmr-analysis-card">
                                                    <h6><i class="fas fa-target me-2"></i>Goal Analysis</h6>
                                                    <div class="analysis-content">
                                                        <div class="analysis-item">
                                                            <span class="analysis-label">Weight Change Rate:</span>
                                                            <span class="analysis-value" id="weightChangeRate">-</span>
                                                        </div>
                                                        <div class="analysis-item">
                                                            <span class="analysis-label">Time to Goal:</span>
                                                            <span class="analysis-value" id="timeToGoal">-</span>
                                                        </div>
                                                        <div class="analysis-item">
                                                            <span class="analysis-label">Macro Distribution:</span>
                                                            <span class="analysis-value" id="macroDistribution">-</span>
                                                        </div>
                                                        <div class="analysis-item">
                                                            <span class="analysis-label">Recommended Approach:</span>
                                                            <span class="analysis-value" id="recommendedApproach">-</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Recommendations & Insights -->
                                        <div class="row mb-4">
                                            <div class="col-lg-8 mb-4">
                                                <div class="bmr-recommendations-card">
                                                    <h6><i class="fas fa-lightbulb me-2"></i>Personalized Recommendations</h6>
                                                    <div class="recommendations-content">
                                                        <div class="recommendation-category">
                                                            <h6 class="category-title">Nutrition Strategy</h6>
                                                            <ul class="recommendation-list" id="nutritionRecommendations">
                                                                <li>Focus on whole, unprocessed foods</li>
                                                                <li>Maintain consistent meal timing</li>
                                                                <li>Stay hydrated throughout the day</li>
                                                            </ul>
                                                        </div>
                                                        <div class="recommendation-category">
                                                            <h6 class="category-title">Exercise Recommendations</h6>
                                                            <ul class="recommendation-list" id="exerciseRecommendations">
                                                                <li>Include both cardio and strength training</li>
                                                                <li>Aim for 150+ minutes of moderate activity weekly</li>
                                                                <li>Prioritize recovery and sleep</li>
                                                            </ul>
                                                        </div>
                                                        <div class="recommendation-category">
                                                            <h6 class="category-title">Lifestyle Tips</h6>
                                                            <ul class="recommendation-list" id="lifestyleRecommendations">
                                                                <li>Get 7-9 hours of quality sleep</li>
                                                                <li>Manage stress through relaxation techniques</li>
                                                                <li>Track progress consistently</li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 mb-4">
                                                <div class="bmr-insights-card">
                                                    <h6><i class="fas fa-chart-bar me-2"></i>Key Insights</h6>
                                                    <div class="insights-content">
                                                        <div class="insight-item">
                                                            <div class="insight-icon">
                                                                <i class="fas fa-fire text-warning"></i>
                                                            </div>
                                                            <div class="insight-text">
                                                                <strong>Metabolic Rate</strong>
                                                                <p id="metabolicInsight">Your BMR represents your baseline calorie needs</p>
                                                            </div>
                                                        </div>
                                                        <div class="insight-item">
                                                            <div class="insight-icon">
                                                                <i class="fas fa-balance-scale text-success"></i>
                                                            </div>
                                                            <div class="insight-text">
                                                                <strong>Energy Balance</strong>
                                                                <p id="energyInsight">Maintaining proper energy balance is key to your goals</p>
                                                            </div>
                                                        </div>
                                                        <div class="insight-item">
                                                            <div class="insight-icon">
                                                                <i class="fas fa-clock text-info"></i>
                                                            </div>
                                                            <div class="insight-text">
                                                                <strong>Timeline</strong>
                                                                <p id="timelineInsight">Consistent adherence will yield optimal results</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <!-- Personalized Meal Plans -->
                            <div class="row mb-4" id="mealPlansSection" style="display: none;">
                                <div class="col-12">
                                    <div class="chart-container">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="chart-title mb-0">Personalized Meal Plans</h5>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#mealTimingModal">
                                                    <i class="fas fa-clock me-1"></i>Meal Timing
                                                </button>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary active" id="breakfastPlanBtn">Breakfast</button>
                                                    <button type="button" class="btn btn-outline-primary" id="lunchPlanBtn">Lunch</button>
                                                    <button type="button" class="btn btn-outline-primary" id="dinnerPlanBtn">Dinner</button>
                                                    <button type="button" class="btn btn-outline-primary" id="snacksPlanBtn">Snacks</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="meal-plan-info mb-3">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="info-card">
                                                        <i class="fas fa-utensils text-primary"></i>
                                                        <h6>Meal Distribution</h6>
                                                        <p class="small text-muted">Breakfast: 25% | Lunch: 35% | Dinner: 30% | Snacks: 10%</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="info-card">
                                                        <i class="fas fa-clock text-success"></i>
                                                        <h6>Meal Timing</h6>
                                                        <p class="small text-muted">Eat every 3-4 hours for optimal metabolism</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="info-card">
                                                        <i class="fas fa-balance-scale text-warning"></i>
                                                        <h6>Macro Balance</h6>
                                                        <p class="small text-muted" id="macroBalanceInfo">Balanced macros for your goal</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="meal-plan-content">
                                            <div id="breakfastPlan" class="meal-plan-tab">
                                                <div class="row" id="breakfastMeals"></div>
                                            </div>
                                            <div id="lunchPlan" class="meal-plan-tab" style="display: none;">
                                                <div class="row" id="lunchMeals"></div>
                                            </div>
                                            <div id="dinnerPlan" class="meal-plan-tab" style="display: none;">
                                                <div class="row" id="dinnerMeals"></div>
                                            </div>
                                            <div id="snacksPlan" class="meal-plan-tab" style="display: none;">
                                                <div class="row" id="snacksMeals"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Tracking -->
                            <div class="row mb-4" id="progressSection" style="display: none;">
                                <div class="col-lg-8 mb-4">
                                    <div class="chart-container">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="chart-title mb-0">Goal Progress Tracking</h5>
                                            <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#progressModal">
                                                <i class="fas fa-chart-line me-1"></i>View Details
                                            </button>
                                        </div>
                                        <div class="progress-tracking">
                                            <div class="progress-item">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span><i class="fas fa-weight me-2"></i>Weight Progress</span>
                                                    <span id="weightProgressText">0 / 0 kg</span>
                                                </div>
                                                <div class="progress mb-3" style="height: 12px;">
                                                    <div class="progress-bar bg-primary" id="weightProgressBar" style="width: 0%"></div>
                                                </div>
                                            </div>
                                            <div class="progress-item">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span><i class="fas fa-fire me-2"></i>Calorie Deficit/Surplus</span>
                                                    <span id="calorieProgressText">0 cal</span>
                                                </div>
                                                <div class="progress mb-3" style="height: 12px;">
                                                    <div class="progress-bar bg-success" id="calorieProgressBar" style="width: 0%"></div>
                                                </div>
                                            </div>
                                            <div class="progress-item">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span><i class="fas fa-calendar-week me-2"></i>Weekly Goal Achievement</span>
                                                    <span id="weeklyProgressText">0%</span>
                                                </div>
                                                <div class="progress mb-3" style="height: 12px;">
                                                    <div class="progress-bar bg-warning" id="weeklyProgressBar" style="width: 0%"></div>
                                                </div>
                                            </div>
                                            <div class="progress-item">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span><i class="fas fa-drumstick-bite me-2"></i>Protein Intake</span>
                                                    <span id="proteinProgressText">0 / 0g</span>
                                                </div>
                                                <div class="progress mb-3" style="height: 12px;">
                                                    <div class="progress-bar bg-info" id="proteinProgressBar" style="width: 0%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>

                            <!-- Nutritional Tips Section -->
                            <div class="row mb-4" id="tipsSection" style="display: none;">
                                <div class="col-12">
                                    <div class="chart-container">
                                        <h5 class="chart-title">Nutritional Tips & Recommendations</h5>
                                        <div class="tips-content">
                                            <div class="row">
                                                <div class="col-lg-4 mb-3">
                                                    <div class="tip-card">
                                                        <div class="tip-icon bg-primary">
                                                            <i class="fas fa-apple-alt"></i>
                                                        </div>
                                                        <h6>Food Choices</h6>
                                                        <p id="foodTips">Choose nutrient-dense foods for your goal</p>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4 mb-3">
                                                    <div class="tip-card">
                                                        <div class="tip-icon bg-success">
                                                            <i class="fas fa-clock"></i>
                                                        </div>
                                                        <h6>Meal Timing</h6>
                                                        <p id="timingTips">Optimize your meal timing for better results</p>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4 mb-3">
                                                    <div class="tip-card">
                                                        <div class="tip-icon bg-warning">
                                                            <i class="fas fa-pills"></i>
                                                        </div>
                                                        <h6>Supplements</h6>
                                                        <p id="supplementTips">Consider these supplements for your goal</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Calorie Calculator Section -->
                            <div class="row mb-4" id="calorieCalculatorSection" style="display: none;">
                                <div class="col-12">
                                    <div class="chart-container">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="chart-title mb-0">Advanced Calorie Calculator</h5>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-primary active" id="singleFoodBtn">Single Food</button>
                                                <button type="button" class="btn btn-outline-primary" id="recipeBtn">Recipe</button>
                                                <button type="button" class="btn btn-outline-primary" id="comparisonBtn">Compare Foods</button>
                                            </div>
                                        </div>
                                        
                                        <!-- Single Food Calculator -->
                                        <div id="singleFoodCalc" class="calorie-calc-tab">
                                            <div class="row">
                                                <div class="col-lg-6 mb-4">
                                                    <div class="calc-form">
                                                        <h6><i class="fas fa-search me-2"></i>Search Food</h6>
                                                        <div class="input-group mb-3">
                                                            <input type="text" class="form-control" id="foodSearchCalc" placeholder="Search for food item...">
                                                            <button class="btn btn-outline-secondary" type="button" id="searchFoodBtn">
                                                                <i class="fas fa-search"></i>
                                                            </button>
                                                        </div>
                                                        <div id="foodSuggestionsCalc" class="suggestions-dropdown"></div>
                                                        
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label for="portionSizeCalc" class="form-label">Portion Size</label>
                                                                <input type="number" class="form-control" id="portionSizeCalc" step="0.1" min="0.1" placeholder="100">
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label for="portionUnitCalc" class="form-label">Unit</label>
                                                                <select class="form-select" id="portionUnitCalc">
                                                                    <option value="g">Grams (g)</option>
                                                                    <option value="ml">Milliliters (ml)</option>
                                                                    <option value="cup">Cup</option>
                                                                    <option value="tbsp">Tablespoon</option>
                                                                    <option value="tsp">Teaspoon</option>
                                                                    <option value="piece">Piece</option>
                                                                    <option value="slice">Slice</option>
                                                                    <option value="medium">Medium</option>
                                                                    <option value="large">Large</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <button class="btn btn-primary w-100" id="calculateCaloriesBtn">
                                                            <i class="fas fa-calculator me-2"></i>Calculate Calories
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 mb-4">
                                                    <div class="calc-results" id="calcResults">
                                                        <div class="result-placeholder">
                                                            <i class="fas fa-calculator fa-3x text-muted mb-3"></i>
                                                            <p class="text-muted">Search and calculate calories for any food item</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Recipe Calculator -->
                                        <div id="recipeCalc" class="calorie-calc-tab" style="display: none;">
                                            <div class="row">
                                                <div class="col-lg-6 mb-4">
                                                    <div class="recipe-form">
                                                        <h6><i class="fas fa-utensils me-2"></i>Recipe Ingredients</h6>
                                                        <div class="ingredient-list" id="ingredientList">
                                                            <div class="ingredient-item">
                                                                <div class="row">
                                                                    <div class="col-md-5">
                                                                        <input type="text" class="form-control" placeholder="Food name" name="ingredientName">
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <input type="number" class="form-control" placeholder="Amount" name="ingredientAmount" step="0.1">
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <select class="form-select" name="ingredientUnit">
                                                                            <option value="g">g</option>
                                                                            <option value="ml">ml</option>
                                                                            <option value="cup">cup</option>
                                                                            <option value="tbsp">tbsp</option>
                                                                            <option value="tsp">tsp</option>
                                                                            <option value="piece">piece</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-md-1">
                                                                        <button type="button" class="btn btn-outline-danger btn-sm remove-ingredient">
                                                                            <i class="fas fa-times"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addIngredientBtn">
                                                            <i class="fas fa-plus me-1"></i>Add Ingredient
                                                        </button>
                                                        <div class="row mt-3">
                                                            <div class="col-md-6">
                                                                <label for="servingsCalc" class="form-label">Number of Servings</label>
                                                                <input type="number" class="form-control" id="servingsCalc" value="1" min="1">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="recipeName" class="form-label">Recipe Name</label>
                                                                <input type="text" class="form-control" id="recipeName" placeholder="My Recipe">
                                                            </div>
                                                        </div>
                                                        <button class="btn btn-primary w-100 mt-3" id="calculateRecipeBtn">
                                                            <i class="fas fa-calculator me-2"></i>Calculate Recipe Calories
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 mb-4">
                                                    <div class="recipe-results" id="recipeResults">
                                                        <div class="result-placeholder">
                                                            <i class="fas fa-utensils fa-3x text-muted mb-3"></i>
                                                            <p class="text-muted">Add ingredients to calculate recipe calories</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Food Comparison -->
                                        <div id="comparisonCalc" class="calorie-calc-tab" style="display: none;">
                                            <div class="row">
                                                <div class="col-lg-6 mb-4">
                                                    <div class="comparison-form">
                                                        <h6><i class="fas fa-balance-scale me-2"></i>Compare Foods</h6>
                                                        <div class="comparison-items">
                                                            <div class="comparison-item">
                                                                <h6>Food 1</h6>
                                                                <div class="row">
                                                                    <div class="col-md-6 mb-2">
                                                                        <input type="text" class="form-control" id="compareFood1" placeholder="Search food 1">
                                                                    </div>
                                                                    <div class="col-md-3 mb-2">
                                                                        <input type="number" class="form-control" id="compareAmount1" placeholder="100" step="0.1">
                                                                    </div>
                                                                    <div class="col-md-3 mb-2">
                                                                        <select class="form-select" id="compareUnit1">
                                                                            <option value="g">g</option>
                                                                            <option value="ml">ml</option>
                                                                            <option value="cup">cup</option>
                                                                            <option value="piece">piece</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="comparison-item">
                                                                <h6>Food 2</h6>
                                                                <div class="row">
                                                                    <div class="col-md-6 mb-2">
                                                                        <input type="text" class="form-control" id="compareFood2" placeholder="Search food 2">
                                                                    </div>
                                                                    <div class="col-md-3 mb-2">
                                                                        <input type="number" class="form-control" id="compareAmount2" placeholder="100" step="0.1">
                                                                    </div>
                                                                    <div class="col-md-3 mb-2">
                                                                        <select class="form-select" id="compareUnit2">
                                                                            <option value="g">g</option>
                                                                            <option value="ml">ml</option>
                                                                            <option value="cup">cup</option>
                                                                            <option value="piece">piece</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <button class="btn btn-primary w-100" id="compareFoodsBtn">
                                                            <i class="fas fa-balance-scale me-2"></i>Compare Foods
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 mb-4">
                                                    <div class="comparison-results" id="comparisonResults">
                                                        <div class="result-placeholder">
                                                            <i class="fas fa-balance-scale fa-3x text-muted mb-3"></i>
                                                            <p class="text-muted">Select two foods to compare their nutritional values</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>




                        
                    </div>
                </div>
            </div>
        </div>

        <!-- Workout Recommendations -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Recommended for You</h5>
                    </div>
                    <div class="card-body">
                        <!-- Recommended workouts removed per request -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Portion Guide Modal -->
    <div class="modal fade" id="portionGuideModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
                <div class="modal-content understanding-bmr-card">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Portion Size Guide</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body portion-guide-modal">
                    <p class="text-muted">Use these visual references to estimate portion sizes accurately:</p>
                    <div class="portion-examples">
                        <div class="portion-example">
                            <div class="macro-circle macro-protein">🍗</div>
                            <h6>Protein (3 oz)</h6>
                            <small>Size of a deck of cards</small>
                        </div>
                        <div class="portion-example">
                            <div class="macro-circle macro-carbs">🍚</div>
                            <h6>Rice (1 cup)</h6>
                            <small>Size of a tennis ball</small>
                        </div>
                        <div class="portion-example">
                            <div class="macro-circle macro-fats">🥜</div>
                            <h6>Nuts (1 oz)</h6>
                            <small>One handful</small>
                        </div>
                        <div class="portion-example">
                            <div class="macro-circle bg-secondary">🥗</div>
                            <h6>Vegetables (1 cup)</h6>
                            <small>Size of a baseball</small>
                        </div>
                        <div class="portion-example">
                            <div class="macro-circle bg-warning">🧀</div>
                            <h6>Cheese (1 oz)</h6>
                            <small>Size of 4 dice</small>
                        </div>
                        <div class="portion-example">
                            <div class="macro-circle bg-info">🥑</div>
                            <h6>Avocado (1/2 medium)</h6>
                            <small>Size of a light bulb</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fixed Login Button -->
    <a href="../index.html" class="fixed-login-btn">
        <i class="fas fa-sign-in-alt"></i>
        <span>Login</span>
    </a>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Read persisted preference (default: enabled)
        (function(){
            try {
                var saved = localStorage.getItem('hm_chatbot_enabled');
                window.HM_CHATBOT_ENABLED = (saved === null) ? true : (saved === '1');
            } catch (e) { window.HM_CHATBOT_ENABLED = true; }
        })();
    </script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/chatbot.js"></script>
    <script>
        // Simple on-page toggle for conditional chatbot enablement
        (function(){
            try {
                var container = document.querySelector('.dashboard-container') || document.body;
                var wrapper = document.createElement('div');
                wrapper.className = 'mb-3';
                wrapper.innerHTML = '\n<label class="form-check form-switch">\n  <input class="form-check-input" type="checkbox" id="hmChatbotToggleSwitch">\n  <span class="form-check-label" for="hmChatbotToggleSwitch">Enable Assistant</span>\n</label>\n';
                var firstRow = container.querySelector('.row');
                if (firstRow && firstRow.parentNode) {
                    firstRow.parentNode.insertBefore(wrapper, firstRow);
                } else {
                    container.insertBefore(wrapper, container.firstChild);
                }

                var key = 'hm_chatbot_enabled';
                var saved = localStorage.getItem(key);
                var enabled = saved === null ? true : saved === '1';
                var checkbox = wrapper.querySelector('#hmChatbotToggleSwitch');
                checkbox.checked = enabled;
                if (typeof window.HM_CHATBOT_SET_ENABLED === 'function') {
                    window.HM_CHATBOT_SET_ENABLED(enabled);
                }
                checkbox.addEventListener('change', function(){
                    var on = !!checkbox.checked;
                    localStorage.setItem(key, on ? '1' : '0');
                    if (typeof window.HM_CHATBOT_SET_ENABLED === 'function') {
                        window.HM_CHATBOT_SET_ENABLED(on);
                    }
                });
            } catch (e) {}
        })();
    </script>
    
    <style>
        /* Ultra Dark Dashboard Styling - Normal Text */
        body {
            font-weight: 400 !important;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 50%, #0f0f0f 100%) !important;
            color: #000000 !important;
            min-height: 100vh !important;
        }
        
        /* Dark overlay for better contrast */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.7) 100%);
            z-index: -1;
            pointer-events: none;
        }
        
        /* Ultra Dark Color Variables */
        :root {
            --ultra-dark-primary: #000000 !important;
            --ultra-dark-secondary: #0a0a0a !important;
            --ultra-dark-accent: #1a1a1a !important;
            --ultra-dark-gray-900: #000000 !important;
            --ultra-dark-gray-800: #0a0a0a !important;
            --ultra-dark-gray-700: #1a1a1a !important;
            --ultra-dark-gray-600: #2a2a2a !important;
            --ultra-dark-gray-500: #3a3a3a !important;
            --ultra-dark-gray-400: #4a4a4a !important;
            --ultra-dark-gray-300: #6a6a6a !important;
            --ultra-dark-gray-200: #8a8a8a !important;
            --ultra-dark-gray-100: #ffffff !important;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-weight: 500 !important;
            color: #000000 !important;
            letter-spacing: 0.5px !important;
        }
        
        .navbar-brand {
            font-weight: 700 !important;
            font-size: 1.8rem !important;
            background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 25%, #45b7d1 50%, #96ceb4 75%, #feca57 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            letter-spacing: 1px !important;
            text-shadow: 0 0 30px rgba(255, 107, 107, 0.3) !important;
            transition: all 0.3s ease !important;
            position: relative !important;
        }
        
        .navbar-brand:hover {
            background: linear-gradient(135deg, #ff5252 0%, #26a69a 25%, #2196f3 50%, #66bb6a 75%, #ffca28 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            transform: scale(1.05) !important;
            text-shadow: 0 0 40px rgba(255, 82, 82, 0.5) !important;
        }
        
        .navbar-brand::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 25%, #45b7d1 50%, #96ceb4 75%, #feca57 100%) !important;
            border-radius: 10px !important;
            opacity: 0.1 !important;
            z-index: -1 !important;
            transform: scale(1.2) !important;
            transition: all 0.3s ease !important;
        }
        
        .navbar-brand:hover::before {
            opacity: 0.2 !important;
            transform: scale(1.3) !important;
        }
        
        /* Colorful Heart Icon */
        .navbar-brand i {
            background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 50%, #45b7d1 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            animation: heartbeat 2s ease-in-out infinite !important;
            margin-right: 0.5rem !important;
        }
        
        @keyframes heartbeat {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }
        
        .navbar-brand:hover i {
            background: linear-gradient(135deg, #ff5252 0%, #26a69a 50%, #2196f3 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            animation: heartbeat 1s ease-in-out infinite !important;
        }
        
        .nav-link {
            font-weight: 400 !important;
            font-size: 1rem !important;
            color: #000000 !important;
            letter-spacing: 0.25px !important;
        }
        
        .nav-link:hover {
            color: #000000 !important;
            font-weight: 500 !important;
        }
        
        .nav-link.active {
            color: #000000 !important;
            font-weight: 500 !important;
        }
        
        .nav-link.active {
            font-weight: 800 !important;
        }
        
        .card-title {
            font-weight: 500 !important;
            font-size: 1.25rem !important;
            color: #000000 !important;
            letter-spacing: 0.5px !important;
        }
        
        .card-text {
            font-weight: 400 !important;
            color: #000000 !important;
            letter-spacing: 0.25px !important;
        }
        
        .card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 50%, #e9ecef 100%) !important;
            border: 4px solid #000000 !important;
            box-shadow: 0 15px 40px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.2) !important;
            border-radius: 20px !important;
            position: relative !important;
            overflow: hidden !important;
        }
        
        .card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ff6b6b 0%, #4ecdc4 25%, #45b7d1 50%, #96ceb4 75%, #feca57 100%);
            z-index: 1;
        }
        
        /* Specific card colors for different stats */
        .card:nth-child(1) {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%) !important;
        }
        
        .card:nth-child(2) {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%) !important;
        }
        
        .card:nth-child(3) {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%) !important;
        }
        
        .card:nth-child(4) {
            background: linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%) !important;
        }
        
        /* Colorful card icons */
        .card i {
            background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 50%, #45b7d1 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            font-size: 2.5rem !important;
            margin-bottom: 1rem !important;
            animation: iconPulse 3s ease-in-out infinite !important;
        }
        
        @keyframes iconPulse {
            0%, 100% {
                transform: scale(1);
                opacity: 0.8;
            }
            50% {
                transform: scale(1.1);
                opacity: 1;
            }
        }
        
        .card:hover i {
            animation: iconPulse 1.5s ease-in-out infinite !important;
            transform: scale(1.2) !important;
        }
        
        /* Colorful styling for all Font Awesome icons */
        .fas, .far, .fab, .fa {
            background: linear-gradient(135deg, #ff1744 0%, #00e676 25%, #2196f3 50%, #ff9800 75%, #e91e63 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            transition: all 0.3s ease !important;
        }
        
        /* Specific colors for different icon types */
        .navbar-brand i {
            background: linear-gradient(135deg, #d32f2f 0%, #00796b 50%, #1976d2 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            animation: logoHeartbeat 2s ease-in-out infinite !important;
        }
        
        /* Navigation icons */
        .navbar-nav .nav-link i {
            background: linear-gradient(135deg, #3f51b5 0%, #9c27b0 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            transition: all 0.3s ease !important;
        }
        
        .navbar-nav .nav-link:hover i {
            background: linear-gradient(135deg, #d32f2f 0%, #00796b 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            transform: scale(1.2) !important;
        }
        
        /* Dropdown icons */
        .dropdown-item i {
            background: linear-gradient(135deg, #3f51b5 0%, #9c27b0 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            transition: all 0.3s ease !important;
        }
        
        .dropdown-item:hover i {
            background: linear-gradient(135deg, #d32f2f 0%, #00796b 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            transform: scale(1.1) !important;
        }
        
        /* Quick action icons */
        .quick-action-icon i {
            background: linear-gradient(135deg, #d32f2f 0%, #00796b 50%, #1976d2 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            font-size: 2rem !important;
            transition: all 0.3s ease !important;
        }
        
        .quick-action-card:hover .quick-action-icon i {
            background: linear-gradient(135deg, #ff1744 0%, #00e676 50%, #2196f3 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            transform: scale(1.3) rotate(10deg) !important;
            animation: iconBounce 0.6s ease !important;
        }
        
        /* Tab icons */
        .nav-tabs .nav-link i {
            background: linear-gradient(135deg, #3f51b5 0%, #9c27b0 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            transition: all 0.3s ease !important;
        }
        
        .nav-tabs .nav-link.active i {
            background: linear-gradient(135deg, #d32f2f 0%, #00796b 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            transform: scale(1.1) !important;
        }
        
        .nav-tabs .nav-link:hover i {
            background: linear-gradient(135deg, #d32f2f 0%, #00796b 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            transform: scale(1.05) !important;
            filter: drop-shadow(0 0 8px rgba(0,0,0,0.9)) !important;
            text-shadow: 0 0 10px rgba(0,0,0,0.95) !important;
        }
        
        /* Meal type icons */
        .btn-outline-primary i {
            background: linear-gradient(135deg, #3f51b5 0%, #9c27b0 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            transition: all 0.3s ease !important;
        }
        
        .btn-check:checked + .btn-outline-primary i {
            background: linear-gradient(135deg, #d32f2f 0%, #00796b 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            transform: scale(1.2) !important;
        }
        
        /* Food item icons */
        .food-item i {
            background: linear-gradient(135deg, #d32f2f 0%, #00796b 50%, #1976d2 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            transition: all 0.3s ease !important;
        }
        
        .food-item:hover i {
            background: linear-gradient(135deg, #ff1744 0%, #00e676 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            transform: scale(1.2) !important;
            animation: iconWiggle 0.5s ease !important;
        }
        
        /* Progress icons */
        .progress-item i {
            background: linear-gradient(135deg, #3f51b5 0%, #9c27b0 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            transition: all 0.3s ease !important;
        }
        
        .progress-item:hover i {
            background: linear-gradient(135deg, #d32f2f 0%, #00796b 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            transform: scale(1.1) !important;
        }
        
        /* Chart icons */
        .chart-icon i {
            background: linear-gradient(135deg, #d32f2f 0%, #00796b 50%, #1976d2 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            font-size: 3rem !important;
            transition: all 0.3s ease !important;
            filter: drop-shadow(0 0 10px rgba(0,0,0,0.8)) !important;
            text-shadow: 0 0 12px rgba(0,0,0,0.9) !important;
        }
        
        .chart-icon:hover i {
            background: linear-gradient(135deg, #ff1744 0%, #00e676 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            transform: scale(1.2) !important;
            animation: iconSpin 1s ease !important;
            filter: drop-shadow(0 0 15px rgba(0,0,0,0.9)) !important;
            text-shadow: 0 0 18px rgba(0,0,0,0.95) !important;
        }
        
        /* Animations for icons */
        @keyframes iconBounce {
            0%, 20%, 50%, 80%, 100% {
                transform: scale(1.3) rotate(10deg) translateY(0);
            }
            40% {
                transform: scale(1.3) rotate(10deg) translateY(-10px);
            }
            60% {
                transform: scale(1.3) rotate(10deg) translateY(-5px);
            }
        }
        
        @keyframes iconWiggle {
            0%, 100% {
                transform: scale(1.2) rotate(0deg);
            }
            25% {
                transform: scale(1.2) rotate(-5deg);
            }
            75% {
                transform: scale(1.2) rotate(5deg);
            }
        }
        
        @keyframes iconSpin {
            0% {
                transform: scale(1.2) rotate(0deg);
            }
            100% {
                transform: scale(1.2) rotate(360deg);
            }
        }
        
        /* Special icon effects with thematic colors */
        .fas.fa-heartbeat {
            background: linear-gradient(135deg, #e91e63 0%, #f06292 50%, #f8bbd9 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            animation: logoHeartbeat 2s ease-in-out infinite !important;
        }
        
        .fas.fa-fire {
            background: linear-gradient(135deg, #ff5722 0%, #ff9800 25%, #ffc107 50%, #ff5722 75%, #d32f2f 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            animation: iconFlicker 2s ease-in-out infinite !important;
        }
        
        .fas.fa-burn {
            background: linear-gradient(135deg, #ff5722 0%, #ff9800 25%, #ffc107 50%, #ff5722 75%, #d32f2f 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            animation: iconFlicker 2s ease-in-out infinite 0.5s !important;
        }
        
        .fas.fa-clock {
            background: linear-gradient(135deg, #2196f3 0%, #03a9f4 50%, #00bcd4 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            animation: iconTick 2s ease-in-out infinite !important;
        }
        
        .fas.fa-target {
            background: linear-gradient(135deg, #4caf50 0%, #8bc34a 50%, #cddc39 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            animation: iconPulse 2s ease-in-out infinite !important;
        }
        
        /* Additional thematic colors for specific icons */
        .fas.fa-trophy {
            background: linear-gradient(135deg, #ffd700 0%, #ffc107 50%, #ff9800 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
        }
        
        .fas.fa-medal {
            background: linear-gradient(135deg, #ffd700 0%, #ffc107 50%, #ff9800 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
        }
        
        .fas.fa-dumbbell {
            background: linear-gradient(135deg, #9e9e9e 0%, #607d8b 50%, #455a64 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
        }
        
        .fas.fa-tachometer-alt {
            background: linear-gradient(135deg, #673ab7 0%, #9c27b0 50%, #e91e63 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
        }
        
        .fas.fa-user-circle {
            background: linear-gradient(135deg, #2196f3 0%, #03a9f4 50%, #00bcd4 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
        }
        
        .fas.fa-user {
            background: linear-gradient(135deg, #2196f3 0%, #03a9f4 50%, #00bcd4 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
        }
        
        .fas.fa-cog {
            background: linear-gradient(135deg, #9e9e9e 0%, #607d8b 50%, #455a64 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
        }
        
        .fas.fa-sign-out-alt {
            background: linear-gradient(135deg, #f44336 0%, #e91e63 50%, #9c27b0 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
        }
        
        .fas.fa-bolt {
            background: linear-gradient(135deg, #ffeb3b 0%, #ffc107 50%, #ff9800 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
        }
        
        .fas.fa-play {
            background: linear-gradient(135deg, #4caf50 0%, #8bc34a 50%, #cddc39 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
        }
        
        .fas.fa-plus {
            background: linear-gradient(135deg, #4caf50 0%, #8bc34a 50%, #cddc39 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
        }
        
        .fas.fa-calculator {
            background: linear-gradient(135deg, #673ab7 0%, #9c27b0 50%, #e91e63 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
        }
        
        .fas.fa-chart-line {
            background: linear-gradient(135deg, #2196f3 0%, #03a9f4 50%, #00bcd4 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
        }
        
        .fas.fa-history {
            background: linear-gradient(135deg, #9e9e9e 0%, #607d8b 50%, #455a64 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
        }
        
        .fas.fa-sun {
            background: linear-gradient(135deg, #ffeb3b 0%, #ffc107 50%, #ff9800 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            filter: drop-shadow(0 0 6px rgba(255, 235, 59, 0.6)) !important;
            text-shadow: 0 0 8px rgba(255, 235, 59, 0.8) !important;
        }
        
        .fas.fa-moon {
            background: linear-gradient(135deg, #3f51b5 0%, #9c27b0 50%, #673ab7 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
        }
        
        .fas.fa-cookie-bite {
            background: linear-gradient(135deg, #8d6e63 0%, #a1887f 50%, #bcaaa4 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
        }
        
        .fas.fa-coffee {
            background: linear-gradient(135deg, #8d6e63 0%, #a1887f 50%, #bcaaa4 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
        }
        
        /* Gender Icons Styling */
        .fas.fa-mars {
            background: linear-gradient(135deg, #2196f3 0%, #1976d2 50%, #0d47a1 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            animation: genderPulse 2s ease-in-out infinite !important;
            filter: drop-shadow(0 0 8px rgba(33, 150, 243, 0.6)) !important;
            text-shadow: 0 0 10px rgba(33, 150, 243, 0.8) !important;
        }
        
        .fas.fa-venus {
            background: linear-gradient(135deg, #e91e63 0%, #c2185b 50%, #880e4f 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            animation: genderPulse 2s ease-in-out infinite !important;
            filter: drop-shadow(0 0 8px rgba(233, 30, 99, 0.6)) !important;
            text-shadow: 0 0 10px rgba(233, 30, 99, 0.8) !important;
        }
        
        @keyframes genderPulse {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.1);
                opacity: 0.8;
            }
        }
        
        /* Gender icon hover effects */
        .navbar-nav .nav-link:hover .fas.fa-mars {
            transform: scale(1.2) rotate(10deg) !important;
            animation: genderBounce 0.6s ease !important;
        }
        
        .navbar-nav .nav-link:hover .fas.fa-venus {
            transform: scale(1.2) rotate(-10deg) !important;
            animation: genderBounce 0.6s ease !important;
        }
        
        @keyframes genderBounce {
            0% {
                transform: scale(1) rotate(0deg);
            }
            50% {
                transform: scale(1.3) rotate(15deg);
            }
            100% {
                transform: scale(1.2) rotate(10deg);
            }
        }
        
        /* Profile Image Styling for Ashish */
        .avatar img {
            transition: all 0.3s ease !important;
            animation: profilePulse 3s ease-in-out infinite !important;
        }
        
        .avatar img:hover {
            transform: scale(1.1) !important;
            box-shadow: 0 0 15px rgba(33, 150, 243, 0.6) !important;
        }
        
        @keyframes profilePulse {
            0%, 100% {
                box-shadow: 0 0 10px rgba(33, 150, 243, 0.3);
            }
            50% {
                box-shadow: 0 0 15px rgba(33, 150, 243, 0.5);
            }
        }
        
        /* Welcome section profile image */
        .dashboard-title img {
            transition: all 0.3s ease !important;
            animation: welcomeProfilePulse 4s ease-in-out infinite !important;
        }
        
        .dashboard-title img:hover {
            transform: scale(1.15) !important;
            box-shadow: 0 0 12px rgba(33, 150, 243, 0.6) !important;
        }
        
        @keyframes welcomeProfilePulse {
            0%, 100% {
                box-shadow: 0 0 8px rgba(33, 150, 243, 0.4);
            }
            50% {
                box-shadow: 0 0 12px rgba(33, 150, 243, 0.6);
            }
        }
        
        @keyframes iconFlicker {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.7;
                transform: scale(1.1);
            }
        }
        
        @keyframes iconTick {
            0%, 100% {
                transform: rotate(0deg);
            }
            25% {
                transform: rotate(5deg);
            }
            75% {
                transform: rotate(-5deg);
            }
        }
        
        .btn {
            font-weight: 400 !important;
            font-size: 1rem !important;
        }
        
        .btn-primary {
            font-weight: 500 !important;
        }
        
        .form-label {
            font-weight: 400 !important;
            font-size: 1rem !important;
            color: #000000 !important;
        }
        
        .form-control {
            font-weight: 400 !important;
            font-size: 1rem !important;
            color: #000000 !important;
            background-color: #f5f5f5 !important;
            border: 2px solid #404040 !important;
        }
        
        .form-control:focus {
            border-color: #1e1b4b !important;
            background-color: #ffffff !important;
            color: #000000 !important;
        }
        
        .stat-number {
            font-weight: 700 !important;
            font-size: 3rem !important;
            background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 25%, #45b7d1 50%, #96ceb4 75%, #feca57 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            letter-spacing: 1px !important;
            line-height: 1 !important;
            text-shadow: 0 0 20px rgba(255, 107, 107, 0.3) !important;
        }
        
        .stat-label {
            font-weight: 600 !important;
            font-size: 1.1rem !important;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            letter-spacing: 0.5px !important;
            text-transform: uppercase !important;
        }
        
        .progress-label {
            font-weight: 400 !important;
            color: #000000 !important;
        }
        
        .text-muted {
            color: #525252 !important;
            font-weight: 400 !important;
        }
        
        .small {
            color: #404040 !important;
            font-weight: 400 !important;
        }
        
        .badge {
            font-weight: 400 !important;
            font-size: 0.9rem !important;
        }
        
        .alert {
            font-weight: 400 !important;
        }
        
        .modal-title {
            font-weight: 500 !important;
            font-size: 1.25rem !important;
        }
        
        .modal-body {
            font-weight: 400 !important;
        }
        
        .table th {
            font-weight: 500 !important;
            font-size: 1rem !important;
        }
        
        .table td {
            font-weight: 400 !important;
        }
        
        .list-group-item {
            font-weight: 400 !important;
        }
        
        .text-muted {
            font-weight: 600 !important;
        }
        
        .small {
            font-weight: 600 !important;
        }
        
        .lead {
            font-weight: 400 !important;
            font-size: 1.1rem !important;
        }
        
        .display-1, .display-2, .display-3, .display-4, .display-5, .display-6 {
            font-weight: 500 !important;
        }
        
        .fw-bold {
            font-weight: 500 !important;
        }
        
        .fw-normal {
            font-weight: 400 !important;
        }
        
        .fw-light {
            font-weight: 300 !important;
        }
        
        /* Ultra Dark and Bold Card Styling */
        .card {
            border: 5px solid #000000 !important;
            border-radius: 25px !important;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6), inset 0 2px 0 rgba(255,255,255,0.1) !important;
            background: linear-gradient(135deg, #ffffff 0%, #f8f8f8 50%, #f0f0f0 100%) !important;
            position: relative !important;
            overflow: hidden !important;
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #000000, #333333, #000000);
            z-index: 1;
        }
        
        .card-header {
            background: linear-gradient(135deg, #000000, #1a1a1a) !important;
            border-bottom: 4px solid #000000 !important;
            font-weight: 500 !important;
            color: #ffffff !important;
            letter-spacing: 0.5px !important;
            position: relative !important;
            z-index: 2 !important;
        }
        
        /* Enhanced Button Styling */
        .btn {
            border-width: 2px !important;
            border-radius: 10px !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #000000, #1a1a1a) !important;
            border: 4px solid #000000 !important;
            color: #ffffff !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.8) !important;
            font-weight: 400 !important;
            font-size: 1rem !important;
            letter-spacing: 0.5px !important;
            border-radius: 15px !important;
        }
        
        .btn-primary:hover {
            transform: translateY(-4px) scale(1.05) !important;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.9) !important;
            background: linear-gradient(135deg, #1a1a1a, #000000) !important;
            border-color: #333333 !important;
        }
        
        /* Ultra Dark Navigation */
        .navbar {
            border-bottom: 5px solid #000000 !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6) !important;
            background: linear-gradient(135deg, #ffffff 0%, #f8f8f8 100%) !important;
            backdrop-filter: blur(20px) !important;
        }
        
        /* Ultra Dark Progress Bars */
        .progress {
            height: 30px !important;
            border-radius: 15px !important;
            border: 4px solid #000000 !important;
            background: linear-gradient(135deg, #f0f0f0, #e0e0e0) !important;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.3) !important;
        }
        
        .progress-bar {
            font-weight: 400 !important;
            font-size: 1rem !important;
            background: linear-gradient(135deg, #000000, #1a1a1a) !important;
            color: #ffffff !important;
            letter-spacing: 0.5px !important;
            border-radius: 10px !important;
        }
        
        /* Enhanced Tables with Darker Colors */
        .table {
            border: 2px solid #404040 !important;
            border-radius: 10px !important;
            background-color: #f5f5f5 !important;
        }
        
        .table th {
            background: linear-gradient(135deg, #1e1b4b, #0f172a) !important;
            color: white !important;
            border-bottom: 3px solid #0f0f0f !important;
            font-weight: 500 !important;
        }
        
        .table td {
            color: #2d2d2d !important;
            font-weight: 400 !important;
        }
        
        /* Enhanced Alerts with Darker Colors */
        .alert {
            border: 2px solid !important;
            border-radius: 10px !important;
            font-weight: 400 !important;
        }
        
        .alert-primary {
            border-color: #1e1b4b !important;
            background: linear-gradient(135deg, rgba(30, 27, 75, 0.2), rgba(30, 27, 75, 0.1)) !important;
            color: #0f0f0f !important;
        }
        
        /* Enhanced Forms */
        .form-control {
            border: 2px solid var(--gray-400) !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
        }
        
        .form-control:focus {
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25) !important;
        }
        
        /* Enhanced Badges */
        .badge {
            border-radius: 8px !important;
            padding: 0.5rem 0.75rem !important;
            font-size: 0.9rem !important;
        }
        
        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .avatar-large {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .quick-action-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            border: 1px solid var(--gray-200);
            transition: var(--transition-normal);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .quick-action-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }
        
        .quick-action-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }
        
        .activity-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
            transition: var(--transition-fast);
        }
        
        .activity-item:hover {
            background-color: var(--gray-50);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.875rem;
            flex-shrink: 0;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-content h6 {
            margin-bottom: 0.25rem;
            font-size: 0.875rem;
        }
        
        .activity-content p {
            font-size: 0.8rem;
            margin-bottom: 0.25rem;
        }
        
        .activity-content small {
            font-size: 0.75rem;
        }
        
        .btn-group .btn {
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
        }
        
                 .btn-group .btn.active {
             background-color: var(--primary-color);
             border-color: var(--primary-color);
             color: white;
         }
         
         /* White text for Recommended for You workout cards */
         #workoutRecommendations .workout-title {
             color: #ffffff; /* white */
         }
         #workoutRecommendations .workout-desc {
             color: #ffffff; /* white */
         }
         #workoutRecommendations .workout-stats {
             color: #ffffff; /* white */
         }
         
         /* White text for workout card elements */
         #workoutRecommendations .workout-card h5,
         #workoutRecommendations .workout-card .workout-badge,
         #workoutRecommendations .workout-card .badge,
         #workoutRecommendations .workout-card p,
         #workoutRecommendations .workout-card .text-muted,
         #workoutRecommendations .workout-card strong,
         #workoutRecommendations .workout-card h6,
         #workoutRecommendations .workout-card .exercise-item strong,
         #workoutRecommendations .workout-card .exercise-item .small {
             color: #ffffff !important;
         }
         
         /* Ensure exercise items have white text */
         #workoutRecommendations .exercise-item {
             background: rgba(255, 255, 255, 0.1) !important;
         }
         
         /* Black text for seconds/rest time badges */
         #workoutRecommendations .exercise-item .badge {
             color: #000000 !important;
             background-color: #ffffff !important;
         }
         
         /* Calories Calculator Styles */
         .calories-calculator {
             background: #f8f9fa;
             border-radius: var(--radius-lg);
             padding: 1.5rem;
         }
         
         .suggestions-dropdown {
             position: absolute;
             top: 100%;
             left: 0;
             right: 0;
             background: white;
             border: 1px solid var(--gray-300);
             border-radius: var(--radius-md);
             box-shadow: var(--shadow-lg);
             max-height: 200px;
             overflow-y: auto;
             z-index: 1000;
             display: none;
         }
         
         .suggestion-item {
             padding: 0.75rem 1rem;
             cursor: pointer;
             border-bottom: 1px solid var(--gray-200);
             transition: var(--transition-fast);
         }
         
         .suggestion-item:hover {
             background-color: var(--gray-50);
         }
         
         .suggestion-item:last-child {
             border-bottom: none;
         }
         
         .food-list {
             max-height: 300px;
             overflow-y: auto;
         }
         
         .food-item {
             background: white;
             border: 1px solid var(--gray-200);
             border-radius: var(--radius-md);
             padding: 1rem;
             margin-bottom: 0.75rem;
             display: flex;
             justify-content: space-between;
             align-items: center;
             transition: var(--transition-fast);
         }
         
         .food-item:hover {
             box-shadow: var(--shadow-md);
             border-color: var(--primary-color);
         }
         
         .food-info h6 {
             margin-bottom: 0.25rem;
             color: var(--gray-900);
         }
         
         .food-info small {
             color: var(--gray-600);
         }
         
         .food-calories {
             font-weight: 600;
             color: var(--primary-color);
             font-size: 1.1rem;
         }
         
         .remove-food-btn {
             background: none;
             border: none;
             color: var(--danger-color);
             cursor: pointer;
             padding: 0.25rem;
             border-radius: var(--radius-sm);
             transition: var(--transition-fast);
         }
         
         .remove-food-btn:hover {
             background-color: var(--danger-color);
             color: white;
         }
         
         .calorie-info-card {
             background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
             color: white;
             border-radius: var(--radius-lg);
             padding: 1.5rem;
             height: fit-content;
         }
         
         .calorie-info-card h6 {
             color: white !important;
         }
         
         .quick-food-btn {
             background: rgba(255, 255, 255, 0.2) !important;
             border: 1px solid rgba(255, 255, 255, 0.3) !important;
             color: white !important;
             transition: var(--transition-fast);
         }
         
         .quick-food-btn:hover {
             background: rgba(255, 255, 255, 0.3) !important;
             border-color: rgba(255, 255, 255, 0.5) !important;
             color: white !important;
         }
         
         .goal-progress {
             background: rgba(255, 255, 255, 0.2);
             border-radius: var(--radius-md);
             padding: 1rem;
         }
         
         .calorie-summary {
             text-align: center;
             padding: 1rem;
             background: white;
             border-radius: var(--radius-md);
             box-shadow: var(--shadow-sm);
         }
         
         .total-calories-display {
             background: white;
             border-radius: var(--radius-lg);
             padding: 1.5rem;
             box-shadow: var(--shadow-md);
         }
         
         /* Enhanced Detailed Calculator Styles */
         .tab-content {
             animation: fadeIn 0.3s ease-in-out;
         }
         
         @keyframes fadeIn {
             from { opacity: 0; transform: translateY(10px); }
             to { opacity: 1; transform: translateY(0); }
         }
         
         .meal-foods {
             background: #f8f9fa;
             border-radius: var(--radius-lg);
             padding: 1rem;
             border: 1px solid var(--gray-200);
         }
         
         .summary-card {
             background: white;
             border-radius: var(--radius-lg);
             padding: 1.5rem;
             text-align: center;
             box-shadow: var(--shadow-sm);
             border: 1px solid var(--gray-200);
             transition: var(--transition-normal);
         }
         
         .summary-card:hover {
             transform: translateY(-2px);
             box-shadow: var(--shadow-md);
         }
         
         .summary-icon {
             width: 50px;
             height: 50px;
             border-radius: 50%;
             display: flex;
             align-items: center;
             justify-content: center;
             margin: 0 auto 1rem;
             color: white;
             font-size: 1.25rem;
         }
         
         .summary-content h4 {
             margin-bottom: 0.25rem;
             font-weight: 700;
         }
         
         .summary-content small {
             color: var(--gray-600);
             font-weight: 500;
         }
         
         .daily-summary {
             background: white;
             border-radius: var(--radius-lg);
             padding: 1.5rem;
             box-shadow: var(--shadow-md);
             border: 1px solid var(--gray-200);
         }
         
         .meal-breakdown {
             background: rgba(255, 255, 255, 0.1);
             border-radius: var(--radius-md);
             padding: 1rem;
         }
         
         .meal-item {
             margin-bottom: 1rem;
         }
         
         .meal-item:last-child {
             margin-bottom: 0;
         }
         
         .meal-history {
             max-height: 500px;
             overflow-y: auto;
         }
         
         .history-item {
             background: white;
             border-radius: var(--radius-md);
             padding: 1rem;
             margin-bottom: 0.75rem;
             border: 1px solid var(--gray-200);
             transition: var(--transition-fast);
         }
         
         .history-item:hover {
             box-shadow: var(--shadow-md);
             border-color: var(--primary-color);
         }
         
         .history-header {
             display: flex;
             justify-content: between;
             align-items: center;
             margin-bottom: 0.5rem;
         }
         
         .history-date {
             font-weight: 600;
             color: var(--gray-900);
         }
         
         .history-total {
             font-weight: 700;
             color: var(--primary-color);
         }
         
         .history-foods {
             display: flex;
             flex-wrap: wrap;
             gap: 0.5rem;
         }
         
         .history-food {
             background: var(--gray-100);
             padding: 0.25rem 0.5rem;
             border-radius: var(--radius-sm);
             font-size: 0.875rem;
             color: var(--gray-700);
         }
         
         .portion-guide-modal .modal-body {
             padding: 2rem;
         }
         
         .portion-examples {
             display: grid;
             grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
             gap: 1rem;
             margin-top: 1rem;
         }
         
         .portion-example {
             text-align: center;
             padding: 1rem;
             background: var(--gray-50);
             border-radius: var(--radius-md);
         }
         
         .portion-example img {
             width: 60px;
             height: 60px;
             object-fit: cover;
             border-radius: 50%;
             margin-bottom: 0.5rem;
         }
         
         .portion-example h6 {
             margin-bottom: 0.25rem;
             font-size: 0.875rem;
         }
         
         .portion-example small {
             color: var(--gray-600);
         }
         
         .nutrition-details {
             background: var(--gray-50);
             border-radius: var(--radius-md);
             padding: 1rem;
             margin-top: 1rem;
         }
         
         .nutrition-row {
             display: flex;
             justify-content: space-between;
             align-items: center;
             padding: 0.5rem 0;
             border-bottom: 1px solid var(--gray-200);
         }
         
         .nutrition-row:last-child {
             border-bottom: none;
         }
         
         .nutrition-label {
             font-weight: 500;
             color: var(--gray-700);
         }
         
         .nutrition-value {
             font-weight: 600;
             color: var(--gray-900);
         }
         
         .macro-distribution {
             display: flex;
             gap: 1rem;
             margin-top: 1rem;
         }
         
         .macro-item {
             flex: 1;
             text-align: center;
             padding: 1rem;
             background: white;
             border-radius: var(--radius-md);
             box-shadow: var(--shadow-sm);
         }
         
         .macro-circle {
             width: 60px;
             height: 60px;
             border-radius: 50%;
             display: flex;
             align-items: center;
             justify-content: center;
             margin: 0 auto 0.5rem;
             font-weight: 700;
             color: white;
         }
         
         .macro-protein { background: var(--success-color); }
         .macro-carbs { background: var(--warning-color); }
         .macro-fats { background: var(--info-color); }
         
         .recommendations {
             background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
             color: white;
             border-radius: var(--radius-lg);
             padding: 1.5rem;
             margin-top: 1rem;
         }
         
         .recommendation-item {
             display: flex;
             align-items: center;
             gap: 0.75rem;
             padding: 0.5rem 0;
         }
         
         .recommendation-icon {
             width: 30px;
             height: 30px;
             border-radius: 50%;
             background: rgba(255, 255, 255, 0.2);
             display: flex;
             align-items: center;
             justify-content: center;
             font-size: 0.875rem;
         }
         
         /* Enhanced Analytics Styles */
         .analytics-card {
             background: white;
             border-radius: var(--radius-lg);
             padding: 1.5rem;
             box-shadow: var(--shadow-md);
             border: 1px solid var(--gray-200);
             transition: var(--transition-normal);
             height: 100%;
         }
         
         .analytics-card:hover {
             transform: translateY(-2px);
             box-shadow: var(--shadow-lg);
         }
         
         .analytics-icon {
             width: 50px;
             height: 50px;
             border-radius: 50%;
             display: flex;
             align-items: center;
             justify-content: center;
             margin-bottom: 1rem;
             color: white;
             font-size: 1.25rem;
         }
         
         .analytics-content h4 {
             margin-bottom: 0.5rem;
             font-weight: 700;
             color: var(--gray-900);
         }
         
         .analytics-content p {
             margin-bottom: 0.5rem;
             color: var(--gray-600);
             font-weight: 500;
         }
         
         .nutrition-balance {
             padding: 1rem 0;
         }
         
         .balance-item {
             margin-bottom: 1.5rem;
         }
         
         .balance-item:last-child {
             margin-bottom: 0;
         }
         
         .balance-score {
             font-weight: 600;
             font-size: 0.875rem;
         }
         
         .balance-score.good { color: var(--success-color); }
         .balance-score.fair { color: var(--warning-color); }
         .balance-score.poor { color: var(--danger-color); }
         
         .insights-container {
             padding: 1rem 0;
         }
         
         .insight-item {
             display: flex;
             align-items: flex-start;
             gap: 1rem;
             padding: 1rem;
             background: var(--gray-50);
             border-radius: var(--radius-md);
             margin-bottom: 1rem;
             transition: var(--transition-fast);
         }
         
         .insight-item:hover {
             background: var(--gray-100);
             transform: translateX(5px);
         }
         
         .insight-item:last-child {
             margin-bottom: 0;
         }
         
         .insight-icon {
             width: 40px;
             height: 40px;
             border-radius: 50%;
             display: flex;
             align-items: center;
             justify-content: center;
             color: white;
             font-size: 1rem;
             flex-shrink: 0;
         }
         
         .insight-content h6 {
             margin-bottom: 0.5rem;
             font-weight: 600;
             color: var(--gray-900);
         }
         
         .insight-content p {
             margin-bottom: 0;
             color: var(--gray-700);
             font-size: 0.875rem;
             line-height: 1.4;
         }
         
         .goals-progress {
             padding: 1rem 0;
         }
         
         .goal-item {
             margin-bottom: 1.5rem;
         }
         
         .goal-item:last-child {
             margin-bottom: 0;
         }
         
         .recommendations {
             padding: 1rem 0;
         }
         
         .recommendation-item {
             display: flex;
             align-items: flex-start;
             gap: 1rem;
             padding: 1rem;
             background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
             color: white;
             border-radius: var(--radius-md);
             margin-bottom: 1rem;
             transition: var(--transition-fast);
         }
         
         .recommendation-item:hover {
             transform: translateY(-2px);
             box-shadow: var(--shadow-lg);
         }
         
         .recommendation-item:last-child {
             margin-bottom: 0;
         }
         
         .recommendation-icon {
             width: 40px;
             height: 40px;
             border-radius: 50%;
             background: rgba(255, 255, 255, 0.2);
             display: flex;
             align-items: center;
             justify-content: center;
             color: white;
             font-size: 1rem;
             flex-shrink: 0;
         }
         
         .recommendation-content h6 {
             margin-bottom: 0.5rem;
             font-weight: 600;
             color: white;
         }
         
         .recommendation-content p {
             margin-bottom: 0;
             color: rgba(255, 255, 255, 0.9);
             font-size: 0.875rem;
             line-height: 1.4;
         }
         
         .comparison-charts {
             padding: 1rem 0;
         }
         
         .stat-comparison h6 {
             margin-bottom: 1rem;
             font-weight: 600;
             color: var(--gray-900);
         }
         
         .comparison-item {
             display: flex;
             align-items: center;
             gap: 1rem;
             margin-bottom: 1rem;
         }
         
         .comparison-item:last-child {
             margin-bottom: 0;
         }
         
         .comparison-item span:first-child {
             min-width: 60px;
             font-weight: 500;
             color: var(--gray-700);
         }
         
         .comparison-bar {
             flex: 1;
             height: 8px;
             background: var(--gray-200);
             border-radius: 4px;
             overflow: hidden;
         }
         
         .comparison-fill {
             height: 100%;
             background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
             border-radius: 4px;
             transition: width 0.5s ease;
         }
         
         .comparison-item span:last-child {
             min-width: 40px;
             text-align: right;
             font-weight: 600;
             color: var(--gray-900);
         }
         
         /* BMR & Meal Plans Styles */
         .bmr-results {
             padding: 1rem 0;
         }
         
         .bmr-card {
             display: flex;
             align-items: center;
             gap: 1rem;
             padding: 1rem;
             background: white;
             border-radius: var(--radius-md);
             box-shadow: var(--shadow-sm);
             margin-bottom: 1rem;
             transition: var(--transition-fast);
         }
         
         .bmr-card:hover {
             transform: translateY(-2px);
             box-shadow: var(--shadow-md);
         }
         
         .bmr-card:last-child {
             margin-bottom: 0;
         }
         
         .bmr-icon {
             width: 50px;
             height: 50px;
             border-radius: 50%;
             display: flex;
             align-items: center;
             justify-content: center;
             color: white;
             font-size: 1.25rem;
             flex-shrink: 0;
         }
         
         .bmr-content h4 {
             margin-bottom: 0.25rem;
             font-weight: 700;
             color: var(--gray-900);
         }
         
         .bmr-content p {
             margin-bottom: 0;
             color: var(--gray-600);
             font-weight: 500;
         }
         
         .bmr-placeholder {
             text-align: center;
             padding: 2rem;
             color: var(--gray-500);
         }
         
         .goal-info {
             background: var(--gray-50);
             padding: 1rem;
             border-radius: var(--radius-md);
             border-left: 4px solid var(--primary-color);
         }
         
         .goal-info h6 {
             margin-bottom: 0.5rem;
             font-weight: 600;
             color: var(--gray-900);
         }
         
         .macro-targets {
             margin-top: 1rem;
         }
         
         .macro-target {
             display: flex;
             justify-content: space-between;
             align-items: center;
             padding: 0.5rem 0;
             border-bottom: 1px solid var(--gray-200);
         }
         
         .macro-target:last-child {
             border-bottom: none;
         }
         
         .macro-target span {
             color: var(--gray-700);
             font-weight: 500;
         }
         
         .macro-target strong {
             color: var(--gray-900);
             font-weight: 700;
         }
         
         .meal-plan-content {
             min-height: 400px;
         }
         
         .meal-plan-tab {
             animation: fadeIn 0.3s ease-in-out;
         }
         
         .meal-plan-card {
             background: white;
             border-radius: var(--radius-lg);
             padding: 1.5rem;
             box-shadow: var(--shadow-sm);
             border: 1px solid var(--gray-200);
             transition: var(--transition-normal);
             height: 100%;
         }
         
         .meal-plan-card:hover {
             transform: translateY(-2px);
             box-shadow: var(--shadow-md);
         }
         
         .meal-plan-header {
             display: flex;
             justify-content: between;
             align-items: center;
             margin-bottom: 1rem;
         }
         
         .meal-plan-title {
             font-weight: 600;
             color: var(--gray-900);
             margin-bottom: 0;
         }
         
         .meal-plan-calories {
             background: var(--primary-color);
             color: white;
             padding: 0.25rem 0.75rem;
             border-radius: var(--radius-sm);
             font-weight: 600;
             font-size: 0.875rem;
         }
         
         .meal-plan-description {
             color: var(--gray-600);
             margin-bottom: 1rem;
             line-height: 1.5;
         }
         
         .meal-plan-macros {
             display: grid;
             grid-template-columns: repeat(3, 1fr);
             gap: 1rem;
             margin-bottom: 1rem;
         }
         
         .macro-item {
             text-align: center;
             padding: 0.75rem;
             background: var(--gray-50);
             border-radius: var(--radius-sm);
         }
         
         .macro-item h6 {
             margin-bottom: 0.25rem;
             font-weight: 600;
             color: var(--gray-900);
         }
         
         .macro-item p {
             margin-bottom: 0;
             color: var(--gray-600);
             font-size: 0.875rem;
         }
         
         .meal-plan-foods {
             margin-bottom: 1rem;
         }
         
         .food-item {
             display: flex;
             justify-content: space-between;
             align-items: center;
             padding: 0.5rem 0;
             border-bottom: 1px solid var(--gray-200);
         }
         
         .food-item:last-child {
             border-bottom: none;
         }
         
         .food-name {
             font-weight: 500;
             color: var(--gray-900);
         }
         
         .food-amount {
             color: var(--gray-600);
             font-size: 0.875rem;
         }
         
         .add-to-tracker-btn {
             width: 100%;
             background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
             border: none;
             color: white;
             padding: 0.75rem;
             border-radius: var(--radius-md);
             font-weight: 600;
             transition: var(--transition-fast);
         }
         
         .add-to-tracker-btn:hover {
             transform: translateY(-1px);
             box-shadow: var(--shadow-md);
         }
         
         .progress-tracking {
             padding: 1rem 0;
         }
         
         .progress-item {
             margin-bottom: 1.5rem;
         }
         
         .progress-item:last-child {
             margin-bottom: 0;
         }
         
         .timeline-info {
             padding: 1rem 0;
         }
         
         .timeline-item {
             margin-bottom: 1.5rem;
             padding-bottom: 1rem;
             border-bottom: 1px solid var(--gray-200);
         }
         
         .timeline-item:last-child {
             margin-bottom: 0;
             padding-bottom: 0;
             border-bottom: none;
         }
         
         .timeline-item h6 {
             margin-bottom: 0.5rem;
             font-weight: 600;
             color: var(--gray-900);
         }
         
         .timeline-item p {
             margin-bottom: 0;
             color: var(--gray-600);
         }
         
         /* Enhanced BMR & Meal Plan Styles */
         .bmr-explanation {
             background: var(--gray-50);
             padding: 0.75rem;
             border-radius: var(--radius-sm);
             border-left: 3px solid var(--primary-color);
         }
         
         .info-card {
             text-align: center;
             padding: 1rem;
             background: var(--gray-50);
             border-radius: var(--radius-md);
             height: 100%;
         }
         
         .info-card i {
             font-size: 1.5rem;
             margin-bottom: 0.5rem;
         }
         
         .info-card h6 {
             margin-bottom: 0.5rem;
             font-weight: 600;
             color: var(--gray-900);
         }
         
         .tip-card {
             display: flex;
             align-items: center;
             gap: 1rem;
             padding: 1rem;
             background: white;
             border-radius: var(--radius-md);
             box-shadow: var(--shadow-sm);
             border: 1px solid var(--gray-200);
             height: 100%;
         }
         
         .tip-icon {
             width: 50px;
             height: 50px;
             border-radius: 50%;
             display: flex;
             align-items: center;
             justify-content: center;
             color: white;
             font-size: 1.25rem;
             flex-shrink: 0;
         }
         
         .tip-card h6 {
             margin-bottom: 0.5rem;
             font-weight: 600;
             color: var(--gray-900);
         }
         
         .tip-card p {
             margin-bottom: 0;
             color: var(--gray-600);
             font-size: 0.875rem;
             line-height: 1.4;
         }
         
         /* Modal Styles */
         .equation-box {
             background: var(--gray-50);
             padding: 1rem;
             border-radius: var(--radius-md);
             border: 1px solid var(--gray-200);
         }
        /* Lighten the Understanding BMR & TDEE modal card */
        #bmrInfoModal .modal-content {
            background: #ffffff !important;
            border: 1px solid rgba(0,0,0,0.06) !important;
            box-shadow: 0 8px 24px rgba(0,0,0,0.05) !important;
        }
        #bmrInfoModal .modal-header {
            background: #f8fafc !important;
            border-bottom: 1px solid #eef2f7 !important;
        }
        #bmrInfoModal .modal-body {
            background: #ffffff !important;
            color: #374151 !important;
        }
        
        /* Remove background image from progress modal */
        #progressModal .modal-content,
        #progressModal .modal-body {
            background: #ffffff !important;
            background-image: none !important;
        }
        #bmrInfoModal .equation-box {
            background: #f9fbfd !important;
            border-color: #e5edf6 !important;
            color: #1f2937 !important;
        }
         
         .meal-timing-schedule {
             max-height: 400px;
             overflow-y: auto;
         }
         
         .timing-item {
             display: flex;
             align-items: center;
             gap: 1rem;
             padding: 1rem;
             margin-bottom: 0.5rem;
             background: var(--gray-50);
             border-radius: var(--radius-md);
             border-left: 4px solid var(--primary-color);
         }
         
         .timing-time {
             min-width: 80px;
             font-weight: 600;
             color: var(--primary-color);
             font-size: 0.875rem;
         }
         
         .timing-meal h6 {
             margin-bottom: 0.25rem;
             font-weight: 600;
             color: var(--gray-900);
         }
         
         .timing-meal p {
             margin-bottom: 0;
             color: var(--gray-600);
             font-size: 0.875rem;
         }
         
         .progress-insights {
             background: var(--gray-50);
             padding: 1rem;
             border-radius: var(--radius-md);
             border: 1px solid var(--gray-200);
         }
         
         /* Calorie Calculator Styles */
         .calorie-calc-tab {
             animation: fadeIn 0.3s ease-in-out;
         }
         
         .calc-form, .recipe-form, .comparison-form {
             background: white;
             padding: 1.5rem;
             border-radius: var(--radius-lg);
             box-shadow: var(--shadow-sm);
             border: 1px solid var(--gray-200);
             height: 100%;
         }
         
         .calc-form h6, .recipe-form h6, .comparison-form h6 {
             margin-bottom: 1rem;
             font-weight: 600;
             color: var(--gray-900);
         }
         
         .calc-results, .recipe-results, .comparison-results {
             background: white;
             padding: 1.5rem;
             border-radius: var(--radius-lg);
             box-shadow: var(--shadow-sm);
             border: 1px solid var(--gray-200);
             height: 100%;
             min-height: 300px;
         }
         
         .result-placeholder {
             text-align: center;
             padding: 2rem;
             color: var(--gray-500);
         }
         
         .food-result-card {
             background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
             color: white;
             padding: 1.5rem;
             border-radius: var(--radius-lg);
             margin-bottom: 1rem;
         }
         
         .food-result-card h5 {
             margin-bottom: 1rem;
             font-weight: 700;
         }
         
         .nutrition-grid {
             display: grid;
             grid-template-columns: repeat(2, 1fr);
             gap: 1rem;
             margin-bottom: 1rem;
         }
         
         .nutrition-item {
             text-align: center;
             padding: 0.75rem;
             background: rgba(255, 255, 255, 0.2);
             border-radius: var(--radius-sm);
         }
         
         .nutrition-item h6 {
             margin-bottom: 0.25rem;
             font-weight: 600;
         }
         
         .nutrition-item p {
             margin-bottom: 0;
             font-size: 0.875rem;
             opacity: 0.9;
         }
         
         .ingredient-list {
             max-height: 300px;
             overflow-y: auto;
             margin-bottom: 1rem;
         }
         
         .ingredient-item {
             margin-bottom: 0.75rem;
             padding: 0.75rem;
             background: var(--gray-50);
             border-radius: var(--radius-sm);
             border: 1px solid var(--gray-200);
         }
         
         .ingredient-item:last-child {
             margin-bottom: 0;
         }
         
         .remove-ingredient {
             width: 100%;
             height: 100%;
             display: flex;
             align-items: center;
             justify-content: center;
         }
         
         .comparison-items {
             margin-bottom: 1rem;
         }
         
         .comparison-item {
             margin-bottom: 1rem;
             padding: 1rem;
             background: var(--gray-50);
             border-radius: var(--radius-md);
             border: 1px solid var(--gray-200);
         }
         
         .comparison-item h6 {
             margin-bottom: 0.75rem;
             font-weight: 600;
             color: var(--gray-900);
         }
         
         .comparison-result {
             display: grid;
             grid-template-columns: 1fr 1fr;
             gap: 1rem;
             margin-bottom: 1rem;
         }
         
         .comparison-food {
             padding: 1rem;
             border-radius: var(--radius-md);
             text-align: center;
         }
         
         .comparison-food.food1 {
             background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
             color: white;
         }
         
         .comparison-food.food2 {
             background: linear-gradient(135deg, var(--success-color), var(--info-color));
             color: white;
         }
         
         .comparison-food h6 {
             margin-bottom: 0.5rem;
             font-weight: 600;
         }
         
         .comparison-food .nutrition-item {
             background: rgba(255, 255, 255, 0.2);
             margin-bottom: 0.5rem;
         }
         
         .comparison-summary {
             background: var(--gray-50);
             padding: 1rem;
             border-radius: var(--radius-md);
             border: 1px solid var(--gray-200);
         }
         
         .comparison-summary h6 {
             margin-bottom: 0.75rem;
             font-weight: 600;
             color: var(--gray-900);
         }
         
         .summary-item {
             display: flex;
             justify-content: space-between;
             align-items: center;
             padding: 0.5rem 0;
             border-bottom: 1px solid var(--gray-200);
         }
         
         .summary-item:last-child {
             border-bottom: none;
         }
         
         .summary-item span {
             font-weight: 500;
             color: var(--gray-700);
         }
         
         .summary-item strong {
             font-weight: 700;
             color: var(--gray-900);
         }
         
         .recipe-summary {
             background: linear-gradient(135deg, var(--warning-color), var(--info-color));
             color: white;
             padding: 1.5rem;
             border-radius: var(--radius-lg);
             margin-bottom: 1rem;
         }
         
         .recipe-summary h5 {
             margin-bottom: 1rem;
             font-weight: 700;
         }
         
         .recipe-ingredients {
             background: var(--gray-50);
             padding: 1rem;
             border-radius: var(--radius-md);
             border: 1px solid var(--gray-200);
             max-height: 200px;
             overflow-y: auto;
         }
         
         .recipe-ingredient {
             display: flex;
             justify-content: space-between;
             align-items: center;
             padding: 0.5rem 0;
             border-bottom: 1px solid var(--gray-200);
         }
         
         .recipe-ingredient:last-child {
             border-bottom: none;
         }
         
         .recipe-ingredient span {
             font-weight: 500;
             color: var(--gray-700);
         }
         
         .recipe-ingredient strong {
             font-weight: 600;
             color: var(--gray-900);
         }
         
         /* Breakfast Options Styles */
         .breakfast-options {
             background: var(--gray-50);
             padding: 1.5rem;
             border-radius: var(--radius-lg);
             border: 1px solid var(--gray-200);
         }
         
         .breakfast-categories {
             margin-top: 1rem;
         }
         
         .breakfast-category {
             background: white;
             padding: 1rem;
             border-radius: var(--radius-md);
             border: 1px solid var(--gray-200);
         }
         
         .category-title {
             margin-bottom: 1rem;
             font-weight: 600;
             color: var(--gray-900);
             border-bottom: 2px solid var(--gray-200);
             padding-bottom: 0.5rem;
         }
         
         .category-title small {
             display: block;
             font-weight: 400;
             margin-top: 0.25rem;
         }
         
         .breakfast-btn {
             height: auto;
             padding: 1rem;
             border-radius: var(--radius-md);
             transition: all 0.3s ease;
             border: 2px solid transparent;
         }
         
         .breakfast-btn:hover {
             transform: translateY(-2px);
             box-shadow: var(--shadow-md);
             border-color: currentColor;
         }
         
         .breakfast-item {
             text-align: center;
         }
         
         .breakfast-item i {
             font-size: 1.5rem;
             margin-bottom: 0.5rem;
             display: block;
             color: currentColor;
         }
         
         .breakfast-item span {
             display: block;
             font-weight: 600;
             margin-bottom: 0.25rem;
             color: var(--gray-900);
         }
         
         .breakfast-item small {
             display: block;
             color: var(--gray-600);
             font-size: 0.8rem;
         }
         
         .breakfast-combo-btn {
             height: auto;
             padding: 1.25rem;
             border-radius: var(--radius-md);
             transition: all 0.3s ease;
             border: 2px solid transparent;
         }
         
         .breakfast-combo-btn:hover {
             transform: translateY(-2px);
             box-shadow: var(--shadow-md);
             border-color: currentColor;
         }
         
         .breakfast-combo {
             text-align: center;
         }
         
         .breakfast-combo i {
             font-size: 1.75rem;
             margin-bottom: 0.75rem;
             display: block;
             color: currentColor;
         }
         
         .breakfast-combo span {
             display: block;
             font-weight: 700;
             margin-bottom: 0.5rem;
             color: var(--gray-900);
             font-size: 1.1rem;
         }
         
         .breakfast-combo small {
             display: block;
             color: var(--gray-600);
             margin-bottom: 0.5rem;
             font-size: 0.9rem;
         }
         
         .combo-calories {
             background: rgba(0, 0, 0, 0.1);
             padding: 0.5rem;
             border-radius: var(--radius-sm);
             font-weight: 600;
             color: var(--gray-800);
             font-size: 0.85rem;
         }
         
         .breakfast-btn.btn-outline-primary:hover {
             background: var(--primary-color);
             color: white;
         }
         
         .breakfast-btn.btn-outline-success:hover {
             background: var(--success-color);
             color: white;
         }
         
         .breakfast-btn.btn-outline-warning:hover {
             background: var(--warning-color);
             color: white;
         }
         
         .breakfast-combo-btn.btn-outline-info:hover {
             background: var(--info-color);
             color: white;
         }
         
         .breakfast-btn.btn-outline-primary:hover .breakfast-item i,
         .breakfast-btn.btn-outline-primary:hover .breakfast-item span,
         .breakfast-btn.btn-outline-primary:hover .breakfast-item small {
             color: white;
         }
         
         .breakfast-btn.btn-outline-success:hover .breakfast-item i,
         .breakfast-btn.btn-outline-success:hover .breakfast-item span,
         .breakfast-btn.btn-outline-success:hover .breakfast-item small {
             color: white;
         }
         
         .breakfast-btn.btn-outline-warning:hover .breakfast-item i,
         .breakfast-btn.btn-outline-warning:hover .breakfast-item span,
         .breakfast-btn.btn-outline-warning:hover .breakfast-item small {
             color: white;
         }
         
         .breakfast-combo-btn.btn-outline-info:hover .breakfast-combo i,
         .breakfast-combo-btn.btn-outline-info:hover .breakfast-combo span,
         .breakfast-combo-btn.btn-outline-info:hover .breakfast-combo small,
         .breakfast-combo-btn.btn-outline-info:hover .combo-calories {
             color: white;
         }
    </style>
    
    <script>
        // Dashboard-specific functions
        function startQuickWorkout() {
            showNotification('info', 'Starting quick workout...', 'Let\'s get moving! 💪');
            setTimeout(() => {
                window.location.href = 'workouts.php';
            }, 1000);
        }
        
        function logProgress() {
            showNotification('info', 'Opening progress log...', '📊 Progress Tracker');
            // In a real app, this would open a modal or redirect to progress page
        }
        
        function viewWorkouts() {
            window.location.href = 'workouts.php';
        }
        
        function viewAchievements() {
            window.location.href = 'achievements.php';
        }
        
        // Initialize dashboard when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize counters with real data
            function initializeCounters() {
                const counters = document.querySelectorAll('.counter');
                counters.forEach(counter => {
                    const target = parseInt(counter.getAttribute('data-target'));
                    const duration = 2000;
                    const increment = target / (duration / 16);
                    let current = 0;
                    
                    const updateCounter = () => {
                        current += increment;
                        if (current < target) {
                            counter.textContent = Math.floor(current);
                            requestAnimationFrame(updateCounter);
                        } else {
                            counter.textContent = target;
                        }
                    };
                    updateCounter();
                });
            }
            
            // Initialize counters
            initializeCounters();
            
            // Load charts with PHP data
            loadProgressChart();
            loadCaloriesChart();

            // Populate Recent Activity from localStorage
            try {
                const raKey = 'recentActivities';
                const raw = localStorage.getItem(raKey) || '[]';
                const items = JSON.parse(raw) || [];
                const container = document.querySelector('.activity-list');
                if (container && items.length) {
                    container.innerHTML = items.map(item => `
                        <div class=\"activity-item\">
                            <div class=\"activity-icon bg-${item.type || 'primary'}\">
                                <i class=\"fas fa-check\"></i>
                            </div>
                            <div class=\"activity-content\">
                                <h6 class=\"mb-1\">${item.title}</h6>
                                <p class=\"text-muted mb-0\">${item.details}</p>
                                <small class=\"text-muted\">${new Date(item.ts).toLocaleString()}</small>
                            </div>
                        </div>
                    `).join('');
                }
            } catch (_) { /* ignore */ }

            // Load progress chart with PHP data
            function loadProgressChart() {
                const progressCanvas = document.getElementById('progressChart');
                if (!progressCanvas || !window.Chart) return;
                
                const labels = <?php echo json_encode($progressLabels); ?>;
                const data = <?php echo json_encode($progressChartData); ?>;
                createProgressChart(progressCanvas, labels, data);
            }
            
            // Create progress chart
            function createProgressChart(canvas, labels, data) {
                const ctx = canvas.getContext('2d');
                const gradient = ctx.createLinearGradient(0, 0, 0, 200);
                gradient.addColorStop(0, 'rgba(99,102,241,0.35)');
                gradient.addColorStop(1, 'rgba(99,102,241,0.05)');

                new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Workouts Completed',
                            data,
                            backgroundColor: gradient,
                            borderColor: '#6366f1',
                            borderWidth: 3,
                            tension: 0.35,
                            pointBackgroundColor: '#6366f1',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            fill: true,
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false },
                            tooltip: { mode: 'index', intersect: false }
                        },
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            x: {
                                grid: { display: false }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 },
                                grid: { color: 'rgba(0,0,0,0.08)' }
                            }
                        }
                    }
                });
            }
            
            // Load calories chart with PHP data
            function loadCaloriesChart() {
                const caloriesCanvas = document.getElementById('caloriesChart');
                if (!caloriesCanvas || !window.Chart) return;
                
                const labels = <?php echo json_encode($caloriesLabels); ?>;
                const data = <?php echo json_encode($caloriesChartData); ?>;
                const colors = <?php echo json_encode($caloriesColors); ?>;
                createCaloriesChart(caloriesCanvas, labels, data, colors);
            }
            
            // Create calories chart
            function createCaloriesChart(canvas, labels, data, colors) {
                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Calories Burned',
                            data,
                            backgroundColor: colors,
                            borderColor: colors,
                            borderWidth: 1,
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        aspectRatio: 1.2,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.y + ' calories';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 50
                                },
                                grid: { color: 'rgba(0,0,0,0.08)' }
                            }
                        }
                    }
                });
            }
            
            // Load recent activity with PHP data
            function loadRecentActivity() {
                const container = document.querySelector('.activity-list');
                if (container) {
                    const activities = <?php echo json_encode($recentActivities); ?>;
                    if (activities.length > 0) {
                        container.innerHTML = activities.map(item => `
                            <div class="activity-item">
                                <div class="activity-icon bg-primary">
                                    <i class="fas fa-dumbbell"></i>
                                </div>
                                <div class="activity-content">
                                    <h6 class="mb-1">${item.workout_name}</h6>
                                    <p class="text-muted mb-0">${item.duration_minutes} min • ${item.calories_burned} cal</p>
                                    <small class="text-muted">${new Date(item.created_at).toLocaleString()}</small>
                                </div>
                            </div>
                        `).join('');
                    }
                }
            }
        });
    </script>

    <!-- BMR Information Modal -->
    <div class="modal fade" id="bmrInfoModal" tabindex="-1" aria-labelledby="bmrInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content understanding-bmr-card">
                <div class="modal-header">
                    <h5 class="modal-title" id="bmrInfoModalLabel">
                        <i class="fas fa-info-circle me-2"></i>Understanding BMR & TDEE
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-fire text-primary me-2"></i>BMR (Basal Metabolic Rate)</h6>
                            <p class="small">The number of calories your body burns at rest to maintain basic functions like breathing, circulation, and cell production.</p>
                            <ul class="small">
                                <li>Accounts for 60-75% of daily calorie burn</li>
                                <li>Calculated using Mifflin-St Jeor equation</li>
                                <li>Factors: Age, gender, weight, height</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-running text-success me-2"></i>TDEE (Total Daily Energy Expenditure)</h6>
                            <p class="small">Total calories burned per day including BMR + physical activity + digestion.</p>
                            <ul class="small">
                                <li>BMR × Activity Level Multiplier</li>
                                <li>Used to determine calorie goals</li>
                                <li>Updated based on lifestyle</li>
                            </ul>
                        </div>
                    </div>
                    <hr>
                    <h6><i class="fas fa-calculator text-warning me-2"></i>Mifflin-St Jeor Equation</h6>
                    <div class="equation-box">
                        <p class="mb-2"><strong>Men:</strong> BMR = (10 × weight) + (6.25 × height) - (5 × age) + 5</p>
                        <p class="mb-0"><strong>Women:</strong> BMR = (10 × weight) + (6.25 × height) - (5 × age) - 161</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Meal Timing Modal -->
    <div class="modal fade" id="mealTimingModal" tabindex="-1" aria-labelledby="mealTimingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mealTimingModalLabel">
                        <i class="fas fa-clock me-2"></i>Optimal Meal Timing
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="meal-timing-schedule">
                        <div class="timing-item">
                            <div class="timing-time">7:00 AM</div>
                            <div class="timing-meal">
                                <h6>Breakfast</h6>
                                <p class="small">Start your day with protein and complex carbs</p>
                            </div>
                        </div>
                        <div class="timing-item">
                            <div class="timing-time">10:00 AM</div>
                            <div class="timing-meal">
                                <h6>Mid-Morning Snack</h6>
                                <p class="small">Light snack to maintain energy</p>
                            </div>
                        </div>
                        <div class="timing-item">
                            <div class="timing-time">1:00 PM</div>
                            <div class="timing-meal">
                                <h6>Lunch</h6>
                                <p class="small">Balanced meal with all macronutrients</p>
                            </div>
                        </div>
                        <div class="timing-item">
                            <div class="timing-time">4:00 PM</div>
                            <div class="timing-meal">
                                <h6>Pre-Workout Snack</h6>
                                <p class="small">Quick energy for training</p>
                            </div>
                        </div>
                        <div class="timing-item">
                            <div class="timing-time">7:00 PM</div>
                            <div class="timing-meal">
                                <h6>Dinner</h6>
                                <p class="small">Recovery meal with lean protein</p>
                            </div>
                        </div>
                        <div class="timing-item">
                            <div class="timing-time">9:00 PM</div>
                            <div class="timing-meal">
                                <h6>Evening Snack</h6>
                                <p class="small">Optional light snack if needed</p>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h6><i class="fas fa-lightbulb text-info me-2"></i>Key Principles</h6>
                    <ul class="small">
                        <li>Eat every 3-4 hours to maintain stable blood sugar</li>
                        <li>Consume protein with every meal</li>
                        <li>Time carbs around workouts for better performance</li>
                        <li>Finish eating 2-3 hours before bedtime</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Details Modal -->
    <div class="modal fade" id="progressModal" tabindex="-1" aria-labelledby="progressModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="progressModalLabel">
                        <i class="fas fa-chart-line me-2"></i>Detailed Progress Analysis
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Weekly Progress</h6>
                            <div style="height: 300px;">
                                <canvas id="weeklyProgressChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Macro Distribution</h6>
                            <div style="height: 300px;">
                                <canvas id="macroProgressChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
