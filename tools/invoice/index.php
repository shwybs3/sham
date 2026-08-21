<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode(['@context'=>'https://schema.org','@type'=>'WebApplication','name'=>'مولّد الفاتورة المجانية','url'=>TOOLS_BASE_URL.'/tools/invoice/','description'=>'أنشئ فاتورة احترافية وطبعها أو حفّظها PDF — مجاناً بدون تسجيل.','applicationCategory'=>'BusinessApplication','operatingSystem'=>'All','offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD']]);
tool_head('مولّد الفاتورة المجانية — أنشئ وطبع فاتورة PDF | yassota','أنشئ فاتورة احترافية مجانية وطبعها أو حفّظها PDF — مع الضريبة والخصم وبيانات العميل والشركة.',$schema,'#0f766e');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#ccfbf1"><span style="font-size:24px">🧾</span></div>
    <h1>مولّد الفاتورة المجانية</h1>
    <p>أنشئ فاتورة احترافية وطبعها مباشرة أو احفظها PDF — مجاناً، بدون تسجيل.</p>
  </div>
  <div class="t-card" style="margin-bottom:16px">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
      <div>
        <h3 style="font-size:14px;margin-bottom:10px">بيانات المُصدِر</h3>
        <input type="text" id="from-name" class="t-input" style="width:100%;margin-bottom:8px" placeholder="اسم شركتك / اسمك">
        <input type="text" id="from-addr" class="t-input" style="width:100%;margin-bottom:8px" placeholder="عنوانك">
        <input type="text" id="from-phone" class="t-input" style="width:100%;margin-bottom:8px" placeholder="رقم الهاتف">
        <input type="text" id="from-email" class="t-input" style="width:100%" placeholder="البريد الإلكتروني">
      </div>
      <div>
        <h3 style="font-size:14px;margin-bottom:10px">بيانات العميل</h3>
        <input type="text" id="to-name" class="t-input" style="width:100%;margin-bottom:8px" placeholder="اسم العميل">
        <input type="text" id="to-addr" class="t-input" style="width:100%;margin-bottom:8px" placeholder="عنوان العميل">
        <input type="text" id="to-phone" class="t-input" style="width:100%;margin-bottom:8px" placeholder="رقم هاتف العميل">
        <input type="text" id="to-email" class="t-input" style="width:100%" placeholder="بريد العميل">
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:16px">
      <div><label class="t-label">رقم الفاتورة</label><input type="text" id="inv-no" class="t-input" value="INV-001" dir="ltr" style="width:100%"></div>
      <div><label class="t-label">تاريخ الإصدار</label><input type="date" id="inv-date" class="t-input" style="width:100%"></div>
      <div><label class="t-label">تاريخ الاستحقاق</label><input type="date" id="due-date" class="t-input" style="width:100%"></div>
    </div>
    <!-- Items -->
    <h3 style="font-size:14px;margin-bottom:10px">البنود</h3>
    <div style="display:grid;grid-template-columns:3fr 1fr 1fr 36px;gap:6px;margin-bottom:6px">
      <div style="font-size:11px;color:#94a3b8;font-weight:700">الوصف</div><div style="font-size:11px;color:#94a3b8;font-weight:700">الكمية</div><div style="font-size:11px;color:#94a3b8;font-weight:700">السعر</div><div></div>
    </div>
    <div id="items-list"></div>
    <button onclick="addItem()" style="background:#ccfbf1;color:#0f766e;border:none;border-radius:8px;padding:8px 16px;font-size:13px;cursor:pointer;margin-top:8px">+ إضافة بند</button>
    <!-- Totals -->
    <div style="max-width:300px;margin:16px 0 0 auto">
      <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:13px">
        <span>المجموع الفرعي</span><span id="subtotal">0.00</span>
      </div>
      <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:13px">
        <span>ضريبة <input type="number" id="tax-pct" value="15" min="0" max="100" step=".1" style="width:45px;text-align:center;border:1px solid #e2e8f0;border-radius:6px;padding:2px 4px;font-size:12px" oninput="calcTotal()">%</span>
        <span id="tax-amt">0.00</span>
      </div>
      <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:13px">
        <span>خصم <input type="number" id="disc-amt" value="0" min="0" step=".01" style="width:60px;text-align:center;border:1px solid #e2e8f0;border-radius:6px;padding:2px 4px;font-size:12px" oninput="calcTotal()"></span>
        <span id="disc-show">0.00</span>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:18px;font-weight:900;color:#0f766e;border-top:2px solid #0f766e;padding-top:8px;margin-top:8px">
        <span>الإجمالي</span><span id="total">0.00</span>
      </div>
      <div style="margin-top:8px">
        <label class="t-label" style="font-size:11px">العملة</label>
        <select id="currency" class="t-input" style="width:100%" onchange="calcTotal()">
          <option value="USD">$ دولار أمريكي</option>
          <option value="SYP">ل.س ليرة سورية</option>
          <option value="SAR">ر.س ريال سعودي</option>
          <option value="AED">د.إ درهم إماراتي</option>
          <option value="EUR">€ يورو</option>
          <option value="TRY">₺ ليرة تركية</option>
        </select>
      </div>
    </div>
    <div><label class="t-label">ملاحظات</label><textarea id="notes" class="t-input" rows="2" style="width:100%" placeholder="شروط الدفع، ملاحظات إضافية…"></textarea></div>
    <button onclick="printInvoice()" class="t-btn" style="width:100%;background:#0f766e;margin-top:16px">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      طباعة / حفظ PDF
    </button>
  </div>
  <?php tool_ad(); ?>
</main>
<script>
let items=[];
function addItem(d='',q=1,p=0){
  items.push({d,q,p});renderItems();calcTotal();
}
function removeItem(i){items.splice(i,1);renderItems();calcTotal();}
function renderItems(){
  document.getElementById('items-list').innerHTML=items.map((it,i)=>`
    <div style="display:grid;grid-template-columns:3fr 1fr 1fr 36px;gap:6px;margin-bottom:6px">
      <input class="t-input" value="${it.d}" oninput="items[${i}].d=this.value" placeholder="وصف الخدمة / المنتج" style="font-size:13px">
      <input class="t-input" type="number" value="${it.q}" min="1" oninput="items[${i}].q=+this.value;calcTotal()" dir="ltr" style="font-size:13px;text-align:center">
      <input class="t-input" type="number" value="${it.p}" min="0" step=".01" oninput="items[${i}].p=+this.value;calcTotal()" dir="ltr" style="font-size:13px;text-align:right">
      <button onclick="removeItem(${i})" style="background:#fee2e2;color:#dc2626;border:none;border-radius:6px;cursor:pointer;font-size:14px">✕</button>
    </div>`).join('');
}
function calcTotal(){
  const sub=items.reduce((s,it)=>s+(it.q*it.p),0);
  const tax=sub*(+document.getElementById('tax-pct').value||0)/100;
  const disc=+document.getElementById('disc-amt').value||0;
  const total=sub+tax-disc;
  const cur=document.getElementById('currency').value;
  const fmt=v=>v.toFixed(2)+' '+cur;
  document.getElementById('subtotal').textContent=fmt(sub);
  document.getElementById('tax-amt').textContent=fmt(tax);
  document.getElementById('disc-show').textContent='-'+fmt(disc);
  document.getElementById('total').textContent=fmt(total);
}
function printInvoice(){
  const cur=document.getElementById('currency').value;
  const fmt=v=>v.toFixed(2)+' '+cur;
  const sub=items.reduce((s,it)=>s+(it.q*it.p),0);
  const tax=sub*(+document.getElementById('tax-pct').value||0)/100;
  const disc=+document.getElementById('disc-amt').value||0;
  const total=sub+tax-disc;
  const w=window.open('','_blank');
  w.document.write(`<!DOCTYPE html><html dir="rtl" lang="ar"><head><meta charset="UTF-8"><title>فاتورة ${document.getElementById('inv-no').value}</title>
  <style>body{font-family:Arial,sans-serif;padding:40px;color:#1e293b}h1{color:#0f766e}table{width:100%;border-collapse:collapse}th,td{padding:10px 12px;border:1px solid #e2e8f0;text-align:right}th{background:#f0fdfa}.total-row{font-weight:700;font-size:16px;color:#0f766e}@media print{button{display:none}}</style></head><body>
  <div style="display:flex;justify-content:space-between;margin-bottom:30px">
    <div><h1>فاتورة</h1><div>${document.getElementById('from-name').value}</div><div>${document.getElementById('from-addr').value}</div><div>${document.getElementById('from-phone').value}</div><div>${document.getElementById('from-email').value}</div></div>
    <div style="text-align:left"><div><b>رقم:</b> ${document.getElementById('inv-no').value}</div><div><b>تاريخ:</b> ${document.getElementById('inv-date').value}</div><div><b>استحقاق:</b> ${document.getElementById('due-date').value}</div></div>
  </div>
  <div style="margin-bottom:20px"><b>إلى:</b> ${document.getElementById('to-name').value}<br>${document.getElementById('to-addr').value}<br>${document.getElementById('to-phone').value}</div>
  <table><thead><tr><th>الوصف</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr></thead><tbody>
  ${items.map(it=>`<tr><td>${it.d}</td><td>${it.q}</td><td>${fmt(it.p)}</td><td>${fmt(it.q*it.p)}</td></tr>`).join('')}
  </tbody><tfoot>
    <tr><td colspan="3">المجموع الفرعي</td><td>${fmt(sub)}</td></tr>
    <tr><td colspan="3">ضريبة (${document.getElementById('tax-pct').value}%)</td><td>${fmt(tax)}</td></tr>
    <tr><td colspan="3">خصم</td><td>-${fmt(disc)}</td></tr>
    <tr class="total-row"><td colspan="3">الإجمالي</td><td>${fmt(total)}</td></tr>
  </tfoot></table>
  ${document.getElementById('notes').value?`<div style="margin-top:20px;padding:12px;background:#f0fdfa;border-radius:8px"><b>ملاحظات:</b> ${document.getElementById('notes').value}</div>`:''}
  <button onclick="window.print()" style="margin-top:20px;background:#0f766e;color:#fff;border:none;border-radius:8px;padding:12px 24px;font-size:15px;cursor:pointer">🖨 طباعة / PDF</button>
  </body></html>`);
  w.document.close();
  setTimeout(()=>w.print(),500);
}
// Init
const today=new Date().toISOString().slice(0,10);
document.getElementById('inv-date').value=today;
const due=new Date(Date.now()+30*864e5).toISOString().slice(0,10);
document.getElementById('due-date').value=due;
addItem('خدمة / منتج',1,100);
</script>
<?php tool_footer(); ?>
