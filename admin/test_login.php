<?php
require_once 'config/config.php';
require_once 'config/Database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Connect to database
    $db = Database::getInstance()->getConnection();
    
    // Check admin user
    $email = 'phini@gmail.com';
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    echo "User details:\n";
    if ($user) {
        echo "Found user with email: " . $user['email'] . "\n";
        echo "Status: " . $user['status'] . "\n";
        echo "Role: " . $user['role'] . "\n";
        
        // Test password verification
        $password = 'phini@1234';
        if (password_verify($password, $user['password'])) {
            echo "Password verification: SUCCESS\n";
        } else {
            echo "Password verification: FAILED\n";
            
            // Generate new hash for reference
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            echo "New hash for 'phini@1234': " . $new_hash . "\n";
        }
    } else {
        echo "No user found with email: " . $email . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
