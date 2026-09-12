<?php
require __DIR__ . '/config.php';
$me = require_login();
$cats = $pdo->query("SELECT * FROM categories ORDER BY sort_order")->fetchAll();
$err = ''; $old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) $err = 'انتهت صلاحية الجلسة، حدّث الصفحة.';
    elseif (!rate_ok('post:' . $me['id'], 20, 3600)) $err = 'تجاوزت حد النشر، حاول لاحقاً.';
    else {
        $title = trim($_POST['title'] ?? '');
        $body  = trim($_POST['description'] ?? '');
        $catId = (int)($_POST['category_id'] ?? 0) ?: null;
        $loc   = trim($_POST['location'] ?? '');
        $vis   = in_array($_POST['visibility'] ?? '', ['public', 'followers'], true) ? $_POST['visibility'] : 'public';
        $tagsRaw = trim($_POST['tags'] ?? '');
        $old = compact('title', 'body', 'loc');

        if (mb_strlen($title) < 3) $err = 'العنوان قصير جداً (3 أحرف على الأقل).';
        else {
            $imageUrl = ''; $type = 'text';
            if (!empty($_FILES['image']['name'])) {
                $up = ya_upload_image($_FILES['image'], 'posts');
                if (!$up['ok']) { $err = $up['error']; }
                else { $imageUrl = $up['url']; $type = 'image'; }
            }
            if (!$err) {
                $slug = ya_unique_slug($pdo, 'posts', $title);
                $seoTitle = mb_substr($title, 0, 190);
                $seoDesc  = mb_substr(strip_tags($body) ?: $title, 0, 155);
                $tags = ya_parse_tags($tagsRaw !== '' ? $tagsRaw : $body);
                $seoKw = implode(', ', $tags);
                $pdo->prepare("INSERT INTO posts (user_id,slug,type,title,description,image,category_id,location,seo_title,seo_description,seo_keywords,visibility,status)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?, 'published')")
                    ->execute([$me['id'], $slug, $type, $title, $body, $imageUrl, $catId, $loc, $seoTitle, $seoDesc, $seoKw, $vis]);
                $pid = (int)$pdo->lastInsertId();
                foreach ($tags as $t) ya_attach_hashtag($pdo, $pid, $t);
                $pdo->prepare("UPDATE users SET posts_count = posts_count + 1 WHERE id = ?")->execute([$me['id']]);
                header('Location: ' . url('post/' . $slug)); exit;
            }
        }
    }
}

layout_top(['title' => 'إنشاء منشور | ' . setting('site_name', 'YASSOTA'), 'noindex' => true, 'active' => 'create']);
?>
<div class="phead"><div><h1>إنشاء منشور</h1><p class="sub">شارك صورة أو مقالاً — سيصبح صفحة مستقلة تظهر في البحث</p></div></div>
<?php if ($err): ?><div class="alert err"><?= e($err) ?></div><?php endif; ?>
<form class="card" style="padding:18px" method="post" enctype="multipart/form-data" id="createForm">
  <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
  <label class="field" style="display:block;cursor:pointer">
    <span style="font-size:13px;color:var(--muted);font-weight:600">الصورة (اختياري)</span>
    <div id="dropZone" style="margin-top:6px;border:2px dashed var(--line);border-radius:14px;aspect-ratio:16/9;display:grid;place-items:center;overflow:hidden;background:var(--surface-2);text-align:center;color:var(--faint)">
      <div id="dzHint"><?= icon('image',40) ?><div style="margin-top:6px;font-size:13.5px">اضغط لاختيار صورة (JPG/PNG/WebP · حتى 8MB)</div></div>
      <img id="preview" hidden style="width:100%;height:100%;object-fit:cover">
    </div>
    <input type="file" name="image" id="imgInput" accept="image/*" hidden>
  </label>
  <div class="field"><label>العنوان *</label><input class="input" name="title" maxlength="200" required value="<?= e($old['title'] ?? '') ?>" placeholder="مثال: أفضل تطبيقات أندرويد في 2026"></div>
  <div class="field"><label>الوصف / المحتوى</label><textarea class="input" name="description" rows="5" placeholder="اكتب التفاصيل… يمكنك استخدام #وسوم داخل النص"><?= e($old['body'] ?? '') ?></textarea></div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
    <div class="field"><label>التصنيف</label><select class="input" name="category_id"><option value="">— بدون —</option><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label>الرؤية</label><select class="input" name="visibility"><option value="public">عام (يظهر للجميع وفي البحث)</option><option value="followers">المتابعون فقط</option></select></div>
  </div>
  <div class="field"><label>الوسوم</label><input class="input" name="tags" placeholder="تقنية، اندرويد، تطبيقات" value=""></div>
  <div class="field"><label>الموقع (اختياري)</label><input class="input" name="location" value="<?= e($old['loc'] ?? '') ?>" placeholder="الرياض، السعودية"></div>
  <button class="btn btn-primary btn-lg btn-block" type="submit"><?= icon('plus',20) ?> نشر</button>
</form>
<script>
(function () {
  var inp = document.getElementById('imgInput'), dz = document.getElementById('dropZone'),
      pv = document.getElementById('preview'), hint = document.getElementById('dzHint');
  dz.addEventListener('click', function () { inp.click(); });
  inp.addEventListener('change', function () {
    var f = inp.files[0]; if (!f) return;
    if (f.size > 8 * 1024 * 1024) { yaToast('حجم الصورة يتجاوز 8MB', 'err'); inp.value = ''; return; }
    pv.src = URL.createObjectURL(f); pv.hidden = false; hint.hidden = true;
  });
})();
</script>
<?php layout_bottom(); ?>
