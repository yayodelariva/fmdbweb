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
    remove_menu_page( 'edit-comments.php' );
    if ( ! current_user_can( 'manage_options' ) ) {
        remove_menu_page( 'edit.php?post_type=product' );
    }

    // Editor Noticias: only Entradas — hide FMDB CPTs and Events
    $user = wp_get_current_user();
    if ( in_array( 'editor_noticias', (array) $user->roles, true ) ) {
        remove_menu_page( 'edit.php?post_type=fmdb_team' );
        remove_menu_page( 'edit.php?post_type=fmdb_league' );
        remove_menu_page( 'edit.php?post_type=fmdb_asociacion' );
        remove_menu_page( 'edit.php?post_type=fmdb_seleccion' );
        remove_menu_page( 'edit.php?post_type=tribe_events' );
    }

    global $menu;
    foreach ( $menu as $pos => $item ) {
        $slug = $item[2] ?? '';
        if ( $slug === 'upload.php' || $slug === 'edit.php?post_type=pp_video_block' ) {
            unset( $menu[ $pos ] );
            $menu[ 900 + $pos ] = $item;
        }
    }
}, 999 );

add_action( 'admin_head', function () {
    echo '<style>.toplevel_page_user-registration{display:none!important}</style>';
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
