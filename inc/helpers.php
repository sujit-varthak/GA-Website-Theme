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

// urlPath is "{shortId}/{categorySlug}/{subCategorySlug?}/{titleSlug}" — but shortId alone
// doesn't resolve via /api/public/articles/:id (confirmed 404, backend lookup is UUID-only
// for now), so we reuse the backend's own urlPath structure (category/subcategory/title,
// slugification and all) and swap its first segment for the real UUID, which is what
// inner-page.php actually needs to fetch the article. Path-based, no query string.
function ga_inner_link(array $article): string
{
    $id = $article['id'] ?? '';
    $urlPath = $article['urlPath'] ?? '';

    if ($urlPath !== '') {
        $segments = explode('/', trim($urlPath, '/'));
        $segments[0] = $id;
        return 'inner-page.php/' . implode('/', array_map('rawurlencode', $segments));
    }

    // Fallback if urlPath is ever missing (e.g. stale cache from before it shipped).
    $slug = $article['slug'] ?? '';
    $query = 'id=' . rawurlencode($id);
    if ($slug !== '') {
        $query .= '&slug=' . rawurlencode($slug);
    }
    return 'inner-page.php?' . $query;
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
