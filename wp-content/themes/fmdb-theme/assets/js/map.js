(function () {
  'use strict';

  // fmdbMapData is passed via wp_localize_script:
  // { teams: {...}, leagues: {...}, asociaciones: {...} }   ('Estado' => count)
  const teamCounts       = (window.fmdbMapData && window.fmdbMapData.teams)        || {};
  const leagueCounts     = (window.fmdbMapData && window.fmdbMapData.leagues)      || {};
  const asociacionCounts = (window.fmdbMapData && window.fmdbMapData.asociaciones) || {};

  // "Todos" shading aggregates equipos + ligas + asociaciones per state.
  const totalCounts = (function () {
    const out = {};
    [teamCounts, leagueCounts, asociacionCounts].forEach(function (src) {
      Object.keys(src).forEach(function (state) {
        out[state] = (out[state] || 0) + (src[state] || 0);
      });
    });
    return out;
  })();

  // Mode controls which dataset shades the map and what the tooltip says.
  // Values: 'equipos' | 'ligas' | 'asociaciones' | 'todos' (todos == equipos shading)
  // Sync with the page's data-view on load so the map matches the active toggle
  // (the page defaults to "todos" but the JS used to assume "equipos").
  let mode = (function () {
    const root = document.querySelector('.fmdb-equipos');
    const v = root && root.getAttribute('data-view');
    return (v === 'ligas' || v === 'asociaciones' || v === 'todos') ? v : 'equipos';
  })();

  function activeCounts() {
    if (mode === 'ligas')        return leagueCounts;
    if (mode === 'asociaciones') return asociacionCounts;
    if (mode === 'todos')        return totalCounts;
    return teamCounts;
  }

  function activeLabel(count) {
    if (mode === 'ligas') {
      return count ? count + ' liga' + (count !== 1 ? 's' : '') : 'Sin ligas aún';
    }
    if (mode === 'asociaciones') {
      return count ? count + ' asociaci' + (count !== 1 ? 'ones' : 'ón') : 'Sin asociaciones aún';
    }
    return count ? count + ' equipo' + (count !== 1 ? 's' : '') : 'Sin equipos aún';
  }

  // "Todos" mode: aggregate label like "1 asociación | 0 ligas | 2 equipos".
  function todosLabel(state) {
    var a = asociacionCounts[state] || 0;
    var l = leagueCounts[state]     || 0;
    var e = teamCounts[state]       || 0;
    return a + ' asociaci' + (a !== 1 ? 'ones' : 'ón')
         + ' | ' + l + ' liga' + (l !== 1 ? 's' : '')
         + ' | ' + e + ' equipo' + (e !== 1 ? 's' : '');
  }

  function intensityClass(count) {
    if (count <= 0)  return '';
    if (count <= 2)  return 'few-teams';
    if (count <= 5)  return 'some-teams';
    if (count <= 10) return 'many-teams';
    return 'most-teams';
  }

  const INTENSITY_CLASSES = ['has-teams', 'few-teams', 'some-teams', 'many-teams', 'most-teams'];

  function shadeStates(states) {
    const counts = activeCounts();
    states.forEach(function (path) {
      const name  = path.getAttribute('data-state');
      const count = counts[name] || 0;
      INTENSITY_CLASSES.forEach(function (c) { path.classList.remove(c); });
      if (count > 0) {
        path.classList.add('has-teams');
        path.classList.add(intensityClass(count));
      }
    });
    updateLegendEmpty();
  }

  function updateLegendEmpty() {
    const el = document.getElementById('fmdb-map-legend-empty');
    if (!el) return;
    if (mode === 'ligas')             el.textContent = 'Sin ligas';
    else if (mode === 'asociaciones') el.textContent = 'Sin asociaciones';
    else if (mode === 'equipos')      el.textContent = 'Sin equipos';
    else                              el.textContent = 'Sin información';
  }

  function initMap() {
    const svg = document.getElementById('fmdb-mexico-map');
    if (!svg) return;

    const tooltip    = document.getElementById('fmdb-tooltip');
    const tipBg      = document.getElementById('fmdb-tooltip-bg');
    const tipName    = document.getElementById('fmdb-tooltip-name');
    const tipTeams   = document.getElementById('fmdb-tooltip-teams');
    const states     = svg.querySelectorAll('.fmdb-state');

    shadeStates(states);

    // React to the equipos page's view toggle
    document.addEventListener('fmdb:viewModeChanged', function (e) {
      mode = (e.detail && e.detail.view) || 'equipos';
      shadeStates(states);
    });

    // Tooltip helpers
    function showTooltip(path, evt) {
      const name  = path.getAttribute('data-state');
      tipName.textContent = name;
      if (mode === 'todos') {
        tipTeams.textContent = todosLabel(name);
      } else {
        tipTeams.textContent = activeLabel(activeCounts()[name] || 0);
      }

      // Position tooltip near cursor within SVG coordinate space
      const svgRect = svg.getBoundingClientRect();
      const vbW = 800, vbH = 600;
      const scaleX = vbW / svgRect.width;
      const scaleY = vbH / svgRect.height;
      const mx = (evt.clientX - svgRect.left) * scaleX;
      const my = (evt.clientY - svgRect.top)  * scaleY;

      // Wider box in "todos" to fit "N equipos | N ligas | N asociaciones".
      const tipW = mode === 'todos' ? 340 : 200;
      const tipH = 62, pad = 10;
      const tx = Math.min(mx + pad, vbW - tipW - pad);
      const ty = Math.max(my - tipH - pad, pad);

      tipBg.setAttribute('x', tx);
      tipBg.setAttribute('y', ty);
      tipBg.setAttribute('width', tipW);
      tipBg.setAttribute('height', tipH);
      tipName.setAttribute('x',  tx + tipW / 2);
      tipName.setAttribute('y',  ty + 26);
      tipTeams.setAttribute('x', tx + tipW / 2);
      tipTeams.setAttribute('y', ty + 50);

      tooltip.setAttribute('visibility', 'visible');
    }

    function hideTooltip() {
      tooltip.setAttribute('visibility', 'hidden');
    }

    // Active state tracking
    let activeState = null;

    states.forEach(function (path) {
      path.addEventListener('mousemove', function (e) { showTooltip(path, e); });
      path.addEventListener('mouseleave', hideTooltip);

      path.addEventListener('click', function () {
        // In "todos" view the map is read-only — hover-only tooltips.
        if (mode === 'todos') return;
        const stateName = path.getAttribute('data-state');

        if (activeState === path) {
          // Deselect
          path.classList.remove('active');
          activeState = null;
          document.dispatchEvent(new CustomEvent('fmdb:stateSelected', { detail: { state: null } }));
        } else {
          if (activeState) activeState.classList.remove('active');
          path.classList.add('active');
          activeState = path;
          document.dispatchEvent(new CustomEvent('fmdb:stateSelected', { detail: { state: stateName } }));
        }
      });

      // Keyboard support
      path.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          path.click();
        }
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMap);
  } else {
    initMap();
  }
})();
