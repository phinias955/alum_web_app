<?php
$pageTitle = 'Error Logs And Statistics For Security';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/config/Database.php';

// Function to parse error logs
function analyzeErrorLogs($logFile) {
    $stats = [
        'errors' => 0,
        'warnings' => 0,
        'notices' => 0,
        'security' => 0,
        'by_file' => [],
        'by_hour' => array_fill(0, 24, 0),
        'by_type' => [],
        'attack_patterns' => []
    ];
    
    $logs = [];
    
    if (!file_exists($logFile)) {
        return ['stats' => $stats, 'logs' => $logs];
    }
    
    $lines = file($logFile);
    foreach ($lines as $line) {
        // Extract timestamp and convert to hour
        if (preg_match('/^\[(.*?)\]/', $line, $matches)) {
            $timestamp = strtotime($matches[1]);
            $hour = (int)date('G', $timestamp);
            $stats['by_hour'][$hour]++;
        }

        if (strpos($line, 'Error') !== false || strpos($line, 'Fatal error') !== false) {
            $stats['errors']++;
            $type = 'Error';
        } elseif (strpos($line, 'Warning') !== false) {
            $stats['warnings']++;
            $type = 'Warning';
        } elseif (strpos($line, 'Notice') !== false) {
            $stats['notices']++;
            $type = 'Notice';
        } else {
            $type = 'Other';
        }
        
        $stats['by_type'][$type] = ($stats['by_type'][$type] ?? 0) + 1;
        
        // Extract file name
        if (preg_match('/in (.+?) on line/', $line, $matches)) {
            $file = basename($matches[1]);
            $stats['by_file'][$file] = ($stats['by_file'][$file] ?? 0) + 1;
        }
        
        // Check for security-related issues
        if (preg_match('/(sql|injection|hack|attack|exploit|xss|csrf)/i', $line, $matches)) {
            $stats['security']++;
            $pattern = strtolower($matches[1]);
            $stats['attack_patterns'][$pattern] = ($stats['attack_patterns'][$pattern] ?? 0) + 1;
        }
        
        $logs[] = [
            'time' => substr($line, 1, 19),
            'message' => $line,
            'type' => $type
        ];
    }
    
    return ['stats' => $stats, 'logs' => $logs];
}

// Get error log data
$logFile = __DIR__ . '/config/error.log';
$data = analyzeErrorLogs($logFile);
$stats = $data['stats'];
$logs = $data['logs'];

?>

<div class="pl-64"> <!-- Add left padding for sidebar -->
    <div class="container mx-auto px-4 py-6">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-500 text-white">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-500">Errors</p>
                        <p class="text-2xl font-bold"><?php echo $stats['errors']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-500 text-white">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-500">Warnings</p>
                        <p class="text-2xl font-bold"><?php echo $stats['warnings']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-500 text-white">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-500">Notices</p>
                        <p class="text-2xl font-bold"><?php echo $stats['notices']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-500 text-white">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-500">Security Issues</p>
                        <p class="text-2xl font-bold"><?php echo $stats['security']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Error Distribution by Type -->
            <div class="bg-white rounded-lg shadow p-4">
                <h2 class="text-lg font-semibold mb-4">Error Distribution by Type</h2>
                <div class="h-64">
                    <canvas id="errorTypeChart"></canvas>
                </div>
            </div>

            <!-- Error Distribution by File -->
            <div class="bg-white rounded-lg shadow p-4">
                <h2 class="text-lg font-semibold mb-4">Most Affected Files</h2>
                <div class="h-64">
                    <canvas id="errorFileChart"></canvas>
                </div>
            </div>

            <!-- Errors Over Time -->
            <div class="bg-white rounded-lg shadow p-4">
                <h2 class="text-lg font-semibold mb-4">Errors by Hour</h2>
                <div class="h-64">
                    <canvas id="errorTimeChart"></canvas>
                </div>
            </div>

            <!-- Security Issues -->
            <div class="bg-white rounded-lg shadow p-4">
                <h2 class="text-lg font-semibold mb-4">Security Attack Patterns</h2>
                <div class="h-64">
                    <canvas id="securityChart"></canvas>
                </div>
            </div>
        </div>




        
        <!-- Log Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold">Error Logs</h2>
                    <div class="space-x-2">
                        <button onclick="window.location.reload()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            <i class="fas fa-sync-alt mr-2"></i>Refresh
                        </button>
                        <button onclick="window.location.href='ajax/download-logs.php'" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                            <i class="fas fa-download mr-2"></i>Download
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-gray-500">No error logs found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($log['time']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full <?php 
                                            echo match($log['type']) {
                                                'Error' => 'bg-red-100 text-red-800',
                                                'Warning' => 'bg-yellow-100 text-yellow-800',
                                                'Notice' => 'bg-blue-100 text-blue-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            };
                                        ?>">
                                            <?php echo htmlspecialchars($log['type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 break-all">
                                        <?php echo htmlspecialchars($log['message']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Error Type Distribution Chart
    new Chart(document.getElementById('errorTypeChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($stats['by_type'])); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($stats['by_type'])); ?>,
                backgroundColor: [
                    'rgba(239, 68, 68, 0.7)',   // red
                    'rgba(245, 158, 11, 0.7)',  // yellow
                    'rgba(59, 130, 246, 0.7)',  // blue
                    'rgba(107, 114, 128, 0.7)'  // gray
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });

    // Error by File Chart
    const fileData = <?php echo json_encode($stats['by_file']); ?>;
    const sortedFiles = Object.entries(fileData)
        .sort(([,a], [,b]) => b - a)
        .slice(0, 5);
    
    new Chart(document.getElementById('errorFileChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: sortedFiles.map(([file]) => file),
            datasets: [{
                label: 'Errors',
                data: sortedFiles.map(([,count]) => count),
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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

    // Errors by Hour Chart
    new Chart(document.getElementById('errorTimeChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: Array.from({length: 24}, (_, i) => `${i}:00`),
            datasets: [{
                label: 'Errors',
                data: <?php echo json_encode(array_values($stats['by_hour'])); ?>,
                fill: true,
                borderColor: 'rgba(147, 51, 234, 1)',
                backgroundColor: 'rgba(147, 51, 234, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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

    // Security Attack Patterns Chart
    const securityData = <?php echo json_encode($stats['attack_patterns'] ?? []); ?>;
    new Chart(document.getElementById('securityChart').getContext('2d'), {
        type: 'polarArea',
        data: {
            labels: Object.keys(securityData),
            datasets: [{
                data: Object.values(securityData),
                backgroundColor: [
                    'rgba(239, 68, 68, 0.7)',
                    'rgba(245, 158, 11, 0.7)',
                    'rgba(59, 130, 246, 0.7)',
                    'rgba(16, 185, 129, 0.7)',
                    'rgba(147, 51, 234, 0.7)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
});

// Auto refresh every 30 seconds
setInterval(() => {
    window.location.reload();
}, 30000);
</script>
