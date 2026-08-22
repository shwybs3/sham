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

function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim(@iconv('utf-8', 'us-ascii//TRANSLIT', $text) ?: $text, '-');
    $text = strtolower($text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = preg_replace('~-+~', '-', $text);
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

function log_ai_activity(string $action, string $targetType, ?int $targetId, string $summary): void {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO ai_activity_log (actor, action, target_type, target_id, summary) VALUES ('gemini', ?, ?, ?, ?)");
    $stmt->execute([$action, $targetType, $targetId, $summary]);
}
