<?php
/**
 * Small misc behaviors that don't belong in their own file.
 */

// Disable comments and pings on news posts
add_filter( 'comments_open', function ( $open, $post_id ) {
    if ( get_post_type( $post_id ) === 'post' ) return false;
    return $open;
}, 10, 2 );

add_filter( 'pings_open', function ( $open, $post_id ) {
    if ( get_post_type( $post_id ) === 'post' ) return false;
    return $open;
}, 10, 2 );

// Hide noisy plugin menu items
add_action( 'admin_menu', function () {
    remove_menu_page( 'jetpack' );
    remove_menu_page( 'mailpoet-homepage' );
    remove_menu_page( 'mailpoet-newsletters' );
    remove_menu_page( 'jetrails-site-assistant' );
    remove_menu_page( 'jetrails' );
}, 999 );

add_action( 'admin_head', function () {
    echo '<style>#toplevel_page_user-registration-dashboard{display:none!important}</style>';
} );

// Translate Kadence parent-theme strings that ship with es_ES only
// (site runs es_MX so WP doesn't fall back to es_ES).
add_filter( 'gettext', function ( $translation, $text, $domain ) {
    if ( $domain !== 'kadence' ) return $translation;
    static $map = [
        'Search Results for: %s' => 'Resultados de búsqueda para: %s',
        'Nothing Found'          => 'Sin resultados',
        'Sorry, but nothing matched your search terms. Please try again with some different keywords.'
            => 'Lo sentimos, no encontramos resultados para tu búsqueda. Intenta con otras palabras.',
    ];
    return $map[ $text ] ?? $translation;
}, 10, 3 );
