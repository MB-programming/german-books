<?php
require_once '../config.php';
require_once '../auth.php';

$auth = new Auth($pdo);

if (!$auth->checkSession()) {
    redirect('../login.php');
}

$user = $auth->getCurrentUser();
$bookId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($bookId > 0) {
    try {
        $stmt = $pdo->prepare("INSERT INTO saved_books (user_id, book_id) VALUES (?, ?)");
        $stmt->execute([$user['id'], $bookId]);
    } catch (PDOException $e) {
        // قد يكون الكتاب محفوظاً بالفعل
    }
}

redirect('dashboard.php');
?>
