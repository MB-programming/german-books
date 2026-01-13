<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'seo-functions.php';

$auth = new Auth($pdo);

// إذا كان المستخدم مسجل دخول، إعادة توجيه لصفحته
if ($auth->checkSession()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('reader/dashboard.php');
    }
}

// إحصائيات للصفحة الرئيسية
$totalBooksStmt = $pdo->query("SELECT COUNT(*) as count FROM books");
$totalBooks = $totalBooksStmt->fetch()['count'];

$freeBooksStmt = $pdo->query("SELECT COUNT(*) as count FROM books WHERE is_paid = 0");
$freeBooks = $freeBooksStmt->fetch()['count'];

$audioStmt = $pdo->query("SELECT COUNT(*) as count FROM audio_files");
$totalAudio = $audioStmt->fetch()['count'];

// أحدث الكتب المجانية
$latestBooksStmt = $pdo->query("SELECT id, title, description, category, language, cover_image FROM books WHERE is_paid = 0 ORDER BY upload_date DESC LIMIT 6");
$latestBooks = $latestBooksStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <?php echo generateMetaTags(
        'منصة الكتب الرقمية التعليمية',
        'منصة تعليمية متكاملة للكتب الرقمية مع دعم الملفات الصوتية التفاعلية عبر QR Codes. تعلم اللغات الألمانية والإنجليزية والإيطالية بسهولة.',
        'كتب رقمية, تعلم اللغات, كتب ألمانية, كتب إنجليزية, كتب إيطالية, ملفات صوتية, QR Code, تعليم إلكتروني, NetLab Academy'
    ); ?>
    <?php echo generateStructuredData('WebSite'); ?>
    <?php echo generateStructuredData('Organization', [
        'social' => [
            'https://facebook.com/netlabacademy',
            'https://twitter.com/netlabacademy'
        ]
    ]); ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }

        /* Hero Section */
        .hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 80px 20px; text-align: center; }
        .hero h1 { font-size: 48px; margin-bottom: 20px; }
        .hero p { font-size: 20px; max-width: 800px; margin: 0 auto 30px; line-height: 1.6; }
        .hero .cta { display: inline-block; background: white; color: #667eea; padding: 15px 40px; border-radius: 8px; text-decoration: none; font-size: 18px; font-weight: bold; transition: transform 0.3s; }
        .hero .cta:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.3); }

        /* Stats Section */
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; max-width: 1200px; margin: -40px auto 60px; padding: 0 20px; }
        .stat-card { background: white; padding: 30px; border-radius: 12px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .stat-card .number { font-size: 48px; font-weight: bold; color: #667eea; margin-bottom: 10px; }
        .stat-card .label { color: #666; font-size: 16px; }

        /* Features Section */
        .features { max-width: 1200px; margin: 60px auto; padding: 0 20px; }
        .features h2 { text-align: center; font-size: 36px; margin-bottom: 50px; color: #333; }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
        .feature-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .feature-card .icon { font-size: 48px; margin-bottom: 20px; }
        .feature-card h3 { color: #333; margin-bottom: 15px; font-size: 22px; }
        .feature-card p { color: #666; line-height: 1.6; }

        /* Books Section */
        .latest-books { max-width: 1200px; margin: 60px auto; padding: 0 20px; }
        .latest-books h2 { text-align: center; font-size: 36px; margin-bottom: 50px; color: #333; }
        .books-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; }
        .book-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .book-card:hover { transform: translateY(-10px); box-shadow: 0 15px 35px rgba(0,0,0,0.2); }
        .book-card .book-cover { height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; }
        .book-card .book-info { padding: 20px; }
        .book-card h3 { color: #333; margin-bottom: 10px; font-size: 18px; }
        .book-card p { color: #666; font-size: 14px; line-height: 1.5; }
        .book-card .meta { margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap; }
        .book-card .badge { padding: 5px 10px; border-radius: 12px; font-size: 12px; background: #e9ecef; color: #495057; }

        /* Footer */
        footer { background: #2c3e50; color: white; padding: 40px 20px; margin-top: 80px; text-align: center; }
        footer p { margin: 10px 0; }
        footer a { color: #3498db; text-decoration: none; }
        footer a:hover { text-decoration: underline; }

        @media (max-width: 768px) {
            .hero h1 { font-size: 32px; }
            .hero p { font-size: 16px; }
            .stat-card .number { font-size: 36px; }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <h1>📚 منصة الكتب الرقمية التعليمية</h1>
        <p>تعلم اللغات والمهارات الجديدة من خلال مكتبة رقمية شاملة مع دعم الملفات الصوتية التفاعلية عبر QR Codes. استمتع بتجربة تعليمية فريدة تجمع بين القراءة والاستماع.</p>
        <a href="login.php" class="cta">ابدأ التعلم الآن</a>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <article class="stat-card">
            <div class="number"><?php echo $totalBooks; ?></div>
            <div class="label">كتاب رقمي</div>
        </article>
        <article class="stat-card">
            <div class="number"><?php echo $freeBooks; ?></div>
            <div class="label">كتاب مجاني</div>
        </article>
        <article class="stat-card">
            <div class="number"><?php echo $totalAudio; ?></div>
            <div class="label">ملف صوتي</div>
        </article>
    </section>

    <!-- Features Section -->
    <section class="features">
        <h2>لماذا تختار منصتنا؟</h2>
        <div class="features-grid">
            <article class="feature-card">
                <div class="icon">📖</div>
                <h3>مكتبة ضخمة</h3>
                <p>الوصول إلى مكتبة شاملة من الكتب الرقمية في مختلف المجالات والل غات</p>
            </article>
            <article class="feature-card">
                <div class="icon">🎵</div>
                <h3>صوتيات تفاعلية</h3>
                <p>ملفات صوتية مدمجة عبر QR Codes لتحسين تجربة التعلم والنطق الصحيح</p>
            </article>
            <article class="feature-card">
                <div class="icon">📱</div>
                <h3>قراءة سلسة</h3>
                <p>قارئ PDF متقدم وآمن مع إمكانية تتبع التقدم وحفظ الصفحات</p>
            </article>
            <article class="feature-card">
                <div class="icon">🌍</div>
                <h3>تعلم اللغات</h3>
                <p>كتب متخصصة في تعلم اللغات الألمانية والإنجليزية والإيطالية وغيرها</p>
            </article>
            <article class="feature-card">
                <div class="icon">💾</div>
                <h3>حفظ التقدم</h3>
                <p>نظام تلقائي لحفظ تقدمك في القراءة والعودة من حيث توقفت</p>
            </article>
            <article class="feature-card">
                <div class="icon">🔒</div>
                <h3>آمن ومحمي</h3>
                <p>نظام أمان متقدم لحماية بياناتك ومحتوياتك التعليمية</p>
            </article>
        </div>
    </section>

    <!-- Latest Books Section -->
    <?php if (count($latestBooks) > 0): ?>
    <section class="latest-books">
        <h2>أحدث الكتب المجانية</h2>
        <div class="books-grid">
            <?php foreach ($latestBooks as $book): ?>
                <article class="book-card" itemscope itemtype="https://schema.org/Book">
                    <div class="book-cover">📚</div>
                    <div class="book-info">
                        <h3 itemprop="name"><?php echo htmlspecialchars($book['title']); ?></h3>
                        <p itemprop="description"><?php echo htmlspecialchars(optimizeDescription($book['description'] ?: 'كتاب تعليمي مميز', 100)); ?></p>
                        <div class="meta">
                            <?php if ($book['category']): ?>
                                <span class="badge" itemprop="genre"><?php echo htmlspecialchars($book['category']); ?></span>
                            <?php endif; ?>
                            <?php if ($book['language']): ?>
                                <span class="badge" itemprop="inLanguage"><?php echo htmlspecialchars($book['language']); ?></span>
                            <?php endif; ?>
                        </div>
                        <meta itemprop="bookFormat" content="EBook">
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div style="text-align: center; margin-top: 40px;">
            <a href="login.php" class="cta" style="background: #667eea; color: white;">عرض جميع الكتب</a>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer>
        <p><strong><?php echo SITE_NAME; ?></strong></p>
        <p>منصة تعليمية متكاملة للكتب الرقمية مع الصوتيات التفاعلية</p>
        <p>&copy; <?php echo date('Y'); ?> NetLab Academy. جميع الحقوق محفوظة.</p>
        <p><a href="sitemap.php">خريطة الموقع</a> | <a href="robots.txt">Robots.txt</a></p>
    </footer>
</body>
</html>