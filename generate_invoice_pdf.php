<?php
/**
 * Professional Invoice PDF Generator - Works for both Student and Admin
 */

error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once 'config.php';

// Check if either student or admin is logged in
$is_admin = isset($_SESSION['admin_id']);
$is_student = isset($_SESSION['student_id']);

if (!$is_admin && !$is_student) {
    header('Location: index.php?page=login');
    exit();
}

if (!isset($_GET['invoice_id']) || !is_numeric($_GET['invoice_id'])) {
    $_SESSION['error'] = 'Invalid invoice ID';
    $redirect = $is_admin ? 'admin.php?page=invoices' : 'index.php?page=invoices';
    header('Location: ' . $redirect);
    exit();
}

$invoice_id = intval($_GET['invoice_id']);

try {
    // Build query based on who's accessing
    if ($is_student) {
        // Students can only view their own invoices
        $student_id = $_SESSION['student_id'];
        $stmt = $pdo->prepare("
            SELECT 
                i.*,
                s.student_id as student_number,
                s.full_name as student_name,
                s.email as student_email,
                s.phone as student_phone,
                c.class_code,
                c.class_name,
                p.verified_date,
                a.full_name as verified_by_name
            FROM invoices i
            INNER JOIN students s ON i.student_id = s.id
            LEFT JOIN classes c ON i.class_id = c.id
            LEFT JOIN payments p ON p.invoice_id = i.id AND p.verification_status = 'verified'
            LEFT JOIN admin_users a ON p.verified_by = a.id
            WHERE i.id = ? AND s.id = ? AND i.status = 'paid'
        ");
        $stmt->execute([$invoice_id, $student_id]);
    } else {
        // Admins can view any invoice
        $stmt = $pdo->prepare("
            SELECT 
                i.*,
                s.student_id as student_number,
                s.full_name as student_name,
                s.email as student_email,
                s.phone as student_phone,
                c.class_code,
                c.class_name,
                p.verified_date,
                a.full_name as verified_by_name
            FROM invoices i
            INNER JOIN students s ON i.student_id = s.id
            LEFT JOIN classes c ON i.class_id = c.id
            LEFT JOIN payments p ON p.invoice_id = i.id AND p.verification_status = 'verified'
            LEFT JOIN admin_users a ON p.verified_by = a.id
            WHERE i.id = ?
        ");
        $stmt->execute([$invoice_id]);
    }
    
    $invoice = $stmt->fetch();
    
} catch(PDOException $e) {
    $_SESSION['error'] = 'Database error';
    $redirect = $is_admin ? 'admin.php?page=invoices' : 'index.php?page=invoices';
    header('Location: ' . $redirect);
    exit();
}

if (!$invoice) {
    $_SESSION['error'] = 'Invoice not found';
    $redirect = $is_admin ? 'admin.php?page=invoices' : 'index.php?page=invoices';
    header('Location: ' . $redirect);
    exit();
}

if (!file_exists('fpdf.php')) {
    $_SESSION['error'] = 'PDF library missing';
    $redirect = $is_admin ? 'admin.php?page=invoices' : 'index.php?page=invoices';
    header('Location: ' . $redirect);
    exit();
}

require('fpdf.php');

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
    private $invoice;
    
    public function setLetterhead($path) {
        $this->letterheadPath = $path;
    }
    
    public function setInvoiceData($data) {
        $this->invoice = $data;
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
        
        // PAID badge - repositioned to top right
        $this->SetXY(165, 10);
        $this->SetFillColor(34, 197, 94);
        $this->SetDrawColor(34, 197, 94);
        $this->SetLineWidth(0.5);
        $this->Rect(165, 10, 30, 12, 'FD');
        $this->SetFont('Helvetica', 'B', 14);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(30, 12, 'PAID', 0, 0, 'C');
        
        // Add invoice number in top right
        $this->SetXY(155, 25);
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(40, 4, $this->invoice['invoice_number'], 0, 0, 'R');
        
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
        
        $this->Cell(0, 4, 'This is a computer-generated invoice. No signature required.', 0, 1, 'C');
        $this->Cell(0, 4, 'Generated: ' . date('d M Y, g:i A'), 0, 1, 'C');
    }
}

// Get letterhead image
$letterheadPath = getLetterheadImage();

// Create PDF
$pdf = new InvoicePDF('P', 'mm', 'A4');
$pdf->setInvoiceData($invoice);
if ($letterheadPath) {
    $pdf->setLetterhead($letterheadPath);
}
$pdf->SetMargins(15, 43, 15);
$pdf->SetAutoPageBreak(true, 25);
$pdf->AddPage();

// ============================================================
// INVOICE DETAILS - TWO COLUMN LAYOUT
// ============================================================

// Left Column - Invoice Details
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetTextColor(15, 52, 96);
$pdf->SetX(15);
$pdf->Cell(90, 6, 'INVOICE DETAILS', 0, 1, 'L');
$pdf->Ln(1);

$pdf->SetFont('Helvetica', '', 10);
$pdf->SetTextColor(80, 80, 80);

$details = [
    ['Invoice Number:', $invoice['invoice_number']],
    ['Date Issued:', date('d M Y', strtotime($invoice['created_at']))],
    ['Due Date:', date('d M Y', strtotime($invoice['due_date']))],
    ['Payment Date:', date('d M Y, g:i A', strtotime($invoice['paid_date']))],
];

foreach ($details as $row) {
    $pdf->SetX(15);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(30, 5.5, $row[0], 0, 0, 'L');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->Cell(0, 5.5, $row[1], 0, 1, 'L');
    $pdf->SetFont('Helvetica', '', 10);
}

// Right Column - Billed To
$pdf->SetXY(115, 43);
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetTextColor(15, 52, 96);
$pdf->Cell(80, 6, 'BILLED TO', 0, 1, 'L');
$pdf->Ln(1);

$pdf->SetX(115);
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5.5, $invoice['student_name'], 0, 1, 'L');

$pdf->SetX(115);
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(22, 5, 'Student ID:', 0, 0, 'L');
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, $invoice['student_number'], 0, 1, 'L');

$pdf->SetX(115);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(22, 5, 'Email:', 0, 0, 'L');
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, $invoice['student_email'], 0, 1, 'L');

if (!empty($invoice['student_phone'])) {
    $pdf->SetX(115);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(22, 5, 'Phone:', 0, 0, 'L');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 5, $invoice['student_phone'], 0, 1, 'L');
}

$pdf->Ln(7);

// ============================================================
// LINE ITEMS TABLE
// ============================================================

$pdf->SetX(15);
$pdf->SetFillColor(15, 52, 96);
$pdf->SetDrawColor(15, 52, 96);
$pdf->SetLineWidth(0.3);
$pdf->SetFont('Helvetica', 'B', 9);
$pdf->SetTextColor(255, 255, 255);

// Adjusted column widths for wrapping
$pdf->Cell(75, 8, 'DESCRIPTION', 1, 0, 'L', true);
$pdf->Cell(35, 8, 'CLASS CODE', 1, 0, 'C', true);
$pdf->Cell(15, 8, 'QTY', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'AMOUNT (RM)', 1, 1, 'R', true);

// Item row with wrapped description
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetDrawColor(220, 220, 220);
$pdf->SetLineWidth(0.2);

$description = $invoice['description'];
$class_code = !empty($invoice['class_code']) ? $invoice['class_code'] : '-';
$amount = number_format($invoice['amount'], 2);

// Store current Y position
$startY = $pdf->GetY();

// Create a temporary cell to calculate height of wrapped text
$pdf->SetXY(15, $startY);
$pdf->SetFont('Helvetica', '', 9);

// Get the height needed for the wrapped description (max 3 lines)
$cellHeight = $pdf->GetStringWidth($description) > 60 ? 15 : 8;

// Draw the cells with proper alignment
$pdf->SetX(15);
$pdf->MultiCell(75, 5, $description, 1, 'L');

// Get the new Y position after multiline cell
$endY = $pdf->GetY();
if ($endY - $startY < 8) {
    $cellHeight = 8;
} else {
    $cellHeight = $endY - $startY;
}

// Redraw with calculated height
$pdf->SetXY(15, $startY);
$pdf->MultiCell(75, 5, $description, 1, 'L');

$pdf->SetXY(90, $startY);
$pdf->SetFont('Helvetica', '', 9);
$pdf->Cell(35, $cellHeight, $class_code, 1, 0, 'C');

$pdf->SetX(125);
$pdf->Cell(15, $cellHeight, '1', 1, 0, 'C');

$pdf->SetX(140);
$pdf->SetFont('Helvetica', 'B', 9);
$pdf->Cell(30, $cellHeight, 'RM ' . $amount, 1, 1, 'R');

$pdf->Ln(3);

// ============================================================
// TOTALS SECTION
// ============================================================

// Subtotal
$pdf->SetX(95);
$pdf->SetFont('Helvetica', '', 10);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(60, 6, 'Subtotal:', 0, 0, 'R');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell(0, 6, 'RM ' . number_format($invoice['amount'], 2), 0, 1, 'R');

// Tax
$pdf->SetX(95);
$pdf->SetFont('Helvetica', '', 10);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(60, 6, 'Tax (0%):', 0, 0, 'R');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell(0, 6, 'RM 0.00', 0, 1, 'R');

// Line separator
$pdf->SetX(95);
$pdf->SetDrawColor(15, 52, 96);
$pdf->SetLineWidth(0.4);
$pdf->Line(95, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(2);

// Total Amount
$pdf->SetX(95);
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetFillColor(15, 52, 96);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(60, 9, 'TOTAL AMOUNT:', 0, 0, 'R', true);
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->Cell(0, 9, 'RM ' . number_format($invoice['amount'], 2), 0, 1, 'R', true);

$pdf->Ln(8);

// ============================================================
// PAYMENT STATUS BOX
// ============================================================

$current_y = $pdf->GetY();
$pdf->SetFillColor(220, 252, 231);
$pdf->SetDrawColor(34, 197, 94);
$pdf->SetLineWidth(0.4);
$pdf->Rect(15, $current_y, 180, 14, 'FD');

$pdf->SetXY(20, $current_y + 2);
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetTextColor(34, 197, 94);
$pdf->Cell(0, 5, 'PAYMENT COMPLETED AND VERIFIED', 0, 1, 'L');

$pdf->SetX(20);
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(22, 163, 74);

if (!empty($invoice['verified_date'])) {
    $verified_text = 'Verified on ' . date('d M Y, g:i A', strtotime($invoice['verified_date']));
    if (!empty($invoice['verified_by_name'])) {
        $verified_text .= ' by ' . $invoice['verified_by_name'];
    }
    $pdf->Cell(0, 4, $verified_text, 0, 1, 'L');
}

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
    'Thank you for your payment. This invoice confirms your class enrollment and payment has been received and verified. Please keep this document for your records. If you have any questions, please contact our administration office.',
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
    "This invoice is a valid proof of payment and enrollment. Please retain this document for your records. Classes are non-transferable unless prior approval is obtained. Wushu Sport Academy reserves the right to modify class schedules with 7 days notice.",
    0, 'L');

// ============================================================
// OUTPUT
// ============================================================

$filename = 'Invoice_' . $invoice['invoice_number'] . '_' . $invoice['student_number'] . '.pdf';
$pdf->Output('D', $filename);
exit();
?>