/**
 * admin.js - Reusable bulk delete functionality for admin tables
 * Usage: Call initBulkDelete(tableName) to enable bulk delete on any table
 */

function initBulkDelete(tableName) {
    let bulkDeleteEnabled = false;
    const selectBtn = document.getElementById('bulkSelectBtn-' + tableName);
    const bulkActions = document.getElementById('bulkActions-' + tableName);
    const selectAllCheckbox = document.getElementById('selectAll-' + tableName);
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn-' + tableName);
    const checkboxes = document.querySelectorAll('.bulk-checkbox-' + tableName);
    
    if (!selectBtn || !bulkActions) {
        console.warn('Bulk delete elements not found for table:', tableName);
        return;
    }
    
    // Toggle bulk selection mode
    selectBtn.addEventListener('click', function() {
        bulkDeleteEnabled = !bulkDeleteEnabled;
        
        if (bulkDeleteEnabled) {
            selectBtn.innerHTML = '<i class="fas fa-times"></i> Cancel Selection';
            selectBtn.classList.remove('btn-primary');
            selectBtn.classList.add('btn-secondary');
            bulkActions.style.display = 'block';
            
            // Show checkboxes
            checkboxes.forEach(cb => {
                cb.style.display = 'inline-block';
            });
            if (selectAllCheckbox) selectAllCheckbox.style.display = 'inline-block';
        } else {
            selectBtn.innerHTML = '<i class="fas fa-check-square"></i> Select';
            selectBtn.classList.remove('btn-secondary');
            selectBtn.classList.add('btn-primary');
            bulkActions.style.display = 'none';
            
            // Hide and uncheck all checkboxes
            checkboxes.forEach(cb => {
                cb.style.display = 'none';
                cb.checked = false;
            });
            if (selectAllCheckbox) {
                selectAllCheckbox.style.display = 'none';
                selectAllCheckbox.checked = false;
            }
            
            updateBulkDeleteButton();
        }
    });
    
    // Select all functionality
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            updateBulkDeleteButton();
        });
    }
    
    // Update button when individual checkboxes change
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkDeleteButton);
    });
    
    // Update bulk delete button text with count
    function updateBulkDeleteButton() {
        const checkedCount = document.querySelectorAll('.bulk-checkbox-' + tableName + ':checked').length;
        
        if (checkedCount > 0) {
            bulkDeleteBtn.disabled = false;
            bulkDeleteBtn.innerHTML = `<i class="fas fa-trash-alt"></i> Delete Selected (${checkedCount})`;
        } else {
            bulkDeleteBtn.disabled = true;
            bulkDeleteBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Delete Selected';
        }
    }
    
    // Bulk delete action
    bulkDeleteBtn.addEventListener('click', function() {
        const checkedBoxes = document.querySelectorAll('.bulk-checkbox-' + tableName + ':checked');
        const ids = Array.from(checkedBoxes).map(cb => parseInt(cb.value));
        
        if (ids.length === 0) {
            alert('Please select at least one item to delete.');
            return;
        }
        
        const confirmMsg = `Are you sure you want to delete ${ids.length} selected item(s)?\n\nThis action cannot be undone and will also delete all related data!`;
        
        if (!confirm(confirmMsg)) {
            return;
        }
        
        // Disable button and show loading
        bulkDeleteBtn.disabled = true;
        bulkDeleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
        
        // Send AJAX request
        fetch('admin_bulk_delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                table: tableName,
                ids: ids
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Success!\n\nDeleted: ${data.deleted_count} items\nFailed: ${data.error_count} items\n\n` + 
                      (data.errors.length > 0 ? 'Errors:\n' + data.errors.join('\n') : ''));
                
                // Reload page to reflect changes
                window.location.reload();
            } else {
                alert('Error: ' + data.error);
                bulkDeleteBtn.disabled = false;
                updateBulkDeleteButton();
            }
        })
        .catch(error => {
            console.error('Bulk delete error:', error);
            alert('An error occurred while deleting items. Please try again.');
            bulkDeleteBtn.disabled = false;
            updateBulkDeleteButton();
        });
    });
    
    // Initial state
    bulkActions.style.display = 'none';
    checkboxes.forEach(cb => {
        cb.style.display = 'none';
    });
    if (selectAllCheckbox) selectAllCheckbox.style.display = 'none';
}

// Auto-initialize on page load for any tables that have bulk delete setup
document.addEventListener('DOMContentLoaded', function() {
    // Detect which tables have bulk delete initialized
    const tables = ['registrations', 'students', 'classes', 'invoices', 'attendance'];
    
    tables.forEach(tableName => {
        if (document.getElementById('bulkSelectBtn-' + tableName)) {
            initBulkDelete(tableName);
        }
    });
});
