# Global Loading Overlay System - Implementation Guide

## Overview
Automatic loading overlay system that prevents double-submissions and provides visual feedback for all form submissions.

## Features
- Automatic form detection
- Full-screen overlay with spinner
- Button disabling to prevent double-clicks
- Customizable loading messages
- Mobile responsive
- AJAX compatible
- 30-second auto-timeout
- Browser back button handling

## Installation

Add this line before closing `</body>` tag in:
- `index.php` (Student Portal)
- `admin.php` (Admin Panel)

```html
<script src="global-loading.js"></script>
```

## Usage

### Basic Form
```html
<form method="POST" action="process.php" data-loading="true">
    <button type="submit">Submit</button>
</form>
```

### Custom Message
```html
<form method="POST" 
      data-loading="true" 
      data-loading-message="Processing payment...">
    <button type="submit">Pay Now</button>
</form>
```

### Individual Button
```html
<button data-loading="true" 
        data-loading-message="Downloading..." 
        onclick="download()">
    Download
</button>
```

## Examples

### Payment Form
```html
<form method="POST" 
      enctype="multipart/form-data"
      data-loading="true" 
      data-loading-message="Processing payment...">
    <input type="file" name="receipt" required>
    <button type="submit">Submit Payment</button>
</form>
```

### Profile Update
```html
<form method="POST" 
      data-loading="true" 
      data-loading-message="Updating profile...">
    <input type="text" name="name" required>
    <button type="submit">Save Changes</button>
</form>
```

## Manual Control (AJAX)

```javascript
// Show loading
GlobalLoading.show('Processing...');

// Hide loading
GlobalLoading.hide();

// Reset form
GlobalLoading.reset('#myForm');

// Refresh after dynamic content
GlobalLoading.refresh();
```

## Files to Update

### Student Portal
- `pages/invoices.php` - Payment forms
- `pages/profile.php` - Update forms
- `pages/register.php` - Registration forms

### Admin Panel
- `admin_pages/invoices.php` - Invoice forms
- `admin_pages/students.php` - Student forms
- `admin_pages/classes.php` - Class forms
- `admin_pages/registrations.php` - Approval forms

## Testing Checklist

1. Submit form -> overlay appears
2. Button disabled during submit
3. Button text changes to "Processing..."
4. Double-click prevention works
5. Mobile display correct
6. Back button clears overlay

## Troubleshooting

**Loading doesn't appear?**
- Add `data-loading="true"` to form
- Check if script is included
- Check console for errors

**Loading doesn't disappear?**
- Call `GlobalLoading.hide()` for AJAX
- Check page redirects properly
- Verify no JS errors

**Button stays disabled?**
- Use `GlobalLoading.reset('#form')` in error handler
- Wait for 30-second timeout
- Check error handling code

## Browser Support
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers

## Quick Start

1. Include `global-loading.js` in your page
2. Add `data-loading="true"` to your forms
3. Test submission
4. Done!

---

**Created:** 2025-12-22
