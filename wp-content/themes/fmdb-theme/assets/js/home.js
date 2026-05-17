(function () {
    'use strict';

    // On homepage: redirect map state click to equipos page with ?estado= param
    document.addEventListener('fmdb:stateSelected', function (e) {
        var state = e.detail.state;
        var slug  = state.toLowerCase().replace(/\s+/g, '-');
        window.location.href = '/equipos-y-ligas/?estado=' + encodeURIComponent(slug);
    });
})();
