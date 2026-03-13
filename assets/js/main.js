// Real-time Dashboard Updates
function startRealtimeDashboardUpdates() {
    const base = window.location.pathname.includes('/pages/') ? '../' : '';
    const refreshStats = async () => {
        try {
            const res = await fetch(`${base}php/dashboard_api.php?action=stats`, { credentials: 'include' });
            const json = await res.json();
            if (!json.success) return;
            const data = json.data || {};
            // Update counters instantly; keep animation simple
            const cal = document.getElementById('statCaloriesBurned');
            const streak = document.getElementById('statDayStreak');
            const goal = document.getElementById('statGoalProgress');
            if (cal) cal.textContent = data.calories_burned ?? cal.textContent;
            if (streak) streak.textContent = data.current_streak ?? streak.textContent;
            if (goal) goal.textContent = data.goal_progress ?? goal.textContent;
        } catch (_) { /* ignore transient errors */ }
    };

    const refreshCharts = () => {
        // Reuse existing loaders if present
        if (typeof loadProgressChart === 'function') {
            loadProgressChart();
        }
        if (typeof loadCaloriesChart === 'function') {
            loadCaloriesChart();
        }
        if (typeof loadRecentActivity === 'function') {
            loadRecentActivity();
        }
    };

    // Initial fetches
    refreshStats();
    refreshCharts();
    // Schedule periodic updates
    setInterval(refreshStats, 15000); // 15s for stats
    setInterval(refreshCharts, 60000); // 60s for heavier charts
}
// HealthMate - Modern UI/UX JavaScript

// Global variables
let currentUser = null;
let workoutData = null;
let isAnimating = false;

// Initialize application
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Add smooth animations and interactions
    initializeAnimations();
    
    // Initialize navigation
    initializeNavigation();
    
    // Load workout data
    loadWorkoutData();

    // Initialize workout filters (only on workouts page)
    if (document.getElementById('difficultyFilter') || document.getElementById('durationFilter')) {
        initializeWorkoutFilters();
    }
    
    // Initialize authentication forms
    initializeAuthForms();
    
    // Initialize contact form
    initializeContactForm();
    
    // Initialize charts if on dashboard
    if (document.getElementById('progressChart')) {
        initializeCharts();
        // Also initialize our custom progress chart
        createTimePeriodSelector();
        // Force initialize the progress chart with fallback data
        setTimeout(() => {
            console.log('Force initializing progress chart...');
            // Try the complex method first
            updateMainProgressChart('week');
        }, 1000);
        
        // Also try a simple direct approach after 2 seconds
        setTimeout(() => {
            console.log('Trying direct chart creation...');
            if (!window.mainProgressChartInstance) {
                createDirectProgressChart();
            }
        }, 2500);
        
        // Final fallback - force create chart after 3 seconds
        setTimeout(() => {
            console.log('Final fallback - forcing chart creation...');
            if (!window.mainProgressChartInstance) {
                forceCreateProgressChart();
            }
        }, 4000);
    }
    
    // Initialize workout recommendations
    if (document.getElementById('workoutRecommendations')) {
        loadWorkoutRecommendations();
    }
    
    // Initialize leaderboard
    if (document.getElementById('leaderboard')) {
        loadLeaderboard();
    }
    
    // Initialize meal options for all meal types (always needed on dashboard)
    setupMealOptions();
    
    // Initialize scroll effects
    initializeScrollEffects();
    
    // Initialize dashboard features when relevant sections exist
    if (document.getElementById('currentMealFoods') || document.getElementById('bmrForm')) {
        initializeCaloriesCalculator();
    }

    // Start real-time dashboard refresh if on dashboard
    if (document.getElementById('statCaloriesBurned') || document.getElementById('statDayStreak') || document.getElementById('statGoalProgress')) {
        startRealtimeDashboardUpdates();
    }
    
    // Initialize progress modal charts when modal is shown
    const progressModal = document.getElementById('progressModal');
    if (progressModal) {
        progressModal.addEventListener('shown.bs.modal', function() {
            console.log('Progress modal shown, initializing charts...');
            setTimeout(() => {
                initializeProgressModalCharts();
            }, 100); // Small delay to ensure DOM is ready
        });
        
        // Also destroy charts when modal is hidden to prevent conflicts
        progressModal.addEventListener('hidden.bs.modal', function() {
            console.log('Progress modal hidden, destroying charts...');
            if (window.weeklyProgressChartInstance) {
                window.weeklyProgressChartInstance.destroy();
                window.weeklyProgressChartInstance = null;
            }
            if (window.macroProgressChartInstance) {
                window.macroProgressChartInstance.destroy();
                window.macroProgressChartInstance = null;
            }
        });
    }
}

// Workout filters: category buttons + difficulty/duration selects
function initializeWorkoutFilters() {
    const categoryButtons = document.querySelectorAll('[data-filter]');
    const difficultySelect = document.getElementById('difficultyFilter');
    const durationSelect = document.getElementById('durationFilter');
    const workoutCards = () => document.querySelectorAll('.workout-card');

    let activeCategory = 'all';

    const matchesDuration = (cardMinutes, selected) => {
        if (!selected) return true;
        const minutes = parseInt(cardMinutes, 10) || 0;
        const bucket = parseInt(selected, 10);
        // Buckets: 15 => <= 20, 30 => 21-35, 45 => 36-50, 60 => >= 51
        if (bucket === 15) return minutes <= 20;
        if (bucket === 30) return minutes >= 21 && minutes <= 35;
        if (bucket === 45) return minutes >= 36 && minutes <= 50;
        if (bucket === 60) return minutes >= 51;
        return true;
    };

    const applyFilters = () => {
        const selectedDifficulty = (difficultySelect && difficultySelect.value) || '';
        const selectedDuration = (durationSelect && durationSelect.value) || '';

        workoutCards().forEach(card => {
            const category = card.getAttribute('data-category') || '';
            const difficulty = card.getAttribute('data-difficulty') || '';
            const duration = card.getAttribute('data-duration') || '';

            const categoryOk = activeCategory === 'all' || category === activeCategory;
            const difficultyOk = !selectedDifficulty || difficulty === selectedDifficulty;
            const durationOk = matchesDuration(duration, selectedDuration);

            card.classList.toggle('hidden', !(categoryOk && difficultyOk && durationOk));
        });
    };

    // Category buttons
    categoryButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            categoryButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeCategory = btn.getAttribute('data-filter') || 'all';
            applyFilters();
        });
    });

    // Select filters
    if (difficultySelect) difficultySelect.addEventListener('change', applyFilters);
    if (durationSelect) durationSelect.addEventListener('change', applyFilters);

    // Initial run
    applyFilters();
}

// Modern Animations and Interactions
function initializeAnimations() {
    // Intersection Observer for fade-in animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in-up');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Observe all cards and sections
    document.querySelectorAll('.card, .feature-card, .workout-card, .leaderboard-item').forEach(el => {
        observer.observe(el);
    });
    
    // Parallax effect for hero section (disabled)
    const heroSection = document.querySelector('.hero-section');
    if (heroSection) {
        // intentionally disabled to prevent visual shifts on interaction
        heroSection.style.transform = 'none';
    }
}

// Enhanced Navigation
function initializeNavigation() {
    // Smooth scrolling with enhanced behavior
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const headerOffset = 80;
                const elementPosition = target.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
                
                // Update active navigation
                updateActiveNavigation(this.getAttribute('href'));
            }
        });
    });
    
    // Mobile menu with enhanced UX
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    if (navbarToggler && navbarCollapse) {
        navbarToggler.addEventListener('click', function() {
            navbarCollapse.classList.toggle('show');
            
            // Animate hamburger icon
            const icon = this.querySelector('.navbar-toggler-icon');
            icon.classList.toggle('rotate');
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!navbarToggler.contains(e.target) && !navbarCollapse.contains(e.target)) {
                navbarCollapse.classList.remove('show');
            }
        });
    }
}

function updateActiveNavigation(hash) {
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });
    
    const activeLink = document.querySelector(`a[href="${hash}"]`);
    if (activeLink) {
        activeLink.classList.add('active');
    }
}

// Scroll Effects
function initializeScrollEffects() {
    // Navbar background on scroll
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }
    
    // Counter animations
    const counters = document.querySelectorAll('.counter');
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = parseInt(counter.getAttribute('data-target'));
                const duration = 2000; // 2 seconds
                const increment = target / (duration / 16); // 60fps
                let current = 0;
                
                const updateCounter = () => {
                    current += increment;
                    if (current < target) {
                        counter.textContent = Math.floor(current);
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.textContent = target;
                    }
                };
                
                updateCounter();
                counterObserver.unobserve(counter);
            }
        });
    });
    
    counters.forEach(counter => counterObserver.observe(counter));
}

// Enhanced Authentication Functions
function initializeAuthForms() {
    // Login form with enhanced validation
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
        initializeFormValidation(loginForm);
    }
    
    // Registration form with enhanced validation
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
        initializeFormValidation(registerForm);
    }
    
    // Logout button
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }
    // Delegated listener as fallback (handles dynamic DOM and nested icon clicks)
    document.addEventListener('click', function(e) {
        const btn = e.target && e.target.closest && e.target.closest('#logoutBtn');
        if (btn) {
            handleLogout(e);
        }
    });
    
    // Tab switching with animations
    const authTabs = document.querySelectorAll('[data-bs-toggle="tab"]');
    authTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const target = document.querySelector(this.getAttribute('data-bs-target'));
            if (target) {
                target.classList.add('animate-fade-in-up');
                setTimeout(() => {
                    target.classList.remove('animate-fade-in-up');
                }, 600);
            }
        });
    });
}

function initializeFormValidation(form) {
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        // Real-time validation
        input.addEventListener('blur', validateField);
        input.addEventListener('input', clearFieldError);
        
        // Enhanced focus effects
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });
    });
}

function validateField(e) {
    const field = e.target;
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    // Remove existing error
    clearFieldError(e);
    
    // Validation rules
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'This field is required';
    } else if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address';
        }
    } else if (field.type === 'password' && value.length < 6) {
        isValid = false;
        errorMessage = 'Password must be at least 6 characters';
    } else if (field.name === 'age' && value) {
        const age = parseInt(value);
        if (age < 13 || age > 100) {
            isValid = false;
            errorMessage = 'Age must be between 13 and 100';
        }
    }
    
    if (!isValid) {
        showFieldError(field, errorMessage);
    }
    
    return isValid;
}

function showFieldError(field, message) {
    field.classList.add('is-invalid');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    
    field.parentElement.appendChild(errorDiv);
}

function clearFieldError(e) {
    const field = e.target;
    field.classList.remove('is-invalid');
    
    const errorDiv = field.parentElement.querySelector('.invalid-feedback');
    if (errorDiv) {
        errorDiv.remove();
    }
}

async function handleLogin(e) {
    e.preventDefault();
    
    if (!validateForm(e.target)) return;
    
    const formData = new FormData(e.target);
    formData.append('action', 'login');
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Enhanced loading state
    submitBtn.innerHTML = '<span class="loading"></span> Signing in...';
    submitBtn.disabled = true;
    submitBtn.classList.add('loading');
    
    try {
        const response = await fetch('php/auth.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', result.message, 'Welcome back!');
            setTimeout(() => {
                window.location.href = 'pages/dashboard.php';
            }, 1500);
        } else {
            showNotification('error', result.message);
        }
    } catch (error) {
        showNotification('error', 'Connection error. Please try again.');
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        submitBtn.classList.remove('loading');
    }
}

async function handleRegister(e) {
    e.preventDefault();
    
    if (!validateForm(e.target)) return;
    
    const formData = new FormData(e.target);
    formData.append('action', 'register');
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<span class="loading"></span> Creating account...';
    submitBtn.disabled = true;
    submitBtn.classList.add('loading');
    
    try {
        const response = await fetch('php/auth.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', result.message, 'Account created successfully!');
            setTimeout(() => {
                // Switch to login form with animation
                const loginTab = document.getElementById('login-tab');
                if (loginTab) {
                    loginTab.click();
                    showNotification('info', 'Please log in with your new account');
                }
            }, 1500);
        } else {
            showNotification('error', result.message);
        }
    } catch (error) {
        showNotification('error', 'Connection error. Please try again.');
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        submitBtn.classList.remove('loading');
    }
}

function validateForm(form) {
    const inputs = form.querySelectorAll('input, select, textarea');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!validateField({ target: input })) {
            isValid = false;
        }
    });
    
    return isValid;
}

async function handleLogout(e) {
    if (e && typeof e.preventDefault === 'function') e.preventDefault();
    const base = window.location.pathname.includes('/pages/') ? '../' : '';
    const formData = new FormData();
    formData.append('action', 'logout');
    
    try {
        const response = await fetch(`${base}php/auth.php`, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', 'Logged out successfully');
            setTimeout(() => {
                window.location.href = `${base}index.html`;
            }, 800);
        } else {
            showNotification('error', result.message || 'Logout failed');
        }
    } catch (error) {
        console.error('Logout error:', error);
        showNotification('error', 'Network error during logout');
    }
}

// Enhanced Contact Form
function initializeContactForm() {
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', handleContactSubmit);
        initializeFormValidation(contactForm);
    }
}

async function handleContactSubmit(e) {
    e.preventDefault();
    
    if (!validateForm(e.target)) return;
    
    const formData = new FormData(e.target);
    formData.append('action', 'submit_feedback');
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerText;
    
    // Simple: no loading animation/effects
    submitBtn.innerText = 'Sending...';
    submitBtn.disabled = true;
    
    try {
        const response = await fetch('php/feedback.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', result.message, 'Message sent successfully!');
            e.target.reset();
            
            // Reset form styling
            e.target.querySelectorAll('.form-control').forEach(input => {
                input.parentElement.classList.remove('focused');
            });
        } else {
            showNotification('error', result.message);
        }
    } catch (error) {
        showNotification('error', 'Connection error. Please try again.');
    } finally {
        submitBtn.innerText = originalText;
        submitBtn.disabled = false;
        // No loading class used for simple button
    }
}

// Enhanced Workout Data and Recommendations
function loadWorkoutData() {
    // Enhanced workout data with more details
    workoutData = {
        weight_loss: [
            {
                name: "HIIT Cardio Blast",
                description: "High-intensity interval training for maximum fat burn",
                duration: "30 minutes",
                calories: 400,
                difficulty: "Intermediate",
                category: "Cardio",
                equipment: ["None"],
                exercises: [
                    { name: "Burpees", sets: 4, reps: 10, rest: "30s", video: "burpees.mp4" },
                    { name: "Mountain Climbers", sets: 4, reps: "45s", rest: "30s", video: "mountain_climbers.mp4" },
                    { name: "Jump Squats", sets: 3, reps: 15, rest: "45s", video: "jump_squats.mp4" },
                    { name: "High Knees", sets: 3, reps: "30s", rest: "30s", video: "high_knees.mp4" }
                ]
            },
            {
                name: "Fat Burning Circuit",
                description: "Full body circuit training for weight loss",
                duration: "45 minutes",
                calories: 350,
                difficulty: "Beginner",
                category: "Strength",
                equipment: ["Dumbbells"],
                exercises: [
                    { name: "Push-ups", sets: 3, reps: 12, rest: "60s", video: "push_ups.mp4" },
                    { name: "Squats", sets: 3, reps: 20, rest: "60s", video: "squats.mp4" },
                    { name: "Plank", sets: 3, reps: "45s", rest: "60s", video: "plank.mp4" },
                    { name: "Lunges", sets: 3, reps: 15, rest: "60s", video: "lunges.mp4" }
                ]
            }
        ],
        muscle_gain: [
            {
                name: "Strength Building",
                description: "Progressive overload training for muscle growth",
                duration: "60 minutes",
                calories: 300,
                difficulty: "Advanced",
                category: "Strength",
                equipment: ["Barbell", "Bench", "Pull-up Bar"],
                exercises: [
                    { name: "Bench Press", sets: 4, reps: 8, rest: "90s", video: "bench_press.mp4" },
                    { name: "Deadlifts", sets: 4, reps: 6, rest: "120s", video: "deadlifts.mp4" },
                    { name: "Squats", sets: 4, reps: 10, rest: "90s", video: "squats.mp4" },
                    { name: "Pull-ups", sets: 3, reps: 8, rest: "90s", video: "pull_ups.mp4" }
                ]
            }
        ],
        endurance: [
            {
                name: "Endurance Training",
                description: "Build cardiovascular endurance and stamina",
                duration: "45 minutes",
                calories: 400,
                difficulty: "Intermediate",
                category: "Cardio",
                equipment: ["Treadmill", "Bike"],
                exercises: [
                    { name: "Running", sets: 1, reps: "30 minutes", rest: "None", video: "running.mp4" },
                    { name: "Cycling", sets: 1, reps: "20 minutes", rest: "None", video: "cycling.mp4" },
                    { name: "Jump Rope", sets: 3, reps: "5 minutes", rest: "60s", video: "jump_rope.mp4" }
                ]
            }
        ]
    };
}

function loadWorkoutRecommendations() {
    const container = document.getElementById('workoutRecommendations');
    if (!container || !workoutData) return;
    
    const fitnessGoal = 'weight_loss'; // This would be dynamic based on user data
    const workouts = workoutData[fitnessGoal] || workoutData.weight_loss;
    
    container.innerHTML = '';
    
    // Disabled per request: remove workout cards
}

function createEnhancedWorkoutCard(workout, index) {
    const card = document.createElement('div');
    card.className = 'col-lg-6 col-md-12 mb-4';
    
    card.innerHTML = `
        <div class="workout-card">
            <div class="workout-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="mb-1">${workout.name}</h5>
                        <span class="workout-badge">${workout.difficulty}</span>
                        <span class="badge bg-secondary ms-2">${workout.category}</span>
                    </div>
                    <div class="text-end">
                        <div class="text-white-50 small">Duration</div>
                        <div class="fw-bold">${workout.duration}</div>
                    </div>
                </div>
            </div>
            <div class="workout-body">
                <p class="text-muted mb-3">${workout.description}</p>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-fire text-danger me-2"></i>
                            <div>
                                <small class="text-muted d-block">Calories</small>
                                <strong>${workout.calories}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-dumbbell text-primary me-2"></i>
                            <div>
                                <small class="text-muted d-block">Equipment</small>
                                <strong>${workout.equipment.join(', ')}</strong>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <h6 class="mb-2">Exercises:</h6>
                    <div class="exercise-list">
                        ${workout.exercises.map((exercise, i) => `
                            <div class="exercise-item d-flex justify-content-between align-items-center p-2 rounded mb-2" style="background: rgba(99, 102, 241, 0.1);">
                                <div>
                                    <strong>${exercise.name}</strong>
                                    <div class="small text-muted">${exercise.sets} sets × ${exercise.reps} reps</div>
                                </div>
                                ${exercise.rest ? `<span class="badge bg-light text-dark">${exercise.rest}</span>` : ''}
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button class="btn btn-primary flex-fill" onclick="startWorkout(${index})">
                        <i class="fas fa-play me-2"></i>Start Workout
                    </button>
                    <button class="btn btn-outline-primary" onclick="previewWorkout(${index})">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    return card;
}

function startWorkout(workoutIndex) {
    // Redirect Complete Body Transformation start button to the Just Exercise page
    if (workoutIndex === 'featured') {
        window.location.href = 'exercise.php?workout=featured';
        return;
    }

    showNotification('success', 'Workout started! Track your progress and stay motivated!', 'Let\'s get moving! 💪');
    setTimeout(() => {
        showNotification('info', 'Remember to stay hydrated and listen to your body!', '💧 Stay hydrated!');
    }, 2000);
}

function previewWorkout(workoutIndex) {
    showNotification('info', 'Workout preview feature coming soon!', '🎬 Preview Mode');
}

// Enhanced Charts and Progress Tracking
function initializeCharts() {
    // Weight Progress Chart with enhanced styling
    const weightCtx = document.getElementById('weightChart');
    if (weightCtx) {
        new Chart(weightCtx, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6'],
                datasets: [{
                    label: 'Weight (kg)',
                    data: [75, 74.5, 74, 73.5, 73, 72.5],
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    title: {
                        display: true,
                        text: 'Weight Progress',
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    
    // Calories Burned Chart with enhanced styling
    const caloriesCtx = document.getElementById('caloriesChart');
    if (caloriesCtx) {
        new Chart(caloriesCtx, {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Calories Burned',
                    data: [350, 400, 300, 450, 380, 420, 320],
                    backgroundColor: [
                        'rgba(99, 102, 241, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(147, 51, 234, 0.8)',
                        'rgba(236, 72, 153, 0.8)'
                    ],
                    borderColor: [
                        '#6366f1',
                        '#10b981',
                        '#f59e0b',
                        '#ef4444',
                        '#3b82f6',
                        '#9333ea',
                        '#ec4899'
                    ],
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    title: {
                        display: true,
                        text: 'Weekly Calories Burned',
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
}

// Enhanced Leaderboard
function loadLeaderboard() {
    const container = document.getElementById('leaderboard');
    if (!container) return;

    const params = new URLSearchParams();
    const activePeriodBtn = document.querySelector('[data-period].active');
    const period = activePeriodBtn ? activePeriodBtn.getAttribute('data-period') : 'all';
    const category = (document.getElementById('categoryFilter')?.value) || 'points';
    params.set('period', period);
    params.set('category', category);
    params.set('limit', '50');

    container.innerHTML = '<div class="p-4 text-center text-muted">Loading leaderboard...</div>';

    fetch(`../php/leaderboard_api.php?${params.toString()}`)
        .then(res => res.json())
        .then(data => {
            if (!data || data.success === false) {
                throw new Error(data?.error || 'Failed to load leaderboard');
            }

            const leaders = data.leaders || [];
            container.innerHTML = '';

            leaders.forEach(user => {
                const item = document.createElement('div');
                item.className = 'leaderboard-item';

                const rankClass = (user.rank <= 3) ? user.rank : 'other';
                const pointsText = typeof user.points === 'number' ? `${user.points} points` : '';
                const scoreText = (category !== 'points' && typeof user.score === 'number') ? ` • ${user.score} ${category === 'workouts' ? 'workouts' : 'calories'}` : '';

                item.innerHTML = `
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="rank-badge rank-${rankClass} me-3">${user.rank}</div>
                            <div>
                                <h6 class="mb-0">${user.name}</h6>
                                <div class="d-flex align-items-center gap-2">
                                    <small class="text-muted">${pointsText}${scoreText}</small>
                                </div>
                            </div>
                        </div>
                        <div class="badge fs-4">${user.badge || '💪'}</div>
                    </div>
                `;

                container.appendChild(item);
            });

            if (leaders.length === 0) {
                container.innerHTML = '<div class="p-4 text-center text-muted">No data available.</div>';
            }

            // Render top podium if available
            const podium = document.getElementById('topPodium');
            if (podium) {
                const [first, second, third] = leaders;
                podium.innerHTML = '';

                const renderPodiumItem = (place, data) => {
                    if (!data) return '';
                    const emojis = { 1: '👑', 2: '🥈', 3: '🥉' };
                    const placeClass = place === 1 ? 'first-place' : (place === 2 ? 'second-place' : 'third-place');
                    return `
                        <div class="col-lg-4 col-md-4 text-center mb-3">
                            <div class="podium-item ${placeClass}">
                                <div class="podium-avatar">
                                    <i class="fas fa-user-circle ${place === 1 ? 'fa-4x' : (place === 2 ? 'fa-3x' : 'fa-2x')}"></i>
                                </div>
                                <div class="podium-rank">${emojis[place]}</div>
                                <h${place === 1 ? '4' : (place === 2 ? '5' : '6')} class="mb-1">${data.name}</h${place === 1 ? '4' : (place === 2 ? '5' : '6')}>
                                <p class="text-muted mb-2">${data.points} points</p>
                                <div class="podium-stats">
                                    <span class="badge bg-light text-dark">Rank ${data.rank}</span>
                                </div>
                            </div>
                        </div>
                    `;
                };

                // Order as 2nd, 1st, 3rd like the existing layout
                podium.innerHTML += renderPodiumItem(2, second);
                podium.innerHTML += renderPodiumItem(1, first);
                podium.innerHTML += renderPodiumItem(3, third);
            }
        })
        .catch(err => {
            container.innerHTML = `<div class="p-4 text-center text-danger">${err.message}</div>`;
        });
}

// Enhanced Notification System (unified signature)
function showNotification(arg1, arg2, arg3) {
    // Support both usages:
    // 1) showNotification('success', 'Message', 'Title')
    // 2) showNotification('Message', 'success')
    const isTypeFirst = typeof arg1 === 'string' && ['success','error','warning','info','danger'].includes(arg1);
    const type = isTypeFirst ? arg1 : (arg2 || 'info');
    const message = isTypeFirst ? (arg2 || '') : (arg1 || '');
    const title = isTypeFirst ? (arg3 || '') : '';
    const notificationContainer = document.getElementById('notificationContainer');
    if (!notificationContainer) {
        const container = document.createElement('div');
        container.id = 'notificationContainer';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        `;
        document.body.appendChild(container);
    }
    
    const notification = document.createElement('div');
    const normalizedType = (type === 'error') ? 'danger' : type;
    notification.className = `alert alert-${normalizedType} alert-dismissible fade show shadow-lg`;

    // Strong, readable color scheme per type
    const palette = {
        success: { bg: '#16a34a', text: '#ffffff', border: '#15803d' },
        danger:  { bg: '#dc2626', text: '#ffffff', border: '#b91c1c' },
        warning: { bg: '#d97706', text: '#111827', border: '#b45309' },
        info:    { bg: '#2563eb', text: '#ffffff', border: '#1d4ed8' }
    };
    const colors = palette[normalizedType] || palette.info;

    notification.style.cssText = `
        margin-bottom: 10px;
        border: none;
        border-left: 6px solid ${colors.border};
        border-radius: 12px;
        animation: slideInRight 0.3s ease-out;
        background: ${colors.bg};
        color: ${colors.text};
        box-shadow: 0 10px 18px rgba(0,0,0,0.12);
    `;
    
    const icon = getNotificationIcon(normalizedType);
    
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="me-3">
                ${icon}
            </div>
            <div class="flex-grow-1">
                ${title ? `<div class="fw-bold" style="color:${colors.text}">${title}</div>` : ''}
                <div style="color:${colors.text}">${message}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    document.getElementById('notificationContainer').appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }
    }, 5000);
}

function getNotificationIcon(type) {
    const icons = {
        success: '<i class="fas fa-check-circle fa-lg"></i>',
        error: '<i class="fas fa-exclamation-circle fa-lg"></i>',
        warning: '<i class="fas fa-exclamation-triangle fa-lg"></i>',
        info: '<i class="fas fa-info-circle fa-lg"></i>'
    };
    return icons[type] || icons.info;
}

// Enhanced Calories Calculator System
let foodDatabase = {};
let addedFoods = [];
let dailyMeals = {
    breakfast: [],
    lunch: [],
    dinner: [],
    snacks: []
};
let dailyCalorieGoal = 2000;
let dailyMacroGoals = {
    protein: 150,
    carbs: 250,
    fats: 65
};
let currentMealType = 'breakfast';
let mealHistory = [];

// BMR and meal plan variables
let userProfile = {
    age: 0,
    gender: '',
    weight: 0,
    height: 0,
    activityLevel: 0,
    fitnessGoal: '',
    bmr: 0,
    tdee: 0,
    goalCalories: 0
};
let mealPlans = {
    cutting: {},
    maintenance: {},
    bulking: {}
};

// Enhanced food database with detailed nutritional information
const initializeFoodDatabase = () => {
    foodDatabase = {
        'apple': { 
            name: 'Apple', 
            caloriesPer100g: 52, 
            protein: 0.3, 
            carbs: 13.8, 
            fats: 0.2, 
            fiber: 2.4,
            sugar: 10.4,
            unit: 'g',
            category: 'fruit',
            vitamins: { 'Vitamin C': 4.6, 'Vitamin K': 2.2 },
            minerals: { 'Potassium': 107, 'Manganese': 0.04 }
        },
        'banana': { 
            name: 'Banana', 
            caloriesPer100g: 89, 
            protein: 1.1, 
            carbs: 22.8, 
            fats: 0.3, 
            fiber: 2.6,
            sugar: 12.2,
            unit: 'g',
            category: 'fruit',
            vitamins: { 'Vitamin C': 8.7, 'Vitamin B6': 0.4 },
            minerals: { 'Potassium': 358, 'Manganese': 0.27 }
        },
        'chicken breast': { 
            name: 'Chicken Breast', 
            caloriesPer100g: 165, 
            protein: 31, 
            carbs: 0, 
            fats: 3.6, 
            fiber: 0,
            sugar: 0,
            unit: 'g',
            category: 'protein',
            vitamins: { 'Niacin': 14.8, 'Vitamin B6': 1.0 },
            minerals: { 'Phosphorus': 228, 'Selenium': 27.4 }
        },
        'rice': { 
            name: 'White Rice (cooked)', 
            caloriesPer100g: 130, 
            protein: 2.7, 
            carbs: 28, 
            fats: 0.3, 
            fiber: 0.4,
            sugar: 0.1,
            unit: 'g',
            category: 'grain',
            vitamins: { 'Thiamine': 0.07, 'Folate': 8 },
            minerals: { 'Manganese': 0.5, 'Selenium': 7.5 }
        },
        'eggs': { 
            name: 'Eggs', 
            caloriesPer100g: 155, 
            protein: 13, 
            carbs: 1.1, 
            fats: 11, 
            fiber: 0,
            sugar: 1.1,
            unit: 'g',
            category: 'protein',
            vitamins: { 'Vitamin B12': 1.1, 'Vitamin D': 2.0 },
            minerals: { 'Selenium': 30.7, 'Phosphorus': 198 }
        },
        'bread': { 
            name: 'White Bread', 
            caloriesPer100g: 265, 
            protein: 9, 
            carbs: 49, 
            fats: 3.2, 
            fiber: 2.7,
            sugar: 5.7,
            unit: 'g',
            category: 'grain',
            vitamins: { 'Folate': 111, 'Thiamine': 0.5 },
            minerals: { 'Iron': 3.6, 'Selenium': 22.5 }
        },
        'milk': { 
            name: 'Whole Milk', 
            caloriesPer100ml: 61, 
            protein: 3.2, 
            carbs: 4.8, 
            fats: 3.3, 
            fiber: 0,
            sugar: 4.8,
            unit: 'ml',
            category: 'dairy',
            vitamins: { 'Vitamin B12': 0.5, 'Vitamin D': 1.2 },
            minerals: { 'Calcium': 113, 'Phosphorus': 84 }
        },
        'yogurt': { 
            name: 'Greek Yogurt', 
            caloriesPer100g: 59, 
            protein: 10, 
            carbs: 3.6, 
            fats: 0.4, 
            fiber: 0,
            sugar: 3.6,
            unit: 'g',
            category: 'dairy',
            vitamins: { 'Vitamin B12': 0.5, 'Riboflavin': 0.2 },
            minerals: { 'Calcium': 110, 'Phosphorus': 135 }
        },
        'oats': { 
            name: 'Oats', 
            caloriesPer100g: 389, 
            protein: 16.9, 
            carbs: 66.3, 
            fats: 6.9, 
            fiber: 10.6,
            sugar: 0.99,
            unit: 'g',
            category: 'grain',
            vitamins: { 'Thiamine': 0.8, 'Folate': 56 },
            minerals: { 'Manganese': 4.9, 'Phosphorus': 523 }
        },
        'salmon': { 
            name: 'Salmon', 
            caloriesPer100g: 208, 
            protein: 25.4, 
            carbs: 0, 
            fats: 12.4, 
            fiber: 0,
            sugar: 0,
            unit: 'g',
            category: 'protein',
            vitamins: { 'Vitamin B12': 3.2, 'Vitamin D': 11 },
            minerals: { 'Selenium': 36.5, 'Phosphorus': 252 }
        },
        'broccoli': { 
            name: 'Broccoli', 
            caloriesPer100g: 34, 
            protein: 2.8, 
            carbs: 6.6, 
            fats: 0.4, 
            fiber: 2.6,
            sugar: 1.5,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin C': 89.2, 'Vitamin K': 101.6 },
            minerals: { 'Potassium': 316, 'Manganese': 0.2 }
        },
        'sweet potato': { 
            name: 'Sweet Potato', 
            caloriesPer100g: 86, 
            protein: 1.6, 
            carbs: 20.1, 
            fats: 0.1, 
            fiber: 3,
            sugar: 4.2,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin A': 14187, 'Vitamin C': 2.4 },
            minerals: { 'Potassium': 337, 'Manganese': 0.3 }
        },
        'avocado': { 
            name: 'Avocado', 
            caloriesPer100g: 160, 
            protein: 2, 
            carbs: 8.5, 
            fats: 14.7, 
            fiber: 6.7,
            sugar: 0.7,
            unit: 'g',
            category: 'fruit',
            vitamins: { 'Vitamin K': 21, 'Folate': 81 },
            minerals: { 'Potassium': 485, 'Manganese': 0.1 }
        },
        'almonds': { 
            name: 'Almonds', 
            caloriesPer100g: 579, 
            protein: 21.2, 
            carbs: 21.6, 
            fats: 49.9, 
            fiber: 12.5,
            sugar: 4.4,
            unit: 'g',
            category: 'nuts',
            vitamins: { 'Vitamin E': 25.6, 'Riboflavin': 1.1 },
            minerals: { 'Magnesium': 270, 'Manganese': 2.3 }
        },
        'olive oil': { 
            name: 'Olive Oil', 
            caloriesPer100ml: 884, 
            protein: 0, 
            carbs: 0, 
            fats: 100, 
            fiber: 0,
            sugar: 0,
            unit: 'ml',
            category: 'fat',
            vitamins: { 'Vitamin E': 14.4, 'Vitamin K': 60.2 },
            minerals: { 'Iron': 0.6, 'Sodium': 2 }
        },
        'pasta': { 
            name: 'Pasta (cooked)', 
            caloriesPer100g: 131, 
            protein: 5, 
            carbs: 25, 
            fats: 1.1, 
            fiber: 1.8,
            sugar: 0.6,
            unit: 'g',
            category: 'grain',
            vitamins: { 'Folate': 18, 'Thiamine': 0.02 },
            minerals: { 'Iron': 1.3, 'Selenium': 26.4 }
        },
        'cheese': { 
            name: 'Cheddar Cheese', 
            caloriesPer100g: 403, 
            protein: 25, 
            carbs: 1.3, 
            fats: 33, 
            fiber: 0,
            sugar: 0.5,
            unit: 'g',
            category: 'dairy',
            vitamins: { 'Vitamin B12': 0.8, 'Vitamin A': 1052 },
            minerals: { 'Calcium': 721, 'Phosphorus': 512 }
        },
        'peanut butter': { 
            name: 'Peanut Butter', 
            caloriesPer100g: 588, 
            protein: 25.1, 
            carbs: 22.3, 
            fats: 50.2, 
            fiber: 8.5,
            sugar: 10.5,
            unit: 'g',
            category: 'nuts',
            vitamins: { 'Niacin': 13.4, 'Vitamin E': 8.3 },
            minerals: { 'Magnesium': 168, 'Manganese': 1.7 }
        },
        'quinoa': { 
            name: 'Quinoa (cooked)', 
            caloriesPer100g: 120, 
            protein: 4.4, 
            carbs: 22, 
            fats: 1.9, 
            fiber: 2.8,
            sugar: 0.9,
            unit: 'g',
            category: 'grain',
            vitamins: { 'Folate': 42, 'Thiamine': 0.1 },
            minerals: { 'Manganese': 0.6, 'Phosphorus': 152 }
        },
        'spinach': { 
            name: 'Spinach', 
            caloriesPer100g: 23, 
            protein: 2.9, 
            carbs: 3.6, 
            fats: 0.4, 
            fiber: 2.2,
            sugar: 0.4,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin K': 483, 'Vitamin A': 469 },
            minerals: { 'Iron': 2.7, 'Manganese': 0.9 }
        },
        'mixed_greens': { 
            name: 'Mixed Greens', 
            caloriesPer100g: 20, 
            protein: 2, 
            carbs: 4, 
            fats: 0.2, 
            fiber: 2,
            sugar: 2,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin K': 200, 'Vitamin A': 200 },
            minerals: { 'Iron': 1, 'Manganese': 0.5 }
        },
        'arugula': { 
            name: 'Arugula', 
            caloriesPer100g: 25, 
            protein: 2.6, 
            carbs: 3.7, 
            fats: 0.7, 
            fiber: 1.6,
            sugar: 2.1,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin K': 109, 'Vitamin A': 119 },
            minerals: { 'Iron': 1.5, 'Manganese': 0.3 }
        },
        'kale': { 
            name: 'Kale', 
            caloriesPer100g: 49, 
            protein: 4.3, 
            carbs: 8.8, 
            fats: 0.9, 
            fiber: 3.6,
            sugar: 2.3,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin K': 817, 'Vitamin A': 999 },
            minerals: { 'Iron': 1.5, 'Manganese': 0.8 }
        },
        'cucumber': { 
            name: 'Cucumber', 
            caloriesPer100g: 16, 
            protein: 0.7, 
            carbs: 4, 
            fats: 0.1, 
            fiber: 0.5,
            sugar: 1.7,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin K': 16, 'Vitamin C': 2.8 },
            minerals: { 'Potassium': 147, 'Manganese': 0.1 }
        },
        'whole_grain_tortilla': { 
            name: 'Whole Grain Tortilla', 
            caloriesPer100g: 150, 
            protein: 4, 
            carbs: 30, 
            fats: 2, 
            fiber: 3,
            sugar: 2,
            unit: 'g',
            category: 'grain',
            vitamins: { 'Folate': 20, 'Thiamine': 0.1 },
            minerals: { 'Iron': 2, 'Selenium': 15 }
        },
        'lettuce_wrap': { 
            name: 'Lettuce Wrap', 
            caloriesPer100g: 5, 
            protein: 0.5, 
            carbs: 1, 
            fats: 0.1, 
            fiber: 0.5,
            sugar: 0.5,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin K': 24, 'Vitamin A': 25 },
            minerals: { 'Iron': 0.2, 'Manganese': 0.1 }
        },
        'pita_bread': { 
            name: 'Whole Wheat Pita', 
            caloriesPer100g: 140, 
            protein: 5, 
            carbs: 28, 
            fats: 1, 
            fiber: 2,
            sugar: 1,
            unit: 'g',
            category: 'grain',
            vitamins: { 'Folate': 15, 'Thiamine': 0.1 },
            minerals: { 'Iron': 1.5, 'Selenium': 12 }
        },
        'black_beans': { 
            name: 'Black Beans (cooked)', 
            caloriesPer100g: 132, 
            protein: 8.9, 
            carbs: 23.7, 
            fats: 0.5, 
            fiber: 8.7,
            sugar: 0.3,
            unit: 'g',
            category: 'legume',
            vitamins: { 'Folate': 256, 'Thiamine': 0.2 },
            minerals: { 'Iron': 2.1, 'Manganese': 0.4 }
        },
        'carrots': { 
            name: 'Carrots', 
            caloriesPer100g: 41, 
            protein: 0.9, 
            carbs: 9.6, 
            fats: 0.2, 
            fiber: 2.8,
            sugar: 4.7,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin A': 16706, 'Vitamin K': 13.2 },
            minerals: { 'Potassium': 320, 'Manganese': 0.1 }
        },
        'butter': { 
            name: 'Butter', 
            caloriesPer100g: 717, 
            protein: 0.9, 
            carbs: 0.1, 
            fats: 81.1, 
            fiber: 0,
            sugar: 0.1,
            unit: 'g',
            category: 'fat',
            vitamins: { 'Vitamin A': 684, 'Vitamin E': 2.3 },
            minerals: { 'Sodium': 11, 'Phosphorus': 24 }
        },
        'asparagus': { 
            name: 'Asparagus', 
            caloriesPer100g: 20, 
            protein: 2.2, 
            carbs: 3.9, 
            fats: 0.1, 
            fiber: 2.1,
            sugar: 1.9,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin K': 41.6, 'Folate': 52 },
            minerals: { 'Potassium': 202, 'Manganese': 0.2 }
        },
        'zucchini': { 
            name: 'Zucchini', 
            caloriesPer100g: 17, 
            protein: 1.2, 
            carbs: 3.1, 
            fats: 0.3, 
            fiber: 1,
            sugar: 2.5,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin C': 17.9, 'Vitamin K': 4.3 },
            minerals: { 'Potassium': 261, 'Manganese': 0.2 }
        },
        'onion': { 
            name: 'Onion', 
            caloriesPer100g: 40, 
            protein: 1.1, 
            carbs: 9.3, 
            fats: 0.1, 
            fiber: 1.7,
            sugar: 4.2,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin C': 7.4, 'Folate': 19 },
            minerals: { 'Potassium': 146, 'Manganese': 0.1 }
        },
        'green_beans': { 
            name: 'Green Beans', 
            caloriesPer100g: 31, 
            protein: 1.8, 
            carbs: 7, 
            fats: 0.2, 
            fiber: 2.7,
            sugar: 3.3,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin K': 43, 'Vitamin C': 12.2 },
            minerals: { 'Manganese': 0.2, 'Potassium': 211 }
        },
        'mushrooms': { 
            name: 'Mushrooms', 
            caloriesPer100g: 22, 
            protein: 3.1, 
            carbs: 3.3, 
            fats: 0.3, 
            fiber: 1,
            sugar: 2,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin D': 0.2, 'Niacin': 3.6 },
            minerals: { 'Selenium': 9.3, 'Potassium': 318 }
        },
        'paneer': { 
            name: 'Paneer (Cottage Cheese)', 
            caloriesPer100g: 98, 
            protein: 11, 
            carbs: 3.4, 
            fats: 4.3, 
            fiber: 0,
            sugar: 3.4,
            unit: 'g',
            category: 'dairy',
            vitamins: { 'Vitamin B12': 0.2, 'Vitamin A': 46 },
            minerals: { 'Calcium': 83, 'Phosphorus': 144 }
        },
        'tuna': { 
            name: 'Tuna (canned)', 
            caloriesPer100g: 132, 
            protein: 28, 
            carbs: 0, 
            fats: 1.3, 
            fiber: 0,
            sugar: 0,
            unit: 'g',
            category: 'protein',
            vitamins: { 'Vitamin B12': 2.5, 'Niacin': 8.5 },
            minerals: { 'Selenium': 36.5, 'Phosphorus': 198 }
        },
        'cherry_tomatoes': { 
            name: 'Cherry Tomatoes', 
            caloriesPer100g: 18, 
            protein: 0.9, 
            carbs: 3.9, 
            fats: 0.2, 
            fiber: 1.2,
            sugar: 2.6,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin C': 13.7, 'Vitamin K': 7.9 },
            minerals: { 'Potassium': 237, 'Manganese': 0.1 }
        },
        'egg_whites': { 
            name: 'Egg Whites', 
            caloriesPer100g: 52, 
            protein: 10.9, 
            carbs: 0.7, 
            fats: 0.2, 
            fiber: 0,
            sugar: 0.7,
            unit: 'g',
            category: 'protein',
            vitamins: { 'Riboflavin': 0.4, 'Niacin': 0.1 },
            minerals: { 'Selenium': 20, 'Sodium': 166 }
        },
        'basmati_rice': { 
            name: 'Basmati Rice (cooked)', 
            caloriesPer100g: 130, 
            protein: 2.7, 
            carbs: 28, 
            fats: 0.3, 
            fiber: 0.4,
            sugar: 0.1,
            unit: 'g',
            category: 'grain',
            vitamins: { 'Thiamine': 0.07, 'Folate': 8 },
            minerals: { 'Manganese': 0.5, 'Selenium': 7.5 }
        },
        'peas': { 
            name: 'Peas', 
            caloriesPer100g: 81, 
            protein: 5.4, 
            carbs: 14.5, 
            fats: 0.4, 
            fiber: 5.1,
            sugar: 5.7,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin K': 24.8, 'Vitamin C': 40 },
            minerals: { 'Manganese': 0.4, 'Phosphorus': 108 }
        },
        'sesame_oil': { 
            name: 'Sesame Oil', 
            caloriesPer100ml: 884, 
            protein: 0, 
            carbs: 0, 
            fats: 100, 
            fiber: 0,
            sugar: 0,
            unit: 'ml',
            category: 'fat',
            vitamins: { 'Vitamin E': 1.4, 'Vitamin K': 13.6 },
            minerals: { 'Iron': 0.1, 'Sodium': 0 }
        },
        'turkey_breast': { 
            name: 'Turkey Breast', 
            caloriesPer100g: 135, 
            protein: 30, 
            carbs: 0, 
            fats: 1, 
            fiber: 0,
            sugar: 0,
            unit: 'g',
            category: 'protein',
            vitamins: { 'Niacin': 8.1, 'Vitamin B6': 0.8 },
            minerals: { 'Selenium': 24.5, 'Phosphorus': 223 }
        },
        'potatoes': { 
            name: 'Potatoes (boiled)', 
            caloriesPer100g: 87, 
            protein: 1.9, 
            carbs: 20.1, 
            fats: 0.1, 
            fiber: 1.8,
            sugar: 0.9,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin C': 13, 'Vitamin B6': 0.2 },
            minerals: { 'Potassium': 379, 'Manganese': 0.1 }
        },
        // Additional foods from nutrition tracker UI
        'eggs': { 
            name: 'Eggs', 
            caloriesPer100g: 155, 
            protein: 13, 
            carbs: 1.1, 
            fats: 11, 
            fiber: 0,
            sugar: 1.1,
            unit: 'g',
            category: 'protein',
            vitamins: { 'Vitamin B12': 0.89, 'Vitamin D': 2.2 },
            minerals: { 'Selenium': 30.7, 'Phosphorus': 198 }
        },
        'greek_yogurt': { 
            name: 'Greek Yogurt', 
            caloriesPer100g: 59, 
            protein: 10, 
            carbs: 3.6, 
            fats: 0.4, 
            fiber: 0,
            sugar: 3.6,
            unit: 'g',
            category: 'dairy',
            vitamins: { 'Vitamin B12': 0.5, 'Riboflavin': 0.2 },
            minerals: { 'Calcium': 110, 'Phosphorus': 135 }
        },
        'protein_powder': { 
            name: 'Protein Powder', 
            caloriesPer100g: 370, 
            protein: 80, 
            carbs: 8, 
            fats: 3, 
            fiber: 0,
            sugar: 2,
            unit: 'g',
            category: 'supplement',
            vitamins: { 'Vitamin B12': 2.4, 'Folate': 50 },
            minerals: { 'Calcium': 200, 'Iron': 2.5 }
        },
        'cottage_cheese': { 
            name: 'Cottage Cheese', 
            caloriesPer100g: 98, 
            protein: 11, 
            carbs: 3.4, 
            fats: 4.3, 
            fiber: 0,
            sugar: 2.7,
            unit: 'g',
            category: 'dairy',
            vitamins: { 'Vitamin B12': 0.4, 'Riboflavin': 0.2 },
            minerals: { 'Calcium': 83, 'Phosphorus': 159 }
        },
        'oatmeal': { 
            name: 'Oatmeal', 
            caloriesPer100g: 68, 
            protein: 2.4, 
            carbs: 12, 
            fats: 1.4, 
            fiber: 1.7,
            sugar: 0.2,
            unit: 'g',
            category: 'grain',
            vitamins: { 'Thiamine': 0.1, 'Folate': 8 },
            minerals: { 'Manganese': 0.7, 'Phosphorus': 77 }
        },
        'whole_grain_bread': { 
            name: 'Whole Grain Bread', 
            caloriesPer100g: 247, 
            protein: 13, 
            carbs: 41, 
            fats: 4.2, 
            fiber: 7,
            sugar: 4.2,
            unit: 'g',
            category: 'grain',
            vitamins: { 'Thiamine': 0.3, 'Folate': 40 },
            minerals: { 'Manganese': 2.5, 'Selenium': 35 }
        },
        'quinoa': { 
            name: 'Quinoa', 
            caloriesPer100g: 120, 
            protein: 4.4, 
            carbs: 22, 
            fats: 1.9, 
            fiber: 2.8,
            sugar: 0.9,
            unit: 'g',
            category: 'grain',
            vitamins: { 'Folate': 42, 'Vitamin E': 0.6 },
            minerals: { 'Manganese': 0.6, 'Phosphorus': 152 }
        },
        'chia_seeds': { 
            name: 'Chia Seeds', 
            caloriesPer100g: 486, 
            protein: 17, 
            carbs: 42, 
            fats: 31, 
            fiber: 34,
            sugar: 0,
            unit: 'g',
            category: 'seed',
            vitamins: { 'Thiamine': 0.6, 'Niacin': 8.8 },
            minerals: { 'Calcium': 631, 'Phosphorus': 860 }
        },
        'peanut_butter': { 
            name: 'Peanut Butter', 
            caloriesPer100g: 588, 
            protein: 25, 
            carbs: 20, 
            fats: 50, 
            fiber: 8.5,
            sugar: 9.2,
            unit: 'g',
            category: 'nut',
            vitamins: { 'Niacin': 13.4, 'Vitamin E': 8.3 },
            minerals: { 'Magnesium': 168, 'Phosphorus': 376 }
        },
        'chicken_breast': { 
            name: 'Chicken Breast', 
            caloriesPer100g: 165, 
            protein: 31, 
            carbs: 0, 
            fats: 3.6, 
            fiber: 0,
            sugar: 0,
            unit: 'g',
            category: 'protein',
            vitamins: { 'Niacin': 14.8, 'Vitamin B6': 1.0 },
            minerals: { 'Phosphorus': 228, 'Selenium': 27.4 }
        },
        'turkey_breast': { 
            name: 'Turkey Breast', 
            caloriesPer100g: 135, 
            protein: 30, 
            carbs: 0, 
            fats: 1, 
            fiber: 0,
            sugar: 0,
            unit: 'g',
            category: 'protein',
            vitamins: { 'Niacin': 8.1, 'Vitamin B6': 0.8 },
            minerals: { 'Selenium': 24.5, 'Phosphorus': 223 }
        },
        'tofu': { 
            name: 'Tofu', 
            caloriesPer100g: 76, 
            protein: 8, 
            carbs: 1.9, 
            fats: 4.8, 
            fiber: 0.3,
            sugar: 0.6,
            unit: 'g',
            category: 'protein',
            vitamins: { 'Folate': 15, 'Vitamin K': 2.4 },
            minerals: { 'Calcium': 350, 'Iron': 5.4 }
        },
        'brown_rice': { 
            name: 'Brown Rice (cooked)', 
            caloriesPer100g: 111, 
            protein: 2.6, 
            carbs: 23, 
            fats: 0.9, 
            fiber: 1.8,
            sugar: 0.4,
            unit: 'g',
            category: 'grain',
            vitamins: { 'Thiamine': 0.1, 'Niacin': 2.3 },
            minerals: { 'Manganese': 1.1, 'Selenium': 9.8 }
        },
        'whole_grain_pasta': { 
            name: 'Whole Grain Pasta', 
            caloriesPer100g: 124, 
            protein: 5, 
            carbs: 25, 
            fats: 1.1, 
            fiber: 3.2,
            sugar: 0.6,
            unit: 'g',
            category: 'grain',
            vitamins: { 'Thiamine': 0.1, 'Folate': 18 },
            minerals: { 'Manganese': 1.3, 'Selenium': 26 }
        },
        'spinach': { 
            name: 'Spinach', 
            caloriesPer100g: 23, 
            protein: 2.9, 
            carbs: 3.6, 
            fats: 0.4, 
            fiber: 2.2,
            sugar: 0.4,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin K': 483, 'Folate': 194 },
            minerals: { 'Iron': 2.7, 'Manganese': 0.9 }
        },
        'bell_peppers': { 
            name: 'Bell Peppers', 
            caloriesPer100g: 31, 
            protein: 1, 
            carbs: 7.3, 
            fats: 0.3, 
            fiber: 2.5,
            sugar: 4.2,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin C': 128, 'Vitamin A': 157 },
            minerals: { 'Potassium': 211, 'Manganese': 0.1 }
        },
        'tomatoes': { 
            name: 'Tomatoes', 
            caloriesPer100g: 18, 
            protein: 0.9, 
            carbs: 3.9, 
            fats: 0.2, 
            fiber: 1.2,
            sugar: 2.6,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin C': 13.7, 'Vitamin K': 7.9 },
            minerals: { 'Potassium': 237, 'Manganese': 0.1 }
        },
        'olive_oil': { 
            name: 'Olive Oil', 
            caloriesPer100ml: 884, 
            protein: 0, 
            carbs: 0, 
            fats: 100, 
            fiber: 0,
            sugar: 0,
            unit: 'ml',
            category: 'fat',
            vitamins: { 'Vitamin E': 14.4, 'Vitamin K': 60.2 },
            minerals: { 'Iron': 0.4, 'Sodium': 2 }
        },
        'walnuts': { 
            name: 'Walnuts', 
            caloriesPer100g: 654, 
            protein: 15, 
            carbs: 14, 
            fats: 65, 
            fiber: 6.7,
            sugar: 2.6,
            unit: 'g',
            category: 'nut',
            vitamins: { 'Folate': 98, 'Vitamin E': 0.7 },
            minerals: { 'Manganese': 3.4, 'Phosphorus': 346 }
        },
        'mixed_greens': { 
            name: 'Mixed Greens', 
            caloriesPer100g: 20, 
            protein: 2, 
            carbs: 4, 
            fats: 0.2, 
            fiber: 2,
            sugar: 2,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin K': 173, 'Vitamin A': 469 },
            minerals: { 'Potassium': 194, 'Manganese': 0.3 }
        },
        'arugula': { 
            name: 'Arugula', 
            caloriesPer100g: 25, 
            protein: 2.6, 
            carbs: 3.7, 
            fats: 0.7, 
            fiber: 1.6,
            sugar: 3.7,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin K': 109, 'Folate': 97 },
            minerals: { 'Calcium': 160, 'Iron': 1.5 }
        },
        'kale': { 
            name: 'Kale', 
            caloriesPer100g: 49, 
            protein: 4.3, 
            carbs: 8.8, 
            fats: 0.9, 
            fiber: 3.6,
            sugar: 2.3,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin K': 817, 'Vitamin C': 120 },
            minerals: { 'Calcium': 150, 'Manganese': 0.8 }
        },
        'cucumber': { 
            name: 'Cucumber', 
            caloriesPer100g: 16, 
            protein: 0.7, 
            carbs: 4, 
            fats: 0.1, 
            fiber: 0.5,
            sugar: 1.7,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin K': 16.4, 'Vitamin C': 2.8 },
            minerals: { 'Potassium': 147, 'Manganese': 0.1 }
        },
        'whole_grain_tortilla': { 
            name: 'Whole Grain Tortilla', 
            caloriesPer100g: 218, 
            protein: 8, 
            carbs: 44, 
            fats: 2.5, 
            fiber: 6,
            sugar: 2,
            unit: 'g',
            category: 'grain',
            vitamins: { 'Thiamine': 0.2, 'Folate': 20 },
            minerals: { 'Manganese': 1.2, 'Selenium': 15 }
        },
        'lettuce_wrap': { 
            name: 'Lettuce Wrap', 
            caloriesPer100g: 5, 
            protein: 0.4, 
            carbs: 1, 
            fats: 0.1, 
            fiber: 0.5,
            sugar: 0.5,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin K': 24, 'Folate': 38 },
            minerals: { 'Potassium': 194, 'Manganese': 0.1 }
        },
        'pita_bread': { 
            name: 'Pita Bread', 
            caloriesPer100g: 275, 
            protein: 9, 
            carbs: 56, 
            fats: 1.2, 
            fiber: 2.2,
            sugar: 1.2,
            unit: 'g',
            category: 'grain',
            vitamins: { 'Thiamine': 0.3, 'Folate': 24 },
            minerals: { 'Manganese': 0.8, 'Selenium': 20 }
        },
        'lean_beef': { 
            name: 'Lean Beef', 
            caloriesPer100g: 250, 
            protein: 26, 
            carbs: 0, 
            fats: 17, 
            fiber: 0,
            sugar: 0,
            unit: 'g',
            category: 'protein',
            vitamins: { 'Vitamin B12': 2.5, 'Niacin': 4.8 },
            minerals: { 'Iron': 2.6, 'Zinc': 4.8 }
        },
        'white_fish': { 
            name: 'White Fish', 
            caloriesPer100g: 96, 
            protein: 20, 
            carbs: 0, 
            fats: 1.5, 
            fiber: 0,
            sugar: 0,
            unit: 'g',
            category: 'protein',
            vitamins: { 'Vitamin B12': 1.2, 'Niacin': 2.1 },
            minerals: { 'Selenium': 36.5, 'Phosphorus': 200 }
        },
        'lentils': { 
            name: 'Lentils', 
            caloriesPer100g: 116, 
            protein: 9, 
            carbs: 20, 
            fats: 0.4, 
            fiber: 7.9,
            sugar: 1.8,
            unit: 'g',
            category: 'legume',
            vitamins: { 'Folate': 181, 'Thiamine': 0.2 },
            minerals: { 'Iron': 3.3, 'Manganese': 0.5 }
        },
        'black_beans': { 
            name: 'Black Beans', 
            caloriesPer100g: 132, 
            protein: 9, 
            carbs: 24, 
            fats: 0.5, 
            fiber: 8.7,
            sugar: 0.3,
            unit: 'g',
            category: 'legume',
            vitamins: { 'Folate': 149, 'Thiamine': 0.2 },
            minerals: { 'Iron': 2.1, 'Manganese': 0.4 }
        },
        'chicken_thigh': { 
            name: 'Chicken Thigh', 
            caloriesPer100g: 209, 
            protein: 18, 
            carbs: 0, 
            fats: 15, 
            fiber: 0,
            sugar: 0,
            unit: 'g',
            category: 'protein',
            vitamins: { 'Niacin': 4.2, 'Vitamin B6': 0.3 },
            minerals: { 'Selenium': 14.2, 'Phosphorus': 155 }
        },
        'wild_rice': { 
            name: 'Wild Rice', 
            caloriesPer100g: 101, 
            protein: 4, 
            carbs: 21, 
            fats: 0.3, 
            fiber: 1.8,
            sugar: 0.7,
            unit: 'g',
            category: 'grain',
            vitamins: { 'Thiamine': 0.1, 'Niacin': 1.3 },
            minerals: { 'Manganese': 0.4, 'Phosphorus': 82 }
        },
        'roasted_potatoes': { 
            name: 'Roasted Potatoes', 
            caloriesPer100g: 93, 
            protein: 2, 
            carbs: 21, 
            fats: 0.1, 
            fiber: 2.2,
            sugar: 1.2,
            unit: 'g',
            category: 'vegetable',
            vitamins: { 'Vitamin C': 9.6, 'Vitamin B6': 0.2 },
            minerals: { 'Potassium': 535, 'Manganese': 0.1 }
        },
        'buckwheat': { 
            name: 'Buckwheat', 
            caloriesPer100g: 343, 
            protein: 13, 
            carbs: 72, 
            fats: 3.4, 
            fiber: 10,
            sugar: 0,
            unit: 'g',
            category: 'grain',
            vitamins: { 'Thiamine': 0.1, 'Niacin': 4.9 },
            minerals: { 'Manganese': 1.3, 'Phosphorus': 347 }
        },
        'flax_seeds': { 
            name: 'Flax Seeds', 
            caloriesPer100g: 534, 
            protein: 18, 
            carbs: 29, 
            fats: 42, 
            fiber: 28,
            sugar: 1.6,
            unit: 'g',
            category: 'seed',
            vitamins: { 'Thiamine': 1.6, 'Folate': 87 },
            minerals: { 'Manganese': 2.5, 'Phosphorus': 642 }
        },
        'protein_bar': { 
            name: 'Protein Bar', 
            caloriesPer100g: 400, 
            protein: 20, 
            carbs: 45, 
            fats: 15, 
            fiber: 5,
            sugar: 20,
            unit: 'g',
            category: 'supplement',
            vitamins: { 'Vitamin B12': 0.5, 'Folate': 25 },
            minerals: { 'Calcium': 100, 'Iron': 2 }
        },
        'nuts_mix': { 
            name: 'Mixed Nuts', 
            caloriesPer100g: 607, 
            protein: 20, 
            carbs: 21, 
            fats: 54, 
            fiber: 7,
            sugar: 4,
            unit: 'g',
            category: 'nut',
            vitamins: { 'Vitamin E': 7.3, 'Folate': 83 },
            minerals: { 'Magnesium': 201, 'Phosphorus': 456 }
        },
        'berries': { 
            name: 'Mixed Berries', 
            caloriesPer100g: 50, 
            protein: 0.7, 
            carbs: 12, 
            fats: 0.3, 
            fiber: 2.4,
            sugar: 8.2,
            unit: 'g',
            category: 'fruit',
            vitamins: { 'Vitamin C': 58.8, 'Vitamin K': 19.8 },
            minerals: { 'Manganese': 0.3, 'Potassium': 77 }
        },
        'dark_chocolate': { 
            name: 'Dark Chocolate', 
            caloriesPer100g: 546, 
            protein: 7.8, 
            carbs: 45.9, 
            fats: 31.3, 
            fiber: 10.9,
            sugar: 24.2,
            unit: 'g',
            category: 'treat',
            vitamins: { 'Riboflavin': 0.1, 'Niacin': 1.1 },
            minerals: { 'Iron': 11.9, 'Magnesium': 228 }
        },
        'hummus': { 
            name: 'Hummus', 
            caloriesPer100g: 166, 
            protein: 8, 
            carbs: 14, 
            fats: 9.6, 
            fiber: 6,
            sugar: 0.3,
            unit: 'g',
            category: 'spread',
            vitamins: { 'Folate': 78, 'Vitamin B6': 0.2 },
            minerals: { 'Iron': 2.4, 'Manganese': 0.6 }
        },
        'rice_cakes': { 
            name: 'Rice Cakes', 
            caloriesPer100g: 387, 
            protein: 8, 
            carbs: 81, 
            fats: 2.8, 
            fiber: 2.4,
            sugar: 0.6,
            unit: 'g',
            category: 'grain',
            vitamins: { 'Thiamine': 0.1, 'Niacin': 1.2 },
            minerals: { 'Manganese': 0.4, 'Selenium': 15 }
        },
        'cheese': { 
            name: 'Cheese', 
            caloriesPer100g: 113, 
            protein: 7, 
            carbs: 1, 
            fats: 9, 
            fiber: 0,
            sugar: 1,
            unit: 'g',
            category: 'dairy',
            vitamins: { 'Vitamin B12': 0.2, 'Riboflavin': 0.1 },
            minerals: { 'Calcium': 200, 'Phosphorus': 100 }
        },
        'orange': { 
            name: 'Orange', 
            caloriesPer100g: 47, 
            protein: 0.9, 
            carbs: 11.8, 
            fats: 0.1, 
            fiber: 2.4,
            sugar: 9.4,
            unit: 'g',
            category: 'fruit',
            vitamins: { 'Vitamin C': 53.2, 'Folate': 30 },
            minerals: { 'Potassium': 181, 'Calcium': 40 }
        },
        'milk': { 
            name: 'Milk', 
            caloriesPer100ml: 42, 
            protein: 3.4, 
            carbs: 5, 
            fats: 1, 
            fiber: 0,
            sugar: 5,
            unit: 'ml',
            category: 'dairy',
            vitamins: { 'Vitamin B12': 0.5, 'Riboflavin': 0.2 },
            minerals: { 'Calcium': 113, 'Phosphorus': 93 }
        }
    };
};

// Initialize enhanced calories calculator
function initializeCaloriesCalculator() {
    initializeFoodDatabase();
    
    const foodSearch = document.getElementById('foodSearch');
    const searchBtn = document.getElementById('searchFoodBtn');
    const addFoodBtn = document.getElementById('addFoodBtn');
    const clearAllBtn = document.getElementById('clearAllBtn');
    const portionSize = document.getElementById('portionSize');
    const portionUnit = document.getElementById('portionUnit');
    const suggestionsDropdown = document.getElementById('foodSuggestions');
    const quickFoodBtns = document.querySelectorAll('.quick-food-btn');
    const portionGuideBtn = document.getElementById('portionGuideBtn');
    const currentMealFoodsEl = document.getElementById('currentMealFoods');
    
    // Set up quick food buttons
    quickFoodBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const foodName = e.target.getAttribute('data-food');
            addQuickFood(foodName);
        });
    });
    
    // Tab switching
    const trackerTab = document.getElementById('trackerTab');
    const bmrTab = document.getElementById('bmrTab');
    const historyTab = document.getElementById('historyTab');
    
    // Food search functionality
    if (foodSearch) {
        foodSearch.addEventListener('input', handleFoodSearch);
    }
    if (searchBtn && foodSearch) {
        searchBtn.addEventListener('click', () => handleFoodSearch({ target: foodSearch }));
    }
    
    // Add food functionality
    if (addFoodBtn) addFoodBtn.addEventListener('click', addFood);
    if (clearAllBtn) clearAllBtn.addEventListener('click', clearAllFoods);
    
    // Quick food buttons
    quickFoodBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const foodName = e.target.getAttribute('data-food');
            selectFood(foodName);
        });
    });
    
    // Portion size change
    if (portionSize) portionSize.addEventListener('input', updateAddButtonState);
    if (portionUnit) portionUnit.addEventListener('change', updateAddButtonState);
    
    // Portion guide
    if (portionGuideBtn) {
        portionGuideBtn.addEventListener('click', () => {
            const modal = new bootstrap.Modal(document.getElementById('portionGuideModal'));
            modal.show();
        });
    }
    
    // Delegated handler for removing foods from the current meal list
    if (currentMealFoodsEl) {
        currentMealFoodsEl.addEventListener('click', (e) => {
            const btn = e.target.closest('.remove-food-btn');
            if (!btn || !currentMealFoodsEl.contains(btn)) return;
            const id = btn.getAttribute('data-id');
            const meal = btn.getAttribute('data-meal') || currentMealType;
            if (id) {
                removeFoodFromMeal(id, meal);
            }
        });
    }
    
    // Tab switching (guarded to avoid breaking other handlers)
    if (trackerTab) trackerTab.addEventListener('click', () => switchTab('tracker'));
    if (bmrTab) bmrTab.addEventListener('click', () => switchTab('bmr'));
    if (historyTab) historyTab.addEventListener('click', () => switchTab('history'));
    
    // Ensure default tab is tracker when available
    if (trackerTab) {
        switchTab('tracker');
    }
    
    // BMR form event listener
    const bmrForm = document.getElementById('bmrForm');
    if (bmrForm) {
        bmrForm.addEventListener('submit', handleBMRCalculation);
    }
    
    // Meal plan tab switching
    const breakfastPlanBtn = document.getElementById('breakfastPlanBtn');
    const lunchPlanBtn = document.getElementById('lunchPlanBtn');
    const dinnerPlanBtn = document.getElementById('dinnerPlanBtn');
    const snacksPlanBtn = document.getElementById('snacksPlanBtn');
    
    if (breakfastPlanBtn) breakfastPlanBtn.addEventListener('click', () => switchMealPlanTab('breakfast'));
    if (lunchPlanBtn) lunchPlanBtn.addEventListener('click', () => switchMealPlanTab('lunch'));
    if (dinnerPlanBtn) dinnerPlanBtn.addEventListener('click', () => switchMealPlanTab('dinner'));
    if (snacksPlanBtn) snacksPlanBtn.addEventListener('click', () => switchMealPlanTab('snacks'));
    
    // Close suggestions when clicking outside
    if (foodSearch && suggestionsDropdown) {
        document.addEventListener('click', (e) => {
            if (!foodSearch.contains(e.target) && !suggestionsDropdown.contains(e.target)) {
                suggestionsDropdown.style.display = 'none';
            }
        });
    }
    
    // Load saved data from localStorage
    loadSavedData();
    
    // Initialize charts
    initializeNutritionCharts();
    
    // Initialize meal options visibility with a small delay to ensure DOM is ready
    setTimeout(() => {
        updateMealOptionsVisibility();
    }, 100);
    
    // Ensure tracker renders at least the empty state on first load
    if (document.getElementById('foodList')) {
        updateCurrentMealDisplay();
        updateDailyTotals();
    }
}

function handleFoodSearch(e) {
    const query = e.target.value.toLowerCase().trim();
    const suggestionsDropdown = document.getElementById('foodSuggestions');
    
    if (query.length < 2) {
        suggestionsDropdown.style.display = 'none';
        return;
    }
    
    // Fetch from database API
    fetch(`../php/food_api.php?action=search&q=${encodeURIComponent(query)}&limit=10`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('API Error:', data.error);
                suggestionsDropdown.style.display = 'none';
                return;
            }
            
            const foods = data.foods || [];
            
            if (foods.length === 0) {
                suggestionsDropdown.style.display = 'none';
                return;
            }
            
            suggestionsDropdown.innerHTML = foods.map(food => `
                <div class="suggestion-item" onclick="selectFoodFromAPI(${food.id}, '${food.name}', ${food.calories_per_100g}, '${food.unit}', '${food.category}')">
                    <div class="fw-bold">${food.name}</div>
                    <small class="text-muted">${food.calories_per_100g} cal per 100${food.unit} • ${food.category}</small>
                </div>
            `).join('');
            
            suggestionsDropdown.style.display = 'block';
        })
        .catch(error => {
            console.error('Error fetching food data:', error);
            suggestionsDropdown.style.display = 'none';
        });
}

function selectFoodFromAPI(foodId, foodName, caloriesPer100g, unit, category) {
    const foodSearch = document.getElementById('foodSearch');
    const suggestionsDropdown = document.getElementById('foodSuggestions');
    
    foodSearch.value = foodName;
    suggestionsDropdown.style.display = 'none';
    
    // Set default portion size based on food type
    const portionSize = document.getElementById('portionSize');
    const portionUnit = document.getElementById('portionUnit');
    
    portionSize.value = 100;
    portionUnit.value = unit;
    
    // Store food data for later use
    window.selectedFoodData = {
        id: foodId,
        name: foodName,
        caloriesPer100g: caloriesPer100g,
        unit: unit,
        category: category
    };
    
    updateAddButtonState();
}

function selectFood(foodKey) {
    const food = foodDatabase[foodKey];
    if (!food) return;
    
    const foodSearch = document.getElementById('foodSearch');
    const suggestionsDropdown = document.getElementById('foodSuggestions');
    
    foodSearch.value = food.name;
    suggestionsDropdown.style.display = 'none';
    
    // Set default portion size based on food type
    const portionSize = document.getElementById('portionSize');
    const portionUnit = document.getElementById('portionUnit');
    
    portionSize.value = 100;
    portionUnit.value = food.unit;
    
    updateAddButtonState();
}

function updateAddButtonState() {
    const foodSearch = document.getElementById('foodSearch');
    const portionSize = document.getElementById('portionSize');
    const addFoodBtn = document.getElementById('addFoodBtn');
    
    const hasFood = foodSearch.value.trim() !== '';
    const hasPortion = portionSize.value && parseFloat(portionSize.value) > 0;
    
    addFoodBtn.disabled = !(hasFood && hasPortion);
}

function addFoodFromAPI(foodData, portionSize, portionUnit) {
    // Fetch complete food data from API
    fetch(`../php/food_api.php?action=get_by_id&id=${foodData.id}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                showNotification('error', 'Error fetching food details: ' + data.error);
                return;
            }
            
            const food = data.food;
            
            // Convert portion to the food's base unit
            let basePortion = portionSize;
            if (portionUnit !== food.unit) {
                basePortion = convertPortion(portionSize, portionUnit, food.unit);
            }
            
            // Calculate detailed nutrition
            const multiplier = basePortion / 100;
            const nutrition = {
                calories: Math.round(food.calories_per_100g * multiplier),
                protein: Math.round(food.protein * multiplier * 10) / 10,
                carbs: Math.round(food.carbs * multiplier * 10) / 10,
                fats: Math.round(food.fats * multiplier * 10) / 10,
                fiber: Math.round(food.fiber * multiplier * 10) / 10,
                sugar: Math.round(food.sugar * multiplier * 10) / 10
            };
            
            // Add to current meal
            const foodItem = {
                id: Date.now(),
                name: food.name,
                portion: portionSize,
                unit: portionUnit,
                calories: nutrition.calories,
                protein: nutrition.protein,
                carbs: nutrition.carbs,
                fats: nutrition.fats,
                fiber: nutrition.fiber,
                sugar: nutrition.sugar,
                category: food.category,
                foodId: food.id // Store database ID for future reference
            };
            
            dailyMeals[currentMealType].push(foodItem);
            updateCurrentMealDisplay();
            updateDailyTotals();
            updateNutritionCharts();
            
            // Clear form
            document.getElementById('foodSearch').value = '';
            document.getElementById('portionSize').value = '';
            document.getElementById('foodSuggestions').style.display = 'none';
            window.selectedFoodData = null;
            
            showNotification('success', `${food.name} added to ${currentMealType}!`);
        })
        .catch(error => {
            console.error('Error fetching food details:', error);
            showNotification('error', 'Error fetching food details. Please try again.');
        });
}

function addFood() {
    const foodSearch = document.getElementById('foodSearch');
    const portionSize = parseFloat(document.getElementById('portionSize').value);
    const portionUnit = document.getElementById('portionUnit').value;
    
    const foodName = foodSearch.value.trim();
    if (!foodName || !portionSize) return;
    
    // Check if we have API food data
    if (window.selectedFoodData && window.selectedFoodData.name.toLowerCase() === foodName.toLowerCase()) {
        addFoodFromAPI(window.selectedFoodData, portionSize, portionUnit);
        return;
    }
    
    // Fallback to local database
    let foodKey = null;
    let food = null;
    
    for (const [key, value] of Object.entries(foodDatabase)) {
        if (value.name.toLowerCase() === foodName.toLowerCase()) {
            foodKey = key;
            food = value;
            break;
        }
    }
    
    if (!food) {
        showNotification('warning', 'Food not found in database. Please try a different food.');
        return;
    }
    
    // Convert portion to the food's base unit
    let basePortion = portionSize;
    if (portionUnit !== food.unit) {
        basePortion = convertPortion(portionSize, portionUnit, food.unit);
    }
    
    // Calculate detailed nutrition
    const multiplier = basePortion / 100;
    const nutrition = {
        calories: Math.round(food.caloriesPer100g * multiplier),
        protein: Math.round(food.protein * multiplier * 10) / 10,
        carbs: Math.round(food.carbs * multiplier * 10) / 10,
        fats: Math.round(food.fats * multiplier * 10) / 10,
        fiber: Math.round(food.fiber * multiplier * 10) / 10,
        sugar: Math.round(food.sugar * multiplier * 10) / 10
    };
    
    // Add to current meal
    const foodItem = {
        id: Date.now(),
        name: food.name,
        portion: portionSize,
        unit: portionUnit,
        calories: nutrition.calories,
        protein: nutrition.protein,
        carbs: nutrition.carbs,
        fats: nutrition.fats,
        fiber: nutrition.fiber,
        sugar: nutrition.sugar,
        baseUnit: food.unit,
        category: food.category,
        mealType: currentMealType,
        timestamp: new Date().toISOString()
    };
    
    dailyMeals[currentMealType].push(foodItem);
    updateCurrentMealDisplay();
    updateDailyTotals();
    saveData();
    
    // Clear form
    foodSearch.value = '';
    document.getElementById('portionSize').value = '';
    updateAddButtonState();
    
    showNotification('success', `${food.name} added to ${currentMealType}!`, 'Food Added');
}

function convertPortion(amount, fromUnit, toUnit) {
    // Simple conversion logic - in a real app, this would be more comprehensive
    const conversions = {
        'g': { 'ml': 1, 'cup': 240, 'tbsp': 15, 'tsp': 5, 'piece': 1 },
        'ml': { 'g': 1, 'cup': 240, 'tbsp': 15, 'tsp': 5, 'piece': 1 },
        'cup': { 'g': 240, 'ml': 240, 'tbsp': 16, 'tsp': 48, 'piece': 1 },
        'tbsp': { 'g': 15, 'ml': 15, 'cup': 0.0625, 'tsp': 3, 'piece': 1 },
        'tsp': { 'g': 5, 'ml': 5, 'cup': 0.0208, 'tbsp': 0.33, 'piece': 1 },
        'piece': { 'g': 1, 'ml': 1, 'cup': 1, 'tbsp': 1, 'tsp': 1 }
    };
    
    if (fromUnit === toUnit) return amount;
    if (!conversions[fromUnit] || !conversions[fromUnit][toUnit]) return amount;
    
    return amount * conversions[fromUnit][toUnit];
}

function renderFoodList() {
    const foodList = document.getElementById('foodList');
    
    if (addedFoods.length === 0) {
        foodList.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-utensils fa-2x mb-2"></i><br>No foods added yet. Start by searching for a food above!</div>';
        return;
    }
    
    foodList.innerHTML = addedFoods.map(food => `
        <div class="food-item">
            <div class="food-info">
                <h6 class="mb-1">${food.name}</h6>
                <small class="text-muted">${food.portion} ${food.unit}</small>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="food-calories">${food.calories} cal</span>
                <button class="remove-food-btn" onclick="removeFood(${food.id})" title="Remove food">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `).join('');
}

function removeFood(foodId) {
    addedFoods = addedFoods.filter(food => food.id !== foodId);
    renderFoodList();
    updateCalorieTotals();
    saveData();
    showNotification('info', 'Food removed from your meal');
}

function clearAllFoods() {
    if (addedFoods.length === 0) return;
    
    if (confirm('Are you sure you want to clear all foods?')) {
        addedFoods = [];
        renderFoodList();
        updateCalorieTotals();
        saveData();
        showNotification('info', 'All foods cleared');
    }
}

function updateCalorieTotals() {
    const totalCalories = addedFoods.reduce((sum, food) => sum + food.calories, 0);
    const foodCount = addedFoods.length;
    
    document.getElementById('totalCalories').textContent = totalCalories;
    document.getElementById('foodCount').textContent = foodCount;
    document.getElementById('consumedCalories').textContent = totalCalories;
    
    // Update progress bar
    const progressPercentage = Math.min((totalCalories / dailyCalorieGoal) * 100, 100);
    document.getElementById('calorieProgress').style.width = `${progressPercentage}%`;
    
    // Update remaining calories
    const remaining = Math.max(dailyCalorieGoal - totalCalories, 0);
    document.getElementById('remainingCalories').textContent = `${remaining} remaining`;
    
    // Change progress bar color based on percentage
    const progressBar = document.getElementById('calorieProgress');
    if (progressPercentage >= 100) {
        progressBar.className = 'progress-bar bg-danger';
    } else if (progressPercentage >= 80) {
        progressBar.className = 'progress-bar bg-warning';
    } else {
        progressBar.className = 'progress-bar bg-primary';
    }
}

function saveData() {
    // Unified persistence for calculator and progress overview
    const snapshotUserProfile = {
        age: userProfile.age,
        gender: userProfile.gender,
        weight: userProfile.weight,
        height: userProfile.height,
        activityLevel: userProfile.activityLevel,
        fitnessGoal: userProfile.fitnessGoal,
        bmr: userProfile.bmr,
        tdee: userProfile.tdee,
        goalCalories: userProfile.goalCalories
    };
    localStorage.setItem('caloriesCalculator', JSON.stringify({
        dailyMeals: dailyMeals,
        dailyCalorieGoal: dailyCalorieGoal,
        dailyMacroGoals: dailyMacroGoals,
        mealHistory: mealHistory,
        userProfile: snapshotUserProfile
    }));
    
    // Save to database
    saveMealsToDatabase();
}

async function saveMealsToDatabase() {
    try {
        const today = new Date().toISOString().split('T')[0];
        const response = await fetch('php/meal_history_api.php?action=save_daily_meals', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                date: today,
                meals: dailyMeals
            })
        });
        
        const result = await response.json();
        if (result.success) {
            console.log('Meals saved to database successfully');
        } else {
            console.error('Failed to save meals to database:', result.message);
        }
    } catch (error) {
        console.error('Error saving meals to database:', error);
    }
}

function loadSavedData() {
    try {
        const saved = localStorage.getItem('caloriesCalculator');
        if (saved) {
            const data = JSON.parse(saved);
            dailyMeals = data.dailyMeals || { breakfast: [], lunch: [], dinner: [], snacks: [] };
            dailyCalorieGoal = data.dailyCalorieGoal || 2000;
            dailyMacroGoals = data.dailyMacroGoals || { protein: 150, carbs: 250, fats: 65 };
            mealHistory = data.mealHistory || [];
            // Ensure user profile reflects loaded goals for progress calculations
            if (typeof userProfile === 'object') {
                userProfile.goalCalories = dailyCalorieGoal;
                if (data.userProfile) {
                    userProfile.age = data.userProfile.age || userProfile.age;
                    userProfile.gender = data.userProfile.gender || userProfile.gender;
                    userProfile.weight = data.userProfile.weight || userProfile.weight;
                    userProfile.height = data.userProfile.height || userProfile.height;
                    userProfile.activityLevel = data.userProfile.activityLevel || userProfile.activityLevel;
                    userProfile.fitnessGoal = data.userProfile.fitnessGoal || userProfile.fitnessGoal;
                    userProfile.bmr = data.userProfile.bmr || userProfile.bmr;
                    userProfile.tdee = data.userProfile.tdee || userProfile.tdee;
                }
            }
            updateCurrentMealDisplay();
            updateDailyTotals();
            // If progress UI exists on page, reveal and refresh it
            const progressSection = document.getElementById('progressSection');
            if (progressSection) {
                progressSection.style.display = 'block';
                if (typeof updateProgressTracking === 'function') {
                    updateProgressTracking();
                }
                // Initialize progress modal charts
                initializeProgressModalCharts();
            }
        }
    } catch (error) {
        console.error('Error loading saved data:', error);
    }
}

// Enhanced functions for detailed tracking
function switchTab(tabName) {
    // Hide all tab contents
    // Only hide immediate tab panes, not nested content using the same class elsewhere
    const tabContainer = document.getElementById('mainTabContent');
    if (tabContainer) {
        tabContainer.querySelectorAll('.tab-pane').forEach(pane => {
            pane.style.display = 'none';
        });
    }
    
    // Remove active class from all tabs
    document.querySelectorAll('[id$="Tab"]').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tabName + 'Content').style.display = 'block';
    document.getElementById(tabName + 'Tab').classList.add('active');
    
    // Load tab-specific data
    if (tabName === 'history') {
        loadMealHistory();
    }
}

function updateCurrentMealDisplay() {
    const foodList = document.getElementById('foodList');
    const currentMealFoods = dailyMeals[currentMealType];
    
    if (currentMealFoods.length === 0) {
        foodList.innerHTML = `
            <div class="empty-meal-state">
                <div class="empty-meal-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="empty-meal-title">No foods added yet</div>
                <div class="empty-meal-description">
                    Click on food cards above to add items to your ${currentMealType} meal.<br>
                    Track your nutrition and reach your daily goals!
                </div>
            </div>
        `;
        return;
    }
    
    foodList.innerHTML = currentMealFoods.map(food => `
        <div class="food-item">
            <div class="meal-type-indicator ${currentMealType}">${currentMealType}</div>
            <div class="food-item-header">
                <div class="food-info">
                    <div class="food-name">${food.name}</div>
                    <div class="food-amount">${food.portion || food.amount}</div>
                </div>
                <div class="food-actions">
                    <div class="food-calories">${food.calories}</div>
                    <button class="remove-food-btn" data-id="${food.id}" data-meal="${currentMealType}" title="Remove food">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="nutrition-grid">
                <div class="nutrition-item protein">
                    <span class="nutrition-label">Protein</span>
                    <span class="nutrition-value">${food.protein}g</span>
                </div>
                <div class="nutrition-item carbs">
                    <span class="nutrition-label">Carbs</span>
                    <span class="nutrition-value">${food.carbs}g</span>
                </div>
                <div class="nutrition-item fats">
                    <span class="nutrition-label">Fats</span>
                    <span class="nutrition-value">${food.fats}g</span>
                </div>
                <div class="nutrition-item fiber">
                    <span class="nutrition-label">Fiber</span>
                    <span class="nutrition-value">${food.fiber || 0}g</span>
                </div>
            </div>
        </div>
    `).join('');
}

function removeFoodFromMeal(foodId, mealType) {
    // Normalize to string to avoid strict type mismatch (e.g., '1' vs 1)
    const targetId = String(foodId);
    dailyMeals[mealType] = dailyMeals[mealType].filter(food => String(food.id) !== targetId);
    updateCurrentMealDisplay();
    updateDailyTotals();
    saveData();
    showNotification('info', 'Food removed from meal');
}

function updateDailyTotals() {
    // Calculate totals for all meals
    const totals = {
        calories: 0,
        protein: 0,
        carbs: 0,
        fats: 0,
        fiber: 0,
        sugar: 0
    };
    
    const mealTotals = {
        breakfast: 0,
        lunch: 0,
        dinner: 0,
        snacks: 0
    };
    
    // Calculate totals
    Object.keys(dailyMeals).forEach(mealType => {
        const mealTotal = dailyMeals[mealType].reduce((sum, food) => {
            sum.calories += food.calories;
            sum.protein += food.protein;
            sum.carbs += food.carbs;
            sum.fats += food.fats;
            sum.fiber += food.fiber;
            sum.sugar += food.sugar;
            return sum;
        }, { calories: 0, protein: 0, carbs: 0, fats: 0, fiber: 0, sugar: 0 });
        
        mealTotals[mealType] = mealTotal.calories;
        
        totals.calories += mealTotal.calories;
        totals.protein += mealTotal.protein;
        totals.carbs += mealTotal.carbs;
        totals.fats += mealTotal.fats;
        totals.fiber += mealTotal.fiber;
        totals.sugar += mealTotal.sugar;
    });
    
    // Update display
    document.getElementById('totalCalories').textContent = Math.round(totals.calories);
    document.getElementById('totalProtein').textContent = Math.round(totals.protein) + 'g';
    document.getElementById('totalCarbs').textContent = Math.round(totals.carbs) + 'g';
    document.getElementById('totalFats').textContent = Math.round(totals.fats) + 'g';
    
    // Update goal progress
    updateGoalProgress(totals);
    
    // Update meal breakdown
    updateMealBreakdown(mealTotals);
}

function updateGoalProgress(totals) {
    // Calories progress
    const calorieProgress = Math.min((totals.calories / dailyCalorieGoal) * 100, 100);
    document.getElementById('calorieProgress').style.width = `${calorieProgress}%`;
    document.getElementById('consumedCalories').textContent = `${Math.round(totals.calories)} / ${dailyCalorieGoal}`;
    
    // Protein progress
    const proteinProgress = Math.min((totals.protein / dailyMacroGoals.protein) * 100, 100);
    document.getElementById('proteinProgress').style.width = `${proteinProgress}%`;
    document.getElementById('consumedProtein').textContent = `${Math.round(totals.protein)} / ${dailyMacroGoals.protein}g`;
    
    // Carbs progress
    const carbsProgress = Math.min((totals.carbs / dailyMacroGoals.carbs) * 100, 100);
    document.getElementById('carbsProgress').style.width = `${carbsProgress}%`;
    document.getElementById('consumedCarbs').textContent = `${Math.round(totals.carbs)} / ${dailyMacroGoals.carbs}g`;
    
    // Fats progress
    const fatsProgress = Math.min((totals.fats / dailyMacroGoals.fats) * 100, 100);
    document.getElementById('fatsProgress').style.width = `${fatsProgress}%`;
    document.getElementById('consumedFats').textContent = `${Math.round(totals.fats)} / ${dailyMacroGoals.fats}g`;
    
    // Change colors based on progress
    updateProgressBarColor('calorieProgress', calorieProgress);
    updateProgressBarColor('proteinProgress', proteinProgress);
    updateProgressBarColor('carbsProgress', carbsProgress);
    updateProgressBarColor('fatsProgress', fatsProgress);
}

function updateProgressBarColor(elementId, percentage) {
    const progressBar = document.getElementById(elementId);
    if (percentage >= 100) {
        progressBar.className = progressBar.className.replace(/bg-\w+/, 'bg-danger');
    } else if (percentage >= 80) {
        progressBar.className = progressBar.className.replace(/bg-\w+/, 'bg-warning');
    } else {
        progressBar.className = progressBar.className.replace(/bg-\w+/, 'bg-primary');
    }
}

function updateMealBreakdown(mealTotals) {
    Object.keys(mealTotals).forEach(mealType => {
        const calories = mealTotals[mealType];
        document.getElementById(`${mealType}Calories`).textContent = `${calories} cal`;
        
        const progressPercentage = Math.min((calories / (dailyCalorieGoal / 4)) * 100, 100);
        document.getElementById(`${mealType}Progress`).style.width = `${progressPercentage}%`;
    });
}

function clearAllFoods() {
    if (dailyMeals[currentMealType].length === 0) return;
    
    if (confirm(`Are you sure you want to clear all foods from ${currentMealType}?`)) {
        dailyMeals[currentMealType] = [];
        updateCurrentMealDisplay();
        updateDailyTotals();
        saveData();
        showNotification('info', `All foods cleared from ${currentMealType}`);
    }
}

function initializeNutritionCharts() {
}


function generateWeeklyData() {
    // Generate realistic weekly calorie data
    const baseCalories = 1800;
    const variation = 300;
    return Array.from({ length: 7 }, () => 
        Math.round(baseCalories + (Math.random() - 0.5) * variation)
    );
}

function loadMealHistory() {
    const historyContainer = document.getElementById('mealHistory');
    
    // Generate sample history data
    const historyData = generateMealHistory();
    
    if (historyData.length === 0) {
        historyContainer.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-history fa-2x mb-2"></i><br>No meal history available yet.</div>';
        return;
    }
    
    historyContainer.innerHTML = historyData.map(day => `
        <div class="history-item">
            <div class="history-header">
                <span class="history-date">${day.date}</span>
                <span class="history-total">${day.totalCalories} cal</span>
            </div>
            <div class="history-foods">
                ${day.foods.map(food => `<span class="history-food">${food}</span>`).join('')}
            </div>
        </div>
    `).join('');
}

function generateMealHistory() {
    // Generate sample meal history for the past week
    const history = [];
    const today = new Date();
    
    for (let i = 6; i >= 0; i--) {
        const date = new Date(today);
        date.setDate(today.getDate() - i);
        
        const sampleFoods = ['Apple', 'Chicken Breast', 'Rice', 'Broccoli', 'Eggs', 'Bread', 'Milk'];
        const dayFoods = sampleFoods.slice(0, Math.floor(Math.random() * 4) + 2);
        const totalCalories = Math.round(1500 + Math.random() * 800);
        
        history.push({
            date: date.toLocaleDateString(),
            foods: dayFoods,
            totalCalories: totalCalories
        });
    }
    
    return history;
}

function saveData() {
    localStorage.setItem('caloriesCalculator', JSON.stringify({
        dailyMeals: dailyMeals,
        dailyCalorieGoal: dailyCalorieGoal,
        dailyMacroGoals: dailyMacroGoals,
        mealHistory: mealHistory
    }));
}


function updateNutritionalBalance(totals) {
    // Calculate balance scores (0-100)
    const proteinBalance = Math.min((totals.protein / dailyMacroGoals.protein) * 100, 100);
    const carbBalance = Math.min((totals.carbs / dailyMacroGoals.carbs) * 100, 100);
    const fatBalance = Math.min((totals.fats / dailyMacroGoals.fats) * 100, 100);
    const overallHealth = (proteinBalance + carbBalance + fatBalance) / 3;
    
    // Update balance bars
    document.getElementById('proteinBalanceBar').style.width = `${proteinBalance}%`;
    document.getElementById('carbBalanceBar').style.width = `${carbBalance}%`;
    document.getElementById('fatBalanceBar').style.width = `${fatBalance}%`;
    document.getElementById('overallHealthBar').style.width = `${overallHealth}%`;
    
    // Update balance scores
    document.getElementById('proteinBalance').textContent = getBalanceScore(proteinBalance);
    document.getElementById('carbBalance').textContent = getBalanceScore(carbBalance);
    document.getElementById('fatBalance').textContent = getBalanceScore(fatBalance);
    document.getElementById('overallHealth').textContent = getBalanceScore(overallHealth);
    
    // Update score colors
    updateBalanceScoreColor('proteinBalance', proteinBalance);
    updateBalanceScoreColor('carbBalance', carbBalance);
    updateBalanceScoreColor('fatBalance', fatBalance);
    updateBalanceScoreColor('overallHealth', overallHealth);
}

function getBalanceScore(percentage) {
    if (percentage >= 90) return 'Excellent';
    if (percentage >= 75) return 'Good';
    if (percentage >= 60) return 'Fair';
    return 'Poor';
}

function updateBalanceScoreColor(elementId, percentage) {
    const element = document.getElementById(elementId);
    element.className = 'balance-score';
    if (percentage >= 90) element.classList.add('good');
    else if (percentage >= 60) element.classList.add('fair');
    else element.classList.add('poor');
}

function updateNutritionalInsights(totals) {
    // Protein insight
    const proteinPercentage = (totals.protein / dailyMacroGoals.protein) * 100;
    let proteinInsight = '';
    if (proteinPercentage >= 90) {
        proteinInsight = 'Excellent protein intake! You\'re meeting your muscle maintenance needs.';
    } else if (proteinPercentage >= 70) {
        proteinInsight = 'Good protein intake. Consider adding lean meats or legumes.';
    } else {
        proteinInsight = 'Low protein intake. Add more chicken, fish, or plant proteins.';
    }
    const proteinInsightEl = document.getElementById('proteinInsight');
    if (proteinInsightEl) {
        proteinInsightEl.textContent = proteinInsight;
    }
    
    // Fiber insight
    const fiberGoal = 25; // Daily fiber goal
    const fiberPercentage = (totals.fiber / fiberGoal) * 100;
    let fiberInsight = '';
    if (fiberPercentage >= 80) {
        fiberInsight = 'Great fiber intake! Keep up the good work with vegetables and whole grains.';
    } else if (fiberPercentage >= 50) {
        fiberInsight = 'Decent fiber intake. Try adding more vegetables and fruits.';
    } else {
        fiberInsight = 'Low fiber intake. Add more vegetables, fruits, and whole grains.';
    }
    const fiberInsightEl = document.getElementById('fiberInsight');
    if (fiberInsightEl) {
        fiberInsightEl.textContent = fiberInsight;
    }
    
    // Hydration insight (static for now)
    const hydrationInsightEl = document.getElementById('hydrationInsight');
    if (hydrationInsightEl) {
        hydrationInsightEl.textContent = 'Remember to drink 8-10 glasses of water daily for optimal health.';
    }
    
    // Trend insight
    const caloriePercentage = (totals.calories / dailyCalorieGoal) * 100;
    let trendInsight = '';
    if (caloriePercentage >= 90 && caloriePercentage <= 110) {
        trendInsight = 'Perfect calorie balance! You\'re maintaining a healthy eating pattern.';
    } else if (caloriePercentage < 90) {
        trendInsight = 'Consider adding healthy snacks to meet your calorie goals.';
    } else {
        trendInsight = 'You\'re exceeding your calorie goals. Consider portion control.';
    }
    const trendInsightEl = document.getElementById('trendInsight');
    if (trendInsightEl) {
        trendInsightEl.textContent = trendInsight;
    }
}

function updateGoalsProgress(totals) {
    // Update goal text
    document.getElementById('calorieGoalText').textContent = `${Math.round(totals.calories)} / ${dailyCalorieGoal}`;
    document.getElementById('proteinGoalText').textContent = `${Math.round(totals.protein)} / ${dailyMacroGoals.protein}g`;
    document.getElementById('carbGoalText').textContent = `${Math.round(totals.carbs)} / ${dailyMacroGoals.carbs}g`;
    document.getElementById('fatGoalText').textContent = `${Math.round(totals.fats)} / ${dailyMacroGoals.fats}g`;
    
    // Update goal bars
    const calorieProgress = Math.min((totals.calories / dailyCalorieGoal) * 100, 100);
    const proteinProgress = Math.min((totals.protein / dailyMacroGoals.protein) * 100, 100);
    const carbsProgress = Math.min((totals.carbs / dailyMacroGoals.carbs) * 100, 100);
    const fatsProgress = Math.min((totals.fats / dailyMacroGoals.fats) * 100, 100);
    
    document.getElementById('calorieGoalBar').style.width = `${calorieProgress}%`;
    document.getElementById('proteinGoalBar').style.width = `${proteinProgress}%`;
    document.getElementById('carbGoalBar').style.width = `${carbsProgress}%`;
    document.getElementById('fatGoalBar').style.width = `${fatsProgress}%`;
}

function updateComparisonCharts(totals) {
    // Update comparison percentages
    const calorieComparison = Math.min((totals.calories / dailyCalorieGoal) * 100, 100);
    const proteinComparison = Math.min((totals.protein / dailyMacroGoals.protein) * 100, 100);
    const carbComparison = Math.min((totals.carbs / dailyMacroGoals.carbs) * 100, 100);
    const fatComparison = Math.min((totals.fats / dailyMacroGoals.fats) * 100, 100);
    
    // Update comparison bars
    document.getElementById('calorieComparison').style.width = `${calorieComparison}%`;
    document.getElementById('proteinComparison').style.width = `${proteinComparison}%`;
    document.getElementById('carbComparison').style.width = `${carbComparison}%`;
    document.getElementById('fatComparison').style.width = `${fatComparison}%`;
    
    // Update comparison text
    document.getElementById('calorieComparisonText').textContent = `${Math.round(calorieComparison)}%`;
    document.getElementById('proteinComparisonText').textContent = `${Math.round(proteinComparison)}%`;
    document.getElementById('carbComparisonText').textContent = `${Math.round(carbComparison)}%`;
    document.getElementById('fatComparisonText').textContent = `${Math.round(fatComparison)}%`;
    
    // Update comparison chart
    const comparisonCtx = document.getElementById('comparisonChart');
    if (comparisonCtx && window.Chart) {
        if (window.comparisonChartInstance) {
            window.comparisonChartInstance.destroy();
        }
        
        window.comparisonChartInstance = new Chart(comparisonCtx, {
            type: 'bar',
            data: {
                labels: ['Calories', 'Protein', 'Carbs', 'Fats'],
                datasets: [{
                    label: 'Current Intake',
                    data: [calorieComparison, proteinComparison, carbComparison, fatComparison],
                    backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#3b82f6'],
                    borderRadius: 8,
                    borderSkipped: false
                }, {
                    label: 'Goal (100%)',
                    data: [100, 100, 100, 100],
                    backgroundColor: 'rgba(0,0,0,0.1)',
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 120,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
}

function updateMicronutrientChart(totals) {
    const microCtx = document.getElementById('microChart');
    if (microCtx && window.Chart) {
        if (window.microChartInstance) {
            window.microChartInstance.destroy();
        }
        
        // Sample micronutrient data (in a real app, this would be calculated from food database)
        const microData = {
            'Vitamin C': 85,
            'Vitamin D': 45,
            'Iron': 78,
            'Calcium': 92,
            'Potassium': 67,
            'Fiber': Math.min((totals.fiber / 25) * 100, 100)
        };
        
        window.microChartInstance = new Chart(microCtx, {
            type: 'horizontalBar',
            data: {
                labels: Object.keys(microData),
                datasets: [{
                    data: Object.values(microData),
                    backgroundColor: [
                        '#ef4444', '#f59e0b', '#10b981', 
                        '#3b82f6', '#8b5cf6', '#ec4899'
                    ],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    y: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
}

// BMR Calculation and Meal Plan Functions
function handleBMRCalculation(e) {
    e.preventDefault();
    
    // Get form data
    const age = parseInt(document.getElementById('age').value);
    const gender = document.getElementById('gender').value;
    const weight = parseFloat(document.getElementById('weight').value);
    const height = parseFloat(document.getElementById('height').value);
    const activityLevel = parseFloat(document.getElementById('activityLevel').value);
    const fitnessGoal = document.getElementById('fitnessGoal').value;
    
    // Store user profile
    userProfile = {
        age, gender, weight, height, activityLevel, fitnessGoal
    };
    
    // Calculate BMR using Mifflin-St Jeor Equation
    let bmr;
    if (gender === 'male') {
        bmr = (10 * weight) + (6.25 * height) - (5 * age) + 5;
    } else {
        bmr = (10 * weight) + (6.25 * height) - (5 * age) - 161;
    }
    
    // Calculate TDEE
    const tdee = bmr * activityLevel;
    
    // Calculate goal calories based on fitness goal
    let goalCalories;
    let goalDescription;
    
    switch (fitnessGoal) {
        case 'cutting':
            goalCalories = Math.round(tdee - 500); // 500 calorie deficit
            goalDescription = `Cutting phase: 500 calorie deficit for ~1lb/week weight loss`;
            break;
        case 'maintenance':
            goalCalories = Math.round(tdee);
            goalDescription = `Maintenance: Maintain current weight`;
            break;
        case 'bulking':
            goalCalories = Math.round(tdee + 300); // 300 calorie surplus
            goalDescription = `Bulking phase: 300 calorie surplus for lean muscle gain`;
            break;
        default:
            goalCalories = Math.round(tdee);
            goalDescription = `Maintenance: Maintain current weight`;
    }
    
    // Update user profile
    userProfile.bmr = Math.round(bmr);
    userProfile.tdee = Math.round(tdee);
    userProfile.goalCalories = goalCalories;
    
    // Update daily goals
    dailyCalorieGoal = goalCalories;
    updateMacroGoals(fitnessGoal, goalCalories);

    // Persist BMR and macro goals to backend
    try {
        const isInPagesDir = window.location.pathname.indexOf('/pages/') !== -1;
        const apiUrl = (isInPagesDir ? '../php/meal_history_api.php' : 'php/meal_history_api.php') + '?action=save_goals';
        fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                bmr: userProfile.bmr,
                tdee: userProfile.tdee,
                goalCalories: userProfile.goalCalories,
                proteinGoal: dailyMacroGoals.protein,
                carbGoal: dailyMacroGoals.carbs,
                fatGoal: dailyMacroGoals.fats,
                fitnessGoal: userProfile.fitnessGoal
            })
        }).catch(() => {});
    } catch (_) {}
    
    // Display results
    displayBMRResults();
    
    // Generate meal plans
    generateMealPlans();
    
    // Show meal plans and progress sections
    document.getElementById('mealPlansSection').style.display = 'block';
    document.getElementById('progressSection').style.display = 'block';
    document.getElementById('tipsSection').style.display = 'block';
    document.getElementById('calorieCalculatorSection').style.display = 'block';
    
    // Update macro balance info
    updateMacroBalanceInfo();
    
    // Update tips and recommendations
    updateNutritionalTips();
    
    // Update progress tracking
    updateProgressTracking();
    
    // Initialize progress modal charts
    initializeProgressModalCharts();
    
    // Initialize calorie calculator
    initializeCalorieCalculator();
    
    // Save data
    saveData();
}

function updateMacroGoals(fitnessGoal, goalCalories) {
    // Calculate macro goals based on fitness goal and calories
    let proteinRatio, carbRatio, fatRatio;
    
    switch (fitnessGoal) {
        case 'cutting':
            proteinRatio = 0.35; // Higher protein for cutting
            carbRatio = 0.35;
            fatRatio = 0.30;
            break;
        case 'bulking':
            proteinRatio = 0.25; // Higher carbs for bulking
            carbRatio = 0.50;
            fatRatio = 0.25;
            break;
        default: // maintenance
            proteinRatio = 0.30;
            carbRatio = 0.40;
            fatRatio = 0.30;
    }
    
    dailyMacroGoals = {
        protein: Math.round((goalCalories * proteinRatio) / 4), // 4 cal/g protein
        carbs: Math.round((goalCalories * carbRatio) / 4), // 4 cal/g carbs
        fats: Math.round((goalCalories * fatRatio) / 9) // 9 cal/g fat
    };
}

function displayBMRResults() {
    // Show results section
    document.getElementById('bmrResults').style.display = 'block';
    document.getElementById('bmrPlaceholder').style.display = 'none';
    
    // Update values
    document.getElementById('bmrValue').textContent = userProfile.bmr;
    document.getElementById('tdeeValue').textContent = userProfile.tdee;
    document.getElementById('goalCalories').textContent = userProfile.goalCalories;
    
    // Update goal description
    let goalDescription = '';
    switch (userProfile.fitnessGoal) {
        case 'cutting':
            goalDescription = `Cutting phase: 500 calorie deficit for ~1lb/week weight loss`;
            break;
        case 'maintenance':
            goalDescription = `Maintenance: Maintain current weight`;
            break;
        case 'bulking':
            goalDescription = `Bulking phase: 300 calorie surplus for lean muscle gain`;
            break;
    }
    document.getElementById('goalDescription').textContent = goalDescription;
    
    // Update macro targets
    document.getElementById('targetProtein').textContent = dailyMacroGoals.protein + 'g';
    document.getElementById('targetCarbs').textContent = dailyMacroGoals.carbs + 'g';
    document.getElementById('targetFats').textContent = dailyMacroGoals.fats + 'g';
    
    // Show BMR report section
    document.getElementById('bmrReportSection').style.display = 'block';
    
    // Generate BMR report
    generateBmrReport();
}

function generateMealPlans() {
    // Generate meal plans based on user profile and goals
    const goalCalories = userProfile.goalCalories;
    const proteinGoal = dailyMacroGoals.protein;
    const carbGoal = dailyMacroGoals.carbs;
    const fatGoal = dailyMacroGoals.fats;
    
    // Calculate meal distribution
    const mealDistribution = {
        breakfast: 0.25, // 25% of daily calories
        lunch: 0.35,     // 35% of daily calories
        dinner: 0.30,    // 30% of daily calories
        snacks: 0.10     // 10% of daily calories
    };
    
    // Generate meal plans for each meal type
    Object.keys(mealDistribution).forEach(mealType => {
        const mealCalories = Math.round(goalCalories * mealDistribution[mealType]);
        const mealProtein = Math.round(proteinGoal * mealDistribution[mealType]);
        const mealCarbs = Math.round(carbGoal * mealDistribution[mealType]);
        const mealFats = Math.round(fatGoal * mealDistribution[mealType]);
        
        mealPlans[userProfile.fitnessGoal][mealType] = generateMealOptions(mealType, mealCalories, mealProtein, mealCarbs, mealFats);
    });
    
    // Display meal plans
    displayMealPlans();
}

function generateMealOptions(mealType, calories, protein, carbs, fats) {
    // Generate 3 different meal options for each meal type
    const mealOptions = [];
    
    // Define meal templates based on fitness goal and meal type
    const templates = getMealTemplates(mealType, userProfile.fitnessGoal);
    
    templates.forEach((template, index) => {
        const meal = {
            name: template.name,
            description: template.description,
            calories: calories,
            protein: protein,
            carbs: carbs,
            fats: fats,
            foods: template.foods.map(food => ({
                name: food.name,
                amount: food.amount,
                calories: Math.round(calories * food.calorieRatio),
                protein: Math.round(protein * food.proteinRatio),
                carbs: Math.round(carbs * food.carbRatio),
                fats: Math.round(fats * food.fatRatio)
            }))
        };
        mealOptions.push(meal);
    });
    
    return mealOptions;
}

function getMealTemplates(mealType, fitnessGoal) {
    const templates = {
        breakfast: {
            cutting: [
                {
                    name: "High Protein Oatmeal",
                    description: "Protein-rich breakfast to fuel your cutting phase",
                    foods: [
                        { name: "Oatmeal", amount: "1 cup", calorieRatio: 0.4, proteinRatio: 0.3, carbRatio: 0.6, fatRatio: 0.2 },
                        { name: "Protein Powder", amount: "1 scoop", calorieRatio: 0.3, proteinRatio: 0.6, carbRatio: 0.1, fatRatio: 0.1 },
                        { name: "Berries", amount: "1/2 cup", calorieRatio: 0.2, proteinRatio: 0.05, carbRatio: 0.25, fatRatio: 0.05 },
                        { name: "Almonds", amount: "10 pieces", calorieRatio: 0.1, proteinRatio: 0.05, carbRatio: 0.05, fatRatio: 0.65 }
                    ]
                },
                {
                    name: "Greek Yogurt Bowl",
                    description: "Low-calorie, high-protein breakfast option",
                    foods: [
                        { name: "Greek Yogurt", amount: "1 cup", calorieRatio: 0.5, proteinRatio: 0.7, carbRatio: 0.2, fatRatio: 0.1 },
                        { name: "Chia Seeds", amount: "1 tbsp", calorieRatio: 0.2, proteinRatio: 0.15, carbRatio: 0.1, fatRatio: 0.6 },
                        { name: "Banana", amount: "1/2 medium", calorieRatio: 0.3, proteinRatio: 0.15, carbRatio: 0.7, fatRatio: 0.3 }
                    ]
                },
                {
                    name: "Egg White Scramble",
                    description: "Lean protein breakfast for cutting",
                    foods: [
                        { name: "Egg Whites", amount: "6 large", calorieRatio: 0.4, proteinRatio: 0.8, carbRatio: 0.05, fatRatio: 0.05 },
                        { name: "Spinach", amount: "1 cup", calorieRatio: 0.1, proteinRatio: 0.1, carbRatio: 0.1, fatRatio: 0.05 },
                        { name: "Avocado", amount: "1/4 medium", calorieRatio: 0.3, proteinRatio: 0.05, carbRatio: 0.1, fatRatio: 0.8 },
                        { name: "Whole Wheat Toast", amount: "1 slice", calorieRatio: 0.2, proteinRatio: 0.05, carbRatio: 0.75, fatRatio: 0.1 }
                    ]
                }
            ],
            bulking: [
                {
                    name: "Power Pancakes",
                    description: "High-calorie breakfast for muscle building",
                    foods: [
                        { name: "Pancake Mix", amount: "1 cup", calorieRatio: 0.4, proteinRatio: 0.2, carbRatio: 0.6, fatRatio: 0.1 },
                        { name: "Whole Eggs", amount: "3 large", calorieRatio: 0.3, proteinRatio: 0.4, carbRatio: 0.05, fatRatio: 0.6 },
                        { name: "Banana", amount: "1 medium", calorieRatio: 0.2, proteinRatio: 0.1, carbRatio: 0.3, fatRatio: 0.05 },
                        { name: "Honey", amount: "2 tbsp", calorieRatio: 0.1, proteinRatio: 0.0, carbRatio: 0.05, fatRatio: 0.0 }
                    ]
                },
                {
                    name: "Protein Smoothie Bowl",
                    description: "Nutrient-dense breakfast for bulking",
                    foods: [
                        { name: "Protein Powder", amount: "2 scoops", calorieRatio: 0.3, proteinRatio: 0.6, carbRatio: 0.1, fatRatio: 0.1 },
                        { name: "Oats", amount: "1/2 cup", calorieRatio: 0.3, proteinRatio: 0.2, carbRatio: 0.5, fatRatio: 0.2 },
                        { name: "Peanut Butter", amount: "2 tbsp", calorieRatio: 0.3, proteinRatio: 0.15, carbRatio: 0.1, fatRatio: 0.6 },
                        { name: "Milk", amount: "1 cup", calorieRatio: 0.1, proteinRatio: 0.05, carbRatio: 0.3, fatRatio: 0.1 }
                    ]
                },
                {
                    name: "Breakfast Burrito",
                    description: "Hearty breakfast for muscle building",
                    foods: [
                        { name: "Whole Wheat Tortilla", amount: "1 large", calorieRatio: 0.3, proteinRatio: 0.1, carbRatio: 0.7, fatRatio: 0.1 },
                        { name: "Scrambled Eggs", amount: "3 large", calorieRatio: 0.3, proteinRatio: 0.4, carbRatio: 0.05, fatRatio: 0.6 },
                        { name: "Black Beans", amount: "1/2 cup", calorieRatio: 0.2, proteinRatio: 0.3, carbRatio: 0.4, fatRatio: 0.05 },
                        { name: "Cheese", amount: "1 oz", calorieRatio: 0.2, proteinRatio: 0.2, carbRatio: 0.0, fatRatio: 0.25 }
                    ]
                }
            ]
        },
        lunch: {
            cutting: [
                {
                    name: "Grilled Chicken Salad",
                    description: "Low-calorie, high-protein lunch",
                    foods: [
                        { name: "Grilled Chicken Breast", amount: "6 oz", calorieRatio: 0.5, proteinRatio: 0.7, carbRatio: 0.0, fatRatio: 0.3 },
                        { name: "Mixed Greens", amount: "2 cups", calorieRatio: 0.1, proteinRatio: 0.1, carbRatio: 0.2, fatRatio: 0.05 },
                        { name: "Cucumber", amount: "1/2 cup", calorieRatio: 0.05, proteinRatio: 0.05, carbRatio: 0.1, fatRatio: 0.0 },
                        { name: "Olive Oil Dressing", amount: "1 tbsp", calorieRatio: 0.35, proteinRatio: 0.0, carbRatio: 0.0, fatRatio: 0.65 }
                    ]
                }
            ],
            bulking: [
                {
                    name: "Chicken Rice Bowl",
                    description: "High-calorie lunch for muscle building",
                    foods: [
                        { name: "Grilled Chicken Thigh", amount: "8 oz", calorieRatio: 0.4, proteinRatio: 0.5, carbRatio: 0.0, fatRatio: 0.6 },
                        { name: "Brown Rice", amount: "1.5 cups", calorieRatio: 0.4, proteinRatio: 0.1, carbRatio: 0.8, fatRatio: 0.1 },
                        { name: "Broccoli", amount: "1 cup", calorieRatio: 0.1, proteinRatio: 0.2, carbRatio: 0.15, fatRatio: 0.05 },
                        { name: "Olive Oil", amount: "1 tbsp", calorieRatio: 0.1, proteinRatio: 0.0, carbRatio: 0.0, fatRatio: 0.25 }
                    ]
                }
            ]
        }
    };
    
    // Add maintenance templates (mix of cutting and bulking)
    templates.breakfast.maintenance = [...templates.breakfast.cutting, ...templates.breakfast.bulking].slice(0, 3);
    templates.lunch.maintenance = [...templates.lunch.cutting, ...templates.lunch.bulking].slice(0, 3);
    
    return templates[mealType]?.[fitnessGoal] || templates[mealType]?.maintenance || [];
}

function displayMealPlans() {
    const mealTypes = ['breakfast', 'lunch', 'dinner', 'snacks'];
    
    mealTypes.forEach(mealType => {
        const container = document.getElementById(`${mealType}Meals`);
        if (!container) return;
        
        const plans = mealPlans[userProfile.fitnessGoal]?.[mealType] || [];
        
        container.innerHTML = '';
        
        plans.forEach((plan, index) => {
            const planCard = createMealPlanCard(plan, mealType);
            container.appendChild(planCard);
        });
    });
}

function createMealPlanCard(plan, mealType) {
    const col = document.createElement('div');
    col.className = 'col-lg-4 col-md-6 mb-4';
    
    col.innerHTML = `
        <div class="meal-plan-card">
            <div class="meal-plan-header">
                <h6 class="meal-plan-title">${plan.name}</h6>
                <span class="meal-plan-calories">${plan.calories} cal</span>
            </div>
            <p class="meal-plan-description">${plan.description}</p>
            
            <div class="meal-plan-macros">
                <div class="macro-item">
                    <h6>${plan.protein}g</h6>
                    <p>Protein</p>
                </div>
                <div class="macro-item">
                    <h6>${plan.carbs}g</h6>
                    <p>Carbs</p>
                </div>
                <div class="macro-item">
                    <h6>${plan.fats}g</h6>
                    <p>Fats</p>
                </div>
            </div>
            
            <div class="meal-plan-foods">
                ${plan.foods.map(food => `
                    <div class="food-item">
                        <span class="food-name">${food.name}</span>
                        <span class="food-amount">${food.amount}</span>
                    </div>
                `).join('')}
            </div>
            
            <button class="add-to-tracker-btn" onclick="addMealPlanToTracker('${mealType}', ${JSON.stringify(plan).replace(/"/g, '&quot;')})">
                <i class="fas fa-plus me-2"></i>Add to Tracker
            </button>
        </div>
    `;
    
    return col;
}

function addMealPlanToTracker(mealType, plan) {
    // Add all foods from the meal plan to the current meal
    plan.foods.forEach(food => {
        const foodData = {
            id: Date.now() + Math.random(),
            name: food.name,
            amount: food.amount,
            calories: food.calories,
            protein: food.protein,
            carbs: food.carbs,
            fats: food.fats,
            fiber: 0, // Default values for missing data
            sugar: 0
        };
        
        dailyMeals[mealType].push(foodData);
    });
    
    // Update displays
    updateCurrentMealDisplay();
    updateDailyTotals();
    
    // Show success message
    showNotification('Meal plan added to tracker!', 'success');
}

function switchMealPlanTab(mealType) {
    // Update button states
    document.querySelectorAll('[id$="PlanBtn"]').forEach(btn => {
        btn.classList.remove('active');
    });
    document.getElementById(`${mealType}PlanBtn`).classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.meal-plan-tab').forEach(tab => {
        tab.style.display = 'none';
    });
    document.getElementById(`${mealType}Plan`).style.display = 'block';
}

function switchTab(tabName) {
    // Update button states
    document.querySelectorAll('[id$="Tab"]').forEach(btn => {
        btn.classList.remove('active');
    });
    document.getElementById(`${tabName}Tab`).classList.add('active');
    
    // Update tab content
    const tabContainer2 = document.getElementById('mainTabContent');
    if (tabContainer2) {
        tabContainer2.querySelectorAll('.tab-pane').forEach(pane => {
            pane.style.display = 'none';
        });
    }
    document.getElementById(`${tabName}Content`).style.display = 'block';
    
    // Load specific content based on tab
    if (tabName === 'history') {
        loadMealHistory();
    } else if (tabName === 'bmr') {
        // Load BMR specific content if needed
        if (userProfile.bmr > 0) {
            updateProgressTracking();
        }
    }
}

// Enhanced BMR Functions
function updateMacroBalanceInfo() {
    const macroBalanceInfo = document.getElementById('macroBalanceInfo');
    if (!macroBalanceInfo) return;
    
    let balanceText = '';
    switch (userProfile.fitnessGoal) {
        case 'cutting':
            balanceText = 'Higher protein (35%) for muscle preservation during cutting';
            break;
        case 'bulking':
            balanceText = 'Higher carbs (50%) for energy and muscle building';
            break;
        case 'maintenance':
            balanceText = 'Balanced macros (30% protein, 40% carbs, 30% fats)';
            break;
        default:
            balanceText = 'Balanced macros for your goal';
    }
    
    macroBalanceInfo.textContent = balanceText;
}

function updateNutritionalTips() {
    // Update food tips
    const foodTips = document.getElementById('foodTips');
    const timingTips = document.getElementById('timingTips');
    const supplementTips = document.getElementById('supplementTips');
    
    if (!foodTips || !timingTips || !supplementTips) return;
    
    let foodTip, timingTip, supplementTip;
    
    switch (userProfile.fitnessGoal) {
        case 'cutting':
            foodTip = 'Focus on lean proteins, vegetables, and complex carbs. Avoid processed foods and sugary drinks.';
            timingTip = 'Eat smaller, more frequent meals. Have protein with every meal to maintain muscle mass.';
            supplementTip = 'Consider whey protein, BCAAs, and multivitamins. Green tea extract may help with fat loss.';
            break;
        case 'bulking':
            foodTip = 'Include healthy fats, complex carbs, and quality proteins. Don\'t neglect vegetables for micronutrients.';
            timingTip = 'Eat larger meals with carbs around workouts. Have a post-workout meal within 2 hours.';
            supplementTip = 'Whey protein, creatine, and beta-alanine can support muscle building. Consider mass gainers if needed.';
            break;
        case 'maintenance':
            foodTip = 'Maintain a balanced diet with all food groups. Focus on whole foods and proper portion control.';
            timingTip = 'Eat regular meals every 3-4 hours. Listen to your hunger cues and eat when hungry.';
            supplementTip = 'A good multivitamin and omega-3 supplements can support overall health.';
            break;
        default:
            foodTip = 'Choose nutrient-dense foods for your goal';
            timingTip = 'Optimize your meal timing for better results';
            supplementTip = 'Consider these supplements for your goal';
    }
    
    foodTips.textContent = foodTip;
    timingTips.textContent = timingTip;
    supplementTips.textContent = supplementTip;
}


function updateProgressTracking() {
    // Update progress text
    const weightProgressText = document.getElementById('weightProgressText');
    const calorieProgressText = document.getElementById('calorieProgressText');
    const weeklyProgressText = document.getElementById('weeklyProgressText');
    const proteinProgressText = document.getElementById('proteinProgressText');
    
    const currentPhase = document.getElementById('currentPhase');
    const expectedTimeline = document.getElementById('expectedTimeline');
    const nextMilestone = document.getElementById('nextMilestone');
    const goalTips = document.getElementById('goalTips');
    
    if (!weightProgressText || !currentPhase) return;
    
    // Calculate current totals
    const totals = {
        calories: 0,
        protein: 0,
        carbs: 0,
        fats: 0
    };
    
    Object.keys(dailyMeals).forEach(mealType => {
        const mealTotal = dailyMeals[mealType].reduce((sum, food) => {
            sum.calories += food.calories;
            sum.protein += food.protein;
            sum.carbs += food.carbs;
            sum.fats += food.fats;
            return sum;
        }, { calories: 0, protein: 0, carbs: 0, fats: 0 });
        
        totals.calories += mealTotal.calories;
        totals.protein += mealTotal.protein;
        totals.carbs += mealTotal.carbs;
        totals.fats += mealTotal.fats;
    });
    
    // Update progress displays
    const calorieDeficit = userProfile.goalCalories - totals.calories;
    const proteinProgress = Math.min((totals.protein / dailyMacroGoals.protein) * 100, 100);
    
    weightProgressText.textContent = `${userProfile.weight} / ${userProfile.weight} kg`;
    calorieProgressText.textContent = `${calorieDeficit > 0 ? '-' : '+'}${Math.abs(calorieDeficit)} cal`;
    weeklyProgressText.textContent = `${Math.round(proteinProgress)}%`;
    proteinProgressText.textContent = `${Math.round(totals.protein)} / ${dailyMacroGoals.protein}g`;
    
    // Update progress bars
    const calorieProgressBar = document.getElementById('calorieProgressBar');
    const proteinProgressBar = document.getElementById('proteinProgressBar');
    const weeklyProgressBar = document.getElementById('weeklyProgressBar');
    
    if (calorieProgressBar) {
        const calorieProgress = Math.min((totals.calories / userProfile.goalCalories) * 100, 100);
        calorieProgressBar.style.width = `${calorieProgress}%`;
    }
    
    if (proteinProgressBar) {
        proteinProgressBar.style.width = `${proteinProgress}%`;
    }
    
    if (weeklyProgressBar) {
        weeklyProgressBar.style.width = `${proteinProgress}%`;
    }
    
    // Update timeline and tips
    let phaseText, timelineText, milestoneText, tipsText;
    
    switch (userProfile.fitnessGoal) {
        case 'cutting':
            phaseText = 'Cutting Phase - Weight Loss';
            timelineText = '8-12 weeks for significant results';
            milestoneText = 'First 5lbs lost';
            tipsText = 'Stay consistent with your calorie deficit. Don\'t cut too aggressively.';
            break;
        case 'bulking':
            phaseText = 'Bulking Phase - Muscle Building';
            timelineText = '12-16 weeks for lean gains';
            milestoneText = 'First 5lbs gained';
            tipsText = 'Focus on progressive overload in training. Eat enough protein.';
            break;
        case 'maintenance':
            phaseText = 'Maintenance Phase';
            timelineText = 'Ongoing lifestyle approach';
            milestoneText = 'Maintain current weight';
            tipsText = 'Focus on sustainable habits. Monitor your progress regularly.';
            break;
        default:
            phaseText = 'Calculate BMR first';
            timelineText = '-';
            milestoneText = '-';
            tipsText = 'Calculate your BMR to get personalized tips';
    }
    
    currentPhase.textContent = phaseText;
    expectedTimeline.textContent = timelineText;
    nextMilestone.textContent = milestoneText;
    goalTips.textContent = tipsText;
}

function initializeProgressModalCharts() {
    console.log('Initializing progress charts...');
    
    // Initialize main dashboard progress chart
    const progressCtx = document.getElementById('progressChart');
    console.log('Main progress chart canvas found:', !!progressCtx);
    console.log('Chart.js available:', !!window.Chart);
    
    if (progressCtx && window.Chart) {
        // Create time period selector for main dashboard
        createTimePeriodSelector();
    } else {
        console.error('Cannot initialize main progress chart - missing canvas or Chart.js');
    }
    
    // Also initialize modal charts if they exist
    const weeklyCtx = document.getElementById('weeklyProgressChart');
    if (weeklyCtx && window.Chart) {
        // Destroy existing chart if it exists
        if (window.weeklyProgressChartInstance) {
            window.weeklyProgressChartInstance.destroy();
        }
        
        // Initialize with weekly data
        updateProgressChart('week');
    }
    
    // Initialize macro distribution chart
    const macroCtx = document.getElementById('macroProgressChart');
    if (macroCtx && window.Chart) {
        // Destroy existing chart if it exists
        if (window.macroProgressChartInstance) {
            window.macroProgressChartInstance.destroy();
        }
        
        // Create macro period selector
        createMacroPeriodSelector();
        
        // Initialize with current day data
        updateMacroChart('day');
    }
    
    // Update progress insights
    const insightsEl = document.getElementById('progressInsights');
    if (insightsEl && userProfile.fitnessGoal) {
        // Calculate current totals
        const currentTotals = {
            calories: 0,
            protein: 0,
            carbs: 0,
            fats: 0
        };
        
        Object.keys(dailyMeals).forEach(mealType => {
            const mealTotal = dailyMeals[mealType].reduce((sum, food) => {
                sum.calories += food.calories;
                sum.protein += food.protein;
                sum.carbs += food.carbs;
                sum.fats += food.fats;
                return sum;
            }, { calories: 0, protein: 0, carbs: 0, fats: 0 });
            
            currentTotals.calories += mealTotal.calories;
            currentTotals.protein += mealTotal.protein;
            currentTotals.carbs += mealTotal.carbs;
            currentTotals.fats += mealTotal.fats;
        });
        
        // Calculate projected weekly/monthly/yearly totals
        const weeklyProjected = {
            calories: Math.round(currentTotals.calories * 7),
            protein: Math.round(currentTotals.protein * 7),
            carbs: Math.round(currentTotals.carbs * 7),
            fats: Math.round(currentTotals.fats * 7)
        };
        
        const monthlyProjected = {
            calories: Math.round(currentTotals.calories * 30),
            protein: Math.round(currentTotals.protein * 30),
            carbs: Math.round(currentTotals.carbs * 30),
            fats: Math.round(currentTotals.fats * 30)
        };
        
        const yearlyProjected = {
            calories: Math.round(currentTotals.calories * 365),
            protein: Math.round(currentTotals.protein * 365),
            carbs: Math.round(currentTotals.carbs * 365),
            fats: Math.round(currentTotals.fats * 365)
        };
        
        let insights = '';
        switch (userProfile.fitnessGoal) {
            case 'cutting':
                insights = `
                    <div class="insight-item mb-3">
                        <h6><i class="fas fa-calendar-day text-primary me-2"></i>Today's Progress</h6>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Calories:</small><br>
                                <strong>${currentTotals.calories} / ${userProfile.goalCalories}</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Protein:</small><br>
                                <strong>${Math.round(currentTotals.protein)}g / ${dailyMacroGoals.protein}g</strong>
                            </div>
                        </div>
                    </div>
                    <div class="insight-item mb-3">
                        <h6><i class="fas fa-calendar-week text-success me-2"></i>Weekly Projection</h6>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Calories:</small><br>
                                <strong>${weeklyProjected.calories.toLocaleString()}</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Protein:</small><br>
                                <strong>${weeklyProjected.protein}g</strong>
                            </div>
                        </div>
                    </div>
                    <div class="insight-item mb-3">
                        <h6><i class="fas fa-calendar-alt text-warning me-2"></i>Monthly Projection</h6>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Calories:</small><br>
                                <strong>${monthlyProjected.calories.toLocaleString()}</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Protein:</small><br>
                                <strong>${monthlyProjected.protein}g</strong>
                            </div>
                        </div>
                    </div>
                    <div class="insight-item">
                        <h6><i class="fas fa-calendar text-info me-2"></i>Yearly Projection</h6>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Calories:</small><br>
                                <strong>${yearlyProjected.calories.toLocaleString()}</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Protein:</small><br>
                                <strong>${yearlyProjected.protein}g</strong>
                            </div>
                        </div>
                    </div>
                `;
                break;
            case 'bulking':
                insights = `
                    <div class="insight-item mb-3">
                        <h6><i class="fas fa-calendar-day text-primary me-2"></i>Today's Progress</h6>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Calories:</small><br>
                                <strong>${currentTotals.calories} / ${userProfile.goalCalories}</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Protein:</small><br>
                                <strong>${Math.round(currentTotals.protein)}g / ${dailyMacroGoals.protein}g</strong>
                            </div>
                        </div>
                    </div>
                    <div class="insight-item mb-3">
                        <h6><i class="fas fa-calendar-week text-success me-2"></i>Weekly Projection</h6>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Calories:</small><br>
                                <strong>${weeklyProjected.calories.toLocaleString()}</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Protein:</small><br>
                                <strong>${weeklyProjected.protein}g</strong>
                            </div>
                        </div>
                    </div>
                    <div class="insight-item mb-3">
                        <h6><i class="fas fa-calendar-alt text-warning me-2"></i>Monthly Projection</h6>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Calories:</small><br>
                                <strong>${monthlyProjected.calories.toLocaleString()}</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Protein:</small><br>
                                <strong>${monthlyProjected.protein}g</strong>
                            </div>
                        </div>
                    </div>
                    <div class="insight-item">
                        <h6><i class="fas fa-calendar text-info me-2"></i>Yearly Projection</h6>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Calories:</small><br>
                                <strong>${yearlyProjected.calories.toLocaleString()}</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Protein:</small><br>
                                <strong>${yearlyProjected.protein}g</strong>
                            </div>
                        </div>
                    </div>
                `;
                break;
            case 'maintenance':
                insights = `
                    <div class="insight-item mb-3">
                        <h6><i class="fas fa-calendar-day text-primary me-2"></i>Today's Progress</h6>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Calories:</small><br>
                                <strong>${currentTotals.calories} / ${userProfile.goalCalories}</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Protein:</small><br>
                                <strong>${Math.round(currentTotals.protein)}g / ${dailyMacroGoals.protein}g</strong>
                            </div>
                        </div>
                    </div>
                    <div class="insight-item mb-3">
                        <h6><i class="fas fa-calendar-week text-success me-2"></i>Weekly Projection</h6>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Calories:</small><br>
                                <strong>${weeklyProjected.calories.toLocaleString()}</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Protein:</small><br>
                                <strong>${weeklyProjected.protein}g</strong>
                            </div>
                        </div>
                    </div>
                    <div class="insight-item mb-3">
                        <h6><i class="fas fa-calendar-alt text-warning me-2"></i>Monthly Projection</h6>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Calories:</small><br>
                                <strong>${monthlyProjected.calories.toLocaleString()}</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Protein:</small><br>
                                <strong>${monthlyProjected.protein}g</strong>
                            </div>
                        </div>
                    </div>
                    <div class="insight-item">
                        <h6><i class="fas fa-calendar text-info me-2"></i>Yearly Projection</h6>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Calories:</small><br>
                                <strong>${yearlyProjected.calories.toLocaleString()}</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Protein:</small><br>
                                <strong>${yearlyProjected.protein}g</strong>
                            </div>
                        </div>
                    </div>
                `;
                break;
        }
        insightsEl.innerHTML = insights;
    }
}

function createTimePeriodSelector() {
    // Use the existing main dashboard progress chart buttons
    const existingButtons = document.querySelectorAll('.chart-container .btn-group .btn');
    if (existingButtons.length >= 3) {
        console.log('Found existing progress chart buttons, adding event listeners');
        
        // Add event listeners to existing buttons
        existingButtons.forEach((btn, index) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Remove active class from all buttons
                existingButtons.forEach(b => b.classList.remove('active'));
                
                // Add active class to clicked button
                btn.classList.add('active');
                
                // Update chart based on button text
                const period = btn.textContent.toLowerCase();
                console.log('Main dashboard period changed to:', period);
                updateMainProgressChart(period);
            });
        });
        
        // Initialize with week data
        updateMainProgressChart('week');
    } else {
        console.log('No existing progress chart buttons found');
    }
}

async function updateMainProgressChart(period) {
    console.log('Updating main dashboard progress chart for period:', period);
    const progressCtx = document.getElementById('progressChart');
    console.log('Main progress canvas element:', progressCtx);
    
    if (!progressCtx || !window.Chart) {
        console.error('Missing main progress canvas or Chart.js');
        return;
    }
    
    // Destroy existing chart if it exists
    if (window.mainProgressChartInstance) {
        console.log('Destroying existing main progress chart...');
        window.mainProgressChartInstance.destroy();
        window.mainProgressChartInstance = null;
    }
    
    // Also destroy any other chart instances that might be using this canvas
    if (window.Chart && window.Chart.instances) {
        Object.keys(window.Chart.instances).forEach(key => {
            const chart = window.Chart.instances[key];
            if (chart && chart.canvas && chart.canvas.id === 'progressChart') {
                console.log('Destroying chart instance with ID:', key);
                chart.destroy();
            }
        });
    }
    
    try {
        // Get real historical data from database
        console.log('Fetching historical data for period:', period);
        const response = await fetch(`../php/meal_history_api.php?action=get_historical_data&period=${period}`);
        const result = await response.json();
        console.log('API response:', result);
        
        if (result.success) {
            const { labels, data, period: dataPeriod } = result.data;
            
            // Check if we have meaningful data (not all zeros)
            const hasData = data.some(value => value > 0);
            console.log('Data values:', data);
            console.log('Has meaningful data:', hasData);
            
            if (hasData) {
                let title = '';
                
                switch (dataPeriod) {
                    case 'week':
                        title = 'Daily Calories (Last 7 Days)';
                        break;
                    case 'month':
                        title = 'Weekly Calories (Last 4 Weeks)';
                        break;
                    case 'year':
                        title = 'Monthly Calories (Last 12 Months)';
                        break;
                }
                
                console.log('Real historical data for', period, ':', { labels, data, title });
                
                window.mainProgressChartInstance = new Chart(progressCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Calories',
                            data: data,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#6366f1',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        aspectRatio: 2.5,
                        plugins: {
                            legend: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: title,
                                font: {
                                    size: 16,
                                    weight: 'bold'
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: false,
                                min: Math.max(0, Math.min(...data) - 200),
                                max: Math.max(...data) + 200,
                                grid: {
                                    color: 'rgba(0,0,0,0.1)'
                                },
                                title: {
                                    display: true,
                                    text: 'Calories'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
                
                console.log('Main progress chart created successfully with real data:', !!window.mainProgressChartInstance);
            } else {
                console.log('No meaningful historical data found, using fallback');
                // Fallback to simulated data
                updateMainProgressChartFallback(period);
            }
        } else {
            console.error('Failed to get historical data:', result.message);
            // Fallback to simulated data
            updateMainProgressChartFallback(period);
        }
    } catch (error) {
        console.error('Error fetching historical data:', error);
        // Fallback to simulated data
        updateMainProgressChartFallback(period);
    }
}

function updateMainProgressChartFallback(period) {
    console.log('Using fallback simulated data for period:', period);
    const progressCtx = document.getElementById('progressChart');
    
    if (!progressCtx || !window.Chart) {
        console.error('Missing canvas or Chart.js for fallback');
        return;
    }
    
    // Destroy existing chart if it exists
    if (window.mainProgressChartInstance) {
        console.log('Destroying existing chart in fallback...');
        window.mainProgressChartInstance.destroy();
        window.mainProgressChartInstance = null;
    }
    
    // Also destroy any other chart instances that might be using this canvas
    if (window.Chart && window.Chart.instances) {
        Object.keys(window.Chart.instances).forEach(key => {
            const chart = window.Chart.instances[key];
            if (chart && chart.canvas && chart.canvas.id === 'progressChart') {
                console.log('Destroying chart instance with ID in fallback:', key);
                chart.destroy();
            }
        });
    }
    
    // Calculate current daily calories
    const currentDailyCalories = Object.keys(dailyMeals).reduce((sum, mealType) => {
        const mealTotal = dailyMeals[mealType].reduce((mealSum, food) => mealSum + food.calories, 0);
        return sum + mealTotal;
    }, 0);
    
    // Use a meaningful baseline - current meals, goal calories, or default
    const baseCalories = currentDailyCalories > 0 ? currentDailyCalories : (userProfile.goalCalories || 2000);
    
    console.log('Current daily calories for fallback:', currentDailyCalories);
    console.log('User goal calories:', userProfile.goalCalories);
    console.log('Using base calories:', baseCalories);
    
    let labels = [];
    let data = [];
    let title = '';
    
    switch (period) {
        case 'week':
            // Last 7 days
            for (let i = 6; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                labels.push(date.toLocaleDateString('en-US', { weekday: 'short' }));
                
                const variation = 0.8 + (Math.random() * 0.4); // 80-120% variation
                const dayCalories = Math.round(baseCalories * variation);
                data.push(dayCalories);
                console.log(`Fallback Day ${i}: ${dayCalories} calories (base: ${baseCalories}, variation: ${variation.toFixed(2)})`);
            }
            title = 'Daily Calories (Last 7 Days) - Simulated';
            break;
            
        case 'month':
            // Last 4 weeks
            for (let i = 3; i >= 0; i--) {
                labels.push(`Week ${4-i}`);
                
                const weeklyTotal = baseCalories * 7;
                const variation = 0.9 + (Math.random() * 0.2); // 90-110% variation
                const weekCalories = Math.round(weeklyTotal * variation);
                data.push(weekCalories);
                console.log(`Fallback Week ${4-i}: ${weekCalories} calories (base: ${baseCalories}, variation: ${variation.toFixed(2)})`);
            }
            title = 'Weekly Calories (Last 4 Weeks) - Simulated';
            break;
            
        case 'year':
            // Last 12 months
            for (let i = 11; i >= 0; i--) {
                const date = new Date();
                date.setMonth(date.getMonth() - i);
                labels.push(date.toLocaleDateString('en-US', { month: 'short' }));
                
                const monthlyTotal = baseCalories * 30;
                const variation = 0.925 + (Math.random() * 0.15); // 92.5-107.5% variation
                const monthCalories = Math.round(monthlyTotal * variation);
                data.push(monthCalories);
                console.log(`Fallback Month ${i}: ${monthCalories} calories (base: ${baseCalories}, variation: ${variation.toFixed(2)})`);
            }
            title = 'Monthly Calories (Last 12 Months) - Simulated';
            break;
    }
    
    console.log('Fallback chart data for', period, ':', { labels, data, title });
    
    window.mainProgressChartInstance = new Chart(progressCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Calories',
                data: data,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#6366f1',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            aspectRatio: 2.5,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: title,
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    min: Math.max(0, Math.min(...data) - 200),
                    max: Math.max(...data) + 200,
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    },
                    title: {
                        display: true,
                        text: 'Calories'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
    
    console.log('Main progress chart created successfully with fallback data:', !!window.mainProgressChartInstance);
    
    // If chart creation failed, try a simple test
    if (!window.mainProgressChartInstance) {
        console.log('Chart creation failed, trying simple test chart...');
        createSimpleTestChart(progressCtx);
    }
}

function createDirectProgressChart() {
    console.log('Creating direct progress chart...');
    const progressCtx = document.getElementById('progressChart');
    
    if (!progressCtx || !window.Chart) {
        console.error('Missing canvas or Chart.js for direct chart');
        return;
    }
    
    // Destroy existing chart if it exists
    if (window.mainProgressChartInstance) {
        console.log('Destroying existing chart in direct creation...');
        window.mainProgressChartInstance.destroy();
        window.mainProgressChartInstance = null;
    }
    
    // Also destroy any other chart instances that might be using this canvas
    if (window.Chart && window.Chart.instances) {
        Object.keys(window.Chart.instances).forEach(key => {
            const chart = window.Chart.instances[key];
            if (chart && chart.canvas && chart.canvas.id === 'progressChart') {
                console.log('Destroying chart instance with ID in direct creation:', key);
                chart.destroy();
            }
        });
    }
    
    // Simple hardcoded data that will definitely work
    const labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const data = [2800, 3200, 2900, 3500, 3100, 3300, 3000];
    
    console.log('Direct chart data:', { labels, data });
    
    try {
        window.mainProgressChartInstance = new Chart(progressCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Calories',
                    data: data,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                aspectRatio: 2.5,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Daily Calories (Last 7 Days)',
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 2500,
                        max: 3700,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        },
                        title: {
                            display: true,
                            text: 'Calories'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        console.log('Direct progress chart created successfully:', !!window.mainProgressChartInstance);
        
        // Update the buttons to work with this chart
        const buttons = document.querySelectorAll('.chart-container .btn-group .btn');
        buttons.forEach((btn, index) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                buttons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                const period = btn.textContent.toLowerCase();
                console.log('Button clicked:', period);
                
                // Update chart data based on period
                updateDirectChart(period);
            });
        });
        
    } catch (error) {
        console.error('Failed to create direct chart:', error);
    }
}

function updateDirectChart(period) {
    console.log('Updating direct chart for period:', period);
    
    if (!window.mainProgressChartInstance) {
        console.error('No chart instance to update');
        return;
    }
    
    let labels = [];
    let data = [];
    let title = '';
    
    switch (period) {
        case 'week':
            labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            data = [2800, 3200, 2900, 3500, 3100, 3300, 3000];
            title = 'Daily Calories (Last 7 Days)';
            break;
        case 'month':
            labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
            data = [21000, 22400, 20300, 21700];
            title = 'Weekly Calories (Last 4 Weeks)';
            break;
        case 'year':
            labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            data = [93000, 89600, 96100, 89900, 102300, 98700, 105400, 101200, 97800, 94200, 89600, 91300];
            title = 'Monthly Calories (Last 12 Months)';
            break;
    }
    
    // Update chart data
    window.mainProgressChartInstance.data.labels = labels;
    window.mainProgressChartInstance.data.datasets[0].data = data;
    window.mainProgressChartInstance.options.plugins.title.text = title;
    window.mainProgressChartInstance.update();
    
    console.log('Direct chart updated for', period, ':', { labels, data, title });
}

function forceCreateProgressChart() {
    console.log('Force creating progress chart...');
    const progressCtx = document.getElementById('progressChart');
    
    if (!progressCtx) {
        console.error('No progress chart canvas found');
        return;
    }
    
    if (!window.Chart) {
        console.error('Chart.js not available');
        return;
    }
    
    // Destroy any existing chart
    if (window.mainProgressChartInstance) {
        console.log('Destroying existing chart in force creation...');
        window.mainProgressChartInstance.destroy();
        window.mainProgressChartInstance = null;
    }
    
    // Also destroy any other chart instances that might be using this canvas
    if (window.Chart && window.Chart.instances) {
        Object.keys(window.Chart.instances).forEach(key => {
            const chart = window.Chart.instances[key];
            if (chart && chart.canvas && chart.canvas.id === 'progressChart') {
                console.log('Destroying chart instance with ID in force creation:', key);
                chart.destroy();
            }
        });
    }
    
    // Create chart with guaranteed data
    const labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const data = [2800, 3200, 2900, 3500, 3100, 3300, 3000];
    
    console.log('Force chart data:', { labels, data });
    
    try {
        window.mainProgressChartInstance = new Chart(progressCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Calories',
                    data: data,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                aspectRatio: 2.5,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Daily Calories (Last 7 Days)',
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 2500,
                        max: 3700,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        },
                        title: {
                            display: true,
                            text: 'Calories'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        console.log('Force chart created successfully:', !!window.mainProgressChartInstance);
        
        // Make buttons work
        const buttons = document.querySelectorAll('.chart-container .btn-group .btn');
        console.log('Found buttons:', buttons.length);
        
        buttons.forEach((btn, index) => {
            // Remove existing listeners
            btn.replaceWith(btn.cloneNode(true));
        });
        
        // Re-select buttons after cloning
        const newButtons = document.querySelectorAll('.chart-container .btn-group .btn');
        newButtons.forEach((btn, index) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                console.log('Button clicked:', btn.textContent);
                
                // Remove active from all
                newButtons.forEach(b => b.classList.remove('active'));
                // Add active to clicked
                btn.classList.add('active');
                
                const period = btn.textContent.toLowerCase();
                forceUpdateChart(period);
            });
        });
        
        // Set first button as active
        if (newButtons.length > 0) {
            newButtons[0].classList.add('active');
        }
        
    } catch (error) {
        console.error('Failed to force create chart:', error);
    }
}

function forceUpdateChart(period) {
    console.log('Force updating chart for period:', period);
    
    if (!window.mainProgressChartInstance) {
        console.error('No chart instance to update');
        return;
    }
    
    let labels = [];
    let data = [];
    let title = '';
    
    switch (period) {
        case 'week':
            labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            data = [2800, 3200, 2900, 3500, 3100, 3300, 3000];
            title = 'Daily Calories (Last 7 Days)';
            break;
        case 'month':
            labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
            data = [21000, 22400, 20300, 21700];
            title = 'Weekly Calories (Last 4 Weeks)';
            break;
        case 'year':
            labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            data = [93000, 89600, 96100, 89900, 102300, 98700, 105400, 101200, 97800, 94200, 89600, 91300];
            title = 'Monthly Calories (Last 12 Months)';
            break;
    }
    
    console.log('Force update data:', { labels, data, title });
    
    // Update chart
    window.mainProgressChartInstance.data.labels = labels;
    window.mainProgressChartInstance.data.datasets[0].data = data;
    window.mainProgressChartInstance.options.plugins.title.text = title;
    window.mainProgressChartInstance.update();
    
    console.log('Force chart updated successfully');
}

function createSimpleTestChart(canvas) {
    console.log('Creating simple test chart...');
    
    const testData = [1200, 1400, 1100, 1600, 1300, 1500, 1200];
    const testLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    
    try {
        window.mainProgressChartInstance = new Chart(canvas, {
            type: 'line',
            data: {
                labels: testLabels,
                datasets: [{
                    label: 'Calories',
                    data: testData,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                aspectRatio: 2.5,
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: 'Daily Calories (Test Data)',
                        font: { size: 16, weight: 'bold' }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 1000,
                        max: 1700,
                        grid: { color: 'rgba(0,0,0,0.1)' },
                        title: { display: true, text: 'Calories' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
        
        console.log('Simple test chart created successfully:', !!window.mainProgressChartInstance);
    } catch (error) {
        console.error('Failed to create simple test chart:', error);
    }
}

function updateProgressChart(period) {
    console.log('Updating progress chart for period:', period);
    const weeklyCtx = document.getElementById('weeklyProgressChart');
    if (!weeklyCtx || !window.Chart) { return; }

    if (window.weeklyProgressChartInstance) {
        window.weeklyProgressChartInstance.destroy();
    }

    const isInPagesDir = window.location.pathname.indexOf('/pages/') !== -1;
    const baseUrl = (isInPagesDir ? '../php/meal_history_api.php' : 'php/meal_history_api.php');
    const apiPeriod = period === 'year' ? 'month' : period; // fallback mapping

    fetch(`${baseUrl}?action=get_nutrition_series&period=${encodeURIComponent(apiPeriod)}`, { credentials: 'include' })
        .then(r => r.json())
        .then(payload => {
            const rows = (payload && payload.success && Array.isArray(payload.series)) ? payload.series : [];
            if (rows.length === 0) { throw new Error('no-data'); }
            const labels = rows.map(r => r.date);
            const data = rows.map(r => Math.round(Number(r.total_calories || 0)));
            const title = apiPeriod === 'week' ? 'Daily Calories (Last 7 Days)' : (apiPeriod === 'month' ? 'Daily Calories (Last 30 Days)' : 'Calories');

            window.weeklyProgressChartInstance = new Chart(weeklyCtx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Calories',
                        data,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#28a745',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, title: { display: true, text: title, font: { size: 14, weight: 'bold' } } },
                    scales: { y: { beginAtZero: true }, x: { grid: { display: false } } }
                }
            });
        })
        .catch(() => {
            // keep previous simulated fallback
            console.log('Falling back to simulated series');
            updateMainProgressChartFallback(period);
        });
}

function createMacroPeriodSelector() {
    // Add macro period selector above the chart
    const chartContainer = document.querySelector('#macroProgressChart').closest('.col-md-6');
    if (!chartContainer) return;
    
    const selectorHtml = `
        <div class="mb-3">
            <label class="form-label">Macro Period:</label>
            <div class="btn-group" role="group" id="macroPeriodSelector">
                <input type="radio" class="btn-check" name="macroPeriod" id="dayMacroPeriod" value="day" checked>
                <label class="btn btn-outline-primary btn-sm" for="dayMacroPeriod">Today</label>
                
                <input type="radio" class="btn-check" name="macroPeriod" id="weekMacroPeriod" value="week">
                <label class="btn btn-outline-primary btn-sm" for="weekMacroPeriod">Week</label>
                
                <input type="radio" class="btn-check" name="macroPeriod" id="monthMacroPeriod" value="month">
                <label class="btn btn-outline-primary btn-sm" for="monthMacroPeriod">Month</label>
            </div>
        </div>
    `;
    
    chartContainer.insertAdjacentHTML('afterbegin', selectorHtml);
    
    // Add event listeners with debugging
    document.querySelectorAll('input[name="macroPeriod"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            console.log('Macro period changed to:', e.target.value);
            if (e.target.checked) {
                updateMacroChart(e.target.value);
            }
        });
    });
    
    console.log('Macro period selector created with', document.querySelectorAll('input[name="macroPeriod"]').length, 'buttons');
}

function updateMacroChart(period) {
    console.log('Updating macro chart for period:', period);
    const macroCtx = document.getElementById('macroProgressChart');
    if (!macroCtx || !window.Chart) { return; }

    if (window.macroProgressChartInstance) { window.macroProgressChartInstance.destroy(); }

    const isInPagesDir = window.location.pathname.indexOf('/pages/') !== -1;
    const baseUrl = (isInPagesDir ? '../php/meal_history_api.php' : 'php/meal_history_api.php');

    const render = (totals, title) => {
        window.macroProgressChartInstance = new Chart(macroCtx, {
            type: 'doughnut',
            data: {
                labels: ['Protein', 'Carbs', 'Fats'],
                datasets: [{ data: [totals.protein, totals.carbs, totals.fats], backgroundColor: ['#007bff', '#28a745', '#ffc107'], borderWidth: 0, hoverOffset: 4 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true } }, title: { display: true, text: title, font: { size: 14, weight: 'bold' } } } }
        });
    };

    if (period === 'day') {
        fetch(baseUrl + '?action=get_today_macros', { credentials: 'include' })
            .then(r => r.json())
            .then(p => {
                const m = (p && p.success && p.macros) ? p.macros : { total_protein: 0, total_carbs: 0, total_fats: 0 };
                render({ protein: Math.round(m.total_protein||0), carbs: Math.round(m.total_carbs||0), fats: Math.round(m.total_fats||0) }, "Today's Macro Distribution");
            })
            .catch(() => render({ protein: 0, carbs: 0, fats: 0 }, "Today's Macro Distribution"));
        return;
    }

    const apiPeriod = period; // 'week' or 'month'
    fetch(`${baseUrl}?action=get_nutrition_series&period=${encodeURIComponent(apiPeriod)}`, { credentials: 'include' })
        .then(r => r.json())
        .then(p => {
            const rows = (p && p.success && Array.isArray(p.series)) ? p.series : [];
            const totals = rows.reduce((acc, r) => {
                acc.protein += Number(r.total_protein||0);
                acc.carbs += Number(r.total_carbs||0);
                acc.fats += Number(r.total_fats||0);
                return acc;
            }, { protein: 0, carbs: 0, fats: 0 });
            render({ protein: Math.round(totals.protein), carbs: Math.round(totals.carbs), fats: Math.round(totals.fats) }, period === 'week' ? 'Weekly Macro Distribution' : 'Monthly Macro Distribution');
        })
        .catch(() => render({ protein: 0, carbs: 0, fats: 0 }, period === 'week' ? 'Weekly Macro Distribution' : 'Monthly Macro Distribution'));
}

// Legacy fallback kept for compatibility: route to unified function
// (Removed duplicate implementation to avoid override)

// Calorie Calculator Functions
function initializeCalorieCalculator() {
    // Set up calorie calculator tab switching
    const singleFoodBtn = document.getElementById('singleFoodBtn');
    const recipeBtn = document.getElementById('recipeBtn');
    const comparisonBtn = document.getElementById('comparisonBtn');
    
    if (singleFoodBtn) singleFoodBtn.addEventListener('click', () => switchCalorieCalcTab('singleFood'));
    if (recipeBtn) recipeBtn.addEventListener('click', () => switchCalorieCalcTab('recipe'));
    if (comparisonBtn) comparisonBtn.addEventListener('click', () => switchCalorieCalcTab('comparison'));
    
    // Set up single food calculator
    const foodSearchCalc = document.getElementById('foodSearchCalc');
    const calculateCaloriesBtn = document.getElementById('calculateCaloriesBtn');
    
    if (foodSearchCalc) {
        foodSearchCalc.addEventListener('input', handleFoodSearchCalc);
    }
    
    if (calculateCaloriesBtn) {
        calculateCaloriesBtn.addEventListener('click', calculateSingleFoodCalories);
    }
    
    // Set up recipe calculator
    const addIngredientBtn = document.getElementById('addIngredientBtn');
    const calculateRecipeBtn = document.getElementById('calculateRecipeBtn');
    
    if (addIngredientBtn) {
        addIngredientBtn.addEventListener('click', addIngredientRow);
    }
    
    if (calculateRecipeBtn) {
        calculateRecipeBtn.addEventListener('click', calculateRecipeCalories);
    }
    
    // Set up comparison calculator
    const compareFoodsBtn = document.getElementById('compareFoodsBtn');
    
    if (compareFoodsBtn) {
        compareFoodsBtn.addEventListener('click', compareFoods);
    }
    
    // Set up ingredient removal
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-ingredient')) {
            e.target.closest('.ingredient-item').remove();
        }
    });
    
    // Meal options are now handled in the main setupMealOptions() function
}

function switchCalorieCalcTab(tabName) {
    // Update button states
    document.querySelectorAll('[id$="Btn"]').forEach(btn => {
        if (btn.id.includes('singleFood') || btn.id.includes('recipe') || btn.id.includes('comparison')) {
            btn.classList.remove('active');
        }
    });
    document.getElementById(`${tabName}Btn`).classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.calorie-calc-tab').forEach(tab => {
        tab.style.display = 'none';
    });
    document.getElementById(`${tabName}Calc`).style.display = 'block';
}

function handleFoodSearchCalc(e) {
    const query = e.target.value.toLowerCase();
    const suggestionsContainer = document.getElementById('foodSuggestionsCalc');
    
    if (query.length < 2) {
        suggestionsContainer.innerHTML = '';
        return;
    }
    
    const suggestions = Object.keys(foodDatabase).filter(food => 
        foodDatabase[food].name.toLowerCase().includes(query)
    ).slice(0, 5);
    
    if (suggestions.length === 0) {
        suggestionsContainer.innerHTML = '<div class="suggestion-item">No foods found</div>';
        return;
    }
    
    suggestionsContainer.innerHTML = suggestions.map(food => `
        <div class="suggestion-item" onclick="selectFoodCalc('${food}')">
            <strong>${foodDatabase[food].name}</strong>
            <small>${foodDatabase[food].caloriesPer100g} cal/100g</small>
        </div>
    `).join('');
}

function selectFoodCalc(foodKey) {
    const food = foodDatabase[foodKey];
    const foodSearchInput = document.getElementById('foodSearchCalc');
    const suggestionsContainer = document.getElementById('foodSuggestionsCalc');
    
    foodSearchInput.value = food.name;
    suggestionsContainer.innerHTML = '';
    
    // Store selected food for calculation
    foodSearchInput.dataset.selectedFood = foodKey;
}

function calculateSingleFoodCalories() {
    const foodSearchInput = document.getElementById('foodSearchCalc');
    const portionSize = parseFloat(document.getElementById('portionSizeCalc').value);
    const portionUnit = document.getElementById('portionUnitCalc').value;
    const selectedFoodKey = foodSearchInput.dataset.selectedFood;
    
    if (!selectedFoodKey || !portionSize) {
        showNotification('Please select a food and enter portion size', 'warning');
        return;
    }
    
    const food = foodDatabase[selectedFoodKey];
    const convertedAmount = convertPortionToGrams(portionSize, portionUnit, food.name);
    const calories = (food.caloriesPer100g * convertedAmount) / 100;
    const protein = (food.protein * convertedAmount) / 100;
    const carbs = (food.carbs * convertedAmount) / 100;
    const fats = (food.fats * convertedAmount) / 100;
    
    displaySingleFoodResult(food.name, portionSize, portionUnit, calories, protein, carbs, fats);
}

function displaySingleFoodResult(foodName, amount, unit, calories, protein, carbs, fats) {
    const resultsContainer = document.getElementById('calcResults');
    
    resultsContainer.innerHTML = `
        <div class="food-result-card">
            <h5><i class="fas fa-utensils me-2"></i>${foodName}</h5>
            <p class="mb-3">${amount} ${unit}</p>
            <div class="nutrition-grid">
                <div class="nutrition-item">
                    <h6>${Math.round(calories)}</h6>
                    <p>Calories</p>
                </div>
                <div class="nutrition-item">
                    <h6>${Math.round(protein)}g</h6>
                    <p>Protein</p>
                </div>
                <div class="nutrition-item">
                    <h6>${Math.round(carbs)}g</h6>
                    <p>Carbs</p>
                </div>
                <div class="nutrition-item">
                    <h6>${Math.round(fats)}g</h6>
                    <p>Fats</p>
                </div>
            </div>
            <button class="btn btn-light w-100" onclick="addToTracker('${foodName}', ${amount}, '${unit}', ${Math.round(calories)}, ${Math.round(protein)}, ${Math.round(carbs)}, ${Math.round(fats)})">
                <i class="fas fa-plus me-2"></i>Add to Daily Tracker
            </button>
        </div>
    `;
}

function addIngredientRow() {
    const ingredientList = document.getElementById('ingredientList');
    const newIngredient = document.createElement('div');
    newIngredient.className = 'ingredient-item';
    newIngredient.innerHTML = `
        <div class="row">
            <div class="col-md-5">
                <input type="text" class="form-control" placeholder="Food name" name="ingredientName">
            </div>
            <div class="col-md-3">
                <input type="number" class="form-control" placeholder="Amount" name="ingredientAmount" step="0.1">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="ingredientUnit">
                    <option value="g">g</option>
                    <option value="ml">ml</option>
                    <option value="cup">cup</option>
                    <option value="tbsp">tbsp</option>
                    <option value="tsp">tsp</option>
                    <option value="piece">piece</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger btn-sm remove-ingredient">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    ingredientList.appendChild(newIngredient);
}

function calculateRecipeCalories() {
    const ingredients = [];
    const ingredientItems = document.querySelectorAll('.ingredient-item');
    const servings = parseInt(document.getElementById('servingsCalc').value) || 1;
    const recipeName = document.getElementById('recipeName').value || 'My Recipe';
    
    ingredientItems.forEach(item => {
        const nameInput = item.querySelector('input[name="ingredientName"]');
        const amountInput = item.querySelector('input[name="ingredientAmount"]');
        const unitSelect = item.querySelector('select[name="ingredientUnit"]');
        
        if (nameInput.value && amountInput.value) {
            const foodKey = findFoodKey(nameInput.value);
            if (foodKey) {
                const food = foodDatabase[foodKey];
                const amount = parseFloat(amountInput.value);
                const unit = unitSelect.value;
                const convertedAmount = convertPortionToGrams(amount, unit, food.name);
                
                ingredients.push({
                    name: food.name,
                    amount: amount,
                    unit: unit,
                    calories: (food.caloriesPer100g * convertedAmount) / 100,
                    protein: (food.protein * convertedAmount) / 100,
                    carbs: (food.carbs * convertedAmount) / 100,
                    fats: (food.fats * convertedAmount) / 100
                });
            }
        }
    });
    
    if (ingredients.length === 0) {
        showNotification('Please add at least one ingredient', 'warning');
        return;
    }
    
    const totalCalories = ingredients.reduce((sum, ing) => sum + ing.calories, 0);
    const totalProtein = ingredients.reduce((sum, ing) => sum + ing.protein, 0);
    const totalCarbs = ingredients.reduce((sum, ing) => sum + ing.carbs, 0);
    const totalFats = ingredients.reduce((sum, ing) => sum + ing.fats, 0);
    
    const perServingCalories = totalCalories / servings;
    const perServingProtein = totalProtein / servings;
    const perServingCarbs = totalCarbs / servings;
    const perServingFats = totalFats / servings;
    
    displayRecipeResult(recipeName, ingredients, servings, totalCalories, totalProtein, totalCarbs, totalFats, perServingCalories, perServingProtein, perServingCarbs, perServingFats);
}

function displayRecipeResult(recipeName, ingredients, servings, totalCalories, totalProtein, totalCarbs, totalFats, perServingCalories, perServingProtein, perServingCarbs, perServingFats) {
    const resultsContainer = document.getElementById('recipeResults');
    
    resultsContainer.innerHTML = `
        <div class="recipe-summary">
            <h5><i class="fas fa-utensils me-2"></i>${recipeName}</h5>
            <p class="mb-3">${servings} serving${servings > 1 ? 's' : ''}</p>
            <div class="nutrition-grid">
                <div class="nutrition-item">
                    <h6>${Math.round(totalCalories)}</h6>
                    <p>Total Calories</p>
                </div>
                <div class="nutrition-item">
                    <h6>${Math.round(totalProtein)}g</h6>
                    <p>Total Protein</p>
                </div>
                <div class="nutrition-item">
                    <h6>${Math.round(totalCarbs)}g</h6>
                    <p>Total Carbs</p>
                </div>
                <div class="nutrition-item">
                    <h6>${Math.round(totalFats)}g</h6>
                    <p>Total Fats</p>
                </div>
            </div>
        </div>
        
        <div class="recipe-summary" style="background: linear-gradient(135deg, var(--success-color), var(--info-color));">
            <h6><i class="fas fa-user me-2"></i>Per Serving</h6>
            <div class="nutrition-grid">
                <div class="nutrition-item">
                    <h6>${Math.round(perServingCalories)}</h6>
                    <p>Calories</p>
                </div>
                <div class="nutrition-item">
                    <h6>${Math.round(perServingProtein)}g</h6>
                    <p>Protein</p>
                </div>
                <div class="nutrition-item">
                    <h6>${Math.round(perServingCarbs)}g</h6>
                    <p>Carbs</p>
                </div>
                <div class="nutrition-item">
                    <h6>${Math.round(perServingFats)}g</h6>
                    <p>Fats</p>
                </div>
            </div>
        </div>
        
        <div class="recipe-ingredients">
            <h6><i class="fas fa-list me-2"></i>Ingredients</h6>
            ${ingredients.map(ing => `
                <div class="recipe-ingredient">
                    <span>${ing.name}</span>
                    <strong>${ing.amount} ${ing.unit}</strong>
                </div>
            `).join('')}
        </div>
        
        <button class="btn btn-primary w-100 mt-3" onclick="addRecipeToTracker('${recipeName}', ${perServingCalories}, ${perServingProtein}, ${perServingCarbs}, ${perServingFats})">
            <i class="fas fa-plus me-2"></i>Add One Serving to Tracker
        </button>
    `;
}

function compareFoods() {
    const food1Name = document.getElementById('compareFood1').value;
    const food1Amount = parseFloat(document.getElementById('compareAmount1').value);
    const food1Unit = document.getElementById('compareUnit1').value;
    const food2Name = document.getElementById('compareFood2').value;
    const food2Amount = parseFloat(document.getElementById('compareAmount2').value);
    const food2Unit = document.getElementById('compareUnit2').value;
    
    if (!food1Name || !food2Name || !food1Amount || !food2Amount) {
        showNotification('Please fill in all fields for both foods', 'warning');
        return;
    }
    
    const food1Key = findFoodKey(food1Name);
    const food2Key = findFoodKey(food2Name);
    
    if (!food1Key || !food2Key) {
        showNotification('One or both foods not found in database', 'warning');
        return;
    }
    
    const food1 = foodDatabase[food1Key];
    const food2 = foodDatabase[food2Key];
    
    const food1Converted = convertPortionToGrams(food1Amount, food1Unit, food1.name);
    const food2Converted = convertPortionToGrams(food2Amount, food2Unit, food2.name);
    
    const food1Calories = (food1.caloriesPer100g * food1Converted) / 100;
    const food1Protein = (food1.protein * food1Converted) / 100;
    const food1Carbs = (food1.carbs * food1Converted) / 100;
    const food1Fats = (food1.fats * food1Converted) / 100;
    
    const food2Calories = (food2.caloriesPer100g * food2Converted) / 100;
    const food2Protein = (food2.protein * food2Converted) / 100;
    const food2Carbs = (food2.carbs * food2Converted) / 100;
    const food2Fats = (food2.fats * food2Converted) / 100;
    
    displayComparisonResult(food1Name, food1Amount, food1Unit, food1Calories, food1Protein, food1Carbs, food1Fats,
                           food2Name, food2Amount, food2Unit, food2Calories, food2Protein, food2Carbs, food2Fats);
}

function displayComparisonResult(food1Name, food1Amount, food1Unit, food1Calories, food1Protein, food1Carbs, food1Fats,
                                food2Name, food2Amount, food2Unit, food2Calories, food2Protein, food2Carbs, food2Fats) {
    const resultsContainer = document.getElementById('comparisonResults');
    
    const calorieDiff = food1Calories - food2Calories;
    const proteinDiff = food1Protein - food2Protein;
    const carbDiff = food1Carbs - food2Carbs;
    const fatDiff = food1Fats - food2Fats;
    
    resultsContainer.innerHTML = `
        <div class="comparison-result">
            <div class="comparison-food food1">
                <h6>${food1Name}</h6>
                <p>${food1Amount} ${food1Unit}</p>
                <div class="nutrition-item">
                    <h6>${Math.round(food1Calories)}</h6>
                    <p>Calories</p>
                </div>
                <div class="nutrition-item">
                    <h6>${Math.round(food1Protein)}g</h6>
                    <p>Protein</p>
                </div>
                <div class="nutrition-item">
                    <h6>${Math.round(food1Carbs)}g</h6>
                    <p>Carbs</p>
                </div>
                <div class="nutrition-item">
                    <h6>${Math.round(food1Fats)}g</h6>
                    <p>Fats</p>
                </div>
            </div>
            <div class="comparison-food food2">
                <h6>${food2Name}</h6>
                <p>${food2Amount} ${food2Unit}</p>
                <div class="nutrition-item">
                    <h6>${Math.round(food2Calories)}</h6>
                    <p>Calories</p>
                </div>
                <div class="nutrition-item">
                    <h6>${Math.round(food2Protein)}g</h6>
                    <p>Protein</p>
                </div>
                <div class="nutrition-item">
                    <h6>${Math.round(food2Carbs)}g</h6>
                    <p>Carbs</p>
                </div>
                <div class="nutrition-item">
                    <h6>${Math.round(food2Fats)}g</h6>
                    <p>Fats</p>
                </div>
            </div>
        </div>
        
        <div class="comparison-summary">
            <h6><i class="fas fa-balance-scale me-2"></i>Comparison Summary</h6>
            <div class="summary-item">
                <span>Calorie Difference</span>
                <strong>${calorieDiff > 0 ? '+' : ''}${Math.round(calorieDiff)} cal</strong>
            </div>
            <div class="summary-item">
                <span>Protein Difference</span>
                <strong>${proteinDiff > 0 ? '+' : ''}${Math.round(proteinDiff)}g</strong>
            </div>
            <div class="summary-item">
                <span>Carb Difference</span>
                <strong>${carbDiff > 0 ? '+' : ''}${Math.round(carbDiff)}g</strong>
            </div>
            <div class="summary-item">
                <span>Fat Difference</span>
                <strong>${fatDiff > 0 ? '+' : ''}${Math.round(fatDiff)}g</strong>
            </div>
        </div>
    `;
}

function findFoodKey(foodName) {
    return Object.keys(foodDatabase).find(key => 
        foodDatabase[key].name.toLowerCase() === foodName.toLowerCase()
    );
}

function convertPortionToGrams(amount, unit, foodName) {
    // Simple conversion logic - in a real app, this would be more sophisticated
    const conversions = {
        'g': 1,
        'ml': 1, // Assuming 1ml = 1g for most liquids
        'cup': 240, // Approximate
        'tbsp': 15,
        'tsp': 5,
        'piece': 100, // Average piece size
        'slice': 30, // Average slice size
        'medium': 150, // Average medium size
        'large': 200 // Average large size
    };
    
    return amount * (conversions[unit] || 1);
}

function addToTracker(foodName, amount, unit, calories, protein, carbs, fats) {
    const foodData = {
        id: Date.now() + Math.random(),
        name: foodName,
        amount: `${amount} ${unit}`,
        calories: calories,
        protein: protein,
        carbs: carbs,
        fats: fats,
        fiber: 0,
        sugar: 0
    };
    
    dailyMeals[currentMealType].push(foodData);
    updateCurrentMealDisplay();
    updateDailyTotals();
    showNotification('Food added to tracker!', 'success');
}

function addRecipeToTracker(recipeName, calories, protein, carbs, fats) {
    const foodData = {
        id: Date.now() + Math.random(),
        name: recipeName,
        amount: '1 serving',
        calories: calories,
        protein: protein,
        carbs: carbs,
        fats: fats,
        fiber: 0,
        sugar: 0
    };
    
    dailyMeals[currentMealType].push(foodData);
    updateCurrentMealDisplay();
    updateDailyTotals();
    showNotification('Recipe added to tracker!', 'success');
}

// Breakfast Options Functions
function setupBreakfastOptions() {
    // Set up individual breakfast food buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.breakfast-btn')) {
            const btn = e.target.closest('.breakfast-btn');
            const food = btn.dataset.food;
            const amount = btn.dataset.amount;
            const unit = btn.dataset.unit;
            
            addBreakfastFood(food, amount, unit);
        }
        
        if (e.target.closest('.breakfast-combo-btn')) {
            const btn = e.target.closest('.breakfast-combo-btn');
            const combo = btn.dataset.combo;
            
            addBreakfastCombo(combo);
        }
    });
}

function addBreakfastFood(foodKey, amount, unit) {
    console.log('addBreakfastFood called:', { foodKey, amount, unit });
    
    // Ensure we're on breakfast meal type
    document.getElementById('breakfast').checked = true;
    currentMealType = 'breakfast';
    
    // Try to find food in local database first
    const food = foodDatabase[foodKey];
    if (food) {
        console.log('Found in local database:', food);
        addBreakfastFoodFromLocal(food, amount, unit);
        return;
    }
    
    console.log('Not found locally, searching API for:', foodKey);
    
    // If not found locally, search in database API
    fetch(`../php/food_api.php?action=search&q=${encodeURIComponent(foodKey)}&limit=1`)
        .then(response => {
            console.log('API response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('API raw response:', text);
            try {
                const data = JSON.parse(text);
                if (data.error || !data.foods || data.foods.length === 0) {
                    console.log('API error or no foods found:', data);
                    showNotification('Food not found in database', 'warning');
                    return;
                }
                
                const apiFood = data.foods[0];
                console.log('Found in API:', apiFood);
                addBreakfastFoodFromAPI(apiFood, amount, unit);
            } catch (e) {
                console.error('JSON parse error:', e);
                showNotification('Error parsing API response', 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching food from API:', error);
            showNotification('Error fetching food data', 'error');
        });
}

function addBreakfastFoodFromLocal(food, amount, unit) {
    // Convert amount to grams for calculation
    const convertedAmount = convertPortionToGrams(parseFloat(amount), unit, food.name);
    
    // Calculate nutrition
    const calories = (food.caloriesPer100g * convertedAmount) / 100;
    const protein = (food.protein * convertedAmount) / 100;
    const carbs = (food.carbs * convertedAmount) / 100;
    const fats = (food.fats * convertedAmount) / 100;
    
    // Create food data
    const foodData = {
        id: Date.now() + Math.random(),
        name: food.name,
        amount: `${amount} ${unit}`,
        calories: Math.round(calories),
        protein: Math.round(protein),
        carbs: Math.round(carbs),
        fats: Math.round(fats),
        fiber: Math.round((food.fiber * convertedAmount) / 100),
        sugar: Math.round((food.sugar * convertedAmount) / 100)
    };
    
    // Add to daily meals
    dailyMeals[currentMealType].push(foodData);
    
    // Update display
    updateCurrentMealDisplay();
    updateDailyTotals();
    updateNutritionCharts();
    
    // Show success notification
    showNotification(`${food.name} added to breakfast!`, 'success');
}

function addBreakfastFoodFromAPI(apiFood, amount, unit) {
    console.log('addBreakfastFoodFromAPI called:', { apiFood, amount, unit });
    
    // Convert amount to grams for calculation
    const convertedAmount = convertPortionToGrams(parseFloat(amount), unit, apiFood.name);
    console.log('Converted amount to grams:', convertedAmount);
    
    // Calculate nutrition
    const calories = (apiFood.calories_per_100g * convertedAmount) / 100;
    const protein = (apiFood.protein * convertedAmount) / 100;
    const carbs = (apiFood.carbs * convertedAmount) / 100;
    const fats = (apiFood.fats * convertedAmount) / 100;
    
    console.log('Calculated nutrition:', { calories, protein, carbs, fats });
    
    // Create food data
    const foodData = {
        id: Date.now() + Math.random(),
        name: apiFood.name,
        amount: `${amount} ${unit}`,
        calories: Math.round(calories),
        protein: Math.round(protein),
        carbs: Math.round(carbs),
        fats: Math.round(fats),
        fiber: Math.round((apiFood.fiber * convertedAmount) / 100),
        sugar: Math.round((apiFood.sugar * convertedAmount) / 100),
        category: apiFood.category,
        foodId: apiFood.id // Store database ID for future reference
    };
    
    console.log('Created food data:', foodData);
    
    // Add to daily meals
    dailyMeals[currentMealType].push(foodData);
    console.log('Added to dailyMeals:', dailyMeals[currentMealType]);
    
    // Update display
    updateCurrentMealDisplay();
    updateDailyTotals();
    updateNutritionCharts();
    
    // Show success notification
    showNotification(`${apiFood.name} added to breakfast!`, 'success');
}

function addBreakfastCombo(comboType) {
    // Ensure we're on breakfast meal type
    document.getElementById('breakfast').checked = true;
    currentMealType = 'breakfast';
    
    const combos = {
        protein_power: [
            { food: 'eggs', amount: '2', unit: 'large' },
            { food: 'greek_yogurt', amount: '200', unit: 'g' },
            { food: 'blueberries', amount: '50', unit: 'g' }
        ],
        energy_boost: [
            { food: 'oatmeal', amount: '50', unit: 'g' },
            { food: 'banana', amount: '1', unit: 'medium' },
            { food: 'almonds', amount: '30', unit: 'g' }
        ],
        healthy_start: [
            { food: 'whole_grain_bread', amount: '2', unit: 'slice' },
            { food: 'avocado', amount: '0.5', unit: 'medium' },
            { food: 'eggs', amount: '2', unit: 'large' },
            { food: 'spinach', amount: '50', unit: 'g' }
        ]
    };
    
    const combo = combos[comboType];
    if (!combo) {
        showNotification('Combo not found', 'warning');
        return;
    }
    
    // Add each food in the combo
    combo.forEach(item => {
        addBreakfastFood(item.food, item.amount, item.unit);
    });
    
    // Show combo success notification
    const comboNames = {
        protein_power: 'Protein Power',
        energy_boost: 'Energy Boost',
        healthy_start: 'Healthy Start'
    };
    
    showNotification(`${comboNames[comboType]} combo added to breakfast!`, 'success');
}

// Update breakfast options visibility based on meal type
function updateBreakfastOptionsVisibility() {
    const breakfastOptions = document.getElementById('breakfastOptions');
    const breakfastRadio = document.getElementById('breakfast');
    
    if (breakfastOptions && breakfastRadio) {
        if (breakfastRadio.checked) {
            breakfastOptions.style.display = 'block';
        } else {
            breakfastOptions.style.display = 'none';
        }
    }
}

// Update all meal options visibility based on meal type
function updateMealOptionsVisibility() {
    const breakfastOptions = document.getElementById('breakfastOptions');
    const lunchOptions = document.getElementById('lunchOptions');
    const dinnerOptions = document.getElementById('dinnerOptions');
    const snacksOptions = document.getElementById('snacksOptions');
    
    const breakfastRadio = document.getElementById('breakfast');
    const lunchRadio = document.getElementById('lunch');
    const dinnerRadio = document.getElementById('dinner');
    const snacksRadio = document.getElementById('snacks');
    
    
    // Hide all options first - using !important to override CSS
    if (breakfastOptions) {
        breakfastOptions.style.setProperty('display', 'none', 'important');
        const breakfastCollapse = document.getElementById('breakfastCollapse');
        if (breakfastCollapse) {
            breakfastCollapse.classList.remove('show');
            breakfastCollapse.style.setProperty('display', 'none', 'important');
        }
    }
    if (lunchOptions) {
        lunchOptions.style.setProperty('display', 'none', 'important');
        const lunchCollapse = document.getElementById('lunchCollapse');
        if (lunchCollapse) {
            lunchCollapse.classList.remove('show');
            lunchCollapse.style.setProperty('display', 'none', 'important');
        }
    }
    if (dinnerOptions) {
        dinnerOptions.style.setProperty('display', 'none', 'important');
        const dinnerCollapse = document.getElementById('dinnerCollapse');
        if (dinnerCollapse) {
            dinnerCollapse.classList.remove('show');
            dinnerCollapse.style.setProperty('display', 'none', 'important');
        }
    }
    if (snacksOptions) {
        snacksOptions.style.setProperty('display', 'none', 'important');
        const snacksCollapse = document.getElementById('snacksCollapse');
        if (snacksCollapse) {
            snacksCollapse.classList.remove('show');
            snacksCollapse.style.setProperty('display', 'none', 'important');
        }
    }
    
    // Show the selected meal type options
    if (breakfastRadio && breakfastRadio.checked && breakfastOptions) {
        breakfastOptions.style.setProperty('display', 'block', 'important');
        
        // Ensure the collapse content is shown
        const breakfastCollapse = document.getElementById('breakfastCollapse');
        if (breakfastCollapse) {
            breakfastCollapse.classList.add('show');
            breakfastCollapse.style.setProperty('display', 'block', 'important');
        }
    } else if (lunchRadio && lunchRadio.checked && lunchOptions) {
        lunchOptions.style.setProperty('display', 'block', 'important');
        
        // Ensure the collapse content is shown
        const lunchCollapse = document.getElementById('lunchCollapse');
        if (lunchCollapse) {
            lunchCollapse.classList.add('show');
            lunchCollapse.style.setProperty('display', 'block', 'important');
        }
    } else if (dinnerRadio && dinnerRadio.checked && dinnerOptions) {
        dinnerOptions.style.setProperty('display', 'block', 'important');
        
        // Ensure the collapse content is shown
        const dinnerCollapse = document.getElementById('dinnerCollapse');
        if (dinnerCollapse) {
            dinnerCollapse.classList.add('show');
            dinnerCollapse.style.setProperty('display', 'block', 'important');
        }
    } else if (snacksRadio && snacksRadio.checked && snacksOptions) {
        snacksOptions.style.setProperty('display', 'block', 'important');
        
        // Ensure the collapse content is shown
        const snacksCollapse = document.getElementById('snacksCollapse');
        if (snacksCollapse) {
            snacksCollapse.classList.add('show');
            snacksCollapse.style.setProperty('display', 'block', 'important');
        }
    } else {
        // Fallback: if no radio is checked, check which one should be checked by default
        if (breakfastRadio && breakfastOptions) {
            breakfastOptions.style.setProperty('display', 'block', 'important');
            
            // Ensure the collapse content is shown
            const breakfastCollapse = document.getElementById('breakfastCollapse');
            if (breakfastCollapse) {
                breakfastCollapse.classList.add('show');
                breakfastCollapse.style.setProperty('display', 'block', 'important');
            }
        }
    }
    
}

// Setup meal options for lunch, dinner, and snacks
function setupMealOptions() {
    // Set up meal type radio button event listeners
    const mealTypeRadios = document.querySelectorAll('input[name="mealType"]');
    
    mealTypeRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            currentMealType = e.target.value;
            updateCurrentMealDisplay();
            updateMealOptionsVisibility();
            
        });
    });
    
    // Set up all meal options (breakfast, lunch, dinner, snacks)
    document.addEventListener('click', function(e) {
        // Breakfast buttons
        if (e.target.closest('.breakfast-btn')) {
            const btn = e.target.closest('.breakfast-btn');
            const food = btn.dataset.food;
            const amount = btn.dataset.amount;
            const unit = btn.dataset.unit;
            
            addMealFood('breakfast', food, amount, unit);
        }
        
        if (e.target.closest('.breakfast-combo-btn')) {
            const btn = e.target.closest('.breakfast-combo-btn');
            const combo = btn.dataset.combo;
            
            addMealCombo('breakfast', combo);
        }
        
        // Lunch buttons
        if (e.target.closest('.lunch-btn')) {
            const btn = e.target.closest('.lunch-btn');
            const food = btn.dataset.food;
            const amount = btn.dataset.amount;
            const unit = btn.dataset.unit;
            
            addMealFood('lunch', food, amount, unit);
        }
        
        if (e.target.closest('.lunch-combo-btn')) {
            const btn = e.target.closest('.lunch-combo-btn');
            const combo = btn.dataset.combo;
            
            addMealCombo('lunch', combo);
        }
        
        if (e.target.closest('.dinner-btn')) {
            const btn = e.target.closest('.dinner-btn');
            const food = btn.dataset.food;
            const amount = btn.dataset.amount;
            const unit = btn.dataset.unit;
            
            addMealFood('dinner', food, amount, unit);
        }
        
        if (e.target.closest('.dinner-combo-btn')) {
            const btn = e.target.closest('.dinner-combo-btn');
            const combo = btn.dataset.combo;
            
            addMealCombo('dinner', combo);
        }
        
        if (e.target.closest('.snacks-btn')) {
            const btn = e.target.closest('.snacks-btn');
            const food = btn.dataset.food;
            const amount = btn.dataset.amount;
            const unit = btn.dataset.unit;
            
            addMealFood('snacks', food, amount, unit);
        }
        
        if (e.target.closest('.snacks-combo-btn')) {
            const btn = e.target.closest('.snacks-combo-btn');
            const combo = btn.dataset.combo;
            
            addMealCombo('snacks', combo);
        }
        
        if (e.target.closest('.lunch-meal-btn')) {
            const btn = e.target.closest('.lunch-meal-btn');
            const meal = btn.dataset.meal;
            
            addDetailedLunchMeal(meal);
        }
    });
}

// Quick add food function
function addQuickFood(foodName) {
    // Map food names to database keys
    const foodMapping = {
        'apple': 'apple',
        'banana': 'banana', 
        'chicken breast': 'chicken_breast',
        'rice': 'rice',
        'eggs': 'eggs',
        'bread': 'whole_grain_bread',
        'milk': 'milk',
        'yogurt': 'greek_yogurt'
    };
    
    const foodKey = foodMapping[foodName.toLowerCase()];
    if (!foodKey || !foodDatabase[foodKey]) {
        showNotification('Food not found in database', 'warning');
        return;
    }
    
    const food = foodDatabase[foodKey];
    
    // Set default portion sizes for quick add
    const defaultPortions = {
        'apple': { amount: 1, unit: 'medium' },
        'banana': { amount: 1, unit: 'medium' },
        'chicken_breast': { amount: 100, unit: 'g' },
        'rice': { amount: 100, unit: 'g' },
        'eggs': { amount: 2, unit: 'large' },
        'whole_grain_bread': { amount: 2, unit: 'slice' },
        'milk': { amount: 250, unit: 'ml' },
        'greek_yogurt': { amount: 150, unit: 'g' }
    };
    
    const portion = defaultPortions[foodKey] || { amount: 100, unit: 'g' };
    
    // Add to current meal
    addMealFoodFromLocal(food, portion.amount, portion.unit);
}

// Add meal combo function
function addMealCombo(mealType, comboType) {
    console.log('addMealCombo called:', { mealType, comboType });
    
    // Ensure we're on the correct meal type
    document.getElementById(mealType).checked = true;
    currentMealType = mealType;
    
    // Define combo meals with their components
    const comboMeals = {
        'protein_bowl': {
            name: 'Protein Bowl',
            foods: [
                { key: 'chicken_breast', amount: 150, unit: 'g' },
                { key: 'brown_rice', amount: 100, unit: 'g' },
                { key: 'broccoli', amount: 100, unit: 'g' }
            ]
        },
        'salmon_meal': {
            name: 'Salmon Meal',
            foods: [
                { key: 'salmon', amount: 120, unit: 'g' },
                { key: 'quinoa', amount: 80, unit: 'g' },
                { key: 'mixed_greens', amount: 100, unit: 'g' }
            ]
        },
        'veggie_power': {
            name: 'Veggie Power',
            foods: [
                { key: 'tofu', amount: 150, unit: 'g' },
                { key: 'sweet_potato', amount: 1, unit: 'medium' },
                { key: 'spinach', amount: 50, unit: 'g' }
            ]
        },
        'mediterranean_lunch': {
            name: 'Mediterranean Lunch',
            foods: [
                { key: 'white_fish', amount: 120, unit: 'g' },
                { key: 'olive_oil', amount: 1, unit: 'tbsp' },
                { key: 'tomatoes', amount: 100, unit: 'g' }
            ]
        },
        'power_salad': {
            name: 'Power Salad',
            foods: [
                { key: 'mixed_greens', amount: 100, unit: 'g' },
                { key: 'chicken_breast', amount: 100, unit: 'g' },
                { key: 'almonds', amount: 25, unit: 'g' }
            ]
        },
        'wrap_lunch': {
            name: 'Wrap Lunch',
            foods: [
                { key: 'whole_grain_tortilla', amount: 1, unit: 'large' },
                { key: 'turkey_breast', amount: 100, unit: 'g' },
                { key: 'lettuce_wrap', amount: 2, unit: 'large' }
            ]
        },
        'beef_dinner': {
            name: 'Beef Dinner',
            foods: [
                { key: 'lean_beef', amount: 120, unit: 'g' },
                { key: 'wild_rice', amount: 100, unit: 'g' },
                { key: 'roasted_potatoes', amount: 150, unit: 'g' }
            ]
        },
        'fish_meal': {
            name: 'Fish Meal',
            foods: [
                { key: 'white_fish', amount: 150, unit: 'g' },
                { key: 'buckwheat', amount: 80, unit: 'g' },
                { key: 'broccoli', amount: 100, unit: 'g' }
            ]
        },
        'vegetarian_feast': {
            name: 'Vegetarian Feast',
            foods: [
                { key: 'lentils', amount: 100, unit: 'g' },
                { key: 'quinoa', amount: 80, unit: 'g' },
                { key: 'spinach', amount: 50, unit: 'g' }
            ]
        },
        'protein_snack': {
            name: 'Protein Snack',
            foods: [
                { key: 'greek_yogurt', amount: 150, unit: 'g' },
                { key: 'almonds', amount: 25, unit: 'g' }
            ]
        },
        'energy_snack': {
            name: 'Energy Snack',
            foods: [
                { key: 'banana', amount: 1, unit: 'medium' },
                { key: 'peanut_butter', amount: 1, unit: 'tbsp' }
            ]
        },
        'healthy_snack': {
            name: 'Healthy Snack',
            foods: [
                { key: 'apple', amount: 1, unit: 'medium' },
                { key: 'cheese', amount: 30, unit: 'g' }
            ]
        }
    };
    
    const combo = comboMeals[comboType];
    if (!combo) {
        showNotification('Combo meal not found', 'warning');
        return;
    }
    
    // Add each food in the combo
    combo.foods.forEach(food => {
        const foodData = foodDatabase[food.key];
        if (foodData) {
            addMealFoodFromLocal(foodData, food.amount, food.unit);
        }
    });
    
    showNotification(`${combo.name} added to ${mealType}!`, 'success');
}

// Add detailed lunch meal function
function addDetailedLunchMeal(mealType) {
    console.log('addDetailedLunchMeal called:', { mealType });
    
    // Define detailed meal plans
    const detailedMeals = {
        'grilled_chicken_rice_bowl': {
            name: 'Grilled Chicken & Brown Rice Bowl',
            foods: [
                { key: 'chicken_breast', amount: 200, unit: 'g' },
                { key: 'brown_rice', amount: 150, unit: 'g' },
                { key: 'broccoli', amount: 100, unit: 'g' }
            ]
        },
        'egg_sweet_potato_bowl': {
            name: 'Egg & Sweet Potato Power Bowl',
            foods: [
                { key: 'eggs', amount: 4, unit: 'large' },
                { key: 'sweet_potato', amount: 200, unit: 'g' },
                { key: 'spinach', amount: 50, unit: 'g' },
                { key: 'bell_peppers', amount: 100, unit: 'g' }
            ]
        },
        'salmon_quinoa_plate': {
            name: 'Salmon & Quinoa Plate',
            foods: [
                { key: 'salmon', amount: 180, unit: 'g' },
                { key: 'quinoa', amount: 120, unit: 'g' },
                { key: 'broccoli', amount: 100, unit: 'g' }
            ]
        },
        'chicken_wraps': {
            name: 'Chicken Wraps',
            foods: [
                { key: 'chicken_breast', amount: 150, unit: 'g' },
                { key: 'whole_grain_tortilla', amount: 2, unit: 'large' },
                { key: 'lettuce_wrap', amount: 2, unit: 'large' }
            ]
        },
        'lean_beef_rice_bowl': {
            name: 'Lean Beef Rice Bowl',
            foods: [
                { key: 'lean_beef', amount: 200, unit: 'g' },
                { key: 'brown_rice', amount: 150, unit: 'g' },
                { key: 'broccoli', amount: 100, unit: 'g' }
            ]
        },
        'paneer_veggie_stirfry': {
            name: 'Paneer & Veggie Stir-Fry',
            foods: [
                { key: 'tofu', amount: 150, unit: 'g' },
                { key: 'brown_rice', amount: 100, unit: 'g' },
                { key: 'bell_peppers', amount: 100, unit: 'g' },
                { key: 'broccoli', amount: 100, unit: 'g' }
            ]
        },
        'tuna_pasta_salad': {
            name: 'Tuna Pasta Salad',
            foods: [
                { key: 'white_fish', amount: 120, unit: 'g' },
                { key: 'whole_grain_pasta', amount: 100, unit: 'g' },
                { key: 'cucumber', amount: 100, unit: 'g' },
                { key: 'tomatoes', amount: 100, unit: 'g' }
            ]
        },
        'chicken_avocado_salad': {
            name: 'Chicken & Avocado Salad',
            foods: [
                { key: 'chicken_breast', amount: 200, unit: 'g' },
                { key: 'mixed_greens', amount: 100, unit: 'g' },
                { key: 'avocado', amount: 0.5, unit: 'medium' },
                { key: 'cucumber', amount: 100, unit: 'g' }
            ]
        },
        'egg_fried_rice': {
            name: 'Egg Fried Rice (Healthy)',
            foods: [
                { key: 'eggs', amount: 3, unit: 'large' },
                { key: 'brown_rice', amount: 150, unit: 'g' },
                { key: 'bell_peppers', amount: 100, unit: 'g' }
            ]
        },
        'grilled_turkey_potato': {
            name: 'Grilled Turkey & Potato Plate',
            foods: [
                { key: 'turkey_breast', amount: 200, unit: 'g' },
                { key: 'roasted_potatoes', amount: 200, unit: 'g' },
                { key: 'broccoli', amount: 100, unit: 'g' }
            ]
        }
    };
    
    const meal = detailedMeals[mealType];
    if (!meal) {
        showNotification('Detailed meal not found', 'warning');
        return;
    }
    
    // Add each food in the detailed meal
    meal.foods.forEach(food => {
        const foodData = foodDatabase[food.key];
        if (foodData) {
            addMealFoodFromLocal(foodData, food.amount, food.unit);
        }
    });
    
    showNotification(`${meal.name} added to lunch!`, 'success');
}

// Generic function to add meal food
function addMealFood(mealType, foodKey, amount, unit) {
    console.log('addMealFood called:', { mealType, foodKey, amount, unit });
    
    // Ensure we're on the correct meal type
    document.getElementById(mealType).checked = true;
    currentMealType = mealType;
    
    // Try to find food in local database first
    const food = foodDatabase[foodKey];
    if (food) {
        console.log('Found food in local database:', food);
        addMealFoodFromLocal(food, amount, unit);
        return;
    }
    
    console.log('Food not found in local database, searching API for:', foodKey);
    
    // If not found locally, search in database API
    fetch(`../php/food_api.php?action=search&q=${encodeURIComponent(foodKey)}&limit=1`)
        .then(response => response.json())
        .then(data => {
            if (data.error || !data.foods || data.foods.length === 0) {
                showNotification('Food not found in database', 'warning');
                return;
            }
            
            const apiFood = data.foods[0];
            addMealFoodFromAPI(apiFood, amount, unit);
        })
        .catch(error => {
            console.error('Error fetching food from API:', error);
            showNotification('Error fetching food data', 'error');
        });
}

function addMealFoodFromLocal(food, amount, unit) {
    console.log('addMealFoodFromLocal called:', { food, amount, unit });
    
    // Convert amount to grams for calculation
    const convertedAmount = convertPortionToGrams(parseFloat(amount), unit, food.name);
    console.log('Converted amount to grams:', convertedAmount);
    
    // Calculate nutrition
    const calories = (food.caloriesPer100g * convertedAmount) / 100;
    const protein = (food.protein * convertedAmount) / 100;
    const carbs = (food.carbs * convertedAmount) / 100;
    const fats = (food.fats * convertedAmount) / 100;
    
    console.log('Calculated nutrition:', { calories, protein, carbs, fats });
    
    // Create food data
    const foodData = {
        id: Date.now() + Math.random(),
        name: food.name,
        amount: `${amount} ${unit}`,
        calories: Math.round(calories),
        protein: Math.round(protein),
        carbs: Math.round(carbs),
        fats: Math.round(fats),
        fiber: Math.round((food.fiber * convertedAmount) / 100),
        sugar: Math.round((food.sugar * convertedAmount) / 100)
    };
    
    // Add to daily meals
    dailyMeals[currentMealType].push(foodData);
    
    // Update display
    updateCurrentMealDisplay();
    updateDailyTotals();
    
    // Show success notification
    showNotification(`${food.name} added to ${currentMealType}!`, 'success');
}

function addMealFoodFromAPI(apiFood, amount, unit) {
    // Convert amount to grams for calculation
    const convertedAmount = convertPortionToGrams(parseFloat(amount), unit, apiFood.name);
    
    // Calculate nutrition
    const calories = (apiFood.calories_per_100g * convertedAmount) / 100;
    const protein = (apiFood.protein * convertedAmount) / 100;
    const carbs = (apiFood.carbs * convertedAmount) / 100;
    const fats = (apiFood.fats * convertedAmount) / 100;
    
    // Create food data
    const foodData = {
        id: Date.now() + Math.random(),
        name: apiFood.name,
        amount: `${amount} ${unit}`,
        calories: Math.round(calories),
        protein: Math.round(protein),
        carbs: Math.round(carbs),
        fats: Math.round(fats),
        fiber: Math.round((apiFood.fiber * convertedAmount) / 100),
        sugar: Math.round((apiFood.sugar * convertedAmount) / 100),
        category: apiFood.category,
        foodId: apiFood.id // Store database ID for future reference
    };
    
    // Add to daily meals
    dailyMeals[currentMealType].push(foodData);
    
    // Update display
    updateCurrentMealDisplay();
    updateDailyTotals();
    
    // Show success notification
    showNotification(`${apiFood.name} added to ${currentMealType}!`, 'success');
}

// Generic function to add meal combo
function addMealCombo(mealType, comboType) {
    // Ensure we're on the correct meal type
    document.getElementById(mealType).checked = true;
    currentMealType = mealType;
    
    const combos = {
        // Lunch combos
        protein_bowl: [
            { food: 'chicken_breast', amount: '150', unit: 'g' },
            { food: 'brown_rice', amount: '100', unit: 'g' },
            { food: 'broccoli', amount: '100', unit: 'g' }
        ],
        salmon_meal: [
            { food: 'salmon', amount: '120', unit: 'g' },
            { food: 'quinoa', amount: '80', unit: 'g' },
            { food: 'spinach', amount: '50', unit: 'g' }
        ],
        veggie_power: [
            { food: 'tofu', amount: '150', unit: 'g' },
            { food: 'brown_rice', amount: '100', unit: 'g' },
            { food: 'bell_peppers', amount: '100', unit: 'g' }
        ],
        mediterranean_lunch: [
            { food: 'white_fish', amount: '120', unit: 'g' },
            { food: 'olive_oil', amount: '1', unit: 'tbsp' },
            { food: 'tomatoes', amount: '100', unit: 'g' },
            { food: 'cucumber', amount: '100', unit: 'g' }
        ],
        power_salad: [
            { food: 'mixed_greens', amount: '100', unit: 'g' },
            { food: 'chicken_breast', amount: '100', unit: 'g' },
            { food: 'almonds', amount: '25', unit: 'g' },
            { food: 'avocado', amount: '0.5', unit: 'medium' }
        ],
        wrap_lunch: [
            { food: 'whole_grain_tortilla', amount: '1', unit: 'large' },
            { food: 'turkey_breast', amount: '100', unit: 'g' },
            { food: 'mixed_greens', amount: '50', unit: 'g' },
            { food: 'tomatoes', amount: '50', unit: 'g' }
        ],
        
        // Dinner combos
        beef_dinner: [
            { food: 'lean_beef', amount: '120', unit: 'g' },
            { food: 'wild_rice', amount: '100', unit: 'g' },
            { food: 'broccoli', amount: '100', unit: 'g' }
        ],
        fish_meal: [
            { food: 'white_fish', amount: '150', unit: 'g' },
            { food: 'roasted_potatoes', amount: '150', unit: 'g' },
            { food: 'spinach', amount: '50', unit: 'g' }
        ],
        vegetarian_feast: [
            { food: 'lentils', amount: '100', unit: 'g' },
            { food: 'wild_rice', amount: '100', unit: 'g' },
            { food: 'bell_peppers', amount: '100', unit: 'g' }
        ],
        
        // Snacks combos
        protein_snack: [
            { food: 'greek_yogurt', amount: '150', unit: 'g' },
            { food: 'berries', amount: '100', unit: 'g' }
        ],
        energy_snack: [
            { food: 'apple', amount: '1', unit: 'medium' },
            { food: 'almonds', amount: '25', unit: 'g' }
        ],
        healthy_snack: [
            { food: 'banana', amount: '1', unit: 'medium' },
            { food: 'walnuts', amount: '25', unit: 'g' }
        ]
    };
    
    const combo = combos[comboType];
    if (!combo) {
        showNotification('Combo not found', 'warning');
        return;
    }
    
    // Add each food in the combo
    combo.forEach(item => {
        addMealFood(mealType, item.food, item.amount, item.unit);
    });
    
    // Show combo success notification
    const comboNames = {
        protein_bowl: 'Protein Bowl',
        salmon_meal: 'Salmon Meal',
        veggie_power: 'Veggie Power',
        mediterranean_lunch: 'Mediterranean Lunch',
        power_salad: 'Power Salad',
        wrap_lunch: 'Wrap Lunch',
        beef_dinner: 'Beef Dinner',
        fish_meal: 'Fish Meal',
        vegetarian_feast: 'Vegetarian Feast',
        protein_snack: 'Protein Snack',
        energy_snack: 'Energy Snack',
        healthy_snack: 'Healthy Snack'
    };
    
    showNotification(`${comboNames[comboType]} combo added to ${mealType}!`, 'success');
}

// Function to add detailed lunch meals
function addDetailedLunchMeal(mealType) {
    // Ensure we're on lunch meal type
    document.getElementById('lunch').checked = true;
    currentMealType = 'lunch';
    
    const detailedMeals = {
        grilled_chicken_rice_bowl: [
            { food: 'chicken_breast', amount: '200', unit: 'g' },
            { food: 'brown_rice', amount: '150', unit: 'g' },
            { food: 'broccoli', amount: '100', unit: 'g' },
            { food: 'carrots', amount: '100', unit: 'g' },
            { food: 'olive_oil', amount: '1', unit: 'tbsp' }
        ],
        egg_sweet_potato_bowl: [
            { food: 'eggs', amount: '4', unit: 'large' },
            { food: 'sweet_potato', amount: '200', unit: 'g' },
            { food: 'spinach', amount: '50', unit: 'g' },
            { food: 'bell_peppers', amount: '100', unit: 'g' },
            { food: 'butter', amount: '1', unit: 'tsp' }
        ],
        salmon_quinoa_plate: [
            { food: 'salmon', amount: '180', unit: 'g' },
            { food: 'quinoa', amount: '120', unit: 'g' },
            { food: 'asparagus', amount: '100', unit: 'g' },
            { food: 'zucchini', amount: '100', unit: 'g' }
        ],
        chicken_wraps: [
            { food: 'chicken_breast', amount: '150', unit: 'g' },
            { food: 'whole_grain_tortilla', amount: '2', unit: 'large' },
            { food: 'lettuce_wrap', amount: '50', unit: 'g' },
            { food: 'tomatoes', amount: '100', unit: 'g' },
            { food: 'onion', amount: '50', unit: 'g' },
            { food: 'greek_yogurt', amount: '1', unit: 'tbsp' }
        ],
        lean_beef_rice_bowl: [
            { food: 'lean_beef', amount: '200', unit: 'g' },
            { food: 'rice', amount: '150', unit: 'g' },
            { food: 'green_beans', amount: '100', unit: 'g' },
            { food: 'mushrooms', amount: '100', unit: 'g' }
        ],
        paneer_veggie_stirfry: [
            { food: 'paneer', amount: '150', unit: 'g' },
            { food: 'brown_rice', amount: '100', unit: 'g' },
            { food: 'bell_peppers', amount: '100', unit: 'g' },
            { food: 'broccoli', amount: '100', unit: 'g' },
            { food: 'olive_oil', amount: '1', unit: 'tbsp' }
        ],
        tuna_pasta_salad: [
            { food: 'tuna', amount: '120', unit: 'g' },
            { food: 'whole_grain_pasta', amount: '100', unit: 'g' },
            { food: 'cucumber', amount: '100', unit: 'g' },
            { food: 'tomatoes', amount: '100', unit: 'g' },
            { food: 'spinach', amount: '50', unit: 'g' },
            { food: 'olive_oil', amount: '1', unit: 'tbsp' }
        ],
        chicken_avocado_salad: [
            { food: 'chicken_breast', amount: '200', unit: 'g' },
            { food: 'mixed_greens', amount: '100', unit: 'g' },
            { food: 'cucumber', amount: '100', unit: 'g' },
            { food: 'cherry_tomatoes', amount: '100', unit: 'g' },
            { food: 'avocado', amount: '0.5', unit: 'medium' },
            { food: 'olive_oil', amount: '1', unit: 'tbsp' }
        ],
        egg_fried_rice: [
            { food: 'eggs', amount: '3', unit: 'large' },
            { food: 'egg_whites', amount: '3', unit: 'large' },
            { food: 'basmati_rice', amount: '150', unit: 'g' },
            { food: 'peas', amount: '50', unit: 'g' },
            { food: 'carrots', amount: '100', unit: 'g' },
            { food: 'sesame_oil', amount: '1', unit: 'tsp' }
        ],
        grilled_turkey_potato: [
            { food: 'turkey_breast', amount: '200', unit: 'g' },
            { food: 'potatoes', amount: '200', unit: 'g' },
            { food: 'green_beans', amount: '100', unit: 'g' },
            { food: 'broccoli', amount: '100', unit: 'g' },
            { food: 'butter', amount: '1', unit: 'tbsp' }
        ]
    };
    
    const meal = detailedMeals[mealType];
    if (!meal) {
        showNotification('Meal not found', 'warning');
        return;
    }
    
    // Add each food in the meal
    meal.forEach(item => {
        addMealFood('lunch', item.food, item.amount, item.unit);
    });
    
    // Show meal success notification
    const mealNames = {
        grilled_chicken_rice_bowl: 'Grilled Chicken & Brown Rice Bowl',
        egg_sweet_potato_bowl: 'Egg & Sweet Potato Power Bowl',
        salmon_quinoa_plate: 'Salmon & Quinoa Plate',
        chicken_wraps: 'Chicken Wraps',
        lean_beef_rice_bowl: 'Lean Beef Rice Bowl',
        paneer_veggie_stirfry: 'Paneer & Veggie Stir-Fry',
        tuna_pasta_salad: 'Tuna Pasta Salad',
        chicken_avocado_salad: 'Chicken & Avocado Salad',
        egg_fried_rice: 'Egg Fried Rice (Healthy)',
        grilled_turkey_potato: 'Grilled Turkey & Potato Plate'
    };
    
    showNotification(`${mealNames[mealType]} added to lunch!`, 'success');
}

// Generate comprehensive BMR report
function generateBmrReport() {
    const bmr = userProfile.bmr;
    const tdee = userProfile.tdee;
    const goalCalories = userProfile.goalCalories;
    const age = userProfile.age;
    const gender = userProfile.gender;
    const weight = userProfile.weight;
    const height = userProfile.height;
    const activityLevel = userProfile.activityLevel;
    const fitnessGoal = userProfile.fitnessGoal;
    
    // Calculate BMI
    const bmi = weight / Math.pow(height / 100, 2);
    
    // Update report overview cards
    document.getElementById('reportBmrValue').textContent = bmr;
    document.getElementById('reportTdeeValue').textContent = tdee;
    document.getElementById('reportGoalCalories').textContent = goalCalories;
    
    // Calculate calorie difference
    const calorieDifference = goalCalories - tdee;
    document.getElementById('reportCalorieDifference').textContent = Math.abs(calorieDifference);
    
    // Update goal type and difference type
    let goalType = '';
    let differenceType = '';
    switch (fitnessGoal) {
        case 'cutting':
            goalType = 'Weight Loss';
            differenceType = 'Deficit';
            break;
        case 'maintenance':
            goalType = 'Maintenance';
            differenceType = 'vs TDEE';
            break;
        case 'bulking':
            goalType = 'Weight Gain';
            differenceType = 'Surplus';
            break;
    }
    document.getElementById('reportGoalType').textContent = goalType;
    document.getElementById('reportDifferenceType').textContent = differenceType;
    
    // Update metabolic analysis with professional insights
    const metabolicCategory = getMetabolicCategory(bmr, age, gender);
    const activityLevelText = getActivityLevelText(activityLevel);
    const bmiStatus = getBmiStatus(bmi);
    const bodyComposition = getBodyComposition(bmi, gender);
    
    // Update metabolic analysis elements with text and color classes
    const metabolicCategoryEl = document.getElementById('metabolicCategory');
    if (metabolicCategoryEl) {
        metabolicCategoryEl.textContent = metabolicCategory.text;
        metabolicCategoryEl.className = `analysis-value ${metabolicCategory.class}`;
        metabolicCategoryEl.classList.add('loaded');
    }
    
    const activityLevelEl = document.getElementById('activityLevelText');
    if (activityLevelEl) {
        activityLevelEl.textContent = activityLevelText.text;
        activityLevelEl.className = `analysis-value ${activityLevelText.class}`;
        activityLevelEl.classList.add('loaded');
    }
    
    const bmiStatusEl = document.getElementById('bmiStatus');
    if (bmiStatusEl) {
        bmiStatusEl.textContent = bmiStatus.text;
        bmiStatusEl.className = `analysis-value ${bmiStatus.class}`;
        bmiStatusEl.classList.add('loaded');
    }
    
    const bodyCompositionEl = document.getElementById('bodyComposition');
    if (bodyCompositionEl) {
        bodyCompositionEl.textContent = bodyComposition.text;
        bodyCompositionEl.className = `analysis-value ${bodyComposition.class}`;
        bodyCompositionEl.classList.add('loaded');
    }
    
    // Update professional insights
    updateProfessionalInsights(bmr, tdee, goalCalories, fitnessGoal, age, gender, bmi);
    
    // Update goal analysis with enhanced data
    updateGoalAnalysis(bmr, tdee, goalCalories, fitnessGoal, weight, height, age, gender, bmi);
    
    // Update recommendations
    updateRecommendations(fitnessGoal, bmi, activityLevel);
    
    // Update insights
    updateInsights(bmr, tdee, fitnessGoal);
    
    // Create progress chart
    createBmrProgressChart();
}

// Helper functions for BMR report
function getMetabolicCategory(bmr, age, gender) {
    const avgBmr = gender === 'male' ? 1800 : 1400;
    if (bmr > avgBmr * 1.1) return { text: 'High Metabolism', class: 'text-success' };
    if (bmr < avgBmr * 0.9) return { text: 'Low Metabolism', class: 'text-warning' };
    return { text: 'Average Metabolism', class: 'text-info' };
}

function getActivityLevelText(activityLevel) {
    const levels = {
        '1.2': { text: 'Sedentary', class: 'text-warning' },
        '1.375': { text: 'Lightly Active', class: 'text-info' },
        '1.55': { text: 'Moderately Active', class: 'text-success' },
        '1.725': { text: 'Very Active', class: 'text-success' },
        '1.9': { text: 'Extra Active', class: 'text-success' }
    };
    return levels[activityLevel] || { text: 'Unknown', class: 'text-warning' };
}

// Professional insights function
function updateProfessionalInsights(bmr, tdee, goalCalories, fitnessGoal, age, gender, bmi) {
    // Update metabolic insight
    const metabolicInsight = getMetabolicInsight(bmr, age, gender);
    document.getElementById('metabolicInsight').textContent = metabolicInsight;
    
    // Update energy balance insight
    const energyInsight = getEnergyBalanceInsight(fitnessGoal, tdee, goalCalories);
    document.getElementById('energyInsight').textContent = energyInsight;
    
    // Update nutritional insight
    const nutritionalInsight = getNutritionalInsight(fitnessGoal, bmi, age);
    document.getElementById('nutritionalInsight').textContent = nutritionalInsight;
}

function getMetabolicInsight(bmr, age, gender) {
    const avgBmr = gender === 'male' ? 1800 : 1400;
    const bmrRatio = bmr / avgBmr;
    
    if (bmrRatio > 1.1) {
        return `Your BMR of ${bmr} calories is above average, indicating a naturally higher metabolic rate. This suggests efficient energy utilization and may support your fitness goals.`;
    } else if (bmrRatio < 0.9) {
        return `Your BMR of ${bmr} calories is below average. Focus on building lean muscle mass through resistance training to boost your metabolic rate naturally.`;
    } else {
        return `Your BMR of ${bmr} calories falls within the normal range for your age and gender. This provides a solid foundation for achieving your fitness objectives.`;
    }
}

function getEnergyBalanceInsight(fitnessGoal, tdee, goalCalories) {
    const difference = Math.abs(goalCalories - tdee);
    
    switch (fitnessGoal) {
        case 'cutting':
            return `Your ${difference} calorie deficit is optimal for sustainable weight loss. This approach promotes fat loss while preserving muscle mass.`;
        case 'bulking':
            return `Your ${difference} calorie surplus supports lean muscle growth. Monitor progress weekly and adjust as needed for optimal results.`;
        case 'maintenance':
            return `Maintaining energy balance at ${tdee} calories will help preserve your current body composition while supporting overall health.`;
        default:
            return `Balanced nutrition at your TDEE level supports optimal health and performance.`;
    }
}

function getNutritionalInsight(fitnessGoal, bmi, age) {
    let insight = '';
    
    if (fitnessGoal === 'cutting' && bmi > 25) {
        insight = 'Focus on nutrient-dense foods with high protein content to support satiety and muscle preservation during your weight loss journey.';
    } else if (fitnessGoal === 'bulking') {
        insight = 'Prioritize quality protein sources and complex carbohydrates to fuel muscle growth and recovery effectively.';
    } else if (age > 40) {
        insight = 'Consider increasing protein intake and incorporating resistance training to combat age-related muscle loss and maintain metabolic health.';
    } else {
        insight = 'A balanced approach with adequate protein, healthy fats, and complex carbohydrates will support your overall health and fitness goals.';
    }
    
    return insight;
}


function getBmiStatus(bmi) {
    if (bmi < 18.5) return { text: 'Underweight', class: 'text-warning' };
    if (bmi < 25) return { text: 'Normal Weight', class: 'text-success' };
    if (bmi < 30) return { text: 'Overweight', class: 'text-warning' };
    return { text: 'Obese', class: 'text-danger' };
}

function getBodyComposition(bmi, gender) {
    if (bmi < 18.5) return { text: 'Low Body Fat', class: 'text-warning' };
    if (bmi < 25) return { text: 'Healthy Body Fat', class: 'text-success' };
    if (bmi < 30) return { text: 'Elevated Body Fat', class: 'text-warning' };
    return { text: 'High Body Fat', class: 'text-danger' };
}

function getWeightChangeRate(fitnessGoal) {
    switch (fitnessGoal) {
        case 'cutting': return '0.5-1.0 kg/week (1-2 lbs)';
        case 'bulking': return '0.25-0.5 kg/week (0.5-1 lb)';
        default: return 'Maintain current weight';
    }
}

function getTimeToGoal(fitnessGoal, weight) {
    const bmi = userProfile.weight / Math.pow(userProfile.height / 100, 2);
    
    switch (fitnessGoal) {
        case 'cutting':
            if (bmi > 30) return '20-30 weeks';
            if (bmi > 25) return '12-20 weeks';
            return '8-12 weeks';
        case 'bulking':
            if (bmi < 18.5) return '16-24 weeks';
            return '12-20 weeks';
        default: return 'Ongoing maintenance';
    }
}

function getMacroDistribution() {
    const proteinPercent = Math.round((dailyMacroGoals.protein * 4 / userProfile.goalCalories) * 100);
    const carbPercent = Math.round((dailyMacroGoals.carbs * 4 / userProfile.goalCalories) * 100);
    const fatPercent = Math.round((dailyMacroGoals.fats * 9 / userProfile.goalCalories) * 100);
    
    return `${dailyMacroGoals.protein}g Protein (${proteinPercent}%) | ${dailyMacroGoals.carbs}g Carbs (${carbPercent}%) | ${dailyMacroGoals.fats}g Fats (${fatPercent}%)`;
}

function getRecommendedApproach(fitnessGoal, bmi) {
    if (fitnessGoal === 'cutting' && bmi > 30) return 'Aggressive deficit (750-1000 cal) with cardio focus';
    if (fitnessGoal === 'cutting' && bmi > 25) return 'Moderate deficit (500-750 cal) with strength training';
    if (fitnessGoal === 'cutting') return 'Conservative deficit (250-500 cal) with balanced training';
    if (fitnessGoal === 'bulking' && bmi < 18.5) return 'Aggressive surplus (500-750 cal) with heavy lifting';
    if (fitnessGoal === 'bulking') return 'Moderate surplus (250-500 cal) with progressive overload';
    return 'Balanced approach with regular exercise and maintenance calories';
}

function updateGoalAnalysis(bmr, tdee, goalCalories, fitnessGoal, weight, height, age, gender, bmi) {
    // Add loading state to analysis content
    const analysisContents = document.querySelectorAll('.analysis-content');
    analysisContents.forEach(content => {
        content.classList.add('loading');
    });
    
    // Calculate weight change rate
    const weightChangeRate = getWeightChangeRate(fitnessGoal);
    
    // Calculate time to goal
    const timeToGoal = getTimeToGoal(fitnessGoal, weight);
    
    // Calculate macro distribution with percentages
    const macroDistribution = getMacroDistribution();
    
    // Get recommended approach
    const recommendedApproach = getRecommendedApproach(fitnessGoal, bmi);
    
    // Update the DOM elements
    const weightChangeRateEl = document.getElementById('weightChangeRate');
    const timeToGoalEl = document.getElementById('timeToGoal');
    const macroDistributionEl = document.getElementById('macroDistribution');
    const recommendedApproachEl = document.getElementById('recommendedApproach');
    
    if (weightChangeRateEl) {
        weightChangeRateEl.textContent = weightChangeRate;
        weightChangeRateEl.classList.add('loaded');
    }
    if (timeToGoalEl) {
        timeToGoalEl.textContent = timeToGoal;
        timeToGoalEl.classList.add('loaded');
    }
    if (macroDistributionEl) {
        macroDistributionEl.textContent = macroDistribution;
        macroDistributionEl.classList.add('loaded');
    }
    if (recommendedApproachEl) {
        recommendedApproachEl.textContent = recommendedApproach;
        recommendedApproachEl.classList.add('loaded');
    }
    
    // Add visual indicators for goal analysis
    updateGoalAnalysisVisuals(fitnessGoal, bmi);
    
    // Remove loading state and add loaded state
    setTimeout(() => {
        analysisContents.forEach(content => {
            content.classList.remove('loading');
            content.classList.add('loaded');
        });
    }, 300);
}

function updateGoalAnalysisVisuals(fitnessGoal, bmi) {
    // Add color coding based on goal type
    const analysisItems = document.querySelectorAll('#weightChangeRate, #timeToGoal, #macroDistribution, #recommendedApproach');
    
    analysisItems.forEach(item => {
        if (item) {
            // Remove existing classes
            item.classList.remove('text-success', 'text-warning', 'text-info', 'text-primary');
            
            // Add appropriate color based on goal
            switch (fitnessGoal) {
                case 'cutting':
                    item.classList.add('text-success');
                    break;
                case 'bulking':
                    item.classList.add('text-warning');
                    break;
                case 'maintenance':
                    item.classList.add('text-info');
                    break;
                default:
                    item.classList.add('text-primary');
            }
        }
    });
}

function updateRecommendations(fitnessGoal, bmi, activityLevel) {
    const nutritionRecs = document.getElementById('nutritionRecommendations');
    const exerciseRecs = document.getElementById('exerciseRecommendations');
    const lifestyleRecs = document.getElementById('lifestyleRecommendations');
    
    // Nutrition recommendations
    let nutritionItems = [];
    if (fitnessGoal === 'cutting') {
        nutritionItems = [
            'Focus on high-protein, low-calorie foods',
            'Increase fiber intake for satiety',
            'Stay hydrated to support metabolism',
            'Consider intermittent fasting if suitable'
        ];
    } else if (fitnessGoal === 'bulking') {
        nutritionItems = [
            'Prioritize lean protein sources',
            'Include complex carbohydrates',
            'Healthy fats for hormone production',
            'Eat every 3-4 hours consistently'
        ];
    } else {
        nutritionItems = [
            'Focus on whole, unprocessed foods',
            'Maintain consistent meal timing',
            'Stay hydrated throughout the day',
            'Balance all macronutrients'
        ];
    }
    
    nutritionRecs.innerHTML = nutritionItems.map(item => `<li>${item}</li>`).join('');
    
    // Exercise recommendations
    let exerciseItems = [];
    if (activityLevel <= 1.375) {
        exerciseItems = [
            'Start with 30 minutes of moderate activity daily',
            'Include both cardio and strength training',
            'Gradually increase intensity and duration',
            'Focus on consistency over intensity'
        ];
    } else {
        exerciseItems = [
            'Include both cardio and strength training',
            'Aim for 150+ minutes of moderate activity weekly',
            'Prioritize recovery and sleep',
            'Consider periodization for optimal results'
        ];
    }
    
    exerciseRecs.innerHTML = exerciseItems.map(item => `<li>${item}</li>`).join('');
    
    // Lifestyle recommendations
    const lifestyleItems = [
        'Get 7-9 hours of quality sleep',
        'Manage stress through relaxation techniques',
        'Track progress consistently',
        'Stay consistent with your routine'
    ];
    
    lifestyleRecs.innerHTML = lifestyleItems.map(item => `<li>${item}</li>`).join('');
}

function updateInsights(bmr, tdee, fitnessGoal) {
    document.getElementById('metabolicInsight').textContent = 
        `Your BMR of ${bmr} calories represents your baseline metabolic needs at rest.`;
    
    document.getElementById('energyInsight').textContent = 
        `With your activity level, you burn ${tdee} calories daily. Maintaining proper energy balance is key to your ${fitnessGoal} goals.`;
    
    document.getElementById('timelineInsight').textContent = 
        'Consistent adherence to your calorie and macro targets will yield optimal results over time.';
}

function createBmrProgressChart() {
    const ctx = document.getElementById('bmrProgressChart');
    if (!ctx) return;
    
    const bmr = userProfile.bmr;
    const tdee = userProfile.tdee;
    const goalCalories = userProfile.goalCalories;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['BMR', 'TDEE', 'Goal Calories'],
            datasets: [{
                data: [bmr, tdee, goalCalories],
                backgroundColor: [
                    'rgba(13, 110, 253, 0.8)',
                    'rgba(25, 135, 84, 0.8)',
                    'rgba(255, 193, 7, 0.8)'
                ],
                borderColor: [
                    'rgba(13, 110, 253, 1)',
                    'rgba(25, 135, 84, 1)',
                    'rgba(255, 193, 7, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed.y + ' calories';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Calories per Day'
                    }
                }
            }
        }
    });
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .navbar.scrolled {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    
    .navbar-toggler-icon.rotate {
        transform: rotate(90deg);
        transition: transform 0.3s ease;
    }
    
    .form-control.focused {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    
    .btn.loading {
        position: relative;
        pointer-events: none;
    }
    
    .btn.loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 20px;
        height: 20px;
        margin: -10px 0 0 -10px;
        border: 2px solid transparent;
        border-top: 2px solid currentColor;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);

// Load today's meals for the logged-in user and update Daily Goals
function loadTodaysMealsAndTotals() {
    try {
        const isInPagesDir = window.location.pathname.indexOf('/pages/') !== -1;
        const baseUrl = (isInPagesDir ? '../php/meal_history_api.php' : 'php/meal_history_api.php');
        // Load saved goals first
        fetch(baseUrl + '?action=get_goals', { credentials: 'include' })
            .then(res => res.json())
            .then(g => {
                if (g && g.success && g.goals) {
                    // Apply saved goals to drive Daily Goals UI
                    if (g.goals.goal_calories) { dailyCalorieGoal = parseInt(g.goals.goal_calories); }
                    dailyMacroGoals = {
                        protein: parseInt(g.goals.protein_goal || dailyMacroGoals.protein),
                        carbs: parseInt(g.goals.carb_goal || dailyMacroGoals.carbs),
                        fats: parseInt(g.goals.fat_goal || dailyMacroGoals.fats)
                    };
                    userProfile.goalCalories = dailyCalorieGoal;
                    if (document.getElementById('goalCalories')) {
                        document.getElementById('goalCalories').textContent = dailyCalorieGoal;
                    }
                    if (document.getElementById('targetProtein')) {
                        document.getElementById('targetProtein').textContent = dailyMacroGoals.protein + 'g';
                    }
                    if (document.getElementById('targetCarbs')) {
                        document.getElementById('targetCarbs').textContent = dailyMacroGoals.carbs + 'g';
                    }
                    if (document.getElementById('targetFats')) {
                        document.getElementById('targetFats').textContent = dailyMacroGoals.fats + 'g';
                    }
                }
            })
            .catch(() => {})
            .finally(() => {
                // Then load today's meals
                fetch(baseUrl + '?action=get_todays_meals', { credentials: 'include' })
            .then(res => res.json())
            .then(payload => {
                if (!payload || !payload.success || !payload.meals) {
                    return;
                }
                // Reset current meals
                dailyMeals = { breakfast: [], lunch: [], dinner: [], snacks: [] };
                ['breakfast','lunch','dinner','snacks'].forEach(mealType => {
                    const foods = payload.meals[mealType] || [];
                    foods.forEach(f => {
                        dailyMeals[mealType].push({
                            id: Date.now() + Math.random(),
                            name: f.name,
                            amount: f.amount + ' ' + (f.unit || ''),
                            calories: Math.round(Number(f.calories) || 0),
                            protein: Math.round(Number(f.protein) || 0),
                            carbs: Math.round(Number(f.carbs) || 0),
                            fats: Math.round(Number(f.fats) || 0),
                            fiber: Math.round(Number(f.fiber) || 0),
                            sugar: Math.round(Number(f.sugar) || 0)
                        });
                    });
                });
                // Reflect UI
                if (typeof updateCurrentMealDisplay === 'function') {
                    updateCurrentMealDisplay();
                }
                if (typeof updateDailyTotals === 'function') {
                    updateDailyTotals();
                }
            })
            .catch(() => {});
            });
    } catch (e) {}
}

// Initialize from server data on load
document.addEventListener('DOMContentLoaded', function() {
    loadTodaysMealsAndTotals();
});
