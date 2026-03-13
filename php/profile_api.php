<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo = getDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $input = $_POST;
    $userId = $_SESSION['user_id'];

    $mode = isset($input['mode']) ? $input['mode'] : 'profile';

    if ($mode === 'profile') {
        $sql = "UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email, phone = :phone, date_of_birth = :date_of_birth, gender = :gender, height = :height, weight = :weight, bio = :bio WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':first_name' => $input['firstName'] ?? null,
            ':last_name' => $input['lastName'] ?? null,
            ':email' => $input['email'] ?? null,
            ':phone' => $input['phone'] ?? null,
            ':date_of_birth' => !empty($input['dateOfBirth']) ? $input['dateOfBirth'] : null,
            ':gender' => !empty($input['gender']) ? $input['gender'] : null,
            ':height' => isset($input['height']) && $input['height'] !== '' ? $input['height'] : null,
            ':weight' => isset($input['weight']) && $input['weight'] !== '' ? $input['weight'] : null,
            ':bio' => $input['bio'] ?? null,
            ':id' => $userId,
        ]);
        echo json_encode(['success' => true, 'message' => 'Profile updated']);
        exit;
    }

    if ($mode === 'goals') {
        $sql = "UPDATE users SET fitness_goal = :fitness_goal, target_weight = :target_weight, workouts_per_week = :workouts_per_week, experience_level = :experience_level WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':fitness_goal' => $input['fitnessGoal'] ?? null,
            ':target_weight' => isset($input['targetWeight']) && $input['targetWeight'] !== '' ? $input['targetWeight'] : null,
            ':workouts_per_week' => isset($input['workoutsPerWeek']) && $input['workoutsPerWeek'] !== '' ? $input['workoutsPerWeek'] : null,
            ':experience_level' => $input['experienceLevel'] ?? null,
            ':id' => $userId,
        ]);
        echo json_encode(['success' => true, 'message' => 'Goals updated']);
        exit;
    }

    if ($mode === 'avatar') {
        if (!isset($_FILES['avatar']) || !is_uploaded_file($_FILES['avatar']['tmp_name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No file uploaded']);
            exit;
        }

        $file = $_FILES['avatar'];
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($file['tmp_name']);
        if (!isset($allowed[$mime])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unsupported file type']);
            exit;
        }
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'File too large (max 5MB)']);
            exit;
        }

        $ext = $allowed[$mime];
        $root = realpath(__DIR__ . '/..');
        $targetDir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }
        $filename = 'user_' . $userId . '.' . $ext;
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

        // Remove existing files for this user with other extensions
        foreach (['jpg','jpeg','png','webp'] as $oldExt) {
            $old = $targetDir . DIRECTORY_SEPARATOR . 'user_' . $userId . '.' . $oldExt;
            if (is_file($old) && $old !== $targetPath) { @unlink($old); }
        }

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save file']);
            exit;
        }

        // Build public URL relative to project root
        $publicPath = 'uploads/avatars/' . $filename;

        // Try to store path in DB if column exists
        try {
            $pdo->query("SELECT avatar_path FROM users LIMIT 1");
            $stmt = $pdo->prepare("UPDATE users SET avatar_path = :p WHERE id = :id");
            $stmt->execute([':p' => $publicPath, ':id' => $userId]);
        } catch (Throwable $e) {
            // Column may not exist; ignore silently
        }

        echo json_encode(['success' => true, 'message' => 'Avatar updated', 'path' => $publicPath]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid mode']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
}
<?php


