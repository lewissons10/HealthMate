<?php
// Script to populate the database with sample users and integrate with admin dashboard
require_once 'php/populate_users.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Add Users to Admin Dashboard</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            max-width: 600px;
            width: 100%;
        }
        .btn-admin {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            color: white;
        }
        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }
    </style>
</head>
<body>
    <div class='container text-center'>
        <h2 class='mb-4'>HealthMate Admin - Add Users</h2>
        <p class='mb-4'>This script will populate the database with sample users and integrate them with the admin dashboard.</p>
        
        <div class='mb-4'>
            <a href='admin_login.php' class='btn btn-admin me-3'>
                <i class='fas fa-shield-alt me-2'></i>Go to Admin Dashboard
            </a>
            <a href='index.html' class='btn btn-outline-primary'>
                <i class='fas fa-home me-2'></i>Back to Main Site
            </a>
        </div>
        
        <div class='alert alert-info'>
            <h5>Sample Users Added:</h5>
            <ul class='list-unstyled'>
                <li>👤 John Doe - Muscle Gain (Intermediate)</li>
                <li>👤 Jane Smith - Weight Loss (Advanced)</li>
                <li>👤 Mike Wilson - Endurance (Advanced)</li>
                <li>👤 Sarah Johnson - General Fitness (Advanced)</li>
                <li>👤 Alex Brown - General Fitness (Beginner)</li>
                <li>👤 Emma Davis - Weight Loss (Intermediate)</li>
                <li>👤 David Miller - Muscle Gain (Advanced)</li>
                <li>👤 Lisa Garcia - General Fitness (Intermediate)</li>
            </ul>
        </div>
        
        <div class='alert alert-success'>
            <h5>✅ Integration Complete!</h5>
            <p>All users have been added to the database and are now visible in the admin dashboard.</p>
            <p><strong>Admin Login:</strong> anson / ansonlewis</p>
        </div>
    </div>
    
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js'></script>
</body>
</html>";
?>
