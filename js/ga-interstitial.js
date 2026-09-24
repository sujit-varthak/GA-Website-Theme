// Client-side port of the interstitial ad's per-visitor eligibility decision - previously
// ga_prepare_interstitial_ad() (inc/helpers.php) made this call server-side, reading the
// frequency cookie and the HTTP Referer header, which meant every page that could show an
// interstitial (home, article, list/category, box office - effectively the whole site) was
// unsafe to cache at Cloudflare's edge: the decision baked into a cached response would apply
// to every visitor who happened to hit that cache entry, not just the one it was computed for.
//
// The overlay container is now always rendered (hidden) by ga_render_interstitial_overlay(),
// carrying its config as data-interstitial-* attributes - this script alone decides whether to
// reveal it, using the exact same rules PHP used to apply.
(function () {
    'use strict';

    function getCookie(name) {
        var escaped = name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1');
        var match = document.cookie.match(new RegExp('(?:^|; )' + escaped + '=([^;]*)'));
        return match ? decodeURIComponent(match[1]) : null;
    }

    function setCookie(name, value, maxAgeSeconds) {
        document.cookie = name + '=' + value + '; max-age=' + maxAgeSeconds + '; path=/';
    }

    // Client-side port of ga_classify_referer_page_type() (inc/helpers.php), using
    // document.referrer in place of the HTTP Referer header - same data, different vantage
    // point. Deliberately kept bug-for-bug identical to the PHP version, including not
    // recognizing the site's current id-less article URLs (movies/reviews/slug - the ARTICLE
    // check below only matches the old numeric/UUID-prefixed form), rather than silently
    // changing TRANSITION-trigger matching behavior as a side effect of this refactor. Worth
    // fixing in both places together, separately.
    function classifyRefererPageType() {
        var referer = document.referrer;
        if (!referer) {
            return null;
        }
        var a = document.createElement('a');
        a.href = referer;
        if (a.hostname.toLowerCase() !== location.hostname.toLowerCase()) {
            return null;
        }
        var path = a.pathname.replace(/^\/+|\/+$/g, '');
        if (path === '' || path === 'index.php') {
            return 'HOME';
        }
        if (path === 'box-office' || path.indexOf('box-office.php') === 0) {
            return 'BOXOFFICE';
        }
        if (
            /^([0-9]+|[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})(\/|$)/.test(path)
            || path.indexOf('inner-page.php') === 0
        ) {
            return 'ARTICLE';
        }
        return 'LISTPAGE';
    }

    function reveal(overlay) {
        overlay.style.display = 'flex';
        var slot = overlay.querySelector('.ga-ad-slot[data-ad-zone]');
        if (slot && window.gaLoadAdSlot) {
            window.gaLoadAdSlot(slot);
        }
    }

    function init() {
        var overlay = document.getElementById('ga-interstitial-overlay');
        if (!overlay) {
            return;
        }

        var closeBtn = overlay.querySelector('.ga-interstitial-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                overlay.style.display = 'none';
            });
        }

        var cookieName = overlay.getAttribute('data-interstitial-cookie-name');
        var cookieTtl = parseInt(overlay.getAttribute('data-interstitial-cookie-ttl'), 10) || 0;
        if (!cookieName || getCookie(cookieName) !== null) {
            return; // already seen it within the current frequency window
        }

        var triggerType = overlay.getAttribute('data-interstitial-trigger-type');

        if (triggerType === 'TIMER') {
            var timerSeconds = parseInt(overlay.getAttribute('data-interstitial-timer-seconds'), 10) || 10;
            setTimeout(function () {
                setCookie(cookieName, '1', cookieTtl);
                reveal(overlay);
            }, timerSeconds * 1000);
            return;
        }

        var fromPage = overlay.getAttribute('data-interstitial-from-page') || 'ANY';
        var toPage = overlay.getAttribute('data-interstitial-to-page') || 'ANY';
        var currentPage = overlay.getAttribute('data-interstitial-current-page') || 'ANY';
        var refererPageType = classifyRefererPageType();
        var matches = (fromPage === 'ANY' || fromPage === refererPageType)
            && (toPage === 'ANY' || toPage === currentPage);

        if (!matches) {
            // Doesn't set the cookie either - a later matching transition later in the same
            // session (i.e. this same script running again on a later page) can still trigger
            // it, same as the original server-side behavior.
            return;
        }

        setCookie(cookieName, '1', cookieTtl);
        reveal(overlay);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
