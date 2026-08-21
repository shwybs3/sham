<?php
require_once __DIR__ . '/../config.php';
requireAdminLogin();

$id = (int)($_GET['id'] ?? 0);
$isNew = $id === 0;

$article = ['title'=>'','slug'=>'','description'=>'','tags'=>'','content'=>'','status'=>'draft'];
if (!$isNew) {
    $row = $pdo->prepare("SELECT * FROM articles WHERE id=?");
    $row->execute([$id]);
    $article = $row->fetch();
    if (!$article) { header('Location: articles.php'); exit; }
}

$pageTitle = $isNew ? 'مقال جديد' : 'تعديل: ' . $article['title'];
$activeNav = 'articles';
$ok = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck()) {
        $error = 'انتهت صلاحية الجلسة.';
    } else {
        $title  = trim($_POST['title'] ?? '');
        $slug   = trim($_POST['slug'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $tags   = trim($_POST['tags'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['draft','published'], true) ? $_POST['status'] : 'draft';
        $content = $_POST['content'] ?? '';

        // تنظيف الـ slug
        if ($slug === '') {
            $slug = mb_strtolower($title, 'UTF-8');
            $slug = preg_replace('/[\s\-]+/', '-', $slug);
            $slug = preg_replace('/[^\p{L}\p{N}\-]/u', '', $slug);
            $slug = trim($slug, '-');
        }
        $slug = preg_replace('/[\s]+/', '-', $slug);
        $slug = preg_replace('/[^a-zA-Z0-9\-\x{0600}-\x{06FF}\x{0750}-\x{077F}]/u', '', $slug);
        $slug = trim($slug, '-');

        if ($title === '') {
            $error = 'العنوان مطلوب.';
        } elseif ($slug === '') {
            $error = 'الـ Slug مطلوب.';
        } elseif ($content === '') {
            $error = 'المحتوى مطلوب.';
        } else {
            try {
                if ($isNew) {
                    $stmt = $pdo->prepare("INSERT INTO articles (title,slug,description,tags,content,status) VALUES (?,?,?,?,?,?)");
                    $stmt->execute([$title, $slug, $desc ?: null, $tags ?: null, $content, $status]);
                    $newId = (int)$pdo->lastInsertId();
                    header('Location: article-edit.php?id=' . $newId . '&saved=1');
                    exit;
                } else {
                    $stmt = $pdo->prepare("UPDATE articles SET title=?,slug=?,description=?,tags=?,content=?,status=? WHERE id=?");
                    $stmt->execute([$title, $slug, $desc ?: null, $tags ?: null, $content, $status, $id]);
                    $article = array_merge($article, compact('title','slug','desc','tags','content','status'));
                    $article['description'] = $desc;
                    $ok = 'تم حفظ المقال.';
                }
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $error = 'هذا الـ Slug مستخدم مسبقاً، اختر Slug آخر.';
                } else {
                    $error = 'خطأ: ' . $e->getMessage();
                }
            }
        }
    }
}

if (!$isNew && !$ok && !$error && isset($_GET['saved'])) {
    $ok = 'تم إنشاء المقال بنجاح.';
}

require __DIR__ . '/includes/admin-header.php';
?>
<style>
  .edit-grid{display:grid; grid-template-columns:1fr 320px; gap:22px; align-items:start;}
  @media(max-width:960px){.edit-grid{grid-template-columns:1fr;}}
  .tag-hint{font-size:12px; color:var(--ink-faint); margin-top:4px;}
  #content-editor{
    font-family:var(--mono); font-size:13px; line-height:1.7;
    min-height:460px; resize:vertical; width:100%; box-sizing:border-box;
    background:var(--panel-2); color:var(--ink); border:1px solid var(--line);
    border-radius:var(--radius); padding:14px; tab-size:2;
  }
  #content-editor:focus{outline:none; border-color:var(--brand); box-shadow:0 0 0 3px rgba(34,197,142,.15);}
  .upload-zone{
    border:2px dashed var(--line); border-radius:12px; padding:20px;
    text-align:center; cursor:pointer; transition:.2s; color:var(--ink-dim);
  }
  .upload-zone:hover,.upload-zone.drag{border-color:var(--brand); color:var(--brand);}
  .slug-preview{font-family:var(--mono); font-size:12px; color:var(--ink-faint); margin-top:4px; word-break:break-all;}
  .status-pill{display:flex; gap:8px; flex-wrap:wrap;}
  .status-pill label{
    display:flex; align-items:center; gap:6px; padding:8px 14px;
    border:1px solid var(--line); border-radius:8px; cursor:pointer; font-size:14px; font-weight:600;
    transition:.15s; color:var(--ink-dim);
  }
  .status-pill input[type=radio]{display:none;}
  .status-pill input:checked + span{color:var(--brand);}
  .status-pill label:has(input:checked){border-color:var(--brand); background:rgba(34,197,142,.08); color:var(--brand);}
</style>

<a href="articles.php" class="btn btn-ghost btn-sm" style="margin-bottom:18px;"><?= icon('arrow-right', 16) ?> كل المقالات</a>

<?php if ($ok):   ?><div class="notice ok"><?= icon('check-circle', 16) ?> <?= h($ok) ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice err"><?= icon('alert', 16) ?> <?= h($error) ?></div><?php endif; ?>

<form method="post" id="article-form">
  <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
  <div class="edit-grid">

    <!-- العمود الرئيسي -->
    <div>
      <div class="panel">
        <h2><?= icon('receipt', 18) ?> محتوى المقال</h2>

        <div class="field">
          <label>عنوان المقال *</label>
          <input type="text" name="title" id="title-input" value="<?= h($article['title']) ?>" required placeholder="مثال: أفضل طرق توفير المال في الدكان">
        </div>

        <div class="field">
          <label>المحتوى (HTML) *</label>
          <div class="upload-zone" id="upload-zone" onclick="document.getElementById('html-file').click()">
            <?= icon('upload', 20) ?>
            <div style="margin-top:8px; font-size:14px;">اسحب ملف HTML هنا أو انقر للرفع</div>
            <div style="font-size:12px; margin-top:4px; opacity:.7;">يُستبدل المحتوى بمحتوى الملف المرفوع</div>
          </div>
          <input type="file" id="html-file" accept=".html,.htm" style="display:none;" onchange="loadHtmlFile(this)">
          <textarea name="content" id="content-editor" required placeholder="&lt;h1&gt;عنوان المقال&lt;/h1&gt;&#10;&lt;p&gt;محتوى المقال...&lt;/p&gt;"><?= h($article['content']) ?></textarea>
        </div>
      </div>
    </div>

    <!-- الشريط الجانبي -->
    <div>
      <div class="panel">
        <h2><?= icon('settings', 18) ?> إعدادات النشر</h2>

        <div class="field" style="margin-bottom:16px;">
          <label>الحالة</label>
          <div class="status-pill">
            <label>
              <input type="radio" name="status" value="draft" <?= $article['status'] === 'draft' ? 'checked' : '' ?>>
              <span><?= icon('edit', 14) ?> مسودة</span>
            </label>
            <label>
              <input type="radio" name="status" value="published" <?= $article['status'] === 'published' ? 'checked' : '' ?>>
              <span><?= icon('check-circle', 14) ?> منشور</span>
            </label>
          </div>
        </div>

        <div class="field">
          <label>Slug (رابط المقال) *</label>
          <input type="text" name="slug" id="slug-input" value="<?= h($article['slug']) ?>" placeholder="my-article-slug" dir="ltr" style="text-align:left;">
          <div class="slug-preview" id="slug-preview"></div>
        </div>

        <div class="field">
          <label>وصف المقال (meta description)</label>
          <textarea name="description" rows="3" placeholder="وصف مختصر يظهر في نتائج البحث ومشاركات التواصل الاجتماعي..."><?= h($article['description'] ?? '') ?></textarea>
          <div class="tag-hint">يُنصح بـ 120–160 حرف</div>
        </div>

        <div class="field">
          <label>الهاشتاقات / الوسوم</label>
          <input type="text" name="tags" value="<?= h($article['tags'] ?? '') ?>" placeholder="تقنية، مال، دكان">
          <div class="tag-hint">افصل بين الوسوم بفاصلة</div>
        </div>

        <button class="btn btn-primary btn-block" type="submit"><?= icon('check', 16) ?> <?= $isNew ? 'إنشاء المقال' : 'حفظ التعديلات' ?></button>

        <?php if (!$isNew && $article['status'] === 'published'): ?>
        <a href="../article.php?slug=<?= urlencode($article['slug']) ?>" target="_blank" class="btn btn-ghost btn-block" style="margin-top:8px;">
          <?= icon('external', 15) ?> عرض المقال
        </a>
        <?php endif; ?>
      </div>

      <?php if (!$isNew): ?>
      <div class="panel" style="border-color:rgba(239,106,95,.3);">
        <h2 style="color:var(--debt);"><?= icon('trash', 18) ?> حذف المقال</h2>
        <form method="post" onsubmit="return confirm('حذف هذا المقال نهائياً؟');">
          <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
          <input type="hidden" name="action" value="delete_redirect">
          <input type="hidden" name="id" value="<?= (int)$id ?>">
          <button class="btn btn-danger btn-block" type="button"
            onclick="if(confirm('حذف هذا المقال نهائياً؟')) { fetch('articles.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'csrf=<?= urlencode(csrfToken()) ?>&action=delete&id=<?= (int)$id ?>'}).then(()=>location.href='articles.php') }">
            <?= icon('trash', 16) ?> حذف نهائياً
          </button>
        </form>
      </div>
      <?php endif; ?>
    </div>
  </div>
</form>

<script>
// Auto-generate slug from title
const titleInput = document.getElementById('title-input');
const slugInput  = document.getElementById('slug-input');
const slugPreview = document.getElementById('slug-preview');
const siteBase = '<?= rtrim($settings['site_url'] ?? '', '/') ?>/article.php?slug=';

function makeSlug(str) {
  return str
    .toLowerCase()
    .replace(/\s+/g, '-')
    .replace(/[^؀-ۿݐ-ݿa-z0-9\-]/g, '')
    .replace(/\-+/g, '-')
    .replace(/^\-|\-$/g, '');
}
function updatePreview() {
  const s = slugInput.value || makeSlug(titleInput.value);
  slugPreview.textContent = s ? siteBase + s : '';
}

titleInput.addEventListener('input', function() {
  if (!slugInput.dataset.manual) slugInput.value = makeSlug(this.value);
  updatePreview();
});
slugInput.addEventListener('input', function() {
  this.dataset.manual = '1';
  updatePreview();
});
updatePreview();

// HTML file upload
function loadHtmlFile(input) {
  const file = input.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = function(e) {
    document.getElementById('content-editor').value = e.target.result;
  };
  reader.readAsText(file, 'UTF-8');
  input.value = '';
}

// Drag & drop
const zone = document.getElementById('upload-zone');
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag'); });
zone.addEventListener('dragleave', () => zone.classList.remove('drag'));
zone.addEventListener('drop', e => {
  e.preventDefault(); zone.classList.remove('drag');
  const file = e.dataTransfer.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = ev => document.getElementById('content-editor').value = ev.target.result;
    reader.readAsText(file, 'UTF-8');
  }
});
</script>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
