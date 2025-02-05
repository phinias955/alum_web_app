<?php
$pageTitle = 'Alumni Management';
require_once __DIR__ . '/controllers/AlumniController.php';
require_once __DIR__ . '/includes/layout.php';

// Initialize controller
$alumniController = new AlumniController();

// Handle search
$searchTerm = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;

try {
    if (!empty($searchTerm)) {
        $alumni = $alumniController->searchAlumni($searchTerm, $page, $limit);
    } else {
        $alumni = $alumniController->getAll($page, $limit);
    }
    
    // Ensure we have the data property
    if (!isset($alumni['data'])) {
        $alumni = ['data' => [], 'total' => 0, 'page' => $page, 'limit' => $limit, 'total_pages' => 0];
    }
} catch (Exception $e) {
    error_log("Alumni List Error: " . $e->getMessage());
    $error = "An error occurred while loading the alumni list.";
    $alumni = ['data' => [], 'total' => 0, 'page' => $page, 'limit' => $limit, 'total_pages' => 0];
}

// Header buttons
$headerButtons = <<<HTML
<div class="flex space-x-4">
    <a href="alumni-add.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
        <i class="fas fa-plus mr-2"></i> Add Alumni
    </a>
    <a href="alumni-export.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
        <i class="fas fa-file-export mr-2"></i> Export
    </a>
</div>
HTML;

// Main content
ob_start();
?>

<!-- Search and filters -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-4">
        <form action="" method="GET" class="flex gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" 
                       placeholder="Search alumni..." 
                       class="w-full px-4 py-2 border rounded-md focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700">
                <i class="fas fa-search mr-2"></i> Search
            </button>
        </form>
    </div>
</div>

<?php if (isset($error)): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
</div>
<?php endif; ?>

<!-- Alumni list -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Graduation Year</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($alumni['data'])): ?>
            <tr>
                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                    No alumni records found.
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($alumni['data'] as $alum): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <?php if (!empty($alum['profile_image'])): ?>
                                <img class="h-10 w-10 rounded-full" src="<?php echo htmlspecialchars($alum['profile_image']); ?>" alt="">
                                <?php else: ?>
                                <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                    <span class="text-gray-600 text-lg">
                                        <?php echo strtoupper(substr($alum['first_name'] ?? 'A', 0, 1)); ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars(($alum['first_name'] ?? '') . ' ' . ($alum['last_name'] ?? '')); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($alum['email'] ?? ''); ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($alum['course'] ?? 'N/A'); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($alum['graduation_year'] ?? 'N/A'); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                   <?php echo ($alum['status'] ?? '') === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo ucfirst(htmlspecialchars($alum['status'] ?? 'inactive')); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-3">
                            <?php if (isset($alum['id'])): ?>
                            <a href="alumni-view.php?id=<?php echo htmlspecialchars($alum['id']); ?>" 
                               class="text-indigo-600 hover:text-indigo-900">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="alumni-edit.php?id=<?php echo htmlspecialchars($alum['id']); ?>" 
                               class="text-blue-600 hover:text-blue-900">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="#" onclick="deleteAlumni(<?php echo htmlspecialchars($alum['id']); ?>)" 
                               class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($alumni['total_pages'] > 1): ?>
<div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 mt-4 rounded-lg shadow">
    <div class="flex-1 flex justify-between sm:hidden">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?><?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?>" 
           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            Previous
        </a>
        <?php endif; ?>
        <?php if ($page < $alumni['total_pages']): ?>
        <a href="?page=<?php echo $page + 1; ?><?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?>" 
           class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            Next
        </a>
        <?php endif; ?>
    </div>
    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-gray-700">
                Showing <span class="font-medium"><?php echo ($page - 1) * $limit + 1; ?></span> to 
                <span class="font-medium"><?php echo min($page * $limit, $alumni['total']); ?></span> of 
                <span class="font-medium"><?php echo $alumni['total']; ?></span> results
            </p>
        </div>
        <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <?php for ($i = 1; $i <= $alumni['total_pages']; $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?>" 
                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50
                          <?php echo $i === $page ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
            </nav>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/includes/layout.php';
?>
