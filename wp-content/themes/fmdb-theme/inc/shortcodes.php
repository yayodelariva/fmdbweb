<?php
/**
 * Theme shortcodes.
 */

// [fmdb_map] — Mexico SVG map with team/league legend
add_shortcode( 'fmdb_map', function () {
    $svg_path = get_stylesheet_directory() . '/assets/mexico-map.svg';
    if ( ! file_exists( $svg_path ) ) {
        return '<p>Mapa no disponible.</p>';
    }
    $svg = file_get_contents( $svg_path );
    return '<div class="fmdb-map-wrapper">'
        . $svg
        . '<div class="fmdb-map-legend">'
        . '<span><i style="background:#D3D1C7"></i>Sin equipos</span>'
        . '<span><i style="background:#9FE1CB"></i>1-2 equipos</span>'
        . '<span><i style="background:#5DCAA5"></i>3-5 equipos</span>'
        . '<span><i style="background:#1D9E75"></i>6-10 equipos</span>'
        . '<span><i style="background:#085041"></i>10+ equipos</span>'
        . '</div>'
        . '</div>';
} );
