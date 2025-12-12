<?php
// student/pages/register.php
// Public Student Registration Form
// No session required
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

        #pdf-template {
            position: absolute !important;
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
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

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
            border-color: #7c3aed;
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

        /* Signature Box */
        .signature-box {
            position: relative;
            width: 100%;
            height: 200px;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            background: white;
        }

        #signature-canvas {
            position: absolute;
            top: 0;
            left: 0;
            cursor: crosshair;
            width: 100%;
            height: 100%;
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

        .clear-sig-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            z-index: 20;
            background: #fee2e2;
            color: #dc2626;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }

        .clear-sig-btn:hover {
            background: #fecaca;
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
                <h2 style="font-size: 20px; font-weight: bold; color: #1e293b; margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-user-graduate" style="color: #fbbf24;"></i> 基本资料 Student Details
                </h2>
                <div style="display: flex; flex-direction: column; gap: 24px;">
                    <!-- Name Row -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 8px;">Chinese Name 中文名</label>
                            <input type="text" id="name-cn" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid #cbd5e1; outline: none;" placeholder="张三">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 8px;">English Name 英文名 *</label>
                            <input type="text" id="name-en" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid #cbd5e1; outline: none;" placeholder="Tan Ah Meng" required>
                        </div>
                    </div>

                    <!-- IC and Age Row -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 8px;">IC Number 身份证号码 *</label>
                            <input type="text" id="ic" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid #cbd5e1; outline: none;" placeholder="000000-00-0000" maxlength="14" required>
                            <p style="font-size: 12px; color: #94a3b8; margin-top: 4px;">Format: 000000-00-0000</p>
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 8px;">Age 年龄 (2026)</label>
                            <input type="number" id="age" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid #cbd5e1; background: #f1f5f9; color: #64748b; outline: none;" placeholder="Auto-calculated" readonly>
                            <p style="font-size: 12px; color: #94a3b8; margin-top: 4px;">
                                <i class="fas fa-info-circle"></i> Calculated from IC
                            </p>
                        </div>
                    </div>

                    <!-- School Row -->
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 8px;">School 学校 *</label>
                        <select id="school" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid #cbd5e1; background: white; outline: none;" required>
                            <option value="">Select School...</option>
                            <option value="SJK(C) PUAY CHAI 2">SJK(C) PUAY CHAI 2 (培才二校)</option>
                            <option value="SJK(C) Chee Wen">SJK(C) Chee Wen</option>
                            <option value="SJK(C) Subang">SJK(C) Subang</option>
                            <option value="SJK(C) Sin Ming">SJK(C) Sin Ming</option>
                            <option value="Others">Others (其他)</option>
                        </select>
                        <input type="text" id="school-other" style="display: none; width: 100%; margin-top: 8px; padding: 12px; border-radius: 12px; border: 1px solid #cbd5e1; outline: none;" placeholder="Please specify school name">
                    </div>
                </div>
            </div>

            <!-- STEP 2: Contact -->
            <div id="step-2" class="step-content">
                <h2 style="font-size: 20px; font-weight: bold; color: #1e293b; margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-address-card" style="color: #fbbf24;"></i> 联系方式 Contact Info
                </h2>
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 8px;">Phone Number 电话号码 *</label>
                        <div style="position: relative;">
                            <i class="fa-solid fa-phone" style="position: absolute; left: 16px; top: 16px; color: #94a3b8;"></i>
                            <input type="tel" id="phone" style="width: 100%; padding: 12px 12px 12px 40px; border-radius: 12px; border: 1px solid #cbd5e1; outline: none;" placeholder="012-345 6789" maxlength="13" required>
                        </div>
                        <p style="font-size: 12px; color: #94a3b8; margin-top: 4px;">Format: 012-345 6789</p>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 8px;">Parent's Email 家长邮箱 *</label>
                        <div style="position: relative;">
                            <i class="fa-solid fa-envelope" style="position: absolute; left: 16px; top: 16px; color: #94a3b8;"></i>
                            <input type="email" id="email" style="width: 100%; padding: 12px 12px 12px 40px; border-radius: 12px; border: 1px solid #cbd5e1; outline: none;" placeholder="parent@example.com" required>
                        </div>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 8px;">Student Status 身份 *</label>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                            <label style="cursor: pointer;">
                                <input type="radio" name="status" value="Student 学生" style="display: none;" class="status-radio" checked>
                                <div class="status-option" style="padding: 12px; text-align: center; border-radius: 12px; border: 1px solid #e2e8f0; background: white; transition: all 0.2s;">
                                    Student<br>学生
                                </div>
                            </label>
                            <label style="cursor: pointer;">
                                <input type="radio" name="status" value="State Team 州队" style="display: none;" class="status-radio">
                                <div class="status-option" style="padding: 12px; text-align: center; border-radius: 12px; border: 1px solid #e2e8f0; background: white; transition: all 0.2s;">
                                    State Team<br>州队
                                </div>
                            </label>
                            <label style="cursor: pointer;">
                                <input type="radio" name="status" value="Backup Team 后备队" style="display: none;" class="status-radio">
                                <div class="status-option" style="padding: 12px; text-align: center; border-radius: 12px; border: 1px solid #e2e8f0; background: white; transition: all 0.2s;">
                                    Backup Team<br>后备队
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
                        <!-- STEP 3: Events -->
            <div id="step-3" class="step-content">
                <h2 style="font-size: 20px; font-weight: bold; color: #1e293b; margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-trophy" style="color: #fbbf24;"></i> 项目选择 Event Selection
                </h2>
                
                <p style="font-size: 14px; color: #64748b; margin-bottom: 16px;">Select events for each level (You can select multiple events across different levels)</p>

                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <!-- Basic Level -->
                    <div style="border-left: 4px solid #475569; background: #f8fafc; border-radius: 0 12px 12px 0; padding: 16px;">
                        <h3 style="font-weight: bold; color: #1e293b; margin-bottom: 12px;">基础 Basic</h3>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="基础-长拳" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">长拳</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="基础-南拳" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">南拳</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="基础-太极拳" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">太极拳</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="基础-剑" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">剑</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="基础-枪" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">枪</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="基础-刀" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">刀</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="基础-棍" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">棍</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="基础-南刀" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">南刀</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="基础-南棍" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">南棍</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="基础-太极剑" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">太极剑</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="基础-太极扇" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">太极扇</span>
                            </label>
                        </div>
                    </div>

                    <!-- Junior Level -->
                    <div style="border-left: 4px solid #2563eb; background: #eff6ff; border-radius: 0 12px 12px 0; padding: 16px;">
                        <h3 style="font-weight: bold; color: #1e40af; margin-bottom: 12px;">初级 Junior</h3>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="初级-长拳" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">长拳</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="初级-南拳" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">南拳</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="初级-太极拳" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">太极拳</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="初级-剑" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">剑</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="初级-枪" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">枪</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="初级-刀" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">刀</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="初级-棍" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">棍</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="初级-南刀" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">南刀</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="初级-南棍" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">南棍</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="初级-太极剑" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">太极剑</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="初级-太极扇" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">太极扇</span>
                            </label>
                        </div>
                    </div>

                    <!-- Group B -->
                    <div style="border-left: 4px solid #16a34a; background: #f0fdf4; border-radius: 0 12px 12px 0; padding: 16px;">
                        <h3 style="font-weight: bold; color: #15803d; margin-bottom: 12px;">B组 Group B</h3>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="B组-长拳" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">长拳</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="B组-南拳" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">南拳</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="B组-太极拳" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">太极拳</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="B组-剑" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">剑</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="B组-枪" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">枪</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="B组-刀" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">刀</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="B组-棍" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">棍</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="B组-南刀" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">南刀</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="B组-南棍" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">南棍</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="B组-太极剑" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">太极剑</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="B组-太极扇" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">太极扇</span>
                            </label>
                        </div>
                    </div>

                    <!-- Group A -->
                    <div style="border-left: 4px solid #9333ea; background: #faf5ff; border-radius: 0 12px 12px 0; padding: 16px;">
                        <h3 style="font-weight: bold; color: #7e22ce; margin-bottom: 12px;">A组 Group A</h3>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="A组-长拳" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">长拳</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="A组-南拳" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">南拳</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="A组-太极拳" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">太极拳</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="A组-剑" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">剑</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="A组-枪" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">枪</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="A组-刀" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">刀</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="A组-棍" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">棍</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="A组-南刀" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">南刀</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="A组-南棍" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">南棍</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="A组-太极剑" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">太极剑</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="A组-太极扇" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">太极扇</span>
                            </label>
                        </div>
                    </div>

                    <!-- Optional Level -->
                    <div style="border-left: 4px solid #f59e0b; background: #fffbeb; border-radius: 0 12px 12px 0; padding: 16px;">
                        <h3 style="font-weight: bold; color: #d97706; margin-bottom: 12px;">自选 Optional</h3>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="自选-长拳" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">长拳</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="自选-南拳" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">南拳</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="自选-太极拳" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">太极拳</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="自选-剑" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">剑</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="自选-枪" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">枪</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="自选-刀" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">刀</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="自选-棍" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">棍</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="自选-南刀" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">南刀</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="自选-南棍" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">南棍</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="自选-太极剑" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">太极剑</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="evt" value="自选-太极扇" style="width: 16px; height: 16px;">
                                <span style="font-size: 14px;">太极扇</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 4: Schedule -->
            <div id="step-4" class="step-content">
                <h2 style="font-size: 20px; font-weight: bold; color: #1e293b; margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-regular fa-calendar-check" style="color: #fbbf24;"></i> 训练时间 Training Schedule
                </h2>

                <!-- Fee Info -->
                <div style="background: #fffbeb; color: #78350f; padding: 16px; border-radius: 12px; font-size: 12px; margin-bottom: 32px; border: 1px solid #fde68a;">
                    <p style="font-weight: bold; margin-bottom: 4px;"><i class="fas fa-info-circle"></i> 注明 (Remark)：州队运动员需至少选择 两堂课。</p>
                    <p>• 选择 一堂课：收费 <strong>RM 120</strong></p>
                    <p>• 选择 二堂课：收费 <strong>RM 200</strong></p>
                    <p>• 选择 三堂课：收费 <strong>RM 280</strong></p>
                    <p>• 选择 四堂课：收费 <strong>RM 320</strong></p>
                    <p style="font-weight: bold; margin-top: 8px;">State team athletes must choose at least two classes.</p>
                </div>

                <div>
                    <!-- SCHOOL 1: Wushu Sport Academy -->
                    <div class="school-box">
                        <div class="school-header" onclick="toggleSchoolBox(this.parentElement)">
                            <div class="school-info">
                                <img src="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/Wushu+Sport+Academy+Circle+Yellow.png" alt="WSA Logo" class="school-logo">
                                <div class="school-text">
                                    <h3>
                                        <i class="fas fa-map-marker-alt" style="color: #fbbf24;"></i>
                                        Wushu Sport Academy 武术体育学院
                                    </h3>
                                    <p><i class="fas fa-location-dot" style="color: #94a3b8;"></i> No. 2, Jalan BP 5/6, Bandar Bukit Puchong</p>
                                </div>
                            </div>
                            <div class="school-toggle">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                        <div class="school-schedules">
                            <div class="school-schedules-inner">
                                <div style="display: flex; flex-direction: column; gap: 12px;">
                                    <label class="custom-checkbox" style="border: 2px solid #e2e8f0; border-radius: 12px;">
                                        <input type="checkbox" name="sch" value="Wushu Sport Academy: Sun 10am-12pm">
                                        <div class="custom-checkbox-label">
                                            <div style="font-size: 14px; font-weight: bold; color: #1e293b; margin-bottom: 4px;">
                                                <i class="far fa-calendar" style="color: #fbbf24;"></i> Sunday 星期日 · 10:00 AM - 12:00 PM
                                            </div>
                                            <div style="font-size: 12px; color: #64748b;">
                                                <span style="background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 4px; font-weight: 600;">只限于州队/后备队 Only for State/Backup Team</span>
                                            </div>
                                            <div class="disabled-msg" style="font-size: 10px; color: #dc2626; font-weight: bold; display: none; margin-top: 4px;">
                                                <i class="fas fa-ban"></i> Not available for Normal Students 普通学生不允许参加
                                            </div>
                                        </div>
                                    </label>

                                    <label class="custom-checkbox" style="border: 2px solid #e2e8f0; border-radius: 12px;">
                                        <input type="checkbox" name="sch" value="Wushu Sport Academy: Sun 12pm-2pm">
                                        <div class="custom-checkbox-label">
                                            <div style="font-size: 14px; font-weight: bold; color: #1e293b;">
                                                <i class="far fa-calendar" style="color: #fbbf24;"></i> Sunday 星期日 · 12:00 PM - 2:00 PM
                                            </div>
                                        </div>
                                    </label>

                                    <label class="custom-checkbox" style="border: 2px solid #e2e8f0; border-radius: 12px;">
                                        <input type="checkbox" name="sch" value="Wushu Sport Academy: Wed 8pm-10pm">
                                        <div class="custom-checkbox-label">
                                            <div style="font-size: 14px; font-weight: bold; color: #1e293b; margin-bottom: 4px;">
                                                <i class="far fa-calendar" style="color: #fbbf24;"></i> Wednesday 星期三 · 8:00 PM - 10:00 PM
                                            </div>
                                            <div style="font-size: 12px; color: #64748b;">全部组别 All Groups (A/B/C/D, 太极 Tai Chi, 传统 Traditional)</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SCHOOL 2: SJK(C) Puay Chai 2 -->
                    <div class="school-box">
                        <div class="school-header" onclick="toggleSchoolBox(this.parentElement)">
                            <div class="school-info">
                                <img src="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/PC2+Logo.png" alt="PC2 Logo" class="school-logo">
                                <div class="school-text">
                                    <h3>
                                        <i class="fas fa-map-marker-alt" style="color: #fbbf24;"></i>
                                        SJK(C) Puay Chai 2 培才二校
                                    </h3>
                                    <p><i class="fas fa-location-dot" style="color: #94a3b8;"></i> Jln BU 3/1, Bandar Utama, Petaling Jaya</p>
                                </div>
                            </div>
                            <div class="school-toggle">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                        <div class="school-schedules">
                            <div class="school-schedules-inner">
                                <div style="display: flex; flex-direction: column; gap: 12px;">
                                    <label class="custom-checkbox" style="border: 2px solid #e2e8f0; border-radius: 12px;">
                                        <input type="checkbox" name="sch" value="SJK(C) Puay Chai 2: Tue 8pm-10pm">
                                        <div class="custom-checkbox-label">
                                            <div style="font-size: 14px; font-weight: bold; color: #1e293b; margin-bottom: 4px;">
                                                <i class="far fa-calendar" style="color: #fbbf24;"></i> Tuesday 星期二 · 8:00 PM - 10:00 PM
                                            </div>
                                            <div style="font-size: 12px; color: #64748b;">
                                                <span style="background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 4px; font-weight: 600;">只限于州队/后备队 Only for State/Backup Team</span>
                                                <span style="color: #94a3b8; margin-left: 4px;">(D/C/B/A 套路 Routine)</span>
                                            </div>
                                            <div class="disabled-msg" style="font-size: 10px; color: #dc2626; font-weight: bold; display: none; margin-top: 4px;">
                                                <i class="fas fa-ban"></i> Not available for Normal Students 普通学生不允许参加
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SCHOOL 3: Stadium Chinwoo -->
                    <div class="school-box">
                        <div class="school-header" onclick="toggleSchoolBox(this.parentElement)">
                            <div class="school-info">
                                <div class="school-logo" style="background: linear-gradient(135deg, #1e293b 0%, #475569 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 18px;">精</div>
                                <div class="school-text">
                                    <h3>
                                        <i class="fas fa-map-marker-alt" style="color: #fbbf24;"></i>
                                        Stadium Chinwoo 精武体育馆
                                    </h3>
                                    <p><i class="fas fa-location-dot" style="color: #94a3b8;"></i> Jalan Hang Jebat, Kuala Lumpur</p>
                                </div>
                            </div>
                            <div class="school-toggle">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                        <div class="school-schedules">
                            <div class="school-schedules-inner">
                                <div style="display: flex; flex-direction: column; gap: 12px;">
                                    <label class="custom-checkbox" style="border: 2px solid #e2e8f0; border-radius: 12px;">
                                        <input type="checkbox" name="sch" value="Stadium Chinwoo: Sun 2pm-4pm">
                                        <div class="custom-checkbox-label">
                                            <div style="font-size: 14px; font-weight: bold; color: #1e293b; margin-bottom: 4px;">
                                                <i class="far fa-calendar" style="color: #fbbf24;"></i> Sunday 星期日 · 2:00 PM - 4:00 PM
                                            </div>
                                            <div style="font-size: 12px; color: #64748b;">
                                                <span style="background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 4px; font-weight: 600;">只限于州队/后备队 Only for State/Backup Team</span>
                                            </div>
                                            <div class="disabled-msg" style="font-size: 10px; color: #dc2626; font-weight: bold; display: none; margin-top: 4px;">
                                                <i class="fas fa-ban"></i> Not available for Normal Students 普通学生不允许参加
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
                <h2 style="font-size: 20px; font-weight: bold; color: #1e293b; margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-file-signature" style="color: #fbbf24;"></i> 条款与协议 Agreement
                </h2>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 16px; border-radius: 0 8px 8px 0;">
                        <h4 style="font-weight: bold; color: #1e40af; font-size: 14px; margin-bottom: 4px;">学费缴付 · Fee Payment</h4>
                        <p style="font-size: 12px; color: #1e40af; line-height: 1.5;">
                            学费需在每月10号之前缴付。Fees must be paid before the 10th of every month.
                        </p>
                    </div>
                    <div style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 16px; border-radius: 0 8px 8px 0;">
                        <h4 style="font-weight: bold; color: #991b1b; font-size: 14px; margin-bottom: 4px;">运动员守则 · Code of Conduct</h4>
                        <p style="font-size: 12px; color: #991b1b; line-height: 1.5;">
                            严守纪律，必须守时。Athletes must be disciplined and punctual.
                        </p>
                    </div>
                </div>

                <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; height: 240px; overflow-y: auto; margin-bottom: 24px; font-size: 12px; line-height: 1.6;" class="custom-scroll">
                    <div style="text-align: center; margin-bottom: 16px;">
                        <h4 style="font-weight: bold; color: #1e293b; font-size: 14px;">
                            📋 TERMS & CONDITIONS 条款与条件
                        </h4>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <strong>1.</strong> 本人（学员/家长/监护人）确认上述资料属实。I confirm that all information provided is true and correct.
                    </div>
                    <div style="margin-bottom: 12px;">
                        <strong>2.</strong> 本人明白武术是一项剧烈运动。I understand that Wushu is a high-intensity sport.
                    </div>
                    <div style="margin-bottom: 12px;">
                        <strong>3.</strong> 学院有权调整训练时间或地点。The Academy reserves the right to adjust training times or venues.
                    </div>
                    <div style="margin-bottom: 12px;">
                        <strong>4.</strong> 学费一经缴付，概不退还。Fees paid are strictly non-refundable.
                    </div>
                    <div style="margin-bottom: 12px;">
                        <strong>5.</strong> 本人同意遵守学院及教练的所有指示。I agree to follow all instructions set by the Academy and coaches.
                    </div>
                    <div style="margin-bottom: 12px;">
                        <strong>6.</strong> 只限于本院通知取消课程，将会另行安排补课。Replacement classes are only provided when the Academy cancels a session.
                    </div>
                    <div style="margin-bottom: 12px;">
                        <strong>7.</strong> 如学员无法出席训练，必须向行政与教练申请请假。Leave must be applied for with admin and coach.
                    </div>
                    <div style="margin-bottom: 12px;">
                        <strong>8.</strong> 州队必须出席所有训练。State-team athletes must attend all training.
                    </div>
                    <div style="margin-bottom: 12px;">
                        <strong>9.</strong> 如因受伤或生病，请勿勉强出席训练。Students with injuries should not attend training.
                    </div>
                    <div style="margin-bottom: 12px;">
                        <strong>10.</strong> 本院不负责学员及家长的任何贵重财物。The Academy is not responsible for any valuables.
                    </div>
                </div>

                <!-- Signature Section -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px;">
                    <h4 style="font-weight: bold; color: #475569; margin-bottom: 16px; font-size: 14px; text-transform: uppercase;">Legal Declaration</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: bold; color: #64748b; margin-bottom: 4px;">Parent Name *</label>
                            <input type="text" id="parent-name" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; background: white;" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: bold; color: #64748b; margin-bottom: 4px;">Parent IC No. *</label>
                            <input type="text" id="parent-ic" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; background: white;" placeholder="000000-00-0000" maxlength="14" required>
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <label style="display: block; font-size: 12px; font-weight: bold; color: #64748b; margin-bottom: 4px;">Effective Date</label>
                            <input type="text" id="today-date" style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; background: #f1f5f9; color: #64748b; border-radius: 8px; font-size: 14px;" readonly>
                        </div>
                    </div>

                    <label style="display: block; font-size: 12px; font-weight: bold; color: #64748b; margin-bottom: 8px;">Parent's Signature (Sign Below) *</label>
                    
                    <div class="signature-box">
                        <canvas id="signature-canvas"></canvas>
                        <div id="sig-placeholder">SIGN HERE</div>
                        <button type="button" onclick="clearSignature(); return false;" class="clear-sig-btn">
                            <i class="fa-solid fa-eraser"></i> Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- STEP 6: Success -->
            <div id="step-6" class="step-content">
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
                            <li>Your registration has been submitted 您的报名已提交</li>
                            <li>Admin will review and create your account 管理员将审核并创建账户</li>
                            <li>Pay fees before the 10th of every month 请在每月10号前缴费</li>
                            <li>Send payment receipt to coach and admin 将收据发给教练和行政</li>
                        </ul>
                    </div>

                    <div style="display: flex; justify-content: center; gap: 16px;">
                        <button type="button" onclick="downloadPDF()" style="background: #16a34a; color: white; padding: 16px 32px; border-radius: 12px; font-weight: bold; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3); border: none; cursor: pointer; display: flex; align-items: center; gap: 12px; transition: transform 0.2s;">
                            <i class="fas fa-download" style="font-size: 20px;"></i>
                            <div style="text-align: left;">
                                <div>Download Agreement</div>
                                <div style="font-size: 14px; font-weight: normal;">下载协议</div>
                            </div>
                        </button>
                        <button type="button" onclick="submitAnother()" style="background: #9333ea; color: white; padding: 16px 32px; border-radius: 12px; font-weight: bold; box-shadow: 0 4px 12px rgba(147, 51, 234, 0.3); border: none; cursor: pointer; display: flex; align-items: center; gap: 12px; transition: transform 0.2s;">
                            <i class="fas fa-plus-circle" style="font-size: 20px;"></i>
                            <div style="text-align: left;">
                                <div>Submit Another</div>
                                <div style="font-size: 14px; font-weight: normal;">提交另一份</div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </div>

    <!-- Footer Buttons -->
    <div style="padding: 24px; background: white; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
        <button id="btn-prev" onclick="changeStep(-1)" style="padding: 10px 24px; border-radius: 12px; font-weight: 600; color: #64748b; background: transparent; border: none; cursor: pointer; transition: background 0.2s;" disabled>
            Back
        </button>
        <button id="btn-next" onclick="changeStep(1)" style="background: #1e293b; color: white; padding: 10px 32px; border-radius: 12px; font-weight: 600; box-shadow: 0 4px 12px rgba(30, 41, 59, 0.3); border: none; cursor: pointer;">
            Next Step <i class="fa-solid fa-arrow-right"></i>
        </button>
    </div>
</div>

<!-- HIDDEN PDF TEMPLATE - EXACT MATCH -->
<!-- HIDDEN PDF TEMPLATE - EXACT A4 FORMAT WITH LETTERHEAD -->
<div id="pdf-template" style="width: 210mm; margin: 0 auto;">
    
    <!-- PAGE 1 -->
    <div class="pdf-page" style="width: 210mm; height: 297mm; padding: 15mm; margin-bottom: 20px; background: white; font-family: Arial, sans-serif; position: relative;">
        
        <!-- Letterhead Image -->
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="/assets/WSP Letter.png" style="max-width: 100%; height: auto; max-height: 80px;">
        </div>

        <!-- Title -->
        <h1 style="text-align: center; font-size: 18px; font-weight: 800; margin: 15px 0 3px 0; color: #000; letter-spacing: 1px;">OFFICIAL WUSHU REGISTRATION 2026</h1>
        <p style="text-align: center; font-size: 11px; color: #666; margin: 0 0 8px 0;">Legal Binding Document • This form confirms participation in Wushu Sports Academy programmes.</p>

        <!-- Student Details Section -->
        <div style="margin-bottom: 15px;">
            <div style="background: #e8e8e8; padding: 6px 10px; font-weight: 700; font-size: 11px; margin-bottom: 0px; border-bottom: 2px solid #333;">STUDENT DETAILS / 学员资料</div>
            <table style="width: 100%; border-collapse: collapse; border: 1px solid #999; font-size: 11px;">
                <tr style="background: #f5f5f5;">
                    <td style="padding: 8px 10px; border-right: 1px solid #999; border-bottom: 1px solid #999; width: 35%; font-weight: 600;">Name 姓名:</td>
                    <td style="padding: 8px 10px; border-bottom: 1px solid #999;" id="pdf-name"></td>
                </tr>
                <tr style="background: #f5f5f5;">
                    <td style="padding: 8px 10px; border-right: 1px solid #999; border-bottom: 1px solid #999; font-weight: 600;">IC No 身份证:</td>
                    <td style="padding: 8px 10px; border-bottom: 1px solid #999;" id="pdf-ic"></td>
                </tr>
                <tr style="background: #f5f5f5;">
                    <td style="padding: 8px 10px; border-right: 1px solid #999; border-bottom: 1px solid #999; font-weight: 600;">Age 年龄:</td>
                    <td style="padding: 8px 10px; border-bottom: 1px solid #999;" id="pdf-age"></td>
                </tr>
                <tr style="background: #f5f5f5;">
                    <td style="padding: 8px 10px; border-right: 1px solid #999; border-bottom: 1px solid #999; font-weight: 600;">School 学校:</td>
                    <td style="padding: 8px 10px; border-bottom: 1px solid #999;" id="pdf-school"></td>
                </tr>
                <tr style="background: #f5f5f5;">
                    <td style="padding: 8px 10px; border-right: 1px solid #999; border-bottom: 1px solid #999; font-weight: 600;">Status 身份:</td>
                    <td style="padding: 8px 10px; border-bottom: 1px solid #999;" id="pdf-status"></td>
                </tr>
            </table>
        </div>

        <!-- Contact & Events Section -->
        <div style="margin-bottom: 15px;">
            <div style="background: #e8e8e8; padding: 6px 10px; font-weight: 700; font-size: 11px; margin-bottom: 0px; border-bottom: 2px solid #333;">CONTACT & EVENTS / 联系与项目</div>
            <table style="width: 100%; border-collapse: collapse; border: 1px solid #999; font-size: 11px;">
                <tr style="background: #f5f5f5;">
                    <td style="padding: 8px 10px; border-right: 1px solid #999; border-bottom: 1px solid #999; width: 35%; font-weight: 600;">Phone 电话:</td>
                    <td style="padding: 8px 10px; border-bottom: 1px solid #999;" id="pdf-phone"></td>
                </tr>
                <tr style="background: #f5f5f5;">
                    <td style="padding: 8px 10px; border-right: 1px solid #999; border-bottom: 1px solid #999; font-weight: 600;">Email 邮箱:</td>
                    <td style="padding: 8px 10px; border-bottom: 1px solid #999;" id="pdf-email"></td>
                </tr>
                <tr style="background: #f5f5f5;">
                    <td style="padding: 8px 10px; border-right: 1px solid #999; border-bottom: 1px solid #999; font-weight: 600;">Level 等级:</td>
                    <td style="padding: 8px 10px; border-bottom: 1px solid #999;" id="pdf-level"></td>
                </tr>
                <tr style="background: #f5f5f5;">
                    <td style="padding: 8px 10px; border-right: 1px solid #999; border-bottom: 1px solid #999; font-weight: 600;">Events 项目:</td>
                    <td style="padding: 8px 10px; border-bottom: 1px solid #999;" id="pdf-events"></td>
                </tr>
                <tr style="background: #f5f5f5;">
                    <td style="padding: 8px 10px; border-right: 1px solid #999; border-bottom: 1px solid #999; font-weight: 600;">Schedule 时间:</td>
                    <td style="padding: 8px 10px; border-bottom: 1px solid #999;" id="pdf-schedule"></td>
                </tr>
            </table>
        </div>

        <!-- Declaration & Signature Section -->
        <div style="margin-bottom: 15px;">
            <div style="background: #e8e8e8; padding: 6px 10px; font-weight: 700; font-size: 11px; margin-bottom: 8px; border-bottom: 2px solid #333;">DECLARATION & SIGNATURE / 声明与签名</div>
            
            <p style="font-size: 10px; line-height: 1.5; margin: 8px 0; color: #333;">
                I hereby confirm that all information provided is accurate. I have read and agreed to the <strong>Terms & Conditions</strong>, <strong>Fee Policy</strong>, and <strong>Athlete Code of Conduct</strong>. I understand that Wushu is a high-intensity sport and agree to bear the risks involved.
            </p>

            <!-- Signature Box -->
            <div style="border: 2px solid #333; padding: 12px; width: 280px; height: 120px; margin: 10px 0; background: #fafafa; display: flex; align-items: center; justify-content: center;">
                <img id="pdf-sig-img" style="max-width: 100%; max-height: 100%; object-fit: contain;">
            </div>

            <table style="width: 100%; font-size: 11px; line-height: 1.8;">
                <tr>
                    <td style="padding: 4px 0;"><strong>Parent / Guardian Name:</strong></td>
                    <td style="padding: 4px 0;" id="pdf-parent-name"></td>
                </tr>
                <tr>
                    <td style="padding: 4px 0;"><strong>Parent / Guardian IC No.:</strong></td>
                    <td style="padding: 4px 0;" id="pdf-parent-ic"></td>
                </tr>
                <tr>
                    <td style="padding: 4px 0;"><strong>Date:</strong></td>
                    <td style="padding: 4px 0;" id="pdf-date"></td>
                </tr>
            </table>
        </div>

        <!-- Notes Section -->
        <div style="background: #fffacd; border: 2px solid #ffd700; padding: 10px; border-radius: 3px; margin-top: 10px; font-size: 9.5px; line-height: 1.5; color: #333;">
            <p style="margin: 0;"><strong>NOTES / 备注:</strong> Fees are non-refundable and must be paid by the 10th of every month. Strict discipline and punctuality are required at all times. The Academy reserves the right to adjust training schedules and venues when necessary. 学费概不退还,并须在每月10号前缴清。学员必须严守纪律与守时;学院保留在有需要时调整训练时间及地点的权利。</p>
        </div>
    </div>

    <!-- PAGE 2 -->
    <div class="pdf-page" style="width: 210mm; height: 297mm; padding: 15mm; background: white; font-family: Arial, sans-serif;">
        
        <!-- Letterhead Image -->
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="/assets/WSP Letter.png" style="max-width: 100%; height: auto; max-height: 80px;">
        </div>

        <!-- Title -->
        <h1 style="text-align: center; font-size: 18px; font-weight: 800; margin: 15px 0 2px 0; color: #000;">TERMS & CONDITIONS</h1>
        <p style="text-align: center; font-size: 11px; color: #666; margin: 0 0 15px 0;">条款与条件 · Agreed and Signed by Parent/Guardian</p>

        <p style="font-size: 10px; margin-bottom: 12px; color: #333; line-height: 1.4; font-weight: 600;">The parent/guardian has read, understood, and agreed to the following terms:</p>

        <!-- Terms List -->
        <div style="font-size: 9.5px; line-height: 1.7; color: #333;">
            <table style="width: 100%; margin-bottom: 12px; border-collapse: collapse;">
                <tr>
                    <td style="vertical-align: top; width: 25px; padding-right: 8px; font-weight: 700; background: #000; color: white; text-align: center; border-radius: 50%; height: 24px; line-height: 24px;">1</td>
                    <td style="padding-bottom: 10px; padding-left: 8px;">
                        <strong>本人(学员/家长/监护人)确认上述资料属实。</strong><br>
                        <em>I, the student/parent/guardian, confirm that all information provided above is true and correct.</em>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top; font-weight: 700; background: #000; color: white; text-align: center; border-radius: 50%; height: 24px; line-height: 24px;">2</td>
                    <td style="padding-bottom: 10px; padding-left: 8px;">
                        <strong>本人明白武术是一项剧烈运动,并愿意自行承担训练期间可能发生的意外风险。</strong><br>
                        <em>I understand that Wushu is a high-intensity sport and agree to bear any risk of injury during training.</em>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top; font-weight: 700; background: #000; color: white; text-align: center; border-radius: 50%; height: 24px; line-height: 24px;">3</td>
                    <td style="padding-bottom: 10px; padding-left: 8px;">
                        <strong>学院有权在必要时调整训练时间或地点,并将提前通知。</strong><br>
                        <em>The Academy reserves the right to adjust training times or venues when necessary and will notify in advance.</em>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top; font-weight: 700; background: #000; color: white; text-align: center; border-radius: 50%; height: 24px; line-height: 24px;">4</td>
                    <td style="padding-bottom: 10px; padding-left: 8px;">
                        <strong>学费一经缴付,概不退还 (Non-refundable)。</strong><br>
                        <em>Fees paid are strictly non-refundable under all circumstances.</em>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top; font-weight: 700; background: #000; color: white; text-align: center; border-radius: 50%; height: 24px; line-height: 24px;">5</td>
                    <td style="padding-bottom: 10px; padding-left: 8px;">
                        <strong>本人同意遵守学院及教练的所有指示与安排。</strong><br>
                        <em>I agree to follow all instructions, rules, and arrangements set by the Academy and coaches.</em>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top; font-weight: 700; background: #000; color: white; text-align: center; border-radius: 50%; height: 24px; line-height: 24px;">6</td>
                    <td style="padding-bottom: 10px; padding-left: 8px;">
                        <strong>只限于本院通知取消课程,将会另行安排补课,家长不允许自行取消课程。</strong><br>
                        <em>Replacement classes are only provided when the Academy cancels a session; parents may not cancel classes on their own.</em>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top; font-weight: 700; background: #000; color: white; text-align: center; border-radius: 50%; height: 24px; line-height: 24px;">7</td>
                    <td style="padding-bottom: 10px; padding-left: 8px;">
                        <strong>如学员因病或其他原因无法出席训练,必须向行政与教练申请请假;未经许可的缺席将被记录。</strong><br>
                        <em>If the student cannot attend due to sickness or other reasons, leave must be applied for with admin and coach; unapproved absences will be recorded.</em>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top; font-weight: 700; background: #000; color: white; text-align: center; border-radius: 50%; height: 24px; line-height: 24px;">8</td>
                    <td style="padding-bottom: 10px; padding-left: 8px;">
                        <strong>州队及后备队必须出席所有训练,保持良好态度,接受严格训练与训导。</strong><br>
                        <em>State-team and reserve athletes must attend all training, maintain good attitude, and accept strict training and discipline.</em>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top; font-weight: 700; background: #000; color: white; text-align: center; border-radius: 50%; height: 24px; line-height: 24px;">9</td>
                    <td style="padding-bottom: 10px; padding-left: 8px;">
                        <strong>如因脚受伤、扭伤或生病,请勿勉强出席训练,后果自负。</strong><br>
                        <em>Students with injuries or illness should not attend training; any consequences are at their own risk.</em>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top; font-weight: 700; background: #000; color: white; text-align: center; border-radius: 50%; height: 24px; line-height: 24px;">10</td>
                    <td style="padding-bottom: 10px; padding-left: 8px;">
                        <strong>本院不负责学员及家长的任何贵重财物。</strong><br>
                        <em>The Academy is not responsible for any valuables belonging to students or parents.</em>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top; font-weight: 700; background: #000; color: white; text-align: center; border-radius: 50%; height: 24px; line-height: 24px;">11</td>
                    <td style="padding-bottom: 10px; padding-left: 8px;">
                        <strong>不允许打架、吵架、态度恶劣或不配合训练,否则将被取消州队及学员资格。</strong><br>
                        <em>Fighting, quarrelling, poor attitude, or refusing to cooperate with training may result in removal from the state team and the Academy.</em>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top; font-weight: 700; background: #000; color: white; text-align: center; border-radius: 50%; height: 24px; line-height: 24px;">12</td>
                    <td style="padding-bottom: 10px; padding-left: 8px;">
                        <strong>训练期间不允许大吃大喝,只能在休息时间喝水。</strong><br>
                        <em>Eating is not allowed during training; only drinking water during breaks is permitted.</em>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top; font-weight: 700; background: #000; color: white; text-align: center; border-radius: 50%; height: 24px; line-height: 24px;">13</td>
                    <td style="padding-bottom: 10px; padding-left: 8px;">
                        <strong>家长不允许干涉教练所设的专业训练计划及纪律管理。</strong><br>
                        <em>Parents are not allowed to interfere with professional training plans or discipline set by the coaches.</em>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top; font-weight: 700; background: #000; color: white; text-align: center; border-radius: 50%; height: 24px; line-height: 24px;">14</td>
                    <td style="padding-bottom: 10px; padding-left: 8px;">
                        <strong>家长必须准时接送学生,并自行负责交通安全。</strong><br>
                        <em>Parents must send and pick up their children on time and are fully responsible for transport safety.</em>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top; font-weight: 700; background: #000; color: white; text-align: center; border-radius: 50%; height: 24px; line-height: 24px;">15</td>
                    <td style="padding-bottom: 10px; padding-left: 8px;">
                        <strong>训练过程中,学员可能被录影或拍照作宣传用途,如家长不同意,须以书面通知本院。</strong><br>
                        <em>Training sessions may be recorded or photographed for publicity; parents who do not consent must inform the Academy in writing.</em>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Legal Acknowledgement -->
        <div style="border: 2px solid #333; padding: 12px; margin-top: 15px; border-radius: 4px;">
            <h3 style="margin: 0 0 8px 0; font-size: 11px; font-weight: 700;">LEGAL ACKNOWLEDGEMENT / 法律声明</h3>
            <p style="font-size: 9.5px; line-height: 1.5; margin: 0 0 8px 0; color: #333;">
                By signing this document, the parent/guardian acknowledges that they have read, understood, and agreed to all 15 terms and conditions listed above.
            </p>
            <p style="font-size: 9.5px; line-height: 1.5; margin: 0; color: #333;">
                家长/监护人签署此文件,即表示已阅读、理解并同意上述所有15项条款与条件。
            </p>
            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd;">
                <p style="font-size: 10px; margin: 4px 0;"><strong>Signed by:</strong> <span id="pdf-sign-name"></span> (<span id="pdf-sign-ic"></span>)</p>
                <p style="font-size: 10px; margin: 4px 0;"><strong>Date:</strong> <span id="pdf-sign-date"></span></p>
            </div>
        </div>
    </div>
</div>




<!-- LOADING OVERLAY -->
<div id="pdf-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center;">
    <div style="text-align: center; color: white;">
        <div style="width: 60px; height: 60px; border: 5px solid rgba(255,255,255,0.3); border-top: 5px solid white; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
        <h3 style="font-size: 20px; margin: 0;">Processing Registration...</h3>
        <p style="margin-top: 10px; font-size: 14px; opacity: 0.8;">Please wait</p>
    </div>
</div>

<script>
    // ========================================
    // GLOBAL VARIABLES
    // ========================================
    let sigCanvas, sigCtx, sigPlaceholder;
    let isDrawingSig = false;
    let hasSigned = false;
    let signatureInitialized = false;
    
    let currentStep = 1;
    const totalSteps = 6;
    const pdfOverlay = document.getElementById('pdf-overlay');
    let registrationData = null;

    // ========================================
    // DOM CONTENT LOADED
    // ========================================
    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById('today-date').value = new Date().toLocaleDateString('en-GB');
        document.getElementById('ic').addEventListener('input', formatIC);
        document.getElementById('ic').addEventListener('input', calculateAge);
        document.getElementById('parent-ic').addEventListener('input', formatIC);
        document.getElementById('phone').addEventListener('input', formatPhone);
        
        // Status radio change
        const statusRadios = document.getElementsByName('status');
        statusRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                updateScheduleAvailability();
                updateStatusRadioStyle();
            });
        });

        // School select change
        document.getElementById('school').addEventListener('change', toggleOtherSchool);
        
        updateScheduleAvailability();
        updateStatusRadioStyle();
    });

    // ========================================
    // SIGNATURE FUNCTIONS - LAZY INIT
    // ========================================
    function initSignature() {
        if (signatureInitialized) {
            console.log('Signature already initialized');
            return;
        }

        sigCanvas = document.getElementById('signature-canvas');
        sigCtx = sigCanvas.getContext('2d');
        sigPlaceholder = document.getElementById('sig-placeholder');

        if (!sigCanvas || !sigCtx) {
            console.error('Signature canvas not found!');
            return;
        }

        const parent = sigCanvas.parentElement;
        sigCanvas.width = parent.offsetWidth;
        sigCanvas.height = parent.offsetHeight;
        
        sigCtx.strokeStyle = '#000000';
        sigCtx.lineWidth = 3;
        sigCtx.lineCap = 'round';
        sigCtx.lineJoin = 'round';
        
        // Mouse events
        sigCanvas.addEventListener('mousedown', handleMouseDown);
        sigCanvas.addEventListener('mousemove', handleMouseMove);
        sigCanvas.addEventListener('mouseup', handleMouseUp);
        sigCanvas.addEventListener('mouseleave', handleMouseUp);
        
        // Touch events
        sigCanvas.addEventListener('touchstart', handleTouchStart);
        sigCanvas.addEventListener('touchmove', handleTouchMove);
        sigCanvas.addEventListener('touchend', handleTouchEnd);
        
        signatureInitialized = true;
        console.log('✅ Signature canvas initialized:', sigCanvas.width, 'x', sigCanvas.height);
    }

    function handleMouseDown(e) {
        isDrawingSig = true;
        const rect = sigCanvas.getBoundingClientRect();
        sigCtx.beginPath();
        sigCtx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
        hasSigned = true;
        sigPlaceholder.style.display = 'none';
    }

    function handleMouseMove(e) {
        if (!isDrawingSig) return;
        const rect = sigCanvas.getBoundingClientRect();
        sigCtx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
        sigCtx.stroke();
    }

    function handleMouseUp() {
        isDrawingSig = false;
    }

    function handleTouchStart(e) {
        e.preventDefault();
        isDrawingSig = true;
        const rect = sigCanvas.getBoundingClientRect();
        const touch = e.touches[0];
        sigCtx.beginPath();
        sigCtx.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
        hasSigned = true;
        sigPlaceholder.style.display = 'none';
    }

    function handleTouchMove(e) {
        if (!isDrawingSig) return;
        e.preventDefault();
        const rect = sigCanvas.getBoundingClientRect();
        const touch = e.touches[0];
        sigCtx.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
        sigCtx.stroke();
    }

    function handleTouchEnd(e) {
        e.preventDefault();
        isDrawingSig = false;
    }

    function clearSignature() {
        if (!sigCanvas || !sigCtx) return;
        sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
        hasSigned = false;
        if (sigPlaceholder) {
            sigPlaceholder.style.display = 'flex';
        }
    }

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
            } else {
                ageInput.value = age;
            }
        } else {
            ageInput.value = '';
        }
    }

    function toggleOtherSchool() {
        const schoolSelect = document.getElementById('school');
        const otherInput = document.getElementById('school-other');
        
        if (schoolSelect.value === 'Others') {
            otherInput.style.display = 'block';
            otherInput.required = true;
        } else {
            otherInput.style.display = 'none';
            otherInput.required = false;
            otherInput.value = '';
        }
    }

    function toggleSchoolBox(element) {
        element.classList.toggle('active');
    }

    // ========================================
    // STATUS RADIO STYLE UPDATE
    // ========================================
    function updateStatusRadioStyle() {
        const radios = document.querySelectorAll('.status-radio');
        radios.forEach(radio => {
            const option = radio.nextElementSibling;
            if (radio.checked) {
                option.style.background = '#7c3aed';
                option.style.color = 'white';
                option.style.borderColor = '#7c3aed';
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
    // SCHEDULE AVAILABILITY
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

        const restrictedClasses = [
            "SJK(C) Puay Chai 2: Tue 8pm-10pm",
            "Wushu Sport Academy: Sun 10am-12pm",
            "Stadium Chinwoo: Sun 2pm-4pm"
        ];

        restrictedClasses.forEach(val => {
            const checkbox = document.querySelector(`input[name="sch"][value="${val}"]`);
            
            if (checkbox) {
                const container = checkbox.closest('label');
                const errorMsg = container ? container.querySelector('.disabled-msg') : null;

                if (isRegularStudent) {
                    checkbox.checked = false;
                    checkbox.disabled = true;
                    if (container) {
                        container.style.opacity = '0.5';
                        container.style.cursor = 'not-allowed';
                        container.style.background = '#f1f5f9';
                    }
                    if (errorMsg) errorMsg.style.display = 'block';
                } else {
                    checkbox.disabled = false;
                    if (container) {
                        container.style.opacity = '1';
                        container.style.cursor = 'pointer';
                        container.style.background = 'white';
                    }
                    if (errorMsg) errorMsg.style.display = 'none';
                }
            }
        });
    }

    // ========================================
    // STEP NAVIGATION
    // ========================================
    function changeStep(dir) {
        if (dir === 1 && !validateStep(currentStep)) {
            return;
        }
        
        if (dir === 1 && currentStep === 5) {
            submitForm();
            return;
        }

        document.getElementById(`step-${currentStep}`).classList.remove('active');
        currentStep += dir;
        document.getElementById(`step-${currentStep}`).classList.add('active');

        // Initialize signature when reaching step 5
        if (currentStep === 5) {
            setTimeout(initSignature, 100);
        }

        document.getElementById('btn-prev').disabled = (currentStep === 1);
        document.getElementById('btn-next').style.display = (currentStep === 6) ? 'none' : 'block';

        const stepCounter = document.getElementById('step-counter');
        stepCounter.innerHTML = `0${currentStep}<span style="color: #475569; font-size: 14px;">/0${totalSteps}</span>`;

        const progressBar = document.getElementById('progress-bar');
        progressBar.style.width = `${(currentStep / totalSteps) * 100}%`;

        window.scrollTo({ top: 0, behavior: 'smooth' });
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
        }

        return true;
    }

    // ========================================
    // FORM SUBMISSION
    // ========================================
    async function submitForm() {
        pdfOverlay.style.display = 'flex';

        try {
            const nameCn = document.getElementById('name-cn').value.trim();
            const nameEn = document.getElementById('name-en').value.trim();
            const ic = document.getElementById('ic').value;
            const age = document.getElementById('age').value;
            const school = document.getElementById('school').value === 'Others' 
                ? document.getElementById('school-other').value.trim() 
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

            const events = Array.from(document.querySelectorAll('input[name="evt"]:checked'))
                .map(el => el.value)
                .join(', ');

            const schedules = Array.from(document.querySelectorAll('input[name="sch"]:checked'))
                .map(el => el.value)
                .join(', ');

            const parentName = document.getElementById('parent-name').value;
            const parentIC = document.getElementById('parent-ic').value;
            const formDate = document.getElementById('today-date').value;

            const signatureBase64 = sigCanvas.toDataURL('image/png');

            const displayName = nameCn ? `${nameEn} (${nameCn})` : nameEn;

            // Determine level from events
            let level = '';
            const levelPrefixes = ['基础', '初级', 'B组', 'A组', '自选'];
            for (const prefix of levelPrefixes) {
                if (events.includes(prefix)) {
                    if (level) level += ', ';
                    level += prefix;
                }
            }
            if (!level) level = 'Not specified';

            // Store for PDF re-download
            registrationData = {
                nameCn, nameEn, namePlain: nameEn, displayName, ic, age, school, status,
                phone, email, level, events, schedule: schedules,
                parent: parentName, parentIC, date: formDate, signature: signatureBase64
            };

            // Generate PDF
            const pdfBase64 = await generatePDFFile();

            // Submit to server
            const payload = {
                name_cn: nameCn,
                name_en: nameEn,
                ic,
                age,
                school,
                status,
                phone,
                email,
                level,
                events,
                schedule: schedules,
                parent_name: parentName,
                parent_ic: parentIC,
                form_date: formDate,
                signature_base64: signatureBase64,
                signed_pdf_base64: pdfBase64
            };

            const response = await fetch('../process_registration.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            pdfOverlay.style.display = 'none';

            if (result.success) {
                document.getElementById('reg-number-display').innerText = 
                    `Registration Number: ${result.registration_number}`;
                changeStep(1);
                
                Swal.fire({
                    icon: 'success',
                    title: 'Registration Successful!',
                    html: `Your registration number is:<br><strong>${result.registration_number}</strong>`,
                    confirmButtonText: 'OK'
                });
            } else {
                Swal.fire('Error', result.error || 'Registration failed', 'error');
            }

        } catch (error) {
            pdfOverlay.style.display = 'none';
            console.error('Error:', error);
            Swal.fire('Error', 'An error occurred during submission', 'error');
        }
    }

    // ========================================
    // PDF GENERATION
    // ========================================
    async function generatePDFFile() {
    if (!registrationData) return null;

    const { displayName, ic, age, school, status, phone, email, level, events, schedule, parent, parentIC, date, signature } = registrationData;

    // Fill PDF data for PAGE 1
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

    // Fill PDF data for PAGE 2
    document.getElementById('pdf-parent-name-2').innerText = parent;
    document.getElementById('pdf-parent-ic-2').innerText = parentIC;
    document.getElementById('pdf-date-2').innerText = date;

    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF('p', 'mm', 'a4');

    const pdfTemplate = document.getElementById('pdf-template');
    pdfTemplate.style.visibility = 'visible';
    pdfTemplate.style.display = 'block';

    const pages = pdfTemplate.querySelectorAll('.pdf-page');
    
    for (let i = 0; i < pages.length; i++) {
        const page = pages[i];
        
        // Make only this page visible
        pages.forEach(p => p.style.display = 'none');
        page.style.display = 'block';
        
        const canvas = await html2canvas(page, {
            scale: 2,
            useCORS: true,
            width: 794,
            height: 1123,
            logging: false,
            backgroundColor: '#ffffff'
        });

        const imgData = canvas.toDataURL('image/jpeg', 0.95);
        const pdfWidth = 210;
        const pdfHeight = 297; // A4 height

        if (i > 0) {
            pdf.addPage();
        }
        
        pdf.addImage(imgData, 'JPEG', 0, 0, pdfWidth, pdfHeight);
    }

    // Reset visibility
    pages.forEach(p => p.style.display = 'block');
    pdfTemplate.style.visibility = 'hidden';
    pdfTemplate.style.display = 'none';

    // Download PDF
    pdf.save(`${registrationData.namePlain || 'Registration'}_Signed_Agreement.pdf`);
    
    // Return base64 for database
    return pdf.output('datauristring').split(',')[1];
}



    function downloadPDF() {
        if (registrationData) {
            generatePDFFile();
        }
    }

    function submitAnother() {
        location.reload();
    }
</script>

</body>
</html>
