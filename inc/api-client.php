<?php

function ga_http_get_json(string $url, int $connectTimeout = 3, int $timeout = 8): ?array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => $connectTimeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $body = curl_exec($ch);
    $curlErrno = curl_errno($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlErrno !== 0 || $body === false) {
        return null;
    }
    if ($status < 200 || $status >= 300) {
        return null;
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return null;
    }
    // HttpExceptionFilter error shape: { statusCode, message }
    if (isset($data['statusCode']) && (int) $data['statusCode'] >= 400) {
        return null;
    }

    return $data;
}

function ga_cache_path(string $key): string
{
    if (!is_dir(GA_CACHE_DIR)) {
        mkdir(GA_CACHE_DIR, 0775, true);
    }
    return rtrim(GA_CACHE_DIR, '/\\') . '/' . $key . '.json';
}

function ga_cache_read(string $cacheFile): ?array
{
    if (!is_file($cacheFile)) {
        return null;
    }
    $decoded = json_decode((string) file_get_contents($cacheFile), true);
    return is_array($decoded) ? $decoded : null;
}

// Like ga_http_get_json() but also surfaces the HTTP status, since callers may need to
// tell "not found" (404) apart from "unreachable/erroring" (everything else).
function ga_http_get(string $url, int $connectTimeout = 3, int $timeout = 8): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => $connectTimeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $body = curl_exec($ch);
    $curlErrno = curl_errno($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlErrno !== 0 || $body === false) {
        return ['status' => null, 'data' => null];
    }

    $data = json_decode($body, true);

    return ['status' => $status, 'data' => is_array($data) ? $data : null];
}

// Returns ['status' => 'found'|'not_found'|'unavailable', 'article' => array|null]
// The backend's article-detail endpoint now resolves by id (not slug) — confirmed 2026-07-24.
function ga_fetch_article_by_id(string $id): array
{
    $safeId = preg_replace('/[^a-z0-9\-]/', '', strtolower($id));
    $cacheFile = ga_cache_path('article_' . ($safeId !== '' ? $safeId : 'unknown'));

    $isFresh = is_file($cacheFile) && (time() - filemtime($cacheFile)) < GA_CACHE_TTL;
    if ($isFresh) {
        $cached = ga_cache_read($cacheFile);
        if ($cached !== null) {
            return ['status' => 'found', 'article' => $cached];
        }
    }

    $url = rtrim(GA_API_BASE_URL, '/') . '/api/public/articles/' . rawurlencode($id);
    $result = ga_http_get($url);

    if ($result['status'] === 404) {
        return ['status' => 'not_found', 'article' => null];
    }

    $badResponse = $result['status'] === null
        || $result['status'] < 200
        || $result['status'] >= 300
        || !is_array($result['data']);

    if ($badResponse) {
        // API unreachable/erroring (e.g. Render cold-start) — fall back to a stale cache if we have one.
        $stale = ga_cache_read($cacheFile);
        if ($stale !== null) {
            return ['status' => 'found', 'article' => $stale];
        }
        return ['status' => 'unavailable', 'article' => null];
    }

    $article = $result['data'];
    if (isset($article['statusCode']) && (int) $article['statusCode'] >= 400) {
        return ['status' => 'not_found', 'article' => null];
    }

    file_put_contents($cacheFile, json_encode($article));

    return ['status' => 'found', 'article' => $article];
}

// Fire-and-forget view counter ping — failures are silently ignored, never blocks rendering.
function ga_ping_view(string $articleId): void
{
    if ($articleId === '') {
        return;
    }
    $ch = curl_init(rtrim(GA_API_BASE_URL, '/') . '/api/public/articles/' . rawurlencode($articleId) . '/view');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '',
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => 3,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// Returns the full aggregate response — currently ['bigStory' => ['hero'=>.., 'related'=>..],
// 'trending' => article[]] — or null if the API failed AND no cache (fresh or stale) exists.
// This endpoint is documented as growing (more keys added over time), so callers should read
// only the specific key(s) they need rather than assuming this shape is final.
function ga_fetch_homepage(): ?array
{
    $cacheFile = ga_cache_path('homepage');

    $isFresh = is_file($cacheFile) && (time() - filemtime($cacheFile)) < GA_CACHE_TTL;
    if ($isFresh) {
        $cached = ga_cache_read($cacheFile);
        if ($cached !== null) {
            return $cached;
        }
    }

    $url = rtrim(GA_API_BASE_URL, '/') . '/api/public/homepage';
    $data = ga_http_get_json($url);

    if ($data === null || !isset($data['bigStory']) || !is_array($data['bigStory'])) {
        // API unreachable/erroring, or the response didn't have the key we need — fall back to stale cache.
        return ga_cache_read($cacheFile);
    }

    file_put_contents($cacheFile, json_encode($data));

    return $data;
}

// Returns ['items' => article[], 'total' => int], or null if the API failed AND no cache
// (fresh or stale) exists. total is the count matching the given filter (categoryId/
// includeChildren/tagId applied), not the whole site — confirmed live 2026-08-01.
// $categoryId is optional — confirmed working server-side filter.
// $includeChildren (opt-in, confirmed live 2026-07-31) also returns the category's direct children's articles.
// $tagId (opt-in, confirmed live 2026-08-02 — was silently ignored before that) filters by tag instead.
function ga_fetch_articles(int $take = 4, int $skip = 0, ?string $categoryId = null, bool $includeChildren = false, ?string $tagId = null): ?array
{
    $cacheKey = "articles_take{$take}_skip{$skip}"
        . ($categoryId ? "_cat{$categoryId}" : '')
        . ($includeChildren ? '_children' : '')
        . ($tagId ? "_tag{$tagId}" : '');
    $cacheFile = ga_cache_path($cacheKey);

    $isFresh = is_file($cacheFile) && (time() - filemtime($cacheFile)) < GA_CACHE_TTL;
    if ($isFresh) {
        $cached = ga_cache_read($cacheFile);
        if ($cached !== null) {
            return $cached;
        }
    }

    $query = ['take' => $take, 'skip' => $skip];
    if ($categoryId) {
        $query['categoryId'] = $categoryId;
    }
    if ($includeChildren) {
        $query['includeChildren'] = 'true';
    }
    if ($tagId) {
        $query['tagId'] = $tagId;
    }
    $url = rtrim(GA_API_BASE_URL, '/') . '/api/public/articles?' . http_build_query($query);

    $data = ga_http_get_json($url);

    if ($data === null) {
        // API unreachable/erroring (e.g. Render cold-start) — fall back to a stale cache if we have one.
        return ga_cache_read($cacheFile);
    }

    // Response is { items: [...], total: N } (confirmed live 2026-08-01). Defensive fallback
    // for a bare array or {data:[...]} in case of a future/partial rollback — total is unknown
    // in that case, so it's derived from the page size rather than the true site-wide count.
    if (isset($data['items']) && is_array($data['items'])) {
        $items = $data['items'];
        $total = isset($data['total']) ? (int) $data['total'] : count($items);
    } elseif (array_is_list($data)) {
        $items = $data;
        $total = count($items);
    } elseif (isset($data['data']) && is_array($data['data'])) {
        $items = $data['data'];
        $total = isset($data['total']) ? (int) $data['total'] : count($items);
    } else {
        return ga_cache_read($cacheFile);
    }

    $result = ['items' => $items, 'total' => $total];
    file_put_contents($cacheFile, json_encode($result));

    return $result;
}

// Full tag list ({id, name, slug, createdAt, ...}[], 2251 items / ~300KB as of 2026-08-01).
// Confirmed live that /api/public/tags and /api/public/articles both silently ignore a
// ?slug=/?tagSlug= filter and just return everything unfiltered — there's no server-side
// slug lookup to call instead — so /tag/{slug} URLs resolve by fetching this once and scanning
// it in PHP (see ga_find_tag_by_slug()/ga_find_tag_by_id() in inc/helpers.php). Cached far
// longer than articles (GA_TAGS_CACHE_TTL vs GA_CACHE_TTL) since new tags appear far less often.
function ga_fetch_all_tags(): array
{
    $cacheFile = ga_cache_path('all_tags');
    $isFresh = is_file($cacheFile) && (time() - filemtime($cacheFile)) < GA_TAGS_CACHE_TTL;
    if ($isFresh) {
        $cached = ga_cache_read($cacheFile);
        if ($cached !== null) {
            return $cached;
        }
    }

    $url = rtrim(GA_API_BASE_URL, '/') . '/api/public/tags';
    $tags = ga_http_get_json($url);

    if (!is_array($tags)) {
        return ga_cache_read($cacheFile) ?? [];
    }

    file_put_contents($cacheFile, json_encode($tags));

    return $tags;
}
