<?php
/**
 * Original inline SVG artwork. Everything here is hand-built geometry —
 * no stock imagery, no AI-generated images, so there is zero licensing
 * or attribution risk when this is resold as part of a template.
 *
 * All art inherits its palette from CSS custom properties where possible
 * so a buyer can recolor the whole site by editing tokens in style.css.
 */

const ART_PALETTES = [
    'p1'  => ['#6366f1', '#8b5cf6'],
    'p2'  => ['#0ea5e9', '#22d3ee'],
    'p3'  => ['#f97316', '#f43f5e'],
    'p4'  => ['#10b981', '#059669'],
    'p5'  => ['#8b5cf6', '#ec4899'],
    'p6'  => ['#3b82f6', '#14b8a6'],
    'p7'  => ['#eab308', '#f97316'],
    'p8'  => ['#ef4444', '#f59e0b'],
    'p9'  => ['#14b8a6', '#3b82f6'],
    'p10' => ['#ec4899', '#8b5cf6'],
];

function art_colors(string $key): array {
    return ART_PALETTES[$key] ?? ART_PALETTES['p1'];
}

/** Decorative background pattern used behind hero sections. */
function svg_hero_pattern(): string {
    return <<<'SVG'
<svg class="hero-pattern" viewBox="0 0 1200 320" preserveAspectRatio="none" aria-hidden="true" focusable="false">
  <defs>
    <linearGradient id="hp1" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#6366f1" stop-opacity=".14"/>
      <stop offset="1" stop-color="#22d3ee" stop-opacity=".05"/>
    </linearGradient>
    <linearGradient id="hp2" x1="1" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#ec4899" stop-opacity=".10"/>
      <stop offset="1" stop-color="#8b5cf6" stop-opacity=".03"/>
    </linearGradient>
  </defs>
  <circle cx="1040" cy="60" r="180" fill="url(#hp1)"/>
  <circle cx="880" cy="240" r="120" fill="url(#hp2)"/>
  <circle cx="1160" cy="230" r="80" fill="url(#hp1)"/>
  <g stroke="#6366f1" stroke-opacity=".10" stroke-width="1.5" fill="none">
    <path d="M0 250 Q 200 190 400 240 T 800 220"/>
    <path d="M0 285 Q 220 225 440 275 T 860 255"/>
  </g>
</svg>
SVG;
}

/**
 * Product artwork — a distinct abstract composition per product,
 * tinted by that product's palette key.
 */
function svg_product_art(string $key, string $iconClass = ''): string {
    [$a, $b] = art_colors($key);
    $id = 'pa_' . preg_replace('~\W~', '', $key);
    $n = (int)filter_var($key, FILTER_SANITIZE_NUMBER_INT);

    $shapes = '';
    switch ($n % 5) {
        case 0:
            $shapes = '<rect x="52" y="40" width="116" height="86" rx="12" fill="#fff" fill-opacity=".22"/>
                       <rect x="66" y="56" width="64" height="8" rx="4" fill="#fff" fill-opacity=".55"/>
                       <rect x="66" y="72" width="88" height="6" rx="3" fill="#fff" fill-opacity=".35"/>
                       <rect x="66" y="86" width="74" height="6" rx="3" fill="#fff" fill-opacity=".35"/>
                       <rect x="66" y="100" width="46" height="10" rx="5" fill="#fff" fill-opacity=".65"/>';
            break;
        case 1:
            $shapes = '<circle cx="110" cy="84" r="46" fill="#fff" fill-opacity=".18"/>
                       <circle cx="110" cy="84" r="28" fill="#fff" fill-opacity=".28"/>
                       <circle cx="110" cy="84" r="12" fill="#fff" fill-opacity=".7"/>
                       <path d="M110 24 v18 M110 126 v18 M50 84 h18 M152 84 h18" stroke="#fff" stroke-opacity=".5" stroke-width="5" stroke-linecap="round"/>';
            break;
        case 2:
            $shapes = '<rect x="58" y="94" width="24" height="36" rx="5" fill="#fff" fill-opacity=".65"/>
                       <rect x="90" y="70" width="24" height="60" rx="5" fill="#fff" fill-opacity=".5"/>
                       <rect x="122" y="48" width="24" height="82" rx="5" fill="#fff" fill-opacity=".35"/>
                       <path d="M58 60 L 90 44 L 122 52 L 158 30" stroke="#fff" stroke-opacity=".7" stroke-width="4" fill="none" stroke-linecap="round" stroke-linejoin="round"/>';
            break;
        case 3:
            $shapes = '<path d="M110 34 L 168 68 V 116 L 110 150 L 52 116 V 68 Z" fill="#fff" fill-opacity=".18"/>
                       <path d="M110 60 L 144 80 V 112 L 110 132 L 76 112 V 80 Z" fill="#fff" fill-opacity=".32"/>
                       <circle cx="110" cy="96" r="13" fill="#fff" fill-opacity=".75"/>';
            break;
        default:
            $shapes = '<rect x="48" y="52" width="60" height="60" rx="14" fill="#fff" fill-opacity=".3"/>
                       <rect x="116" y="52" width="56" height="26" rx="10" fill="#fff" fill-opacity=".5"/>
                       <rect x="116" y="86" width="56" height="26" rx="10" fill="#fff" fill-opacity=".22"/>
                       <circle cx="78" cy="82" r="15" fill="#fff" fill-opacity=".72"/>';
    }

    return <<<SVG
<svg class="product-art" viewBox="0 0 220 176" role="img" aria-hidden="true" focusable="false">
  <defs>
    <linearGradient id="{$id}" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{$a}"/><stop offset="1" stop-color="{$b}"/>
    </linearGradient>
  </defs>
  <rect width="220" height="176" fill="url(#{$id})"/>
  <circle cx="196" cy="24" r="46" fill="#fff" fill-opacity=".08"/>
  <circle cx="24" cy="158" r="38" fill="#fff" fill-opacity=".07"/>
  {$shapes}
</svg>
SVG;
}

/**
 * Abstract initials avatar for a team/profile card — intentionally not
 * a photo. We won't pass off a stock photo of an unrelated person, or a
 * synthetic AI-generated face, as someone's real likeness; swap this
 * for a real photo in about.php whenever one is available.
 */
function svg_initials_avatar(string $initial): string {
    $initial = e(mb_substr($initial, 0, 1));
    return <<<SVG
<svg viewBox="0 0 200 200" class="avatar-art" role="img" aria-label="Profile avatar" focusable="false">
  <defs>
    <linearGradient id="avg1" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#6366f1"/><stop offset="0.55" stop-color="#8b5cf6"/><stop offset="1" stop-color="#22d3ee"/>
    </linearGradient>
  </defs>
  <circle cx="100" cy="100" r="96" fill="url(#avg1)"/>
  <circle cx="100" cy="100" r="96" fill="none" stroke="#fff" stroke-opacity=".25" stroke-width="2"/>
  <circle cx="46" cy="40" r="10" fill="#fff" fill-opacity=".18"/>
  <circle cx="162" cy="150" r="16" fill="#fff" fill-opacity=".12"/>
  <circle cx="150" cy="46" r="7" fill="#fff" fill-opacity=".2"/>
  <text x="100" y="128" font-family="Arial, sans-serif" font-size="84" font-weight="800" fill="#fff" text-anchor="middle">{$initial}</text>
</svg>
SVG;
}

/** Small decorative badge/seal used on guarantee blocks. */
function svg_guarantee_seal(): string {
    return <<<'SVG'
<svg viewBox="0 0 64 64" class="seal" role="img" aria-label="Guarantee" focusable="false">
  <defs><linearGradient id="gs" x1="0" y1="0" x2="1" y2="1">
    <stop offset="0" stop-color="#10b981"/><stop offset="1" stop-color="#059669"/>
  </linearGradient></defs>
  <path d="M32 4l22 9v16c0 14-9.5 24.5-22 31C19.5 53.5 10 43 10 29V13z" fill="url(#gs)"/>
  <path d="M22 32l7 7 14-15" stroke="#fff" stroke-width="5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
SVG;
}

/** Empty-state / illustration for pages with no results. */
function svg_empty_state(): string {
    return <<<'SVG'
<svg viewBox="0 0 200 150" class="empty-art" aria-hidden="true" focusable="false">
  <defs><linearGradient id="es" x1="0" y1="0" x2="1" y2="1">
    <stop offset="0" stop-color="#6366f1" stop-opacity=".25"/><stop offset="1" stop-color="#22d3ee" stop-opacity=".1"/>
  </linearGradient></defs>
  <ellipse cx="100" cy="128" rx="62" ry="10" fill="#6366f1" fill-opacity=".08"/>
  <rect x="58" y="34" width="84" height="82" rx="12" fill="url(#es)"/>
  <rect x="72" y="52" width="46" height="7" rx="3.5" fill="#6366f1" fill-opacity=".35"/>
  <rect x="72" y="68" width="56" height="6" rx="3" fill="#6366f1" fill-opacity=".22"/>
  <rect x="72" y="82" width="38" height="6" rx="3" fill="#6366f1" fill-opacity=".22"/>
  <circle cx="146" cy="42" r="17" fill="#22d3ee" fill-opacity=".25"/>
</svg>
SVG;
}
