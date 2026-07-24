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

function ga_inner_link(array $article): string
{
    $slug = $article['slug'] ?? '';
    return 'inner-page.php?slug=' . rawurlencode($slug);
}

function ga_e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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
