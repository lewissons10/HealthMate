<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - HealthMate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(10px);
        }
        
        .admin-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            color: white;
            font-size: 2rem;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-admin {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="admin-icon">
            <i class="fas fa-shield-alt"></i>
        </div>
        
        <h2 class="text-center mb-4 text-dark">Admin Login</h2>
        <p class="text-center text-muted mb-4">Restricted Access - Anson Only</p>
        
        <?php
        // Start session
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Include admin config
        require_once 'php/admin_config.php';
        
        $error_message = '';
        $debug_info = '';
        
        // Debug mode (remove in production)
        if (isset($_GET['debug'])) {
            $debug_info = "Debug: Functions loaded - " . 
                         (function_exists('loginAdmin') ? 'YES' : 'NO') . " | " .
                         "Constants - " . (defined('ADMIN_USERNAME') ? ADMIN_USERNAME : 'NO') . " | " .
                         "Session status: " . (session_status() == PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE');
        }
        
        // Handle login
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            
            if (empty($username) || empty($password)) {
                $error_message = 'Please enter both username and password.';
            } else {
                if (loginAdmin($username, $password)) {
                    header('Location: admin_dashboard.php');
                    exit();
                } else {
                    $error_message = 'Invalid username or password. Use: anson / ansonlewis';
                }
            }
        }
        
        // Redirect if already logged in
        if (isAdminLoggedIn()) {
            header('Location: admin_dashboard.php');
            exit();
        }
        ?>
        
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($debug_info)): ?>
        <div class="alert alert-info" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <?php echo htmlspecialchars($debug_info); ?>
        </div>
        <?php endif; ?>
        
        <div class="alert alert-warning" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Restricted Access:</strong> This admin panel is restricted to Anson only.<br>
            <small><strong>Username:</strong> anson | <strong>Password:</strong> ansonlewis</small>
        </div>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">
                    <i class="fas fa-user me-2"></i>Username
                </label>
                <input type="text" class="form-control" id="username" name="username" 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                       placeholder="Enter username" required>
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label">
                    <i class="fas fa-lock me-2"></i>Password
                </label>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Enter password" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-admin w-100">
                <i class="fas fa-sign-in-alt me-2"></i>Login to Admin Panel
            </button>
        </form>
        
        <div class="text-center mt-4">
            <a href="index.html" class="text-muted text-decoration-none">
                <i class="fas fa-arrow-left me-2"></i>Back to Main Site
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
