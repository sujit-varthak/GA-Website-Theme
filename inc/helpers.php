<?php

function ga_truncate(?string $text, int $maxLength): string
{
    $text = trim((string) $text);
    if (mb_strlen($text) <= $maxLength) {
        return $text;
    }
    $truncated = mb_substr($text, 0, $maxLength);
    $lastSpace = mb_strrpos($truncated, ' ');
    if ($lastSpace !== false) {
        $truncated = mb_substr($truncated, 0, $lastSpace);
    }
    return rtrim($truncated) . '…';
}

// Returns ['src' => ..., 'width' => ..., 'height' => ..., 'is_fallback' => bool]
function ga_image(array $article, array $fallback): array
{
    $url = $article['featuredImageUrl'] ?? null;
    if (!empty($url)) {
        return [
            'src' => $url,
            'width' => $fallback['width'],
            'height' => $fallback['height'],
            'is_fallback' => false,
        ];
    }
    return [
        'src' => $fallback['src'],
        'width' => $fallback['width'],
        'height' => $fallback['height'],
        'is_fallback' => true,
    ];
}

// urlPath is "{shortId}/{categorySlug}/{subCategorySlug?}/{titleSlug}". The backend's
// /api/public/articles/:id now accepts either the shortId or the full UUID (confirmed
// 2026-07-31, auto-detected by format) — so urlPath is used verbatim, no more substituting
// the UUID in. Path-based, no query string.
// Bare form (no "inner-page.php/" prefix) as of the .htaccess rewrite — root-relative, safe
// on every page that calls this: index/list-page/box-office are always one level deep at
// root, and inner-page.php itself carries a <base href="/"> specifically so its own deep
// PATH_INFO URL doesn't throw off this same relative link when linking to related articles.
function ga_inner_link(array $article): string
{
    $urlPath = $article['urlPath'] ?? '';

    if ($urlPath !== '') {
        $segments = explode('/', trim($urlPath, '/'));
        return implode('/', array_map('rawurlencode', $segments));
    }

    // Fallback if urlPath is ever missing (e.g. stale cache from before it shipped).
    $id = $article['id'] ?? '';
    $slug = $article['slug'] ?? '';
    $query = 'id=' . rawurlencode($id);
    if ($slug !== '') {
        $query .= '&slug=' . rawurlencode($slug);
    }
    return 'inner-page.php?' . $query;
}

// Builds a nav link to list-page.php for a category (key into GA_NAV_CATEGORY_IDS). Emits the
// clean GA_CATEGORY_ROUTES path (e.g. "movies/gossip") when one exists for this key; falls back
// to the old ?categoryId=... query-string form for any category not yet added there, so a
// category can be migrated to the clean scheme one at a time without breaking the others.
// $categoryName/$includeChildren only matter on that fallback path — GA_CATEGORY_ROUTES already
// carries both for anything routed through the clean form.
function ga_nav_category_link(string $categoryKey, string $categoryName, bool $includeChildren = false): string
{
    $route = GA_CATEGORY_ROUTES[$categoryKey] ?? null;
    if ($route !== null) {
        return $route['urlPath'];
    }

    $id = GA_NAV_CATEGORY_IDS[$categoryKey] ?? '';
    $url = 'list-page.php?categoryId=' . rawurlencode($id) . '&categoryName=' . rawurlencode($categoryName);
    if ($includeChildren) {
        $url .= '&includeChildren=true';
    }
    return $url;
}

// Resolves a clean category URL path ("movies/gossip", "politics", ...) back to the
// categoryId/name/includeChildren list-page.php needs — the single source both its PATH_INFO
// parsing and the old ?categoryId=... redirect (via ga_category_path_for_id() below) key off of.
// Returns null for anything not in GA_CATEGORY_ROUTES (caller 404s).
function ga_resolve_category_path(string $path): ?array
{
    foreach (GA_CATEGORY_ROUTES as $key => $route) {
        if ($route['urlPath'] === $path) {
            return [
                'id' => GA_NAV_CATEGORY_IDS[$key] ?? '',
                'name' => $route['name'],
                'includeChildren' => $route['includeChildren'],
            ];
        }
    }
    return null;
}

// Reverse of ga_resolve_category_path(): given an old link's ?categoryId=..., finds its clean
// URL path for the 301 redirect. Null if that ID isn't in GA_CATEGORY_ROUTES yet.
function ga_category_path_for_id(string $categoryId): ?string
{
    $key = array_search($categoryId, GA_NAV_CATEGORY_IDS, true);
    if ($key === false || !isset(GA_CATEGORY_ROUTES[$key])) {
        return null;
    }
    return GA_CATEGORY_ROUTES[$key]['urlPath'];
}

// The backend's tag.slug is unique/canonical (confirmed live 2026-08-01 — zero collisions
// across the full 2251-tag list), so /tag/{slug} URLs need no ID at all. Both scan the same
// cached full list (ga_fetch_all_tags() in inc/api-client.php) — there's no slug-based filter
// on the API (confirmed: /api/public/tags?slug=... and /api/public/articles?tagSlug=... both
// silently ignore the param and return everything), so resolution happens client-side. Cheap
// even at 2000+ tags (one linear scan of small associative arrays).
function ga_find_tag_by_slug(string $slug): ?array
{
    foreach (ga_fetch_all_tags() as $tag) {
        if (($tag['slug'] ?? '') === $slug) {
            return $tag;
        }
    }
    return null;
}

// Reverse lookup, used to 301 the earlier tag/{id}/{slug} scheme (built before the slug field
// was confirmed) and old ?tagId=... links to the current pure-slug /tag/{slug} form.
function ga_find_tag_by_id(string $tagId): ?array
{
    foreach (ga_fetch_all_tags() as $tag) {
        if (($tag['id'] ?? '') === $tagId) {
            return $tag;
        }
    }
    return null;
}

// Builds a link to list-page.php for a tag (e.g. from Top Trending Topics). $tagSlug should be
// the tag's own real slug field from the API — not re-derived here — so it round-trips exactly
// through ga_find_tag_by_slug() above.
function ga_tag_link(string $tagSlug): string
{
    return 'tag/' . rawurlencode($tagSlug);
}

function ga_e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Migrated articles store `body` as plain text (blank-line-separated paragraphs, no HTML
// at all), while native articles already store it as real HTML (<div>/<span> blocks). Since
// the template renders body raw, plain-text paragraph breaks were collapsing into one wall
// of text. Detect which shape we have and only reformat the plain-text case — HTML bodies
// pass through untouched. Fixes every current and future plain-text article with no data migration.
function ga_render_article_body(?string $body): string
{
    $body = (string) $body;
    if (trim($body) === '') {
        return '';
    }

    // Already has real HTML tags — leave it exactly as-is.
    if ($body !== strip_tags($body)) {
        return $body;
    }

    $paragraphs = preg_split('/\r\n\s*\r\n|\n\s*\n/', trim($body));
    $html = '';
    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);
        if ($paragraph === '') {
            continue;
        }
        $html .= '<p>' . nl2br(ga_e($paragraph)) . '</p>';
    }
    return $html;
}

// excerpt is null for almost every article (confirmed 2026-07-31) — fall back to a plain-text
// preview of body, same "field is empty, use what IS available" pattern as the image fallback.
function ga_article_excerpt(array $article, int $maxLength): string
{
    $excerpt = trim((string) ($article['excerpt'] ?? ''));
    if ($excerpt !== '') {
        return ga_truncate($excerpt, $maxLength);
    }

    $body = (string) ($article['body'] ?? '');
    $plainBody = trim(preg_replace('/\s+/', ' ', strip_tags($body)));
    return ga_truncate($plainBody, $maxLength);
}

function ga_format_date(?string $iso, string $format = 'F d, Y'): string
{
    if (empty($iso)) {
        return '';
    }
    try {
        $dt = new DateTime($iso);
    } catch (Exception) {
        return '';
    }
    return $dt->format($format);
}

// Shared by all 6 homepage category sections (Movie News, Movie Gossip, Andhra News,
// Telangana News, Gossip, Reviews) — identical markup pattern in every one: a hero image
// for the first article, then all fetched articles again as plain headline links (the repeat
// of article 0 as the first, CSS-bolded link is intentional, matching the static design).
function ga_render_category_section(array $articles, array $heroFallbackImage, int $titleMax): void
{
    if (empty($articles)) {
        echo '<li class="main-story clearfix ga-unavailable"><p class="ga-unavailable-msg">Content temporarily unavailable</p></li>';
        return;
    }

    $hero = $articles[0];
    $heroImg = ga_image($hero, $heroFallbackImage);
    $heroTitleAttr = ga_e($hero['title'] ?? '');
    $heroLink = ga_e(ga_inner_link($hero));

    echo '<li class="main-story clearfix">';
    echo '<a href="' . $heroLink . '" title="' . $heroTitleAttr . '">';
    echo '<img alt="' . $heroTitleAttr . '" height="' . (int) $heroImg['height'] . '" src="' . ga_e($heroImg['src']) . '" width="' . (int) $heroImg['width'] . '" />';
    echo '</a></li>';

    foreach ($articles as $article) {
        $link = ga_e(ga_inner_link($article));
        $titleAttr = ga_e($article['title'] ?? '');
        $titleText = ga_e(ga_truncate($article['title'] ?? '', $titleMax));
        echo '<li> <a class="oneline-title" href="' . $link . '" title="' . $titleAttr . '">' . $titleText . '</a> </li>';
    }
}

// Homepage "Editor's Pick" card only features articles with schemaData.movieName and
// schemaData.rating populated (most Reviews articles don't have this yet). Returns the most
// recently published qualifying article, or null if none of $articles qualify.
function ga_pick_editors_review(array $articles): ?array
{
    $qualifying = array_filter($articles, function ($article) {
        $schema = $article['schemaData'] ?? null;
        return is_array($schema) && !empty($schema['movieName']) && !empty($schema['rating']);
    });

    if (empty($qualifying)) {
        return null;
    }

    usort($qualifying, function ($a, $b) {
        return strtotime($b['publishedAt'] ?? '') <=> strtotime($a['publishedAt'] ?? '');
    });

    return $qualifying[0];
}

// tags[] comes as [{tag:{id,name,slug}}] — a join-table artifact, unwrap it here.
function ga_tag_names(array $article): array
{
    $names = [];
    foreach (($article['tags'] ?? []) as $entry) {
        $name = $entry['tag']['name'] ?? null;
        if ($name) {
            $names[] = $name;
        }
    }
    return $names;
}

// Only "/relative/paths" starting with a single slash are allowed as a roadblock-ad return
// target — blocks open-redirect abuse via a crafted ?return= (e.g. //evil.com or https://evil.com).
function ga_sanitize_local_path(?string $path, string $fallback = '/'): string
{
    $path = (string) $path;
    if ($path === '' || $path[0] !== '/' || (isset($path[1]) && $path[1] === '/') || strpos($path, '://') !== false) {
        return $fallback;
    }
    return $path;
}

// Called at the top of every page (before any API fetch or output): first visit in this
// cookie window gets redirected to the roadblock ad instead of the page they asked for;
// advertisement.php sends them on to their original destination once the ad's done.
// Skips entirely once the cookie is set — the cookie's own TTL is the admin-managed roadblock
// ad's roadblockCookieTTL when one is active (falls back to GA_ROADBLOCK_COOKIE_TTL otherwise),
// so a "show again after N minutes" edit in the admin panel takes effect on the very next visit.
function ga_maybe_show_roadblock_ad(): void
{
    if (!GA_ROADBLOCK_AD_ENABLED || isset($_COOKIE[GA_ROADBLOCK_COOKIE_NAME])) {
        return;
    }

    require_once __DIR__ . '/api-client.php';
    $ad = ga_fetch_roadblock_ad(!ga_is_mobile());
    $ad = $ad ?? (GA_AD_FALLBACKS['ROADBLOCK'] ?? null);

    // No active roadblock ad in the admin panel and no fallback configured — skip the
    // interstitial entirely rather than redirect to an empty ad page.
    if ($ad === null) {
        return;
    }

    $cookieTtl = (int) ($ad['roadblockCookieTTL'] ?? GA_ROADBLOCK_COOKIE_TTL);
    setcookie(GA_ROADBLOCK_COOKIE_NAME, '1', time() + $cookieTtl, '/');

    $returnTo = ga_sanitize_local_path($_SERVER['REQUEST_URI'] ?? '/');
    header('Location: /advertisement.php?return=' . rawurlencode($returnTo));
    exit;
}

// Detects a mobile user agent (used to pick imageUrlMobile vs imageUrlDesktop and the
// showOnMobile/showOnDesktop zone toggle). Memoized — called from multiple places per request.
function ga_is_mobile(): bool
{
    static $isMobile = null;
    if ($isMobile !== null) {
        return $isMobile;
    }
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $isMobile = (bool) preg_match('/Mobi|Android|iPhone|iPad|iPod|BlackBerry|Windows Phone/i', $userAgent);
    return $isMobile;
}

// Renders one ad slot for $zone (an AdZone string matching the backend's enum, e.g.
// 'HOMEPAGE_SIDEBAR_LEFT'): fetches the active admin-managed ad, falls back to
// GA_AD_FALLBACKS[$zone] if none is active, and renders nothing if neither exists. IMAGE ads
// render as a linked <img> (desktop or mobile source based on ga_is_mobile()); SCRIPT ads
// output the stored embed code as-is — trusted admin-authored content, not user input.
// $showLabel controls the "Advertisement" caption above the image — most zones had one in
// their original static markup, but a few (the above-header banner, the 3-ad strip row, the
// big-story banner) were always plain linked images with no label, so those pass false.
function ga_render_ad(string $zone, bool $showLabel = true): void
{
    $isMobile = ga_is_mobile();
    $ad = ga_fetch_ad($zone, !$isMobile);
    $ad = $ad ?? (GA_AD_FALLBACKS[$zone] ?? null);

    if ($ad === null) {
        return;
    }

    if (($ad['type'] ?? 'IMAGE') === 'SCRIPT') {
        $script = $ad['scriptCode'] ?? '';
        if ($script !== '') {
            echo $script;
        }
        return;
    }

    $imageUrl = $isMobile
        ? ($ad['imageUrlMobile'] ?? $ad['imageUrlDesktop'] ?? '')
        : ($ad['imageUrlDesktop'] ?? $ad['imageUrlMobile'] ?? '');
    if ($imageUrl === '') {
        return;
    }

    $landingUrl = $ad['landingUrl'] ?? '';
    $name = $ad['name'] ?? 'Advertisement';

    // Fixed width/height attributes per zone (GA_AD_ZONE_IMAGE_DIMENSIONS), matching what was
    // hardcoded in the static markup this replaced — e.g. sidebar images are width="160"
    // regardless of their fixed-position wrapper's narrower width, not stretched to fit it.
    // Zones with no entry (e.g. HOMEPAGE_SECTION_INLINE, which never had an explicit size)
    // render responsively instead.
    $dims = GA_AD_ZONE_IMAGE_DIMENSIONS[$zone] ?? null;
    $imgAttrs = '';
    if ($dims !== null) {
        if ($dims['width'] !== null) {
            $imgAttrs .= ' width="' . (int) $dims['width'] . '"';
        }
        if ($dims['height'] !== null) {
            $imgAttrs .= ' height="' . (int) $dims['height'] . '"';
        }
        $style = $dims['width'] === null ? 'max-width: 100%;' : '';
    } else {
        $style = 'max-width: 100%; height: auto;';
    }
    if ($style !== '') {
        $imgAttrs .= ' style="' . $style . '"';
    }

    if ($showLabel) {
        echo '<p style="font-size: 11px;text-align: center;">Advertisement</p>';
    }
    if ($landingUrl !== '') {
        echo '<a href="' . ga_e($landingUrl) . '" target="_blank" rel="noopener">';
    }
    echo '<img alt="' . ga_e($name) . '" src="' . ga_e($imageUrl) . '"' . $imgAttrs . ' border="0" />';
    if ($landingUrl !== '') {
        echo '</a>';
    }
}
