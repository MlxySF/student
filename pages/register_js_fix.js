// ========================================
// QUICK FIX FOR STEP 6 ERROR
// Add this script to your existing register.php
// ========================================

// FIX 1: Update submitAndGeneratePDF to not require level field
window.submitAndGeneratePDF_ORIGINAL = window.submitAndGeneratePDF;
window.submitAndGeneratePDF = async function() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) overlay.style.display = 'flex';

    try {
        // Collect all form data
        const nameEn = document.getElementById('name-en').value;
        const nameCn = document.getElementById('name-cn').value || '';
        const ic = document.getElementById('ic').value;
        const age = document.getElementById('age').value;
        const school = document.getElementById('school').value === 'Others' 
            ? document.getElementById('school-other').value 
            : document.getElementById('school').value;
        
        const statusRadios = document.getElementsByName('status');
        let status = '';
        for (const radio of statusRadios) {
            if (radio.checked) {
                status = radio.value;
                break;
            }
        }

        const phone = document.getElementById('phone').value;
        const email = document.getElementById('email').value;

        // FIX: Auto-extract level from events instead of requiring input
        const eventsCheckboxes = document.querySelectorAll('input[name="evt"]:checked');
        const eventsArray = Array.from(eventsCheckboxes).map(cb => cb.value);
        const events = eventsArray.join(', ');

        // Auto-detect level from events
        let level = 'Mixed';
        if (events.includes('基础') || events.includes('Basic')) {
            level = '基础 Basic';
        } else if (events.includes('初级') || events.includes('Junior')) {
            level = '初级 Junior';
        } else if (events.includes('B组') || events.includes('Group B')) {
            level = 'B组 Group B';
        } else if (events.includes('A组') || events.includes('Group A')) {
            level = 'A组 Group A';
        } else if (events.includes('自选') || events.includes('Optional')) {
            level = '自选 Optional';
        }

        // Get schedules
        const scheduleCheckboxes = document.querySelectorAll('input[name="sch"]:checked');
        const schedulesArray = Array.from(scheduleCheckboxes).map(cb => cb.value);
        const schedules = schedulesArray.join(', ');

        const parentName = document.getElementById('parent-name').value;
        const parentIC = document.getElementById('parent-ic').value;
        const formDate = document.getElementById('today-date').value;

        // Get signature
        if (!hasSigned) {
            if (overlay) overlay.style.display = 'none';
            Swal.fire('Error', 'Please provide a signature', 'error');
            return;
        }

        const signatureBase64 = canvas.toDataURL('image/png');
        const displayName = nameCn ? `${nameEn} (${nameCn})` : nameEn;

        // Generate PDF
        const pdfBase64 = await generatePDFFile();

        // Store data temporarily
        window.registrationData = {
            nameCn: nameCn,
            nameEn: nameEn,
            displayName: displayName,
            ic: ic,
            age: age,
            school: school,
            status: status,
            phone: phone,
            email: email,
            level: level, // Now auto-detected
            events: events,
            schedule: schedules,
            parent: parentName,
            parentIC: parentIC,
            date: formDate,
            signature: signatureBase64,
            pdfBase64: pdfBase64
        };

        if (overlay) overlay.style.display = 'none';

        // Move to Step 6 (Payment)
        document.getElementById(`step-${currentStep}`).classList.remove('active');
        currentStep = 6;
        document.getElementById(`step-${currentStep}`).classList.add('active');
        
        const stepCounter = document.getElementById('step-counter');
        stepCounter.innerHTML = `0${currentStep}<span style="color: #475569; font-size: 14px;">/07</span>`;
        
        const progressBar = document.getElementById('progress-bar');
        progressBar.style.width = `${(currentStep / 7) * 100}%`;
        
        updatePaymentDisplay();
        document.getElementById('payment-date').value = new Date().toISOString().split('T')[0];
        
        window.scrollTo({ top: 0, behavior: 'smooth' });

    } catch (error) {
        if (overlay) overlay.style.display = 'none';
        console.error('Error:', error);
        Swal.fire('Error', 'An error occurred: ' + error.message, 'error');
    }
};

// FIX 2: Improved receipt upload handling
window.handleReceiptUpload_ORIGINAL = window.handleReceiptUpload;
window.handleReceiptUpload = function(event) {
    const file = event.target.files[0];
    if (!file) return;

    // Validate file size (5MB max)
    if (file.size > 5 * 1024 * 1024) {
        Swal.fire('Error', 'File size must be less than 5MB', 'error');
        event.target.value = '';
        return;
    }

    // Validate file type
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    if (!validTypes.includes(file.type)) {
        Swal.fire('Error', 'Only JPG, PNG, and PDF files are allowed', 'error');
        event.target.value = '';
        return;
    }

    // Convert to base64 with data URL prefix (IMPORTANT!)
    const reader = new FileReader();
    reader.onload = function(e) {
        // Store with data URL prefix for MIME type detection
        window.receiptBase64 = e.target.result;
        
        document.getElementById('upload-prompt').classList.add('hidden');
        document.getElementById('upload-preview').classList.remove('hidden');
        
        if (file.type === 'application/pdf') {
            document.getElementById('preview-image').style.display = 'none';
        } else {
            document.getElementById('preview-image').src = window.receiptBase64;
            document.getElementById('preview-image').style.display = 'block';
        }
        
        document.getElementById('preview-filename').innerText = file.name;
    };
    reader.readAsDataURL(file); // This includes MIME type prefix
};

// FIX 3: Update submitPayment with better error handling
window.submitPayment_ORIGINAL = window.submitPayment;
window.submitPayment = async function() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) overlay.style.display = 'flex';

    try {
        const { classCount, totalFee } = calculateFees();
        const paymentDate = document.getElementById('payment-date').value;

        if (!paymentDate) {
            throw new Error('Please select payment date');
        }
        
        if (!window.receiptBase64) {
            throw new Error('Please upload payment receipt');
        }

        // Prepare payload
        const payload = {
            name_cn: window.registrationData.nameCn || '',
            name_en: window.registrationData.nameEn,
            ic: window.registrationData.ic,
            age: window.registrationData.age,
            school: window.registrationData.school,
            status: window.registrationData.status,
            phone: window.registrationData.phone,
            email: window.registrationData.email,
            level: window.registrationData.level, // Now included
            events: window.registrationData.events,
            schedule: window.registrationData.schedule,
            parent_name: window.registrationData.parent,
            parent_ic: window.registrationData.parentIC,
            form_date: window.registrationData.date,
            signature_base64: window.registrationData.signature,
            signed_pdf_base64: window.registrationData.pdfBase64,
            payment_amount: totalFee,
            payment_date: paymentDate,
            payment_receipt_base64: window.receiptBase64, // With data URL prefix
            class_count: classCount
        };

        console.log('Submitting payload (without base64 data):', {
            ...payload,
            signature_base64: '[BASE64 DATA]',
            signed_pdf_base64: '[BASE64 DATA]',
            payment_receipt_base64: '[BASE64 DATA]'
        });

        const response = await fetch('../process_registration.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (overlay) overlay.style.display = 'none';

        if (result.success) {
            document.getElementById('reg-number-display').innerText = 
                `Registration Number: ${result.registration_number}`;
            
            // Move to Step 7 (Success)
            document.getElementById(`step-${currentStep}`).classList.remove('active');
            currentStep = 7;
            document.getElementById(`step-${currentStep}`).classList.add('active');
            
            const stepCounter = document.getElementById('step-counter');
            stepCounter.innerHTML = `0${currentStep}<span style="color: #475569; font-size: 14px;">/07</span>`;
            
            const progressBar = document.getElementById('progress-bar');
            progressBar.style.width = '100%';
            
            document.getElementById('btn-prev').disabled = true;
            document.getElementById('btn-next').style.display = 'none';
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            Swal.fire({
                icon: 'success',
                title: 'Registration Successful!',
                html: `<strong>Registration #:</strong> ${result.registration_number}<br><br>
                       <strong>Login Email:</strong> ${result.email}<br>
                       <strong>Temporary Password:</strong> ${result.temp_password}<br><br>
                       <small>Please login to track your registration status</small>`,
                confirmButtonText: 'OK'
            });
        } else {
            Swal.fire('Error', result.error || 'Registration failed', 'error');
        }

    } catch (error) {
        if (overlay) overlay.style.display = 'none';
        console.error('Error:', error);
        Swal.fire('Error', 'An error occurred: ' + error.message, 'error');
    }
};

console.log('✅ Registration form patches applied successfully!');
