// Fills every .ga-ad-slot placeholder (see ga_render_ad() in inc/helpers.php) with its real
// ad content, fetched fresh per page-load from ad-fetch.php. Moved client-side specifically so
// pages containing ad zones can be cached at Cloudflare's edge without freezing every visitor
// onto whichever ad happened to be picked when that cache entry was generated - each browser
// resolves its own ad independently, the same way it always did server-side, just one request
// later.
(function () {
    'use strict';

    function loadSlot(el) {
        var zone = el.getAttribute('data-ad-zone');
        if (!zone) {
            return;
        }
        var dimensionZone = el.getAttribute('data-ad-dimension-zone');
        var url = 'ad-fetch.php?zone=' + encodeURIComponent(zone);
        if (dimensionZone) {
            url += '&dimensionZone=' + encodeURIComponent(dimensionZone);
        }

        fetch(url, { credentials: 'omit' })
            .then(function (res) {
                return res.ok ? res.json() : null;
            })
            .then(function (data) {
                if (data && data.html) {
                    el.innerHTML = data.html;
                }
            })
            .catch(function () {
                // No ad this load - leave the placeholder empty rather than surface an error,
                // same as ga_build_ad_html() returning '' server-side previously did.
            });
    }

    function loadAllSlots() {
        var slots = document.querySelectorAll('.ga-ad-slot[data-ad-zone]');
        for (var i = 0; i < slots.length; i++) {
            // The interstitial overlay's own ad slot is deliberately skipped here - most
            // visits never become eligible to see it at all (the frequency cookie usually
            // already blocks it), so loading it unconditionally on every page view would waste
            // a fetch for content that's never shown. ga-interstitial.js loads it itself, via
            // window.gaLoadAdSlot below, only once it actually decides to reveal the overlay.
            if (slots[i].closest('#ga-interstitial-overlay')) {
                continue;
            }
            loadSlot(slots[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadAllSlots);
    } else {
        loadAllSlots();
    }

    // Exposed so ga-interstitial.js (revealed later, on a timer or a client-side transition
    // match - see ga_render_interstitial_overlay()) can load its own ad slot the moment it
    // actually becomes eligible, without waiting for/duplicating the DOMContentLoaded pass
    // above.
    window.gaLoadAdSlot = loadSlot;
})();
