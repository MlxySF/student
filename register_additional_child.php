<?php
/**
 * register_additional_child.php
 * Stage 3: Simplified registration form for parents to add additional children
 * Only available to logged-in parent users
 */

session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth_helper.php';

// Check if user is logged in as parent
if (!isLoggedIn() || !isParent()) {
    header('Location: index.php?error=access_denied');
    exit;
}

// Get parent info
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
$childrenCount = $stmt->fetch()['count'];

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body text-center py-4" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: white; border-radius: 8px;">
                    <i class="fas fa-user-plus fa-3x mb-3"></i>
                    <h2 class="mb-2">Register Additional Child</h2>
                    <p class="mb-0">Add another child to your parent account</p>
                </div>
            </div>

            <!-- Parent Info Card -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-user"></i> Parent Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <strong>Parent Name:</strong><br>
                            <?php echo htmlspecialchars($parentInfo['full_name']); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Parent Email:</strong><br>
                            <?php echo htmlspecialchars($parentInfo['email']); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Parent Phone:</strong><br>
                            <?php echo htmlspecialchars($parentInfo['phone']); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Existing Children:</strong><br>
                            <span class="badge bg-primary"><?php echo $childrenCount; ?> child(ren)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registration Form -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-child"></i> New Child Information</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> This child will be automatically linked to your parent account. 
                        Fill in the child's information below and submit the registration with payment proof.
                    </div>

                    <form id="additionalChildForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="mode" value="additional_child">
                        <input type="hidden" name="parent_id" value="<?php echo $parentId; ?>">
                        <input type="hidden" name="parent_email" value="<?php echo htmlspecialchars($parentInfo['email']); ?>">
                        <input type="hidden" name="parent_name" value="<?php echo htmlspecialchars($parentInfo['full_name']); ?>">
                        <input type="hidden" name="parent_phone" value="<?php echo htmlspecialchars($parentInfo['phone']); ?>">
                        <input type="hidden" name="parent_ic" value="<?php echo htmlspecialchars($parentInfo['ic_number']); ?>">

                        <!-- Child's Full Name -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-user"></i> Child's Full Name (English) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name_en" required placeholder="e.g. John Tan Wei Ming">
                        </div>

                        <!-- Child's Chinese Name (Optional) -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-language"></i> Child's Chinese Name (Optional)</label>
                            <input type="text" class="form-control" name="name_cn" placeholder="e.g. 陈伟明">
                        </div>

                        <!-- Child's IC Number -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-id-card"></i> Child's IC/Passport Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="ic" required placeholder="e.g. 120101-01-1234">
                        </div>

                        <div class="row">
                            <!-- Age -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-birthday-cake"></i> Age <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="age" required min="5" max="18" placeholder="e.g. 10">
                            </div>

                            <!-- Date of Birth -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-calendar"></i> Date of Birth <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="date_of_birth" required>
                            </div>
                        </div>

                        <!-- School -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-school"></i> School <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="school" required placeholder="e.g. SJKC Puchong">
                        </div>

                        <!-- Student Status -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-user-tag"></i> Student Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="">-- Select Status --</option>
                                <option value="Normal Student 普通学员">Normal Student 普通学员</option>
                                <option value="State Team 州队">State Team 州队</option>
                                <option value="Backup Team 后备队">Backup Team 后备队</option>
                            </select>
                        </div>

                        <!-- Contact Info -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-envelope"></i> Child's Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" required placeholder="child@email.com">
                                <small class="text-muted">Child's login email (must be unique)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-phone"></i> Child's Phone</label>
                                <input type="text" class="form-control" name="phone" placeholder="012-345-6789">
                            </div>
                        </div>

                        <!-- Events Participating -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-trophy"></i> Events Participating <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="events" required placeholder="e.g. Changquan, Nanquan">
                        </div>

                        <!-- Training Schedule -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-calendar-alt"></i> Preferred Training Schedule <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="schedule" required placeholder="e.g. Monday & Wednesday 7-9pm">
                        </div>

                        <!-- Number of Classes -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-list-ol"></i> Number of Classes per Week <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="class_count" required min="1" max="7" value="2">
                        </div>

                        <!-- Payment Information -->
                        <hr class="my-4">
                        <h5 class="mb-3"><i class="fas fa-credit-card"></i> Payment Information</h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Payment Amount (RM) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="payment_amount" required min="0" step="0.01" placeholder="e.g. 150.00">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="payment_date" required>
                            </div>
                        </div>

                        <!-- Payment Receipt -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-receipt"></i> Payment Receipt <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" name="payment_receipt" required accept="image/*,.pdf">
                            <small class="text-muted">Upload payment proof (Image or PDF)</small>
                        </div>

                        <!-- Form Date and Signature Placeholders -->
                        <input type="hidden" name="form_date" id="form_date">
                        <input type="hidden" name="signature_base64" value="parent_signed">
                        <input type="hidden" name="signed_pdf_base64" value="auto_generated">

                        <!-- Submit Button -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane"></i> Submit Registration
                            </button>
                            <a href="index.php?page=dashboard" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Help Text -->
            <div class="card mt-4 bg-light border-0">
                <div class="card-body">
                    <h6><i class="fas fa-question-circle"></i> Need Help?</h6>
                    <p class="mb-0 small text-muted">
                        If you encounter any issues, please contact admin at <strong>admin@wushusportacademy.com</strong> or call <strong>+60 12-345 6789</strong>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Set form date to today
document.getElementById('form_date').value = new Date().toISOString().split('T')[0];

// Form submission handler
document.getElementById('additionalChildForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type=submit]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    
    try {
        const formData = new FormData(this);
        
        // Convert payment receipt to base64
        const receiptFile = formData.get('payment_receipt');
        if (receiptFile && receiptFile.size > 0) {
            const receiptBase64 = await fileToBase64(receiptFile);
            formData.delete('payment_receipt');
            
            // Prepare JSON data for API
            const jsonData = {
                mode: 'additional_child',
                has_parent_account: true,
                parent_email: formData.get('parent_email'),
                parent_name: formData.get('parent_name'),
                parent_phone: formData.get('parent_phone'),
                parent_ic: formData.get('parent_ic'),
                name_en: formData.get('name_en'),
                name_cn: formData.get('name_cn') || '',
                ic: formData.get('ic'),
                age: parseInt(formData.get('age')),
                date_of_birth: formData.get('date_of_birth'),
                school: formData.get('school'),
                status: formData.get('status'),
                email: formData.get('email'),
                phone: formData.get('phone') || formData.get('parent_phone'),
                events: formData.get('events'),
                schedule: formData.get('schedule'),
                class_count: parseInt(formData.get('class_count')),
                payment_amount: parseFloat(formData.get('payment_amount')),
                payment_date: formData.get('payment_date'),
                payment_receipt_base64: receiptBase64,
                form_date: formData.get('form_date'),
                signature_base64: formData.get('signature_base64'),
                signed_pdf_base64: formData.get('signed_pdf_base64')
            };
            
            const response = await fetch('process_registration.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(jsonData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('✅ Success! Child registration submitted successfully.\n\nRegistration Number: ' + result.registration_number + '\nStudent ID: ' + result.student_id + '\n\nThe child has been linked to your parent account. Please check your email for login credentials.');
                window.location.href = 'index.php?page=dashboard';
            } else {
                throw new Error(result.error || 'Registration failed');
            }
        } else {
            throw new Error('Please upload payment receipt');
        }
    } catch (error) {
        alert('❌ Error: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});

// Helper: Convert file to base64
function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
