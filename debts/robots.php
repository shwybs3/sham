<?php
/**
 * robots.php — ملف robots.txt ديناميكي
 * ────────────────────────────────────────────────────────
 * يُولِّد robots.txt مخصصًا يقرأ SITE_URL من config.php
 * ليضمن صحة رابط Sitemap تلقائيًا.
 *
 * الوصول المباشر: https://example.com/robots.php
 * ─── أو عبر .htaccess (مُستحسن) ───────────────────────
 * في .htaccess أضف:
 *   RewriteRule ^robots\.txt$ robots.php [L,QSA]
 * هذا يجعل /robots.txt يُخدَّم من robots.php دون تغيير URL.
 */
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$isAllowPublic = ($settings['robots_allow_public'] ?? '0') === '1';

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: public, max-age=86400'); // يوم كامل
?>
# ═══════════════════════════════════════════════════
#  robots.txt — <?= $settings['shop_name'] ?? 'دفتر الدكان' ?>

#  مُولَّد تلقائيًا بواسطة robots.php
#  التاريخ: <?= date('Y-m-d H:i') ?>

# ═══════════════════════════════════════════════════

# ── قواعد عامة لكل الزواحف ──
User-agent: *

<?php if ($isAllowPublic): ?>
# وضع السماح العام (sitemap_allow_public = 1 في الإعدادات)
Allow: /
Disallow: /admin/
Disallow: /api/
Disallow: /includes/
Disallow: /setup.php
<?php else: ?>
# النظام خاص — نسمح فقط بالأصول الضرورية
Allow: /assets/css/
Allow: /assets/js/
Allow: /$

Disallow: /admin/
Disallow: /api/
Disallow: /includes/
Disallow: /customer.php
Disallow: /customers.php
Disallow: /setup.php
Disallow: /rss.php
Disallow: /sitemap.php
Disallow: /robots.php
<?php endif; ?>

# ── منع زواحف AI من جمع البيانات ──
User-agent: GPTBot
Disallow: /

User-agent: Google-Extended
Disallow: /

User-agent: CCBot
Disallow: /

User-agent: anthropic-ai
Disallow: /

User-agent: Claude-Web
Disallow: /

# ── Sitemap ──
Sitemap: <?= rtrim(SITE_URL, '/') ?>/sitemap.php
