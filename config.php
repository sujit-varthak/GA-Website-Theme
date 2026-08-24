<?php
// Was pointed at the old Render deployment (great-andhra-uat.onrender.com) -
// the backend actually moved to DigitalOcean App Platform as part of the
// Postgres/Valkey migration, but this URL was never updated to follow it.
// The Render instance still runs but is disconnected/stale (its own Redis
// was unreachable - unrelated to the working DO Valkey instance). Confirmed
// live 2026-08-24: the DO backend responds healthy (db/redis both "ok") at
// ~0.3-0.9s per call vs. the Render instance's 4-30s.
define('GA_API_BASE_URL', 'https://ga-backend-dnaa7.ondigitalocean.app');
define('GA_CACHE_DIR', __DIR__ . '/cache');
define('GA_CACHE_TTL', 120); // seconds — was 30 ("lowered for demo purposes"), raised now that real traffic is in scope (load-audit fix #6, 2026-08-20); the cache-stampede lock in ga_cache_lock_try() makes a longer TTL safe instead of just less-fresh
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
// (GA_CACHE_TTL) since an editor toggling an ad active/inactive or changing its schedule
// should take effect fairly quickly, not sit behind the same cache window as content. Was 60
// (already longer than the old 30s article TTL, contradicting this comment) - raised to 90 to
// stay under the new 120s article TTL while still being materially shorter (load-audit fix #6).
define('GA_AD_CACHE_TTL', 90); // seconds

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
        // Used by the phone-view banner slot (index.php reuses this same zone's ad there,
        // sized via the HOMEPAGE_MOBILE_BANNER dimension entry below) when the active ad
        // has no imageUrlMobile of its own set.
        'imageUrlMobile' => 'images/IndianClicks_BestBrains_380x250_12242025_1.webp',
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
    'HOMEPAGE_ABOVE_HEADER_BANNER' => [
        'type' => 'IMAGE',
        'imageUrlDesktop' => 'images/msn_990_2.jpg',
        'landingUrl' => 'https://www.msnrealty.com/new-lp/GreatAndhra',
        'name' => 'MSN Reality',
    ],
    'HOMEPAGE_STRIP_BANNER_1' => [
        'type' => 'IMAGE',
        'imageUrlDesktop' => 'images/GVega_320x40_03122025_1.gif',
        'landingUrl' => 'https://www.garudavega.com/',
        'name' => 'GVega',
    ],
    'HOMEPAGE_STRIP_BANNER_2' => [
        'type' => 'IMAGE',
        'imageUrlDesktop' => 'images/Sankara_Nethralaya_320x40_04172023_1_2.gif',
        'landingUrl' => 'http://www.sankaranethralayausa.org/',
        'name' => 'Sankara Nethralaya',
    ],
    'HOMEPAGE_STRIP_BANNER_3' => [
        'type' => 'IMAGE',
        'imageUrlDesktop' => 'images/urth_spices.gif',
        'landingUrl' => 'https://urthindia.com/',
        'name' => 'TANA',
    ],
    'HOMEPAGE_BIG_STORY_BANNER' => [
        'type' => 'IMAGE',
        'imageUrlDesktop' => 'images/Learntek_320_03292026_1.gif',
        'landingUrl' => 'https://www.learntek.org/masterprograms/',
        'name' => 'Learntek',
    ],
    // Fallback is the exact AdSense unit that sat here before this zone existed, saved as a
    // SCRIPT-type ad so nothing changes visually until a real ad is configured.
    'HOMEPAGE_OPINION_BANNER' => [
        'type' => 'SCRIPT',
        'scriptCode' => '<div style="text-align: center;"><script async crossorigin="anonymous" src="js/adsbygoogle.js"></script><ins class="adsbygoogle" data-ad-client="ca-pub-1239645388568087" data-ad-slot="4304682596" style="display:inline-block;width:728px;height:90px"></ins><script>(adsbygoogle = window.adsbygoogle || []).push({});</script></div>',
        'name' => 'AdSense - Opinion Banner',
    ],
    // No fallback for HOMEPAGE_LATEST_NEWS_INLINE_AD, HOMEPAGE_ARTICLE_WIDGET_AD,
    // INNER_ARTICLE_MIDCONTENT_AD, INNER_SIDEBAR_BOTTOM_AD - all brand new slots with no prior
    // static content, so they render empty until a real ad is configured (same as LISTPAGE_CONTENT_AD).
    // Every page is independently manageable again (2026-08-07) - inner-page.php and
    // box-office.php each have their own sidebar/top-banner zones instead of reusing the
    // homepage's.
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
    'LISTPAGE_TOP_BANNER' => [
        'type' => 'IMAGE',
        'imageUrlDesktop' => 'images/msn_728.jpg',
        'landingUrl' => 'https://www.msnrealty.com/new-lp/GreatAndhra',
        'name' => 'MSN Reality',
    ],
    'LISTPAGE_MOBILE_BANNER' => [
        'type' => 'IMAGE',
        'imageUrlMobile' => 'images/IndianClicks_BestBrains_380x250_12242025_1.webp',
        'landingUrl' => 'https://bestbrains.com/promotions/newyearoffer',
        'name' => 'Best Brains',
    ],
    // Static placeholder that always sat under the byline (add_place_650X60) - kept as the
    // fallback so the slot doesn't just disappear before a real ad is configured.
    'INNER_ARTICLE_BANNER' => [
        'type' => 'IMAGE',
        'imageUrlDesktop' => 'images/650-60.jpg',
        'landingUrl' => '',
        'name' => 'Advertisement',
    ],
    // No fallback for LISTPAGE_CONTENT_AD - it replaces a live third-party (Vuukle) script
    // slot, not a static image, so there's nothing sensible to show until a real ad exists;
    // the slot renders empty until then.
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

// Fixed image dimensions per zone, matching what was hardcoded in the static markup this
// replaced (e.g. the sidebar images were always width="160" with auto height, not stretched
// to their fixed-position container's much narrower 80px/120px wrapper). ga_render_ad() uses
// these as HTML width/height attributes; a zone not listed here (e.g.
// HOMEPAGE_SECTION_INLINE, which had no explicit size in the original markup) renders
// responsively instead (max-width: 100%; height: auto).
define('GA_AD_ZONE_IMAGE_DIMENSIONS', [
    'HOMEPAGE_SIDEBAR_LEFT' => ['width' => 160, 'height' => null],
    'HOMEPAGE_SIDEBAR_RIGHT' => ['width' => 160, 'height' => null],
    'HOMEPAGE_TOP_BANNER' => ['width' => 728, 'height' => 90],
    'HOMEPAGE_MOBILE_BANNER' => ['width' => null, 'height' => 250],
    'HOMEPAGE_ABOVE_HEADER_BANNER' => ['width' => 990, 'height' => null],
    'HOMEPAGE_STRIP_BANNER_1' => ['width' => 330, 'height' => 40],
    'HOMEPAGE_STRIP_BANNER_2' => ['width' => 330, 'height' => 40],
    'HOMEPAGE_STRIP_BANNER_3' => ['width' => 300, 'height' => 40],
    'HOMEPAGE_BIG_STORY_BANNER' => ['width' => 320, 'height' => null],
    'INNER_SIDEBAR_LEFT' => ['width' => 160, 'height' => null],
    'INNER_SIDEBAR_RIGHT' => ['width' => 160, 'height' => null],
    'INNER_TOP_BANNER' => ['width' => 728, 'height' => 90],
    'INNER_MOBILE_BANNER' => ['width' => null, 'height' => 250],
    'BOXOFFICE_TOP_BANNER' => ['width' => 728, 'height' => 90],
    'BOXOFFICE_MOBILE_BANNER' => ['width' => null, 'height' => 250],
    'INNER_ARTICLE_BANNER' => ['width' => 650, 'height' => 60],
    'LISTPAGE_CONTENT_AD' => ['width' => 300, 'height' => 250],
    'LISTPAGE_TOP_BANNER' => ['width' => 728, 'height' => 90],
    'LISTPAGE_MOBILE_BANNER' => ['width' => null, 'height' => 250],
    'HOMEPAGE_LATEST_NEWS_INLINE_AD' => ['width' => 300, 'height' => 250],
    'HOMEPAGE_OPINION_BANNER' => ['width' => 728, 'height' => 90],
    'HOMEPAGE_ARTICLE_WIDGET_AD' => ['width' => 300, 'height' => 250],
    // INNER_ARTICLE_MIDCONTENT_AD intentionally has no entry - full width, auto height,
    // same as HOMEPAGE_SECTION_INLINE, on both desktop and mobile.
    'INNER_SIDEBAR_BOTTOM_AD' => ['width' => 300, 'height' => 250],
    'BOXOFFICE_STICKY_AD' => ['width' => 300, 'height' => 250],
    'BOXOFFICE_REVIEW_AD' => ['width' => 300, 'height' => 250],
]);

// Max title length before PHP-side truncation kicks in (no CSS line-clamp anywhere in this theme).
define('GA_HOME_HERO_TITLE_MAX', 100);
define('GA_HOME_LIST_TITLE_MAX', 80);
define('GA_MOST_POPULAR_TITLE_MAX', 90);
define('GA_OPINION_TITLE_MAX', 90);
define('GA_CATEGORY_SECTION_TITLE_MAX', 90);

// Mobile-only "Latest News" list shown between Big Story and the Top News/Most Read/Telugu
// tabs (index.php) - hidden on desktop via CSS. Mirrors the Top News tab's own content
// ($ga_trending_articles), capped to this count. An ad (HOMEPAGE_LATEST_NEWS_INLINE_AD) is
// inserted after the Nth item - see GA_MOBILE_LATEST_NEWS_AD_AFTER_INDEX below.
define('GA_MOBILE_LATEST_NEWS_COUNT', 15);
define('GA_MOBILE_LATEST_NEWS_TITLE_MAX', 80);
// 0-based index - 6 means "after the 7th article".
define('GA_MOBILE_LATEST_NEWS_AD_AFTER_INDEX', 6);

// Mid-article-content ad (inner-page.php, INNER_ARTICLE_MIDCONTENT_AD) - only applies to
// plain-text article bodies (ga_render_article_body() already detects this shape); HTML
// bodies pass through untouched, no ad injected. If the body has this many paragraphs or
// fewer, the ad is placed after the last one (i.e. at the end); otherwise it's placed after
// the paragraph at the midpoint.
define('GA_ARTICLE_MIDCONTENT_AD_SHORT_THRESHOLD', 3);

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
// Re-synced 2026-08-24: categories were cleared and recreated as part of a data migration
// (Vercel->R2 image migration + a fresh WordPress XML re-import), so every category got a new
// UUID and every value below went stale - any code reading this constant directly was silently
// querying deleted category IDs (confirmed live: Editor's Pick, and every nav-driven list page,
// were returning zero results). Values re-confirmed directly against the live
// /api/public/categories response. 'latest-news' is intentionally null - that "category" never
// existed as a real one in the database; it was WordPress's own "newly published" marker, not a
// topic, and isn't in this import. Nothing currently reads this value for a real fetch (list-page.php's
// Latest News page branches on the URL path string, not this ID), but keep it null rather than a
// guessed UUID so a future caller fails loudly/predictably instead of querying a wrong category.
//
// This hardcoded-UUID approach re-breaks every time categories are regenerated on the backend -
// worth resolving category IDs by slug at request time (or on a cache refresh) instead, so this
// doesn't need manual re-syncing after future data operations.
define('GA_NAV_CATEGORY_IDS', [
    'latest-news' => null,
    'andhra-news' => '6e250f68-c9be-439d-854e-08def23574e2',
    'telangana-news' => '1ccf4e94-3792-4716-b4be-33061625a25a',
    'movie-news' => '4ca31e89-2fa9-453f-a63c-67a59fa2f095',
    'movie-gossip' => '0c57a1b9-2177-47ef-98ab-cc2679c0ff34',
    'reviews' => '9707921d-55b6-477b-9aff-a913cdf7b5b5',
    'opinion' => '5afbbece-78ef-4c74-890d-61be86eef330',
    'politics' => '409f81ef-5a53-4d24-8c5e-68485b7a074d',
    'movies' => '4bb8805d-e01d-42aa-b31b-5e3f1de298b8',
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
