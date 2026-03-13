-- HealthMate Database Schema
-- Create database
CREATE DATABASE IF NOT EXISTS healthmate_db;
USE healthmate_db;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    age INT,
    weight DECIMAL(5,2),
    height DECIMAL(5,2),
    phone VARCHAR(20) NULL,
    date_of_birth DATE NULL,
    gender ENUM('male','female','other') NULL,
    bio TEXT NULL,
    target_weight DECIMAL(5,2) NULL,
    workouts_per_week TINYINT NULL,
    experience_level ENUM('beginner','intermediate','advanced') NULL,
    level INT DEFAULT 1,
    fitness_goal ENUM('weight_loss', 'muscle_gain', 'endurance', 'general_fitness') DEFAULT 'general_fitness',
    points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Workouts table
CREATE TABLE workouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category ENUM('strength', 'cardio', 'flexibility', 'balance') NOT NULL,
    difficulty ENUM('beginner', 'intermediate', 'advanced') NOT NULL,
    duration_minutes INT,
    calories_burn INT,
    equipment_needed TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Workout exercises table
CREATE TABLE workout_exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workout_id INT,
    exercise_name VARCHAR(100) NOT NULL,
    description TEXT,
    sets INT DEFAULT 3,
    reps INT DEFAULT 10,
    duration_seconds INT,
    rest_seconds INT DEFAULT 60,
    FOREIGN KEY (workout_id) REFERENCES workouts(id) ON DELETE CASCADE
);

-- User progress tracking
CREATE TABLE user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    date DATE NOT NULL,
    weight DECIMAL(5,2),
    calories_consumed INT,
    calories_burned INT,
    workout_duration_minutes INT,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Feedback table
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Achievements table
CREATE TABLE achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    points_reward INT DEFAULT 0,
    icon VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User achievements table
CREATE TABLE user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    achievement_id INT,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
);

-- Insert sample data
INSERT INTO workouts (name, description, category, difficulty, duration_minutes, calories_burn, equipment_needed) VALUES
('Full Body Strength', 'Complete body workout focusing on major muscle groups', 'strength', 'intermediate', 45, 300, 'Dumbbells, Resistance Bands'),
('HIIT Cardio', 'High-intensity interval training for maximum calorie burn', 'cardio', 'advanced', 30, 400, 'None'),
('Yoga Flow', 'Gentle yoga sequence for flexibility and relaxation', 'flexibility', 'beginner', 20, 150, 'Yoga Mat'),
('Core Crusher', 'Intensive core workout for strong abs', 'strength', 'intermediate', 25, 200, 'None'),
('Running Program', 'Progressive running program for endurance', 'cardio', 'beginner', 40, 350, 'Running Shoes');

INSERT INTO workout_exercises (workout_id, exercise_name, description, sets, reps, duration_seconds, rest_seconds) VALUES
(1, 'Push-ups', 'Standard push-ups for chest and triceps', 3, 15, NULL, 60),
(1, 'Squats', 'Bodyweight squats for legs and glutes', 3, 20, NULL, 60),
(1, 'Plank', 'Core stability exercise', 3, NULL, 60, 60),
(2, 'Burpees', 'Full body explosive movement', 4, 10, NULL, 30),
(2, 'Mountain Climbers', 'Dynamic cardio exercise', 4, NULL, 45, 30),
(3, 'Sun Salutation', 'Classic yoga sequence', 3, NULL, 300, 30),
(4, 'Crunches', 'Traditional abdominal exercise', 3, 20, NULL, 45),
(4, 'Russian Twists', 'Rotational core exercise', 3, 15, NULL, 45);

INSERT INTO achievements (name, description, points_reward, icon) VALUES
('First Workout', 'Complete your first workout', 50, '🏃‍♂️'),
('Week Warrior', 'Complete 7 workouts in a week', 200, '💪'),
('Weight Loss Champion', 'Lose 5 pounds', 300, '⚖️'),
('Strength Builder', 'Complete 20 strength workouts', 400, '🏋️‍♂️'),
('Consistency King', 'Workout for 30 consecutive days', 500, '👑');

-- Create a sample user for testing (password: test123)
INSERT INTO users (username, email, password_hash, first_name, last_name, age, weight, height, fitness_goal, points) VALUES
('demo_user', 'demo@healthmate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo', 'User', 25, 70.5, 175.0, 'weight_loss', 150);
