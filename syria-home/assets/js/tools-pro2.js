/* ═══════════════════════════════════════════════
   Syria Home — "Pro" tool set, part 2.

   Eight more tools whose core function is normally sold as a paid product
   or subscription. Every one is an independent implementation written
   against plain browser APIs — nothing here is copied from, bundled with,
   or circumvents any commercial product. All processing is local: no data
   ever leaves the visitor's device.

   Registers into the same SHTools registry defined by tools.js, so
   tools.js (and ideally tools-pro.js) must load first.
   ═══════════════════════════════════════════════ */
(function () {
  'use strict';
  if (!window.SHTools) { console.warn('tools-pro2.js: load tools.js first'); return; }
  var T = window.SHTools;

  function $(root, sel) { return root.querySelector(sel); }
  function $$(root, sel) { return Array.prototype.slice.call(root.querySelectorAll(sel)); }
  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(function () {
      var old = btn.textContent; btn.textContent = 'Copied!';
      setTimeout(function () { btn.textContent = old; }, 1200);
    });
  }

  /* ══════════ 21. Contrast Checker (WCAG) ══════════ */
  T.register('contrast_checker', function (el) {
    el.innerHTML =
      '<div class="tool-controls">' +
        '<div class="row2">' +
          '<label>Text colour<input type="text" id="fg" value="#0f172a"></label>' +
          '<label>Background colour<input type="text" id="bg" value="#ffffff"></label>' +
        '</div>' +
        '<div class="row"><input type="color" id="fgc" value="#0f172a" style="width:60px;height:40px"><input type="color" id="bgc" value="#ffffff" style="width:60px;height:40px"><button class="btn-run" id="run">Check contrast</button></div>' +
        '<div class="result" id="res"></div>' +
      '</div>';
    var fg = $(el, '#fg'), bg = $(el, '#bg'), fgc = $(el, '#fgc'), bgc = $(el, '#bgc'), res = $(el, '#res');
    fgc.oninput = function () { fg.value = fgc.value; };
    bgc.oninput = function () { bg.value = bgc.value; };
    function hexToRgb(hex) {
      hex = hex.trim().replace('#', '');
      if (hex.length === 3) hex = hex.split('').map(function (c) { return c + c; }).join('');
      var n = parseInt(hex, 16);
      return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
    }
    function luminance(hex) {
      var rgb = hexToRgb(hex).map(function (c) { c /= 255; return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4); });
      return 0.2126 * rgb[0] + 0.7152 * rgb[1] + 0.0722 * rgb[2];
    }
    $(el, '#run').onclick = function () {
      var l1 = luminance(fg.value), l2 = luminance(bg.value);
      var ratio = (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
      var r = ratio.toFixed(2);
      function badge(pass) { return '<span style="color:' + (pass ? '#16a34a' : '#dc2626') + ';font-weight:800"><i class="fa-solid ' + (pass ? 'fa-circle-check' : 'fa-circle-xmark') + '"></i> ' + (pass ? 'Pass' : 'Fail') + '</span>'; }
      res.className = 'result show';
      res.innerHTML =
        '<div style="font-size:30px;font-weight:800">' + r + ':1</div>' +
        '<div style="padding:16px;border-radius:10px;margin:10px 0;color:' + fg.value + ';background:' + bg.value + ';font-weight:700">Sample text on this background</div>' +
        '<table style="width:100%;font-size:13px"><tr><td>Normal text — AA (4.5:1)</td><td>' + badge(ratio >= 4.5) + '</td></tr>' +
        '<tr><td>Normal text — AAA (7:1)</td><td>' + badge(ratio >= 7) + '</td></tr>' +
        '<tr><td>Large text — AA (3:1)</td><td>' + badge(ratio >= 3) + '</td></tr>' +
        '<tr><td>Large text — AAA (4.5:1)</td><td>' + badge(ratio >= 4.5) + '</td></tr></table>';
    };
  });

  /* ══════════ 22. Percentage Calculator ══════════ */
  T.register('percentage_calculator', function (el) {
    el.innerHTML =
      '<div class="tool-controls">' +
        '<div class="tabs" id="tabs" style="display:flex;gap:8px;flex-wrap:wrap">' +
          '<button class="btn-ghost active" data-m="of">X% of Y</button>' +
          '<button class="btn-ghost" data-m="is">X is what % of Y</button>' +
          '<button class="btn-ghost" data-m="chg">% change from X to Y</button>' +
        '</div>' +
        '<div class="row2" style="margin-top:10px"><label id="l1">X (%)<input type="text" id="x" value="20"></label><label id="l2">Y<input type="text" id="y" value="150"></label></div>' +
        '<button class="btn-run" id="run">Calculate</button>' +
        '<div class="result" id="res"></div>' +
      '</div>';
    var l1 = $(el, '#l1'), l2 = $(el, '#l2'), x = $(el, '#x'), y = $(el, '#y'), res = $(el, '#res'), mode = 'of';
    var labels = { of: ['X (%)', 'Y'], is: ['X', 'Y (total)'], chg: ['X (original)', 'Y (new)'] };
    $$(el, '#tabs button').forEach(function (b) {
      b.onclick = function () {
        $$(el, '#tabs button').forEach(function (o) { o.classList.remove('active'); });
        b.classList.add('active'); mode = b.dataset.m;
        l1.firstChild.textContent = labels[mode][0]; l2.firstChild.textContent = labels[mode][1];
      };
    });
    $(el, '#run').onclick = function () {
      var xv = parseFloat(x.value), yv = parseFloat(y.value);
      if (isNaN(xv) || isNaN(yv)) { res.className = 'result show'; res.textContent = 'Enter valid numbers.'; return; }
      var out;
      if (mode === 'of') out = (xv / 100 * yv).toLocaleString(undefined, { maximumFractionDigits: 4 });
      else if (mode === 'is') out = (xv / yv * 100).toFixed(2) + '%';
      else { var chg = (yv - xv) / Math.abs(xv) * 100; out = (chg >= 0 ? '+' : '') + chg.toFixed(2) + '%'; }
      res.className = 'result show';
      res.innerHTML = '<div style="font-size:28px;font-weight:800">' + out + '</div>';
    };
  });

  /* ══════════ 23. Loan / Mortgage Calculator ══════════ */
  T.register('loan_calculator', function (el) {
    el.innerHTML =
      '<div class="tool-controls">' +
        '<div class="row2">' +
          '<label>Loan amount<input type="text" id="p" value="20000"></label>' +
          '<label>Annual interest rate (%)<input type="text" id="r" value="6.5"></label>' +
        '</div>' +
        '<label>Term (years)<input type="text" id="n" value="5"></label>' +
        '<button class="btn-run" id="run">Calculate payment</button>' +
        '<div class="result" id="res"></div>' +
      '</div>';
    var p = $(el, '#p'), r = $(el, '#r'), n = $(el, '#n'), res = $(el, '#res');
    $(el, '#run').onclick = function () {
      var P = parseFloat(p.value), annual = parseFloat(r.value), years = parseFloat(n.value);
      if (!P || !years || isNaN(annual)) { res.className = 'result show'; res.textContent = 'Enter valid loan details.'; return; }
      var months = years * 12, i = annual / 100 / 12;
      var payment = i === 0 ? P / months : P * (i * Math.pow(1 + i, months)) / (Math.pow(1 + i, months) - 1);
      var total = payment * months, interest = total - P;
      res.className = 'result show';
      res.innerHTML =
        '<div style="font-size:28px;font-weight:800">' + payment.toLocaleString(undefined, { maximumFractionDigits: 2 }) + ' / month</div>' +
        '<p>Total paid over ' + years + ' years: <b>' + total.toLocaleString(undefined, { maximumFractionDigits: 2 }) + '</b></p>' +
        '<p>Total interest: <b>' + interest.toLocaleString(undefined, { maximumFractionDigits: 2 }) + '</b></p>';
    };
  });

  /* ══════════ 24. Random Number & PIN Generator ══════════ */
  T.register('random_number_generator', function (el) {
    el.innerHTML =
      '<div class="tool-controls">' +
        '<div class="row2"><label>Minimum<input type="text" id="min" value="1"></label><label>Maximum<input type="text" id="max" value="100"></label></div>' +
        '<div class="row2"><label>How many<input type="text" id="count" value="1"></label>' +
          '<label style="display:flex;align-items:center;gap:8px;margin-top:22px"><input type="checkbox" id="uniq" style="width:auto"> No duplicates</label></div>' +
        '<button class="btn-run" id="run">Generate</button>' +
        '<div class="result" id="res"></div>' +
      '</div>';
    var min = $(el, '#min'), max = $(el, '#max'), count = $(el, '#count'), uniq = $(el, '#uniq'), res = $(el, '#res');
    function secureRandInt(lo, hi) {
      var range = hi - lo + 1, buf = new Uint32Array(1);
      crypto.getRandomValues(buf);
      return lo + (buf[0] % range);
    }
    $(el, '#run').onclick = function () {
      var lo = parseInt(min.value, 10), hi = parseInt(max.value, 10), c = Math.max(1, parseInt(count.value, 10) || 1);
      if (isNaN(lo) || isNaN(hi) || hi < lo) { res.className = 'result show'; res.textContent = 'Enter a valid range.'; return; }
      if (uniq.checked && c > (hi - lo + 1)) { res.className = 'result show'; res.textContent = 'Range too small for that many unique numbers.'; return; }
      var out = [];
      if (uniq.checked) {
        var pool = []; for (var i = lo; i <= hi; i++) pool.push(i);
        for (var j = 0; j < c; j++) { var idx = secureRandInt(0, pool.length - 1); out.push(pool[idx]); pool.splice(idx, 1); }
      } else {
        for (var k = 0; k < c; k++) out.push(secureRandInt(lo, hi));
      }
      res.className = 'result show';
      res.innerHTML = '<div style="font-size:24px;font-weight:800;word-break:break-word">' + out.join(', ') + '</div>' +
        '<button class="btn-ghost" id="cp" style="margin-top:10px">Copy</button>';
      $(res, '#cp').onclick = function (e) { copyText(out.join(', '), e.target); };
    };
  });

  /* ══════════ 25. Timezone Converter ══════════ */
  T.register('timezone_converter', function (el) {
    var zones = ['UTC', 'America/New_York', 'America/Los_Angeles', 'America/Chicago', 'Europe/London', 'Europe/Berlin', 'Europe/Istanbul', 'Asia/Dubai', 'Asia/Riyadh', 'Asia/Damascus', 'Asia/Karachi', 'Asia/Kolkata', 'Asia/Dhaka', 'Asia/Singapore', 'Asia/Tokyo', 'Australia/Sydney'];
    var opts = zones.map(function (z) { return '<option value="' + z + '">' + z + '</option>'; }).join('');
    el.innerHTML =
      '<div class="tool-controls">' +
        '<label>Date &amp; time<input type="datetime-local" id="dt"></label>' +
        '<div class="row2"><label>From timezone<select id="from">' + opts + '</select></label>' +
          '<label>To timezone<select id="to">' + opts + '</select></label></div>' +
        '<button class="btn-run" id="run">Convert</button>' +
        '<div class="result" id="res"></div>' +
      '</div>';
    var dt = $(el, '#dt'), from = $(el, '#from'), to = $(el, '#to'), res = $(el, '#res');
    var now = new Date(); dt.value = now.toISOString().slice(0, 16);
    to.value = 'Asia/Damascus';
    $(el, '#run').onclick = function () {
      if (!dt.value) { res.className = 'result show'; res.textContent = 'Pick a date and time.'; return; }
      try {
        // Interpret the picked local time as wall-clock time in the "from" zone,
        // by finding its UTC offset via Intl, then format that instant in "to".
        var naive = new Date(dt.value + ':00Z');
        var fmt = new Intl.DateTimeFormat('en-US', { timeZone: from.value, timeZoneName: 'shortOffset', hour: '2-digit' });
        var parts = fmt.formatToParts(naive);
        var offsetPart = parts.find(function (p) { return p.type === 'timeZoneName'; });
        var m = offsetPart && offsetPart.value.match(/GMT([+-]\d+)(?::?(\d+))?/);
        var offsetMin = m ? (parseInt(m[1], 10) * 60 + (m[1][0] === '-' ? -1 : 1) * (parseInt(m[2] || '0', 10))) : 0;
        var utcInstant = new Date(naive.getTime() - offsetMin * 60000);
        var outFmt = new Intl.DateTimeFormat('en-US', { timeZone: to.value, dateStyle: 'medium', timeStyle: 'short' });
        res.className = 'result show';
        res.innerHTML = '<div style="font-size:22px;font-weight:800">' + esc(outFmt.format(utcInstant)) + '</div><p>' + esc(to.value) + '</p>';
      } catch (e) {
        res.className = 'result show'; res.textContent = 'Could not convert — try a different browser or timezone.';
      }
    };
  });

  /* ══════════ 26. Slug Generator ══════════ */
  T.register('slug_generator', function (el) {
    el.innerHTML =
      '<div class="tool-controls">' +
        '<label>Title / text<input type="text" id="input" placeholder="My Awesome Blog Post Title!"></label>' +
        '<div class="row2"><label>Word separator<select id="sep"><option value="-">Hyphen (-)</option><option value="_">Underscore (_)</option></select></label>' +
          '<label>Max length<input type="text" id="len" value="60"></label></div>' +
        '<div class="result" id="res"></div>' +
      '</div>';
    var input = $(el, '#input'), sep = $(el, '#sep'), len = $(el, '#len'), res = $(el, '#res');
    function slugify() {
      var s = input.value.normalize('NFKD').replace(/[̀-ͯ]/g, '');
      s = s.toLowerCase().replace(/[^a-z0-9]+/g, sep.value).replace(new RegExp('\\' + sep.value + '+', 'g'), sep.value).replace(new RegExp('^\\' + sep.value + '+|\\' + sep.value + '+$', 'g'), '');
      var max = parseInt(len.value, 10) || 60;
      if (s.length > max) { s = s.slice(0, max); s = s.replace(new RegExp('\\' + sep.value + '[^' + sep.value + ']*$'), ''); }
      return s;
    }
    function update() {
      var slug = slugify();
      res.className = 'result show';
      res.innerHTML = '<div style="font-family:\'JetBrains Mono\',monospace;font-size:15px;word-break:break-all">' + esc(slug) + '</div><button class="btn-ghost" id="cp" style="margin-top:10px">Copy</button>';
      $(res, '#cp').onclick = function (e) { copyText(slug, e.target); };
    }
    input.oninput = update; sep.onchange = update; len.oninput = update;
    update();
  });

  /* ══════════ 27. HTML Entity Encoder / Decoder ══════════ */
  T.register('html_entity_tool', function (el) {
    el.innerHTML =
      '<div class="tool-controls">' +
        '<textarea id="input" placeholder="<div class=&quot;example&quot;>Text &amp; more</div>"></textarea>' +
        '<div class="row"><button class="btn-run" id="enc">Encode</button><button class="btn-ghost" id="dec">Decode</button><button class="btn-ghost" id="cp">Copy result</button></div>' +
        '<div class="result" id="res"></div>' +
      '</div>';
    var input = $(el, '#input'), res = $(el, '#res'), out = '';
    function show(text) { out = text; res.className = 'result show'; res.innerHTML = '<pre style="white-space:pre-wrap;word-break:break-all;font-family:\'JetBrains Mono\',monospace;font-size:13px;margin:0">' + esc(text) + '</pre>'; }
    $(el, '#enc').onclick = function () {
      var div = document.createElement('div'); div.textContent = input.value;
      show(div.innerHTML);
    };
    $(el, '#dec').onclick = function () {
      var div = document.createElement('div'); div.innerHTML = input.value;
      show(div.textContent);
    };
    $(el, '#cp').onclick = function (e) { copyText(out, e.target); };
  });

  /* ══════════ 28. Invoice Generator ══════════ */
  T.register('invoice_generator', function (el) {
    el.innerHTML =
      '<div class="tool-controls" id="ig">' +
        '<div class="row2"><label>From (your business)<textarea id="from" rows="3">Your Company\nAddress\nemail@example.com</textarea></label>' +
          '<label>Bill to<textarea id="to" rows="3">Client Name\nAddress\nemail@example.com</textarea></label></div>' +
        '<div class="row2"><label>Invoice #<input type="text" id="num" value="INV-001"></label><label>Date<input type="date" id="date"></label></div>' +
        '<div id="items"></div>' +
        '<button class="btn-ghost" id="addItem" type="button">+ Add line item</button>' +
        '<div class="row2"><label>Currency symbol<input type="text" id="cur" value="$"></label><label>Tax (%)<input type="text" id="tax" value="0"></label></div>' +
        '<div class="row"><button class="btn-run" id="preview">Preview invoice</button></div>' +
        '<div id="printArea" style="display:none"></div>' +
      '</div>';
    var itemsEl = $(el, '#items'), rowN = 0;
    function addRow(desc, qty, price) {
      rowN++;
      var row = document.createElement('div');
      row.className = 'row2'; row.style.marginBottom = '6px';
      row.innerHTML =
        '<input type="text" class="idesc" placeholder="Description" value="' + esc(desc || '') + '" style="flex:2">' +
        '<input type="text" class="iqty" placeholder="Qty" value="' + (qty || 1) + '" style="max-width:80px">' +
        '<input type="text" class="iprice" placeholder="Price" value="' + (price || '') + '" style="max-width:110px">';
      itemsEl.appendChild(row);
    }
    addRow('Service or product', 1, '100.00');
    $(el, '#addItem').onclick = function () { addRow('', 1, ''); };
    var dateInput = $(el, '#date'); dateInput.value = new Date().toISOString().slice(0, 10);
    $(el, '#preview').onclick = function () {
      var rows = $$(itemsEl, '.row2');
      var lines = rows.map(function (r) {
        var d = r.querySelector('.idesc').value, q = parseFloat(r.querySelector('.iqty').value) || 0, p = parseFloat(r.querySelector('.iprice').value) || 0;
        return { d: d, q: q, p: p, sub: q * p };
      }).filter(function (l) { return l.d; });
      var cur = $(el, '#cur').value || '$';
      var taxPct = parseFloat($(el, '#tax').value) || 0;
      var subtotal = lines.reduce(function (s, l) { return s + l.sub; }, 0);
      var tax = subtotal * taxPct / 100, total = subtotal + tax;
      var from = esc($(el, '#from').value).replace(/\n/g, '<br>');
      var to = esc($(el, '#to').value).replace(/\n/g, '<br>');
      var html =
        '<div style="max-width:640px;margin:0 auto;padding:30px;background:#fff;font-family:Arial,sans-serif;color:#0f172a">' +
          '<div style="display:flex;justify-content:space-between;margin-bottom:24px"><h2 style="margin:0">INVOICE</h2><div style="text-align:right"><b>' + esc($(el, '#num').value) + '</b><br>' + esc(dateInput.value) + '</div></div>' +
          '<div style="display:flex;justify-content:space-between;margin-bottom:20px;font-size:13px"><div>' + from + '</div><div style="text-align:right">' + to + '</div></div>' +
          '<table style="width:100%;border-collapse:collapse;font-size:13px"><tr style="border-bottom:2px solid #0f172a"><th style="text-align:left;padding:6px 0">Description</th><th style="text-align:right;padding:6px 0">Qty</th><th style="text-align:right;padding:6px 0">Price</th><th style="text-align:right;padding:6px 0">Subtotal</th></tr>' +
          lines.map(function (l) { return '<tr style="border-bottom:1px solid #e2e8f0"><td style="padding:6px 0">' + esc(l.d) + '</td><td style="text-align:right">' + l.q + '</td><td style="text-align:right">' + cur + l.p.toFixed(2) + '</td><td style="text-align:right">' + cur + l.sub.toFixed(2) + '</td></tr>'; }).join('') +
          '</table>' +
          '<div style="text-align:right;margin-top:16px;font-size:13px"><div>Subtotal: ' + cur + subtotal.toFixed(2) + '</div>' + (taxPct ? '<div>Tax (' + taxPct + '%): ' + cur + tax.toFixed(2) + '</div>' : '') + '<div style="font-size:18px;font-weight:800;margin-top:6px">Total: ' + cur + total.toFixed(2) + '</div></div>' +
        '</div>';
      var printArea = $(el, '#printArea');
      printArea.innerHTML = html + '<div style="text-align:center;margin-top:16px" class="no-print"><button class="btn-run" id="doPrint">Print / Save as PDF</button></div>';
      printArea.style.display = 'block';
      $(printArea, '#doPrint').onclick = function () { window.print(); };
    };
    var style = document.createElement('style');
    style.textContent = '@media print { body * { visibility: hidden; } #printArea, #printArea * { visibility: visible; } #printArea { position: absolute; left: 0; top: 0; width: 100%; } .no-print { display: none !important; } }';
    document.head.appendChild(style);
  });

})();
