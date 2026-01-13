<?php
/**
 * Hostinger Auto-Fix Tool
 * أداة إصلاح تلقائي لمشاكل Hostinger
 * كلمة المرور: hostinger123
 */

define('AUTOFIX_PASSWORD', 'hostinger123');

session_start();

$authenticated = false;
$results = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === AUTOFIX_PASSWORD) {
        $_SESSION['autofix_auth'] = true;
        $authenticated = true;
    } else {
        $errors[] = 'كلمة المرور غير صحيحة!';
    }
}

if (isset($_SESSION['autofix_auth']) && $_SESSION['autofix_auth'] === true) {
    $authenticated = true;
}

// تنفيذ الإصلاح التلقائي
if ($authenticated && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['autofix'])) {
    $baseDir = __DIR__;

    // 1. فحص الملفات الأساسية
    $requiredFiles = ['login.php', 'index.php', 'config.php', 'auth.php'];
    $missingFiles = [];

    foreach ($requiredFiles as $file) {
        if (!file_exists($baseDir . '/' . $file)) {
            $missingFiles[] = $file;
        } else {
            $results[] = "✅ وجد: $file";
        }
    }

    if (!empty($missingFiles)) {
        $errors[] = "❌ ملفات مفقودة: " . implode(', ', $missingFiles);
        $errors[] = "→ تأكد أن الملفات في public_html مباشرة!";
    }

    // 2. فحص المجلدات
    $requiredDirs = ['admin', 'reader', 'uploads'];
    foreach ($requiredDirs as $dir) {
        if (!file_exists($baseDir . '/' . $dir)) {
            $errors[] = "❌ مجلد مفقود: $dir/";
        } else {
            $results[] = "✅ وجد: $dir/";
        }
    }

    // 3. حذف/تعطيل .htaccess
    $htaccessPath = $baseDir . '/.htaccess';
    if (file_exists($htaccessPath)) {
        if (@rename($htaccessPath, $baseDir . '/.htaccess.disabled')) {
            $results[] = "✅ تم تعطيل .htaccess (أعيدت التسمية إلى .htaccess.disabled)";
        } else {
            $errors[] = "❌ فشل تعطيل .htaccess";
        }
    } else {
        $results[] = "ℹ️ ملف .htaccess غير موجود (هذا جيد!)";
    }

    // 4. حذف index.html الافتراضي
    $indexHtml = $baseDir . '/index.html';
    if (file_exists($indexHtml)) {
        if (@unlink($indexHtml)) {
            $results[] = "✅ تم حذف index.html الافتراضي";
        } else {
            $errors[] = "❌ فشل حذف index.html";
        }
    } else {
        $results[] = "ℹ️ index.html غير موجود (جيد!)";
    }

    // 5. إنشاء .htaccess بسيط
    $simpleHtaccess = "# Hostinger Simple .htaccess\n";
    $simpleHtaccess .= "Options -Indexes\n";
    $simpleHtaccess .= "DirectoryIndex index.php index.html\n";
    $simpleHtaccess .= "AddDefaultCharset UTF-8\n";

    if (@file_put_contents($htaccessPath, $simpleHtaccess)) {
        $results[] = "✅ تم إنشاء .htaccess جديد ومبسط";
    } else {
        $errors[] = "⚠️ لم نتمكن من إنشاء .htaccess (سيعمل بدونه)";
    }

    // 6. إصلاح الصلاحيات
    $phpFiles = glob($baseDir . '/*.php');
    $fixedPerms = 0;
    foreach ($phpFiles as $file) {
        if (@chmod($file, 0644)) {
            $fixedPerms++;
        }
    }
    if ($fixedPerms > 0) {
        $results[] = "✅ تم إصلاح صلاحيات $fixedPerms ملف PHP";
    }

    // 7. فحص صلاحيات المجلدات
    foreach ($requiredDirs as $dir) {
        $dirPath = $baseDir . '/' . $dir;
        if (file_exists($dirPath)) {
            @chmod($dirPath, 0755);
        }
    }
    $results[] = "✅ تم إصلاح صلاحيات المجلدات";

    // 8. اختبار قاعدة البيانات
    if (file_exists($baseDir . '/config.php')) {
        require_once $baseDir . '/config.php';
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
            $results[] = "✅ اتصال قاعدة البيانات يعمل!";
        } catch (PDOException $e) {
            $errors[] = "❌ مشكلة في قاعدة البيانات: " . $e->getMessage();
        }
    }
}

function getTestResults() {
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    $scriptPath = dirname($_SERVER['PHP_SELF']);
    $baseUrl .= $scriptPath;

    return [
        'info.php' => rtrim($baseUrl, '/') . '/info.php',
        'index.php' => rtrim($baseUrl, '/') . '/index.php',
        'login.php' => rtrim($baseUrl, '/') . '/login.php',
    ];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Hostinger Auto-Fix</title>
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
            max-width: 900px;
            margin: 0 auto;
            padding: 40px;
        }
        h1 { color: #667eea; margin-bottom: 10px; }
        .box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
        }
        .info { border-right: 4px solid #17a2b8; }
        .success { background: #d4edda; border-right: 4px solid #28a745; }
        .warning { background: #fff3cd; border-right: 4px solid #ffc107; }
        .error { background: #f8d7da; border-right: 4px solid #dc3545; }
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
        .btn-test {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            text-decoration: none;
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
        .result-list {
            max-height: 400px;
            overflow-y: auto;
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .result-item {
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .result-item:last-child { border-bottom: none; }
        pre {
            background: #2d3748;
            color: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Hostinger Auto-Fix Tool</h1>
        <p style="color: #666; margin-bottom: 20px;">أداة الإصلاح التلقائي لمشاكل Hostinger</p>

        <?php if (!$authenticated): ?>
            <div class="box warning">
                <strong>🔐 كلمة المرور:</strong>
                <pre>hostinger123</pre>
            </div>

            <div class="box info">
                <h3>ℹ️ ماذا ستفعل هذه الأداة؟</h3>
                <ul style="margin-right: 20px; line-height: 1.8;">
                    <li>✅ فحص وجود الملفات الأساسية</li>
                    <li>✅ تعطيل .htaccess المسبب للمشاكل</li>
                    <li>✅ حذف index.html الافتراضي</li>
                    <li>✅ إنشاء .htaccess بسيط جديد</li>
                    <li>✅ إصلاح صلاحيات الملفات والمجلدات</li>
                    <li>✅ اختبار اتصال قاعدة البيانات</li>
                </ul>
            </div>

            <form method="POST">
                <label><strong>أدخل كلمة المرور:</strong></label>
                <input type="password" name="password" placeholder="hostinger123" required>
                <button type="submit" class="btn">🔓 تسجيل الدخول</button>
            </form>

            <?php if (!empty($errors)): ?>
                <div class="box error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>

            <?php if (!empty($results)): ?>
                <div class="box success">
                    <h3>✅ تم تنفيذ الإصلاح!</h3>
                    <p>عدد العمليات الناجحة: <?php echo count($results); ?></p>
                </div>

                <div class="result-list">
                    <?php foreach ($results as $result): ?>
                        <div class="result-item"><?php echo htmlspecialchars($result); ?></div>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="box error">
                        <h3>⚠️ تحذيرات/أخطاء:</h3>
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="box info">
                    <h3>🧪 اختبر الروابط الآن:</h3>
                    <?php $tests = getTestResults(); ?>
                    <?php foreach ($tests as $name => $url): ?>
                        <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" class="btn btn-test">
                            اختبر <?php echo htmlspecialchars($name); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="box error">
                    <h3>🚨 بعد التأكد من عمل الموقع:</h3>
                    <p><strong>احذف هذا الملف فوراً!</strong></p>
                    <pre>rm hostinger-autofix.php</pre>
                </div>

            <?php else: ?>

                <div class="box info">
                    <h3>📋 المعلومات الحالية</h3>
                    <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                    <p><strong>Current Directory:</strong> <?php echo __DIR__; ?></p>
                    <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                </div>

                <div class="box warning">
                    <h3>⚠️ قبل البدء:</h3>
                    <p>هذه الأداة ستقوم بـ:</p>
                    <ul style="margin-right: 20px; margin-top: 10px; line-height: 1.8;">
                        <li>تعطيل .htaccess الحالي</li>
                        <li>حذف index.html إذا كان موجوداً</li>
                        <li>إنشاء .htaccess بسيط جديد</li>
                        <li>إصلاح الصلاحيات</li>
                    </ul>
                    <p style="margin-top: 10px;"><strong>هل أنت متأكد؟</strong></p>
                </div>

                <form method="POST">
                    <button type="submit" name="autofix" class="btn">
                        🔧 تشغيل الإصلاح التلقائي
                    </button>
                </form>

            <?php endif; ?>

        <?php endif; ?>
    </div>
</body>
</html>
