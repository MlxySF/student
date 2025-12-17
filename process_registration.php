<?php
/**
 * process_registration.php - Complete Registration Processing with PHPMailer
 * Handles student registration, account creation, and email notification
 * Updated: Allows re-registration with same email if previous registration was rejected
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

// PHPMailer imports
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// If using manual download, uncomment these lines instead:
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

/**
 * Send registration email with account credentials
 */
function sendRegistrationEmail($toEmail, $studentName, $registrationNumber, $password, $studentStatus) {
    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        
        // ============================================
        // CONFIGURE YOUR SMTP SETTINGS HERE
        // ============================================
        
        // Gmail Example:
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'chaichonghern@gmail.com'; // Your Gmail address
        $mail->Password   = 'kyyj elhp dkdw gvki';    // Your Gmail App Password (not regular password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        /* 
        // Alternative: cPanel/Hosting Email
        $mail->Host       = 'mail.yourdomain.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noreply@yourdomain.com';
        $mail->Password   = 'your-email-password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SSL;
        $mail->Port       = 465;
        */
        
        /*
        // Alternative: SendGrid
        $mail->Host       = 'smtp.sendgrid.net';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'apikey';
        $mail->Password   = 'your-sendgrid-api-key';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        */

        // Email settings
        $mail->setFrom('noreply@wushusportacademy.com', 'Wushu Sport Academy');
        $mail->addAddress($toEmail, $studentName);
        $mail->addReplyTo('admin@wushusportacademy.com', 'Academy Admin');

        // Optional: Add CC or BCC
        // $mail->addCC('admin@wushusportacademy.com');
        // $mail->addBCC('backup@wushusportacademy.com');

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = '🎊 Wushu Sport Academy - Registration Successful';
        
        // HTML Email Body
        $mail->Body = getEmailHTMLContent($studentName, $registrationNumber, $toEmail, $password, $studentStatus);
        
        // Plain text alternative
        $mail->AltBody = "Registration Successful!\n\n"
                       . "Student Name: $studentName\n"
                       . "Registration Number: $registrationNumber\n"
                       . "Email: $toEmail\n"
                       . "Password: $password\n"
                       . "Status: $studentStatus\n\n"
                       . "Please login to the student portal to view your details.";

        // Send email
        $mail->send();
        error_log("Email sent successfully to: $toEmail");
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending failed to $toEmail: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Generate HTML content for registration email
 */
function getEmailHTMLContent($studentName, $registrationNumber, $toEmail, $password, $studentStatus) {
    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { 
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0; 
                padding: 0;
                background-color: #f5f5f5;
            }
            .container { 
                max-width: 600px; 
                margin: 20px auto; 
                background: white;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            .header { 
                background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
                color: white; 
                padding: 32px 24px; 
                text-align: center;
            }
            .header h1 {
                margin: 0 0 8px 0;
                font-size: 28px;
                font-weight: 700;
            }
            .header p {
                margin: 0;
                font-size: 15px;
                opacity: 0.9;
            }
            .content { 
                padding: 32px 24px;
                background: white;
            }
            .greeting {
                font-size: 18px;
                font-weight: 600;
                color: #1e293b;
                margin-bottom: 16px;
            }
            .credentials { 
                background: #f8fafc;
                padding: 24px; 
                margin: 24px 0; 
                border-left: 4px solid #fbbf24;
                border-radius: 8px;
            }
            .credentials h3 {
                margin: 0 0 16px 0;
                font-size: 18px;
                color: #1e293b;
            }
            .credential-row {
                display: table;
                width: 100%;
                margin-bottom: 12px;
            }
            .credential-label {
                display: table-cell;
                width: 45%;
                font-weight: 600;
                color: #475569;
                padding: 8px 0;
            }
            .credential-value {
                display: table-cell;
                padding: 8px 0;
                color: #1e293b;
            }
            .password-highlight {
                font-size: 22px;
                color: #dc2626;
                font-weight: bold;
                font-family: 'Courier New', monospace;
                letter-spacing: 3px;
                background: #fef2f2;
                padding: 12px 16px;
                border-radius: 6px;
                display: inline-block;
                margin-top: 4px;
            }
            .status-badge {
                display: inline-block;
                padding: 6px 12px;
                background: #dcfce7;
                color: #15803d;
                border-radius: 6px;
                font-weight: 600;
                font-size: 14px;
            }
            .warning-box {
                background: #fef3c7;
                border-left: 4px solid #f59e0b;
                padding: 16px;
                border-radius: 6px;
                margin: 24px 0;
            }
            .warning-box p {
                margin: 0;
                font-size: 14px;
                color: #92400e;
            }
            .info-box {
                background: #eff6ff;
                border-left: 4px solid #3b82f6;
                padding: 16px;
                border-radius: 6px;
                margin: 24px 0;
            }
            .info-box p {
                margin: 0 0 8px 0;
                font-size: 14px;
                color: #1e40af;
            }
            .info-box ul {
                margin: 8px 0 0 20px;
                padding: 0;
                color: #1e40af;
            }
            .info-box li {
                margin-bottom: 6px;
            }
            .button-container {
                text-align: center;
                margin: 32px 0;
            }
            .button {
                display: inline-block;
                padding: 14px 32px;
                background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
                color: white !important;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                font-size: 16px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            .divider {
                border: none;
                border-top: 1px solid #e2e8f0;
                margin: 32px 0;
            }
            .footer {
                text-align: center;
                padding: 24px;
                background: #f8fafc;
                color: #64748b;
                font-size: 13px;
            }
            .footer p {
                margin: 4px 0;
            }
            .footer strong {
                color: #475569;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
    <div style='text-align: center; margin-bottom: 16px;'>
        <img src='https://wushu-assets.s3.ap-southeast-1.amazonaws.com/Wushu+Sport+Academy+Circle+Yellow.png' alt='Wushu Sport Academy Logo' style='width: 60px; height: 60px; border-radius: 50%; border: 3px solid white; box-shadow: 0 4px 8px rgba(0,0,0,0.2);'>
    </div>
    <h1 style='margin-top: 12px;'>Welcome to Wushu Sport Academy!</h1>
    <p>报名成功 · Registration Successful</p>
</div>
            
            <div class='content'>
                <div class='greeting'>Dear {$studentName},</div>
                
                <p style='margin-bottom: 16px;'>Congratulations! Your registration has been successfully processed and your student account has been created.</p>
                <p style='color: #64748b; margin-bottom: 24px;'>恭喜！您的报名已成功处理，您的学生账户已创建。</p>
                
                <div class='credentials'>
                    <h3>🔑 Your Account Credentials 您的账户凭证</h3>
                    
                    <div class='credential-row'>
                        <div class='credential-label'>Registration Number<br><span style='font-size: 12px; font-weight: normal; color: #94a3b8;'>报名号码</span></div>
                        <div class='credential-value' style='font-weight: 700; color: #7c3aed; font-size: 18px;'>{$registrationNumber}</div>
                    </div>
                    
                    <div class='credential-row'>
                        <div class='credential-label'>Student ID<br><span style='font-size: 12px; font-weight: normal; color: #94a3b8;'>学号</span></div>
                        <div class='credential-value' style='font-weight: 600;'>{$registrationNumber}</div>
                    </div>
                    
                    <div class='credential-row'>
                        <div class='credential-label'>Email<br><span style='font-size: 12px; font-weight: normal; color: #94a3b8;'>邮箱</span></div>
                        <div class='credential-value'>{$toEmail}</div>
                    </div>
                    
                    <div class='credential-row'>
                        <div class='credential-label' style='vertical-align: top; padding-top: 16px;'>Password<br><span style='font-size: 12px; font-weight: normal; color: #94a3b8;'>密码</span></div>
                        <div class='credential-value'>
                            <div class='password-highlight'>{$password}</div>
                        </div>
                    </div>
                    
                    <div class='credential-row'>
                        <div class='credential-label'>Status<br><span style='font-size: 12px; font-weight: normal; color: #94a3b8;'>身份</span></div>
                        <div class='credential-value'>
                            <span class='status-badge'>{$studentStatus}</span>
                        </div>
                    </div>
                </div>
                
                <div class='warning-box'>
                    <p><strong>⚠️ Important / 重要提示:</strong></p>
                    <p style='margin-top: 8px;'>Please save your password in a secure location. You will need these credentials to access the student portal.</p>
                    <p style='margin-top: 4px; font-size: 13px;'>请将您的密码保存在安全的地方。您需要这些凭证来访问学生门户。</p>
                </div>
                
                <div class='info-box'>
                    <p><strong>📋 Next Steps / 接下来的步骤:</strong></p>
                    <ul>
                        <li>Your payment is currently under review<br><span style='font-size: 13px;'>您的付款正在审核中</span></li>
                        <li>You will receive confirmation once verified<br><span style='font-size: 13px;'>审核通过后您将收到确认</span></li>
                        <li>Login to the student portal with your credentials<br><span style='font-size: 13px;'>使用您的凭证登录学生门户</span></li>
                        <li>View your class schedule and payment status<br><span style='font-size: 13px;'>查看您的课程表和付款状态</span></li>
                    </ul>
                </div>
                
                <div class='button-container'>
                    <a href='https://your-domain.com/index.php?page=login' class='button'>
                        🚀 Login to Student Portal<br>
                        <span style='font-size: 13px; font-weight: normal;'>登录学生门户</span>
                    </a>
                </div>
                
                <hr class='divider'>
                
                <p style='font-size: 13px; color: #64748b; margin-bottom: 8px;'>
                    If you did not request this registration or have any questions, please contact us immediately.
                </p>
                <p style='font-size: 13px; color: #64748b;'>
                    如果您没有申请此注册或有任何疑问，请立即与我们联系。
                </p>
            </div>
            
            <div class='footer'>
                <p><strong>Wushu Sport Academy 武术体育学院</strong></p>
                <p>No. 2, Jalan BP 5/6, Bandar Bukit Puchong</p>
                <p>47120 Puchong, Selangor, Malaysia</p>
                <p style='margin-top: 12px;'>
                    📧 Email: admin@wushusportacademy.com<br>
                    📱 Phone: +60 12-345 6789
                </p>
                <p style='margin-top: 16px; font-size: 11px; color: #94a3b8;'>
                    This is an automated email. Please do not reply to this message.<br>
                    此邮件为系统自动发送，请勿直接回复。
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
}

// ============================================
// MAIN REGISTRATION PROCESSING
// ============================================

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('Invalid JSON data received');
    }

    // Validate required fields
    $required = [
        'name_en', 'ic', 'age', 'school', 'status', 'phone', 'email', 
        'events', 'schedule', 'parent_name', 'parent_ic', 
        'form_date', 'signature_base64', 'signed_pdf_base64',
        'payment_amount', 'payment_date', 'payment_receipt_base64', 'class_count'
    ];
    
    foreach ($required as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            throw new Exception("Missing or empty required field: $field");
        }
    }

    // Database connection
    $host = 'localhost';
    $dbname = 'mlxysf_student_portal';
    $username = 'mlxysf_student_portal';
    $password = 'YAjv86kdSAPpw';

    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Start transaction
    $conn->beginTransaction();

    $email = trim($data['email']);
    
    // Check if email exists in students table
    $stmt = $conn->prepare("SELECT id, student_id FROM students WHERE email = ?");
    $stmt->execute([$email]);
    $existingStudent = $stmt->fetch();
    
    // Check if email exists in registrations table and get its status
    $stmt = $conn->prepare("SELECT id, payment_status, student_account_id FROM registrations WHERE email = ?");
    $stmt->execute([$email]);
    $existingRegistration = $stmt->fetch();
    
    $isReregistration = false;
    $oldRegistrationId = null;
    $oldStudentAccountId = null;
    
    // If registration exists and is rejected, allow re-registration
    if ($existingRegistration && $existingRegistration['payment_status'] === 'rejected') {
        $isReregistration = true;
        $oldRegistrationId = $existingRegistration['id'];
        $oldStudentAccountId = $existingRegistration['student_account_id'];
        error_log("Re-registration detected for email: $email (Old Registration ID: $oldRegistrationId)");
    } else if ($existingStudent || ($existingRegistration && $existingRegistration['payment_status'] !== 'rejected')) {
        // Email exists and is NOT rejected
        throw new Exception("Email already registered. Please use a different email address.");
    }

    // Generate registration number
    $year = date('Y');
    $stmt = $conn->query("SELECT COUNT(*) FROM registrations WHERE YEAR(created_at) = $year");
    $count = $stmt->fetchColumn();
    $regNumber = 'WSA' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

    // Generate random password (8 characters: 4 uppercase + 4 lowercase)
    $generatedPassword = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 4) . 
                        substr(str_shuffle('abcdefghjkmnpqrstuvwxyz'), 0, 4);
    $hashedPassword = password_hash($generatedPassword, PASSWORD_DEFAULT);

    // Prepare student data
    $studentId = $regNumber;
    $fullName = trim($data['name_en']);
    $phone = trim($data['phone']);
    $studentStatus = trim($data['status']);

    $studentAccountId = null;
    
    if ($isReregistration && $oldStudentAccountId) {
        // Update existing student account
        $stmt = $conn->prepare("
            UPDATE students 
            SET student_id = ?, full_name = ?, email = ?, phone = ?, password = ?, student_status = ?
            WHERE id = ?
        ");
        $stmt->execute([$studentId, $fullName, $email, $phone, $hashedPassword, $studentStatus, $oldStudentAccountId]);
        $studentAccountId = $oldStudentAccountId;
        error_log("Updated existing student account ID: $studentAccountId");
    } else {
        // Insert new student account
        $stmt = $conn->prepare("
            INSERT INTO students (student_id, full_name, email, phone, password, student_status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$studentId, $fullName, $email, $phone, $hashedPassword, $studentStatus]);
        $studentAccountId = $conn->lastInsertId();
        error_log("Created new student account ID: $studentAccountId");
    }

    if ($isReregistration && $oldRegistrationId) {
        // Update existing rejected registration with new data and set status to pending
        $sql = "UPDATE registrations SET
            registration_number = :reg_num,
            name_cn = :name_cn,
            name_en = :name_en,
            ic = :ic,
            age = :age,
            school = :school,
            status = :status,
            phone = :phone,
            email = :email,
            level = :level,
            events = :events,
            schedule = :schedule,
            parent_name = :parent_name,
            parent_ic = :parent_ic,
            form_date = :form_date,
            signature_base64 = :signature_base64,
            pdf_base64 = :pdf_base64,
            payment_amount = :payment_amount,
            payment_date = :payment_date,
            payment_receipt_base64 = :payment_receipt_base64,
            payment_status = 'pending',
            class_count = :class_count,
            student_account_id = :student_account_id,
            account_created = 'yes',
            password_generated = :password_generated,
            created_at = NOW()
            WHERE id = :old_reg_id";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':reg_num' => $regNumber,
            ':name_cn' => isset($data['name_cn']) ? trim($data['name_cn']) : '',
            ':name_en' => $fullName,
            ':ic' => trim($data['ic']),
            ':age' => intval($data['age']),
            ':school' => trim($data['school']),
            ':status' => $studentStatus,
            ':phone' => $phone,
            ':email' => $email,
            ':level' => isset($data['level']) ? trim($data['level']) : '',
            ':events' => trim($data['events']),
            ':schedule' => trim($data['schedule']),
            ':parent_name' => trim($data['parent_name']),
            ':parent_ic' => trim($data['parent_ic']),
            ':form_date' => $data['form_date'],
            ':signature_base64' => $data['signature_base64'],
            ':pdf_base64' => $data['signed_pdf_base64'],
            ':payment_amount' => floatval($data['payment_amount']),
            ':payment_date' => $data['payment_date'],
            ':payment_receipt_base64' => $data['payment_receipt_base64'],
            ':class_count' => intval($data['class_count']),
            ':student_account_id' => $studentAccountId,
            ':password_generated' => $generatedPassword,
            ':old_reg_id' => $oldRegistrationId
        ]);
        error_log("Updated registration ID $oldRegistrationId with new data, status changed to pending");
    } else {
        // Insert new registration
        $sql = "INSERT INTO registrations (
            registration_number, name_cn, name_en, ic, age, school, status,
            phone, email, level, events, schedule, parent_name, parent_ic,
            form_date, signature_base64, pdf_base64, 
            payment_amount, payment_date, payment_receipt_base64, payment_status, class_count,
            student_account_id, account_created, password_generated, created_at
        ) VALUES (
            :reg_num, :name_cn, :name_en, :ic, :age, :school, :status,
            :phone, :email, :level, :events, :schedule, :parent_name, :parent_ic,
            :form_date, :signature_base64, :pdf_base64,
            :payment_amount, :payment_date, :payment_receipt_base64, 'pending', :class_count,
            :student_account_id, 'yes', :password_generated, NOW()
        )";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':reg_num' => $regNumber,
            ':name_cn' => isset($data['name_cn']) ? trim($data['name_cn']) : '',
            ':name_en' => $fullName,
            ':ic' => trim($data['ic']),
            ':age' => intval($data['age']),
            ':school' => trim($data['school']),
            ':status' => $studentStatus,
            ':phone' => $phone,
            ':email' => $email,
            ':level' => isset($data['level']) ? trim($data['level']) : '',
            ':events' => trim($data['events']),
            ':schedule' => trim($data['schedule']),
            ':parent_name' => trim($data['parent_name']),
            ':parent_ic' => trim($data['parent_ic']),
            ':form_date' => $data['form_date'],
            ':signature_base64' => $data['signature_base64'],
            ':pdf_base64' => $data['signed_pdf_base64'],
            ':payment_amount' => floatval($data['payment_amount']),
            ':payment_date' => $data['payment_date'],
            ':payment_receipt_base64' => $data['payment_receipt_base64'],
            ':class_count' => intval($data['class_count']),
            ':student_account_id' => $studentAccountId,
            ':password_generated' => $generatedPassword
        ]);
    }

    // Commit transaction
    $conn->commit();

    // Send email with account credentials
    $emailSent = false;
    try {
        $emailSent = sendRegistrationEmail($email, $fullName, $regNumber, $generatedPassword, $studentStatus);
    } catch (Exception $e) {
        error_log("Email error (non-critical): " . $e->getMessage());
        // Don't fail the registration if email fails
    }

    // Log success
    $regType = $isReregistration ? 'Re-registration' : 'Registration';
    error_log("✅ $regType successful: $regNumber for $email (Status: $studentStatus) | Email sent: " . ($emailSent ? 'Yes' : 'No'));

    // Return success response
    echo json_encode([
        'success' => true,
        'registration_number' => $regNumber,
        'student_id' => $studentId,
        'email' => $email,
        'password' => $generatedPassword,
        'status' => $studentStatus,
        'email_sent' => $emailSent,
        'is_reregistration' => $isReregistration,
        'message' => ($isReregistration ? 'Re-registration successful! Your previous rejected registration has been updated. ' : 'Registration successful! ') .
                    'Your account has been created.' . 
                    ($emailSent ? ' Please check your email for login credentials.' : ' Please save your credentials displayed on screen.')
    ]);

} catch (PDOException $e) {
    // Rollback on database error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("❌ Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
    
} catch (Exception $e) {
    // Rollback on general error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("❌ Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
