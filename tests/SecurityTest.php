<?php
use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase {
    private $security;
    private $db;

    protected function setUp(): void {
        parent::setUp();
        require_once __DIR__ . '/../admin/includes/Security.php';
        require_once __DIR__ . '/../admin/config/config.php';
        
        $this->security = new Security();
        $this->db = Database::getInstance();
        
        // Clean up test data
        $this->db->exec("DELETE FROM login_attempts WHERE username LIKE 'test%'");
        $this->db->exec("DELETE FROM rate_limits WHERE request_key LIKE 'test%'");
    }

    public function testLoginAttemptLimiting() {
        $username = 'test_user_' . time();
        
        // Should allow initial attempts
        for ($i = 0; $i < 5; $i++) {
            try {
                Security::checkLoginAttempts($username);
                Security::recordLoginAttempt($username, false);
                $this->assertTrue(true);
            } catch (Exception $e) {
                $this->fail('Should not throw exception before limit');
            }
        }

        // Should block after limit reached
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Too many login attempts');
        Security::checkLoginAttempts($username);
    }

    public function testRateLimiting() {
        $key = 'test_key_' . time();
        
        // Should allow initial requests
        for ($i = 0; $i < 100; $i++) {
            try {
                Security::checkRateLimit($key);
                $this->assertTrue(true);
            } catch (Exception $e) {
                $this->fail('Should not throw exception before limit');
            }
        }

        // Should block after limit reached
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Rate limit exceeded');
        Security::checkRateLimit($key);
    }

    public function testCSRFProtection() {
        // Generate token
        $token = Security::generateCSRFToken();
        $this->assertNotEmpty($token);
        
        // Verify valid token
        try {
            Security::verifyCSRFToken($token);
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->fail('Should not throw exception for valid token');
        }
        
        // Verify invalid token
        $this->expectException(Exception::class);
        Security::verifyCSRFToken('invalid_token');
    }

    public function testSessionSecurity() {
        // Test session initialization
        Security::initSession();
        $this->assertTrue(session_status() === PHP_SESSION_ACTIVE);
        
        // Test secure session settings
        $this->assertEquals('1', ini_get('session.cookie_httponly'));
        $this->assertEquals('1', ini_get('session.cookie_secure'));
        $this->assertEquals('1', ini_get('session.use_only_cookies'));
        $this->assertEquals('Strict', ini_get('session.cookie_samesite'));
    }

    public function testSecurityHeaders() {
        Security::initSession();
        
        $headers = xdebug_get_headers();
        
        $this->assertContains('X-Frame-Options: DENY', $headers);
        $this->assertContains('X-XSS-Protection: 1; mode=block', $headers);
        $this->assertContains('X-Content-Type-Options: nosniff', $headers);
        $this->assertContains('Referrer-Policy: strict-origin-when-cross-origin', $headers);
        $this->assertContains(
            "Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' https://cdn.jsdelivr.net",
            $headers
        );
    }

    public function testIPAddressValidation() {
        // Test valid IPv4
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $ip = Security::getClientIP();
        $this->assertEquals('192.168.1.1', $ip);
        
        // Test valid IPv6
        $_SERVER['REMOTE_ADDR'] = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
        $ip = Security::getClientIP();
        $this->assertEquals('2001:0db8:85a3:0000:0000:8a2e:0370:7334', $ip);
        
        // Test invalid IP
        $_SERVER['REMOTE_ADDR'] = 'invalid_ip';
        $ip = Security::getClientIP();
        $this->assertEquals('0.0.0.0', $ip);
    }

    public function testRoleBasedAccess() {
        // Test admin access
        $_SESSION['user_role'] = 'admin';
        $this->assertTrue(Security::hasRole('admin'));
        
        // Test editor access
        $_SESSION['user_role'] = 'editor';
        $this->assertFalse(Security::hasRole('admin'));
        $this->assertTrue(Security::hasRole('editor'));
        
        // Test viewer access
        $_SESSION['user_role'] = 'viewer';
        $this->assertFalse(Security::hasRole('admin'));
        $this->assertFalse(Security::hasRole('editor'));
        $this->assertTrue(Security::hasRole('viewer'));
    }

    protected function tearDown(): void {
        // Clean up test data
        $this->db->exec("DELETE FROM login_attempts WHERE username LIKE 'test%'");
        $this->db->exec("DELETE FROM rate_limits WHERE request_key LIKE 'test%'");
        parent::tearDown();
    }
}
