<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/api-client.php';
require_once __DIR__ . '/inc/sitemap-helpers.php';

// Every tag's /tag/{slug} page - reuses ga_fetch_all_tags(), the same full tag list
// list-page.php's own tag-mode routing already fetches and caches (GA_TAGS_CACHE_TTL, far
// longer than articles since new tags appear much less often). That function always returns
// an array (falls back to [] on a hard failure, never null), so there's no partial-failure
// case to guard against here the way sitemap-articles.php has to for its paginated walk.

header('Content-Type: application/xml; charset=utf-8');

ga_sitemap_serve('sitemap_tags', GA_TAGS_CACHE_TTL, function () {
    $base = GA_SITEMAP_BASE_URL;
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    foreach (ga_fetch_all_tags() as $tag) {
        if (empty($tag['slug'])) {
            continue;
        }
        $xml .= ga_sitemap_url_entry($base . '/tag/' . rawurlencode($tag['slug']), null, 'weekly', '0.5');
    }

    $xml .= '</urlset>' . "\n";
    return ['xml' => $xml, 'complete' => true];
});
