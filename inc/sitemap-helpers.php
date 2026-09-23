<?php
// Shared building blocks for every sitemap file (the index and each type-specific
// sitemap-*.php) - the cache-lock-serve-or-regenerate pattern here is the exact same one
// sitemap.php used before it was split up (fixed for two edge cases earlier: a partial
// backend failure caching as if complete, and a cold-cache race triggering duplicate
// regeneration). Centralizing it means every sitemap file gets those same guarantees for
// free instead of re-implementing them once per file.

function ga_sitemap_xml_escape(string $s): string
{
    return htmlspecialchars($s, ENT_XML1, 'UTF-8');
}

function ga_sitemap_url_entry(string $loc, ?string $lastmod, string $changefreq, string $priority): string
{
    $xml = "  <url>\n    <loc>" . ga_sitemap_xml_escape($loc) . "</loc>\n";
    if (!empty($lastmod)) {
        $xml .= "    <lastmod>" . ga_sitemap_xml_escape($lastmod) . "</lastmod>\n";
    }
    $xml .= "    <changefreq>{$changefreq}</changefreq>\n";
    $xml .= "    <priority>{$priority}</priority>\n";
    $xml .= "  </url>\n";
    return $xml;
}

// Google News sitemap entry (news-sitemap.xml only) - a different schema from the regular
// sitemap protocol (news:* namespace, no changefreq/priority, title in a CDATA block rather
// than escaped - matches how Yoast/Google itself emits it, and sidesteps needing to escape
// titles that already contain raw HTML entities like &#8217;).
function ga_sitemap_news_entry(string $loc, string $title, string $publicationDate): string
{
    $xml = "  <url>\n    <loc>" . ga_sitemap_xml_escape($loc) . "</loc>\n";
    $xml .= "    <news:news>\n";
    $xml .= "      <news:publication>\n";
    $xml .= "        <news:name>" . ga_sitemap_xml_escape(GA_SITE_NAME) . "</news:name>\n";
    $xml .= "        <news:language>" . ga_sitemap_xml_escape(GA_SITE_LANGUAGE) . "</news:language>\n";
    $xml .= "      </news:publication>\n";
    $xml .= "      <news:publication_date>" . ga_sitemap_xml_escape($publicationDate) . "</news:publication_date>\n";
    $xml .= "      <news:title><![CDATA[{$title}]]></news:title>\n";
    $xml .= "    </news:news>\n";
    $xml .= "  </url>\n";
    return $xml;
}

// Generic cache-lock-serve-or-regenerate wrapper, shared by every sitemap file. $generator
// returns ['xml' => string, 'complete' => bool] - complete=false means a backend call failed
// mid-generation and this partial result must never be cached, only served as a last resort
// when there's truly nothing else (same rule sitemap.php's article walk already followed).
// Writes nothing to output itself beyond the XML body - callers set Content-Type and print
// the XML prolog and root element around whatever $generator produces.
function ga_sitemap_serve(string $cacheKey, int $ttl, callable $generator): void
{
    $cacheFile = rtrim(GA_CACHE_DIR, '/\\') . '/' . $cacheKey . '.xml';

    $isFresh = is_file($cacheFile) && (time() - filemtime($cacheFile)) < $ttl;
    if ($isFresh) {
        readfile($cacheFile);
        return;
    }

    $lock = ga_cache_lock_try($cacheFile);
    if ($lock === null) {
        // Someone else is already regenerating - serve stale rather than pile on a second
        // regeneration, same as sitemap.php's original lock-miss handling. If this is the
        // very first-ever generation (no file yet either), briefly retry for the lock instead
        // of immediately generating twice in parallel.
        for ($wait = 0; $lock === null && !is_file($cacheFile) && $wait < 5; $wait++) {
            usleep(500000);
            $lock = ga_cache_lock_try($cacheFile);
        }
        if ($lock === null && is_file($cacheFile)) {
            readfile($cacheFile);
            return;
        }
    }

    $result = $generator();
    $xml = $result['xml'];
    $complete = $result['complete'] ?? true;

    if ($complete) {
        file_put_contents($cacheFile, $xml, LOCK_EX);
        ga_cache_lock_release($lock);
        echo $xml;
    } else {
        ga_cache_lock_release($lock);
        if (is_file($cacheFile)) {
            readfile($cacheFile);
        } else {
            echo $xml;
        }
    }
}

// mtime of a cached sitemap file, for the index's own <lastmod> per sub-sitemap - falls back
// to "now" when a type hasn't been generated yet (e.g. right after a fresh cache clear), since
// there's no real prior generation time to report.
function ga_sitemap_cache_mtime(string $cacheKey): string
{
    $cacheFile = rtrim(GA_CACHE_DIR, '/\\') . '/' . $cacheKey . '.xml';
    $time = is_file($cacheFile) ? filemtime($cacheFile) : time();
    return (new DateTime('@' . $time))->format('c');
}
