# Stage 4: Admin Navigation Update Required

## File: `admin.php`

### Add to Navigation Menu (Sidebar)

Find the sidebar navigation section (around line 520-580) and add this menu item **after Students** and **before Classes**:

```php
<a class="nav-link <?php echo $page === 'parent_accounts' ? 'active' : ''; ?>" href="?page=parent_accounts">
    <i class="fas fa-users"></i>
    <span>Parent Accounts</span>
    <span class="badge bg-success ms-auto">New</span>
</a>
```

### Add to Page Switch Statement

Find the switch statement for page loading (around line 620) and add this case:

```php
case 'parent_accounts':
    if (file_exists($pages_dir . 'parent_accounts.php')) {
        include $pages_dir . 'parent_accounts.php';
    }
    break;
case 'parent_details':
    if (file_exists($pages_dir . 'parent_details.php')) {
        include $pages_dir . 'parent_details.php';
    }
    break;
```

### Update Page Title Icon Mapping

Find the page title icon mapping (around line 600) and add:

```php
($page === 'parent_accounts' ? 'users' : 
($page === 'parent_details' ? 'user' : ...
```

## Navigation Menu Order

Recommended order:
1. Dashboard
2. New Registrations  
3. Students
4. **Parent Accounts** ← NEW
5. Classes
6. Invoices
7. Attendance
8. Logout

## Expected Result

After making these changes:
- "Parent Accounts" will appear in the admin sidebar
- Clicking it will load `admin_pages/parent_accounts.php`
- Shows list of all parent accounts with children count
- Clicking "View" button will navigate to parent details page

## Files Created So Far

✅ `admin_pages/parent_accounts.php` - Parent accounts list page  
⏳ `admin_pages/parent_details.php` - Parent details page (next step)

## Next Steps

After updating admin.php navigation:
1. Test parent accounts page loads
2. Verify DataTable displays correctly
3. Continue to Phase 2: Create parent details page
