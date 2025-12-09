<?php
/**
 * Professional Invoice PDF Generator
 * Generates stunning, professional PDF invoices for paid invoices
 * Uses TCPDF library for PDF generation
 */

require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit();
}

// Get invoice ID from URL
if (!isset($_GET['invoice_id']) || !is_numeric($_GET['invoice_id'])) {
    $_SESSION['error'] = 'Invalid invoice ID';
    header('Location: index.php?page=invoices');
    exit();
}

$invoice_id = intval($_GET['invoice_id']);
$student_id = $_SESSION['student_id'];

// Fetch invoice details with all related information
$stmt = $pdo->prepare("
    SELECT 
        i.*,
        s.student_id as student_number,
        s.full_name as student_name,
        s.email as student_email,
        s.phone as student_phone,
        c.class_code,
        c.class_name,
        c.description as class_description,
        p.receipt_filename,
        p.verified_date,
        p.verified_by as payment_verified_by,
        a.full_name as verified_by_name
    FROM invoices i
    INNER JOIN students s ON i.student_id = s.id
    LEFT JOIN classes c ON i.class_id = c.id
    LEFT JOIN payments p ON p.invoice_id = i.id AND p.verification_status = 'verified'
    LEFT JOIN admin_users a ON p.verified_by = a.id
    WHERE i.id = ? AND i.student_id = ? AND i.status = 'paid'
");

$stmt->execute([$invoice_id, $student_id]);
$invoice = $stmt->fetch();

// Check if invoice exists and belongs to this student and is paid
if (!$invoice) {
    $_SESSION['error'] = 'Invoice not found or not yet paid';
    header('Location: index.php?page=invoices');
    exit();
}

// Check if TCPDF is installed, if not provide instructions
if (!file_exists(__DIR__ . '/vendor/autoload.php') && !class_exists('TCPDF')) {
    // Try to load TCPDF manually if it exists in a tcpdf folder
    if (file_exists(__DIR__ . '/tcpdf/tcpdf.php')) {
        require_once(__DIR__ . '/tcpdf/tcpdf.php');
    } else {
        die('<h1>TCPDF Library Required</h1><p>Please install TCPDF library first:</p><pre>composer require tecnickcom/tcpdf</pre><p>Or download from: <a href="https://github.com/tecnickcom/TCPDF">https://github.com/tecnickcom/TCPDF</a></p>');
    }
} else if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Wushu Student Portal');
$pdf->SetAuthor('Wushu Academy');
$pdf->SetTitle('Invoice ' . $invoice['invoice_number']);
$pdf->SetSubject('Payment Invoice');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10);

// Define colors
$primary_color = '#1e40af'; // Blue
$secondary_color = '#64748b'; // Slate gray
$success_color = '#16a34a'; // Green
$border_color = '#e2e8f0';

// Calculate QR code if available
$qr_code_data = 'INVOICE:' . $invoice['invoice_number'] . '|AMOUNT:' . $invoice['amount'] . '|DATE:' . date('Y-m-d', strtotime($invoice['paid_date']));

// ============================================================
// HEADER SECTION WITH SCHOOL LOGO PLACEHOLDER
// ============================================================
$pdf->SetFillColor(30, 64, 175); // Primary blue
$pdf->Rect(0, 0, 210, 35, 'F');

// School Name
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 24);
$pdf->SetXY(15, 10);
$pdf->Cell(0, 10, 'WUSHU ACADEMY', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(15, 22);
$pdf->Cell(0, 5, 'Official Payment Invoice', 0, 1, 'L');

// PAID Stamp on the right
$pdf->SetFont('helvetica', 'B', 28);
$pdf->SetTextColor(22, 163, 74); // Green
$pdf->SetXY(140, 8);
$pdf->Cell(55, 15, 'PAID', 1, 0, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->SetXY(140, 23);
$pdf->Cell(55, 5, date('d M Y', strtotime($invoice['paid_date'])), 0, 0, 'C');

// Reset text color
$pdf->SetTextColor(0, 0, 0);

// ============================================================
// INVOICE INFORMATION SECTION
// ============================================================
$pdf->SetY(45);

// Left column - Invoice details
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 6, 'INVOICE DETAILS', 0, 1, 'L');
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(35, 5, 'Invoice Number:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, $invoice['invoice_number'], 0, 1, 'L');

$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(35, 5, 'Invoice Date:', 0, 0, 'L');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, date('d F Y', strtotime($invoice['created_at'])), 0, 1, 'L');

$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(35, 5, 'Due Date:', 0, 0, 'L');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, date('d F Y', strtotime($invoice['due_date'])), 0, 1, 'L');

$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(35, 5, 'Payment Date:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor(22, 163, 74);
$pdf->Cell(0, 5, date('d F Y, g:i A', strtotime($invoice['paid_date'])), 0, 1, 'L');

if ($invoice['payment_month']) {
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(35, 5, 'Payment Month:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 5, $invoice['payment_month'], 0, 1, 'L');
}

$pdf->Ln(5);

// ============================================================
// STUDENT INFORMATION SECTION
// ============================================================
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 6, 'BILLED TO', 0, 1, 'L');
$pdf->Ln(2);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 5, $invoice['student_name'], 0, 1, 'L');

$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(30, 5, 'Student ID:', 0, 0, 'L');
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, $invoice['student_number'], 0, 1, 'L');

$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(30, 5, 'Email:', 0, 0, 'L');
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, $invoice['student_email'], 0, 1, 'L');

if ($invoice['student_phone']) {
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(30, 5, 'Phone:', 0, 0, 'L');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 5, $invoice['student_phone'], 0, 1, 'L');
}

$pdf->Ln(8);

// ============================================================
// INVOICE ITEMS TABLE
// ============================================================
$pdf->SetFillColor(241, 245, 249); // Light gray background
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', 'B', 9);

// Table header
$pdf->Cell(90, 8, 'DESCRIPTION', 1, 0, 'L', true);
$pdf->Cell(45, 8, 'CLASS', 1, 0, 'C', true);
$pdf->Cell(45, 8, 'AMOUNT', 1, 1, 'R', true);

// Table content
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);

$description = $invoice['description'];
if (strlen($description) > 80) {
    $description = substr($description, 0, 80) . '...';
}

$class_info = '-';
if ($invoice['class_name']) {
    $class_info = $invoice['class_code'] . "\n" . $invoice['class_name'];
}

$pdf->MultiCell(90, 12, $description, 1, 'L', false, 0);
$pdf->MultiCell(45, 12, $class_info, 1, 'C', false, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->MultiCell(45, 12, 'RM ' . number_format($invoice['amount'], 2), 1, 'R', false, 1);

// Spacer
$pdf->Ln(2);

// Total section
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(135, 10, 'TOTAL AMOUNT', 0, 0, 'R');
$pdf->SetFillColor(22, 163, 74);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(45, 10, 'RM ' . number_format($invoice['amount'], 2), 1, 1, 'R', true);

$pdf->SetTextColor(0, 0, 0);

// Payment status
$pdf->Ln(3);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor(22, 163, 74);
$pdf->Cell(0, 6, 'Payment Status: PAID IN FULL', 0, 1, 'R');

if ($invoice['verified_date']) {
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(0, 5, 'Verified on ' . date('d M Y, g:i A', strtotime($invoice['verified_date'])), 0, 1, 'R');
    if ($invoice['verified_by_name']) {
        $pdf->Cell(0, 4, 'Verified by: ' . $invoice['verified_by_name'], 0, 1, 'R');
    }
}

$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(10);

// ============================================================
// ADDITIONAL INFORMATION
// ============================================================
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'ADDITIONAL INFORMATION', 0, 1, 'L');
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(60, 60, 60);

$additional_info = "Invoice Type: " . ucfirst(str_replace('_', ' ', $invoice['invoice_type'])) . "\n";
$additional_info .= "This is an official invoice issued by Wushu Academy. \n";
$additional_info .= "This invoice has been paid and verified. No further action required.\n";
$additional_info .= "For any queries, please contact the administration office.";

$pdf->MultiCell(0, 4, $additional_info, 0, 'L');

$pdf->Ln(8);

// ============================================================
// QR CODE (for verification)
// ============================================================
$style = array(
    'border' => 2,
    'vpadding' => 'auto',
    'hpadding' => 'auto',
    'fgcolor' => array(0,0,0),
    'bgcolor' => array(255,255,255),
    'module_width' => 1,
    'module_height' => 1
);

$pdf->write2DBarcode($qr_code_data, 'QRCODE,H', 15, $pdf->GetY(), 25, 25, $style, 'N');

$pdf->SetXY(45, $pdf->GetY());
$pdf->SetFont('helvetica', '', 7);
$pdf->SetTextColor(120, 120, 120);
$pdf->MultiCell(0, 3, "Scan QR code to verify this invoice\nInvoice ID: " . $invoice['invoice_number'], 0, 'L');

// ============================================================
// FOOTER
// ============================================================
$pdf->SetY(-25);
$pdf->SetFont('helvetica', '', 7);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell(0, 3, '_______________________________________________________________________________', 0, 1, 'C');
$pdf->Ln(1);
$pdf->Cell(0, 3, 'Wushu Academy | Email: admin@wushu.com | Phone: +60 123-456-7890', 0, 1, 'C');
$pdf->Cell(0, 3, 'This is a computer-generated invoice and does not require a signature', 0, 1, 'C');
$pdf->Cell(0, 3, 'Generated on ' . date('d F Y, g:i A'), 0, 1, 'C');

// ============================================================
// OUTPUT PDF
// ============================================================
$filename = 'Invoice_' . $invoice['invoice_number'] . '_' . $invoice['student_number'] . '.pdf';
$pdf->Output($filename, 'D'); // D = Download

exit();
?>