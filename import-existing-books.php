<?php
/**
 * سكريبت لاستيراد الكتب الموجودة في مجلد books/
 *
 * الاستخدام: php import-existing-books.php
 */

require_once 'config.php';

echo "=== استيراد الكتب الموجودة ===\n\n";

$booksDir = __DIR__ . '/books/';

if (!is_dir($booksDir)) {
    die("خطأ: مجلد books/ غير موجود!\n");
}

// الحصول على معرف المستخدم admin
$stmt = $pdo->prepare("SELECT id FROM users WHERE user_type = 'admin' LIMIT 1");
$stmt->execute();
$admin = $stmt->fetch();

if (!$admin) {
    die("خطأ: لا يوجد مستخدم أدمن! قم بتشغيل database.sql أولاً.\n");
}

$adminId = $admin['id'];

// البحث عن ملفات PDF
$files = glob($booksDir . '*.pdf');

if (empty($files)) {
    die("لا توجد ملفات PDF في مجلد books/\n");
}

echo "تم العثور على " . count($files) . " ملف PDF\n\n";

$imported = 0;
$skipped = 0;

foreach ($files as $file) {
    $filename = basename($file);
    $filesize = filesize($file);

    // التحقق من عدم وجود الكتاب بالفعل
    $checkStmt = $pdo->prepare("SELECT id FROM books WHERE original_filename = ?");
    $checkStmt->execute([$filename]);

    if ($checkStmt->fetch()) {
        echo "⏭️  تم تخطي: $filename (موجود بالفعل)\n";
        $skipped++;
        continue;
    }

    // توليد اسم فريد
    $uniqueFilename = generateUniqueFilename('pdf');
    $newPath = BOOKS_DIR . $uniqueFilename;

    // نسخ الملف
    if (copy($file, $newPath)) {
        // استخراج عنوان الكتاب من اسم الملف
        $title = pathinfo($filename, PATHINFO_FILENAME);
        $title = str_replace('_', ' ', $title);
        $title = str_replace('-', ' ', $title);

        // تحديد اللغة بناءً على اسم الملف
        $language = '';
        if (strpos($filename, 'ita') !== false) {
            $language = 'الإيطالية';
        } elseif (strpos($filename, 'eng') !== false) {
            $language = 'الإنجليزية';
        } elseif (preg_match('/[\x{0600}-\x{06FF}]/u', $filename)) {
            $language = 'العربية';
        } else {
            $language = 'الألمانية'; // افتراضياً
        }

        // إدخال في قاعدة البيانات
        try {
            $stmt = $pdo->prepare("INSERT INTO books
                (title, original_filename, unique_filename, file_path, file_size, uploaded_by, category, language)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $title,
                $filename,
                $uniqueFilename,
                'uploads/books/' . $uniqueFilename,
                $filesize,
                $adminId,
                'تعلم اللغات',
                $language
            ]);

            echo "✅ تم استيراد: $filename\n";
            echo "   العنوان: $title\n";
            echo "   اللغة: $language\n";
            echo "   الحجم: " . round($filesize / 1024 / 1024, 2) . " MB\n\n";

            $imported++;
        } catch (PDOException $e) {
            echo "❌ خطأ في استيراد $filename: " . $e->getMessage() . "\n\n";
            // حذف الملف المنسوخ في حالة الخطأ
            if (file_exists($newPath)) {
                unlink($newPath);
            }
        }
    } else {
        echo "❌ فشل نسخ الملف: $filename\n\n";
    }
}

echo "\n=== النتائج ===\n";
echo "تم الاستيراد: $imported كتاب\n";
echo "تم التخطي: $skipped كتاب\n";
echo "الإجمالي: " . count($files) . " ملف\n\n";

if ($imported > 0) {
    echo "✅ تم الاستيراد بنجاح! يمكنك الآن تسجيل الدخول وعرض الكتب.\n";
} else {
    echo "⚠️  لم يتم استيراد أي كتب جديدة.\n";
}

echo "\nملاحظة: يمكنك الآن حذف مجلد books/ الأصلي إذا أردت.\n";
?>
