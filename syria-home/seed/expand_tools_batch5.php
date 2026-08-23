<?php
/** Long-form expansion for tool full_description fields. See includes/content_expansion.php. */
function expand_tools_batch5(PDO $pdo): void {

expand_tool($pdo, 'background-remover', <<<HTML
<p>Removing an image's background used to require dedicated design software and real skill with a selection tool — carefully tracing an edge, painstakingly cleaning up stray pixels around hair or fine detail. This tool automates that entire process, detecting the subject and cutting away the background in seconds, directly in your browser, with no design experience and no software installation required.</p>

<h2>How automatic background removal works</h2>
<p>The tool analyzes the image to distinguish the foreground subject from its surrounding background, using edge detection and contrast analysis to build a precise mask around the subject, then removes everything outside that mask and replaces it with transparency. Modern automatic background removal handles complex edges — hair, fur, fine detail — dramatically better than older, simpler tools that struggled badly with anything beyond a clean, simple silhouette.</p>

<h2>How to use it</h2>
<ol>
<li>Upload the image you want to process.</li>
<li>The background is detected and removed automatically within seconds.</li>
<li>Download the result as a transparent PNG, ready to place on any new background.</li>
</ol>

<h2>Common use cases</h2>
<ul>
<li><strong>Product photography</strong> — placing a product on a clean white or branded background for an e-commerce listing, without needing a physical studio backdrop.</li>
<li><strong>Profile and headshot photos</strong> — creating a consistent, professional-looking background across a team page or set of profile photos.</li>
<li><strong>Marketing and social graphics</strong> — isolating a subject to place over branded graphics, promotional banners, or social media templates.</li>
<li><strong>Presentation and design work</strong> — cutting out an object or person to compose into a slide, poster, or graphic without the original background interfering.</li>
</ul>

<h2>Getting the cleanest results</h2>
<p>Automatic background removal performs best on images with good lighting and reasonable contrast between the subject and its background — a subject wearing clothing similar in color to the background, or shot in poor lighting with heavy shadows, is inherently harder for any detection algorithm (automatic or manual) to separate cleanly. For the highest-stakes use cases, like a hero product photo, it's worth reviewing the result closely around fine detail like hair or transparent objects before publishing.</p>

<h2>Frequently asked questions</h2>
<p><strong>Is my photo uploaded to a remote server for processing?</strong> Processing runs in your browser using on-device image analysis; your photo is not stored on a remote server.</p>
<p><strong>What format does the output come in?</strong> A PNG file with a transparent background, ready to layer onto any new background in a design tool or directly on a website.</p>
<p><strong>Does this work well on photos with complex detail like hair?</strong> Modern edge-detection handles fine detail significantly better than older tools, though extremely fine, wispy detail against a busy background remains the hardest case for any automatic tool.</p>
<p><strong>Can I use the result commercially?</strong> Yes — the tool only modifies the image you provide; you retain the same rights to the output that you held over the original image.</p>
HTML
);

expand_tool($pdo, 'bulk-image-resizer', <<<HTML
<p>Resizing dozens or hundreds of images one at a time is exactly the kind of repetitive task that makes a strong case for automation — a task that's simple in isolation becomes genuinely tedious at any real volume. This tool resizes multiple images at once to a consistent target size, directly in your browser, saving the manual work of opening, resizing, and re-exporting each file individually.</p>

<h2>Why consistent image dimensions matter</h2>
<p>A gallery, product catalog, or blog with inconsistently sized images looks visibly unpolished — mismatched thumbnail sizes create an uneven grid, and oversized images slow down page loading unnecessarily. Standardizing dimensions across a batch of images, before they're published, produces a cleaner, more professional result and a measurably faster-loading page than resizing images individually and inconsistently after the fact.</p>

<h2>How to use it</h2>
<ol>
<li>Select multiple images at once from your device.</li>
<li>Set your target dimensions — either an exact size or a maximum width/height that scales proportionally.</li>
<li>Process the entire batch and download the resized images.</li>
</ol>

<h2>Choosing between exact and proportional resizing</h2>
<p>Exact resizing forces every image to precisely the same dimensions, which is right for something like a product thumbnail grid where visual consistency is the priority, but can distort images that don't share the same original aspect ratio. Proportional resizing scales each image down to fit within a maximum dimension while preserving its original aspect ratio, avoiding any distortion — the right choice whenever images have varying original proportions and distortion isn't acceptable.</p>

<h2>Common use cases</h2>
<ul>
<li><strong>E-commerce product galleries</strong> — standardizing every product photo to the same dimensions before uploading to a store platform.</li>
<li><strong>Blog and article images</strong> — resizing a batch of photos down to a sensible maximum width before publishing, cutting page weight across an entire site at once.</li>
<li><strong>Social media content batches</strong> — preparing multiple images at a consistent size for a content calendar or scheduled posting tool.</li>
<li><strong>Photography portfolio prep</strong> — resizing an entire shoot down to web-appropriate dimensions in one pass rather than one image at a time.</li>
</ul>

<h2>Frequently asked questions</h2>
<p><strong>How many images can I process at once?</strong> The practical limit depends on your device's available memory rather than the tool itself — most everyday batches process comfortably.</p>
<p><strong>Will resizing distort my images?</strong> Only if you choose exact dimensions on images with differing original aspect ratios — use proportional resizing to avoid distortion entirely.</p>
<p><strong>Are my images uploaded to a server?</strong> No — resizing happens locally in your browser for every image in the batch.</p>
<p><strong>Can I resize images to different sizes within the same batch?</strong> This tool applies one consistent target size across the whole batch, which is the point — for genuinely different sizes per image, process them in separate batches.</p>
HTML
);

expand_tool($pdo, 'watermark-adder', <<<HTML
<p>A watermark serves a specific practical purpose: marking an image as yours before you share it publicly, discouraging unauthorized use, and making it easy for others to trace an image back to its original source even after it's been copied or reshared elsewhere. This tool adds a text or logo watermark to any image, with full control over position, opacity, and size, entirely in your browser.</p>

<h2>Why photographers and creators watermark their work</h2>
<p>Once an image is posted publicly, controlling how it gets used and shared becomes genuinely difficult — a watermark doesn't prevent someone from copying an image, but it does make the original source unmistakably visible even after it's been reshared, cropped from its original context, or reposted without credit. For anyone whose livelihood depends even partly on their images — photographers, designers, content creators — a visible watermark is a simple, low-effort form of protection and, just as importantly, free advertising every time the image circulates.</p>

<h2>How to use it</h2>
<ol>
<li>Upload the image you want to watermark.</li>
<li>Add a text watermark (like your name or website) or upload a logo image to use instead.</li>
<li>Adjust position, size, and opacity until it looks right, then download the watermarked result.</li>
</ol>

<h2>Getting the balance right</h2>
<p>A watermark that's too subtle is trivially easy to crop out; one that's too intrusive ruins the image it's meant to protect and makes it visibly less appealing to share in the first place — which somewhat defeats the purpose if part of your goal is exposure. A common, effective middle ground is a semi-transparent watermark placed across a meaningful portion of the image (not just a corner, which crops out easily) at a low enough opacity that it doesn't overwhelm the actual photo underneath.</p>

<h2>Common use cases</h2>
<ul>
<li><strong>Photography portfolios</strong> — protecting preview images shared publicly before a client purchases the unwatermarked original.</li>
<li><strong>Stock and marketplace previews</strong> — watermarking sample images so a buyer can evaluate quality before paying for a clean, unwatermarked version.</li>
<li><strong>Social media branding</strong> — adding a consistent logo watermark across posted content, reinforcing brand recognition as images get shared and reshared.</li>
<li><strong>Draft and proof sharing</strong> — clearly marking an image as a draft or proof before final approval, avoiding accidental early use of an unfinished version.</li>
</ul>

<h2>Frequently asked questions</h2>
<p><strong>Does a watermark completely prevent someone from using my image?</strong> No — a determined person can crop or edit around a watermark; it functions as a deterrent and attribution tool, not an absolute technical barrier.</p>
<p><strong>What opacity works best for most images?</strong> A moderate opacity — visible enough to be noticed and hard to casually crop out, but low enough not to obscure the underlying photo — is generally the most effective middle ground.</p>
<p><strong>Is my image uploaded anywhere?</strong> No — watermarking happens entirely in your browser using the Canvas API.</p>
<p><strong>Can I save a watermark preset to reuse across multiple images?</strong> Position, size, and text settings carry over within a session, making it fast to apply a consistent watermark across several images in a row.</p>
HTML
);

expand_tool($pdo, 'exif-viewer-remover', <<<HTML
<p>Every photo taken on a modern phone or camera carries hidden metadata called EXIF data — camera settings, timestamp, and very often precise GPS coordinates of exactly where the photo was taken — embedded invisibly in the file itself. This tool reveals exactly what metadata a given image contains, and strips it out entirely when you need to share a photo without exposing that hidden information, all processed locally in your browser.</p>

<h2>What EXIF data actually contains</h2>
<p>EXIF (Exchangeable Image File Format) metadata typically includes the camera or phone model used, the exact date and time the photo was taken, camera settings like aperture and shutter speed, and — critically for privacy — GPS coordinates precise enough to identify exactly where the photo was captured, including inside a private home. None of this is visible when you simply look at the image, but it travels along with the file every time it's shared, unless something specifically strips it out first.</p>

<h2>How to use it</h2>
<ol>
<li>Upload an image to view all embedded EXIF metadata, including any GPS coordinates.</li>
<li>Review exactly what information the file contains.</li>
<li>Strip all metadata and download a clean copy with none of it attached.</li>
</ol>

<h2>Why this matters more than most people realize</h2>
<p>A photo posted publicly — on a marketplace listing, a social media post, or a blog — often still carries its original EXIF data unless the platform automatically strips it, which not all platforms reliably do. Anyone who downloads that image can potentially extract the exact GPS location where it was taken, which becomes a genuine privacy and safety concern for photos taken at home, at a child's school, or anywhere else someone might not want their precise location exposed to strangers.</p>

<h2>Common use cases</h2>
<ul>
<li><strong>Selling items online</strong> — stripping location data from marketplace listing photos before posting them publicly to strangers.</li>
<li><strong>Sharing personal photos</strong> — removing GPS data from photos before posting on social media or forwarding to people outside your trusted circle.</li>
<li><strong>Journalism and sensitive photography</strong> — protecting a source's or subject's location when sharing photos in a professional or sensitive context.</li>
<li><strong>Verifying image authenticity</strong> — reviewing camera settings and timestamps as one signal (among several) when assessing whether an image is what it claims to be.</li>
</ul>

<h2>Frequently asked questions</h2>
<p><strong>Do social media platforms automatically remove EXIF data?</strong> Many major platforms strip most metadata on upload, but this isn't universal or guaranteed across every platform and every sharing method, which is why checking directly is the safer approach.</p>
<p><strong>Is my image uploaded to a server to read its metadata?</strong> No — metadata reading and removal both happen entirely locally in your browser.</p>
<p><strong>Does removing EXIF data reduce image quality?</strong> No — EXIF data is separate from the actual image pixels; removing it has no effect on visual quality.</p>
<p><strong>Can I selectively remove just the GPS data and keep other metadata?</strong> The tool is built primarily around full metadata removal for maximum privacy, which is the safest default for anything being shared publicly.</p>
HTML
);

expand_tool($pdo, 'favicon-generator', <<<HTML
<p>A favicon — the small icon that appears in a browser tab, bookmarks bar, and search results next to your site's name — is a small detail that has an outsized effect on how polished and trustworthy a website looks at a glance. This generator takes a source image or logo and produces a complete set of favicon files in every size modern browsers and devices need, ready to upload directly to your site.</p>

<h2>Why a single icon size isn't enough anymore</h2>
<p>Modern favicons need to work across a wide range of contexts — a tiny browser tab, a home-screen icon on a phone, a bookmark, a search result listing — and each of those contexts expects a different pixel size for the icon to look crisp rather than blurry or oddly cropped. A single generic favicon file that used to be sufficient years ago now commonly needs to be exported at eight or more different sizes to look correct everywhere it might appear.</p>

<h2>How to use it</h2>
<ol>
<li>Upload your logo or source image — ideally simple and high-contrast, since a favicon is viewed extremely small.</li>
<li>The tool generates a complete set of icon sizes automatically.</li>
<li>Download the full set, along with the HTML markup needed to link them correctly in your site's <code>&lt;head&gt;</code>.</li>
</ol>

<h2>What a complete favicon set typically includes</h2>
<ul>
<li><strong>Standard browser tab sizes</strong> — small square icons for the browser tab itself, typically 16×16 and 32×32 pixels.</li>
<li><strong>Apple touch icon</strong> — a larger icon used when a page is saved to an iPhone or iPad home screen.</li>
<li><strong>Android/Chrome icons</strong> — sizes used for home-screen shortcuts and progressive web app installs on Android devices.</li>
<li><strong>A classic <code>.ico</code> file</strong> — still checked by some older browsers and systems as a fallback.</li>
</ul>

<h2>Design tips for a favicon that actually reads clearly</h2>
<p>Because a favicon is viewed at an extremely small size, fine detail and thin text simply disappear — a simple, bold shape or a single letter with strong contrast reads far more clearly at 16 pixels than a detailed logo with fine lines does. If your main logo is detailed, consider creating a simplified icon-only version specifically for the favicon rather than shrinking your full logo down and hoping it still reads clearly.</p>

<h2>Frequently asked questions</h2>
<p><strong>Why does my favicon look blurry in the browser tab?</strong> Usually because the source image was too detailed or low-resolution to begin with — start from a simple, high-resolution source image for the sharpest result.</p>
<p><strong>Do I need all the generated sizes, or just one?</strong> Different devices and contexts request different sizes automatically; providing the full set ensures your icon looks correct everywhere rather than falling back to a stretched or cropped substitute.</p>
<p><strong>Is my logo uploaded to a remote server?</strong> No — icon generation happens locally in your browser using the Canvas API.</p>
<p><strong>How do I actually install the generated favicon on my site?</strong> Upload the generated files to your site and add the provided HTML link tags to your page's <code>&lt;head&gt;</code> section — the generator includes this markup ready to copy.</p>
HTML
);

}
