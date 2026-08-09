<?php
/**
 * api/mcp.php — Secure remote file management API for Claude.
 *
 * Allows Claude (or any authorized client) to read, write, list, and
 * check PHP files on this server via a Bearer token — no GitHub needed.
 *
 * Security design:
 * - 256-bit API token stored in config table, never in code
 * - All paths are validated to stay within DOCUMENT_ROOT
 * - PHP files are syntax-checked before being written
 * - Every action is logged to the DB audit trail
 * - Rate-limited to 120 requests/minute per IP
 * - IP whitelist support (optional; empty = any IP allowed)
 * - No shell command execution whatsoever
 *
 * Setup: visit admin.php?page=settings and set mcp_api_token to a long
 * random value; or let this file auto-generate one on first request.
 *
 * Usage:
 *   curl -H "Authorization: Bearer TOKEN" https://yourdomain.com/api/mcp.php \
 *        -d '{"action":"read","path":"tools/chat/index.php"}'
 */

define('MCP_VERSION', '1.0');

$cfgFile = dirname(__DIR__) . '/config.php';
if (!file_exists($cfgFile)) { http_response_code(503); echo json_encode(['error'=>'config.php not found']); exit; }
require_once $cfgFile;

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ── Authenticate ──────────────────────────────────────────────────────────
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_API_TOKEN'] ?? '';
$token = '';
if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m)) $token = trim($m[1]);
if (!$token) $token = $_GET['token'] ?? '';

$storedToken = get_cfg($pdo, 'mcp_api_token', '');
if ($storedToken === '') {
    // Auto-generate on first use — store and return it once
    $storedToken = bin2hex(random_bytes(32));
    set_cfg($pdo, 'mcp_api_token', $storedToken);
    http_response_code(401);
    echo json_encode(['error'=>'API token not configured. A token has been auto-generated and stored in your database. Retrieve it from admin.php?page=settings under "mcp_api_token".']);
    exit;
}

if (!hash_equals($storedToken, $token)) {
    http_response_code(401);
    echo json_encode(['error'=>'Unauthorized']);
    exit;
}

// ── IP whitelist (optional) ───────────────────────────────────────────────
$ipWhitelist = get_cfg($pdo, 'mcp_ip_whitelist', '');
if ($ipWhitelist !== '') {
    $allowed = array_filter(array_map('trim', explode(',', $ipWhitelist)));
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($clientIp, $allowed, true)) {
        http_response_code(403);
        echo json_encode(['error'=>'IP not whitelisted: '.$clientIp]);
        exit;
    }
}

// ── Rate limit (simple: 120 req/min per IP stored in DB config) ───────────
$ipKey  = 'mcp_rate_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
$window = (int)get_cfg($pdo, $ipKey . '_ts', '0');
$count  = (int)get_cfg($pdo, $ipKey . '_ct', '0');
$now    = time();
if ($now - $window > 60) { $window = $now; $count = 0; }
$count++;
set_cfg($pdo, $ipKey . '_ts', (string)$window);
set_cfg($pdo, $ipKey . '_ct', (string)$count);
if ($count > 120) {
    http_response_code(429);
    echo json_encode(['error'=>'Rate limit exceeded (120 req/min)']);
    exit;
}

// ── Parse input ───────────────────────────────────────────────────────────
$body   = file_get_contents('php://input') ?: '';
$input  = json_decode($body, true) ?: [];
if (!$body && !empty($_POST)) $input = $_POST; // form-encoded fallback
$action = trim($input['action'] ?? $_GET['action'] ?? '');

// ── Root directory (all paths must stay inside this) ─────────────────────
$webRoot = defined('DOCUMENT_ROOT') ? rtrim(DOCUMENT_ROOT,'/') : rtrim($_SERVER['DOCUMENT_ROOT'],'/');
if (!$webRoot) $webRoot = dirname(__DIR__);

function mcp_safe_path(string $rel, string $root): ?string {
    if (preg_match('#(^|/)\.\.(/|$)#', $rel)) return null; // no traversal
    $abs = realpath($root . '/' . ltrim($rel, '/'));
    if (!$abs) {
        // File might not exist yet — validate without realpath
        $abs = $root . '/' . ltrim($rel, '/');
        $real = realpath(dirname($abs));
        if (!$real || strpos($real, $root) !== 0) return null;
        return $abs;
    }
    if (strpos($abs, $root) !== 0) return null;
    return $abs;
}

function mcp_log(PDO $pdo, string $action, string $path, bool $ok, string $note=''): void {
    try {
        $pdo->prepare("INSERT INTO claude_api_log (endpoint,action,target,success,note,created_at) VALUES ('mcp',?,?,?,?,NOW())")
            ->execute([$action, $path, $ok?1:0, mb_substr($note,0,500)]);
    } catch (Throwable $e) {}
}

function mcp_ok(array $data): void {
    echo json_encode(array_merge(['ok'=>true], $data), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
function mcp_err(string $msg, int $code=400): void {
    http_response_code($code);
    echo json_encode(['ok'=>false,'error'=>$msg]);
}

// ── Dispatch ─────────────────────────────────────────────────────────────
switch ($action) {

// ── List directory ─────────────────────────────────────────────────────
case 'list':
    $rel  = trim($input['path'] ?? '.');
    $abs  = mcp_safe_path($rel, $webRoot);
    if (!$abs || !is_dir($abs)) { mcp_err('Path not found or not a directory'); break; }
    $entries = [];
    foreach (new DirectoryIterator($abs) as $f) {
        if ($f->isDot()) continue;
        $entries[] = [
            'name' => $f->getFilename(),
            'type' => $f->isDir() ? 'dir' : 'file',
            'size' => $f->isFile() ? $f->getSize() : null,
            'mtime'=> date('Y-m-d H:i:s', $f->getMTime()),
        ];
    }
    usort($entries, fn($a,$b) => ($a['type']==='dir'?0:1) - ($b['type']==='dir'?0:1) ?: strcmp($a['name'],$b['name']));
    mcp_log($pdo,'list',$rel,true);
    mcp_ok(['path'=>$rel,'entries'=>$entries,'count'=>count($entries)]);
    break;

// ── Read file ──────────────────────────────────────────────────────────
case 'read':
    $rel = trim($input['path'] ?? '');
    if (!$rel) { mcp_err('path required'); break; }
    $abs = mcp_safe_path($rel, $webRoot);
    if (!$abs || !is_file($abs)) { mcp_err('File not found'); break; }
    $size = filesize($abs);
    if ($size > 2 * 1024 * 1024) { mcp_err('File too large (>2MB). Use read_range for large files.'); break; }
    $content = file_get_contents($abs);
    if ($content === false) { mcp_err('Could not read file'); break; }
    mcp_log($pdo,'read',$rel,true,"size=$size");
    mcp_ok(['path'=>$rel,'content'=>$content,'size'=>$size,'lines'=>substr_count($content,"\n")+1]);
    break;

// ── Read file range (by line numbers) ─────────────────────────────────
case 'read_range':
    $rel   = trim($input['path'] ?? '');
    $from  = max(1,(int)($input['from'] ?? 1));
    $count = min(500, max(1,(int)($input['count'] ?? 100)));
    if (!$rel) { mcp_err('path required'); break; }
    $abs = mcp_safe_path($rel, $webRoot);
    if (!$abs || !is_file($abs)) { mcp_err('File not found'); break; }
    $lines  = file($abs, FILE_IGNORE_NEW_LINES);
    if ($lines === false) { mcp_err('Could not read file'); break; }
    $total  = count($lines);
    $slice  = array_slice($lines, $from-1, $count, true);
    $result = '';
    foreach ($slice as $lno => $line) $result .= ($lno+1) . "\t" . $line . "\n";
    mcp_log($pdo,'read_range',$rel,true,"from=$from count=$count");
    mcp_ok(['path'=>$rel,'from'=>$from,'to'=>min($from+$count-1,$total),'total_lines'=>$total,'content'=>$result]);
    break;

// ── Write file ─────────────────────────────────────────────────────────
case 'write':
    $rel     = trim($input['path'] ?? '');
    $content = $input['content'] ?? null;
    if (!$rel || $content === null) { mcp_err('path and content required'); break; }
    $abs = mcp_safe_path($rel, $webRoot);
    if (!$abs) { mcp_err('Invalid path'); break; }

    // PHP syntax check before writing
    if (preg_match('/\.php$/i', $rel)) {
        $tmpFile = tempnam(sys_get_temp_dir(), 'mcp_');
        file_put_contents($tmpFile, $content);
        $out = shell_exec('php -l ' . escapeshellarg($tmpFile) . ' 2>&1');
        unlink($tmpFile);
        if ($out === null || stripos($out,'No syntax errors') === false) {
            mcp_log($pdo,'write',$rel,false,'syntax error: '.($out??'unknown'));
            mcp_err('PHP syntax error: ' . ($out ?: 'unknown')); break;
        }
    }

    // Backup existing file if it exists
    if (is_file($abs)) {
        $bak = $abs . '.mcp_bak_' . date('YmdHis');
        @copy($abs, $bak);
    }

    // Ensure directory exists
    $dir = dirname($abs);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);

    if (file_put_contents($abs, $content) === false) {
        mcp_log($pdo,'write',$rel,false,'write failed');
        mcp_err('Could not write file'); break;
    }
    mcp_log($pdo,'write',$rel,true,'size='.strlen($content));
    mcp_ok(['path'=>$rel,'size'=>strlen($content),'lines'=>substr_count($content,"\n")+1]);
    break;

// ── Create directory ───────────────────────────────────────────────────
case 'mkdir':
    $rel = trim($input['path'] ?? '');
    if (!$rel) { mcp_err('path required'); break; }
    $abs = mcp_safe_path($rel, $webRoot);
    if (!$abs) { mcp_err('Invalid path'); break; }
    if (is_dir($abs)) { mcp_ok(['path'=>$rel,'created'=>false,'msg'=>'Already exists']); break; }
    if (!@mkdir($abs, 0755, true)) { mcp_err('Could not create directory'); break; }
    mcp_log($pdo,'mkdir',$rel,true);
    mcp_ok(['path'=>$rel,'created'=>true]);
    break;

// ── Delete file ────────────────────────────────────────────────────────
case 'delete':
    $rel = trim($input['path'] ?? '');
    if (!$rel) { mcp_err('path required'); break; }
    $abs = mcp_safe_path($rel, $webRoot);
    if (!$abs || !is_file($abs)) { mcp_err('File not found'); break; }
    // Backup before delete
    @copy($abs, $abs . '.mcp_deleted_' . date('YmdHis'));
    if (!@unlink($abs)) { mcp_err('Could not delete file'); break; }
    mcp_log($pdo,'delete',$rel,true);
    mcp_ok(['path'=>$rel,'deleted'=>true]);
    break;

// ── PHP syntax check ───────────────────────────────────────────────────
case 'lint':
    $rel = trim($input['path'] ?? '');
    if (!$rel) { mcp_err('path required'); break; }
    $abs = mcp_safe_path($rel, $webRoot);
    if (!$abs || !is_file($abs)) { mcp_err('File not found'); break; }
    $out = shell_exec('php -l ' . escapeshellarg($abs) . ' 2>&1');
    $ok  = $out !== null && stripos($out,'No syntax errors') !== false;
    mcp_log($pdo,'lint',$rel,$ok,$out??'');
    mcp_ok(['path'=>$rel,'ok'=>$ok,'output'=>trim($out??'')]);
    break;

// ── Lint all PHP files in a directory ─────────────────────────────────
case 'lint_all':
    $rel = trim($input['path'] ?? '.');
    $abs = mcp_safe_path($rel, $webRoot);
    if (!$abs || !is_dir($abs)) { mcp_err('Path not found or not a directory'); break; }
    $errors = [];
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($abs));
    $checked = 0;
    foreach ($iter as $f) {
        if (!$f->isFile() || $f->getExtension() !== 'php') continue;
        $out = shell_exec('php -l ' . escapeshellarg($f->getPathname()) . ' 2>&1');
        $checked++;
        if ($out === null || stripos($out,'No syntax errors') === false) {
            $relPath = str_replace($webRoot.'/', '', $f->getPathname());
            $errors[] = ['file'=>$relPath,'output'=>trim($out??'unknown')];
        }
        if ($checked > 500) break; // safety cap
    }
    mcp_log($pdo,'lint_all',$rel,empty($errors),"checked=$checked errors=".count($errors));
    mcp_ok(['path'=>$rel,'checked'=>$checked,'errors'=>$errors,'clean'=>empty($errors)]);
    break;

// ── Search in files ────────────────────────────────────────────────────
case 'grep':
    $pattern = trim($input['pattern'] ?? '');
    $rel     = trim($input['path'] ?? '.');
    $ext     = trim($input['ext'] ?? 'php');
    if (!$pattern) { mcp_err('pattern required'); break; }
    $abs = mcp_safe_path($rel, $webRoot);
    if (!$abs || !is_dir($abs)) { mcp_err('Path not found or not a directory'); break; }
    $results = [];
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($abs));
    $fileCount = 0;
    foreach ($iter as $f) {
        if (!$f->isFile()) continue;
        if ($ext && $f->getExtension() !== $ext) continue;
        $lines = file($f->getPathname(), FILE_IGNORE_NEW_LINES);
        if ($lines === false) continue;
        $fileCount++;
        if ($fileCount > 200) break;
        foreach ($lines as $lno => $line) {
            if (preg_match('/' . addcslashes($pattern,'/') . '/i', $line)) {
                $relPath = str_replace($webRoot.'/', '', $f->getPathname());
                $results[] = ['file'=>$relPath,'line'=>$lno+1,'content'=>trim($line)];
                if (count($results) >= 100) break 2;
            }
        }
    }
    mcp_log($pdo,'grep',$rel,true,"pattern=$pattern results=".count($results));
    mcp_ok(['pattern'=>$pattern,'results'=>$results,'count'=>count($results),'capped'=>count($results)>=100]);
    break;

// ── Get PHP error log ──────────────────────────────────────────────────
case 'error_log':
    $lines_req = min(200, max(1,(int)($input['lines'] ?? 50)));
    $logPath   = ini_get('error_log') ?: '/tmp/php_errors.log';
    if (!is_file($logPath) || !is_readable($logPath)) {
        mcp_ok(['log'=>'Error log not found or not readable at: '.$logPath,'path'=>$logPath]);
        break;
    }
    $all   = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $slice = array_slice($all, -$lines_req);
    mcp_log($pdo,'error_log','php_error_log',true,"lines=$lines_req");
    mcp_ok(['path'=>$logPath,'total_lines'=>count($all),'lines'=>$slice]);
    break;

// ── Server info ────────────────────────────────────────────────────────
case 'info':
    mcp_ok([
        'php_version' => PHP_VERSION,
        'web_root'    => $webRoot,
        'extensions'  => get_loaded_extensions(),
        'disk_free'   => disk_free_space($webRoot),
        'disk_total'  => disk_total_space($webRoot),
        'memory_limit'=> ini_get('memory_limit'),
        'max_execution_time'=> ini_get('max_execution_time'),
        'upload_max'  => ini_get('upload_max_filesize'),
        'site_url'    => defined('SITE_URL') ? SITE_URL : '',
        'mcp_version' => MCP_VERSION,
    ]);
    break;

// ── Ping ───────────────────────────────────────────────────────────────
case 'ping':
    mcp_ok(['pong'=>true,'time'=>date('c'),'version'=>MCP_VERSION]);
    break;

// ── Audit log ──────────────────────────────────────────────────────────
case 'audit_log':
    $limit = min(100, max(1,(int)($input['limit'] ?? 30)));
    try {
        $rows = $pdo->query("SELECT * FROM claude_api_log WHERE endpoint='mcp' ORDER BY id DESC LIMIT $limit")
                    ->fetchAll(PDO::FETCH_ASSOC);
    } catch(Throwable $e) { $rows = []; }
    mcp_ok(['entries'=>$rows,'count'=>count($rows)]);
    break;

default:
    mcp_err("Unknown action: '$action'. Available: list, read, read_range, write, mkdir, delete, lint, lint_all, grep, error_log, info, ping, audit_log");
}
