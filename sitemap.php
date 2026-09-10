<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/api-client.php';

// Full site XML sitemap - homepage, box office, every clean-URL category (GA_CATEGORY_ROUTES,
// the same single source of truth list-page.php's own routing already uses), and every
// published article (walked GA_MAX_TAKE=100 at a time via the same public /api/public/articles
// endpoint every list page already calls - urlPath and publishedAt are already in its default
// response shape, no extra backend work needed). ~2,900 articles today comfortably fits in one
// file under Google's 50,000-URL/50MB sitemap limit, so this stays a single <urlset> rather than
// a sitemap index with child files - revisit if the catalog grows an order of magnitude.
//
// Cached to a flat file (same non-blocking flock() pattern as ga_fetch_articles()'s per-page
// cache) since generating this means ~30 sequential backend calls - without caching, every
// crawler hit would pay that cost. 1 hour is plenty fresh for a sitemap; Google re-crawls it on
// its own schedule regardless of how often the file itself changes.

header('Content-Type: application/xml; charset=utf-8');

$ga_sitemap_cache_file = rtrim(GA_CACHE_DIR, '/\\') . '/sitemap.xml';
$ga_sitemap_ttl = 3600;

$ga_is_fresh = is_file($ga_sitemap_cache_file) && (time() - filemtime($ga_sitemap_cache_file)) < $ga_sitemap_ttl;
if ($ga_is_fresh) {
    readfile($ga_sitemap_cache_file);
    exit;
}

$ga_lock = ga_cache_lock_try($ga_sitemap_cache_file);
if ($ga_lock === null && is_file($ga_sitemap_cache_file)) {
    // Someone else is already regenerating - serve the existing file even if stale rather
    // than piling on a second full ~30-call regeneration.
    readfile($ga_sitemap_cache_file);
    exit;
}

function ga_sitemap_url_entry(string $loc, ?string $lastmod, string $changefreq, string $priority): string
{
    $xml = "  <url>\n    <loc>" . htmlspecialchars($loc, ENT_XML1) . "</loc>\n";
    if (!empty($lastmod)) {
        $xml .= "    <lastmod>" . htmlspecialchars($lastmod, ENT_XML1) . "</lastmod>\n";
    }
    $xml .= "    <changefreq>{$changefreq}</changefreq>\n";
    $xml .= "    <priority>{$priority}</priority>\n";
    $xml .= "  </url>\n";
    return $xml;
}

$ga_base = 'https://www.greatandhra.com';
$ga_xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$ga_xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$ga_xml .= ga_sitemap_url_entry($ga_base . '/', null, 'hourly', '1.0');
$ga_xml .= ga_sitemap_url_entry($ga_base . '/box-office', null, 'daily', '0.7');

foreach (GA_CATEGORY_ROUTES as $ga_route) {
    $ga_xml .= ga_sitemap_url_entry($ga_base . '/' . $ga_route['urlPath'], null, 'hourly', '0.8');
}

$ga_skip = 0;
$ga_take = 100;
$ga_total = 0;
do {
    $ga_result = ga_fetch_articles($ga_take, $ga_skip);
    if ($ga_result === null) {
        // Backend unreachable and no cache at all for this particular page - stop walking
        // rather than erroring the whole sitemap; whatever was gathered before this still ships.
        break;
    }
    $ga_total = (int) ($ga_result['total'] ?? 0);
    foreach ($ga_result['items'] ?? [] as $ga_article) {
        if (empty($ga_article['urlPath'])) {
            continue;
        }
        $ga_lastmod = null;
        if (!empty($ga_article['publishedAt'])) {
            try {
                $ga_lastmod = (new DateTime($ga_article['publishedAt']))->format('c');
            } catch (Exception) {
            }
        }
        $ga_xml .= ga_sitemap_url_entry($ga_base . '/' . $ga_article['urlPath'], $ga_lastmod, 'weekly', '0.6');
    }
    $ga_skip += $ga_take;
} while ($ga_skip < $ga_total);

$ga_xml .= '</urlset>' . "\n";

file_put_contents($ga_sitemap_cache_file, $ga_xml, LOCK_EX);
ga_cache_lock_release($ga_lock);

echo $ga_xml;
