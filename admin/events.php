<?php
require_once 'config/config.php';
require_once 'controllers/EventController.php';

Security::initSession();

if (!Security::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$eventController = new EventController();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Security::verifyCSRFToken($_POST['csrf_token'] ?? '');
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    $data = [
                        'title' => Security::sanitizeInput($_POST['title']),
                        'description' => Security::sanitizeInput($_POST['description']),
                        'event_date' => $_POST['event_date'],
                        'location' => Security::sanitizeInput($_POST['location']),
                        'type' => Security::sanitizeInput($_POST['type']),
                        'status' => $_POST['status']
                    ];
                    $eventController->createEvent($data, $_FILES['image'] ?? null);
                    $_SESSION['flash_message'] = "Event created successfully";
                    $_SESSION['flash_type'] = "bg-green-100 text-green-700";
                    break;

                case 'update':
                    $data = [
                        'title' => Security::sanitizeInput($_POST['title']),
                        'description' => Security::sanitizeInput($_POST['description']),
                        'event_date' => $_POST['event_date'],
                        'location' => Security::sanitizeInput($_POST['location']),
                        'type' => Security::sanitizeInput($_POST['type']),
                        'status' => $_POST['status']
                    ];
                    $eventController->updateEvent($_POST['id'], $data, $_FILES['image'] ?? null);
                    $_SESSION['flash_message'] = "Event updated successfully";
                    $_SESSION['flash_type'] = "bg-green-100 text-green-700";
                    break;

                case 'delete':
                    $eventController->deleteEvent($_POST['id']);
                    $_SESSION['flash_message'] = "Event deleted successfully";
                    $_SESSION['flash_type'] = "bg-green-100 text-green-700";
                    break;

                case 'update-status':
                    $eventController->updateEventStatus($_POST['id'], $_POST['status']);
                    echo json_encode(['status' => 'success']);
                    exit;
            }
        }
        
        header('Location: events.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['flash_message'] = $e->getMessage();
        $_SESSION['flash_type'] = "bg-red-100 text-red-700";
    }
}

// Get events
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? Security::sanitizeInput($_GET['search']) : '';

if ($search) {
    $eventsData = $eventController->searchEvents($search, $page);
} else {
    $eventsData = $eventController->getAll($page);
}

$pageTitle = "Events Management";
ob_start();
?>

<!-- Add Event Button -->
<div class="mb-6">
    <button class="bg-primary text-white px-6 py-2 rounded-md hover:bg-secondary transition duration-300"
            onclick="openModal('createEventModal')">
        <i class="fas fa-plus mr-2"></i>Add Event
    </button>
</div>

<!-- Events List -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="p-6">
        <table id="eventsTable" class="w-full">
            <thead>
                <tr>
                    <th class="px-4 py-2">Title</th>
                    <th class="px-4 py-2">Date</th>
                    <th class="px-4 py-2">Location</th>
                    <th class="px-4 py-2">Type</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($eventsData['data'] as $event): ?>
                <tr>
                    <td class="px-4 py-2"><?php echo htmlspecialchars($event['title']); ?></td>
                    <td class="px-4 py-2"><?php echo date('M j, Y H:i', strtotime($event['event_date'])); ?></td>
                    <td class="px-4 py-2"><?php echo htmlspecialchars($event['location']); ?></td>
                    <td class="px-4 py-2">
                        <span class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">
                            <?php echo htmlspecialchars($event['type']); ?>
                        </span>
                    </td>
                    <td class="px-4 py-2">
                        <select onchange="updateStatus(<?php echo $event['id']; ?>, this.value)"
                                class="text-sm rounded-full px-2 py-1 border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="upcoming" <?php echo $event['status'] === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                            <option value="ongoing" <?php echo $event['status'] === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                            <option value="completed" <?php echo $event['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $event['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </td>
                    <td class="px-4 py-2">
                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($event)); ?>)"
                                class="text-blue-600 hover:text-blue-800 mr-2">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="confirmDelete(<?php echo $event['id']; ?>)"
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

<!-- Create Event Modal -->
<div id="createEventModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg w-full max-w-2xl mx-4">
        <div class="flex justify-between items-center p-6 border-b">
            <h3 class="text-xl font-semibold">Add Event</h3>
            <button onclick="closeModal('createEventModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="" method="POST" enctype="multipart/form-data" class="p-6">
            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="create">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                        Title
                    </label>
                    <input type="text" name="title" id="title" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="event_date">
                        Event Date & Time
                    </label>
                    <input type="datetime-local" name="event_date" id="event_date" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                    Description
                </label>
                <textarea name="description" id="description" rows="4" required
                          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="location">
                        Location
                    </label>
                    <input type="text" name="location" id="location" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="type">
                        Event Type
                    </label>
                    <select name="type" id="type" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="Networking">Networking</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Seminar">Seminar</option>
                        <option value="Social">Social</option>
                        <option value="Career Fair">Career Fair</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="image">
                    Event Image
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
                    <option value="upcoming">Upcoming</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            
            <div class="flex justify-end">
                <button type="button" onclick="closeModal('createEventModal')"
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

<!-- Edit Event Modal -->
<div id="editEventModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg w-full max-w-2xl mx-4">
        <div class="flex justify-between items-center p-6 border-b">
            <h3 class="text-xl font-semibold">Edit Event</h3>
            <button onclick="closeModal('editEventModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="" method="POST" enctype="multipart/form-data" class="p-6">
            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_title">
                        Title
                    </label>
                    <input type="text" name="title" id="edit_title" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_event_date">
                        Event Date & Time
                    </label>
                    <input type="datetime-local" name="event_date" id="edit_event_date" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_description">
                    Description
                </label>
                <textarea name="description" id="edit_description" rows="4" required
                          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_location">
                        Location
                    </label>
                    <input type="text" name="location" id="edit_location" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_type">
                        Event Type
                    </label>
                    <select name="type" id="edit_type" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="Networking">Networking</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Seminar">Seminar</option>
                        <option value="Social">Social</option>
                        <option value="Career Fair">Career Fair</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_image">
                    Event Image
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
                    <option value="upcoming">Upcoming</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            
            <div class="flex justify-end">
                <button type="button" onclick="closeModal('editEventModal')"
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
            <p class="mb-6">Are you sure you want to delete this event? This action cannot be undone.</p>
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
    $('#eventsTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf'
        ],
        order: [[1, 'asc']] // Sort by date by default
    });
});

function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

function openEditModal(event) {
    document.getElementById('edit_id').value = event.id;
    document.getElementById('edit_title').value = event.title;
    document.getElementById('edit_description').value = event.description;
    document.getElementById('edit_event_date').value = event.event_date.slice(0, 16); // Format for datetime-local
    document.getElementById('edit_location').value = event.location;
    document.getElementById('edit_type').value = event.type;
    document.getElementById('edit_status').value = event.status;
    
    if (event.image_url) {
        document.getElementById('current_image').innerHTML = `
            <img src="${event.image_url}" alt="Current Image" class="h-20 w-auto">
        `;
    }
    
    openModal('editEventModal');
}

function confirmDelete(id) {
    document.getElementById('delete_id').value = id;
    openModal('deleteModal');
}

function updateStatus(id, status) {
    fetch('events.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update-status&id=${id}&status=${status}&csrf_token=<?php echo Security::generateCSRFToken(); ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Optional: Show success message
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
