/* ═══════════════════════════════════════════════
   Syria Home — "Pro" tool set.

   Twenty tools whose core function is normally sold as a paid product or
   subscription. Every one is an independent implementation written against
   plain browser APIs — nothing here is copied from, bundled with, or
   circumvents any commercial product. All processing is local: no file
   ever leaves the visitor's device.

   Registers into the same SHTools registry defined by tools.js, so
   tools.js must load first.
   ═══════════════════════════════════════════════ */
(function () {
  'use strict';
  if (!window.SHTools) { console.warn('tools-pro.js: load tools.js first'); return; }
  var T = window.SHTools;

  /* ── shared helpers ── */
  function $(root, sel) { return root.querySelector(sel); }
  function $$(root, sel) { return Array.prototype.slice.call(root.querySelectorAll(sel)); }
  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function fmtBytes(n) { if (n < 1024) return n + ' B'; if (n < 1048576) return (n / 1024).toFixed(1) + ' KB'; return (n / 1048576).toFixed(2) + ' MB'; }
  function download(name, blob) {
    var a = document.createElement('a');
    a.href = typeof blob === 'string' ? blob : URL.createObjectURL(blob);
    a.download = name;
    document.body.appendChild(a); a.click();
    setTimeout(function () { a.remove(); }, 100);
  }
  function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(function () {
      var old = btn.textContent; btn.textContent = 'Copied!';
      setTimeout(function () { btn.textContent = old; }, 1200);
    });
  }
  /** Reads a File into an <img>, resolving with the loaded element. */
  function readImage(file) {
    return new Promise(function (res, rej) {
      var img = new Image();
      img.onload = function () { res(img); };
      img.onerror = rej;
      img.src = URL.createObjectURL(file);
    });
  }

  /* ══════════ 1. Background Remover ══════════
     Flood-fills from the image edges, removing every pixel within a colour
     tolerance of the border colour. Works cleanly on product shots and
     logos on flat backgrounds — the same job people pay per-image for. */
  T.register('bg_remover', function (el) {
    el.innerHTML =
      '<div class="tool-controls">' +
      '<div class="drop" id="d">Click or drop an image with a plain background</div>' +
      '<input type="file" id="f" accept="image/*" style="display:none">' +
      '<div class="row"><label style="flex:1;min-width:180px">Tolerance: <span id="tv">32</span>' +
      '<input type="range" id="tol" min="2" max="120" value="32"></label>' +
      '<label style="flex:1;min-width:180px">Edge softness: <span id="fv">1</span>' +
      '<input type="range" id="feather" min="0" max="4" value="1"></label></div>' +
      '<div class="result" id="res"></div></div>';
    var d = $(el, '#d'), f = $(el, '#f'), tol = $(el, '#tol'), tv = $(el, '#tv'),
      feather = $(el, '#feather'), fv = $(el, '#fv'), res = $(el, '#res'), current = null;
    d.onclick = function () { f.click(); };
    d.ondragover = function (e) { e.preventDefault(); };
    d.ondrop = function (e) { e.preventDefault(); if (e.dataTransfer.files[0]) load(e.dataTransfer.files[0]); };
    f.onchange = function () { if (f.files[0]) load(f.files[0]); };
    tol.oninput = function () { tv.textContent = tol.value; if (current) run(); };
    feather.oninput = function () { fv.textContent = feather.value; if (current) run(); };

    function load(file) { readImage(file).then(function (img) { current = img; run(); }); }

    function run() {
      var img = current, max = 1400;
      var scale = Math.min(1, max / Math.max(img.width, img.height));
      var w = Math.round(img.width * scale), h = Math.round(img.height * scale);
      var c = document.createElement('canvas'); c.width = w; c.height = h;
      var ctx = c.getContext('2d');
      ctx.drawImage(img, 0, 0, w, h);
      var data = ctx.getImageData(0, 0, w, h), px = data.data;
      var t = +tol.value, t2 = t * t * 3;

      // Seed from every border pixel, then flood-fill inward.
      var seen = new Uint8Array(w * h), stack = [];
      function seed(x, y) { var i = y * w + x; if (!seen[i]) { seen[i] = 1; stack.push(i); } }
      for (var x = 0; x < w; x++) { seed(x, 0); seed(x, h - 1); }
      for (var y = 0; y < h; y++) { seed(0, y); seed(w - 1, y); }

      // Reference colour = average of the four corners.
      var ref = [0, 0, 0], corners = [0, (w - 1) * 4, (h - 1) * w * 4, ((h - 1) * w + w - 1) * 4];
      corners.forEach(function (o) { ref[0] += px[o]; ref[1] += px[o + 1]; ref[2] += px[o + 2]; });
      ref = ref.map(function (v) { return v / 4; });

      var out = new Uint8Array(w * h);
      while (stack.length) {
        var i = stack.pop(), o = i * 4;
        var dr = px[o] - ref[0], dg = px[o + 1] - ref[1], db = px[o + 2] - ref[2];
        if (dr * dr + dg * dg + db * db > t2) continue;
        out[i] = 1;
        var cx = i % w, cy = (i / w) | 0;
        if (cx > 0) seed(cx - 1, cy);
        if (cx < w - 1) seed(cx + 1, cy);
        if (cy > 0) seed(cx, cy - 1);
        if (cy < h - 1) seed(cx, cy + 1);
      }

      // Feather the cut so edges don't alias.
      var fr = +feather.value;
      for (var i2 = 0; i2 < w * h; i2++) {
        if (!out[i2]) continue;
        px[i2 * 4 + 3] = 0;
      }
      if (fr > 0) {
        for (var pass = 0; pass < fr; pass++) {
          for (var yy = 1; yy < h - 1; yy++) {
            for (var xx = 1; xx < w - 1; xx++) {
              var idx = yy * w + xx;
              if (px[idx * 4 + 3] === 0) continue;
              var n = 0;
              if (px[(idx - 1) * 4 + 3] === 0) n++;
              if (px[(idx + 1) * 4 + 3] === 0) n++;
              if (px[(idx - w) * 4 + 3] === 0) n++;
              if (px[(idx + w) * 4 + 3] === 0) n++;
              if (n >= 2) px[idx * 4 + 3] = 90;
              else if (n === 1) px[idx * 4 + 3] = 175;
            }
          }
        }
      }
      ctx.putImageData(data, 0, 0);

      var kept = 0;
      for (var k = 0; k < w * h; k++) if (!out[k]) kept++;
      res.innerHTML = '<p style="margin-bottom:10px">Removed <b>' + Math.round((1 - kept / (w * h)) * 100) +
        '%</b> of the image as background. Lower the tolerance if too much vanished, raise it if edges remain.</p>' +
        '<div style="background:repeating-conic-gradient(#e5e7eb 0 25%,#fff 0 50%) 50%/18px 18px;border-radius:10px;padding:10px;display:inline-block"></div>';
      var holder = res.querySelector('div');
      c.style.maxWidth = '100%'; c.style.display = 'block'; c.style.borderRadius = '6px';
      holder.appendChild(c);
      var btn = document.createElement('button');
      btn.className = 'btn'; btn.style.marginTop = '12px';
      btn.textContent = 'Download transparent PNG';
      btn.onclick = function () { c.toBlob(function (b) { download('no-background.png', b); }, 'image/png'); };
      res.appendChild(btn);
    }
  });

  /* ══════════ 2. Bulk Image Resizer ══════════ */
  T.register('image_resizer', function (el) {
    el.innerHTML =
      '<div class="tool-controls">' +
      '<div class="drop" id="d">Click or drop images (multiple allowed)</div>' +
      '<input type="file" id="f" accept="image/*" multiple style="display:none">' +
      '<div class="row">' +
      '<label style="flex:1;min-width:140px">Width (px)<input type="number" id="w" placeholder="auto"></label>' +
      '<label style="flex:1;min-width:140px">Height (px)<input type="number" id="h" placeholder="auto"></label>' +
      '<label style="flex:1;min-width:160px">Preset<select id="preset">' +
      '<option value="">— custom —</option>' +
      '<option value="1920x1080">1920×1080 · Full HD</option>' +
      '<option value="1200x630">1200×630 · Social share</option>' +
      '<option value="1080x1080">1080×1080 · Square post</option>' +
      '<option value="1080x1920">1080×1920 · Story</option>' +
      '<option value="800x800">800×800 · Product</option>' +
      '<option value="400x400">400×400 · Avatar</option>' +
      '</select></label></div>' +
      '<div class="row"><label style="flex:1;min-width:160px">Format<select id="fmt">' +
      '<option value="image/webp">WebP (smallest)</option><option value="image/jpeg">JPEG</option><option value="image/png">PNG</option>' +
      '</select></label>' +
      '<label style="flex:1;min-width:180px">Quality: <span id="qv">85</span>%<input type="range" id="q" min="30" max="100" value="85"></label>' +
      '<label style="flex:1;min-width:150px;display:flex;align-items:center;gap:7px;margin-top:22px">' +
      '<input type="checkbox" id="keep" checked style="width:auto"> Keep aspect ratio</label></div>' +
      '<div class="result" id="res"></div></div>';
    var f = $(el, '#f'), res = $(el, '#res'), q = $(el, '#q'), qv = $(el, '#qv'), preset = $(el, '#preset');
    $(el, '#d').onclick = function () { f.click(); };
    q.oninput = function () { qv.textContent = q.value; };
    preset.onchange = function () {
      if (!preset.value) return;
      var p = preset.value.split('x');
      $(el, '#w').value = p[0]; $(el, '#h').value = p[1];
    };
    f.onchange = function () {
      res.innerHTML = '';
      Array.prototype.forEach.call(f.files, function (file) { one(file); });
    };
    function one(file) {
      readImage(file).then(function (img) {
        var tw = parseInt($(el, '#w').value, 10) || 0, th = parseInt($(el, '#h').value, 10) || 0;
        if (!tw && !th) { tw = img.width; th = img.height; }
        if ($(el, '#keep').checked) {
          if (tw && !th) th = Math.round(img.height * (tw / img.width));
          else if (th && !tw) tw = Math.round(img.width * (th / img.height));
          else { var s = Math.min(tw / img.width, th / img.height); tw = Math.round(img.width * s); th = Math.round(img.height * s); }
        } else { tw = tw || img.width; th = th || img.height; }
        var c = document.createElement('canvas'); c.width = tw; c.height = th;
        var ctx = c.getContext('2d');
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(img, 0, 0, tw, th);
        var fmt = $(el, '#fmt').value;
        c.toBlob(function (b) {
          var row = document.createElement('div');
          row.style.cssText = 'display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #eef1f8;flex-wrap:wrap';
          row.innerHTML = '<img src="' + URL.createObjectURL(b) + '" style="width:56px;height:56px;object-fit:cover;border-radius:8px">' +
            '<div style="flex:1;min-width:160px"><b>' + esc(file.name) + '</b><br>' +
            '<span style="font-size:12.5px;color:#64748b">' + img.width + '×' + img.height + ' · ' + fmtBytes(file.size) +
            ' → ' + tw + '×' + th + ' · ' + fmtBytes(b.size) +
            ' <b style="color:#16a34a">(−' + Math.max(0, Math.round((1 - b.size / file.size) * 100)) + '%)</b></span></div>';
          var btn = document.createElement('button');
          btn.className = 'btn sm'; btn.textContent = 'Download';
          btn.onclick = function () { download(file.name.replace(/\.\w+$/, '') + '-' + tw + 'x' + th + '.' + fmt.split('/')[1], b); };
          row.appendChild(btn);
          res.appendChild(row);
        }, fmt, +q.value / 100);
      });
    }
  });

  /* ══════════ 3. Watermark ══════════ */
  T.register('watermark', function (el) {
    el.innerHTML =
      '<div class="tool-controls">' +
      '<div class="drop" id="d">Click or drop the image to watermark</div>' +
      '<input type="file" id="f" accept="image/*" style="display:none">' +
      '<label>Watermark text<input type="text" id="txt" value="© Your Brand"></label>' +
      '<div class="row">' +
      '<label style="flex:1;min-width:150px">Position<select id="pos">' +
      '<option value="br">Bottom right</option><option value="bl">Bottom left</option>' +
      '<option value="tr">Top right</option><option value="tl">Top left</option>' +
      '<option value="c">Centre</option><option value="tile">Tiled across</option></select></label>' +
      '<label style="flex:1;min-width:150px">Size: <span id="sv">4</span>%<input type="range" id="size" min="2" max="14" value="4"></label>' +
      '<label style="flex:1;min-width:150px">Opacity: <span id="ov">45</span>%<input type="range" id="op" min="5" max="100" value="45"></label>' +
      '<label style="flex:1;min-width:110px">Colour<input type="color" id="col" value="#ffffff"></label></div>' +
      '<div class="result" id="res"></div></div>';
    var f = $(el, '#f'), res = $(el, '#res'), img = null;
    $(el, '#d').onclick = function () { f.click(); };
    f.onchange = function () { if (f.files[0]) readImage(f.files[0]).then(function (i) { img = i; run(); }); };
    ['#txt', '#pos', '#size', '#op', '#col'].forEach(function (s) {
      $(el, s).oninput = function () {
        $(el, '#sv').textContent = $(el, '#size').value;
        $(el, '#ov').textContent = $(el, '#op').value;
        if (img) run();
      };
    });
    function run() {
      var c = document.createElement('canvas');
      c.width = img.width; c.height = img.height;
      var ctx = c.getContext('2d');
      ctx.drawImage(img, 0, 0);
      var fs = Math.max(12, Math.round(Math.min(c.width, c.height) * (+$(el, '#size').value / 100)));
      ctx.font = '600 ' + fs + 'px Inter, Arial, sans-serif';
      ctx.fillStyle = $(el, '#col').value;
      ctx.globalAlpha = +$(el, '#op').value / 100;
      var text = $(el, '#txt').value, m = ctx.measureText(text), pad = fs * 0.8, pos = $(el, '#pos').value;
      if (pos === 'tile') {
        ctx.save();
        ctx.rotate(-Math.PI / 9);
        for (var y = -c.height; y < c.height * 2; y += fs * 4) {
          for (var x = -c.width; x < c.width * 2; x += m.width + fs * 3) ctx.fillText(text, x, y);
        }
        ctx.restore();
      } else {
        var xy = {
          br: [c.width - m.width - pad, c.height - pad],
          bl: [pad, c.height - pad],
          tr: [c.width - m.width - pad, fs + pad],
          tl: [pad, fs + pad],
          c: [(c.width - m.width) / 2, c.height / 2]
        }[pos];
        ctx.fillText(text, xy[0], xy[1]);
      }
      ctx.globalAlpha = 1;
      res.innerHTML = '';
      c.style.maxWidth = '100%'; c.style.borderRadius = '10px'; c.style.display = 'block';
      res.appendChild(c);
      var btn = document.createElement('button');
      btn.className = 'btn'; btn.style.marginTop = '12px'; btn.textContent = 'Download watermarked image';
      btn.onclick = function () { c.toBlob(function (b) { download('watermarked.png', b); }, 'image/png'); };
      res.appendChild(btn);
    }
  });

  /* ══════════ 4. EXIF Viewer & Stripper ══════════
     Parses the APP1/TIFF block directly — reveals the GPS coordinates and
     camera serial numbers that photos silently carry. */
  T.register('exif_viewer', function (el) {
    el.innerHTML =
      '<div class="tool-controls"><div class="drop" id="d">Click or drop a JPEG to inspect its hidden metadata</div>' +
      '<input type="file" id="f" accept="image/jpeg,image/tiff" style="display:none">' +
      '<div class="result" id="res"></div></div>';
    var f = $(el, '#f'), res = $(el, '#res');
    $(el, '#d').onclick = function () { f.click(); };
    f.onchange = function () { if (f.files[0]) go(f.files[0]); };

    var TAGS = {
      0x010F: 'Camera make', 0x0110: 'Camera model', 0x0112: 'Orientation',
      0x0132: 'Date/time', 0x013B: 'Artist', 0x8298: 'Copyright',
      0x829A: 'Exposure time', 0x829D: 'F-number', 0x8827: 'ISO',
      0x9003: 'Date taken', 0x920A: 'Focal length', 0xA002: 'Width', 0xA003: 'Height',
      0xA434: 'Lens model', 0xA431: 'Body serial number'
    };
    var GPS = { 1: 'GPS lat ref', 2: 'GPS latitude', 3: 'GPS lon ref', 4: 'GPS longitude', 6: 'GPS altitude' };

    function go(file) {
      var fr = new FileReader();
      fr.onload = function () {
        var buf = fr.result, dv = new DataView(buf), found = {};
        try { parse(dv, found); } catch (e) { /* malformed EXIF — show what we got */ }
        render(file, found, buf);
      };
      fr.readAsArrayBuffer(file);
    }

    function parse(dv, out) {
      if (dv.getUint16(0) !== 0xFFD8) return;
      var off = 2;
      while (off < dv.byteLength) {
        if (dv.getUint16(off) !== 0xFFE1) {
          var len = dv.getUint16(off + 2); off += 2 + len;
          if (dv.getUint16(off) === 0xFFDA) return;
          continue;
        }
        var tiff = off + 10;
        var le = dv.getUint16(tiff) === 0x4949;
        var ifd0 = tiff + dv.getUint32(tiff + 4, le);
        readIFD(dv, tiff, ifd0, le, out, TAGS);
        return;
      }
    }

    function readIFD(dv, tiff, dir, le, out, map) {
      var n = dv.getUint16(dir, le);
      for (var i = 0; i < n; i++) {
        var e = dir + 2 + i * 12,
          tag = dv.getUint16(e, le), type = dv.getUint16(e + 2, le), count = dv.getUint32(e + 4, le);
        if (tag === 0x8769) { readIFD(dv, tiff, tiff + dv.getUint32(e + 8, le), le, out, TAGS); continue; }
        if (tag === 0x8825) { readIFD(dv, tiff, tiff + dv.getUint32(e + 8, le), le, out, GPS); continue; }
        var name = map[tag]; if (!name) continue;
        out[name] = value(dv, tiff, e, type, count, le);
      }
    }

    function value(dv, tiff, e, type, count, le) {
      var size = { 1: 1, 2: 1, 3: 2, 4: 4, 5: 8, 7: 1, 9: 4, 10: 8 }[type] || 1;
      var total = size * count;
      var off = total > 4 ? tiff + dv.getUint32(e + 8, le) : e + 8;
      if (type === 2) {
        var s = '';
        for (var i = 0; i < count - 1; i++) s += String.fromCharCode(dv.getUint8(off + i));
        return s.trim();
      }
      var vals = [];
      for (var j = 0; j < Math.min(count, 3); j++) {
        var o = off + j * size;
        if (type === 3) vals.push(dv.getUint16(o, le));
        else if (type === 4) vals.push(dv.getUint32(o, le));
        else if (type === 5 || type === 10) {
          var num = dv.getUint32(o, le), den = dv.getUint32(o + 4, le);
          vals.push(den ? +(num / den).toFixed(4) : 0);
        } else vals.push(dv.getUint8(o));
      }
      return vals.join(', ');
    }

    function render(file, found, buf) {
      var keys = Object.keys(found);
      var hasGps = keys.some(function (k) { return k.indexOf('GPS') === 0; });
      var html = '<h4 style="margin:0 0 10px">' + esc(file.name) + ' · ' + fmtBytes(file.size) + '</h4>';
      if (!keys.length) {
        html += '<p style="color:#16a34a;font-weight:600">No EXIF metadata found — this file is already clean.</p>';
      } else {
        if (hasGps) html += '<p style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:11px 14px;border-radius:9px;font-weight:600">' +
          '⚠ This photo contains GPS coordinates. Anyone you send it to can read exactly where it was taken.</p>';
        html += '<table style="width:100%;border-collapse:collapse;font-size:13.5px;margin-top:10px">';
        keys.forEach(function (k) {
          html += '<tr><td style="padding:7px 8px;border-bottom:1px solid #eef1f8;color:#64748b;width:180px">' + esc(k) +
            '</td><td style="padding:7px 8px;border-bottom:1px solid #eef1f8"><b>' + esc(found[k]) + '</b></td></tr>';
        });
        html += '</table>';
      }
      res.innerHTML = html;

      var btn = document.createElement('button');
      btn.className = 'btn'; btn.style.marginTop = '14px';
      btn.textContent = 'Download a copy with all metadata removed';
      btn.onclick = function () {
        // Re-encoding through a canvas drops every metadata segment.
        readImage(file).then(function (img) {
          var c = document.createElement('canvas');
          c.width = img.naturalWidth; c.height = img.naturalHeight;
          c.getContext('2d').drawImage(img, 0, 0);
          c.toBlob(function (b) { download(file.name.replace(/\.\w+$/, '') + '-clean.jpg', b); }, 'image/jpeg', 0.95);
        });
      };
      res.appendChild(btn);
    }
  });

  /* ══════════ 5. Favicon Generator ══════════ */
  T.register('favicon_generator', function (el) {
    var SIZES = [16, 32, 48, 64, 96, 128, 180, 192, 256, 512];
    el.innerHTML =
      '<div class="tool-controls"><div class="drop" id="d">Click or drop a square logo (512×512 or larger works best)</div>' +
      '<input type="file" id="f" accept="image/*" style="display:none">' +
      '<div class="row"><label style="flex:1;min-width:150px">Background<select id="bg">' +
      '<option value="">Transparent</option><option value="#ffffff">White</option><option value="custom">Custom…</option></select></label>' +
      '<label style="flex:1;min-width:120px"><input type="color" id="bgc" value="#ffffff"></label>' +
      '<label style="flex:1;min-width:150px">Corner radius: <span id="rv">0</span>%<input type="range" id="r" min="0" max="50" value="0"></label></div>' +
      '<div class="result" id="res"></div></div>';
    var f = $(el, '#f'), res = $(el, '#res'), img = null;
    $(el, '#d').onclick = function () { f.click(); };
    f.onchange = function () { if (f.files[0]) readImage(f.files[0]).then(function (i) { img = i; run(); }); };
    ['#bg', '#bgc', '#r'].forEach(function (s) {
      $(el, s).oninput = function () { $(el, '#rv').textContent = $(el, '#r').value; if (img) run(); };
    });

    function make(size) {
      var c = document.createElement('canvas'); c.width = c.height = size;
      var ctx = c.getContext('2d');
      var bg = $(el, '#bg').value;
      if (bg) { ctx.fillStyle = bg === 'custom' ? $(el, '#bgc').value : bg; ctx.fillRect(0, 0, size, size); }
      var rad = size * (+$(el, '#r').value / 100);
      if (rad > 0) {
        ctx.globalCompositeOperation = 'destination-in';
        ctx.beginPath();
        if (ctx.roundRect) ctx.roundRect(0, 0, size, size, rad); else ctx.rect(0, 0, size, size);
        ctx.fill();
        ctx.globalCompositeOperation = 'source-over';
      }
      ctx.drawImage(img, 0, 0, size, size);
      return c;
    }

    function run() {
      res.innerHTML = '<p style="margin-bottom:12px">Ten sizes generated. Download the ones your project needs, then paste the HTML below into your &lt;head&gt;.</p>';
      var grid = document.createElement('div');
      grid.style.cssText = 'display:flex;flex-wrap:wrap;gap:14px;align-items:flex-end;margin-bottom:18px';
      SIZES.forEach(function (s) {
        var c = make(s), box = document.createElement('div');
        box.style.cssText = 'text-align:center';
        c.style.cssText = 'max-width:72px;image-rendering:auto;border:1px solid #e7eaf3;border-radius:6px;display:block;margin:0 auto 6px';
        box.appendChild(c);
        var b = document.createElement('button');
        b.className = 'btn sm'; b.textContent = s + '×' + s;
        b.onclick = function () { c.toBlob(function (bl) { download('favicon-' + s + 'x' + s + '.png', bl); }, 'image/png'); };
        box.appendChild(b);
        grid.appendChild(box);
      });
      res.appendChild(grid);
      var code = '<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">\n' +
        '<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">\n' +
        '<link rel="apple-touch-icon" sizes="180x180" href="/favicon-180x180.png">\n' +
        '<link rel="manifest" href="/site.webmanifest">';
      var pre = document.createElement('pre');
      pre.style.cssText = 'background:#0f172a;color:#e2e8f0;padding:16px;border-radius:10px;overflow-x:auto;font-size:12.5px';
      pre.textContent = code;
      res.appendChild(pre);
      var cb = document.createElement('button');
      cb.className = 'btn sm'; cb.textContent = 'Copy HTML';
      cb.onclick = function () { copyText(code, cb); };
      res.appendChild(cb);
    }
  });

  /* ══════════ 6. Regex Tester ══════════ */
  T.register('regex_tester', function (el) {
    el.innerHTML =
      '<div class="tool-controls">' +
      '<div class="row"><label style="flex:3;min-width:220px">Pattern' +
      '<input type="text" id="pat" value="\\b(\\w+)@(\\w+)\\.com\\b" style="font-family:\'JetBrains Mono\',monospace"></label>' +
      '<label style="flex:1;min-width:110px">Flags<input type="text" id="flags" value="gi" style="font-family:\'JetBrains Mono\',monospace"></label></div>' +
      '<label>Test string<textarea id="txt" style="min-height:150px;font-family:\'JetBrains Mono\',monospace">' +
      'Contact sales@example.com or support@example.com.\nInvalid: foo@bar.org, plain text, 12345.</textarea></label>' +
      '<div class="result" id="res"></div></div>';
    var pat = $(el, '#pat'), flags = $(el, '#flags'), txt = $(el, '#txt'), res = $(el, '#res');
    [pat, flags, txt].forEach(function (i) { i.oninput = run; });
    run();

    function run() {
      var re;
      try { re = new RegExp(pat.value, flags.value); }
      catch (e) {
        res.innerHTML = '<p style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:11px 14px;border-radius:9px">' +
          '<b>Invalid pattern:</b> ' + esc(e.message) + '</p>';
        return;
      }
      var s = txt.value, matches = [], m, guard = 0;
      if (re.global) {
        re.lastIndex = 0;
        while ((m = re.exec(s)) !== null && guard++ < 5000) {
          matches.push(m);
          if (m.index === re.lastIndex) re.lastIndex++;
        }
      } else { m = re.exec(s); if (m) matches.push(m); }

      // Highlight matches without letting the source text inject markup.
      var html = '', last = 0;
      matches.forEach(function (mm) {
        html += esc(s.slice(last, mm.index));
        html += '<mark style="background:#fde68a;border-radius:3px;padding:1px 2px">' + esc(mm[0]) + '</mark>';
        last = mm.index + (mm[0].length || 1);
      });
      html += esc(s.slice(last));

      var out = '<p style="font-weight:700;margin-bottom:10px">' + matches.length + ' match' + (matches.length === 1 ? '' : 'es') + '</p>' +
        '<div style="background:#f8fafc;border:1px solid #e7eaf3;border-radius:10px;padding:14px;white-space:pre-wrap;' +
        'font-family:\'JetBrains Mono\',monospace;font-size:13px;line-height:1.7">' + html + '</div>';

      if (matches.length) {
        out += '<table style="width:100%;border-collapse:collapse;font-size:13px;margin-top:14px">' +
          '<tr><th style="text-align:left;padding:7px;border-bottom:1px solid #e7eaf3">#</th>' +
          '<th style="text-align:left;padding:7px;border-bottom:1px solid #e7eaf3">Match</th>' +
          '<th style="text-align:left;padding:7px;border-bottom:1px solid #e7eaf3">At</th>' +
          '<th style="text-align:left;padding:7px;border-bottom:1px solid #e7eaf3">Groups</th></tr>';
        matches.slice(0, 50).forEach(function (mm, i) {
          var groups = mm.slice(1).map(function (g, gi) { return (gi + 1) + ': ' + (g === undefined ? '—' : g); }).join('  ·  ');
          out += '<tr><td style="padding:7px;border-bottom:1px solid #eef1f8">' + (i + 1) + '</td>' +
            '<td style="padding:7px;border-bottom:1px solid #eef1f8"><code>' + esc(mm[0]) + '</code></td>' +
            '<td style="padding:7px;border-bottom:1px solid #eef1f8">' + mm.index + '</td>' +
            '<td style="padding:7px;border-bottom:1px solid #eef1f8;color:#64748b">' + esc(groups || '—') + '</td></tr>';
        });
        out += '</table>';
      }
      res.innerHTML = out;
    }
  });

  /* ══════════ 7. Diff Checker ══════════
     Proper LCS line diff — the same algorithm the paid diff sites charge
     a monthly fee for. */
  T.register('diff_checker', function (el) {
    el.innerHTML =
      '<div class="tool-controls">' +
      '<div class="row"><label style="flex:1;min-width:240px">Original' +
      '<textarea id="a" style="min-height:170px;font-family:\'JetBrains Mono\',monospace;font-size:13px">The quick brown fox\njumps over the lazy dog\nline three\nline four</textarea></label>' +
      '<label style="flex:1;min-width:240px">Changed' +
      '<textarea id="b" style="min-height:170px;font-family:\'JetBrains Mono\',monospace;font-size:13px">The quick brown fox\nleaps over the lazy dog\nline three\nline four\nline five</textarea></label></div>' +
      '<div class="result" id="res"></div></div>';
    var a = $(el, '#a'), b = $(el, '#b'), res = $(el, '#res');
    a.oninput = b.oninput = run; run();

    function lcs(x, y) {
      var n = x.length, m = y.length;
      // Row-by-row DP keeps memory at O(min(n,m)) for large files.
      var dp = [];
      for (var i = 0; i <= n; i++) dp.push(new Uint32Array(m + 1));
      for (var i2 = n - 1; i2 >= 0; i2--) {
        for (var j = m - 1; j >= 0; j--) {
          dp[i2][j] = x[i2] === y[j] ? dp[i2 + 1][j + 1] + 1 : Math.max(dp[i2 + 1][j], dp[i2][j + 1]);
        }
      }
      var out = [], i3 = 0, j3 = 0;
      while (i3 < n && j3 < m) {
        if (x[i3] === y[j3]) { out.push(['=', x[i3]]); i3++; j3++; }
        else if (dp[i3 + 1][j3] >= dp[i3][j3 + 1]) { out.push(['-', x[i3]]); i3++; }
        else { out.push(['+', y[j3]]); j3++; }
      }
      while (i3 < n) out.push(['-', x[i3++]]);
      while (j3 < m) out.push(['+', y[j3++]]);
      return out;
    }

    function run() {
      var x = a.value.split('\n'), y = b.value.split('\n');
      if (x.length * y.length > 4000000) { res.innerHTML = '<p>Inputs too large to diff in the browser.</p>'; return; }
      var d = lcs(x, y), added = 0, removed = 0, html = '';
      d.forEach(function (row) {
        var style = 'padding:3px 12px;font-family:\'JetBrains Mono\',monospace;font-size:13px;white-space:pre-wrap;';
        if (row[0] === '+') { added++; style += 'background:#f0fdf4;border-left:3px solid #16a34a;'; }
        else if (row[0] === '-') { removed++; style += 'background:#fef2f2;border-left:3px solid #dc2626;'; }
        else style += 'border-left:3px solid transparent;color:#64748b;';
        html += '<div style="' + style + '">' + esc(row[0] === '=' ? '  ' : row[0] + ' ') + esc(row[1] || ' ') + '</div>';
      });
      res.innerHTML = '<p style="margin-bottom:10px"><b style="color:#16a34a">+' + added + ' added</b> · ' +
        '<b style="color:#dc2626">−' + removed + ' removed</b> · ' + (d.length - added - removed) + ' unchanged</p>' +
        '<div style="border:1px solid #e7eaf3;border-radius:10px;overflow:hidden">' + html + '</div>';
    }
  });

  /* ══════════ 8. JWT Decoder ══════════ */
  T.register('jwt_decoder', function (el) {
    el.innerHTML =
      '<div class="tool-controls"><label>Paste a JWT' +
      '<textarea id="t" style="min-height:110px;font-family:\'JetBrains Mono\',monospace;font-size:12.5px" ' +
      'placeholder="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."></textarea></label>' +
      '<p class="hint" style="color:#d97706"><b>Decoded locally in your browser.</b> A JWT is only base64-encoded, not encrypted — ' +
      'anyone holding the token can read its payload. Never paste a live production token into any online decoder, this one included.</p>' +
      '<div class="result" id="res"></div></div>';
    var t = $(el, '#t'), res = $(el, '#res');
    t.oninput = run;

    function b64url(s) {
      s = s.replace(/-/g, '+').replace(/_/g, '/');
      while (s.length % 4) s += '=';
      return decodeURIComponent(atob(s).split('').map(function (c) {
        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
      }).join(''));
    }

    function run() {
      var raw = t.value.trim();
      if (!raw) { res.innerHTML = ''; return; }
      var parts = raw.split('.');
      if (parts.length < 2) { res.innerHTML = '<p style="color:#dc2626">That doesn\'t look like a JWT — expected three dot-separated segments.</p>'; return; }
      var head, body;
      try { head = JSON.parse(b64url(parts[0])); body = JSON.parse(b64url(parts[1])); }
      catch (e) { res.innerHTML = '<p style="color:#dc2626">Could not decode: ' + esc(e.message) + '</p>'; return; }

      var notes = '';
      if (body.exp) {
        var exp = new Date(body.exp * 1000), dead = exp < new Date();
        notes += '<p style="padding:11px 14px;border-radius:9px;font-weight:600;' +
          (dead ? 'background:#fef2f2;border:1px solid #fecaca;color:#dc2626">⚠ Expired '
            : 'background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a">✓ Valid until ') +
          exp.toLocaleString() + '</p>';
      }
      if (body.iat) notes += '<p class="hint">Issued at: ' + new Date(body.iat * 1000).toLocaleString() + '</p>';

      res.innerHTML = notes +
        block('Header', head) + block('Payload', body) +
        '<p class="hint" style="margin-top:12px"><b>Signature:</b> <code>' + esc((parts[2] || '').slice(0, 40)) +
        '…</code> — verifying it needs the secret or public key, which never leaves your server, so no browser tool can check it.</p>';
    }
    function block(title, obj) {
      return '<h4 style="margin:16px 0 6px">' + title + '</h4>' +
        '<pre style="background:#0f172a;color:#e2e8f0;padding:16px;border-radius:10px;overflow-x:auto;font-size:12.5px;margin:0">' +
        esc(JSON.stringify(obj, null, 2)) + '</pre>';
    }
  });

  /* ══════════ 9. SQL Formatter ══════════ */
  T.register('sql_formatter', function (el) {
    el.innerHTML =
      '<div class="tool-controls"><label>SQL' +
      '<textarea id="in" style="min-height:130px;font-family:\'JetBrains Mono\',monospace;font-size:13px">' +
      'select u.id, u.name, count(o.id) as orders from users u left join orders o on o.user_id = u.id where u.active = 1 and u.created_at > \'2024-01-01\' group by u.id order by orders desc limit 20;</textarea></label>' +
      '<div class="row"><label style="flex:1;min-width:150px">Keyword case<select id="kw">' +
      '<option value="upper">UPPERCASE</option><option value="lower">lowercase</option></select></label>' +
      '<label style="flex:1;min-width:150px">Indent<select id="ind">' +
      '<option value="2">2 spaces</option><option value="4">4 spaces</option></select></label></div>' +
      '<div class="result" id="res"></div></div>';
    var inp = $(el, '#in'), res = $(el, '#res');
    var MAJOR = ['SELECT', 'FROM', 'WHERE', 'GROUP BY', 'HAVING', 'ORDER BY', 'LIMIT', 'INSERT INTO', 'VALUES', 'UPDATE', 'SET', 'DELETE FROM', 'UNION ALL', 'UNION'];
    var JOINS = ['LEFT OUTER JOIN', 'RIGHT OUTER JOIN', 'FULL OUTER JOIN', 'LEFT JOIN', 'RIGHT JOIN', 'INNER JOIN', 'CROSS JOIN', 'JOIN'];
    var MINOR = ['AND', 'OR', 'ON'];
    var WORDS = ['SELECT', 'FROM', 'WHERE', 'GROUP', 'BY', 'HAVING', 'ORDER', 'LIMIT', 'INSERT', 'INTO', 'VALUES',
      'UPDATE', 'SET', 'DELETE', 'JOIN', 'LEFT', 'RIGHT', 'INNER', 'OUTER', 'FULL', 'CROSS', 'ON', 'AND', 'OR',
      'AS', 'COUNT', 'SUM', 'AVG', 'MIN', 'MAX', 'DISTINCT', 'CASE', 'WHEN', 'THEN', 'ELSE', 'END', 'NULL',
      'NOT', 'IN', 'LIKE', 'BETWEEN', 'IS', 'ASC', 'DESC', 'UNION', 'ALL', 'EXISTS'];
    inp.oninput = $(el, '#kw').onchange = $(el, '#ind').onchange = run; run();

    function run() {
      var sql = inp.value.replace(/\s+/g, ' ').trim();
      var upper = $(el, '#kw').value === 'upper';
      var pad = ' '.repeat(+$(el, '#ind').value);

      // Case-normalise keywords outside of quoted literals.
      var parts = sql.split(/('(?:[^']|'')*')/);
      for (var i = 0; i < parts.length; i += 2) {
        parts[i] = parts[i].replace(/\b[a-zA-Z_]+\b/g, function (w) {
          return WORDS.indexOf(w.toUpperCase()) >= 0 ? (upper ? w.toUpperCase() : w.toLowerCase()) : w;
        });
      }
      sql = parts.join('');

      MAJOR.forEach(function (k) {
        sql = sql.replace(new RegExp('\\s*\\b' + k.replace(' ', '\\s+') + '\\b\\s*', 'gi'), '\n' + (upper ? k : k.toLowerCase()) + '\n' + pad);
      });
      JOINS.forEach(function (k) {
        sql = sql.replace(new RegExp('\\s*\\b' + k.replace(/ /g, '\\s+') + '\\b\\s*', 'gi'), '\n' + (upper ? k : k.toLowerCase()) + ' ');
      });
      MINOR.forEach(function (k) {
        sql = sql.replace(new RegExp('\\s+\\b' + k + '\\b\\s+', 'gi'), '\n' + pad + (upper ? k : k.toLowerCase()) + ' ');
      });
      sql = sql.replace(/,\s*/g, ',\n' + pad).replace(/\n{2,}/g, '\n').replace(/[ \t]+$/gm, '').trim();

      res.innerHTML = '<pre id="out" style="background:#0f172a;color:#e2e8f0;padding:18px;border-radius:10px;overflow-x:auto;font-size:13px;line-height:1.7;margin:0">' + esc(sql) + '</pre>';
      var btn = document.createElement('button');
      btn.className = 'btn'; btn.style.marginTop = '12px'; btn.textContent = 'Copy formatted SQL';
      btn.onclick = function () { copyText(sql, btn); };
      res.appendChild(btn);
    }
  });

  /* ══════════ 10. Cron Expression Builder ══════════ */
  T.register('cron_builder', function (el) {
    el.innerHTML =
      '<div class="tool-controls">' +
      '<label>Cron expression<input type="text" id="c" value="0 3 * * 1-5" style="font-family:\'JetBrains Mono\',monospace;font-size:16px;letter-spacing:.08em"></label>' +
      '<div class="row" style="margin-top:6px">' +
      '<button class="btn sm gray" data-p="* * * * *">Every minute</button>' +
      '<button class="btn sm gray" data-p="0 * * * *">Hourly</button>' +
      '<button class="btn sm gray" data-p="0 0 * * *">Daily midnight</button>' +
      '<button class="btn sm gray" data-p="0 9 * * 1">Mondays 9am</button>' +
      '<button class="btn sm gray" data-p="*/15 * * * *">Every 15 min</button>' +
      '<button class="btn sm gray" data-p="0 0 1 * *">Monthly</button></div>' +
      '<div class="result" id="res"></div></div>';
    var c = $(el, '#c'), res = $(el, '#res');
    c.oninput = run;
    $$(el, '[data-p]').forEach(function (b) {
      b.onclick = function () { c.value = b.dataset.p; run(); };
    });
    run();

    var DOW = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    var MON = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    function describeField(f, names, unit) {
      if (f === '*') return 'every ' + unit;
      if (/^\*\/(\d+)$/.test(f)) return 'every ' + RegExp.$1 + ' ' + unit + 's';
      if (/^(\d+)-(\d+)$/.test(f)) {
        var a = +RegExp.$1, b = +RegExp.$2;
        return names ? (names[a] + ' through ' + names[b]) : (unit + ' ' + a + ' through ' + b);
      }
      var list = f.split(',').map(function (v) { return names ? names[+v] : v; });
      return (names ? '' : unit + ' ') + list.join(', ');
    }

    function matches(f, v, max) {
      if (f === '*') return true;
      return f.split(',').some(function (part) {
        var step = 1, range = part;
        if (part.indexOf('/') > -1) { var s = part.split('/'); range = s[0]; step = +s[1]; }
        var lo, hi;
        if (range === '*') { lo = 0; hi = max; }
        else if (range.indexOf('-') > -1) { var r = range.split('-'); lo = +r[0]; hi = +r[1]; }
        else { lo = hi = +range; }
        if (v < lo || v > hi) return false;
        return (v - lo) % step === 0;
      });
    }

    function run() {
      var f = c.value.trim().split(/\s+/);
      if (f.length !== 5) {
        res.innerHTML = '<p style="color:#dc2626">A cron expression needs exactly five fields: minute hour day-of-month month day-of-week.</p>';
        return;
      }
      var desc = 'Runs at ' + describeField(f[0], null, 'minute') +
        ', ' + describeField(f[1], null, 'hour') +
        ', on ' + describeField(f[2], null, 'day of the month') +
        ', in ' + describeField(f[3], MON.map(function (m, i) { return i === 0 ? '' : m; }), 'month') +
        ', on ' + describeField(f[4], DOW, 'day of the week') + '.';

      // Walk forward minute by minute to find the next five firings.
      var runs = [], d = new Date(); d.setSeconds(0, 0); d.setMinutes(d.getMinutes() + 1);
      for (var i = 0; i < 527040 && runs.length < 5; i++) {
        if (matches(f[0], d.getMinutes(), 59) && matches(f[1], d.getHours(), 23) &&
          matches(f[2], d.getDate(), 31) && matches(f[3], d.getMonth() + 1, 12) &&
          matches(f[4], d.getDay(), 6)) {
          runs.push(new Date(d));
        }
        d.setMinutes(d.getMinutes() + 1);
      }

      res.innerHTML =
        '<div style="background:#eef2ff;border:1px solid #c7d2fe;border-radius:11px;padding:16px;font-size:15px;font-weight:600;color:#4338ca">' +
        esc(desc) + '</div>' +
        '<h4 style="margin:18px 0 8px">Next 5 runs (your local time)</h4>' +
        (runs.length
          ? '<ul style="list-style:none;padding:0;margin:0">' + runs.map(function (r) {
            return '<li style="padding:8px 12px;background:#f8fafc;border-radius:8px;margin-bottom:6px;font-family:\'JetBrains Mono\',monospace;font-size:13px">' +
              r.toLocaleString(undefined, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) + '</li>';
          }).join('') + '</ul>'
          : '<p style="color:#d97706">This expression never fires within the next year — check the day-of-month and month fields.</p>');
    }
  });

  /* ══════════ 11. UUID / ID Generator ══════════ */
  T.register('uuid_generator', function (el) {
    el.innerHTML =
      '<div class="tool-controls"><div class="row">' +
      '<label style="flex:1;min-width:160px">Format<select id="kind">' +
      '<option value="v4">UUID v4 (random)</option><option value="ulid">ULID (sortable)</option>' +
      '<option value="nano">Nano ID (21 chars)</option><option value="short">Short ID (8 chars)</option></select></label>' +
      '<label style="flex:1;min-width:140px">How many<input type="number" id="n" value="10" min="1" max="500"></label>' +
      '<label style="flex:1;min-width:150px;display:flex;align-items:center;gap:7px;margin-top:22px">' +
      '<input type="checkbox" id="upper" style="width:auto"> Uppercase</label></div>' +
      '<button class="btn" id="go" style="margin-top:6px">Generate</button>' +
      '<div class="result" id="res"></div></div>';
    var res = $(el, '#res');
    $(el, '#go').onclick = run; run();

    function rand(n) { var a = new Uint8Array(n); crypto.getRandomValues(a); return a; }
    function v4() {
      var b = rand(16);
      b[6] = (b[6] & 0x0f) | 0x40; b[8] = (b[8] & 0x3f) | 0x80;
      var h = Array.prototype.map.call(b, function (x) { return ('0' + x.toString(16)).slice(-2); }).join('');
      return h.slice(0, 8) + '-' + h.slice(8, 12) + '-' + h.slice(12, 16) + '-' + h.slice(16, 20) + '-' + h.slice(20);
    }
    function ulid() {
      var A = '0123456789ABCDEFGHJKMNPQRSTVWXYZ', t = Date.now(), s = '';
      for (var i = 9; i >= 0; i--) { s = A[t % 32] + s; t = Math.floor(t / 32); }
      var r = rand(16);
      for (var j = 0; j < 16; j++) s += A[r[j] % 32];
      return s;
    }
    function nano(len) {
      var A = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-';
      var r = rand(len), s = '';
      for (var i = 0; i < len; i++) s += A[r[i] % A.length];
      return s;
    }

    function run() {
      var kind = $(el, '#kind').value, n = Math.min(500, Math.max(1, +$(el, '#n').value || 1)), out = [];
      for (var i = 0; i < n; i++) {
        out.push(kind === 'v4' ? v4() : kind === 'ulid' ? ulid() : kind === 'nano' ? nano(21) : nano(8));
      }
      var text = out.join('\n');
      if ($(el, '#upper').checked) text = text.toUpperCase();
      res.innerHTML = '<pre style="background:#0f172a;color:#7dd3fc;padding:16px;border-radius:10px;overflow-x:auto;' +
        'font-size:13px;line-height:1.8;margin:0;max-height:340px">' + esc(text) + '</pre>';
      var b = document.createElement('button');
      b.className = 'btn'; b.style.marginTop = '12px'; b.textContent = 'Copy all ' + n;
      b.onclick = function () { copyText(text, b); };
      res.appendChild(b);
    }
  });

  /* ══════════ 12. Google SERP Preview ══════════
     Measures real pixel widths in Google's own fonts, because Google
     truncates by pixels, not characters. */
  T.register('serp_preview', function (el) {
    el.innerHTML =
      '<div class="tool-controls">' +
      '<label>Page title<input type="text" id="t" value="Free Online Image Compressor — Reduce File Size Without Losing Quality"></label>' +
      '<label>URL<input type="text" id="u" value="https://example.com/tools/image-compressor"></label>' +
      '<label>Meta description<textarea id="d" style="min-height:80px">Compress JPG, PNG and WebP images right in your browser. No uploads, no watermarks, no sign-up — your files never leave your device.</textarea></label>' +
      '<div class="row"><label style="flex:1;min-width:150px">Device<select id="dev">' +
      '<option value="desktop">Desktop</option><option value="mobile">Mobile</option></select></label></div>' +
      '<div class="result" id="res"></div></div>';
    var res = $(el, '#res');
    ['#t', '#u', '#d', '#dev'].forEach(function (s) { $(el, s).oninput = $(el, s).onchange = run; });
    run();

    // Google truncates titles at ~600px desktop / ~920px mobile (rendered in Arial ≈ its own font).
    var measure = document.createElement('canvas').getContext('2d');
    function px(text, font) { measure.font = font; return measure.measureText(text).width; }
    function truncate(text, font, limit) {
      if (px(text, font) <= limit) return { text: text, cut: false };
      var s = text;
      while (s.length && px(s + '…', font) > limit) s = s.slice(0, -1);
      return { text: s.replace(/\s+\S*$/, '') + '…', cut: true };
    }

    function run() {
      var mobile = $(el, '#dev').value === 'mobile';
      var tFont = mobile ? '400 18px Arial' : '400 20px Arial';
      var dFont = mobile ? '400 14px Arial' : '400 14px Arial';
      var tLimit = mobile ? 920 : 600, dLimit = mobile ? 1560 : 1500;

      var title = truncate($(el, '#t').value, tFont, tLimit);
      var desc = truncate($(el, '#d').value, dFont, dLimit);
      var url = $(el, '#u').value.replace(/^https?:\/\//, '').replace(/\/$/, '').split('/');
      var crumb = url[0] + (url.length > 1 ? ' › ' + url.slice(1).join(' › ') : '');

      var tw = Math.round(px($(el, '#t').value, tFont)), dw = Math.round(px($(el, '#d').value, dFont));

      res.innerHTML =
        '<div style="background:#fff;border:1px solid #e7eaf3;border-radius:12px;padding:22px;max-width:' + (mobile ? '420px' : '620px') + '">' +
        '<div style="font-size:12px;color:#4d5156;margin-bottom:3px">' + esc(crumb) + '</div>' +
        '<div style="font-size:' + (mobile ? '18px' : '20px') + ';color:#1a0dab;line-height:1.3;margin-bottom:4px;font-family:Arial,sans-serif">' + esc(title.text) + '</div>' +
        '<div style="font-size:14px;color:#4d5156;line-height:1.58;font-family:Arial,sans-serif">' + esc(desc.text) + '</div>' +
        '</div>' +
        '<div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:16px">' +
        bar('Title', tw, tLimit, title.cut) + bar('Description', dw, dLimit, desc.cut) + '</div>' +
        '<p class="hint" style="margin-top:12px">Google truncates by pixel width, not character count — so a title of capital letters runs out of room sooner than the same number of lowercase ones. These bars measure the real rendered width.</p>';
    }

    function bar(label, w, limit, cut) {
      var pct = Math.min(100, Math.round(w / limit * 100));
      var col = cut ? '#dc2626' : pct > 90 ? '#d97706' : '#16a34a';
      return '<div style="flex:1;min-width:200px">' +
        '<div style="display:flex;justify-content:space-between;font-size:12.5px;font-weight:700;margin-bottom:5px">' +
        '<span>' + label + '</span><span style="color:' + col + '">' + w + ' / ' + limit + ' px' + (cut ? ' · truncated' : '') + '</span></div>' +
        '<div style="background:#eef1f8;border-radius:99px;height:8px;overflow:hidden">' +
        '<div style="height:100%;width:' + pct + '%;background:' + col + '"></div></div></div>';
    }
  });

  /* ══════════ 13. Readability Analyzer ══════════ */
  T.register('readability', function (el) {
    el.innerHTML =
      '<div class="tool-controls"><label>Paste your text' +
      '<textarea id="t" style="min-height:200px">Paste an article here. This tool scores how hard it is to read, flags sentences that run too long, and highlights passive voice and adverbs — the same feedback the well-known paid desktop editors give you.</textarea></label>' +
      '<div class="result" id="res"></div></div>';
    var t = $(el, '#t'), res = $(el, '#res');
    t.oninput = run; run();

    function syllables(w) {
      w = w.toLowerCase().replace(/[^a-z]/g, '');
      if (w.length <= 3) return 1;
      w = w.replace(/(?:[^laeiouy]es|ed|[^laeiouy]e)$/, '').replace(/^y/, '');
      var m = w.match(/[aeiouy]{1,2}/g);
      return m ? m.length : 1;
    }

    function run() {
      var text = t.value.trim();
      if (!text) { res.innerHTML = ''; return; }
      var sentences = text.split(/[.!?]+(?:\s|$)/).filter(function (s) { return s.trim().length; });
      var words = text.match(/\b[\w']+\b/g) || [];
      var syl = words.reduce(function (a, w) { return a + syllables(w); }, 0);
      var W = words.length || 1, S = sentences.length || 1;

      var flesch = 206.835 - 1.015 * (W / S) - 84.6 * (syl / W);
      var grade = 0.39 * (W / S) + 11.8 * (syl / W) - 15.59;
      var level = flesch >= 90 ? 'Very easy (5th grade)' : flesch >= 80 ? 'Easy (6th grade)' :
        flesch >= 70 ? 'Fairly easy (7th grade)' : flesch >= 60 ? 'Plain English (8–9th grade)' :
          flesch >= 50 ? 'Fairly hard (10–12th grade)' : flesch >= 30 ? 'Hard (college)' : 'Very hard (graduate)';
      var col = flesch >= 60 ? '#16a34a' : flesch >= 30 ? '#d97706' : '#dc2626';

      var longs = sentences.filter(function (s) { return (s.match(/\b[\w']+\b/g) || []).length > 25; });
      var passive = (text.match(/\b(?:was|were|is|are|been|being|be)\s+\w+(?:ed|en)\b/gi) || []);
      var adverbs = (text.match(/\b\w+ly\b/gi) || []);
      var readMin = Math.max(1, Math.round(W / 225));

      res.innerHTML =
        '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:14px;margin-bottom:20px">' +
        card(Math.round(flesch), 'Flesch score', col) +
        card(Math.max(1, Math.round(grade)), 'Grade level') +
        card(W.toLocaleString(), 'Words') +
        card(S, 'Sentences') +
        card(Math.round(W / S), 'Avg words/sentence') +
        card(readMin + ' min', 'Read time') +
        '</div>' +
        '<div style="background:' + col + '18;border:1px solid ' + col + '55;border-radius:11px;padding:15px;font-weight:700;color:' + col + '">' +
        level + '</div>' +
        '<h4 style="margin:20px 0 8px">Suggestions</h4><ul style="margin:0;padding-left:20px;line-height:1.9;font-size:14px">' +
        li(longs.length, 'sentence', 'longer than 25 words — split them so readers don\'t lose the thread') +
        li(passive.length, 'passive construction', 'like "was written" — active voice reads faster') +
        li(adverbs.length, 'adverb', 'ending in -ly — most can be cut or replaced with a stronger verb') +
        (W / S > 20 ? '<li>Your average sentence is <b>' + Math.round(W / S) + ' words</b>. Aim for 15–20 for web reading.</li>' : '') +
        '</ul>' +
        (longs.length ? '<h4 style="margin:20px 0 8px">Longest sentences</h4>' +
          longs.slice(0, 5).map(function (s) {
            return '<p style="background:#fffbeb;border-left:3px solid #f59e0b;padding:10px 14px;margin:0 0 8px;font-size:13.5px;border-radius:0 8px 8px 0">' +
              esc(s.trim().slice(0, 240)) + ((s.length > 240) ? '…' : '') +
              ' <b style="color:#d97706">(' + (s.match(/\b[\w']+\b/g) || []).length + ' words)</b></p>';
          }).join('') : '');
    }
    function card(n, l, c) {
      return '<div style="background:#f8fafc;border:1px solid #e7eaf3;border-radius:11px;padding:14px;text-align:center">' +
        '<div style="font-size:24px;font-weight:800' + (c ? ';color:' + c : '') + '">' + n + '</div>' +
        '<div style="font-size:11.5px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.03em;margin-top:2px">' + l + '</div></div>';
    }
    function li(n, noun, tail) {
      if (!n) return '';
      return '<li><b>' + n + '</b> ' + noun + (n === 1 ? '' : 's') + ' ' + tail + '.</li>';
    }
  });

  /* ══════════ 14. Keyword Density ══════════ */
  T.register('keyword_density', function (el) {
    el.innerHTML =
      '<div class="tool-controls"><label>Your content' +
      '<textarea id="t" style="min-height:180px" placeholder="Paste the article you want to analyse…"></textarea></label>' +
      '<div class="row"><label style="flex:1;min-width:160px">Phrase length<select id="n">' +
      '<option value="1">Single words</option><option value="2">Two-word phrases</option><option value="3">Three-word phrases</option></select></label>' +
      '<label style="flex:1;min-width:180px">Target keyword (optional)<input type="text" id="kw" placeholder="e.g. image compressor"></label></div>' +
      '<div class="result" id="res"></div></div>';
    var t = $(el, '#t'), res = $(el, '#res');
    var STOP = ('the a an and or but of to in for on at by with from as is are was were be been being it its this that these those ' +
      'you your we our they their he she his her i me my not no so if then than there here what which who whom will would can could ' +
      'should may might do does did done have has had about into over under out up down off just also very more most such own same too').split(' ');
    ['#t', '#n', '#kw'].forEach(function (s) { $(el, s).oninput = $(el, s).onchange = run; });

    function run() {
      var text = t.value.toLowerCase();
      var words = (text.match(/\b[a-z؀-ۿ][a-z؀-ۿ'-]*\b/g) || []);
      if (!words.length) { res.innerHTML = ''; return; }
      var n = +$(el, '#n').value, counts = {}, total = 0;

      for (var i = 0; i + n <= words.length; i++) {
        var gram = words.slice(i, i + n);
        if (n === 1 && STOP.indexOf(gram[0]) >= 0) continue;
        if (n > 1 && (STOP.indexOf(gram[0]) >= 0 || STOP.indexOf(gram[n - 1]) >= 0)) continue;
        var key = gram.join(' ');
        counts[key] = (counts[key] || 0) + 1;
        total++;
      }
      var rows = Object.keys(counts).map(function (k) { return [k, counts[k]]; })
        .sort(function (a, b) { return b[1] - a[1]; }).slice(0, 25);
      if (!rows.length) { res.innerHTML = '<p class="hint">Not enough content to analyse yet.</p>'; return; }

      var target = $(el, '#kw').value.trim().toLowerCase();
      var tCount = target ? (counts[target] || 0) : 0;
      var tDensity = total ? (tCount / total * 100) : 0;
      var head = '';
      if (target) {
        var verdict = tDensity === 0 ? ['#dc2626', 'not present — add it to your title, first paragraph and one subheading']
          : tDensity < 0.5 ? ['#d97706', 'a little thin — one or two more natural mentions would help']
            : tDensity <= 2.5 ? ['#16a34a', 'in a healthy range']
              : ['#dc2626', 'over-used — this reads as keyword stuffing and can hurt rankings'];
        head = '<div style="background:' + verdict[0] + '18;border:1px solid ' + verdict[0] + '55;border-radius:11px;padding:15px;margin-bottom:18px">' +
          '<b style="color:' + verdict[0] + '">"' + esc(target) + '" appears ' + tCount + ' time' + (tCount === 1 ? '' : 's') +
          ' (' + tDensity.toFixed(2) + '%)</b> — ' + verdict[1] + '.</div>';
      }

      var max = rows[0][1];
      res.innerHTML = head +
        '<p class="hint" style="margin-bottom:12px">' + words.length.toLocaleString() + ' words analysed · ' +
        Object.keys(counts).length.toLocaleString() + ' distinct phrases</p>' +
        rows.map(function (r) {
          var d = (r[1] / total * 100);
          return '<div style="display:flex;align-items:center;gap:12px;margin-bottom:7px">' +
            '<span style="width:190px;font-size:13.5px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + esc(r[0]) + '</span>' +
            '<div style="flex:1;background:#eef1f8;border-radius:99px;height:9px;overflow:hidden">' +
            '<div style="height:100%;width:' + Math.round(r[1] / max * 100) + '%;background:linear-gradient(135deg,#6366f1,#22d3ee)"></div></div>' +
            '<span style="width:96px;text-align:right;font-size:12.5px;color:#64748b">' + r[1] + ' · ' + d.toFixed(2) + '%</span></div>';
        }).join('');
    }
  });

  /* ══════════ 15. CSS Gradient Generator ══════════ */
  T.register('gradient_generator', function (el) {
    el.innerHTML =
      '<div class="tool-controls"><div class="row">' +
      '<label style="flex:1;min-width:130px">Colour 1<input type="color" id="c1" value="#6366f1"></label>' +
      '<label style="flex:1;min-width:130px">Colour 2<input type="color" id="c2" value="#22d3ee"></label>' +
      '<label style="flex:1;min-width:150px">Type<select id="type">' +
      '<option value="linear">Linear</option><option value="radial">Radial</option><option value="conic">Conic</option></select></label>' +
      '<label style="flex:1;min-width:170px">Angle: <span id="av">135</span>°<input type="range" id="ang" min="0" max="360" value="135"></label></div>' +
      '<div class="row"><label style="flex:1;min-width:150px;display:flex;align-items:center;gap:7px;margin-top:8px">' +
      '<input type="checkbox" id="three" style="width:auto"> Add a third colour</label>' +
      '<label style="flex:1;min-width:130px">Colour 3<input type="color" id="c3" value="#f59e0b"></label></div>' +
      '<div class="result" id="res"></div></div>';
    var res = $(el, '#res');
    ['#c1', '#c2', '#c3', '#type', '#ang', '#three'].forEach(function (s) { $(el, s).oninput = $(el, s).onchange = run; });
    run();

    function css() {
      var c1 = $(el, '#c1').value, c2 = $(el, '#c2').value, c3 = $(el, '#c3').value;
      var stops = $(el, '#three').checked ? [c1, c3, c2] : [c1, c2];
      var a = $(el, '#ang').value, type = $(el, '#type').value;
      if (type === 'radial') return 'radial-gradient(circle at center, ' + stops.join(', ') + ')';
      if (type === 'conic') return 'conic-gradient(from ' + a + 'deg at 50% 50%, ' + stops.join(', ') + ')';
      return 'linear-gradient(' + a + 'deg, ' + stops.join(', ') + ')';
    }

    function run() {
      $(el, '#av').textContent = $(el, '#ang').value;
      var g = css();
      res.innerHTML =
        '<div style="background:' + g + ';height:200px;border-radius:14px;margin-bottom:14px"></div>' +
        '<pre style="background:#0f172a;color:#e2e8f0;padding:16px;border-radius:10px;overflow-x:auto;font-size:13px;margin:0">' +
        esc('background: ' + g + ';') + '</pre>';
      var b = document.createElement('button');
      b.className = 'btn'; b.style.marginTop = '12px'; b.textContent = 'Copy CSS';
      b.onclick = function () { copyText('background: ' + g + ';', b); };
      res.appendChild(b);
      var r = document.createElement('button');
      r.className = 'btn gray'; r.style.cssText = 'margin:12px 0 0 8px'; r.textContent = 'Randomise';
      r.onclick = function () {
        function rc() { return '#' + Array.from(crypto.getRandomValues(new Uint8Array(3))).map(function (x) { return ('0' + x.toString(16)).slice(-2); }).join(''); }
        $(el, '#c1').value = rc(); $(el, '#c2').value = rc(); $(el, '#c3').value = rc();
        $(el, '#ang').value = Math.floor(Math.random() * 360);
        run();
      };
      res.appendChild(r);
    }
  });

  /* ══════════ 16. Palette Extractor ══════════
     Median-cut quantisation, then sorted by population. */
  T.register('palette_from_image', function (el) {
    el.innerHTML =
      '<div class="tool-controls"><div class="drop" id="d">Click or drop an image to pull its colour palette</div>' +
      '<input type="file" id="f" accept="image/*" style="display:none">' +
      '<label>Colours to extract: <span id="nv">6</span><input type="range" id="n" min="3" max="12" value="6"></label>' +
      '<div class="result" id="res"></div></div>';
    var f = $(el, '#f'), res = $(el, '#res'), img = null;
    $(el, '#d').onclick = function () { f.click(); };
    f.onchange = function () { if (f.files[0]) readImage(f.files[0]).then(function (i) { img = i; run(); }); };
    $(el, '#n').oninput = function () { $(el, '#nv').textContent = $(el, '#n').value; if (img) run(); };

    function medianCut(pixels, depth) {
      if (depth === 0 || pixels.length === 0) {
        if (!pixels.length) return [];
        var sum = [0, 0, 0];
        pixels.forEach(function (p) { sum[0] += p[0]; sum[1] += p[1]; sum[2] += p[2]; });
        return [[Math.round(sum[0] / pixels.length), Math.round(sum[1] / pixels.length), Math.round(sum[2] / pixels.length), pixels.length]];
      }
      var ranges = [0, 1, 2].map(function (ch) {
        var lo = 255, hi = 0;
        pixels.forEach(function (p) { if (p[ch] < lo) lo = p[ch]; if (p[ch] > hi) hi = p[ch]; });
        return hi - lo;
      });
      var ch = ranges.indexOf(Math.max.apply(null, ranges));
      pixels.sort(function (a, b) { return a[ch] - b[ch]; });
      var mid = pixels.length >> 1;
      return medianCut(pixels.slice(0, mid), depth - 1).concat(medianCut(pixels.slice(mid), depth - 1));
    }

    function run() {
      var size = 120;
      var c = document.createElement('canvas'); c.width = c.height = size;
      var ctx = c.getContext('2d');
      ctx.drawImage(img, 0, 0, size, size);
      var d = ctx.getImageData(0, 0, size, size).data, px = [];
      for (var i = 0; i < d.length; i += 4) {
        if (d[i + 3] < 128) continue;
        px.push([d[i], d[i + 1], d[i + 2]]);
      }
      var want = +$(el, '#n').value;
      var depth = Math.ceil(Math.log2(want));
      var cols = medianCut(px, depth).sort(function (a, b) { return b[3] - a[3]; }).slice(0, want);

      res.innerHTML = '<div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:16px">' +
        cols.map(function (col) {
          var hex = '#' + col.slice(0, 3).map(function (v) { return ('0' + v.toString(16)).slice(-2); }).join('');
          var lum = (0.299 * col[0] + 0.587 * col[1] + 0.114 * col[2]) / 255;
          return '<div style="flex:1;min-width:110px"><div style="background:' + hex + ';height:96px;border-radius:11px;' +
            'display:flex;align-items:flex-end;justify-content:center;padding-bottom:9px;color:' + (lum > .6 ? '#0f172a' : '#fff') +
            ';font-family:\'JetBrains Mono\',monospace;font-size:12.5px;font-weight:600">' + hex + '</div>' +
            '<div style="font-size:11.5px;color:#64748b;text-align:center;margin-top:5px">rgb(' + col.slice(0, 3).join(', ') + ')</div></div>';
        }).join('') + '</div>';

      var hexes = cols.map(function (col) {
        return '#' + col.slice(0, 3).map(function (v) { return ('0' + v.toString(16)).slice(-2); }).join('');
      });
      var vars = ':root {\n' + hexes.map(function (h, i) { return '  --color-' + (i + 1) + ': ' + h + ';'; }).join('\n') + '\n}';
      var pre = document.createElement('pre');
      pre.style.cssText = 'background:#0f172a;color:#e2e8f0;padding:16px;border-radius:10px;overflow-x:auto;font-size:13px;margin:0';
      pre.textContent = vars;
      res.appendChild(pre);
      var b = document.createElement('button');
      b.className = 'btn'; b.style.marginTop = '12px'; b.textContent = 'Copy CSS variables';
      b.onclick = function () { copyText(vars, b); };
      res.appendChild(b);
    }
  });

  /* ══════════ 17. Images → PDF ══════════
     Builds the PDF byte-for-byte: JPEG frames become DCTDecode XObjects,
     so there's no library and no upload. */
  T.register('image_to_pdf', function (el) {
    el.innerHTML =
      '<div class="tool-controls"><div class="drop" id="d">Click or drop images — they become one PDF, in the order you pick them</div>' +
      '<input type="file" id="f" accept="image/*" multiple style="display:none">' +
      '<div class="row"><label style="flex:1;min-width:150px">Page size<select id="size">' +
      '<option value="fit">Fit each image</option><option value="a4">A4 portrait</option><option value="a4l">A4 landscape</option>' +
      '<option value="letter">US Letter</option></select></label>' +
      '<label style="flex:1;min-width:160px">Quality: <span id="qv">85</span>%<input type="range" id="q" min="40" max="100" value="85"></label></div>' +
      '<div id="list"></div><div class="result" id="res"></div></div>';
    var f = $(el, '#f'), list = $(el, '#list'), res = $(el, '#res'), files = [];
    $(el, '#d').onclick = function () { f.click(); };
    $(el, '#q').oninput = function () { $(el, '#qv').textContent = $(el, '#q').value; };
    f.onchange = function () {
      files = files.concat(Array.prototype.slice.call(f.files));
      paint();
    };

    function paint() {
      list.innerHTML = files.length
        ? '<p style="font-weight:700;margin:14px 0 8px">' + files.length + ' page' + (files.length === 1 ? '' : 's') + '</p>' +
        files.map(function (file, i) {
          return '<div style="display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid #eef1f8;font-size:13.5px">' +
            '<span style="color:#94a3b8;width:24px">' + (i + 1) + '.</span><span style="flex:1">' + esc(file.name) + '</span>' +
            '<span style="color:#64748b;font-size:12.5px">' + fmtBytes(file.size) + '</span>' +
            '<button class="btn gray sm" data-rm="' + i + '">Remove</button></div>';
        }).join('')
        : '';
      $$(list, '[data-rm]').forEach(function (b) {
        b.onclick = function () { files.splice(+b.dataset.rm, 1); paint(); };
      });
      res.innerHTML = '';
      if (files.length) {
        var btn = document.createElement('button');
        btn.className = 'btn'; btn.style.marginTop = '14px';
        btn.textContent = 'Build PDF (' + files.length + ' page' + (files.length === 1 ? '' : 's') + ')';
        btn.onclick = function () { btn.disabled = true; btn.textContent = 'Building…'; build(btn); };
        res.appendChild(btn);
      }
    }

    /** Re-encodes one image to JPEG and returns its bytes + dimensions. */
    function toJpeg(file) {
      return readImage(file).then(function (img) {
        var c = document.createElement('canvas');
        var max = 2000, s = Math.min(1, max / Math.max(img.width, img.height));
        c.width = Math.round(img.width * s); c.height = Math.round(img.height * s);
        var ctx = c.getContext('2d');
        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
        ctx.drawImage(img, 0, 0, c.width, c.height);
        return new Promise(function (resolve) {
          c.toBlob(function (b) {
            b.arrayBuffer().then(function (buf) {
              resolve({ bytes: new Uint8Array(buf), w: c.width, h: c.height });
            });
          }, 'image/jpeg', +$(el, '#q').value / 100);
        });
      });
    }

    function build(btn) {
      Promise.all(files.map(toJpeg)).then(function (imgs) {
        var SIZES = { a4: [595, 842], a4l: [842, 595], letter: [612, 792] };
        var mode = $(el, '#size').value;
        // Object numbering: 1=catalog, 2=pages, then 3 objects per image.
        var pageIds = imgs.map(function (_, i) { return 3 + i * 3; });
        var offsets = [0], bytes = [];
        function write(s) {
          if (typeof s === 'string') {
            for (var i = 0; i < s.length; i++) bytes.push(s.charCodeAt(i) & 0xff);
          } else {
            for (var j = 0; j < s.length; j++) bytes.push(s[j]);
          }
        }
        function mark() { offsets.push(bytes.length); }

        write('%PDF-1.4\n');
        mark();
        write('1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n');
        mark();
        write('2 0 obj\n<< /Type /Pages /Kids [' + pageIds.map(function (id) { return id + ' 0 R'; }).join(' ') +
          '] /Count ' + imgs.length + ' >>\nendobj\n');

        imgs.forEach(function (im, i) {
          var pid = pageIds[i], cid = pid + 1, iid = pid + 2;
          var pw, ph, dw, dh, ox, oy;
          if (mode === 'fit') { pw = im.w * 0.75; ph = im.h * 0.75; dw = pw; dh = ph; ox = 0; oy = 0; }
          else {
            pw = SIZES[mode][0]; ph = SIZES[mode][1];
            var sc = Math.min(pw / im.w, ph / im.h);
            dw = im.w * sc; dh = im.h * sc;
            ox = (pw - dw) / 2; oy = (ph - dh) / 2;
          }
          var content = 'q\n' + dw.toFixed(2) + ' 0 0 ' + dh.toFixed(2) + ' ' + ox.toFixed(2) + ' ' + oy.toFixed(2) + ' cm\n/Im0 Do\nQ\n';

          mark();
          write(pid + ' 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' + pw.toFixed(2) + ' ' + ph.toFixed(2) +
            '] /Resources << /XObject << /Im0 ' + iid + ' 0 R >> >> /Contents ' + cid + ' 0 R >>\nendobj\n');
          mark();
          write(cid + ' 0 obj\n<< /Length ' + content.length + ' >>\nstream\n' + content + 'endstream\nendobj\n');
          mark();
          write(iid + ' 0 obj\n<< /Type /XObject /Subtype /Image /Width ' + im.w + ' /Height ' + im.h +
            ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' + im.bytes.length + ' >>\nstream\n');
          write(im.bytes);
          write('\nendstream\nendobj\n');
        });

        var xref = bytes.length;
        var count = offsets.length;
        write('xref\n0 ' + count + '\n0000000000 65535 f \n');
        for (var i = 1; i < count; i++) {
          write(('0000000000' + offsets[i]).slice(-10) + ' 00000 n \n');
        }
        write('trailer\n<< /Size ' + count + ' /Root 1 0 R >>\nstartxref\n' + xref + '\n%%EOF');

        var blob = new Blob([new Uint8Array(bytes)], { type: 'application/pdf' });
        download('images.pdf', blob);
        btn.disabled = false;
        btn.textContent = 'Build PDF (' + files.length + ' page' + (files.length === 1 ? '' : 's') + ')';
        var note = document.createElement('p');
        note.className = 'hint';
        note.style.marginTop = '10px';
        note.textContent = 'PDF built (' + fmtBytes(blob.size) + ') and downloaded. Nothing was uploaded.';
        res.appendChild(note);
      });
    }
  });

  /* ══════════ 18. Speech to Text ══════════ */
  T.register('speech_to_text', function (el) {
    var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SR) {
      el.innerHTML = '<div class="tool-controls"><p style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;padding:14px;border-radius:10px">' +
        'Your browser doesn\'t support the Web Speech API. This tool works in Chrome, Edge and Safari.</p></div>';
      return;
    }
    el.innerHTML =
      '<div class="tool-controls"><div class="row">' +
      '<label style="flex:1;min-width:180px">Language<select id="lang">' +
      '<option value="en-US">English (US)</option><option value="en-GB">English (UK)</option>' +
      '<option value="ar-SA">العربية</option><option value="fr-FR">Français</option>' +
      '<option value="es-ES">Español</option><option value="de-DE">Deutsch</option>' +
      '<option value="tr-TR">Türkçe</option></select></label></div>' +
      '<div style="display:flex;gap:10px;margin:12px 0;flex-wrap:wrap">' +
      '<button class="btn" id="start">🎙 Start dictating</button>' +
      '<button class="btn gray" id="stop" disabled>Stop</button>' +
      '<button class="btn gray" id="clear">Clear</button></div>' +
      '<div id="status" class="hint"></div>' +
      '<textarea id="out" style="min-height:220px;margin-top:12px" placeholder="Your speech appears here…"></textarea>' +
      '<div class="result" id="res"></div></div>';
    var rec = null, out = $(el, '#out'), status = $(el, '#status'), finalText = '';

    $(el, '#start').onclick = function () {
      rec = new SR();
      rec.lang = $(el, '#lang').value;
      rec.continuous = true;
      rec.interimResults = true;
      finalText = out.value ? out.value + ' ' : '';

      rec.onstart = function () {
        status.innerHTML = '<span style="color:#dc2626">● Listening — speak now</span>';
        $(el, '#start').disabled = true; $(el, '#stop').disabled = false;
      };
      rec.onresult = function (e) {
        var interim = '';
        for (var i = e.resultIndex; i < e.results.length; i++) {
          var txt = e.results[i][0].transcript;
          if (e.results[i].isFinal) finalText += txt + ' '; else interim += txt;
        }
        out.value = finalText + interim;
        out.scrollTop = out.scrollHeight;
      };
      rec.onerror = function (e) {
        status.innerHTML = '<span style="color:#dc2626">Error: ' + esc(e.error) +
          (e.error === 'not-allowed' ? ' — allow microphone access in your browser.' : '') + '</span>';
      };
      rec.onend = function () {
        status.textContent = 'Stopped.';
        $(el, '#start').disabled = false; $(el, '#stop').disabled = true;
        stats();
      };
      rec.start();
    };
    $(el, '#stop').onclick = function () { if (rec) rec.stop(); };
    $(el, '#clear').onclick = function () { out.value = ''; finalText = ''; stats(); };
    out.oninput = stats;

    function stats() {
      var w = (out.value.match(/\b[\w'؀-ۿ]+\b/g) || []).length;
      $(el, '#res').innerHTML = w
        ? '<p class="hint">' + w.toLocaleString() + ' words · about ' + Math.max(1, Math.round(w / 150)) + ' min of speech</p>' +
        '<button class="btn sm" id="cp">Copy transcript</button> ' +
        '<button class="btn sm gray" id="dl">Download .txt</button>'
        : '';
      var cp = $(el, '#res').querySelector('#cp');
      if (cp) cp.onclick = function () { copyText(out.value, cp); };
      var dl = $(el, '#res').querySelector('#dl');
      if (dl) dl.onclick = function () { download('transcript.txt', new Blob([out.value], { type: 'text/plain' })); };
    }
  });

  /* ══════════ 19. Robots.txt Generator ══════════ */
  T.register('robots_generator', function (el) {
    el.innerHTML =
      '<div class="tool-controls">' +
      '<label>Sitemap URL<input type="text" id="sm" placeholder="https://example.com/sitemap.xml"></label>' +
      '<label>Disallow these paths (one per line)<textarea id="dis" style="min-height:90px;font-family:\'JetBrains Mono\',monospace;font-size:13px">/admin/\n/install/\n/cgi-bin/</textarea></label>' +
      '<label>Allow these paths (one per line, optional)<textarea id="al" style="min-height:60px;font-family:\'JetBrains Mono\',monospace;font-size:13px"></textarea></label>' +
      '<div class="row"><label style="flex:1;min-width:170px">Crawl delay (seconds)<input type="number" id="cd" min="0" max="60" placeholder="none"></label></div>' +
      '<p style="font-weight:700;margin:14px 0 6px;font-size:13.5px">Block these crawlers entirely</p>' +
      '<div id="bots" style="display:flex;flex-wrap:wrap;gap:8px"></div>' +
      '<div class="result" id="res"></div></div>';
    var BOTS = ['GPTBot', 'CCBot', 'Google-Extended', 'anthropic-ai', 'ClaudeBot', 'PerplexityBot', 'Bytespider', 'AhrefsBot', 'SemrushBot', 'MJ12bot', 'DotBot'];
    var botsEl = $(el, '#bots'), res = $(el, '#res');
    botsEl.innerHTML = BOTS.map(function (b) {
      return '<label style="display:flex;align-items:center;gap:6px;background:#f8fafc;border:1px solid #e7eaf3;' +
        'border-radius:8px;padding:6px 11px;font-size:12.5px;font-weight:600;cursor:pointer;margin:0">' +
        '<input type="checkbox" value="' + b + '" style="width:auto">' + b + '</label>';
    }).join('');
    $$(el, 'input, textarea, select').forEach(function (i) { i.oninput = i.onchange = run; });
    run();

    function run() {
      var lines = ['User-agent: *'];
      $(el, '#al').value.split('\n').map(function (s) { return s.trim(); }).filter(Boolean)
        .forEach(function (p) { lines.push('Allow: ' + p); });
      $(el, '#dis').value.split('\n').map(function (s) { return s.trim(); }).filter(Boolean)
        .forEach(function (p) { lines.push('Disallow: ' + p); });
      var cd = $(el, '#cd').value;
      if (cd) lines.push('Crawl-delay: ' + cd);

      $$(botsEl, 'input:checked').forEach(function (b) {
        lines.push('', 'User-agent: ' + b.value, 'Disallow: /');
      });

      var sm = $(el, '#sm').value.trim();
      if (sm) lines.push('', 'Sitemap: ' + sm);

      var text = lines.join('\n');
      res.innerHTML = '<pre style="background:#0f172a;color:#e2e8f0;padding:18px;border-radius:10px;overflow-x:auto;font-size:13px;line-height:1.7;margin:0">' + esc(text) + '</pre>' +
        '<p class="hint" style="margin-top:10px">robots.txt controls <em>crawling</em>, not indexing — a blocked page can still appear in results if other sites link to it. Use a <code>noindex</code> meta tag to keep a page out of the index.</p>';
      var b1 = document.createElement('button');
      b1.className = 'btn'; b1.textContent = 'Copy'; b1.style.marginRight = '8px';
      b1.onclick = function () { copyText(text, b1); };
      var b2 = document.createElement('button');
      b2.className = 'btn gray'; b2.textContent = 'Download robots.txt';
      b2.onclick = function () { download('robots.txt', new Blob([text], { type: 'text/plain' })); };
      res.appendChild(b1); res.appendChild(b2);
    }
  });

  /* ══════════ 20. Meta Tag Generator ══════════ */
  T.register('meta_generator', function (el) {
    el.innerHTML =
      '<div class="tool-controls">' +
      '<div class="row"><label style="flex:1;min-width:220px">Page title<input type="text" id="t" value="My Page Title"></label>' +
      '<label style="flex:1;min-width:200px">Site name<input type="text" id="sn" value="My Site"></label></div>' +
      '<label>Description<textarea id="d" style="min-height:70px">A clear, benefit-led sentence describing this page in about 155 characters.</textarea></label>' +
      '<div class="row"><label style="flex:1;min-width:220px">Canonical URL<input type="text" id="u" value="https://example.com/page"></label>' +
      '<label style="flex:1;min-width:220px">Preview image URL<input type="text" id="img" value="https://example.com/og.jpg"></label></div>' +
      '<div class="row"><label style="flex:1;min-width:170px">Type<select id="ty">' +
      '<option value="website">Website</option><option value="article">Article</option><option value="product">Product</option></select></label>' +
      '<label style="flex:1;min-width:170px">Twitter handle<input type="text" id="tw" placeholder="@yourhandle"></label>' +
      '<label style="flex:1;min-width:170px">Robots<select id="rb">' +
      '<option value="index, follow">index, follow</option><option value="noindex, follow">noindex, follow</option>' +
      '<option value="index, nofollow">index, nofollow</option><option value="noindex, nofollow">noindex, nofollow</option></select></label></div>' +
      '<div class="result" id="res"></div></div>';
    var res = $(el, '#res');
    $$(el, 'input, textarea, select').forEach(function (i) { i.oninput = i.onchange = run; });
    run();

    function run() {
      function v(s) { return $(el, s).value.trim(); }
      var tags = [
        '<title>' + v('#t') + '</title>',
        '<meta name="description" content="' + v('#d') + '">',
        '<meta name="robots" content="' + v('#rb') + '">',
        '<link rel="canonical" href="' + v('#u') + '">',
        '',
        '<!-- Open Graph (Facebook, LinkedIn, WhatsApp) -->',
        '<meta property="og:type" content="' + v('#ty') + '">',
        '<meta property="og:title" content="' + v('#t') + '">',
        '<meta property="og:description" content="' + v('#d') + '">',
        '<meta property="og:url" content="' + v('#u') + '">',
        '<meta property="og:image" content="' + v('#img') + '">',
        '<meta property="og:site_name" content="' + v('#sn') + '">',
        '',
        '<!-- Twitter / X -->',
        '<meta name="twitter:card" content="summary_large_image">',
        '<meta name="twitter:title" content="' + v('#t') + '">',
        '<meta name="twitter:description" content="' + v('#d') + '">',
        '<meta name="twitter:image" content="' + v('#img') + '">'
      ];
      if (v('#tw')) tags.push('<meta name="twitter:site" content="' + v('#tw') + '">');

      var text = tags.join('\n');
      var dLen = v('#d').length, tLen = v('#t').length;
      res.innerHTML =
        '<div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:14px">' +
        gauge('Title', tLen, 60) + gauge('Description', dLen, 155) + '</div>' +
        '<pre style="background:#0f172a;color:#e2e8f0;padding:18px;border-radius:10px;overflow-x:auto;font-size:12.5px;line-height:1.7;margin:0">' + esc(text) + '</pre>';
      var b = document.createElement('button');
      b.className = 'btn'; b.style.marginTop = '12px'; b.textContent = 'Copy all meta tags';
      b.onclick = function () { copyText(text, b); };
      res.appendChild(b);
    }
    function gauge(label, n, ideal) {
      var col = n === 0 ? '#94a3b8' : n > ideal ? '#dc2626' : n > ideal * 0.75 ? '#16a34a' : '#d97706';
      return '<div style="flex:1;min-width:190px">' +
        '<div style="display:flex;justify-content:space-between;font-size:12.5px;font-weight:700;margin-bottom:5px">' +
        '<span>' + label + '</span><span style="color:' + col + '">' + n + ' / ' + ideal + ' chars</span></div>' +
        '<div style="background:#eef1f8;border-radius:99px;height:8px;overflow:hidden">' +
        '<div style="height:100%;width:' + Math.min(100, n / ideal * 100) + '%;background:' + col + '"></div></div></div>';
    }
  });

})();
