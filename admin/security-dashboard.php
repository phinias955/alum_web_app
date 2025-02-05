<?php
require_once 'config/config.php';
require_once 'controllers/NotificationController.php';
require_once 'includes/SecurityAudit.php';

Security::initSession();

if (!Security::isLoggedIn() || !Security::hasRole('admin')) {
    header('Location: login.php');
    exit;
}

$notificationController = new NotificationController();
$securityAudit = new SecurityAudit();

// Generate security report
$report = $securityAudit->generateSecurityReport();

// Get security statistics and events
$stats = $notificationController->getSecurityStats();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$filters = [
    'event_type' => $_GET['event_type'] ?? null,
    'date_from' => $_GET['date_from'] ?? null,
    'date_to' => $_GET['date_to'] ?? null
];
$events = $notificationController->getSecurityEvents($page, 50, $filters);

$pageTitle = "Security Dashboard";
ob_start();
?>

<!-- System Health Status -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h3 class="text-xl font-semibold mb-4">System Health</h3>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Disk Space -->
        <div class="p-4 border rounded-lg">
            <h4 class="font-semibold mb-2">Disk Space</h4>
            <div class="relative pt-1">
                <div class="flex mb-2 items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-primary bg-primary-100">
                            <?php echo round($report['system_health']['disk_space']['usage_percent']); ?>%
                        </span>
                    </div>
                </div>
                <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-primary-200">
                    <div style="width:<?php echo $report['system_health']['disk_space']['usage_percent']; ?>%" 
                         class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-primary">
                    </div>
                </div>
                <div class="text-xs">
                    Free: <?php echo round($report['system_health']['disk_space']['free'] / 1024 / 1024 / 1024, 2); ?> GB
                </div>
            </div>
        </div>
        
        <!-- Active Sessions -->
        <div class="p-4 border rounded-lg">
            <h4 class="font-semibold mb-2">Active Sessions</h4>
            <p class="text-2xl font-bold text-primary">
                <?php echo $report['system_health']['session_count']; ?>
            </p>
        </div>
        
        <!-- Latest Backup -->
        <div class="p-4 border rounded-lg">
            <h4 class="font-semibold mb-2">Latest Backup</h4>
            <?php if (!empty($report['system_health']['backup_status'])): ?>
                <?php $latestBackup = reset($report['system_health']['backup_status']); ?>
                <p class="text-sm">
                    <?php echo date('M j, Y H:i', strtotime($latestBackup['created_at'])); ?>
                    <br>
                    Size: <?php echo round($latestBackup['size'] / 1024 / 1024, 2); ?> MB
                </p>
            <?php else: ?>
                <p class="text-sm text-red-600">No recent backups</p>
            <?php endif; ?>
        </div>
        
        <!-- Error Logs -->
        <div class="p-4 border rounded-lg">
            <h4 class="font-semibold mb-2">Recent Errors</h4>
            <p class="text-2xl font-bold <?php echo count($report['system_health']['error_logs']) > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                <?php echo count($report['system_health']['error_logs']); ?>
            </p>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-2">Total Security Events</h3>
        <p class="text-3xl font-bold text-primary"><?php echo $stats['total_events']; ?></p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-2">Failed Logins (24h)</h3>
        <p class="text-3xl font-bold text-red-600"><?php echo $stats['failed_logins_24h']; ?></p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-2">Rate Limit Hits (24h)</h3>
        <p class="text-3xl font-bold text-yellow-600"><?php echo $stats['rate_limit_hits_24h']; ?></p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-2">Suspicious IPs</h3>
        <div class="text-sm">
            <?php foreach (array_slice($stats['suspicious_ips'], 0, 3) as $ip): ?>
                <div class="mb-1">
                    <span class="font-semibold"><?php echo htmlspecialchars($ip['ip_address']); ?></span>
                    <span class="text-red-600">(<?php echo $ip['count']; ?> hits)</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Security Metrics -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <!-- 2FA Usage -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4">2FA Statistics (24h)</h3>
        <canvas id="twoFactorChart" width="400" height="300"></canvas>
    </div>
    
    <!-- Failed Login Distribution -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4">Failed Login Distribution</h3>
        <canvas id="loginAttemptsChart" width="400" height="300"></canvas>
    </div>
</div>

<!-- Events by Type Chart -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4">Events by Type</h3>
        <canvas id="eventsByTypeChart"></canvas>
    </div>
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4">Recent Activity</h3>
        <div class="space-y-4">
            <?php foreach (array_slice($events['data'], 0, 5) as $event): ?>
                <div class="flex items-center justify-between">
                    <div>
                        <span class="font-semibold"><?php echo htmlspecialchars($event['event_type']); ?></span>
                        <span class="text-gray-500 text-sm">
                            from <?php echo htmlspecialchars($event['ip_address']); ?>
                        </span>
                    </div>
                    <span class="text-sm text-gray-500">
                        <?php echo date('M j, Y H:i:s', strtotime($event['created_at'])); ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Filter Form -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="event_type">
                Event Type
            </label>
            <select name="event_type" id="event_type"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="">All Events</option>
                <?php foreach ($stats['events_by_type'] as $type): ?>
                    <option value="<?php echo $type['event_type']; ?>"
                            <?php echo ($filters['event_type'] == $type['event_type']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($type['event_type']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="date_from">
                Date From
            </label>
            <input type="date" name="date_from" id="date_from"
                   value="<?php echo $filters['date_from']; ?>"
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>

        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="date_to">
                Date To
            </label>
            <input type="date" name="date_to" id="date_to"
                   value="<?php echo $filters['date_to']; ?>"
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>

        <div class="md:col-span-3 flex justify-between items-center">
            <button type="submit"
                    class="bg-primary text-white px-6 py-2 rounded-md hover:bg-secondary transition duration-300">
                <i class="fas fa-filter mr-2"></i>Apply Filters
            </button>
            <a href="security-dashboard.php" 
               class="bg-gray-500 text-white px-6 py-2 rounded-md hover:bg-gray-600 transition duration-300">
                <i class="fas fa-times mr-2"></i>Clear Filters
            </a>
        </div>
    </form>
</div>

<!-- Security Events Table -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="p-6">
        <table id="securityEventsTable" class="w-full">
            <thead>
                <tr>
                    <th class="px-4 py-2">Event Type</th>
                    <th class="px-4 py-2">IP Address</th>
                    <th class="px-4 py-2">Details</th>
                    <th class="px-4 py-2">Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events['data'] as $event): ?>
                <tr>
                    <td class="px-4 py-2">
                        <span class="px-2 py-1 rounded-full text-xs 
                            <?php
                            echo match($event['event_type']) {
                                'login_failed' => 'bg-red-100 text-red-800',
                                'rate_limit' => 'bg-yellow-100 text-yellow-800',
                                'security_alert' => 'bg-orange-100 text-orange-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            ?>">
                            <?php echo htmlspecialchars($event['event_type']); ?>
                        </span>
                    </td>
                    <td class="px-4 py-2"><?php echo htmlspecialchars($event['ip_address']); ?></td>
                    <td class="px-4 py-2">
                        <?php
                        $details = json_decode($event['details'], true);
                        echo htmlspecialchars(is_array($details) ? 
                             implode(', ', array_map(fn($k, $v) => "$k: $v", array_keys($details), $details)) : 
                             $event['details']);
                        ?>
                    </td>
                    <td class="px-4 py-2">
                        <?php echo date('M j, Y H:i:s', strtotime($event['created_at'])); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($events['pages'] > 1): ?>
        <div class="mt-4 flex justify-center">
            <div class="flex space-x-2">
                <?php for ($i = 1; $i <= $events['pages']; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&<?php echo http_build_query($filters); ?>"
                       class="px-4 py-2 rounded-md <?php echo $i === $events['current_page'] ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    $('#securityEventsTable').DataTable({
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf'],
        order: [[3, 'desc']]
    });

    // 2FA Statistics Chart
    const twoFactorCtx = document.getElementById('twoFactorChart').getContext('2d');
    new Chart(twoFactorCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_column($report['two_factor_stats'], 'verification_type')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($report['two_factor_stats'], 'total_attempts')); ?>,
                backgroundColor: ['#4F46E5', '#10B981', '#F59E0B']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Failed Login Distribution Chart
    const loginAttemptsCtx = document.getElementById('loginAttemptsChart').getContext('2d');
    new Chart(loginAttemptsCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($report['failed_logins'], 'username')); ?>,
            datasets: [{
                label: 'Failed Attempts',
                data: <?php echo json_encode(array_column($report['failed_logins'], 'attempt_count')); ?>,
                backgroundColor: '#EF4444'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Events by Type Chart
    const eventsByTypeData = <?php echo json_encode($stats['events_by_type']); ?>;
    new Chart(document.getElementById('eventsByTypeChart'), {
        type: 'pie',
        data: {
            labels: eventsByTypeData.map(item => item.event_type),
            datasets: [{
                data: eventsByTypeData.map(item => item.count),
                backgroundColor: [
                    '#4F46E5',
                    '#EF4444',
                    '#F59E0B',
                    '#10B981',
                    '#6366F1'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>

<?php
$pageContent = ob_get_clean();
include 'includes/layout.php';
?>
