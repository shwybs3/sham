<?php
require_once __DIR__ . '/../config.php';
requireAdminLogin();

$currencyCode = $settings['currency'] ?? 'SYP';
$currency     = currencySymbol($currencyCode);
$pageTitle    = 'تسجيل سريع';
$activeNav    = 'quick';
$productTypes = getProductTypes();

// جلب آخر 12 عملية اليوم
$todayStmt = $pdo->prepare(
    "SELECT t.*, c.name AS customer_name FROM transactions t
     JOIN customers c ON c.id = t.customer_id
     WHERE DATE(t.created_at) = CURDATE()
     ORDER BY t.created_at DESC LIMIT 12"
);
$todayStmt->execute();
$todayTx = $todayStmt->fetchAll();

// القيم المبدئية من الرابط
$defaultType = in_array($_GET['type'] ?? '', ['purchase','payment']) ? $_GET['type'] : 'purchase';

require __DIR__ . '/includes/admin-header.php';
?>
<style>
.qe-wrap{max-width:680px; margin:0 auto;}
.qe-card{background:var(--panel); border:1px solid var(--line); border-radius:20px; padding:28px; animation:fadeUp .3s var(--ease);}
.qe-toggle{display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:22px;}
.qe-toggle button{
  padding:16px; border-radius:14px; border:1.5px solid var(--line); background:var(--panel-2); color:var(--ink-dim);
  font-family:var(--body); font-size:15px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:9px;
  transition:.15s; min-height:52px;
}
.qe-toggle button.active[data-type="purchase"]{ background:rgba(239,106,95,.12); border-color:var(--debt); color:var(--debt); }
.qe-toggle button.active[data-type="payment"]{ background:rgba(34,197,142,.12); border-color:var(--brand); color:var(--brand); }
.qe-amount-wrap{position:relative;}
.qe-amount-wrap input{font-family:var(--mono); font-size:26px; font-weight:700; text-align:center; padding:18px;}
.qe-currency{position:absolute; top:50%; transform:translateY(-50%); inset-inline-end:16px; color:var(--ink-faint); font-size:15px; font-weight:700;}
.qe-submit{margin-top:6px; padding:16px; font-size:16px;}
.qe-toast{
  position:fixed; top:24px; left:50%; transform:translateX(-50%) translateY(-20px); opacity:0; z-index:80;
  background:var(--panel); border:1px solid var(--brand); color:var(--ink); padding:13px 22px; border-radius:12px;
  display:flex; align-items:center; gap:10px; box-shadow:0 14px 34px rgba(0,0,0,.4); transition:.25s var(--ease); pointer-events:none;
  min-width:260px; text-align:center; justify-content:center;
}
.qe-toast.show{ transform:translateX(-50%) translateY(0); opacity:1; }
.qe-toast.err{ border-color:var(--debt); }
.qe-recent{margin-top:28px;}
.qe-recent h2{font-size:14px; color:var(--ink-dim); margin-bottom:12px; display:flex; align-items:center; gap:8px;}
.product-section{ margin-bottom:18px; }
.product-section > label{ font-size:13px; font-weight:600; display:block; margin-bottom:8px; }
.products-grid{ display:flex; flex-wrap:wrap; gap:6px; }
.prod-cat-title{ width:100%; font-size:11px; color:var(--ink-faint); font-weight:700; letter-spacing:.5px; margin:8px 0 4px; }
.product-chip{
  padding:7px 13px; border-radius:999px; border:1px solid var(--line);
  background:var(--panel-2); font-size:13px; cursor:pointer; color:var(--ink-dim);
  transition:all .12s; user-select:none; -webkit-tap-highlight-color:transparent;
}
.product-chip:hover{ border-color:var(--brand); color:var(--brand); }
.product-chip.selected{ background:rgba(34,197,142,.12); border-color:var(--brand); color:var(--brand); font-weight:700; }
@media (max-width:600px){
  .qe-card{padding:18px;}
  .qe-toggle{gap:8px;}
  .qe-toggle button{padding:13px; font-size:14px;}
  .qe-amount-wrap input{font-size:22px; padding:14px;}
}
</style>

<div class="qe-wrap">
  <div class="qe-card">
    <!-- اسم الزبون -->
    <div class="field" style="position:relative; z-index:20;">
      <label for="qe-name"><?= icon('user', 15) ?> اسم الزبون</label>
      <input type="text" id="qe-name" placeholder="اكتب الاسم... (زبون جديد؟ فقط اكتبه)" autocomplete="off">
      <div class="suggest-list" id="qe-suggest" style="position:absolute; top:100%; margin-top:6px; z-index:200;"></div>
    </div>

    <!-- نوع العملية -->
    <div class="qe-toggle">
      <button type="button" data-type="purchase" class="<?= $defaultType==='purchase'?'active':'' ?>">
        <?= icon('plus', 18) ?> مشتريات (دين)
      </button>
      <button type="button" data-type="payment" class="<?= $defaultType==='payment'?'active':'' ?>">
        <?= icon('check', 18) ?> دفعة (تسديد)
      </button>
    </div>

    <!-- نوع المنتج (يظهر فقط عند المشتريات) -->
    <div class="product-section" id="product-section">
      <label><?= icon('tag', 14) ?> نوع المنتج (اختياري)</label>
      <div class="products-grid" id="products-grid">
        <?php foreach ($productTypes as $category => $items): ?>
        <?php if ($items): ?>
        <div class="prod-cat-title"><?= h($category) ?></div>
        <?php foreach ($items as $item): ?>
        <div class="product-chip" data-product="<?= h($item) ?>"><?= h($item) ?></div>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <div style="margin-top:8px;">
        <input type="text" id="qe-product" placeholder="أو اكتب نوع المنتج يدوياً..." style="font-size:15px; padding:10px 14px;">
      </div>
    </div>

    <!-- المبلغ -->
    <div class="field qe-amount-wrap">
      <label><?= icon('coin', 14) ?> المبلغ</label>
      <input type="number" id="qe-amount" inputmode="decimal" placeholder="0" min="0" step="1">
      <span class="qe-currency"><?= h($currency) ?></span>
    </div>

    <!-- ملاحظة -->
    <div class="field">
      <label for="qe-desc"><?= icon('receipt', 15) ?> ملاحظة (اختياري)</label>
      <input type="text" id="qe-desc" placeholder="مثال: رز وسكر وزيت">
    </div>

    <button class="btn btn-primary btn-block qe-submit" id="qe-submit">
      <?= icon('check-circle', 20) ?> حفظ العملية
    </button>
  </div>

  <!-- آخر عمليات اليوم -->
  <div class="qe-recent">
    <h2><?= icon('clock', 15) ?> عمليات اليوم</h2>
    <div class="timeline" id="qe-recent-list">
      <?php if (!$todayTx): ?>
        <div class="cc-empty-state" style="padding:30px;"><?= icon('receipt', 30) ?><p>لم تُسجَّل أي عملية اليوم.</p></div>
      <?php endif; ?>
      <?php foreach ($todayTx as $t): $isP = $t['type'] === 'purchase'; ?>
      <div class="tx-row <?= $isP ? 'purchase' : 'payment' ?>">
        <div class="tx-icon"><?= icon($isP ? 'plus' : 'check', 16) ?></div>
        <div class="tx-body">
          <div class="tx-desc">
            <?= h($t['customer_name']) ?>
            <span style="color:var(--ink-faint); font-weight:400;">— <?= h($t['description'] ?: ($isP?'مشتريات':'دفعة')) ?></span>
            <?php if ($t['product_type']): ?>
            <span class="tx-product-badge"><?= h($t['product_type']) ?></span>
            <?php endif; ?>
          </div>
          <div class="tx-date"><?= date('H:i', strtotime($t['created_at'])) ?></div>
        </div>
        <div class="tx-amount"><?= $isP?'+':'−' ?><?= money($t['amount']) ?> <?= h($currency) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="qe-toast" id="qe-toast"></div>

<script>
(function(){
  const csrf      = <?= json_encode(csrfToken()) ?>;
  const currency  = <?= json_encode($currency) ?>;
  const nameInput = document.getElementById('qe-name');
  const suggest   = document.getElementById('qe-suggest');
  const amtInput  = document.getElementById('qe-amount');
  const descInput = document.getElementById('qe-desc');
  const prodInput = document.getElementById('qe-product');
  const prodSec   = document.getElementById('product-section');
  const submitBtn = document.getElementById('qe-submit');
  const toast     = document.getElementById('qe-toast');
  const recentList= document.getElementById('qe-recent-list');
  const toggleBtns= [...document.querySelectorAll('.qe-toggle button')];

  let txType = <?= json_encode($defaultType) ?>;
  let selectedCustomerId = null;
  let selectedProduct = '';
  let activeIdx = -1, debounceTimer = null;

  // -- نوع العملية --
  toggleBtns.forEach(btn => btn.addEventListener('click', () => {
    toggleBtns.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    txType = btn.dataset.type;
    prodSec.style.display = txType === 'purchase' ? '' : 'none';
  }));
  prodSec.style.display = txType === 'purchase' ? '' : 'none';

  // -- اختيار منتج --
  document.querySelectorAll('.product-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      document.querySelectorAll('.product-chip').forEach(c => c.classList.remove('selected'));
      chip.classList.add('selected');
      selectedProduct = chip.dataset.product;
      prodInput.value = selectedProduct;
    });
  });
  prodInput.addEventListener('input', () => {
    selectedProduct = prodInput.value.trim();
    document.querySelectorAll('.product-chip').forEach(c => {
      c.classList.toggle('selected', c.dataset.product === selectedProduct);
    });
  });

  // -- اقتراح العملاء --
  function esc(s){ return s.replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
  function closeSug(){ suggest.classList.remove('show'); suggest.innerHTML=''; activeIdx=-1; }
  function renderSug(items, q){
    suggest.innerHTML = (!items.length)
      ? `<div class="suggest-item" data-new="1"><span class="s-name">${esc(q)} <small style="color:var(--ink-faint);">(زبون جديد)</small></span></div>`
      : items.map(it=>`<div class="suggest-item" data-id="${it.id}" data-name="${esc(it.name)}"><span class="s-name">${esc(it.name)}</span><span class="s-bal">${it.balance_fmt}</span></div>`).join('')
        + `<div class="suggest-item" data-new="1" style="color:var(--ink-faint)"><span class="s-name">${esc(q)} <small>(زبون جديد)</small></span></div>`;
    suggest.classList.add('show');
    suggest.querySelectorAll('.suggest-item').forEach(el=>{
      el.addEventListener('click', ()=>{
        nameInput.value = el.dataset.name || q;
        selectedCustomerId = el.dataset.id ? parseInt(el.dataset.id) : null;
        closeSug();
        amtInput.focus();
      });
    });
  }

  nameInput.addEventListener('input', ()=>{
    selectedCustomerId = null;
    const q = nameInput.value.trim();
    clearTimeout(debounceTimer);
    if (!q){ closeSug(); return; }
    debounceTimer = setTimeout(()=>{
      fetch('api/search-customers.php?q='+encodeURIComponent(q))
        .then(r=>r.json()).then(d=>renderSug(d.results||[], q));
    }, 100);
  });
  nameInput.addEventListener('keydown', (e)=>{
    const items = [...suggest.querySelectorAll('.suggest-item')];
    if (e.key==='ArrowDown'){ e.preventDefault(); activeIdx=Math.min(activeIdx+1,items.length-1); items.forEach((el,i)=>el.classList.toggle('active',i===activeIdx)); }
    else if (e.key==='ArrowUp'){ e.preventDefault(); activeIdx=Math.max(activeIdx-1,0); items.forEach((el,i)=>el.classList.toggle('active',i===activeIdx)); }
    else if (e.key==='Enter'){ e.preventDefault(); if(activeIdx>=0&&items[activeIdx]) items[activeIdx].click(); else amtInput.focus(); }
  });
  document.addEventListener('click',(e)=>{ if(!e.target.closest('#qe-name')&&!e.target.closest('#qe-suggest')) closeSug(); });

  amtInput.addEventListener('keydown', e=>{ if(e.key==='Enter'){ e.preventDefault(); descInput.focus(); } });
  descInput.addEventListener('keydown', e=>{ if(e.key==='Enter'){ e.preventDefault(); save(); } });

  // -- Toast --
  function showToast(msg, isErr){
    toast.textContent = msg;
    toast.className = 'qe-toast show' + (isErr?' err':'');
    setTimeout(()=>toast.classList.remove('show'), 3000);
  }

  // -- إضافة صف للعمليات الحديثة --
  function prependRecent(cName, amount, desc, product, type){
    const isP = type==='purchase';
    const row = document.createElement('div');
    row.className='tx-row '+type; row.style.animation='fadeUp .3s var(--ease)';
    const badge = product ? `<span class="tx-product-badge">${esc(product)}</span>` : '';
    row.innerHTML=`
      <div class="tx-icon">${isP ? '<?= addslashes(icon('plus',16)) ?>' : '<?= addslashes(icon('check',16)) ?>'}</div>
      <div class="tx-body">
        <div class="tx-desc">${esc(cName)} <span style="color:var(--ink-faint);font-weight:400;">— ${esc(desc||(isP?'مشتريات':'دفعة'))}</span>${badge}</div>
        <div class="tx-date">الآن</div>
      </div>
      <div class="tx-amount">${isP?'+':'−'}${Number(amount).toLocaleString()} ${currency}</div>`;
    const empty = recentList.querySelector('.cc-empty-state');
    if(empty) empty.remove();
    recentList.insertBefore(row, recentList.firstChild);
  }

  // -- الحفظ --
  function save(){
    const name    = nameInput.value.trim();
    const amount  = parseFloat(amtInput.value);
    const product = prodInput.value.trim() || selectedProduct;
    if(!name){ showToast('اكتب اسم الزبون أولًا',true); nameInput.focus(); return; }
    if(!amount||amount<=0){ showToast('اكتب مبلغًا صحيحًا',true); amtInput.focus(); return; }

    submitBtn.disabled=true; submitBtn.textContent='جارٍ الحفظ...';

    fetch('api/save-transaction.php',{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({csrf, type:txType, customer_id:selectedCustomerId, customer_name:name, amount, description:descInput.value.trim(), product_type:product})
    }).then(r=>r.json()).then(d=>{
      submitBtn.disabled=false; submitBtn.innerHTML='<?= addslashes(icon('check-circle',20)) ?> حفظ العملية';
      if(!d.ok){ showToast(d.error||'حدث خطأ',true); return; }
      showToast('تم — '+d.customer.name+' ('+d.customer.balance_fmt+')', false);
      prependRecent(d.customer.name, amount, descInput.value.trim(), product, txType);
      nameInput.value=''; amtInput.value=''; descInput.value=''; prodInput.value='';
      selectedProduct=''; selectedCustomerId=null;
      document.querySelectorAll('.product-chip').forEach(c=>c.classList.remove('selected'));
      nameInput.focus();
    }).catch(()=>{ submitBtn.disabled=false; submitBtn.innerHTML='حفظ العملية'; showToast('تعذّر الاتصال',true); });
  }
  submitBtn.addEventListener('click', save);
  nameInput.focus();
})();
</script>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
