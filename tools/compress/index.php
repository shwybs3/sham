<?php
require_once dirname(__DIR__) . '/_base.php';
require_once dirname(dirname(__DIR__)) . '/config.php';

$error = $resultPath = $originalSize = $compressedSize = '';
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
            $error = 'الصيغة غير مدعومة. استخدم JPG أو PNG أو WebP.';
        } else {
            $quality = max(10, min(95, (int)($_POST['quality'] ?? 82)));
            $maxW    = max(50, min(4000, (int)($_POST['max_w'] ?? 1920)));
            $outDir  = __DIR__ . '/../../uploads/.cache/compress';
            if (!is_dir($outDir)) @mkdir($outDir, 0755, true);
            $outName = 'c_' . uniqid() . '.webp';
            $outPath = $outDir . '/' . $outName;
            $result  = compress_image_to($f['tmp_name'], $outPath, $maxW, $maxW, $quality);
            if ($result) {
                $originalSize   = round($f['size'] / 1024, 1) . ' KB';
                $compressedSize = round(filesize($outPath) / 1024, 1) . ' KB';
                $savings        = round((1 - filesize($outPath) / $f['size']) * 100);
                $resultPath     = url('uploads/.cache/compress/' . $outName);
            } else {
                $error = 'تعذّر معالجة الصورة. تأكد من صلاحية الملف.';
            }
        }
    }
}

$schema = json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'WebApplication',
    'name'     => 'ضاغط الصور المجاني',
    'url'      => TOOLS_BASE_URL . '/tools/compress/',
    'description' => 'ضغط الصور ببلاش وتحويلها إلى WebP بدون فقدان جودة كبير',
    'applicationCategory' => 'UtilitiesApplication',
    'operatingSystem' => 'All',
    'offers' => ['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);

tool_head('ضاغط الصور — ضغط مجاني وتحويل إلى WebP | yassota', 'ضغط الصور مجاناً وتحويلها إلى WebP بنسبة توفير تصل 85% — يعمل مباشرة من المتصفح بدون تسجيل', $schema);
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#dbeafe">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M3 9h18M9 21V9"/></svg>
    </div>
    <h1>ضاغط الصور المجاني</h1>
    <p>ضغط JPG وPNG وWebP وتحويلها إلى WebP بنسبة توفير تصل 85% — مجانًا، بدون حدود، وبدون تسجيل. تُعالَج الصورة على السيرفر وتُحذف تلقائيًا.</p>
  </div>

<?php if ($error): ?>
  <div class="t-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($resultPath): ?>
  <div class="t-card" style="border-color:#86efac;background:#f0fdf4">
    <h2 style="color:#16a34a">✓ تم الضغط بنجاح</h2>
    <div style="display:flex;gap:18px;flex-wrap:wrap;margin-bottom:16px">
      <span>الحجم الأصلي: <strong><?= $originalSize ?></strong></span>
      <span>بعد الضغط: <strong><?= $compressedSize ?></strong></span>
      <span style="color:#16a34a;font-weight:700">توفير: <?= $savings ?>%</span>
    </div>
    <a href="<?= htmlspecialchars($resultPath) ?>" download="compressed.webp" class="t-btn t-btn-success">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
      تحميل الصورة المضغوطة (WebP)
    </a>
  </div>
<?php endif; ?>

  <div class="t-card">
    <form method="post" enctype="multipart/form-data">
      <div style="margin-bottom:16px">
        <label class="t-label">اختر الصورة (JPG, PNG, WebP, GIF — حتى <?= $maxMb ?> MB)</label>
        <input type="file" name="img" accept="image/*" required class="t-input" style="padding:8px;cursor:pointer">
      </div>
      <div class="t-row">
        <div class="t-col">
          <label class="t-label">جودة الضغط (10-95)</label>
          <input type="number" name="quality" min="10" max="95" value="82" class="t-input">
          <p style="font-size:11px;color:#64748b;margin-top:4px">82 = توازن مثالي جودة/حجم</p>
        </div>
        <div class="t-col">
          <label class="t-label">أقصى عرض (بكسل)</label>
          <input type="number" name="max_w" min="50" max="4000" value="1920" class="t-input">
          <p style="font-size:11px;color:#64748b;margin-top:4px">0 = بدون تغيير الأبعاد</p>
        </div>
      </div>
      <div style="margin-top:16px">
        <button type="submit" class="t-btn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
          اضغط الصورة الآن
        </button>
      </div>
    </form>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>لماذا WebP؟</h2>
    <ul style="list-style:none;display:flex;flex-direction:column;gap:8px;font-size:14px;color:#475569">
      <li>✓ أصغر بـ 30-80% من JPG و PNG بنفس الجودة</li>
      <li>✓ يدعمه Chrome وFirefox وSafari وEdge</li>
      <li>✓ مثالي لتحسين سرعة المواقع ونتائج Google Core Web Vitals</li>
      <li>✓ يدعم الشفافية (مثل PNG) والحركة (مثل GIF)</li>
    </ul>
  </div>
</main>
<?php tool_footer(); ?>
