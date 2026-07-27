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

// Returns a flat list of article arrays, or null if the API failed AND no cache (fresh or stale) exists.
function ga_fetch_articles(int $take = 4, int $skip = 0): ?array
{
    $cacheKey = "articles_take{$take}_skip{$skip}";
    $cacheFile = ga_cache_path($cacheKey);

    $isFresh = is_file($cacheFile) && (time() - filemtime($cacheFile)) < GA_CACHE_TTL;
    if ($isFresh) {
        $cached = ga_cache_read($cacheFile);
        if ($cached !== null) {
            return $cached;
        }
    }

    $url = rtrim(GA_API_BASE_URL, '/') . '/api/public/articles?' . http_build_query([
        'take' => $take,
        'skip' => $skip,
    ]);

    $data = ga_http_get_json($url);

    if ($data === null) {
        // API unreachable/erroring (e.g. Render cold-start) — fall back to a stale cache if we have one.
        return ga_cache_read($cacheFile);
    }

    // Response envelope isn't confirmed yet — handle a bare array or a {data:[...]} / {items:[...]} wrapper.
    $articles = $data;
    if (array_is_list($data) === false) {
        if (isset($data['data']) && is_array($data['data'])) {
            $articles = $data['data'];
        } elseif (isset($data['items']) && is_array($data['items'])) {
            $articles = $data['items'];
        } else {
            return ga_cache_read($cacheFile);
        }
    }

    file_put_contents($cacheFile, json_encode($articles));

    return $articles;
}
