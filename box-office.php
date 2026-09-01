<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/helpers.php';
// Roadblock (full-page interstitial before render) only fires on the homepage now - was
// showing on every page type, which the user found intrusive on list/box-office/article pages.
require_once __DIR__ . '/inc/api-client.php';

$ga_bo_page = max(1, isset($_GET['page']) ? (int) $_GET['page'] : 1);
$ga_bo_skip = ($ga_bo_page - 1) * GA_BOX_OFFICE_TAKE;

// Fires the box-office listing feed + sidebar reviews feed + the 3 small Movie Rankings
// endpoints + every ad zone on this page concurrently, instead of the ~6 sequential blocking
// calls this page used to make one at a time.
ga_prefetch_page([
    'movieRankings' => true,
    'articles' => [
        [GA_BOX_OFFICE_TAKE, $ga_bo_skip, GA_NAV_CATEGORY_IDS['movies'], true],
        [GA_LIST_SIDEBAR_COUNT, 0, GA_NAV_CATEGORY_IDS['reviews']],
    ],
    'adZones' => [
        'BOXOFFICE_TOP_BANNER',
        'BOXOFFICE_MOBILE_BANNER',
        'BOXOFFICE_STICKY_AD',
        'BOXOFFICE_REVIEW_AD',
        'FULLSCREEN_INTERSTITIAL_AD',
        'BOTTOM_STICKY_AD',
    ],
]);

// Reads the FULLSCREEN_INTERSTITIAL_AD zone from the cache the batch above just warmed,
// instead of its own separate blocking request (previously called before ga_prefetch_page(),
// adding a full sequential network round trip to every page load).
$ga_interstitial_decision = ga_prepare_interstitial_ad('BOXOFFICE');

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
     <script type="text/javascript" async="" src="assets/config.js"></script>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <link rel="canonical" href="https://www.greatandhra.com/boxoffice">
    <title>Great Andhra - Boxoffice</title>
    <!-- <meta name="robots" content="index, follow"> -->

    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <link href="assets/css2" rel="stylesheet">
    <!-- <link href="css/great_andhra_style_landing_pages.css" rel="stylesheet" type="text/css"> -->
    <link href="css/footer.css?v=<?php echo ga_asset_version('css/footer.css'); ?>" rel="stylesheet" type="text/css">
    <link href="css/main-box-office.css?v=<?php echo ga_asset_version('css/main-box-office.css'); ?>" rel="stylesheet" type="text/css">
    <link href="css/box-office-mobile-responsive.css?v=<?php echo ga_asset_version('css/box-office-mobile-responsive.css'); ?>" rel="stylesheet">
    <link href="css/site-ads.css?v=<?php echo ga_asset_version('css/site-ads.css'); ?>" rel="stylesheet">
    <link href="css/header-mob.css?v=<?php echo ga_asset_version('css/header-mob.css'); ?>" rel="stylesheet">
    <script src="js/drawer.js?v=<?php echo ga_asset_version('js/drawer.js'); ?>"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- <link href="assets/image_preview.css" rel="stylesheet" type="text/css">
    <script async="" src="//cdn.confiant-integrations.net/gptprebidnative/202603241056/wrap.js"></script>
    <script type="text/javascript" async="" src="https://www.google-analytics.com/analytics.js"></script>
    <script type="text/javascript" async="" src="assets/js"></script>
    <script
        src="https://pagead2.googlesyndication.com/pagead/managed/js/adsense/m202604080101/show_ads_impl_fy2021.js?bust=31097754"></script>
    <script type="text/javascript" async="" src="https://d31qbv1cthcecs.cloudfront.net/atrk.js"></script>
    <script type="text/javascript" src="js/jquery-1.3.2.js"></script> -->
    <style>
        /* .thumb_container_box {
			width: 46%;
			float: left;
			margin: 20px 2%;
			height: 250px;
		} */

        /* .img_container_box img {
			width: 300px;
		} */

        /* .img_text_cont_box {
			display: block;
			clear: both;
			padding: 10px 0px;

		} */
        /* 
        .img_text_cont_box a {
            font-family: 'Lato', sans-serif;
            font-size: 15px;
            text-decoration: none;
        }

        .img_text_cont_box a:hover {
            font-family: 'Lato', sans-serif;
            font-size: 15px;

            text-decoration: none;
        } */

        /* 
        .content-block {
            padding: 10px;
        }

        .content-block p {
            font-family: 'Lato', sans-serif;
            font-size: 14px;
            line-height: 22px;
            margin-bottom: 20px;
        } */

        /* .table {
            width: 100%;
        }

        .table td,
        .table th {
            padding: 5px 10px;
            font-family: 'Lato', sans-serif;
            font-size: 14px;
            line-height: 22px;
        }

        .table th {
            background: #ed1c24;
            color: #fff;
        }

        .table-bordered {
            border-right: 1px solid #e0e0e0;
            border-top: 1px solid #e0e0e0;
        }

        .table-bordered td,
        .table-bordered th {
            border-left: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
        }

        .odd td {
            background: #f9f9f9;
        }

        .table-bordered td a {
            color: #006699;
        } */

        /* .block-title {
            margin: 10px 2%;
        }

        .block-title>span {
            font-family: 'Lato', sans-serif;
            font-size: 11px;
            font-weight: bold;
            color: #ffffff;
            background-color: #222222;
            border-radius: 3px;
            padding: 2px 15px 2px 15px;
            position: relative;
            display: inline-table;
            vertical-align: middle;
            line-height: 16px;
            top: -2px;
        }

        .most-recent {

            padding: 10px 0;
            overflow: hidden;
        }

        .related-title {
            font-family: 'Lato', sans-serif;
            font-size: 11px;
            font-weight: 700;
            line-height: 16px;
            margin: 22px 0px 22px 10px;
        }

        .related-left {
            color: #ffffff;
            background-color: #222222;
            border-radius: 3px 0px 0px 3px;
            padding: 1px 15px 1px 15px;
            border: 1px solid #222222;
            white-space: nowrap;
        }

        .related-right {
            border-radius: 0px 3px 3px 0px;
            border-top: 1px;
            border-right: 1px;
            border-bottom: 1px;
            border-left: 0px;
            border-color: #222222;
            border-style: solid;
            padding: 1px 14px 1px 14px;
            background-color: #ffffff;
            color: #222222;
            white-space: nowrap;
        }

        .most-recent ul {
            margin: 0px;
            padding: 0px;
        }

        .most-recent li {
            list-style: none;
            float: left;
            width: 31%;
            padding-right: 2%;
        }

        .most-recent li img {
            width: 100%;

        }

        .most-recent li h4 {
            font-family: 'Lato', sans-serif;
            font-size: 14px;
            font-weight: 700;
            margin-top: 10px;
        } */

        /* .gal_body_box {
			clear: both;
			margin-top: 20px;
		} */

        /* .header a {
            text-decoration: none;
        } */
    </style>
    <!-- Start Alexa Certify Javascript -->
    <script type="text/javascript">
        _atrk_opts = { atrk_acct: "TbYbo1IWNa10Y8", domain: "greatandhra.com", dynamic: true };
        (function () { var as = document.createElement('script'); as.type = 'text/javascript'; as.async = true; as.src = "https://d31qbv1cthcecs.cloudfront.net/atrk.js"; var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(as, s); })();
    </script>
    <noscript>&lt;img src="https://d5nxst8fruw4z.cloudfront.net/atrk.gif?account=TbYbo1IWNa10Y8" style="display:none"
        height="1" width="1" alt="" /&gt;</noscript>
    <!-- End Alexa Certify Javascript -->

    <!-- <script async="" src="assets/platform.js"></script>
    <meta http-equiv="origin-trial"
        content="AlK2UR5SkAlj8jjdEc9p3F3xuFYlF6LYjAML3EOqw1g26eCwWPjdmecULvBH5MVPoqKYrOfPhYVL71xAXI1IBQoAAAB8eyJvcmlnaW4iOiJodHRwczovL2RvdWJsZWNsaWNrLm5ldDo0NDMiLCJmZWF0dXJlIjoiV2ViVmlld1hSZXF1ZXN0ZWRXaXRoRGVwcmVjYXRpb24iLCJleHBpcnkiOjE3NTgwNjcxOTksImlzU3ViZG9tYWluIjp0cnVlfQ==">
    <meta http-equiv="origin-trial"
        content="Amm8/NmvvQfhwCib6I7ZsmUxiSCfOxWxHayJwyU1r3gRIItzr7bNQid6O8ZYaE1GSQTa69WwhPC9flq/oYkRBwsAAACCeyJvcmlnaW4iOiJodHRwczovL2dvb2dsZXN5bmRpY2F0aW9uLmNvbTo0NDMiLCJmZWF0dXJlIjoiV2ViVmlld1hSZXF1ZXN0ZWRXaXRoRGVwcmVjYXRpb24iLCJleHBpcnkiOjE3NTgwNjcxOTksImlzU3ViZG9tYWluIjp0cnVlfQ==">
    <meta http-equiv="origin-trial"
        content="A9nrunKdU5m96PSN1XsSGr3qOP0lvPFUB2AiAylCDlN5DTl17uDFkpQuHj1AFtgWLxpLaiBZuhrtb2WOu7ofHwEAAACKeyJvcmlnaW4iOiJodHRwczovL2RvdWJsZWNsaWNrLm5ldDo0NDMiLCJmZWF0dXJlIjoiQUlQcm9tcHRBUElNdWx0aW1vZGFsSW5wdXQiLCJleHBpcnkiOjE3NzQzMTA0MDAsImlzU3ViZG9tYWluIjp0cnVlLCJpc1RoaXJkUGFydHkiOnRydWV9">
    <meta http-equiv="origin-trial"
        content="A93bovR+QVXNx2/38qDbmeYYf1wdte9EO37K9eMq3r+541qo0byhYU899BhPB7Cv9QqD7wIbR1B6OAc9kEfYCA4AAACQeyJvcmlnaW4iOiJodHRwczovL2dvb2dsZXN5bmRpY2F0aW9uLmNvbTo0NDMiLCJmZWF0dXJlIjoiQUlQcm9tcHRBUElNdWx0aW1vZGFsSW5wdXQiLCJleHBpcnkiOjE3NzQzMTA0MDAsImlzU3ViZG9tYWluIjp0cnVlLCJpc1RoaXJkUGFydHkiOnRydWV9">
    <meta http-equiv="origin-trial"
        content="A1S5fojrAunSDrFbD8OfGmFHdRFZymSM/1ss3G+NEttCLfHkXvlcF6LGLH8Mo5PakLO1sCASXU1/gQf6XGuTBgwAAACQeyJvcmlnaW4iOiJodHRwczovL2dvb2dsZXRhZ3NlcnZpY2VzLmNvbTo0NDMiLCJmZWF0dXJlIjoiQUlQcm9tcHRBUElNdWx0aW1vZGFsSW5wdXQiLCJleHBpcnkiOjE3NzQzMTA0MDAsImlzU3ViZG9tYWluIjp0cnVlLCJpc1RoaXJkUGFydHkiOnRydWV9">
    <script type="text/javascript" src="assets/greatandhra.com.js" async=""></script>
    <script type="text/javascript" src="assets/prebid3.js" async=""></script>
    <script type="text/javascript" src="assets/config.js"></script>
    <script type="text/javascript" src="assets/gpt.js"></script>
    <meta http-equiv="origin-trial"
        content="AlK2UR5SkAlj8jjdEc9p3F3xuFYlF6LYjAML3EOqw1g26eCwWPjdmecULvBH5MVPoqKYrOfPhYVL71xAXI1IBQoAAAB8eyJvcmlnaW4iOiJodHRwczovL2RvdWJsZWNsaWNrLm5ldDo0NDMiLCJmZWF0dXJlIjoiV2ViVmlld1hSZXF1ZXN0ZWRXaXRoRGVwcmVjYXRpb24iLCJleHBpcnkiOjE3NTgwNjcxOTksImlzU3ViZG9tYWluIjp0cnVlfQ==">
    <meta http-equiv="origin-trial"
        content="Amm8/NmvvQfhwCib6I7ZsmUxiSCfOxWxHayJwyU1r3gRIItzr7bNQid6O8ZYaE1GSQTa69WwhPC9flq/oYkRBwsAAACCeyJvcmlnaW4iOiJodHRwczovL2dvb2dsZXN5bmRpY2F0aW9uLmNvbTo0NDMiLCJmZWF0dXJlIjoiV2ViVmlld1hSZXF1ZXN0ZWRXaXRoRGVwcmVjYXRpb24iLCJleHBpcnkiOjE3NTgwNjcxOTksImlzU3ViZG9tYWluIjp0cnVlfQ==">
    <meta http-equiv="origin-trial"
        content="A9nrunKdU5m96PSN1XsSGr3qOP0lvPFUB2AiAylCDlN5DTl17uDFkpQuHj1AFtgWLxpLaiBZuhrtb2WOu7ofHwEAAACKeyJvcmlnaW4iOiJodHRwczovL2RvdWJsZWNsaWNrLm5ldDo0NDMiLCJmZWF0dXJlIjoiQUlQcm9tcHRBUElNdWx0aW1vZGFsSW5wdXQiLCJleHBpcnkiOjE3NzQzMTA0MDAsImlzU3ViZG9tYWluIjp0cnVlLCJpc1RoaXJkUGFydHkiOnRydWV9">
    <meta http-equiv="origin-trial"
        content="A93bovR+QVXNx2/38qDbmeYYf1wdte9EO37K9eMq3r+541qo0byhYU899BhPB7Cv9QqD7wIbR1B6OAc9kEfYCA4AAACQeyJvcmlnaW4iOiJodHRwczovL2dvb2dsZXN5bmRpY2F0aW9uLmNvbTo0NDMiLCJmZWF0dXJlIjoiQUlQcm9tcHRBUElNdWx0aW1vZGFsSW5wdXQiLCJleHBpcnkiOjE3NzQzMTA0MDAsImlzU3ViZG9tYWluIjp0cnVlLCJpc1RoaXJkUGFydHkiOnRydWV9">
    <meta http-equiv="origin-trial"
        content="A1S5fojrAunSDrFbD8OfGmFHdRFZymSM/1ss3G+NEttCLfHkXvlcF6LGLH8Mo5PakLO1sCASXU1/gQf6XGuTBgwAAACQeyJvcmlnaW4iOiJodHRwczovL2dvb2dsZXRhZ3NlcnZpY2VzLmNvbTo0NDMiLCJmZWF0dXJlIjoiQUlQcm9tcHRBUElNdWx0aW1vZGFsSW5wdXQiLCJleHBpcnkiOjE3NzQzMTA0MDAsImlzU3ViZG9tYWluIjp0cnVlLCJpc1RoaXJkUGFydHkiOnRydWV9">
    <script src="assets/pubads_impl.js" async=""></script>
    <link href="https://securepubads.g.doubleclick.net/pagead/managed/dict/m202604090101/gpt"
        rel="compression-dictionary">
    <script async="" src="https://fundingchoicesmessages.google.com/i/123116330?ers=3"></script>
    <script async="" id="AV61613e403ff92a4a1008c1a4" src="assets/spt" type="text/javascript"></script> -->
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
    <script src="assets/jszip.min.js"></script>
    <link type="text/css" rel="stylesheet" href="assets/mcafee_fonts.css">
    <script src="assets/jszip.min.js"></script>
    <script src="assets/jszip.min.js"></script>
</head>

<body class="home_bg">
    <?php ga_render_interstitial_overlay($ga_interstitial_decision); ?>
    <?php ga_render_bottom_sticky_ad(); ?>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <!-- <script async="" src="assets/js"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag() { dataLayer.push(arguments); }
		gtag('js', new Date());

		gtag('config', 'G-PX1LPBMH02');
	</script> -->
    <!--great_andhra_body-->
    <div class="great_andhra_movie_body">
        <!--great_andhra_inner_body-->
        <div class="great_andhra_movie_inner_body">
            <!--great_andhra_search_panel-->
            <!--great_andhra_logo_panel-->
            <!-- <style>
				.great_andhra_logo_panel {
					height: 110px;
					margin: 10px 0px;
				}
			</style> -->
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
            <link rel="stylesheet" href="assets/all.css"
                integrity="sha384-lZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9S+oqd12jhcu+A56Ebc1zFSJ"
                crossorigin="anonymous">
            <script>
                $(function () {

                    // grab the initial top offset of the navigation
                    var sticky_navigation_offset_top = $('#great_andhra_main_menu_panel_2019').offset().top;

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
            <script type="text/javascript" src="assets/jquery.min.1.8.2.js"></script>
            <script type="text/javascript">
                $(document).ready(function (e) {
                    $('.search_img').click(function () {
                        $('#search_box_new').slideToggle('slow');
                    });
                });
            </script>



            <style>
                /* .dropbtn {
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
				} */

                /*.dropdown:hover .dropdown-content {
    display: block;
}*/

                /* .dropdown:hover .dropbtn {
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
					text-indent: 9999px;
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
				} */

                /* .is-active c-hamburger--htx span::after,
				.c-hamburger--htx span::before {
					-webkit-transition-duration: .3s, .3s;
					transition-duration: .3s, .3s;
					-webkit-transition-delay: .3s, 0s;
					transition-delay: .3s, 0s;

				}

				.c-hamburger--htx span::before {
					-webkit-transition-property: top, -webkit-transform;
					transition-property: top, transform
				} */

                /* .c-hamburger--htx span::after {
					-webkit-transition-property: bottom, -webkit-transform;
					transition-property: bottom, transform
				} */



                /* .c-hamburger--htx.is-active span {
					background: 0 0
				} */

                /* .c-hamburger--htx.is-active span::before {
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
				} */


                /* .great_andhra_main_menu_panel_2019 ul {
					font-size: 13px !important;
					margin: 0 !important;
					padding: 0 !important;
					list-style: none !important;
				} */

                /* .great_andhra_main_menu_panel_2019 ul li {
					display: block !important;
					position: relative !important;
					float: left !important;
				} */

                /* .great_andhra_main_menu_panel_2019 li ul {
					display: none !important;
					z-index: 999;
					min-width: 120px;
				} */

                /* .great_andhra_main_menu_panel_2019 ul li a {
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
				} */

                /* li.social-icons {
					width: 250px;
					padding-top: 2px;
				}

				.great_andhra_main_menu_panel_2019 li.social-icons ul {
					display: block !important;
					position: relative !important;
					float: right !important;
				} */
            </style>
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
							<img src="assets/ga-print.png" alt="greatandhra print">
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
                                <div class="header"><a
                                        href="https://www.greatandhra.com/movies/movie-news/top-5-solid-first-weekend-for-babu-bangaram-76373.html">This
                                        Week Top Five </a></div>
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
                                var img = box.querySelector('img');
                                if (!img || !img.src) return;
                                function applyBg() {
                                    box.style.backgroundImage = 'url(' + img.src + ')';
                                }
                                if (img.complete) applyBg();
                                else img.addEventListener('load', applyBg);
                            })();
                            </script>
                        </li>
                        <li class="un-sortable-item sortable-item_right_top_panel">
                            <div class="innerpage_latestnews1">
                                <div class="header">
                                    <a
                                        href="https://www.greatandhra.com/movies/box-office/tollywoods-all-time-top-movies-77063.html">All
                                        Time Top 5 Films </a>
                                </div>
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
                                <div class="header">
                                    <a
                                        href="https://www.greatandhra.com/movies/box-office/us-box-office-top-10-grossing-films-in-2016-76355.html">USA
                                        Box Office 2018: Top 5 Films </a>
                                </div>
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
            <!-- <div class="great_andhra_main_footer">
				<div class="copyright_bar_index">
					<footer id="gan-footer" class="section gan-footer">
						<div class="gangrid-main gan-footer-first" id="gan-footer-first">
							<section class="block" style="width:96%;">
								<ul class="menuf" style="text-align: center;">
									<li><a target="_blank" href="https://www.greatandhra.com/aboutus.php"
											title="About Us">About Us</a></li>
									<li><a target="_blank" href="https://www.greatandhra.com/disclaimer.php"
											title="Disclaimer">Disclaimer</a></li>
									<li><a target="_blank" href="https://www.greatandhra.com/contactus.php"
											title="Contact Us">Contact Us</a></li>
									<li><a target="_blank" href="https://www.greatandhra.com/convergence/index.php"
											title="Advertise With Us">Advertise With Us</a></li>
									<li><a target="_blank" href="https://www.greatandhra.com/privacy.php"
											title="Privacy Policy">Privacy Policy</a></li>
									<li><a target="_blank" href="https://www.greatandhra.com/grievance.php"
											title="Grievance">Grievance</a></li>
									<li><a target="_blank" href="https://epaper.greatandhra.com/"
											title="ePaper">ePaper</a></li>
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
    <script type="text/javascript" src="assets/great_andhra_view_js.js"> </script>


    <style>
        @media only screen and (max-width: 997px) {
            .vuukle-sticky-ad[data-ad-id="vuukle-ad-25"] {
                display: none !important;
            }
        }
    </style>
</body>
</html>
