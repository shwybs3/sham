<?php
require_once dirname(__DIR__) . '/_base.php';
require_once dirname(dirname(__DIR__)) . '/config.php';

$error = $resultPath = $originalSize = $compressedSize = $savings = '';
$maxMb = 10;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['img'])) {
    $f = $_FILES['img'];
    do {
        if ($f['error'] !== UPLOAD_ERR_OK) { $error = 'حدث خطأ أثناء رفع الملف. حاول مجدداً.'; break; }
        if ($f['size'] > $maxMb * 1024 * 1024) { $error = "حجم الملف ({$maxMb} MB) تجاوز الحد المسموح."; break; }
        if ($f['size'] < 100) { $error = 'الملف صغير جداً ويبدو تالفاً.'; break; }

        // ── Security: validate actual MIME type via libmagic ──────────────────
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $f['tmp_name']);
        finfo_close($finfo);
        $allowed  = ['image/jpeg','image/png','image/gif','image/webp','image/bmp','image/tiff'];
        if (!in_array($mimeType, $allowed, true)) {
            $error = 'نوع الملف مرفوض. يُقبل فقط: JPG, PNG, WebP, GIF, BMP.';
            break;
        }

        // ── Security: scan first 512 bytes for PHP/script signatures ──────────
        $head = file_get_contents($f['tmp_name'], false, null, 0, 512);
        if (preg_match('/<\?(?:php|=)/i', $head) || preg_match('/<script\b/i', $head)) {
            $error = 'تم رفض الملف لأسباب أمنية — يحتوي على نصوص برمجية.';
            break;
        }

        // ── Extension check (secondary, after MIME) ────────────────────────────
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','gif','webp','bmp','tiff','tif'], true)) {
            $error = 'امتداد الملف غير مدعوم.';
            break;
        }

        // ── Process ────────────────────────────────────────────────────────────
        $quality = max(10, min(95, (int)($_POST['quality'] ?? 82)));
        $maxW    = max(0,  min(4000, (int)($_POST['max_w'] ?? 1920)));
        if ($maxW === 0) $maxW = 8000; // no resize
        $outDir  = dirname(__DIR__, 2) . '/uploads/.cache/compress';
        if (!is_dir($outDir)) @mkdir($outDir, 0755, true);
        $outName = 'c_' . bin2hex(random_bytes(8)) . '.webp';
        $outPath = $outDir . '/' . $outName;
        $result  = compress_image_to($f['tmp_name'], $outPath, $maxW, $maxW, $quality);
        if ($result) {
            $originalSize   = round($f['size'] / 1024, 1);
            $compressedSize = round(filesize($outPath) / 1024, 1);
            $savings        = max(0, round((1 - filesize($outPath) / $f['size']) * 100));
            $resultPath     = url('uploads/.cache/compress/' . $outName);
        } else {
            $error = 'تعذّر معالجة الصورة. تأكد من أن الملف صورة صالحة.';
        }
    } while (false);
}

$schema = json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'WebApplication',
    'name'     => 'ضاغط الصور المجاني — تحويل JPG وPNG إلى WebP',
    'url'      => TOOLS_BASE_URL . '/tools/compress/',
    'description' => 'ضغط صور JPG وPNG وWebP وتحويلها إلى WebP مجاناً مع توفير يصل 85% من الحجم',
    'applicationCategory' => 'UtilitiesApplication',
    'operatingSystem' => 'All',
    'offers' => ['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
    'featureList' => ['ضغط JPG','ضغط PNG','تحويل إلى WebP','بدون تسجيل','بدون حدود'],
]);

tool_head(
    'ضاغط الصور المجاني — تحويل JPG وPNG إلى WebP بدون فقدان الجودة',
    'اضغط صور JPG وPNG وWebP وحوّلها إلى WebP مجاناً بنسبة توفير تصل 85% من الحجم الأصلي. تُعالَج على السيرفر وتُحذف تلقائياً.',
    $schema,
    '#2563eb'
);
tool_header();
?>
<main class="t-main">

  <div class="t-hero">
    <div class="t-hero-icon" style="background:#dbeafe">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2">
        <rect x="3" y="3" width="18" height="18" rx="3"/>
        <path d="M3 9h18M9 21V9"/>
      </svg>
    </div>
    <h1>ضاغط الصور المجاني</h1>
    <p>ضغط JPG وPNG وWebP وتحويلها إلى WebP — توفير يصل 85% — مجاناً، بدون حدود، بدون تسجيل. تُعالَج الصورة على السيرفر وتُحذف تلقائياً في دقائق.</p>
  </div>

  <?php if ($error): ?>
  <div class="t-error" role="alert"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($resultPath): ?>
  <div class="t-card" style="border:2px solid #86efac;background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%)">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px">
      <div style="width:44px;height:44px;background:#16a34a;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <div>
        <h2 style="color:#15803d;margin:0;font-size:18px">تم الضغط بنجاح</h2>
        <p style="margin:2px 0 0;font-size:13px;color:#166534">الصورة جاهزة للتحميل</p>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:18px">
      <div style="background:#fff;border-radius:12px;padding:12px;text-align:center">
        <div style="font-size:11px;color:#64748b;margin-bottom:4px">الحجم الأصلي</div>
        <div style="font-size:18px;font-weight:700;color:#334155"><?= $originalSize ?> KB</div>
      </div>
      <div style="background:#fff;border-radius:12px;padding:12px;text-align:center">
        <div style="font-size:11px;color:#64748b;margin-bottom:4px">بعد الضغط</div>
        <div style="font-size:18px;font-weight:700;color:#334155"><?= $compressedSize ?> KB</div>
      </div>
      <div style="background:#dcfce7;border-radius:12px;padding:12px;text-align:center;border:1px solid #86efac">
        <div style="font-size:11px;color:#166534;margin-bottom:4px">التوفير</div>
        <div style="font-size:22px;font-weight:800;color:#16a34a"><?= $savings ?>%</div>
      </div>
    </div>
    <a href="<?= htmlspecialchars($resultPath) ?>" download="compressed.webp" class="t-btn" style="background:#16a34a;border-color:#16a34a;width:100%;justify-content:center;font-size:16px;padding:14px">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
      تحميل الصورة المضغوطة (WebP)
    </a>
    <p style="text-align:center;font-size:12px;color:#6b7280;margin:10px 0 0">سيُحذف الملف من السيرفر تلقائياً خلال ساعة</p>
  </div>
  <?php endif; ?>

  <div class="t-card">
    <form method="post" enctype="multipart/form-data" id="compress-form">

      <!-- Drop Zone -->
      <div class="drop-zone" id="drop-zone" onclick="document.getElementById('img-file').click()">
        <div class="drop-zone-idle" id="dz-idle">
          <div class="dz-icon-wrap">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--tool-h)" stroke-width="1.5">
              <rect x="3" y="3" width="18" height="13" rx="2"/>
              <circle cx="8.5" cy="8.5" r="1.5"/>
              <polyline points="21 15 16 10 5 21"/>
            </svg>
          </div>
          <p class="dz-title">اسحب الصورة هنا أو انقر للاختيار</p>
          <p class="dz-sub">JPG, PNG, WebP, GIF, BMP — حتى <?= $maxMb ?> MB</p>
          <div class="dz-btn">اختر ملفاً</div>
        </div>
        <div class="drop-zone-preview" id="dz-preview" style="display:none">
          <img id="dz-img" alt="">
          <div id="dz-info"></div>
          <button type="button" class="dz-clear" id="dz-clear" title="إزالة الصورة">✕ إزالة</button>
        </div>
        <input type="file" name="img" id="img-file" accept="image/jpeg,image/png,image/gif,image/webp,image/bmp" required style="display:none">
      </div>

      <div class="t-row" style="margin-top:20px">
        <div class="t-col">
          <label class="t-label">جودة الضغط (10–95)</label>
          <div style="display:flex;align-items:center;gap:12px">
            <input type="range" name="quality" id="quality-range" min="10" max="95" value="82"
                   style="flex:1;accent-color:var(--tool-h)" oninput="document.getElementById('quality-val').textContent=this.value">
            <span id="quality-val" style="font-weight:700;color:var(--tool-h);min-width:28px;text-align:center">82</span>
          </div>
          <p style="font-size:11px;color:#64748b;margin-top:4px">82 = توازن مثالي بين الجودة والحجم</p>
        </div>
        <div class="t-col">
          <label class="t-label">أقصى عرض (بكسل)</label>
          <input type="number" name="max_w" min="0" max="4000" value="1920" class="t-input" placeholder="0 = بدون تغيير">
          <p style="font-size:11px;color:#64748b;margin-top:4px">0 = بدون تغيير الأبعاد</p>
        </div>
      </div>

      <div style="margin-top:20px">
        <button type="submit" class="t-btn" id="submit-btn" style="width:100%;justify-content:center;font-size:16px;padding:14px">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
          اضغط الصورة الآن
        </button>
      </div>
    </form>
  </div>

  <?php tool_ad(); ?>

  <!-- Article content -->
  <div class="t-card">
    <h2 class="t-article-h2">لماذا تحويل الصور إلى WebP؟</h2>
    <p>WebP هو تنسيق صور حديث طوّرته شركة Google عام 2010، ويوفر ضغطاً فائقاً مع الحفاظ على جودة بصرية لا تُلاحَظ فروقها بالعين المجردة. مقارنةً بـ JPG وPNG التقليديَّين، يُقلّص WebP الحجم بنسبة تتراوح بين 25% و80% لنفس مستوى الجودة، مما يُسرّع تحميل المواقع بشكل ملحوظ ويُحسّن تجربة المستخدم ونتائج Google Core Web Vitals.</p>

    <h2 class="t-article-h2">مزايا ضاغط الصور المجاني في yassota</h2>
    <div class="t-features-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;margin-top:14px">
      <?php
      $features = [
        ['🔒','أمان تام','صورك تُعالَج على السيرفر وتُحذف تلقائياً. لا تُخزَّن ولا تُشارَك مع أي طرف.'],
        ['⚡','سرعة فائقة','معالجة الصور في ثوانٍ باستخدام مكتبات GD وImageMagick المحسَّنة.'],
        ['📏','تحكم كامل','اضبط جودة الضغط بين 10 و95، وحدد أقصى عرض للصورة.'],
        ['🆓','مجاني 100%','لا تسجيل، لا بريد إلكتروني، لا حدود يومية. ضغط غير محدود.'],
        ['🖼️','دعم متعدد','JPG, PNG, WebP, GIF, BMP, TIFF — جميع الصيغ الشائعة مدعومة.'],
        ['🌐','WebP للجميع','Chrome وFirefox وSafari وEdge وأجهزة Android كلها تدعم WebP.'],
      ];
      foreach ($features as [$icon,$name,$desc]):
      ?>
      <div style="background:var(--tool-light);border-radius:12px;padding:14px">
        <div style="font-size:24px;margin-bottom:8px"><?= $icon ?></div>
        <div style="font-weight:700;color:var(--tool-h);margin-bottom:4px"><?= $name ?></div>
        <div style="font-size:13px;color:#475569;line-height:1.5"><?= $desc ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <h2 class="t-article-h2" style="margin-top:28px">كيف يعمل ضاغط الصور؟</h2>
    <p>خوارزمية الضغط في yassota تمرّ بثلاث مراحل: أولاً، تُحلَّل بيانات الصورة الأصلية للتعرف على نوع المحتوى (صورة فوتوغرافية، رسم توضيحي، لقطة شاشة). ثانياً، يُطبَّق خوارزم ضغط WebP المناسب مع مستوى الجودة الذي حددته. ثالثاً، تُوجَز الميتاداتا غير الضرورية (EXIF، ICC profile) لتوفير مساحة إضافية. النتيجة: صورة مطابقة بصرياً للأصل لكن بجزء بسيط من حجمه.</p>

    <h2 class="t-article-h2">الفرق بين جودة 82 و95 و60</h2>
    <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;font-size:13px;margin-top:10px">
      <tr style="background:var(--tool-light)">
        <th style="padding:10px 14px;text-align:right;font-weight:700">مستوى الجودة</th>
        <th style="padding:10px 14px;text-align:center;font-weight:700">نسبة التوفير</th>
        <th style="padding:10px 14px;text-align:right;font-weight:700">الاستخدام المثالي</th>
      </tr>
      <?php foreach ([
        ['95 (عالية جداً)','15–30%','صور الطباعة، المنتجات بدقة عالية'],
        ['82 (مثالي)','50–70%','مواقع الويب — التوازن المثالي'],
        ['70 (متوسط)','70–80%','الصور المصغرة، البطاقات، الأيقونات'],
        ['50 (منخفض)','80–90%','الصور التعبيرية، الخلفيات الباهتة'],
      ] as [$q,$s,$u]): ?>
      <tr style="border-top:1px solid var(--tool-border)">
        <td style="padding:9px 14px;font-weight:600"><?= $q ?></td>
        <td style="padding:9px 14px;text-align:center;color:var(--tool-h);font-weight:700"><?= $s ?></td>
        <td style="padding:9px 14px;color:#475569"><?= $u ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    </div>

    <h2 class="t-article-h2" style="margin-top:28px">تأثير ضغط الصور على SEO وسرعة الموقع</h2>
    <p>وفقاً لدراسات Google، 53% من المستخدمين يتركون الصفحة إذا استغرق تحميلها أكثر من 3 ثوانٍ. الصور تمثل في المتوسط 60–70% من إجمالي حجم صفحة الويب. بضغط صورك إلى WebP، تُقلّص وقت التحميل، وترفع نقاط Lighthouse، وتُحسّن تجربة المستخدم على الشبكات البطيئة والهواتف.</p>
    <p>محرك بحث Google يضع سرعة الصفحة (Core Web Vitals) ضمن عوامل الترتيب الرئيسية منذ 2021. وهذا يعني أن ضغط صورك لم يعد ترفاً بل ضرورة SEO حقيقية للمواقع العربية والعالمية.</p>

    <h2 class="t-article-h2" style="margin-top:28px">أدوات ذات صلة</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;margin-top:10px">
      <?php foreach ([
        ['resize','تغيير حجم الصورة'],
        ['img-convert','تحويل صيغ الصور'],
        ['img-crop','قص الصور'],
        ['qr-code','مولد QR Code'],
        ['color-picker','اختيار الألوان'],
      ] as [$s,$t]): ?>
      <a href="<?= TOOLS_BASE_URL ?>/tools/<?= $s ?>/" style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:var(--tool-light);border-radius:10px;text-decoration:none;color:var(--tool-h);font-size:13px;font-weight:600">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
        <?= $t ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php tool_section('أسئلة شائعة حول ضاغط الصور', '', true); ?>
  <div class="t-card">
    <?php $faqs = [
      ['هل ضاغط الصور مجاني؟', 'نعم، الأداة مجانية تماماً وبدون أي قيود. لا تسجيل ولا بريد إلكتروني ولا حد يومي للاستخدام.'],
      ['هل تُحفظ صوري على السيرفر؟', 'تُعالَج الصور مؤقتاً على السيرفر للضغط ثم تُحذف تلقائياً. لا تُخزَّن بشكل دائم ولا تُشارَك مع أي طرف ثالث.'],
      ['ما الفرق بين WebP وJPG؟', 'WebP يوفر ضغطاً أكثر كفاءة من JPG. بنفس الجودة البصرية، يكون WebP أصغر بنسبة 25–35%. كما يدعم الشفافية مثل PNG.'],
      ['هل يدعم المتصفحات العربية WebP؟', 'نعم، جميع المتصفحات الحديثة (Chrome, Firefox, Safari 14+, Edge, Opera) وأجهزة Android تدعم WebP بشكل كامل.'],
      ['كيف أحوّل JPG إلى WebP؟', 'ارفع صورة JPG باستخدام الأداة أعلاه، اضبط جودة الضغط، ثم انقر "اضغط الصورة الآن". ستحصل على WebP جاهز للتحميل.'],
      ['ما الحجم الأقصى للصورة؟', 'تقبل الأداة صوراً حتى 10 ميغابايت. للصور الأكبر، نوصي بتقليص أبعادها أولاً.'],
      ['هل يمكن الضغط دون تغيير الأبعاد؟', 'نعم، اضبط حقل "أقصى عرض" على 0 وسيُطبَّق الضغط فقط دون تغيير أبعاد الصورة.'],
      ['ما هو أفضل مستوى جودة للمواقع؟', 'نوصي بـ 82 للصفحات العامة، و75 للصور المصغرة، و90 لصور المنتجات حيث يهم التفاصيل الدقيقة.'],
    ];
    foreach ($faqs as [$q, $a]): ?>
    <div class="t-faq-item">
      <div class="t-faq-q"><?= htmlspecialchars($q) ?></div>
      <div class="t-faq-a"><?= htmlspecialchars($a) ?></div>
    </div>
    <?php endforeach; ?>
  </div>

</main>

<style>
/* ── Drop Zone ─────────────────────────────────────────────────────── */
.drop-zone {
  border: 2.5px dashed var(--tool-border);
  border-radius: 16px;
  background: var(--tool-light);
  cursor: pointer;
  transition: border-color .2s, background .2s;
  min-height: 180px;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
}
.drop-zone:hover,
.drop-zone.drag-over {
  border-color: var(--tool-h);
  background: rgba(var(--tool-rgb),.15);
}
.drop-zone-idle {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 24px 16px;
  text-align: center;
  pointer-events: none;
}
.dz-icon-wrap {
  width: 64px; height: 64px;
  background: rgba(var(--tool-rgb),.12);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 4px;
}
.dz-title { font-size: 17px; font-weight: 700; color: var(--tool-h); margin: 0; }
.dz-sub   { font-size: 13px; color: #64748b; margin: 0; }
.dz-btn   {
  margin-top: 8px;
  padding: 8px 22px;
  background: var(--tool-h);
  color: #fff;
  border-radius: 30px;
  font-size: 14px;
  font-weight: 600;
  pointer-events: none;
}
/* preview */
.drop-zone-preview {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 20px;
  width: 100%;
}
#dz-img {
  max-height: 180px;
  max-width: 100%;
  border-radius: 10px;
  object-fit: contain;
  box-shadow: 0 4px 16px rgba(0,0,0,.12);
}
#dz-info { font-size: 13px; color: #475569; text-align: center; }
.dz-clear {
  padding: 6px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  background: #fff;
  color: #ef4444;
  font-size: 13px;
  cursor: pointer;
  transition: background .15s;
}
.dz-clear:hover { background: #fef2f2; }
</style>

<script>
(function(){
  const zone   = document.getElementById('drop-zone');
  const input  = document.getElementById('img-file');
  const idle   = document.getElementById('dz-idle');
  const prev   = document.getElementById('dz-preview');
  const img    = document.getElementById('dz-img');
  const info   = document.getElementById('dz-info');
  const clrBtn = document.getElementById('dz-clear');

  function showPreview(file) {
    if (!file || !file.type.startsWith('image/')) return;
    const url = URL.createObjectURL(file);
    img.src = url;
    img.onload = () => URL.revokeObjectURL(url);
    const kb = (file.size / 1024).toFixed(1);
    const mb = (file.size / 1048576).toFixed(2);
    info.textContent = file.name + ' — ' + (file.size > 1048576 ? mb + ' MB' : kb + ' KB');
    idle.style.display = 'none';
    prev.style.display = 'flex';
  }

  function clearFile(e) {
    e.stopPropagation();
    input.value = '';
    img.src = '';
    info.textContent = '';
    prev.style.display = 'none';
    idle.style.display = 'flex';
  }

  input.addEventListener('change', () => input.files[0] && showPreview(input.files[0]));
  clrBtn.addEventListener('click', clearFile);

  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
  zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('drag-over');
    const f = e.dataTransfer.files[0];
    if (f) { showFile(f); }
  });

  function showFile(file) {
    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    showPreview(file);
  }

  // quality range live label already inline (oninput)
})();
</script>

<?php tool_footer(); ?>
