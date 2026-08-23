<?php
/** Long-form expansion for tool full_description fields. See includes/content_expansion.php. */
function expand_tools_batch4(PDO $pdo): void {

expand_tool($pdo, 'bmi-calculator', <<<HTML
<p>Body Mass Index is a simple, widely used screening measurement that relates your weight to your height, giving a quick general indication of whether your weight falls within a typical range for your height. This calculator computes it instantly from your height and weight, in either metric or imperial units, and explains what the resulting number actually means.</p>

<h2>How BMI is calculated</h2>
<p>BMI is calculated as weight in kilograms divided by height in meters squared, or equivalently, weight in pounds divided by height in inches squared, multiplied by a conversion constant. The formula deliberately ignores factors like muscle mass, bone density, age, and sex — which is precisely why it's described as a screening tool rather than a diagnostic one; it gives a fast, population-level estimate, not an individual medical assessment.</p>

<h2>How to use it</h2>
<ol>
<li>Enter your height and weight, choosing metric (centimeters and kilograms) or imperial (feet/inches and pounds) units.</li>
<li>Your BMI calculates instantly, along with the standard weight category it falls into.</li>
</ol>

<h2>Understanding the standard BMI categories</h2>
<ul>
<li><strong>Below 18.5</strong> — classified as underweight.</li>
<li><strong>18.5–24.9</strong> — classified as a typical or healthy weight range.</li>
<li><strong>25–29.9</strong> — classified as overweight.</li>
<li><strong>30 and above</strong> — classified as obese, with further sub-categories at higher values.</li>
</ul>
<p>These ranges come from widely used public health guidelines and are useful as a general population-level reference point, not as a precise individual health verdict.</p>

<h2>Why BMI has real, well-documented limitations</h2>
<p>BMI doesn't distinguish between muscle and fat — a highly muscular athlete can register a "high" BMI despite having very low body fat, because the formula only sees total weight relative to height, not body composition. It also doesn't account for where fat is distributed on the body, which matters significantly for certain health risk assessments, or for differences across age groups and ethnicities that affect what a "typical" range actually looks like for a given individual. For these reasons, BMI is best treated as one general data point among several, not a standalone health diagnosis.</p>

<h2>When to talk to a healthcare provider instead</h2>
<p>A single BMI number, calculated once, tells you very little on its own — it's most useful as a rough starting reference point or tracked as a general trend over time, alongside other measures. Anyone with specific health concerns, or whose BMI falls well outside the typical range, should discuss it with a doctor or qualified healthcare provider who can properly account for muscle mass, overall health history, and other factors this calculator simply can't see.</p>

<h2>Frequently asked questions</h2>
<p><strong>Is BMI an accurate measure of health?</strong> It's a useful, fast screening tool at a population level, but it has real, documented limitations for individuals — particularly athletes, older adults, and anyone with atypical body composition.</p>
<p><strong>What's a "healthy" BMI?</strong> Standard public health guidelines place 18.5–24.9 as the typical healthy range for most adults, though individual context always matters.</p>
<p><strong>Does this tool store my height and weight?</strong> No — the calculation happens instantly and locally in your browser; nothing is saved or transmitted.</p>
<p><strong>Should I make health decisions based on this number alone?</strong> No — treat it as one general reference point, and consult a healthcare provider for any actual health assessment or decision.</p>
HTML
);

expand_tool($pdo, 'age-calculator', <<<HTML
<p>Calculating an exact age — down to the year, month, and day — sounds trivial until you actually try to do it by hand across different month lengths and leap years, at which point it becomes a surprisingly fiddly bit of arithmetic. This calculator computes exact age instantly from a birth date, along with days until your next birthday and total days lived, handling all the calendar edge cases automatically.</p>

<h2>Why manual age calculation goes wrong so often</h2>
<p>Months have different numbers of days, leap years add an extra day every four years (with some exceptions), and simply subtracting birth year from current year gets the age wrong for anyone who hasn't yet had their birthday this calendar year. These small edge cases compound quickly when calculating by hand, which is exactly why a dedicated calculator — that correctly accounts for every one of them — produces a reliably accurate result every time.</p>

<h2>How to use it</h2>
<ol>
<li>Enter a birth date.</li>
<li>Instantly see the exact current age in years, months, and days.</li>
<li>See additional details like days until the next birthday and total days lived.</li>
</ol>

<h2>Practical situations where exact age matters</h2>
<ul>
<li><strong>Legal and administrative forms</strong> — many official documents require an exact age calculation as of a specific date, not just a birth year.</li>
<li><strong>Eligibility checks</strong> — age-restricted services, programs, or benefits often have precise age cutoffs tied to a specific date.</li>
<li><strong>Milestone tracking</strong> — parents tracking a child's exact age in months during early development, when precise age matters for developmental milestones.</li>
<li><strong>Curiosity and fun facts</strong> — calculating exact days lived, or which day of the week you were born on, are popular reasons people use an age calculator beyond strict necessity.</li>
</ul>

<h2>A note on leap years and edge cases</h2>
<p>Anyone born on February 29th technically only has a "real" birthday once every four years, and different systems handle this differently for legal and practical purposes. This calculator handles leap years and month-length differences correctly by working from actual calendar dates rather than a simplified day-count approximation, avoiding the small but real errors that a naive manual calculation would introduce.</p>

<h2>Frequently asked questions</h2>
<p><strong>Does this account for leap years correctly?</strong> Yes — the calculation is based on real calendar dates and correctly handles leap years and varying month lengths.</p>
<p><strong>Can I calculate age as of a specific past or future date, not just today?</strong> Yes — enter any reference date to calculate age as of that specific date rather than the current date.</p>
<p><strong>Is my birth date information stored anywhere?</strong> No — the calculation happens instantly and locally in your browser and is never transmitted or saved.</p>
<p><strong>Why does my age in months not divide evenly?</strong> Because months have different lengths, an age expressed in months and days naturally won't divide as evenly as a simple year count — this is expected and mathematically correct.</p>
HTML
);

expand_tool($pdo, 'unix-timestamp-converter', <<<HTML
<p>A Unix timestamp — a single large number representing the number of seconds (or milliseconds) since January 1st, 1970 — is how computers internally represent dates and times, but it's essentially meaningless to a human reading it directly. This converter translates instantly between Unix timestamps and human-readable dates in both directions, and displays the result in your local timezone as well as UTC.</p>

<h2>Why computers use timestamps instead of calendar dates</h2>
<p>Storing a date as a single incrementing number is dramatically simpler and more reliable for a computer than storing a calendar date with a timezone, because it avoids ambiguity entirely — a Unix timestamp always refers to the exact same instant in time regardless of what timezone the system reading it happens to be in. Calendar dates only get attached for human display, calculated from the underlying timestamp at the moment they're shown.</p>

<h2>How to use it</h2>
<ol>
<li>Paste a Unix timestamp to convert it into a readable date and time.</li>
<li>Or select a date and time to convert it into the corresponding Unix timestamp.</li>
<li>Results display in both UTC and your local timezone for easy comparison.</li>
</ol>

<h2>Common situations where this comes up</h2>
<ul>
<li><strong>Debugging API responses</strong> — many APIs return dates as raw Unix timestamps, and converting one to a readable date is often the fastest way to confirm the data is correct.</li>
<li><strong>Reading server or application logs</strong> — log files frequently record events with a Unix timestamp rather than a formatted date.</li>
<li><strong>Database troubleshooting</strong> — many database systems store date fields internally as timestamps, and converting one manually is a common debugging step.</li>
<li><strong>Scheduling and expiration logic</strong> — developers building anything with a time-based expiration (a token, a session, a cache entry) often work directly with timestamps.</li>
</ul>

<h2>Seconds versus milliseconds — a common source of confusion</h2>
<p>Some systems (like most Unix and server-side tools) use seconds since the epoch, while JavaScript's built-in <code>Date</code> object uses milliseconds — a difference of a factor of 1000 that, if missed, produces a wildly wrong date, either far in the past or absurdly far in the future. This converter handles both formats, and a quick way to sanity-check which one you're looking at is the number of digits: a current second-based timestamp has 10 digits, while a millisecond-based one has 13.</p>

<h2>Frequently asked questions</h2>
<p><strong>What does a Unix timestamp of 0 represent?</strong> January 1st, 1970, 00:00:00 UTC — the reference point, or "epoch," that all Unix timestamps count forward from.</p>
<p><strong>Can Unix timestamps represent dates before 1970?</strong> Yes — using negative numbers, though support for negative timestamps varies slightly between systems.</p>
<p><strong>Why does the same timestamp show a different time in different timezones?</strong> A Unix timestamp represents a single fixed instant in time; the displayed date and time simply reflects that instant translated into whichever timezone you're viewing it in.</p>
<p><strong>Is my data sent anywhere when I convert a timestamp?</strong> No — all conversion happens instantly and locally in your browser.</p>
HTML
);

expand_tool($pdo, 'css-minifier', <<<HTML
<p>Every extra byte in a stylesheet is a byte every visitor has to download before your page can render properly, and a nicely formatted, human-readable CSS file — full of comments, indentation, and whitespace — carries a real, measurable cost in page load time at scale. This minifier strips all of that out instantly, producing a functionally identical but dramatically smaller stylesheet, entirely in your browser.</p>

<h2>What minification actually removes</h2>
<p>Minification strips whitespace, line breaks, comments, and unnecessary characters from CSS without changing what it does — every selector, property, and value that affects how a page actually renders is preserved exactly; only the human-readability formatting, which browsers don't need, is removed. The result is a file that looks unreadable to a person but parses and executes identically to the original in every browser.</p>

<h2>How to use it</h2>
<ol>
<li>Paste your CSS into the input area.</li>
<li>The tool minifies it instantly, removing whitespace, comments, and redundant characters.</li>
<li>Copy the minified output and use it in production; keep your original, readable version for future editing.</li>
</ol>

<h2>Why this matters for real page performance</h2>
<p>CSS is a render-blocking resource by default — a browser generally won't paint anything on screen until it has downloaded and parsed the page's stylesheets, which means a smaller CSS file translates directly into a faster First Contentful Paint. For a stylesheet with heavy comments and formatting, minification can meaningfully cut file size, and every one of those saved bytes shortens the time before a visitor sees anything on the page at all.</p>

<h2>Best practices around minification</h2>
<ul>
<li><strong>Always keep an unminified source</strong> — write and maintain your CSS in a readable, commented format, and generate the minified version specifically for production deployment.</li>
<li><strong>Minify as a build step, not by hand</strong> — for any project beyond a single quick page, automate minification as part of your deployment process rather than manually minifying and pasting in a new version every time you make a change.</li>
<li><strong>Combine with other optimizations</strong> — minification works best alongside other performance practices like removing genuinely unused CSS rules and properly compressing the file at the server level.</li>
</ul>

<h2>Frequently asked questions</h2>
<p><strong>Does minifying CSS change how a page looks or behaves?</strong> No — minification only removes formatting that doesn't affect rendering; every functional rule is fully preserved.</p>
<p><strong>Can minified CSS be un-minified back to a readable format?</strong> The specific formatting choices (exact indentation, original comments) are lost, but a "beautifier" tool can re-add generic formatting to make minified CSS readable again for editing purposes.</p>
<p><strong>Is my CSS uploaded anywhere?</strong> No — minification happens entirely locally in your browser.</p>
<p><strong>How much file size reduction should I expect?</strong> It varies significantly based on how much whitespace and commenting the original file has — heavily commented, indented stylesheets see the largest reduction.</p>
HTML
);

expand_tool($pdo, 'text-to-speech', <<<HTML
<p>Turning written text into spoken audio has genuine practical uses well beyond accessibility, though accessibility remains one of its most important applications — proofreading by ear, previewing how a script or announcement will actually sound when read aloud, or simply consuming written content hands-free while doing something else. This tool converts any text you provide into natural-sounding speech instantly, using your browser's built-in speech synthesis, with nothing you type ever uploaded anywhere.</p>

<h2>How browser-based text-to-speech works</h2>
<p>Modern browsers include a built-in Web Speech API capable of converting text into spoken audio using voices installed on your own device or provided by your operating system — no server round-trip, no account, and no data leaving your device required. This tool provides a simple interface on top of that built-in capability, letting you control voice selection, speaking rate, and pitch without writing any code yourself.</p>

<h2>How to use it</h2>
<ol>
<li>Paste or type the text you want converted to speech.</li>
<li>Choose an available voice, and adjust the speaking rate and pitch to your preference.</li>
<li>Press play to hear it read aloud instantly.</li>
</ol>

<h2>Genuinely useful applications</h2>
<ul>
<li><strong>Accessibility</strong> — making written content accessible to people with visual impairments or reading difficulties like dyslexia, letting them consume text by listening rather than reading.</li>
<li><strong>Proofreading by ear</strong> — hearing your own writing read aloud is a well-known technique for catching awkward phrasing, repeated words, and errors that are easy to miss when reading silently, since your ear catches different mistakes than your eye does.</li>
<li><strong>Script and speech rehearsal</strong> — hearing how a script, announcement, or presentation actually sounds spoken aloud before delivering it live.</li>
<li><strong>Language learning</strong> — hearing correct pronunciation of written text, useful when learning a new language or unfamiliar vocabulary.</li>
<li><strong>Hands-free content consumption</strong> — listening to articles, notes, or documents while doing something else, like commuting or exercising.</li>
</ul>

<h2>A note on voice quality and availability</h2>
<p>Available voices depend on your specific device and operating system rather than this tool itself — most modern devices ship with several reasonably natural-sounding voices in multiple languages built in, and the tool simply gives you access to whichever ones your browser and system make available.</p>

<h2>Frequently asked questions</h2>
<p><strong>Is my text uploaded to a server to generate the speech?</strong> No — speech synthesis happens entirely on your own device using your browser's built-in Web Speech API.</p>
<p><strong>Can I download the generated speech as an audio file?</strong> This tool is built for instant playback rather than file export; for a downloadable audio file, a dedicated recording step would be needed separately.</p>
<p><strong>Does it support languages other than English?</strong> Voice availability depends on what's installed on your specific device, and most modern systems include several languages by default.</p>
<p><strong>Why does the voice sound slightly different on different devices?</strong> Because the tool uses each device's own built-in voices rather than a single centralized voice engine, the exact sound varies by operating system and installed voice packs.</p>
HTML
);

}
