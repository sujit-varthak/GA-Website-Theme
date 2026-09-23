<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/api-client.php';
require_once __DIR__ . '/inc/sitemap-helpers.php';

// Google News sitemap - per Google's own News sitemap guidance, only articles published in
// roughly the last 48 hours belong here (older articles are for the regular sitemaps above,
// not this one). ga_fetch_articles() already returns items ordered publishedAt desc (same
// order every other list on the site relies on), so this walks pages only until the first
// article older than the cutoff - no need to paginate through the full catalog just to
// filter most of it back out.
//
// Cached far more briefly than the other sitemaps: this one is meant to reflect "what's
// fresh right now", and unlike the others a stale hour-old cache here could mean a
// just-published article misses its best window for Google News pickup.

header('Content-Type: application/xml; charset=utf-8');

const GA_NEWS_SITEMAP_WINDOW_HOURS = 48;
const GA_NEWS_SITEMAP_TTL = 300; // seconds

ga_sitemap_serve('news_sitemap', GA_NEWS_SITEMAP_TTL, function () {
    $base = GA_SITEMAP_BASE_URL;
    $cutoff = (new DateTime())->modify('-' . GA_NEWS_SITEMAP_WINDOW_HOURS . ' hours');

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" '
        . 'xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";

    $skip = 0;
    $take = 100;
    $total = 0;
    $complete = true;
    do {
        $result = ga_fetch_articles($take, $skip);
        if ($result === null) {
            $complete = false;
            break;
        }
        $total = (int) ($result['total'] ?? 0);
        foreach ($result['items'] ?? [] as $article) {
            if (empty($article['urlPath']) || empty($article['publishedAt']) || empty($article['title'])) {
                continue;
            }
            try {
                $publishedAt = new DateTime($article['publishedAt']);
            } catch (Exception) {
                continue;
            }
            if ($publishedAt < $cutoff) {
                // Results are publishedAt-descending, so every item from here on is also
                // outside the window - stop walking entirely rather than keep paginating
                // through the rest of the catalog just to discard it.
                break 2;
            }
            $xml .= ga_sitemap_news_entry(
                $base . '/' . $article['urlPath'],
                $article['title'],
                $publishedAt->format('c')
            );
        }
        $skip += $take;
    } while ($skip < $total);

    $xml .= '</urlset>' . "\n";
    return ['xml' => $xml, 'complete' => $complete];
});
