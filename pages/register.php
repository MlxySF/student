<?php
// student/pages/register.php
// Public Student Registration Form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2026 WSA Wushu Registration | Enrollment Form</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Noto+Sans+SC:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- jsPDF + html2canvas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Noto Sans SC', sans-serif;
            background: radial-gradient(circle at top right, #1e293b, #0f172a);
            min-height: 100vh;
            padding: 20px;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            max-width: 800px;
            margin: 0 auto;
            border-radius: 24px;
            overflow: hidden;
        }

        .step-content {
            display: none;
            animation: fadeIn 0.4s ease-in-out;
        }
        .step-content.active { display: block; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

        /* Signature Box */
        .sig-box {
            position: relative;
            width: 100%;
            height: 200px;
            border: 2px dashed #cbd5e1;
            border-radius: 0.75rem;
            background: #fff;
            overflow: hidden;
            touch-action: none;
        }

        #sig-placeholder {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #cbd5e1;
            font-size: 24px;
            font-weight: 700;
            pointer-events: none;
            user-select: none;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Status Radio Styling */
        input[type="radio"][name="status"] {
            display: none;
        }

        .status-option {
            transition: all 0.2s ease;
            cursor: pointer;
        }

        /* Custom Checkbox */
        .custom-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            cursor: pointer;
            padding: 12px;
            border-radius: 8px;
            transition: background-color 0.2s;
        }

        .custom-checkbox:hover {
            background-color: #f8fafc;
        }

        .custom-checkbox input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            min-width: 20px;
            border: 2px solid #cbd5e1;
            border-radius: 4px;
            cursor: pointer;
            position: relative;
            transition: all 0.2s;
            margin-top: 2px;
        }

        .custom-checkbox input[type="checkbox"]:hover {
            border-color: #fbbf24;
        }

        .custom-checkbox input[type="checkbox"]:checked {
            background-color: #7c3aed;
            border-color: #7c3aed;
        }

        .custom-checkbox input[type="checkbox"]:checked::after {
            content: '';
            position: absolute;
            left: 5px;
            top: 2px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .custom-checkbox-label {
            flex: 1;
            line-height: 1.5;
        }

        /* School Box Styles */
        .school-box {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
            margin-bottom: 16px;
        }

        .school-box:hover {
            border-color: #fbbf24;
            box-shadow: 0 4px 12px rgba(251, 191, 36, 0.15);
        }

        .school-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            cursor: pointer;
            background: #f8fafc;
            transition: background 0.2s;
        }

        .school-header:hover {
            background: #f1f5f9;
        }

        .school-info {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
        }

        .school-logo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e2e8f0;
            flex-shrink: 0;
        }

        .school-text h3 {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 4px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .school-text p {
            font-size: 13px;
            color: #64748b;
            margin: 0;
        }

        .school-toggle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            flex-shrink: 0;
        }

        .school-toggle i {
            transition: transform 0.3s;
            color: #475569;
        }

        .school-box.active .school-toggle {
            background: #fbbf24;
        }

        .school-box.active .school-toggle i {
            transform: rotate(180deg);
            color: white;
        }

        .school-schedules {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .school-schedules-inner {
            padding: 0 20px 20px 20px;
        }

        .school-box.active .school-schedules {
            max-height: 1000px;
        }

        /* PDF Templates */
        #pdf-template-page1, #pdf-template-page2 {
            position: fixed !important;
            left: -99999px !important;
            top: -99999px !important;
            width: 794px;
            padding: 40px;
            background: #ffffff;
            visibility: hidden;
            opacity: 0;
            pointer-events: none;
            z-index: -9999;
        }
    </style>
</head>
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

            <!-- STEP 1-5 remain the same as original file - TRUNCATED FOR BREVITY -->
            
            <!-- STEP 4: Schedule (MODIFIED) -->
            <div id="step-4" class="step-content">
                <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <i class="fa-regular fa-calendar-check text-amber-500"></i> 训练时间 Training Schedule
                </h2>

                <!-- Fee Info -->
                <div class="bg-amber-50 text-amber-900 p-4 rounded-xl text-xs mb-8 border border-amber-100">
                    <p class="font-bold mb-1"><i class="fas fa-info-circle"></i> 注明 (Remark)：州队运动员需至少选择 两堂课。</p>
                    <p>• 选择 一堂课：收费 <strong>RM 120</strong></p>
                    <p>• 选择 二堂课：收费 <strong>RM 200</strong></p>
                    <p>• 选择 三堂课：收费 <strong>RM 280</strong></p>
                    <p>• 选择 四堂课：收费 <strong>RM 320</strong></p>
                    <p class="font-bold mt-1"><br>State team athletes must choose at least two classes.</p>
                    <p>• If only one class is chosen: <strong>RM 120</strong></p>
                    <p>• If a second class is chosen: <strong>RM 200</strong></p>
                    <p>• If a third class is chosen: <strong>RM 280</strong></p>
                    <p>• If a forth class is chosen: <strong>RM 320</strong></p>
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
                                    <label class="custom-checkbox border-2 border-slate-200 rounded-xl hover:border-amber-400 hover:bg-amber-50/30 transition-all">
                                        <input type="checkbox" name="sch" value="Wushu Sport Academy: Sun 10am-12pm">
                                        <div class="custom-checkbox-label">
                                            <div class="text-sm font-bold text-slate-800 mb-1">
                                                <i class="far fa-calendar mr-2 text-amber-500"></i>Sunday 星期日 · 10:00 AM - 12:00 PM
                                            </div>
                                            <div class="text-xs text-slate-600">
                                                <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-md font-semibold">只限于州队/后备队 Only for State/Backup Team</span>
                                            </div>
                                            <div class="text-[10px] text-red-500 font-bold hidden disabled-msg mt-1">
                                                <i class="fas fa-ban mr-1"></i>Not available for Normal Students 普通学生不允许参加
                                            </div>
                                        </div>
                                    </label>

                                    <label class="custom-checkbox border-2 border-slate-200 rounded-xl hover:border-amber-400 hover:bg-amber-50/30 transition-all">
                                        <input type="checkbox" name="sch" value="Wushu Sport Academy: Sun 12pm-2pm">
                                        <div class="custom-checkbox-label">
                                            <div class="text-sm font-bold text-slate-800">
                                                <i class="far fa-calendar mr-2 text-amber-500"></i>Sunday 星期日 · 12:00 PM - 2:00 PM
                                            </div>
                                        </div>
                                    </label>

                                    <label class="custom-checkbox border-2 border-slate-200 rounded-xl hover:border-amber-400 hover:bg-amber-50/30 transition-all">
                                        <input type="checkbox" name="sch" value="Wushu Sport Academy: Wed 8pm-10pm">
                                        <div class="custom-checkbox-label">
                                            <div class="text-sm font-bold text-slate-800 mb-1">
                                                <i class="far fa-calendar mr-2 text-amber-500"></i>Wednesday 星期三 · 8:00 PM - 10:00 PM
                                            </div>
                                            <div class="text-xs text-slate-500">全部组别 All Groups (A/B/C/D, 太极 Tai Chi, 传统 Traditional)</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SCHOOL 2: SJK(C) Puay Chai 2 (MODIFIED - ADDED WEDNESDAY CLASS) -->
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
                                    <label class="custom-checkbox border-2 border-slate-200 rounded-xl hover:border-amber-400 hover:bg-amber-50/30 transition-all">
                                        <input type="checkbox" name="sch" value="SJK(C) Puay Chai 2: Tue 8pm-10pm">
                                        <div class="custom-checkbox-label">
                                            <div class="text-sm font-bold text-slate-800 mb-1">
                                                <i class="far fa-calendar mr-2 text-amber-500"></i>Tuesday 星期二 · 8:00 PM - 10:00 PM
                                            </div>
                                            <div class="text-xs text-slate-600">
                                                <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-md font-semibold">只限于州队/后备队 Only for State/Backup Team</span>
                                                <span class="text-slate-400 ml-1">(D/C/B/A 套路 Routine)</span>
                                            </div>
                                            <div class="text-[10px] text-red-500 font-bold hidden disabled-msg mt-1">
                                                <i class="fas fa-ban mr-1"></i>Not available for Normal Students 普通学生不允许参加
                                            </div>
                                        </div>
                                    </label>

                                    <!-- NEW WEDNESDAY CLASS -->
                                    <label class="custom-checkbox border-2 border-slate-200 rounded-xl hover:border-amber-400 hover:bg-amber-50/30 transition-all">
                                        <input type="checkbox" name="sch" value="SJK(C) Puay Chai 2: Wed 8pm-10pm">
                                        <div class="custom-checkbox-label">
                                            <div class="text-sm font-bold text-slate-800">
                                                <i class="far fa-calendar mr-2 text-amber-500"></i>Wednesday 星期三 · 8:00 PM - 10:00 PM
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SCHOOL 3: Stadium Chinwoo (MODIFIED - ADDED LOGO) -->
                    <div class="school-box" onclick="toggleSchoolBox(this)">
                        <div class="school-header">
                            <div class="school-info">
                                <img src="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/Chinwoo+Logo.jpg" alt="Chinwoo Logo" class="school-logo">
                                <div class="school-text">
                                    <h3>
                                        <i class="fas fa-map-marker-alt" style="color: #fbbf24;"></i>
                                        Stadium Chinwoo 精武体育馆
                                    </h3>
                                    <p><i class="fas fa-location-dot" style="color: #94a3b8;"></i> Jalan Hang Jebat, 50150 Kuala Lumpur</p>
                                </div>
                            </div>
                            <div class="school-toggle">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                        <div class="school-schedules">
                            <div class="school-schedules-inner">
                                <div class="space-y-3">
                                    <label class="custom-checkbox border-2 border-slate-200 rounded-xl hover:border-amber-400 hover:bg-amber-50/30 transition-all">
                                        <input type="checkbox" name="sch" value="Stadium Chinwoo: Sun 2pm-4pm">
                                        <div class="custom-checkbox-label">
                                            <div class="text-sm font-bold text-slate-800 mb-1">
                                                <i class="far fa-calendar mr-2 text-amber-500"></i>Sunday 星期日 · 2:00 PM - 4:00 PM
                                            </div>
                                            <div class="text-xs text-slate-600">
                                                <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-md font-semibold">只限于州队/后备队 Only for State/Backup Team</span>
                                            </div>
                                            <div class="text-[10px] text-red-500 font-bold hidden disabled-msg mt-1">
                                                <i class="fas fa-ban mr-1"></i>Not available for Normal Students 普通学生不允许参加
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

<!-- STEP 7: Success (MODIFIED - ADDED LOGIN BUTTON) -->
<div id="step-7" class="step-content">
    <div style="text-align: center; padding: 48px 0; position: relative;">
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

        <div style="display: flex; justify-content: center; gap: 16px; flex-wrap: wrap;">
            <button type="button" onclick="downloadPDF()" style="background: #16a34a; color: white; padding: 16px 32px; border-radius: 12px; font-weight: bold; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3); border: none; cursor: pointer; display: flex; align-items: center; gap: 12px; transition: transform 0.2s;">
                <i class="fas fa-download" style="font-size: 20px;"></i>
                <div style="text-align: left;">
                    <div>Download Agreement</div>
                    <div style="font-size: 12px; font-weight: normal;">下载协议PDF</div>
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

        <!-- NEW LOGIN BUTTON AT BOTTOM RIGHT -->
        <div style="position: absolute; bottom: 20px; right: 20px;">
            <button type="button" onclick="window.location.href='/index.php'" style="background: #1e293b; color: white; padding: 12px 24px; border-radius: 12px; font-weight: 600; box-shadow: 0 4px 12px rgba(30, 41, 59, 0.3); border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s;">
                <i class="fas fa-sign-in-alt"></i>
                <span>Go to Login 登录页面</span>
            </button>
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

<!-- JavaScript remains the same -->

</body>
</html>