<?php

// Cache-busting query string for a static asset (CSS/JS), based on the file's own last-
// modified time instead of the current second (date('His') on index.php's stylesheet
// <link> tags meant the version string changed on every single request, so browsers could
// never cache those files across requests at all - load-audit fix #6, 2026-08-20). Only
// changes when the file itself actually changes, so normal browser caching works between
// deploys. Falls back to the current time if the file can't be found, so a missing/moved
// asset never breaks the page - it just loses the caching benefit for that one request.
function ga_asset_version(string $relativePath): string
{
    $fullPath = __DIR__ . '/../' . $relativePath;
    $mtime = @filemtime($fullPath);
    return (string) ($mtime !== false ? $mtime : time());
}

// Falls back to byte-based strlen/substr if the mbstring extension isn't loaded, instead of
// fatally crashing the whole page (happened live on a host whose PHP build didn't have
// mbstring enabled, composer.json now declares it so this shouldn't trigger again - but a
// missing extension should degrade a title's truncation, not take down the entire page).
// Byte-based truncation can split a multi-byte UTF-8 character (e.g. in Telugu text) - only
// relevant in that fallback case, mb_* is used whenever it's actually available.
function ga_truncate(?string $text, int $maxLength): string
{
    $hasMbstring = function_exists('mb_strlen');
    $strlen = $hasMbstring ? 'mb_strlen' : 'strlen';
    $substr = $hasMbstring ? 'mb_substr' : 'substr';
    $strrpos = $hasMbstring ? 'mb_strrpos' : 'strrpos';

    $text = trim((string) $text);
    if ($strlen($text) <= $maxLength) {
        return $text;
    }
    $truncated = $substr($text, 0, $maxLength);
    $lastSpace = $strrpos($truncated, ' ');
    if ($lastSpace !== false) {
        $truncated = $substr($truncated, 0, $lastSpace);
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

// `body` arrives in one of two shapes: already block-structured HTML (native articles,
// from the admin's contentEditable rich text editor - <div>/<span> blocks, or plain <p>
// tags), or plain-text paragraphs with at most inline-level tags mixed in (legacy-migrated
// articles with genuinely no HTML at all, and WordPress-XML-imported articles, whose raw
// `content:encoded` - stored verbatim by scripts/import-articles-from-xml.ts - commonly
// carries a stray <a>/<img>/<strong>/<br> from the original post alongside otherwise
// blank-line-separated plain text). Since the template renders body raw, the second shape's
// paragraph breaks were collapsing into one wall of text unless reformatted. Detect which
// shape we have (a real block-level tag = already structured) and only reformat the other.
//
// $adZone, when given, injects ga_render_ad($adZone)'s output between two paragraphs, in
// both cases above:
// - Plain text (+ inline tags): split on blank lines, count the resulting paragraphs.
// - Block HTML: parsed with DOMDocument (ga_inject_ad_into_html_body) rather than
//   string-split, since the rich text editor's actual output is inconsistent — plain <p>
//   tags for some articles, but for others a mix of <div><span>text</span></div> blocks with
//   blank-line spacer divs, and it sometimes nests a later paragraph's <div> inside an
//   earlier one's instead of as a sibling (a contentEditable quirk). String-splitting on
//   that would corrupt the markup; DOM manipulation finds and counts real paragraphs
//   regardless of nesting and inserts the ad as a proper sibling node.
//
// Either way, articles with at most GA_ARTICLE_MIDCONTENT_AD_SHORT_THRESHOLD paragraphs get
// the ad after the last one (i.e. at the end); longer articles get it at the midpoint.
function ga_render_article_body(?string $body, ?string $adZone = null): string
{
    $body = (string) $body;
    if (trim($body) === '') {
        return '';
    }

    // A real block-level tag (native contentEditable output always wraps each
    // paragraph in its own <div>) means the body already has genuine paragraph
    // structure - render it as-is via the DOM-based path below. A body with only
    // inline tags (<a>/<img>/<strong>/<em>/<br>/bare <span>) is the WordPress-XML-
    // import case: raw `content:encoded`, stored verbatim by
    // scripts/import-articles-from-xml.ts - plain-text paragraphs (blank-line
    // separated) with occasional inline HTML left in from the original WordPress
    // content, never run through WordPress's own wpautop() or anything
    // equivalent. Treating "has any tag at all" as "already formatted" (the
    // previous check) meant a single inline tag anywhere in an otherwise
    // plain-text body skipped paragraph-wrapping entirely, collapsing the whole
    // article into one unbroken blob - this is what was reported.
    if (preg_match('/<(div|p|ul|ol|li|blockquote|h[1-6]|table|pre|figure)\b/i', $body)) {
        return $adZone !== null ? ga_inject_ad_into_html_body($body, $adZone) : $body;
    }

    $paragraphs = array_values(array_filter(
        array_map('trim', preg_split('/\r\n\s*\r\n|\n\s*\n/', trim($body))),
        function ($paragraph) {
            return $paragraph !== '';
        }
    ));

    $adHtml = '';
    if ($adZone !== null) {
        ob_start();
        ga_render_ad($adZone);
        $adHtml = ob_get_clean();
    }

    $count = count($paragraphs);
    $insertAfter = ($adHtml === '' || $count === 0)
        ? null
        : ($count <= GA_ARTICLE_MIDCONTENT_AD_SHORT_THRESHOLD ? $count : (int) floor($count / 2));

    // Not run through ga_e() - a paragraph here may carry genuine inline HTML
    // (a WordPress-imported <a> link, <strong>, etc.) that escaping would turn
    // into visible literal text instead of real markup. body is admin/import-
    // controlled content (RBAC-gated CMS, not raw public input), same trust
    // boundary the already-HTML branch above already renders unescaped.
    $html = '';
    foreach ($paragraphs as $i => $paragraph) {
        $html .= '<p>' . nl2br($paragraph) . '</p>';
        if ($insertAfter !== null && ($i + 1) === $insertAfter) {
            $html .= $adHtml;
        }
    }
    return $html;
}

// HTML-body counterpart to the plain-text paragraph splitting above. "Paragraph" here means
// any <p> or <div> node with real text content and no <p>/<div> of its own nested inside it
// (a leaf content block) — found via XPath so mixed <p>/<div> siblings stay in true document
// order, unlike collecting each tag separately. Blank-line spacer divs (just a bare <br>) have
// no text content and are correctly skipped. The ad is inserted as a new sibling <div> right
// after the target paragraph's own node, wherever that node actually lives in the tree — so a
// paragraph the editor happened to nest inside an earlier one still gets the ad placed
// immediately after it, not at the wrong depth.
function ga_inject_ad_into_html_body(string $body, string $adZone): string
{
    $previousLibxmlSetting = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8"?><html><body>' . $body . '</body></html>');
    libxml_clear_errors();
    libxml_use_internal_errors($previousLibxmlSetting);

    $bodyNode = $dom->getElementsByTagName('body')->item(0);
    if ($bodyNode === null) {
        return $body;
    }

    $xpath = new DOMXPath($dom);
    $candidates = $xpath->query('.//p | .//div', $bodyNode);

    $paragraphs = [];
    if ($candidates !== false) {
        foreach ($candidates as $el) {
            if (trim($el->textContent) === '') {
                continue;
            }
            $hasNestedBlock = $el->getElementsByTagName('p')->length > 0
                || $el->getElementsByTagName('div')->length > 0;
            if ($hasNestedBlock) {
                continue;
            }
            $paragraphs[] = $el;
        }
    }

    $count = count($paragraphs);
    if ($count === 0) {
        return $body;
    }

    ob_start();
    ga_render_ad($adZone);
    $adHtml = ob_get_clean();
    if ($adHtml === '') {
        return $body;
    }

    $insertAfter = $count <= GA_ARTICLE_MIDCONTENT_AD_SHORT_THRESHOLD ? $count : (int) floor($count / 2);
    $targetNode = $paragraphs[$insertAfter - 1];

    $previousLibxmlSetting = libxml_use_internal_errors(true);
    $adDom = new DOMDocument();
    $adDom->loadHTML('<?xml encoding="utf-8"?><html><body>' . $adHtml . '</body></html>');
    libxml_clear_errors();
    libxml_use_internal_errors($previousLibxmlSetting);
    $adBodyNode = $adDom->getElementsByTagName('body')->item(0);

    $adWrapper = $dom->createElement('div');
    $adWrapper->setAttribute('class', 'article-midcontent-ad');
    if ($adBodyNode !== null) {
        foreach ($adBodyNode->childNodes as $child) {
            $adWrapper->appendChild($dom->importNode($child, true));
        }
    }

    $targetNode->parentNode->insertBefore($adWrapper, $targetNode->nextSibling);

    $html = '';
    foreach ($bodyNode->childNodes as $child) {
        $html .= $dom->saveHTML($child);
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
// output the stored embed code as-is — trusted admin-authored content, not user input. No
// "Advertisement" caption is ever printed — removed site-wide per admin request.
// $dimensionZone overrides which GA_AD_ZONE_IMAGE_DIMENSIONS entry sizes the <img> — for a
// call site that fetches one zone's ad but needs a different fixed size than that zone's own
// primary placement (e.g. the homepage phone-view banner reuses HOMEPAGE_TOP_BANNER's ad but
// sizes it like the old HOMEPAGE_MOBILE_BANNER slot, not the 728x90 desktop banner).
function ga_render_ad(string $zone, ?string $dimensionZone = null): void
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
    // Inline !important styles (not just bare HTML width/height attributes) so a zone's
    // fixed size always wins over broad container-level image resets elsewhere in the CSS
    // (e.g. mobile-responsive.css's ".great_andhra_main_body_container img { height: auto
    // !important }") regardless of which page/container that zone happens to render inside.
    $dims = GA_AD_ZONE_IMAGE_DIMENSIONS[$dimensionZone ?? $zone] ?? null;
    $imgAttrs = '';
    $styleParts = [];
    if ($dims !== null) {
        if ($dims['width'] !== null) {
            $imgAttrs .= ' width="' . (int) $dims['width'] . '"';
            $styleParts[] = 'width: ' . (int) $dims['width'] . 'px !important';
        } else {
            $styleParts[] = 'max-width: 100% !important';
        }
        if ($dims['height'] !== null) {
            $imgAttrs .= ' height="' . (int) $dims['height'] . '"';
            $styleParts[] = 'height: ' . (int) $dims['height'] . 'px !important';
        }
    } else {
        $styleParts[] = 'max-width: 100% !important';
        $styleParts[] = 'height: auto !important';
    }
    $imgAttrs .= ' style="' . implode('; ', $styleParts) . ';"';

    if ($landingUrl !== '') {
        echo '<a href="' . ga_e($landingUrl) . '" target="_blank" rel="noopener">';
    }
    echo '<img alt="' . ga_e($name) . '" src="' . ga_e($imageUrl) . '"' . $imgAttrs . ' border="0" />';
    if ($landingUrl !== '') {
        echo '</a>';
    }
}
