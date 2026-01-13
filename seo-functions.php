<?php
/**
 * دوال تحسين محركات البحث (SEO)
 */

// دالة توليد Meta Tags
function generateMetaTags($title = '', $description = '', $keywords = '', $image = '', $type = 'website') {
    $title = !empty($title) ? $title . ' - ' . SITE_NAME : SITE_NAME;
    $description = !empty($description) ? $description : SITE_DESCRIPTION;
    $keywords = !empty($keywords) ? $keywords : SITE_KEYWORDS;
    $image = !empty($image) ? $image : SITE_LOGO;
    $url = getCurrentUrl();

    $tags = [];

    // Basic Meta Tags
    $tags[] = '<meta charset="UTF-8">';
    $tags[] = '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    $tags[] = '<meta http-equiv="X-UA-Compatible" content="IE=edge">';
    $tags[] = '<title>' . htmlspecialchars($title) . '</title>';
    $tags[] = '<meta name="description" content="' . htmlspecialchars($description) . '">';
    $tags[] = '<meta name="keywords" content="' . htmlspecialchars($keywords) . '">';
    $tags[] = '<meta name="author" content="' . SITE_AUTHOR . '">';
    $tags[] = '<meta name="robots" content="index, follow">';
    $tags[] = '<meta name="googlebot" content="index, follow">';
    $tags[] = '<link rel="canonical" href="' . htmlspecialchars($url) . '">';

    // Open Graph Tags (Facebook, LinkedIn)
    $tags[] = '<meta property="og:type" content="' . $type . '">';
    $tags[] = '<meta property="og:title" content="' . htmlspecialchars($title) . '">';
    $tags[] = '<meta property="og:description" content="' . htmlspecialchars($description) . '">';
    $tags[] = '<meta property="og:image" content="' . htmlspecialchars($image) . '">';
    $tags[] = '<meta property="og:url" content="' . htmlspecialchars($url) . '">';
    $tags[] = '<meta property="og:site_name" content="' . SITE_NAME . '">';
    $tags[] = '<meta property="og:locale" content="ar_AR">';

    // Twitter Card Tags
    $tags[] = '<meta name="twitter:card" content="summary_large_image">';
    $tags[] = '<meta name="twitter:title" content="' . htmlspecialchars($title) . '">';
    $tags[] = '<meta name="twitter:description" content="' . htmlspecialchars($description) . '">';
    $tags[] = '<meta name="twitter:image" content="' . htmlspecialchars($image) . '">';

    // Additional SEO Tags
    $tags[] = '<meta name="theme-color" content="#667eea">';
    $tags[] = '<meta name="msapplication-TileColor" content="#667eea">';
    $tags[] = '<link rel="alternate" hreflang="ar" href="' . htmlspecialchars($url) . '">';

    return implode("\n    ", $tags);
}

// دالة توليد JSON-LD Structured Data
function generateStructuredData($type = 'WebSite', $data = []) {
    $baseSchema = [
        '@context' => 'https://schema.org',
        '@type' => $type
    ];

    if ($type === 'WebSite') {
        $schema = array_merge($baseSchema, [
            'name' => SITE_NAME,
            'url' => SITE_URL,
            'description' => SITE_DESCRIPTION,
            'publisher' => [
                '@type' => 'Organization',
                'name' => SITE_AUTHOR,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => SITE_LOGO
                ]
            ],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => SITE_URL . '/search?q={search_term_string}',
                'query-input' => 'required name=search_term_string'
            ]
        ]);
    } elseif ($type === 'Book') {
        $schema = array_merge($baseSchema, [
            'name' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'author' => [
                '@type' => 'Person',
                'name' => $data['author'] ?? SITE_AUTHOR
            ],
            'inLanguage' => $data['language'] ?? 'ar',
            'bookFormat' => 'EBook',
            'url' => $data['url'] ?? getCurrentUrl()
        ]);

        if (!empty($data['price'])) {
            $schema['offers'] = [
                '@type' => 'Offer',
                'price' => $data['price'],
                'priceCurrency' => 'EGP',
                'availability' => 'https://schema.org/InStock'
            ];
        }
    } elseif ($type === 'Organization') {
        $schema = array_merge($baseSchema, [
            'name' => SITE_NAME,
            'url' => SITE_URL,
            'logo' => SITE_LOGO,
            'description' => SITE_DESCRIPTION,
            'sameAs' => $data['social'] ?? []
        ]);
    }

    $schema = array_merge($schema, $data);

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}

// دالة الحصول على الرابط الحالي
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    return $protocol . '://' . $host . $uri;
}

// دالة توليد Breadcrumb
function generateBreadcrumb($items) {
    if (empty($items)) return '';

    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => []
    ];

    foreach ($items as $position => $item) {
        $breadcrumbSchema['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $position + 1,
            'name' => $item['name'],
            'item' => $item['url'] ?? ''
        ];
    }

    $html = '<nav aria-label="Breadcrumb" class="breadcrumb">';
    $html .= '<ol vocab="https://schema.org/" typeof="BreadcrumbList">';

    foreach ($items as $position => $item) {
        $isLast = $position === count($items) - 1;
        $html .= '<li property="itemListElement" typeof="ListItem">';

        if (!$isLast && !empty($item['url'])) {
            $html .= '<a property="item" typeof="WebPage" href="' . htmlspecialchars($item['url']) . '">';
            $html .= '<span property="name">' . htmlspecialchars($item['name']) . '</span>';
            $html .= '</a>';
        } else {
            $html .= '<span property="name">' . htmlspecialchars($item['name']) . '</span>';
        }

        $html .= '<meta property="position" content="' . ($position + 1) . '">';
        $html .= '</li>';

        if (!$isLast) {
            $html .= '<li class="separator">/</li>';
        }
    }

    $html .= '</ol>';
    $html .= '</nav>';
    $html .= '<script type="application/ld+json">' . json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';

    return $html;
}

// دالة تحسين العناوين
function optimizeTitle($text, $maxLength = 60) {
    if (mb_strlen($text) > $maxLength) {
        return mb_substr($text, 0, $maxLength - 3) . '...';
    }
    return $text;
}

// دالة تحسين الوصف
function optimizeDescription($text, $maxLength = 160) {
    $text = strip_tags($text);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);

    if (mb_strlen($text) > $maxLength) {
        return mb_substr($text, 0, $maxLength - 3) . '...';
    }
    return $text;
}

// دالة توليد Alt Text للصور
function generateAltText($filename, $context = '') {
    $alt = pathinfo($filename, PATHINFO_FILENAME);
    $alt = str_replace(['_', '-'], ' ', $alt);
    $alt = ucwords($alt);

    if (!empty($context)) {
        $alt = $context . ' - ' . $alt;
    }

    return $alt;
}

// دالة توليد Sitemap
function generateSitemapXml($pdo) {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    // الصفحة الرئيسية
    $xml .= '<url>' . "\n";
    $xml .= '  <loc>' . SITE_URL . '</loc>' . "\n";
    $xml .= '  <changefreq>daily</changefreq>' . "\n";
    $xml .= '  <priority>1.0</priority>' . "\n";
    $xml .= '</url>' . "\n";

    // صفحة تسجيل الدخول
    $xml .= '<url>' . "\n";
    $xml .= '  <loc>' . SITE_URL . '/login.php</loc>' . "\n";
    $xml .= '  <changefreq>monthly</changefreq>' . "\n";
    $xml .= '  <priority>0.8</priority>' . "\n";
    $xml .= '</url>' . "\n";

    // جميع الكتب المجانية
    try {
        $stmt = $pdo->query("SELECT id, title, upload_date FROM books WHERE is_paid = 0 ORDER BY upload_date DESC");
        while ($book = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $xml .= '<url>' . "\n";
            $xml .= '  <loc>' . SITE_URL . '/reader/view-book.php?id=' . $book['id'] . '</loc>' . "\n";
            $xml .= '  <lastmod>' . date('Y-m-d', strtotime($book['upload_date'])) . '</lastmod>' . "\n";
            $xml .= '  <changefreq>weekly</changefreq>' . "\n";
            $xml .= '  <priority>0.9</priority>' . "\n";
            $xml .= '</url>' . "\n";
        }
    } catch (PDOException $e) {
        // Silently fail
    }

    $xml .= '</urlset>';

    return $xml;
}

// دالة إنشاء robots.txt
function generateRobotsTxt() {
    $robots = "User-agent: *\n";
    $robots .= "Allow: /\n";
    $robots .= "Disallow: /admin/\n";
    $robots .= "Disallow: /uploads/\n";
    $robots .= "Disallow: /install.php\n";
    $robots .= "Disallow: /config.php\n";
    $robots .= "\n";
    $robots .= "Sitemap: " . SITE_URL . "/sitemap.xml\n";

    return $robots;
}

// دالة توليد رابط صديق لمحركات البحث
function generateSeoUrl($text) {
    // تحويل النص العربي إلى transliteration
    $text = mb_strtolower($text, 'UTF-8');

    // إزالة الأحرف الخاصة
    $text = preg_replace('/[^ا-ي\s\-_a-z0-9]/u', '', $text);

    // استبدال المسافات بشرطات
    $text = preg_replace('/[\s_]+/', '-', $text);

    // إزالة الشرطات المتعددة
    $text = preg_replace('/-+/', '-', $text);

    // إزالة الشرطات من البداية والنهاية
    $text = trim($text, '-');

    return $text;
}
?>
