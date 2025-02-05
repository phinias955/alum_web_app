<?php
require_once '../config/Database.php';
require_once '../includes/Security.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use TCPDF as PDF;

// Check if user is logged in and has admin access
if (!Security::isLoggedIn()) {
    http_response_code(401);
    exit('Unauthorized');
}

if (!Security::hasRole('admin')) {
    http_response_code(403);
    exit('Forbidden');
}

// Initialize database connection
$conn = Database::getInstance()->getConnection();

// Get export parameters
$reportType = $_POST['type'] ?? 'users';
$format = $_POST['format'] ?? 'excel';

// Function to get report data based on type
function getReportData($conn, $type) {
    switch ($type) {
        case 'users':
            $query = "SELECT id, username, email, role, status, last_login_at, created_at FROM users";
            break;
        case 'alumni':
            $query = "SELECT a.id, a.first_name, a.last_name, a.email, a.graduation_year, a.course, 
                     a.current_job, a.company, a.phone, a.status, a.created_at 
                     FROM alumni a";
            break;
        case 'events':
            $query = "SELECT e.id, e.title, e.description, e.event_date, e.event_time, 
                     e.location, e.status, u.username as created_by, e.created_at 
                     FROM events e 
                     LEFT JOIN users u ON e.created_by = u.id 
                     ORDER BY e.event_date DESC";
            break;
        case 'activity':
            $query = "SELECT al.id, u.username, al.activity, al.ip_address, 
                     al.created_at 
                     FROM activity_logs al 
                     LEFT JOIN users u ON al.user_id = u.id 
                     ORDER BY al.created_at DESC";
            break;
        default:
            return [];
    }
    
    $result = $conn->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Get the data
$data = getReportData($conn, $reportType);

// Set headers based on format
if ($format === 'excel') {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $reportType . '_report.xlsx"');
    header('Cache-Control: max-age=0');
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Add headers
    if (!empty($data)) {
        $columns = array_keys($data[0]);
        foreach ($columns as $idx => $column) {
            $sheet->setCellValue(chr(65 + $idx) . '1', ucwords(str_replace('_', ' ', $column)));
        }
        
        // Add data
        $row = 2;
        foreach ($data as $item) {
            foreach (array_values($item) as $idx => $value) {
                $sheet->setCellValue(chr(65 + $idx) . $row, $value);
            }
            $row++;
        }
    }
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
} else if ($format === 'pdf') {
    // Create new PDF document
    $pdf = new PDF();
    $pdf->SetCreator('Alumni System');
    $pdf->SetAuthor('Admin');
    $pdf->SetTitle(ucwords($reportType) . ' Report');
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 12);
    
    // Add title
    $pdf->Cell(0, 10, ucwords($reportType) . ' Report', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Add table headers
    if (!empty($data)) {
        $columns = array_keys($data[0]);
        $width = 190 / count($columns);
        
        foreach ($columns as $column) {
            $pdf->Cell($width, 7, ucwords(str_replace('_', ' ', $column)), 1);
        }
        $pdf->Ln();
        
        // Add data rows
        foreach ($data as $row) {
            foreach ($row as $cell) {
                $pdf->Cell($width, 6, $cell, 1);
            }
            $pdf->Ln();
        }
    }
    
    // Output PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment;filename="' . $reportType . '_report.pdf"');
    $pdf->Output($reportType . '_report.pdf', 'D');
}

exit();
