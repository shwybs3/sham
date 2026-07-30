<?php
define('MAIN_SITE', 'https://your-domain.com');
$error  = '';
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $url = trim($_POST['url'] ?? '');
  if (!$url || !preg_match('#(tiktok\.com|vm\.tiktok\.com)#i', $url)) {
    $error = 'أدخل رابط TikTok صحيح';
  } else {
    $safeUrl = escapeshellarg($url);
    $json = shell_exec("yt-dlp --no-warnings --dump-json $safeUrl 2>/dev/null");
    if ($json) {
      $info = json_decode($json, true);
      if ($info) {
        $result = [
          'title'     => $info['title'] ?? 'فيديو TikTok',
          'thumbnail' => $info['thumbnail'] ?? '',
          'duration'  => $info['duration'] ?? 0,
          'url'       => $info['url'] ?? ($info['formats'][0]['url'] ?? ''),
        ];
      } else { $error = 'تعذّر تحليل الفيديو.'; }
    } else { $error = 'تعذّر جلب الفيديو. تأكد من الرابط.'; }
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تحميل فيديوهات TikTok مجاناً بدون علامة مائية</title>
<meta name="description" content="حمّل مقاطع TikTok بدون علامة مائية وبأعلى جودة — مجاناً وبدون تسجيل">
<meta name="robots" content="index,follow">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{direction:rtl}
body{font-family:'Cairo',sans-serif;background:#0f0f1a;color:#e2e8f0;min-height:100vh}
.wrap{max-width:700px;margin:0 auto;padding:2rem 1rem}
.logo{text-align:center;margin-bottom:2.5rem}
.logo h1{font-size:2rem;font-weight:900;color:#fff}
.logo h1 span{color:#fe2c55}
.logo p{color:#94a3b8;font-size:.9rem;margin-top:.35rem}
.card{background:#1a1a2e;border:1px solid #2d2d44;border-radius:16px;padding:1.5rem;margin-bottom:1.25rem}
.input-row{display:flex;gap:.5rem}
.input-row input{flex:1;background:#0f0f1a;border:1px solid #2d2d44;border-radius:8px;padding:.75rem 1rem;color:#e2e8f0;font-size:15px;font-family:inherit}
.input-row input:focus{outline:none;border-color:#fe2c55}
.btn{background:linear-gradient(135deg,#fe2c55,#fe7096);color:#fff;border:none;border-radius:8px;padding:.75rem 1.5rem;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap}
.error{background:rgba(254,44,85,.1);border:1px solid rgba(254,44,85,.3);border-radius:8px;padding:.75rem 1rem;color:#fe2c55;font-size:14px;margin-bottom:1rem}
.result-thumb{width:100%;border-radius:10px;margin-bottom:1rem;max-height:300px;object-fit:cover}
.result-title{font-size:1.1rem;font-weight:700;margin-bottom:.5rem}
.dl-btn{display:inline-block;background:#16a34a;color:#fff;border:none;border-radius:8px;padding:.7rem 1.5rem;font-size:14px;font-weight:700;text-decoration:none;margin-top:1rem}
.nav-back{display:block;text-align:center;color:#64748b;font-size:13px;margin-top:1.5rem;text-decoration:none}
.nav-back:hover{color:#fe2c55}
</style>
</head>
<body>
<div class="wrap">
  <div class="logo">
    <h1>🎵 Tik<span>Tok</span></h1>
    <p>تحميل مقاطع TikTok بدون علامة مائية • مجاناً</p>
  </div>

  <div class="card">
    <?php if ($error): ?>
    <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="input-row">
        <input type="url" name="url" placeholder="https://vm.tiktok.com/..." value="<?= htmlspecialchars($_POST['url'] ?? '') ?>" required autofocus>
        <button type="submit" class="btn">⬇️ تحميل</button>
      </div>
    </form>

    <?php if ($result): ?>
    <div style="margin-top:1.25rem">
      <?php if ($result['thumbnail']): ?>
      <img src="<?= htmlspecialchars($result['thumbnail']) ?>" class="result-thumb" alt="thumbnail">
      <?php endif; ?>
      <div class="result-title"><?= htmlspecialchars($result['title']) ?></div>
      <?php if ($result['url']): ?>
      <a href="<?= htmlspecialchars($result['url']) ?>" class="dl-btn" download>⬇️ تحميل الفيديو</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="card" style="font-size:14px;color:#94a3b8;line-height:1.7">
    <strong style="color:#e2e8f0;display:block;margin-bottom:.5rem">ملاحظة</strong>
    يتم التحميل بدون علامة مائية. استخدام هذه الأداة يعني موافقتك على <a href="<?= htmlspecialchars(MAIN_SITE) ?>/privacy" style="color:#fe2c55">سياسة الخصوصية</a>.
    حمّل المحتوى الذي تملك حق تحميله فقط.
  </div>

  <a href="<?= htmlspecialchars(MAIN_SITE) ?>" class="nav-back">← العودة للموقع الرئيسي</a>
</div>
</body>
</html>
