<?php
require_once '../config.php';
require_once '../auth.php';

$auth = new Auth($pdo);

if (!$auth->checkSession() || !isAdmin()) {
    redirect('../login.php');
}

// الحصول على جميع الكتب
$stmt = $pdo->prepare("SELECT b.*, u.username,
                       (SELECT COUNT(*) FROM audio_files WHERE book_id = b.id) as audio_count
                       FROM books b
                       JOIN users u ON b.uploaded_by = u.id
                       ORDER BY b.upload_date DESC");
$stmt->execute();
$books = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الكتب - <?php echo SITE_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
        .sidebar h2 { margin-bottom: 30px; font-size: 24px; text-align: center; }
        .sidebar nav a { display: block; padding: 12px 15px; color: white; text-decoration: none; margin-bottom: 10px; border-radius: 8px; transition: background 0.3s; }
        .sidebar nav a:hover, .sidebar nav a.active { background: rgba(255,255,255,0.2); }
        .main-content { flex: 1; padding: 30px; }
        .header { background: white; padding: 20px 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .header h1 { color: #333; }
        .section { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        table th { background: #f8f9fa; padding: 12px; text-align: right; color: #666; font-weight: 600; }
        table td { padding: 12px; border-bottom: 1px solid #eee; }
        table tr:hover { background: #f8f9fa; }
        .btn { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; transition: all 0.3s; margin-left: 5px; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-success:hover { background: #27ae60; }
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; display: inline-block; }
        .badge-audio { background: #3498db; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h2>📚 منصة الكتب</h2>
            <nav>
                <a href="dashboard.php">الرئيسية</a>
                <a href="books.php" class="active">إدارة الكتب</a>
                <a href="upload-book.php">رفع كتاب جديد</a>
                <a href="audio-manager.php">إدارة الصوتيات</a>
                <a href="users.php">إدارة المستخدمين</a>
                <a href="settings.php">الإعدادات</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>إدارة الكتب</h1>
                <p style="color: #666; margin-top: 5px;">جميع الكتب المرفوعة على المنصة</p>
            </div>

            <div class="section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="color: #333;">قائمة الكتب (<?php echo count($books); ?>)</h2>
                    <a href="upload-book.php" class="btn btn-success">+ رفع كتاب جديد</a>
                </div>

                <?php if (count($books) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>العنوان</th>
                                <th>التصنيف</th>
                                <th>اللغة</th>
                                <th>الحجم</th>
                                <th>الصوتيات</th>
                                <th>رفع بواسطة</th>
                                <th>تاريخ الرفع</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($book['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($book['category'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($book['language'] ?: '-'); ?></td>
                                    <td><?php echo round($book['file_size'] / 1024 / 1024, 2); ?> MB</td>
                                    <td>
                                        <?php if ($book['audio_count'] > 0): ?>
                                            <span class="badge badge-audio"><?php echo $book['audio_count']; ?> ملف صوتي</span>
                                        <?php else: ?>
                                            <span style="color: #999;">لا يوجد</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($book['username']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($book['upload_date'])); ?></td>
                                    <td>
                                        <a href="../reader/view-book.php?id=<?php echo $book['id']; ?>" class="btn btn-primary" target="_blank">عرض</a>
                                        <a href="add-audio.php?book_id=<?php echo $book['id']; ?>" class="btn btn-success">إضافة صوت</a>
                                        <a href="delete-book.php?id=<?php echo $book['id']; ?>" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذا الكتاب؟ سيتم حذف جميع الملفات الصوتية المرتبطة به.')">حذف</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 40px;">لا توجد كتب مرفوعة بعد.</p>
                    <div style="text-align: center;">
                        <a href="upload-book.php" class="btn btn-success">رفع كتاب جديد</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
