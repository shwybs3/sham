<?php
/* ============================================================
   DarkStore — cache.php
   كاش HTML ثابت لتسريع الصفحات على الاستضافة المشتركة (InfinityFree)
   يُخزَّن ناتج كل صفحة في ملف HTML ويُعاد استخدامه حتى 30 دقيقة
   ============================================================ */

define('CACHE_DIR', __DIR__ . '/data/cache');
define('CACHE_TTL', 1800); // ثانية (30 دقيقة)

function cache_path(string $type, string $slug = ''): string {
    $lang = (isset($_SESSION) && isset($_SESSION['lang'])) ? $_SESSION['lang'] : 'ar';
    $key  = $type . '_' . $lang . ($slug ? '_' . preg_replace('/[^a-z0-9-]/', '_', strtolower($slug)) : '');
    return CACHE_DIR . '/' . $key . '.html';
}

function cache_serve(string $type, string $slug = ''): bool {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') return false;
    if (!empty($_SESSION['admin'])) return false;
    $file = cache_path($type, $slug);
    if (!is_file($file) || (time() - filemtime($file)) > CACHE_TTL) return false;
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Cache: HIT');
    readfile($file);
    return true;
}

function cache_start(string $type, string $slug = ''): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') return;
    if (!empty($_SESSION['admin'])) return;
    @mkdir(CACHE_DIR, 0755, true);
    $file = cache_path($type, $slug);
    ob_start();
    register_shutdown_function(function() use ($file) {
        $html = ob_get_flush();
        if ($html && strlen($html) > 500) {
            @file_put_contents($file, $html, LOCK_EX);
        }
    });
}

// يُستدعى من admin.php عند حفظ أو حذف منتج
function cache_clear(string $type = '', string $slug = ''): void {
    if (!is_dir(CACHE_DIR)) return;
    if ($type === '') {
        foreach (glob(CACHE_DIR . '/*.html') ?: [] as $f) @unlink($f);
        return;
    }
    foreach (['ar', 'en'] as $lang) {
        $key  = $type . '_' . $lang . ($slug ? '_' . preg_replace('/[^a-z0-9-]/', '_', strtolower($slug)) : '');
        $file = CACHE_DIR . '/' . $key . '.html';
        if (is_file($file)) @unlink($file);
    }
    // تغييرات المنتج تؤثر أيضاً على صفحة الفهرس
    if ($type !== 'index') {
        foreach (['ar', 'en'] as $lang) {
            $f = CACHE_DIR . '/index_' . $lang . '.html';
            if (is_file($f)) @unlink($f);
        }
    }
}
