<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/helpers.php';

$ga_return_to = ga_sanitize_local_path($_GET['return'] ?? null, '/');

// Direct/bare hits with no roadblock cookie (bookmarked link, crawler, etc.) skip straight to
// the destination — the ad only shows as part of the redirect chain from ga_maybe_show_roadblock_ad().
if (!isset($_COOKIE[GA_ROADBLOCK_COOKIE_NAME])) {
    header('Location: ' . $ga_return_to);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>GreatAndhra - <?php echo ga_e(GA_ROADBLOCK_AD_NAME); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="15;url=<?php echo ga_e($ga_return_to); ?>">
    <link rel="canonical" href="https://www.greatandhra.com/">
    <style>
        body { margin: 0; }
        .footer { font-size: 11px; height: 50px; border-top: 1px dotted #343434; }
        a { text-decoration: none; color: #039; font-size: 11px; font-family: arial; }
        a:hover { text-decoration: underline; }
        .copyright { font-size: 11px; font-family: arial; }
        .ad-page-wrap { max-width: 800px; margin: 0 auto; text-align: center; }
        .ad-page-banner { background: #dbdbdb; font-family: Arial; font-size: 12px; padding: 8px 0; }
        .ad-page-topnav { display: flex; align-items: center; justify-content: center; padding: 10px 0; font-family: Arial; font-size: 12px; }
        .ad-page-topnav img { width: 200px; }
        .ad-page-topnav a { padding-left: 12px; color: #000; }
        .ad-page-media img { max-width: min(100%, 420px); height: auto; }
        @media (min-width: 769px) {
            .ad-page-media img { max-width: min(100%, 900px); }
        }
    </style>
</head>

<body>
    <div class="ad-page-wrap">
        <div class="ad-page-topnav">
            <a href="<?php echo ga_e($ga_return_to); ?>"><img border="0" src="images/great_andhra.gif" alt="GreatAndhra"></a>
            <a href="<?php echo ga_e($ga_return_to); ?>">click here to go to greatandhra.com</a>
        </div>
        <div class="ad-page-banner">Advertisement</div>

        <div class="ad-page-media" id="ad1">
            <a href="<?php echo ga_e(GA_ROADBLOCK_AD_LINK); ?>" target="_blank" rel="noopener">
                <picture>
                    <source media="(min-width: 769px)" srcset="<?php echo ga_e(GA_ROADBLOCK_AD_IMAGE_DESKTOP); ?>">
                    <img src="<?php echo ga_e(GA_ROADBLOCK_AD_IMAGE_MOBILE); ?>" alt="<?php echo ga_e(GA_ROADBLOCK_AD_NAME); ?>">
                </picture>
            </a>
        </div>

        <noscript>
            <p><a href="<?php echo ga_e($ga_return_to); ?>">Click here to go to greatandhra.com</a></p>
        </noscript>

        <div class="footer">
            <a href="https://www.greatandhra.com/disclaimer.php">Disclaimer</a>&nbsp;|&nbsp;
            <a href="https://www.greatandhra.com/advertise.php">Advertise with Us</a>&nbsp;|&nbsp;
            <a href="https://www.greatandhra.com/privacy.php">Privacy Policy</a>
            <br>
            <span class="copyright">Copyright &#169; 2026 India Brains Infotech LLC. All rights reserved.</span>
        </div>
    </div>

    <script>
        setTimeout(function () {
            window.location.href = <?php echo json_encode($ga_return_to); ?>;
        }, 10000);
    </script>
</body>

</html>
