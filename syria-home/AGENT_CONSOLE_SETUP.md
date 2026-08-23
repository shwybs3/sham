# Agent Console setup (one-time, over SSH)

`admin/pages/agent-console.php` (Admin → Agent Console) lets you trigger the
real Claude Code CLI from a web form instead of an SSH terminal, and it
survives after you close the browser tab or the triggering request ends —
unlike a bare `claude -p "..."` typed straight into an SSH session, which
cPanel's shared-hosting process limiter (CloudLinux/LVE) kills the moment
the SSH connection drops or the account's memory/CPU cap is hit. That's what
"Killed" in your terminal screenshot was.

This part — installing the CLI itself — needs to happen once over SSH,
since I don't have credentials to your hosting account. Everything after
that runs from the browser.

## 1. Install Node.js in your account (no root needed)

```bash
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
source ~/.nvm/nvm.sh
nvm install 20
node -v      # confirm it printed a version
```

If your host's cPanel has **Setup Node.js App**, you can use that instead —
either way you just need a `node`/`npm` on the account.

## 2. Install Claude Code

```bash
npm install -g @anthropic-ai/claude-code
which claude
```

Copy the path `which claude` prints (something like
`/home/yourcpaneluser/.nvm/versions/node/v20.x/bin/claude`) — you'll paste
it into the admin panel in step 4.

## 3. Get an Anthropic API key

Headless/unattended runs authenticate with an API key, not the interactive
browser login `claude` normally uses (there's no browser to complete that
flow on a server). Create one at https://console.anthropic.com/ →
API Keys. You'll paste this into the admin panel too — it's stored in the
`settings` table, not in a file, and it's only read at the moment a run
launches.

## 4. Configure the admin panel

Go to **Admin → Agent Console** and fill in:
- **Claude Code CLI path** — the output of `which claude` from step 2
- **Working directory** — defaults to this site's root; change it if you
  want the agent operating somewhere else (e.g. a different subsite's
  document root)
- **Anthropic API key** — from step 3

## 5. Run a task

Type what you want done in the "New task" box and click **Run now**. The
page will show live output (polled every ~2.5s) until the run finishes.
Every run's full log is kept under `agent_runs/<run-id>/output.log` on the
server (not in git — that folder is `.gitignore`d and has its own
`Require all denied` so it can't be browsed).

## What it actually does under the hood

`agent_launch()` in `includes/agent_console.php` runs:

```bash
cd <working-dir> && setsid env ANTHROPIC_API_KEY=<key> <cli-path> \
  -p "<your task>" --permission-mode bypassPermissions \
  --output-format text > output.log 2>&1 < /dev/null; touch done &
```

`setsid` plus full I/O redirection detaches the process from the PHP-FPM/
Apache worker that launched it, so it keeps running after that HTTP request
returns — the same problem tmux solves for an interactive SSH session
(see `SERVER_BRIEF.md` §0), just for a request-triggered run instead.

`--permission-mode bypassPermissions` is what makes it unattended: Claude
Code normally pauses to ask before an edit or a shell command; this flag
skips every one of those prompts. There's no confirmation step and no undo.
Whoever can reach this admin page can have it touch anything the PHP
process's user can — treat admin access here as equivalent to SSH access to
the account.

## If a run doesn't produce output

- Wrong CLI path, or `claude` not executable by the web server's user
  (check file permissions — shared hosting sometimes runs PHP as a
  different user than your SSH login; `chmod +x` the binary if needed).
- Missing/invalid API key.
- `setsid` not available (rare, but some minimal shared-hosting shells lack
  it) — check the run's `output.log` for `setsid: command not found` and,
  if so, ask to switch the launcher to `nohup ... &` instead, which is
  available almost everywhere but slightly less robust against the parent
  shell exiting immediately.
- The account's resource cap was hit — check `agent_runs/<run-id>/output.log`
  for an OOM-style message, same as the killed terminal session; heavier
  tasks may need a higher hosting tier.
