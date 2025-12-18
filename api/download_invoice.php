<?php
/**
 * Invoice PDF Download - Registration Invoice
 * Uses existing FPDF library (same as generate_invoice_pdf.php)
 */

error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    exit('Invalid registration ID');
}

$registration_id = intval($_GET['id']);

try {
    // Fetch registration and student data
    $stmt = $pdo->prepare("
        SELECT 
            r.id as registration_id,
            r.registration_number,
            r.amount,
            r.status,
            r.created_at,
            s.id as student_id,
            s.full_name as student_name,
            s.email as student_email,
            s.phone as student_phone,
            c.class_code,
            c.class_name
        FROM registrations r
        INNER JOIN students s ON r.student_id = s.id
        LEFT JOIN classes c ON r.class_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$registration_id]);
    $registration = $stmt->fetch();
    
    if (!$registration) {
        http_response_code(404);
        exit('Registration not found');
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    exit('Database error');
}

if (!file_exists('../fpdf.php')) {
    http_response_code(500);
    exit('PDF library missing');
}

require('../fpdf.php');

// Function to download and cache letterhead image
function getLetterheadImage() {
    $imageUrl = 'https://wushu-assets.s3.ap-southeast-1.amazonaws.com/WSP+Letter.png';
    $tempDir = sys_get_temp_dir();
    $cacheFile = $tempDir . '/wushu_letterhead.jpg';
    
    // Use cached image if exists and less than 24 hours old
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 86400)) {
        return $cacheFile;
    }
    
    // Download the image
    $imageData = @file_get_contents($imageUrl);
    if ($imageData === false) {
        return false;
    }
    
    // Create image from downloaded data
    $image = @imagecreatefromstring($imageData);
    if ($image === false) {
        return false;
    }
    
    // Convert to JPG for better FPDF compatibility
    $jpgImage = imagecreatetruecolor(imagesx($image), imagesy($image));
    
    // Fill background with white
    $white = imagecolorallocate($jpgImage, 255, 255, 255);
    imagefill($jpgImage, 0, 0, $white);
    
    // Copy image onto white background
    imagecopy($jpgImage, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
    
    // Save as JPG
    imagejpeg($jpgImage, $cacheFile, 95);
    imagedestroy($image);
    imagedestroy($jpgImage);
    
    return $cacheFile;
}

class InvoicePDF extends FPDF
{
    private $letterheadPath = '';
    private $registration;
    
    public function setLetterhead($path) {
        $this->letterheadPath = $path;
    }
    
    public function setRegistrationData($data) {
        $this->registration = $data;
    }
    
    function Header()
    {
        // Try to load letterhead
        if (!empty($this->letterheadPath) && file_exists($this->letterheadPath)) {
            try {
                // Place letterhead image - adjusted size and position for better look
                $this->Image($this->letterheadPath, 10, 8, 140, 25, 'JPG');
            } catch (Exception $e) {
                $this->createTextHeader();
            }
        } else {
            $this->createTextHeader();
        }
        
        // REGISTRATION badge - repositioned to top right
        $this->SetXY(165, 10);
        $this->SetFillColor(34, 197, 94);
        $this->SetDrawColor(34, 197, 94);
        $this->SetLineWidth(0.5);
        $this->Rect(165, 10, 30, 12, 'FD');
        $this->SetFont('Helvetica', 'B', 12);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(30, 12, 'ACTIVE', 0, 0, 'C');
        
        // Add registration number in top right
        $this->SetXY(155, 25);
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(40, 4, $this->registration['registration_number'], 0, 0, 'R');
        
        // Horizontal line separator
        $this->SetY(36);
        $this->SetDrawColor(15, 52, 96);
        $this->SetLineWidth(0.5);
        $this->Line(10, 36, 200, 36);
        
        $this->SetY(40);
    }
    
    function createTextHeader() {
        // Fallback: Clean professional header
        $this->SetFillColor(15, 52, 96);
        $this->Rect(0, 0, 210, 35, 'F');
        
        $this->SetXY(15, 12);
        $this->SetFont('Helvetica', 'B', 22);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 10, 'WUSHU SPORT ACADEMY', 0, 1, 'L');
    }
    
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(120, 120, 120);
        
        $this->Cell(0, 4, 'This is a computer-generated document. No signature required.', 0, 1, 'C');
        $this->Cell(0, 4, 'Generated: ' . date('d M Y, g:i A'), 0, 1, 'C');
    }
}

// Get letterhead image
$letterheadPath = getLetterheadImage();

// Create PDF
$pdf = new InvoicePDF('P', 'mm', 'A4');
$pdf->setRegistrationData($registration);
if ($letterheadPath) {
    $pdf->setLetterhead($letterheadPath);
}
$pdf->SetMargins(15, 43, 15);
$pdf->SetAutoPageBreak(true, 25);
$pdf->AddPage();

// ============================================================
// REGISTRATION DETAILS - TWO COLUMN LAYOUT
// ============================================================

// Left Column - Registration Details
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetTextColor(15, 52, 96);
$pdf->SetX(15);
$pdf->Cell(90, 6, 'REGISTRATION DETAILS', 0, 1, 'L');
$pdf->Ln(1);

$pdf->SetFont('Helvetica', '', 10);
$pdf->SetTextColor(80, 80, 80);

$details = [
    ['Registration Number:', $registration['registration_number']],
    ['Date Registered:', date('d M Y', strtotime($registration['created_at']))],
    ['Status:', strtoupper($registration['status'])],
    ['Registration Date:', date('d M Y', strtotime($registration['created_at']))],
];

foreach ($details as $row) {
    $pdf->SetX(15);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(35, 5.5, $row[0], 0, 0, 'L');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->Cell(0, 5.5, $row[1], 0, 1, 'L');
    $pdf->SetFont('Helvetica', '', 10);
}

// Right Column - Registered To
$pdf->SetXY(115, 43);
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetTextColor(15, 52, 96);
$pdf->Cell(80, 6, 'REGISTERED TO', 0, 1, 'L');
$pdf->Ln(1);

$pdf->SetX(115);
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5.5, $registration['student_name'], 0, 1, 'L');

$pdf->SetX(115);
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(22, 5, 'Email:', 0, 0, 'L');
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, $registration['student_email'], 0, 1, 'L');

if (!empty($registration['student_phone'])) {
    $pdf->SetX(115);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(22, 5, 'Phone:', 0, 0, 'L');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 5, $registration['student_phone'], 0, 1, 'L');
}

$pdf->Ln(7);

// ============================================================
// LINE ITEMS TABLE
// ============================================================

$pdf->SetX(15);
$pdf->SetFillColor(15, 52, 96);
$pdf->SetDrawColor(15, 52, 96);
$pdf->SetLineWidth(0.3);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetTextColor(255, 255, 255);

$pdf->Cell(100, 8, 'DESCRIPTION', 1, 0, 'L', true);
$pdf->Cell(40, 8, 'CLASS CODE', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'AMOUNT (RM)', 1, 1, 'R', true);

// Item row
$pdf->SetFont('Helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetDrawColor(220, 220, 220);
$pdf->SetLineWidth(0.2);

$class_code = !empty($registration['class_code']) ? $registration['class_code'] : '-';
$description = !empty($registration['class_name']) ? $registration['class_name'] : 'Class Registration';

$pdf->SetX(15);
$pdf->Cell(100, 8, substr($description, 0, 60), 1, 0, 'L');
$pdf->Cell(40, 8, $class_code, 1, 0, 'C');
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell(35, 8, number_format($registration['amount'], 2), 1, 1, 'R');

$pdf->Ln(5);

// ============================================================
// TOTALS SECTION
// ============================================================

// Subtotal
$pdf->SetX(105);
$pdf->SetFont('Helvetica', '', 10);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(60, 6, 'Subtotal:', 0, 0, 'R');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell(0, 6, 'RM ' . number_format($registration['amount'], 2), 0, 1, 'R');

// Tax
$pdf->SetX(105);
$pdf->SetFont('Helvetica', '', 10);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(60, 6, 'Tax (0%):', 0, 0, 'R');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell(0, 6, 'RM 0.00', 0, 1, 'R');

// Line separator
$pdf->SetX(105);
$pdf->SetDrawColor(15, 52, 96);
$pdf->SetLineWidth(0.4);
$pdf->Line(105, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(2);

// Total Amount
$pdf->SetX(105);
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetFillColor(15, 52, 96);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(60, 9, 'TOTAL AMOUNT:', 0, 0, 'R', true);
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->Cell(0, 9, 'RM ' . number_format($registration['amount'], 2), 0, 1, 'R', true);

$pdf->Ln(8);

// ============================================================
// REGISTRATION STATUS BOX
// ============================================================

$current_y = $pdf->GetY();
$pdf->SetFillColor(220, 252, 231);
$pdf->SetDrawColor(34, 197, 94);
$pdf->SetLineWidth(0.4);
$pdf->Rect(15, $current_y, 180, 14, 'FD');

$pdf->SetXY(20, $current_y + 2);
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetTextColor(34, 197, 94);
$pdf->Cell(0, 5, 'REGISTRATION CONFIRMED AND ACTIVE', 0, 1, 'L');

$pdf->SetX(20);
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(22, 163, 74);
$pdf->Cell(0, 4, 'Registered on ' . date('d M Y, g:i A', strtotime($registration['created_at'])), 0, 1, 'L');

$pdf->SetY($current_y + 16);
$pdf->Ln(4);

// ============================================================
// NOTES & TERMS
// ============================================================

$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetTextColor(15, 52, 96);
$pdf->SetX(15);
$pdf->Cell(0, 6, 'IMPORTANT NOTES', 0, 1, 'L');

$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(60, 60, 60);
$pdf->SetX(15);
$pdf->MultiCell(180, 4.5, 
    'Thank you for registering with Wushu Sport Academy. Your registration is confirmed and active. Please keep this document for your records. If you have any questions, please contact our administration office.',
    0, 'L');

$pdf->Ln(2);

$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetTextColor(15, 52, 96);
$pdf->SetX(15);
$pdf->Cell(0, 6, 'TERMS AND CONDITIONS', 0, 1, 'L');

$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(80, 80, 80);
$pdf->SetX(15);
$pdf->MultiCell(180, 4, 
    "This document is a valid proof of registration and enrollment. Please retain this document for your records. Classes are non-transferable unless prior approval is obtained. Wushu Sport Academy reserves the right to modify class schedules with 7 days notice.",
    0, 'L');

// ============================================================
// OUTPUT - Direct Download
// ============================================================

$filename = 'Registration_' . $registration['registration_number'] . '.pdf';

// Set headers for direct download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

// Output PDF to browser for download
$pdf->Output('D', $filename);
exit();
?>