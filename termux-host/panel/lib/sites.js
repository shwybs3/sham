'use strict';
const fs = require('fs');
const path = require('path');
const { P, load, update } = require('./config');
const { rid, slug, isDomain } = require('./util');
const nginx = require('./nginx');

const TYPES = ['static', 'php', 'proxy'];

function list() { return load().sites; }
function get(id) { return load().sites.find(s => s.id === id) || null; }
function byDomain(d) { return load().sites.find(s => s.domain === d) || null; }

function scaffold(root, site) {
  fs.mkdirSync(root, { recursive: true });
  fs.mkdirSync(path.join(root, '.well-known'), { recursive: true });
  const idx = path.join(root, site.type === 'php' ? 'index.php' : 'index.html');
  if (fs.existsSync(idx) || fs.existsSync(path.join(root, 'index.html')) || fs.existsSync(path.join(root, 'index.php'))) return;
  const banner = `<!doctype html>
<html lang="ar" dir="rtl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>${site.domain}</title>
<style>body{font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#0d1117;color:#e6edf3;display:grid;place-items:center;min-height:100vh;margin:0;text-align:center}
.c{max-width:36rem;padding:2rem}h1{margin:.3rem 0;font-size:1.6rem}p{color:#9aa4b2;line-height:1.9}
code{background:#161b22;border:1px solid #30363d;padding:.15rem .5rem;border-radius:.4rem;font-size:.85em}</style>
<div class="c">
<h1>${site.domain} يعمل ✅</h1>
<p>هذا الموقع مستضاف على جهاز أندرويد عبر ShamHost.</p>
<p>ارفع ملفاتك إلى:<br><code>${root}</code></p>
</div>`;
  if (site.type === 'php') {
    fs.writeFileSync(path.join(root, 'index.php'),
      banner.replace('</div>', '<p style="color:#3fb950">PHP <?= PHP_VERSION ?> يعمل</p>\n</div>'));
  } else {
    fs.writeFileSync(path.join(root, 'index.html'), banner);
  }
}

async function create(input) {
  const domain = String(input.domain || '').trim().toLowerCase().replace(/^https?:\/\//, '').replace(/\/.*$/, '');
  if (!isDomain(domain)) return { ok: false, error: 'اسم دومين غير صالح: ' + domain };
  if (byDomain(domain)) return { ok: false, error: 'هذا الدومين مضاف مسبقاً' };

  const type = TYPES.includes(input.type) ? input.type : 'static';
  const aliases = (Array.isArray(input.aliases) ? input.aliases : String(input.aliases || '').split(/[\s,]+/))
    .map(a => String(a).trim().toLowerCase()).filter(a => a && isDomain(a));

  const id = slug(domain) || ('site-' + rid(4));
  if (get(id)) return { ok: false, error: 'معرّف الموقع مستخدم' };
  const root = input.root && String(input.root).startsWith(P.SITES)
    ? String(input.root)
    : path.join(P.SITES, id);

  const site = {
    id, domain, aliases, type, root,
    proxy_port: type === 'proxy' ? (parseInt(input.proxy_port, 10) || 3000) : null,
    spa: !!input.spa,
    ssl: false,
    force_https: input.force_https !== false,
    enabled: true,
    max_body: parseInt(input.max_body, 10) || 256,
    db: null,
    created_at: new Date().toISOString(),
  };

  scaffold(root, site);
  update(c => { c.sites.push(site); });
  nginx.writeVhost(site);
  const r = await nginx.reload();
  if (!r.ok) {
    update(c => { c.sites = c.sites.filter(s => s.id !== id); });
    nginx.removeVhost(site);
    await nginx.reload();
    return { ok: false, error: 'رفض nginx الإعداد: ' + (r.output || r.error) };
  }
  return { ok: true, site };
}

async function edit(id, patch) {
  const site = get(id);
  if (!site) return { ok: false, error: 'الموقع غير موجود' };
  const before = JSON.stringify(site);

  const next = Object.assign({}, site);
  if (patch.type && TYPES.includes(patch.type)) next.type = patch.type;
  if (patch.aliases !== undefined) {
    next.aliases = (Array.isArray(patch.aliases) ? patch.aliases : String(patch.aliases).split(/[\s,]+/))
      .map(a => String(a).trim().toLowerCase()).filter(a => a && isDomain(a));
  }
  if (patch.proxy_port !== undefined) next.proxy_port = parseInt(patch.proxy_port, 10) || 3000;
  if (patch.spa !== undefined) next.spa = !!patch.spa;
  if (patch.force_https !== undefined) next.force_https = !!patch.force_https;
  if (patch.enabled !== undefined) next.enabled = !!patch.enabled;
  if (patch.max_body !== undefined) next.max_body = parseInt(patch.max_body, 10) || 256;

  update(c => { const i = c.sites.findIndex(s => s.id === id); if (i >= 0) c.sites[i] = next; });
  if (next.enabled === false) nginx.removeVhost(next); else nginx.writeVhost(next);
  const r = await nginx.reload();
  if (!r.ok) {
    const old = JSON.parse(before);
    update(c => { const i = c.sites.findIndex(s => s.id === id); if (i >= 0) c.sites[i] = old; });
    nginx.writeVhost(old);
    await nginx.reload();
    return { ok: false, error: 'رفض nginx الإعداد: ' + (r.output || r.error) };
  }
  return { ok: true, site: next };
}

async function remove(id, deleteFiles) {
  const site = get(id);
  if (!site) return { ok: false, error: 'الموقع غير موجود' };
  nginx.removeVhost(site);
  update(c => { c.sites = c.sites.filter(s => s.id !== id); });
  if (deleteFiles && site.root.startsWith(P.SITES)) {
    try { fs.rmSync(site.root, { recursive: true, force: true }); } catch (_) {}
  }
  await nginx.reload();
  return { ok: true };
}

function setSsl(domain, on) {
  update(c => {
    const s = c.sites.find(x => x.domain === domain);
    if (s) s.ssl = !!on;
  });
}

/** حجم مجلد الموقع بالبايت */
function sizeOf(root) {
  let total = 0;
  const walk = (dir, depth) => {
    if (depth > 12) return;
    let entries;
    try { entries = fs.readdirSync(dir, { withFileTypes: true }); } catch (_) { return; }
    for (const e of entries) {
      const f = path.join(dir, e.name);
      try {
        if (e.isDirectory()) walk(f, depth + 1);
        else if (e.isFile()) total += fs.statSync(f).size;
      } catch (_) {}
    }
  };
  walk(root, 0);
  return total;
}

module.exports = { list, get, byDomain, create, edit, remove, setSsl, sizeOf, scaffold, TYPES };
