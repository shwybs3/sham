<?php
require_once dirname(__DIR__) . '/config.php';
$pdo = $social_pdo;
$me  = sc_user($pdo);
if (!$me) { header('Location: ' . SOCIAL_URL . '/auth/login'); exit; }

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $longDesc = trim($_POST['long_desc'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $toolType = in_array($_POST['tool_type'] ?? '', ['free','paid']) ? $_POST['tool_type'] : 'free';
    $priceUsd = $toolType === 'paid' ? max(0.5, (float)($_POST['price_usd'] ?? 1)) : 0;
    $dlUrl    = trim($_POST['download_url'] ?? '');
    $demoUrl  = trim($_POST['demo_url'] ?? '');
    $tags     = array_filter(array_map('trim', explode(',', $_POST['tags'] ?? '')));

    if (!$title || !$desc) {
        $error = 'العنوان والوصف مطلوبان';
    } elseif (!sc_is_clean($title . ' ' . $desc)) {
        $error = 'المحتوى يخالف معايير المجتمع';
    } else {
        // Upload images
        $images = [];
        if (!empty($_FILES['images']['tmp_name'])) {
            foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
                if ($tmp) {
                    $fakeFile = [
                        'tmp_name' => $tmp,
                        'name'     => $_FILES['images']['name'][$i],
                        'type'     => $_FILES['images']['type'][$i],
                        'size'     => $_FILES['images']['size'][$i],
                    ];
                    $img = sc_upload_image($fakeFile, 'tools');
                    if ($img) $images[] = $img;
                }
            }
        }
        $slug = sc_slug($title) . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
        $pdo->prepare("INSERT INTO marketplace_tools
            (user_id,slug,title,description,long_desc,category,tool_type,price_usd,download_url,demo_url,images,tags,status,is_approved)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'published',1)")
            ->execute([
                $me['id'], $slug, $title, $desc, $longDesc ?: null, $category ?: null,
                $toolType, $priceUsd, $dlUrl ?: null, $demoUrl ?: null,
                json_encode($images, JSON_UNESCAPED_UNICODE),
                json_encode($tags, JSON_UNESCAPED_UNICODE),
            ]);
        header('Location: ' . SOCIAL_URL . '/marketplace');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>نشر أداة — <?= sc_h(SOCIAL_NAME) ?></title>
<link rel="icon" href="/social/assets/favicon.svg" type="image/svg+xml">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#0e1117;color:#e6edf3;min-height:100vh;padding-bottom:40px}
.nav{background:#161b22;border-bottom:1px solid #30363d;padding:0 20px;height:52px;display:flex;align-items:center;gap:14px}
.nav a{color:#e6edf3;text-decoration:none;font-size:.9rem}
.nav-brand{font-weight:800;color:#2f81f7}
.container{max-width:700px;margin:28px auto;padding:0 16px}
h1{font-size:1.3rem;font-weight:700;margin-bottom:20px}
.card{background:#161b22;border:1px solid #30363d;border-radius:12px;padding:24px;margin-bottom:20px}
.card h2{font-size:.92rem;font-weight:700;margin-bottom:16px;color:#8b949e;text-transform:uppercase;letter-spacing:.5px}
.field{margin-bottom:16px}
label{display:block;font-size:.85rem;font-weight:600;margin-bottom:6px}
.input,.select,.textarea{width:100%;background:#0e1117;border:1px solid #30363d;border-radius:8px;padding:10px 14px;color:#e6edf3;font-size:.9rem}
.input:focus,.select:focus,.textarea:focus{outline:none;border-color:#2f81f7}
.textarea{resize:vertical}
.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.type-tabs{display:flex;gap:8px;margin-bottom:0}
.type-tab{flex:1;padding:10px;text-align:center;border:2px solid #30363d;border-radius:8px;cursor:pointer;font-weight:600;font-size:.88rem;color:#8b949e;background:transparent;transition:all .15s}
.type-tab.active,.type-tab:hover{border-color:#2f81f7;color:#2f81f7}
.price-field{display:none}
.price-field.show{display:block}
.btn-submit{width:100%;background:#3fb950;color:#fff;border:none;border-radius:10px;padding:13px;font-size:1rem;font-weight:700;cursor:pointer}
.btn-submit:hover{opacity:.88}
.err{background:rgba(248,81,73,.1);border:1px solid #f85149;border-radius:8px;padding:10px;color:#f85149;font-size:.88rem;margin-bottom:16px}
.hint{font-size:.77rem;color:#8b949e;margin-top:4px}
</style>
</head>
<body>
<nav class="nav">
  <a href="<?= SOCIAL_URL ?>/marketplace" class="nav-brand">← السوق</a>
  <span style="color:#8b949e">·</span>
  <span>نشر أداة جديدة</span>
</nav>

<div class="container">
  <h1>📦 نشر أداة أو سكريبت</h1>

  <?php if ($error): ?><div class="err"><?= sc_h($error) ?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <div class="card">
      <h2>المعلومات الأساسية</h2>
      <div class="field">
        <label>عنوان الأداة *</label>
        <input class="input" type="text" name="title" required maxlength="200" placeholder="مثال: سكريبت تلقائي لاستخراج النصوص">
      </div>
      <div class="field">
        <label>وصف مختصر *</label>
        <textarea class="textarea" name="description" required rows="3" maxlength="500" placeholder="وصف مختصر يظهر في البطاقة..."></textarea>
      </div>
      <div class="field">
        <label>وصف تفصيلي (اختياري)</label>
        <textarea class="textarea" name="long_desc" rows="6" placeholder="شرح مفصّل، طريقة الاستخدام، المتطلبات..."></textarea>
      </div>
      <div class="row">
        <div class="field">
          <label>التصنيف</label>
          <input class="input" type="text" name="category" maxlength="80" placeholder="مثال: Python, Web, أمن">
        </div>
        <div class="field">
          <label>الوسوم (Tags) — مفصولة بفاصلة</label>
          <input class="input" type="text" name="tags" maxlength="300" placeholder="automation, ai, python">
        </div>
      </div>
    </div>

    <div class="card">
      <h2>نوع الأداة والسعر</h2>
      <div class="type-tabs">
        <button type="button" class="type-tab active" id="tab-free" onclick="setType('free')">🆓 مجاني</button>
        <button type="button" class="type-tab" id="tab-paid" onclick="setType('paid')">💰 مدفوع</button>
      </div>
      <input type="hidden" name="tool_type" id="tool_type" value="free">
      <div class="price-field" id="price-field" style="margin-top:12px">
        <label>السعر (USD)</label>
        <input class="input" type="number" name="price_usd" min="0.5" step="0.5" value="5" placeholder="5">
        <div class="hint">الحد الأدنى $0.50 — النظام يأخذ 10% عمولة</div>
      </div>
    </div>

    <div class="card">
      <h2>الروابط والصور</h2>
      <div class="field">
        <label>رابط التحميل / GitHub / Gist</label>
        <input class="input" type="url" name="download_url" placeholder="https://github.com/...">
      </div>
      <div class="field">
        <label>رابط التجربة / Demo (اختياري)</label>
        <input class="input" type="url" name="demo_url" placeholder="https://...">
      </div>
      <div class="field">
        <label>صور الأداة (حتى 5 صور)</label>
        <input type="file" name="images[]" accept="image/*" multiple>
        <div class="hint">JPEG/PNG/WebP — الحد الأقصى 5MB لكل صورة</div>
      </div>
    </div>

    <button class="btn-submit" type="submit">🚀 نشر الأداة</button>
  </form>
</div>

<script>
function setType(t) {
  document.getElementById('tool_type').value = t;
  document.getElementById('tab-free').className = 'type-tab' + (t==='free'?' active':'');
  document.getElementById('tab-paid').className = 'type-tab' + (t==='paid'?' active':'');
  document.getElementById('price-field').className = 'price-field' + (t==='paid'?' show':'');
}
</script>
</body>
</html>
