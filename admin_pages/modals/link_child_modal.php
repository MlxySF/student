<!-- Link Child to Parent Modal -->
<div class="modal fade" id="linkChildModal" tabindex="-1" aria-labelledby="linkChildModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="linkChildModalLabel">
                    <i class="fas fa-user-plus"></i> Link Child to Parent Account
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="link_parent_id" value="">
                
                <!-- Search Section -->
                <div class="mb-4">
                    <label class="form-label"><strong>Search for Student</strong></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" 
                               class="form-control" 
                               id="student_search" 
                               placeholder="Enter student ID, name, email, or phone...">
                        <button class="btn btn-primary" onclick="searchStudents()" type="button">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="exclude_linked" checked>
                        <label class="form-check-label" for="exclude_linked">
                            <small>Show only students without parents</small>
                        </label>
                    </div>
                </div>

                <!-- Search Results -->
                <div id="search_results_container" style="display:none;">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        <span id="search_results_count">0</span> student(s) found
                    </div>
                    <div id="search_results" class="list-group" style="max-height: 300px; overflow-y: auto;">
                        <!-- Results will be populated here -->
                    </div>
                </div>

                <!-- Selected Student Section -->
                <div id="selected_student_container" style="display:none;">
                    <hr>
                    <h6 class="text-success"><i class="fas fa-check-circle"></i> Selected Student</h6>
                    <div class="card border-success">
                        <div class="card-body">
                            <div id="selected_student_info"></div>
                            
                            <hr>
                            
                            <!-- Relationship Settings -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Relationship Type</label>
                                    <select class="form-select" id="relationship_type">
                                        <option value="guardian" selected>Guardian</option>
                                        <option value="father">Father</option>
                                        <option value="mother">Mother</option>
                                        <option value="grandparent">Grandparent</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="is_primary">
                                        <label class="form-check-label" for="is_primary">
                                            <strong>Set as Primary Child</strong><br>
                                            <small class="text-muted">Primary child is listed first</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="replace_parent_warning" style="display:none;">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>This student already has a parent account!</strong>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="replace_parent">
                                        <label class="form-check-label" for="replace_parent">
                                            Replace existing parent with this parent account
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" 
                        class="btn btn-success" 
                        id="link_child_btn" 
                        onclick="confirmLinkChild()" 
                        style="display:none;">
                    <i class="fas fa-link"></i> Link Child to Parent
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedStudent = null;

function linkChild(parentId) {
    // Reset modal
    document.getElementById('link_parent_id').value = parentId;
    document.getElementById('student_search').value = '';
    document.getElementById('search_results_container').style.display = 'none';
    document.getElementById('selected_student_container').style.display = 'none';
    document.getElementById('link_child_btn').style.display = 'none';
    selectedStudent = null;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('linkChildModal'));
    modal.show();
}

function searchStudents() {
    const search = document.getElementById('student_search').value.trim();
    const excludeLinked = document.getElementById('exclude_linked').checked;
    
    if (search.length < 2) {
        alert('Please enter at least 2 characters to search');
        return;
    }
    
    // Show loading
    document.getElementById('search_results').innerHTML = '<div class="text-center p-3"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    document.getElementById('search_results_container').style.display = 'block';
    
    fetch(`admin_pages/api/search_students.php?search=${encodeURIComponent(search)}&exclude_linked=${excludeLinked}&limit=20`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySearchResults(data.students);
                document.getElementById('search_results_count').textContent = data.count;
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            alert('Failed to search students');
        });
}

function displaySearchResults(students) {
    const container = document.getElementById('search_results');
    
    if (students.length === 0) {
        container.innerHTML = '<div class="alert alert-warning">No students found matching your search.</div>';
        return;
    }
    
    let html = '';
    students.forEach(student => {
        const isLinked = student.link_status === 'Linked';
        const statusBadge = student.student_status.includes('State Team') ? 'bg-success' :
                           (student.student_status.includes('Backup Team') ? 'bg-warning' : 'bg-info');
        
        html += `
            <div class="list-group-item list-group-item-action" onclick="selectStudent(${student.id}, '${student.student_id}', '${student.full_name.replace(/'/g, "\\'")}'', ${isLinked}, '${student.parent_name ? student.parent_name.replace(/'/g, "\\'"): ''}')" style="cursor:pointer;">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <strong>${student.full_name}</strong>
                        ${isLinked ? '<span class="badge bg-warning ms-2">Already Linked</span>' : ''}
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-id-card"></i> ${student.student_id} | 
                            <i class="fas fa-envelope"></i> ${student.email}
                        </small>
                        ${isLinked && student.parent_name ? `<br><small class="text-muted"><i class="fas fa-user"></i> Current Parent: ${student.parent_name}</small>` : ''}
                    </div>
                    <div class="text-end">
                        <span class="badge ${statusBadge}">${student.student_status}</span>
                        ${student.outstanding_amount > 0 ? `<br><span class="badge bg-danger mt-1">RM ${parseFloat(student.outstanding_amount).toFixed(2)} due</span>` : ''}
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function selectStudent(id, studentId, fullName, isLinked, currentParent) {
    selectedStudent = { id, studentId, fullName, isLinked, currentParent };
    
    // Update selected student display
    document.getElementById('selected_student_info').innerHTML = `
        <div class="d-flex align-items-center">
            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-3" 
                 style="width: 50px; height: 50px; font-size: 20px; font-weight: bold;">
                ${fullName.charAt(0).toUpperCase()}
            </div>
            <div>
                <h5 class="mb-0">${fullName}</h5>
                <small class="text-muted">${studentId}</small>
            </div>
        </div>
    `;
    
    // Show/hide replace parent warning
    if (isLinked) {
        document.getElementById('replace_parent_warning').style.display = 'block';
        document.querySelector('#replace_parent_warning').innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>This student is already linked to parent: ${currentParent}</strong>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="replace_parent">
                    <label class="form-check-label" for="replace_parent">
                        Replace existing parent with this parent account
                    </label>
                </div>
            </div>
        `;
    } else {
        document.getElementById('replace_parent_warning').style.display = 'none';
    }
    
    document.getElementById('selected_student_container').style.display = 'block';
    document.getElementById('link_child_btn').style.display = 'inline-block';
}

function confirmLinkChild() {
    if (!selectedStudent) {
        alert('Please select a student');
        return;
    }
    
    const parentId = document.getElementById('link_parent_id').value;
    const relationship = document.getElementById('relationship_type').value;
    const isPrimary = document.getElementById('is_primary').checked;
    const replaceParent = selectedStudent.isLinked ? document.getElementById('replace_parent').checked : false;
    
    if (selectedStudent.isLinked && !replaceParent) {
        alert('This student is already linked to a parent. Please check "Replace existing parent" to proceed.');
        return;
    }
    
    if (!confirm(`Link ${selectedStudent.fullName} to this parent account?`)) {
        return;
    }
    
    // Disable button
    const btn = document.getElementById('link_child_btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Linking...';
    
    // Call API
    fetch('admin_pages/api/link_child.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            parent_id: parseInt(parentId),
            student_id: selectedStudent.id,
            relationship: relationship,
            is_primary: isPrimary,
            replace_parent: replaceParent
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload(); // Reload page to show updated children list
        } else {
            alert('Error: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-link"></i> Link Child to Parent';
        }
    })
    .catch(error => {
        console.error('Link error:', error);
        alert('Failed to link child to parent');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-link"></i> Link Child to Parent';
    });
}

// Allow Enter key to trigger search
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('student_search');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchStudents();
            }
        });
    }
});
</script>