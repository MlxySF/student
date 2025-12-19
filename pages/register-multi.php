<?php
/**
 * register-multi.php - Multi-Child Registration Form
 * Allows parents to register multiple children in a single submission
 * Stage 3 Enhancement
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the head and initialize
require_once __DIR__ . '/register-parts/head.php';
?>

<body>

<div class="glass-card">
    
    <!-- Header -->
    <div style="background: #1e293b; color: white; padding: 24px; border-bottom: 4px solid #fbbf24;">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 8px;">
            <div>
                <h1 style="font-size: 24px; font-weight: bold; background: linear-gradient(to right, #fde68a, #fbbf24); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 4px;">
                    2026 武术训练报名 - 多人报名 Multi-Child Registration
                </h1>
                <p style="color: #94a3b8; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Register Multiple Children in One Form</p>
            </div>
            <div style="color: #fbbf24; font-weight: bold; font-size: 20px;" id="step-counter">
                01<span style="color: #475569; font-size: 14px;">/05</span>
            </div>
        </div>
        <div style="width: 100%; background: #475569; height: 6px; border-radius: 999px; overflow: hidden; margin-top: 8px;">
            <div id="progress-bar" style="height: 100%; background: #fbbf24; transition: width 0.5s ease; width: 20%;"></div>
        </div>
    </div>

    <!-- Form Body -->
    <div style="padding: 32px; background: #f8fafc; max-height: 70vh; overflow-y: auto;" class="custom-scroll">
        <form id="regForm" onsubmit="return false;">

            <!-- STEP 1: Parent Information -->
            <div id="step-1" class="step-content active">
                <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-user-tie text-amber-500"></i> 家长资料 Parent Information
                </h2>
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-xl mb-6">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>This information will be used for all children registered in this form.</strong><br>
                        此信息将用于本表格中注册的所有孩子。
                    </p>
                </div>
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase">Parent/Guardian Name 家长姓名 *</label>
                            <input type="text" id="parent-name" class="w-full p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none" placeholder="Tan Ah Meng" required>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase">Parent IC Number 家长身份证 *</label>
                            <input type="text" id="parent-ic" class="w-full p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none" placeholder="000000-00-0000" maxlength="14" required>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase">Parent Email 家长邮箱 *</label>
                            <div class="relative">
                                <i class="fa-solid fa-envelope absolute left-4 top-4 text-slate-400"></i>
                                <input type="email" id="parent-email" class="w-full pl-10 p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none" placeholder="parent@example.com" required>
                            </div>
                            <p class="text-xs text-slate-400">Account credentials will be sent to this email</p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase">Parent Phone 家长电话 *</label>
                            <div class="relative">
                                <i class="fa-solid fa-phone absolute left-4 top-4 text-slate-400"></i>
                                <input type="tel" id="parent-phone" class="w-full pl-10 p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none" placeholder="012-345 6789" maxlength="13" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 2: Children Information -->
            <div id="step-2" class="step-content">
                <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-children text-amber-500"></i> 学员资料 Children Information
                </h2>
                
                <div id="children-container" class="space-y-6">
                    <!-- Child 1 (Template) -->
                    <div class="child-block border-2 border-amber-200 rounded-xl p-6 bg-white" data-child-index="1">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                <span class="bg-amber-500 text-white w-8 h-8 rounded-full flex items-center justify-center text-sm">1</span>
                                Child 1 第一个孩子
                            </h3>
                            <button type="button" class="remove-child-btn hidden text-red-600 hover:text-red-800 font-semibold text-sm" onclick="removeChild(this)">
                                <i class="fas fa-times-circle"></i> Remove
                            </button>
                        </div>
                        
                        <div class="space-y-4">
                            <!-- Name Row -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-slate-500">Chinese Name 中文名</label>
                                    <input type="text" name="child_name_cn[]" class="w-full p-3 rounded-lg border border-slate-300" placeholder="张三">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-slate-500">English Name 英文名 *</label>
                                    <input type="text" name="child_name_en[]" class="w-full p-3 rounded-lg border border-slate-300" placeholder="Tan Xiao Ming" required>
                                </div>
                            </div>

                            <!-- IC and Age -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-slate-500">IC Number 身份证 *</label>
                                    <input type="text" name="child_ic[]" class="child-ic w-full p-3 rounded-lg border border-slate-300" placeholder="000000-00-0000" maxlength="14" required>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-slate-500">Age 年龄 (2026)</label>
                                    <input type="number" name="child_age[]" class="child-age w-full p-3 rounded-lg border border-slate-300 bg-slate-100" placeholder="Auto" readonly>
                                </div>
                            </div>

                            <!-- School and Status -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-slate-500">School 学校 *</label>
                                    <select name="child_school[]" class="w-full p-3 rounded-lg border border-slate-300 bg-white" required>
                                        <option value="">Select School...</option>
                                        <option value="SJK(C) PUAY CHAI 2">SJK(C) PUAY CHAI 2</option>
                                        <option value="SJK(C) Chee Wen">SJK(C) Chee Wen</option>
                                        <option value="SJK(C) Subang">SJK(C) Subang</option>
                                        <option value="Others">Others</option>
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-slate-500">Status 身份 *</label>
                                    <select name="child_status[]" class="w-full p-3 rounded-lg border border-slate-300 bg-white" required>
                                        <option value="Student 学生">Student 学生</option>
                                        <option value="State Team 州队">State Team 州队</option>
                                        <option value="Backup Team 后备队">Backup Team 后备队</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Events Selection (Compact) -->
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500">Events 项目 *</label>
                                <div class="border border-slate-300 rounded-lg p-4 max-h-48 overflow-y-auto">
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                                        <label class="flex items-center gap-2"><input type="checkbox" name="child_events_1[]" value="基础-长拳"> 基础-长拳</label>
                                        <label class="flex items-center gap-2"><input type="checkbox" name="child_events_1[]" value="基础-南拳"> 基础-南拳</label>
                                        <label class="flex items-center gap-2"><input type="checkbox" name="child_events_1[]" value="初级-长拳"> 初级-长拳</label>
                                        <label class="flex items-center gap-2"><input type="checkbox" name="child_events_1[]" value="B组-长拳"> B组-长拳</label>
                                        <!-- Add more events as needed -->
                                    </div>
                                </div>
                            </div>

                            <!-- Schedule Selection -->
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500">Training Schedule 训练时间 *</label>
                                <div class="border border-slate-300 rounded-lg p-4 space-y-2">
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="child_schedule_1[]" value="WSA: Sun 10am-12pm">
                                        WSA - Sunday 10am-12pm
                                    </label>
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="child_schedule_1[]" value="WSA: Sun 12pm-2pm">
                                        WSA - Sunday 12pm-2pm
                                    </label>
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="child_schedule_1[]" value="PC2: Tue 8pm-10pm">
                                        PC2 - Tuesday 8pm-10pm
                                    </label>
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="child_schedule_1[]" value="PC2: Wed 8pm-10pm">
                                        PC2 - Wednesday 8pm-10pm
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Child Button -->
                <button type="button" onclick="addChild()" class="w-full mt-6 bg-gradient-to-r from-purple-600 to-blue-600 text-white py-4 rounded-xl font-bold text-lg hover:shadow-lg transition-all">
                    <i class="fas fa-plus-circle mr-2"></i> Add Another Child 添加另一个孩子
                </button>
            </div>

            <!-- STEP 3: Terms & Signature -->
            <div id="step-3" class="step-content">
                <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-file-signature text-amber-500"></i> 条款与协议 Agreement
                </h2>
                
                <div class="bg-white border border-slate-200 rounded-xl p-4 h-64 overflow-y-auto custom-scroll mb-6 text-xs">
                    <h4 class="font-bold text-center mb-4">📋 TERMS & CONDITIONS 条款与条件</h4>
                    <ol class="space-y-3">
                        <li><strong>1.</strong> All information provided is true and accurate 所有资料属实</li>
                        <li><strong>2.</strong> Wushu is a high-intensity sport; risks are acknowledged 武术是剧烈运动，风险自负</li>
                        <li><strong>3.</strong> Academy may adjust training times/venues 学院有权调整训练时间/地点</li>
                        <li><strong>4.</strong> Fees are non-refundable 学费不退还</li>
                        <li><strong>5.</strong> Must follow all Academy rules and coach instructions 遵守学院规则及教练指示</li>
                        <!-- Add all 15 terms here -->
                    </ol>
                </div>

                <div class="bg-slate-50 border border-slate-200 rounded-xl p-5">
                    <label class="text-xs font-bold text-slate-500 mb-2 block">Parent's Signature (Sign Below) *</label>
                    <div id="sig-wrapper" class="sig-box">
                        <canvas id="sig-canvas" width="700" height="200"></canvas>
                    </div>
                    <button type="button" onclick="clearSignature()" class="mt-2 bg-red-100 text-red-600 px-4 py-2 rounded text-sm font-bold">
                        <i class="fas fa-eraser"></i> Clear
                    </button>
                </div>
            </div>

            <!-- STEP 4: Payment -->
            <div id="step-4" class="step-content">
                <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-credit-card text-amber-500"></i> 学费缴付 Fee Payment
                </h2>

                <!-- Fee Summary -->
                <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-400 rounded-xl p-6 mb-6">
                    <h3 class="font-bold text-amber-900 text-lg mb-4">📊 Fee Summary 费用摘要</h3>
                    <div id="fee-breakdown" class="space-y-2 mb-4">
                        <!-- Will be populated dynamically -->
                    </div>
                    <div class="border-t-2 border-amber-200 pt-3 mt-3">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold">Total Amount 总额:</span>
                            <span class="text-3xl font-bold text-amber-600" id="total-amount">RM 0</span>
                        </div>
                    </div>
                </div>

                <!-- Bank Details -->
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 mb-6">
                    <h3 class="font-bold mb-4">🏦 Bank Details 银行详情</h3>
                    <div class="space-y-2 text-sm">
                        <p><strong>Bank:</strong> Maybank</p>
                        <p><strong>Account Name:</strong> Wushu Sport Academy</p>
                        <p><strong>Account Number:</strong> <span class="text-lg font-mono">5621 2345 6789</span></p>
                    </div>
                </div>

                <!-- Upload Receipt -->
                <div class="bg-white border-2 border-slate-200 rounded-xl p-5">
                    <h3 class="font-bold mb-4">📄 Upload Payment Receipt *</h3>
                    <input type="date" id="payment-date" class="w-full p-3 border rounded-lg mb-4" required>
                    <div class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center">
                        <input type="file" id="receipt-upload" accept="image/*,.pdf" class="hidden" onchange="handleReceiptUpload(event)">
                        <i class="fas fa-cloud-upload text-4xl text-slate-400 mb-3"></i>
                        <p class="text-sm mb-2">Click to Upload Receipt</p>
                        <button type="button" onclick="document.getElementById('receipt-upload').click()" class="bg-slate-800 text-white px-6 py-2 rounded-lg">
                            Choose File
                        </button>
                    </div>
                </div>
            </div>

            <!-- STEP 5: Success -->
            <div id="step-5" class="step-content">
                <div class="text-center py-12">
                    <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-check-circle text-green-600 text-5xl"></i>
                    </div>
                    <h2 class="text-3xl font-bold mb-4">Registration Successful! 报名成功！</h2>
                    <p class="text-slate-600 mb-6" id="success-message">All children have been registered successfully.</p>
                    <button type="button" onclick="window.location.href='register-multi.php'" class="bg-purple-600 text-white px-8 py-3 rounded-xl font-bold">
                        <i class="fas fa-plus-circle mr-2"></i> Register More Children
                    </button>
                </div>
            </div>

        </form>
    </div>

    <!-- Footer Navigation -->
    <div style="padding: 24px; background: white; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between;">
        <button id="btn-prev" onclick="previousStep()" class="px-6 py-3 rounded-xl font-semibold text-slate-600" disabled>
            ← Back
        </button>
        <button id="btn-next" onclick="nextStep()" class="bg-slate-800 text-white px-8 py-3 rounded-xl font-semibold">
            Next Step <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" class="hidden fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50">
    <div class="text-center text-white">
        <div class="w-16 h-16 border-4 border-white border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
        <h3 class="text-xl font-bold">Processing Registration...</h3>
        <p class="text-sm mt-2">正在处理报名 · Please wait</p>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
let currentStep = 1;
let totalSteps = 5;
let childCount = 1;
let signatureCanvas, signatureCtx;
let isDrawing = false;

// Initialize signature canvas
window.onload = function() {
    signatureCanvas = document.getElementById('sig-canvas');
    if (signatureCanvas) {
        signatureCtx = signatureCanvas.getContext('2d');
        signatureCtx.strokeStyle = '#000';
        signatureCtx.lineWidth = 2;
        
        signatureCanvas.addEventListener('mousedown', startDrawing);
        signatureCanvas.addEventListener('mousemove', draw);
        signatureCanvas.addEventListener('mouseup', stopDrawing);
        signatureCanvas.addEventListener('mouseout', stopDrawing);
    }
    
    setupICListeners();
};

function startDrawing(e) {
    isDrawing = true;
    signatureCtx.beginPath();
    signatureCtx.moveTo(e.offsetX, e.offsetY);
}

function draw(e) {
    if (!isDrawing) return;
    signatureCtx.lineTo(e.offsetX, e.offsetY);
    signatureCtx.stroke();
}

function stopDrawing() {
    isDrawing = false;
}

function clearSignature() {
    signatureCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
}

// Setup IC auto-age calculation
function setupICListeners() {
    document.querySelectorAll('.child-ic').forEach(input => {
        input.addEventListener('input', function() {
            const ic = this.value;
            const ageField = this.closest('.child-block').querySelector('.child-age');
            if (ic.length >= 6) {
                const year = parseInt('20' + ic.substring(0, 2));
                const age = 2026 - year;
                ageField.value = age;
            }
        });
    });
}

// Add new child
function addChild() {
    childCount++;
    const template = document.querySelector('.child-block').cloneNode(true);
    template.setAttribute('data-child-index', childCount);
    template.querySelector('h3 span').textContent = childCount;
    template.querySelector('h3').childNodes[2].textContent = ` Child ${childCount} 第${childCount}个孩子`;
    template.querySelector('.remove-child-btn').classList.remove('hidden');
    
    // Clear all inputs
    template.querySelectorAll('input, select').forEach(input => {
        if (input.type === 'checkbox') {
            input.checked = false;
        } else {
            input.value = '';
        }
    });
    
    document.getElementById('children-container').appendChild(template);
    setupICListeners();
}

// Remove child
function removeChild(btn) {
    if (childCount > 1) {
        btn.closest('.child-block').remove();
        childCount--;
        updateChildNumbers();
    }
}

function updateChildNumbers() {
    document.querySelectorAll('.child-block').forEach((block, index) => {
        block.setAttribute('data-child-index', index + 1);
        block.querySelector('h3 span').textContent = index + 1;
        block.querySelector('h3').childNodes[2].textContent = ` Child ${index + 1} 第${index + 1}个孩子`;
    });
}

// Step navigation
function nextStep() {
    if (validateCurrentStep()) {
        currentStep++;
        if (currentStep > totalSteps) currentStep = totalSteps;
        updateStepDisplay();
    }
}

function previousStep() {
    currentStep--;
    if (currentStep < 1) currentStep = 1;
    updateStepDisplay();
}

function updateStepDisplay() {
    document.querySelectorAll('.step-content').forEach(step => step.classList.remove('active'));
    document.getElementById(`step-${currentStep}`).classList.add('active');
    
    document.getElementById('step-counter').innerHTML = `0${currentStep}<span style="color: #475569; font-size: 14px;">/0${totalSteps}</span>`;
    document.getElementById('progress-bar').style.width = `${(currentStep / totalSteps) * 100}%`;
    
    document.getElementById('btn-prev').disabled = currentStep === 1;
    document.getElementById('btn-next').textContent = currentStep === totalSteps ? 'View Success' : 'Next Step ➔';
    
    if (currentStep === 4) {
        calculateFees();
    }
}

function validateCurrentStep() {
    // Add validation logic here
    return true;
}

function calculateFees() {
    const children = document.querySelectorAll('.child-block');
    let totalFee = 0;
    let breakdown = '';
    
    children.forEach((child, index) => {
        const name = child.querySelector('input[name="child_name_en[]"]').value;
        const schedules = child.querySelectorAll(`input[name="child_schedule_${index + 1}[]"]:checked`);
        const classCount = schedules.length;
        
        let fee = 0;
        if (classCount === 1) fee = 120;
        else if (classCount === 2) fee = 200;
        else if (classCount === 3) fee = 280;
        else if (classCount >= 4) fee = 320;
        
        totalFee += fee;
        breakdown += `<div class="flex justify-between text-sm"><span>${name} (${classCount} classes)</span><span class="font-semibold">RM ${fee}</span></div>`;
    });
    
    document.getElementById('fee-breakdown').innerHTML = breakdown;
    document.getElementById('total-amount').textContent = `RM ${totalFee}`;
}

function handleReceiptUpload(event) {
    const file = event.target.files[0];
    if (file) {
        alert('Receipt uploaded: ' + file.name);
    }
}

// Form submission
async function submitRegistration() {
    document.getElementById('loading-overlay').classList.remove('hidden');
    
    // Collect all data and submit
    // Implementation details...
    
    setTimeout(() => {
        document.getElementById('loading-overlay').classList.add('hidden');
        currentStep = 5;
        updateStepDisplay();
    }, 2000);
}

// CSS for active step
document.head.insertAdjacentHTML('beforeend', `
<style>
.step-content { display: none; }
.step-content.active { display: block; }
.sig-box { border: 2px solid #cbd5e1; border-radius: 12px; background: white; }
.custom-scroll::-webkit-scrollbar { width: 8px; }
.custom-scroll::-webkit-scrollbar-track { background: #f1f5f9; }
.custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
</style>
`);
</script>

</body>
</html>
