<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/api-client.php';
require_once __DIR__ . '/inc/helpers.php';

// Big Story hero + the flagged articles below the ad, and the "Top News" tab's trending
// articles — all resolved server-side (filtering, sorting, hero-exclusion) by the same
// /api/public/homepage call, cached once and read for both.
$ga_home_data = ga_fetch_homepage();
$ga_hero_article = $ga_home_data['bigStory']['hero'] ?? null;
$ga_list_articles = $ga_home_data['bigStory']['related'] ?? [];
$ga_trending_articles = $ga_home_data['trending'] ?? [];
$ga_opinion_articles = $ga_home_data['opinion'] ?? [];

// Shared latest-articles batch feeds three sections: "Article" (latest-first, as fetched),
// Most Popular and the "Most Read" tab (both viewCount-sorted, excluding the Big Story hero
// so it never reappears elsewhere on the page).
$ga_latest_feed = ga_fetch_articles(GA_MOST_POPULAR_FEED_SIZE, 0);

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
$ga_most_read_articles = array_slice($ga_popular_sorted, 0, 15);
?>
<!-- <script type="text/javascript">
    if (screen.width <= 700) {
        document.location = "https://m.greatandhra.com/";
        exit();
    }

    var isRoadBlock = "true";
    var gaRBCookieName = "garb";
    if (isRoadBlock == 'true') {
        var isCoookieSet = 'false';
        var pattern = RegExp("garb=.[^;]*");
        matched = document.cookie.match(pattern);
        if (matched) {
            var cookie = matched[0].split('=');
            var cookie = cookie[1];
        }
        if (cookie != '' && cookie == '1') {
            var isCoookieSet = 'true';
        }
        if (isCoookieSet == 'false') {
            var minutes = "10";
            var date = new Date();
            date.setTime(date.getTime() + (minutes * 60 * 1000));
            var expires = "; expires=" + date.toGMTString();
            document.cookie = gaRBCookieName + "=1; " + expires + "; path=/";
            document.location = "https://www.greatandhra.com/advertisement.php";
            exit();
        }
    }
</script> -->
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
            <!--<a href="https://www.indianclicks.com/clicks.php?url=https://www.giridhariconstructions.com/prospera-county.php&sid=GALeft"><img src="images/general/Giridhari_Constructions_160_07222023_1_1.jpg" width="160" alt="Andhra Pradesh Number One" /> </a> 	-->
            <a href="https://www.msnrealty.com/new-lp/GreatAndhra"><img alt="MSN Realty" src="images/msn_new_160_2.jpg"
                    width="160" /> </a>

        </div>
    </div>
    <div class="local_great" style="position:fixed; width:120px; right:0;">
        <div class="source-image-right" style="float:right; display:block; right:69px;">
            <!-- RHS AD 1 ==> Starts -->
            <!--<a href="https://www.indianclicks.com/clicks.php?url=https://www.giridhariconstructions.com/prospera-county.php&sid=GARight"><img src="images/general/Giridhari_Constructions_160_07222023_1_1.jpg" width="160" alt="Andhra Pradesh Number One" /> </a> -->
            <a href="https://www.msnrealty.com/new-lp/GreatAndhra"><img alt="MSN Realty" src="images/msn_new_160_2.jpg"
                    width="160" /> </a>
            <!--<div id="vuukle-ad-4"></div>-->
            <!-- RHS AD 1 ==> Ends -->
        </div>
    </div>
    <!--great_andhra_body-->
    <div class="great_andhra_body">
        <div class="great_andhra_logo_panel_top_box_201223_">
            <a href="https://www.msnrealty.com/new-lp/GreatAndhra">
                <img alt="MSN Reality" src="images/msn_990_2.jpg" width="990" />
            </a>
        </div>

        <!--great_andhra_logo_panel-->
        <div class="great_andhra_logo_panel">
            <a class="logo" href="https://www.greatandhra.com/">
                <img alt="Greatandhra logo" src="images/great_andhra.gif" title="Greatandhra ebsite Logo" />
            </a>
            <div>

            </div>
            <div class="_201223_">
                <p style="font-size: 11px;text-align: center;"> Advertisement</p>
                <a href="https://bestbrains.com/promotions/newyearoffer" target="_blank"><img alt="Best Brains"
                        border="0" height="90" src="images/Best_Brains_728_12242025_1.jpg" width="728" /> </a>
            </div>

        </div>


        <div class="great_andhra_logo_panel-mob">
            <!-- First Row: Logo and Hamburger -->
            <div class="logo-bar">
                <a class="logo" href="https://www.greatandhra.com/">
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
                            <a href="https://www.greatandhra.com/">
                                <i class="fas fa-home"></i> Home
                            </a>
                        </li>
                        <li>
                            <a href="https://www.greatandhra.com/latest">Latest</a>
                        </li>
                        <li class="has-submenu">
                            <a href="#" class="submenu-toggle">
                                Politics <i class="fas fa-caret-down"></i>
                            </a>
                            <ul class="submenu">
                                <li><a href="https://www.greatandhra.com/andhra-news">Andhra</a></li>
                                <li><a href="https://www.greatandhra.com/telangana-news">Telangana</a></li>
                                <li><a href="https://www.greatandhra.com/india-news">India</a></li>
                            </ul>
                        </li>
                        <li class="has-submenu">
                            <a href="#" class="submenu-toggle">
                                Movies <i class="fas fa-caret-down"></i>
                            </a>
                            <ul class="submenu">
                                <li><a href="https://www.greatandhra.com/movies">News</a></li>
                                <li><a href="https://www.greatandhra.com/moviegossip">Gossip</a></li>
                                <li><a href="https://www.greatandhra.com/boxoffice">Box Office</a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="https://www.greatandhra.com/reviews">Reviews</a>
                        </li>
                        <li>
                            <a href="https://gallery.greatandhra.com/index.php">Gallery</a>
                        </li>
                        <li>
                            <a href="https://www.greatandhra.com/opinion">Opinion</a>
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
                <p style="font-size: 11px; text-align: center;">Advertisement</p>
                <a href="https://bestbrains.com/promotions/newyearoffer" target="_blank">
                    <img alt="Best Brains" border="0" src="images/IndianClicks_BestBrains_380x250_12242025_1.webp"
                        style="max-width: 100%; height: 250px;" />
                </a>
            </div>
        </div>
        


        <link crossorigin="anonymous" href="assets/all.css"
            integrity="sha384-lZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9S+oqd12jhcu+A56Ebc1zFSJ" rel="stylesheet" />

        <!---Search button-->
        <script src="assets/jquery.min.1.8.2.js" type="text/javascript"></script>
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
        <nav class="ga-nav" itemscope itemtype="https://www.schema.org/SiteNavigationElement">
            <ul class="menu">
                <!-- Home Icon -->
                <li class="menu-item">
                    <a href="https://www.greatandhra.com/" class="menu-link" title="greandhra home" itemprop="url">
                        <span itemprop="name"><i class="fas fa-home" style="color:#333333;"></i></span>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="https://www.greatandhra.com/latest" class="menu-link" itemprop="url">
                        <span itemprop="name">latest</span>
                    </a>
                </li>

                <!-- Politics with Dropdown -->
                <li class="menu-item">
                    <a href="https://www.greatandhra.com/politics" class="menu-link" itemprop="url">
                        <span itemprop="name">politics</span>
                        <i class="fas fa-caret-down"></i>
                    </a>
                    <ul class="dropdown">
                        <li><a href="https://www.greatandhra.com/andhra-news" itemprop="url">andhra</a></li>
                        <li><a href="https://www.greatandhra.com/telangana-news" itemprop="url">telangana</a></li>
                        <li><a href="https://www.greatandhra.com/india-news" itemprop="url">india</a></li>
                    </ul>
                </li>

                <!-- Movies with Dropdown -->
                <li class="menu-item">
                    <a href="https://www.greatandhra.com/movies" class="menu-link" itemprop="url">
                        <span itemprop="name">movies</span>
                        <i class="fas fa-caret-down"></i>
                    </a>
                    <ul class="dropdown">
                        <li><a href="https://www.greatandhra.com/movies" itemprop="url">news</a></li>
                        <li><a href="https://www.greatandhra.com/moviegossip" itemprop="url">gossip</a></li>
                        <li><a href="https://www.greatandhra.com/boxoffice" itemprop="url">boxoffice</a></li>
                    </ul>
                </li>

                <li class="menu-item">
                    <a href="https://www.greatandhra.com/reviews" class="menu-link">reviews</a>
                </li>

                <li class="menu-item">
                    <a href="https://gallery.greatandhra.com/index.php" class="menu-link" itemprop="url">
                        <span itemprop="name">gallery</span>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="https://www.greatandhra.com/opinion" class="menu-link" itemprop="url">
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
                <a href="https://www.garudavega.com/" target="_blank"><img alt="GVega" border="0" height="40"
                        src="images/GVega_320x40_03122025_1.gif" width="330" /> </a>
            </div>
            <div class="_201223_2">
                <a href="http://www.sankaranethralayausa.org/" target="_blank"><img alt="Sankara Nethralaya" border="0"
                        height="40" src="images/Sankara_Nethralaya_320x40_04172023_1_2.gif" width="330" /> </a>
            </div>
            <div class="_201223_3">
                <a href="https://urthindia.com/" target="_blank"><img alt="TANA" border="0" height="40"
                        src="images/urth_spices.gif" width="300" /> </a>
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
                                <a href="https://www.learntek.org/masterprograms/" target="_blank"><img alt="Learntek"
                                        src="images/Learntek_320_03292026_1.gif" /></a>
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
                                    <li class="ga-unavailable"><p class="ga-unavailable-msg">Content temporarily unavailable</p></li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                            <!--related Articles end-->
                            </li>
                        </ul>
                    </div>
                    <div class="home_left_column">
                        <ul class="sortable-list ui-sortable">
                            <li class="sortable-item clear">
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
                                                <?php foreach ($ga_trending_articles as $ga_article): ?>
                                                <li> <a href="<?php echo ga_e(ga_inner_link($ga_article)); ?>"
                                                        title="<?php echo ga_e($ga_article['title'] ?? ''); ?>">
                                                        <strong> <?php echo ga_e($ga_article['title'] ?? ''); ?> </strong>
                                                    </a>
                                                </li>
                                                <?php endforeach; ?>
                                                <?php else: ?>
                                                <li class="ga-unavailable"><p class="ga-unavailable-msg">Content temporarily unavailable</p></li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                        <div class="tabcontent" id="latest" style="display:none;">
                                            <ul class="news_style">
                                                <?php if (!empty($ga_most_read_articles)): ?>
                                                <?php foreach ($ga_most_read_articles as $ga_article): ?>
                                                <li> <a href="<?php echo ga_e(ga_inner_link($ga_article)); ?>"
                                                        title="<?php echo ga_e($ga_article['title'] ?? ''); ?>">
                                                        <?php echo ga_e($ga_article['title'] ?? ''); ?> </a>
                                                </li>
                                                <?php endforeach; ?>
                                                <?php else: ?>
                                                <li class="ga-unavailable"><p class="ga-unavailable-msg">Content temporarily unavailable</p></li>
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
                                    <li>
                                        <a class="oneline-title"
                                            href="https://www.greatandhra.com/movies/news/hot-rx100-beauty-blasts-in-low-neck-blouse-153889"
                                            title="HOT: RX100 Beauty Blasts In Low Neck Blouse">
                                            HOT: RX100 Beauty Blasts In Low Neck Blouse </a>
                                    </li>
                                    <li>
                                        <a class="oneline-title"
                                            href="https://www.greatandhra.com/movies/news/rishab-shettys-immaturity-raises-serious-concerns-153886"
                                            title="Rishab Shetty's Immaturity Raises Serious Concerns">
                                            Rishab Shetty's Immaturity Raises Serious Concerns </a>
                                    </li>
                                    <li>
                                        <a class="oneline-title"
                                            href="https://www.greatandhra.com/movies/news/dhurandhar-action-on-the-roads-of-pakistan-153885"
                                            title="'Dhurandhar' Action On The Roads Of Pakistan">
                                            'Dhurandhar' Action On The Roads Of Pakistan </a>
                                    </li>
                                    <li>
                                        <a class="oneline-title"
                                            href="https://www.greatandhra.com/movies/news/actress-leases-out-bungalow-for-rs-414-crore-153880"
                                            title="Actress Leases Out Bungalow for Rs 4.14 Crore">
                                            Actress Leases Out Bungalow for Rs 4.14 Crore </a>
                                    </li>
                                    <li>
                                        <a class="oneline-title"
                                            href="https://www.greatandhra.com/movies/news/dhurandhar-2-changes-bollywoods-box-office-math-153877"
                                            title="Dhurandhar 2 Changes Bollywood's Box Office Math">
                                            Dhurandhar 2 Changes Bollywood's Box Office Math </a>
                                    </li>
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
                            <p style="/* float: left; */font-size: 11px;text-align: center;"> Advertisement</p>
                            <!-- <script type="text/javascript">
                                google_ad_client = "ca-pub-1239645388568087";
                                /* Mediumrectangle */
                                google_ad_slot = "0742396642";
                                google_ad_width = 300;
                                google_ad_height = 250;


                            </script>
                            <script src="assets/show_ads.js" type="text/javascript">
                            </script> -->
                            <img class="temp-class" src="images/16260515895400254651.jpg" />
                        </li>
                    </div>
                </ul>
            </div>
        </div>
        <!--great_andhra_main_body_container-->
        <div class="great_andhra_main_body_container ad-opinion">
            <div class="full_width_home editors_pick_full_width border-topbottom margin-top-bot">
                <p style="/* float: left; */font-size: 11px;text-align: center;"> Advertisement</p>
                <div style="text-align: center;">
                    <script async="" crossorigin="anonymous" src="js/adsbygoogle.js"></script>
                    <!-- Top 728x90 -->
                    <ins class="adsbygoogle" data-ad-client="ca-pub-1239645388568087" data-ad-slot="4304682596"
                        style="display:inline-block;width:728px;height:90px"></ins>
                    <script>

                        (adsbygoogle = window.adsbygoogle || []).push({});

                    </script>
                </div>
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
                            <li class="ga-unavailable"><p class="ga-unavailable-msg">Content temporarily unavailable</p></li>
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
                                            <li class="editors_pick_li clearfix">
                                                <a href="https://www.greatandhra.com/movies/reviews/ustaad-bhagat-singh-review-cliched-cop-153588"
                                                    title="'Ustaad Bhagat Singh' Review: Cliched Cop">
                                                    <div class="editors_pick">
                                                        <span class="editors_pick_cat">Review </span>
                                                        <span class="editors_pick_title">'Ustaad Bhagat Singh' Review:
                                                            Cliched Cop</span>
                                                        <span class="editors_pick_desc">Overall, 'Ustaad Bhagat Singh'
                                                            turns out to be a routine, mostly boring commercial
                                                            entertainer. </span>
                                                    </div>
                                                </a>
                                            </li>
                                            <li class="main-story editors_pick_li clearfix">
                                                <a href="https://www.greatandhra.com/movies/reviews/ustaad-bhagat-singh-review-cliched-cop-153588"
                                                    title="'Ustaad Bhagat Singh' Review: Cliched Cop">
                                                    <img alt="'Ustaad Bhagat Singh' Review: Cliched Cop" height="200"
                                                        src="images/UstaadBhagatSingh11773903556.jpg" width="330" />
                                                </a>
                                            </li>
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
                                    <a href="https://www.greatandhra.com/movies">
                                        <div class="header"> Movie News <span class="more_arrow"></span> </div>
                                    </a>
                                    <div class="content">
                                        <ul class="news_style news_section_ul" style="padding-top:3px;">
                                            <li class="main-story clearfix">
                                                <a href="https://www.greatandhra.com/movies/news/teaser-ranbirs-rama-avatar-is-grand-glorious-goosebumps-153916"
                                                    title="Teaser: Ranbir's Rama Avatar Is Grand, Glorious, Goosebumps">
                                                    <img alt="Teaser: Ranbir's Rama Avatar Is Grand, Glorious, Goosebumps"
                                                        height="200" src="images/ramayana41775105638.jpg" width="330">
                                                    </img></a>
                                            </li>
                                            <li> <a href="https://www.greatandhra.com/movies/news/teaser-ranbirs-rama-avatar-is-grand-glorious-goosebumps-153916"
                                                    title="Teaser: Ranbir's Rama Avatar Is Grand, Glorious, Goosebumps">



                                                    Teaser: Ranbir's Rama Avatar Is Grand, Glorious, Goosebumps



                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/movies/news/sharwas-biker-first-of-its-kind-in-tollywood-153915"
                                                    title="Sharwa's Biker: First-Of-Its-Kind In Tollywood">



                                                    Sharwa's Biker: First-Of-Its-Kind In Tollywood



                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/movies/news/first-quarter-report-one-mega-hit-one-mega-shock-153914"
                                                    title="First Quarter Report: One Mega Hit... One Mega Shock!">



                                                    First Quarter Report: One Mega Hit... One Mega Shock!



                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/movies/news/prabhass-films-may-see-release-date-reshuffle-153912"
                                                    title="Prabhas's Films May See Release Date Reshuffle">



                                                    Prabhas's Films May See Release Date Reshuffle



                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/movies/news/malavika-reveals-sharwanand-is-an-introvert-like-her-153911"
                                                    title="Malavika Reveals Sharwanand is an Introvert like Her">



                                                    Malavika Reveals Sharwanand is an Introvert like Her



                                                </a> </li>
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
                                    <a href="https://www.greatandhra.com/moviegossip">
                                        <div class="header"> Movie Gossip <span class="more_arrow"></span> </div>
                                    </a>
                                    <div class="content">
                                        <ul class="news_style news_section_ul" style="padding-top:3px;">
                                            <li class="main-story clearfix">
                                                <a href="https://www.greatandhra.com/movies/gossip/buzz-big-director-cost-shock-stuns-producer-153840"
                                                    title="Buzz: Big Director Cost Shock Stuns Producer">
                                                    <img alt="Buzz: Big Director Cost Shock Stuns Producer" height="200"
                                                        src="images/armurugadoss1774855134.jpg" width="330" />
                                                </a>
                                            </li>
                                            <li> <a href="https://www.greatandhra.com/movies/gossip/buzz-big-director-cost-shock-stuns-producer-153840"
                                                    title="Buzz: Big Director Cost Shock Stuns Producer">



                                                    Buzz: Big Director Cost Shock Stuns Producer



                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/movies/gossip/buzz-pooja-name-free-for-every-project-153828"
                                                    title="Buzz: Pooja Name... Free for Every Project?">



                                                    Buzz: Pooja Name... Free for Every Project?



                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/movies/gossip/buzz-rs-15-cr-demand-from-small-heroine-153821"
                                                    title="Buzz: Rs 1.5 Cr Demand from Small Heroine?">



                                                    Buzz: Rs 1.5 Cr Demand from Small Heroine?



                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/movies/gossip/young-heroes-background-check-on-new-producers-153816"
                                                    title="Young Heroes' Background Check On New Producers">



                                                    Young Heroes' Background Check On New Producers



                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/movies/gossip/rajamouli-splits-varanasi-in-two-153793"
                                                    title="Rajamouli Splits Varanasi in Two?">



                                                    Rajamouli Splits Varanasi in Two?



                                                </a> </li>
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
                                    <a href="https://www.greatandhra.com/andhra-news">
                                        <div class="header"> Andhra News <span class="more_arrow"></span> </div>
                                    </a>
                                    <div class="content">
                                        <ul class="news_style news_section_ul" style="padding-top:3px;">
                                            <li class="main-story clearfix">
                                                <a href="https://www.greatandhra.com/politics/andhra-news/naidu-wants-statewide-amaravati-celebrations-153917"
                                                    title="Naidu Wants Statewide Amaravati Celebrations">
                                                    <img alt="Naidu Wants Statewide Amaravati Celebrations" height="200"
                                                        src="images/babu_new251775111776.jpg" width="330" />
                                                </a>
                                            </li>
                                            <li> <a href="https://www.greatandhra.com/politics/andhra-news/naidu-wants-statewide-amaravati-celebrations-153917"
                                                    title="Naidu Wants Statewide Amaravati Celebrations">



                                                    Naidu Wants Statewide Amaravati Celebrations



                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/politics/andhra-news/cadre-upset-over-jagans-mavigun-proposal-153908"
                                                    title="Cadre Upset Over Jagan's MAVIGUN Proposal">



                                                    Cadre Upset Over Jagan's MAVIGUN Proposal



                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/politics/andhra-news/naidu-helpless-as-tdp-mla-behaves-rudely-with-sp-153900"
                                                    title="Naidu helpless, as TDP MLA behaves rudely with SP">



                                                    Naidu helpless, as TDP MLA behaves rudely with SP



                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/politics/andhra-news/ysrcp-opposes-amaravati-bill-in-ls-stages-walk-out-153894"
                                                    title="YSRCP opposes Amaravati bill in LS, stages walk out">



                                                    YSRCP opposes Amaravati bill in LS, stages walk out



                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/politics/andhra-news/hydraa-demolishes-illegal-properties-of-mim-153892"
                                                    title="HYDRAA demolishes illegal properties of MIM">



                                                    HYDRAA demolishes illegal properties of MIM



                                                </a> </li>
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
                                    <a href="https://www.greatandhra.com/telangana-news">
                                        <div class="header"> Telangana News <span class="more_arrow"></span> </div>
                                    </a>
                                    <div class="content">
                                        <ul class="news_style news_section_ul" style="padding-top:3px;">
                                            <li class="main-story clearfix">
                                                <a href="https://www.greatandhra.com/politics/telangana-news/real-estate-rupee-fall-hits-hyderabad-market-153910"
                                                    title="Real Estate: Rupee Fall Hits Hyderabad Market">
                                                    <img alt="Real Estate: Rupee Fall Hits Hyderabad Market"
                                                        height="200" src="images/dollar-rupee11775089475.jpg"
                                                        width="330" />
                                                </a>
                                            </li>
                                            <li> <a href="https://www.greatandhra.com/politics/telangana-news/real-estate-rupee-fall-hits-hyderabad-market-153910"
                                                    title="Real Estate: Rupee Fall Hits Hyderabad Market">



                                                    Real Estate: Rupee Fall Hits Hyderabad Market



                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/politics/telangana-news/is-vijayashanti-in-congress-or-opposition-153909"
                                                    title="Is Vijayashanti in Congress or opposition?">



                                                    Is Vijayashanti in Congress or opposition?



                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/politics/telangana-news/reddys-alleged-unruly-behaviour-referred-to-ethics-committee-153826"
                                                    title="Reddy's alleged unruly behaviour referred to Ethics Committee">



                                                    Reddy's alleged unruly behaviour referred to Ethics Committee



                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/politics/telangana-news/hyd-techie-in-us-duped-of-rs-103-cr-in-casino-scam-153784"
                                                    title="Hyd Techie in US Duped of Rs 10.3 Cr in Casino Scam">



                                                    Hyd Techie in US Duped of Rs 10.3 Cr in Casino Scam



                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/politics/telangana-news/sit-to-attach-rs-70-crore-properties-of-raj-kesireddy-153769"
                                                    title="SIT to attach Rs 70 crore properties of Raj Kesireddy">



                                                    SIT to attach Rs 70 crore properties of Raj Kesireddy



                                                </a> </li>
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
                                    <a href="https://www.greatandhra.com/politics">
                                        <div class="header"> Gossip <span class="more_arrow"></span> </div>
                                    </a>
                                    <div class="content">
                                        <ul class="news_style news_section_ul" style="padding-top:3px;">
                                            <li class="main-story clearfix">
                                                <a href="https://www.greatandhra.com/politics/gossip/fact-sheet-mass-layoffs-shake-telugu-media-153903"
                                                    title="Fact Sheet: Mass Layoffs Shake Telugu Media">
                                                    <img alt="Fact Sheet: Mass Layoffs Shake Telugu Media" height="200"
                                                        src="images/bigtv11775055984.jpg" width="330" />
                                                </a>
                                            </li>
                                            <li> <a href="https://www.greatandhra.com/politics/gossip/fact-sheet-mass-layoffs-shake-telugu-media-153903"
                                                    title="Fact Sheet: Mass Layoffs Shake Telugu Media">



                                                    Fact Sheet: Mass Layoffs Shake Telugu Media



                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/politics/gossip/naidu-planning-to-drop-b-r-naidu-as-ttd-chief-153866"
                                                    title="Naidu Planning to Drop B R Naidu as TTD Chief?">



                                                    Naidu Planning to Drop B R Naidu as TTD Chief?



                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/politics/gossip/jubilee-hills-coop-society-polls-turn-a-media-war-153832"
                                                    title="Jubilee Hills Coop Society polls turn a media war!">



                                                    Jubilee Hills Coop Society polls turn a media war!



                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/politics/gossip/jagan-keeps-liquor-scam-accused-at-bay-153789"
                                                    title="Jagan Keeps Liquor Scam Accused At Bay">



                                                    Jagan Keeps Liquor Scam Accused At Bay



                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/politics/gossip/the-unlucky-ipl-story-of-mr-reddy-153762"
                                                    title="The Unlucky IPL Story Of Mr Reddy">



                                                    The Unlucky IPL Story Of Mr Reddy



                                                </a> </li>
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
                                    <a href="https://www.greatandhra.com/reviews">
                                        <div class="header"> Reviews <span class="more_arrow"></span> </div>
                                    </a>
                                    <div class="content">
                                        <ul class="news_style news_section_ul" style="padding-top:3px;">
                                            <li class="main-story clearfix">
                                                <a href="https://www.greatandhra.com/movies/reviews/youth-review-works-for-teenagers-only-153773"
                                                    title="Youth Review: Works for Teenagers Only">
                                                    <img alt="Youth Review: Works for Teenagers Only" height="200"
                                                        src="images/youth41774602072.jpg" width="330" />
                                                </a>
                                            </li>
                                            <li> <a href="https://www.greatandhra.com/movies/reviews/youth-review-works-for-teenagers-only-153773"
                                                    title="Youth Review: Works for Teenagers Only">



                                                    Youth Review: Works for Teenagers Only

                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/movies/reviews/band-melam-review-amateurish-stale-and-dim-153748"
                                                    title="'Band Melam' Review: Amateurish, Stale and Dim">



                                                    'Band Melam' Review: Amateurish, Stale and Dim

                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/movies/reviews/ustaad-bhagat-singh-review-cliched-cop-153588"
                                                    title="'Ustaad Bhagat Singh' Review: Cliched Cop">



                                                    'Ustaad Bhagat Singh' Review: Cliched Cop

                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/movies/reviews/dhurandhar-the-revenge-review-epic-spy-saga-153583"
                                                    title="'Dhurandhar The Revenge' Review: Epic Spy Saga">



                                                    'Dhurandhar The Revenge' Review: Epic Spy Saga

                                                </a> </li>
                                            <li> <a href="https://www.greatandhra.com/movies/reviews/first-report-ustaad-bhagat-singh-lacks-freshness-153581"
                                                    title="First Report: Ustaad Bhagat Singh Lacks Freshness">



                                                    First Report: Ustaad Bhagat Singh Lacks Freshness

                                                </a> </li>
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
                                    <li><a href="https://movies.indianclicks.com/biker-movie" target="_blank">Biker </a>
                                    </li>
                                    <li><a href="https://movies.indianclicks.com/youth-telugu-movie"
                                            target="_blank">Youth </a></li>
                                    <li><a href="https://movies.indianclicks.com/couple-friendly-movie"
                                            target="_blank">Couple Friendly</a></li>
                                </ul>
                            </div>
                        </div>
                    </li>
                    <li class="sortable-item clear">
                        <div class="sortable-item_style_13">
                            <div class="header"> Featured </div>
                            <div class="content">
                                <ul class="news_style">
                                    <li><a
                                            href="https://www.greatandhra.com/articles/news/azure-ai-engineer-certification-course-apr-11-153899">Azure
                                            AI Engineer Certification Course, Apr 11</a></li>
                                    <li><a
                                            href="https://www.greatandhra.com/articles/news/why-used-mahindra-suvs-rule-off-the-road-153890">Why
                                            Used Mahindra SUVs Rule Off the Road</a></li>
                                    <li><a
                                            href="https://www.greatandhra.com/articles/news/snusa-columbus-oh-chapters-music-dance-153807">SNUSA
                                            Columbus OH Chapter's Music &amp; Dance</a></li>
                                    <li><a
                                            href="https://www.greatandhra.com/articles/news/watch-ipl-2026-on-cricbuzz-channel-via-yupptv-153787">Watch
                                            IPL 2026 on Cricbuzz channel via YuppTV</a></li>
                                    <li><a
                                            href="https://www.greatandhra.com/articles/news/live-demo-on-full-stack-ai-llms-rag-ai-agents-153776">LIVE
                                            Demo on Full Stack AI LLMs, RAG, AI Agent's</a></li>
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
                                <a href="https://www.greatandhra.com/topic/bheemla-nayak">Bheemla Nayak</a>
                                <a href="https://www.greatandhra.com/topic/aadavallu-meeku-johaarlu">Aadavallu Meeku
                                    Johaarlu</a>
                                <a href="https://www.greatandhra.com/topic/trivikram-srinivas">Trivikram Srinivas</a>
                                <a href="https://www.greatandhra.com/topic/radhe-shyam">Radhe Shyam</a>
                                <a href="https://www.greatandhra.com/topic/pawan-kalyan">Pawan Kalyan</a>
                                <a href="https://www.greatandhra.com/topic/chiranjeevi">Chiranjeevi</a>
                                <a href="https://www.greatandhra.com/topic/sai-dharam-tej">Sai Dharam Tej</a>
                                <a href="https://www.greatandhra.com/topic/samantha">Samantha</a>
                                <a href="https://www.greatandhra.com/topic/rashmika">Rashmika</a>
                                <a href="https://www.greatandhra.com/topic/sharwanand">Sharwanand</a>
                                <a href="https://www.greatandhra.com/topic/maha-samudram">Maha Samudram</a>
                                <a href="https://www.greatandhra.com/topic/cm-jagan">CM Jagan</a>
                                <a href="https://www.greatandhra.com/topic/balakrishna">Balakrishna</a>
                                <a href="https://www.greatandhra.com/topic/cm-kcr">CM KCR</a>
                                <a href="https://www.greatandhra.com/topic/andhra-pradesh">Andhra Pradesh</a>
                                <a href="https://www.greatandhra.com/topic/telangana">Telangana</a>
                                <a href="https://www.greatandhra.com/topic/pooja-hegde">Pooja Hegde</a>
                                <a href="https://www.greatandhra.com/topic/acharya">Acharya</a>
                                <a href="https://www.greatandhra.com/topic/rrr">RRR</a>
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
                            <div id="vuukle-ad-13" style="min-width: 300px; min-height: 250px;"></div>
                        </li>
                    </div>
                    <li class="sortable-item clear">
                        <div class="sortable-item_style_13">
                            <div class="header"> Article </div>
                            <div class="content">
                                <ul class="news_style">
                                    <?php if (!empty($ga_article_section_articles)): ?>
                                    <?php foreach ($ga_article_section_articles as $ga_article): ?>
                                    <li><a href="<?php echo ga_e(ga_inner_link($ga_article)); ?>"><?php echo ga_e($ga_article['title'] ?? ''); ?></a></li>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <li class="ga-unavailable"><p class="ga-unavailable-msg">Content temporarily unavailable</p></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </li>
                    <!--<li class="sortable-item">

						<div id="vuukle-ad-15" style="min-width: 300px; min-height: 250px;" class="display-inline-block margin-top-bot">                    

					</li>-->
                    <li class="sortable-item">
                        <div class="sortable-item_style_14">
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
                                    <li class="ga-unavailable"><p class="ga-unavailable-msg">Content temporarily unavailable</p></li>
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
    <script>var VUUKLE_CONFIG = { apiKey: '2b166297-6273-48a9-82e9-696327c67418', articleId: '1', comments: { enabled: false }, emotes: { "enabled": false }, powerbar: { "enabled": false }, ads: { noDefaults: true } }; (function () { var d = document, s = d.createElement('script'); s.async = true; s.src = 'https://cdn.vuukle.com/platform.js'; (d.head || d.body).appendChild(s); })();</script>
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