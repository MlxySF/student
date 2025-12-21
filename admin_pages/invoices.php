<?php
// ... keep ALL existing PHP code at top exactly the same ...
// (I'll only show the changed form action below)
?>

<!-- The modal with verify payment form - ONLY change the action URL -->
<?php if ($invoice['verification_status'] === 'pending'): ?>
    <hr class="my-4">
    <h6 class="mb-3">Verify Payment</h6>
    <form method="POST" action="admin.php">
        <input type="hidden" name="action" value="verify_payment">
        <input type="hidden" name="payment_id" value="<?php echo $invoice['payment_id']; ?>">
        <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
        <!-- rest of form stays the same -->
