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

    // ========================================
    // DOM CONTENT LOADED
    // ========================================
    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById('today-date').value = new Date().toLocaleDateString('en-GB');
        document.getElementById('ic').addEventListener('input', formatIC);
        document.getElementById('ic').addEventListener('input', calculateAge);
        document.getElementById('parent-ic').addEventListener('input', formatIC);
        document.getElementById('phone').addEventListener('input', formatPhone);
        
        document.getElementById('school').addEventListener('change', toggleOtherSchool);
        
        const statusRadios = document.querySelectorAll('.status-radio');
        statusRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                updateStatusRadioStyle();
                updateScheduleAvailability();
                updateEventAvailability(); // Add this line to update event availability
            });
        });
        
        updateStatusRadioStyle();
        updateScheduleAvailability();
        updateEventAvailability(); // Add this line to set initial event availability
    });

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
    // EVENT AVAILABILITY - NEW FUNCTION
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
        
        // Get all event checkboxes
        const eventCheckboxes = document.querySelectorAll('input[name="evt"]');
        
        eventCheckboxes.forEach(checkbox => {
            const eventValue = checkbox.value;
            
            // Check if event starts with "基础" (Basic)
            if (eventValue.startsWith('基础')) {
                const label = checkbox.closest('label');
                
                if (isStateOrBackupTeam) {
                    // Disable basic events for State Team and Backup Team
                    checkbox.disabled = true;
                    checkbox.checked = false; // Uncheck if previously checked
                    
                    // Visual styling for disabled state
                    if (label) {
                        label.style.opacity = '0.4';
                        label.style.cursor = 'not-allowed';
                        label.style.pointerEvents = 'none';
                    }
                } else {
                    // Enable basic events for regular students
                    checkbox.disabled = false;
                    
                    // Reset visual styling
                    if (label) {
                        label.style.opacity = '1';
                        label.style.cursor = 'pointer';
                        label.style.pointerEvents = 'auto';
                    }
                }
            }
        });
        
        // Add informational message for State/Backup Team
        const basicSection = document.querySelector('.border-l-4.border-slate-700');
        if (basicSection) {
            // Remove existing message if any
            const existingMessage = basicSection.querySelector('.state-team-message');
            if (existingMessage) {
                existingMessage.remove();
            }
            
            // Add message for State/Backup Team
            if (isStateOrBackupTeam) {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'state-team-message bg-red-50 border-l-4 border-red-500 p-3 mt-3 rounded-r-lg';
                messageDiv.innerHTML = `
                    <p class="text-xs text-red-800 leading-relaxed">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>注意：</strong> 州队和后备队不允许选择基础项目。
                    </p>
                    <p class="text-xs text-red-700 leading-relaxed mt-1">
                        <strong>Note:</strong> State Team and Backup Team cannot select Basic level events.
                    </p>
                `;
                basicSection.appendChild(messageDiv);
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
                        
                        // Only show wsa-sun-10am and wsa-sun-12pm for normal students
                        if (scheduleKey === 'wsa-sun-10am' || scheduleKey === 'wsa-sun-12pm') {
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

    // ========================================
    // STEP NAVIGATION
    // ========================================
    function changeStep(dir) {
        if (dir === 1 && !validateStep(currentStep)) {
            return;
        }
        
        if (dir === 1 && currentStep === 5) {
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

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    async function submitPayment() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) overlay.style.display = 'flex';

        try {
            const { classCount, totalFee } = calculateFees();
            const paymentDate = document.getElementById('payment-date').value;

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
                
                window.scrollTo({ top: 0, behavior: 'smooth' });
                
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
            if (!ic || ic.length < 14) {
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
            const paymentDate = document.getElementById('payment-date').value;
            
            if (!paymentDate) {
                Swal.fire('Error', 'Please select payment date', 'error');
                return false;
            }
            
            if (!receiptBase64) {
                Swal.fire('Error', 'Please upload payment receipt', 'error');
                return false;
            }
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
                pdfBase64: pdfBase64
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
            
            window.scrollTo({ top: 0, behavior: 'smooth' });

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
        document.getElementById('pdf-level').innerText = level;
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
    // PAYMENT FUNCTIONS
    // ========================================
    let receiptBase64 = null;

    function calculateFees() {
        const schedules = document.querySelectorAll('input[name="sch"]:checked');
        const classCount = schedules.length;
        
        let totalFee = 0;
        if (classCount === 1) totalFee = 120;
        else if (classCount === 2) totalFee = 200;
        else if (classCount === 3) totalFee = 280;
        else if (classCount >= 4) totalFee = 320;
        
        return { classCount, totalFee };
    }

    function updatePaymentDisplay() {
        const { classCount, totalFee } = calculateFees();
        
        document.getElementById('payment-class-count').innerText = classCount;
        document.getElementById('payment-total').innerText = `RM ${totalFee}`;
        
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

    function copyAccountNumber() {
        const accountNumber = '5621 2345 6789';
        navigator.clipboard.writeText(accountNumber).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'Copied!',
                text: 'Account number copied to clipboard',
                timer: 1500,
                showConfirmButton: false
            });
        });
    }

    function handleReceiptUpload(event) {
        const file = event.target.files[0];
        if (!file) return;

        if (file.size > 5 * 1024 * 1024) {
            Swal.fire('Error', 'File size must be less than 5MB', 'error');
            return;
        }

        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        if (!validTypes.includes(file.type)) {
            Swal.fire('Error', 'Only JPG, PNG, and PDF files are allowed', 'error');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            receiptBase64 = e.target.result;
            
            document.getElementById('upload-prompt').classList.add('hidden');
            document.getElementById('upload-preview').classList.remove('hidden');
            
            if (file.type === 'application/pdf') {
                document.getElementById('preview-image').style.display = 'none';
            } else {
                document.getElementById('preview-image').src = receiptBase64;
                document.getElementById('preview-image').style.display = 'block';
            }
            
            document.getElementById('preview-filename').innerText = file.name;
        };
        reader.readAsDataURL(file);
    }

    function removeReceipt() {
        receiptBase64 = null;
        document.getElementById('receipt-upload').value = '';
        document.getElementById('upload-prompt').classList.remove('hidden');
        document.getElementById('upload-preview').classList.add('hidden');
    }