<?php
/**
 * PDF Viewer Template
 *
 * @package Digital_Books_Theme
 * Variables available: $book, $pdf_url, $audio_pages, $current_page
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="dbp-pdf-viewer-container">
    <div class="dbp-viewer-header">
        <div class="dbp-book-info">
            <h2><?php echo esc_html($book->post_title); ?></h2>
            <?php if ($book->post_excerpt): ?>
                <p><?php echo esc_html($book->post_excerpt); ?></p>
            <?php endif; ?>
        </div>

        <div class="dbp-viewer-controls">
            <button id="prev-page" class="dbp-control-btn" title="الصفحة السابقة">
                <span>←</span>
            </button>

            <div class="dbp-page-info">
                <span id="current-page"><?php echo $current_page; ?></span>
                <span>/</span>
                <span id="total-pages">0</span>
            </div>

            <button id="next-page" class="dbp-control-btn" title="الصفحة التالية">
                <span>→</span>
            </button>

            <div class="dbp-zoom-controls">
                <button id="zoom-out" class="dbp-control-btn" title="تصغير">-</button>
                <span id="zoom-level">100%</span>
                <button id="zoom-in" class="dbp-control-btn" title="تكبير">+</button>
            </div>

            <button id="fullscreen-btn" class="dbp-control-btn" title="ملء الشاشة">
                <span>⛶</span>
            </button>

            <button id="save-progress" class="dbp-control-btn dbp-save-btn" title="حفظ التقدم">
                💾 حفظ
            </button>
        </div>
    </div>

    <!-- Audio Player -->
    <div id="audio-player" class="dbp-audio-player" style="display: none;">
        <div class="audio-info">
            <span>🎧 تشغيل الصوت للصفحة <span id="audio-page-num"></span></span>
        </div>
        <audio id="page-audio" controls>
            <source id="audio-source" src="" type="audio/mpeg">
            متصفحك لا يدعم تشغيل الصوت
        </audio>
    </div>

    <div class="dbp-pdf-canvas-container" id="pdf-container">
        <canvas id="pdf-canvas"></canvas>
        <div id="text-layer" class="textLayer"></div>
    </div>

    <div class="dbp-keyboard-shortcuts">
        <small>⌨️ اختصارات: ← → للتنقل | + - للتكبير/التصغير | F للملء الشاشة | S للحفظ</small>
    </div>
</div>

<style>
.dbp-pdf-viewer-container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

.dbp-viewer-header {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.dbp-book-info h2 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 24px;
}

.dbp-book-info p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.dbp-viewer-controls {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.dbp-control-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
}

.dbp-control-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.5);
}

.dbp-control-btn:active {
    transform: translateY(0);
}

.dbp-page-info {
    background: #f0f0f0;
    padding: 8px 15px;
    border-radius: 8px;
    font-weight: bold;
    color: #333;
}

.dbp-zoom-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f0f0f0;
    padding: 5px 10px;
    border-radius: 8px;
}

#zoom-level {
    font-weight: bold;
    color: #333;
    min-width: 50px;
    text-align: center;
}

.dbp-save-btn {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.dbp-audio-player {
    background: white;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.audio-info {
    font-weight: bold;
    color: #333;
}

#page-audio {
    flex: 1;
    min-width: 300px;
}

.dbp-pdf-canvas-container {
    position: relative;
    background: white;
    border-radius: 10px;
    overflow: auto;
    max-height: 800px;
    display: flex;
    justify-content: center;
    padding: 20px;
}

#pdf-canvas {
    display: block;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.textLayer {
    position: absolute;
    left: 0;
    top: 0;
    right: 0;
    bottom: 0;
    overflow: hidden;
    opacity: 0.2;
    line-height: 1.0;
}

.textLayer > span {
    color: transparent;
    position: absolute;
    white-space: pre;
    cursor: text;
    transform-origin: 0% 0%;
}

.textLayer ::selection {
    background: rgba(102, 126, 234, 0.3);
}

.dbp-keyboard-shortcuts {
    text-align: center;
    margin-top: 15px;
    color: white;
    opacity: 0.8;
}

@media (max-width: 768px) {
    .dbp-viewer-controls {
        justify-content: center;
    }

    .dbp-pdf-canvas-container {
        max-height: 500px;
    }
}
</style>

<script>
(function($) {
    'use strict';

    // Configuration
    const pdfUrl = '<?php echo esc_js($pdf_url); ?>';
    const bookId = <?php echo intval($book_id); ?>;
    let currentPageNum = <?php echo intval($current_page); ?>;
    let pdfDoc = null;
    let pageRendering = false;
    let pageNumPending = null;
    let scale = 1.0;
    const MIN_SCALE = 0.5;
    const MAX_SCALE = 3.0;
    const SCALE_STEP = 0.2;

    // Audio pages mapping
    const audioPages = <?php echo json_encode($audio_pages); ?>;

    // PDF.js configuration
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    // Initialize
    $(document).ready(function() {
        loadPDF();
        attachEventListeners();
    });

    function loadPDF() {
        const loadingTask = pdfjsLib.getDocument(pdfUrl);

        loadingTask.promise.then(function(pdf) {
            pdfDoc = pdf;
            $('#total-pages').text(pdf.numPages);
            renderPage(currentPageNum);
        }).catch(function(error) {
            Swal.fire({
                icon: 'error',
                title: 'خطأ في تحميل الكتاب',
                text: 'حدث خطأ أثناء تحميل ملف PDF',
                confirmButtonText: 'حسناً'
            });
            console.error('Error loading PDF:', error);
        });
    }

    function renderPage(num) {
        pageRendering = true;

        pdfDoc.getPage(num).then(function(page) {
            const canvas = document.getElementById('pdf-canvas');
            const ctx = canvas.getContext('2d');
            const viewport = page.getViewport({ scale: scale });

            canvas.height = viewport.height;
            canvas.width = viewport.width;

            const renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };

            const renderTask = page.render(renderContext);

            // Render text layer for text selection
            page.getTextContent().then(function(textContent) {
                const textLayerDiv = document.getElementById('text-layer');
                textLayerDiv.innerHTML = '';
                textLayerDiv.style.height = viewport.height + 'px';
                textLayerDiv.style.width = viewport.width + 'px';

                pdfjsLib.renderTextLayer({
                    textContent: textContent,
                    container: textLayerDiv,
                    viewport: viewport,
                    textDivs: []
                });
            });

            // Handle annotations (links)
            page.getAnnotations().then(function(annotations) {
                annotations.forEach(function(annotation) {
                    if (annotation.subtype === 'Link' && annotation.url) {
                        const link = document.createElement('a');
                        link.href = annotation.url;
                        link.target = '_blank';
                        link.rel = 'noopener noreferrer';

                        const rect = annotation.rect;
                        const [x1, y1, x2, y2] = rect;

                        link.style.position = 'absolute';
                        link.style.left = x1 + 'px';
                        link.style.top = (viewport.height - y2) + 'px';
                        link.style.width = (x2 - x1) + 'px';
                        link.style.height = (y2 - y1) + 'px';

                        document.getElementById('text-layer').appendChild(link);
                    }
                });
            });

            renderTask.promise.then(function() {
                pageRendering = false;
                if (pageNumPending !== null) {
                    renderPage(pageNumPending);
                    pageNumPending = null;
                }

                // Update current page display
                $('#current-page').text(num);
                currentPageNum = num;

                // Handle audio for this page
                handlePageAudio(num);
            });
        });
    }

    function queueRenderPage(num) {
        if (pageRendering) {
            pageNumPending = num;
        } else {
            renderPage(num);
        }
    }

    function handlePageAudio(pageNum) {
        if (audioPages[pageNum]) {
            const audio = audioPages[pageNum];
            const audioUrl = '<?php echo wp_upload_dir()["baseurl"]; ?>/dbt-audio/' + audio.unique_filename;

            $('#audio-source').attr('src', audioUrl);
            $('#page-audio')[0].load();
            $('#audio-page-num').text(pageNum);
            $('#audio-player').show();
        } else {
            $('#audio-player').hide();
        }
    }

    function onPrevPage() {
        if (currentPageNum <= 1) {
            return;
        }
        currentPageNum--;
        queueRenderPage(currentPageNum);
    }

    function onNextPage() {
        if (currentPageNum >= pdfDoc.numPages) {
            return;
        }
        currentPageNum++;
        queueRenderPage(currentPageNum);
    }

    function zoomIn() {
        if (scale < MAX_SCALE) {
            scale += SCALE_STEP;
            scale = Math.min(scale, MAX_SCALE);
            updateZoom();
        }
    }

    function zoomOut() {
        if (scale > MIN_SCALE) {
            scale -= SCALE_STEP;
            scale = Math.max(scale, MIN_SCALE);
            updateZoom();
        }
    }

    function updateZoom() {
        $('#zoom-level').text(Math.round(scale * 100) + '%');
        queueRenderPage(currentPageNum);
    }

    function toggleFullscreen() {
        const container = document.querySelector('.dbp-pdf-viewer-container');

        if (!document.fullscreenElement) {
            container.requestFullscreen().catch(err => {
                console.error('Error attempting to enable fullscreen:', err);
            });
        } else {
            document.exitFullscreen();
        }
    }

    function saveProgress() {
        $.ajax({
            url: dbtData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dbt_save_progress',
                nonce: dbtData.nonce,
                book_id: bookId,
                current_page: currentPageNum
            },
            success: function(response) {
                if (response.success) {
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
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: 'حدث خطأ أثناء حفظ التقدم',
                    confirmButtonText: 'حسناً'
                });
            }
        });
    }

    function attachEventListeners() {
        // Navigation buttons
        $('#prev-page').on('click', onPrevPage);
        $('#next-page').on('click', onNextPage);

        // Zoom buttons
        $('#zoom-in').on('click', zoomIn);
        $('#zoom-out').on('click', zoomOut);

        // Fullscreen button
        $('#fullscreen-btn').on('click', toggleFullscreen);

        // Save progress button
        $('#save-progress').on('click', saveProgress);

        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            switch(e.key) {
                case 'ArrowLeft':
                    onNextPage(); // RTL: left arrow = next
                    break;
                case 'ArrowRight':
                    onPrevPage(); // RTL: right arrow = previous
                    break;
                case '+':
                case '=':
                    zoomIn();
                    break;
                case '-':
                case '_':
                    zoomOut();
                    break;
                case 'f':
                case 'F':
                    toggleFullscreen();
                    break;
                case 's':
                case 'S':
                    e.preventDefault();
                    saveProgress();
                    break;
            }
        });
    }

})(jQuery);
</script>
