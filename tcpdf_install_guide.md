# TCPDF Library Installation Guide

## For Invoice PDF Generation Feature

The invoice PDF download feature requires the TCPDF library. Here are the installation options:

## Option 1: Using Composer (Recommended)

```bash
composer require tecnickcom/tcpdf
```

## Option 2: Manual Installation

1. Download TCPDF from: https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip
2. Extract the contents
3. Create a folder named `tcpdf` in your project root
4. Copy all TCPDF files into the `tcpdf` folder
5. The structure should be: `/tcpdf/tcpdf.php`

## Option 3: CDN/Direct Download

1. Download from: https://sourceforge.net/projects/tcpdf/files/
2. Follow the same steps as Option 2

## Verification

After installation, the `generate_invoice_pdf.php` file will automatically detect TCPDF.

## File Structure

```
your-project/
├── generate_invoice_pdf.php (new)
├── pages/
│   └── invoices.php (updated)
├── vendor/ (if using composer)
│   └── tecnickcom/tcpdf/
└── tcpdf/ (if manual install)
    └── tcpdf.php
```

## Testing

1. Install TCPDF using one of the methods above
2. Login to student portal
3. Go to Invoices page
4. Find a paid invoice
5. Click the "Download Invoice PDF" button
6. PDF should download automatically

## Troubleshooting

If you see an error about TCPDF not found:
1. Check that TCPDF is installed in the correct location
2. Verify file permissions
3. Clear PHP cache if using OPcache
4. Check PHP error logs

## Requirements

- PHP 5.5 or higher
- GD library (usually included with PHP)
- mbstring extension (usually included with PHP)
