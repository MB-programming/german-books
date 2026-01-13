<?php
/**
 * Server Test & Diagnostic Tool
 * أداة اختبار وتشخيص السيرفر
 * ⚠️ احذف هذا الملف بعد الاستخدام!
 */

// كلمة مرور مؤقتة
define('TEST_PASSWORD', 'test123');

session_start();

$authenticated = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === TEST_PASSWORD) {
        $_SESSION['test_auth'] = true;
        $authenticated = true;
    }
}

if (isset($_SESSION['test_auth']) && $_SESSION['test_auth'] === true) {
    $authenticated = true;
}

function testStatus($condition, $success, $failure) {
    return $condition ? "✅ $success" : "❌ $failure";
}

function checkFileExists($file) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path);
    $perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';
    return [
        'exists' => $exists,
        'perms' => $perms,
        'readable' => $exists && is_readable($path),
        'writable' => $exists && is_writable($path)
    ];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 اختبار السيرفر - Server Test</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px;
        }
        h1 { color: #667eea; margin-bottom: 10px; }
        h2 { color: #333; margin: 30px 0 15px; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
        }
        .info { border-left: 4px solid #17a2b8; }
        .success { border-left: 4px solid #28a745; }
        .warning { border-left: 4px solid #ffc107; }
        .error { border-left: 4px solid #dc3545; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: white;
        }
        th, td {
            padding: 12px;
            text-align: right;
            border: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        tr:nth-child(even) { background: #f8f9fa; }
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
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            margin: 10px 0;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: #333; }
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
        <h1>🔍 أداة اختبار وتشخيص السيرفر</h1>
        <p style="color: #666; margin-bottom: 20px;">Server Test & Diagnostic Tool</p>

        <?php if (!$authenticated): ?>
            <div class="box warning">
                <strong>🔐 كلمة المرور:</strong>
                <p>افتح ملف <code>test-server.php</code> واقرأ كلمة المرور من السطر 9</p>
                <pre>define('TEST_PASSWORD', 'test123');</pre>
            </div>

            <form method="POST">
                <input type="password" name="password" placeholder="كلمة المرور" required>
                <button type="submit" class="btn">🔓 تسجيل الدخول</button>
            </form>

        <?php else: ?>

            <!-- 1. معلومات PHP -->
            <h2>📋 معلومات PHP</h2>
            <div class="box info">
                <table>
                    <tr>
                        <th>البند</th>
                        <th>القيمة</th>
                        <th>الحالة</th>
                    </tr>
                    <tr>
                        <td>PHP Version</td>
                        <td><?php echo phpversion(); ?></td>
                        <td><?php echo version_compare(phpversion(), '7.4.0', '>=') ? '<span class="badge badge-success">✓</span>' : '<span class="badge badge-danger">✗ يحتاج 7.4+</span>'; ?></td>
                    </tr>
                    <tr>
                        <td>Server Software</td>
                        <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td>Document Root</td>
                        <td><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td>Current Directory</td>
                        <td><?php echo __DIR__; ?></td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td>upload_max_filesize</td>
                        <td><?php echo ini_get('upload_max_filesize'); ?></td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td>post_max_size</td>
                        <td><?php echo ini_get('post_max_size'); ?></td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td>memory_limit</td>
                        <td><?php echo ini_get('memory_limit'); ?></td>
                        <td>-</td>
                    </tr>
                </table>
            </div>

            <!-- 2. الإضافات المطلوبة -->
            <h2>🔌 PHP Extensions</h2>
            <div class="box">
                <table>
                    <tr>
                        <th>الإضافة</th>
                        <th>الحالة</th>
                    </tr>
                    <tr>
                        <td>PDO</td>
                        <td><?php echo extension_loaded('PDO') ? '<span class="badge badge-success">✓ مفعّل</span>' : '<span class="badge badge-danger">✗ غير مفعّل</span>'; ?></td>
                    </tr>
                    <tr>
                        <td>PDO MySQL</td>
                        <td><?php echo extension_loaded('pdo_mysql') ? '<span class="badge badge-success">✓ مفعّل</span>' : '<span class="badge badge-danger">✗ غير مفعّل</span>'; ?></td>
                    </tr>
                    <tr>
                        <td>GD (للصور)</td>
                        <td><?php echo extension_loaded('gd') ? '<span class="badge badge-success">✓ مفعّل</span>' : '<span class="badge badge-warning">⚠ غير مفعّل</span>'; ?></td>
                    </tr>
                    <tr>
                        <td>mbstring</td>
                        <td><?php echo extension_loaded('mbstring') ? '<span class="badge badge-success">✓ مفعّل</span>' : '<span class="badge badge-warning">⚠ غير مفعّل</span>'; ?></td>
                    </tr>
                </table>
            </div>

            <!-- 3. فحص الملفات -->
            <h2>📁 فحص الملفات الرئيسية</h2>
            <div class="box">
                <table>
                    <tr>
                        <th>الملف</th>
                        <th>موجود؟</th>
                        <th>الصلاحية</th>
                        <th>قراءة</th>
                        <th>كتابة</th>
                    </tr>
                    <?php
                    $files = [
                        'login.php',
                        'index.php',
                        'config.php',
                        'auth.php',
                        'database.sql',
                        '.htaccess',
                        'admin/dashboard.php',
                        'reader/dashboard.php'
                    ];

                    foreach ($files as $file) {
                        $info = checkFileExists($file);
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($file) . '</td>';
                        echo '<td>' . ($info['exists'] ? '<span class="badge badge-success">✓</span>' : '<span class="badge badge-danger">✗</span>') . '</td>';
                        echo '<td>' . htmlspecialchars($info['perms']) . '</td>';
                        echo '<td>' . ($info['readable'] ? '<span class="badge badge-success">✓</span>' : '<span class="badge badge-danger">✗</span>') . '</td>';
                        echo '<td>' . ($info['writable'] ? '<span class="badge badge-success">✓</span>' : '<span class="badge badge-danger">✗</span>') . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </table>
            </div>

            <!-- 4. فحص المجلدات -->
            <h2>📂 فحص المجلدات</h2>
            <div class="box">
                <table>
                    <tr>
                        <th>المجلد</th>
                        <th>موجود؟</th>
                        <th>الصلاحية</th>
                        <th>قراءة</th>
                        <th>كتابة</th>
                    </tr>
                    <?php
                    $dirs = [
                        'admin',
                        'reader',
                        'uploads',
                        'uploads/books',
                        'uploads/audio',
                        'uploads/covers',
                        'uploads/qr'
                    ];

                    foreach ($dirs as $dir) {
                        $info = checkFileExists($dir);
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($dir) . '/</td>';
                        echo '<td>' . ($info['exists'] ? '<span class="badge badge-success">✓</span>' : '<span class="badge badge-danger">✗</span>') . '</td>';
                        echo '<td>' . htmlspecialchars($info['perms']) . '</td>';
                        echo '<td>' . ($info['readable'] ? '<span class="badge badge-success">✓</span>' : '<span class="badge badge-danger">✗</span>') . '</td>';
                        echo '<td>' . ($info['writable'] ? '<span class="badge badge-success">✓</span>' : '<span class="badge badge-danger">✗</span>') . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </table>
            </div>

            <!-- 5. اختبار قاعدة البيانات -->
            <h2>🗄️ اختبار الاتصال بقاعدة البيانات</h2>
            <div class="box">
                <?php
                if (file_exists(__DIR__ . '/config.php')) {
                    require_once __DIR__ . '/config.php';

                    try {
                        $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
                        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                        ]);

                        echo '<div class="box success">';
                        echo '<p>✅ <strong>نجح الاتصال بـ MySQL!</strong></p>';
                        echo '<p>Host: ' . htmlspecialchars(DB_HOST) . '</p>';
                        echo '<p>User: ' . htmlspecialchars(DB_USER) . '</p>';
                        echo '</div>';

                        // فحص قاعدة البيانات
                        $stmt = $pdo->query("SHOW DATABASES");
                        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);

                        if (in_array(DB_NAME, $databases)) {
                            echo '<div class="box success">';
                            echo '<p>✅ <strong>قاعدة البيانات موجودة: ' . htmlspecialchars(DB_NAME) . '</strong></p>';

                            // فحص الجداول
                            $pdo->exec("USE `" . DB_NAME . "`");
                            $stmt = $pdo->query("SHOW TABLES");
                            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

                            echo '<p>الجداول الموجودة (' . count($tables) . '):</p>';
                            echo '<ul style="margin-right: 20px;">';
                            foreach ($tables as $table) {
                                echo '<li>' . htmlspecialchars($table) . '</li>';
                            }
                            echo '</ul>';
                            echo '</div>';

                            // فحص الجداول المطلوبة
                            $requiredTables = ['users', 'books', 'audio_files', 'saved_books', 'reading_progress', 'purchase_requests', 'purchases', 'sessions'];
                            $missingTables = array_diff($requiredTables, $tables);

                            if (empty($missingTables)) {
                                echo '<div class="box success">';
                                echo '<p>✅ <strong>جميع الجداول المطلوبة موجودة!</strong></p>';
                                echo '</div>';
                            } else {
                                echo '<div class="box error">';
                                echo '<p>❌ <strong>جداول ناقصة:</strong></p>';
                                echo '<ul style="margin-right: 20px;">';
                                foreach ($missingTables as $table) {
                                    echo '<li>' . htmlspecialchars($table) . '</li>';
                                }
                                echo '</ul>';
                                echo '<p>→ شغّل <a href="install-simple.php">install-simple.php</a></p>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="box error">';
                            echo '<p>❌ <strong>قاعدة البيانات غير موجودة: ' . htmlspecialchars(DB_NAME) . '</strong></p>';
                            echo '<p>→ شغّل <a href="install-simple.php">install-simple.php</a> لإنشائها</p>';
                            echo '</div>';
                        }

                    } catch (PDOException $e) {
                        echo '<div class="box error">';
                        echo '<p>❌ <strong>فشل الاتصال بقاعدة البيانات!</strong></p>';
                        echo '<p>الخطأ: ' . htmlspecialchars($e->getMessage()) . '</p>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="box error">';
                    echo '<p>❌ <strong>ملف config.php غير موجود!</strong></p>';
                    echo '</div>';
                }
                ?>
            </div>

            <!-- 6. اختبار .htaccess -->
            <h2>⚙️ فحص .htaccess</h2>
            <div class="box">
                <?php
                $htaccess = __DIR__ . '/.htaccess';
                if (file_exists($htaccess)) {
                    echo '<div class="box success">';
                    echo '<p>✅ <strong>ملف .htaccess موجود</strong></p>';
                    echo '<p>الحجم: ' . filesize($htaccess) . ' bytes</p>';

                    // فحص محتوى .htaccess
                    $content = file_get_contents($htaccess);
                    if (strpos($content, 'RewriteEngine On') !== false) {
                        echo '<p>✓ RewriteEngine مفعّل</p>';
                    }
                    if (strpos($content, 'HTTPS') !== false) {
                        echo '<p>⚠️ يحتوي على HTTPS Redirect (قد يسبب مشاكل إذا كان HTTP فقط)</p>';
                    }

                    echo '</div>';

                    echo '<div class="box warning">';
                    echo '<h3>💡 إذا كان .htaccess يسبب مشاكل:</h3>';
                    echo '<p>1. أعد تسمية .htaccess إلى .htaccess.bak</p>';
                    echo '<p>2. استخدم .htaccess-simple بدلاً منه:</p>';
                    echo '<pre>mv .htaccess .htaccess.bak
mv .htaccess-simple .htaccess</pre>';
                    echo '</div>';
                } else {
                    echo '<div class="box warning">';
                    echo '<p>⚠️ <strong>ملف .htaccess غير موجود</strong></p>';
                    echo '<p>يُفضل وجوده للأمان والتوجيه.</p>';
                    echo '</div>';
                }
                ?>
            </div>

            <!-- 7. الروابط المباشرة -->
            <h2>🔗 اختبار الروابط</h2>
            <div class="box">
                <p>اختبر هذه الروابط في المتصفح:</p>
                <?php
                $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
                $scriptPath = dirname($_SERVER['PHP_SELF']);
                $baseUrl .= $scriptPath;

                $links = [
                    'index.php' => 'الصفحة الرئيسية',
                    'login.php' => 'تسجيل الدخول',
                    'admin/dashboard.php' => 'لوحة الأدمن',
                    'reader/dashboard.php' => 'لوحة القارئ'
                ];

                echo '<ul style="margin-right: 20px; line-height: 2;">';
                foreach ($links as $file => $name) {
                    $url = rtrim($baseUrl, '/') . '/' . $file;
                    echo '<li><a href="' . htmlspecialchars($url) . '" target="_blank">' . htmlspecialchars($name) . '</a></li>';
                }
                echo '</ul>';
                ?>
            </div>

            <div class="box error">
                <h3>🚨 احذف هذا الملف بعد الانتهاء!</h3>
                <pre>rm test-server.php</pre>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>
