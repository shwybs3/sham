<?php
// YouTube Shorts & Video Downloader subdomain
define('MAIN_SITE', 'https://your-domain.com');
define('SUB_NAME',  'تحميل يوتيوب شورت');

$error   = '';
$result  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $url = trim($_POST['url'] ?? '');
  if (!$url || !preg_match('#(youtube\.com|youtu\.be|youtube\.com/shorts)#i', $url)) {
    $error = 'أدخل رابط يوتيوب صحيح';
  } else {
    // yt-dlp fetch info (JSON)
    $safeUrl = escapeshellarg($url);
    $cmd = "yt-dlp --no-warnings --dump-json --no-playlist $safeUrl 2>/dev/null";
    $json = shell_exec($cmd);
    if ($json) {
      $info = json_decode($json, true);
      if ($info) {
        $result = [
          'title'     => $info['title'] ?? 'فيديو يوتيوب',
          'thumbnail' => $info['thumbnail'] ?? '',
          'duration'  => $info['duration'] ?? 0,
          'id'        => $info['id'] ?? '',
          'formats'   => array_filter($info['formats'] ?? [], fn($f) => !empty($f['url']) && (!isset($f['vcodec']) || $f['vcodec'] !== 'none') || (isset($f['acodec']) && $f['acodec'] !== 'none')),
        ];
      } else {
        $error = 'تعذّر تحليل الفيديو. تأكد من الرابط وحاول مجدداً.';
      }
    } else {
      $error = 'تعذّر جلب معلومات الفيديو. قد يكون yt-dlp غير مثبت أو الرابط محمي.';
    }
  }
}

function fmtDur($secs) {
  $m = floor($secs / 60); $s = $secs % 60;
  return sprintf('%d:%02d', $m, $s);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تحميل يوتيوب شورت وفيديوهات يوتيوب مجاناً</title>
<meta name="description" content="حمّل مقاطع يوتيوب شورت وفيديوهات يوتيوب بجودة عالية مجاناً — بدون تسجيل دخول">
<meta name="robots" content="index,follow">
<link rel="canonical" href="https://shorts.<?= htmlspecialchars(parse_url(MAIN_SITE, PHP_URL_HOST)) ?>">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{direction:rtl}
body{font-family:'Cairo',sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh}
.wrap{max-width:700px;margin:0 auto;padding:2rem 1rem}
.logo{text-align:center;margin-bottom:2.5rem}
.logo h1{font-size:2rem;font-weight:900;color:#fff}
.logo h1 span{color:#ef4444}
.logo p{color:#94a3b8;font-size:.9rem;margin-top:.35rem}
.card{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:1.5rem;margin-bottom:1.25rem}
.input-row{display:flex;gap:.5rem}
.input-row input{flex:1;background:#0f172a;border:1px solid #334155;border-radius:8px;padding:.75rem 1rem;color:#e2e8f0;font-size:15px;font-family:inherit}
.input-row input:focus{outline:none;border-color:#ef4444}
.btn{background:#ef4444;color:#fff;border:none;border-radius:8px;padding:.75rem 1.5rem;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap}
.btn:hover{background:#dc2626}
.error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:8px;padding:.75rem 1rem;color:#fca5a5;font-size:14px;margin-bottom:1rem}
.result-thumb{width:100%;border-radius:10px;margin-bottom:1rem;max-height:300px;object-fit:cover}
.result-title{font-size:1.1rem;font-weight:700;margin-bottom:.5rem}
.result-meta{font-size:13px;color:#94a3b8;margin-bottom:1rem}
.formats{display:flex;flex-direction:column;gap:.5rem}
.fmt-row{display:flex;align-items:center;justify-content:space-between;background:#0f172a;border:1px solid #334155;border-radius:8px;padding:.75rem 1rem;gap:.5rem;flex-wrap:wrap}
.fmt-info{font-size:13px;color:#94a3b8}
.fmt-info strong{color:#e2e8f0}
.dl-btn{background:#16a34a;color:#fff;border:none;border-radius:6px;padding:.4rem .9rem;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;font-family:inherit}
.nav-back{display:block;text-align:center;color:#94a3b8;font-size:13px;margin-top:1.5rem;text-decoration:none}
.nav-back:hover{color:#ef4444}
.tips{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:.75rem;margin-top:1rem}
.tip{background:#0f172a;border:1px solid #334155;border-radius:10px;padding:.9rem;font-size:13px;color:#94a3b8;text-align:center}
.tip strong{display:block;color:#e2e8f0;margin-bottom:.3rem}
</style>
</head>
<body>
<div class="wrap">
  <div class="logo">
    <h1>▶️ يوتيوب <span>شورت</span></h1>
    <p>حمّل مقاطع يوتيوب شورت والفيديوهات العادية مجاناً</p>
  </div>

  <div class="card">
    <?php if ($error): ?>
    <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="input-row">
        <input type="url" name="url" placeholder="https://youtube.com/shorts/..." value="<?= htmlspecialchars($_POST['url'] ?? '') ?>" required autofocus>
        <button type="submit" class="btn">⬇️ تحميل</button>
      </div>
    </form>

    <?php if ($result): ?>
    <div style="margin-top:1.25rem">
      <?php if ($result['thumbnail']): ?>
      <img src="<?= htmlspecialchars($result['thumbnail']) ?>" class="result-thumb" alt="thumbnail">
      <?php endif; ?>
      <div class="result-title"><?= htmlspecialchars($result['title']) ?></div>
      <?php if ($result['duration']): ?>
      <div class="result-meta">⏱️ المدة: <?= fmtDur($result['duration']) ?></div>
      <?php endif; ?>
      <div class="formats">
        <a href="<?= htmlspecialchars(reset($result['formats'])['url'] ?? '#') ?>" class="fmt-row" style="text-decoration:none;color:inherit" download>
          <div class="fmt-info"><strong>أفضل جودة متاحة</strong></div>
          <span class="dl-btn">⬇️ تحميل</span>
        </a>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <strong style="display:block;margin-bottom:.75rem;font-size:.95rem">كيف تستخدم الأداة؟</strong>
    <div class="tips">
      <div class="tip"><strong>1. انسخ الرابط</strong>افتح المقطع في يوتيوب وانسخ الرابط</div>
      <div class="tip"><strong>2. الصق هنا</strong>الصق الرابط في الحقل أعلاه</div>
      <div class="tip"><strong>3. تحميل</strong>اضغط "تحميل" وانتظر ثواني</div>
    </div>
  </div>

  <a href="<?= htmlspecialchars(MAIN_SITE) ?>" class="nav-back">← العودة لـ <?= htmlspecialchars(parse_url(MAIN_SITE, PHP_URL_HOST)) ?></a>
</div>
</body>
</html>
