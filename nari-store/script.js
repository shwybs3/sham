/* ============================================================
   ناري ستور — سكربت مشترك لكل صفحات الموقع
   - يقرأ data/settings.json (تُدار من لوحة التحكم) لتحديث رقم
     واتساب، شريط الإعلان، ووضع الصيانة على كل الصفحات تلقائياً
   - يبني روابط واتساب مع رسالة جاهزة حسب كل زر
   - قائمة جوال قابلة للفتح/الإغلاق
   - مساعد دردشة هجين:
     * أسئلة شائعة (أسعار/دفع/مدة) تُجاب فوراً بقواعد جاهزة بدون
       أي اتصال خارجي (سريع ومجاني دائماً).
     * أي سؤال آخر يُرسَل مباشرة من متصفح الزائر إلى OpenRouter
       (وليس من خادم PHP) إن كانت الخدمة مُفعّلة ومعها مفتاح API
       من لوحة التحكم — هذا يلتف بشكل مشروع على منع استضافة
       Infinity Free المجانية للاتصالات الصادرة من PHP، لأن طلب
       الشبكة هنا يخرج من جهاز الزائر نفسه.
     * إن كانت الخدمة غير مُفعّلة أو تعذّر الاتصال، تُعرض رسالة
       احتياطية توجّه العميل مباشرة لواتساب.
   ============================================================ */

let WHATSAPP_NUMBER = "963994898623"; // قيمة افتراضية، تُستبدل من data/settings.json إن وُجد
let SITE_NAME = "ناري ستور";

const AI_CONFIG = {
  enabled: false,
  apiKey: "",
  model: "meta-llama/llama-3.1-8b-instruct:free",
  systemPrompt: "",
  maxTurns: 12,
};

let ACTIVE_SERVICES = [];
const CHAT_HISTORY = []; // {role:'user'|'assistant', content:string}

function buildWhatsappLink(message){
  const text = encodeURIComponent(message || "مرحباً، أرغب بالاستفسار عن خدماتكم");
  return `https://wa.me/${WHATSAPP_NUMBER}?text=${text}`;
}

function refreshWhatsappLinks(){
  document.querySelectorAll("[data-wa]").forEach(el => {
    const msg = el.getAttribute("data-wa") || "";
    el.setAttribute("href", buildWhatsappLink(msg));
    el.setAttribute("target", "_blank");
    el.setAttribute("rel", "noopener");
  });
}

function applySettings(cfg){
  if(!cfg) return;

  if(cfg.site_name) SITE_NAME = cfg.site_name;

  if(cfg.whatsapp_number){
    WHATSAPP_NUMBER = String(cfg.whatsapp_number).replace(/\D/g, "");
    refreshWhatsappLinks();
  }
  document.querySelectorAll("[data-wa-display]").forEach(el => {
    if(cfg.whatsapp_display) el.textContent = cfg.whatsapp_display;
  });

  const bar = document.getElementById("announceBar");
  if(bar){
    if(cfg.announcement_enabled && cfg.announcement_text){
      const dismissed = sessionStorage.getItem("nariAnnounceDismissed");
      const textEl = bar.querySelector(".announce-text");
      if(textEl) textEl.textContent = cfg.announcement_text;
      if(!dismissed) bar.classList.add("show");
    } else {
      bar.classList.remove("show");
    }
  }

  if(cfg.maintenance_mode){
    const overlay = document.getElementById("maintOverlay");
    if(overlay){
      const msgEl = overlay.querySelector(".maint-msg");
      if(msgEl && cfg.maintenance_message) msgEl.textContent = cfg.maintenance_message;
      overlay.classList.add("show");
    }
  }

  // روابط التواصل الاجتماعي في الفوتر (تُدار من لوحة التحكم)
  const socialBox = document.getElementById("socialLinks");
  if(socialBox){
    const links = [
      ["instagram_url", "انستقرام"],
      ["tiktok_url", "تيك توك"],
      ["facebook_url", "فيسبوك"],
    ];
    const html = links
      .filter(([key]) => cfg[key] && String(cfg[key]).trim())
      .map(([key, label]) => {
        const url = String(cfg[key]).trim().replace(/"/g, "&quot;");
        return `<li><a href="${url}" target="_blank" rel="noopener nofollow">${label}</a></li>`;
      })
      .join("");
    socialBox.innerHTML = html;
  }

  AI_CONFIG.enabled = !!(cfg.ai_chat_enabled && cfg.openrouter_api_key);
  AI_CONFIG.apiKey = cfg.openrouter_api_key || "";
  AI_CONFIG.model = cfg.openrouter_model || AI_CONFIG.model;
  AI_CONFIG.systemPrompt = cfg.ai_system_prompt || "";
  AI_CONFIG.maxTurns = Number(cfg.ai_max_turns_per_session) > 0 ? Number(cfg.ai_max_turns_per_session) : 12;
}

function applyServiceStatus(list){
  if(!Array.isArray(list)) return;
  ACTIVE_SERVICES = list.filter(s => s.active);

  const byId = {};
  list.forEach(s => { byId[s.id] = s; });

  // بطاقات الخدمات في الصفحة الرئيسية وصفحة كل الخدمات
  document.querySelectorAll("[data-service-id]").forEach(card => {
    const id = card.getAttribute("data-service-id");
    const svc = byId[id];
    if(!svc) return;
    if(!svc.active){
      card.classList.add("is-paused");
      if(!card.querySelector(".badge-off")){
        const badge = document.createElement("span");
        badge.className = "badge-off";
        badge.textContent = svc.note && svc.note.trim() ? svc.note : "متوقفة مؤقتاً";
        card.appendChild(badge);
      }
    }
  });

  // صفحة الخدمة نفسها (body[data-service-id])
  const currentId = document.body.getAttribute("data-service-id");
  if(currentId && byId[currentId] && !byId[currentId].active){
    const alertBox = document.querySelector(".top-alert");
    if(alertBox){
      const note = byId[currentId].note && byId[currentId].note.trim()
        ? byId[currentId].note
        : "سنعلمك بمجرد عودتها";
      alertBox.innerHTML = `${PAUSE_ICON_SVG} هذه الخدمة متوقفة مؤقتاً حالياً — ${note}. للاستفسار `
        + `<a data-wa="مرحباً، أرغب بمعرفة متى تعود هذه الخدمة للعمل" href="#">تواصل معنا عبر واتساب</a>`;
      alertBox.style.background = "linear-gradient(90deg,#3a3a3f,#232327)";
      refreshWhatsappLinks();
    }
  }
}

const PAUSE_ICON_SVG = '<svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><line x1="9" y1="5" x2="9" y2="19"/><line x1="15" y1="5" x2="15" y2="19"/></svg>';

function formatSyp(n){
  // نفس تنسيق الفواصل المستخدم في الصفحات الثابتة (أرقام غربية + فاصلة "٬")
  const grouped = Math.round(Number(n) || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, "٬");
  return grouped + " ل.س";
}

function applyProducts(list){
  if(!Array.isArray(list)) return;
  const byId = {};
  list.forEach(p => { byId[p.id] = p; });

  // كل عناصر السعر القابلة للتحديث الحي (جداول الخدمات، صفحة قائمة الأسعار، صفحة المنتج نفسه)
  document.querySelectorAll("[data-product-price]").forEach(el => {
    const p = byId[el.getAttribute("data-product-price")];
    if(p) el.textContent = formatSyp(p.price_syp);
  });

  // صفوف/بطاقات المنتجات المتوقفة مؤقتاً
  document.querySelectorAll("[data-product-id]").forEach(row => {
    const p = byId[row.getAttribute("data-product-id")];
    if(!p) return;
    if(!p.active){
      row.classList.add("is-paused");
      if(row.tagName === "TR"){
        row.style.opacity = ".55";
        const link = row.querySelector("a.buy");
        if(link){ link.textContent = p.note && p.note.trim() ? p.note : "غير متوفر حالياً"; link.removeAttribute("href"); }
      }
    }
  });

  // صفحة المنتج نفسها (body[data-product-id])
  const currentId = document.body.getAttribute("data-product-id");
  if(currentId && byId[currentId] && !byId[currentId].active){
    const alertBox = document.querySelector(".top-alert");
    if(alertBox){
      const note = byId[currentId].note && byId[currentId].note.trim()
        ? byId[currentId].note
        : "سنعلمك بمجرد عودته";
      alertBox.innerHTML = `${PAUSE_ICON_SVG} هذا المنتج متوقف مؤقتاً حالياً — ${note}. للاستفسار `
        + `<a data-wa="مرحباً، أرغب بمعرفة متى يعود هذا المنتج للتوفر" href="#">تواصل معنا عبر واتساب</a>`;
      alertBox.style.background = "linear-gradient(90deg,#3a3a3f,#232327)";
      refreshWhatsappLinks();
    }
  }
}

function loadProducts(){
  fetch("data/products.json", { cache: "no-store" })
    .then(res => res.ok ? res.json() : null)
    .then(applyProducts)
    .catch(() => { /* لا مشكلة إن تعذّرت القراءة */ });
}

function loadServiceStatus(){
  fetch("data/services.json", { cache: "no-store" })
    .then(res => res.ok ? res.json() : null)
    .then(applyServiceStatus)
    .catch(() => { /* لا مشكلة إن تعذّرت القراءة */ });
}

function loadSettings(){
  // المسار نسبي حتى يعمل من أي صفحة في جذر الموقع
  fetch("data/settings.json", { cache: "no-store" })
    .then(res => res.ok ? res.json() : null)
    .then(applySettings)
    .catch(() => { /* لا مشكلة إن تعذّرت القراءة — الموقع يعمل بالقيم الافتراضية */ });
}

document.addEventListener("DOMContentLoaded", () => {
  refreshWhatsappLinks();
  loadSettings();
  loadServiceStatus();
  loadProducts();

  // -------------------- قائمة الجوال --------------------
  const navToggle = document.getElementById("navToggle");
  const navLinks  = document.getElementById("navLinks");
  if(navToggle && navLinks){
    navToggle.addEventListener("click", () => navLinks.classList.toggle("open"));
    navLinks.querySelectorAll("a").forEach(a => a.addEventListener("click", () => navLinks.classList.remove("open")));
  }

  // -------------------- شريط الإعلان --------------------
  const announceClose = document.querySelector(".close-announce");
  if(announceClose){
    announceClose.addEventListener("click", () => {
      const bar = document.getElementById("announceBar");
      if(bar) bar.classList.remove("show");
      sessionStorage.setItem("nariAnnounceDismissed", "1");
    });
  }

  // -------------------- كتالوج الخدمات: فلترة حسب الفئة --------------------
  const catTabs = document.querySelectorAll(".cat-tabs button");
  if(catTabs.length){
    catTabs.forEach(btn => {
      btn.addEventListener("click", () => {
        catTabs.forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        const cat = btn.getAttribute("data-cat");
        document.querySelectorAll("[data-service-cat]").forEach(card => {
          card.style.display = (cat === "all" || card.getAttribute("data-service-cat") === cat) ? "" : "none";
        });
      });
    });
  }

  // -------------------- ويدجت الدردشة --------------------
  const launcher = document.getElementById("chatLauncher");
  const win      = document.getElementById("chatWindow");
  const closeBtn = document.getElementById("chatClose");
  const body     = document.getElementById("chatBody");
  const form     = document.getElementById("chatForm");
  const input    = document.getElementById("chatInput");
  const quickBar = document.getElementById("chatQuick");

  if(!launcher || !win) return;

  launcher.addEventListener("click", () => win.classList.toggle("open"));
  closeBtn.addEventListener("click", () => win.classList.remove("open"));

  function addMsg(text, who){
    const div = document.createElement("div");
    div.className = `msg ${who}`;
    div.textContent = text;
    body.appendChild(div);
    body.scrollTop = body.scrollHeight;
    return div;
  }

  function addWhatsappPrompt(context){
    const div = document.createElement("div");
    div.className = "msg bot";
    const a = document.createElement("a");
    a.href = buildWhatsappLink(context);
    a.target = "_blank"; a.rel = "noopener";
    a.style.color = "#fff";
    a.style.fontWeight = "800";
    a.textContent = "تواصل معنا مباشرة عبر واتساب ↗";
    div.appendChild(document.createTextNode("للتأكيد والدفع، تفضّل عبر: "));
    div.appendChild(a);
    body.appendChild(div);
    body.scrollTop = body.scrollHeight;
  }

  const RULES = [
    { keys: ["سعر","اسعار","أسعار","باقة","باقات"],
      reply: "الأسعار موضّحة في جدول الباقات بكل صفحة خدمة، وأيضاً في صفحة قائمة الأسعار الكاملة. اختر الباقة المناسبة، وسنؤكد السعر النهائي فوراً عبر واتساب." },
    { keys: ["شحن","جواهر","الماس","فري فاير","ماسات","شدات","ببجي","كول اوف ديوتي","كول أوف ديوتي"],
      reply: "نوفّر شحن جواهر فري فاير وشدات ببجي ونقاط كول أوف ديوتي عبر شام كاش، والتنفيذ خلال دقائق من تأكيد الدفع." },
    { keys: ["شام كاش","دفع","الدفع","طريقة الدفع"],
      reply: "الدفع حالياً عبر شام كاش فقط. أرسل رقم الحساب أو صورة الإيصال بعد إتمام التحويل عبر واتساب." },
    { keys: ["متابعين","انستقرام","انستغرام","فولورز","لايكات","تيك توك","تيكتوك","مشاهدات","يوتيوب"],
      reply: "نوفّر زيادة متابعين ولايكات انستقرام، ومتابعين تيك توك، ومشاهدات يوتيوب بجودة عالية وتسليم آمن. التفاصيل في صفحة كل خدمة." },
    { keys: ["ايتونز","آيتونز","جوجل بلاي","شاهد","نتفليكس","اشتراك","بطاقة","بطاقات"],
      reply: "نوفّر بطاقات آيتونز وجوجل بلاي واشتراكات شاهد VIP ونتفليكس بأسعار منافسة وتسليم فوري عبر واتساب." },
    { keys: ["وقت","مدة","متى","سرعة"],
      reply: "غالبية الطلبات تُنفَّذ خلال دقائق إلى ساعة كحد أقصى بعد تأكيد الدفع." },
    { keys: ["ضمان","موثوق","امان","أمان","احتيال"],
      reply: "نعمل بشفافية كاملة: تأكيد الطلب قبل التنفيذ، وتواصل مباشر معك حتى استلام الخدمة." },
    { keys: ["واتساب","تواصل","رقم"],
      reply: "يمكنك مراسلتنا مباشرة على واتساب من الزر الأخضر العائم أو من أي زر في الصفحة." },
  ];

  function buildSystemPrompt(){
    const base = AI_CONFIG.systemPrompt
      || `أنت مساعد ${SITE_NAME}، متجر رقمي سوري لشحن الألعاب والبطاقات والاشتراكات وزيادة متابعين السوشيال ميديا عبر شام كاش. تحدث بالعربية بأسلوب ودود ومختصر.`;
    const services = ACTIVE_SERVICES.length
      ? "الخدمات المتاحة حالياً: " + ACTIVE_SERVICES.map(s => s.name).join("، ") + "."
      : "";
    return `${base}\n${services}\nرقم واتساب المتجر للتأكيد والشراء: ${WHATSAPP_NUMBER}. اجعل ردودك قصيرة (3-4 أسطر كحد أقصى) وادعُ العميل دائماً لإكمال طلبه عبر واتساب.`;
  }

  async function callOpenRouter(){
    const res = await fetch("https://openrouter.ai/api/v1/chat/completions", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Authorization": `Bearer ${AI_CONFIG.apiKey}`,
        "HTTP-Referer": window.location.origin,
        "X-Title": SITE_NAME,
      },
      body: JSON.stringify({
        model: AI_CONFIG.model,
        messages: [{ role: "system", content: buildSystemPrompt() }, ...CHAT_HISTORY.slice(-10)],
        max_tokens: 350,
        temperature: 0.6,
      }),
    });
    if(!res.ok) throw new Error("openrouter_http_" + res.status);
    const data = await res.json();
    const text = data && data.choices && data.choices[0] && data.choices[0].message && data.choices[0].message.content;
    if(!text || !text.trim()) throw new Error("openrouter_empty");
    return text.trim();
  }

  function aiTurnsUsed(){
    return Number(sessionStorage.getItem("nariAiTurns") || "0");
  }
  function bumpAiTurns(){
    sessionStorage.setItem("nariAiTurns", String(aiTurnsUsed() + 1));
  }

  function fallbackReply(userText){
    addMsg("شكراً لسؤالك! لضمان أسرع وأدق رد، تواصل معنا مباشرة عبر واتساب وسيتم الرد عليك فوراً من فريقنا.", "bot");
    addWhatsappPrompt(`مرحباً، لدي استفسار: ${userText}`);
  }

  function respond(userText){
    addMsg(userText, "user");
    CHAT_HISTORY.push({ role: "user", content: userText });

    const lower = userText.trim();
    const hit = RULES.find(r => r.keys.some(k => lower.includes(k)));

    if(hit){
      setTimeout(() => {
        addMsg(hit.reply, "bot");
        CHAT_HISTORY.push({ role: "assistant", content: hit.reply });
        addWhatsappPrompt(`مرحباً، لدي استفسار: ${userText}`);
      }, 350);
      return;
    }

    if(!AI_CONFIG.enabled){
      setTimeout(() => fallbackReply(userText), 350);
      return;
    }

    if(aiTurnsUsed() >= AI_CONFIG.maxTurns){
      setTimeout(() => {
        addMsg("وصلنا للحد الأقصى من الأسئلة في هذه الجلسة 🙂 لإكمال حديثك تواصل معنا مباشرة عبر واتساب.", "bot");
        addWhatsappPrompt(`مرحباً، لدي استفسار: ${userText}`);
      }, 300);
      return;
    }

    const typingBubble = addMsg("...", "bot");
    bumpAiTurns();
    callOpenRouter()
      .then(reply => {
        typingBubble.textContent = reply;
        body.scrollTop = body.scrollHeight;
        CHAT_HISTORY.push({ role: "assistant", content: reply });
        addWhatsappPrompt(`مرحباً، لدي استفسار: ${userText}`);
      })
      .catch(() => {
        typingBubble.remove();
        fallbackReply(userText);
      });
  }

  if(form){
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const val = input.value.trim();
      if(!val) return;
      respond(val);
      input.value = "";
    });
  }

  if(quickBar){
    quickBar.querySelectorAll("button").forEach(btn => {
      btn.addEventListener("click", () => respond(btn.textContent));
    });
  }
});
