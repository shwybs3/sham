<?php
require_once dirname(__DIR__) . '/_base.php';
require_once dirname(dirname(__DIR__)) . '/config.php';

$error = $resultPath = '';
$maxMb = 10;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['img'])) {
    $f = $_FILES['img'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        $error = 'خطأ في رفع الملف.';
    } elseif ($f['size'] > $maxMb * 1024 * 1024) {
        $error = "حجم الملف يتجاوز {$maxMb} ميغابايت.";
    } else {
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','gif','webp','bmp'])) {
            $error = 'الصيغة غير مدعومة.';
        } else {
            $w = max(10, min(8000, (int)($_POST['width'] ?? 800)));
            $h = max(10, min(8000, (int)($_POST['height'] ?? 0)));
            $keepRatio = isset($_POST['keep_ratio']);
            $outFmt = in_array($_POST['format'] ?? 'webp', ['webp','jpg','png']) ? ($_POST['format'] ?? 'webp') : 'webp';
            $outDir = __DIR__ . '/../../uploads/.cache/resize';
            if (!is_dir($outDir)) @mkdir($outDir, 0755, true);
            $outName = 'r_' . uniqid() . '.' . $outFmt;
            $outPath = $outDir . '/' . $outName;

            $src = @imagecreatefromstring(file_get_contents($f['tmp_name']));
            if ($src) {
                $srcW = imagesx($src);
                $srcH = imagesy($src);
                $newW = $w;
                $newH = $h;
                if ($keepRatio || $h === 0) {
                    $ratio = $srcH / $srcW;
                    $newH = (int)round($newW * $ratio);
                }
                $dst = imagecreatetruecolor($newW, $newH);
                if ($outFmt === 'png') {
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                    imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
                }
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);
                $ok = false;
                if ($outFmt === 'webp') $ok = imagewebp($dst, $outPath, 88);
                elseif ($outFmt === 'jpg') $ok = imagejpeg($dst, $outPath, 90);
                else $ok = imagepng($dst, $outPath, 6);
                imagedestroy($src);
                imagedestroy($dst);
                if ($ok) {
                    $resultPath = url('uploads/.cache/resize/' . $outName);
                } else {
                    $error = 'تعذّر حفظ الصورة.';
                }
            } else {
                $error = 'تعذّر قراءة الصورة. تأكد من صلاحية الملف.';
            }
        }
    }
}

$schema = json_encode(['@context'=>'https://schema.org','@type'=>'WebApplication','name'=>'تغيير حجم الصورة مجاناً','url'=>TOOLS_BASE_URL.'/tools/resize/','description'=>'غيّر أبعاد الصورة بدقة وحوّلها إلى WebP أو JPG أو PNG مجاناً','applicationCategory'=>'UtilitiesApplication','operatingSystem'=>'All','offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD']]);
tool_head('تغيير حجم الصورة مجاناً — Resize Image | yassota أدوات','غيّر أبعاد صورتك بدقة وحوّلها إلى WebP أو JPG أو PNG مع الحفاظ على النسبة — مجاناً وبدون تسجيل',$schema,'#dc2626');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#fee2e2">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
    </div>
    <h1>تغيير حجم الصورة</h1>
    <p>غيّر أبعاد أي صورة بدقة، مع خيار الحفاظ على نسبة العرض/الارتفاع، وتحويل الصيغة — مجاناً تماماً.</p>
  </div>

<?php if ($error): ?>
  <div class="t-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($resultPath): ?>
  <div class="t-card" style="border-color:#86efac;background:#f0fdf4">
    <h2 style="color:#16a34a">✓ تم تغيير الحجم بنجاح</h2>
    <img src="<?= htmlspecialchars($resultPath) ?>" style="max-width:100%;border-radius:10px;margin-bottom:12px" alt="نتيجة">
    <a href="<?= htmlspecialchars($resultPath) ?>" download="resized" class="t-btn t-btn-success">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
      تحميل الصورة
    </a>
  </div>
<?php endif; ?>

  <div class="t-card">
    <form method="post" enctype="multipart/form-data">
      <div style="margin-bottom:16px">
        <label class="t-label">اختر الصورة (حتى <?= $maxMb ?> MB)</label>
        <input type="file" name="img" accept="image/*" required class="t-input" style="padding:8px;cursor:pointer" onchange="previewImg(this)">
      </div>
      <div id="preview-box" style="display:none;margin-bottom:16px">
        <img id="preview-img" style="max-width:200px;max-height:160px;border-radius:8px;border:1px solid #e2e8f0" alt="">
        <div id="orig-dims" style="font-size:12px;color:#64748b;margin-top:4px"></div>
      </div>
      <div class="t-row">
        <div class="t-col">
          <label class="t-label">العرض (بكسل)</label>
          <input type="number" name="width" id="inp-w" min="10" max="8000" value="800" class="t-input">
        </div>
        <div class="t-col">
          <label class="t-label">الارتفاع (بكسل)</label>
          <input type="number" name="height" id="inp-h" min="0" max="8000" value="0" class="t-input">
          <p style="font-size:11px;color:#64748b;margin-top:4px">0 = تلقائي حسب النسبة</p>
        </div>
        <div class="t-col">
          <label class="t-label">صيغة الإخراج</label>
          <select name="format" class="t-input">
            <option value="webp">WebP (أصغر حجم)</option>
            <option value="jpg">JPG</option>
            <option value="png">PNG (شفافية)</option>
          </select>
        </div>
      </div>
      <label style="display:flex;align-items:center;gap:8px;margin-top:14px;font-size:14px;cursor:pointer">
        <input type="checkbox" name="keep_ratio" checked> الحفاظ على نسبة الأبعاد
      </label>
      <div style="margin-top:16px">
        <button type="submit" class="t-btn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
          تغيير الحجم الآن
        </button>
      </div>
    </form>
  </div>

  <?php tool_ad(); ?>
</main>
<script>
function previewImg(inp){
  var file=inp.files[0];if(!file)return;
  var reader=new FileReader();
  reader.onload=function(e){
    var img=document.getElementById('preview-img');
    img.src=e.target.result;
    img.onload=function(){
      document.getElementById('orig-dims').textContent='الحجم الأصلي: '+img.naturalWidth+' × '+img.naturalHeight+' بكسل';
      document.getElementById('inp-w').value=img.naturalWidth;
      document.getElementById('inp-h').value=0;
    };
    document.getElementById('preview-box').style.display='block';
  };
  reader.readAsDataURL(file);
}
</script>
<?php tool_footer(); ?>
