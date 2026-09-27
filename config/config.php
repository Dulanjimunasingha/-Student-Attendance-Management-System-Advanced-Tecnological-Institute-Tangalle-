<?php
/**
 * Configuration File
 * Advanced Technological Institute of Tangalle - Attendance Management System
 */

// ============================================================================
// ENVIRONMENT SETTINGS
// ============================================================================
define('APP_ENV', 'development');

// ============================================================================
// SECURITY SETTINGS
// ============================================================================
define('FORCE_HTTPS', false);
define('SESSION_COOKIE_SECURE', false);
define('SESSION_COOKIE_HTTPONLY', true);
define('SESSION_COOKIE_SAMESITE', 'Lax');
define('SESSION_TIMEOUT', 1800);
define('CSRF_TOKEN_LENGTH', 32);
define('STUDENT_VERIFICATION_MAX_ATTEMPTS', 5);
define('STUDENT_VERIFICATION_LOCK_MINUTES', 15);

// ============================================================================
// APPLICATION SETTINGS
// ============================================================================

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Base URL - UPDATE THIS TO MATCH YOUR SETUP
define('BASE_URL', 'http://localhost/atit-attendly-main/php-site');

// Site Configuration
define('SITE_NAME', 'Advanced Technological Institute of Tangalle');
define('SITE_SHORT_NAME', 'ATIT');
define('SYSTEM_VERSION', '1.0.0');

// Institution details
define('INSTITUTION_NAME', 'Advanced Technological Institute of Tangalle');
define('INSTITUTION_EMAIL', 'noreply@atit.edu.lk');

// Default attendance settings
define('DEFAULT_MIN_ATTENDANCE', 75);
define('LATE_COUNT_PERCENTAGE', 50);
define('EXCUSED_COUNT_FULL', true);

// Time settings
define('TIMEZONE', 'Asia/Colombo');
date_default_timezone_set(TIMEZONE);

// Pagination
define('ITEMS_PER_PAGE', 20);

// Paths
define('ROOT_PATH', dirname(__DIR__) . '/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('ASSETS_PATH', ROOT_PATH . 'assets/');

// Export paths
define('EXPORT_PATH', ROOT_PATH . 'exports/');
define('PDF_PATH', EXPORT_PATH . 'pdf/');
define('EXCEL_PATH', EXPORT_PATH . 'excel/');