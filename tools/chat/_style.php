<?php
/**
 * Yasmin chat — visual system.
 *
 * Shared by index.php (the chat app) and auth.php (sign in / create account)
 * so both screens speak the same language.
 *
 * Direction: "ليل الياسمين" — jasmine blooms at night and is white with a gold
 * centre, so the app is a dark night-violet instrument sitting on the light
 * article page, with jasmine rose and gold as the two accents. The split is
 * deliberate: the app reads as a device, the SEO article below it reads as a
 * document.
 */
function ys_styles(): void
{
    echo <<<'CSS'
<style>
:root{
  /* Night ground */
  --ys-night:   #0c0a1d;
  --ys-night-2: #141130;
  --ys-night-3: #1d1943;
  --ys-line:    rgba(255,255,255,.085);
  --ys-line-2:  rgba(255,255,255,.16);

  /* Jasmine accents */
  --ys-violet:  #7c5cff;
  --ys-violet-2:#a78bfa;
  --ys-rose:    #ff6ba8;
  --ys-gold:    #ffc86b;

  /* Text on night */
  --ys-mist:    #ece9fb;
  --ys-mist-2:  rgba(236,233,251,.64);
  --ys-mist-3:  rgba(236,233,251,.40);

  --ys-r:       14px;
  --ys-r-lg:    22px;
  --ys-ease:    cubic-bezier(.4,0,.2,1);
  --ys-spring:  cubic-bezier(.34,1.56,.64,1);
}

/* ── Shell ─────────────────────────────────────────────────────────
   Height is derived from --t-header-h (set in tools/_base.php) so the
   app can never drift out of sync with the fixed site header again.
   dvh, not vh, so mobile browser chrome collapsing does not clip it.  */
.ys-wrap{max-width:1080px;margin:0 auto;padding:16px 16px 0}
.ys-shell{
  height:clamp(520px, calc(100dvh - var(--t-header-h,62px) - 104px), 880px);
  display:flex;
  background:var(--ys-night);
  border:1px solid var(--ys-line);
  border-radius:var(--ys-r-lg);
  overflow:hidden;
  position:relative;
  box-shadow:0 24px 70px -20px rgba(28,10,80,.45), 0 2px 8px rgba(0,0,0,.06);
}
/* Ambient aurora behind everything in the shell */
.ys-shell::before{
  content:'';position:absolute;inset:-40%;pointer-events:none;z-index:0;
  background:
    radial-gradient(40% 40% at 20% 25%, rgba(124,92,255,.30), transparent 70%),
    radial-gradient(35% 35% at 78% 18%, rgba(255,107,168,.20), transparent 70%),
    radial-gradient(30% 30% at 60% 85%, rgba(255,200,107,.13), transparent 70%);
  animation:ysAurora 26s ease-in-out infinite alternate;
}
@keyframes ysAurora{
  0%  {transform:translate3d(0,0,0) scale(1)}
  50% {transform:translate3d(3%,-2%,0) scale(1.08)}
  100%{transform:translate3d(-3%,2%,0) scale(1.04)}
}

/* ── Sidebar ───────────────────────────────────────────────────── */
.ys-side{
  width:264px;flex-shrink:0;position:relative;z-index:2;
  display:flex;flex-direction:column;
  background:rgba(10,8,26,.72);
  backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);
  border-inline-start:1px solid var(--ys-line);
}
.ys-side-top{padding:16px 14px 12px;border-bottom:1px solid var(--ys-line)}
.ys-new{
  width:100%;display:flex;align-items:center;justify-content:center;gap:8px;
  padding:11px 14px;border:none;border-radius:var(--ys-r);cursor:pointer;
  font-family:inherit;font-size:13.5px;font-weight:700;color:#fff;
  background:linear-gradient(135deg,var(--ys-violet),var(--ys-rose));
  background-size:180% 180%;
  box-shadow:0 6px 18px -6px rgba(124,92,255,.75);
  transition:transform .2s var(--ys-spring), box-shadow .2s var(--ys-ease), background-position .5s var(--ys-ease);
}
.ys-new:hover{transform:translateY(-2px);background-position:100% 0;box-shadow:0 10px 24px -6px rgba(255,107,168,.6)}
.ys-new:active{transform:translateY(0)}
.ys-side-label{
  padding:14px 16px 6px;font-size:10.5px;font-weight:800;letter-spacing:.12em;
  color:var(--ys-mist-3);text-transform:uppercase;
}
.ys-convs{flex:1;overflow-y:auto;padding:0 8px 12px;overscroll-behavior:contain}
.ys-convs::-webkit-scrollbar{width:4px}
.ys-convs::-webkit-scrollbar-thumb{background:var(--ys-line-2);border-radius:99px}
.ys-conv{
  display:flex;align-items:center;gap:8px;
  padding:10px 12px;margin-bottom:3px;border-radius:11px;cursor:pointer;
  position:relative;transition:background .16s var(--ys-ease),transform .16s var(--ys-ease);
}
.ys-conv:hover{background:rgba(255,255,255,.06);transform:translateX(-2px)}
.ys-conv.on{background:rgba(124,92,255,.18)}
/* Active marker rides the inline-start edge (RTL-aware) */
.ys-conv.on::before{
  content:'';position:absolute;inset-inline-start:0;top:50%;translate:0 -50%;
  width:3px;height:60%;border-radius:99px;
  background:linear-gradient(var(--ys-violet),var(--ys-rose));
}
.ys-conv .t{
  flex:1;min-width:0;font-size:13px;color:var(--ys-mist-2);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.ys-conv.on .t{color:var(--ys-mist);font-weight:600}
.ys-conv .n{
  font-size:10px;color:var(--ys-mist-3);background:rgba(255,255,255,.07);
  padding:2px 7px;border-radius:99px;flex-shrink:0;
}
.ys-del{
  opacity:0;background:none;border:none;cursor:pointer;color:var(--ys-rose);
  font-size:13px;padding:3px 5px;border-radius:6px;flex-shrink:0;
  transition:opacity .16s var(--ys-ease),background .16s var(--ys-ease);
}
.ys-conv:hover .ys-del{opacity:.75}
.ys-del:hover{opacity:1;background:rgba(255,107,168,.16)}
.ys-empty{padding:22px 14px;text-align:center;font-size:12px;color:var(--ys-mist-3);line-height:1.8}

/* ── Account block at the sidebar foot ─────────────────────────── */
.ys-acct{padding:12px;border-top:1px solid var(--ys-line)}
.ys-acct-row{display:flex;align-items:center;gap:10px;padding:8px;border-radius:12px}
.ys-avatar{
  width:34px;height:34px;border-radius:50%;flex-shrink:0;object-fit:cover;
  display:flex;align-items:center;justify-content:center;
  font-size:14px;font-weight:800;color:#fff;
  background:linear-gradient(135deg,var(--ys-violet),var(--ys-rose));
  border:1.5px solid var(--ys-line-2);
}
.ys-acct-meta{flex:1;min-width:0}
.ys-acct-name{font-size:12.5px;font-weight:700;color:var(--ys-mist);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ys-acct-mail{font-size:10.5px;color:var(--ys-mist-3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;direction:ltr;text-align:start}
.ys-signout{
  background:none;border:1px solid var(--ys-line-2);color:var(--ys-mist-2);
  border-radius:9px;padding:5px 9px;font-size:11px;font-family:inherit;cursor:pointer;
  transition:all .16s var(--ys-ease);flex-shrink:0;
}
.ys-signout:hover{border-color:var(--ys-rose);color:var(--ys-rose)}
.ys-signin-cta{
  display:flex;align-items:center;justify-content:center;gap:7px;
  padding:10px;border-radius:12px;font-size:12.5px;font-weight:700;
  color:var(--ys-mist);border:1px dashed var(--ys-line-2);
  transition:all .18s var(--ys-ease);
}
.ys-signin-cta:hover{border-color:var(--ys-violet);background:rgba(124,92,255,.12);border-style:solid}

/* ── Main column ───────────────────────────────────────────────── */
.ys-main{flex:1;display:flex;flex-direction:column;min-width:0;position:relative;z-index:1}

.ys-top{
  display:flex;align-items:center;gap:12px;padding:13px 18px;flex-shrink:0;
  border-bottom:1px solid var(--ys-line);
  background:rgba(12,10,29,.55);
  backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);
}
.ys-burger{
  display:none;width:36px;height:36px;flex-shrink:0;
  background:rgba(255,255,255,.07);border:1px solid var(--ys-line);
  border-radius:10px;color:var(--ys-mist);cursor:pointer;
  align-items:center;justify-content:center;
}
/* Jasmine bloom mark — CSS only, no image request */
.ys-bloom{
  width:42px;height:42px;border-radius:50%;flex-shrink:0;position:relative;
  display:flex;align-items:center;justify-content:center;font-size:20px;
  background:radial-gradient(circle at 35% 30%, #fff, #f3e9ff 45%, #d9c8ff);
  box-shadow:0 0 0 1.5px rgba(255,255,255,.35), 0 6px 20px -6px rgba(124,92,255,.9);
}
.ys-bloom::after{
  content:'';position:absolute;inset:-5px;border-radius:50%;
  border:1.5px solid rgba(255,200,107,.45);
  animation:ysHalo 3.4s ease-in-out infinite;
}
@keyframes ysHalo{
  0%,100%{transform:scale(1);opacity:.7}
  50%    {transform:scale(1.13);opacity:0}
}
.ys-id{flex:1;min-width:0}
.ys-id h2{font-size:15.5px;font-weight:800;color:var(--ys-mist);margin:0;display:flex;align-items:center;gap:7px}
.ys-id p{font-size:11.5px;color:var(--ys-mist-3);margin:2px 0 0}
.ys-live{width:7px;height:7px;border-radius:50%;background:#4ade80;flex-shrink:0;animation:ysPulse 2.2s ease-in-out infinite}
@keyframes ysPulse{
  0%,100%{box-shadow:0 0 0 0 rgba(74,222,128,.55)}
  70%    {box-shadow:0 0 0 7px rgba(74,222,128,0)}
}

/* ── Stream ────────────────────────────────────────────────────── */
.ys-stream{
  flex:1;overflow-y:auto;overscroll-behavior:contain;
  padding:20px 18px;display:flex;flex-direction:column;gap:14px;
  scroll-behavior:smooth;
}
.ys-stream::-webkit-scrollbar{width:5px}
.ys-stream::-webkit-scrollbar-thumb{background:var(--ys-line-2);border-radius:99px}

.ys-row{display:flex;gap:9px;align-items:flex-end;animation:ysRise .38s var(--ys-spring) both}
.ys-row.me{flex-direction:row-reverse}
@keyframes ysRise{
  from{opacity:0;transform:translateY(14px) scale(.96)}
  to  {opacity:1;transform:none}
}
.ys-pic{
  width:30px;height:30px;border-radius:50%;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-size:14px;
}
.ys-pic.ai{background:radial-gradient(circle at 35% 30%,#fff,#e4d7ff);box-shadow:0 3px 10px -3px rgba(124,92,255,.8)}
.ys-pic.me{background:rgba(255,255,255,.1);border:1px solid var(--ys-line-2);color:var(--ys-mist-2);font-size:13px;font-weight:700}
.ys-pic img{width:100%;height:100%;border-radius:50%;object-fit:cover}

.ys-bub{
  max-width:74%;padding:11px 15px;font-size:14.5px;line-height:1.8;
  white-space:pre-wrap;word-break:break-word;
}
.ys-bub.ai{
  background:rgba(255,255,255,.055);
  border:1px solid var(--ys-line);
  color:var(--ys-mist);
  border-radius:5px 18px 18px 18px;
  backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);
}
.ys-bub.me{
  background:linear-gradient(135deg,var(--ys-violet),#6d4bf0);
  color:#fff;border-radius:18px 5px 18px 18px;
  box-shadow:0 8px 22px -10px rgba(124,92,255,.9);
}
.ys-bub.bad{
  background:rgba(255,107,168,.1);border:1px solid rgba(255,107,168,.32);
  color:#ffb3cd;border-radius:14px;
}
.ys-time{font-size:10px;color:var(--ys-mist-3);margin-top:4px;padding:0 5px}

/* Streaming caret — only present while text is arriving */
.ys-bub.live::after{
  content:'';display:inline-block;width:2px;height:1em;
  vertical-align:-2px;margin-inline-start:3px;border-radius:1px;
  background:var(--ys-gold);animation:ysCaret 1s steps(2) infinite;
}
@keyframes ysCaret{0%,100%{opacity:1}50%{opacity:0}}

/* Typing petals */
.ys-typing{display:flex;gap:5px;align-items:center;padding:3px 2px}
.ys-typing i{
  width:7px;height:7px;border-radius:50%;display:block;
  background:linear-gradient(135deg,var(--ys-violet-2),var(--ys-rose));
  animation:ysBob 1.3s ease-in-out infinite;
}
.ys-typing i:nth-child(2){animation-delay:.18s}
.ys-typing i:nth-child(3){animation-delay:.36s}
@keyframes ysBob{
  0%,60%,100%{transform:translateY(0);opacity:.35}
  30%        {transform:translateY(-6px);opacity:1}
}

/* ── Welcome ───────────────────────────────────────────────────── */
.ys-welcome{
  margin:auto 0;text-align:center;padding:14px 8px;
  animation:ysRise .5s var(--ys-spring) both;
}
.ys-welcome-bloom{
  width:76px;height:76px;margin:0 auto 16px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;font-size:36px;
  background:radial-gradient(circle at 35% 30%,#fff,#f0e6ff 50%,#cfbcff);
  box-shadow:0 0 0 1.5px rgba(255,255,255,.4), 0 14px 40px -12px rgba(124,92,255,1);
  animation:ysFloat 5s ease-in-out infinite;
}
@keyframes ysFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-9px)}}
.ys-welcome h3{
  font-size:20px;font-weight:800;margin:0 0 8px;
  background:linear-gradient(100deg,var(--ys-mist),var(--ys-violet-2),var(--ys-gold),var(--ys-mist));
  background-size:250% auto;
  -webkit-background-clip:text;background-clip:text;
  -webkit-text-fill-color:transparent;color:transparent;
  animation:ysSheen 7s linear infinite;
}
@keyframes ysSheen{to{background-position:250% center}}
.ys-welcome p{font-size:13.5px;color:var(--ys-mist-2);line-height:1.85;max-width:440px;margin:0 auto 18px}
.ys-chips{display:flex;flex-wrap:wrap;gap:8px;justify-content:center;max-width:520px;margin:0 auto}
.ys-chip{
  background:rgba(255,255,255,.05);border:1px solid var(--ys-line-2);
  color:var(--ys-mist-2);border-radius:99px;padding:8px 15px;
  font-size:12.5px;font-weight:600;font-family:inherit;cursor:pointer;
  transition:all .2s var(--ys-ease);
  animation:ysRise .5s var(--ys-spring) both;
}
.ys-chip:nth-child(1){animation-delay:.05s}
.ys-chip:nth-child(2){animation-delay:.1s}
.ys-chip:nth-child(3){animation-delay:.15s}
.ys-chip:nth-child(4){animation-delay:.2s}
.ys-chip:nth-child(5){animation-delay:.25s}
.ys-chip:hover{
  color:#fff;border-color:transparent;transform:translateY(-2px);
  background:linear-gradient(135deg,var(--ys-violet),var(--ys-rose));
  box-shadow:0 8px 20px -8px rgba(124,92,255,.9);
}

/* ── Composer ──────────────────────────────────────────────────── */
.ys-composer{
  flex-shrink:0;padding:12px 16px 16px;
  border-top:1px solid var(--ys-line);
  background:rgba(12,10,29,.6);
  backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);
}
.ys-field{
  display:flex;gap:9px;align-items:flex-end;
  background:rgba(255,255,255,.05);
  border:1.5px solid var(--ys-line-2);
  border-radius:18px;padding:7px 7px 7px 7px;
  transition:border-color .2s var(--ys-ease),box-shadow .2s var(--ys-ease);
}
.ys-field:focus-within{border-color:var(--ys-violet);box-shadow:0 0 0 4px rgba(124,92,255,.16)}
#ys-input{
  flex:1;min-width:0;background:none;border:none;outline:none;resize:none;
  color:var(--ys-mist);font-family:inherit;
  /* 16px keeps iOS Safari from zooming the viewport on focus */
  font-size:16px;line-height:1.55;padding:8px 10px;max-height:132px;overflow-y:auto;
}
#ys-input::placeholder{color:var(--ys-mist-3)}
#ys-send{
  width:42px;height:42px;flex-shrink:0;border:none;border-radius:14px;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  background:linear-gradient(135deg,var(--ys-violet),var(--ys-rose));
  box-shadow:0 6px 18px -6px rgba(124,92,255,.9);
  transition:transform .2s var(--ys-spring),opacity .2s var(--ys-ease);
}
#ys-send svg{width:18px;height:18px;fill:#fff}
#ys-send:hover:not(:disabled){transform:scale(1.07) rotate(-8deg)}
#ys-send:disabled{opacity:.4;cursor:not-allowed;transform:none}
#ys-send.busy{background:rgba(255,255,255,.1);box-shadow:none}
#ys-send.busy svg{display:none}
#ys-send.busy::after{
  content:'';width:16px;height:16px;border-radius:50%;
  border:2px solid rgba(255,255,255,.25);border-top-color:var(--ys-gold);
  animation:ysSpin .7s linear infinite;
}
@keyframes ysSpin{to{transform:rotate(360deg)}}
.ys-hint{
  text-align:center;font-size:10.5px;color:var(--ys-mist-3);
  margin:9px 0 0;
}
.ys-hint a{color:var(--ys-violet-2);font-weight:700}

/* ── Mobile drawer ─────────────────────────────────────────────── */
.ys-scrim{
  position:absolute;inset:0;z-index:5;background:rgba(4,2,14,.6);
  backdrop-filter:blur(2px);opacity:0;pointer-events:none;
  transition:opacity .25s var(--ys-ease);
}
.ys-scrim.on{opacity:1;pointer-events:auto}

/* ── Auth screens ──────────────────────────────────────────────── */
.ys-auth{
  /* flex:1 — .ys-shell is a flex row, so without it this pane is sized to its
     content and the card hugs the inline-start edge instead of centring. */
  flex:1;min-width:0;
  min-height:clamp(520px, calc(100dvh - var(--t-header-h,62px) - 104px), 820px);
  display:flex;align-items:center;justify-content:center;padding:28px 18px;
  position:relative;z-index:2;
}
.ys-card{
  width:100%;max-width:430px;
  background:rgba(255,255,255,.045);
  border:1px solid var(--ys-line-2);
  border-radius:var(--ys-r-lg);
  padding:34px 30px;
  backdrop-filter:blur(22px);-webkit-backdrop-filter:blur(22px);
  box-shadow:0 30px 70px -25px rgba(10,4,40,.9);
  animation:ysRise .5s var(--ys-spring) both;
}
.ys-card h1{
  font-size:23px;font-weight:800;color:var(--ys-mist);
  text-align:center;margin:0 0 7px;text-wrap:balance;
}
.ys-card .sub{font-size:13px;color:var(--ys-mist-2);text-align:center;margin:0 0 24px;line-height:1.75}
.ys-lbl{display:block;font-size:12px;font-weight:700;color:var(--ys-mist-2);margin:0 0 6px}
.ys-in{
  width:100%;background:rgba(255,255,255,.05);
  border:1.5px solid var(--ys-line-2);border-radius:12px;
  padding:12px 14px;color:var(--ys-mist);font-family:inherit;font-size:15px;
  outline:none;transition:border-color .18s var(--ys-ease),box-shadow .18s var(--ys-ease);
}
.ys-in::placeholder{color:var(--ys-mist-3)}
.ys-in:focus{border-color:var(--ys-violet);box-shadow:0 0 0 4px rgba(124,92,255,.16)}
.ys-fld{margin-bottom:15px}
.ys-btn{
  width:100%;padding:13px;border:none;border-radius:13px;cursor:pointer;
  font-family:inherit;font-size:14.5px;font-weight:800;color:#fff;
  background:linear-gradient(135deg,var(--ys-violet),var(--ys-rose));
  background-size:180% 180%;
  box-shadow:0 10px 26px -10px rgba(124,92,255,1);
  transition:transform .2s var(--ys-spring),background-position .5s var(--ys-ease);
}
.ys-btn:hover{transform:translateY(-2px);background-position:100% 0}
.ys-btn:active{transform:none}
.ys-or{
  display:flex;align-items:center;gap:12px;margin:20px 0;
  font-size:11px;color:var(--ys-mist-3);
}
.ys-or::before,.ys-or::after{content:'';flex:1;height:1px;background:var(--ys-line-2)}
.ys-google{
  width:100%;display:flex;align-items:center;justify-content:center;gap:10px;
  padding:12px;border-radius:13px;cursor:pointer;
  background:#fff;color:#1f1f1f;border:none;
  font-family:inherit;font-size:14px;font-weight:700;
  transition:transform .2s var(--ys-spring),box-shadow .2s var(--ys-ease);
}
.ys-google:hover{transform:translateY(-2px);box-shadow:0 12px 28px -12px rgba(255,255,255,.5)}
.ys-google svg{width:18px;height:18px;flex-shrink:0}
.ys-swap{text-align:center;font-size:12.5px;color:var(--ys-mist-2);margin:20px 0 0}
.ys-swap a{color:var(--ys-violet-2);font-weight:700}
.ys-msg{
  border-radius:12px;padding:11px 14px;font-size:12.5px;line-height:1.7;margin:0 0 18px;
  animation:ysRise .35s var(--ys-spring) both;
}
.ys-msg.bad{background:rgba(255,107,168,.1);border:1px solid rgba(255,107,168,.3);color:#ffb3cd}
.ys-msg.ok{background:rgba(74,222,128,.1);border:1px solid rgba(74,222,128,.3);color:#a7f3c8}
.ys-skip{text-align:center;margin:16px 0 0}
.ys-skip a{font-size:12px;color:var(--ys-mist-3)}
.ys-skip a:hover{color:var(--ys-mist-2)}

/* ── Responsive ────────────────────────────────────────────────── */
@media(max-width:860px){
  /* Below this width the app goes edge-to-edge like a native chat app: no
     side padding, no rounded corners, and the breadcrumb (redundant once
     you're inside a full-screen app) is hidden so the shell can claim that
     strip of height too. Scoped to this stylesheet, so only chat/auth pages
     are affected — every other tool keeps its breadcrumb. */
  .t-breadcrumb{display:none}
  .ys-wrap{padding:0;max-width:none}
  .ys-shell{
    height:calc(100dvh - var(--t-header-h,62px));
    min-height:430px;border-radius:0;
    box-shadow:none;border-inline:none;
  }
  .ys-side{
    /* Physical `right`, not `inset-inline-end`: transforms are always
       physical, and in this RTL layout the inline-end edge is the *left*
       one, so pairing it with translateX(+105%) slid the drawer into view
       instead of out of it. Anchor and transform must use the same axis. */
    position:absolute;top:0;bottom:0;right:0;z-index:6;
    width:min(84vw,290px);
    transform:translateX(105%);
    transition:transform .3s var(--ys-ease);
    background:rgba(10,8,26,.96);
  }
  .ys-side.on{transform:translateX(0)}
  .ys-burger{display:flex}
  .ys-bub{max-width:86%}
  .ys-stream{padding:16px 13px}
  .ys-composer{padding:10px 12px 13px}
  .ys-welcome-bloom{width:64px;height:64px;font-size:30px}
  .ys-welcome h3{font-size:17.5px}
  .ys-card{padding:28px 22px}
}
@media(max-width:420px){
  .ys-top{padding:11px 13px;gap:9px}
  .ys-bloom{width:36px;height:36px;font-size:17px}
  .ys-id h2{font-size:14px}
  .ys-chip{font-size:12px;padding:7px 13px}
}

/* Respect the OS "reduce motion" setting — the design still reads without
   any of the ambient movement. */
@media(prefers-reduced-motion:reduce){
  *,*::before,*::after{
    animation-duration:.01ms !important;animation-iteration-count:1 !important;
    transition-duration:.01ms !important;scroll-behavior:auto !important;
  }
}
</style>
CSS;
}
