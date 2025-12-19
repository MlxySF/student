<?php
/**
 * register_additional_child.php - Register Additional Child for Existing Parents
 * Stage 3: Allows logged-in parents to register more children
 */

session_start();
require_once 'config/database.php';
require_once 'includes/auth_helper.php';

// Check if user is logged in and is a parent
if (!isLoggedIn() || !isParent()) {
    header('Location: index.php?page=login&error=parent_only');
    exit;
}

// Get parent information
$parentId = getUserId();
$stmt = $pdo->prepare("SELECT * FROM parent_accounts WHERE id = ?");
$stmt->execute([$parentId]);
$parentInfo = $stmt->fetch();

if (!$parentInfo) {
    die('Parent account not found.');
}

// Get existing children count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM parent_child_relationships WHERE parent_id = ?");
$stmt->execute([$parentId]);
$existingChildrenCount = $stmt->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Additional Child - Wushu Sport Academy</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #7c3aed;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        
        .registration-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 25px;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.4);
        }
        
        .parent-info-box {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-left: 5px solid var(--primary-color);
            padding: 20px;
            border-radius: 10px;
        }
        
        .required::after {
            content: ' *';
            color: red;
        }
        
        .signature-pad {
            border: 2px dashed #ddd;
            border-radius: 8px;
            cursor: crosshair;
        }
    </style>
</head>
<body>
    <div class="container registration-container">
        <!-- Back Button -->
        <div class="mb-3">
            <a href="index.php?page=dashboard" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Registration Card -->
        <div class="card animate__animated animate__fadeIn">
            <div class="card-header">
                <h3 class="mb-0"><i class="fas fa-user-plus"></i> Register Additional Child</h3>
                <p class="mb-0 mt-2 opacity-75">Add another child to your parent account</p>
            </div>
            
            <div class="card-body p-4">
                <!-- Parent Info Display -->
                <div class="parent-info-box mb-4">
                    <h5 class="mb-3"><i class="fas fa-user-check"></i> Parent Account Information</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($parentInfo['full_name']); ?></p>
                            <p class="mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($parentInfo['email']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Phone:</strong> <?php echo htmlspecialchars($parentInfo['phone']); ?></p>
                            <p class="mb-2"><strong>Existing Children:</strong> <span class="badge bg-primary"><?php echo $existingChildrenCount; ?></span></p>
                        </div>
                    </div>
                    <p class="text-muted small mb-0 mt-2">
                        <i class="fas fa-info-circle"></i> The new child will be linked to this parent account.
                    </p>
                </div>

                <!-- Registration Form -->
                <form id="additionalChildForm" enctype="multipart/form-data">
                    <input type="hidden" name="is_additional_child" value="1">
                    <input type="hidden" name="parent_account_id" value="<?php echo $parentId; ?>">
                    <input type="hidden" name="parent_name" value="<?php echo htmlspecialchars($parentInfo['full_name']); ?>">
                    <input type="hidden" name="parent_email" value="<?php echo htmlspecialchars($parentInfo['email']); ?>">
                    <input type="hidden" name="parent_phone" value="<?php echo htmlspecialchars($parentInfo['phone']); ?>">
                    <input type="hidden" name="parent_ic" value="<?php echo htmlspecialchars($parentInfo['ic_number'] ?? ''); ?>">

                    <h5 class="mb-3 mt-4"><i class="fas fa-child"></i> Child Information</h5>

                    <!-- Child Name -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Full Name (English)</label>
                            <input type="text" class="form-control" name="name_en" required placeholder="e.g. John Smith">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Full Name (Chinese)</label>
                            <input type="text" class="form-control" name="name_cn" placeholder="e.g. 李明 (Optional)">
                        </div>
                    </div>

                    <!-- IC & Age -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">IC Number / Birth Cert</label>
                            <input type="text" class="form-control" name="ic" required placeholder="e.g. 050101-01-0123">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Age</label>
                            <input type="number" class="form-control" name="age" required min="4" max="18" placeholder="e.g. 10">
                        </div>
                    </div>

                    <!-- Email & Phone -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Child's Email</label>
                            <input type="email" class="form-control" name="email" required placeholder="child@example.com">
                            <small class="text-muted">For child's portal login</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Child's Phone</label>
                            <input type="tel" class="form-control" name="phone" required placeholder="01X-XXX XXXX">
                        </div>
                    </div>

                    <!-- School & Status -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">School</label>
                            <input type="text" class="form-control" name="school" required placeholder="e.g. SJKC Puchong">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Student Status</label>
                            <select class="form-select" name="status" required>
                                <option value="">Select Status</option>
                                <option value="Normal Student 普通学员">Normal Student 普通学员</option>
                                <option value="State Team 州队">State Team 州队</option>
                                <option value="Backup Team 后备队">Backup Team 后备队</option>
                            </select>
                        </div>
                    </div>

                    <!-- Events & Level -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Events</label>
                            <input type="text" class="form-control" name="events" required placeholder="e.g. Taolu, Sanda">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Level</label>
                            <input type="text" class="form-control" name="level" placeholder="e.g. Beginner, Intermediate">
                        </div>
                    </div>

                    <!-- Schedule -->
                    <div class="mb-3">
                        <label class="form-label required">Class Schedule</label>
                        <textarea class="form-control" name="schedule" rows="2" required placeholder="e.g. Monday & Wednesday 6pm-8pm"></textarea>
                    </div>

                    <!-- Class Count -->
                    <div class="mb-3">
                        <label class="form-label required">Number of Classes per Month</label>
                        <input type="number" class="form-control" name="class_count" required min="1" max="30" value="8">
                    </div>

                    <hr class="my-4">

                    <h5 class="mb-3"><i class="fas fa-credit-card"></i> Payment Information</h5>

                    <!-- Payment Amount & Date -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Payment Amount (RM)</label>
                            <input type="number" class="form-control" name="payment_amount" required step="0.01" min="0" placeholder="e.g. 200.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Payment Date</label>
                            <input type="date" class="form-control" name="payment_date" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <!-- Payment Receipt -->
                    <div class="mb-3">
                        <label class="form-label required">Payment Receipt</label>
                        <input type="file" class="form-control" name="payment_receipt" required accept="image/*,.pdf">
                        <small class="text-muted">Upload bank transfer receipt or payment proof</small>
                    </div>

                    <hr class="my-4">

                    <h5 class="mb-3"><i class="fas fa-signature"></i> Parent Signature</h5>

                    <!-- Signature Canvas -->
                    <div class="mb-3">
                        <label class="form-label required">Parent/Guardian Signature</label>
                        <canvas id="signatureCanvas" class="signature-pad" width="600" height="200"></canvas>
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSignature">
                                <i class="fas fa-eraser"></i> Clear Signature
                            </button>
                        </div>
                        <input type="hidden" name="signature_base64" id="signatureData">
                    </div>

                    <!-- Form Date -->
                    <div class="mb-3">
                        <label class="form-label required">Form Date</label>
                        <input type="date" class="form-control" name="form_date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                            <i class="fas fa-paper-plane"></i> Submit Registration
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <div id="messageContainer" class="mt-3"></div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
    <script>
        // Signature Pad Implementation
        const canvas = document.getElementById('signatureCanvas');
        const ctx = canvas.getContext('2d');
        let isDrawing = false;

        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing);

        // Touch events for mobile
        canvas.addEventListener('touchstart', (e) => {
            e.preventDefault();
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent('mousedown', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            canvas.dispatchEvent(mouseEvent);
        });

        canvas.addEventListener('touchmove', (e) => {
            e.preventDefault();
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent('mousemove', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            canvas.dispatchEvent(mouseEvent);
        });

        canvas.addEventListener('touchend', (e) => {
            e.preventDefault();
            const mouseEvent = new MouseEvent('mouseup', {});
            canvas.dispatchEvent(mouseEvent);
        });

        function startDrawing(e) {
            isDrawing = true;
            const rect = canvas.getBoundingClientRect();
            ctx.beginPath();
            ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
        }

        function draw(e) {
            if (!isDrawing) return;
            const rect = canvas.getBoundingClientRect();
            ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.stroke();
        }

        function stopDrawing() {
            isDrawing = false;
        }

        document.getElementById('clearSignature').addEventListener('click', () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        });

        // Form Submission
        document.getElementById('additionalChildForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            try {
                // Get signature
                const signatureData = canvas.toDataURL('image/png');
                document.getElementById('signatureData').value = signatureData;

                // Prepare form data
                const formData = new FormData(e.target);
                
                // Handle file upload
                const receiptFile = formData.get('payment_receipt');
                if (receiptFile) {
                    const receiptBase64 = await fileToBase64(receiptFile);
                    formData.set('payment_receipt_base64', receiptBase64);
                }

                // Generate simple PDF (signed form)
                const pdfBase64 = generateSimplePDF(formData);
                formData.set('signed_pdf_base64', pdfBase64);

                // Convert FormData to JSON
                const jsonData = {};
                formData.forEach((value, key) => {
                    if (key !== 'payment_receipt') {
                        jsonData[key] = value;
                    }
                });

                // Submit to backend
                const response = await fetch('process_registration.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(jsonData)
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('success', 'Registration Successful!', 
                        `Child registered successfully! Student ID: ${result.student_id}`);
                    setTimeout(() => {
                        window.location.href = 'index.php?page=dashboard';
                    }, 2000);
                } else {
                    showMessage('danger', 'Registration Failed', result.error || 'Unknown error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Registration';
                }
            } catch (error) {
                showMessage('danger', 'Error', 'An error occurred: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Registration';
            }
        });

        function fileToBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        }

        function generateSimplePDF(formData) {
            // Simple placeholder - in production, generate proper PDF
            const data = {
                child_name: formData.get('name_en'),
                parent: formData.get('parent_name'),
                date: formData.get('form_date')
            };
            return 'data:application/pdf;base64,' + btoa(JSON.stringify(data));
        }

        function showMessage(type, title, message) {
            const container = document.getElementById('messageContainer');
            container.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    <strong>${title}</strong> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }
    </script>
</body>
</html>