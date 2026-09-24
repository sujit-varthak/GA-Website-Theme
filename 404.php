<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/api-client.php';

http_response_code(404);

// Ad zones on this page (skyscraper/top-banner, same chrome as list-page.php minus the
// interstitial/bottom-sticky zones - an error page shouldn't add an extra ad on top of telling
// a visitor they landed somewhere that doesn't exist) all resolve client-side now, via
// js/ga-ad-loader.js - nothing here reads a prefetched ad-zone cache entry server-side, so
// there's no ga_prefetch_page() call to make on this page anymore.
?>
<html lang="en">

<head>
    <!-- Same root-anchoring as list-page.php/inner-page.php - this page can be reached from
         any depth (Apache's ErrorDocument, or a direct /404.php hit), so every relative asset
         path below needs to resolve against site root, not wherever the request path was. -->
    <base href="/">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, follow">
    <title>Page Not Found - Greatandhra</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,400italic,700,700italic|Roboto+Condensed:400,700"
        rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&amp;display=swap"
        rel="stylesheet">
    <link href="./css/footer.css?v=<?php echo ga_asset_version('css/footer.css'); ?>" rel="stylesheet">
    <link href="css/site-ads.css?v=<?php echo ga_asset_version('css/site-ads.css'); ?>" rel="stylesheet">
    <link href="css/header-mob.css?v=<?php echo ga_asset_version('css/header-mob.css'); ?>" rel="stylesheet">
    <script src="js/drawer.js?v=<?php echo ga_asset_version('js/drawer.js'); ?>"> </script>
    <?php // defer, not the bottom-of-body placement this used to have - starts downloading
          // immediately, in parallel with the rest of this page's HTML, instead of only once
          // the parser reaches the very end of it. Execution still waits for the full DOM
          // (defer's own guarantee), so every .ga-ad-slot below is already there when it runs -
          // same behavior, just no longer paying for the download as a trailing round trip
          // after everything else has already rendered. ?>
    <script src="js/ga-ad-loader.js?v=<?php echo ga_asset_version('js/ga-ad-loader.js'); ?>" defer></script>

    <link href="./css/main-list-page.css?v=<?php echo ga_asset_version('css/main-list-page.css'); ?>" rel="stylesheet" />
    <link href="./css/list-page-mobile-responsive.css?v=<?php echo ga_asset_version('css/list-page-mobile-responsive.css'); ?>" rel="stylesheet" />

    <link type="text/css" href="//fonts.googleapis.com/css?family=Google%20Sans%3A400" rel="stylesheet">
    <link type="text/css" href="//fonts.googleapis.com/css?family=Google%20Sans%3A700" rel="stylesheet">

    <script type="text/javascript" src="assets/jquery.min.1.8.2.js"></script>

    <style>
        .ga-404-panel {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            width: 100%;
            padding: 60px 20px;
            background: #f2f2f2;
        }
        .ga-404-code {
            font-family: 'Roboto Condensed', sans-serif;
            font-size: 96px;
            font-weight: 700;
            color: #326891;
            line-height: 1;
            margin: 0;
        }
        .ga-404-title {
            font-size: 22px;
            font-weight: 700;
            color: #222;
            margin: 12px 0 6px;
        }
        .ga-404-msg {
            font-size: 15px;
            color: #666;
            max-width: 480px;
            margin: 0 0 24px;
        }
        .ga-404-home-link {
            display: inline-block;
            background: #326891;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            padding: 10px 28px;
            border-radius: 4px;
            margin-bottom: 28px;
        }
        .ga-404-home-link:hover {
            background: #244d6e;
        }
        .ga-404-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
        }
        .ga-404-links a {
            font-size: 13px;
            color: #326891;
            text-decoration: none;
            border: 1px solid #ccc;
            border-radius: 20px;
            padding: 6px 16px;
            background: #fff;
        }
        .ga-404-links a:hover {
            background: #326891;
            color: #fff;
            border-color: #326891;
        }
        @media (max-width: 767px) {
            .ga-404-code { font-size: 64px; }
            .ga-404-panel { padding: 40px 16px; }
        }
    </style>
</head>

<body class="home_bg">

    <div class="local_great" style="position:fixed; width:80px; float:left;">
        <div class="source-image-left" style="float:left">
            <?php ga_render_ad('LISTPAGE_SIDEBAR_LEFT'); ?>
        </div>
    </div>
    <div class="local_great" style="position:fixed; width:120px; right:0;">
        <div class="source-image-right" style="float:right">
            <?php ga_render_ad('LISTPAGE_SIDEBAR_RIGHT'); ?>
        </div>
    </div>

    <div class="great_andhra_movie_body">
        <div class="great_andhra_movie_inner_body">
            <div class="great_andhra_logo_panel">
                <a href="/" class="logo">
                    <img src="./images/great_andhra.gif" title="Greatandhra website logo" alt="Greatandhra logo">
                </a>
                <div class="AdinHedare">
                    <?php ga_render_ad('LISTPAGE_TOP_BANNER'); ?>
                </div>
            </div>

            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

            <script type="text/javascript">
                $(document).ready(function (e) {
                    $('.search_img').click(function () {
                        $('#search_box_new').slideToggle('slow');
                    });
                });
            </script>
            <script>
                $(document).ready(function () {
                    $(".dropdown").click(function () {
                        $(".dropdown-content").toggle();
                    });
                });
            </script>

            <!-- new nav bar -->
            <nav class="ga-nav" itemscope itemtype="https://www.schema.org/SiteNavigationElement">
                <ul class="menu">
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

                    <li class="menu-item">
                        <a href="http://epaper.greatandhra.com/" class="menu-link">
                            <img alt="greatandhra print" src="./images/ga-print.png" class="nav-print-img"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        </a>
                    </li>

                    <li class="menu-item">
                        <a href="https://telugu.greatandhra.com/" class="menu-link" title="తెలుగు" itemprop="url">
                            <span itemprop="name" style="font-size: 14px;">తెలుగు</span>
                        </a>
                    </li>

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

            <div class="mobile-overlay" id="mobileOverlay"></div>

            <div class="_201223_">
                <?php ga_render_ad('LISTPAGE_MOBILE_BANNER'); ?>
            </div>
        </div>

            <div class="great_andhra_main_body_container">
                <div class="ga-404-panel">
                    <p class="ga-404-code">404</p>
                    <h1 class="ga-404-title">This page doesn't exist</h1>
                    <p class="ga-404-msg">The page you're looking for may have been moved, renamed, or never existed. Try one of the links below, or head back to the homepage.</p>
                    <a href="/" class="ga-404-home-link">Back to Home</a>
                    <div class="ga-404-links">
                        <a href="<?php echo ga_e(ga_nav_category_link('latest-news', 'Latest News')); ?>">Latest News</a>
                        <a href="<?php echo ga_e(ga_nav_category_link('politics', 'Politics', true)); ?>">Politics</a>
                        <a href="<?php echo ga_e(ga_nav_category_link('movies', 'Movies', true)); ?>">Movies</a>
                        <a href="box-office">Box Office</a>
                        <a href="<?php echo ga_e(ga_nav_category_link('movie-gossip', 'Movie Gossip')); ?>">Movie Gossip</a>
                    </div>
                </div>
            </div>

            <div class="new_great_andhra_main_footer">
                <div class="footer-container">

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

                    <div class="footer-copyright">
                        &copy; 2026 GreatAndhra | All Rights Reserved
                    </div>

                </div>
            </div>

            <div class="footer-bottom-spacer"></div>

        </div>
    </div>

    <?php // Positions .source-image-left/.source-image-right relative to the page's centered
          // 990px content column - same script every other page on the site uses for this. ?>
    <script src="js/great_andhra_view_js_160_1.js?v=<?php echo ga_asset_version('js/great_andhra_view_js_160_1.js'); ?>" type="text/javascript"></script>
</body>
</html>
