<?php
/**
 * Setup Admin Page
 * صفحة إنشاء حساب أدمن لأول مرة
 * ⚠️ احذف هذا الملف بعد إنشاء الأدمن!
 */

require_once 'config.php';

// كلمة مرور الحماية
define('SETUP_PASSWORD', 'setup2026');

$authenticated = false;
$error = '';
$success = '';

session_start();

// التحقق من وجود أدمن في قاعدة البيانات
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin'");
    $adminCount = $stmt->fetchColumn();

    // إذا كان يوجد أدمن بالفعل
    if ($adminCount > 0 && !isset($_SESSION['setup_force'])) {
        $existingAdmins = true;
    } else {
        $existingAdmins = false;
    }
} catch (PDOException $e) {
    $error = 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
}

// التحقق من كلمة مرور الحماية
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_password'])) {
    if ($_POST['setup_password'] === SETUP_PASSWORD) {
        $_SESSION['setup_auth'] = true;
        $authenticated = true;
    } else {
        $error = 'كلمة مرور الحماية غير صحيحة!';
    }
}

if (isset($_SESSION['setup_auth']) && $_SESSION['setup_auth'] === true) {
    $authenticated = true;
}

// إنشاء حساب أدمن
if ($authenticated && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // التحقق من البيانات
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'الرجاء ملء جميع الحقول';
    } elseif ($password !== $confirm_password) {
        $error = 'كلمات المرور غير متطابقة';
    } elseif (strlen($password) < 6) {
        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'البريد الإلكتروني غير صحيح';
    } else {
        try {
            // التحقق من عدم وجود نفس البريد أو اسم المستخدم
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR username = ?");
            $checkStmt->execute([$email, $username]);

            if ($checkStmt->fetchColumn() > 0) {
                $error = 'البريد الإلكتروني أو اسم المستخدم موجود بالفعل';
            } else {
                // إنشاء حساب الأدمن
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $insertStmt = $pdo->prepare("INSERT INTO users (username, email, password, user_type) VALUES (?, ?, ?, 'admin')");
                $insertStmt->execute([$username, $email, $hashedPassword]);

                $success = true;
            }
        } catch (PDOException $e) {
            $error = 'خطأ في إنشاء الحساب: ' . $e->getMessage();
        }
    }
}

// السماح بإضافة أدمن إضافي
if (isset($_POST['add_more']) && isset($_SESSION['setup_auth'])) {
    $_SESSION['setup_force'] = true;
    $existingAdmins = false;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعداد حساب الأدمن - <?php echo SITE_NAME; ?></title>

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h1 {
            color: #667eea;
            text-align: center;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }

        .box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .info { border-right: 4px solid #17a2b8; }
        .success { background: #d4edda; border-right: 4px solid #28a745; }
        .warning { background: #fff3cd; border-right: 4px solid #ffc107; }
        .error { background: #f8d7da; border-right: 4px solid #dc3545; }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }

        pre {
            background: #2d3748;
            color: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 10px 0;
        }

        .password-strength {
            height: 5px;
            background: #ddd;
            border-radius: 3px;
            margin-top: 8px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
        }

        .strength-weak { background: #e74c3c; }
        .strength-medium { background: #f39c12; }
        .strength-strong { background: #2ecc71; }

        a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 إعداد حساب الأدمن</h1>
        <p class="subtitle">Setup Administrator Account</p>

        <?php if ($existingAdmins && !$authenticated): ?>
            <!-- يوجد أدمن بالفعل -->
            <div class="box warning">
                <h3>⚠️ يوجد حسابات أدمن بالفعل</h3>
                <p style="margin-top: 10px;">يوجد <?php echo $adminCount; ?> حساب أدمن في قاعدة البيانات.</p>
                <p style="margin-top: 10px;">إذا كنت تريد إضافة أدمن جديد، الرجاء إدخال كلمة مرور الحماية.</p>
            </div>

            <div class="box info">
                <h3>🔑 كلمة مرور الحماية</h3>
                <p>افتح ملف <code>setup-admin.php</code> واقرأ كلمة المرور من السطر 13:</p>
                <pre>define('SETUP_PASSWORD', 'setup2026');</pre>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label>كلمة مرور الحماية</label>
                    <input type="password" name="setup_password" placeholder="أدخل كلمة مرور الحماية" required>
                </div>
                <button type="submit" class="btn btn-primary">🔓 متابعة</button>
            </form>

            <div class="box" style="margin-top: 20px; text-align: center;">
                <p>تريد تسجيل الدخول بدلاً من إنشاء حساب جديد؟</p>
                <a href="login.php" class="btn btn-success" style="display: inline-block; margin-top: 10px; text-decoration: none;">تسجيل الدخول</a>
            </div>

        <?php elseif (!$authenticated): ?>
            <!-- لا يوجد أدمن - طلب كلمة مرور الحماية -->
            <div class="box info">
                <h3>ℹ️ مرحباً بك!</h3>
                <p>لإنشاء أول حساب أدمن، الرجاء إدخال كلمة مرور الحماية.</p>
            </div>

            <div class="box warning">
                <h3>🔑 كلمة مرور الحماية</h3>
                <p>افتح ملف <code>setup-admin.php</code> واقرأ كلمة المرور من السطر 13:</p>
                <pre>define('SETUP_PASSWORD', 'setup2026');</pre>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label>كلمة مرور الحماية</label>
                    <input type="password" name="setup_password" placeholder="أدخل كلمة مرور الحماية" required>
                </div>
                <button type="submit" class="btn btn-primary">🔓 متابعة</button>
            </form>

            <?php if ($error): ?>
                <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: '<?php echo addslashes($error); ?>',
                        confirmButtonText: 'موافق'
                    });
                </script>
            <?php endif; ?>

        <?php elseif ($success): ?>
            <!-- تم إنشاء الحساب بنجاح -->
            <div class="box success">
                <h3>✅ تم إنشاء حساب الأدمن بنجاح!</h3>
                <p style="margin-top: 15px;">يمكنك الآن تسجيل الدخول باستخدام البيانات التالية:</p>
                <pre style="background: white; color: #333; border: 2px solid #28a745;">البريد الإلكتروني: <?php echo htmlspecialchars($_POST['email']); ?>
كلمة المرور: [التي قمت بإدخالها]</pre>
            </div>

            <div class="box error">
                <h3>🚨 مهم جداً!</h3>
                <p><strong>احذف ملف setup-admin.php الآن من السيرفر!</strong></p>
                <pre>rm setup-admin.php</pre>
                <p style="margin-top: 10px;">هذا مهم جداً لأسباب أمنية. لا تترك هذا الملف على السيرفر!</p>
            </div>

            <a href="login.php" class="btn btn-success" style="text-decoration: none;">🔐 الذهاب لتسجيل الدخول</a>

            <form method="POST" style="margin-top: 20px;">
                <button type="submit" name="add_more" class="btn btn-primary">➕ إضافة أدمن آخر</button>
            </form>

            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'تم بنجاح!',
                    text: 'تم إنشاء حساب الأدمن. لا تنسَ حذف ملف setup-admin.php!',
                    confirmButtonText: 'موافق',
                    confirmButtonColor: '#2ecc71'
                });
            </script>

        <?php else: ?>
            <!-- نموذج إنشاء حساب الأدمن -->
            <div class="box info">
                <h3>📝 إنشاء حساب أدمن جديد</h3>
                <p>املأ البيانات أدناه لإنشاء حساب مدير النظام.</p>
            </div>

            <form method="POST" id="adminForm">
                <div class="form-group">
                    <label>اسم المستخدم</label>
                    <input type="text" name="username" id="username" placeholder="أدخل اسم المستخدم" required minlength="3">
                </div>

                <div class="form-group">
                    <label>البريد الإلكتروني</label>
                    <input type="email" name="email" id="email" placeholder="admin@example.com" required>
                </div>

                <div class="form-group">
                    <label>كلمة المرور</label>
                    <input type="password" name="password" id="password" placeholder="أدخل كلمة مرور قوية" required minlength="6" oninput="checkPasswordStrength()">
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <small id="strengthText" style="display: block; margin-top: 5px; color: #666;"></small>
                </div>

                <div class="form-group">
                    <label>تأكيد كلمة المرور</label>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="أعد إدخال كلمة المرور" required minlength="6">
                </div>

                <button type="submit" name="create_admin" class="btn btn-success">✨ إنشاء حساب الأدمن</button>
            </form>

            <?php if ($error): ?>
                <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: '<?php echo addslashes($error); ?>',
                        confirmButtonText: 'موافق'
                    });
                </script>
            <?php endif; ?>

            <div class="box warning" style="margin-top: 20px;">
                <h3>💡 تذكير</h3>
                <ul style="margin-right: 20px; line-height: 1.8;">
                    <li>استخدم كلمة مرور قوية (6 أحرف على الأقل)</li>
                    <li>احفظ بيانات الدخول في مكان آمن</li>
                    <li>احذف ملف setup-admin.php بعد الانتهاء</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');

            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;

            const percentage = (strength / 5) * 100;
            strengthBar.style.width = percentage + '%';

            if (strength <= 2) {
                strengthBar.className = 'password-strength-bar strength-weak';
                strengthText.textContent = 'ضعيفة ⚠️';
                strengthText.style.color = '#e74c3c';
            } else if (strength <= 3) {
                strengthBar.className = 'password-strength-bar strength-medium';
                strengthText.textContent = 'متوسطة 👍';
                strengthText.style.color = '#f39c12';
            } else {
                strengthBar.className = 'password-strength-bar strength-strong';
                strengthText.textContent = 'قوية ✅';
                strengthText.style.color = '#2ecc71';
            }
        }

        // التحقق من تطابق كلمات المرور عند الإرسال
        document.getElementById('adminForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: 'كلمات المرور غير متطابقة',
                    confirmButtonText: 'موافق'
                });
            }
        });
    </script>
</body>
</html>
