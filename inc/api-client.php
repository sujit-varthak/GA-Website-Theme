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

// Cache-stampede guard (load-audit root cause #2: zero flock() anywhere in this file).
// When a cache key expires, every concurrent request that misses it would otherwise fire
// its own duplicate backend call, racing to write the same file. This makes at most one
// request at a time actually refresh a given key - callers that lose the race serve
// whatever's already cached (even if stale) instead of piling on more backend load, the
// same "serve something now" tradeoff this codebase already makes for a fully failed
// backend call, just triggered by contention instead of an error. Non-blocking: nothing
// ever waits on another request's network call.
// Returns a lock handle to pass to ga_cache_lock_release(), or null if another request
// already holds it (or the lock file couldn't be opened - fails open, treated as "no lock").
function ga_cache_lock_try(string $cacheFile)
{
    $handle = @fopen($cacheFile . '.lock', 'c');
    if ($handle === false) {
        return null;
    }
    if (!flock($handle, LOCK_EX | LOCK_NB)) {
        fclose($handle);
        return null;
    }
    return $handle;
}

function ga_cache_lock_release($handle): void
{
    if ($handle === null) {
        return;
    }
    flock($handle, LOCK_UN);
    fclose($handle);
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

// Builds the {url, cacheFile, ttl} triple for a ga_fetch_article_by_id() call without
// performing it — shared with ga_prefetch_page() so the cache key/URL construction can
// never drift between the two.
function ga_article_by_id_request(string $id): array
{
    $safeId = preg_replace('/[^a-z0-9\-]/', '', strtolower($id));
    return [
        'url' => rtrim(GA_API_BASE_URL, '/') . '/api/public/articles/' . rawurlencode($id),
        'cacheFile' => ga_cache_path('article_' . ($safeId !== '' ? $safeId : 'unknown')),
        'ttl' => GA_CACHE_TTL,
    ];
}

// Returns ['status' => 'found'|'not_found'|'unavailable', 'article' => array|null]
// The backend's article-detail endpoint now resolves by id (not slug) — confirmed 2026-07-24.
function ga_fetch_article_by_id(string $id): array
{
    $req = ga_article_by_id_request($id);
    $cacheFile = $req['cacheFile'];

    $isFresh = is_file($cacheFile) && (time() - filemtime($cacheFile)) < GA_CACHE_TTL;
    if ($isFresh) {
        $cached = ga_cache_read($cacheFile);
        if ($cached !== null) {
            return ['status' => 'found', 'article' => $cached];
        }
    }

    $lock = ga_cache_lock_try($cacheFile);
    if ($lock === null) {
        // Another request is already refreshing this key - serve whatever's cached (even
        // stale) rather than firing a duplicate backend call. Only proceeds unlocked below
        // if there's truly no cache yet (first-ever request for this article).
        $stale = ga_cache_read($cacheFile);
        if ($stale !== null) {
            return ['status' => 'found', 'article' => $stale];
        }
    }

    $result = ga_http_get($req['url']);

    if ($result['status'] === 404) {
        ga_cache_lock_release($lock);
        return ['status' => 'not_found', 'article' => null];
    }

    $badResponse = $result['status'] === null
        || $result['status'] < 200
        || $result['status'] >= 300
        || !is_array($result['data']);

    if ($badResponse) {
        // API unreachable/erroring (e.g. Render cold-start) — fall back to a stale cache if we have one.
        ga_cache_lock_release($lock);
        $stale = ga_cache_read($cacheFile);
        if ($stale !== null) {
            return ['status' => 'found', 'article' => $stale];
        }
        return ['status' => 'unavailable', 'article' => null];
    }

    $article = $result['data'];
    if (isset($article['statusCode']) && (int) $article['statusCode'] >= 400) {
        ga_cache_lock_release($lock);
        return ['status' => 'not_found', 'article' => null];
    }

    file_put_contents($cacheFile, json_encode($article));
    ga_cache_lock_release($lock);

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

    $lock = ga_cache_lock_try($cacheFile);
    if ($lock === null) {
        $stale = ga_cache_read($cacheFile);
        if ($stale !== null) {
            return $stale;
        }
    }

    $url = rtrim(GA_API_BASE_URL, '/') . '/api/public/homepage';
    $data = ga_http_get_json($url);

    if ($data === null || !isset($data['bigStory']) || !is_array($data['bigStory'])) {
        // API unreachable/erroring, or the response didn't have the key we need — fall back to stale cache.
        ga_cache_lock_release($lock);
        return ga_cache_read($cacheFile);
    }

    file_put_contents($cacheFile, json_encode($data));
    ga_cache_lock_release($lock);

    return $data;
}

// Builds the {url, cacheFile, ttl} triple for a ga_fetch_articles() call without performing
// it — shared by ga_fetch_articles() itself and ga_prefetch_page() so the cache key/URL
// construction can never drift between the two.
function ga_articles_request(int $take = 4, int $skip = 0, ?string $categoryId = null, bool $includeChildren = false, ?string $tagId = null, bool $isTrending = false): array
{
    $cacheKey = "articles_take{$take}_skip{$skip}"
        . ($categoryId ? "_cat{$categoryId}" : '')
        . ($includeChildren ? '_children' : '')
        . ($tagId ? "_tag{$tagId}" : '')
        . ($isTrending ? '_trending' : '');

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
    if ($isTrending) {
        $query['isTrending'] = 'true';
    }

    return [
        'url' => rtrim(GA_API_BASE_URL, '/') . '/api/public/articles?' . http_build_query($query),
        'cacheFile' => ga_cache_path($cacheKey),
        'ttl' => GA_CACHE_TTL,
    ];
}

// Returns ['items' => article[], 'total' => int], or null if the API failed AND no cache
// (fresh or stale) exists. total is the count matching the given filter (categoryId/
// includeChildren/tagId/isTrending applied), not the whole site — confirmed live 2026-08-01.
// $categoryId is optional — confirmed working server-side filter.
// $includeChildren (opt-in, confirmed live 2026-07-31) also returns the category's direct children's articles.
// $tagId (opt-in, confirmed live 2026-08-02 — was silently ignored before that) filters by tag instead.
// $isTrending (opt-in, added 2026-08-25 alongside the backend's isTrending filter support) -
// "Latest News" has no real category, this is what backs its listing page with real
// server-side pagination instead of slicing the homepage's fixed-size trending widget.
function ga_fetch_articles(int $take = 4, int $skip = 0, ?string $categoryId = null, bool $includeChildren = false, ?string $tagId = null, bool $isTrending = false): ?array
{
    $req = ga_articles_request($take, $skip, $categoryId, $includeChildren, $tagId, $isTrending);
    $cacheFile = $req['cacheFile'];

    $isFresh = is_file($cacheFile) && (time() - filemtime($cacheFile)) < GA_CACHE_TTL;
    if ($isFresh) {
        $cached = ga_cache_read($cacheFile);
        if ($cached !== null) {
            return $cached;
        }
    }

    $lock = ga_cache_lock_try($cacheFile);
    if ($lock === null) {
        $stale = ga_cache_read($cacheFile);
        if ($stale !== null) {
            return $stale;
        }
    }

    $data = ga_http_get_json($req['url']);

    if ($data === null) {
        // API unreachable/erroring (e.g. Render cold-start) — fall back to a stale cache if we have one.
        ga_cache_lock_release($lock);
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
        ga_cache_lock_release($lock);
        return ga_cache_read($cacheFile);
    }

    $result = ['items' => $items, 'total' => $total];
    file_put_contents($cacheFile, json_encode($result));
    ga_cache_lock_release($lock);

    return $result;
}

// Builds the {url, cacheFile, ttl} triple for ga_fetch_weekly_top_five() - shared with
// ga_prefetch_page() as usual.
function ga_weekly_top_five_request(): array
{
    return [
        'url' => rtrim(GA_API_BASE_URL, '/') . '/api/public/weekly-top-five',
        'cacheFile' => ga_cache_path('weekly_top_five'),
        'ttl' => GA_CACHE_TTL,
    ];
}

// "This Week Top Five" curated link list (5 items, admin-managed) - its own small dedicated
// endpoint rather than reading it off the full homepage aggregate. box-office.php used to
// call ga_fetch_homepage() just to read this and ga_fetch_movie_box_office() below, pulling
// the entire ~16-section homepage (all 10 full article-list sections included) to get 3
// small non-article arrays - load-audit finding #3 (2026-08-10) / #7 (2026-08-20).
function ga_fetch_weekly_top_five(): ?array
{
    $req = ga_weekly_top_five_request();
    $cacheFile = $req['cacheFile'];

    $isFresh = is_file($cacheFile) && (time() - filemtime($cacheFile)) < GA_CACHE_TTL;
    if ($isFresh) {
        $cached = ga_cache_read($cacheFile);
        if ($cached !== null) {
            return $cached;
        }
    }

    $lock = ga_cache_lock_try($cacheFile);
    if ($lock === null) {
        $stale = ga_cache_read($cacheFile);
        if ($stale !== null) {
            return $stale;
        }
    }

    $data = ga_http_get_json($req['url']);
    if ($data === null || !array_is_list($data)) {
        ga_cache_lock_release($lock);
        return ga_cache_read($cacheFile);
    }

    file_put_contents($cacheFile, json_encode($data));
    ga_cache_lock_release($lock);

    return $data;
}

// Builds the {url, cacheFile, ttl} triple for ga_fetch_movie_box_office() - shared with
// ga_prefetch_page() as usual. $section is 'ALL_TIME' or 'USA_BOX_OFFICE' (matches the
// backend's MovieBoxOfficeSection enum).
function ga_movie_box_office_request(string $section): array
{
    return [
        'url' => rtrim(GA_API_BASE_URL, '/') . '/api/public/movie-box-office?section=' . rawurlencode($section),
        'cacheFile' => ga_cache_path('movie_box_office_' . strtolower($section)),
        'ttl' => GA_CACHE_TTL,
    ];
}

// Movie Rankings for one section ("All Time Top Films" / "USA Box Office", 5 items each,
// admin-managed) - same rationale as ga_fetch_weekly_top_five() above.
function ga_fetch_movie_box_office(string $section): ?array
{
    $req = ga_movie_box_office_request($section);
    $cacheFile = $req['cacheFile'];

    $isFresh = is_file($cacheFile) && (time() - filemtime($cacheFile)) < GA_CACHE_TTL;
    if ($isFresh) {
        $cached = ga_cache_read($cacheFile);
        if ($cached !== null) {
            return $cached;
        }
    }

    $lock = ga_cache_lock_try($cacheFile);
    if ($lock === null) {
        $stale = ga_cache_read($cacheFile);
        if ($stale !== null) {
            return $stale;
        }
    }

    $data = ga_http_get_json($req['url']);
    if ($data === null || !array_is_list($data)) {
        ga_cache_lock_release($lock);
        return ga_cache_read($cacheFile);
    }

    file_put_contents($cacheFile, json_encode($data));
    ga_cache_lock_release($lock);

    return $data;
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

    $lock = ga_cache_lock_try($cacheFile);
    if ($lock === null) {
        $stale = ga_cache_read($cacheFile);
        if ($stale !== null) {
            return $stale;
        }
    }

    $url = rtrim(GA_API_BASE_URL, '/') . '/api/public/tags';
    $tags = ga_http_get_json($url);

    if (!is_array($tags)) {
        ga_cache_lock_release($lock);
        return ga_cache_read($cacheFile) ?? [];
    }

    file_put_contents($cacheFile, json_encode($tags));
    ga_cache_lock_release($lock);

    return $tags;
}

// Returns the active admin-managed ad for a zone ({ id, name, type, imageUrlDesktop,
// imageUrlMobile, landingUrl, scriptCode, ... }), or null if none is active/configured — callers
// (ga_render_ad() in inc/helpers.php) fall back to GA_AD_FALLBACKS in that case. The public
// endpoint returns {} rather than 404 when nothing matches, so an empty decode means "no ad"
// rather than "API failure" — only a hard failure falls back to a stale cache.
function ga_fetch_ad(string $zone, bool $isDesktop = true): ?array
{
    $safeZone = preg_replace('/[^A-Z0-9_]/', '', strtoupper($zone));
    $cacheFile = ga_cache_path('ad_' . strtolower($safeZone) . '_' . ($isDesktop ? 'desktop' : 'mobile'));

    $isFresh = is_file($cacheFile) && (time() - filemtime($cacheFile)) < GA_AD_CACHE_TTL;
    if ($isFresh) {
        $cached = ga_cache_read($cacheFile);
        if ($cached !== null) {
            return empty($cached) ? null : $cached;
        }
    }

    $lock = ga_cache_lock_try($cacheFile);
    if ($lock === null) {
        $stale = ga_cache_read($cacheFile);
        if ($stale !== null) {
            return empty($stale) ? null : $stale;
        }
    }

    $url = rtrim(GA_API_BASE_URL, '/') . '/api/public/advertisements/' . rawurlencode($safeZone)
        . '?isDesktop=' . ($isDesktop ? 'true' : 'false');
    $data = ga_http_get_json($url);

    if ($data === null) {
        // API unreachable/erroring — fall back to a stale cache if we have one.
        ga_cache_lock_release($lock);
        $stale = ga_cache_read($cacheFile);
        return $stale !== null ? (empty($stale) ? null : $stale) : null;
    }

    file_put_contents($cacheFile, json_encode($data));
    ga_cache_lock_release($lock);

    return empty($data) ? null : $data;
}

// Fires many independent GET requests concurrently via curl_multi (shared TCP+TLS
// connections where the host repeats, one round trip bounded by the slowest single
// request instead of the sum of every request) and writes each successful response into
// the exact cache file its normal single-request fetch function already reads. Requests
// whose cache is still fresh are skipped entirely — this only replaces the network wait,
// every existing per-resource fetch/stale-cache-fallback function downstream is unchanged
// and just sees a warm cache instead of a cold one.
// $requests: array of ['url' => string, 'cacheFile' => string, 'ttl' => int].
function ga_prefetch(array $requests): void
{
    // Each pending key also needs its own stampede lock (see ga_cache_lock_try()) - without
    // this, two concurrent visitors' requests would each run their own ga_prefetch() batch
    // and both fire the same 17 network calls the moment those keys expire, multiplying the
    // single-resource stampede this file already guards against by however many keys are in
    // the batch. A key whose lock is already held by another request's batch is dropped here
    // silently - that request is already refreshing it, and this request's own later
    // ga_fetch_*() call for it will see the lock miss too and serve stale/whatever exists.
    $pending = [];
    $locks = [];
    foreach ($requests as $req) {
        $isFresh = is_file($req['cacheFile']) && (time() - filemtime($req['cacheFile'])) < $req['ttl'];
        if ($isFresh) {
            continue;
        }
        $lock = ga_cache_lock_try($req['cacheFile']);
        if ($lock === null) {
            continue;
        }
        $pending[] = $req;
        $locks[] = $lock;
    }
    if ($pending === []) {
        return;
    }

    $mh = curl_multi_init();
    $handles = [];
    foreach ($pending as $i => $req) {
        $ch = curl_init($req['url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[$i] = $ch;
    }

    $running = null;
    do {
        $status = curl_multi_exec($mh, $running);
        if ($running) {
            curl_multi_select($mh, 1.0);
        }
    } while ($running > 0 && $status === CURLM_OK);

    foreach ($handles as $i => $ch) {
        $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $body = curl_multi_getcontent($ch);
        $curlErrno = curl_errno($ch);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);

        if ($curlErrno !== 0 || $httpStatus < 200 || $httpStatus >= 300 || $body === '') {
            // Leave any existing cache file alone — the resource's own ga_fetch_*() will
            // fall back to a stale cache (or its "unavailable" state) exactly as before.
            continue;
        }
        $data = json_decode($body, true);
        if (!is_array($data) || (isset($data['statusCode']) && (int) $data['statusCode'] >= 400)) {
            continue;
        }
        file_put_contents($pending[$i]['cacheFile'], $body);
    }

    curl_multi_close($mh);
    foreach ($locks as $lock) {
        ga_cache_lock_release($lock);
    }
}

// Builds and fires the full concurrent prefetch batch for one page load: optionally the
// homepage aggregate, an article looked up by id (inner-page.php), any number of article
// feeds, and any number of ad zones. Call this once, right after api-client.php is required
// (and after any early-exit check like the roadblock interstitial) and before any of the
// page's own ga_fetch_*()/ga_render_ad() calls — those then hit an already-warm cache
// instead of each independently making its own blocking curl_exec() call. This is what
// turns a page's 6-18 sequential round trips into one batch bounded by the slowest single
// request among them.
//
// Anything a page needs that isn't knowable until an earlier response comes back (e.g.
// inner-page.php's "related articles" feed, keyed off the fetched article's category id)
// can't be included here — that stays a normal single ga_fetch_articles() call made after
// this batch resolves and the dependency is known.
//
// $opts:
//   'homepage'      => bool — include the /api/public/homepage aggregate
//   'movieRankings' => bool — include weekly-top-five + both movie-box-office sections
//                      (the 3 small dedicated endpoints box-office.php needs, instead of
//                      pulling the full homepage aggregate just to read them)
//   'articleId'     => string|null — include ga_fetch_article_by_id()'s request for this id
//   'articles'      => array of ga_fetch_articles()-style argument lists, e.g. [[take, skip,
//                      categoryId], ...] — passed through to ga_articles_request() via
//                      call_user_func_array()
//   'adZones'       => array of zone name strings
function ga_prefetch_page(array $opts): void
{
    $isMobile = ga_is_mobile();
    $requests = [];

    if (!empty($opts['homepage'])) {
        $requests[] = [
            'url' => rtrim(GA_API_BASE_URL, '/') . '/api/public/homepage',
            'cacheFile' => ga_cache_path('homepage'),
            'ttl' => GA_CACHE_TTL,
        ];
    }

    if (!empty($opts['movieRankings'])) {
        $requests[] = ga_weekly_top_five_request();
        $requests[] = ga_movie_box_office_request('ALL_TIME');
        $requests[] = ga_movie_box_office_request('USA_BOX_OFFICE');
    }

    if (!empty($opts['articleId'])) {
        $requests[] = ga_article_by_id_request($opts['articleId']);
    }

    foreach ($opts['articles'] ?? [] as $args) {
        $requests[] = call_user_func_array('ga_articles_request', $args);
    }

    foreach ($opts['adZones'] ?? [] as $zone) {
        $safeZone = preg_replace('/[^A-Z0-9_]/', '', strtoupper($zone));
        $requests[] = [
            'url' => rtrim(GA_API_BASE_URL, '/') . '/api/public/advertisements/' . rawurlencode($safeZone)
                . '?isDesktop=' . ($isMobile ? 'false' : 'true'),
            'cacheFile' => ga_cache_path('ad_' . strtolower($safeZone) . '_' . ($isMobile ? 'mobile' : 'desktop')),
            'ttl' => GA_AD_CACHE_TTL,
        ];
    }

    ga_prefetch($requests);
}

// Same shape as ga_fetch_ad() but for the roadblock zone specifically — also carries
// roadblockDelayMs/roadblockCookieTTL, which advertisement.php uses in place of the static
// GA_ROADBLOCK_AD_* config constants when an active roadblock ad exists.
function ga_fetch_roadblock_ad(bool $isDesktop = true): ?array
{
    $cacheFile = ga_cache_path('ad_roadblock_' . ($isDesktop ? 'desktop' : 'mobile'));

    $isFresh = is_file($cacheFile) && (time() - filemtime($cacheFile)) < GA_AD_CACHE_TTL;
    if ($isFresh) {
        $cached = ga_cache_read($cacheFile);
        if ($cached !== null) {
            return empty($cached) ? null : $cached;
        }
    }

    $lock = ga_cache_lock_try($cacheFile);
    if ($lock === null) {
        $stale = ga_cache_read($cacheFile);
        if ($stale !== null) {
            return empty($stale) ? null : $stale;
        }
    }

    $url = rtrim(GA_API_BASE_URL, '/') . '/api/public/advertisements/roadblock/active'
        . '?isDesktop=' . ($isDesktop ? 'true' : 'false');
    $data = ga_http_get_json($url);

    if ($data === null) {
        ga_cache_lock_release($lock);
        $stale = ga_cache_read($cacheFile);
        return $stale !== null ? (empty($stale) ? null : $stale) : null;
    }

    file_put_contents($cacheFile, json_encode($data));
    ga_cache_lock_release($lock);

    return empty($data) ? null : $data;
}
