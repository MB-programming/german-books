<?php
require_once '../config.php';
require_once '../auth.php';

$auth = new Auth($pdo);

if (!$auth->checkSession()) {
    redirect('../login.php');
}

$user = $auth->getCurrentUser();
$bookId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// الحصول على بيانات الكتاب
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book) {
    redirect('dashboard.php');
}

// الحصول على الملفات الصوتية
$audioStmt = $pdo->prepare("SELECT * FROM audio_files WHERE book_id = ? ORDER BY page_number");
$audioStmt->execute([$bookId]);
$audioFiles = $audioStmt->fetchAll();

// إنشاء مصفوفة للصفحات التي تحتوي على صوتيات
$audioPages = [];
foreach ($audioFiles as $audio) {
    $audioPages[$audio['page_number']] = $audio;
}

// الحصول على التقدم في القراءة
$progressStmt = $pdo->prepare("SELECT * FROM reading_progress WHERE user_id = ? AND book_id = ?");
$progressStmt->execute([$user['id'], $bookId]);
$progress = $progressStmt->fetch();

$currentPage = $progress ? $progress['current_page'] : 1;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - <?php echo SITE_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #2c3e50; overflow: hidden; }
        .viewer-container { display: flex; flex-direction: column; height: 100vh; }
        .toolbar { background: #34495e; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.3); }
        .toolbar h1 { font-size: 20px; flex: 1; }
        .toolbar-controls { display: flex; gap: 10px; align-items: center; }
        .btn { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; transition: all 0.3s; }
        .btn-primary { background: #3498db; color: white; }
        .btn-primary:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-success:hover { background: #27ae60; }
        .pdf-container { flex: 1; display: flex; justify-content: center; align-items: center; overflow: auto; padding: 20px; }
        .pdf-canvas { max-width: 100%; max-height: 100%; box-shadow: 0 5px 30px rgba(0,0,0,0.5); background: white; }
        .controls { background: #34495e; color: white; padding: 15px 20px; display: flex; justify-content: center; align-items: center; gap: 20px; box-shadow: 0 -2px 10px rgba(0,0,0,0.3); }
        .page-info { font-size: 16px; min-width: 150px; text-align: center; }
        input[type="number"] { width: 60px; padding: 5px; text-align: center; border: none; border-radius: 4px; }
        .zoom-controls { display: flex; gap: 10px; align-items: center; }
        .audio-indicator { position: fixed; bottom: 100px; left: 20px; background: #3498db; color: white; padding: 12px 20px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); animation: pulse 2s infinite; cursor: pointer; z-index: 1000; }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        .loading { text-align: center; color: white; font-size: 18px; }
        .progress-indicator { position: fixed; top: 70px; left: 20px; background: rgba(52, 73, 94, 0.9); color: white; padding: 15px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        .progress-bar-container { width: 200px; height: 10px; background: #2c3e50; border-radius: 5px; overflow: hidden; margin-top: 10px; }
        .progress-bar-fill { height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); transition: width 0.3s; }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
</head>
<body>
    <div class="viewer-container">
        <div class="toolbar">
            <h1><?php echo htmlspecialchars($book['title']); ?></h1>
            <div class="toolbar-controls">
                <button class="btn btn-success" onclick="saveProgress()">حفظ التقدم</button>
                <a href="dashboard.php" class="btn btn-danger">إغلاق</a>
            </div>
        </div>

        <div class="progress-indicator">
            <div><strong>تقدم القراءة</strong></div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill" id="progressBarFill" style="width: 0%"></div>
            </div>
            <div id="progressText" style="margin-top: 5px; font-size: 14px;">0%</div>
        </div>

        <div class="pdf-container">
            <canvas id="pdfCanvas" class="pdf-canvas"></canvas>
            <div id="loading" class="loading">جاري تحميل الكتاب...</div>
        </div>

        <div class="controls">
            <button class="btn btn-primary" onclick="prevPage()">← السابق</button>
            <div class="page-info">
                صفحة <input type="number" id="pageInput" min="1" value="<?php echo $currentPage; ?>" onchange="goToPage()">
                من <span id="totalPages">-</span>
            </div>
            <button class="btn btn-primary" onclick="nextPage()">التالي →</button>
            <div class="zoom-controls">
                <button class="btn btn-primary" onclick="zoomOut()">-</button>
                <span id="zoomLevel">100%</span>
                <button class="btn btn-primary" onclick="zoomIn()">+</button>
            </div>
        </div>
    </div>

    <script>
        const bookId = <?php echo $bookId; ?>;
        const userId = <?php echo $user['id']; ?>;
        const pdfUrl = '../<?php echo $book['file_path']; ?>';
        const audioPages = <?php echo json_encode($audioPages); ?>;

        let pdfDoc = null;
        let pageNum = <?php echo $currentPage; ?>;
        let pageRendering = false;
        let pageNumPending = null;
        let scale = 1.5;

        const canvas = document.getElementById('pdfCanvas');
        const ctx = canvas.getContext('2d');
        const loading = document.getElementById('loading');

        // تحميل PDF
        pdfjsLib.getDocument(pdfUrl).promise.then(function(pdf) {
            pdfDoc = pdf;
            document.getElementById('totalPages').textContent = pdf.numPages;
            loading.style.display = 'none';
            renderPage(pageNum);
            updateProgress();
        }).catch(function(error) {
            loading.textContent = 'فشل تحميل الكتاب: ' + error.message;
        });

        // عرض صفحة
        function renderPage(num) {
            pageRendering = true;

            pdfDoc.getPage(num).then(function(page) {
                const viewport = page.getViewport({scale: scale});
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                const renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };

                const renderTask = page.render(renderContext);

                renderTask.promise.then(function() {
                    pageRendering = false;
                    if (pageNumPending !== null) {
                        renderPage(pageNumPending);
                        pageNumPending = null;
                    }

                    // التحقق من وجود صوتيات في هذه الصفحة
                    checkAudioForPage(num);

                    // تحديث التقدم
                    updateProgress();
                });
            });

            document.getElementById('pageInput').value = num;
        }

        function queueRenderPage(num) {
            if (pageRendering) {
                pageNumPending = num;
            } else {
                renderPage(num);
            }
        }

        function prevPage() {
            if (pageNum <= 1) return;
            pageNum--;
            queueRenderPage(pageNum);
        }

        function nextPage() {
            if (!pdfDoc || pageNum >= pdfDoc.numPages) return;
            pageNum++;
            queueRenderPage(pageNum);
        }

        function goToPage() {
            const input = document.getElementById('pageInput');
            const page = parseInt(input.value);
            if (page >= 1 && page <= pdfDoc.numPages) {
                pageNum = page;
                queueRenderPage(pageNum);
            } else {
                input.value = pageNum;
            }
        }

        function zoomIn() {
            scale += 0.25;
            document.getElementById('zoomLevel').textContent = Math.round(scale * 100) + '%';
            queueRenderPage(pageNum);
        }

        function zoomOut() {
            if (scale <= 0.5) return;
            scale -= 0.25;
            document.getElementById('zoomLevel').textContent = Math.round(scale * 100) + '%';
            queueRenderPage(pageNum);
        }

        function checkAudioForPage(page) {
            // إزالة المؤشر الصوتي الحالي
            const existingIndicator = document.querySelector('.audio-indicator');
            if (existingIndicator) {
                existingIndicator.remove();
            }

            // إضافة مؤشر جديد إذا كانت الصفحة تحتوي على صوت
            if (audioPages[page]) {
                const indicator = document.createElement('div');
                indicator.className = 'audio-indicator';
                indicator.innerHTML = '🎵 يوجد ملف صوتي - اضغط للتشغيل';
                indicator.onclick = function() {
                    const qrCode = audioPages[page].qr_code;
                    window.open('../play-audio.php?qr=' + qrCode, '_blank', 'width=600,height=600');
                };
                document.body.appendChild(indicator);
            }
        }

        function updateProgress() {
            if (!pdfDoc) return;

            const progress = (pageNum / pdfDoc.numPages) * 100;
            document.getElementById('progressBarFill').style.width = progress + '%';
            document.getElementById('progressText').textContent = Math.round(progress) + '%';
        }

        function saveProgress() {
            if (!pdfDoc) return;

            const formData = new FormData();
            formData.append('book_id', bookId);
            formData.append('current_page', pageNum);
            formData.append('total_pages', pdfDoc.numPages);

            fetch('save-progress.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم حفظ تقدمك بنجاح!');
                } else {
                    alert('حدث خطأ في حفظ التقدم');
                }
            });
        }

        // حفظ تلقائي كل 30 ثانية
        setInterval(function() {
            saveProgress();
        }, 30000);

        // حفظ التقدم عند إغلاق الصفحة
        window.addEventListener('beforeunload', function() {
            saveProgress();
        });

        // التحكم بالكيبورد
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowRight' || e.key === 'ArrowUp') {
                prevPage();
            } else if (e.key === 'ArrowLeft' || e.key === 'ArrowDown') {
                nextPage();
            }
        });
    </script>
</body>
</html>
