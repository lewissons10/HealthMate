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

// Get user's workout statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total_workouts FROM user_progress WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$workoutStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent achievements
$stmt = $pdo->prepare(
    "SELECT a.*, ua.earned_at AS earned_date
     FROM user_achievements ua
     INNER JOIN achievements a ON ua.achievement_id = a.id
     WHERE ua.user_id = ?
     ORDER BY ua.earned_at DESC
     LIMIT 5"
);
$stmt->execute([$_SESSION['user_id']]);
$recentAchievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - HealthMate</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="profile-page">
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
                        <a class="nav-link" href="achievements.php">
                            <i class="fas fa-medal me-1"></i>Achievements
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center active" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <div class="avatar me-2">
                                <i class="fas fa-user-circle fa-lg"></i>
                            </div>
                            <span><?php echo htmlspecialchars($user['first_name']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item active" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            
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
        <!-- Profile Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="profile-header">
                    <div class="row align-items-center">
                        <div class="col-lg-3 col-md-4 text-center mb-3">
                            <div class="profile-avatar">
                                <i class="fas fa-user-circle fa-6x"></i>
                            </div>
                        </div>
                        <div class="col-lg-9 col-md-8">
                            <div class="profile-info">
                                <h1 class="mb-2"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                                <p class="text-muted mb-3">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                                
                                <!-- Quick Stats -->
                                <div class="row">
                                    <div class="col-md-3 col-6 mb-2">
                                        <div class="quick-stat">
                                            <div class="stat-number"><?php echo $user['points']; ?></div>
                                            <div class="stat-label">Total Points</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-2">
                                        <div class="quick-stat">
                                            <div class="stat-number"><?php echo $workoutStats['total_workouts'] ?? 0; ?></div>
                                            <div class="stat-label">Workouts</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-2">
                                        <div class="quick-stat">
                                            <div class="stat-number"><?php echo count($recentAchievements); ?></div>
                                            <div class="stat-label">Achievements</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-2">
                                        <div class="quick-stat">
                                            <div class="stat-number"><?php echo $user['level'] ?? 1; ?></div>
                                            <div class="stat-label">Level</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Content -->
        <div class="row">
            <!-- Personal Information -->
            <div class="col-lg-8 mb-4">
                <div class="card bg-white">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h5>
                    </div>
                    <div class="card-body">
                        <form id="profileForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="firstName" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="lastName" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="dateOfBirth" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="dateOfBirth" name="dateOfBirth" value="<?php echo $user['date_of_birth'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value="">Select Gender</option>
                                        <option value="male" <?php echo ($user['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo ($user['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo ($user['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="height" class="form-label">Height (cm)</label>
                                    <input type="number" class="form-control" id="height" name="height" value="<?php echo $user['height'] ?? ''; ?>" min="100" max="250">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="weight" class="form-label">Weight (kg)</label>
                                    <input type="number" class="form-control" id="weight" name="weight" value="<?php echo $user['weight'] ?? ''; ?>" min="30" max="300" step="0.1">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="3" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Fitness Goals -->
                <div class="card mt-4 bg-white">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-target me-2"></i>Fitness Goals</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fitnessGoal" class="form-label">Primary Goal</label>
                                <select class="form-select" id="fitnessGoal" name="fitnessGoal">
                                    <option value="weight_loss" <?php echo ($user['fitness_goal'] ?? '') === 'weight_loss' ? 'selected' : ''; ?>>Weight Loss</option>
                                    <option value="muscle_gain" <?php echo ($user['fitness_goal'] ?? '') === 'muscle_gain' ? 'selected' : ''; ?>>Muscle Gain</option>
                                    <option value="endurance" <?php echo ($user['fitness_goal'] ?? '') === 'endurance' ? 'selected' : ''; ?>>Endurance</option>
                                    <option value="flexibility" <?php echo ($user['flexibility'] ?? '') === 'flexibility' ? 'selected' : ''; ?>>Flexibility</option>
                                    <option value="general_fitness" <?php echo ($user['fitness_goal'] ?? '') === 'general_fitness' ? 'selected' : ''; ?>>General Fitness</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="targetWeight" class="form-label">Target Weight (kg)</label>
                                <input type="number" class="form-control" id="targetWeight" name="targetWeight" value="<?php echo $user['target_weight'] ?? ''; ?>" min="30" max="300" step="0.1">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="workoutsPerWeek" class="form-label">Workouts per Week</label>
                                <select class="form-select" id="workoutsPerWeek" name="workoutsPerWeek">
                                    <option value="2" <?php echo ($user['workouts_per_week'] ?? '') == '2' ? 'selected' : ''; ?>>2</option>
                                    <option value="3" <?php echo ($user['workouts_per_week'] ?? '') == '3' ? 'selected' : ''; ?>>3</option>
                                    <option value="4" <?php echo ($user['workouts_per_week'] ?? '') == '4' ? 'selected' : ''; ?>>4</option>
                                    <option value="5" <?php echo ($user['workouts_per_week'] ?? '') == '5' ? 'selected' : ''; ?>>5</option>
                                    <option value="6" <?php echo ($user['workouts_per_week'] ?? '') == '6' ? 'selected' : ''; ?>>6</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="experienceLevel" class="form-label">Experience Level</label>
                                <select class="form-select" id="experienceLevel" name="experienceLevel">
                                    <option value="beginner" <?php echo ($user['experience_level'] ?? '') === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                                    <option value="intermediate" <?php echo ($user['experience_level'] ?? '') === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                    <option value="advanced" <?php echo ($user['experience_level'] ?? '') === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                                </select>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-success" onclick="updateFitnessGoals()">
                            <i class="fas fa-check me-2"></i>Update Goals
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4 mb-4">
                <!-- Recent Achievements -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-medal me-2"></i>Recent Achievements</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentAchievements)): ?>
                            <?php foreach ($recentAchievements as $achievement): ?>
                                <div class="achievement-item d-flex align-items-center mb-3">
                                    <div class="achievement-icon me-3">
                                        <i class="fas fa-trophy text-warning"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($achievement['name']); ?></h6>
                                        <small class="text-muted"><?php echo date('M j, Y', strtotime($achievement['earned_date'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center">No achievements yet. Keep working out!</p>
                        <?php endif; ?>
                        <a href="achievements.php" class="btn btn-outline-primary btn-sm w-100">View All Achievements</a>
                    </div>
                </div>
                
                <!-- Account Settings -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Account Settings</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-outline-secondary btn-sm w-100 mb-2" onclick="changePassword()">
                            <i class="fas fa-key me-2"></i>Change Password
                        </button>
                        <button class="btn btn-outline-secondary btn-sm w-100 mb-2" onclick="exportData()">
                            <i class="fas fa-download me-2"></i>Export Data
                        </button>
                        <button class="btn btn-outline-danger btn-sm w-100" onclick="deleteAccount()">
                            <i class="fas fa-trash me-2"></i>Delete Account
                        </button>
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
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: #ffffff;
            padding: 2.5rem 2rem;
            border-radius: var(--radius-lg);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
        }
        
        .profile-avatar {
            text-align: center;
        }
        
        .profile-avatar i {
            color: rgba(255, 255, 255, 0.95);
        }

        /* Avatar ring */
        .profile-avatar i::before {
            box-shadow: 0 0 0 6px rgba(255, 255, 255, 0.15), 0 8px 24px rgba(0,0,0,0.25);
            border-radius: 50%;
        }
        
        .quick-stat {
            text-align: center;
            padding: 1rem;
            background: #ffffff;
            border-radius: var(--radius-md);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
        }
        
        .stat-number {
            font-size: 1.65rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
            line-height: 1.1;
            color: #111827;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #4b5563;
        }

        /* Remove any blinking/animation from profile header and quick stats */
        .profile-page .profile-header,
        .profile-page .quick-stat,
        .profile-page .stat-number,
        .profile-page .stat-label {
            animation: none !important;
            transition: none !important;
        }
        
        .achievement-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .achievement-item:last-child {
            border-bottom: none;
        }
        
        .achievement-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 193, 7, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--gray-700);
        }
        /* Ensure inputs/selects are white with dark text */
        .profile-page .form-control,
        .profile-page .form-select,
        .profile-page textarea.form-control {
            background-color: #ffffff !important;
            color: #212529 !important;
            border-color: #ced4da !important;
        }
        .profile-page .form-control::placeholder {
            color: #6c757d !important;
            opacity: 1;
        }
        .profile-page .form-control:focus,
        .profile-page .form-select:focus,
        .profile-page textarea.form-control:focus {
            background-color: #ffffff !important;
            color: #212529 !important;
            border-color: #ced4da !important;
            box-shadow: none !important;
        }
        
        .card {
            border: none;
            box-shadow: var(--shadow-sm);
            border-radius: var(--radius-lg);
            background: #ffffff;
        }
        
        .card-header {
            background: #ffffff;
            border-bottom: 1px solid var(--gray-200);
            font-weight: 700;
            color: #111827;
        }

        /* Sidebar list/tiles polish */
        .achievement-item h6 { color: #111827; font-weight: 700; }
        .achievement-item small { color: #6b7280 !important; }
        .achievement-icon { box-shadow: var(--shadow-sm); }

        /* Section spacing harmony */
        .profile-page .card + .card { margin-top: 1.25rem; }

        /* Disable hover effects specifically on the profile avatar */
        .profile-page .profile-avatar,
        .profile-page .profile-avatar *,
        .profile-page .profile-avatar:hover,
        .profile-page .profile-avatar *:hover,
        .profile-page .profile-avatar:focus,
        .profile-page .profile-avatar *:focus,
        .profile-page .profile-avatar:active,
        .profile-page .profile-avatar *:active {
            color: inherit !important;
            background: transparent !important;
            box-shadow: none !important;
            text-decoration: none !important;
            transform: none !important;
            filter: none !important;
            opacity: 1 !important;
            border-color: inherit !important;
            outline: none !important;
        }

        /* Remove all hover effects within profile page */
        @media (hover:hover) {
            .profile-page * {
                transition: none !important;
            }
            /* hover selectors removed at source in core CSS */
            /* hover selectors removed at source in core CSS */
        }

        /* Also neutralize focus/active visual states that mimic hover */
        .profile-page a { text-decoration: none !important; }
        .profile-page .btn:focus,
        .profile-page .btn:active,
        .profile-page .nav-link:focus,
        .profile-page .dropdown-item:focus,
        .profile-page button:focus {
            outline: none !important;
            box-shadow: none !important;
            background-color: inherit !important;
            color: inherit !important;
        }

        /* Ultra-specific overrides for stubborn hover states */
        /* hover removed */
        .profile-page .navbar .nav-link:focus,
        .profile-page .navbar .nav-link:active,
        .profile-page .navbar .nav-link.show,
        /* hover removed */
        .profile-page .navbar .dropdown-toggle:focus,
        /* hover removed */
        .profile-page .navbar-brand:focus {
            color: inherit !important;
            background: transparent !important;
            text-decoration: none !important;
            box-shadow: none !important;
        }

        /* hover removed */
        .profile-page .dropdown-menu .dropdown-item:focus,
        .profile-page .dropdown-menu .dropdown-item:active {
            background-color: transparent !important;
            color: inherit !important;
        }

        /* hover removed */
        .profile-page .list-group-item:focus,
        .profile-page .list-group-item:active {
            background-color: inherit !important;
            color: inherit !important;
            border-color: inherit !important;
            box-shadow: none !important;
        }

        /* Handle Bootstrap compound selectors */
        .profile-page .btn-check:focus + .btn,
        .profile-page .btn:focus-visible,
        .profile-page .btn:focus,
        .profile-page .btn:active,
        /* hover removed */
            outline: none !important;
            box-shadow: none !important;
            filter: none !important;
            transform: none !important;
        }

        /* Prevent card hover lift/shadow from any theme CSS */
        .profile-page .card {
            transition: none !important;
        }
        /* hover removed */
        .profile-page .card:focus,
        .profile-page .card:active,
        .profile-page .card:focus-within {
            transform: none !important;
            box-shadow: var(--shadow-sm) !important;
            background: #fff !important;
            border: none !important;
            outline: none !important;
        }

        /* Remove hover from Account Settings buttons specifically */
        /* hover removed */
        .profile-page .card .btn:focus,
        .profile-page .card .btn:active {
            background-color: inherit !important;
            border-color: inherit !important;
            color: inherit !important;
            box-shadow: none !important;
            transform: none !important;
        }

        /* Consistent font styling for all start buttons (matching Cardio Endurance style) */
        .profile-page .btn {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
            letter-spacing: 0.025em !important;
        }
        .profile-page .btn i {
            font-weight: 600 !important;
        }

        /* Strong explicit overrides for Bootstrap hover states (always on) */
        /* hover removed */
        /* hover removed */ {
            color: inherit !important;
        }
        /* hover removed */
        .profile-page .dropdown-item:focus,
        .profile-page .dropdown-item:active {
            background-color: transparent !important;
            color: inherit !important;
        }
        /* hover removed */
        .profile-page .btn:focus,
        .profile-page .btn:active {
            box-shadow: none !important;
            transform: none !important;
            filter: none !important;
        }
        /* hover removed */
        .profile-page .btn-primary:focus,
        .profile-page .btn-primary:active {
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
            color: #fff !important;
        }
        /* hover removed */
        .profile-page .btn-success:focus,
        .profile-page .btn-success:active {
            background-color: #198754 !important;
            border-color: #198754 !important;
            color: #fff !important;
        }
        /* hover removed */
        .profile-page .btn-outline-primary:focus,
        .profile-page .btn-outline-primary:active {
            color: #0d6efd !important;
            background-color: transparent !important;
            border-color: #0d6efd !important;
        }
        /* hover removed */
        .profile-page .btn-outline-secondary:focus,
        .profile-page .btn-outline-secondary:active {
            color: #6c757d !important;
            background-color: transparent !important;
            border-color: #6c757d !important;
        }
        /* hover removed */
        .profile-page .btn-outline-danger:focus,
        .profile-page .btn-outline-danger:active {
            color: #dc3545 !important;
            background-color: transparent !important;
            border-color: #dc3545 !important;
        }

        /* Force static hover effects across the entire profile page */
        /* hover removed */
    </style>
    
    <script>
        // Profile form submission
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            updateProfile();
        });
        
        function updateProfile() {
            const form = document.getElementById('profileForm');
            const formData = new FormData(form);
            formData.append('mode', 'profile');

            const submitBtn = document.querySelector('#profileForm button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
            submitBtn.disabled = true;

            fetch('../php/profile_api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    showNotification('success', 'Profile updated successfully!', 'Your changes have been saved.');
                } else {
                    showNotification('danger', 'Update failed', res.message || 'Please try again.');
                }
            })
            .catch(() => {
                showNotification('danger', 'Network error', 'Please check your connection.');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }
        
        function updateFitnessGoals() {
            const fitnessGoal = document.getElementById('fitnessGoal').value;
            const targetWeight = document.getElementById('targetWeight').value;
            const workoutsPerWeek = document.getElementById('workoutsPerWeek').value;
            const experienceLevel = document.getElementById('experienceLevel').value;
            
            // Show loading state
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('mode', 'goals');
            formData.append('fitnessGoal', fitnessGoal);
            formData.append('targetWeight', targetWeight);
            formData.append('workoutsPerWeek', workoutsPerWeek);
            formData.append('experienceLevel', experienceLevel);

            fetch('../php/profile_api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    showNotification('success', 'Fitness goals updated!', 'Your goals have been saved.');
                } else {
                    showNotification('danger', 'Update failed', res.message || 'Please try again.');
                }
            })
            .catch(() => {
                showNotification('danger', 'Network error', 'Please check your connection.');
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }
        
        // Avatar upload removed per request
        
        function changePassword() {
            showNotification('info', 'Password change feature', 'This feature will be available soon!');
        }
        
        function exportData() {
            showNotification('info', 'Data export feature', 'This feature will be available soon!');
        }
        
        function deleteAccount() {
            if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
                showNotification('warning', 'Account deletion', 'Please contact support to delete your account.');
            }
        }
    </script>
</body>
</html>
