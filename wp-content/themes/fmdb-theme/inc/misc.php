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
