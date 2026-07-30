<?php
require_once dirname(__DIR__) . '/_base.php';

/* ── SSE chat endpoint ───────────────────────────────────────────── */
if (isset($_GET['action']) && $_GET['action'] === 'chat') {
    @set_time_limit(90);
    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    if (ob_get_level()) ob_end_clean();

    function sse(string $event, array $d): void {
        echo "event: {$event}\ndata: " . json_encode($d, JSON_UNESCAPED_UNICODE) . "\n\n";
        if (ob_get_level()) ob_flush();
        flush();
    }

    $input   = json_decode(file_get_contents('php://input'), true) ?: [];
    $message = trim($input['message'] ?? '');
    $history = is_array($input['history'] ?? null) ? $input['history'] : [];

    if (!$message) { sse('error', ['msg' => 'الرسالة فارغة']); exit; }

    /* Load main site config for OpenRouter keys ─────────── */
    $mainCfg = dirname(dirname(__DIR__)) . '/config.php';
    if (!file_exists($mainCfg)) {
        sse('error', ['msg' => 'الخدمة غير متاحة حالياً']); exit;
    }
    require_once $mainCfg;

    $keys = openrouter_keys(get_cfg($pdo, 'openrouter_key'));
    if (!$keys) {
        sse('error', ['msg' => 'لم تُضف مفاتيح OpenRouter بعد — أضفها من إعدادات الموقع']); exit;
    }

    $models = build_model_rotation($pdo, false);
    if (!$models) $models = ['openrouter/free'];

    /* System prompt ─────────────────────────────────────── */
    $sys = 'أنت ياسمين، مساعدة ذكاء اصطناعي سورية ذكية ودودة. اسمك مأخوذ من زهرة الياسمين، الزهرة الوطنية لسوريا. '
         . 'تتحدثين العربية الفصيحة بطلاقة مع لمسة شامية دافئة. تساعدين في كل المجالات: العلوم والتاريخ والثقافة السورية والطبخ الشامي والتقنية والأدب والمزيد. '
         . 'ردودك مباشرة ومفيدة وودية. تستخدمين صيغة المؤنث عند الحديث عن نفسك. لا تُطيلين دون داعٍ — أجيبي باختصار مناسب ودقيق.';

    /* Build messages array ─────────────────────────────── */
    $messages = [['role' => 'system', 'content' => $sys]];
    foreach (array_slice($history, -20) as $h) { // keep last 20 turns
        $role = ($h['role'] === 'user') ? 'user' : 'assistant';
        $messages[] = ['role' => $role, 'content' => (string)($h['content'] ?? '')];
    }
    $messages[] = ['role' => 'user', 'content' => $message];

    /* Try each key × model until one streams successfully ─ */
    $streamed = false;
    $tried    = 0;
    $maxTry   = min(6, count($keys) * count($models));

    outer: foreach ($keys as $key) {
        foreach ($models as $modelEntry) {
            if ($tried >= $maxTry) break 2;
            $tried++;
            $mId = is_array($modelEntry) ? ($modelEntry['id'] ?? 'openrouter/free') : $modelEntry;

            $payload = json_encode([
                'model'      => $mId,
                'messages'   => $messages,
                'stream'     => true,
                'max_tokens' => 1500,
            ], JSON_UNESCAPED_UNICODE);

            $buffer  = '';
            $gotData = false;
            $errHttp = 0;

            $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_POST        => true,
                CURLOPT_POSTFIELDS  => $payload,
                CURLOPT_HTTPHEADER  => [
                    'Authorization: Bearer ' . $key,
                    'Content-Type: application/json',
                    'HTTP-Referer: https://yassota.com',
                    'X-Title: ياسمين - yassota',
                ],
                CURLOPT_TIMEOUT => 60,
                CURLOPT_WRITEFUNCTION => function($ch, $data) use (&$buffer, &$gotData) {
                    $buffer .= $data;
                    // Process complete lines
                    while (($pos = strpos($buffer, "\n")) !== false) {
                        $line   = substr($buffer, 0, $pos);
                        $buffer = substr($buffer, $pos + 1);
                        $line   = trim($line);
                        if (!str_starts_with($line, 'data: ')) continue;
                        $json = substr($line, 6);
                        if ($json === '[DONE]') {
                            echo "event: done\ndata: {}\n\n";
                            if (ob_get_level()) ob_flush();
                            flush();
                            $gotData = true;
                            continue;
                        }
                        $parsed = json_decode($json, true);
                        $delta  = $parsed['choices'][0]['delta']['content'] ?? '';
                        if ($delta !== '') {
                            $gotData = true;
                            echo "event: chunk\ndata: " . json_encode(['t' => $delta], JSON_UNESCAPED_UNICODE) . "\n\n";
                            if (ob_get_level()) ob_flush();
                            flush();
                        }
                    }
                    return strlen($data);
                },
            ]);

            $errHttp = curl_getinfo(curl_exec($ch) === false ? $ch : $ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($gotData) {
                $streamed = true;
                break 2;
            }
        }
    }

    if (!$streamed) {
        sse('error', ['msg' => 'تعذّر الاتصال بخدمة الذكاء الاصطناعي، جرّب مرة أخرى.']);
    }
    exit;
}

/* ── Page ─────────────────────────────────────────────────────────── */
$schema = json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'WebApplication',
    'name'     => 'ياسمين — مساعد ذكاء اصطناعي سوري',
    'url'      => TOOLS_BASE_URL . '/tools/chat/',
    'description' => 'تحدّث مع ياسمين، مساعدتك الذكية السورية — تجيب على أسئلتك بالعربية في كل المجالات مجاناً.',
    'applicationCategory' => 'UtilitiesApplication',
    'operatingSystem' => 'All',
    'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
]);
tool_head('ياسمين — مساعد ذكاء اصطناعي سوري | yassota', 'تحدّث مع ياسمين، مساعدتك الذكية السورية — تجيب على أسئلتك بالعربية مجاناً في كل المجالات.', $schema, '#CE1126');
tool_header();
?>
<style>
:root {
  --sy-red:   #CE1126;
  --sy-dark:  #1a0a0f;
  --sy-gold:  #d4a017;
  --sy-green: #007A3D;
  --chat-bg:  #faf7f2;
  --bubble-ai: #ffffff;
  --bubble-user: var(--sy-red);
  --input-bg:  #fff;
  --shadow:    0 2px 16px rgba(0,0,0,.10);
}
.chat-wrap {
  max-width: 720px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  height: calc(100vh - 56px - 48px);
  padding: 0 0 16px;
}
/* Chat header */
.chat-header {
  background: linear-gradient(135deg, var(--sy-red) 0%, #8b0000 100%);
  color: #fff;
  border-radius: 18px 18px 0 0;
  padding: 18px 20px;
  display: flex;
  align-items: center;
  gap: 14px;
}
.chat-avatar {
  width: 48px;
  height: 48px;
  background: rgba(255,255,255,.18);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  flex-shrink: 0;
  border: 2px solid rgba(255,255,255,.4);
}
.chat-header-info h2 { font-size: 17px; font-weight: 800; margin-bottom: 2px; }
.chat-header-info p  { font-size: 12px; opacity: .82; }
.status-dot {
  width: 9px; height: 9px;
  background: #4ade80;
  border-radius: 50%;
  display: inline-block;
  margin-left: 5px;
  box-shadow: 0 0 6px #4ade80;
}
/* Messages area */
.chat-messages {
  flex: 1;
  overflow-y: auto;
  background: var(--chat-bg);
  padding: 16px 18px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  scroll-behavior: smooth;
}
.chat-messages::-webkit-scrollbar { width: 5px; }
.chat-messages::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
.msg-row {
  display: flex;
  gap: 10px;
  align-items: flex-end;
}
.msg-row.user { flex-direction: row-reverse; }
.msg-avatar {
  width: 32px; height: 32px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 15px;
  flex-shrink: 0;
}
.msg-avatar.ai   { background: linear-gradient(135deg, var(--sy-red), #8b0000); color: #fff; }
.msg-avatar.user { background: #e2e8f0; }
.bubble {
  max-width: 75%;
  padding: 12px 16px;
  border-radius: 18px;
  font-size: 14px;
  line-height: 1.7;
  white-space: pre-wrap;
  word-break: break-word;
}
.bubble.ai {
  background: var(--bubble-ai);
  color: #1e293b;
  border-radius: 4px 18px 18px 18px;
  box-shadow: var(--shadow);
  border: 1px solid #e2e8f0;
}
.bubble.user {
  background: var(--bubble-user);
  color: #fff;
  border-radius: 18px 4px 18px 18px;
}
.bubble-time {
  font-size: 10px;
  color: #94a3b8;
  margin-top: 4px;
  padding: 0 4px;
  align-self: flex-end;
}
.typing-dots {
  display: flex; gap: 5px; align-items: center; padding: 4px 0;
}
.typing-dots span {
  width: 7px; height: 7px;
  background: #94a3b8;
  border-radius: 50%;
  animation: blink 1.2s infinite;
}
.typing-dots span:nth-child(2) { animation-delay: .2s; }
.typing-dots span:nth-child(3) { animation-delay: .4s; }
@keyframes blink { 0%,80%,100%{opacity:.3} 40%{opacity:1} }
/* Input area */
.chat-input-area {
  background: var(--input-bg);
  border-top: 1px solid #e2e8f0;
  padding: 12px 16px;
  border-radius: 0 0 18px 18px;
  display: flex;
  gap: 10px;
  align-items: flex-end;
  box-shadow: 0 -2px 12px rgba(0,0,0,.05);
}
#chat-input {
  flex: 1;
  border: 1.5px solid #e2e8f0;
  border-radius: 14px;
  padding: 11px 16px;
  font-size: 14px;
  font-family: inherit;
  resize: none;
  outline: none;
  direction: rtl;
  max-height: 120px;
  overflow-y: auto;
  line-height: 1.5;
  transition: border-color .2s;
  background: #f8fafc;
}
#chat-input:focus { border-color: var(--sy-red); background: #fff; }
#send-btn {
  width: 44px; height: 44px;
  background: var(--sy-red);
  border: none;
  border-radius: 12px;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  transition: all .2s;
}
#send-btn:hover { background: #a80e1e; transform: scale(1.05); }
#send-btn:disabled { background: #94a3b8; transform: none; cursor: not-allowed; }
#send-btn svg { width: 18px; height: 18px; fill: #fff; }
.welcome-banner {
  background: linear-gradient(135deg, #fff5f5, #fff);
  border: 1.5px solid #fecaca;
  border-radius: 14px;
  padding: 16px 18px;
  display: flex;
  gap: 14px;
  align-items: flex-start;
  margin: 0;
}
.welcome-banner .icon { font-size: 28px; flex-shrink: 0; }
.welcome-banner h3   { font-size: 15px; font-weight: 700; color: #7f1d1d; margin-bottom: 4px; }
.welcome-banner p    { font-size: 13px; color: #64748b; line-height: 1.7; }
.quick-chips {
  display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px;
}
.quick-chip {
  background: #fff;
  border: 1.5px solid #fca5a5;
  border-radius: 20px;
  padding: 6px 14px;
  font-size: 12px;
  cursor: pointer;
  color: var(--sy-red);
  font-weight: 600;
  transition: all .2s;
}
.quick-chip:hover { background: var(--sy-red); color: #fff; border-color: var(--sy-red); }
.t-main { padding: 16px 16px 0; }
@media(max-width:600px) {
  .chat-wrap { height: calc(100dvh - 56px - 8px); }
  .bubble { max-width: 85%; }
  .t-main { padding: 8px 8px 0; }
}
</style>
<main class="t-main">
<div class="chat-wrap">
  <!-- Header -->
  <div class="chat-header">
    <div class="chat-avatar">🌸</div>
    <div class="chat-header-info">
      <h2>ياسمين <span class="status-dot"></span></h2>
      <p>مساعدة ذكاء اصطناعي سورية · تعمل بـ OpenRouter AI</p>
    </div>
  </div>

  <!-- Messages -->
  <div class="chat-messages" id="chat-messages">
    <!-- Welcome -->
    <div class="welcome-banner">
      <span class="icon">🌸</span>
      <div>
        <h3>أهلاً وسهلاً! أنا ياسمين 🇸🇾</h3>
        <p>مساعدتك الذكية السورية — تحدّثي معي بالعربية في أي موضوع: علوم، تاريخ، طبخ شامي، تقنية، ثقافة، أو أي شيء تريد!</p>
        <div class="quick-chips">
          <button class="quick-chip" onclick="send('أخبريني عن دمشق')">🏛️ عن دمشق</button>
          <button class="quick-chip" onclick="send('وصفة كبة حلبية')">🍽️ كبة حلبية</button>
          <button class="quick-chip" onclick="send('ما هو سعر الدولار اليوم؟')">💵 سعر الدولار</button>
          <button class="quick-chip" onclick="send('اشرح لي الذكاء الاصطناعي ببساطة')">🤖 الذكاء الاصطناعي</button>
          <button class="quick-chip" onclick="send('أنشودة وطنية سورية')">🎶 أنشودة سورية</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Input -->
  <div class="chat-input-area">
    <textarea id="chat-input" placeholder="اكتب رسالتك لياسمين…" rows="1" onkeydown="handleKey(event)" oninput="autoResize(this)"></textarea>
    <button id="send-btn" onclick="sendMsg()" title="إرسال">
      <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
    </button>
  </div>
</div>
<?php tool_ad(); ?>
</main>

<script>
const msgs = document.getElementById('chat-messages');
const input = document.getElementById('chat-input');
const sendBtn = document.getElementById('send-btn');
let history = [];
let streaming = false;

function autoResize(el) {
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

function now() {
  return new Date().toLocaleTimeString('ar-SA', {hour:'2-digit', minute:'2-digit'});
}

function addMsg(role, text, typing = false) {
  const row = document.createElement('div');
  row.className = 'msg-row ' + (role === 'user' ? 'user' : 'ai');

  const avatar = document.createElement('div');
  avatar.className = 'msg-avatar ' + (role === 'user' ? 'user' : 'ai');
  avatar.textContent = role === 'user' ? '👤' : '🌸';

  const wrap = document.createElement('div');

  const bubble = document.createElement('div');
  bubble.className = 'bubble ' + (role === 'user' ? 'user' : 'ai');

  if (typing) {
    bubble.innerHTML = '<div class="typing-dots"><span></span><span></span><span></span></div>';
  } else {
    bubble.textContent = text;
  }

  const time = document.createElement('div');
  time.className = 'bubble-time';
  time.textContent = now();

  wrap.appendChild(bubble);
  wrap.appendChild(time);
  row.appendChild(avatar);
  row.appendChild(wrap);
  msgs.appendChild(row);
  msgs.scrollTop = msgs.scrollHeight;
  return bubble;
}

function send(text) {
  input.value = text;
  sendMsg();
}

async function sendMsg() {
  const text = input.value.trim();
  if (!text || streaming) return;

  addMsg('user', text);
  history.push({role: 'user', content: text});
  input.value = '';
  input.style.height = 'auto';
  sendBtn.disabled = true;
  streaming = true;

  // Typing indicator
  const typingBubble = addMsg('ai', '', true);

  try {
    const resp = await fetch('?action=chat', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({message: text, history: history.slice(0, -1)}),
    });

    if (!resp.ok || !resp.body) throw new Error('Network error');

    const reader = resp.body.getReader();
    const decoder = new TextDecoder();
    let aiText = '';
    let buffer = '';

    typingBubble.innerHTML = '';

    while (true) {
      const {done, value} = await reader.read();
      if (done) break;
      buffer += decoder.decode(value, {stream: true});

      const lines = buffer.split('\n');
      buffer = lines.pop();

      for (const line of lines) {
        const trim = line.trim();
        if (!trim || trim.startsWith(':')) continue;

        let event = 'message', data = '';
        if (trim.startsWith('event:')) {
          event = trim.slice(6).trim();
        } else if (trim.startsWith('data:')) {
          data = trim.slice(5).trim();
          if (event === 'chunk' || true) {
            try {
              const parsed = JSON.parse(data);
              if (parsed.t !== undefined) {
                aiText += parsed.t;
                typingBubble.textContent = aiText;
                msgs.scrollTop = msgs.scrollHeight;
              } else if (parsed.error) {
                typingBubble.textContent = '⚠️ ' + parsed.error;
              }
            } catch(e) {}
          }
        }
      }
    }

    if (aiText) {
      history.push({role: 'assistant', content: aiText});
    }

  } catch (e) {
    typingBubble.textContent = '⚠️ حدث خطأ في الاتصال، جرّب مرة أخرى.';
  }

  sendBtn.disabled = false;
  streaming = false;
  input.focus();
  msgs.scrollTop = msgs.scrollHeight;
}

function handleKey(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMsg();
  }
}
</script>
<?php tool_footer(); ?>
