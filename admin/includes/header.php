<?php
if (!defined('ADMIN_PATH')) {
    require_once __DIR__ . '/../config/config.php';
}
?>
<link href="assets/css/profile.css" rel="stylesheet">
<header class="fixed top-0 left-0 right-0 bg-white shadow-md z-50">
    
    <div class="flex items-center justify-between px-6 py-4">
        <div class="flex items-center">
            <a href="<?php echo ADMIN_URL; ?>/dashboard.php" class="flex items-center space-x-2">
                <i class="fas fa-graduation-cap text-2xl text-indigo-600"></i>
                <span class="text-xl font-bold text-gray-800">Alumni Portal</span>
            </a>
        </div>

        <div class="flex items-center space-x-4">
            <!-- Notifications -->
            <div class="relative">
                <button onclick="toggleDropdown('notificationDropdown')" class="p-2 text-gray-500 hover:text-indigo-600 focus:outline-none">
                    <i class="fas fa-bell"></i>
                    <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>
                <div id="notificationDropdown" class="dropdown absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg py-2">
                    <div class="px-4 py-2 border-b">
                        <h3 class="text-sm font-semibold text-gray-700">Notifications</h3>
                    </div>
                    <div class="max-h-64 overflow-y-auto">
                        <div class="px-4 py-2 text-sm text-gray-500">No new notifications</div>
                    </div>
                </div>
            </div>

            <!-- User Menu -->
            <div class="relative">
                <button onclick="toggleDropdown('profileDropdown')" class="flex items-center space-x-2 focus:outline-none">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                        <i class="fas fa-user text-indigo-600"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                </button>
                <div id="profileDropdown" class="dropdown absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2">
                    <a href="<?php echo ADMIN_URL; ?>/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50">
                        <i class="fas fa-user-circle mr-2"></i> Profile
                    </a>
                    <a href="<?php echo ADMIN_URL; ?>/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50">
                        <i class="fas fa-cog mr-2"></i> Settings
                    </a>
                    <div class="border-t my-1"></div>
                    <a href="<?php echo ADMIN_URL; ?>/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
<div class="h-16"></div> <!-- Spacer for fixed header -->
