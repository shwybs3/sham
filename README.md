# DarkStore — دليل التثبيت على InfinityFree

متجر أدوات وخدمات برمجية رقمية (بوتات تيليغرام، سكريبتات PHP، قوالب ويب، أدوات API)،
مبني بـ PHP خالص بدون frameworks، ومُجهَّز خصيصاً للعمل على استضافة InfinityFree المجانية.

---

## 1) المتطلبات

- استضافة تدعم PHP 8.0+ مع `PDO_MySQL` أو `PDO_SQLite` (متوفرة افتراضياً على InfinityFree)
- دعم `.htaccess` (mod_rewrite, mod_headers, mod_deflate, mod_expires) — متوفر على InfinityFree
- قاعدة بيانات MySQL من لوحة cPanel (أو تعمل تلقائياً بخطة SQLite الاحتياطية بدونها)

---

## 2) رفع الملفات

ارفع جميع محتويات المجلد إلى `htdocs/` (وليس `public_html`، فهذا مسار InfinityFree) عبر
File Manager في لوحة vPanel أو عبر FTP (بيانات FTP موجودة في vPanel ← FTP Accounts).

---

## 3) اسم الموقع والدومين على InfinityFree

InfinityFree يوفّر عدة طرق لتسمية موقعك:

1. **دومين مجاني خاص بك**: سجّل دومين مجاني من أي مزوّد (مثل Freenom بدائله الحالية، أو
   أي مسجّل نطاقات) بالاسم `darkstore` أو ما شابه، ثم أضِفه من vPanel ← **Addon Domains**.
2. **نطاق فرعي مجاني من InfinityFree نفسه**: من صفحة إنشاء الحساب أو من قسم **Domains**
   في لوحة التحكم، يمكنك عادة الحصول على نطاق فرعي مجاني تحت أحد النطاقات التي يوفرها
   InfinityFree (تتغيّر القائمة من وقت لآخر — راجع الخيارات المتاحة فعلياً في حسابك عند
   التسجيل، مثل `something.infinityfreeapp.com` أو نطاقات مشابهة يقترحها الموقع).

بعد ربط الدومين، عدّل `SITE_URL` تلقائياً يُكتشف من `$_SERVER['HTTP_HOST']` في `config.php`
فلا حاجة لتعديله يدوياً في معظم الحالات.

---

## 4) قاعدة البيانات (MySQL خارجية عبر cPanel)

1. من vPanel ← **MySQL Databases**: أنشئ قاعدة بيانات جديدة (سيُضاف بادئة تلقائياً مثل
   `epiz_XXXXXXX_darkstore`) ومستخدماً مرتبطاً بها بكامل الصلاحيات.
2. من نفس الصفحة أو من **Remote MySQL**، لاحظ اسم الـ **Host** (عادة شيء مثل
   `sqlXXX.epizy.com` وليس `localhost`).
3. افتح `config.php` وعدّل:
   ```php
   define('DB_HOST', 'sqlXXX.epizy.com');
   define('DB_NAME', 'epiz_XXXXXXX_darkstore');
   define('DB_USER', 'epiz_XXXXXXX');
   define('DB_PASS', 'كلمة_مرور_قاعدة_البيانات');
   ```
4. **لا حاجة لاستيراد أي ملف SQL يدوياً** — جميع الجداول والبيانات الافتراضية تُنشأ تلقائياً
   عند أول زيارة للموقع (دالة `install_db()` في `config.php`).
5. إن تعذّر الاتصال بـ MySQL لأي سبب (بيانات خاطئة، أو Remote MySQL غير مفعّل)، يتحوّل
   الموقع تلقائياً لقاعدة SQLite محلية (`data/store.sqlite`) حتى لا يتعطّل الموقع بالكامل —
   ستظهر رسالة تنبيه بذلك أعلى لوحة التحكم مع توضيح كيفية الإصلاح.

---

## 5) إنشاء حساب الأدمن (كلمة مرور مشفّرة، لا تُخزَّن أبداً كنص صريح)

افتح مرة واحدة فقط من المتصفح:
```
https://yourdomain.com/install/create-admin.php?u=admin&p=كلمة_مرور_قوية_جداً
```
- استخدم كلمة مرور 10 أحرف على الأقل، تتضمن أرقاماً ورموزاً.
- **احذف مجلد `install/` بالكامل من السيرفر فوراً بعد التنفيذ** — يحتوي على سكريبت
  إنشاء الأدمن الذي لا داعي لبقائه بعد الاستخدام.

سجّل الدخول من: `https://yourdomain.com/admin.php`

---

## 6) إعداد الحماية من الكابتشا والبوتات

من لوحة التحكم ← **الإعدادات** ← قسم **Cloudflare Turnstile (CAPTCHA)**:

1. أنشئ حساباً مجانياً على [Cloudflare](https://dash.cloudflare.com/) إن لم يكن لديك.
2. من قسم **Turnstile** في لوحة Cloudflare، أنشئ Widget جديد واحصل على
   **Site Key** و **Secret Key**.
3. الصق المفتاحين في الإعدادات وفعّل خيار "تفعيل CAPTCHA".

بمجرد التفعيل، يظهر تحدي الكابتشا تلقائياً في: تسجيل دخول الأدمن، تأكيد الدفع (checkout)،
نموذج التواصل، ونموذج طلب الاسترداد.

**بدون Cloudflare Turnstile أيضاً**، الموقع محمي بحد أقصى تلقائي لعدد المحاولات لكل IP
(rate limiting) على نفس النماذج — قيمه قابلة للتعديل من نفس صفحة الإعدادات، قسم
"الحماية من الفلود والبوتات".

---

## 7) عنوان محفظة USDT

من لوحة التحكم ← الإعدادات ← قسم **الدفع**، ضع عنوان محفظتك الحقيقية (يجب أن تدعم
شبكتي TRC20 و ERC20). كل طلب دفع يُخزَّن في جدول `orders` بحالة `pending` حتى يتحقق
الأدمن يدوياً من `tx_hash` ويعلّمه كـ `paid` من صفحة **الطلبات**.

---

## 8) السيو (SEO) — Sitemap / RSS / Robots

جاهزة تلقائياً بدون أي إعداد إضافي:
- `https://yourdomain.com/robots.txt` → يُعاد كتابته إلى `robots.php`
- `https://yourdomain.com/sitemap.xml` → يُعاد كتابته إلى `sitemap.php` (يضم كل منتج نشط)
- `https://yourdomain.com/rss.xml` → يُعاد كتابته إلى `rss.php` (آخر 50 منتج)

قدّم رابط `sitemap.xml` في Google Search Console وBing Webmaster Tools بعد ربط الدومين.

---

## 9) هيكل الملفات

```
index.php            → الصفحة الرئيسية
product.php           → صفحة المنتج (?slug=)
checkout.php          → صفحة الدفع وتأكيد الطلب
admin.php             → لوحة التحكم الكاملة
about.php, contact.php, privacy-policy.php, terms.php,
refund-policy.php, cookie-policy.php → الصفحات الثابتة والسياسات
robots.php, sitemap.php, rss.php → ملفات السيو
config.php            → الاتصال بقاعدة البيانات + كل الدوال المساعدة والحماية
header.php, footer.php, style.css → القالب المشترك
install/create-admin.php → إنشاء/تحديث حساب الأدمن (احذفه بعد الاستخدام)
```

---

## 10) ملخص تحسينات الحماية (Red Team hardening)

- تشفير كلمة مرور الأدمن بـ `password_hash`/`password_verify` (لا تخزين نصي صريح).
- حماية CSRF على كل نموذج POST (تسجيل الدخول، الدفع، التواصل، الاسترداد، إعدادات الأدمن).
- تحديد معدل الطلبات (Rate Limiting) لكل IP على: تسجيل الدخول، الدفع، والتواصل — قابل
  للتعديل من لوحة التحكم.
- كابتشا Cloudflare Turnstile اختيارية على نفس النماذج الحساسة.
- حقل Honeypot خفي في نموذج التواصل لصد البوتات الأساسية دون إزعاج المستخدم الحقيقي.
- جلسات آمنة: `HttpOnly`, `SameSite=Lax`, وعلم `Secure` تلقائياً عند HTTPS.
- `session_regenerate_id()` عند كل تسجيل دخول ناجح لمنع Session Fixation.
- رؤوس أمان HTTP عبر `.htaccess`: `X-Frame-Options`, `X-Content-Type-Options`,
  `Referrer-Policy`, `Permissions-Policy`.
- منع الوصول المباشر لـ `config.php` وملفات `.sql/.log/.env/.md` عبر `.htaccess`.
- جميع استعلامات قاعدة البيانات عبر Prepared Statements (لا SQL injection).
- كل مخرجات المستخدم تمر عبر `htmlspecialchars()` (دالة `clean()`/`h()`) لمنع XSS.
- صفحة تسجيل دخول الأدمن ولوحة التحكم تحمل `robots: noindex, nofollow`.

---

## 11) الهوية البصرية

- خلفية داكنة `#0a0a0f` / نيون سيان `#00f0ff` / أحمر `#ff0055` / بنفسجي `#7c3aed`
- خط Cairo لكل النصوص، دعم كامل لـ RTL (عربي) و LTR (إنجليزي) مع تبديل فوري.
