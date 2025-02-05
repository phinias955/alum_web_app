<?php
require_once 'config/config.php';
require_once 'controllers/NewsController.php';

Security::initSession();

if (!Security::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$newsController = new NewsController();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Security::verifyCSRFToken($_POST['csrf_token'] ?? '');
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    $data = [
                        'title' => Security::sanitizeInput($_POST['title']),
                        'content' => Security::sanitizeInput($_POST['content']),
                        'status' => $_POST['status']
                    ];
                    $newsController->createNews($data, $_FILES['image'] ?? null);
                    $_SESSION['flash_message'] = "News article created successfully";
                    $_SESSION['flash_type'] = "bg-green-100 text-green-700";
                    break;

                case 'update':
                    $data = [
                        'title' => Security::sanitizeInput($_POST['title']),
                        'content' => Security::sanitizeInput($_POST['content']),
                        'status' => $_POST['status']
                    ];
                    $newsController->updateNews($_POST['id'], $data, $_FILES['image'] ?? null);
                    $_SESSION['flash_message'] = "News article updated successfully";
                    $_SESSION['flash_type'] = "bg-green-100 text-green-700";
                    break;

                case 'delete':
                    $newsController->deleteNews($_POST['id']);
                    $_SESSION['flash_message'] = "News article deleted successfully";
                    $_SESSION['flash_type'] = "bg-green-100 text-green-700";
                    break;

                case 'toggle-status':
                    $newStatus = $newsController->toggleStatus($_POST['id']);
                    echo json_encode(['status' => 'success', 'newStatus' => $newStatus]);
                    exit;
            }
        }
        
        header('Location: news.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['flash_message'] = $e->getMessage();
        $_SESSION['flash_type'] = "bg-red-100 text-red-700";
    }
}

// Get news articles
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? Security::sanitizeInput($_GET['search']) : '';

if ($search) {
    $newsData = $newsController->searchNews($search, $page);
} else {
    $newsData = $newsController->getAll($page);
}

$pageTitle = "News Management";
ob_start();
?>

<!-- Add News Button -->
<div class="mb-6">
    <button class="bg-primary text-white px-6 py-2 rounded-md hover:bg-secondary transition duration-300"
            onclick="openModal('createNewsModal')">
        <i class="fas fa-plus mr-2"></i>Add News
    </button>
</div>

<!-- News List -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="p-6">
        <table id="newsTable" class="w-full">
            <thead>
                <tr>
                    <th class="px-4 py-2">Title</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Created At</th>
                    <th class="px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($newsData['data'] as $news): ?>
                <tr>
                    <td class="px-4 py-2"><?php echo htmlspecialchars($news['title']); ?></td>
                    <td class="px-4 py-2">
                        <span class="px-2 py-1 rounded-full text-xs <?php echo $news['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo ucfirst($news['status']); ?>
                        </span>
                    </td>
                    <td class="px-4 py-2"><?php echo date('M j, Y', strtotime($news['created_at'])); ?></td>
                    <td class="px-4 py-2">
                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($news)); ?>)"
                                class="text-blue-600 hover:text-blue-800 mr-2">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="toggleStatus(<?php echo $news['id']; ?>)"
                                class="text-yellow-600 hover:text-yellow-800 mr-2">
                            <i class="fas fa-toggle-on"></i>
                        </button>
                        <button onclick="confirmDelete(<?php echo $news['id']; ?>)"
                                class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create News Modal -->
<div id="createNewsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg w-full max-w-2xl mx-4">
        <div class="flex justify-between items-center p-6 border-b">
            <h3 class="text-xl font-semibold">Add News Article</h3>
            <button onclick="closeModal('createNewsModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="" method="POST" enctype="multipart/form-data" class="p-6">
            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="create">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                    Title
                </label>
                <input type="text" name="title" id="title" required
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="content">
                    Content
                </label>
                <textarea name="content" id="content" rows="6" required
                          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="image">
                    Image
                </label>
                <input type="file" name="image" id="image" accept="image/*"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="status">
                    Status
                </label>
                <select name="status" id="status" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
            </div>
            
            <div class="flex justify-end">
                <button type="button" onclick="closeModal('createNewsModal')"
                        class="bg-gray-500 text-white px-4 py-2 rounded mr-2 hover:bg-gray-600">
                    Cancel
                </button>
                <button type="submit"
                        class="bg-primary text-white px-4 py-2 rounded hover:bg-secondary">
                    Create
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit News Modal -->
<div id="editNewsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg w-full max-w-2xl mx-4">
        <div class="flex justify-between items-center p-6 border-b">
            <h3 class="text-xl font-semibold">Edit News Article</h3>
            <button onclick="closeModal('editNewsModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="" method="POST" enctype="multipart/form-data" class="p-6">
            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_title">
                    Title
                </label>
                <input type="text" name="title" id="edit_title" required
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_content">
                    Content
                </label>
                <textarea name="content" id="edit_content" rows="6" required
                          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_image">
                    Image
                </label>
                <input type="file" name="image" id="edit_image" accept="image/*"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <div id="current_image" class="mt-2"></div>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_status">
                    Status
                </label>
                <select name="status" id="edit_status" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
            </div>
            
            <div class="flex justify-end">
                <button type="button" onclick="closeModal('editNewsModal')"
                        class="bg-gray-500 text-white px-4 py-2 rounded mr-2 hover:bg-gray-600">
                    Cancel
                </button>
                <button type="submit"
                        class="bg-primary text-white px-4 py-2 rounded hover:bg-secondary">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg w-full max-w-md mx-4">
        <div class="p-6">
            <h3 class="text-xl font-semibold mb-4">Confirm Delete</h3>
            <p class="mb-6">Are you sure you want to delete this news article? This action cannot be undone.</p>
            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <div class="flex justify-end">
                    <button type="button" onclick="closeModal('deleteModal')"
                            class="bg-gray-500 text-white px-4 py-2 rounded mr-2 hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit"
                            class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                        Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#newsTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf'
        ]
    });
});

function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

function openEditModal(news) {
    document.getElementById('edit_id').value = news.id;
    document.getElementById('edit_title').value = news.title;
    document.getElementById('edit_content').value = news.content;
    document.getElementById('edit_status').value = news.status;
    
    if (news.image_url) {
        document.getElementById('current_image').innerHTML = `
            <img src="${news.image_url}" alt="Current Image" class="h-20 w-auto">
        `;
    }
    
    openModal('editNewsModal');
}

function confirmDelete(id) {
    document.getElementById('delete_id').value = id;
    openModal('deleteModal');
}

function toggleStatus(id) {
    fetch('news.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=toggle-status&id=${id}&csrf_token=<?php echo Security::generateCSRFToken(); ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            location.reload();
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
