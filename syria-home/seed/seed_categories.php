<?php
function seed_categories(PDO $pdo): void {
    $rows = [
        ['AI & Software', 'ai-software', 'article', 'fa-microchip', '#6366f1'],
        ['Hardware & Gadgets', 'hardware-gadgets', 'article', 'fa-laptop', '#0ea5e9'],
        ['Cybersecurity', 'cybersecurity', 'article', 'fa-shield-halved', '#ef4444'],
        ['Mobile', 'mobile', 'article', 'fa-mobile-screen', '#10b981'],
        ['Internet Culture', 'internet-culture', 'article', 'fa-globe', '#f97316'],
        ['Comparisons', 'comparisons', 'article', 'fa-scale-balanced', '#ec4899'],
        ['How-To Guides', 'how-to-guides', 'article', 'fa-graduation-cap', '#14b8a6'],
        ['Image Tools', 'image-tools', 'tool', 'fa-images', '#6366f1'],
        ['Text Tools', 'text-tools', 'tool', 'fa-font', '#0ea5e9'],
        ['Generators', 'generators', 'tool', 'fa-wand-magic-sparkles', '#f97316'],
        ['Converters', 'converters', 'tool', 'fa-right-left', '#10b981'],
        ['Calculators', 'calculators', 'tool', 'fa-calculator', '#ec4899'],
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, slug, type, icon, color) VALUES (?,?,?,?,?)");
    foreach ($rows as $r) $stmt->execute($r);
}
