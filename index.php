<?php
require_once 'config.php';
require_once 'auth.php';

$auth = new Auth($pdo);

// إذا كان المستخدم مسجل دخول، إعادة توجيه لصفحته
if ($auth->checkSession()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('reader/dashboard.php');
    }
}

// إعادة توجيه لصفحة تسجيل الدخول
redirect('login.php');
?>
