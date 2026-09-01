<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/helpers.php';
// Roadblock (full-page interstitial before render) only fires on the homepage now - was
// showing on every page type, which the user found intrusive on list/box-office/article pages.
require_once __DIR__ . '/inc/api-client.php';

// Clean URL path ("movies/gossip", "politics", "tag/{slug}") arrives via PATH_INFO from the
// .htaccess catch-all rewrite. "tag/..." is pure-slug — confirmed live 2026-08-01 that the
// backend's tag.slug is already unique/canonical (0 collisions across 2251 tags), so
// ga_find_tag_by_slug() resolves it directly against the full tag list (no slug-filter exists
// on the API, so this is done client-side — see ga_fetch_all_tags() in inc/api-client.php).
// Anything else is resolved against GA_CATEGORY_ROUTES. No PATH_INFO means an old-style
// ?categoryId=/?tagId= link, or the earlier tag/{id}/{slug} scheme (built before the slug field
// was confirmed) — both 301 to the current clean path when resolvable, otherwise rendered
// directly (covers any category not yet added to GA_CATEGORY_ROUTES).
$ga_path_info = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
$ga_clean_path = '';
$ga_category_id = '';
$ga_category_name = 'Latest News';
$ga_include_children = false;
$ga_tag_id = '';
$ga_tag_name = '';
$ga_is_tag_mode = false;
// "Latest News" shows whatever's currently flagged trending — same source as the homepage's
// Top News tab — not a literal category filter. Backed by the paginated articles endpoint's
// isTrending filter (added 2026-08-25), same real total-based pagination as a category listing
// gets - not the homepage's small fixed-size trending widget.
$ga_is_latest_news_trending = false;

if ($ga_path_info !== '') {
    $ga_segments = explode('/', $ga_path_info);
    if ($ga_segments[0] === 'tag' && !empty($ga_segments[1])) {
        $ga_tag_arg = rawurldecode(trim((string) $ga_segments[1]));
        $ga_is_legacy_tag_id = (bool) preg_match(
            '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
            $ga_tag_arg
        );
        if ($ga_is_legacy_tag_id) {
            $ga_legacy_tag = ga_find_tag_by_id($ga_tag_arg);
            if ($ga_legacy_tag !== null) {
                header('Location: /tag/' . rawurlencode($ga_legacy_tag['slug']), true, 301);
                exit;
            }
            http_response_code(404);
        } else {
            $ga_tag = ga_find_tag_by_slug($ga_tag_arg);
            if ($ga_tag !== null) {
                $ga_tag_id = $ga_tag['id'];
                $ga_tag_name = $ga_tag['name'];
                $ga_is_tag_mode = true;
                $ga_clean_path = 'tag/' . rawurlencode($ga_tag_arg);
            } else {
                http_response_code(404);
            }
        }
    } else {
        $ga_route = ga_resolve_category_path($ga_path_info);
        if ($ga_route !== null) {
            $ga_category_id = $ga_route['id'];
            $ga_category_name = $ga_route['name'];
            $ga_include_children = $ga_route['includeChildren'];
            $ga_clean_path = $ga_path_info;
            $ga_is_latest_news_trending = ($ga_path_info === 'latest-news');
        } else {
            http_response_code(404);
        }
    }
} else {
    $ga_qs_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $ga_qs_tag_id = isset($_GET['tagId']) ? trim((string) $_GET['tagId']) : '';
    $ga_qs_category_id = isset($_GET['categoryId']) ? trim((string) $_GET['categoryId']) : '';

    if ($ga_qs_tag_id !== '') {
        $ga_legacy_tag = ga_find_tag_by_id($ga_qs_tag_id);
        if ($ga_legacy_tag !== null) {
            header('Location: /tag/' . rawurlencode($ga_legacy_tag['slug']) . ($ga_qs_page > 1 ? '?page=' . $ga_qs_page : ''), true, 301);
            exit;
        }
        // Tag not found in the full list (deleted, or a stale/bad id) — render with whatever
        // the old link carried rather than 404ing outright.
        $ga_tag_id = $ga_qs_tag_id;
        $ga_tag_name = isset($_GET['tagName']) ? trim((string) $_GET['tagName']) : '';
        $ga_is_tag_mode = true;
    } elseif ($ga_qs_category_id !== '') {
        $ga_redirect_path = ga_category_path_for_id($ga_qs_category_id);
        if ($ga_redirect_path !== null) {
            header('Location: /' . $ga_redirect_path . ($ga_qs_page > 1 ? '?page=' . $ga_qs_page : ''), true, 301);
            exit;
        }
        $ga_category_id = $ga_qs_category_id;
        $ga_category_name = isset($_GET['categoryName']) ? trim((string) $_GET['categoryName']) : 'Latest News';
        $ga_include_children = isset($_GET['includeChildren']) && $_GET['includeChildren'] === 'true';
    }
}

$ga_page_heading = $ga_is_tag_mode ? $ga_tag_name : $ga_category_name;

// ga_fetch_articles() now returns a total count matching the filter (confirmed live
// 2026-08-01), so real numbered pagination is possible instead of the earlier Prev/Next-only
// "fetch one extra row" workaround.
$ga_page = max(1, isset($_GET['page']) ? (int) $_GET['page'] : 1);
$ga_skip = ($ga_page - 1) * GA_LIST_PAGE_TAKE;

// Fires whichever main-list feed this request resolved to (tag/trending/category — only one
// applies) + both sidebar feeds + every ad zone on this page concurrently, instead of the
// ~5-6 sequential blocking calls this page used to make one at a time.
$ga_prefetch_articles = [
    [GA_LIST_SIDEBAR_COUNT, 0, GA_NAV_CATEGORY_IDS['movie-gossip']],
    [GA_LIST_SIDEBAR_COUNT, 0, GA_NAV_CATEGORY_IDS['reviews']],
];
if ($ga_is_tag_mode) {
    $ga_prefetch_articles[] = [GA_LIST_PAGE_TAKE, $ga_skip, null, false, $ga_tag_id];
} elseif ($ga_is_latest_news_trending) {
    $ga_prefetch_articles[] = [GA_LIST_PAGE_TAKE, $ga_skip, null, false, null, true];
} elseif ($ga_category_id !== '') {
    $ga_prefetch_articles[] = [GA_LIST_PAGE_TAKE, $ga_skip, $ga_category_id, $ga_include_children];
}
ga_prefetch_page([
    'articles' => $ga_prefetch_articles,
    'adZones' => [
        'LISTPAGE_SIDEBAR_LEFT',
        'LISTPAGE_SIDEBAR_RIGHT',
        'LISTPAGE_TOP_BANNER',
        'LISTPAGE_MOBILE_BANNER',
        'LISTPAGE_CONTENT_AD',
        'LISTPAGE_MOBILE_MIDDLE_AD',
        'FULLSCREEN_INTERSTITIAL_AD',
        'BOTTOM_STICKY_AD',
    ],
]);

// Reads the FULLSCREEN_INTERSTITIAL_AD zone from the cache the batch above just warmed,
// instead of its own separate blocking request (previously called before ga_prefetch_page(),
// adding a full sequential network round trip to every page load).
$ga_interstitial_decision = ga_prepare_interstitial_ad('LISTPAGE');

$ga_list_articles = [];
$ga_total = 0;
// includeBody=true on all three branches below - this page's main list is the one place
// on the whole site that shows a publish date and an excerpt (see ga_article_excerpt() in
// helpers.php), so it's the one caller of ga_fetch_articles() that needs `body` back.
if ($ga_is_tag_mode) {
    $ga_result = ga_fetch_articles(GA_LIST_PAGE_TAKE, $ga_skip, null, false, $ga_tag_id, false, true);
    $ga_list_articles = $ga_result['items'] ?? [];
    $ga_total = $ga_result['total'] ?? 0;
} elseif ($ga_is_latest_news_trending) {
    // Real server-side pagination via the backend's isTrending filter (added 2026-08-25) -
    // previously sliced the homepage's trending widget, which is hard-capped at 17 regardless
    // of how many articles are actually flagged trending, so pagination could never surface
    // more than that no matter how it was implemented on top.
    $ga_result = ga_fetch_articles(GA_LIST_PAGE_TAKE, $ga_skip, null, false, null, true, true);
    $ga_list_articles = $ga_result['items'] ?? [];
    $ga_total = $ga_result['total'] ?? 0;
} elseif ($ga_category_id !== '') {
    $ga_result = ga_fetch_articles(GA_LIST_PAGE_TAKE, $ga_skip, $ga_category_id, $ga_include_children, null, false, true);
    $ga_list_articles = $ga_result['items'] ?? [];
    $ga_total = $ga_result['total'] ?? 0;
}
$ga_total_pages = $ga_total > 0 ? (int) ceil($ga_total / GA_LIST_PAGE_TAKE) : 1;

// Sidebar widgets — small fixed lists (not affected by the main category/pagination above).
$ga_sidebar_gossip = ga_fetch_articles(GA_LIST_SIDEBAR_COUNT, 0, GA_NAV_CATEGORY_IDS['movie-gossip'])['items'] ?? [];
$ga_sidebar_reviews = ga_fetch_articles(GA_LIST_SIDEBAR_COUNT, 0, GA_NAV_CATEGORY_IDS['reviews'])['items'] ?? [];

// Base query params for the legacy ?categoryId=/?tagId= form — only used as a pagination
// fallback when this request itself isn't on a clean path (see ga_list_page_url() below).
$ga_base_params = $ga_is_tag_mode
    ? ['tagId' => $ga_tag_id, 'tagName' => $ga_tag_name]
    : ['categoryId' => $ga_category_id, 'categoryName' => $ga_category_name];
if (!$ga_is_tag_mode && $ga_include_children) {
    $ga_base_params['includeChildren'] = 'true';
}

function ga_list_page_url(string $cleanPath, array $legacyParams, int $page): string
{
    if ($cleanPath !== '') {
        return '/' . $cleanPath . ($page > 1 ? '?page=' . $page : '');
    }
    $params = $legacyParams;
    if ($page > 1) {
        $params['page'] = $page;
    }
    return 'list-page.php?' . http_build_query($params);
}
?>
<html lang="en">

<head>
    <!-- Clean category/tag URLs now carry 1-3 path segments (e.g. movies/gossip,
         politics/andhra, tag/{id}/{slug}), so every relative asset path below needs
         anchoring to root — same fix already applied to inner-page.php for the same reason. -->
    <base href="/">
    <script type="text/javascript" async=""
        src="https://cdn.confiant-integrations.net/RNw7xiqRu-6_97G1pl1Hr7_2fbE/gpt_and_prebid/config.js"></script>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Latest News Today,Current News,online News,Latest Breaking News Headlines,Live News,Video News- Greatandhra
    </title>
    <!-- <meta name="description"
        content="Telugu Movie News, Latest Telugu Cinema News, Tollywood Film News, Telugu Film News, Tollywood News, Tollywood Latest News, Latest Tollywood News, Latest Telugu Film News, Andhra News, Telugu Cinema Gossips, Latest Andhra Updates, Andhra Political News, Telugu Cinema Updates and Andhra News">
    <meta name="keywords"
        content="Telugu Movie News, Latest Telugu Cinema News, Tollywood Film News, Telugu Film News, Tollywood News, Tollywood Latest News, Latest Tollywood News, Latest Telugu Film News, Andhra News, Telugu Cinema Gossips, Latest Andhra Updates, Andhra Political News, Telugu Cinema Updates and Andhra News">
    <link rel="canonical" href="https://www.greatandhra.com/latest">
     -->

    <link href="https://fonts.googleapis.com/css?family=Roboto:400,400italic,700,700italic|Roboto+Condensed:400,700"
        rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&amp;display=swap"
        rel="stylesheet">
    <!-- <meta name="robots" content="index, follow"> -->
    <link href="./css/great_andhra_style_landing_pages.css?v=<?php echo ga_asset_version('css/great_andhra_style_landing_pages.css'); ?>" rel="stylesheet">
    <link href="./css/footer.css?v=<?php echo ga_asset_version('css/footer.css'); ?>" rel="stylesheet">
    <link href="css/site-ads.css?v=<?php echo ga_asset_version('css/site-ads.css'); ?>" rel="stylesheet">
    <link href="css/header-mob.css?v=<?php echo ga_asset_version('css/header-mob.css'); ?>" rel="stylesheet">
    <script src="js/drawer.js?v=<?php echo ga_asset_version('js/drawer.js'); ?>"> </script>
    <!-- <link href="https://www.greatandhra.com/css/image_preview.css" rel="stylesheet" type="text/css">
	<script async="" src="//cdn.confiant-integrations.net/gptprebidnative/202603241056/wrap.js"></script>
	<script
		src="https://pagead2.googlesyndication.com/pagead/managed/js/adsense/m202604030101/show_ads_impl_fy2021.js"></script>
	<script type="text/javascript" async="" src="https://www.google-analytics.com/analytics.js"></script>
	<script type="text/javascript" async=""
		src="https://www.googletagmanager.com/gtag/js?id=UA-195166-1&amp;cx=c&amp;gtm=4e6471h1"></script>
	<script type="text/javascript" async="" src="https://d31qbv1cthcecs.cloudfront.net/atrk.js"></script>
	<script type="text/javascript" src="js/jquery-1.3.2.js"></script>
	<link rel="alternate" media="only screen and (max-width: 640px)" href="https://m.greatandhra.com/category.php?id=1"
		hreflang="en"> -->

    <!-- Start Alexa Certify Javascript -->
    <!-- <script type="text/javascript">
		_atrk_opts = { atrk_acct: "TbYbo1IWNa10Y8", domain: "greatandhra.com", dynamic: true };
		(function () { var as = document.createElement('script'); as.type = 'text/javascript'; as.async = true; as.src = "https://d31qbv1cthcecs.cloudfront.net/atrk.js"; var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(as, s); })();
	</script>
	<noscript><img src="https://d5nxst8fruw4z.cloudfront.net/atrk.gif?account=TbYbo1IWNa10Y8" style="display:none"
			height="1" width="1" alt="" /></noscript> -->
    <!-- End Alexa Certify Javascript -->

    <link href="./css/main-list-page.css?v=<?php echo ga_asset_version('css/main-list-page.css'); ?>" rel="stylesheet" />
    <link href="./css/list-page-mobile-responsive.css?v=<?php echo ga_asset_version('css/list-page-mobile-responsive.css'); ?>" rel="stylesheet" />

    <!-- <meta http-equiv="origin-trial"
		content="AlK2UR5SkAlj8jjdEc9p3F3xuFYlF6LYjAML3EOqw1g26eCwWPjdmecULvBH5MVPoqKYrOfPhYVL71xAXI1IBQoAAAB8eyJvcmlnaW4iOiJodHRwczovL2RvdWJsZWNsaWNrLm5ldDo0NDMiLCJmZWF0dXJlIjoiV2ViVmlld1hSZXF1ZXN0ZWRXaXRoRGVwcmVjYXRpb24iLCJleHBpcnkiOjE3NTgwNjcxOTksImlzU3ViZG9tYWluIjp0cnVlfQ==">
	<meta http-equiv="origin-trial"
		content="Amm8/NmvvQfhwCib6I7ZsmUxiSCfOxWxHayJwyU1r3gRIItzr7bNQid6O8ZYaE1GSQTa69WwhPC9flq/oYkRBwsAAACCeyJvcmlnaW4iOiJodHRwczovL2dvb2dsZXN5bmRpY2F0aW9uLmNvbTo0NDMiLCJmZWF0dXJlIjoiV2ViVmlld1hSZXF1ZXN0ZWRXaXRoRGVwcmVjYXRpb24iLCJleHBpcnkiOjE3NTgwNjcxOTksImlzU3ViZG9tYWluIjp0cnVlfQ==">
	<meta http-equiv="origin-trial"
		content="A9nrunKdU5m96PSN1XsSGr3qOP0lvPFUB2AiAylCDlN5DTl17uDFkpQuHj1AFtgWLxpLaiBZuhrtb2WOu7ofHwEAAACKeyJvcmlnaW4iOiJodHRwczovL2RvdWJsZWNsaWNrLm5ldDo0NDMiLCJmZWF0dXJlIjoiQUlQcm9tcHRBUElNdWx0aW1vZGFsSW5wdXQiLCJleHBpcnkiOjE3NzQzMTA0MDAsImlzU3ViZG9tYWluIjp0cnVlLCJpc1RoaXJkUGFydHkiOnRydWV9">
	<meta http-equiv="origin-trial"
		content="A93bovR+QVXNx2/38qDbmeYYf1wdte9EO37K9eMq3r+541qo0byhYU899BhPB7Cv9QqD7wIbR1B6OAc9kEfYCA4AAACQeyJvcmlnaW4iOiJodHRwczovL2dvb2dsZXN5bmRpY2F0aW9uLmNvbTo0NDMiLCJmZWF0dXJlIjoiQUlQcm9tcHRBUElNdWx0aW1vZGFsSW5wdXQiLCJleHBpcnkiOjE3NzQzMTA0MDAsImlzU3ViZG9tYWluIjp0cnVlLCJpc1RoaXJkUGFydHkiOnRydWV9">
	<meta http-equiv="origin-trial"
		content="A1S5fojrAunSDrFbD8OfGmFHdRFZymSM/1ss3G+NEttCLfHkXvlcF6LGLH8Mo5PakLO1sCASXU1/gQf6XGuTBgwAAACQeyJvcmlnaW4iOiJodHRwczovL2dvb2dsZXRhZ3NlcnZpY2VzLmNvbTo0NDMiLCJmZWF0dXJlIjoiQUlQcm9tcHRBUElNdWx0aW1vZGFsSW5wdXQiLCJleHBpcnkiOjE3NzQzMTA0MDAsImlzU3ViZG9tYWluIjp0cnVlLCJpc1RoaXJkUGFydHkiOnRydWV9"> -->
    <!-- <script async="" src="https://cdn.vuukle.com/platform.js"></script>
    <script type="text/javascript" src="https://cdn.vuukle.com/domain-configs/greatandhra.com.js" async=""></script>
    <script type="text/javascript" src="https://cdn.vuukle.com/static/prebid3.js" async=""></script>
    <script type="text/javascript"
        src="https://cdn.confiant-integrations.net/RNw7xiqRu-6_97G1pl1Hr7_2fbE/gpt_and_prebid/config.js"></script>
    <script type="text/javascript" src="https://securepubads.g.doubleclick.net/tag/js/gpt.js"></script> -->
    <!-- <meta http-equiv="origin-trial"
		content="AlK2UR5SkAlj8jjdEc9p3F3xuFYlF6LYjAML3EOqw1g26eCwWPjdmecULvBH5MVPoqKYrOfPhYVL71xAXI1IBQoAAAB8eyJvcmlnaW4iOiJodHRwczovL2RvdWJsZWNsaWNrLm5ldDo0NDMiLCJmZWF0dXJlIjoiV2ViVmlld1hSZXF1ZXN0ZWRXaXRoRGVwcmVjYXRpb24iLCJleHBpcnkiOjE3NTgwNjcxOTksImlzU3ViZG9tYWluIjp0cnVlfQ==">
	<meta http-equiv="origin-trial"
		content="Amm8/NmvvQfhwCib6I7ZsmUxiSCfOxWxHayJwyU1r3gRIItzr7bNQid6O8ZYaE1GSQTa69WwhPC9flq/oYkRBwsAAACCeyJvcmlnaW4iOiJodHRwczovL2dvb2dsZXN5bmRpY2F0aW9uLmNvbTo0NDMiLCJmZWF0dXJlIjoiV2ViVmlld1hSZXF1ZXN0ZWRXaXRoRGVwcmVjYXRpb24iLCJleHBpcnkiOjE3NTgwNjcxOTksImlzU3ViZG9tYWluIjp0cnVlfQ==">
	<meta http-equiv="origin-trial"
		content="A9nrunKdU5m96PSN1XsSGr3qOP0lvPFUB2AiAylCDlN5DTl17uDFkpQuHj1AFtgWLxpLaiBZuhrtb2WOu7ofHwEAAACKeyJvcmlnaW4iOiJodHRwczovL2RvdWJsZWNsaWNrLm5ldDo0NDMiLCJmZWF0dXJlIjoiQUlQcm9tcHRBUElNdWx0aW1vZGFsSW5wdXQiLCJleHBpcnkiOjE3NzQzMTA0MDAsImlzU3ViZG9tYWluIjp0cnVlLCJpc1RoaXJkUGFydHkiOnRydWV9">
	<meta http-equiv="origin-trial"
		content="A93bovR+QVXNx2/38qDbmeYYf1wdte9EO37K9eMq3r+541qo0byhYU899BhPB7Cv9QqD7wIbR1B6OAc9kEfYCA4AAACQeyJvcmlnaW4iOiJodHRwczovL2dvb2dsZXN5bmRpY2F0aW9uLmNvbTo0NDMiLCJmZWF0dXJlIjoiQUlQcm9tcHRBUElNdWx0aW1vZGFsSW5wdXQiLCJleHBpcnkiOjE3NzQzMTA0MDAsImlzU3ViZG9tYWluIjp0cnVlLCJpc1RoaXJkUGFydHkiOnRydWV9">
	<meta http-equiv="origin-trial"
		content="A1S5fojrAunSDrFbD8OfGmFHdRFZymSM/1ss3G+NEttCLfHkXvlcF6LGLH8Mo5PakLO1sCASXU1/gQf6XGuTBgwAAACQeyJvcmlnaW4iOiJodHRwczovL2dvb2dsZXRhZ3NlcnZpY2VzLmNvbTo0NDMiLCJmZWF0dXJlIjoiQUlQcm9tcHRBUElNdWx0aW1vZGFsSW5wdXQiLCJleHBpcnkiOjE3NzQzMTA0MDAsImlzU3ViZG9tYWluIjp0cnVlLCJpc1RoaXJkUGFydHkiOnRydWV9"> -->
    <!-- <script src="https://securepubads.g.doubleclick.net/pagead/managed/js/gpt/m202604080101/pubads_impl.js?cb=31097742"
        async=""></script>
    <link href="https://securepubads.g.doubleclick.net/pagead/managed/dict/m202604070101/gpt"
        rel="compression-dictionary">
    <script async="" src="https://fundingchoicesmessages.google.com/i/123116330?ers=3"></script>
    <script async="" id="AV61613e403ff92a4a1008c1a4"
        src="https://tg1.vuukle.com/api/adserver/spt?AV_TAGID=61613e403ff92a4a1008c1a4&amp;AV_PUBLISHERID=607eaa2728f57c570863c77a"
        type="text/javascript"></script> -->
    <!-- <style id="vuukle-ad-25-styles">
        #vuukle-ad-25 .vuukle-ad-label {
            display: none !important;
        }

        @media only screen and (max-width: 997px) {
            #vuukle-ad-25 {
                width: 320px !important;
                min-width: unset !important;
                min-height: 60px !important;
                height: unset !important;
                left: 50% !important;
                transform: translate(-50%) !important;

            }

            .vuukle-sticky-ad-bg {
                height: 68px !important;
                left: 50% !important;
                transform: translate(-50%) !important;
            }

            .vuukle-sticky-ad-label {}

            .vuukle-sticky-ad-label>p {}

            .vuukle-sticky-ad-label>a {}

            .vuukle-sticky-ad-label>.vuukle-sticky-ad-label-text-compact {}
        }
    </style>
    <script async=""
        src="https://fundingchoicesmessages.google.com/f/AGSKWxVt_hVFKf2wXR1k1vn4LtbObd0GMuoHJrVmZHqn2mPygZd9S38-9JBhcEZvH_bL8NpYy6G7PJDC7Z4nRdYMZbzVp8VctrMPNUs9wxq5Lq6Ztf4iXLoHTrd86nXZHw3rOA8nEGV5CA==?fccs=W1siQUtzUm9sOVZheFh4REl5LVZNcW9tb0JuS3RKdE1LTW5xd1Rnanp3RS1oaHRBNE9XUDlkQVA3VHVMamNOUXdzSzBVSWJfYllOeWl2X3N6NHg4VHROV2VmQjdBaU84aWRlZWtScXdsLS1yWTJkTHI3ZmJkbjBlS1FSb1piNGVLVHZVVTl3MEdBb0VOekljNFVWNGJ4cUdsOEhoc0N3S1dhb0xBPT0iXSxudWxsLG51bGwsbnVsbCxudWxsLG51bGwsWzE3NzU3MzE5NTgsMjkwMDAwMDBdLG51bGwsbnVsbCxudWxsLFtudWxsLFs3XV0sImh0dHBzOi8vd3d3LmdyZWF0YW5kaHJhLmNvbS9sYXRlc3QiLG51bGwsW1s4LCJhVDJwLXppZ19lVSJdLFs5LCJlbi1VUyJdLFsxOCwiW1tbbnVsbCw5NThdXV0iXSxbMjMsIjE3NzMzOTY1NDQiXSxbMzUsIjE3NzMzOTY1NDciXSxbMjYsIjE5Il0sWzI3LCI2Il0sWzE5LCIyIl0sWzI0LCJ3d3cuZ3JlYXRhbmRocmEuY29tIl0sWzI1LCJbWzk1Mzg2MTkwXV0iXSxbMjksImZhbHNlIl1dXQ"></script>
    <script esp-signal="true" src="https://cdn.jsdelivr.net/gh/prebid/shared-id/pubcid.js/docs/pubcid.min.js"></script>
    <script esp-signal="true" src="https://cdn.id5-sync.com/api/1.0/esp.js"></script>
    <script esp-signal="true" src="https://invstatic101.creativecdn.com/encrypted-signals/encrypted-tag-g.js"></script> -->
    <link type="text/css" href="//fonts.googleapis.com/css?family=Google%20Sans%3A400" rel="stylesheet">
    <link type="text/css" href="//fonts.googleapis.com/css?family=Google%20Sans%3A700" rel="stylesheet">
    <!-- <script async=""
        src="https://fundingchoicesmessages.google.com/f/AGSKWxUhV-LpmKN7x14HTRRxJ7HKCVTbQYiIGqZsTdT0tNhYFbKFt1zSccl2Yq-jdt8K4dveyL3cgBa6ujBFtZ1dG7vrboCwlYjChJYCtxxcZ7RJ9TmOba6ankiPfWRPgEpxpR_bEF-VdQ==?fccs=W1siQUtzUm9sOVZheFh4REl5LVZNcW9tb0JuS3RKdE1LTW5xd1Rnanp3RS1oaHRBNE9XUDlkQVA3VHVMamNOUXdzSzBVSWJfYllOeWl2X3N6NHg4VHROV2VmQjdBaU84aWRlZWtScXdsLS1yWTJkTHI3ZmJkbjBlS1FSb1piNGVLVHZVVTl3MEdBb0VOekljNFVWNGJ4cUdsOEhoc0N3S1dhb0xBPT0iXSxudWxsLG51bGwsbnVsbCxudWxsLG51bGwsWzE3NzU3MzE5NTgsODI0MDAwMDAwXSxudWxsLG51bGwsbnVsbCxbbnVsbCxbNyw2XSxudWxsLG51bGwsbnVsbCxudWxsLG51bGwsbnVsbCxudWxsLG51bGwsbnVsbCwxXSwiaHR0cHM6Ly93d3cuZ3JlYXRhbmRocmEuY29tL2xhdGVzdCIsbnVsbCxbWzgsImFUMnAtemlnX2VVIl0sWzksImVuLVVTIl0sWzE4LCJbW1tudWxsLDk1OF1dXSJdLFsyMywiMTc3MzM5NjU0NCJdLFszNSwiMTc3MzM5NjU0NyJdLFsyNiwiMTkiXSxbMjcsIjYiXSxbMTksIjIiXSxbMjQsInd3dy5ncmVhdGFuZGhyYS5jb20iXSxbMjUsIltbOTUzODYxOTBdXSJdLFsyOSwiZmFsc2UiXV1d"></script>
    <script async=""
        src="https://fundingchoicesmessages.google.com/f/AGSKWxVm7DS2Iu-Hty-W5MeBCdtNPEoChC9u0srP5x7VRZZmyfVUQjEgGZZTr7qIJwj-CDAXbMitKvlwhptI74IDetF1Vqd_Zt7NqbWrFelcOuIjtU01upkCkcEyfUgwWyJM_wdA6wr0jQ==?fccs=W1siQUtzUm9sOVZheFh4REl5LVZNcW9tb0JuS3RKdE1LTW5xd1Rnanp3RS1oaHRBNE9XUDlkQVA3VHVMamNOUXdzSzBVSWJfYllOeWl2X3N6NHg4VHROV2VmQjdBaU84aWRlZWtScXdsLS1yWTJkTHI3ZmJkbjBlS1FSb1piNGVLVHZVVTl3MEdBb0VOekljNFVWNGJ4cUdsOEhoc0N3S1dhb0xBPT0iXSxudWxsLG51bGwsbnVsbCxudWxsLG51bGwsWzE3NzU3MzE5NTgsOTEwMDAwMDAwXSxudWxsLG51bGwsbnVsbCxbbnVsbCxbNyw2LDEwXSxudWxsLG51bGwsbnVsbCxudWxsLG51bGwsbnVsbCxudWxsLG51bGwsbnVsbCwxXSwiaHR0cHM6Ly93d3cuZ3JlYXRhbmRocmEuY29tL2xhdGVzdCIsbnVsbCxbWzgsImFUMnAtemlnX2VVIl0sWzksImVuLVVTIl0sWzE4LCJbW1tudWxsLDk1OF1dXSJdLFsyMywiMTc3MzM5NjU0NCJdLFszNSwiMTc3MzM5NjU0NyJdLFsyNiwiMTkiXSxbMjcsIjYiXSxbMTksIjIiXSxbMjQsInd3dy5ncmVhdGFuZGhyYS5jb20iXSxbMjUsIltbOTUzODYxOTBdXSJdLFsyOSwiZmFsc2UiXV1d"></script>
    <script async=""
        src="https://fundingchoicesmessages.google.com/f/AGSKWxUbpK279Uo1bVwg5CLCKNsHN4YPl3FBW8vBnNm9nly1VKVcb8b17sz20dgg2L1YX_L4Zw4qvDzGszS8L1Fjw0YbfVW61l3NSw3A61dpgMwtyK0MN5BIdum4GS_6k-BLJVsHkocTvQ==?fccs=W1siQUtzUm9sOVZheFh4REl5LVZNcW9tb0JuS3RKdE1LTW5xd1Rnanp3RS1oaHRBNE9XUDlkQVA3VHVMamNOUXdzSzBVSWJfYllOeWl2X3N6NHg4VHROV2VmQjdBaU84aWRlZWtScXdsLS1yWTJkTHI3ZmJkbjBlS1FSb1piNGVLVHZVVTl3MEdBb0VOekljNFVWNGJ4cUdsOEhoc0N3S1dhb0xBPT0iXSxudWxsLG51bGwsbnVsbCxudWxsLG51bGwsWzE3NzU3MzE5NTgsOTgwMDAwMDAwXSxudWxsLG51bGwsbnVsbCxbbnVsbCxbNyw2LDEwLDldLG51bGwsMixudWxsLCJlbiIsbnVsbCxudWxsLG51bGwsbnVsbCxudWxsLDFdLCJodHRwczovL3d3dy5ncmVhdGFuZGhyYS5jb20vbGF0ZXN0IixudWxsLFtbOCwiYVQycC16aWdfZVUiXSxbOSwiZW4tVVMiXSxbMTgsIltbW251bGwsOTU4XV1dIl0sWzIzLCIxNzczMzk2NTQ0Il0sWzM1LCIxNzczMzk2NTQ3Il0sWzI2LCIxOSJdLFsyNywiNiJdLFsxOSwiMiJdLFsyNCwid3d3LmdyZWF0YW5kaHJhLmNvbSJdLFsyNSwiW1s5NTM4NjE5MF1dIl0sWzI5LCJmYWxzZSJdXV0"></script>
     <style type="text/css"> -->
    <!-- :root {
			Rt: env(safe-area-inset-top);
			kt: env(safe-area-inset-bottom);
			Ct: env(safe-area-inset-left);
			Dt: env(safe-area-inset-right);
		}
	</style> -->
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script> -->
    <link type="text/css" rel="stylesheet"
        href="chrome-extension://fheoggkfdfchfphceeifdbepaooicaho/css/mcafee_fonts.css">
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script> -->
    <?php // jQuery, loaded here (head) same as index.php - every inline script below the top
          // banner ad assumes it's already available. Previously loaded from
          // https://www.greatandhra.com/js/jquery.min.1.8.2.js, the old WordPress domain's copy
          // of this file - confirmed that now 404s there, so jQuery silently never loaded on
          // this page at all, breaking every $()-dependent script on it (sticky nav, search
          // toggle, dropdowns, and the sidebar-ad positioning script added below). ?>
    <script type="text/javascript" src="assets/jquery.min.1.8.2.js"></script>
</head>

<body class="home_bg">
    <?php ga_render_interstitial_overlay($ga_interstitial_decision); ?>
    <?php ga_render_bottom_sticky_ad(); ?>
    <!-- Global site tag (gtag.js) - Google Analytics
	<script async="" src="https://www.googletagmanager.com/gtag/js?id=G-PX1LPBMH02"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag() { dataLayer.push(arguments); }
		gtag('js', new Date());

		gtag('config', 'G-PX1LPBMH02');
	</script> -->
    <!-- <style>
        .close_button {
            display: none;
        }
    </style> -->

    <div class="local_great" style="position:fixed; width:100%; float:left; width:80px;">
        <div class="source-image-left" style="float:left">
            <?php ga_render_ad('LISTPAGE_SIDEBAR_LEFT'); ?>
        </div>
    </div>
    <div class="local_great" style="position:fixed; width:120px; right:0;">
        <div class="source-image-right" style="float:right">
            <?php ga_render_ad('LISTPAGE_SIDEBAR_RIGHT'); ?>
        </div>
    </div>

    <!--great_andhra_body-->
    <div class="great_andhra_movie_body">
        <!--great_andhra_inner_body-->
        <div class="great_andhra_movie_inner_body">
            <!--great_andhra_search_panel-->
            <!--great_andhra_logo_panel-->
            <style>
                /* .great_andhra_logo_panel {
                    height: 110px;
                    margin: 10px 0px;
                } */
            </style>
            <div class="great_andhra_logo_panel">
                <a href="/" class="logo">
                    <img src="./images/great_andhra.gif" title="Greatandhra website logo" alt="Greatandhra logo">
                </a>
                <div class="AdinHedare">
                    <?php // Same ad as the homepage's top banner - not independently manageable. ?>
                    <?php ga_render_ad('LISTPAGE_TOP_BANNER'); ?>
                </div>
            </div>

            <!--great_andhra_logo_panel-->
            <!--great_andhra_main_menu_panel-->
            <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.0/css/all.css"
                integrity="sha384-lZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9S+oqd12jhcu+A56Ebc1zFSJ"
                crossorigin="anonymous">
            <script>
                <?php // #great_andhra_main_menu_panel_2019 doesn't exist on this page (it's
                      // homepage-only markup) - $(...).offset() on an empty jQuery set returns
                      // undefined, so .top threw uncaught here on every load. That silently
                      // aborted every OTHER $(document).ready() handler registered later in the
                      // page too (jQuery 1.8's ready-queue stops on an unhandled exception),
                      // which is why the sidebar-ad positioning script below never ran. ?>
                $(function () {
                    var $stickyNav = $('#great_andhra_main_menu_panel_2019');
                    if ($stickyNav.length === 0) {
                        return;
                    }

                    // grab the initial top offset of the navigation
                    var sticky_navigation_offset_top = $stickyNav.offset().top;

                    // our function that decides weather the navigation bar should have "fixed" css position or not.
                    var great_andhra_main_menu_panel_2019 = function () {
                        var scroll_top = $(window).scrollTop(); // our current vertical position from the top

                        // if we've scrolled more than the navigation, change its position to fixed to stick to top, otherwise change it back to relative
                        if (scroll_top > sticky_navigation_offset_top) {
                            $('#great_andhra_main_menu_panel_2019').css({ 'position': 'fixed', 'top': 0 });
                        } else {
                            $('#great_andhra_main_menu_panel_2019').css({ 'position': 'relative' });
                        }
                    };

                    // run our function on load
                    great_andhra_main_menu_panel_2019();

                    // and run it again every time you scroll
                    $(window).scroll(function () {
                        great_andhra_main_menu_panel_2019();
                    });

                    // NOT required:
                    // for this demo disable all links that point to "#"
                    $('a[href="#"]').click(function (event) {
                        event.preventDefault();
                    });

                });
            </script>

            <!---Search button-->
            <script type="text/javascript">
                $(document).ready(function (e) {
                    $('.search_img').click(function () {
                        $('#search_box_new').slideToggle('slow');
                    });
                });
            </script>

            <!-- <style>
				.dropbtn {
					background-color: #ffffff;
					color: #5b5b5b;
					padding: 7px;
					font-size: 15px;
					font-family: Roboto, sans-serif;
					font-weight: bold;
					border: none;
					cursor: pointer;
				}

				.dropdown {
					display: inline-block;
					float: right;
					width: 200px;
				}

				.dropdown-content {
					display: none;
					position: absolute;
					background-color: #ffffff;
					min-width: 145px;
					box-shadow: 0px 8px 16px 0px;
					z-index: 3;
					margin-left: 20px;
					margin-top: 35px;
				}

				.dropdown-content a {
					color: #5b5b5b;
					padding: 8px 16px;
					text-decoration: none;
					display: block;
					font-size: 15px;
					font-family: Roboto, sans-serif;
					font-weight: bold;

				}

				.dropbtn img {
					position: relative;
					float: left;
					margin-top: 4px;
					margin-right: 2px;

				}

				.dropdown-content a:hover {
					background-color: #ffffff;
					color: #ed1b24;
				}

				/*.dropdown:hover .dropdown-content {
    display: block;
}*/

				.dropdown:hover .dropbtn {
					background-color: #ffffff;
				}

				.dropbtn p {
					background-color: #ffffff;
					color: #5b5b5b;
					padding: 7px;
					font-size: 15px;
					font-family: Roboto, sans-serif;
					font-weight: bold;
					border: none;
					cursor: pointer;
					margin-top: -1px;
					font-size: 14px;
				}



				.c-hamburger {
					display: block;
					position: relative;
					overflow: hidden;
					margin: 0;
					padding: 0;
					width: 200px;
					height: 31px;
					font-size: 20px;
					/* text-indent: 9999px; */
					-webkit-appearance: none;
					-moz-appearance: none;
					appearance: none;
					box-shadow: none;
					border-radius: none;
					border: none;
					cursor: pointer;
					-webkit-transition: background .3s;
					transition: background .3s;
					float: left;
				}

				.c-hamburger:focus {
					outline: 0
				}

				.c-hamburger span {
					display: block;
					position: absolute;
					top: 15px;
					left: 8px;
					right: 8px;
					height: 3px;
					background: #5d5d5d;
					width: 20px;
				}

				.c-hamburger span::after,
				.c-hamburger span::before {
					position: absolute;
					display: block;
					left: 0;
					width: 100%;
					height: 3px;
					background-color: #5d5d5d;
					content: ""
				}

				.c-hamburger--htla.is-active span::after,
				.c-hamburger--htla.is-active span::before,
				.c-hamburger--htra.is-active span::after,
				.c-hamburger--htra.is-active span::before {
					width: 50%
				}

				.c-hamburger span::before {
					top: -7px
				}

				.c-hamburger span::after {
					bottom: -7px
				}



				.c-hamburger--htx span {
					-webkit-transition: background 0 .3s;
					transition: background 0 .3s
				}

				.c-hamburger--htx span::after,
				.c-hamburger--htx span::before {
					-webkit-transition-duration: .3s, .3s;
					transition-duration: .3s, .3s;
					-webkit-transition-delay: .3s, 0s;
					transition-delay: .3s, 0s;
				}

				.is-active c-hamburger--htx span::after,
				.c-hamburger--htx span::before {
					-webkit-transition-duration: .3s, .3s;
					transition-duration: .3s, .3s;
					-webkit-transition-delay: .3s, 0s;
					transition-delay: .3s, 0s;

				}

				.c-hamburger--htx span::before {
					-webkit-transition-property: top, -webkit-transform;
					transition-property: top, transform
				}

				.c-hamburger--htx span::after {
					-webkit-transition-property: bottom, -webkit-transform;
					transition-property: bottom, transform
				}



				.c-hamburger--htx.is-active span {
					background: 0 0
				}

				.c-hamburger--htx.is-active span::before {
					top: 0;
					-webkit-transform: rotate(45deg);
					-ms-transform: rotate(45deg);
					transform: rotate(45deg);
					background-color: #cb0032;
				}

				.c-hamburger--htx.is-active span::after {
					bottom: 0;
					-webkit-transform: rotate(-45deg);
					-ms-transform: rotate(-45deg);
					transform: rotate(-45deg);
					background-color: #cb0032;
				}

				.c-hamburger--htx.is-active span::after,
				.c-hamburger--htx.is-active span::before {
					-webkit-transition-delay: 0s, .3s;
					transition-delay: 0s, .3s
				}


				.great_andhra_main_menu_panel_2019 {
					height: 35px !important;
					background: #fbfbfb;
					width: 990px;
					margin: 0;
					padding: 0;
					float: left;
					overflow: visible;
					z-index: 1;
					border-top: 1px solid #e6e6e6;
					border-bottom: 1px solid #e6e6e6;
					margin-bottom: 5px;
					border-right: 1px solid #e6e6e6;
					border-left: 1px solid #e6e6e6;
				}


				.great_andhra_main_menu_panel_2019 ul {
					font-size: 13px !important;
					margin: 0 !important;
					padding: 0 !important;
					list-style: none !important;
				}

				.great_andhra_main_menu_panel_2019 ul li {
					display: block !important;
					position: relative !important;
					float: left !important;
				}

				.great_andhra_main_menu_panel_2019 li ul {
					display: none !important;
					z-index: 999;
					min-width: 120px;
				}

				.great_andhra_main_menu_panel_2019 ul li a {
					display: block !important;
					text-decoration: none !important;
					white-space: nowrap !important;
					text-transform: capitalize;
					font-weight: 600 !important;
					color: #333333;
					font-family: Roboto, sans-serif;
					padding: 0 13px !important;
					height: 35px;
					line-height: 33px;
					margin: 0;
					border-left: 0px solid #e6e6e6;
					font-family: 'Lato' !important;
					letter-spacing: 0.5px;
				}

				.great_andhra_main_menu_panel_2019 ul li a:hover {
					background: #f8f8f8 !important;
				}

				.great_andhra_main_menu_panel_2019 li:hover ul {
					display: block !important;
					position: absolute !important;
				}

				.great_andhra_main_menu_panel_2019 li:hover li {
					float: none !important;
					font-size: 11px !important;
				}

				.great_andhra_main_menu_panel_2019 li:hover a {
					background: #f8f8f8 !important;
				}

				.great_andhra_main_menu_panel_2019 li:hover li a:hover {
					background: #f8f8f8 !important;
					color: #ee1c25;
				}

				#menu img {
					float: right;
					padding-top: 12px;
					padding-left: 5px;
				}

				i {
					color: #a5a5a5;
					transition: color .3s;
					font-size: 20px;
					padding: 6px 0px;
				}

				.social-icons {
					float: right;
				}

				i.fab.fa-youtube:hover {
					color: #e02a26;
				}

				i.fab.fa-twitter:hover {
					color: #4099ff;
				}

				i.fab.fa-facebook-f:hover {
					color: #3b5998;
				}

				i.fas.fa-caret-down {
					font-size: inherit;
					text-rendering: auto;
					-webkit-font-smoothing: antialiased;
					padding-left: 1px;
				}

				#epaper img {
					float: right;
					padding-top: 9px;
					padding-left: 0px;
				}

				i.fab.fa-youtube {
					color: #e02a26;
				}

				i.fab.fa-twitter {
					color: #4099ff;
				}

				i.fab.fa-facebook-f {
					color: #3b5998;
				}

				i.fas.fa-home {
					font-size: 16px;
					margin-top: 2px;
					color: #333333;
				}

				li.social-icons {
					width: 250px;
					padding-top: 2px;
				}

				.great_andhra_main_menu_panel_2019 li.social-icons ul {
					display: block !important;
					position: relative !important;
					float: right !important;
				}
			</style> -->
            <script>
                $(document).ready(function () {

                    /*$("body").click(function(){
                        $(".dropdown-content").removeAttr('style');
                    });*/
                    $(".dropdown").click(function () {
                        $(".dropdown-content").toggle();
                    });
                });
            </script>
            <!-- <div class="great_andhra_main_menu_panel_2019" id="great_andhra_main_menu_panel_2019"
				style="position: relative; top: 0px;">
				<ul id="menu">
					<li><a href="index.php" title="greandhra home"><i class="fas fa-home"
								style="color:#333333;"></i></a></li>
					<li><a href="https://www.greatandhra.com/latest">Latest</a></li>
					<li id="Latest">
						<a href="https://www.greatandhra.com/politics" class="main_menu_head" id="0">
							Politics
							<i class="fas fa-caret-down"></i>
						</a>
						<ul>
							<li><a href="https://www.greatandhra.com/andhra-news" title="">Andhra</a></li>
							<li><a href="https://www.greatandhra.com/telangana-news" title="">Telangana</a></li>
							<li><a href="https://www.greatandhra.com/india-news" title="">India</a></li>
						</ul>
					</li>

					<li id="Latest">
						<a href="https://www.greatandhra.com/movies" class="main_menu_head" id="1">
							Movies
							<i class="fas fa-caret-down"></i>
						</a>
						<ul>
							<li><a href="https://www.greatandhra.com/movies/" title="go to news">news</a></li>
							<li><a href="https://www.greatandhra.com/moviegossip" title="">gossip</a></li>
							<li><a href="https://www.greatandhra.com/boxoffice" title="">boxoffice</a></li>
						</ul>
					</li>


					<li id="Reviews">
						<a href="https://www.greatandhra.com/reviews" class="main_menu_head" id="2">
							Reviews
						</a>
					</li>

					<li id="Gallery">
						<a href="http://gallery.greatandhra.com/index.php" class="main_menu_head" id="3">
							Gallery
						</a>
					</li>


					<li id="Opinion">
						<a href="https://www.greatandhra.com/opinion" itemprop="url">
							<span itemprop="name">Opinion</span>
						</a>
					</li>
					<li id="epaper">
						<a href="http://epaper.greatandhra.com/">
							<img src="https://www.greatandhra.com/images/ga-print.png" alt="greatandhra print">
						</a>
					</li>

					<li id="telugu_text">
						<a href="https://telugu.greatandhra.com/" style="font-size: 14px;" title="తెలుగు">
							తెలుగు
						</a>
					</li>

					<li class="social-icons">
						<ul>
							<li id="telugu_text">
								<a href="https://www.facebook.com/greatandhra" target="_blank" title="facebook">
									<i class="fab fa-facebook-f"></i>
								</a>
							</li>

							<li id="telugu_text">
								<a href="https://twitter.com/greatandhranews" target="_blank" title="twitter">
									<i class="fab fa-twitter"></i>
								</a>
							</li>
							<li id="telugu_text">
								<a href="https://www.youtube.com/channel/UCoarMz-cpxAnBy8tszp35wA" target="_blank"
									title="youtube">
									<i class="fab fa-youtube"></i>
								</a>
							</li>
						</ul>
					</li>

				</ul>
			</div> -->

            <!-- new nav bar -->
            <nav class="ga-nav" itemscope itemtype="https://www.schema.org/SiteNavigationElement">
                <ul class="menu">
                    <!-- Home Icon -->
                    <li class="menu-item">
                        <a href="index.php" class="menu-link" title="greandhra home" itemprop="url">
                            <span itemprop="name"><i class="fas fa-home" style="color:#333333;"></i></span>
                        </a>
                    </li>

                    <li class="menu-item">
                        <a href="<?php echo ga_e(ga_nav_category_link('latest-news', 'Latest News')); ?>" class="menu-link" itemprop="url">
                            <span itemprop="name">latest</span>
                        </a>
                    </li>

                    <!-- Politics with Dropdown -->
                    <li class="menu-item">
                        <a href="<?php echo ga_e(ga_nav_category_link('politics', 'Politics', true)); ?>" class="menu-link" itemprop="url">
                            <span itemprop="name">politics</span>
                            <i class="fas fa-caret-down"></i>
                        </a>
                        <ul class="dropdown">
                            <li><a href="<?php echo ga_e(ga_nav_category_link('andhra-news', 'Andhra News')); ?>" itemprop="url">andhra</a></li>
                            <li><a href="<?php echo ga_e(ga_nav_category_link('telangana-news', 'Telangana News')); ?>" itemprop="url">telangana</a></li>
                            <li><a href="<?php echo ga_e(ga_nav_category_link('india-news', 'India News')); ?>" itemprop="url">india</a></li>
                        </ul>
                    </li>

                    <!-- Movies with Dropdown -->
                    <li class="menu-item">
                        <a href="<?php echo ga_e(ga_nav_category_link('movies', 'Movies', true)); ?>" class="menu-link" itemprop="url">
                            <span itemprop="name">movies</span>
                            <i class="fas fa-caret-down"></i>
                        </a>
                        <ul class="dropdown">
                            <li><a href="<?php echo ga_e(ga_nav_category_link('movie-news', 'Movie News')); ?>" itemprop="url">news</a></li>
                            <li><a href="<?php echo ga_e(ga_nav_category_link('movie-gossip', 'Movie Gossip')); ?>" itemprop="url">gossip</a></li>
                            <li><a href="box-office" itemprop="url">boxoffice</a></li>
                        </ul>
                    </li>

                    <li class="menu-item">
                        <a href="<?php echo ga_e(ga_nav_category_link('reviews', 'Reviews')); ?>" class="menu-link">reviews</a>
                    </li>

                    <li class="menu-item">
                        <a href="https://gallery.greatandhra.com/index.php" class="menu-link" itemprop="url">
                            <span itemprop="name">gallery</span>
                        </a>
                    </li>

                    <li class="menu-item">
                        <a href="<?php echo ga_e(ga_nav_category_link('opinion', 'Opinion')); ?>" class="menu-link" itemprop="url">
                            <span itemprop="name">opinion</span>
                        </a>
                    </li>

                    <!-- Logo Link -->
                    <li class="menu-item">
                        <a href="http://epaper.greatandhra.com/" class="menu-link">
                            <!-- <span style="display: flex; align-items: center; gap: 2px;">
                            <span style="color: #333; font-weight: 800; font-size: 16px;">గ్రేట్<span
                                    style="color:red; font-style: italic;">ఆంధ్ర</span></span>
                            <span
                                style="color: #ff4500; font-weight: 900; font-size: 20px; font-family: sans-serif;">Print</span>
                        </span> -->
                            <img alt="greatandhra print" src="./images/ga-print.png" class="nav-print-img"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        </a>
                    </li>

                    <li class="menu-item">
                        <a href="https://telugu.greatandhra.com/" class="menu-link" title="తెలుగు" itemprop="url">
                            <span itemprop="name" style="font-size: 14px;">తెలుగు</span>
                        </a>
                    </li>

                    <!-- Social Media Group - Right Aligned -->
                    <li class="social-group">
                        <a href="https://www.facebook.com/greatandhra" target="_blank" title="facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/greatandhranews" target="_blank" title="twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://www.youtube.com/channel/UCoarMz-cpxAnBy8tszp35wA" target="_blank"
                            title="youtube">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            
            
            <div class="great_andhra_logo_panel-mob">
            <!-- First Row: Logo and Hamburger -->
            <div class="logo-bar">
                <a class="logo" href="/">
                    <img alt="Greatandhra logo" src="images/great_andhra.gif" title="Greatandhra website Logo" />
                </a>

                <button class="hamburger-menu" id="hamburgerBtn" aria-label="Menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>

            <!-- Mobile Navigation Menu -->
            <div class="mobile-nav-wrapper" id="mobileNav">
                <div class="mobile-nav-content">
                    <ul class="mobile-menu">
                        <li>
                            <a href="index.php">
                                <i class="fas fa-home"></i> Home
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo ga_e(ga_nav_category_link('latest-news', 'Latest News')); ?>">Latest</a>
                        </li>
                        <li class="has-submenu">
                            <a href="<?php echo ga_e(ga_nav_category_link('politics', 'Politics', true)); ?>" class="submenu-toggle">
                                Politics <i class="fas fa-caret-down"></i>
                            </a>
                            <ul class="submenu">
                                <li><a href="<?php echo ga_e(ga_nav_category_link('andhra-news', 'Andhra News')); ?>">Andhra</a></li>
                                <li><a href="<?php echo ga_e(ga_nav_category_link('telangana-news', 'Telangana News')); ?>">Telangana</a></li>
                                <li><a href="<?php echo ga_e(ga_nav_category_link('india-news', 'India News')); ?>">India</a></li>
                            </ul>
                        </li>
                        <li class="has-submenu">
                            <a href="<?php echo ga_e(ga_nav_category_link('movies', 'Movies', true)); ?>" class="submenu-toggle">
                                Movies <i class="fas fa-caret-down"></i>
                            </a>
                            <ul class="submenu">
                                <li><a href="<?php echo ga_e(ga_nav_category_link('movie-news', 'Movie News')); ?>">News</a></li>
                                <li><a href="<?php echo ga_e(ga_nav_category_link('movie-gossip', 'Movie Gossip')); ?>">Gossip</a></li>
                                <li><a href="box-office">Box Office</a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="<?php echo ga_e(ga_nav_category_link('reviews', 'Reviews')); ?>">Reviews</a>
                        </li>
                        <li>
                            <a href="https://gallery.greatandhra.com/index.php">Gallery</a>
                        </li>
                        <li>
                            <a href="<?php echo ga_e(ga_nav_category_link('opinion', 'Opinion')); ?>">Opinion</a>
                        </li>
                        <li>
                            <a href="http://epaper.greatandhra.com/">
                                <img alt="greatandhra print" src="images/ga-print.png"
                                    style="height: 20px; vertical-align: middle;">
                            </a>
                        </li>
                        <li>
                            <a href="https://telugu.greatandhra.com/" style="font-size: 16px;">తెలుగు</a>
                        </li>
                    </ul>

                    <div class="mobile-social">
                        <a href="https://www.facebook.com/greatandhra" target="_blank">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/greatandhranews" target="_blank">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://www.youtube.com/channel/UCoarMz-cpxAnBy8tszp35wA" target="_blank">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="shortcut-menu-links">
                <li><a href="https://m.greatandhra.com/index.php" title="home"> <img width="20" height="20"
                            src="images/home-icon.png" alt="home icon" title="home icon"></a></li>
                <li><a href="http://telugu.greatandhra.com/">తెలుగు</a></li>
                <li><a href="https://m.greatandhra.com/category.php?id=4" title="Reviews">Reviews</a></li>
                <li><a href="http://epaper.greatandhra.com/" title="epaper">ePaper</a></li>
                <li><a href="http://gallery.greatandhra.com/index.php" title="gallery">Gallery</a></li>
            </div>

            <!-- Overlay -->
            <div class="mobile-overlay" id="mobileOverlay"></div>

            <!-- Second Row: Advertisement -->
            <div class="_201223_">
                <?php // Reuses the Homepage Top Banner ad's mobile image - same pattern as index.php/inner-page.php. ?>
                <?php ga_render_ad('LISTPAGE_MOBILE_BANNER'); ?>
            </div>
        </div>
            <!--great_andhra_main_menu_panel-->
            <!--great_andhra_main_menu_white_gap-->
            <!--<div class="great_andhra_main_menu_white_gap">-->
            <!-- &nbsp; -->
            <!--</div>-->
            <!--great_andhra_main_menu_white_gap-->
            <!--great_andhra_main_body_container-->

            <div class="great_andhra_main_body_container">
                <!--two_column-->
                <div class="movies_column float-left">


                    <!--page_news-->
                    <div class="movies_page_news">
                        <ul class="un-sortable-list ui-sortable">
                            <li class="un-sortable-item sortable-item_right_top_panel">
                                <div class="sortable-item_style_8_mov">
                                    <div class="header">
                                        <h1><?php echo ga_e($ga_page_heading); ?></h1>
                                    </div>

                                    <div class="content">
                                        <?php if (!empty($ga_list_articles)): ?>
                                        <?php
                                            // Phone-only ad dropped at the true middle of however many articles
                                            // this page actually has (varies with pagination) - never fires on
                                            // desktop, and simply doesn't fire on a near-empty last page (index
                                            // never matched) rather than needing a special-cased minimum.
                                            $ga_list_mobile_ad_after_index = (int) floor(count($ga_list_articles) / 2) - 1;
                                        ?>
                                        <?php foreach ($ga_list_articles as $ga_list_i => $ga_article): ?>
                                        <?php
                                            $ga_list_img = ga_image($ga_article, GA_LIST_PAGE_FALLBACK_IMAGE);
                                            $ga_list_date = ga_format_date($ga_article['publishedAt'] ?? null, 'd-M-Y H:i:s');
                                        ?>
                                        <div class="movies_news_description_container float-left" style="color:#000; ">
                                            <div class="img_plc">
                                                <div>
                                                    <img border="0" src="<?php echo ga_e($ga_list_img['src']); ?>"
                                                        align="absmiddle" width="<?php echo (int) $ga_list_img['width']; ?>"
                                                        height="<?php echo (int) $ga_list_img['height']; ?>"
                                                        alt="<?php echo ga_e($ga_article['title'] ?? ''); ?>"
                                                        <?php if ($ga_list_i > 0): ?>loading="lazy"<?php endif; ?>>
                                                </div>
                                            </div>
                                            <div>
                                                <a style="color:#326891; "
                                                    href="<?php echo ga_e(ga_inner_link($ga_article)); ?>"
                                                    title="<?php echo ga_e($ga_article['title'] ?? ''); ?>"><?php echo ga_e($ga_article['title'] ?? ''); ?></a>
                                                <div class="byline " style="padding:0px; ">Published Date : <?php echo ga_e($ga_list_date); ?> IST</div>
                                            </div>
                                            <div class="view_mov_poli_content">
                                                <?php echo ga_e(ga_article_excerpt($ga_article, 220)); ?>
                                            </div>
                                        </div>
                                        <?php if ($ga_list_i === $ga_list_mobile_ad_after_index && ga_is_mobile()): ?>
                                        <div class="listpage-mobile-middle-ad">
                                            <?php ga_render_ad('LISTPAGE_MOBILE_MIDDLE_AD'); ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                        <?php else: ?>
                                        <div class="ga-unavailable" style="min-height:150px;">
                                            <p class="ga-unavailable-msg">No articles found in this category yet.</p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <!--page_news-->
                    <?php if ($ga_total_pages > 1): ?>
                    <?php
                        $ga_window_start = max(1, $ga_page - GA_LIST_PAGINATION_WINDOW);
                        $ga_window_end = min($ga_total_pages, $ga_page + GA_LIST_PAGINATION_WINDOW);
                    ?>
                    <div class="new_pagination" style="margin-left:0px;margin-top:10px; width:650px; ">
                        <table width="100%" align="center">
                            <tbody>
                                <tr>
                                    <td align="center">
                                        <?php if ($ga_page > 1): ?>
                                        <a href="<?php echo ga_e(ga_list_page_url($ga_clean_path, $ga_base_params,$ga_page - 1)); ?>">&laquo; Prev</a>
                                        <?php endif; ?>

                                        <?php if ($ga_window_start > 1): ?>
                                        <a href="<?php echo ga_e(ga_list_page_url($ga_clean_path, $ga_base_params,1)); ?>">1</a>
                                        <?php if ($ga_window_start > 2): ?><span>&hellip;</span><?php endif; ?>
                                        <?php endif; ?>

                                        <?php for ($ga_p = $ga_window_start; $ga_p <= $ga_window_end; $ga_p++): ?>
                                        <?php if ($ga_p === $ga_page): ?>
                                        <span><?php echo $ga_p; ?></span>
                                        <?php else: ?>
                                        <a href="<?php echo ga_e(ga_list_page_url($ga_clean_path, $ga_base_params,$ga_p)); ?>"><?php echo $ga_p; ?></a>
                                        <?php endif; ?>
                                        <?php endfor; ?>

                                        <?php if ($ga_window_end < $ga_total_pages): ?>
                                        <?php if ($ga_window_end < $ga_total_pages - 1): ?><span>&hellip;</span><?php endif; ?>
                                        <a href="<?php echo ga_e(ga_list_page_url($ga_clean_path, $ga_base_params,$ga_total_pages)); ?>"><?php echo $ga_total_pages; ?></a>
                                        <?php endif; ?>

                                        <?php if ($ga_page < $ga_total_pages): ?>
                                        <a href="<?php echo ga_e(ga_list_page_url($ga_clean_path, $ga_base_params,$ga_page + 1)); ?>">Next &raquo;</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                    </div>
                    <?php endif; ?>


                </div>
                <!--two_column-->
                <div class="movies_column_middle">
                    <ul class="un-sortable-list ui-sortable">
                        <li class="un-sortable-item sortable-item_right_top_panel">
                            <div class="sortable-item_style_8_movies">
                                <div class="header"> Gossip</div>
                                <div class="content">
                                    <ul class="news_style">
                                        <?php if (!empty($ga_sidebar_gossip)): ?>
                                        <?php foreach ($ga_sidebar_gossip as $ga_gossip_article): ?>
                                        <li><a href="<?php echo ga_e(ga_inner_link($ga_gossip_article)); ?>"
                                                title="<?php echo ga_e($ga_gossip_article['title'] ?? ''); ?>"><?php echo ga_e($ga_gossip_article['title'] ?? ''); ?></a>
                                        </li>
                                        <?php endforeach; ?>
                                        <?php else: ?>
                                        <li class="ga-unavailable"><p class="ga-unavailable-msg">Content temporarily unavailable</p></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </li>



                        <li class="sortable-item">
                            <?php // New admin-manageable zone, no content configured yet - renders nothing until an ad is added. ?>
                            <?php ga_render_ad('LISTPAGE_CONTENT_AD'); ?>
                        </li>

                        <li class="sortable-item">
                            <p style="float: left;font-size: 7px;"> ADVERTISEMENT</p>
                            <script async="" src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
                            <!-- Review ad -->
                            <ins class="adsbygoogle" style="display:inline-block;width:300px;height:600px"
                                data-ad-client="ca-pub-1239645388568087" data-ad-slot="3746403796"></ins>
                            <script>
                                (adsbygoogle = window.adsbygoogle || []).push({});
                            </script>
                        </li>



                        <li class="un-sortable-item sortable-item_right_top_panel">
                            <div class="sortable-item_style_8_movies">
                                <div class="header"> Reviews</div>
                                <div class="content">
                                    <ul class="news_style">
                                        <?php if (!empty($ga_sidebar_reviews)): ?>
                                        <?php foreach ($ga_sidebar_reviews as $ga_review_article): ?>
                                        <li><a href="<?php echo ga_e(ga_inner_link($ga_review_article)); ?>"
                                                title="<?php echo ga_e($ga_review_article['title'] ?? ''); ?>"><?php echo ga_e($ga_review_article['title'] ?? ''); ?></a>
                                        </li>
                                        <?php endforeach; ?>
                                        <?php else: ?>
                                        <li class="ga-unavailable"><p class="ga-unavailable-msg">Content temporarily unavailable</p></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>

            </div>
            <!--great_andhra_main_body_container-->



            <!--great_andhra_main_footer-->
            <!-- <div class="great_andhra_main_footer">
				
				<div class="copyright_bar_index">
					<footer id="gan-footer" class="section gan-footer">
						<div class="gangrid-main gan-footer-first" id="gan-footer-first">
							<section class="block" style="width:96%;">
								<ul class="menuf" style="text-align: center;">
									<li><a target="_blank" href="https://www.greatandhra.com/aboutus.php"
											title="Visit About us">About Us</a></li>
									<li><a target="_blank" href="https://www.greatandhra.com/disclaimer.php"
											title="Visit Disclaimer Page">Disclaimer</a></li>
									<li><a target="_blank" href="https://www.greatandhra.com/contactus.php"
											title="Visit Contact Us">Contact Us</a></li>
									<li><a target="_blank" href="https://www.greatandhra.com/convergence/index.php"
											title="Visit Advertise with us page">Advertise With Us</a></li>
									<li><a target="_blank" href="https://www.greatandhra.com/privacy.php"
											title="Visit privacy policy page">Privacy Policy</a></li>
									<li><a target="_blank" href="https://www.greatandhra.com/grievance.php"
											title="Visit grievance page">Grievance</a></li>
									<li><a target="_blank" href="https://epaper.greatandhra.com/"
											title="ePaper page">ePaper</a></li>
								</ul>
							</section>
						</div>
						<div class="gangrid-main gan-footer-first" id="gan-footer-first">
							<a target="_blank" href="https://www.facebook.com/greatandhra" title="facebook"><i
									class="fab fa-facebook-f"></i></a>
							<a target="_blank" href="https://twitter.com/greatandhranews" title="twitter"><i
									class="fab fa-twitter"></i></a>
							<a target="_blank" href="https://www.youtube.com/channel/UCoarMz-cpxAnBy8tszp35wA"
								title="youtube"><i class="fab fa-youtube"></i></a>
						</div>
						<div class="gangrid-main gan-footer-third" id="gan-footer-third">
							<div class="block"> ©
								2026 greatandhra | All rights reserved </div>
						</div>
					</footer>
				</div>
			</div> -->

            <div class="new_great_andhra_main_footer">
                <div class="footer-container">

                    <!-- Navigation Links: Lato 13px White -->
                    <nav>
                        <ul class="footer-nav-links">
                            <li><a href="https://www.greatandhra.com/aboutus.php" target="_blank">About Us</a></li>
                            <li><a href="https://www.greatandhra.com/disclaimer.php" target="_blank">Disclaimer</a></li>
                            <li><a href="https://www.greatandhra.com/contactus.php" target="_blank">Contact Us</a></li>
                            <li><a href="https://www.greatandhra.com/convergence/index.php" target="_blank">Advertise
                                    With
                                    Us</a></li>
                            <li><a href="https://www.greatandhra.com/privacy.php" target="_blank">Privacy Policy</a>
                            </li>
                            <li><a href="https://www.greatandhra.com/grievance.php" target="_blank">Grievance</a></li>
                            <li><a href="https://epaper.greatandhra.com/" target="_blank">ePaper</a></li>
                        </ul>
                    </nav>

                    <!-- Social Icons: White 24px -->
                    <div class="footer-social-bar">
                        <a href="https://www.facebook.com/greatandhra" target="_blank" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/greatandhranews" target="_blank" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://www.youtube.com/channel/UCoarMz-cpxAnBy8tszp35wA" target="_blank"
                            title="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>

                    <!-- Copyright Information: Custom Styled Last Line -->
                    <div class="footer-copyright">
                        &copy; 2026 GreatAndhra | All Rights Reserved
                    </div>

                </div>
            </div>

            <!-- White part at the very end -->
            <div class="footer-bottom-spacer"></div>

            <style>
                /* .gan-footer .gan-footer-first {
					height: 20px;
					text-align: center;
				}

				.gan-footer .gan-footer-first .block ul li {
					display: inline-block;
				}

				.menuf li a {
					font-family: Lato;
				}

				.menuf li a:hover {
					color: #fff !important;
				}

				.gan-footer {
					background: #000000;
					height: 175px;
				}

				.gan-footer .gan-footer-first i.fab {
					color: #ffffff !important;
					padding: 5px;
					font-size: 24px;
				} */
            </style> <!--great_andhra_main_footer-->

        </div>
        <!--great_andhra_inner_body-->
        <script>var VUUKLE_CONFIG = { apiKey: '2b166297-6273-48a9-82e9-696327c67418', articleId: '1', comments: { enabled: false }, emotes: { "enabled": false }, powerbar: { "enabled": false }, ads: { noDefaults: true } }; (function () { var d = document, s = d.createElement('script'); s.async = true; s.src = 'https://cdn.vuukle.com/platform.js'; (d.head || d.body).appendChild(s); })();</script>
    </div>
    <!--great_andhra_body-->

    <!-- <script type="text/javascript" src="https://www.greatandhra.com/js/great_andhra_framework.js"> </script>
    <script type="text/javascript" src="https://www.greatandhra.com/js/great_andhra_img_preview.js"> </script>
    <script type="text/javascript" src="https://www.greatandhra.com/js/jquery-ui-1.8.custom.min.js"> </script>
    <script type="text/javascript" src="https://www.greatandhra.com/js/jquery.cookie.js"> </script>
    <script type="text/javascript" src="https://www.greatandhra.com/js/jquery.easing.1.3.js"> </script>
    <script type="text/javascript" src="https://www.greatandhra.com/js/jquery.hoverIntent.js"> </script>
    <script type="text/javascript" src="https://www.greatandhra.com/js/jquery.scrollTo-min.js"> </script>
    <script type="text/javascript" src="https://www.greatandhra.com/js/jquery.sumOuterWidth.js"> </script>
    <script type="text/javascript" src="https://www.greatandhra.com/js/jquery.marquee.js"> </script>
    <script type="text/javascript" src="https://www.greatandhra.com/js/jquery.anythingslider.js"> </script>
    <script type="text/javascript" src="https://www.greatandhra.com/js/great_andhra_view_js.js"> </script> -->


    <style>
        @media only screen and (max-width: 997px) {
            .vuukle-sticky-ad[data-ad-id="vuukle-ad-25"] {
                display: none !important;
            }
        }
    </style>
    <div class="vuukle-sticky-ad" data-ad-id="vuukle-ad-25"
        style="position: fixed; z-index: 2147483647; width: 100%; top: 0px;">
        <div id="vuukle-ad-25"
            style="min-width: 728px; height: 18px; position: fixed; z-index: 2147483647; bottom: 0px; text-align: center; left: 50%; transform: translate(-50%); width: 728px; max-height: 100px;">
            <div class="vuukle-sticky-ad-close"
                style="display: flex; width: 100%; text-align: center; position: relative; justify-content: flex-end; top: 6px;">
                <span title="Close"
                    style="cursor: pointer; width: 18px; height: 18px; background-position: center center; background-repeat: no-repeat; border-radius: 50%; background-size: 60%; background-image: url(&quot;data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20xmlns%3Axlink%3D%22http%3A%2F%2Fwww.w3.org%2F1999%2Fxlink%22%20id%3D%22close-Layer_1%22%20xml%3Aspace%3D%22preserve%22%20height%3D%2223%22%20viewBox%3D%220%200%2022.677%2022.677%22%20width%3D%2223%22%20version%3D%221.1%22%20y%3D%220px%22%20x%3D%220px%22%20enable-background%3D%22new%200%200%2022.677%2022.677%22%3E%09%09%3Cpolygon%20fill%3D%22white%22%20points%3D%2219.346%205.421%2017.256%203.332%2011.338%209.25%205.42%203.332%203.332%205.421%209.25%2011.338%203.332%2017.257%205.42%2019.345%2011.338%2013.427%2017.256%2019.345%2019.346%2017.257%2013.428%2011.338%22%20clip-rule%3D%22evenodd%22%20fill-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E&quot;); background-color: rgba(0, 0, 0, 0.5); visibility: hidden;"></span>
            </div>
            <div class="vuukle-ads"
                style="display: block !important;height: auto;margin: 0px auto;text-align: center; clear: both; overflow: hidden;">
                <div class="vuukle-ad-label"
                    style="display: flex; justify-content: space-evenly; flex-basis: 100%; margin: 0px auto 5px; width: 80px; height: 11px; padding: 0px; line-height: 1.1 !important;">
                    <span style="display: none;"><a aria-label="Vuukle" href="https://vuukle.com" target="_blank"
                            rel="noopener nofollow" style="background-color: transparent; box-shadow: none;">
                            <svg width="11px" viewBox="0 0 30 30" version="1.1" xmlns="http://www.w3.org/2000/svg"
                                xmlns:xlink="http://www.w3.org/1999/xlink">
                                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                    <g transform="translate(-150.000000, -31.000000)" fill-rule="nonzero">
                                        <g transform="translate(150.000000, 31.000000)">
                                            <path
                                                d="M41.8097027,29.0691892 L42.3657568,29.0691892 L54.204,1.80093081 L48.8013243,1.80093081 L44.2726216,12.2153514 C43.2397297,14.5605405 42.2069189,18.3368108 42.2069189,18.3368108 C42.2069189,18.3368108 41.1342973,14.5208108 40.1014865,12.2153514 L35.4535946,1.80093081 L29.4948649,1.80093081 L41.8097027,29.0691892 Z M59.7741892,29.1883784 C62.118,29.1883784 64.0645946,28.0754595 65.0974054,26.0481892 L64.9782162,28.8307297 L70.3412432,28.8307297 L70.3412432,11.8972703 L64.9782162,11.8972703 L64.9782162,20.9602703 C64.9782162,23.1067297 63.7864865,24.339 61.6413243,24.339 C59.7345405,24.339 58.8207568,23.226 58.8207568,21.318 L58.8207568,11.8972703 L53.4578919,11.8972703 L53.4578919,21.6757297 C53.4578919,26.4854595 56.0797297,29.1883784 59.7741892,29.1883784 Z M80.7617838,29.1883784 C83.1056757,29.1883784 85.0524324,28.0754595 86.0845946,26.0481892 L85.9662162,28.8307297 L91.3289189,28.8307297 L91.3289189,11.8972703 L85.9662162,11.8972703 L85.9662162,20.9602703 C85.9662162,23.1067297 84.7743243,24.339 82.6289189,24.339 C80.7218919,24.339 79.8081892,23.226 79.8081892,21.318 L79.8081892,11.8972703 L74.4458919,11.8972703 L74.4458919,21.6757297 C74.4458919,26.4854595 77.0671622,29.1883784 80.7617838,29.1883784 Z M100.915946,23.9414595 L102.345405,22.431 L106.556757,28.8307297 L112.793514,28.8307297 L106.158649,18.8138108 L112.157838,11.8972703 L106.198378,11.8972703 L100.637838,18.4162703 C100.796757,17.343 100.915946,16.0312703 100.915946,14.7592703 L100.915946,0.131416216 L95.5524324,0.131416216 L95.5524324,28.8307297 L100.915946,28.8307297 L100.915946,23.9414595 Z M115.301351,28.8307297 L120.624324,28.8307297 L120.624324,0.131416216 L115.301351,0.131416216 L115.301351,28.8307297 Z M129.844865,21.9937297 L142.675946,21.9937297 C143.073243,16.071 139.776486,11.5395405 133.697838,11.5395405 C128.454324,11.5395405 124.362973,15.1965405 124.362973,20.3242703 C124.362973,25.5314595 128.256486,29.1883784 134.293784,29.1883784 C137.82973,29.1883784 139.894865,28.1151892 141.524595,26.6841892 L138.465405,23.3849189 C137.631081,23.9811892 136.161081,24.7364595 134.214324,24.7364595 C131.790811,24.7364595 130.361351,23.7427297 129.844865,21.9937297 Z M129.765405,18.8931892 C130.202432,16.866 131.631892,15.9120811 133.738378,15.9120811 C135.843243,15.9120811 137.035135,17.0250811 137.272703,18.8931892 L129.765405,18.8931892 Z"
                                                id="Shape" fill="#FACC2B"></path>
                                            <path
                                                d="M12.4448919,5.99524054 C5.66632703,5.99524054 0.171030811,10.9249459 0.171030811,17.0061892 C0.171030811,19.2473514 0.919248649,21.3307297 2.20112432,23.0697568 C2.03273514,25.079027 1.54816216,27.9091622 0.171030811,29.2876216 C0.171030811,29.2876216 4.37897838,28.6966216 7.22951351,26.9742973 C8.81278378,27.6419189 10.5797838,28.0171622 12.4448919,28.0171622 C19.2235135,28.0171622 24.7187838,23.0874324 24.7187838,17.0061892 C24.7187838,10.9249459 19.2235135,5.99524054 12.4448919,5.99524054 Z"
                                                id="Path" fill="#FACC2B"></path>
                                            <path
                                                d="M12.4448919,5.99524054 C5.66632703,5.99524054 0.171030811,10.9249459 0.171030811,17.0061892 C0.171030811,19.2473514 0.919248649,21.3307297 2.20112432,23.0697568 C2.03273514,25.079027 1.54816216,27.9091622 0.171030811,29.2876216 C0.171030811,29.2876216 4.37897838,28.6966216 7.22951351,26.9742973 C8.81278378,27.6419189 10.5797838,28.0171622 12.4448919,28.0171622 C19.2235135,28.0171622 24.7187838,23.0874324 24.7187838,17.0061892 C24.7187838,10.9249459 19.2235135,5.99524054 12.4448919,5.99524054 Z"
                                                id="Path" fill="#4885ED"></path>
                                            <path
                                                d="M12.4421351,24.8694324 L12.7312703,24.8694324 L18.8872703,10.6898108 L16.0778919,10.6898108 L13.7228919,16.1052973 C13.1858108,17.3249189 12.6487297,19.2884595 12.6487297,19.2884595 C12.6487297,19.2884595 12.0908919,17.3041622 11.5538108,16.1052973 L9.13686486,10.6898108 L6.03832703,10.6898108 L12.4421351,24.8694324 Z"
                                                id="Path" fill="#FFFFFF"></path>
                                            <path
                                                d="M28.6454595,12.7881892 C28.6454595,6.70699459 23.1501892,1.77718703 16.3715676,1.77718703 C11.4767027,1.77718703 7.25202973,4.34843514 5.28135405,8.06723514 C7.29767838,6.76494324 9.7697027,5.99514324 12.4439189,5.99514324 C19.2225405,5.99514324 24.7178108,10.9248649 24.7178108,17.0061892 C24.7178108,19.3322432 23.9117027,21.4883514 22.5394054,23.2662162 C25.2882973,24.594973 28.6454595,25.0696216 28.6454595,25.0696216 C27.2682973,23.6915676 26.7837568,20.8614324 26.6153514,18.8517568 C27.8972432,17.1127297 28.6454595,15.0293514 28.6454595,12.7881892 Z"
                                                id="Path" fill="#FACC2B"></path>
                                        </g>
                                    </g>
                                </g>
                            </svg>
                        </a></span><span
                        style="display: block; color: rgb(108, 117, 125); margin: 0px; text-align: center; flex: 1 1 0%; padding: 0px; font-size: 10px !important; line-height: 1 !important;">Advertisement</span>
                </div>
                <div id="div-gpt-ad-1497448474263-25" data-google-query-id="CImV34TN4JMDFT6SrAIdl3ArTw">
                    <div id="google_ads_iframe_/213794966,123116330/vuukle-widget/greatandhra.com-25_0__container__"
                        style="border: 0pt none; margin: auto; text-align: center; width: 468px; height: 0px;"></div>
                </div>
            </div>
            <div class="vuukle-sticky-ad-label"
                style="display: flex; width: 100%; text-align: center; position: relative; justify-content: center;">
            </div>
        </div>
    </div>
    <script src="https://player.vuukle.com/script/6.1/player.js" async=""></script>
    <?php // Positions .source-image-left/.source-image-right (the fixed skyscraper ad panels
          // above) relative to the page's centered 990px content column - same script the
          // homepage uses. Without it these panels have no left/right offset at all and never
          // "stick" against the content edges the way they do on index.php. ?>
    <script src="js/great_andhra_view_js_160_1.js?v=<?php echo ga_asset_version('js/great_andhra_view_js_160_1.js'); ?>" type="text/javascript"></script>
</body>
</html>
