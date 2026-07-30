<section class="page-hero small">
  <div class="container">
    <nav class="breadcrumb"><a href="/">الرئيسية</a> <span>›</span> <span>أدوات AI</span></nav>
    <h1>🤖 أدوات الذكاء الاصطناعي</h1>
    <p>أفضل أدوات الذكاء الاصطناعي المجانية للكتابة والترجمة وتوليد الصور والمزيد</p>
  </div>
</section>

<div class="container page-body">

  <!-- AI Tools Grid -->
  <section class="card" aria-labelledby="h-ai-tools">
    <h2 id="h-ai-tools">🛠️ الأدوات المتاحة</h2>
    <div class="tools-grid">
      <a href="#chatgpt" class="tool-card cat-ai" id="chatgpt">
        <div class="tc-icon">💬</div>
        <div class="tc-body"><h3>محادثة ذكية (ChatGPT)</h3><p>اكتب رسالتك واحصل على إجابات ذكية بالعربية والإنجليزية</p></div>
        <span class="tc-tag">مجاني</span>
      </a>
      <a href="#translate" class="tool-card cat-ai" id="translate">
        <div class="tc-icon">🌐</div>
        <div class="tc-body"><h3>الترجمة الفورية</h3><p>ترجمة فورية دقيقة بين العربية والإنجليزية والفرنسية</p></div>
        <span class="tc-tag">مجاني</span>
      </a>
      <a href="#summary" class="tool-card cat-ai" id="summary">
        <div class="tc-icon">📝</div>
        <div class="tc-body"><h3>تلخيص النصوص</h3><p>لخص أي نص طويل في نقاط واضحة وموجزة</p></div>
        <span class="tc-tag">مجاني</span>
      </a>
      <a href="#image" class="tool-card cat-ai" id="image">
        <div class="tc-icon">🎨</div>
        <div class="tc-body"><h3>توليد الصور</h3><p>أنشئ صوراً احترافية بالوصف النصي عبر AI</p></div>
        <span class="tc-tag">مجاني</span>
      </a>
      <a href="#grammar" class="tool-card cat-ai">
        <div class="tc-icon">✍️</div>
        <div class="tc-body"><h3>تصحيح اللغة</h3><p>صحح قواعد اللغة العربية والنصوص</p></div>
        <span class="tc-tag">مجاني</span>
      </a>
      <a href="#keywords" class="tool-card cat-ai">
        <div class="tc-icon">🔑</div>
        <div class="tc-body"><h3>استخراج الكلمات المفتاحية</h3><p>استخرج أهم الكلمات من أي نص</p></div>
        <span class="tc-tag">مجاني</span>
      </a>
    </div>
  </section>

  <!-- Chat tool -->
  <section class="card" id="chatgpt-tool" aria-labelledby="h-chat">
    <h2 id="h-chat">💬 محادثة مع الذكاء الاصطناعي</h2>
    <p style="color:var(--c-text2);font-size:14px;margin-bottom:1rem">اطرح أي سؤال بالعربية أو الإنجليزية</p>
    <div id="chatMessages" style="min-height:180px;max-height:400px;overflow-y:auto;background:var(--c-bg2);border:1px solid var(--c-border);border-radius:var(--radius-lg);padding:1rem;margin-bottom:1rem;display:flex;flex-direction:column;gap:.75rem">
      <div class="ai-msg assistant">أهلاً! أنا مساعدك الذكي. كيف يمكنني مساعدتك اليوم؟ يمكنك سؤالي عن أي شيء — الأسعار، الحسابات، المعلومات العامة، وأكثر.</div>
    </div>
    <div style="display:flex;gap:.5rem">
      <textarea id="chatInput" rows="2" placeholder="اكتب رسالتك هنا..." style="resize:vertical;flex:1"></textarea>
      <div style="display:flex;flex-direction:column;gap:.5rem">
        <button onclick="sendChat()" class="btn-primary" style="flex:1">إرسال</button>
        <button onclick="clearChat()" class="btn-outline" style="font-size:12px">مسح</button>
      </div>
    </div>
    <div id="chatInfo" style="font-size:11px;color:var(--c-text3);margin-top:.5rem">المحادثات لا تُحفظ على الخادم · للخصوصية استخدم ChatGPT أو Claude مباشرةً</div>
  </section>

  <!-- Text tools -->
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.25rem">

    <!-- Translate -->
    <section class="card" id="translate-tool" aria-labelledby="h-trans">
      <h2 id="h-trans">🌐 الترجمة الفورية</h2>
      <div style="display:flex;gap:.5rem;margin-bottom:.5rem">
        <select id="transFrom" style="flex:1">
          <option value="auto">الكشف التلقائي</option>
          <option value="ar" selected>العربية</option>
          <option value="en">الإنجليزية</option>
          <option value="fr">الفرنسية</option>
          <option value="de">الألمانية</option>
          <option value="tr">التركية</option>
        </select>
        <button onclick="swapTranslate()" class="swap-btn">⇄</button>
        <select id="transTo" style="flex:1">
          <option value="en" selected>الإنجليزية</option>
          <option value="ar">العربية</option>
          <option value="fr">الفرنسية</option>
          <option value="de">الألمانية</option>
          <option value="tr">التركية</option>
        </select>
      </div>
      <textarea id="transInput" rows="4" placeholder="اكتب النص للترجمة..." style="margin-bottom:.5rem"></textarea>
      <button onclick="doTranslate()" class="btn-primary" style="width:100%">ترجمة</button>
      <div id="transOutput" hidden style="margin-top:.75rem;padding:1rem;background:var(--c-bg2);border:1px solid var(--c-border);border-radius:var(--radius);font-size:14px;line-height:1.7;min-height:80px"></div>
      <div style="font-size:11px;color:var(--c-text3);margin-top:.5rem">مدعوم بـ MyMemory API المجانية</div>
    </section>

    <!-- Summary -->
    <section class="card" id="summary-tool" aria-labelledby="h-summ">
      <h2 id="h-summ">📝 تلخيص النصوص</h2>
      <textarea id="summaryInput" rows="5" placeholder="الصق النص الطويل هنا..." style="margin-bottom:.5rem"></textarea>
      <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem;flex-wrap:wrap">
        <label style="margin:0;font-size:13px">نسبة التلخيص:
          <select id="summaryRatio" style="width:auto;display:inline-block;margin-right:.5rem">
            <option value="0.3">30%</option>
            <option value="0.5" selected>50%</option>
            <option value="0.7">70%</option>
          </select>
        </label>
        <button onclick="doSummary()" class="btn-primary">لخّص</button>
      </div>
      <div id="summaryOutput" hidden style="padding:1rem;background:var(--c-bg2);border:1px solid var(--c-border);border-radius:var(--radius);font-size:14px;line-height:1.8"></div>
    </section>

  </div>

  <!-- AI tools links -->
  <section class="card" aria-labelledby="h-ai-links">
    <h2 id="h-ai-links">🔗 روابط أدوات AI الخارجية</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.75rem">
      <?php
      $aiLinks = [
        ['ChatGPT',    'https://chat.openai.com',          '💬', 'الدردشة بالذكاء الاصطناعي'],
        ['Claude',     'https://claude.ai',                '🤖', 'مساعد Anthropic الذكي'],
        ['Gemini',     'https://gemini.google.com',        '🔷', 'نموذج Google المتقدم'],
        ['DALL·E',     'https://openai.com/dall-e',        '🎨', 'توليد الصور بـ OpenAI'],
        ['Midjourney', 'https://midjourney.com',           '🖼️', 'أفضل مولد صور AI'],
        ['Sora',       'https://sora.com',                 '🎬', 'توليد الفيديو بـ AI'],
        ['Gamma',      'https://gamma.app',                '📊', 'إنشاء عروض بـ AI'],
        ['Perplexity', 'https://perplexity.ai',            '🔍', 'بحث بالذكاء الاصطناعي'],
      ];
      foreach ($aiLinks as [$name, $url, $ico, $desc]): ?>
      <a href="<?= h($url) ?>" target="_blank" rel="noopener noreferrer" class="sub-card">
        <div class="sub-icon"><?= $ico ?></div>
        <div class="sub-info">
          <strong><?= h($name) ?></strong>
          <span><?= h($desc) ?></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>

</div>

<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"SoftwareApplication",
  "name":"أدوات الذكاء الاصطناعي — <?= h(SITE_NAME) ?>",
  "applicationCategory":"UtilitiesApplication",
  "description":"أفضل أدوات الذكاء الاصطناعي المجانية: ترجمة، تلخيص، محادثة، توليد صور",
  "url":"<?= h(base_url('ai')) ?>",
  "inLanguage":"ar",
  "offers":{"@type":"Offer","price":"0","priceCurrency":"SAR"}
}
</script>
<script>
// ── Inline AI chat (local, no API key needed — just demonstrating UX) ──
const chatMsgs = document.getElementById('chatMessages');
const autoReplies = {
  'مرحبا': 'أهلاً وسهلاً! كيف يمكنني مساعدتك اليوم؟',
  'الذهب': 'يمكنك الاطلاع على أحدث أسعار الذهب في صفحة <a href="/gold">أسعار الذهب</a>.',
  'الصرف': 'تفضل بزيارة صفحة <a href="/exchange">أسعار الصرف</a> لمعرفة أحدث الأسعار.',
  'pdf': 'نعم! لدينا <a href="/pdf">أدوات PDF مجانية</a> للدمج والضغط والتحويل.',
  'شمسية': 'استخدم <a href="/solar">حاسبة الطاقة الشمسية</a> لمعرفة عدد الألواح والتكلفة.',
};
function addMsg(text, role) {
  const d = document.createElement('div');
  d.className = 'ai-msg ' + role;
  d.style.cssText = 'padding:.65rem 1rem;border-radius:1rem;max-width:85%;font-size:14px;line-height:1.6;' +
    (role === 'user' ? 'background:var(--c-primary);color:#fff;align-self:flex-end;' : 'background:var(--c-surface2);color:var(--c-text);align-self:flex-start;border:1px solid var(--c-border)');
  d.innerHTML = text;
  chatMsgs.appendChild(d);
  chatMsgs.scrollTop = chatMsgs.scrollHeight;
}
function sendChat() {
  const inp = document.getElementById('chatInput');
  const msg = inp.value.trim();
  if (!msg) return;
  addMsg(msg, 'user');
  inp.value = '';
  setTimeout(() => {
    let reply = 'شكراً على سؤالك! هذا المساعد للتجريب المحلي فقط. للحصول على إجابات متقدمة، جرّب <a href="https://chat.openai.com" target="_blank" style="color:var(--c-primary)">ChatGPT</a> أو <a href="https://claude.ai" target="_blank" style="color:var(--c-gold)">Claude</a>.';
    for (const [key, r] of Object.entries(autoReplies)) {
      if (msg.includes(key)) { reply = r; break; }
    }
    addMsg(reply, 'assistant');
  }, 600);
}
function clearChat() {
  chatMsgs.innerHTML = '<div class="ai-msg assistant" style="padding:.65rem 1rem;border-radius:1rem;background:var(--c-surface2);border:1px solid var(--c-border);font-size:14px;align-self:flex-start">تم مسح المحادثة. كيف يمكنني مساعدتك؟</div>';
}
document.getElementById('chatInput')?.addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendChat(); }
});

// ── Translate (MyMemory free API) ──
function swapTranslate() {
  const f = document.getElementById('transFrom');
  const t = document.getElementById('transTo');
  if (!f || !t) return;
  const tmp = f.value === 'auto' ? 'ar' : f.value;
  f.value = t.value;
  t.value = tmp;
}
async function doTranslate() {
  const text = document.getElementById('transInput')?.value?.trim();
  const from = document.getElementById('transFrom')?.value;
  const to   = document.getElementById('transTo')?.value;
  const out  = document.getElementById('transOutput');
  if (!text || !out) return;
  out.textContent = '⏳ جاري الترجمة...';
  out.removeAttribute('hidden');
  try {
    const langPair = (from === 'auto' ? '' : from + '|') + to;
    const url = `https://api.mymemory.translated.net/get?q=${encodeURIComponent(text)}&langpair=${langPair}`;
    const r = await fetch(url);
    const d = await r.json();
    out.textContent = d.responseData?.translatedText || 'تعذّرت الترجمة. جرّب لاحقاً.';
  } catch {
    out.textContent = 'تعذّر الاتصال بخدمة الترجمة. تحقق من الإنترنت.';
  }
}

// ── Summary (extractive: pick top N sentences) ──
function doSummary() {
  const text  = document.getElementById('summaryInput')?.value?.trim();
  const ratio = parseFloat(document.getElementById('summaryRatio')?.value || 0.5);
  const out   = document.getElementById('summaryOutput');
  if (!text || !out) return;
  const sentences = text.match(/[^.!?؟\n]+[.!?؟\n]*/g) || [];
  if (sentences.length < 2) { out.textContent = النص قصير جداً للتلخيص; out.removeAttribute('hidden'); return; }
  const keep = Math.max(1, Math.round(sentences.length * ratio));
  // Score by word frequency
  const words = text.toLowerCase().split(/\s+/);
  const freq = {};
  words.forEach(w => freq[w] = (freq[w]||0) + 1);
  const scored = sentences.map((s,i) => ({s, score: s.split(/\s+/).reduce((a,w)=>a+(freq[w.toLowerCase()]||0),0), i}));
  scored.sort((a,b) => b.score - a.score);
  const top = scored.slice(0, keep).sort((a,b) => a.i - b.i).map(x => x.s.trim());
  out.innerHTML = top.map(s => `<p style="margin-bottom:.5rem">${h(s)}</p>`).join('');
  out.removeAttribute('hidden');
}
function h(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
</script>
