<?php
require_once 'config.php';

class Auth {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // تسجيل الدخول
    public function login($email, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['last_activity'] = time();
            return true;
        }
        return false;
    }

    // تسجيل مستخدم جديد
    public function register($username, $email, $password, $userType = 'reader') {
        // التحقق من وجود البريد الإلكتروني
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'البريد الإلكتروني مستخدم بالفعل'];
        }

        // التحقق من وجود اسم المستخدم
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'اسم المستخدم مستخدم بالفعل'];
        }

        // تشفير كلمة المرور
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // إدخال المستخدم
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, user_type) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$username, $email, $hashedPassword, $userType])) {
            return ['success' => true, 'message' => 'تم التسجيل بنجاح'];
        }
        return ['success' => false, 'message' => 'حدث خطأ أثناء التسجيل'];
    }

    // تسجيل الخروج
    public function logout() {
        session_unset();
        session_destroy();
        return true;
    }

    // التحقق من الجلسة
    public function checkSession() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // التحقق من انتهاء الجلسة
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
            $this->logout();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    // الحصول على بيانات المستخدم الحالي
    public function getCurrentUser() {
        if (!$this->checkSession()) {
            return null;
        }

        $stmt = $this->pdo->prepare("SELECT id, username, email, user_type, created_at FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
}
?>
