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

// Builds a nav link to list-page.php for a category (key into GA_NAV_CATEGORY_IDS).
// $categoryName rides along so list-page.php can show a real header even with 0 results,
// without needing a separate categories lookup. $includeChildren=true is for parent
// categories (Politics/Movies) whose own listing should also include their children's articles.
function ga_nav_category_link(string $categoryKey, string $categoryName, bool $includeChildren = false): string
{
    $id = GA_NAV_CATEGORY_IDS[$categoryKey] ?? '';
    $url = 'list-page.php?categoryId=' . rawurlencode($id) . '&categoryName=' . rawurlencode($categoryName);
    if ($includeChildren) {
        $url .= '&includeChildren=true';
    }
    return $url;
}

// Builds a link to list-page.php for a tag (e.g. from Top Trending Topics). tagId filtering
// is confirmed working live 2026-08-02 — was silently ignored before that.
function ga_tag_link(string $tagId, string $tagName): string
{
    return 'list-page.php?tagId=' . rawurlencode($tagId) . '&tagName=' . rawurlencode($tagName);
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
// Skips entirely once the cookie is set, so it only ever fires once per GA_ROADBLOCK_COOKIE_TTL.
function ga_maybe_show_roadblock_ad(): void
{
    if (!GA_ROADBLOCK_AD_ENABLED || isset($_COOKIE[GA_ROADBLOCK_COOKIE_NAME])) {
        return;
    }

    setcookie(GA_ROADBLOCK_COOKIE_NAME, '1', time() + GA_ROADBLOCK_COOKIE_TTL, '/');

    $returnTo = ga_sanitize_local_path($_SERVER['REQUEST_URI'] ?? '/');
    header('Location: /advertisement.php?return=' . rawurlencode($returnTo));
    exit;
}
