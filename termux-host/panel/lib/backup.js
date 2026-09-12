'use strict';
const fs = require('fs');
const path = require('path');
const { P, load } = require('./config');
const { run, humanBytes } = require('./util');
const db = require('./db');
const sitesMod = require('./sites');

function list() {
  let files = [];
  try { files = fs.readdirSync(P.BACKUPS); } catch (_) { return []; }
  return files
    .filter(f => f.endsWith('.tar.gz'))
    .map(f => {
      const st = fs.statSync(path.join(P.BACKUPS, f));
      return { name: f, size: st.size, sizeText: humanBytes(st.size), mtime: st.mtime.toISOString() };
    })
    .sort((a, b) => b.mtime.localeCompare(a.mtime));
}

/** نسخة احتياطية لموقع: الملفات + قاعدة البيانات المرتبطة (إن وُجدت) */
async function create(siteId, opts = {}) {
  const site = sitesMod.get(siteId);
  if (!site) return { ok: false, error: 'الموقع غير موجود' };
  const stamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
  const workDir = path.join(P.TMP, 'bk-' + site.id + '-' + Date.now());
  fs.mkdirSync(workDir, { recursive: true });

  try {
    fs.writeFileSync(path.join(workDir, 'site.json'), JSON.stringify(site, null, 2));

    const dbName = opts.database || (site.db && site.db.database);
    if (dbName) {
      const d = await db.dump(dbName, path.join(workDir, 'database.sql'));
      if (!d.ok) fs.writeFileSync(path.join(workDir, 'database.error.txt'), d.error || 'فشل تفريغ قاعدة البيانات');
    }

    const filesTar = path.join(workDir, 'files.tar.gz');
    const t = await run('tar', ['-czf', filesTar, '-C', path.dirname(site.root), path.basename(site.root)], { timeout: 900000 });
    if (!t.ok) return { ok: false, error: 'فشل ضغط الملفات: ' + (t.stderr || '').slice(-400) };

    const outName = site.id + '-' + stamp + '.tar.gz';
    const out = path.join(P.BACKUPS, outName);
    const t2 = await run('tar', ['-czf', out, '-C', workDir, '.'], { timeout: 900000 });
    if (!t2.ok) return { ok: false, error: 'فشل إنشاء الأرشيف: ' + (t2.stderr || '').slice(-400) };

    const st = fs.statSync(out);
    return { ok: true, name: outName, size: st.size, sizeText: humanBytes(st.size) };
  } finally {
    try { fs.rmSync(workDir, { recursive: true, force: true }); } catch (_) {}
  }
}

async function restore(name, opts = {}) {
  const file = path.join(P.BACKUPS, path.basename(String(name)));
  if (!fs.existsSync(file)) return { ok: false, error: 'النسخة غير موجودة' };
  const workDir = path.join(P.TMP, 'rs-' + Date.now());
  fs.mkdirSync(workDir, { recursive: true });
  try {
    const x = await run('tar', ['-xzf', file, '-C', workDir], { timeout: 900000 });
    if (!x.ok) return { ok: false, error: 'فشل فك الأرشيف' };

    const metaPath = path.join(workDir, 'site.json');
    if (!fs.existsSync(metaPath)) return { ok: false, error: 'الأرشيف لا يحتوي بيانات موقع' };
    const meta = JSON.parse(fs.readFileSync(metaPath, 'utf8'));

    const filesTar = path.join(workDir, 'files.tar.gz');
    if (fs.existsSync(filesTar)) {
      fs.mkdirSync(path.dirname(meta.root), { recursive: true });
      const r = await run('tar', ['-xzf', filesTar, '-C', path.dirname(meta.root)], { timeout: 900000 });
      if (!r.ok) return { ok: false, error: 'فشل استرجاع الملفات' };
    }

    const sqlFile = path.join(workDir, 'database.sql');
    if (opts.database && fs.existsSync(sqlFile)) {
      await db.createDatabase(opts.database);
      const imp = await db.importSql(opts.database, fs.readFileSync(sqlFile, 'utf8'));
      if (!imp.ok) return { ok: false, error: 'فشل استرجاع قاعدة البيانات: ' + imp.error };
    }

    if (!sitesMod.get(meta.id) && opts.recreateSite !== false) {
      const c = await sitesMod.create(meta);
      if (!c.ok) return { ok: false, error: 'استُرجعت الملفات لكن فشل إنشاء الموقع: ' + c.error };
    }
    return { ok: true, site: meta.id, root: meta.root };
  } finally {
    try { fs.rmSync(workDir, { recursive: true, force: true }); } catch (_) {}
  }
}

function remove(name) {
  const file = path.join(P.BACKUPS, path.basename(String(name)));
  if (!file.startsWith(P.BACKUPS)) return { ok: false, error: 'مسار غير صالح' };
  try { fs.unlinkSync(file); } catch (e) { return { ok: false, error: e.message }; }
  return { ok: true };
}

/** حذف النسخ الأقدم من العدد المحدد لكل موقع */
function prune(keep) {
  const k = parseInt(keep, 10) || 5;
  const groups = {};
  for (const b of list()) {
    const site = b.name.replace(/-\d{4}-\d{2}-\d{2}T.*$/, '');
    (groups[site] = groups[site] || []).push(b);
  }
  let deleted = 0;
  for (const site of Object.keys(groups)) {
    groups[site].slice(k).forEach(b => { if (remove(b.name).ok) deleted++; });
  }
  return { ok: true, deleted };
}

module.exports = { list, create, restore, remove, prune };
