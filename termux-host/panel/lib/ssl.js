'use strict';
/**
 * شهادات SSL.
 *  - letsencrypt عبر تحدي DNS من Namecheap: يعمل حتى بدون فتح المنفذ 80،
 *    وهو الخيار الوحيد العملي على الجوال لأن المنافذ <1024 تحتاج روت.
 *  - self-signed: للاختبار المحلي فقط، المتصفح سيحذّر منها.
 *  - عند استخدام Cloudflare Tunnel فالتشفير يتم عند حافة Cloudflare ولا تحتاج شهادة محلية.
 */
const fs = require('fs');
const path = require('path');
const { P, load } = require('./config');
const { run, isDomain } = require('./util');

const ACME = path.join(process.env.HOME || '', '.acme.sh', 'acme.sh');

function acmeInstalled() { return fs.existsSync(ACME); }

function certDir(domain) { return path.join(P.SSL, domain); }

function info(domain) {
  const d = certDir(domain);
  const full = path.join(d, 'fullchain.pem');
  if (!fs.existsSync(full)) return null;
  return { domain, path: d, installed_at: fs.statSync(full).mtime.toISOString() };
}

function list() {
  let out = [];
  try {
    for (const name of fs.readdirSync(P.SSL)) {
      const i = info(name);
      if (i) out.push(i);
    }
  } catch (_) {}
  return out;
}

async function expiry(domain) {
  const full = path.join(certDir(domain), 'fullchain.pem');
  if (!fs.existsSync(full)) return null;
  const r = await run('openssl', ['x509', '-enddate', '-noout', '-in', full], { timeout: 10000 });
  const m = /notAfter=(.+)/.exec(r.stdout || '');
  return m ? m[1].trim() : null;
}

async function installAcme() {
  if (acmeInstalled()) return { ok: true, already: true };
  const r = await run('sh', ['-c',
    'curl -fsSL https://get.acme.sh -o "$HOME/.acme-install.sh" && sh "$HOME/.acme-install.sh" --home "$HOME/.acme.sh" && rm -f "$HOME/.acme-install.sh"'],
    { timeout: 180000 });
  return r.ok ? { ok: true } : { ok: false, error: (r.stderr || r.stdout || '').slice(-800) };
}

/** إصدار شهادة Let's Encrypt عبر DNS-01 مع Namecheap */
async function issueLetsEncrypt(domain, opts = {}) {
  if (!isDomain(domain)) return { ok: false, error: 'دومين غير صالح' };
  if (!acmeInstalled()) {
    const inst = await installAcme();
    if (!inst.ok) return { ok: false, error: 'acme.sh غير مثبّت: ' + inst.error };
  }
  const cfg = load();
  const nc = cfg.namecheap || {};
  if (!nc.api_key || !nc.username) {
    return { ok: false, error: 'مطلوب مفتاح Namecheap API لإتمام تحدي DNS. أدخله في الإعدادات أولاً.' };
  }
  const env = {
    NAMECHEAP_API_KEY: nc.api_key,
    NAMECHEAP_USERNAME: nc.username,
    NAMECHEAP_SOURCEIP: nc.client_ip || '',
  };

  const args = ['--issue', '--dns', 'dns_namecheap', '-d', domain];
  if (opts.wildcard) args.push('-d', '*.' + domain);
  else if (opts.www !== false) args.push('-d', 'www.' + domain);
  args.push('--server', 'letsencrypt', '--dnssleep', String(opts.dnssleep || 120));
  if (opts.force) args.push('--force');

  const issued = await run(ACME, args, { env, timeout: 600000 });
  const log = (issued.stdout + '\n' + issued.stderr).slice(-3000);
  if (!issued.ok && !/Cert success|already.*next renewal/i.test(log)) {
    return { ok: false, error: 'فشل إصدار الشهادة', log };
  }

  const d = certDir(domain);
  fs.mkdirSync(d, { recursive: true });
  const inst = await run(ACME, [
    '--install-cert', '-d', domain,
    '--key-file', path.join(d, 'privkey.pem'),
    '--fullchain-file', path.join(d, 'fullchain.pem'),
    '--reloadcmd', 'shamhost reload',
  ], { env, timeout: 120000 });

  if (!fs.existsSync(path.join(d, 'fullchain.pem'))) {
    return { ok: false, error: 'لم تُثبَّت الشهادة', log: (inst.stdout + inst.stderr).slice(-2000) };
  }
  return { ok: true, domain, dir: d, log };
}

/** شهادة موقّعة ذاتياً — للتجارب على الشبكة المحلية */
async function issueSelfSigned(domain) {
  if (!isDomain(domain) && domain !== 'localhost') return { ok: false, error: 'دومين غير صالح' };
  const d = certDir(domain);
  fs.mkdirSync(d, { recursive: true });
  const r = await run('openssl', [
    'req', '-x509', '-newkey', 'rsa:2048', '-nodes', '-days', '825',
    '-keyout', path.join(d, 'privkey.pem'),
    '-out', path.join(d, 'fullchain.pem'),
    '-subj', '/CN=' + domain,
    '-addext', 'subjectAltName=DNS:' + domain + ',DNS:www.' + domain,
  ], { timeout: 60000 });
  if (!r.ok) return { ok: false, error: (r.stderr || '').slice(-600) };
  return { ok: true, domain, dir: d, selfSigned: true };
}

async function renewAll() {
  if (!acmeInstalled()) return { ok: false, error: 'acme.sh غير مثبّت' };
  const cfg = load();
  const nc = cfg.namecheap || {};
  const r = await run(ACME, ['--cron'], {
    env: {
      NAMECHEAP_API_KEY: nc.api_key || '',
      NAMECHEAP_USERNAME: nc.username || '',
      NAMECHEAP_SOURCEIP: nc.client_ip || '',
    },
    timeout: 900000,
  });
  return { ok: true, log: (r.stdout + r.stderr).slice(-3000) };
}

function remove(domain) {
  const d = certDir(domain);
  if (!d.startsWith(P.SSL)) return { ok: false, error: 'مسار غير صالح' };
  try { fs.rmSync(d, { recursive: true, force: true }); } catch (_) {}
  return { ok: true };
}

module.exports = { list, info, expiry, issueLetsEncrypt, issueSelfSigned, renewAll, remove, acmeInstalled, installAcme, certDir };
