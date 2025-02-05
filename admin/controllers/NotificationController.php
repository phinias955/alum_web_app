<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/BaseController.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class NotificationController extends BaseController {
    private $mailer;
    
    public function __construct() {
        parent::__construct('notifications');
        $this->initializeMailer();
    }
    
    private function initializeMailer() {
        $this->mailer = new PHPMailer(true);
        
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = 'smtp.gmail.com';
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = 'your-email@gmail.com'; // Replace with your email
            $this->mailer->Password = 'your-app-password'; // Replace with your app password
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = 587;
            
            // Default sender
            $this->mailer->setFrom('your-email@gmail.com', 'Alumni Portal');
        } catch (Exception $e) {
            error_log("Mailer initialization error: " . $e->getMessage());
        }
    }
    
    public function sendEmail($to, $subject, $body) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $this->getEmailTemplate($body);
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    private function getEmailTemplate($content) {
        return "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #f8f9fa; padding: 20px; text-align: center; }
                    .content { padding: 20px; }
                    .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Alumni Portal</h2>
                    </div>
                    <div class='content'>
                        $content
                    </div>
                    <div class='footer'>
                        <p>This is an automated message, please do not reply.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
    }
    
    public function sendSecurityAlert($userId, $event, $details) {
        try {
            $user = $this->getUserById($userId);
            if (!$user) {
                throw new Exception("User not found");
            }
            
            $subject = "Security Alert - " . ucfirst($event);
            $body = "
                <h3>Security Alert</h3>
                <p>We detected a security-related event on your account:</p>
                <p><strong>Event:</strong> " . htmlspecialchars($event) . "</p>
                <p><strong>Details:</strong> " . htmlspecialchars($details) . "</p>
                <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
                <p>If you did not initiate this action, please contact the administrator immediately.</p>
            ";
            
            return $this->sendEmail($user['email'], $subject, $body);
        } catch (Exception $e) {
            error_log("Security alert error: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendPasswordResetLink($email, $token) {
        try {
            $resetLink = ADMIN_URL . '/reset-password.php?token=' . urlencode($token);
            $subject = "Password Reset Request";
            $body = "
                <h3>Password Reset Request</h3>
                <p>We received a request to reset your password. Click the link below to proceed:</p>
                <p><a href='$resetLink'>Reset Password</a></p>
                <p>If you did not request this, please ignore this email.</p>
                <p>This link will expire in 1 hour.</p>
            ";
            
            return $this->sendEmail($email, $subject, $body);
        } catch (Exception $e) {
            error_log("Password reset email error: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendLoginAlert($userId, $ipAddress, $userAgent) {
        try {
            $user = $this->getUserById($userId);
            if (!$user) {
                throw new Exception("User not found");
            }
            
            $subject = "New Login Alert";
            $body = "
                <h3>New Login Detected</h3>
                <p>A new login was detected on your account:</p>
                <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
                <p><strong>IP Address:</strong> " . htmlspecialchars($ipAddress) . "</p>
                <p><strong>Browser:</strong> " . htmlspecialchars($userAgent) . "</p>
                <p>If this wasn't you, please change your password immediately and contact the administrator.</p>
            ";
            
            return $this->sendEmail($user['email'], $subject, $body);
        } catch (Exception $e) {
            error_log("Login alert error: " . $e->getMessage());
            return false;
        }
    }
    
    private function getUserById($userId) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}
?>
