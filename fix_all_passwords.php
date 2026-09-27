<?php
/**
 * Fix all passwords in the database
 * Run this once: http://localhost/atit-attendly-main/php-site/fix_all_passwords.php
 */

require_once __DIR__ . '/config/database.php';

echo "<pre>";
echo "Fixing Passwords\n";
echo "================\n\n";

try {
    $db = getDB();
    
    // Generate proper bcrypt hashes
    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    $lecturerHash = password_hash('lecturer123', PASSWORD_DEFAULT);
    
    echo "Admin hash: $adminHash\n";
    echo "Lecturer hash: $lecturerHash\n\n";
    
    // Update admin
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$adminHash]);
    echo "✓ Admin password updated (admin / admin123)\n";
    
    // Update lecturers
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE role_id = 2");
    $stmt->execute([$lecturerHash]);
    echo "✓ Lecturer passwords updated (lecturer123)\n";
    
    // Verify
    echo "\nVerifying...\n";
    $stmt = $db->query("SELECT id, username, email, password FROM users");
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        echo "User: {$user['username']} ({$user['email']})\n";
        echo "  Hash starts with: " . substr($user['password'], 0, 10) . "...\n";
        
        // Test admin
        if ($user['username'] === 'admin') {
            $valid = password_verify('admin123', $user['password']);
            echo "  admin123 valid: " . ($valid ? "YES ✓" : "NO ✗") . "\n";
        } else {
            $valid = password_verify('lecturer123', $user['password']);
            echo "  lecturer123 valid: " . ($valid ? "YES ✓" : "NO ✗") . "\n";
        }
    }
    
    echo "\n✓ All passwords fixed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>