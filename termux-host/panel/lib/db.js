'use strict';
const fs = require('fs');
const path = require('path');
const { P, load } = require('./config');
const { run, isIdent } = require('./util');

function creds() {
  const c = load().db;
  const args = ['-u', c.admin_user, '-h', c.host || '127.0.0.1', '-P', String(c.port || 3306), '--protocol=TCP'];
  const env = {};
  if (c.admin_pass) env.MYSQL_PWD = c.admin_pass;
  return { args, env };
}

function client() {
  for (const bin of ['mariadb', 'mysql']) {
    try { if (require('child_process').execSync('command -v ' + bin + ' 2>/dev/null').toString().trim()) return bin; }
    catch (_) {}
  }
  return 'mysql';
}

function dumpClient() {
  for (const bin of ['mariadb-dump', 'mysqldump']) {
    try { if (require('child_process').execSync('command -v ' + bin + ' 2>/dev/null').toString().trim()) return bin; }
    catch (_) {}
  }
  return 'mysqldump';
}

/** تنفيذ SQL وإرجاع صفوف مُحلّلة من مخرجات TSV */
async function query(sql, opts = {}) {
  const { args, env } = creds();
  const r = await run(client(), args.concat(['--batch', '--raw', '-e', sql].concat(opts.db ? ['-D', opts.db] : [])), { env, timeout: opts.timeout || 30000 });
  if (!r.ok) return { ok: false, error: cleanErr(r.stderr) || 'فشل تنفيذ الاستعلام' };
  const lines = r.stdout.replace(/\n$/, '').split('\n');
  if (!lines.length || lines[0] === '') return { ok: true, columns: [], rows: [] };
  const columns = lines[0].split('\t');
  const rows = lines.slice(1).map(l => l.split('\t'));
  return { ok: true, columns, rows };
}

function cleanErr(s) {
  return String(s || '').split('\n').filter(l => l && !/insecure|Warning/i.test(l)).join(' ').trim();
}

async function ping() {
  const r = await query('SELECT VERSION() AS v');
  return r.ok ? { ok: true, version: (r.rows[0] || [])[0] } : { ok: false, error: r.error };
}

const SYSTEM_DBS = ['information_schema', 'mysql', 'performance_schema', 'sys', 'test'];

async function listDatabases() {
  const r = await query(
    "SELECT s.SCHEMA_NAME AS name, IFNULL(ROUND(SUM(t.data_length+t.index_length)),0) AS bytes, COUNT(t.TABLE_NAME) AS tables " +
    "FROM information_schema.SCHEMATA s LEFT JOIN information_schema.TABLES t ON t.TABLE_SCHEMA=s.SCHEMA_NAME " +
    "GROUP BY s.SCHEMA_NAME ORDER BY s.SCHEMA_NAME");
  if (!r.ok) return r;
  return {
    ok: true,
    databases: r.rows
      .map(x => ({ name: x[0], bytes: parseInt(x[1], 10) || 0, tables: parseInt(x[2], 10) || 0 }))
      .filter(d => !SYSTEM_DBS.includes(d.name)),
  };
}

async function createDatabase(name) {
  if (!isIdent(name, 60)) return { ok: false, error: 'اسم قاعدة بيانات غير صالح (حروف وأرقام و _ فقط)' };
  const r = await query('CREATE DATABASE IF NOT EXISTS `' + name + '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
  return r.ok ? { ok: true, name } : r;
}

async function dropDatabase(name) {
  if (!isIdent(name, 60) || SYSTEM_DBS.includes(name)) return { ok: false, error: 'لا يمكن حذف هذه القاعدة' };
  const r = await query('DROP DATABASE `' + name + '`');
  return r.ok ? { ok: true } : r;
}

async function listUsers() {
  const r = await query("SELECT User, Host FROM mysql.user WHERE User NOT IN ('','mysql','root','mariadb.sys','PUBLIC') ORDER BY User");
  if (!r.ok) return r;
  return { ok: true, users: r.rows.map(x => ({ user: x[0], host: x[1] })) };
}

function q(s) { return "'" + String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'") + "'"; }

async function createUser(name, password) {
  if (!isIdent(name, 32)) return { ok: false, error: 'اسم مستخدم غير صالح' };
  if (!password || String(password).length < 6) return { ok: false, error: 'كلمة المرور قصيرة جداً' };
  const r = await query("CREATE USER IF NOT EXISTS " + q(name) + "@'localhost' IDENTIFIED BY " + q(password) + "; " +
                        "CREATE USER IF NOT EXISTS " + q(name) + "@'127.0.0.1' IDENTIFIED BY " + q(password) + "; FLUSH PRIVILEGES;");
  return r.ok ? { ok: true, user: name } : r;
}

async function setUserPassword(name, password) {
  if (!isIdent(name, 32)) return { ok: false, error: 'اسم مستخدم غير صالح' };
  const r = await query("ALTER USER " + q(name) + "@'localhost' IDENTIFIED BY " + q(password) + "; " +
                        "ALTER USER " + q(name) + "@'127.0.0.1' IDENTIFIED BY " + q(password) + "; FLUSH PRIVILEGES;");
  return r.ok ? { ok: true } : r;
}

async function dropUser(name) {
  if (!isIdent(name, 32)) return { ok: false, error: 'اسم مستخدم غير صالح' };
  const r = await query("DROP USER IF EXISTS " + q(name) + "@'localhost'; DROP USER IF EXISTS " + q(name) + "@'127.0.0.1'; FLUSH PRIVILEGES;");
  return r.ok ? { ok: true } : r;
}

async function grant(user, database, privileges) {
  if (!isIdent(user, 32) || !isIdent(database, 60)) return { ok: false, error: 'اسم غير صالح' };
  const priv = /^[A-Z, ]+$/.test(String(privileges || '')) ? privileges : 'ALL PRIVILEGES';
  const r = await query('GRANT ' + priv + ' ON `' + database + '`.* TO ' + q(user) + "@'localhost'; " +
                        'GRANT ' + priv + ' ON `' + database + '`.* TO ' + q(user) + "@'127.0.0.1'; FLUSH PRIVILEGES;");
  return r.ok ? { ok: true } : r;
}

async function dump(database, outFile) {
  if (!isIdent(database, 60)) return { ok: false, error: 'اسم قاعدة بيانات غير صالح' };
  const { args, env } = creds();
  const r = await run(dumpClient(), args.concat(['--single-transaction', '--quick', '--default-character-set=utf8mb4', database]),
    { env, timeout: 300000, maxBuffer: 200 * 1024 * 1024 });
  if (!r.ok) return { ok: false, error: cleanErr(r.stderr) };
  fs.writeFileSync(outFile, r.stdout);
  return { ok: true, file: outFile, bytes: Buffer.byteLength(r.stdout) };
}

async function importSql(database, sqlText) {
  if (!isIdent(database, 60)) return { ok: false, error: 'اسم قاعدة بيانات غير صالح' };
  const { args, env } = creds();
  const r = await run(client(), args.concat(['-D', database]), { env, input: sqlText, timeout: 600000 });
  return r.ok ? { ok: true } : { ok: false, error: cleanErr(r.stderr) };
}

/** إنشاء قاعدة + مستخدم + صلاحيات دفعة واحدة (يُستخدم عند إنشاء موقع) */
async function provision(base, password) {
  const name = String(base).toLowerCase().replace(/[^a-z0-9]/g, '').slice(0, 24) || ('site' + Date.now().toString(36));
  const dbName = name + '_db';
  const dbUser = (name + '_u').slice(0, 30);
  let r = await createDatabase(dbName); if (!r.ok) return r;
  r = await createUser(dbUser, password); if (!r.ok) return r;
  r = await grant(dbUser, dbName, 'ALL PRIVILEGES'); if (!r.ok) return r;
  return { ok: true, database: dbName, user: dbUser, password };
}

module.exports = {
  query, ping, listDatabases, createDatabase, dropDatabase,
  listUsers, createUser, dropUser, setUserPassword, grant,
  dump, importSql, provision, SYSTEM_DBS,
};
