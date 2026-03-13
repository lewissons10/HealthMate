<?php
require_once 'config.php';

class PointsSystem {
    private $pdo;
    private $userId;
    
    public function __construct($userId) {
        $this->pdo = getDBConnection();
        $this->userId = $userId;
    }
    
    // Calculate points for a workout
    public function calculateWorkoutPoints($workoutName, $durationMinutes, $caloriesBurned) {
        $points = 0;
        
        // Base points for completing a workout
        $points += 10;
        
        // Duration bonus (1 point per 5 minutes)
        $points += floor($durationMinutes / 5);
        
        // Calories bonus (1 point per 50 calories)
        $points += floor($caloriesBurned / 50);
        
        // Workout type bonus
        $workoutType = strtolower($workoutName);
        if (strpos($workoutType, 'hiit') !== false || strpos($workoutType, 'cardio') !== false) {
            $points += 5; // High intensity bonus
        } elseif (strpos($workoutType, 'strength') !== false || strpos($workoutType, 'weight') !== false) {
            $points += 3; // Strength training bonus
        } elseif (strpos($workoutType, 'yoga') !== false || strpos($workoutType, 'stretch') !== false) {
            $points += 2; // Flexibility bonus
        }
        
        // Streak bonus
        $streak = $this->getCurrentStreak();
        if ($streak >= 7) {
            $points += 10; // Weekly streak bonus
        } elseif ($streak >= 3) {
            $points += 5; // 3-day streak bonus
        }
        
        return $points;
    }
    
    // Get current workout streak
    public function getCurrentStreak() {
        $stmt = $this->pdo->prepare("
            SELECT DATE(created_at) as workout_date 
            FROM workout_logs 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$this->userId]);
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
        
        return $currentStreak;
    }
    
    // Award points for a workout
    public function awardWorkoutPoints($workoutName, $durationMinutes, $caloriesBurned) {
        $points = $this->calculateWorkoutPoints($workoutName, $durationMinutes, $caloriesBurned);
        
        // Update user points
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET points = points + ? 
            WHERE id = ?
        ");
        $stmt->execute([$points, $this->userId]);
        
        // Log the points earned
        $this->logPointsEarned('workout', $points, "Workout: $workoutName");
        
        return $points;
    }
    
    // Award achievement points
    public function awardAchievementPoints($achievementName, $points) {
        // Update user points
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET points = points + ? 
            WHERE id = ?
        ");
        $stmt->execute([$points, $this->userId]);
        
        // Log the points earned
        $this->logPointsEarned('achievement', $points, "Achievement: $achievementName");
        
        return $points;
    }
    
    // Log points earned
    private function logPointsEarned($type, $points, $description) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO points_log (user_id, type, points, description, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$this->userId, $type, $points, $description]);
        } catch (Exception $e) {
            // Ignore if table doesn't exist
        }
    }
    
    // Get user's total points
    public function getTotalPoints() {
        $stmt = $this->pdo->prepare("SELECT points FROM users WHERE id = ?");
        $stmt->execute([$this->userId]);
        return (int)$stmt->fetchColumn();
    }
    
    // Get points history
    public function getPointsHistory($limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT type, points, description, created_at 
                FROM points_log 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$this->userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Check and award streak achievements
    public function checkStreakAchievements() {
        $streak = $this->getCurrentStreak();
        $achievements = [];
        
        if ($streak >= 3 && $streak < 7) {
            $achievements[] = ['name' => '3-Day Streak', 'points' => 25];
        } elseif ($streak >= 7 && $streak < 14) {
            $achievements[] = ['name' => 'Weekly Warrior', 'points' => 50];
        } elseif ($streak >= 14 && $streak < 30) {
            $achievements[] = ['name' => 'Two Week Champion', 'points' => 100];
        } elseif ($streak >= 30) {
            $achievements[] = ['name' => 'Monthly Machine', 'points' => 200];
        }
        
        return $achievements;
    }
    
    // Get weekly progress
    public function getWeeklyProgress() {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as workouts,
                SUM(calories_burned) as calories,
                SUM(duration_minutes) as duration
            FROM workout_logs 
            WHERE user_id = ? 
            AND created_at >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
        ");
        $stmt->execute([$this->userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get monthly progress
    public function getMonthlyProgress() {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as workouts,
                SUM(calories_burned) as calories,
                SUM(duration_minutes) as duration
            FROM workout_logs 
            WHERE user_id = ? 
            AND created_at >= DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) - 1 DAY)
        ");
        $stmt->execute([$this->userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Create points log table if it doesn't exist
function createPointsLogTable() {
    try {
        $pdo = getDBConnection();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS points_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                type ENUM('workout', 'achievement', 'bonus', 'penalty') NOT NULL,
                points INT NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB
        ");
    } catch (Exception $e) {
        // Ignore if table already exists
    }
}

// Initialize points log table
createPointsLogTable();
?>
