<?php
// ملف اختبار بسيط جداً - إذا ظهر معناه PHP شغال
echo "✅ PHP يعمل بنجاح!<br>";
echo "📂 المجلد الحالي: " . __DIR__ . "<br>";
echo "🌐 الموقع: " . $_SERVER['HTTP_HOST'] . "<br>";
echo "📄 المسار: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "<hr>";
echo "<strong>إذا ظهرت هذه الرسالة، PHP يعمل بشكل صحيح!</strong><br>";
echo "<a href='index.php'>اختبر index.php</a> | ";
echo "<a href='login.php'>اختبر login.php</a>";
?>
