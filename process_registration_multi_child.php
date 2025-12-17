<?php
/**
 * process_registration_multi_child.php - Multi-Child Registration Processing
 * Creates/uses parent account and links children
 * Handles:
 * - New parent with first child
 * - Existing parent adding additional child
 * - Sends separate emails to parent with all children credentials
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

// PHPMailer imports
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

/**
 * Send registration email to parent with child details
 */
function sendParentRegistrationEmail($parentEmail, $parentName, $childName, $registrationNumber, $isNewParent, $parentPassword = null, $allChildren = []) {
    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'chaichonghern@gmail.com';
        $mail->Password   = 'kyyj elhp dkdw gvki';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('noreply@wushusportacademy.com', 'Wushu Sport Academy');
        $mail->addAddress($parentEmail, $parentName);
        $mail->addReplyTo('admin@wushusportacademy.com', 'Academy Admin');

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Wushu Sport Academy - Registration Successful for ' . $childName;
        
        $mail->Body = getParentEmailHTMLContent($parentName, $childName, $registrationNumber, $parentEmail, $isNewParent, $parentPassword, $allChildren);
        
        $mail->AltBody = "Registration Successful!\n\n"
                       . "Parent Name: $parentName\n"
                       . "Child Registered: $childName\n"
                       . "Registration Number: $registrationNumber\n"
                       . ($isNewParent ? "Your Parent Account Password: $parentPassword\n" : "")
                       . "\nPlease login to the parent portal to manage your children's accounts.";

        $mail->send();
        error_log("Parent email sent successfully to: $parentEmail");
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending failed to $parentEmail: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Generate HTML content for parent registration email
 */
function getParentEmailHTMLContent($parentName, $childName, $registrationNumber, $parentEmail, $isNewParent, $parentPassword, $allChildren) {
    $childrenList = '';
    if (!empty($allChildren)) {
        $childrenList = '<div class="credentials">';
        $childrenList .= '<h3>👨‍👩‍👧‍👦 Your Registered Children 您注册的孩子</h3>';
        foreach ($allChildren as $child) {
            $childrenList .= '
            <div style="background: #f8fafc; padding: 16px; margin-bottom: 12px; border-left: 4px solid #3b82f6; border-radius: 6px;">
                <div class="credential-row">
                    <div class="credential-label">Name 姓名</div>
                    <div class="credential-value" style="font-weight: 700; color: #1e293b;">' . htmlspecialchars($child['name']) . '</div>
                </div>
                <div class="credential-row">
                    <div class="credential-label">Student ID 学号</div>
                    <div class="credential-value" style="font-weight: 600; color: #7c3aed;">' . htmlspecialchars($child['student_id']) . '</div>
                </div>
                <div class="credential-row">
                    <div class="credential-label">Status 状态</div>
                    <div class="credential-value"><span class="status-badge">' . htmlspecialchars($child['status']) . '</span></div>
                </div>
            </div>';
        }
        $childrenList .= '</div>';
    }

    $passwordSection = '';
    if ($isNewParent && $parentPassword) {
        $passwordSection = '
        <div class="warning-box">
            <p><strong>🔑 Your Parent Account Password 您的家长账户密码:</strong></p>
            <div class="password-highlight" style="margin-top: 12px;">' . $parentPassword . '</div>
            <p style="margin-top: 12px; font-size: 13px;">Please save this password securely. You will use your email and this password to login.</p>
            <p style="font-size: 13px; color: #dc2626;">请妥善保管此密码。您将使用您的电子邮件和此密码登录。</p>
        </div>';
    }

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
            .header img {
                width: 60px;
                height: 60px;
                border-radius: 50%;
                border: 3px solid white;
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                background: white;
                padding: 4px;
                margin-bottom: 12px;
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
            .footer {
                text-align: center;
                padding: 24px;
                background: #f8fafc;
                color: #64748b;
                font-size: 13px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <!-- Replace with your logo -->
                <!-- <img src='https://your-domain.com/uploads/logo.png' alt='Logo'> -->
                <h1>Welcome to Wushu Sport Academy!</h1>
                <p>报名成功 · Registration Successful</p>
            </div>
            
            <div class='content'>
                <div class='greeting'>Dear {$parentName},</div>
                
                <p style='margin-bottom: 16px;'>" . ($isNewParent ? 'Congratulations! Your parent account has been created and your child has been successfully registered.' : 'Your child has been successfully added to your account.') . "</p>
                <p style='color: #64748b; margin-bottom: 24px;'>恭喜！" . ($isNewParent ? '您的家长账户已创建，您的孩子已成功注册。' : '您的孩子已成功添加到您的账户。') . "</p>
                
                <div class='credentials'>
                    <h3>🎓 Newly Registered Child 新注册的孩子</h3>
                    
                    <div class='credential-row'>
                        <div class='credential-label'>Child Name<br><span style='font-size: 12px; font-weight: normal; color: #94a3b8;'>孩子姓名</span></div>
                        <div class='credential-value' style='font-weight: 700; color: #1e293b; font-size: 18px;'>{$childName}</div>
                    </div>
                    
                    <div class='credential-row'>
                        <div class='credential-label'>Registration Number<br><span style='font-size: 12px; font-weight: normal; color: #94a3b8;'>报名号码</span></div>
                        <div class='credential-value' style='font-weight: 700; color: #7c3aed; font-size: 18px;'>{$registrationNumber}</div>
                    </div>
                    
                    <div class='credential-row'>
                        <div class='credential-label'>Parent Email<br><span style='font-size: 12px; font-weight: normal; color: #94a3b8;'>家长邮箱</span></div>
                        <div class='credential-value'>{$parentEmail}</div>
                    </div>
                </div>
                
                {$passwordSection}
                
                {$childrenList}
                
                <div class='info-box'>
                    <p><strong>📋 Next Steps / 接下来的步骤:</strong></p>
                    <ul>
                        <li>Payment verification is under review 付款正在审核中</li>
                        <li>Login to parent portal to manage all children 登录家长门户管理所有孩子</li>
                        <li>View schedules, payments, and attendance 查看课程表、付款和出勤</li>
                        <li>Add more children anytime 随时添加更多孩子</li>
                    </ul>
                </div>
                
                <div class='button-container'>
                    <a href='https://your-domain.com/parent_portal.php' class='button'>
                        🚀 Login to Parent Portal<br>
                        <span style='font-size: 13px; font-weight: normal;'>登录家长门户</span>
                    </a>
                </div>
            </div>
            
            <div class='footer'>
                <p><strong>Wushu Sport Academy 武术体育学院</strong></p>
                <p>This is an automated email. Please do not reply.</p>
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
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('Invalid JSON data received');
    }

    // Validate required fields
    $required = [
        'name_en', 'ic', 'age', 'school', 'status', 'phone', 'email', 
        'events', 'schedule', 'parent_name', 'parent_ic', 'parent_email',
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
    $conn->beginTransaction();

    $parentEmail = trim($data['parent_email']);
    $parentName = trim($data['parent_name']);
    $parentIC = trim($data['parent_ic']);
    $parentPhone = trim($data['phone']);
    
    // Check if parent account exists
    $stmt = $conn->prepare("SELECT id, parent_id FROM parent_accounts WHERE email = ?");
    $stmt->execute([$parentEmail]);
    $existingParent = $stmt->fetch();
    
    $isNewParent = false;
    $parentAccountId = null;
    $parentPassword = null;
    $parentCode = null;
    
    if (!$existingParent) {
        // Create new parent account
        $isNewParent = true;
        $year = date('Y');
        $stmt = $conn->query("SELECT COUNT(*) FROM parent_accounts WHERE YEAR(created_at) = $year");
        $count = $stmt->fetchColumn();
        $parentCode = 'PAR-' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        
        // Generate parent password
        $parentPassword = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 4) . 
                         substr(str_shuffle('abcdefghjkmnpqrstuvwxyz'), 0, 4);
        $hashedPassword = password_hash($parentPassword, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("
            INSERT INTO parent_accounts (parent_id, full_name, email, phone, ic_number, password, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");
        $stmt->execute([$parentCode, $parentName, $parentEmail, $parentPhone, $parentIC, $hashedPassword]);
        $parentAccountId = $conn->lastInsertId();
        
        error_log("Created new parent account: $parentCode for $parentEmail");
    } else {
        $parentAccountId = $existingParent['id'];
        $parentCode = $existingParent['parent_id'];
        error_log("Using existing parent account: $parentCode");
    }
    
    // Generate student registration number
    $year = date('Y');
    $stmt = $conn->query("SELECT COUNT(*) FROM registrations WHERE YEAR(created_at) = $year");
    $count = $stmt->fetchColumn();
    $regNumber = 'WSA' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    
    // Create student account (no password - managed by parent)
    $studentId = $regNumber;
    $fullName = trim($data['name_en']);
    $studentStatus = trim($data['status']);
    
    $stmt = $conn->prepare("
        INSERT INTO students (
            student_id, full_name, name_cn, email, phone, ic_number, age, school,
            student_status, status, has_parent_account, password, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 1, NULL, NOW())
    ");
    $stmt->execute([
        $studentId, 
        $fullName,
        isset($data['name_cn']) ? trim($data['name_cn']) : '',
        $parentEmail, // Use parent email
        $parentPhone,
        trim($data['ic']),
        intval($data['age']),
        trim($data['school']),
        $studentStatus
    ]);
    $studentAccountId = $conn->lastInsertId();
    
    // Link parent to student
    $stmt = $conn->prepare("
        INSERT INTO parent_student_links (parent_id, student_id, relationship, is_primary) 
        VALUES (?, ?, 'parent', 1)
    ");
    $stmt->execute([$parentAccountId, $studentAccountId]);
    
    // Insert registration record
    $sql = "INSERT INTO registrations (
        registration_number, name_cn, name_en, ic, age, school, status,
        phone, email, level, events, schedule, parent_name, parent_ic,
        form_date, signature_base64, pdf_base64, 
        payment_amount, payment_date, payment_receipt_base64, payment_status, class_count,
        student_account_id, parent_account_id, account_created, password_generated, created_at
    ) VALUES (
        :reg_num, :name_cn, :name_en, :ic, :age, :school, :status,
        :phone, :email, :level, :events, :schedule, :parent_name, :parent_ic,
        :form_date, :signature_base64, :pdf_base64,
        :payment_amount, :payment_date, :payment_receipt_base64, 'pending', :class_count,
        :student_account_id, :parent_account_id, 'yes', :password_generated, NOW()
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
        ':phone' => $parentPhone,
        ':email' => $parentEmail,
        ':level' => isset($data['level']) ? trim($data['level']) : '',
        ':events' => trim($data['events']),
        ':schedule' => trim($data['schedule']),
        ':parent_name' => $parentName,
        ':parent_ic' => $parentIC,
        ':form_date' => $data['form_date'],
        ':signature_base64' => $data['signature_base64'],
        ':pdf_base64' => $data['signed_pdf_base64'],
        ':payment_amount' => floatval($data['payment_amount']),
        ':payment_date' => $data['payment_date'],
        ':payment_receipt_base64' => $data['payment_receipt_base64'],
        ':class_count' => intval($data['class_count']),
        ':student_account_id' => $studentAccountId,
        ':parent_account_id' => $parentAccountId,
        ':password_generated' => $parentPassword ? $parentPassword : 'N/A (Existing Parent)'
    ]);
    
    // Get all children for this parent
    $stmt = $conn->prepare("
        SELECT s.full_name as name, s.student_id, s.student_status as status
        FROM students s
        INNER JOIN parent_student_links psl ON s.id = psl.student_id
        WHERE psl.parent_id = ?
    ");
    $stmt->execute([$parentAccountId]);
    $allChildren = $stmt->fetchAll();
    
    $conn->commit();

    // Send email
    $emailSent = false;
    try {
        $emailSent = sendParentRegistrationEmail($parentEmail, $parentName, $fullName, $regNumber, $isNewParent, $parentPassword, $allChildren);
    } catch (Exception $e) {
        error_log("Email error (non-critical): " . $e->getMessage());
    }

    error_log("✅ Registration successful: $regNumber for child $fullName, parent $parentCode | Email sent: " . ($emailSent ? 'Yes' : 'No'));

    echo json_encode([
        'success' => true,
        'registration_number' => $regNumber,
        'student_id' => $studentId,
        'parent_email' => $parentEmail,
        'parent_code' => $parentCode,
        'is_new_parent' => $isNewParent,
        'parent_password' => $parentPassword,
        'child_name' => $fullName,
        'email_sent' => $emailSent,
        'total_children' => count($allChildren),
        'message' => ($isNewParent ? 'Parent account created! ' : 'Child added to existing parent account. ') .
                    'Your child has been successfully registered.' . 
                    ($emailSent ? ' Please check your email for details.' : '')
    ]);

} catch (PDOException $e) {
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