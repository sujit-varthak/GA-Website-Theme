<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/api-client.php'; // ga_sitemap_serve() below needs ga_cache_lock_try()/_release()
require_once __DIR__ . '/inc/sitemap-helpers.php';

// Static (non-article, non-category) pages - just the homepage for now. Unlike the WordPress
// site's page-sitemap.xml (About Us, Disclaimer, Privacy, Contact, Grievance), this site has
// no local page for any of those yet - the footer still links out to
// www.greatandhra.com/about%20us/ etc. on the old WordPress domain (see 404.php's footer
// markup). Add each one here once it has a real local page to point to; listing them now
// would put 404-on-this-domain URLs in the sitemap.

header('Content-Type: application/xml; charset=utf-8');

ga_sitemap_serve('sitemap_pages', GA_CACHE_TTL, function () {
    $base = GA_SITEMAP_BASE_URL;
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    $xml .= ga_sitemap_url_entry($base . '/', null, 'hourly', '1.0');

    $xml .= '</urlset>' . "\n";
    return ['xml' => $xml, 'complete' => true];
});
