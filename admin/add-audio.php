<?php
require_once '../config.php';
require_once '../auth.php';

$auth = new Auth($pdo);

if (!$auth->checkSession() || !isAdmin()) {
    redirect('../login.php');
}

$bookId = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;

// الحصول على بيانات الكتاب
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book) {
    redirect('books.php');
}

// الحصول على الملفات الصوتية الموجودة
$audioStmt = $pdo->prepare("SELECT * FROM audio_files WHERE book_id = ? ORDER BY page_number");
$audioStmt->execute([$bookId]);
$audioFiles = $audioStmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pageNumber = intval($_POST['page_number']);

    if (!isset($_FILES['audio_file']) || $_FILES['audio_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'الرجاء اختيار ملف صوتي';
    } else {
        $file = $_FILES['audio_file'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExt, ALLOWED_AUDIO_TYPES)) {
            $error = 'نوع الملف غير مسموح. يجب أن يكون MP3, WAV, OGG, أو M4A';
        } elseif ($file['size'] > MAX_FILE_SIZE) {
            $error = 'حجم الملف كبير جداً';
        } else {
            $uniqueFilename = generateUniqueFilename($fileExt);
            $uploadPath = AUDIO_DIR . $uniqueFilename;

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // توليد QR Code فريد
                $qrCode = bin2hex(random_bytes(16));
                $qrFilename = $qrCode . '.png';

                // إنشاء رابط الصوت للـ QR Code
                $audioUrl = SITE_URL . '/play-audio.php?qr=' . $qrCode;

                // توليد صورة QR Code
                // ملاحظة: نحتاج مكتبة phpqrcode - سنستخدم طريقة بديلة مؤقتاً
                // يمكنك استخدام Google Charts API أو مكتبة PHP QR Code

                try {
                    $stmt = $pdo->prepare("INSERT INTO audio_files (book_id, page_number, qr_code, audio_filename, audio_path)
                                          VALUES (?, ?, ?, ?, ?)");

                    $stmt->execute([
                        $bookId,
                        $pageNumber,
                        $qrCode,
                        $uniqueFilename,
                        'uploads/audio/' . $uniqueFilename
                    ]);

                    $success = 'تم إضافة الملف الصوتي بنجاح! QR Code: ' . $qrCode;

                    // تحديث قائمة الملفات الصوتية
                    $audioStmt->execute([$bookId]);
                    $audioFiles = $audioStmt->fetchAll();
                } catch (PDOException $e) {
                    $error = 'حدث خطأ: ' . $e->getMessage();
                    if (file_exists($uploadPath)) {
                        unlink($uploadPath);
                    }
                }
            } else {
                $error = 'فشل رفع الملف';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة ملف صوتي - <?php echo SITE_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; }
        .sidebar h2 { margin-bottom: 30px; font-size: 24px; text-align: center; }
        .sidebar nav a { display: block; padding: 12px 15px; color: white; text-decoration: none; margin-bottom: 10px; border-radius: 8px; transition: background 0.3s; }
        .sidebar nav a:hover { background: rgba(255,255,255,0.2); }
        .main-content { flex: 1; padding: 30px; }
        .header { background: white; padding: 20px 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .section { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; }
        input, select { width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 15px; }
        input:focus, select:focus { outline: none; border-color: #667eea; }
        button { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 14px 30px; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; }
        button:hover { transform: translateY(-2px); }
        .alert { padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #667eea; text-decoration: none; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table th { background: #f8f9fa; padding: 12px; text-align: right; color: #666; }
        table td { padding: 12px; border-bottom: 1px solid #eee; }
        .qr-code { width: 100px; height: 100px; background: #f0f1ff; border: 2px solid #667eea; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #667eea; font-size: 12px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h2>📚 منصة الكتب</h2>
            <nav>
                <a href="dashboard.php">الرئيسية</a>
                <a href="books.php">إدارة الكتب</a>
                <a href="upload-book.php">رفع كتاب جديد</a>
                <a href="audio-manager.php">إدارة الصوتيات</a>
                <a href="users.php">إدارة المستخدمين</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <a href="books.php" class="back-link">← العودة لإدارة الكتب</a>
                <h1>إضافة ملف صوتي: <?php echo htmlspecialchars($book['title']); ?></h1>
            </div>

            <div class="section">
                <h2 style="margin-bottom: 20px; color: #333;">إضافة ملف صوتي جديد</h2>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>رقم الصفحة *</label>
                        <input type="number" name="page_number" min="1" required>
                    </div>

                    <div class="form-group">
                        <label>الملف الصوتي * (MP3, WAV, OGG, M4A)</label>
                        <input type="file" name="audio_file" accept=".mp3,.wav,.ogg,.m4a" required>
                    </div>

                    <button type="submit">إضافة الملف الصوتي</button>
                </form>
            </div>

            <?php if (count($audioFiles) > 0): ?>
                <div class="section">
                    <h2 style="margin-bottom: 20px; color: #333;">الملفات الصوتية الموجودة</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>رقم الصفحة</th>
                                <th>اسم الملف</th>
                                <th>QR Code</th>
                                <th>رابط التشغيل</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($audioFiles as $audio): ?>
                                <tr>
                                    <td><?php echo $audio['page_number']; ?></td>
                                    <td><?php echo htmlspecialchars($audio['audio_filename']); ?></td>
                                    <td>
                                        <div class="qr-code">QR<br><?php echo substr($audio['qr_code'], 0, 8); ?>...</div>
                                    </td>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>/play-audio.php?qr=<?php echo $audio['qr_code']; ?>" target="_blank">
                                            تشغيل
                                        </a>
                                    </td>
                                    <td>
                                        <a href="delete-audio.php?id=<?php echo $audio['id']; ?>&book_id=<?php echo $bookId; ?>"
                                           onclick="return confirm('هل أنت متأكد من حذف هذا الملف الصوتي؟')"
                                           style="color: #e74c3c;">حذف</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
