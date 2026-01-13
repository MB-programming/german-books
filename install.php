<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تثبيت منصة الكتب الرقمية</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .container { background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 800px; width: 100%; padding: 40px; }
        h1 { color: #667eea; margin-bottom: 30px; text-align: center; }
        .step { background: #f8f9fa; padding: 20px; border-radius: 12px; margin: 20px 0; border-right: 4px solid #667eea; }
        .step h3 { color: #333; margin-bottom: 10px; }
        .success { background: #d4edda; border-right-color: #28a745; }
        .error { background: #f8d7da; border-right-color: #dc3545; }
        .warning { background: #fff3cd; border-right-color: #ffc107; }
        .btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; width: 100%; margin-top: 20px; }
        .btn:hover { opacity: 0.9; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        pre { background: #2d3748; color: #f7fafc; padding: 15px; border-radius: 8px; overflow-x: auto; margin: 10px 0; }
        .progress { background: #e9ecef; height: 30px; border-radius: 15px; overflow: hidden; margin: 20px 0; }
        .progress-bar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 تثبيت منصة الكتب الرقمية</h1>

        <?php
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        // معلومات قاعدة البيانات
        $dbHost = 'localhost';
        $dbName = 'u186120816_books';
        $dbUser = 'u186120816_minaboulesf3';
        $dbPass = 'yd+I*aN6';

        $installed = false;
        $errors = [];
        $success = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
            echo '<div class="progress"><div class="progress-bar" id="progressBar" style="width: 0%">0%</div></div>';
            echo '<script>
            function updateProgress(percent, text) {
                document.getElementById("progressBar").style.width = percent + "%";
                document.getElementById("progressBar").textContent = text;
            }
            </script>';

            try {
                // الخطوة 1: الاتصال بقاعدة البيانات
                echo '<script>updateProgress(10, "الاتصال بقاعدة البيانات...");</script>';
                flush();

                $pdo = new PDO(
                    "mysql:host=$dbHost;charset=utf8mb4",
                    $dbUser,
                    $dbPass,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );

                $success[] = "تم الاتصال بقاعدة البيانات بنجاح";

                // الخطوة 2: إنشاء قاعدة البيانات
                echo '<script>updateProgress(20, "إنشاء قاعدة البيانات...");</script>';
                flush();

                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `$dbName`");
                $success[] = "تم إنشاء قاعدة البيانات: $dbName";

                // الخطوة 3: قراءة ملف SQL
                echo '<script>updateProgress(30, "قراءة ملف SQL...");</script>';
                flush();

                $sqlFile = __DIR__ . '/database.sql';
                if (!file_exists($sqlFile)) {
                    throw new Exception("ملف database.sql غير موجود!");
                }

                $sql = file_get_contents($sqlFile);
                $sqlFileSize = strlen($sql);
                $success[] = "تم قراءة ملف SQL بنجاح (حجم: $sqlFileSize حرف)";

                // الخطوة 4: تنفيذ الاستعلامات
                echo '<script>updateProgress(50, "إنشاء الجداول...");</script>';
                flush();

                // إزالة التعليقات من SQL
                $sql = preg_replace('/--.*$/m', '', $sql); // إزالة تعليقات --
                $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // إزالة تعليقات /* */
                $sql = preg_replace('/^#.*$/m', '', $sql); // إزالة تعليقات #

                // تقسيم الاستعلامات بناءً على الفاصلة المنقوطة
                $statements = explode(';', $sql);
                $statements = array_map('trim', $statements);
                $statements = array_filter($statements, function($stmt) {
                    return !empty($stmt);
                });

                $totalStatements = count($statements);
                $success[] = "تم العثور على $totalStatements استعلام SQL";
                $executedStatements = 0;
                $failedStatements = [];

                foreach ($statements as $index => $statement) {
                    if (!empty($statement)) {
                        try {
                            $pdo->exec($statement);
                            $executedStatements++;
                            $progress = 50 + (($executedStatements / $totalStatements) * 40);
                            echo '<script>updateProgress(' . $progress . ', "تنفيذ: ' . $executedStatements . '/' . $totalStatements . '...");</script>';
                            flush();
                        } catch (PDOException $e) {
                            // تسجيل الأخطاء (ماعدا التكرار)
                            if (strpos($e->getMessage(), 'Duplicate') === false &&
                                strpos($e->getMessage(), 'already exists') === false) {
                                $failedStatements[] = [
                                    'index' => $index + 1,
                                    'error' => $e->getMessage(),
                                    'statement' => substr($statement, 0, 100) . '...'
                                ];
                            }
                        }
                    }
                }

                if (!empty($failedStatements)) {
                    foreach ($failedStatements as $failed) {
                        $errors[] = "خطأ في الاستعلام #{$failed['index']}: {$failed['error']}";
                    }
                } else {
                    $success[] = "تم تنفيذ $executedStatements استعلام بنجاح";
                }

                // الخطوة 5: التحقق من الجداول
                echo '<script>updateProgress(95, "التحقق من الجداول...");</script>';
                flush();

                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                $requiredTables = ['users', 'books', 'audio_files', 'saved_books', 'reading_progress', 'purchase_requests', 'purchases', 'sessions'];
                $missingTables = array_diff($requiredTables, $tables);

                if (empty($missingTables)) {
                    $success[] = "جميع الجداول المطلوبة موجودة: " . implode(', ', $tables);
                } else {
                    $errors[] = "جداول ناقصة: " . implode(', ', $missingTables);
                }

                // الخطوة 6: التحقق من المستخدمين الافتراضيين
                echo '<script>updateProgress(98, "التحقق من المستخدمين...");</script>';
                flush();

                $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                if ($userCount > 0) {
                    $success[] = "تم إنشاء $userCount مستخدم افتراضي";
                } else {
                    $errors[] = "لم يتم إنشاء مستخدمين افتراضيين";
                }

                echo '<script>updateProgress(100, "اكتمل التثبيت!");</script>';
                flush();

                if (empty($errors)) {
                    $installed = true;
                }

            } catch (PDOException $e) {
                $errors[] = "خطأ في قاعدة البيانات: " . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        ?>

        <?php if (!$installed && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            <div class="step warning">
                <h3>⚠️ تنبيه هام</h3>
                <p>سيتم إنشاء قاعدة البيانات وجميع الجداول المطلوبة.</p>
                <p>إذا كانت قاعدة البيانات موجودة، سيتم الاحتفاظ بالبيانات الموجودة.</p>
            </div>

            <div class="step">
                <h3>📋 معلومات قاعدة البيانات</h3>
                <pre>اسم القاعدة: <?php echo $dbName; ?>
المستخدم: <?php echo $dbUser; ?>
المضيف: <?php echo $dbHost; ?></pre>
            </div>

            <div class="step">
                <h3>✨ ما سيتم تثبيته:</h3>
                <ul style="margin-right: 20px; line-height: 1.8;">
                    <li>قاعدة البيانات الكاملة</li>
                    <li>جدول المستخدمين (users)</li>
                    <li>جدول الكتب (books)</li>
                    <li>جدول الملفات الصوتية (audio_files)</li>
                    <li>جدول الكتب المحفوظة (saved_books)</li>
                    <li>جدول تقدم القراءة (reading_progress)</li>
                    <li>جدول طلبات الشراء (purchase_requests)</li>
                    <li>جدول المشتريات (purchases)</li>
                    <li>جدول الجلسات (sessions)</li>
                    <li>مستخدمين افتراضيين (admin & reader)</li>
                </ul>
            </div>

            <form method="POST">
                <button type="submit" name="install" class="btn">🚀 بدء التثبيت</button>
            </form>

        <?php elseif (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="step error">
                    <h3>❌ خطأ</h3>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endforeach; ?>

            <form method="POST">
                <button type="submit" name="install" class="btn">🔄 إعادة المحاولة</button>
            </form>

        <?php else: ?>
            <?php foreach ($success as $msg): ?>
                <div class="step success">
                    <h3>✅ نجح</h3>
                    <p><?php echo htmlspecialchars($msg); ?></p>
                </div>
            <?php endforeach; ?>

            <div class="step success">
                <h3>🎉 تم التثبيت بنجاح!</h3>
                <p>يمكنك الآن استخدام المنصة.</p>
            </div>

            <div class="step">
                <h3>🔑 حسابات تجريبية</h3>
                <pre><strong>حساب الأدمن:</strong>
البريد: admin@bookplatform.com
كلمة المرور: admin123

<strong>حساب القارئ:</strong>
البريد: reader@bookplatform.com
كلمة المرور: reader123</pre>
            </div>

            <div class="step warning">
                <h3>⚠️ مهم جداً</h3>
                <p><strong>احذف ملف install.php فوراً من السيرفر لأسباب أمنية!</strong></p>
            </div>

            <a href="login.php" class="btn" style="display: block; text-align: center; text-decoration: none;">
                🔐 الذهاب لصفحة تسجيل الدخول
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
