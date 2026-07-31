<?php
/**
 * Web Tools Management Admin Panel
 * يتم تضمينها من admin.php عند ?page=web-tools
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

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
        $error = 'خطأ في حذف الأداة: ' . $e->getMessage();
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

    if (!$data['name']) {
        $error = 'اسم الأداة مطلوب';
    } elseif (!$data['slug']) {
        $error = 'الرابط (slug) مطلوب';
    } else {
        try {
            $newId = save_web_tool($pdo, $data, $toolId);
            log_admin_action($pdo, $toolId ? 'edit' : 'add', 'web_tool', $newId, $data['name']);
            header("Location: admin.php?page=web-tools&action=edit&id=$newId&msg=" . ($toolId ? 'updated' : 'added'));
            exit;
        } catch (Throwable $e) {
            $error = 'خطأ في الحفظ: ' . $e->getMessage();
        }
    }
}

$tool = $id ? get_web_tool($pdo, $id) : null;
$tools = list_web_tools($pdo, 'all');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>إدارة أدوات الويب</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Tahoma, Arial, sans-serif; background: #f5f5f5; padding: 20px; }
    .container { max-width: 1200px; margin: 0 auto; }
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    .header h1 { font-size: 28px; color: #333; }
    .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 14px; }
    .btn:hover { background: #0056b3; }
    .btn-danger { background: #dc3545; }
    .btn-danger:hover { background: #c82333; }
    .btn-success { background: #28a745; }
    .btn-success:hover { background: #218838; }
    .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group textarea,
    .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; font-size: 14px; }
    .form-group textarea { min-height: 150px; resize: vertical; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-full { grid-column: 1 / -1; }
    .tools-table { width: 100%; border-collapse: collapse; background: white; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,.1); border-radius: 5px; overflow: hidden; }
    .tools-table th { background: #f8f9fa; padding: 12px; text-align: right; font-weight: bold; color: #333; border-bottom: 2px solid #dee2e6; }
    .tools-table td { padding: 12px; border-bottom: 1px solid #dee2e6; }
    .tools-table tr:hover { background: #f9f9f9; }
    .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
    .status-published { background: #d4edda; color: #155724; }
    .status-draft { background: #fff3cd; color: #856404; }
    .edit-form { background: white; padding: 30px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,.1); }
    .form-actions { display: flex; gap: 10px; margin-top: 20px; }
    .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #ddd; }
    .tab { padding: 10px 20px; cursor: pointer; border: none; background: none; font-size: 16px; color: #666; border-bottom: 3px solid transparent; }
    .tab.active { color: #007bff; border-bottom-color: #007bff; }
  </style>
</head>
<body>

<div class="container">
  <div class="header">
    <h1>إدارة أدوات الويب</h1>
    <?php if ($action === 'list'): ?>
    <a href="?page=web-tools&action=add" class="btn btn-success">+ أضف أداة جديدة</a>
    <?php else: ?>
    <a href="?page=web-tools" class="btn">← العودة للقائمة</a>
    <?php endif; ?>
  </div>

  <?php if ($msg === 'added'): ?>
  <div class="alert alert-success">✓ تم إضافة الأداة بنجاح</div>
  <?php elseif ($msg === 'updated'): ?>
  <div class="alert alert-success">✓ تم تحديث الأداة بنجاح</div>
  <?php elseif ($msg === 'deleted'): ?>
  <div class="alert alert-success">✓ تم حذف الأداة بنجاح</div>
  <?php elseif ($error): ?>
  <div class="alert alert-danger">✗ خطأ: <?= h($error) ?></div>
  <?php endif; ?>

  <?php if ($action === 'list'): ?>
    <!-- ── قائمة الأدوات ── -->
    <table class="tools-table">
      <thead>
        <tr>
          <th>الاسم</th>
          <th>الرابط</th>
          <th>الحالة</th>
          <th>المشاهدات</th>
          <th>التاريخ</th>
          <th>الإجراءات</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tools as $t): ?>
        <tr>
          <td><?= h($t['name']) ?></td>
          <td><code><?= h($t['slug']) ?></code></td>
          <td><span class="status-badge status-<?= $t['status'] ?>"><?= $t['status'] === 'published' ? 'منشور' : 'مسودة' ?></span></td>
          <td><?= number_format($t['views']) ?></td>
          <td><?= date('d M Y', strtotime($t['created_at'])) ?></td>
          <td>
            <a href="?page=web-tools&action=edit&id=<?= $t['id'] ?>" class="btn" style="padding: 6px 12px; font-size: 12px;">تعديل</a>
            <a href="?page=web-tools&action=delete&id=<?= $t['id'] ?>&t=<?= csrf_token() ?>" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;" onclick="return confirm('هل تريد حذف هذه الأداة؟')">حذف</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

  <?php elseif ($action === 'add' || $action === 'edit'): ?>
    <!-- ── نموذج الأداة ── -->
    <form method="post" class="edit-form">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="id" value="<?= $tool['id'] ?? 0 ?>">

      <div class="form-grid">
        <div class="form-group">
          <label>اسم الأداة *</label>
          <input type="text" name="name" value="<?= h($tool['name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label>الرابط (slug) *</label>
          <input type="text" name="slug" value="<?= h($tool['slug'] ?? '') ?>" placeholder="currency-converter" required>
        </div>

        <div class="form-group">
          <label>عنوان SEO</label>
          <input type="text" name="seo_title" value="<?= h($tool['seo_title'] ?? '') ?>" placeholder="محول العملات - تحويل العملات الحي">
        </div>

        <div class="form-group">
          <label>وصف Meta (160 حرف)</label>
          <input type="text" name="meta_description" value="<?= h($tool['meta_description'] ?? '') ?>" maxlength="160" placeholder="محول العملات مع أسعار صرف حية">
        </div>

        <div class="form-group">
          <label>الحالة</label>
          <select name="status">
            <option value="draft" <?= ($tool['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>مسودة</option>
            <option value="published" <?= ($tool['status'] ?? 'draft') === 'published' ? 'selected' : '' ?>>منشورة</option>
          </select>
        </div>

        <div class="form-group">
          <label>الكلمات المفتاحية</label>
          <input type="text" name="meta_tags" value="<?= h($tool['meta_tags'] ?? '') ?>" placeholder="محول عملات, صرف, دولار">
        </div>

        <div class="form-group form-full">
          <label>الوصف القصير (500 حرف)</label>
          <textarea name="short_description" maxlength="500" placeholder="وصف قصير عن الأداة..."><?= h($tool['short_description'] ?? '') ?></textarea>
        </div>

        <div class="form-group form-full">
          <label>الوصف الطويل (SEO - 1000+ حرف)</label>
          <textarea name="long_description" placeholder="محتوى تفصيلي شامل عن الأداة..."><?= h($tool['long_description'] ?? '') ?></textarea>
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
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-success">
          <?= $tool ? '✓ حفظ التعديلات' : '+ إضافة الأداة' ?>
        </button>
        <a href="?page=web-tools" class="btn">← إلغاء</a>
      </div>
    </form>

  <?php endif; ?>
</div>

</body>
</html>
