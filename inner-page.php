<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/api-client.php';
require_once __DIR__ . '/inc/helpers.php';

$ga_id = isset($_GET['id']) ? trim((string) $_GET['id']) : '';
$ga_result = $ga_id !== '' ? ga_fetch_article_by_id($ga_id) : ['status' => 'not_found', 'article' => null];
$ga_status = $ga_result['status'];
$ga_article = $ga_result['article'];

if ($ga_status === 'found' && $ga_article && !empty($ga_article['id'])) {
    ga_ping_view((string) $ga_article['id']);
}

$ga_tag_list = $ga_article ? ga_tag_names($ga_article) : [];
$ga_img_src = $ga_article ? (ga_image($ga_article, ['src' => GA_ARTICLE_FALLBACK_IMAGE['src'], 'width' => null, 'height' => null])['src']) : GA_ARTICLE_FALLBACK_IMAGE['src'];

$ga_meta_title = $ga_article ? (($ga_article['seoTitle'] ?? null) ?: ($ga_article['title'] ?? '')) : 'Article Not Found';
$ga_meta_desc = $ga_article ? (($ga_article['seoDescription'] ?? null) ?: ($ga_article['excerpt'] ?? '')) : '';
$ga_category_name = $ga_article['category']['name'] ?? '';
?>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" version="XHTML+RDFa 1.0" dir="ltr"
    xmlns:og="http://ogp.me/ns#" xmlns:article="http://ogp.me/ns/article#" xmlns:book="http://ogp.me/ns/book#"
    xmlns:profile="http://ogp.me/ns/profile#" xmlns:video="http://ogp.me/ns/video#"
    xmlns:product="http://ogp.me/ns/product#" xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:dc="http://purl.org/dc/terms/" xmlns:foaf="http://xmlns.com/foaf/0.1/"
    xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#" xmlns:sioc="http://rdfs.org/sioc/ns#"
    xmlns:sioct="http://rdfs.org/sioc/types#" xmlns:skos="http://www.w3.org/2004/02/skos/core#"
    xmlns:xsd="http://www.w3.org/2001/XMLSchema#">


<head profile="http://www.w3.org/1999/xhtml/vocab">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <title><?php echo ga_e($ga_meta_title); ?> | greatandhra.com</title>
    <meta name="keywords" content="<?php echo ga_e(implode(', ', $ga_tag_list)); ?>">
    <meta name="news_keywords" content="<?php echo ga_e(implode(', ', $ga_tag_list)); ?>">

    <meta name="description" content="<?php echo ga_e($ga_meta_desc); ?>">

    <meta property="fb:app_id" content="588741781200880">
    <meta property="og:title" content="<?php echo ga_e($ga_meta_title); ?>">
    <meta property="og:type" content="article">
    <meta property="og:image" content="<?php echo ga_e($ga_img_src); ?>">
    <meta property="og:url" content="<?php echo ga_e($ga_article ? ga_inner_link($ga_article) : ''); ?>">
    <meta property="og:site_name" content="greatandhra.com">
    <meta property="og:description" content="<?php echo ga_e($ga_meta_desc); ?>">
    <link rel="shortcut icon" type="image/ico" href="https://www.greatandhra.com/favicon.ico">
    <link rel="shortcut icon" type="image/png" href="https://www.greatandhra.com/favicon.png">
    <link rel="icon" type="image/ico" href="https://www.greatandhra.com/favicon.ico">
    <link rel="icon" type="image/png" href="https://www.greatandhra.com/favicon.png">
    <meta name="subsection" content="<?php echo ga_e($ga_category_name); ?>">
    <meta itemprop="name" content="<?php echo ga_e($ga_meta_title); ?>">
    <meta itemprop="description" content="<?php echo ga_e($ga_meta_desc); ?>">
    <meta itemprop="image" content="<?php echo ga_e($ga_img_src); ?>">
    <meta itemprop="publisher" content="greatandhra">
  
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <meta name="dcterms.identifier" content="<?php echo ga_e($ga_article ? ga_inner_link($ga_article) : ''); ?>">

    <link href="css/footer.css" rel="stylesheet" type="text/css">
    <link href="css/image_preview.css" rel="stylesheet" type="text/css">
    <link href="css/inner-page-main.css" rel="stylesheet">
    <link href="css/inner-page-mobile-responsive.css" rel="stylesheet">
    <link href="css/header-mob.css" rel="stylesheet">
    <script src="js/drawer.js"></script>
 
    
</head>

<body>
    <div id="fb-root" class=" fb_reset">
        <div style="position: absolute; top: -10000px; width: 0px; height: 0px;">
            <div></div>
        </div>
    </div>
    <script>(function (d, s, id) {
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) return;
            js = d.createElement(s); js.id = id;
            js.src = "//connect.facebook.net/en_GB/all.js#xfbml=1";
            fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));</script>
    <script>
        $(document).ready(function () {

            //Check to see if the window is top if not then display button
            $(window).scroll(function () {
                if ($(this).scrollTop() > 100) {
                    $('.scrollToTop').fadeIn();
                } else {
                    $('.scrollToTop').fadeOut();
                }
            });

            //Click event to scroll to top
            $('.scrollToTop').click(function () {
                $('html, body').animate({ scrollTop: 0 }, 400);
                return false;
            });

            $(window).scroll(function () {
                if ($(this).scrollTop() > 100) {
                    $('.scrollToHome').fadeIn();
                } else {
                    $('.scrollToHome').fadeOut();
                }
            });

            //Click event to scroll to top
            $('.scrollToHome').click(function () {
                window.location = 'http://www.greatandhra.com/';
            });

        });
    </script>
    <link href="./css/css" rel="stylesheet">

    <!-- Global site tag (gtag.js) - Google Analytics -->
    <!-- <script async="" src="https://www.googletagmanager.com/gtag/js?id=G-PX1LPBMH02"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-PX1LPBMH02');
</script> -->

    <!-- <div style="position:fixed; width:100%; float:left; width:80px;" class="local_great">
        <div class="source-image-left" style="float: left; left: 69px; display: block;">
            

            <a href="https://www.msnrealty.com/new-lp/GreatAndhra"><img src="images/msn_new_160_2.jpg" width="160"
                    alt="MSN Realty"> </a>

           

            <div class="close_button">[X] Close</div>
        </div>
    </div>

    <div style="position:fixed; width:120px; right:0;" class="local_great">
        <div class="source-image-right" style="float: right; right: 69px; display: block;">
            

            <a href="https://www.msnrealty.com/new-lp/GreatAndhra"><img src="images/msn_new_160_2.jpg" width="160"
                    alt="MSN Realty"> </a>

           
            <div class="close_button">[X] Close</div>
        </div>
    </div> -->

    <div class="local_great" style="position:fixed; width:100%; float:left; width:80px;">
        <div class="source-image-left" style="float:left">
            <!--<a href="https://www.indianclicks.com/clicks.php?url=https://www.giridhariconstructions.com/prospera-county.php&sid=GALeft"><img src="images/general/Giridhari_Constructions_160_07222023_1_1.jpg" width="160" alt="Andhra Pradesh Number One" /> </a> 	-->
            <a href="https://www.msnrealty.com/new-lp/GreatAndhra"><img alt="MSN Realty" src="images/msn_new_160_2.jpg"
                    width="160" /> </a>
            <!--<div id="vuukle-ad-3"></div>-->
            <!-- LHS AD 1 ==> Ends -->
        </div>
    </div>
    <div class="local_great" style="position:fixed; width:120px; right:0;">
        <div class="source-image-right" style="float:right">
            <!-- RHS AD 1 ==> Starts -->
            <!--<a href="https://www.indianclicks.com/clicks.php?url=https://www.giridhariconstructions.com/prospera-county.php&sid=GARight"><img src="images/general/Giridhari_Constructions_160_07222023_1_1.jpg" width="160" alt="Andhra Pradesh Number One" /> </a> -->
            <a href="https://www.msnrealty.com/new-lp/GreatAndhra"><img alt="MSN Realty" src="images/msn_new_160_2.jpg"
                    width="160" /> </a>
            <!--<div id="vuukle-ad-4"></div>-->
            <!-- RHS AD 1 ==> Ends -->
        </div>
    </div>

    <!--great_andhra_body-->
    <div class="great_andhra_movie_body">
        <!--great_andhra_inner_body-->
        <div class="great_andhra_movie_inner_body">
            <!--great_andhra_logo_panel-->
            <div class="great_andhra_logo_panel">
                <a href="https://www.greatandhra.com/" class="logo">
                    <img src="images/great_andhra.gif" title="Greatandhra website logo" alt="Greatandhra logo">
                </a>
                <div class="AdinHedare">
                    <span class="a-label-header"
                        style="font-size: 10px;text-align: center;display: block;">Advertisement</span>
                    <a href="https://www.msnrealty.com/new-lp/GreatAndhra" target="_blank"><img border="0"
                            src="images/msn_728.jpg" width="728" height="90" alt="MSN Reality"> </a>
                    <!--<div id="vuukle-ad-6"></div>-->
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


            <!---Search button-->
            <script type="text/javascript" src="./js/jquery.min.1.8.2.js"></script>
            <script type="text/javascript">
                $(document).ready(function (e) {
                    $('.search_img').click(function () {
                        $('#search_box_new').slideToggle('slow');
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
                        <a href="https://www.youtube.com/channel/UCoarMz-cpxAnBy8tszp35wA" target="_blank"
                            title="youtube">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <!--great_andhra_main_menu_panel-->

            <!--great_andhra_main_menu_white_gap-->
            <div class="great_andhra_main_body_container">
                <!--two_column-->
                <div class="two_column" style="overflow-anchor: auto;">

                    <?php if ($ga_article): ?>
                    <script type="application/ld+json">{
						"@context": "http://schema.org",
						"@type": "BreadcrumbList",
						"itemListElement": [
							{
								"@type": "ListItem",
								"position": 1,
								"item": {
									"@type": "WebSite",
									"@id": "index.php",
									"name": "Home"
								}
							},
							{
								"@type": "ListItem",
								"position": 2,
									"item": {
									"@type": "WebPage",
									"@id": "<?php echo ga_e(ga_inner_link($ga_article)); ?>",
									"name": "<?php echo ga_e($ga_category_name); ?>"
								}
							}
							,{
								"@type": "ListItem",
								"position": 3,
									"item": {
									"@type": "WebPage",
									"@id": "<?php echo ga_e(ga_inner_link($ga_article)); ?>",
									"name": "<?php echo ga_e($ga_meta_title); ?>"
								}
							}
						]
					}
				</script>
                    <div class="breade_crumb"> <a href="index.php" title="Go to Home">Home</a>
                        <span><?php echo ga_e($ga_category_name); ?></span>
                    </div>
                    <?php endif; ?>
                    <!--breade_crumb-->
                    <!--page_news-->
                    <div class="page_news">
                        <?php if ($ga_status === 'found' && $ga_article): ?>
                        <div class="header color_15">
                            <h1><?php echo ga_e($ga_article['title'] ?? ''); ?></h1>
                        </div>

                        <div class="byline">
                            <span class="pub_name">By <?php echo ga_e($ga_article['author']['name'] ?? 'GreatAndhra'); ?> On </span>
                            <?php echo ga_e(ga_format_date($ga_article['publishedAt'] ?? null, 'F d , Y')); ?>
                            <?php $ga_updated = ga_format_date($ga_article['updatedAt'] ?? null, 'H:i'); ?>
                            <?php if ($ga_updated !== ''): ?>| UPDATED <?php echo ga_e($ga_updated); ?> IST<?php endif; ?>
                        </div>

                        <div class="add_place_650X60">
                            <img class="temp-ads" alt="addbyme" src="images/650-60.jpg" class="nav-print-img">
                        </div>

                        <div class="content">
                            <div class="unselectable">

                                <div class="img_plc">
                                    <div>
                                        <img border="0" src="<?php echo ga_e($ga_img_src); ?>" align="absmiddle"
                                            alt="<?php echo ga_e($ga_article['title'] ?? ''); ?>">
                                    </div>
                                </div>

                                <?php echo $ga_article['body'] ?? ''; ?>

                                <div class="vuukle-powerbar" style="min-height: 50px;"></div>

                            </div><!--unselect end-->
                        </div><!--content end-->
                        <?php elseif ($ga_status === 'not_found'): ?>
                        <div class="header color_15">
                            <h1>Article Not Found</h1>
                        </div>
                        <div class="content">
                            <div class="unselectable ga-unavailable" style="min-height: 150px;">
                                <p class="ga-unavailable-msg">The article you're looking for doesn't exist or may have
                                    been removed. <a href="index.php">Go back home</a>.</p>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="header color_15">
                            <h1>Content Temporarily Unavailable</h1>
                        </div>
                        <div class="content">
                            <div class="unselectable ga-unavailable" style="min-height: 150px;">
                                <p class="ga-unavailable-msg">We couldn't load this article right now. Please try
                                    again shortly.</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="local_place_650X60">
                            <div id="vuukle-ad-13" style="min-width: 320px; min-height: 260px;"></div>

                        </div>

                        <div class="header_re">RELATED ARTICLES</div>
                        <div class="content_re">
                            <ul class="news_style_re">
                                <li>
                                    <a href="https://www.greatandhra.com/movies/gossip/buzz-two-leading-actresses-transformations-spark-ozempic-rumours-153859"
                                        title="Buzz: Two Leading Actresses' Transformations Spark Ozempic Rumours">
                                        Buzz: Two Leading Actresses' Transformations Spark Ozempic Rumours </a>
                                </li>
                                <li>
                                    <a href="https://www.greatandhra.com/movies/gossip/buzz-less-shooting-more-dating-for-heroine-153848"
                                        title="Buzz: Less Shooting, More Dating for Heroine">
                                        Buzz: Less Shooting, More Dating for Heroine </a>
                                </li>
                                <li>
                                    <a href="https://www.greatandhra.com/movies/gossip/buzz-big-director-cost-shock-stuns-producer-153840"
                                        title="Buzz: Big Director Cost Shock Stuns Producer">
                                        Buzz: Big Director Cost Shock Stuns Producer </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <!--page_news-->




                    <div id="vuukle-emote" style="margin: auto;"></div>

                    <div id="vuukle-comments"
                        style="display: flex; flex-direction: column; width: 100%; align-items: stretch; overflow-anchor: auto;">
                    </div>

                    <?php if (!empty($ga_tag_list)): ?>
                    <p class="tags"><strong>Tags: </strong>
                        <?php foreach ($ga_tag_list as $ga_tag_name): ?>
                        <span class="tag-chip"><?php echo ga_e($ga_tag_name); ?></span>
                        <?php endforeach; ?>
                    </p>
                    <?php endif; ?>


                </div>
                <!--two_column-->
                <div class="column">
                    <ul class="un-sortable-list ui-sortable">
                        <li class="sortable-item">
                            <!--<div>				

			 	<a href="https://bit.ly/4ifpV1D"><img src="https://www.greatandhra.com/images/general/Artium_Academy_300_04242025_2.gif" width="300" /> </a>
			 	
			 	</div>-->

                            <div class="innerpage_latestnews">
                                <div class="header">Top News</div>
                                <div class="hm_topstory_3_story">
                                    <ul class="top_story_option2_3story list_top_news_mrgn">

                                        <li>
                                            <a href="https://www.greatandhra.com/movies/news/monday-bo-biker-outperforms-raakaasa-154025"
                                                title="Monday BO: Biker Outperforms Raakaasa">
                                                <div class="top_newsbox_img">
                                                    <img alt="Monday BO: Biker Outperforms Raakaasa" border="0"
                                                        src="images/biker61775536801.jpeg" width="111" height="62">
                                                </div>
                                                <div class="top_news_txt">
                                                    Monday BO: Biker Outperforms Raakaasa </div>
                                            </a>
                                        </li>


                                        <li>
                                            <a href="https://www.greatandhra.com/movies/news/peddis-postponement-team-to-make-it-official-shortly-154024"
                                                title="Peddi's Postponement: Team to Make it Official Shortly">
                                                <div class="top_newsbox_img">
                                                    <img alt="Peddi's Postponement: Team to Make it Official Shortly"
                                                        border="0" src="images/peddi81775535513.jpg" width="111"
                                                        height="62">
                                                </div>
                                                <div class="top_news_txt">
                                                    Peddi's Postponement: Team to Make it Official Shortly </div>
                                            </a>
                                        </li>


                                        <li>
                                            <a href="https://www.greatandhra.com/politics/gossip/revanth-to-fulfil-one-tola-gold-promise-soon-154023"
                                                title="Revanth to fulfil one-tola gold promise soon?">
                                                <div class="top_newsbox_img">
                                                    <img alt="Revanth to fulfil one-tola gold promise soon?" border="0"
                                                        src="images/revanth%20(1)1775529951.jpg" width="111"
                                                        height="62">
                                                </div>
                                                <div class="top_news_txt">
                                                    Revanth to fulfil one-tola gold promise soon? </div>
                                            </a>
                                        </li>



                                    </ul>
                                </div>

                            </div>
                        </li>



                        <li class="sortable-item">
                            <div id="vuukle-ad-5" class="margin-top-bot"></div>
                        </li>


                        <li class="sortable-item clear">

                            </style>
                            <div class="sortable-item_style_13">
                                <div class="header"> Top Trending Topics </div>
                                <div class="content trending_topics">
                                    <a href="https://www.greatandhra.com/topic/bheemla-nayak">Bheemla Nayak</a>
                                    <a href="https://www.greatandhra.com/topic/aadavallu-meeku-johaarlu">Aadavallu Meeku
                                        Johaarlu</a>
                                    <a href="https://www.greatandhra.com/topic/trivikram-srinivas">Trivikram
                                        Srinivas</a>
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



                        <li class="sortable-item">

                            <div class="sortable-item_style_14">
                                <div class="header"> Recommended For You </div>
                                <div class="hm_topstory_3_story">
                                    <ul class="top_story_option2_3story list_top_news_mrgn">

                                        <li>
                                            <a href="https://www.greatandhra.com/movies/reviews/biker-review-new-game-familiar-emotions-153935"
                                                title="Biker Review: New Game, Familiar Emotions">
                                                <div class="top_newsbox_img">
                                                    <img border="0"
                                                        src="images/biker91775193758.jpg&amp;width=113&amp;height=62&amp;action=resize&amp;quality=100"
                                                        width="111" height="62"
                                                        alt="Biker Review: New Game, Familiar Emotions">
                                                </div>
                                                <div class="top_news_txt">
                                                    Biker Review: New Game, Familiar Emotions </div>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="https://www.greatandhra.com/movies/reviews/raakaasaa-review-zero-horror-little-comedy-153936"
                                                title="RaaKaaSaa Review: Zero Horror, Little Comedy">
                                                <div class="top_newsbox_img">
                                                    <img border="0"
                                                        src="images/raakaasaa21775196309.jpg&amp;width=113&amp;height=62&amp;action=resize&amp;quality=100"
                                                        width="111" height="62"
                                                        alt="RaaKaaSaa Review: Zero Horror, Little Comedy">
                                                </div>
                                                <div class="top_news_txt">
                                                    RaaKaaSaa Review: Zero Horror, Little Comedy </div>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="https://www.greatandhra.com/articles/special-articles/trump-loses-it-drops-f-bomb-on-iran-153987"
                                                title="Trump Loses It, Drops F-Bomb On Iran">
                                                <div class="top_newsbox_img">
                                                    <img border="0"
                                                        src="images/trump_iran11775399425.jpg&amp;width=113&amp;height=62&amp;action=resize&amp;quality=100"
                                                        width="111" height="62"
                                                        alt="Trump Loses It, Drops F-Bomb On Iran">
                                                </div>
                                                <div class="top_news_txt">
                                                    Trump Loses It, Drops F-Bomb On Iran </div>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="https://www.greatandhra.com/movies/reviews/first-report-raakaasa-decent-fun-no-big-roar-153930"
                                                title="First Report: RaaKaaSa- Decent Fun, No Big Roar">
                                                <div class="top_newsbox_img">
                                                    <img border="0"
                                                        src="images/raakaasaa21775148515.jpg&amp;width=113&amp;height=62&amp;action=resize&amp;quality=100"
                                                        width="111" height="62"
                                                        alt="First Report: RaaKaaSa- Decent Fun, No Big Roar">
                                                </div>
                                                <div class="top_news_txt">
                                                    First Report: RaaKaaSa- Decent Fun, No Big Roar </div>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="https://www.greatandhra.com/movies/news/teaser-ranbirs-rama-avatar-is-grand-glorious-goosebumps-153916"
                                                title="Teaser: Ranbir's Rama Avatar Is Grand, Glorious, Goosebumps">
                                                <div class="top_newsbox_img">
                                                    <img border="0"
                                                        src="images/ramayana41775105638.jpg&amp;width=113&amp;height=62&amp;action=resize&amp;quality=100"
                                                        width="111" height="62"
                                                        alt="Teaser: Ranbir's Rama Avatar Is Grand, Glorious, Goosebumps">
                                                </div>
                                                <div class="top_news_txt">
                                                    Teaser: Ranbir's Rama Avatar Is Grand, Glorious, Goosebumps </div>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="https://www.greatandhra.com/politics/andhra-news/ap-murdered-by-family-betrayed-by-khaki-153981"
                                                title="AP: Murdered By Family, Betrayed By Khaki">
                                                <div class="top_newsbox_img">
                                                    <img border="0"
                                                        src="images/palnadu11775372743.jpg&amp;width=113&amp;height=62&amp;action=resize&amp;quality=100"
                                                        width="111" height="62"
                                                        alt="AP: Murdered By Family, Betrayed By Khaki">
                                                </div>
                                                <div class="top_news_txt">
                                                    AP: Murdered By Family, Betrayed By Khaki </div>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="https://www.greatandhra.com/articles/special-articles/cow-housewarming-ritual-in-texas-triggers-cultural-storm-153945"
                                                title="Cow Housewarming Ritual In Texas Triggers Cultural Storm">
                                                <div class="top_newsbox_img">
                                                    <img border="0"
                                                        src="images/cow11775227175.jpg&amp;width=113&amp;height=62&amp;action=resize&amp;quality=100"
                                                        width="111" height="62"
                                                        alt="Cow Housewarming Ritual In Texas Triggers Cultural Storm">
                                                </div>
                                                <div class="top_news_txt">
                                                    Cow Housewarming Ritual In Texas Triggers Cultural Storm </div>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="https://www.greatandhra.com/politics/telangana-news/dowry-horror-newlywed-techie-ends-life-in-hyd-153927"
                                                title="Dowry Horror: Newlywed Techie Ends Life In Hyd">
                                                <div class="top_newsbox_img">
                                                    <img border="0"
                                                        src="images/yaav11775140301.jpg&amp;width=113&amp;height=62&amp;action=resize&amp;quality=100"
                                                        width="111" height="62"
                                                        alt="Dowry Horror: Newlywed Techie Ends Life In Hyd">
                                                </div>
                                                <div class="top_news_txt">
                                                    Dowry Horror: Newlywed Techie Ends Life In Hyd </div>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </li>

                        <li class="sortable-item">
                            <div id="vuukle-ad-15" style="min-width: 300px; min-height: 250px;" class="margin-top-bot">
                            </div>
                        </li>



                        <!-- Newly Added Placeholder for Ad -->
                        <!-- Newly Added Placeholder for Ad -->



                    </ul>
                </div>
            </div>

        </div>

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
        <!--great_andhra_body-->
        <script>
            var VUUKLE_CONFIG = {
                apiKey: "2b166297-6273-48a9-82e9-696327c67418",
                articleId: "<?php echo ga_e($ga_article['id'] ?? ''); ?>",
                img: "<?php echo ga_e($ga_img_src); ?>",
                tags: "<?php echo ga_e(implode(', ', $ga_tag_list)); ?>",
                "powerbar": {
                    "items": [
                        "facebook",
                        "twitter",
                        "whatsapp",
                        "linkedin",
                    ],
                },
                theme: {
                    powerbarStyles: {
                        '.vuukle-actions': {
                            display: 'none' // hide comment and recommend buttons
                        },
                        '.vuukle-emote': {
                            display: 'none' // hide the emote button
                        },
                        '.shares-badge': {
                            display: 'block !important' // hide the emote button
                        },
                        '.more-btn': {
                            display: 'none' // hide the emote button
                        }
                    }
                },
                ads: { refresh: true }
            };
            // ⛔️ DON'T EDIT BELOW THIS LINE
            (function () {
                var d = document,
                    s = d.createElement('script');
                s.src = 'https://cdn.vuukle.com/platform.js';
                (d.head || d.body).appendChild(s);
            })();
        </script>

        <script type="text/javascript" src="./js/jquery-ui-1.8.custom.min.js"> </script>
        <script type="text/javascript" src="./js/great_andhra_view_js_160_ad_1.js"> </script>

    </div><iframe scrolling="no" frameborder="0" allowtransparency="true"
        src="https://platform.twitter.com/widgets/widget_iframe.2f70fb173b9000da126c79afe2098f02.html?origin=https%3A%2F%2Fwww.greatandhra.com"
        title="Twitter settings iframe" style="display: none;"></iframe><iframe id="rufous-sandbox" scrolling="no"
        frameborder="0" allowtransparency="true" allowfullscreen="true"
        style="position: absolute; visibility: hidden; display: none; width: 0px; height: 0px; padding: 0px; border: none;"
        title="Twitter analytics iframe"></iframe>
</body>

</html>