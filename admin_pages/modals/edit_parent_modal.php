<!-- Edit Parent Information Modal -->
<div class="modal fade" id="editParentModal" tabindex="-1" aria-labelledby="editParentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editParentModalLabel">
                    <i class="fas fa-edit"></i> Edit Parent Information
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editParentForm" onsubmit="return false;">
                <div class="modal-body">
                    <input type="hidden" id="edit_parent_id" value="">
                    
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">
                            <i class="fas fa-user"></i> Full Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="edit_full_name" 
                               required 
                               maxlength="100"
                               placeholder="Enter full name">
                        <div class="invalid-feedback">Full name is required</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">
                            <i class="fas fa-envelope"></i> Email <span class="text-danger">*</span>
                        </label>
                        <input type="email" 
                               class="form-control" 
                               id="edit_email" 
                               required
                               maxlength="100"
                               placeholder="parent@example.com">
                        <div class="invalid-feedback">Valid email is required</div>
                        <small class="text-muted">Used for parent portal login</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_phone" class="form-label">
                            <i class="fas fa-phone"></i> Phone Number <span class="text-danger">*</span>
                        </label>
                        <input type="tel" 
                               class="form-control" 
                               id="edit_phone" 
                               required
                               maxlength="20"
                               placeholder="012-345-6789">
                        <div class="invalid-feedback">Phone number is required</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_ic_number" class="form-label">
                            <i class="fas fa-id-card"></i> IC Number
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="edit_ic_number"
                               maxlength="20"
                               placeholder="Optional">
                        <small class="text-muted">Optional field</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">
                            <i class="fas fa-toggle-on"></i> Account Status <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="edit_status" required>
                            <option value="active">Active - Can access parent portal</option>
                            <option value="inactive">Inactive - Portal access disabled</option>
                        </select>
                        <small class="text-muted">Inactive accounts cannot login to parent portal</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <small><strong>Note:</strong> Changes will be reflected immediately in the parent portal.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-warning" onclick="saveParentChanges()">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editParent(parentId) {
    // Get parent data from page
    const parentData = <?php echo json_encode($parent ?? []); ?>;
    
    if (!parentData || !parentData.id) {
        alert('Parent data not found');
        return;
    }
    
    // Populate form
    document.getElementById('edit_parent_id').value = parentData.id;
    document.getElementById('edit_full_name').value = parentData.full_name || '';
    document.getElementById('edit_email').value = parentData.email || '';
    document.getElementById('edit_phone').value = parentData.phone || '';
    document.getElementById('edit_ic_number').value = parentData.ic_number || '';
    document.getElementById('edit_status').value = parentData.status || 'active';
    
    // Reset validation
    const form = document.getElementById('editParentForm');
    form.classList.remove('was-validated');
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('editParentModal'));
    modal.show();
}

function saveParentChanges() {
    const form = document.getElementById('editParentForm');
    
    // Validate form
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }
    
    const parentId = document.getElementById('edit_parent_id').value;
    const fullName = document.getElementById('edit_full_name').value.trim();
    const email = document.getElementById('edit_email').value.trim();
    const phone = document.getElementById('edit_phone').value.trim();
    const icNumber = document.getElementById('edit_ic_number').value.trim();
    const status = document.getElementById('edit_status').value;
    
    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address');
        document.getElementById('edit_email').focus();
        return;
    }
    
    if (!confirm('Save changes to parent account?')) {
        return;
    }
    
    // Disable save button
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    // Call API
    fetch('admin_pages/api/update_parent.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            parent_id: parseInt(parentId),
            full_name: fullName,
            email: email,
            phone: phone,
            ic_number: icNumber,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message + '\n\nPage will reload to show updated information.');
            location.reload(); // Reload to show updated info
        } else {
            alert('Error: ' + data.message);
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Update error:', error);
        alert('Failed to update parent information');
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

// Email validation on blur
document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('edit_email');
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (this.value && !emailRegex.test(this.value)) {
                this.setCustomValidity('Invalid email format');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });
    }
});
</script>

<style>
.was-validated .form-control:invalid,
.was-validated .form-select:invalid {
    border-color: #dc3545;
}

.was-validated .form-control:valid,
.was-validated .form-select:valid {
    border-color: #198754;
}

.invalid-feedback {
    display: none;
}

.was-validated .form-control:invalid ~ .invalid-feedback,
.was-validated .form-select:invalid ~ .invalid-feedback {
    display: block;
}
</style>