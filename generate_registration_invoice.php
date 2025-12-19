<?php
// generate_registration_invoice.php
require_once 'fpdf.php';
require_once 'config.php';

class RegistrationInvoice extends FPDF {
    function Header() {
        // Logo
        if (file_exists('assets/logo.png')) {
            $this->Image('assets/logo.png', 10, 6, 30);
        }
        
        // Title
        $this->SetFont('Arial', 'B', 20);
        $this->Cell(0, 10, 'WUSHU SPORT ACADEMY', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, 'Registration Invoice', 0, 1, 'C');
        $this->Ln(10);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

// Get registration data from POST
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    die('Invalid data');
}

// Fetch class codes from database if student_id is provided
$classCodes = [];
if (!empty($data['student_id'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT c.class_code
            FROM enrollments e
            JOIN classes c ON e.class_id = c.id
            WHERE e.student_id = ? AND e.status = 'active'
            ORDER BY c.class_code
        ");
        $stmt->execute([$data['student_id']]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $classCodes = array_column($results, 'class_code');
    } catch (Exception $e) {
        // Silently fail, continue with what we have
    }
}

$pdf = new RegistrationInvoice();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 11);

// Invoice Details
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'REGISTRATION INVOICE', 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(50, 6, 'Registration No:', 0, 0);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, $data['registration_number'], 0, 1);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(50, 6, 'Date:', 0, 0);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, date('F j, Y'), 0, 1);
$pdf->Ln(5);

// Student Information
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(0, 8, 'STUDENT INFORMATION', 0, 1, 'L', true);
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(50, 6, 'Name:', 0, 0);
$pdf->Cell(0, 6, $data['name_en'], 0, 1);

if (!empty($data['name_cn'])) {
    $pdf->Cell(50, 6, 'Chinese Name:', 0, 0);
    $pdf->Cell(0, 6, $data['name_cn'], 0, 1);
}

$pdf->Cell(50, 6, 'IC Number:', 0, 0);
$pdf->Cell(0, 6, $data['ic'], 0, 1);

$pdf->Cell(50, 6, 'Age:', 0, 0);
$pdf->Cell(0, 6, $data['age'] . ' years', 0, 1);

$pdf->Cell(50, 6, 'School:', 0, 0);
$pdf->Cell(0, 6, $data['school'], 0, 1);

$pdf->Cell(50, 6, 'Status:', 0, 0);
$pdf->Cell(0, 6, $data['status'], 0, 1);

$pdf->Cell(50, 6, 'Phone:', 0, 0);
$pdf->Cell(0, 6, $data['phone'], 0, 1);

$pdf->Cell(50, 6, 'Email:', 0, 0);
$pdf->Cell(0, 6, $data['email'], 0, 1);
$pdf->Ln(5);

// Class Information
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, 'CLASS INFORMATION', 0, 1, 'L', true);
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(50, 6, 'Events:', 0, 0);
$pdf->MultiCell(0, 6, $data['events']);

$pdf->Cell(50, 6, 'Schedule:', 0, 0);
$pdf->MultiCell(0, 6, $data['schedule']);

$pdf->Cell(50, 6, 'Number of Classes:', 0, 0);
$pdf->Cell(0, 6, $data['class_count'] . ' classes', 0, 1);

// Class Codes
if (!empty($classCodes)) {
    $pdf->Cell(50, 6, 'Class Codes:', 0, 0);
    $classCodesStr = implode(', ', $classCodes);
    $pdf->MultiCell(0, 6, $classCodesStr);
}

$pdf->Ln(3);

// Payment Information
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, 'PAYMENT DETAILS', 0, 1, 'L', true);
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(50, 6, 'Payment Date:', 0, 0);
$pdf->Cell(0, 6, date('F j, Y', strtotime($data['payment_date'])), 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 8, 'Total Amount:', 0, 0);
$pdf->SetTextColor(0, 128, 0);
$pdf->Cell(0, 8, 'RM ' . number_format($data['payment_amount'], 2), 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(10);

// Footer Note
$pdf->SetFont('Arial', 'I', 9);
$pdf->MultiCell(0, 5, 'Thank you for registering with Wushu Sport Academy. Your account details will be sent to your email after payment verification. For inquiries, please contact us.');

// Output PDF
$pdf->Output('I', 'Registration_Invoice_' . $data['registration_number'] . '.pdf');
?>