<?php
/**
 * إعدادات لوحة تحكم ناري ستور.
 *
 * هام جداً: غيّر اسم المستخدم وكلمة المرور فور رفع الموقع على الاستضافة.
 * لتوليد كلمة مرور جديدة، شغّل هذا السطر مرة واحدة عبر أي بيئة PHP (أو
 * استخدم أداة "توليد كلمة مرور" أونلاين تعتمد password_hash بخوارزمية bcrypt):
 *
 *   php -r "echo password_hash('كلمة_مرورك_الجديدة', PASSWORD_DEFAULT);"
 *
 * ثم انسخ الناتج وضعه مكان قيمة ADMIN_PASSWORD_HASH أدناه.
 */

if (!defined('NARI_ADMIN')) {
    http_response_code(403);
    exit('محظور');
}

// اسم المستخدم لتسجيل الدخول للوحة التحكم
define('ADMIN_USERNAME', 'admin');

// كلمة المرور الافتراضية هي: NariStore@2026  (غيّرها فوراً بعد أول نشر!)
define('ADMIN_PASSWORD_HASH', '$2y$12$BsgmH.rLrdbZjwhCsZcihe.i2zzhGYwP8skFKe5BI/EZ7ADf.VOEO');

// مسارات ملفات البيانات (نفس البيانات التي يقرأها الموقع العام)
define('SETTINGS_FILE', __DIR__ . '/../data/settings.json');
define('SERVICES_FILE', __DIR__ . '/../data/services.json');

// اسم جلسة مخصص لتفادي أي تعارض مع تطبيقات أخرى على نفس الاستضافة
define('ADMIN_SESSION_NAME', 'nari_admin_session');
