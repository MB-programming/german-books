<?php
require_once '../config.php';
require_once '../auth.php';

header('Content-Type: application/json');

$auth = new Auth($pdo);

if (!$auth->checkSession()) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit();
}

$user = $auth->getCurrentUser();
$bookId = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
$currentPage = isset($_POST['current_page']) ? intval($_POST['current_page']) : 0;
$totalPages = isset($_POST['total_pages']) ? intval($_POST['total_pages']) : 0;

if ($bookId > 0 && $currentPage > 0) {
    $progressPercentage = $totalPages > 0 ? ($currentPage / $totalPages) * 100 : 0;

    try {
        // التحقق من وجود سجل سابق
        $checkStmt = $pdo->prepare("SELECT id FROM reading_progress WHERE user_id = ? AND book_id = ?");
        $checkStmt->execute([$user['id'], $bookId]);

        if ($checkStmt->fetch()) {
            // تحديث السجل الموجود
            $stmt = $pdo->prepare("UPDATE reading_progress
                                  SET current_page = ?, total_pages = ?, progress_percentage = ?
                                  WHERE user_id = ? AND book_id = ?");
            $stmt->execute([$currentPage, $totalPages, $progressPercentage, $user['id'], $bookId]);
        } else {
            // إنشاء سجل جديد
            $stmt = $pdo->prepare("INSERT INTO reading_progress (user_id, book_id, current_page, total_pages, progress_percentage)
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user['id'], $bookId, $currentPage, $totalPages, $progressPercentage]);
        }

        echo json_encode(['success' => true, 'message' => 'تم حفظ التقدم']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة']);
}
?>
