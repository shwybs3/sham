// Plain CommonJS, runs directly under Node with no build step — used by server.js
// on startup so shared hosting (cPanel Node Selector) without terminal/SSH access
// still gets its database tables created automatically on first boot.
//
// If you DO have terminal access, `npx prisma migrate deploy` works exactly the
// same way and is fully compatible with this bootstrap (it also records applied
// migrations by folder name, so the two approaches never double-apply a migration).

const path = require("node:path");
const fs = require("node:fs");
const Database = require("better-sqlite3");

function resolveDbPath() {
  const url = process.env.DATABASE_URL || "file:./dev.db";
  const filePath = url.replace(/^file:/, "");
  return path.resolve(process.cwd(), filePath);
}

function ensureDatabaseReady() {
  const dbPath = resolveDbPath();
  const isNewDb = !fs.existsSync(dbPath);

  fs.mkdirSync(path.dirname(dbPath), { recursive: true });

  const db = new Database(dbPath);
  db.pragma("journal_mode = WAL");
  db.pragma("foreign_keys = ON");

  db.exec(
    `CREATE TABLE IF NOT EXISTS _app_migrations (name TEXT PRIMARY KEY, applied_at TEXT NOT NULL)`
  );

  const migrationsDir = path.join(__dirname, "..", "prisma", "migrations");
  if (!fs.existsSync(migrationsDir)) {
    db.close();
    return;
  }

  const applied = new Set(
    db.prepare("SELECT name FROM _app_migrations").all().map((r) => r.name)
  );

  const folders = fs
    .readdirSync(migrationsDir)
    .filter((f) => fs.statSync(path.join(migrationsDir, f)).isDirectory())
    .sort();

  for (const folder of folders) {
    if (applied.has(folder)) continue;

    const sqlFile = path.join(migrationsDir, folder, "migration.sql");
    if (!fs.existsSync(sqlFile)) continue;

    const sql = fs.readFileSync(sqlFile, "utf8");
    const run = db.transaction(() => {
      db.exec(sql);
      db.prepare("INSERT INTO _app_migrations (name, applied_at) VALUES (?, ?)").run(
        folder,
        new Date().toISOString()
      );
    });

    try {
      run();
      console.log(`[db] applied migration ${folder}`);
    } catch (err) {
      db.close();
      console.error(`[db] failed to apply migration ${folder}:`, err.message);
      throw err;
    }
  }

  db.close();
  if (isNewDb) console.log(`[db] created new database at ${dbPath}`);
}

module.exports = { ensureDatabaseReady, resolveDbPath };
