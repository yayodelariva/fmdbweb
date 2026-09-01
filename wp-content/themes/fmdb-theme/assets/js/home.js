(function () {
    'use strict';

    // On homepage: redirect map state click to mapa-interactivo with ?estado= param
    document.addEventListener('fmdb:stateSelected', function (e) {
        var state = e.detail.state;
        var slug  = state.toLowerCase().replace(/\s+/g, '-');
        window.location.href = '/mapa-interactivo/?estado=' + encodeURIComponent(slug);
    });

    // Announcement modal: show once per day via localStorage.
    (function () {
        var MODAL_KEY = 'fmdb_announce_cmdx2026';
        var DISMISS_MS = 24 * 60 * 60 * 1000; // 24 h

        function shouldShow() {
            try {
                var ts = localStorage.getItem(MODAL_KEY);
                if (!ts) return true;
                return (Date.now() - parseInt(ts, 10)) > DISMISS_MS;
            } catch (e) { return true; }
        }

        function dismiss() {
            try { localStorage.setItem(MODAL_KEY, String(Date.now())); } catch (e) {}
            var modal = document.getElementById('fmdb-announce-modal');
            if (modal) modal.hidden = true;
        }

        document.addEventListener('DOMContentLoaded', function () {
            var modal    = document.getElementById('fmdb-announce-modal');
            var backdrop = modal && modal.querySelector('.fmdb-announce-modal__backdrop');
            var closeBtn = modal && modal.querySelector('.fmdb-announce-modal__close');
            var dismissBtn = modal && modal.querySelector('.fmdb-announce-modal__dismiss');

            if (!modal) return;

            if (shouldShow()) {
                // Small delay so it doesn't flash on top of page load
                setTimeout(function () { modal.hidden = false; }, 600);
            }

            if (closeBtn)   closeBtn.addEventListener('click', dismiss);
            if (dismissBtn) dismissBtn.addEventListener('click', dismiss);
            if (backdrop)   backdrop.addEventListener('click', dismiss);

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !modal.hidden) dismiss();
            });
        });
    }());
})();
