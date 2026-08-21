<?php
/**
 * includes/icons.php — مجموعة الأيقونات SVG
 */

function icon(string $name, int $size = 20, string $class = ''): string {
    $stroke = 'stroke="currentColor" stroke-width="1.8" fill="none" stroke-linecap="round" stroke-linejoin="round"';
    $attrs  = "width=\"{$size}\" height=\"{$size}\" viewBox=\"0 0 24 24\" class=\"icon {$class}\" style=\"width:{$size}px;height:{$size}px;\" {$stroke}";

    $paths = [
        'search'       => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
        'user'         => '<circle cx="12" cy="8" r="4"/><path d="M4 20c1.5-4 5-6 8-6s6.5 2 8 6"/>',
        'users'        => '<circle cx="9" cy="8" r="3.2"/><path d="M2.5 20c1.2-3.3 4-5 6.5-5s5.3 1.7 6.5 5"/><path d="M16 4.5c1.6.3 2.8 1.7 2.8 3.4S17.6 11 16 11.3"/><path d="M21.5 20c-.6-1.9-1.8-3.3-3.3-4.2"/>',
        'wallet'       => '<rect x="3" y="6" width="18" height="13" rx="2.2"/><path d="M3 10h18"/><circle cx="16.5" cy="14" r="1.1" fill="currentColor" stroke="none"/>',
        'coin'         => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5v9M9.5 9.7c0-1.2 1.1-2 2.5-2s2.5.9 2.5 2c0 2.7-5 1.8-5 4.5 0 1.1 1.1 2 2.5 2s2.5-.8 2.5-2"/>',
        'plus'         => '<path d="M12 5v14M5 12h14"/>',
        'plus-circle'  => '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
        'check'        => '<path d="M4 12l5.5 5.5L20 6.5"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.7 2.7L16 9.5"/>',
        'x'            => '<path d="M6 6l12 12M18 6L6 18"/>',
        'x-circle'     => '<circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/>',
        'edit'         => '<path d="M4 20h4.2L19 9.2a2.4 2.4 0 0 0-3.4-3.4L5 16.5V20z"/><path d="M13.5 7.5l3 3"/>',
        'trash'        => '<path d="M4 7h16"/><path d="M9 7V5a1.5 1.5 0 0 1 1.5-1.5h3A1.5 1.5 0 0 1 15 5v2"/><path d="M6.5 7l.7 12a2 2 0 0 0 2 1.9h5.6a2 2 0 0 0 2-1.9l.7-12"/><path d="M10 11v6M14 11v6"/>',
        'phone'        => '<path d="M6 3h3l1.5 4.5-2 1.5a12 12 0 0 0 6.5 6.5l1.5-2L21 15v3a2 2 0 0 1-2 2C10.5 20 4 13.5 4 5a2 2 0 0 1 2-2z"/>',
        'calendar'     => '<rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M8 3v4M16 3v4M3.5 10h17"/>',
        'clock'        => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
        'chart'        => '<path d="M4 20V10M11 20V4M18 20v-7"/><path d="M2.5 20h19"/>',
        'trend-up'     => '<path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
        'receipt'      => '<path d="M6 3h12v18l-2.5-1.6L13 21l-2.5-1.6L8 21l-2-1.6z"/><path d="M9 8h6M9 12h6M9 16h3"/>',
        'logout'       => '<path d="M9 5H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h3"/><path d="M15 16l4-4-4-4"/><path d="M19 12H9"/>',
        'login'        => '<path d="M14 5h3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-3"/><path d="M8 16l-4-4 4-4"/><path d="M4 12h11"/>',
        'home'         => '<path d="M4 11l8-6.5L20 11"/><path d="M6 10v9a1 1 0 0 0 1 1h4v-6h2v6h4a1 1 0 0 0 1-1v-9"/>',
        'dashboard'    => '<rect x="3.5" y="3.5" width="7.5" height="8.5" rx="1.5"/><rect x="13" y="3.5" width="7.5" height="5.5" rx="1.5"/><rect x="13" y="11.5" width="7.5" height="9" rx="1.5"/><rect x="3.5" y="14.5" width="7.5" height="6" rx="1.5"/>',
        'settings'     => '<circle cx="12" cy="12" r="3"/><path d="M19.4 13.5a7.5 7.5 0 0 0 0-3l2-1.5-2-3.4-2.3.9a7.4 7.4 0 0 0-2.6-1.5L14 2h-4l-.5 2.5a7.4 7.4 0 0 0-2.6 1.5l-2.3-.9-2 3.4 2 1.5a7.5 7.5 0 0 0 0 3l-2 1.5 2 3.4 2.3-.9c.8.7 1.7 1.2 2.6 1.5L10 22h4l.5-2.5a7.4 7.4 0 0 0 2.6-1.5l2.3.9 2-3.4z"/>',
        'bell'         => '<path d="M18 9a6 6 0 0 0-12 0c0 6-2.5 6.5-2.5 8.5h17C20.5 15.5 18 15 18 9z"/><path d="M10 20.5a2 2 0 0 0 4 0"/>',
        'arrow-left'   => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'arrow-right'  => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'chevron-down' => '<path d="M6 9l6 6 6-6"/>',
        'menu'         => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'store'        => '<path d="M4 9l1-5h14l1 5"/><path d="M4 9a2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0"/><path d="M5 9v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9"/><path d="M10 20v-5h4v5"/>',
        'refresh'      => '<path d="M20 11a8 8 0 1 0-2.3 5.7"/><path d="M20 5v6h-6"/>',
        'link'         => '<path d="M9.5 14.5l5-5"/><path d="M8 16l-1.5 1.5a3.2 3.2 0 0 1-4.5-4.5L5.5 10"/><path d="M16 8l1.5-1.5a3.2 3.2 0 0 1 4.5 4.5L18.5 14"/>',
        'send'         => '<path d="M21 3L10.5 13.5M21 3l-6.5 18-4-8-8-4z"/>',
        'chat'         => '<path d="M4 5h16v11H8l-4 4z"/>',
        'shield'       => '<path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6z"/><path d="M9 12l2 2 4-4"/>',
        'alert'        => '<path d="M12 3l10 18H2z"/><path d="M12 10v4M12 17.2v.1"/>',
        'info'         => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5v.1"/>',
        'filter'       => '<path d="M4 5h16M7 12h10M10 19h4"/>',
        'download'     => '<path d="M12 3v12M7 10l5 5 5-5"/><path d="M4 19h16"/>',
        'external'     => '<path d="M14 4h6v6"/><path d="M20 4L10 14"/><path d="M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4"/>',
        'globe'        => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 3.8 5.8 3.8 9s-1.3 6.5-3.8 9c-2.5-2.5-3.8-5.8-3.8-9S9.5 5.5 12 3z"/>',
        'key'          => '<circle cx="8" cy="15" r="4.5"/><path d="M11.2 11.8L20 3"/><path d="M15.5 7.5l2.5 2.5M18.5 4.5L21 7"/>',
        'image'        => '<rect x="3.5" y="4.5" width="17" height="15" rx="2"/><circle cx="9" cy="10" r="1.7"/><path d="M4 17l5-5 4 4 3-3 4 4"/>',
        'upload'       => '<path d="M12 15V3M7 8l5-5 5 5"/><path d="M4 19h16"/>',
        'save'         => '<path d="M5 3h11l3 3v15H5z"/><rect x="9" y="14" width="6" height="7"/><rect x="8" y="3" width="8" height="5"/>',
        'package'      => '<path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M12 3v18M3 8l9 5 9-5"/>',
        'tag'          => '<path d="M3.5 3.5h7.5l10 10-7.5 7.5-10-10z"/><circle cx="8" cy="9" r="1.5"/>',
        'trending-up'  => '<path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h6v6"/>',
        'swap'         => '<path d="M7 16l-4-4 4-4"/><path d="M17 8l4 4-4 4"/><path d="M3 12h18"/>',
        'zap'          => '<path d="M13 2L4.5 13H12L11 22l8.5-11H12.5z"/>',
    ];

    $body = $paths[$name] ?? $paths['info'];
    return "<svg {$attrs}>{$body}</svg>";
}
