<?php
require_once __DIR__ . '/config.php';

$search      = trim($_GET['q'] ?? '');
$order       = $_GET['sort'] ?? 'balance_desc';
$customers   = getCustomersWithBalances($pdo, $search, $order);
$totalDebt   = getTotalDebt($pdo);
$customerCount = count($customers);
$currencyCode  = $settings['currency'] ?? 'SYP';
$currency      = currencySymbol($currencyCode);

$pageTitle = $settings['shop_name'] ?? 'دفتر الدكان';
require __DIR__ . '/includes/page-header.php';
?>
<main class="page">
  <!-- Hero -->
  <section class="hero">
    <h1>مين عليه دين؟</h1>
    <p>ابحث عن الزبون وشوف تفاصيل الدين فورًا</p>

    <div class="search-shell" style="z-index:500; position:relative;">
      <div class="search-box">
        <?= icon('search', 20) ?>
        <input type="text" id="search-input" placeholder="اكتب اسم الزبون..." autocomplete="off" value="<?= h($search) ?>">
        <button class="search-clear" id="search-clear" type="button"><?= icon('x', 16) ?></button>
      </div>
      <div class="suggest-list" id="suggest-list"></div>
    </div>
  </section>

  <!-- إحصائيات -->
  <div class="stat-strip">
    <div class="stat-card" style="animation-delay:.04s">
      <div class="s-icon"><?= icon('users', 20) ?></div>
      <div>
        <div class="stat-value" id="stat-customers"><?= $customerCount ?></div>
        <div class="stat-label">عدد الزبائن</div>
      </div>
    </div>
    <div class="stat-card warn" style="animation-delay:.10s">
      <div class="s-icon"><?= icon('wallet', 20) ?></div>
      <div>
        <div class="stat-value"><?= money($totalDebt) ?> <small style="font-size:14px;"><?= h($currency) ?></small></div>
        <div class="stat-label">إجمالي الديون</div>
      </div>
    </div>
  </div>

  <!-- أزرار الإجراءات السريعة -->
  <div class="quick-actions">
    <a class="action-card debt" href="admin/quick-entry.php?type=purchase">
      <div class="ac-icon"><?= icon('plus', 24) ?></div>
      <span>مشتريات جديدة</span>
      <small>تسجيل دين جديد</small>
    </a>
    <a class="action-card payment" href="admin/quick-entry.php?type=payment">
      <div class="ac-icon"><?= icon('check', 24) ?></div>
      <span>تسديد دفعة</span>
      <small>تسجيل دفع</small>
    </a>
  </div>

  <!-- قائمة العملاء -->
  <div class="filter-row" style="margin-top:32px;">
    <span class="count"><?= $customerCount ?> زبون</span>
    <form method="get" id="sort-form">
      <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?= h($search) ?>"><?php endif; ?>
      <select name="sort" class="sort-select" onchange="document.getElementById('sort-form').submit()">
        <option value="balance_desc" <?= $order === 'balance_desc' ? 'selected' : '' ?>>الأعلى دينًا</option>
        <option value="recent"       <?= $order === 'recent'       ? 'selected' : '' ?>>الأحدث</option>
        <option value="name_asc"     <?= $order === 'name_asc'     ? 'selected' : '' ?>>الاسم (أ-ي)</option>
      </select>
    </form>
  </div>

  <div class="customer-grid">
    <?php if (!$customers): ?>
      <div class="cc-empty-state">
        <?= icon('users', 40) ?>
        <p>لا يوجد زبائن<?= $search !== '' ? ' مطابقون للبحث' : ' حتى الآن' ?>.</p>
      </div>
    <?php endif; ?>
    <?php foreach ($customers as $i => $c):
      $bal     = (float)$c['balance'];
      $initial = mb_substr($c['name'], 0, 1, 'UTF-8');
    ?>
    <a class="customer-card" href="customer.php?id=<?= (int)$c['id'] ?>" style="animation-delay:<?= min($i * 0.04, 0.5) ?>s">
      <div class="cc-top">
        <div class="cc-avatar"><?= h($initial) ?></div>
        <div>
          <div class="cc-name"><?= h($c['name']) ?></div>
          <?php if ($c['phone']): ?>
          <div class="cc-meta"><?= icon('phone', 13) ?> <?= h($c['phone']) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="cc-balance">
        <span class="label">الرصيد</span>
        <span class="val <?= $bal <= 0 ? 'zero' : '' ?>"><?= money($bal) ?> <?= h($currency) ?></span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</main>

<?php require __DIR__ . '/includes/page-footer.php'; ?>

<script>
(function(){
  const input    = document.getElementById('search-input');
  const list     = document.getElementById('suggest-list');
  const clearBtn = document.getElementById('search-clear');
  let activeIndex = -1, debounceTimer = null, abortCtrl = null;

  function esc(s){ return s.replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
  function hi(name, q){
    const i = name.toLowerCase().indexOf(q.toLowerCase());
    if (i<0) return esc(name);
    return esc(name.slice(0,i))+'<mark>'+esc(name.slice(i,i+q.length))+'</mark>'+esc(name.slice(i+q.length));
  }
  function close(){ list.classList.remove('show'); list.innerHTML=''; activeIndex=-1; }
  function render(items, q){
    activeIndex = -1;
    if (!items.length){ list.innerHTML='<div class="suggest-empty">لا يوجد زبون بهذا الاسم</div>'; list.classList.add('show'); return; }
    list.innerHTML = items.map(it=>`
      <div class="suggest-item" data-id="${it.id}">
        <span class="s-name">${hi(it.name,q)}</span>
        <span class="s-bal">${it.balance_fmt}</span>
      </div>`).join('');
    list.classList.add('show');
    list.querySelectorAll('.suggest-item').forEach(el=>{
      el.addEventListener('click',()=>{ window.location.href='customer.php?id='+el.dataset.id; });
    });
  }
  function setActive(idx){
    const els=[...list.querySelectorAll('.suggest-item')];
    els.forEach(el=>el.classList.remove('active'));
    if(els[idx]){ els[idx].classList.add('active'); els[idx].scrollIntoView({block:'nearest'}); }
    activeIndex=idx;
  }

  input.addEventListener('input',()=>{
    const q=input.value.trim();
    clearBtn.classList.toggle('show',q.length>0);
    clearTimeout(debounceTimer);
    if(q.length<2){ close(); return; }
    list.innerHTML='<div class="suggest-loading"><span class="spinner"></span> جارٍ البحث...</div>';
    list.classList.add('show');
    debounceTimer=setTimeout(()=>{
      if(abortCtrl) abortCtrl.abort();
      abortCtrl=new AbortController();
      fetch('api/search.php?q='+encodeURIComponent(q),{signal:abortCtrl.signal})
        .then(r=>r.json()).then(d=>render(d.results||[],q))
        .catch(err=>{ if(err.name!=='AbortError') close(); });
    },120);
  });

  input.addEventListener('keydown',(e)=>{
    const items=[...list.querySelectorAll('.suggest-item')];
    if(!items.length) return;
    if(e.key==='ArrowDown'){ e.preventDefault(); setActive(Math.min(activeIndex+1,items.length-1)); }
    else if(e.key==='ArrowUp'){ e.preventDefault(); setActive(Math.max(activeIndex-1,0)); }
    else if(e.key==='Enter'&&activeIndex>=0){ e.preventDefault(); items[activeIndex].click(); }
    else if(e.key==='Escape') close();
  });
  clearBtn.addEventListener('click',()=>{ input.value=''; clearBtn.classList.remove('show'); close(); input.focus(); });
  document.addEventListener('click',(e)=>{ if(!e.target.closest('.search-shell')) close(); });
})();
</script>
