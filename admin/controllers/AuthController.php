<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../includes/Security.php';

class AuthController {
    private $db;
    private $security;

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
            if (!$this->db) {
                throw new Exception('Database connection failed');
            }
            Security::initSession();
        } catch (Exception $e) {
            error_log("AuthController initialization error: " . $e->getMessage());
            throw $e;
        }
    }

    public function login($email, $password) {
        try {
            error_log("Login attempt for email: " . $email);

            // Validate email format
            if (!Security::validateEmail($email)) {
                throw new Exception('Invalid email format');
            }

            // Get user data
            $stmt = $this->db->prepare("
                SELECT id, username, email, role, password, status, failed_login_attempts 
                FROM users 
                WHERE email = :email
            ");
            
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Log the query results
            error_log("Query results for email " . $email . ": " . ($user ? "User found" : "No user found"));

            // Verify user exists and is active
            if (!$user) {
                throw new Exception('Invalid email or password');
            }

            if ($user['status'] !== 'active') {
                throw new Exception('Account is not active. Please contact administrator.');
            }

            // Verify password
            if (!password_verify($password, $user['password'])) {
                error_log("Password verification failed for email: " . $email);
                
                // Update failed login attempts
                $stmt = $this->db->prepare("
                    UPDATE users 
                    SET failed_login_attempts = failed_login_attempts + 1 
                    WHERE id = :id
                ");
                
                $stmt->bindValue(':id', $user['id'], PDO::PARAM_INT);
                $stmt->execute();

                throw new Exception('Invalid email or password');
            }

            // Reset failed attempts and update last login
            $stmt = $this->db->prepare("
                UPDATE users 
                SET failed_login_attempts = 0,
                    last_login_at = CURRENT_TIMESTAMP,
                    last_login_ip = :ip
                WHERE id = :id
            ");

            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt->bindValue(':ip', $ip, PDO::PARAM_STR);
            $stmt->bindValue(':id', $user['id'], PDO::PARAM_INT);
            $stmt->execute();

            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // Regenerate session ID for security
            session_regenerate_id(true);

            error_log("Successful login for email: " . $email);
            return true;

        } catch (Exception $e) {
            error_log("Login error in AuthController: " . $e->getMessage());
            throw $e;
        }
    }

    public function logout() {
        if (isset($_SESSION['user_id'])) {
            Security::logActivity($_SESSION['user_id'], 'User logged out');
        }
        
        $_SESSION = array();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-42000, '/');
        }
        session_destroy();
    }

    public function resetPassword($email) {
        try {
            if (!Security::validateEmail($email)) {
                throw new Exception("Invalid email format");
            }

            $token = Security::generateToken();
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $this->db->prepare("UPDATE users SET reset_token = :token, reset_expires = :expires WHERE email = :email AND status = 'active'");
            $stmt->bindValue(':token', $token, PDO::PARAM_STR);
            $stmt->bindValue(':expires', $expires, PDO::PARAM_STR);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Here you would typically send an email with the reset link
                // For now, we'll just return the token
                return $token;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Password Reset Error: " . $e->getMessage());
            throw new Exception("Password reset failed");
        }
    }

    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            $stmt = $this->db->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && Security::verifyPassword($currentPassword, $user['password'])) {
                $hashedPassword = Security::hashPassword($newPassword);
                $stmt = $this->db->prepare("
                    UPDATE users 
                    SET password = :password,
                        password_changed_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
                $stmt->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
                $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
                $stmt->execute();
                Security::logActivity($userId, 'Password changed');
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Change Password Error: " . $e->getMessage());
            throw new Exception("Password change failed");
        }
    }
}
