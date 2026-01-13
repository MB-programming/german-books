<?php
require_once 'config.php';
require_once 'auth.php';

$auth = new Auth($pdo);
$error = '';
$success = '';

// إذا كان المستخدم مسجل دخول بالفعل
if ($auth->checkSession()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('reader/dashboard.php');
    }
}

// معالجة تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'الرجاء إدخال البريد الإلكتروني وكلمة المرور';
    } else {
        if ($auth->login($email, $password)) {
            if (isAdmin()) {
                redirect('admin/dashboard.php');
            } else {
                redirect('reader/dashboard.php');
            }
        } else {
            $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
        }
    }
}

// معالجة التسجيل
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = cleanInput($_POST['username']);
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'الرجاء ملء جميع الحقول';
    } elseif ($password !== $confirmPassword) {
        $error = 'كلمات المرور غير متطابقة';
    } elseif (strlen($password) < 6) {
        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    } else {
        $result = $auth->register($username, $email, $password);
        if ($result['success']) {
            $success = $result['message'] . ' - يمكنك الآن تسجيل الدخول';
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            display: flex;
        }

        .form-section {
            flex: 1;
            padding: 50px;
        }

        .info-section {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }

        .tabs {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 16px;
            color: #666;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }

        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .form-container {
            display: none;
        }

        .form-container.active {
            display: block;
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

        button[type="submit"] {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
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

        .info-section h2 {
            font-size: 32px;
            margin-bottom: 20px;
        }

        .info-section ul {
            list-style: none;
            margin-top: 20px;
        }

        .info-section li {
            padding: 10px 0;
            padding-right: 25px;
            position: relative;
        }

        .info-section li:before {
            content: "✓";
            position: absolute;
            right: 0;
            color: #a8ff78;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .form-section, .info-section {
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-section">
            <h1>مرحباً بك</h1>
            <p class="subtitle">منصة الكتب الرقمية مع الصوتيات التفاعلية</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="tabs">
                <button class="tab active" onclick="switchTab('login')">تسجيل الدخول</button>
                <button class="tab" onclick="switchTab('register')">إنشاء حساب</button>
            </div>

            <!-- نموذج تسجيل الدخول -->
            <div id="login-form" class="form-container active">
                <form method="POST">
                    <div class="form-group">
                        <label>البريد الإلكتروني</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>كلمة المرور</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" name="login">تسجيل الدخول</button>
                </form>
                <p style="margin-top: 20px; color: #666; font-size: 14px;">
                    حساب تجريبي للأدمن:<br>
                    <strong>admin@bookplatform.com / admin123</strong><br><br>
                    حساب تجريبي للقارئ:<br>
                    <strong>reader@bookplatform.com / reader123</strong>
                </p>
            </div>

            <!-- نموذج التسجيل -->
            <div id="register-form" class="form-container">
                <form method="POST">
                    <div class="form-group">
                        <label>اسم المستخدم</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>البريد الإلكتروني</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>كلمة المرور</label>
                        <input type="password" name="password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>تأكيد كلمة المرور</label>
                        <input type="password" name="confirm_password" required minlength="6">
                    </div>
                    <button type="submit" name="register">إنشاء حساب</button>
                </form>
            </div>
        </div>

        <div class="info-section">
            <h2>منصة الكتب الرقمية</h2>
            <p>استمتع بتجربة قراءة فريدة مع الصوتيات التفاعلية</p>
            <ul>
                <li>قارئ PDF آمن وسلس</li>
                <li>صوتيات تفاعلية عبر QR Codes</li>
                <li>تتبع تقدم القراءة</li>
                <li>حفظ الكتب المفضلة</li>
                <li>واجهة سهلة الاستخدام</li>
            </ul>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            // إخفاء جميع النماذج
            document.querySelectorAll('.form-container').forEach(el => {
                el.classList.remove('active');
            });

            // إزالة التنشيط من جميع التبويبات
            document.querySelectorAll('.tab').forEach(el => {
                el.classList.remove('active');
            });

            // تفعيل التبويب والنموذج المحدد
            if (tab === 'login') {
                document.getElementById('login-form').classList.add('active');
                document.querySelectorAll('.tab')[0].classList.add('active');
            } else {
                document.getElementById('register-form').classList.add('active');
                document.querySelectorAll('.tab')[1].classList.add('active');
            }
        }
    </script>
</body>
</html>
