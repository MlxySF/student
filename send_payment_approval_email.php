<?php
/**
 * Payment Approval Email Notification System
 * Sends emails to parents/students when payment status changes
 * 
 * FIXED: PHPMailer Exception conflict with PHP's built-in Exception
 * FIXED: Pass $pdo as parameter instead of using global scope
 * FIXED: Parent email retrieval - join through students.parent_account_id
 * FIXED: Character encoding issues - removed emojis, using HTML entities
 * UPDATED: Removed PDF attachment to improve email deliverability
 * UPDATED: Added anti-spam optimizations
 * ADDED: Extensive debugging to troubleshoot email issues
 * 
 * Usage:
 *   require_once 'send_payment_approval_email.php';
 *   sendPaymentApprovalEmail($pdo, $paymentId, 'verified', $adminNotes);
 */

// Load PHPMailer classes (centralized loader prevents duplicate declarations)
require_once __DIR__ . '/phpmailer_loader.php';

/**
 * Send payment approval/rejection email (NO PDF ATTACHMENT)
 * Receipt can be downloaded from student dashboard
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
        // Get payment details with parent email through students.parent_account_id
        $sql = "
            SELECT 
                p.id as payment_id,
                p.amount,
                p.payment_month,
                p.receipt_filename,
                p.payment_method,
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
        error_log("  - Parent Email: " . ($payment['parent_email'] ?? 'NULL'));
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
            error_log("[Payment Approval Email] ERROR: No email found for payment ID {$paymentId}");
            return false;
        }
        
        error_log("[Payment Approval Email] Email will be sent to: {$recipientEmail}");
        
        // Setup email
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        // SMTP Configuration
        $mail->isSMTP();
        $mail->isSMTP();
        $mail->Host       = 'mail.wushusportacademy.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'admin@wushusportacademy.com';
        $mail->Password   = 'P1}tKwojKgl0vdMv';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8'; // ✨ Ensure UTF-8 encoding
        $mail->Encoding   = 'base64'; // ✨ Use base64 encoding for better compatibility
        
        error_log("[Payment Approval Email] SMTP configured");
        
        // Recipients
        $mail->setFrom('admin@wushusportacademy.com', 'Wushu Sport Academy');
        $mail->addAddress($recipientEmail, $recipientName);
        $mail->addReplyTo('admin@wushusportacademy.com', 'Wushu Sport Academy');
        
        // Email content based on status
        $mail->isHTML(true);
        
        if ($status === 'verified') {
            error_log("[Payment Approval Email] Preparing APPROVED email (NO PDF ATTACHMENT)");
            
            $mail->Subject = 'Payment Approved - ' . $payment['student_name'] . ' - ' . $payment['invoice_number'];
            $mail->Body = getApprovedPaymentEmailHTML($payment, $adminNotes);
            
            // ✨ NO PDF ATTACHMENT - User can download from dashboard
            error_log("[Payment Approval Email] Skipping PDF attachment to improve deliverability");
            
        } else if ($status === 'rejected') {
            error_log("[Payment Approval Email] Preparing REJECTED email");
            
            $mail->Subject = 'Payment Verification Required - ' . $payment['student_name'];
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
 * Get HTML template for approved payment email
 * ✨ FIXED: Removed emojis, using HTML symbols and entities instead
 */
function getApprovedPaymentEmailHTML($payment, $adminNotes) {
    $studentName = htmlspecialchars($payment['student_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $invoiceNumber = htmlspecialchars($payment['invoice_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
    $amount = number_format($payment['amount'] ?? 0, 2);
    $className = htmlspecialchars($payment['class_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
    $classCode = htmlspecialchars($payment['class_code'] ?? '', ENT_QUOTES, 'UTF-8');
    $paymentMonth = htmlspecialchars($payment['payment_month'] ?? '', ENT_QUOTES, 'UTF-8');
    $notes = !empty($adminNotes) ? htmlspecialchars($adminNotes, ENT_QUOTES, 'UTF-8') : 'No additional notes';
    $paymentMethod = ucfirst(str_replace('_', ' ', $payment['payment_method'] ?? 'Bank Transfer'));
    
    // Portal URL for downloading receipt
    $portalUrl = 'https://wushusportacademy.app.tc/student/';
    
    return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .dashboard-box { background: #eff6ff; border: 2px solid #3b82f6; padding: 20px; border-radius: 8px; margin: 24px 0; text-align: center; }
        .dashboard-btn { display: inline-block; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 12px; }
        .dashboard-btn:hover { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
        .footer { text-align: center; padding: 24px; background: #f8fafc; color: #64748b; font-size: 13px; border-top: 1px solid #e2e8f0; }
        ul { margin: 10px 0; padding-left: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="checkmark">&#10004;</div>
            <h1>Payment Approved!</h1>
            <p>Your payment has been verified and processed</p>
        </div>
        
        <div class="content">
            <p>Dear Parent/Guardian,</p>
            
            <p>Great news! Your payment for <strong>' . $studentName . '</strong> has been approved and processed successfully.</p>
            
            <div class="info-box">
                <h3 style="margin: 0 0 16px 0; color: #1e293b; font-size: 18px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">Payment Details</h3>
                <div class="info-row">
                    <div class="info-label">Receipt Number:</div>
                    <div class="info-value"><strong>' . $invoiceNumber . '</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Student Name:</div>
                    <div class="info-value">' . $studentName . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Class:</div>
                    <div class="info-value">' . $className . ' (' . $classCode . ')</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Payment Month:</div>
                    <div class="info-value">' . $paymentMonth . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Amount Paid:</div>
                    <div class="info-value"><span class="amount">RM ' . $amount . '</span></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Status:</div>
                    <div class="info-value"><strong style="color: #059669;">APPROVED &#10003;</strong></div>
                </div>
            </div>
            
            <div class="dashboard-box">
                <strong style="font-size: 18px; color: #1e293b;">&#128196; Download Your Receipt</strong><br><br>
                <p style="margin: 10px 0; color: #64748b;">Your official payment receipt is ready to download from your student dashboard.</p>
                <a href="' . $portalUrl . '" class="dashboard-btn" style="color: white;">&#128279; Go to Dashboard</a><br>
                <small style="color: #64748b; margin-top: 10px; display: inline-block;">Login to view and download your payment receipt</small>
            </div>
            
            <div class="success-box">
                <strong>&#10004; Payment Successfully Processed</strong><br>
                Your payment has been recorded in our system. The invoice has been marked as PAID.
            </div>
            
            <p><strong>What\'s Next?</strong></p>
            <ul>
                <li>Your child can continue attending classes</li>
                <li>Login to the dashboard to download your official receipt</li>
                <li>View your updated payment history in the portal</li>
            </ul>
            
            <p><strong>Admin Notes:</strong><br>' . $notes . '</p>
            
            <p>If you have any questions about this payment, please don\'t hesitate to contact us.</p>
            
            <p>Thank you for your prompt payment!<br>
            <strong>Wushu Sport Academy Team</strong></p>
        </div>
        
        <div class="footer">
            <p style="margin: 0 0 8px 0; font-weight: 600; color: #1e293b; font-size: 15px;">Wushu Sport Academy &#27494;&#26415;&#20307;&#32946;&#23398;&#38498;</p>
            <p style="margin: 4px 0;">Student &amp; Parent Portal System</p>
            <p style="margin: 16px 0 0 0; font-size: 11px; color: #94a3b8;">This is an automated notification.</p>
        </div>
    </div>
</body>
</html>
    ';
}

/**
 * Get HTML template for rejected payment email
 * ✨ FIXED: Removed emojis, using HTML symbols and entities instead
 */
function getRejectedPaymentEmailHTML($payment, $adminNotes) {
    $studentName = htmlspecialchars($payment['student_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $invoiceNumber = htmlspecialchars($payment['invoice_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
    $amount = number_format($payment['amount'] ?? 0, 2);
    $className = htmlspecialchars($payment['class_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
    $paymentMonth = htmlspecialchars($payment['payment_month'] ?? '', ENT_QUOTES, 'UTF-8');
    $notes = !empty($adminNotes) ? htmlspecialchars($adminNotes, ENT_QUOTES, 'UTF-8') : 'Receipt unclear or payment details do not match';
    $paymentMethod = ucfirst(str_replace('_', ' ', $payment['payment_method'] ?? 'Bank Transfer'));
    
    $portalUrl = 'https://wushusportacademy.com/';
    
    return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .dashboard-box { background: #eff6ff; border: 2px solid #3b82f6; padding: 20px; border-radius: 8px; margin: 24px 0; text-align: center; }
        .dashboard-btn { display: inline-block; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 12px; }
        .footer { text-align: center; padding: 24px; background: #f8fafc; color: #64748b; font-size: 13px; border-top: 1px solid #e2e8f0; }
        ul, ol { margin: 10px 0; padding-left: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">&#9888;</div>
            <h1>Payment Verification Required</h1>
            <p>Action needed to complete your payment</p>
        </div>
        
        <div class="content">
            <p>Dear Parent/Guardian,</p>
            
            <p>We have reviewed your payment submission for <strong>' . $studentName . '</strong>, and we need you to resubmit your payment receipt.</p>
            
            <div class="info-box">
                <strong>Payment Details:</strong><br><br>
                <strong>Student:</strong> ' . $studentName . '<br>
                <strong>Class:</strong> ' . $className . '<br>
                <strong>Payment Month:</strong> ' . $paymentMonth . '<br>
                <strong>Amount:</strong> RM ' . $amount . '<br>
                <strong>Status:</strong> <span style="color: #ef4444;"><strong>Verification Required</strong></span>
            </div>
            
            <div class="warning-box">
                <strong>Reason for Rejection:</strong><br>
                ' . $notes . '
            </div>
            
            <p><strong>Common reasons for rejection:</strong></p>
            <ul>
                <li>Payment receipt is unclear or unreadable</li>
                <li>Payment amount does not match the invoice amount</li>
                <li>Payment receipt appears incomplete</li>
                <li>Wrong payment reference or account details</li>
            </ul>
            
            <div class="dashboard-box">
                <strong style="font-size: 18px; color: #1e293b;">&#128228; Resubmit Your Receipt</strong><br><br>
                <p style="margin: 10px 0; color: #64748b;">Please login to your dashboard to upload a corrected payment receipt.</p>
                <a href="' . $portalUrl . '" class="dashboard-btn" style="color: white;">&#128279; Go to Dashboard</a>
            </div>
            
            <p><strong>What you need to do:</strong></p>
            <ol>
                <li>Check that your payment receipt clearly shows all transaction details</li>
                <li>Verify the payment amount matches: <strong>RM ' . $amount . '</strong></li>
                <li>Take a new, clear photo/screenshot if needed</li>
                <li>Login to the portal and upload the corrected receipt</li>
            </ol>
            
            <p><strong>Need assistance?</strong><br>
Please contact us if you need help or have questions about your payment.</p>
            
            <p>We apologize for any inconvenience and look forward to resolving this quickly.</p>
            
            <p>Best regards,<br>
            <strong>Wushu Sport Academy Team</strong></p>
        </div>
        
        <div class="footer">
            <p style="margin: 0 0 8px 0; font-weight: 600; color: #1e293b; font-size: 15px;">Wushu Sport Academy &#27494;&#26415;&#20307;&#32946;&#23398;&#38498;</p>
            <p style="margin: 4px 0;">Student &amp; Parent Portal System</p>
            <p style="margin: 16px 0 0 0; font-size: 11px; color: #94a3b8;">For assistance, please reply to this email</p>
        </div>
    </div>
</body>
</html>
    ';
}

?>