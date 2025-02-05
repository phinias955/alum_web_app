<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/includes/Security.php';

// Initialize security
Security::initSession();

// Check if user is logged in
if (!Security::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Initialize controller
$dashboardController = new DashboardController();

// Initialize variables with default values
$stats = [
    'total_alumni' => 0,
    'active_alumni' => 0,
    'recent_activities' => 0,
    'total_events' => 0
];
$recentActivities = [];
$recentAlumni = [];
$error = '';

// Get dashboard data
try {
    $stats = $dashboardController->getStats();
    $recentActivities = $dashboardController->getRecentActivities();
    $recentAlumni = $dashboardController->getRecentAlumni();
} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $error = "An error occurred while loading the dashboard. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Alumni Portal Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .dropdown {
            display: none;
        }
        .dropdown.show {
            display: block;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php 
    require_once __DIR__ . '/includes/header.php';
    require_once __DIR__ . '/includes/sidebar.php';
    ?>

    <main class="ml-64 p-8">
        <div class="container mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-8">Dashboard</h1>

            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Alumni -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                            <i class="fas fa-user-graduate text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Total Alumni</p>
                            <p class="text-lg font-semibold"><?php echo number_format($stats['total_alumni']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Active Alumni -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-500">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Active Alumni</p>
                            <p class="text-lg font-semibold"><?php echo number_format($stats['active_alumni']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-500">
                            <i class="fas fa-chart-line text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Recent Activities</p>
                            <p class="text-lg font-semibold"><?php echo number_format($stats['recent_activities']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Events -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-500">
                            <i class="fas fa-calendar text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Total Events</p>
                            <p class="text-lg font-semibold"><?php echo number_format($stats['total_events']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities and Alumni Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Activities -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b">
                        <h2 class="text-xl font-semibold">Recent Activities</h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($recentActivities)): ?>
                            <p class="text-gray-500 text-center py-4">No recent activities</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($recentActivities as $activity): ?>
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                                <i class="fas fa-user-circle text-blue-500"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($activity['activity']); ?></p>
                                            <p class="text-xs text-gray-400"><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Alumni -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b">
                        <h2 class="text-xl font-semibold">Recent Alumni</h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($recentAlumni)): ?>
                            <p class="text-gray-500 text-center py-4">No recent alumni</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($recentAlumni as $alumni): ?>
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <?php if (!empty($alumni['profile_image'])): ?>
                                                <img class="w-10 h-10 rounded-full object-cover" src="<?php echo htmlspecialchars($alumni['profile_image']); ?>" alt="Profile">
                                            <?php else: ?>
                                                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                    <i class="fas fa-user text-gray-400"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <p class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($alumni['first_name'] . ' ' . $alumni['last_name']); ?>
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($alumni['course']); ?> (<?php echo htmlspecialchars($alumni['graduation_year']); ?>)
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            var notificationDropdown = document.getElementById('notificationDropdown');
            var profileDropdown = document.getElementById('profileDropdown');
            
            if (!event.target.closest('.relative')) {
                notificationDropdown.classList.remove('show');
                profileDropdown.classList.remove('show');
            }
        });

        // Toggle dropdown
        function toggleDropdown(dropdownId) {
            event.stopPropagation();
            var dropdown = document.getElementById(dropdownId);
            var otherDropdownId = dropdownId === 'notificationDropdown' ? 'profileDropdown' : 'notificationDropdown';
            var otherDropdown = document.getElementById(otherDropdownId);
            
            // Close other dropdown if open
            otherDropdown.classList.remove('show');
            
            // Toggle current dropdown
            dropdown.classList.toggle('show');
        }
    </script>
</body>
</html>
