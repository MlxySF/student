<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Guides - Wushu Sport Academy</title>
    <link rel="icon" type="image/png" href="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/Wushu+Sport+Academy+Circle+Yellow.png">
    <link rel="shortcut icon" type="image/png" href="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/Wushu+Sport+Academy+Circle+Yellow.png">
    <link rel="apple-touch-icon" href="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/Wushu+Sport+Academy+Circle+Yellow.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 60px;
            animation: fadeInDown 0.8s ease;
        }

        .header-logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 30px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: float 3s ease-in-out infinite;
            overflow: hidden;
        }

        .header-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .header h1 {
            color: white;
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 15px;
            text-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }

        .header p {
            color: rgba(255,255,255,0.95);
            font-size: 20px;
            font-weight: 500;
        }

        .language-section {
            background: white;
            border-radius: 30px;
            padding: 50px;
            margin-bottom: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: fadeInUp 0.8s ease;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 25px;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 3px solid #f0f0f0;
        }

        .section-flag {
            font-size: 80px;
            line-height: 1;
            animation: bounce 2s ease-in-out infinite;
        }

        .section-title-wrapper h2 {
            font-size: 36px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-title-wrapper p {
            font-size: 16px;
            color: #64748b;
            font-weight: 500;
        }

        .videos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
        }

        .video-card {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 20px;
            padding: 30px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            border: 3px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .video-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102,126,234,0.1) 0%, rgba(118,75,162,0.1) 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .video-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(102,126,234,0.3);
            border-color: #667eea;
        }

        .video-card:hover::before {
            opacity: 1;
        }

        .video-card:active {
            transform: translateY(-8px) scale(1.01);
        }

        .video-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            transition: all 0.4s ease;
            position: relative;
            z-index: 1;
        }

        .video-card:hover .video-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .video-icon i {
            font-size: 40px;
            color: white;
        }

        .video-number {
            position: absolute;
            top: -10px;
            right: -10px;
            width: 35px;
            height: 35px;
            background: #fbbf24;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: white;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(251,191,36,0.4);
        }

        .video-content {
            position: relative;
            z-index: 1;
        }

        .video-title {
            font-size: 22px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .video-desc {
            font-size: 15px;
            color: #64748b;
            font-weight: 500;
            line-height: 1.6;
        }

        .play-hint {
            margin-top: 15px;
            padding: 10px 15px;
            background: white;
            border-radius: 10px;
            font-size: 13px;
            color: #667eea;
            font-weight: 600;
            border: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .video-card:hover .play-hint {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .play-hint i {
            font-size: 14px;
        }

        /* VIDEO MODAL STYLES - OPTIMIZED FOR DESKTOP */
        .video-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .video-modal.active {
            display: flex;
        }

        .modal-content {
            position: relative;
            width: 85%;
            max-width: 900px;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.5);
            animation: modalSlideIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            border: 3px solid rgba(102,126,234,0.3);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255,255,255,0.1);
        }

        .modal-title {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            min-width: 0;
        }

        .modal-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
            flex-shrink: 0;
        }

        .modal-title-text {
            flex: 1;
            min-width: 0;
        }

        .modal-title h3 {
            font-size: 22px;
            font-weight: 800;
            color: white;
            margin: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .modal-title p {
            font-size: 13px;
            color: rgba(255,255,255,0.7);
            margin: 5px 0 0 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .close-btn {
            width: 45px;
            height: 45px;
            background: rgba(239,68,68,0.2);
            border: 2px solid rgba(239,68,68,0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #ef4444;
            font-size: 20px;
            flex-shrink: 0;
        }

        .close-btn:hover {
            background: #ef4444;
            color: white;
            transform: rotate(90deg) scale(1.1);
            border-color: #ef4444;
        }

        .video-container {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            background: #000;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }

        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .video-info {
            margin-top: 20px;
            padding: 16px;
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .video-info p {
            color: rgba(255,255,255,0.8);
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
        }

        .footer {
            text-align: center;
            margin-top: 60px;
            color: white;
            animation: fadeIn 1s ease 0.5s both;
        }

        .footer p {
            font-size: 16px;
            font-weight: 500;
            opacity: 0.9;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-30px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 20px 15px;
            }

            .header h1 {
                font-size: 32px;
            }

            .header p {
                font-size: 16px;
            }

            .language-section {
                padding: 30px 20px;
            }

            .section-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .section-flag {
                font-size: 60px;
            }

            .section-title-wrapper h2 {
                font-size: 28px;
            }

            .videos-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .video-card {
                padding: 25px;
            }

            .video-title {
                font-size: 20px;
            }

            .modal-content {
                width: 95%;
                padding: 20px;
                max-height: 95vh;
            }

            .modal-header {
                margin-bottom: 15px;
                padding-bottom: 12px;
            }

            .modal-title {
                gap: 10px;
            }

            .modal-title h3 {
                font-size: 18px;
            }

            .modal-title p {
                font-size: 12px;
            }

            .modal-icon {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }

            .close-btn {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            .video-info {
                margin-top: 15px;
                padding: 12px;
            }

            .video-info p {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-logo">
                <img src="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/Wushu+Sport+Academy+Circle+Yellow.png" alt="Wushu Sport Academy Logo">
            </div>
            <h1>Video Guides</h1>
            <p>Wushu Sport Academy - System Guide Videos</p>
        </div>

        <!-- Chinese Videos Section -->
        <div class="language-section">
            <div class="section-header">
                <div class="section-flag">🇨🇳</div>
                <div class="section-title-wrapper">
                    <h2>中文版本 (Chinese Version)</h2>
                    <p>如何使用系统 - System Guides</p>
                </div>
            </div>

            <div class="videos-grid">
                <div class="video-card" onclick="openVideo('videos/tutorials/chinese/如何注册报名表格.mp4', '如何注册报名表格', '注册表格概述 - Registration Form Overview')">
                    <div class="video-icon">
                        <i class="fas fa-play"></i>
                        <div class="video-number">1</div>
                    </div>
                    <div class="video-content">
                        <h3 class="video-title">如何注册报名表格</h3>
                        <p class="video-desc">注册表格概述 - Registration Form Overview</p>
                        <div class="play-hint">
                            <i class="fas fa-play-circle"></i>
                            <span>点击播放视频 Click to Play</span>
                        </div>
                    </div>
                </div>

                <div class="video-card" onclick="openVideo('videos/tutorials/chinese/如何使用学生系统.mp4', '如何使用学生系统', '学生系统指南 - Student System Guide')">
                    <div class="video-icon">
                        <i class="fas fa-play"></i>
                        <div class="video-number">2</div>
                    </div>
                    <div class="video-content">
                        <h3 class="video-title">如何使用学生系统</h3>
                        <p class="video-desc">学生系统指南 - Student System Guide</p>
                        <div class="play-hint">
                            <i class="fas fa-play-circle"></i>
                            <span>点击播放视频 Click to Play</span>
                        </div>
                    </div>
                </div>

                <div class="video-card" onclick="openVideo('videos/tutorials/chinese/如何缴付学费指南.mp4', '如何缴付学费指南', '缴付学费指南 - Payment Guide')">
                    <div class="video-icon">
                        <i class="fas fa-play"></i>
                        <div class="video-number">3</div>
                    </div>
                    <div class="video-content">
                        <h3 class="video-title">如何缴付学费指南</h3>
                        <p class="video-desc">缴付学费指南 - Payment Guide</p>
                        <div class="play-hint">
                            <i class="fas fa-play-circle"></i>
                            <span>点击播放视频 Click to Play</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- English Videos Section -->
        <div class="language-section">
            <div class="section-header">
                <div class="section-flag">🇬🇧</div>
                <div class="section-title-wrapper">
                    <h2>English Version</h2>
                    <p>Learn how to use the parent portal</p>
                </div>
            </div>

            <div class="videos-grid">
                <div class="video-card" onclick="openVideo('videos/tutorials/english/Registration Video.mp4', 'Registration Video', 'Registration Form Explanations - Complete guide to registering')">
                    <div class="video-icon">
                        <i class="fas fa-play"></i>
                        <div class="video-number">1</div>
                    </div>
                    <div class="video-content">
                        <h3 class="video-title">Registration Video</h3>
                        <p class="video-desc">Registration Form Explanations - Complete guide to registering</p>
                        <div class="play-hint">
                            <i class="fas fa-play-circle"></i>
                            <span>Click to Play Video</span>
                        </div>
                    </div>
                </div>

                <div class="video-card" onclick="openVideo('videos/tutorials/english/Student System Guide.mp4', 'Student System Guide', 'Guides you around the student portal - Navigate the system easily')">
                    <div class="video-icon">
                        <i class="fas fa-play"></i>
                        <div class="video-number">2</div>
                    </div>
                    <div class="video-content">
                        <h3 class="video-title">Student System Guide</h3>
                        <p class="video-desc">Guides you around the student portal - Navigate the system easily</p>
                        <div class="play-hint">
                            <i class="fas fa-play-circle"></i>
                            <span>Click to Play Video</span>
                        </div>
                    </div>
                </div>

                <div class="video-card" onclick="openVideo('videos/tutorials/english/Invoice Payment Video.mp4', 'Invoice Payment Video', 'Guide you through the invoicing system - Learn payment process')">
                    <div class="video-icon">
                        <i class="fas fa-play"></i>
                        <div class="video-number">3</div>
                    </div>
                    <div class="video-content">
                        <h3 class="video-title">Invoice Payment Video</h3>
                        <p class="video-desc">Guide you through the invoicing system - Learn payment process</p>
                        <div class="play-hint">
                            <i class="fas fa-play-circle"></i>
                            <span>Click to Play Video</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p><i class="fas fa-heart"></i> Wushu Sport Academy 武术体育学院</p>
            <p style="margin-top: 10px; font-size: 14px;">System Videos - 2026</p>
        </div>
    </div>

    <!-- VIDEO MODAL -->
    <div class="video-modal" id="videoModal" onclick="closeModalOnBackdrop(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <div class="modal-title">
                    <div class="modal-icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <div class="modal-title-text">
                        <h3 id="modalVideoTitle">Video Title</h3>
                        <p id="modalVideoDesc">Video Description</p>
                    </div>
                </div>
                <div class="close-btn" onclick="closeVideo()">
                    <i class="fas fa-times"></i>
                </div>
            </div>

            <div class="video-container">
                <video id="videoPlayer" controls controlsList="nodownload">
                    <source id="videoSource" src="" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>

            <div class="video-info">
                <p><i class="fas fa-info-circle"></i> Use the video controls to play, pause, adjust volume, or go fullscreen for the best viewing experience.</p>
            </div>
        </div>
    </div>

    <script>
        function openVideo(videoPath, title, description) {
            const modal = document.getElementById('videoModal');
            const videoPlayer = document.getElementById('videoPlayer');
            const videoSource = document.getElementById('videoSource');
            const modalTitle = document.getElementById('modalVideoTitle');
            const modalDesc = document.getElementById('modalVideoDesc');

            // Set video source and info
            videoSource.src = videoPath;
            modalTitle.textContent = title;
            modalDesc.textContent = description;

            // Load and play video
            videoPlayer.load();

            // Show modal
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Auto play video after modal opens
            setTimeout(() => {
                videoPlayer.play().catch(e => console.log('Autoplay prevented:', e));
            }, 300);
        }

        function closeVideo() {
            const modal = document.getElementById('videoModal');
            const videoPlayer = document.getElementById('videoPlayer');

            // Pause video
            videoPlayer.pause();
            videoPlayer.currentTime = 0;

            // Hide modal
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        function closeModalOnBackdrop(event) {
            if (event.target.id === 'videoModal') {
                closeVideo();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeVideo();
            }
        });

        // Prevent video from playing in background
        document.getElementById('videoModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeVideo();
            }
        });
    </script>
</body>
</html>