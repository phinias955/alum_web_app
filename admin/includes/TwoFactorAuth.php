<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorAuth {
    private $google2fa;
    private $qrWriter;
    private $db;
    private $issuer = 'Alumni Portal';

    public function __construct() {
        $this->google2fa = new Google2FA();
        
        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new SvgImageBackEnd()
        );
        $this->qrWriter = new Writer($renderer);
        $this->db = Database::getInstance();
    }

    /**
     * Enable 2FA for a user
     */
    public function enable2FA(int $userId): array {
        $secretKey = $this->generateSecretKey();
        $recoveryCodes = $this->generateRecoveryCodes();
        
        $stmt = $this->db->prepare("
            UPDATE users 
            SET two_factor_secret = ?, 
                two_factor_enabled = TRUE,
                recovery_codes = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$secretKey, json_encode($recoveryCodes), $userId]);
        
        return [
            'secret' => $secretKey,
            'qrcode' => $this->generateQRCode(
                $this->getUserEmail($userId),
                $this->getUserEmail($userId),
                $secretKey
            ),
            'recovery_codes' => $recoveryCodes
        ];
    }

    /**
     * Verify 2FA code
     */
    public function verifyCode(int $userId, string $code): bool {
        $secretKey = $this->getUserSecret($userId);
        $result = $this->verifyKey($secretKey, $code);
        
        // Log the verification attempt
        $this->logVerificationAttempt($userId, 'app', $result);
        
        return $result;
    }

    /**
     * Verify recovery code
     */
    public function verifyRecoveryCode(int $userId, string $code): bool {
        $stmt = $this->db->prepare("SELECT recovery_codes FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $recoveryCodes = json_decode($stmt->fetchColumn(), true);
        
        if (!$recoveryCodes) {
            return false;
        }
        
        // Check if the code exists and hasn't been used
        $codeKey = array_search($code, array_column($recoveryCodes, 'code'));
        if ($codeKey === false || $recoveryCodes[$codeKey]['used']) {
            return false;
        }
        
        // Mark the code as used
        $recoveryCodes[$codeKey]['used'] = true;
        $stmt = $this->db->prepare("UPDATE users SET recovery_codes = ? WHERE id = ?");
        $stmt->execute([json_encode($recoveryCodes), $userId]);
        
        // Log the verification attempt
        $this->logVerificationAttempt($userId, 'recovery_code', true);
        
        return true;
    }

    /**
     * Disable 2FA for a user
     */
    public function disable2FA(int $userId): bool {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET two_factor_secret = NULL,
                two_factor_enabled = FALSE,
                recovery_codes = NULL
            WHERE id = ?
        ");
        
        return $stmt->execute([$userId]);
    }

    /**
     * Check if user has 2FA enabled
     */
    public function isEnabled(int $userId): bool {
        $stmt = $this->db->prepare("
            SELECT two_factor_enabled 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Generate new recovery codes
     */
    private function generateRecoveryCodes(int $count = 8): array {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = [
                'code' => $this->generateRecoveryCode(),
                'used' => false
            ];
        }
        return $codes;
    }

    /**
     * Generate a single recovery code
     */
    private function generateRecoveryCode(int $length = 10): string {
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }

    /**
     * Get user's 2FA secret
     */
    private function getUserSecret(int $userId): string {
        $stmt = $this->db->prepare("SELECT two_factor_secret FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    /**
     * Get user's email
     */
    private function getUserEmail(int $userId): string {
        $stmt = $this->db->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    /**
     * Log 2FA verification attempt
     */
    private function logVerificationAttempt(int $userId, string $type, bool $success): void {
        $stmt = $this->db->prepare("
            INSERT INTO two_factor_logs 
            (user_id, verification_type, success, ip_address) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $type,
            $success,
            Security::getClientIP()
        ]);
    }

    public function generateSecretKey() {
        return $this->google2fa->generateSecretKey();
    }

    public function getQRCodeUrl($name, $email, $secretKey) {
        return $this->google2fa->getQRCodeUrl(
            $name,
            $email,
            $secretKey
        );
    }

    public function generateQRCode($name, $email, $secretKey) {
        $url = $this->getQRCodeUrl($name, $email, $secretKey);
        return $this->qrWriter->writeString($url);
    }

    public function verifyKey($secretKey, $code) {
        return $this->google2fa->verifyKey($secretKey, $code);
    }
}
?>
