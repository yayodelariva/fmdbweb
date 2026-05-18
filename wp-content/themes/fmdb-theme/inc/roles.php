<?php
/**
 * Roles: register `jugador`, remove unused WP defaults, gate wp-admin
 * access for player-facing roles, hide admin bar for non-admins.
 *
 * Note: roles `representante_equipo` and `editor_fmdb` are referenced
 * elsewhere in the theme but are managed by the Members plugin (or were
 * created via WP admin) rather than registered here.
 */

// Register custom roles and remove unused WP defaults
add_action( 'init', function () {
    if ( ! get_role( 'jugador' ) ) {
        add_role( 'jugador', 'Jugador', [ 'read' => true ] );
    }
    foreach ( [ 'subscriber', 'contributor', 'author', 'editor' ] as $r ) {
        remove_role( $r );
    }
} );

// Block all WP admin access for jugadores and representantes — front-end only
add_action( 'admin_init', function () {
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return;
    $user  = wp_get_current_user();
    $roles = (array) $user->roles;
    if ( array_intersect( $roles, [ 'jugador', 'representante_equipo' ] ) ) {
        wp_safe_redirect( home_url( '/mi-equipo/' ) );
        exit;
    }
} );

// Hide admin bar for everyone except administrators
add_action( 'after_setup_theme', function () {
    if ( ! current_user_can( 'manage_options' ) ) {
        show_admin_bar( false );
    }
} );
