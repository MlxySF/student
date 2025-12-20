<?php
/**
 * generate_signed_agreement_pdf.php
 * 
 * Generates a professional signed registration agreement PDF
 * Matches the design and content of the old signed agreement
 * 
 * Usage: Can be called from process_registration.php to generate PDF from signature data
 * Or standalone: generate_signed_agreement_pdf.php?student_name=John%20Doe&reg_number=WSA2025-1234&signature_base64=...
 */

require_once 'fpdf.php';
require_once 'config.php';

class SignedAgreementPDF extends FPDF {
    private $studentName;
    private $registrationNumber;
    private $parentName;
    private $studentIC;
    private $parentIC;
    private $formDate;
    private $signatureBase64;
    
    public function __construct($params) {
        parent::__construct('P', 'mm', 'A4');
        $this->studentName = $params['student_name'] ?? 'Student Name';
        $this->registrationNumber = $params['registration_number'] ?? 'REG-2025-0000';
        $this->parentName = $params['parent_name'] ?? 'Parent Name';
        $this->studentIC = $params['student_ic'] ?? '000000-00-0000';
        $this->parentIC = $params['parent_ic'] ?? '000000-00-0000';
        $this->formDate = $params['form_date'] ?? date('d/m/Y');
        $this->signatureBase64 = $params['signature_base64'] ?? null;
    }
    
    public function Header() {
        // Header background color
        $this->SetFillColor(25, 41, 60); // Dark blue
        $this->Rect(0, 0, 210, 25, 'F');
        
        // Title
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(10, 8);
        $this->Cell(0, 10, 'WUSHU SPORT ACADEMY', 0, 1, 'C');
        
        // Subtitle
        $this->SetFont('Arial', '', 10);
        $this->SetXY(10, 16);
        $this->Cell(0, 6, 'Registration Agreement - 武术体育学院注册协议', 0, 1, 'C');
        
        // Add spacing
        $this->Ln(4);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 10, 'Registration Number: ' . $this->registrationNumber . ' | Page ' . $this->PageNo(), 0, 0, 'C');
    }
    
    public function generatePDF() {
        $this->AddPage();
        
        // Document info
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(50, 50, 50);
        $this->SetXY(10, 35);
        $this->MultiCell(0, 5, 
            "Registration Number: " . $this->registrationNumber . "\n" .
            "Date: " . $this->formDate,
            0, 'L');
        
        $this->Ln(3);
        
        // Main title
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(25, 41, 60);
        $this->SetXY(10, $this->GetY());
        $this->MultiCell(0, 8, 'STUDENT REGISTRATION AGREEMENT\n武术体育学院学生注册协议', 0, 'C');
        
        $this->Ln(5);
        
        // Student Information Section
        $this->drawSection('STUDENT INFORMATION', $this->GetY());
        $yPos = $this->GetY();
        
        $this->SetFont('Arial', '', 10);
        $this->SetXY(10, $yPos);
        $this->Cell(50, 7, 'Student Name:', 0, 0, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 7, $this->studentName, 0, 1, 'L');
        
        $this->SetFont('Arial', '', 10);
        $this->Cell(50, 7, 'Student IC Number:', 0, 0, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 7, $this->studentIC, 0, 1, 'L');
        
        $this->Ln(3);
        
        // Parent Information Section
        $this->drawSection('PARENT/GUARDIAN INFORMATION', $this->GetY());
        $yPos = $this->GetY();
        
        $this->SetFont('Arial', '', 10);
        $this->SetXY(10, $yPos);
        $this->Cell(50, 7, 'Parent/Guardian Name:', 0, 0, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 7, $this->parentName, 0, 1, 'L');
        
        $this->SetFont('Arial', '', 10);
        $this->Cell(50, 7, 'Parent IC Number:', 0, 0, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 7, $this->parentIC, 0, 1, 'L');
        
        $this->Ln(3);
        
        // Terms and Conditions
        $this->drawSection('TERMS AND CONDITIONS', $this->GetY());
        $yPos = $this->GetY();
        
        $this->SetFont('Arial', '', 9);
        $this->SetXY(12, $yPos);
        $terms = [
            "1. The student will comply with all academy rules and regulations.",
            "2. The parent/guardian is responsible for ensuring regular attendance.",
            "3. Payment of fees is due on the specified dates as per invoice.",
            "4. The academy reserves the right to cancel enrollment for non-payment.",
            "5. The student will participate safely and follow instructor guidance.",
            "6. Medical emergencies will be handled according to academy protocols.",
            "7. Photography/video recording during classes requires prior permission.",
            "8. The academy is not responsible for lost or damaged personal items.",
            "9. Withdrawal must be notified 30 days in advance.",
            "10. This agreement is valid from the registration date onwards."
        ];
        
        foreach ($terms as $term) {
            $this->MultiCell(0, 5, $term, 0, 'L');
            $this->SetX(12);
        }
        
        $this->Ln(3);
        
        // Liability Waiver
        $this->drawSection('LIABILITY WAIVER', $this->GetY());
        $yPos = $this->GetY();
        
        $this->SetFont('Arial', '', 9);
        $this->SetXY(12, $yPos);
        $waiver = "The parent/guardian acknowledges that wushu training involves physical activity and potential for injury. " .
                  "By signing this agreement, the parent/guardian accepts full responsibility and agrees to release Wushu Sport Academy, " .
                  "its instructors, and staff from any liability for injuries sustained during training or on academy premises.";
        $this->MultiCell(0, 5, $waiver, 0, 'L');
        
        $this->Ln(5);
        
        // Signature Section
        $this->drawSection('AUTHORIZED SIGNATURES', $this->GetY());
        $sigYPos = $this->GetY();
        
        // Display signature if provided
        if ($this->signatureBase64) {
            // Save signature image temporarily
            $signatureData = $this->signatureBase64;
            if (strpos($signatureData, 'data:image/png;base64,') === 0) {
                $signatureData = substr($signatureData, strlen('data:image/png;base64,'));
            }
            
            $tempFile = sys_get_temp_dir() . '/sig_' . uniqid() . '.png';
            file_put_contents($tempFile, base64_decode($signatureData));
            
            // Add signature image
            $this->SetXY(20, $sigYPos);
            $this->Image($tempFile, 20, $sigYPos, 60, 20, 'PNG');
            
            // Clean up
            unlink($tempFile);
        }
        
        $this->SetXY(20, $sigYPos + 22);
        $this->SetFont('Arial', '', 9);
        $this->Cell(60, 4, '________________________', 0, 1, 'C');
        $this->SetXY(20, $this->GetY());
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(60, 4, 'Parent/Guardian Signature', 0, 1, 'C');
        
        // Date
        $this->Ln(2);
        $this->SetFont('Arial', '', 9);
        $this->SetXY(20, $this->GetY());
        $this->Cell(60, 4, 'Date: ___________________', 0, 1, 'L');
        
        // Academy section
        $this->Ln(8);
        $this->SetXY(120, $sigYPos);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(70, 5, 'FOR ACADEMY USE ONLY', 0, 1, 'C');
        
        $this->SetXY(120, $this->GetY());
        $this->SetFont('Arial', '', 9);
        $this->Cell(70, 4, '________________________', 0, 1, 'C');
        $this->SetXY(120, $this->GetY());
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(70, 4, 'Admin Signature', 0, 1, 'C');
        
        $this->Ln(2);
        $this->SetFont('Arial', '', 9);
        $this->SetXY(120, $this->GetY());
        $this->Cell(70, 4, 'Date: ___________________', 0, 1, 'L');
        
        // Footer note
        $this->Ln(10);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->SetXY(10, $this->GetY());
        $this->MultiCell(0, 4,
            "This is an official registration agreement issued by Wushu Sport Academy. " .
            "Both parties must sign and retain a copy for their records. " .
            "This agreement is valid from the date of signing.",
            0, 'C');
    }
    
    private function drawSection($title, $yPos) {
        // Section background
        $this->SetFillColor(230, 240, 250); // Light blue
        $this->Rect(10, $yPos, 190, 6, 'F');
        
        // Section title
        $this->SetXY(12, $yPos + 1);
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(25, 41, 60);
        $this->Cell(0, 4, $title, 0, 1, 'L');
        
        $this->Ln(2);
    }
}

/**
 * Standalone usage or for integration into process_registration.php
 * 
 * To use in process_registration.php, call:
 * $pdfGenerator = new SignedAgreementPDF([
 *     'student_name' => $fullName,
 *     'registration_number' => $regNumber,
 *     'parent_name' => $parentName,
 *     'student_ic' => $childIC,
 *     'parent_ic' => $parentIC,
 *     'form_date' => date('d/m/Y', strtotime($validatedFormDateTime)),
 *     'signature_base64' => $data['signature_base64']
 * ]);
 * $pdfGenerator->generatePDF();
 * $pdfOutput = $pdfGenerator->Output('S'); // Return as string
 * $pdfBase64 = base64_encode($pdfOutput); // Store in database
 */

// If called directly (standalone)
if (php_sapi_name() === 'cli' || isset($_GET['test'])) {
    $testParams = [
        'student_name' => $_GET['student_name'] ?? 'John Tan',
        'registration_number' => $_GET['reg_number'] ?? 'WSA2025-1234',
        'parent_name' => $_GET['parent_name'] ?? 'Parent Name',
        'student_ic' => $_GET['student_ic'] ?? '020101-01-0001',
        'parent_ic' => $_GET['parent_ic'] ?? '900101-01-0001',
        'form_date' => $_GET['form_date'] ?? date('d/m/Y'),
        'signature_base64' => $_GET['signature_base64'] ?? null
    ];
    
    $pdf = new SignedAgreementPDF($testParams);
    $pdf->generatePDF();
    $pdf->Output('D', 'Registration_Agreement_' . str_replace(' ', '_', $testParams['student_name']) . '.pdf');
}
?>
