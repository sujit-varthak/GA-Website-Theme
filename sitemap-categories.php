<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/api-client.php'; // ga_sitemap_serve() below needs ga_cache_lock_try()/_release()
require_once __DIR__ . '/inc/sitemap-helpers.php';

// Every clean-URL category (GA_CATEGORY_ROUTES, the same single source of truth
// list-page.php's own routing already uses) plus Box Office - no backend calls needed, this
// is a small static list, so no partial-failure case exists here (always complete).

header('Content-Type: application/xml; charset=utf-8');

ga_sitemap_serve('sitemap_categories', GA_CACHE_TTL, function () {
    $base = GA_SITEMAP_BASE_URL;
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    $xml .= ga_sitemap_url_entry($base . '/box-office', null, 'daily', '0.7');
    foreach (GA_CATEGORY_ROUTES as $route) {
        $xml .= ga_sitemap_url_entry($base . '/' . $route['urlPath'], null, 'hourly', '0.8');
    }

    $xml .= '</urlset>' . "\n";
    return ['xml' => $xml, 'complete' => true];
});
