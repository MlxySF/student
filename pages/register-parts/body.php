<?php
// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));}
?>


<body>

<div class="glass-card">
    
    <!-- Header -->
    <div style="background: #1e293b; color: white; padding: 24px; border-bottom: 4px solid #fbbf24;">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 8px;">
            <div>
                <h1 style="font-size: 24px; font-weight: bold; background: linear-gradient(to right, #fde68a, #fbbf24); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 4px;">
                    2026 武术训练报名
                </h1>
                <p style="color: #94a3b8; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Official Registration Form</p>
            </div>
            <div style="color: #fbbf24; font-weight: bold; font-size: 20px;" id="step-counter">
                01<span style="color: #475569; font-size: 14px;">/07</span>
            </div>
        </div>
        <div style="width: 100%; background: #475569; height: 6px; border-radius: 999px; overflow: hidden; margin-top: 8px;">
            <div id="progress-bar" style="height: 100%; background: #fbbf24; transition: width 0.5s ease; width: 14.28%;"></div>
        </div>
    </div>

    <!-- Form Body -->
    <div style="padding: 32px; background: #f8fafc; max-height: 70vh; overflow-y: auto;" class="custom-scroll">
        <form id="regForm" onsubmit="return false;">
                        <!-- CSRF Token Hidden Field -->
            <input type="hidden" name="csrf_token" id="csrf-token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">


            <!-- STEP 1: Basic Info -->
            <div id="step-1" class="step-content active">
                <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-user-graduate text-amber-500"></i> 基本资料 Student Details
                </h2>
                <div class="space-y-6">
                    <!-- Name Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase">Chinese Name 中文名</label>
                            <input type="text" id="name-cn" class="w-full p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none" placeholder="张三">
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase">English Name 英文名 *</label>
                            <input type="text" id="name-en" class="w-full p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none" placeholder="Tan Ah Meng" required>
                        </div>
                    </div>

                    <!-- IC and Age Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase">IC Number 身份证号码 *</label>
                            <input type="text" id="ic" class="w-full p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none" placeholder="000000-00-0000" maxlength="14" required>
                            <p class="text-xs text-slate-400">Format: 000000-00-0000</p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase">Age 年龄 (2026)</label>
                            <input type="number" id="age" class="w-full p-3 rounded-xl border border-slate-300 bg-slate-100 text-slate-500 cursor-not-allowed outline-none" placeholder="Auto-calculated" readonly>
                            <p class="text-xs text-slate-400">
                                <i class="fas fa-info-circle mr-1"></i>Calculated from IC
                            </p>
                        </div>
                    </div>

                    <!-- School Row -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase">School 学校 *</label>
                        <select id="school" class="w-full p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none bg-white" required>
                            <option value="">Select School...</option>
                            <option value="SJK(C) PUAY CHAI 2">SJK(C) PUAY CHAI 2 (培才二校)</option>
                            <option value="SJK(C) Chee Wen">SJK(C) Chee Wen</option>
                            <option value="SJK(C) Subang">SJK(C) Subang</option>
                            <option value="SJK(C) Sin Ming">SJK(C) Sin Ming</option>
                            <option value="Others">Others (其他)</option>
                        </select>
                        <input type="text" id="school-other" class="hidden w-full mt-2 p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none" placeholder="Please specify school name">
                    </div>
                </div>
            </div>

            <!-- STEP 2: Contact -->
            <div id="step-2" class="step-content">
                <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-address-card text-amber-500"></i> 联系方式 Contact Info
                </h2>
                <div class="space-y-5">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase">Phone Number 电话号码 *</label>
                        <div class="relative">
                            <i class="fa-solid fa-phone absolute left-4 top-4 text-slate-400"></i>
                            <input type="tel" id="phone" class="w-full pl-10 p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none" placeholder="012-345 6789" maxlength="13" required>
                        </div>
                        <p class="text-xs text-slate-400">Format: 012-345 6789 or 011-2345 6789</p>
                    </div>

                    <!-- REMARK FOR ADDITIONAL CHILDREN -->
                        <div class="bg-blue-50 border-l-4 border-blue-500 p-3 mb-2 rounded-r-lg">
                            <p class="text-s text-blue-800 leading-relaxed">
                                <i class="fas fa-info-circle mr-1"></i>
                                <strong>Registering another child?</strong> Use the <strong>same parent email</strong> to link all your children under one parent account.
                            </p>
                            <p class="text-s text-blue-700 leading-relaxed mt-1">
                                <strong>注册另一个孩子？</strong>使用<strong>相同的家长电邮</strong>将所有孩子连接到一个家长账户。
                            </p>
                        </div>

                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase">Parent's Email 家长邮箱 *</label>
                        <div class="relative">
                            <i class="fa-solid fa-envelope absolute left-4 top-4 text-slate-400"></i>
                            <input type="email" id="email" class="w-full pl-10 p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none" placeholder="parent@example.com" required>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase">Student Status 身份 *</label>
                        <div class="grid grid-cols-3 gap-3">
                            <label class="cursor-pointer">
                                <input type="radio" name="status" value="Student 学生" class="status-radio" checked>
                                <div class="status-option p-3 text-center rounded-xl border border-slate-200 bg-white">
                                    Student<br>学生
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="status" value="State Team 州队" class="status-radio">
                                <div class="status-option p-3 text-center rounded-xl border border-slate-200 bg-white">
                                    State Team<br>州队
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="status" value="Backup Team 后备队" class="status-radio">
                                <div class="status-option p-3 text-center rounded-xl border border-slate-200 bg-white">
                                    Backup Team<br>后备队
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: Events (content unchanged, keeping original) -->
            <div id="step-3" class="step-content">
                <!-- Original content from previous code -->
            </div>

            <!-- STEP 4: Schedule (content unchanged, keeping original) -->
            <div id="step-4" class="step-content">
                <!-- Original content from previous code -->
            </div>

            <!-- STEP 5: Terms & Signature (content unchanged, keeping original) -->
            <div id="step-5" class="step-content">
                <!-- Original content from previous code -->
            </div>
            
            <!-- STEP 6: Payment (content unchanged, keeping original) -->
            <div id="step-6" class="step-content">
                <!-- Original content from previous code -->
            </div>

<!-- ✨ NEW STEP 7: Summary Confirmation -->
<div id="step-7" class="step-content">
    <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
        <i class="fa-solid fa-clipboard-check text-amber-500"></i> 确认资料 Confirmation Summary
    </h2>

    <div class="space-y-4">
        <!-- Student Details Summary -->
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <h3 class="font-bold text-slate-700 mb-3 text-sm uppercase flex items-center gap-2">
                <i class="fa-solid fa-user text-blue-600"></i> Student Information
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <div>
                    <span class="text-slate-500">Name:</span>
                    <span class="font-semibold text-slate-800" id="summary-name">-</span>
                </div>
                <div>
                    <span class="text-slate-500">IC Number:</span>
                    <span class="font-semibold text-slate-800" id="summary-ic">-</span>
                </div>
                <div>
                    <span class="text-slate-500">Age:</span>
                    <span class="font-semibold text-slate-800" id="summary-age">-</span>
                </div>
                <div>
                    <span class="text-slate-500">School:</span>
                    <span class="font-semibold text-slate-800" id="summary-school">-</span>
                </div>
                <div>
                    <span class="text-slate-500">Status:</span>
                    <span class="font-semibold text-slate-800" id="summary-status">-</span>
                </div>
                <div>
                    <span class="text-slate-500">Phone:</span>
                    <span class="font-semibold text-slate-800" id="summary-phone">-</span>
                </div>
                <div class="md:col-span-2">
                    <span class="text-slate-500">Email:</span>
                    <span class="font-semibold text-slate-800" id="summary-email">-</span>
                </div>
            </div>
        </div>

        <!-- Events Summary -->
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <h3 class="font-bold text-slate-700 mb-3 text-sm uppercase flex items-center gap-2">
                <i class="fa-solid fa-trophy text-purple-600"></i> Selected Events
            </h3>
            <div id="summary-events" class="text-sm text-slate-800">
                <span class="text-slate-400 italic">No events selected</span>
            </div>
        </div>

        <!-- Schedule Summary -->
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <h3 class="font-bold text-slate-700 mb-3 text-sm uppercase flex items-center gap-2">
                <i class="fa-regular fa-calendar-check text-green-600"></i> Training Schedule
            </h3>
            <div id="summary-schedule" class="text-sm text-slate-800">
                <span class="text-slate-400 italic">No schedule selected</span>
            </div>
        </div>

        <!-- Payment Summary -->
        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-400 rounded-xl p-5">
            <h3 class="font-bold text-amber-900 mb-3 text-sm uppercase flex items-center gap-2">
                <i class="fa-solid fa-credit-card"></i> Payment Details
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm mb-3">
                <div>
                    <span class="text-slate-600">Classes Selected:</span>
                    <span class="font-bold text-slate-800" id="summary-class-count">0</span>
                </div>
                <div>
                    <span class="text-slate-600">Payment Date:</span>
                    <span class="font-semibold text-slate-800" id="summary-payment-date">-</span>
                </div>
            </div>
            <div class="border-t-2 border-amber-200 pt-3">
                <div class="flex justify-between items-center">
                    <span class="text-base font-bold text-slate-800">Total Amount:</span>
                    <span class="text-2xl font-bold text-amber-600" id="summary-total">RM 0</span>
                </div>
            </div>
        </div>

        <!-- Parent Signature Confirmation -->
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-5">
            <h3 class="font-bold text-slate-700 mb-3 text-sm uppercase flex items-center gap-2">
                <i class="fa-solid fa-file-signature text-indigo-600"></i> Legal Declaration
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm mb-3">
                <div>
                    <span class="text-slate-500">Parent Name:</span>
                    <span class="font-semibold text-slate-800" id="summary-parent-name">-</span>
                </div>
                <div>
                    <span class="text-slate-500">Parent IC:</span>
                    <span class="font-semibold text-slate-800" id="summary-parent-ic">-</span>
                </div>
            </div>
            <div class="text-xs text-slate-600 bg-white rounded-lg p-3 border border-slate-200">
                <i class="fas fa-check-circle text-green-600 mr-1"></i>
                <span>Signature received and terms accepted</span>
            </div>
        </div>

        <!-- Confirmation Checkbox -->
        <div class="bg-amber-50 border-2 border-amber-400 rounded-xl p-5 mt-6">
            <label class="flex items-start gap-3 cursor-pointer group">
                <input type="checkbox" id="confirm-details" class="w-5 h-5 mt-1 text-amber-600 border-2 border-amber-400 rounded focus:ring-2 focus:ring-amber-500 cursor-pointer" required>
                <div class="flex-1">
                    <p class="font-bold text-slate-800 text-sm mb-1 group-hover:text-amber-700 transition-colors">
                        <i class="fas fa-check-double text-amber-600"></i> I confirm all details are accurate *
                    </p>
                    <p class="text-xs text-slate-700 leading-relaxed">
                        本人确认以上所有资料准确无误，并同意提交此报名表格。一旦提交，资料将无法更改。
                    </p>
                    <p class="text-xs text-slate-600 leading-relaxed mt-1">
                        I confirm that all the information above is accurate and agree to submit this registration form. Once submitted, the information cannot be changed.
                    </p>
                </div>
            </label>
        </div>
    </div>
</div>

<!-- STEP 8: Success (renamed from step-7) -->
<div id="step-8" class="step-content">
    <div style="text-align: center; padding: 48px 0;">
        <div style="margin-bottom: 24px;">
            <div style="width: 96px; height: 96px; background: #dcfce7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i class="fas fa-check-circle" style="color: #16a34a; font-size: 48px;"></i>
            </div>
            <h2 style="font-size: 28px; font-weight: bold; color: #1e293b; margin-bottom: 8px;">Registration Successful!</h2>
            <p style="color: #64748b; font-size: 18px; margin-bottom: 4px;">报名成功！</p>
            <p style="color: #94a3b8; font-size: 14px;" id="reg-number-display"></p>
        </div>
        
        <div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 24px; border-radius: 0 12px 12px 0; margin-bottom: 32px; max-width: 600px; margin-left: auto; margin-right: auto; text-align: left;">
            <h3 style="font-weight: bold; color: #1e40af; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-info-circle"></i>
                What's Next? 接下来做什么？
            </h3>
            <ul style="font-size: 14px; color: #1e40af; line-height: 1.8; padding-left: 20px;">
                <li>Your registration and payment have been submitted 您的报名及付款已提交</li>
                <li>Admin will review your payment receipt 管理员将审核您的付款收据</li>
                <li>You will receive account credentials via email 您将通过电子邮件收到账户凭证</li>
                <li>Login to student portal to track your progress 登录学生门户跟踪您的进度</li>
            </ul>
        </div>

        <div style="display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; margin-bottom: 32px;">
            <button type="button" onclick="downloadPDF()" style="background: #16a34a; color: white; padding: 16px 32px; border-radius: 12px; font-weight: bold; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3); border: none; cursor: pointer; display: flex; align-items: center; gap: 12px; transition: transform 0.2s;">
                <i class="fas fa-download" style="font-size: 20px;"></i>
                <div style="text-align: left;">
                    <div>Download Signed Agreement</div>
                    <div style="font-size: 12px; font-weight: normal;">下载已签协议 PDF</div>
                </div>
            </button>
            <button type="button" onclick="submitAnother()" style="background: linear-gradient(to right, #7c3aed, #6d28d9); color: white; padding: 16px 32px; border-radius: 12px; font-weight: bold; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3); border: none; cursor: pointer; display: flex; align-items: center; gap: 12px; transition: transform 0.2s;">
                <i class="fas fa-plus-circle" style="font-size: 20px;"></i>
                <div style="text-align: left;">
                    <div>Submit Another</div>
                    <div style="font-size: 12px; font-weight: normal;">提交另一份报名</div>
                </div>
            </button>
        </div>

        <!-- Login Button - Compact Dark Sleek Design -->
<div style="position: fixed; bottom: 24px; right: 24px; z-index: 1000;">
    <a href="../index.php" style="
        background: #1e293b;
        color: white;
        padding: 12px 20px;
        border-radius: 16px;
        font-weight: 600;
        box-shadow: 0 6px 20px rgba(30, 41, 59, 0.5);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 2px solid #fbbf24;
        font-size: 14px;
    " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 10px 30px rgba(30, 41, 59, 0.6);'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 6px 20px rgba(30, 41, 59, 0.5)';">
        <div style="
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        ">
            <i class="fas fa-arrow-right" style="font-size: 14px; color: #1e293b;"></i>
        </div>
        <div style="text-align: left;">
            <div style="font-size: 13px; font-weight: 700; line-height: 1.3;">Parent Login</div>
            <div style="font-size: 10px; color: #fbbf24; font-weight: 500;">家长登录 →</div>
        </div>
    </a>
</div>

    </div>
</div>


        </form>
    </div>

    <!-- Footer buttons -->
    <div style="padding: 24px; background: white; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
        <button id="btn-prev" onclick="changeStep(-1)" style="padding: 10px 24px; border-radius: 12px; font-weight: 600; color: #64748b; background: transparent; border: none; cursor: pointer; transition: background 0.2s;" disabled>
            ← Back
        </button>
        <button id="btn-next" onclick="changeStep(1)" style="background: #1e293b; color: white; padding: 10px 32px; border-radius: 12px; font-weight: 600; box-shadow: 0 4px 12px rgba(30, 41, 59, 0.3); border: none; cursor: pointer; transition: all 0.2s;">
            Next Step <i class="fa-solid fa-arrow-right"></i>
        </button>
    </div>
</div>

<!-- HIDDEN PDF TEMPLATES (keeping original) -->
<!-- PDF Template Page 1 and 2 content unchanged -->

<!-- LOADING OVERLAY (keeping original) -->
<div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center;">
    <div style="text-align: center; color: white;">
        <div style="width: 60px; height: 60px; border: 5px solid rgba(255,255,255,0.3); border-top: 5px solid white; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
        <h3 style="font-size: 20px; margin: 0;">Processing Registration...</h3>
        <p style="margin-top: 10px; font-size: 14px; opacity: 0.8;">正在处理报名 · Please wait</p>
    </div>
</div>



</body>