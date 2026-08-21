// Custom production server — required for hosts like cPanel's "Setup Node.js App"
// (Phusion Passenger), which need a plain Node.js entry point that listens on the
// PORT they assign, rather than an `npm run` script.
//
// Set this file as the "Application Startup File" in cPanel's Node.js App config.
// For any other Node host (VPS, Railway, etc.) `npm run start:server` works the same way.

const { createServer } = require("node:http");
const next = require("next");
const { ensureDatabaseReady } = require("./scripts/ensure-db");

const port = parseInt(process.env.PORT || "3000", 10);
const hostname = "0.0.0.0";
const dev = process.env.NODE_ENV !== "production";

const app = next({ dev, hostname, port });
const handle = app.getRequestHandler();

ensureDatabaseReady();

app.prepare().then(() => {
  createServer((req, res) => handle(req, res)).listen(port, hostname, () => {
    console.log(`> دفتر الدكان جاهز على http://${hostname}:${port}`);
  });
});
