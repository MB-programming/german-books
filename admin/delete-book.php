<?php
require_once '../config.php';
require_once '../auth.php';

$auth = new Auth($pdo);

if (!$auth->checkSession() || !isAdmin()) {
    redirect('../login.php');
}

$bookId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($bookId > 0) {
    // الحصول على بيانات الكتاب
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$bookId]);
    $book = $stmt->fetch();

    if ($book) {
        // حذف الملف الفعلي
        $filePath = '../' . $book['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // حذف صورة الغلاف
        if ($book['cover_image'] && file_exists('../' . $book['cover_image'])) {
            unlink('../' . $book['cover_image']);
        }

        // حذف الملفات الصوتية المرتبطة
        $audioStmt = $pdo->prepare("SELECT * FROM audio_files WHERE book_id = ?");
        $audioStmt->execute([$bookId]);
        $audioFiles = $audioStmt->fetchAll();

        foreach ($audioFiles as $audio) {
            $audioPath = '../' . $audio['audio_path'];
            if (file_exists($audioPath)) {
                unlink($audioPath);
            }
        }

        // حذف السجل من قاعدة البيانات (سيتم حذف الصوتيات تلقائياً بسبب CASCADE)
        $deleteStmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        $deleteStmt->execute([$bookId]);
    }
}

redirect('books.php');
?>
