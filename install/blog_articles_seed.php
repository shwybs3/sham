<?php
/**
 * install/blog_articles_seed.php — one-time blog_posts seed (batch 1).
 * Runs as a plain script (not `return`-based) inside ensure_schema(); insert
 * rows into blog_posts here via $pdo if this batch needs to seed real posts.
 * No-op by default — config.php marks 'blog_seeded_v1' done regardless.
 */
