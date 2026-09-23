<?php
require_once __DIR__ . '/config.php';

// Superseded by sitemap_index.php + the type-specific sitemap-*.php files (articles,
// categories, tags, pages) and news-sitemap.php - matching the WordPress/Yoast sitemap this
// site is replacing, which also splits by type behind an index rather than one flat file.
// Kept as a redirect, not deleted outright, since /sitemap.xml may already be submitted in
// Search Console or bookmarked/linked elsewhere.
header('Location: ' . GA_SITEMAP_BASE_URL . '/sitemap_index.xml', true, 301);
exit;
