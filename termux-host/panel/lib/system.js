'use strict';
const fs = require('fs');
const os = require('os');
const path = require('path');
const { P, load } = require('./config');
const { run, sh, humanBytes } = require('./util');

const SERVICES = ['mariadb', 'php-fpm', 'nginx', 'crond', 'panel'];

function pidAlive(pid) {
  try { process.kill(pid, 0); return true; } catch (_) { return false; }
}

function serviceState(name) {
  if (name === 'mariadb') {
    try {
      const out = require('child_process').execSync('pgrep -f "mariadbd|mysqld" 2>/dev/null || true').toString().trim();
      return { name, running: !!out, pid: out ? parseInt(out.split('\n')[0], 10) : null };
    } catch (_) { return { name, running: false, pid: null }; }
  }
  const f = path.join(P.RUN, name + '.pid');
  try {
    const pid = parseInt(fs.readFileSync(f, 'utf8').trim(), 10);
    return { name, running: !!pid && pidAlive(pid), pid: pid || null };
  } catch (_) { return { name, running: false, pid: null }; }
}

function services() { return SERVICES.map(serviceState); }

async function control(name, action) {
  if (!SERVICES.includes(name)) return { ok: false, error: 'خدمة غير معروفة' };
  if (!['start', 'stop', 'restart'].includes(action)) return { ok: false, error: 'إجراء غير معروف' };
  if (name === 'panel' && action !== 'restart') return { ok: false, error: 'لا يمكن إيقاف اللوحة من داخل اللوحة' };
  const r = await run('shamhost', ['service', name, action], { timeout: 60000 });
  return { ok: r.ok, output: (r.stdout + r.stderr).slice(-2000) };
}

async function battery() {
  const r = await run('termux-battery-status', [], { timeout: 8000 });
  if (!r.ok) return null;
  try { return JSON.parse(r.stdout); } catch (_) { return null; }
}

async function disk() {
  const r = await run('df', ['-Pk', process.env.HOME || os.homedir()], { timeout: 8000 });
  const line = (r.stdout || '').split('\n')[1] || '';
  const p = line.split(/\s+/);
  if (p.length < 5) return null;
  const total = parseInt(p[1], 10) * 1024, used = parseInt(p[2], 10) * 1024, avail = parseInt(p[3], 10) * 1024;
  return { total, used, avail, percent: parseInt(p[4], 10) || 0, totalText: humanBytes(total), usedText: humanBytes(used), availText: humanBytes(avail) };
}

async function versions() {
  const get = async (cmd) => { const r = await sh(cmd, { timeout: 8000 }); return r.ok ? r.stdout.trim().split('\n')[0] : null; };
  const [php, nginxV, mysql, cf] = await Promise.all([
    get('php -r "echo PHP_VERSION;" 2>/dev/null'),
    get('nginx -v 2>&1 | sed "s|nginx version: ||"'),
    get('mysql --version 2>/dev/null | head -1'),
    get('cloudflared --version 2>/dev/null | head -1'),
  ]);
  return { node: process.version, php, nginx: nginxV, mysql, cloudflared: cf };
}

function lanIp() {
  const ifs = os.networkInterfaces();
  for (const name of Object.keys(ifs)) {
    for (const i of ifs[name] || []) {
      if (i.family === 'IPv4' && !i.internal) return i.address;
    }
  }
  return '127.0.0.1';
}

async function summary() {
  const cfg = load();
  const mem = { total: os.totalmem(), free: os.freemem() };
  const [bat, dsk, ver] = await Promise.all([battery(), disk(), versions()]);
  return {
    hostname: os.hostname(),
    uptime: os.uptime(),
    panelUptime: process.uptime(),
    load: os.loadavg(),
    cpus: os.cpus().length,
    arch: os.arch(),
    memory: {
      total: mem.total, free: mem.free, used: mem.total - mem.free,
      totalText: humanBytes(mem.total), usedText: humanBytes(mem.total - mem.free),
      percent: Math.round(((mem.total - mem.free) / mem.total) * 100),
    },
    disk: dsk,
    battery: bat,
    versions: ver,
    lanIp: lanIp(),
    ports: { panel: cfg.panel.port, http: cfg.web.http_port, https: cfg.web.https_port },
    services: services(),
    counts: {
      sites: (cfg.sites || []).length,
      tunnels: (cfg.tunnels || []).length,
      ddns: (cfg.ddns || []).length,
    },
  };
}

const LOG_FILES = {
  panel: 'panel.out',
  'nginx-error': 'nginx-error.log',
  'nginx-access': 'nginx-access.log',
  php: 'php-error.log',
  'php-fpm': 'php-fpm.log',
  mariadb: 'mariadb.out',
  boot: 'boot.log',
};

function readLog(key, lines) {
  const name = LOG_FILES[key];
  if (!name) return { ok: false, error: 'سجل غير معروف' };
  const f = path.join(P.LOGS, name);
  if (!fs.existsSync(f)) return { ok: true, content: '(السجل فارغ)' };
  const stat = fs.statSync(f);
  const size = Math.min(stat.size, 256 * 1024);
  const fd = fs.openSync(f, 'r');
  const buf = Buffer.alloc(size);
  fs.readSync(fd, buf, 0, size, Math.max(0, stat.size - size));
  fs.closeSync(fd);
  const all = buf.toString('utf8').split('\n');
  return { ok: true, content: all.slice(-(parseInt(lines, 10) || 200)).join('\n') };
}

function siteLog(siteId, kind, lines) {
  const f = path.join(P.LOGS, 'site-' + path.basename(String(siteId)) + '-' + (kind === 'error' ? 'error' : 'access') + '.log');
  if (!f.startsWith(P.LOGS)) return { ok: false, error: 'مسار غير صالح' };
  if (!fs.existsSync(f)) return { ok: true, content: '(لا توجد سجلات بعد)' };
  const txt = fs.readFileSync(f, 'utf8');
  return { ok: true, content: txt.split('\n').slice(-(parseInt(lines, 10) || 200)).join('\n') };
}

/** تنفيذ أمر في الصدفة — متاح للمدير فقط، الجهاز جهازه */
async function exec(command, cwd) {
  if (!String(command || '').trim()) return { ok: false, error: 'أمر فارغ' };
  const r = await sh(command, { timeout: 120000, cwd: cwd || process.env.HOME });
  return { ok: true, code: r.code, stdout: r.stdout.slice(-100000), stderr: r.stderr.slice(-20000) };
}

async function logsList() { return Object.keys(LOG_FILES); }

module.exports = { summary, services, serviceState, control, readLog, siteLog, exec, logsList, lanIp, SERVICES };
