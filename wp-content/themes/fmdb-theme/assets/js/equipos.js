(function () {
    'use strict';

    var activeState      = null;
    var activeCategories = [];
    var activeLigaState  = null;

    var equiposEl             = document.querySelector('.fmdb-equipos');
    var filterStateSelect     = document.getElementById('filter-state');
    var filterCategoryBoxes   = document.querySelectorAll('.filter-category');
    var clearBtn              = document.getElementById('fmdb-clear-filters');
    var stateListItems        = document.querySelectorAll('.fmdb-state-list__item');
    var noResults             = document.getElementById('fmdb-no-results');
    var filterStateLigasSelect = document.getElementById('filter-state-ligas');
    var clearBtnLigas         = document.getElementById('fmdb-clear-filters-ligas');
    var noResultsLigas        = document.getElementById('fmdb-ligas-no-results');

    function applyFilters() {
        var stateBlocks = document.querySelectorAll('.fmdb-state-block');
        var anyVisible  = false;
        var stateHasBlock = false;

        stateBlocks.forEach(function (block) {
            var blockState = block.getAttribute('data-state');
            var stateMatch = !activeState || blockState === activeState;

            if (stateMatch && activeState) stateHasBlock = true;

            if (!stateMatch) {
                block.classList.add('hidden');
                return;
            }

            // Filter team cards by category directly under this state
            var cards         = block.querySelectorAll('.fmdb-team-card');
            var blockVisible  = false;

            cards.forEach(function (card) {
                var cardCats = card.getAttribute('data-categories').split(',').map(function (c) { return c.trim(); });
                var catMatch = activeCategories.length === 0 ||
                    activeCategories.some(function (c) { return cardCats.indexOf(c) !== -1; });

                card.classList.toggle('hidden', !catMatch);
                if (catMatch) blockVisible = true;
            });

            block.classList.toggle('hidden', !blockVisible);
            if (blockVisible) anyVisible = true;
        });

        if (noResults) {
            if (anyVisible) {
                noResults.style.display = 'none';
            } else {
                noResults.textContent = (activeState && !stateHasBlock)
                    ? 'No se encontraron equipos en este estado.'
                    : 'No se encontraron equipos con los filtros seleccionados.';
                noResults.style.display = 'block';
            }
        }
    }

    function applyLigasFilters(state) {
        var cards = document.querySelectorAll('.fmdb-league-card');
        var anyVisible = false;
        cards.forEach(function (card) {
            var cardState = card.getAttribute('data-state') || '';
            var match = !state || cardState === state;
            card.classList.toggle('hidden', !match);
            if (match) anyVisible = true;
        });
        if (noResultsLigas) {
            noResultsLigas.style.display = (cards.length && !anyVisible) ? 'block' : 'none';
        }
    }

    function setActiveState(state) {
        activeState = state || null;

        // Sync dropdown
        if (filterStateSelect) filterStateSelect.value = state || '';

        // Sync sidebar
        stateListItems.forEach(function (li) {
            li.classList.toggle('active', li.getAttribute('data-state') === state);
        });

        // Sync SVG map
        document.querySelectorAll('.fmdb-state').forEach(function (path) {
            path.classList.toggle('active', path.getAttribute('data-state') === state);
        });

        applyFilters();

        // In todos view, also filter leagues by the same state
        if (equiposEl && equiposEl.getAttribute('data-view') === 'todos') {
            activeLigaState = state || null;
            if (filterStateLigasSelect) filterStateLigasSelect.value = state || '';
            applyLigasFilters(activeLigaState);
        }

        // Scroll to first visible state block
        if (state) {
            var target = document.querySelector('.fmdb-state-block[data-state="' + CSS.escape(state) + '"]');
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // Dropdown → filter
    if (filterStateSelect) {
        filterStateSelect.addEventListener('change', function () {
            setActiveState(this.value);
        });
    }

    // Category checkboxes → filter
    filterCategoryBoxes.forEach(function (box) {
        box.addEventListener('change', function () {
            activeCategories = Array.from(filterCategoryBoxes)
                .filter(function (b) { return b.checked; })
                .map(function (b) { return b.value; });
            applyFilters();
        });
    });

    // Sidebar state list → filter
    stateListItems.forEach(function (li) {
        li.addEventListener('click', function () {
            var state = li.getAttribute('data-state');
            setActiveState(activeState === state ? null : state);
        });
    });

    // SVG map click → filter
    document.addEventListener('fmdb:stateSelected', function (e) {
        setActiveState(e.detail.state);
    });

    // Clear filters
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            filterCategoryBoxes.forEach(function (b) { b.checked = false; });
            activeCategories = [];
            setActiveState(null);
        });
    }

    // Read ?estado= from URL on load. Match against SVG paths (all 32 states),
    // not just the sidebar list (which excludes states with no teams).
    var params = new URLSearchParams(window.location.search);
    var urlState = params.get('estado');
    if (urlState) {
        var target = urlState.toLowerCase();
        document.querySelectorAll('.fmdb-state').forEach(function (path) {
            var name = path.getAttribute('data-state');
            if (name && name.toLowerCase().replace(/\s+/g, '-') === target) {
                setActiveState(name);
            }
        });
    }

    // Map view toggle (Equipos / Ligas / Todos)
    var toggleBtns = document.querySelectorAll('.fmdb-equipos__toggle-btn');

    function setView(view) {
        if (!equiposEl) return;
        equiposEl.setAttribute('data-view', view);
        toggleBtns.forEach(function (b) {
            var on = b.dataset.view === view;
            b.classList.toggle('active', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        document.dispatchEvent(new CustomEvent('fmdb:viewModeChanged', { detail: { view: view } }));
    }

    toggleBtns.forEach(function (btn) {
        btn.addEventListener('click', function () { setView(btn.dataset.view); });
    });

    // Allow ?view=equipos|ligas|todos in the URL
    var urlView = params.get('view');
    if (urlView === 'equipos' || urlView === 'ligas' || urlView === 'todos') {
        setView(urlView);
    }

    // Ligas state filter (Ligas panel only)
    if (filterStateLigasSelect) {
        filterStateLigasSelect.addEventListener('change', function () {
            activeLigaState = this.value || null;
            applyLigasFilters(activeLigaState);
        });
    }

    if (clearBtnLigas) {
        clearBtnLigas.addEventListener('click', function () {
            activeLigaState = null;
            if (filterStateLigasSelect) filterStateLigasSelect.value = '';
            applyLigasFilters(null);
        });
    }

})();
