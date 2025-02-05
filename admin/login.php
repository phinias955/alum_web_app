<?php
require_once 'config/config.php';
require_once 'controllers/AuthController.php';

Security::initSession();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect if already logged in
if (Security::isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$authController = new AuthController();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
            throw new Exception('Invalid request');
        }
        
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            throw new Exception('Please enter both email and password');
        }

        // Check for too many login attempts
        Security::checkLoginAttempts($email);
        
        if ($authController->login($email, $password)) {
            // Record successful login
            Security::recordLoginAttempt($email, true);
            
            // Redirect to dashboard
            header('Location: dashboard.php');
            exit;
        }
    } catch (Exception $e) {
        // Record failed login attempt if email was provided
        if (!empty($email)) {
            Security::recordLoginAttempt($email, false);
        }
        $error = $e->getMessage();
        error_log("Login error: " . $e->getMessage());
    }
}

// Generate new CSRF token for the form
$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Alumni Portal Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@2.8.2/dist/alpine.min.js" defer></script>
    <style>
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
        .bg-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100 py-6 flex flex-col justify-center sm:py-12" x-data="{ showPassword: false }">
    <div class="relative py-3 sm:max-w-xl sm:mx-auto">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-300 to-blue-600 shadow-lg transform -skew-y-6 sm:skew-y-0 sm:-rotate-6 sm:rounded-3xl"></div>
        
        <div class="relative px-4 py-10 bg-white shadow-lg sm:rounded-3xl sm:p-20 min-w-[380px]">
            <div class="max-w-md mx-auto">
                <div class="divide-y divide-gray-200">
                    <div class="py-8 text-base leading-6 space-y-4 text-gray-700 sm:text-lg sm:leading-7">
                        <div class="text-center mb-8">
                            <i class="fas fa-graduation-cap text-5xl text-blue-600 mb-4 animate-float"></i>
                            <h2 class="text-3xl font-extrabold text-gray-900">Alumni Portal</h2>
                            <p class="mt-2 text-gray-600">Admin Login</p>
                        </div>

                        <?php if ($error): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                        </div>
                        <?php endif; ?>

                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                    <div class="mt-1">
                                        <input id="email" name="email" type="email" required 
                                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                                    <div class="mt-1 relative">
                                        <input id="password" name="password" 
                                               :type="showPassword ? 'text' : 'password'" 
                                               required
                                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                            <button type="button" @click="showPassword = !showPassword" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                                                <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Sign in
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Decorative elements -->
    <div class="fixed bottom-0 left-0 w-full h-2 bg-gradient"></div>
    <div class="fixed top-0 right-0 w-2 h-full bg-gradient"></div>
</body>
</html>
