'use strict';
const fs = require('fs');
const path = require('path');
const os = require('os');
const { P } = require('./config');
const { safeJoin, run, humanBytes } = require('./util');

const HOME = process.env.HOME || os.homedir();

const ROOTS = {
  sites:   { label: 'المواقع',          dir: P.SITES },
  backups: { label: 'النسخ الاحتياطية', dir: P.BACKUPS },
  logs:    { label: 'السجلات',          dir: P.LOGS },
  home:    { label: 'المجلد الرئيسي',   dir: HOME },
};

function resolve(rootKey, rel) {
  const root = ROOTS[rootKey];
  if (!root) { const e = new Error('جذر غير معروف'); e.code = 'EROOT'; throw e; }
  return { root, abs: safeJoin(root.dir, rel || '') };
}

const TEXT_EXT = new Set(['.html', '.htm', '.css', '.js', '.mjs', '.json', '.php', '.txt', '.md', '.xml', '.svg',
  '.yml', '.yaml', '.ini', '.conf', '.env', '.sh', '.sql', '.log', '.htaccess', '.ts', '.jsx', '.tsx', '.py']);

function isTextFile(p) {
  const e = path.extname(p).toLowerCase();
  if (TEXT_EXT.has(e)) return true;
  return path.basename(p).startsWith('.') && e === '';
}

function list(rootKey, rel) {
  const { root, abs } = resolve(rootKey, rel);
  const st = fs.statSync(abs);
  if (!st.isDirectory()) { const e = new Error('ليس مجلداً'); e.code = 'ENOTDIR'; throw e; }
  const entries = fs.readdirSync(abs, { withFileTypes: true }).map(d => {
    const full = path.join(abs, d.name);
    let s = null;
    try { s = fs.lstatSync(full); } catch (_) {}
    return {
      name: d.name,
      dir: d.isDirectory(),
      link: d.isSymbolicLink(),
      size: s && s.isFile() ? s.size : 0,
      sizeText: s && s.isFile() ? humanBytes(s.size) : '',
      mtime: s ? s.mtime.toISOString() : null,
      mode: s ? (s.mode & 0o777).toString(8).padStart(3, '0') : null,
      text: isTextFile(d.name),
    };
  }).sort((a, b) => (b.dir - a.dir) || a.name.localeCompare(b.name, 'ar'));

  return {
    root: rootKey,
    rootLabel: root.label,
    rootDir: root.dir,
    path: path.relative(root.dir, abs) || '',
    abs,
    parent: abs === root.dir ? null : path.relative(root.dir, path.dirname(abs)) || '',
    entries,
  };
}

const MAX_EDIT = 3 * 1024 * 1024;

function read(rootKey, rel) {
  const { abs } = resolve(rootKey, rel);
  const st = fs.statSync(abs);
  if (st.size > MAX_EDIT) { const e = new Error('الملف أكبر من 3MB — حمّله بدل فتحه'); e.code = 'E2BIG'; throw e; }
  return { content: fs.readFileSync(abs, 'utf8'), size: st.size, abs };
}

function write(rootKey, rel, content) {
  const { abs } = resolve(rootKey, rel);
  fs.mkdirSync(path.dirname(abs), { recursive: true });
  fs.writeFileSync(abs, String(content));
  return { ok: true, size: Buffer.byteLength(String(content)) };
}

function mkdir(rootKey, rel) {
  const { abs } = resolve(rootKey, rel);
  fs.mkdirSync(abs, { recursive: true });
  return { ok: true };
}

function remove(rootKey, rel) {
  const { root, abs } = resolve(rootKey, rel);
  if (abs === root.dir) return { ok: false, error: 'لا يمكن حذف المجلد الجذر' };
  fs.rmSync(abs, { recursive: true, force: true });
  return { ok: true };
}

function rename(rootKey, rel, newRel) {
  const { abs } = resolve(rootKey, rel);
  const { abs: dst } = resolve(rootKey, newRel);
  fs.mkdirSync(path.dirname(dst), { recursive: true });
  fs.renameSync(abs, dst);
  return { ok: true };
}

function chmod(rootKey, rel, mode) {
  const { abs } = resolve(rootKey, rel);
  const m = parseInt(String(mode), 8);
  if (!Number.isInteger(m) || m < 0 || m > 0o777) return { ok: false, error: 'صلاحيات غير صالحة' };
  fs.chmodSync(abs, m);
  return { ok: true };
}

async function extract(rootKey, rel) {
  const { abs } = resolve(rootKey, rel);
  const dir = path.dirname(abs);
  const lower = abs.toLowerCase();
  let r;
  if (lower.endsWith('.zip')) r = await run('unzip', ['-o', abs, '-d', dir], { timeout: 300000 });
  else if (/\.(tar\.gz|tgz)$/.test(lower)) r = await run('tar', ['-xzf', abs, '-C', dir], { timeout: 300000 });
  else if (lower.endsWith('.tar')) r = await run('tar', ['-xf', abs, '-C', dir], { timeout: 300000 });
  else return { ok: false, error: 'صيغة غير مدعومة (zip / tar.gz / tar فقط)' };
  return r.ok ? { ok: true, into: dir } : { ok: false, error: (r.stderr || r.stdout).slice(-600) };
}

async function compress(rootKey, rel, outName) {
  const { root, abs } = resolve(rootKey, rel);
  const base = path.basename(abs);
  const out = path.join(path.dirname(abs), (outName || base) + '.tar.gz');
  const r = await run('tar', ['-czf', out, '-C', path.dirname(abs), base], { timeout: 600000 });
  return r.ok ? { ok: true, file: path.relative(root.dir, out) } : { ok: false, error: (r.stderr || '').slice(-600) };
}

/** اسم ملف آمن: بلا مسارات وبلا محارف تحكم */
function sanitizeName(name) {
  const base = path.basename(String(name || ''));
  const clean = base.replace(/[\u0000-\u001f]/g, '').replace(/[\\/]/g, '').trim();
  return clean || 'upload.bin';
}

function saveUpload(rootKey, rel, filename, buffer) {
  const safeName = sanitizeName(filename);
  const { abs } = resolve(rootKey, path.join(rel || '', safeName));
  fs.mkdirSync(path.dirname(abs), { recursive: true });
  fs.writeFileSync(abs, buffer);
  return { ok: true, name: safeName, size: buffer.length };
}

module.exports = {
  ROOTS, resolve, list, read, write, mkdir, remove, rename, chmod,
  extract, compress, saveUpload, isTextFile, sanitizeName,
};
