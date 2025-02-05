<?php
require_once 'config/config.php';
require_once 'controllers/BackupController.php';

Security::initSession();

if (!Security::isLoggedIn() || !Security::hasRole('admin')) {
    header('Location: login.php');
    exit;
}

$backupController = new BackupController();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Security::verifyCSRFToken($_POST['csrf_token'] ?? '');
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    $result = $backupController->createBackup($_POST['type']);
                    $_SESSION['flash_message'] = "Backup created successfully";
                    $_SESSION['flash_type'] = "bg-green-100 text-green-700";
                    break;

                case 'restore':
                    $backupController->restoreBackup($_POST['filename']);
                    $_SESSION['flash_message'] = "Backup restored successfully";
                    $_SESSION['flash_type'] = "bg-green-100 text-green-700";
                    break;

                case 'schedule':
                    $backupController->scheduleBackup(
                        $_POST['schedule_type'],
                        (int)$_POST['frequency'],
                        $_POST['scheduled_time']
                    );
                    $_SESSION['flash_message'] = "Backup scheduled successfully";
                    $_SESSION['flash_type'] = "bg-green-100 text-green-700";
                    break;
            }
        }
        
        header('Location: backups.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['flash_message'] = $e->getMessage();
        $_SESSION['flash_type'] = "bg-red-100 text-red-700";
    }
}

// Get backup history
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$backups = $backupController->getBackups($page);

$pageTitle = "Backup Management";
ob_start();
?>

<!-- Action Buttons -->
<div class="mb-6 flex justify-between items-center">
    <div>
        <button onclick="openModal('createBackupModal')"
                class="bg-primary text-white px-6 py-2 rounded-md hover:bg-secondary transition duration-300">
            <i class="fas fa-plus mr-2"></i>Create Backup
        </button>
        <button onclick="openModal('scheduleBackupModal')"
                class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition duration-300 ml-2">
            <i class="fas fa-clock mr-2"></i>Schedule Backup
        </button>
    </div>
</div>

<!-- Backup History Table -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="p-6">
        <table id="backupsTable" class="w-full">
            <thead>
                <tr>
                    <th class="px-4 py-2">Filename</th>
                    <th class="px-4 py-2">Type</th>
                    <th class="px-4 py-2">Size</th>
                    <th class="px-4 py-2">Created By</th>
                    <th class="px-4 py-2">Created At</th>
                    <th class="px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($backups['data'] as $backup): ?>
                <tr>
                    <td class="px-4 py-2"><?php echo htmlspecialchars($backup['filename']); ?></td>
                    <td class="px-4 py-2">
                        <span class="px-2 py-1 rounded-full text-xs 
                            <?php echo $backup['type'] === 'full' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                            <?php echo ucfirst($backup['type']); ?>
                        </span>
                    </td>
                    <td class="px-4 py-2">
                        <?php echo number_format($backup['size'] / 1024 / 1024, 2) . ' MB'; ?>
                    </td>
                    <td class="px-4 py-2">
                        <?php echo htmlspecialchars($backup['username'] ?? 'System'); ?>
                    </td>
                    <td class="px-4 py-2">
                        <?php echo date('M j, Y H:i:s', strtotime($backup['created_at'])); ?>
                    </td>
                    <td class="px-4 py-2">
                        <a href="<?php echo 'uploads/' . $backup['filename']; ?>" 
                           class="text-blue-600 hover:text-blue-800 mr-2"
                           download>
                            <i class="fas fa-download"></i>
                        </a>
                        <button onclick="confirmRestore('<?php echo $backup['filename']; ?>')"
                                class="text-green-600 hover:text-green-800">
                            <i class="fas fa-undo"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($backups['pages'] > 1): ?>
        <div class="mt-4 flex justify-center">
            <div class="flex space-x-2">
                <?php for ($i = 1; $i <= $backups['pages']; $i++): ?>
                    <a href="?page=<?php echo $i; ?>"
                       class="px-4 py-2 rounded-md <?php echo $i === $backups['current_page'] ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Backup Modal -->
<div id="createBackupModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg w-full max-w-md mx-4">
        <div class="flex justify-between items-center p-6 border-b">
            <h3 class="text-xl font-semibold">Create Backup</h3>
            <button onclick="closeModal('createBackupModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="" method="POST" class="p-6">
            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="create">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="type">
                    Backup Type
                </label>
                <select name="type" id="type" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="full">Full Backup</option>
                    <option value="partial">Partial Backup (Exclude Logs)</option>
                </select>
            </div>
            
            <div class="flex justify-end">
                <button type="button" onclick="closeModal('createBackupModal')"
                        class="bg-gray-500 text-white px-4 py-2 rounded mr-2 hover:bg-gray-600">
                    Cancel
                </button>
                <button type="submit"
                        class="bg-primary text-white px-4 py-2 rounded hover:bg-secondary">
                    Create Backup
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Schedule Backup Modal -->
<div id="scheduleBackupModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg w-full max-w-md mx-4">
        <div class="flex justify-between items-center p-6 border-b">
            <h3 class="text-xl font-semibold">Schedule Backup</h3>
            <button onclick="closeModal('scheduleBackupModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="" method="POST" class="p-6">
            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="schedule">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="schedule_type">
                    Backup Type
                </label>
                <select name="schedule_type" id="schedule_type" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="full">Full Backup</option>
                    <option value="partial">Partial Backup</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="frequency">
                    Frequency (hours)
                </label>
                <select name="frequency" id="frequency" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="24">Daily</option>
                    <option value="168">Weekly</option>
                    <option value="720">Monthly</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="scheduled_time">
                    Time
                </label>
                <input type="time" name="scheduled_time" id="scheduled_time" required
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            
            <div class="flex justify-end">
                <button type="button" onclick="closeModal('scheduleBackupModal')"
                        class="bg-gray-500 text-white px-4 py-2 rounded mr-2 hover:bg-gray-600">
                    Cancel
                </button>
                <button type="submit"
                        class="bg-primary text-white px-4 py-2 rounded hover:bg-secondary">
                    Schedule
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Restore Confirmation Modal -->
<div id="restoreModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg w-full max-w-md mx-4">
        <div class="p-6">
            <h3 class="text-xl font-semibold mb-4">Confirm Restore</h3>
            <p class="mb-6">Are you sure you want to restore this backup? This will overwrite your current database.</p>
            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="restore">
                <input type="hidden" name="filename" id="restore_filename">
                <div class="flex justify-end">
                    <button type="button" onclick="closeModal('restoreModal')"
                            class="bg-gray-500 text-white px-4 py-2 rounded mr-2 hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit"
                            class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Restore
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#backupsTable').DataTable({
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf'],
        order: [[4, 'desc']]
    });
});

function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

function confirmRestore(filename) {
    document.getElementById('restore_filename').value = filename;
    openModal('restoreModal');
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
