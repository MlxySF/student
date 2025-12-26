<?php
/**
 * send_invoice_notification.php
 * Sends email notification when invoices are generated
 * Call this after creating new invoices (monthly fees, etc.)
 * 
 * FIXED: Use centralized PHPMailer loader to prevent class conflicts
 */

// Load PHPMailer classes (centralized loader prevents duplicate declarations)
require_once __DIR__ . '/phpmailer_loader.php';

/**
 * Send invoice notification email to parent or student
 * 
 * @param PDO $pdo Database connection
 * @param int $invoiceId Invoice ID from invoices table
 * @return bool True if email sent successfully
 */
function sendInvoiceNotification($pdo, $invoiceId) {
    error_log("========================================");
    error_log("[Invoice Notification] STARTING");
    error_log("[Invoice Notification] Invoice ID: {$invoiceId}");
    error_log("========================================");
    
    try {
        // Get invoice details with parent/student email
        $sql = "
            SELECT 
                i.id as invoice_id,
                i.invoice_number,
                i.amount,
                i.due_date,
                i.description,
                i.invoice_type,
                s.id as student_account_id,
                s.full_name as student_name,
                s.student_id as student_number,
                s.email as student_email,
                s.parent_account_id,
                c.class_name,
                c.class_code,
                pa.email as parent_email,
                pa.full_name as parent_name
            FROM invoices i
            JOIN students s ON i.student_id = s.id
            LEFT JOIN classes c ON i.class_id = c.id
            LEFT JOIN parent_accounts pa ON s.parent_account_id = pa.id
            WHERE i.id = ?
        ";
        
        error_log("[Invoice Notification] SQL Query: " . str_replace("\n", " ", $sql));
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$invoiceId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invoice) {
            error_log("[Invoice Notification] ERROR: Invoice ID {$invoiceId} not found in database");
            return false;
        }
        
        error_log("[Invoice Notification] Invoice record found:");
        error_log("  - Invoice Number: " . ($invoice['invoice_number'] ?? 'NULL'));
        error_log("  - Student Name: " . ($invoice['student_name'] ?? 'NULL'));
        error_log("  - Student Email: " . ($invoice['student_email'] ?? 'NULL'));
        error_log("  - Parent Email: " . ($invoice['parent_email'] ?? 'NULL'));
        error_log("  - Amount: " . ($invoice['amount'] ?? 'NULL'));
        
        // Determine recipient email - prioritize parent email, fallback to student email
        $recipientEmail = $invoice['parent_email'];
        $recipientName = $invoice['parent_name'];
        
        if (empty($recipientEmail)) {
            error_log("[Invoice Notification] WARNING: No parent email found, trying student email");
            $recipientEmail = $invoice['student_email'];
            $recipientName = $invoice['student_name'];
        }
        
        if (empty($recipientEmail)) {
            error_log("[Invoice Notification] ERROR: No email found for invoice ID {$invoiceId}");
            return false;
        }
        
        error_log("[Invoice Notification] Email will be sent to: {$recipientEmail}");
        
        // Setup email - use fully qualified class name to avoid conflicts
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'mail.wushusportacademy.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'admin@wushusportacademy.com';
        $mail->Password   = 'P1}tKwojKgl0vdMv';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding   = 'base64';
        
        error_log("[Invoice Notification] SMTP configured");
        
        // Recipients
        $mail->setFrom('admin@wushusportacademy.com', 'Wushu Sport Academy');
        $mail->addAddress($recipientEmail, $recipientName);
        $mail->addReplyTo('admin@wushusportacademy.com', 'Wushu Sport Academy');
        
        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Wushu Sport Academy: Invoice ' . $invoice['invoice_number'] . ' - ' . $invoice['student_name'];
        $mail->Body = getInvoiceNotificationEmailHTML($invoice);
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $mail->Body));
        
        // Send email
        error_log("[Invoice Notification] Attempting to send email via SMTP...");
        $mail->send();
        
        error_log("[Invoice Notification] ✅ SUCCESS! Email sent to {$recipientEmail}");
        error_log("========================================");
        
        return true;
        
    } catch (\Exception $e) {
        error_log("[Invoice Notification] ❌ EXCEPTION CAUGHT:");
        error_log("[Invoice Notification] Message: " . $e->getMessage());
        
        if (isset($mail)) {
            error_log("[Invoice Notification] PHPMailer Error: " . $mail->ErrorInfo);
        }
        
        error_log("========================================");
        return false;
    }
}

/**
 * Get HTML template for invoice notification email
 */
function getInvoiceNotificationEmailHTML($invoice) {
    $invoiceNumber = htmlspecialchars($invoice['invoice_number'], ENT_QUOTES, 'UTF-8');
    $studentName = htmlspecialchars($invoice['student_name'], ENT_QUOTES, 'UTF-8');
    $amount = number_format($invoice['amount'], 2);
    $dueDate = date('F j, Y', strtotime($invoice['due_date']));
    $description = htmlspecialchars($invoice['description'], ENT_QUOTES, 'UTF-8');
    $invoiceType = ucfirst(htmlspecialchars($invoice['invoice_type'], ENT_QUOTES, 'UTF-8'));
    $className = !empty($invoice['class_name']) ? htmlspecialchars($invoice['class_name'], ENT_QUOTES, 'UTF-8') : 'N/A';
    $classCode = !empty($invoice['class_code']) ? htmlspecialchars($invoice['class_code'], ENT_QUOTES, 'UTF-8') : '';
    
    // Portal URL for payment
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
        .header { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 30px 24px; text-align: center; }
        .icon { font-size: 48px; margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 28px; font-weight: 700; }
        .header p { margin: 8px 0 0 0; font-size: 14px; opacity: 0.95; }
        .content { padding: 32px 24px; }
        .info-box { background: #f8fafc; border-radius: 8px; padding: 20px; margin-bottom: 24px; }
        .info-row { display: flex; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #64748b; font-weight: 600; width: 40%; }
        .info-value { color: #1e293b; width: 60%; }
        .amount { color: #ef4444; font-weight: 700; font-size: 24px; }
        .due-date { color: #f59e0b; font-weight: 700; }
        .warning-box { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 8px; margin: 24px 0; }
        .warning-box strong { color: #92400e; }
        .payment-box { background: #dbeafe; border: 2px solid #3b82f6; padding: 20px; border-radius: 8px; margin: 24px 0; text-align: center; }
        .payment-btn { display: inline-block; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 12px; }
        .payment-btn:hover { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
        .footer { text-align: center; padding: 24px; background: #f8fafc; color: #64748b; font-size: 13px; border-top: 1px solid #e2e8f0; }
        ul { margin: 10px 0; padding-left: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">&#128179;</div>
            <h1>New Invoice Generated</h1>
            <p>Payment Required</p>
        </div>
        
        <div class="content">
            <p>Dear Parent/Guardian,</p>
            
            <p>A new invoice has been generated for <strong>' . $studentName . '</strong>. Please review the details below and make payment by the due date.</p>
            
            <div class="info-box">
                <h3 style="margin: 0 0 16px 0; color: #1e293b; font-size: 18px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">&#128196; Invoice Details</h3>
                <div class="info-row">
                    <div class="info-label">Invoice Number:</div>
                    <div class="info-value"><strong>' . $invoiceNumber . '</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Student Name:</div>
                    <div class="info-value">' . $studentName . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Invoice Type:</div>
                    <div class="info-value"><span style="background: #dbeafe; color: #1e40af; padding: 4px 12px; border-radius: 4px; font-size: 13px; font-weight: 600;">' . $invoiceType . '</span></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Class:</div>
                    <div class="info-value">' . $className . ($classCode ? ' (' . $classCode . ')' : '') . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Description:</div>
                    <div class="info-value">' . $description . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Amount Due:</div>
                    <div class="info-value"><span class="amount">RM ' . $amount . '</span></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Due Date:</div>
                    <div class="info-value"><span class="due-date">' . $dueDate . '</span></div>
                </div>
            </div>
            
            <div class="warning-box">
                <strong>&#9888; Important:</strong><br>
                Please make payment before <strong>' . $dueDate . '</strong> to avoid late payment.
            </div>
            
            <div class="payment-box">
                <strong style="font-size: 18px; color: #1e293b;">&#128178; Make Payment Now</strong><br><br>
                <p style="margin: 10px 0; color: #64748b;">Login to your student/parent portal to pay this invoice online.</p>
                <a href="' . $portalUrl . '" class="payment-btn" style="color: white;">&#128274; Go to Payment Portal</a><br>
                <small style="color: #64748b; margin-top: 10px; display: inline-block;">Secure online payment available</small>
            </div>
            
            <p><strong>How to Pay:</strong></p>
            <ul>
                <li>Login to your dashboard</li>
                <li>Go to &quot;Invoices&quot; or &quot;Invoices&quot; section</li>
                <li>Find invoice <strong>' . $invoiceNumber . '</strong></li>
                <li>Upload payment receipt or make online payment</li>
                <li>Wait for admin verification</li>
            </ul>
            
            <p><strong>Payment Methods:</strong></p>
            <ul>
                <li>Bank Transfer</li>
                <li>Cash payment</li>
            </ul>
            
            <p>If you have any questions about this invoice, please don\'t hesitate to contact us.</p>
            
            <p>Thank you!<br>
            <strong>Wushu Sport Academy Team</strong></p>
        </div>
        
        <div class="footer">
            <p style="margin: 0 0 8px 0; font-weight: 600; color: #1e293b; font-size: 15px;">Wushu Sport Academy &#27494;&#26415;&#20307;&#32946;&#23398;&#38498;</p>
            <p style="margin: 4px 0;">Student &amp; Parent Portal System</p>
            <p style="margin: 16px 0 0 0; font-size: 11px; color: #94a3b8;">This is an automated notification. Invoice reference: ' . $invoiceNumber . '</p>
        </div>
    </div>
</body>
</html>
    ';
}

?>