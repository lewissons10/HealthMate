<?php
require_once 'php/admin_config.php';
requireAnsonAccess(); // Restrict access to Anson only
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HealthMate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --admin-primary: #667eea;
            --admin-secondary: #764ba2;
            --admin-success: #28a745;
            --admin-warning: #ffc107;
            --admin-danger: #dc3545;
            --admin-info: #17a2b8;
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .admin-sidebar {
            background: white;
            min-height: calc(100vh - 80px);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            padding: 0;
        }
        
        .admin-sidebar .nav-link {
            color: #6c757d;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
            color: white;
        }
        
        .admin-content {
            padding: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: none;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .stat-icon.users { background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary)); }
        .stat-icon.meals { background: linear-gradient(135deg, var(--admin-success), #20c997); }
        .stat-icon.calories { background: linear-gradient(135deg, var(--admin-warning), #fd7e14); }
        .stat-icon.activity { background: linear-gradient(135deg, var(--admin-info), #6f42c1); }
        
        .user-table {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
            color: white;
            border: none;
            font-weight: 600;
            padding: 1rem;
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-color: #f8f9fa;
        }
        
        .badge-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-active {
            background: rgba(40, 167, 69, 0.1);
            color: var(--admin-success);
        }
        
        .badge-inactive {
            background: rgba(220, 53, 69, 0.1);
            color: var(--admin-danger);
        }
        
        .btn-admin {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .search-box {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 1rem;
            margin-bottom: 2rem;
        }
        
        .search-input {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0">
                        <i class="fas fa-shield-alt me-2"></i>
                        HealthMate Admin Dashboard - Anson
                    </h4>
                </div>
                <div class="col-md-6 text-end">
                    <span class="me-3">
                        <i class="fas fa-user me-1"></i>
                        Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                    </span>
                    <a href="admin_logout.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Admin Sidebar -->
            <div class="col-md-2 admin-sidebar">
                <nav class="nav flex-column">
                    <a class="nav-link active" href="#dashboard" data-section="dashboard">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a class="nav-link" href="#users" data-section="users">
                        <i class="fas fa-users me-2"></i>User Management
                    </a>
                    <a class="nav-link" href="#settings" data-section="settings">
                        <i class="fas fa-cog me-2"></i>Settings
                    </a>
                </nav>
            </div>

            <!-- Admin Content -->
            <div class="col-md-10 admin-content">
                <!-- Dashboard Section -->
                <div id="dashboard-section" class="admin-section">
                    <div class="alert alert-info mb-4" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Anson's Admin Panel:</strong> You have full access to all user data and system management features.
                    </div>
                    
                    <h2 class="mb-4">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview
                    </h2>
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon users me-3">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0" id="total-users">-</h3>
                                        <p class="text-muted mb-0">Total Users</p>
                                        <small id="new-users-week" class="text-success">+0 this week</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon meals me-3">
                                        <i class="fas fa-utensils"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0" id="total-meals">-</h3>
                                        <p class="text-muted mb-0">Total Meals</p>
                                        <small id="meals-this-week" class="text-info">0 this week</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon calories me-3">
                                        <i class="fas fa-fire"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0" id="total-calories">-</h3>
                                        <p class="text-muted mb-0">Calories Tracked</p>
                                        <small id="avg-calories-per-meal" class="text-warning">0 avg/meal</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon activity me-3">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0" id="active-users">-</h3>
                                        <p class="text-muted mb-0">Active Users</p>
                                        <small id="avg-user-age" class="text-primary">Avg age: 0</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="user-table">
                                <div class="p-3 border-bottom">
                                    <h5 class="mb-0">
                                        <i class="fas fa-clock me-2"></i>Recent User Activity
                                    </h5>
                                </div>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Activity</th>
                                                <th>Time</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="recent-activity">
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">Loading...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Management Section -->
                <div id="users-section" class="admin-section" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="mb-0">
                            <i class="fas fa-users me-2"></i>User Management
                        </h2>
                        <div class="btn-group">
                            <button class="btn btn-outline-primary" onclick="exportAllUsers()">
                                <i class="fas fa-download me-2"></i>Export All
                            </button>
                            <button class="btn btn-outline-info" onclick="addNewUser()">
                                <i class="fas fa-user-plus me-2"></i>Add User
                            </button>
                        </div>
                    </div>
                    
                    <!-- User Statistics Summary -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon users me-3">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0" id="total-users-count">0</h4>
                                        <p class="text-muted mb-0">Total Users</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon activity me-3">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0" id="active-users-count">0</h4>
                                        <p class="text-muted mb-0">Active Users</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon meals me-3">
                                        <i class="fas fa-utensils"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0" id="total-meals-count">0</h4>
                                        <p class="text-muted mb-0">Total Meals</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon calories me-3">
                                        <i class="fas fa-fire"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0" id="total-calories-count">0</h4>
                                        <p class="text-muted mb-0">Total Calories</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Search and Filters -->
                    <div class="search-box mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <input type="text" class="form-control search-input" id="user-search" placeholder="Search by name, email, or username...">
                            </div>
                            <div class="col-md-2">
                                <select class="form-control search-input" id="status-filter">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="recent">Recent</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control search-input" id="gender-filter">
                                    <option value="">All Genders</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control search-input" id="goal-filter">
                                    <option value="">All Goals</option>
                                    <option value="weight_loss">Weight Loss</option>
                                    <option value="muscle_gain">Muscle Gain</option>
                                    <option value="endurance">Endurance</option>
                                    <option value="general_fitness">General Fitness</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control search-input" id="sort-by">
                                    <option value="created_at">Sort by Join Date</option>
                                    <option value="last_login">Sort by Last Login</option>
                                    <option value="total_meals">Sort by Meals</option>
                                    <option value="total_calories">Sort by Calories</option>
                                    <option value="points">Sort by Points</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button class="btn btn-admin w-100" onclick="refreshUsers()">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Users Table -->
                    <div class="user-table">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="text-muted">Showing <span id="showing-count">0</span> of <span id="total-count">0</span> users</span>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-secondary" onclick="changePageSize(10)">10</button>
                                <button class="btn btn-outline-secondary" onclick="changePageSize(25)">25</button>
                                <button class="btn btn-outline-secondary" onclick="changePageSize(50)">50</button>
                                <button class="btn btn-outline-secondary" onclick="changePageSize(100)">100</button>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="select-all" onchange="toggleSelectAll()">
                                        </th>
                                        <th>ID</th>
                                        <th>User Info</th>
                                        <th>Personal Details</th>
                                        <th>Fitness Data</th>
                                        <th>Activity</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="users-table">
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Loading users...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <nav aria-label="User pagination" class="mt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <button class="btn btn-outline-primary btn-sm" onclick="previousPage()" id="prev-btn" disabled>
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </button>
                                    <span class="mx-3">
                                        Page <span id="current-page">1</span> of <span id="total-pages">1</span>
                                    </span>
                                    <button class="btn btn-outline-primary btn-sm" onclick="nextPage()" id="next-btn" disabled>
                                        Next <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                                <div>
                                    <button class="btn btn-outline-danger btn-sm" onclick="bulkDeleteUsers()" id="bulk-delete-btn" disabled>
                                        <i class="fas fa-trash"></i> Delete Selected
                                    </button>
                                </div>
                            </div>
                        </nav>
                    </div>
                </div>


                <!-- Settings Section -->
                <div id="settings-section" class="admin-section" style="display: none;">
                    <h2 class="mb-4">
                        <i class="fas fa-cog me-2"></i>Settings
                    </h2>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="stat-card">
                                <h5 class="mb-3">System Information</h5>
                                <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
                                <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                                <p><strong>Database:</strong> MySQL</p>
                                <p><strong>Last Backup:</strong> Never</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-card">
                                <h5 class="mb-3">Admin Actions</h5>
                                <button class="btn btn-admin mb-2 w-100" onclick="exportUserData()">
                                    <i class="fas fa-download me-2"></i>Export User Data
                                </button>
                                <button class="btn btn-outline-danger mb-2 w-100" onclick="clearOldData()">
                                    <i class="fas fa-trash me-2"></i>Clear Old Data
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Admin Dashboard JavaScript
        let currentSection = 'dashboard';
        
        // Navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const section = this.getAttribute('data-section');
                showSection(section);
                
                // Update active state
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        function showSection(section) {
            // Hide all sections
            document.querySelectorAll('.admin-section').forEach(s => s.style.display = 'none');
            
            // Show selected section
            document.getElementById(section + '-section').style.display = 'block';
            currentSection = section;
            
                // Load section data
                switch(section) {
                    case 'dashboard':
                        loadDashboardData();
                        break;
                    case 'users':
                        loadUsersData();
                        break;
                }
        }
        
        // Load dashboard data
        function loadDashboardData() {
            // Load basic stats
            fetch('php/admin_api.php?action=get_stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('total-users').textContent = data.stats.total_users || 0;
                        document.getElementById('total-meals').textContent = data.stats.total_meals || 0;
                        document.getElementById('total-calories').textContent = data.stats.total_calories || 0;
                        document.getElementById('active-users').textContent = data.stats.active_users || 0;
                    }
                })
                .catch(error => console.error('Error loading dashboard data:', error));
            
            // Load detailed stats
            fetch('php/admin_api.php?action=get_detailed_stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const stats = data.stats;
                        
                        // Update additional stats
                        if (stats.users) {
                            document.getElementById('new-users-week').textContent = `+${stats.users.new_users_week || 0} this week`;
                            document.getElementById('avg-user-age').textContent = `Avg age: ${Math.round(stats.users.avg_age || 0)}`;
                        }
                        
                        if (stats.meals) {
                            document.getElementById('meals-this-week').textContent = `${stats.meals.meals_week || 0} this week`;
                            document.getElementById('avg-calories-per-meal').textContent = `${Math.round(stats.meals.avg_calories_per_meal || 0)} avg/meal`;
                        }
                    }
                })
                .catch(error => console.error('Error loading detailed stats:', error));
            
            // Load recent activity
            loadRecentActivity();
        }
        
        // Load recent activity
        function loadRecentActivity() {
            fetch('php/admin_api.php?action=get_recent_activity')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('recent-activity');
                        tbody.innerHTML = '';
                        
                        if (data.activities.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No recent activity</td></tr>';
                            return;
                        }
                        
                        data.activities.forEach(activity => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${activity.username || 'Unknown'}</td>
                                <td>${activity.activity}</td>
                                <td>${activity.time}</td>
                                <td><span class="badge-status badge-${activity.status}">${activity.status}</span></td>
                            `;
                            tbody.appendChild(row);
                        });
                    }
                })
                .catch(error => console.error('Error loading recent activity:', error));
        }
        
        // User management variables
        let allUsers = [];
        let filteredUsers = [];
        let currentPage = 1;
        let pageSize = 25;
        let totalPages = 1;
        
        // Load users data
        function loadUsersData() {
            fetch('php/admin_api.php?action=get_users')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allUsers = data.users;
                        applyFilters();
                        updateUserStatistics();
                    }
                })
                .catch(error => console.error('Error loading users:', error));
        }
        
        // Apply filters and pagination
        function applyFilters() {
            const searchTerm = document.getElementById('user-search').value.toLowerCase();
            const statusFilter = document.getElementById('status-filter').value;
            const genderFilter = document.getElementById('gender-filter').value;
            const goalFilter = document.getElementById('goal-filter').value;
            const sortBy = document.getElementById('sort-by').value;
            
            // Filter users
            filteredUsers = allUsers.filter(user => {
                const matchesSearch = !searchTerm || 
                    user.username?.toLowerCase().includes(searchTerm) ||
                    user.email?.toLowerCase().includes(searchTerm) ||
                    user.full_name?.toLowerCase().includes(searchTerm);
                
                const matchesStatus = !statusFilter || user.status === statusFilter;
                const matchesGender = !genderFilter || user.gender === genderFilter;
                const matchesGoal = !goalFilter || user.fitness_goal === goalFilter;
                
                return matchesSearch && matchesStatus && matchesGender && matchesGoal;
            });
            
            // Sort users
            filteredUsers.sort((a, b) => {
                if (sortBy === 'created_at') return new Date(b.created_at) - new Date(a.created_at);
                if (sortBy === 'last_login') return new Date(b.last_login) - new Date(a.last_login);
                if (sortBy === 'total_meals') return (b.total_meals || 0) - (a.total_meals || 0);
                if (sortBy === 'total_calories') return (b.total_calories || 0) - (a.total_calories || 0);
                if (sortBy === 'points') return (b.points || 0) - (a.points || 0);
                return 0;
            });
            
            // Update pagination
            totalPages = Math.ceil(filteredUsers.length / pageSize);
            currentPage = Math.min(currentPage, totalPages) || 1;
            
            displayUsers();
            updatePagination();
        }
        
        // Display users for current page
        function displayUsers() {
            const tbody = document.getElementById('users-table');
            tbody.innerHTML = '';
            
            if (filteredUsers.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No users found</td></tr>';
                return;
            }
            
            const startIndex = (currentPage - 1) * pageSize;
            const endIndex = Math.min(startIndex + pageSize, filteredUsers.length);
            const pageUsers = filteredUsers.slice(startIndex, endIndex);
            
            pageUsers.forEach(user => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <input type="checkbox" class="user-checkbox" value="${user.id}" onchange="updateBulkActions()">
                    </td>
                    <td>${user.id}</td>
                    <td>
                        <div>
                            <strong>${user.username || 'N/A'}</strong><br>
                            <small class="text-muted">${user.email || 'N/A'}</small><br>
                            <small class="text-muted">${user.full_name || 'N/A'}</small>
                        </div>
                    </td>
                    <td>
                        <small>
                            <strong>Age:</strong> ${user.age_display || 'N/A'}<br>
                            <strong>Gender:</strong> ${user.gender || 'N/A'}<br>
                            <strong>Phone:</strong> ${user.phone || 'N/A'}<br>
                            <strong>DOB:</strong> ${user.date_of_birth || 'N/A'}
                        </small>
                    </td>
                    <td>
                        <small>
                            <strong>Weight:</strong> ${user.weight_display || 'N/A'}<br>
                            <strong>Height:</strong> ${user.height_display || 'N/A'}<br>
                            <strong>BMI:</strong> ${user.bmi || 'N/A'}<br>
                            <strong>Goal:</strong> ${user.fitness_goal || 'N/A'}<br>
                            <strong>Target:</strong> ${user.target_weight_display || 'N/A'}<br>
                            <strong>Level:</strong> ${user.level || 'N/A'} | <strong>Points:</strong> ${user.points || '0'}
                        </small>
                    </td>
                    <td>
                        <small>
                            <strong>Last Login:</strong> ${user.last_login || 'Never'}<br>
                            <strong>Meals:</strong> ${user.total_meals || '0'}<br>
                            <strong>Calories:</strong> ${user.total_calories || '0'}<br>
                            <strong>Activities:</strong> ${user.total_activities || '0'}<br>
                            <strong>Joined:</strong> ${new Date(user.created_at).toLocaleDateString()}
                        </small>
                    </td>
                    <td><span class="badge-status badge-${user.status}">${user.status}</span></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="viewUserDetails(${user.id})" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-outline-info" onclick="viewUserMeals(${user.id})" title="View Meals">
                                <i class="fas fa-utensils"></i>
                            </button>
                            <button class="btn btn-outline-success" onclick="viewUserProgress(${user.id})" title="View Progress">
                                <i class="fas fa-chart-line"></i>
                            </button>
                            <button class="btn btn-outline-warning" onclick="editUser(${user.id})" title="Edit User">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteUser(${user.id})" title="Delete User">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
            
            // Update showing count
            document.getElementById('showing-count').textContent = `${startIndex + 1}-${endIndex}`;
            document.getElementById('total-count').textContent = filteredUsers.length;
        }
        
        // Update user statistics
        function updateUserStatistics() {
            const totalUsers = allUsers.length;
            const activeUsers = allUsers.filter(u => u.status === 'active').length;
            const totalMeals = allUsers.reduce((sum, u) => sum + (u.total_meals || 0), 0);
            const totalCalories = allUsers.reduce((sum, u) => sum + (u.total_calories || 0), 0);
            
            document.getElementById('total-users-count').textContent = totalUsers;
            document.getElementById('active-users-count').textContent = activeUsers;
            document.getElementById('total-meals-count').textContent = totalMeals;
            document.getElementById('total-calories-count').textContent = totalCalories;
        }
        
        // Update pagination controls
        function updatePagination() {
            document.getElementById('current-page').textContent = currentPage;
            document.getElementById('total-pages').textContent = totalPages;
            
            document.getElementById('prev-btn').disabled = currentPage <= 1;
            document.getElementById('next-btn').disabled = currentPage >= totalPages;
        }
        
        // Pagination functions
        function previousPage() {
            if (currentPage > 1) {
                currentPage--;
                displayUsers();
                updatePagination();
            }
        }
        
        function nextPage() {
            if (currentPage < totalPages) {
                currentPage++;
                displayUsers();
                updatePagination();
            }
        }
        
        function changePageSize(newSize) {
            pageSize = newSize;
            currentPage = 1;
            applyFilters();
        }
        
        // Bulk selection functions
        function toggleSelectAll() {
            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.user-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateBulkActions();
        }
        
        function updateBulkActions() {
            const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
            const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
            
            bulkDeleteBtn.disabled = checkedBoxes.length === 0;
        }
        
        // Event listeners for filters
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners for filters
            document.getElementById('user-search').addEventListener('input', applyFilters);
            document.getElementById('status-filter').addEventListener('change', applyFilters);
            document.getElementById('gender-filter').addEventListener('change', applyFilters);
            document.getElementById('goal-filter').addEventListener('change', applyFilters);
            document.getElementById('sort-by').addEventListener('change', applyFilters);
        });
        
        
        // Utility functions
        function refreshUsers() {
            loadUsersData();
        }
        
        // Export all users
        function exportAllUsers() {
            const data = {
                users: allUsers,
                export_date: new Date().toISOString(),
                total_users: allUsers.length
            };
            
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `healthmate_users_export_${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
        
        
        // Edit user
        function editUser(userId) {
            const user = allUsers.find(u => u.id == userId);
            if (user) {
                const modal = createEditUserModal(user);
                document.body.appendChild(modal);
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
                
                modal.addEventListener('hidden.bs.modal', () => {
                    document.body.removeChild(modal);
                });
            }
        }
        
        // Delete user
        function deleteUser(userId) {
            const user = allUsers.find(u => u.id == userId);
            if (user && confirm(`Are you sure you want to delete user "${user.username}"? This action cannot be undone.`)) {
                fetch('php/admin_api.php?action=delete_user', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ user_id: userId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('User deleted successfully!');
                        loadUsersData(); // Refresh the user list
                    } else {
                        alert('Error deleting user: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting user');
                });
            }
        }
        
        // Bulk delete users
        function bulkDeleteUsers() {
            const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
            const userIds = Array.from(checkedBoxes).map(cb => cb.value);
            
            if (userIds.length === 0) return;
            
            if (confirm(`Are you sure you want to delete ${userIds.length} selected users? This action cannot be undone.`)) {
                const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
                const prevText = bulkDeleteBtn.innerHTML;
                bulkDeleteBtn.disabled = true;
                bulkDeleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting...';
                
                fetch('php/admin_api.php?action=bulk_delete_users', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ user_ids: userIds })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Selected users deleted successfully');
                        // Refresh list
                        loadUsersData();
                        // Uncheck select all
                        const selectAll = document.getElementById('select-all');
                        if (selectAll) selectAll.checked = false;
                    } else {
                        alert('Error deleting users: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting users');
                })
                .finally(() => {
                    bulkDeleteBtn.disabled = false;
                    bulkDeleteBtn.innerHTML = prevText;
                });
            }
        }
        
        // Add new user
        function addNewUser() {
            const modal = createAddUserModal();
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            modal.addEventListener('hidden.bs.modal', () => {
                document.body.removeChild(modal);
            });
        }
        
        function viewUserDetails(userId) {
            fetch(`php/admin_api.php?action=get_user_details&user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.user;
                        const modal = createUserDetailsModal(user);
                        document.body.appendChild(modal);
                        const bsModal = new bootstrap.Modal(modal);
                        bsModal.show();
                        
                        // Remove modal from DOM when hidden
                        modal.addEventListener('hidden.bs.modal', () => {
                            document.body.removeChild(modal);
                        });
                    } else {
                        alert('Error loading user details: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error loading user details:', error);
                    alert('Error loading user details');
                });
        }
        
        // View user meals
        function viewUserMeals(userId) {
            fetch(`php/admin_api.php?action=get_user_meals&user_id=${userId}&limit=20`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const modal = createUserMealsModal(userId, data.meals);
                        document.body.appendChild(modal);
                        const bsModal = new bootstrap.Modal(modal);
                        bsModal.show();
                        
                        modal.addEventListener('hidden.bs.modal', () => {
                            document.body.removeChild(modal);
                        });
                    } else {
                        alert('Error loading user meals: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error loading user meals:', error);
                    alert('Error loading user meals');
                });
        }
        
        // View user progress
        function viewUserProgress(userId) {
            fetch(`php/admin_api.php?action=get_user_progress&user_id=${userId}&days=30`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const modal = createUserProgressModal(userId, data.nutrition, data.progress);
                        document.body.appendChild(modal);
                        const bsModal = new bootstrap.Modal(modal);
                        bsModal.show();
                        
                        modal.addEventListener('hidden.bs.modal', () => {
                            document.body.removeChild(modal);
                        });
                    } else {
                        alert('Error loading user progress: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error loading user progress:', error);
                    alert('Error loading user progress');
                });
        }
        
        function exportUserData() {
            alert('Export user data functionality');
            // Implement data export
        }
        
        function clearOldData() {
            if (confirm('Are you sure you want to clear old data? This action cannot be undone.')) {
                alert('Clear old data functionality');
                // Implement data clearing
            }
        }
        
        // Create user details modal
        function createUserDetailsModal(user) {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">User Details - ${user.username}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Personal Information</h6>
                                    <p><strong>Name:</strong> ${user.full_name}</p>
                                    <p><strong>Email:</strong> ${user.email}</p>
                                    <p><strong>Phone:</strong> ${user.phone || 'N/A'}</p>
                                    <p><strong>Date of Birth:</strong> ${user.date_of_birth || 'N/A'}</p>
                                    <p><strong>Gender:</strong> ${user.gender || 'N/A'}</p>
                                    <p><strong>Bio:</strong> ${user.bio || 'N/A'}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Fitness Information</h6>
                                    <p><strong>Age:</strong> ${user.age_display}</p>
                                    <p><strong>Weight:</strong> ${user.weight_display}</p>
                                    <p><strong>Height:</strong> ${user.height_display}</p>
                                    <p><strong>BMI:</strong> ${user.bmi || 'N/A'}</p>
                                    <p><strong>Target Weight:</strong> ${user.target_weight_display}</p>
                                    <p><strong>Fitness Goal:</strong> ${user.fitness_goal || 'N/A'}</p>
                                    <p><strong>Experience Level:</strong> ${user.experience_level || 'N/A'}</p>
                                    <p><strong>Workouts/Week:</strong> ${user.workouts_per_week || 'N/A'}</p>
                                    <p><strong>Level:</strong> ${user.level} | <strong>Points:</strong> ${user.points}</p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <h6>Activity Summary</h6>
                                    <p><strong>Total Meals:</strong> ${user.total_meals}</p>
                                    <p><strong>Total Calories:</strong> ${user.total_calories}</p>
                                    <p><strong>Total Activities:</strong> ${user.total_activities}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Account Information</h6>
                                    <p><strong>Last Login:</strong> ${user.last_login}</p>
                                    <p><strong>IP Address:</strong> ${user.ip_address || 'N/A'}</p>
                                    <p><strong>Joined:</strong> ${new Date(user.created_at).toLocaleDateString()}</p>
                                    <p><strong>Last Updated:</strong> ${new Date(user.updated_at).toLocaleDateString()}</p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            `;
            return modal;
        }
        
        // Create user meals modal
        function createUserMealsModal(userId, meals) {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            
            let mealsHtml = '';
            if (meals.length === 0) {
                mealsHtml = '<tr><td colspan="6" class="text-center text-muted">No meals found</td></tr>';
            } else {
                meals.forEach(meal => {
                    mealsHtml += `
                        <tr>
                            <td>${meal.meal_date}</td>
                            <td>${meal.meal_time}</td>
                            <td>${meal.meal_type}</td>
                            <td>${meal.food_name}</td>
                            <td>${meal.amount} ${meal.unit}</td>
                            <td>${meal.calories}</td>
                        </tr>
                    `;
                });
            }
            
            modal.innerHTML = `
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">User Meals - ID: ${userId}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Meal Type</th>
                                            <th>Food Name</th>
                                            <th>Amount</th>
                                            <th>Calories</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${mealsHtml}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            `;
            return modal;
        }
        
        // Create user progress modal
        function createUserProgressModal(userId, nutrition, progress) {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            
            let nutritionHtml = '';
            if (nutrition.length === 0) {
                nutritionHtml = '<tr><td colspan="5" class="text-center text-muted">No nutrition data found</td></tr>';
            } else {
                nutrition.forEach(n => {
                    nutritionHtml += `
                        <tr>
                            <td>${n.date}</td>
                            <td>${n.total_calories}</td>
                            <td>${n.total_protein}</td>
                            <td>${n.total_carbs}</td>
                            <td>${n.total_fats}</td>
                        </tr>
                    `;
                });
            }
            
            let progressHtml = '';
            if (progress.length === 0) {
                progressHtml = '<tr><td colspan="5" class="text-center text-muted">No progress data found</td></tr>';
            } else {
                progress.forEach(p => {
                    progressHtml += `
                        <tr>
                            <td>${p.date}</td>
                            <td>${p.weight || 'N/A'}</td>
                            <td>${p.calories_consumed || 'N/A'}</td>
                            <td>${p.calories_burned || 'N/A'}</td>
                            <td>${p.workout_duration_minutes || 'N/A'}</td>
                        </tr>
                    `;
                });
            }
            
            modal.innerHTML = `
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">User Progress - ID: ${userId}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <h6>Daily Nutrition Totals</h6>
                            <div class="table-responsive mb-4">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Total Calories</th>
                                            <th>Total Protein</th>
                                            <th>Total Carbs</th>
                                            <th>Total Fats</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${nutritionHtml}
                                    </tbody>
                                </table>
                            </div>
                            
                            <h6>Progress Tracking</h6>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Weight</th>
                                            <th>Calories Consumed</th>
                                            <th>Calories Burned</th>
                                            <th>Workout Duration (min)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${progressHtml}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            `;
            return modal;
        }
        
        
        // Create edit user modal
        function createEditUserModal(user) {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit User - ${user.username}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="edit-user-form">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Personal Information</h6>
                                        <div class="mb-3">
                                            <label class="form-label">First Name</label>
                                            <input type="text" class="form-control" name="first_name" value="${user.first_name || ''}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Last Name</label>
                                            <input type="text" class="form-control" name="last_name" value="${user.last_name || ''}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" value="${user.email || ''}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Phone</label>
                                            <input type="text" class="form-control" name="phone" value="${user.phone || ''}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Date of Birth</label>
                                            <input type="date" class="form-control" name="date_of_birth" value="${user.date_of_birth || ''}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Gender</label>
                                            <select class="form-control" name="gender">
                                                <option value="">Select Gender</option>
                                                <option value="male" ${user.gender === 'male' ? 'selected' : ''}>Male</option>
                                                <option value="female" ${user.gender === 'female' ? 'selected' : ''}>Female</option>
                                                <option value="other" ${user.gender === 'other' ? 'selected' : ''}>Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Fitness Information</h6>
                                        <div class="mb-3">
                                            <label class="form-label">Age</label>
                                            <input type="number" class="form-control" name="age" value="${user.age || ''}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Weight (kg)</label>
                                            <input type="number" class="form-control" name="weight" value="${user.weight || ''}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Height (cm)</label>
                                            <input type="number" class="form-control" name="height" value="${user.height || ''}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Target Weight (kg)</label>
                                            <input type="number" class="form-control" name="target_weight" value="${user.target_weight || ''}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Fitness Goal</label>
                                            <select class="form-control" name="fitness_goal">
                                                <option value="">Select Goal</option>
                                                <option value="weight_loss" ${user.fitness_goal === 'weight_loss' ? 'selected' : ''}>Weight Loss</option>
                                                <option value="muscle_gain" ${user.fitness_goal === 'muscle_gain' ? 'selected' : ''}>Muscle Gain</option>
                                                <option value="endurance" ${user.fitness_goal === 'endurance' ? 'selected' : ''}>Endurance</option>
                                                <option value="general_fitness" ${user.fitness_goal === 'general_fitness' ? 'selected' : ''}>General Fitness</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Experience Level</label>
                                            <select class="form-control" name="experience_level">
                                                <option value="">Select Level</option>
                                                <option value="beginner" ${user.experience_level === 'beginner' ? 'selected' : ''}>Beginner</option>
                                                <option value="intermediate" ${user.experience_level === 'intermediate' ? 'selected' : ''}>Intermediate</option>
                                                <option value="advanced" ${user.experience_level === 'advanced' ? 'selected' : ''}>Advanced</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Workouts per Week</label>
                                            <input type="number" class="form-control" name="workouts_per_week" value="${user.workouts_per_week || ''}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Level</label>
                                            <input type="number" class="form-control" name="level" value="${user.level || ''}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Points</label>
                                            <input type="number" class="form-control" name="points" value="${user.points || ''}">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Bio</label>
                                    <textarea class="form-control" name="bio" rows="3">${user.bio || ''}</textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="saveUserChanges(${user.id})">Save Changes</button>
                        </div>
                    </div>
                </div>
            `;
            return modal;
        }
        
        // Save user changes
        function saveUserChanges(userId) {
            const form = document.getElementById('edit-user-form');
            const formData = new FormData(form);
            const userData = Object.fromEntries(formData.entries());
            userData.user_id = userId;
            
            fetch('php/admin_api.php?action=update_user', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(userData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User updated successfully!');
                    loadUsersData(); // Refresh the user list
                } else {
                    alert('Error updating user: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating user');
            });
            
            // Close modal
            const modal = document.querySelector('.modal.show');
            if (modal) {
                const bsModal = bootstrap.Modal.getInstance(modal);
                bsModal.hide();
            }
        }
        
        // Create add user modal
        function createAddUserModal() {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add New User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="add-user-form">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Account Information</h6>
                                        <div class="mb-3">
                                            <label class="form-label">Username *</label>
                                            <input type="text" class="form-control" name="username" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email *</label>
                                            <input type="email" class="form-control" name="email" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Password *</label>
                                            <input type="password" class="form-control" name="password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">First Name *</label>
                                            <input type="text" class="form-control" name="first_name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Last Name *</label>
                                            <input type="text" class="form-control" name="last_name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Phone</label>
                                            <input type="text" class="form-control" name="phone">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Date of Birth</label>
                                            <input type="date" class="form-control" name="date_of_birth">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Gender</label>
                                            <select class="form-control" name="gender">
                                                <option value="">Select Gender</option>
                                                <option value="male">Male</option>
                                                <option value="female">Female</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Fitness Information</h6>
                                        <div class="mb-3">
                                            <label class="form-label">Age</label>
                                            <input type="number" class="form-control" name="age" min="13" max="120">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Weight (kg)</label>
                                            <input type="number" class="form-control" name="weight" step="0.1" min="20" max="300">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Height (cm)</label>
                                            <input type="number" class="form-control" name="height" min="100" max="250">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Target Weight (kg)</label>
                                            <input type="number" class="form-control" name="target_weight" step="0.1" min="20" max="300">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Fitness Goal</label>
                                            <select class="form-control" name="fitness_goal">
                                                <option value="general_fitness">General Fitness</option>
                                                <option value="weight_loss">Weight Loss</option>
                                                <option value="muscle_gain">Muscle Gain</option>
                                                <option value="endurance">Endurance</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Experience Level</label>
                                            <select class="form-control" name="experience_level">
                                                <option value="beginner">Beginner</option>
                                                <option value="intermediate">Intermediate</option>
                                                <option value="advanced">Advanced</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Workouts per Week</label>
                                            <input type="number" class="form-control" name="workouts_per_week" min="0" max="7">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Level</label>
                                            <input type="number" class="form-control" name="level" min="1" max="100" value="1">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Points</label>
                                            <input type="number" class="form-control" name="points" min="0" value="0">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Bio</label>
                                    <textarea class="form-control" name="bio" rows="3" placeholder="Tell us about yourself..."></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="saveNewUser()">Add User</button>
                        </div>
                    </div>
                </div>
            `;
            return modal;
        }
        
        // Save new user
        function saveNewUser() {
            const form = document.getElementById('add-user-form');
            const formData = new FormData(form);
            const userData = Object.fromEntries(formData.entries());
            
            // Validate required fields
            const required = ['username', 'email', 'password', 'first_name', 'last_name'];
            for (const field of required) {
                if (!userData[field]) {
                    alert(`Please fill in the ${field.replace('_', ' ')} field.`);
                    return;
                }
            }
            
            fetch('php/admin_api.php?action=add_user', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(userData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User added successfully!');
                    loadUsersData(); // Refresh the user list
                    
                    // Close modal
                    const modal = document.querySelector('.modal.show');
                    if (modal) {
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        bsModal.hide();
                    }
                } else {
                    alert('Error adding user: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding user');
            });
        }
        
        // Initialize dashboard on load
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
        });
    </script>
</body>
</html>
