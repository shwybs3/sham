<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/_base.php';

// Tool directory is admin-manageable via admin.php?page=web-tools — each row's
// slug must match an existing tools/{slug}/ folder for its "Try it" link to work.
$tools = [];
try {
    $rows = $pdo->query("SELECT slug, name AS title, short_description AS `desc`, icon_svg AS icon, icon_color AS color, icon_bg AS bg FROM web_tools WHERE status='published' ORDER BY sort_order, id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        if (is_dir(__DIR__ . '/' . $r['slug'])) $tools[] = $r;
    }
} catch (\Throwable $e) { $tools = []; }

$schema = json_encode([
  '@context'=>'https://schema.org',
  '@type'=>'WebSite',
  'name'=>'yassota أدوات — أدوات ويب مجانية',
  'url'=>TOOLS_BASE_URL.'/',
  'description'=>'مجموعة أدوات ويب مجانية احترافية: ضغط صور، QR Code، كلمات مرور، ذكاء اصطناعي، وأكثر',
]);
tool_head('yassota أدوات — أدوات ويب مجانية ومتقدمة','مجموعة أدوات ويب مجانية احترافية: ضغط الصور، توليد QR، كلمات المرور، ذكاء اصطناعي، تحليل النصوص وأكثر',$schema);
tool_header();
?>
<main class="t-main">
  <div class="t-hero" style="text-align:center">
    <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:18px;background:#dbeafe;margin:0 auto 16px">
      <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
    </div>
    <h1 style="font-size:28px">أدوات ويب مجانية ومتقدمة</h1>
    <p>أدوات احترافية تعمل مباشرة في متصفحك — بدون تسجيل، بدون حدود، مجانية 100%</p>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;margin-bottom:24px">
    <?php foreach ($tools as $t):
      $toolUrl = TOOLS_BASE_URL . '/tools/' . $t['slug'] . '/';
    ?>
    <a href="<?= htmlspecialchars($toolUrl) ?>" style="text-decoration:none;display:block">
      <div class="t-card" style="margin:0;height:100%;transition:transform .18s,box-shadow .18s;cursor:pointer" onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.12)'" onmouseleave="this.style.transform='';this.style.boxShadow=''">
        <div style="width:46px;height:46px;border-radius:12px;background:<?= $t['bg'] ?>;display:flex;align-items:center;justify-content:center;margin-bottom:12px">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="<?= in_array($t['slug'],['whatsapp']) ? $t['color'] : 'none' ?>" stroke="<?= $t['color'] ?>" stroke-width="2"><?= $t['icon'] ?></svg>
        </div>
        <div style="font-size:15px;font-weight:700;color:#0f172a;margin-bottom:6px"><?= htmlspecialchars($t['title']) ?></div>
        <div style="font-size:13px;color:#64748b;line-height:1.6"><?= htmlspecialchars($t['desc']) ?></div>
        <div style="margin-top:12px;font-size:12px;font-weight:700;color:<?= $t['color'] ?>">جرّب الآن ←</div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>لماذا أدوات yassota؟</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-top:4px">
      <?php
      $features = [
        ['icon'=>'🔒','title'=>'خصوصية تامة','desc'=>'أدوات كثيرة تعمل داخل متصفحك بالكامل دون إرسال أي بيانات'],
        ['icon'=>'⚡','title'=>'سريعة جداً','desc'=>'لا انتظار، لا تسجيل، لا حدود — نتائج فورية'],
        ['icon'=>'🆓','title'=>'مجانية 100%','desc'=>'جميع الأدوات مجانية ولا تتطلب اشتراكاً أو بطاقة ائتمانية'],
        ['icon'=>'🤖','title'=>'ذكاء اصطناعي','desc'=>'أدوات مدعومة بأحدث نماذج الذكاء الاصطناعي العربية'],
      ];
      foreach ($features as $f):
      ?>
      <div style="padding:14px;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0">
        <div style="font-size:22px;margin-bottom:6px"><?= $f['icon'] ?></div>
        <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:4px"><?= $f['title'] ?></div>
        <div style="font-size:12px;color:#64748b"><?= $f['desc'] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</main>
<?php tool_footer(); ?>
