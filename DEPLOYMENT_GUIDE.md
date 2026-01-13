# 🚀 دليل النشر على الاستضافة الحقيقية

## معلومات الاستضافة

**الموقع:** https://netlabacademy.com/
**قاعدة البيانات:** u186120816_books
**المستخدم:** u186120816_minaboulesf3
**كلمة المرور:** yd+I*aN6

## 📋 خطوات النشر

### 1. رفع الملفات

#### عبر FTP/SFTP:
1. قم بتحميل جميع ملفات المشروع
2. ارفعها إلى المجلد الرئيسي (public_html أو httpdocs)
3. تأكد من رفع جميع المجلدات والملفات بما فيها:
   - admin/
   - reader/
   - uploads/
   - جميع ملفات PHP
   - database.sql
   - install.php

#### باستخدام Git:
```bash
cd /path/to/public_html
git clone [repository-url]
cd german-books
```

### 2. ضبط الصلاحيات

```bash
# صلاحيات المجلدات
chmod 755 uploads/
chmod 755 uploads/books/
chmod 755 uploads/audio/
chmod 755 uploads/covers/
chmod 755 uploads/qr/

# صلاحيات الملفات
chmod 644 config.php
chmod 644 *.php
chmod 644 database.sql
```

### 3. تثبيت قاعدة البيانات

#### الطريقة الأولى: استخدام install.php (موصى بها)

1. افتح المتصفح واذهب إلى:
   ```
   https://netlabacademy.com/install.php
   ```

2. ستظهر صفحة التثبيت مع المعلومات التالية:
   - اسم القاعدة: u186120816_books
   - المستخدم: u186120816_minaboulesf3
   - المضيف: localhost

3. اضغط على زر "🚀 بدء التثبيت"

4. انتظر حتى يكتمل التثبيت (عداد التقدم سيصل إلى 100%)

5. **مهم جداً:** احذف ملف install.php فوراً بعد التثبيت:
   ```bash
   rm install.php
   ```

#### الطريقة الثانية: phpMyAdmin

1. سجل دخول إلى cPanel
2. افتح phpMyAdmin
3. اختر قاعدة البيانات: u186120816_books
4. اضغط على "Import"
5. اختر ملف database.sql
6. اضغط "Go"

### 4. التحقق من التثبيت

1. افتح الموقع:
   ```
   https://netlabacademy.com/
   ```

2. يجب أن تظهر الصفحة الرئيسية مع:
   - عنوان: "منصة الكتب الرقمية التعليمية"
   - إحصائيات (عدد الكتب، الملفات الصوتية)
   - قسم المميزات

3. جرب تسجيل الدخول:
   - **Admin:** admin@bookplatform.com / admin123
   - **Reader:** reader@bookplatform.com / reader123

### 5. تفعيل SEO

#### Google Search Console

1. اذهب إلى: https://search.google.com/search-console
2. أضف الموقع: https://netlabacademy.com
3. تحقق من الملكية
4. أرسل Sitemap:
   ```
   https://netlabacademy.com/sitemap.php
   ```

#### Bing Webmaster Tools

1. اذهب إلى: https://www.bing.com/webmasters
2. أضف الموقع
3. أرسل Sitemap:
   ```
   https://netlabacademy.com/sitemap.php
   ```

### 6. تحسينات إضافية

#### SSL Certificate

تأكد من أن SSL مفعّل:
```
https://netlabacademy.com ✅
```

إذا لم يكن مفعلاً:
1. اذهب إلى cPanel
2. SSL/TLS
3. فعّل Let's Encrypt SSL (مجاني)

#### .htaccess (إعادة توجيه HTTPS)

أضف في ملف `.htaccess`:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

#### Gzip Compression

أضف في ملف `.htaccess`:
```apache
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/css text/javascript application/javascript application/json
</IfModule>
```

#### Browser Caching

أضف في ملف `.htaccess`:
```apache
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/jpg "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType text/css "access plus 1 month"
  ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

## 🔧 استيراد الكتب الموجودة

إذا كانت لديك كتب في مجلد `books/`:

```bash
cd /path/to/netlabacademy.com
php import-existing-books.php
```

سيقوم بـ:
- استيراد جميع ملفات PDF من مجلد books/
- إنشاء أسماء فريدة تلقائياً
- إضافتها لقاعدة البيانات
- تحديد اللغة تلقائياً

## 📊 المراقبة والصيانة

### فحص الأخطاء

```bash
# عرض سجل الأخطاء
tail -f error.log

# مسح سجل الأخطاء (بعد المراجعة)
> error.log
```

### النسخ الاحتياطي

#### قاعدة البيانات:
```bash
mysqldump -u u186120816_minaboulesf3 -p u186120816_books > backup_$(date +%Y%m%d).sql
```

#### الملفات:
```bash
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz uploads/
```

### التحديثات

```bash
# Pull latest changes
git pull origin main

# Clear cache (if any)
php artisan cache:clear  # If using Laravel
```

## 🎯 قائمة التحقق

### قبل النشر
- [x] رفع جميع الملفات
- [x] ضبط الصلاحيات
- [x] تشغيل install.php
- [x] حذف install.php
- [x] اختبار تسجيل الدخول
- [x] التحقق من عرض الصفحات

### بعد النشر
- [ ] إرسال Sitemap لـ Google
- [ ] إرسال Sitemap لـ Bing
- [ ] تفعيل SSL
- [ ] تفعيل Gzip
- [ ] تفعيل Browser Caching
- [ ] اختبار Page Speed
- [ ] اختبار Mobile Friendly
- [ ] استيراد الكتب الموجودة

### SEO
- [ ] التحقق من Meta Tags
- [ ] التحقق من Structured Data
- [ ] التحقق من Sitemap
- [ ] التحقق من Robots.txt
- [ ] التحقق من Canonical URLs
- [ ] اختبار Rich Results

## 🔐 الأمان

### تغيير كلمات المرور الافتراضية

**مهم جداً:** غيّر كلمات المرور بعد التثبيت:

```sql
-- تغيير كلمة مرور الأدمن
UPDATE users
SET password = '$2y$10$[NEW_HASH]'
WHERE email = 'admin@bookplatform.com';

-- تغيير كلمة مرور القارئ
UPDATE users
SET password = '$2y$10$[NEW_HASH]'
WHERE email = 'reader@bookplatform.com';
```

لتوليد hash جديد:
```php
<?php
echo password_hash('كلمة_المرور_الجديدة', PASSWORD_BCRYPT);
?>
```

### حماية الملفات الحساسة

في ملف `.htaccess`:
```apache
<FilesMatch "(config\.php|auth\.php|seo-functions\.php|install\.php)">
    Order Allow,Deny
    Deny from all
</FilesMatch>
```

### تعطيل عرض الأخطاء في الإنتاج

في `config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 0);  // ✅ Already set
ini_set('log_errors', 1);      // ✅ Already set
```

## 🆘 حل المشاكل

### المشكلة: صفحة بيضاء (White Screen)

**الحل:**
```bash
# تفعيل عرض الأخطاء مؤقتاً
vim config.php
# غيّر: ini_set('display_errors', 1);

# أو افحص error.log
tail error.log
```

### المشكلة: خطأ في الاتصال بقاعدة البيانات

**الحل:**
1. تحقق من بيانات الاتصال في `config.php`
2. تأكد من أن قاعدة البيانات موجودة
3. تحقق من صلاحيات المستخدم

### المشكلة: الصور لا تظهر

**الحل:**
```bash
# تحقق من الصلاحيات
chmod 755 uploads/
chmod 755 uploads/covers/

# تحقق من المسارات في config.php
```

### المشكلة: Sitemap لا يعمل

**الحل:**
```bash
# تأكد من وجود mod_rewrite
vim .htaccess

# أضف:
RewriteRule ^sitemap\.xml$ sitemap.php [L]
```

## 📞 الدعم

### الملفات المهمة
- `README.md` - التوثيق الكامل
- `PAYMENT_SYSTEM.md` - دليل نظام الدفع
- `SEO_GUIDE.md` - دليل تحسين SEO
- `DEPLOYMENT_GUIDE.md` - هذا الملف

### الموارد
- Google Search Console: https://search.google.com/search-console
- PageSpeed Insights: https://pagespeed.web.dev/
- Schema Validator: https://validator.schema.org/
- SSL Test: https://www.ssllabs.com/ssltest/

---

**تم التطوير بواسطة Claude** ❤️

**NetLab Academy** - منصة تعليمية متكاملة
