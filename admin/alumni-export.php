<?php
require_once __DIR__ . '/controllers/AlumniController.php';

// Initialize controller
$alumniController = new AlumniController();

try {
    // Generate CSV file
    $filename = 'alumni_export_' . date('Y-m-d_His') . '.csv';
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write headers
    fputcsv($output, [
        'First Name',
        'Last Name',
        'Email',
        'Phone',
        'Course',
        'Graduation Year',
        'Current Job',
        'Company',
        'LinkedIn Profile',
        'Status'
    ]);
    
    // Get all alumni
    $alumni = $alumniController->getAllForExport();
    
    // Write data rows
    foreach ($alumni as $alum) {
        fputcsv($output, [
            $alum['first_name'],
            $alum['last_name'],
            $alum['email'],
            $alum['phone'] ?? '',
            $alum['course'],
            $alum['graduation_year'],
            $alum['current_job'] ?? '',
            $alum['company'] ?? '',
            $alum['linkedin_url'] ?? '',
            $alum['status']
        ]);
    }
    
    // Close output stream
    fclose($output);
    exit;
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Error exporting alumni data: ' . $e->getMessage();
    header('Location: alumni.php');
    exit;
}
?>
