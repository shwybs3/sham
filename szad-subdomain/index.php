<?php
/* szad-subdomain/index.php — Szad AI landing page. Self-contained, no DB required. */
$siteUrl  = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://yassota.com';
$authUrl  = $siteUrl . '/tools/chat/auth.php?redirect=' . urlencode($siteUrl . '/tools/szad/');
$szadUrl  = $siteUrl . '/tools/szad/';
$payUrl   = $siteUrl . '/tools/szad/pay.php';
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>صَعد AI — ذكاء اصطناعي عربي بقدرات بحث الويب والتفكير العميق</title>
<meta name="description" content="صَعد AI: منصة ذكاء اصطناعي عربية متقدمة تجمع بحث الويب الفوري والتفكير العميق والمحادثة الطبيعية. أقوى من ChatGPT للمستخدم العربي.">
<meta property="og:title" content="صَعد AI — ذكاء اصطناعي عربي متقدم">
<meta property="og:description" content="بحث ويب فوري، تفكير عميق، ذاكرة محادثة. جرّب مجاناً.">
<meta property="og:type" content="website">
<meta name="theme-color" content="#c50000">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔥</text></svg>">
<style>
/* ── Reset & Base ───────────────────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --red:#c50000;--red2:#ff2200;--red3:#7a0000;--red4:#3a0000;
  --bg:#050008;--bg2:#080010;--bg3:#0d0018;
  --glass:rgba(180,0,0,.08);--glass2:rgba(180,0,0,.14);
  --border:rgba(197,0,0,.2);--border2:rgba(197,0,0,.35);
  --text:#f0e0e0;--muted:#a88888;--muted2:#7a6060;
  --ff:'Segoe UI','Helvetica Neue',Arial,sans-serif;
  --mono:ui-monospace,monospace;
}
html{scroll-behavior:smooth}
body{font-family:var(--ff);background:var(--bg);color:var(--text);overflow-x:hidden;line-height:1.7}
a{color:var(--red2);text-decoration:none}
a:hover{text-decoration:underline}
img{max-width:100%}
button{cursor:pointer;font-family:var(--ff)}
h1,h2,h3,h4{text-wrap:balance;line-height:1.25}

/* ── Scrollbar ──────────────────────────────────────────────────── */
::-webkit-scrollbar{width:6px}
::-webkit-scrollbar-track{background:var(--bg)}
::-webkit-scrollbar-thumb{background:var(--red3);border-radius:3px}

/* ── Nav ────────────────────────────────────────────────────────── */
.sz-nav{
  position:fixed;top:0;right:0;left:0;z-index:100;
  display:flex;align-items:center;justify-content:space-between;
  padding:14px 24px;
  background:rgba(5,0,8,.85);
  backdrop-filter:blur(18px);
  border-bottom:1px solid var(--border);
}
.sz-logo{font-size:22px;font-weight:900;color:#fff;display:flex;align-items:center;gap:8px}
.sz-logo .flame{color:var(--red2);animation:pulse 2s ease-in-out infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.65}}
.sz-logo .name{letter-spacing:.02em}
.sz-nav-links{display:flex;align-items:center;gap:20px}
.sz-nav-links a{font-size:13px;color:var(--muted);transition:color .15s}
.sz-nav-links a:hover{color:#fff;text-decoration:none}
.btn-nav-login{
  background:var(--red);color:#fff;
  padding:8px 20px;border-radius:20px;
  font-size:13px;font-weight:700;
  transition:background .15s;
}
.btn-nav-login:hover{background:var(--red2);text-decoration:none}

/* ── Hero ───────────────────────────────────────────────────────── */
.sz-hero{
  position:relative;min-height:100vh;
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  text-align:center;padding:100px 20px 60px;overflow:hidden;
}
#sz-canvas{position:absolute;inset:0;z-index:0;pointer-events:none}
.hero-content{position:relative;z-index:1;max-width:760px}
.hero-badge{
  display:inline-flex;align-items:center;gap:6px;
  background:var(--glass2);border:1px solid var(--border2);
  border-radius:20px;padding:6px 16px;font-size:12px;color:var(--red2);
  font-weight:600;margin-bottom:24px;letter-spacing:.04em;
}
.hero-title{
  font-size:clamp(38px,8vw,72px);font-weight:900;
  color:#fff;margin-bottom:20px;
  text-shadow:0 0 60px rgba(197,0,0,.4);
}
.hero-title .accent{color:var(--red2)}
.hero-sub{
  font-size:clamp(15px,2.5vw,20px);color:var(--muted);
  max-width:580px;margin:0 auto 36px;line-height:1.75;
}
.hero-ctas{display:flex;gap:14px;justify-content:center;flex-wrap:wrap}
.btn-primary{
  background:linear-gradient(135deg,var(--red),var(--red2));
  color:#fff;padding:14px 32px;border-radius:12px;
  font-size:16px;font-weight:700;border:none;
  box-shadow:0 4px 24px rgba(197,0,0,.4);
  transition:transform .18s,box-shadow .18s;
  display:inline-flex;align-items:center;gap:8px;
}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 32px rgba(197,0,0,.5);text-decoration:none;color:#fff}
.btn-outline{
  background:transparent;color:#fff;
  padding:14px 28px;border-radius:12px;
  font-size:16px;font-weight:600;
  border:1.5px solid var(--border2);
  transition:all .18s;
  display:inline-flex;align-items:center;gap:8px;
}
.btn-outline:hover{background:var(--glass2);text-decoration:none;color:#fff}
.hero-stats{
  display:flex;gap:40px;justify-content:center;flex-wrap:wrap;
  margin-top:48px;padding-top:36px;border-top:1px solid var(--border);
}
.hero-stat .val{font-size:28px;font-weight:800;color:#fff}
.hero-stat .lbl{font-size:12px;color:var(--muted);margin-top:2px}

/* ── Section base ───────────────────────────────────────────────── */
.sz-section{padding:80px 20px;max-width:1100px;margin:0 auto}
.sz-section-full{padding:80px 20px;background:var(--bg2)}
.section-inner{max-width:1100px;margin:0 auto}
.section-tag{
  font-size:11px;font-weight:700;letter-spacing:.1em;color:var(--red2);
  text-transform:uppercase;margin-bottom:10px;
}
.section-title{font-size:clamp(24px,4vw,40px);font-weight:800;color:#fff;margin-bottom:14px}
.section-sub{font-size:15px;color:var(--muted);max-width:580px;line-height:1.8}

/* ── Demo tool ──────────────────────────────────────────────────── */
#tool{background:var(--bg2)}
.tool-inner{max-width:820px;margin:0 auto;text-align:center;padding:80px 20px}
.mode-tabs{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin:24px 0}
.mode-tab{
  padding:9px 22px;border-radius:20px;font-size:14px;font-weight:600;
  border:1.5px solid var(--border);background:transparent;color:var(--muted);
  transition:all .18s;cursor:pointer;
}
.mode-tab.active{background:var(--red);border-color:var(--red);color:#fff}
.mode-tab:hover:not(.active){border-color:var(--red);color:var(--text)}
.sz-demo{
  background:rgba(0,0,0,.35);border:1px solid var(--border2);
  border-radius:18px;padding:24px;text-align:start;
}
.sz-demo-msgs{min-height:120px;margin-bottom:16px;display:flex;flex-direction:column;gap:12px}
.demo-msg{padding:12px 16px;border-radius:12px;font-size:14px;line-height:1.7;max-width:85%}
.demo-msg.user{background:var(--red3);color:#fff;align-self:flex-end;border-radius:12px 4px 12px 12px}
.demo-msg.ai{background:rgba(255,255,255,.06);color:var(--text);align-self:flex-start;border-radius:4px 12px 12px 12px}
.sz-input-row{display:flex;gap:10px}
.sz-input{
  flex:1;background:rgba(255,255,255,.06);border:1px solid var(--border2);
  border-radius:10px;padding:12px 16px;color:var(--text);font-size:14px;
  font-family:var(--ff);resize:none;
}
.sz-input::placeholder{color:var(--muted2)}
.sz-input:focus{outline:none;border-color:var(--red)}
.sz-send-btn{
  background:var(--red);color:#fff;border:none;border-radius:10px;
  padding:12px 22px;font-size:14px;font-weight:700;
  display:flex;align-items:center;gap:6px;
  transition:background .15s;
  white-space:nowrap;
}
.sz-send-btn:hover{background:var(--red2)}

/* ── Features grid ──────────────────────────────────────────────── */
.features-grid{
  display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px;
  margin-top:40px;
}
.feat-card{
  background:var(--glass);border:1px solid var(--border);
  border-radius:16px;padding:24px 20px;
  transition:transform .18s,border-color .18s;
}
.feat-card:hover{transform:translateY(-3px);border-color:var(--border2)}
.feat-icon{font-size:28px;margin-bottom:12px}
.feat-name{font-size:15px;font-weight:700;color:#fff;margin-bottom:6px}
.feat-desc{font-size:13px;color:var(--muted);line-height:1.7}

/* ── Mode cards ─────────────────────────────────────────────────── */
.modes-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;margin-top:36px}
.mode-card{
  background:var(--glass);border:1px solid var(--border);
  border-radius:20px;padding:28px 24px;
}
.mode-card-icon{font-size:36px;margin-bottom:14px}
.mode-card-name{font-size:18px;font-weight:800;color:#fff;margin-bottom:8px}
.mode-card-desc{font-size:13px;color:var(--muted);line-height:1.75;margin-bottom:16px}
.mode-feats{list-style:none;display:flex;flex-direction:column;gap:7px}
.mode-feats li{font-size:13px;color:#ddd;display:flex;align-items:center;gap:8px}
.mode-feats li::before{content:'▸';color:var(--red2);flex-shrink:0}

/* ── How it works ───────────────────────────────────────────────── */
.steps{display:flex;flex-direction:column;gap:28px;margin-top:40px}
.step{display:flex;gap:20px;align-items:flex-start}
.step-num{
  flex-shrink:0;width:44px;height:44px;border-radius:50%;
  background:linear-gradient(135deg,var(--red),var(--red2));
  display:flex;align-items:center;justify-content:center;
  font-size:17px;font-weight:800;color:#fff;
}
.step-body h3{font-size:16px;font-weight:700;color:#fff;margin-bottom:4px}
.step-body p{font-size:14px;color:var(--muted);line-height:1.7}

/* ── Use cases ──────────────────────────────────────────────────── */
.uc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:18px;margin-top:36px}
.uc-card{
  background:var(--glass);border:1px solid var(--border);
  border-radius:14px;padding:20px;
}
.uc-icon{font-size:26px;margin-bottom:10px}
.uc-title{font-size:14px;font-weight:700;color:#fff;margin-bottom:10px}
.uc-list{list-style:none;display:flex;flex-direction:column;gap:6px}
.uc-list li{font-size:12px;color:var(--muted);display:flex;gap:7px;align-items:flex-start}
.uc-list li::before{content:'·';color:var(--red2);flex-shrink:0;font-weight:900}

/* ── Compare table ──────────────────────────────────────────────── */
.cmp-wrap{overflow-x:auto;margin-top:36px;border-radius:14px;border:1px solid var(--border)}
table.cmp{width:100%;border-collapse:collapse;font-size:14px;min-width:600px}
table.cmp th{
  background:rgba(197,0,0,.18);color:#fff;font-weight:700;
  padding:14px 18px;text-align:center;border-bottom:1px solid var(--border2);
}
table.cmp th:first-child{text-align:start}
table.cmp td{
  padding:13px 18px;border-bottom:1px solid var(--border);
  text-align:center;color:var(--muted);vertical-align:middle;
}
table.cmp td:first-child{text-align:start;color:var(--text);font-weight:500}
table.cmp tr:last-child td{border-bottom:none}
table.cmp tr:hover td{background:rgba(255,255,255,.03)}
.cmp-yes{color:#10b981;font-weight:700}
.cmp-no{color:#f87171}
.cmp-part{color:#fbbf24}
.cmp-szad td{background:rgba(197,0,0,.06)!important}
.cmp-szad th{background:rgba(197,0,0,.25)!important}

/* ── Pricing ────────────────────────────────────────────────────── */
.pricing-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:20px;margin-top:36px}
.price-card{
  background:var(--glass);border:1.5px solid var(--border);
  border-radius:20px;padding:28px 22px;
  display:flex;flex-direction:column;gap:12px;
  position:relative;transition:transform .18s,border-color .18s;
}
.price-card:hover{transform:translateY(-4px);border-color:var(--border2)}
.price-card.featured{border-color:var(--red);box-shadow:0 0 0 1px rgba(197,0,0,.3)}
.price-badge{
  position:absolute;top:-13px;right:20px;
  background:var(--red);color:#fff;font-size:11px;font-weight:700;
  padding:3px 14px;border-radius:20px;
}
.price-name{font-size:17px;font-weight:700;color:#fff}
.price-val{font-size:40px;font-weight:900;color:#fff;line-height:1}
.price-val sup{font-size:18px;vertical-align:super;color:var(--muted)}
.price-val span{font-size:14px;color:var(--muted)}
.price-desc{font-size:13px;color:var(--muted);line-height:1.65}
.price-feats{list-style:none;display:flex;flex-direction:column;gap:8px;flex:1}
.price-feats li{font-size:13px;color:#ddd;display:flex;align-items:flex-start;gap:8px}
.price-feats li::before{content:'✓';color:var(--red2);font-weight:700;flex-shrink:0;margin-top:2px}
.price-btn{
  display:block;text-align:center;padding:12px;border-radius:12px;
  font-weight:700;font-size:15px;transition:opacity .15s;
  margin-top:auto;text-decoration:none;
}
.price-btn-red{background:linear-gradient(135deg,var(--red),var(--red2));color:#fff}
.price-btn-red:hover{opacity:.88;text-decoration:none;color:#fff}
.price-btn-outline{background:transparent;border:1.5px solid var(--border2);color:#aaa}
.price-btn-outline:hover{background:var(--glass2);text-decoration:none;color:#fff}

/* ── Testimonials ───────────────────────────────────────────────── */
.testi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px;margin-top:36px}
.testi-card{
  background:var(--glass);border:1px solid var(--border);
  border-radius:14px;padding:20px;display:flex;flex-direction:column;gap:10px;
}
.testi-stars{color:#fbbf24;font-size:14px}
.testi-text{font-size:13px;color:#ddd;line-height:1.75;flex:1}
.testi-author{display:flex;align-items:center;gap:10px}
.testi-avatar{
  width:36px;height:36px;border-radius:50%;
  background:linear-gradient(135deg,var(--red3),var(--red));
  display:flex;align-items:center;justify-content:center;
  font-size:16px;flex-shrink:0;
}
.testi-name{font-size:13px;font-weight:700;color:#fff}
.testi-role{font-size:11px;color:var(--muted)}

/* ── Tutorials ──────────────────────────────────────────────────── */
.tutorial-list{display:flex;flex-direction:column;gap:12px;margin-top:36px}
details.tutorial{
  background:var(--glass);border:1px solid var(--border);
  border-radius:14px;overflow:hidden;
}
details.tutorial summary{
  padding:18px 22px;font-size:15px;font-weight:700;color:#fff;
  cursor:pointer;display:flex;align-items:center;justify-content:space-between;
  user-select:none;list-style:none;
}
details.tutorial summary::after{content:'＋';color:var(--red2);font-size:18px;transition:transform .2s}
details.tutorial[open] summary::after{transform:rotate(45deg)}
details.tutorial summary::-webkit-details-marker{display:none}
.tutorial-body{padding:0 22px 20px;font-size:14px;color:var(--muted);line-height:1.85}
.tutorial-body h4{color:#fff;font-size:14px;margin:12px 0 6px}
.tutorial-body ul{padding-right:18px}
.tutorial-body li{margin-bottom:5px}
.tutorial-body code{
  background:rgba(255,255,255,.08);padding:2px 6px;border-radius:4px;
  font-family:var(--mono);font-size:12px;color:#f0e0e0;
}

/* ── FAQ ────────────────────────────────────────────────────────── */
.faq-list{display:flex;flex-direction:column;gap:10px;margin-top:36px}
details.faq{
  background:var(--glass);border:1px solid var(--border);border-radius:12px;
}
details.faq summary{
  padding:16px 20px;font-size:14px;font-weight:600;color:#fff;
  cursor:pointer;list-style:none;display:flex;justify-content:space-between;align-items:center;
}
details.faq summary::after{content:'▾';color:var(--red2);transition:transform .2s}
details.faq[open] summary::after{transform:rotate(180deg)}
details.faq summary::-webkit-details-marker{display:none}
.faq-ans{padding:0 20px 16px;font-size:13px;color:var(--muted);line-height:1.85}

/* ── Legal sections ─────────────────────────────────────────────── */
.legal-section{
  background:var(--bg2);border-top:1px solid var(--border);
  padding:60px 20px;
}
.legal-inner{max-width:820px;margin:0 auto}
.legal-inner h2{font-size:22px;font-weight:800;color:#fff;margin-bottom:18px}
.legal-inner h3{font-size:15px;font-weight:700;color:#e0c0c0;margin:20px 0 8px}
.legal-inner p{font-size:13px;color:var(--muted);line-height:1.9;margin-bottom:10px}
.legal-inner ul{padding-right:20px;margin-bottom:10px}
.legal-inner li{font-size:13px;color:var(--muted);line-height:1.9;margin-bottom:4px}

/* ── Contact ────────────────────────────────────────────────────── */
.contact-form{display:flex;flex-direction:column;gap:14px;max-width:520px}
.contact-form input,.contact-form textarea{
  background:rgba(255,255,255,.06);border:1px solid var(--border2);
  border-radius:10px;padding:12px 16px;color:var(--text);
  font-size:14px;font-family:var(--ff);
}
.contact-form textarea{resize:vertical;min-height:120px}
.contact-form input:focus,.contact-form textarea:focus{outline:none;border-color:var(--red)}

/* ── Footer ─────────────────────────────────────────────────────── */
.sz-footer{
  background:var(--bg2);border-top:1px solid var(--border);
  padding:40px 20px 24px;text-align:center;
}
.footer-links{
  display:flex;gap:24px;justify-content:center;flex-wrap:wrap;
  margin-bottom:20px;
}
.footer-links a{font-size:13px;color:var(--muted);transition:color .15s}
.footer-links a:hover{color:#fff;text-decoration:none}
.footer-copy{font-size:12px;color:var(--muted2)}
.back-top{
  position:fixed;bottom:24px;left:20px;
  background:var(--red);color:#fff;border:none;border-radius:50%;
  width:42px;height:42px;font-size:18px;
  display:flex;align-items:center;justify-content:center;
  box-shadow:0 4px 16px rgba(197,0,0,.4);
  transition:opacity .2s,transform .2s;opacity:0;pointer-events:none;z-index:50;
}
.back-top.visible{opacity:1;pointer-events:auto}
.back-top:hover{transform:translateY(-3px)}

/* ── Responsive ─────────────────────────────────────────────────── */
@media(max-width:768px){
  .sz-nav-links{gap:12px}
  .sz-nav-links a:not(.btn-nav-login){display:none}
  .hero-stats{gap:24px}
  .step{flex-direction:column;gap:12px}
}
@media(max-width:480px){
  .sz-nav{padding:12px 16px}
  .hero-ctas{flex-direction:column;align-items:center}
  .pricing-grid{grid-template-columns:1fr}
}

/* ── Animations ─────────────────────────────────────────────────── */
.reveal{opacity:0;transform:translateY(24px);transition:opacity .55s ease,transform .55s ease}
.reveal.in{opacity:1;transform:none}
</style>
</head>
<body>

<!-- ══ NAV ══════════════════════════════════════════════════════════ -->
<nav class="sz-nav">
  <div class="sz-logo">
    <span class="flame">🔥</span>
    <span class="name">صَعد AI</span>
  </div>
  <div class="sz-nav-links">
    <a href="#features">المميزات</a>
    <a href="#modes">الأوضاع</a>
    <a href="#pricing">الأسعار</a>
    <a href="#faq">الأسئلة</a>
    <a href="<?= $h($authUrl) ?>" class="btn-nav-login">دخول بـ Google</a>
  </div>
</nav>

<!-- ══ HERO ══════════════════════════════════════════════════════════ -->
<section class="sz-hero" id="home">
  <canvas id="sz-canvas"></canvas>
  <div class="hero-content">
    <div class="hero-badge">🔥 الذكاء الاصطناعي العربي الأقوى</div>
    <h1 class="hero-title">
      <span class="accent">صَعد</span> AI<br>
      بحث الويب + التفكير العميق
    </h1>
    <p class="hero-sub">
      مساعد ذكاء اصطناعي عربي متقدم يجمع بين البحث الفوري في الويب،
      التفكير المنطقي العميق، والمحادثة الطبيعية — كل ما تحتاجه في مكان واحد.
    </p>
    <div class="hero-ctas">
      <a href="<?= $h($szadUrl) ?>" class="btn-primary">🚀 جرّب مجاناً الآن</a>
      <a href="#pricing" class="btn-outline">💎 عرض الباقات</a>
    </div>
    <div class="hero-stats">
      <div class="hero-stat"><div class="val" data-count="50000">0</div><div class="lbl">مستخدم نشط</div></div>
      <div class="hero-stat"><div class="val" data-count="3">0</div><div class="lbl">أوضاع ذكاء اصطناعي</div></div>
      <div class="hero-stat"><div class="val" data-count="80">0</div><div class="lbl">أداة مجانية</div></div>
      <div class="hero-stat"><div class="val" data-count="99">0</div><div class="lbl">% رضا المستخدمين</div></div>
    </div>
  </div>
</section>

<!-- ══ DEMO TOOL ══════════════════════════════════════════════════════ -->
<section id="tool" style="background:var(--bg2);padding:80px 0">
  <div class="tool-inner">
    <div class="section-tag">جرّب الآن</div>
    <h2 class="section-title">اختبر صَعد AI مباشرة</h2>
    <p class="section-sub" style="margin:0 auto 10px">
      اختر الوضع الذي تريده وتفاعل مع أقوى نموذج ذكاء اصطناعي عربي.
    </p>

    <div class="mode-tabs">
      <button class="mode-tab active" onclick="setMode('normal',this)">💬 عادي</button>
      <button class="mode-tab" onclick="setMode('search',this)">🔍 بحث</button>
      <button class="mode-tab" onclick="setMode('think',this)">🧠 تفكير</button>
    </div>

    <div class="sz-demo">
      <div class="sz-demo-msgs" id="demo-msgs">
        <div class="demo-msg ai">مرحباً! أنا صَعد — كيف يمكنني مساعدتك اليوم؟</div>
      </div>
      <div class="sz-input-row">
        <textarea class="sz-input" id="demo-input" rows="2" placeholder="اكتب سؤالك هنا..."></textarea>
        <button class="sz-send-btn" onclick="demoSend()">جرّب ↗</button>
      </div>
    </div>
    <p style="font-size:13px;color:var(--muted);margin-top:14px">
      هذا عرض توضيحي — لتجربة كاملة
      <a href="<?= $h($szadUrl) ?>" style="color:var(--red2)">افتح صَعد AI</a>
    </p>
  </div>
</section>

<!-- ══ FEATURES ══════════════════════════════════════════════════════ -->
<section class="sz-section" id="features">
  <div class="reveal">
    <div class="section-tag">لماذا صَعد؟</div>
    <h2 class="section-title">12 ميزة تجعله الخيار الأمثل</h2>
    <p class="section-sub">مصمم للمستخدم العربي المحترف الذي يحتاج أداة حقيقية، لا مجرد دردشة.</p>
  </div>
  <div class="features-grid">
    <?php
    $feats = [
      ['🔍','بحث الويب الفوري','نتائج حقيقية ومحدّثة من الإنترنت — لا بيانات منتهية الصلاحية'],
      ['🧠','تفكير عميق','تحليل متعدد الخطوات لحل المشاكل المعقدة والأسئلة الأكاديمية'],
      ['🌍','عربي أولاً','صُمِّم مع الاعتناء باللغة العربية — فهم السياق والمعنى'],
      ['🔒','خصوصية تامة','لا نخزن محادثاتك على خوادم خارجية — جلستك ملكك'],
      ['⚡','استجابة فورية','تدفق النص كلمة بكلمة — لا انتظار لاكتمال الرد'],
      ['📚','ذاكرة طويلة','يتذكر سياق المحادثة كاملاً ويبني عليه'],
      ['🎯','دقة عالية','نماذج متعددة تعمل بالتوازي لضمان أدق إجابة ممكنة'],
      ['💼','للمحترفين','كتابة تقارير، تحليل بيانات، برمجة، ترجمة، وأكثر'],
      ['🔄','تبديل تلقائي','تبديل ذكي بين النماذج للحصول على أفضل نتيجة'],
      ['📱','يعمل على الجوال','واجهة مُحسَّنة للمحمول — استخدمه في أي مكان'],
      ['🌐','80+ أداة مجانية','اشتراكك يشمل جميع أدوات yassota.com'],
      ['🆘','دعم فوري','فريق دعم متخصص يرد خلال ساعات على استفساراتك'],
    ];
    foreach ($feats as [$icon,$name,$desc]): ?>
    <div class="feat-card reveal">
      <div class="feat-icon"><?= $icon ?></div>
      <div class="feat-name"><?= $h($name) ?></div>
      <div class="feat-desc"><?= $h($desc) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ══ MODES ══════════════════════════════════════════════════════════ -->
<div class="sz-section-full" id="modes">
  <div class="section-inner">
    <div class="reveal" style="text-align:center;margin-bottom:8px">
      <div class="section-tag">الأوضاع الثلاثة</div>
      <h2 class="section-title">وضع لكل حاجة</h2>
      <p class="section-sub" style="margin:0 auto">اختر الوضع الذي يناسب مهمتك وصَعد يتكيف تلقائياً.</p>
    </div>
    <div class="modes-grid">
      <div class="mode-card reveal">
        <div class="mode-card-icon">💬</div>
        <div class="mode-card-name">الوضع العادي</div>
        <div class="mode-card-desc">
          محادثة طبيعية وسريعة — الخيار الأمثل للمهام اليومية من كتابة رسائل
          واستفسارات وترجمة وأسئلة سريعة. يستخدم أسرع النماذج وأكثرها استجابة.
        </div>
        <ul class="mode-feats">
          <li>استجابة أقل من ثانية</li>
          <li>محادثة متصلة وطبيعية</li>
          <li>GPT-4o / Claude Haiku</li>
          <li>مناسب للاستخدام اليومي</li>
        </ul>
      </div>
      <div class="mode-card reveal" style="border-color:var(--border2)">
        <div class="mode-card-icon">🔍</div>
        <div class="mode-card-name">وضع البحث</div>
        <div class="mode-card-desc">
          يبحث في الإنترنت فعلياً ويجمع معلومات محدّثة من مصادر موثوقة قبل
          الإجابة. مثالي للأخبار، الأسعار، البيانات الحديثة، والتحقق من المعلومات.
        </div>
        <ul class="mode-feats">
          <li>نتائج من الويب المباشر</li>
          <li>مصادر مذكورة مع الإجابة</li>
          <li>Perplexity / Gemini Flash</li>
          <li>تحديث فوري للمعلومات</li>
        </ul>
      </div>
      <div class="mode-card reveal">
        <div class="mode-card-icon">🧠</div>
        <div class="mode-card-name">وضع التفكير</div>
        <div class="mode-card-desc">
          يفكر بعمق قبل الإجابة — تحليل متعدد الخطوات، مراجعة الفرضيات،
          واستخلاص نتائج دقيقة. مثالي للمسائل الرياضية، التخطيط الاستراتيجي،
          والتحليل المعقد.
        </div>
        <ul class="mode-feats">
          <li>Claude Opus / o1 / Gemini Pro</li>
          <li>chain-of-thought مرئي</li>
          <li>دقة عالية في المسائل المعقدة</li>
          <li>مراجعة ذاتية للإجابات</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- ══ HOW IT WORKS ══════════════════════════════════════════════════ -->
<section class="sz-section" id="how">
  <div class="reveal">
    <div class="section-tag">كيف يعمل</div>
    <h2 class="section-title">خمس خطوات بسيطة</h2>
  </div>
  <div class="steps">
    <?php
    $steps = [
      ['سجّل بحساب Google','ادخل في ثوانٍ باستخدام بريدك الإلكتروني — لا كلمة مرور، لا تعقيد.'],
      ['اختر وضع المساعد','عادي للدردشة السريعة، بحث للمعلومات المحدّثة، تفكير للمسائل المعقدة.'],
      ['اكتب سؤالك بالعربية','اكتب بلهجتك أو بالفصحى — صَعد يفهم كليهما ويرد بأفضل طريقة.'],
      ['استقبل الإجابة فوراً','النص يتدفق كلمة بكلمة — ترى الإجابة وهي تُبنى أمامك.'],
      ['احفظ محادثاتك وشاركها','كل محادثاتك محفوظة ومنظمة — يمكنك الرجوع إليها في أي وقت.'],
    ];
    foreach ($steps as $i => [$title,$desc]): ?>
    <div class="step reveal">
      <div class="step-num"><?= $i+1 ?></div>
      <div class="step-body">
        <h3><?= $h($title) ?></h3>
        <p><?= $h($desc) ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ══ USE CASES ══════════════════════════════════════════════════════ -->
<div class="sz-section-full" id="use-cases">
  <div class="section-inner">
    <div class="reveal" style="text-align:center;margin-bottom:8px">
      <div class="section-tag">من يستخدم صَعد؟</div>
      <h2 class="section-title">لكل شخص وكل مجال</h2>
    </div>
    <div class="uc-grid">
      <?php
      $ucs = [
        ['🎓','الطلاب',['شرح المفاهيم الصعبة بأسلوب مبسط','حل المسائل الرياضية والعلمية','تلخيص الكتب والمقالات الأكاديمية','كتابة ملخصات للدراسة']],
        ['💼','المحترفون',['كتابة التقارير والعروض التقديمية','تحليل البيانات واستخلاص الأنماط','صياغة رسائل البريد الاحترافية','البحث في المصادر المتخصصة']],
        ['💻','المطورون',['مراجعة الكود وإصلاح الأخطاء','توليد سكريبتات وأكواد جاهزة','شرح المفاهيم البرمجية','توثيق المشاريع تلقائياً']],
        ['✍️','الكتّاب',['توليد أفكار للمقالات والقصص','إعادة صياغة النصوص وتحسينها','تدقيق لغوي ونحوي دقيق','SEO copywriting بالعربية']],
        ['🔬','الباحثون',['مراجعة الدراسات والمراجع','تلخيص الأوراق البحثية الطويلة','مقارنة النتائج والنظريات','اقتراح منهجيات البحث']],
        ['🏢','الشركات',['أتمتة الردود على استفسارات العملاء','إعداد الاستراتيجيات والخطط','تحليل المنافسين والسوق','إنشاء محتوى تسويقي']],
        ['🎨','المبدعون',['توليد أفكار إبداعية لمشاريع','كتابة سيناريوهات ومحتوى ترفيهي','ترجمة إبداعية بين اللغات','وصف الأعمال الفنية']],
        ['⚖️','القانونيون',['تلخيص العقود والوثائق القانونية','شرح المصطلحات القانونية','البحث في القضايا المشابهة','صياغة مسودات أولية']],
      ];
      foreach ($ucs as [$icon,$title,$items]): ?>
      <div class="uc-card reveal">
        <div class="uc-icon"><?= $icon ?></div>
        <div class="uc-title"><?= $h($title) ?></div>
        <ul class="uc-list">
          <?php foreach ($items as $item): ?>
          <li><?= $h($item) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ══ COMPARE ════════════════════════════════════════════════════════ -->
<section class="sz-section" id="compare">
  <div class="reveal">
    <div class="section-tag">مقارنة</div>
    <h2 class="section-title">صَعد vs المنافسون</h2>
    <p class="section-sub">لماذا يختار المستخدم العربي صَعد على الأدوات الأجنبية؟</p>
  </div>
  <div class="cmp-wrap reveal">
    <table class="cmp">
      <thead>
        <tr>
          <th>الميزة</th>
          <th style="color:var(--red2)">🔥 صَعد AI</th>
          <th>ChatGPT</th>
          <th>Gemini</th>
          <th>Copilot</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $rows = [
          ['واجهة عربية RTL كاملة','✓','✗','جزئي','✗'],
          ['بحث ويب مجاني','✓','مدفوع','✓','✓'],
          ['تفكير عميق مجاني','✓','مدفوع','مدفوع','جزئي'],
          ['دعم اللهجات العربية','✓','جزئي','جزئي','✗'],
          ['خصوصية البيانات العربية','✓','جزئي','جزئي','جزئي'],
          ['تبديل تلقائي بين النماذج','✓','✗','✗','✗'],
          ['اشتراك بالعملة الرقمية','✓','✗','✗','✗'],
          ['80+ أداة مجانية','✓','✗','✗','✗'],
        ];
        foreach ($rows as [$feat,$szad,$gpt,$gem,$cop]):
          $cls = fn($v) => $v==='✓'?'cmp-yes':($v==='✗'?'cmp-no':'cmp-part');
        ?>
        <tr>
          <td><?= $h($feat) ?></td>
          <td class="<?= $cls($szad) ?>"><?= $h($szad) ?></td>
          <td class="<?= $cls($gpt) ?>"><?= $h($gpt) ?></td>
          <td class="<?= $cls($gem) ?>"><?= $h($gem) ?></td>
          <td class="<?= $cls($cop) ?>"><?= $h($cop) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<!-- ══ PRICING ════════════════════════════════════════════════════════ -->
<div class="sz-section-full" id="pricing">
  <div class="section-inner">
    <div class="reveal" style="text-align:center;margin-bottom:8px">
      <div class="section-tag">الأسعار</div>
      <h2 class="section-title">خطط لكل احتياج</h2>
      <p class="section-sub" style="margin:0 auto">الدفع بالعملة الرقمية — USDT، BTC، ETH وأكثر من 50 عملة أخرى.</p>
    </div>
    <div class="pricing-grid">

      <div class="price-card reveal">
        <div class="price-name">مجاني</div>
        <div class="price-val"><sup>$</sup>0 <span>دائماً</span></div>
        <div class="price-desc">ابدأ بدون أي رسوم — لا بطاقة مطلوبة</div>
        <ul class="price-feats">
          <li>20 رسالة يومياً</li>
          <li>الوضع العادي فقط</li>
          <li>حفظ آخر 3 محادثات</li>
          <li>جميع الأدوات المجانية (80+)</li>
        </ul>
        <a href="<?= $h($szadUrl) ?>" class="price-btn price-btn-outline">ابدأ مجاناً</a>
      </div>

      <div class="price-card reveal featured">
        <div class="price-badge">الأكثر شيوعاً</div>
        <div class="price-name">بلاس</div>
        <div class="price-val"><sup>$</sup>5 <span>/ شهر</span></div>
        <div class="price-desc">للمستخدم الجاد الذي يحتاج المزيد</div>
        <ul class="price-feats">
          <li>500 رسالة / شهر</li>
          <li>بحث الويب مفعّل</li>
          <li>GPT-4o + Claude Sonnet</li>
          <li>حفظ غير محدود للمحادثات</li>
          <li>أولوية في الاستجابة</li>
        </ul>
        <a href="<?= $h($payUrl) ?>?tier=plus" class="price-btn price-btn-red">اشترك الآن</a>
      </div>

      <div class="price-card reveal">
        <div class="price-name">برو</div>
        <div class="price-val"><sup>$</sup>10 <span>/ شهر</span></div>
        <div class="price-desc">للمحترفين ومتطلبات الأعمال</div>
        <ul class="price-feats">
          <li>2,000 رسالة / شهر</li>
          <li>جميع الأوضاع الثلاثة</li>
          <li>GPT-4o + Claude Opus + Gemini Pro</li>
          <li>تفكير عميق غير محدود</li>
          <li>API مباشر للمطورين</li>
        </ul>
        <a href="<?= $h($payUrl) ?>?tier=pro" class="price-btn price-btn-red">اشترك الآن</a>
      </div>

      <div class="price-card reveal">
        <div class="price-name">بريميوم</div>
        <div class="price-val"><sup>$</sup>20 <span>/ شهر</span></div>
        <div class="price-desc">لا حدود — قوة كاملة بلا قيود</div>
        <ul class="price-feats">
          <li>رسائل غير محدودة</li>
          <li>جميع النماذج + أحدث إصدار</li>
          <li>أولوية قصوى في الاستجابة</li>
          <li>دعم مخصص 24/7</li>
          <li>تكامل Webhook</li>
          <li>جميع أدوات yassota مجاناً</li>
        </ul>
        <a href="<?= $h($payUrl) ?>?tier=premium" class="price-btn price-btn-red">اشترك الآن</a>
      </div>

    </div>
    <p style="text-align:center;font-size:13px;color:var(--muted);margin-top:20px">
      اشتراكك يشمل <strong style="color:#fff">جميع أدوات yassota.com</strong> — أكثر من 80 أداة مجانية
    </p>
  </div>
</div>

<!-- ══ TESTIMONIALS ══════════════════════════════════════════════════ -->
<section class="sz-section" id="testimonials">
  <div class="reveal">
    <div class="section-tag">آراء المستخدمين</div>
    <h2 class="section-title">ماذا يقولون عن صَعد؟</h2>
  </div>
  <div class="testi-grid">
    <?php
    $testis = [
      ['م.أحمد السعيد','مهندس برمجيات — السعودية','جربت ChatGPT وClaude لكن صَعد الوحيد اللي يفهمني بالعربي ويعطيني إجابات دقيقة في الكود. أداة لا غنى عنها في عملي.',5],
      ['فاطمة المحمود','طالبة دكتوراه — مصر','وضع التفكير العميق غيّر طريقة بحثي تماماً. الآن أستخدمه لتحليل الأوراق البحثية واستخلاص الأنماط. لم أتوقع هذا المستوى.',5],
      ['خالد الراشد','رجل أعمال — الإمارات','أصبحت أحضّر تقاريري واجتماعاتي بنصف الوقت. صَعد يفهم السياق التجاري العربي بشكل لم أره في أي أداة أخرى.',5],
      ['نورا القحطاني','مدونة ومحتوى — الكويت','للكتابة بالعربية لا يوجد منافس. يساعدني في الأفكار والصياغة والSEO — وكل شيء بعربية فصيحة راقية.',5],
      ['عمر الزهراني','مطور تطبيقات — السعودية','الـAPI والتكامل المباشر في الخطة البرو أنقذا مشروعي. الآن أبني تطبيقات تستخدم صَعد كمحرك AI خلفي.',4],
      ['ريم الشمري','معلمة — قطر','أستخدمه لتحضير شرح المناهج بطريقة ممتعة. الطلاب يحبون الأمثلة التي يولدها — بسيطة وواضحة وبالعربية الفصيحة.',5],
      ['باسل الحموي','محلل مالي — لبنان','بحث الويب في صَعد يعطيني آخر الأسعار والأخبار المالية مباشرة في المحادثة. لا أحتاج فتح عشرات التبويبات بعد الآن.',5],
      ['منى العتيبي','كاتبة قصص — السعودية','أكتب القصص بمساعدة صَعد وهو يحترم أسلوبي ولا يفرض نمطاً غريباً. الأفضل للكتابة الإبداعية بالعربية.',5],
    ];
    foreach ($testis as [$name,$role,$text,$stars]): ?>
    <div class="testi-card reveal">
      <div class="testi-stars"><?= str_repeat('★',$stars) . str_repeat('☆',5-$stars) ?></div>
      <div class="testi-text">"<?= $h($text) ?>"</div>
      <div class="testi-author">
        <div class="testi-avatar"><?= mb_substr($name,0,1) ?></div>
        <div>
          <div class="testi-name"><?= $h($name) ?></div>
          <div class="testi-role"><?= $h($role) ?></div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ══ TUTORIALS ══════════════════════════════════════════════════════ -->
<div class="sz-section-full" id="tutorials">
  <div class="section-inner">
    <div class="reveal" style="text-align:center;margin-bottom:8px">
      <div class="section-tag">دروس تعليمية</div>
      <h2 class="section-title">تعلّم كيف تستفيد أكثر من صَعد</h2>
    </div>
    <div class="tutorial-list">

      <details class="tutorial reveal">
        <summary>كيف تكتب سؤالاً ممتازاً يحصل على أفضل إجابة؟</summary>
        <div class="tutorial-body">
          <p>جودة إجابة صَعد تعتمد مباشرة على جودة سؤالك. اتبع هذه المبادئ للحصول على أفضل نتيجة:</p>
          <h4>1. كن محدداً في سؤالك</h4>
          <p>بدلاً من "اشرح لي التسويق الرقمي"، اكتب "اشرح لي كيف أبني حملة إعلانية على Google Ads لمتجر إلكتروني يبيع ملابس عربية، ميزانية 1000 دولار شهرياً، الجمهور السعودي والإماراتي".</p>
          <h4>2. أعطِ السياق اللازم</h4>
          <p>أخبر صَعد من أنت وما هدفك: "أنا مطور مبتدئ يتعلم Python، أريد كوداً بسيطاً يقرأ ملف CSV ويحسب المتوسط". السياق يضاعف جودة الإجابة.</p>
          <h4>3. حدّد نوع الإجابة</h4>
          <ul>
            <li>"أعطني قائمة نقاط" — للمعلومات المنظمة</li>
            <li>"اشرح لمبتدئ" — للتبسيط</li>
            <li>"كن مختصراً" — لإجابات سريعة</li>
            <li>"تعمّق في التفاصيل" — للتحليل الشامل</li>
          </ul>
          <h4>4. استخدم الوضع المناسب</h4>
          <p>للأسئلة العلمية والتحليلية استخدم وضع <strong>التفكير</strong>. للأخبار والأسعار الحديثة استخدم <strong>البحث</strong>. للمحادثة العادية والكتابة استخدم <strong>العادي</strong>.</p>
        </div>
      </details>

      <details class="tutorial reveal">
        <summary>استخدام وضع البحث للحصول على معلومات محدّثة</summary>
        <div class="tutorial-body">
          <p>وضع البحث في صَعد يختلف جذرياً عن أي أداة أخرى — إليك كيف تستخدمه بفعالية:</p>
          <h4>متى تستخدم وضع البحث؟</h4>
          <ul>
            <li>أسعار العملات والأسهم والعقارات</li>
            <li>آخر الأخبار والأحداث الجارية</li>
            <li>معلومات عن منتجات حديثة</li>
            <li>إحصاءات ودراسات جديدة</li>
            <li>التحقق من صحة معلومة</li>
          </ul>
          <h4>نموذج سؤال مثالي لوضع البحث</h4>
          <p><code>ما هو سعر الذهب اليوم؟ قارنه بمتوسط الشهر الماضي وأخبرني هل هو وقت مناسب للشراء.</code></p>
          <h4>فهم النتائج</h4>
          <p>صَعد يذكر المصادر مع كل معلومة — تحقق دائماً من المصدر للمعلومات الحساسة مثل القرارات الطبية أو القانونية.</p>
        </div>
      </details>

      <details class="tutorial reveal">
        <summary>الاستفادة من وضع التفكير في المسائل المعقدة</summary>
        <div class="tutorial-body">
          <p>وضع التفكير يمنح صَعد وقتاً إضافياً ليفكر بعمق قبل الإجابة. هذا يحسن الدقة بشكل ملحوظ في:</p>
          <h4>أفضل حالات الاستخدام</h4>
          <ul>
            <li>المسائل الرياضية والإحصاء</li>
            <li>الأسئلة الاستراتيجية متعددة الأبعاد</li>
            <li>تحليل إيجابيات وسلبيات قرار مهم</li>
            <li>إيجاد الأخطاء المنطقية في حجة</li>
            <li>التخطيط للمشاريع المعقدة</li>
          </ul>
          <h4>مثال عملي</h4>
          <p><code>أملك متجراً إلكترونياً يبيع 300 منتج ولكنه لا يحقق أرباحاً كافية. تكلفة الإعلانات عالية والمنافسة شديدة. ساعدني في بناء استراتيجية تحسّن الربحية خلال 3 أشهر.</code></p>
          <p>صَعد سيفكر في عدة محاور: تحليل التكاليف، استراتيجيات تخفيض الإعلانات، تحسين التحويل، وبناء ولاء العملاء.</p>
        </div>
      </details>

      <details class="tutorial reveal">
        <summary>كيف تدفع وتشترك بالعملة الرقمية؟</summary>
        <div class="tutorial-body">
          <p>الدفع في صَعد يتم عبر NOWPayments ويدعم أكثر من 50 عملة رقمية:</p>
          <h4>خطوات الاشتراك</h4>
          <ul>
            <li>1. سجّل دخول بـ Google</li>
            <li>2. اضغط "اشترك الآن" في الباقة المناسبة</li>
            <li>3. اختر عملتك الرقمية (USDT موصى به)</li>
            <li>4. أرسل المبلغ المحدد للعنوان الظاهر</li>
            <li>5. يتفعّل اشتراكك تلقائياً خلال 1-5 دقائق</li>
          </ul>
          <h4>عملات مدعومة</h4>
          <p>USDT (TRC20/ERC20) • BTC • ETH • BNB • LTC • DOGE • SOL • ADA وأكثر من 45 عملة أخرى.</p>
          <h4>مشكلة في الدفع؟</h4>
          <p>تواصل معنا عبر <a href="mailto:info@yassota.com">info@yassota.com</a> مع ذكر رقم المعاملة وسنحل المشكلة خلال 24 ساعة.</p>
        </div>
      </details>

      <details class="tutorial reveal">
        <summary>نصائح للكتابة والمحتوى باستخدام صَعد</summary>
        <div class="tutorial-body">
          <p>صَعد من أفضل الأدوات للكتابة بالعربية — إليك كيف تستفيد منه في إنتاج محتوى احترافي:</p>
          <h4>أنواع المحتوى التي يتقنها صَعد</h4>
          <ul>
            <li>المقالات التقنية والتعليمية</li>
            <li>منشورات وسائل التواصل الاجتماعي</li>
            <li>رسائل تسويقية وإعلانات</li>
            <li>تقارير الأعمال والعروض</li>
            <li>القصص القصيرة والمحتوى الإبداعي</li>
          </ul>
          <h4>أسلوب الطلب الأمثل للمحتوى</h4>
          <p>حدّد: الجمهور المستهدف، نبرة الصوت (رسمية/ودية)، الطول المطلوب، والهدف من المحتوى (إقناع/إعلام/ترفيه).</p>
          <h4>مثال</h4>
          <p><code>اكتب منشور LinkedIn احترافي عن أهمية الذكاء الاصطناعي في الأعمال، 150 كلمة، نبرة واثقة، للجمهور السعودي من رجال الأعمال.</code></p>
        </div>
      </details>

    </div>
  </div>
</div>

<!-- ══ FAQ ════════════════════════════════════════════════════════════ -->
<section class="sz-section" id="faq">
  <div class="reveal">
    <div class="section-tag">الأسئلة الشائعة</div>
    <h2 class="section-title">كل ما تريد معرفته</h2>
  </div>
  <div class="faq-list">
    <?php
    $faqs = [
      ['ما الفرق بين صَعد AI وياسمين AI؟','صَعد AI يركز على البحث والتحليل المتعمق مع وضع البحث في الويب والتفكير العميق، بينما ياسمين AI مصممة للمحادثة العاطفية والدعم النفسي. كلاهما يعملان باللغة العربية لكن لأغراض مختلفة.'],
      ['هل أحتاج إلى بطاقة ائتمان للبدء؟','لا. الخطة المجانية متاحة فوراً بتسجيل دخول Google — لا بطاقة، لا معلومات دفع، لا حد زمني.'],
      ['كيف يعمل بحث الويب في صَعد؟','في وضع البحث، صَعد يستعلم عن المعلومات من مصادر موثوقة عبر الإنترنت قبل صياغة إجابته، ويذكر المصادر مع كل معلومة للتحقق منها.'],
      ['هل محادثاتي خاصة ومشفرة؟','نعم. لا نبيع بياناتك لأطراف ثالثة ولا نستخدمها لتدريب النماذج. محادثاتك تُخزَّن مشفرة وتُحذف بطلبك فوراً.'],
      ['ما العملات الرقمية المقبولة للدفع؟','ندعم USDT (TRC20 وERC20)، BTC، ETH، BNB، LTC، DOGE، SOL، ADA، وأكثر من 50 عملة رقمية أخرى عبر NOWPayments.'],
      ['هل يمكن إلغاء الاشتراك في أي وقت؟','نعم. الاشتراك شهري ولا يتجدد تلقائياً — تدفع يدوياً كل شهر وتتوقف متى شئت.'],
      ['كم من الوقت حتى يتفعل اشتراكي؟','عادةً من 1 إلى 5 دقائق بعد استلام تأكيد الدفع. في حالات نادرة قد يصل إلى 30 دقيقة.'],
      ['هل يعمل صَعد مع اللهجات العربية؟','نعم. صَعد يفهم اللهجات العربية الرئيسية: السعودية، المصرية، الشامية، الخليجية والمغاربية — ويرد بالفصحى أو بلهجتك حسب تفضيلك.'],
      ['ما الفرق بين خطة برو والبريميوم؟','برو: 2000 رسالة مع وصول API، بريميوم: رسائل غير محدودة + أحدث النماذج + دعم مخصص + تكامل Webhook + جميع أدوات yassota مجاناً.'],
      ['هل يمكنني استخدام صَعد على الجوال؟','نعم. الواجهة مُحسَّنة بالكامل للأجهزة المحمولة وتعمل مباشرة من المتصفح — لا حاجة لتحميل تطبيق.'],
      ['هل يدعم صَعد قراءة الملفات والصور؟','قراءة الصور متاحة في الخطط المدفوعة. دعم الملفات (PDF, Word) قيد التطوير وسيُضاف قريباً.'],
      ['ما هي حدود الرسائل في الخطة المجانية؟','20 رسالة يومياً في الوضع العادي فقط. ترقية أي خطة مدفوعة ترفع الحد فوراً.'],
      ['هل يمكن استخدام صَعد في تطبيقي عبر API؟','نعم، للمشتركين في خطتَي برو والبريميوم. نوفر API key مباشر يمكن دمجه في أي تطبيق أو موقع.'],
      ['ماذا يحدث إذا انتهت رسائلي الشهرية؟','يمكنك الترقية إلى خطة أعلى أو الانتظار حتى التجديد الشهري. لن يُقطع اتصالك فجأة — ستُعلَم قبل نفاد الرصيد.'],
      ['كيف أتواصل مع الدعم الفني؟','عبر البريد الإلكتروني info@yassota.com — نرد خلال 24 ساعة في أيام العمل. المشتركون في البريميوم يحصلون على أولوية ودعم خلال ساعات.'],
      ['هل هناك خصم للمؤسسات والجامعات؟','نعم، تواصل معنا لمناقشة خطط المجموعات والمؤسسات مع أسعار مخصصة.'],
      ['ما النماذج التي يستخدمها صَعد؟','نستخدم مزيجاً من GPT-4o وClaude Opus وGemini Pro وPerplexity حسب الوضع المختار — دائماً أحدث النماذج المتاحة.'],
      ['هل يمكنني تحميل سجل محادثاتي؟','نعم، يمكن تصدير أي محادثة بصيغة نص من داخل الأداة.'],
      ['هل يعمل صَعد بدون إنترنت؟','لا، صَعد يتطلب اتصالاً بالإنترنت لأنه يعتمد على نماذج سحابية. لا يوجد وضع offline حالياً.'],
      ['هل تخططون لإضافة نماذج جديدة؟','نعم باستمرار. نضيف أحدث النماذج فور توفرها. المشتركون يحصلون على الوصول تلقائياً بمجرد الإضافة.'],
    ];
    foreach ($faqs as [$q,$a]): ?>
    <details class="faq reveal">
      <summary><?= $h($q) ?></summary>
      <div class="faq-ans"><?= $h($a) ?></div>
    </details>
    <?php endforeach; ?>
  </div>
</section>

<!-- ══ PRIVACY ════════════════════════════════════════════════════════ -->
<div class="legal-section" id="privacy">
  <div class="legal-inner reveal">
    <h2>🔒 سياسة الخصوصية</h2>
    <p><strong>آخر تحديث:</strong> <?= date('Y-m-d') ?></p>

    <h3>ما المعلومات التي نجمعها؟</h3>
    <p>نجمع فقط ما هو ضروري لتشغيل الخدمة:</p>
    <ul>
      <li>بريدك الإلكتروني واسمك من Google Sign-In</li>
      <li>سجل المحادثات (مشفر في قاعدة البيانات)</li>
      <li>بيانات الاشتراك وسجل المدفوعات (بدون تفاصيل البطاقة)</li>
      <li>سجلات الاستخدام الأساسية (الوقت، عدد الرسائل)</li>
    </ul>

    <h3>كيف نستخدم بياناتك؟</h3>
    <ul>
      <li>تشغيل الخدمة وتوفير الإجابات المطلوبة</li>
      <li>إدارة اشتراكك وسجل الدفع</li>
      <li>تحسين جودة الخدمة (إحصائيات مجمّعة مجهولة الهوية فقط)</li>
      <li>إرسال إشعارات الاشتراك المهمة</li>
    </ul>

    <h3>هل نشارك بياناتك؟</h3>
    <p>
      <strong>لا نبيع بياناتك أبداً.</strong> نشارك بيانات محدودة فقط مع:
      NOWPayments لمعالجة الدفع، Google للمصادقة، ومزودي النماذج (OpenRouter)
      لمعالجة طلباتك — وفق عقود صارمة تحمي خصوصيتك.
    </p>

    <h3>تخزين البيانات وحمايتها</h3>
    <p>
      جميع البيانات مشفرة أثناء النقل (HTTPS/TLS 1.3) وأثناء التخزين.
      المحادثات لا تُستخدم لتدريب أي نموذج ذكاء اصطناعي.
      يمكنك طلب حذف بياناتك كاملاً في أي وقت.
    </p>

    <h3>ملفات تعريف الارتباط (Cookies)</h3>
    <p>
      نستخدم cookies للجلسة فقط (تسجيل الدخول) ولا نستخدم cookies تتبعية.
      Google Analytics مُعطَّل على هذه الصفحة.
    </p>

    <h3>حقوقك (GDPR)</h3>
    <ul>
      <li>الحق في الوصول إلى بياناتك</li>
      <li>الحق في التصحيح والحذف ("الحق في النسيان")</li>
      <li>الحق في نقل البيانات</li>
      <li>الحق في الاعتراض على المعالجة</li>
    </ul>
    <p>لممارسة أي من هذه الحقوق: <a href="mailto:privacy@yassota.com">privacy@yassota.com</a></p>
  </div>
</div>

<!-- ══ TERMS ══════════════════════════════════════════════════════════ -->
<div class="legal-section" id="terms">
  <div class="legal-inner reveal">
    <h2>📜 شروط الاستخدام</h2>
    <p><strong>آخر تحديث:</strong> <?= date('Y-m-d') ?></p>

    <h3>1. قبول الشروط</h3>
    <p>
      باستخدامك لصَعد AI توافق على هذه الشروط بالكامل. إذا كنت لا توافق على أي بند،
      الرجاء التوقف عن استخدام الخدمة.
    </p>

    <h3>2. استخدام الخدمة</h3>
    <p>يُسمح لك باستخدام صَعد AI للأغراض المشروعة القانونية فقط. يُحظر تحديداً:</p>
    <ul>
      <li>توليد محتوى ضار أو احتيالي أو مضلل</li>
      <li>انتهاك حقوق الملكية الفكرية للآخرين</li>
      <li>التحرش، التهديد، أو إيذاء الأفراد</li>
      <li>محاولة اختراق أو تعطيل الخدمة</li>
      <li>إعادة بيع أو مشاركة حساب واحد مع عدة مستخدمين</li>
      <li>توليد محتوى يخالف القوانين المحلية لبلدك</li>
    </ul>

    <h3>3. الاشتراكات والمدفوعات</h3>
    <ul>
      <li>الاشتراكات شهرية وتتجدد بالدفع اليدوي فقط</li>
      <li>المدفوعات بالعملة الرقمية غير قابلة للاسترداد بعد التأكيد</li>
      <li>في حالة مشاكل تقنية من جهتنا نوفر تمديداً أو اعتماداً مقابل الفترة المتضررة</li>
      <li>لا يسمح بالاسترداد النقدي بعد استخدام الخدمة</li>
    </ul>

    <h3>4. الملكية الفكرية</h3>
    <p>
      المحتوى الذي تولّده بمساعدة صَعد هو ملكك أنت — لا ندّعي أي حقوق عليه.
      واجهة ومصدر صَعد AI محمية بحقوق النشر لـ yassota.com.
    </p>

    <h3>5. إخلاء المسؤولية</h3>
    <p>
      صَعد AI يوفر معلومات للمساعدة وليس بديلاً عن المتخصصين في الطب، القانون، المال،
      أو أي مجال متخصص آخر. قرارات مهمة تستلزم دائماً مراجعة متخصص مؤهل.
    </p>
    <p>
      لا نضمن دقة 100% لجميع المعلومات — التحقق المستقل مسؤوليتك خاصة للقرارات الحساسة.
    </p>

    <h3>6. تحديد المسؤولية</h3>
    <p>
      لن نكون مسؤولين عن أي أضرار غير مباشرة أو تبعية ناتجة عن استخدام الخدمة.
      أقصى مسؤوليتنا تساوي ما دفعته خلال الشهر الأخير من الاشتراك.
    </p>

    <h3>7. تعديل الشروط</h3>
    <p>
      نحتفظ بحق تعديل هذه الشروط مع إشعار مسبق بـ 7 أيام عبر البريد الإلكتروني.
      استمرارك في استخدام الخدمة يعني قبولك للشروط المحدّثة.
    </p>
  </div>
</div>

<!-- ══ CONTACT ════════════════════════════════════════════════════════ -->
<section class="sz-section" id="contact">
  <div class="reveal">
    <div class="section-tag">تواصل معنا</div>
    <h2 class="section-title">نحن هنا للمساعدة</h2>
    <p class="section-sub" style="margin-bottom:32px">سؤال؟ مشكلة؟ اقتراح؟ نرد خلال 24 ساعة.</p>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;max-width:860px" class="contact-grid reveal">
    <div>
      <form class="contact-form" onsubmit="submitContact(event)">
        <input type="text" placeholder="اسمك" required id="cf-name">
        <input type="email" placeholder="بريدك الإلكتروني" required id="cf-email">
        <textarea placeholder="رسالتك..." required id="cf-msg"></textarea>
        <button type="submit" class="btn-primary" style="border:none;width:100%;justify-content:center">إرسال الرسالة</button>
        <div id="cf-status" style="font-size:13px;color:var(--muted);display:none"></div>
      </form>
    </div>
    <div style="display:flex;flex-direction:column;gap:20px;justify-content:center">
      <div>
        <div style="font-size:13px;color:var(--muted);margin-bottom:4px">📧 البريد الإلكتروني</div>
        <div style="color:#fff">info@yassota.com</div>
      </div>
      <div>
        <div style="font-size:13px;color:var(--muted);margin-bottom:4px">🌐 الموقع الرئيسي</div>
        <div><a href="<?= $h($siteUrl) ?>" style="color:var(--red2)"><?= $h($siteUrl) ?></a></div>
      </div>
      <div>
        <div style="font-size:13px;color:var(--muted);margin-bottom:4px">⏱ وقت الرد</div>
        <div style="color:#fff">خلال 24 ساعة في أيام العمل</div>
      </div>
      <div>
        <div style="font-size:13px;color:var(--muted);margin-bottom:8px">🔗 تابعنا</div>
        <div style="display:flex;gap:12px">
          <a href="#" style="color:var(--muted);font-size:13px">Twitter/X</a>
          <a href="#" style="color:var(--muted);font-size:13px">YouTube</a>
          <a href="#" style="color:var(--muted);font-size:13px">Telegram</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ FOOTER ═════════════════════════════════════════════════════════ -->
<footer class="sz-footer">
  <div class="footer-links">
    <a href="#home">الرئيسية</a>
    <a href="#features">المميزات</a>
    <a href="#pricing">الأسعار</a>
    <a href="#faq">الأسئلة</a>
    <a href="#privacy">الخصوصية</a>
    <a href="#terms">الشروط</a>
    <a href="#contact">تواصل</a>
    <a href="<?= $h($siteUrl) ?>">yassota.com</a>
    <a href="<?= $h($szadUrl) ?>">صَعد AI</a>
  </div>
  <div class="footer-copy">
    © <?= date('Y') ?> yassota.com — جميع الحقوق محفوظة
  </div>
</footer>

<button class="back-top" id="btt" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="للأعلى">↑</button>

<script>
/* ── Canvas Particles ─────────────────────────────────────────── */
(function(){
  var cv = document.getElementById('sz-canvas');
  var ctx = cv.getContext('2d');
  var W, H, pts = [];
  function resize(){
    W = cv.width  = cv.offsetWidth;
    H = cv.height = cv.offsetHeight;
  }
  resize();
  window.addEventListener('resize', resize);
  for(var i=0;i<90;i++){
    pts.push({
      x: Math.random()*2000,
      y: Math.random()*2000,
      vx:(Math.random()-.5)*.4,
      vy:(Math.random()-.5)*.4,
      r: Math.random()*1.8+.4,
      a: Math.random()
    });
  }
  var raf;
  function draw(){
    ctx.clearRect(0,0,W,H);
    ctx.fillStyle='rgba(197,0,0,.08)';
    pts.forEach(function(p){
      p.x+=p.vx; p.y+=p.vy;
      if(p.x<0)p.x=W; if(p.x>W)p.x=0;
      if(p.y<0)p.y=H; if(p.y>H)p.y=0;
      ctx.beginPath();
      ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
      ctx.globalAlpha=p.a*.7;
      ctx.fill();
    });
    /* lines */
    ctx.strokeStyle='rgba(197,0,0,.06)';
    ctx.lineWidth=.6;
    for(var i=0;i<pts.length;i++){
      for(var j=i+1;j<pts.length;j++){
        var dx=pts[i].x-pts[j].x, dy=pts[i].y-pts[j].y;
        var d=Math.sqrt(dx*dx+dy*dy);
        if(d<120){
          ctx.globalAlpha=(1-d/120)*.25;
          ctx.beginPath();ctx.moveTo(pts[i].x,pts[i].y);ctx.lineTo(pts[j].x,pts[j].y);ctx.stroke();
        }
      }
    }
    ctx.globalAlpha=1;
    raf=requestAnimationFrame(draw);
  }
  draw();
})();

/* ── Counter animation ────────────────────────────────────────── */
function animCount(el,target){
  var start=0,dur=1800,step=16;
  var t=setInterval(function(){
    start+=target/Math.round(dur/step);
    if(start>=target){start=target;clearInterval(t);}
    el.textContent=Math.round(start).toLocaleString('ar-EG')+'+';
  },step);
}
var counted=false;
function countIfVisible(){
  if(counted)return;
  var stats=document.querySelector('.hero-stats');
  if(!stats)return;
  var r=stats.getBoundingClientRect();
  if(r.top<window.innerHeight*.95){
    counted=true;
    document.querySelectorAll('[data-count]').forEach(function(el){
      animCount(el,+el.dataset.count);
    });
  }
}
window.addEventListener('scroll',countIfVisible,{passive:true});
countIfVisible();

/* ── Reveal on scroll ─────────────────────────────────────────── */
var revObs=new IntersectionObserver(function(entries){
  entries.forEach(function(e){if(e.isIntersecting)e.target.classList.add('in');});
},{threshold:.12});
document.querySelectorAll('.reveal').forEach(function(el){revObs.observe(el);});

/* ── Back to top ──────────────────────────────────────────────── */
var btt=document.getElementById('btt');
window.addEventListener('scroll',function(){
  btt.classList.toggle('visible',window.scrollY>400);
},{passive:true});

/* ── Demo mode selector ───────────────────────────────────────── */
var curMode='normal';
function setMode(m,btn){
  curMode=m;
  document.querySelectorAll('.mode-tab').forEach(function(b){b.classList.remove('active');});
  btn.classList.add('active');
  var msgs=document.getElementById('demo-msgs');
  var greetings={
    normal:'مرحباً! أنا صَعد في الوضع العادي — كيف يمكنني مساعدتك؟',
    search:'وضع البحث مفعّل — اسألني عن أي معلومة حديثة وسأبحث عنها فوراً!',
    think:'وضع التفكير العميق جاهز — أرسل مسألتك المعقدة وسأحللها خطوة بخطوة.'
  };
  msgs.innerHTML='<div class="demo-msg ai">'+greetings[m]+'</div>';
}
function demoSend(){
  var inp=document.getElementById('demo-input');
  var val=inp.value.trim();
  if(!val)return;
  var msgs=document.getElementById('demo-msgs');
  msgs.innerHTML+='<div class="demo-msg user">'+escHtml(val)+'</div>';
  inp.value='';
  setTimeout(function(){
    var replies={
      normal:'شكراً على سؤالك! هذا مجرد عرض توضيحي. للإجابة الكاملة افتح صَعد AI مباشرة.',
      search:'كنت سأبحث في الويب عن "'+escHtml(val)+'". افتح صَعد AI للبحث الفعلي!',
      think:'سؤال رائع يستحق تفكيراً عميقاً! افتح صَعد AI لتحليل شامل متعدد الخطوات.'
    };
    msgs.innerHTML+='<div class="demo-msg ai">'+replies[curMode]+'</div>';
    msgs.scrollTop=msgs.scrollHeight;
  },600);
}
function escHtml(s){
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
document.getElementById('demo-input').addEventListener('keydown',function(e){
  if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();demoSend();}
});

/* ── Contact form (mailto fallback) ──────────────────────────── */
function submitContact(e){
  e.preventDefault();
  var name=document.getElementById('cf-name').value;
  var email=document.getElementById('cf-email').value;
  var msg=document.getElementById('cf-msg').value;
  var st=document.getElementById('cf-status');
  var mailLink='mailto:info@yassota.com?subject=رسالة من '+encodeURIComponent(name)+'&body='+encodeURIComponent(msg+'\n\nمن: '+name+' ('+email+')');
  window.location.href=mailLink;
  st.style.display='block';
  st.textContent='تم فتح برنامج البريد — إذا لم يفتح، راسلنا على info@yassota.com';
}

/* ── Responsive contact grid ─────────────────────────────────── */
(function(){
  var cg=document.querySelector('.contact-grid');
  if(!cg)return;
  function check(){cg.style.gridTemplateColumns=window.innerWidth<640?'1fr':'1fr 1fr';}
  check();window.addEventListener('resize',check,{passive:true});
})();
</script>
</body>
</html>
