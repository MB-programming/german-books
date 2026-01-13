# 🔴 حل نهائي لمشكلة Hostinger - This Page Does Not Exist

## 🎯 المشكلة
جميع الملفات مرفوعة، لكن Hostinger يعرض:
```
❌ This Page Does Not Exist
```

---

## ✅ الحل النهائي (100% مضمون)

### الخطوة 1: اختبار PHP (ابدأ من هنا!) 🔍

**1. ارفع ملف `info.php`**

**2. افتحه:**
```
https://netlabacademy.com/info.php
```

**النتائج المحتملة:**

#### ✅ إذا ظهرت رسالة "PHP يعمل بنجاح":
→ **PHP شغال، المشكلة في .htaccess أو المسار**
→ انتقل للخطوة 2

#### ❌ إذا ظهر "This Page Does Not Exist":
→ **الملفات في المكان الخطأ**
→ انتقل للحل A

---

## 🔧 الحل A: الملفات في المكان الخطأ

### في Hostinger، يجب أن تكون الملفات في:

**✅ الصحيح:**
```
/domains/netlabacademy.com/public_html/
  ├── index.php
  ├── login.php
  ├── config.php
  ├── admin/
  ├── reader/
  └── ...
```

**❌ خطأ شائع:**
```
/domains/netlabacademy.com/public_html/book-platform/
  └── ... (الملفات هنا - خطأ!)
```

### كيفية التحقق:

**عبر File Manager:**
1. افتح **File Manager** في hPanel
2. اذهب إلى: `domains → netlabacademy.com → public_html`
3. يجب أن ترى **login.php** مباشرة (وليس في مجلد فرعي!)

**إذا كانت الملفات في مجلد فرعي:**
```bash
# انقل جميع الملفات إلى public_html مباشرة
mv /domains/netlabacademy.com/public_html/subfolder/* /domains/netlabacademy.com/public_html/
```

---

## 🔧 الحل B: مشكلة .htaccess

### حذف .htaccess مؤقتاً:

**عبر File Manager:**
1. افتح **File Manager**
2. اذهب إلى `public_html`
3. ابحث عن `.htaccess` (فعّل Show Hidden Files)
4. كليك يمين → **Delete** أو **Rename** → `.htaccess.disabled`

**اختبر الآن:**
```
https://netlabacademy.com/login.php
```

### ✅ إذا عمل:
→ المشكلة من .htaccess
→ استخدم .htaccess-hostinger (الموضح أدناه)

### ❌ إذا لم يعمل:
→ انتقل للحل C

---

## 🔧 الحل C: إنشاء .htaccess خاص بـ Hostinger

انسخ هذا الكود في ملف `.htaccess` جديد:

```apache
# Hostinger Optimized .htaccess

# PHP Settings
php_value upload_max_filesize 50M
php_value post_max_size 50M
php_value max_execution_time 300
php_value memory_limit 256M

# Disable Directory Listing
Options -Indexes

# Default Document
DirectoryIndex index.php index.html

# Charset
AddDefaultCharset UTF-8

# OPTIONAL: Uncomment if you want HTTPS redirect
# RewriteEngine On
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

**احفظ كـ:** `.htaccess` في `public_html`

---

## 🔧 الحل D: التحقق من إعدادات Domain

### في hPanel:

1. اذهب إلى **Domains**
2. اختر **netlabacademy.com**
3. تحقق من:
   - ✅ Document Root: `/domains/netlabacademy.com/public_html`
   - ✅ PHP Version: 7.4 أو أحدث

4. إذا كان Document Root مختلف:
   - اضغط **Manage**
   - غيّر Document Root إلى: `public_html`

---

## 🔧 الحل E: إزالة index.html الافتراضي

Hostinger يضع ملف `index.html` افتراضي قد يتعارض مع `index.php`:

**عبر File Manager:**
1. اذهب إلى `public_html`
2. ابحث عن ملف `index.html`
3. احذفه أو أعد تسميته

```bash
# عبر SSH
rm /domains/netlabacademy.com/public_html/index.html
```

---

## 📋 قائمة التحقق النهائية

```
[ ] 1. افتح info.php - هل يعمل؟
      ✅ نعم → المشكلة في .htaccess
      ❌ لا → الملفات في المكان الخطأ

[ ] 2. الملفات في /public_html/ مباشرة؟
      ✅ نعم
      ❌ لا → انقلها

[ ] 3. حذفت/عطلت .htaccess؟
      ✅ نعم
      ❌ جرب الآن

[ ] 4. حذفت index.html الافتراضي؟
      ✅ نعم
      ❌ احذفه

[ ] 5. PHP Version صحيح؟
      ✅ 7.4+
      ❌ غيّره في hPanel

[ ] 6. Document Root صحيح؟
      ✅ public_html
      ❌ غيّره
```

---

## 🎯 الاختبار النهائي

بعد تطبيق الحلول، اختبر هذه الروابط بالترتيب:

```
1. https://netlabacademy.com/info.php
   → يجب أن يظهر "PHP يعمل بنجاح" ✅

2. https://netlabacademy.com/
   → يجب أن تفتح الصفحة الرئيسية ✅

3. https://netlabacademy.com/login.php
   → يجب أن تفتح صفحة تسجيل الدخول ✅

4. https://netlabacademy.com/admin/
   → يجب أن تحوّل لـ login.php ✅
```

---

## 🚨 الحل السريع (جرب هذا أولاً!)

**في File Manager:**

```
1. احذف/أعد تسمية .htaccess
   .htaccess → .htaccess.disabled

2. احذف index.html إذا كان موجوداً
   index.html → حذف

3. تأكد أن الملفات في public_html مباشرة
   ✅ public_html/login.php
   ❌ public_html/website/login.php

4. اختبر: netlabacademy.com/info.php
```

---

## 📞 إذا لم يعمل أي حل

**اتصل بدعم Hostinger:**
1. افتح **Live Chat** في hPanel
2. قل: "My PHP files show 'This Page Does Not Exist'"
3. اطلب منهم التحقق من:
   - PHP handler
   - mod_rewrite
   - Document root

---

## ✅ بعد حل المشكلة

**احذف ملف الاختبار:**
```bash
rm info.php
```

**ثبّت قاعدة البيانات:**
```
https://netlabacademy.com/install-simple.php
```

---

## 💡 ملاحظات خاصة بـ Hostinger

### 1. PHP Selector
في hPanel → PHP → اختر PHP 7.4 أو أحدث

### 2. CloudFlare
إذا كنت تستخدم CloudFlare:
- امسح الـ Cache
- عطّل Development Mode مؤقتاً

### 3. SSL Certificate
تأكد من تفعيل SSL في hPanel

### 4. File Permissions
Hostinger تضبطها تلقائياً، لكن إذا أردت:
- Files: 644
- Folders: 755

---

**🎉 المشكلة يجب أن تُحل الآن!**

جرب الحل السريع أولاً، ثم الحلول بالترتيب حتى يعمل الموقع.
