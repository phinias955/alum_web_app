<?php
class Security {
    private static $db = null;
    private static $maxLoginAttempts = 5;
    private static $lockoutTime = 900; // 15 minutes in seconds
    private static $rateLimitRequests = 100;
    private static $rateLimitWindow = 60; // 1 minute in seconds

    // Initialize database connection
    private static function initDB() {
        if (self::$db === null) {
            try {
                self::$db = Database::getInstance()->getConnection();
                if (!self::$db) {
                    throw new Exception('Database connection failed in Security class');
                }
                
                // Ensure login_attempts table exists
                self::createLoginAttemptsTable();
                
                // Ensure rate_limits table exists
                self::createRateLimitsTable();
                
            } catch (Exception $e) {
                error_log("Security DB initialization error: " . $e->getMessage());
                throw $e;
            }
        }
    }

    private static function createLoginAttemptsTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) NOT NULL,
                ip_address VARCHAR(45),
                user_agent VARCHAR(255),
                success TINYINT(1) DEFAULT 0,
                attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_username (username),
                INDEX idx_attempted_at (attempted_at)
            )";
            
            self::$db->exec($sql);
        } catch (PDOException $e) {
            error_log("Error creating login_attempts table: " . $e->getMessage());
            throw $e;
        }
    }

    private static function createRateLimitsTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS rate_limits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                request_key VARCHAR(255) NOT NULL,
                requests INT NOT NULL DEFAULT 0,
                window_start TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_request_key (request_key),
                INDEX idx_window_start (window_start)
            )";
            
            self::$db->exec($sql);
        } catch (PDOException $e) {
            error_log("Error creating rate_limits table: " . $e->getMessage());
            throw $e;
        }
    }

    // Initialize secure session
    public static function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session parameters
            $params = session_get_cookie_params();
            session_set_cookie_params([
                'lifetime' => $params['lifetime'],
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'],
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            session_start();
        }
        
        // Set security headers
        if (!headers_sent()) {
            header('X-Frame-Options: DENY');
            header('X-XSS-Protection: 1; mode=block');
            header('X-Content-Type-Options: nosniff');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline' 'unsafe-eval'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com");
        }
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration']) || 
            time() - $_SESSION['last_regeneration'] > 300) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }

    // Generate CSRF token
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Verify CSRF token
    public static function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || empty($token) || 
            !hash_equals($_SESSION['csrf_token'], $token)) {
            error_log("CSRF token verification failed");
            self::logSecurityEvent('csrf_failure', 'CSRF token validation failed');
            throw new Exception('CSRF token validation failed');
        }
        return true;
    }

    // Hash password with proper cost
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    // Verify password
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    // Sanitize input
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    // Generate secure random token
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }

    // Validate email
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    // Log security event
    public static function logSecurityEvent($eventType, $description = '') {
        try {
            self::initDB(); // Ensure database connection exists
            $userId = $_SESSION['user_id'] ?? null;
            $ip = self::getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $stmt = self::$db->prepare("
                INSERT INTO security_events (user_id, event_type, description, ip_address, user_agent)
                VALUES (:user_id, :event_type, :description, :ip_address, :user_agent)
            ");
            
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':event_type', $eventType, PDO::PARAM_STR);
            $stmt->bindValue(':description', $description, PDO::PARAM_STR);
            $stmt->bindValue(':ip_address', $ip, PDO::PARAM_STR);
            $stmt->bindValue(':user_agent', $userAgent, PDO::PARAM_STR);
            $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Database error in security event log: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Security Event Log Error: " . $e->getMessage());
        }
    }

    // Log activity
    public static function logActivity($userId, $action, $details = null) {
        try {
            self::initDB(); // Ensure database connection exists
            $ip = self::getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $detailsJson = $details ? json_encode($details) : null;

            $stmt = self::$db->prepare("
                INSERT INTO user_activity_log (user_id, action, details, ip_address, user_agent)
                VALUES (:user_id, :action, :details, :ip_address, :user_agent)
            ");
            
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':action', $action, PDO::PARAM_STR);
            $stmt->bindValue(':details', $detailsJson, PDO::PARAM_STR);
            $stmt->bindValue(':ip_address', $ip, PDO::PARAM_STR);
            $stmt->bindValue(':user_agent', $userAgent, PDO::PARAM_STR);
            $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Database error in activity log: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Activity Log Error: " . $e->getMessage());
        }
    }

    // Check rate limit
    public static function checkRateLimit($key = null) {
        try {
            self::initDB(); // Ensure database connection exists
            $ip = self::getClientIP();
            $key = $key ?: $ip;
            
            // Clean up old rate limits
            $stmt = self::$db->prepare("
                DELETE FROM rate_limits 
                WHERE window_start < DATE_SUB(NOW(), INTERVAL :window SECOND)
            ");
            $stmt->bindValue(':window', self::$rateLimitWindow, PDO::PARAM_INT);
            $stmt->execute();
            
            // Get current requests
            $stmt = self::$db->prepare("
                SELECT requests, window_start
                FROM rate_limits
                WHERE request_key = :key
            ");
            $stmt->bindValue(':key', $key, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                if ($result['requests'] >= self::$rateLimitRequests) {
                    $waitTime = self::$rateLimitWindow - 
                               (time() - strtotime($result['window_start']));
                    
                    if ($waitTime > 0) {
                        header('HTTP/1.1 429 Too Many Requests');
                        header("Retry-After: $waitTime");
                        self::logSecurityEvent('rate_limit_exceeded', "Rate limit exceeded for key: $key");
                        throw new Exception("Rate limit exceeded. Please try again in $waitTime seconds.");
                    }
                }
                
                // Update request count
                $stmt = self::$db->prepare("
                    UPDATE rate_limits 
                    SET requests = requests + 1
                    WHERE request_key = :key
                ");
                $stmt->bindValue(':key', $key, PDO::PARAM_STR);
                $stmt->execute();
            } else {
                // Create new rate limit entry
                $stmt = self::$db->prepare("
                    INSERT INTO rate_limits (request_key, requests, window_start)
                    VALUES (:key, 1, NOW())
                ");
                $stmt->bindValue(':key', $key, PDO::PARAM_STR);
                $stmt->execute();
            }
        } catch (PDOException $e) {
            error_log("Database error in rate limit check: " . $e->getMessage());
            throw new Exception('Database error occurred');
        } catch (Exception $e) {
            error_log("Rate Limit Error: " . $e->getMessage());
            throw $e;
        }
    }

    // Check login attempts
    public static function checkLoginAttempts($email) {
        try {
            self::initDB(); // Ensure database connection exists
            error_log("Checking login attempts for email: " . $email);
            
            // Clean up old attempts first
            $stmt = self::$db->prepare("
                DELETE FROM login_attempts 
                WHERE attempted_at < DATE_SUB(NOW(), INTERVAL :minutes MINUTE)
            ");
            
            $minutes = self::$lockoutTime / 60;
            $stmt->bindValue(':minutes', $minutes, PDO::PARAM_INT);
            $stmt->execute();

            // Count recent failed attempts
            $stmt = self::$db->prepare("
                SELECT COUNT(*) as attempts 
                FROM login_attempts 
                WHERE username = :username 
                AND success = 0 
                AND attempted_at > DATE_SUB(NOW(), INTERVAL :minutes MINUTE)
            ");
            
            $stmt->bindValue(':username', $email, PDO::PARAM_STR);
            $stmt->bindValue(':minutes', $minutes, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $attempts = $result['attempts'];
            
            error_log("Found $attempts failed attempts for email: " . $email);
            
            if ($attempts >= self::$maxLoginAttempts) {
                throw new Exception("Too many failed login attempts. Please try again later.");
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Database error in checkLoginAttempts: " . $e->getMessage());
            throw new Exception('Database error occurred');
        }
    }

    // Record login attempt
    public static function recordLoginAttempt($email, $success) {
        try {
            self::initDB(); // Ensure database connection exists
            error_log("Recording login attempt for email: " . $email . " (Success: " . ($success ? "Yes" : "No") . ")");
            
            $stmt = self::$db->prepare("
                INSERT INTO login_attempts (username, ip_address, user_agent, success) 
                VALUES (:username, :ip_address, :user_agent, :success)
            ");
            
            $ip = self::getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt->bindValue(':username', $email, PDO::PARAM_STR);
            $stmt->bindValue(':ip_address', $ip, PDO::PARAM_STR);
            $stmt->bindValue(':user_agent', $userAgent, PDO::PARAM_STR);
            $stmt->bindValue(':success', $success, PDO::PARAM_INT);
            $stmt->execute();

            // Log security event
            $eventType = $success ? 'login_success' : 'login_failed';
            $description = $success ? 'Successful login' : 'Failed login attempt';
            self::logSecurityEvent($eventType, $description);
            
        } catch (PDOException $e) {
            error_log("Database error in recordLoginAttempt: " . $e->getMessage());
            // Don't throw the exception here as this is a non-critical operation
        } catch (Exception $e) {
            error_log("Login attempt record error: " . $e->getMessage());
            // Don't throw the exception here as this is a non-critical operation
        }
    }

    // Get client IP
    public static function getClientIP() {
        $ipAddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
        } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED'];
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        }
        return $ipAddress;
    }

    // Check if user is logged in
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    // Check user role
    public static function hasRole($requiredRole) {
        return isset($_SESSION['role']) && $_SESSION['role'] === $requiredRole;
    }

    // Regenerate session
    public static function regenerateSession() {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }

    // Clean up old sessions
    public static function cleanupSessions() {
        try {
            self::initDB(); // Ensure database connection exists
            $stmt = self::$db->prepare("
                DELETE FROM user_sessions 
                WHERE last_activity < DATE_SUB(NOW(), INTERVAL :lifetime SECOND)
            ");
            $stmt->bindValue(':lifetime', SESSION_LIFETIME, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error in session cleanup: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Session Cleanup Error: " . $e->getMessage());
        }
    }

    // Redirect if not logged in
    public static function redirectIfNotLoggedIn($path = '/login.php') {
        if (!self::isLoggedIn() && !headers_sent()) {
            header('Location: ' . $path);
            exit;
        }
    }

    // Redirect if not admin
    public static function redirectIfNotAdmin($path = '/login.php') {
        if (!self::hasRole('admin') && !headers_sent()) {
            header('Location: ' . $path);
            exit;
        }
    }
}
?>
