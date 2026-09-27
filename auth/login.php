<?php
/**
 * Login Page
 * Advanced Technological Institute of Tangalle - Attendance Management System
 * 
 * No OTP - Student uses Student ID + verification field
 * No email login for students
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Get requested role from URL parameter (if clicking portal buttons from homepage)
$requestedRole = sanitize($_GET['role'] ?? '');

// If accessing login page with role parameter, ensure no stale session interferes
if (!empty($requestedRole)) {
    // Log out any existing session to prevent hijacking
    if (isLoggedIn() || isStudentLoggedIn()) {
        logoutUser();
    }
}

// Redirect if already logged in (only if no role parameter was requested)
if (empty($requestedRole) && isLoggedIn()) {
    $role = $_SESSION['role_slug'];
    if ($role === 'admin') {
        redirect(BASE_URL . '/admin/index.php');
    } elseif ($role === 'lecturer') {
        redirect(BASE_URL . '/lecturer/index.php');
    }
} elseif (empty($requestedRole) && isStudentLoggedIn()) {
    redirect(BASE_URL . '/student/index.php');
}

$error = '';
$success = '';

// Handle staff login (Admin/Lecturer)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_type']) && $_POST['login_type'] === 'staff') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = sanitize($_POST['role'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username/email and password';
    } else {
        $result = loginUser($username, $password, $role ?: null);
        
        if ($result['success']) {
            $roleSlug = $result['user']['role_slug'];
            
            // Check if password change is required
            if (!empty($result['must_change_password'])) {
                // Force password change
                if ($roleSlug === 'lecturer') {
                    $_SESSION['force_password_change'] = true;
                    redirect(BASE_URL . '/lecturer/change_password.php');
                }
            }
            
            if ($roleSlug === 'admin') {
                redirect(BASE_URL . '/admin/index.php');
            } elseif ($roleSlug === 'lecturer') {
                redirect(BASE_URL . '/lecturer/index.php');
            }
        } else {
            $error = $result['message'];
        }
    }
}

// Handle student access - Student ID + verification field (single step)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_type']) && $_POST['login_type'] === 'student') {
    $studentId = sanitize($_POST['student_id'] ?? '');
    $verification = sanitize($_POST['verification'] ?? '');
    
    if (empty($studentId) || empty($verification)) {
        $error = 'Please enter both Student ID and verification details';
    } else {
        $result = verifyStudentDirect($studentId, $verification);
        
        if ($result['success']) {
            redirect(BASE_URL . '/student/index.php');
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0d6efd;
            --primary-dark: #0a58ca;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
            --dark-bg: #1a1a2e;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container { width: 100%; max-width: 480px; }
        
        .login-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--dark-bg) 0%, #1a1a2e 100%);
            padding: 30px;
            text-align: center;
            color: #fff;
        }
        
        .login-header h2 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 1.6rem;
        }
        
        .login-header p { opacity: 0.8; font-size: 14px; }
        
        .login-body { padding: 30px; }
        
        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-control {
            padding: 12px 16px;
            border-radius: 10px;
            border: 1px solid #ddd;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
        }
        
        .input-group-text {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(13, 110, 253, 0.3);
        }
        
        .btn-outline {
            border: 2px solid #ddd;
            padding: 12px;
            border-radius: 10px;
            font-weight: 500;
            background: #fff;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn-outline:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .login-tabs {
            display: flex;
            margin-bottom: 25px;
            border-bottom: 2px solid #eee;
        }
        
        .login-tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            font-weight: 500;
            color: #6c757d;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.3s;
        }
        
        .login-tab:hover { color: var(--primary-color); }
        
        .login-tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .role-select {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .role-option {
            flex: 1;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .role-option:hover { border-color: var(--primary-color); }
        
        .role-option.selected {
            border-color: var(--primary-color);
            background: rgba(13, 110, 253, 0.05);
        }
        
        .role-option i { font-size: 20px; margin-bottom: 5px; display: block; }
        .role-option span { font-size: 12px; font-weight: 500; }
        .role-option input { display: none; }
        
        .student-notice {
            background: #e7f3ff;
            border: 1px solid #0d6efd;
            border-radius: 10px;
            padding: 15px;
            font-size: 13px;
            color: #0d6efd;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .student-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .student-info .name {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }
        
        .verification-notice {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 12px;
            font-size: 12px;
            color: #856404;
            margin-bottom: 15px;
        }
        
        .alert {
            border-radius: 10px;
            padding: 14px 18px;
            font-size: 14px;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .credentials-hint {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px;
            margin-top: 15px;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2><i class="fas fa-graduation-cap me-2"></i>ATIT</h2>
                <p>Attendance Management System</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>
                
                <!-- Tab Navigation -->
                <?php 
                $isStudentTab = false;
                if (isset($_POST['login_type']) && $_POST['login_type'] === 'student') {
                    $isStudentTab = true;
                } elseif ($requestedRole === 'student') {
                    $isStudentTab = true;
                }
                ?>
                <div class="login-tabs">
                    <div class="login-tab <?php echo !$isStudentTab ? 'active' : ''; ?>" data-tab="staff">Staff Login</div>
                    <div class="login-tab <?php echo $isStudentTab ? 'active' : ''; ?>" data-tab="student">Student Access</div>
                </div>
                
                <!-- Staff Login Tab -->
                <div class="tab-content <?php echo !$isStudentTab ? 'active' : ''; ?>" id="tab-staff">
                    <form method="POST" action="">
                        <input type="hidden" name="login_type" value="staff">
                        
                        <?php 
                        // Determine selected role from POST or GET parameter
                        $selectedRole = $_POST['role'] ?? $requestedRole;
                        ?>
                        <div class="mb-3">
                            <label class="form-label">Login as</label>
                            <div class="role-select">
                                <label class="role-option <?php echo $selectedRole === 'admin' ? 'selected' : ''; ?>">
                                    <input type="radio" name="role" value="admin" <?php echo $selectedRole === 'admin' ? 'checked' : ''; ?>>
                                    <i class="fas fa-user-shield text-primary"></i>
                                    <span>Admin</span>
                                </label>
                                <label class="role-option <?php echo $selectedRole === 'lecturer' ? 'selected' : ''; ?>">
                                    <input type="radio" name="role" value="lecturer" <?php echo $selectedRole === 'lecturer' ? 'checked' : ''; ?>>
                                    <i class="fas fa-chalkboard-teacher text-success"></i>
                                    <span>Lecturer</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email or Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" placeholder="Enter your email or username" required autofocus>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" name="password" id="password" placeholder="Enter your password" required>
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </form>
                    
                    <div class="credentials-hint">
                        <!-- <strong>Admin:</strong> admin@atit.edu.lk / admin123<br>
                        <strong>Lecturer:</strong> dr.silva@atit.edu.lk / lecturer123 -->
                    </div>
                </div>
                
                <!-- Student Access Tab -->
                <div class="tab-content <?php echo $isStudentTab ? 'active' : ''; ?>" id="tab-student">
                    
                    <div class="student-notice">
                        <i class="fas fa-shield-alt me-2"></i>
                        Enter your <strong>Student ID</strong> and last 4 digits of your Date of Birth (DDMM), NIC, or Phone number to access your attendance.
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="login_type" value="student">
                        
                        <div class="mb-3">
                            <label class="form-label">Student ID</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                <input type="text" class="form-control" name="student_id" id="studentId" 
                                       placeholder="e.g., ATIT-2024-0001" required autofocus>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Verification (Last 4 digits)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="text" class="form-control" name="verification" id="verification" 
                                       placeholder="Enter last 4 digits" maxlength="4" required>
                            </div>
                            <small class="text-muted">Use last 4 digits of: DOB (e.g., 0315), NIC, or Phone</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-graduation-cap me-2"></i>Access My Attendance
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <p class="footer-text">
            &copy; <?php echo date('Y'); ?> Advanced Technological Institute of Tangalle
        </p>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Tab switching
        document.querySelectorAll('.login-tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.login-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById('tab-' + this.dataset.tab).classList.add('active');
            });
        });
        
        // Role selection
        document.querySelectorAll('.role-option').forEach(function(option) {
            option.addEventListener('click', function() {
                document.querySelectorAll('.role-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input').checked = true;
            });
        });
        
        // Toggle password
        document.querySelectorAll('.toggle-password').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const input = document.querySelector(this.dataset.target);
                const type = input.type === 'password' ? 'text' : 'password';
                input.type = type;
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        });
        
        // Verification input - only numbers
        const verificationInput = document.getElementById('verification');
        if (verificationInput) {
            verificationInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }
    </script>
</body>
</html>