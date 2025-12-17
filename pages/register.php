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

        /* Hidden Schedule Item */
        .schedule-hidden {
            display: none !important;
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

            <!-- STEP 3: Events -->
            <div id="step-3" class="step-content">
                <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-trophy text-amber-500"></i> 项目选择 Event Selection
                </h2>
                
                <p class="text-sm text-slate-600 mb-4">Select events for each level (You can select multiple events across different levels)</p>

                <div class="space-y-4">
                    <!-- Basic Level -->
                    <div class="border-l-4 border-slate-700 bg-slate-50 rounded-r-xl p-4">
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
        <p class="font-bold mb-1"><i class="fas fa-info-circle"></i> 注明 (Remark)：州队运动员需至少选择 两堂课。</p>
        <p>• 选择 一堂课：收费 <strong>RM 120</strong></p>
        <p>• 选择 二堂课：收费 <strong>RM 200</strong></p>
        <p>• 选择 三堂课：收费 <strong>RM 280</strong></p>
        <p>• 选择 四堂课：收费 <strong>RM 320</strong></p>
        <p class="font-bold mt-1"><br>State team athletes must choose at least two classes.</p>
        <p>• Choose one class: <strong>RM 120</strong></p>
        <p>• Choose two classes: <strong>RM 200</strong></p>
        <p>• Choose three classes: <strong>RM 280</strong></p>
        <p>• Choose four classes: <strong>RM 320</strong></p>
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
                        <label class="custom-checkbox border-2 border-slate-200 rounded-xl hover:border-amber-400 hover:bg-amber-50/30 transition-all" data-schedule="wsa-sun-10am">
                            <input type="checkbox" name="sch" value="Wushu Sport Academy: Sun 10am-12pm">
                            <div class="custom-checkbox-label">
                                <div class="text-sm font-bold text-slate-800 mb-1">
                                    <i class="far fa-calendar mr-2 text-amber-500"></i>Sunday 星期日 · 10:00 AM - 12:00 PM
                                </div>
                                <div class="text-xs text-slate-600">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-md font-semibold">只限于州队/后备队 Only for State/Backup Team</span>
                                </div>
                            </div>
                        </label>

                        <label class="custom-checkbox border-2 border-slate-200 rounded-xl hover:border-amber-400 hover:bg-amber-50/30 transition-all" data-schedule="wsa-sun-12pm">
                            <input type="checkbox" name="sch" value="Wushu Sport Academy: Sun 12pm-2pm">
                            <div class="custom-checkbox-label">
                                <div class="text-sm font-bold text-slate-800">
                                    <i class="far fa-calendar mr-2 text-amber-500"></i>Sunday 星期日 · 12:00 PM - 2:00 PM
                                </div>
                            </div>
                        </label>
                        
                        <label class="custom-checkbox border-2 border-slate-200 rounded-xl hover:border-amber-400 hover:bg-amber-50/30 transition-all" data-schedule="wsa-wed-8pm">
                            <input type="checkbox" name="sch" value="Wushu Sport Academy: Wed 8pm-10pm">
                            <div class="custom-checkbox-label">
                                <div class="text-sm font-bold text-slate-800 mb-1">
                                    <i class="far fa-calendar mr-2 text-amber-500"></i>Wednesday 星期三 · 8:00 PM - 10:00 PM
                                </div>
                                <div class="text-xs text-slate-600">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-md font-semibold">只有基础套路 和 太极套路</span>
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
                                    <span class="text-slate-400 ml-1">(D/C/B/A 套路 Routine) 和 传统套路</span>
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
                    </div>
                </div>
            </div>
        </div>

        <!-- SCHOOL 3: Stadium Chinwoo -->
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
                        <label class="custom-checkbox border-2 border-slate-200 rounded-xl hover:border-amber-400 hover:bg-amber-50/30 transition-all" data-schedule="chinwoo-sun-2pm">
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
        <div class="bg-blue-50 border-l-4 border-blue-500 p-3 text-xs text-blue-800">
            <p class="font-semibold mb-1"><i class="fas fa-info-circle"></i> 收费标准 Fee Structure:</p>
            <p>• 1 堂课 (1 class): RM 120</p>
            <p>• 2 堂课 (2 classes): RM 200</p>
            <p>• 3 堂课 (3 classes): RM 280</p>
            <p>• 4 堂课或以上 (4+ classes): RM 320</p>
        </div>
    </div>

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
                    <p class="font-bold text-slate-800 text-lg">5621 2345 6789</p>
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
            <i class="fas fa-receipt text-purple-600"></i> 上传收据 Upload Payment Receipt *
        </h3>
        
        <div class="mb-4">
            <label class="block text-sm font-semibold text-slate-700 mb-2">
                付款日期 Payment Date *
            </label>
            <input type="date" id="payment-date" class="w-full p-3 border border-slate-300 rounded-lg text-sm" required>
        </div>

        <div class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center hover:border-amber-400 transition-all cursor-pointer" id="upload-area">
            <input type="file" id="receipt-upload" accept="image/*,.pdf" class="hidden" onchange="handleReceiptUpload(event)">
            <div id="upload-prompt">
                <i class="fas fa-cloud-upload-alt text-4xl text-slate-400 mb-3"></i>
                <p class="text-sm font-semibold text-slate-700 mb-1">点击上传收据 Click to Upload Receipt</p>
                <p class="text-xs text-slate-500">支持 JPG, PNG, PDF (最大 5MB)</p>
                <button type="button" onclick="document.getElementById('receipt-upload').click()" class="mt-3 bg-slate-800 text-white px-6 py-2 rounded-lg text-sm font-semibold hover:bg-slate-700">
                    选择文件 Choose File
                </button>
            </div>
            <div id="upload-preview" class="hidden">
                <img id="preview-image" src="" class="max-w-full max-h-64 mx-auto mb-3 rounded-lg border border-slate-200">
                <p id="preview-filename" class="text-sm font-semibold text-slate-800 mb-2"></p>
                <button type="button" onclick="removeReceipt()" class="text-xs text-red-600 hover:text-red-800 font-semibold">
                    <i class="fas fa-trash"></i> 删除 Remove
                </button>
            </div>
        </div>

        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mt-4 text-xs text-yellow-800">
            <p class="font-semibold mb-1"><i class="fas fa-exclamation-triangle"></i> 重要提示 Important Note:</p>
            <p>请确保收据清晰可见，包含付款金额、日期及银行信息。</p>
            <p class="mt-1">Please ensure the receipt is clear and shows payment amount, date, and bank details.</p>
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
    " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 10px 30px rgba(30, 41, 59, 0.6)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 6px 20px rgba(30, 41, 59, 0.5)';">
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
            <div style="font-size: 13px; font-weight: 700; line-height: 1.3;">Student Login</div>
            <div style="font-size: 10px; color: #fbbf24; font-weight: 500;">学生登录 →</div>
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
<div id="pdf-template-page1" style="width: 794px; padding: 34px 40px 24px 40px; background: #ffffff; position: fixed; top: -10000px; left: -10000px; visibility: hidden; pointer-events: none; color: #111827; font-family: 'Noto Sans SC', sans-serif;">
    
    <img src="/assets/WSP Letter.png" style="width: 100%; margin-bottom: 12px;" alt="Letterhead">
    
    <h1 style="text-align: center; font-size: 24px; font-weight: 800; margin-top: 6px; margin-bottom: 4px; line-height: 1.2;">OFFICIAL WUSHU REGISTRATION 2026</h1>
    <p style="text-align: center; font-size: 12px; color: #6b7280; margin-bottom: 22px;">Legal Binding Document · This form confirms participation in Wushu Sports Academy programmes.</p>

    <div style="margin-bottom: 20px;">
        <div style="background: #e5e7eb; padding: 7px 12px; font-weight: 700; font-size: 13px; text-transform: uppercase;">STUDENT INFORMATION 学员资料</div>
        <div style="border: 1px solid #e5e7eb; border-top: none; padding: 10px 12px; font-size: 12.5px; line-height: 1.6;">
            <div style="margin-bottom: 5px;"><span style="font-weight: 600; color: #6b7280; display: inline-block; width: 140px;">Full Name 姓名:</span><span style="font-weight: 500; color: #111827;" id="pdf-name"></span></div>
            <div style="margin-bottom: 5px;"><span style="font-weight: 600; color: #6b7280; display: inline-block; width: 140px;">IC Number 身份证:</span><span style="font-weight: 500; color: #111827;" id="pdf-ic"></span></div>
            <div style="margin-bottom: 5px;"><span style="font-weight: 600; color: #6b7280; display: inline-block; width: 140px;">Age (2026) 年龄:</span><span style="font-weight: 500; color: #111827;" id="pdf-age"></span></div>
            <div style="margin-bottom: 5px;"><span style="font-weight: 600; color: #6b7280; display: inline-block; width: 140px;">School 学校:</span><span style="font-weight: 500; color: #111827;" id="pdf-school"></span></div>
            <div style="margin-bottom: 5px;"><span style="font-weight: 600; color: #6b7280; display: inline-block; width: 140px;">Status 身份:</span><span style="font-weight: 500; color: #111827;" id="pdf-status"></span></div>
        </div>
    </div>

    <div style="margin-bottom: 20px;">
        <div style="background: #e5e7eb; padding: 7px 12px; font-weight: 700; font-size: 13px; text-transform: uppercase;">CONTACT 联系方式</div>
        <div style="border: 1px solid #e5e7eb; border-top: none; padding: 10px 12px; font-size: 12.5px; line-height: 1.6;">
            <div style="margin-bottom: 5px;"><span style="font-weight: 600; color: #6b7280; display: inline-block; width: 140px;">Phone 电话:</span><span style="font-weight: 500; color: #111827;" id="pdf-phone"></span></div>
            <div style="margin-bottom: 5px;"><span style="font-weight: 600; color: #6b7280; display: inline-block; width: 140px;">Email 邮箱:</span><span style="font-weight: 500; color: #111827;" id="pdf-email"></span></div>
        </div>
    </div>

    <div style="margin-bottom: 20px;">
        <div style="background: #e5e7eb; padding: 7px 12px; font-weight: 700; font-size: 13px; text-transform: uppercase;">TRAINING DETAILS 训练详情</div>
        <div style="border: 1px solid #e5e7eb; border-top: none; padding: 10px 12px; font-size: 12.5px; line-height: 1.6;">
            <div style="margin-bottom: 5px;"><span style="font-weight: 600; color: #6b7280; display: inline-block; width: 140px;">Level 级别:</span><span style="font-weight: 500; color: #111827;" id="pdf-level"></span></div>
            <div style="margin-bottom: 5px;"><span style="font-weight: 600; color: #6b7280; display: inline-block; width: 140px;">Events 项目:</span><span style="font-weight: 500; color: #111827;" id="pdf-events"></span></div>
            <div style="margin-bottom: 5px;"><span style="font-weight: 600; color: #6b7280; display: inline-block; width: 140px;">Schedule 时间:</span><span style="font-weight: 500; color: #111827;" id="pdf-schedule"></span></div>
        </div>
    </div>

    <div style="border: 2px solid #374151; padding: 14px; border-radius: 6px; background: #f9fafb;">
        <h3 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 700; color: #111827;">PARENT / GUARDIAN DECLARATION 家长/监护人声明</h3>
        <p style="font-size: 12px; line-height: 1.55; margin: 0 0 9px 0; color: #374151;">
            I hereby confirm that all information provided is accurate. I have read and agree to all terms and conditions outlined by Wushu Sports Academy.
        </p>
        <p style="font-size: 12px; line-height: 1.55; margin: 0 0 13px 0; color: #374151;">
            本人确认以上所有资料属实。本人已阅读并同意武术体育学院的所有条款与条件。
        </p>
        <div style="margin-top: 11px; padding-top: 11px; border-top: 1px solid #d1d5db;">
            <p style="font-size: 12px; margin: 4px 0; color: #111827;"><strong>Parent Name 家长姓名:</strong> <span id="pdf-parent-name"></span></p>
            <p style="font-size: 12px; margin: 4px 0; color: #111827;"><strong>Parent IC 家长身份证:</strong> <span id="pdf-parent-ic"></span></p>
            <p style="font-size: 12px; margin: 4px 0; color: #111827;"><strong>Date 日期:</strong> <span id="pdf-date"></span></p>
            <p style="font-size: 12px; margin: 10px 0 4px 0; color: #111827;"><strong>Signature 签名:</strong></p>
            <img id="pdf-sig-img" src="" style="max-width: 220px; max-height: 95px; border: 1px solid #d1d5db; padding: 4px; background: white; display: block;">
        </div>
    </div>
</div>


<!-- HIDDEN PDF TEMPLATE - PAGE 2 -->
<div id="pdf-template-page2" style="width: 794px; padding: 34px 40px 22px 40px; background: #ffffff; position: fixed; top: -10000px; left: -10000px; visibility: hidden; pointer-events: none; color: #111827; font-family: 'Noto Sans SC', sans-serif;">
    
    <img src="/assets/WSP Letter.png" style="width: 100%; margin-bottom: 12px;" alt="Letterhead">
    
    <h1 style="text-align: center; font-size: 24px; font-weight: 800; margin-top: 6px; margin-bottom: 4px; line-height: 1.2;">TERMS & CONDITIONS</h1>
    <p style="text-align: center; font-size: 12px; color: #6b7280; margin-bottom: 18px;">条款与条件 · Agreed and Signed by Parent/Guardian</p>

    <p style="font-size: 12px; margin-bottom: 12px; color: #111827; line-height: 1.4; font-weight: 600;">The parent/guardian has read, understood, and agreed to the following terms:</p>

    <table style="width: 100%; margin-bottom: 14px; border-collapse: collapse; font-size: 11px; line-height: 1.5;">
        <tr>
            <td style="vertical-align: top; width: 32px; padding-right: 10px; padding-bottom: 6.5px;">
                <div style="width: 23px; height: 23px; border-radius: 50%; background: #111827; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px;">1</div>
            </td>
            <td style="padding-bottom: 6.5px; line-height: 1.5;">
                <strong style="color: #111827; font-size: 11px;">本人（学员/家长/监护人）确认上述资料属实。</strong><br>
                <span style="color: #6b7280; font-size: 10.5px;">I, the student/parent/guardian, confirm that all information provided above is true and correct.</span>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top; width: 32px; padding-right: 10px; padding-bottom: 6.5px;">
                <div style="width: 23px; height: 23px; border-radius: 50%; background: #111827; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px;">2</div>
            </td>
            <td style="padding-bottom: 6.5px; line-height: 1.5;">
                <strong style="color: #111827; font-size: 11px;">本人明白武术是一项剧烈运动，并愿意自行承担训练期间可能发生的意外风险。</strong><br>
                <span style="color: #6b7280; font-size: 10.5px;">I understand that Wushu is a high‑intensity sport and agree to bear any risk of injury during training.</span>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top; width: 32px; padding-right: 10px; padding-bottom: 6.5px;">
                <div style="width: 23px; height: 23px; border-radius: 50%; background: #111827; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px;">3</div>
            </td>
            <td style="padding-bottom: 6.5px; line-height: 1.5;">
                <strong style="color: #111827; font-size: 11px;">学院有权在必要时调整训练时间或地点，并将提前通知。</strong><br>
                <span style="color: #6b7280; font-size: 10.5px;">The Academy reserves the right to adjust training times or venues when necessary and will notify in advance.</span>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top; width: 32px; padding-right: 10px; padding-bottom: 6.5px;">
                <div style="width: 23px; height: 23px; border-radius: 50%; background: #111827; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px;">4</div>
            </td>
            <td style="padding-bottom: 6.5px; line-height: 1.5;">
                <strong style="color: #111827; font-size: 11px;">学费一经缴付，概不退还（Non‑refundable）。</strong><br>
                <span style="color: #6b7280; font-size: 10.5px;">Fees paid are strictly non‑refundable under all circumstances.</span>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top; width: 32px; padding-right: 10px; padding-bottom: 6.5px;">
                <div style="width: 23px; height: 23px; border-radius: 50%; background: #111827; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px;">5</div>
            </td>
            <td style="padding-bottom: 6.5px; line-height: 1.5;">
                <strong style="color: #111827; font-size: 11px;">本人同意遵守学院及教练的所有指示与安排。</strong><br>
                <span style="color: #6b7280; font-size: 10.5px;">I agree to follow all instructions, rules, and arrangements set by the Academy and coaches.</span>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top; width: 32px; padding-right: 10px; padding-bottom: 6.5px;">
                <div style="width: 23px; height: 23px; border-radius: 50%; background: #111827; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px;">6</div>
            </td>
            <td style="padding-bottom: 6.5px; line-height: 1.5;">
                <strong style="color: #111827; font-size: 11px;">只限于本院通知取消课程，将会另行安排补课，家长不允许自行取消课程。</strong><br>
                <span style="color: #6b7280; font-size: 10.5px;">Replacement classes are only provided when the Academy cancels a session; parents may not cancel classes on their own.</span>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top; width: 32px; padding-right: 10px; padding-bottom: 6.5px;">
                <div style="width: 23px; height: 23px; border-radius: 50%; background: #111827; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px;">7</div>
            </td>
            <td style="padding-bottom: 6.5px; line-height: 1.5;">
                <strong style="color: #111827; font-size: 11px;">如学员因病或其他原因无法出席训练，必须向行政与教练申请请假；未经许可的缺席将被记录。</strong><br>
                <span style="color: #6b7280; font-size: 10.5px;">If the student cannot attend due to sickness or other reasons, leave must be applied for with admin and coach; unapproved absences will be recorded.</span>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top; width: 32px; padding-right: 10px; padding-bottom: 6.5px;">
                <div style="width: 23px; height: 23px; border-radius: 50%; background: #111827; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px;">8</div>
            </td>
            <td style="padding-bottom: 6.5px; line-height: 1.5;">
                <strong style="color: #111827; font-size: 11px;">州队及后备队必须出席所有训练，保持良好态度，接受严格训练与训导。</strong><br>
                <span style="color: #6b7280; font-size: 10.5px;">State‑team and reserve athletes must attend all training, maintain good attitude, and accept strict training and discipline.</span>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top; width: 32px; padding-right: 10px; padding-bottom: 6.5px;">
                <div style="width: 23px; height: 23px; border-radius: 50%; background: #111827; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px;">9</div>
            </td>
            <td style="padding-bottom: 6.5px; line-height: 1.5;">
                <strong style="color: #111827; font-size: 11px;">如因脚受伤、扭伤或生病，请勿勉强出席训练，后果自负。</strong><br>
                <span style="color: #6b7280; font-size: 10.5px;">Students with injuries or illness should not attend training; any consequences are at their own risk.</span>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top; width: 32px; padding-right: 10px; padding-bottom: 6.5px;">
                <div style="width: 23px; height: 23px; border-radius: 50%; background: #111827; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px;">10</div>
            </td>
            <td style="padding-bottom: 6.5px; line-height: 1.5;">
                <strong style="color: #111827; font-size: 11px;">本院不负责学员及家长的任何贵重财物。</strong><br>
                <span style="color: #6b7280; font-size: 10.5px;">The Academy is not responsible for any valuables belonging to students or parents.</span>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top; width: 32px; padding-right: 10px; padding-bottom: 6.5px;">
                <div style="width: 23px; height: 23px; border-radius: 50%; background: #111827; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px;">11</div>
            </td>
            <td style="padding-bottom: 6.5px; line-height: 1.5;">
                <strong style="color: #111827; font-size: 11px;">不允许打架、吵架、态度恶劣或不配合训练，否则将被取消州队及学员资格。</strong><br>
                <span style="color: #6b7280; font-size: 10.5px;">Fighting, quarrelling, poor attitude, or refusing to cooperate with training may result in removal from the state team and the Academy.</span>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top; width: 32px; padding-right: 10px; padding-bottom: 6.5px;">
                <div style="width: 23px; height: 23px; border-radius: 50%; background: #111827; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px;">12</div>
            </td>
            <td style="padding-bottom: 6.5px; line-height: 1.5;">
                <strong style="color: #111827; font-size: 11px;">训练期间不允许吃食物，只能在休息时间喝水。</strong><br>
                <span style="color: #6b7280; font-size: 10.5px;">Eating is not allowed during training; only drinking water during breaks is permitted.</span>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top; width: 32px; padding-right: 10px; padding-bottom: 6.5px;">
                <div style="width: 23px; height: 23px; border-radius: 50%; background: #111827; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px;">13</div>
            </td>
            <td style="padding-bottom: 6.5px; line-height: 1.5;">
                <strong style="color: #111827; font-size: 11px;">家长不允许干涉教练所安排的专业训练计划及纪律管理。</strong><br>
                <span style="color: #6b7280; font-size: 10.5px;">Parents are not allowed to interfere with professional training plans or discipline set by the coaches.</span>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top; width: 32px; padding-right: 10px; padding-bottom: 6.5px;">
                <div style="width: 23px; height: 23px; border-radius: 50%; background: #111827; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px;">14</div>
            </td>
            <td style="padding-bottom: 6.5px; line-height: 1.5;">
                <strong style="color: #111827; font-size: 11px;">家长必须准时载送孩子往返训练地点，并自行负责交通安全。</strong><br>
                <span style="color: #6b7280; font-size: 10.5px;">Parents must send and pick up their children on time and are fully responsible for transport safety.</span>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top; width: 32px; padding-right: 10px; padding-bottom: 6.5px;">
                <div style="width: 23px; height: 23px; border-radius: 50%; background: #111827; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px;">15</div>
            </td>
            <td style="padding-bottom: 6.5px; line-height: 1.5;">
                <strong style="color: #111827; font-size: 11px;">训练过程中，学员可能被录影或拍照作为宣传用途，如家长不允许，须以书面通知本院。</strong><br>
                <span style="color: #6b7280; font-size: 10.5px;">Training sessions may be recorded or photographed for publicity; parents who do not consent must inform the Academy in writing.</span>
            </td>
        </tr>
    </table>

    <div style="border: 2px solid #374151; padding: 14px; margin-top: 16px; border-radius: 6px; background: #f9fafb;">
        <h3 style="margin: 0 0 10px 0; font-size: 13.5px; font-weight: 700; color: #111827;">LEGAL ACKNOWLEDGEMENT / 法律声明</h3>
        <p style="font-size: 11px; line-height: 1.5; margin: 0 0 9px 0; color: #374151;">
            By signing this document, the parent/guardian acknowledges that they have read, understood, and agreed to all 15 terms and conditions listed above.
        </p>
        <p style="font-size: 11px; line-height: 1.5; margin: 0 0 12px 0; color: #374151;">
            家长/监护人签署此文件，即表示已阅读、理解并同意上述所有15项条款与条件。
        </p>
        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #d1d5db;">
            <p style="font-size: 11.5px; margin: 4px 0; color: #111827;"><strong>Signed by:</strong> <span id="pdf-parent-name-2"></span> (<span id="pdf-parent-ic-2"></span>)</p>
            <p style="font-size: 11.5px; margin: 4px 0; color: #111827;"><strong>Date:</strong> <span id="pdf-date-2"></span></p>
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

<script>
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
            });
        });
        
        updateStatusRadioStyle();
        updateScheduleAvailability();
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
        
        // For normal students, only show WSA Sunday 10am-12pm and 12pm-2pm
        if (isRegularStudent) {
            // Hide all schedules except the two allowed for normal students
            const allScheduleLabels = document.querySelectorAll('label[data-schedule]');
            
            allScheduleLabels.forEach(label => {
                const scheduleKey = label.getAttribute('data-schedule');
                const checkbox = label.querySelector('input[type="checkbox"]');
                
                // Only show wsa-sun-10am and wsa-sun-12pm for normal students
                if (scheduleKey === 'wsa-sun-10am' || scheduleKey === 'wsa-sun-12pm') {
                    label.style.display = 'flex'; // Show this schedule
                    if (checkbox) {
                        checkbox.disabled = false;
                    }
                } else {
                    label.style.display = 'none'; // Hide this schedule
                    if (checkbox) {
                        checkbox.checked = false; // Uncheck if previously selected
                        checkbox.disabled = true;
                    }
                }
            });
        } else {
            // For State Team and Backup Team, show all schedules
            const allScheduleLabels = document.querySelectorAll('label[data-schedule]');
            
            allScheduleLabels.forEach(label => {
                const checkbox = label.querySelector('input[type="checkbox"]');
                label.style.display = 'flex'; // Show all schedules
                if (checkbox) {
                    checkbox.disabled = false;
                }
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
</script>



</body>
</html>
