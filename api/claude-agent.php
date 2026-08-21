<?php
/**
 * Claude Agent API — a bearer-token endpoint so a Claude Code session can
 * manage blog content and read basic site diagnostics without a browser
 * session or SSH access.
 *
 * Deliberately scoped: this endpoint can read/write blog_posts and report
 * read-only diagnostics. It cannot write arbitrary files, run arbitrary SQL,
 * or execute shell commands — an HTTP endpoint that could edit the site's own
 * PHP files would be a standing remote-code-execution hole regardless of who
 * holds the key. Actual code changes still go through the normal git/PR flow.
 *
 * Auth: Authorization: Bearer <key>  (falls back to X-Claude-Api-Key, then an
 * api_key query/body param, since some Apache/CGI setups strip the
 * Authorization header before PHP ever sees it). Only a SHA-256 hash of the
 * key is stored — admin.php can show it once at generation time and never
 * again. Off by default; an admin must generate a key and enable it.
 */

require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json; charset=utf-8');

const CLAUDE_API_MAX_FAILS  = 10;
const CLAUDE_API_FAIL_WINDOW_MIN = 15;

function claude_api_fail(int $http, string $error): never {
    http_response_code($http);
    echo json_encode(['success' => false, 'error' => $error], JSON_UNESCAPED_UNICODE);
    exit;
}

function claude_api_note(PDO $pdo, string $action, bool $ok, string $detail = ''): void {
    try {
        $pdo->prepare("INSERT INTO claude_api_log (action, ip, ok, detail) VALUES (?,?,?,?)")
            ->execute([$action, $_SERVER['REMOTE_ADDR'] ?? null, $ok ? 1 : 0, mb_substr($detail, 0, 500)]);
    } catch (Throwable $e) {}
}

function claude_api_recent_fails(PDO $pdo): int {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($ip === '') return 0;
    try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM claude_api_log
                             WHERE ip=? AND action='auth' AND ok=0
                               AND created_at > (NOW() - INTERVAL " . CLAUDE_API_FAIL_WINDOW_MIN . " MINUTE)");
        $st->execute([$ip]);
        return (int)$st->fetchColumn();
    } catch (Throwable $e) { return 0; }
}

function claude_api_bearer_key(): string {
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($hdr === '' && function_exists('apache_request_headers')) {
        $all = apache_request_headers();
        $hdr = $all['Authorization'] ?? $all['authorization'] ?? '';
    }
    if (preg_match('/^Bearer\s+(.+)$/i', trim($hdr), $m)) return trim($m[1]);

    $alt = $_SERVER['HTTP_X_CLAUDE_API_KEY'] ?? '';
    if ($alt !== '') return trim($alt);

    return trim((string)($_GET['api_key'] ?? $_POST['api_key'] ?? ''));
}

/* ── Gate: feature must be explicitly enabled ─────────────────────── */
if (get_cfg($pdo, 'claude_api_enabled', '0') !== '1') {
    // 404, not 401/403 — an endpoint that isn't turned on shouldn't even
    // confirm it exists.
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'not found'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── General abuse throttle, independent of whether auth succeeds ── */
if (!evil_rate_check($pdo, 'claude_api', 120)) {
    claude_api_fail(429, 'rate limited');
}

/* ── Auth ───────────────────────────────────────────────────────── */
if (claude_api_recent_fails($pdo) >= CLAUDE_API_MAX_FAILS) {
    claude_api_fail(429, 'too many failed attempts — locked for ' . CLAUDE_API_FAIL_WINDOW_MIN . ' minutes');
}

$storedHash = get_cfg($pdo, 'claude_api_key_hash', '');
$providedKey = claude_api_bearer_key();

if ($storedHash === '' || $providedKey === '' || !hash_equals($storedHash, hash('sha256', $providedKey))) {
    claude_api_note($pdo, 'auth', false);
    claude_api_fail(401, 'unauthorized');
}

$action = (string)($_GET['action'] ?? $_POST['action'] ?? '');
$isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
$body   = $isPost ? (json_decode(file_get_contents('php://input'), true) ?: []) : [];

/* ── action=status — read-only diagnostics ────────────────────────── */
if ($action === 'status') {
    $docroot = dirname(__DIR__);

    $counts = [];
    try {
        $counts['blog_posts_total']     = (int)$pdo->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn();
        $counts['blog_posts_published'] = (int)$pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status='published'")->fetchColumn();
        $counts['apps_total']           = (int)$pdo->query("SELECT COUNT(*) FROM apps")->fetchColumn();
    } catch (Throwable $e) { $counts['error'] = 'query failed'; }

    // Best-effort recent error tail — most shared hosts won't expose this to
    // the PHP process, which is fine; the field is simply null then.
    $recentErrors = null;
    foreach (array_filter([ini_get('error_log') ?: null, $docroot . '/error_log', $docroot . '/php_errorlog']) as $candidate) {
        if (is_readable($candidate) && is_file($candidate)) {
            $lines = @file($candidate, FILE_IGNORE_NEW_LINES);
            if ($lines) { $recentErrors = array_slice($lines, -40); }
            break;
        }
    }

    claude_api_note($pdo, 'status', true);
    echo json_encode([
        'success'        => true,
        'php_version'    => PHP_VERSION,
        'server_software'=> $_SERVER['SERVER_SOFTWARE'] ?? null,
        'disk_free_mb'   => ($f = @disk_free_space($docroot)) !== false ? round($f / 1048576, 1) : null,
        'memory_limit'   => ini_get('memory_limit'),
        'counts'         => $counts,
        'recent_errors'  => $recentErrors,
        'server_time'    => date('c'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── action=list_posts ─────────────────────────────────────────────── */
if ($action === 'list_posts') {
    $status = (string)($_GET['status'] ?? '');
    $type   = (string)($_GET['type'] ?? '');
    $limit  = min(100, max(1, (int)($_GET['limit'] ?? 20)));
    $offset = max(0, (int)($_GET['offset'] ?? 0));

    $where  = [];
    $params = [];
    if (in_array($status, ['published', 'draft'], true)) { $where[] = 'status=?'; $params[] = $status; }
    if (isset(BLOG_TYPES[$type])) { $where[] = 'type=?'; $params[] = $type; }
    $sql = "SELECT id,type,title,slug,status,excerpt,created_at,updated_at FROM blog_posts";
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

    $st = $pdo->prepare($sql);
    $st->execute($params);

    claude_api_note($pdo, 'list_posts', true);
    echo json_encode(['success' => true, 'posts' => $st->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── action=get_post ───────────────────────────────────────────────── */
if ($action === 'get_post') {
    $id = (int)($_GET['id'] ?? 0);
    $st = $pdo->prepare("SELECT * FROM blog_posts WHERE id=?");
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    claude_api_note($pdo, 'get_post', (bool)$row, "id=$id");
    if (!$row) claude_api_fail(404, 'post not found');
    echo json_encode(['success' => true, 'post' => $row], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── action=create_post ────────────────────────────────────────────── */
if ($action === 'create_post' && $isPost) {
    $body = clean_utf8_deep($body);
    $type  = isset(BLOG_TYPES[$body['type'] ?? '']) ? $body['type'] : 'article';
    $title = trim((string)($body['title'] ?? ''));
    $html  = trim((string)($body['body'] ?? ''));
    $status = (($body['status'] ?? '') === 'published') ? 'published' : 'draft';

    if ($title === '' || $html === '') {
        claude_api_note($pdo, 'create_post', false, 'missing title/body');
        claude_api_fail(422, 'title and body are required');
    }

    $slug = unique_blog_slug($pdo, $title);
    $pdo->prepare("INSERT INTO blog_posts (type,title,slug,seo_title,meta_description,keywords,excerpt,body,status)
                   VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([
            $type, $title, $slug,
            trim((string)($body['seo_title'] ?? '')),
            trim((string)($body['meta_description'] ?? '')),
            trim((string)($body['keywords'] ?? '')),
            trim((string)($body['excerpt'] ?? '')),
            $html, $status,
        ]);
    $id = (int)$pdo->lastInsertId();
    if ($status === 'published') bump_cache_version($pdo);

    claude_api_note($pdo, 'create_post', true, "id=$id title=" . mb_substr($title, 0, 80));
    echo json_encode(['success' => true, 'id' => $id, 'slug' => $slug], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── action=update_post ────────────────────────────────────────────── */
if ($action === 'update_post' && $isPost) {
    $id = (int)($body['id'] ?? 0);
    $st = $pdo->prepare("SELECT id FROM blog_posts WHERE id=?");
    $st->execute([$id]);
    if (!$st->fetch()) { claude_api_note($pdo, 'update_post', false, "id=$id not found"); claude_api_fail(404, 'post not found'); }

    $body = clean_utf8_deep($body);
    $fields = [];
    $params = [];
    $map = ['title'=>'title','seo_title'=>'seo_title','meta_description'=>'meta_description',
            'keywords'=>'keywords','excerpt'=>'excerpt','body'=>'body'];
    foreach ($map as $key => $col) {
        if (array_key_exists($key, $body)) { $fields[] = "$col=?"; $params[] = trim((string)$body[$key]); }
    }
    if (isset($body['type']) && isset(BLOG_TYPES[$body['type']])) { $fields[] = 'type=?'; $params[] = $body['type']; }
    if (isset($body['status']) && in_array($body['status'], ['published','draft'], true)) {
        $fields[] = 'status=?'; $params[] = $body['status'];
    }
    if (!$fields) { claude_api_fail(422, 'no updatable fields provided'); }

    $params[] = $id;
    $pdo->prepare("UPDATE blog_posts SET " . implode(',', $fields) . " WHERE id=?")->execute($params);
    bump_cache_version($pdo);

    claude_api_note($pdo, 'update_post', true, "id=$id");
    echo json_encode(['success' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── action=publish_post / unpublish_post ─────────────────────────── */
if (($action === 'publish_post' || $action === 'unpublish_post') && $isPost) {
    $id = (int)($body['id'] ?? 0);
    $status = ($action === 'publish_post') ? 'published' : 'draft';

    $st = $pdo->prepare("UPDATE blog_posts SET status=? WHERE id=?");
    $st->execute([$status, $id]);
    $ok = $st->rowCount() > 0;
    if ($ok) bump_cache_version($pdo);

    claude_api_note($pdo, $action, $ok, "id=$id");
    if (!$ok) claude_api_fail(404, 'post not found');
    echo json_encode(['success' => true, 'id' => $id, 'status' => $status], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── action=delete_post ────────────────────────────────────────────── */
if ($action === 'delete_post' && $isPost) {
    $id = (int)($body['id'] ?? 0);
    $st = $pdo->prepare("DELETE FROM blog_posts WHERE id=?");
    $st->execute([$id]);
    $ok = $st->rowCount() > 0;
    if ($ok) bump_cache_version($pdo);

    claude_api_note($pdo, 'delete_post', $ok, "id=$id");
    if (!$ok) claude_api_fail(404, 'post not found');
    echo json_encode(['success' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── action=generate_post — uses the site's own AI keys ───────────── */
if ($action === 'generate_post' && $isPost) {
    @set_time_limit(120);
    $type  = (string)($body['type'] ?? 'article');
    $topic = trim((string)($body['topic'] ?? ''));

    // blog_ai_generate() always inserts as a draft, so there is nothing
    // publicly visible yet — no cache invalidation needed here. Use
    // publish_post afterwards once the draft has been reviewed.
    $result = blog_ai_generate($pdo, $type, $topic);

    claude_api_note($pdo, 'generate_post', (bool)($result['success'] ?? false), "type=$type topic=" . mb_substr($topic, 0, 80));
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

claude_api_note($pdo, 'unknown_action', false, $action);
claude_api_fail(400, 'unknown action');
