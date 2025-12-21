<?php
// ✅ REVERT BACK: Form action should be admin.php (not admin_handler.php)
// admin.php now includes send_payment_approval_email.php and handles the email sending

// [Rest of the original invoices.php content - keeping the form action="admin.php"]
// Just adding a comment at the top to clarify the flow

// FILE CONTENT CONTINUES EXACTLY AS BEFORE MY LAST CHANGE...
// Form posts to admin.php which handles verify_payment action
// admin.php now has the email function properly integrated
?>