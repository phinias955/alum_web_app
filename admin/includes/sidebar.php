<?php
if (!defined('ADMIN_PATH')) {
    require_once __DIR__ . '/../config/config.php';
}

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="fixed left-0 top-16 bottom-0 w-64 bg-white shadow-lg overflow-y-auto">
    <nav class="mt-5 px-4">
        <div class="space-y-1">
            <!-- Dashboard -->
            <a href="<?php echo ADMIN_URL; ?>/dashboard.php" 
               class="<?php echo $currentPage === 'dashboard.php' ? 'bg-indigo-50 text-indigo-600' : 'text-gray-600 hover:bg-gray-50'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-md">
                <i class="fas fa-home mr-3 <?php echo $currentPage === 'dashboard.php' ? 'text-indigo-600' : 'text-gray-400'; ?>"></i>
                Dashboard
            </a>

            <!-- Alumni Management -->
            <a href="<?php echo ADMIN_URL; ?>/alumni.php" 
               class="<?php echo $currentPage === 'alumni.php' ? 'bg-indigo-50 text-indigo-600' : 'text-gray-600 hover:bg-gray-50'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-md">
                <i class="fas fa-user-graduate mr-3 <?php echo $currentPage === 'alumni.php' ? 'text-indigo-600' : 'text-gray-400'; ?>"></i>
                Alumni
            </a>

            <!-- Events -->
            <a href="<?php echo ADMIN_URL; ?>/events.php" 
               class="<?php echo $currentPage === 'events.php' ? 'bg-indigo-50 text-indigo-600' : 'text-gray-600 hover:bg-gray-50'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-md">
                <i class="fas fa-calendar-alt mr-3 <?php echo $currentPage === 'events.php' ? 'text-indigo-600' : 'text-gray-400'; ?>"></i>
                Events
            </a>

            <!-- News -->
            <a href="<?php echo ADMIN_URL; ?>/news.php" 
               class="<?php echo $currentPage === 'news.php' ? 'bg-indigo-50 text-indigo-600' : 'text-gray-600 hover:bg-gray-50'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-md">
                <i class="fas fa-newspaper mr-3 <?php echo $currentPage === 'news.php' ? 'text-indigo-600' : 'text-gray-400'; ?>"></i>
                News
            </a>

            <!-- Users -->
            <a href="<?php echo ADMIN_URL; ?>/users.php" 
               class="<?php echo $currentPage === 'users.php' ? 'bg-indigo-50 text-indigo-600' : 'text-gray-600 hover:bg-gray-50'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-md">
                <i class="fas fa-users mr-3 <?php echo $currentPage === 'users.php' ? 'text-indigo-600' : 'text-gray-400'; ?>"></i>
                Users
            </a>

            <!-- Reports -->
            <a href="<?php echo ADMIN_URL; ?>/reports.php" 
               class="<?php echo $currentPage === 'reports.php' ? 'bg-indigo-50 text-indigo-600' : 'text-gray-600 hover:bg-gray-50'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-md">
                <i class="fas fa-chart-bar mr-3 <?php echo $currentPage === 'reports.php' ? 'text-indigo-600' : 'text-gray-400'; ?>"></i>
                Reports
            </a>

            <!-- Settings -->
            <a href="<?php echo ADMIN_URL; ?>/settings.php" 
               class="<?php echo $currentPage === 'settings.php' ? 'bg-indigo-50 text-indigo-600' : 'text-gray-600 hover:bg-gray-50'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-md">
                <i class="fas fa-cog mr-3 <?php echo $currentPage === 'settings.php' ? 'text-indigo-600' : 'text-gray-400'; ?>"></i>
                Settings
            </a>

              <!-- error logs -->
              <a href="<?php echo ADMIN_URL; ?>/error-logs.php" 
               class="<?php echo $currentPage === 'error-logs.php' ? 'bg-indigo-50 text-indigo-600' : 'text-gray-600 hover:bg-gray-50'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-md">
                <i class="fas fa-cog mr-3 <?php echo $currentPage === 'error-logs.php' ? 'text-indigo-600' : 'text-gray-400'; ?>"></i>
                Error Logs
            </a>
             <!-- kibana maps -->
             <a href="<?php echo ADMIN_URL; ?>/kibana-maps.php" 
               class="<?php echo $currentPage === 'kibana-maps.php' ? 'bg-indigo-50 text-indigo-600' : 'text-gray-600 hover:bg-gray-50'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-md">
                <svg class="mr-3 h-6 w-6 <?php echo $currentPage === 'kibana-maps.php' ? 'text-indigo-600' : 'text-gray-400'; ?>" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
                Kibana Maps
            </a>
        </div>

        <!-- System Info -->
        <div class="mt-8 pt-4 border-t">
            <div class="px-3 py-2">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">System Info</h3>
                <div class="mt-2 text-xs text-gray-500">
                    <p>Version: 1.0.0</p>
                    <p>Last Update: <?php echo date('M j, Y'); ?></p>
                    <p>Server: <?php echo $_SERVER['SERVER_NAME']; ?></p>
                    <p>PHP Version: <?php echo phpversion(); ?></p>
                     <p>Developed by: <a href="https://github.com/phinias955" target="_blank" class="text-indigo-600 hover:underline">PHINItech</a></p>
                </div>
            </div>
        </div>
    </nav>
</aside>
