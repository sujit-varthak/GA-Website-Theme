<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/helpers.php';
// Roadblock (full-page interstitial before render) only fires on the homepage now - was
// showing on every page type, which the user found intrusive on list/box-office/article pages.
require_once __DIR__ . '/inc/api-client.php';

// Safe to cache at Cloudflare's edge as of the client-side ad/interstitial rework (see
// ga_render_ad()/ga_render_interstitial_overlay() in inc/helpers.php) - this page's render is
// now identical for every visitor requesting the same URL (page number included, since that's
// part of the querystring/cache key): no ad content or interstitial-eligibility decision is
// baked in here anymore, both are resolved client-side per visitor after this cached HTML
// loads.
header('Cache-Control: public, max-age=60, s-maxage=60');

$ga_bo_page = max(1, isset($_GET['page']) ? (int) $_GET['page'] : 1);
$ga_bo_skip = ($ga_bo_page - 1) * GA_BOX_OFFICE_TAKE;

// Fires the box-office listing feed + sidebar reviews feed + the 3 small Movie Rankings
// endpoints concurrently, instead of the ~6 sequential blocking calls this page used to make
// one at a time. adZones is now just the two zones something on this page still reads
// server-side (FULLSCREEN_INTERSTITIAL_AD via ga_prepare_interstitial_config() below,
// BOTTOM_STICKY_AD via ga_render_bottom_sticky_ad()'s existence check) - every other zone's ad
// content now resolves client-side (see ga_render_ad() in inc/helpers.php), so prefetching them
// here would just warm a cache entry nothing reads.
ga_prefetch_page([
    'movieRankings' => true,
    'articles' => [
        [GA_BOX_OFFICE_TAKE, $ga_bo_skip, GA_NAV_CATEGORY_IDS['movies'], true],
        [GA_LIST_SIDEBAR_COUNT, 0, GA_NAV_CATEGORY_IDS['reviews']],
    ],
    'adZones' => [
        'FULLSCREEN_INTERSTITIAL_AD',
        'BOTTOM_STICKY_AD',
    ],
]);

// Reads the FULLSCREEN_INTERSTITIAL_AD zone from the cache the batch above just warmed,
// instead of its own separate blocking request (previously called before ga_prefetch_page(),
// adding a full sequential network round trip to every page load).
$ga_interstitial_config = ga_prepare_interstitial_config();

$ga_bo_result = ga_fetch_articles(GA_BOX_OFFICE_TAKE, $ga_bo_skip, GA_NAV_CATEGORY_IDS['movies'], true);
$ga_bo_articles = $ga_bo_result['items'] ?? [];
$ga_bo_total = $ga_bo_result['total'] ?? 0;
$ga_bo_total_pages = $ga_bo_total > 0 ? (int) ceil($ga_bo_total / GA_BOX_OFFICE_TAKE) : 1;

// Sidebar "Reviews" widget — Movies > Reviews subcategory, same small fixed list as list-page.php's.
$ga_bo_sidebar_reviews = ga_fetch_articles(GA_LIST_SIDEBAR_COUNT, 0, GA_NAV_CATEGORY_IDS['reviews'])['items'] ?? [];

// Movie Rankings (confirmed live 2026-08-02) — admin-curated link lists, not articles, same
// shape as usaMovieSchedule (title/movieName + linkUrl + openInNewTab), capped at 5
// server-side. Each is its own small dedicated endpoint rather than reading it off the full
// homepage aggregate (load-audit finding #3/#7 — this used to pull all ~16 homepage sections,
// including all 10 full article-list sections, just to read these 3 small arrays).
$ga_weekly_top_five = ga_fetch_weekly_top_five() ?? [];
$ga_all_time_top_films = ga_fetch_movie_box_office('ALL_TIME') ?? [];
$ga_usa_box_office = ga_fetch_movie_box_office('USA_BOX_OFFICE') ?? [];

function ga_box_office_url(int $page): string
{
    return $page > 1 ? 'box-office?page=' . $page : 'box-office';
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="canonical" href="https://www.greatandhra.com/boxoffice">
    <title>Great Andhra - Boxoffice</title>
    <meta name="robots" content="index, follow">

    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <link href="assets/css2" rel="stylesheet">
    <link href="css/footer.css?v=<?php echo ga_asset_version('css/footer.css'); ?>" rel="stylesheet" type="text/css">
    <link href="css/main-box-office.css?v=<?php echo ga_asset_version('css/main-box-office.css'); ?>" rel="stylesheet" type="text/css">
    <link href="css/box-office-mobile-responsive.css?v=<?php echo ga_asset_version('css/box-office-mobile-responsive.css'); ?>" rel="stylesheet">
    <link href="css/site-ads.css?v=<?php echo ga_asset_version('css/site-ads.css'); ?>" rel="stylesheet">
    <link href="css/header-mob.css?v=<?php echo ga_asset_version('css/header-mob.css'); ?>" rel="stylesheet">
    <script src="js/drawer.js?v=<?php echo ga_asset_version('js/drawer.js'); ?>"></script>
    <?php // defer, not the bottom-of-body placement this used to have - starts downloading
          // immediately, in parallel with the rest of this page's HTML, instead of only once
          // the parser reaches the very end of it. Execution still waits for the full DOM
          // (defer's own guarantee), so every .ga-ad-slot below is already there when it runs -
          // same behavior, just no longer paying for the download as a trailing round trip
          // after everything else has already rendered. ?>
    <script src="js/ga-ad-loader.js?v=<?php echo ga_asset_version('js/ga-ad-loader.js'); ?>" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Start Alexa Certify Javascript -->
    <script type="text/javascript">
        _atrk_opts = { atrk_acct: "TbYbo1IWNa10Y8", domain: "greatandhra.com", dynamic: true };
        (function () { var as = document.createElement('script'); as.type = 'text/javascript'; as.async = true; as.src = "https://d31qbv1cthcecs.cloudfront.net/atrk.js"; var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(as, s); })();
    </script>
    <noscript>&lt;img src="https://d5nxst8fruw4z.cloudfront.net/atrk.gif?account=TbYbo1IWNa10Y8" style="display:none"
        height="1" width="1" alt="" /&gt;</noscript>
    <!-- End Alexa Certify Javascript -->

    <style id="vuukle-ad-25-styles">
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
        src="https://fundingchoicesmessages.google.com/f/AGSKWxXNZ8et6BWzXJdgi4VpoL3qTC2I533AF5x1xNVCR9Nkt8mB1xusXMIBYViCl0XiD9mQAyxYrmlXJKLf8I3X4umIGCdwoN7_4HtaY_V3U1x9pmaxe5mjvQM0taC5GCRTkDnN5Bq8pQ==?fccs=W1siQUtzUm9sLWNvR1VIck5nU29UU19sQThjNGg5aXR4ZFFRUTdHNVFJMkUxRkI2eE12bF8zaGo1allrQmhSRTlyTHA0SU1MN3JMSGVoRUxraGN3ck1fSHV3bXVJU1ZYX1pKQkF1UjFXQnVsUVotelF0NVpZbU5tdTZzRy1ybWxJMlRQQjZDbS1GQmN1RVhtWEpMQkVub2Q0VVZmdDdUNmtVOEpRPT0iXSxudWxsLG51bGwsbnVsbCxudWxsLG51bGwsWzE3NzYwNjE2NDYsODU4MDAwMDAwXSxudWxsLG51bGwsbnVsbCxbbnVsbCxbN11dLCJodHRwczovL3d3dy5ncmVhdGFuZGhyYS5jb20vYm94b2ZmaWNlIixudWxsLFtbOCwiWHo0VGUzVHFmVDgiXSxbOSwiZW4tVVMiXSxbMTgsIltbW251bGwsMjY0MV1dXSJdLFsyMywiMTc3MzM5NjU0NCJdLFszNSwiMTc3MzM5NjU0NyJdLFsxOSwiMiJdLFsyNCwid3d3LmdyZWF0YW5kaHJhLmNvbSJdLFsyOSwiZmFsc2UiXV1d"></script>
    <link type="text/css" href="//fonts.googleapis.com/css?family=Google%20Sans%3A400" rel="stylesheet">
    <link type="text/css" href="//fonts.googleapis.com/css?family=Google%20Sans%3A700" rel="stylesheet">
    <script async=""
        src="https://fundingchoicesmessages.google.com/f/AGSKWxWMMtnmgoxPQiOHxW13wlPJeFnn-sKEkvKUUVXVwlOD8T3GxO0rMRvrxOChJ0z1Cwi1nprLuvLTyUOkYkBEORRTD-LCUfoPnf75mzaQENN9THCz4FUsxhQq-LdfTWifuYaih3t27g==?fccs=W1siQUtzUm9sLWNvR1VIck5nU29UU19sQThjNGg5aXR4ZFFRUTdHNVFJMkUxRkI2eE12bF8zaGo1allrQmhSRTlyTHA0SU1MN3JMSGVoRUxraGN3ck1fSHV3bXVJU1ZYX1pKQkF1UjFXQnVsUVotelF0NVpZbU5tdTZzRy1ybWxJMlRQQjZDbS1GQmN1RVhtWEpMQkVub2Q0VVZmdDdUNmtVOEpRPT0iXSxudWxsLG51bGwsbnVsbCxudWxsLG51bGwsWzE3NzYwNjE2NDcsNjkwMDAwMDAwXSxudWxsLG51bGwsbnVsbCxbbnVsbCxbNyw2XSxudWxsLG51bGwsbnVsbCxudWxsLG51bGwsbnVsbCxudWxsLG51bGwsbnVsbCwxXSwiaHR0cHM6Ly93d3cuZ3JlYXRhbmRocmEuY29tL2JveG9mZmljZSIsbnVsbCxbWzgsIlh6NFRlM1RxZlQ4Il0sWzksImVuLVVTIl0sWzE4LCJbW1tudWxsLDI2NDFdXV0iXSxbMjMsIjE3NzMzOTY1NDQiXSxbMzUsIjE3NzMzOTY1NDciXSxbMTksIjIiXSxbMjQsInd3dy5ncmVhdGFuZGhyYS5jb20iXSxbMjksImZhbHNlIl1dXQ"></script>
    <script async=""
        src="https://fundingchoicesmessages.google.com/f/AGSKWxWiT7uDjilaGvlMs1o94tOedIwJZOHhCR5_WNRw825DDhij9LcRTV1mHyUb0vlXF1slx3Vr8jKtixIuHy_6Sgipy8A4w9W3AymcD3NlelGhBKKliEiWn1anKAGL4YIiRc3dInDZ7A==?fccs=W1siQUtzUm9sLWNvR1VIck5nU29UU19sQThjNGg5aXR4ZFFRUTdHNVFJMkUxRkI2eE12bF8zaGo1allrQmhSRTlyTHA0SU1MN3JMSGVoRUxraGN3ck1fSHV3bXVJU1ZYX1pKQkF1UjFXQnVsUVotelF0NVpZbU5tdTZzRy1ybWxJMlRQQjZDbS1GQmN1RVhtWEpMQkVub2Q0VVZmdDdUNmtVOEpRPT0iXSxudWxsLG51bGwsbnVsbCxudWxsLG51bGwsWzE3NzYwNjE2NDcsOTQ1MDAwMDAwXSxudWxsLG51bGwsbnVsbCxbbnVsbCxbNyw2LDEwXSxudWxsLG51bGwsbnVsbCxudWxsLG51bGwsbnVsbCxudWxsLG51bGwsbnVsbCwxXSwiaHR0cHM6Ly93d3cuZ3JlYXRhbmRocmEuY29tL2JveG9mZmljZSIsbnVsbCxbWzgsIlh6NFRlM1RxZlQ4Il0sWzksImVuLVVTIl0sWzE4LCJbW1tudWxsLDI2NDFdXV0iXSxbMjMsIjE3NzMzOTY1NDQiXSxbMzUsIjE3NzMzOTY1NDciXSxbMTksIjIiXSxbMjQsInd3dy5ncmVhdGFuZGhyYS5jb20iXSxbMjksImZhbHNlIl1dXQ"></script>
    <script async=""
        src="https://fundingchoicesmessages.google.com/f/AGSKWxWrxgkToHDzES_XSljzKEGgPkgAaq73dIVUKhrd3zF_rCZMpkvtmPZX5G6I9GQ2HRDoBVw4nmasycfbseQ39MnYRQzQ3OUdUJX-qxttsx84iAVtYNj9JVeVAF3RGaLJU1ipETmdNg==?fccs=W1siQUtzUm9sLWNvR1VIck5nU29UU19sQThjNGg5aXR4ZFFRUTdHNVFJMkUxRkI2eE12bF8zaGo1allrQmhSRTlyTHA0SU1MN3JMSGVoRUxraGN3ck1fSHV3bXVJU1ZYX1pKQkF1UjFXQnVsUVotelF0NVpZbU5tdTZzRy1ybWxJMlRQQjZDbS1GQmN1RVhtWEpMQkVub2Q0VVZmdDdUNmtVOEpRPT0iXSxudWxsLG51bGwsbnVsbCxudWxsLG51bGwsWzE3NzYwNjE2NDgsMTYwMDAwMDAwXSxudWxsLG51bGwsbnVsbCxbbnVsbCxbNyw2LDEwLDldLG51bGwsMixudWxsLCJlbiIsbnVsbCxudWxsLG51bGwsbnVsbCxudWxsLDFdLCJodHRwczovL3d3dy5ncmVhdGFuZGhyYS5jb20vYm94b2ZmaWNlIixudWxsLFtbOCwiWHo0VGUzVHFmVDgiXSxbOSwiZW4tVVMiXSxbMTgsIltbW251bGwsMjY0MV1dXSJdLFsyMywiMTc3MzM5NjU0NCJdLFszNSwiMTc3MzM5NjU0NyJdLFsxOSwiMiJdLFsyNCwid3d3LmdyZWF0YW5kaHJhLmNvbSJdLFsyOSwiZmFsc2UiXV1d"></script>
</head>

<body class="home_bg">
    <?php ga_render_interstitial_overlay($ga_interstitial_config, 'BOXOFFICE'); ?>
    <?php ga_render_bottom_sticky_ad(); ?>
    <!--great_andhra_body-->
    <div class="great_andhra_movie_body">
        <!--great_andhra_inner_body-->
        <div class="great_andhra_movie_inner_body">
            <!--great_andhra_search_panel-->
            <!--great_andhra_logo_panel-->
            <div class="great_andhra_logo_panel">
                <a href="/" class="logo">
                    <img src="images/great_andhra.gif" title="Greatandhra website logo" alt="Greatandhra logo">
                </a>
                <div class="AdinHedare">
                    <?php // Same ad as the homepage's top banner - not independently manageable. ?>
                    <?php ga_render_ad('BOXOFFICE_TOP_BANNER'); ?>
                </div>
            </div>

            <!--great_andhra_logo_panel-->
            <!--great_andhra_main_menu_panel-->
            <!---Search button-->
            <script type="text/javascript" src="assets/jquery.min.1.8.2.js"></script>
            <script type="text/javascript">
                $(document).ready(function (e) {
                    $('.search_img').click(function () {
                        $('#search_box_new').slideToggle('slow');
                    });
                });
            </script>



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
                            <img alt="greatandhra print" src="images/ga-print.png" class="nav-print-img"
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

                <div class="mobile-actions">
                    <button class="mobile-search-btn" id="mobileSearchBtn" aria-label="Search">
                        <i class="fas fa-search"></i>
                    </button>

                    <button class="hamburger-menu" id="hamburgerBtn" aria-label="Menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>

            <!-- Mobile Search Bar -->
            <div class="mobile-search-bar" id="mobileSearchBar">
                <form class="mobile-search-form" action="/list-page.php" method="get">
                    <input class="mobile-search-input" type="text" name="search" placeholder="Search articles..." required>
                    <button class="mobile-search-submit" type="submit" aria-label="Submit search">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
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
                            <div class="submenu-row">
                                <a href="<?php echo ga_e(ga_nav_category_link('politics', 'Politics', true)); ?>" class="submenu-link">Politics</a>
                                <button type="button" class="submenu-caret" aria-label="Toggle Politics submenu">
                                    <i class="fas fa-caret-down"></i>
                                </button>
                            </div>
                            <ul class="submenu">
                                <li><a href="<?php echo ga_e(ga_nav_category_link('andhra-news', 'Andhra News')); ?>">Andhra</a></li>
                                <li><a href="<?php echo ga_e(ga_nav_category_link('telangana-news', 'Telangana News')); ?>">Telangana</a></li>
                                <li><a href="<?php echo ga_e(ga_nav_category_link('india-news', 'India News')); ?>">India</a></li>
                            </ul>
                        </li>
                        <li class="has-submenu">
                            <div class="submenu-row">
                                <a href="<?php echo ga_e(ga_nav_category_link('movies', 'Movies', true)); ?>" class="submenu-link">Movies</a>
                                <button type="button" class="submenu-caret" aria-label="Toggle Movies submenu">
                                    <i class="fas fa-caret-down"></i>
                                </button>
                            </div>
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
                <?php // Reuses the Homepage Top Banner ad's mobile image - same pattern as index.php/inner-page.php/list-page.php. ?>
                <?php ga_render_ad('BOXOFFICE_MOBILE_BANNER'); ?>
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
                <div class="movies_column">


                    <!--page_news-->
                    <div class="movies_page_news">
                        <ul class="un-sortable-list ui-sortable">
                            <li class="un-sortable-item sortable-item_right_top_panel">
                                <div class="sortable-item_style_8_mov">
                                    <div class="header"> Box Office</div>
                                    <div class="gal_body_box">
                                        <?php if (!empty($ga_bo_articles)): ?>
                                        <?php foreach ($ga_bo_articles as $ga_bo_i => $ga_bo_article): ?>
                                        <?php $ga_bo_img = ga_image($ga_bo_article, GA_BOX_OFFICE_FALLBACK_IMAGE); ?>
                                        <div class="thumb_container_box">
                                            <div class="img_container_box"> <a
                                                    href="<?php echo ga_e(ga_inner_link($ga_bo_article)); ?>">
                                                    <img border="0"
                                                        src="<?php echo ga_e($ga_bo_img['src']); ?>"
                                                        width="<?php echo (int) $ga_bo_img['width']; ?>"
                                                        height="<?php echo (int) $ga_bo_img['height']; ?>"
                                                        alt="<?php echo ga_e($ga_bo_article['title'] ?? ''); ?>"
                                                        <?php if ($ga_bo_i > 0): ?>loading="lazy"<?php endif; ?>> </a>
                                            </div>

                                            <div class="img_text_cont_box">

                                                <a href="<?php echo ga_e(ga_inner_link($ga_bo_article)); ?>"
                                                    class="sublink"> <?php echo ga_e($ga_bo_article['title'] ?? ''); ?>
                                                </a>

                                            </div>

                                        </div>
                                        <?php endforeach; ?>
                                        <?php else: ?>
                                        <div class="ga-unavailable" style="min-height:150px; width:100%;">
                                            <p class="ga-unavailable-msg">No articles found.</p>
                                        </div>
                                        <?php endif; ?>
                                        <br>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <page_news>
                        <?php if ($ga_bo_total_pages > 1): ?>
                        <?php
                            $ga_bo_window_start = max(1, $ga_bo_page - GA_LIST_PAGINATION_WINDOW);
                            $ga_bo_window_end = min($ga_bo_total_pages, $ga_bo_page + GA_LIST_PAGINATION_WINDOW);
                        ?>
                        <div class="new_pagination" style="margin-left:0px;margin-top:10px; width:650px; ">
                            <table width="100%" align="center">
                                <tbody>
                                    <tr>
                                        <td align="center">
                                            <?php if ($ga_bo_page > 1): ?>
                                            <a href="<?php echo ga_e(ga_box_office_url($ga_bo_page - 1)); ?>">&laquo; Prev</a>
                                            <?php endif; ?>

                                            <?php if ($ga_bo_window_start > 1): ?>
                                            <a href="<?php echo ga_e(ga_box_office_url(1)); ?>">1</a>
                                            <?php if ($ga_bo_window_start > 2): ?><span>&hellip;</span><?php endif; ?>
                                            <?php endif; ?>

                                            <?php for ($ga_bo_p = $ga_bo_window_start; $ga_bo_p <= $ga_bo_window_end; $ga_bo_p++): ?>
                                            <?php if ($ga_bo_p === $ga_bo_page): ?>
                                            <span><?php echo $ga_bo_p; ?></span>
                                            <?php else: ?>
                                            <a href="<?php echo ga_e(ga_box_office_url($ga_bo_p)); ?>"><?php echo $ga_bo_p; ?></a>
                                            <?php endif; ?>
                                            <?php endfor; ?>

                                            <?php if ($ga_bo_window_end < $ga_bo_total_pages): ?>
                                            <?php if ($ga_bo_window_end < $ga_bo_total_pages - 1): ?><span>&hellip;</span><?php endif; ?>
                                            <a href="<?php echo ga_e(ga_box_office_url($ga_bo_total_pages)); ?>"><?php echo $ga_bo_total_pages; ?></a>
                                            <?php endif; ?>

                                            <?php if ($ga_bo_page < $ga_bo_total_pages): ?>
                                            <a href="<?php echo ga_e(ga_box_office_url($ga_bo_page + 1)); ?>">Next &raquo;</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                        </div>
                        <?php endif; ?>
                        <br>

                    </page_news>
                </div>


                <!--two_column-->
                <div class="movies_column_middle">
                    <ul class="un-sortable-list ui-sortable">
                        <li class="un-sortable-item sortable-item_right_top_panel">
                            <div class="innerpage_latestnews1">
                                <div class="header">This Week Top Five</div>
                                <div class="content">
                                    <table class="table table-bordered" cellpadding="0" cellspacing="0">


                                        <tbody class="row-hover">
                                            <?php if (!empty($ga_weekly_top_five)): ?>
                                            <?php foreach ($ga_weekly_top_five as $ga_wtf_i => $ga_wtf_item): ?>
                                            <tr class="<?php echo $ga_wtf_i % 2 === 0 ? 'row-2 even' : 'row-3 odd'; ?>">
                                                <td class="column-1"><a
                                                        href="<?php echo ga_e($ga_wtf_item['linkUrl'] ?? ''); ?>"
                                                        <?php echo !empty($ga_wtf_item['openInNewTab']) ? 'target="_blank"' : ''; ?>><?php echo ($ga_wtf_i + 1) . '. ' . ga_e(trim($ga_wtf_item['title'] ?? '')); ?></a></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php else: ?>
                                            <tr>
                                                <td class="column-1 ga-unavailable"><p class="ga-unavailable-msg">Content temporarily unavailable</p></td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>


                                </div>
                            </div>
                        </li>
                        <li class="sortable-item">
                            <div class="boxoffice-sticky-ad">
                                <?php ga_render_ad('BOXOFFICE_STICKY_AD'); ?>
                            </div>
                            <script>
                            (function () {
                                var box = document.currentScript.previousElementSibling;
                                if (!box || !box.classList.contains('boxoffice-sticky-ad')) return;
                                var slot = box.querySelector('.ga-ad-slot');
                                if (!slot) return;

                                function tryApplyBg() {
                                    var img = box.querySelector('img');
                                    if (!img || !img.src) return;
                                    function applyBg() {
                                        box.style.backgroundImage = 'url(' + img.src + ')';
                                    }
                                    if (img.complete) applyBg();
                                    else img.addEventListener('load', applyBg);
                                }

                                // The <img> ga_render_ad() used to render synchronously now arrives
                                // asynchronously, via js/ga-ad-loader.js's fetch - wait for its
                                // 'ga-ad-loaded' event (detail.loaded false means no ad was active
                                // for this zone at all, nothing to apply) instead of assuming the
                                // <img> is already there.
                                slot.addEventListener('ga-ad-loaded', function (e) {
                                    if (e.detail && e.detail.loaded) {
                                        tryApplyBg();
                                    }
                                });
                            })();
                            </script>
                        </li>
                        <li class="un-sortable-item sortable-item_right_top_panel">
                            <div class="innerpage_latestnews1">
                                <div class="header">All Time Top 5 Films</div>
                                <div class="content">
                                    <table class="table table-bordered" cellpadding="0" cellspacing="0">


                                        <tbody class="row-hover">
                                            <?php if (!empty($ga_all_time_top_films)): ?>
                                            <?php foreach ($ga_all_time_top_films as $ga_atf_i => $ga_atf_item): ?>
                                            <tr class="<?php echo $ga_atf_i % 2 === 0 ? 'row-2 even' : 'row-3 odd'; ?>">
                                                <td class="column-1"><a
                                                        href="<?php echo ga_e($ga_atf_item['linkUrl'] ?? ''); ?>"
                                                        <?php echo !empty($ga_atf_item['openInNewTab']) ? 'target="_blank"' : ''; ?>><?php echo ($ga_atf_i + 1) . '. ' . ga_e(trim($ga_atf_item['movieName'] ?? '')); ?></a></td>
                                                <td class="column-2"><?php echo ga_e($ga_atf_item['amount'] ?? ''); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php else: ?>
                                            <tr>
                                                <td class="column-1 ga-unavailable" colspan="2"><p class="ga-unavailable-msg">Content temporarily unavailable</p></td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>


                            </div>
                        </li>
                        <li class="sortable-item">
                            <?php ga_render_ad('BOXOFFICE_REVIEW_AD'); ?>
                        </li>
                        <li class="un-sortable-item sortable-item_right_top_panel">
                            <div class="innerpage_latestnews1">
                                <div class="header">USA Box Office: Top 5 Films</div>
                                <div class="content">
                                    <table class="table table-bordered" cellpadding="0" cellspacing="0">


                                        <tbody class="row-hover">
                                            <?php if (!empty($ga_usa_box_office)): ?>
                                            <?php foreach ($ga_usa_box_office as $ga_usbo_i => $ga_usbo_item): ?>
                                            <tr class="<?php echo $ga_usbo_i % 2 === 0 ? 'row-2 even' : 'row-3 odd'; ?>">
                                                <td class="column-1"><a
                                                        href="<?php echo ga_e($ga_usbo_item['linkUrl'] ?? ''); ?>"
                                                        <?php echo !empty($ga_usbo_item['openInNewTab']) ? 'target="_blank"' : ''; ?>><?php echo ga_e(trim($ga_usbo_item['movieName'] ?? '')); ?></a></td>
                                                <td class="column-2"><?php echo ga_e($ga_usbo_item['amount'] ?? ''); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php else: ?>
                                            <tr>
                                                <td class="column-1 ga-unavailable" colspan="2"><p class="ga-unavailable-msg">Content temporarily unavailable</p></td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>


                            </div>
                        </li>





                        <li class="un-sortable-item sortable-item_right_top_panel">
                            <div class="sortable-item_style_8_movies">
                                <div class="header"> Reviews</div>
                                <div class="content">
                                    <ul class="news_style">
                                        <?php if (!empty($ga_bo_sidebar_reviews)): ?>
                                        <?php foreach ($ga_bo_sidebar_reviews as $ga_bo_review_article): ?>
                                        <li><a href="<?php echo ga_e(ga_inner_link($ga_bo_review_article)); ?>"
                                                title="<?php echo ga_e($ga_bo_review_article['title'] ?? ''); ?>"><?php echo ga_e($ga_bo_review_article['title'] ?? ''); ?></a>
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

            <div class="new_great_andhra_main_footer">
                <div class="footer-container">

                    <!-- Navigation Links: Lato 13px White -->
                    <nav>
                        <ul class="footer-nav-links">
                            <li><a href="https://www.greatandhra.com/about%20us/" target="_blank">About Us</a></li>
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
            <!--great_andhra_main_footer-->

        </div>
        <!--great_andhra_inner_body-->
        <script>var VUUKLE_CONFIG = { apiKey: '2b166297-6273-48a9-82e9-696327c67418', articleId: '1', comments: { enabled: false }, emotes: { "enabled": false }, powerbar: { "enabled": false }, ads: { noDefaults: true } }; (function () { var d = document, s = d.createElement('script'); s.async = true; s.src = 'https://cdn.vuukle.com/platform.js'; (d.head || d.body).appendChild(s); })();</script>
    </div>
    <!--great_andhra_body-->

    <script type="text/javascript" src="assets/great_andhra_framework.js"> </script>
    <script type="text/javascript" src="assets/great_andhra_img_preview.js"> </script>
    <script type="text/javascript" src="assets/jquery-ui-1.8.custom.min.js"> </script>
    <script type="text/javascript" src="assets/jquery.cookie.js"> </script>
    <script type="text/javascript" src="assets/jquery.easing.1.3.js"> </script>
    <script type="text/javascript" src="assets/jquery.hoverIntent.js"> </script>
    <script type="text/javascript" src="assets/jquery.scrollTo-min.js"> </script>
    <script type="text/javascript" src="assets/jquery.sumOuterWidth.js"> </script>
    <script type="text/javascript" src="assets/jquery.marquee.js"> </script>
    <script type="text/javascript" src="assets/jquery.anythingslider.js"> </script>
    <?php // Wrong filename here 404'd sitewide - every other page correctly loads this under
          // js/, with the cache-busting version param ga_asset_version() adds. This is what
          // positions the fixed skyscraper ad panels and injects the ad's close (X) button -
          // Box Office was the one page on the site missing that button because of this. ?>
    <script type="text/javascript" src="js/great_andhra_view_js_160_1.js?v=<?php echo ga_asset_version('js/great_andhra_view_js_160_1.js'); ?>"></script>
    <script type="text/javascript" src="js/ga-interstitial.js?v=<?php echo ga_asset_version('js/ga-interstitial.js'); ?>"></script>


    <style>
        @media only screen and (max-width: 997px) {
            .vuukle-sticky-ad[data-ad-id="vuukle-ad-25"] {
                display: none !important;
            }
        }
    </style>
</body>
</html>
