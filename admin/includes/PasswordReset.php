<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/NotificationController.php';

class PasswordReset {
    private $db;
    private $notification;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->notification = new NotificationController();
    }
    
    public function createResetToken($email) {
        try {
            // Verify user exists
            $user = $this->getUserByEmail($email);
            if (!$user) {
                throw new Exception("No account found with this email address");
            }
            
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Save token
            $stmt = $this->db->prepare("
                INSERT INTO password_reset_tokens (user_id, token, expires_at)
                VALUES (?, ?, ?)
            ");
            
            $stmt->bind_param('iss', $user['id'], $token, $expiresAt);
            if (!$stmt->execute()) {
                throw new Exception("Failed to create reset token");
            }
            
            // Send email
            $this->notification->sendPasswordResetLink($email, $token);
            
            return true;
        } catch (Exception $e) {
            error_log("Create Reset Token Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function validateToken($token) {
        try {
            $stmt = $this->db->prepare("
                SELECT user_id, expires_at, used 
                FROM password_reset_tokens 
                WHERE token = ? 
                LIMIT 1
            ");
            
            $stmt->bind_param('s', $token);
            $stmt->execute();
            $result = $stmt->get_result();
            $tokenData = $result->fetch_assoc();
            
            if (!$tokenData) {
                throw new Exception("Invalid reset token");
            }
            
            if ($tokenData['used']) {
                throw new Exception("This reset token has already been used");
            }
            
            if (strtotime($tokenData['expires_at']) < time()) {
                throw new Exception("This reset token has expired");
            }
            
            return $tokenData['user_id'];
        } catch (Exception $e) {
            error_log("Validate Token Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function resetPassword($token, $newPassword) {
        try {
            $userId = $this->validateToken($token);
            
            // Start transaction
            $this->db->begin_transaction();
            
            // Update password
            $hashedPassword = password_hash($newPassword . PASSWORD_PEPPER, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("
                UPDATE users 
                SET password = ?, 
                    updated_at = NOW() 
                WHERE id = ?
            ");
            
            $stmt->bind_param('si', $hashedPassword, $userId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update password");
            }
            
            // Mark token as used
            $stmt = $this->db->prepare("
                UPDATE password_reset_tokens 
                SET used = 1 
                WHERE token = ?
            ");
            
            $stmt->bind_param('s', $token);
            if (!$stmt->execute()) {
                throw new Exception("Failed to mark token as used");
            }
            
            // Log the event
            $this->logPasswordReset($userId);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Reset Password Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function getUserByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    private function logPasswordReset($userId) {
        $stmt = $this->db->prepare("
            INSERT INTO activity_logs (user_id, action, description)
            VALUES (?, 'password_reset', 'Password was reset using reset token')
        ");
        
        $stmt->bind_param('i', $userId);
        return $stmt->execute();
    }
}
?>
