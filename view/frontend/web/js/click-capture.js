/**
 * UpturnStudio_GoogleFeed
 *
 * Remembers the Google Ads click a shopper arrived on, so the order they place can be tied back to it.
 *
 * Plain script on purpose: no RequireJS or Alpine, so it runs unchanged on Luma and Hyvä, and it works on pages
 * served from full-page cache because everything happens in the browser.
 */
(function () {
    'use strict';

    var PENDING_KEY = 'upturnstudio_gf_click_pending',
        ID_PARAMS = ['gclid', 'gbraid', 'wbraid'],
        ID_PATTERN = /^[A-Za-z0-9_\-.]{1,255}$/,
        settings = document.getElementById('upturnstudio-googlefeed-click'),
        click;

    if (!settings) {
        return;
    }

    /**
     * Click data from the landing URL, or null when the URL carries no click ID.
     */
    function readFromUrl() {
        var params = new URLSearchParams(window.location.search),
            data = {},
            found = false;

        ID_PARAMS.forEach(function (name) {
            var value = params.get(name);

            if (value && ID_PATTERN.test(value)) {
                data[name] = value;
                found = true;
            }
        });

        if (!found) {
            return null;
        }

        data.ts = Date.now();
        // Path only: query strings can carry personal data that has no business in an order record.
        data.url = (window.location.origin + window.location.pathname).slice(0, 2000);

        if (settings.dataset.sku) {
            data.sku = settings.dataset.sku;
        }

        return data;
    }

    function readPending() {
        try {
            return JSON.parse(window.sessionStorage.getItem(PENDING_KEY));
        } catch (e) {
            return null;
        }
    }

    function setPending(data) {
        try {
            if (data) {
                window.sessionStorage.setItem(PENDING_KEY, JSON.stringify(data));
            } else {
                window.sessionStorage.removeItem(PENDING_KEY);
            }
        } catch (e) {
            // Storage can be blocked; the click is then simply not carried to the next page.
        }
    }

    function hasConsent() {
        return settings.dataset.consentRequired !== '1' ||
            document.cookie.indexOf('user_allowed_save_cookie=') !== -1;
    }

    click = readFromUrl() || readPending();

    if (!click) {
        return;
    }

    if (!hasConsent()) {
        // Hold the click for this tab only and try again once the shopper has accepted cookies.
        setPending(click);

        return;
    }

    document.cookie = settings.dataset.cookie + '=' + encodeURIComponent(JSON.stringify(click)) +
        '; path=/; max-age=' + (parseInt(settings.dataset.lifetime, 10) || 90) * 86400 +
        '; SameSite=Lax' + (window.location.protocol === 'https:' ? '; Secure' : '');
    setPending(null);
}());
