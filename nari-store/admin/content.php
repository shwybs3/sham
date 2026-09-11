<?php
define('NARI_ADMIN', true);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/nav.php';
nari_require_login();

function v($val, $default = '') {
    return htmlspecialchars((string)($val ?? $default), ENT_QUOTES, 'UTF-8');
}

$csrf = nari_csrf_token();
$type = ($_GET['type'] ?? 'page') === 'article' ? 'article' : 'page';
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

$pages = nari_read_json(PAGES_FILE);
$articles = nari_read_json(ARTICLES_FILE);
$list = $type === 'article' ? $articles : $pages;

$editSlug = $_GET['edit'] ?? null;
$isNew = isset($_GET['new']);
$editItem = null;
if ($editSlug !== null) {
    foreach ($list as $item) {
        if (($item['slug'] ?? '') === $editSlug) { $editItem = $item; break; }
    }
}
$showForm = $isNew || $editItem !== null || ($editSlug !== null && $editItem === null && $editSlug === '');

$categories = ['مقال', 'دليل', 'إعلانات', 'أخبار', 'تحديثات'];

$MESSAGES = ['saved' => 'تم الحفظ بنجاح.', 'deleted' => 'تم الحذف بنجاح.'];
$ERRORS = ['invalid' => 'العنوان مطلوب، والمعرّف (slug) يجب أن يكون بأحرف إنجليزية صغيرة وأرقام وشرطات فقط، مثل: my-page-name.', 'exists' => 'يوجد عنصر آخر بنفس المعرّف (slug) مسبقاً.'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>الصفحات والمقالات | لوحة تحكم ناري ستور</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="../assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="admin.css">
</head>
<body>

<div class="top-bar">
  <div class="wrap">
    <h1><svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="none" aria-hidden="true"><path d="M4 4h16v4H4zM4 10h10v10H4zM16 10h4v10h-4z"/></svg> الصفحات <span>والمقالات</span></h1>
    <div style="display:flex; align-items:center; gap:14px;">
      <a href="../blog.php" target="_blank" class="btn ghost small">عرض المقالات ↗</a>
      <a href="logout.php" class="btn ghost small">تسجيل الخروج</a>
    </div>
  </div>
</div>
<?php nari_admin_nav('content'); ?>

<div class="wrap">

  <?php if ($msg && isset($MESSAGES[$msg])): ?><div class="success-box"><?= v($MESSAGES[$msg]) ?></div><?php endif; ?>
  <?php if ($err && isset($ERRORS[$err])): ?><div class="error-box"><?= v($ERRORS[$err]) ?></div><?php endif; ?>

  <div class="panel" style="margin-bottom:16px;">
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <a class="btn <?= $type === 'page' ? '' : 'ghost' ?> small" href="content.php?type=page">صفحات ثابتة (<?= count($pages) ?>)</a>
      <a class="btn <?= $type === 'article' ? '' : 'ghost' ?> small" href="content.php?type=article">مقالات وأخبار (<?= count($articles) ?>)</a>
    </div>
  </div>

  <?php if ($showForm): ?>
    <?php
      $item = $editItem ?? [];
      $slug = v($item['slug'] ?? '');
      $title = v($item['title'] ?? '');
      $metaDesc = v($item['meta_description'] ?? '');
      $keywords = v($item['keywords'] ?? '');
      $contentHtml = v($item['content_html'] ?? '');
      $published = !empty($item['published']);
    ?>
    <div class="panel">
      <h2><?= $editItem ? 'تعديل: ' . $title : ($type === 'article' ? 'مقال جديد' : 'صفحة جديدة') ?></h2>
      <p class="hint">
        حقل "المحتوى" يقبل HTML كاملاً (فقرات &lt;p&gt;، عناوين &lt;h2&gt;، قوائم &lt;ul&gt;&lt;li&gt;، صور &lt;img&gt;...) — يُعرض كما هو على الصفحة العامة، بنفس تنسيق صفحات مثل "سياسة الخصوصية".
        <?php if ($type === 'article'): ?>
          <br><strong>مهم:</strong> انشر فقط معلومات موثّقة من مصدرها الرسمي (تحديثات المتجر، شروحات، إعلانات مؤكدة). لأي معلومة عامة حسّاسة (مواعيد رسمية حكومية مثلاً)، تحقق من المصدر الرسمي قبل النشر ولا تعتمد على تخمين.
        <?php endif; ?>
      </p>
      <form method="post" action="save.php">
        <input type="hidden" name="csrf" value="<?= v($csrf) ?>">
        <input type="hidden" name="action" value="<?= $type === 'article' ? 'save_article' : 'save_page' ?>">
        <input type="hidden" name="original_slug" value="<?= $slug ?>">

        <div class="grid2">
          <div class="field">
            <label>العنوان</label>
            <input type="text" name="title" value="<?= $title ?>" required>
          </div>
          <div class="field">
            <label>المعرّف بالرابط (slug — أحرف إنجليزية صغيرة وأرقام وشرطات فقط)</label>
            <input type="text" name="slug" value="<?= $slug ?>" pattern="[a-z0-9]+(-[a-z0-9]+)*" required placeholder="my-page-slug">
          </div>
        </div>

        <?php if ($type === 'article'): ?>
        <div class="grid2">
          <div class="field">
            <label>التصنيف</label>
            <select name="category">
              <?php foreach ($categories as $cat): ?>
                <option value="<?= v($cat) ?>" <?= ($item['category'] ?? '') === $cat ? 'selected' : '' ?>><?= v($cat) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>مقتطف قصير (يظهر في قائمة المقالات)</label>
            <input type="text" name="excerpt" value="<?= v($item['excerpt'] ?? '') ?>">
          </div>
        </div>
        <?php endif; ?>

        <div class="field">
          <label>وصف SEO (Meta Description)</label>
          <input type="text" name="meta_description" value="<?= $metaDesc ?>" maxlength="160">
        </div>
        <div class="field">
          <label>كلمات مفتاحية (اختياري، مفصولة بفواصل)</label>
          <input type="text" name="keywords" value="<?= $keywords ?>">
        </div>
        <div class="field">
          <label>المحتوى (HTML)</label>
          <textarea name="content_html" class="code-editor" style="min-height:320px;" spellcheck="false"><?= $contentHtml ?></textarea>
        </div>

        <div class="check-row">
          <input type="checkbox" id="published" name="published" <?= $published ? 'checked' : '' ?>>
          <label for="published" style="margin:0;">منشور (يظهر للزوار ومحركات البحث)</label>
        </div>

        <div style="display:flex; gap:12px; flex-wrap:wrap;">
          <button type="submit" class="btn" style="width:auto; padding:12px 28px;">حفظ</button>
          <a class="btn ghost small" href="content.php?type=<?= $type ?>">إلغاء والعودة للقائمة</a>
          <?php if ($editItem): ?>
            <a class="btn ghost small" href="../<?= $type === 'article' ? 'article/' . $slug : 'p/' . $slug ?>" target="_blank">عرض على الموقع ↗</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  <?php else: ?>

    <div class="panel">
      <h2><?= $type === 'article' ? 'كل المقالات والأخبار' : 'كل الصفحات الثابتة' ?> (<?= count($list) ?>)</h2>
      <p class="hint">
        <?= $type === 'article'
            ? 'تظهر المقالات المنشورة تلقائياً في صفحة "المقالات والأخبار" العامة، وكل مقال له رابط مستقل: /article/&lt;slug&gt;'
            : 'الصفحات الثابتة لا تُدرج في أي قائمة تلقائياً — أضف رابطها يدوياً من القائمة أو التذييل عند الحاجة. رابط كل صفحة: /p/&lt;slug&gt;' ?>
      </p>
      <a class="btn small" style="width:auto; padding:10px 22px; margin-bottom:20px;" href="content.php?type=<?= $type ?>&new=1"><?= $type === 'article' ? 'مقال جديد +' : 'صفحة جديدة +' ?></a>

      <?php if (empty($list)): ?>
        <p class="hint">لا يوجد عناصر بعد.</p>
      <?php endif; ?>
      <?php foreach ($list as $item): ?>
        <div class="file-row">
          <div class="fname">
            <span class="badge <?= !empty($item['published']) ? 'on' : 'off' ?>"><?= !empty($item['published']) ? 'منشور' : 'مسودة' ?></span>
            <a href="content.php?type=<?= $type ?>&edit=<?= rawurlencode($item['slug']) ?>"><strong><?= v($item['title']) ?></strong></a>
            <?php if ($type === 'article' && !empty($item['category'])): ?><span class="tag-pill"><?= v($item['category']) ?></span><?php endif; ?>
          </div>
          <div class="fmeta"><?= v($item['updated_at'] ?? ($item['published_at'] ?? '')) ?></div>
          <div class="file-actions">
            <a class="btn ghost tiny" href="../<?= $type === 'article' ? 'article/' . v($item['slug']) : 'p/' . v($item['slug']) ?>" target="_blank">عرض ↗</a>
            <form method="post" action="save.php" onsubmit="return confirm('حذف \'<?= v($item['title']) ?>\' نهائياً؟');">
              <input type="hidden" name="csrf" value="<?= v($csrf) ?>">
              <input type="hidden" name="action" value="<?= $type === 'article' ? 'delete_article' : 'delete_page' ?>">
              <input type="hidden" name="target_slug" value="<?= v($item['slug']) ?>">
              <button type="submit" class="btn danger tiny">حذف</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  <?php endif; ?>

</div>

<footer class="admin-foot">لوحة تحكم ناري ستور — للاستخدام الداخلي فقط، غير مفهرسة في محركات البحث.</footer>
</body>
</html>
