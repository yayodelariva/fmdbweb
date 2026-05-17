<?php
/**
 * Plugin Name: FMDB Login Customizations
 */

add_filter( 'login_message', function ( $message ) {
    $url = home_url( '/registro/' );
    $message .= '<p style="text-align:center;margin-top:16px;font-size:0.88rem;">'
        . '¿No tienes cuenta? <a href="' . esc_url( $url ) . '" style="color:#1D9E75;font-weight:600;">Regístrate aquí</a>'
        . '</p>';
    return $message;
} );
