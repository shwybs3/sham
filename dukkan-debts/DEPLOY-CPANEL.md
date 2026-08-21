# النشر على استضافة cPanel (مثل Namecheap Business)

هذا الدليل يشرح خطوة بخطوة كيف ترفع **دفتر الدكان** على استضافة cPanel تدعم تطبيقات Node.js.

## 1) تأكد أن الميزة موجودة عندك

ادخل لوحة cPanel → قسم **Software** → ابحث عن أيقونة اسمها **"Setup Node.js App"** (أو "Node.js Selector").

- **موجودة؟** تابع الخطوات بالأسفل.
- **غير موجودة؟** تواصل مع دعم Namecheap واطلب منهم تفعيل "CloudLinux NodeJS Selector" على حسابك — أغلب خطط cPanel Business توفرها لكنها أحياناً تحتاج تفعيل. بديل سريع: استضافة VPS بسيطة (Namecheap نفسها تبيعها) تعطيك تحكم كامل بدون قيود.

## 2) أنشئ التطبيق

من "Setup Node.js App" اضغط **Create Application** واملأ:

| الحقل | القيمة |
|---|---|
| Node.js version | أعلى نسخة متوفرة (22.x مثالي، أي نسخة 18+ تعمل) |
| Application mode | Production |
| Application root | مثلاً `dukkan-debts` (مجلد داخل حسابك، ليس بالضرورة `public_html`) |
| Application URL | الدومين أو الساب-دومين اللي بدك الموقع يظهر عليه |
| Application startup file | **`server.js`** |

اضغط **Create**. cPanel رح ينشئ لك مجلد فاضي بمسار الـ Application root ويعطيك أمر "تفعيل البيئة الافتراضية" (نحتاجه لاحقاً فقط لو رغبت تشغّل أوامر يدوية).

## 3) ارفع الملفات

فك ضغط الملف المرفق ثم ارفع **كل محتوياته** (وليس المجلد نفسه) داخل مجلد الـ Application root اللي أنشأته بالخطوة السابقة — عبر File Manager أو FTP.

الحزمة تحتوي على `.next` (النسخة المبنية جاهزة) — **لن تحتاج تشغّل build على السيرفر إطلاقاً**، فقط تثبيت الحزم.

## 4) متغيرات البيئة

من نفس صفحة "Setup Node.js App"، افتح تطبيقك واضغط **Add Variable** لكل مما يلي:

| المتغير | القيمة |
|---|---|
| `DATABASE_URL` | `file:./data/app.db` |
| `AUTH_SECRET` | مفتاح عشوائي — ولّده محلياً بـ: `node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"` |
| `NEXT_PUBLIC_SITE_URL` | `https://your-domain.com` (رابط موقعك الفعلي) |
| `NODE_ENV` | `production` |

> لا ترفع ملف `.env` أبداً — كل الإعدادات من هنا فقط.

## 5) ثبّت الحزم

بنفس صفحة التطبيق اضغط **Run NPM Install**. هذا يثبّت كل الحزم (بما فيها `better-sqlite3` المبنية خصيصاً لمعمارية سيرفرك — لهذا السبب لم نرفق `node_modules` بالحزمة).

## 6) شغّل التطبيق

اضغط **Restart** (أو **Start**). بأول تشغيل، السيرفر ينشئ قاعدة البيانات وكل الجداول تلقائياً — **لا تحتاج Terminal ولا SSH إطلاقاً**.

## 7) افتح موقعك

روح لـ `https://your-domain.com/admin/setup` وأنشئ اسم الدكان وحساب الإدارة الأول. بعدها كل شي جاهز:

- الموقع العام: `https://your-domain.com`
- لوحة الإدارة: `https://your-domain.com/admin`
- قسم الخبز: `https://your-domain.com/bread`

## النسخ الاحتياطي

قاعدة بياناتك بالكامل هي ملف واحد فقط: `data/app.db` (بالإضافة لمجلد `public/uploads` للصور المرفوعة بالدردشة والبانرات). حمّل نسخة منهما بشكل دوري عبر File Manager أو FTP كنسخة احتياطية — هذا كل دفتر ديونك.

## تحديث الموقع لاحقاً

كل مرة يوصلك إصدار جديد: ارفع محتويات الحزمة الجديدة فوق الملفات القديمة (بدون حذف `data/app.db` أو `public/uploads`)، اضغط **Run NPM Install** مرة ثانية، ثم **Restart**. قاعدة البيانات تُحدَّث تلقائياً عند أول تشغيل (نفس آلية الخطوة 6) دون فقدان أي بيانات.

## لو عندك Terminal/SSH (اختياري)

بدل الاعتماد على التحديث التلقائي، تقدر تشغّل الهجرات يدوياً بأي وقت:

```bash
source /home/USERNAME/nodevenv/dukkan-debts/22/bin/activate
cd ~/dukkan-debts
npx prisma migrate deploy
```

(المسار بالضبط يعطيك إياه cPanel بصفحة التطبيق نفسها، بزر "Enter to virtual environment").
