<?php
require_once '../config.php';
require_once '../auth.php';

$auth = new Auth($pdo);

if (!$auth->checkSession()) {
    redirect('../login.php');
}

$user = $auth->getCurrentUser();

// الحصول على جميع الكتب المتاحة
$stmt = $pdo->prepare("SELECT b.*, u.username,
                       (SELECT COUNT(*) FROM audio_files WHERE book_id = b.id) as audio_count,
                       (SELECT id FROM saved_books WHERE user_id = ? AND book_id = b.id) as is_saved,
                       (SELECT id FROM purchases WHERE user_id = ? AND book_id = b.id) as is_purchased,
                       (SELECT id FROM purchase_requests WHERE user_id = ? AND book_id = b.id AND status = 'pending') as has_pending_request,
                       rp.current_page, rp.total_pages, rp.progress_percentage
                       FROM books b
                       JOIN users u ON b.uploaded_by = u.id
                       LEFT JOIN reading_progress rp ON rp.book_id = b.id AND rp.user_id = ?
                       ORDER BY b.upload_date DESC");
$stmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
$allBooks = $stmt->fetchAll();

// الكتب المحفوظة
$savedStmt = $pdo->prepare("SELECT b.*, u.username,
                            rp.current_page, rp.total_pages, rp.progress_percentage, rp.last_read
                            FROM saved_books sb
                            JOIN books b ON sb.book_id = b.id
                            JOIN users u ON b.uploaded_by = u.id
                            LEFT JOIN reading_progress rp ON rp.book_id = b.id AND rp.user_id = ?
                            WHERE sb.user_id = ?
                            ORDER BY sb.saved_at DESC");
$savedStmt->execute([$user['id'], $user['id']]);
$savedBooks = $savedStmt->fetchAll();

// آخر الكتب التي تمت قراءتها
$recentStmt = $pdo->prepare("SELECT b.*, rp.current_page, rp.total_pages, rp.progress_percentage, rp.last_read
                             FROM reading_progress rp
                             JOIN books b ON rp.book_id = b.id
                             WHERE rp.user_id = ?
                             ORDER BY rp.last_read DESC
                             LIMIT 5");
$recentStmt->execute([$user['id']]);
$recentBooks = $recentStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم القارئ - <?php echo SITE_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
        .sidebar h2 { margin-bottom: 30px; font-size: 24px; text-align: center; }
        .sidebar nav a { display: block; padding: 12px 15px; color: white; text-decoration: none; margin-bottom: 10px; border-radius: 8px; transition: background 0.3s; }
        .sidebar nav a:hover, .sidebar nav a.active { background: rgba(255,255,255,0.2); }
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .header { background: white; padding: 20px 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #333; }
        .logout-btn { background: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; }
        .section { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .section h2 { color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #eee; }
        .books-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
        .book-card { background: #f8f9fa; padding: 20px; border-radius: 12px; transition: all 0.3s; border: 2px solid transparent; }
        .book-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.1); border-color: #667eea; }
        .book-card h3 { color: #333; margin-bottom: 10px; font-size: 18px; }
        .book-card p { color: #666; font-size: 14px; margin: 5px 0; }
        .progress-bar { width: 100%; height: 8px; background: #ddd; border-radius: 4px; margin: 10px 0; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 4px; transition: width 0.3s; }
        .book-actions { display: flex; gap: 10px; margin-top: 15px; }
        .btn { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; transition: all 0.3s; text-align: center; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-success:hover { background: #27ae60; }
        .btn-outline { background: white; color: #667eea; border: 2px solid #667eea; }
        .btn-outline:hover { background: #667eea; color: white; }
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; display: inline-block; margin-left: 5px; }
        .badge-audio { background: #3498db; color: white; }
        .tabs { display: flex; gap: 20px; margin-bottom: 20px; border-bottom: 2px solid #eee; }
        .tab { padding: 10px 20px; cursor: pointer; border: none; background: none; font-size: 16px; color: #666; border-bottom: 3px solid transparent; transition: all 0.3s; }
        .tab.active { color: #667eea; border-bottom-color: #667eea; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h2>📚 منصة الكتب</h2>
            <nav>
                <a href="dashboard.php" class="active">الرئيسية</a>
                <a href="my-books.php">كتبي المحفوظة</a>
                <a href="browse.php">تصفح الكتب</a>
                <a href="reading-history.php">سجل القراءة</a>
                <a href="profile.php">الملف الشخصي</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <div>
                    <h1>مرحباً، <?php echo htmlspecialchars($user['username']); ?></h1>
                    <p style="color: #666; margin-top: 5px;">استمتع بقراءة الكتب الرقمية</p>
                </div>
                <div>
                    <a href="../logout.php" class="logout-btn">تسجيل الخروج</a>
                </div>
            </div>

            <?php if (count($recentBooks) > 0): ?>
                <div class="section">
                    <h2>آخر ما قرأت</h2>
                    <div class="books-grid">
                        <?php foreach ($recentBooks as $book): ?>
                            <div class="book-card">
                                <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                                <p>آخر قراءة: <?php echo date('Y-m-d H:i', strtotime($book['last_read'])); ?></p>
                                <p>الصفحة: <?php echo $book['current_page']; ?> من <?php echo $book['total_pages'] ?: '؟'; ?></p>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $book['progress_percentage']; ?>%"></div>
                                </div>
                                <p style="text-align: center; color: #667eea; font-weight: bold;"><?php echo round($book['progress_percentage'], 1); ?>%</p>
                                <div class="book-actions">
                                    <a href="view-book.php?id=<?php echo $book['id']; ?>" class="btn btn-primary" style="flex: 1;">متابعة القراءة</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="section">
                <div class="tabs">
                    <button class="tab active" onclick="switchTab('all')">جميع الكتب (<?php echo count($allBooks); ?>)</button>
                    <button class="tab" onclick="switchTab('saved')">الكتب المحفوظة (<?php echo count($savedBooks); ?>)</button>
                </div>

                <div id="all-books" class="tab-content active">
                    <div class="books-grid">
                        <?php foreach ($allBooks as $book): ?>
                            <div class="book-card">
                                <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                                <p>التصنيف: <?php echo htmlspecialchars($book['category'] ?: 'غير محدد'); ?></p>
                                <p>اللغة: <?php echo htmlspecialchars($book['language'] ?: 'غير محدد'); ?></p>

                                <?php if ($book['is_paid'] == 1): ?>
                                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 10px; border-radius: 8px; margin: 10px 0; font-weight: bold; text-align: center;">
                                        💰 <?php echo number_format($book['price'], 2); ?> جنيه
                                    </div>
                                <?php else: ?>
                                    <span class="badge" style="background: #2ecc71; color: white;">مجاني</span>
                                <?php endif; ?>

                                <?php if ($book['audio_count'] > 0): ?>
                                    <span class="badge badge-audio">🎵 <?php echo $book['audio_count']; ?> ملف صوتي</span>
                                <?php endif; ?>

                                <?php if ($book['progress_percentage'] > 0): ?>
                                    <div class="progress-bar" style="margin-top: 10px;">
                                        <div class="progress-fill" style="width: <?php echo $book['progress_percentage']; ?>%"></div>
                                    </div>
                                    <p style="text-align: center; color: #667eea; font-size: 12px;"><?php echo round($book['progress_percentage'], 1); ?>%</p>
                                <?php endif; ?>

                                <div class="book-actions">
                                    <?php if ($book['is_paid'] == 1 && !$book['is_purchased']): ?>
                                        <?php if ($book['has_pending_request']): ?>
                                            <button class="btn" style="background: #ffc107; color: #856404; flex: 1;" disabled>⏳ طلب معلق</button>
                                        <?php else: ?>
                                            <a href="purchase-book.php?id=<?php echo $book['id']; ?>" class="btn" style="background: #f39c12; color: white; flex: 1;">🛒 شراء الكتاب</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="view-book.php?id=<?php echo $book['id']; ?>" class="btn btn-primary">قراءة</a>
                                        <?php if ($book['is_saved']): ?>
                                            <a href="unsave-book.php?id=<?php echo $book['id']; ?>" class="btn btn-success">محفوظ ✓</a>
                                        <?php else: ?>
                                            <a href="save-book.php?id=<?php echo $book['id']; ?>" class="btn btn-outline">حفظ</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="saved-books" class="tab-content">
                    <?php if (count($savedBooks) > 0): ?>
                        <div class="books-grid">
                            <?php foreach ($savedBooks as $book): ?>
                                <div class="book-card">
                                    <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                                    <?php if ($book['progress_percentage'] > 0): ?>
                                        <p>التقدم: <?php echo $book['current_page']; ?> من <?php echo $book['total_pages'] ?: '؟'; ?></p>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $book['progress_percentage']; ?>%"></div>
                                        </div>
                                        <p style="text-align: center; color: #667eea; font-weight: bold;"><?php echo round($book['progress_percentage'], 1); ?>%</p>
                                    <?php else: ?>
                                        <p style="color: #999;">لم تبدأ القراءة بعد</p>
                                    <?php endif; ?>
                                    <div class="book-actions">
                                        <a href="view-book.php?id=<?php echo $book['id']; ?>" class="btn btn-primary" style="flex: 1;">قراءة</a>
                                        <a href="unsave-book.php?id=<?php echo $book['id']; ?>" class="btn btn-outline">إلغاء الحفظ</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; color: #666; padding: 40px;">لم تقم بحفظ أي كتب بعد</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));

            if (tab === 'all') {
                document.querySelectorAll('.tab')[0].classList.add('active');
                document.getElementById('all-books').classList.add('active');
            } else {
                document.querySelectorAll('.tab')[1].classList.add('active');
                document.getElementById('saved-books').classList.add('active');
            }
        }
    </script>
</body>
</html>
