<?php
/**
 * generate_signed_agreement_pdf.php
 * 
 * Generates a professional signed registration agreement PDF
 * Matches the design and content of the old signed agreement exactly
 * 
 * Usage: Can be called from process_registration.php to generate PDF from signature data
 * Or standalone: generate_signed_agreement_pdf.php?student_name=John%20Tan&reg_number=WSA2025-1234&signature_base64=...
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
        // Header background color - Dark blue/navy
        $this->SetFillColor(25, 41, 60);
        $this->Rect(0, 0, 210, 28, 'F');
        
        // Main Title
        $this->SetFont('Arial', 'B', 20);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(10, 6);
        $this->Cell(0, 8, 'WUSHU SPORT ACADEMY', 0, 1, 'C');
        
        // Subtitle with Chinese
        $this->SetFont('Arial', '', 11);
        $this->SetXY(10, 15);
        $this->Cell(0, 6, 'Registration Agreement - 武术体育学院注册协议', 0, 1, 'C');
        
        // Add spacing
        $this->Ln(2);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 10, 'Registration Number: ' . $this->registrationNumber . ' | Page ' . $this->PageNo(), 0, 0, 'C');
    }
    
    public function generatePDF() {
        $this->AddPage();
        
        // Document reference info at top
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(50, 50, 50);
        $this->SetXY(10, 32);
        $this->Cell(50, 5, 'Registration Number:', 0, 0, 'L');
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(0, 5, $this->registrationNumber, 0, 1, 'L');
        
        $this->SetFont('Arial', '', 9);
        $this->SetXY(10, $this->GetY());
        $this->Cell(50, 5, 'Date of Agreement:', 0, 0, 'L');
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(0, 5, $this->formDate, 0, 1, 'L');
        
        $this->Ln(2);
        
        // Document title - centered and bold
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(25, 41, 60);
        $this->SetXY(10, $this->GetY());
        $this->MultiCell(0, 7, 'STUDENT REGISTRATION AGREEMENT', 0, 'C');
        $this->SetFont('Arial', '', 11);
        $this->SetXY(10, $this->GetY());
        $this->MultiCell(0, 6, '学生注册协议', 0, 'C');
        
        $this->Ln(4);
        
        // STUDENT INFORMATION Section
        $this->drawSectionHeader('STUDENT INFORMATION / 学生信息');
        $this->Ln(1);
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(0, 0, 0);
        $this->SetXY(15, $this->GetY());
        $this->Cell(50, 6, 'Full Name:', 0, 0, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 6, $this->studentName, 0, 1, 'L');
        
        $this->SetFont('Arial', '', 10);
        $this->SetXY(15, $this->GetY());
        $this->Cell(50, 6, 'IC/Passport Number:', 0, 0, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 6, $this->studentIC, 0, 1, 'L');
        
        $this->Ln(2);
        
        // PARENT/GUARDIAN INFORMATION Section
        $this->drawSectionHeader('PARENT/GUARDIAN INFORMATION / 父母/监护人信息');
        $this->Ln(1);
        
        $this->SetFont('Arial', '', 10);
        $this->SetXY(15, $this->GetY());
        $this->Cell(50, 6, 'Full Name:', 0, 0, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 6, $this->parentName, 0, 1, 'L');
        
        $this->SetFont('Arial', '', 10);
        $this->SetXY(15, $this->GetY());
        $this->Cell(50, 6, 'IC/Passport Number:', 0, 0, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 6, $this->parentIC, 0, 1, 'L');
        
        $this->Ln(3);
        
        // TERMS AND CONDITIONS Section
        $this->drawSectionHeader('TERMS AND CONDITIONS / 条款和条件');
        $this->Ln(1);
        
        $this->SetFont('Arial', '', 9);
        $this->SetXY(15, $this->GetY());
        
        $terms = [
            "1. The student will comply with all academy rules, regulations, and code of conduct.",
            "2. The parent/guardian is responsible for ensuring regular and punctual attendance.",
            "3. All applicable fees must be paid according to the schedule provided by the academy.",
            "4. The academy reserves the right to cancel enrollment for non-payment after 30 days.",
            "5. The student agrees to participate safely and follow all instructor guidance.",
            "6. In case of medical emergencies, the academy will contact parent/guardian immediately.",
            "7. Photography or video recording during classes requires prior written permission.",
            "8. The academy is not responsible for lost, stolen, or damaged personal items.",
            "9. Withdrawal from the academy must be notified 30 days in advance in writing.",
            "10. This agreement is valid from the registration date and continues until termination.",
            "11. The academy reserves the right to update terms with 14 days written notice.",
            "12. Any disputes shall be resolved according to Malaysian law."
        ];
        
        foreach ($terms as $term) {
            $this->MultiCell(0, 5, $term, 0, 'L');
            $this->SetX(15);
        }
        
        $this->Ln(2);
        
        // LIABILITY WAIVER AND CONSENT Section
        $this->drawSectionHeader('LIABILITY WAIVER AND CONSENT / 责任豁免和同意');
        $this->Ln(1);
        
        $this->SetFont('Arial', '', 9);
        $this->SetXY(15, $this->GetY());
        
        $waiver = "By signing this agreement, the parent/guardian acknowledges that wushu training involves physical activity and inherent risks of injury. " .
                  "The parent/guardian accepts full responsibility and agrees to release, indemnify, and hold harmless Wushu Sport Academy, " .
                  "its proprietors, instructors, employees, and staff from any and all liability, claims, demands, or actions arising from any injury or harm " .
                  "sustained by the student during training, on academy premises, or in connection with any academy activities.\n\n" .
                  "The parent/guardian further confirms that the student is in good health and is physically capable of participating in wushu training. " .
                  "Any known medical conditions or injuries must be reported to the academy in writing immediately.";
        
        $this->MultiCell(0, 4, $waiver, 0, 'L');
        
        $this->Ln(2);
        
        // Add page break if content is getting too long
        if ($this->GetY() > 240) {
            $this->AddPage();
        }
        
        // AUTHORIZATION AND SIGNATURES Section
        $this->drawSectionHeader('AUTHORIZED SIGNATURES / 授权签名');
        $this->Ln(3);
        
        $sigYPos = $this->GetY();
        
        // Display signature image if provided
        if ($this->signatureBase64) {
            $signatureData = $this->signatureBase64;
            if (strpos($signatureData, 'data:image/png;base64,') === 0) {
                $signatureData = substr($signatureData, strlen('data:image/png;base64,'));
            }
            
            $tempFile = sys_get_temp_dir() . '/sig_' . uniqid() . '.png';
            file_put_contents($tempFile, base64_decode($signatureData));
            
            // Add signature image
            $this->SetXY(20, $sigYPos);
            $this->Image($tempFile, 20, $sigYPos, 50, 18, 'PNG');
            
            // Clean up
            @unlink($tempFile);
        }
        
        // Parent/Guardian signature line
        $this->SetXY(20, $sigYPos + 20);
        $this->SetFont('Arial', '', 9);
        $this->Cell(50, 3, '________________________', 0, 1, 'C');
        $this->SetXY(20, $this->GetY());
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(50, 4, 'Parent/Guardian Signature', 0, 1, 'C');
        
        // Date field for signature
        $this->SetFont('Arial', '', 9);
        $this->SetXY(20, $this->GetY() + 2);
        $this->Cell(50, 3, 'Date: ___________________', 0, 1, 'L');
        
        // Academy section
        $this->SetXY(115, $sigYPos);
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(25, 41, 60);
        $this->Cell(70, 5, 'FOR ACADEMY USE ONLY', 0, 1, 'C');
        
        // Admin signature line
        $this->SetXY(115, $sigYPos + 10);
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(70, 3, '________________________', 0, 1, 'C');
        
        $this->SetXY(115, $this->GetY());
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(70, 4, 'Authorized Academy Officer', 0, 1, 'C');
        
        // Academy date field
        $this->SetFont('Arial', '', 9);
        $this->SetXY(115, $this->GetY() + 2);
        $this->Cell(70, 3, 'Date: ___________________', 0, 1, 'L');
        
        // Footer disclaimer
        $this->Ln(8);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->SetXY(10, $this->GetY());
        $this->MultiCell(0, 4,
            "This is an official registration agreement issued by Wushu Sport Academy. " .
            "Both parties must sign this agreement. The parent/guardian and academy must each retain a certified copy for their records. " .
            "A copy of the signed agreement must be provided to the student. This agreement is binding from the date of signing.",
            0, 'C');
    }
    
    private function drawSectionHeader($title) {
        // Section background color - light blue
        $this->SetFillColor(200, 220, 240);
        $this->Rect(10, $this->GetY(), 190, 7, 'F');
        
        // Section border
        $this->SetDrawColor(25, 41, 60);
        $this->Rect(10, $this->GetY(), 190, 7);
        
        // Section title
        $this->SetXY(12, $this->GetY() + 1);
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(25, 41, 60);
        $this->Cell(0, 5, $title, 0, 1, 'L');
    }
}

/**
 * Integration with process_registration.php
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

// Standalone usage or testing
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
