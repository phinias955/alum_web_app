<?php
$pageTitle = 'Edit Alumni';
require_once __DIR__ . '/controllers/AlumniController.php';
require_once __DIR__ . '/includes/layout.php';

// Initialize controller
$alumniController = new AlumniController();

// Get alumni ID
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: alumni.php');
    exit;
}

try {
    // Get alumni data
    $alumni = $alumniController->getById($id);
    if (!$alumni) {
        throw new Exception('Alumni not found');
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $alumniController->updateAlumni($id, $_POST, $_FILES['profile_image'] ?? null);
        $_SESSION['success'] = 'Alumni record updated successfully.';
        header('Location: alumni.php');
        exit;
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Main content
ob_start();
?>

<div class="bg-white rounded-lg shadow-lg p-6">
    <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
        <!-- Profile Image -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Profile Image</label>
            <div class="mt-1 flex items-center">
                <?php if (!empty($alumni['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($alumni['profile_image']); ?>" 
                         alt="Current profile" 
                         class="w-20 h-20 rounded-full object-cover">
                <?php else: ?>
                    <div class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center">
                        <i class="fas fa-user text-gray-400 text-2xl"></i>
                    </div>
                <?php endif; ?>
                <input type="file" name="profile_image" accept="image/*" 
                       class="ml-5 bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            </div>
        </div>

        <!-- Personal Information -->
        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
            <div>
                <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                <input type="text" name="first_name" id="first_name" required
                       value="<?php echo htmlspecialchars($alumni['first_name']); ?>"
                       class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>

            <div>
                <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                <input type="text" name="last_name" id="last_name" required
                       value="<?php echo htmlspecialchars($alumni['last_name']); ?>"
                       class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="email" required
                       value="<?php echo htmlspecialchars($alumni['email']); ?>"
                       class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                <input type="tel" name="phone" id="phone"
                       value="<?php echo htmlspecialchars($alumni['phone'] ?? ''); ?>"
                       class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
        </div>

        <!-- Academic Information -->
        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
            <div>
                <label for="course" class="block text-sm font-medium text-gray-700">Course</label>
                <input type="text" name="course" id="course" required
                       value="<?php echo htmlspecialchars($alumni['course']); ?>"
                       class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>

            <div>
                <label for="graduation_year" class="block text-sm font-medium text-gray-700">Graduation Year</label>
                <input type="number" name="graduation_year" id="graduation_year" required
                       value="<?php echo htmlspecialchars($alumni['graduation_year']); ?>"
                       min="1900" max="<?php echo date('Y'); ?>"
                       class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
        </div>

        <!-- Professional Information -->
        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
            <div>
                <label for="current_job" class="block text-sm font-medium text-gray-700">Current Job</label>
                <input type="text" name="current_job" id="current_job"
                       value="<?php echo htmlspecialchars($alumni['current_job'] ?? ''); ?>"
                       class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>

            <div>
                <label for="company" class="block text-sm font-medium text-gray-700">Company</label>
                <input type="text" name="company" id="company"
                       value="<?php echo htmlspecialchars($alumni['company'] ?? ''); ?>"
                       class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>

            <div>
                <label for="linkedin_url" class="block text-sm font-medium text-gray-700">LinkedIn Profile</label>
                <input type="url" name="linkedin_url" id="linkedin_url"
                       value="<?php echo htmlspecialchars($alumni['linkedin_url'] ?? ''); ?>"
                       class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" id="status"
                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="active" <?php echo ($alumni['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($alumni['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-end space-x-3">
            <a href="alumni.php" 
               class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Cancel
            </a>
            <button type="submit"
                    class="bg-indigo-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Update Alumni
            </button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
?>
