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

// التحقق من أن الكتاب مدفوع والمستخدم لم يشتريه
if ($book['is_paid'] == 1) {
    $purchaseCheck = $pdo->prepare("SELECT id FROM purchases WHERE user_id = ? AND book_id = ?");
    $purchaseCheck->execute([$user['id'], $bookId]);
    $hasPurchased = $purchaseCheck->fetch();

    if (!$hasPurchased) {
        redirect('purchase-book.php?id=' . $bookId);
    }
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

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- PDF.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf_viewer.min.css">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1a1a2e;
            overflow: hidden;
        }

        .viewer-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .toolbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            z-index: 100;
        }

        .toolbar h1 {
            font-size: 20px;
            flex: 1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .toolbar-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .btn-primary { background: #3498db; color: white; }
        .btn-primary:hover { background: #2980b9; transform: translateY(-2px); }

        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; transform: translateY(-2px); }

        .btn-success { background: #2ecc71; color: white; }
        .btn-success:hover { background: #27ae60; transform: translateY(-2px); }

        .btn-info { background: #f39c12; color: white; }
        .btn-info:hover { background: #e67e22; transform: translateY(-2px); }

        .pdf-viewer-wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            background: #16213e;
            position: relative;
        }

        .pdf-container {
            width: 100%;
            height: 100%;
            overflow: auto;
            display: flex;
            justify-content: center;
            padding: 20px;
        }

        .pdf-page-wrapper {
            position: relative;
            margin: 0 auto;
            box-shadow: 0 8px 40px rgba(0,0,0,0.5);
        }

        .pdf-canvas {
            display: block;
            background: white;
        }

        .text-layer {
            position: absolute;
            left: 0;
            top: 0;
            right: 0;
            bottom: 0;
            overflow: hidden;
            opacity: 0.2;
            line-height: 1.0;
        }

        .text-layer > span {
            color: transparent;
            position: absolute;
            white-space: pre;
            cursor: text;
            transform-origin: 0% 0%;
        }

        .text-layer ::selection {
            background: rgba(0, 123, 255, 0.3);
        }

        .controls {
            background: linear-gradient(135deg, #0f3460 0%, #16213e 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.3);
            flex-wrap: wrap;
        }

        .page-info {
            font-size: 16px;
            min-width: 200px;
            text-align: center;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        input[type="number"] {
            width: 70px;
            padding: 8px;
            text-align: center;
            border: 2px solid #3498db;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
        }

        .zoom-controls {
            display: flex;
            gap: 10px;
            align-items: center;
            background: rgba(52, 152, 219, 0.1);
            padding: 8px 15px;
            border-radius: 8px;
        }

        .zoom-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
        }

        .audio-indicator {
            position: fixed;
            bottom: 120px;
            left: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.4);
            animation: pulse 2s infinite;
            cursor: pointer;
            z-index: 1000;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 8px 30px rgba(102, 126, 234, 0.4); }
            50% { transform: scale(1.05); box-shadow: 0 8px 40px rgba(102, 126, 234, 0.6); }
        }

        .loading {
            text-align: center;
            color: white;
            font-size: 20px;
            padding: 40px;
        }

        .loading::after {
            content: '...';
            animation: dots 1.5s infinite;
        }

        @keyframes dots {
            0%, 20% { content: '.'; }
            40% { content: '..'; }
            60%, 100% { content: '...'; }
        }

        .progress-indicator {
            position: fixed;
            top: 80px;
            left: 30px;
            background: rgba(22, 33, 62, 0.95);
            color: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.4);
            backdrop-filter: blur(10px);
            z-index: 90;
        }

        .progress-bar-container {
            width: 220px;
            height: 12px;
            background: #0f3460;
            border-radius: 6px;
            overflow: hidden;
            margin-top: 12px;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.3);
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.5s ease;
            box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
        }

        .fullscreen-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 100;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .toolbar h1 { font-size: 16px; }
            .controls { padding: 10px; gap: 10px; }
            .progress-indicator {
                left: 10px;
                right: 10px;
                top: 70px;
            }
            .progress-bar-container { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="viewer-container">
        <div class="toolbar">
            <h1>📖 <?php echo htmlspecialchars($book['title']); ?></h1>
            <div class="toolbar-controls">
                <button class="btn btn-info" onclick="toggleFullscreen()">🖥️ ملء الشاشة</button>
                <button class="btn btn-success" onclick="saveProgress()">💾 حفظ التقدم</button>
                <a href="dashboard.php" class="btn btn-danger" style="text-decoration: none;">✖ إغلاق</a>
            </div>
        </div>

        <div class="progress-indicator">
            <div><strong>📊 تقدم القراءة</strong></div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill" id="progressBarFill" style="width: 0%"></div>
            </div>
            <div id="progressText" style="margin-top: 8px; font-size: 14px;">0%</div>
        </div>

        <div class="pdf-viewer-wrapper">
            <div class="pdf-container" id="pdfContainer">
                <div id="loading" class="loading">جاري تحميل الكتاب</div>
            </div>
        </div>

        <div class="controls">
            <button class="btn btn-primary" onclick="prevPage()">⏮ السابق</button>
            <div class="page-info">
                صفحة <input type="number" id="pageInput" min="1" value="<?php echo $currentPage; ?>" onchange="goToPage()">
                من <span id="totalPages">-</span>
            </div>
            <button class="btn btn-primary" onclick="nextPage()">التالي ⏭</button>

            <div class="zoom-controls">
                <button class="btn btn-primary zoom-btn" onclick="zoomOut()">-</button>
                <span id="zoomLevel" style="min-width: 60px; text-align: center; font-weight: 600;">100%</span>
                <button class="btn btn-primary zoom-btn" onclick="zoomIn()">+</button>
                <button class="btn btn-info" onclick="resetZoom()">🔄</button>
            </div>
        </div>
    </div>

    <script>
        const bookId = <?php echo $bookId; ?>;
        const userId = <?php echo $user['id']; ?>;
        const pdfUrl = '../<?php echo $book['file_path']; ?>';
        const audioPages = <?php echo json_encode($audioPages); ?>;

        // إعداد PDF.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        let pdfDoc = null;
        let pageNum = <?php echo $currentPage; ?>;
        let pageRendering = false;
        let pageNumPending = null;
        let scale = 1.5;
        const minScale = 0.5;
        const maxScale = 3.0;

        const container = document.getElementById('pdfContainer');
        const loading = document.getElementById('loading');

        // تحميل PDF
        pdfjsLib.getDocument(pdfUrl).promise.then(function(pdf) {
            pdfDoc = pdf;
            document.getElementById('totalPages').textContent = pdf.numPages;
            loading.style.display = 'none';
            renderPage(pageNum);
            updateProgress();
        }).catch(function(error) {
            Swal.fire({
                icon: 'error',
                title: 'خطأ في التحميل',
                text: 'فشل تحميل الكتاب: ' + error.message,
                confirmButtonText: 'موافق'
            });
        });

        // عرض صفحة مع text layer
        function renderPage(num) {
            pageRendering = true;

            pdfDoc.getPage(num).then(function(page) {
                const viewport = page.getViewport({scale: scale});

                // إنشاء wrapper للصفحة
                const pageWrapper = document.createElement('div');
                pageWrapper.className = 'pdf-page-wrapper';
                pageWrapper.style.width = viewport.width + 'px';
                pageWrapper.style.height = viewport.height + 'px';

                // إنشاء canvas
                const canvas = document.createElement('canvas');
                canvas.className = 'pdf-canvas';
                const ctx = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                // إنشاء text layer
                const textLayerDiv = document.createElement('div');
                textLayerDiv.className = 'text-layer';
                textLayerDiv.style.width = viewport.width + 'px';
                textLayerDiv.style.height = viewport.height + 'px';

                pageWrapper.appendChild(canvas);
                pageWrapper.appendChild(textLayerDiv);

                // مسح المحتوى السابق
                container.innerHTML = '';
                container.appendChild(pageWrapper);

                // رندر الصفحة
                const renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };

                const renderTask = page.render(renderContext);

                renderTask.promise.then(function() {
                    // رندر text layer
                    page.getTextContent().then(function(textContent) {
                        pdfjsLib.renderTextLayer({
                            textContent: textContent,
                            container: textLayerDiv,
                            viewport: viewport,
                            textDivs: []
                        });
                    });

                    // جعل الروابط تفتح في تاب جديد
                    page.getAnnotations().then(function(annotations) {
                        annotations.forEach(function(annotation) {
                            if (annotation.subtype === 'Link' && annotation.url) {
                                const link = document.createElement('a');
                                link.href = annotation.url;
                                link.target = '_blank';  // فتح في تاب جديد
                                link.rel = 'noopener noreferrer';
                                link.style.position = 'absolute';
                                link.style.left = (annotation.rect[0] * scale) + 'px';
                                link.style.top = (viewport.height - annotation.rect[3] * scale) + 'px';
                                link.style.width = ((annotation.rect[2] - annotation.rect[0]) * scale) + 'px';
                                link.style.height = ((annotation.rect[3] - annotation.rect[1]) * scale) + 'px';
                                link.style.opacity = '0';
                                pageWrapper.appendChild(link);
                            }
                        });
                    });

                    pageRendering = false;
                    if (pageNumPending !== null) {
                        renderPage(pageNumPending);
                        pageNumPending = null;
                    }

                    checkAudioForPage(num);
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
            if (pageNum <= 1) {
                Swal.fire({
                    icon: 'info',
                    title: 'أول صفحة',
                    text: 'أنت في أول صفحة من الكتاب',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });
                return;
            }
            pageNum--;
            queueRenderPage(pageNum);
        }

        function nextPage() {
            if (!pdfDoc || pageNum >= pdfDoc.numPages) {
                Swal.fire({
                    icon: 'info',
                    title: 'آخر صفحة',
                    text: 'أنت في آخر صفحة من الكتاب',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });
                return;
            }
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
                Swal.fire({
                    icon: 'warning',
                    title: 'رقم صفحة غير صحيح',
                    text: `الرجاء إدخال رقم بين 1 و ${pdfDoc.numPages}`,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });
            }
        }

        function zoomIn() {
            if (scale >= maxScale) {
                Swal.fire({
                    icon: 'info',
                    text: 'الحد الأقصى للتكبير',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1500
                });
                return;
            }
            scale += 0.25;
            updateZoomDisplay();
            queueRenderPage(pageNum);
        }

        function zoomOut() {
            if (scale <= minScale) {
                Swal.fire({
                    icon: 'info',
                    text: 'الحد الأدنى للتصغير',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1500
                });
                return;
            }
            scale -= 0.25;
            updateZoomDisplay();
            queueRenderPage(pageNum);
        }

        function resetZoom() {
            scale = 1.5;
            updateZoomDisplay();
            queueRenderPage(pageNum);
        }

        function updateZoomDisplay() {
            document.getElementById('zoomLevel').textContent = Math.round(scale * 100) + '%';
        }

        function checkAudioForPage(page) {
            const existingIndicator = document.querySelector('.audio-indicator');
            if (existingIndicator) {
                existingIndicator.remove();
            }

            if (audioPages[page]) {
                const indicator = document.createElement('div');
                indicator.className = 'audio-indicator';
                indicator.innerHTML = '<span style="font-size: 24px;">🎵</span> يوجد ملف صوتي - اضغط للتشغيل';
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
                    Swal.fire({
                        icon: 'success',
                        title: 'تم الحفظ!',
                        text: 'تم حفظ تقدمك في القراءة بنجاح',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: 'حدث خطأ في حفظ التقدم',
                        confirmButtonText: 'موافق'
                    });
                }
            }).catch(error => {
                console.error('Error:', error);
            });
        }

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        }

        // حفظ تلقائي كل 30 ثانية
        setInterval(function() {
            if (pdfDoc) {
                const formData = new FormData();
                formData.append('book_id', bookId);
                formData.append('current_page', pageNum);
                formData.append('total_pages', pdfDoc.numPages);

                fetch('save-progress.php', {
                    method: 'POST',
                    body: formData
                });
            }
        }, 30000);

        // حفظ التقدم عند إغلاق الصفحة
        window.addEventListener('beforeunload', function() {
            if (pdfDoc) {
                const formData = new FormData();
                formData.append('book_id', bookId);
                formData.append('current_page', pageNum);
                formData.append('total_pages', pdfDoc.numPages);

                navigator.sendBeacon('save-progress.php', formData);
            }
        });

        // التحكم بالكيبورد
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowRight' || e.key === 'ArrowUp') {
                e.preventDefault();
                prevPage();
            } else if (e.key === 'ArrowLeft' || e.key === 'ArrowDown') {
                e.preventDefault();
                nextPage();
            } else if (e.key === '+' || e.key === '=') {
                e.preventDefault();
                zoomIn();
            } else if (e.key === '-') {
                e.preventDefault();
                zoomOut();
            } else if (e.key === '0') {
                e.preventDefault();
                resetZoom();
            } else if (e.key === 'f' || e.key === 'F') {
                e.preventDefault();
                toggleFullscreen();
            }
        });

        // تحديث الزوم عند البداية
        updateZoomDisplay();
    </script>
</body>
</html>
