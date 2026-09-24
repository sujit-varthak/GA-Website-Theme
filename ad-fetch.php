<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/api-client.php';

// Client-side ad loader's target (js/ga-ad-loader.js) - see ga_render_ad()'s comment in
// inc/helpers.php for why ad rendering moved here instead of staying baked into each page's
// own server-rendered HTML. ga_is_mobile() is read here (from this request's own User-Agent
// header, sent automatically by fetch()) rather than trusted from a client-supplied param, so
// desktop/mobile ad selection stays exactly as consistent as it was when server-rendered.
//
// Naturally sits behind the same two-layer cache ga_fetch_ad() already used for server-rendered
// ads: its own local file cache (GA_AD_CACHE_TTL), and behind that, the backend's
// PublicCacheInterceptor (Redis-backed, 10 minute TTL) on the /api/public/advertisements/{zone}
// call it makes on a local cache miss. No new caching layer needed for this endpoint - both
// already existed for exactly this data.

header('Content-Type: application/json; charset=utf-8');

$ga_zone = isset($_GET['zone']) ? preg_replace('/[^A-Z0-9_]/', '', strtoupper((string) $_GET['zone'])) : '';
$ga_dimension_zone = isset($_GET['dimensionZone']) && $_GET['dimensionZone'] !== ''
    ? preg_replace('/[^A-Z0-9_]/', '', strtoupper((string) $_GET['dimensionZone']))
    : null;

if ($ga_zone === '') {
    http_response_code(400);
    echo json_encode(['error' => 'zone is required']);
    exit;
}

echo json_encode(['html' => ga_build_ad_html($ga_zone, $ga_dimension_zone, ga_is_mobile())]);
