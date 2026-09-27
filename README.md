# Advanced Technological Institute of Tangalle - Attendance Management System

A complete full-stack PHP + MySQL web application for managing institute attendance.

## Features

### Admin Panel
- Dashboard with statistics and charts
- Student management (add, edit, delete, assign courses)
- Lecturer management (add, edit, delete, assign courses)
- Course management (add, edit, delete, configure requirements)
- Attendance records management
- Reports with export (CSV, Print)
- Announcements management

### Lecturer Panel
- Dashboard with course overview
- Mark attendance with lesson topics
- View attendance history
- Search students by ID
- View low attendance alerts

### Student Panel
- Dashboard with attendance overview
- Course-wise attendance details
- Attendance percentage and progress
- Personal profile view
- View announcements

## Tech Stack

- **Backend**: PHP 8+
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, Bootstrap 5
- **JavaScript**: Vanilla JS with AJAX
- **Charts**: Chart.js
- **Icons**: Font Awesome 6
- **Alerts**: SweetAlert2

## Installation

### 1. Requirements

- XAMPP / WAMP / Laragon (Apache + PHP + MySQL)
- PHP 8.0 or higher
- MySQL 5.7 or higher

### 2. Setup Steps

#### Step 1: Copy Files
Copy the entire `php-site` folder to your web server's document root:
- **XAMPP**: `C:\xampp\htdocs\`
- **WAMP**: `C:\wamp64\www\`
- **Laragon**: `C:\laragon\www\`

#### Step 2: Create Database

1. Open phpMyAdmin (usually http://localhost/phpmyadmin)

2. Click "Import" tab

3. Select the SQL file: `database/attendance_system.sql`

4. Click "Go" to import

The database `atit_attendance` will be created with all tables and sample data.

#### Step 3: Configure Database Connection

Open `config/database.php` and update credentials if needed:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'atit_attendance');
define('DB_USER', 'root');      // Your MySQL username
define('DB_PASS', '');          // Your MySQL password
```

#### Step 4: Update Base URL

Open `config/config.php` and update the BASE_URL:

```php
define('BASE_URL', 'http://localhost/php-site');
```

#### Step 5: Access the Application

Open your browser and go to:
- **Homepage**: http://localhost/php-site/
- **Login**: http://localhost/php-site/auth/login.php

## Demo Credentials

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |
| Lecturer | dr.silva | admin123 |
| Student | stu001 | admin123 |

**Note**: The password for all demo accounts is `admin123`. The SQL uses bcrypt hashing.

## Project Structure

```
php-site/
├── config/
│   ├── config.php          # Application configuration
│   └── database.php         # Database connection
├── includes/
│   ├── auth.php             # Authentication functions
│   ├── functions.php        # Helper functions
│   ├── header.php           # Header template
│   └── footer.php           # Footer template
├── assets/
│   ├── css/
│   │   └── style.css        # Custom styles
│   └── js/
│       └── app.js           # Custom JavaScript
├── auth/
│   ├── login.php            # Login page
│   └── logout.php           # Logout handler
├── admin/
│   ├── index.php            # Admin dashboard
│   ├── students.php        # Student management
│   ├── lecturers.php        # Lecturer management
│   ├── courses.php          # Course management
│   ├── attendance.php       # Attendance management
│   ├── reports.php          # Reports & exports
│   └── announcements.php    # Announcements
├── lecturer/
│   ├── index.php            # Lecturer dashboard
│   ├── mark_attendance.php  # Mark attendance
│   ├── attendance_history.php
│   └── student_search.php
├── student/
│   ├── index.php            # Student dashboard
│   ├── attendance.php       # Attendance details
│   └── profile.php          # Profile view
├── api/
│   ├── get_enrollments.php
│   ├── get_lecturer_courses.php
│   └── get_session_details.php
├── database/
│   └── attendance_system.sql # Database schema & data
└── index.php                # Public homepage
```

## Features Details

### Attendance Calculation

```
Attendance Percentage = (Present + Late×0.5 + Excused) / Total Classes × 100
```

### Attendance Status
- **Present**: Full attendance
- **Absent**: No attendance
- **Late**: 50% attendance value
- **Excused**: Full attendance (with valid reason)

### Minimum Requirements
- Default: 75% attendance required
- Can be configured per course

## Security Features

- PDO with prepared statements
- Password hashing (bcrypt)
- Session-based authentication
- Role-based access control
- Input sanitization
- XSS prevention

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Edge (latest)
- Safari (latest)

## Mobile Responsive

The system is fully responsive and works on:
- Desktop computers
- Laptops
- Tablets
- Mobile phones

## Troubleshooting

### Database Connection Error
1. Check MySQL is running
2. Verify credentials in `config/database.php`
3. Ensure database exists

### Blank Page
1. Enable error reporting in `config/config.php`:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

### Login Not Working
1. Clear browser cookies
2. Check session directory permissions
3. Verify database has users

## License

This project is developed for educational purposes.

## Support

For support, please contact the system administrator.

---

**Developed for**: Advanced Technological Institute of Tangalle  
**Version**: 1.0.0  
**Year**: 2024
