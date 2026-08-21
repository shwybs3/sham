<?php
require_once __DIR__ . '/../config.php';
requireAdminLogin();

$pageTitle = 'إدارة المقالات';
$activeNav = 'articles';

$ok = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfCheck()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $aid = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM articles WHERE id=?")->execute([$aid]);
        $ok = 'تم حذف المقال.';
    } elseif ($action === 'toggle_status') {
        $aid = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE articles SET status = IF(status='published','draft','published') WHERE id=?")->execute([$aid]);
        $ok = 'تم تغيير حالة المقال.';
    }
}

$articles = $pdo->query("SELECT id, title, slug, status, created_at, updated_at FROM articles ORDER BY created_at DESC")->fetchAll();

require __DIR__ . '/includes/admin-header.php';
?>
<style>
  .art-status-pub{background:rgba(34,197,142,.12); color:var(--brand);}
  .art-status-dft{background:rgba(240,180,41,.12); color:#c9983a;}
</style>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
  <div></div>
  <a href="article-edit.php" class="btn btn-primary"><?= icon('plus-circle', 16) ?> مقال جديد</a>
</div>

<?php if ($ok):   ?><div class="notice ok"><?= icon('check-circle', 16) ?> <?= h($ok) ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice err"><?= icon('alert', 16) ?> <?= h($error) ?></div><?php endif; ?>

<div class="panel">
  <h2><?= icon('receipt', 18) ?> المقالات (<?= count($articles) ?>)</h2>
  <?php if (!$articles): ?>
    <div class="cc-empty-state"><?= icon('receipt', 32) ?><p>لا توجد مقالات بعد.<br><a href="article-edit.php">أضف أول مقال</a></p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>العنوان</th>
          <th>Slug</th>
          <th>الحالة</th>
          <th>التاريخ</th>
          <th>إجراءات</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($articles as $a): ?>
        <tr>
          <td style="max-width:280px;">
            <strong style="font-size:14px;"><?= h($a['title']) ?></strong>
          </td>
          <td style="font-family:var(--mono); font-size:12px; color:var(--ink-dim);"><?= h($a['slug']) ?></td>
          <td>
            <span class="badge <?= $a['status'] === 'published' ? 'art-status-pub' : 'art-status-dft' ?>">
              <?= $a['status'] === 'published' ? 'منشور' : 'مسودة' ?>
            </span>
          </td>
          <td style="font-size:12.5px; color:var(--ink-dim); white-space:nowrap;">
            <?= date('Y-m-d', strtotime($a['created_at'])) ?>
          </td>
          <td>
            <div class="row-actions">
              <a href="article-edit.php?id=<?= (int)$a['id'] ?>" class="icon-btn" title="تعديل"><?= icon('edit', 14) ?></a>
              <?php if ($a['status'] === 'published'): ?>
              <a href="../article.php?slug=<?= urlencode($a['slug']) ?>" class="icon-btn" title="عرض" target="_blank"><?= icon('external', 14) ?></a>
              <?php endif; ?>
              <form method="post" style="display:inline;">
                <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                <button class="icon-btn" title="<?= $a['status'] === 'published' ? 'تحويل لمسودة' : 'نشر' ?>"><?= icon($a['status'] === 'published' ? 'alert' : 'check-circle', 14) ?></button>
              </form>
              <form method="post" style="display:inline;" onsubmit="return confirm('حذف هذا المقال نهائياً؟');">
                <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                <button class="icon-btn danger" title="حذف"><?= icon('trash', 14) ?></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
