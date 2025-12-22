/**
 * Global Loading Overlay System
 * Automatically adds loading states to all forms with submit buttons
 * Prevents double-submission and provides visual feedback
 * 
 * Features:
 * - Automatic detection of all forms with data-loading attribute
 * - Disables submit buttons during processing
 * - Shows full-screen loading overlay
 * - Prevents multiple submissions
 * - Works with AJAX and regular form submissions
 * 
 * Usage:
 * 1. Add data-loading="true" to any form
 * 2. Optionally add data-loading-message="Custom message" for custom text
 * 3. Script will automatically handle the rest
 * 
 * Example:
 * <form method="POST" data-loading="true" data-loading-message="Processing payment...">
 *   <button type="submit">Submit Payment</button>
 * </form>
 */

(function() {
    'use strict';
    
    // Create loading overlay HTML
    const loadingOverlayHTML = `
        <div id="globalLoadingOverlay" class="global-loading-overlay" style="display: none;">
            <div class="global-loading-content">
                <div class="global-loading-spinner"></div>
                <div class="global-loading-text">Processing...</div>
                <div class="global-loading-subtext">Please do not close this window or press back button</div>
            </div>
        </div>
    `;
    
    // Create loading overlay CSS
    const loadingOverlayCSS = `
        <style id="globalLoadingStyles">
            .global-loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.85);
                z-index: 999999;
                display: flex;
                align-items: center;
                justify-content: center;
                backdrop-filter: blur(4px);
                -webkit-backdrop-filter: blur(4px);
            }
            
            .global-loading-content {
                text-align: center;
                padding: 40px;
                background: white;
                border-radius: 20px;
                box-shadow: 0 10px 50px rgba(0, 0, 0, 0.3);
                max-width: 400px;
                animation: slideIn 0.3s ease-out;
            }
            
            @keyframes slideIn {
                from {
                    transform: translateY(-30px);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
            
            .global-loading-spinner {
                width: 60px;
                height: 60px;
                margin: 0 auto 20px;
                border: 4px solid #f3f3f3;
                border-top: 4px solid #2563eb;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .global-loading-text {
                font-size: 20px;
                font-weight: 700;
                color: #1e293b;
                margin-bottom: 10px;
            }
            
            .global-loading-subtext {
                font-size: 14px;
                color: #64748b;
                margin-top: 10px;
            }
            
            /* Button loading state */
            .btn-loading {
                position: relative;
                pointer-events: none;
                opacity: 0.7;
            }
            
            .btn-loading::after {
                content: '';
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
                width: 16px;
                height: 16px;
                border: 2px solid rgba(255, 255, 255, 0.3);
                border-top-color: white;
                border-radius: 50%;
                animation: spin 0.8s linear infinite;
            }
            
            /* Mobile responsive */
            @media (max-width: 480px) {
                .global-loading-content {
                    margin: 20px;
                    padding: 30px 20px;
                    max-width: calc(100% - 40px);
                }
                
                .global-loading-spinner {
                    width: 50px;
                    height: 50px;
                }
                
                .global-loading-text {
                    font-size: 18px;
                }
                
                .global-loading-subtext {
                    font-size: 12px;
                }
            }
        </style>
    `;
    
    // Initialize on DOM ready
    function init() {
        // Add CSS to head
        if (!document.getElementById('globalLoadingStyles')) {
            document.head.insertAdjacentHTML('beforeend', loadingOverlayCSS);
        }
        
        // Add overlay to body
        if (!document.getElementById('globalLoadingOverlay')) {
            document.body.insertAdjacentHTML('beforeend', loadingOverlayHTML);
        }
        
        // Find all forms with data-loading attribute
        const forms = document.querySelectorAll('form[data-loading="true"]');
        
        forms.forEach(form => {
            // Prevent multiple event listeners
            if (form.dataset.loadingInitialized) {
                return;
            }
            form.dataset.loadingInitialized = 'true';
            
            // Add submit event listener
            form.addEventListener('submit', function(e) {
                // Check if form is already submitting
                if (form.dataset.submitting === 'true') {
                    e.preventDefault();
                    return false;
                }
                
                // Get custom message if provided
                const customMessage = form.dataset.loadingMessage || 'Processing...';
                
                // Mark as submitting
                form.dataset.submitting = 'true';
                
                // Show loading overlay
                showLoading(customMessage);
                
                // Disable all submit buttons in the form
                const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                submitButtons.forEach(btn => {
                    btn.disabled = true;
                    btn.classList.add('btn-loading');
                    
                    // Store original text
                    if (!btn.dataset.originalText) {
                        btn.dataset.originalText = btn.textContent || btn.value;
                    }
                    
                    // Change button text
                    if (btn.tagName === 'BUTTON') {
                        btn.textContent = 'Processing...';
                    } else {
                        btn.value = 'Processing...';
                    }
                });
                
                // For AJAX forms, allow the form to proceed
                // For regular forms, the page will reload anyway
                // Set a timeout to re-enable if something goes wrong
                setTimeout(function() {
                    // Only reset if we're still on the same page (for AJAX forms that failed)
                    if (form.dataset.submitting === 'true') {
                        resetForm(form);
                    }
                }, 30000); // 30 second timeout
            });
        });
        
        // Also handle individual buttons with data-loading attribute
        const loadingButtons = document.querySelectorAll('button[data-loading="true"], input[type="submit"][data-loading="true"]');
        
        loadingButtons.forEach(button => {
            if (button.dataset.loadingInitialized) {
                return;
            }
            button.dataset.loadingInitialized = 'true';
            
            button.addEventListener('click', function(e) {
                // Check if already processing
                if (button.disabled || button.classList.contains('btn-loading')) {
                    e.preventDefault();
                    return false;
                }
                
                const customMessage = button.dataset.loadingMessage || 'Processing...';
                
                // Show loading
                showLoading(customMessage);
                
                // Disable button
                button.disabled = true;
                button.classList.add('btn-loading');
                
                if (!button.dataset.originalText) {
                    button.dataset.originalText = button.textContent || button.value;
                }
                
                if (button.tagName === 'BUTTON') {
                    button.textContent = 'Processing...';
                } else {
                    button.value = 'Processing...';
                }
            });
        });
    }
    
    // Show loading overlay
    function showLoading(message) {
        const overlay = document.getElementById('globalLoadingOverlay');
        const textElement = overlay.querySelector('.global-loading-text');
        
        if (textElement && message) {
            textElement.textContent = message;
        }
        
        overlay.style.display = 'flex';
        
        // Prevent scrolling
        document.body.style.overflow = 'hidden';
    }
    
    // Hide loading overlay
    function hideLoading() {
        const overlay = document.getElementById('globalLoadingOverlay');
        overlay.style.display = 'none';
        
        // Restore scrolling
        document.body.style.overflow = '';
    }
    
    // Reset form to allow resubmission (for AJAX forms)
    function resetForm(form) {
        form.dataset.submitting = 'false';
        
        const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        submitButtons.forEach(btn => {
            btn.disabled = false;
            btn.classList.remove('btn-loading');
            
            if (btn.dataset.originalText) {
                if (btn.tagName === 'BUTTON') {
                    btn.textContent = btn.dataset.originalText;
                } else {
                    btn.value = btn.dataset.originalText;
                }
            }
        });
        
        hideLoading();
    }
    
    // Public API for manual control
    window.GlobalLoading = {
        show: showLoading,
        hide: hideLoading,
        reset: function(formSelector) {
            const form = document.querySelector(formSelector);
            if (form) {
                resetForm(form);
            }
        },
        // Reinitialize for dynamically loaded content
        refresh: init
    };
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Reinitialize on page show (for back button)
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            hideLoading();
            // Reset all forms
            document.querySelectorAll('form[data-loading="true"]').forEach(resetForm);
        }
    });
    
})();
