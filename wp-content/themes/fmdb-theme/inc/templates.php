<?php
/**
 * Template routing overrides.
 */

// Force our own single-tribe_events.php to win over TEC's template hijack
add_filter( 'template_include', function ( $template ) {
    if ( is_singular( 'tribe_events' ) ) {
        $custom = locate_template( 'single-tribe_events.php' );
        if ( $custom ) return $custom;
    }
    return $template;
}, 999 );
