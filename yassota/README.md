# YASSOTA — منصة اجتماعية عربية حديثة

منصة تواصل عربية (تجربة تجمع Instagram/TikTok) حيث **كل منشور صفحة ويب مستقلة قابلة للفهرسة** في محركات البحث، مع تطبيق أندرويد رسمي مرتبط بنفس الـBackend وقاعدة البيانات.

مبنية بـ **PHP + MySQL** فقط لتعمل على InfinityFree وأي استضافة PHP.

## القرارات المعمارية

- **PHP+MySQL خالص (بلا إطار):** يعمل على InfinityFree، نشر بالرفع فقط، SSR كامل للفهرسة.
- **SSR لكل منشور:** `/post/{slug}` يُصيّر من الخادم مع JSON-LD كامل.
- **الدردشة بالـPolling:** InfinityFree لا يدعم WebSocket؛ للـReal-time انقل الدردشة لـVPS/Pusher لاحقاً.
- **روابط نظيفة عبر `.htaccess`** (مدعومة على Apache/InfinityFree).
- **تحسين الصور إلى WebP عبر GD** مع احتياطي لحفظ الأصل.
- **الأسرار خارج git:** `config.local.php` يكتبه معالج الإعداد؛ مفاتيح جوجل/SEO في جدول `settings` تُدار من `/admin`.

## التثبيت على InfinityFree

1. أنشئ قاعدة بيانات MySQL (**MySQL Databases**) واحفظ: Host / Name / User / Password.
2. ارفع محتويات `yassota/` إلى `htdocs/` (جذر النطاق).
3. تأكد أن `uploads/` قابل للكتابة (755).
4. افتح نطاقك — سيظهر **معالج الإعداد**: أدخل بيانات MySQL واسم/كلمة مرور المدير.
5. المعالج ينشئ الجداول والبيانات التجريبية وحساب المدير.
6. من **/admin ← SEO والإعدادات** اضبط: رابط الموقع و **Google Client ID**.

## إعداد تسجيل الدخول بجوجل (OAuth)

1. [Google Cloud Console](https://console.cloud.google.com) ← أنشئ مشروعاً.
2. **OAuth consent screen** ← اضبط اسم التطبيق.
3. **Credentials ← Create Credentials ← OAuth client ID ← Web application**.
4. في **Authorized JavaScript origins** أضف: `https://yassota.com`.
5. انسخ **Client ID** إلى **/admin ← Google Client ID**.
6. التحقق من الـID token يتم **في الخادم** (`google_verify()`) — لا أسرار في الواجهة.

## قاعدة البيانات

`users`, `posts`, `post_media`, `comments`, `post_likes`, `comment_likes`, `post_saves`, `follows`, `hashtags`, `post_hashtags`, `categories`, `notifications`, `conversations`, `messages`, `reports`, `settings`, `rate_limits` — تُنشأ **تلقائياً** (self-healing) مع فهارس. لا حاجة لاستيراد SQL.

## الأمان المطبّق

CSRF على كل طلب معدّل، استعلامات مُعدة (لا SQL injection)، تهريب المخرجات (XSS)، جلسات HttpOnly+SameSite+Secure، تحديد معدل، تحقق نوع/حجم الصور، ومنع تنفيذ PHP داخل `uploads/`، والتحقق من توكن جوجل في الخادم.

## تطبيق أندرويد (المرحلة التالية)

صفحة التحميل جاهزة على `/apps/yassota`، و`/.well-known/assetlinks.json` جاهز لربط App Links. بناء الـAPK الموقّع (Release، v2/v3، اسم حزمة ثابت `com.yassota.app`) يُنفّذ كبناء منفصل — مفتاح التوقيع **مستبعد من git**، وبصمة SHA-256 توضع في لوحة الإدارة و`assetlinks.json`. لا تحايل على تحذيرات الأمان: توقيع رسمي + HTTPS + نطاق رسمي + اسم حزمة ثابت.
