<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AlumniController.php';

try {
    $alumniController = new AlumniController();

    // Test creating an alumni
    $alumniData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
        'phone' => '1234567890',
        'course' => 'Computer Science',
        'graduation_year' => '2020',
        'current_job' => 'Software Engineer',
        'company' => 'Tech Corp',
        'linkedin_url' => 'https://linkedin.com/in/johndoe',
        'status' => 'active'
    ];

    echo "Creating test alumni record...\n";
    $newAlumniId = $alumniController->createAlumni($alumniData);
    echo "Created alumni with ID: $newAlumniId\n";

    // Test getting alumni by ID
    echo "\nFetching alumni details...\n";
    $alumni = $alumniController->getById($newAlumniId);
    if ($alumni) {
        echo "Retrieved alumni: {$alumni['first_name']} {$alumni['last_name']}\n";
    }

    // Test updating alumni
    $updateData = [
        'current_job' => 'Senior Software Engineer',
        'company' => 'Big Tech Corp'
    ];

    echo "\nUpdating alumni record...\n";
    $alumniController->updateAlumni($newAlumniId, $updateData);
    
    // Verify update
    $updatedAlumni = $alumniController->getById($newAlumniId);
    if ($updatedAlumni) {
        echo "Updated alumni job: {$updatedAlumni['current_job']} at {$updatedAlumni['company']}\n";
    }

    // Test search functionality
    echo "\nTesting search functionality...\n";
    $searchResults = $alumniController->searchAlumni('John');
    echo "Found " . count($searchResults['data']) . " results for 'John'\n";

    // Test delete functionality
    echo "\nDeleting test alumni record...\n";
    if ($alumniController->deleteAlumni($newAlumniId)) {
        echo "Successfully deleted alumni record\n";
    }
    
    // Verify deletion
    $deletedAlumni = $alumniController->getById($newAlumniId);
    if (!$deletedAlumni) {
        echo "Verified alumni record is deleted\n";
    }

    echo "\nAll tests completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
