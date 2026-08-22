<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/_layout.php';

$page = $_GET['page'] ?? 'dashboard';
if (!array_key_exists($page, ADMIN_NAV)) $page = 'dashboard';

$file = __DIR__ . '/pages/' . $page . '.php';
admin_head(ADMIN_NAV[$page][0]);
admin_sidebar($page);
admin_topbar(ADMIN_NAV[$page][0]);
if (file_exists($file)) require $file; else echo '<div class="card">Page not found.</div>';
admin_foot();
