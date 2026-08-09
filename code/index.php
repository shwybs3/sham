<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>code.yassota.com — أداة الذكاء الاصطناعي للمطورين</title>
<meta name="description" content="أداة الذكاء الاصطناعي للمطورين العرب — شرح الكود، إصلاح الأخطاء، تحويل البرمجيات، توليد الاختبارات. مجانية وفورية.">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<style>
:root{
  --bg:#050008;--bg2:#0d0010;--red:#c50000;--red2:#ff2200;--red3:#ff6644;
  --text:#e8e0f0;--muted:#9980b0;--card:#0f0015;--border:#2a0030;
  --f:system-ui,-apple-system,sans-serif
}
*{box-sizing:border-box;margin:0;padding:0}
body{background:var(--bg);color:var(--text);font-family:var(--f);direction:rtl;overflow-x:hidden}
a{color:var(--red3);text-decoration:none}
a:hover{color:#fff}

/* NAV */
.nav{position:fixed;top:0;left:0;right:0;z-index:100;background:rgba(5,0,8,.85);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);padding:0 24px;display:flex;align-items:center;height:60px;gap:16px}
.nav-logo{font-size:1.1rem;font-weight:900;color:#fff;letter-spacing:-.5px}
.nav-logo span{color:var(--red)}
.nav-links{display:flex;gap:20px;font-size:.84rem;color:var(--muted);margin-inline-start:auto}
.nav-links a{color:var(--muted)}
.nav-links a:hover{color:#fff}
.nav-cta{margin-inline-start:16px;padding:7px 18px;background:var(--red);color:#fff;border-radius:8px;font-size:.84rem;font-weight:700}
.nav-cta:hover{background:var(--red2);color:#fff}

/* HERO */
.hero{min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;position:relative;padding:80px 20px 60px;text-align:center;overflow:hidden}
canvas#bg{position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:0}
.hero-content{position:relative;z-index:1;max-width:800px}
.hero-badge{display:inline-block;padding:5px 14px;background:rgba(197,0,0,.15);border:1px solid rgba(197,0,0,.4);border-radius:20px;font-size:.75rem;color:var(--red3);margin-bottom:20px;letter-spacing:.5px}
.hero h1{font-size:clamp(2rem,6vw,4rem);font-weight:900;line-height:1.15;margin-bottom:20px;background:linear-gradient(135deg,#fff 0%,var(--red3) 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hero p{font-size:1.1rem;color:var(--muted);max-width:560px;margin:0 auto 32px;line-height:1.7}
.hero-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
.btn-primary{padding:14px 32px;background:var(--red);color:#fff;border-radius:10px;font-weight:700;font-size:1rem;transition:.2s}
.btn-primary:hover{background:var(--red2);color:#fff;transform:translateY(-1px)}
.btn-secondary{padding:14px 32px;background:transparent;color:#fff;border:1px solid var(--border);border-radius:10px;font-weight:600;font-size:1rem;transition:.2s}
.btn-secondary:hover{border-color:var(--red);color:var(--red3)}

/* SECTIONS */
section{padding:72px 20px;max-width:1100px;margin:0 auto}
.section-tag{font-size:.72rem;font-weight:700;color:var(--red);letter-spacing:1.5px;text-transform:uppercase;margin-bottom:10px}
.section-title{font-size:clamp(1.5rem,4vw,2.4rem);font-weight:800;margin-bottom:12px}
.section-sub{color:var(--muted);font-size:.95rem;line-height:1.7;max-width:560px}

/* MODES */
.modes-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-top:40px}
.mode-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:24px;transition:.2s}
.mode-card:hover{border-color:var(--red);transform:translateY(-2px)}
.mode-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;font-size:1.3rem}
.mode-card h3{font-size:1rem;font-weight:700;margin-bottom:8px}
.mode-card p{font-size:.85rem;color:var(--muted);line-height:1.6}

/* FEATURES GRID */
.features-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-top:40px}
.feat{display:flex;align-items:flex-start;gap:12px;padding:16px;background:var(--card);border:1px solid var(--border);border-radius:12px}
.feat-icon{font-size:1.3rem;flex-shrink:0;margin-top:2px}
.feat p{font-size:.84rem;color:var(--muted);line-height:1.5;margin-top:4px}
.feat h4{font-size:.9rem;font-weight:700}

/* COMPARE */
.compare-wrap{overflow-x:auto;margin-top:36px;border-radius:12px;border:1px solid var(--border)}
table{width:100%;border-collapse:collapse;min-width:500px}
th{background:rgba(197,0,0,.1);padding:14px 18px;font-size:.84rem;font-weight:700;text-align:right;border-bottom:1px solid var(--border);white-space:nowrap}
td{padding:12px 18px;font-size:.85rem;border-bottom:1px solid rgba(42,0,48,.5);color:var(--muted)}
td:first-child{color:var(--text);font-weight:600}
tr:last-child td{border:none}
.yes{color:#4ade80;font-weight:700}.no{color:#f87171}

/* PRICING */
.pricing-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;margin-top:40px}
.price-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:28px;text-align:center;transition:.2s}
.price-card.featured{border-color:var(--red);position:relative}
.price-card.featured::before{content:'الأشهر';position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:var(--red);color:#fff;font-size:.7rem;font-weight:700;padding:3px 12px;border-radius:20px}
.price-name{font-size:.9rem;font-weight:700;color:var(--muted);margin-bottom:8px}
.price-amount{font-size:2.4rem;font-weight:900;color:#fff;margin-bottom:4px}
.price-amount span{font-size:.9rem;font-weight:400;color:var(--muted)}
.price-feats{list-style:none;text-align:right;margin:16px 0;font-size:.84rem;color:var(--muted)}
.price-feats li{padding:5px 0;border-bottom:1px solid rgba(42,0,48,.4)}
.price-feats li:last-child{border:none}
.price-feats li::before{content:'✓ ';color:var(--red)}
.price-btn{display:block;margin-top:16px;padding:11px;background:var(--red);color:#fff;border-radius:8px;font-weight:700;font-size:.9rem}
.price-btn:hover{background:var(--red2);color:#fff}

/* TESTIMONIALS */
.testi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;margin-top:36px}
.testi{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:20px}
.testi-stars{color:var(--red);font-size:.85rem;margin-bottom:10px}
.testi-text{font-size:.88rem;color:var(--muted);line-height:1.6;margin-bottom:14px}
.testi-name{font-size:.82rem;font-weight:700}
.testi-role{font-size:.76rem;color:var(--muted)}

/* FAQ */
.faq-list{margin-top:36px;display:flex;flex-direction:column;gap:10px}
details{background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden}
details[open]{border-color:var(--red)}
summary{padding:16px 20px;cursor:pointer;font-weight:600;font-size:.9rem;list-style:none;display:flex;justify-content:space-between;align-items:center}
summary::after{content:'＋';color:var(--red);font-size:1rem;font-weight:700}
details[open] summary::after{content:'－'}
details p{padding:0 20px 16px;font-size:.86rem;color:var(--muted);line-height:1.7}

/* CTA */
.cta-section{background:linear-gradient(135deg,rgba(197,0,0,.15) 0%,rgba(5,0,8,.8) 100%);border:1px solid rgba(197,0,0,.3);border-radius:20px;padding:60px 32px;text-align:center;margin:40px auto;max-width:700px}

/* FOOTER */
footer{background:var(--bg2);border-top:1px solid var(--border);padding:32px 20px;text-align:center;font-size:.82rem;color:var(--muted)}
footer a{color:var(--muted);margin:0 8px}
footer a:hover{color:#fff}

@media(max-width:600px){
  .nav-links{display:none}
  .hero h1{font-size:2rem}
}
</style>
</head>
<body>

<nav class="nav">
  <div class="nav-logo">code<span>.yassota</span></div>
  <div class="nav-links">
    <a href="#modes">الميزات</a>
    <a href="#pricing">الأسعار</a>
    <a href="#faq">الأسئلة</a>
    <a href="https://yassota.com" target="_blank">yassota.com</a>
  </div>
  <a href="https://yassota.com/tools/szad/" class="nav-cta" target="_blank">جرّب مجاناً</a>
</nav>

<div class="hero">
  <canvas id="bg"></canvas>
  <div class="hero-content">
    <div class="hero-badge">ذكاء اصطناعي للمطورين العرب</div>
    <h1>مساعدك الذكي<br>لكل سطر كود</h1>
    <p>شرح، تصحيح، تحويل، وتحسين الكود — بالعربية والإنجليزية، بدون تعقيد، مجاناً.</p>
    <div class="hero-btns">
      <a href="https://yassota.com/tools/szad/" class="btn-primary" target="_blank">ابدأ الآن مجاناً</a>
      <a href="#modes" class="btn-secondary">اكتشف الميزات</a>
    </div>
  </div>
</div>

<section id="modes">
  <div class="section-tag">الأوضاع</div>
  <div class="section-title">كل ما تحتاجه في أداة واحدة</div>
  <p class="section-sub">أكثر من مجرد شرح كود — مساعد برمجي كامل يفهم متطلباتك</p>
  <div class="modes-grid">
    <div class="mode-card">
      <div class="mode-icon" style="background:rgba(197,0,0,.15)">🔍</div>
      <h3>شرح الكود</h3>
      <p>الصق أي كود واحصل على شرح تفصيلي بالعربية — كل سطر وكل دالة</p>
    </div>
    <div class="mode-card">
      <div class="mode-icon" style="background:rgba(197,0,0,.15)">🐛</div>
      <h3>إصلاح الأخطاء</h3>
      <p>أرسل رسالة الخطأ ويشخّص السبب ويقترح الحل الأمثل مع الكود المصحّح</p>
    </div>
    <div class="mode-card">
      <div class="mode-icon" style="background:rgba(197,0,0,.15)">🔄</div>
      <h3>تحويل اللغات</h3>
      <p>تحويل بين Python، JavaScript، PHP، Java وأكثر من 20 لغة برمجة</p>
    </div>
  </div>
</section>

<section id="features">
  <div class="section-tag">المزايا</div>
  <div class="section-title">لماذا code.yassota؟</div>
  <div class="features-grid">
    <?php
    $feats = [
      ['🚀','سرعة فائقة','ردود فورية خلال ثوانٍ — لا انتظار'],
      ['🌐','متعدد اللغات','يفهم الكود بأي لغة برمجة'],
      ['🇸🇦','بالعربية','شرح ووثائق كاملة بالعربية الفصحى'],
      ['🔒','خصوصية تامة','لا يُحفظ كودك — كل جلسة مؤقتة'],
      ['📱','يعمل على الجوال','واجهة محسّنة للهاتف والجهاز اللوحي'],
      ['⚡','بدون تسجيل','ابدأ مباشرة بلا حسابات ولا بيانات'],
      ['🎯','دقة عالية','مدعوم بأحدث نماذج GPT-4 و Claude'],
      ['🔁','محادثة متصلة','السياق محفوظ طوال جلستك'],
      ['💾','تصدير الكود','نسخ الكود بنقرة واحدة'],
    ];
    foreach($feats as [$icon,$title,$desc]):
    ?>
    <div class="feat">
      <div class="feat-icon"><?= $icon ?></div>
      <div>
        <h4><?= $title ?></h4>
        <p><?= $desc ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<section id="usecases" style="padding-top:0">
  <div class="section-tag">حالات الاستخدام</div>
  <div class="section-title">مَن يستخدم code.yassota؟</div>
  <div class="compare-wrap">
    <table>
      <thead><tr><th>المستخدم</th><th>الحالة</th><th>الفائدة</th></tr></thead>
      <tbody>
      <?php
      $cases = [
        ['طالب برمجة','لا يفهم كود المشروع','شرح كامل بالعربية مع أمثلة'],
        ['مطور مبتدئ','خطأ غامض يوقف العمل','تشخيص الخطأ والحل الفوري'],
        ['مطور محترف','تحويل مشروع Python إلى JS','تحويل تلقائي مع شرح الفروقات'],
        ['فريق تقني','مراجعة الكود قبل النشر','اقتراحات تحسين الأداء والأمان'],
        ['مستقل Freelancer','ميزة جديدة لعميل','توليد كود جاهز حسب المتطلبات'],
        ['مدرّس برمجة','شرح مفهوم معقد للطلاب','تبسيط الخوارزميات بلغة سهلة'],
        ['صاحب مشروع','تدقيق أمان API','فحص الثغرات وأفضل ممارسات الأمان'],
        ['مبرمج بدون خبرة بالـ AI','أتمتة مهمة متكررة','كتابة سكريبت مخصص من الصفر'],
      ];
      foreach($cases as [$who,$prob,$sol]): ?>
      <tr><td><?= $who ?></td><td style="color:var(--muted)"><?= $prob ?></td><td style="color:#4ade80"><?= $sol ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section id="pricing" style="text-align:center">
  <div class="section-tag">الأسعار</div>
  <div class="section-title">خطة تناسب كل احتياج</div>
  <div class="pricing-grid" style="text-align:right">
    <div class="price-card">
      <div class="price-name">مجاني</div>
      <div class="price-amount">$0 <span>/شهر</span></div>
      <ul class="price-feats">
        <li>20 طلب يومياً</li>
        <li>كل أوضاع الكود الأساسية</li>
        <li>بدون تسجيل</li>
      </ul>
      <a href="https://yassota.com/tools/szad/" class="price-btn" target="_blank">ابدأ مجاناً</a>
    </div>
    <div class="price-card featured">
      <div class="price-name">Plus</div>
      <div class="price-amount">$5 <span>/شهر</span></div>
      <ul class="price-feats">
        <li>100 طلب يومياً</li>
        <li>بدون إعلانات</li>
        <li>دعم أولوية</li>
        <li>تصدير المحادثات</li>
      </ul>
      <a href="/upgrade?tier=plus" class="price-btn">اشترك الآن</a>
    </div>
    <div class="price-card">
      <div class="price-name">Pro</div>
      <div class="price-amount">$10 <span>/شهر</span></div>
      <ul class="price-feats">
        <li>وصول غير محدود</li>
        <li>نماذج GPT-4 و Claude</li>
        <li>API مخصص</li>
        <li>كل مزايا Plus</li>
      </ul>
      <a href="/upgrade?tier=pro" class="price-btn">اشترك الآن</a>
    </div>
  </div>
</section>

<section id="testimonials" style="padding-top:0">
  <div class="section-tag">آراء المستخدمين</div>
  <div class="section-title">ماذا يقول المطورون؟</div>
  <div class="testi-grid">
    <?php
    $testis = [
      ['⭐⭐⭐⭐⭐','أخيراً أداة تشرح الكود بالعربية الصح — وفّرت علي ساعات من البحث.','أحمد خليل','مطور ويب، السعودية'],
      ['⭐⭐⭐⭐⭐','بستخدمها كل يوم لتحويل كود Python إلى JavaScript، نتائجها دقيقة جداً.','محمد السيد','مطور full-stack، مصر'],
      ['⭐⭐⭐⭐⭐','إصلاح الأخطاء صار أسرع بمرات، ما في داعي أسأل Stack Overflow.','نور الهدى','طالبة هندسة برمجيات، المغرب'],
      ['⭐⭐⭐⭐','الواجهة بسيطة والردود سريعة، أنصح كل مطور عربي بها.','عمر العبدالله','مستقل Freelancer، الإمارات'],
    ];
    foreach($testis as [$stars,$text,$name,$role]): ?>
    <div class="testi">
      <div class="testi-stars"><?= $stars ?></div>
      <div class="testi-text"><?= $text ?></div>
      <div class="testi-name"><?= $name ?></div>
      <div class="testi-role"><?= $role ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<section id="faq" style="max-width:720px">
  <div class="section-tag">الأسئلة الشائعة</div>
  <div class="section-title">لديك سؤال؟</div>
  <div class="faq-list">
    <details open><summary>هل الأداة مجانية تماماً؟</summary><p>نعم، الخطة المجانية تتيح 20 طلباً يومياً بدون تسجيل. للاستخدام المكثّف تتوفر خطة Plus بـ $5/شهر.</p></details>
    <details><summary>ما لغات البرمجة المدعومة؟</summary><p>تدعم الأداة أكثر من 30 لغة برمجة: Python, JavaScript, PHP, Java, C++, Go, Rust, Swift, Kotlin, Ruby, TypeScript وغيرها.</p></details>
    <details><summary>هل يحتفظ الموقع بالكود الذي أرسله؟</summary><p>لا — كل جلسة مؤقتة ولا تُحفظ بياناتك على خوادمنا. خصوصيتك أولويتنا.</p></details>
    <details><summary>ما الفرق بين code.yassota وأدوات مثل ChatGPT؟</summary><p>code.yassota مُحسَّنة خصيصاً للمطورين العرب: واجهة عربية، شرح بالعربية، وأوضاع محددة (شرح/إصلاح/تحويل) تجعل التجربة أسرع وأدق من المحادثات العامة.</p></details>
    <details><summary>هل يمكن استخدامها على الجوال؟</summary><p>نعم — الواجهة محسّنة بالكامل للهاتف والجهاز اللوحي، تعمل على كل المتصفحات الحديثة.</p></details>
  </div>
</section>

<div class="cta-section">
  <div class="section-tag" style="text-align:center">ابدأ الآن</div>
  <div class="section-title">كودك أفضل، بالعربية</div>
  <p style="color:var(--muted);margin:12px auto 28px;max-width:400px;font-size:.95rem">انضم إلى آلاف المطورين العرب الذين يستخدمون code.yassota يومياً</p>
  <a href="https://yassota.com/tools/szad/" class="btn-primary" target="_blank" style="font-size:1.05rem;padding:16px 40px">جرّب مجاناً الآن — لا تسجيل مطلوب</a>
</div>

<footer>
  <p style="margin-bottom:12px">
    <a href="/">yassota.com</a>
    <a href="/tools/">الأدوات</a>
    <a href="/privacy-policy">الخصوصية</a>
    <a href="/terms">الشروط</a>
    <a href="/contact">تواصل معنا</a>
  </p>
  <p>© <?= date('Y') ?> yassota.com — جميع الحقوق محفوظة</p>
</footer>

<script>
(function(){
  var c=document.getElementById('bg'),ctx=c.getContext('2d');
  var pts=[];
  function resize(){c.width=window.innerWidth;c.height=window.innerHeight;}
  resize();window.addEventListener('resize',resize);
  for(var i=0;i<70;i++)pts.push({x:Math.random()*c.width,y:Math.random()*c.height,vx:(Math.random()-.5)*.4,vy:(Math.random()-.5)*.4,r:Math.random()*2+.5,o:Math.random()*.6+.1});
  function draw(){
    ctx.clearRect(0,0,c.width,c.height);
    pts.forEach(function(p){
      p.x+=p.vx;p.y+=p.vy;
      if(p.x<0)p.x=c.width;if(p.x>c.width)p.x=0;
      if(p.y<0)p.y=c.height;if(p.y>c.height)p.y=0;
      ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
      ctx.fillStyle='rgba(197,0,0,'+p.o+')';ctx.fill();
    });
    pts.forEach(function(a,i){pts.forEach(function(b,j){
      if(j<=i)return;
      var d=Math.hypot(a.x-b.x,a.y-b.y);
      if(d<130){ctx.beginPath();ctx.moveTo(a.x,a.y);ctx.lineTo(b.x,b.y);ctx.strokeStyle='rgba(197,0,0,'+(0.12*(1-d/130))+')';ctx.lineWidth=.5;ctx.stroke();}
    });});
    requestAnimationFrame(draw);
  }
  draw();
})();
</script>
</body>
</html>
