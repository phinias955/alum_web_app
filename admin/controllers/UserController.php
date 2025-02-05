<?php
require_once 'BaseController.php';
require_once __DIR__ . '/../includes/TwoFactorAuth.php';
require_once __DIR__ . '/../includes/PasswordReset.php';
require_once __DIR__ . '/../includes/Security.php';

class UserController extends BaseController {
    private $twoFactorAuth;
    private $passwordReset;
    
    public function __construct() {
        parent::__construct('users');
        $this->twoFactorAuth = new TwoFactorAuth();
        $this->passwordReset = new PasswordReset();
    }

    public function createUser($data) {
        try {
            $this->validateUserData($data);

            // Check if email already exists
            if ($this->emailExists($data['email'])) {
                throw new Exception("Email already exists");
            }

            // Hash password with pepper
            $data['password'] = Security::hashPassword($data['password']);
            $data['created_at'] = date('Y-m-d H:i:s');
            
            $userId = $this->create($data);
            $this->logActivity($userId, "account_created", "User account created");

            return $userId;
        } catch (Exception $e) {
            error_log("Create User Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateUser($id, $data) {
        try {
            $this->validateUserData($data, true);
            $existingUser = $this->getById($id);

            if (!$existingUser) {
                throw new Exception("User not found");
            }

            // Check if email is being changed and if it exists
            if (isset($data['email']) && $data['email'] !== $existingUser['email'] && $this->emailExists($data['email'])) {
                throw new Exception("Email already exists");
            }

            $result = $this->update($id, $data);
            $this->logActivity($id, "profile_updated", "User profile updated");

            return $result;
        } catch (Exception $e) {
            error_log("Update User Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            $user = $this->getById($userId);
            if (!$user) {
                throw new Exception("User not found");
            }

            // Verify current password
            if (!Security::verifyPassword($currentPassword, $user['password'])) {
                throw new Exception("Current password is incorrect");
            }

            // Validate new password
            if (strlen($newPassword) < 8) {
                throw new Exception("Password must be at least 8 characters long");
            }

            // Hash new password
            $hashedPassword = Security::hashPassword($newPassword);
            
            $stmt = $this->db->prepare("
                UPDATE users 
                SET password = ?, 
                    updated_at = NOW() 
                WHERE id = ?
            ");
            
            $stmt->bind_param('si', $hashedPassword, $userId);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $this->logActivity($userId, "password_changed", "User changed their password");
                return true;
            }

            throw new Exception("Failed to update password");
        } catch (Exception $e) {
            error_log("Change Password Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function toggle2FA($userId, $enabled) {
        try {
            $user = $this->getById($userId);
            if (!$user) {
                throw new Exception("User not found");
            }

            $stmt = $this->db->prepare("
                UPDATE users 
                SET 2fa_enabled = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->bind_param('ii', $enabled, $userId);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $action = $enabled ? "enabled" : "disabled";
                $this->logActivity($userId, "2fa_" . $action, "User {$action} two-factor authentication");
                return true;
            }

            throw new Exception("Failed to update 2FA settings");
        } catch (Exception $e) {
            error_log("Toggle 2FA Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateNotificationPreferences($userId, $preferences) {
        try {
            $user = $this->getById($userId);
            if (!$user) {
                throw new Exception("User not found");
            }

            $stmt = $this->db->prepare("
                UPDATE users 
                SET notification_preferences = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $preferencesJson = json_encode($preferences);
            $stmt->bind_param('si', $preferencesJson, $userId);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $this->logActivity($userId, "preferences_updated", "User updated notification preferences");
                return true;
            }

            throw new Exception("Failed to update notification preferences");
        } catch (Exception $e) {
            error_log("Update Notification Preferences Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getNotificationPreferences($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT notification_preferences 
                FROM users 
                WHERE id = :userId
            ");
            
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && $row['notification_preferences']) {
                return json_decode($row['notification_preferences'], true);
            }

            // Return default preferences if none set
            return [
                'email_notifications' => true,
                'login_alerts' => true,
                'security_alerts' => true
            ];
        } catch (Exception $e) {
            error_log("Get Notification Preferences Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteAccount($userId, $confirmation) {
        try {
            if ($confirmation !== 'DELETE') {
                throw new Exception("Invalid confirmation text");
            }

            $user = $this->getById($userId);
            if (!$user) {
                throw new Exception("User not found");
            }

            // Start transaction
            $this->db->begin_transaction();

            // Log the deletion
            $this->logActivity($userId, "account_deleted", "User account deleted");

            // Delete the user
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();

            if ($stmt->affected_rows === 0) {
                throw new Exception("Failed to delete account");
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Delete Account Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function logActivity($userId, $action, $description) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs (user_id, action, description)
                VALUES (?, ?, ?)
            ");
            
            $stmt->bind_param('iss', $userId, $action, $description);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Activity Log Error: " . $e->getMessage());
            // Don't throw the error as this is a non-critical operation
            return false;
        }
    }

    public function getUserStatistics() {
        try {
            $stats = [
                'total_users' => 0,
                'active_users' => 0,
                'inactive_users' => 0,
                'admin_users' => 0
            ];

            // Get total users count
            $result = $this->db->query("SELECT COUNT(*) as total FROM users");
            if ($result) {
                $stats['total_users'] = $result->fetch_assoc()['total'];
            }

            // Get active users count
            $result = $this->db->query("SELECT COUNT(*) as active FROM users WHERE status = 'active'");
            if ($result) {
                $stats['active_users'] = $result->fetch_assoc()['active'];
            }

            // Get inactive users count
            $result = $this->db->query("SELECT COUNT(*) as inactive FROM users WHERE status = 'inactive'");
            if ($result) {
                $stats['inactive_users'] = $result->fetch_assoc()['inactive'];
            }

            // Get admin users count
            $result = $this->db->query("SELECT COUNT(*) as admin FROM users WHERE role = 'admin'");
            if ($result) {
                $stats['admin_users'] = $result->fetch_assoc()['admin'];
            }

            return $stats;
        } catch (Exception $e) {
            error_log("Error getting user statistics: " . $e->getMessage());
            return [
                'total_users' => 0,
                'active_users' => 0,
                'inactive_users' => 0,
                'admin_users' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    private function validateUserData($data, $isUpdate = false) {
        $requiredFields = ['username', 'email'];
        if (!$isUpdate) {
            $requiredFields[] = 'password';
        }

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("$field is required");
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        if (isset($data['password']) && strlen($data['password']) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }
    }

    private function emailExists($email) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
}
?>
