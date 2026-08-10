<?php
/**
 * MCP HTTP Transport Server — Yassota
 * Compatible with: claude mcp add --transport http szad-ai https://chat.yassota.com/mcp.php
 *                                  --header "Authorization: Bearer <token>"
 */

// No stray output
while (ob_get_level()) ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo '{"error":"Method Not Allowed"}'; exit; }

// ── Load site config ──────────────────────────────────────────────────────
define('MCP_ENTRY', 1);
require_once __DIR__ . '/config.php';

// ── Auth ──────────────────────────────────────────────────────────────────
function mcp_valid_token(): string {
    global $pdo;
    try { $t = get_cfg($pdo, 'mcp_token', ''); if ($t !== '') return $t; } catch (Throwable $e) {}
    return defined('MCP_TOKEN') ? (string) MCP_TOKEN : '';
}

$authHeader = $_SERVER['HTTP_AUTHORIZATION']
           ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
if (!preg_match('/^Bearer\s+(.+)$/i', trim($authHeader), $m)) {
    http_response_code(401);
    echo json_encode(['error' => 'Missing Authorization: Bearer header']);
    exit;
}
$validToken = mcp_valid_token();
if ($validToken === '' || !hash_equals($validToken, trim($m[1]))) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

// ── Parse request ─────────────────────────────────────────────────────────
$raw = (string) file_get_contents('php://input');
$req = json_decode($raw, true);
if (!is_array($req) || !isset($req['jsonrpc'], $req['method'])) {
    http_response_code(400);
    echo json_encode(['jsonrpc'=>'2.0','id'=>null,'error'=>['code'=>-32600,'message'=>'Invalid JSON-RPC request']]);
    exit;
}

$id     = $req['id'] ?? null;
$method = (string) ($req['method'] ?? '');
$params = is_array($req['params'] ?? null) ? $req['params'] : [];

// ── Response helpers ──────────────────────────────────────────────────────
function mcp_ok(mixed $result, mixed $id): never {
    echo json_encode(['jsonrpc'=>'2.0','id'=>$id,'result'=>$result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function mcp_err(int $code, string $msg, mixed $id): never {
    echo json_encode(['jsonrpc'=>'2.0','id'=>$id,'error'=>['code'=>$code,'message'=>$msg]], JSON_UNESCAPED_UNICODE);
    exit;
}
function mcp_text(string $text): array {
    return ['content' => [['type' => 'text', 'text' => $text]]];
}

// ── Path safety ───────────────────────────────────────────────────────────
function safe_path(string $rel): string|false {
    $root = str_replace('\\', '/', ROOT_PATH);
    $abs  = $root . '/' . ltrim(str_replace(['../', '..' . DIRECTORY_SEPARATOR], '', $rel), '/');
    // For existing paths, resolve symlinks; for new paths, verify prefix only
    $real = realpath($abs);
    $check = $real !== false ? str_replace('\\', '/', $real) : str_replace('\\', '/', $abs);
    return str_starts_with($check, $root) ? $abs : false;
}

// ── Tool definitions ──────────────────────────────────────────────────────
$TOOLS = [
    [
        'name' => 'ping',
        'description' => 'Check server connectivity and return basic status.',
        'inputSchema' => ['type' => 'object', 'properties' => new stdClass, 'required' => []],
    ],
    [
        'name' => 'read_file',
        'description' => 'Read a file relative to the site root. Returns numbered lines.',
        'inputSchema' => [
            'type' => 'object',
            'properties' => [
                'path'   => ['type' => 'string',  'description' => 'File path relative to site root, e.g. config.php or assets/js/main.js'],
                'offset' => ['type' => 'integer', 'description' => 'First line to return (1-based, default 1)'],
                'limit'  => ['type' => 'integer', 'description' => 'Max lines to return (default 2000, max 5000)'],
            ],
            'required' => ['path'],
        ],
    ],
    [
        'name' => 'write_file',
        'description' => 'Write content to a file (creates directories as needed). PHP files are syntax-checked before saving; a backup is kept in .mcp-backups/.',
        'inputSchema' => [
            'type' => 'object',
            'properties' => [
                'path'    => ['type' => 'string', 'description' => 'File path relative to site root'],
                'content' => ['type' => 'string', 'description' => 'Full file content to write'],
            ],
            'required' => ['path', 'content'],
        ],
    ],
    [
        'name' => 'list_files',
        'description' => 'List files and directories in a path.',
        'inputSchema' => [
            'type' => 'object',
            'properties' => [
                'path'    => ['type' => 'string', 'description' => 'Directory path relative to site root (default: root)'],
                'pattern' => ['type' => 'string', 'description' => 'Glob pattern, e.g. *.php (default *)'],
            ],
            'required' => [],
        ],
    ],
    [
        'name' => 'delete_file',
        'description' => 'Safely delete a file by moving it to .mcp-trash/ (reversible).',
        'inputSchema' => [
            'type' => 'object',
            'properties' => [
                'path' => ['type' => 'string', 'description' => 'File path relative to site root'],
            ],
            'required' => ['path'],
        ],
    ],
    [
        'name' => 'grep_files',
        'description' => 'Search for a text pattern across files. Returns file:line: match lines.',
        'inputSchema' => [
            'type' => 'object',
            'properties' => [
                'pattern'   => ['type' => 'string',  'description' => 'Search string (literal) or regex'],
                'path'      => ['type' => 'string',  'description' => 'Directory to search in (default: root)'],
                'glob'      => ['type' => 'string',  'description' => 'Filename glob filter, e.g. *.php (default: *.php)'],
                'max_lines' => ['type' => 'integer', 'description' => 'Max results to return (default 100, max 500)'],
                'context'   => ['type' => 'integer', 'description' => 'Lines of context around each match (default 0)'],
            ],
            'required' => ['pattern'],
        ],
    ],
    [
        'name' => 'php_lint',
        'description' => 'Check PHP syntax. Pass either code (string) or a file path.',
        'inputSchema' => [
            'type' => 'object',
            'properties' => [
                'code' => ['type' => 'string', 'description' => 'PHP code to lint'],
                'path' => ['type' => 'string', 'description' => 'Path to PHP file (alternative to code)'],
            ],
            'required' => [],
        ],
    ],
    [
        'name' => 'error_log',
        'description' => 'View the PHP error log (last N lines).',
        'inputSchema' => [
            'type' => 'object',
            'properties' => [
                'lines' => ['type' => 'integer', 'description' => 'Lines to return (default 50, max 500)'],
            ],
            'required' => [],
        ],
    ],
    [
        'name' => 'db_query',
        'description' => 'Run a read-only SELECT query on the site database.',
        'inputSchema' => [
            'type' => 'object',
            'properties' => [
                'sql'    => ['type' => 'string', 'description' => 'SELECT SQL query'],
                'params' => ['type' => 'array',  'description' => 'Bound parameter values', 'items' => ['type' => 'string']],
            ],
            'required' => ['sql'],
        ],
    ],
];

// ── Route ─────────────────────────────────────────────────────────────────
switch ($method) {

    case 'initialize':
        mcp_ok([
            'protocolVersion' => '2024-11-05',
            'capabilities'    => ['tools' => []],
            'serverInfo'      => ['name' => 'yassota-mcp', 'version' => '1.0.0'],
        ], $id);

    case 'notifications/initialized':
        http_response_code(204);
        exit;

    case 'tools/list':
        mcp_ok(['tools' => $TOOLS], $id);

    case 'tools/call':
        $toolName = (string) ($params['name'] ?? '');
        $args     = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];
        run_tool($toolName, $args, $id);

    default:
        mcp_err(-32601, "Method not found: $method", $id);
}

// ── Tool implementations ──────────────────────────────────────────────────
function run_tool(string $name, array $a, mixed $id): never {
    global $pdo;
    try {
        switch ($name) {

            /* ── ping ── */
            case 'ping':
                mcp_ok(mcp_text('pong — yassota-mcp v1.0.0 · ' . SITE_URL . ' · ' . date('Y-m-d H:i:s T')), $id);

            /* ── read_file ── */
            case 'read_file': {
                $abs = safe_path($a['path'] ?? '');
                if (!$abs || !file_exists($abs))
                    mcp_ok(mcp_text("File not found: " . ($a['path'] ?? '(empty)')), $id);
                if (is_dir($abs))
                    mcp_ok(mcp_text("Path is a directory, use list_files instead."), $id);
                $lines = file($abs, FILE_IGNORE_NEW_LINES);
                if ($lines === false) mcp_ok(mcp_text("Cannot read file (permission denied?)."), $id);
                $offset = max(0, (int)($a['offset'] ?? 1) - 1);
                $limit  = min(5000, (int)($a['limit'] ?? 2000));
                $slice  = array_slice($lines, $offset, $limit);
                $out    = [];
                foreach ($slice as $i => $line) $out[] = ($offset + $i + 1) . "\t" . $line;
                mcp_ok(mcp_text(implode("\n", $out)), $id);
            }

            /* ── write_file ── */
            case 'write_file': {
                $path    = (string)($a['path'] ?? '');
                $content = (string)($a['content'] ?? '');
                $abs     = safe_path($path);
                if (!$abs) mcp_ok(mcp_text("Invalid or unsafe path."), $id);

                // PHP syntax check
                if (str_ends_with(strtolower($path), '.php')) {
                    $tmp = tempnam(sys_get_temp_dir(), 'mcp_');
                    file_put_contents($tmp, $content);
                    $lint = (string) shell_exec('php -l ' . escapeshellarg($tmp) . ' 2>&1');
                    unlink($tmp);
                    if (!str_contains($lint, 'No syntax errors')) {
                        mcp_ok(mcp_text("PHP syntax error — file NOT saved:\n$lint"), $id);
                    }
                }

                // Backup original
                if (file_exists($abs)) {
                    $bdir = ROOT_PATH . '/.mcp-backups';
                    if (!is_dir($bdir)) @mkdir($bdir, 0755, true);
                    @copy($abs, $bdir . '/' . basename($abs) . '.' . time() . '.bak');
                }

                $dir = dirname($abs);
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
                $bytes = file_put_contents($abs, $content);
                if ($bytes === false) mcp_ok(mcp_text("Write failed (disk full or permission denied)."), $id);
                mcp_ok(mcp_text("OK — wrote $bytes bytes to $path"), $id);
            }

            /* ── list_files ── */
            case 'list_files': {
                $relDir = (string)($a['path'] ?? '');
                $absDir = $relDir !== '' ? safe_path($relDir) : ROOT_PATH;
                if (!$absDir || !is_dir($absDir)) mcp_ok(mcp_text("Directory not found: $relDir"), $id);
                $pat   = (string)($a['pattern'] ?? '*');
                $items = glob($absDir . '/' . $pat, GLOB_MARK) ?: [];
                sort($items);
                $lines = ["type\tsize\tmodified\t\tpath"];
                foreach ($items as $item) {
                    $isDir = is_dir($item);
                    $rel   = substr(rtrim($item, '/'), strlen(ROOT_PATH) + 1);
                    $size  = $isDir ? '-' : (string) filesize($item);
                    $mtime = date('Y-m-d H:i', (int) filemtime($item));
                    $lines[] = ($isDir ? 'dir' : 'file') . "\t$size\t$mtime\t$rel";
                }
                mcp_ok(mcp_text(implode("\n", $lines)), $id);
            }

            /* ── delete_file ── */
            case 'delete_file': {
                $abs = safe_path((string)($a['path'] ?? ''));
                if (!$abs || !file_exists($abs)) mcp_ok(mcp_text("File not found."), $id);
                $trash = ROOT_PATH . '/.mcp-trash';
                if (!is_dir($trash)) @mkdir($trash, 0755, true);
                $dest = $trash . '/' . basename($abs) . '.' . time();
                rename($abs, $dest);
                mcp_ok(mcp_text("Moved to trash: " . basename($dest) . "\nRestore with: mv .mcp-trash/" . basename($dest) . " " . ($a['path'] ?? '')), $id);
            }

            /* ── grep_files ── */
            case 'grep_files': {
                $pattern  = (string)($a['pattern'] ?? '');
                $relDir   = (string)($a['path'] ?? '');
                $absDir   = $relDir !== '' ? safe_path($relDir) : ROOT_PATH;
                $glob     = (string)($a['glob'] ?? '*.php');
                $maxLines = min(500, (int)($a['max_lines'] ?? 100));
                $ctx      = max(0, min(5, (int)($a['context'] ?? 0)));
                if (!$absDir || !is_dir($absDir)) mcp_ok(mcp_text("Directory not found."), $id);

                $results = [];
                $count   = 0;
                $iter    = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($absDir, FilesystemIterator::SKIP_DOTS)
                );
                foreach ($iter as $file) {
                    if (!$file->isFile()) continue;
                    if ($glob !== '*' && !fnmatch($glob, $file->getBasename())) continue;
                    // Skip backup/trash dirs
                    $fp = str_replace('\\', '/', $file->getPathname());
                    if (str_contains($fp, '/.mcp-')) continue;

                    $lines   = @file($file->getPathname(), FILE_IGNORE_NEW_LINES);
                    if ($lines === false) continue;
                    $relFile = substr($file->getPathname(), strlen(ROOT_PATH) + 1);

                    foreach ($lines as $ln => $line) {
                        $match = false;
                        if (@preg_match('/' . str_replace('/', '\/', $pattern) . '/i', $line) === 1) {
                            $match = true;
                        } elseif (stripos($line, $pattern) !== false) {
                            $match = true;
                        }
                        if (!$match) continue;

                        if ($ctx > 0) {
                            for ($c = max(0, $ln - $ctx); $c <= min(count($lines) - 1, $ln + $ctx); $c++) {
                                $pfx = ($c === $ln) ? ':' : '-';
                                $results[] = "$relFile:" . ($c + 1) . "$pfx " . $lines[$c];
                            }
                            $results[] = '--';
                        } else {
                            $results[] = "$relFile:" . ($ln + 1) . ": $line";
                        }

                        if (++$count >= $maxLines) break 2;
                    }
                }
                mcp_ok(mcp_text(empty($results) ? "No matches for: $pattern" : implode("\n", $results)), $id);
            }

            /* ── php_lint ── */
            case 'php_lint': {
                $code = (string)($a['code'] ?? '');
                $path = (string)($a['path'] ?? '');
                if ($code !== '') {
                    $tmp = tempnam(sys_get_temp_dir(), 'mcp_lint_');
                    file_put_contents($tmp, $code);
                    $out = (string) shell_exec('php -l ' . escapeshellarg($tmp) . ' 2>&1');
                    // Rewrite temp path to placeholder in output
                    $out = str_replace($tmp, '<code>', $out);
                    unlink($tmp);
                } elseif ($path !== '') {
                    $abs = safe_path($path);
                    if (!$abs || !file_exists($abs)) mcp_ok(mcp_text("File not found: $path"), $id);
                    $out = (string) shell_exec('php -l ' . escapeshellarg($abs) . ' 2>&1');
                } else {
                    mcp_ok(mcp_text("Provide 'code' or 'path'."), $id);
                }
                mcp_ok(mcp_text(trim($out)), $id);
            }

            /* ── error_log ── */
            case 'error_log': {
                $lines = min(500, (int)($a['lines'] ?? 50));
                // Try multiple candidate locations
                $candidates = [
                    ini_get('error_log') ?: '',
                    ROOT_PATH . '/php_error.log',
                    ROOT_PATH . '/error.log',
                    dirname(ROOT_PATH) . '/error.log',
                    '/tmp/php_errors.log',
                ];
                $logFile = '';
                foreach ($candidates as $f) {
                    if ($f !== '' && file_exists($f)) { $logFile = $f; break; }
                }
                if ($logFile === '') mcp_ok(mcp_text("No error log found. Checked: " . implode(', ', array_filter($candidates))), $id);
                $all  = @file($logFile, FILE_IGNORE_NEW_LINES) ?: [];
                $tail = array_slice($all, -$lines);
                mcp_ok(mcp_text("Last $lines lines of " . basename($logFile) . ":\n" . implode("\n", $tail)), $id);
            }

            /* ── db_query ── */
            case 'db_query': {
                $sql    = trim((string)($a['sql'] ?? ''));
                $params = array_values((array)($a['params'] ?? []));
                if (!preg_match('/^\s*SELECT\b/i', $sql))
                    mcp_ok(mcp_text("Only SELECT queries are allowed."), $id);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll();
                if (empty($rows)) mcp_ok(mcp_text("(no rows returned)"), $id);
                $keys = array_keys($rows[0]);
                $out  = implode("\t", $keys);
                foreach ($rows as $row) {
                    $out .= "\n" . implode("\t", array_map(fn($v) => $v ?? 'NULL', $row));
                }
                mcp_ok(mcp_text($out), $id);
            }

            default:
                mcp_err(-32601, "Unknown tool: $name", $id);
        }
    } catch (Throwable $e) {
        mcp_err(-32000, $e->getMessage(), $id);
    }
}
