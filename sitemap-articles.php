<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/api-client.php';
require_once __DIR__ . '/inc/sitemap-helpers.php';

// Every published article, walked GA_MAX_TAKE=100 at a time via the same public
// /api/public/articles endpoint every list page already calls (urlPath and publishedAt are
// already in its default response shape, no extra backend work needed). ~2,900 articles today
// comfortably fits in one file under Google's 50,000-URL/50MB sitemap limit - revisit
// (chunk into sitemap-articles-2.xml etc., listed from sitemap_index.php) if the catalog
// grows an order of magnitude past that.

header('Content-Type: application/xml; charset=utf-8');

ga_sitemap_serve('sitemap_articles', GA_CACHE_TTL, function () {
    $base = GA_SITEMAP_BASE_URL;
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    $skip = 0;
    $take = 100;
    $total = 0;
    $complete = true;
    do {
        $result = ga_fetch_articles($take, $skip);
        if ($result === null) {
            // Backend unreachable and no cache at all for this particular page - stop walking
            // rather than erroring the whole sitemap. $complete below decides whether this
            // partial result is safe to cache (it isn't).
            $complete = false;
            break;
        }
        $total = (int) ($result['total'] ?? 0);
        foreach ($result['items'] ?? [] as $article) {
            if (empty($article['urlPath'])) {
                continue;
            }
            $lastmod = null;
            if (!empty($article['publishedAt'])) {
                try {
                    $lastmod = (new DateTime($article['publishedAt']))->format('c');
                } catch (Exception) {
                }
            }
            $xml .= ga_sitemap_url_entry($base . '/' . $article['urlPath'], $lastmod, 'weekly', '0.6');
        }
        $skip += $take;
    } while ($skip < $total);

    $xml .= '</urlset>' . "\n";
    return ['xml' => $xml, 'complete' => $complete];
});
