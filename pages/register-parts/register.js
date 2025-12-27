// ========================================
    // GLOBAL VARIABLES
    // ========================================
    let canvas, ctx;
    let isDrawing = false;
    let lastX = 0, lastY = 0;
    let hasSigned = false;
    
    let currentStep = 1;
    const totalSteps = 7;
    let registrationData = null;
    let savedPdfBlob = null;
    let classHolidays = []; // Store holidays loaded from API

    // ========================================
    // DOM CONTENT LOADED
    // ========================================
    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById('today-date').value = new Date().toLocaleDateString('en-GB');
        //document.getElementById('ic').addEventListener('input', formatIC);
        document.getElementById('ic').addEventListener('input', calculateAge);
        //document.getElementById('parent-ic').addEventListener('input', formatIC);
        document.getElementById('phone').addEventListener('input', formatPhone);
        
        document.getElementById('school').addEventListener('change', toggleOtherSchool);
        
        document.getElementById('password-type').addEventListener('change', togglePasswordField);
        
        document.getElementById('email').addEventListener('input', function(e) {
        checkParentEmail(e.target.value.trim());
        });
        
        const statusRadios = document.querySelectorAll('.status-radio');
        statusRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                updateStatusRadioStyle();
                updateScheduleAvailability();
                updateEventAvailability(); // Update event availability when status changes
            });
        });
        
        updateStatusRadioStyle();
        updateScheduleAvailability();
        
        // Call updateEventAvailability after a short delay to ensure DOM is fully loaded
        setTimeout(function() {
            updateEventAvailability();
        }, 100);
        
        // Load holidays on page load
        loadHolidays();
    });

// ========================================
// LOAD HOLIDAYS FROM API
// ========================================
async function loadHolidays() {
    try {
        const response = await fetch('../admin_pages/api/get_available_dates.php');
        const result = await response.json();
        
        if (result.success) {
            classHolidays = result.holidays;
            console.log('✅ Holidays loaded:', classHolidays);
        } else {
            console.error('❌ Failed to load holidays:', result.message);
            classHolidays = [];
        }
    } catch (error) {
        console.error('❌ Error loading holidays:', error);
        classHolidays = [];
    }
}

// ========================================
// CALCULATE CLASS COUNTS FOR EACH SELECTED SCHEDULE
// ========================================
function calculateActualClassCounts() {
    const selectedSchedules = Array.from(document.querySelectorAll('input[name="sch"]:checked'))
        .map(cb => cb.value);
    
    if (selectedSchedules.length === 0) {
        return { totalClasses: 0, breakdown: [] };
    }
    
    const breakdown = [];
    let totalClassesAfterDeductions = 0;
    
    selectedSchedules.forEach(schedule => {
        const classCount = calculateClassesForSchedule(schedule, classHolidays);
        breakdown.push({
            schedule: schedule,
            classes: classCount
        });
        totalClassesAfterDeductions += classCount;
    });
    
    return {
        totalClasses: totalClassesAfterDeductions,
        breakdown: breakdown
    };
}

// 1. UPDATE calculateClassesForSchedule function (around line 96-145)
function calculateClassesForSchedule(scheduleText, holidays) {
    let dayOfWeek = null;
    
    if (scheduleText.includes('Wed') || scheduleText.includes('Wednesday')) {
        dayOfWeek = 3;
    } else if (scheduleText.includes('Sun') || scheduleText.includes('Sunday')) {
        dayOfWeek = 0;
    } else if (scheduleText.includes('Tue') || scheduleText.includes('Tuesday')) {
        dayOfWeek = 2;
    } else if (scheduleText.includes('Fri') || scheduleText.includes('Friday')) {
        dayOfWeek = 5;
    }
    
    if (dayOfWeek === null) {
        console.warn('Could not parse day from schedule:', scheduleText);
        return 4;
    }
    
    // ✅ CHANGED: Use January 2026 instead of current date
    const currentMonth = 0;  // January (0-indexed)
    const currentYear = 2026;
    
    const startDate = new Date(currentYear, currentMonth, 1);
    const endDate = new Date(currentYear, currentMonth + 1, 0);
    
    const allDates = [];
    
    for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
        if (d.getDay() === dayOfWeek) {
            const dateStr = d.getFullYear() + '-' + 
                            String(d.getMonth() + 1).padStart(2, '0') + '-' + 
                            String(d.getDate()).padStart(2, '0');
            allDates.push(dateStr);
        }
    }
    
    const validDates = allDates.filter(date => !holidays.includes(date));
    
    const monthName = startDate.toLocaleString('en-US', { month: 'long', year: 'numeric' });
    console.log('Valid dates:', validDates);
    console.log(`📅 ${scheduleText} (${monthName}): ${allDates.length} total, ${validDates.length} after holidays`);
    
    return validDates.length;
}



    // ========================================
    // SCROLL TO TOP HELPER FUNCTION
    // ========================================
    function scrollToTop() {
        // Method 1: Scroll the form container (with class custom-scroll)
        const formContainer = document.querySelector('.custom-scroll');
        if (formContainer) {
            formContainer.scrollTop = 0; // Instant scroll
            formContainer.scrollTo({ top: 0, behavior: 'auto' }); // Force instant
        }
        
        // Method 2: Scroll window
        window.scrollTo({ top: 0, behavior: 'auto' });
        document.documentElement.scrollTop = 0;
        document.body.scrollTop = 0;
        
        // Method 3: Try again after a tiny delay to ensure DOM has updated
        setTimeout(() => {
            if (formContainer) {
                formContainer.scrollTop = 0;
            }
            window.scrollTo({ top: 0, behavior: 'auto' });
        }, 50);
    }
    
    // ========================================
// PASSWORD SELECTION TOGGLE
// ========================================
function togglePasswordField() {
    const passwordType = document.getElementById('password-type').value;
    const customPasswordContainer = document.getElementById('custom-password-container');
    const customPasswordConfirmContainer = document.getElementById('custom-password-confirm-container');
    const customPasswordInput = document.getElementById('custom-password');
    const customPasswordConfirmInput = document.getElementById('custom-password-confirm');
    
    if (passwordType === 'custom') {
        customPasswordContainer.classList.remove('hidden');
        customPasswordConfirmContainer.classList.remove('hidden');
        customPasswordInput.required = true;
        customPasswordConfirmInput.required = true;
    } else {
        customPasswordContainer.classList.add('hidden');
        customPasswordConfirmContainer.classList.add('hidden');
        customPasswordInput.required = false;
        customPasswordConfirmInput.required = false;
        customPasswordInput.value = '';
        customPasswordConfirmInput.value = '';
    }
}

// ========================================
// CHECK IF PARENT EMAIL EXISTS
// ========================================
let isExistingParent = false;
let emailCheckTimeout = null;

async function checkParentEmail(email) {
    // Clear previous timeout
    if (emailCheckTimeout) {
        clearTimeout(emailCheckTimeout);
    }
    
    // Reset state
    isExistingParent = false;
    const existingParentInfo = document.getElementById('existing-parent-info');
    const newParentInfo = document.getElementById('new-parent-info');
    const passwordSelectorContainer = document.getElementById('password-selector-container');
    const passwordTypeSelect = document.getElementById('password-type');
    
    // Hide all info boxes
    existingParentInfo.classList.add('hidden');
    newParentInfo.classList.add('hidden');
    passwordSelectorContainer.classList.add('hidden');
    
    // Validate email format
    if (!email || !email.includes('@')) {
        return;
    }
    
    // Debounce - wait 500ms after user stops typing
    emailCheckTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`../check_parent_email.php?email=${encodeURIComponent(email)}`);
            const result = await response.json();
            
            if (result.success && result.exists) {
                // Existing parent found
                isExistingParent = true;
                
                // Show existing parent info
                existingParentInfo.classList.remove('hidden');
                document.getElementById('existing-parent-details').innerHTML = `
                    <p><strong>Parent Code:</strong> ${result.parent_id}</p>
                    <p><strong>Name:</strong> ${result.parent_name}</p>
                    <p><strong>Registered:</strong> ${result.registered_date}</p>
                `;
                
                // Hide password selector
                passwordSelectorContainer.classList.add('hidden');
                passwordTypeSelect.required = false;
                
                console.log('✅ Existing parent detected:', result.parent_id);
                
            } else if (result.success && !result.exists) {
                // New parent
                isExistingParent = false;
                
                // Show new parent info
                newParentInfo.classList.remove('hidden');
                
                // Show password selector
                passwordSelectorContainer.classList.remove('hidden');
                passwordTypeSelect.required = true;
                
                console.log('🆕 New parent - password selection required');
            }
            
        } catch (error) {
            console.error('Error checking parent email:', error);
            // On error, assume new parent (fail-safe)
            isExistingParent = false;
            newParentInfo.classList.remove('hidden');
            passwordSelectorContainer.classList.remove('hidden');
            passwordTypeSelect.required = true;
        }
    }, 500); // Wait 500ms after user stops typing
}



    // ========================================
    // STATUS RADIO STYLING
    // ========================================
    function updateStatusRadioStyle() {
        const radios = document.querySelectorAll('.status-radio');
        radios.forEach(radio => {
            const option = radio.nextElementSibling;
            if (radio.checked) {
                option.style.background = '#1e293b';
                option.style.color = 'white';
                option.style.borderColor = '#1e293b';
                option.style.fontWeight = 'bold';
            } else {
                option.style.background = 'white';
                option.style.color = '#475569';
                option.style.borderColor = '#e2e8f0';
                option.style.fontWeight = 'normal';
            }
        });
    }

    // ========================================
    // EVENT AVAILABILITY - HIDE BASIC SECTION FOR STATE/BACKUP TEAM
    // ========================================
    function updateEventAvailability() {
        const statusRadios = document.getElementsByName('status');
        let selectedStatus = 'Student 学生';
        
        for (const radio of statusRadios) {
            if (radio.checked) {
                selectedStatus = radio.value;
                break;
            }
        }
        
        const isStateOrBackupTeam = selectedStatus === 'State Team 州队' || selectedStatus === 'Backup Team 后备队';
        
        // Find the Basic (基础) section - it has border-slate-700 class
        const basicSection = document.querySelector('.basic-routines');
        
        if (basicSection) {
            if (isStateOrBackupTeam) {
                // Hide the entire Basic section for State/Backup Team
                basicSection.style.display = 'none';
                
                // Uncheck all Basic event checkboxes
                const basicCheckboxes = basicSection.querySelectorAll('input[name="evt"]');
                basicCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
            } else {
                // Show the Basic section for regular students
                basicSection.style.display = 'block';
            }
        }
    }

    // ========================================
    // SCHEDULE AVAILABILITY - MODIFIED FOR NORMAL STUDENTS
    // ========================================
    function updateScheduleAvailability() {
        const statusRadios = document.getElementsByName('status');
        let selectedStatus = 'Student 学生';
        for (const radio of statusRadios) {
            if (radio.checked) {
                selectedStatus = radio.value;
                break;
            }
        }

        const isRegularStudent = selectedStatus === 'Student 学生';
        
        // Get all school boxes
        const schoolBoxes = document.querySelectorAll('.school-box');
        
        if (isRegularStudent) {
            // For normal students, only show WSA and hide all schedules except Sunday 10am-12pm and 12pm-2pm
            schoolBoxes.forEach(schoolBox => {
                const schoolHeader = schoolBox.querySelector('.school-text h3');
                const schoolName = schoolHeader ? schoolHeader.textContent : '';
                
                // Check if this is the Wushu Sport Academy
                if (schoolName.includes('Wushu Sport Academy') || schoolName.includes('武术体育学院')) {
                    // Show WSA school box
                    schoolBox.style.display = 'block';
                    
                    // Handle individual schedules within WSA
                    const allScheduleLabels = schoolBox.querySelectorAll('label[data-schedule]');
                    allScheduleLabels.forEach(label => {
                        const scheduleKey = label.getAttribute('data-schedule');
                        const checkbox = label.querySelector('input[type="checkbox"]');
                        
                        // Only show wsa-sun-10am and wsa-sun-1pm for normal students
                        if (scheduleKey === 'wsa-sun-10am' || scheduleKey === 'wsa-sun-1pm') {
                            label.style.display = 'flex';
                            if (checkbox) {
                                checkbox.disabled = false;
                            }
                        } else {
                            label.style.display = 'none';
                            if (checkbox) {
                                checkbox.checked = false;
                                checkbox.disabled = true;
                            }
                        }
                    });
                } else {
                    // Hide Puay Chai 2 and Chinwoo completely for normal students
                    schoolBox.style.display = 'none';
                    
                    // Uncheck and disable all checkboxes in hidden school boxes
                    const allCheckboxes = schoolBox.querySelectorAll('input[type="checkbox"]');
                    allCheckboxes.forEach(checkbox => {
                        checkbox.checked = false;
                        checkbox.disabled = true;
                    });
                }
            });
        } else {
            // For State Team and Backup Team, show all school boxes and all schedules
            schoolBoxes.forEach(schoolBox => {
                schoolBox.style.display = 'block';
                
                const allScheduleLabels = schoolBox.querySelectorAll('label[data-schedule]');
                allScheduleLabels.forEach(label => {
                    const checkbox = label.querySelector('input[type="checkbox"]');
                    label.style.display = 'flex';
                    if (checkbox) {
                        checkbox.disabled = false;
                    }
                });
            });
        }
    }

    // ========================================
    // SIGNATURE FUNCTIONS
    // ========================================
    function initSignaturePad() {
        if (canvas) return;
        
        const wrapper = document.getElementById('sig-wrapper');
        if (!wrapper) {
            console.error('sig-wrapper not found');
            return;
        }

        canvas = document.createElement('canvas');
        canvas.id = 'sigCanvas';
        canvas.style.display = 'block';
        canvas.style.cursor = 'crosshair';
        canvas.style.position = 'absolute';
        canvas.style.top = '0';
        canvas.style.left = '0';
        wrapper.appendChild(canvas);

        ctx = canvas.getContext('2d');
        resizeCanvas();

        canvas.addEventListener('mousedown', (e) => {
            const rect = canvas.getBoundingClientRect();
            startDraw(e.clientX - rect.left, e.clientY - rect.top);
        });

        canvas.addEventListener('mousemove', (e) => {
            const rect = canvas.getBoundingClientRect();
            moveDraw(e.clientX - rect.left, e.clientY - rect.top);
        });

        canvas.addEventListener('mouseup', stopDraw);
        canvas.addEventListener('mouseleave', stopDraw);

        canvas.addEventListener('touchstart', (e) => {
            e.preventDefault();
            const t = e.touches[0];
            const rect = canvas.getBoundingClientRect();
            startDraw(t.clientX - rect.left, t.clientY - rect.top);
        }, { passive: false });

        canvas.addEventListener('touchmove', (e) => {
            e.preventDefault();
            const t = e.touches[0];
            const rect = canvas.getBoundingClientRect();
            moveDraw(t.clientX - rect.left, t.clientY - rect.top);
        }, { passive: false });

        canvas.addEventListener('touchend', stopDraw);
        
        console.log('✅ Signature pad initialized');
    }

    function resizeCanvas() {
        if (!canvas) return;
        const wrapper = document.getElementById('sig-wrapper');
        const rect = wrapper.getBoundingClientRect();
        if (rect.width > 0 && rect.height > 0) {
            canvas.width = rect.width;
            canvas.height = rect.height;
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#000';
        }
    }

    function startDraw(x, y) {
        isDrawing = true;
        hasSigned = true;
        document.getElementById('sig-placeholder').style.display = 'none';
        lastX = x;
        lastY = y;
    }

    function moveDraw(x, y) {
        if (!isDrawing) return;
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(x, y);
        ctx.stroke();
        lastX = x;
        lastY = y;
    }

    function stopDraw() {
        isDrawing = false;
    }

    function clearSig() {
        if (!ctx) return;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hasSigned = false;
        document.getElementById('sig-placeholder').style.display = 'flex';
    }

    window.addEventListener('resize', resizeCanvas);

    // ========================================
    // FORMAT FUNCTIONS
    // ========================================
    function formatIC(e) {
        let val = e.target.value.replace(/\D/g, '');
        if (val.length > 12) val = val.substring(0, 12);
        if (val.length > 8) {
            e.target.value = val.substring(0, 6) + '-' + val.substring(6, 8) + '-' + val.substring(8, 12);
        } else if (val.length > 6) {
            e.target.value = val.substring(0, 6) + '-' + val.substring(6, 8);
        } else {
            e.target.value = val;
        }
    }

    function formatPhone(e) {
        let val = e.target.value.replace(/\D/g, '');
        if (val.length > 11) val = val.substring(0, 11);
        if (val.length >= 11) {
            e.target.value = val.substring(0, 3) + '-' + val.substring(3, 7) + ' ' + val.substring(7, 11);
        } else if (val.length >= 10) {
            e.target.value = val.substring(0, 3) + '-' + val.substring(3, 6) + ' ' + val.substring(6, 10);
        } else if (val.length > 3) {
            e.target.value = val.substring(0, 3) + '-' + val.substring(3);
        } else {
            e.target.value = val;
        }
    }
    
    function calculateAge() {
        const ic = document.getElementById('ic').value;
        const ageInput = document.getElementById('age');
        
        if (ic.length >= 6) {
            const year = ic.substring(0, 2);
            const month = ic.substring(2, 4);
            const day = ic.substring(4, 6);
            
            let fullYear = parseInt(year);
            fullYear = fullYear > 25 ? 1900 + fullYear : 2000 + fullYear;
            
            const birthDate = new Date(fullYear, parseInt(month) - 1, parseInt(day));
            const targetDate = new Date(2026, 0, 1);
            let age = targetDate.getFullYear() - birthDate.getFullYear();
            const monthDiff = targetDate.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && targetDate.getDate() < birthDate.getDate())) {
                age--;
            }
            
            if (age < 4 || age > 100) {
                ageInput.value = '';
                showError('Invalid birth year from IC. Age must be between 4-100 in 2026.');
            } else {
                ageInput.value = age;
            }
        } else {
            ageInput.value = '';
        }
    }

    function showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mt-2';
        errorDiv.innerHTML = `<i class="fas fa-exclamation-circle mr-2"></i>${message}`;
        
        const ageInput = document.getElementById('age');
        const existingError = ageInput.parentElement.querySelector('.bg-red-50');
        if (existingError) existingError.remove();
        
        ageInput.parentElement.appendChild(errorDiv);
        setTimeout(() => errorDiv.remove(), 4000);
    }

    function toggleOtherSchool() {
        const schoolSelect = document.getElementById('school');
        const otherInput = document.getElementById('school-other');
        
        if (schoolSelect.value === 'Others') {
            otherInput.classList.remove('hidden');
            otherInput.required = true;
        } else {
            otherInput.classList.add('hidden');
            otherInput.required = false;
            otherInput.value = '';
        }
    }

    function toggleSchoolBox(element) {
        element.classList.toggle('active');
    }

function validateStep5() {
    const termsCheckbox = document.getElementById('terms-agreement');
    
    if (!termsCheckbox.checked) {
        alert('Please agree to the Terms and Conditions before proceeding.\n请先同意条款与条件才能继续。');
        termsCheckbox.focus();
        // Add visual feedback
        termsCheckbox.parentElement.parentElement.classList.add('shake-animation');
        setTimeout(() => {
            termsCheckbox.parentElement.parentElement.classList.remove('shake-animation');
        }, 500);
        return false;
    }
    return true;
}

const style = document.createElement('style');
style.textContent = `
    .shake-animation {
        animation: shake 0.5s;
    }
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
`;
document.head.appendChild(style);

    // ========================================
    // STEP NAVIGATION - FIXED WITH AGGRESSIVE SCROLL
    // ========================================
    function changeStep(dir) {
        if (dir === 1 && !validateStep(currentStep)) {
            return;
        }
        
        if (dir === 1 && currentStep === 5 && !validateStep5()) {
            return;
        }
        
        if (dir === 1 && currentStep === 5 && validateStep5()) {
            submitAndGeneratePDF();
            return;
        }

        if (dir === 1 && currentStep === 6) {
            submitPayment();
            return;
        }

        document.getElementById(`step-${currentStep}`).classList.remove('active');
        currentStep += dir;
        document.getElementById(`step-${currentStep}`).classList.add('active');

        if (currentStep === 5) {
            setTimeout(initSignaturePad, 100);
        }

        if (currentStep === 6) {
            updatePaymentDisplay();
            document.getElementById('payment-date').value = new Date().toISOString().split('T')[0];
        }
        
        // Re-run event availability check when navigating to step 3 (events)
        if (currentStep === 3) {
            setTimeout(updateEventAvailability, 50);
        }

        document.getElementById('btn-prev').disabled = (currentStep === 1);
        
        if (currentStep === 7) {
            document.getElementById('btn-next').style.display = 'none';
        } else {
            document.getElementById('btn-next').style.display = 'block';
        }

        const stepCounter = document.getElementById('step-counter');
        stepCounter.innerHTML = `0${currentStep}<span style="color: #475569; font-size: 14px;">/07</span>`;

        const progressBar = document.getElementById('progress-bar');
        progressBar.style.width = `${(currentStep / 7) * 100}%`;

        // ✨ FORCE SCROLL TO TOP - Multiple attempts
        scrollToTop();
        
        updateBackButton(); // Update back button text based on current step
    }

    async function submitPayment() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) overlay.style.display = 'flex';

        try {
            const { classCount, totalFee } = calculateFees();
            const paymentDate = document.getElementById('payment-date').value;
            
            // Collect payment method
        const paymentMethod = document.getElementById('payment-method').value;
        
        // Collect receipt (only for bank transfer)
        let receiptData = null;
        if (paymentMethod === 'bank_transfer') {
            receiptData = receiptBase64;
        }

            const payload = {
                name_cn: registrationData.nameCn || '',
                name_en: registrationData.nameEn,
                ic: registrationData.ic,
                age: registrationData.age,
                school: registrationData.school,
                status: registrationData.status,
                phone: registrationData.phone,
                email: registrationData.email,
                level: registrationData.level || '',
                events: registrationData.events,
                schedule: registrationData.schedule,
                parent_name: registrationData.parent,
                parent_ic: registrationData.parentIC,
                form_date: registrationData.date,
                signature_base64: registrationData.signature,
                signed_pdf_base64: registrationData.pdfBase64,
                password_type: registrationData.passwordType,
                custom_password: registrationData.customPassword,
                payment_method: paymentMethod,
                payment_amount: totalFee,
                payment_date: paymentDate,
                payment_receipt_base64: receiptBase64,
                class_count: classCount
            };

            console.log('Submitting payload:', payload);

            const response = await fetch('../process_registration.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (overlay) overlay.style.display = 'none';

            if (result.success) {
                registrationData.registrationNumber = result.registration_number;
                registrationData.studentId = result.student_id;
                registrationData.password = result.password;
                registrationData.emailSent = result.email_sent;

                document.getElementById('reg-number-display').innerHTML = `
                    <strong style="font-size: 20px; color: #7c3aed;">Registration Number: ${result.registration_number}</strong>
                `;

                const successContent = document.querySelector('#step-7 > div');
                const credentialsHTML = `
                    <div style="background: #dcfce7; border: 2px solid #16a34a; border-radius: 12px; padding: 24px; margin: 24px auto; max-width: 600px;">
                        <h3 style="font-weight: bold; color: #15803d; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-key"></i>
                            Your Account Credentials 您的账户凭证
                        </h3>
                        <div style="background: white; border-radius: 8px; padding: 16px;">
                            <div style="margin-bottom: 12px;">
                                <strong style="color: #15803d;">Student ID 学号:</strong>
                                <p style="font-size: 18px; font-weight: bold; color: #1e293b; margin: 4px 0;">${result.student_id}</p>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <strong style="color: #15803d;">Email 邮箱:</strong>
                                <p style="font-size: 16px; color: #1e293b; margin: 4px 0;">${result.email}</p>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <strong style="color: #15803d;">Password 密码:</strong>
                                <p style="font-size: 24px; font-weight: bold; color: #dc2626; margin: 4px 0; font-family: monospace; letter-spacing: 2px;">${result.password}</p>
                            </div>
                            <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; margin-top: 16px; border-radius: 4px;">
                                <p style="font-size: 13px; color: #92400e; margin: 0;">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Important:</strong> Please save your password. ${result.email_sent ? 'An email has been sent to your registered email address.' : 'Please note down these credentials as email delivery may be delayed.'}
                                </p>
                            </div>
                        </div>
                    </div>
                `;

                const infoBox = successContent.querySelector('.bg-blue-50');
                if (infoBox) {
                    infoBox.insertAdjacentHTML('beforebegin', credentialsHTML);
                }

                document.getElementById(`step-${currentStep}`).classList.remove('active');
                currentStep = 7;
                document.getElementById(`step-${currentStep}`).classList.add('active');
                
                const stepCounter = document.getElementById('step-counter');
                stepCounter.innerHTML = `07<span style="color: #475569; font-size: 14px;">/07</span>`;
                
                const progressBar = document.getElementById('progress-bar');
                progressBar.style.width = '100%';
                
                document.getElementById('btn-prev').disabled = true;
                document.getElementById('btn-next').style.display = 'none';
                
                // Force scroll to top
                scrollToTop();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Registration Successful!',
                    html: `
                        <p>Your registration number is:</p>
                        <p style="font-size: 20px; font-weight: bold; color: #7c3aed; margin: 10px 0;">${result.registration_number}</p>
                        <p style="margin-top: 16px;">Please save your login credentials displayed on the screen.</p>
                        ${result.email_sent ? '<p style="color: #16a34a; margin-top: 8px;"><i class="fas fa-check-circle"></i> Email sent successfully!</p>' : '<p style="color: #f59e0b; margin-top: 8px;"><i class="fas fa-exclamation-triangle"></i> Email may be delayed. Please save your credentials.</p>'}
                    `,
                    confirmButtonText: 'OK'
                });
            } else {
                Swal.fire('Error', result.error || 'Registration failed', 'error');
            }

        } catch (error) {
            if (overlay) overlay.style.display = 'none';
            console.error('Error:', error);
            Swal.fire('Error', 'An error occurred during submission: ' + error.message, 'error');
        }
    }

    // ========================================
    // VALIDATION
    // ========================================
    function validateStep(step) {
        if (step === 1) {
            const nameEn = document.getElementById('name-en').value.trim();
            const ic = document.getElementById('ic').value.trim();
            const age = document.getElementById('age').value;
            const school = document.getElementById('school').value;
            const schoolOther = document.getElementById('school-other');

            if (!nameEn) {
                Swal.fire('Error', 'Please enter English name', 'error');
                return false;
            }
             //|| ic.length < 14
            if (!ic) {
                Swal.fire('Error', 'Please enter a valid IC number', 'error');
                return false;
            }
            if (!age) {
                Swal.fire('Error', 'Age could not be calculated from IC', 'error');
                return false;
            }
            if (!school) {
                Swal.fire('Error', 'Please select a school', 'error');
                return false;
            }
            if (school === 'Others' && !schoolOther.value.trim()) {
                Swal.fire('Error', 'Please specify school name', 'error');
                return false;
            }
        }

        if (step === 2) {
    const phone = document.getElementById('phone').value.trim();
    const email = document.getElementById('email').value.trim();

    if (!phone || phone.length < 12) {
        Swal.fire('Error', 'Please enter a valid phone number', 'error');
        return false;
    }
    if (!email || !email.includes('@')) {
        Swal.fire('Error', 'Please enter a valid email address', 'error');
        return false;
    }
    
    // Password validation - ONLY for NEW parents
    if (!isExistingParent) {
        const passwordType = document.getElementById('password-type').value;
        
        if (!passwordType) {
            Swal.fire('Error', 'Please select a password option\n请选择密码选项', 'error');
            return false;
        }
        
        if (passwordType === 'custom') {
            const customPassword = document.getElementById('custom-password').value;
            const customPasswordConfirm = document.getElementById('custom-password-confirm').value;
            
            if (customPassword.length < 6) {
                Swal.fire('Error', 'Password must be at least 6 characters\n密码至少需要6个字符', 'error');
                return false;
            }
            
            if (customPassword !== customPasswordConfirm) {
                Swal.fire('Error', 'Passwords do not match\n密码不匹配', 'error');
                return false;
            }
        }
    }
}



        if (step === 3) {
            const events = document.querySelectorAll('input[name="evt"]:checked');
            if (events.length === 0) {
                Swal.fire('Error', 'Please select at least one event', 'error');
                return false;
            }
        }

        if (step === 4) {
            const schedules = document.querySelectorAll('input[name="sch"]:checked');
            if (schedules.length === 0) {
                Swal.fire('Error', 'Please select at least one training schedule', 'error');
                return false;
            }
        }

        if (step === 5) {
            const parentName = document.getElementById('parent-name').value.trim();
            const parentIC = document.getElementById('parent-ic').value.trim();

            if (!parentName) {
                Swal.fire('Error', 'Please enter parent/guardian name', 'error');
                return false;
            }
            if (!parentIC || parentIC.length < 14) {
                Swal.fire('Error', 'Please enter a valid parent/guardian IC', 'error');
                return false;
            }
            if (!hasSigned) {
                Swal.fire('Error', 'Please sign the agreement', 'error');
                return false;
            }
            if (!canvas) {
                Swal.fire('Error', 'Signature canvas not initialized', 'error');
                return false;
            }
        }

        if (step === 6) {
    const paymentMethod = document.getElementById('payment-method').value;
    
    // Check if payment method is selected
    if (!paymentMethod) {
        Swal.fire('Error', 'Please select a payment method!\n请选择付款方式！', 'error');
        return false;
    }
    
    // If bank transfer is selected, validate date and receipt
    if (paymentMethod === 'bank_transfer') {
        const paymentDate = document.getElementById('payment-date').value;
        
        if (!paymentDate) {
            Swal.fire('Error', 'Please select payment date\n请选择付款日期', 'error');
            return false;
        }
        
        if (!receiptBase64) {
            Swal.fire('Error', 'Please upload payment receipt\n请上传付款收据', 'error');
            return false;
        }
    }
    // For cash payment, no additional validation needed
}


        return true;
    }

    // ========================================
    // FORM SUBMISSION
    // ========================================
    async function submitAndGeneratePDF() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) overlay.style.display = 'flex';

        try {
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

            const levelRadios = document.getElementsByName('lvl');
            let level = '';
            for (const radio of levelRadios) {
                if (radio.checked) {
                    level = radio.value === 'Other' 
                        ? document.getElementById('level-other').value 
                        : radio.value;
                    break;
                }
            }

            const eventsCheckboxes = document.querySelectorAll('input[name="evt"]:checked');
            const eventsArray = Array.from(eventsCheckboxes).map(cb => cb.value);
            const events = eventsArray.join(', ');

            const scheduleCheckboxes = document.querySelectorAll('input[name="sch"]:checked');
            const schedulesArray = Array.from(scheduleCheckboxes).map(cb => cb.value);
            const schedules = schedulesArray.join(', ');

            const parentName = document.getElementById('parent-name').value;
            const parentIC = document.getElementById('parent-ic').value;
            const formDate = document.getElementById('today-date').value;
            let passwordType = 'ic_last4'; // Default
            let customPassword = null;


// Only collect password data if this is a NEW parent
if (!isExistingParent) {
    passwordType = document.getElementById('password-type').value || 'ic_last4';
    customPassword = passwordType === 'custom' ? document.getElementById('custom-password').value : null;
}

console.log('Password collection - isExistingParent:', isExistingParent, 'Type:', passwordType);

            if (!hasSigned) {
                if (overlay) overlay.style.display = 'none';
                Swal.fire('Error', 'Please provide a signature', 'error');
                return;
            }

            const signatureBase64 = canvas.toDataURL('image/png');
            const displayName = nameCn ? `${nameEn} (${nameCn})` : nameEn;
            const namePlain = nameEn;

            const pdfBase64 = await generatePDFFile();

            registrationData = {
                nameCn: nameCn,
                nameEn: nameEn,
                namePlain: namePlain,
                displayName: displayName,
                ic: ic,
                age: age,
                school: school,
                status: status,
                phone: phone,
                email: email,
                level: level,
                events: events,
                schedule: schedules,
                parent: parentName,
                parentIC: parentIC,
                date: formDate,
                signature: signatureBase64,
                pdfBase64: pdfBase64,
                passwordType: passwordType,
                customPassword: customPassword
            };

            if (overlay) overlay.style.display = 'none';

            document.getElementById(`step-${currentStep}`).classList.remove('active');
            currentStep = 6;
            document.getElementById(`step-${currentStep}`).classList.add('active');
            
            const stepCounter = document.getElementById('step-counter');
            stepCounter.innerHTML = `06<span style="color: #475569; font-size: 14px;">/07</span>`;
            
            const progressBar = document.getElementById('progress-bar');
            progressBar.style.width = `${(6 / 7) * 100}%`;
            
            document.getElementById('btn-prev').disabled = false;
            
            updatePaymentDisplay();
            document.getElementById('payment-date').value = new Date().toISOString().split('T')[0];
            
            // Force scroll to top
            scrollToTop();

        } catch (error) {
            if (overlay) overlay.style.display = 'none';
            console.error('Error:', error);
            Swal.fire('Error', 'An error occurred during PDF generation: ' + error.message, 'error');
        }
    }

    // ========================================
    // PDF GENERATION
    // ========================================
    async function generatePDFFile() {
        const displayName = (document.getElementById('name-cn').value ? 
            `${document.getElementById('name-en').value} (${document.getElementById('name-cn').value})` : 
            document.getElementById('name-en').value);
        
        const ic = document.getElementById('ic').value;
        const age = document.getElementById('age').value;
        const school = document.getElementById('school').value === 'Others' ? 
            document.getElementById('school-other').value : 
            document.getElementById('school').value;
        
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
        // Get password selection data
const passwordType = document.getElementById('password-type').value;
const customPassword = passwordType === 'custom' ? document.getElementById('custom-password').value : null;

        
        const levelRadios = document.getElementsByName('lvl');
        let level = '';
        for (const radio of levelRadios) {
            if (radio.checked) {
                level = radio.value === 'Other' ? 
                    document.getElementById('level-other').value : 
                    radio.value;
                break;
            }
        }
        
        const eventsCheckboxes = document.querySelectorAll('input[name="evt"]:checked');
        const events = Array.from(eventsCheckboxes).map(cb => cb.value).join(', ');
        
        const scheduleCheckboxes = document.querySelectorAll('input[name="sch"]:checked');
        const schedule = Array.from(scheduleCheckboxes).map(cb => cb.value).join(', ');
        
        const parent = document.getElementById('parent-name').value;
        const parentIC = document.getElementById('parent-ic').value;
        const date = document.getElementById('today-date').value;
        const signature = canvas.toDataURL('image/png');

        document.getElementById('pdf-name').innerText = displayName;
        document.getElementById('pdf-ic').innerText = ic;
        document.getElementById('pdf-age').innerText = age;
        document.getElementById('pdf-school').innerText = school;
        document.getElementById('pdf-status').innerText = status;
        document.getElementById('pdf-phone').innerText = phone;
        document.getElementById('pdf-email').innerText = email;
        //document.getElementById('pdf-level').innerText = level;
        document.getElementById('pdf-events').innerText = events;
        document.getElementById('pdf-schedule').innerText = schedule;
        document.getElementById('pdf-parent-name').innerText = parent;
        document.getElementById('pdf-parent-ic').innerText = parentIC;
        document.getElementById('pdf-date').innerText = date;
        document.getElementById('pdf-sig-img').src = signature;

        document.getElementById('pdf-parent-name-2').innerText = parent;
        document.getElementById('pdf-parent-ic-2').innerText = parentIC;
        document.getElementById('pdf-date-2').innerText = date;

        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');

        const page1 = document.getElementById('pdf-template-page1');
        page1.style.visibility = 'visible';
        page1.style.opacity = '1';
        page1.style.position = 'absolute';
        page1.style.left = '0';
        page1.style.top = '0';
        page1.style.zIndex = '9999';

        await new Promise(resolve => setTimeout(resolve, 200));

        const canvas1 = await html2canvas(page1, {
            scale: 2,
            useCORS: true,
            allowTaint: true,
            width: 794,
            height: 1123,
            logging: false,
            backgroundColor: '#ffffff'
        });

        const imgData1 = canvas1.toDataURL('image/jpeg', 0.95);
        pdf.addImage(imgData1, 'JPEG', 0, 0, 210, 297);

        page1.style.visibility = 'hidden';
        page1.style.opacity = '0';
        page1.style.position = 'fixed';
        page1.style.left = '-99999px';
        page1.style.top = '-99999px';
        page1.style.zIndex = '-9999';

        const page2 = document.getElementById('pdf-template-page2');
        page2.style.visibility = 'visible';
        page2.style.opacity = '1';
        page2.style.position = 'absolute';
        page2.style.left = '0';
        page2.style.top = '0';
        page2.style.zIndex = '9999';

        await new Promise(resolve => setTimeout(resolve, 200));

        const canvas2 = await html2canvas(page2, {
            scale: 2,
            useCORS: true,
            allowTaint: true,
            width: 794,
            height: 1123,
            logging: false,
            backgroundColor: '#ffffff'
        });

        const imgData2 = canvas2.toDataURL('image/jpeg', 0.95);
        pdf.addPage();
        pdf.addImage(imgData2, 'JPEG', 0, 0, 210, 297);

        page2.style.visibility = 'hidden';
        page2.style.opacity = '0';
        page2.style.position = 'fixed';
        page2.style.left = '-99999px';
        page2.style.top = '-99999px';
        page2.style.zIndex = '-9999';

        const nameForFile = document.getElementById('name-en').value.replace(/\s+/g, '_');
        pdf.save(`${nameForFile}_Registration_Agreement.pdf`);

        savedPdfBlob = pdf.output('blob');

        return pdf.output('datauristring').split(',')[1];
    }

    function downloadPDF() {
        if (savedPdfBlob) {
            const nameForFile = registrationData.nameEn.replace(/\s+/g, '_');
            const url = URL.createObjectURL(savedPdfBlob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${nameForFile}_Registration_Agreement.pdf`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            Swal.fire({
                icon: 'success',
                title: 'Downloaded!',
                text: 'Your registration agreement has been downloaded.',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire('Error', 'PDF not available. Please try again.', 'error');
        }
    }

    function submitAnother() {
        location.reload();
    }

    // ========================================
    // PAYMENT FUNCTIONS - UPDATED FOR JANUARY 2026 PRICING
    // ========================================
    let receiptBase64 = null;

// CORRECTED FEE CALCULATION - January 2026 Per-Session Pricing
// ✅ FIXED: Sort ASCENDING so classes with FEWER sessions get positioned LAST
// This ensures they get the LOWER pricing rates (RM21, RM24, etc.)
function calculateFees() {
    const schedules = document.querySelectorAll('input[name="sch"]:checked');
    const scheduleCount = schedules.length;
    
    if (scheduleCount === 0) {
        return { classCount: 0, totalFee: 0, holidayDeduction: 0 };
    }
    
    // Get actual class counts using breakdown
    const { breakdown } = calculateActualClassCounts();
    
    // ✅ SORT ASCENDING (fewest sessions first → positioned first → gets RM30)
    // This way: 3 sessions gets RM30, 4 sessions gets RM27, 4 sessions gets RM24, 5 sessions gets RM21
    const sortedBreakdown = breakdown.sort((a, b) => b.classes - a.classes);
    
    // Per-session pricing based on class position
    const sessionPricing = [30, 27, 24, 21]; // RM30, RM27, RM24, RM21
    
    let totalFee = 0;
    let totalSessions = 0;
    
    // Calculate fee for each class based on its SORTED position
    sortedBreakdown.forEach((classData, index) => {
        const pricePerSession = sessionPricing[index] || sessionPricing[sessionPricing.length - 1];
        const sessionCount = classData.classes;
        const classFee = pricePerSession * sessionCount;
        
        totalFee += classFee;
        totalSessions += sessionCount;
        
        console.log(`💰 Position ${index + 1} - ${classData.schedule}: ${sessionCount} sessions × RM${pricePerSession} = RM${classFee}`);
    });
    
    console.log(`💰 TOTAL FEE: RM${totalFee} (${totalSessions} total sessions)`);
    
    return { 
        classCount: totalSessions, 
        totalFee: totalFee,
        baseFee: totalFee,
        holidayDeduction: 0,
        missedClasses: 0,
        sortedBreakdown: sortedBreakdown // Return sorted breakdown for display
    };
}



function updatePaymentDisplay() {
    const { classCount, totalFee, sortedBreakdown } = calculateFees();
    
    // Get selected schedules
    const scheduleCheckboxes = document.querySelectorAll('input[name="sch"]:checked');
    const selectedClasses = Array.from(scheduleCheckboxes).map(cb => cb.value);
    
    // Update class count
    document.getElementById('payment-class-count').innerText = classCount;
    
    // Create a detailed list of selected classes with pricing (SORTED by session count)
    let classListHTML = '';
    if (selectedClasses.length > 0 && sortedBreakdown) {
        const sessionPricing = [30, 27, 24, 21];
        
        classListHTML = '<div style="margin-top: 8px; padding: 8px; background: #f8fafc; border-radius: 6px; border-left: 3px solid #7c3aed;">';
        classListHTML += '<div style="font-size: 11px; font-weight: 600; color: #6b7280; margin-bottom: 4px; text-transform: uppercase;">📅 Fee Calculation:</div>';
        
        sortedBreakdown.forEach((classData, index) => {
            const pricePerSession = sessionPricing[index] || sessionPricing[sessionPricing.length - 1];
            const classFee = pricePerSession * classData.classes;
            classListHTML += `<div style="font-size: 12px; color: #1e293b; padding: 3px 0;">• ${classData.schedule} <span style="color: #7c3aed; font-weight: 600;">(${classData.classes})</span></div>`;
        });
        
        classListHTML += `<div style="margin-top: 6px; padding-top: 6px; border-top: 1px solid #e2e8f0; font-weight: 600; color: #16a34a; font-size: 13px;">Total: ${classCount} sessions</div>`;
        classListHTML += '</div>';
    }
    
    // Find the parent container and update it
    const classCountElement = document.getElementById('payment-class-count');
    const parentDiv = classCountElement.closest('.flex.justify-between.items-center');
    
    // Remove any existing class list
    const existingList = parentDiv.parentElement.querySelector('.selected-classes-list');
    if (existingList) existingList.remove();
    
    // Add the new class list after the count div
    if (classListHTML) {
        const listContainer = document.createElement('div');
        listContainer.className = 'selected-classes-list';
        listContainer.innerHTML = classListHTML;
        parentDiv.parentElement.insertBefore(listContainer, parentDiv.nextSibling);
    }
    
    // Update total
    document.getElementById('payment-total').innerText = `RM ${totalFee}`;
    
    // Update status
    const statusRadios = document.getElementsByName('status');
    let status = '';
    for (const radio of statusRadios) {
        if (radio.checked) {
            status = radio.value;
            break;
        }
    }
    document.getElementById('payment-status').innerText = status;
}
    
    // Toggle payment method sections
function togglePaymentMethod() {
    const paymentMethod = document.getElementById('payment-method').value;
    const bankTransferSection = document.getElementById('bank-transfer-section');
    const cashPaymentNote = document.getElementById('cash-payment-note');
    const paymentDate = document.getElementById('payment-date');
    const receiptUpload = document.getElementById('receipt-upload');
    
    // Hide both sections initially
    bankTransferSection.style.display = 'none';
    cashPaymentNote.style.display = 'none';
    
    // Remove required attribute from bank transfer fields
    if (paymentDate) paymentDate.removeAttribute('required');
    if (receiptUpload) receiptUpload.removeAttribute('required');
    
    if (paymentMethod === 'bank_transfer') {
        // Show bank transfer section
        bankTransferSection.style.display = 'block';
        // Make fields required
        if (paymentDate) paymentDate.setAttribute('required', 'required');
        if (receiptUpload) receiptUpload.setAttribute('required', 'required');
    } else if (paymentMethod === 'cash') {
        // Show cash payment note
        cashPaymentNote.style.display = 'block';
        // Update cash amount
        const totalAmount = document.getElementById('payment-total').textContent;
        document.getElementById('cash-amount').textContent = totalAmount;
    }
}

// Copy account number to clipboard
function copyAccountNumber() {
    const accountNumber = '5050 1981 6740';
    navigator.clipboard.writeText(accountNumber).then(function() {
        // Show success message
        alert('Account number copied! 账户号码已复制！');
    }, function(err) {
        console.error('Could not copy text: ', err);
    });
}

// Handle receipt upload
// Handle receipt upload
function handleReceiptUpload(event) {
    const file = event.target.files[0];
    if (!file) return;

    // Check file size (5MB limit)
    if (file.size > 5 * 1024 * 1024) {
        alert('File size must be less than 5MB!');
        event.target.value = '';
        return;
    }

    // Show preview
    const uploadPrompt = document.getElementById('upload-prompt');
    const uploadPreview = document.getElementById('upload-preview');
    const previewImage = document.getElementById('preview-image');
    const previewFilename = document.getElementById('preview-filename');

    uploadPrompt.classList.add('hidden');
    uploadPreview.classList.remove('hidden');

    // Set filename
    previewFilename.textContent = file.name;

    // Convert to base64 and store in receiptBase64
    const reader = new FileReader();
    reader.onload = function(e) {
        receiptBase64 = e.target.result; // ✅ STORE BASE64 DATA
        console.log('[Receipt Upload] File converted to base64, size:', receiptBase64.length);
        
        // Show image preview if it's an image
        if (file.type.startsWith('image/')) {
            previewImage.src = e.target.result;
            previewImage.style.display = 'block';
        } else {
            // For PDF, show PDF icon
            previewImage.style.display = 'none';
        }
    };
    reader.readAsDataURL(file);
}


// Remove receipt
function removeReceipt() {
    const uploadPrompt = document.getElementById('upload-prompt');
    const uploadPreview = document.getElementById('upload-preview');
    const receiptUpload = document.getElementById('receipt-upload');
    const previewImage = document.getElementById('preview-image');
    
    uploadPreview.classList.add('hidden');
    uploadPrompt.classList.remove('hidden');
    receiptUpload.value = '';
    previewImage.src = '';
}


    // Copy account number to clipboard
function copyAccountNumber() {
    const accountNumber = '5050 1981 6740';
    navigator.clipboard.writeText(accountNumber).then(function() {
        // Show success message
        alert('Account number copied! 账户号码已复制！');
    }, function(err) {
        console.error('Could not copy text: ', err);
    });
}

// Handle receipt upload
function handleReceiptUpload(event) {
    const file = event.target.files[0];
    if (!file) {
        console.log('[Receipt Upload] No file selected');
        return;
    }

    console.log('[Receipt Upload] File selected:', file.name, 'Size:', file.size);

    // Check file size (5MB limit)
    if (file.size > 5 * 1024 * 1024) {
        Swal.fire('Error', 'File size must be less than 5MB!', 'error');
        event.target.value = '';
        receiptBase64 = null;
        return;
    }

    // Show preview
    const uploadPrompt = document.getElementById('upload-prompt');
    const uploadPreview = document.getElementById('upload-preview');
    const previewImage = document.getElementById('preview-image');
    const previewFilename = document.getElementById('preview-filename');

    uploadPrompt.classList.add('hidden');
    uploadPreview.classList.remove('hidden');

    // Set filename
    previewFilename.textContent = file.name;

    // Convert to base64 and store
    const reader = new FileReader();
    reader.onload = function(e) {
        receiptBase64 = e.target.result;
        console.log('[Receipt Upload] ✅ File converted to base64, length:', receiptBase64.length);
        
        // Show image preview if it's an image
        if (file.type.startsWith('image/')) {
            previewImage.src = e.target.result;
            previewImage.style.display = 'block';
        } else {
            // For PDF, hide image and just show filename
            previewImage.style.display = 'none';
        }
    };
    
    reader.onerror = function(error) {
        console.error('[Receipt Upload] ❌ Error reading file:', error);
        Swal.fire('Error', 'Failed to read file. Please try again.', 'error');
        receiptBase64 = null;
    };
    
    reader.readAsDataURL(file);
}

// Remove receipt
function removeReceipt() {
    const uploadPrompt = document.getElementById('upload-prompt');
    const uploadPreview = document.getElementById('upload-preview');
    const receiptUpload = document.getElementById('receipt-upload');
    const previewImage = document.getElementById('preview-image');

    uploadPreview.classList.add('hidden');
    uploadPrompt.classList.remove('hidden');
    receiptUpload.value = '';
    previewImage.src = '';
    
    // Clear base64 data
    receiptBase64 = null;
    console.log('[Receipt Upload] Receipt removed, receiptBase64 cleared');
}

// Handle back button behavior based on current step
function handleBackButton() {
    const currentStepElement = document.querySelector('.step-content.active');
    if (!currentStepElement) return;
    
    const currentStep = parseInt(currentStepElement.id.split('-')[1]);
    
    if (currentStep === 1) {
        // On first step, redirect to login page
        if (confirm('Are you sure you want to go back to login? Any entered information will be lost.\n\n确定要返回登录页面吗？任何已输入的信息将会丢失。')) {
            window.location.href = '../index.php';
        }
    } else {
        // On other steps, go back to previous step
        changeStep(-1);
    }
}

// Update back button text and style based on current step
function updateBackButton() {
    const currentStepElement = document.querySelector('.step-content.active');
    if (!currentStepElement) return;
    
    const currentStep = parseInt(currentStepElement.id.split('-')[1]);
    const backBtn = document.getElementById('btn-prev');
    const backBtnIcon = document.getElementById('back-btn-icon');
    const backBtnText = document.getElementById('back-btn-text');
    const backBtnSubtext = document.getElementById('back-btn-subtext');
    const footerButtons = document.getElementById('footer-buttons');
    
    if (currentStep === 1) {
        // FIRST STEP ONLY - show styled "Back to Login" button
        backBtn.style.display = 'inline-flex';
        backBtn.style.background = '#1e293b';
        backBtn.style.color = 'white';
        backBtn.style.border = '2px solid #fbbf24';
        backBtn.style.boxShadow = '0 4px 12px rgba(30, 41, 59, 0.3)';
        backBtn.style.opacity = '1';
        backBtn.style.cursor = 'pointer';
        backBtn.disabled = false;
        
        // Show icon
        backBtnIcon.style.display = 'flex';
        
        // Update text
        backBtnText.innerHTML = 'Back to Login';
        backBtnText.style.color = 'white';
        backBtnSubtext.innerHTML = '返回登录 ←';
        backBtnSubtext.style.display = 'block';
        
        // Add hover effect
        backBtn.onmouseover = function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 6px 16px rgba(30, 41, 59, 0.4)';
        };
        backBtn.onmouseout = function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 12px rgba(30, 41, 59, 0.3)';
        };
        
        footerButtons.style.justifyContent = 'space-between';
        
    } else if (currentStep === 7) {
        // SUCCESS PAGE - hide the entire back button
        backBtn.style.display = 'none';
        footerButtons.style.justifyContent = 'center';
        
    } else {
        // STEPS 2-6 - show regular gray "← Back" button
        backBtn.style.display = 'inline-flex';
        backBtn.style.background = 'transparent';
        backBtn.style.color = '#64748b';
        backBtn.style.border = 'none';
        backBtn.style.boxShadow = 'none';
        backBtn.style.opacity = '1';
        backBtn.style.cursor = 'pointer';
        backBtn.style.transform = 'none';
        backBtn.disabled = false;
        
        // Hide icon
        backBtnIcon.style.display = 'none';
        
        // Update text
        backBtnText.innerHTML = '← Back';
        backBtnText.style.color = '#64748b';
        backBtnSubtext.innerHTML = '';
        backBtnSubtext.style.display = 'none';
        
        // Remove hover effect
        backBtn.onmouseover = null;
        backBtn.onmouseout = null;
        
        footerButtons.style.justifyContent = 'space-between';
    }
}

// Call updateBackButton when page loads
document.addEventListener('DOMContentLoaded', function() {
    updateBackButton();
});

// Override the original changeStep to also update back button
// This ensures the back button updates whenever the step changes
const originalChangeStep = window.changeStep;
if (typeof originalChangeStep === 'function') {
    window.changeStep = function(direction) {
        originalChangeStep(direction);
        setTimeout(updateBackButton, 50); // Small delay to ensure DOM is updated
    };
}


