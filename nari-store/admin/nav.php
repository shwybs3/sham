<?php
/** شريط تنقّل موحّد بين كل صفحات لوحة التحكم — يعطي شكل "نظام إدارة محتوى" متكامل. */

if (!defined('NARI_ADMIN')) {
    http_response_code(403);
    exit('محظور');
}

function nari_admin_nav(string $active): void
{
    $items = [
        'overview' => ['index.php', 'نظرة عامة'],
        'products' => ['products.php', 'المنتجات والأسعار'],
        'content'  => ['content.php', 'الصفحات والمقالات'],
        'files'    => ['files.php', 'مدير الملفات'],
        'site'     => ['site-settings.php', 'الموقع والدومين'],
    ];
    echo '<div class="admin-nav"><div class="wrap">';
    foreach ($items as $key => $item) {
        [$href, $label] = $item;
        $cls = $key === $active ? ' class="active"' : '';
        echo '<a' . $cls . ' href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
    }
    echo '</div></div>';
}
