# Stage 3: Registration System Updates - Implementation Plan

## Overview
Update the registration system to support parent accounts with multiple children. Parents can register multiple children and link them to a single parent account.

## Goals

1. **First Child Registration**: Create parent account + first child
2. **Additional Children**: Link new children to existing parent account
3. **Parent Recognition**: Detect existing parents by email or IC
4. **Backward Compatibility**: Existing independent student registration still works

## Database Changes Required

### Already Available (from Stage 1):
- ✅ `parent_accounts` table
- ✅ `parent_child_relationships` table
- ✅ `students.parent_account_id` column
- ✅ `students.student_type` column
- ✅ `registrations.parent_account_id` column
- ✅ `registrations.registration_type` column

### New Fields Needed in Registrations:
- `is_additional_child` (BOOLEAN) - Indicates if this is 2nd, 3rd child, etc.
- `link_to_parent_email` (VARCHAR) - Parent email to link to

## Registration Flow

### Scenario 1: First Child (New Parent)
**User Journey:**
1. Parent fills registration form
2. Provides parent details (name, email, phone, IC)
3. Provides first child details
4. Submits with payment

**Backend Process:**
```
1. Check if parent email exists in parent_accounts
   - NO: Create new parent account
2. Create student account (child)
3. Link child to parent in parent_child_relationships
4. Set student.parent_account_id
5. Set student.student_type = 'child'
6. Create registration record with parent_account_id
7. Send email to PARENT with:
   - Parent login credentials
   - Child 1 details
```

### Scenario 2: Additional Child (Existing Parent)
**User Journey:**
1. Parent logs into portal OR fills new registration
2. Option 1: "Register Additional Child" button in portal
3. Option 2: Registration form with "I already have an account" checkbox
4. Parent authenticates (if via form)
5. Provides new child details
6. Submits with payment

**Backend Process:**
```
1. Check if parent email exists in parent_accounts
   - YES: Get parent_account_id
2. Create new student account (child 2, 3, etc.)
3. Link new child to parent in parent_child_relationships
4. Set student.parent_account_id
5. Set student.student_type = 'child'
6. Create registration record with parent_account_id
7. Send email to PARENT with:
   - "New child added"
   - New child's details
   - Login using same parent account
```

### Scenario 3: Independent Student (No Parent)
**User Journey:**
1. Student fills registration form (adult or independent student)
2. NO parent details required (optional)
3. Submits with payment

**Backend Process:**
```
1. Create student account
2. Set student.parent_account_id = NULL
3. Set student.student_type = 'independent'
4. Create registration record
5. Send email to STUDENT with:
   - Student login credentials
```

## Implementation Changes

### 1. Registration Form Updates

**Add Parent Account Detection Section:**
```html
<div id="parentAccountSection">
    <h4>Parent Account</h4>
    
    <label>
        <input type="checkbox" id="hasExistingParentAccount" name="has_parent_account">
        I already have a parent account
    </label>
    
    <!-- Show if checkbox checked -->
    <div id="existingParentSection" style="display: none;">
        <label>Parent Email:</label>
        <input type="email" name="parent_email_login" placeholder="Enter your registered parent email">
        
        <label>Parent Password:</label>
        <input type="password" name="parent_password_login" placeholder="Enter your parent password">
        
        <p class="text-muted">This child will be added to your existing parent account.</p>
    </div>
</div>
```

### 2. `process_registration.php` Updates

**Key Changes:**
```php
// NEW: Check for existing parent account
function findOrCreateParentAccount($conn, $parentData, $authenticatePassword = null) {
    $parentEmail = trim($parentData['email']);
    $parentIC = trim($parentData['ic']);
    
    // Check if parent exists by email
    $stmt = $conn->prepare("SELECT id, password FROM parent_accounts WHERE email = ?");
    $stmt->execute([$parentEmail]);
    $existingParent = $stmt->fetch();
    
    if ($existingParent) {
        // Verify password if provided (for additional child registration)
        if ($authenticatePassword) {
            if (!password_verify($authenticatePassword, $existingParent['password'])) {
                throw new Exception("Invalid parent account password.");
            }
        }
        
        return $existingParent['id'];
    }
    
    // Create new parent account
    $parentId = generateParentId($conn);
    $defaultPassword = generatePassword(); // Same format as student password
    $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("
        INSERT INTO parent_accounts 
        (parent_id, full_name, email, phone, ic_number, password, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
    ");
    
    $stmt->execute([
        $parentId,
        $parentData['name'],
        $parentEmail,
        $parentData['phone'],
        $parentIC,
        $hashedPassword
    ]);
    
    $newParentAccountId = $conn->lastInsertId();
    
    // Store generated password for email
    $parentData['generated_password'] = $defaultPassword;
    $parentData['is_new_account'] = true;
    
    return $newParentAccountId;
}

function generateParentId($conn) {
    $year = date('Y');
    $stmt = $conn->query("SELECT COUNT(*) FROM parent_accounts WHERE YEAR(created_at) = $year");
    $count = $stmt->fetchColumn();
    return 'PAR-' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}

// MAIN PROCESS
$hasExistingParentAccount = isset($data['has_parent_account']) && $data['has_parent_account'] === true;
$parentPassword = isset($data['parent_password_login']) ? $data['parent_password_login'] : null;

// Extract parent data
$parentData = [
    'name' => $data['parent_name'],
    'email' => $data['parent_email'] ?? $data['email'], // Use student email if parent email not provided
    'phone' => $data['parent_phone'] ?? $data['phone'],
    'ic' => $data['parent_ic']
];

// Find or create parent account
$parentAccountId = findOrCreateParentAccount($conn, $parentData, $parentPassword);
$isNewParent = isset($parentData['is_new_account']) && $parentData['is_new_account'];

// Create student account
$studentId = $regNumber;
$stmt = $conn->prepare("
    INSERT INTO students 
    (student_id, full_name, email, phone, password, ic_number, date_of_birth, age, school, student_status, 
     parent_account_id, student_type, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'child', NOW())
");

$stmt->execute([
    $studentId,
    $fullName,
    $email, // Child's email
    $phone,
    $hashedPassword,
    $data['ic'],
    $data['date_of_birth'] ?? null,
    $data['age'],
    $data['school'],
    $data['status'],
    $parentAccountId // Link to parent
]);

$studentAccountId = $conn->lastInsertId();

// Create parent-child relationship
$stmt = $conn->prepare("
    INSERT INTO parent_child_relationships 
    (parent_id, student_id, relationship, is_primary, can_manage_payments, can_view_attendance, created_at)
    VALUES (?, ?, 'guardian', TRUE, TRUE, TRUE, NOW())
");
$stmt->execute([$parentAccountId, $studentAccountId]);

// Update registration record
$stmt = $conn->prepare("
    INSERT INTO registrations (..., parent_account_id, registration_type, is_additional_child) 
    VALUES (..., ?, ?, ?)
");
$stmt->execute([..., $parentAccountId, 'parent_managed', !$isNewParent]);

// Send email
if ($isNewParent) {
    sendParentRegistrationEmail($parentData['email'], $parentData, $studentData, $parentData['generated_password']);
} else {
    sendAdditionalChildEmail($parentData['email'], $parentData, $studentData);
}
```

### 3. Email Templates

**New Parent Account Email:**
```
Subject: Welcome to Wushu Sport Academy - Parent Account Created

Dear [Parent Name],

Your parent account has been created! You can now manage your children's information.

Parent Account Credentials:
- Email: [parent@email.com]
- Password: [ABC123def]

Your First Child:
- Name: [Child Name]
- Student ID: WSA2025-0001
- Status: [State Team]

You can:
- View all your children's invoices
- Make payments for any child
- Track attendance records
- View class schedules

Login: https://your-domain.com/student/
```

**Additional Child Email:**
```
Subject: New Child Added - Wushu Sport Academy

Dear [Parent Name],

A new child has been added to your account!

New Child Details:
- Name: [Child Name]
- Student ID: WSA2025-0002
- Status: [Normal Student]

Your existing login credentials remain the same.
Login to view and manage all your children.

Login: https://your-domain.com/student/
```

## Portal Enhancement: "Register Additional Child" Button

### Location: Dashboard (for parents)

```php
<?php if (isParent()): ?>
<div class="card mb-4">
    <div class="card-body text-center">
        <h5>Register Additional Child</h5>
        <p>Add another child to your account</p>
        <a href="register.php?mode=additional" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Register Another Child
        </a>
    </div>
</div>
<?php endif; ?>
```

### New Registration Mode

```php
// register.php?mode=additional
if (isset($_GET['mode']) && $_GET['mode'] === 'additional') {
    // Pre-fill parent information from session
    $parentInfo = getParentInfo(getUserId());
    
    // Show simplified form with only child details
    // Parent info is read-only/hidden
}
```

## Testing Scenarios

### Test 1: New Parent + First Child
- [ ] Parent details entered
- [ ] Child details entered
- [ ] Parent account created
- [ ] Student account created with parent link
- [ ] Parent receives email with parent + child credentials
- [ ] Parent can login and see child

### Test 2: Existing Parent + Additional Child
- [ ] "Register Additional Child" clicked from dashboard
- [ ] Parent auto-authenticated
- [ ] Only child details required
- [ ] New child linked to existing parent
- [ ] Parent receives email about new child
- [ ] Parent sees both children in dashboard

### Test 3: Registration Form with Existing Parent
- [ ] Public registration form accessed
- [ ] "I have an existing account" checked
- [ ] Parent email + password entered
- [ ] Authentication succeeds
- [ ] New child linked to parent
- [ ] Email sent

### Test 4: Independent Student (No Parent)
- [ ] Registration form filled without parent account
- [ ] Student account created as independent
- [ ] `student_type` = 'independent'
- [ ] `parent_account_id` = NULL
- [ ] Student receives own credentials

## Benefits

### For Parents:
- ✅ Single login for all children
- ✅ View all children's data in one place
- ✅ Simplified registration for 2nd, 3rd child
- ✅ Manage all payments centrally

### For Admin:
- ✅ Better family grouping
- ✅ Easier billing (family discounts possible)
- ✅ Contact one parent for multiple children
- ✅ Family statistics and reporting

### For System:
- ✅ Proper parent-child relationships
- ✅ Data integrity maintained
- ✅ Backward compatible
- ✅ Scalable design

## Files to Modify

1. ✅ `process_registration.php` - Main registration logic
2. ✅ `register.php` or registration form - Add parent account detection
3. ✅ `pages/dashboard.php` - Add "Register Additional Child" button (already has summary)
4. ✅ Email templates in `process_registration.php`

## Security Considerations

- ✅ Parent password required for linking additional children
- ✅ Email verification recommended
- ✅ Rate limiting on registration attempts
- ✅ Parent IC cross-check for additional security
- ✅ Audit log for account creation and linking

## Migration for Existing Registrations

For registrations already in database:
```sql
-- Find registrations with same parent email/IC
-- Create parent accounts for them
-- Link students to parents
-- Update registration records
```

This is handled by the Stage 1 migration script already.

---

**Stage 3 Status:** Planning Complete, Ready to Implement
