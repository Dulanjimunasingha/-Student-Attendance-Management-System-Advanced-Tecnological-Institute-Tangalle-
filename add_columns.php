<?php
require_once __DIR__ . '/config/database.php';

$db = getDB();

try {
    $db->exec("ALTER TABLE students ADD COLUMN verification_field VARCHAR(50)");
    echo "Added verification_field column\n";
} catch (Exception $e) {
    echo "verification_field: " . $e->getMessage() . "\n";
} 

try {
    $db->exec("ALTER TABLE students ADD COLUMN date_of_birth DATE");
    echo "Added date_of_birth column\n";
} catch (Exception $e) {
    echo "date_of_birth: " . $e->getMessage() . "\n";
}

try {
    $db->exec("ALTER TABLE students ADD COLUMN nic VARCHAR(20)");
    echo "Added nic column\n";
} catch (Exception $e) {
    echo "nic: " . $e->getMessage() . "\n";
}

try {
    $db->exec("ALTER TABLE students ADD COLUMN phone VARCHAR(20)");
    echo "Added phone column\n";
} catch (Exception $e) {
    echo "phone: " . $e->getMessage() . "\n";
}

try {
    $db->exec("ALTER TABLE students ADD COLUMN image_path VARCHAR(255)");
    echo "Added image_path column\n";
} catch (Exception $e) {
    echo "image_path: " . $e->getMessage() . "\n";
}

echo "Done!";