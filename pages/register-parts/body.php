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
                    
                    <!-- PASSWORD SELECTION SECTION -->
<div class="bg-purple-50 border-l-4 border-purple-500 p-4 mb-2 rounded-r-lg mt-5">
    <p class="text-sm text-purple-800 leading-relaxed font-semibold mb-2">
        <i class="fas fa-lock mr-1"></i> Parent Account Password Setup 家长账户密码设置
    </p>
    <p class="text-xs text-purple-700 leading-relaxed">
        Choose how you want to set up your login password for the parent portal.
        <br>选择您想要如何设置家长门户的登录密码。
    </p>
</div>

<!-- EXISTING PARENT INFO (Hidden by default) -->
<div id="existing-parent-info" class="bg-green-50 border-l-4 border-green-500 p-4 mb-2 rounded-r-lg mt-5 hidden">
    <p class="text-sm text-green-800 leading-relaxed font-semibold mb-2">
        <i class="fas fa-check-circle mr-1"></i> Existing Parent Account Detected 检测到现有家长账户
    </p>
    <div id="existing-parent-details" class="text-xs text-green-700 leading-relaxed">
        <!-- Will be populated by JavaScript -->
    </div>
    <p class="text-xs text-green-700 leading-relaxed mt-2">
        <i class="fas fa-info-circle mr-1"></i> You'll use your existing password to login. This child will be added to your account.
        <br>您将使用现有密码登录。此孩子将添加到您的账户中。
    </p>
</div>

<!-- NEW PARENT INFO (Hidden by default) -->
<div id="new-parent-info" class="bg-purple-50 border-l-4 border-purple-500 p-4 mb-2 rounded-r-lg mt-5 hidden">
    <p class="text-sm text-purple-800 leading-relaxed font-semibold mb-2">
        <i class="fas fa-user-plus mr-1"></i> New Parent Account 新家长账户
    </p>
    <p class="text-xs text-purple-700 leading-relaxed">
        This email is not registered. We'll create a new parent account for you.
        <br>此邮箱未注册。我们将为您创建新的家长账户。
    </p>
</div>

<!-- PASSWORD SELECTOR (Only shown for new parents) -->
<div id="password-selector-container" class="hidden">
    <div class="space-y-2">
        <label class="text-xs font-bold text-slate-500 uppercase">Password Option 密码选项 *</label>
        <select id="password-type" class="w-full p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none bg-white" required>
            <option value="">Select password option...</option>
            <option value="ic_last4">Use Parent IC Last 4 Digits (Default) 使用家长身份证最后4位</option>
            <option value="custom">Set Custom Password 设置自定义密码</option>
        </select>
    </div>

    <!-- Custom Password Input (Hidden by default) -->
    <div id="custom-password-container" class="space-y-2 hidden">
        <label class="text-xs font-bold text-slate-500 uppercase">Custom Password 自定义密码 *</label>
        <div class="relative">
            <i class="fa-solid fa-key absolute left-4 top-4 text-slate-400"></i>
            <input type="password" id="custom-password" class="w-full pl-10 p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none" placeholder="Enter your password" minlength="6">
        </div>
        <p class="text-xs text-slate-400">Minimum 6 characters 至少6个字符</p>
    </div>

    <div id="custom-password-confirm-container" class="space-y-2 hidden">
        <label class="text-xs font-bold text-slate-500 uppercase">Confirm Password 确认密码 *</label>
        <div class="relative">
            <i class="fa-solid fa-key absolute left-4 top-4 text-slate-400"></i>
            <input type="password" id="custom-password-confirm" class="w-full pl-10 p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none" placeholder="Confirm your password">
        </div>
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

            <!-- STEP 3: Events -->
            <div id="step-3" class="step-content">
                <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-trophy text-amber-500"></i> 项目选择 Event Selection
                </h2>
                
                <p class="text-sm text-slate-600 mb-4">Select events for each level (You can select multiple events across different levels)</p>

                <div class="space-y-4">
                    <!-- Basic Level -->
                    <div class="border-l-4 border-slate-700 bg-slate-50 rounded-r-xl p-4 basic-routines">
                        <h3 class="font-bold text-slate-800 mb-3">基础 Basic</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="基础-长拳" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">长拳</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="基础-南拳" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">南拳</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="基础-太极拳" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">太极拳</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="基础-剑" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">剑</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="基础-枪" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">枪</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="基础-刀" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">刀</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="基础-棍" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">棍</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="基础-南刀" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">南刀</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="基础-南棍" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">南棍</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="基础-太极剑" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">太极剑</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="基础-太极扇" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">太极扇</span>
                            </label>
                        </div>
                    </div>

                    <!-- Junior Level -->
                    <div class="border-l-4 border-blue-600 bg-blue-50 rounded-r-xl p-4">
                        <h3 class="font-bold text-blue-800 mb-3">初级 Junior</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="初级-长拳" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">长拳</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="初级-南拳" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">南拳</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="初级-太极拳" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">太极拳</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="初级-剑" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">剑</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="初级-枪" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">枪</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="初级-刀" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">刀</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="初级-棍" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">棍</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="初级-南刀" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">南刀</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="初级-南棍" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">南棍</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="初级-太极剑" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">太极剑</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="初级-太极扇" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">太极扇</span>
                            </label>
                        </div>
                    </div>

                    <!-- Group B -->
                    <div class="border-l-4 border-green-600 bg-green-50 rounded-r-xl p-4">
                        <h3 class="font-bold text-green-800 mb-3">B组 Group B</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="B组-长拳" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">长拳</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="B组-南拳" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">南拳</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="B组-太极拳" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">太极拳</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="B组-剑" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">剑</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="B组-枪" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">枪</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="B组-刀" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">刀</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="B组-棍" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">棍</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="B组-南刀" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">南刀</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="B组-南棍" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">南棍</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="B组-太极剑" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">太极剑</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="B组-太极扇" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">太极扇</span>
                            </label>
                        </div>
                    </div>

                    <!-- Group A -->
                    <div class="border-l-4 border-purple-600 bg-purple-50 rounded-r-xl p-4">
                        <h3 class="font-bold text-purple-800 mb-3">A组 Group A</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="A组-长拳" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">长拳</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="A组-南拳" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">南拳</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="A组-太极拳" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">太极拳</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="A组-剑" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">剑</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="A组-枪" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">枪</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="A组-刀" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">刀</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="A组-棍" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">棍</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="A组-南刀" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">南刀</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="A组-南棍" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">南棍</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="A组-太极剑" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">太极剑</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="A组-太极扇" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">太极扇</span>
                            </label>
                        </div>
                    </div>

                    <!-- Optional Level -->
                    <div class="border-l-4 border-amber-600 bg-amber-50 rounded-r-xl p-4">
                        <h3 class="font-bold text-amber-800 mb-3">自选 Optional</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="自选-长拳" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">长拳</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="自选-南拳" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">南拳</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="自选-太极拳" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">太极拳</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="自选-剑" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">剑</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="自选-枪" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">枪</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="自选-刀" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">刀</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="自选-棍" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">棍</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="自选-南刀" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">南刀</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="自选-南棍" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">南棍</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="自选-太极剑" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">太极剑</span>
                            </label>
                            <label class="cursor-pointer flex items-center gap-2">
                                <input type="checkbox" name="evt" value="自选-太极扇" class="w-4 h-4 text-amber-500 rounded">
                                <span class="text-sm">太极扇</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 4: Schedule -->
<div id="step-4" class="step-content">
    <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
        <i class="fa-regular fa-calendar-check text-amber-500"></i> 训练时间 Training Schedule
    </h2>

    <!-- Fee Info -->
    <div class="bg-amber-50 text-amber-900 p-4 rounded-xl text-xs mb-8 border border-amber-100">
        <p class="font-bold mb-1 text-m" style="font-size: 16px;"><i class="fas fa-info-circle"></i> 注明 (Remark)：州队运动员需至少选择 两堂课。</p>
        <p style="font-size: 15px;">• 选择 一堂课：收费 <strong>RM 120</strong></p>
        <p style="font-size: 15px;">• 选择 二堂课：收费 <strong>RM 200</strong></p>
        <p style="font-size: 15px;">• 选择 三堂课：收费 <strong>RM 280</strong></p>
        <p style="font-size: 15px;">• 选择 四堂课：收费 <strong>RM 320</strong></p>
        <p class="font-bold mt-1" style="font-size: 16px;"><br>State team athletes must choose at least two classes.</p>
        <p style="font-size: 15px;">• Choose one class: <strong>RM 120</strong></p>
        <p style="font-size: 15px;">• Choose two classes: <strong>RM 200</strong></p>
        <p style="font-size: 15px;">• Choose three classes: <strong>RM 280</strong></p>
        <p style="font-size: 15px;">• Choose four classes: <strong>RM 320</strong></p>
    </div>

    <div class="space-y-4">
        <!-- SCHOOL 1: Wushu Sport Academy -->
        <div class="school-box" onclick="toggleSchoolBox(this)">
            <div class="school-header">
                <div class="school-info">
                    <img src="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/Wushu+Sport+Academy+Circle+Yellow.png" alt="WSA Logo" class="school-logo">
                    <div class="school-text">
                        <h3>
                            <i class="fas fa-map-marker-alt" style="color: #fbbf24;"></i>
                            Wushu Sport Academy 武术体育学院
                        </h3>
                        <p><i class="fas fa-location-dot" style="color: #94a3b8;"></i> No. 2, Jalan BP 5/6, Bandar Bukit Puchong, 47120 Puchong, Selangor</p>
                    </div>
                </div>
                <div class="school-toggle">
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
            <div class="school-schedules">
                <div class="school-schedules-inner">
                    <div class="space-y-3">
                        <label class="custom-checkbox border-2 border-slate-200 rounded-xl hover:border-amber-400 hover:bg-amber-50/30 transition-all" data-schedule="wsa-wed-8pm">
                            <input type="checkbox" name="sch" value="Wushu Sport Academy: Wed 8pm-10pm">
                            <div class="custom-checkbox-label">
                                <div class="text-sm font-bold text-slate-800 mb-1">
                                    <i class="far fa-calendar mr-2 text-amber-500"></i>Wednesday 星期三 · 8:00 PM - 10:00 PM
                                </div>
                                <div class="text-xs text-slate-600">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-md font-semibold">(C 和 太极套路)</span>
                                </div>
                            </div>
                        </label>
                        <label class="custom-checkbox border-2 border-slate-200 rounded-xl hover:border-amber-400 hover:bg-amber-50/30 transition-all" data-schedule="wsa-sun-10am">
                            <input type="checkbox" name="sch" value="Wushu Sport Academy: Sun 10am-12pm">
                            <div class="custom-checkbox-label">
                                <div class="text-sm font-bold text-slate-800 mb-1">
                                    <i class="far fa-calendar mr-2 text-amber-500"></i>Sunday 星期日 · 10:00 AM - 12:00 PM
                                </div>
                                <div class="text-xs text-slate-600">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-md font-semibold">只限于州队/后备队 Only for State/Backup Team</span>
                                </div>
                                <div class="text-xs text-slate-600">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-md font-semibold">(A/B/C/D 传统和太极套路)</span>
                                </div>
                            </div>
                        </label>

                        <label class="custom-checkbox border-2 border-slate-200 rounded-xl hover:border-amber-400 hover:bg-amber-50/30 transition-all" data-schedule="wsa-sun-1pm">
                            <input type="checkbox" name="sch" value="Wushu Sport Academy: Sun 1pm-3pm">
                            <div class="custom-checkbox-label">
                                <div class="text-sm font-bold text-slate-800">
                                    <i class="far fa-calendar mr-2 text-amber-500"></i>Sunday 星期日 · 1:00 PM - 3:00 PM
                                </div>
                                <div class="text-xs text-slate-600">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-md font-semibold">(C/D 和太极套路)</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- SCHOOL 2: SJK(C) Puay Chai 2 -->
        <div class="school-box" onclick="toggleSchoolBox(this)">
            <div class="school-header">
                <div class="school-info">
                    <img src="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/PC2+Logo.png" alt="PC2 Logo" class="school-logo">
                    <div class="school-text">
                        <h3>
                            <i class="fas fa-map-marker-alt" style="color: #fbbf24;"></i>
                            SJK(C) Puay Chai 2 培才二校
                        </h3>
                        <p><i class="fas fa-location-dot" style="color: #94a3b8;"></i> Jln BU 3/1, Bandar Utama, 47800 Petaling Jaya, Selangor</p>
                    </div>
                </div>
                <div class="school-toggle">
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
            <div class="school-schedules">
                <div class="school-schedules-inner">
                    <div class="space-y-3">
                        <label class="custom-checkbox border-2 border-slate-200 rounded-xl hover:border-amber-400 hover:bg-amber-50/30 transition-all" data-schedule="pc2-tue-8pm">
                            <input type="checkbox" name="sch" value="SJK(C) Puay Chai 2: Tue 8pm-10pm">
                            <div class="custom-checkbox-label">
                                <div class="text-sm font-bold text-slate-800 mb-1">
                                    <i class="far fa-calendar mr-2 text-amber-500"></i>Tuesday 星期二 · 8:00 PM - 10:00 PM
                                </div>
                                <div class="text-xs text-slate-600">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-md font-semibold">只限于州队/后备队 Only for State/Backup Team</span>
                                </div>
                                <div class="text-xs text-slate-600">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-md font-semibold">(A/B/C 和 传统套路)</span>
                                </div>
                                <div class="text-[10px] text-red-500 font-bold hidden disabled-msg mt-1">
                                    <i class="fas fa-ban mr-1"></i>Not available for Normal Students 普通学生不允许参加
                                </div>
                            </div>
                        </label>

                        <label class="custom-checkbox border-2 border-slate-200 rounded-xl hover:border-amber-400 hover:bg-amber-50/30 transition-all" data-schedule="pc2-wed-8pm">
                            <input type="checkbox" name="sch" value="SJK(C) Puay Chai 2: Wed 8pm-10pm">
                            <div class="custom-checkbox-label">
                                <div class="text-sm font-bold text-slate-800 mb-1">
                                    <i class="far fa-calendar mr-2 text-amber-500"></i>Wednesday 星期三 · 8:00 PM - 10:00 PM
                                </div>
                                <div class="text-xs text-slate-600">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-md font-semibold">全部组别 All Groups (A/B/C/D 套路) 没有太极 和 没有传统</span>
                                </div>
                            </div>
                        </label>
                        <label class="custom-checkbox border-2 border-slate-200 rounded-xl hover:border-amber-400 hover:bg-amber-50/30 transition-all" data-schedule="pc2-fri-8pm">
                            <input type="checkbox" name="sch" value="SJK(C) Puay Chai 2: Wed 8pm-10pm">
                            <div class="custom-checkbox-label">
                                <div class="text-sm font-bold text-slate-800 mb-1">
                                    <i class="far fa-calendar mr-2 text-amber-500"></i>Friday 星期五 · 8:00 PM - 10:00 PM
                                </div>
                                <div class="text-xs text-slate-600">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-md font-semibold">太极套路而已</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


            <!-- STEP 5: Terms & Signature -->
            <div id="step-5" class="step-content">
                <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-file-signature text-amber-500"></i> 条款与协议 Agreement
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
                        <h4 class="font-bold text-blue-700 text-sm mb-1">学费缴付 · Fee Payment</h4>
                        <p class="text-xs text-blue-800 leading-relaxed">学费需在每月10号之前缴付，并将收据发送至教练与行政。</p>
                        <p class="text-xs text-blue-700 leading-relaxed mt-1">Fees must be paid before the 10th of every month, and the receipt must be sent to the coach and admin.</p>
                    </div>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                        <h4 class="font-bold text-red-700 text-sm mb-1">运动员守则 · Code of Conduct</h4>
                        <p class="text-xs text-red-800 leading-relaxed">严守纪律，必须守时，不允许在训练期间嬉戏；违者可能被取消资格。</p>
                        <p class="text-xs text-red-700 leading-relaxed mt-1">Athletes must be disciplined and punctual and are not allowed to play during training; violations may result in disqualification.</p>
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl p-4 md:p-5 h-64 md:h-56 overflow-y-auto custom-scroll mb-6 text-xs leading-relaxed">
                    <div class="flex items-center justify-center mb-4">
                        <h4 class="font-bold text-slate-800 text-sm">📋 TERMS & CONDITIONS 条款与条件</h4>
                    </div>
                    
                    <ol class="space-y-4">
                        <li class="flex items-start gap-2 md:gap-3">
                            <div class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">1</div>
                            <div class="space-y-1">
                                <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">本人（学员/家长/监护人）确认上述资料属实。</p>
                                <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">I, the student/parent/guardian, confirm that all information provided above is true and correct.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-2 md:gap-3">
                            <div class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">2</div>
                            <div class="space-y-1">
                                <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">本人明白武术是一项剧烈运动，并愿意自行承担训练期间可能发生的意外风险。</p>
                                <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">I understand that Wushu is a high‑intensity sport and agree to bear any risk of injury during training.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-2 md:gap-3">
                            <div class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">3</div>
                            <div class="space-y-1">
                                <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">学院有权在必要时调整训练时间或地点，并将提前通知。</p>
                                <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">The Academy reserves the right to adjust training times or venues when necessary and will notify in advance.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-2 md:gap-3">
                            <div class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">4</div>
                            <div class="space-y-1">
                                <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">学费一经缴付，概不退还（Non‑refundable）。</p>
                                <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">Fees paid are strictly non‑refundable under all circumstances.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-2 md:gap-3">
                            <div class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">5</div>
                            <div class="space-y-1">
                                <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">本人同意遵守学院及教练的所有指示与安排。</p>
                                <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">I agree to follow all instructions, rules, and arrangements set by the Academy and coaches.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-2 md:gap-3">
                            <div class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">6</div>
                            <div class="space-y-1">
                                <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">只限于本院通知取消课程，将会另行安排补课，家长不允许自行取消课程。</p>
                                <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">Replacement classes are only provided when the Academy cancels a session; parents may not cancel classes on their own.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-2 md:gap-3">
                            <div class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">7</div>
                            <div class="space-y-1">
                                <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">如学员因病或其他原因无法出席训练，必须向行政与教练申请请假；未经许可的缺席将被记录。</p>
                                <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">If the student cannot attend due to sickness or other reasons, leave must be applied for with admin and coach; unapproved absences will be recorded.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-2 md:gap-3">
                            <div class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">8</div>
                            <div class="space-y-1">
                                <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">州队及后备队必须出席所有训练，保持良好态度，接受严格训练与训导。</p>
                                <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">State‑team and reserve athletes must attend all training, maintain good attitude, and accept strict training and discipline.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-2 md:gap-3">
                            <div class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">9</div>
                            <div class="space-y-1">
                                <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">如因脚受伤、扭伤或生病，请勿勉强出席训练，后果自负。</p>
                                <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">Students with injuries or illness should not attend training; any consequences are at their own risk.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-2 md:gap-3">
                            <div class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">10</div>
                            <div class="space-y-1">
                                <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">本院不负责学员及家长的任何贵重财物。</p>
                                <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">The Academy is not responsible for any valuables belonging to students or parents.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-2 md:gap-3">
                            <div class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">11</div>
                            <div class="space-y-1">
                                <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">不允许打架、吵架、态度恶劣或不配合训练，否则将被取消州队及学员资格。</p>
                                <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">Fighting, quarrelling, poor attitude, or refusing to cooperate with training may result in removal from the state team and the Academy.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-2 md:gap-3">
                            <div class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">12</div>
                            <div class="space-y-1">
                                <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">训练期间不允许吃食物，只能在休息时间喝水。</p>
                                <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">Eating is not allowed during training; only drinking water during breaks is permitted.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-2 md:gap-3">
                            <div class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">13</div>
                            <div class="space-y-1">
                                <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">家长不允许干涉教练所安排的专业训练计划及纪律管理。</p>
                                <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">Parents are not allowed to interfere with professional training plans or discipline set by the coaches.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-2 md:gap-3">
                            <div class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">14</div>
                            <div class="space-y-1">
                                <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">家长必须准时载送孩子往返训练地点，并自行负责交通安全。</p>
                                <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">Parents must send and pick up their children on time and are fully responsible for transport safety.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-2 md:gap-3">
                            <div class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">15</div>
                            <div class="space-y-1">
                                <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">训练过程中，学员可能被录影或拍照作为宣传用途，如家长不允许，须以书面通知本院。</p>
                                <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">Training sessions may be recorded or photographed for publicity; parents who do not consent must inform the Academy in writing.</p>
                            </div>
                        </li>
                    </ol>
                </div>

                <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 mt-6">
                    <h4 class="font-bold text-slate-700 mb-4 text-sm uppercase">Legal Declaration</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="text-xs font-bold text-slate-500">Parent Name *</label>
                            <input type="text" id="parent-name" class="w-full p-2 border border-slate-300 rounded-lg text-sm bg-white" required>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500">Parent IC No. *</label>
                            <input type="text" id="parent-ic" class="w-full p-2 border border-slate-300 rounded-lg text-sm bg-white" placeholder="000000-00-0000" maxlength="14" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs font-bold text-slate-500">Effective Date</label>
                            <input type="text" id="today-date" class="w-full p-2 border border-slate-200 bg-slate-100 text-slate-500 rounded-lg text-sm" readonly>
                        </div>
                    </div>

                    <label class="text-xs font-bold text-slate-500 mb-2 block">Parent's Signature (Sign Below) *</label>
                    <div id="sig-wrapper" class="sig-box">
                        <div id="sig-placeholder">SIGN HERE</div>
                        <div class="absolute top-2 right-2 z-10">
                            <button type="button" onclick="clearSig()" class="bg-red-100 text-red-600 px-3 py-1 rounded text-xs font-bold hover:bg-red-200 cursor-pointer border-none">
                                <i class="fa-solid fa-eraser"></i> Clear
                            </button>
                        </div>
                    </div>
                    <!-- MANDATORY AGREEMENT CHECKBOX -->
    <div class="bg-amber-50 border-2 border-amber-400 rounded-xl p-4 mb-6 mt-8">
        <label class="flex items-start gap-3 cursor-pointer group">
            <input type="checkbox" id="terms-agreement" class="w-5 h-5 mt-1 text-amber-600 border-2 border-amber-400 rounded focus:ring-2 focus:ring-amber-500 cursor-pointer" required>
            <div class="flex-1">
                <p class="font-bold text-slate-800 text-sm mb-1 group-hover:text-amber-700 transition-colors">
                    <i class="fas fa-check-circle text-amber-600"></i> I agree to the Terms and Conditions *
                </p>
                <p class="text-xs text-slate-700 leading-relaxed">
                    本人已阅读并同意上述所有条款与条件，包括学费政策、运动员守则及免责声明。
                </p>
                <p class="text-xs text-slate-600 leading-relaxed mt-1">
                    I have read and agree to all the above terms and conditions, including the fee policy, code of conduct, and disclaimer.
                </p>
            </div>
        </label>
    </div>
                </div>
            </div>
            
            <!-- STEP 6: Payment -->
<div id="step-6" class="step-content">
    <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
        <i class="fa-solid fa-credit-card text-amber-500"></i> 学费缴付 Fee Payment
    </h2>

    <!-- Fee Calculation -->
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-400 rounded-xl p-6 mb-6">
        <h3 class="font-bold text-amber-900 text-lg mb-4 flex items-center gap-2">
            <i class="fas fa-calculator"></i> 应付学费 Total Fees
        </h3>
        <div class="bg-white rounded-lg p-4 mb-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-slate-600 text-sm">已选择课程数量 Selected Classes:</span>
                <span class="font-bold text-slate-800" id="payment-class-count">0</span>
            </div>
            <div class="flex justify-between items-center mb-2">
                <span class="text-slate-600 text-sm">学员身份 Student Status:</span>
                <span class="font-semibold text-slate-800" id="payment-status">-</span>
            </div>
            <div class="border-t-2 border-amber-200 pt-3 mt-3">
                <div class="flex justify-between items-center">
                    <span class="text-lg font-bold text-slate-800">应付总额 Total Amount:</span>
                    <span class="text-3xl font-bold text-amber-600" id="payment-total">RM 0</span>
                </div>
            </div>
        </div>
        <div class="bg-blue-50 border-l-4 border-blue-500 p-3 text-lg text-blue-800">
            <p class="font-semibold mb-1"><i class="fas fa-info-circle"></i> 收费标准:</p>
            <p>• 1 堂课: RM 120</p>
            <p>• 2 堂课: RM 200</p>
            <p>• 3 堂课: RM 280</p>
            <p>• 4 堂课或以上: RM 320</p>
            <p><br></p>
            <p class="font-semibold mb-1"><i class="fas fa-info-circle"></i> Fee Structure:</p>
            <p>• 1 class: RM 120</p>
            <p>• 2 classes: RM 200</p>
            <p>• 3 classes: RM 280</p>
            <p>• 4 classes: RM 320</p>
        </div>
    </div>

    <!-- Payment Method Selection -->
    <div class="bg-white border-2 border-slate-200 rounded-xl p-5 mb-6">
        <h3 class="font-bold text-slate-800 text-base mb-4 flex items-center gap-2" style="font-size: 17px;">
            <i class="fas fa-wallet text-green-600"></i> 选择付款方式 *
        </h3>
        
        <div class="space-y-2 mb-4">
            <label class="text-sm font-semibold text-slate-700 mb-2 block">
                Select Payment Method *
            </label>
            <select id="payment-method" class="w-full p-3 border border-slate-300 rounded-lg text-sm focus:border-amber-500 focus:outline-none" required onchange="togglePaymentMethod()">
                <option value="">-- Select Payment Method --</option>
                <option value="cash">Cash 现金</option>
                <option value="bank_transfer">Bank Transfer 银行转账</option>
            </select>
        </div>

        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 text-lg text-yellow-800">
            <p class="font-semibold mb-1 text-lg"><i class="fas fa-info-circle"></i> 付款说明:</p>
            <p>• <strong>现金:</strong> 训练时直接交给教练</p>
            <p>• <strong>银行转账:</strong> 转账至提供的银行账户并上传收据<br></p>
            <p><br></p>
            <p class="font-semibold mb-1"><i class="fas fa-info-circle"></i> Payment Instructions:</p>
            <p>• <strong>Cash:</strong> Pay directly to coach during training</p>
            <p>• <strong>Bank Transfer:</strong> Transfer to provided bank account and upload receipt</p>
        </div>
    </div>

    <!-- Bank Transfer Section (Hidden by default) -->
    <div id="bank-transfer-section" style="display: none;">
        <!-- Bank Details -->
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 mb-6">
            <h3 class="font-bold text-slate-800 text-base mb-4 flex items-center gap-2">
                <i class="fas fa-building-columns text-blue-600"></i> 银行详情 Bank Details
            </h3>
            <div class="space-y-3 text-sm">
                <div class="flex items-start gap-3 bg-white p-3 rounded-lg">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-bank text-blue-600 text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs text-slate-500 mb-1">Bank Name 银行名称</p>
                        <p class="font-bold text-slate-800">Maybank</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-white p-3 rounded-lg">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-user text-green-600 text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs text-slate-500 mb-1">Account Name 户口名称</p>
                        <p class="font-bold text-slate-800">Wushu Sport Academy</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-white p-3 rounded-lg">
                    <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-hashtag text-amber-600 text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs text-slate-500 mb-1">Account Number 户口号码</p>
                        <p class="font-bold text-slate-800 text-lg">5050 1981 6740</p>
                        <button onclick="copyAccountNumber()" class="text-xs text-blue-600 hover:text-blue-800 mt-1 flex items-center gap-1">
                            <i class="fas fa-copy"></i> Copy 复制
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Receipt -->
<div class="bg-white border-2 border-slate-200 rounded-xl p-5">
    <h3 class="font-bold text-slate-800 text-base mb-4 flex items-center gap-2">
        <i class="fas fa-receipt text-purple-600"></i> Upload Payment Receipt
    </h3>
    
    <div class="mb-4">
        <label class="block text-sm font-semibold text-slate-700 mb-2">Payment Date</label>
        <input type="date" id="payment-date" class="w-full p-3 border border-slate-300 rounded-lg text-sm">
    </div>

    <!-- Upload Area -->
    <div class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center hover:border-amber-400 transition-all cursor-pointer" 
         id="upload-area" 
         onclick="document.getElementById('receipt-upload').click()">
        
        <!-- Hidden file input -->
        <input type="file" 
               id="receipt-upload" 
               accept="image/*,.pdf" 
               class="hidden" 
               onchange="handleReceiptUpload(event)">
        
        <!-- Upload Prompt -->
        <div id="upload-prompt">
            <i class="fas fa-cloud-upload-alt text-4xl text-slate-400 mb-3"></i>
            <p class="text-sm font-semibold text-slate-700 mb-1">Click to Upload Receipt</p>
            <p class="text-xs text-slate-500">JPG, PNG, PDF (Max 5MB)</p>
            <button type="button" 
                    onclick="event.stopPropagation(); document.getElementById('receipt-upload').click()" 
                    class="mt-3 bg-slate-800 text-white px-6 py-2 rounded-lg text-sm font-semibold hover:bg-slate-700">
                Choose File
            </button>
        </div>

        <!-- Upload Preview -->
        <div id="upload-preview" class="hidden">
            <img id="preview-image" src="" class="max-w-full max-h-64 mx-auto mb-3 rounded-lg border border-slate-200">
            <p id="preview-filename" class="text-sm font-semibold text-slate-800 mb-2"></p>
            <button type="button" 
                    onclick="event.stopPropagation(); removeReceipt()" 
                    class="text-xs text-red-600 hover:text-red-800 font-semibold">
                <i class="fas fa-trash"></i> Remove
            </button>
        </div>
    </div>

    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mt-4 text-xs text-yellow-800">
        <p class="font-semibold mb-1">
            <i class="fas fa-exclamation-triangle"></i> Important Note
        </p>
        <p class="mt-1">Please ensure the receipt is clear and shows payment amount, date, and bank details.</p>
    </div>
</div>

    </div>

    <!-- Cash Payment Note (Hidden by default) -->
    <div id="cash-payment-note" style="display: none;">
        <div class="bg-green-50 border-2 border-green-400 rounded-xl p-6 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-money-bill-wave text-green-600 text-2xl"></i>
            </div>
            <h3 class="font-bold text-green-800 text-lg mb-2">现金付款<br>Cash Payment</h3>
            <p class="text-sm text-green-700 mb-3">
                Please pay <strong id="cash-amount" class="text-green-900">RM 0</strong> to Coach Lim Kim and your payment record card during the training session.
            </p>
            <p class="text-sm text-green-700">
                请在训练课程中把<strong class="text-green-900"> 现金</strong> 交给林金教练并且记录在 Payment Record Card。
            </p>
            <!-- <div class="bg-white border border-green-200 rounded-lg p-3 mt-4 text-xs text-green-800">
                <p class="font-semibold mb-1"><i class="fas fa-check-circle"></i> 重要提醒 :</p>
                <p>• 现金交给林金教练并且记录在 Record Card<br></p>
                <p>• 请在每月10号前缴付<br></p>
                <p class="font-semibold mb-1"><i class="fas fa-check-circle"></i> Important Reminder:</p>
                <p>• Pass your cash and record card to Coach Lim Kim for Cash Payments<br></p>
                <p>• Payment must be made by the 10th of every month</p>
                
            </div>-->
        </div>
    </div>
</div>



<!-- STEP 7: Success -->
<div id="step-7" class="step-content">
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

<!-- HIDDEN PDF TEMPLATE - PAGE 1 -->
<div id="pdf-template-page1" style="width: 794px; padding: 40px; background: #ffffff; position: fixed; top: -10000px; left: -10000px; visibility: hidden; pointer-events: none; color: #111827; font-family: 'Noto Sans SC', sans-serif;">
    <img src="/cache/letterhead_cache.jpg" style="width: 100%; margin-bottom: 12px;" alt="Letterhead">
    <h1 style="text-align:center; font-size:24px; font-weight:800; margin-top:6px;">OFFICIAL WUSHU REGISTRATION 2026</h1>
    <p style="text-align:center; font-size:13px; color:#6b7280; margin-bottom:24px;">Legal Binding Document · This form confirms participation in Wushu Sports Academy programmes.</p>

    <div style="margin-bottom:22px;">
        <div style="background:#e5e7eb; padding:7px 12px; font-weight:700; font-size:13px; text-transform:uppercase;">STUDENT DETAILS / 学员资料</div>
        <div style="border:1px solid #e5e7eb; border-top:none; padding:10px 12px; font-size:13px; line-height:1.5;">
            <div style="margin-bottom:5px;"><span style="font-weight:600; color:#6b7280; display:inline-block; width:140px;">Name 姓名:</span> <span style="font-weight:500; color:#111827;" id="pdf-name"></span></div>
            <div style="margin-bottom:5px;"><span style="font-weight:600; color:#6b7280; display:inline-block; width:140px;">IC No 身份证:</span> <span style="font-weight:500; color:#111827;" id="pdf-ic"></span></div>
            <div style="margin-bottom:5px;"><span style="font-weight:600; color:#6b7280; display:inline-block; width:140px;">Age 年龄:</span> <span style="font-weight:500; color:#111827;" id="pdf-age"></span></div>
            <div style="margin-bottom:5px;"><span style="font-weight:600; color:#6b7280; display:inline-block; width:140px;">School 学校:</span> <span style="font-weight:500; color:#111827;" id="pdf-school"></span></div>
            <div style="margin-bottom:5px;"><span style="font-weight:600; color:#6b7280; display:inline-block; width:140px;">Status 身份:</span> <span style="font-weight:500; color:#111827;" id="pdf-status"></span></div>
        </div>
    </div>

    <div style="margin-bottom:22px;">
        <div style="background:#e5e7eb; padding:7px 12px; font-weight:700; font-size:13px; text-transform:uppercase;">CONTACT & EVENTS / 联系与项目</div>
        <div style="border:1px solid #e5e7eb; border-top:none; padding:10px 12px; font-size:13px; line-height:1.5;">
            <div style="margin-bottom:5px;"><span style="font-weight:600; color:#6b7280; display:inline-block; width:140px;">Phone 电话:</span> <span style="font-weight:500; color:#111827;" id="pdf-phone"></span></div>
            <div style="margin-bottom:5px;"><span style="font-weight:600; color:#6b7280; display:inline-block; width:140px;">Email 邮箱:</span> <span style="font-weight:500; color:#111827;" id="pdf-email"></span></div>
            <!--<div style="margin-bottom:5px;"><span style="font-weight:600; color:#6b7280; display:inline-block; width:140px;">Level 等级:</span> <span style="font-weight:500; color:#111827;" id="pdf-level"></span></div>-->
            <div style="margin-bottom:5px;"><span style="font-weight:600; color:#6b7280; display:inline-block; width:140px;">Events 项目:</span> <span style="font-weight:500; color:#111827;" id="pdf-events"></span></div>
            <div style="margin-bottom:5px;"><span style="font-weight:600; color:#6b7280; display:inline-block; width:140px;">Schedule 时间:</span> <span style="font-weight:500; color:#111827;" id="pdf-schedule"></span></div>
        </div>
    </div>

    <div style="margin-bottom:22px;">
        <div style="background:#e5e7eb; padding:7px 12px; font-weight:700; font-size:13px; text-transform:uppercase;">DECLARATION & SIGNATURE / 声明与签名</div>
        <div style="border:1px solid #e5e7eb; border-top:none; padding:10px 12px; font-size:13px; line-height:1.5;">
            <p style="font-size:13px; margin-bottom:12px;">
                I hereby confirm that all information provided is accurate. I have read and agreed to the
                Terms & Conditions, Fee Policy, and Athlete Code of Conduct. I understand that Wushu is a
                high-intensity sport and agree to bear the risks involved.
            </p>
            <div style="border:1px solid #d1d5db; padding:8px; width:340px; height:130px; position:relative; margin-bottom:10px;">
                <img id="pdf-sig-img" style="max-width:100%; max-height:100%; object-fit:contain;">
            </div>
            <p style="font-size:13px; font-weight:600; margin-bottom:4px;">
                Parent / Guardian Name: <span id="pdf-parent-name"></span>
            </p>
            <p style="font-size:13px; font-weight:600; margin-bottom:4px;">
                Parent / Guardian IC No.: <span id="pdf-parent-ic"></span>
            </p>
            <p style="font-size:12px; margin-top:2px;">
                Date: <span id="pdf-date"></span>
            </p>
        </div>
    </div>

    <p style="font-size:12px; color:#4b5563; margin-top:14px; text-align:justify; line-height:1.6;">
        <strong>NOTES / 备注：</strong>
        Fees are non-refundable and must be paid by the 10th of every month. Strict discipline and punctuality
        are required at all times. The Academy reserves the right to adjust training schedules and venues when
        necessary. 学费概不退还，并须在每月10号前缴清。学员必须严守纪律与守时；学院保留在有需要时调整训练时间及地点的权利。
    </p>
</div>


<!-- HIDDEN PDF TEMPLATE - PAGE 2 -->
<div id="pdf-template-page2" style="width: 794px; padding: 40px; background: #ffffff; position: fixed; top: -10000px; left: -10000px; visibility: hidden; pointer-events: none; color: #111827; font-family: Arial, sans-serif;">
    <img src="/cache/letterhead_cache.jpg" style="width: 100%; margin-bottom: 12px;" alt="Letterhead">
    <h1 style="text-align:center; font-size:24px; font-weight:800; margin-top:6px; font-family: 'Noto Sans SC', sans-serif;">TERMS & CONDITIONS</h1>
    <p style="text-align:center; font-size:13px; color:#6b7280; margin-bottom:16px; font-family: 'Noto Sans SC', sans-serif;">条款与条件 · Agreed and Signed by Parent/Guardian</p>

    <div style="font-size: 11px; line-height: 1.5; color: #111827; font-family: 'Noto Sans SC', sans-serif;">
        <p style="margin-bottom: 10px; font-weight: 600; color: #1e293b; font-size: 12px;">
            The parent/guardian has read, understood, and agreed to the following terms:
        </p>

        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 30px; padding: 0 8px 8px 0;">
                    <div style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">1</div>
                </td>
                <td style="padding: 0 0 8px 0;">
                    <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">本人（学员/家长/监护人）确认上述资料属实。</p>
                    <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">I, the student/parent/guardian, confirm that all information provided above is true and correct.</p>
                </td>
            </tr>
            <tr>
                <td style="width: 30px; padding: 0 8px 8px 0;">
                    <div style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">2</div>
                </td>
                <td style="padding: 0 0 8px 0;">
                    <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">本人明白武术是一项剧烈运动，并愿意自行承担训练期间可能发生的意外风险。</p>
                    <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">I understand that Wushu is a high‑intensity sport and agree to bear any risk of injury during training.</p>
                </td>
            </tr>
            <tr>
                <td style="width: 30px; padding: 0 8px 8px 0;">
                    <div style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">3</div>
                </td>
                <td style="padding: 0 0 8px 0;">
                    <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">学院有权在必要时调整训练时间或地点，并将提前通知。</p>
                    <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">The Academy reserves the right to adjust training times or venues when necessary and will notify in advance.</p>
                </td>
            </tr>
            <tr>
                <td style="width: 30px; padding: 0 8px 8px 0;">
                    <div style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">4</div>
                </td>
                <td style="padding: 0 0 8px 0;">
                    <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">学费一经缴付，概不退还（Non‑refundable）。</p>
                    <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">Fees paid are strictly non‑refundable under all circumstances.</p>
                </td>
            </tr>
            <tr>
                <td style="width: 30px; padding: 0 8px 8px 0;">
                    <div style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">5</div>
                </td>
                <td style="padding: 0 0 8px 0;">
                    <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">本人同意遵守学院及教练的所有指示与安排。</p>
                    <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">I agree to follow all instructions, rules, and arrangements set by the Academy and coaches.</p>
                </td>
            </tr>
            <tr>
                <td style="width: 30px; padding: 0 8px 8px 0;">
                    <div style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">6</div>
                </td>
                <td style="padding: 0 0 8px 0;">
                    <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">只限于本院通知取消课程，将会另行安排补课，家长不允许自行取消课程。</p>
                    <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">Replacement classes are only provided when the Academy cancels a session; parents may not cancel classes on their own.</p>
                </td>
            </tr>
            <tr>
                <td style="width: 30px; padding: 0 8px 8px 0;">
                    <div style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">7</div>
                </td>
                <td style="padding: 0 0 8px 0;">
                    <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">如学员因病或其他原因无法出席训练，必须向行政与教练申请请假；未经许可的缺席将被记录。</p>
                    <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">If the student cannot attend due to sickness or other reasons, leave must be applied for with admin and coach; unapproved absences will be recorded.</p>
                </td>
            </tr>
            <tr>
                <td style="width: 30px; padding: 0 8px 8px 0;">
                    <div style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">8</div>
                </td>
                <td style="padding: 0 0 8px 0;">
                    <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">州队及后备队必须出席所有训练，保持良好态度，接受严格训练与训导。</p>
                    <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">State‑team and reserve athletes must attend all training, maintain good attitude, and accept strict training and discipline.</p>
                </td>
            </tr>
            <tr>
                <td style="width: 30px; padding: 0 8px 8px 0;">
                    <div style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">9</div>
                </td>
                <td style="padding: 0 0 8px 0;">
                    <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">如因脚受伤、扭伤或生病，请勿勉强出席训练，后果自负。</p>
                    <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">Students with injuries or illness should not attend training; any consequences are at their own risk.</p>
                </td>
            </tr>
            <tr>
                <td style="width: 30px; padding: 0 8px 8px 0;">
                    <div style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">10</div>
                </td>
                <td style="padding: 0 0 8px 0;">
                    <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">本院不负责学员及家长的任何贵重财物。</p>
                    <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">The Academy is not responsible for any valuables belonging to students or parents.</p>
                </td>
            </tr>
            <tr>
                <td style="width: 30px; padding: 0 8px 8px 0;">
                    <div style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">11</div>
                </td>
                <td style="padding: 0 0 8px 0;">
                    <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">不允许打架、吵架、态度恶劣或不配合训练，否则将被取消州队及学员资格。</p>
                    <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">Fighting, quarrelling, poor attitude, or refusing to cooperate with training may result in removal from the state team and the Academy.</p>
                </td>
            </tr>
            <tr>
                <td style="width: 30px; padding: 0 8px 8px 0;">
                    <div style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">12</div>
                </td>
                <td style="padding: 0 0 8px 0;">
                    <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">训练期间不允许吃食物，只能在休息时间喝水。</p>
                    <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">Eating is not allowed during training; only drinking water during breaks is permitted.</p>
                </td>
            </tr>
            <tr>
                <td style="width: 30px; padding: 0 8px 8px 0;">
                    <div style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">13</div>
                </td>
                <td style="padding: 0 0 8px 0;">
                    <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">家长不允许干涉教练所安排的专业训练计划及纪律管理。</p>
                    <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">Parents are not allowed to interfere with professional training plans or discipline set by the coaches.</p>
                </td>
            </tr>
            <tr>
                <td style="width: 30px; padding: 0 8px 8px 0;">
                    <div style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">14</div>
                </td>
                <td style="padding: 0 0 8px 0;">
                    <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">家长必须准时载送孩子往返训练地点，并自行负责交通安全。</p>
                    <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">Parents must send and pick up their children on time and are fully responsible for transport safety.</p>
                </td>
            </tr>
            <tr>
                <td style="width: 30px; padding: 0 8px 8px 0;">
                    <div style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">15</div>
                </td>
                <td style="padding: 0 0 8px 0;">
                    <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">训练过程中，学员可能被录影或拍照作为宣传用途，如家长不允许，须以书面通知本院。</p>
                    <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">Training sessions may be recorded or photographed for publicity; parents who do not consent must inform the Academy in writing.</p>
                </td>
            </tr>
        </table>

        <div style="margin-top: 18px; padding: 14px 16px; background: #f8fafc; border: 2px solid #1e293b; border-radius: 6px;">
            <p style="font-weight: 700; margin: 0 0 8px 0; color: #1e293b; font-size: 12px;">LEGAL ACKNOWLEDGEMENT / 法律声明</p>
            <p style="margin: 0 0 6px 0; font-size: 10.5px; line-height: 1.5;">
                By signing this document, the parent/guardian acknowledges that they have read, understood, and agreed to all 15 terms and conditions listed above.
            </p>
            <p style="color: #4b5563; font-size: 10px; margin: 0 0 10px 0; line-height: 1.5;">
                家长/监护人签署此文件，即表示已阅读、理解并同意上述所有15项条款与条件。
            </p>
            <p style="margin: 0; font-weight: 600; font-size: 11px; line-height: 1.6;">
                Signed by: <span id="pdf-parent-name-2" style="font-weight: 500;"></span> (<span id="pdf-parent-ic-2" style="font-weight: 500;"></span>)<br>
                Date: <span id="pdf-date-2" style="font-weight: 500;"></span>
            </p>
        </div>
    </div>
</div>




<!-- LOADING OVERLAY -->
<div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center;">
    <div style="text-align: center; color: white;">
        <div style="width: 60px; height: 60px; border: 5px solid rgba(255,255,255,0.3); border-top: 5px solid white; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
        <h3 style="font-size: 20px; margin: 0;">Processing Registration...</h3>
        <p style="margin-top: 10px; font-size: 14px; opacity: 0.8;">正在处理报名 · Please wait</p>
    </div>
</div>



</body>
