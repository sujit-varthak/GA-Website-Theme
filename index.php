<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/helpers.php';
ga_maybe_show_roadblock_ad();
require_once __DIR__ . '/inc/api-client.php';

// Big Story hero + the flagged articles below the ad, and the "Top News" tab's trending
// articles — all resolved server-side (filtering, sorting, hero-exclusion) by the same
// /api/public/homepage call, cached once and read for both.
$ga_home_data = ga_fetch_homepage();
$ga_hero_article = $ga_home_data['bigStory']['hero'] ?? null;
$ga_list_articles = $ga_home_data['bigStory']['related'] ?? [];
$ga_trending_articles = $ga_home_data['trending'] ?? [];
$ga_opinion_articles = $ga_home_data['opinion'] ?? [];
$ga_movie_news_articles = $ga_home_data['movieNews'] ?? [];
$ga_movie_gossip_articles = $ga_home_data['movieGossip'] ?? [];
$ga_andhra_news_articles = $ga_home_data['andhraNews'] ?? [];
$ga_telangana_news_articles = $ga_home_data['telanganaNews'] ?? [];
$ga_politics_gossip_articles = $ga_home_data['politicsGossip'] ?? [];
$ga_reviews_articles = $ga_home_data['reviews'] ?? [];
$ga_talk_of_town_articles = $ga_home_data['talkOfTheTown'] ?? [];
$ga_featured_articles = $ga_home_data['featured'] ?? [];
// Not an Article — a standalone curated link list (title + destination URL), admin-managed.
$ga_usa_movie_schedule = $ga_home_data['usaMovieSchedule'] ?? [];
$ga_trending_tags = array_slice($ga_home_data['trendingTags'] ?? [], 0, GA_TRENDING_TAGS_COUNT);

// Editor's Pick — most recent Reviews article with schemaData.movieName + rating populated.
$ga_editors_pick_scan = ga_fetch_articles(GA_EDITORS_PICK_SCAN_COUNT, 0, GA_NAV_CATEGORY_IDS['reviews'])['items'] ?? [];
$ga_editors_pick = ga_pick_editors_review($ga_editors_pick_scan);

// Shared latest-articles batch feeds three sections: "Article" (latest-first, as fetched),
// Most Popular and the "Most Read" tab (both viewCount-sorted, excluding the Big Story hero
// so it never reappears elsewhere on the page).
$ga_latest_feed = ga_fetch_articles(GA_MOST_POPULAR_FEED_SIZE, 0)['items'] ?? null;

$ga_article_section_articles = $ga_latest_feed ? array_slice($ga_latest_feed, 0, 5) : [];

$ga_popular_sorted = [];
if ($ga_latest_feed) {
    $ga_popular_sorted = $ga_latest_feed;
    usort($ga_popular_sorted, function ($a, $b) {
        return (int) ($b['viewCount'] ?? 0) <=> (int) ($a['viewCount'] ?? 0);
    });
    if ($ga_hero_article) {
        $ga_popular_sorted = array_values(array_filter($ga_popular_sorted, function ($a) use ($ga_hero_article) {
            return ($a['id'] ?? null) !== ($ga_hero_article['id'] ?? null);
        }));
    }
}

$ga_popular_articles = array_slice($ga_popular_sorted, 0, 5);
// Most Read was uncapped (up to ~39 items) so it could match Big Story's height, but that
// overflowed way past it instead. Capped at 17 now, same as Top News.
$ga_most_read_articles = array_slice($ga_popular_sorted, 0, GA_TAB_ARTICLE_LIMIT);
// Top News: back to the isTrending flag (via the homepage aggregate's trending key) — the
// "Latest News" category filter was tried and then reverted back to this.
$ga_trending_articles = array_slice($ga_trending_articles, 0, GA_TAB_ARTICLE_LIMIT);

// Mobile-only "Latest News" list (hidden on desktop via CSS) — mirrors the Top News tab's own
// content ($ga_trending_articles, already capped to GA_TAB_ARTICLE_LIMIT above), just capped
// further to GA_MOBILE_LATEST_NEWS_COUNT for this shorter list.
$ga_mobile_latest_news_articles = array_slice($ga_trending_articles, 0, GA_MOBILE_LATEST_NEWS_COUNT);
?>
<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html lang="en">

<head>
    <!--<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" /> -->
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <title>No.1 Telugu news website in the world | Latest Telugu News - Greatandhra</title>
    <meta
        content="Greatandhra - World No 1 leading Telugu Daily News website delivers Andhra Pradesh and Telangana News, Latest Telugu News, Telugu News Paper Online, Astrology, Rasi Palan, Movie news, Political News in Telugu and English, Business News in Telugu and English, Tollywood, Cinema and Sports News in Telugu and English"
        name="description" />
    <meta
        content="Greatandhra, Greatandhra news, Andhra News, Telangana News, Telugu News Paper, Daily Telugu News, Tollywood, Telugu Cinema, Telugu Movie Reviews, Tollywood Movie Reviews, videos, India news, breaking news, today news, current news, news website, politics, world news, business news, bollywood news, cricket news, sports, lifestyle, gadgets, tech news, video news"
        name="keywords" />
    <meta content="greatandhra.com" name="application-name" />
    <meta content="GreatAndhra - The Company" name="msapplication-tooltip" />
    <meta
        content="Greatandhra.com: News,Latest News,Todays News,Headlines,Breaking News,Live News,Tollywood News,Tollywood Movie News"
        property="og:title" />
    <meta content="article" property="og:type" />
    <meta content="https://www.greatandhra.com/images/great_andhra_logo.gif" property="og:image" />
    <meta content="https://www.greatandhra.com/index.php" property="og:url" />
    <meta content="www.greatandhra.com" property="og:site_name" />
    <meta
        content="Greatandhra.com provides latest news from India and the world. Get todayÃ¢â‚¬â„¢s news headlines from Business, Technology,Telugu news, Cricket, videos, photos, live news coverage and exclusive breaking news from India."
        property="og:description" />
    <link href="https://www.greatandhra.com/" rel="canonical" />
    <meta content="--d1HbcWFdwBumVtTHK5L1MxZr-K-vhVSii3cr2XGEw" name="google-site-verification" />
    <link href="https://www.greatandhra.com/favicon.ico" rel="shortcut icon" type="image/x-icon">
    <link href="https://www.greatandhra.com/favicon.png" rel="shortcut icon" type="image/png" />
    <link href="https://www.greatandhra.com/favicon.ico" rel="icon" type="image/ico" />

    <link href="https://www.greatandhra.com/favicon.png" rel="icon" type="image/png" />
    <link href="assets/css" rel="stylesheet">
    <link href="assets/css2" rel="stylesheet" />
    <link href="css/main-single.css?v=<?php echo date('His'); ?>" rel="stylesheet" />
    <link href="css/footer.css?v=<?php echo date('His'); ?>" rel="stylesheet" />
    <link href="css/mobile-responsive.css?v=<?php echo date('His'); ?>" rel="stylesheet">
    <link href="css/header-mob.css?v=<?php echo date('His'); ?>" rel="stylesheet">
    <script src="js/drawer.js"></script>

    <!-- <link href="css/great_andhra_style_lato_font.css" rel="stylesheet" />
    <link href="css/home-style-lato-font.css" rel="stylesheet"> -->
    <!-- <link href="assets/poll.css" rel="stylesheet" /> -->
    <link href="assets/watch_video1234.css" rel="stylesheet" />
    <!-- <link href="css/great-block.css" rel="stylesheet"> -->
    <!-- <link href="https://m.greatandhra.com/" hreflang="en" media="only screen and (max-width: 640px)" rel="alternate"> -->
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta content="noodp,noydir" name="robots" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap" rel="stylesheet">

</head>

<body>
    <div id="fb-root"></div>
    <div class="local_great" style="position:fixed; width:100%; float:left; width:80px;">
        <div class="source-image-left" style="float:left; display:block; left:69px;">
            <?php ga_render_ad('HOMEPAGE_SIDEBAR_LEFT'); ?>
        </div>
    </div>
    <div class="local_great" style="position:fixed; width:120px; right:0;">
        <div class="source-image-right" style="float:right; display:block; right:69px;">
            <?php ga_render_ad('HOMEPAGE_SIDEBAR_RIGHT'); ?>
        </div>
    </div>
    <!--great_andhra_body-->
    <div class="great_andhra_body">
        <div class="great_andhra_logo_panel_top_box_201223_">
            <?php ga_render_ad('HOMEPAGE_ABOVE_HEADER_BANNER'); ?>
        </div>

        <!--great_andhra_logo_panel-->
        <div class="great_andhra_logo_panel">
            <a class="logo" href="index.php">
                <img alt="Greatandhra logo" src="images/great_andhra.gif" title="Greatandhra ebsite Logo" />
            </a>
            <div>

            </div>
            <div class="_201223_">
                <?php ga_render_ad('HOMEPAGE_TOP_BANNER'); ?>
            </div>

        </div>


        <div class="great_andhra_logo_panel-mob">
            <!-- First Row: Logo and Hamburger -->
            <div class="logo-bar">
                <a class="logo" href="index.php">
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
                                <li><a href="https://www.greatandhra.com/india-news">India</a></li>
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
                <?php // Reuses the Homepage Top Banner ad's mobile image, not a separate zone -
                // see GA_AD_ZONE_IMAGE_DIMENSIONS['HOMEPAGE_MOBILE_BANNER'] for why the 3rd
                // arg still references that zone name (sizing only, no ad is fetched under it). ?>
                <?php ga_render_ad('HOMEPAGE_MOBILE_BANNER'); ?>
            </div>
        </div>



        <link crossorigin="anonymous" href="assets/all.css"
            integrity="sha384-lZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9S+oqd12jhcu+A56Ebc1zFSJ" rel="stylesheet" />

        <!---Search button-->
        <script src="assets/jquery.min.1.8.2.js" type="text/javascript"></script>
        <script type="text/javascript">
            $(document).ready(function(e) {
                $('.search_img').click(function() {
                    $('#search_box_new').slideToggle('slow');
                });
            });
        </script>
        <script>
            $(document).ready(function() {

                /*$("body").click(function(){
                    $(".dropdown-content").removeAttr('style');
                });*/
                $(".dropdown").click(function() {
                    $(".dropdown-content").toggle();
                });
            });
        </script>
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
                        <li><a href="https://www.greatandhra.com/india-news" itemprop="url">india</a></li>
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
                    <a href="https://www.youtube.com/channel/UCoarMz-cpxAnBy8tszp35wA" target="_blank" title="youtube">
                        <i class="fab fa-youtube"></i>
                    </a>
                </li>
            </ul>
        </nav>

        <?php include_once 'html-files/trending-highlight.php'; ?>



        <div class="great_andhra_main_201223_">
            <div class="_201223_1">
                <?php ga_render_ad('HOMEPAGE_STRIP_BANNER_1'); ?>
            </div>
            <div class="_201223_2">
                <?php ga_render_ad('HOMEPAGE_STRIP_BANNER_2'); ?>
            </div>
            <div class="_201223_3">
                <?php ga_render_ad('HOMEPAGE_STRIP_BANNER_3'); ?>
            </div>

            <!-- <div class="local2">

                <a href="https://www.youtube.com/channel/UCoarMz-cpxAnBy8tszp35wA" target="_blank"><img
                        src="images/general/greatandhra_youtube.jpg" border='0' width='648' height='40'
                        alt="ga image" /> </a>

            </div> -->
        </div>
        <!--great_andhra_main_add_rotator-->
        <!--great_andhra_main_body_container-->
        <div class="great_andhra_main_body_container">
            <!--two_column_home-->
            <div class="two_column_home">
                <div class="paras float-left clear hero">
                    <div class="home_left_column">
                        <ul class="sortable-list ui-sortable">
                            <li class="sortable-item" style="margin: 0px;">
                                <div class="sortable-item_style_1">
                                    <div class="header"> Big Story </div>
                                    <?php if ($ga_hero_article): ?>
                                        <?php $ga_hero_img = ga_image($ga_hero_article, GA_HOME_HERO_FALLBACK_IMAGE); ?>
                                        <div class="content1">
                                            <div class="todays_picture">
                                                <a href="<?php echo ga_e(ga_inner_link($ga_hero_article)); ?>">
                                                    <img alt="<?php echo ga_e($ga_hero_article['title'] ?? ''); ?>"
                                                        border="0" height="<?php echo (int) $ga_hero_img['height']; ?>"
                                                        src="<?php echo ga_e($ga_hero_img['src']); ?>"
                                                        width="<?php echo (int) $ga_hero_img['width']; ?>" />
                                                </a>
                                            </div>
                                            <div class="big_description">
                                                <h1 class="big_content">
                                                    <a class="sublink" href="<?php echo ga_e(ga_inner_link($ga_hero_article)); ?>">
                                                        <?php echo ga_e(ga_truncate($ga_hero_article['title'] ?? '', GA_HOME_HERO_TITLE_MAX)); ?>
                                                    </a>
                                                </h1>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="content1">
                                            <div class="todays_picture ga-unavailable">
                                                <p class="ga-unavailable-msg">Content temporarily unavailable</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <li class="sortable-item_clear123">
                                <?php ga_render_ad('HOMEPAGE_BIG_STORY_BANNER'); ?>
                            </li>
                            <li>
                                <ul class="big-galist bg-unlist">
                                    <?php if (!empty($ga_list_articles)): ?>
                                        <?php foreach (array_values($ga_list_articles) as $ga_i => $ga_article): ?>
                                            <?php
                                            $ga_fallback = GA_HOME_LIST_FALLBACK_IMAGES[$ga_i] ?? GA_HOME_LIST_FALLBACK_IMAGES[0];
                                            $ga_list_img = ga_image($ga_article, $ga_fallback);
                                            $ga_list_title = ga_truncate($ga_article['title'] ?? '', GA_HOME_LIST_TITLE_MAX);
                                            ?>
                                            <li style="margin-bottom: 2px;"> <a
                                                    href="<?php echo ga_e(ga_inner_link($ga_article)); ?>">
                                                    <div class="big-galist-lft"> <img
                                                            alt="<?php echo ga_e($ga_article['title'] ?? ''); ?>"
                                                            height="<?php echo (int) $ga_list_img['height']; ?>"
                                                            src="<?php echo ga_e($ga_list_img['src']); ?>"
                                                            width="<?php echo (int) $ga_list_img['width']; ?>" /> </div>
                                                    <div class="big-galist-rgt">
                                                        <p><?php echo ga_e($ga_list_title); ?></p>
                                                    </div>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li class="ga-unavailable">
                                            <p class="ga-unavailable-msg">Content temporarily unavailable</p>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                            <!--related Articles end-->
                            </li>
                        </ul>
                    </div>
                    <div class="home_left_column mobile-latest-news">
                        <div class="sortable-item_style_1">
                            <div class="header">Latest News</div>
                            <ul class="mobile-latest-news-list">
                                <?php if (!empty($ga_mobile_latest_news_articles)): ?>
                                    <?php foreach ($ga_mobile_latest_news_articles as $ga_i => $ga_article): ?>
                                        <?php
                                        $ga_mln_fallback = GA_MOST_POPULAR_FALLBACK_IMAGES[$ga_i % count(GA_MOST_POPULAR_FALLBACK_IMAGES)];
                                        $ga_mln_img = ga_image($ga_article, $ga_mln_fallback);
                                        $ga_mln_title = ga_truncate($ga_article['title'] ?? '', GA_MOBILE_LATEST_NEWS_TITLE_MAX);
                                        ?>
                                        <li class="mobile-latest-news-item">
                                            <a href="<?php echo ga_e(ga_inner_link($ga_article)); ?>">
                                                <img alt="<?php echo ga_e($ga_article['title'] ?? ''); ?>"
                                                    src="<?php echo ga_e($ga_mln_img['src']); ?>"
                                                    class="mobile-latest-news-thumb" />
                                                <span class="mobile-latest-news-title"><?php echo ga_e($ga_mln_title); ?></span>
                                            </a>
                                        </li>
                                        <?php if ($ga_i === GA_MOBILE_LATEST_NEWS_AD_AFTER_INDEX): ?>
                                            <li class="mobile-latest-news-ad">
                                                <?php ga_render_ad('HOMEPAGE_LATEST_NEWS_INLINE_AD'); ?>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="ga-unavailable">
                                        <p class="ga-unavailable-msg">Content temporarily unavailable</p>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="home_left_column">
                        <ul class="sortable-list ui-sortable">
                            <li class="sortable-item clear mobile-hide-tabs">
                                <div class="sortable-item_style_2">
                                    <!--<div class="header"> <h1>Top Stories</h1> </div>-->
                                    <div class="content">
                                        <!-- Tab links -->
                                        <div class="tab">
                                            <button class="tablinks active" onclick="openTabs(event, 'top')">Top
                                                News</button>
                                            <button class="tablinks" onclick="openTabs(event, 'latest')">Most
                                                Read</button>
                                            <button class="tablinks" onclick="openTabs(event, 'telugu')">Telugu</button>
                                        </div>
                                        <!-- Tab content -->
                                        <div class="tabcontent" id="top" style="display:block;">
                                            <ul class="news_style">
                                                <?php if (!empty($ga_trending_articles)): ?>
                                                    <?php foreach ($ga_trending_articles as $ga_i => $ga_article): ?>
                                                        <li> <a class="oneline-title" href="<?php echo ga_e(ga_inner_link($ga_article)); ?>"
                                                                title="<?php echo ga_e($ga_article['title'] ?? ''); ?>">
                                                                <?php if ($ga_i < 2): ?><strong> <?php echo ga_e($ga_article['title'] ?? ''); ?> </strong><?php else: ?><?php echo ga_e($ga_article['title'] ?? ''); ?><?php endif; ?>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <li class="ga-unavailable">
                                                        <p class="ga-unavailable-msg">Content temporarily unavailable</p>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                        <div class="tabcontent" id="latest" style="display:none;">
                                            <ul class="news_style">
                                                <?php if (!empty($ga_most_read_articles)): ?>
                                                    <?php foreach ($ga_most_read_articles as $ga_article): ?>
                                                        <li> <a class="oneline-title" href="<?php echo ga_e(ga_inner_link($ga_article)); ?>"
                                                                title="<?php echo ga_e($ga_article['title'] ?? ''); ?>">
                                                                <?php echo ga_e($ga_article['title'] ?? ''); ?> </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <li class="ga-unavailable">
                                                        <p class="ga-unavailable-msg">Content temporarily unavailable</p>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                        <div class="tabcontent" id="telugu" style="display:none;">
                                            <ul class="news_style">
                                                <li>
                                                    <div class="mtitle">
                                                        <a href=" https://telugu.greatandhra.com/movies/movie-news/allu-arjuns-new-movie-schedule-canceled-due-to-gulf-war.html"
                                                            target="_blank">గల్ఫ్ యుద్ధం బారిన పడిన బన్నీ సినిమా</a>
                                                    </div>
                                                    <div class="CLR"></div>
                                                </li>
                                                <li>
                                                    <div class="mtitle">
                                                        <a href="https://telugu.greatandhra.com/movies/movie-news/can-pawan-fans-add-ubs-movie-into-their-library.html"
                                                            target="_blank">లైబ్రరీలో దాచుకునే ఫుల్ మీల్స్ సినిమా ఇదేనా?
                                                        </a>
                                                    </div>
                                                    <div class="CLR"></div>
                                                </li>
                                                <li>
                                                    <div class="mtitle">
                                                        <a href=" https://telugu.greatandhra.com/politics/andhra-news/bosta-commenta-on-amaravathi-bill.html"
                                                            target="_blank">బొత్స మాటలు పార్టీకి చేటు చేయవా?</a>
                                                    </div>
                                                    <div class="CLR"></div>
                                                </li>
                                                <li>
                                                    <div class="mtitle">
                                                        <a href="https://telugu.greatandhra.com/politics/andhra-news/jagan-focus-on-vijayanagaram.html"
                                                            target="_blank">విజయనగరం జిల్లా మీద జగన్ ఫోకస్</a>
                                                    </div>
                                                    <div class="CLR"></div>
                                                </li>
                                                <li>
                                                    <div class="mtitle">
                                                        <a href="https://telugu.greatandhra.com/politics/andhra-news/will-delimitation-become-the-foundation-for-the-disintegration-of-the-country.html"
                                                            target="_blank">డీలిమిటేషన్.. దేశ విచ్ఛిన్నానికి పునాది
                                                            అవుతుందా?</a>
                                                    </div>
                                                    <div class="CLR"></div>
                                                </li>
                                                <li>
                                                    <div class="mtitle">
                                                        <a href="https://telugu.greatandhra.com/politics/andhra-news/bhogapuram-as-a-assembly-constituency.html"
                                                            target="_blank">భోగాపురం పేరుతో అసెంబ్లీ సీటు</a>
                                                    </div>
                                                    <div class="CLR"></div>
                                                </li>
                                                <li>
                                                    <div class="mtitle">
                                                        <a href="https://telugu.greatandhra.com/movies/movie-gossip/benami-producers-under-ott-cover.html"
                                                            target="_blank">ఓటీటీ ముసుగులో బినామీ నిర్మాతలు</a>
                                                    </div>
                                                    <div class="CLR"></div>
                                                </li>
                                                <li>
                                                    <div class="mtitle">
                                                        <a href="https://telugu.greatandhra.com/articles/specialarticles/are-you-assessing-yourself-correctly.html"
                                                            target="_blank">మిమ్మ‌ల్ని మీరు స‌రిగ్గా అంచ‌నా
                                                            వేసుకుంటున్నారా?!</a>
                                                    </div>
                                                    <div class="CLR"></div>
                                                </li>
                                                <li>
                                                    <div class="mtitle">
                                                        <a href="https://telugu.greatandhra.com/articles/only-one-habit-can-make-or-break-a-relationship.html"
                                                            target="_blank">బంధాన్ని నిల‌బెట్టే, తెంపేయ‌గ‌ల‌.. ఒకే ఒక
                                                            అల‌వాటు!</a>
                                                    </div>
                                                    <div class="CLR"></div>
                                                </li>
                                                <li>
                                                    <div class="mtitle">
                                                        <a href="https://telugu.greatandhra.com/mbs/parsis-iranis-aryans.html"
                                                            target="_blank">ఎమ్బీయస్‌: పార్సీలు – ఇరానీలు – ఆర్యన్లు</a>
                                                    </div>
                                                    <div class="CLR"></div>
                                                </li>
                                                <li>
                                                    <div class="mtitle">
                                                        <a href="https://telugu.greatandhra.com/movies/movie-news/will-varanasi-poster-has-rajamoulis-stamp.html"
                                                            target="_blank">వారణాసిపై రాజమౌళి రాజముద్ర పడదా?</a>
                                                    </div>
                                                    <div class="CLR"></div>
                                                </li>
                                                <li>
                                                    <div class="mtitle">
                                                        <a href="https://telugu.greatandhra.com/politics/national/son-in-kill-own-uncle.html"
                                                            target="_blank">అత్తను గాఢంగా ప్రేమించాడు.. మేనమామను ఖతం
                                                            చేశాడు!</a>
                                                    </div>
                                                    <div class="CLR"></div>
                                                </li>
                                                <li>
                                                    <div class="mtitle">
                                                        <a href="https://telugu.greatandhra.com/politics/andhra-news/the-rdt-episode-a-case-study-for-jagan.html"
                                                            target="_blank">ఆర్డీటీ ఎపిసోడ్‌…జ‌గ‌న్‌కు ఓ కేస్
                                                            స్ట‌డీ!</a>
                                                    </div>
                                                    <div class="CLR"></div>
                                                </li>
                                                <li>
                                                    <div class="mtitle">
                                                        <a href="https://telugu.greatandhra.com/movies/movie-news/this-is-the-last-chance-for-ustaad.html"
                                                            target="_blank">ఉస్తాద్ కు ఇదే చివరి అవకాశం</a>
                                                    </div>
                                                    <div class="CLR"></div>
                                                </li>
                                                <li>
                                                    <div class="mtitle">
                                                        <a href="https://telugu.greatandhra.com/politics/national/the-young-prime-minister-who-sent-the-former-pm-to-jail.html"
                                                            target="_blank">మాజీ ప్రధానిని జైలుకు పంపిన యువ
                                                            ప్రధాని…!</a>
                                                    </div>
                                                    <div class="CLR"></div>
                                                </li>
                                            </ul>
                                        </div>
                                        <style>
                                            /* Style the tab */
                                            /* .tab {
                                                overflow: hidden;
                                                border-bottom: 1px #d9d9d9 solid;
                                                background-color: #ffffff;
                                            } */

                                            /* Style the buttons that are used to open the tab content */
                                            /* .tab button {
                                                background-color: #ffffff;
                                                float: left;
                                                border: none;
                                                outline: none;
                                                cursor: pointer;
                                                padding: 5px 5px;
                                                font-family: 'Lato';
                                                width: 33.333333333%;
                                                margin: 0px;
                                                font-size: 12.5px;
                                                color: #666666;
                                                letter-spacing: 0.5px;
                                            } */

                                            /* Change background color of buttons on hover */
                                            /* .tab button:hover {
                                                font-weight: 600;
                                                background: #f2f2f2;
                                                color: #000;
                                            } */

                                            /* Create an active/current tablink class */
                                            /* .tab button.active {
                                                font-weight: 600;
                                                border-bottom: 2px solid #000000;
                                                background: #f2f2f2;
                                                color: #000;
                                            } */

                                            /* Style the tab content */
                                            /* .tabcontent {
                                                display: none;
                                                padding: 6px 0px;
                                                border: 0px solid #ccc;
                                                border-top: none;
                                            } */
                                        </style>
                                    </div>
                                </div>
                            </li>
                            <!--related Articles Start-->
                        </ul>
                    </div>
                </div>
            </div>
            <!--two_column_home-->
            <div class="home_right_column">
                <ul class="sortable-list ui-sortable">
                    <!--<li class="sortable-item">

					<div>				

			 	 		<a href="https://urthindia.com/"><img src="images/general/urthindia-300.gif" width="300"  height="50" alt="Sankranthi" /> </a> 

					</div>

				</li>-->
                    <li class="sortable-item clear">
                        <div class="sortable-item_style_13">
                            <div class="header"> Talk Of The Town </div>
                            <div class="content">
                                <ul class="news_style">
                                    <?php if (!empty($ga_talk_of_town_articles)): ?>
                                        <?php foreach ($ga_talk_of_town_articles as $ga_article): ?>
                                            <li>
                                                <a class="oneline-title" href="<?php echo ga_e(ga_inner_link($ga_article)); ?>"
                                                    title="<?php echo ga_e($ga_article['title'] ?? ''); ?>">
                                                    <?php echo ga_e($ga_article['title'] ?? ''); ?> </a>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li class="ga-unavailable">
                                            <p class="ga-unavailable-msg">Content temporarily unavailable</p>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        <style>
                            /* .great_andhra_main_body_container .news_style li a.oneline-title {
                                overflow: hidden;
                                text-overflow: ellipsis;
                                display: -webkit-box;
                                -webkit-line-clamp: 1;
                                -webkit-box-orient: vertical;
                            } */
                        </style>
                    </li>
                    <div class="display-inline-block padding-top-bot">
                        <li class="sortable-item">
                            <?php ga_render_ad('HOMEPAGE_SECTION_INLINE'); ?>
                        </li>
                    </div>
                </ul>
            </div>
        </div>
        <!--great_andhra_main_body_container-->
        <div class="great_andhra_main_body_container ad-opinion">
            <div class="full_width_home editors_pick_full_width border-topbottom margin-top-bot">
                <?php ga_render_ad('HOMEPAGE_OPINION_BANNER'); ?>
            </div>
            <div class="full_width_home editors_pick_full_width" style="padding-top: 10px;">
                <style>
                    /* ul.bg-unlist.ga-featured-videos li {
                        background: #f2f2f2;
                        width: 188px;
                        float: left;
                        padding: 2px;
                        margin: 4px 0px;
                        margin-left: 6px;
                        min-height: 190px;
                    } */

                    /* .ga-featured-videos .big-galist-lft {
                        float: left;
                        margin-right: 0px;
                        height: 110px;
                        width: 185px;
                    } */

                    /* .ga-featured-videos .big-galist-rgt {
                        float: left;
                        width: 100%;
                        min-height: 50px;
                        padding: 0 5px 0 5px;
                    } */
                    /* 
                    i.fa.fa-play-circle-o.fa-play-circle-oo {
                        font-size: 30px;
                        top: -35px;
                        position: relative;
                        clear: both;
                        color: #ffffff;
                    } */

                    /* .editors_pick_full {
                        width: 100% !important;
                    } */

                    /* ul.editors_pick_full li:nth-child(1) {
                        margin-left: 0px;
                    } */

                    /* ul.editors_pick_full li {
                        margin-right: 5px !important;
                    } */

                    /* ul.bg-unlist.ga-grply a p {
                        font-weight: 600;
                    } */
                </style>
                <div class="ga-gallery">
                    <div class="ga-mdcenter">
                        <h3 style="font-size: 14px; background: none;"> OPINION </h3>
                        <ul class="bg-unlist ga-grply ga-featured-videos editors_pick_full">
                            <?php if (!empty($ga_opinion_articles)): ?>
                                <?php foreach ($ga_opinion_articles as $ga_i => $ga_article): ?>
                                    <?php
                                    $ga_fallback = GA_OPINION_FALLBACK_IMAGES[$ga_i] ?? GA_OPINION_FALLBACK_IMAGES[0];
                                    $ga_img = ga_image($ga_article, $ga_fallback);
                                    $ga_title = ga_truncate($ga_article['title'] ?? '', GA_OPINION_TITLE_MAX);
                                    ?>
                                    <li>
                                        <a href="<?php echo ga_e(ga_inner_link($ga_article)); ?>"
                                            title="<?php echo ga_e($ga_article['title'] ?? ''); ?>">
                                            <div class="big-galist-lft">
                                                <img alt="<?php echo ga_e($ga_article['title'] ?? ''); ?>"
                                                    height="<?php echo (int) $ga_img['height']; ?>"
                                                    src="<?php echo ga_e($ga_img['src']); ?>"
                                                    width="<?php echo (int) $ga_img['width']; ?>">
                                                </img>
                                            </div>
                                            <div class="big-galist-rgt">
                                                <p><?php echo ga_e($ga_title); ?></p>
                                            </div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="ga-unavailable">
                                    <p class="ga-unavailable-msg">Content temporarily unavailable</p>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="great_andhra_main_body_container category_section">
            <!--two_column_home-->
            <div class="two_column_home">
                <style>
                    /* .editors_pick {

                        background-color: #0a2149;

                        height: 210px;

                        color: #ffffff;

                    } */

                    /* .great_andhra_main_body_container .news_style.news_section_ul li.editors_pick_li {

                        width: 50%;

                        margin: 0px;

                        padding: 0px;

                    } */

                    /* li.editors_pick_li.clearfix img {

                        width: 100%;

                        height: 210px;

                    }

                    span.editors_pick_cat {

                        margin: 10px;

                        text-transform: uppercase;

                        width: 90%;

                        display: inline-block;

                    }

                    span.editors_pick_title {

                        width: 90%;

                        display: inline-block;

                        margin-left: 10px;

                        font-size: 25px;

                        line-height: 30px;

                        font-weight: bold;

                    }

                    span.editors_pick_desc {

                        width: 90%;

                        display: inline-block;

                        margin-left: 10px;

                        font-size: 14px;

                        line-height: 20px;

                        margin-top: 0px;

                    } */
                </style>
                <div class="paras float-left clear">
                    <div class="home_left_column news-section">
                        <ul class="sortable-list ui-sortable">
                            <li class="sortable-item">
                                <div class="sortable-item_style_3">
                                    <div class="content">
                                        <ul class="news_style news_section_ul" style="padding-top:3px;">
                                            <?php if ($ga_editors_pick): ?>
                                                <?php
                                                $ga_ep_link = ga_e(ga_inner_link($ga_editors_pick));
                                                $ga_ep_full_title = ga_e($ga_editors_pick['title'] ?? '');
                                                $ga_ep_post_title = ga_e(ga_truncate($ga_editors_pick['title'] ?? '', GA_EDITORS_PICK_TITLE_MAX));
                                                $ga_ep_movie_name = ga_e($ga_editors_pick['schemaData']['movieName'] ?? '');
                                                $ga_ep_rating = ga_e($ga_editors_pick['schemaData']['rating'] ?? '');
                                                $ga_ep_release = ga_format_date($ga_editors_pick['schemaData']['releaseDate'] ?? null, 'd-M-Y');
                                                $ga_ep_img = ga_image($ga_editors_pick, GA_REVIEWS_FALLBACK_IMAGE);
                                                ?>
                                                <li class="editors_pick_li clearfix">
                                                    <a href="<?php echo $ga_ep_link; ?>"
                                                        title="<?php echo $ga_ep_full_title; ?>">
                                                        <div class="editors_pick">
                                                            <span class="editors_pick_post_title"><?php echo $ga_ep_post_title; ?></span>
                                                            <span class="editors_pick_title"><?php echo $ga_ep_movie_name; ?></span>

                                                        </div>
                                                        <div class="editor_pick_review">
                                                            <span class="editors_pick_desc">Rating : <?php echo $ga_ep_rating; ?></span>
                                                            <?php if ($ga_ep_release !== ''): ?>
                                                                <span class="editors_pick_release">Release Date : <?php echo ga_e($ga_ep_release); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </a>
                                                </li>
                                                <li class="main-story editors_pick_li clearfix">
                                                    <a href="<?php echo $ga_ep_link; ?>"
                                                        title="<?php echo $ga_ep_full_title; ?>">
                                                        <img alt="<?php echo $ga_ep_full_title; ?>" height="<?php echo (int) $ga_ep_img['height']; ?>"
                                                            src="<?php echo ga_e($ga_ep_img['src']); ?>" width="<?php echo (int) $ga_ep_img['width']; ?>" />
                                                    </a>
                                                </li>
                                            <?php else: ?>
                                                <li class="editors_pick_li clearfix ga-unavailable">
                                                    <p class="ga-unavailable-msg">Content temporarily unavailable</p>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <!-- categories Start-->
                <div class="paras float-left clear">
                    <div class="home_left_column news-section">
                        <ul class="sortable-list ui-sortable">
                            <li class="sortable-item">
                                <div class="sortable-item_style_3">
                                    <a href="<?php echo ga_e(ga_nav_category_link('movie-news', 'Movie News')); ?>">
                                        <div class="header"> Movie News <span class="more_arrow"></span> </div>
                                    </a>
                                    <div class="content">
                                        <ul class="news_style news_section_ul" style="padding-top:3px;">
                                            <?php ga_render_category_section($ga_movie_news_articles, GA_MOVIE_NEWS_FALLBACK_IMAGE, GA_CATEGORY_SECTION_TITLE_MAX); ?>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="home_left_column news-section">
                        <ul class="sortable-list ui-sortable">
                            <li class="sortable-item">
                                <div class="sortable-item_style_3">
                                    <!-- Moview Gossip -->
                                    <a href="<?php echo ga_e(ga_nav_category_link('movie-gossip', 'Movie Gossip')); ?>">
                                        <div class="header"> Movie Gossip <span class="more_arrow"></span> </div>
                                    </a>
                                    <div class="content">
                                        <ul class="news_style news_section_ul" style="padding-top:3px;">
                                            <?php ga_render_category_section($ga_movie_gossip_articles, GA_MOVIE_GOSSIP_FALLBACK_IMAGE, GA_CATEGORY_SECTION_TITLE_MAX); ?>
                                        </ul>
                                        <!--<div class="more"> <a href="https://www.greatandhra.com/movie-gossip-5.html"> more </a> </div>-->
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="paras float-left clear">
                    <div class="home_left_column news-section">
                        <ul class="sortable-list ui-sortable">
                            <li class="sortable-item">
                                <div class="sortable-item_style_3">
                                    <a href="<?php echo ga_e(ga_nav_category_link('andhra-news', 'Andhra News')); ?>">
                                        <div class="header"> Andhra News <span class="more_arrow"></span> </div>
                                    </a>
                                    <div class="content">
                                        <ul class="news_style news_section_ul" style="padding-top:3px;">
                                            <?php ga_render_category_section($ga_andhra_news_articles, GA_ANDHRA_NEWS_FALLBACK_IMAGE, GA_CATEGORY_SECTION_TITLE_MAX); ?>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="home_left_column news-section">
                        <ul class="sortable-list ui-sortable">
                            <li class="sortable-item">
                                <div class="sortable-item_style_3">
                                    <!-- telangana-news -->
                                    <a href="<?php echo ga_e(ga_nav_category_link('telangana-news', 'Telangana News')); ?>">
                                        <div class="header"> Telangana News <span class="more_arrow"></span> </div>
                                    </a>
                                    <div class="content">
                                        <ul class="news_style news_section_ul" style="padding-top:3px;">
                                            <?php ga_render_category_section($ga_telangana_news_articles, GA_TELANGANA_NEWS_FALLBACK_IMAGE, GA_CATEGORY_SECTION_TITLE_MAX); ?>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="paras float-left clear">
                    <div class="home_left_column news-section">
                        <ul class="sortable-list ui-sortable">
                            <li class="sortable-item">
                                <div class="sortable-item_style_3">
                                    <!-- Gossip -->
                                    <a href="<?php echo ga_e(ga_nav_category_link('politics', 'Politics', true)); ?>">
                                        <div class="header"> Gossip <span class="more_arrow"></span> </div>
                                    </a>
                                    <div class="content">
                                        <ul class="news_style news_section_ul" style="padding-top:3px;">
                                            <?php ga_render_category_section($ga_politics_gossip_articles, GA_POLITICS_GOSSIP_FALLBACK_IMAGE, GA_CATEGORY_SECTION_TITLE_MAX); ?>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="paras float-left clear" style="max-width: 675px !important;">
                    <ul class="sortable-list ui-sortable">
                        <li class="sortable-item text-center">
                            <div class="add_place_50 margin-top-bot">
                                <div id="vuukle-ad-9"></div>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="paras float-left clear">
                    <div class="home_left_column news-section">
                        <ul class="sortable-list ui-sortable">
                            <li class="sortable-item" id="sortable-item_5">
                                <div class="sortable-item_style_3">
                                    <a href="<?php echo ga_e(ga_nav_category_link('reviews', 'Reviews')); ?>">
                                        <div class="header"> Reviews <span class="more_arrow"></span> </div>
                                    </a>
                                    <div class="content">
                                        <ul class="news_style news_section_ul" style="padding-top:3px;">
                                            <?php ga_render_category_section($ga_reviews_articles, GA_REVIEWS_FALLBACK_IMAGE, GA_CATEGORY_SECTION_TITLE_MAX); ?>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!--two_column_home-->
            <div class="home_right_column">
                <ul class="sortable-list ui-sortable">
                    <li class="sortable-item clear" style="padding-top: 3px;">
                        <div class="sortable-item_style_13">
                            <div class="header"> USA Movie Schedules </div>
                            <div class="content">
                                <ul class="news_style">
                                    <?php if (!empty($ga_usa_movie_schedule)): ?>
                                        <?php foreach ($ga_usa_movie_schedule as $ga_schedule_item): ?>
                                            <li><a class="oneline-title" href="<?php echo ga_e($ga_schedule_item['linkUrl'] ?? ''); ?>"
                                                    <?php echo !empty($ga_schedule_item['openInNewTab']) ? 'target="_blank"' : ''; ?>><?php echo ga_e($ga_schedule_item['title'] ?? ''); ?></a></li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li class="ga-unavailable">
                                            <p class="ga-unavailable-msg">Content temporarily unavailable</p>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </li>
                    <li class="sortable-item clear">
                        <div class="sortable-item_style_13">
                            <div class="header"> Featured </div>
                            <div class="content">
                                <ul class="news_style">
                                    <?php if (!empty($ga_featured_articles)): ?>
                                        <?php foreach ($ga_featured_articles as $ga_article): ?>
                                            <li><a class="oneline-title" href="<?php echo ga_e(ga_inner_link($ga_article)); ?>"
                                                    title="<?php echo ga_e($ga_article['title'] ?? ''); ?>"><?php echo ga_e($ga_article['title'] ?? ''); ?></a></li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li class="ga-unavailable">
                                            <p class="ga-unavailable-msg">Content temporarily unavailable</p>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </li>
                    <li class="sortable-item clear">
                        <style>
                            /* .trending_topics a {
                                color: #727272;
                                font-size: 11px;
                                padding: 4px 11px;
                                border-radius: 17.5px;
                                background-color: #fff;
                                text-transform: capitalize;
                                border: 1px solid #d3d3d3;
                                margin-top: 6px;
                                display: inline-block;
                            }

                            .trending_topics a:hover {
                                text-decoration: underline;
                            } */
                        </style>
                        <div class="sortable-item_style_13">
                            <div class="header"> Top Trending Topics </div>
                            <div class="content trending_topics">
                                <?php if (!empty($ga_trending_tags)): ?>
                                    <?php foreach ($ga_trending_tags as $ga_tag): ?>
                                        <a href="<?php echo ga_e(ga_tag_link($ga_tag['slug'] ?? '')); ?>"><?php echo ga_e($ga_tag['name'] ?? ''); ?></a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="ga-unavailable-msg">Content temporarily unavailable</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                    <style>
                        /* .content.trending_topics {

                            margin-bottom: 10px;

                        } */

                        /* .trending_topics a {

                            color: #727272;

                            font-size: 11px;

                            padding: 5px 10px;

                            border-radius: 18px;

                            background-color: #fff;

                            text-transform: capitalize;

                            border: 1px solid #d3d3d3;

                            margin-top: 6px;

                            display: inline-block;

                            text-decoration: none;

                            font-family: 'Lato';

                        } */

                        /* .trending_topics a:hover {

                            text-decoration: underline;

                        } */
                    </style>
                    <div class="display-inline-block padding-top-bot">
                        <li class="sortable-item sortable-item_top_add123">
                            <?php ga_render_ad('HOMEPAGE_ARTICLE_WIDGET_AD'); ?>
                        </li>
                    </div>
                    <li class="sortable-item clear">
                        <div class="sortable-item_style_13">
                            <div class="header"> Article </div>
                            <div class="content">
                                <ul class="news_style">
                                    <?php if (!empty($ga_article_section_articles)): ?>
                                        <?php foreach ($ga_article_section_articles as $ga_article): ?>
                                            <li><a class="oneline-title" href="<?php echo ga_e(ga_inner_link($ga_article)); ?>"><?php echo ga_e($ga_article['title'] ?? ''); ?></a></li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li class="ga-unavailable">
                                            <p class="ga-unavailable-msg">Content temporarily unavailable</p>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </li>
                    <!--<li class="sortable-item">

						<div id="vuukle-ad-15" style="min-width: 300px; min-height: 250px;" class="display-inline-block margin-top-bot">                    

					</li>-->
                    <li class="sortable-item">
                        <div class="sortable-item_style_14 most-popular-widget">
                            <div class="header"> Most Popular </div>
                            <div class="hm_topstory_3_story">
                                <ul class="top_story_option2_3story list_top_news_mrgn">
                                    <?php if (!empty($ga_popular_articles)): ?>
                                        <?php foreach ($ga_popular_articles as $ga_i => $ga_article): ?>
                                            <?php
                                            $ga_fallback = GA_MOST_POPULAR_FALLBACK_IMAGES[$ga_i] ?? GA_MOST_POPULAR_FALLBACK_IMAGES[0];
                                            $ga_img = ga_image($ga_article, $ga_fallback);
                                            $ga_title = ga_truncate($ga_article['title'] ?? '', GA_MOST_POPULAR_TITLE_MAX);
                                            ?>
                                            <li>
                                                <a href="<?php echo ga_e(ga_inner_link($ga_article)); ?>"
                                                    title="<?php echo ga_e($ga_article['title'] ?? ''); ?>">
                                                    <div class="top_newsbox_img">
                                                        <img alt="<?php echo ga_e($ga_article['title'] ?? ''); ?>" border="0"
                                                            height="<?php echo (int) $ga_img['height']; ?>"
                                                            src="<?php echo ga_e($ga_img['src']); ?>"
                                                            width="<?php echo (int) $ga_img['width']; ?>" />
                                                    </div>
                                                    <div class="top_news_txt">
                                                        <?php echo ga_e($ga_title); ?>
                                                    </div>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li class="ga-unavailable">
                                            <p class="ga-unavailable-msg">Content temporarily unavailable</p>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </li>
                    <!--<li class="sortable-item">

						<div id="vuukle-ad-16" style="min-width: 300px; min-height: 250px;" class="margin-top-bot">                    

					</li>-->
                </ul>
            </div>
        </div>
        <!--great_andhra_main_body_container-->
        <!--great_andhra_main_footer-->
        <!-- <div class="great_andhra_main_footer">
            <div class="copyright_bar_index">
                <footer class="section gan-footer" id="gan-footer">
                    <div class="gangrid-main gan-footer-first" id="gan-footer-first">
                        <section class="block" style="width:96%;">
                            <ul class="menuf" style="text-align: center;">
                                <li><a href="https://www.greatandhra.com/aboutus.php" target="_blank"
                                        title="Visit About Us">About Us</a></li>
                                <li><a href="https://www.greatandhra.com/disclaimer.php" target="_blank"
                                        title="Visit Disclaimer">Disclaimer</a></li>
                                <li><a href="https://www.greatandhra.com/contactus.php" target="_blank"
                                        title="Visit Contact Us">Contact Us</a></li>
                                <li><a href="https://www.greatandhra.com/convergence/index.php" target="_blank"
                                        title="Visit Advertise With Us">Advertise With Us</a></li>
                                <li><a href="https://www.greatandhra.com/privacy.php" target="_blank"
                                        title="Visit Privacy Policy">Privacy Policy</a></li>
                                <li><a href="https://www.greatandhra.com/grievance.php" target="_blank"
                                        title="Visit Grievance">Grievance</a></li>
                                <li><a href="https://epaper.greatandhra.com/" target="_blank"
                                        title="Visit ePaper">ePaper</a></li>
                            </ul>
                        </section>
                    </div>
                    <div class="gangrid-main gan-footer-first" id="gan-footer-first">
                        <a href="https://www.facebook.com/greatandhra" target="_blank" title="facebook"><i
                                class="fab fa-facebook-f"></i></a>
                        <a href="https://twitter.com/greatandhranews" target="_blank" title="twitter"><i
                                class="fab fa-twitter"></i></a>
                        <a href="https://www.youtube.com/channel/UCoarMz-cpxAnBy8tszp35wA" target="_blank"
                            title="youtube"><i class="fab fa-youtube"></i></a>
                    </div>
                    <div class="gangrid-main gan-footer-third" id="gan-footer-third">
                        <div class="block"> ©
                            2026 greatandhra | All rights reserved </div>
                    </div>
                </footer>
            </div>
        </div> -->
        <!-- new footer section -->
        <div class="new_great_andhra_main_footer">
            <div class="footer-container">

                <!-- Navigation Links: Lato 13px White -->
                <nav>
                    <ul class="footer-nav-links">
                        <li><a href="https://www.greatandhra.com/aboutus.php" target="_blank">About Us</a></li>
                        <li><a href="https://www.greatandhra.com/disclaimer.php" target="_blank">Disclaimer</a></li>
                        <li><a href="https://www.greatandhra.com/contactus.php" target="_blank">Contact Us</a></li>
                        <li><a href="https://www.greatandhra.com/convergence/index.php" target="_blank">Advertise With
                                Us</a></li>
                        <li><a href="https://www.greatandhra.com/privacy.php" target="_blank">Privacy Policy</a></li>
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
                    <a href="https://www.youtube.com/channel/UCoarMz-cpxAnBy8tszp35wA" target="_blank" title="YouTube">
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
            } */

            /* .menuf li a {
                font-family: Lato;
            }

            .menuf li a:hover {
                color: #fff !important;
            }

            .gan-footer {
                background: #292221;
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

    <!--great_andhra_body-->
    <script>
        var VUUKLE_CONFIG = {
            apiKey: '2b166297-6273-48a9-82e9-696327c67418',
            articleId: '1',
            comments: {
                enabled: false
            },
            emotes: {
                "enabled": false
            },
            powerbar: {
                "enabled": false
            },
            ads: {
                noDefaults: true
            }
        };
        (function() {
            var d = document,
                s = d.createElement('script');
            s.async = true;
            s.src = 'https://cdn.vuukle.com/platform.js';
            (d.head || d.body).appendChild(s);
        })();
    </script>
</body>
<script src="js/jquery-ui-1.8.custom.min.js" type="text/javascript"> </script>
<script src="js/jquery.marquee.js" type="text/javascript"> </script>
<script src="js/great_andhra_view_js_160_1.js" type="text/javascript"> </script>
<link href="js/font-awesome.min.css" rel="stylesheet" />
<style>
    /* .great_andhra_main_menu_panel_2019 {

        border-left: 1px solid #e6e6e6;

        background: #fbfbfb;

        margin-bottom: 0px;

    } */

    /* .great_andhra_main_menu_panel_2019 ul li a {
        text-transform: capitalize;
        font-weight: 600 !important;
        font-family: 'Lato';
        border-left: 0px;
        letter-spacing: 0.5px;
    } */
    /* 
    ul.top_story_option2_3story li .top_news_txt a {

        font-size: 14px;

        font-family: 'Lato';

    } */

    /* .great_andhra_main_local_rotator1 {

        border: 0px;

    } */

    /* .ga-gallery {

        width: 990px;

    } */

    /* .ga-mdcenter {

        width: 990px;

    } */
    /* 
    .great_andhra_main_local_rotator1 {

        border: 0px;

    } */

    /* ul.top_story_option2_3story li .top_news_txt {

        margin-right: 4px;

        line-height: 21px;

        color: #262626;

        text-decoration: none;

        padding-top: 10px;

        display: block;

        font-family: 'Lato';

        font-size: 14px;

        margin-bottom: -21px;

    } */

    /* .border-topbottom {

        border-top: 1px #d9d9d9 solid;

        border-bottom: 1px #d9d9d9 solid;

        padding: 8px;

    } */
</style>
<script>
    function openTabs(evt, cityName) {

        // Declare all variables

        var i, tabcontent, tablinks;



        // Get all elements with class="tabcontent" and hide them

        tabcontent = document.getElementsByClassName("tabcontent");

        for (i = 0; i < tabcontent.length; i++) {

            tabcontent[i].style.display = "none";

        }



        // Get all elements with class="tablinks" and remove the class "active"

        tablinks = document.getElementsByClassName("tablinks");

        for (i = 0; i < tablinks.length; i++) {

            tablinks[i].className = tablinks[i].className.replace(" active", "");

        }



        // Show the current tab, and add an "active" class to the button that opened the tab

        document.getElementById(cityName).style.display = "block";

        evt.currentTarget.className += " active";

    }
</script>

</html>