'use strict';
const fs = require('fs');
const path = require('path');
const os = require('os');

const HOME = process.env.HOME || os.homedir();
const HOSTDIR = process.env.SHAMHOST_DIR || path.join(HOME, '.shamhost');

const P = {
  HOSTDIR,
  DATA:    path.join(HOSTDIR, 'data'),
  CONF:    path.join(HOSTDIR, 'conf'),
  VHOSTS:  path.join(HOSTDIR, 'conf', 'vhosts'),
  SITES:   path.join(HOSTDIR, 'sites'),
  LOGS:    path.join(HOSTDIR, 'logs'),
  RUN:     path.join(HOSTDIR, 'run'),
  BACKUPS: path.join(HOSTDIR, 'backups'),
  SSL:     path.join(HOSTDIR, 'ssl'),
  TMP:     path.join(HOSTDIR, 'tmp'),
  PANEL:   path.join(HOSTDIR, 'panel'),
  TUNNELS: path.join(HOSTDIR, 'tunnels'),
  CONFIG:  path.join(HOSTDIR, 'data', 'config.json'),
};

for (const d of [P.DATA, P.CONF, P.VHOSTS, P.SITES, P.LOGS, P.RUN, P.BACKUPS, P.SSL, P.TMP, P.TUNNELS]) {
  try { fs.mkdirSync(d, { recursive: true }); } catch (_) {}
}

const DEFAULTS = {
  version: '1.0.0',
  admin: { username: 'admin', salt: '', hash: '' },
  session_secret: '',
  api_token: '',
  panel: { port: 8088, bind: '0.0.0.0' },
  web: { http_port: 8080, https_port: 8443 },
  db: { admin_user: process.env.USER || 'u0_a0', admin_pass: '', host: '127.0.0.1', port: 3306 },
  namecheap: { api_user: '', api_key: '', username: '', client_ip: '', sandbox: false },
  cloudflare: { token: '' },
  sites: [],
  tunnels: [],
  ddns: [],
};

function deepMerge(base, over) {
  const out = Array.isArray(base) ? base.slice() : Object.assign({}, base);
  for (const k of Object.keys(over || {})) {
    const v = over[k];
    if (v && typeof v === 'object' && !Array.isArray(v) && base && typeof base[k] === 'object' && !Array.isArray(base[k])) {
      out[k] = deepMerge(base[k], v);
    } else if (v !== undefined) {
      out[k] = v;
    }
  }
  return out;
}

function load() {
  let raw = {};
  try { raw = JSON.parse(fs.readFileSync(P.CONFIG, 'utf8')); }
  catch (_) { raw = {}; }
  return deepMerge(DEFAULTS, raw);
}

function save(cfg) {
  const tmp = P.CONFIG + '.tmp';
  fs.writeFileSync(tmp, JSON.stringify(cfg, null, 2), { mode: 0o600 });
  fs.renameSync(tmp, P.CONFIG);
  return cfg;
}

/** قراءة-تعديل-كتابة ذرّية */
function update(fn) {
  const cfg = load();
  const r = fn(cfg);
  save(cfg);
  return r === undefined ? cfg : r;
}

module.exports = { P, load, save, update, deepMerge, DEFAULTS, HOSTDIR, HOME };
