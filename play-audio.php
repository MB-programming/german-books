<?php
require_once 'config.php';

$qrCode = isset($_GET['qr']) ? cleanInput($_GET['qr']) : '';

if (empty($qrCode)) {
    die('QR Code غير صالح');
}

// الحصول على بيانات الملف الصوتي
$stmt = $pdo->prepare("SELECT af.*, b.title as book_title
                       FROM audio_files af
                       JOIN books b ON af.book_id = b.id
                       WHERE af.qr_code = ?");
$stmt->execute([$qrCode]);
$audio = $stmt->fetch();

if (!$audio) {
    die('الملف الصوتي غير موجود');
}

$audioPath = $audio['audio_path'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مشغل الصوت - <?php echo htmlspecialchars($audio['book_title']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .player-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }

        .book-info {
            color: #666;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }

        .audio-player {
            width: 100%;
            margin: 30px 0;
            outline: none;
        }

        .controls {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-close {
            background: #e74c3c;
            color: white;
        }

        .btn-close:hover {
            background: #c0392b;
        }

        .audio-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }

        .audio-info p {
            color: #666;
            margin: 5px 0;
        }

        .wave-animation {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
            height: 60px;
            margin: 20px 0;
        }

        .wave-bar {
            width: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 4px;
            animation: wave 1s ease-in-out infinite;
        }

        .wave-bar:nth-child(1) { height: 20px; animation-delay: 0s; }
        .wave-bar:nth-child(2) { height: 35px; animation-delay: 0.1s; }
        .wave-bar:nth-child(3) { height: 50px; animation-delay: 0.2s; }
        .wave-bar:nth-child(4) { height: 35px; animation-delay: 0.3s; }
        .wave-bar:nth-child(5) { height: 20px; animation-delay: 0.4s; }

        @keyframes wave {
            0%, 100% { transform: scaleY(0.5); }
            50% { transform: scaleY(1); }
        }

        #playPauseBtn {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            font-size: 32px;
            cursor: pointer;
            transition: all 0.3s;
            margin: 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #playPauseBtn:hover {
            transform: scale(1.1);
        }

        .time-display {
            display: flex;
            justify-content: space-between;
            color: #666;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="player-container">
        <h1>🎵 مشغل الصوت</h1>

        <div class="book-info">
            <strong><?php echo htmlspecialchars($audio['book_title']); ?></strong><br>
            <span>الصفحة رقم: <?php echo $audio['page_number']; ?></span>
        </div>

        <div class="wave-animation" id="waveAnimation">
            <div class="wave-bar"></div>
            <div class="wave-bar"></div>
            <div class="wave-bar"></div>
            <div class="wave-bar"></div>
            <div class="wave-bar"></div>
        </div>

        <button id="playPauseBtn">▶</button>

        <audio id="audioPlayer" class="audio-player" controls>
            <source src="<?php echo $audioPath; ?>" type="audio/mpeg">
            المتصفح لا يدعم تشغيل الصوت
        </audio>

        <div class="time-display">
            <span id="currentTime">0:00</span>
            <span id="duration">0:00</span>
        </div>

        <div class="controls">
            <button class="btn btn-close" onclick="window.close()">إغلاق النافذة</button>
        </div>

        <div class="audio-info">
            <p><strong>ملاحظة:</strong> يمكنك إغلاق هذه النافذة في أي وقت</p>
            <p>سيستمر الصوت في العمل حتى تغلق النافذة</p>
        </div>
    </div>

    <script>
        const audio = document.getElementById('audioPlayer');
        const playPauseBtn = document.getElementById('playPauseBtn');
        const waveAnimation = document.getElementById('waveAnimation');
        const currentTimeEl = document.getElementById('currentTime');
        const durationEl = document.getElementById('duration');

        // تشغيل/إيقاف
        playPauseBtn.addEventListener('click', function() {
            if (audio.paused) {
                audio.play();
                playPauseBtn.textContent = '⏸';
                waveAnimation.style.opacity = '1';
            } else {
                audio.pause();
                playPauseBtn.textContent = '▶';
                waveAnimation.style.opacity = '0.3';
            }
        });

        // تحديث الوقت
        audio.addEventListener('timeupdate', function() {
            const current = formatTime(audio.currentTime);
            currentTimeEl.textContent = current;
        });

        // عند تحميل البيانات
        audio.addEventListener('loadedmetadata', function() {
            const duration = formatTime(audio.duration);
            durationEl.textContent = duration;
        });

        // عند انتهاء التشغيل
        audio.addEventListener('ended', function() {
            playPauseBtn.textContent = '▶';
            waveAnimation.style.opacity = '0.3';
        });

        // تنسيق الوقت
        function formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return mins + ':' + (secs < 10 ? '0' : '') + secs;
        }

        // إخفاء الموجات في البداية
        waveAnimation.style.opacity = '0.3';
    </script>
</body>
</html>
