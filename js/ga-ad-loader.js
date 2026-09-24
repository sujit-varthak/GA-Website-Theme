// Fills every .ga-ad-slot placeholder (see ga_render_ad() in inc/helpers.php) with its real
// ad content, fetched fresh per page-load from ad-fetch.php. Moved client-side specifically so
// pages containing ad zones can be cached at Cloudflare's edge without freezing every visitor
// onto whichever ad happened to be picked when that cache entry was generated - each browser
// resolves its own ad independently, the same way it always did server-side, just one request
// later.
//
// Dispatches a 'ga-ad-loaded' CustomEvent on the slot once it's settled (detail: {zone,
// loaded}) - for any page-specific script that needs to react to a slot's real content
// arriving, since that content is no longer there synchronously the way a server-rendered
// ga_render_ad() call used to guarantee (see box-office.php's BOXOFFICE_STICKY_AD parallax
// script for the one call site that actually needs this).
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
                    el.dispatchEvent(new CustomEvent('ga-ad-loaded', { detail: { zone: zone, loaded: true } }));
                } else {
                    collapseSlot(el);
                    el.dispatchEvent(new CustomEvent('ga-ad-loaded', { detail: { zone: zone, loaded: false } }));
                }
            })
            .catch(function () {
                // Fetch itself failed (network error, etc.) - same collapse as "no ad
                // configured", rather than leaving a permanently blank reserved box.
                collapseSlot(el);
                el.dispatchEvent(new CustomEvent('ga-ad-loaded', { detail: { zone: zone, loaded: false } }));
            });
    }

    // ga_render_ad() (inc/helpers.php) can't know at render time whether this zone actually
    // has an active ad anymore - that's resolved here, after the fetch above completes - so it
    // always emits the placeholder with its min-width/min-height reserved, just in case. When
    // the fetch comes back empty, undo that reservation: hide the slot itself so it goes back
    // to taking up zero space, the same as ga_render_ad() echoing nothing at all used to do
    // before ad content moved client-side. A .ga-ad-collapse-wrapper ancestor (used by zones
    // that also need their own surrounding padding/margin to disappear, not just the slot -
    // see INNER_ARTICLE_END_AD in inner-page.php) gets hidden too, if one is marked.
    function collapseSlot(el) {
        el.style.display = 'none';
        var wrapper = el.closest('.ga-ad-collapse-wrapper');
        if (wrapper) {
            wrapper.style.display = 'none';
        }
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
