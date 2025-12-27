<?php
// Generate CSRF token if not exists
//if (empty($_SESSION['csrf_token'])) {
//  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2026 WSA Wushu Registration | Enrollment Form</title>

    <!-- ✨ NEW: Favicon -->
    <link rel="icon" type="image/png"
        href="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/Wushu+Sport+Academy+Circle+Yellow.png">
    <link rel="shortcut icon" type="image/png"
        href="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/Wushu+Sport+Academy+Circle+Yellow.png">
    <link rel="apple-touch-icon"
        href="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/Wushu+Sport+Academy+Circle+Yellow.png">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Howler.js Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/howler/2.2.4/howler.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- jsPDF + html2canvas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <script src="register-parts/fee_calculator.js"></script>

    <style>
        /* Apply beautiful fonts globally to registration page */
        * {
            font-family: 'Inter', 'Noto Sans SC', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            font-family: 'Inter', 'Noto Sans SC', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
    </style>

</head>
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

    .step-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .custom-scroll::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scroll::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    .custom-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

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
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
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
    #pdf-template-page1,
    #pdf-template-page2 {
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

    /* ========================================
           MOBILE RESPONSIVE - PAYMENT SECTION
           ======================================== */

    @media (max-width: 640px) {

        /* Step 6 Payment Section */
        #step-6 .bg-gradient-to-r {
            padding: 16px !important;
        }

        #step-6 h3 {
            font-size: 15px !important;
            margin-bottom: 12px !important;
        }

        /* Fee Calculation Box */
        #step-6 .bg-white.rounded-lg {
            padding: 12px !important;
        }

        #step-6 .bg-white.rounded-lg>div {
            margin-bottom: 8px !important;
            font-size: 13px !important;
        }

        #step-6 .bg-white.rounded-lg>div span:first-child {
            font-size: 12px !important;
        }

        #step-6 #payment-class-count,
        #step-6 #payment-status {
            font-size: 14px !important;
        }

        /* Total Amount Display */
        #step-6 .border-t-2 {
            padding-top: 10px !important;
            margin-top: 10px !important;
        }

        #step-6 .border-t-2 .flex {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 4px !important;
        }

        #step-6 .border-t-2 span:first-child {
            font-size: 14px !important;
        }

        #step-6 #payment-total {
            font-size: 28px !important;
        }

        /* Fee Structure Info Box */
        #step-6 .bg-blue-50 {
            padding: 10px !important;
            font-size: 11px !important;
        }

        #step-6 .bg-blue-50 p {
            font-size: 11px !important;
            line-height: 1.4 !important;
        }

        /* Bank Details Section */
        #step-6 .bg-slate-50 {
            padding: 16px !important;
        }

        #step-6 .bg-slate-50 h3 {
            font-size: 14px !important;
            margin-bottom: 12px !important;
        }

        #step-6 .bg-slate-50 .space-y-3 {
            gap: 10px !important;
        }

        #step-6 .bg-slate-50 .bg-white {
            padding: 10px !important;
        }

        #step-6 .bg-slate-50 .w-8.h-8 {
            width: 28px !important;
            height: 28px !important;
            font-size: 12px !important;
        }

        #step-6 .bg-slate-50 p {
            font-size: 11px !important;
        }

        #step-6 .bg-slate-50 .font-bold {
            font-size: 13px !important;
        }

        #step-6 .bg-slate-50 .text-lg {
            font-size: 16px !important;
        }

        /* Upload Receipt Section */
        #step-6 .bg-white.border-2 {
            padding: 16px !important;
        }

        #step-6 .bg-white.border-2 h3 {
            font-size: 14px !important;
            margin-bottom: 12px !important;
        }

        #step-6 .border-2.border-dashed {
            padding: 20px !important;
        }

        #step-6 .fa-cloud-upload-alt {
            font-size: 32px !important;
            margin-bottom: 8px !important;
        }

        #step-6 .bg-yellow-50 {
            padding: 10px !important;
            font-size: 11px !important;
        }

        #step-6 .bg-yellow-50 p {
            font-size: 11px !important;
            line-height: 1.4 !important;
        }

        /* Payment Date Input */
        #step-6 #payment-date {
            font-size: 14px !important;
            padding: 10px !important;
        }

        /* General mobile adjustments */
        body {
            padding: 10px !important;
        }

        .glass-card {
            border-radius: 16px !important;
        }

        /* School box mobile */
        .school-logo {
            width: 40px !important;
            height: 40px !important;
        }

        .school-text h3 {
            font-size: 14px !important;
        }

        .school-text p {
            font-size: 11px !important;
        }

        .school-header {
            padding: 12px 16px !important;
        }

        .school-schedules-inner {
            padding: 0 16px 16px 16px !important;
        }
    }

    /* Mobile responsive styles for footer buttons */
    @media (max-width: 768px) {
        #footer-buttons {
            padding: 16px !important;
            gap: 8px !important;
        }

        #btn-prev {
            padding: 8px 12px !important;
            gap: 6px !important;
        }

        #back-btn-icon {
            width: 24px !important;
            height: 24px !important;
        }

        #back-btn-icon i {
            font-size: 11px !important;
        }

        #back-btn-text {
            font-size: 12px !important;
        }

        #back-btn-subtext {
            font-size: 8px !important;
        }

        #btn-next {
            padding: 8px 20px !important;
            font-size: 14px !important;
        }
    }

    /* ========================================
   MUSIC ENABLE OVERLAY
   ======================================== */
    .music-enable-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(10px);
        z-index: 10000;
        display: flex;
        justify-content: center;
        align-items: center;
        animation: fadeIn 0.3s ease;
    }

    .music-enable-overlay.fade-out {
        animation: fadeOut 0.3s ease forwards;
    }

    .music-enable-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        text-align: center;
        max-width: 450px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: slideUp 0.5s ease;
    }

    .music-enable-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        animation: pulse 2s ease-in-out infinite;
    }

    .music-enable-icon i {
        font-size: 36px;
        color: #1e293b;
    }

    .music-enable-card h3 {
        font-size: 24px;
        color: #1e293b;
        margin-bottom: 10px;
        font-weight: bold;
    }

    .music-enable-card p {
        font-size: 16px;
        color: #64748b;
        margin-bottom: 8px;
    }

    .music-enable-subtitle {
        font-size: 14px !important;
        color: #94a3b8 !important;
        margin-bottom: 24px !important;
    }

    .enable-music-btn,
    .skip-music-btn {
        padding: 14px 32px;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin: 8px;
    }

    .enable-music-btn {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: #1e293b;
        box-shadow: 0 4px 15px rgba(251, 191, 36, 0.4);
    }

    .enable-music-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(251, 191, 36, 0.6);
    }

    .skip-music-btn {
        background: #e5e7eb;
        color: #64748b;
    }

    .skip-music-btn:hover {
        background: #d1d5db;
        transform: translateY(-2px);
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
        }

        to {
            opacity: 0;
        }
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }
    }

    /* Responsive for overlay */
    @media (max-width: 768px) {
        .music-enable-card {
            padding: 30px 20px;
            max-width: 90%;
            margin: 20px;
        }

        .music-enable-card h3 {
            font-size: 20px;
        }

        .enable-music-btn,
        .skip-music-btn {
            width: 100%;
            margin: 6px 0;
        }
    }

    /* ========================================
   FLOATING MUSIC CONTROL BUTTON
   ======================================== */
    .music-control {
        position: fixed;
        top: 30px;
        right: 30px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 10px;
    }

    .music-toggle-btn,
    .music-menu-btn {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        border: 3px solid #1e293b;
        box-shadow: 0 4px 15px rgba(251, 191, 36, 0.4);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        color: #1e293b;
        font-size: 20px;
        animation: float 3s ease-in-out infinite;
    }

    .music-menu-btn {
        width: 50px;
        height: 50px;
        font-size: 18px;
        background: linear-gradient(135deg, #7c3aed, #6d28d9);
        animation-delay: 0.5s;
    }

    .music-toggle-btn:hover,
    .music-menu-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(251, 191, 36, 0.6);
    }

    .music-toggle-btn:active,
    .music-menu-btn:active {
        transform: scale(0.95);
    }

    /* Music Menu Dropdown */
    .music-menu {
        position: absolute;
        top: 130px;
        right: 0;
        width: 250px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        overflow: hidden;
        transition: all 0.3s ease;
        border: 2px solid #1e293b;
    }

    .music-menu.hidden {
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
    }

    .music-menu-header {
        background: linear-gradient(135deg, #1e293b, #334155);
        color: white;
        padding: 12px 16px;
        font-weight: bold;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .music-menu-items {
        max-height: 300px;
        overflow-y: auto;
    }

    .music-item {
        padding: 12px 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        color: #1e293b;
        border-bottom: 1px solid #e5e7eb;
    }

    .music-item:last-child {
        border-bottom: none;
    }

    .music-item:hover {
        background: #f8fafc;
        padding-left: 20px;
    }

    .music-item.active {
        background: #dcfce7;
        color: #16a34a;
        font-weight: bold;
    }

    .music-item.active i {
        color: #16a34a;
    }

    .music-item i {
        color: #7c3aed;
        font-size: 16px;
    }

    /* Floating animation */
    @keyframes float {

        0%,
        100% {
            transform: translateY(0px);
        }

        50% {
            transform: translateY(-10px);
        }
    }

    /* Scrollbar styling for menu */
    .music-menu-items::-webkit-scrollbar {
        width: 6px;
    }

    .music-menu-items::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    .music-menu-items::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 3px;
    }

    .music-menu-items::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }

    /* Responsive design - Side by Side for Mobile */
    @media (max-width: 768px) {
        .music-control {
            top: 20px;
            right: 20px;
            flex-direction: row;
            align-items: center;
            gap: 8px;
        }

        .music-toggle-btn {
            width: 50px;
            height: 50px;
            font-size: 18px;
        }

        .music-menu-btn {
            width: 45px;
            height: 45px;
            font-size: 16px;
        }

        .music-menu {
            width: 220px;
            top: 70px;
            right: 0;
        }
    }

    /* Extra small screens */
    @media (max-width: 480px) {
        .music-control {
            top: 15px;
            right: 15px;
            gap: 6px;
        }

        .music-toggle-btn {
            width: 45px;
            height: 45px;
            font-size: 16px;
            border: 2px solid #1e293b;
        }

        .music-menu-btn {
            width: 40px;
            height: 40px;
            font-size: 14px;
            border: 2px solid #1e293b;
        }

        .music-menu {
            width: 200px;
            top: 65px;
        }

        .music-menu-header {
            padding: 10px 12px;
            font-size: 13px;
        }

        .music-item {
            padding: 10px 12px;
            font-size: 13px;
        }
    }
</style>

<body>

    <!-- Music Enable Overlay -->
    <div id="musicEnableOverlay" class="music-enable-overlay">
        <div class="music-enable-card">
            <div class="music-enable-icon">
                <i class="fas fa-music"></i>
            </div>
            <h3>🎵 Enable Background Music?</h3>
            <p>Click anywhere to start music playback</p>
            <p class="music-enable-subtitle">点击任意位置启用背景音乐</p>
            <button id="enableMusicBtn" class="enable-music-btn">
                <i class="fas fa-play-circle"></i> Enable Music
            </button>
            <button id="skipMusicBtn" class="skip-music-btn">
                <i class="fas fa-times-circle"></i> Continue Without Music
            </button>
        </div>
    </div>

    <!-- Floating Music Control Button with Dropdown -->
    <div id="musicControl" class="music-control">
        <button id="musicToggle" class="music-toggle-btn" title="Play/Pause Music">
            <i class="fas fa-play" id="musicIcon"></i>
        </button>
        <button id="musicMenuToggle" class="music-menu-btn" title="Select Music">
            <i class="fas fa-music"></i>
        </button>

        <!-- Music Selection Dropdown -->
        <div id="musicMenu" class="music-menu hidden">
            <div class="music-menu-header">
                <i class="fas fa-headphones"></i> Select Music
            </div>
            <div class="music-menu-items">
                <div class="music-item"
                    data-src="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/music/No3+-+%E9%9D%9C%E5%BF%83%E3%80%81%E5%86%A5%E6%83%B3%E3%80%81%E5%BF%83%E9%9D%88%E7%92%B0%E4%BF%9D+-+1%E5%B0%8F%E6%99%82%EF%BD%9C%E6%B6%88%E9%99%A4%E7%84%A6%E6%85%AE%E3%80%81%E7%85%A9%E8%BA%81%EF%BC%8C%E9%81%A9%E5%90%88%E7%A6%AA%E4%BF%AE%E9%9D%9C%E5%9D%90%E5%8A%A9%E7%9C%A0+-+%E9%9F%B3%E6%A8%82%E7%A6%AAMusical+Zen+(youtube).mp3"
                    data-name="Relaxing Music">
                    <i class="fas fa-play-circle"></i> Relaxing Music
                </div>
                <div class="music-item"
                    data-src="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/music/No10+-+%E5%86%A5%E6%83%B3%E3%80%81%E7%91%9C%E7%8F%88%E3%80%81%E5%8A%A9%E7%9C%A0+-+1%E5%B0%8F%E6%99%82%EF%BD%9C%E9%81%A9%E5%90%88%E7%A6%AA%E4%BF%AE%E3%80%81%E8%AA%BF%E6%81%AF%E7%9A%84%E6%94%BE%E9%AC%86%E9%9F%B3%E6%A8%82+-+%E9%9F%B3%E6%A8%82%E7%A6%AAMusical+Zen+(youtube).mp3"
                    data-name="Relaxing Music2">
                    <i class="fas fa-play-circle"></i> Relaxing Music 2
                </div>
                <div class="music-item"
                    data-src="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/music/Titanic+-+Hymn+to+the+Sea+(whistle+version+by+Leyna+Robinson-Stone)+-+Leyna+Robinson-Stone+(youtube).mp3"
                    data-name="Titanic">
                    <i class="fas fa-play-circle"></i> Titanic
                </div>
                <div class="music-item" data-src="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/music/Song.mp3"
                    data-name="Song">
                    <i class="fas fa-play-circle"></i> Song
                </div>
                <div class="music-item" data-src="" data-name="No Music">
                    <i class="fas fa-volume-mute"></i> No Music
                </div>
            </div>
        </div>
    </div>


    <div class="glass-card">

        <!-- Header -->
        <div style="background: #1e293b; color: white; padding: 24px; border-bottom: 4px solid #fbbf24;">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 8px;">
                <div>
                    <h1
                        style="font-size: 24px; font-weight: bold; background: linear-gradient(to right, #fde68a, #fbbf24); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 4px;">
                        2026 武术训练报名
                    </h1>
                    <p style="color: #94a3b8; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Official
                        Registration Form</p>
                </div>
                <div style="color: #fbbf24; font-weight: bold; font-size: 20px;" id="step-counter">
                    01<span style="color: #475569; font-size: 14px;">/06</span>
                </div>
            </div>
            <div
                style="width: 100%; background: #475569; height: 6px; border-radius: 999px; overflow: hidden; margin-top: 8px;">
                <div id="progress-bar"
                    style="height: 100%; background: #fbbf24; transition: width 0.5s ease; width: 16.66%;"></div>
            </div>
        </div>

        <!-- Form Body -->
        <div style="padding: 32px; background: #f8fafc; max-height: 70vh; overflow-y: auto;" class="custom-scroll">
            <form id="regForm" onsubmit="return false;">
                <!-- CSRF Token Hidden Field -->
                <input type="hidden" name="csrf_token" id="csrf-token"
                    value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">


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
                                <input type="text" id="name-cn"
                                    class="w-full p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none"
                                    placeholder="张三">
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 uppercase">English Name 英文名 *</label>
                                <input type="text" id="name-en"
                                    class="w-full p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none"
                                    placeholder="Tan Ah Meng" required>
                            </div>
                        </div>

                        <!-- ID Type Selection -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase">Identification Type 身份证明类型
                                *</label>
                            <select id="id-type"
                                class="w-full p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none bg-white"
                                required onchange="toggleIDType()">
                                <option value="ic">IC Number 身份证号码</option>
                                <option value="passport">Passport Number 护照号码</option>
                            </select>
                        </div>

                        <!-- IC and Age Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 uppercase" id="id-label">IC Number 身份证号码
                                    *</label>
                                <input type="text" id="ic"
                                    class="w-full p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none"
                                    placeholder="000000-00-0000" maxlength="30" required>
                                <p class="text-xs text-slate-400" id="id-format-hint">Format: 000000-00-0000</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 uppercase">Age 年龄 (2026) *</label>
                                <input type="number" id="age"
                                    class="w-full p-3 rounded-xl border border-slate-300 outline-none"
                                    placeholder="Auto-calculated" required>
                                <p class="text-xs text-slate-400" id="age-hint">
                                    <i class="fas fa-info-circle mr-1"></i>Calculated from IC
                                </p>
                            </div>
                        </div>

                        <!-- School Row -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase">School 学校 *</label>
                            <select id="school"
                                class="w-full p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none bg-white"
                                required>
                                <option value="">Select School...</option>
                                <option value="SJK(C) PUAY CHAI 2">SJK(C) PUAY CHAI 2 (培才二校)</option>
                                <option value="SJK(C) Chee Wen">SJK(C) Chee Wen</option>
                                <option value="SJK(C) Subang">SJK(C) Subang</option>
                                <option value="SJK(C) Sin Ming">SJK(C) Sin Ming</option>
                                <option value="Others">Others (其他)</option>
                            </select>
                            <input type="text" id="school-other"
                                class="hidden w-full mt-2 p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none"
                                placeholder="Please specify school name">
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
                                <input type="tel" id="phone"
                                    class="w-full pl-10 p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none"
                                    placeholder="012-345 6789" maxlength="13" required>
                            </div>
                            <p class="text-xs text-slate-400">Format: 012-345 6789 or 011-2345 6789</p>
                        </div>

                        <!-- REMARK FOR ADDITIONAL CHILDREN -->
                        <div class="bg-blue-50 border-l-4 border-blue-500 p-3 mb-2 rounded-r-lg">
                            <p class="text-s text-blue-800 leading-relaxed">
                                <i class="fas fa-info-circle mr-1"></i>
                                <strong>Registering another child?</strong> Use the <strong>same parent email</strong>
                                to link all your children under one account.
                            </p>
                            <p class="text-s text-blue-700 leading-relaxed mt-1">
                                <strong>注册另一个孩子？</strong>使用<strong>相同的家长电邮</strong>将所有孩子连接到一个家长账户。
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase">Parent's Email 家长邮箱 *</label>
                            <div class="relative">
                                <i class="fa-solid fa-envelope absolute left-4 top-4 text-slate-400"></i>
                                <input type="email" id="email"
                                    class="w-full pl-10 p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none"
                                    placeholder="parent@example.com" required>
                            </div>
                        </div>

                        <!-- PASSWORD SELECTION SECTION -->
                        <div class="bg-purple-50 border-l-4 border-purple-500 p-4 mb-2 rounded-r-lg mt-5">
                            <p class="text-sm text-purple-800 leading-relaxed font-semibold mb-2">
                                <i class="fas fa-lock mr-1"></i> Account Password Setup 家长账户密码设置
                            </p>
                            <p class="text-xs text-purple-700 leading-relaxed">
                                Choose how you want to set up your login password for the account.
                                <br>选择您想要如何设置家长门户的登录密码。
                            </p>
                        </div>

                        <!-- EXISTING PARENT INFO (Hidden by default) -->
                        <div id="existing-parent-info"
                            class="bg-green-50 border-l-4 border-green-500 p-4 mb-2 rounded-r-lg mt-5 hidden">
                            <p class="text-sm text-green-800 leading-relaxed font-semibold mb-2">
                                <i class="fas fa-check-circle mr-1"></i> Existing Account Detected 检测到现有家长账户
                            </p>
                            <div id="existing-parent-details" class="text-xs text-green-700 leading-relaxed">
                                <!-- Will be populated by JavaScript -->
                            </div>
                            <p class="text-xs text-green-700 leading-relaxed mt-2">
                                <i class="fas fa-info-circle mr-1"></i> You'll use your existing password to login. This
                                child will be added to your account.
                                <br>您将使用现有密码登录。此孩子将添加到您的账户中。
                            </p>
                        </div>

                        <!-- NEW PARENT INFO (Hidden by default) -->
                        <div id="new-parent-info"
                            class="bg-purple-50 border-l-4 border-purple-500 p-4 mb-2 rounded-r-lg mt-5 hidden">
                            <p class="text-sm text-purple-800 leading-relaxed font-semibold mb-2">
                                <i class="fas fa-user-plus mr-1"></i> New Account 新家长账户
                            </p>
                            <p class="text-xs text-purple-700 leading-relaxed">
                                This email is not registered. We'll create a new account for you.
                                <br>此邮箱未注册。我们将为您创建新的家长账户。
                            </p>
                        </div>

                        <!-- PASSWORD SELECTOR (Only shown for new parents) -->
                        <div id="password-selector-container" class="hidden">
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 uppercase">Password Option 密码选项 *</label>
                                <select id="password-type"
                                    class="w-full p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none bg-white"
                                    required>
                                    <option value="">Select password option...</option>
                                    <option value="ic_last4">Use Parent IC Last 4 Digits (Default) 使用家长身份证最后4位</option>
                                    <option value="custom">Set Custom Password 设置自定义密码</option>
                                </select>
                            </div>

                            <!-- Custom Password Input (Hidden by default) -->
                            <div id="custom-password-container" class="space-y-2 hidden">
                                <label class="text-xs font-bold text-slate-500 uppercase">Custom Password 自定义密码
                                    *</label>
                                <div class="relative">
                                    <i class="fa-solid fa-key absolute left-4 top-4 text-slate-400"></i>
                                    <input type="password" id="custom-password"
                                        class="w-full pl-10 p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none"
                                        placeholder="Enter your password" minlength="6">
                                </div>
                                <p class="text-xs text-slate-400">Minimum 6 characters 至少6个字符</p>
                            </div>

                            <div id="custom-password-confirm-container" class="space-y-2 hidden">
                                <label class="text-xs font-bold text-slate-500 uppercase">Confirm Password 确认密码
                                    *</label>
                                <div class="relative">
                                    <i class="fa-solid fa-key absolute left-4 top-4 text-slate-400"></i>
                                    <input type="password" id="custom-password-confirm"
                                        class="w-full pl-10 p-3 rounded-xl border border-slate-300 focus:border-amber-500 outline-none"
                                        placeholder="Confirm your password">
                                </div>
                            </div>
                        </div>



                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase">Student Status 身份 *</label>
                            <div class="grid grid-cols-3 gap-3">
                                <label class="cursor-pointer">
                                    <input type="radio" name="status" value="Student 学生" class="status-radio" checked>
                                    <div
                                        class="status-option p-3 text-center rounded-xl border border-slate-200 bg-white">
                                        Student<br>学生
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="status" value="State Team 州队" class="status-radio">
                                    <div
                                        class="status-option p-3 text-center rounded-xl border border-slate-200 bg-white">
                                        State Team<br>州队
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="status" value="Backup Team 后备队" class="status-radio">
                                    <div
                                        class="status-option p-3 text-center rounded-xl border border-slate-200 bg-white">
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

                    <p class="text-sm text-slate-600 mb-4">Select events for each level (You can select multiple events
                        across different levels)</p>

                    <div class="space-y-4">
                        <!-- Basic Level -->
                        <div class="border-l-4 border-slate-700 bg-slate-50 rounded-r-xl p-4 basic-routines">
                            <h3 class="font-bold text-slate-800 mb-3">基础 Basic</h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="基础-长拳"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">长拳</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="基础-南拳"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">南拳</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="基础-太极拳"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">太极拳</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="基础-剑"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">剑</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="基础-枪"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">枪</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="基础-刀"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">刀</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="基础-棍"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">棍</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="基础-南刀"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">南刀</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="基础-南棍"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">南棍</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="基础-太极剑"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">太极剑</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="基础-太极扇"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">太极扇</span>
                                </label>
                            </div>
                        </div>

                        <!-- Junior Level -->
                        <div class="border-l-4 border-blue-600 bg-blue-50 rounded-r-xl p-4">
                            <h3 class="font-bold text-blue-800 mb-3">初级 Junior</h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="初级-长拳"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">长拳</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="初级-南拳"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">南拳</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="初级-太极拳"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">太极拳</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="初级-剑"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">剑</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="初级-枪"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">枪</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="初级-刀"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">刀</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="初级-棍"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">棍</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="初级-南刀"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">南刀</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="初级-南棍"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">南棍</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="初级-太极剑"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">太极剑</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="初级-太极扇"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">太极扇</span>
                                </label>
                            </div>
                        </div>

                        <!-- Group B -->
                        <div class="border-l-4 border-green-600 bg-green-50 rounded-r-xl p-4">
                            <h3 class="font-bold text-green-800 mb-3">B组 Group B</h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="B组-长拳"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">长拳</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="B组-南拳"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">南拳</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="B组-太极拳"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">太极拳</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="B组-剑"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">剑</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="B组-枪"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">枪</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="B组-刀"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">刀</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="B组-棍"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">棍</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="B组-南刀"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">南刀</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="B组-南棍"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">南棍</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="B组-太极剑"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">太极剑</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="B组-太极扇"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">太极扇</span>
                                </label>
                            </div>
                        </div>

                        <!-- Group A -->
                        <div class="border-l-4 border-purple-600 bg-purple-50 rounded-r-xl p-4">
                            <h3 class="font-bold text-purple-800 mb-3">A组 Group A</h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="A组-长拳"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">长拳</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="A组-南拳"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">南拳</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="A组-太极拳"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">太极拳</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="A组-剑"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">剑</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="A组-枪"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">枪</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="A组-刀"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">刀</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="A组-棍"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">棍</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="A组-南刀"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">南刀</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="A组-南棍"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">南棍</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="A组-太极剑"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">太极剑</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="A组-太极扇"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">太极扇</span>
                                </label>
                            </div>
                        </div>

                        <!-- Optional Level -->
                        <div class="border-l-4 border-amber-600 bg-amber-50 rounded-r-xl p-4">
                            <h3 class="font-bold text-amber-800 mb-3">自选 Optional</h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="自选-长拳"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">长拳</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="自选-南拳"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">南拳</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="自选-太极拳"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">太极拳</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="自选-剑"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">剑</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="自选-枪"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">枪</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="自选-刀"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">刀</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="自选-棍"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">棍</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="自选-南刀"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">南刀</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="自选-南棍"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">南棍</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="自选-太极剑"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">太极剑</span>
                                </label>
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="自选-太极扇"
                                        class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">太极扇</span>
                                </label>
                            </div>
                        </div>
                        <div class="border-l-4 border-amber-600 bg-amber-50 rounded-r-xl p-4">
                            <h3 class="font-bold text-amber-800 mb-3">传统 Traditional</h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                                <label class="cursor-pointer flex items-center gap-2">
                                    <input type="checkbox" name="evt" value="传统" class="w-4 h-4 text-amber-500 rounded">
                                    <span class="text-sm">传统</span>
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
                        <p class="font-bold mb-1 text-m" style="font-size: 16px;"><i class="fas fa-info-circle"></i> 注明
                            (Remark)：州队运动员需至少选择 两堂课程。</p>
                        <p style="font-size: 15px;">• 选择 <strong>一堂课程</strong>：<strong>RM30</strong><br></p>
                        <p style="font-size: 15px;">• 选择 <strong>二堂课程</strong>：<strong>RM27</strong></p>
                        <p style="font-size: 15px;">• 选择 <strong>三堂课程</strong>：<strong>RM24</strong></p>
                        <p style="font-size: 15px;">• 选择 <strong>四堂课程</strong>：<strong>RM21</strong></p>
                        <p class="font-bold mt-1" style="font-size: 16px;"><br>State team athletes must choose at least
                            two courses.</p>
                        <p style="font-size: 15px;">• Select <strong>one course</strong>：<strong>RM30</strong><br></p>
                        <p style="font-size: 15px;">• Select <strong>two courses</strong>：<strong>RM27</strong></p>
                        <p style="font-size: 15px;">• Select <strong>three courses</strong>：<strong>RM24</strong></p>
                        <p style="font-size: 15px;">• Select <strong>four courses</strong>：<strong>RM21</strong></p>
                    </div>

                    <div class="space-y-4">
                        <!-- SCHOOL 1: Wushu Sport Academy -->
                        <div class="school-box" onclick="toggleSchoolBox(this)">
                            <div class="school-header">
                                <div class="school-info">
                                    <img src="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/Wushu+Sport+Academy+Circle+Yellow.png"
                                        alt="WSA Logo" class="school-logo">
                                    <div class="school-text">
                                        <h3>
                                            <i class="fas fa-map-marker-alt" style="color: #fbbf24;"></i>
                                            Wushu Sport Academy 武术体育学院
                                        </h3>
                                        <p><i class="fas fa-location-dot" style="color: #94a3b8;"></i> No. 2, Jalan BP
                                            5/6, Bandar Bukit Puchong, 47120 Puchong, Selangor</p>
                                    </div>
                                </div>
                                <div class="school-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div class="school-schedules">
                                <div class="school-schedules-inner">
                                    <div class="space-y-3">
                                        <label
                                            class="custom-checkbox border-2 border-slate-200 rounded-xl hover:border-amber-400 hover:bg-amber-50/30 transition-all"
                                            data-schedule="wsa-wed-8pm">
                                            <input type="checkbox" name="sch" value="Wushu Sport Academy: Wed 8pm-10pm">
                                            <div class="custom-checkbox-label">
                                                <div class="text-sm font-bold text-slate-800 mb-1">
                                                    <i class="far fa-calendar mr-2 text-amber-500"></i>Wednesday 星期三 ·
                                                    8:00 PM - 10:00 PM
                                                </div>
                                                <div class="text-xs text-slate-600">
                                                    <span
                                                        class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-md font-semibold">(C
                                                        和 太极套路)</span>
                                                </div>
                                            </div>
                                        </label>
                                        <label
                                            class="custom-checkbox border-2 border-slate-200 rounded-xl hover:border-amber-400 hover:bg-amber-50/30 transition-all"
                                            data-schedule="wsa-sun-10am">
                                            <input type="checkbox" name="sch"
                                                value="Wushu Sport Academy: Sun 10am-12pm">
                                            <div class="custom-checkbox-label">
                                                <div class="text-sm font-bold text-slate-800 mb-1">
                                                    <i class="far fa-calendar mr-2 text-amber-500"></i>Sunday 星期日 ·
                                                    10:00 AM - 12:00 PM
                                                </div>
                                                <div class="text-xs text-slate-600">
                                                    <span
                                                        class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-md font-semibold">只限于州队/后备队
                                                        Only for State/Backup Team</span>
                                                </div>
                                                <div class="text-xs text-slate-600">
                                                    <span
                                                        class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-md font-semibold">(A/B/C/D
                                                        传统和太极套路)</span>
                                                </div>
                                            </div>
                                        </label>

                                        <label
                                            class="custom-checkbox border-2 border-slate-200 rounded-xl hover:border-amber-400 hover:bg-amber-50/30 transition-all"
                                            data-schedule="wsa-sun-1pm">
                                            <input type="checkbox" name="sch" value="Wushu Sport Academy: Sun 1pm-3pm">
                                            <div class="custom-checkbox-label">
                                                <div class="text-sm font-bold text-slate-800">
                                                    <i class="far fa-calendar mr-2 text-amber-500"></i>Sunday 星期日 · 1:00
                                                    PM - 3:00 PM
                                                </div>
                                                <div class="text-xs text-slate-600">
                                                    <span
                                                        class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-md font-semibold">(C/D
                                                        和太极套路)</span>
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
                                    <img src="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/PC2+Logo.png"
                                        alt="PC2 Logo" class="school-logo">
                                    <div class="school-text">
                                        <h3>
                                            <i class="fas fa-map-marker-alt" style="color: #fbbf24;"></i>
                                            SJK(C) Puay Chai 2 培才二校
                                        </h3>
                                        <p><i class="fas fa-location-dot" style="color: #94a3b8;"></i> Jln BU 3/1,
                                            Bandar Utama, 47800 Petaling Jaya, Selangor</p>
                                    </div>
                                </div>
                                <div class="school-toggle">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div class="school-schedules">
                                <div class="school-schedules-inner">
                                    <div class="space-y-3">
                                        <label
                                            class="custom-checkbox border-2 border-slate-200 rounded-xl hover:border-amber-400 hover:bg-amber-50/30 transition-all"
                                            data-schedule="pc2-tue-8pm">
                                            <input type="checkbox" name="sch" value="SJK(C) Puay Chai 2: Tue 8pm-10pm">
                                            <div class="custom-checkbox-label">
                                                <div class="text-sm font-bold text-slate-800 mb-1">
                                                    <i class="far fa-calendar mr-2 text-amber-500"></i>Tuesday 星期二 ·
                                                    8:00 PM - 10:00 PM
                                                </div>
                                                <div class="text-xs text-slate-600">
                                                    <span
                                                        class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-md font-semibold">只限于州队/后备队
                                                        Only for State/Backup Team</span>
                                                </div>
                                                <div class="text-xs text-slate-600">
                                                    <span
                                                        class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-md font-semibold">(A/B/C
                                                        和 传统套路)</span>
                                                </div>
                                                <div
                                                    class="text-[10px] text-red-500 font-bold hidden disabled-msg mt-1">
                                                    <i class="fas fa-ban mr-1"></i>Not available for Normal Students
                                                    普通学生不允许参加
                                                </div>
                                            </div>
                                        </label>

                                        <label
                                            class="custom-checkbox border-2 border-slate-200 rounded-xl hover:border-amber-400 hover:bg-amber-50/30 transition-all"
                                            data-schedule="pc2-wed-8pm">
                                            <input type="checkbox" name="sch" value="SJK(C) Puay Chai 2: Wed 8pm-10pm">
                                            <div class="custom-checkbox-label">
                                                <div class="text-sm font-bold text-slate-800 mb-1">
                                                    <i class="far fa-calendar mr-2 text-amber-500"></i>Wednesday 星期三 ·
                                                    8:00 PM - 10:00 PM
                                                </div>
                                                <div class="text-xs text-slate-600">
                                                    <span
                                                        class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-md font-semibold">全部组别
                                                        All Groups (A/B/C/D 套路) 没有太极 和 没有传统</span>
                                                </div>
                                            </div>
                                        </label>
                                        <label
                                            class="custom-checkbox border-2 border-slate-200 rounded-xl hover:border-amber-400 hover:bg-amber-50/30 transition-all"
                                            data-schedule="pc2-fri-8pm">
                                            <input type="checkbox" name="sch" value="SJK(C) Puay Chai 2: Fri 8pm-10pm">
                                            <div class="custom-checkbox-label">
                                                <div class="text-sm font-bold text-slate-800 mb-1">
                                                    <i class="far fa-calendar mr-2 text-amber-500"></i>Friday 星期五 · 8:00
                                                    PM - 10:00 PM
                                                </div>
                                                <div class="text-xs text-slate-600">
                                                    <span
                                                        class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-md font-semibold">太极套路而已</span>
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
                            <p class="text-xs text-blue-700 leading-relaxed mt-1">Fees must be paid before the 10th of
                                every month, and the receipt must be sent to the coach and admin.</p>
                        </div>
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                            <h4 class="font-bold text-red-700 text-sm mb-1">运动员守则 · Code of Conduct</h4>
                            <p class="text-xs text-red-800 leading-relaxed">严守纪律，必须守时，不允许在训练期间嬉戏；违者可能被取消资格。</p>
                            <p class="text-xs text-red-700 leading-relaxed mt-1">Athletes must be disciplined and
                                punctual and are not allowed to play during training; violations may result in
                                disqualification.</p>
                        </div>
                    </div>

                    <div
                        class="bg-white border border-slate-200 rounded-xl p-4 md:p-5 h-64 md:h-56 overflow-y-auto custom-scroll mb-6 text-xs leading-relaxed">
                        <div class="flex items-center justify-center mb-4">
                            <h4 class="font-bold text-slate-800 text-sm">📋 TERMS & CONDITIONS 条款与条件</h4>
                        </div>

                        <ol class="space-y-4">
                            <li class="flex items-start gap-2 md:gap-3">
                                <div
                                    class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">
                                    1</div>
                                <div class="space-y-1">
                                    <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">
                                        本人（学员/家长/监护人）确认上述资料属实。</p>
                                    <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">I, the
                                        student/parent/guardian, confirm that all information provided above is true and
                                        correct.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-2 md:gap-3">
                                <div
                                    class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">
                                    2</div>
                                <div class="space-y-1">
                                    <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">
                                        本人明白武术是一项剧烈运动，并愿意自行承担训练期间可能发生的意外风险。</p>
                                    <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">I understand
                                        that Wushu is a high‑intensity sport and agree to bear any risk of injury during
                                        training.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-2 md:gap-3">
                                <div
                                    class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">
                                    3</div>
                                <div class="space-y-1">
                                    <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">
                                        学院有权在必要时调整训练时间或地点，并将提前通知。</p>
                                    <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">The Academy
                                        reserves the right to adjust training times or venues when necessary and will
                                        notify in advance.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-2 md:gap-3">
                                <div
                                    class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">
                                    4</div>
                                <div class="space-y-1">
                                    <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">
                                        学费一经缴付，概不退还（Non‑refundable）。</p>
                                    <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">Fees paid are
                                        strictly non‑refundable under all circumstances.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-2 md:gap-3">
                                <div
                                    class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">
                                    5</div>
                                <div class="space-y-1">
                                    <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">
                                        本人同意遵守学院及教练的所有指示与安排。</p>
                                    <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">I agree to
                                        follow all instructions, rules, and arrangements set by the Academy and coaches.
                                    </p>
                                </div>
                            </li>
                            <li class="flex items-start gap-2 md:gap-3">
                                <div
                                    class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">
                                    6</div>
                                <div class="space-y-1">
                                    <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">
                                        只限于本院通知取消课程，将会另行安排补课，家长不允许自行取消课程。</p>
                                    <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">Replacement
                                        classes are only provided when the Academy cancels a session; parents may not
                                        cancel classes on their own.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-2 md:gap-3">
                                <div
                                    class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">
                                    7</div>
                                <div class="space-y-1">
                                    <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">
                                        如学员因病或其他原因无法出席训练，必须向行政与教练申请请假；未经许可的缺席将被记录。</p>
                                    <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">If the student
                                        cannot attend due to sickness or other reasons, leave must be applied for with
                                        admin and coach; unapproved absences will be recorded.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-2 md:gap-3">
                                <div
                                    class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">
                                    8</div>
                                <div class="space-y-1">
                                    <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">
                                        州队及后备队必须出席所有训练，保持良好态度，接受严格训练与训导。</p>
                                    <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">State‑team and
                                        reserve athletes must attend all training, maintain good attitude, and accept
                                        strict training and discipline.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-2 md:gap-3">
                                <div
                                    class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">
                                    9</div>
                                <div class="space-y-1">
                                    <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">
                                        如因脚受伤、扭伤或生病，请勿勉强出席训练，后果自负。</p>
                                    <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">Students with
                                        injuries or illness should not attend training; any consequences are at their
                                        own risk.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-2 md:gap-3">
                                <div
                                    class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">
                                    10</div>
                                <div class="space-y-1">
                                    <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">
                                        本院不负责学员及家长的任何贵重财物。</p>
                                    <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">The Academy is
                                        not responsible for any valuables belonging to students or parents.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-2 md:gap-3">
                                <div
                                    class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">
                                    11</div>
                                <div class="space-y-1">
                                    <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">
                                        不允许打架、吵架、态度恶劣或不配合训练，否则将被取消州队及学员资格。</p>
                                    <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">Fighting,
                                        quarrelling, poor attitude, or refusing to cooperate with training may result in
                                        removal from the state team and the Academy.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-2 md:gap-3">
                                <div
                                    class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">
                                    12</div>
                                <div class="space-y-1">
                                    <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">
                                        训练期间不允许吃食物，只能在休息时间喝水。</p>
                                    <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">Eating is not
                                        allowed during training; only drinking water during breaks is permitted.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-2 md:gap-3">
                                <div
                                    class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">
                                    13</div>
                                <div class="space-y-1">
                                    <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">
                                        家长不允许干涉教练所安排的专业训练计划及纪律管理。</p>
                                    <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">Parents are not
                                        allowed to interfere with professional training plans or discipline set by the
                                        coaches.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-2 md:gap-3">
                                <div
                                    class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">
                                    14</div>
                                <div class="space-y-1">
                                    <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">
                                        家长必须准时载送孩子往返训练地点，并自行负责交通安全。</p>
                                    <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">Parents must
                                        send and pick up their children on time and are fully responsible for transport
                                        safety.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-2 md:gap-3">
                                <div
                                    class="mt-0.5 h-5 w-5 md:h-6 md:w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] md:text-[11px] font-bold flex-shrink-0">
                                    15</div>
                                <div class="space-y-1">
                                    <p class="text-[11px] md:text-[12px] text-slate-800 leading-relaxed">
                                        训练过程中，学员可能被录影或拍照作为宣传用途，如家长不允许，须以书面通知本院。</p>
                                    <p class="text-[11px] md:text-[12px] text-slate-600 leading-relaxed">Training
                                        sessions may be recorded or photographed for publicity; parents who do not
                                        consent must inform the Academy in writing.</p>
                                </div>
                            </li>
                        </ol>
                    </div>

                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 mt-6">
                        <h4 class="font-bold text-slate-700 mb-4 text-sm uppercase">Legal Declaration</h4>

                        <!-- Parent ID Type Selection -->
                        <div class="mb-4">
                            <label class="text-xs font-bold text-slate-500 uppercase">Parent/Guardian ID Type 家长身份证明类型
                                *</label>
                            <select id="parent-id-type"
                                class="w-full p-2 border border-slate-300 rounded-lg text-sm bg-white" required
                                onchange="toggleParentIDType()">
                                <option value="ic">IC Number 身份证号码</option>
                                <option value="passport">Passport Number 护照号码</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="text-xs font-bold text-slate-500 uppercase">Parent/Guardian Name 家长姓名
                                    *</label>
                                <input type="text" id="parent-name"
                                    class="w-full p-2 border border-slate-300 rounded-lg text-sm bg-white" required>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-500 uppercase"
                                    id="parent-id-label">Parent/Guardian IC No. 家长身份证 *</label>
                                <input type="text" id="parent-ic"
                                    class="w-full p-2 border border-slate-300 rounded-lg text-sm bg-white"
                                    placeholder="000000-00-0000" maxlength="30" required>
                                <p class="text-xs text-slate-400 mt-1" id="parent-id-format-hint">Format: 000000-00-0000
                                </p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-xs font-bold text-slate-500 uppercase">Effective Date 生效日期</label>
                                <input type="text" id="today-date"
                                    class="w-full p-2 border border-slate-200 bg-slate-100 text-slate-500 rounded-lg text-sm"
                                    readonly>
                            </div>
                        </div>

                        <label class="text-xs font-bold text-slate-500 mb-2 block">Parent's Signature (Sign Below) 家长签名
                            *</label>
                        <div id="sig-wrapper" class="sig-box">
                            <div id="sig-placeholder">SIGN HERE</div>
                            <div class="absolute top-2 right-2 z-10">
                                <button type="button" onclick="clearSig()"
                                    class="bg-red-100 text-red-600 px-3 py-1 rounded text-xs font-bold hover:bg-red-200 cursor-pointer border-none">
                                    <i class="fa-solid fa-eraser"></i> Clear
                                </button>
                            </div>
                        </div>
                        <!-- MANDATORY AGREEMENT CHECKBOX -->
                        <div class="bg-amber-50 border-2 border-amber-400 rounded-xl p-4 mb-6 mt-8">
                            <label class="flex items-start gap-3 cursor-pointer group">
                                <input type="checkbox" id="terms-agreement"
                                    class="w-5 h-5 mt-1 text-amber-600 border-2 border-amber-400 rounded focus:ring-2 focus:ring-amber-500 cursor-pointer"
                                    required>
                                <div class="flex-1">
                                    <p
                                        class="font-bold text-slate-800 text-sm mb-1 group-hover:text-amber-700 transition-colors">
                                        <i class="fas fa-check-circle text-amber-600"></i> I agree to the Terms and
                                        Conditions *
                                    </p>
                                    <p class="text-xs text-slate-700 leading-relaxed">
                                        本人已阅读并同意上述所有条款与条件，包括学费政策、运动员守则及免责声明。
                                    </p>
                                    <p class="text-xs text-slate-600 leading-relaxed mt-1">
                                        I have read and agree to all the above terms and conditions, including the fee
                                        policy, code of conduct, and disclaimer.
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

                    <div id="feeBreakdownContainer" class="mt-4">
                        <!-- Fee breakdown will appear here -->
                    </div>

                    <!-- Fee Calculation -->
                    <div
                        class="bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-400 rounded-xl p-6 mb-6">
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
                            <p>• <strong>一堂课程</strong>：<strong>RM30</strong></p>
                            <p>• <strong>二堂课程</strong>：<strong>RM27</strong></p>
                            <p>• <strong>三堂课程</strong>：<strong>RM24</strong></p>
                            <p>• <strong>四堂课程</strong>：<strong>RM21</strong></p>
                            <p><br></p>
                            <p class="font-semibold mb-1"><i class="fas fa-info-circle"></i> Fee Structure:</p>
                            <p>• <strong>One Course</strong>：<strong>RM30</strong></p>
                            <p>• <strong>Two Courses</strong>：<strong>RM27</strong></p>
                            <p>• <strong>Three Courses</strong>：<strong>RM24</strong></p>
                            <p>• <strong>Four Courses</strong>：<strong>RM21</strong></p>
                        </div>
                    </div>

                    <!-- Payment Method Selection -->
                    <div class="bg-white border-2 border-slate-200 rounded-xl p-5 mb-6">
                        <h3 class="font-bold text-slate-800 text-base mb-4 flex items-center gap-2"
                            style="font-size: 17px;">
                            <i class="fas fa-wallet text-green-600"></i> 选择付款方式 *
                        </h3>

                        <div class="space-y-2 mb-4">
                            <label class="text-sm font-semibold text-slate-700 mb-2 block">
                                Select Payment Method *
                            </label>
                            <select id="payment-method"
                                class="w-full p-3 border border-slate-300 rounded-lg text-sm focus:border-amber-500 focus:outline-none"
                                required onchange="togglePaymentMethod()">
                                <option value="">-- Select Payment Method --</option>
                                <option value="cash">Cash 现金</option>
                                <option value="bank_transfer">Bank Transfer 银行转账</option>
                            </select>
                        </div>

                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 text-lg text-yellow-800">
                            <p class="font-semibold mb-1 text-lg"><i class="fas fa-info-circle"></i> 付款说明:</p>
                            <p>• <strong>现金:</strong> 将学费以现金付款方式交于林金教练</p>
                            <p>• <strong>银行转账:</strong> 转账至银行账户并上传收据<br></p>
                            <p><br></p>
                            <p class="font-semibold mb-1"><i class="fas fa-info-circle"></i> Payment Instructions:</p>
                            <p>• <strong>Cash:</strong> Pay directly to coach</p>
                            <p>• <strong>Bank Transfer:</strong> Transfer to bank account and upload receipt</p>
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
                                    <div
                                        class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-bank text-blue-600 text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-xs text-slate-500 mb-1">Bank Name 银行名称</p>
                                        <p class="font-bold text-slate-800">Maybank</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 bg-white p-3 rounded-lg">
                                    <div
                                        class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-user text-green-600 text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-xs text-slate-500 mb-1">Account Name 户口名称</p>
                                        <p class="font-bold text-slate-800">Wushu Sport Academy</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 bg-white p-3 rounded-lg">
                                    <div
                                        class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-hashtag text-amber-600 text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-xs text-slate-500 mb-1">Account Number 户口号码</p>
                                        <p class="font-bold text-slate-800 text-lg">5050 1981 6740</p>
                                        <button onclick="copyAccountNumber()"
                                            class="text-xs text-blue-600 hover:text-blue-800 mt-1 flex items-center gap-1">
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
                                <input type="date" id="payment-date"
                                    class="w-full p-3 border border-slate-300 rounded-lg text-sm">
                            </div>

                            <!-- Upload Area -->
                            <div class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center hover:border-amber-400 transition-all cursor-pointer"
                                id="upload-area" onclick="document.getElementById('receipt-upload').click()">

                                <!-- Hidden file input -->
                                <input type="file" id="receipt-upload" accept="image/*,.pdf" class="hidden"
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
                                    <img id="preview-image" src=""
                                        class="max-w-full max-h-64 mx-auto mb-3 rounded-lg border border-slate-200">
                                    <p id="preview-filename" class="text-sm font-semibold text-slate-800 mb-2"></p>
                                    <button type="button" onclick="event.stopPropagation(); removeReceipt()"
                                        class="text-xs text-red-600 hover:text-red-800 font-semibold">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </div>
                            </div>

                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mt-4 text-xs text-yellow-800">
                                <p class="font-semibold mb-1">
                                    <i class="fas fa-exclamation-triangle"></i> Important Note
                                </p>
                                <p class="mt-1">Please ensure the receipt is clear and shows payment amount, date, and
                                    bank details.</p>
                            </div>
                        </div>

                    </div>

                    <!-- Cash Payment Note (Hidden by default) -->
                    <div id="cash-payment-note" style="display: none;">
                        <div class="bg-blue-50 border-2 border-blue-400 rounded-xl p-6 text-center">
                            <div
                                class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-money-bill-wave text-blue-600 text-2xl"></i>
                            </div>
                            <h3 class="font-bold text-blue-800 text-lg mb-2">现金付款<br>Cash Payment</h3>
                            <p class="text-sm text-blue-700 mb-3">
                                Please pay <strong id="cash-amount" class="text-blue-900">RM 0</strong> in cash to Coach
                                Lim Kim. He will record the payment and sign ✍🏼 on the training record card. As the
                                payment is made in cash, no official receipt will be issued.
                            </p>
                            <p class="text-sm text-blue-700">
                                請將學費以<strong class="text-blue-1200">現金</strong>付款方式交于林金教練，他将会记录与簽名✍🏼在學費卡上；由於現金付款
                                將不會有正式收据.
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
                            <div
                                style="width: 96px; height: 96px; background: #dcfce7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                                <i class="fas fa-check-circle" style="color: #16a34a; font-size: 48px;"></i>
                            </div>
                            <h2 style="font-size: 28px; font-weight: bold; color: #1e293b; margin-bottom: 8px;">
                                Registration Successful!</h2>
                            <p style="color: #64748b; font-size: 18px; margin-bottom: 4px;">报名成功！</p>
                            <p style="color: #94a3b8; font-size: 14px;" id="reg-number-display"></p>
                        </div>

                        <div
                            style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 24px; border-radius: 0 12px 12px 0; margin-bottom: 32px; max-width: 600px; margin-left: auto; margin-right: auto; text-align: left;">
                            <h3
                                style="font-weight: bold; color: #1e40af; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
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

                        <div
                            style="display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; margin-bottom: 32px;">
                            <button type="button" onclick="downloadPDF()"
                                style="background: #16a34a; color: white; padding: 16px 32px; border-radius: 12px; font-weight: bold; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3); border: none; cursor: pointer; display: flex; align-items: center; gap: 12px; transition: transform 0.2s;">
                                <i class="fas fa-download" style="font-size: 20px;"></i>
                                <div style="text-align: left;">
                                    <div>Download Signed Agreement</div>
                                    <div style="font-size: 12px; font-weight: normal;">下载已签协议 PDF</div>
                                </div>
                            </button>
                            <button type="button" onclick="submitAnother()"
                                style="background: linear-gradient(to right, #7c3aed, #6d28d9); color: white; padding: 16px 32px; border-radius: 12px; font-weight: bold; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3); border: none; cursor: pointer; display: flex; align-items: center; gap: 12px; transition: transform 0.2s;">
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
    " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 10px 30px rgba(30, 41, 59, 0.6);'"
                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 6px 20px rgba(30, 41, 59, 0.5)';">
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
                                    <div style="font-size: 13px; font-weight: 700; line-height: 1.3;">Login</div>
                                    <div style="font-size: 10px; color: #fbbf24; font-weight: 500;">登录 →</div>
                                </div>
                            </a>
                        </div>

                    </div>
                </div>


            </form>
        </div>

        <!-- Footer buttons -->
        <div id="footer-buttons"
            style="padding: 24px; background: white; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
            <!-- Back/Login Button -->
            <button id="btn-prev" onclick="handleBackButton()" style="
        padding: 10px 16px; 
        border-radius: 12px; 
        font-weight: 600; 
        background: transparent; 
        color: #64748b; 
        border: none; 
        cursor: pointer; 
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: none;
    ">
                <div id="back-btn-icon" style="
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            border-radius: 8px;
            display: none;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        ">
                    <i class="fas fa-arrow-left" style="font-size: 12px; color: #1e293b;"></i>
                </div>
                <div style="text-align: left;">
                    <div id="back-btn-text" style="font-size: 14px; font-weight: 700; line-height: 1.2;">← Back</div>
                    <div id="back-btn-subtext" style="font-size: 9px; color: #fbbf24; font-weight: 500; display: none;">
                    </div>
                </div>
            </button>

            <!-- Next Button -->
            <button id="btn-next" onclick="changeStep(1)"
                style="background: #1e293b; color: white; padding: 10px 32px; border-radius: 12px; font-weight: 600; box-shadow: 0 4px 12px rgba(30, 41, 59, 0.3); border: none; cursor: pointer; transition: all 0.2s;">
                Next Step <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>

        <!-- HIDDEN PDF TEMPLATE - PAGE 1 -->
        <div id="pdf-template-page1"
            style="width: 794px; padding: 40px; background: #ffffff; position: fixed; top: -10000px; left: -10000px; visibility: hidden; pointer-events: none; color: #111827; font-family: 'Noto Sans SC', sans-serif;">
            <img src="/cache/letterhead_cache.jpg" style="width: 100%; margin-bottom: 12px;" alt="Letterhead">
            <h1 style="text-align:center; font-size:24px; font-weight:800; margin-top:6px;">OFFICIAL WUSHU REGISTRATION
                2026</h1>
            <p style="text-align:center; font-size:13px; color:#6b7280; margin-bottom:24px;">Legal Binding Document ·
                This form confirms participation in Wushu Sports Academy programmes.</p>

            <div style="margin-bottom:22px;">
                <div
                    style="background:#e5e7eb; padding:7px 12px; font-weight:700; font-size:13px; text-transform:uppercase;">
                    STUDENT DETAILS / 学员资料</div>
                <div
                    style="border:1px solid #e5e7eb; border-top:none; padding:10px 12px; font-size:13px; line-height:1.5;">
                    <div style="margin-bottom:5px;"><span
                            style="font-weight:600; color:#6b7280; display:inline-block; width:140px;">Name 姓名:</span>
                        <span style="font-weight:500; color:#111827;" id="pdf-name"></span>
                    </div>
                    <div style="margin-bottom:5px;"><span
                            style="font-weight:600; color:#6b7280; display:inline-block; width:140px;">IC No 身份证:</span>
                        <span style="font-weight:500; color:#111827;" id="pdf-ic"></span>
                    </div>
                    <div style="margin-bottom:5px;"><span
                            style="font-weight:600; color:#6b7280; display:inline-block; width:140px;">Age 年龄:</span>
                        <span style="font-weight:500; color:#111827;" id="pdf-age"></span>
                    </div>
                    <div style="margin-bottom:5px;"><span
                            style="font-weight:600; color:#6b7280; display:inline-block; width:140px;">School 学校:</span>
                        <span style="font-weight:500; color:#111827;" id="pdf-school"></span>
                    </div>
                    <div style="margin-bottom:5px;"><span
                            style="font-weight:600; color:#6b7280; display:inline-block; width:140px;">Status 身份:</span>
                        <span style="font-weight:500; color:#111827;" id="pdf-status"></span>
                    </div>
                </div>
            </div>

            <div style="margin-bottom:22px;">
                <div
                    style="background:#e5e7eb; padding:7px 12px; font-weight:700; font-size:13px; text-transform:uppercase;">
                    CONTACT & EVENTS / 联系与项目</div>
                <div
                    style="border:1px solid #e5e7eb; border-top:none; padding:10px 12px; font-size:13px; line-height:1.5;">
                    <div style="margin-bottom:5px;"><span
                            style="font-weight:600; color:#6b7280; display:inline-block; width:140px;">Phone 电话:</span>
                        <span style="font-weight:500; color:#111827;" id="pdf-phone"></span>
                    </div>
                    <div style="margin-bottom:5px;"><span
                            style="font-weight:600; color:#6b7280; display:inline-block; width:140px;">Email 邮箱:</span>
                        <span style="font-weight:500; color:#111827;" id="pdf-email"></span>
                    </div>
                    <!--<div style="margin-bottom:5px;"><span style="font-weight:600; color:#6b7280; display:inline-block; width:140px;">Level 等级:</span> <span style="font-weight:500; color:#111827;" id="pdf-level"></span></div>-->
                    <div style="margin-bottom:5px;"><span
                            style="font-weight:600; color:#6b7280; display:inline-block; width:140px;">Events 项目:</span>
                        <span style="font-weight:500; color:#111827;" id="pdf-events"></span>
                    </div>
                    <div style="margin-bottom:5px;"><span
                            style="font-weight:600; color:#6b7280; display:inline-block; width:140px;">Schedule
                            时间:</span> <span style="font-weight:500; color:#111827;" id="pdf-schedule"></span></div>
                </div>
            </div>

            <div style="margin-bottom:22px;">
                <div
                    style="background:#e5e7eb; padding:7px 12px; font-weight:700; font-size:13px; text-transform:uppercase;">
                    DECLARATION & SIGNATURE / 声明与签名</div>
                <div
                    style="border:1px solid #e5e7eb; border-top:none; padding:10px 12px; font-size:13px; line-height:1.5;">
                    <p style="font-size:13px; margin-bottom:12px;">
                        I hereby confirm that all information provided is accurate. I have read and agreed to the
                        Terms & Conditions, Fee Policy, and Athlete Code of Conduct. I understand that Wushu is a
                        high-intensity sport and agree to bear the risks involved.
                    </p>
                    <div
                        style="border:1px solid #d1d5db; padding:8px; width:340px; height:130px; position:relative; margin-bottom:10px;">
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
        <div id="pdf-template-page2"
            style="width: 794px; padding: 40px; background: #ffffff; position: fixed; top: -10000px; left: -10000px; visibility: hidden; pointer-events: none; color: #111827; font-family: Arial, sans-serif;">
            <img src="/cache/letterhead_cache.jpg" style="width: 100%; margin-bottom: 12px;" alt="Letterhead">
            <h1
                style="text-align:center; font-size:24px; font-weight:800; margin-top:6px; font-family: 'Noto Sans SC', sans-serif;">
                TERMS & CONDITIONS</h1>
            <p
                style="text-align:center; font-size:13px; color:#6b7280; margin-bottom:16px; font-family: 'Noto Sans SC', sans-serif;">
                条款与条件 · Agreed and Signed by Parent/Guardian</p>

            <div style="font-size: 11px; line-height: 1.5; color: #111827; font-family: 'Noto Sans SC', sans-serif;">
                <p style="margin-bottom: 10px; font-weight: 600; color: #1e293b; font-size: 12px;">
                    The parent/guardian has read, understood, and agreed to the following terms:
                </p>

                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 30px; padding: 0 8px 8px 0;">
                            <div
                                style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">
                                1</div>
                        </td>
                        <td style="padding: 0 0 8px 0;">
                            <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">
                                本人（学员/家长/监护人）确认上述资料属实。</p>
                            <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">I, the
                                student/parent/guardian, confirm that all information provided above is true and
                                correct.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30px; padding: 0 8px 8px 0;">
                            <div
                                style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">
                                2</div>
                        </td>
                        <td style="padding: 0 0 8px 0;">
                            <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">
                                本人明白武术是一项剧烈运动，并愿意自行承担训练期间可能发生的意外风险。</p>
                            <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">I understand that
                                Wushu is a high‑intensity sport and agree to bear any risk of injury during training.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30px; padding: 0 8px 8px 0;">
                            <div
                                style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">
                                3</div>
                        </td>
                        <td style="padding: 0 0 8px 0;">
                            <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">
                                学院有权在必要时调整训练时间或地点，并将提前通知。</p>
                            <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">The Academy
                                reserves the right to adjust training times or venues when necessary and will notify in
                                advance.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30px; padding: 0 8px 8px 0;">
                            <div
                                style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">
                                4</div>
                        </td>
                        <td style="padding: 0 0 8px 0;">
                            <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">
                                学费一经缴付，概不退还（Non‑refundable）。</p>
                            <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">Fees paid are
                                strictly non‑refundable under all circumstances.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30px; padding: 0 8px 8px 0;">
                            <div
                                style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">
                                5</div>
                        </td>
                        <td style="padding: 0 0 8px 0;">
                            <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">
                                本人同意遵守学院及教练的所有指示与安排。</p>
                            <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">I agree to follow
                                all instructions, rules, and arrangements set by the Academy and coaches.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30px; padding: 0 8px 8px 0;">
                            <div
                                style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">
                                6</div>
                        </td>
                        <td style="padding: 0 0 8px 0;">
                            <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">
                                只限于本院通知取消课程，将会另行安排补课，家长不允许自行取消课程。</p>
                            <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">Replacement classes
                                are only provided when the Academy cancels a session; parents may not cancel classes on
                                their own.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30px; padding: 0 8px 8px 0;">
                            <div
                                style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">
                                7</div>
                        </td>
                        <td style="padding: 0 0 8px 0;">
                            <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">
                                如学员因病或其他原因无法出席训练，必须向行政与教练申请请假；未经许可的缺席将被记录。</p>
                            <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">If the student
                                cannot attend due to sickness or other reasons, leave must be applied for with admin and
                                coach; unapproved absences will be recorded.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30px; padding: 0 8px 8px 0;">
                            <div
                                style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">
                                8</div>
                        </td>
                        <td style="padding: 0 0 8px 0;">
                            <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">
                                州队及后备队必须出席所有训练，保持良好态度，接受严格训练与训导。</p>
                            <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">State‑team and
                                reserve athletes must attend all training, maintain good attitude, and accept strict
                                training and discipline.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30px; padding: 0 8px 8px 0;">
                            <div
                                style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">
                                9</div>
                        </td>
                        <td style="padding: 0 0 8px 0;">
                            <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">
                                如因脚受伤、扭伤或生病，请勿勉强出席训练，后果自负。</p>
                            <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">Students with
                                injuries or illness should not attend training; any consequences are at their own risk.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30px; padding: 0 8px 8px 0;">
                            <div
                                style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">
                                10</div>
                        </td>
                        <td style="padding: 0 0 8px 0;">
                            <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">
                                本院不负责学员及家长的任何贵重财物。</p>
                            <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">The Academy is not
                                responsible for any valuables belonging to students or parents.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30px; padding: 0 8px 8px 0;">
                            <div
                                style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">
                                11</div>
                        </td>
                        <td style="padding: 0 0 8px 0;">
                            <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">
                                不允许打架、吵架、态度恶劣或不配合训练，否则将被取消州队及学员资格。</p>
                            <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">Fighting,
                                quarrelling, poor attitude, or refusing to cooperate with training may result in removal
                                from the state team and the Academy.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30px; padding: 0 8px 8px 0;">
                            <div
                                style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">
                                12</div>
                        </td>
                        <td style="padding: 0 0 8px 0;">
                            <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">
                                训练期间不允许吃食物，只能在休息时间喝水。</p>
                            <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">Eating is not
                                allowed during training; only drinking water during breaks is permitted.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30px; padding: 0 8px 8px 0;">
                            <div
                                style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">
                                13</div>
                        </td>
                        <td style="padding: 0 0 8px 0;">
                            <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">
                                家长不允许干涉教练所安排的专业训练计划及纪律管理。</p>
                            <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">Parents are not
                                allowed to interfere with professional training plans or discipline set by the coaches.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30px; padding: 0 8px 8px 0;">
                            <div
                                style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">
                                14</div>
                        </td>
                        <td style="padding: 0 0 8px 0;">
                            <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">
                                家长必须准时载送孩子往返训练地点，并自行负责交通安全。</p>
                            <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">Parents must send
                                and pick up their children on time and are fully responsible for transport safety.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30px; padding: 0 8px 8px 0;">
                            <div
                                style="width: 24px; height: 24px; background: #1e293b; border-radius: 50%; color: white; font-weight: 700; font-size: 12px; text-align: center; font-family: Arial, sans-serif; box-sizing: border-box; line-height: 10px;">
                                15</div>
                        </td>
                        <td style="padding: 0 0 8px 0;">
                            <p style="margin: 0 0 2px 0; font-weight: 600; font-size: 11px; line-height: 1.4;">
                                训练过程中，学员可能被录影或拍照作为宣传用途，如家长不允许，须以书面通知本院。</p>
                            <p style="margin: 0; color: #4b5563; font-size: 10px; line-height: 1.4;">Training sessions
                                may be recorded or photographed for publicity; parents who do not consent must inform
                                the Academy in writing.</p>
                        </td>
                    </tr>
                </table>

                <div
                    style="margin-top: 18px; padding: 14px 16px; background: #f8fafc; border: 2px solid #1e293b; border-radius: 6px;">
                    <p style="font-weight: 700; margin: 0 0 8px 0; color: #1e293b; font-size: 12px;">LEGAL
                        ACKNOWLEDGEMENT / 法律声明</p>
                    <p style="margin: 0 0 6px 0; font-size: 10.5px; line-height: 1.5;">
                        By signing this document, the parent/guardian acknowledges that they have read, understood, and
                        agreed to all 15 terms and conditions listed above.
                    </p>
                    <p style="color: #4b5563; font-size: 10px; margin: 0 0 10px 0; line-height: 1.5;">
                        家长/监护人签署此文件，即表示已阅读、理解并同意上述所有15项条款与条件。
                    </p>
                    <p style="margin: 0; font-weight: 600; font-size: 11px; line-height: 1.6;">
                        Signed by: <span id="pdf-parent-name-2" style="font-weight: 500;"></span> (<span
                            id="pdf-parent-ic-2" style="font-weight: 500;"></span>)<br>
                        Date: <span id="pdf-date-2" style="font-weight: 500;"></span>
                    </p>
                </div>
            </div>
        </div>




        <!-- LOADING OVERLAY -->
        <div id="loading-overlay"
            style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center;">
            <div style="text-align: center; color: white;">
                <div
                    style="width: 60px; height: 60px; border: 5px solid rgba(255,255,255,0.3); border-top: 5px solid white; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;">
                </div>
                <h3 style="font-size: 20px; margin: 0;">Processing Registration...</h3>
                <p style="margin-top: 10px; font-size: 14px; opacity: 0.8;">正在处理报名 · Please wait</p>
            </div>
        </div>



</body>
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
    let classHolidays = []; // Store holidays loaded from API

    // ========================================
    // DOM CONTENT LOADED
    // ========================================
    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById('today-date').value = new Date().toLocaleDateString('en-GB');
        document.getElementById('ic').addEventListener('input', formatIC);
        document.getElementById('ic').addEventListener('input', calculateAge);
        document.getElementById('parent-ic').addEventListener('input', formatIC);
        document.getElementById('phone').addEventListener('input', formatPhone);

        document.getElementById('school').addEventListener('change', toggleOtherSchool);

        document.getElementById('password-type').addEventListener('change', togglePasswordField);

        document.getElementById('email').addEventListener('input', function (e) {
            checkParentEmail(e.target.value.trim());
        });

        const statusRadios = document.querySelectorAll('.status-radio');
        statusRadios.forEach(radio => {
            radio.addEventListener('change', function () {
                updateStatusRadioStyle();
                updateScheduleAvailability();
                updateEventAvailability(); // Update event availability when status changes
            });
        });

        updateStatusRadioStyle();
        updateScheduleAvailability();

        // Call updateEventAvailability after a short delay to ensure DOM is fully loaded
        setTimeout(function () {
            updateEventAvailability();
        }, 100);

        // Load holidays on page load
        loadHolidays();

        toggleIDType();

        toggleParentIDType();
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

    // Update calculateAge function to handle both IC and Passport
    function calculateAge(e) {
        const idType = document.getElementById('id-type').value;

        if (idType === 'passport') {
            // Don't auto-calculate for passport
            return;
        }

        const ic = e.target.value.replace(/\D/g, '');
        const ageField = document.getElementById('age');

        if (ic.length >= 6) {
            let year = parseInt(ic.substring(0, 2));
            const month = parseInt(ic.substring(2, 4));
            const day = parseInt(ic.substring(4, 6));

            const currentYear = new Date().getFullYear();
            const century = (year >= 0 && year <= (currentYear % 100)) ? 2000 : 1900;
            year += century;

            const birthDate = new Date(year, month - 1, day);
            const targetYear = 2026;
            let age = targetYear - birthDate.getFullYear();

            if (isNaN(age) || age < 0 || age > 100) {
                ageField.value = '';
            } else {
                ageField.value = age;
            }
        } else {
            ageField.value = '';
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
            // || ic.length < 14
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
            // || parentIC.length < 14
            if (!parentIC) {
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
        //pdf.save(`${nameForFile}_Registration_Agreement.pdf`);

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
        navigator.clipboard.writeText(accountNumber).then(function () {
            // Show success message
            alert('Account number copied! 账户号码已复制！');
        }, function (err) {
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
        reader.onload = function (e) {
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
        navigator.clipboard.writeText(accountNumber).then(function () {
            // Show success message
            alert('Account number copied! 账户号码已复制！');
        }, function (err) {
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
        reader.onload = function (e) {
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

        reader.onerror = function (error) {
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
            backBtn.onmouseover = function () {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 6px 16px rgba(30, 41, 59, 0.4)';
            };
            backBtn.onmouseout = function () {
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
    document.addEventListener('DOMContentLoaded', function () {
        updateBackButton();
    });

    // Override the original changeStep to also update back button
    // This ensures the back button updates whenever the step changes
    const originalChangeStep = window.changeStep;
    if (typeof originalChangeStep === 'function') {
        window.changeStep = function (direction) {
            originalChangeStep(direction);
            setTimeout(updateBackButton, 50); // Small delay to ensure DOM is updated
        };
    }


    // ========================================
    // BACKGROUND MUSIC CONTROL WITH USER INTERACTION
    // ========================================
    document.addEventListener("DOMContentLoaded", function () {
        localStorage.clear();
        const musicToggle = document.getElementById('musicToggle');
        const musicIcon = document.getElementById('musicIcon');
        const musicMenuToggle = document.getElementById('musicMenuToggle');
        const musicMenu = document.getElementById('musicMenu');
        const musicItems = document.querySelectorAll('.music-item');
        const musicEnableOverlay = document.getElementById('musicEnableOverlay');
        const enableMusicBtn = document.getElementById('enableMusicBtn');
        const skipMusicBtn = document.getElementById('skipMusicBtn');

        let currentSound = null;
        let isPlaying = false;
        let musicEnabled = localStorage.getItem('musicEnabled') === 'true';
        let currentTrack = localStorage.getItem('selectedMusic') || 'https://wushu-assets.s3.ap-southeast-1.amazonaws.com/music/Song.mp3';
        let currentTrackName = localStorage.getItem('selectedMusicName') || 'Song';

        // Initialize Howler with current track
        function initSound(src) {
            if (!src) return null;

            return new Howl({
                src: [src],
                html5: true,
                loop: true,
                volume: 0.3,
                onplay: function () {
                    console.log('🎵 Music started:', currentTrackName);
                    isPlaying = true;
                    updateMusicIcon(true);
                },
                onpause: function () {
                    console.log('⏸️ Music paused');
                    isPlaying = false;
                    updateMusicIcon(false);
                },
                onstop: function () {
                    isPlaying = false;
                    updateMusicIcon(false);
                },
                onloaderror: function (id, error) {
                    console.error('❌ Failed to load audio:', error);
                    updateMusicIcon(false);
                },
                onplayerror: function (id, error) {
                    console.error('❌ Playback error:', error);
                    updateMusicIcon(false);
                }
            });
        }

        // Initialize and play music
        function initAndPlayMusic() {
            if (currentTrack && !currentSound) {
                currentSound = initSound(currentTrack);
                if (currentSound) {
                    currentSound.play();
                    highlightSelectedTrack(currentTrack);
                }
            } else if (currentSound && !isPlaying) {
                currentSound.play();
            }
        }

        // Update music icon
        function updateMusicIcon(playing) {
            if (playing) {
                musicIcon.classList.remove('fa-play');
                musicIcon.classList.add('fa-pause');
            } else {
                musicIcon.classList.remove('fa-pause');
                musicIcon.classList.add('fa-play');
            }
        }

        // Highlight selected track
        function highlightSelectedTrack(src) {
            musicItems.forEach(item => {
                if (item.dataset.src === src) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });
        }

        // Enable music button
        if (enableMusicBtn) {
            enableMusicBtn.addEventListener('click', function () {
                musicEnabled = true;
                localStorage.setItem('musicEnabled', 'true');
                musicEnableOverlay.classList.add('fade-out');
                setTimeout(() => {
                    musicEnableOverlay.style.display = 'none';
                }, 300);
                initAndPlayMusic();
            });
        }

        // Skip music button
        if (skipMusicBtn) {
            skipMusicBtn.addEventListener('click', function () {
                musicEnabled = false;
                localStorage.setItem('musicEnabled', 'false');
                currentTrack = '';
                musicEnableOverlay.classList.add('fade-out');
                setTimeout(() => {
                    musicEnableOverlay.style.display = 'none';
                }, 300);
            });
        }

        // Click anywhere on overlay to enable music
        if (musicEnableOverlay) {
            musicEnableOverlay.addEventListener('click', function (e) {
                if (e.target === musicEnableOverlay) {
                    musicEnabled = true;
                    localStorage.setItem('musicEnabled', 'true');
                    musicEnableOverlay.classList.add('fade-out');
                    setTimeout(() => {
                        musicEnableOverlay.style.display = 'none';
                    }, 300);
                    initAndPlayMusic();
                }
            });
        }

        // Toggle music on button click
        if (musicToggle) {
            musicToggle.addEventListener('click', function (e) {
                e.stopPropagation();

                if (!currentSound) {
                    if (currentTrack) {
                        currentSound = initSound(currentTrack);
                        currentSound.play();
                    }
                    return;
                }

                if (isPlaying) {
                    currentSound.pause();
                } else {
                    currentSound.play();
                }
            });
        }

        // Toggle music menu
        if (musicMenuToggle) {
            musicMenuToggle.addEventListener('click', function (e) {
                e.stopPropagation();
                musicMenu.classList.toggle('hidden');
            });
        }

        // Close menu when clicking outside
        document.addEventListener('click', function (e) {
            if (!musicMenu.contains(e.target) && !musicMenuToggle.contains(e.target)) {
                musicMenu.classList.add('hidden');
            }
        });

        // Handle music selection
        musicItems.forEach(item => {
            item.addEventListener('click', function (e) {
                e.stopPropagation();
                const newTrack = this.dataset.src;
                const newTrackName = this.dataset.name;

                // If "No Music" is selected
                if (newTrack === '') {
                    if (currentSound) {
                        currentSound.stop();
                        currentSound.unload();
                        currentSound = null;
                    }
                    currentTrack = '';
                    currentTrackName = 'No Music';
                    isPlaying = false;
                    updateMusicIcon(false);
                    localStorage.setItem('selectedMusic', '');
                    localStorage.setItem('selectedMusicName', 'No Music');
                    localStorage.setItem('musicEnabled', 'false');
                    highlightSelectedTrack('');
                    musicMenu.classList.add('hidden');
                    console.log('🔇 Music disabled');
                    return;
                }

                // Stop current track
                if (currentSound) {
                    currentSound.stop();
                    currentSound.unload();
                }

                // Load and play new track
                currentTrack = newTrack;
                currentTrackName = newTrackName;
                currentSound = initSound(currentTrack);
                currentSound.play();

                // Save preference
                localStorage.setItem('selectedMusic', currentTrack);
                localStorage.setItem('selectedMusicName', currentTrackName);
                localStorage.setItem('musicEnabled', 'true');

                highlightSelectedTrack(currentTrack);
                musicMenu.classList.add('hidden');

                console.log('🎵 Switched to:', currentTrackName);
            });
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', function () {
            if (currentSound) {
                currentSound.unload();
            }
        });
    });



    // ========================================
    // ID TYPE TOGGLE (IC vs Passport)
    // ========================================
    function toggleIDType() {
        const idType = document.getElementById('id-type').value;
        const icField = document.getElementById('ic');
        const ageField = document.getElementById('age');
        const idLabel = document.getElementById('id-label');
        const idFormatHint = document.getElementById('id-format-hint');
        const ageHint = document.getElementById('age-hint');

        if (idType === 'passport') {
            // Passport mode
            idLabel.textContent = 'Passport Number 护照号码 *';
            idFormatHint.textContent = 'Enter passport number';
            icField.placeholder = 'A12345678';
            icField.removeEventListener('input', calculateAge);
            icField.removeEventListener('input', formatIC);

            // Enable age field for manual input
            ageField.readOnly = false;
            ageField.classList.remove('bg-slate-100', 'text-slate-500', 'cursor-not-allowed');
            ageField.classList.add('bg-white', 'focus:border-amber-500');
            ageField.placeholder = 'Enter age';
            ageField.value = '';
            ageHint.innerHTML = '<i class="fas fa-edit mr-1"></i>Enter manually for passport';

        } else {
            // IC mode
            idLabel.textContent = 'IC Number 身份证号码 *';
            idFormatHint.textContent = 'Format: 000000-00-0000';
            icField.placeholder = '000000-00-0000';
            icField.addEventListener('input', calculateAge);
            icField.addEventListener('input', formatIC);

            // Disable age field (auto-calculated)
            ageField.readOnly = true;
            ageField.classList.add('bg-slate-100', 'text-slate-500', 'cursor-not-allowed');
            ageField.classList.remove('bg-white', 'focus:border-amber-500');
            ageField.placeholder = 'Auto-calculated';
            ageField.value = '';
            ageHint.innerHTML = '<i class="fas fa-info-circle mr-1"></i>Calculated from IC';

            // Recalculate age if IC has value
            if (icField.value) {
                calculateAge({ target: icField });
            }
        }
    }

    // ========================================
    // PARENT ID TYPE TOGGLE (IC vs Passport)
    // ========================================
    function toggleParentIDType() {
        const parentIdType = document.getElementById('parent-id-type').value;
        const parentIcField = document.getElementById('parent-ic');
        const parentIdLabel = document.getElementById('parent-id-label');
        const parentIdFormatHint = document.getElementById('parent-id-format-hint');

        if (parentIdType === 'passport') {
            // Passport mode
            parentIdLabel.textContent = 'Parent/Guardian Passport No. 家长护照号码 *';
            parentIdFormatHint.textContent = 'Enter passport number';
            parentIcField.placeholder = 'A12345678';
            parentIcField.removeEventListener('input', formatIC);
            parentIcField.maxLength = 30;

        } else {
            // IC mode
            parentIdLabel.textContent = 'Parent/Guardian IC No. 家长身份证 *';
            parentIdFormatHint.textContent = 'Format: 000000-00-0000';
            parentIcField.placeholder = '000000-00-0000';
            parentIcField.addEventListener('input', formatIC);
            parentIcField.maxLength = 14;
        }

        // Clear the field when switching types
        parentIcField.value = '';
    }


</script>