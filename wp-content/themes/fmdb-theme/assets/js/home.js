(function () {
    'use strict';

    // On homepage: redirect map state click to mapa-interactivo with ?estado= param
    document.addEventListener('fmdb:stateSelected', function (e) {
        var state = e.detail.state;
        var slug  = state.toLowerCase().replace(/\s+/g, '-');
        window.location.href = '/mapa-interactivo/?estado=' + encodeURIComponent(slug);
    });


    // Tutor accounts announcement modal — show once per browser
    var MODAL_KEY = 'fmdb_tutor_announce_v1';
    var modal = document.getElementById('fmdb-announce-modal');
    if (modal) {
        try {
            if (!localStorage.getItem(MODAL_KEY)) {
                modal.removeAttribute('hidden');
            }
        } catch (e) { modal.removeAttribute('hidden'); }

        function closeModal() {
            modal.setAttribute('hidden', '');
            try { localStorage.setItem(MODAL_KEY, '1'); } catch (e) {}
        }

        modal.querySelector('.fmdb-announce-modal__backdrop').addEventListener('click', closeModal);
        modal.querySelector('.fmdb-announce-modal__close').addEventListener('click', closeModal);
        modal.querySelector('.fmdb-announce-modal__cta').addEventListener('click', closeModal);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hasAttribute('hidden')) closeModal();
        });
    }

})();
