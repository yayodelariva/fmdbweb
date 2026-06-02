(function () {
    'use strict';

    // On homepage: redirect map state click to mapa-interactivo with ?estado= param
    document.addEventListener('fmdb:stateSelected', function (e) {
        var state = e.detail.state;
        var slug  = state.toLowerCase().replace(/\s+/g, '-');
        window.location.href = '/mapa-interactivo/?estado=' + encodeURIComponent(slug);
    });
})();
