# Stage 4 Phase 2: Link/Unlink Children Features - COMPLETE! ✅

## Overview
Phase 2 adds interactive functionality for admins to link children to parent accounts and unlink them when needed.

## Files Created

### API Endpoints

1. **[`admin_pages/api/link_child.php`](https://github.com/MlxySF/student/blob/main/admin_pages/api/link_child.php)**
   - Links a student to a parent account
   - Creates parent_child_relationship record
   - Updates student's parent_account_id
   - Validates parent and student exist
   - Checks for existing parent links
   - Option to replace existing parent
   - Sets primary child flag
   - Logs admin action
   - **Route:** `POST admin_pages/api/link_child.php`

2. **[`admin_pages/api/unlink_child.php`](https://github.com/MlxySF/student/blob/main/admin_pages/api/unlink_child.php)**
   - Unlinks a student from parent account
   - Removes parent_child_relationship
   - Sets student's parent_account_id to NULL
   - Checks for outstanding invoices (warning)
   - Logs admin action
   - **Route:** `POST admin_pages/api/unlink_child.php`

3. **[`admin_pages/api/search_students.php`](https://github.com/MlxySF/student/blob/main/admin_pages/api/search_students.php)**
   - Searches students by ID, name, email, phone
   - Returns student details with parent status
   - Option to exclude already-linked students
   - Shows enrollment count and outstanding invoices
   - **Route:** `GET admin_pages/api/search_students.php?search={query}`

### UI Components

4. **[`admin_pages/modals/link_child_modal.php`](https://github.com/MlxySF/student/blob/main/admin_pages/modals/link_child_modal.php)**
   - Complete modal for linking children
   - Student search with live results
   - Student selection interface
   - Relationship type selector (Guardian, Father, Mother, etc.)
   - Primary child checkbox
   - Replace existing parent option
   - Visual confirmation before linking
   - Success/error handling

## Integration Instructions

### Step 1: Include Link Child Modal in parent_details.php

At the **bottom** of `admin_pages/parent_details.php`, **before** the closing `</script>` tag, add:

```php
<?php include 'modals/link_child_modal.php'; ?>
```

### Step 2: Update unlinkChild() Function in parent_details.php

Replace the existing `unlinkChild()` function (around line 280) with:

```javascript
function unlinkChild(parentId, studentId, studentName) {
    if (!confirm('Are you sure you want to unlink ' + studentName + ' from this parent account?\n\nThe student will become an independent account.')) {
        return;
    }
    
    // Call API
    fetch('admin_pages/api/unlink_child.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            parent_id: parentId,
            student_id: studentId,
            keep_student: true
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload(); // Reload to show updated list
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Unlink error:', error);
        alert('Failed to unlink child from parent');
    });
}
```

### Step 3: Remove Placeholder Functions from parent_details.php

Remove these placeholder functions (they're replaced by the modal):

```javascript
// DELETE THESE:
function editParent(parentId) {
    alert('Edit parent feature coming in Phase 3');
}

function linkChild(parentId) {
    alert('Link child feature coming in Phase 2');
}
```

**Note:** `linkChild()` is now defined in `link_child_modal.php`

### Step 4: Create admin_action_logs Table (if not exists)

Run this SQL to create the admin action logging table:

```sql
CREATE TABLE IF NOT EXISTS admin_action_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    target_type VARCHAR(50) NOT NULL,
    target_id INT NOT NULL,
    details TEXT,
    created_at DATETIME NOT NULL,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at)
);
```

## Features Added

### Link Child to Parent

**Workflow:**
1. Admin clicks "Link New Child" button on parent details page
2. Modal opens with search interface
3. Admin searches for student by ID, name, email, or phone
4. Search results show:
   - Student details
   - Current link status (Linked/Independent)
   - Current parent (if linked)
   - Outstanding invoices
   - Student status badge
5. Admin selects student from results
6. Selected student card appears with options:
   - Relationship type dropdown
   - Primary child checkbox
   - Replace parent checkbox (if already linked)
7. Admin clicks "Link Child to Parent"
8. Confirmation dialog
9. API creates relationship
10. Page reloads showing updated children list

**Validation:**
- ✅ Parent must exist
- ✅ Student must exist
- ✅ Prevents duplicate links (unless replace_parent = true)
- ✅ Handles primary child designation
- ✅ Logs all actions

### Unlink Child from Parent

**Workflow:**
1. Admin clicks unlink button (🔗 icon) next to child
2. Confirmation dialog with student name
3. API removes relationship
4. Student becomes independent
5. Outstanding invoice warning shown (if any)
6. Page reloads showing updated list

**Validation:**
- ✅ Checks for outstanding invoices (warns but allows)
- ✅ Removes relationship record
- ✅ Updates student's parent_account_id to NULL
- ✅ Logs all actions

### Search Students

**Features:**
- Search by multiple fields (ID, name, email, phone)
- Fuzzy matching with LIKE queries
- Optional filter: show only unlinked students
- Returns up to 20 results (configurable)
- Shows student status badges
- Shows outstanding invoice amounts
- Shows current parent (if linked)

## API Request/Response Examples

### Link Child

**Request:**
```json
POST /admin_pages/api/link_child.php
Content-Type: application/json

{
    "parent_id": 5,
    "student_id": 23,
    "relationship": "guardian",
    "is_primary": true,
    "replace_parent": false
}
```

**Response (Success):**
```json
{
    "success": true,
    "message": "Child successfully linked to parent account",
    "data": {
        "parent_name": "John Doe",
        "student_name": "Jane Doe"
    }
}
```

**Response (Error - Already Linked):**
```json
{
    "success": false,
    "message": "Student is already linked to a parent. Enable 'Replace Parent' to proceed."
}
```

### Unlink Child

**Request:**
```json
POST /admin_pages/api/unlink_child.php
Content-Type: application/json

{
    "parent_id": 5,
    "student_id": 23,
    "keep_student": true
}
```

**Response (Success with Outstanding):**
```json
{
    "success": true,
    "message": "Child successfully unlinked from parent account. Note: Student has 3 unpaid invoice(s) totaling RM 450.00",
    "data": {
        "parent_name": "John Doe",
        "student_name": "Jane Doe",
        "had_outstanding": true,
        "outstanding_amount": 450.00
    }
}
```

### Search Students

**Request:**
```
GET /admin_pages/api/search_students.php?search=john&exclude_linked=true&limit=20
```

**Response:**
```json
{
    "success": true,
    "students": [
        {
            "id": 23,
            "student_id": "STU-2025-023",
            "full_name": "John Smith",
            "email": "john@example.com",
            "phone": "012-345-6789",
            "age": 12,
            "school": "SJK(C) Example",
            "student_status": "Normal Student",
            "parent_account_id": null,
            "parent_code": null,
            "parent_name": null,
            "link_status": "Independent",
            "enrollments_count": 2,
            "unpaid_invoices": 1,
            "outstanding_amount": 150.00
        }
    ],
    "count": 1
}
```

## Security Features

- ✅ Session-based admin authentication required
- ✅ POST method validation
- ✅ SQL injection prevention (prepared statements)
- ✅ Transaction support (rollback on error)
- ✅ Action logging for audit trail
- ✅ Input validation and sanitization

## Testing Checklist

### Link Child Tests
- [ ] Search for students by ID
- [ ] Search for students by name
- [ ] Search with "exclude linked" enabled
- [ ] Search with "exclude linked" disabled
- [ ] Select student from results
- [ ] Link student with Guardian relationship
- [ ] Link student with Father/Mother relationship
- [ ] Set student as primary child
- [ ] Try to link already-linked student (should warn)
- [ ] Replace existing parent (check replace_parent)
- [ ] Verify relationship appears in parent_details
- [ ] Check admin_action_logs for entry

### Unlink Child Tests
- [ ] Unlink child with no outstanding invoices
- [ ] Unlink child with outstanding invoices (check warning)
- [ ] Verify child removed from parent_details
- [ ] Verify student's parent_account_id is NULL
- [ ] Check admin_action_logs for entry
- [ ] Reload parent_details page (should not show unlinked child)

### Edge Cases
- [ ] Link non-existent student ID
- [ ] Link to non-existent parent ID
- [ ] Unlink child that's not linked
- [ ] Search with special characters
- [ ] Search with empty string
- [ ] Handle API errors gracefully

## Known Limitations

1. **Search Limit:** Maximum 20 students returned (adjustable in code)
2. **No Bulk Operations:** Must link/unlink one at a time
3. **No Undo:** Unlinking is permanent (can re-link manually)
4. **Invoice Warning Only:** Unlinking doesn't prevent if outstanding invoices exist

## Next Steps

**Phase 3 will add:**
- Edit parent information modal
- Update parent email, phone, status
- Admin can change parent details

**Phase 4 will add:**
- Family reports page
- Export family data to Excel
- Financial reports by family

---

## Stage 4 Overall Progress

| Phase | Status | Description |
|-------|--------|-------------|
| **Phase 1** | ✅ 100% | View-only features (parent list, details) |
| **Phase 2** | ✅ 100% | Link/Unlink children functionality |
| **Phase 3** | 🔲 0% | Edit parent information |
| **Phase 4** | 🔲 0% | Family reports |

**Total Stage 4 Progress: 50%** 🎉

---

**Status:** Phase 2 Complete, Ready for Integration
**Estimated Integration Time:** 15-20 minutes
**Dependencies:** Phase 1 must be integrated first
