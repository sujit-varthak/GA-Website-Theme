<?php
define('GA_API_BASE_URL', 'https://great-andhra-uat.onrender.com');
define('GA_CACHE_DIR', __DIR__ . '/cache');
define('GA_CACHE_TTL', 30); // seconds — lowered for demo purposes, bump back up before real traffic
define('GA_TAGS_CACHE_TTL', 3600); // seconds — the full tag list (ga_fetch_all_tags()) changes far less often than articles

// Full-page roadblock ad (advertisement.php), shown once per cookie window before any page
// renders. Kill switch + cookie name/lifetime in one place so it's easy to disable or retune.
define('GA_ROADBLOCK_AD_ENABLED', true);
define('GA_ROADBLOCK_COOKIE_NAME', 'garb');
define('GA_ROADBLOCK_COOKIE_TTL', 15 * 60); // seconds — re-shows the ad 15 minutes after the last one

// Current roadblock ad campaign — swap these when the campaign changes, no template edits needed.
// Two crops: the portrait one-sheet reads fine on a narrow phone screen but wastes most of a
// desktop viewport, so desktop gets the wide landscape banner instead (picked via <picture> in
// advertisement.php's markup, at the same 768px breakpoint the rest of the site's CSS uses).
define('GA_ROADBLOCK_AD_NAME', 'Spider-Man: Brand New Day');
define('GA_ROADBLOCK_AD_IMAGE_MOBILE', 'images/spiderman-brand-new-day-poster.jpg');
define('GA_ROADBLOCK_AD_IMAGE_DESKTOP', 'images/spiderman-brand-new-day-landscape.jpg');
define('GA_ROADBLOCK_AD_LINK', 'https://www.imdb.com/title/tt22084616/');

// Manual cache-clear trigger: hit clear-cache.php?key=<this> to force-refresh instantly
// instead of waiting out the TTL. Change this to your own value before going live.
define('GA_CACHE_CLEAR_KEY', 'ga-dev-clear-2026');

// Admin-managed advertisements (ga_render_ad() in inc/helpers.php). Shorter TTL than articles
// since an editor toggling an ad active/inactive or changing its schedule should take effect
// fairly quickly, not sit behind the same cache window as content.
define('GA_AD_CACHE_TTL', 60); // seconds

// Static backup ads shown only when the admin API has no active ad for a zone (API down, or
// nothing configured there yet) — keeps every placement looking intentional instead of blank
// during the admin panel's rollout. Same shape ga_render_ad() reads off a real ad record.
// Remove an entry once real ads are consistently configured for that zone.
define('GA_AD_FALLBACKS', [
    'HOMEPAGE_SIDEBAR_LEFT' => [
        'type' => 'IMAGE',
        'imageUrlDesktop' => 'images/msn_new_160_2.jpg',
        'landingUrl' => 'https://www.msnrealty.com/new-lp/GreatAndhra',
        'name' => 'MSN Realty',
    ],
    'HOMEPAGE_SIDEBAR_RIGHT' => [
        'type' => 'IMAGE',
        'imageUrlDesktop' => 'images/msn_new_160_2.jpg',
        'landingUrl' => 'https://www.msnrealty.com/new-lp/GreatAndhra',
        'name' => 'MSN Realty',
    ],
    'HOMEPAGE_TOP_BANNER' => [
        'type' => 'IMAGE',
        'imageUrlDesktop' => 'images/Best_Brains_728_12242025_1.jpg',
        'landingUrl' => 'https://bestbrains.com/promotions/newyearoffer',
        'name' => 'Best Brains',
    ],
    'HOMEPAGE_MOBILE_BANNER' => [
        'type' => 'IMAGE',
        'imageUrlMobile' => 'images/IndianClicks_BestBrains_380x250_12242025_1.webp',
        'landingUrl' => 'https://bestbrains.com/promotions/newyearoffer',
        'name' => 'Best Brains',
    ],
    'HOMEPAGE_SECTION_INLINE' => [
        'type' => 'IMAGE',
        'imageUrlDesktop' => 'images/16260515895400254651.jpg',
        'landingUrl' => '',
        'name' => 'Advertisement',
    ],
    'INNER_SIDEBAR_LEFT' => [
        'type' => 'IMAGE',
        'imageUrlDesktop' => 'images/msn_new_160_2.jpg',
        'landingUrl' => 'https://www.msnrealty.com/new-lp/GreatAndhra',
        'name' => 'MSN Realty',
    ],
    'INNER_SIDEBAR_RIGHT' => [
        'type' => 'IMAGE',
        'imageUrlDesktop' => 'images/msn_new_160_2.jpg',
        'landingUrl' => 'https://www.msnrealty.com/new-lp/GreatAndhra',
        'name' => 'MSN Realty',
    ],
    'INNER_TOP_BANNER' => [
        'type' => 'IMAGE',
        'imageUrlDesktop' => 'images/msn_728.jpg',
        'landingUrl' => 'https://www.msnrealty.com/new-lp/GreatAndhra',
        'name' => 'MSN Reality',
    ],
    'INNER_MOBILE_BANNER' => [
        'type' => 'IMAGE',
        'imageUrlMobile' => 'images/IndianClicks_BestBrains_380x250_12242025_1.webp',
        'landingUrl' => 'https://bestbrains.com/promotions/newyearoffer',
        'name' => 'Best Brains',
    ],
    'BOXOFFICE_TOP_BANNER' => [
        'type' => 'IMAGE',
        'imageUrlDesktop' => 'images/msn_728.jpg',
        'landingUrl' => 'https://www.msnrealty.com/new-lp/GreatAndhra',
        'name' => 'MSN Reality',
    ],
    'BOXOFFICE_MOBILE_BANNER' => [
        'type' => 'IMAGE',
        'imageUrlMobile' => 'images/IndianClicks_BestBrains_380x250_12242025_1.webp',
        'landingUrl' => 'https://bestbrains.com/promotions/newyearoffer',
        'name' => 'Best Brains',
    ],
    'ROADBLOCK' => [
        'type' => 'IMAGE',
        'imageUrlMobile' => 'images/spiderman-brand-new-day-poster.jpg',
        'imageUrlDesktop' => 'images/spiderman-brand-new-day-landscape.jpg',
        'landingUrl' => 'https://www.imdb.com/title/tt22084616/',
        'name' => 'Spider-Man: Brand New Day',
        'roadblockDelayMs' => 15000,
        'roadblockCookieTTL' => 900,
    ],
]);

// Max title length before PHP-side truncation kicks in (no CSS line-clamp anywhere in this theme).
define('GA_HOME_HERO_TITLE_MAX', 100);
define('GA_HOME_LIST_TITLE_MAX', 80);
define('GA_MOST_POPULAR_TITLE_MAX', 90);
define('GA_OPINION_TITLE_MAX', 90);
define('GA_CATEGORY_SECTION_TITLE_MAX', 90);

// Most Popular / Most Read: no dedicated backend endpoint yet, so we pull this many latest
// articles and sort by viewCount in PHP (same "could miss an older high-view article" caveat
// as any client-side scan — revisit if this ever needs to be exhaustive rather than latest-N).
// Raised from 20 so Most Read has enough material to fill the same height as Big Story.
define('GA_MOST_POPULAR_FEED_SIZE', 40);

// Top News / Most Read tabs: hard cap so the list doesn't overflow past Big Story's height.
define('GA_TAB_ARTICLE_LIMIT', 17);

// Inner-page.php's own "Top News" sidebar widget (reuses the homepage's trending data) and
// "Recommended For You" widget (filtered to the "Articles" category).
define('GA_INNER_TOP_NEWS_COUNT', 3);
define('GA_RECOMMENDED_COUNT', 8);
define('GA_ARTICLES_CATEGORY_ID', '43a01cf4-66a6-460b-8816-8c22518ad33c');

// "Related Articles" widget — other articles from the same category as the one being read.
define('GA_RELATED_ARTICLES_COUNT', 3);

// list-page.php: category-filtered listing, driven by ?categoryId=&categoryName= from the nav.
// Matches the static design's hardcoded card count.
define('GA_LIST_PAGE_TAKE', 15);

// Homepage "Editor's Pick" card: only articles with schemaData.movieName + schemaData.rating
// populated qualify (confirmed live 2026-08-01 — only 2 of 32 Reviews articles have it so far).
// Scans this many latest Reviews articles for the most recent qualifying one.
define('GA_EDITORS_PICK_SCAN_COUNT', 30);
// The card is narrow (~half the 650px column) with a large font, so the article's own
// headline needs a shorter cap than other sections to stay readable within it.
define('GA_EDITORS_PICK_TITLE_MAX', 55);

// Numbered pagination: how many page links to show on each side of the current page
// (plus first/last with an ellipsis gap) — categories can run to 80+ pages at this take size.
define('GA_LIST_PAGINATION_WINDOW', 2);

// list-page.php sidebar widgets (Gossip / Reviews) — small fixed lists, no pagination,
// matches the item count already hardcoded in the static design.
define('GA_LIST_SIDEBAR_COUNT', 5);

// box-office.php: Movies category (includeChildren=true, so News/Gossip/Reviews are included
// too — there's no dedicated "Box Office" category in the backend) with real pagination.
// Take size matches the static design's 2-column x 7-row grid.
define('GA_BOX_OFFICE_TAKE', 14);
define('GA_BOX_OFFICE_FALLBACK_IMAGE', ['src' => 'images/ManaShankaraVaraPrasadGaru81768714474.jpg', 'width' => 300, 'height' => 200]);

// Top Trending Topics: homepage's trendingTags is already sorted by articleCount descending
// (capped at 15 server-side); cap further to the top N most-tagged topics for display.
define('GA_TRENDING_TAGS_COUNT', 12);

// Nav category IDs. Leaf categories are filtered exact-match; 'politics' and 'movies' are
// parent categories meant to be used with ga_nav_category_link(..., includeChildren: true)
// so their listing also pulls in their children's articles (confirmed live 2026-07-31).
define('GA_NAV_CATEGORY_IDS', [
    'latest-news' => '7cb7575e-3833-449c-9879-735f8358572d',
    'andhra-news' => 'e361f8ee-2628-424c-9ef8-70ac74de3467',
    'telangana-news' => 'f0bad803-1efd-417e-abc0-f5685bc548de',
    'movie-news' => '0f5e2282-bd0f-4485-b3a1-fdb097f64fa1',
    'movie-gossip' => '41355071-df49-44b5-90cc-a3f54d960b84',
    'reviews' => 'f2cb5c9c-c2a5-4a8e-88a3-9f0b75fd3cff',
    'opinion' => '473631fd-cf8c-4b45-be7b-229bcae015f4',
    'politics' => 'd0c0f169-82ac-4bb3-8d9f-5350e97c07ea',
    'movies' => '4a49a6ff-53f8-4d5b-be10-dea88bae7a18',
]);

// Clean-URL routing for list-page.php, keyed by the same category key as GA_NAV_CATEGORY_IDS
// (kept as a separate table rather than folded into it, since several call sites read
// GA_NAV_CATEGORY_IDS directly for ga_fetch_articles() and don't need urlPath/name at all).
// urlPath is what shows in the browser and what ga_resolve_category_path()/ga_category_path_for_id()
// in inc/helpers.php both key off of — single source of truth for both directions (link-building
// and the old ?categoryId=... -> clean-path 301 redirect).
define('GA_CATEGORY_ROUTES', [
    'politics' => ['urlPath' => 'politics', 'name' => 'Politics', 'includeChildren' => true],
    'movies' => ['urlPath' => 'movies', 'name' => 'Movies', 'includeChildren' => true],
    'movie-news' => ['urlPath' => 'movies/news', 'name' => 'Movie News', 'includeChildren' => false],
    'movie-gossip' => ['urlPath' => 'movies/gossip', 'name' => 'Movie Gossip', 'includeChildren' => false],
    'andhra-news' => ['urlPath' => 'politics/andhra', 'name' => 'Andhra News', 'includeChildren' => false],
    'telangana-news' => ['urlPath' => 'politics/telangana', 'name' => 'Telangana News', 'includeChildren' => false],
    'reviews' => ['urlPath' => 'reviews', 'name' => 'Reviews', 'includeChildren' => false],
    'opinion' => ['urlPath' => 'opinion', 'name' => 'Opinion', 'includeChildren' => false],
    'latest-news' => ['urlPath' => 'latest-news', 'name' => 'Latest News', 'includeChildren' => false],
]);

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

define('GA_INNER_TOP_NEWS_FALLBACK_IMAGES', [
    ['src' => 'images/biker61775536801.jpeg', 'width' => 111, 'height' => 62],
    ['src' => 'images/peddi81775535513.jpg', 'width' => 111, 'height' => 62],
    ['src' => 'images/revanth (1)1775529951.jpg', 'width' => 111, 'height' => 62],
]);

// list-page.php card image — reuses the first static image from the current design.
define('GA_LIST_PAGE_FALLBACK_IMAGE', ['src' => './images/babuwont1775729325.jpeg', 'width' => 150, 'height' => 112]);

define('GA_RECOMMENDED_FALLBACK_IMAGES', [
    ['src' => 'images/biker91775193758.jpg&width=113&height=62&action=resize&quality=100', 'width' => 111, 'height' => 62],
    ['src' => 'images/raakaasaa21775196309.jpg&width=113&height=62&action=resize&quality=100', 'width' => 111, 'height' => 62],
    ['src' => 'images/trump_iran11775399425.jpg&width=113&height=62&action=resize&quality=100', 'width' => 111, 'height' => 62],
    ['src' => 'images/raakaasaa21775148515.jpg&width=113&height=62&action=resize&quality=100', 'width' => 111, 'height' => 62],
    ['src' => 'images/ramayana41775105638.jpg&width=113&height=62&action=resize&quality=100', 'width' => 111, 'height' => 62],
    ['src' => 'images/palnadu11775372743.jpg&width=113&height=62&action=resize&quality=100', 'width' => 111, 'height' => 62],
    ['src' => 'images/cow11775227175.jpg&width=113&height=62&action=resize&quality=100', 'width' => 111, 'height' => 62],
    ['src' => 'images/yaav11775140301.jpg&width=113&height=62&action=resize&quality=100', 'width' => 111, 'height' => 62],
]);
