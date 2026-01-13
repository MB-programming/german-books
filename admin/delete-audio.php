<?php
require_once '../config.php';
require_once '../auth.php';

$auth = new Auth($pdo);

if (!$auth->checkSession() || !isAdmin()) {
    redirect('../login.php');
}

$audioId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$bookId = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;

if ($audioId > 0) {
    // الحصول على بيانات الملف الصوتي
    $stmt = $pdo->prepare("SELECT * FROM audio_files WHERE id = ?");
    $stmt->execute([$audioId]);
    $audio = $stmt->fetch();

    if ($audio) {
        // حذف الملف الفعلي
        $filePath = '../' . $audio['audio_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // حذف السجل من قاعدة البيانات
        $deleteStmt = $pdo->prepare("DELETE FROM audio_files WHERE id = ?");
        $deleteStmt->execute([$audioId]);
    }
}

redirect('add-audio.php?book_id=' . $bookId);
?>
