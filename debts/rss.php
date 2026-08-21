<?php
/**
 * rss.php — تغذية RSS 2.0 لآخر العمليات
 * ────────────────────────────────────────────────────────
 * يُولِّد تغذية RSS تتضمن آخر العمليات (ديون ودفعات).
 * مفيدة لمتابعة النشاط المالي عبر قارئات RSS أو أنظمة تنبيه.
 *
 * الوصول: https://example.com/rss.php
 * أو عبر rewrite: /feed ← rss.php
 *
 * الأمان: يمكن حمايتها بمفتاح API عبر الإعداد rss_secret
 *         مثال: /rss.php?key=YOUR_SECRET
 */
declare(strict_types=1);
require_once __DIR__ . '/config.php';

/* ─── حماية اختيارية بمفتاح سري ─── */
$rssSecret = $settings['rss_secret'] ?? '';
if ($rssSecret !== '') {
    $providedKey = $_GET['key'] ?? '';
    if (!hash_equals($rssSecret, $providedKey)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        die('403 Forbidden — مطلوب مفتاح RSS صالح.');
    }
}

$shopName  = $settings['shop_name'] ?? 'دفتر الدكان';
$currency  = currencySymbol($settings['currency'] ?? 'SYP');
$limit     = min((int)($_GET['limit'] ?? 50), 200);
$feedUrl   = SITE_URL . '/rss.php';
$siteUrl   = SITE_URL . '/';

/* ─── جلب آخر العمليات مع بيانات الزبون ─── */
$stmt = $pdo->prepare(
    "SELECT t.id, t.type, t.amount, t.description, t.created_at,
            c.id AS customer_id, c.name AS customer_name
     FROM transactions t
     JOIN customers c ON c.id = t.customer_id
     ORDER BY t.created_at DESC, t.id DESC
     LIMIT ?"
);
$stmt->execute([$limit]);
$transactions = $stmt->fetchAll();

/* ─── مساعدات ─── */
function rfc822(string $dt): string {
    try {
        return (new DateTimeImmutable($dt))->format(DateTime::RSS);
    } catch (Exception) {
        return date(DateTime::RSS);
    }
}
function xmlEsc(string $s): string {
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}
function txTitle(string $type, string $customer, float $amount, string $currency): string {
    $typeLabel = $type === 'purchase' ? '🔴 دين جديد' : '🟢 دفعة';
    return "{$typeLabel} — {$customer}: " . number_format($amount, 0) . " {$currency}";
}

$buildDate = !empty($transactions)
    ? rfc822($transactions[0]['created_at'])
    : date(DateTime::RSS);

/* ─── رأس الاستجابة ─── */
header('Content-Type: application/rss+xml; charset=UTF-8');
header('Cache-Control: public, max-age=900'); // 15 دقيقة

/* ─── إخراج RSS ─── */
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:dc="http://purl.org/dc/elements/1.1/"
     xmlns:content="http://purl.org/rss/1.0/modules/content/">

  <channel>
    <title><?= xmlEsc($shopName) ?> — آخر العمليات</title>
    <link><?= xmlEsc($siteUrl) ?></link>
    <description>تغذية RSS لآخر عمليات الديون والدفعات في <?= xmlEsc($shopName) ?></description>
    <language><?= xmlEsc(SITE_LANG) ?></language>
    <lastBuildDate><?= $buildDate ?></lastBuildDate>
    <ttl>15</ttl>
    <atom:link href="<?= xmlEsc($feedUrl) ?>" rel="self" type="application/rss+xml"/>

    <?php foreach ($transactions as $tx):
        $amount    = (float)$tx['amount'];
        $title     = txTitle($tx['type'], $tx['customer_name'], $amount, $currency);
        $link      = SITE_URL . '/customer.php?id=' . (int)$tx['customer_id'];
        $pubDate   = rfc822($tx['created_at']);
        $desc      = $tx['description'] ?? '';
        $typeLabel = $tx['type'] === 'purchase' ? 'دين' : 'دفعة';
        $content   = "نوع: {$typeLabel} | المبلغ: " . number_format($amount, 0) . " {$currency}";
        if ($desc !== '') $content .= " | ملاحظة: {$desc}";
    ?>
    <item>
      <title><?= xmlEsc($title) ?></title>
      <link><?= xmlEsc($link) ?></link>
      <guid isPermaLink="false">tx-<?= (int)$tx['id'] ?></guid>
      <pubDate><?= $pubDate ?></pubDate>
      <dc:creator><?= xmlEsc($tx['customer_name']) ?></dc:creator>
      <category><?= $tx['type'] === 'purchase' ? 'دين' : 'دفعة' ?></category>
      <description><?= xmlEsc($content) ?></description>
      <content:encoded><![CDATA[
        <p><strong>الزبون:</strong> <?= xmlEsc($tx['customer_name']) ?></p>
        <p><strong>النوع:</strong> <?= xmlEsc($typeLabel) ?></p>
        <p><strong>المبلغ:</strong> <?= number_format($amount, 0) ?> <?= xmlEsc($currency) ?></p>
        <?php if ($desc !== ''): ?>
        <p><strong>ملاحظة:</strong> <?= xmlEsc($desc) ?></p>
        <?php endif; ?>
        <p><a href="<?= xmlEsc($link) ?>">عرض تفاصيل الزبون</a></p>
      ]]></content:encoded>
    </item>
    <?php endforeach; ?>

  </channel>
</rss>
