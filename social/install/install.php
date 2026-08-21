<?php
/* One-time installer — DELETE after running */
require_once dirname(__DIR__) . '/config.php';

$pdo = $social_pdo;
$errors = [];
$done   = [];

$sql = file_get_contents(__DIR__ . '/schema.sql');
// Split on statement boundaries and run each
$stmts = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));

foreach ($stmts as $stmt) {
    if (!$stmt || str_starts_with($stmt, '--')) continue;
    try {
        $pdo->exec($stmt);
        $done[] = substr($stmt, 0, 60) . '…';
    } catch (PDOException $e) {
        // Ignore "already exists" errors
        if (!str_contains($e->getMessage(), 'already exists')) {
            $errors[] = $e->getMessage();
        } else {
            $done[] = '[skipped — exists] ' . substr($stmt, 0, 40);
        }
    }
}

// Create directories
$dirs = [
    SOCIAL_UPLOAD_PATH . '/avatars',
    SOCIAL_UPLOAD_PATH . '/posts',
    SOCIAL_UPLOAD_PATH . '/tools',
];
foreach ($dirs as $d) {
    if (!is_dir($d)) { mkdir($d, 0755, true); $done[] = "mkdir: $d"; }
}

// Create .htaccess to protect uploads
$htaccess = SOCIAL_UPLOAD_PATH . '/.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Options -Indexes\n<FilesMatch \"\\.php$\">\nDeny from all\n</FilesMatch>");
    $done[] = 'Created uploads/.htaccess';
}

?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><title>تثبيت Yassota Connect</title>
<style>body{font-family:system-ui,sans-serif;background:#0e1117;color:#e6edf3;padding:30px;max-width:700px;margin:0 auto}
.ok{color:#3fb950}.err{color:#f85149}pre{background:#161b22;padding:14px;border-radius:8px;font-size:.82rem;overflow-x:auto}
h1{margin-bottom:20px}</style></head>
<body>
<h1>🚀 تثبيت Yassota Connect</h1>
<?php if ($errors): ?>
<h2 class="err">❌ أخطاء (<?= count($errors) ?>)</h2>
<pre><?= implode("\n", array_map('htmlspecialchars', $errors)) ?></pre>
<?php else: ?>
<h2 class="ok">✅ تم التثبيت بنجاح!</h2>
<?php endif; ?>
<h3 style="margin-top:20px">العمليات المنفّذة:</h3>
<pre class="ok"><?= implode("\n", array_map('htmlspecialchars', $done)) ?></pre>
<p style="margin-top:20px;color:#f85149">⚠️ <strong>احذف هذا الملف فوراً!</strong> <code>rm install/install.php</code></p>
<a href="<?= SOCIAL_URL ?>" style="display:inline-block;margin-top:14px;background:#2f81f7;color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none">→ الذهاب للموقع</a>
</body></html>
