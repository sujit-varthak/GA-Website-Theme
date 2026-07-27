<?php
define('GA_API_BASE_URL', 'https://great-andhra-uat.onrender.com');
define('GA_CACHE_DIR', __DIR__ . '/cache');
define('GA_CACHE_TTL', 30); // seconds — lowered for demo purposes, bump back up before real traffic

// Manual cache-clear trigger: hit clear-cache.php?key=<this> to force-refresh instantly
// instead of waiting out the TTL. Change this to your own value before going live.
define('GA_CACHE_CLEAR_KEY', 'ga-dev-clear-2026');

// Max title length before PHP-side truncation kicks in (no CSS line-clamp anywhere in this theme).
define('GA_HOME_HERO_TITLE_MAX', 100);
define('GA_HOME_LIST_TITLE_MAX', 80);
define('GA_MOST_POPULAR_TITLE_MAX', 90);
define('GA_OPINION_TITLE_MAX', 90);
define('GA_CATEGORY_SECTION_TITLE_MAX', 90);

// Most Popular: no dedicated backend endpoint yet, so we pull this many latest articles and
// sort by viewCount in PHP (same "could miss an older high-view article" caveat as any
// client-side scan — revisit if this ever needs to be exhaustive rather than latest-N).
define('GA_MOST_POPULAR_FEED_SIZE', 20);

// featuredImageUrl is currently always null from the API — these are the exact images/dimensions
// already hardcoded in today's static markup, reused as a positional fallback until images are wired up.
define('GA_HOME_HERO_FALLBACK_IMAGE', [
    'src' => 'images/ramayana41775105638.jpg',
    'width' => 360,
    'height' => 240,
]);

define('GA_HOME_LIST_FALLBACK_IMAGES', [
    ['src' => 'images/tecg1775062056.jpg', 'width' => 111, 'height' => 62],
    ['src' => 'images/h1bvisa41775058007.jpg', 'width' => 111, 'height' => 62],
    ['src' => 'images/jagan_new181775031416.jpg', 'width' => 111, 'height' => 62],
]);

// inner-page.php featured image — reuses the image already hardcoded in the static template,
// since featuredImageUrl is currently always null.
define('GA_ARTICLE_FALLBACK_IMAGE', [
    'src' => 'images/alluarjun_atlee21775487958.jpg',
]);

define('GA_MOST_POPULAR_FALLBACK_IMAGES', [
    ['src' => 'images/food11775001619.jpg', 'width' => 120, 'height' => 75],
    ['src' => 'images/tecg1775062056.jpg', 'width' => 120, 'height' => 75],
    ['src' => 'images/jagan_new181775031416.jpg', 'width' => 120, 'height' => 75],
    ['src' => 'images/h1bvisa41775058007.jpg', 'width' => 120, 'height' => 75],
    ['src' => 'images/rishabshetty11775008296.jpg', 'width' => 120, 'height' => 75],
]);

define('GA_OPINION_FALLBACK_IMAGES', [
    ['src' => 'images/us-pak-iran11774750562.jpg', 'width' => 185, 'height' => 110],
    ['src' => 'images/amaravati-babu11774729994.jpg', 'width' => 185, 'height' => 110],
    ['src' => 'images/politician11774397654.jpg', 'width' => 185, 'height' => 110],
    ['src' => 'images/modi_11774228164.jpg', 'width' => 185, 'height' => 110],
    ['src' => 'images/garikapati11774051378.jpg', 'width' => 185, 'height' => 110],
]);

// The 6 homepage category sections — each is a hero image (index 0) + a repeated text list
// (all fetched items, hero included, as plain links). One fallback image per section, reused
// from the article that currently occupies that hero slot in the static markup.
define('GA_MOVIE_NEWS_FALLBACK_IMAGE', ['src' => 'images/ramayana41775105638.jpg', 'width' => 330, 'height' => 200]);
define('GA_MOVIE_GOSSIP_FALLBACK_IMAGE', ['src' => 'images/armurugadoss1774855134.jpg', 'width' => 330, 'height' => 200]);
define('GA_ANDHRA_NEWS_FALLBACK_IMAGE', ['src' => 'images/babu_new251775111776.jpg', 'width' => 330, 'height' => 200]);
define('GA_TELANGANA_NEWS_FALLBACK_IMAGE', ['src' => 'images/dollar-rupee11775089475.jpg', 'width' => 330, 'height' => 200]);
define('GA_POLITICS_GOSSIP_FALLBACK_IMAGE', ['src' => 'images/bigtv11775055984.jpg', 'width' => 330, 'height' => 200]);
define('GA_REVIEWS_FALLBACK_IMAGE', ['src' => 'images/youth41774602072.jpg', 'width' => 330, 'height' => 200]);
