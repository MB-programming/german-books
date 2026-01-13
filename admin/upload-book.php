<?php
require_once '../config.php';
require_once '../auth.php';

$auth = new Auth($pdo);

if (!$auth->checkSession() || !isAdmin()) {
    redirect('../login.php');
}

$user = $auth->getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = cleanInput($_POST['title']);
    $description = cleanInput($_POST['description']);
    $category = cleanInput($_POST['category']);
    $language = cleanInput($_POST['language']);

    // التحقق من رفع ملف PDF
    if (!isset($_FILES['book_file']) || $_FILES['book_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'الرجاء اختيار ملف الكتاب';
    } else {
        $file = $_FILES['book_file'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // التحقق من نوع الملف
        if (!in_array($fileExt, ALLOWED_BOOK_TYPES)) {
            $error = 'نوع الملف غير مسموح. يجب أن يكون PDF';
        }
        // التحقق من حجم الملف
        elseif ($file['size'] > MAX_FILE_SIZE) {
            $error = 'حجم الملف كبير جداً. الحد الأقصى 50MB';
        } else {
            // توليد اسم ملف فريد
            $uniqueFilename = generateUniqueFilename($fileExt);
            $uploadPath = BOOKS_DIR . $uniqueFilename;

            // نقل الملف
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // معالجة صورة الغلاف (اختياري)
                $coverImage = null;
                if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                    $coverFile = $_FILES['cover_image'];
                    $coverExt = strtolower(pathinfo($coverFile['name'], PATHINFO_EXTENSION));

                    if (in_array($coverExt, ALLOWED_IMAGE_TYPES)) {
                        $coverFilename = generateUniqueFilename($coverExt);
                        $coverPath = COVERS_DIR . $coverFilename;

                        if (move_uploaded_file($coverFile['tmp_name'], $coverPath)) {
                            $coverImage = 'uploads/covers/' . $coverFilename;
                        }
                    }
                }

                // إدخال بيانات الكتاب في قاعدة البيانات
                try {
                    $stmt = $pdo->prepare("INSERT INTO books (title, original_filename, unique_filename, file_path, file_size, uploaded_by, description, cover_image, category, language)
                                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                    $stmt->execute([
                        $title,
                        $file['name'],
                        $uniqueFilename,
                        'uploads/books/' . $uniqueFilename,
                        $file['size'],
                        $user['id'],
                        $description,
                        $coverImage,
                        $category,
                        $language
                    ]);

                    $success = 'تم رفع الكتاب بنجاح!';

                    // إعادة تعيين النموذج
                    $_POST = [];
                } catch (PDOException $e) {
                    $error = 'حدث خطأ أثناء حفظ بيانات الكتاب: ' . $e->getMessage();
                    // حذف الملف المرفوع في حالة الخطأ
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
    <title>رفع كتاب جديد - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar h2 {
            margin-bottom: 30px;
            font-size: 24px;
            text-align: center;
        }

        .sidebar nav a {
            display: block;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            margin-bottom: 10px;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .sidebar nav a:hover {
            background: rgba(255,255,255,0.2);
        }

        .main-content {
            flex: 1;
            padding: 30px;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .header h1 {
            color: #333;
        }

        .form-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            max-width: 800px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        input, textarea, select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        input[type="file"] {
            padding: 30px;
            border: 2px dashed #ddd;
            background: #f8f9fa;
            cursor: pointer;
        }

        input[type="file"]:hover {
            border-color: #667eea;
            background: #f0f1ff;
        }

        button[type="submit"] {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h2>📚 منصة الكتب</h2>
            <nav>
                <a href="dashboard.php">الرئيسية</a>
                <a href="books.php">إدارة الكتب</a>
                <a href="upload-book.php" class="active">رفع كتاب جديد</a>
                <a href="audio-manager.php">إدارة الصوتيات</a>
                <a href="users.php">إدارة المستخدمين</a>
                <a href="settings.php">الإعدادات</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <a href="dashboard.php" class="back-link">← العودة للوحة التحكم</a>
                <h1>رفع كتاب جديد</h1>
            </div>

            <div class="form-section">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                        <a href="books.php" style="margin-right: 15px; color: inherit;">عرض جميع الكتب</a>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>عنوان الكتاب *</label>
                        <input type="text" name="title" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>وصف الكتاب</label>
                        <textarea name="description"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>التصنيف</label>
                        <select name="category">
                            <option value="">اختر التصنيف</option>
                            <option value="تعلم اللغات">تعلم اللغات</option>
                            <option value="أدب">أدب</option>
                            <option value="علوم">علوم</option>
                            <option value="تاريخ">تاريخ</option>
                            <option value="تكنولوجيا">تكنولوجيا</option>
                            <option value="فلسفة">فلسفة</option>
                            <option value="أخرى">أخرى</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>اللغة</label>
                        <select name="language">
                            <option value="">اختر اللغة</option>
                            <option value="العربية">العربية</option>
                            <option value="الإنجليزية">الإنجليزية</option>
                            <option value="الألمانية">الألمانية</option>
                            <option value="الفرنسية">الفرنسية</option>
                            <option value="الإيطالية">الإيطالية</option>
                            <option value="أخرى">أخرى</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>ملف الكتاب (PDF) * - الحد الأقصى 50MB</label>
                        <input type="file" name="book_file" accept=".pdf" required>
                    </div>

                    <div class="form-group">
                        <label>صورة الغلاف (اختياري)</label>
                        <input type="file" name="cover_image" accept="image/*">
                    </div>

                    <button type="submit">رفع الكتاب</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
