<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/api-client.php';

$ga_return_to = ga_sanitize_local_path($_GET['return'] ?? null, '/');

// Direct/bare hits with no roadblock cookie (bookmarked link, crawler, etc.) skip straight to
// the destination — the ad only shows as part of the redirect chain from ga_maybe_show_roadblock_ad().
if (!isset($_COOKIE[GA_ROADBLOCK_COOKIE_NAME])) {
    header('Location: ' . $ga_return_to);
    exit;
}

// Same lookup ga_maybe_show_roadblock_ad() made when it set the cookie — re-fetched here
// (cheap: file-cached) rather than passed through the redirect, since the cached admin ad
// rarely changes mid-window. Falls back to the config-defined backup ad if the admin panel
// has nothing active right now.
$ga_is_mobile = ga_is_mobile();
$ga_ad = ga_fetch_roadblock_ad(!$ga_is_mobile);
$ga_ad = $ga_ad ?? (GA_AD_FALLBACKS['ROADBLOCK'] ?? null);

// No roadblock ad configured anywhere (admin panel or fallback) — nothing to show, send the
// visitor straight on rather than render an empty ad page.
if ($ga_ad === null) {
    header('Location: ' . $ga_return_to);
    exit;
}

$ga_ad_name = $ga_ad['name'] ?? 'Advertisement';
$ga_ad_type = $ga_ad['type'] ?? 'IMAGE';
$ga_ad_link = $ga_ad['landingUrl'] ?? '';
$ga_ad_image_desktop = $ga_ad['imageUrlDesktop'] ?? ($ga_ad['imageUrlMobile'] ?? '');
$ga_ad_image_mobile = $ga_ad['imageUrlMobile'] ?? ($ga_ad['imageUrlDesktop'] ?? '');
$ga_ad_script = $ga_ad['scriptCode'] ?? '';

// How long the interstitial stays up before auto-continuing to the original destination.
// Meta refresh (no-JS fallback) uses whole seconds; the JS timeout below uses the exact ms.
$ga_delay_ms = (int) ($ga_ad['roadblockDelayMs'] ?? 15000);
$ga_delay_seconds = max(1, (int) round($ga_delay_ms / 1000));

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>GreatAndhra - <?php echo ga_e($ga_ad_name); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="<?php echo (int) $ga_delay_seconds; ?>;url=<?php echo ga_e($ga_return_to); ?>">
    <link rel="canonical" href="https://www.greatandhra.com/">
    <style>
        body { margin: 0; }
        .footer { font-size: 11px; height: 50px; border-top: 1px dotted #343434; }
        a { text-decoration: none; color: #039; font-size: 11px; font-family: arial; }
        a:hover { text-decoration: underline; }
        .copyright { font-size: 11px; font-family: arial; }
        .ad-page-wrap { max-width: 1000px; margin: 0 auto; text-align: center; }
        /* 3-column header matching the reference: logo left, ad name
           center, countdown text + Skip button right (countdown text drops
           on narrow screens - the mobile reference doesn't show it, just
           logo/title/Skip, to keep the bar compact). */
        .ad-page-topnav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 16px;
            font-family: Arial, sans-serif;
            border-bottom: 1px solid #e5e5e5;
        }
        .ad-page-logo img { width: 130px; display: block; }
        .ad-page-title { flex: 1; min-width: 0; font-size: 15px; font-weight: 700; color: #111; text-align: center; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ad-page-skip-area { display: flex; align-items: center; gap: 10px; font-size: 12px; color: #666; white-space: nowrap; }
        .ad-page-brand { color: #d0021b; font-weight: 700; }
        .ad-page-skip-btn {
            display: inline-block;
            border: 1px solid #d0021b;
            color: #d0021b !important;
            font-weight: 700;
            font-size: 12px;
            padding: 4px 14px;
            border-radius: 14px;
            text-decoration: none !important;
        }
        .ad-page-skip-btn:hover { background: #d0021b; color: #fff !important; }
        @media (max-width: 480px) {
            .ad-page-countdown { display: none; }
            .ad-page-logo img { width: 100px; }
            .ad-page-title { font-size: 13px; }
        }
        /* Capped at 1000x600 - scales down to fit narrower viewports (mobile) via
           width:100%, but never exceeds this on desktop. */
        .ad-page-media img { max-width: 1000px; max-height: 600px; width: 100%; height: auto; }
    </style>
</head>

<body>
    <div class="ad-page-wrap">
        <div class="ad-page-topnav">
            <a class="ad-page-logo" href="<?php echo ga_e($ga_return_to); ?>">
                <img border="0" src="images/great_andhra.gif" alt="GreatAndhra">
            </a>
            <div class="ad-page-title"><?php echo ga_e($ga_ad_name); ?></div>
            <div class="ad-page-skip-area">
                <span class="ad-page-countdown"><span class="ad-page-brand">Great Andhra</span> will load in a few seconds.</span>
                <a class="ad-page-skip-btn" href="<?php echo ga_e($ga_return_to); ?>">Skip</a>
            </div>
        </div>

        <div class="ad-page-media" id="ad1">
            <?php if ($ga_ad_type === 'SCRIPT' && $ga_ad_script !== ''): ?>
                <?php echo $ga_ad_script; ?>
            <?php elseif ($ga_ad_image_desktop !== '' || $ga_ad_image_mobile !== ''): ?>
                <?php if ($ga_ad_link !== ''): ?>
                    <a href="<?php echo ga_e($ga_ad_link); ?>" target="_blank" rel="noopener">
                <?php endif; ?>
                        <picture>
                            <source media="(min-width: 769px)" srcset="<?php echo ga_e($ga_ad_image_desktop); ?>">
                            <img src="<?php echo ga_e($ga_ad_image_mobile); ?>" alt="<?php echo ga_e($ga_ad_name); ?>">
                        </picture>
                <?php if ($ga_ad_link !== ''): ?>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
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
        }, <?php echo (int) $ga_delay_ms; ?>);
    </script>
</body>

</html>
