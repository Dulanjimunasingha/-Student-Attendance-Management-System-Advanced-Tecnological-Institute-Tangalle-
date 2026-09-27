<?php
/**
 * Logout Handler
 * Advanced Technological Institute of Tangalle - Attendance Management System
 */

require_once __DIR__ . '/../config/config.php'; 
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Log out the user (handles all user types: admin, lecturer, student)
logoutUser();

// Restart session to store flash after logout session is destroyed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set success message
$_SESSION['flash'] = [
    'type' => 'success',
    'message' => 'You have been logged out successfully.'
];

// Redirect to login page
header("Location: " . BASE_URL . "/auth/login.php");
exit;
