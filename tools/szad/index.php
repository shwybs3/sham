<?php
/**
 * tools/szad/index.php — Szad AI: Arabic AI chat with web search & deep thinking.
 *
 * Distinct from ياسمين: focuses on research, analysis, and professional use.
 * Web search mode: uses online-capable models via OpenRouter.
 * Deep thinking mode: uses reasoning-focused models + chain-of-thought.
 */
require_once dirname(__DIR__) . '/_base.php';

$mainCfg = dirname(dirname(__DIR__)) . '/config.php';
if (!file_exists($mainCfg)) { http_response_code(503); echo 'Service unavailable'; exit; }
require_once $mainCfg;
require_once dirname(__DIR__) . '/chat/_auth.php'; // reuse Yasmin auth

$me = ys_current_user($pdo);

/* ── Helper ─────────────────────────────────────────────────── */
function szad_cfg(PDO $pdo, string $k, string $d = ''): string {
    return get_cfg($pdo, 'szad_' . $k, get_cfg($pdo, $k, $d));
}

/* ── AJAX: List conversations ─────────────────────────────── */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'szad_convs') {
    header('Content-Type: application/json; charset=utf-8');
    $sid = $_COOKIE['yasmin_sid'] ?? '';
    [$w, $p] = ys_scope($me, $sid);
    try {
        $st = $pdo->prepare("SELECT id,title,updated_at FROM yasmin_conversations
                              WHERE $w AND title LIKE '[Szad]%'
                              ORDER BY updated_at DESC LIMIT 30");
        $st->execute($p);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) $r['title'] = ltrim((string)$r['title'], '[Szad] ');
        echo json_encode($rows);
    } catch (Throwable $e) { echo '[]'; }
    exit;
}

/* ── AJAX: New conversation ─────────────────────────────── */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'szad_new') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true]);
    exit;
}

/* ── AJAX: Delete conversation ─────────────────────────── */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'szad_del' && $me) {
    header('Content-Type: application/json; charset=utf-8');
    $cid = (int)($_POST['id'] ?? 0);
    if ($cid) {
        $pdo->prepare("DELETE FROM yasmin_conversations WHERE id=? AND user_id=?")->execute([$cid, (int)$me['id']]);
    }
    echo json_encode(['ok' => true]);
    exit;
}

/* ── AJAX: Load conversation messages ───────────────────── */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'szad_msgs') {
    header('Content-Type: application/json; charset=utf-8');
    $cid = (int)($_GET['id'] ?? 0);
    $sid = $_COOKIE['yasmin_sid'] ?? '';
    [$w, $p] = ys_scope($me, $sid);
    try {
        $check = $pdo->prepare("SELECT id FROM yasmin_conversations WHERE id=? AND $w");
        $check->execute(array_merge([$cid], $p));
        if (!$check->fetch()) { echo '[]'; exit; }
        $msgs = $pdo->prepare("SELECT role,content FROM yasmin_messages WHERE conversation_id=? ORDER BY id LIMIT 60");
        $msgs->execute([$cid]);
        echo json_encode($msgs->fetchAll(PDO::FETCH_ASSOC));
    } catch (Throwable $e) { echo '[]'; }
    exit;
}

/* ── SSE chat endpoint ───────────────────────────────────── */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'szad_chat') {
    @set_time_limit(90);
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');

    function szad_sse(string $event, $data): void {
        echo "event: {$event}\ndata: " . (is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE)) . "\n\n";
        if (ob_get_level()) ob_flush();
        flush();
    }

    $message  = trim((string)($_POST['msg'] ?? ''));
    $convId   = (int)($_POST['conv_id'] ?? 0);
    $mode     = trim($_POST['mode'] ?? 'normal'); // normal | search | think
    $sid      = $_COOKIE['yasmin_sid'] ?? '';
    $sessionId = $sid !== '' ? $sid : bin2hex(random_bytes(12));

    if ($message === '') { szad_sse('error', ['msg' => 'الرسالة فارغة.']); exit; }

    // Rate limiting (shares ياسمين limits)
    $cap = ys_hourly_cap($me, $pdo);
    if (ys_recent_msg_count($pdo, $me, $sessionId) >= $cap) {
        szad_sse('error', ['msg' => 'وصلت للحد الأقصى من الرسائل بهالساعة. جرّب بعد شوي.']);
        exit;
    }

    // Retrieve history
    $history = [];
    if ($convId) {
        [$w, $p] = ys_scope($me, $sessionId);
        $check = $pdo->prepare("SELECT id FROM yasmin_conversations WHERE id=? AND $w");
        $check->execute(array_merge([$convId], $p));
        if ($check->fetch()) {
            $hst = $pdo->prepare("SELECT role,content FROM yasmin_messages WHERE conversation_id=? ORDER BY id ASC LIMIT 30");
            $hst->execute([$convId]);
            $history = $hst->fetchAll(PDO::FETCH_ASSOC);
        } else { $convId = 0; }
    }

    // System prompt per mode
    $sysBase = 'أنتَ Szad AI، مساعد ذكاء اصطناعي عربي احترافي متخصص في البحث والتحليل العميق. '
             . 'تُجيب بالعربية الفصحى السهلة مع لمسة من الدقة العلمية. '
             . 'تتميز بالتفكير العميق، تحليل المعلومات، والإجابات الشاملة الموثقة. '
             . 'لا تذكر أبداً أنك نموذج لغوي — أنتَ Szad AI.';

    if ($mode === 'search') {
        $sys = $sysBase . ' أنتَ في وضع "بحث في الإنترنت": استخدم معرفتك المحدّثة وقدّم معلومات محددة وحديثة قدر الإمكان. '
             . 'أشر للمصادر والتواريخ حيثما أمكن. كن دقيقاً ومحدداً.';
    } elseif ($mode === 'think') {
        $sys = $sysBase . ' أنتَ في وضع "التفكير العميق": قبل الإجابة، فكّر بصوت عالٍ خطوة بخطوة. '
             . 'حلّل المشكلة من زوايا مختلفة، ثم قدّم إجابتك النهائية بعد التحليل. '
             . 'استخدم عنواناً فاصلاً بين "التحليل" و"الإجابة النهائية".';
    } else {
        $sys = $sysBase . ' تساعد في جميع المجالات: بحث، تحليل، برمجة، ترجمة، تلخيص، كتابة، ومزيد.';
    }

    if ($me && trim((string)$me['name']) !== '') {
        $sys .= ' اسم المستخدم: ' . trim((string)$me['name']) . '.';
    }

    // Build messages array
    $messages = [['role' => 'system', 'content' => $sys]];
    foreach (array_slice($history, -20) as $h) {
        $messages[] = ['role' => $h['role'] === 'user' ? 'user' : 'assistant', 'content' => (string)$h['content']];
    }
    $messages[] = ['role' => 'user', 'content' => $message];

    // Choose models based on mode
    if ($mode === 'search') {
        // Online models with real web search
        $models = ['perplexity/llama-3.1-sonar-huge-128k-online', 'perplexity/llama-3.1-sonar-large-128k-online', 'openai/gpt-4o-mini'];
    } elseif ($mode === 'think') {
        // Reasoning/thinking models
        $models = ['anthropic/claude-3.5-sonnet', 'openai/o1-mini', 'openai/gpt-4o', 'google/gemini-pro-1.5'];
    } else {
        // Tier-based or free models
        $tierModels = ys_tier_models($me, $pdo);
        $models = $tierModels ?: build_model_rotation($pdo, false) ?: ['openrouter/auto'];
    }

    // Keys
    $keys = openrouter_keys(get_cfg($pdo, 'openrouter_key'));
    if (!$keys) { szad_sse('error', ['msg' => 'مفاتيح API غير متاحة.']); exit; }

    // Save/update conversation
    [$scopeWhere, $scopeParams] = ys_scope($me, $sessionId);
    $convTitle = '[Szad] ' . mb_substr($message, 0, 55, 'UTF-8');
    if ($convId) {
        $pdo->prepare("UPDATE yasmin_conversations SET message_count=message_count+1,updated_at=NOW() WHERE id=?")->execute([$convId]);
    } else {
        $ins = $pdo->prepare("INSERT INTO yasmin_conversations (session_id,user_id,title,message_count) VALUES (?,?,?,1)");
        $ins->execute([$sessionId, $me ? (int)$me['id'] : null, $convTitle]);
        $convId = (int)$pdo->lastInsertId();
    }
    $pdo->prepare("INSERT INTO yasmin_messages (conversation_id,role,content) VALUES (?,'user',?)")->execute([$convId, $message]);
    if ($me) { try { $pdo->prepare("UPDATE yasmin_users SET msg_count=msg_count+1 WHERE id=?")->execute([(int)$me['id']]); } catch(Throwable $e){} }

    szad_sse('conv', json_encode(['id' => $convId]));

    // Stream response
    $streamed = false;
    $fullResponse = '';
    shuffle($keys);

    foreach ($keys as $key) {
        foreach ($models as $mId) {
            if ($streamed) break 2;

            $payload = json_encode([
                'model'       => $mId,
                'messages'    => $messages,
                'stream'      => true,
                'max_tokens'  => $mode === 'think' ? 4000 : 2000,
                'temperature' => $mode === 'think' ? 0.3 : 0.7,
            ], JSON_UNESCAPED_UNICODE);

            $buffer = ''; $gotData = false;

            $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_TIMEOUT        => 90,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $key,
                    'Content-Type: application/json',
                    'HTTP-Referer: https://yassota.com',
                    'X-Title: Szad AI',
                ],
                CURLOPT_WRITEFUNCTION => function($ch, $data) use (&$buffer, &$gotData, &$fullResponse) {
                    $buffer .= $data;
                    while (($pos = strpos($buffer, "\n")) !== false) {
                        $line   = substr($buffer, 0, $pos);
                        $buffer = substr($buffer, $pos + 1);
                        $line   = trim($line);
                        if (!str_starts_with($line, 'data: ')) continue;
                        $json = substr($line, 6);
                        if ($json === '[DONE]') { $gotData = true; return strlen($data); }
                        $obj = json_decode($json, true);
                        $delta = $obj['choices'][0]['delta']['content'] ?? '';
                        if ($delta !== '' && $delta !== null) {
                            $fullResponse .= $delta;
                            szad_sse('token', $delta);
                            $gotData = true;
                        }
                    }
                    return strlen($data);
                },
            ]);
            curl_exec($ch);
            curl_close($ch);
            if ($gotData && $fullResponse !== '') { $streamed = true; break; }
        }
    }

    if ($fullResponse) {
        $pdo->prepare("INSERT INTO yasmin_messages (conversation_id,role,content) VALUES (?,'assistant',?)")->execute([$convId, $fullResponse]);
    }
    if (!$streamed) szad_sse('error', ['msg' => 'لم أتمكن من الاتصال. جرّب مجدداً.']);
    szad_sse('done', '1');
    exit;
}

/* ── Page ─────────────────────────────────────────────────── */
$pTitle = 'Szad AI — منصة الذكاء الاصطناعي العربية للبحث والتفكير العميق | yassota';
$pDesc  = 'Szad AI: مساعد ذكاء اصطناعي عربي احترافي للبحث في الإنترنت، التفكير العميق، التحليل، البرمجة، الترجمة وأكثر. مجاني 100%.';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'Szad AI','url'=>TOOLS_BASE_URL.'/tools/szad/',
    'description'=>$pDesc,'applicationCategory'=>'UtilitiesApplication',
    'operatingSystem'=>'All','inLanguage'=>'ar',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
    'author'=>['@type'=>'Organization','name'=>'yassota','url'=>SITE_URL],
], JSON_UNESCAPED_UNICODE);

tool_head($pTitle, $pDesc, $schema, '#cc0000');
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<style>
/* ── Szad AI — professional dark red theme ─────────────── */
:root{
  --sz-bg:#050509;--sz-surface:#0e0e16;--sz-card:#141420;--sz-border:#1e1e2e;
  --sz-red:#cc0000;--sz-red2:#ff2020;--sz-text:#e8e8f0;--sz-muted:#7878a0;
  --sz-accent:#cc0000;--sz-user-bg:#1a0008;--sz-ai-bg:#0e0e16;
}
*{box-sizing:border-box;margin:0;padding:0}
body{background:var(--sz-bg);color:var(--sz-text);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;overflow:hidden;height:100dvh;padding-top:0!important}
.sz-wrap{display:flex;flex-direction:column;height:100dvh;max-height:100dvh}
/* Header */
.sz-header{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:var(--sz-surface);border-bottom:1px solid var(--sz-border);flex-shrink:0;gap:12px}
.sz-burger{background:none;border:none;color:var(--sz-text);cursor:pointer;padding:6px;border-radius:8px;line-height:0}
.sz-burger:hover{background:var(--sz-border)}
.sz-title-group{display:flex;flex-direction:column;align-items:center;gap:2px}
.sz-brand{font-size:18px;font-weight:800;color:#fff;letter-spacing:1px}
.sz-tagline{font-size:10px;color:var(--sz-muted);text-align:center;max-width:280px}
.sz-badge{display:inline-flex;align-items:center;gap:5px;background:rgba(204,0,0,.15);border:1px solid rgba(204,0,0,.35);color:var(--sz-red2);padding:2px 10px;border-radius:20px;font-size:10px;font-weight:700}
.sz-badge-dot{width:6px;height:6px;border-radius:50%;background:var(--sz-red2);animation:szPulse 2s ease-in-out infinite}
@keyframes szPulse{0%,100%{opacity:1}50%{opacity:.3}}
.sz-avatar{width:36px;height:36px;border-radius:8px;background:var(--sz-red);color:#fff;font-weight:800;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;text-decoration:none;overflow:hidden}
.sz-avatar img{width:100%;height:100%;object-fit:cover;border-radius:8px}
/* Stream area */
.sz-stream{flex:1;overflow-y:auto;padding:20px 16px;scroll-behavior:smooth;display:flex;flex-direction:column;gap:16px}
.sz-stream::-webkit-scrollbar{width:4px}.sz-stream::-webkit-scrollbar-thumb{background:#1e1e2e;border-radius:4px}
/* Messages */
.sz-msg{display:flex;gap:10px;align-items:flex-start;max-width:780px}
.sz-msg.user{flex-direction:row-reverse;align-self:flex-end}
.sz-msg.assistant{align-self:flex-start;width:100%}
.sz-msg-icon{width:32px;height:32px;border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700}
.sz-msg.user .sz-msg-icon{background:var(--sz-red);color:#fff}
.sz-msg.assistant .sz-msg-icon{background:#1a1a2e;color:#cc0000;font-size:16px}
.sz-msg-bubble{background:var(--sz-card);border:1px solid var(--sz-border);border-radius:12px;padding:12px 16px;font-size:14px;line-height:1.75;color:var(--sz-text);word-break:break-word;flex:1}
.sz-msg.user .sz-msg-bubble{background:var(--sz-user-bg);border-color:rgba(204,0,0,.25)}
.sz-msg-bubble h1,.sz-msg-bubble h2,.sz-msg-bubble h3{margin:12px 0 6px;font-size:16px;color:#fff}
.sz-msg-bubble p{margin:6px 0}.sz-msg-bubble ul,.sz-msg-bubble ol{padding-right:20px;margin:6px 0}
.sz-msg-bubble code{background:#1a1a2e;padding:1px 5px;border-radius:4px;font-family:monospace;font-size:13px;color:#e06c75}
.sz-msg-bubble pre{background:#0a0a14;border:1px solid #1e1e2e;border-radius:8px;padding:12px;overflow-x:auto;margin:8px 0}
.sz-msg-bubble pre code{background:none;padding:0;color:#abb2bf}
.sz-msg-bubble strong{color:#fff}
.sz-msg-bubble table{border-collapse:collapse;width:100%;margin:8px 0;font-size:13px}
.sz-msg-bubble td,.sz-msg-bubble th{border:1px solid var(--sz-border);padding:6px 10px;text-align:right}
.sz-msg-bubble th{background:#1a1a2e;color:#fff}
/* Thinking block */
.sz-thinking{background:#0a0a0f;border:1px solid rgba(204,0,0,.2);border-radius:10px;padding:10px 14px;font-size:12px;color:var(--sz-muted);margin-bottom:8px;font-style:italic;line-height:1.6}
.sz-thinking::before{content:'⚙️ التفكير العميق…';display:block;color:#cc0000;font-weight:700;font-style:normal;margin-bottom:4px;font-size:11px}
/* Welcome */
.sz-welcome{text-align:center;padding:40px 20px;color:var(--sz-muted)}
.sz-welcome-icon{font-size:48px;margin-bottom:16px}
.sz-welcome h2{font-size:22px;color:var(--sz-text);font-weight:800;margin-bottom:8px}
.sz-welcome p{font-size:14px;max-width:400px;margin:0 auto;line-height:1.8}
.sz-suggestions{display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin-top:16px}
.sz-sug{background:var(--sz-card);border:1px solid var(--sz-border);color:var(--sz-muted);padding:7px 14px;border-radius:20px;font-size:12px;cursor:pointer;transition:border-color .15s,color .15s}
.sz-sug:hover{border-color:var(--sz-red);color:var(--sz-text)}
/* Composer */
.sz-composer{padding:12px 16px 16px;background:var(--sz-surface);border-top:1px solid var(--sz-border);flex-shrink:0}
.sz-modes{display:flex;gap:8px;margin-bottom:10px}
.sz-mode-btn{background:none;border:1px solid var(--sz-border);color:var(--sz-muted);padding:6px 14px;border-radius:20px;font-size:12px;cursor:pointer;transition:all .15s;display:flex;align-items:center;gap:5px}
.sz-mode-btn.active{border-color:var(--sz-red);color:var(--sz-red2);background:rgba(204,0,0,.08)}
.sz-field{display:flex;gap:10px;align-items:flex-end;background:var(--sz-card);border:1px solid var(--sz-border);border-radius:14px;padding:10px 12px;transition:border-color .15s}
.sz-field:focus-within{border-color:rgba(204,0,0,.4)}
.sz-field textarea{flex:1;background:none;border:none;color:var(--sz-text);font-size:14px;resize:none;max-height:120px;outline:none;font-family:inherit;line-height:1.5}
.sz-field textarea::placeholder{color:var(--sz-muted)}
.sz-send{background:var(--sz-red);color:#fff;border:none;width:36px;height:36px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:opacity .15s}
.sz-send:hover{opacity:.85}.sz-send:disabled{opacity:.4;cursor:default}
.sz-send svg{width:16px;height:16px;fill:currentColor}
.sz-hint{font-size:11px;color:var(--sz-muted);text-align:center;margin-top:8px}
.sz-hint a{color:var(--sz-red)}
/* Cursor */
@keyframes szBlink{0%,100%{opacity:1}50%{opacity:0}}
.sz-cursor{display:inline-block;width:2px;height:14px;background:var(--sz-red2);vertical-align:middle;animation:szBlink 1s ease-in-out infinite;margin-inline-start:2px}
/* Sidebar */
.sz-side{position:fixed;top:0;right:0;width:260px;height:100dvh;background:var(--sz-surface);border-left:1px solid var(--sz-border);z-index:200;transform:translateX(100%);transition:transform .25s;display:flex;flex-direction:column;overflow:hidden}
.sz-side.open{transform:translateX(0)}
.sz-side-header{padding:14px 16px;border-bottom:1px solid var(--sz-border);display:flex;justify-content:space-between;align-items:center;flex-shrink:0}
.sz-side-header h3{font-size:14px;font-weight:700;color:#fff}
.sz-side-close{background:none;border:none;color:var(--sz-muted);cursor:pointer;font-size:18px;padding:4px}
.sz-side-new{display:flex;align-items:center;gap:8px;padding:10px 16px;background:rgba(204,0,0,.1);border-bottom:1px solid var(--sz-border);cursor:pointer;font-size:13px;color:var(--sz-red2);flex-shrink:0}
.sz-side-new:hover{background:rgba(204,0,0,.15)}
.sz-convs{flex:1;overflow-y:auto;padding:8px}
.sz-conv-item{padding:9px 12px;border-radius:8px;cursor:pointer;font-size:12px;color:var(--sz-muted);border:1px solid transparent;display:flex;justify-content:space-between;gap:6px;align-items:center;margin-bottom:2px;transition:all .15s}
.sz-conv-item:hover{background:var(--sz-card);border-color:var(--sz-border);color:var(--sz-text)}
.sz-conv-item.active{background:rgba(204,0,0,.08);border-color:rgba(204,0,0,.2);color:#fff}
.sz-conv-title{flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sz-conv-del{opacity:0;color:var(--sz-muted);font-size:14px;flex-shrink:0;background:none;border:none;cursor:pointer;padding:0 2px}
.sz-conv-item:hover .sz-conv-del{opacity:.6}
.sz-conv-del:hover{opacity:1!important;color:#ef4444}
.sz-side-acct{padding:12px 16px;border-top:1px solid var(--sz-border);flex-shrink:0}
.sz-side-acct a{color:var(--sz-red2);font-size:12px;text-decoration:none}
.sz-scrim{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:190;display:none}
.sz-scrim.show{display:block}
/* SEO content area */
.sz-seo{max-width:760px;margin:0 auto;padding:32px 16px 64px}
.sz-seo h2{font-size:20px;font-weight:800;margin:24px 0 10px}
.sz-seo p,.sz-seo li{font-size:14px;line-height:1.9;color:var(--sz-muted)}
.sz-seo ul{padding-right:20px}
.sz-seo details{border:1px solid var(--sz-border);border-radius:10px;margin-bottom:8px}
.sz-seo summary{padding:12px 16px;cursor:pointer;font-size:14px;font-weight:600;color:var(--sz-text);list-style:none}
.sz-seo details[open] summary{border-bottom:1px solid var(--sz-border)}
.sz-seo .faq-body{padding:12px 16px;font-size:13px;color:var(--sz-muted);line-height:1.8}
@media(min-width:860px){.sz-side{width:280px}.sz-stream{padding:24px 20%}}
@media(max-width:600px){.sz-header{padding:10px 12px}.sz-tagline{display:none}.sz-brand{font-size:16px}}
</style>

<div class="sz-wrap">

  <!-- Scrim -->
  <div class="sz-scrim" id="sz-scrim" onclick="szCloseSide()"></div>

  <!-- Sidebar -->
  <aside class="sz-side" id="sz-side" role="complementary" aria-label="المحادثات السابقة">
    <div class="sz-side-header">
      <h3>Szad AI — محادثاتك</h3>
      <button class="sz-side-close" onclick="szCloseSide()" aria-label="إغلاق">✕</button>
    </div>
    <div class="sz-side-new" onclick="szNew()" role="button" tabindex="0">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      محادثة جديدة
    </div>
    <div class="sz-convs" id="sz-convs"></div>
    <div class="sz-side-acct">
      <?php if ($me): ?>
        <div style="font-size:12px;color:var(--sz-muted);margin-bottom:6px"><?= $h($me['email']) ?></div>
        <form method="post" action="../chat/auth.php?a=logout" style="display:inline">
          <?= csrf_field() ?>
          <button type="submit" style="background:none;border:none;color:var(--sz-red2);font-size:12px;cursor:pointer;padding:0">تسجيل الخروج</button>
        </form>
      <?php else: ?>
        <a href="../chat/auth.php?a=login">تسجيل الدخول لحفظ المحادثات</a>
      <?php endif; ?>
    </div>
  </aside>

  <!-- Header -->
  <header class="sz-header">
    <button class="sz-burger" onclick="szOpenSide()" aria-label="المحادثات">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <div class="sz-title-group">
      <div style="display:flex;align-items:center;gap:8px">
        <div class="sz-badge"><span class="sz-badge-dot"></span>متصل الآن</div>
        <span class="sz-brand">Szad AI</span>
      </div>
      <div class="sz-tagline">منصة الذكاء الاصطناعي العربية للمحادثة والتفكير العميق والبحث الفوري</div>
    </div>
    <?php if ($me): ?>
      <div class="sz-avatar" title="<?= $h($me['email']) ?>">
        <?php if (!empty($me['avatar'])): ?>
          <img src="<?= $h($me['avatar']) ?>" alt="" referrerpolicy="no-referrer">
        <?php else: ?>
          <?= $h(ys_initial($me)) ?>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <a href="../chat/auth.php?a=login" class="sz-avatar" title="تسجيل الدخول" style="text-decoration:none;font-size:11px">دخول</a>
    <?php endif; ?>
  </header>

  <!-- Stream -->
  <div class="sz-stream" id="sz-stream" aria-live="polite">
    <div class="sz-welcome" id="sz-welcome">
      <div class="sz-welcome-icon">🔬</div>
      <h2>مرحباً بك في Szad AI</h2>
      <p>مساعدك للبحث والتفكير العميق والتحليل الشامل. اختر وضع المحادثة ثم ابدأ.</p>
      <div class="sz-suggestions">
        <div class="sz-sug" onclick="szFill('ابحث عن آخر أخبار الذكاء الاصطناعي')">🔍 أخبار AI</div>
        <div class="sz-sug" onclick="szFill('اشرح لي مفهوم التعلم العميق خطوة بخطوة')">🧠 تعلم عميق</div>
        <div class="sz-sug" onclick="szFill('قارن بين GPT-4 و Claude 3.5')">⚖️ مقارنة نماذج</div>
        <div class="sz-sug" onclick="szFill('اكتب كود Python لتحليل بيانات CSV')">💻 كود Python</div>
        <div class="sz-sug" onclick="szFill('ترجم هذه الفقرة للإنجليزية: ')">🌐 ترجمة</div>
        <div class="sz-sug" onclick="szFill('لخّص لي هذا النص: ')">📄 تلخيص</div>
      </div>
    </div>
  </div>

  <!-- Composer -->
  <div class="sz-composer">
    <div class="sz-modes">
      <button class="sz-mode-btn" id="sz-mode-normal" onclick="szMode('normal')">💬 محادثة</button>
      <button class="sz-mode-btn" id="sz-mode-search" onclick="szMode('search')">🔍 بحث في الإنترنت</button>
      <button class="sz-mode-btn" id="sz-mode-think" onclick="szMode('think')">🧠 تفكير عميق</button>
    </div>
    <div class="sz-field">
      <textarea id="sz-input" rows="1" placeholder="اكتب رسالتك هنا…"
                onkeydown="szKey(event)" oninput="szGrow(this)"
                aria-label="رسالتك"></textarea>
      <button class="sz-send" id="sz-send" onclick="szSend()" aria-label="إرسال">
        <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
      </button>
    </div>
    <p class="sz-hint">
      <?php if ($me): ?>
        مرحباً <?= $h($me['name'] ?: 'بك') ?> — محادثاتك محفوظة
      <?php else: ?>
        <a href="../chat/auth.php?a=login">سجّل دخولك</a> لحفظ محادثاتك
      <?php endif; ?>
    </p>
  </div>
</div>

<!-- SEO content (below the fold) -->
<div style="position:relative;z-index:1;background:var(--sz-bg);border-top:1px solid var(--sz-border)">
<div class="sz-seo">
  <h2>Szad AI — منصة الذكاء الاصطناعي العربية الأكثر تطوراً</h2>
  <p>
    Szad AI هي منصة ذكاء اصطناعي عربية متكاملة تجمع بين ثلاث قدرات رئيسية: المحادثة الذكية، البحث الفوري في الإنترنت، والتفكير العميق المنطقي.
    على عكس المساعدات التقليدية، يستطيع Szad AI إجراء بحث فعلي عن أحدث المعلومات والأحداث، ثم تحليلها وتقديمها في سياق منظم ومفهوم.
    سواء كنت تبحث في الأخبار، تحتاج تحليلاً لبيانات، أو تريد فهماً عميقاً لموضوع معقد — Szad AI هو رفيقك المثالي.
  </p>
  <h2>الأوضاع الثلاثة لـ Szad AI</h2>
  <ul>
    <li><strong>💬 وضع المحادثة:</strong> دردشة ذكية متعددة المجالات — إجابات سريعة، مساعدة في الكتابة، الترجمة، البرمجة، وأكثر.</li>
    <li><strong>🔍 البحث في الإنترنت:</strong> يستخدم نماذج AI متصلة بالإنترنت للحصول على أحدث المعلومات والأخبار الآنية.</li>
    <li><strong>🧠 التفكير العميق:</strong> يحلل المشكلة خطوة بخطوة، يقلّب وجهات النظر، ثم يقدم إجابة متكاملة ومعللة.</li>
  </ul>
  <h2>لماذا Szad AI؟</h2>
  <ul>
    <li>🌐 متصل بأحدث نماذج الذكاء الاصطناعي العالمية (GPT-4، Claude، Gemini، Perplexity)</li>
    <li>🎯 يتخصص في المحتوى العربي والمصادر الموثوقة</li>
    <li>🔄 يدعم حفظ المحادثات ومزامنتها عبر الأجهزة عند التسجيل</li>
    <li>⚡ سريع الاستجابة — يستخدم البث المباشر (SSE) للردود الفورية</li>
    <li>🔒 آمن ومجاني — لا يحتاج اشتراكاً للاستخدام الأساسي</li>
  </ul>
  <h2>أسئلة شائعة عن Szad AI</h2>
  <?php $faqs = [
    ['ما الفرق بين Szad AI وياسمين؟','ياسمين مساعدة ودية للمحادثة اليومية باللهجة السورية، بينما Szad AI منصة احترافية للبحث والتحليل بالفصحى ويتميز بقدرات البحث الإنترنت والتفكير المنطقي.'],
    ['هل Szad AI يبحث فعلاً في الإنترنت؟','نعم، في وضع "البحث في الإنترنت" يستخدم نماذج Perplexity المتصلة بالإنترنت لجلب أحدث المعلومات من مصادر موثوقة.'],
    ['هل Szad AI مجاني؟','الاستخدام الأساسي مجاني تماماً. للحصول على حدود رسائل أعلى ونماذج أقوى، يمكنك الاشتراك في باقة ياسمين التي تشمل أيضاً Szad AI.'],
    ['كيف يعمل وضع التفكير العميق؟','يستخدم نماذج AI متخصصة في التفكير المنطقي (مثل o1 و Claude) مع تعليمات خاصة لتحليل المشكلة من زوايا متعددة قبل تقديم الإجابة النهائية.'],
    ['هل يمكنني حفظ محادثاتي؟','نعم، بالتسجيل في yassota يمكنك الوصول لمحادثاتك من أي جهاز. المحادثات بدون تسجيل تُحفظ مؤقتاً في المتصفح.'],
    ['ما لغات البرمجة التي يدعمها Szad AI؟','يدعم جميع لغات البرمجة الشائعة: Python، JavaScript، PHP، Java، C++، SQL، وغيرها. يمكنه كتابة، شرح، وتصحيح الأكواد.'],
  ]; ?>
  <?php foreach ($faqs as [$q, $a]): ?>
  <details>
    <summary><?= $h($q) ?></summary>
    <div class="faq-body"><?= $h($a) ?></div>
  </details>
  <?php endforeach; ?>
</div>
</div>

<script>
/* ── State ─────────────────────────────────────────────── */
var szConvId = 0, szMode_ = 'normal', szStreaming = false;

/* ── Init ──────────────────────────────────────────────── */
szModeSet('normal');
szLoadConvs();

/* ── Sidebar ────────────────────────────────────────────── */
function szOpenSide() { document.getElementById('sz-side').classList.add('open'); document.getElementById('sz-scrim').classList.add('show'); szLoadConvs(); }
function szCloseSide() { document.getElementById('sz-side').classList.remove('open'); document.getElementById('sz-scrim').classList.remove('show'); }

/* ── Mode ───────────────────────────────────────────────── */
function szMode(m) { szMode_ = m; szModeSet(m); }
function szModeSet(m) {
  ['normal','search','think'].forEach(function(t){
    var b = document.getElementById('sz-mode-'+t);
    if (b) b.classList.toggle('active', t === m);
  });
  var inp = document.getElementById('sz-input');
  var hints = {normal:'اكتب رسالتك هنا…', search:'ابحث عن أي شيء…', think:'اطرح مشكلة للتحليل العميق…'};
  if (inp) inp.placeholder = hints[m] || hints.normal;
}

/* ── New conversation ─────────────────────────────────── */
function szNew() {
  szConvId = 0;
  document.getElementById('sz-stream').innerHTML = document.getElementById('sz-welcome') ? '' :
    '<div class="sz-welcome" id="sz-welcome"><div class="sz-welcome-icon">🔬</div><h2>محادثة جديدة</h2><p>اختر الوضع وابدأ.</p></div>';
  var items = document.querySelectorAll('.sz-conv-item');
  items.forEach(function(i){i.classList.remove('active')});
  szCloseSide();
  document.getElementById('sz-input').focus();
}

/* ── Load conversations ─────────────────────────────── */
function szLoadConvs() {
  var box = document.getElementById('sz-convs');
  fetch('index.php?ajax=szad_convs').then(function(r){return r.json();}).then(function(rows){
    if (!rows.length) { box.innerHTML = '<div style="padding:12px;font-size:12px;color:var(--sz-muted);text-align:center">لا توجد محادثات بعد</div>'; return; }
    box.innerHTML = rows.map(function(c){
      return '<div class="sz-conv-item'+(c.id==szConvId?' active':'')+'" id="szconv-'+c.id+'" onclick="szLoadConv('+c.id+')">'
        +'<span class="sz-conv-title">'+szEsc(c.title||'محادثة')+'</span>'
        +'<button class="sz-conv-del" onclick="event.stopPropagation();szDel('+c.id+')" title="حذف" aria-label="حذف">×</button>'
        +'</div>';
    }).join('');
  }).catch(function(){});
}

/* ── Load a conversation ─────────────────────────────── */
function szLoadConv(id) {
  szConvId = id;
  document.querySelectorAll('.sz-conv-item').forEach(function(el){el.classList.remove('active')});
  var item = document.getElementById('szconv-'+id);
  if (item) item.classList.add('active');
  szCloseSide();
  fetch('index.php?ajax=szad_msgs&id='+id).then(function(r){return r.json();}).then(function(msgs){
    var s = document.getElementById('sz-stream');
    s.innerHTML = '';
    msgs.forEach(function(m){szAppend(m.role, m.content);});
    s.scrollTop = s.scrollHeight;
  });
}

/* ── Delete conversation ─────────────────────────────── */
function szDel(id) {
  if (!confirm('حذف هذه المحادثة؟')) return;
  var fd = new FormData(); fd.append('id', id);
  fetch('index.php?ajax=szad_del', {method:'POST',body:fd}).then(function(){
    if (szConvId === id) { szConvId = 0; document.getElementById('sz-stream').innerHTML = ''; }
    szLoadConvs();
  });
}

/* ── Render messages ─────────────────────────────────── */
function szEsc(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
function szAppend(role, text) {
  var s = document.getElementById('sz-stream');
  var welcome = document.getElementById('sz-welcome');
  if (welcome) welcome.remove();
  var div = document.createElement('div');
  div.className = 'sz-msg ' + (role === 'user' ? 'user' : 'assistant');
  var icon = document.createElement('div');
  icon.className = 'sz-msg-icon';
  icon.textContent = role === 'user' ? 'أنت' : '⚡';
  var bubble = document.createElement('div');
  bubble.className = 'sz-msg-bubble';
  if (role === 'assistant') {
    bubble.innerHTML = szMd(text || '');
  } else {
    bubble.textContent = text;
  }
  div.appendChild(icon);
  div.appendChild(bubble);
  s.appendChild(div);
  s.scrollTop = s.scrollHeight;
  return {div:div, bubble:bubble};
}

/* ── Markdown renderer (subset) ─────────────────────── */
function szMd(t) {
  t = t.replace(/</g,'&lt;').replace(/>/g,'&gt;');
  // Code blocks
  t = t.replace(/```(\w*)\n?([\s\S]*?)```/g, function(_,lang,code){return '<pre><code>'+code+'</code></pre>';});
  // Inline code
  t = t.replace(/`([^`]+)`/g,'<code>$1</code>');
  // Bold
  t = t.replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>');
  t = t.replace(/__(.+?)__/g,'<strong>$1</strong>');
  // Italic
  t = t.replace(/\*(.+?)\*/g,'<em>$1</em>');
  // Headings
  t = t.replace(/^### (.+)$/gm,'<h3>$1</h3>');
  t = t.replace(/^## (.+)$/gm,'<h2>$1</h2>');
  t = t.replace(/^# (.+)$/gm,'<h1>$1</h1>');
  // Lists
  t = t.replace(/^[\-\*] (.+)$/gm,'<li>$1</li>');
  t = t.replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>');
  t = t.replace(/^\d+\. (.+)$/gm,'<li>$1</li>');
  // Paragraphs
  t = t.replace(/\n\n+/g,'</p><p>');
  t = t.replace(/\n/g,'<br>');
  return '<p>' + t + '</p>';
}

/* ── Send ────────────────────────────────────────────── */
function szSend() {
  if (szStreaming) return;
  var inp = document.getElementById('sz-input');
  var msg = inp.value.trim();
  if (!msg) return;
  inp.value = ''; szGrow(inp);
  szStreaming = true;
  document.getElementById('sz-send').disabled = true;

  szAppend('user', msg);

  var el = szAppend('assistant', '');
  var bubble = el.bubble;
  var accumulated = '';
  bubble.innerHTML = '<span class="sz-cursor"></span>';

  var xhr = new XMLHttpRequest();
  var lastPos = 0, szBuf = '';
  xhr.open('POST', 'index.php?ajax=szad_chat', true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.onprogress = function() {
    var newText = xhr.responseText.substring(lastPos);
    lastPos = xhr.responseText.length;
    szBuf += newText;
    var evBlocks = szBuf.split('\n\n');
    szBuf = evBlocks.pop() || '';
    evBlocks.forEach(function(block) {
      var evName = 'message', data = '';
      block.split('\n').forEach(function(l) {
        if (l.startsWith('event: ')) evName = l.slice(7).trim();
        if (l.startsWith('data: '))  data  = l.slice(6);
      });
      if (evName === 'token') {
        accumulated += data;
        bubble.innerHTML = szMd(accumulated) + '<span class="sz-cursor"></span>';
        document.getElementById('sz-stream').scrollTop = 9999;
      }
      if (evName === 'conv')  { try { var d = JSON.parse(data); if (d.id) szConvId = d.id; } catch(e){} }
      if (evName === 'done')  { szStreaming = false; document.getElementById('sz-send').disabled = false; bubble.innerHTML = szMd(accumulated); szLoadConvs(); }
      if (evName === 'error') { szStreaming = false; document.getElementById('sz-send').disabled = false; try { var e = JSON.parse(data); bubble.innerHTML = '<span style="color:#ef4444">' + szEsc(e.msg || 'خطأ في الاتصال') + '</span>'; } catch(ex){} }
    });
  };
  xhr.onloadend = function() {
    szStreaming = false;
    document.getElementById('sz-send').disabled = false;
    if (accumulated && bubble.querySelector('.sz-cursor')) bubble.innerHTML = szMd(accumulated);
    szLoadConvs();
  };
  xhr.send('msg=' + encodeURIComponent(msg) + '&conv_id=' + szConvId + '&mode=' + szMode_);
}

/* ── Helpers ─────────────────────────────────────────── */
function szGrow(ta) {
  ta.style.height = 'auto';
  ta.style.height = Math.min(ta.scrollHeight, 120) + 'px';
}
function szKey(e) {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); szSend(); }
}
function szFill(text) {
  var inp = document.getElementById('sz-input');
  inp.value = text;
  inp.focus();
  szGrow(inp);
}
</script>

<?php tool_footer(); ?>
