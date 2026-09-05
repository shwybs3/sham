'use strict';
const { spawn } = require('child_process');
const crypto = require('crypto');
const path = require('path');
const fs = require('fs');

/** تشغيل أمر وإرجاع النتيجة (لا يرمي استثناء عند فشل الأمر) */
function run(cmd, args = [], opts = {}) {
  return new Promise((resolve) => {
    const child = spawn(cmd, args, {
      env: Object.assign({}, process.env, opts.env || {}),
      cwd: opts.cwd,
      stdio: ['pipe', 'pipe', 'pipe'],
    });
    let out = '', err = '', done = false;
    const limit = opts.maxBuffer || 4 * 1024 * 1024;
    const timer = setTimeout(() => {
      if (!done) { try { child.kill('SIGKILL'); } catch (_) {} }
    }, opts.timeout || 120000);

    child.stdout.on('data', d => { if (out.length < limit) out += d; });
    child.stderr.on('data', d => { if (err.length < limit) err += d; });
    child.on('error', (e) => {
      if (done) return; done = true; clearTimeout(timer);
      resolve({ code: 127, stdout: '', stderr: String(e.message), ok: false });
    });
    child.on('close', (code) => {
      if (done) return; done = true; clearTimeout(timer);
      resolve({ code, stdout: out, stderr: err, ok: code === 0 });
    });
    if (opts.input != null) { child.stdin.write(opts.input); }
    child.stdin.end();
  });
}

/** تشغيل سطر أوامر عبر الصدفة */
function sh(command, opts = {}) {
  return run(process.env.SHELL && fs.existsSync(process.env.SHELL) ? process.env.SHELL : 'sh', ['-c', command], opts);
}

function rid(n = 8) { return crypto.randomBytes(n).toString('hex'); }

function slug(s) {
  return String(s || '').toLowerCase().replace(/[^a-z0-9._-]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 63);
}

/** اسم دومين صالح فقط — يمنع الحقن في إعدادات nginx */
function isDomain(d) {
  return typeof d === 'string' &&
    d.length > 0 && d.length < 254 &&
    /^(\*\.)?([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i.test(d);
}

function isIdent(s, max = 48) {
  return typeof s === 'string' && s.length > 0 && s.length <= max && /^[A-Za-z0-9_][A-Za-z0-9_-]*$/.test(s);
}

/** يمنع الخروج من مجلد الجذر (path traversal) */
function safeJoin(root, rel) {
  const target = path.resolve(root, '.' + path.sep + String(rel || '').replace(/^[/\\]+/, ''));
  const normRoot = path.resolve(root);
  if (target !== normRoot && !target.startsWith(normRoot + path.sep)) {
    const e = new Error('مسار خارج النطاق المسموح'); e.code = 'EPATH'; throw e;
  }
  return target;
}

function humanBytes(n) {
  if (!Number.isFinite(n)) return '-';
  const u = ['B', 'KB', 'MB', 'GB', 'TB'];
  let i = 0;
  while (n >= 1024 && i < u.length - 1) { n /= 1024; i++; }
  return (i === 0 ? n : n.toFixed(1)) + ' ' + u[i];
}

/** مقارنة زمنية ثابتة */
function timingSafeEq(a, b) {
  const ba = Buffer.from(String(a)), bb = Buffer.from(String(b));
  if (ba.length !== bb.length) return false;
  return crypto.timingSafeEqual(ba, bb);
}

function hashPassword(pass, salt) {
  return crypto.pbkdf2Sync(pass, salt, 120000, 32, 'sha256').toString('hex');
}

/** استخراج عناصر XML بسيطة (كافٍ لردود Namecheap) */
function xmlAttrs(tag) {
  const attrs = {};
  const re = /([A-Za-z0-9_:.-]+)\s*=\s*"([^"]*)"/g;
  let m;
  while ((m = re.exec(tag))) attrs[m[1]] = decodeXml(m[2]);
  return attrs;
}
function decodeXml(s) {
  return String(s).replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"')
    .replace(/&apos;/g, "'").replace(/&#(\d+);/g, (_, d) => String.fromCharCode(+d)).replace(/&amp;/g, '&');
}
function xmlFindAll(xml, tagName) {
  const re = new RegExp('<' + tagName + '\\b([^>]*)\\/?>', 'gi');
  const out = []; let m;
  while ((m = re.exec(xml))) out.push(xmlAttrs(m[1]));
  return out;
}
function xmlText(xml, tagName) {
  const m = new RegExp('<' + tagName + '\\b[^>]*>([\\s\\S]*?)<\\/' + tagName + '>', 'i').exec(xml);
  return m ? decodeXml(m[1].trim()) : null;
}

/** طلب HTTPS بسيط بدون اعتماديات */
function httpsRequest(url, opts = {}) {
  const https = require('https');
  const http = require('http');
  const u = new URL(url);
  const mod = u.protocol === 'http:' ? http : https;
  return new Promise((resolve, reject) => {
    const req = mod.request({
      hostname: u.hostname,
      port: u.port || (u.protocol === 'http:' ? 80 : 443),
      path: u.pathname + u.search,
      method: opts.method || 'GET',
      headers: opts.headers || {},
      timeout: opts.timeout || 30000,
    }, (res) => {
      let body = '';
      res.setEncoding('utf8');
      res.on('data', d => { if (body.length < 8 * 1024 * 1024) body += d; });
      res.on('end', () => resolve({ status: res.statusCode, headers: res.headers, body }));
    });
    req.on('timeout', () => { req.destroy(new Error('انتهت مهلة الطلب')); });
    req.on('error', reject);
    if (opts.body) req.write(opts.body);
    req.end();
  });
}

module.exports = {
  run, sh, rid, slug, isDomain, isIdent, safeJoin, humanBytes,
  timingSafeEq, hashPassword, xmlFindAll, xmlText, xmlAttrs, decodeXml, httpsRequest,
};
