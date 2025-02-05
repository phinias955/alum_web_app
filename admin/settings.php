<?php
$pageTitle = 'Settings';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/includes/auth.php';

// Check if user is logged in
checkUserLogin();

$userController = new UserController();
$message = '';
$error = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['change_password'])) {
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception("New passwords do not match");
            }
            
            $userController->changePassword($_SESSION['user_id'], $currentPassword, $newPassword);
            $message = "Password changed successfully!";
        }
        
        if (isset($_POST['toggle_2fa'])) {
            $enabled = isset($_POST['enable_2fa']) && $_POST['enable_2fa'] === '1';
            $userController->toggle2FA($_SESSION['user_id'], $enabled);
            $message = $enabled ? "Two-factor authentication enabled!" : "Two-factor authentication disabled!";
        }
        
        if (isset($_POST['update_notifications'])) {
            $preferences = [
                'email_notifications' => isset($_POST['email_notifications']),
                'login_alerts' => isset($_POST['login_alerts']),
                'security_alerts' => isset($_POST['security_alerts'])
            ];
            $userController->updateNotificationPreferences($_SESSION['user_id'], $preferences);
            $message = "Notification preferences updated!";
        }
    }
    
    $user = $userController->getById($_SESSION['user_id']);
    $preferences = $userController->getNotificationPreferences($_SESSION['user_id']);
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">Settings</h1>
    </div>

    <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Left Column -->
        <div class="space-y-6">
            <!-- Password Change Section -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Change Password</h2>
                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                        <input type="password" name="current_password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                        <input type="password" name="new_password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                        <input type="password" name="confirm_password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <button type="submit" name="change_password"
                            class="w-full bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Change Password
                    </button>
                </form>
            </div>

            <!-- Two-Factor Authentication Section -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Two-Factor Authentication</h2>
                <form method="POST" action="">
                    <div class="flex items-center mb-4">
                        <input type="checkbox" id="enable_2fa" name="enable_2fa" value="1"
                               <?php echo $user['2fa_enabled'] ? 'checked' : ''; ?>
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="enable_2fa" class="ml-2 text-sm text-gray-700">
                            Enable Two-Factor Authentication
                        </label>
                    </div>

                    <button type="submit" name="toggle_2fa"
                            class="w-full bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Save 2FA Settings
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-6">
            <!-- Notification Preferences Section -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Notification Preferences</h2>
                <form method="POST" action="" class="space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <input type="checkbox" id="email_notifications" name="email_notifications"
                                   <?php echo $preferences['email_notifications'] ? 'checked' : ''; ?>
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <label for="email_notifications" class="ml-2 text-sm text-gray-700">
                                Email Notifications
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="login_alerts" name="login_alerts"
                                   <?php echo $preferences['login_alerts'] ? 'checked' : ''; ?>
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <label for="login_alerts" class="ml-2 text-sm text-gray-700">
                                Login Alerts
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="security_alerts" name="security_alerts"
                                   <?php echo $preferences['security_alerts'] ? 'checked' : ''; ?>
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <label for="security_alerts" class="ml-2 text-sm text-gray-700">
                                Security Alerts
                            </label>
                        </div>
                    </div>

                    <button type="submit" name="update_notifications"
                            class="w-full bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Save Notification Settings
                    </button>
                </form>
            </div>

            <!-- Account Deletion Section -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-red-600 mb-4">Danger Zone</h2>
                <p class="text-gray-600 mb-4">Once you delete your account, there is no going back. Please be certain.</p>
                <button type="button" data-bs-toggle="modal" data-bs-target="#deleteAccountModal"
                        class="w-full bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500">
                    Delete Account
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get the modal
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteAccountModal'), {
        keyboard: true,
        backdrop: 'static'
    });

    // Get the delete button
    const deleteBtn = document.querySelector('[data-bs-target="#deleteAccountModal"]');

    // Add click event listener
    deleteBtn.addEventListener('click', function() {
        deleteModal.show();
    });

    // Reset form when modal is hidden
    document.getElementById('deleteAccountModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('deleteAccountForm').reset();
    });
});
</script>
