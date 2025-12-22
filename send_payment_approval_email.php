<?php
/**
 * Payment Approval Email Notification System
 * Sends emails to parents/students when payment status changes
 * Includes PDF invoice receipt attachment for approved payments
 * 
 * FIXED: PHPMailer Exception conflict with PHP's built-in Exception
 * FIXED: FPDF path corrected to main directory
 * FIXED: Pass $pdo as parameter instead of using global scope
 * FIXED: Parent email retrieval - join through students.parent_account_id instead of payments.parent_account_id
 * ADDED: Extensive debugging to troubleshoot email issues
 * 
 * Usage:
 *   require_once 'send_payment_approval_email.php';
 *   sendPaymentApprovalEmail($pdo, $paymentId, 'verified', $adminNotes);
 */

// ✨ FIX: Use aliases to prevent class name conflicts
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

/**
 * Send payment approval/rejection email with optional PDF receipt
 * 
 * @param PDO $pdo Database connection
 * @param int $paymentId Payment ID from payments table
 * @param string $status 'verified' or 'rejected'
 * @param string $adminNotes Optional notes from admin
 * @return bool True if email sent successfully
 */
function sendPaymentApprovalEmail($pdo, $paymentId, $status, $adminNotes = '') {
    error_log("========================================");
    error_log("[Payment Approval Email] STARTING");
    error_log("[Payment Approval Email] Payment ID: {$paymentId}");
    error_log("[Payment Approval Email] Status: {$status}");
    error_log("[Payment Approval Email] Admin Notes: {$adminNotes}");
    error_log("========================================");
    
    try {
        // ✨ FIXED: Get payment details with parent email through students.parent_account_id
        $sql = "
            SELECT 
                p.id as payment_id,
                p.amount,
                p.payment_month,
                p.receipt_filename,
                p.verification_status,
                p.invoice_id,
                s.id as student_account_id,
                s.full_name as student_name,
                s.student_id as student_number,
                s.email as student_email,
                s.parent_account_id,
                r.name_en as registration_name,
                c.class_name,
                c.class_code,
                i.invoice_number,
                i.description as invoice_description,
                pa.email as parent_email,
                pa.full_name as parent_name,
                pa.parent_id as parent_number
            FROM payments p
            JOIN students s ON p.student_id = s.id
            LEFT JOIN parent_accounts pa ON s.parent_account_id = pa.id
            LEFT JOIN registrations r ON s.id = r.student_account_id
            LEFT JOIN classes c ON p.class_id = c.id
            LEFT JOIN invoices i ON p.invoice_id = i.id
            WHERE p.id = ?
        ";
        
        error_log("[Payment Approval Email] SQL Query: " . str_replace("\n", " ", $sql));
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            error_log("[Payment Approval Email] ERROR: Payment ID {$paymentId} not found in database");
            return false;
        }
        
        error_log("[Payment Approval Email] Payment record found:");
        error_log("  - Student Name: " . ($payment['student_name'] ?? 'NULL'));
        error_log("  - Student Email: " . ($payment['student_email'] ?? 'NULL'));
        error_log("  - Parent Account ID: " . ($payment['parent_account_id'] ?? 'NULL'));
        error_log("  - Parent Email: " . ($payment['parent_email'] ?? 'NULL'));
        error_log("  - Parent Name: " . ($payment['parent_name'] ?? 'NULL'));
        error_log("  - Amount: " . ($payment['amount'] ?? 'NULL'));
        
        // Determine recipient email - prioritize parent email, fallback to student email
        $recipientEmail = $payment['parent_email'];
        $recipientName = $payment['parent_name'];
        
        if (empty($recipientEmail)) {
            error_log("[Payment Approval Email] WARNING: No parent email found, trying student email");
            $recipientEmail = $payment['student_email'];
            $recipientName = $payment['student_name'];
        }
        
        if (empty($recipientEmail)) {
            error_log("[Payment Approval Email] ERROR: No email found (neither parent nor student) for payment ID {$paymentId}");
            return false;
        }
        
        error_log("[Payment Approval Email] Email will be sent to: {$recipientEmail} ({$recipientName})");
        
        // Setup email
        $mail = new PHPMailer(true);
        
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'chaichonghern@gmail.com';
        $mail->Password   = 'kyyj elhp dkdw gvki';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        
        error_log("[Payment Approval Email] SMTP configured");
        
        // Recipients
        $mail->setFrom('noreply@wushusportacademy.com', 'Wushu Sport Academy');
        $mail->addAddress($recipientEmail, $recipientName);
        $mail->addReplyTo('chaichonghern@gmail.com', 'Wushu Sport Academy');
        
        // Email content based on status
        $mail->isHTML(true);
        
        if ($status === 'verified') {
            error_log("[Payment Approval Email] Preparing APPROVED email");
            
            $mail->Subject = '✅ Payment Approved - ' . $payment['student_name'] . ' - ' . $payment['invoice_number'];
            $mail->Body = getApprovedPaymentEmailHTML($payment, $adminNotes);
            
            // Generate and attach PDF receipt
            if ($payment['invoice_id']) {
                error_log("[Payment Approval Email] Attempting to generate PDF receipt");
                try {
                    $pdfContent = generateInvoiceReceiptPDF($payment);
                    if ($pdfContent) {
                        $filename = 'Payment_Receipt_' . $payment['invoice_number'] . '.pdf';
                        $mail->addStringAttachment($pdfContent, $filename, 'base64', 'application/pdf');
                        error_log("[Payment Approval Email] PDF receipt attached: {$filename}");
                    } else {
                        error_log("[Payment Approval Email] WARNING: PDF generation returned null");
                    }
                } catch (Exception $e) {
                    error_log("[Payment Approval Email] ERROR generating PDF: " . $e->getMessage());
                    error_log("[Payment Approval Email] Continuing without PDF attachment");
                }
            }
            
        } else if ($status === 'rejected') {
            error_log("[Payment Approval Email] Preparing REJECTED email");
            
            $mail->Subject = '⚠️ Payment Verification Required - ' . $payment['student_name'];
            $mail->Body = getRejectedPaymentEmailHTML($payment, $adminNotes);
            
        } else {
            error_log("[Payment Approval Email] ERROR: Invalid status '{$status}'");
            return false;
        }
        
        // Plain text alternative
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $mail->Body));
        
        // Send email
        error_log("[Payment Approval Email] Attempting to send email via SMTP...");
        $mail->send();
        
        error_log("[Payment Approval Email] ✅ SUCCESS! Email sent to {$recipientEmail}");
        error_log("========================================");
        
        return true;
        
    } catch (Exception $e) {
        error_log("[Payment Approval Email] ❌ EXCEPTION CAUGHT:");
        error_log("[Payment Approval Email] Message: " . $e->getMessage());
        
        if (isset($mail)) {
            error_log("[Payment Approval Email] PHPMailer Error: " . $mail->ErrorInfo);
        }
        
        error_log("========================================");
        return false;
    }
}

/**
 * Generate PDF receipt for approved payment
 * ✨ FIXED: Correct path to fpdf.php in main directory
 */
function generateInvoiceReceiptPDF($payment) {
    error_log("[PDF Generation] Starting PDF generation");
    
    // ✨ FIX: FPDF is in the main directory, not a subdirectory
    if (!file_exists(__DIR__ . '/fpdf.php')) {
        error_log("[PDF Generation] ERROR: fpdf.php not found in main directory");
        error_log("[PDF Generation] Checked path: " . __DIR__ . '/fpdf.php');
        return null;
    }
    
    require_once __DIR__ . '/fpdf.php';
    
    try {
        error_log("[PDF Generation] Creating FPDF instance");
        
        // Create PDF
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 20);
        
        // Header
        $pdf->Cell(0, 10, 'PAYMENT RECEIPT', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Invoice details
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(50, 8, 'Receipt Number:', 0);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, $payment['invoice_number'], 0, 1);
        
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(50, 8, 'Payment Date:', 0);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, date('F j, Y'), 0, 1);
        
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(50, 8, 'Student:', 0);
        $pdf->Cell(0, 8, $payment['student_name'] . ' (' . $payment['student_number'] . ')', 0, 1);
        
        $pdf->Cell(50, 8, 'Class:', 0);
        $pdf->Cell(0, 8, $payment['class_name'] . ' (' . $payment['class_code'] . ')', 0, 1);
        
        $pdf->Ln(10);
        
        // Payment details
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'Payment Details', 0, 1);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(5);
        
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(50, 8, 'Description:', 0);
        $pdf->MultiCell(0, 8, $payment['invoice_description'], 0, 'L');
        
        $pdf->Cell(50, 8, 'Payment Month:', 0);
        $pdf->Cell(0, 8, $payment['payment_month'], 0, 1);
        
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(50, 10, 'Amount Paid:', 0);
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(0, 150, 100);
        $pdf->Cell(0, 10, 'RM ' . number_format($payment['amount'], 2), 0, 1);
        
        $pdfString = $pdf->Output('S'); // Return PDF as string
        error_log("[PDF Generation] PDF generated successfully (size: " . strlen($pdfString) . " bytes)");
        
        return $pdfString;
        
    } catch (Exception $e) {
        error_log("[PDF Generation] ERROR: " . $e->getMessage());
        return null;
    }
}

/**
 * Get HTML template for approved payment email
 */
function getApprovedPaymentEmailHTML($payment, $adminNotes) {
    $studentName = htmlspecialchars($payment['student_name']);
    $invoiceNumber = htmlspecialchars($payment['invoice_number']);
    $amount = number_format($payment['amount'], 2);
    $className = htmlspecialchars($payment['class_name']);
    $classCode = htmlspecialchars($payment['class_code']);
    $paymentMonth = htmlspecialchars($payment['payment_month']);
    $notes = !empty($adminNotes) ? htmlspecialchars($adminNotes) : 'No additional notes';
    
    return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
        .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; padding: 30px 24px; text-align: center; }
        .checkmark { font-size: 48px; margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 28px; font-weight: 700; }
        .header p { margin: 8px 0 0 0; font-size: 14px; opacity: 0.95; }
        .content { padding: 32px 24px; }
        .info-box { background: #f8fafc; border-radius: 8px; padding: 20px; margin-bottom: 24px; }
        .info-row { display: flex; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #64748b; font-weight: 600; width: 40%; }
        .info-value { color: #1e293b; width: 60%; }
        .amount { color: #059669; font-weight: 700; font-size: 24px; }
        .success-box { background: #f0fdf4; border-left: 4px solid #059669; padding: 16px; border-radius: 8px; margin: 24px 0; }
        .success-box strong { color: #166534; }
        .attachment-box { background: #eff6ff; border-left: 4px solid #3b82f6; padding: 16px; border-radius: 8px; margin: 24px 0; }
        .footer { text-align: center; padding: 24px; background: #f8fafc; color: #64748b; font-size: 13px; border-top: 1px solid #e2e8f0; }
        ul { margin: 10px 0; padding-left: 20px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <div class='checkmark'>✅</div>
            <h1>Payment Approved!</h1>
            <p>Your payment has been verified and processed</p>
        </div>
        
        <div class='content'>
            <p>Dear Parent/Guardian,</p>
            
            <p>Great news! Your payment for <strong>{$studentName}</strong> has been approved and processed successfully.</p>
            
            <div class='info-box'>
                <h3 style='margin: 0 0 16px 0; color: #1e293b; font-size: 18px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;'>💳 Payment Details</h3>
                <div class='info-row'>
                    <div class='info-label'>Receipt Number:</div>
                    <div class='info-value'><strong>{$invoiceNumber}</strong></div>
                </div>
                <div class='info-row'>
                    <div class='info-label'>Student Name:</div>
                    <div class='info-value'>{$studentName}</div>
                </div>
                <div class='info-row'>
                    <div class='info-label'>Class:</div>
                    <div class='info-value'>{$className} ({$classCode})</div>
                </div>
                <div class='info-row'>
                    <div class='info-label'>Payment Month:</div>
                    <div class='info-value'>{$paymentMonth}</div>
                </div>
                <div class='info-row'>
                    <div class='info-label'>Amount Paid:</div>
                    <div class='info-value'><span class='amount'>RM {$amount}</span></div>
                </div>
                <div class='info-row'>
                    <div class='info-label'>Status:</div>
                    <div class='info-value'><strong style='color: #059669;'>APPROVED ✓</strong></div>
                </div>
            </div>
            
            <div class='attachment-box'>
                <strong>📎 Attached Document:</strong><br><br>
                <strong>Payment Receipt PDF</strong> - Official receipt for your records<br>
                <small style='color: #64748b;'>Please save this receipt for future reference</small>
            </div>
            
            <div class='success-box'>
                <strong>✅ Payment Successfully Processed</strong><br>
                Your payment has been recorded in our system. The invoice has been marked as PAID.
            </div>
            
            <p><strong>What's Next?</strong></p>
            <ul>
                <li>Your child can continue attending classes</li>
                <li>Keep the attached receipt for your records</li>
                <li>Login to the student portal to view updated payment status</li>
            </ul>
            
            <p><strong>Admin Notes:</strong><br>{$notes}</p>
            
            <p>If you have any questions about this payment, please don't hesitate to contact us.</p>
            
            <p>Thank you for your prompt payment!<br>
            <strong>Wushu Sport Academy Team</strong></p>
        </div>
        
        <div class='footer'>
            <p style='margin: 0 0 8px 0; font-weight: 600; color: #1e293b; font-size: 15px;'>Wushu Sport Academy 武术体育学院</p>
            <p style='margin: 4px 0;'>Student & Parent Portal System</p>
            <p style='margin: 16px 0 0 0; font-size: 11px; color: #94a3b8;'>This is an automated notification. Generated on " . date('Y-m-d H:i:s') . "</p>
        </div>
    </div>
</body>
</html>
    ";
}

/**
 * Get HTML template for rejected payment email
 */
function getRejectedPaymentEmailHTML($payment, $adminNotes) {
    $studentName = htmlspecialchars($payment['student_name']);
    $invoiceNumber = htmlspecialchars($payment['invoice_number'] ?? 'N/A');
    $amount = number_format($payment['amount'], 2);
    $className = htmlspecialchars($payment['class_name']);
    $paymentMonth = htmlspecialchars($payment['payment_month']);
    $notes = !empty($adminNotes) ? htmlspecialchars($adminNotes) : 'Receipt unclear or payment details do not match';
    
    return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
        .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 30px 24px; text-align: center; }
        .icon { font-size: 48px; margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 28px; font-weight: 700; }
        .header p { margin: 8px 0 0 0; font-size: 14px; opacity: 0.95; }
        .content { padding: 32px 24px; }
        .info-box { background: #fef2f2; border-left: 4px solid #ef4444; padding: 20px; border-radius: 8px; margin: 24px 0; }
        .warning-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 16px; border-radius: 8px; margin: 24px 0; }
        .footer { text-align: center; padding: 24px; background: #f8fafc; color: #64748b; font-size: 13px; border-top: 1px solid #e2e8f0; }
        ul, ol { margin: 10px 0; padding-left: 20px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <div class='icon'>⚠️</div>
            <h1>Payment Verification Required</h1>
            <p>Action needed to complete your payment</p>
        </div>
        
        <div class='content'>
            <p>Dear Parent/Guardian,</p>
            
            <p>We have reviewed your payment submission for <strong>{$studentName}</strong>, and we need you to resubmit your payment receipt.</p>
            
            <div class='info-box'>
                <strong>Payment Details:</strong><br><br>
                <strong>Student:</strong> {$studentName}<br>
                <strong>Class:</strong> {$className}<br>
                <strong>Payment Month:</strong> {$paymentMonth}<br>
                <strong>Amount:</strong> RM {$amount}<br>
                <strong>Status:</strong> <span style='color: #ef4444;'><strong>Verification Required</strong></span>
            </div>
            
            <div class='warning-box'>
                <strong>Reason for Rejection:</strong><br>
                {$notes}
            </div>
            
            <p><strong>Common reasons for rejection:</strong></p>
            <ul>
                <li>Payment receipt is unclear or unreadable</li>
                <li>Payment amount does not match the invoice amount</li>
                <li>Payment receipt appears incomplete</li>
                <li>Wrong payment reference or account details</li>
            </ul>
            
            <p><strong>What you need to do:</strong></p>
            <ol>
                <li>Check that your payment receipt clearly shows all transaction details</li>
                <li>Verify the payment amount matches: <strong>RM {$amount}</strong></li>
                <li>Take a new, clear photo/screenshot if the original was unclear</li>
                <li>Login to the student portal and upload the corrected receipt</li>
            </ol>
            
            <p><strong>Need assistance?</strong><br>
Please contact us if you need help or have questions about your payment. We're here to help!</p>
            
            <p>We apologize for any inconvenience and look forward to resolving this quickly.</p>
            
            <p>Best regards,<br>
            <strong>Wushu Sport Academy Team</strong></p>
        </div>
        
        <div class='footer'>
            <p style='margin: 0 0 8px 0; font-weight: 600; color: #1e293b; font-size: 15px;'>Wushu Sport Academy 武术体育学院</p>
            <p style='margin: 4px 0;'>Student & Parent Portal System</p>
            <p style='margin: 16px 0 0 0; font-size: 11px; color: #94a3b8;'>For assistance, please reply to this email</p>
        </div>
    </div>
</body>
</html>
    ";
}

?>