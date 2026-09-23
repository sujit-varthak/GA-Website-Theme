<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/sitemap-helpers.php';

// Sitemap index - lists every type-specific sitemap below, matching the same structure the
// WordPress/Yoast sitemap this is replacing already uses (sitemap_index.xml -> post-sitemap*,
// page-sitemap, category-sitemap, post_tag-sitemap*, news-sitemap). Each entry's <lastmod> is
// that sub-sitemap's own cache file mtime, not "now" - an index that always claims everything
// just changed defeats the point of lastmod for crawlers deciding what to re-fetch.
//
// This file itself isn't cached the way the others are: it's cheap (just five mtime lookups,
// no backend calls) and always needs to reflect each sub-sitemap's true current lastmod.

header('Content-Type: application/xml; charset=utf-8');

$ga_base = GA_SITEMAP_BASE_URL;

$ga_sitemaps = [
    ['url' => $ga_base . '/sitemap-articles.xml', 'key' => 'sitemap_articles'],
    ['url' => $ga_base . '/sitemap-categories.xml', 'key' => 'sitemap_categories'],
    ['url' => $ga_base . '/sitemap-tags.xml', 'key' => 'sitemap_tags'],
    ['url' => $ga_base . '/sitemap-pages.xml', 'key' => 'sitemap_pages'],
    ['url' => $ga_base . '/news-sitemap.xml', 'key' => 'news_sitemap'],
];

$ga_xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$ga_xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($ga_sitemaps as $ga_sitemap) {
    $ga_xml .= "  <sitemap>\n";
    $ga_xml .= "    <loc>" . ga_sitemap_xml_escape($ga_sitemap['url']) . "</loc>\n";
    $ga_xml .= "    <lastmod>" . ga_sitemap_xml_escape(ga_sitemap_cache_mtime($ga_sitemap['key'])) . "</lastmod>\n";
    $ga_xml .= "  </sitemap>\n";
}

$ga_xml .= '</sitemapindex>' . "\n";

echo $ga_xml;
