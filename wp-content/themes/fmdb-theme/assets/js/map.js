(function () {
  'use strict';

  // fmdbMapData is passed via wp_localize_script:
  // { teams: { 'Jalisco': 12, ... }, leagues: { 'Jalisco': 2, ... } }
  const teamCounts   = (window.fmdbMapData && window.fmdbMapData.teams)   || {};
  const leagueCounts = (window.fmdbMapData && window.fmdbMapData.leagues) || {};

  // Mode controls which dataset shades the map and what the tooltip says.
  // Values: 'equipos' | 'ligas' | 'todos' (todos == equipos shading)
  let mode = 'equipos';

  function activeCounts() {
    return mode === 'ligas' ? leagueCounts : teamCounts;
  }

  function activeLabel(count) {
    if (mode === 'ligas') {
      return count ? count + ' liga' + (count !== 1 ? 's' : '') : 'Sin ligas aún';
    }
    return count ? count + ' equipo' + (count !== 1 ? 's' : '') : 'Sin equipos aún';
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
      const count = activeCounts()[name] || 0;
      tipName.textContent  = name;
      tipTeams.textContent = activeLabel(count);

      // Position tooltip near cursor within SVG coordinate space
      const svgRect = svg.getBoundingClientRect();
      const vbW = 800, vbH = 600;
      const scaleX = vbW / svgRect.width;
      const scaleY = vbH / svgRect.height;
      const mx = (evt.clientX - svgRect.left) * scaleX;
      const my = (evt.clientY - svgRect.top)  * scaleY;

      const tipW = 160, tipH = 44, pad = 8;
      const tx = Math.min(mx + pad, vbW - tipW - pad);
      const ty = Math.max(my - tipH - pad, pad);

      tipBg.setAttribute('x', tx);
      tipBg.setAttribute('y', ty);
      tipBg.setAttribute('width', tipW);
      tipBg.setAttribute('height', tipH);
      tipName.setAttribute('x',  tx + tipW / 2);
      tipName.setAttribute('y',  ty + 16);
      tipTeams.setAttribute('x', tx + tipW / 2);
      tipTeams.setAttribute('y', ty + 32);

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
