# Stage 4: Students Page Enhancement Instructions

## File: `admin_pages/students.php`

### Enhancement Overview
Add parent/guardian information display to the student details modal.

## Changes Required

### 1. Modify the `viewStudent()` JavaScript function

Find the `viewStudent()` function (around line 232) and add a parent information column.

Replace the current row structure:

```javascript
let html = `
    <div class="row">
        <div class="col-md-6">
            <h6 class="text-muted mb-3"><i class="fas fa-user"></i> Personal Information</h6>
            // ... existing code ...
        </div>
        <div class="col-md-6">
            <h6 class="text-muted mb-3"><i class="fas fa-chalkboard-teacher"></i> Enrolled Classes</h6>
            // ... existing code ...
        </div>
    </div>
`;
```

With this 3-column structure:

```javascript
let html = `
    <div class="row">
        <div class="col-md-4">
            <h6 class="text-muted mb-3"><i class="fas fa-user"></i> Personal Information</h6>
            <table class="table table-bordered table-sm">
                <tr>
                    <th width="50%">Student ID:</th>
                    <td><strong>${student.student_id}</strong></td>
                </tr>
                <tr>
                    <th>Full Name:</th>
                    <td>${student.full_name}</td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td><small>${student.email}</small></td>
                </tr>
                <tr>
                    <th>Phone:</th>
                    <td>${student.phone}</td>
                </tr>
                <tr>
                    <th>Status:</th>
                    <td>
                        <span class="badge ${student.student_status.includes('State Team') ? 'badge-state-team' : 
                            (student.student_status.includes('Backup Team') ? 'badge-backup-team' : 'badge-student')}">
                            ${student.student_status}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Created:</th>
                    <td><small>${new Date(student.created_at).toLocaleDateString()}</small></td>
                </tr>
            </table>
        </div>
        <div class="col-md-4">
            <h6 class="text-muted mb-3"><i class="fas fa-users"></i> Parent/Guardian</h6>
            ${data.parent ? `
                <div class="card">
                    <div class="card-body">
                        <div class="text-center mb-2">
                            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" 
                                 style="width: 50px; height: 50px; font-size: 20px; font-weight: bold;">
                                ${data.parent.full_name.charAt(0).toUpperCase()}
                            </div>
                        </div>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="text-muted"><i class="fas fa-id-card"></i></td>
                                <td><small><strong>${data.parent.parent_id}</strong></small></td>
                            </tr>
                            <tr>
                                <td class="text-muted"><i class="fas fa-user"></i></td>
                                <td><small>${data.parent.full_name}</small></td>
                            </tr>
                            <tr>
                                <td class="text-muted"><i class="fas fa-envelope"></i></td>
                                <td><small>${data.parent.email}</small></td>
                            </tr>
                            <tr>
                                <td class="text-muted"><i class="fas fa-phone"></i></td>
                                <td><small>${data.parent.phone}</small></td>
                            </tr>
                            ${data.parent.siblings_count > 0 ? `
                            <tr>
                                <td class="text-muted"><i class="fas fa-child"></i></td>
                                <td><small>${data.parent.siblings_count} other child${data.parent.siblings_count > 1 ? 'ren' : ''}</small></td>
                            </tr>
                            ` : ''}
                        </table>
                        <a href="?page=parent_details&id=${data.parent.id}" class="btn btn-sm btn-primary w-100" 
                           onclick="bootstrap.Modal.getInstance(document.getElementById('viewStudentModal')).hide();">
                            <i class="fas fa-eye"></i> View Parent Details
                        </a>
                    </div>
                </div>
            ` : `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <small>This student has no parent account linked.</small>
                    <hr>
                    <button class="btn btn-sm btn-success w-100" onclick="linkToParent(${id})">
                        <i class="fas fa-link"></i> Link to Parent
                    </button>
                </div>
            `}
        </div>
        <div class="col-md-4">
            <h6 class="text-muted mb-3"><i class="fas fa-chalkboard-teacher"></i> Enrolled Classes</h6>
            // ... existing enrollments code ...
        </div>
    </div>
`;
```

### 2. Update `admin_handler.php` to Return Parent Data

Find the `get_student_details` action in `admin_handler.php` and modify it to include parent information:

```php
case 'get_student_details':
    $student_id = $_GET['student_id'];
    
    // Get enrollments
    $enrollStmt = $pdo->prepare("
        SELECT e.*, c.class_name, c.class_code, c.monthly_fee 
        FROM enrollments e
        JOIN classes c ON e.class_id = c.id
        WHERE e.student_id = ? AND e.status = 'active'
        ORDER BY c.class_name
    ");
    $enrollStmt->execute([$student_id]);
    $enrollments = $enrollStmt->fetchAll();
    
    // Get parent information
    $parentStmt = $pdo->prepare("
        SELECT 
            pa.id,
            pa.parent_id,
            pa.full_name,
            pa.email,
            pa.phone,
            pcr.relationship,
            (SELECT COUNT(*) - 1 FROM parent_child_relationships 
             WHERE parent_id = pa.id) as siblings_count
        FROM parent_accounts pa
        INNER JOIN parent_child_relationships pcr ON pa.id = pcr.parent_id
        WHERE pcr.student_id = ?
        LIMIT 1
    ");
    $parentStmt->execute([$student_id]);
    $parent = $parentStmt->fetch();
    
    echo json_encode([
        'enrollments' => $enrollments,
        'parent' => $parent ?: null
    ]);
    exit;
```

### 3. Add Link to Parent Function (Optional for Phase 2)

Add this JavaScript function at the end of the script section:

```javascript
function linkToParent(studentId) {
    // TODO: Implement in Phase 2
    alert('Link to parent feature coming in Phase 2');
}
```

## Expected Result

After making these changes, when viewing a student:

**If Student Has Parent:**
- Shows parent's avatar and name
- Shows parent ID, email, phone
- Shows number of siblings
- "View Parent Details" button to see full parent account

**If Student Has No Parent:**
- Shows warning message
- "Link to Parent" button (Phase 2 feature)

## Visual Layout

```
+------------------+------------------+------------------+
| Personal Info    | Parent/Guardian  | Enrolled Classes |
+------------------+------------------+------------------+
| Student ID       | [Avatar]         | [Class 1]        |
| Name            | Parent Name      | [Class 2]        |
| Email           | Parent ID        | [Class 3]        |
| Phone           | Email            |                  |
| Status          | Phone            | [Enroll Button]  |
| Created         | X other children |                  |
|                 | [View Parent]    |                  |
+------------------+------------------+------------------+
```

## Testing Checklist

- [ ] Modal shows 3 columns properly
- [ ] Parent info displays when student has parent
- [ ] Warning shows when student has no parent
- [ ] "View Parent Details" button navigates correctly
- [ ] Siblings count is accurate
- [ ] Layout is responsive on mobile

## Files Modified

1. `admin_pages/students.php` - Add parent info column to modal
2. `admin_handler.php` - Update get_student_details to include parent data

---

**Status:** Ready to implement
**Phase:** 1 (View-Only Features)
**Step:** 3 of 3
