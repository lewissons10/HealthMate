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
    <title>Achievements - HealthMate</title>
    
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
                        <a class="nav-link" href="leaderboard.php">
                            <i class="fas fa-trophy me-1"></i>Leaderboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="achievements.php">
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
                            <h2 class="mb-2">🏆 Your Achievements</h2>
                            <p class="mb-0 opacity-75">Celebrate your fitness milestones and unlock new badges!</p>
                        </div>
                        <div class="col-lg-4 text-lg-end">
                            <div class="d-flex align-items-center justify-content-lg-end">
                                <div class="me-4 text-center">
                                    <div id="ach-total-earned" class="fw-bold fs-2">0</div>
                                    <small class="opacity-75">Achievements Earned</small>
                                </div>
                                <div class="me-4 text-center">
                                    <div id="ach-current-streak" class="fw-bold fs-2">0</div>
                                    <small class="opacity-75">Current Streak</small>
                                </div>
                                <div class="avatar-large">
                                    <i class="fas fa-medal fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Achievement Stats -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon me-3">
                            <i class="fas fa-medal text-warning"></i>
                        </div>
                        <div>
                            <div class="stat-number counter" data-target="8">0</div>
                            <div class="stat-label">Total Achievements</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon me-3">
                            <i class="fas fa-star text-primary"></i>
                        </div>
                        <div>
                            <div class="stat-number counter" data-target="3">0</div>
                            <div class="stat-label">Rare Achievements</div>
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
                            <div class="stat-number counter" data-target="15">0</div>
                            <div class="stat-label">Day Streak</div>
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
                            <div class="stat-number counter" data-target="85">0</div>
                            <div class="stat-label">Completion %</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Achievements -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-lg">
                    <div class="card-header bg-gradient-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-star me-2"></i>Recently Earned</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-4 col-md-6 mb-3">
                                <div class="achievement-card earned">
                                    <div class="achievement-icon">
                                        <i class="fas fa-fire fa-2x"></i>
                                    </div>
                                    <div class="achievement-content">
                                        <h6 class="achievement-title">Week Warrior</h6>
                                        <p class="achievement-desc">Complete 5 workouts in a week</p>
                                        <div class="achievement-meta">
                                            <span class="badge bg-success">Earned</span>
                                            <small class="text-muted">2 days ago</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-3">
                                <div class="achievement-card earned">
                                    <div class="achievement-icon">
                                        <i class="fas fa-dumbbell fa-2x"></i>
                                    </div>
                                    <div class="achievement-content">
                                        <h6 class="achievement-title">Strength Builder</h6>
                                        <p class="achievement-desc">Complete 10 strength training workouts</p>
                                        <div class="achievement-meta">
                                            <span class="badge bg-success">Earned</span>
                                            <small class="text-muted">1 week ago</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-3">
                                <div class="achievement-card earned">
                                    <div class="achievement-icon">
                                        <i class="fas fa-heart fa-2x"></i>
                                    </div>
                                    <div class="achievement-content">
                                        <h6 class="achievement-title">First Steps</h6>
                                        <p class="achievement-desc">Complete your first workout</p>
                                        <div class="achievement-meta">
                                            <span class="badge bg-success">Earned</span>
                                            <small class="text-muted">2 weeks ago</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Achievement Categories -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-primary active" data-category="all">
                                <i class="fas fa-th-large me-1"></i>All Badges
                            </button>
                            <button class="btn btn-outline-primary" data-category="workouts">
                                <i class="fas fa-dumbbell me-1"></i>Workout Completion
                            </button>
                            <button class="btn btn-outline-primary" data-category="streaks">
                                <i class="fas fa-fire me-1"></i>Streak Badges
                            </button>
                            <button class="btn btn-outline-primary" data-category="category">
                                <i class="fas fa-layer-group me-1"></i>Categories
                            </button>
                            <button class="btn btn-outline-primary" data-category="special">
                                <i class="fas fa-star me-1"></i>Special Milestones
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dynamic badges container -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-gradient-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-medal me-2"></i>Your Badges</h5>
                    </div>
                    <div class="card-body">
                        <div id="badges-grid" class="row"></div>
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
        
        .achievement-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            border: 2px solid var(--gray-200);
            transition: var(--transition-normal);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 1rem;
            height: 100%;
        }
        
        /* hover removed */
        
        .achievement-card.earned {
            border-color: var(--success-color);
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
        }
        
        .achievement-card.locked {
            border-color: var(--gray-300);
            background: var(--gray-50);
            opacity: 0.7;
        }
        
        /* hover removed */
        
        .achievement-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .achievement-card.earned .achievement-icon {
            background: linear-gradient(135deg, var(--success-color), #34d399);
            color: white;
        }
        
        .achievement-card.locked .achievement-icon {
            background: var(--gray-300);
            color: var(--gray-500);
        }
        
        .achievement-content {
            flex: 1;
        }
        
        .achievement-title {
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .achievement-desc {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-bottom: 0.75rem;
        }
        
        .achievement-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        
        .bg-gradient-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        }
        
        .bg-gradient-success {
            background: linear-gradient(135deg, var(--secondary-color), var(--secondary-dark));
        }
        
        .bg-gradient-warning {
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

        /* Readability overrides scoped to Achievements page */
        .container-fluid .dashboard-stats,
        .container-fluid .stat-card,
        .container-fluid .card-body,
        .container-fluid .card,
        .container-fluid .achievement-card {
            background: #ffffff !important;
            color: #000000 !important;
        }

        /* Ensure headings and labels are clearly visible */
        .container-fluid h2,
        .container-fluid h5,
        .container-fluid h6,
        .container-fluid .stat-number,
        .container-fluid .stat-label,
        .container-fluid .achievement-title,
        .container-fluid .achievement-desc {
            color: #000000 !important;
        }

        /* Make muted text more readable on light backgrounds */
        .container-fluid .text-muted,
        .container-fluid small.opacity-75,
        .container-fluid p.opacity-75 {
            color: #4b5563 !important; /* slate-600 */
            opacity: 1 !important;
        }

        /* Keep gradient headers white while body text stays dark */
        .container-fluid .card-header.bg-gradient-primary,
        .container-fluid .card-header.bg-gradient-success,
        .container-fluid .card-header.bg-gradient-warning,
        .container-fluid .card-header.bg-gradient-info {
            color: #ffffff !important;
        }

        /* Earned/locked cards: keep readable while retaining status accent */
        .achievement-card.earned {
            background: #ffffff !important;
            border-color: var(--success-color) !important;
        }
        .achievement-card.locked {
            background: #ffffff !important;
            border-color: var(--gray-300) !important;
            opacity: 1 !important;
        }
    </style>
    
    <script>
        // Achievements page specific functions
        async function fetchAchievementStats() {
            try {
                const res = await fetch('../php/workouts.php', { method: 'POST', headers: { 'Accept': 'application/json' }, body: new URLSearchParams({ action: 'achievement_stats' }) });
                const json = await res.json();
                if (!json.success) throw new Error('Failed to load achievements');
                return json.data;
            } catch (e) { return null; }
        }

        function badgeIconFor(group, title) {
            if (group === 'workouts') return 'fa-dumbbell';
            if (group === 'streaks') return 'fa-fire';
            if (group === 'category') return 'fa-layer-group';
            if (group === 'special') return 'fa-star';
            return 'fa-medal';
        }

        function renderBadges(badges, activeCat = 'all') {
            const grid = document.getElementById('badges-grid');
            if (!grid) return;
            grid.innerHTML = '';
            const filtered = badges.filter(b => activeCat === 'all' || b.group === activeCat);
            filtered.forEach(b => {
                const col = document.createElement('div');
                col.className = 'col-lg-3 col-md-6 mb-3';
                const pct = Math.min(100, Math.floor((Math.min(b.progress || 0, b.threshold || 1) / Math.max(1, b.threshold || 1)) * 100));
                col.innerHTML = `
                    <div class="achievement-card ${b.earned ? 'earned' : 'locked'}">
                        <div class="achievement-icon"><i class="fas ${badgeIconFor(b.group, b.title)} fa-2x"></i></div>
                        <div class="achievement-content">
                            <h6 class="achievement-title">${b.title}</h6>
                            <p class="achievement-desc">${b.group === 'streaks' ? 'Best streak' : 'Progress'}: ${b.progress || 0}/${b.threshold}</p>
                            <div class="achievement-meta">
                                <span class="badge ${b.earned ? 'bg-success' : 'bg-secondary'}">${b.earned ? 'Earned' : 'Locked'}</span>
                                <small class="text-muted">${pct}%</small>
                            </div>
                        </div>
                    </div>`;
                grid.appendChild(col);
            });
        }
        function viewAchievement(achievementId) {
            showNotification('info', 'Opening achievement details...', '🏆 Achievement Details');
            // In a real app, this would show achievement details
        }
        
        // Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Load and render achievements
            fetchAchievementStats().then(data => {
                if (!data) return;
                const earned = (data.badges || []).filter(b => b.earned).length;
                const totalsEl = document.getElementById('ach-total-earned');
                const streakEl = document.getElementById('ach-current-streak');
                if (totalsEl) totalsEl.textContent = String(earned);
                if (streakEl) streakEl.textContent = String(data.totals?.current_streak || 0);
                renderBadges(data.badges || [], 'all');
            });
            
            // Category filter buttons
            const categoryButtons = document.querySelectorAll('[data-category]');
            categoryButtons.forEach(button => {
                button.addEventListener('click', function() {
                    categoryButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    const cat = this.getAttribute('data-category') || 'all';
                    fetchAchievementStats().then(data => {
                        if (!data) return;
                        renderBadges(data.badges || [], cat);
                    });
                });
            });
            
            // Achievement card click handlers
            const achievementCards = document.querySelectorAll('.achievement-card');
            achievementCards.forEach(card => {
                card.addEventListener('click', function() {
                    const isEarned = this.classList.contains('earned');
                    if (isEarned) {
                        showNotification('success', 'Achievement unlocked!', '🎉 Congratulations!');
                    } else {
                        showNotification('info', 'Keep working to unlock this achievement!', '🔒 Locked Achievement');
                    }
                });
            });
        });
    </script>
</body>
</html>
