<?php
/**
 * This script helps set up the required cron jobs for security monitoring
 * Run this script with appropriate permissions to set up the cron jobs
 */

$cronJobs = [
    // Daily security report at 6 AM
    [
        'name' => 'daily-security-report',
        'schedule' => '0 6 * * *',
        'command' => 'php ' . __DIR__ . '/security-report.php'
    ],
    
    // Clean up old security data at 2 AM
    [
        'name' => 'cleanup-security-data',
        'schedule' => '0 2 * * *',
        'command' => 'php ' . __DIR__ . '/cleanup-security-data.php'
    ],
    
    // Monitor system health every 5 minutes
    [
        'name' => 'monitor-system-health',
        'schedule' => '*/5 * * * *',
        'command' => 'php ' . __DIR__ . '/monitor-health.php'
    ],
    
    // Collect security metrics every minute
    [
        'name' => 'collect-security-metrics',
        'schedule' => '* * * * *',
        'command' => 'php ' . __DIR__ . '/collect-metrics.php'
    ],
    
    // Check security conditions every 5 minutes
    [
        'name' => 'check-security-conditions',
        'schedule' => '*/5 * * * *',
        'command' => 'php -r "require \'' . __DIR__ . '/../includes/SecurityAlerts.php\'; $alerts = new SecurityAlerts(); $alerts->checkSecurityConditions();"'
    ],
    
    // Generate threat intelligence report every hour
    [
        'name' => 'threat-intelligence',
        'schedule' => '0 * * * *',
        'command' => 'php ' . __DIR__ . '/threat-intelligence.php'
    ]
];

// Windows Task Scheduler commands
foreach ($cronJobs as $job) {
    $taskName = "AlumniPortal-{$job['name']}";
    $command = $job['command'];
    
    // Convert cron schedule to Windows schedule
    $schedule = convertCronToWindowsSchedule($job['schedule']);
    
    echo "Setting up task: $taskName\n";
    echo "Schedule: {$job['schedule']} (Cron) -> $schedule (Windows)\n";
    echo "Command: $command\n\n";
    
    // Generate the schtasks command
    $schtasksCmd = "schtasks /create /tn \"$taskName\" /tr \"$command\" /sc $schedule /f";
    
    echo "Run the following command to create the task:\n";
    echo $schtasksCmd . "\n\n";
}

/**
 * Convert cron schedule to Windows schedule format
 */
function convertCronToWindowsSchedule(string $cronSchedule): string {
    $parts = explode(' ', $cronSchedule);
    
    // Minute schedule
    if ($cronSchedule === '* * * * *') {
        return 'MINUTE';
    }
    
    // Every X minutes
    if (preg_match('/^\*\/(\d+)\s/', $cronSchedule, $matches)) {
        return "MINUTE /mo {$matches[1]}";
    }
    
    // Hourly
    if ($cronSchedule === '0 * * * *') {
        return 'HOURLY';
    }
    
    // Daily at specific time
    if (preg_match('/^(\d+)\s+(\d+)\s+\*\s+\*\s+\*$/', $cronSchedule, $matches)) {
        $hour = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $minute = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        return "DAILY /st $hour:$minute";
    }
    
    // Default fallback
    return 'DAILY';
}

echo "Please run these commands with administrative privileges to set up the scheduled tasks.\n";
echo "You may need to modify the PHP path in the commands based on your system configuration.\n";
