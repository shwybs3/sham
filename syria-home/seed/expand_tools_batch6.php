<?php
/** Long-form expansion for tool full_description fields. See includes/content_expansion.php. */
function expand_tools_batch6(PDO $pdo): void {

expand_tool($pdo, 'regex-tester-debugger', <<<HTML
<p>Regular expressions are one of the most powerful tools in a developer's toolkit for matching and manipulating text — and also one of the most notoriously difficult to get right on the first try, or even the fifth. This tester lets you write a pattern, test it live against sample text, and see exactly what matches, with each capture group highlighted, entirely in your browser.</p>

<h2>Why regex debugging is uniquely painful without a live tester</h2>
<p>A regular expression is a dense, symbolic pattern where a single misplaced character — a forgotten escape, a greedy quantifier where a lazy one was needed — can silently produce completely wrong matches without throwing any obvious error. Writing and testing a pattern blind, directly inside application code, means a slow cycle of guess, run, check output, and repeat. A live tester collapses that cycle down to instant visual feedback as you type.</p>

<h2>How to use it</h2>
<ol>
<li>Write your regular expression pattern, including any flags (like case-insensitive or global matching).</li>
<li>Paste sample text to test it against.</li>
<li>Matches highlight instantly, with individual capture groups broken out separately.</li>
</ol>

<h2>Common regex building blocks worth knowing</h2>
<ul>
<li><strong>Character classes</strong> — like <code>\d</code> for any digit or <code>\w</code> for any word character, matching a category of characters rather than one specific one.</li>
<li><strong>Quantifiers</strong> — like <code>*</code>, <code>+</code>, and <code>{n,m}</code>, controlling how many times the preceding element can repeat.</li>
<li><strong>Anchors</strong> — like <code>^</code> and <code>$</code>, pinning a match to the start or end of a line or string.</li>
<li><strong>Capture groups</strong> — parentheses that both group part of a pattern together and extract that specific portion of a match separately.</li>
<li><strong>Greedy versus lazy matching</strong> — a frequent source of unexpected results, where a quantifier matches as much as possible by default unless explicitly marked lazy with a trailing <code>?</code>.</li>
</ul>

<h2>Common real-world uses</h2>
<p>Validating form input like email addresses or phone numbers, extracting specific data from log files or text dumps, performing a search-and-replace across code with a pattern rather than a fixed string, and parsing structured text that doesn't have a dedicated parser available all rely on regular expressions — and nearly all of them benefit from testing the pattern against realistic sample data before dropping it into production code.</p>

<h2>Frequently asked questions</h2>
<p><strong>Why does my pattern match more text than I expected?</strong> Almost always a greedy quantifier matching further than intended — try a lazy quantifier, or make the pattern more specific about where it should stop.</p>
<p><strong>What's the difference between a regex flavor's "global" and default matching?</strong> Without a global flag, most regex engines stop after the first match; the global flag finds every match in the input rather than just the first one.</p>
<p><strong>Is my test text sent anywhere?</strong> No — pattern testing happens entirely locally in your browser's own regex engine.</p>
<p><strong>Do all programming languages use the exact same regex syntax?</strong> Mostly, but not entirely — there are real syntax differences between languages, so always verify a pattern against the specific regex flavor your target language actually uses.</p>
HTML
);

expand_tool($pdo, 'text-diff-checker', <<<HTML
<p>Spotting the difference between two versions of a document, a block of code, or a piece of text by reading them side by side is slow and genuinely unreliable — the human eye is bad at catching a single changed word buried in an otherwise identical paragraph. This tool compares two blocks of text and highlights every addition, deletion, and change instantly, line by line, entirely in your browser.</p>

<h2>Why manual comparison fails at real-world text lengths</h2>
<p>Beyond a sentence or two, manually comparing two versions of anything — a contract redline, a config file, a paragraph of copy — becomes genuinely error-prone; small changes hide easily in long, similar-looking blocks of text, and a reviewer's attention naturally drifts across repetitive content. An automated diff removes the guesswork entirely, mechanically comparing every character and clearly marking exactly what changed and where.</p>

<h2>How to use it</h2>
<ol>
<li>Paste the original text into one field and the revised version into the other.</li>
<li>The tool instantly highlights every difference — additions in one color, deletions in another.</li>
<li>Review the changes line by line, or word by word for more granular comparison.</li>
</ol>

<h2>Common real-world uses</h2>
<ul>
<li><strong>Reviewing document edits</strong> — comparing an original draft against a revised version to see exactly what an editor or collaborator changed.</li>
<li><strong>Code review and debugging</strong> — comparing two versions of a code snippet or configuration file to spot exactly what changed between them.</li>
<li><strong>Contract and legal document comparison</strong> — catching subtle wording changes between contract drafts that could carry real legal significance.</li>
<li><strong>Content and copy revisions</strong> — verifying that only intended changes were made to published copy, and nothing else was accidentally altered.</li>
</ul>

<h2>Line-level versus word-level comparison</h2>
<p>A line-level diff is faster to scan for structural changes — an added or removed paragraph, a reordered section — while a word-level or character-level diff catches smaller, more subtle changes within a line that a line-level view would only flag as "this whole line changed" without showing exactly what changed inside it. For catching a single altered word or number buried in an otherwise unchanged paragraph, the more granular view matters significantly.</p>

<h2>Frequently asked questions</h2>
<p><strong>Does this work for comparing code, not just prose?</strong> Yes — the underlying comparison works on any text, including source code, configuration files, and structured data.</p>
<p><strong>Is my text uploaded anywhere?</strong> No — the comparison runs entirely locally in your browser.</p>
<p><strong>Can it compare more than two versions at once?</strong> This tool is built for direct two-way comparison, the most common real-world need; for multi-version history, a version control system's diff view is generally the better tool.</p>
<p><strong>Does whitespace count as a difference?</strong> Depending on the comparison mode, purely whitespace-level differences (like trailing spaces) can be flagged or ignored — useful to check when a diff shows unexpected results.</p>
HTML
);

expand_tool($pdo, 'jwt-decoder', <<<HTML
<p>JSON Web Tokens (JWTs) are the standard way modern web applications pass authentication and authorization information between a client and a server, and they're everywhere in modern development — but their compact, encoded form makes them unreadable at a glance. This decoder instantly breaks a JWT down into its header, payload, and signature, showing exactly what claims and data it actually contains, entirely in your browser.</p>

<h2>What's actually inside a JWT</h2>
<p>A JWT consists of three parts separated by periods: a header describing the token's type and signing algorithm, a payload containing the actual claims (like a user ID, expiration time, and any custom data the issuer chose to include), and a signature that verifies the token hasn't been tampered with. The header and payload are simply Base64-encoded JSON — not encrypted — meaning anyone can decode and read them; only the signature requires a secret key to verify or forge.</p>

<h2>How to use it</h2>
<ol>
<li>Paste a JWT into the input field.</li>
<li>The tool instantly decodes and displays the header and payload as readable JSON.</li>
<li>Review the claims — expiration time, issuer, subject, and any custom fields — to debug or inspect the token's contents.</li>
</ol>

<h2>A critical security point about JWTs</h2>
<p>Because the header and payload are only encoded, not encrypted, a JWT should never be used to store sensitive information that shouldn't be readable by anyone who gets hold of the token — assume anyone with the token can read every claim inside it. The signature's job is verifying the token's authenticity and integrity (that it was issued by a trusted source and hasn't been altered), not keeping its contents secret.</p>

<h2>Common debugging scenarios</h2>
<ul>
<li><strong>Checking token expiration</strong> — decoding a JWT to see its exact expiration timestamp when debugging an unexpected "session expired" error.</li>
<li><strong>Verifying claims during development</strong> — confirming a token actually contains the user ID, roles, or permissions your application code expects it to contain.</li>
<li><strong>Debugging authentication integration issues</strong> — inspecting a token from a third-party identity provider to understand exactly what claims format it uses.</li>
<li><strong>Learning how JWTs work</strong> — decoding real tokens is one of the fastest ways to understand the format concretely rather than only reading about it abstractly.</li>
</ul>

<h2>Frequently asked questions</h2>
<p><strong>Can this tool verify a JWT's signature?</strong> Decoding shows the token's contents without requiring the secret key; verifying the signature specifically requires the issuer's secret or public key, which this tool doesn't need or request.</p>
<p><strong>Is my token uploaded anywhere?</strong> No — decoding happens entirely locally in your browser; nothing is sent to a server, which matters given how sensitive a live authentication token can be.</p>
<p><strong>Should I decode a real production token from a live application?</strong> Be cautious — treat any real authentication token as sensitive, and prefer testing with expired or clearly non-production tokens where possible.</p>
<p><strong>Why can I read the payload without any key?</strong> Because the payload is Base64-encoded, not encrypted — encoding is fully reversible by design and requires no secret to decode.</p>
HTML
);

expand_tool($pdo, 'sql-formatter', <<<HTML
<p>A SQL query generated by an application, exported from a tool, or pasted from a log file is very often a single dense, unformatted line — readable to the database engine but nearly impossible for a human to scan and understand at a glance. This formatter instantly reformats SQL into clean, properly indented, readable structure, entirely in your browser.</p>

<h2>Why formatted SQL matters for actually understanding a query</h2>
<p>A complex query with multiple joins, subqueries, and conditions is dramatically easier to read, debug, and modify once it's properly indented — each clause (SELECT, FROM, WHERE, JOIN, GROUP BY) on its own visual level, with nested subqueries clearly indented inward. Reading the same query as a single unformatted line means holding far more of its structure in your head at once, which is both slower and more error-prone, especially under time pressure while debugging a production issue.</p>

<h2>How to use it</h2>
<ol>
<li>Paste your SQL query, formatted or not, into the input.</li>
<li>The tool instantly reformats it with consistent indentation and clause structure.</li>
<li>Copy the formatted result for documentation, code review, or your own reference.</li>
</ol>

<h2>What proper formatting makes easier to spot</h2>
<ul>
<li><strong>Missing or misplaced JOIN conditions</strong> — far easier to notice when each JOIN clause sits on its own clearly indented line.</li>
<li><strong>Deeply nested subqueries</strong> — proper indentation makes the actual nesting level immediately visible, rather than buried in a dense single line.</li>
<li><strong>Overly broad WHERE clauses</strong> — formatted conditions, each on their own line, make it easier to spot a missing condition or an unintended OR where an AND was meant.</li>
<li><strong>Inconsistent style across a codebase</strong> — running every query through the same formatter keeps a team's SQL visually consistent regardless of who originally wrote each one.</li>
</ul>

<h2>Common situations where this saves real debugging time</h2>
<p>Reviewing a slow query pulled from a database's slow-query log, understanding a query generated by an ORM or reporting tool before optimizing it, formatting SQL for documentation or a code review, and simply making a colleague's dense, unformatted query readable before trying to understand what it actually does are all situations where a fast, reliable formatter saves real time over manually reformatting by hand.</p>

<h2>Frequently asked questions</h2>
<p><strong>Does formatting change what a query actually does?</strong> No — formatting only changes whitespace and line breaks; the query's logic and behavior against the database are completely unchanged.</p>
<p><strong>Does this work across different SQL dialects (MySQL, PostgreSQL, etc.)?</strong> Core SQL syntax formats consistently across dialects; dialect-specific functions and extensions are preserved exactly as written.</p>
<p><strong>Is my query uploaded anywhere?</strong> No — formatting happens entirely locally in your browser. This matters since queries can contain sensitive table or column names.</p>
<p><strong>Can I customize the indentation style?</strong> The tool applies a consistent, widely-used formatting convention designed to be readable by default without requiring configuration.</p>
HTML
);

expand_tool($pdo, 'cron-expression-builder', <<<HTML
<p>A cron expression — the compact five-field string that schedules recurring tasks on Unix-based systems — is notoriously easy to get subtly wrong, since the field order and syntax aren't something most people work with often enough to memorize confidently. This builder lets you construct a cron expression visually, and instantly explains in plain language exactly when any given expression will run, entirely in your browser.</p>

<h2>Why cron syntax trips people up</h2>
<p>A standard cron expression packs five fields — minute, hour, day of month, month, and day of week — into a single terse string, and the meaning of special characters like <code>*</code>, <code>,</code>, <code>-</code>, and <code>/</code> within each field isn't always intuitive without regular practice. A single misplaced field or misunderstood special character can schedule a task to run at completely the wrong time, or far more often than intended — a mistake that's easy to make and, without a validator, easy to miss until it causes a real problem in production.</p>

<h2>How to use it</h2>
<ol>
<li>Build your schedule visually, selecting specific minutes, hours, days, months, or intervals.</li>
<li>The tool generates the correct cron expression automatically as you make selections.</li>
<li>See a plain-language explanation of exactly when the resulting expression will trigger, to confirm it matches what you intended.</li>
</ol>

<h2>Common scheduling patterns</h2>
<ul>
<li><strong>Every N minutes or hours</strong> — using the step syntax (like <code>*/15</code> for every 15 minutes) rather than manually listing out every value.</li>
<li><strong>Once daily at a specific time</strong> — the most common pattern for daily backups, reports, or maintenance tasks.</li>
<li><strong>Specific days of the week</strong> — like running only on weekdays, useful for business-hours-only tasks.</li>
<li><strong>Monthly on a specific date</strong> — for tasks like monthly billing or reporting that need to run on a particular day each month.</li>
</ul>

<h2>Where cron expressions are actually used</h2>
<p>Server-side scheduled tasks (backups, cleanup jobs, report generation), CI/CD pipeline scheduling for recurring builds or deployments, and scheduled functions in cloud platforms that use standard cron syntax for their trigger configuration all rely on getting a cron expression exactly right — a mistake here doesn't throw an obvious error, it just silently runs at the wrong time (or not at all), which is exactly why validating an expression before deploying it matters.</p>

<h2>Frequently asked questions</h2>
<p><strong>What does an asterisk mean in a cron field?</strong> It means "every" value for that field — an asterisk in the minute field, for instance, means the task considers every minute as a match for that field.</p>
<p><strong>Do all systems use the exact same cron syntax?</strong> Standard five-field cron syntax is widely consistent, though some systems add a sixth field for seconds or support slightly different special syntax — always verify against your specific system's documentation for edge cases.</p>
<p><strong>Is my schedule data sent anywhere?</strong> No — the expression is built and explained entirely locally in your browser.</p>
<p><strong>Why did my scheduled task run at the wrong time?</strong> Often a timezone mismatch between where the cron expression was written and where the scheduler actually executes it — always confirm which timezone your specific scheduling system uses.</p>
HTML
);

}
