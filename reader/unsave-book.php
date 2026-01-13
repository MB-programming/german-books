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
    $stmt = $pdo->prepare("DELETE FROM saved_books WHERE user_id = ? AND book_id = ?");
    $stmt->execute([$user['id'], $bookId]);
}

redirect('dashboard.php');
?>
