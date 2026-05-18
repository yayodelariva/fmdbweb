<?php
/**
 * Login URL overrides — point WP's login/lostpassword links at our
 * custom front-end pages.
 */

add_filter( 'lostpassword_url', function () {
    return home_url( '/olvide-mi-contrasena/' );
} );

add_filter( 'login_url', function ( $url, $redirect ) {
    $custom = home_url( '/login/' );
    return $redirect ? add_query_arg( 'redirect_to', urlencode( $redirect ), $custom ) : $custom;
}, 10, 2 );
