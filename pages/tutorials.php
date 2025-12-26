<?php
/**
 * Tutorials Page - Dedicated page for tutorial videos
 * Created from floating tutorial button modal
 */

redirectIfNotLoggedIn();
?>

<style>
/* Tutorial Page Specific Styles */
.tutorial-page-header {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(79, 70, 229, 0.3);
}

.tutorial-page-header h2 {
    margin: 0 0 10px 0;
    font-size: 28px;
    font-weight: 700;
}

.tutorial-page-header p {
    margin: 0;
    opacity: 0.95;
    font-size: 15px;
}

/* Language Sections */
.tutorial-section {
    margin-bottom: 40px;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    padding: 20px;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius: 12px;
    border-left: 5px solid #4f46e5;
}

.section-flag {
    font-size: 42px;
    line-height: 1;
}

.section-title {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
}

.section-subtitle {
    margin: 5px 0 0 0;
    font-size: 14px;
    color: #64748b;
}

/* Video Items Container */
.videos-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

/* Individual Video Item */
.tutorial-video-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 18px;
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.tutorial-video-item:hover {
    border-color: #4f46e5;
    transform: translateX(8px);
    box-shadow: 0 6px 20px rgba(79, 70, 229, 0.2);
    background: #f8fafc;
}

.tutorial-video-icon {
    width: 70px;
    height: 70px;
    min-width: 70px;
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 28px;
    transition: all 0.3s ease;
}

.tutorial-video-item:hover .tutorial-video-icon {
    transform: scale(1.1) rotate(5deg);
}

.tutorial-video-content {
    flex: 1;
}

.tutorial-video-title {
    margin: 0 0 8px 0;
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
}

.tutorial-video-desc {
    margin: 0;
    font-size: 14px;
    color: #64748b;
}

/* Video Player Container */
.video-player-container {
    display: none;
    margin: 15px 0;
    padding: 0;
    background: transparent;
    border-radius: 15px;
    opacity: 0;
    max-height: 0;
    overflow: hidden;
    transition: all 0.4s ease;
    position: relative;
}

.video-player-container.active {
    display: block;
    opacity: 1;
    max-height: 800px;
    animation: slideDown 0.4s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Floating Close Button */
.floating-close-btn {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 50px;
    height: 50px;
    background: rgba(239, 68, 68, 0.95);
    color: white;
    border: 3px solid rgba(255, 255, 255, 0.9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    cursor: pointer;
    z-index: 10;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(10px);
}

.floating-close-btn:hover {
    background: rgba(220, 38, 38, 1);
    transform: scale(1.15) rotate(90deg);
    box-shadow: 0 8px 25px rgba(239, 68, 68, 0.5);
}

.floating-close-btn:active {
    transform: scale(0.95) rotate(90deg);
}

.video-iframe-wrapper {
    position: relative;
    width: 100%;
    padding-bottom: 56.25%;
    background: #000;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.video-iframe-wrapper video,
.video-iframe-wrapper iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: none;
}

/* Responsive Styles */
@media (max-width: 768px) {
    .tutorial-page-header {
        padding: 20px;
    }

    .tutorial-page-header h2 {
        font-size: 22px;
    }

    .tutorial-page-header p {
        font-size: 14px;
    }

    .section-header {
        padding: 15px;
    }

    .section-flag {
        font-size: 32px;
    }

    .section-title {
        font-size: 18px;
    }

    .section-subtitle {
        font-size: 13px;
    }

    .tutorial-video-item {
        padding: 15px;
    }

    .tutorial-video-icon {
        width: 60px;
        height: 60px;
        min-width: 60px;
        font-size: 24px;
    }

    .tutorial-video-title {
        font-size: 16px;
    }

    .tutorial-video-desc {
        font-size: 13px;
    }

    .floating-close-btn {
        width: 45px;
        height: 45px;
        font-size: 20px;
    }
}

@media (max-width: 480px) {
    .tutorial-page-header {
        padding: 18px;
    }

    .tutorial-page-header h2 {
        font-size: 20px;
    }

    .section-flag {
        font-size: 28px;
    }

    .section-title {
        font-size: 16px;
    }

    .tutorial-video-item {
        padding: 12px;
        gap: 12px;
    }

    .tutorial-video-icon {
        width: 50px;
        height: 50px;
        min-width: 50px;
        font-size: 20px;
    }

    .tutorial-video-title {
        font-size: 15px;
    }

    .tutorial-video-desc {
        font-size: 12px;
    }

    .floating-close-btn {
        width: 40px;
        height: 40px;
        font-size: 18px;
        top: 12px;
        right: 12px;
    }
}
</style>

<div class="tutorial-page-header">
    <h2><i class="fas fa-graduation-cap"></i> Tutorial Videos</h2>
    <p>Learn how to use the student portal system with step-by-step video guides</p>
</div>

<!-- Chinese Section -->
<div class="tutorial-section">
    <div class="section-header">
        <div class="section-flag">🇨🇳</div>
        <div>
            <h3 class="section-title">中文版本 (Chinese Version)</h3>
            <p class="section-subtitle">系统教学视频</p>
        </div>
    </div>
    
    <div class="videos-container">
        <div class="tutorial-video-item" onclick="playVideo('zh', 1, this)">
            <div class="tutorial-video-icon">
                <i class="fas fa-play"></i>
            </div>
            <div class="tutorial-video-content">
                <h4 class="tutorial-video-title">如何注册报名表格</h4>
                <p class="tutorial-video-desc">注册表格概述</p>
            </div>
        </div>
        
        <!-- Video Player for Chinese Part 1 -->
        <div class="video-player-container" id="player-zh-1"></div>
        
        <div class="tutorial-video-item" onclick="playVideo('zh', 2, this)">
            <div class="tutorial-video-icon">
                <i class="fas fa-play"></i>
            </div>
            <div class="tutorial-video-content">
                <h4 class="tutorial-video-title">如何使用学生系统</h4>
                <p class="tutorial-video-desc">学生系统指南</p>
            </div>
        </div>
        
        <!-- Video Player for Chinese Part 2 -->
        <div class="video-player-container" id="player-zh-2"></div>
        
        <div class="tutorial-video-item" onclick="playVideo('zh', 3, this)">
            <div class="tutorial-video-icon">
                <i class="fas fa-play"></i>
            </div>
            <div class="tutorial-video-content">
                <h4 class="tutorial-video-title">如何缴付学费指南</h4>
                <p class="tutorial-video-desc">缴付学费指南</p>
            </div>
        </div>
        
        <!-- Video Player for Chinese Part 3 -->
        <div class="video-player-container" id="player-zh-3"></div>
    </div>
</div>

<!-- English Section -->
<div class="tutorial-section">
    <div class="section-header">
        <div class="section-flag">🇬🇧</div>
        <div>
            <h3 class="section-title">English Version</h3>
            <p class="section-subtitle">Learn how to use the parent portal</p>
        </div>
    </div>
    
    <div class="videos-container">
        <div class="tutorial-video-item" onclick="playVideo('en', 1, this)">
            <div class="tutorial-video-icon">
                <i class="fas fa-play"></i>
            </div>
            <div class="tutorial-video-content">
                <h4 class="tutorial-video-title">Registration Tutorial</h4>
                <p class="tutorial-video-desc">Registration Form Explanations</p>
            </div>
        </div>
        
        <!-- Video Player for English Part 1 -->
        <div class="video-player-container" id="player-en-1"></div>
        
        <div class="tutorial-video-item" onclick="playVideo('en', 2, this)">
            <div class="tutorial-video-icon">
                <i class="fas fa-play"></i>
            </div>
            <div class="tutorial-video-content">
                <h4 class="tutorial-video-title">Student System Tutorial</h4>
                <p class="tutorial-video-desc">Guides you around the student portal</p>
            </div>
        </div>
        
        <!-- Video Player for English Part 2 -->
        <div class="video-player-container" id="player-en-2"></div>
        
        <div class="tutorial-video-item" onclick="playVideo('en', 3, this)">
            <div class="tutorial-video-icon">
                <i class="fas fa-play"></i>
            </div>
            <div class="tutorial-video-content">
                <h4 class="tutorial-video-title">Invoice Payment Tutorial</h4>
                <p class="tutorial-video-desc">Guide you through the invoicing system</p>
            </div>
        </div>
        
        <!-- Video Player for English Part 3 -->
        <div class="video-player-container" id="player-en-3"></div>
    </div>
</div>

<script>
// Tutorial Video Player Functions
function playVideo(language, part, element) {
    const playerId = `player-${language}-${part}`;
    const playerContainer = document.getElementById(playerId);
    
    // Close all other players first
    const allPlayers = document.querySelectorAll('.video-player-container');
    allPlayers.forEach(player => {
        if (player.id !== playerId) {
            player.classList.remove('active');
            player.innerHTML = '';
        }
    });
    
    // Define your video file paths
    const videos = {
        'en': {
            1: 'videos/tutorials/english/Registration Video.mp4',
            2: 'videos/tutorials/english/Student System Guide.mp4',
            3: 'videos/tutorials/english/Invoice Payment Video.mp4'
        },
        'zh': {
            1: 'videos/tutorials/chinese/如何注册报名表格.mp4',
            2: 'videos/tutorials/chinese/如何使用学生系统.mp4',
            3: 'videos/tutorials/chinese/如何缴付学费指南.mp4'
        }
    };
    
    const videoPath = videos[language][part];
    
    // Toggle player visibility
    if (playerContainer.classList.contains('active')) {
        // If already playing, close it
        playerContainer.classList.remove('active');
        playerContainer.innerHTML = '';
    } else {
        // Show player with video
        playerContainer.innerHTML = `
            <button class="floating-close-btn" onclick="closeVideoPlayer('${playerId}')">
                <i class="fas fa-times"></i>
            </button>
            <div class="video-iframe-wrapper">
                <video controls autoplay style="width: 100%; height: 100%; object-fit: contain;">
                    <source src="${videoPath}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
        `;
        
        playerContainer.classList.add('active');
        
        // Smooth scroll to the player
        setTimeout(() => {
            playerContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
    }
}

function closeVideoPlayer(playerId) {
    const playerContainer = document.getElementById(playerId);
    playerContainer.classList.remove('active');
    playerContainer.innerHTML = '';
}
</script>