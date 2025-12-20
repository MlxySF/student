<?php
// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
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
                01<span style="color: #475569; font-size: 14px;">/06</span>
            </div>
        </div>
        <div style="width: 100%; background: #475569; height: 6px; border-radius: 999px; overflow: hidden; margin-top: 8px;">
            <div id="progress-bar" style="height: 100%; background: #fbbf24; transition: width 0.5s ease; width: 16.66%;"></div>
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

            <!-- Continue with remaining steps exactly as is... -->
            <!-- STEP 3: Events (keeping original code) -->