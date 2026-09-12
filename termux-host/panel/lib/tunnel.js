'use strict';
/**
 * أنفاق Cloudflare — تنشر الموقع على الإنترنت بدون IP ثابت وبدون فتح منافذ الراوتر.
 * وهذا هو الحل العملي للجوال، لأن شبكات الجيل الرابع/الخامس خلف CGNAT.
 *
 * نوعان:
 *   quick  — رابط مؤقت *.trycloudflare.com، بدون حساب، للاختبار الفوري.
 *   named  — نفق دائم مربوط بدومينك، يحتاج رمز Tunnel Token من Cloudflare Zero Trust.
 */
const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');
const { P, load, update } = require('./config');
const { run, rid } = require('./util');

function pidFile(id) { return path.join(P.RUN, 'tunnel-' + id + '.pid'); }
function logFile(id) { return path.join(P.LOGS, 'tunnel-' + id + '.log'); }

async function installed() {
  const r = await run('sh', ['-c', 'command -v cloudflared']);
  return r.ok && r.stdout.trim() ? r.stdout.trim() : null;
}

function alive(id) {
  try {
    const pid = parseInt(fs.readFileSync(pidFile(id), 'utf8').trim(), 10);
    if (!pid) return false;
    process.kill(pid, 0);
    return true;
  } catch (_) { return false; }
}

function readUrl(id) {
  try {
    const txt = fs.readFileSync(logFile(id), 'utf8');
    const m = txt.match(/https:\/\/[a-z0-9-]+\.trycloudflare\.com/i);
    return m ? m[0] : null;
  } catch (_) { return null; }
}

function list() {
  const cfg = load();
  return (cfg.tunnels || []).map(t => Object.assign({}, t, {
    running: alive(t.id),
    url: t.type === 'quick' ? (readUrl(t.id) || t.url || null) : t.url,
  }));
}

async function start(input) {
  const bin = await installed();
  if (!bin) return { ok: false, error: 'cloudflared غير مثبّت. نفّذ: pkg install cloudflared' };

  const cfg = load();
  const type = input.type === 'named' ? 'named' : 'quick';
  const id = 'tn' + rid(4);
  const localPort = parseInt(input.port, 10) || (cfg.web.http_port || 8080);

  let args;
  if (type === 'named') {
    const token = String(input.token || '').trim();
    if (!token) return { ok: false, error: 'مطلوب Tunnel Token من لوحة Cloudflare Zero Trust.' };
    args = ['tunnel', '--no-autoupdate', 'run', '--token', token];
  } else {
    args = ['tunnel', '--no-autoupdate', '--url', 'http://127.0.0.1:' + localPort];
  }

  const out = fs.openSync(logFile(id), 'a');
  const child = spawn(bin, args, { detached: true, stdio: ['ignore', out, out] });
  child.unref();
  fs.writeFileSync(pidFile(id), String(child.pid));

  const rec = {
    id, type,
    port: localPort,
    site: input.site || null,
    hostname: input.hostname || null,
    url: null,
    started_at: new Date().toISOString(),
  };

  if (type === 'quick') {
    for (let i = 0; i < 30; i++) {
      await new Promise(r => setTimeout(r, 1000));
      const u = readUrl(id);
      if (u) { rec.url = u; break; }
      if (!alive(id)) {
        const log = fs.existsSync(logFile(id)) ? fs.readFileSync(logFile(id), 'utf8').slice(-800) : '';
        return { ok: false, error: 'توقف النفق فوراً. آخر السجل:\n' + log };
      }
    }
    if (!rec.url) return { ok: false, error: 'لم يُصدر Cloudflare رابطاً خلال 30 ثانية. راجع سجل النفق.' };
  } else {
    await new Promise(r => setTimeout(r, 3000));
    if (!alive(id)) {
      const log = fs.existsSync(logFile(id)) ? fs.readFileSync(logFile(id), 'utf8').slice(-800) : '';
      return { ok: false, error: 'فشل تشغيل النفق. آخر السجل:\n' + log };
    }
    rec.url = input.hostname ? 'https://' + input.hostname : null;
  }

  update(c => { c.tunnels = (c.tunnels || []).filter(t => t.id !== id).concat([rec]); });
  return { ok: true, tunnel: rec };
}

function stop(id) {
  const cfg = load();
  const t = (cfg.tunnels || []).find(x => x.id === id);
  if (!t) return { ok: false, error: 'النفق غير موجود' };
  try {
    const pid = parseInt(fs.readFileSync(pidFile(id), 'utf8').trim(), 10);
    if (pid) { try { process.kill(pid, 'SIGTERM'); } catch (_) {} }
  } catch (_) {}
  try { fs.unlinkSync(pidFile(id)); } catch (_) {}
  update(c => { c.tunnels = (c.tunnels || []).filter(x => x.id !== id); });
  return { ok: true };
}

function logs(id, lines) {
  try {
    const txt = fs.readFileSync(logFile(id), 'utf8');
    return txt.split('\n').slice(-(lines || 120)).join('\n');
  } catch (_) { return ''; }
}

/** تسجيل الدخول لحساب Cloudflare (يطبع رابطاً يفتحه المستخدم في المتصفح) */
async function login() {
  const bin = await installed();
  if (!bin) return { ok: false, error: 'cloudflared غير مثبّت' };
  const r = await run(bin, ['tunnel', 'login'], { timeout: 15000 });
  const m = (r.stdout + r.stderr).match(/https:\/\/\S+/);
  return { ok: true, url: m ? m[0] : null, output: r.stdout + r.stderr };
}

module.exports = { installed, list, start, stop, logs, login, alive };
