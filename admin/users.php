<?php
$pageTitle = 'User Management';
define('INCLUDED_FROM_ADMIN', true);

// Set extra scripts
$extraScripts = '<script src="assets/js/users.js"></script>';

// Start output buffering
ob_start();

require_once 'config/Database.php';
require_once 'includes/Security.php';

// Initialize security and check permissions
Security::initSession();
Security::redirectIfNotAdmin();

try {
    // Database connection
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Fetch users
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll();

    // Handle search and filters
    $search = isset($_GET['search']) ? Security::sanitizeInput($_GET['search']) : '';
    $statusFilter = isset($_GET['status']) ? Security::sanitizeInput($_GET['status']) : '';
    $roleFilter = isset($_GET['role']) ? Security::sanitizeInput($_GET['role']) : '';
    $sortBy = isset($_GET['sort']) ? Security::sanitizeInput($_GET['sort']) : 'username';
    $sortOrder = isset($_GET['order']) ? Security::sanitizeInput($_GET['order']) : 'ASC';

    // Pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Build query with filters
    $query = "SELECT SQL_CALC_FOUND_ROWS id, username, email, role, status, created_at, last_login_at 
              FROM users WHERE 1=1";
    $params = [];
    $types = "";

    if ($search) {
        $query .= " AND (username LIKE :search OR email LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if ($statusFilter) {
        $query .= " AND status = :status";
        $params[':status'] = $statusFilter;
    }

    if ($roleFilter) {
        $query .= " AND role = :role";
        $params[':role'] = $roleFilter;
    }

    $query .= " ORDER BY $sortBy $sortOrder LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    // Prepare and execute query
    $stmt = $conn->prepare($query);
    foreach ($params as $key => &$val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total records for pagination
    $totalResult = $conn->query("SELECT FOUND_ROWS() as total");
    $totalUsers = $totalResult->fetchColumn();
    $totalPages = ceil($totalUsers / $limit);

    // Get user statistics
    $statsQuery = "SELECT status, COUNT(*) as count FROM users GROUP BY status";
    $stats = $conn->query($statsQuery)->fetchAll(PDO::FETCH_ASSOC);

    // Get roles for filter
    $rolesQuery = "SELECT DISTINCT role FROM users";
    $roles = $conn->query($rolesQuery)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = "An error occurred while fetching users";
}
?>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">User Management</h1>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Users</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $totalUsers; ?></p>
                </div>
                <div class="bg-blue-500 text-white p-3 rounded-lg">
                    <svg class="icon icon-lg" viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
            </div>
        </div>
        <?php foreach ($stats as $stat): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm font-medium text-gray-500"><?php echo ucfirst($stat['status']); ?> Users</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $stat['count']; ?></p>
                </div>
                <div class="bg-<?php echo $stat['status'] === 'active' ? 'green' : 'yellow'; ?>-500 text-white p-3 rounded-lg">
                    <?php if ($stat['status'] === 'active'): ?>
                    <svg class="icon icon-lg" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                    <?php else: ?>
                    <svg class="icon icon-lg" viewBox="0 0 24 24">
                        <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                    </svg>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filters and Search -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="col-span-2">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="icon text-gray-400" viewBox="0 0 24 24">
                            <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                        </svg>
                    </div>
                    <input type="text" name="search" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 sm:text-sm" 
                           placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div>
                <select name="status" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 sm:text-sm rounded-md">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div>
                <select name="role" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 sm:text-sm rounded-md">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $role): ?>
                    <option value="<?php echo $role['role']; ?>" <?php echo $roleFilter === $role['role'] ? 'selected' : ''; ?>>
                        <?php echo ucfirst($role['role']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex space-x-4">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-500 hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="icon mr-2" viewBox="0 0 24 24">
                        <path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z"/>
                    </svg>
                    Apply Filters
                </button>
                <button type="button" onclick="openModal('createUserModal')" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-500 hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <svg class="icon mr-2" viewBox="0 0 24 24">
                        <path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                    Add User
                </button>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="?sort=username&order=<?php echo $sortBy === 'username' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>" 
                               class="group inline-flex items-center">
                                Username
                                <?php if ($sortBy === 'username'): ?>
                                <i class="bi bi-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?> ml-2"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="?sort=email&order=<?php echo $sortBy === 'email' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>"
                               class="group inline-flex items-center">
                                Email
                                <?php if ($sortBy === 'email'): ?>
                                <i class="bi bi-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?> ml-2"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="?sort=last_login_at&order=<?php echo $sortBy === 'last_login_at' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>"
                               class="group inline-flex items-center">
                                Last Login
                                <?php if ($sortBy === 'last_login_at'): ?>
                                <i class="bi bi-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?> ml-2"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <?php if (!empty($user['profile_picture'])): ?>
                                        <img src="../uploads/profile_pictures/<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                             alt="Profile" class="h-10 w-10 rounded-full">
                                    <?php else: ?>
                                        <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-medium">
                                            <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['role'] === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                <i class="bi bi-<?php echo $user['status'] === 'active' ? 'check' : 'clock'; ?> text-base mr-1"></i>
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php if ($user['last_login_at']): ?>
                                <span data-bs-toggle="tooltip" title="<?php echo date('Y-m-d H:i:s', strtotime($user['last_login_at'])); ?>">
                                    <?php echo date('M d, Y H:i', strtotime($user['last_login_at'])); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400">Never</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-3">
                                <!-- View -->
                                <button onclick="viewUser(<?php echo $user['id']; ?>)" 
                                        class="p-1.5 rounded-lg text-sky-700 hover:text-white hover:bg-sky-700 transition-colors duration-200">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                </button>

                                <!-- Edit -->
                                <button onclick="editUser(<?php echo $user['id']; ?>)" 
                                        class="p-1.5 rounded-lg text-emerald-600 hover:text-white hover:bg-emerald-600 transition-colors duration-200">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                    </svg>
                                </button>

                                <!-- Reset Password -->
                                <button onclick="resetPassword(<?php echo $user['id']; ?>)" 
                                        class="p-1.5 rounded-lg text-amber-500 hover:text-white hover:bg-amber-500 transition-colors duration-200">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                                    </svg>
                                </button>

                                <!-- Toggle Status -->
                                <button onclick="toggleStatus(<?php echo $user['id']; ?>)" 
                                        class="p-1.5 rounded-lg <?php echo $user['status'] === 'active' ? 'text-purple-600 hover:bg-purple-600' : 'text-indigo-600 hover:bg-indigo-600'; ?> hover:text-white transition-colors duration-200">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </button>

                                <!-- Delete -->
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <button onclick="deleteUser(<?php echo $user['id']; ?>)" 
                                        class="p-1.5 rounded-lg text-rose-600 hover:text-white hover:bg-rose-600 transition-colors duration-200">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M6 2l2-2h4l2 2h4v2H2V2h4zM3 6h14l-1 14H4L3 6zm5 2v10h1V8H8zm3 0v10h1V8h-1z"/>
                                    </svg>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            <div class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&role=<?php echo $roleFilter; ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Previous
                    </a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&role=<?php echo $roleFilter; ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>" 
                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Next
                    </a>
                    <?php endif; ?>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to 
                            <span class="font-medium"><?php echo min($offset + $limit, $totalUsers); ?></span> of 
                            <span class="font-medium"><?php echo $totalUsers; ?></span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <?php if ($page > 1): ?>
                            <a href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&role=<?php echo $roleFilter; ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>" 
                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Previous</span>
                                <svg class="icon" viewBox="0 0 24 24">
                                    <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
                                </svg>
                            </a>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 1);
                            $end = min($totalPages, $page + 1);

                            if ($start > 1): ?>
                            <a href="?page=1&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&role=<?php echo $roleFilter; ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                1
                            </a>
                            <?php if ($start > 2): ?>
                            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                ...
                            </span>
                            <?php endif;
                            endif;

                            for ($i = $start; $i <= $end; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&role=<?php echo $roleFilter; ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $page === $i ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor;

                            if ($end < $totalPages): 
                            if ($end < $totalPages - 1): ?>
                            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                ...
                            </span>
                            <?php endif; ?>
                            <a href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&role=<?php echo $roleFilter; ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                <?php echo $totalPages; ?>
                            </a>
                            <?php endif;

                            if ($page < $totalPages): ?>
                            <a href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&role=<?php echo $roleFilter; ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>" 
                               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Next</span>
                                <svg class="icon" viewBox="0 0 24 24">
                                    <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
                                </svg>
                            </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Get buffered content
$content = ob_get_clean();

// Include layout
require_once 'includes/layout.php';
?>
