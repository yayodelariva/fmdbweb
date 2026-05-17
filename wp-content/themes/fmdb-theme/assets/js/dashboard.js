(function () {
    'use strict';

    document.querySelectorAll('.fmdb-tabs-nav .fmdb-tab-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tab = btn.getAttribute('data-tab');
            document.querySelectorAll('.fmdb-tab-btn').forEach(function (b) { b.classList.remove('active'); });
            document.querySelectorAll('.fmdb-tab-panel').forEach(function (p) { p.classList.remove('active'); });
            btn.classList.add('active');
            var panel = document.querySelector('.fmdb-tab-panel[data-panel="' + tab + '"]');
            if (panel) panel.classList.add('active');
        });
    });
})();
