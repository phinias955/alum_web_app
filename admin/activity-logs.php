<?php
require_once 'config/config.php';
require_once 'controllers/ActivityLogController.php';
require_once 'controllers/UserController.php';

Security::initSession();

if (!Security::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$activityLogController = new ActivityLogController();
$userController = new UserController();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Security::verifyCSRFToken($_POST['csrf_token'] ?? '');
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'export':
                    $filters = [
                        'user_id' => $_POST['user_id'] ?? null,
                        'action_type' => $_POST['action_type'] ?? null,
                        'date_from' => $_POST['date_from'] ?? null,
                        'date_to' => $_POST['date_to'] ?? null
                    ];
                    $filepath = $activityLogController->exportToCSV($filters);
                    echo json_encode(['status' => 'success', 'file' => $filepath]);
                    exit;

                case 'purge':
                    $daysToKeep = (int)$_POST['days_to_keep'];
                    $count = $activityLogController->purgeOldLogs($daysToKeep);
                    $_SESSION['flash_message'] = "Successfully purged $count old log entries";
                    $_SESSION['flash_type'] = "bg-green-100 text-green-700";
                    break;
            }
        }
        
        header('Location: activity-logs.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['flash_message'] = $e->getMessage();
        $_SESSION['flash_type'] = "bg-red-100 text-red-700";
    }
}

// Get filters from query string
$filters = [
    'user_id' => $_GET['user_id'] ?? null,
    'action_type' => $_GET['action_type'] ?? null,
    'date_from' => $_GET['date_from'] ?? null,
    'date_to' => $_GET['date_to'] ?? null
];

// Get activity logs and statistics
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$logsData = $activityLogController->getActivityLogs($page, 50, $filters);
$stats = $activityLogController->getActivityStatistics();
$actionTypes = $activityLogController->getActionTypes();
$users = $userController->getAll()['data'];

$pageTitle = "Activity Logs";
ob_start();
?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-2">Total Activities</h3>
        <p class="text-3xl font-bold text-primary"><?php echo $stats['total']; ?></p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-2">Most Active User</h3>
        <p class="text-3xl font-bold text-blue-600">
            <?php
            if (!empty($stats['most_active_users'])) {
                echo htmlspecialchars($stats['most_active_users'][0]['username']);
            } else {
                echo 'N/A';
            }
            ?>
        </p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-2">Top Action Type</h3>
        <p class="text-3xl font-bold text-green-600">
            <?php
            if (!empty($stats['by_type'])) {
                echo htmlspecialchars($stats['by_type'][0]['action_type']);
            } else {
                echo 'N/A';
            }
            ?>
        </p>
    </div>
</div>

<!-- Filter Form -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="user_id">
                User
            </label>
            <select name="user_id" id="user_id"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="">All Users</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>"
                            <?php echo ($filters['user_id'] == $user['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['username']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="action_type">
                Action Type
            </label>
            <select name="action_type" id="action_type"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="">All Actions</option>
                <?php foreach ($actionTypes as $type): ?>
                    <option value="<?php echo $type; ?>"
                            <?php echo ($filters['action_type'] == $type) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($type); ?>
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

        <div class="md:col-span-4 flex justify-between items-center">
            <div>
                <button type="submit"
                        class="bg-primary text-white px-6 py-2 rounded-md hover:bg-secondary transition duration-300">
                    <i class="fas fa-filter mr-2"></i>Apply Filters
                </button>
                <a href="activity-logs.php" 
                   class="bg-gray-500 text-white px-6 py-2 rounded-md hover:bg-gray-600 transition duration-300 ml-2">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
            </div>
            <div>
                <button type="button" onclick="exportLogs()"
                        class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 transition duration-300">
                    <i class="fas fa-file-export mr-2"></i>Export CSV
                </button>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <button type="button" onclick="openModal('purgeModal')"
                            class="bg-red-600 text-white px-6 py-2 rounded-md hover:bg-red-700 transition duration-300 ml-2">
                        <i class="fas fa-trash mr-2"></i>Purge Old Logs
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Activity Logs Table -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="p-6">
        <table id="logsTable" class="w-full">
            <thead>
                <tr>
                    <th class="px-4 py-2">User</th>
                    <th class="px-4 py-2">Action</th>
                    <th class="px-4 py-2">IP Address</th>
                    <th class="px-4 py-2">Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logsData['data'] as $log): ?>
                <tr>
                    <td class="px-4 py-2">
                        <?php if ($log['username']): ?>
                            <?php echo htmlspecialchars($log['username']); ?>
                            <span class="text-gray-500 text-sm">
                                (<?php echo htmlspecialchars($log['email']); ?>)
                            </span>
                        <?php else: ?>
                            <span class="text-gray-500">System</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-2">
                        <?php
                        $actionParts = explode(':', $log['action']);
                        $actionType = $actionParts[0];
                        $actionDetail = $actionParts[1] ?? '';
                        ?>
                        <span class="px-2 py-1 rounded-full text-xs 
                            <?php
                            echo match($actionType) {
                                'created' => 'bg-green-100 text-green-800',
                                'updated' => 'bg-blue-100 text-blue-800',
                                'deleted' => 'bg-red-100 text-red-800',
                                'login' => 'bg-purple-100 text-purple-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            ?>">
                            <?php echo ucfirst($actionType); ?>
                        </span>
                        <?php if ($actionDetail): ?>
                            <span class="text-gray-600 ml-2"><?php echo htmlspecialchars($actionDetail); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-2"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                    <td class="px-4 py-2">
                        <?php echo date('M j, Y H:i:s', strtotime($log['created_at'])); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($logsData['pages'] > 1): ?>
        <div class="mt-4 flex justify-center">
            <div class="flex space-x-2">
                <?php for ($i = 1; $i <= $logsData['pages']; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&<?php echo http_build_query($filters); ?>"
                       class="px-4 py-2 rounded-md <?php echo $i === $logsData['current_page'] ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Purge Logs Modal -->
<div id="purgeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg w-full max-w-md mx-4">
        <div class="flex justify-between items-center p-6 border-b">
            <h3 class="text-xl font-semibold">Purge Old Logs</h3>
            <button onclick="closeModal('purgeModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="" method="POST" class="p-6">
            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="purge">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="days_to_keep">
                    Keep logs from the last
                </label>
                <select name="days_to_keep" id="days_to_keep" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="30">30 days</option>
                    <option value="60">60 days</option>
                    <option value="90" selected>90 days</option>
                    <option value="180">180 days</option>
                    <option value="365">365 days</option>
                </select>
                <p class="text-sm text-gray-600 mt-2">
                    Logs older than the selected period will be permanently deleted.
                </p>
            </div>
            
            <div class="flex justify-end">
                <button type="button" onclick="closeModal('purgeModal')"
                        class="bg-gray-500 text-white px-4 py-2 rounded mr-2 hover:bg-gray-600">
                    Cancel
                </button>
                <button type="submit"
                        class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                    Purge Logs
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#logsTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf'
        ],
        order: [[3, 'desc']], // Sort by timestamp by default
        pageLength: 50
    });
});

function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

function exportLogs() {
    const filters = {
        user_id: document.getElementById('user_id').value,
        action_type: document.getElementById('action_type').value,
        date_from: document.getElementById('date_from').value,
        date_to: document.getElementById('date_to').value
    };

    fetch('activity-logs.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=export&csrf_token=<?php echo Security::generateCSRFToken(); ?>&` + 
              Object.entries(filters)
                    .filter(([_, v]) => v)
                    .map(([k, v]) => `${k}=${v}`)
                    .join('&')
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            window.location.href = data.file;
        }
    })
    .catch(error => console.error('Error:', error));
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        event.target.classList.add('hidden');
    }
}
</script>

<?php
$pageContent = ob_get_clean();
include 'includes/layout.php';
?>
