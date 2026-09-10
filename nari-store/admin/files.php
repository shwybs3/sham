<?php
/**
 * مدير ملفات آمن داخل لوحة التحكم — البديل العملي والآمن لطلب "شل/Power Shell".
 *
 * لماذا لا يوجد هنا تنفيذ أوامر نظام (Shell/Terminal) حقيقي؟
 * لأن أي واجهة ويب تنفّذ أوامر نظام تشغيل مباشرة تتحول فوراً إلى ثغرة تنفيذ
 * أوامر عن بُعد (RCE): لو تسرّبت كلمة مرور لوحة التحكم ولو لمرة واحدة، يصبح
 * الخادم بالكامل مخترقاً لا الموقع فقط، وأغلب الاستضافات المجانية (ومنها
 * Infinity Free) تُعطّل أصلاً دوال exec/shell_exec/system وتحظر مثل هذه
 * السكربتات فور اكتشافها. لذلك بُني بدلاً منه مدير ملفات كامل الصلاحيات
 * (تصفح، رفع، إنشاء، تحرير نصوص، إعادة تسمية، حذف) يغطي نفس الحاجة العملية
 * دون فتح هذا الباب الخطير.
 */

define('NARI_ADMIN', true);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/nav.php';
nari_require_login();

$ROOT = realpath(__DIR__ . '/..');
$csrf = nari_csrf_token();

function v($val, $default = '') {
    return htmlspecialchars((string)($val ?? $default), ENT_QUOTES, 'UTF-8');
}

/** يحوّل مساراً نسبياً (قد يحتوي .. أو \) إلى مسار نظيف بدون الخروج من الجذر أبداً. */
function nari_clean_rel(string $rel): string {
    $rel = str_replace('\\', '/', $rel);
    $parts = [];
    foreach (explode('/', $rel) as $seg) {
        if ($seg === '' || $seg === '.') continue;
        if ($seg === '..') { array_pop($parts); continue; }
        $parts[] = $seg;
    }
    return implode('/', $parts);
}

/** يبني مساراً كاملاً من الجذر + مسار نسبي نظيف، ويتحقق أن الناتج لا يزال داخل الجذر. */
function nari_resolve(string $root, string $rel): ?string {
    $clean = nari_clean_rel($rel);
    $full = $clean === '' ? $root : $root . '/' . $clean;
    // إن كان المسار موجوداً فعلاً، تحقق عبر realpath (الطريقة الأدق ضد أي التفاف على الروابط الرمزية)
    $real = realpath($full);
    if ($real !== false) {
        if ($real === $root || strpos($real, $root . DIRECTORY_SEPARATOR) === 0) {
            return $real;
        }
        return null;
    }
    // مسار غير موجود بعد (رفع/إنشاء ملف جديد) — تحقق من المجلد الأب فقط
    $parentReal = realpath(dirname($full));
    if ($parentReal === false) return null;
    if ($parentReal !== $root && strpos($parentReal, $root . DIRECTORY_SEPARATOR) !== 0) return null;
    return $full;
}

function nari_safe_basename(string $name): string {
    $name = trim(str_replace(['/', '\\'], '', $name));
    $name = preg_replace('/[\x00-\x1F]/', '', $name);
    return $name;
}

$EDITABLE_EXT = ['html','htm','css','js','json','txt','md','xml','svg','php','ini','conf','csv','yml','yaml','webmanifest'];
function nari_is_editable(string $path): bool {
    global $EDITABLE_EXT;
    $base = basename($path);
    if ($base === '.htaccess' || $base === '.htpasswd') return true;
    $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
    return in_array($ext, $EDITABLE_EXT, true);
}

function nari_rrmdir(string $dir): bool {
    $items = @scandir($dir);
    if ($items === false) return false;
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path) && !is_link($path)) {
            nari_rrmdir($path);
        } else {
            @unlink($path);
        }
    }
    return @rmdir($dir);
}

function nari_fmt_size(int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    if ($bytes < 1073741824) return round($bytes / 1048576, 1) . ' MB';
    return round($bytes / 1073741824, 2) . ' GB';
}

$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';
$curDir = nari_clean_rel($_GET['dir'] ?? '');

/* ------------------------------------------------------------ معالجة الإجراءات (POST) */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!nari_csrf_check($_POST['csrf'] ?? '')) {
        header('Location: files.php?dir=' . rawurlencode($curDir) . '&err=csrf');
        exit;
    }
    $action = $_POST['action'] ?? '';
    $returnDir = nari_clean_rel($_POST['dir'] ?? $curDir);

    if ($action === 'mkdir') {
        $name = nari_safe_basename($_POST['name'] ?? '');
        $target = nari_resolve($ROOT, $returnDir . '/' . $name);
        if ($name === '' || $target === null) {
            header('Location: files.php?dir=' . rawurlencode($returnDir) . '&err=name');
        } elseif (file_exists($target)) {
            header('Location: files.php?dir=' . rawurlencode($returnDir) . '&err=exists');
        } else {
            mkdir($target, 0755);
            header('Location: files.php?dir=' . rawurlencode($returnDir) . '&msg=mkdir');
        }
        exit;
    }

    if ($action === 'newfile') {
        $name = nari_safe_basename($_POST['name'] ?? '');
        $target = nari_resolve($ROOT, $returnDir . '/' . $name);
        if ($name === '' || $target === null || !nari_is_editable($target)) {
            header('Location: files.php?dir=' . rawurlencode($returnDir) . '&err=name');
        } elseif (file_exists($target)) {
            header('Location: files.php?dir=' . rawurlencode($returnDir) . '&err=exists');
        } else {
            file_put_contents($target, '');
            header('Location: files.php?edit=' . rawurlencode(substr($target, strlen($ROOT) + 1)));
        }
        exit;
    }

    if ($action === 'upload' && !empty($_FILES['upload_files'])) {
        $dirFull = nari_resolve($ROOT, $returnDir);
        $files = $_FILES['upload_files'];
        $count = is_array($files['name']) ? count($files['name']) : 0;
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            $name = nari_safe_basename($files['name'][$i]);
            if ($name === '') continue;
            $target = nari_resolve($ROOT, $returnDir . '/' . $name);
            if ($target === null) continue;
            move_uploaded_file($files['tmp_name'][$i], $target);
        }
        header('Location: files.php?dir=' . rawurlencode($returnDir) . '&msg=upload');
        exit;
    }

    if ($action === 'rename') {
        $old = nari_resolve($ROOT, $_POST['old'] ?? '');
        $newName = nari_safe_basename($_POST['new_name'] ?? '');
        if ($old === null || $old === $ROOT || $newName === '' || !file_exists($old)) {
            header('Location: files.php?dir=' . rawurlencode($returnDir) . '&err=name');
            exit;
        }
        $newTarget = dirname($old) . '/' . $newName;
        if (file_exists($newTarget)) {
            header('Location: files.php?dir=' . rawurlencode($returnDir) . '&err=exists');
            exit;
        }
        rename($old, $newTarget);
        header('Location: files.php?dir=' . rawurlencode($returnDir) . '&msg=rename');
        exit;
    }

    if ($action === 'delete') {
        $target = nari_resolve($ROOT, $_POST['target'] ?? '');
        if ($target !== null && $target !== $ROOT && file_exists($target)) {
            if (is_dir($target) && !is_link($target)) {
                nari_rrmdir($target);
            } else {
                unlink($target);
            }
        }
        header('Location: files.php?dir=' . rawurlencode($returnDir) . '&msg=delete');
        exit;
    }

    if ($action === 'save_file') {
        $target = nari_resolve($ROOT, $_POST['target'] ?? '');
        if ($target === null || !nari_is_editable($target)) {
            header('Location: files.php?dir=' . rawurlencode($returnDir) . '&err=name');
            exit;
        }
        file_put_contents($target, (string)($_POST['content'] ?? ''));
        header('Location: files.php?edit=' . rawurlencode(substr($target, strlen($ROOT) + 1)) . '&msg=saved');
        exit;
    }

    header('Location: files.php');
    exit;
}

/* ------------------------------------------------------------ وضع التحرير */
$editTarget = null;
$editContent = null;
$editRel = '';
if (isset($_GET['edit'])) {
    $editTarget = nari_resolve($ROOT, $_GET['edit']);
    if ($editTarget !== null && is_file($editTarget) && nari_is_editable($editTarget)) {
        if (filesize($editTarget) > 3 * 1024 * 1024) {
            $err = 'toolarge';
            $editTarget = null;
        } else {
            $editContent = file_get_contents($editTarget);
            $editRel = substr($editTarget, strlen($ROOT) + 1);
        }
    } else {
        $editTarget = null;
        $err = $err ?: 'notfound';
    }
}

/* ------------------------------------------------------------ وضع التصفح */
$browseFull = null;
$entries = [];
if ($editTarget === null) {
    $browseFull = nari_resolve($ROOT, $curDir);
    if ($browseFull === null || !is_dir($browseFull)) {
        $browseFull = $ROOT;
        $curDir = '';
    }
    $items = @scandir($browseFull) ?: [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $browseFull . '/' . $item;
        $entries[] = [
            'name' => $item,
            'is_dir' => is_dir($full),
            'size' => is_file($full) ? filesize($full) : 0,
            'mtime' => filemtime($full),
            'rel' => ($curDir === '' ? '' : $curDir . '/') . $item,
        ];
    }
    usort($entries, function ($a, $b) {
        if ($a['is_dir'] !== $b['is_dir']) return $a['is_dir'] ? -1 : 1;
        return strcasecmp($a['name'], $b['name']);
    });
}

$MESSAGES = [
    'mkdir' => 'تم إنشاء المجلد بنجاح.',
    'upload' => 'تم رفع الملفات بنجاح.',
    'rename' => 'تمت إعادة التسمية بنجاح.',
    'delete' => 'تم الحذف بنجاح.',
    'saved' => 'تم حفظ الملف بنجاح.',
];
$ERRORS = [
    'csrf' => 'انتهت صلاحية الجلسة، أعد المحاولة.',
    'name' => 'اسم غير صالح أو مسار غير مسموح.',
    'exists' => 'يوجد ملف/مجلد بنفس الاسم مسبقاً.',
    'notfound' => 'الملف غير موجود أو غير قابل للتحرير من هنا.',
    'toolarge' => 'الملف أكبر من 3 ميجابايت — حمّله وعدّله محلياً ثم أعد رفعه.',
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>مدير الملفات | لوحة تحكم ناري ستور</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="../assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="admin.css">
</head>
<body>

<div class="top-bar">
  <div class="wrap">
    <h1><svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="none" aria-hidden="true"><path d="M3 6a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6z"/></svg> مدير <span>الملفات</span></h1>
    <div style="display:flex; align-items:center; gap:14px;">
      <a href="../index.html" target="_blank" class="btn ghost small">عرض الموقع ↗</a>
      <a href="logout.php" class="btn ghost small">تسجيل الخروج</a>
    </div>
  </div>
</div>
<?php nari_admin_nav('files'); ?>

<div class="wrap">

  <?php if ($msg && isset($MESSAGES[$msg])): ?>
    <div class="success-box"><?= v($MESSAGES[$msg]) ?></div>
  <?php endif; ?>
  <?php if ($err && isset($ERRORS[$err])): ?>
    <div class="error-box"><?= v($ERRORS[$err]) ?></div>
  <?php endif; ?>

  <?php if ($editTarget !== null): ?>
    <div class="panel">
      <h2>تحرير: <?= v($editRel) ?></h2>
      <p class="hint">تحذير: أي خطأ في ملفات .php أو .htaccess قد يعطّل الموقع مؤقتاً. تأكد من الشكل الصحيح قبل الحفظ.</p>
      <form method="post" action="files.php">
        <input type="hidden" name="csrf" value="<?= v($csrf) ?>">
        <input type="hidden" name="action" value="save_file">
        <input type="hidden" name="target" value="<?= v($editRel) ?>">
        <input type="hidden" name="dir" value="<?= v(dirname($editRel) === '.' ? '' : dirname($editRel)) ?>">
        <textarea name="content" class="code-editor" spellcheck="false"><?= v($editContent) ?></textarea>
        <div style="display:flex; gap:12px; margin-top:16px;">
          <button type="submit" class="btn" style="width:auto; padding:12px 28px;">حفظ الملف</button>
          <a class="btn ghost small" href="files.php?dir=<?= rawurlencode(dirname($editRel) === '.' ? '' : dirname($editRel)) ?>">إلغاء والعودة للتصفح</a>
        </div>
      </form>
    </div>
  <?php else: ?>

    <div class="breadcrumbs">
      <a href="files.php">📁 الجذر</a>
      <?php
        $acc = '';
        if ($curDir !== ''):
          foreach (explode('/', $curDir) as $seg):
            $acc = $acc === '' ? $seg : $acc . '/' . $seg;
      ?>
        / <a href="files.php?dir=<?= rawurlencode($acc) ?>"><?= v($seg) ?></a>
      <?php endforeach; endif; ?>
    </div>

    <div class="panel">
      <h2>إنشاء ورفع</h2>
      <div class="grid3">
        <form method="post" action="files.php">
          <input type="hidden" name="csrf" value="<?= v($csrf) ?>">
          <input type="hidden" name="action" value="mkdir">
          <input type="hidden" name="dir" value="<?= v($curDir) ?>">
          <div class="field"><label>اسم مجلد جديد</label><input type="text" name="name" required placeholder="uploads-2026"></div>
          <button type="submit" class="btn small">إنشاء مجلد</button>
        </form>
        <form method="post" action="files.php">
          <input type="hidden" name="csrf" value="<?= v($csrf) ?>">
          <input type="hidden" name="action" value="newfile">
          <input type="hidden" name="dir" value="<?= v($curDir) ?>">
          <div class="field"><label>اسم ملف نصي جديد</label><input type="text" name="name" required placeholder="page.html"></div>
          <button type="submit" class="btn small">إنشاء ملف وتحريره</button>
        </form>
        <form method="post" action="files.php" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?= v($csrf) ?>">
          <input type="hidden" name="action" value="upload">
          <input type="hidden" name="dir" value="<?= v($curDir) ?>">
          <div class="field"><label>رفع ملف واحد أو أكثر</label><input type="file" name="upload_files[]" multiple required></div>
          <button type="submit" class="btn small">رفع</button>
        </form>
      </div>
    </div>

    <div class="panel">
      <h2>محتويات المجلد (<?= count($entries) ?>)</h2>
      <?php if (empty($entries)): ?>
        <p class="hint">المجلد فارغ.</p>
      <?php endif; ?>
      <?php foreach ($entries as $e): ?>
        <div class="file-row">
          <div class="fname">
            <?php if ($e['is_dir']): ?>
              📁 <a href="files.php?dir=<?= rawurlencode($e['rel']) ?>"><strong><?= v($e['name']) ?></strong></a>
            <?php elseif (nari_is_editable($browseFull . '/' . $e['name'])): ?>
              📝 <a href="files.php?edit=<?= rawurlencode($e['rel']) ?>"><?= v($e['name']) ?></a>
            <?php else: ?>
              📄 <a href="../<?= implode('/', array_map('rawurlencode', explode('/', $e['rel']))) ?>" target="_blank"><?= v($e['name']) ?></a>
            <?php endif; ?>
          </div>
          <div class="fmeta"><?= $e['is_dir'] ? '' : v(nari_fmt_size($e['size'])) ?> — <?= v(date('Y-m-d H:i', $e['mtime'])) ?></div>
          <div class="file-actions">
            <form method="post" action="files.php" onsubmit="return confirm('تغيير اسم \'<?= v($e['name']) ?>\'؟');">
              <input type="hidden" name="csrf" value="<?= v($csrf) ?>">
              <input type="hidden" name="action" value="rename">
              <input type="hidden" name="old" value="<?= v($e['rel']) ?>">
              <input type="hidden" name="dir" value="<?= v($curDir) ?>">
              <input type="text" name="new_name" value="<?= v($e['name']) ?>" required>
              <button type="submit" class="btn ghost tiny">إعادة تسمية</button>
            </form>
            <form method="post" action="files.php" onsubmit="return confirm('حذف \'<?= v($e['name']) ?>\' نهائياً؟ لا يمكن التراجع.');">
              <input type="hidden" name="csrf" value="<?= v($csrf) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="target" value="<?= v($e['rel']) ?>">
              <input type="hidden" name="dir" value="<?= v($curDir) ?>">
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
