<?php
require_once '../config.php';
require_once '../auth.php';

$auth = new Auth($pdo);

// التحقق من تسجيل الدخول والصلاحيات
if (!$auth->checkSession() || !isAdmin()) {
    redirect('../login.php');
}

$user = $auth->getCurrentUser();

// إحصائيات
$statsStmt = $pdo->query("SELECT COUNT(*) as total_books FROM books");
$stats = $statsStmt->fetch();

$usersStmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE user_type = 'reader'");
$userStats = $usersStmt->fetch();

$audioStmt = $pdo->query("SELECT COUNT(*) as total_audio FROM audio_files");
$audioStats = $audioStmt->fetch();

// أحدث الكتب
$booksStmt = $pdo->prepare("SELECT b.*, u.username FROM books b
                            JOIN users u ON b.uploaded_by = u.id
                            ORDER BY b.upload_date DESC LIMIT 10");
$booksStmt->execute();
$recentBooks = $booksStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الأدمن - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/admin-style.css">
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

        .sidebar nav a:hover,
        .sidebar nav a.active {
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #333;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: right;
            color: #666;
            font-weight: 600;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        table tr:hover {
            background: #f8f9fa;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-success {
            background: #2ecc71;
            color: white;
        }

        .btn-success:hover {
            background: #27ae60;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h2>📚 منصة الكتب</h2>
            <nav>
                <a href="dashboard.php" class="active">الرئيسية</a>
                <a href="books.php">إدارة الكتب</a>
                <a href="upload-book.php">رفع كتاب جديد</a>
                <a href="audio-manager.php">إدارة الصوتيات</a>
                <a href="users.php">إدارة المستخدمين</a>
                <a href="settings.php">الإعدادات</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <div>
                    <h1>مرحباً، <?php echo htmlspecialchars($user['username']); ?></h1>
                    <p style="color: #666; margin-top: 5px;">لوحة تحكم الأدمن</p>
                </div>
                <div class="user-info">
                    <span style="color: #666;"><?php echo htmlspecialchars($user['email']); ?></span>
                    <a href="../logout.php" class="logout-btn">تسجيل الخروج</a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>إجمالي الكتب</h3>
                    <div class="number"><?php echo $stats['total_books']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>إجمالي القراء</h3>
                    <div class="number"><?php echo $userStats['total_users']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>الملفات الصوتية</h3>
                    <div class="number"><?php echo $audioStats['total_audio']; ?></div>
                </div>
            </div>

            <div class="section">
                <h2>أحدث الكتب المرفوعة</h2>
                <?php if (count($recentBooks) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>العنوان</th>
                                <th>اسم الملف الأصلي</th>
                                <th>رفع بواسطة</th>
                                <th>تاريخ الرفع</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentBooks as $book): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                                    <td><?php echo htmlspecialchars($book['original_filename']); ?></td>
                                    <td><?php echo htmlspecialchars($book['username']); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($book['upload_date'])); ?></td>
                                    <td class="action-buttons">
                                        <a href="edit-book.php?id=<?php echo $book['id']; ?>" class="btn btn-primary">تعديل</a>
                                        <a href="delete-book.php?id=<?php echo $book['id']; ?>" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذا الكتاب؟')">حذف</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 20px;">لا توجد كتب مرفوعة بعد.</p>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="upload-book.php" class="btn btn-success">رفع كتاب جديد</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
