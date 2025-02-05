<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';

try {
    // Get database connection
    $db = Database::getInstance()->getConnection();
    
    // Test the connection
    if ($db->connect_error) {
        die("Connection failed: " . $db->connect_error);
    }
    echo "Database connection successful!\n";
    
    // Check if the database exists
    $result = $db->query("SELECT DATABASE()");
    $row = $result->fetch_row();
    echo "Current database: " . $row[0] . "\n";
    
    // Check if users table exists and has the admin user
    $stmt = $db->prepare("SELECT id, username, email, role, status FROM users WHERE email = ?");
    $email = 'phini@gmail.com';
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "Found user:\n";
        echo "ID: " . $user['id'] . "\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Role: " . $user['role'] . "\n";
        echo "Status: " . $user['status'] . "\n";
    } else {
        echo "No user found with email: " . $email . "\n";
    }
    
    // Check the password hash in the database
    $stmt = $db->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "Password hash: " . $row['password'] . "\n";
        
        // Test password verification
        $testPassword = 'phini@1234';
        if (password_verify($testPassword, $row['password'])) {
            echo "Password verification successful!\n";
        } else {
            echo "Password verification failed!\n";
        }
    }
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
