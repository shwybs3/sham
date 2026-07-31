<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$contactEmail = get_cfg($pdo, 'contact_email') ?: 'contact@' . parse_url(SITE_URL, PHP_URL_HOST);
$sent  = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['website'])) {
        // honeypot
        $sent = true;
    } elseif (!csrf_check()) {
        $error = 'جلسة غير صالحة، أعد تحميل الصفحة وحاول مجدداً.';
    } elseif (cf_turnstile_enabled($pdo) && !cf_turnstile_verify($pdo, $_POST['cf-turnstile-response'] ?? '')) {
        $error = 'فشل التحقق من الهوية. يرجى إعادة المحاولة.';
    } else {
        $reqName    = trim($_POST['req_name']    ?? '');
        $reqEmail   = trim($_POST['req_email']   ?? '');
        $contentUrl = trim($_POST['content_url'] ?? '');
        $reqType    = in_array($_POST['req_type'] ?? '', ['dmca','app_removal','wrong_info','other'], true)
                        ? $_POST['req_type'] : 'dmca';
        $message    = trim($_POST['message']     ?? '');

        if (!$reqName || !filter_var($reqEmail, FILTER_VALIDATE_EMAIL) || !$contentUrl || !$message) {
            $error = 'يرجى تعبئة جميع الحقول المطلوبة بشكل صحيح.';
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $pdo->prepare("INSERT INTO removal_requests
                (req_name, req_email, content_url, req_type, message, ip)
                VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$reqName, $reqEmail, $contentUrl, $reqType, $message, $ip]);

            // Email to site contact
            $typeLabels = ['dmca'=>'DMCA حقوق النشر','app_removal'=>'إزالة تطبيق','wrong_info'=>'معلومات خاطئة','other'=>'أخرى'];
            $typeLabel = $typeLabels[$reqType] ?? $reqType;
            $mailBody = "طلب إزالة محتوى جديد — {$typeLabel}\n\n"
                . "الاسم: {$reqName}\n"
                . "البريد: {$reqEmail}\n"
                . "الرابط: {$contentUrl}\n"
                . "النوع: {$typeLabel}\n\n"
                . "الرسالة:\n{$message}\n\n"
                . "IP: {$ip}\n"
                . "التاريخ: " . date('Y-m-d H:i:s');
            $mailHeaders = "From: yassota <no-reply@" . parse_url(SITE_URL, PHP_URL_HOST) . ">\r\n"
                . "Reply-To: {$reqEmail}\r\n"
                . "Content-Type: text/plain; charset=UTF-8";
            @mail($contactEmail,
                '=?UTF-8?B?' . base64_encode("طلب إزالة محتوى — {$typeLabel} — yassota") . '?=',
                $mailBody, $mailHeaders);

            notify_admin($pdo, "طلب إزالة محتوى — {$typeLabel}", $mailBody);
            $sent = true;
        }
    }
}

$seoTitle = 'طلب إزالة محتوى — DMCA — yassota';
$metaDesc = 'قدّم طلب إزالة محتوى أو تطبيق من موقع yassota لأسباب DMCA أو معلومات خاطئة أو غير ذلك.';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <?= nav_guard_script() ?>
  <meta charset="UTF-8">
  <?= head_extras($pdo) ?>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title><?= h($seoTitle) ?></title>
  <meta name="description" content="<?= h($metaDesc) ?>">
  <link rel="canonical" href="<?= h(url('dmca')) ?>">
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189"
     crossorigin="anonymous"></script>
</head>
<body>

<?php render_site_header(); ?>

<div class="page-wrap fw">

<main class="main-content">
  <nav style="font-size:12px;color:var(--muted);margin-bottom:16px;display:flex;gap:6px;align-items:center;flex-wrap:wrap">
    <a href="/" style="color:var(--cyan)">الرئيسية</a><span>/</span><span>DMCA وإزالة المحتوى</span>
  </nav>

  <div class="section-head reveal"><span class="section-title">طلب إزالة محتوى أو تطبيق</span></div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:28px;align-items:start">

    <!-- Policy info -->
    <div class="section-box reveal" style="color:var(--muted);font-size:14px;line-height:1.9">
      <h2 style="color:var(--white);font-size:16px;margin-bottom:14px">سياسة DMCA وحقوق النشر</h2>
      <p>يحترم موقع yassota حقوق الملكية الفكرية للغير، ويستجيب لبلاغات إزالة المحتوى المخالف بموجب قانون الألفية الرقمية لحقوق النشر (DMCA). الموقع دليل معلوماتي يعرض بيانات عن تطبيقات ويوجّه لروابط تحميل خارجية.</p>

      <h3 style="color:var(--white);margin:18px 0 8px;font-size:15px">ما تتضمنه بلاغات الإزالة الصحيحة</h3>
      <ol style="padding-inline-start:20px;display:flex;flex-direction:column;gap:8px;margin-top:8px">
        <li>توقيع (إلكتروني) لمالك الحق أو من يمثله قانونياً.</li>
        <li>تحديد واضح للعمل المحمي بحقوق النشر المدّعى انتهاكه.</li>
        <li>الرابط المباشر للصفحة المخالفة على yassota.</li>
        <li>معلومات تواصل: الاسم، البريد الإلكتروني.</li>
        <li>إفادة بحسن نية بأن الاستخدام غير مرخّص.</li>
      </ol>

      <h3 style="color:var(--white);margin:18px 0 8px;font-size:15px">مدة المعالجة</h3>
      <p>نراجع البلاغات الجادة ونردّ عليها خلال <strong style="color:var(--cyan)">3–5 أيام عمل</strong>.</p>

      <h3 style="color:var(--white);margin:18px 0 8px;font-size:15px">البلاغ المضاد</h3>
      <p>إذا أُزيل محتوى تعتقد أنه أُزيل خطأً، أرسل بلاغاً مضاداً عبر البريد
        <a href="mailto:<?= h($contactEmail) ?>" style="color:var(--cyan)"><?= h($contactEmail) ?></a>.</p>
    </div>

    <!-- Request form -->
    <div class="section-box reveal">
      <?php if ($sent): ?>
        <div style="text-align:center;padding:30px 20px">
          <div style="font-size:48px;margin-bottom:12px">✅</div>
          <h3 style="color:var(--white);margin-bottom:8px;font-size:18px">تم استلام طلبك بنجاح</h3>
          <p style="color:var(--muted);font-size:14px;line-height:1.7">
            سيتم مراجعة طلبك والرد عليك عبر البريد الإلكتروني المُدخل خلال 3–5 أيام عمل.<br>
            شكراً لمساعدتنا في الحفاظ على جودة المحتوى.
          </p>
          <a href="/" style="display:inline-block;margin-top:16px;padding:10px 22px;background:var(--cyan);color:#fff;border-radius:10px;font-size:13px;font-weight:700">العودة للرئيسية</a>
        </div>
      <?php else: ?>
        <h2 style="color:var(--white);font-size:16px;margin-bottom:18px">نموذج طلب الإزالة</h2>

        <?php if ($error): ?>
        <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#ef4444;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:13px"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="post" action="dmca.php" style="display:flex;flex-direction:column;gap:14px">
          <?= csrf_field() ?>
          <!-- Honeypot -->
          <input type="text" name="website" tabindex="-1" autocomplete="off"
                 style="position:absolute;left:-9999px;width:1px;height:1px" aria-hidden="true">

          <div style="display:flex;flex-direction:column;gap:6px">
            <label style="font-size:12px;color:var(--muted);font-weight:600">الاسم الكامل <span style="color:#ef4444">*</span></label>
            <input type="text" name="req_name" required placeholder="اسمك الكامل"
                   value="<?= h($_POST['req_name'] ?? '') ?>"
                   style="background:var(--navy-600);border:1px solid var(--border-c);border-radius:10px;padding:11px 14px;color:var(--white);font-size:14px;outline:none;transition:border-color .15s"
                   onfocus="this.style.borderColor='var(--cyan)'" onblur="this.style.borderColor='var(--border-c)'">
          </div>

          <div style="display:flex;flex-direction:column;gap:6px">
            <label style="font-size:12px;color:var(--muted);font-weight:600">البريد الإلكتروني <span style="color:#ef4444">*</span></label>
            <input type="email" name="req_email" required placeholder="your@email.com"
                   value="<?= h($_POST['req_email'] ?? '') ?>"
                   style="background:var(--navy-600);border:1px solid var(--border-c);border-radius:10px;padding:11px 14px;color:var(--white);font-size:14px;outline:none;transition:border-color .15s"
                   onfocus="this.style.borderColor='var(--cyan)'" onblur="this.style.borderColor='var(--border-c)'">
          </div>

          <div style="display:flex;flex-direction:column;gap:6px">
            <label style="font-size:12px;color:var(--muted);font-weight:600">رابط المحتوى على yassota <span style="color:#ef4444">*</span></label>
            <input type="url" name="content_url" required placeholder="https://yassota.com/..."
                   value="<?= h($_POST['content_url'] ?? '') ?>"
                   style="background:var(--navy-600);border:1px solid var(--border-c);border-radius:10px;padding:11px 14px;color:var(--white);font-size:14px;direction:ltr;text-align:right;outline:none;transition:border-color .15s"
                   onfocus="this.style.borderColor='var(--cyan)'" onblur="this.style.borderColor='var(--border-c)'">
          </div>

          <div style="display:flex;flex-direction:column;gap:6px">
            <label style="font-size:12px;color:var(--muted);font-weight:600">نوع الطلب <span style="color:#ef4444">*</span></label>
            <select name="req_type"
                    style="background:var(--navy-600);border:1px solid var(--border-c);border-radius:10px;padding:11px 14px;color:var(--white);font-size:14px;outline:none">
              <?php $selType = $_POST['req_type'] ?? 'dmca'; ?>
              <option value="dmca"        <?= $selType==='dmca'        ? 'selected':'' ?>>DMCA — انتهاك حقوق النشر</option>
              <option value="app_removal" <?= $selType==='app_removal' ? 'selected':'' ?>>إزالة تطبيق</option>
              <option value="wrong_info"  <?= $selType==='wrong_info'  ? 'selected':'' ?>>معلومات خاطئة أو مضللة</option>
              <option value="other"       <?= $selType==='other'       ? 'selected':'' ?>>أخرى</option>
            </select>
          </div>

          <div style="display:flex;flex-direction:column;gap:6px">
            <label style="font-size:12px;color:var(--muted);font-weight:600">تفاصيل الطلب <span style="color:#ef4444">*</span></label>
            <textarea name="message" rows="5" required
                      placeholder="اشرح بالتفصيل سبب طلب الإزالة..."
                      style="background:var(--navy-600);border:1px solid var(--border-c);border-radius:10px;padding:11px 14px;color:var(--white);font-size:14px;resize:vertical;outline:none;transition:border-color .15s"
                      onfocus="this.style.borderColor='var(--cyan)'" onblur="this.style.borderColor='var(--border-c)'"><?= h($_POST['message'] ?? '') ?></textarea>
          </div>

          <?= cf_turnstile_widget($pdo) ?>

          <button type="submit" class="btn-primary" style="align-self:flex-start;gap:8px">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2L15 22l-4-9-9-4z"/></svg>
            إرسال الطلب
          </button>
        </form>
      <?php endif; ?>
    </div>

  </div><!-- /grid -->
</main>
</div>

<?php render_site_footer(); ?>
<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
</body>
</html>
