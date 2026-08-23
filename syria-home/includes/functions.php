<?php
/* Core helpers shared across the whole site + admin. */

function setting(string $key, $default = '') {
    static $cache = null;
    global $pdo;
    if ($cache === null) {
        $cache = [];
        foreach ($pdo->query("SELECT `key`, `value` FROM settings") as $row) {
            $cache[$row['key']] = $row['value'];
        }
    }
    return $cache[$key] ?? $default;
}

function set_setting(string $key, $value): void {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
    $stmt->execute([$key, $value]);
}

/** Short, clean, SEO-friendly slugs — capped well under Google's ~60-char
 *  display limit, cutting at a whole word rather than mid-word. */
function slugify(string $text, int $maxLength = 60): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim(@iconv('utf-8', 'us-ascii//TRANSLIT', $text) ?: $text, '-');
    $text = strtolower($text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = preg_replace('~-+~', '-', $text);
    $text = trim($text, '-');

    if (mb_strlen($text) > $maxLength) {
        $truncated = mb_substr($text, 0, $maxLength);
        $lastHyphen = mb_strrpos($truncated, '-');
        if ($lastHyphen !== false && $lastHyphen >= (int)($maxLength * 0.4)) {
            $truncated = mb_substr($truncated, 0, $lastHyphen);
        }
        $text = trim($truncated, '-');
    }

    return $text !== '' ? $text : 'item-' . substr(md5((string)microtime(true)), 0, 6);
}

function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}

function csrf_check(): bool {
    return isset($_POST['csrf']) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

function csrf_check_get(): bool {
    return isset($_GET['csrf']) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_GET['csrf']);
}

function is_admin_logged_in(): bool {
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void {
    if (!is_admin_logged_in()) {
        header('Location: ' . rtrim(admin_base_url(), '/') . '/login.php');
        exit;
    }
}

function admin_base_url(): string {
    return rtrim(SITE_URL, '/') . '/admin';
}

function site_url(string $path = ''): string {
    return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
}

/** True when this request is browsing the Arabic section — either an
 *  /ar/-prefixed pretty URL, or the ?lang=ar query param those rewrite to. */
function is_ar_request(): bool {
    static $ar = null;
    if ($ar === null) {
        $ar = ($_GET['lang'] ?? '') === 'ar' || (bool)preg_match('~^/ar(/|$|\?)~', $_SERVER['REQUEST_URI'] ?? '');
    }
    return $ar;
}

/** English/Arabic UI string picker for chrome text that isn't stored in the
 *  DB (nav labels, breadcrumbs, buttons). Pass $lang explicitly when it's
 *  known from a content row (e.g. $article['lang']); otherwise it falls
 *  back to the current request's language. */
function t(string $en, string $ar, ?string $lang = null): string {
    return ($lang ?? (is_ar_request() ? 'ar' : 'en')) === 'ar' ? $ar : $en;
}

/* Original CSS-only "hero" graphic per article/tool — avoids any copyright
   risk from stock/AI photo sourcing and keeps every page 100% self-contained. */
const HERO_GRADIENTS = [
    'g1' => ['#6366f1', '#8b5cf6'],
    'g2' => ['#0ea5e9', '#22d3ee'],
    'g3' => ['#f97316', '#f43f5e'],
    'g4' => ['#10b981', '#059669'],
    'g5' => ['#eab308', '#f97316'],
    'g6' => ['#ec4899', '#8b5cf6'],
    'g7' => ['#14b8a6', '#3b82f6'],
    'g8' => ['#ef4444', '#f59e0b'],
];

function hero_style_css(string $key): string {
    [$a, $b] = HERO_GRADIENTS[$key] ?? HERO_GRADIENTS['g1'];
    return "background:linear-gradient(135deg,$a,$b)";
}

function reading_time_from_html(string $html): int {
    $words = str_word_count(strip_tags($html));
    return max(2, (int)ceil($words / 220));
}

function excerpt_from_html(string $html, int $len = 160): string {
    $text = trim(preg_replace('~\s+~', ' ', strip_tags($html)));
    return mb_strlen($text) > $len ? mb_substr($text, 0, $len - 1) . '…' : $text;
}

function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    $units = [31536000 => 'year', 2592000 => 'month', 86400 => 'day', 3600 => 'hour', 60 => 'minute'];
    foreach ($units as $secs => $label) {
        if ($diff >= $secs) {
            $n = floor($diff / $secs);
            return $n . ' ' . $label . ($n > 1 ? 's' : '') . ' ago';
        }
    }
    return 'just now';
}

/** A persistent, unguessable per-browser identifier — used to remember
 *  which premium content a visitor has paid to unlock. Not tied to any
 *  personal information; just a random token in a long-lived cookie. */
function visitor_identifier(): string {
    if (!empty($_COOKIE['sh_vid']) && preg_match('/^[a-f0-9]{32}$/', $_COOKIE['sh_vid'])) {
        return $_COOKIE['sh_vid'];
    }
    $id = bin2hex(random_bytes(16));
    setcookie('sh_vid', $id, time() + 3600 * 24 * 365 * 2, '/', '', !empty($_SERVER['HTTPS']), true);
    $_COOKIE['sh_vid'] = $id;
    return $id;
}

function is_content_unlocked(string $contentType, int $contentId): bool {
    global $pdo;
    $stmt = $pdo->prepare("SELECT 1 FROM unlocks WHERE identifier = ? AND content_type = ? AND content_id = ? LIMIT 1");
    $stmt->execute([visitor_identifier(), $contentType, $contentId]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Auto-links the first mention of another published article's or tool's
 * title inside $html to that page, skipping anything already inside an
 * <a>...</a>. Best-effort HTML text scanning, not a full parser — safe
 * because it only ever inserts <a> tags, never removes/rewrites markup.
 */
function auto_link_body(PDO $pdo, string $html, int $excludeArticleId, int $maxLinks = 6): string {
    $candidates = [];
    $a = $pdo->prepare("SELECT title AS label, slug, 'article.php?slug=' AS base FROM articles WHERE status='published' AND id != ? ORDER BY CHAR_LENGTH(title) DESC LIMIT 60");
    $a->execute([$excludeArticleId]);
    foreach ($a as $row) $candidates[] = $row;
    $t = $pdo->query("SELECT name AS label, slug, 'tool.php?slug=' AS base FROM tools WHERE status='published' ORDER BY CHAR_LENGTH(name) DESC LIMIT 40");
    foreach ($t as $row) $candidates[] = $row;
    usort($candidates, fn($x, $y) => mb_strlen($y['label']) <=> mb_strlen($x['label']));

    // Existing anchor spans — never insert a link inside or overlapping one.
    // Recomputed fresh every iteration below since each insertion shifts offsets.
    $anchorSpansOf = function (string $h): array {
        preg_match_all('~<a\b[^>]*>.*?</a>~is', $h, $matches, PREG_OFFSET_CAPTURE);
        $spans = [];
        foreach ($matches[0] as $m) $spans[] = [$m[1], $m[1] + strlen($m[0])];
        return $spans;
    };
    $insideAnySpan = function (int $pos, array $spans): bool {
        foreach ($spans as [$start, $end]) if ($pos >= $start && $pos < $end) return true;
        return false;
    };

    $linked = 0;
    foreach ($candidates as $c) {
        if ($linked >= $maxLinks) break;
        $label = trim($c['label']);
        if (mb_strlen($label) < 5) continue;
        $pattern = '~(?<![\w>])(' . preg_quote($label, '~') . ')(?![\w<])~iu';
        if (!preg_match($pattern, $html, $m, PREG_OFFSET_CAPTURE)) continue;
        [$matchText, $pos] = $m[1];
        if ($insideAnySpan($pos, $anchorSpansOf($html))) continue;

        $url = site_url($c['base'] . urlencode($c['slug']));
        $replacement = '<a href="' . e($url) . '">' . $matchText . '</a>';
        $html = substr($html, 0, $pos) . $replacement . substr($html, $pos + strlen($matchText));
        $linked++;
    }
    return $html;
}

function log_ai_activity(string $action, string $targetType, ?int $targetId, string $summary): void {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO ai_activity_log (actor, action, target_type, target_id, summary) VALUES ('gemini', ?, ?, ?, ?)");
    $stmt->execute([$action, $targetType, $targetId, $summary]);
}

/** Turns tags/keywords into a handful of #Hashtags (CamelCase, no spaces). */
function share_kit_hashtags(string $tagsCsv, int $limit = 4): string {
    $tags = array_filter(array_map('trim', explode(',', $tagsCsv)));
    $tags = array_slice($tags, 0, $limit);
    $out = [];
    foreach ($tags as $tag) {
        $words = preg_split('~[\s\-]+~u', $tag, -1, PREG_SPLIT_NO_EMPTY);
        $camel = implode('', array_map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)) . mb_substr($w, 1), $words));
        if ($camel !== '') $out[] = '#' . $camel;
    }
    return implode(' ', $out);
}

/** Ready-to-paste promotional posts for one article — a "share kit" an
 *  editor can copy straight into social media or another site's comment
 *  section, no AI call required (deterministic, works with no API keys
 *  configured). Templates are chosen by $lang. */
function share_kit_variants(array $article, string $canonicalUrl, string $lang = 'en'): array {
    $title = $article['title'];
    $excerpt = trim((string)($article['excerpt'] ?? ''));
    $hashtags = share_kit_hashtags((string)($article['tags'] ?? ''));

    if ($lang === 'ar') {
        $short = mb_substr($title, 0, 140) . ' ' . $canonicalUrl . ($hashtags ? ' ' . $hashtags : '');
        $medium = $title . "\n\n" . mb_substr($excerpt, 0, 220) . "\n\n" . 'اقرأ المقال كاملاً: ' . $canonicalUrl;
        $long = 'قرأت للتو مقالاً جيدًا بعنوان "' . $title . '". ' . $excerpt . ' يستحق الاطلاع عليه: ' . $canonicalUrl . ($hashtags ? "\n" . $hashtags : '');
        return [
            ['label' => 'منشور قصير (X / تويتر)', 'text' => $short],
            ['label' => 'منشور متوسط (فيسبوك / لينكدإن)', 'text' => $medium],
            ['label' => 'تعليق أو منتدى (نص أطول)', 'text' => $long],
            ['label' => 'الرابط فقط', 'text' => $canonicalUrl],
        ];
    }

    $short = mb_substr($title, 0, 160) . ' ' . $canonicalUrl . ($hashtags ? ' ' . $hashtags : '');
    $medium = $title . "\n\n" . mb_substr($excerpt, 0, 220) . "\n\n" . 'Read the full breakdown: ' . $canonicalUrl;
    $long = 'Just read a solid breakdown of this: "' . $title . '". ' . $excerpt . ' Worth a look if this is your kind of thing: ' . $canonicalUrl . ($hashtags ? "\n" . $hashtags : '');
    return [
        ['label' => 'Short post (X / Twitter)', 'text' => $short],
        ['label' => 'Medium post (Facebook / LinkedIn)', 'text' => $medium],
        ['label' => 'Comment / forum post (longer)', 'text' => $long],
        ['label' => 'Link only', 'text' => $canonicalUrl],
    ];
}
