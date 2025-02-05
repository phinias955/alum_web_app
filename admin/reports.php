<?php
$pageTitle = 'Reports';
require_once 'includes/layout.php';
require_once 'config/Database.php';
require_once 'includes/Security.php';
require_once '../vendor/autoload.php'; // For TCPDF and PHPSpreadsheet

// Check if user is logged in and has admin access
if (!Security::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (!Security::hasRole('admin')) {
    header('Location: index.php');
    exit;
}

// Initialize database connection
$conn = Database::getInstance()->getConnection();

// Get report type from query parameter
$reportType = isset($_GET['type']) ? $_GET['type'] : 'users';

// Function to get report data based on type
function getReportData($conn, $type) {
    switch ($type) {
        case 'users':
            $query = "SELECT id, username, email, role, status, last_login_at, created_at FROM users";
            break;
        case 'alumni':
            $query = "SELECT a.id, a.first_name, a.last_name, a.email, a.graduation_year, a.course, 
                     a.current_job, a.company, a.phone, a.status, a.created_at 
                     FROM alumni a";
            break;
        case 'events':
            $query = "SELECT e.id, e.title, e.description, e.event_date, e.event_time, 
                     e.location, e.status, u.username as created_by, e.created_at 
                     FROM events e 
                     LEFT JOIN users u ON e.created_by = u.id 
                     ORDER BY e.event_date DESC";
            break;
        case 'activity':
            $query = "SELECT al.id, u.username, al.activity, al.ip_address, 
                     al.created_at 
                     FROM activity_logs al 
                     LEFT JOIN users u ON al.user_id = u.id 
                     ORDER BY al.created_at DESC";
            break;
        default:
            return [];
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get the data for the selected report
$reportData = getReportData($conn, $reportType);
?>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">System Reports</h1>
    </div>

    <!-- Report Type Selection -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex space-x-4">
            <a href="?type=users" 
               class="px-4 py-2 rounded-md <?php echo $reportType === 'users' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700'; ?>">
                Users Report
            </a>
            <a href="?type=alumni" 
               class="px-4 py-2 rounded-md <?php echo $reportType === 'alumni' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700'; ?>">
                Alumni Report
            </a>
            <a href="?type=events" 
               class="px-4 py-2 rounded-md <?php echo $reportType === 'events' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700'; ?>">
                Events Report
            </a>
            <a href="?type=activity" 
               class="px-4 py-2 rounded-md <?php echo $reportType === 'activity' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700'; ?>">
                Activity Report
            </a>
        </div>
    </div>

    <!-- Export Options -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Export Options</h2>
            <div class="flex space-x-4">
                <form action="ajax/export-report.php" method="POST" class="inline">
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($reportType); ?>">
                    <input type="hidden" name="format" value="excel">
                    <button type="submit" class="flex items-center px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Excel
                    </button>
                </form>

                <form action="ajax/export-report.php" method="POST" class="inline">
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($reportType); ?>">
                    <input type="hidden" name="format" value="pdf">
                    <button type="submit" class="flex items-center px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        PDF
                    </button>
                </form>

                <button onclick="window.print()" class="flex items-center px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print
                </button>
            </div>
        </div>
    </div>

    <!-- Report Data Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <?php
                        if (!empty($reportData)) {
                            foreach (array_keys($reportData[0]) as $header) {
                                echo '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">';
                                echo htmlspecialchars(str_replace('_', ' ', $header));
                                echo '</th>';
                            }
                        }
                        ?>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($reportData as $row): ?>
                        <tr>
                            <?php foreach ($row as $value): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($value); ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style type="text/css" media="print">
    @page {
        size: landscape;
    }
    .no-print {
        display: none !important;
    }
    body {
        padding: 0;
        margin: 0;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    th {
        background-color: #f3f4f6;
    }
</style>
