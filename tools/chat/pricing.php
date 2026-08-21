<?php
/**
 * tools/chat/pricing.php — ياسمين subscription plans.
 */
require_once dirname(__DIR__) . '/_base.php';

$mainCfg = dirname(dirname(__DIR__)) . '/config.php';
if (!file_exists($mainCfg)) { http_response_code(503); echo 'Service unavailable'; exit; }
require_once $mainCfg;
require_once __DIR__ . '/_auth.php';

$me       = ys_current_user($pdo);
$activeSub = $me ? ys_active_sub($pdo, (int)$me['id']) : null;
$nowpayEnabled = get_cfg($pdo, 'nowpay_api_key', '') !== '';

$error   = '';
$invoice = null;

/* ── Handle checkout POST ────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tier'])) {
    if (!$me) {
        $error = 'يجب تسجيل الدخول أولاً لشراء الاشتراك.';
    } elseif (!csrf_check()) {
        $error = 'جلسة غير صالحة، أعد تحميل الصفحة وحاول مجدداً.';
    } else {
        $tier = $_POST['tier'] ?? '';
        if (!isset(YS_TIERS[$tier])) {
            $error = 'خطة غير صالحة.';
        } elseif (!$nowpayEnabled) {
            $error = 'نظام الدفع غير مفعّل حالياً — تواصل معنا مباشرة.';
        } else {
            $price = (float)YS_TIERS[$tier]['price'];
            $invoice = nowpay_create_invoice($pdo, (int)$me['id'], $tier, $price, 30);
            if (!$invoice) {
                $error = 'تعذّر إنشاء طلب الدفع، حاول مجدداً أو تواصل معنا.';
            }
        }
    }
}

$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$pageTitle = 'باقات ياسمين — اشتراك شهري بالعملات الرقمية';
$pageDesc  = 'اشترك في باقة ياسمين بلاس أو برو أو بريميوم للحصول على رسائل أكثر ونماذج ذكاء اصطناعي أقوى.';

tool_head($pageTitle, $pageDesc, '', '#7c5cff');
?>
<style>
.ys-pricing-wrap{max-width:900px;margin:0 auto;padding:24px 16px 64px}
.ys-plans{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px;margin:28px 0}
.ys-plan{background:#1a1035;border:1.5px solid #312660;border-radius:20px;padding:28px 24px;position:relative;transition:transform .18s,box-shadow .18s;display:flex;flex-direction:column;gap:12px}
.ys-plan:hover{transform:translateY(-4px);box-shadow:0 8px 32px rgba(124,92,255,.2)}
.ys-plan.featured{border-color:#7c5cff;box-shadow:0 0 0 1px #7c5cff40}
.ys-plan-badge{position:absolute;top:-12px;right:20px;background:#7c5cff;color:#fff;font-size:11px;font-weight:700;padding:3px 12px;border-radius:20px}
.ys-plan-name{font-size:18px;font-weight:700;color:#e2d9ff}
.ys-plan-price{font-size:36px;font-weight:800;color:#fff}
.ys-plan-price sup{font-size:18px;vertical-align:super;font-weight:400;color:#a89ecc}
.ys-plan-price span{font-size:14px;color:#a89ecc;font-weight:400}
.ys-plan-desc{font-size:13px;color:#a89ecc;line-height:1.6;margin-bottom:4px}
.ys-plan-feats{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:8px;flex:1}
.ys-plan-feats li{font-size:13px;color:#c4b8ef;display:flex;align-items:flex-start;gap:8px}
.ys-plan-feats li::before{content:'✓';color:#7c5cff;font-weight:700;flex-shrink:0;margin-top:1px}
.ys-plan-btn{display:block;text-align:center;padding:12px;border-radius:12px;font-weight:700;font-size:15px;cursor:pointer;border:none;transition:opacity .15s;text-decoration:none;margin-top:auto}
.ys-plan-btn-primary{background:linear-gradient(135deg,#7c5cff,#a259ff);color:#fff}
.ys-plan-btn-primary:hover{opacity:.88}
.ys-plan-btn-outline{background:transparent;border:1.5px solid #7c5cff;color:#a389ff}
.ys-plan-btn-outline:hover{background:#7c5cff20}
.ys-plan-active{background:#10b98120;border-color:#10b981;padding:6px 12px;border-radius:8px;font-size:12px;color:#10b981;font-weight:600;text-align:center}
.ys-invoice-box{background:#0d0a1f;border:1.5px solid #312660;border-radius:16px;padding:24px;max-width:480px;margin:0 auto}
.ys-invoice-addr{background:#1a1035;border-radius:10px;padding:12px 14px;font-family:monospace;font-size:13px;color:#c4b8ef;word-break:break-all;margin:10px 0;border:1px solid #312660}
.ys-qr{display:block;margin:12px auto;border-radius:8px;background:#fff;padding:6px}
.ys-copy-btn{background:#7c5cff;color:#fff;border:none;border-radius:8px;padding:8px 18px;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.ys-copy-btn:active{opacity:.7}
.ys-note{font-size:12px;color:#a89ecc;margin-top:10px;line-height:1.7}
@media(max-width:600px){.ys-plans{grid-template-columns:1fr}}
</style>

<main class="ys-pricing-wrap">

  <div style="text-align:center;margin-bottom:8px">
    <div style="font-size:42px;margin-bottom:8px">🌸</div>
    <h1 style="font-size:26px;font-weight:800;color:#e2d9ff;margin:0 0 8px">باقات ياسمين</h1>
    <p style="color:#a89ecc;font-size:15px;max-width:460px;margin:0 auto;line-height:1.7">
      خذ تجربتك مع ياسمين لمستوى أعلى — رسائل أكثر، نماذج أقوى، وأولوية في الاستجابة.
    </p>
  </div>

  <?php if ($error): ?>
    <div style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#fca5a5;padding:12px 18px;border-radius:10px;margin:16px 0;font-size:14px"><?= $h($error) ?></div>
  <?php endif; ?>

  <?php if (!$invoice): ?>

  <div class="ys-plans">

    <!-- ── Free ── -->
    <div class="ys-plan">
      <div class="ys-plan-name">مجاني</div>
      <div class="ys-plan-price"><sup>$</sup>0 <span>/ دائماً</span></div>
      <div class="ys-plan-desc">ابدأ بدون بطاقة ولا اشتراك</div>
      <ul class="ys-plan-feats">
        <li>40 رسالة في الساعة (زائر)</li>
        <li>160 رسالة في الساعة (بحساب)</li>
        <li>نماذج مجانية (Llama، Gemini Flash)</li>
        <li>حفظ المحادثات بالتسجيل</li>
        <li>دعم العربية واللهجة السورية</li>
      </ul>
      <?php if (!$me): ?>
        <a href="auth.php?a=register" class="ys-plan-btn ys-plan-btn-outline">إنشاء حساب مجاني</a>
      <?php elseif (!$activeSub): ?>
        <div class="ys-plan-active">خطتك الحالية ✓</div>
      <?php else: ?>
        <a href="index.php" class="ys-plan-btn ys-plan-btn-outline">ابدأ المحادثة</a>
      <?php endif; ?>
    </div>

    <!-- ── Plus ── -->
    <div class="ys-plan">
      <div class="ys-plan-name" style="color:#10b981">ياسمين بلاس</div>
      <div class="ys-plan-price"><sup>$</sup>5 <span>/ شهر</span></div>
      <div class="ys-plan-desc">للمستخدمين النشطين الذين يحتاجون المزيد</div>
      <ul class="ys-plan-feats">
        <li>300 رسالة في الساعة</li>
        <li>نماذج أسرع (Gemini Flash 1.5، Llama 70B)</li>
        <li>أولوية في الاستجابة</li>
        <li>جميع مزايا الخطة المجانية</li>
      </ul>
      <?php if ($activeSub && $activeSub['tier'] === 'plus'): ?>
        <div class="ys-plan-active">اشتراكك الحالي — ينتهي <?= $h(substr($activeSub['expires_at'],0,10)) ?> ✓</div>
      <?php elseif ($me): ?>
        <form method="post" action="pricing.php">
          <?= csrf_field() ?>
          <input type="hidden" name="tier" value="plus">
          <button type="submit" class="ys-plan-btn ys-plan-btn-primary" style="background:linear-gradient(135deg,#059669,#10b981)">اشترك بـ $5/شهر</button>
        </form>
      <?php else: ?>
        <a href="auth.php?a=login" class="ys-plan-btn ys-plan-btn-outline">سجّل الدخول للاشتراك</a>
      <?php endif; ?>
    </div>

    <!-- ── Pro (featured) ── -->
    <div class="ys-plan featured">
      <div class="ys-plan-badge">الأكثر طلباً</div>
      <div class="ys-plan-name" style="color:#60a5fa">ياسمين برو</div>
      <div class="ys-plan-price"><sup>$</sup>10 <span>/ شهر</span></div>
      <div class="ys-plan-desc">تجربة AI احترافية بنماذج GPT و Claude</div>
      <ul class="ys-plan-feats">
        <li>800 رسالة في الساعة</li>
        <li>نماذج متقدمة (GPT-4o mini، Claude 3 Haiku)</li>
        <li>ذكاء أعمق وإجابات أكثر دقة</li>
        <li>أولوية عالية في المعالجة</li>
        <li>جميع مزايا بلاس</li>
      </ul>
      <?php if ($activeSub && $activeSub['tier'] === 'pro'): ?>
        <div class="ys-plan-active">اشتراكك الحالي — ينتهي <?= $h(substr($activeSub['expires_at'],0,10)) ?> ✓</div>
      <?php elseif ($me): ?>
        <form method="post" action="pricing.php">
          <?= csrf_field() ?>
          <input type="hidden" name="tier" value="pro">
          <button type="submit" class="ys-plan-btn ys-plan-btn-primary">اشترك بـ $10/شهر</button>
        </form>
      <?php else: ?>
        <a href="auth.php?a=login" class="ys-plan-btn ys-plan-btn-outline">سجّل الدخول للاشتراك</a>
      <?php endif; ?>
    </div>

    <!-- ── Premium ── -->
    <div class="ys-plan">
      <div class="ys-plan-name" style="color:#a78bfa">ياسمين بريميوم</div>
      <div class="ys-plan-price"><sup>$</sup>20 <span>/ شهر</span></div>
      <div class="ys-plan-desc">للمحترفين — أقوى نماذج الذكاء الاصطناعي</div>
      <ul class="ys-plan-feats">
        <li>رسائل غير محدودة عملياً (9999/ساعة)</li>
        <li>Claude 3.5 Sonnet، GPT-4o، Claude 3 Opus</li>
        <li>أعلى دقة وأعمق تحليل</li>
        <li>أولوية قصوى وسرعة استجابة مثلى</li>
        <li>جميع مزايا برو</li>
      </ul>
      <?php if ($activeSub && $activeSub['tier'] === 'premium'): ?>
        <div class="ys-plan-active">اشتراكك الحالي — ينتهي <?= $h(substr($activeSub['expires_at'],0,10)) ?> ✓</div>
      <?php elseif ($me): ?>
        <form method="post" action="pricing.php">
          <?= csrf_field() ?>
          <input type="hidden" name="tier" value="premium">
          <button type="submit" class="ys-plan-btn ys-plan-btn-primary" style="background:linear-gradient(135deg,#6d28d9,#8b5cf6)">اشترك بـ $20/شهر</button>
        </form>
      <?php else: ?>
        <a href="auth.php?a=login" class="ys-plan-btn ys-plan-btn-outline">سجّل الدخول للاشتراك</a>
      <?php endif; ?>
    </div>

  </div><!-- /plans -->

  <div style="text-align:center;margin-top:16px">
    <p style="color:#a89ecc;font-size:13px;line-height:1.8">
      💳 الدفع بالعملات الرقمية (USDT TRC20، ETH، BTC، وأكثر) عبر NOWPayments<br>
      🔄 الاشتراك يُفعَّل تلقائياً بمجرد تأكيد الدفع على الشبكة<br>
      📅 جميع الباقات شهرية قابلة للتجديد — لا رسوم خفية ولا التزام دائم
    </p>
    <a href="index.php" style="color:#7c5cff;font-size:13px">← العودة لياسمين</a>
  </div>

  <?php else: /* Invoice created — show payment instructions */ ?>

  <div class="ys-invoice-box" style="margin-top:24px">
    <h2 style="font-size:18px;font-weight:700;color:#e2d9ff;margin:0 0 4px">أكمل الدفع</h2>
    <p style="color:#a89ecc;font-size:13px;margin-bottom:16px">
      أرسل المبلغ المطلوب على عنوان المحفظة التالي.
      سيُفعَّل اشتراكك تلقائياً بمجرد تأكيد المعاملة (عادةً خلال بضع دقائق).
    </p>

    <?php
    $invLink   = $invoice['invoice_url'] ?? '';
    $payAmount = (float)($invoice['pay_amount'] ?? 0);
    $payCur    = strtoupper($invoice['pay_currency'] ?? 'USDT');
    $payAddr   = $invoice['pay_address'] ?? '';
    $tierLabel = YS_TIERS[$_POST['tier']]['label'] ?? '';
    $tierPrice = YS_TIERS[$_POST['tier']]['price'] ?? 0;
    ?>

    <div style="background:#12102a;border-radius:10px;padding:14px;margin-bottom:14px;display:flex;gap:12px;align-items:center">
      <div style="font-size:24px">🌸</div>
      <div>
        <div style="font-size:13px;color:#a89ecc">الباقة المختارة</div>
        <div style="font-size:16px;font-weight:700;color:#e2d9ff"><?= $h($tierLabel) ?> — $<?= $tierPrice ?>/شهر</div>
      </div>
    </div>

    <?php if ($invLink): ?>
      <a href="<?= $h($invLink) ?>" target="_blank" rel="noopener"
         style="display:block;text-align:center;background:linear-gradient(135deg,#7c5cff,#a259ff);color:#fff;padding:13px;border-radius:12px;font-weight:700;font-size:15px;text-decoration:none;margin-bottom:16px">
        افتح صفحة الدفع الآمنة ↗
      </a>
    <?php endif; ?>

    <?php if ($payAddr): ?>
      <div style="font-size:12px;color:#a89ecc;margin-bottom:4px">عنوان المحفظة (<?= $h($payCur) ?>)</div>
      <div class="ys-invoice-addr" id="ys-pay-addr"><?= $h($payAddr) ?></div>
      <button class="ys-copy-btn" onclick="yscopytxt(document.getElementById('ys-pay-addr').textContent.trim())">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
        نسخ العنوان
      </button>

      <!-- QR code via Google Charts (inline fallback) -->
      <img class="ys-qr" width="160" height="160"
           src="https://chart.googleapis.com/chart?chs=160x160&cht=qr&chl=<?= urlencode($payAddr) ?>&choe=UTF-8"
           alt="QR للعنوان" onerror="this.style.display='none'">
    <?php endif; ?>

    <?php if ($payAmount > 0): ?>
      <div style="margin-top:14px;background:#1a1035;border-radius:10px;padding:12px;display:flex;justify-content:space-between;align-items:center">
        <span style="font-size:13px;color:#a89ecc">المبلغ المطلوب</span>
        <span style="font-size:16px;font-weight:700;color:#fff" id="ys-pay-amount"><?= $h(number_format($payAmount, 6)) ?> <?= $h($payCur) ?></span>
      </div>
      <button class="ys-copy-btn" style="margin-top:8px;background:#1a1035;color:#a89ecc;border:1px solid #312660"
              onclick="yscopytxt('<?= $h($payAmount) ?>')">
        نسخ المبلغ
      </button>
    <?php endif; ?>

    <p class="ys-note">
      ⚠️ أرسل فقط <?= $h($payCur) ?> على هذا العنوان. الإرسال بعملة خاطئة أو شبكة خاطئة قد يؤدي لضياع الأموال.<br>
      ⏱ صلاحية هذه الفاتورة 60 دقيقة — إذا انتهت المهلة، ارجع وابدأ عملية دفع جديدة.<br>
      🔄 سيُفعَّل اشتراكك تلقائياً بعد التأكيد. للمساعدة: <a href="/contact.php" style="color:#7c5cff">تواصل معنا</a>.
    </p>
  </div>

  <div style="text-align:center;margin-top:20px">
    <a href="pricing.php" style="color:#a89ecc;font-size:13px">← العودة للباقات</a>
    &nbsp;·&nbsp;
    <a href="index.php" style="color:#7c5cff;font-size:13px">افتح ياسمين</a>
  </div>

  <?php endif; /* /invoice */ ?>

</main>

<script>
function yscopytxt(t){
  if(navigator.clipboard){navigator.clipboard.writeText(t).then(function(){alert('تم النسخ ✓');});}
  else{var ta=document.createElement('textarea');ta.value=t;document.body.appendChild(ta);ta.select();document.execCommand('copy');document.body.removeChild(ta);alert('تم النسخ ✓');}
}
</script>

<?php tool_footer(); ?>
