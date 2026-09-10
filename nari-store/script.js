/* ============================================================
   ناري ستور — سكربت مشترك لكل صفحات الموقع
   - يقرأ data/settings.json (تُدار من لوحة التحكم) لتحديث رقم
     واتساب، شريط الإعلان، ووضع الصيانة على كل الصفحات تلقائياً
   - يبني روابط واتساب مع رسالة جاهزة حسب كل زر
   - قائمة جوال قابلة للفتح/الإغلاق
   - مساعد دردشة مبني بقواعد جاهزة (بدون اتصال خارجي)
     * ملاحظة: هذا مساعد قائم على قواعد وليس نموذج ذكاء
       اصطناعي متصل بخادم خارجي.
   ============================================================ */

let WHATSAPP_NUMBER = "963994898623"; // قيمة افتراضية، تُستبدل من data/settings.json إن وُجد

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
}

function applyServiceStatus(list){
  if(!Array.isArray(list)) return;
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
      alertBox.innerHTML = `⏸️ هذه الخدمة متوقفة مؤقتاً حالياً — ${note}. للاستفسار `
        + `<a data-wa="مرحباً، أرغب بمعرفة متى تعود هذه الخدمة للعمل" href="#">تواصل معنا عبر واتساب</a>`;
      alertBox.style.background = "linear-gradient(90deg,#3a3a3f,#232327)";
      refreshWhatsappLinks();
    }
  }
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
      reply: "الأسعار موضّحة في جدول الباقات بكل صفحة خدمة. اختر الباقة المناسبة، وسنؤكد السعر النهائي فوراً عبر واتساب." },
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

  function respond(userText){
    addMsg(userText, "user");
    const lower = userText.trim();
    const hit = RULES.find(r => r.keys.some(k => lower.includes(k)));
    setTimeout(() => {
      if(hit){
        addMsg(hit.reply, "bot");
        addWhatsappPrompt(`مرحباً، لدي استفسار: ${userText}`);
      } else {
        addMsg("شكراً لسؤالك! لضمان أسرع وأدق رد، تواصل معنا مباشرة عبر واتساب وسيتم الرد عليك فوراً من فريقنا.", "bot");
        addWhatsappPrompt(`مرحباً، لدي استفسار: ${userText}`);
      }
    }, 450);
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
