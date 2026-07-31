<?php
/**
 * Web Tools Management Admin Panel
 * يتم تضمينها من admin.php عند ?page=web-tools
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';
require_once __DIR__ . '/lang.php';

evil_check_ban($pdo);
require_admin();

$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);
$msg = $_GET['msg'] ?? '';
$error = '';

// ── Delete Tool ──
if ($action === 'delete' && $id && csrf_check($_GET['t'] ?? '')) {
    try {
        delete_web_tool($pdo, $id);
        header("Location: admin.php?page=web-tools&msg=deleted"); exit;
    } catch (Throwable $e) {
        $error = __('delete_tool') . ': ' . $e->getMessage();
    }
}

// ── Save Tool ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check($_POST['csrf'] ?? '')) {
    $toolId = (int)($_POST['id'] ?? 0);
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'slug' => trim($_POST['slug'] ?? ''),
        'seo_title' => trim($_POST['seo_title'] ?? ''),
        'meta_description' => trim($_POST['meta_description'] ?? ''),
        'meta_tags' => trim($_POST['meta_tags'] ?? ''),
        'short_description' => trim($_POST['short_description'] ?? ''),
        'long_description' => trim($_POST['long_description'] ?? ''),
        'tutorials' => trim($_POST['tutorials'] ?? ''),
        'whats_new' => trim($_POST['whats_new'] ?? ''),
        'how_it_started' => trim($_POST['how_it_started'] ?? ''),
        'status' => in_array($_POST['status'] ?? 'draft', ['published', 'draft']) ? $_POST['status'] : 'draft',
    ];

    if ($data['features'] = $_POST['features'] ?? '') {
        $feats = array_filter(array_map('trim', explode("\n", $data['features'])));
        $data['features'] = json_encode($feats);
    }
    if ($pros = $_POST['pros'] ?? '') {
        $prosList = array_filter(array_map('trim', explode("\n", $pros)));
        $data['pros'] = json_encode($prosList);
    }
    if ($cons = $_POST['cons'] ?? '') {
        $consList = array_filter(array_map('trim', explode("\n", $cons)));
        $data['cons'] = json_encode($consList);
    }
    // FAQ textarea format: "Q: question" then "A: answer" (answer may span
    // multiple lines until the next "Q:" block).
    if ($faqRaw = trim($_POST['faq'] ?? '')) {
        $pairs = [];
        if (preg_match_all('/Q:\s*(.+?)\s*\R+A:\s*(.+?)(?=\R+Q:|\z)/is', $faqRaw, $m, PREG_SET_ORDER)) {
            foreach ($m as $pair) {
                $q = trim($pair[1]);
                $a = trim($pair[2]);
                if ($q && $a) $pairs[] = ['q' => $q, 'a' => $a];
            }
        }
        $data['faq'] = json_encode($pairs, JSON_UNESCAPED_UNICODE);
    }

    if (!$data['name']) {
        $error = __('tool_name') . ' ' . __('required');
    } elseif (!$data['slug']) {
        $error = __('tool_slug') . ' ' . __('required');
    } else {
        try {
            $newId = save_web_tool($pdo, $data, $toolId);
            log_admin_action($pdo, $toolId ? 'edit' : 'add', 'web_tool', $newId, $data['name']);
            header("Location: admin.php?page=web-tools&action=edit&id=$newId&msg=" . ($toolId ? 'updated' : 'added'));
            exit;
        } catch (Throwable $e) {
            $error = __('save') . ': ' . $e->getMessage();
        }
    }
}

$tool = $id ? get_web_tool($pdo, $id) : null;
$tools = list_web_tools($pdo, 'all');
?>
<style>
  /* Scoped to .wt-tools-admin so it never leaks into / collides with the
     shared admin.css classes used by the rest of the admin panel shell. */
  .wt-tools-admin * { box-sizing: border-box; }
  .wt-tools-admin { font-family: Tahoma, Arial, sans-serif; }
  .wt-tools-admin .wt-topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 10px; }
  .wt-tools-admin .wt-topbar h1 { font-size: 22px; color: var(--text, #333); margin: 0; }
  .wt-tools-admin .wt-btn { display: inline-block; padding: 10px 20px; background: #007bff; color: #fff; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 14px; }
  .wt-tools-admin .wt-btn:hover { background: #0056b3; }
  .wt-tools-admin .wt-btn-danger { background: #dc3545; }
  .wt-tools-admin .wt-btn-danger:hover { background: #c82333; }
  .wt-tools-admin .wt-btn-success { background: #28a745; }
  .wt-tools-admin .wt-btn-success:hover { background: #218838; }
  .wt-tools-admin .wt-alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
  .wt-tools-admin .wt-alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
  .wt-tools-admin .wt-alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
  .wt-tools-admin .form-group { margin-bottom: 15px; }
  .wt-tools-admin .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
  .wt-tools-admin .form-group input[type="text"],
  .wt-tools-admin .form-group input[type="email"],
  .wt-tools-admin .form-group textarea,
  .wt-tools-admin .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; font-size: 14px; background:#fff; color:#111; }
  .wt-tools-admin .form-group textarea { min-height: 150px; resize: vertical; }
  .wt-tools-admin .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
  .wt-tools-admin .form-full { grid-column: 1 / -1; }
  .wt-tools-admin .tools-table { width: 100%; border-collapse: collapse; background: #fff; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,.1); border-radius: 5px; overflow: hidden; }
  .wt-tools-admin .tools-table th { background: #f8f9fa; padding: 12px; text-align: right; font-weight: bold; color: #333; border-bottom: 2px solid #dee2e6; }
  .wt-tools-admin .tools-table td { padding: 12px; border-bottom: 1px solid #dee2e6; color: #333; }
  .wt-tools-admin .tools-table tr:hover { background: #f9f9f9; }
  .wt-tools-admin .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
  .wt-tools-admin .status-published { background: #d4edda; color: #155724; }
  .wt-tools-admin .status-draft { background: #fff3cd; color: #856404; }
  .wt-tools-admin .edit-form { background: #fff; padding: 30px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,.1); }
  .wt-tools-admin .form-actions { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; }
  @media (max-width: 700px) {
    .wt-tools-admin .form-grid { grid-template-columns: 1fr; }
    .wt-tools-admin .tools-table, .wt-tools-admin .tools-table thead { display: block; }
    .wt-tools-admin .tools-table tbody { display: block; }
    .wt-tools-admin .tools-table tr { display: block; border-bottom: 2px solid #dee2e6; padding: 8px 0; }
    .wt-tools-admin .tools-table thead tr { display: none; }
    .wt-tools-admin .tools-table td { display: flex; justify-content: space-between; gap: 10px; border-bottom: none; padding: 6px 12px; }
  }
</style>

<div class="wt-tools-admin">
  <div class="wt-topbar">
    <h1><?= __('manage_tools') ?></h1>
    <?php if ($action === 'list'): ?>
    <a href="?page=web-tools&action=add" class="wt-btn wt-btn-success">+ <?= __('add_tool_new') ?></a>
    <?php else: ?>
    <a href="?page=web-tools" class="wt-btn">← <?= __('back') ?></a>
    <?php endif; ?>
  </div>

  <?php if ($msg === 'added'): ?>
  <div class="wt-alert wt-alert-success">✓ <?= __('added_success') ?></div>
  <?php elseif ($msg === 'updated'): ?>
  <div class="wt-alert wt-alert-success">✓ <?= __('updated_success') ?></div>
  <?php elseif ($msg === 'deleted'): ?>
  <div class="wt-alert wt-alert-success">✓ <?= __('deleted_success') ?></div>
  <?php elseif ($error): ?>
  <div class="wt-alert wt-alert-danger">✗ <?= h($error) ?></div>
  <?php endif; ?>

  <?php if ($action === 'list'): ?>
    <!-- ── قائمة الأدوات ── -->
    <?php if (empty($tools)): ?>
    <p style="text-align: center; color: #666; padding: 40px 20px;"><?= __('no_tools') ?></p>
    <?php else: ?>
    <table class="tools-table">
      <thead>
        <tr>
          <th><?= __('name') ?></th>
          <th><?= __('slug') ?></th>
          <th><?= __('status') ?></th>
          <th><?= __('views') ?></th>
          <th><?= __('date') ?></th>
          <th><?= __('action') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tools as $t): ?>
        <tr>
          <td><?= h($t['name']) ?></td>
          <td><code><?= h($t['slug']) ?></code></td>
          <td><span class="status-badge status-<?= $t['status'] ?>"><?= $t['status'] === 'published' ? __('tool_published') : __('tool_draft') ?></span></td>
          <td><?= number_format($t['views']) ?></td>
          <td><?= date('d M Y', strtotime($t['created_at'])) ?></td>
          <td>
            <a href="?page=web-tools&action=edit&id=<?= $t['id'] ?>" class="wt-btn" style="padding: 6px 12px; font-size: 12px;"><?= __('edit') ?></a>
            <a href="?page=web-tools&action=delete&id=<?= $t['id'] ?>&t=<?= csrf_token() ?>" class="wt-btn wt-btn-danger" style="padding: 6px 12px; font-size: 12px;" onclick="return confirm('<?= __('tool_delete_confirm') ?>')"><?= __('delete') ?></a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>

  <?php elseif ($action === 'add' || $action === 'edit'): ?>
    <!-- ── نموذج الأداة ── -->
    <form method="post" class="edit-form">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="id" value="<?= $tool['id'] ?? 0 ?>">

      <div class="form-grid">
        <div class="form-group">
          <label><?= __('tool_name') ?> *</label>
          <input type="text" name="name" value="<?= h($tool['name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label><?= __('tool_slug') ?> *</label>
          <input type="text" name="slug" value="<?= h($tool['slug'] ?? '') ?>" placeholder="currency-converter" required>
        </div>

        <div class="form-group">
          <label><?= __('description') ?> (SEO)</label>
          <input type="text" name="seo_title" value="<?= h($tool['seo_title'] ?? '') ?>" placeholder="">
        </div>

        <div class="form-group">
          <label><?= __('tool_description') ?> (160 chars)</label>
          <input type="text" name="meta_description" value="<?= h($tool['meta_description'] ?? '') ?>" maxlength="160" placeholder="">
        </div>

        <div class="form-group">
          <label><?= __('status') ?></label>
          <select name="status">
            <option value="draft" <?= ($tool['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>><?= __('tool_draft') ?></option>
            <option value="published" <?= ($tool['status'] ?? 'draft') === 'published' ? 'selected' : '' ?>><?= __('tool_published') ?></option>
          </select>
        </div>

        <div class="form-group">
          <label><?= __('tool_features') ?> (Keywords)</label>
          <input type="text" name="meta_tags" value="<?= h($tool['meta_tags'] ?? '') ?>" placeholder="">
        </div>

        <div class="form-group form-full">
          <label><?= __('tool_description') ?> (500 chars)</label>
          <textarea name="short_description" maxlength="500" placeholder=""><?= h($tool['short_description'] ?? '') ?></textarea>
        </div>

        <div class="form-group form-full">
          <label><?= __('tool_long_desc') ?> (1000+ chars)</label>
          <textarea name="long_description" placeholder=""><?= h($tool['long_description'] ?? '') ?></textarea>
        </div>

        <div class="form-group form-full">
          <label>الشروحات والأمثلة (3-5000 حرف - مهم جداً للفهرسة)</label>
          <textarea name="tutorials" placeholder="شرح مفصل عن كيفية استخدام الأداة مع أمثلة عملية..."><?= h($tool['tutorials'] ?? '') ?></textarea>
          <small style="color: #666; display: block; margin-top: 5px;">هذا الحقل مهم جداً للفهرسة في Google وقبول AdSense</small>
        </div>

        <div class="form-group form-full">
          <label>المميزات (سطر واحد لكل مميزة)</label>
          <textarea name="features" placeholder="مميزة 1&#10;مميزة 2&#10;مميزة 3"><?php
            if ($tool && isset($tool['features'])) {
                $feats = json_decode($tool['features'], true);
                echo h(implode("\n", $feats ?? []));
            }
          ?></textarea>
        </div>

        <div class="form-group form-full">
          <label>الإيجابيات (سطر واحد لكل نقطة)</label>
          <textarea name="pros" placeholder="إيجابية 1&#10;إيجابية 2&#10;إيجابية 3"><?php
            if ($tool && isset($tool['pros'])) {
                $pros = json_decode($tool['pros'], true);
                echo h(implode("\n", $pros ?? []));
            }
          ?></textarea>
        </div>

        <div class="form-group form-full">
          <label>السلبيات (سطر واحد لكل نقطة)</label>
          <textarea name="cons" placeholder="سلبية 1&#10;سلبية 2"><?php
            if ($tool && isset($tool['cons'])) {
                $cons = json_decode($tool['cons'], true);
                echo h(implode("\n", $cons ?? []));
            }
          ?></textarea>
        </div>

        <div class="form-group form-full">
          <label>ما الجديد في هذه الأداة (سجل التحديثات)</label>
          <textarea name="whats_new" placeholder="وصف آخر التحديثات والتحسينات على الأداة..."><?= h($tool['whats_new'] ?? '') ?></textarea>
        </div>

        <div class="form-group form-full">
          <label>كيف بدأت هذه الأداة (قصة النشأة)</label>
          <textarea name="how_it_started" placeholder="احكِ قصة بداية هذه الأداة ولماذا تم إنشاؤها..."><?= h($tool['how_it_started'] ?? '') ?></textarea>
        </div>

        <div class="form-group form-full">
          <label>الأسئلة الشائعة (FAQ)</label>
          <textarea name="faq" rows="10" placeholder="Q: ما هو السؤال الأول؟&#10;A: الإجابة هنا، يمكن أن تمتد لعدة أسطر.&#10;&#10;Q: السؤال الثاني؟&#10;A: الإجابة الثانية..."><?php
            if ($tool && !empty($tool['faq'])) {
                $faqPairs = json_decode($tool['faq'], true) ?: [];
                $lines = [];
                foreach ($faqPairs as $pair) {
                    if (!empty($pair['q']) && !empty($pair['a'])) {
                        $lines[] = "Q: " . $pair['q'] . "\nA: " . $pair['a'];
                    }
                }
                echo h(implode("\n\n", $lines));
            }
          ?></textarea>
          <small style="color: #666; display: block; margin-top: 5px;">اكتب كل سؤال بصيغة "Q: السؤال" وتحته "A: الإجابة"، واترك سطراً فارغاً بين كل زوج سؤال/إجابة.</small>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="wt-btn wt-btn-success">
          <?= $tool ? '✓ حفظ التعديلات' : '+ إضافة الأداة' ?>
        </button>
        <button type="button" class="wt-btn" onclick="openLivePreview()" style="background:#6366f1">
          👁️ معاينة حية
        </button>
        <a href="?page=web-tools" class="wt-btn">← إلغاء</a>
      </div>
    </form>

    <!-- Live Preview Modal -->
    <div id="preview-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);overflow-y:auto">
      <div style="max-width:1000px;margin:20px auto;background:white;border-radius:12px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.3)">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:20px;background:#f8f9fa;border-bottom:1px solid #e0e0e0">
          <h2 style="margin:0;font-size:18px">👁️ معاينة حية للأداة</h2>
          <button onclick="document.getElementById('preview-modal').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer;color:#666">×</button>
        </div>
        <div id="preview-content" style="padding:30px;max-height:80vh;overflow-y:auto">
          <div style="text-align:center;color:#999">جاري التحميل...</div>
        </div>
      </div>
    </div>

    <script>
    function openLivePreview() {
      const name = document.querySelector('[name="name"]')?.value || '';
      const slug = document.querySelector('[name="slug"]')?.value || '';
      const seoTitle = document.querySelector('[name="seo_title"]')?.value || '';
      const metaDesc = document.querySelector('[name="meta_description"]')?.value || '';
      const shortDesc = document.querySelector('[name="short_description"]')?.value || '';
      const longDesc = document.querySelector('[name="long_description"]')?.value || '';
      const tutorials = document.querySelector('[name="tutorials"]')?.value || '';
      const features = document.querySelector('[name="features"]')?.value || '';
      const pros = document.querySelector('[name="pros"]')?.value || '';
      const cons = document.querySelector('[name="cons"]')?.value || '';

      if (!name) { alert('أدخل اسم الأداة أولاً'); return; }

      const previewHtml = `
        <style>
          .preview-header { max-width: 100%; }
          .preview-section { margin: 24px 0; padding: 16px; background: #f8f9fa; border-radius: 8px; }
          .preview-section h3 { margin: 0 0 12px; font-size: 18px; color: #2563eb; font-weight: 700; }
          .preview-features { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; }
          .feature-item { padding: 10px; background: rgba(37,99,235,.05); border-radius: 6px; font-size: 14px; }
          .preview-content { line-height: 1.8; color: #333; font-size: 15px; }
          .pros-cons { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
          .pro-list, .con-list { padding: 12px; border-radius: 6px; list-style: none; margin: 0; padding-left: 20px; }
          .pro-list { background: rgba(34,197,94,.08); }
          .con-list { background: rgba(239,68,68,.08); }
          .pro-list li { margin: 6px 0; }
          .con-list li { margin: 6px 0; }
          .pro-list li:before { content: '✓ '; color: #22c55e; }
          .con-list li:before { content: '✗ '; color: #ef4444; }
          .preview-seo { margin-top: 20px; padding: 16px; background: #f0f9ff; border-left: 4px solid #0066cc; border-radius: 6px; font-size: 13px; }
          .preview-seo strong { color: #0066cc; }
        </style>
        <div class="preview-header">
          <h1 style="font-size:32px;margin:0 0 10px;font-weight:800">${escapeHtml(name)}</h1>
          <p style="color:#999;font-size:14px;margin:0 0 20px">معاينة حية للأداة</p>
          ${shortDesc ? '<div class="preview-section" style="background:linear-gradient(135deg,rgba(37,99,235,.08),rgba(99,102,241,.08));border-left:4px solid #2563eb"><p style="margin:0;font-size:16px">' + escapeHtml(shortDesc) + '</p></div>' : '<div style="color:#999">لم تُدخل وصفاً قصيراً</div>'}
        </div>

        ${longDesc ? '<div class="preview-section"><h3>📝 نظرة عامة</h3><div class="preview-content">' + escapeHtml(longDesc).replace(/\n/g, '<br>') + '</div></div>' : ''}

        ${features ? '<div class="preview-section"><h3>✨ المميزات الرئيسية</h3><div class="preview-features">' +
          escapeHtml(features).split('\n').filter(f => f.trim()).map(f => '<div class="feature-item">✔️ ' + f.trim() + '</div>').join('') +
          '</div></div>' : ''}

        ${(pros || cons) ? '<div class="preview-section"><h3>⚖️ الإيجابيات والسلبيات</h3><div class="pros-cons">' +
          (pros ? '<div><h4 style="margin-top:0;color:#22c55e">✅ الإيجابيات</h4><ul class="pro-list">' +
            escapeHtml(pros).split('\n').filter(p => p.trim()).map(p => '<li>' + p.trim() + '</li>').join('') +
            '</ul></div>' : '') +
          (cons ? '<div><h4 style="margin-top:0;color:#ef4444">❌ السلبيات</h4><ul class="con-list">' +
            escapeHtml(cons).split('\n').filter(c => c.trim()).map(c => '<li>' + c.trim() + '</li>').join('') +
            '</ul></div>' : '') +
          '</div></div>' : ''}

        ${tutorials ? '<div class="preview-section"><h3>🎓 كيفية الاستخدام والشروحات</h3><div class="preview-content">' + escapeHtml(tutorials).replace(/\n/g, '<br>') + '</div></div>' : ''}

        <div class="preview-seo">
          <strong>📊 معلومات SEO والمشاركة:</strong><br>
          <strong>العنوان (Title):</strong> ${escapeHtml(seoTitle || name)}<br>
          <strong>الوصف (Description):</strong> ${escapeHtml(metaDesc || shortDesc || '...')}<br>
          <strong>الرابط (URL):</strong> /tools?slug=${escapeHtml(slug || 'tool-name')}
        </div>
      `;

      document.getElementById('preview-content').innerHTML = previewHtml;
      document.getElementById('preview-modal').style.display = 'flex';
      document.getElementById('preview-modal').style.alignItems = 'flex-start';
      document.getElementById('preview-modal').style.justifyContent = 'center';
      document.body.style.overflow = 'hidden';
    }

    function escapeHtml(text) {
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      };
      return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    // إغلاق المعاينة عند الضغط خارجها
    document.getElementById('preview-modal')?.addEventListener('click', function(e) {
      if (e.target === this) {
        this.style.display = 'none';
        document.body.style.overflow = 'auto';
      }
    });
    </script>

  <?php endif; ?>
</div>
