<?php
require_once 'config/config.php';
require_once 'controllers/UserController.php';

Security::initSession();

$userController = new UserController();
$error = '';
$success = '';
$tokenData = null;

// Validate token if present
if (isset($_GET['token'])) {
    try {
        $tokenData = $userController->validateResetToken($_GET['token']);
        if (!$tokenData) {
            throw new Exception('Invalid or expired reset token');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Security::verifyCSRFToken($_POST['csrf_token'] ?? '');
        
        if (isset($_POST['email'])) {
            // Request password reset
            if ($userController->resetPassword($_POST['email'])) {
                $success = 'If an account exists with this email, you will receive password reset instructions.';
            }
        } elseif (isset($_POST['new_password']) && isset($_POST['token'])) {
            // Complete password reset
            if ($userController->completePasswordReset($_POST['token'], $_POST['new_password'])) {
                $success = 'Password reset successfully. You can now login with your new password.';
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Alumni Portal Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow-md">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900">
                    Reset Password
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    <?php echo $tokenData ? 'Enter your new password' : 'Request a password reset'; ?>
                </p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($tokenData): ?>
                <!-- Reset Password Form -->
                <form class="mt-8 space-y-6" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                    
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700">
                            New Password
                        </label>
                        <input id="new_password" name="new_password" type="password" required 
                               class="appearance-none rounded relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm" 
                               placeholder="Enter your new password">
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                            Confirm Password
                        </label>
                        <input id="confirm_password" name="confirm_password" type="password" required 
                               class="appearance-none rounded relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm" 
                               placeholder="Confirm your new password">
                    </div>

                    <div>
                        <button type="submit" 
                                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            Reset Password
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <!-- Request Reset Form -->
                <form class="mt-8 space-y-6" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            Email Address
                        </label>
                        <input id="email" name="email" type="email" required 
                               class="appearance-none rounded relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm" 
                               placeholder="Enter your email address">
                    </div>

                    <div>
                        <button type="submit" 
                                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            Send Reset Link
                        </button>
                    </div>
                </form>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="login.php" class="text-sm text-primary hover:text-secondary">
                    Back to Login
                </a>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('new_password')?.addEventListener('input', validatePassword);
    document.getElementById('confirm_password')?.addEventListener('input', validatePassword);

    function validatePassword() {
        const password = document.getElementById('new_password').value;
        const confirm = document.getElementById('confirm_password').value;
        const submitBtn = document.querySelector('button[type="submit"]');
        
        let isValid = true;
        let errors = [];
        
        // Length check
        if (password.length < 12) {
            isValid = false;
            errors.push('Password must be at least 12 characters long');
        }
        
        // Uppercase check
        if (!/[A-Z]/.test(password)) {
            isValid = false;
            errors.push('Password must contain at least one uppercase letter');
        }
        
        // Lowercase check
        if (!/[a-z]/.test(password)) {
            isValid = false;
            errors.push('Password must contain at least one lowercase letter');
        }
        
        // Number check
        if (!/[0-9]/.test(password)) {
            isValid = false;
            errors.push('Password must contain at least one number');
        }
        
        // Special character check
        if (!/[^A-Za-z0-9]/.test(password)) {
            isValid = false;
            errors.push('Password must contain at least one special character');
        }
        
        // Match check
        if (password !== confirm) {
            isValid = false;
            errors.push('Passwords do not match');
        }
        
        // Update submit button state
        submitBtn.disabled = !isValid;
        submitBtn.classList.toggle('opacity-50', !isValid);
        submitBtn.classList.toggle('cursor-not-allowed', !isValid);
        
        // Show/hide error messages
        let errorDiv = document.getElementById('password-errors');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.id = 'password-errors';
            errorDiv.className = 'text-red-600 text-sm mt-2';
            document.getElementById('new_password').parentNode.appendChild(errorDiv);
        }
        errorDiv.innerHTML = errors.map(e => `<p>${e}</p>`).join('');
    }
    </script>
</body>
</html>
