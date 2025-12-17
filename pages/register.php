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

    <!-- Form Body continues with all steps... -->