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

	// Get top leaders among users
	// We'll rank them by workout completion count, then by last activity
$stmt = $pdo->prepare("
    SELECT 
        u.username, 
        u.first_name, 
        u.last_name, 
			u.updated_at,
        COALESCE(SUM(CASE WHEN DATE(w.created_at) = CURDATE() THEN 1 ELSE 0 END), 0) as workout_count,
        COALESCE(SUM(CASE WHEN DATE(w.created_at) = CURDATE() THEN w.calories_burned ELSE 0 END), 0) as total_calories,
			COALESCE(MAX(w.created_at), u.updated_at) as last_activity
    FROM users u
    LEFT JOIN workout_logs w ON u.id = w.user_id
		WHERE u.updated_at IS NOT NULL
		GROUP BY u.id, u.username, u.first_name, u.last_name, u.updated_at
    ORDER BY 
        workout_count DESC,
        total_calories DESC,
        last_activity DESC
    LIMIT 10
");
$stmt->execute();
$topLeaders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workouts - HealthMate</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="workouts.php">
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
                                <i class="fas fa-user-circle fa-lg"></i>
                            </div>
                            <span><?php echo htmlspecialchars($user['first_name']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" id="logoutBtn"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-5 pt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-stats">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <h2 class="mb-2">💪 Your Workouts</h2>
                            <p class="mb-0 opacity-75">Personalized workout plans based on your fitness goal: <strong><?php echo ucfirst(str_replace('_', ' ', $user['fitness_goal'])); ?></strong></p>
                        </div>
                        <div class="col-lg-4 text-lg-end">
                            <div class="d-flex align-items-center justify-content-lg-end">
                                <div class="me-3 text-center">
                                    <div class="fw-bold fs-2" id="workoutsCompleted">0</div>
                                    <small class="opacity-75">Workouts Completed</small>
                        </div>
                                <div class="avatar-large">
                                    <i class="fas fa-dumbbell fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Workout Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-lg-8">
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-primary active" data-filter="all">
                                        <i class="fas fa-th-large me-1"></i>All Workouts
                                    </button>
                                    <button class="btn btn-outline-primary" data-filter="cardio">
                                        <i class="fas fa-running me-1"></i>Cardio
                                    </button>
                                    <button class="btn btn-outline-primary" data-filter="strength">
                                        <i class="fas fa-dumbbell me-1"></i>Strength
                                    </button>
                                    <button class="btn btn-outline-primary" data-filter="flexibility">
                                        <i class="fas fa-child me-1"></i>Flexibility
                                    </button>
                                    <button class="btn btn-outline-primary" data-filter="hiit">
                                        <i class="fas fa-fire me-1"></i>HIIT
                                    </button>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="d-flex gap-2">
                                    <select class="form-select" id="difficultyFilter">
                                        <option value="">All Difficulties</option>
                                        <option value="beginner">Beginner</option>
                                        <option value="intermediate">Intermediate</option>
                                        <option value="advanced">Advanced</option>
                                    </select>
                                    <select class="form-select" id="durationFilter">
                                        <option value="">All Durations</option>
                                        <option value="15">15 min</option>
                                        <option value="30">30 min</option>
                                        <option value="45">45 min</option>
                                        <option value="60">60+ min</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Featured Workout -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-lg featured-workout">
                    <div class="card-body p-0">
                        <div class="row g-0">
                            <div class="col-lg-6">
                                <div class="p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <span class="badge bg-primary me-2">Featured</span>
                                        <span class="badge bg-success">Recommended</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h3 class="mb-0">Complete Body Transformation</h3>
                                        <span class="badge bg-success" id="cbtStatusBadge">In Progress</span>
                                    </div>
                                    <p class="text-muted mb-3">A comprehensive full-body workout designed to build strength, endurance, and muscle tone. Perfect for your fitness goals!</p>

                                    <!-- In‑Progress Functional Panel -->
                                    <div class="p-3 border rounded-3 mb-4" style="background:#fafafa;border-color: var(--gray-200)">
                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                            <div class="d-flex align-items-center gap-3">
                                                <div>
                                                    <div class="small text-muted">Timer</div>
                                                    <div class="fs-4 fw-bold" id="cbtTimer">00:00</div>
                                                    <div class="small text-muted">Elapsed • <span id="cbtElapsed">0</span> / <span id="cbtTotal">45</span> min</div>
                                                </div>
                                                <div>
                                                    <div class="small text-muted">Calories</div>
                                                    <div class="fs-5 fw-semibold"><span id="cbtCalories">0</span> / <span id="cbtCaloriesTarget">400</span> cal</div>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="small text-muted mb-1">Progress</div>
                                                <div class="progress" style="width:260px;height:10px;">
                                                    <div class="progress-bar bg-primary" role="progressbar" id="cbtProgressBar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <div class="small mt-1"><span id="cbtProgressText">0%</span> completed</div>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                                            <button class="btn btn-sm btn-primary" id="cbtStartBtn"><i class="fas fa-play me-1"></i>Start</button>
                                            <button class="btn btn-sm btn-warning" id="cbtPauseBtn" disabled><i class="fas fa-pause me-1"></i>Pause</button>
                                            <button class="btn btn-sm btn-success" id="cbtResumeBtn" disabled><i class="fas fa-play me-1"></i>Resume</button>
                                            <button class="btn btn-sm btn-danger" id="cbtEndBtn" disabled><i class="fas fa-stop me-1"></i>End</button>
                                            <button class="btn btn-sm btn-outline-secondary" id="cbtToggleDetails"><i class="fas fa-chevron-down me-1"></i>Details</button>
                                        </div>

                                        <div class="mt-3" id="cbtDetails" style="display:none;">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <div class="p-3 rounded border h-100">
                                                        <div class="small text-muted">Current Stage</div>
                                                        <div class="fw-bold" id="cbtStage">Warm-up</div>
                                                        <div class="mt-2 small">Stages:</div>
                                                        <div class="d-flex flex-column gap-1 small">
                                                            <div><span class="badge bg-info me-1">Warm-up</span> <span id="cbtWarm">0</span>/<span id="cbtWarmTotal">7</span> min</div>
                                                            <div><span class="badge bg-primary me-1">Main</span> <span id="cbtMain">0</span>/<span id="cbtMainTotal">31</span> min</div>
                                                            <div><span class="badge bg-secondary me-1">Cool-down</span> <span id="cbtCool">0</span>/<span id="cbtCoolTotal">7</span> min</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-8">
                                                    <div class="p-3 rounded border h-100">
                                                        <div class="small text-muted mb-2">Stage Breakdown</div>
                                                        <div class="mb-2">Warm-up
                                                            <div class="progress" style="height:8px;">
                                                                <div class="progress-bar bg-info" id="cbtWarmBar" style="width:0%"></div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">Main
                                                            <div class="progress" style="height:8px;">
                                                                <div class="progress-bar bg-primary" id="cbtMainBar" style="width:0%"></div>
                                                            </div>
                                                        </div>
                                                        <div>Cool-down
                                                            <div class="progress" style="height:8px;">
                                                                <div class="progress-bar bg-secondary" id="cbtCoolBar" style="width:0%"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <div class="col-6">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-clock text-primary me-2"></i>
                                                <span>45 minutes</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-fire text-danger me-2"></i>
                                                <span>400 calories</span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-signal text-warning me-2"></i>
                                                <span>Intermediate</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-dumbbell text-info me-2"></i>
                                                <span>Equipment needed</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-primary btn-lg" onclick="startWorkout('featured')">
                                            <i class="fas fa-play me-2"></i>Start Workout
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="workout-preview">
                                    <div class="workout-image">
                                        <i class="fas fa-dumbbell fa-6x text-white opacity-25"></i>
                                    </div>
                                </div>
                    </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Workout Categories -->
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-3">Workout Categories</h4>
            </div>
        </div>

        <!-- Cardio Workouts -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-gradient-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-running me-2"></i>Cardio Workouts</h5>
                    </div>
                    <div class="card-body">
                        <div class="row" id="cardioWorkouts">
                            <div class="col-lg-4 col-md-6 mb-3">
                                <div class="workout-card" data-category="cardio" data-difficulty="beginner" data-duration="25">
                                    <div class="workout-card-header">
                                        <div class="workout-icon">
                                            <i class="fas fa-running"></i>
                                        </div>
                                        <div class="workout-badge">Beginner</div>
                                    </div>
                                    <div class="workout-card-body">
                                        <h6 class="workout-title">Cardio Foundation</h6>
                                        <p class="workout-desc">Perfect for beginners: walking, light jogging, and basic cardio moves</p>
                                        <div class="workout-stats">
                                            <div class="workout-stat">
                                                <i class="fas fa-clock"></i>
                                                <span>25 min</span>
                                            </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-fire"></i>
                                                <span>180 cal</span>
                                            </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-signal"></i>
                                                <span>Beginner</span>
                                            </div>
                                        </div>
                                        <div class="workout-plan">
                                            <small class="text-muted">Plan: 5 min warm-up • 15 min cardio • 5 min cool-down</small>
                                        </div>
                                    </div>
                                    <div class="workout-card-footer">
                                        <button class="btn btn-primary btn-sm" onclick="startWorkout('cardio-beginner')">
                                            <i class="fas fa-play me-1"></i>Start
                                        </button>
                                        
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-3">
                                <div class="workout-card" data-category="cardio" data-difficulty="intermediate" data-duration="35">
                                    <div class="workout-card-header">
                                        <div class="workout-icon">
                                            <i class="fas fa-heart"></i>
                                        </div>
                                        <div class="workout-badge">Intermediate</div>
                                    </div>
                                    <div class="workout-card-body">
                                        <h6 class="workout-title">Cardio Endurance</h6>
                                        <p class="workout-desc">Build stamina with running, cycling, and interval training</p>
                                        <div class="workout-stats">
                                            <div class="workout-stat">
                                                <i class="fas fa-clock"></i>
                                                <span>35 min</span>
                                            </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-fire"></i>
                                                <span>320 cal</span>
                                            </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-signal"></i>
                                                <span>Intermediate</span>
                                            </div>
                                        </div>
                                        <div class="workout-plan">
                                            <small class="text-muted">Plan: 8 min warm-up • 20 min intervals • 7 min cool-down</small>
                                        </div>
                                    </div>
                                    <div class="workout-card-footer">
                                        <button class="btn btn-primary btn-sm" onclick="startWorkout('cardio-intermediate')">
                                            <i class="fas fa-play me-1"></i>Start
                                        </button>
                                        
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-3">
                                <div class="workout-card" data-category="cardio" data-difficulty="advanced" data-duration="50">
                                    <div class="workout-card-header">
                                        <div class="workout-icon">
                                            <i class="fas fa-bolt"></i>
                                        </div>
                                        <div class="workout-badge">Advanced</div>
                                    </div>
                                    <div class="workout-card-body">
                                        <h6 class="workout-title">Cardio Master</h6>
                                        <p class="workout-desc">High-intensity cardio with advanced techniques and endurance building</p>
                                        <div class="workout-stats">
                                            <div class="workout-stat">
                                                <i class="fas fa-clock"></i>
                                                <span>50 min</span>
                                            </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-fire"></i>
                                                <span>480 cal</span>
                                            </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-signal"></i>
                                                <span>Advanced</span>
                                            </div>
                                        </div>
                                        <div class="workout-plan">
                                            <small class="text-muted">Plan: 10 min warm-up • 30 min intense cardio • 10 min cool-down</small>
                                        </div>
                                    </div>
                                    <div class="workout-card-footer">
                                        <button class="btn btn-primary btn-sm" onclick="startWorkout('cardio-advanced')">
                                            <i class="fas fa-play me-1"></i>Start
                                        </button>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Strength Workouts -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-gradient-success text-white">
                        <h5 class="mb-0"><i class="fas fa-dumbbell me-2"></i>Strength Training</h5>
                    </div>
                    <div class="card-body">
                        <div class="row" id="strengthWorkouts">
                            <div class="col-lg-4 col-md-6 mb-3">
                                <div class="workout-card" data-category="strength" data-difficulty="beginner" data-duration="30">
                                    <div class="workout-card-header">
                                        <div class="workout-icon">
                                            <i class="fas fa-dumbbell"></i>
                                        </div>
                                        <div class="workout-badge">Beginner</div>
                                    </div>
                                    <div class="workout-card-body">
                                        <h6 class="workout-title">Strength Foundation</h6>
                                        <p class="workout-desc">Learn proper form with bodyweight exercises and light weights</p>
                                        <div class="workout-stats">
                                            <div class="workout-stat">
                                                <i class="fas fa-clock"></i>
                                                <span>30 min</span>
                                            </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-fire"></i>
                                                <span>200 cal</span>
                                            </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-signal"></i>
                                                <span>Beginner</span>
                                            </div>
                                        </div>
                                        <div class="workout-plan">
                                            <small class="text-muted">Plan: 8 min warm-up • 15 min strength • 7 min cool-down</small>
                                        </div>
                                    </div>
                                    <div class="workout-card-footer">
                                        <button class="btn btn-primary btn-sm" onclick="startWorkout('strength-beginner')">
                                            <i class="fas fa-play me-1"></i>Start
                                        </button>
                                        
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-3">
                                <div class="workout-card" data-category="strength" data-difficulty="intermediate" data-duration="40">
                                    <div class="workout-card-header">
                                        <div class="workout-icon">
                                            <i class="fas fa-weight-hanging"></i>
                                        </div>
                                        <div class="workout-badge">Intermediate</div>
                                    </div>
                                    <div class="workout-card-body">
                                        <h6 class="workout-title">Muscle Development</h6>
                                        <p class="workout-desc">Build muscle mass with compound movements and progressive overload</p>
                                        <div class="workout-stats">
                                            <div class="workout-stat">
                                                <i class="fas fa-clock"></i>
                                                <span>40 min</span>
                                            </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-fire"></i>
                                                <span>300 cal</span>
                                            </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-signal"></i>
                                                <span>Intermediate</span>
                                            </div>
                                        </div>
                                        <div class="workout-plan">
                                            <small class="text-muted">Plan: 10 min warm-up • 25 min strength • 5 min cool-down</small>
                                        </div>
                                    </div>
                                    <div class="workout-card-footer">
                                        <button class="btn btn-primary btn-sm" onclick="startWorkout('strength-intermediate')">
                                            <i class="fas fa-play me-1"></i>Start
                                        </button>
                                        
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-3">
                                <div class="workout-card" data-category="strength" data-difficulty="advanced" data-duration="55">
                                    <div class="workout-card-header">
                                        <div class="workout-icon">
                                            <i class="fas fa-barbell"></i>
                                        </div>
                                        <div class="workout-badge">Advanced</div>
                                    </div>
                                    <div class="workout-card-body">
                                        <h6 class="workout-title">Power & Strength</h6>
                                        <p class="workout-desc">Advanced compound movements, heavy lifting, and power development</p>
                                        <div class="workout-stats">
                                            <div class="workout-stat">
                                                <i class="fas fa-clock"></i>
                                                <span>55 min</span>
                                            </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-fire"></i>
                                                <span>420 cal</span>
                                            </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-signal"></i>
                                                <span>Advanced</span>
                                </div>
                            </div>
                                        <div class="workout-plan">
                                            <small class="text-muted">Plan: 12 min warm-up • 35 min strength • 8 min cool-down</small>
                                </div>
                            </div>
                                    <div class="workout-card-footer">
                                        <button class="btn btn-primary btn-sm" onclick="startWorkout('strength-advanced')">
                                            <i class="fas fa-play me-1"></i>Start
                                        </button>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- HIIT Workouts -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header style="background: #ffc107 !important; background-image: none !important;" text-white">
                        <h5 class="mb-0"><i class="fas fa-fire me-2"></i>HIIT Workouts</h5>
                    </div>
                    <div class="card-body">
                        <div class="row" id="hiitWorkouts">
                            <div class="col-lg-4 col-md-6 mb-3">
                                <div class="workout-card" data-category="hiit" data-difficulty="beginner" data-duration="20">
                                    <div class="workout-card-header">
                                        <div class="workout-icon">
                                            <i class="fas fa-fire"></i>
                                        </div>
                                        <div class="workout-badge">Beginner</div>
                                    </div>
                                    <div class="workout-card-body">
                                        <h6 class="workout-title">HIIT Introduction</h6>
                                        <p class="workout-desc">Gentle intervals with longer rest periods, perfect for HIIT beginners</p>
                                        <div class="workout-stats">
                                            <div class="workout-stat">
                                                <i class="fas fa-clock"></i>
                                                <span>20 min</span>
                                            </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-fire"></i>
                                                <span>180 cal</span>
                                            </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-signal"></i>
                                                <span>Beginner</span>
                                            </div>
                                        </div>
                                        <div class="workout-plan">
                                            <small class="text-muted">Plan: 5 min warm-up • 10 min intervals • 5 min cool-down</small>
                                        </div>
                                        </div>
                                    <div class="workout-card-footer">
                                        <button class="btn btn-primary btn-sm" onclick="startWorkout('hiit-beginner')">
                                            <i class="fas fa-play me-1"></i>Start
                                        </button>
                                        
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-3">
                                <div class="workout-card" data-category="hiit" data-difficulty="intermediate" data-duration="30">
                                    <div class="workout-card-header">
                                        <div class="workout-icon">
                                            <i class="fas fa-bolt"></i>
                                        </div>
                                        <div class="workout-badge">Intermediate</div>
                                    </div>
                                    <div class="workout-card-body">
                                        <h6 class="workout-title">HIIT Burn</h6>
                                        <p class="workout-desc">High-intensity intervals with balanced work-to-rest ratios</p>
                                        <div class="workout-stats">
                                            <div class="workout-stat">
                                                <i class="fas fa-clock"></i>
                                                <span>30 min</span>
                                            </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-fire"></i>
                                                <span>350 cal</span>
                                            </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-signal"></i>
                                                <span>Intermediate</span>
                                            </div>
                                        </div>
                                        <div class="workout-plan">
                                            <small class="text-muted">Plan: 8 min warm-up • 18 min intervals • 4 min cool-down</small>
                                        </div>
                                        </div>
                                    <div class="workout-card-footer">
                                        <button class="btn btn-primary btn-sm" onclick="startWorkout('hiit-intermediate')">
                                            <i class="fas fa-play me-1"></i>Start
                                        </button>
                                        
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-3">
                                <div class="workout-card" data-category="hiit" data-difficulty="advanced" data-duration="40">
                                    <div class="workout-card-header">
                                        <div class="workout-icon">
                                            <i class="fas fa-explosion"></i>
                                        </div>
                                        <div class="workout-badge">Advanced</div>
                                    </div>
                                    <div class="workout-card-body">
                                        <h6 class="workout-title">HIIT Inferno</h6>
                                        <p class="workout-desc">Maximum intensity intervals with minimal rest for elite fitness</p>
                                        <div class="workout-stats">
                                            <div class="workout-stat">
                                                <i class="fas fa-clock"></i>
                                                <span>40 min</span>
                                            </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-fire"></i>
                                                <span>500 cal</span>
                                            </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-signal"></i>
                                                <span>Advanced</span>
                                            </div>
                                        </div>
                                        <div class="workout-plan">
                                            <small class="text-muted">Plan: 10 min warm-up • 25 min intervals • 5 min cool-down</small>
                                        </div>
                                        </div>
                                    <div class="workout-card-footer">
                                        <button class="btn btn-primary btn-sm" onclick="startWorkout('hiit-advanced')">
                                            <i class="fas fa-play me-1"></i>Start
                                        </button>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flexibility & Recovery -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-gradient-info text-white">
                        <h5 class="mb-0"><i class="fas fa-child me-2"></i>Flexibility & Recovery</h5>
                    </div>
                    <div class="card-body">
                        <div class="row" id="flexibilityWorkouts">
                            <div class="col-lg-4 col-md-6 mb-3">
                                <div class="workout-card" data-category="flexibility" data-difficulty="beginner" data-duration="25">
                                    <div class="workout-card-header">
                                        <div class="workout-icon">
                                            <i class="fas fa-child"></i>
                                        </div>
                                        <div class="workout-badge">Beginner</div>
                                    </div>
                                    <div class="workout-card-body">
                                        <h6 class="workout-title">Flexibility Basics</h6>
                                        <p class="workout-desc">Gentle stretching and mobility exercises for beginners</p>
                                        <div class="workout-stats">
                                            <div class="workout-stat">
                                                <i class="fas fa-clock"></i>
                                                <span>25 min</span>
                                            </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-fire"></i>
                                                <span>100 cal</span>
                                            </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-signal"></i>
                                                <span>Beginner</span>
                                            </div>
                                        </div>
                                        <div class="workout-plan">
                                            <small class="text-muted">Plan: 8 min warm-up • 12 min stretching • 5 min cool-down</small>
                                        </div>
                                    </div>
                                    <div class="workout-card-footer">
                                        <button class="btn btn-primary btn-sm" onclick="startWorkout('flexibility-beginner')">
                                            <i class="fas fa-play me-1"></i>Start
                                        </button>
                                        
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-3">
                                <div class="workout-card" data-category="flexibility" data-difficulty="intermediate" data-duration="35">
                                    <div class="workout-card-header">
                                        <div class="workout-icon">
                                            <i class="fas fa-yoga"></i>
                                        </div>
                                        <div class="workout-badge">Intermediate</div>
                                    </div>
                                    <div class="workout-card-body">
                                        <h6 class="workout-title">Yoga & Mobility</h6>
                                        <p class="workout-desc">Intermediate yoga flows with advanced stretching techniques</p>
                                        <div class="workout-stats">
                                            <div class="workout-stat">
                                                <i class="fas fa-clock"></i>
                                                <span>35 min</span>
                                            </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-fire"></i>
                                                <span>140 cal</span>
                                            </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-signal"></i>
                                                <span>Intermediate</span>
                                            </div>
                                        </div>
                                        <div class="workout-plan">
                                            <small class="text-muted">Plan: 10 min warm-up • 20 min yoga • 5 min cool-down</small>
                                        </div>
                                    </div>
                                    <div class="workout-card-footer">
                                        <button class="btn btn-primary btn-sm" onclick="startWorkout('flexibility-intermediate')">
                                            <i class="fas fa-play me-1"></i>Start
                                        </button>
                                        
            </div>
        </div>
    </div>
                            <div class="col-lg-4 col-md-6 mb-3">
                                <div class="workout-card" data-category="flexibility" data-difficulty="advanced" data-duration="50">
                                    <div class="workout-card-header">
                                        <div class="workout-icon">
                                            <i class="fas fa-meditation"></i>
                </div>
                                        <div class="workout-badge">Advanced</div>
                    </div>
                                    <div class="workout-card-body">
                                        <h6 class="workout-title">Advanced Flexibility</h6>
                                        <p class="workout-desc">Advanced stretching, mobility work, and recovery techniques</p>
                                        <div class="workout-stats">
                                            <div class="workout-stat">
                                                <i class="fas fa-clock"></i>
                                                <span>50 min</span>
                        </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-fire"></i>
                                                <span>160 cal</span>
                        </div>
                                            <div class="workout-stat">
                                                <i class="fas fa-signal"></i>
                                                <span>Advanced</span>
                        </div>
                    </div>
                                        <div class="workout-plan">
                                            <small class="text-muted">Plan: 12 min warm-up • 30 min flexibility • 8 min cool-down</small>
                                        </div>
                    </div>
                                    <div class="workout-card-footer">
                                        <button class="btn btn-primary btn-sm" onclick="startWorkout('flexibility-advanced')">
                                            <i class="fas fa-play me-1"></i>Start
                        </button>
                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performers Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-lg top-performers-card">
                    <div class="card-header text-white">
                        <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Top Leaders</h5>
                        <small class="opacity-75">Ranked by workout performance among logged-in users</small>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($topLeaders)): ?>
                            <div class="row">
                                <?php foreach ($topLeaders as $index => $leader): ?>
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <div class="d-flex align-items-center p-3 rounded-3 performer-item">
                                            <div class="performer-avatar me-3">
                                                <svg class="user-anim" width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                    <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.8"/>
                                                    <path d="M4 20c0-4.418 3.582-8 8-8s8 3.582 8 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                                </svg>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 text-dark">
                                                    <?php 
                                                    $displayName = !empty($leader['first_name']) ? 
                                                        $leader['first_name'] . ' ' . $leader['last_name'] : 
                                                        $leader['username'];
                                                    echo htmlspecialchars($displayName);
                                                    ?>
                                                </h6>
                                                <div class="d-flex flex-wrap gap-2 mb-1">
                                                    <small class="badge bg-primary">
                                                        <i class="fas fa-dumbbell me-1"></i>
                                                        <?php echo $leader['workout_count']; ?> workouts
                                                    </small>
                                                    <small class="badge bg-success">
                                                        <i class="fas fa-fire me-1"></i>
                                                        <?php echo number_format($leader['total_calories']); ?> cal
                                                    </small>
                                                </div>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    Last active: <?php echo date('M j, Y', strtotime($leader['last_activity'])); ?>
                                                </small>
                                            </div>
                                            <div class="performer-rank">
                                                <?php echo $index + 1; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-trophy fa-3x text-white mb-3"></i>
                                <h6 class="text-white">No leaders yet</h6>
                                <p class="text-white opacity-75 mb-0">Complete your first workout to join the leaderboard!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <style>
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
        
        .featured-workout {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            overflow: hidden;
        }
        
        .workout-preview {
            background: rgba(255, 255, 255, 0.1);
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 300px;
        }
        
        .workout-image {
            text-align: center;
        }
        
        .bg-gradient-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        }
        
        /* Top Performers Section Styles */
        .top-performers-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .performer-item {
            transition: all 0.3s ease;
            border: 1px solid #e9ecef !important;
            background: white !important;
        }
        
        /* hover removed */
        
        .performer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
        }
        .performer-avatar .user-anim {
            animation: pulseGlow 2s ease-in-out infinite;
        }
        @keyframes pulseGlow {
            0% { opacity: 0.8; filter: drop-shadow(0 0 0 rgba(255,255,255,0)); }
            50% { opacity: 1; filter: drop-shadow(0 0 6px rgba(255,255,255,0.6)); }
            100% { opacity: 0.8; filter: drop-shadow(0 0 0 rgba(255,255,255,0)); }
        }
        
        .performer-rank {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        /* Special styling for top 3 leaders */
        .performer-item:nth-child(1) .performer-avatar {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #333;
        }
        
        .performer-item:nth-child(2) .performer-avatar {
            background: linear-gradient(135deg, #c0c0c0, #e8e8e8);
            color: #333;
        }
        
        .performer-item:nth-child(3) .performer-avatar {
            background: linear-gradient(135deg, #cd7f32, #daa520);
            color: white;
        }
        
        .performer-item:nth-child(1) .performer-rank {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #333;
        }
        
        .performer-item:nth-child(2) .performer-rank {
            background: linear-gradient(135deg, #c0c0c0, #e8e8e8);
            color: #333;
        }
        
        .performer-item:nth-child(3) .performer-rank {
            background: linear-gradient(135deg, #cd7f32, #daa520);
            color: white;
        }
        
        .leader-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        .bg-gradient-success {
            background: linear-gradient(135deg, var(--secondary-color), var(--secondary-dark));
        }
        
        .style="background: #ffc107 !important; background-image: none !important;" {
            background: linear-gradient(135deg, var(--accent-color), var(--accent-dark));
        }
        
        .bg-gradient-info {
            background: linear-gradient(135deg, var(--info-color), #1d4ed8);
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
        
        .workout-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            border: 1px solid var(--gray-200);
            transition: var(--transition-normal);
            cursor: pointer;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        /* hover removed */
        
        .workout-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .workout-icon {
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
        
        .workout-badge {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-md);
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .workout-card-body {
            flex: 1;
        }
        
        .workout-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--gray-800);
        }
        
        .workout-desc {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .workout-stats {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .workout-stat {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--gray-600);
        }
        
        .workout-stat i {
            width: 16px;
            color: var(--primary-color);
        }
        
        /* Darker text for Cardio workout cards */
        #cardioWorkouts .workout-title {
            color: #111827; /* gray-900 */
        }
        #cardioWorkouts .workout-desc {
            color: #374151; /* gray-700 */
        }
        #cardioWorkouts .workout-stat {
            color: #374151; /* gray-700 */
        }
        
        /* Darker text for Strength workout cards */
        #strengthWorkouts .workout-title {
            color: #111827; /* gray-900 */
        }
        #strengthWorkouts .workout-desc {
            color: #374151; /* gray-700 */
        }
        #strengthWorkouts .workout-stat {
            color: #374151; /* gray-700 */
        }
        
        /* Darker text for HIIT workout cards */
        #hiitWorkouts .workout-title {
            color: #111827; /* gray-900 */
        }
        #hiitWorkouts .workout-desc {
            color: #374151; /* gray-700 */
        }
        #hiitWorkouts .workout-stat {
            color: #374151; /* gray-700 */
        }
        
        /* Darker text for Flexibility & Recovery workout cards */
        #flexibilityWorkouts .workout-title {
            color: #111827; /* gray-900 */
        }
        #flexibilityWorkouts .workout-desc {
            color: #374151; /* gray-700 */
        }
        #flexibilityWorkouts .workout-stat {
            color: #374151; /* gray-700 */
        }
        
        .workout-card-footer {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }
        
        .workout-card.hidden {
            display: none;
        }
        
        .workout-plan {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: none !important;
            background-image: none !important;
            border-radius: var(--radius-md);
            border-left: 3px solid var(--primary-color);
        }
        
        .workout-plan small {
            color: var(--gray-800) !important;
            font-weight: 600;
        }
        
        /* Workout Plan Modal Styles */
        .workout-section {
            border-left: 4px solid #e9ecef;
            padding-left: 1rem;
        }
        
        .workout-section h6 {
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .exercise-item {
            padding: 0.5rem;
            border-radius: 0.375rem;
            background-color: #f8f9fa;
            margin-bottom: 0.5rem;
            transition: all 0.2s ease;
        }
        
        /* hover removed */
        
        .exercise-item .badge {
            font-size: 0.75rem;
            min-width: 60px;
        }
        
        .workout-section:nth-child(1) {
            border-left-color: #6366f1;
        }
        
        .workout-section:nth-child(2) {
            border-left-color: #10b981;
        }
        
        .workout-section:nth-child(3) {
            border-left-color: #f59e0b;
        }
        
        .workout-section:nth-child(4) {
            border-left-color: #06b6d4;
        }
        
        .modal-xl {
            max-width: 1200px;
        }
        
        .bg-gradient-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        }
        
        .bg-gradient-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .style="background: #ffc107 !important; background-image: none !important;" {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        .bg-gradient-info {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
        }
        
        .tip-category h6 {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .tip-category ul li {
            font-size: 0.85rem;
            line-height: 1.4;
        }
        
        .alert {
            border: none;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            padding: 0.75rem;
        }
        
        .alert-warning {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .alert-info {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .text-purple {
            color: #8b5cf6 !important;
        }
        
        .fw-bold {
            font-weight: 600 !important;
        }
        
        /* Preview Modal Styles */
        .preview-section h6 {
            font-weight: 600;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.5rem;
        }
        
        .preview-exercises {
            margin-left: 1rem;
        }
        
        .preview-exercise {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            background-color: #f8f9fa;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }
        
        /* hover removed */
        
        .preview-exercise i {
            font-size: 1.1rem;
            min-width: 20px;
        }
        
        .preview-stats {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 0.5rem;
            padding: 1rem;
        }
        
        .stat-item {
            padding: 0.5rem;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .preview-highlight {
            border-left: 4px solid #f59e0b;
        }
        
        .preview-highlight h6 {
            color: #f59e0b;
            font-weight: 600;
        }

        /* Dark theme for preview modal */
        .preview-dark {
            background-color: #0f172a;
            color: #ffffff;
            background-image: none !important;
        }
        .preview-dark .modal-content,
        .preview-dark .card,
        .preview-dark .list-group,
        .preview-dark .list-group-item,
        .preview-dark .preview-stats,
        .preview-dark .preview-highlight {
            background-image: none !important;
        }
        .preview-dark .modal-header {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .preview-dark .modal-footer {
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .preview-dark .preview-section h6,
        .preview-dark .stat-label,
        .preview-dark .stat-number,
        .preview-dark .preview-exercise span {
            color: #ffffff !important;
        }
        .preview-dark .preview-exercise {
            background-color: rgba(255,255,255,0.08);
        }
        /* hover removed */
        .preview-dark .preview-stats {
            background: linear-gradient(135deg, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0.12) 100%);
        }
        .preview-dark .preview-highlight {
            background-color: rgba(255,255,255,0.06);
        }
        .preview-dark .text-muted {
            color: rgba(255,255,255,0.8) !important;
        }
        .preview-dark .card {
            background-color: rgba(255,255,255,0.06);
            color: #ffffff;
        }
        .preview-dark .list-group-item {
            background-color: rgba(255,255,255,0.06);
            color: #ffffff;
            border-color: rgba(255,255,255,0.1);
        }

        /* Active Workout (in-session) */
        .active-workout {
            background: linear-gradient(135deg, #111827, #1f2937);
            color: #fff;
        }
        .active-workout .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            color: #fff;
        }
        .active-workout .chip {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .5rem .75rem;
            background: rgba(255,255,255,0.06);
            border-radius: 9999px;
            font-size: .9rem;
        }
        .active-workout .progress {
            height: 10px;
            background: rgba(255,255,255,0.08);
            border-radius: 9999px;
            overflow: hidden;
        }
        .active-workout .progress-bar {
            background: linear-gradient(90deg, #6366f1, #10b981);
        }
        .active-workout .next-step {
            background: rgba(255,255,255,0.06);
            border-radius: .5rem;
            padding: .75rem 1rem;
        }
        .btn-outline-light {
            border-color: rgba(255,255,255,0.4);
            color: #fff;
        }
        .btn-outline-light:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border-color: rgba(255,255,255,0.6);
        }
    </style>
    
    <script>
        // Workouts page specific functions
        function startWorkout(workoutId) {
            if (workoutId === 'featured') {
                showCompleteBodyTransformationPlan();
			} else if (workoutId === 'cardio-beginner' || workoutId === 'cardio-intermediate' || workoutId === 'cardio-advanced') {
                showCardioPlan(workoutId);
			} else if (workoutId.startsWith('strength-')) {
				showStrengthPlan(workoutId);
			} else if (workoutId.startsWith('hiit-')) {
				showHiitPlan(workoutId);
			} else if (workoutId.startsWith('flexibility-')) {
				showFlexPlan(workoutId);
            } else {
                showNotification('success', 'Starting workout...', 'Let\'s get moving! 💪');
                // In a real app, this would start the workout timer and tracking
            }
        }
        
        function showCompleteBodyTransformationPlan() {
            const workoutPlan = `
                <div class="modal fade" id="workoutPlanModal" tabindex="-1" aria-labelledby="workoutPlanModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="workoutPlanModalLabel">
                                    <i class="fas fa-dumbbell me-2"></i>Complete Body Transformation Workout Plan
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-dark">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card border-0 shadow-sm mb-3">
                                            <div class="card-header bg-gradient-primary text-white">
                                                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Workout Overview</h6>
                                            </div>
                                            <div class="card-body bg-dark text-white">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-clock text-primary me-2"></i>
                                                    <span><strong>Duration:</strong> 45 minutes</span>
                                                </div>
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-fire text-danger me-2"></i>
                                                    <span><strong>Calories:</strong> 400-500</span>
                                                </div>
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-signal text-warning me-2"></i>
                                                    <span><strong>Level:</strong> Intermediate</span>
                                                </div>
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-dumbbell text-info me-2"></i>
                                                    <span><strong>Equipment:</strong> Dumbbells, Mat</span>
                                                </div>
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-calendar text-success me-2"></i>
                                                    <span><strong>Frequency:</strong> 3-4 times/week</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="card border-0 shadow-sm mb-3">
                                            <div class="card-header bg-gradient-success text-white">
                                                <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Quick Tips</h6>
                                            </div>
                                            <div class="card-body bg-dark text-white">
                                                <ul class="list-unstyled mb-0">
                                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Warm up properly</li>
                                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Maintain proper form</li>
                                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Stay hydrated</li>
                                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Listen to your body</li>
                                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Rest between sets</li>
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        
                                    </div>
                                    
                                    <div class="col-md-8">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-header style="background: #ffc107 !important; background-image: none !important;" text-white">
                                                <h6 class="mb-0"><i class="fas fa-list me-2"></i>Workout Structure</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="workout-section mb-4">
                                                    <h6 class="text-primary mb-3"><i class="fas fa-play-circle me-2"></i>Phase 1: Warm-up (5 minutes)</h6>
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="exercise-item d-flex align-items-center mb-2">
                                                                <span class="badge bg-light text-dark me-2">5 min</span>
                                                                <span>Light cardio (jogging in place)</span>
                                                            </div>
                                                            <div class="exercise-item d-flex align-items-center mb-2">
                                                                <span class="badge bg-light text-dark me-2">5 min</span>
                                                                <span>Dynamic stretches</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="exercise-item d-flex align-items-center mb-2">
                                                                <span class="badge bg-light text-dark me-2">5 min</span>
                                                                <span>Arm circles & leg swings</span>
                                                            </div>
                                                            <div class="exercise-item d-flex align-items-center mb-2">
                                                                <span class="badge bg-light text-dark me-2">5 min</span>
                                                                <span>Light jumping jacks</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="workout-section mb-4">
                                                    <h6 class="text-success mb-3"><i class="fas fa-dumbbell me-2"></i>Phase 2: Strength Training (25 minutes)</h6>
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="exercise-item d-flex align-items-center mb-2">
                                                                <span class="badge bg-success text-white me-2">3x12</span>
                                                                <span>Squats with dumbbells</span>
                                                            </div>
                                                            <div class="exercise-item d-flex align-items-center mb-2">
                                                                <span class="badge bg-success text-white me-2">3x10</span>
                                                                <span>Push-ups</span>
                                                            </div>
                                                            <div class="exercise-item d-flex align-items-center mb-2">
                                                                <span class="badge bg-success text-white me-2">3x12</span>
                                                                <span>Dumbbell rows</span>
                                                            </div>
                                                            <div class="exercise-item d-flex align-items-center mb-2">
                                                                <span class="badge bg-success text-white me-2">3x15</span>
                                                                <span>Lunges</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="exercise-item d-flex align-items-center mb-2">
                                                                <span class="badge bg-success text-white me-2">3x10</span>
                                                                <span>Dumbbell shoulder press</span>
                                                            </div>
                                                            <div class="exercise-item d-flex align-items-center mb-2">
                                                                <span class="badge bg-success text-white me-2">3x12</span>
                                                                <span>Plank (30 sec)</span>
                                                            </div>
                                                            <div class="exercise-item d-flex align-items-center mb-2">
                                                                <span class="badge bg-success text-white me-2">3x15</span>
                                                                <span>Glute bridges</span>
                                                            </div>
                                                            <div class="exercise-item d-flex align-items-center mb-2">
                                                                <span class="badge bg-success text-white me-2">3x12</span>
                                                                <span>Bicep curls</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="workout-section mb-4">
                                                    <h6 class="text-warning mb-3"><i class="fas fa-running me-2"></i>Phase 3: Cardio & HIIT (10 minutes)</h6>
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="exercise-item d-flex align-items-center mb-2">
                                                                <span class="badge bg-warning text-dark me-2">30s</span>
                                                                <span>High knees</span>
                                                            </div>
                                                            <div class="exercise-item d-flex align-items-center mb-2">
                                                                <span class="badge bg-warning text-dark me-2">30s</span>
                                                                <span>Burpees</span>
                                                            </div>
                                                            <div class="exercise-item d-flex align-items-center mb-2">
                                                                <span class="badge bg-warning text-dark me-2">30s</span>
                                                                <span>Mountain climbers</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="exercise-item d-flex align-items-center mb-2">
                                                                <span class="badge bg-warning text-dark me-2">30s</span>
                                                                <span>Jump squats</span>
                                                            </div>
                                                            <div class="exercise-item d-flex align-items-center mb-2">
                                                                <span class="badge bg-warning text-dark me-2">30s</span>
                                                                <span>Plank jacks</span>
                                                            </div>
                                                            <div class="exercise-item d-flex align-items-center mb-2">
                                                                <span class="badge bg-warning text-dark me-2">30s</span>
                                                                <span>Rest</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="workout-section">
                                                    <h6 class="text-info mb-3"><i class="fas fa-snowflake me-2"></i>Phase 4: Cool-down (5 minutes)</h6>
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="exercise-item d-flex align-items-center mb-2">
                                                                <span class="badge bg-info text-white me-2">5 min</span>
                                                                <span>Static stretching</span>
                                                            </div>
                                                            <div class="exercise-item d-flex align-items-center mb-2">
                                                                <span class="badge bg-info text-white me-2">5 min</span>
                                                                <span>Deep breathing</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="exercise-item d-flex align-items-center mb-2">
                                                                <span class="badge bg-info text-white me-2">5 min</span>
                                                                <span>Foam rolling</span>
                                                            </div>
                                                            <div class="mb-2"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
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
            `;
            
            // Remove existing modal if any
            const existingModal = document.getElementById('workoutPlanModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Add modal to body
            document.body.insertAdjacentHTML('beforeend', workoutPlan);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('workoutPlanModal'));
            modal.show();
        }
        
        function beginWorkout() {
            // Close the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('workoutPlanModal'));
            modal.hide();
            
            // Start the actual workout
            showNotification('success', 'Complete Body Transformation workout started!', 'Let\'s get moving! 💪');
            
            // In a real app, this would start a workout timer and tracking
            setTimeout(() => {
                showNotification('info', 'Remember to stay hydrated and listen to your body!', '💧 Stay hydrated!');
            }, 2000);

            // Render simple in-session view
            if (!document.getElementById('activeWorkoutSession')) {
                const sessionHtml = `
                <div id="activeWorkoutSession" class="active-workout card border-0 shadow-lg mt-4">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-play-circle me-2"></i>
                            <h5 class="mb-0">Complete Body Transformation — In Progress</h5>
                        </div>
                        <div>
                            <button id="exListBtn" class="btn btn-outline-light btn-sm me-2" onclick="showExercisesModal(document.getElementById('currentPhase')?.textContent || 'Warm-up')">
                                <i class="fas fa-list-ul me-1"></i>Exercises
                            </button>
                            <button id="pauseBtn" class="btn btn-outline-light btn-sm me-2" onclick="pauseWorkout()" data-state="running">
                                <i class="fas fa-pause me-1"></i>Pause
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="endWorkout()">
                                <i class="fas fa-stop me-1"></i>End
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 align-items-center mb-3">
                            <div class="col-md-3">
                                <div class="chip"><i class="fas fa-hourglass-half me-2"></i><span>Elapsed: <strong id="elapsedTime">00:00</strong></span></div>
                            </div>
                            <div class="col-md-3">
                                <div class="chip"><i class="fas fa-clock me-2"></i><span>Remaining: <strong id="remainingTime">45:00</strong></span></div>
                            </div>
                            <div class="col-md-3">
                                <div class="chip"><i class="fas fa-layer-group me-2"></i><span>Phase: <strong id="currentPhase">Warm-up</strong></span></div>
                            </div>
                            <div class="col-md-3">
                                <div class="chip"><i class="fas fa-fire me-2"></i><span>Intensity: <strong id="currentIntensity">Moderate</strong></span></div>
                            </div>
                        </div>
                        <div class="progress mb-3" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                            <div id="workoutProgress" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6 mb-3 mb-lg-0">
                                <h6 class="text-uppercase text-white-50 mb-2">Up Next</h6>
                                <div class="next-step d-flex align-items-center">
                                    <i class="fas fa-forward me-2 text-warning"></i>
                                    <span id="nextStepText">Dynamic stretches → Squats with dumbbells</span>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <h6 class="text-uppercase text-white-50 mb-2">Checklist</h6>
                                <ul class="list-unstyled mb-0 small" id="sessionChecklist">
                                    <li class="mb-1 d-flex align-items-center"><input class="form-check-input me-2" type="checkbox" data-step="warmup">Warm-up</li>
                                    <li class="mb-1 d-flex align-items-center"><input class="form-check-input me-2" type="checkbox" data-step="strength1">Strength block 1</li>
                                    <li class="mb-1 d-flex align-items-center"><input class="form-check-input me-2" type="checkbox" data-step="strength2">Strength block 2</li>
                                    <li class="mb-1 d-flex align-items-center"><input class="form-check-input me-2" type="checkbox" data-step="cardio">Cardio & HIIT</li>
                                    <li class="mb-1 d-flex align-items-center"><input class="form-check-input me-2" type="checkbox" data-step="cooldown">Cool-down</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>`;

                // Insert below Featured Workout section
                const featuredRow = document.querySelector('.featured-workout')?.closest('.row');
                if (featuredRow) {
                    featuredRow.insertAdjacentHTML('afterend', sessionHtml);
                    document.getElementById('activeWorkoutSession').scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    document.querySelector('.container-fluid')?.insertAdjacentHTML('beforeend', sessionHtml);
                }
            }
        }

        function pauseWorkout() {
            const btn = document.getElementById('pauseBtn');
            const isPaused = btn?.dataset.state === 'paused';
            if (btn) {
                if (isPaused) {
                    btn.dataset.state = 'running';
                    btn.innerHTML = '<i class="fas fa-pause me-1"></i>Pause';
                    showNotification('success', 'Workout resumed', 'Keep it up!');
                    startWorkoutTimer();
                } else {
                    btn.dataset.state = 'paused';
                    btn.innerHTML = '<i class="fas fa-play me-1"></i>Resume';
                    showNotification('info', 'Workout paused', 'Catch your breath.');
                    stopWorkoutTimer();
                }
            }
        }

function endWorkout() {
            const el = document.getElementById('activeWorkoutSession');
			if (el) {
                el.remove();
                showNotification('success', 'Workout ended', 'Great job today!');
            }
        }

        // --- Timer & Phase logic ---
        let workoutTimerId = null;
        let workoutStartEpoch = null;
        let workoutPausedAccumulatedMs = 0;
        let workoutPauseStartEpoch = null;
        let lastPhaseIndex = -1;
        let totalWorkoutMs = 45 * 60 * 1000; // default 45 minutes
        let phases = [
            { key: 'Warm-up', durationMs: 5 * 60 * 1000, intensity: 'Light' },
            { key: 'Strength 1', durationMs: 12 * 60 * 1000, intensity: 'Moderate' },
            { key: 'Strength 2', durationMs: 13 * 60 * 1000, intensity: 'Moderate' },
            { key: 'Cardio & HIIT', durationMs: 10 * 60 * 1000, intensity: 'High' },
            { key: 'Cool-down', durationMs: 5 * 60 * 1000, intensity: 'Light' },
        ];
        
        function initializeSession(newTotalMs, newPhases) {
            // Reset timer state
            if (workoutTimerId) { clearInterval(workoutTimerId); workoutTimerId = null; }
            workoutStartEpoch = null;
            workoutPausedAccumulatedMs = 0;
            workoutPauseStartEpoch = null;
            lastPhaseIndex = -1;
            totalWorkoutMs = newTotalMs;
            phases = newPhases;
        }
        const phaseExercises = {
            'Warm-up': [
                'Light cardio (jog in place)',
                'Dynamic stretches',
                'Arm circles & leg swings',
            ],
            'Strength 1': [
                'Squats with dumbbells',
                'Push-ups',
                'Dumbbell rows',
                'Lunges',
            ],
            'Strength 2': [
                'Dumbbell shoulder press',
                'Plank (30 sec)',
                'Glute bridges',
                'Bicep curls',
            ],
            'Cardio & HIIT': [
                'High knees (30s)',
                'Burpees (30s)',
                'Mountain climbers (30s)',
                'Jump squats (30s)',
                'Plank jacks (30s)',
                'Rest (30s)',
            ],
            'Cool-down': [
                'Static stretching',
                'Deep breathing',
                'Foam rolling',
            ]
        };
        
        function startWorkoutTimer() {
            if (!workoutStartEpoch) workoutStartEpoch = Date.now();
            if (workoutPauseStartEpoch) {
                workoutPausedAccumulatedMs += Date.now() - workoutPauseStartEpoch;
                workoutPauseStartEpoch = null;
            }
            if (workoutTimerId) return;
            workoutTimerId = setInterval(updateWorkoutUiTick, 1000);
        }
        
        function stopWorkoutTimer() {
            if (workoutTimerId) {
                clearInterval(workoutTimerId);
                workoutTimerId = null;
                workoutPauseStartEpoch = Date.now();
            }
        }
        
        function msToMMSS(ms) {
            const totalSeconds = Math.max(0, Math.floor(ms / 1000));
            const m = Math.floor(totalSeconds / 60).toString().padStart(2, '0');
            const s = (totalSeconds % 60).toString().padStart(2, '0');
            return `${m}:${s}`;
        }
        
        function updateWorkoutUiTick() {
            const now = Date.now();
            const elapsedMs = now - workoutStartEpoch - workoutPausedAccumulatedMs;
            const remainingMs = Math.max(0, totalWorkoutMs - elapsedMs);
            const progress = Math.min(100, Math.floor((elapsedMs / totalWorkoutMs) * 100));
            
            const elapsedEl = document.getElementById('elapsedTime');
            const remainingEl = document.getElementById('remainingTime');
            const progressEl = document.getElementById('workoutProgress');
            if (elapsedEl) elapsedEl.textContent = msToMMSS(elapsedMs);
            if (remainingEl) remainingEl.textContent = msToMMSS(remainingMs);
            if (progressEl) progressEl.style.width = progress + '%';
            
            // Determine phase
            let phaseIndex = 0;
            let phaseElapsed = elapsedMs;
            for (let i = 0; i < phases.length; i++) {
                if (phaseElapsed < phases[i].durationMs) { phaseIndex = i; break; }
                phaseElapsed -= phases[i].durationMs;
                phaseIndex = i + 1;
            }
            if (phaseIndex >= phases.length) phaseIndex = phases.length - 1;
            const phase = phases[phaseIndex];
            const currentPhaseEl = document.getElementById('currentPhase');
            const currentIntensityEl = document.getElementById('currentIntensity');
            if (currentPhaseEl) currentPhaseEl.textContent = phase.key;
            if (currentIntensityEl) currentIntensityEl.textContent = phase.intensity;
            
            // Up next
            const nextStepEl = document.getElementById('nextStepText');
            if (nextStepEl) {
                const next = phases[phaseIndex + 1];
                nextStepEl.textContent = next ? `${phase.key} → ${next.key}` : `${phase.key} → Finish`;
            }
            
            // Disable auto exercises popup on phase change
            if (phaseIndex !== lastPhaseIndex) {
                lastPhaseIndex = phaseIndex;
                // no popup
            }

            // Auto-complete checklist as phases finish
            const checklist = document.getElementById('sessionChecklist');
            if (checklist) {
                const mapping = ['warmup','strength1','strength2','cardio','cooldown'];
                let cumulative = 0;
                for (let i = 0; i < phases.length; i++) {
                    cumulative += phases[i].durationMs;
                    const cb = checklist.querySelector(`input[data-step="${mapping[i]}"]`);
                    if (cb) cb.checked = elapsedMs >= cumulative;
                }
            }
            
            // Finish
            if (remainingMs <= 0) {
                stopWorkoutTimer();
                showNotification('success', 'Workout complete! Great job! 🎉', 'Session finished');
                // Persist completion for dashboard progress chart (weekly)
                try {
                    const key = 'workoutCompletions';
                    const raw = localStorage.getItem(key) || '[]';
                    let completions = [];
                    try { completions = JSON.parse(raw) || []; } catch (_) { completions = []; }
                    completions.push(new Date().toISOString());
                    localStorage.setItem(key, JSON.stringify(completions));
                } catch (e) { /* ignore storage errors */ }
                // Add to Recent Activity feed for dashboard
                try {
                    const raKey = 'recentActivities';
                    const raw = localStorage.getItem(raKey) || '[]';
                    let items = [];
                    try { items = JSON.parse(raw) || []; } catch (_) { items = []; }
                    const headerEl = document.querySelector('#activeWorkoutSession .card-header h5');
                    const workoutTitle = headerEl ? headerEl.textContent.replace(' — In Progress', '') : 'Workout Session';
                    items.unshift({
                        type: 'success',
                        title: `Completed ${workoutTitle}`,
                        details: `${Math.round(totalWorkoutMs/60000)} minutes • Session complete`,
                        ts: new Date().toISOString()
                    });
                    // Keep only latest 20
                    if (items.length > 20) items = items.slice(0, 20);
                    localStorage.setItem(raKey, JSON.stringify(items));
                } catch (e) { /* ignore storage errors */ }
				// Persist featured workout completion
				try { saveLegacyWorkout(totalWorkoutMs); } catch (_) { }

				// Update progress counter in header UI
                const counterEl = document.querySelector('.counter[data-target]');
                if (counterEl) {
                    const currentVal = parseInt(counterEl.textContent || '0', 10) || 0;
                    const newVal = currentVal + 1;
                    counterEl.textContent = newVal.toString();
                }
                // Optionally remove active session card after completion
                const sessionEl = document.getElementById('activeWorkoutSession');
                if (sessionEl) {
                    setTimeout(() => sessionEl.remove(), 1500);
                }
            }
        }
        
        // Start timer immediately when session renders
        setTimeout(() => startWorkoutTimer(), 300);

        // Show exercises modal for current or specific phase
        function showExercisesModal(phaseKey) {
            const items = phaseExercises[phaseKey] || [];
            const html = `
                <div class="modal fade" id="exerciseListModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-md">
                        <div class="modal-content">
                            <div class="modal-header bg-gradient-primary text-white">
                                <h5 class="modal-title"><i class="fas fa-list-ul me-2"></i>${phaseKey} — Exercises</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <ul class="list-group">
                                    ${items.map(t => `<li class=\"list-group-item d-flex align-items-center\"><i class=\"fas fa-check-circle text-success me-2\"></i><span>${t}</span></li>`).join('')}
                                </ul>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>`;
            const existing = document.getElementById('exerciseListModal');
            if (existing) existing.remove();
            document.body.insertAdjacentHTML('beforeend', html);
            const m = new bootstrap.Modal(document.getElementById('exerciseListModal'));
            m.show();
        }
        
        function previewCompleteBodyTransformation() {
            const previewModal = `
                <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content preview-dark">
                            <div class="modal-header bg-gradient-primary text-white">
                                <h5 class="modal-title" id="previewModalLabel">
                                    <i class="fas fa-eye me-2"></i>Complete Body Transformation Preview
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="preview-section mb-4">
                                            <h6 class="text-primary mb-3"><i class="fas fa-play-circle me-2"></i>Warm-up (5 min)</h6>
                                            <div class="preview-exercises">
                                                <div class="preview-exercise">
                                                    <i class="fas fa-running text-success me-2"></i>
                                                    <span>Light cardio & dynamic stretches</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="preview-section mb-4">
                                            <h6 class="text-success mb-3"><i class="fas fa-dumbbell me-2"></i>Strength (25 min)</h6>
                                            <div class="preview-exercises">
                                                <div class="preview-exercise">
                                                    <i class="fas fa-dumbbell text-success me-2"></i>
                                                    <span>Squats, Push-ups, Rows</span>
                                                </div>
                                                <div class="preview-exercise">
                                                    <i class="fas fa-dumbbell text-success me-2"></i>
                                                    <span>Lunges, Shoulder Press</span>
                                                </div>
                                                <div class="preview-exercise">
                                                    <i class="fas fa-dumbbell text-success me-2"></i>
                                                    <span>Plank, Glute Bridges</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="preview-section mb-4">
                                            <h6 class="text-warning mb-3"><i class="fas fa-running me-2"></i>Cardio & HIIT (10 min)</h6>
                                            <div class="preview-exercises">
                                                <div class="preview-exercise">
                                                    <i class="fas fa-fire text-warning me-2"></i>
                                                    <span>High knees, Burpees</span>
                                                </div>
                                                <div class="preview-exercise">
                                                    <i class="fas fa-fire text-warning me-2"></i>
                                                    <span>Mountain climbers, Jump squats</span>
                                                </div>
                                                <div class="preview-exercise">
                                                    <i class="fas fa-fire text-warning me-2"></i>
                                                    <span>Plank jacks, Rest intervals</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="preview-section">
                                            <h6 class="text-info mb-3"><i class="fas fa-snowflake me-2"></i>Cool-down (5 min)</h6>
                                            <div class="preview-exercises">
                                                <div class="preview-exercise">
                                                    <i class="fas fa-snowflake text-info me-2"></i>
                                                    <span>Static stretching & deep breathing</span>
                                                </div>
                                                <div class="preview-exercise">
                                                    <i class="fas fa-snowflake text-info me-2"></i>
                                                    <span>Foam rolling & relaxation</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="preview-stats mt-4">
                                            <div class="row text-center">
                                                <div class="col-4">
                                                    <div class="stat-item">
                                                        <div class="stat-number text-primary">45</div>
                                                        <div class="stat-label">Minutes</div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="stat-item">
                                                        <div class="stat-number text-success">8</div>
                                                        <div class="stat-label">Exercises</div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="stat-number text-warning">400-500</div>
                                                    <div class="stat-label">Calories</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="preview-highlight mt-4 p-3 bg-light rounded">
                                    <h6 class="text-center mb-2"><i class="fas fa-star text-warning me-2"></i>What to Expect</h6>
                                    <p class="text-center mb-0 text-muted">
                                        A balanced full-body workout combining strength training, cardio, and flexibility. 
                                        Perfect for building muscle, burning fat, and improving overall fitness!
                                    </p>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" onclick="startWorkout('featured')">
                                    <i class="fas fa-play me-2"></i>Start This Workout
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing preview modal if any
            const existingPreviewModal = document.getElementById('previewModal');
            if (existingPreviewModal) {
                existingPreviewModal.remove();
            }
            
            // Add preview modal to body
            document.body.insertAdjacentHTML('beforeend', previewModal);
            
            // Show preview modal
            const modal = new bootstrap.Modal(document.getElementById('previewModal'));
            modal.show();
        }
        
        function previewWorkout(workoutId) {
            showNotification('info', 'Opening workout preview...', '🎬 Preview Mode');
            // In a real app, this would show workout details and exercises
        }

        // Cardio workout plans
        function showCardioPlan(workoutId) {
            const config = {
                'cardio-beginner': {
                    title: 'Cardio Foundation',
                    duration: '25 minutes',
                    calories: '180-220',
                    level: 'Beginner',
                    overviewColor: 'primary',
                    structure: [
                        { phase: 'Warm-up (5 min)', color: 'primary', items: [
                            'Brisk walk or march in place (2 min)',
                            'Arm swings & leg swings (1 min)',
                            'Ankle circles & hip rotations (1 min)',
                            'Light side steps (1 min)'
                        ] },
                        { phase: 'Main Cardio (15 min)', color: 'success', items: [
                            'Light jog (2 min) • Walk (1 min) × 3 rounds',
                            'Low-impact jumping jacks (45s) • Walk (45s)',
                            'Knee lifts (45s) • Walk (45s)'
                        ] },
                        { phase: 'Cool-down (5 min)', color: 'info', items: [
                            'Slow walk & breathing control (2 min)',
                            'Calf/quad/hamstring stretches (3 min)'
                        ] },
                    ],
                    tips: ['Keep pace conversational', 'Focus on rhythm and breathing', 'Hydrate before and after']
                },
                'cardio-intermediate': {
                    title: 'Cardio Endurance',
                    duration: '35 minutes',
                    calories: '280-360',
                    level: 'Intermediate',
                    overviewColor: 'warning',
                    structure: [
                        { phase: 'Warm-up (8 min)', color: 'primary', items: [
                            'Light jog (4 min)',
                            'Dynamic mobility: hips, ankles, thoracic (2 min)',
                            '3 × 20s pickups (easy accelerations) + 20s walk'
                        ] },
                        { phase: 'Intervals (20 min)', color: 'success', items: [
                            'Run (2 min) • Jog (1 min) × 4 rounds',
                            'Run (3 min) • Jog (1 min) × 2 rounds',
                            'Optional: last round on slight incline'
                        ] },
                        { phase: 'Cool-down (7 min)', color: 'info', items: [
                            'Walk & breathing control (3 min)',
                            'Stretch: calves, quads, hamstrings, hip flexors (4 min)'
                        ] },
                    ],
                    tips: ['Aim for steady effort', 'Nasal breathing when possible', 'Keep posture tall']
                },
                'cardio-advanced': {
                    title: 'Cardio Master',
                    duration: '50 minutes',
                    calories: '420-520',
                    level: 'Advanced',
                    overviewColor: 'danger',
                    structure: [
                        { phase: 'Warm-up (10 min)', color: 'primary', items: [
                            'Gradual jog build-up (6 min)',
                            'Mobility prep: hips/ankles/hamstrings (2 min)',
                            'Stride-outs × 4 (20s each) with 40s walk'
                        ] },
                        { phase: 'HIIT Block (30 min)', color: 'warning', items: [
                            'Hard run (1 min) • Easy jog (1:30) × 6 rounds',
                            'Tempo run (4 min) • Easy jog (2 min) × 3 rounds',
                            'Optional: last hard rep sprint finish (20s)'
                        ] },
                        { phase: 'Cool-down (10 min)', color: 'info', items: [
                            'Walk to reduce HR (4 min)',
                            'Thorough stretching & mobility (6 min)'
                        ] },
                    ],
                    tips: ['Hit 80–90% max effort on hard reps', 'Relax shoulders and hands', 'Refuel with protein/carbs post-run']
                }
            }[workoutId];

            if (!config) return;

			const modalHtml = `
                <div class="modal fade" id="cardioPlanModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-gradient-primary text-white">
                                <h5 class="modal-title"><i class="fas fa-running me-2"></i>${config.title} — Plan</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="card border-0 shadow-sm mb-3">
                                            <div class="card-header style="background: #dc3545 !important; background-image: none !important;" text-white">
                                                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Overview</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-2"><i class="fas fa-clock me-2 text-${config.overviewColor}"></i><span><strong>Duration:</strong> ${config.duration}</span></div>
                                                <div class="d-flex align-items-center mb-2"><i class="fas fa-fire me-2 text-${config.overviewColor}"></i><span><strong>Calories:</strong> ${config.calories}</span></div>
                                                <div class="d-flex align-items-center"><i class="fas fa-signal me-2 text-${config.overviewColor}"></i><span><strong>Level:</strong> ${config.level}</span></div>
                                            </div>
                                        </div>
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-header bg-gradient-success text-white">
                                                <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Tips</h6>
                                            </div>
                                            <div class="card-body">
                                                <ul class="mb-0 list-unstyled">
                                                    ${config.tips.map(t => `<li class=\"mb-2\"><i class=\"fas fa-check text-success me-2\"></i>${t}</li>`).join('')}
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-header style="background: #ffc107 !important; background-image: none !important;" text-white">
                                                <h6 class="mb-0"><i class="fas fa-list me-2"></i>Structure</h6>
                                            </div>
                                            <div class="card-body">
                                                ${config.structure.map(block => `
                                                    <div class=\"workout-section mb-3\">
                                                        <h6 class=\"text-${block.color} mb-2\"><i class=\"fas fa-chevron-right me-2\"></i>${block.phase}</h6>
                                                        <ul class=\"mb-0\">
                                                            ${block.items.map(i => `<li class=\"mb-1\">${i}</li>`).join('')}
                                                        </ul>
                                                    </div>
                                                `).join('')}
                                            </div>
                                        </div>
                                        
                                        <div class="card border-0 shadow-sm mt-3">
                                            <div class="card-header bg-gradient-info text-white">
                                                <h6 class="mb-0"><i class="fas fa-play-circle me-2"></i>When You Begin</h6>
                                            </div>
                                            <div class="card-body">
                                                <ul class="mb-0">
                                                    <li class="mb-2">A timer starts automatically and tracks <strong>${config.duration}</strong>.</li>
                                                    <li class="mb-2">Phases guide you through: ${config.structure.map(b => `<strong>${b.phase}</strong>`).join(' → ')}.</li>
                                                    <li class="mb-2">The "Up Next" panel shows the upcoming block and exercises.</li>
                                                    <li class="mb-2">A checklist lets you tick off each block as you complete it.</li>
                                                    <li class="mb-0">Use <strong>Pause</strong> to take a break or <strong>End</strong> to finish early.</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
							<div class="modal-footer">
								<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
								<button type="button" class="btn btn-primary" id="cardioPlanBeginBtn" onclick="beginCardioFromPlan('${workoutId}', '${config.title}')"><i class="fas fa-play me-2"></i>Begin Workout</button>
							</div>
                        </div>
                    </div>
                </div>`;

            const existing = document.getElementById('cardioPlanModal');
            if (existing) existing.remove();
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const m = new bootstrap.Modal(document.getElementById('cardioPlanModal'));
            m.show();

            // Wire up Begin button explicitly
            const beginBtn = document.getElementById('cardioPlanBeginBtn');
        if (beginBtn) {
            beginBtn.addEventListener('click', function() {
                beginCardioFromPlan('${workoutId}', '${config.title}');
                const instance = bootstrap.Modal.getInstance(document.getElementById('cardioPlanModal'));
                if (instance) instance.hide();
            });
        }
        }

        function renderActiveSessionHeader(title) {
            const header = document.querySelector('#activeWorkoutSession .card-header h5');
            if (header) header.textContent = `${title} — In Progress`;
    // Also ensure Timer button is removed for Cardio Foundation sessions
    if (title === 'Cardio Foundation') {
        const timerBtn = document.querySelector('#activeWorkoutSession button[onclick="openTimerModal()"]');
        if (timerBtn) timerBtn.remove();
    }
        }

		// Ensure an active workout session card exists with the elements our timer logic updates
		function createWorkoutSessionCard() {
			if (document.getElementById('activeWorkoutSession')) return;
			const sessionHtml = `
			<div id="activeWorkoutSession" class="active-workout card border-0 shadow-lg mt-4">
				<div class="card-header d-flex align-items-center justify-content-between">
					<div class="d-flex align-items-center gap-2">
						<i class="fas fa-play-circle me-2"></i>
						<h5 class="mb-0">Workout — In Progress</h5>
					</div>
					<div>
						<button class="btn btn-outline-light btn-sm me-2" onclick="openTimerModal()">
							<i class="fas fa-stopwatch me-1"></i>Timer
						</button>
						<button id="startWorkoutBtn" class="btn btn-success btn-sm me-2" onclick="startCardioTimer()">
							<i class="fas fa-play me-1"></i>Start
						</button>
						<button id="pauseWorkoutBtn" class="btn btn-warning btn-sm me-2" onclick="pauseCardioTimer()">
							<i class="fas fa-pause me-1"></i>Pause
						</button>
						<button id="resumeWorkoutBtn" class="btn btn-info btn-sm me-2" onclick="resumeCardioTimer()">
							<i class="fas fa-play me-1"></i>Resume
						</button>
						<button id="endWorkoutBtn" class="btn btn-danger btn-sm" onclick="stopCardioTimer()">
							<i class="fas fa-stop me-1"></i>End
						</button>
					</div>
				</div>
				<div class="card-body">
					<div class="row g-3 align-items-center mb-3">
						<div class="col-md-4">
							<div class="display-6 fw-bold" id="sessionTimer">00:00</div>
							<div class="progress mt-2" role="progressbar" aria-valuemin="0" aria-valuemax="100">
								<div id="sessionProgress" class="progress-bar" style="width: 0%"></div>
							</div>
						</div>
						<div class="col-md-8">
							<div class="row g-2">
								<div class="col-sm-6">
									<div class="chip"><i class="fas fa-layer-group me-2"></i><span>Phase: <strong id="currentPhase">Warm-up</strong></span></div>
									<div class="progress mt-2"><div id="phaseProgress" class="progress-bar bg-info" style="width: 0%"></div></div>
								</div>
								<div class="col-sm-6">
									<div class="chip"><i class="fas fa-fire me-2"></i><span>Calories: <strong id="caloriesBurned">0</strong></span></div>
									<div class="chip mt-2"><i class="fas fa-forward me-2"></i><span>Up Next: <strong id="nextPhase">—</strong></span></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>`;

			const featuredRow = document.querySelector('.featured-workout')?.closest('.row');
			if (featuredRow) {
				featuredRow.insertAdjacentHTML('afterend', sessionHtml);
				document.getElementById('activeWorkoutSession').scrollIntoView({ behavior: 'smooth', block: 'start' });
			} else {
				document.querySelector('.container-fluid')?.insertAdjacentHTML('beforeend', sessionHtml);
			}
		}

		// Strength workout plans
		function showStrengthPlan(workoutId) {
			const config = {
				'strength-beginner': { title: 'Strength Foundation', duration: '30 minutes', calories: '180-230', level: 'Beginner', overviewColor: 'success',
					structure: [
						{ phase: 'Warm-up (8 min)', color: 'primary', items: ['Dynamic mobility (4 min)','Band activation (4 min)'] },
						{ phase: 'Strength (15 min)', color: 'success', items: ['Squats 3×12','Push-ups 3×10','Rows 3×12'] },
						{ phase: 'Cool-down (7 min)', color: 'info', items: ['Stretch and breathing (7 min)'] },
					], tips: ['Focus on form','Control tempo','Rest 60–90s'] },
				'strength-intermediate': { title: 'Muscle Development', duration: '40 minutes', calories: '250-320', level: 'Intermediate', overviewColor: 'success',
					structure: [
						{ phase: 'Warm-up (10 min)', color: 'primary', items: ['Mobility + activation (10 min)'] },
						{ phase: 'Strength (25 min)', color: 'success', items: ['Deadlift 4×8','Bench 4×8','Lunges 3×12'] },
						{ phase: 'Cool-down (5 min)', color: 'info', items: ['Stretch (5 min)'] },
					], tips: ['Progressive overload','Full ROM','Breathe properly'] },
				'strength-advanced': { title: 'Power & Strength', duration: '55 minutes', calories: '350-450', level: 'Advanced', overviewColor: 'success',
					structure: [
						{ phase: 'Warm-up (12 min)', color: 'primary', items: ['Dynamic warmup (12 min)'] },
						{ phase: 'Strength (35 min)', color: 'warning', items: ['Heavy compounds 5×5','Accessory 3×12'] },
						{ phase: 'Cool-down (8 min)', color: 'info', items: ['Mobility (8 min)'] },
					], tips: ['Spotter for heavy sets','Longer rest','Maintain technique'] },
			}[workoutId];

			if (!config) return;
			const modalHtml = `
			<div class="modal fade" id="strengthPlanModal" tabindex="-1" aria-hidden="true">
				<div class="modal-dialog modal-lg">
					<div class="modal-content">
						<div class="modal-header bg-gradient-primary text-white">
							<h5 class="modal-title"><i class="fas fa-dumbbell me-2"></i>${config.title} — Plan</h5>
							<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							<div class="row g-3">
								<div class="col-md-4">
									<div class="card border-0 shadow-sm mb-3">
										<div class="card-header bg-light text-dark">
											<h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Overview</h6>
										</div>
										<div class="card-body">
											<div class="d-flex align-items-center mb-2 text-dark"><i class="fas fa-clock me-2 text-${config.overviewColor}"></i><span><strong>Duration:</strong> ${config.duration}</span></div>
											<div class="d-flex align-items-center mb-2 text-dark"><i class="fas fa-fire me-2 text-${config.overviewColor}"></i><span><strong>Calories:</strong> ${config.calories}</span></div>
											<div class="d-flex align-items-center text-dark"><i class="fas fa-signal me-2 text-${config.overviewColor}"></i><span><strong>Level:</strong> ${config.level}</span></div>
										</div>
									</div>
								<div class="card border-0 shadow-sm">
									<div class="card-header bg-gradient-success text-white">
										<h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Tips</h6>
									</div>
									<div class="card-body">
										<ul class="mb-0 list-unstyled">
											${config.tips.map(t => `<li class=\"mb-2\"><i class=\"fas fa-check text-success me-2\"></i>${t}</li>`).join('')}
										</ul>
									</div>
								</div>
					</div>
					<div class="col-md-8">
								<div class="card border-0 shadow-sm">
									<div class="card-header bg-light text-dark">
										<h6 class="mb-0"><i class="fas fa-list me-2"></i>Structure</h6>
									</div>
									<div class="card-body">
										${config.structure.map(block => `
											<div class=\"workout-section mb-3\">
												<h6 class=\"text-${block.color} mb-2\"><i class=\"fas fa-chevron-right me-2\"></i>${block.phase}</h6>
												<ul class=\"mb-0\">
													${block.items.map(i => `<li class=\"mb-1\">${i}</li>`).join('')}
												</ul>
											</div>
										`).join('')}
									</div>
								</div>

								<div class="card border-0 shadow-sm mt-3">
									<div class="card-header bg-gradient-info text-white">
										<h6 class="mb-0"><i class="fas fa-play-circle me-2"></i>When You Begin</h6>
									</div>
									<div class="card-body">
										<ul class="mb-0">
											<li class="mb-2">A timer starts automatically and tracks <strong>${config.duration}</strong>.</li>
											<li class="mb-2">Phases guide you through: ${config.structure.map(b => `<strong>${b.phase}</strong>`).join(' → ')}.</li>
											<li class="mb-2">The "Up Next" panel shows the upcoming block and exercises.</li>
											<li class="mb-2">A checklist lets you tick off each block as you complete it.</li>
											<li class="mb-0">Use <strong>Pause</strong> to take a break or <strong>End</strong> to finish early.</li>
										</ul>
									</div>
								</div>
					</div>
							</div>
					<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
							<button type="button" class="btn btn-primary" id="strengthPlanBeginBtn" onclick="beginGenericFromPlan('${workoutId}', '${config.title}')"><i class="fas fa-play me-2"></i>Begin Workout</button>
						</div>
					</div>
				</div>
			</div>`;

			const existing = document.getElementById('strengthPlanModal');
			if (existing) existing.remove();
			document.body.insertAdjacentHTML('beforeend', modalHtml);
			new bootstrap.Modal(document.getElementById('strengthPlanModal')).show();
		}

		// HIIT workout plans
		function showHiitPlan(workoutId) {
			const config = {
				'hiit-beginner': { title: 'HIIT Introduction', duration: '20 minutes', calories: '160-210', level: 'Beginner', overviewColor: 'danger',
					structure: [
						{ phase: 'Warm-up (5 min)', color: 'primary', items: ['Mobility + easy cardio (5 min)'] },
						{ phase: 'Intervals (10 min)', color: 'warning', items: ['30s work • 60s rest × 10'] },
						{ phase: 'Cool-down (5 min)', color: 'info', items: ['Walk + stretch (5 min)'] },
					], tips: ['Keep first reps easy','Full recoveries','Good form'] },
				'hiit-intermediate': { title: 'HIIT Burn', duration: '30 minutes', calories: '280-360', level: 'Intermediate', overviewColor: 'danger',
					structure: [
						{ phase: 'Warm-up (8 min)', color: 'primary', items: ['Mobility (8 min)'] },
						{ phase: 'Intervals (18 min)', color: 'warning', items: ['45s work • 45s rest × 12'] },
						{ phase: 'Cool-down (4 min)', color: 'info', items: ['Stretch (4 min)'] },
					], tips: ['Control breathing','Explosive but safe','Even pacing'] },
				'hiit-advanced': { title: 'HIIT Inferno', duration: '40 minutes', calories: '420-520', level: 'Advanced', overviewColor: 'danger',
					structure: [
						{ phase: 'Warm-up (10 min)', color: 'primary', items: ['Dynamic warmup (10 min)'] },
						{ phase: 'Intervals (25 min)', color: 'warning', items: ['60s work • 30s rest × 15'] },
						{ phase: 'Cool-down (5 min)', color: 'info', items: ['Stretch (5 min)'] },
					], tips: ['Max effort late','Stay tall','Power from hips'] },
			}[workoutId];

			if (!config) return;
			const html = `
			<div class="modal fade" id="hiitPlanModal" tabindex="-1" aria-hidden="true">
				<div class="modal-dialog modal-lg">
					<div class="modal-content">
						<div class="modal-header bg-gradient-primary text-white">
							<h5 class="modal-title"><i class="fas fa-bolt me-2"></i>${config.title} — Plan</h5>
							<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
						</div>
						<div class="modal-body">
							<div class="row g-3">
								<div class="col-md-4">
									<div class="card border-0 shadow-sm mb-3">
										<div class="card-header bg-light text-dark"><h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Overview</h6></div>
										<div class="card-body">
											<div class="d-flex align-items-center mb-2"><i class="fas fa-clock me-2 text-${config.overviewColor}"></i><span><strong>Duration:</strong> ${config.duration}</span></div>
											<div class="d-flex align-items-center mb-2"><i class="fas fa-fire me-2 text-${config.overviewColor}"></i><span><strong>Calories:</strong> ${config.calories}</span></div>
											<div class="d-flex align-items-center"><i class="fas fa-signal me-2 text-${config.overviewColor}"></i><span><strong>Level:</strong> ${config.level}</span></div>
										</div>
									</div>
									<div class="card border-0 shadow-sm">
										<div class="card-header bg-gradient-warning text-white">
											<h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Tips</h6>
										</div>
										<div class="card-body">
											<ul class="mb-0 list-unstyled">
												${config.tips.map(t => `<li class=\"mb-2\"><i class=\"fas fa-check text-warning me-2\"></i>${t}</li>`).join('')}
											</ul>
										</div>
									</div>
								</div>
								<div class="col-md-8">
									<div class="card border-0 shadow-sm">
										<div class="card-header bg-light text-dark">
											<h6 class="mb-0"><i class="fas fa-list me-2"></i>Structure</h6>
										</div>
										<div class="card-body">
											${config.structure.map(block => `
												<div class=\"workout-section mb-3\">
													<h6 class=\"text-${block.color} mb-2\"><i class=\"fas fa-chevron-right me-2\"></i>${block.phase}</h6>
													<ul class=\"mb-0\">
														${block.items.map(i => `<li class=\"mb-1\">${i}</li>`).join('')}
													</ul>
												</div>
											`).join('')}
										</div>
									</div>

									<div class="card border-0 shadow-sm mt-3">
										<div class="card-header bg-gradient-info text-white">
											<h6 class="mb-0"><i class="fas fa-play-circle me-2"></i>When You Begin</h6>
										</div>
										<div class="card-body">
											<ul class="mb-0">
												<li class="mb-2">A timer starts automatically and tracks <strong>${config.duration}</strong>.</li>
												<li class="mb-2">Phases guide you through: ${config.structure.map(b => `<strong>${b.phase}</strong>`).join(' → ')}.</li>
												<li class="mb-2">The "Up Next" panel shows the upcoming block and exercises.</li>
												<li class="mb-2">A checklist lets you tick off each block as you complete it.</li>
												<li class="mb-0">Use <strong>Pause</strong> to take a break or <strong>End</strong> to finish early.</li>
											</ul>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
							<button class="btn btn-primary" onclick="beginGenericFromPlan('${workoutId}', '${config.title}')"><i class="fas fa-play me-2"></i>Begin Workout</button>
						</div>
					</div>
				</div>
			</div>`;

			const ex = document.getElementById('hiitPlanModal'); if (ex) ex.remove();
			document.body.insertAdjacentHTML('beforeend', html);
			new bootstrap.Modal(document.getElementById('hiitPlanModal')).show();
		}

		// Flexibility workout plans
		function showFlexPlan(workoutId) {
			const config = {
				'flexibility-beginner': { title: 'Flexibility Basics', duration: '25 minutes', calories: '80-120', level: 'Beginner', overviewColor: 'info',
					structure: [
						{ phase: 'Warm-up (8 min)', color: 'primary', items: ['Gentle mobility (8 min)'] },
						{ phase: 'Stretching (12 min)', color: 'info', items: ['Full body static stretches (12 min)'] },
						{ phase: 'Cool-down (5 min)', color: 'info', items: ['Breathing + relax (5 min)'] },
					], tips: ['No pain stretching','Slow breathing','Hold 20–30s'] },
				'flexibility-intermediate': { title: 'Yoga & Mobility', duration: '35 minutes', calories: '120-180', level: 'Intermediate', overviewColor: 'info',
					structure: [
						{ phase: 'Warm-up (10 min)', color: 'primary', items: ['Sun salutations (10 min)'] },
						{ phase: 'Yoga Flow (20 min)', color: 'info', items: ['Flow sequence (20 min)'] },
						{ phase: 'Cool-down (5 min)', color: 'info', items: ['Savasana + stretch (5 min)'] },
					], tips: ['Focus on control','Smooth transitions','Relax jaw/shoulders'] },
				'flexibility-advanced': { title: 'Advanced Flexibility', duration: '50 minutes', calories: '140-200', level: 'Advanced', overviewColor: 'info',
					structure: [
						{ phase: 'Warm-up (12 min)', color: 'primary', items: ['Mobility + activation (12 min)'] },
						{ phase: 'Flexibility (30 min)', color: 'info', items: ['PNF and deep stretches (30 min)'] },
						{ phase: 'Cool-down (8 min)', color: 'info', items: ['Relax + respiration (8 min)'] },
					], tips: ['Never bounce','Gentle progress','Stay warm'] },
			}[workoutId];

			if (!config) return;
			const html = `
			<div class="modal fade" id="flexPlanModal" tabindex="-1" aria-hidden="true">
				<div class="modal-dialog modal-lg">
					<div class="modal-content">
						<div class="modal-header bg-gradient-primary text-white">
							<h5 class="modal-title"><i class="fas fa-child me-2"></i>${config.title} — Plan</h5>
							<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
						</div>
						<div class="modal-body">
							<div class="row g-3">
								<div class="col-md-4">
									<div class="card border-0 shadow-sm mb-3">
										<div class="card-header style="background: #dc3545 !important; background-image: none !important;" text-white"><h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Overview</h6></div>
										<div class="card-body">
											<div class="d-flex align-items-center mb-2"><i class="fas fa-clock me-2 text-${config.overviewColor}"></i><span><strong>Duration:</strong> ${config.duration}</span></div>
											<div class="d-flex align-items-center mb-2"><i class="fas fa-fire me-2 text-${config.overviewColor}"></i><span><strong>Calories:</strong> ${config.calories}</span></div>
											<div class="d-flex align-items-center"><i class="fas fa-signal me-2 text-${config.overviewColor}"></i><span><strong>Level:</strong> ${config.level}</span></div>
										</div>
									</div>
									<div class="card border-0 shadow-sm">
										<div class="card-header bg-gradient-info text-white"><h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Tips</h6></div>
										<div class="card-body">
											<ul class="mb-0 list-unstyled">
												${config.tips.map(t => `<li class=\"mb-2\"><i class=\"fas fa-check text-info me-2\"></i>${t}</li>`).join('')}
											</ul>
										</div>
									</div>
								</div>
								<div class="col-md-8">
									<div class="card border-0 shadow-sm">
										<div class="card-header bg-light text-dark"><h6 class="mb-0"><i class="fas fa-list me-2"></i>Structure</h6></div>
										<div class="card-body">${config.structure.map(block => `
											<div class=\"workout-section mb-3\">
												<h6 class=\"text-${block.color} mb-2\"><i class=\"fas fa-chevron-right me-2\"></i>${block.phase}</h6>
												<ul class=\"mb-0\">${block.items.map(i => `<li class=\"mb-1\">${i}</li>`).join('')}</ul>
											</div>`).join('')}</div>
									</div>

									<div class="card border-0 shadow-sm mt-3">
										<div class="card-header bg-gradient-info text-white"><h6 class="mb-0"><i class="fas fa-play-circle me-2"></i>When You Begin</h6></div>
										<div class="card-body">
											<ul class="mb-0">
												<li class="mb-2">A timer starts automatically and tracks <strong>${config.duration}</strong>.</li>
												<li class="mb-2">Phases guide you through: ${config.structure.map(b => `<strong>${b.phase}</strong>`).join(' → ')}.</li>
												<li class="mb-2">The "Up Next" panel shows the upcoming block and exercises.</li>
												<li class="mb-2">A checklist lets you tick off each block as you complete it.</li>
												<li class="mb-0">Use <strong>Pause</strong> to take a break or <strong>End</strong> to finish early.</li>
											</ul>
										</div>
									</div>
								</div>
							</div>
						<div class="modal-footer">
							<button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
							<button class="btn btn-info" onclick="beginGenericFromPlan('${workoutId}', '${config.title}')"><i class="fas fa-play me-2"></i>Begin Workout</button>
						</div>
					</div>
				</div>
			</div>`;

			const ex = document.getElementById('flexPlanModal'); if (ex) ex.remove();
			document.body.insertAdjacentHTML('beforeend', html);
			new bootstrap.Modal(document.getElementById('flexPlanModal')).show();
		}

		// Generic begin that maps phases and starts timer using the shared engine
		function beginGenericFromPlan(workoutId, title) {
			const phasePresets = {
				'strength-beginner': [
					{ key: 'Warm-up', durationMs: 8*60*1000, intensity: 'Light', caloriesPerMin: 3.0 },
					{ key: 'Strength', durationMs: 15*60*1000, intensity: 'Moderate', caloriesPerMin: 6.0 },
					{ key: 'Cool-down', durationMs: 7*60*1000, intensity: 'Light', caloriesPerMin: 2.5 },
				],
				'strength-intermediate': [
					{ key: 'Warm-up', durationMs: 10*60*1000, intensity: 'Light', caloriesPerMin: 3.0 },
					{ key: 'Strength', durationMs: 25*60*1000, intensity: 'Moderate-High', caloriesPerMin: 7.5 },
					{ key: 'Cool-down', durationMs: 5*60*1000, intensity: 'Light', caloriesPerMin: 2.5 },
				],
				'strength-advanced': [
					{ key: 'Warm-up', durationMs: 12*60*1000, intensity: 'Light', caloriesPerMin: 3.5 },
					{ key: 'Strength', durationMs: 35*60*1000, intensity: 'High', caloriesPerMin: 8.5 },
					{ key: 'Cool-down', durationMs: 8*60*1000, intensity: 'Light', caloriesPerMin: 2.5 },
				],
				'hiit-beginner': [
					{ key: 'Warm-up', durationMs: 5*60*1000, intensity: 'Light', caloriesPerMin: 3.5 },
					{ key: 'Intervals', durationMs: 10*60*1000, intensity: 'High', caloriesPerMin: 11.0 },
					{ key: 'Cool-down', durationMs: 5*60*1000, intensity: 'Light', caloriesPerMin: 3.0 },
				],
				'hiit-intermediate': [
					{ key: 'Warm-up', durationMs: 8*60*1000, intensity: 'Light', caloriesPerMin: 3.5 },
					{ key: 'Intervals', durationMs: 18*60*1000, intensity: 'High', caloriesPerMin: 12.0 },
					{ key: 'Cool-down', durationMs: 4*60*1000, intensity: 'Light', caloriesPerMin: 3.0 },
				],
				'hiit-advanced': [
					{ key: 'Warm-up', durationMs: 10*60*1000, intensity: 'Light', caloriesPerMin: 3.5 },
					{ key: 'Intervals', durationMs: 25*60*1000, intensity: 'Very High', caloriesPerMin: 13.0 },
					{ key: 'Cool-down', durationMs: 5*60*1000, intensity: 'Light', caloriesPerMin: 3.0 },
				],
				'flexibility-beginner': [
					{ key: 'Warm-up', durationMs: 8*60*1000, intensity: 'Light', caloriesPerMin: 2.0 },
					{ key: 'Stretching', durationMs: 12*60*1000, intensity: 'Light', caloriesPerMin: 2.5 },
					{ key: 'Cool-down', durationMs: 5*60*1000, intensity: 'Light', caloriesPerMin: 2.0 },
				],
				'flexibility-intermediate': [
					{ key: 'Warm-up', durationMs: 10*60*1000, intensity: 'Light', caloriesPerMin: 2.0 },
					{ key: 'Yoga Flow', durationMs: 20*60*1000, intensity: 'Light-Moderate', caloriesPerMin: 3.0 },
					{ key: 'Cool-down', durationMs: 5*60*1000, intensity: 'Light', caloriesPerMin: 2.0 },
				],
				'flexibility-advanced': [
					{ key: 'Warm-up', durationMs: 12*60*1000, intensity: 'Light', caloriesPerMin: 2.0 },
					{ key: 'Flexibility', durationMs: 30*60*1000, intensity: 'Light', caloriesPerMin: 2.8 },
					{ key: 'Cool-down', durationMs: 8*60*1000, intensity: 'Light', caloriesPerMin: 2.0 },
				],
			};

			const phases = phasePresets[workoutId];
			if (!phases) return;
			const totalMs = phases.reduce((s,p)=>s+p.durationMs,0);

			createWorkoutSessionCard();
			initializeCardioSession(totalMs, phases, workoutId, title);
			renderActiveSessionHeader(title);
			startCardioTimer();
		}

		// Popup modal that mirrors the current session timer and phase
		function openTimerModal() {
			const existing = document.getElementById('timerPopupModal');
			if (existing) existing.remove();
			const html = `
			<div class="modal fade" id="timerPopupModal" tabindex="-1" aria-hidden="true">
				<div class="modal-dialog modal-sm modal-dialog-centered">
					<div class="modal-content">
						<div class="modal-header">
							<h6 class="modal-title"><i class="fas fa-stopwatch me-2"></i>Workout Timer</h6>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body text-center">
							<div class="display-5 fw-bold" id="timerPopupDisplay">00:00</div>
							<div class="small text-muted mt-1">Phase: <span id="timerPopupPhase">—</span></div>
							<div class="progress mt-3"><div id="timerPopupProgress" class="progress-bar" style="width:0%"></div></div>
						</div>
						<div class="modal-footer d-flex justify-content-between">
							<div>
								<button class="btn btn-success btn-sm me-1" onclick="startCardioTimer()"><i class="fas fa-play"></i></button>
								<button class="btn btn-warning btn-sm me-1" onclick="pauseCardioTimer()"><i class="fas fa-pause"></i></button>
								<button class="btn btn-info btn-sm me-1" onclick="resumeCardioTimer()"><i class="fas fa-play"></i></button>
							</div>
							<button class="btn btn-danger btn-sm" data-bs-dismiss="modal" onclick="stopCardioTimer()">End</button>
						</div>
					</div>
				</div>
			</div>`;
			document.body.insertAdjacentHTML('beforeend', html);
			const modalEl = document.getElementById('timerPopupModal');
			const m = new bootstrap.Modal(modalEl);
			let syncInterval = null;
			const syncUi = () => {
				const s = window.cardioSession;
				if (!s) return;
				const remainingMs = Math.max(0, s.totalMs - s.elapsedMs);
				const mm = Math.floor(remainingMs / 60000).toString().padStart(2, '0');
				const ss = Math.floor((remainingMs % 60000) / 1000).toString().padStart(2, '0');
				document.getElementById('timerPopupDisplay').textContent = `${mm}:${ss}`;
				document.getElementById('timerPopupPhase').textContent = s.phases[s.currentPhaseIndex]?.key || '—';
				const pct = (s.elapsedMs / s.totalMs) * 100;
				document.getElementById('timerPopupProgress').style.width = `${pct}%`;
			};
			modalEl.addEventListener('shown.bs.modal', () => {
				syncUi();
				syncInterval = setInterval(syncUi, 1000);
			});
			modalEl.addEventListener('hide.bs.modal', () => { if (syncInterval) clearInterval(syncInterval); });
			m.show();
		}


        // Cardio Foundation Modal with React-like functionality

        function beginCardioFromPlan(workoutId, title) {
            // Cardio-specific phases with calorie burn rates (calories per minute for 70kg person)
            const cardioConfigs = {
                'cardio-beginner': [
                    { key: 'Warm-up', durationMs: 5*60*1000, intensity: 'Light', caloriesPerMin: 3.5 },
                    { key: 'Main Cardio', durationMs: 15*60*1000, intensity: 'Moderate', caloriesPerMin: 7.0 },
                    { key: 'Cool-down', durationMs: 5*60*1000, intensity: 'Light', caloriesPerMin: 3.5 },
                ],
                'cardio-intermediate': [
                    { key: 'Warm-up', durationMs: 8*60*1000, intensity: 'Light', caloriesPerMin: 4.0 },
                    { key: 'Intervals', durationMs: 20*60*1000, intensity: 'Moderate-High', caloriesPerMin: 9.0 },
                    { key: 'Cool-down', durationMs: 7*60*1000, intensity: 'Light', caloriesPerMin: 3.5 },
                ],
                'cardio-advanced': [
                    { key: 'Warm-up', durationMs: 10*60*1000, intensity: 'Light-Moderate', caloriesPerMin: 5.0 },
                    { key: 'HIIT Block', durationMs: 30*60*1000, intensity: 'High', caloriesPerMin: 12.0 },
                    { key: 'Cool-down', durationMs: 10*60*1000, intensity: 'Light', caloriesPerMin: 3.5 },
                ],
            };

            const selectedPhases = cardioConfigs[workoutId];
            if (!selectedPhases) return;
            const totalMs = selectedPhases.reduce((sum, p) => sum + p.durationMs, 0);

            // Create or ensure session panel exists
            createWorkoutSessionCard();

            // Initialize enhanced cardio session with calorie tracking
            initializeCardioSession(totalMs, selectedPhases, workoutId, title);
            renderActiveSessionHeader(title);

            // Remove Timer button specifically for Cardio Foundation
            if (workoutId === 'cardio-beginner') {
                const timerBtn = document.querySelector('#activeWorkoutSession button[onclick="openTimerModal()"]');
                if (timerBtn) timerBtn.remove();
            }

            // Adjust checklist items to match 3 blocks
            const checklist = document.getElementById('sessionChecklist');
            if (checklist) {
                checklist.innerHTML = `
                    <li class="mb-1 d-flex align-items-center"><input class="form-check-input me-2" type="checkbox" data-step="warmup">Warm-up</li>
                    <li class="mb-1 d-flex align-items-center"><input class="form-check-input me-2" type="checkbox" data-step="main">${selectedPhases[1].key}</li>
                    <li class="mb-1 d-flex align-items-center"><input class="form-check-input me-2" type="checkbox" data-step="cooldown">Cool-down</li>
                `;
            }

            // Start the enhanced cardio timer
            startCardioTimer();
            showNotification('success', `Starting ${title}...`, 'Let\'s go!');
        }

        // Enhanced cardio session with calorie tracking
        function initializeCardioSession(totalMs, phases, workoutId, title) {
            // Store session data globally
            window.cardioSession = {
                totalMs: totalMs,
                phases: phases,
                workoutId: workoutId,
                title: title,
                startTime: Date.now(),
                elapsedMs: 0,
                currentPhaseIndex: 0,
                isRunning: false,
                isPaused: false,
                totalCaloriesBurned: 0,
                phaseCaloriesBurned: 0
            };

            // Update session display
            updateCardioSessionDisplay();
        }

        function startCardioTimer() {
            if (window.cardioSession.isRunning) return;
            
            window.cardioSession.isRunning = true;
            window.cardioSession.isPaused = false;
            window.cardioSession.startTime = Date.now() - window.cardioSession.elapsedMs;
            
            // Start the timer loop
            window.cardioTimer = setInterval(updateCardioTimer, 1000);
            updateCardioSessionDisplay();
        }

        function pauseCardioTimer() {
            if (!window.cardioSession.isRunning) return;
            
            window.cardioSession.isRunning = false;
            window.cardioSession.isPaused = true;
            clearInterval(window.cardioTimer);
            updateCardioSessionDisplay();
        }

        function resumeCardioTimer() {
            if (window.cardioSession.isRunning) return;
            
            window.cardioSession.isRunning = true;
            window.cardioSession.isPaused = false;
            window.cardioSession.startTime = Date.now() - window.cardioSession.elapsedMs;
            window.cardioTimer = setInterval(updateCardioTimer, 1000);
            updateCardioSessionDisplay();
        }

function stopCardioTimer(completed = false) {
    window.cardioSession.isRunning = false;
    window.cardioSession.isPaused = false;
    clearInterval(window.cardioTimer);
    if (completed) {
        // Save only if session reached natural completion
        saveCardioWorkout();
    }
    updateCardioSessionDisplay();
}

        function updateCardioTimer() {
            if (!window.cardioSession.isRunning) return;
            
            const now = Date.now();
            window.cardioSession.elapsedMs = now - window.cardioSession.startTime;
            
            // Check if session is complete
            if (window.cardioSession.elapsedMs >= window.cardioSession.totalMs) {
                stopCardioTimer(true);
                showNotification('success', 'Workout completed! Great job!', '🎉');
                return;
            }
            
            // Update current phase
            updateCurrentPhase();
            
            // Calculate calories burned
            calculateCaloriesBurned();
            
            // Update display
            updateCardioSessionDisplay();
        }

        function updateCurrentPhase() {
            let cumulativeMs = 0;
            let newPhaseIndex = 0;
            
            for (let i = 0; i < window.cardioSession.phases.length; i++) {
                cumulativeMs += window.cardioSession.phases[i].durationMs;
                if (window.cardioSession.elapsedMs < cumulativeMs) {
                    newPhaseIndex = i;
                    break;
                }
            }
            
            if (newPhaseIndex !== window.cardioSession.currentPhaseIndex) {
                window.cardioSession.currentPhaseIndex = newPhaseIndex;
                window.cardioSession.phaseCaloriesBurned = 0;
                
                // Mark previous phase as complete in checklist
                const checkboxes = document.querySelectorAll('#sessionChecklist input[type="checkbox"]');
                if (checkboxes[window.cardioSession.currentPhaseIndex - 1]) {
                    checkboxes[window.cardioSession.currentPhaseIndex - 1].checked = true;
                }
            }
        }

        function calculateCaloriesBurned() {
            const currentPhase = window.cardioSession.phases[window.cardioSession.currentPhaseIndex];
            const phaseElapsedMs = Math.min(
                window.cardioSession.elapsedMs - getPhaseStartMs(window.cardioSession.currentPhaseIndex),
                currentPhase.durationMs
            );
            const phaseElapsedMinutes = phaseElapsedMs / (60 * 1000);
            
            // Calculate calories for current phase (assuming 70kg person, adjust as needed)
            const phaseCalories = phaseElapsedMinutes * currentPhase.caloriesPerMin;
            window.cardioSession.phaseCaloriesBurned = Math.round(phaseCalories);
            
            // Calculate total calories
            let totalCalories = 0;
            for (let i = 0; i < window.cardioSession.currentPhaseIndex; i++) {
                const phase = window.cardioSession.phases[i];
                const phaseMinutes = phase.durationMs / (60 * 1000);
                totalCalories += phaseMinutes * phase.caloriesPerMin;
            }
            totalCalories += phaseCalories;
            window.cardioSession.totalCaloriesBurned = Math.round(totalCalories);
        }

        function getPhaseStartMs(phaseIndex) {
            let startMs = 0;
            for (let i = 0; i < phaseIndex; i++) {
                startMs += window.cardioSession.phases[i].durationMs;
            }
            return startMs;
        }

        function updateCardioSessionDisplay() {
            const session = window.cardioSession;
            if (!session) return;
            
            // Update timer display
            const remainingMs = Math.max(0, session.totalMs - session.elapsedMs);
            const remainingMinutes = Math.floor(remainingMs / (60 * 1000));
            const remainingSeconds = Math.floor((remainingMs % (60 * 1000)) / 1000);
            
            const timerEl = document.getElementById('sessionTimer');
            if (timerEl) {
                timerEl.textContent = `${remainingMinutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
            }
            
            // Update progress bar
            const progressPercent = (session.elapsedMs / session.totalMs) * 100;
            const progressBar = document.getElementById('sessionProgress');
            if (progressBar) {
                progressBar.style.width = `${progressPercent}%`;
            }
            
            // Update current phase
            const currentPhase = session.phases[session.currentPhaseIndex];
            const phaseEl = document.getElementById('currentPhase');
            if (phaseEl && currentPhase) {
                phaseEl.textContent = currentPhase.key;
            }
            
            // Update calories burned
            const caloriesEl = document.getElementById('caloriesBurned');
            if (caloriesEl) {
                caloriesEl.textContent = session.totalCaloriesBurned;
            }
            
            // Update phase progress
            const phaseProgressEl = document.getElementById('phaseProgress');
            if (phaseProgressEl && currentPhase) {
                const phaseElapsedMs = Math.min(
                    session.elapsedMs - getPhaseStartMs(session.currentPhaseIndex),
                    currentPhase.durationMs
                );
                const phaseProgressPercent = (phaseElapsedMs / currentPhase.durationMs) * 100;
                phaseProgressEl.style.width = `${phaseProgressPercent}%`;
            }
            
            // Update next phase
            const nextPhaseEl = document.getElementById('nextPhase');
            if (nextPhaseEl) {
                const nextPhase = session.phases[session.currentPhaseIndex + 1];
                nextPhaseEl.textContent = nextPhase ? nextPhase.key : 'Workout Complete';
            }
            
            // Update button states
            updateCardioButtonStates();
        }

        function updateCardioButtonStates() {
            const session = window.cardioSession;
            if (!session) return;
            
            const startBtn = document.getElementById('startWorkoutBtn');
            const pauseBtn = document.getElementById('pauseWorkoutBtn');
            const resumeBtn = document.getElementById('resumeWorkoutBtn');
            const endBtn = document.getElementById('endWorkoutBtn');
            
            if (startBtn) startBtn.disabled = session.isRunning || session.isPaused;
            if (pauseBtn) pauseBtn.disabled = !session.isRunning;
            if (resumeBtn) resumeBtn.disabled = !session.isPaused;
            if (endBtn) endBtn.disabled = false;
        }

        async function saveCardioWorkout() {
            const session = window.cardioSession;
            if (!session) return;
            
            const durationMinutes = Math.round(session.elapsedMs / (60 * 1000));
            const caloriesBurned = session.totalCaloriesBurned;
            
            try {
                const formData = new FormData();
                formData.append('action', 'log_workout');
                formData.append('workout_name', session.title);
                formData.append('workout_type', 'cardio');
                formData.append('duration_minutes', durationMinutes.toString());
                formData.append('calories_burned', caloriesBurned.toString());
                formData.append('intensity', session.phases[session.currentPhaseIndex]?.intensity || 'Moderate');
                
                const response = await fetch('../php/workouts.php', {
                    method: 'POST',
                    body: formData
                });
                
            const result = await response.json();
            if (result.success) {
                    showNotification('success', `Workout saved! ${caloriesBurned} calories burned.`, '💪');
                    try { if (window.refreshWorkoutCount) { window.refreshWorkoutCount(); } } catch(_) {}
                }

		// Save any generic/legacy workout (e.g., featured) by estimating calories modestly
		async function saveLegacyWorkout(elapsedMs) {
			const minutes = Math.round((elapsedMs || 0) / (60 * 1000));
			const calories = Math.max(0, Math.round(minutes * 6)); // approx 6 cal/min default
			try {
				const headerEl = document.querySelector('#activeWorkoutSession .card-header h5');
				const title = headerEl ? headerEl.textContent.replace(' — In Progress','') : 'Workout Session';
				const formData = new FormData();
				formData.append('action', 'log_workout');
				formData.append('workout_name', title);
				formData.append('workout_type', 'general');
				formData.append('duration_minutes', minutes.toString());
				formData.append('calories_burned', calories.toString());
				formData.append('intensity', 'Moderate');
                const res = await fetch('../php/workouts.php', { method: 'POST', body: formData });
                try { if (res.ok && window.refreshWorkoutCount) { window.refreshWorkoutCount(); } } catch(_) {}
			} catch (_) { /* swallow */ }
		}
            } catch (error) {
                console.error('Error saving workout:', error);
                showNotification('warning', 'Workout completed but could not save to history.', '⚠️');
            }
        }

        
        // Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize counters
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
            
            // Filter functionality
            let currentFilter = 'all';
            let currentDifficulty = '';
            let currentDuration = '';
            
            // Category filter buttons
            const filterButtons = document.querySelectorAll('[data-filter]');
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    currentFilter = this.getAttribute('data-filter');
                    applyFilters();
                });
            });
            
            // Difficulty filter
            const difficultyFilter = document.getElementById('difficultyFilter');
            difficultyFilter.addEventListener('change', function() {
                currentDifficulty = this.value;
                applyFilters();
            });
            
            // Duration filter
            const durationFilter = document.getElementById('durationFilter');
            durationFilter.addEventListener('change', function() {
                currentDuration = this.value;
                applyFilters();
            });
            
            function applyFilters() {
                const workoutCards = document.querySelectorAll('.workout-card');
                
                workoutCards.forEach(card => {
                    const category = card.getAttribute('data-category');
                    const difficulty = card.getAttribute('data-difficulty');
                    const duration = parseInt(card.getAttribute('data-duration'));
                    
                    let showCard = true;
                    
                    // Category filter
                    if (currentFilter !== 'all' && category !== currentFilter) {
                        showCard = false;
                    }
                    
                    // Difficulty filter
                    if (currentDifficulty && difficulty !== currentDifficulty) {
                        showCard = false;
                    }
                    
                    // Duration filter
                    if (currentDuration) {
                        const durationValue = parseInt(currentDuration);
                        if (durationValue === 15 && duration > 15) {
                            showCard = false;
                        } else if (durationValue === 30 && (duration <= 15 || duration > 30)) {
                            showCard = false;
                        } else if (durationValue === 45 && (duration <= 30 || duration > 45)) {
                            showCard = false;
                        } else if (durationValue === 60 && duration <= 45) {
                            showCard = false;
                        }
                    }
                    
                    // Show/hide card with animation
                    if (showCard) {
                        card.classList.remove('hidden');
                        card.style.display = 'flex';
                    } else {
                        card.classList.add('hidden');
                        card.style.display = 'none';
                    }
                });
                
                // Update category sections visibility
                updateCategorySections();
            }
            
            function updateCategorySections() {
                const sections = ['cardioWorkouts', 'strengthWorkouts', 'hiitWorkouts', 'flexibilityWorkouts'];
                
                sections.forEach(sectionId => {
                    const section = document.getElementById(sectionId);
                    if (section) {
                        const visibleCards = section.querySelectorAll('.workout-card:not(.hidden)');
                        const sectionContainer = section.closest('.row').closest('.card');
                        
                        if (visibleCards.length === 0 && currentFilter !== 'all') {
                            sectionContainer.style.display = 'none';
                        } else {
                            sectionContainer.style.display = 'block';
                        }
                    }
                });
            }
            
            // Initialize filters
            applyFilters();

            // Complete Body Transformation — In Progress panel logic
            (function(){
                const $ = (id) => document.getElementById(id);
                const totalMin = 45;
                const totalCal = 400;
                const warm = 7, main = 31, cool = 7;
                const totalSec = totalMin * 60;
                const warmSec = warm*60, mainSec = main*60, coolSec = cool*60;

                const fmt = (sec) => {
                    const m = Math.floor(sec/60).toString().padStart(2,'0');
                    const s = Math.floor(sec%60).toString().padStart(2,'0');
                    return `${m}:${s}`;
                };

                if ($('cbtTotal')) $('cbtTotal').textContent = String(totalMin);
                if ($('cbtCaloriesTarget')) $('cbtCaloriesTarget').textContent = String(totalCal);
                if ($('cbtWarmTotal')) $('cbtWarmTotal').textContent = String(warm);
                if ($('cbtMainTotal')) $('cbtMainTotal').textContent = String(main);
                if ($('cbtCoolTotal')) $('cbtCoolTotal').textContent = String(cool);

                let elapsedSec = 0;
                let running = false;
                let raf = null;
                let lastTs = null;

                function currentStage(){
                    if (elapsedSec < warmSec) return 'Warm-up';
                    if (elapsedSec < warmSec + mainSec) return 'Main';
                    return 'Cool-down';
                }

                function updateUI(){
                    const remaining = Math.max(0, totalSec - elapsedSec);
                    $('cbtTimer') && ($('cbtTimer').textContent = fmt(remaining));
                    $('cbtElapsed') && ($('cbtElapsed').textContent = String(Math.floor(elapsedSec/60)));

                    const progress = (elapsedSec/totalSec)*100;
                    $('cbtProgressBar') && ($('cbtProgressBar').style.width = progress.toFixed(1)+'%');
                    $('cbtProgressText') && ($('cbtProgressText').textContent = Math.round(progress)+'%');

                    const calories = (elapsedSec/totalSec)* totalCal;
                    $('cbtCalories') && ($('cbtCalories').textContent = String(Math.round(calories)));

                    const st = currentStage();
                    $('cbtStage') && ($('cbtStage').textContent = (st==='Main'?'Main Workout':st));
                    const warmElapsed = Math.min(elapsedSec, warmSec);
                    const mainElapsed = Math.max(0, Math.min(elapsedSec - warmSec, mainSec));
                    const coolElapsed = Math.max(0, Math.min(elapsedSec - warmSec - mainSec, coolSec));
                    $('cbtWarm') && ($('cbtWarm').textContent = String(Math.floor(warmElapsed/60)));
                    $('cbtMain') && ($('cbtMain').textContent = String(Math.floor(mainElapsed/60)));
                    $('cbtCool') && ($('cbtCool').textContent = String(Math.floor(coolElapsed/60)));
                    $('cbtWarmBar') && ($('cbtWarmBar').style.width = (warmElapsed/warmSec*100)+'%');
                    $('cbtMainBar') && ($('cbtMainBar').style.width = (mainElapsed/mainSec*100)+'%');
                    $('cbtCoolBar') && ($('cbtCoolBar').style.width = (coolElapsed/coolSec*100)+'%');
                }

                function tick(ts){
                    if (!running) return;
                    if (lastTs == null) lastTs = ts;
                    const delta = (ts - lastTs)/1000;
                    lastTs = ts;
                    elapsedSec = Math.min(elapsedSec + delta, totalSec);
                    updateUI();
                    if (elapsedSec >= totalSec) { stop(); return; }
                    raf = requestAnimationFrame(tick);
                }

                function start(){ if (running) return; running = true; lastTs=null; raf=requestAnimationFrame(tick); toggleBtns('start'); $('cbtStatusBadge') && ($('cbtStatusBadge').textContent='In Progress'); }
                function pause(){ running=false; if (raf) cancelAnimationFrame(raf); toggleBtns('pause'); }
                function resume(){ if (running) return; running=true; lastTs=null; raf=requestAnimationFrame(tick); toggleBtns('resume'); }
                function stop(){
                    running=false;
                    if (raf) cancelAnimationFrame(raf);
                    toggleBtns('end');
                    $('cbtStatusBadge') && ($('cbtStatusBadge').textContent='Completed');
                    // Persist workout completion
                    try {
                        const caloriesEl = $('cbtCalories');
                        const calories = caloriesEl ? parseInt(caloriesEl.textContent || '0', 10) : 0;
                        const minutes = Math.round(elapsedSec / 60);
                        saveCbtCompletion(minutes, calories);
                    } catch (err) { /* no-op */ }
                }

                async function saveCbtCompletion(durationMinutes, caloriesBurned){
                    const form = new FormData();
                    form.append('action', 'log_workout');
                    form.append('workout_name', 'Complete Body Transformation');
                    form.append('duration_minutes', String(durationMinutes));
                    form.append('calories_burned', String(caloriesBurned));
                    try {
                        const res = await fetch('../php/workouts.php', { method: 'POST', body: form });
                        const result = await res.json();
                        if (result && result.success) {
                            // Optional: notify and reflect today total
                            if (result.today_total_calories !== undefined) {
                                // Could update a dashboard badge if present
                            }
                        } else {
                            // Optional: surface a non-blocking notice
                        }
                    } catch (e) {
                        // Optional: silent fail
                    }
                }

                function toggleBtns(state){
                    const sb=$('cbtStartBtn'), pb=$('cbtPauseBtn'), rb=$('cbtResumeBtn'), eb=$('cbtEndBtn');
                    if (!sb||!pb||!rb||!eb) return;
                    if(state==='start'){ sb.disabled=true; pb.disabled=false; rb.disabled=true; eb.disabled=false; }
                    if(state==='pause'){ sb.disabled=true; pb.disabled=true; rb.disabled=false; eb.disabled=false; }
                    if(state==='resume'){ sb.disabled=true; pb.disabled=false; rb.disabled=true; eb.disabled=false; }
                    if(state==='end'){ sb.disabled=false; pb.disabled=true; rb.disabled=true; eb.disabled=true; }
                }

                const detailsBtn = $('cbtToggleDetails');
                detailsBtn && detailsBtn.addEventListener('click', function(){
                    const d=$('cbtDetails'); if(!d) return; d.style.display = d.style.display==='none'?'block':'none';
                    this.querySelector('i')?.classList.toggle('fa-chevron-down');
                    this.querySelector('i')?.classList.toggle('fa-chevron-up');
                });

                const startBtn=$('cbtStartBtn'), pauseBtn=$('cbtPauseBtn'), resumeBtn=$('cbtResumeBtn'), endBtn=$('cbtEndBtn');
                startBtn && startBtn.addEventListener('click', async function(){
                    start();
                    try { await fetch('../php/workouts.php', { method:'POST', body: new URLSearchParams({ action:'cbt_start' }) }); } catch(e){}
                });
                pauseBtn && pauseBtn.addEventListener('click', async function(){
                    pause();
                    try { await fetch('../php/workouts.php', { method:'POST', body: new URLSearchParams({ action:'cbt_pause' }) }); } catch(e){}
                });
                resumeBtn && resumeBtn.addEventListener('click', async function(){
                    resume();
                    try { await fetch('../php/workouts.php', { method:'POST', body: new URLSearchParams({ action:'cbt_resume' }) }); } catch(e){}
                });
                endBtn && endBtn.addEventListener('click', async function(){
                    stop();
                    try {
                        const res = await fetch('../php/workouts.php', { method:'POST', body: new URLSearchParams({ action:'cbt_end' }) });
                        await refreshWorkoutCount();
                    } catch(e){}
                });

                // Wire up cardio session buttons
                const cardioStartBtn = document.getElementById('startWorkoutBtn');
                const cardioPauseBtn = document.getElementById('pauseWorkoutBtn');
                const cardioResumeBtn = document.getElementById('resumeWorkoutBtn');
                const cardioEndBtn = document.getElementById('endWorkoutBtn');

                if (cardioStartBtn) {
                    cardioStartBtn.addEventListener('click', startCardioTimer);
                }
                if (cardioPauseBtn) {
                    cardioPauseBtn.addEventListener('click', pauseCardioTimer);
                }
                if (cardioResumeBtn) {
                    cardioResumeBtn.addEventListener('click', resumeCardioTimer);
                }
                if (cardioEndBtn) {
                    cardioEndBtn.addEventListener('click', stopCardioTimer);
                }

                // Restore session on load
                (async function restore(){
                    try {
                        const res = await fetch('../php/workouts.php?action=cbt_get');
                        const data = await res.json();
                        if (data && data.success) {
                            const els = data.elapsed_seconds || 0;
                            elapsedSec = Math.min(els, totalSec);
                            updateUI();
                            if (data.status === 'in_progress') {
                                start(); // will set running and continue ticking
                            } else {
                                toggleBtns('end'); // default paused state
                            }
                        } else {
                            toggleBtns('end');
                            updateUI();
                        }
                    } catch (e) {
                        toggleBtns('end');
                        updateUI();
                    }
                })();

                // Update "Your Workouts" completed count initially and after completion
                window.refreshWorkoutCount = async function(){
                    try {
                        const res = await fetch('../php/workouts.php', { method:'POST', body: new URLSearchParams({ action:'get_workout_count' }) });
                        const json = await res.json();
                        if (json && json.success && typeof json.count === 'number') {
                            const el = document.getElementById('workoutsCompleted');
                            if (el) el.textContent = String(json.count);
                        }
                    } catch(_){}
                }
                window.refreshWorkoutCount();
            })();
        });
    </script>
</body>
</html>
