# HealthMate - AI-Powered Fitness Application

A comprehensive, responsive web application for personalized fitness tracking and workout management. Built with modern web technologies including HTML5, CSS3, Bootstrap 5, JavaScript, PHP, and MySQL.

## 🎯 Features

### Core Features
- **User Authentication System** - Secure registration and login with password hashing
- **Personalized Dashboard** - User profile, progress tracking, and fitness statistics
- **AI-Powered Workout Recommendations** - Dynamic workout suggestions based on fitness goals
- **Progress Tracking** - Visual charts and analytics for weight, calories, and workout progress
- **Gamification System** - Points, badges, achievements, and leaderboards
- **Responsive Design** - Mobile-first design that works on all devices

### Technical Features
- **Modern UI/UX** - Beautiful, intuitive interface with Bootstrap 5
- **Real-time Charts** - Interactive progress visualization with Chart.js
- **Secure Backend** - PHP with MySQL database and prepared statements
- **Session Management** - Secure user sessions and authentication
- **Form Validation** - Client and server-side validation
- **AJAX Integration** - Smooth, asynchronous user interactions

## 🚀 Quick Start

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) or PHP built-in server
- Modern web browser

### Installation

1. **Clone or Download the Project**
   ```bash
   git clone <repository-url>
   cd fitness-app
   ```

2. **Set Up Database**
   - Create a MySQL database named `healthmate_db`
   - Import the database schema:
   ```bash
   mysql -u root -p healthmate_db < db/schema.sql
   ```

3. **Configure Database Connection**
   - Edit `php/config.php` and update database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'healthmate_db');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

4. **Start the Application**
   - Using PHP built-in server:
   ```bash
   php -S localhost:8000
   ```
   - Or configure your web server to point to the project directory

5. **Access the Application**
   - Open your browser and navigate to `http://localhost:8000`
   - Register a new account or use the demo account:
     - Username: `demo_user`
     - Password: `test123`

## 📁 Project Structure

```
fitness-app/
├── assets/
│   ├── css/
│   │   └── style.css          # Custom styles
│   └── js/
│       └── main.js            # Main JavaScript functionality
├── db/
│   └── schema.sql             # Database schema and sample data
├── pages/
│   ├── dashboard.php          # User dashboard
│   ├── workouts.php           # Workout management
│   ├── leaderboard.php        # Gamification leaderboard
│   └── achievements.php       # User achievements
├── php/
│   ├── config.php             # Database configuration
│   ├── auth.php               # Authentication system
│   └── feedback.php           # Feedback form handler
├── index.html                 # Landing page
└── README.md                  # This file
```

## 🎨 Design Features

### Color Scheme
- **Primary**: Green (#2ecc71) - Represents health and vitality
- **Secondary**: Dark Green (#27ae60) - Complementary primary
- **Accent**: Orange (#f39c12) - For highlights and achievements
- **Dark**: Navy (#2c3e50) - For text and backgrounds

### UI Components
- **Hero Section** - Eye-catching landing page with gradient background
- **Card Layouts** - Clean, modern card-based design
- **Progress Charts** - Interactive data visualization
- **Responsive Navigation** - Mobile-friendly navigation menu
- **Modal Dialogs** - Smooth authentication and workout timers

## 🔧 Technical Implementation

### Frontend Technologies
- **HTML5** - Semantic markup and modern structure
- **CSS3** - Custom styling with CSS Grid and Flexbox
- **Bootstrap 5** - Responsive framework and components
- **JavaScript (ES6+)** - Modern JavaScript with async/await
- **Chart.js** - Interactive charts and data visualization

### Backend Technologies
- **PHP 7.4+** - Server-side logic and API endpoints
- **MySQL** - Relational database for data persistence
- **PDO** - Secure database connections with prepared statements
- **Sessions** - User authentication and state management

### Security Features
- **Password Hashing** - bcrypt password encryption
- **SQL Injection Prevention** - Prepared statements
- **XSS Protection** - Input sanitization
- **CSRF Protection** - Form token validation
- **Session Security** - Secure session management

## 📊 Database Schema

### Core Tables
- **users** - User accounts and profiles
- **workouts** - Workout definitions and metadata
- **workout_exercises** - Individual exercises within workouts
- **user_progress** - User progress tracking
- **feedback** - User feedback and support requests
- **achievements** - Gamification achievements
- **user_achievements** - User achievement tracking

## 🎮 Gamification System

### Points System
- **Workout Completion**: 50 points per workout
- **Consistency Streaks**: Bonus points for daily workouts
- **Achievements**: Points for unlocking badges
- **Leaderboards**: Competitive ranking system

### Achievements
- **First Workout** - Complete your first workout
- **Week Warrior** - Complete 7 workouts in a week
- **Weight Loss Champion** - Lose 5 pounds
- **Strength Builder** - Complete 20 strength workouts
- **Consistency King** - Workout for 30 consecutive days

## 📱 Responsive Design

### Breakpoints
- **Mobile**: < 768px - Single column layout
- **Tablet**: 768px - 991px - Two column layout
- **Desktop**: > 992px - Full multi-column layout

### Mobile Features
- Touch-friendly navigation
- Swipe gestures for workout cards
- Optimized forms for mobile input
- Responsive charts and data visualization

## 🔄 Workflow

### User Journey
1. **Landing Page** - User discovers the application
2. **Registration** - Create account with fitness goals
3. **Dashboard** - View personalized stats and recommendations
4. **Workouts** - Browse and start workout routines
5. **Progress Tracking** - Monitor fitness journey
6. **Gamification** - Earn points and achievements
7. **Community** - Compete on leaderboards

### Admin Features
- User management
- Workout content management
- Achievement system configuration
- Analytics and reporting

## 🚀 Deployment

### Production Setup
1. **Web Server Configuration**
   - Configure Apache/Nginx for PHP
   - Set up SSL certificates
   - Configure URL rewriting

2. **Database Optimization**
   - Enable query caching
   - Optimize database indexes
   - Set up database backups

3. **Security Hardening**
   - Update database credentials
   - Configure firewall rules
   - Enable HTTPS only

### Environment Variables
```bash
DB_HOST=your_database_host
DB_NAME=healthmate_db
DB_USER=your_database_user
DB_PASS=your_database_password
```

## 🧪 Testing

### Manual Testing Checklist
- [ ] User registration and login
- [ ] Dashboard functionality
- [ ] Workout recommendations
- [ ] Progress tracking
- [ ] Gamification features
- [ ] Responsive design
- [ ] Form validation
- [ ] Error handling

### Browser Compatibility
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## 🤝 Contributing

### Development Setup
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

### Code Standards
- Follow PSR-12 PHP coding standards
- Use meaningful variable and function names
- Add comments for complex logic
- Maintain consistent indentation

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🙏 Acknowledgments

- **Bootstrap** - For the responsive framework
- **Chart.js** - For data visualization
- **Font Awesome** - For icons
- **PHP Community** - For excellent documentation

## 📞 Support

For support and questions:
- Create an issue in the repository
- Email: support@healthmate.com
- Documentation: [docs.healthmate.com](https://docs.healthmate.com)

---

**HealthMate** - Transform your fitness journey with AI-powered personalized recommendations! 💪
