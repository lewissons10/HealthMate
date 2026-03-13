<?php
require_once 'config.php';
require_once 'phpmailer_mail.php';

// Handle registration
if ($_POST['action'] == 'register') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $age = (int)$_POST['age'];
    $weight = (float)$_POST['weight'];
    $height = (float)$_POST['height'];
    $fitness_goal = sanitizeInput($_POST['fitness_goal']);
    
    try {
        $pdo = getDBConnection();
        
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
            exit();
        }
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, age, weight, height, fitness_goal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password_hash, $first_name, $last_name, $age, $weight, $height, $fitness_goal]);
        
        // Send welcome email to new user
        $welcome_sent = sendWelcomeEmail($email, $first_name);
        
        // Send notification to admin about new user registration
        $admin_subject = "New User Registration: " . $username;
        $admin_message = "A new user has registered on HealthMate:\n\n";
        $admin_message .= "Username: " . $username . "\n";
        $admin_message .= "Name: " . $first_name . " " . $last_name . "\n";
        $admin_message .= "Email: " . $email . "\n";
        $admin_message .= "Fitness Goal: " . $fitness_goal . "\n";
        $admin_message .= "Registration Date: " . date('Y-m-d H:i:s') . "\n";
        $admin_message .= "---\nThis is an automated notification from HealthMate Fitness App.";
        
        $admin_notification_sent = sendEmailWithPHPMailer(ADMIN_EMAIL, $admin_subject, $admin_message);
        
        echo json_encode(['success' => true, 'message' => 'Registration successful! Welcome email sent.']);
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
    }
}

// Handle login
if ($_POST['action'] == 'login') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    try {
        $pdo = getDBConnection();
        
        // Get user by username
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['fitness_goal'] = $user['fitness_goal'];
            
            echo json_encode(['success' => true, 'message' => 'Login successful!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
        }
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Login failed: ' . $e->getMessage()]);
    }
}

// Handle logout
if ($_POST['action'] == 'logout') {
    // Fully clear session data and cookie
    session_unset();
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
}
?>
