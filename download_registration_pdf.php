<?php
// download_registration_pdf.php - Download signed registration agreement PDF
session_start();
require_once 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    die('Unauthorized access');
}

// Get registration ID
$registration_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($registration_id <= 0) {
    http_response_code(400);
    die('Invalid registration ID');
}

// Fetch registration with PDF data
$stmt = $pdo->prepare("
    SELECT 
        registration_number,
        name_en,
        pdf_base64
    FROM registrations 
    WHERE id = ?
");
$stmt->execute([$registration_id]);
$registration = $stmt->fetch();

if (!$registration) {
    http_response_code(404);
    die('Registration not found');
}

if (empty($registration['pdf_base64'])) {
    http_response_code(404);
    die('PDF not available for this registration');
}

// Extract base64 data (remove data:application/pdf;base64, prefix if present)
$pdf_data = $registration['pdf_base64'];
if (strpos($pdf_data, 'data:application/pdf;base64,') === 0) {
    $pdf_data = substr($pdf_data, strlen('data:application/pdf;base64,'));
}

// Decode base64
$pdf_content = base64_decode($pdf_data);

if ($pdf_content === false) {
    http_response_code(500);
    die('Failed to decode PDF data');
}

// Generate filename
$filename = 'Registration_Agreement_' . $registration['registration_number'] . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $registration['name_en']) . '.pdf';

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdf_content));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Output PDF content
echo $pdf_content;
exit;