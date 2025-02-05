<?php
require_once __DIR__ . '/controllers/AlumniController.php';
require_once __DIR__ . '/includes/layout.php';

// Initialize controller
$alumniController = new AlumniController();

// Get alumni ID
$id = $_GET['id'] ?? null;
if (!$id) {
    $_SESSION['error'] = 'Invalid alumni ID.';
    header('Location: alumni.php');
    exit;
}

try {
    // Delete alumni
    $alumniController->deleteAlumni($id);
    $_SESSION['success'] = 'Alumni record deleted successfully.';
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: alumni.php');
exit;
?>
