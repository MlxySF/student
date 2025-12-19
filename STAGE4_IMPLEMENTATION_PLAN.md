# Stage 4: Admin Portal Updates - Implementation Plan

## Overview
Update the admin portal to support parent account management, allowing admins to view, edit, link/unlink children, and manage family accounts.

## Goals

1. **View Parent Accounts**: List all parent accounts with children count
2. **Parent Details Page**: View full parent information with all children
3. **Link/Unlink Children**: Admin can manually link or unlink children to/from parents
4. **Edit Parent Info**: Update parent contact details
5. **Student Details Enhancement**: Show parent info when viewing student details
6. **Family Reports**: Generate reports grouped by families

## Assumptions About Current Admin Portal

Since we don't have direct access to your admin files, we'll assume:
- Admin portal exists (likely `admin/` folder or `admin.php`)
- Has student management pages
- Uses similar database connection and session management
- Has authentication for admin users

If your admin structure is different, adjustments will be needed.

## Implementation Components

### 1. Parent Accounts List Page
**File:** `admin/pages/parent_accounts.php` or similar

**Features:**
- Table showing all parent accounts
- Columns:
  - Parent ID (PAR-2025-XXXX)
  - Full Name
  - Email
  - Phone
  - Number of Children
  - Total Outstanding Invoices
  - Account Status
  - Actions (View, Edit, Delete)
- Search and filter functionality
- Pagination
- Sort by name, email, children count

**UI Mock:**
```
+----------------------------------------------------------+
| Parent Accounts Management                     [+ Add]   |
+----------------------------------------------------------+
| Search: [____________]  Status: [All v]  [Search Button] |
+----------------------------------------------------------+
| Parent ID   | Name        | Email      | Children | $    |
+----------------------------------------------------------+
| PAR-2025-001| John Doe    | j@ex.com   | 3        | 450  |
| PAR-2025-002| Jane Smith  | jane@...   | 2        | 200  |
+----------------------------------------------------------+
```

### 2. Parent Details Page
**File:** `admin/pages/parent_details.php?id=123`

**Sections:**

#### A. Parent Information Card
- Full Name
- Email (editable)
- Phone (editable)
- IC Number
- Account Status (Active/Inactive dropdown)
- Created Date
- Last Login
- [Edit Parent Info] button

#### B. Children List Card
- Table of all children linked to this parent
- Columns:
  - Student ID
  - Name
  - Age
  - School
  - Status (State Team, etc.)
  - Classes Enrolled
  - Outstanding Invoices
  - Actions (View, Unlink)
- [Link New Child] button

#### C. Family Financial Summary
- Total invoices across all children
- Total paid
- Total outstanding
- Recent payments

#### D. Recent Activity Log
- Login history
- Child registrations
- Payment activities

### 3. Link Child to Parent Modal
**Triggered from:** Parent Details Page

**Features:**
- Search for student by:
  - Student ID
  - Name
  - Email
- Show student details before linking
- Confirmation: "Link [Student Name] to [Parent Name]?"
- Set relationship type:
  - Guardian (default)
  - Father
  - Mother
  - Other
- Set permissions:
  - Can manage payments (checkbox)
  - Can view attendance (checkbox)
  - Is primary guardian (checkbox)

### 4. Unlink Child from Parent
**Triggered from:** Parent Details Page > Children List > Unlink button

**Features:**
- Confirmation modal: "Are you sure you want to unlink [Child] from [Parent]?"
- Options:
  - Keep student account (set as independent)
  - Delete parent-child relationship only
- Warning if child has outstanding invoices

### 5. Enhanced Student Details Page
**File:** `admin/pages/student_details.php?id=123` (existing)

**Add New Section:**
```
+----------------------------------------------------------+
| Parent/Guardian Information                              |
+----------------------------------------------------------+
| Parent Name:    John Doe                                 |
| Parent Email:   john@example.com                         |
| Parent Phone:   012-345 6789                             |
| Parent ID:      PAR-2025-0001                            |
| Relationship:   Guardian                                 |
| Siblings:       2 other children                         |
| [View Parent Details] [Change Parent]                    |
+----------------------------------------------------------+
```

**If No Parent:**
```
+----------------------------------------------------------+
| Parent/Guardian Information                              |
+----------------------------------------------------------+
| This student has no parent account.                      |
| [Link to Parent Account]                                 |
+----------------------------------------------------------+
```

### 6. Registrations List Enhancement
**File:** `admin/pages/registrations.php` (existing)

**Add Column:**
- "Registration Type"
  - Shows: "First Child" or "Additional Child"
  - Color-coded badge
- "Parent Account" (with link to parent details)

### 7. Family Reports
**File:** `admin/pages/reports/family_report.php`

**Features:**
- List families (grouped by parent)
- Show:
  - Family name (parent name)
  - Total children
  - Total monthly fees
  - Total outstanding
  - Average attendance rate (across all children)
- Export to Excel/CSV
- Filter by:
  - Number of children (1, 2, 3+)
  - Total outstanding (>RM500, etc.)
  - Attendance rate

## Database Queries Needed

### Get All Parents with Children Count
```sql
SELECT 
    pa.id,
    pa.parent_id,
    pa.full_name,
    pa.email,
    pa.phone,
    pa.status,
    COUNT(DISTINCT pcr.student_id) as children_count,
    COALESCE(SUM(i.amount), 0) as total_outstanding
FROM parent_accounts pa
LEFT JOIN parent_child_relationships pcr ON pa.id = pcr.parent_id
LEFT JOIN students s ON pcr.student_id = s.id
LEFT JOIN invoices i ON s.id = i.student_id AND i.status IN ('unpaid', 'overdue')
GROUP BY pa.id
ORDER BY pa.created_at DESC
```

### Get Parent with All Children Details
```sql
SELECT 
    pa.*,
    s.id as student_id,
    s.student_id as student_code,
    s.full_name as student_name,
    s.email as student_email,
    s.student_status,
    COUNT(DISTINCT e.id) as classes_count,
    COALESCE(SUM(i.amount), 0) as student_outstanding
FROM parent_accounts pa
LEFT JOIN parent_child_relationships pcr ON pa.id = pcr.parent_id
LEFT JOIN students s ON pcr.student_id = s.id
LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
LEFT JOIN invoices i ON s.id = i.student_id AND i.status IN ('unpaid', 'overdue')
WHERE pa.id = ?
GROUP BY s.id
```

### Search Students for Linking
```sql
SELECT 
    s.*,
    CASE 
        WHEN s.parent_account_id IS NOT NULL THEN 'Linked'
        ELSE 'Independent'
    END as link_status,
    pa.full_name as current_parent_name
FROM students s
LEFT JOIN parent_accounts pa ON s.parent_account_id = pa.id
WHERE (s.student_id LIKE ? OR s.full_name LIKE ? OR s.email LIKE ?)
    AND s.parent_account_id IS NULL -- only show unlinked students
LIMIT 20
```

## API Endpoints (AJAX)

### Link Child to Parent
```php
// admin/api/link_child_to_parent.php
POST /admin/api/link_child_to_parent.php
{
    "parent_id": 123,
    "student_id": 456,
    "relationship": "guardian",
    "is_primary": true,
    "can_manage_payments": true,
    "can_view_attendance": true
}

Response:
{
    "success": true,
    "message": "Child linked successfully"
}
```

### Unlink Child from Parent
```php
// admin/api/unlink_child_from_parent.php
POST /admin/api/unlink_child_from_parent.php
{
    "parent_id": 123,
    "student_id": 456,
    "keep_student_account": true
}

Response:
{
    "success": true,
    "message": "Child unlinked successfully"
}
```

### Update Parent Info
```php
// admin/api/update_parent.php
POST /admin/api/update_parent.php
{
    "parent_id": 123,
    "full_name": "John Doe",
    "email": "john@example.com",
    "phone": "012-345 6789",
    "status": "active"
}

Response:
{
    "success": true,
    "message": "Parent information updated"
}
```

## Admin Navigation Menu Update

**Add New Menu Item:**
```html
<li class="nav-item">
    <a class="nav-link" href="?page=parent_accounts">
        <i class="fas fa-users"></i> Parent Accounts
        <span class="badge bg-primary">New</span>
    </a>
</li>
```

**Update Existing Menu Items:**
- Students → Add parent info badge
- Registrations → Add family grouping
- Reports → Add "Family Reports" submenu

## Implementation Steps (Phased)

### Phase 1: View-Only Features (Quick Win)
1. Create parent accounts list page
2. Create parent details page (read-only)
3. Update student details to show parent info
4. Test with existing data

### Phase 2: Link/Unlink Features
1. Create link child modal
2. Create unlink child confirmation
3. Implement API endpoints
4. Test linking/unlinking

### Phase 3: Edit Features
1. Create edit parent modal
2. Implement update API
3. Add validation
4. Test updates

### Phase 4: Reports
1. Create family reports page
2. Add export functionality
3. Add filters
4. Test with large datasets

## Security Considerations

### Admin Only Access
```php
// Check admin permission
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}
```

### Action Logging
```sql
INSERT INTO admin_action_logs 
(admin_id, action_type, target_type, target_id, details, created_at)
VALUES 
(?, 'link_child', 'parent', ?, 'Linked student ID 456 to parent ID 123', NOW())
```

### Validation
- Prevent linking child to multiple parents
- Prevent unlinking if parent has only 1 child (warn admin)
- Validate parent email uniqueness
- Check for outstanding invoices before unlinking

## UI/UX Guidelines

### Color Coding
- **Parent with 1 child**: Blue badge
- **Parent with 2-3 children**: Green badge
- **Parent with 4+ children**: Purple badge
- **Parent with outstanding >RM500**: Red indicator

### Confirmation Modals
- Always require confirmation for destructive actions
- Show impact warnings (e.g., "Child has 3 unpaid invoices")
- Provide undo option where possible

### Success/Error Messages
- Use toast notifications
- Clear success messages
- Detailed error messages for failures

## Testing Checklist

### Parent Accounts List
- [ ] Shows all parents correctly
- [ ] Children count accurate
- [ ] Search works
- [ ] Pagination works
- [ ] Sort works

### Parent Details
- [ ] Shows correct parent info
- [ ] Lists all children
- [ ] Financial summary accurate
- [ ] Activity log displays

### Link/Unlink
- [ ] Can link independent student to parent
- [ ] Cannot link already-linked student
- [ ] Can unlink child from parent
- [ ] Unlinking converts to independent
- [ ] Relationship data saved correctly

### Student Details Enhancement
- [ ] Shows parent info when linked
- [ ] Shows "no parent" when independent
- [ ] Link to parent details works
- [ ] Can change parent from student page

### Reports
- [ ] Family report shows accurate data
- [ ] Export works
- [ ] Filters work
- [ ] Performance acceptable with large data

## Performance Optimization

### Indexing
```sql
CREATE INDEX idx_parent_id ON parent_child_relationships(parent_id);
CREATE INDEX idx_student_id ON parent_child_relationships(student_id);
CREATE INDEX idx_parent_account_id ON students(parent_account_id);
CREATE INDEX idx_parent_email ON parent_accounts(email);
```

### Caching
- Cache parent children count
- Cache family outstanding totals
- Invalidate cache on link/unlink

## Documentation Needed

1. **Admin User Guide**: How to manage parent accounts
2. **API Documentation**: Endpoints for linking/unlinking
3. **Troubleshooting Guide**: Common issues and fixes

## Expected Deliverables

1. ✅ Parent accounts list page
2. ✅ Parent details page with children
3. ✅ Link child to parent functionality
4. ✅ Unlink child from parent functionality
5. ✅ Enhanced student details with parent info
6. ✅ Family reports page
7. ✅ API endpoints for admin actions
8. ✅ Navigation menu updates
9. ✅ Action logging
10. ✅ Documentation

---

**Stage 4 Status:** Planning Complete, Ready to Implement

**Estimated Time:** 
- Phase 1 (View): 2-3 hours
- Phase 2 (Link/Unlink): 2-3 hours
- Phase 3 (Edit): 1-2 hours
- Phase 4 (Reports): 2-3 hours
- **Total:** 7-11 hours

**Dependencies:**
- Admin portal structure
- Admin authentication system
- Admin UI framework (Bootstrap, etc.)

**Next Step:** Determine admin portal structure and begin Phase 1 implementation.
