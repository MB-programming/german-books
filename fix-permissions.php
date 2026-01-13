<?php
/**
 * Fix Permissions Tool
 * صفحة لإصلاح صلاحيات الملفات والمجلدات
 * ⚠️ احذف هذا الملف بعد الاستخدام!
 */

// كلمة مرور مؤقتة (غيّرها!)
define('FIX_PASSWORD', 'fix123');

session_start();

$authenticated = false;
$results = [];
$errors = [];

// التحقق من كلمة المرور
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === FIX_PASSWORD) {
        $_SESSION['fix_auth'] = true;
        $authenticated = true;
    } else {
        $errors[] = 'كلمة المرور غير صحيحة!';
    }
}

if (isset($_SESSION['fix_auth']) && $_SESSION['fix_auth'] === true) {
    $authenticated = true;
}

// تنفيذ إصلاح الصلاحيات
if ($authenticated && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_permissions'])) {

    $baseDir = __DIR__;

    // 1. إصلاح صلاحيات الملفات PHP (644)
    $phpFiles = [
        'index.php', 'login.php', 'logout.php', 'auth.php', 'config.php',
        'play-audio.php', 'sitemap.php', 'seo-functions.php',
        'install.php', 'install-simple.php', 'import-existing-books.php'
    ];

    foreach ($phpFiles as $file) {
        $path = $baseDir . '/' . $file;
        if (file_exists($path)) {
            if (@chmod($path, 0644)) {
                $results[] = "✅ $file → 644";
            } else {
                $errors[] = "❌ فشل تغيير صلاحية $file";
            }
        }
    }

    // 2. إصلاح صلاحيات المجلدات (755)
    $directories = [
        'admin',
        'reader',
        'uploads',
        'uploads/books',
        'uploads/audio',
        'uploads/covers',
        'uploads/qr'
    ];

    foreach ($directories as $dir) {
        $path = $baseDir . '/' . $dir;

        // إنشاء المجلد إذا لم يكن موجوداً
        if (!file_exists($path)) {
            if (@mkdir($path, 0755, true)) {
                $results[] = "✅ تم إنشاء المجلد: $dir → 755";
            } else {
                $errors[] = "❌ فشل إنشاء المجلد: $dir";
                continue;
            }
        }

        // تغيير الصلاحيات
        if (@chmod($path, 0755)) {
            $results[] = "✅ $dir/ → 755";
        } else {
            $errors[] = "❌ فشل تغيير صلاحية $dir/";
        }
    }

    // 3. إصلاح صلاحيات ملفات admin
    $adminFiles = glob($baseDir . '/admin/*.php');
    foreach ($adminFiles as $file) {
        if (@chmod($file, 0644)) {
            $results[] = "✅ " . basename($file) . " → 644";
        } else {
            $errors[] = "❌ فشل: admin/" . basename($file);
        }
    }

    // 4. إصلاح صلاحيات ملفات reader
    $readerFiles = glob($baseDir . '/reader/*.php');
    foreach ($readerFiles as $file) {
        if (@chmod($file, 0644)) {
            $results[] = "✅ " . basename($file) . " → 644";
        } else {
            $errors[] = "❌ فشل: reader/" . basename($file);
        }
    }

    // 5. ملف .htaccess
    $htaccessPath = $baseDir . '/.htaccess';
    if (file_exists($htaccessPath)) {
        if (@chmod($htaccessPath, 0644)) {
            $results[] = "✅ .htaccess → 644";
        } else {
            $errors[] = "❌ فشل تغيير صلاحية .htaccess";
        }
    }

    // 6. إنشاء ملف index.html فارغ في uploads (للحماية)
    $uploadsIndex = $baseDir . '/uploads/index.html';
    if (!file_exists($uploadsIndex)) {
        if (@file_put_contents($uploadsIndex, '<!-- Protected -->')) {
            $results[] = "✅ تم إنشاء uploads/index.html";
        }
    }
}

// وظيفة للتحقق من الصلاحيات الحالية
function checkPermissions($path) {
    if (!file_exists($path)) {
        return '❌ غير موجود';
    }
    $perms = fileperms($path);
    $octal = substr(sprintf('%o', $perms), -4);
    return $octal;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 إصلاح الصلاحيات - Fix Permissions</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px;
        }
        h1 { color: #667eea; margin-bottom: 10px; }
        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
        .info {
            background: #d1ecf1;
            border: 2px solid #17a2b8;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            color: #0c5460;
        }
        .success {
            background: #d4edda;
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border: 2px solid #dc3545;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            color: #721c24;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }
        .btn:hover { opacity: 0.9; }
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            margin: 10px 0;
        }
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .result-list {
            max-height: 400px;
            overflow-y: auto;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .result-item {
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .result-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 أداة إصلاح الصلاحيات</h1>
        <p style="color: #666; margin-bottom: 20px;">Fix File & Folder Permissions Tool</p>

        <?php if (!$authenticated): ?>
            <!-- صفحة تسجيل الدخول -->
            <div class="warning">
                <strong>⚠️ تحذير أمني</strong>
                <p>هذه الأداة تقوم بتعديل صلاحيات الملفات. استخدمها بحذر!</p>
            </div>

            <div class="info">
                <strong>🔐 كلمة المرور المؤقتة:</strong>
                <p>افتح ملف <code>fix-permissions.php</code> واقرأ كلمة المرور من السطر 9</p>
                <pre style="background: white; padding: 10px; border-radius: 5px; margin-top: 10px;">define('FIX_PASSWORD', 'fix123');</pre>
            </div>

            <form method="POST">
                <label><strong>أدخل كلمة المرور:</strong></label>
                <input type="password" name="password" placeholder="كلمة المرور" required>
                <button type="submit" class="btn">🔓 تسجيل الدخول</button>
            </form>

            <?php if (!empty($errors)): ?>
                <div class="error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- صفحة الإصلاح -->

            <?php if (!empty($results)): ?>
                <div class="success">
                    <h3>✅ تم إصلاح الصلاحيات بنجاح!</h3>
                    <p>عدد العمليات الناجحة: <?php echo count($results); ?></p>
                </div>

                <div class="result-list">
                    <?php foreach ($results as $result): ?>
                        <div class="result-item"><?php echo htmlspecialchars($result); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="error">
                    <h3>❌ أخطاء:</h3>
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($results) && empty($errors)): ?>
                <div class="info">
                    <h3>📋 الصلاحيات الحالية</h3>
                    <p>فحص الصلاحيات الحالية للملفات والمجلدات:</p>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>الملف/المجلد</th>
                            <th>الصلاحية الحالية</th>
                            <th>الصلاحية المطلوبة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>login.php</td>
                            <td><?php echo checkPermissions(__DIR__ . '/login.php'); ?></td>
                            <td>0644</td>
                        </tr>
                        <tr>
                            <td>index.php</td>
                            <td><?php echo checkPermissions(__DIR__ . '/index.php'); ?></td>
                            <td>0644</td>
                        </tr>
                        <tr>
                            <td>config.php</td>
                            <td><?php echo checkPermissions(__DIR__ . '/config.php'); ?></td>
                            <td>0644</td>
                        </tr>
                        <tr>
                            <td>admin/</td>
                            <td><?php echo checkPermissions(__DIR__ . '/admin'); ?></td>
                            <td>0755</td>
                        </tr>
                        <tr>
                            <td>reader/</td>
                            <td><?php echo checkPermissions(__DIR__ . '/reader'); ?></td>
                            <td>0755</td>
                        </tr>
                        <tr>
                            <td>uploads/</td>
                            <td><?php echo checkPermissions(__DIR__ . '/uploads'); ?></td>
                            <td>0755</td>
                        </tr>
                        <tr>
                            <td>uploads/books/</td>
                            <td><?php echo checkPermissions(__DIR__ . '/uploads/books'); ?></td>
                            <td>0755</td>
                        </tr>
                        <tr>
                            <td>uploads/audio/</td>
                            <td><?php echo checkPermissions(__DIR__ . '/uploads/audio'); ?></td>
                            <td>0755</td>
                        </tr>
                    </tbody>
                </table>
            <?php endif; ?>

            <div class="warning">
                <h3>⚠️ ماذا ستفعل هذه الأداة؟</h3>
                <ul style="margin-right: 20px; margin-top: 10px; line-height: 1.8;">
                    <li>✅ ضبط صلاحيات جميع ملفات PHP إلى <strong>644</strong></li>
                    <li>✅ ضبط صلاحيات المجلدات (admin, reader, uploads) إلى <strong>755</strong></li>
                    <li>✅ إنشاء مجلد uploads والمجلدات الفرعية إذا لم تكن موجودة</li>
                    <li>✅ إنشاء ملف index.html في uploads للحماية</li>
                    <li>✅ ضبط صلاحيات .htaccess إلى <strong>644</strong></li>
                </ul>
            </div>

            <form method="POST">
                <button type="submit" name="fix_permissions" class="btn">
                    🔧 إصلاح جميع الصلاحيات الآن
                </button>
            </form>

            <div class="error" style="margin-top: 20px;">
                <h3>🚨 مهم جداً - بعد الانتهاء:</h3>
                <p><strong>احذف هذا الملف فوراً لأسباب أمنية!</strong></p>
                <pre style="background: white; padding: 10px; border-radius: 5px; margin-top: 10px;">rm fix-permissions.php</pre>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>
