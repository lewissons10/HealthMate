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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - HealthMate</title>
    
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
                        <a class="nav-link" href="workouts.php">
                            <i class="fas fa-dumbbell me-1"></i>Workouts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="leaderboard.php">
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
                            <h2 class="mb-2">🏆 Global Leaderboard</h2>
                            <p class="mb-0 opacity-75">Compete with fitness enthusiasts worldwide and climb the ranks!</p>
                        </div>
                        <div class="col-lg-4 text-lg-end">
                            <div class="d-flex align-items-center justify-content-lg-end">
                                <div class="me-3 text-center">
                                    <div class="fw-bold fs-2 counter" data-target="2840">0</div>
                                    <small class="opacity-75">Your Points</small>
                                </div>
                                <div class="avatar-large">
                                    <i class="fas fa-trophy fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leaderboard Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-lg-8">
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-primary active" data-period="all">
                                        <i class="fas fa-calendar-alt me-1"></i>All Time
                                    </button>
                                    <button class="btn btn-outline-primary" data-period="month">
                                        <i class="fas fa-calendar me-1"></i>This Month
                                    </button>
                                    <button class="btn btn-outline-primary" data-period="week">
                                        <i class="fas fa-calendar-week me-1"></i>This Week
                                    </button>
                                    <button class="btn btn-outline-primary" data-period="today">
                                        <i class="fas fa-calendar-day me-1"></i>Today
                                    </button>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="d-flex gap-2">
                                    <select class="form-select" id="categoryFilter">
                                        <option value="">All Categories</option>
                                        <option value="workouts">Most Workouts</option>
                                        <option value="calories">Calories Burned</option>
                                        <option value="streak">Longest Streak</option>
                                        <option value="achievements">Most Achievements</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top 3 Podium -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-lg">
                    <div class="card-header bg-gradient-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-crown me-2"></i>Top Performers</h5>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-end" id="topPodium">
                            <!-- 2nd Place -->
                            <div class="col-lg-4 col-md-4 text-center mb-3">
                                <div class="podium-item second-place">
                                    <div class="podium-avatar">
                                        <i class="fas fa-user-circle fa-3x"></i>
                                    </div>
                                    <div class="podium-rank">🥈</div>
                                    <h5 class="mb-1">Mike Chen</h5>
                                    <p class="text-muted mb-2">2,650 points</p>
                                    <div class="podium-stats">
                                        <span class="badge bg-light text-dark me-1">12 day streak</span>
                                        <span class="badge bg-light text-dark">45 workouts</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 1st Place -->
                            <div class="col-lg-4 col-md-4 text-center mb-3">
                                <div class="podium-item first-place">
                                    <div class="podium-avatar">
                                        <i class="fas fa-user-circle fa-4x"></i>
                                    </div>
                                    <div class="podium-rank">👑</div>
                                    <h4 class="mb-1">Sarah Johnson</h4>
                                    <p class="text-muted mb-2">2,840 points</p>
                                    <div class="podium-stats">
                                        <span class="badge bg-warning me-1">15 day streak</span>
                                        <span class="badge bg-success">52 workouts</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 3rd Place -->
                            <div class="col-lg-4 col-md-4 text-center mb-3">
                                <div class="podium-item third-place">
                                    <div class="podium-avatar">
                                        <i class="fas fa-user-circle fa-2x"></i>
                                    </div>
                                    <div class="podium-rank">🥉</div>
                                    <h6 class="mb-1">Emma Davis</h6>
                                    <p class="text-muted mb-2">2,480 points</p>
                                    <div class="podium-stats">
                                        <span class="badge bg-light text-dark me-1">10 day streak</span>
                                        <span class="badge bg-light text-dark">38 workouts</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Your Position -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-gradient-success text-white">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Your Position</h5>
                    </div>
                    <div class="card-body">
                        <div class="your-position">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="rank-badge rank-other me-3">
                                        7
                                    </div>
                                    <div>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($user['first_name']); ?> <?php echo htmlspecialchars($user['last_name']); ?></h5>
                                        <p class="text-muted mb-0">1,780 points • 8 day streak</p>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary">Your Rank</span>
                                        <button class="btn btn-outline-primary btn-sm" onclick="viewProfile()">
                                            <i class="fas fa-eye me-1"></i>View Profile
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Full Leaderboard -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-gradient-info text-white">
                        <h5 class="mb-0"><i class="fas fa-list-ol me-2"></i>Complete Leaderboard</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="leaderboard-list" id="leaderboard">
                            <!-- Leaderboard items will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leaderboard Stats -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon me-3">
                            <i class="fas fa-users text-primary"></i>
                        </div>
                        <div>
                            <div class="stat-number counter" data-target="1250">0</div>
                            <div class="stat-label">Total Participants</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon me-3">
                            <i class="fas fa-fire text-danger"></i>
                        </div>
                        <div>
                            <div class="stat-number counter" data-target="2840">0</div>
                            <div class="stat-label">Highest Score</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon me-3">
                            <i class="fas fa-clock text-warning"></i>
                        </div>
                        <div>
                            <div class="stat-number counter" data-target="15">0</div>
                            <div class="stat-label">Longest Streak</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon me-3">
                            <i class="fas fa-trophy text-success"></i>
                        </div>
                        <div>
                            <div class="stat-number counter" data-target="52">0</div>
                            <div class="stat-label">Most Workouts</div>
                        </div>
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
        
        .podium-item {
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            transition: var(--transition-normal);
        }
        
        /* hover removed */
        
        .first-place {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #333;
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.3);
        }
        
        .second-place {
            background: linear-gradient(135deg, #c0c0c0, #e5e5e5);
            color: #333;
            box-shadow: 0 8px 25px rgba(192, 192, 192, 0.3);
        }
        
        .third-place {
            background: linear-gradient(135deg, #cd7f32, #daa520);
            color: white;
            box-shadow: 0 6px 20px rgba(205, 127, 50, 0.3);
        }
        
        .podium-avatar {
            margin-bottom: 1rem;
        }
        
        .podium-rank {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .podium-stats {
            margin-top: 1rem;
        }
        
        .your-position {
            padding: 1rem;
            background: rgba(16, 185, 129, 0.1);
            border-radius: var(--radius-lg);
            border: 2px solid rgba(16, 185, 129, 0.2);
        }
        
        .leaderboard-list {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .leaderboard-item {
            display: flex;
            align-items: center;
            justify-content: between;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            transition: var(--transition-fast);
        }
        
        /* hover removed */
        
        .leaderboard-item:last-child {
            border-bottom: none;
        }
        
        .rank-badge {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: white;
            font-size: 1.25rem;
            box-shadow: var(--shadow-md);
        }
        
        .rank-1 { 
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
        }
        .rank-2 { 
            background: linear-gradient(135deg, #c0c0c0, #e5e5e5);
            box-shadow: 0 0 20px rgba(192, 192, 192, 0.5);
        }
        .rank-3 { 
            background: linear-gradient(135deg, #cd7f32, #daa520);
            box-shadow: 0 0 20px rgba(205, 127, 50, 0.5);
        }
        .rank-other { 
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        
        .bg-gradient-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        }
        
        .bg-gradient-success {
            background: linear-gradient(135deg, var(--secondary-color), var(--secondary-dark));
        }
        
        .bg-gradient-info {
            background: linear-gradient(135deg, var(--info-color), #1d4ed8);
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

        /* Readability overrides scoped to Leaderboard page */
        .container-fluid .card,
        .container-fluid .card-body,
        .container-fluid .dashboard-stats,
        .container-fluid .stat-card,
        .container-fluid .leaderboard-list,
        .container-fluid .leaderboard-item {
            background: #ffffff !important;
            color: #000000 !important;
        }

        /* Keep gradient headers readable */
        .container-fluid .card-header.bg-gradient-primary,
        .container-fluid .card-header.bg-gradient-success,
        .container-fluid .card-header.bg-gradient-info,
        .container-fluid .card-header.bg-gradient-warning {
            color: #ffffff !important;
        }

        /* Ensure headings and body text are dark for contrast */
        .container-fluid h2,
        .container-fluid h4,
        .container-fluid h5,
        .container-fluid h6,
        .container-fluid p,
        .container-fluid li,
        .container-fluid .stat-number,
        .container-fluid .stat-label {
            color: #000000 !important;
        }

        /* Muted text: increase contrast */
        .container-fluid .text-muted,
        .container-fluid small.opacity-75,
        .container-fluid p.opacity-75 {
            color: #4b5563 !important; /* slate-600 */
            opacity: 1 !important;
        }

        /* Podium items: ensure readable text on gradients */
        .first-place h4, .first-place p,
        .second-place h5, .second-place p,
        .third-place h6, .third-place p {
            color: inherit !important;
        }

        /* Your position strip stays readable */
        .your-position { color: #000000 !important; }
    </style>
    
    <script>
        // Leaderboard page specific functions
        function viewProfile() {
            // Redirect to the current user's profile page
            window.location.href = 'profile.php';
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
            
            // Period filter buttons (refetch on change) with proper visual toggle
            const periodButtons = document.querySelectorAll('[data-period]');
            function setActivePeriodButton(activeBtn) {
                periodButtons.forEach(btn => {
                    btn.classList.remove('active');
                    btn.classList.remove('btn-primary');
                    if (!btn.classList.contains('btn-outline-primary')) {
                        btn.classList.add('btn-outline-primary');
                    }
                });
                activeBtn.classList.add('active');
                activeBtn.classList.remove('btn-outline-primary');
                activeBtn.classList.add('btn-primary');
            }
            periodButtons.forEach(button => {
                button.addEventListener('click', function() {
                    setActivePeriodButton(this);
                    if (typeof loadLeaderboard === 'function') {
                        loadLeaderboard();
                    }
                });
            });

            // Category filter (refetch on change)
            const categoryFilter = document.getElementById('categoryFilter');
            if (categoryFilter) {
                categoryFilter.addEventListener('change', function() {
                    if (typeof loadLeaderboard === 'function') {
                        loadLeaderboard();
                    }
                });
            }
        });
    </script>
</body>
</html>

