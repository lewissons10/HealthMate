<?php
require_once 'config.php';

// Sample users data to populate the database
$sampleUsers = [
    [
        'username' => 'john_doe',
        'email' => 'john.doe@example.com',
        'password' => 'password123',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'age' => 28,
        'weight' => 75.5,
        'height' => 175.0,
        'phone' => '+1234567890',
        'date_of_birth' => '1995-03-15',
        'gender' => 'male',
        'bio' => 'Fitness enthusiast looking to build muscle and improve overall health.',
        'target_weight' => 80.0,
        'workouts_per_week' => 4,
        'experience_level' => 'intermediate',
        'level' => 3,
        'fitness_goal' => 'muscle_gain',
        'points' => 1250
    ],
    [
        'username' => 'jane_smith',
        'email' => 'jane.smith@example.com',
        'password' => 'password123',
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'age' => 25,
        'weight' => 60.0,
        'height' => 165.0,
        'phone' => '+1234567891',
        'date_of_birth' => '1998-07-22',
        'gender' => 'female',
        'bio' => 'Yoga instructor and wellness coach focused on holistic health.',
        'target_weight' => 58.0,
        'workouts_per_week' => 5,
        'experience_level' => 'advanced',
        'level' => 5,
        'fitness_goal' => 'weight_loss',
        'points' => 2100
    ],
    [
        'username' => 'mike_wilson',
        'email' => 'mike.wilson@example.com',
        'password' => 'password123',
        'first_name' => 'Mike',
        'last_name' => 'Wilson',
        'age' => 32,
        'weight' => 85.0,
        'height' => 180.0,
        'phone' => '+1234567892',
        'date_of_birth' => '1991-11-08',
        'gender' => 'male',
        'bio' => 'Marathon runner and endurance athlete.',
        'target_weight' => 82.0,
        'workouts_per_week' => 6,
        'experience_level' => 'advanced',
        'level' => 7,
        'fitness_goal' => 'endurance',
        'points' => 3200
    ],
    [
        'username' => 'sarah_johnson',
        'email' => 'sarah.johnson@example.com',
        'password' => 'password123',
        'first_name' => 'Sarah',
        'last_name' => 'Johnson',
        'age' => 29,
        'weight' => 65.0,
        'height' => 170.0,
        'phone' => '+1234567893',
        'date_of_birth' => '1994-05-12',
        'gender' => 'female',
        'bio' => 'Personal trainer helping others achieve their fitness goals.',
        'target_weight' => 63.0,
        'workouts_per_week' => 5,
        'experience_level' => 'advanced',
        'level' => 6,
        'fitness_goal' => 'general_fitness',
        'points' => 2800
    ],
    [
        'username' => 'alex_brown',
        'email' => 'alex.brown@example.com',
        'password' => 'password123',
        'first_name' => 'Alex',
        'last_name' => 'Brown',
        'age' => 24,
        'weight' => 70.0,
        'height' => 172.0,
        'phone' => '+1234567894',
        'date_of_birth' => '1999-09-30',
        'gender' => 'other',
        'bio' => 'New to fitness, looking to build healthy habits.',
        'target_weight' => 68.0,
        'workouts_per_week' => 3,
        'experience_level' => 'beginner',
        'level' => 1,
        'fitness_goal' => 'general_fitness',
        'points' => 450
    ],
    [
        'username' => 'emma_davis',
        'email' => 'emma.davis@example.com',
        'password' => 'password123',
        'first_name' => 'Emma',
        'last_name' => 'Davis',
        'age' => 27,
        'weight' => 58.0,
        'height' => 162.0,
        'phone' => '+1234567895',
        'date_of_birth' => '1996-12-03',
        'gender' => 'female',
        'bio' => 'Dance instructor and fitness enthusiast.',
        'target_weight' => 56.0,
        'workouts_per_week' => 4,
        'experience_level' => 'intermediate',
        'level' => 4,
        'fitness_goal' => 'weight_loss',
        'points' => 1800
    ],
    [
        'username' => 'david_miller',
        'email' => 'david.miller@example.com',
        'password' => 'password123',
        'first_name' => 'David',
        'last_name' => 'Miller',
        'age' => 35,
        'weight' => 90.0,
        'height' => 185.0,
        'phone' => '+1234567896',
        'date_of_birth' => '1988-04-18',
        'gender' => 'male',
        'bio' => 'Bodybuilder and strength training coach.',
        'target_weight' => 95.0,
        'workouts_per_week' => 5,
        'experience_level' => 'advanced',
        'level' => 8,
        'fitness_goal' => 'muscle_gain',
        'points' => 4500
    ],
    [
        'username' => 'lisa_garcia',
        'email' => 'lisa.garcia@example.com',
        'password' => 'password123',
        'first_name' => 'Lisa',
        'last_name' => 'Garcia',
        'age' => 31,
        'weight' => 62.0,
        'height' => 168.0,
        'phone' => '+1234567897',
        'date_of_birth' => '1992-08-25',
        'gender' => 'female',
        'bio' => 'Nutritionist and wellness coach.',
        'target_weight' => 60.0,
        'workouts_per_week' => 4,
        'experience_level' => 'intermediate',
        'level' => 3,
        'fitness_goal' => 'general_fitness',
        'points' => 1600
    ]
];

try {
    $pdo = getDBConnection();
    
    // Check if users table exists and has data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $userCount = $result['count'];
    
    echo "Current users in database: $userCount\n";
    
    if ($userCount == 0) {
        echo "No users found. Adding sample users...\n";
        
        foreach ($sampleUsers as $userData) {
            // Check if user already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$userData['username'], $userData['email']]);
            
            if ($stmt->rowCount() == 0) {
                // Hash password
                $password_hash = password_hash($userData['password'], PASSWORD_DEFAULT);
                
                // Insert user
                $stmt = $pdo->prepare("
                    INSERT INTO users (
                        username, email, password_hash, first_name, last_name, 
                        age, weight, height, phone, date_of_birth, gender, bio,
                        target_weight, workouts_per_week, experience_level, 
                        level, fitness_goal, points
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $userData['username'],
                    $userData['email'],
                    $password_hash,
                    $userData['first_name'],
                    $userData['last_name'],
                    $userData['age'],
                    $userData['weight'],
                    $userData['height'],
                    $userData['phone'],
                    $userData['date_of_birth'],
                    $userData['gender'],
                    $userData['bio'],
                    $userData['target_weight'],
                    $userData['workouts_per_week'],
                    $userData['experience_level'],
                    $userData['level'],
                    $userData['fitness_goal'],
                    $userData['points']
                ]);
                
                echo "Added user: {$userData['username']} ({$userData['first_name']} {$userData['last_name']})\n";
            } else {
                echo "User {$userData['username']} already exists, skipping...\n";
            }
        }
        
        echo "Sample users added successfully!\n";
    } else {
        echo "Users already exist in database. Skipping population.\n";
    }
    
    // Add some sample meal data for users
    $stmt = $pdo->query("SELECT id FROM users LIMIT 3");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "Adding sample meal data...\n";
        
        $sampleMeals = [
            ['food_name' => 'Oatmeal with Berries', 'calories' => 350, 'protein' => 12, 'carbs' => 65, 'fats' => 8, 'meal_type' => 'breakfast'],
            ['food_name' => 'Grilled Chicken Salad', 'calories' => 450, 'protein' => 35, 'carbs' => 20, 'fats' => 25, 'meal_type' => 'lunch'],
            ['food_name' => 'Salmon with Quinoa', 'calories' => 520, 'protein' => 40, 'carbs' => 45, 'fats' => 20, 'meal_type' => 'dinner'],
            ['food_name' => 'Greek Yogurt with Nuts', 'calories' => 280, 'protein' => 20, 'carbs' => 15, 'fats' => 18, 'meal_type' => 'snacks']
        ];
        
        foreach ($users as $user) {
            $userId = $user['id'];
            
            // Check if user already has meal data
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM daily_meal_history WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] == 0) {
                // Add meals for the last 7 days
                for ($i = 0; $i < 7; $i++) {
                    $date = date('Y-m-d', strtotime("-$i days"));
                    
                    foreach ($sampleMeals as $meal) {
                        $stmt = $pdo->prepare("
                            INSERT INTO daily_meal_history (
                                user_id, date, meal_type, food_name, calories, 
                                protein, carbs, fats, amount, unit
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $stmt->execute([
                            $userId,
                            $date,
                            $meal['meal_type'],
                            $meal['food_name'],
                            $meal['calories'],
                            $meal['protein'],
                            $meal['carbs'],
                            $meal['fats'],
                            1.0, // amount
                            'serving' // unit
                        ]);
                    }
                }
                
                echo "Added meal data for user ID: $userId\n";
            }
        }
    }
    
    echo "Database population completed!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
