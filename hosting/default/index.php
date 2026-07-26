<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>yassota - hosting web | استضافة مواقع ويب احترافية</title>
  <meta name="description" content="yassota hosting — استضافة مواقع ويب احترافية بأفضل الأسعار. دومينات مجانية، HTTPS تلقائي، لوحة تحكم متقدمة.">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg: #0f172a;
      --surface: #1e293b;
      --border: #334155;
      --text: #e2e8f0;
      --muted: #94a3b8;
      --accent: #38bdf8;
      --accent2: #818cf8;
      --green: #22c55e;
    }
    body {
      font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      line-height: 1.6;
    }
    .container { max-width: 900px; margin: 0 auto; padding: 20px; }

    /* Header */
    header {
      text-align: center;
      padding: 60px 20px 40px;
      position: relative;
    }
    .logo {
      font-size: 42px;
      font-weight: 900;
      letter-spacing: -1px;
      color: var(--accent);
      margin-bottom: 6px;
    }
    .logo span { color: var(--accent2); }
    .tagline { font-size: 16px; color: var(--muted); margin-bottom: 20px; }
    .badge-coming {
      display: inline-block;
      background: linear-gradient(135deg, #1d4ed8, #7c3aed);
      color: #fff;
      padding: 6px 20px;
      border-radius: 30px;
      font-size: 13px;
      font-weight: 600;
      letter-spacing: 0.5px;
    }

    /* Announcement box */
    .announcement {
      background: linear-gradient(135deg, #1e3a5f, #1e2a4a);
      border: 1px solid #1d4ed8;
      border-radius: 14px;
      padding: 28px 32px;
      margin: 30px 0;
      text-align: center;
    }
    .announcement h2 { font-size: 20px; color: var(--accent); margin-bottom: 10px; }
    .announcement p { color: var(--muted); font-size: 14px; line-height: 1.8; }

    /* Plans grid */
    .plans { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin: 30px 0; }
    .plan {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 24px 20px;
      text-align: center;
      transition: transform .2s, border-color .2s;
    }
    .plan:hover { transform: translateY(-3px); border-color: var(--accent); }
    .plan .icon { font-size: 32px; margin-bottom: 12px; }
    .plan h3 { font-size: 16px; color: var(--text); margin-bottom: 8px; }
    .plan .price {
      font-size: 24px;
      font-weight: 800;
      color: var(--accent);
      margin-bottom: 6px;
    }
    .plan .price small { font-size: 13px; font-weight: 400; color: var(--muted); }
    .plan ul { list-style: none; text-align: right; font-size: 13px; color: var(--muted); margin-top: 12px; line-height: 2; }
    .plan ul li::before { content: '✓ '; color: var(--green); font-weight: 700; }
    .plan.featured { border-color: var(--accent2); background: #1a1f35; }

    /* Features */
    .features { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin: 30px 0; }
    .feature {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 18px;
      display: flex;
      gap: 14px;
      align-items: flex-start;
    }
    .feature .fi { font-size: 24px; flex-shrink: 0; }
    .feature h4 { font-size: 14px; color: var(--text); margin-bottom: 4px; }
    .feature p { font-size: 12px; color: var(--muted); }

    /* CTA */
    .cta { text-align: center; margin: 40px 0; }
    .cta h2 { font-size: 24px; margin-bottom: 12px; }
    .cta p { color: var(--muted); font-size: 14px; margin-bottom: 24px; }
    .btn-tg {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      background: #0088cc;
      color: #fff;
      padding: 14px 32px;
      border-radius: 12px;
      text-decoration: none;
      font-weight: 700;
      font-size: 16px;
      transition: background .2s, transform .2s;
    }
    .btn-tg:hover { background: #0077b5; transform: translateY(-2px); }

    /* Free domains */
    .free-domains {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 24px;
      margin: 20px 0;
    }
    .free-domains h3 { color: var(--accent); margin-bottom: 14px; font-size: 16px; }
    .domain-list { display: flex; flex-wrap: wrap; gap: 10px; }
    .domain-pill {
      background: #0f172a;
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 6px 14px;
      font-size: 13px;
      color: var(--accent);
      font-family: monospace;
    }

    footer {
      text-align: center;
      padding: 30px 20px;
      color: var(--muted);
      font-size: 13px;
      border-top: 1px solid var(--border);
      margin-top: 40px;
    }
    footer a { color: var(--accent); text-decoration: none; }

    @media (max-width: 600px) {
      .logo { font-size: 30px; }
      header { padding: 40px 16px 30px; }
      .plan .price { font-size: 20px; }
    }
  </style>
</head>
<body>

<div class="container">
  <header>
    <div class="logo">yassota<span>.</span>host</div>
    <p class="tagline">استضافة مواقع ويب — دومينات مجانية — HTTPS تلقائي</p>
    <span class="badge-coming">🚀 قريباً — تواصل معنا الآن</span>
  </header>

  <div class="announcement">
    <h2>🌐 استضافة احترافية بأسعار لا تُصدَّق</h2>
    <p>
      نقدم خدمة استضافة متكاملة مع دومينات مجانية، شهادات SSL، ولوحة تحكم متقدمة.<br>
      للاستفسار والحجز المسبق تواصل معنا مباشرة عبر تيليجرام.
    </p>
  </div>

  <!-- Hosting Plans -->
  <h2 style="font-size:18px;margin-bottom:16px">خطط الاستضافة</h2>
  <div class="plans">
    <div class="plan">
      <div class="icon">🆓</div>
      <h3>مجاني</h3>
      <div class="price">$0 <small>/شهر</small></div>
      <ul>
        <li>دومين فرعي مجاني</li>
        <li>200MB مساحة تخزين</li>
        <li>SSL مجاني</li>
        <li>PHP + MySQL</li>
        <li>دعم عبر تيليجرام</li>
      </ul>
    </div>
    <div class="plan featured">
      <div class="icon">⚡</div>
      <h3>مشترك</h3>
      <div class="price">$3 <small>/شهر</small></div>
      <ul>
        <li>دومين .com مجاني لسنة</li>
        <li>10GB SSD</li>
        <li>SSL مجاني</li>
        <li>نطاقات فرعية غير محدودة</li>
        <li>بريد إلكتروني مهني</li>
        <li>لوحة cPanel كاملة</li>
      </ul>
    </div>
    <div class="plan">
      <div class="icon">🏆</div>
      <h3>VPS</h3>
      <div class="price">$10 <small>/شهر</small></div>
      <ul>
        <li>IP مخصص</li>
        <li>50GB SSD NVMe</li>
        <li>2 vCPU + 2GB RAM</li>
        <li>دومينات غير محدودة</li>
        <li>تحكم كامل بالسيرفر</li>
        <li>دعم 24/7</li>
      </ul>
    </div>
  </div>

  <!-- Features -->
  <h2 style="font-size:18px;margin:30px 0 16px">مميزاتنا</h2>
  <div class="features">
    <div class="feature">
      <div class="fi">🔒</div>
      <div>
        <h4>HTTPS تلقائي</h4>
        <p>شهادة SSL مجانية مع التجديد التلقائي — أمان كامل لموقعك</p>
      </div>
    </div>
    <div class="feature">
      <div class="fi">⚡</div>
      <div>
        <h4>سرعة فائقة</h4>
        <p>خوادم SSD NVMe مع شبكة CDN عالمية لتحميل أسرع</p>
      </div>
    </div>
    <div class="feature">
      <div class="fi">🌐</div>
      <div>
        <h4>دومينات مجانية</h4>
        <p>احصل على نطاق فرعي مجاني فوراً أو اربط دومينك الخاص</p>
      </div>
    </div>
    <div class="feature">
      <div class="fi">📊</div>
      <div>
        <h4>لوحة تحكم متقدمة</h4>
        <p>إدارة كاملة للملفات، قواعد البيانات، والبريد الإلكتروني</p>
      </div>
    </div>
    <div class="feature">
      <div class="fi">🤖</div>
      <div>
        <h4>إنشاء مواقع بالذكاء الاصطناعي</h4>
        <p>أنشئ موقعك كاملاً بضغطة زر مع محتوى مُحسَّن لـ SEO</p>
      </div>
    </div>
    <div class="feature">
      <div class="fi">🔄</div>
      <div>
        <h4>نسخ احتياطية يومية</h4>
        <p>احتفظ ببياناتك آمنة مع نسخ تلقائية يومية</p>
      </div>
    </div>
  </div>

  <!-- Free Domains -->
  <div class="free-domains">
    <h3>🌐 نطاقات مجانية متاحة</h3>
    <div class="domain-list">
      <span class="domain-pill">yourname.rf.gd</span>
      <span class="domain-pill">yourname.epizy.com</span>
      <span class="domain-pill">yourname.infinityfreeapp.com</span>
      <span class="domain-pill">yourname.000webhostapp.com</span>
      <span class="domain-pill">yourname.netlify.app</span>
      <span class="domain-pill">yourname.vercel.app</span>
      <span class="domain-pill">yourname.github.io</span>
      <span class="domain-pill">yourname.tk</span>
      <span class="domain-pill">yourname.ml</span>
      <span class="domain-pill">yourname.ga</span>
    </div>
  </div>

  <!-- CTA -->
  <div class="cta">
    <h2>ابدأ موقعك اليوم</h2>
    <p>تواصل معنا عبر تيليجرام للحصول على استضافة مجانية أو معرفة المزيد</p>
    <a href="https://t.me/layos_he" target="_blank" class="btn-tg">
      <svg viewBox="0 0 24 24" fill="currentColor" style="width:22px;height:22px;flex-shrink:0"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.248l-2.01 9.47c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12l-6.871 4.326-2.962-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.873.75z"/></svg>
      تواصل مع @layos_he
    </a>
  </div>

</div>

<footer>
  <p>© <?php echo date('Y'); ?> yassota hosting — جميع الحقوق محفوظة</p>
  <p style="margin-top:6px"><a href="https://yassota.com">yassota.com</a> — أفضل تطبيقات Android بالعربي</p>
</footer>

</body>
</html>
