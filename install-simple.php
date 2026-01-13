<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التثبيت البديل - منصة الكتب الرقمية</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 1000px; margin: 0 auto; padding: 40px; }
        h1 { color: #667eea; margin-bottom: 20px; }
        .step { background: #f8f9fa; padding: 20px; border-radius: 12px; margin: 20px 0; border-right: 4px solid #667eea; }
        .step h3 { color: #333; margin-bottom: 10px; }
        .code-box { background: #2d3748; color: #f7fafc; padding: 20px; border-radius: 8px; overflow-x: auto; margin: 10px 0; max-height: 500px; overflow-y: auto; }
        .btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; margin: 5px; }
        .btn:hover { opacity: 0.9; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { background: #d1ecf1; border-right-color: #17a2b8; }
        .warning { background: #fff3cd; border-right-color: #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 التثبيت البديل - منصة الكتب الرقمية</h1>

        <?php
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $dbHost = 'localhost';
        $dbName = 'u186120816_books';
        $dbUser = 'u186120816_minaboulesf3';
        $dbPass = 'yd+I*aN6';

        $result = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auto_install'])) {
            try {
                // الاتصال بقاعدة البيانات
                $pdo = new PDO(
                    "mysql:host=$dbHost;charset=utf8mb4",
                    $dbUser,
                    $dbPass,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );

                // إنشاء قاعدة البيانات واستخدامها
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `$dbName`");

                // قراءة وتنفيذ ملف SQL
                $sqlFile = __DIR__ . '/database.sql';
                $sql = file_get_contents($sqlFile);

                // تنفيذ SQL كاملاً دفعة واحدة
                $pdo->exec($sql);

                // التحقق من الجداول
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

                $result = [
                    'success' => true,
                    'tables' => $tables,
                    'message' => 'تم التثبيت بنجاح!'
                ];

            } catch (PDOException $e) {
                $result = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        ?>

        <?php if ($result && $result['success']): ?>
            <div class="step" style="background: #d4edda; border-right-color: #28a745;">
                <h3>✅ <?php echo $result['message']; ?></h3>
                <p>الجداول المُنشأة (<?php echo count($result['tables']); ?>):</p>
                <ul style="margin-right: 20px; margin-top: 10px;">
                    <?php foreach ($result['tables'] as $table): ?>
                        <li><?php echo htmlspecialchars($table); ?></li>
                    <?php endforeach; ?>
                </ul>
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
                <p><strong>احذف ملفات التثبيت فوراً:</strong></p>
                <div class="code-box">rm install.php install-simple.php</div>
            </div>

            <a href="login.php" class="btn" style="display: inline-block; text-decoration: none;">
                🔐 الذهاب لصفحة تسجيل الدخول
            </a>

        <?php elseif ($result && !$result['success']): ?>
            <div class="step" style="background: #f8d7da; border-right-color: #dc3545;">
                <h3>❌ خطأ في التثبيت</h3>
                <p><?php echo htmlspecialchars($result['error']); ?></p>
            </div>

            <button onclick="location.reload()" class="btn">🔄 إعادة المحاولة</button>

        <?php else: ?>
            <div class="step info">
                <h3>ℹ️ معلومات</h3>
                <p>هذا هو معالج التثبيت البديل. استخدمه إذا واجهت مشاكل مع install.php العادي.</p>
            </div>

            <div class="step">
                <h3>📋 معلومات قاعدة البيانات</h3>
                <pre>اسم القاعدة: <?php echo $dbName; ?>
المستخدم: <?php echo $dbUser; ?>
المضيف: <?php echo $dbHost; ?></pre>
            </div>

            <!-- خيار 1: التثبيت التلقائي -->
            <div class="step">
                <h3>🚀 الخيار 1: التثبيت التلقائي (موصى به)</h3>
                <p>اضغط الزر أدناه لتثبيت قاعدة البيانات تلقائياً:</p>
                <form method="POST">
                    <button type="submit" name="auto_install" class="btn">🚀 تثبيت تلقائي</button>
                </form>
            </div>

            <!-- خيار 2: التثبيت اليدوي -->
            <div class="step">
                <h3>📝 الخيار 2: التثبيت اليدوي (عبر phpMyAdmin)</h3>
                <p>إذا فشل التثبيت التلقائي، استخدم هذه الطريقة:</p>
                <ol style="margin-right: 20px; line-height: 1.8;">
                    <li>سجل دخول إلى <strong>phpMyAdmin</strong></li>
                    <li>اختر قاعدة البيانات: <code><?php echo $dbName; ?></code></li>
                    <li>اذهب إلى تبويب <strong>SQL</strong></li>
                    <li>انسخ الكود أدناه والصقه في صندوق SQL</li>
                    <li>اضغط <strong>Go</strong> أو <strong>تنفيذ</strong></li>
                </ol>

                <button onclick="copySQL()" class="btn">📋 نسخ SQL</button>
                <button onclick="toggleSQL()" class="btn">👁️ عرض/إخفاء SQL</button>

                <div id="sqlCode" class="code-box" style="display: none; direction: ltr; text-align: left;">
<?php echo htmlspecialchars(file_get_contents(__DIR__ . '/database.sql')); ?>
                </div>
            </div>

            <!-- خيار 3: التثبيت عبر Command Line -->
            <div class="step">
                <h3>💻 الخيار 3: التثبيت عبر SSH/Command Line</h3>
                <p>إذا كان لديك وصول SSH، استخدم هذا الأمر:</p>
                <button onclick="copyCommand()" class="btn">📋 نسخ الأمر</button>
                <div class="code-box" id="sshCommand" style="direction: ltr; text-align: left;">mysql -h <?php echo $dbHost; ?> -u <?php echo $dbUser; ?> -p'<?php echo $dbPass; ?>' <?php echo $dbName; ?> &lt; database.sql</div>
            </div>

        <?php endif; ?>
    </div>

    <script>
        function toggleSQL() {
            const sqlCode = document.getElementById('sqlCode');
            sqlCode.style.display = sqlCode.style.display === 'none' ? 'block' : 'none';
        }

        function copySQL() {
            const sqlCode = document.getElementById('sqlCode').textContent;
            navigator.clipboard.writeText(sqlCode).then(() => {
                alert('✅ تم نسخ SQL إلى الحافظة!');
            }).catch(() => {
                toggleSQL();
                alert('⚠️ لم يتم النسخ. قم بتحديد النص يدوياً والنسخ (Ctrl+C)');
            });
        }

        function copyCommand() {
            const command = document.getElementById('sshCommand').textContent;
            navigator.clipboard.writeText(command).then(() => {
                alert('✅ تم نسخ الأمر إلى الحافظة!');
            }).catch(() => {
                alert('⚠️ لم يتم النسخ. قم بتحديد النص يدوياً والنسخ (Ctrl+C)');
            });
        }
    </script>
</body>
</html>
