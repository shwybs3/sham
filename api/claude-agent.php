<?php
/**
 * Claude Agent — MCP Streamable HTTP endpoint.
 *
 * Register with the Claude CLI:
 *   claude mcp add --transport http szad-ai https://yourdomain.com/api/claude-agent.php?action=generate_post \
 *     --header "Authorization: Bearer <token>"
 *
 * The token is NOT stored in this file or in git — set it once from
 * لوحة التحكم → الإعدادات → "Claude Agent Token" (empty = disabled by
 * default). The endpoint speaks plain JSON-RPC 2.0 over POST (no SSE
 * streaming needed for this single, fast tool), exposing one tool:
 * generate_post — runs the same AI pipeline as the admin "توليد بالذكاء
 * الاصطناعي" button (config.php::ai_generate_blog_post) and always saves
 * the result as a draft, never publishes automatically.
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

function agent_rpc_error($id, int $code, string $message, int $httpStatus = 200): void {
    http_response_code($httpStatus);
    echo json_encode(['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_GET['action'] ?? '') !== 'generate_post') {
    http_response_code(404);
    echo json_encode(['error' => 'unknown action']);
    exit;
}

// ─── Bearer token auth — disabled (401 for everyone) until a token is set ───
$token = get_cfg($pdo, 'claude_agent_token');
$authHeader = $_SERVER['HTTP_AUTHORIZATION']
    ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
    ?? '';
$provided = '';
if (preg_match('/^Bearer\s+(.+)$/i', trim($authHeader), $m)) $provided = trim($m[1]);
if ($token === '' || $provided === '' || !hash_equals($token, $provided)) {
    http_response_code(401);
    echo json_encode(['jsonrpc' => '2.0', 'id' => null, 'error' => ['code' => -32001, 'message' => 'Unauthorized']], JSON_UNESCAPED_UNICODE);
    exit;
}

$req = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($req)) agent_rpc_error(null, -32700, 'Parse error', 400);

$id     = $req['id'] ?? null;
$method = $req['method'] ?? '';
$params = is_array($req['params'] ?? null) ? $req['params'] : [];

switch ($method) {
    case 'initialize':
        echo json_encode(['jsonrpc' => '2.0', 'id' => $id, 'result' => [
            'protocolVersion' => '2024-11-05',
            'capabilities'    => ['tools' => new stdClass()],
            'serverInfo'      => ['name' => 'szad-ai', 'version' => '1.0.0'],
        ]], JSON_UNESCAPED_UNICODE);
        break;

    // Notifications carry no id and expect no JSON-RPC response body.
    case 'notifications/initialized':
    case 'notifications/cancelled':
        http_response_code(202);
        break;

    case 'tools/list':
        echo json_encode(['jsonrpc' => '2.0', 'id' => $id, 'result' => ['tools' => [[
            'name'        => 'generate_post',
            'description' => 'يولّد مقال مدونة عربي كامل (نص + SEO + صورة غلاف) بالذكاء الاصطناعي ويحفظه كمسودة في yassota. لا يُنشر تلقائياً.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'type'  => ['type' => 'string', 'enum' => array_keys(BLOG_TYPES), 'description' => 'نوع المقال'],
                    'topic' => ['type' => 'string', 'description' => 'الموضوع المطلوب تحديداً (اختياري — يُختار تلقائياً إن ترك فارغاً)'],
                ],
            ],
        ]]]], JSON_UNESCAPED_UNICODE);
        break;

    case 'tools/call':
        if (($params['name'] ?? '') !== 'generate_post') agent_rpc_error($id, -32602, 'Unknown tool');
        $args  = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];
        $type  = isset(BLOG_TYPES[$args['type'] ?? '']) ? $args['type'] : 'article';
        $topic = trim((string)($args['topic'] ?? ''));

        $r = ai_generate_blog_post($pdo, $type, $topic);

        echo json_encode(['jsonrpc' => '2.0', 'id' => $id, 'result' => [
            'content' => [['type' => 'text', 'text' => json_encode($r, JSON_UNESCAPED_UNICODE)]],
            'isError' => empty($r['success']),
        ]], JSON_UNESCAPED_UNICODE);
        break;

    default:
        agent_rpc_error($id, -32601, 'Method not found');
}
