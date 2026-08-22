/* ═══════════════════════════════════════════════
   Syria Home — 20 free client-side tools.
   Everything here runs entirely in the browser: no
   uploads, no server calls (the QR generator is the
   one exception — it calls a public, free QR image API
   since it only needs the text you asked to encode).
   ═══════════════════════════════════════════════ */
(function () {
  'use strict';

  const SHTools = { registry: {} };
  SHTools.register = function (key, fn) { SHTools.registry[key] = fn; };
  SHTools.mount = function (containerId) {
    const el = document.getElementById(containerId);
    if (!el) return;
    const key = el.dataset.tool;
    const fn = SHTools.registry[key];
    if (fn) fn(el); else el.innerHTML = '<p>This tool engine isn\'t available yet.</p>';
  };
  window.SHTools = SHTools;

  /* ── shared helpers ── */
  function h(html) { const d = document.createElement('div'); d.innerHTML = html.trim(); return d.firstElementChild; }
  function $(root, sel) { return root.querySelector(sel); }
  function fmtBytes(n) { if (n < 1024) return n + ' B'; if (n < 1024 * 1024) return (n / 1024).toFixed(1) + ' KB'; return (n / 1024 / 1024).toFixed(2) + ' MB'; }
  function copyBtn(getText) {
    return function (e) {
      navigator.clipboard.writeText(getText()).then(() => {
        const btn = e.target.closest('button');
        const old = btn.textContent; btn.textContent = 'Copied!';
        setTimeout(() => btn.textContent = old, 1200);
      });
    };
  }
  function download(filename, blobOrUrl) {
    const a = document.createElement('a');
    a.href = typeof blobOrUrl === 'string' ? blobOrUrl : URL.createObjectURL(blobOrUrl);
    a.download = filename;
    document.body.appendChild(a); a.click(); a.remove();
  }

  /* ══════════ 1. Image Format Converter (PNG/JPG/WebP) ══════════ */
  SHTools.register('png_to_webp', function (el) {
    el.innerHTML = `
      <div class="tool-controls">
        <div class="drop" id="drop">Click to choose an image, or drag one here (PNG, JPG, WebP)</div>
        <input type="file" id="file" accept="image/*" style="display:none">
        <div class="row">
          <label style="flex:1;min-width:160px">Output format
            <select id="fmt"><option value="image/webp">WebP</option><option value="image/png">PNG</option><option value="image/jpeg">JPEG</option></select>
          </label>
          <label style="flex:1;min-width:160px">Quality (WebP/JPEG): <span id="qv">85</span>%
            <input type="range" id="q" min="10" max="100" value="85">
          </label>
        </div>
        <div class="result" id="res"></div>
      </div>`;
    const drop = $(el, '#drop'), file = $(el, '#file'), fmt = $(el, '#fmt'), q = $(el, '#q'), qv = $(el, '#qv'), res = $(el, '#res');
    q.oninput = () => qv.textContent = q.value;
    drop.onclick = () => file.click();
    ['dragover', 'dragleave', 'drop'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.toggle('drag', ev === 'dragover'); }));
    drop.addEventListener('drop', e => { if (e.dataTransfer.files[0]) handle(e.dataTransfer.files[0]); });
    file.onchange = () => file.files[0] && handle(file.files[0]);

    function handle(f) {
      const img = new Image();
      const origUrl = URL.createObjectURL(f);
      img.onload = () => {
        const canvas = document.createElement('canvas');
        canvas.width = img.width; canvas.height = img.height;
        canvas.getContext('2d').drawImage(img, 0, 0);
        const mime = fmt.value;
        canvas.toBlob(blob => {
          const ext = mime.split('/')[1];
          res.className = 'result show';
          res.innerHTML = `<div class="row"><img src="${URL.createObjectURL(blob)}" style="max-width:220px;border-radius:10px;border:1px solid var(--line)"><div>
            <p><b>Original:</b> ${fmtBytes(f.size)}<br><b>Converted (${ext.toUpperCase()}):</b> ${fmtBytes(blob.size)} (${blob.size < f.size ? '−' + Math.round(100 - blob.size / f.size * 100) + '%' : '+' + Math.round(blob.size / f.size * 100 - 100) + '%'})</p>
            <button class="btn-run" id="dl">Download .${ext}</button></div></div>`;
          $(res, '#dl').onclick = () => download('converted.' + ext, blob);
        }, mime, mime === 'image/png' ? undefined : Number(q.value) / 100);
      };
      img.src = origUrl;
    }
  });

  /* ══════════ 2. Image Compressor ══════════ */
  SHTools.register('image_compressor', function (el) {
    el.innerHTML = `
      <div class="tool-controls">
        <div class="drop" id="drop">Click to choose an image to compress</div>
        <input type="file" id="file" accept="image/*" style="display:none">
        <label>Quality: <span id="qv">70</span>% <input type="range" id="q" min="10" max="95" value="70"></label>
        <label style="max-width:220px">Max width (px, 0 = no resize)<input type="text" id="mw" value="1600"></label>
        <div class="result" id="res"></div>
      </div>`;
    const drop = $(el, '#drop'), file = $(el, '#file'), q = $(el, '#q'), qv = $(el, '#qv'), mw = $(el, '#mw'), res = $(el, '#res');
    q.oninput = () => qv.textContent = q.value;
    drop.onclick = () => file.click();
    file.onchange = () => file.files[0] && run(file.files[0]);
    function run(f) {
      const img = new Image();
      img.onload = () => {
        let w = img.width, hgt = img.height;
        const maxW = Number(mw.value) || 0;
        if (maxW && w > maxW) { hgt = Math.round(hgt * (maxW / w)); w = maxW; }
        const c = document.createElement('canvas'); c.width = w; c.height = hgt;
        c.getContext('2d').drawImage(img, 0, 0, w, hgt);
        c.toBlob(blob => {
          res.className = 'result show';
          res.innerHTML = `<p><b>Original:</b> ${fmtBytes(f.size)} → <b>Compressed:</b> ${fmtBytes(blob.size)} (saved ${Math.max(0, Math.round(100 - blob.size / f.size * 100))}%)</p><button class="btn-run" id="dl">Download compressed image</button>`;
          $(res, '#dl').onclick = () => download('compressed.jpg', blob);
        }, 'image/jpeg', Number(q.value) / 100);
      };
      img.src = URL.createObjectURL(f);
    }
  });

  /* ══════════ 3. QR Code Generator ══════════ */
  SHTools.register('qr_code_generator', function (el) {
    el.innerHTML = `
      <div class="tool-controls">
        <label>Text or URL<input type="text" id="txt" placeholder="https://example.com" value="https://"></label>
        <div class="row"><button class="btn-run" id="gen">Generate QR code</button></div>
        <div class="result" id="res"></div>
        <p class="hint" style="font-size:12px;color:var(--muted)">Generated via a public QR image API — only the text you enter is sent, nothing else.</p>
      </div>`;
    const txt = $(el, '#txt'), res = $(el, '#res');
    $(el, '#gen').onclick = () => {
      const data = encodeURIComponent(txt.value || ' ');
      const url = `https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=${data}`;
      res.className = 'result show';
      res.innerHTML = `<div style="text-align:center"><img src="${url}" width="220" height="220" style="border-radius:10px;border:1px solid var(--line)"><br><br><a class="btn-run" style="display:inline-block;text-decoration:none" href="${url}" download="qr-code.png">Download PNG</a></div>`;
    };
  });

  /* ══════════ 4. Password Generator ══════════ */
  SHTools.register('password_generator', function (el) {
    el.innerHTML = `
      <div class="tool-controls">
        <label>Length: <span id="lv">16</span><input type="range" id="len" min="6" max="64" value="16"></label>
        <div class="row">
          <label><input type="checkbox" id="up" checked style="width:auto"> Uppercase (A-Z)</label>
          <label><input type="checkbox" id="lo" checked style="width:auto"> Lowercase (a-z)</label>
          <label><input type="checkbox" id="nu" checked style="width:auto"> Numbers (0-9)</label>
          <label><input type="checkbox" id="sy" style="width:auto"> Symbols (!@#$...)</label>
        </div>
        <button class="btn-run" id="gen">Generate password</button>
        <div class="result" id="res"></div>
      </div>`;
    const len = $(el, '#len'), lv = $(el, '#lv'), res = $(el, '#res');
    len.oninput = () => lv.textContent = len.value;
    $(el, '#gen').onclick = () => {
      const sets = [];
      if ($(el, '#up').checked) sets.push('ABCDEFGHJKLMNPQRSTUVWXYZ');
      if ($(el, '#lo').checked) sets.push('abcdefghijkmnpqrstuvwxyz');
      if ($(el, '#nu').checked) sets.push('23456789');
      if ($(el, '#sy').checked) sets.push('!@#$%^&*-_=+?');
      if (!sets.length) { res.className = 'result show'; res.innerHTML = 'Pick at least one character set.'; return; }
      const all = sets.join('');
      const arr = new Uint32Array(Number(len.value));
      crypto.getRandomValues(arr);
      let pw = Array.from(arr, n => all[n % all.length]).join('');
      const strength = sets.length >= 3 && pw.length >= 12 ? 'Strong' : sets.length >= 2 && pw.length >= 8 ? 'Medium' : 'Weak';
      res.className = 'result show';
      res.innerHTML = `<div style="font-family:'JetBrains Mono',monospace;font-size:18px;font-weight:700;word-break:break-all">${pw}</div><p>Strength: <b>${strength}</b></p><button class="btn-run" id="cp">Copy password</button>`;
      $(res, '#cp').onclick = copyBtn(() => pw);
    };
  });

  /* ══════════ 5. JSON Formatter & Validator ══════════ */
  SHTools.register('json_formatter', function (el) {
    el.innerHTML = `
      <div class="tool-controls">
        <textarea id="input" placeholder='{"hello":"world"}'></textarea>
        <div class="row"><button class="btn-run" id="fmt">Format</button><button class="btn-ghost" id="min">Minify</button><button class="btn-ghost" id="cp">Copy</button></div>
        <div class="result" id="res"></div>
      </div>`;
    const input = $(el, '#input'), res = $(el, '#res');
    function run(pretty) {
      try {
        const obj = JSON.parse(input.value);
        input.value = pretty ? JSON.stringify(obj, null, 2) : JSON.stringify(obj);
        res.className = 'result show'; res.innerHTML = '<span style="color:var(--brand1)">✓ Valid JSON</span>';
      } catch (e) { res.className = 'result show'; res.innerHTML = '<span style="color:#dc2626">✗ ' + e.message + '</span>'; }
    }
    $(el, '#fmt').onclick = () => run(true);
    $(el, '#min').onclick = () => run(false);
    $(el, '#cp').onclick = copyBtn(() => input.value);
  });

  /* ══════════ 6. Base64 Encoder / Decoder ══════════ */
  SHTools.register('base64_tool', function (el) {
    el.innerHTML = `
      <div class="tool-controls">
        <textarea id="input" placeholder="Text to encode/decode..."></textarea>
        <div class="row"><button class="btn-run" id="enc">Encode</button><button class="btn-ghost" id="dec">Decode</button><button class="btn-ghost" id="cp">Copy</button></div>
        <div class="result" id="res"></div>
      </div>`;
    const input = $(el, '#input'), res = $(el, '#res');
    $(el, '#enc').onclick = () => {
      try { input.value = btoa(unescape(encodeURIComponent(input.value))); res.className = 'result show'; res.textContent = 'Encoded.'; }
      catch (e) { res.className = 'result show'; res.textContent = 'Error: ' + e.message; }
    };
    $(el, '#dec').onclick = () => {
      try { input.value = decodeURIComponent(escape(atob(input.value.trim()))); res.className = 'result show'; res.textContent = 'Decoded.'; }
      catch (e) { res.className = 'result show'; res.textContent = 'Not valid Base64.'; }
    };
    $(el, '#cp').onclick = copyBtn(() => input.value);
  });

  /* ══════════ 7. Word & Character Counter ══════════ */
  SHTools.register('word_counter', function (el) {
    el.innerHTML = `
      <div class="tool-controls">
        <textarea id="input" placeholder="Paste or type your text..."></textarea>
        <div class="result show" id="res"></div>
      </div>`;
    const input = $(el, '#input'), res = $(el, '#res');
    function stats() {
      const t = input.value;
      const words = (t.match(/\S+/g) || []).length;
      const chars = t.length, charsNoSpace = t.replace(/\s/g, '').length;
      const sentences = (t.match(/[.!?]+(?=\s|$)/g) || []).length;
      const paragraphs = t.split(/\n\s*\n/).filter(p => p.trim()).length || (t.trim() ? 1 : 0);
      const readMin = Math.max(1, Math.ceil(words / 220));
      res.innerHTML = `<div class="grid-stats" style="grid-template-columns:repeat(3,1fr);gap:10px">
        <div><b>${words}</b><br><span style="font-size:12px;color:var(--muted)">Words</span></div>
        <div><b>${chars}</b><br><span style="font-size:12px;color:var(--muted)">Characters</span></div>
        <div><b>${charsNoSpace}</b><br><span style="font-size:12px;color:var(--muted)">No spaces</span></div>
        <div><b>${sentences}</b><br><span style="font-size:12px;color:var(--muted)">Sentences</span></div>
        <div><b>${paragraphs}</b><br><span style="font-size:12px;color:var(--muted)">Paragraphs</span></div>
        <div><b>${readMin} min</b><br><span style="font-size:12px;color:var(--muted)">Reading time</span></div>
      </div>`;
    }
    input.oninput = stats; stats();
  });

  /* ══════════ 8. Text Case Converter ══════════ */
  SHTools.register('case_converter', function (el) {
    el.innerHTML = `
      <div class="tool-controls">
        <textarea id="input" placeholder="Type text..."></textarea>
        <div class="row">
          <button class="btn-ghost" data-c="upper">UPPERCASE</button>
          <button class="btn-ghost" data-c="lower">lowercase</button>
          <button class="btn-ghost" data-c="title">Title Case</button>
          <button class="btn-ghost" data-c="sentence">Sentence case</button>
          <button class="btn-ghost" data-c="camel">camelCase</button>
          <button class="btn-ghost" data-c="snake">snake_case</button>
          <button class="btn-ghost" data-c="kebab">kebab-case</button>
          <button class="btn-run" id="cp">Copy</button>
        </div>
      </div>`;
    const input = $(el, '#input');
    const words = s => s.trim().split(/\s+/).filter(Boolean);
    const conv = {
      upper: s => s.toUpperCase(),
      lower: s => s.toLowerCase(),
      title: s => words(s).map(w => w[0].toUpperCase() + w.slice(1).toLowerCase()).join(' '),
      sentence: s => s.toLowerCase().replace(/(^\s*\w|[.!?]\s*\w)/g, c => c.toUpperCase()),
      camel: s => words(s).map((w, i) => i === 0 ? w.toLowerCase() : w[0].toUpperCase() + w.slice(1).toLowerCase()).join(''),
      snake: s => words(s).map(w => w.toLowerCase()).join('_'),
      kebab: s => words(s).map(w => w.toLowerCase()).join('-'),
    };
    el.querySelectorAll('[data-c]').forEach(btn => btn.onclick = () => input.value = conv[btn.dataset.c](input.value));
    $(el, '#cp').onclick = copyBtn(() => input.value);
  });

  /* ══════════ 9. Lorem Ipsum Generator ══════════ */
  SHTools.register('lorem_ipsum', function (el) {
    const bank = 'lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua enim ad minim veniam quis nostrud exercitation ullamco laboris nisi aliquip ex ea commodo consequat duis aute irure in reprehenderit voluptate velit esse cillum eu fugiat nulla pariatur excepteur sint occaecat cupidatat non proident sunt culpa qui officia deserunt mollit anim id est laborum'.split(' ');
    el.innerHTML = `
      <div class="tool-controls">
        <label>Paragraphs: <span id="pv">3</span><input type="range" id="p" min="1" max="20" value="3"></label>
        <div class="row"><button class="btn-run" id="gen">Generate</button><button class="btn-ghost" id="cp">Copy</button></div>
        <textarea id="out" readonly></textarea>
      </div>`;
    const p = $(el, '#p'), pv = $(el, '#pv'), out = $(el, '#out');
    p.oninput = () => pv.textContent = p.value;
    function para() {
      const len = 40 + Math.floor(Math.random() * 40);
      let words = ['Lorem', 'ipsum'];
      for (let i = 0; i < len; i++) words.push(bank[Math.floor(Math.random() * bank.length)]);
      return words.join(' ').replace(/\.$/, '') + '.';
    }
    $(el, '#gen').onclick = () => out.value = Array.from({ length: Number(p.value) }, para).join('\n\n');
    $(el, '#cp').onclick = copyBtn(() => out.value);
    out.value = Array.from({ length: 3 }, para).join('\n\n');
  });

  /* ══════════ 10. Markdown to HTML ══════════ */
  function mdToHtml(md) {
    let h = md
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/^### (.*)$/gm, '<h3>$1</h3>')
      .replace(/^## (.*)$/gm, '<h2>$1</h2>')
      .replace(/^# (.*)$/gm, '<h1>$1</h1>')
      .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
      .replace(/\*(.+?)\*/g, '<em>$1</em>')
      .replace(/`(.+?)`/g, '<code>$1</code>')
      .replace(/\[(.+?)\]\((.+?)\)/g, '<a href="$2">$1</a>')
      .replace(/^\> (.*)$/gm, '<blockquote>$1</blockquote>')
      .replace(/^\s*-\s+(.*)$/gm, '<li>$1</li>');
    h = h.replace(/(<li>.*<\/li>\n?)+/g, m => '<ul>' + m + '</ul>');
    return h.split(/\n{2,}/).map(block => /^<(h\d|ul|blockquote)/.test(block.trim()) ? block : (block.trim() ? '<p>' + block.trim() + '</p>' : '')).join('\n');
  }
  SHTools.register('markdown_to_html', function (el) {
    el.innerHTML = `
      <div class="tool-controls">
        <textarea id="input" placeholder="# Heading&#10;&#10;Some **bold** and *italic* text with a [link](https://example.com).">## Example&#10;&#10;Some **bold** text, *italic* text, and a [link](https://example.com).&#10;&#10;- item one&#10;- item two</textarea>
        <div class="row"><button class="btn-run" id="render">Convert</button><button class="btn-ghost" id="cp">Copy HTML</button></div>
        <div class="result" id="preview"></div>
      </div>`;
    const input = $(el, '#input'), preview = $(el, '#preview');
    let lastHtml = '';
    $(el, '#render').onclick = () => { lastHtml = mdToHtml(input.value); preview.className = 'result show'; preview.innerHTML = lastHtml; };
    $(el, '#cp').onclick = copyBtn(() => lastHtml || mdToHtml(input.value));
  });

  /* ══════════ 11. CSV to JSON ══════════ */
  SHTools.register('csv_to_json', function (el) {
    el.innerHTML = `
      <div class="tool-controls">
        <textarea id="input" placeholder="name,age&#10;Alice,30&#10;Bob,25">name,age,city
Alice,30,Paris
Bob,25,Berlin</textarea>
        <div class="row"><button class="btn-run" id="conv">Convert</button><button class="btn-ghost" id="cp">Copy</button><button class="btn-ghost" id="dl">Download .json</button></div>
        <textarea id="out" readonly style="min-height:160px"></textarea>
      </div>`;
    const input = $(el, '#input'), out = $(el, '#out');
    function parseCsv(text) {
      const lines = text.trim().split(/\r?\n/).filter(l => l.length);
      const headers = lines[0].split(',').map(h => h.trim());
      return lines.slice(1).map(line => {
        const cells = line.split(',');
        const obj = {};
        headers.forEach((hdr, i) => obj[hdr] = (cells[i] || '').trim());
        return obj;
      });
    }
    $(el, '#conv').onclick = () => out.value = JSON.stringify(parseCsv(input.value), null, 2);
    $(el, '#cp').onclick = copyBtn(() => out.value);
    $(el, '#dl').onclick = () => download('data.json', new Blob([out.value || '[]'], { type: 'application/json' }));
  });

  /* ══════════ 12. Hash Generator ══════════ */
  function md5(str) {
    function rl(n, c) { return (n << c) | (n >>> (32 - c)); }
    function toWords(s) { const w = []; for (let i = 0; i < s.length * 8; i += 8) w[i >> 5] |= (s.charCodeAt(i / 8) & 0xff) << (i % 32); return w; }
    function toHex(bin) {
      let s = '';
      for (let i = 0; i < bin.length * 4; i++) s += ((bin[i >> 2] >> ((i % 4) * 8 + 4)) & 0xF).toString(16) + ((bin[i >> 2] >> ((i % 4) * 8)) & 0xF).toString(16);
      return s;
    }
    function cmn(q, a, b, x, s, t) { a = (((a + q) | 0) + ((x + t) | 0)) | 0; return (((rl(a, s) + b) | 0)); }
    function ff(a, b, c, d, x, s, t) { return cmn((b & c) | (~b & d), a, b, x, s, t); }
    function gg(a, b, c, d, x, s, t) { return cmn((b & d) | (c & ~d), a, b, x, s, t); }
    function hh(a, b, c, d, x, s, t) { return cmn(b ^ c ^ d, a, b, x, s, t); }
    function ii(a, b, c, d, x, s, t) { return cmn(c ^ (b | ~d), a, b, x, s, t); }

    str = unescape(encodeURIComponent(str));
    let x = toWords(str); const len = str.length * 8;
    x[len >> 5] |= 0x80 << (len % 32);
    x[(((len + 64) >>> 9) << 4) + 14] = len;
    let a = 1732584193, b = -271733879, c = -1732584194, d = 271733878;
    for (let i = 0; i < x.length; i += 16) {
      const oa = a, ob = b, oc = c, od = d;
      a = ff(a, b, c, d, x[i + 0] | 0, 7, -680876936); d = ff(d, a, b, c, x[i + 1] | 0, 12, -389564586); c = ff(c, d, a, b, x[i + 2] | 0, 17, 606105819); b = ff(b, c, d, a, x[i + 3] | 0, 22, -1044525330);
      a = ff(a, b, c, d, x[i + 4] | 0, 7, -176418897); d = ff(d, a, b, c, x[i + 5] | 0, 12, 1200080426); c = ff(c, d, a, b, x[i + 6] | 0, 17, -1473231341); b = ff(b, c, d, a, x[i + 7] | 0, 22, -45705983);
      a = ff(a, b, c, d, x[i + 8] | 0, 7, 1770035416); d = ff(d, a, b, c, x[i + 9] | 0, 12, -1958414417); c = ff(c, d, a, b, x[i + 10] | 0, 17, -42063); b = ff(b, c, d, a, x[i + 11] | 0, 22, -1990404162);
      a = ff(a, b, c, d, x[i + 12] | 0, 7, 1804603682); d = ff(d, a, b, c, x[i + 13] | 0, 12, -40341101); c = ff(c, d, a, b, x[i + 14] | 0, 17, -1502002290); b = ff(b, c, d, a, x[i + 15] | 0, 22, 1236535329);
      a = gg(a, b, c, d, x[i + 1] | 0, 5, -165796510); d = gg(d, a, b, c, x[i + 6] | 0, 9, -1069501632); c = gg(c, d, a, b, x[i + 11] | 0, 14, 643717713); b = gg(b, c, d, a, x[i + 0] | 0, 20, -373897302);
      a = gg(a, b, c, d, x[i + 5] | 0, 5, -701558691); d = gg(d, a, b, c, x[i + 10] | 0, 9, 38016083); c = gg(c, d, a, b, x[i + 15] | 0, 14, -660478335); b = gg(b, c, d, a, x[i + 4] | 0, 20, -405537848);
      a = gg(a, b, c, d, x[i + 9] | 0, 5, 568446438); d = gg(d, a, b, c, x[i + 14] | 0, 9, -1019803690); c = gg(c, d, a, b, x[i + 3] | 0, 14, -187363961); b = gg(b, c, d, a, x[i + 8] | 0, 20, 1163531501);
      a = gg(a, b, c, d, x[i + 13] | 0, 5, -1444681467); d = gg(d, a, b, c, x[i + 2] | 0, 9, -51403784); c = gg(c, d, a, b, x[i + 7] | 0, 14, 1735328473); b = gg(b, c, d, a, x[i + 12] | 0, 20, -1926607734);
      a = hh(a, b, c, d, x[i + 5] | 0, 4, -378558); d = hh(d, a, b, c, x[i + 8] | 0, 11, -2022574463); c = hh(c, d, a, b, x[i + 11] | 0, 16, 1839030562); b = hh(b, c, d, a, x[i + 14] | 0, 23, -35309556);
      a = hh(a, b, c, d, x[i + 1] | 0, 4, -1530992060); d = hh(d, a, b, c, x[i + 4] | 0, 11, 1272893353); c = hh(c, d, a, b, x[i + 7] | 0, 16, -155497632); b = hh(b, c, d, a, x[i + 10] | 0, 23, -1094730640);
      a = hh(a, b, c, d, x[i + 13] | 0, 4, 681279174); d = hh(d, a, b, c, x[i + 0] | 0, 11, -358537222); c = hh(c, d, a, b, x[i + 3] | 0, 16, -722521979); b = hh(b, c, d, a, x[i + 6] | 0, 23, 76029189);
      a = hh(a, b, c, d, x[i + 9] | 0, 4, -640364487); d = hh(d, a, b, c, x[i + 12] | 0, 11, -421815835); c = hh(c, d, a, b, x[i + 15] | 0, 16, 530742520); b = hh(b, c, d, a, x[i + 2] | 0, 23, -995338651);
      a = ii(a, b, c, d, x[i + 0] | 0, 6, -198630844); d = ii(d, a, b, c, x[i + 7] | 0, 10, 1126891415); c = ii(c, d, a, b, x[i + 14] | 0, 15, -1416354905); b = ii(b, c, d, a, x[i + 5] | 0, 21, -57434055);
      a = ii(a, b, c, d, x[i + 12] | 0, 6, 1700485571); d = ii(d, a, b, c, x[i + 3] | 0, 10, -1894986606); c = ii(c, d, a, b, x[i + 10] | 0, 15, -1051523); b = ii(b, c, d, a, x[i + 1] | 0, 21, -2054922799);
      a = ii(a, b, c, d, x[i + 8] | 0, 6, 1873313359); d = ii(d, a, b, c, x[i + 15] | 0, 10, -30611744); c = ii(c, d, a, b, x[i + 6] | 0, 15, -1560198380); b = ii(b, c, d, a, x[i + 13] | 0, 21, 1309151649);
      a = ii(a, b, c, d, x[i + 4] | 0, 6, -145523070); d = ii(d, a, b, c, x[i + 11] | 0, 10, -1120210379); c = ii(c, d, a, b, x[i + 2] | 0, 15, 718787259); b = ii(b, c, d, a, x[i + 9] | 0, 21, -343485551);
      a = (a + oa) | 0; b = (b + ob) | 0; c = (c + oc) | 0; d = (d + od) | 0;
    }
    return toHex([a, b, c, d]);
  }
  SHTools.register('hash_generator', function (el) {
    el.innerHTML = `
      <div class="tool-controls">
        <textarea id="input" placeholder="Text to hash..."></textarea>
        <button class="btn-run" id="run">Generate hashes</button>
        <div class="result" id="res"></div>
      </div>`;
    const input = $(el, '#input'), res = $(el, '#res');
    async function sha(alg, text) {
      const buf = await crypto.subtle.digest(alg, new TextEncoder().encode(text));
      return Array.from(new Uint8Array(buf)).map(b => b.toString(16).padStart(2, '0')).join('');
    }
    $(el, '#run').onclick = async () => {
      const t = input.value;
      const [sha1, sha256, sha512] = await Promise.all([sha('SHA-1', t), sha('SHA-256', t), sha('SHA-512', t)]);
      res.className = 'result show';
      res.innerHTML = ['MD5', 'SHA-1', 'SHA-256', 'SHA-512'].map((label, i) => {
        const val = [md5(t), sha1, sha256, sha512][i];
        return `<div style="margin-bottom:8px"><b>${label}</b><div style="font-family:'JetBrains Mono',monospace;font-size:12px;word-break:break-all">${val}</div></div>`;
      }).join('');
    };
  });

  /* ══════════ 13. URL Encoder / Decoder ══════════ */
  SHTools.register('url_encoder', function (el) {
    el.innerHTML = `
      <div class="tool-controls">
        <textarea id="input" placeholder="https://example.com/?q=hello world"></textarea>
        <div class="row"><button class="btn-run" id="enc">Encode</button><button class="btn-ghost" id="dec">Decode</button><button class="btn-ghost" id="cp">Copy</button></div>
      </div>`;
    const input = $(el, '#input');
    $(el, '#enc').onclick = () => input.value = encodeURIComponent(input.value);
    $(el, '#dec').onclick = () => { try { input.value = decodeURIComponent(input.value); } catch (e) { /* leave as-is */ } };
    $(el, '#cp').onclick = copyBtn(() => input.value);
  });

  /* ══════════ 14. Color Converter & Palette Generator ══════════ */
  SHTools.register('color_converter', function (el) {
    el.innerHTML = `
      <div class="tool-controls">
        <div class="row">
          <input type="color" id="picker" value="#6366f1" style="width:60px;height:44px;padding:2px">
          <input type="text" id="hex" value="#6366f1" style="max-width:140px">
          <input type="text" id="rgb" readonly style="max-width:200px">
          <input type="text" id="hsl" readonly style="max-width:200px">
        </div>
        <button class="btn-run" id="pal">Generate palette</button>
        <div class="tool-swatches" id="swatches"></div>
      </div>`;
    const picker = $(el, '#picker'), hex = $(el, '#hex'), rgb = $(el, '#rgb'), hsl = $(el, '#hsl'), swatches = $(el, '#swatches');
    function hexToRgb(h) { h = h.replace('#', ''); if (h.length === 3) h = h.split('').map(c => c + c).join(''); const n = parseInt(h, 16); return [n >> 16 & 255, n >> 8 & 255, n & 255]; }
    function rgbToHsl(r, g, b) {
      r /= 255; g /= 255; b /= 255;
      const max = Math.max(r, g, b), min = Math.min(r, g, b); let h, s, l = (max + min) / 2;
      if (max === min) { h = s = 0; } else {
        const d = max - min; s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        h = max === r ? (g - b) / d + (g < b ? 6 : 0) : max === g ? (b - r) / d + 2 : (r - g) / d + 4; h /= 6;
      }
      return [Math.round(h * 360), Math.round(s * 100), Math.round(l * 100)];
    }
    function update(hx) {
      hex.value = hx; picker.value = hx;
      const [r, g, b] = hexToRgb(hx); rgb.value = `rgb(${r}, ${g}, ${b})`;
      const [hh, ss, ll] = rgbToHsl(r, g, b); hsl.value = `hsl(${hh}, ${ss}%, ${ll}%)`;
    }
    picker.oninput = () => update(picker.value);
    hex.oninput = () => /^#?[0-9a-f]{3}([0-9a-f]{3})?$/i.test(hex.value) && update(hex.value.startsWith('#') ? hex.value : '#' + hex.value);
    $(el, '#pal').onclick = () => {
      const [r, g, b] = hexToRgb(hex.value); let [hh] = rgbToHsl(r, g, b);
      swatches.innerHTML = '';
      [0, 30, 60, 180, 210].forEach(offset => {
        const nh = (hh + offset) % 360;
        const c = `hsl(${nh}, 65%, 55%)`;
        const sw = h(`<div class="sw" style="background:${c}" title="${c}"></div>`);
        sw.onclick = () => navigator.clipboard.writeText(c);
        swatches.appendChild(sw);
      });
    };
    update('#6366f1');
  });

  /* ══════════ 15. Unit Converter ══════════ */
  SHTools.register('unit_converter', function (el) {
    const groups = {
      length: { m: 1, km: 1000, cm: 0.01, mm: 0.001, mile: 1609.344, yard: 0.9144, foot: 0.3048, inch: 0.0254 },
      weight: { kg: 1, g: 0.001, mg: 0.000001, lb: 0.45359237, oz: 0.0283495231 },
      volume: { liter: 1, ml: 0.001, gallon: 3.785411784, quart: 0.946352946, cup: 0.2365882365 },
    };
    el.innerHTML = `
      <div class="tool-controls">
        <label>Category<select id="cat"><option value="length">Length</option><option value="weight">Weight</option><option value="volume">Volume</option><option value="temperature">Temperature</option></select></label>
        <div class="row">
          <label style="flex:1">Value<input type="text" id="val" value="1"></label>
          <label style="flex:1">From<select id="from"></select></label>
          <label style="flex:1">To<select id="to"></select></label>
        </div>
        <div class="result show" id="res"></div>
      </div>`;
    const cat = $(el, '#cat'), val = $(el, '#val'), from = $(el, '#from'), to = $(el, '#to'), res = $(el, '#res');
    function fillUnits() {
      const list = cat.value === 'temperature' ? ['celsius', 'fahrenheit', 'kelvin'] : Object.keys(groups[cat.value]);
      from.innerHTML = to.innerHTML = list.map(u => `<option value="${u}">${u}</option>`).join('');
      to.selectedIndex = 1;
      convert();
    }
    function tempToC(v, u) { return u === 'celsius' ? v : u === 'fahrenheit' ? (v - 32) * 5 / 9 : v - 273.15; }
    function cToTemp(c, u) { return u === 'celsius' ? c : u === 'fahrenheit' ? c * 9 / 5 + 32 : c + 273.15; }
    function convert() {
      const v = parseFloat(val.value); if (isNaN(v)) { res.textContent = 'Enter a number.'; return; }
      let out;
      if (cat.value === 'temperature') out = cToTemp(tempToC(v, from.value), to.value);
      else out = v * groups[cat.value][from.value] / groups[cat.value][to.value];
      res.innerHTML = `<b>${v} ${from.value}</b> = <b>${(Math.round(out * 100000) / 100000)} ${to.value}</b>`;
    }
    [cat].forEach(x => x.onchange = fillUnits);
    [val, from, to].forEach(x => x.oninput = convert);
    fillUnits();
  });

  /* ══════════ 16. BMI Calculator ══════════ */
  SHTools.register('bmi_calculator', function (el) {
    el.innerHTML = `
      <div class="tool-controls">
        <div class="row2">
          <label>Height (cm)<input type="text" id="h" value="170"></label>
          <label>Weight (kg)<input type="text" id="w" value="70"></label>
        </div>
        <button class="btn-run" id="calc">Calculate BMI</button>
        <div class="result" id="res"></div>
      </div>`;
    const hI = $(el, '#h'), wI = $(el, '#w'), res = $(el, '#res');
    $(el, '#calc').onclick = () => {
      const hM = parseFloat(hI.value) / 100, w = parseFloat(wI.value);
      if (!hM || !w) { res.className = 'result show'; res.textContent = 'Enter valid height and weight.'; return; }
      const bmi = w / (hM * hM);
      const cat = bmi < 18.5 ? 'Underweight' : bmi < 25 ? 'Normal weight' : bmi < 30 ? 'Overweight' : 'Obese';
      const color = bmi < 18.5 ? '#f59e0b' : bmi < 25 ? '#10b981' : bmi < 30 ? '#f59e0b' : '#ef4444';
      res.className = 'result show';
      res.innerHTML = `<div style="font-size:28px;font-weight:800">${bmi.toFixed(1)}</div><div style="color:${color};font-weight:700">${cat}</div>`;
    };
  });

  /* ══════════ 17. Age Calculator ══════════ */
  SHTools.register('age_calculator', function (el) {
    el.innerHTML = `
      <div class="tool-controls">
        <label>Date of birth<input type="text" id="dob" placeholder="YYYY-MM-DD"></label>
        <button class="btn-run" id="calc">Calculate age</button>
        <div class="result" id="res"></div>
      </div>`;
    const dob = $(el, '#dob'), res = $(el, '#res');
    $(el, '#calc').onclick = () => {
      const d = new Date(dob.value); const now = new Date();
      if (isNaN(d)) { res.className = 'result show'; res.textContent = 'Enter a valid date (YYYY-MM-DD).'; return; }
      let years = now.getFullYear() - d.getFullYear(), months = now.getMonth() - d.getMonth(), days = now.getDate() - d.getDate();
      if (days < 0) { months--; days += new Date(now.getFullYear(), now.getMonth(), 0).getDate(); }
      if (months < 0) { years--; months += 12; }
      const totalDays = Math.floor((now - d) / 86400000);
      let next = new Date(now.getFullYear(), d.getMonth(), d.getDate());
      if (next < now) next.setFullYear(next.getFullYear() + 1);
      const untilBday = Math.ceil((next - now) / 86400000);
      res.className = 'result show';
      res.innerHTML = `<div style="font-size:24px;font-weight:800">${years} years, ${months} months, ${days} days</div><p>Total days lived: <b>${totalDays.toLocaleString()}</b> · Next birthday in <b>${untilBday}</b> days</p>`;
    };
  });

  /* ══════════ 18. Unix Timestamp Converter ══════════ */
  SHTools.register('timestamp_converter', function (el) {
    el.innerHTML = `
      <div class="tool-controls">
        <div class="row2">
          <label>Unix timestamp (seconds)<input type="text" id="ts"></label>
          <label>Human date (local)<input type="text" id="dt" placeholder="YYYY-MM-DD HH:MM:SS"></label>
        </div>
        <div class="row"><button class="btn-run" id="now">Use current time</button><button class="btn-ghost" id="toDate">Timestamp → Date</button><button class="btn-ghost" id="toTs">Date → Timestamp</button></div>
        <div class="result" id="res"></div>
      </div>`;
    const ts = $(el, '#ts'), dt = $(el, '#dt'), res = $(el, '#res');
    function show(text) { res.className = 'result show'; res.innerHTML = text; }
    $(el, '#now').onclick = () => { const n = Math.floor(Date.now() / 1000); ts.value = n; dt.value = new Date().toISOString().slice(0, 19).replace('T', ' '); show('Current Unix time: <b>' + n + '</b>'); };
    $(el, '#toDate').onclick = () => { const n = Number(ts.value); if (!n) return show('Enter a valid timestamp.'); const d = new Date(n * 1000); dt.value = d.toISOString().slice(0, 19).replace('T', ' '); show('<b>' + d.toString() + '</b>'); };
    $(el, '#toTs').onclick = () => { const d = new Date(dt.value.replace(' ', 'T')); if (isNaN(d)) return show('Enter a valid date.'); ts.value = Math.floor(d.getTime() / 1000); show('Unix timestamp: <b>' + ts.value + '</b>'); };
  });

  /* ══════════ 19. CSS Minifier ══════════ */
  SHTools.register('css_minifier', function (el) {
    el.innerHTML = `
      <div class="tool-controls">
        <textarea id="input" placeholder="body {\n  color: red;\n}"></textarea>
        <div class="row"><button class="btn-run" id="run">Minify</button><button class="btn-ghost" id="cp">Copy</button><button class="btn-ghost" id="dl">Download .css</button></div>
        <div class="result" id="res"></div>
      </div>`;
    const input = $(el, '#input'), res = $(el, '#res');
    let mini = '';
    $(el, '#run').onclick = () => {
      mini = input.value
        .replace(/\/\*[\s\S]*?\*\//g, '')
        .replace(/\s+/g, ' ')
        .replace(/\s*([{}:;,])\s*/g, '$1')
        .replace(/;}/g, '}')
        .trim();
      res.className = 'result show';
      res.innerHTML = `<div style="font-family:'JetBrains Mono',monospace;font-size:12px;word-break:break-all">${mini}</div><p>${fmtBytes(new Blob([input.value]).size)} → ${fmtBytes(new Blob([mini]).size)}</p>`;
    };
    $(el, '#cp').onclick = copyBtn(() => mini);
    $(el, '#dl').onclick = () => download('styles.min.css', new Blob([mini], { type: 'text/css' }));
  });

  /* ══════════ 20. Text to Speech ══════════ */
  SHTools.register('text_to_speech', function (el) {
    el.innerHTML = `
      <div class="tool-controls">
        <textarea id="input" placeholder="Type something to hear it read aloud...">Hello! This is a free text to speech tool.</textarea>
        <div class="row">
          <label style="flex:2">Voice<select id="voice"></select></label>
          <label style="flex:1">Rate: <span id="rv">1</span><input type="range" id="rate" min="0.5" max="2" step="0.1" value="1"></label>
          <label style="flex:1">Pitch: <span id="pv">1</span><input type="range" id="pitch" min="0" max="2" step="0.1" value="1"></label>
        </div>
        <div class="row"><button class="btn-run" id="speak">Speak</button><button class="btn-ghost" id="stop">Stop</button></div>
        <p class="hint" id="warn" style="display:none;color:#dc2626">Your browser doesn't support speech synthesis.</p>
      </div>`;
    const input = $(el, '#input'), voiceSel = $(el, '#voice'), rate = $(el, '#rate'), rv = $(el, '#rv'), pitch = $(el, '#pitch'), pv = $(el, '#pv');
    rate.oninput = () => rv.textContent = rate.value;
    pitch.oninput = () => pv.textContent = pitch.value;
    if (!('speechSynthesis' in window)) { $(el, '#warn').style.display = 'block'; $(el, '#speak').disabled = true; return; }
    function loadVoices() {
      const voices = speechSynthesis.getVoices();
      voiceSel.innerHTML = voices.map((v, i) => `<option value="${i}">${v.name} (${v.lang})</option>`).join('');
    }
    loadVoices();
    speechSynthesis.onvoiceschanged = loadVoices;
    $(el, '#speak').onclick = () => {
      speechSynthesis.cancel();
      const u = new SpeechSynthesisUtterance(input.value);
      const voices = speechSynthesis.getVoices();
      if (voices[voiceSel.value]) u.voice = voices[voiceSel.value];
      u.rate = Number(rate.value); u.pitch = Number(pitch.value);
      speechSynthesis.speak(u);
    };
    $(el, '#stop').onclick = () => speechSynthesis.cancel();
  });

})();
