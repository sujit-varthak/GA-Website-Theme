<?php
define('GA_API_BASE_URL', 'https://great-andhra-uat.onrender.com');
define('GA_CACHE_DIR', __DIR__ . '/cache');
define('GA_CACHE_TTL', 300); // seconds — one Render call feeds every visitor for 5 minutes

// Homepage Big Story: the API has no tagId filter (confirmed it's silently ignored), so we pull
// this many latest articles in one call and scan them in PHP for the "big story" tag.
define('GA_HOME_FEED_SIZE', 20);
define('GA_BIG_STORY_TAG_SLUG', 'big-story');

// Max title length before PHP-side truncation kicks in (no CSS line-clamp anywhere in this theme).
define('GA_HOME_HERO_TITLE_MAX', 100);
define('GA_HOME_LIST_TITLE_MAX', 80);

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
