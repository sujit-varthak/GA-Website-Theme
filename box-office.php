<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/helpers.php';
ga_maybe_show_roadblock_ad();
$ga_interstitial_decision = ga_prepare_interstitial_ad('BOXOFFICE');
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
    <link href="css/footer.css" rel="stylesheet" type="text/css">
    <link href="css/main-box-office.css" rel="stylesheet" type="text/css">
    <link href="css/box-office-mobile-responsive.css" rel="stylesheet">
    <link href="css/site-ads.css?v=<?php echo ga_asset_version('css/site-ads.css'); ?>" rel="stylesheet">
    <link href="css/header-mob.css" rel="stylesheet">
    <script src="js/drawer.js"></script>
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
                                        <?php foreach ($ga_bo_articles as $ga_bo_article): ?>
                                        <?php $ga_bo_img = ga_image($ga_bo_article, GA_BOX_OFFICE_FALLBACK_IMAGE); ?>
                                        <div class="thumb_container_box">
                                            <div class="img_container_box"> <a
                                                    href="<?php echo ga_e(ga_inner_link($ga_bo_article)); ?>">
                                                    <img border="0"
                                                        src="<?php echo ga_e($ga_bo_img['src']); ?>"
                                                        width="<?php echo (int) $ga_bo_img['width']; ?>"
                                                        height="<?php echo (int) $ga_bo_img['height']; ?>"
                                                        alt="<?php echo ga_e($ga_bo_article['title'] ?? ''); ?>"> </a>
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


    <ins class="adsbygoogle adsbygoogle-noablate" data-ad-hi="true" data-adsbygoogle-status="done"
        style="display: none !important;" data-ad-status="unfilled">
        <div id="aswift_1_host"
            style="border: none; height: 0px; width: 0px; margin: 0px; padding: 0px; position: relative; visibility: visible; background-color: transparent; display: inline-block;">
            <iframe id="aswift_1" name="aswift_1"
                style="left:0;position:absolute;top:0;border:0;width:undefinedpx;height:undefinedpx;min-height:auto;max-height:none;min-width:auto;max-width:none;"
                sandbox="allow-forms allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts allow-top-navigation-by-user-activation"
                frameborder="0" marginwidth="0" marginheight="0" vspace="0" hspace="0" allowtransparency="true"
                scrolling="no" allow="attribution-reporting; run-ad-auction"
                src="https://googleads.g.doubleclick.net/pagead/ads?client=ca-pub-1239645388568087&amp;output=html&amp;adk=1812271804&amp;adf=3025194257&amp;lmt=1776061646&amp;plaf=1%3A2%2C7%3A2&amp;plat=1%3A128%2C2%3A128%2C3%3A128%2C4%3A128%2C9%3A134250504%2C16%3A8388608%2C17%3A32%2C24%3A32%2C25%3A32%2C30%3A1081344%2C32%3A32%2C41%3A32%2C42%3A32%2C43%3A32&amp;format=0x0&amp;url=https%3A%2F%2Fwww.greatandhra.com%2Fboxoffice&amp;pra=7&amp;aiof=9&amp;asro=0&amp;itsi=0&amp;aiapmd=0.0001&amp;aiapmid=0.0001&amp;aiactd=0&amp;aicctd=0&amp;ailctd=0&amp;aimartd=4&amp;aieuf=1&amp;aicrs=1&amp;uach=WyJXaW5kb3dzIiwiMTkuMC4wIiwieDg2IiwiIiwiMTQ2LjAuNzY4MC4xNzgiLG51bGwsMCxudWxsLCI2NCIsW1siQ2hyb21pdW0iLCIxNDYuMC43NjgwLjE3OCJdLFsiTm90LUEuQnJhbmQiLCIyNC4wLjAuMCJdLFsiR29vZ2xlIENocm9tZSIsIjE0Ni4wLjc2ODAuMTc4Il1dLDBd&amp;abgtt=11&amp;dt=1776061646463&amp;bpp=1&amp;bdt=1443&amp;idt=58&amp;shv=r20260408&amp;mjsv=m202604080101&amp;ptt=9&amp;saldr=aa&amp;abxe=1&amp;cookie=ID%3Dc1d005f1c2328313%3AT%3D1773396544%3ART%3D1776061639%3AS%3DALNI_MbtV5gHJwIAwS1icZmf9P0aK7G9Mg&amp;gpic=UID%3D0000134e9624ee81%3AT%3D1773396544%3ART%3D1776061639%3AS%3DALNI_MZnoI_byq5q9-1EJZN5uK_WBkc5jw&amp;eo_id_str=ID%3D98b37d8217efaecc%3AT%3D1773396544%3ART%3D1776061639%3AS%3DAA-AfjZ-HZk45cwLPWylP6Rzwe16&amp;prev_fmts=300x600&amp;nras=1&amp;correlator=6674390663878&amp;frm=20&amp;pv=1&amp;u_tz=330&amp;u_his=8&amp;u_h=864&amp;u_w=1536&amp;u_ah=816&amp;u_aw=1536&amp;u_cd=32&amp;u_sd=1.25&amp;dmc=8&amp;adx=-12245933&amp;ady=-12245933&amp;biw=1521&amp;bih=730&amp;scr_x=0&amp;scr_y=0&amp;eid=31097634%2C95382262%2C95385799%2C95386179%2C31097754%2C95387625%2C95386336%2C95386957%2C95388041&amp;oid=2&amp;pvsid=3664697994000739&amp;tmod=291761131&amp;uas=1&amp;nvt=1&amp;fsapi=1&amp;ref=https%3A%2F%2Fwww.greatandhra.com%2Fmovies&amp;fc=896&amp;brdim=0%2C0%2C0%2C0%2C1536%2C0%2C1536%2C816%2C1536%2C730&amp;vis=1&amp;rsz=%7C%7Cs%7C&amp;abl=NS&amp;fu=32768&amp;bc=31&amp;bz=1&amp;ifi=2&amp;uci=a!2&amp;fsb=1&amp;dtd=63"
                data-google-container-id="a!2" tabindex="0" title="Advertisement" aria-label="Advertisement"
                data-load-complete="true"></iframe>
        </div>
    </ins><iframe style="display: none;"></iframe><iframe marginwidth="0" marginheight="0" scrolling="no"
        frameborder="0" id="119e0c7dd47946" width="0" height="0" src="about:blank" name="__pb_locator__"
        style="display: none; height: 0px; width: 0px; border: 0px;"></iframe><iframe
        srcdoc="&lt;html lang=&quot;en&quot;&gt;&lt;head&gt;&lt;meta charset=&quot;UTF-8&quot;&gt;&lt;script&gt;window.googletag = window.parent.googletag;window.__cmp = window.parent.__cmp; window.__tcfapi = window.parent.__tcfapi;!function(a9,a,p,s,t,A,g){if(a[a9])return; function q(c,r){a[a9]._Q.push([c,r])}a[a9]={init:function(){q(&quot;i&quot;,arguments)},fetchBids:function(){q(&quot;f&quot;,arguments)},setDisplayBids:function(){},targetingKeys:function(){return[]},_Q:[]}; A=p.createElement(s); A.async=!0; A.src=t; g=p.getElementsByTagName(s)[0]; g.parentNode.insertBefore(A,g)}(&quot;apstag&quot;,window,document,&quot;script&quot;,&quot;//c.amazon-adsystem.com/aax2/apstag.js&quot;);apstag.init({&quot;pubID&quot;:&quot;842701b4-f689-4de3-9ff4-bc1999093771&quot;,&quot;adServer&quot;:&quot;googletag&quot;,&quot;videoAdServer&quot;:&quot;DFP&quot;,&quot;gdpr&quot;:{&quot;cmpTimeout&quot;:200},&quot;schain&quot;:{&quot;ver&quot;:&quot;1.0&quot;,&quot;complete&quot;:1,&quot;nodes&quot;:[{&quot;asi&quot;:&quot;vuukle.com&quot;,&quot;sid&quot;:&quot;2b166297-6273-48a9-82e9-696327c67418&quot;,&quot;hp&quot;:1}]}});window.parent['__vuukleCb49a0b71b']();&lt;/script&gt;&lt;/head&gt;&lt;body&gt;&lt;/body&gt;&lt;/html&gt;"
        src="javascript:false" style="width: 1px; height: 1px; border: 0px; margin: 0px;"></iframe><iframe
        style="width: 0px; height: 0px; display: none; position: fixed; left: -999px; top: -999px;"></iframe><iframe
        name="cnftComm"
        style="width: 0px; height: 0px; display: none; position: fixed; left: -999px; top: -999px;"></iframe><iframe
        name="googlefcPresent"
        style="display: none; width: 0px; height: 0px; border: none; z-index: -1000; left: -1000px; top: -1000px;"></iframe>
    <style>
        @media only screen and (max-width: 997px) {
            .vuukle-sticky-ad[data-ad-id="vuukle-ad-25"] {
                display: none !important;
            }
        }
    </style>
    <div class="AV61613e403ff92a4a1008c1a4" style="width: 100%; margin: 0px auto;">
        <div id="aniBox"
            style="overflow: hidden; margin: 0px auto; transition: height 1s; width: 400px; height: 1px; opacity: 0;">
            <div id="aniplayer_AV61613e403ff92a4a1008c1a4-1776061646717"
                style="z-index: 10000001; position: fixed; bottom: 1px; right: 20px; transform: scale(1); transform-origin: right bottom; visibility: hidden;">
                <!-- <style type="text/css" id="av_css_id" style="display: none !important;">
                    #nonl-container * {
                        display: block
                    }

                    #av-caption #av-close-btn:hover,
                    #av-container #av-inner #gui #av-close-btn:hover,
                    #av-container.av-desktop #av-inner #gui #close-btn:hover,
                    #av-container.av-desktop #av-inner #gui #skip-btn:hover {
                        background-color: #000;
                        background-color: rgba(0, 0, 0, .7);
                        cursor: pointer
                    }

                    #av-caption #av-close-btn,
                    #av-container #av-inner #gui #av-close-btn,
                    #av-container #av-inner #gui #close-btn {
                        background-color: #323232;
                        background-color: rgba(0, 0, 0, .4);
                        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 xml:space=%27preserve%27 width=%2723%27 height=%2723%27 viewBox=%270 0 22.677 22.677%27%3E%3Cpath fill=%27%23fff%27 fill-rule=%27evenodd%27 d=%27m19.346 5.421-2.09-2.089-5.918 5.918L5.42 3.332 3.332 5.421l5.918 5.917-5.918 5.919 2.088 2.088 5.918-5.918 5.918 5.918 2.09-2.088-5.918-5.919z%27 clip-rule=%27evenodd%27/%3E%3C/svg%3E");
                        background-position: 50%;
                        background-repeat: no-repeat;
                        background-size: 60%;
                        border-color: #fff;
                        border-style: solid;
                        border-width: 0 1px 1px 0;
                        height: 28px;
                        left: 0;
                        position: absolute;
                        top: 0;
                        -webkit-transition: all .15s ease-in-out;
                        -moz-transition: all .15s ease-in-out;
                        -o-transition: all .15s ease-in-out;
                        transition: all .15s ease-in-out;
                        width: 28px;
                        z-index: 9999999
                    }

                    #av-caption #av-close-btn,
                    #av-container #av-inner #gui #av-close-btn {
                        border: none;
                        height: 18px;
                        position: static;
                        width: 18px
                    }

                    #av-caption {
                        line-height: 18px;
                        position: relative;
                        text-align: center
                    }

                    #av-caption #av-close-btn-overlay {
                        display: inline-block;
                        position: relative;
                        vertical-align: top;
                        z-index: 9999999
                    }

                    #av-label {
                        color: #bbb;
                        display: inline-block;
                        font-family: Helvetica, Arial, fallback, sans-serif;
                        font-size: 9px;
                        line-height: 10px;
                        margin: 0;
                        padding: 4px;
                        text-align: center;
                        text-transform: uppercase;
                        vertical-align: top;
                        z-index: 83
                    }

                    #av-container {
                        height: 360px;
                        margin: 0;
                        overflow: hidden;
                        pointer-events: auto;
                        position: relative;
                        text-align: initial;
                        width: 640px
                    }

                    #av-container ::-webkit-media-controls-panel {
                        -webkit-appearance: none;
                        display: none !important
                    }

                    #av-container ::state(webkit-media-controls-play-button) {
                        -webkit-appearance: none;
                        display: none !important
                    }

                    #av-container ::-webkit-media-controls-start-playback-button {
                        -webkit-appearance: none;
                        display: none !important
                    }

                    #av-container div {
                        position: static
                    }

                    #av-container #av-inner {
                        height: 100%;
                        left: 0;
                        position: absolute;
                        top: 0;
                        width: 100%
                    }

                    #av-container #av-inner #slot {
                        -webkit-box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .1);
                        -moz-box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .1);
                        box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .1);
                        height: 100%;
                        position: absolute;
                        width: 100%
                    }

                    #av-container #av-inner #slot #preloader {
                        bottom: 0;
                        height: 0;
                        left: 0;
                        margin: auto;
                        outline: none;
                        position: absolute;
                        right: 0;
                        top: 0;
                        width: 0
                    }

                    #av-container #av-inner #slot #preloader svg {
                        left: 50%;
                        position: absolute;
                        top: 50%
                    }

                    #av-container #av-inner #slot #preloader svg.avicon {
                        fill: #fff;
                        height: 50px;
                        margin-left: -25px;
                        margin-top: -25px;
                        width: 50px
                    }

                    #av-container #av-inner #slot #preloader svg.avcircle {
                        fill: transparent;
                        stroke: hsla(0, 0%, 100%, .2);
                        stroke-width: 3;
                        height: 70px;
                        margin-left: -35px;
                        margin-top: -35px;
                        width: 70px
                    }

                    #av-container #av-inner #slot #preloader svg.avcircle.active {
                        color: red;
                        stroke: #fff;
                        stroke-linecap: round;
                        animation: av-loading-dash 2s ease infinite, av-loading-rotate 2.5s linear infinite
                    }

                    #av-container #av-inner #slot #videoslot {
                        bottom: 0;
                        filter: alpha(opacity=0);
                        left: 0;
                        object-fit: fill;
                        opacity: 0;
                        position: absolute;
                        right: 0;
                        text-align: left;
                        top: 50%;
                        -webkit-transform: translateY(-50%);
                        -ms-transform: translateY(-50%);
                        transform: translateY(-50%);
                        width: 100%
                    }

                    #av-container #av-inner #slot #videoslot.loaded {
                        -webkit-animation: fade-in .5s ease;
                        -moz-animation: fade-in .5s ease;
                        -o-animation: fade-in .5s ease;
                        animation: fade-in .5s ease;
                        filter: alpha(opacity=100);
                        opacity: 1
                    }

                    #av-container #av-inner #slot #videoslot div {
                        all: initial;
                        background: initial;
                        position: static;
                        z-index: auto
                    }

                    #av-container #av-inner #gui:before {
                        background: -webkit-linear-gradient(top, transparent, rgba(0, 0, 0, .5));
                        background: -moz-linear-gradient(top, transparent 0, rgba(0, 0, 0, .5) 100%);
                        background: linear-gradient(180deg, transparent 0, rgba(0, 0, 0, .5));
                        bottom: 0;
                        content: "";
                        filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#00000000", endColorstr="#80000000", GradientType=0);
                        height: 15%;
                        left: 0;
                        pointer-events: none;
                        width: 100%
                    }

                    #av-container #av-inner #gui #timeline,
                    #av-container #av-inner #gui:before {
                        position: absolute;
                        -webkit-transition: all .15s ease-in-out;
                        -moz-transition: all .15s ease-in-out;
                        -o-transition: all .15s ease-in-out;
                        transition: all .15s ease-in-out
                    }

                    #av-container #av-inner #gui #timeline {
                        cursor: pointer;
                        height: 10px;
                        overflow: hidden
                    }

                    #av-container #av-inner #gui #timeline #timeline-buffer,
                    #av-container #av-inner #gui #timeline #timeline-moveto,
                    #av-container #av-inner #gui #timeline #timeline-progress,
                    #av-container #av-inner #gui #timeline:before {
                        height: 2px;
                        left: 0;
                        position: absolute
                    }

                    #av-container #av-inner #gui #timeline:before {
                        background: #646464;
                        background: hsla(0, 0%, 100%, .3);
                        content: "";
                        width: 100%
                    }

                    #av-container #av-inner #gui #timeline #timeline-buffer,
                    #av-container #av-inner #gui #timeline:before {
                        -webkit-box-shadow: 0 0 3px 0 rgba(0, 0, 0, .1);
                        -moz-box-shadow: 0 0 3px 0 rgba(0, 0, 0, .1);
                        box-shadow: 0 0 3px 0 rgba(0, 0, 0, .1)
                    }

                    #av-container #av-inner #gui #timeline #timeline-buffer {
                        background: #b4b4b4;
                        background: hsla(0, 0%, 100%, .5);
                        width: 0
                    }

                    #av-container #av-inner #gui #timeline #timeline-moveto {
                        background: #fff;
                        background: hsla(0, 0%, 100%, .8);
                        opacity: 0;
                        width: 0
                    }

                    #av-container #av-inner #gui #timeline #timeline-progress {
                        background: red;
                        width: 0
                    }

                    #av-container #av-inner #gui #timeline.av-overlay {
                        bottom: 41px;
                        left: 10px;
                        right: 10px
                    }

                    #av-container #av-inner #gui #timeline.av-overlay #timeline-buffer,
                    #av-container #av-inner #gui #timeline.av-overlay #timeline-moveto,
                    #av-container #av-inner #gui #timeline.av-overlay #timeline-progress,
                    #av-container #av-inner #gui #timeline.av-overlay:before {
                        margin-top: -1px;
                        top: 50%
                    }

                    #av-container #av-inner #gui #timeline.av-bottom {
                        bottom: 0;
                        left: 0;
                        right: 0
                    }

                    #av-container #av-inner #gui #timeline.av-bottom #timeline-buffer,
                    #av-container #av-inner #gui #timeline.av-bottom #timeline-moveto,
                    #av-container #av-inner #gui #timeline.av-bottom #timeline-progress,
                    #av-container #av-inner #gui #timeline.av-bottom:before {
                        bottom: 0;
                        top: auto
                    }

                    #av-container #av-inner #gui #timeline.av-top {
                        left: 0;
                        right: 0;
                        top: 0
                    }

                    #av-container #av-inner #gui #timeline.av-top #timeline-buffer,
                    #av-container #av-inner #gui #timeline.av-top #timeline-moveto,
                    #av-container #av-inner #gui #timeline.av-top #timeline-progress,
                    #av-container #av-inner #gui #timeline.av-top:before {
                        bottom: auto;
                        top: 0
                    }

                    #av-container #av-inner #gui #timeline.av-top~#skip-btn {
                        top: 2px
                    }

                    #av-container #av-inner #gui #timeline.av-top~#aniview-credit {
                        top: 4px
                    }

                    #av-container #av-inner #gui #timeline.av-none {
                        display: none !important;
                        opacity: 0 !important
                    }

                    #av-container #av-inner #gui #buttons {
                        bottom: 0;
                        display: flex;
                        justify-content: space-between;
                        left: 0;
                        padding: 0 13px 7px;
                        position: absolute;
                        right: 0;
                        -webkit-transition: all .15s ease-in-out;
                        -moz-transition: all .15s ease-in-out;
                        -o-transition: all .15s ease-in-out;
                        transition: all .15s ease-in-out
                    }

                    #av-container #av-inner #gui #buttons.av-left {
                        justify-content: flex-start;
                        right: auto
                    }

                    #av-container #av-inner #gui #buttons.av-left #play-pause {
                        margin-right: 0
                    }

                    #av-container #av-inner #gui #buttons #play-pause:after,
                    #av-container #av-inner #gui #buttons #sound:after {
                        bottom: -10px;
                        content: "";
                        left: -10px;
                        position: absolute;
                        right: -10px;
                        top: 0
                    }

                    #av-container #av-inner #gui #buttons #fullscreen,
                    #av-container #av-inner #gui #buttons #phone,
                    #av-container #av-inner #gui #buttons #play-pause,
                    #av-container #av-inner #gui #buttons #share,
                    #av-container #av-inner #gui #buttons #sound {
                        background-repeat: no-repeat;
                        background-size: cover;
                        height: 24px;
                        width: 24px
                    }

                    #av-container #av-inner #gui #buttons #left,
                    #av-container #av-inner #gui #buttons #right {
                        height: 24px
                    }

                    #av-container #av-inner #gui #buttons #left>div {
                        float: left;
                        margin-right: 14px;
                        position: relative
                    }

                    #av-container #av-inner #gui #buttons #left #play-pause.play {
                        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 xml:space=%27preserve%27 width=%2723%27 height=%2723%27 viewBox=%270 0 22.677 22.677%27%3E%3Cpath fill=%27%23fff%27 d=%27M19.343 11.251c-.035-.439-.334-.874-.902-1.207L7.536 3.641c-1.211-.712-2.203-.138-2.203 1.275v7.463c5.85-2.36 13.783-1.163 14.01-1.128M5.333 17v.762c0 1.412.992 1.985 2.203 1.273l10.904-6.402c.643-.377.941-.882.902-1.379-8.65.696-12.404 3.614-14.009 5.746%27/%3E%3C/svg%3E")
                    }

                    #av-container #av-inner #gui #buttons #left #play-pause.pause {
                        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 xml:space=%27preserve%27 width=%2723%27 height=%2723%27 viewBox=%270 0 22.677 22.677%27%3E%3Cpath fill=%27%23fff%27 d=%27M4.338 19.339h5v-16h-5zm9.001-16.001v16h5v-16z%27/%3E%3C/svg%3E")
                    }

                    #av-container #av-inner #gui #buttons #left #play-pause.replay {
                        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 xml:space=%27preserve%27 width=%2723%27 height=%2723%27 viewBox=%270 0 22.677 22.677%27%3E%3Cpath fill=%27%23fff%27 d=%27M16.672 11.339a5.334 5.334 0 0 1-10.668 0 5.334 5.334 0 0 1 5.326-5.333v2.336l7.016-3.503-7.016-3.503V3.34a8 8 0 0 0 .008 15.999 8 8 0 0 0 8-8z%27/%3E%3C/svg%3E")
                    }

                    #av-container #av-inner #gui #buttons #left #phone {
                        font-size: 0;
                        width: auto
                    }

                    #av-container #av-inner #gui #buttons #left #phone .avicon {
                        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 xml:space=%27preserve%27 width=%2723%27 height=%2723%27 viewBox=%270 0 22.677 22.677%27%3E%3Cpath fill=%27%23fff%27 fill-rule=%27evenodd%27 d=%27M20.154 17.236c.299-.298.254-.921-.236-1.188-.492-.267-4.518-2.602-4.982-2.138-.463.465-1.447 1.498-2.135 1.188s-2.289-1.816-2.846-2.375c-.559-.557-2.064-2.159-2.377-2.847-.31-.687.725-1.67 1.188-2.134.464-.465-1.87-4.491-2.137-4.982-.266-.489-.889-.534-1.188-.237s-2.18 2.176-2.85 2.847c-.671.671-.279 5.415 4.514 10.202 4.787 4.792 9.531 5.185 10.203 4.514s2.549-2.551 2.846-2.85%27 clip-rule=%27evenodd%27/%3E%3C/svg%3E");
                        background-repeat: no-repeat;
                        background-size: cover;
                        display: inline-block;
                        height: 24px;
                        vertical-align: top;
                        width: 24px
                    }

                    #av-container #av-inner #gui #buttons #left #phone #phone-num {
                        color: #fff;
                        display: inline-block;
                        font-family: Helvetica, Arial, fallback, sans-serif;
                        font-size: 12px;
                        font-weight: 400;
                        height: 24px;
                        line-height: 24px;
                        margin-left: 4px
                    }

                    #av-container #av-inner #gui #buttons #right>div {
                        float: right;
                        margin-left: 14px;
                        position: relative
                    }

                    #av-container #av-inner #gui #buttons #right #sound {
                        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 xml:space=%27preserve%27 width=%2723%27 height=%2723%27 viewBox=%270 0 22.68 22.68%27%3E%3Cpath fill=%27%23fff%27 fill-rule=%27evenodd%27 d=%27M7.247 7.34H1.761v8h5.486l5.496 4.006V3.334z%27 clip-rule=%27evenodd%27/%3E%3C/svg%3E")
                    }

                    #av-container #av-inner #gui #buttons #right #sound div {
                        background-repeat: no-repeat;
                        background-size: cover;
                        height: 100%;
                        left: 0;
                        position: absolute;
                        top: 0;
                        width: 100%
                    }

                    #av-container #av-inner #gui #buttons #right #sound div.on {
                        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 xml:space=%27preserve%27 width=%2723%27 height=%2723%27 viewBox=%270 0 22.677 22.677%27%3E%3Cpath fill=%27%23fff%27 d=%27m17.808 4.757-1.705 1.881c1.357 1.146 2.221 2.824 2.221 4.7s-.863 3.556-2.221 4.701l1.705 1.881c1.902-1.605 3.109-3.955 3.109-6.582s-1.207-4.975-3.109-6.581%27/%3E%3C/svg%3E")
                    }

                    #av-container #av-inner #gui #buttons #right #sound div.off {
                        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 xml:space=%27preserve%27 width=%2723%27 height=%2723%27 viewBox=%270 0 22.677 22.677%27%3E%3Cpath fill=%27red%27 fill-rule=%27evenodd%27 d=%27m20.009 11.337 2.662-2.663-1.328-1.329-2.664 2.662-2.664-2.662-1.33 1.329 2.664 2.663-2.668 2.666 1.334 1.328 2.664-2.665 2.664 2.665 1.334-1.328z%27 clip-rule=%27evenodd%27/%3E%3C/svg%3E")
                    }

                    #av-container #av-inner #gui #buttons #right #share {
                        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 xml:space=%27preserve%27 width=%2723%27 height=%2723%27 viewBox=%270 0 22.677 22.677%27%3E%3Cpath fill=%27%23fff%27 fill-rule=%27evenodd%27 d=%27M17.338 14.339a2.97 2.97 0 0 0-1.838.645l-7.17-3.567c0-.026.008-.051.008-.078 0-.026-.008-.052-.008-.078l7.17-3.566c.51.397 1.143.645 1.838.645a3 3 0 1 0-3-3c0 .188.023.372.057.552L7.531 9.304a2.98 2.98 0 0 0-2.193-.966 3 3 0 0 0 0 6c.869 0 1.645-.375 2.193-.965l6.863 3.412a3 3 0 0 0-.057.553 3.001 3.001 0 0 0 6 0 3 3 0 0 0-2.999-2.999%27 clip-rule=%27evenodd%27/%3E%3C/svg%3E")
                    }

                    #av-container #av-inner #gui #buttons #right #fullscreen.off {
                        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 xml:space=%27preserve%27 width=%2723%27 height=%2723%27 viewBox=%270 0 22.677 22.677%27%3E%3Cpath fill=%27%23fff%27 d=%27M4.339 15.339h-2v5h5v-2h-3zm-2-8.001h2v-3h3v-2h-5zm13-5v2h3v3h2v-5zm3 16.001h-3v2h5v-5h-2z%27/%3E%3C/svg%3E")
                    }

                    #av-container #av-inner #gui #buttons #right #fullscreen.on {
                        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 xml:space=%27preserve%27 width=%2723%27 height=%2723%27 viewBox=%270 0 22.677 22.677%27%3E%3Cpath fill=%27%23fff%27 d=%27M6.338 6.338h-3v2h5v-5h-2zm-3 10.001h3v3h2v-5h-5zm11 3h2v-3h3v-2h-5zm2-13.001v-3h-2v5h5v-2z%27/%3E%3C/svg%3E")
                    }

                    #av-container #av-inner #gui #big-play {
                        background-color: #323232;
                        background-color: rgba(0, 0, 0, .4);
                        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 xml:space=%27preserve%27 width=%2723%27 height=%2723%27 viewBox=%270 0 22.677 22.677%27%3E%3Cpath fill=%27%23fff%27 d=%27M19.343 11.251c-.035-.439-.334-.874-.902-1.207L7.536 3.641c-1.211-.712-2.203-.138-2.203 1.275v7.463c5.85-2.36 13.783-1.163 14.01-1.128M5.333 17v.762c0 1.412.992 1.985 2.203 1.273l10.904-6.402c.643-.377.941-.882.902-1.379-8.65.696-12.404 3.614-14.009 5.746%27/%3E%3C/svg%3E");
                        background-position: 16px;
                        background-repeat: no-repeat;
                        background-size: 62% 64%;
                        border: 3px solid #fff;
                        border-radius: 50%;
                        display: none;
                        height: 68px;
                        left: 50%;
                        margin-left: -34px;
                        margin-top: -34px;
                        position: absolute;
                        top: 50%;
                        -webkit-transition: all .15s ease-in-out;
                        -moz-transition: all .15s ease-in-out;
                        -o-transition: all .15s ease-in-out;
                        transition: all .15s ease-in-out;
                        width: 68px
                    }

                    #av-container #av-inner #gui #timer {
                        background-color: #000;
                        background-color: rgba(0, 0, 0, .7);
                        border-color: #fff;
                        border-style: solid;
                        border-width: 0 1px 1px 0;
                        font-size: 12px;
                        height: 28px;
                        left: 0;
                        line-height: 28px;
                        top: 0;
                        width: 28px
                    }

                    #av-container #av-inner #gui #skip-btn,
                    #av-container #av-inner #gui #timer {
                        color: #fff;
                        font-family: Helvetica, Arial, fallback, sans-serif;
                        position: absolute;
                        text-align: center;
                        -webkit-transition: all .15s ease-in-out;
                        -moz-transition: all .15s ease-in-out;
                        -o-transition: all .15s ease-in-out;
                        transition: all .15s ease-in-out
                    }

                    #av-container #av-inner #gui #skip-btn {
                        background-color: #323232;
                        background-color: rgba(0, 0, 0, .4);
                        border: 1px solid #fff;
                        border-right-width: 0;
                        bottom: 60px;
                        font-size: 14px;
                        height: 32px;
                        line-height: 32px;
                        min-width: 30px;
                        padding: 0 12px;
                        right: 0;
                        text-transform: uppercase
                    }

                    #av-container #av-inner #gui #aniview-credit {
                        color: #fff;
                        font-family: Helvetica, Arial, fallback, sans-serif;
                        font-size: 11px;
                        font-weight: 500;
                        height: 24px;
                        line-height: 24px;
                        position: absolute;
                        right: 2px;
                        top: 2px
                    }

                    #av-container #av-inner #gui #aniview-credit span {
                        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 xml:space=%27preserve%27 width=%2723%27 height=%2723%27 viewBox=%270 0 22.677 22.677%27%3E%3Cpath fill=%27red%27 d=%27M19.343 11.251c-.035-.439-.334-.874-.902-1.207L7.536 3.641c-1.211-.712-2.203-.138-2.203 1.275v7.463c5.85-2.36 13.783-1.163 14.01-1.128M5.333 17v.762c0 1.412.992 1.985 2.203 1.273l10.904-6.402c.643-.377.941-.882.902-1.379-8.65.696-12.404 3.614-14.009 5.746%27/%3E%3C/svg%3E");
                        background-repeat: no-repeat;
                        background-size: cover;
                        display: inline-block;
                        height: 24px;
                        vertical-align: top;
                        width: 24px
                    }

                    #av-container.av-desktop #av-inner #gui #big-play {
                        border-color: #ccc
                    }

                    #av-container.av-desktop #gui:before {
                        bottom: -15%
                    }

                    #av-container.av-desktop #gui #buttons,
                    #av-container.av-desktop #gui #timeline,
                    #av-container.av-desktop #gui:before {
                        filter: alpha(opacity=0);
                        opacity: 0
                    }

                    #av-container.av-desktop:hover #av-inner #gui #big-play {
                        background-color: #000;
                        background-color: rgba(0, 0, 0, .7);
                        border-color: #fff
                    }

                    #av-container.av-desktop:hover #gui:before {
                        bottom: 0
                    }

                    #av-container.av-desktop #av-inner #gui #timeline:hover #timeline-moveto,
                    #av-container.av-desktop:hover #gui #buttons,
                    #av-container.av-desktop:hover #gui #timeline,
                    #av-container.av-desktop:hover #gui:before {
                        filter: alpha(opacity=100);
                        opacity: 1
                    }

                    #av-container.av-responsive {
                        height: 520px;
                        width: 100%
                    }

                    #av-container.hide-controls #av-inner #gui #buttons,
                    #av-container.hide-controls #av-inner #gui:before,
                    #av-container.hide-controls #timeline {
                        display: none
                    }

                    #av-container.buttons-below #av-inner #slot #videoslot {
                        top: 0;
                        -webkit-transform: none;
                        transform: none
                    }

                    #av-container #videoslot video {
                        max-width: none
                    }

                    #av-container #slot video {
                        object-fit: contain
                    }

                    @-webkit-keyframes "fade-in" {
                        0% {
                            filter: alpha(opacity=0);
                            opacity: 0
                        }

                        to {
                            filter: alpha(opacity=100);
                            opacity: 1
                        }
                    }

                    @-moz-keyframes "fade-in" {
                        0% {
                            filter: alpha(opacity=0);
                            opacity: 0
                        }

                        to {
                            filter: alpha(opacity=100);
                            opacity: 1
                        }
                    }

                    @-o-keyframes fade-in {
                        0% {
                            filter: alpha(opacity=0);
                            opacity: 0
                        }

                        to {
                            filter: alpha(opacity=100);
                            opacity: 1
                        }
                    }

                    @keyframes "fade-in" {
                        0% {
                            filter: alpha(opacity=0);
                            opacity: 0
                        }

                        to {
                            filter: alpha(opacity=100);
                            opacity: 1
                        }
                    }

                    @-webkit-keyframes "av-loading-dash" {
                        0% {
                            stroke-dasharray: 1, 210;
                            stroke-dashoffset: 0
                        }

                        50% {
                            stroke-dasharray: 130, 220;
                            stroke-dashoffset: -50
                        }

                        to {
                            stroke-dasharray: 170, 220;
                            stroke-dashoffset: -210
                        }
                    }

                    @-moz-keyframes "av-loading-dash" {
                        0% {
                            stroke-dasharray: 1, 210;
                            stroke-dashoffset: 0
                        }

                        50% {
                            stroke-dasharray: 130, 220;
                            stroke-dashoffset: -50
                        }

                        to {
                            stroke-dasharray: 170, 220;
                            stroke-dashoffset: -210
                        }
                    }

                    @-o-keyframes av-loading-dash {
                        0% {
                            stroke-dasharray: 1, 210;
                            stroke-dashoffset: 0
                        }

                        50% {
                            stroke-dasharray: 130, 220;
                            stroke-dashoffset: -50
                        }

                        to {
                            stroke-dasharray: 170, 220;
                            stroke-dashoffset: -210
                        }
                    }

                    @keyframes "av-loading-dash" {
                        0% {
                            stroke-dasharray: 1, 210;
                            stroke-dashoffset: 0
                        }

                        50% {
                            stroke-dasharray: 130, 220;
                            stroke-dashoffset: -50
                        }

                        to {
                            stroke-dasharray: 170, 220;
                            stroke-dashoffset: -210
                        }
                    }

                    @-webkit-keyframes "av-loading-rotate" {
                        0% {
                            stroke: #fff;
                            -webkit-transform: rotate(0deg);
                            transform: rotate(0deg)
                        }

                        to {
                            stroke: currentColor;
                            -webkit-transform: rotate(1turn);
                            transform: rotate(1turn)
                        }
                    }

                    @-moz-keyframes "av-loading-rotate" {
                        0% {
                            stroke: #fff;
                            -webkit-transform: rotate(0deg);
                            transform: rotate(0deg)
                        }

                        to {
                            stroke: currentColor;
                            -webkit-transform: rotate(1turn);
                            transform: rotate(1turn)
                        }
                    }

                    @-o-keyframes av-loading-rotate {
                        0% {
                            stroke: #fff;
                            -webkit-transform: rotate(0deg);
                            transform: rotate(0deg)
                        }

                        to {
                            stroke: currentColor;
                            -webkit-transform: rotate(1turn);
                            transform: rotate(1turn)
                        }
                    }

                    @keyframes "av-loading-rotate" {
                        0% {
                            stroke: #fff;
                            -webkit-transform: rotate(0deg);
                            transform: rotate(0deg)
                        }

                        to {
                            stroke: currentColor;
                            -webkit-transform: rotate(1turn);
                            transform: rotate(1turn)
                        }
                    }

                    @media screen and (min-width: 1355px) and (max-width: 1430px) {
                        #av-close-btn-overlay {
                            float: left !important;
                        }
                    }
                </style> -->
                <div id="aniplayer_AV61613e403ff92a4a1008c1a4-1776061646717gui"></div>
                <div id="anibid"></div>
            </div>
        </div>
    </div>
    <script src="assets/player.js" async=""></script><iframe
        id="AVLoaderaniplayer_AV61613e403ff92a4a1008c1a4-1776061646717" allow="autoplay" src="about:blank"
        style="display: none;"></iframe><iframe name="__tcfapiLocator" src="about:blank"
        style="display: none; width: 0px; height: 0px; border: none; z-index: -1000; left: -1000px; top: -1000px;"></iframe><iframe
        name="__uspapiLocator" src="about:blank"
        style="display: none; width: 0px; height: 0px; border: none; z-index: -1000; left: -1000px; top: -1000px;"></iframe><iframe
        name="__gppLocator" src="about:blank"
        style="display: none; width: 0px; height: 0px; border: none; z-index: -1000; left: -1000px; top: -1000px;"></iframe><iframe
        name="googlefcInactive" src="about:blank"
        style="display: none; width: 0px; height: 0px; border: none; z-index: -1000; left: -1000px; top: -1000px;"></iframe><iframe
        name="googlefcLoaded" src="about:blank"
        style="display: none; width: 0px; height: 0px; border: none; z-index: -1000; left: -1000px; top: -1000px;"></iframe><iframe
        name="google_ads_top_frame" id="google_ads_top_frame"
        style="display: none; position: fixed; left: -999px; top: -999px; width: 0px; height: 0px;"></iframe><iframe
        src="https://www.google.com/recaptcha/api2/aframe" width="0" height="0" style="display: none;"></iframe><span
        id="PING_CONTENT_DLS_POPUP" style="display: none;"></span>
    <div
        style="background-color: transparent; border: none; bottom: 15px; display: block; margin: 0px; opacity: 1; padding: 0px; position: fixed; right: 15px; z-index: 2147483647;">
    </div>
</body>
</html>
